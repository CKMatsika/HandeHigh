<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Services\Finance\HandeFeeVotesService;
use Tests\TestCase;

class BulkInvoicingPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected Account $receivableAccount;
    protected Account $tuitionRevenueAccount;
    protected Account $boardingRevenueAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS001',
            'timezone' => 'Africa/Harare',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bursar User',
            'email' => 'bursar@handehigh.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('super-admin');

        // Setup base chart of accounts for invoice posting
        $this->receivableAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1201',
            'name' => 'Student Fees Receivable',
            'type' => 'asset',
            'category' => 'receivable',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->tuitionRevenueAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '4001',
            'name' => 'Tuition Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->boardingRevenueAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '4002',
            'name' => 'Boarding Revenue',
            'type' => 'revenue',
            'category' => 'other_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);
    }

    public function test_bulk_invoicing_preview_filters_by_grade_class_and_boarding(): void
    {
        // 1. Fee structures for Form 2, 2026 Term 1
        FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'category' => 'tuition',
            'code' => 'F2-TUI',
            'label' => 'Form 2 Tuition',
            'amount' => 350.00,
            'revenue_account_id' => $this->tuitionRevenueAccount->id,
        ]);

        FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'category' => 'boarding',
            'code' => 'F2-BRD',
            'label' => 'Form 2 Boarding & Catering',
            'amount' => 450.00,
            'revenue_account_id' => $this->boardingRevenueAccount->id,
        ]);

        // 2. Class: Form 2 North
        $classF2North = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'North',
            'grade' => 'Form 2',
            'academic_year' => '2026',
        ]);

        // Student A: Form 2 North Boarder
        $boarder = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tadiwa',
            'last_name' => 'Boarder',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'is_boarding' => true,
            'status' => 'active',
        ]);
        $enrBoarder = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $boarder->id,
            'class_id' => $classF2North->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'is_boarding' => true,
            'student_type' => 'boarding',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Student B: Form 2 North Day Scholar
        $dayScholar = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Farai',
            'last_name' => 'DayScholar',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'is_boarding' => false,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);
        $enrDay = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $dayScholar->id,
            'class_id' => $classF2North->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'is_boarding' => false,
            'student_type' => 'day',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Student C: Form 1 North Boarder (different grade)
        $form1Boarder = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Kuda',
            'last_name' => 'FormOne',
            'grade' => 'Form 1',
            'class_name' => 'North',
            'is_boarding' => true,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $form1Boarder->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'North',
            'is_boarding' => true,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Act: Filter for Form 2, North, Boarding only
        $response = $this->actingAs($this->adminUser)->get(route('admin.invoices.bulk.create', [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'residency' => 'boarding',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Tadiwa Boarder');
        // Total should be Tuition ($350) + Boarding ($450) = $800.00
        $response->assertSee('800.00');
        $response->assertSee('Boarder');

        // Form 2 Day Scholar must NOT be shown when filtering for boarders
        $response->assertDontSee('Farai DayScholar');
        // Form 1 student must NOT be shown when filtering for Form 2
        $response->assertDontSee('Kuda FormOne');
    }

    public function test_bulk_invoicing_preview_filters_day_scholars_without_boarding_fees(): void
    {
        FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'category' => 'tuition',
            'code' => 'F2-TUI',
            'label' => 'Form 2 Tuition',
            'amount' => 350.00,
        ]);

        FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'category' => 'boarding',
            'code' => 'F2-BRD',
            'label' => 'Form 2 Boarding & Catering',
            'amount' => 450.00,
        ]);

        $dayScholar = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Farai',
            'last_name' => 'DayScholar',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'is_boarding' => false,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $dayScholar->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'is_boarding' => false,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Act: Filter for Form 2, Day scholars only
        $response = $this->actingAs($this->adminUser)->get(route('admin.invoices.bulk.create', [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'residency' => 'day',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Farai DayScholar');
        $response->assertSee('Day Scholar');
        // Amount for Day Scholar should be Tuition only ($350.00)
        $response->assertSee('350.00');
    }

    public function test_bulk_invoicing_store_creates_invoices_and_ledger_entries_with_fee_structure_sync(): void
    {
        $tuitionFee = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'category' => 'tuition',
            'code' => 'F2-TUI',
            'label' => 'Tuition Fees',
            'amount' => 300.00,
            'revenue_account_id' => $this->tuitionRevenueAccount->id,
        ]);

        $boardingFee = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'category' => 'boarding',
            'code' => 'F2-BRD',
            'label' => 'Boarding Accommodation',
            'amount' => 500.00,
            'revenue_account_id' => $this->boardingRevenueAccount->id,
        ]);

        // Student 1: Boarder
        $boarder = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Boarder',
            'last_name' => 'Student',
            'grade' => 'Form 2',
            'class_name' => 'East',
            'is_boarding' => true,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);
        $enr1 = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $boarder->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'East',
            'is_boarding' => true,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Student 2: Day Scholar
        $dayStudent = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Day',
            'last_name' => 'Student',
            'grade' => 'Form 2',
            'class_name' => 'East',
            'is_boarding' => false,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);
        $enr2 = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $dayStudent->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'East',
            'is_boarding' => false,
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Post bulk store for both
        $response = $this->actingAs($this->adminUser)->post(route('admin.invoices.bulk.store'), [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'enrollment_ids' => [$enr1->id, $enr2->id],
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertRedirect(route('admin.invoices.index'));
        $response->assertSessionHas('status');

        // Check Boarder Invoice: Tuition ($300) + Boarding ($500) = $800
        $boarderInvoice = Invoice::where('student_id', $boarder->id)->first();
        $this->assertNotNull($boarderInvoice);
        $this->assertEquals(800.00, (float) $boarderInvoice->total_amount);
        $this->assertCount(2, $boarderInvoice->items);

        // Check Day Scholar Invoice: Tuition only ($300) = $300
        $dayInvoice = Invoice::where('student_id', $dayStudent->id)->first();
        $this->assertNotNull($dayInvoice);
        $this->assertEquals(300.00, (float) $dayInvoice->total_amount);
        $this->assertCount(1, $dayInvoice->items);
        $this->assertEquals('Tuition Fees', $dayInvoice->items->first()->description);
    }

    public function test_bulk_invoicing_skips_already_invoiced_students_to_prevent_duplicate_billing(): void
    {
        FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'category' => 'tuition',
            'code' => 'F2-TUI',
            'label' => 'Tuition Fees',
            'amount' => 200.00,
            'revenue_account_id' => $this->tuitionRevenueAccount->id,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Already',
            'last_name' => 'InvoicedStudent',
            'grade' => 'Form 2',
            'class_name' => 'West',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);
        $enr = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'West',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Pre-create an invoice for this term
        Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'enrollment_id' => $enr->id,
            'number' => 'INV-2026-0001',
            'type' => 'fees',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 200.00,
            'balance' => 200.00,
            'issued_at' => now()->toDateString(),
            'status' => 'unpaid',
        ]);

        // Attempt bulk store including this already-invoiced student
        $response = $this->actingAs($this->adminUser)->post(route('admin.invoices.bulk.store'), [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'enrollment_ids' => [$enr->id],
            'issued_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('admin.invoices.index'));
        // Verify only 1 invoice still exists (no duplicate created!)
        $this->assertEquals(1, Invoice::where('student_id', $student->id)->count());
    }

    public function test_bulk_invoicing_strictly_isolates_form_1_south_from_form_2_south(): void
    {
        // Class A: South (legacy single row that had Form 1)
        $classSouth = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'South',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        // Student 1: Form 1 South Boarder (target)
        $studentForm1 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'FormOne',
            'last_name' => 'SouthStudent',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $studentForm1->id,
            'class_id' => $classSouth->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'student_type' => 'boarding',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Student 2: Form 2 South Boarder (points to same South class row, but is Form 2!)
        $studentForm2 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'FormTwo',
            'last_name' => 'SouthStudent',
            'grade' => 'Form 2',
            'class_name' => 'South',
            'is_boarding' => true,
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $studentForm2->id,
            'class_id' => $classSouth->id, // points to classSouth
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2', // explicitly Form 2
            'class_name' => 'South',
            'is_boarding' => true,
            'student_type' => 'boarding',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Act: Filter for Form 1, South, Boarders Only
        $response = $this->actingAs($this->adminUser)->get(route('admin.invoices.bulk.create', [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'residency' => 'boarding',
        ]));

        $response->assertStatus(200);
        $response->assertSee('FormOne SouthStudent');
        $response->assertDontSee('FormTwo SouthStudent');
    }

    public function test_bulk_invoicing_preview_deduplicates_students_with_multiple_enrollments(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Kimberly',
            'last_name' => 'Alifazembe',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'status' => 'active',
        ]);

        // Two enrollment records for the same student (e.g. duplicate import rows or multi-term rows)
        $enr1 = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'student_type' => 'boarding',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);
        $enr2 = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'student_type' => 'boarding',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Act: Filter for Form 1 South
        $response = $this->actingAs($this->adminUser)->get(route('admin.invoices.bulk.create', [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'residency' => 'boarding',
        ]));

        $response->assertStatus(200);
        $previews = $response->viewData('studentPreviews');
        $this->assertCount(1, $previews, 'Student with duplicate enrollments must only appear once in preview');
    }

    public function test_bulk_invoicing_preview_calculates_cohort_breakdown_correctly(): void
    {
        // 2 Boarders and 3 Day Scholars in Form 1 South
        for ($i = 1; $i <= 2; $i++) {
            $student = Student::create([
                'school_id' => $this->school->id,
                'first_name' => "Boarder{$i}",
                'last_name' => 'South',
                'grade' => 'Form 1',
                'class_name' => 'South',
                'is_boarding' => true,
                'status' => 'active',
            ]);
            Enrollment::create([
                'school_id' => $this->school->id,
                'student_id' => $student->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
                'grade' => 'Form 1',
                'class_name' => 'South',
                'is_boarding' => true,
                'student_type' => 'boarding',
                'enrollment_date' => now(),
                'status' => 'active',
            ]);
        }

        for ($i = 1; $i <= 3; $i++) {
            $student = Student::create([
                'school_id' => $this->school->id,
                'first_name' => "DayScholar{$i}",
                'last_name' => 'South',
                'grade' => 'Form 1',
                'class_name' => 'South',
                'is_boarding' => false,
                'status' => 'active',
            ]);
            Enrollment::create([
                'school_id' => $this->school->id,
                'student_id' => $student->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
                'grade' => 'Form 1',
                'class_name' => 'South',
                'is_boarding' => false,
                'student_type' => 'day',
                'enrollment_date' => now(),
                'status' => 'active',
            ]);
        }

        // When filtering for Boarders Only:
        $response = $this->actingAs($this->adminUser)->get(route('admin.invoices.bulk.create', [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'residency' => 'boarding',
        ]));

        $response->assertStatus(200);
        // Matching billing preview should have 2 boarders
        $this->assertEquals(2, $response->viewData('totalBoarders'));
        $this->assertEquals(0, $response->viewData('totalDayScholars'));
        // Cohort breakdown should show total class size of 5: 2 boarders and 3 day scholars
        $this->assertEquals(5, $response->viewData('cohortTotalCount'));
        $this->assertEquals(2, $response->viewData('cohortBoarderCount'));
        $this->assertEquals(3, $response->viewData('cohortDayCount'));
    }

    public function test_bulk_invoicing_preview_with_manual_fee_selection_only_applies_ticked_fees(): void
    {
        $tuition = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'category' => 'tuition',
            'code' => 'F1-TUI',
            'label' => 'Tuition - Day',
            'amount' => 155.00,
            'revenue_account_id' => $this->tuitionRevenueAccount->id,
        ]);

        $boarding = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'category' => 'boarding',
            'code' => 'F1-BRD',
            'label' => 'Boarding Fee',
            'amount' => 490.00,
            'revenue_account_id' => $this->boardingRevenueAccount->id,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Wendy',
            'last_name' => 'Zekiya',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'status' => 'active',
        ]);

        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'student_type' => 'boarding',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // When ONLY Boarding Fee is selected (ticked):
        $response = $this->actingAs($this->adminUser)->get(route('admin.invoices.bulk.create', [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'residency' => 'boarding',
            'fee_structure_ids' => [$boarding->id],
        ]));

        $response->assertStatus(200);
        $response->assertSee('Wendy Zekiya');
        $response->assertSee('Boarding Fee');
        // Total preview for student must be $490.00, NOT $645.00 ($490 + $155)
        $response->assertSee('490.00');
        $previews = $response->viewData('studentPreviews');
        $this->assertCount(1, $previews);
        $this->assertEquals(490.00, $previews[0]['total_amount']);
        $this->assertCount(1, $previews[0]['applied_items']);
        $this->assertEquals('Boarding Fee', $previews[0]['applied_items'][0]['label']);
    }

    public function test_bulk_invoicing_store_with_manual_fee_selection_creates_invoices_with_only_selected_fees(): void
    {
        $tuition = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'category' => 'tuition',
            'code' => 'F1-TUI',
            'label' => 'Tuition - Day',
            'amount' => 155.00,
            'revenue_account_id' => $this->tuitionRevenueAccount->id,
        ]);

        $boarding = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'category' => 'boarding',
            'code' => 'F1-BRD',
            'label' => 'Boarding Fee',
            'amount' => 490.00,
            'revenue_account_id' => $this->boardingRevenueAccount->id,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Wendy',
            'last_name' => 'Zekiya',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'status' => 'active',
        ]);

        $enr = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => true,
            'student_type' => 'boarding',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Post with ONLY boarding fee selected:
        $response = $this->actingAs($this->adminUser)->post(route('admin.invoices.bulk.store'), [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'enrollment_ids' => [$enr->id],
            'fee_structure_ids' => [$boarding->id],
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertRedirect(route('admin.invoices.index'));

        $invoice = Invoice::where('student_id', $student->id)->first();
        $this->assertNotNull($invoice);
        // Only Boarding Fee ($490.00), Tuition ($155.00) must NOT be included!
        $this->assertEquals(490.00, (float) $invoice->total_amount);
        $this->assertEquals(490.00, (float) $invoice->balance);
        $this->assertCount(1, $invoice->items);
        $this->assertEquals('Boarding Fee', $invoice->items->first()->description);
    }

    public function test_bulk_manual_fee_selection_applies_selected_schedule_across_filtered_grades(): void
    {
        $formThreeFee = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 3',
            'category' => 'tuition',
            'code' => 'F3-TUI',
            'label' => 'Tuition - Form 3 Schedule',
            'amount' => 215.00,
            'revenue_account_id' => $this->tuitionRevenueAccount->id,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Test',
            'last_name' => 'Form Two',
            'grade' => 'Form 2',
            'class_name' => 'East',
            'is_boarding' => false,
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'East',
            'is_boarding' => false,
            'student_type' => 'day',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $preview = $this->actingAs($this->adminUser)->get(route('admin.invoices.bulk.create', [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'residency' => 'day',
            'fee_structure_ids' => [$formThreeFee->id],
        ]));

        $preview->assertOk();
        $previews = $preview->viewData('studentPreviews');
        $this->assertCount(1, $previews);
        $this->assertSame(215.0, (float) $previews[0]['total_amount']);
        $this->assertSame($formThreeFee->id, $previews[0]['applied_items'][0]['id']);

        $response = $this->actingAs($this->adminUser)->post(route('admin.invoices.bulk.store'), [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'enrollment_ids' => [$enrollment->id],
            'fee_structure_ids' => [$formThreeFee->id],
            'fee_selection_submitted' => '1',
            'match_fee_grade' => '0',
            'issued_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('admin.invoices.index'));
        $invoice = Invoice::where('student_id', $student->id)->firstOrFail();
        $this->assertSame(215.0, (float) $invoice->total_amount);
        $this->assertSame($formThreeFee->id, $invoice->items->first()->fee_structure_id);
    }

    public function test_bulk_invoice_chunk_endpoint_reports_saved_and_duplicate_rows_idempotently(): void
    {
        $fee = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'category' => 'tuition',
            'code' => 'F2-TUI-CHUNK',
            'label' => 'Tuition - Form 2',
            'amount' => 135.00,
            'revenue_account_id' => $this->tuitionRevenueAccount->id,
        ]);
        $record = $this->createStudentEnrollment(false);
        $payload = [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'enrollment_ids' => [$record['enrollment']->id],
            'fee_structure_ids' => [$fee->id],
            'fee_selection_submitted' => '1',
            'match_fee_grade' => '1',
            'issued_at' => now()->toDateString(),
        ];

        $headers = ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];
        $first = $this->actingAs($this->adminUser)->post(route('admin.invoices.bulk.store'), $payload, $headers);
        $first->assertOk()
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('created_total', 135)
            ->assertJsonPath('skipped_count', 0);

        $retry = $this->actingAs($this->adminUser)->post(route('admin.invoices.bulk.store'), $payload, $headers);
        $retry->assertOk()
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('skipped_count', 1);

        $this->assertSame(1, Invoice::where('student_id', $record['student']->id)->count());
    }

    public function test_single_invoicing_create_view_renders_manual_fee_checkboxes(): void
    {
        FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'category' => 'boarding',
            'code' => 'F1-BRD',
            'label' => 'Boarding Fee',
            'amount' => 490.00,
            'revenue_account_id' => $this->boardingRevenueAccount->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.invoices.create'));

        $response->assertStatus(200);
        $response->assertSee('Select Fee Structures to Invoice (Tick to Include)');
        $response->assertSee('Boarding Fee');
        $response->assertSee('single-fee-checkbox');
    }

    public function test_bulk_generation_expands_selected_packages_to_residency_specific_vote_lines(): void
    {
        $packages = HandeFeeVotesService::syncSchoolVotes($this->school, '2026', 'Term 1');
        $boarder = $this->createStudentEnrollment(true);
        $dayScholar = $this->createStudentEnrollment(false);

        $preview = $this->actingAs($this->adminUser)->get(route('admin.invoices.bulk.create', [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
        ]));
        $preview->assertOk();
        $previews = collect($preview->viewData('studentPreviews'))->keyBy(fn ($row) => $row['student']->id);
        $this->assertSame(470.0, (float) $previews[$boarder['student']->id]['total_amount']);
        $this->assertSame(135.0, (float) $previews[$dayScholar['student']->id]['total_amount']);
        $this->assertCount(7, $previews[$boarder['student']->id]['applied_items']);
        $this->assertCount(7, $previews[$dayScholar['student']->id]['applied_items']);

        $response = $this->actingAs($this->adminUser)->post(route('admin.invoices.bulk.store'), [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'enrollment_ids' => [$boarder['enrollment']->id, $dayScholar['enrollment']->id],
            'fee_structure_ids' => [$packages['boarding_parent']->id, $packages['day_parent']->id],
            'issued_at' => now()->toDateString(),
        ]);
        $response->assertRedirect(route('admin.invoices.index'));

        foreach ([[$boarder['student'], 470.0, 'BOARDING_FEES'], [$dayScholar['student'], 135.0, 'FOOD']] as [$student, $total, $lastVote]) {
            $invoice = Invoice::where('student_id', $student->id)->firstOrFail();
            $this->assertSame($total, (float) $invoice->total_amount);
            $this->assertCount(7, $invoice->items);
            $this->assertSame($lastVote, $invoice->items->firstWhere('vote_code', $lastVote)->vote_code);
            $this->assertNotContains($packages['boarding_parent']->id, $invoice->items->pluck('fee_structure_id')->all());
            $this->assertNotContains($packages['day_parent']->id, $invoice->items->pluck('fee_structure_id')->all());
        }
    }

    public function test_web_and_scheduled_generation_use_grade_independent_vote_schedules_and_transport_eligibility(): void
    {
        HandeFeeVotesService::syncSchoolVotes($this->school, '2026', 'Term 1');
        $boarder = $this->createStudentEnrollment(true);
        $dayScholar = $this->createStudentEnrollment(false);
        $dayScholar['student']->update(['has_transport' => true]);
        $dayScholar['enrollment']->update(['has_transport' => true]);
        FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'category' => 'transport',
            'code' => 'TRANSPORT-2026',
            'label' => 'Transport',
            'amount' => 25.00,
            'service_type' => 'transport',
        ]);

        $webResponse = $this->actingAs($this->adminUser)->post(route('admin.invoices.process-auto-generate'), [
            'target_academic_year' => '2026',
            'target_term' => 'Term 1',
            'generation_date' => now()->toDateString(),
            'include_boarding' => '1',
            'include_transport' => '1',
        ]);
        $webResponse->assertRedirect(route('admin.invoices.index'));

        $boarderInvoice = Invoice::where('student_id', $boarder['student']->id)->firstOrFail();
        $dayInvoice = Invoice::where('student_id', $dayScholar['student']->id)->firstOrFail();
        $this->assertSame(470.0, (float) $boarderInvoice->total_amount);
        $this->assertSame(160.0, (float) $dayInvoice->total_amount);
        $this->assertCount(7, $boarderInvoice->items);
        $this->assertCount(8, $dayInvoice->items);

        $otherSchool = School::create([
            'name' => 'Scheduled Invoice School',
            'code' => 'HHS002',
            'is_active' => true,
        ]);
        HandeFeeVotesService::syncSchoolVotes($otherSchool, '2026', 'Term 1');
        $scheduledBoarder = $this->createStudentEnrollment(true, $otherSchool);
        $scheduledDay = $this->createStudentEnrollment(false, $otherSchool);

        Artisan::call('invoices:generate-scheduled', [
            '--school_id' => $otherSchool->id,
            '--academic_year' => '2026',
            '--term' => 'Term 1',
            '--dry-run' => true,
        ]);

        $this->assertStringContainsString('2 invoices would be created', Artisan::output());
        $this->assertDatabaseMissing('invoices', ['student_id' => $scheduledBoarder['student']->id]);
        $this->assertDatabaseMissing('invoices', ['student_id' => $scheduledDay['student']->id]);

        Artisan::call('invoices:generate-scheduled', [
            '--school_id' => $otherSchool->id,
            '--academic_year' => '2026',
            '--term' => 'Term 1',
        ]);
        $this->assertStringContainsString('2 invoices created', Artisan::output());
        $scheduledBoarderInvoice = Invoice::where('student_id', $scheduledBoarder['student']->id)->firstOrFail();
        $scheduledDayInvoice = Invoice::where('student_id', $scheduledDay['student']->id)->firstOrFail();
        $this->assertSame(470.0, (float) $scheduledBoarderInvoice->total_amount);
        $this->assertSame(135.0, (float) $scheduledDayInvoice->total_amount);
        $this->assertCount(7, $scheduledBoarderInvoice->items);
        $this->assertCount(7, $scheduledDayInvoice->items);
    }

    public function test_bulk_custom_items_preserve_vote_codes_and_apply_residency_restrictions(): void
    {
        $boarder = $this->createStudentEnrollment(true);
        $dayScholar = $this->createStudentEnrollment(false);

        $response = $this->actingAs($this->adminUser)->post(route('admin.invoices.bulk.store'), [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'enrollment_ids' => [$boarder['enrollment']->id, $dayScholar['enrollment']->id],
            'issued_at' => now()->toDateString(),
            'custom_fee_items' => [
                ['description' => 'Extra science subject', 'category' => 'other', 'amount' => 5, 'vote_code' => 'SCIENCE_LEVY'],
                ['description' => 'Boarding top-up', 'category' => 'other', 'amount' => 10, 'vote_code' => 'BOARDING_FEES'],
                ['description' => 'Lunch top-up', 'category' => 'other', 'amount' => 5, 'vote_code' => 'FOOD'],
            ],
        ]);
        $response->assertRedirect(route('admin.invoices.index'));

        $boarderInvoice = Invoice::where('student_id', $boarder['student']->id)->firstOrFail();
        $dayInvoice = Invoice::where('student_id', $dayScholar['student']->id)->firstOrFail();
        $this->assertSame(15.0, (float) $boarderInvoice->total_amount);
        $this->assertSame(10.0, (float) $dayInvoice->total_amount);
        $this->assertEqualsCanonicalizing(['SCIENCE_LEVY', 'BOARDING_FEES'], $boarderInvoice->items->pluck('vote_code')->all());
        $this->assertEqualsCanonicalizing(['SCIENCE_LEVY', 'FOOD'], $dayInvoice->items->pluck('vote_code')->all());
    }

    private function createStudentEnrollment(bool $isBoarding, ?School $school = null): array
    {
        $school ??= $this->school;
        $student = Student::create([
            'school_id' => $school->id,
            'first_name' => $isBoarding ? 'Boarding' : 'Day',
            'last_name' => 'Vote Test',
            'grade' => 'Form 2',
            'is_boarding' => $isBoarding,
            'status' => 'active',
        ]);
        $enrollment = Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'is_boarding' => $isBoarding,
            'enrollment_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        return compact('student', 'enrollment');
    }
}
