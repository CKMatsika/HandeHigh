<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceLifecycleAuditTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected Student $student;
    protected Enrollment $enrollment;
    protected Account $receivableAccount;
    protected Account $tuitionAccount;

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
            'name' => 'Bursar Admin',
            'email' => 'bursar@handehigh.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('super-admin');

        $this->receivableAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1201',
            'name' => 'Student Fees Receivable',
            'type' => 'asset',
            'category' => 'receivable',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->tuitionAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '4001',
            'name' => 'Tuition Fees Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Wendy',
            'last_name' => 'Zekiya',
            'admission_number' => 'ADM-2678',
            'grade' => 'Form 1',
            'class_name' => 'Form 1 South',
            'is_boarding' => true,
            'status' => 'active',
        ]);

        $this->enrollment = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'residency_status' => 'boarder',
            'enrollment_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    public function test_user_can_reinvoice_student_when_previous_invoice_was_cancelled(): void
    {
        // 1. Create a cancelled invoice
        $cancelledInvoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'number' => 'INV-2026-000001',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 500.00,
            'balance' => 500.00,
            'status' => 'cancelled',
            'issued_at' => '2026-01-01',
        ]);

        // 2. Submit a new invoice for the same student, year, and term
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.invoices.store'), [
                'student_id' => $this->student->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
                'issued_at' => '2026-01-01',
                'due_date' => '2026-01-31',
                'items' => [
                    [
                        'description' => 'Tuition - Day Corrected',
                        'category' => 'tuition',
                        'quantity' => 1,
                        'unit_amount' => 155.00,
                    ],
                    [
                        'description' => 'Boarding Fee Corrected',
                        'category' => 'boarding',
                        'quantity' => 1,
                        'unit_amount' => 490.00,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.invoices.index'));

        // Check that new invoice exists and is unpaid
        $newInvoice = Invoice::where('school_id', $this->school->id)
            ->where('student_id', $this->student->id)
            ->where('status', 'unpaid')
            ->first();

        $this->assertNotNull($newInvoice);
        $this->assertEquals(645.00, (float) $newInvoice->total_amount);

        // Check AuditLog was created for invoice creation
        $auditLog = AuditLog::where('school_id', $this->school->id)
            ->where('action', 'create')
            ->where('model_id', $newInvoice->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertStringContainsString('Wendy Zekiya', $auditLog->description);
    }

    public function test_user_can_permanently_delete_a_cancelled_invoice_with_audit_trail_and_reason(): void
    {
        $cancelledInvoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'number' => 'INV-2026-000002',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 490.00,
            'balance' => 490.00,
            'status' => 'cancelled',
            'issued_at' => '2026-01-01',
        ]);

        InvoiceItem::create([
            'invoice_id' => $cancelledInvoice->id,
            'description' => 'Mistaken Boarding Fee',
            'category' => 'boarding',
            'quantity' => 1,
            'unit_amount' => 490.00,
            'line_total' => 490.00,
        ]);

        $deletionReason = 'Mistake on dates and fee structure lines during bulk generation';

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.invoices.destroy', $cancelledInvoice), [
                'deletion_reason' => $deletionReason,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.invoices.index'));

        // Assert invoice is permanently removed from invoices table
        $this->assertDatabaseMissing('invoices', ['id' => $cancelledInvoice->id]);
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $cancelledInvoice->id]);

        // Assert audit log was recorded with reason and details
        $auditLog = AuditLog::where('school_id', $this->school->id)
            ->where('action', 'delete')
            ->where('model_id', $cancelledInvoice->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertStringContainsString($deletionReason, $auditLog->description);
        $this->assertEquals($deletionReason, $auditLog->old_values['deletion_reason']);
        $this->assertEquals('INV-2026-000002', $auditLog->old_values['invoice_number']);
        $this->assertCount(1, $auditLog->old_values['items']);
    }

    public function test_cannot_delete_invoice_with_payments(): void
    {
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'number' => 'INV-2026-000003',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 300.00,
            'balance' => 200.00,
            'status' => 'partial',
            'issued_at' => '2026-01-01',
        ]);

        $payment = Payment::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'amount' => 100.00,
            'method' => 'cash',
            'paid_at' => '2026-01-05',
            'status' => 'completed',
        ]);

        PaymentAllocation::create([
            'school_id' => $this->school->id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.invoices.destroy', $invoice), [
                'deletion_reason' => 'Trying to delete paid invoice',
            ]);

        $response->assertSessionHasErrors(['general']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_superiors_can_view_invoice_deletion_audit_logs(): void
    {
        AuditLog::create([
            'school_id' => $this->school->id,
            'user_id' => $this->adminUser->id,
            'action' => 'delete',
            'module' => 'accounting',
            'model_type' => Invoice::class,
            'model_id' => 999,
            'description' => 'Deleted invoice INV-2026-999 for Wendy Zekiya. Reason: Wrong fee structures applied in bulk.',
            'old_values' => [
                'deletion_reason' => 'Wrong fee structures applied in bulk.',
                'invoice_number' => 'INV-2026-999',
                'items' => [
                    ['description' => 'Tuition', 'quantity' => 1, 'line_total' => 150.00],
                ],
                'total_amount' => 150.00,
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.school-setup.logs', ['module' => 'accounting', 'action' => 'delete']));

        $response->assertOk();
        $response->assertSee('Deleted invoice INV-2026-999');
        $response->assertSee('Wrong fee structures applied in bulk.');
        $response->assertSee('View deleted snapshot');
    }

    public function test_invoice_print_view_renders_clean_portrait_layout_matching_specification(): void
    {
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'number' => 'INV-2026-000027',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 490.00,
            'balance' => 490.00,
            'status' => 'unpaid',
            'issued_at' => '2026-01-01',
            'due_date' => '2026-01-31',
        ]);

        $invoice->items()->create([
            'description' => 'Boarding Fee',
            'quantity' => 1,
            'unit_amount' => 490.00,
            'line_total' => 490.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.invoices.print', $invoice));

        $response->assertOk();
        $response->assertSee('STUDENT INVOICE', false);
        $response->assertSee('INV-2026-000027');
        $response->assertSee('2026 &nbsp;/&nbsp; TERM 1', false);
        $response->assertSee('ISSUED ON');
        $response->assertSee('DUE DATE');
        $response->assertSee('UNPAID');
        $response->assertSee('BILL TO / STUDENT');
        $response->assertSee('Wendy Zekiya');
        $response->assertSee('INVOICE TOTAL');
        $response->assertSee('AMOUNT PAID');
        $response->assertSee('BALANCE DUE');
        $response->assertSee('ITEMISED FEE CHARGES');
        $response->assertSee('Boarding Fee');
        $response->assertSee('TOTAL INVOICE');
        $response->assertSee('LESS PAYMENTS');
        $response->assertSee('BANKING & PAYMENT DETAILS', false);
        $response->assertSee('Please use the invoice reference when making payment.');
        $response->assertSee('PAYMENT NOTE');
        $response->assertSee('Page 1 of 1');
        // Ensure layouts.app sidebar and global app footer are NOT present
        $response->assertDontSee('Betterlink Systems');
    }

    public function test_invoice_print_view_picks_up_uploaded_school_logo(): void
    {
        // 1. Give the school an uploaded logo in the system
        $this->school->update([
            'logo' => 'schools/' . $this->school->id . '/branding/test_school_crest.png',
        ]);

        // 2. Create invoice with empty/null logo in snapshot (e.g. created prior to logo upload)
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'number' => 'INV-2026-000088',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 490.00,
            'balance' => 490.00,
            'status' => 'unpaid',
            'issued_at' => '2026-01-01',
            'school_snapshot' => [
                'name' => 'Hande High School',
                'logo_url' => null,
                'logo_base64' => null,
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.invoices.print', $invoice));

        $response->assertOk();
        // Assert that the img tag with the school logo is rendered, NOT the fallback crest box
        $response->assertSee('test_school_crest.png');
        $response->assertSee('class="school-logo-img"', false);
    }
}
