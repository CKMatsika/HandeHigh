<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MigrationBatch;
use App\Models\MigrationStagingRow;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/** Invoice-only execution. Receipts and unresolved funding stay outside this writer. */
class Term1ControlledImportService
{
    public function __construct(private Term1ImportPlanner $planner, private Term1ReconciliationExportService $export) {}

    public function execute(array $plan, array $evidence, array $sourceSnapshot, User $operator, string $deployedCommit): array
    {
        $this->planner->validateManifest($plan, $sourceSnapshot);
        $schoolId = (int) $plan['school_id'];
        $checker = User::find($evidence['checker_id'] ?? 0);
        $capture = CarbonImmutable::parse($sourceSnapshot['exported_at'] ?? '2000-01-01');
        $backupDate = CarbonImmutable::parse($evidence['backup_verified_at'] ?? '2000-01-01');
        $guardFailures = [];
        foreach ([
            'operator_active' => (bool) $operator->is_active,
            'operator_tenant' => (int) $operator->school_id === $schoolId,
            'operator_role' => $operator->hasAnyRole(['school-admin', 'super-admin']),
            'checker_exists' => (bool) $checker,
            'checker_active' => (bool) ($checker?->is_active),
            'checker_tenant' => (int) ($checker?->school_id ?? 0) === $schoolId,
            'independent_checker' => $checker && $checker->id !== $operator->id,
            'checker_role' => $checker && $checker->hasAnyRole(['school-admin', 'super-admin']),
            'maker_matches_operator' => ($evidence['maker_id'] ?? null) === $operator->id,
            'manifest_hash_matches' => ($evidence['manifest_sha256'] ?? '') === $this->planner->fingerprint($plan),
            'snapshot_hash_matches' => ($evidence['snapshot_sha256'] ?? '') === $this->planner->fingerprint($sourceSnapshot),
            'production_main_branch' => ($evidence['production_branch'] ?? '') === 'main',
            'deployment_reference' => ! empty($evidence['deployment_reference']),
            'deployed_commit_matches' => preg_match('/^[a-f0-9]{40}$/D', $deployedCommit) && ($evidence['deployment_commit'] ?? '') === $deployedCommit,
            'database_driver_matches' => ($evidence['database_driver'] ?? '') === DB::getDriverName() && $sourceSnapshot['database_driver'] === DB::getDriverName(),
            'backup_restore_verified' => ($evidence['backup_restore_verified'] ?? false) === true && ! empty($evidence['backup_reference']),
            'approval_reference' => ! empty($evidence['approval_reference']),
            'snapshot_fresh' => ! $capture->isFuture() && ! $capture->lt(now()->subDay()),
            'backup_fresh' => ! $backupDate->isFuture() && ! $backupDate->lt(now()->subDay()),
            'approved_references' => ! empty($evidence['cleared_references']),
        ] as $guard => $passed) {
            if (! $passed) {
                $guardFailures[] = $guard;
            }
        }
        if ($guardFailures) {
            throw new RuntimeException('Execution prerequisites failed: '.implode(', ', $guardFailures).'.');
        }
        if (! app()->runningUnitTests() && ! in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            throw new RuntimeException('Execution requires the verified managed production database engine.');
        }
        $selected = array_values(array_filter($plan['rows'], fn ($r) => in_array($r['charge_reference'], $evidence['cleared_references'], true)));
        if (count($selected) !== count(array_unique($evidence['cleared_references']))) {
            throw new RuntimeException('Approved references are not unique manifest rows.');
        }

        return DB::transaction(function () use ($plan, $evidence, $selected, $schoolId, $operator, $checker) {
            School::whereKey($schoolId)->lockForUpdate()->firstOrFail();
            Student::where('school_id', $schoolId)->whereIn('id', array_filter(array_column($selected, 'student_id')))->orderBy('id')->lockForUpdate()->get();
            AccountMapping::where('school_id', $schoolId)->orderBy('id')->lockForUpdate()->get();
            Account::where('school_id', $schoolId)->orderBy('id')->lockForUpdate()->get();
            $pending = [];
            $already = [];
            foreach ($selected as $row) {
                $key = hash('sha256', $schoolId.'|2026|Term 1|'.$row['charge_reference']);
                $existing = MigrationStagingRow::where('school_id', $schoolId)->where('idempotency_key', $key)->lockForUpdate()->get();
                if ($existing->count()) {
                    $stage = $existing->sole();
                    if ($stage->status !== 'posted' || ($stage->parsed_data['payload_hash'] ?? null) !== $this->planner->fingerprint($row)
                        || ($stage->parsed_data['source_sha256'] ?? null) !== $plan['source_sha256']) {
                        throw new RuntimeException('Changed or incomplete prior import; reviewed recovery required.');
                    }
                    $invoice = Invoice::where('school_id', $schoolId)->findOrFail($stage->parsed_data['invoice_id']);
                    $journal = $stage->journalBatch()->firstOrFail();
                    if ($invoice->number !== $this->planner->number($row['charge_reference']) || $invoice->academic_year !== '2026'
                        || $invoice->term !== 'Term 1' || $invoice->issued_at->toDateString() !== '2026-01-01'
                        || $invoice->due_date?->toDateString() !== '2026-01-31'
                        || (int) $invoice->student_id !== (int) $stage->matched_entity_id
                        || $this->planner->cents($invoice->total_amount) !== $this->planner->cents($row['expected_amount'])
                        || $journal->status !== 'posted' || $journal->source_type !== 'invoice' || (int) $journal->source_id !== $invoice->id
                        || ! $this->retryPostingMatches($invoice, $journal, $row)) {
                        throw new RuntimeException('Prior posting integrity check failed.');
                    }
                    $already[] = $invoice->id;
                    continue;
                }
                $pending[] = $row;
            }
            if (! $pending) {
                return ['posted' => 0, 'already_posted' => count($already), 'invoice_ids' => $already];
            }
            $current = $this->export->snapshot($schoolId, '2026', false);
            $fullPreview = $this->planner->preview($plan, $current);
            $byReference = array_column($fullPreview['rows'], null, 'reference');
            $checks = array_map(fn ($r) => $byReference[$r['charge_reference']], $pending);
            $preview = ['rows' => $checks, 'cleared_invoices' => count(array_filter($checks, fn ($r) => $r['status'] === 'READY')),
                'cleared_amount_cents' => array_sum(array_column($checks, 'amount_cents'))];
            if ($preview['cleared_invoices'] !== count($pending)) {
                throw new RuntimeException('A selected row is no longer ready; the entire selected transaction was refused.');
            }
            // All blockers are checked before even creating staging/audit rows.
            $batch = app(HistoricalMigrationService::class)->createBatch($schoolId, 'term1_2026_invoices', 'approved-manifest.json', $operator->id,
                ['source_sha256' => $plan['source_sha256'], 'manifest_sha256' => $this->planner->fingerprint($plan), 'approval' => $evidence]);
            $batch->update(['approved_by' => $checker->id, 'approved_at' => now()]);
            $invoiceIds = [];
            Auth::login($operator);
            app()->forgetInstance(FinancialAccountResolver::class);
            foreach ($pending as $i => $row) {
                $validated = $preview['rows'][$i];
                $student = ! empty($row['student_id']) ? Student::where('school_id', $schoolId)->findOrFail($row['student_id']) : $this->createVerifiedStudent($schoolId, $row['new_student']);
                $amount = $validated['amount_cents'] / 100;
                $stage = MigrationStagingRow::create(['migration_batch_id' => $batch->id, 'school_id' => $schoolId,
                    'row_number' => $i + 1, 'accounting_date' => '2026-01-01', 'reference_number' => $row['charge_reference'],
                    'description' => 'Approved Term 1 2026 original fee assessment', 'amount' => $amount, 'direction' => 'debit',
                    'classification' => 'invoice', 'raw_data' => $row, 'matched_entity_type' => Student::class, 'matched_entity_id' => $student->id,
                    'status' => 'validated', 'idempotency_key' => hash('sha256', $schoolId.'|2026|Term 1|'.$row['charge_reference'])]);
                $invoice = Invoice::create(['school_id' => $schoolId, 'student_id' => $student->id, 'number' => $this->planner->number($row['charge_reference']),
                    'type' => 'fees', 'academic_year' => '2026', 'term' => 'Term 1', 'issued_at' => '2026-01-01', 'due_date' => '2026-01-31',
                    'total_amount' => $amount, 'balance' => $amount, 'status' => 'unpaid']);
                foreach ($row['lines'] as $line) {
                    $lineAmount = $this->planner->cents($line['amount']) / 100;
                    InvoiceItem::create(['invoice_id' => $invoice->id, 'fee_structure_id' => null, 'vote_code' => $line['vote_code'],
                        'description' => $line['description'], 'category' => $row['original_category'], 'quantity' => 1,
                        'unit_amount' => $lineAmount, 'line_total' => $lineAmount]);
                }
                $journal = app(AccountingService::class)->postInvoice($invoice);
                $expected = ['debit|'.$validated['debit_account_id'] => $validated['amount_cents']];
                foreach ($validated['credits_cents'] as $accountId => $cents) {
                    $expected['credit|'.$accountId] = $cents;
                }
                $actual = [];
                foreach ($journal->entries as $entry) {
                    $key = $entry->entry_type.'|'.$entry->account_id;
                    $actual[$key] = ($actual[$key] ?? 0) + $this->planner->cents($entry->amount);
                }
                ksort($expected);
                ksort($actual);
                if ($actual !== $expected || $journal->status !== 'posted' || $journal->transaction_date->toDateString() !== '2026-01-01') {
                    throw new RuntimeException('Authoritative journal does not match approved votes; transaction rolled back.');
                }
                $stage->update(['status' => 'posted', 'journal_batch_id' => $journal->id,
                    'parsed_data' => ['payload_hash' => $this->planner->fingerprint($row), 'source_sha256' => $plan['source_sha256'], 'invoice_id' => $invoice->id]]);
                AuditLog::create(['school_id' => $schoolId, 'user_id' => $operator->id, 'action' => 'term1_invoice_imported', 'module' => 'finance',
                    'model_type' => Invoice::class, 'model_id' => $invoice->id,
                    'new_values' => ['batch_id' => $batch->id, 'source_reference' => $row['charge_reference'], 'journal_id' => $journal->id,
                        'payload_hash' => $validated['payload_hash'], 'approval_reference' => $evidence['approval_reference']]]);
                $invoiceIds[] = $invoice->id;
            }
            $batch->update(['status' => 'posted', 'total_rows' => count($pending), 'valid_rows' => count($pending), 'posted_rows' => count($pending),
                'total_debit' => $preview['cleared_amount_cents'] / 100, 'total_credit' => $preview['cleared_amount_cents'] / 100, 'is_balanced' => true, 'posted_at' => now()]);

            return ['posted' => count($pending), 'already_posted' => count($already), 'batch_id' => $batch->id,
                'invoice_ids' => array_merge($already, $invoiceIds), 'debits_cents' => $preview['cleared_amount_cents'], 'credits_cents' => $preview['cleared_amount_cents']];
        });
    }

    private function createVerifiedStudent(int $schoolId, array $source): Student
    {
        $values = array_intersect_key($source, array_flip(['first_name', 'last_name', 'admission_number', 'is_boarding', 'grade', 'class_name']));
        Validator::make($values, ['first_name' => 'required|string|max:255', 'last_name' => 'required|string|max:255',
            'admission_number' => 'required|string|max:100', 'is_boarding' => 'required|boolean', 'grade' => 'nullable|string|max:50', 'class_name' => 'nullable|string|max:50'])->validate();
        if (Student::where('school_id', $schoolId)->where('admission_number', $values['admission_number'])->exists()) {
            throw new RuntimeException('Admission identity appeared during execution; no duplicate student created.');
        }
        // This path is entered only after the locked tenant-wide absence checks.
        return Student::create($values + ['school_id' => $schoolId, 'funding_status' => 'regular', 'status' => 'active']);
    }

    private function retryPostingMatches(Invoice $invoice, $journal, array $row): bool
    {
        $expectedVotes = [];
        $expectedAccounts = [];
        foreach ($row['lines'] as $line) {
            $cents = $this->planner->cents($line['amount']);
            $vote = $line['vote_code'];
            $accountId = (int) $line['revenue_account_id'];
            $expectedVotes[$vote] = ($expectedVotes[$vote] ?? 0) + $cents;
            $expectedAccounts['credit|'.$accountId] = ($expectedAccounts['credit|'.$accountId] ?? 0) + $cents;
        }
        $actualVotes = [];
        foreach ($invoice->items()->get() as $item) {
            $actualVotes[$item->vote_code] = ($actualVotes[$item->vote_code] ?? 0) + $this->planner->cents($item->line_total);
        }
        $actualAccounts = [];
        foreach ($journal->entries as $entry) {
            $key = $entry->entry_type.'|'.$entry->account_id;
            $actualAccounts[$key] = ($actualAccounts[$key] ?? 0) + $this->planner->cents($entry->amount);
        }
        $debitTotal = 0;
        foreach ($journal->entries as $entry) {
            if ($entry->entry_type === 'debit') {
                $debitTotal += $this->planner->cents($entry->amount);
            }
        }
        $expectedAccounts['debit|'.$journal->entries->firstWhere('entry_type', 'debit')?->account_id] = $debitTotal;
        ksort($expectedVotes);
        ksort($actualVotes);
        ksort($expectedAccounts);
        ksort($actualAccounts);

        return $expectedVotes === $actualVotes && $expectedAccounts === $actualAccounts
            && $debitTotal === $this->planner->cents($row['expected_amount']);
    }
}
