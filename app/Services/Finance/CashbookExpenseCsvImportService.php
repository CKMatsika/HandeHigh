<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\Cashbook;
use App\Models\MigrationBatch;
use App\Models\MigrationStagingRow;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Stages source-supported Cashbook expense rows before an explicit accounting post. */
class CashbookExpenseCsvImportService
{
    private const IMPORT_TYPE = 'cashbook_expense_csv';

    private const REQUIRED_HEADERS = [
        'transaction_date', 'source_reference', 'voucher_number', 'description', 'amount',
        'category', 'cash_account_code', 'offset_account_code', 'source_evidence',
    ];

    public function template(): string
    {
        return implode(',', self::REQUIRED_HEADERS).",notes\r\n";
    }

    public function stage(UploadedFile $file, int $schoolId, User $user): MigrationBatch
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'The CSV file could not be read.']);
        }

        try {
            $headers = fgetcsv($handle);
            if (! is_array($headers) || $headers === []) {
                throw ValidationException::withMessages(['file' => 'The CSV file must contain the Cashbook template header row.']);
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $headers = array_map(fn ($value) => strtolower(trim((string) $value)), $headers);
            $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headers));
            if ($missing) {
                throw ValidationException::withMessages(['file' => 'Missing required CSV columns: '.implode(', ', $missing).'.']);
            }
            $sourceRows = [];
            $csvLine = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $csvLine++;
                if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }
                $row = [];
                foreach ($headers as $header => $name) {
                    $row[$name] = trim((string) ($values[$header] ?? ''));
                }
                $row['_csv_row'] = $csvLine;
                $sourceRows[] = $row;
                if (count($sourceRows) > 50) {
                    throw ValidationException::withMessages(['file' => 'Upload no more than 50 expense rows at a time. Split the file into smaller batches to keep posting responsive and recoverable.']);
                }
            }
        } finally {
            fclose($handle);
        }

        if (! $sourceRows) {
            throw ValidationException::withMessages(['file' => 'The CSV contains no transaction rows.']);
        }

        foreach ($sourceRows as $source) {
            if (! $this->parseDate($source['transaction_date'])) {
                throw ValidationException::withMessages(['file' => 'CSV line '.($source['_csv_row']).' must have a valid, explicit YYYY-MM-DD date. Correct the file and upload again.']);
            }
        }

        return DB::transaction(function () use ($sourceRows, $file, $schoolId, $user) {
            $batch = MigrationBatch::create([
                'school_id' => $schoolId,
                'batch_number' => 'CB-CSV-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(3))),
                'import_type' => self::IMPORT_TYPE,
                'status' => 'uploaded',
                'file_name' => basename($file->getClientOriginalName()),
                'total_rows' => count($sourceRows),
                'valid_rows' => 0,
                'error_rows' => count($sourceRows),
                'uploaded_by' => $user->id,
                'metadata' => ['source_kind' => 'cashbook_expense_csv', 'created_at' => now()->toISOString()],
            ]);

            $seenKeys = [];
            foreach ($sourceRows as $source) {
                $date = $this->parseDate($source['transaction_date']);
                $amountCents = $this->amountCents($source['amount']);
                $sourceReference = $source['source_reference'];
                $idempotencyKey = hash('sha256', implode('|', [
                    $schoolId, $date ?? $source['transaction_date'], strtoupper($sourceReference), $amountCents, 'expense',
                ]));
                $errors = $this->validateSourceRow($source, $schoolId, $date, $amountCents, $idempotencyKey, $seenKeys);
                if ($sourceReference !== '') {
                    $seenKeys[strtoupper($sourceReference)] = true;
                }

                $cash = Account::where('school_id', $schoolId)->where('code', $source['cash_account_code'])->first();
                $offset = Account::where('school_id', $schoolId)->where('code', $source['offset_account_code'])->first();
                $parsed = [
                    'transaction_type' => 'expense',
                    'category' => $source['category'],
                    'cash_account_id' => $cash?->id,
                    'offset_account_id' => $offset?->id,
                    'voucher_number' => $source['voucher_number'],
                    'source_evidence' => $source['source_evidence'],
                    'notes' => $source['notes'] ?? '',
                ];
                $errors = array_merge($errors, $this->validateMappings($schoolId, $date, $sourceReference, $amountCents, $parsed));
                $errors = array_values(array_unique($errors));

                MigrationStagingRow::create([
                    'migration_batch_id' => $batch->id,
                    'school_id' => $schoolId,
                    'row_number' => $source['_csv_row'],
                    'accounting_date' => $date,
                    'reference_number' => $sourceReference ?: null,
                    'description' => $source['description'] ?: 'Unspecified expense',
                    'amount' => $amountCents / 100,
                    'direction' => 'credit',
                    'classification' => 'expense',
                    'account_code' => $source['cash_account_code'] ?: null,
                    'raw_data' => $source,
                    'parsed_data' => $parsed,
                    'status' => $errors ? 'unresolved' : 'validated',
                    'validation_errors' => $errors ?: null,
                    'idempotency_key' => $idempotencyKey,
                ]);
            }

            $this->refreshBatchStatus($batch);
            AuditLog::create([
                'school_id' => $schoolId,
                'user_id' => $user->id,
                'action' => 'cashbook_expense_csv_staged',
                'module' => 'finance',
                'model_type' => MigrationBatch::class,
                'model_id' => $batch->id,
                'description' => 'Cashbook expense CSV staged for review; no cashbook or ledger entries were posted.',
                'new_values' => ['batch_number' => $batch->batch_number, 'row_count' => count($sourceRows)],
            ]);

            return $batch->fresh();
        });
    }

    public function updateMapping(MigrationBatch $batch, MigrationStagingRow $row, array $input): MigrationStagingRow
    {
        $parsed = $row->parsed_data ?? [];
        $parsed['cash_account_id'] = (int) $input['cash_account_id'];
        $parsed['offset_account_id'] = (int) $input['offset_account_id'];
        $parsed['category'] = trim($input['category']);
        $row->parsed_data = $parsed;

        $seen = [];
        foreach ($batch->stagingRows as $stagedRow) {
            if ($stagedRow->id === $row->id) {
                break;
            }
            if ($stagedRow->reference_number) {
                $seen[strtoupper($stagedRow->reference_number)] = true;
            }
        }
        $errors = $this->validateStagedRow($batch, $row, $parsed, $seen);
        $row->validation_errors = $errors ?: null;
        $row->status = $errors ? 'unresolved' : 'validated';
        $row->account_code = Account::where('school_id', $batch->school_id)->whereKey($parsed['cash_account_id'])->value('code');
        $row->save();
        $this->refreshBatchStatus($batch);

        return $row->fresh();
    }

    public function validateBatch(MigrationBatch $batch): MigrationBatch
    {
        $seen = [];
        foreach ($batch->stagingRows as $row) {
            if ($row->status === 'posted') {
                if ($row->reference_number) {
                    $seen[strtoupper($row->reference_number)] = true;
                }
                continue;
            }

            $errors = $this->validateStagedRow($batch, $row, $row->parsed_data ?? [], $seen);
            $row->validation_errors = $errors ?: null;
            $row->status = $errors ? 'unresolved' : 'validated';
            $row->save();
            if ($row->reference_number) {
                $seen[strtoupper($row->reference_number)] = true;
            }
        }

        $this->refreshBatchStatus($batch);
        return $batch->fresh();
    }

    public function post(MigrationBatch $batch, User $user): array
    {
        return DB::transaction(function () use ($batch, $user) {
            $batch = MigrationBatch::whereKey($batch->id)->where('school_id', $user->school_id)->lockForUpdate()->firstOrFail();
            if ($batch->import_type !== self::IMPORT_TYPE || ! in_array($batch->status, ['uploaded', 'validated', 'partially_posted'], true)) {
                throw new RuntimeException('This Cashbook expense batch is not available for posting.');
            }

            $rows = MigrationStagingRow::where('migration_batch_id', $batch->id)
                ->where('status', 'validated')
                ->orderBy('accounting_date')->orderBy('row_number')->lockForUpdate()->get();
            if ($rows->isEmpty() || $rows->contains(fn ($row) => ! empty($row->validation_errors))) {
                throw new RuntimeException('There are no validated expense rows ready to post. Resolve and validate at least one row first.');
            }

            $posted = 0;
            $debitCents = 0;
            $creditCents = 0;
            foreach ($rows as $row) {
                $existingStage = MigrationStagingRow::where('school_id', $user->school_id)
                    ->where('idempotency_key', $row->idempotency_key)
                    ->where('id', '!=', $row->id)
                    ->where('status', 'posted')->exists();
                if ($existingStage || Cashbook::where('school_id', $user->school_id)
                    ->where('reference_number', $row->reference_number)
                    ->whereDate('transaction_date', $row->accounting_date)
                    ->where('amount', $row->amount)->exists()) {
                    throw new RuntimeException('A source reference/date/amount is already posted; the batch was not changed.');
                }

                $parsed = $row->parsed_data ?? [];
                $cashAccount = Account::where('school_id', $user->school_id)->whereKey($parsed['cash_account_id'] ?? 0)->lockForUpdate()->firstOrFail();
                $offset = Account::where('school_id', $user->school_id)->whereKey($parsed['offset_account_id'] ?? 0)->lockForUpdate()->firstOrFail();
                $this->assertAccountMappings($cashAccount, $offset);
                $periodOpen = AccountingPeriod::where('school_id', $user->school_id)
                    ->where('status', 'open')
                    ->whereDate('start_date', '<=', $row->accounting_date)
                    ->whereDate('end_date', '>=', $row->accounting_date)
                    ->exists();
                if (! $periodOpen) {
                    throw new RuntimeException('An explicitly open accounting period covering '.$row->accounting_date->format('Y-m-d').' is required.');
                }
                app(AccountingPeriodService::class)->assertPeriodOpen($row->accounting_date, $user->school_id, 'post Cashbook CSV expense');

                $cashbook = Cashbook::create([
                    'school_id' => $user->school_id,
                    'account_id' => $cashAccount->id,
                    'offset_account_id' => $offset->id,
                    'transaction_type' => 'expense',
                    'classification' => 'direct_expense',
                    'category' => $parsed['category'],
                    'description' => $row->description,
                    'amount' => $row->amount,
                    'currency' => 'USD',
                    'balance_after' => $this->balanceAt($cashAccount, $row->accounting_date) - (float) $row->amount,
                    'transaction_date' => $row->accounting_date,
                    'reference_number' => $row->reference_number,
                    'voucher_number' => $parsed['voucher_number'] ?? null,
                    'payment_method' => null,
                    'supporting_document_ref' => $parsed['source_evidence'] ?? null,
                    'notes' => trim(implode(' | ', array_filter([
                        'CSV expense import '.$batch->batch_number,
                        $parsed['notes'] ?? null,
                    ]))),
                    'status' => 'active',
                    'created_by' => $user->id,
                ]);

                $journal = app(AccountingService::class)->postManualCashbookTransaction($cashbook);
                $row->update([
                    'status' => 'posted',
                    'matched_entity_type' => Cashbook::class,
                    'matched_entity_id' => $cashbook->id,
                    'journal_batch_id' => $journal->id,
                ]);
                AuditLog::create([
                    'school_id' => $user->school_id,
                    'user_id' => $user->id,
                    'action' => 'cashbook_expense_csv_posted',
                    'module' => 'finance',
                    'model_type' => Cashbook::class,
                    'model_id' => $cashbook->id,
                    'description' => 'Reviewed Cashbook expense CSV row posted to Cashbook and General Ledger.',
                    'new_values' => ['batch_number' => $batch->batch_number, 'source_reference' => $row->reference_number, 'journal_batch_id' => $journal->id],
                ]);
                $cents = $this->amountCents($row->amount);
                $debitCents += $cents;
                $creditCents += $cents;
                $posted++;
            }

            $postedRowCount = MigrationStagingRow::where('migration_batch_id', $batch->id)->where('status', 'posted')->count();
            $validatedRowCount = MigrationStagingRow::where('migration_batch_id', $batch->id)->where('status', 'validated')->count();
            $heldRowCount = MigrationStagingRow::where('migration_batch_id', $batch->id)->whereNotIn('status', ['validated', 'posted'])->count();
            $complete = $postedRowCount === (int) $batch->total_rows;
            $batch->update([
                'status' => $complete ? 'posted' : ($postedRowCount > 0 ? 'partially_posted' : ($heldRowCount === 0 ? 'validated' : 'uploaded')),
                'valid_rows' => $validatedRowCount,
                'error_rows' => $heldRowCount,
                'posted_rows' => $postedRowCount,
                'total_debit' => ((int) round((float) $batch->total_debit * 100) + $debitCents) / 100,
                'total_credit' => ((int) round((float) $batch->total_credit * 100) + $creditCents) / 100,
                'is_balanced' => ((int) round((float) $batch->total_debit * 100) + $debitCents) === ((int) round((float) $batch->total_credit * 100) + $creditCents),
                'approved_by' => $user->id,
                'approved_at' => now(),
                'posted_at' => now(),
            ]);

            return ['batch' => $batch->fresh(), 'posted_count' => $posted, 'debit_cents' => $debitCents, 'credit_cents' => $creditCents];
        });
    }

    private function validateSourceRow(array $source, int $schoolId, ?string $date, int $amountCents, string $key, array $seenKeys, ?int $ignoreRowId = null): array
    {
        $errors = [];
        if (! $date) {
            $errors[] = 'Use an explicit ISO date in YYYY-MM-DD format.';
        }
        if ($amountCents <= 0) {
            $errors[] = 'Expense amount must be a positive numeric amount.';
        }
        if ($source['source_reference'] === '') {
            $errors[] = 'Stable source_reference is required for duplicate prevention.';
        }
        if ($source['description'] === '') {
            $errors[] = 'Original transaction description is required.';
        }
        if ($source['source_evidence'] === '') {
            $errors[] = 'source_evidence is required (for example, the source workbook sheet and row).';
        }
        if (strlen($source['source_reference']) > 100) {
            $errors[] = 'source_reference must not exceed 100 characters.';
        }
        if (strlen($source['description']) > 255) {
            $errors[] = 'Description must not exceed 255 characters.';
        }
        if (strlen($source['source_evidence']) > 255) {
            $errors[] = 'source_evidence must not exceed 255 characters.';
        }
        if ($source['source_reference'] !== '' && isset($seenKeys[strtoupper($source['source_reference'])])) {
            $errors[] = 'Duplicate source_reference within the uploaded CSV.';
        }
        if ($date && $source['source_reference'] !== '' && Cashbook::where('school_id', $schoolId)
            ->where('reference_number', $source['source_reference'])->whereDate('transaction_date', $date)->where('amount', $amountCents / 100)->exists()) {
            $errors[] = 'This source reference, date and amount already exist in the Cashbook.';
        }
        if (MigrationStagingRow::where('school_id', $schoolId)->where('idempotency_key', $key)
            ->when($ignoreRowId, fn ($query) => $query->where('id', '!=', $ignoreRowId))->exists()) {
            $errors[] = 'This source row already exists in a previous import batch.';
        }

        return $errors;
    }

    private function validateMappings(int $schoolId, ?string $date, string $reference, int $amountCents, array $parsed): array
    {
        $errors = [];
        if (! $date) {
            $errors[] = 'Transaction date is invalid.';
        } else {
            $openPeriodExists = AccountingPeriod::where('school_id', $schoolId)
                ->where('status', 'open')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();
            if (! $openPeriodExists) {
                $errors[] = 'An explicitly open accounting period covering this date is required.';
            }
        }
        $cash = Account::where('school_id', $schoolId)->whereKey($parsed['cash_account_id'] ?? 0)->first();
        $offset = Account::where('school_id', $schoolId)->whereKey($parsed['offset_account_id'] ?? 0)->first();
        if (! $cash) {
            $errors[] = 'Select a valid same-school cash or bank account.';
        }
        if (! $offset) {
            $errors[] = 'Select an offset Chart of Accounts account before posting.';
        }
        if ($cash && $offset) {
            try {
                $this->assertAccountMappings($cash, $offset);
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        if (empty($parsed['category']) || strlen((string) $parsed['category']) > 50) {
            $errors[] = 'Select a valid transaction category.';
        }
        if ($reference === '' || $amountCents <= 0) {
            $errors[] = 'A source reference and positive amount are required.';
        }

        return array_values(array_unique($errors));
    }

    private function validateStagedRow(MigrationBatch $batch, MigrationStagingRow $row, array $parsed, array $seen): array
    {
        $source = $row->raw_data ?? [];
        $errors = $this->validateSourceRow(
            $source,
            (int) $batch->school_id,
            $row->accounting_date?->toDateString(),
            $this->amountCents($row->amount),
            (string) $row->idempotency_key,
            $seen,
            (int) $row->id,
        );
        $errors = array_merge($errors, $this->validateMappings(
            (int) $batch->school_id,
            $row->accounting_date?->toDateString(),
            (string) $row->reference_number,
            $this->amountCents($row->amount),
            $parsed,
        ));

        return array_values(array_unique($errors));
    }

    private function assertAccountMappings(Account $cash, Account $offset): void
    {
        if (! $cash->is_active || ! $cash->is_postable || ! in_array($cash->category, ['cash', 'bank'], true)) {
            throw new RuntimeException('Cash account must be active, postable and classified as cash or bank.');
        }
        if (! $offset->is_active || ! $offset->is_postable || ! in_array($offset->type, ['expense', 'liability'], true)) {
            throw new RuntimeException('Expense offset must be an active, postable expense or liability account.');
        }
    }

    private function balanceAt(Account $account, mixed $date): float
    {
        $entries = JournalEntry::query()
            ->where('account_id', $account->id)
            ->whereHas('batch', fn ($query) => $query
                ->where('school_id', $account->school_id)
                ->where('status', 'posted')
                ->whereDate('transaction_date', '<=', $date))
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'debit' THEN amount ELSE 0 END), 0) AS debits")
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE 0 END), 0) AS credits")
            ->first();

        $movement = in_array($account->type, ['asset', 'expense'], true)
            ? ((float) $entries->debits - (float) $entries->credits)
            : ((float) $entries->credits - (float) $entries->debits);

        return (float) $account->opening_balance + $movement;
    }

    private function refreshBatchStatus(MigrationBatch $batch): void
    {
        $rows = $batch->stagingRows()->get();
        $valid = $rows->where('status', 'validated')->count();
        $posted = $rows->where('status', 'posted')->count();
        $errors = $rows->whereNotIn('status', ['validated', 'posted'])->count();
        $complete = $rows->isNotEmpty() && $posted === $rows->count();
        $batch->update([
            'status' => $complete ? 'posted' : ($posted > 0 ? 'partially_posted' : ($errors === 0 && $valid > 0 ? 'validated' : 'uploaded')),
            'valid_rows' => $valid,
            'error_rows' => $errors,
            'posted_rows' => $posted,
            'total_rows' => $rows->count(),
        ]);
    }

    private function parseDate(string $value): ?string
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
            return $date && $date->format('Y-m-d') === $value ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function amountCents(mixed $value): int
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            return 0;
        }

        return (int) round((float) $value * 100, 0, PHP_ROUND_HALF_UP);
    }
}
