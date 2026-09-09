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
}
