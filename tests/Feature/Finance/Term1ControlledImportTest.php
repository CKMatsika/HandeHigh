<?php

namespace Tests\Feature\Finance;

use App\Models\AccountingPeriod;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\MigrationBatch;
use App\Models\Student;
use App\Services\AccountingService;
use App\Services\Finance\Term1ControlledImportService;
use App\Services\Finance\Term1ImportPlanner;
use App\Services\Finance\Term1ReconciliationExportService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\Support\BeamRehearsal;
use Tests\TestCase;

class Term1ControlledImportTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $r = new BeamRehearsal;
        $f = $r->fixture();
        Role::findOrCreate('school-admin', 'web');
        $f['maker']->forceFill(['is_active' => true])->save();
        $f['checker']->forceFill(['is_active' => true])->save();
        $f['maker']->assignRole('school-admin');
        $f['checker']->assignRole('school-admin');
        AccountingPeriod::create(['school_id' => $f['school']->id, 'name' => 'Synthetic January', 'period_type' => 'monthly',
            'start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'status' => 'open']);
        $s = $r->student('Ordinary');
        $snapshot = app(Term1ReconciliationExportService::class)->snapshot($f['school']->id, '2026');
        $before = collect($snapshot['data']['students'])->firstWhere('id', $s->id);
        $row = ['charge_reference' => 'SYN-T1-001', 'student_id' => $s->id, 'student_before' => array_intersect_key($before, array_flip(['id', 'first_name', 'last_name', 'grade', 'class_name', 'is_boarding', 'funding_status'])),
            'expected_amount' => '100.00', 'source_amount_blank' => false, 'original_category' => 'tuition',
            'identity_verified' => true, 'identity_evidence' => 'Synthetic verified ID and class register',
            'charges_verified' => true, 'charge_evidence' => 'Synthetic original approved fee schedule',
            'lines' => [['vote_code' => 'TUITION', 'description' => 'Synthetic historical tuition', 'amount' => '100.00', 'revenue_account_id' => $f['revenue']->id, 'source_evidence' => 'Synthetic original vote allocation']]];
        $plan = ['format' => 'hande-term1-import-v1', 'school_id' => $f['school']->id, 'school_code' => $f['school']->code,
            'academic_year' => '2026', 'term' => 'Term 1', 'issued_at' => '2026-01-01', 'due_date' => '2026-01-31',
            'source_sha256' => str_repeat('a', 64), 'rows' => [$row], 'payments' => []];

        return [$r, $f, $s, $snapshot, $plan];
    }

    private function approval(array $f, array $snapshot, array $plan): array
    {
        $p = app(Term1ImportPlanner::class);

        return ['maker_id' => $f['maker']->id, 'checker_id' => $f['checker']->id, 'manifest_sha256' => $p->fingerprint($plan),
            'snapshot_sha256' => $p->fingerprint($snapshot), 'production_branch' => 'main', 'deployment_commit' => str_repeat('b', 40),
            'deployment_reference' => 'SYNTHETIC-CLOUD-DEPLOYMENT', 'database_driver' => 'sqlite', 'backup_restore_verified' => true,
            'backup_reference' => 'SYNTHETIC-RESTORED-BACKUP', 'backup_verified_at' => now()->toIso8601String(),
            'approval_reference' => 'SYNTHETIC-INDEPENDENT-APPROVAL', 'cleared_references' => array_column($plan['rows'], 'charge_reference')];
    }

    private function execute(array $f, array $snapshot, array $plan, ?array $approval = null): array
    {
        return app(Term1ControlledImportService::class)->execute($plan, $approval ?? $this->approval($f, $snapshot, $plan), $snapshot, $f['maker'], str_repeat('b', 40));
    }

    public function test_preview_is_pure_and_valid_ordinary_row_clears(): void
    {
        [,,, $snapshot, $plan] = $this->fixture();
        $queries = [];
        DB::listen(function (QueryExecuted $q) use (&$queries) { $queries[] = $q->sql; });
        $result = app(Term1ImportPlanner::class)->preview($plan, $snapshot);
        $this->assertSame([], $queries);
        $this->assertSame(1, $result['cleared_invoices'], json_encode([$result['rows'], $snapshot['data']['accounting_periods']]));
        $this->assertSame(10000, $result['cleared_amount_cents']);
    }

    public function test_artisan_default_mode_writes_private_dry_run_report_without_database_changes(): void
    {
        [,,, $snapshot, $plan] = $this->fixture();
        $directory = storage_path('app/private/term1-import');
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $tag = bin2hex(random_bytes(5));
        $manifestPath = $directory.DIRECTORY_SEPARATOR.'test-manifest-'.$tag.'.json';
        $snapshotPath = $directory.DIRECTORY_SEPARATOR.'test-snapshot-'.$tag.'.json';
        $reportName = 'test-report-'.$tag.'.json';
        file_put_contents($manifestPath, json_encode($plan, JSON_THROW_ON_ERROR));
        file_put_contents($snapshotPath, json_encode($snapshot, JSON_THROW_ON_ERROR));
        $before = [Invoice::count(), JournalBatch::count(), MigrationBatch::count()];
        try {
            $exit = Artisan::call('finance:term1-controlled-import', ['--manifest' => $manifestPath, '--snapshot' => $snapshotPath, '--report' => $reportName]);
            $report = json_decode(file_get_contents($directory.DIRECTORY_SEPARATOR.$reportName), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(0, $exit, Artisan::output());
            $this->assertSame('OFFLINE_DRY_RUN_NO_DATABASE_WRITES', $report['mode']);
            $this->assertSame($before, [Invoice::count(), JournalBatch::count(), MigrationBatch::count()]);
            $this->assertStringContainsString('storage/app/private/term1-import/', str_replace('\\', '/', $directory.DIRECTORY_SEPARATOR.$reportName));
        } finally {
            @unlink($manifestPath);
            @unlink($snapshotPath);
            @unlink($directory.DIRECTORY_SEPARATOR.$reportName);
        }
    }

    public function test_scholarship_blank_does_not_block_verified_ordinary_row(): void
    {
        [,,, $snapshot, $plan] = $this->fixture();
        $hold = $plan['rows'][0];
        $hold['charge_reference'] = 'SYN-SCHOLARSHIP';
        $hold['student_id'] = null;
        $hold['source_amount_blank'] = true;
        $hold['expected_amount'] = null;
        $hold['identity_verified'] = false;
        $hold['lines'] = [];
        $plan['rows'][] = $hold;
        $result = app(Term1ImportPlanner::class)->preview($plan, $snapshot);
        $this->assertSame(1, $result['cleared_invoices'], json_encode($result['rows']));
        $this->assertSame(1, $result['scholarship_holds']);
        $this->assertSame('HOLD', $result['rows'][1]['status']);
    }

    public function test_unmapped_header_wrong_currency_and_unsupported_votes_hold_without_bootstrap(): void
    {
        [,,, $snapshot, $plan] = $this->fixture();
        foreach (['missing', 'header', 'currency', 'line_sum'] as $case) {
            $copy = $snapshot;
            $manifest = $plan;
            if ($case === 'missing') { $copy['data']['account_mappings'] = []; }
            if ($case === 'header') { foreach ($copy['data']['accounts'] as &$a) { $a['is_postable'] = false; } unset($a); }
            if ($case === 'currency') { foreach ($copy['data']['accounts'] as &$a) { $a['currency'] = 'EUR'; } unset($a); }
            if ($case === 'line_sum') { $manifest['rows'][0]['lines'][0]['amount'] = '99.99'; }
            $this->assertSame(0, app(Term1ImportPlanner::class)->preview($manifest, $copy)['cleared_invoices'], $case);
        }
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_wrong_term_and_receipt_payloads_are_refused(): void
    {
        [,,, $snapshot, $plan] = $this->fixture();
        foreach (['term', 'payments'] as $field) {
            $bad = $plan;
            $bad[$field] = $field === 'term' ? 'Term 3' : [['amount' => '100', 'date' => null]];
            try { app(Term1ImportPlanner::class)->preview($bad, $snapshot); $this->fail('Unsafe scope accepted'); }
            catch (\InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
    }

    public function test_posting_reconciles_journal_and_preserves_current_student_and_other_terms(): void
    {
        [, $f, $student, $snapshot, $plan] = $this->fixture();
        $before = $student->fresh()->getAttributes();
        $other = Invoice::create(['school_id' => $f['school']->id, 'student_id' => $student->id, 'number' => 'SYN-T3', 'academic_year' => '2026', 'term' => 'Term 3', 'issued_at' => '2026-09-01', 'total_amount' => 999, 'balance' => 999, 'status' => 'unpaid']);
        $otherBefore = $other->fresh()->getAttributes();
        $result = $this->execute($f, $snapshot, $plan);
        $this->assertSame(1, $result['posted']);
        $this->assertSame(10000, $result['debits_cents']);
        $this->assertSame($result['debits_cents'], $result['credits_cents']);
        $this->assertSame($before, $student->fresh()->getAttributes());
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $invoice = Invoice::findOrFail($result['invoice_ids'][0]);
        $this->assertSame('2026-01-01', $invoice->issued_at->toDateString());
        $this->assertSame('2026-01-31', $invoice->due_date->toDateString());
        $this->assertSame('100.00', $invoice->balance);
        $this->assertSame('TUITION', $invoice->items->sole()->vote_code);
        $this->assertNull($invoice->items->sole()->fee_structure_id);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_identical_retry_returns_original_without_duplicate_invoices_or_journals(): void
    {
        [, $f,, $snapshot, $plan] = $this->fixture();
        $first = $this->execute($f, $snapshot, $plan);
        $retry = $this->execute($f, $snapshot, $plan);
        $this->assertSame(0, $retry['posted']);
        $this->assertSame(1, $retry['already_posted']);
        $this->assertSame($first['invoice_ids'], $retry['invoice_ids']);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('journal_batches', 1);
        $this->assertDatabaseCount('migration_batches', 1);
    }

    public function test_changed_retry_is_rejected_without_rewriting_original(): void
    {
        [, $f,, $snapshot, $plan] = $this->fixture();
        $this->execute($f, $snapshot, $plan);
        $plan['rows'][0]['expected_amount'] = '101.00';
        try { $this->execute($f, $snapshot, $plan); $this->fail('Changed payload accepted'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('prior import', $e->getMessage()); }
        $this->assertSame('100.00', Invoice::sole()->total_amount);
        $this->assertDatabaseCount('journal_batches', 1);
    }

    public function test_missing_backup_stale_snapshot_self_approval_and_driver_mismatch_refuse_before_writes(): void
    {
        [, $f,, $snapshot, $plan] = $this->fixture();
        foreach (['backup', 'stale', 'self', 'driver', 'commit'] as $case) {
            $copy = $snapshot;
            if ($case === 'stale') { $copy['exported_at'] = now()->subDays(2)->toIso8601String(); }
            $evidence = $this->approval($f, $copy, $plan);
            if ($case === 'backup') { $evidence['backup_restore_verified'] = false; }
            if ($case === 'self') { $evidence['checker_id'] = $f['maker']->id; }
            if ($case === 'driver') { $evidence['database_driver'] = 'pgsql'; }
            if ($case === 'commit') { $evidence['deployment_commit'] = str_repeat('c', 40); }
            try { $this->execute($f, $copy, $plan, $evidence); $this->fail('Missing guard accepted '.$case); }
            catch (\RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->assertDatabaseCount('migration_batches', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_selected_hold_rolls_back_entire_approved_selection_but_unselected_hold_does_not_block(): void
    {
        [, $f,, $snapshot, $plan] = $this->fixture();
        $hold = $plan['rows'][0];
        $hold['charge_reference'] = 'SYN-HELD';
        $hold['student_id'] = null;
        $hold['identity_verified'] = false;
        $plan['rows'][] = $hold;
        try { $this->execute($f, $snapshot, $plan); $this->fail('Selected hold accepted'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('no longer ready', $e->getMessage()); }
        $this->assertDatabaseCount('migration_batches', 0);
        $approval = $this->approval($f, $snapshot, $plan);
        $approval['cleared_references'] = ['SYN-T1-001'];
        $this->assertSame(1, $this->execute($f, $snapshot, $plan, $approval)['posted']);
    }

    public function test_closed_period_and_foreign_student_are_held(): void
    {
        [,,, $snapshot, $plan] = $this->fixture();
        $snapshot['data']['accounting_periods'][0]['status'] = 'closed';
        $plan['rows'][0]['student_id'] = 999999;
        $this->assertSame(0, app(Term1ImportPlanner::class)->preview($plan, $snapshot)['cleared_invoices']);
        $this->assertDatabaseCount('journal_batches', 0);
    }

    public function test_verified_new_student_can_be_created_once_without_fictional_guardians_or_current_updates(): void
    {
        [, $f,, $snapshot, $plan] = $this->fixture();
        $plan['rows'][0]['student_id'] = null;
        $plan['rows'][0]['new_student'] = ['confirmed_absent' => true, 'absence_evidence' => 'Synthetic complete roster and register check',
            'first_name' => 'SyntheticNew', 'last_name' => 'Verified', 'admission_number' => 'SYN-NEW-VERIFIED', 'is_boarding' => false];
        $this->assertSame(1, $this->execute($f, $snapshot, $plan)['posted']);
        $this->assertSame(1, $this->execute($f, $snapshot, $plan)['already_posted']);
        $this->assertDatabaseCount('students', 2);
        $this->assertDatabaseCount('guardians', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }
}
