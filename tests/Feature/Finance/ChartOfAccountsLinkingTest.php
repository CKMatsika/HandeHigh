<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\Bill;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AccountingService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountsLinkingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $bursar;
    private Student $student;
    private AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
        ]);

        $this->bursar = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bursar User',
            'email' => 'bursar@handehigh.test',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->bursar->assignRole('bursar');

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tariro',
            'last_name' => 'Chikore',
            'admission_number' => 'ADM-2026-700',
            'grade' => 'Form 1',
            'class_name' => 'Form 1A',
            'status' => 'active',
        ]);

        $this->accountingService = app(AccountingService::class);
    }

    public function test_ensure_chart_of_accounts_creates_standard_accounts_for_school(): void
    {
        $this->accountingService->ensureChartOfAccountsExist($this->school);

        $this->assertTrue(Account::where('school_id', $this->school->id)->where('code', '1000')->exists());
        $this->assertTrue(Account::where('school_id', $this->school->id)->where('code', '1201')->exists()); // Student Fees Receivable
        $this->assertTrue(Account::where('school_id', $this->school->id)->where('code', '3100')->exists()); // Accounts Payable
        $this->assertTrue(Account::where('school_id', $this->school->id)->where('code', '5100')->exists()); // Tuition Fees
        $this->assertTrue(Account::where('school_id', $this->school->id)->where('code', '6300')->exists()); // Utilities Expense
    }

    public function test_fee_structure_can_be_linked_to_specific_revenue_account(): void
    {
        $this->accountingService->ensureChartOfAccountsExist($this->school);

        $tuitionAccount = Account::where('school_id', $this->school->id)->where('code', '5100')->firstOrFail();

        $response = $this->actingAs($this->bursar)->post(route('admin.fees.store'), [
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'category' => 'tuition',
            'code' => 'TUITION_F1',
            'label' => 'Form 1 Tuition Fee',
            'amount' => 700.00,
            'revenue_account_id' => $tuitionAccount->id,
        ]);

        $response->assertRedirect(route('admin.fees.index', ['academic_year' => '2026', 'term' => 'Term 1']));

        $feeStructure = FeeStructure::where('school_id', $this->school->id)->where('code', 'TUITION_F1')->first();
        $this->assertNotNull($feeStructure);
        $this->assertEquals($tuitionAccount->id, $feeStructure->revenue_account_id);
    }

    public function test_raising_invoice_posts_to_general_ledger_and_updates_bursar_revenue_streams(): void
    {
        $this->accountingService->ensureChartOfAccountsExist($this->school);

        // 1. Create Invoice of $700.00 for Student
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'number' => 'INV-2026-700',
            'type' => 'fees',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 700.00,
            'balance' => 350.00,
            'issued_at' => now()->toDateString(),
            'status' => 'partial',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Form 1 Tuition Fee Term 1',
            'category' => 'tuition',
            'quantity' => 1,
            'unit_amount' => 700.00,
            'line_total' => 700.00,
        ]);

        // Post to Accounting
        $batch = $this->accountingService->postInvoice($invoice);
        $this->assertNotNull($batch);
        $this->assertEquals('posted', $batch->status);

        // Verify Double Entry
        $receivableAccount = Account::where('school_id', $this->school->id)->where('code', '1201')->firstOrFail();
        $tuitionAccount = Account::where('school_id', $this->school->id)->where('code', '5100')->firstOrFail();

        $debitEntry = $batch->entries()->where('account_id', $receivableAccount->id)->where('entry_type', 'debit')->first();
        $creditEntry = $batch->entries()->where('account_id', $tuitionAccount->id)->where('entry_type', 'credit')->first();

        $this->assertNotNull($debitEntry);
        $this->assertNotNull($creditEntry);
        $this->assertEquals(700.00, (float) $debitEntry->amount);
        $this->assertEquals(700.00, (float) $creditEntry->amount);

        // 2. Test Bursar Dashboard shows $700.00 revenue and $700.00 Tuition Fees under Revenue Streams
        $dashboardResponse = $this->actingAs($this->bursar)->get(route('admin.dashboard.bursar'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('$700.00');
    }

    public function test_creating_bill_posts_to_general_ledger_and_updates_bursar_expense_breakdown(): void
    {
        $this->accountingService->ensureChartOfAccountsExist($this->school);

        $vendor = Vendor::create([
            'school_id' => $this->school->id,
            'name' => 'City Power & Utilities',
            'code' => 'VEND-001',
            'status' => 'active',
        ]);

        $utilitiesAccount = Account::where('school_id', $this->school->id)->where('code', '6300')->firstOrFail();

        $billResponse = $this->actingAs($this->bursar)->post(route('admin.bills.store'), [
            'vendor_id' => $vendor->id,
            'expense_account_id' => $utilitiesAccount->id,
            'bill_number' => 'BILL-2026-001',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'total_amount' => 250.00,
            'description' => 'Electricity consumption for classrooms',
        ]);

        $billResponse->assertRedirect(route('admin.bills.index'));

        $bill = Bill::where('school_id', $this->school->id)->where('bill_number', 'BILL-2026-001')->first();
        $this->assertNotNull($bill);
        $this->assertEquals($utilitiesAccount->id, $bill->expense_account_id);

        // Verify Journal Batch
        $batch = JournalBatch::where('school_id', $this->school->id)
            ->where('source_type', 'bill')
            ->where('source_id', $bill->id)
            ->first();

        $this->assertNotNull($batch);

        $accountsPayable = Account::where('school_id', $this->school->id)->whereIn('code', ['3100', '3101'])->first();
        $debitEntry = $batch->entries()->where('account_id', $utilitiesAccount->id)->where('entry_type', 'debit')->first();
        $creditEntry = $batch->entries()->where('account_id', $accountsPayable->id)->where('entry_type', 'credit')->first();

        $this->assertNotNull($debitEntry);
        $this->assertNotNull($creditEntry);
        $this->assertEquals(250.00, (float) $debitEntry->amount);
        $this->assertEquals(250.00, (float) $creditEntry->amount);

        // Verify Bursar Dashboard shows Expense breakdown with $250.00 Utilities
        $dashboardResponse = $this->actingAs($this->bursar)->get(route('admin.dashboard.bursar'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('$250.00');
    }

    public function test_financial_reports_trial_balance_and_balance_sheet_reflect_ledger_transactions(): void
    {
        $this->accountingService->ensureChartOfAccountsExist($this->school);

        // Raise invoice
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'number' => 'INV-2026-800',
            'type' => 'fees',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 500.00,
            'balance' => 500.00,
            'issued_at' => now()->toDateString(),
            'status' => 'unpaid',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Tuition Fee Term 1',
            'category' => 'tuition',
            'quantity' => 1,
            'unit_amount' => 500.00,
            'line_total' => 500.00,
        ]);

        $this->accountingService->postInvoice($invoice);

        // Trial balance
        $tbResponse = $this->actingAs($this->bursar)->get(route('admin.reports.trial-balance'));
        $tbResponse->assertStatus(200);
        $tbResponse->assertSee('Student Fees Receivable');
        $tbResponse->assertSee('Tuition Fees');

        // Balance sheet
        $bsResponse = $this->actingAs($this->bursar)->get(route('admin.reports.balance-sheet'));
        $bsResponse->assertStatus(200);
        $bsResponse->assertSee('Student Fees Receivable');
    }

    public function test_budget_vs_actual_report_picks_up_actual_revenue_from_general_ledger(): void
    {
        $this->accountingService->ensureChartOfAccountsExist($this->school);

        // 1. Post an Invoice of $700.00 for 2026
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'number' => 'INV-2026-777',
            'type' => 'fees',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 700.00,
            'balance' => 700.00,
            'issued_at' => now()->toDateString(),
            'status' => 'unpaid',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Grade 8 Tuition Fees',
            'category' => 'tuition',
            'quantity' => 1,
            'unit_amount' => 700.00,
            'line_total' => 700.00,
        ]);

        $this->accountingService->postInvoice($invoice);

        // 2. Fetch Budget vs Actual Report for 2026
        $response = $this->actingAs($this->bursar)->get(route('admin.reports.budget-vs-actual', ['fiscal_year' => now()->year]));
        
        $response->assertStatus(200);
        $response->assertSee('$700.00');
        $response->assertSee('Tuition Fees');
        $response->assertSee('5100');
    }

    public function test_budget_vs_actual_with_formal_budget_calculates_variance(): void
    {
        $this->accountingService->ensureChartOfAccountsExist($this->school);

        $tuitionAccount = Account::where('school_id', $this->school->id)->where('code', '5100')->firstOrFail();
        $utilitiesAccount = Account::where('school_id', $this->school->id)->where('code', '6300')->firstOrFail();

        // 1. Create a Budget with lines
        $budget = \App\Models\Budget::create([
            'school_id' => $this->school->id,
            'name' => 'Annual Operating Budget 2026',
            'fiscal_year' => (int) now()->year,
            'budget_type' => 'annual',
            'status' => 'active',
            'created_by' => $this->bursar->id,
        ]);

        $budget->lines()->create([
            'account_id' => $tuitionAccount->id,
            'budgeted_amount' => 1000.00,
        ]);

        $budget->lines()->create([
            'account_id' => $utilitiesAccount->id,
            'budgeted_amount' => 500.00,
        ]);

        // 2. Post $700 Invoice (Revenue) and $250 Bill (Expense)
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'number' => 'INV-2026-999',
            'type' => 'fees',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 700.00,
            'balance' => 700.00,
            'issued_at' => now()->toDateString(),
            'status' => 'unpaid',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Tuition Item',
            'category' => 'tuition',
            'quantity' => 1,
            'unit_amount' => 700.00,
            'line_total' => 700.00,
        ]);

        $this->accountingService->postInvoice($invoice);

        $vendor = Vendor::create([
            'school_id' => $this->school->id,
            'name' => 'Water Authority',
            'code' => 'VEND-WAT',
            'status' => 'active',
        ]);

        $bill = Bill::create([
            'school_id' => $this->school->id,
            'vendor_id' => $vendor->id,
            'expense_account_id' => $utilitiesAccount->id,
            'bill_number' => 'BILL-WAT-01',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'total_amount' => 250.00,
            'paid_amount' => 0,
            'balance' => 250.00,
            'status' => 'pending',
            'created_by' => $this->bursar->id,
        ]);

        $this->accountingService->postBill($bill);

        // 3. View Budget vs Actual Report
        $response = $this->actingAs($this->bursar)->get(route('admin.reports.budget-vs-actual', ['fiscal_year' => now()->year]));
        
        $response->assertStatus(200);
        $response->assertSee('$700.00');
        $response->assertSee('$250.00');
        $response->assertSee('Annual Operating Budget 2026');
    }
}
