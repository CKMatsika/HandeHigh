<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatutoryRatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollComplianceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        if (! \Illuminate\Support\Facades\Schema::hasTable('academic_years')) {
            \Illuminate\Support\Facades\Schema::create('academic_years', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('school_classes')) {
            \Illuminate\Support\Facades\Schema::create('school_classes', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
        (new StatutoryRatesSeeder)->run();

        $this->schoolA = School::create(['name' => 'Highlands Academy', 'code' => 'HIGH']);
        $this->schoolB = School::create(['name' => 'Bulawayo College', 'code' => 'BULA']);

        $this->adminA = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->adminA->assignRole(Role::findByName('school-admin', 'web'));
    }

    public function test_dual_currency_payroll_processing_and_statutory_calculations(): void
    {
        $employee = Employee::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Farai',
            'last_name' => 'Moyo',
            'email' => 'farai.moyo@highlands.ac.zw',
            'phone' => '+263771234567',
            'employee_id' => 'EMP-001',
            'national_id' => '63-123456-A-70',
            'zimra_tin' => '2001987654',
            'nssa_number' => 'NSSA-887766',
            'nec_sector_code' => 'NEC-EDU',
            'position' => 'Senior Mathematics Teacher',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1985-05-15',
            'hire_date' => '2020-01-10',
            'salary' => 1000.00,
            'medical_aid_usd' => 100.00,
            'medical_aid_zwg' => 200.00,
        ]);

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.payrolls.process'), [
                'period_month' => 8,
                'period_year' => 2026,
                'processed_date' => '2026-08-28',
                'notes' => 'August 2026 Dual-Currency Staff Payroll',
                'employees' => [
                    [
                        'id' => $employee->id,
                        'basic_salary_usd' => 1000.00,
                        'housing_allowance_usd' => 200.00,
                        'bonus_usd' => 0.00,
                        'medical_aid_usd' => 100.00,
                        'basic_salary_zwg' => 5000.00,
                        'housing_allowance_zwg' => 1000.00,
                        'medical_aid_zwg' => 200.00,
                        'nec_sector_code' => 'NEC-EDU',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('payrolls', [
            'school_id' => $this->schoolA->id,
            'period_month' => 8,
            'period_year' => 2026,
            'status' => 'draft',
        ]);

        $payroll = Payroll::where('school_id', $this->schoolA->id)->first();
        $this->assertNotNull($payroll);

        // Verify USD totals
        $this->assertEquals(1200.00, $payroll->total_gross_usd);
        $this->assertGreaterThan(0, $payroll->total_paye_usd);
        $this->assertGreaterThan(0, $payroll->total_aids_levy_usd);
        $this->assertGreaterThan(0, $payroll->total_employer_nssa_usd);
        $this->assertGreaterThan(0, $payroll->total_employer_nec_usd);

        // Verify ZWG totals
        $this->assertEquals(6000.00, $payroll->total_gross_zwg);
        $this->assertGreaterThan(0, $payroll->total_paye_zwg);
        $this->assertGreaterThan(0, $payroll->total_employer_nssa_zwg);
    }

    public function test_tarms_csv_export_format_and_compliance(): void
    {
        $employee = Employee::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Tatenda',
            'last_name' => 'Chirandu',
            'email' => 'tatenda.c@highlands.ac.zw',
            'phone' => '+263772223344',
            'employee_id' => 'EMP-002',
            'national_id' => '08-765432-B-08',
            'zimra_tin' => '2009876543',
            'nssa_number' => 'NSSA-998877',
            'nec_sector_code' => 'NEC-EDU',
            'position' => 'Science Teacher',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1990-03-20',
            'hire_date' => '2021-02-01',
            'salary' => 1500.00,
        ]);

        $this->actingAs($this->adminA)
            ->post(route('admin.payrolls.process'), [
                'period_month' => 9,
                'period_year' => 2026,
                'processed_date' => '2026-09-25',
                'employees' => [
                    [
                        'id' => $employee->id,
                        'basic_salary_usd' => 1500.00,
                        'basic_salary_zwg' => 10000.00,
                        'nec_sector_code' => 'NEC-EDU',
                    ],
                ],
            ]);

        $payroll = Payroll::where('period_month', 9)->where('period_year', 2026)->first();

        $response = $this->actingAs($this->adminA)
            ->get(route('admin.payrolls.export-tarms', $payroll));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Stream content check
        ob_start();
        $response->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringContainsString('Employee TIN', $csvContent);
        $this->assertStringContainsString('National ID', $csvContent);
        $this->assertStringContainsString('2009876543', $csvContent);
        $this->assertStringContainsString('08-765432-B-08', $csvContent);
        $this->assertStringContainsString('Tatenda Chirandu', $csvContent);
        $this->assertStringContainsString('NEC-EDU', $csvContent);
    }

    public function test_dual_currency_printable_payslip_view(): void
    {
        $employee = Employee::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Chipo',
            'last_name' => 'Ndlovu',
            'email' => 'chipo.n@highlands.ac.zw',
            'phone' => '+263773334455',
            'employee_id' => 'EMP-003',
            'national_id' => '29-112233-C-29',
            'zimra_tin' => '2005544332',
            'nssa_number' => 'NSSA-554433',
            'nec_sector_code' => 'NEC-EDU',
            'position' => 'Head of Languages',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1988-08-10',
            'hire_date' => '2019-05-01',
            'salary' => 1200.00,
        ]);

        $this->actingAs($this->adminA)
            ->post(route('admin.payrolls.process'), [
                'period_month' => 10,
                'period_year' => 2026,
                'processed_date' => '2026-10-28',
                'employees' => [
                    [
                        'id' => $employee->id,
                        'basic_salary_usd' => 1200.00,
                        'basic_salary_zwg' => 8000.00,
                    ],
                ],
            ]);

        $payroll = Payroll::where('period_month', 10)->where('period_year', 2026)->first();

        $response = $this->actingAs($this->adminA)
            ->get(route('admin.payrolls.payslip', [$payroll, $employee]));

        $response->assertOk();
        $response->assertSee('Chipo Ndlovu');
        $response->assertSee('2005544332');
        $response->assertSee('29-112233-C-29');
        $response->assertSee('NSSA-554433');
        $response->assertSee('NEC-EDU');
        $response->assertSee('Employer Statutory Contributions');
    }

    public function test_tenant_isolation_prevents_foreign_school_access(): void
    {
        $adminB = User::factory()->create(['school_id' => $this->schoolB->id]);
        $adminB->assignRole(Role::findByName('school-admin', 'web'));

        $employeeA = Employee::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Farai',
            'last_name' => 'Moyo',
            'email' => 'farai.moyo2@highlands.ac.zw',
            'phone' => '+263771234568',
            'employee_id' => 'EMP-004',
            'position' => 'Teacher',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1985-05-15',
            'hire_date' => '2020-01-10',
            'salary' => 1000.00,
        ]);

        $this->actingAs($this->adminA)
            ->post(route('admin.payrolls.process'), [
                'period_month' => 11,
                'period_year' => 2026,
                'processed_date' => '2026-11-28',
                'employees' => [
                    [
                        'id' => $employeeA->id,
                        'basic_salary_usd' => 1000.00,
                    ],
                ],
            ]);

        $payrollA = Payroll::where('period_month', 11)->first();

        // Admin B cannot view School A's payroll (tenant scoped binding returns 404 / 403)
        $this->actingAs($adminB)
            ->get(route('admin.payrolls.show', $payrollA))
            ->assertNotFound();

        // Admin B cannot export School A's TaRMS CSV
        $this->actingAs($adminB)
            ->get(route('admin.payrolls.export-tarms', $payrollA))
            ->assertNotFound();

        // Admin B cannot view School A employee payslip
        $this->actingAs($adminB)
            ->get(route('admin.payrolls.payslip', [$payrollA, $employeeA]))
            ->assertNotFound();
    }

    public function test_employee_creation_and_update_with_statutory_compliance_fields(): void
    {
        $department = \App\Models\Department::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Humanities',
        ]);

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.employees.store'), [
                'first_name' => 'Tinashe',
                'last_name' => 'Gumbo',
                'email' => 'tinashe.g@highlands.ac.zw',
                'phone' => '+263774445566',
                'employee_id' => 'EMP-005',
                'department_id' => $department->id,
                'position' => 'History Teacher',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'date_of_birth' => '1992-07-22',
                'hire_date' => '2023-01-15',
                'salary' => 1200.00,
                'national_id' => '63-998877-K-63',
                'zimra_tin' => '2007766554',
                'nssa_number' => 'NSSA-776655',
                'nec_sector_code' => 'NEC-EDU',
                'medical_aid_usd' => 80.00,
                'trade_union_member' => 1,
                'trade_union_rate' => 1.5,
            ]);

        $response->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('employees', [
            'school_id' => $this->schoolA->id,
            'employee_id' => 'EMP-005',
            'national_id' => '63-998877-K-63',
            'zimra_tin' => '2007766554',
            'nssa_number' => 'NSSA-776655',
            'nec_sector_code' => 'NEC-EDU',
            'medical_aid_usd' => 80.00,
            'trade_union_member' => 1,
            'trade_union_rate' => 1.5,
        ]);
    }

    public function test_payroll_approval_locks_run_accrues_leave_and_autoposts_to_gl(): void
    {
        $employee = Employee::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Kudakwashe',
            'last_name' => 'Musona',
            'email' => 'kuda.m@highlands.ac.zw',
            'phone' => '+263775556677',
            'employee_id' => 'EMP-006',
            'national_id' => '63-554433-Z-63',
            'zimra_tin' => '2004433221',
            'nssa_number' => 'NSSA-443322',
            'nec_sector_code' => 'NEC-EDU',
            'position' => 'Senior Science Teacher',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1987-11-12',
            'hire_date' => '2022-03-01',
            'salary' => 1000.00,
            'leave_days_accrued' => 10.0,
            'leave_balance' => 10.0,
        ]);

        $this->actingAs($this->adminA)
            ->post(route('admin.payrolls.process'), [
                'period_month' => 12,
                'period_year' => 2026,
                'processed_date' => '2026-12-20',
                'employees' => [
                    [
                        'id' => $employee->id,
                        'basic_salary_usd' => 1000.00,
                        'housing_allowance_usd' => 200.00,
                        'basic_salary_zwg' => 5000.00,
                        'nec_sector_code' => 'NEC-EDU',
                    ],
                ],
            ]);

        $payroll = Payroll::where('period_month', 12)->where('period_year', 2026)->first();
        $this->assertFalse((bool)$payroll->is_locked);
        $this->assertNull($payroll->journal_batch_id);

        // Approve Payroll Run
        $approveResponse = $this->actingAs($this->adminA)
            ->post(route('admin.payrolls.approve', $payroll));

        $approveResponse->assertRedirect(route('admin.payrolls.show', $payroll));
        $payroll->refresh();

        // 1. Verify Immutability Lock
        $this->assertTrue((bool)$payroll->is_locked);
        $this->assertEquals('processed', $payroll->status);
        $this->assertNotNull($payroll->locked_at);

        // 2. Verify Statutory Leave Accrual (+2.5 days per month)
        $employee->refresh();
        $this->assertEquals(12.5, (float)$employee->leave_days_accrued);
        $this->assertEquals(12.5, (float)$employee->leave_balance);

        // 3. Verify General Ledger Auto-Posting
        $this->assertNotNull($payroll->journal_batch_id);
        $journalBatch = $payroll->journalBatch;
        $this->assertNotNull($journalBatch);
        $this->assertEquals('posted', $journalBatch->status);
        $this->assertEquals('payroll', $journalBatch->source_type);

        // Verify that Double-Entry is strictly balanced
        $totalDebits = $journalBatch->entries()->where('entry_type', 'debit')->sum('amount');
        $totalCredits = $journalBatch->entries()->where('entry_type', 'credit')->sum('amount');
        $this->assertEquals(round($totalDebits, 2), round($totalCredits, 2));
    }

    public function test_locked_payroll_cannot_be_deleted(): void
    {
        $employee = Employee::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Nyasha',
            'last_name' => 'Zowa',
            'email' => 'nyasha.z@highlands.ac.zw',
            'phone' => '+263776667788',
            'employee_id' => 'EMP-007',
            'position' => 'Teacher',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1991-04-10',
            'hire_date' => '2023-05-01',
            'salary' => 800.00,
        ]);

        $payroll = Payroll::create([
            'school_id' => $this->schoolA->id,
            'period_month' => 1,
            'period_year' => 2027,
            'processed_date' => '2027-01-25',
            'status' => 'processed',
            'is_locked' => true,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($this->adminA)
            ->delete(route('admin.payrolls.destroy', $payroll));

        $response->assertRedirect();
        $this->assertDatabaseHas('payrolls', ['id' => $payroll->id]);
    }

    public function test_compliance_reports_and_bulk_payslips(): void
    {
        $employee = Employee::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Blessing',
            'last_name' => 'Sibanda',
            'email' => 'blessing.s@highlands.ac.zw',
            'phone' => '+263777778899',
            'employee_id' => 'EMP-008',
            'national_id' => '63-112244-M-63',
            'zimra_tin' => '2001122334',
            'nssa_number' => 'NSSA-112233',
            'nec_sector_code' => 'NEC-EDU',
            'position' => 'Teacher',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1989-09-09',
            'hire_date' => '2021-08-01',
            'salary' => 1100.00,
        ]);

        $this->actingAs($this->adminA)
            ->post(route('admin.payrolls.process'), [
                'period_month' => 2,
                'period_year' => 2027,
                'processed_date' => '2027-02-25',
                'employees' => [
                    [
                        'id' => $employee->id,
                        'basic_salary_usd' => 1100.00,
                        'basic_salary_zwg' => 6000.00,
                        'nec_sector_code' => 'NEC-EDU',
                    ],
                ],
            ]);

        $payroll = Payroll::where('period_month', 2)->where('period_year', 2027)->first();

        // 1. Test Bulk Payslips HTML
        $this->actingAs($this->adminA)
            ->get(route('admin.payrolls.bulk-payslips', $payroll))
            ->assertOk()
            ->assertSee('Blessing Sibanda')
            ->assertSee('2001122334');

        // 2. Test Statutory Reports View
        $this->actingAs($this->adminA)
            ->get(route('admin.payrolls.statutory-report', [$payroll, 'type' => 'nssa-p4']))
            ->assertOk()
            ->assertSee('NSSA Social Security Scheme')
            ->assertSee('NSSA-112233');

        // 3. Test NSSA CSV Export
        $nssaResponse = $this->actingAs($this->adminA)
            ->get(route('admin.payrolls.export-statutory', [$payroll, 'nssa']));
        $nssaResponse->assertOk();
        $nssaResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 4. Test NEC CSV Export
        $necResponse = $this->actingAs($this->adminA)
            ->get(route('admin.payrolls.export-statutory', [$payroll, 'nec']));
        $necResponse->assertOk();
        $necResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 5. Test Master Summary CSV Export
        $summaryResponse = $this->actingAs($this->adminA)
            ->get(route('admin.payrolls.export-statutory', [$payroll, 'summary']));
        $summaryResponse->assertOk();
        $summaryResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}

