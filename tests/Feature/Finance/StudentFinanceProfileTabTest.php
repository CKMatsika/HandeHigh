<?php

namespace Tests\Feature\Finance;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFinanceProfileTabTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private School $foreignSchool;
    private User $admin;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
        ]);

        $this->foreignSchool = School::create([
            'name' => 'Foreign Academy',
            'code' => 'FA',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin User',
            'email' => 'admin@handehigh.test',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $this->admin->assignRole('school-admin');

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tinashe',
            'last_name' => 'Moyo',
            'admission_number' => 'ADM-2026-001',
            'registration_number' => 'REG-2026-001',
            'grade' => 'Form 3',
            'class_name' => 'Form 3 A',
            'status' => 'active',
        ]);
    }

    public function test_student_profile_displays_finance_tab_with_debtor_balance(): void
    {
        // 1. Create Invoices for Student
        $invoice1 = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'number' => 'INV-2026-001',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 500.00,
            'balance' => 200.00,
            'issued_at' => '2026-01-10',
            'status' => 'partial',
        ]);

        InvoiceItem::create([
            'school_id' => $this->school->id,
            'invoice_id' => $invoice1->id,
            'description' => 'Tuition Fee Term 1',
            'category' => 'tuition',
            'quantity' => 1,
            'unit_amount' => 500.00,
            'line_total' => 500.00,
        ]);

        // 2. Create Payment / Receipt
        $payment1 = Payment::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'method' => 'bank',
            'reference' => 'PAY-REF-9901',
            'amount' => 300.00,
            'paid_at' => '2026-01-15',
            'status' => 'completed',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment1->id,
            'invoice_id' => $invoice1->id,
            'amount' => 300.00,
        ]);

        // 3. Create Credit Note
        CreditNote::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'credit_note_number' => 'CN-2026-001',
            'credit_note_date' => '2026-01-20',
            'type' => 'student',
            'amount' => 50.00,
            'applied_amount' => 50.00,
            'status' => 'applied',
            'reason' => 'Scholarship discount',
        ]);

        // Expected Debtor Outstanding Balance: 500 - 300 - 50 = 150
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.show', ['student' => $this->student, 'tab' => 'finance']));

        $response->assertOk();
        $response->assertSee('Debtor Balance');
        $response->assertSee('$150.00');
        $response->assertSee('$500.00'); // Total Invoiced
        $response->assertSee('$300.00'); // Total Paid
        $response->assertSee('$50.00'); // Credit Adjustments
        $response->assertSee('INV-2026-001');
        $response->assertSee('PAY-REF-9901');
        $response->assertSee('GL Entry');
        $response->assertSee('Student Account Statement');
        $response->assertSee('Print Statement');
        $response->assertSee('PDF Statement');
    }

    public function test_student_statement_print_route_works_from_profile_link(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.statement.print', ['student' => $this->student, 'academic_year' => '2026']));

        $response->assertOk();
        $response->assertSee($this->student->full_name);
        $response->assertSee('Hande High School');
    }

    public function test_foreign_school_cannot_view_student_finance_profile(): void
    {
        $foreignAdmin = User::create([
            'school_id' => $this->foreignSchool->id,
            'name' => 'Foreign Admin',
            'email' => 'admin@foreign.test',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $foreignAdmin->assignRole('school-admin');

        $response = $this->actingAs($foreignAdmin)
            ->get(route('admin.students.show', ['student' => $this->student, 'tab' => 'finance']));

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_student_general_ledger_postings_are_strictly_isolated_to_target_student(): void
    {
        // 1. Target student with NO admission number (null) - matching Wendy Zekiya scenario
        $wendy = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Wendy',
            'last_name' => 'Zekiya',
            'admission_number' => null,
            'grade' => 'Form 1',
            'class_name' => 'Form 1 South',
            'status' => 'active',
        ]);

        $wendyInvoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $wendy->id,
            'number' => 'INV-2026-000027',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 470.00,
            'balance' => 20.00,
            'issued_at' => '2026-01-01',
            'status' => 'partial',
        ]);

        $wendyPayment = Payment::create([
            'school_id' => $this->school->id,
            'student_id' => $wendy->id,
            'method' => 'bank',
            'reference' => 'Receipt Number 11706',
            'amount' => 450.00,
            'paid_at' => '2026-01-31',
            'status' => 'completed',
        ]);

        PaymentAllocation::create([
            'payment_id' => $wendyPayment->id,
            'invoice_id' => $wendyInvoice->id,
            'amount' => 450.00,
        ]);

        // Post Wendy transactions to General Ledger
        $accountingService = app(\App\Services\AccountingService::class);
        $accountingService->postInvoice($wendyInvoice);
        $accountingService->postPayment($wendyPayment, $wendyInvoice);

        // 2. Create OTHER students in the same school with invoices and post them to General Ledger
        $otherStudent1 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tawananyasha',
            'last_name' => 'Mupanguri',
            'admission_number' => null,
            'grade' => 'Form 1',
            'class_name' => 'Form 1 South',
            'status' => 'active',
        ]);

        $otherInvoice1 = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $otherStudent1->id,
            'number' => 'INV-2026-000025',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 645.00,
            'balance' => 645.00,
            'issued_at' => '2026-10-13',
            'status' => 'unpaid',
        ]);
        $accountingService->postInvoice($otherInvoice1);

        $otherStudent2 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Kuzivakwashe',
            'last_name' => 'Nsingo',
            'admission_number' => null,
            'grade' => 'Form 1',
            'status' => 'active',
        ]);

        $otherInvoice2 = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $otherStudent2->id,
            'number' => 'INV-2026-000026',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 645.00,
            'balance' => 645.00,
            'issued_at' => '2026-10-13',
            'status' => 'unpaid',
        ]);
        $accountingService->postInvoice($otherInvoice2);

        // 3. Request Wendy's profile finance tab
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.show', ['student' => $wendy, 'tab' => 'finance']));

        $response->assertOk();

        // Wendy's invoice and payment must be visible
        $response->assertSee('INV-2026-000027');
        $response->assertSee('Receipt Number 11706');

        // Check glEntries in view data
        $glEntries = $response->viewData('glEntries');
        $this->assertNotEmpty($glEntries);

        // Assert all batches in glEntries belong strictly to Wendy's transactions
        foreach ($glEntries as $entry) {
            $batch = $entry->batch;
            $this->assertNotNull($batch);
            $this->assertContains($batch->reference_number, ['INV-2026-000027', 'Receipt Number 11706']);
            $this->assertStringNotContainsString('Tawananyasha', (string) $batch->description);
            $this->assertStringNotContainsString('Mupanguri', (string) $batch->description);
            $this->assertStringNotContainsString('INV-2026-000025', (string) $batch->description);
            $this->assertStringNotContainsString('Kuzivakwashe', (string) $batch->description);
            $this->assertStringNotContainsString('Nsingo', (string) $batch->description);
            $this->assertStringNotContainsString('INV-2026-000026', (string) $batch->description);
        }

        // HTML view must NOT contain other students' postings in Wendy's profile
        $response->assertDontSee('Tawananyasha Mupanguri');
        $response->assertDontSee('INV-2026-000025');
        $response->assertDontSee('Kuzivakwashe Nsingo');
        $response->assertDontSee('INV-2026-000026');
    }
}
