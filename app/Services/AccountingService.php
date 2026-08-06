<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\AccountBalance;
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
            // Validate entries are balanced
            $debitTotal = collect($data['entries'])->where('entry_type', 'debit')->sum('amount');
            $creditTotal = collect($data['entries'])->where('entry_type', 'credit')->sum('amount');
            
            if (abs($debitTotal - $creditTotal) > 0.01) {
                throw new \Exception('Journal entries are not balanced. Debits: ' . $debitTotal . ', Credits: ' . $creditTotal);
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
                foreach ($batch->entries as $entry) {
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
     * Post invoice to accounting
     */
    public function postInvoice($invoice): JournalBatch
    {
        $school = $invoice->school;
        
        // Prevent duplicate journal batch postings for the same invoice
        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->first();
            
        if ($existingBatch) {
            return $existingBatch;
        }
        
        // Get or create accounts
        $feesReceivableAccount = Account::where('school_id', $school->id)
            ->whereIn('code', ['1201', '1200']) // Student Fees AR fallback to AR
            ->first();
        
        $feesRevenueAccount = Account::where('school_id', $school->id)
            ->whereIn('code', ['5100', '4100']) // Tuition Revenue
            ->first();
        
        if (!$feesReceivableAccount || !$feesRevenueAccount) {
            throw new \Exception('Required accounts not found. Please ensure Chart of Accounts is set up.');
        }
        
        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $invoice->issued_at,
            'reference_number' => $invoice->number,
            'description' => 'Invoice ' . $invoice->number . ' for ' . ($invoice->student->full_name ?? 'Student'),
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'status' => 'posted',
            'created_by' => auth()->id(),
            'entries' => [
                [
                    'account_id' => $feesReceivableAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $invoice->total_amount,
                    'memo' => 'Invoice ' . $invoice->number,
                ],
                [
                    'account_id' => $feesRevenueAccount->id,
                    'entry_type' => 'credit',
                    'amount' => $invoice->total_amount,
                    'memo' => 'Invoice ' . $invoice->number,
                ],
            ],
        ]);
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
        
        $feesReceivableAccount = Account::where('school_id', $school->id)
            ->whereIn('code', ['1201', '1200'])
            ->first();
        
        if (!$cashAccount || !$feesReceivableAccount) {
            throw new \Exception('Required accounts not found.');
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

        // Use main bank account code for now
        $fromAccount = Account::where('school_id', $school->id)
            ->where('code', '1301')
            ->firstOrFail();
        $toAccount = Account::where('school_id', $school->id)
            ->where('code', '1301')
            ->firstOrFail();

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
     * Post a vendor bill to the general ledger
     * Debit: Expense/Asset account, Credit: Accounts Payable
     */
    public function postBill($bill): JournalBatch
    {
        $school = $bill->school;

        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'bill')
            ->where('source_id', $bill->id)
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        $accountsPayable = Account::where('school_id', $school->id)
            ->whereIn('code', ['2100', '2101'])
            ->first();

        $expenseAccount = Account::where('school_id', $school->id)
            ->where('type', 'expense')
            ->orderBy('code')
            ->first();

        if (!$accountsPayable || !$expenseAccount) {
            throw new \Exception('Required accounts not found. Please ensure Chart of Accounts has Accounts Payable and Expense accounts.');
        }

        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $bill->bill_date,
            'reference_number' => $bill->bill_number,
            'description' => 'Bill ' . $bill->bill_number . ' - ' . ($bill->vendor->name ?? 'Vendor'),
            'source_type' => 'bill',
            'source_id' => $bill->id,
            'status' => 'posted',
            'created_by' => $bill->created_by ?? auth()->id(),
            'entries' => [
                [
                    'account_id' => $expenseAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $bill->total_amount,
                    'memo' => 'Bill ' . $bill->bill_number,
                ],
                [
                    'account_id' => $accountsPayable->id,
                    'entry_type' => 'credit',
                    'amount' => $bill->total_amount,
                    'memo' => 'Bill ' . $bill->bill_number . ' - ' . ($bill->vendor->name ?? 'Vendor'),
                ],
            ],
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

        $existingBatch = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'bill_payment')
            ->where('source_id', $payment->id)
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        $cashAccountCode = match($payment->payment_method) {
            'cash' => '1102',
            'bank_transfer' => '1301',
            'check' => '1301',
            'mobile_money' => '1303',
            default => '1102',
        };

        $cashAccount = Account::where('school_id', $school->id)
            ->where('code', $cashAccountCode)
            ->first();

        $accountsPayable = Account::where('school_id', $school->id)
            ->whereIn('code', ['2100', '2101'])
            ->first();

        if (!$cashAccount || !$accountsPayable) {
            throw new \Exception('Required accounts not found for bill payment posting.');
        }

        return $this->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $payment->payment_date,
            'reference_number' => $payment->reference ?? 'BPAY-' . $payment->id,
            'description' => 'Payment for bill ' . $bill->bill_number,
            'source_type' => 'bill_payment',
            'source_id' => $payment->id,
            'status' => 'posted',
            'created_by' => $payment->created_by ?? auth()->id(),
            'entries' => [
                [
                    'account_id' => $accountsPayable->id,
                    'entry_type' => 'debit',
                    'amount' => $payment->amount,
                    'memo' => 'Payment for bill ' . $bill->bill_number,
                ],
                [
                    'account_id' => $cashAccount->id,
                    'entry_type' => 'credit',
                    'amount' => $payment->amount,
                    'memo' => 'Payment for bill ' . $bill->bill_number,
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
     * Determine the offsite ledger account based on cashbook category and type
     */
    protected function determineOffsetAccount(int $schoolId, string $category, string $type): ?Account
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

