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
}
