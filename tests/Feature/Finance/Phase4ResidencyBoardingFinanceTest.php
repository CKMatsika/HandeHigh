<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\Bed;
use App\Models\BedAssignment;
use App\Models\Department;
use App\Models\Dormitory;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Hostel;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\KioskProduct;
use App\Models\KioskSale;
use App\Models\Project;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\Finance\FeeRevenueAccountResolver;
use App\Services\Finance\FinanceReportingService;
use App\Services\Finance\ProjectRevenueService;
use App\Services\Kiosk\KioskService;
use App\Services\Residency\BedAllocationService;
use App\Services\Residency\BoardingStaffService;
use App\Services\Residency\StudentResidencyService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class Phase4ResidencyBoardingFinanceTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        (new \Database\Seeders\RoleSeeder)->run();
        (new \Database\Seeders\RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'school_id' => $this->school->id,
        ]);
        $this->adminUser->assignRole('school-admin');

        $this->seed(ChartOfAccountsSeeder::class);
    }

    protected function createStudent(array $attributes = []): Student
    {
        static $counter = 1000;
        $counter++;

        return Student::create(array_merge([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-' . $counter,
            'first_name' => 'Student',
            'last_name' => 'Number' . $counter,
            'gender' => 'female',
            'grade' => 'Form 1',
            'class_name' => '1A',
            'status' => 'active',
            'is_boarding' => false,
            'enrollment_date' => '2026-01-10',
        ], $attributes));
    }

    /** @test */
    public function day_scholar_cannot_be_allocated_a_bed()
    {
        $student = $this->createStudent([
            'gender' => 'female',
            'is_boarding' => false,
        ]);

        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => '1',
            'grade' => 'Form 1',
            'class_name' => '1A',
            'student_type' => 'day',
            'is_boarding' => false,
            'enrollment_date' => '2026-01-10',
            'status' => 'active',
        ]);

        $dorm = Dormitory::create([
            'school_id' => $this->school->id,
            'name' => 'Kaguvi Girls Wing',
            'gender' => 'female',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $bed = Bed::create([
            'dormitory_id' => $dorm->id,
            'bed_number' => 'Bed-01',
            'is_available' => true,
        ]);

        $allocationService = app(BedAllocationService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('registered as a Day Scholar');

        $allocationService->allocateBed($student, $bed, '2026', '1');
    }

    /** @test */
    public function residency_transition_enables_bed_allocation_and_releasing_transitions_safely()
    {
        $student = $this->createStudent([
            'gender' => 'female',
            'is_boarding' => false,
        ]);

        $dorm = Dormitory::create([
            'school_id' => $this->school->id,
            'name' => 'Victoria Hall',
            'gender' => 'female',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $bed = Bed::create([
            'dormitory_id' => $dorm->id,
            'bed_number' => 'Bed-01',
            'is_available' => true,
        ]);

        $residencyService = app(StudentResidencyService::class);
        $allocationService = app(BedAllocationService::class);

        // 1. Transition Day -> Boarding
        $enrollment = $residencyService->transitionResidency($student, 'boarding', '2026', '1', 'Parent requested boarding facility');
        $this->assertTrue($enrollment->isBoarder());
        $this->assertTrue($student->fresh()->is_boarding);

        // 2. Allocate Bed
        $assignment = $allocationService->allocateBed($student, $bed, '2026', '1');
        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->is_current);
        $this->assertEquals($bed->id, $student->fresh()->currentBedAssignment->bed_id);

        // 3. Transition Boarding -> Day (Auto-releases active bed assignment)
        $residencyService->transitionResidency($student, 'day', '2026', '1', 'Switched to day scholar');
        $this->assertFalse($student->fresh()->is_boarding);

        $assignment->refresh();
        $this->assertFalse($assignment->is_current);
        $this->assertNotNull($assignment->released_date);
        $this->assertNull($student->fresh()->currentBedAssignment);
    }

    /** @test */
    public function gender_validation_prevents_gender_mismatched_bed_allocations()
    {
        $maleStudent = $this->createStudent([
            'gender' => 'male',
            'is_boarding' => true,
        ]);

        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $maleStudent->id,
            'academic_year' => '2026',
            'term' => '1',
            'grade' => 'Form 1',
            'student_type' => 'boarding',
            'is_boarding' => true,
            'enrollment_date' => '2026-01-10',
            'status' => 'active',
        ]);

        $femaleDorm = Dormitory::create([
            'school_id' => $this->school->id,
            'name' => 'Girls Hostel West',
            'gender' => 'female',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $bed = Bed::create([
            'dormitory_id' => $femaleDorm->id,
            'bed_number' => 'Bed-01',
            'is_available' => true,
        ]);

        $allocationService = app(BedAllocationService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gender mismatch');

        $allocationService->allocateBed($maleStudent, $bed, '2026', '1');
    }

    /** @test */
    public function matrons_and_boarding_masters_are_strictly_validated_from_non_teaching_staff()
    {
        $nonTeachingDept = Department::create([
            'school_id' => $this->school->id,
            'name' => 'Boarding Services',
            'code' => 'BS',
            'is_active' => true,
        ]);

        $matron = Employee::create([
            'school_id' => $this->school->id,
            'employee_id' => 'EMP-MAT-001',
            'first_name' => 'Grace',
            'last_name' => 'Moyo',
            'email' => 'grace.moyo@handehigh.ac.zw',
            'position' => 'Senior Matron',
            'department_id' => $nonTeachingDept->id,
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1985-05-15',
            'national_id' => '63-123456-X-42',
            'phone' => '+263771111111',
            'hire_date' => '2024-01-01',
            'salary' => 800,
        ]);

        $girlsHostel = Hostel::create([
            'school_id' => $this->school->id,
            'name' => 'Queen Elizabeth Hostel',
            'gender' => 'female',
            'is_active' => true,
        ]);

        $staffService = app(BoardingStaffService::class);

        // Valid assignment of Matron to Girls' Hostel
        $staffService->assignHostelSupervisor($girlsHostel, $matron);
        $this->assertEquals($matron->id, $girlsHostel->fresh()->supervisor_id);

        // Invalid: Teacher assigned as residential supervisor
        $academicDept = Department::create([
            'school_id' => $this->school->id,
            'name' => 'Academics',
            'code' => 'ACAD',
            'is_active' => true,
        ]);

        $teacher = Employee::create([
            'school_id' => $this->school->id,
            'employee_id' => 'EMP-TCH-002',
            'first_name' => 'John',
            'last_name' => 'Ndlovu',
            'email' => 'john.ndlovu@handehigh.ac.zw',
            'position' => 'Mathematics Teacher',
            'department_id' => $academicDept->id,
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1988-08-20',
            'national_id' => '63-999999-Y-42',
            'phone' => '+263772222222',
            'hire_date' => '2024-01-01',
            'salary' => 900,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('categorized as teaching staff');

        $staffService->assignHostelSupervisor($girlsHostel, $teacher);
    }

    /** @test */
    public function itemized_invoice_revenue_posting_creates_balanced_multi_line_gl_entries()
    {
        $student = $this->createStudent([
            'is_boarding' => true,
        ]);

        $tuitionFee = FeeStructure::create([
            'school_id' => $this->school->id,
            'code' => 'FEE-T1-TUI',
            'academic_year' => '2026',
            'term' => '1',
            'label' => 'Term 1 Tuition',
            'category' => 'tuition',
            'service_type' => 'tuition',
            'amount' => 450.00,
            'is_active' => true,
        ]);

        $boardingFee = FeeStructure::create([
            'school_id' => $this->school->id,
            'code' => 'FEE-T1-BDG',
            'academic_year' => '2026',
            'term' => '1',
            'label' => 'Term 1 Boarding & Catering',
            'category' => 'boarding',
            'service_type' => 'boarding',
            'amount' => 600.00,
            'is_active' => true,
        ]);

        $levyFee = FeeStructure::create([
            'school_id' => $this->school->id,
            'code' => 'FEE-T1-LEV',
            'academic_year' => '2026',
            'term' => '1',
            'label' => 'Building Development Levy',
            'category' => 'levy',
            'service_type' => 'levy',
            'amount' => 50.00,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'number' => 'INV-2026-000001',
            'type' => 'fees',
            'academic_year' => '2026',
            'term' => '1',
            'total_amount' => 1100.00,
            'balance' => 1100.00,
            'issued_at' => '2026-01-15',
            'status' => 'unpaid',
        ]);

        InvoiceItem::create([
            'school_id' => $this->school->id,
            'invoice_id' => $invoice->id,
            'fee_structure_id' => $tuitionFee->id,
            'description' => 'Term 1 Tuition',
            'category' => 'tuition',
            'quantity' => 1,
            'unit_amount' => 450.00,
            'line_total' => 450.00,
        ]);

        InvoiceItem::create([
            'school_id' => $this->school->id,
            'invoice_id' => $invoice->id,
            'fee_structure_id' => $boardingFee->id,
            'description' => 'Term 1 Boarding & Catering',
            'category' => 'boarding',
            'quantity' => 1,
            'unit_amount' => 600.00,
            'line_total' => 600.00,
        ]);

        InvoiceItem::create([
            'school_id' => $this->school->id,
            'invoice_id' => $invoice->id,
            'fee_structure_id' => $levyFee->id,
            'description' => 'Building Development Levy',
            'category' => 'levy',
            'quantity' => 1,
            'unit_amount' => 50.00,
            'line_total' => 50.00,
        ]);

        $accountingService = app(AccountingService::class);
        $batch = $accountingService->postInvoice($invoice);

        $this->assertNotNull($batch);
        $this->assertEquals(1100.00, $batch->debit_total);
        $this->assertEquals(1100.00, $batch->credit_total);

        // Verify entries
        $entries = $batch->entries()->with('account')->get();
        $debitEntry = $entries->firstWhere('entry_type', 'debit');
        $this->assertEquals('1201', $debitEntry->account->code);
        $this->assertEquals(1100.00, $debitEntry->amount);

        $tuitionEntry = $entries->where('entry_type', 'credit')->first(fn ($e) => $e->account->code === '5100');
        $this->assertNotNull($tuitionEntry);
        $this->assertEquals(450.00, $tuitionEntry->amount);

        $boardingEntry = $entries->where('entry_type', 'credit')->first(fn ($e) => $e->account->code === '5200');
        $this->assertNotNull($boardingEntry);
        $this->assertEquals(600.00, $boardingEntry->amount);

        $levyEntry = $entries->where('entry_type', 'credit')->first(fn ($e) => $e->account->code === '5700');
        $this->assertNotNull($levyEntry);
        $this->assertEquals(50.00, $levyEntry->amount);
    }

    /** @test */
    public function kiosk_sale_reduces_stock_and_posts_to_canteen_gl_revenue()
    {
        $kioskProduct = KioskProduct::create([
            'school_id' => $this->school->id,
            'code' => 'SNK-01',
            'name' => 'Fresh Bread Loaf',
            'category' => 'snacks',
            'unit_price' => 1.20,
            'cost_price' => 0.80,
            'stock_quantity' => 20,
            'track_stock' => true,
            'is_active' => true,
        ]);

        $kioskService = app(KioskService::class);

        $sale = $kioskService->recordSale([
            'school_id' => $this->school->id,
            'payment_method' => 'cash',
            'customer_name' => 'Student John',
            'items' => [
                [
                    'kiosk_product_id' => $kioskProduct->id,
                    'quantity' => 3,
                    'unit_price' => 1.20,
                ],
            ],
        ]);

        $this->assertNotNull($sale);
        $this->assertEquals(3.60, $sale->grand_total);
        $this->assertEquals(17, $kioskProduct->fresh()->stock_quantity);

        // Verify GL Batch
        $journalBatch = JournalBatch::where('source_type', 'receipt')->where('reference_number', $sale->receipt_number)->first();
        $this->assertNotNull($journalBatch);
        $this->assertEquals(3.60, $journalBatch->debit_total);
        $this->assertEquals(3.60, $journalBatch->credit_total);

        $canteenCredit = $journalBatch->entries()->whereHas('account', fn ($q) => $q->where('code', '5803'))->first();
        $this->assertNotNull($canteenCredit);
        $this->assertEquals(3.60, $canteenCredit->amount);
    }

    /** @test */
    public function commercial_project_revenue_posts_with_project_tag_to_gl()
    {
        $project = Project::create([
            'school_id' => $this->school->id,
            'code' => 'PRJ-AGR-01',
            'name' => 'Layer Poultry Commercial Project',
            'description' => 'Commercial egg production project',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'budget_amount' => 5000.00,
            'project_type' => 'agriculture',
            'status' => 'active',
        ]);

        $projectRevenueService = app(ProjectRevenueService::class);
        $receipt = $projectRevenueService->recordProjectIncome($project, [
            'amount' => 250.00,
            'payment_method' => 'cash',
            'transaction_date' => '2026-02-10',
            'description' => 'Sale of 50 crates of eggs to local supermarket',
            'customer_name' => 'OK Supermarket',
        ]);

        $this->assertNotNull($receipt);
        $this->assertEquals(250.00, $receipt->grand_total);

        $batch = JournalBatch::where('source_type', 'receipt')->where('source_id', $receipt->id)->first();
        $this->assertNotNull($batch);
        $this->assertEquals(250.00, $batch->debit_total);
        $this->assertEquals(250.00, $batch->credit_total);

        $projectEntry = $batch->entries()->where('project_id', $project->id)->first();
        $this->assertNotNull($projectEntry);
    }

    /** @test */
    public function revenue_summary_and_reconciliation_accurately_aggregate_all_domains()
    {
        // 1. Post Invoice (Tuition 400 + Boarding 500 = 900)
        $student = $this->createStudent(['is_boarding' => true]);
        $inv = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'number' => 'INV-2026-TEST',
            'type' => 'fees',
            'academic_year' => '2026',
            'term' => '1',
            'total_amount' => 900.00,
            'balance' => 900.00,
            'issued_at' => '2026-01-20',
            'status' => 'unpaid',
        ]);

        InvoiceItem::create([
            'school_id' => $this->school->id,
            'invoice_id' => $inv->id,
            'description' => 'Tuition',
            'category' => 'tuition',
            'quantity' => 1,
            'unit_amount' => 400.00,
            'line_total' => 400.00,
        ]);
        InvoiceItem::create([
            'school_id' => $this->school->id,
            'invoice_id' => $inv->id,
            'description' => 'Boarding',
            'category' => 'boarding',
            'quantity' => 1,
            'unit_amount' => 500.00,
            'line_total' => 500.00,
        ]);

        app(AccountingService::class)->postInvoice($inv);

        // 2. Run Reporting Service
        $reportingService = app(FinanceReportingService::class);
        $summary = $reportingService->getSchoolRevenueSummary($this->school, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->assertEquals(900.00, $summary['total_revenue']);
        $this->assertEquals(400.00, $summary['categories']['tuition_revenue']);
        $this->assertEquals(500.00, $summary['categories']['boarding_revenue']);

        $reconciliation = $reportingService->getAccountingReconciliation($this->school, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->assertTrue($reconciliation['is_fully_reconciled']);
        $this->assertEquals(0, $reconciliation['invoices']['discrepancy']);
    }
}
