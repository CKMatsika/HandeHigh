<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\MigrationBatch;
use App\Models\MigrationStagingRow;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/** Approved AFC postings. Bank statement matches remain evidence and never create cash. */
class AfcCashbookImportService
{
    public function __construct(private AfcCashbookImportPlanner $planner) {}

    public function execute(array $manifest, array $snapshot, array $evidence, User $maker, string $deployedCommit): array
    {
        $schoolId = (int) ($manifest['school_id'] ?? 0);
        $checker = User::find($evidence['checker_id'] ?? 0);
        $manifestHash = hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $snapshotHash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $snapshotAt = CarbonImmutable::parse($snapshot['exported_at'] ?? '2000-01-01');
        $backupAt = CarbonImmutable::parse($evidence['backup_verified_at'] ?? '2000-01-01');
        $now = CarbonImmutable::now();
        if ($schoolId < 1 || ($snapshot['format'] ?? null) !== 'hande-term1-reconciliation-v1'
            || (int) ($snapshot['school']['id'] ?? 0) !== $schoolId
            || ($snapshot['database_driver'] ?? null) !== DB::getDriverName()
            || ($evidence['database_driver'] ?? null) !== DB::getDriverName() || ($evidence['manifest_sha256'] ?? null) !== $manifestHash
            || ($evidence['snapshot_sha256'] ?? null) !== $snapshotHash || ($evidence['production_branch'] ?? null) !== 'main'
            || ($evidence['deployment_commit'] ?? null) !== $deployedCommit || ! preg_match('/^[a-f0-9]{40}$/D', $deployedCommit)
            || empty($evidence['deployment_reference']) || empty($evidence['approval_reference'])
            || empty($evidence['backup_reference']) || empty($evidence['backup_restore_verified'])
            || $snapshotAt->isFuture() || $snapshotAt->lt($now->subDay()) || $backupAt->isFuture() || $backupAt->lt($now->subDay())
            || empty($evidence['cleared_references']) || ($evidence['maker_id'] ?? null) !== $maker->id) {
            throw new RuntimeException('AFC execution evidence is incomplete or does not match the approved manifest and current snapshot.');
        }
        if (! $maker->is_active || (int) $maker->school_id !== $schoolId || ! $maker->hasAnyRole(['school-admin', 'super-admin'])
            || ! $checker || ! $checker->is_active || (int) $checker->school_id !== $schoolId || $checker->id === $maker->id
            || ! $checker->hasAnyRole(['school-admin', 'super-admin'])) {
            throw new RuntimeException('Active same-school administrator maker and independent checker are required.');
        }
        if (! app()->runningUnitTests() && ! in_array(DB::getDriverName(), ['pgsql', 'mysql'], true)) {
            throw new RuntimeException('Execution requires the verified production database driver.');
        }
        $preview = $this->planner->preview($manifest, $schoolId);
        $ready = collect($preview['rows'])->where('status', 'READY')->keyBy('source_reference');
        $references = $evidence['cleared_references'];
        if (count($references) !== count(array_unique($references))) {
            throw new RuntimeException('Approved AFC source references must be unique.');
        }
        foreach ($references as $reference) {
            if (! $ready->has($reference)) {
                throw new RuntimeException('A cleared AFC row is held or absent from the approved manifest.');
            }
        }

        return DB::transaction(function () use ($manifest, $schoolId, $references, $ready, $checker, $maker, $evidence) {
            School::whereKey($schoolId)->lockForUpdate()->firstOrFail();
            $already = [];
            foreach ($references as $reference) {
                $row = collect($manifest['rows'])->firstWhere('source_reference', $reference);
                $previewRow = $ready->get($reference);
                $prior = MigrationStagingRow::where('school_id', $schoolId)->where('idempotency_key', $previewRow['idempotency_key'])->lockForUpdate()->first();
                if (! $prior) {
                    continue;
                }
                $sourceHash = hash('sha256', json_encode($row, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
                $priorJournal = $prior->journalBatch()->first();
                if ($prior->status !== 'posted' || ($prior->parsed_data['source_hash'] ?? null) !== $sourceHash
                    || ! $priorJournal || $priorJournal->status !== 'posted' || ! $priorJournal->isBalanced()) {
                    throw new RuntimeException('Prior AFC import conflicts with the approved source row or its journal.');
                }
                $already[] = $prior->id;
            }
            if (count($already) === count($references)) {
                return ['batch_id' => null, 'posted_or_previously_posted' => count($already), 'staging_row_ids' => $already];
            }
            $batch = app(HistoricalMigrationService::class)->createBatch($schoolId, 'afc_cashbook_2026_jan_apr', 'approved-afc-manifest.json', $maker->id,
                ['manifest_sha256' => $evidence['manifest_sha256'], 'snapshot_sha256' => $evidence['snapshot_sha256'],
                    'approval_reference' => $evidence['approval_reference'], 'backup_reference' => $evidence['backup_reference']]);
            $postedIds = [];
            $postedAmountCents = 0;
            Auth::login($maker);
            foreach ($references as $index => $reference) {
                $row = collect($manifest['rows'])->firstWhere('source_reference', $reference);
                $preview = $ready->get($reference);
                $key = $preview['idempotency_key'];
                $prior = MigrationStagingRow::where('school_id', $schoolId)->where('idempotency_key', $key)->lockForUpdate()->first();
                $sourceHash = hash('sha256', json_encode($row, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
                if ($prior) {
                    if ($prior->status !== 'posted' || ($prior->parsed_data['source_hash'] ?? null) !== $sourceHash) {
                        throw new RuntimeException('Prior AFC import conflicts with this source row; manual recovery is required.');
                    }
                    $postedIds[] = $prior->id;
                    continue;
                }
                $date = CarbonImmutable::parse($row['transaction_date'])->toDateString();
                $amount = $preview['amount_cents'] / 100;
                $this->assertNoDuplicateSourcePosting($schoolId, $row, $date, $amount);
                $staging = MigrationStagingRow::create(['migration_batch_id' => $batch->id, 'school_id' => $schoolId,
                    'row_number' => $index + 1, 'accounting_date' => $date, 'reference_number' => $reference,
                    'description' => trim($row['description']), 'amount' => $amount,
                    'direction' => in_array($row['transaction_type'], ['student_receipt', 'other_income'], true) ? 'debit' : 'credit',
                    'classification' => $row['transaction_type'], 'account_code' => (string) $row['account_id'],
                    'student_admission_number' => $row['student_admission_number'] ?? null, 'raw_data' => $row,
                    'parsed_data' => ['source_hash' => $sourceHash], 'status' => 'validated', 'idempotency_key' => $key]);
                $journal = match ($row['transaction_type']) {
                    'student_receipt' => $this->postReceipt($schoolId, $row, $preview, $amount, $maker),
                    'expense', 'other_income' => $this->postCashbook($schoolId, $row, $date, $amount, $maker),
                    'transfer' => $this->postTransfer($schoolId, $row, $date, $amount, $maker, $staging),
                    default => throw new RuntimeException('Unsupported AFC posting category.'),
                };
                $staging->update(['status' => 'posted', 'journal_batch_id' => $journal->id,
                    'matched_entity_type' => match ($row['transaction_type']) {
                        'student_receipt' => Payment::class,
                        'transfer' => JournalBatch::class,
                        default => Cashbook::class,
                    },
                    'matched_entity_id' => $row['transaction_type'] === 'student_receipt'
                        ? Payment::where('school_id', $schoolId)->where('mopse_receipt_number', $row['receipt_number'])->value('id')
                        : ($row['transaction_type'] === 'transfer' ? $journal->id
                            : Cashbook::where('school_id', $schoolId)->where('reference_number', $reference)->value('id'))]);
                AuditLog::create(['school_id' => $schoolId, 'user_id' => $maker->id, 'action' => 'afc_cashbook_imported',
                    'module' => 'finance', 'model_type' => MigrationStagingRow::class, 'model_id' => $staging->id,
                    'new_values' => ['source_reference' => $reference, 'transaction_type' => $row['transaction_type'],
                        'journal_batch_id' => $journal->id, 'checker_id' => $checker->id, 'approval_reference' => $evidence['approval_reference']],
                    'description' => 'Approved AFC source transaction imported with original date and reference.']);
                $postedIds[] = $staging->id;
                $postedAmountCents += $preview['amount_cents'];
            }
            $batch->update(['status' => 'posted', 'total_rows' => count($references), 'valid_rows' => count($references) - count($already),
                'posted_rows' => count($references) - count($already), 'total_debit' => $postedAmountCents / 100,
                'total_credit' => $postedAmountCents / 100, 'is_balanced' => true, 'posted_at' => now()]);

            return ['batch_id' => $batch->id, 'posted_or_previously_posted' => count($postedIds), 'staging_row_ids' => $postedIds];
        });
    }

    private function postReceipt(int $schoolId, array $row, array $preview, float $amount, User $maker): JournalBatch
    {
        $invoice = Invoice::where('school_id', $schoolId)->whereKey($preview['invoice_id'])->lockForUpdate()->firstOrFail();
        $student = Student::where('school_id', $schoolId)->whereKey($preview['student_id'])->firstOrFail();
        if ($amount > (float) $invoice->balance) {
            throw new RuntimeException('Invoice balance changed before posting.');
        }
        $payment = Payment::create(['school_id' => $schoolId, 'student_id' => $student->id, 'created_by' => $maker->id,
            'method' => $row['payment_method'] ?? 'other', 'account_id' => (int) $row['account_id'],
            'reference' => $row['source_reference'], 'mopse_receipt_number' => $row['receipt_number'],
            'amount' => $amount, 'paid_at' => $row['transaction_date'], 'status' => 'completed']);
        PaymentAllocation::create(['payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount' => $amount]);
        $balance = max(0, round((float) $invoice->balance - $amount, 2));
        $invoice->update(['balance' => $balance, 'status' => $balance === 0.0 ? 'paid' : 'partial']);

        return app(AccountingService::class)->postPayment($payment, $invoice, (int) $row['account_id']);
    }

    private function postCashbook(int $schoolId, array $row, string $date, float $amount, User $maker): JournalBatch
    {
        $inflow = $row['transaction_type'] === 'other_income';
        $cashbook = Cashbook::create(['school_id' => $schoolId, 'account_id' => (int) $row['account_id'],
            'offset_account_id' => (int) $row['offset_account_id'], 'transaction_type' => $inflow ? 'income' : 'expense',
            'category' => $row['category'], 'classification' => $inflow ? 'direct_revenue' : 'direct_expense',
            'description' => trim($row['description']), 'amount' => $amount, 'balance_after' => 0,
            'transaction_date' => $date, 'reference_number' => $row['source_reference'],
            'voucher_number' => $row['voucher_number'] ?? null, 'payment_method' => 'bank_transfer',
            'status' => 'active', 'created_by' => $maker->id, 'notes' => 'Approved AFC import; source evidence: '.$row['source_evidence']]);

        return app(AccountingService::class)->postManualCashbookTransaction($cashbook);
    }

    private function postTransfer(int $schoolId, array $row, string $date, float $amount, User $maker, MigrationStagingRow $staging): JournalBatch
    {
        $from = Account::where('school_id', $schoolId)->findOrFail($row['account_id']);
        $to = Account::where('school_id', $schoolId)->findOrFail($row['to_account_id']);
        $journal = app(AccountingService::class)->createJournalBatch(['school_id' => $schoolId, 'transaction_date' => $date,
            'reference_number' => $row['source_reference'], 'description' => trim($row['description']),
            'source_type' => 'afc_transfer_import', 'source_id' => $staging->id, 'status' => 'posted', 'created_by' => $maker->id,
            'entries' => [['account_id' => $to->id, 'entry_type' => 'debit', 'amount' => $amount, 'memo' => trim($row['description'])],
                ['account_id' => $from->id, 'entry_type' => 'credit', 'amount' => $amount, 'memo' => trim($row['description'])]]]);
        foreach ([[$from, 'expense', 'credit'], [$to, 'income', 'debit']] as [$account, $direction, $entryType]) {
            $entry = $journal->entries->firstWhere('account_id', $account->id);
            $cash = Cashbook::create(['school_id' => $schoolId, 'account_id' => $account->id, 'transaction_type' => $direction,
                'category' => 'transfer', 'classification' => 'bank_transfer', 'description' => trim($row['description']),
                'amount' => $amount, 'balance_after' => 0, 'transaction_date' => $date,
                'reference_number' => $row['source_reference'].'-'.$direction, 'payment_method' => 'transfer',
                'status' => 'posted', 'journal_batch_id' => $journal->id, 'created_by' => $maker->id]);
            CashbookTransaction::create(['school_id' => $schoolId, 'account_id' => $account->id, 'transaction_date' => $date,
                'reference_number' => $cash->reference_number, 'description' => $cash->description, 'amount' => $amount,
                'transaction_type' => $entryType, 'category' => 'transfer', 'journal_entry_id' => $entry?->id,
                'status' => 'unmatched', 'created_by' => $maker->id]);
        }

        return $journal;
    }

    private function assertNoDuplicateSourcePosting(int $schoolId, array $row, string $date, float $amount): void
    {
        if ($row['transaction_type'] === 'student_receipt' && Payment::where('school_id', $schoolId)
            ->where('mopse_receipt_number', $row['receipt_number'])->exists()) {
            throw new RuntimeException('Receipt number already exists for this school.');
        }
        if (Cashbook::where('school_id', $schoolId)->where('reference_number', $row['source_reference'])
            ->where('transaction_date', $date)->where('amount', $amount)->exists()) {
            throw new RuntimeException('AFC reference/date/amount already exists in the cashbook.');
        }
    }
}
