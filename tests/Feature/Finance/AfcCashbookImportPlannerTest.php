<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Cashbook;
use App\Models\JournalBatch;
use App\Services\Finance\AfcCashbookImportService;
use App\Services\Finance\AfcCashbookImportPlanner;
use App\Services\Finance\Term1ReconciliationExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Support\BeamRehearsal;
use Tests\TestCase;

class AfcCashbookImportPlannerTest extends TestCase
{
    use RefreshDatabase;

    private function row(array $overrides = []): array
    {
        return array_replace(['transaction_date' => '2026-01-03', 'transaction_type' => 'expense',
            'source_reference' => 'SYN-AFC-001', 'description' => 'Synthetic AFC expenditure', 'amount' => '25.00',
            'category' => 'supplies', 'account_id' => null, 'offset_account_id' => null,
            'source_evidence' => 'Synthetic source workbook row 5'], $overrides);
    }

    public function test_valid_expense_is_ready_and_missing_date_or_bank_statement_only_movement_is_held(): void
    {
        $fixture = (new BeamRehearsal)->fixture();
        $expense = Account::create(['school_id' => $fixture['school']->id, 'code' => 'SYN-EXP', 'name' => 'Synthetic supplies expense',
            'type' => 'expense', 'category' => 'supply_expense', 'is_active' => true, 'is_postable' => true]);
        $base = $this->row(['account_id' => $fixture['bank']->id, 'offset_account_id' => $expense->id]);
        $missingDate = $this->row(['source_reference' => 'SYN-AFC-002', 'transaction_date' => null,
            'account_id' => $fixture['bank']->id, 'offset_account_id' => $expense->id]);
        $statementMovement = $this->row(['source_reference' => 'SYN-AFC-003', 'transaction_type' => 'bank_movement',
            'account_id' => $fixture['bank']->id]);
        $result = app(AfcCashbookImportPlanner::class)->preview(['scope' => 'afc_cashbook_2026_jan_apr',
            'school_id' => $fixture['school']->id, 'rows' => [$base, $missingDate, $statementMovement]], $fixture['school']->id);

        $this->assertSame(1, $result['ready_count']);
        $this->assertSame(2, $result['held_count']);
        $this->assertSame('READY', $result['rows'][0]['status']);
        $this->assertSame('HOLD', $result['rows'][1]['status']);
        $this->assertSame('HOLD', $result['rows'][2]['status']);
    }

    public function test_receipt_is_ready_only_when_stable_student_and_term_one_invoice_match(): void
    {
        $rehearsal = new BeamRehearsal;
        $fixture = $rehearsal->fixture();
        $student = $rehearsal->student('AFC-Receipt');
        $invoice = Invoice::create(['school_id' => $fixture['school']->id, 'student_id' => $student->id,
            'number' => 'SYN-T1-AFC-001', 'academic_year' => '2026', 'term' => 'Term 1', 'issued_at' => '2026-01-01',
            'total_amount' => 70, 'balance' => 70, 'status' => 'unpaid']);
        $row = $this->row(['transaction_type' => 'student_receipt', 'source_reference' => 'SYN-AFC-R-001',
            'account_id' => $fixture['bank']->id, 'student_admission_number' => $student->admission_number,
            'invoice_number' => $invoice->number, 'receipt_number' => 'SYN-REC-001', 'payment_method' => 'bank',
            'category' => 'fees', 'offset_account_id' => null]);
        $service = app(AfcCashbookImportPlanner::class);
        $ready = $service->preview(['scope' => 'afc_cashbook_2026_jan_apr', 'rows' => [$row]], $fixture['school']->id);
        $this->assertSame('READY', $ready['rows'][0]['status']);

        $row['student_admission_number'] = 'UNKNOWN';
        $held = $service->preview(['scope' => 'afc_cashbook_2026_jan_apr', 'rows' => [$row]], $fixture['school']->id);
        $this->assertSame('HOLD', $held['rows'][0]['status']);
    }

    public function test_out_of_scope_dates_and_duplicate_references_are_held(): void
    {
        $fixture = (new BeamRehearsal)->fixture();
        $expense = Account::create(['school_id' => $fixture['school']->id, 'code' => 'SYN-EXP', 'name' => 'Synthetic supplies expense',
            'type' => 'expense', 'category' => 'supply_expense', 'is_active' => true, 'is_postable' => true]);
        $row = $this->row(['account_id' => $fixture['bank']->id, 'offset_account_id' => $expense->id]);
        $outside = $this->row(['transaction_date' => '2026-05-01', 'account_id' => $fixture['bank']->id,
            'offset_account_id' => $expense->id]);
        $result = app(AfcCashbookImportPlanner::class)->preview(['scope' => 'afc_cashbook_2026_jan_apr', 'rows' => [$row, $row, $outside]], $fixture['school']->id);

        $this->assertSame(['READY', 'HOLD', 'HOLD'], array_column($result['rows'], 'status'));
        $this->assertSame(1, $result['ready_count']);
        $this->assertSame(2, $result['held_count']);
    }

    public function test_approved_synthetic_expense_posts_once_to_existing_accounting_engine(): void
    {
        $fixture = (new BeamRehearsal)->fixture();
        Role::findOrCreate('school-admin', 'web');
        $fixture['maker']->forceFill(['is_active' => true])->save();
        $fixture['checker']->forceFill(['is_active' => true])->save();
        $fixture['maker']->assignRole('school-admin');
        $fixture['checker']->assignRole('school-admin');
        $expense = Account::create(['school_id' => $fixture['school']->id, 'code' => 'SYN-AFC-EXP', 'name' => 'Synthetic supplies expense',
            'type' => 'expense', 'category' => 'supply_expense', 'is_active' => true, 'is_postable' => true]);
        AccountingPeriod::create(['school_id' => $fixture['school']->id, 'name' => 'Synthetic January 2026', 'period_type' => 'monthly',
            'start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'status' => 'open']);
        $row = $this->row(['account_id' => $fixture['bank']->id, 'offset_account_id' => $expense->id]);
        $manifest = ['scope' => 'afc_cashbook_2026_jan_apr', 'school_id' => $fixture['school']->id, 'rows' => [$row]];
        $snapshot = app(Term1ReconciliationExportService::class)->snapshot($fixture['school']->id, '2026');
        $evidence = ['manifest_sha256' => hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            'snapshot_sha256' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            'production_branch' => 'main', 'deployment_commit' => str_repeat('b', 40), 'deployment_reference' => 'SYNTHETIC-DEPLOYMENT',
            'database_driver' => 'sqlite', 'backup_reference' => 'SYNTHETIC-RESTORE-VERIFICATION',
            'backup_restore_verified' => true, 'backup_verified_at' => now()->toIso8601String(),
            'approval_reference' => 'SYNTHETIC-MANAGEMENT-APPROVAL', 'maker_id' => $fixture['maker']->id,
            'checker_id' => $fixture['checker']->id, 'cleared_references' => ['SYN-AFC-001']];

        $service = app(AfcCashbookImportService::class);
        $first = $service->execute($manifest, $snapshot, $evidence, $fixture['maker'], str_repeat('b', 40));
        $this->assertSame(1, $first['posted_or_previously_posted']);
        $this->assertSame(1, Cashbook::where('school_id', $fixture['school']->id)->where('reference_number', 'SYN-AFC-001')->count());
        $journal = JournalBatch::where('school_id', $fixture['school']->id)->where('source_type', 'cashbook')->firstOrFail();
        $this->assertSame(2500, (int) round((float) $journal->entries()->where('entry_type', 'debit')->sum('amount') * 100));
        $this->assertSame(2500, (int) round((float) $journal->entries()->where('entry_type', 'credit')->sum('amount') * 100));

        $retry = $service->execute($manifest, $snapshot, $evidence, $fixture['maker'], str_repeat('b', 40));
        $this->assertSame(1, $retry['posted_or_previously_posted']);
        $this->assertSame(1, Cashbook::where('school_id', $fixture['school']->id)->where('reference_number', 'SYN-AFC-001')->count());
        $this->assertSame(1, JournalBatch::where('school_id', $fixture['school']->id)->where('source_type', 'cashbook')->count());
    }

    public function test_approved_synthetic_receipt_creates_payment_allocation_and_no_duplicate_revenue(): void
    {
        $rehearsal = new BeamRehearsal;
        $fixture = $rehearsal->fixture();
        Role::findOrCreate('school-admin', 'web');
        $fixture['maker']->forceFill(['is_active' => true])->save();
        $fixture['checker']->forceFill(['is_active' => true])->save();
        $fixture['maker']->assignRole('school-admin');
        $fixture['checker']->assignRole('school-admin');
        AccountingPeriod::create(['school_id' => $fixture['school']->id, 'name' => 'Synthetic January 2026', 'period_type' => 'monthly',
            'start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'status' => 'open']);
        $student = $rehearsal->student('AFC-Payment');
        $invoice = Invoice::create(['school_id' => $fixture['school']->id, 'student_id' => $student->id,
            'number' => 'SYN-T1-AFC-PAY-001', 'academic_year' => '2026', 'term' => 'Term 1', 'issued_at' => '2026-01-01',
            'total_amount' => 70, 'balance' => 70, 'status' => 'unpaid']);
        $row = $this->row(['transaction_type' => 'student_receipt', 'source_reference' => 'SYN-AFC-RECEIPT-001',
            'receipt_number' => 'SYN-RECEIPT-001', 'student_admission_number' => $student->admission_number,
            'invoice_number' => $invoice->number, 'payment_method' => 'bank', 'category' => 'fees',
            'account_id' => $fixture['bank']->id, 'offset_account_id' => null]);
        $manifest = ['scope' => 'afc_cashbook_2026_jan_apr', 'school_id' => $fixture['school']->id, 'rows' => [$row]];
        $snapshot = app(Term1ReconciliationExportService::class)->snapshot($fixture['school']->id, '2026');
        $evidence = ['manifest_sha256' => hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            'snapshot_sha256' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            'production_branch' => 'main', 'deployment_commit' => str_repeat('b', 40), 'deployment_reference' => 'SYNTHETIC-DEPLOYMENT',
            'database_driver' => 'sqlite', 'backup_reference' => 'SYNTHETIC-RESTORE-VERIFICATION',
            'backup_restore_verified' => true, 'backup_verified_at' => now()->toIso8601String(),
            'approval_reference' => 'SYNTHETIC-MANAGEMENT-APPROVAL', 'maker_id' => $fixture['maker']->id,
            'checker_id' => $fixture['checker']->id, 'cleared_references' => ['SYN-AFC-RECEIPT-001']];

        $service = app(AfcCashbookImportService::class);
        $service->execute($manifest, $snapshot, $evidence, $fixture['maker'], str_repeat('b', 40));
        $payment = \App\Models\Payment::where('school_id', $fixture['school']->id)->where('mopse_receipt_number', 'SYN-RECEIPT-001')->firstOrFail();
        $this->assertSame('45.00', $invoice->fresh()->balance);
        $this->assertSame('25.00', $payment->amount);
        $this->assertSame('25.00', $payment->allocations()->sole()->amount);
        $this->assertSame(1, JournalBatch::where('school_id', $fixture['school']->id)->where('source_type', 'payment')->where('source_id', $payment->id)->count());
        $this->assertSame(1, Cashbook::where('school_id', $fixture['school']->id)->where('related_payment_id', $payment->id)->count());

        $service->execute($manifest, $snapshot, $evidence, $fixture['maker'], str_repeat('b', 40));
        $this->assertSame(1, \App\Models\Payment::where('school_id', $fixture['school']->id)->where('mopse_receipt_number', 'SYN-RECEIPT-001')->count());
        $this->assertSame(1, JournalBatch::where('school_id', $fixture['school']->id)->where('source_type', 'payment')->where('source_id', $payment->id)->count());
    }
}
