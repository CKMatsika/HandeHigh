<?php

namespace Tests\Feature\Finance;

use App\Exceptions\AccountingPeriodClosedException;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\KioskProduct;
use App\Models\KioskSale;
use App\Models\Project;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\Finance\AccountingPeriodService;
use App\Services\Kiosk\KioskService;
use App\Services\ProjectRevenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountingPeriodHardLockTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;
    protected User $bursarA;
    protected User $bursarB;
    protected Account $assetAccountA;
    protected Account $revenueAccountA;
    protected Account $cashAccountA;
    protected AccountingPeriodService $periodService;
    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'school-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bursar', 'guard_name' => 'web']);

        $this->schoolA = School::create([
            'name' => 'Hande High School A',
            'code' => 'HHS-A',
            'email' => 'finance@hande.ac.zw',
            'is_active' => true,
        ]);

        $this->schoolB = School::create([
            'name' => 'Mutare Secondary School B',
            'code' => 'MSS-B',
            'email' => 'finance@mutare.ac.zw',
            'is_active' => true,
        ]);

        $this->bursarA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Bursar Hande',
            'email' => 'bursar@hande.ac.zw',
        ]);
        $this->bursarA->assignRole('school-admin');

        $this->bursarB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'name' => 'Bursar Mutare',
            'email' => 'bursar@mutare.ac.zw',
        ]);
        $this->bursarB->assignRole('school-admin');

        // Accounts for School A
        $this->cashAccountA = Account::create([
            'school_id' => $this->schoolA->id,
            'code' => '1101',
            'name' => 'Main Operating Cash',
            'type' => 'asset',
            'category' => 'cash',
            'is_postable' => true,
            'is_active' => true,
        ]);

        $this->assetAccountA = Account::create([
            'school_id' => $this->schoolA->id,
            'code' => '1201',
            'name' => 'Student Fees Receivable',
            'type' => 'asset',
            'category' => 'receivable',
            'is_postable' => true,
            'is_active' => true,
        ]);

        $this->revenueAccountA = Account::create([
            'school_id' => $this->schoolA->id,
            'code' => '5101',
            'name' => 'Tuition Fee Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_postable' => true,
            'is_active' => true,
        ]);

        $this->periodService = app(AccountingPeriodService::class);
        $this->accountingService = app(AccountingService::class);
    }

    public function test_creates_accounting_period_successfully()
    {
        $period = $this->periodService->createPeriod([
            'name' => 'August 2026',
            'period_type' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ], $this->schoolA->id);

        $this->assertDatabaseHas('accounting_periods', [
            'id' => $period->id,
            'school_id' => $this->schoolA->id,
            'name' => 'August 2026',
            'status' => 'open',
        ]);
    }

    public function test_prevents_overlapping_periods_for_same_school()
    {
        $this->periodService->createPeriod([
            'name' => 'August 2026',
            'period_type' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ], $this->schoolA->id);

        $this->expectException(ValidationException::class);

        // Attempt overlapping period 15 Aug - 15 Sept
        $this->periodService->createPeriod([
            'name' => 'Mid Aug-Sept 2026',
            'period_type' => 'monthly',
            'start_date' => '2026-08-15',
            'end_date' => '2026-09-15',
        ], $this->schoolA->id);
    }

    public function test_allows_same_dates_across_different_school_tenants()
    {
        $periodA = $this->periodService->createPeriod([
            'name' => 'August 2026 - Hande',
            'period_type' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ], $this->schoolA->id);

        $periodB = $this->periodService->createPeriod([
            'name' => 'August 2026 - Mutare',
            'period_type' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ], $this->schoolB->id);

        $this->assertEquals($this->schoolA->id, $periodA->school_id);
        $this->assertEquals($this->schoolB->id, $periodB->school_id);
        $this->assertEquals('2026-08-01', $periodA->start_date->format('Y-m-d'));
        $this->assertEquals('2026-08-01', $periodB->start_date->format('Y-m-d'));
    }

    public function test_allows_posting_into_open_accounting_period()
    {
        $this->periodService->createPeriod([
            'name' => 'August 2026',
            'period_type' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ], $this->schoolA->id);

        $batch = $this->accountingService->createJournalBatch([
            'school_id' => $this->schoolA->id,
            'transaction_date' => '2026-08-15',
            'description' => 'Valid open period journal',
            'source_type' => 'manual',
            'status' => 'posted',
            'created_by' => $this->bursarA->id,
            'entries' => [
                ['account_id' => $this->assetAccountA->id, 'entry_type' => 'debit', 'amount' => 500.00],
                ['account_id' => $this->revenueAccountA->id, 'entry_type' => 'credit', 'amount' => 500.00],
            ],
        ]);

        $this->assertNotNull($batch);
        $this->assertEquals('posted', $batch->status);
    }

    public function test_blocks_manual_journal_posting_into_closed_accounting_period()
    {
        $period = $this->periodService->createPeriod([
            'name' => 'August 2026',
            'period_type' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ], $this->schoolA->id);

        // Close the period
        $this->periodService->closePeriod($period, $this->bursarA, 'Month-end closed');

        $this->expectException(AccountingPeriodClosedException::class);

        // Attempt posting transaction on 15 August 2026
        $this->accountingService->createJournalBatch([
            'school_id' => $this->schoolA->id,
            'transaction_date' => '2026-08-15',
            'description' => 'Illegal backdated journal into closed period',
            'source_type' => 'manual',
            'status' => 'posted',
            'created_by' => $this->bursarA->id,
            'entries' => [
                ['account_id' => $this->assetAccountA->id, 'entry_type' => 'debit', 'amount' => 300.00],
                ['account_id' => $this->revenueAccountA->id, 'entry_type' => 'credit', 'amount' => 300.00],
            ],
        ]);
    }

    public function test_closing_period_validates_trial_balance_and_records_closing_user()
    {
        $period = $this->periodService->createPeriod([
            'name' => 'September 2026',
            'period_type' => 'monthly',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ], $this->schoolA->id);

        // Create balanced entries
        $this->accountingService->createJournalBatch([
            'school_id' => $this->schoolA->id,
            'transaction_date' => '2026-09-10',
            'description' => 'Balanced September Transaction',
            'source_type' => 'manual',
            'status' => 'posted',
            'created_by' => $this->bursarA->id,
            'entries' => [
                ['account_id' => $this->cashAccountA->id, 'entry_type' => 'debit', 'amount' => 1200.00],
                ['account_id' => $this->revenueAccountA->id, 'entry_type' => 'credit', 'amount' => 1200.00],
            ],
        ]);

        $closedPeriod = $this->periodService->closePeriod($period, $this->bursarA, 'September audit finalized');

        $this->assertEquals('closed', $closedPeriod->status);
        $this->assertEquals($this->bursarA->id, $closedPeriod->closed_by);
        $this->assertNotNull($closedPeriod->closed_at);
        $this->assertEquals('September audit finalized', $closedPeriod->closing_notes);
    }

    public function test_reopening_closed_period_requires_reason_and_logs_audit_record()
    {
        $period = $this->periodService->createPeriod([
            'name' => 'October 2026',
            'period_type' => 'monthly',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ], $this->schoolA->id);

        $this->periodService->closePeriod($period, $this->bursarA, 'October closed');

        // Reopen with reason
        $reopenedPeriod = $this->periodService->reopenPeriod($period, $this->bursarA, 'Audit adjustment for science levy allocation');

        $this->assertEquals('open', $reopenedPeriod->status);
        $this->assertEquals($this->bursarA->id, $reopenedPeriod->reopened_by);
        $this->assertNotNull($reopenedPeriod->reopened_at);
        $this->assertEquals('Audit adjustment for science levy allocation', $reopenedPeriod->reopening_notes);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $this->schoolA->id,
            'action' => 'period_reopened',
        ]);
    }

    public function test_cross_tenant_period_isolation_blocks_foreign_closing_and_modifications()
    {
        $periodA = $this->periodService->createPeriod([
            'name' => 'August 2026 - Hande',
            'period_type' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ], $this->schoolA->id);

        // Bursar B from School B attempts to close School A's period via controller endpoint
        $response = $this->actingAs($this->bursarB)
            ->post(route('admin.accounting-periods.close', $periodA), [
                'closing_notes' => 'Malicious cross-tenant close attempt',
            ]);

        $response->assertStatus(403);
        $this->assertEquals('open', $periodA->fresh()->status);
    }

    public function test_rejected_transaction_attempt_into_closed_period_is_logged_in_audit_trail()
    {
        $period = $this->periodService->createPeriod([
            'name' => 'July 2026',
            'period_type' => 'monthly',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ], $this->schoolA->id);

        $this->periodService->closePeriod($period, $this->bursarA, 'July finalized');

        try {
            $this->periodService->assertPeriodOpen('2026-07-15', $this->schoolA->id, 'post manual journal');
        } catch (AccountingPeriodClosedException $e) {
            // Expected
        }

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $this->schoolA->id,
            'action' => 'transaction_rejected_closed_period',
        ]);
    }
}
