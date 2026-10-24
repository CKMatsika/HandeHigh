<?php

namespace App\Services\Finance;

use App\Exceptions\MissingAccountMappingException;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Cashbook;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\MigrationBatch;
use App\Models\MigrationStagingRow;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistoricalMigrationService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected FinancialAccountResolver $accountResolver,
        protected CashbookReversalService $reversalService
    ) {}

    /**
     * Create a new historical migration batch.
     */
    public function createBatch(
        School|int $school,
        string $importType,
        string $fileName,
        ?int $uploadedBy = null,
        array $metadata = []
    ): MigrationBatch {
        $schoolId = $school instanceof School ? $school->id : $school;
        $batchNumber = 'MIG-' . date('Ymd') . '-' . strtoupper(substr($importType, 0, 4)) . '-' . strtoupper(bin2hex(random_bytes(3)));

        return MigrationBatch::create([
            'school_id' => $schoolId,
            'batch_number' => $batchNumber,
            'import_type' => $importType,
            'status' => 'uploaded',
            'file_name' => $fileName,
            'total_rows' => 0,
            'valid_rows' => 0,
            'error_rows' => 0,
            'posted_rows' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
            'is_balanced' => true,
            'metadata' => $metadata,
            'uploaded_by' => $uploadedBy ?? auth()->id(),
        ]);
    }

    /**
     * Stage rows into the migration batch.
     */
    public function stageRows(MigrationBatch $batch, array $rows): array
    {
        $stagedRows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $rowNumber = $batch->stagingRows()->count() + 1;

        foreach ($rows as $row) {
            $date = !empty($row['accounting_date'])
                ? Carbon::parse($row['accounting_date'])->toDateString()
                : '2026-01-01';

            $ref = trim($row['reference_number'] ?? '');
            $desc = trim($row['description'] ?? 'Historical migration record');
            $amount = round(abs((float) ($row['amount'] ?? 0)), 2);
            $direction = strtolower($row['direction'] ?? 'debit');
            if (!in_array($direction, ['debit', 'credit'])) {
                $direction = 'debit';
            }

            if ($direction === 'debit') {
                $totalDebit += $amount;
            } else {
                $totalCredit += $amount;
            }

            // Generate deterministic idempotency key
            $idempotencyKey = hash('sha256', implode('|', [
                $batch->school_id,
                $batch->import_type,
                $date,
                $ref,
                sprintf('%.2f', $amount),
                $direction,
                $row['account_code'] ?? '',
                $row['student_admission_number'] ?? '',
            ]));

            $stagingRow = MigrationStagingRow::create([
                'migration_batch_id' => $batch->id,
                'school_id' => $batch->school_id,
                'row_number' => $rowNumber++,
                'accounting_date' => $date,
                'reference_number' => $ref ?: null,
                'description' => $desc,
                'amount' => $amount,
                'direction' => $direction,
                'classification' => $row['classification'] ?? null,
                'account_code' => $row['account_code'] ?? null,
                'vote_code' => $row['vote_code'] ?? null,
                'student_admission_number' => $row['student_admission_number'] ?? null,
                'vendor_name' => $row['vendor_name'] ?? null,
                'bank_identifier' => $row['bank_identifier'] ?? null,
                'raw_data' => $row,
                'parsed_data' => $row,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
            ]);

            $stagedRows[] = $stagingRow;
        }

        $batch->update([
            'total_rows' => $batch->total_rows + count($stagedRows),
            'total_debit' => round((float) $batch->total_debit + $totalDebit, 2),
            'total_credit' => round((float) $batch->total_credit + $totalCredit, 2),
        ]);

        return $stagedRows;
    }

    /**
     * Validate all staging rows in a batch before approval and posting.
     */
    public function validateBatch(MigrationBatch $batch): array
    {
        $rows = $batch->stagingRows()->get();
        $validCount = 0;
        $errorCount = 0;
        $seenKeys = [];

        foreach ($rows as $row) {
            $errors = [];

            // 1. Amount must be positive
            if ($row->amount <= 0) {
                $errors[] = "Amount must be strictly greater than 0.00.";
            }

            // 2. Accounting date check (must not be confused with system timestamp)
            if (empty($row->accounting_date)) {
                $errors[] = "Accounting date is required for historical migration.";
            }

            // 3. Duplicate detection & idempotency within batch and across all previously posted rows
            if (isset($seenKeys[$row->idempotency_key])) {
                $errors[] = "Duplicate transaction detected within the same import batch (Row #{$seenKeys[$row->idempotency_key]} and Row #{$row->row_number}).";
            } else {
                $seenKeys[$row->idempotency_key] = $row->row_number;
            }

            $alreadyPosted = MigrationStagingRow::where('school_id', $batch->school_id)
                ->where('idempotency_key', $row->idempotency_key)
                ->where('status', 'posted')
                ->where('id', '!=', $row->id)
                ->exists();

            if ($alreadyPosted) {
                $errors[] = "Duplicate transaction detected: this exact transaction has already been posted to the general ledger in another batch.";
            }

            // 4. Validate accounts and entity mappings depending on import type
            switch ($batch->import_type) {
                case 'opening_balances':
                case 'journal_adjustments':
                    if ($row->account_code) {
                        $acc = Account::where('school_id', $batch->school_id)
                            ->where('code', $row->account_code)
                            ->first();
                        if (!$acc) {
                            $errors[] = "Chart of accounts code '{$row->account_code}' does not exist for this school.";
                        } elseif (!$acc->is_active || !$acc->is_postable) {
                            $errors[] = "Account '{$row->account_code}' is deactivated or is a non-postable header account.";
                        } else {
                            $row->matched_entity_type = Account::class;
                            $row->matched_entity_id = $acc->id;
                        }
                    } else {
                        $errors[] = "Account code is required for opening balances and journal adjustments.";
                    }
                    break;

                case 'bank_cashbook':
                    // Must have bank identifier or account code
                    $bankAccount = null;
                    if ($row->bank_identifier) {
                        $bankAccount = BankAccount::where('school_id', $batch->school_id)
                            ->where(function ($q) use ($row) {
                                $q->where('account_number', $row->bank_identifier)
                                  ->orWhere('bank_name', 'like', "%{$row->bank_identifier}%")
                                  ->orWhere('account_name', 'like', "%{$row->bank_identifier}%");
                            })
                            ->where('is_active', true)
                            ->first();
                    } elseif ($row->account_code) {
                        $bankAccount = BankAccount::where('school_id', $batch->school_id)
                            ->whereHas('glAccount', fn($q) => $q->where('code', $row->account_code))
                            ->first();
                    }

                    if (!$bankAccount && $row->account_code) {
                        $glAccount = Account::where('school_id', $batch->school_id)->where('code', $row->account_code)->first();
                        if ($glAccount && in_array($glAccount->category, ['cash', 'bank'])) {
                            $row->matched_entity_type = Account::class;
                            $row->matched_entity_id = $glAccount->id;
                        } else {
                            $errors[] = "No valid bank or cash account mapped for identifier '{$row->bank_identifier}' or code '{$row->account_code}'.";
                        }
                    } elseif ($bankAccount) {
                        $row->matched_entity_type = BankAccount::class;
                        $row->matched_entity_id = $bankAccount->id;
                    } else {
                        $errors[] = "Bank or cash account could not be resolved for row #{$row->row_number}.";
                    }

                    // Check offsetting classification
                    if (!empty($row->vote_code)) {
                        try {
                            $this->accountResolver->getFeeVoteRevenueAccount($batch->school_id, $row->vote_code);
                        } catch (MissingAccountMappingException $e) {
                            $errors[] = $e->getMessage();
                        }
                    }
                    break;

                case 'student_receipts':
                case 'historical_invoices':
                    if ($row->student_admission_number) {
                        $student = Student::where('school_id', $batch->school_id)
                            ->where(function ($q) use ($row) {
                                $q->where('admission_number', $row->student_admission_number)
                                  ->orWhere('student_id', $row->student_admission_number);
                            })
                            ->first();
                        if ($student) {
                            $row->matched_entity_type = Student::class;
                            $row->matched_entity_id = $student->id;
                        } else {
                            $errors[] = "Student with admission number '{$row->student_admission_number}' not found in school records.";
                        }
                    } else {
                        $errors[] = "Student admission number is required.";
                    }

                    if (!empty($row->vote_code)) {
                        try {
                            $this->accountResolver->getFeeVoteRevenueAccount($batch->school_id, $row->vote_code);
                        } catch (MissingAccountMappingException $e) {
                            $errors[] = $e->getMessage();
                        }
                    }
                    break;

                case 'supplier_bills':
                    if (empty($row->vendor_name)) {
                        $errors[] = "Supplier / Vendor name is required for supplier bills.";
                    }
                    break;
            }

            if (!empty($errors)) {
                $row->update([
                    'status' => 'unresolved',
                    'validation_errors' => $errors,
                    'matched_entity_type' => $row->matched_entity_type,
                    'matched_entity_id' => $row->matched_entity_id,
                ]);
                $errorCount++;
            } else {
                $row->update([
                    'status' => 'validated',
                    'validation_errors' => null,
                    'matched_entity_type' => $row->matched_entity_type,
                    'matched_entity_id' => $row->matched_entity_id,
                ]);
                $validCount++;
            }
        }

        // For opening_balances or journal_adjustments, check total balance
        $isBalanced = true;
        if (in_array($batch->import_type, ['opening_balances', 'journal_adjustments'])) {
            $diff = abs(round((float)$batch->total_debit - (float)$batch->total_credit, 2));
            $isBalanced = ($diff === 0.0);
        }

        $newStatus = ($errorCount === 0 && $isBalanced) ? 'ready_for_approval' : 'validated';

        $batch->update([
            'valid_rows' => $validCount,
            'error_rows' => $errorCount,
            'is_balanced' => $isBalanced,
            'status' => $newStatus,
        ]);

        return [
            'batch_id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'total_rows' => $batch->total_rows,
            'valid_rows' => $validCount,
            'error_rows' => $errorCount,
            'is_balanced' => $isBalanced,
            'status' => $newStatus,
        ];
    }

    /**
     * Perform dry-run reconciliation before final approval and posting.
     */
    public function dryRunReconciliation(MigrationBatch $batch): array
    {
        $rows = $batch->stagingRows()->where('status', 'validated')->get();

        $accountImpacts = [];
        $netCashImpact = 0.0;
        $debtorsImpact = 0.0;

        foreach ($rows as $row) {
            $amount = (float) $row->amount;
            $code = $row->account_code ?? 'UNSPECIFIED';

            if (!isset($accountImpacts[$code])) {
                $accountImpacts[$code] = ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0];
            }

            if ($row->direction === 'debit') {
                $accountImpacts[$code]['debit'] += $amount;
                $accountImpacts[$code]['net'] += $amount;
            } else {
                $accountImpacts[$code]['credit'] += $amount;
                $accountImpacts[$code]['net'] -= $amount;
            }

            if ($batch->import_type === 'bank_cashbook') {
                $netCashImpact += ($row->direction === 'debit' ? $amount : -$amount);
            } elseif ($batch->import_type === 'student_receipts') {
                $debtorsImpact -= $amount; // Receipts reduce receivables
                $netCashImpact += $amount;
            } elseif ($batch->import_type === 'historical_invoices') {
                $debtorsImpact += $amount; // Invoices increase receivables
            }
        }

        return [
            'batch_number' => $batch->batch_number,
            'import_type' => $batch->import_type,
            'total_valid_rows' => $rows->count(),
            'total_debit' => (float) $batch->total_debit,
            'total_credit' => (float) $batch->total_credit,
            'is_balanced' => $batch->is_balanced,
            'net_cash_impact' => round($netCashImpact, 2),
            'debtors_impact' => round($debtorsImpact, 2),
            'account_impacts' => $accountImpacts,
        ];
    }

    /**
     * Approve batch for final posting.
     */
    public function approveBatch(MigrationBatch $batch, int $userId): MigrationBatch
    {
        if ($batch->status === 'posted') {
            throw new \InvalidArgumentException("Batch {$batch->batch_number} has already been posted.");
        }

        if ($batch->error_rows > 0) {
            throw new \InvalidArgumentException("Batch {$batch->batch_number} cannot be approved because it has {$batch->error_rows} unresolved errors.");
        }

        $batch->update([
            'status' => 'ready_for_approval',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $batch;
    }

    /**
     * Authoritatively post the approved migration batch into the General Ledger and subledgers.
     */
    public function postBatch(MigrationBatch $batch, ?int $userId = null): array
    {
        if ($batch->status === 'posted') {
            throw new \InvalidArgumentException("Batch {$batch->batch_number} has already been posted to the General Ledger.");
        }

        $userId = $userId ?? auth()->id() ?? $batch->approved_by ?? $batch->uploaded_by ?? 1;
        $school = $batch->school;

        $rows = $batch->stagingRows()->where('status', 'validated')->get();
        if ($rows->isEmpty()) {
            throw new \InvalidArgumentException("No validated staging rows to post in batch {$batch->batch_number}.");
        }

        $postedRowsCount = 0;
        $createdBatches = [];

        DB::beginTransaction();
        try {
            switch ($batch->import_type) {
                case 'opening_balances':
                    $glBalances = [];
                    foreach ($rows as $row) {
                        $acc = Account::where('school_id', $school->id)->where('code', $row->account_code)->first();
                        if ($acc) {
                            $glBalances[] = [
                                'account_id' => $acc->id,
                                'debit' => $row->direction === 'debit' ? (float) $row->amount : 0,
                                'credit' => $row->direction === 'credit' ? (float) $row->amount : 0,
                                'memo' => $row->description,
                            ];
                        }
                    }

                    $asOfDate = $rows->first()->accounting_date->toDateString();
                    $journalBatch = $this->accountingService->postOpeningBalances(
                        $school,
                        $glBalances,
                        $asOfDate,
                        $userId,
                        $batch->batch_number
                    );

                    foreach ($rows as $row) {
                        $row->update([
                            'status' => 'posted',
                            'journal_batch_id' => $journalBatch->id,
                        ]);
                        $postedRowsCount++;
                    }
                    $createdBatches[] = $journalBatch->id;
                    break;

                case 'bank_cashbook':
                    foreach ($rows as $row) {
                        $accountId = null;
                        if ($row->matched_entity_type === BankAccount::class) {
                            $bank = BankAccount::find($row->matched_entity_id);
                            $glAcc = $this->accountResolver->getBankAccountGLAccount($bank);
                            $accountId = $glAcc->id;
                        } elseif ($row->matched_entity_type === Account::class) {
                            $accountId = $row->matched_entity_id;
                        } else {
                            $glAcc = Account::where('school_id', $school->id)->where('code', $row->account_code)->first();
                            $accountId = $glAcc?->id;
                        }

                        $txType = ($row->direction === 'debit') ? 'income' : 'expense';
                        $classification = $row->classification ?? ($txType === 'income' ? 'debtor_receipt' : 'direct_expense');

                        $cashbook = Cashbook::create([
                            'school_id' => $school->id,
                            'account_id' => $accountId,
                            'transaction_type' => $txType,
                            'category' => $row->vote_code ?? 'general',
                            'classification' => $classification,
                            'description' => $row->description,
                            'amount' => $row->amount,
                            'balance_after' => 0, // Recalculated dynamically
                            'transaction_date' => $row->accounting_date->toDateString(),
                            'payment_method' => 'bank_transfer',
                            'reference_number' => $row->reference_number ?: "MIG-{$row->id}",
                            'voucher_number' => $row->reference_number ?: "MIG-{$row->id}",
                            'notes' => "Historical Migration Batch: {$batch->batch_number}",
                            'vote_code' => $row->vote_code,
                            'status' => 'active',
                            'created_by' => $userId,
                        ]);

                        $jb = $this->accountingService->postManualCashbookTransaction($cashbook);

                        $row->update([
                            'status' => 'posted',
                            'journal_batch_id' => $jb->id,
                        ]);
                        $createdBatches[] = $jb->id;
                        $postedRowsCount++;
                    }
                    break;

                case 'historical_invoices':
                    foreach ($rows as $row) {
                        $student = Student::find($row->matched_entity_id);
                        $invoiceNumber = $row->reference_number ?: ('INV-MIG-' . $row->id);

                        $existingInvoice = Invoice::where('school_id', $school->id)
                            ->where('number', $invoiceNumber)
                            ->first();

                        if (!$existingInvoice) {
                            $invoice = Invoice::create([
                                'school_id' => $school->id,
                                'student_id' => $student->id,
                                'number' => $invoiceNumber,
                                'issued_at' => $row->accounting_date->toDateString(),
                                'due_date' => $row->accounting_date->toDateString(),
                                'total_amount' => $row->amount,
                                'balance' => $row->amount,
                                'status' => 'issued',
                            ]);

                            InvoiceItem::create([
                                'invoice_id' => $invoice->id,
                                'vote_code' => $row->vote_code ?? 'TUITION',
                                'description' => $row->description,
                                'quantity' => 1,
                                'unit_amount' => $row->amount,
                                'line_total' => $row->amount,
                            ]);
                        } else {
                            $invoice = $existingInvoice;
                        }

                        $jb = $this->accountingService->postInvoice($invoice);

                        $row->update([
                            'status' => 'posted',
                            'journal_batch_id' => $jb->id,
                        ]);
                        $createdBatches[] = $jb->id;
                        $postedRowsCount++;
                    }
                    break;

                case 'student_receipts':
                    foreach ($rows as $row) {
                        $student = Student::find($row->matched_entity_id);
                        $bankAccount = BankAccount::where('school_id', $school->id)->where('is_active', true)->first();
                        $cashAccount = $bankAccount ? $this->accountResolver->getBankAccountGLAccount($bankAccount) : null;

                        $payment = Payment::create([
                            'school_id' => $school->id,
                            'student_id' => $student->id,
                            'reference' => $row->reference_number ?: ('REC-MIG-' . $row->id),
                            'amount' => $row->amount,
                            'paid_at' => $row->accounting_date->toDateString(),
                            'method' => 'bank_transfer',
                            'account_id' => $cashAccount?->id,
                            'status' => 'completed',
                            'created_by' => $userId,
                        ]);

                        // Check if student has outstanding invoices to allocate against
                        $unpaidInvoice = Invoice::where('student_id', $student->id)
                            ->where('balance', '>', 0)
                            ->orderBy('issued_at')
                            ->first();

                        if ($unpaidInvoice) {
                            $jb = $this->accountingService->postPayment($payment, $unpaidInvoice, $cashAccount?->id);
                        } else {
                            $jb = $this->accountingService->postAdvancePayment($payment, $cashAccount?->id);
                        }

                        $row->update([
                            'status' => 'posted',
                            'journal_batch_id' => $jb->id,
                        ]);
                        $createdBatches[] = $jb->id;
                        $postedRowsCount++;
                    }
                    break;

                case 'journal_adjustments':
                    $entries = [];
                    foreach ($rows as $row) {
                        $acc = Account::where('school_id', $school->id)->where('code', $row->account_code)->first();
                        if ($acc) {
                            $entries[] = [
                                'account_id' => $acc->id,
                                'entry_type' => $row->direction,
                                'amount' => (float) $row->amount,
                                'memo' => $row->description,
                            ];
                        }
                    }

                    $firstDate = $rows->first()->accounting_date->toDateString();
                    $jb = $this->accountingService->createJournalBatch([
                        'school_id' => $school->id,
                        'transaction_date' => $firstDate,
                        'reference_number' => $batch->batch_number,
                        'description' => "Historical Journal Adjustments - {$batch->batch_number}",
                        'source_type' => 'adjustment',
                        'source_id' => $batch->id,
                        'status' => 'posted',
                        'created_by' => $userId,
                        'entries' => $entries,
                    ]);

                    foreach ($rows as $row) {
                        $row->update([
                            'status' => 'posted',
                            'journal_batch_id' => $jb->id,
                        ]);
                        $postedRowsCount++;
                    }
                    $createdBatches[] = $jb->id;
                    break;

                default:
                    throw new \InvalidArgumentException("Unsupported import type: {$batch->import_type}");
            }

            $batch->update([
                'status' => 'posted',
                'posted_rows' => $postedRowsCount,
                'posted_at' => now(),
            ]);

            DB::commit();

            return [
                'batch_number' => $batch->batch_number,
                'status' => 'posted',
                'posted_rows' => $postedRowsCount,
                'journal_batches_created' => array_unique($createdBatches),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to post migration batch {$batch->batch_number}: " . $e->getMessage(), ['exception' => $e]);
            $batch->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Safely rollback a posted migration batch.
     */
    public function rollbackBatch(MigrationBatch $batch, int $userId, string $reason): array
    {
        if ($batch->status !== 'posted') {
            throw new \InvalidArgumentException("Batch {$batch->batch_number} is not in 'posted' status.");
        }

        DB::beginTransaction();
        try {
            $reversedCount = 0;
            $rows = $batch->stagingRows()->where('status', 'posted')->whereNotNull('journal_batch_id')->get();

            $journalBatchIds = $rows->pluck('journal_batch_id')->unique();
            foreach ($journalBatchIds as $jbId) {
                $jb = JournalBatch::find($jbId);
                if ($jb && $jb->status === 'posted') {
                    // Create reversing batch
                    $reversingEntries = [];
                    foreach ($jb->entries as $entry) {
                        $reversingEntries[] = [
                            'account_id' => $entry->account_id,
                            'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit',
                            'amount' => (float) $entry->amount,
                            'memo' => "Rollback of Batch {$batch->batch_number} - {$entry->memo}",
                        ];
                    }

                    $this->accountingService->createJournalBatch([
                        'school_id' => $batch->school_id,
                        'transaction_date' => now()->toDateString(),
                        'reference_number' => 'REV-' . $jb->reference_number,
                        'description' => "Reversal of {$jb->reference_number}: {$reason}",
                        'source_type' => 'adjustment',
                        'source_id' => $jb->id,
                        'status' => 'posted',
                        'created_by' => $userId,
                        'entries' => $reversingEntries,
                    ]);

                    $jb->update(['status' => 'reversed']);
                    $reversedCount++;
                }
            }

            foreach ($rows as $row) {
                $row->update(['status' => 'pending']);
            }

            $batch->update([
                'status' => 'rolled_back',
                'posted_rows' => 0,
            ]);

            DB::commit();

            return [
                'batch_number' => $batch->batch_number,
                'status' => 'rolled_back',
                'batches_reversed' => $reversedCount,
                'reason' => $reason,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to rollback migration batch {$batch->batch_number}: " . $e->getMessage());
            throw $e;
        }
    }
}
