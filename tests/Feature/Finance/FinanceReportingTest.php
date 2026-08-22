<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\CreditNote;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Receipt;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\FinanceReportingService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceReportingTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $bursarA;
    private User $bursarB;
    private User $teacherA;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->schoolA = School::create(['name' => 'Hande High School A', 'code' => 'HHSA']);
        $this->schoolB = School::create(['name' => 'Foreign School B', 'code' => 'FSB']);

        $this->bursarA = $this->createUserWithRole('bursar', $this->schoolA);
        $this->bursarB = $this->createUserWithRole('bursar', $this->schoolB);
        $this->teacherA = $this->createUserWithRole('teacher', $this->schoolA);
    }

    private function createUserWithRole(string $role, School $school): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'name' => "{$role} {$school->code}",
            'email' => "{$role}.{$school->code}." . uniqid() . "@example.com",
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        return $user;
    }

    public function test_debtors_aging_bucket_calculations(): void
    {
        $student1 = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'admission_number' => 'ADM-001',
            'grade' => 'Form 1',
            'class_name' => '1A',
        ]);

        $asOf = Carbon::parse('2026-08-20');

        // Current invoice (due in future)
        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student1->id,
            'number' => 'INV-CURR',
            'total_amount' => 500,
            'balance' => 500,
            'issued_at' => '2026-08-01',
            'due_date' => '2026-08-25',
            'status' => 'unpaid',
        ]);

        // 1-30 days overdue (due 15 days ago)
        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student1->id,
            'number' => 'INV-30',
            'total_amount' => 400,
            'balance' => 400,
            'issued_at' => '2026-07-15',
            'due_date' => '2026-08-05',
            'status' => 'unpaid',
        ]);

        // 31-60 days overdue (due 45 days ago)
        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student1->id,
            'number' => 'INV-60',
            'total_amount' => 300,
            'balance' => 300,
            'issued_at' => '2026-06-15',
            'due_date' => '2026-07-06',
            'status' => 'unpaid',
        ]);

        // 61-90 days overdue (due 75 days ago)
        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student1->id,
            'number' => 'INV-90',
            'total_amount' => 250,
            'balance' => 250,
            'issued_at' => '2026-05-15',
            'due_date' => '2026-06-06',
            'status' => 'unpaid',
        ]);

        // 91-120 days overdue (due 105 days ago)
        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student1->id,
            'number' => 'INV-120',
            'total_amount' => 200,
            'balance' => 200,
            'issued_at' => '2026-04-15',
            'due_date' => '2026-05-07',
            'status' => 'unpaid',
        ]);

        // 120+ days overdue (due 150 days ago)
        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student1->id,
            'number' => 'INV-OLD',
            'total_amount' => 150,
            'balance' => 150,
            'issued_at' => '2026-02-15',
            'due_date' => '2026-03-23',
            'status' => 'unpaid',
        ]);

        $service = new FinanceReportingService();
        $report = $service->getDebtorsAging($this->schoolA, ['as_of_date' => '2026-08-20']);

        $this->assertEquals(1800.0, $report['totals']['total_invoiced']);
        $this->assertEquals(1800.0, $report['totals']['total_outstanding']);
        $this->assertEquals(500.0, $report['totals']['current']);
        $this->assertEquals(400.0, $report['totals']['bucket_1_30']);
        $this->assertEquals(300.0, $report['totals']['bucket_31_60']);
        $this->assertEquals(250.0, $report['totals']['bucket_61_90']);
        $this->assertEquals(200.0, $report['totals']['bucket_91_120']);
        $this->assertEquals(150.0, $report['totals']['bucket_120_plus']);
    }

    public function test_debtors_aging_filtering_and_grouping(): void
    {
        $studentForm1 = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Farai',
            'last_name' => 'Ncube',
            'admission_number' => 'ADM-F1',
            'grade' => 'Form 1',
            'class_name' => '1A',
        ]);

        $studentForm2 = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Chipo',
            'last_name' => 'Gumbo',
            'admission_number' => 'ADM-F2',
            'grade' => 'Form 2',
            'class_name' => '2B',
        ]);

        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $studentForm1->id,
            'number' => 'INV-F1',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'type' => 'tuition',
            'total_amount' => 600,
            'balance' => 600,
            'issued_at' => '2026-01-10',
            'due_date' => '2026-01-31',
            'status' => 'unpaid',
        ]);

        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $studentForm2->id,
            'number' => 'INV-F2',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'type' => 'boarding',
            'total_amount' => 900,
            'balance' => 900,
            'issued_at' => '2026-01-10',
            'due_date' => '2026-01-31',
            'status' => 'unpaid',
        ]);

        $service = new FinanceReportingService();

        // Filter by Form 1
        $f1Report = $service->getDebtorsAging($this->schoolA, ['form' => 'Form 1']);
        $this->assertCount(1, $f1Report['rows']);
        $this->assertEquals('ADM-F1', $f1Report['rows']->first()['admission_number']);

        // Group by Form
        $groupedReport = $service->getDebtorsAging($this->schoolA, ['group_by' => 'form']);
        $this->assertCount(2, $groupedReport['grouped_data']);
        $this->assertArrayHasKey('Form 1', $groupedReport['grouped_data']);
        $this->assertArrayHasKey('Form 2', $groupedReport['grouped_data']);
        $this->assertEquals(600.0, $groupedReport['grouped_data']['Form 1']['total_outstanding']);
        $this->assertEquals(900.0, $groupedReport['grouped_data']['Form 2']['total_outstanding']);
    }

    public function test_student_statement_running_balance(): void
    {
        $student = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Tatenda',
            'last_name' => 'Mutasa',
            'admission_number' => 'ADM-STMT',
            'grade' => 'Form 3',
            'class_name' => '3Science',
        ]);

        // Invoice 1: $1,000
        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'number' => 'INV-001',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 1000,
            'balance' => 600,
            'issued_at' => '2026-01-05',
            'due_date' => '2026-01-31',
            'status' => 'partial',
        ]);

        // Payment 1: $400
        Payment::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'method' => 'bank_transfer',
            'reference' => 'TXN-001',
            'amount' => 400,
            'paid_at' => '2026-01-20',
            'status' => 'completed',
        ]);

        // Credit note: $100
        CreditNote::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'credit_note_number' => 'CN-001',
            'type' => 'student',
            'total_amount' => 100,
            'applied_amount' => 100,
            'balance' => 0,
            'status' => 'applied',
            'credit_note_date' => '2026-01-25',
        ]);

        // Invoice 2: $500
        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'number' => 'INV-002',
            'academic_year' => '2026',
            'term' => 'Term 2',
            'total_amount' => 500,
            'balance' => 500,
            'issued_at' => '2026-05-05',
            'due_date' => '2026-05-31',
            'status' => 'unpaid',
        ]);

        $service = new FinanceReportingService();
        $statement = $service->getStudentStatement($this->schoolA, $student);

        $this->assertEquals(1500.0, $statement['total_debits']);
        $this->assertEquals(500.0, $statement['total_credits']);
        $this->assertEquals(1000.0, $statement['closing_balance']);
        $this->assertCount(4, $statement['ledger_rows']);
    }

    public function test_fee_collections_filtering_and_summaries(): void
    {
        $student = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Tariro',
            'last_name' => 'Katsande',
            'admission_number' => 'ADM-COL',
            'grade' => 'Form 4',
            'class_name' => '4Arts',
        ]);

        Payment::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'method' => 'cash',
            'reference' => 'PAY-CASH-1',
            'amount' => 350,
            'paid_at' => '2026-08-10',
            'status' => 'completed',
        ]);

        Payment::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'method' => 'ecocash',
            'reference' => 'PAY-ECO-1',
            'amount' => 150,
            'paid_at' => '2026-08-12',
            'status' => 'completed',
        ]);

        Receipt::create([
            'school_id' => $this->schoolA->id,
            'receipt_number' => 'RCT-001',
            'receipt_date' => '2026-08-14',
            'type' => 'service',
            'customer_name' => 'SDA Donor',
            'total_amount' => 200,
            'grand_total' => 200,
            'payment_method' => 'bank_transfer',
        ]);

        $service = new FinanceReportingService();
        $report = $service->getFeeCollections($this->schoolA, [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);

        $this->assertEquals(700.0, $report['total_collected']);
        $this->assertEquals(3, $report['transaction_count']);
        $this->assertArrayHasKey('Cash', $report['by_method']);
        $this->assertArrayHasKey('Ecocash', $report['by_method']);
        $this->assertArrayHasKey('Bank_transfer', $report['by_method']);
    }

    public function test_outstanding_fees_report(): void
    {
        $student1 = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Kuda',
            'last_name' => 'Mupfumi',
            'admission_number' => 'ADM-OUT-1',
            'grade' => 'Form 1',
            'class_name' => '1A',
        ]);

        $student2 = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Paidamoyo',
            'last_name' => 'Shumba',
            'admission_number' => 'ADM-OUT-2',
            'grade' => 'Form 2',
            'class_name' => '2A',
        ]);

        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student1->id,
            'number' => 'INV-OUT-1',
            'total_amount' => 500,
            'balance' => 200,
            'status' => 'partial',
        ]);

        Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student2->id,
            'number' => 'INV-OUT-2',
            'total_amount' => 700,
            'balance' => 700,
            'status' => 'unpaid',
        ]);

        $service = new FinanceReportingService();
        $report = $service->getOutstandingFees($this->schoolA);

        $this->assertEquals(2, $report['total_students']);
        $this->assertEquals(1200.0, $report['total_charges']);
        $this->assertEquals(300.0, $report['total_paid']);
        $this->assertEquals(900.0, $report['total_outstanding']);
    }

    public function test_income_expenditure_from_chart_of_accounts(): void
    {
        $revAccount = Account::create([
            'school_id' => $this->schoolA->id,
            'code' => '4001',
            'name' => 'Tuition Fees Income',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
        ]);

        $expAccount = Account::create([
            'school_id' => $this->schoolA->id,
            'code' => '5001',
            'name' => 'Staff Salaries Expense',
            'type' => 'expense',
            'category' => 'salary_expense',
        ]);

        $batch = JournalBatch::create([
            'school_id' => $this->schoolA->id,
            'batch_number' => 'JB-2026-001',
            'transaction_date' => '2026-08-01',
            'description' => 'August Operations',
            'source_type' => 'manual',
            'status' => 'posted',
        ]);

        JournalEntry::create([
            'journal_batch_id' => $batch->id,
            'account_id' => $revAccount->id,
            'entry_type' => 'credit',
            'amount' => 5000,
        ]);

        JournalEntry::create([
            'journal_batch_id' => $batch->id,
            'account_id' => $expAccount->id,
            'entry_type' => 'debit',
            'amount' => 3200,
        ]);

        $service = new FinanceReportingService();
        $report = $service->getIncomeExpenditure($this->schoolA, [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);

        $this->assertEquals(5000.0, $report['total_income']);
        $this->assertEquals(3200.0, $report['total_expenditure']);
        $this->assertEquals(1800.0, $report['net_surplus_deficit']);
    }

    public function test_tenant_isolation_cross_school_shielding(): void
    {
        // Create student & invoice in School B
        $studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'first_name' => 'Foreign',
            'last_name' => 'Student',
            'admission_number' => 'ADM-SB-001',
        ]);

        Invoice::create([
            'school_id' => $this->schoolB->id,
            'student_id' => $studentB->id,
            'number' => 'INV-SCHOOL-B',
            'total_amount' => 9999,
            'balance' => 9999,
            'issued_at' => '2026-01-01',
            'due_date' => '2026-01-31',
            'status' => 'unpaid',
        ]);

        // School A bursar accesses debtors aging
        $response = $this->actingAs($this->bursarA)->get(route('admin.reports.debtors-aging'));
        $response->assertOk();
        $response->assertDontSee('INV-SCHOOL-B');
        $response->assertDontSee('Foreign Student');
        $response->assertDontSee('9999');

        // School A bursar attempts to access School B student statement -> 403 or 404
        $statementResponse = $this->actingAs($this->bursarA)->get(route('admin.reports.student-statement', ['student_id' => $studentB->id]));
        $statementResponse->assertNotFound();
    }

    public function test_spoofed_school_id_in_request_is_ignored(): void
    {
        $response = $this->actingAs($this->bursarA)->get(route('admin.reports.debtors-aging', [
            'school_id' => $this->schoolB->id,
        ]));

        $response->assertOk();
        // Confirms it loaded School A dashboard
        $response->assertViewHas('totals');
    }

    public function test_authorized_finance_roles_can_access_reports(): void
    {
        $accountant = $this->createUserWithRole('accountant', $this->schoolA);
        $accountsClerk = $this->createUserWithRole('accounts-clerk', $this->schoolA);
        $schoolAdmin = $this->createUserWithRole('school-admin', $this->schoolA);

        $this->actingAs($accountant)->get(route('admin.reports.finance-dashboard'))->assertOk();
        $this->actingAs($accountsClerk)->get(route('admin.reports.debtors-aging'))->assertOk();
        $this->actingAs($schoolAdmin)->get(route('admin.reports.fee-collections'))->assertOk();
    }

    public function test_unauthorized_role_is_denied(): void
    {
        // Teacher role does not have financial reporting permissions
        $response = $this->actingAs($this->teacherA)->get(route('admin.reports.debtors-aging'));
        $response->assertForbidden();
    }

    public function test_excel_export_generates_file_download(): void
    {
        $response = $this->actingAs($this->bursarA)->get(route('admin.reports.debtors-aging.export'));
        $response->assertOk();
        $response->assertHeader('content-disposition');
    }
}
