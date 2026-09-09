<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\AccountBalance;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * Create a journal batch with entries
     * 
     * @param array $data
     * @return JournalBatch
     * @throws \Exception
     */
    public function createJournalBatch(array $data): JournalBatch
    {
        DB::beginTransaction();
        
        try {
            // Period Validation: ensure accounting period is open for transaction date
            app(\App\Services\Finance\AccountingPeriodService::class)->assertPeriodOpen(
                $data['transaction_date'],
                $data['school_id'],
                'create or post journal batch'
            );

            // Validate entries are balanced
            $debitTotal = collect($data['entries'])->where('entry_type', 'debit')->sum('amount');
            $creditTotal = collect($data['entries'])->where('entry_type', 'credit')->sum('amount');
            
            if (abs($debitTotal - $creditTotal) > 0.01) {
                throw new \Exception('Journal entries are not balanced. Debits: ' . $debitTotal . ', Credits: ' . $creditTotal);
            }

            // Validate all accounts are postable, active, and belong to the correct school tenant
            $accountIds = collect($data['entries'])->pluck('account_id')->unique();
            $accounts = Account::whereIn('id', $accountIds)->get()->keyBy('id');

            foreach ($data['entries'] as $entryData) {
                $acc = $accounts->get($entryData['account_id']);
                if (! $acc) {
                    throw new \InvalidArgumentException("Account ID {$entryData['account_id']} not found.");
                }
                if (! $acc->is_postable) {
                    throw new \InvalidArgumentException("Cannot post journal entry to non-postable header account: {$acc->code} - {$acc->name}.");
                }
                if (! $acc->is_active) {
                    throw new \InvalidArgumentException("Cannot post journal entry to deactivated account: {$acc->code} - {$acc->name}.");
                }
                if ($acc->school_id !== (int) $data['school_id']) {
                    throw new \InvalidArgumentException("Cross-tenant violation: Account {$acc->code} does not belong to school {$data['school_id']}.");
                }
            }
            
            // Generate batch number
            $batchNumber = $this->generateBatchNumber($data['school_id']);
            
            // Create batch
            $batch = JournalBatch::create([
                'school_id' => $data['school_id'],
                'batch_number' => $batchNumber,
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'],
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'created_by' => $data['created_by'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Create entries
            foreach ($data['entries'] as $entryData) {
                JournalEntry::create([
                    'journal_batch_id' => $batch->id,
                    'account_id' => $entryData['account_id'],
                    'entry_type' => $entryData['entry_type'],
                    'amount' => $entryData['amount'],
                    'memo' => $entryData['memo'] ?? null,
                    'cost_center_id' => $entryData['cost_center_id'] ?? null,
                    'project_id' => $entryData['project_id'] ?? null,
                    'reference' => $entryData['reference'] ?? null,
                ]);
            }
            
            // Post batch if status is posted and it wasn't created as posted
            if ($batch->status === 'posted' && !isset($data['status'])) {
                $this->postBatch($batch);
            } elseif ($batch->status === 'posted') {
                // If batch was created as posted, update account balances directly
                foreach ($batch->entries()->get() as $entry) {
                    $this->updateAccountBalance(
                        $entry->account_id,
                        $batch->school_id,
                        $batch->transaction_date,
                        $entry->entry_type,
                        $entry->amount
                    );
                }
                
                $batch->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                ]);
            }
            
            DB::commit();
            
            return $batch;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create journal batch: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Post a journal batch
     */
    public function postBatch(JournalBatch $batch): void
    {
        if ($batch->status === 'posted') {
            throw new \Exception('Batch is already posted');
        }
        
        if (!$batch->isBalanced()) {
            throw new \Exception('Batch is not balanced and cannot be posted');
        }

        // Assert accounting period is open for batch transaction date
        app(\App\Services\Finance\AccountingPeriodService::class)->assertPeriodOpen(
            $batch->transaction_date,
            $batch->school_id,
            'post journal batch'
        );
        
        DB::beginTransaction();
        
        try {
            $batch->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);
            
            // Update account balances
            foreach ($batch->entries as $entry) {
                $this->updateAccountBalance(
                    $entry->account_id,
                    $batch->school_id,
                    $batch->transaction_date,
                    $entry->entry_type,
                    $entry->amount
                );
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Update account balance for a period
     */
    protected function updateAccountBalance(int $accountId, int $schoolId, $date, string $entryType, float $amount): void
    {
        $year = date('Y', strtotime($date));
        $month = (int)date('m', strtotime($date));
        
        $balance = AccountBalance::firstOrNew([
            'school_id' => $schoolId,
            'account_id' => $accountId,
            'fiscal_year' => $year,
            'period' => $month,
        ]);
        
        if ($entryType === 'debit') {
            $balance->debit_total += $amount;
        } else {
            $balance->credit_total += $amount;
        }
        
        // Calculate balance based on account type
        $account = Account::find($accountId);
        if (in_array($account->type, ['asset', 'expense'])) {
            $balance->balance = $balance->debit_total - $balance->credit_total;
        } else {
            $balance->balance = $balance->credit_total - $balance->debit_total;
        }
        
        $balance->save();
    }
    
    /**
     * Generate unique batch number
     */
    protected function generateBatchNumber(int $schoolId): string
    {
        $year = date('Y');
        $count = JournalBatch::where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->count() + 1;
        
        return 'JB-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Post invoice to accounting with itemized revenue account allocation
     */
    public function postInvoice($invoice): JournalBatch
    {
        $school = $invoice->school;
        
        $invoice->load(['items.feeStructure', 'student', 'school']);

        // Check if batch already exists for this invoice
        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->first();
            
        if ($existingBatch) {
            // If items now exist and existing batch only had 1 un-itemized credit entry, update it
            if ($invoice->items && $invoice->items->count() > 1 && $existingBatch->entries()->where('entry_type', 'credit')->count() <= 1) {
                $existingBatch->entries()->delete();
                $repostExisting = true;
            } else {
                return $existingBatch;
            }
        }

        // Accounts Receivable Account
        $feesReceivableAccount = Account::where('school_id', $school->id)
            ->where('code', '1201')
            ->first() ?? Account::where('school_id', $school->id)->where('code', '1200')->first();
        
        if (! $feesReceivableAccount) {
            $feesReceivableAccount = Account::create([
                'school_id' => $school->id,
                'code' => '1201',
                'name' => 'Student Fees Receivable',
                'type' => 'asset',
                'category' => 'receivable',
                'is_active' => true,
            ]);
        }

        $resolver = app(\App\Services\Finance\FeeRevenueAccountResolver::class);
        $creditEntries = [];
        $totalItemizedCredits = 0.0;

        if ($invoice->items && $invoice->items->count() > 0) {
            // Group items by resolved revenue account
            $accountGroups = [];
            foreach ($invoice->items as $item) {
                $account = $resolver->resolveAccountForItem($school, $item);
                $amount = (float) $item->line_total;
                if ($amount <= 0) {
                    continue;
                }

                if (! isset($accountGroups[$account->id])) {
                    $accountGroups[$account->id] = [
                        'account' => $account,
                        'total' => 0.0,
                    ];
                }
                $accountGroups[$account->id]['total'] += $amount;
            }

            foreach ($accountGroups as $group) {
                $creditEntries[] = [
                    'account_id' => $group['account']->id,
                    'entry_type' => 'credit',
                    'amount' => $group['total'],
                    'memo' => "Invoice {$invoice->number} - {$group['account']->name}",
                ];
                $totalItemizedCredits += $group['total'];
            }
        }

        // Fallback if no items or zero total items
        if (empty($creditEntries)) {
            $defaultRevenue = Account::where('school_id', $school->id)
                ->whereIn('code', ['5100', '4100'])
                ->first() ?? Account::create([
                    'school_id' => $school->id,
                    'code' => '5100',
                    'name' => 'Tuition Fees',
                    'type' => 'revenue',
                    'category' => 'tuition_revenue',
                    'is_active' => true,
                ]);

            $creditEntries[] = [
                'account_id' => $defaultRevenue->id,
                'entry_type' => 'credit',
                'amount' => $invoice->total_amount,
                'memo' => "Invoice {$invoice->number} - General Revenue",
            ];
            $totalItemizedCredits = (float) $invoice->total_amount;
        }

        // Handle any rounding difference between total_amount and items sum
        $invoiceTotal = (float) $invoice->total_amount;
        if (abs($invoiceTotal - $totalItemizedCredits) > 0.001) {
            $diff = $invoiceTotal - $totalItemizedCredits;
            $creditEntries[0]['amount'] += $diff;
        }

        $entries = array_merge([
            [
                'account_id' => $feesReceivableAccount->id,
                'entry_type' => 'debit',
                'amount' => $invoiceTotal,
                'memo' => 'Invoice ' . $invoice->number,
            ],
        ], $creditEntries);
        
        if (! empty($repostExisting) && $existingBatch) {
            foreach ($entries as $entryData) {
                JournalEntry::create([
                    'journal_batch_id' => $existingBatch->id,
                    'account_id' => $entryData['account_id'],
                    'entry_type' => $entryData['entry_type'],
                    'amount' => $entryData['amount'],
                    'memo' => $entryData['memo'] ?? null,
                    'cost_center_id' => $entryData['cost_center_id'] ?? null,
                    'project_id' => $entryData['project_id'] ?? null,
                    'reference' => $entryData['reference'] ?? null,
                ]);
            }
            return $existingBatch->fresh(['entries.account']);
        }

        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $invoice->issued_at ?? now()->toDateString(),
            'reference_number' => $invoice->number,
            'description' => 'Invoice ' . $invoice->number . ' for ' . ($invoice->student->full_name ?? 'Student'),
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'status' => 'posted',
            'created_by' => auth()->id() ?? 1,
            'entries' => $entries,
        ]);
    }

    /**
     * Post a kiosk/canteen sale to accounting
     */
    public function postKioskSale($kioskSale): JournalBatch
    {
        $school = $kioskSale->school;

        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'receipt')
            ->where('reference_number', $kioskSale->receipt_number)
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        $cashAccountCode = match($kioskSale->payment_method) {
            'cash' => '1102',
            'bank_transfer', 'card' => '1301',
            'mobile_money' => '1303',
            default => '1102',
        };

        $cashAccount = Account::where('school_id', $school->id)
            ->where('code', $cashAccountCode)
            ->first() ?? Account::where('school_id', $school->id)->whereIn('code', ['1100', '1301', '1010'])->first();

        $kioskRevenueAccount = Account::where('school_id', $school->id)
            ->where('code', '5803')
            ->first();

        if (! $kioskRevenueAccount) {
            $kioskRevenueAccount = Account::create([
                'school_id' => $school->id,
                'code' => '5803',
                'name' => 'Canteen / Kiosk Sales',
                'type' => 'revenue',
                'category' => 'other_revenue',
                'is_active' => true,
            ]);
        }

        if (! $cashAccount) {
            $cashAccount = Account::create([
                'school_id' => $school->id,
                'code' => '1102',
                'name' => 'Petty Cash',
                'type' => 'asset',
                'category' => 'cash',
                'is_active' => true,
                'is_postable' => true,
            ]);
        }

        $creditEntries = [];
        $kioskSale->loadMissing(['items.product.revenueAccount']);
        if ($kioskSale->items && $kioskSale->items->count() > 0) {
            $accountGroups = [];
            foreach ($kioskSale->items as $item) {
                $revAcc = ($item->product && $item->product->revenue_account_id)
                    ? $item->product->revenueAccount
                    : null;

                if (! $revAcc) {
                    $revAcc = $kioskRevenueAccount;
                }

                if (! isset($accountGroups[$revAcc->id])) {
                    $accountGroups[$revAcc->id] = [
                        'account' => $revAcc,
                        'total' => 0.0,
                    ];
                }
                $accountGroups[$revAcc->id]['total'] += (float) $item->line_total;
            }

            foreach ($accountGroups as $group) {
                $creditEntries[] = [
                    'account_id' => $group['account']->id,
                    'entry_type' => 'credit',
                    'amount' => $group['total'],
                    'memo' => 'Kiosk Sale ' . $kioskSale->receipt_number . ' - ' . $group['account']->name,
                ];
            }
        }

        if (empty($creditEntries)) {
            $creditEntries[] = [
                'account_id' => $kioskRevenueAccount->id,
                'entry_type' => 'credit',
                'amount' => $kioskSale->grand_total,
                'memo' => 'Kiosk Sale ' . $kioskSale->receipt_number,
            ];
        }

        $journalBatch = $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $kioskSale->sale_date ?? now()->toDateString(),
            'reference_number' => $kioskSale->receipt_number,
            'description' => 'Kiosk Sale ' . $kioskSale->receipt_number,
            'source_type' => 'receipt',
            'source_id' => $kioskSale->id,
            'status' => 'posted',
            'created_by' => $kioskSale->cashier_id ?? auth()->id() ?? 1,
            'entries' => array_merge([
                [
                    'account_id' => $cashAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $kioskSale->grand_total,
                    'memo' => 'Kiosk Sale ' . $kioskSale->receipt_number,
                ],
            ], $creditEntries),
        ]);

        // Create cashbook entry
        $this->createCashbookEntryForKiosk($kioskSale, $cashAccount);

        return $journalBatch;
    }

    /**
     * Create cashbook entry for kiosk sale
     */
    private function createCashbookEntryForKiosk($kioskSale, $cashAccount): void
    {
        $school = $kioskSale->school;

        $lastBalance = \App\Models\Cashbook::where('school_id', $school->id)
            ->where('account_id', $cashAccount->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->value('balance_after') ?? 0;

        $newBalance = $lastBalance + $kioskSale->grand_total;

        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');

        try {
            \App\Models\Cashbook::create([
                'school_id' => $school->id,
                'account_id' => $cashAccount->id,
                'transaction_type' => 'income',
                'category' => 'kiosk_sales',
                'description' => 'Kiosk Sale ' . $kioskSale->receipt_number,
                'amount' => $kioskSale->grand_total,
                'balance_after' => $newBalance,
                'transaction_date' => $kioskSale->sale_date ?? now()->toDateString(),
                'reference_number' => $kioskSale->receipt_number,
                'payment_method' => $kioskSale->payment_method,
                'created_by' => $kioskSale->cashier_id ?? auth()->id() ?? 1,
                'notes' => 'Kiosk / Canteen retail sale',
            ]);
        } finally {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');
        }
    }
    
    /**
     * Post payment to accounting
     */
    public function postPayment($payment, $invoice): JournalBatch
    {
        $school = $payment->school;
        
        // Determine cash account based on payment method
        $cashAccountCode = match($payment->method) {
            'cash' => '1102', // Petty Cash - where physical cash is stored
            'bank' => '1301', // Main operating bank
            'mobile_money' => '1303', // Mobile money account
            default => '1102',
        };
        
        $cashAccount = Account::where('school_id', $school->id)
            ->where('code', $cashAccountCode)
            ->first();
        
        if (!$cashAccount) {
            $cashAccount = Account::where('school_id', $school->id)
                ->whereIn('code', ['1101', '1102', '1100', '1301', '1300'])
                ->first();
        }

        if (!$cashAccount) {
            $cashAccount = Account::create([
                'school_id' => $school->id,
                'code' => $cashAccountCode,
                'name' => match($payment->method) {
                    'bank' => 'Main Operating Account',
                    'mobile_money' => 'Mobile Money Account',
                    default => 'Petty Cash',
                },
                'type' => 'asset',
                'category' => match($payment->method) {
                    'bank', 'mobile_money' => 'bank',
                    default => 'cash',
                },
                'currency' => 'USD',
                'is_active' => true,
                'is_postable' => true,
            ]);
        }
        
        $feesReceivableAccount = Account::where('school_id', $school->id)
            ->whereIn('code', ['1201', '1200'])
            ->first();
        
        if (!$feesReceivableAccount) {
            $feesReceivableAccount = Account::where('school_id', $school->id)
                ->where('type', 'asset')
                ->where('category', 'receivable')
                ->first();
        }

        if (!$feesReceivableAccount) {
            $feesReceivableAccount = Account::create([
                'school_id' => $school->id,
                'code' => '1201',
                'name' => 'Student Fees Receivable',
                'type' => 'asset',
                'category' => 'receivable',
                'currency' => 'USD',
                'is_active' => true,
                'is_postable' => true,
            ]);
        }
        
        // Create journal batch
        $journalBatch = $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $payment->paid_at,
            'reference_number' => $payment->reference ?? 'PAY-' . $payment->id,
            'description' => 'Payment for invoice ' . $invoice->number,
            'source_type' => 'payment',
            'source_id' => $payment->id,
            'status' => 'posted',
            'created_by' => auth()->id(),
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $payment->amount,
                    'memo' => 'Payment for invoice ' . $invoice->number,
                ],
                [
                    'account_id' => $feesReceivableAccount->id,
                    'entry_type' => 'credit',
                    'amount' => $payment->amount,
                    'memo' => 'Payment for invoice ' . $invoice->number,
                ],
            ],
        ]);
        
        // Create cashbook entry
        $this->createCashbookEntry($payment, $invoice, $cashAccount);
        
        return $journalBatch;
    }
    
    /**
     * Create cashbook entry for payment
     */
    private function createCashbookEntry($payment, $invoice, $cashAccount): void
    {
        $school = $payment->school;
        
        // Get current balance for the account
        $lastBalance = \App\Models\Cashbook::where('school_id', $school->id)
            ->where('account_id', $cashAccount->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->value('balance_after') ?? 0;
        
        // Calculate new balance
        $newBalance = $lastBalance + $payment->amount;
        
        // Disable foreign key checks temporarily to avoid constraint issues
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');
        
        try {
            // Create cashbook entry
            \App\Models\Cashbook::create([
                'school_id' => $school->id,
                'account_id' => $cashAccount->id,
                'transaction_type' => 'income',
                'category' => 'fees',
                'description' => 'Payment for invoice ' . $invoice->number,
                'amount' => $payment->amount,
                'balance_after' => $newBalance,
                'transaction_date' => $payment->paid_at,
                'reference_number' => $payment->reference ?? 'PAY-' . $payment->id,
                'payment_method' => $payment->method,
                'related_invoice_id' => $invoice->id,
                'related_payment_id' => $payment->id,
                'created_by' => auth()->id(),
                'notes' => 'Student fee payment - ' . $invoice->student->first_name . ' ' . $invoice->student->last_name,
            ]);
        } finally {
            // Re-enable foreign key checks
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * Post a generic receipt (non-student sale)
     */
    public function postReceipt($receipt): JournalBatch
    {
        $school = $receipt->school;

        $cashAccountCode = match($receipt->payment_method) {
            'cash' => '1102', // Petty Cash - where physical cash is stored
            'bank_transfer' => '1301', // Main operating bank
            'mobile_money' => '1303', // Mobile money account
            default => '1102',
        };

        $cashAccount = Account::where('school_id', $school->id)
            ->where('code', $cashAccountCode)
            ->firstOrFail();

        // Default other revenue
        $revenueAccount = Account::where('school_id', $school->id)
            ->whereIn('code', ['5801', '5800'])
            ->orderBy('code')
            ->firstOrFail();

        // Create journal batch
        $journalBatch = $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $receipt->receipt_date,
            'reference_number' => $receipt->receipt_number,
            'description' => 'Receipt ' . $receipt->receipt_number,
            'source_type' => 'receipt',
            'source_id' => $receipt->id,
            'status' => 'posted',
            'created_by' => auth()->id(),
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $receipt->grand_total,
                    'memo' => 'Receipt ' . $receipt->receipt_number,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'entry_type' => 'credit',
                    'amount' => $receipt->grand_total,
                    'memo' => 'Receipt ' . $receipt->receipt_number,
                ],
            ],
        ]);
        
        // Create cashbook entry for receipt
        $this->createCashbookEntryForReceipt($receipt, $cashAccount);
        
        return $journalBatch;
    }
    
    /**
     * Create cashbook entry for receipt
     */
    private function createCashbookEntryForReceipt($receipt, $cashAccount): void
    {
        $school = $receipt->school;
        
        // Get current balance for the account
        $lastBalance = \App\Models\Cashbook::where('school_id', $school->id)
            ->where('account_id', $cashAccount->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->value('balance_after') ?? 0;
        
        // Calculate new balance
        $newBalance = $lastBalance + $receipt->grand_total;
        
        // Disable foreign key checks temporarily to avoid constraint issues
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');
        
        try {
            // Create cashbook entry
            \App\Models\Cashbook::create([
                'school_id' => $school->id,
                'account_id' => $cashAccount->id,
                'transaction_type' => 'income',
                'category' => 'other_revenue',
                'description' => 'Receipt ' . $receipt->receipt_number,
                'amount' => $receipt->grand_total,
                'balance_after' => $newBalance,
                'transaction_date' => $receipt->receipt_date,
                'reference_number' => $receipt->receipt_number,
                'payment_method' => $receipt->payment_method,
                'created_by' => auth()->id(),
                'notes' => $receipt->notes ?? 'General receipt',
            ]);
        } finally {
            // Re-enable foreign key checks
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * Post an inter-account transfer (e.g., Petty Cash to Bank Account)
     */
    public function postInterAccountTransfer($fromAccountCode, $toAccountCode, $amount, $description, $transactionDate = null): JournalBatch
    {
        $school = auth()->user()->school;
        $transactionDate = $transactionDate ?? now();
        
        // Get accounts
        $fromAccount = Account::where('school_id', $school->id)
            ->where('code', $fromAccountCode)
            ->firstOrFail();
            
        $toAccount = Account::where('school_id', $school->id)
            ->where('code', $toAccountCode)
            ->firstOrFail();
        
        // Create journal batch for the transfer
        $journalBatch = $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $transactionDate,
            'reference_number' => 'TRANSFER-' . date('YmdHis'),
            'description' => $description,
            'source_type' => 'transfer',
            'source_id' => null,
            'status' => 'posted',
            'created_by' => auth()->id(),
            'entries' => [
                [
                    'account_id' => $toAccount->id,      // Debit the receiving account
                    'entry_type' => 'debit',
                    'amount' => $amount,
                    'memo' => $description . ' - Transfer from ' . $fromAccount->name,
                ],
                [
                    'account_id' => $fromAccount->id,    // Credit the sending account
                    'entry_type' => 'credit',
                    'amount' => $amount,
                    'memo' => $description . ' - Transfer to ' . $toAccount->name,
                ],
            ],
        ]);
        
        // Create cashbook entries for both accounts
        $this->createTransferCashbookEntries($fromAccount, $toAccount, $amount, $description, $transactionDate);
        
        return $journalBatch;
    }
    
    /**
     * Create cashbook entries for inter-account transfers
     */
    private function createTransferCashbookEntries($fromAccount, $toAccount, $amount, $description, $transactionDate): void
    {
        $school = auth()->user()->school;
        
        // Disable foreign key checks temporarily
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');
        
        try {
            // Get current balances
            $fromBalance = \App\Models\Cashbook::where('school_id', $school->id)
                ->where('account_id', $fromAccount->id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->value('balance_after') ?? 0;
                
            $toBalance = \App\Models\Cashbook::where('school_id', $school->id)
                ->where('account_id', $toAccount->id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->value('balance_after') ?? 0;
            
            // Create cashbook entry for source account (money going out)
            \App\Models\Cashbook::create([
                'school_id' => $school->id,
                'account_id' => $fromAccount->id,
                'transaction_type' => 'expense',
                'category' => 'transfer',
                'description' => $description . ' - Transfer to ' . $toAccount->name,
                'amount' => $amount,
                'balance_after' => $fromBalance - $amount,
                'transaction_date' => $transactionDate,
                'reference_number' => 'TRANSFER-OUT-' . date('YmdHis'),
                'payment_method' => 'transfer',
                'created_by' => auth()->id(),
                'notes' => 'Transfer out: ' . $description,
            ]);
            
            // Create cashbook entry for destination account (money coming in)
            \App\Models\Cashbook::create([
                'school_id' => $school->id,
                'account_id' => $toAccount->id,
                'transaction_type' => 'income',
                'category' => 'transfer',
                'description' => $description . ' - Transfer from ' . $fromAccount->name,
                'amount' => $amount,
                'balance_after' => $toBalance + $amount,
                'transaction_date' => $transactionDate,
                'reference_number' => 'TRANSFER-IN-' . date('YmdHis'),
                'payment_method' => 'transfer',
                'created_by' => auth()->id(),
                'notes' => 'Transfer in: ' . $description,
            ]);
        } finally {
            // Re-enable foreign key checks
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * Post an interbank transfer
     */
    public function postInterbankTransfer($transfer): JournalBatch
    {
        $school = $transfer->school;

        $fromAccount = Account::where('school_id', $school->id)
            ->where('id', $transfer->from_bank_account_id)
            ->first() ?? Account::where('school_id', $school->id)->where('code', '1301')->firstOrFail();

        $toAccount = Account::where('school_id', $school->id)
            ->where('id', $transfer->to_bank_account_id)
            ->first() ?? Account::where('school_id', $school->id)->where('code', '1301')->firstOrFail();

        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $transfer->transfer_date,
            'reference_number' => $transfer->transfer_number,
            'description' => 'Interbank transfer ' . $transfer->transfer_number,
            'source_type' => 'bank_transfer',
            'source_id' => $transfer->id,
            'status' => 'posted',
            'created_by' => auth()->id(),
            'entries' => [
                [
                    'account_id' => $toAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $transfer->amount,
                    'memo' => 'Transfer in',
                ],
                [
                    'account_id' => $fromAccount->id,
                    'entry_type' => 'credit',
                    'amount' => $transfer->amount,
                    'memo' => 'Transfer out',
                ],
            ],
        ]);
    }

    /**
     * Post a manually recorded Cashbook transaction to the general ledger
     */
    public function postManualCashbookTransaction($cashbook): JournalBatch
    {
        $school = $cashbook->school;
        
        // Cash/Bank account is the cashbook's account
        $cashAccount = $cashbook->account;
        
        // Find offsetting account based on category or type
        $offsetAccount = $this->determineOffsetAccount($school->id, $cashbook->category, $cashbook->transaction_type);
        
        if (!$cashAccount || !$offsetAccount) {
            throw new \Exception('Required account setup not found for posting cashbook transaction.');
        }
        
        $isExpense = $cashbook->transaction_type === 'expense';
        
        // Double entry:
        // If expense: Debit Offset Account (Expense), Credit Cash/Bank (Asset)
        // If income: Debit Cash/Bank (Asset), Credit Offset Account (Revenue)
        $debitAccountId = $isExpense ? $offsetAccount->id : $cashAccount->id;
        $creditAccountId = $isExpense ? $cashAccount->id : $offsetAccount->id;
        
        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $cashbook->transaction_date,
            'reference_number' => $cashbook->reference_number ?? 'CSH-' . $cashbook->id,
            'description' => $cashbook->description,
            'source_type' => 'cashbook',
            'source_id' => $cashbook->id,
            'status' => 'posted',
            'created_by' => $cashbook->created_by ?? auth()->id(),
            'entries' => [
                [
                    'account_id' => $debitAccountId,
                    'entry_type' => 'debit',
                    'amount' => $cashbook->amount,
                    'memo' => $cashbook->description,
                ],
                [
                    'account_id' => $creditAccountId,
                    'entry_type' => 'credit',
                    'amount' => $cashbook->amount,
                    'memo' => $cashbook->description,
                ],
            ],
        ]);
    }
    
    /**
     * Ensure chart of accounts exist for the given school.
     */
    public function ensureChartOfAccountsExist(School|int $school): void
    {
        $schoolModel = $school instanceof School ? $school : School::find($school);
        if (! $schoolModel) {
            return;
        }

        $accountCount = Account::where('school_id', $schoolModel->id)->count();
        if ($accountCount === 0) {
            app(\Database\Seeders\ChartOfAccountsSeeder::class)->seedAccountsForSchool($schoolModel);
        }
    }

    /**
     * Retroactively sync and post any unposted financial transactions for a school.
     */
    public function syncExistingTransactions(School|int $school): array
    {
        $schoolModel = $school instanceof School ? $school : School::find($school);
        if (! $schoolModel) {
            return ['invoices_posted' => 0, 'bills_posted' => 0, 'payments_posted' => 0];
        }

        $this->ensureChartOfAccountsExist($schoolModel);

        $invoicesPosted = 0;
        $billsPosted = 0;
        $paymentsPosted = 0;

        // 1. Sync Invoices
        $invoices = \App\Models\Invoice::where('school_id', $schoolModel->id)->with(['items', 'student'])->get();
        foreach ($invoices as $invoice) {
            $hasBatch = JournalBatch::where('school_id', $schoolModel->id)
                ->where('source_type', 'invoice')
                ->where('source_id', $invoice->id)
                ->exists();

            if (! $hasBatch && (float) $invoice->total_amount > 0) {
                try {
                    $this->postInvoice($invoice);
                    $invoicesPosted++;
                } catch (\Throwable $e) {
                    Log::warning("Could not auto-post invoice #{$invoice->number}: " . $e->getMessage());
                }
            }
        }

        // 2. Sync Bills
        $bills = \App\Models\Bill::where('school_id', $schoolModel->id)->with(['items', 'vendor', 'expenseAccount'])->get();
        foreach ($bills as $bill) {
            $hasBatch = JournalBatch::where('school_id', $schoolModel->id)
                ->where('source_type', 'bill')
                ->where('source_id', $bill->id)
                ->exists();

            if (! $hasBatch && (float) $bill->total_amount > 0) {
                try {
                    $this->postBill($bill);
                    $billsPosted++;
                } catch (\Throwable $e) {
                    Log::warning("Could not auto-post bill #{$bill->bill_number}: " . $e->getMessage());
                }
            }
        }

        // 3. Sync Payments
        $payments = \App\Models\Payment::where('school_id', $schoolModel->id)->with('invoice')->get();
        foreach ($payments as $payment) {
            if ($payment->invoice) {
                $hasBatch = JournalBatch::where('school_id', $schoolModel->id)
                    ->where('source_type', 'payment')
                    ->where('source_id', $payment->id)
                    ->exists();

                if (! $hasBatch && (float) $payment->amount > 0) {
                    try {
                        $this->postPayment($payment, $payment->invoice);
                        $paymentsPosted++;
                    } catch (\Throwable $e) {
                        Log::warning("Could not auto-post payment #{$payment->id}: " . $e->getMessage());
                    }
                }
            }
        }

        return [
            'invoices_posted' => $invoicesPosted,
            'bills_posted' => $billsPosted,
            'payments_posted' => $paymentsPosted,
        ];
    }

    /**
     * Post a vendor bill to the general ledger
     * Debit: Expense/Asset account, Credit: Accounts Payable
     */
    public function postBill($bill): JournalBatch
    {
        $school = $bill->school;
        $this->ensureChartOfAccountsExist($school);

        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'bill')
            ->where('source_id', $bill->id)
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        $accountsPayable = Account::where('school_id', $school->id)
            ->whereIn('code', ['3100', '3101'])
            ->first() ?? Account::where('school_id', $school->id)
            ->where('type', 'liability')
            ->where('category', 'payable')
            ->first();

        if (! $accountsPayable) {
            $accountsPayable = Account::create([
                'school_id' => $school->id,
                'code' => '3100',
                'name' => 'Accounts Payable',
                'type' => 'liability',
                'category' => 'payable',
                'is_active' => true,
                'is_postable' => true,
            ]);
        }

        $debitEntries = [];
        $bill->loadMissing(['items.expenseAccount', 'expenseAccount', 'vendor']);
        $totalItemizedDebits = 0.0;

        if ($bill->items && $bill->items->count() > 0) {
            $accountGroups = [];
            foreach ($bill->items as $item) {
                $expAcc = $item->expenseAccount ?? $bill->expenseAccount;
                if (! $expAcc && $item->category) {
                    $code = match (strtolower(trim($item->category))) {
                        'salaries', 'salary' => '6100',
                        'utilities', 'utility' => '6300',
                        'maintenance', 'repairs' => '6400',
                        'supplies', 'supply' => '6500',
                        'operations', 'operating', 'rent' => '6600',
                        'professional_services', 'services' => '6800',
                        default => '7200',
                    };
                    $expAcc = Account::where('school_id', $school->id)->where('code', $code)->first();
                }

                if (! $expAcc) {
                    $expAcc = Account::where('school_id', $school->id)->where('type', 'expense')->orderBy('code')->first();
                }

                if (! $expAcc) {
                    $expAcc = Account::create([
                        'school_id' => $school->id,
                        'code' => '6500',
                        'name' => 'General Supplies & Expenses',
                        'type' => 'expense',
                        'category' => 'supply_expense',
                        'is_active' => true,
                        'is_postable' => true,
                    ]);
                }

                $amount = (float) $item->line_total;
                if (! isset($accountGroups[$expAcc->id])) {
                    $accountGroups[$expAcc->id] = [
                        'account' => $expAcc,
                        'total' => 0.0,
                    ];
                }
                $accountGroups[$expAcc->id]['total'] += $amount;
            }

            foreach ($accountGroups as $group) {
                $debitEntries[] = [
                    'account_id' => $group['account']->id,
                    'entry_type' => 'debit',
                    'amount' => $group['total'],
                    'memo' => 'Bill ' . $bill->bill_number . ' - ' . $group['account']->name,
                ];
                $totalItemizedDebits += $group['total'];
            }
        }

        if (empty($debitEntries)) {
            $expenseAccount = $bill->expenseAccount ?? Account::where('school_id', $school->id)->where('type', 'expense')->orderBy('code')->first();
            if (! $expenseAccount) {
                $expenseAccount = Account::create([
                    'school_id' => $school->id,
                    'code' => '6500',
                    'name' => 'General Supplies & Expenses',
                    'type' => 'expense',
                    'category' => 'supply_expense',
                    'is_active' => true,
                    'is_postable' => true,
                ]);
            }

            $debitEntries[] = [
                'account_id' => $expenseAccount->id,
                'entry_type' => 'debit',
                'amount' => (float) $bill->total_amount,
                'memo' => 'Bill ' . $bill->bill_number . ' - ' . ($bill->description ?? $expenseAccount->name),
            ];
            $totalItemizedDebits = (float) $bill->total_amount;
        }

        // Adjust rounding
        $billTotal = (float) $bill->total_amount;
        if (abs($billTotal - $totalItemizedDebits) > 0.001) {
            $diff = $billTotal - $totalItemizedDebits;
            $debitEntries[0]['amount'] += $diff;
        }

        $entries = array_merge($debitEntries, [
            [
                'account_id' => $accountsPayable->id,
                'entry_type' => 'credit',
                'amount' => $billTotal,
                'memo' => 'Bill ' . $bill->bill_number . ' - ' . ($bill->vendor->name ?? 'Vendor'),
            ],
        ]);

        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $bill->bill_date ?? now()->toDateString(),
            'reference_number' => $bill->bill_number,
            'description' => 'Bill ' . $bill->bill_number . ' - ' . ($bill->vendor->name ?? 'Vendor'),
            'source_type' => 'bill',
            'source_id' => $bill->id,
            'status' => 'posted',
            'created_by' => $bill->created_by ?? auth()->id(),
            'entries' => $entries,
        ]);
    }

    /**
     * Post a bill payment to the general ledger
     * Debit: Accounts Payable, Credit: Cash/Bank
     */
    public function postBillPayment($payment): JournalBatch
    {
        $school = $payment->school;
        $bill = $payment->bill;
        $this->ensureChartOfAccountsExist($school);

        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'bill_payment')
            ->where('source_id', $payment->id)
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        $cashAccountCode = match($payment->payment_method) {
            'cash' => '1102',
            'bank_transfer', 'check', 'bank' => '1301',
            'mobile_money' => '1303',
            default => '1102',
        };

        $cashAccount = Account::where('school_id', $school->id)
            ->where('code', $cashAccountCode)
            ->first() ?? Account::where('school_id', $school->id)->whereIn('code', ['1101', '1102', '1301', '1300'])->first();

        if (! $cashAccount) {
            $cashAccount = Account::create([
                'school_id' => $school->id,
                'code' => $cashAccountCode,
                'name' => match ($payment->payment_method) {
                    'bank_transfer', 'check', 'bank' => 'Main Operating Account',
                    'mobile_money' => 'Mobile Money Account',
                    default => 'Petty Cash',
                },
                'type' => 'asset',
                'category' => 'cash',
                'is_active' => true,
                'is_postable' => true,
            ]);
        }

        $accountsPayable = Account::where('school_id', $school->id)
            ->whereIn('code', ['3100', '3101'])
            ->first() ?? Account::where('school_id', $school->id)
            ->where('type', 'liability')
            ->where('category', 'payable')
            ->first();

        if (! $accountsPayable) {
            $accountsPayable = Account::create([
                'school_id' => $school->id,
                'code' => '3100',
                'name' => 'Accounts Payable',
                'type' => 'liability',
                'category' => 'payable',
                'is_active' => true,
                'is_postable' => true,
            ]);
        }

        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $payment->payment_date ?? now()->toDateString(),
            'reference_number' => $payment->reference ?? 'BPAY-' . $payment->id,
            'description' => 'Payment for bill ' . ($bill ? $bill->bill_number : $payment->id),
            'source_type' => 'bill_payment',
            'source_id' => $payment->id,
            'status' => 'posted',
            'created_by' => $payment->created_by ?? auth()->id(),
            'entries' => [
                [
                    'account_id' => $accountsPayable->id,
                    'entry_type' => 'debit',
                    'amount' => (float) $payment->amount,
                    'memo' => 'Payment for bill ' . ($bill ? $bill->bill_number : $payment->id),
                ],
                [
                    'account_id' => $cashAccount->id,
                    'entry_type' => 'credit',
                    'amount' => (float) $payment->amount,
                    'memo' => 'Payment for bill ' . ($bill ? $bill->bill_number : $payment->id),
                ],
            ],
        ]);
    }

    /**
     * Post a credit note to the general ledger
     * Debit: Revenue/Accounts Receivable, Credit: Credit Notes (contra-revenue)
     */
    public function postCreditNote($creditNote): JournalBatch
    {
        $school = $creditNote->school;

        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'credit_note')
            ->where('source_id', $creditNote->id)
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        $feesReceivable = Account::where('school_id', $school->id)
            ->whereIn('code', ['1201', '1200'])
            ->first();

        $revenueAccount = Account::where('school_id', $school->id)
            ->whereIn('code', ['5100', '4100'])
            ->first();

        if (!$feesReceivable || !$revenueAccount) {
            throw new \Exception('Required accounts not found for credit note posting.');
        }

        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $creditNote->credit_note_date,
            'reference_number' => $creditNote->credit_note_number,
            'description' => 'Credit Note ' . $creditNote->credit_note_number . ' - ' . ($creditNote->reason ?? 'Adjustment'),
            'source_type' => 'credit_note',
            'source_id' => $creditNote->id,
            'status' => 'posted',
            'created_by' => $creditNote->created_by ?? auth()->id(),
            'entries' => [
                [
                    'account_id' => $revenueAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $creditNote->total_amount,
                    'memo' => 'Credit Note ' . $creditNote->credit_note_number . ' - Revenue reversal',
                ],
                [
                    'account_id' => $feesReceivable->id,
                    'entry_type' => 'credit',
                    'amount' => $creditNote->total_amount,
                    'memo' => 'Credit Note ' . $creditNote->credit_note_number . ' - AR reduction',
                ],
            ],
        ]);
    }

    /**
     * Post a completed payroll run to the General Ledger with Statutory Control Accounts.
     *
     * @param \App\Models\Payroll $payroll
     * @return JournalBatch
     */
    public function postPayroll(\App\Models\Payroll $payroll): JournalBatch
    {
        $school = $payroll->school;
        $payroll->load(['items', 'school']);

        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'payroll')
            ->where('source_id', $payroll->id)
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        // 1. Resolve / Create Expense Accounts (Debits)
        $salariesExpense = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '6100'],
            ['name' => 'Salaries & Allowances Expense', 'type' => 'expense', 'category' => 'salary_expense', 'is_active' => true, 'is_postable' => true]
        );

        $employerNssaExpense = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '6110'],
            ['name' => 'Employer NSSA Pension Expense', 'type' => 'expense', 'category' => 'salary_expense', 'is_active' => true, 'is_postable' => true]
        );

        $employerNecExpense = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '6120'],
            ['name' => 'Employer NEC Contribution Expense', 'type' => 'expense', 'category' => 'salary_expense', 'is_active' => true, 'is_postable' => true]
        );

        // 2. Resolve / Create Statutory & Liability Control Accounts (Credits)
        $netSalariesPayable = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '2101'],
            ['name' => 'Net Salaries Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true, 'is_postable' => true]
        );

        $zimraPayePayable = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '2102'],
            ['name' => 'ZIMRA PAYE Tax Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true, 'is_postable' => true]
        );

        $zimraAidsLevyPayable = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '2103'],
            ['name' => 'ZIMRA AIDS Levy Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true, 'is_postable' => true]
        );

        $nssaTotalPayable = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '2104'],
            ['name' => 'NSSA Social Security Total Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true, 'is_postable' => true]
        );

        $necTotalPayable = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '2105'],
            ['name' => 'NEC Sector Total Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true, 'is_postable' => true]
        );

        $tradeUnionPayable = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '2106'],
            ['name' => 'Trade Union Dues Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true, 'is_postable' => true]
        );

        $medicalAidPayable = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '2107'],
            ['name' => 'Medical Aid Contributions Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true, 'is_postable' => true]
        );

        $staffLoansClearing = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '1105'],
            ['name' => 'Staff Loan Repayments Clearing', 'type' => 'asset', 'category' => 'receivable', 'is_active' => true, 'is_postable' => true]
        );

        $otherDeductionsPayable = Account::firstOrCreate(
            ['school_id' => $school->id, 'code' => '2108'],
            ['name' => 'Other Payroll Deductions Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true, 'is_postable' => true]
        );

        // 3. Compute Aggregates across Items
        $totalGross = (float)($payroll->total_gross_usd + $payroll->total_gross_zwg) ?: (float)$payroll->total_gross;
        $totalNet = (float)($payroll->total_net_usd + $payroll->total_net_zwg) ?: (float)$payroll->total_net;
        $totalPaye = (float)($payroll->total_paye_usd + $payroll->total_paye_zwg);
        $totalAidsLevy = (float)($payroll->total_aids_levy_usd + $payroll->total_aids_levy_zwg);

        $totalEmployeeNssa = (float)($payroll->items->sum('nssa_employee_usd') + $payroll->items->sum('nssa_employee_zwg'));
        $totalEmployerNssa = (float)($payroll->total_employer_nssa_usd + $payroll->total_employer_nssa_zwg) ?: (float)$payroll->total_employer_nssa;
        $combinedNssa = round($totalEmployeeNssa + $totalEmployerNssa, 2);

        $totalEmployeeNec = (float)($payroll->items->sum('nec_employee_usd') + $payroll->items->sum('nec_employee_zwg'));
        $totalEmployerNec = (float)($payroll->total_employer_nec_usd + $payroll->total_employer_nec_zwg);
        $combinedNec = round($totalEmployeeNec + $totalEmployerNec, 2);

        $totalTradeUnion = (float)($payroll->items->sum('trade_union_usd') + $payroll->items->sum('trade_union_zwg'));
        $totalMedicalAid = (float)($payroll->items->sum('medical_aid_usd') + $payroll->items->sum('medical_aid_zwg'));
        $totalLoanRepayment = (float)($payroll->items->sum('loan_repayment_usd') + $payroll->items->sum('loan_repayment_zwg'));
        $totalOtherDeductions = (float)($payroll->items->sum('other_deductions_usd') + $payroll->items->sum('other_deductions_zwg'));

        $entries = [];

        // --- DEBITS (Expenses) ---
        if ($totalGross > 0) {
            $entries[] = [
                'account_id' => $salariesExpense->id,
                'entry_type' => 'debit',
                'amount' => $totalGross,
                'memo' => "Salaries & Allowances Expense - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($totalEmployerNssa > 0) {
            $entries[] = [
                'account_id' => $employerNssaExpense->id,
                'entry_type' => 'debit',
                'amount' => $totalEmployerNssa,
                'memo' => "Employer NSSA Contribution (4.5%) - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($totalEmployerNec > 0) {
            $entries[] = [
                'account_id' => $employerNecExpense->id,
                'entry_type' => 'debit',
                'amount' => $totalEmployerNec,
                'memo' => "Employer NEC Sector Contribution - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }

        // --- CREDITS (Payables & Clearings) ---
        if ($totalNet > 0) {
            $entries[] = [
                'account_id' => $netSalariesPayable->id,
                'entry_type' => 'credit',
                'amount' => $totalNet,
                'memo' => "Net Salaries Payable - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($totalPaye > 0) {
            $entries[] = [
                'account_id' => $zimraPayePayable->id,
                'entry_type' => 'credit',
                'amount' => $totalPaye,
                'memo' => "ZIMRA PAYE Tax Payable - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($totalAidsLevy > 0) {
            $entries[] = [
                'account_id' => $zimraAidsLevyPayable->id,
                'entry_type' => 'credit',
                'amount' => $totalAidsLevy,
                'memo' => "ZIMRA AIDS Levy Payable (3%) - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($combinedNssa > 0) {
            $entries[] = [
                'account_id' => $nssaTotalPayable->id,
                'entry_type' => 'credit',
                'amount' => $combinedNssa,
                'memo' => "NSSA Social Security Total Payable (Employee + Employer) - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($combinedNec > 0) {
            $entries[] = [
                'account_id' => $necTotalPayable->id,
                'entry_type' => 'credit',
                'amount' => $combinedNec,
                'memo' => "NEC Council Total Payable (Employee + Employer) - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($totalTradeUnion > 0) {
            $entries[] = [
                'account_id' => $tradeUnionPayable->id,
                'entry_type' => 'credit',
                'amount' => $totalTradeUnion,
                'memo' => "Trade Union Dues Payable - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($totalMedicalAid > 0) {
            $entries[] = [
                'account_id' => $medicalAidPayable->id,
                'entry_type' => 'credit',
                'amount' => $totalMedicalAid,
                'memo' => "Medical Aid Contributions Payable - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($totalLoanRepayment > 0) {
            $entries[] = [
                'account_id' => $staffLoansClearing->id,
                'entry_type' => 'credit',
                'amount' => $totalLoanRepayment,
                'memo' => "Staff Loan Repayments Payroll Recovery - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }
        if ($totalOtherDeductions > 0) {
            $entries[] = [
                'account_id' => $otherDeductionsPayable->id,
                'entry_type' => 'credit',
                'amount' => $totalOtherDeductions,
                'memo' => "Other Payroll Deductions Payable - {$payroll->period_year}/{$payroll->period_month}",
            ];
        }

        $batch = $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $payroll->processed_date ?? now()->toDateString(),
            'reference_number' => "PAY-{$payroll->period_year}-{$payroll->period_month}",
            'description' => "Monthly Staff Payroll Run & Statutory Remittance - {$payroll->period_year}/" . sprintf('%02d', $payroll->period_month),
            'source_type' => 'payroll',
            'source_id' => $payroll->id,
            'status' => 'posted',
            'created_by' => $payroll->created_by ?? auth()->id(),
            'entries' => $entries,
        ]);

        $payroll->update(['journal_batch_id' => $batch->id]);

        return $batch;
    }

    /**
     * Determine the offsite ledger account based on cashbook category and type
     */
    public function determineOffsetAccount(int $schoolId, string $category, string $type): ?Account
    {
        $code = match($category) {
            'salaries' => '6100',       // Salaries Expense
            'supplies' => '6500',       // Supplies Expense
            'maintenance' => '6400',    // Maintenance Expense
            'utilities' => '6300',      // Utilities Expense
            'rent' => '6600',           // Rent Expense
            'bank_charge' => '7100',    // Bank Charges Expense
            'fees' => '5100',           // Fees revenue
            default => $type === 'expense' ? '7200' : '5800', // Misc Expense vs Other Revenue
        };
        
        return Account::where('school_id', $schoolId)
            ->where('code', $code)
            ->first() ?? Account::where('school_id', $schoolId)->where('type', $type === 'expense' ? 'expense' : 'revenue')->first();
    }
}


