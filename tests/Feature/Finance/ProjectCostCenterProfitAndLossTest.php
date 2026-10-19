<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Department;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AccountingService;
use App\Services\Finance\FinanceReportingService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCostCenterProfitAndLossTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $bursar;
    private Project $busProject;
    private Department $transportDept;
    private Account $arAccount;
    private Account $apAccount;
    private Account $revenueAccount;
    private Account $expenseAccount;
    private AccountingService $accountingService;
    private FinanceReportingService $reportingService;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->school = School::create(['name' => 'Hande High School', 'code' => 'HHS']);

        $this->bursar = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bursar User',
            'email' => 'bursar@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->bursar->assignRole('bursar');

        // Create Project and Cost Center
        $this->busProject = Project::create([
            'school_id' => $this->school->id,
            'name' => 'Bus Operations',
            'code' => 'BUS-OPS',
            'budget' => 5000.00,
            'status' => 'in_progress',
        ]);

        $this->transportDept = Department::create([
            'school_id' => $this->school->id,
            'name' => 'Transport',
        ]);

        // Create Chart of Accounts
        $this->arAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1201',
            'name' => 'Student Fees Receivable',
            'type' => 'asset',
            'category' => 'receivable',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->apAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '3100',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'category' => 'payable',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->revenueAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5010',
            'name' => 'Bus Transport Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->expenseAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '6020',
            'name' => 'Bus Fuel & Maintenance Expense',
            'type' => 'expense',
            'category' => 'supply_expense',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->accountingService = app(AccountingService::class);
        $this->reportingService = app(FinanceReportingService::class);
    }

    public function test_accounting_service_posts_invoice_with_project_and_cost_center_tags(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-101',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'number' => 'INV-001',
            'type' => 'fees',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 300.00,
            'balance' => 300.00,
            'issued_at' => now()->toDateString(),
            'status' => 'unpaid',
            'project_id' => $this->busProject->id,
            'cost_center_id' => $this->transportDept->id,
        ]);

        $item = InvoiceItem::create([
            'school_id' => $this->school->id,
            'invoice_id' => $invoice->id,
            'description' => 'Bus Term 1 Route A',
            'category' => 'transport',
            'quantity' => 1,
            'unit_amount' => 300.00,
            'line_total' => 300.00,
            'project_id' => $this->busProject->id,
            'cost_center_id' => $this->transportDept->id,
        ]);

        $batch = $this->accountingService->postInvoice($invoice);

        $this->assertNotNull($batch);
        $this->assertEquals('posted', $batch->status);

        // Verify journal entries have project and cost center tags
        $entries = JournalEntry::where('journal_batch_id', $batch->id)->get();
        $this->assertNotEmpty($entries);

        foreach ($entries as $entry) {
            $this->assertEquals($this->busProject->id, $entry->project_id);
            $this->assertEquals($this->transportDept->id, $entry->cost_center_id);
        }
    }

    public function test_accounting_service_posts_bill_with_project_and_cost_center_tags(): void
    {
        $vendor = Vendor::create([
            'school_id' => $this->school->id,
            'code' => 'VEND-001',
            'name' => 'Total Energies Service Station',
            'is_active' => true,
        ]);

        $bill = Bill::create([
            'school_id' => $this->school->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-001',
            'title' => 'Bus Diesel Fuel - Term 1',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal' => 120.00,
            'tax_amount' => 0.00,
            'total_amount' => 120.00,
            'balance_due' => 120.00,
            'status' => 'open',
            'project_id' => $this->busProject->id,
            'cost_center_id' => $this->transportDept->id,
        ]);

        BillItem::create([
            'school_id' => $this->school->id,
            'bill_id' => $bill->id,
            'expense_account_id' => $this->expenseAccount->id,
            'description' => 'Diesel 100L for School Bus',
            'quantity' => 100,
            'unit_price' => 1.20,
            'line_total' => 120.00,
            'project_id' => $this->busProject->id,
            'cost_center_id' => $this->transportDept->id,
        ]);

        $batch = $this->accountingService->postBill($bill);

        $this->assertNotNull($batch);
        $this->assertEquals('posted', $batch->status);

        $entries = JournalEntry::where('journal_batch_id', $batch->id)->get();
        $this->assertNotEmpty($entries);

        foreach ($entries as $entry) {
            $this->assertEquals($this->busProject->id, $entry->project_id);
            $this->assertEquals($this->transportDept->id, $entry->cost_center_id);
        }
    }

    public function test_project_profit_and_loss_report_calculates_net_profit_and_margins(): void
    {
        // 1. Post Revenue: $400
        $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Bus Revenue Test',
            'status' => 'posted',
            'entries' => [
                [
                    'account_id' => $this->arAccount->id,
                    'entry_type' => 'debit',
                    'amount' => 400.00,
                    'project_id' => $this->busProject->id,
                    'cost_center_id' => $this->transportDept->id,
                ],
                [
                    'account_id' => $this->revenueAccount->id,
                    'entry_type' => 'credit',
                    'amount' => 400.00,
                    'project_id' => $this->busProject->id,
                    'cost_center_id' => $this->transportDept->id,
                ],
            ],
        ]);

        // 2. Post Direct Expense: $150
        $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Bus Fuel Expense Test',
            'status' => 'posted',
            'entries' => [
                [
                    'account_id' => $this->expenseAccount->id,
                    'entry_type' => 'debit',
                    'amount' => 150.00,
                    'project_id' => $this->busProject->id,
                    'cost_center_id' => $this->transportDept->id,
                ],
                [
                    'account_id' => $this->apAccount->id,
                    'entry_type' => 'credit',
                    'amount' => 150.00,
                    'project_id' => $this->busProject->id,
                    'cost_center_id' => $this->transportDept->id,
                ],
            ],
        ]);

        $report = $this->reportingService->getProjectProfitAndLoss($this->school, [
            'project_id' => $this->busProject->id,
            'period' => 'this_month',
        ]);

        $this->assertEquals(400.00, $report['total_revenue']);
        $this->assertEquals(150.00, $report['direct_costs']);
        $this->assertEquals(250.00, $report['gross_profit']);
        $this->assertEquals(250.00, $report['net_profit_loss']);
        $this->assertEquals(62.5, $report['gross_margin_percent']);
        $this->assertEquals(62.5, $report['net_margin_percent']);
        $this->assertEquals($this->busProject->name, $report['project']->name);
    }

    public function test_authorized_user_can_access_profit_and_loss_web_route(): void
    {
        $response = $this->actingAs($this->bursar)
            ->get(route('admin.reports.profit-and-loss', [
                'project_id' => $this->busProject->id,
                'period' => 'this_year',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Project &amp; Cost Center Profit &amp; Loss', false);
        $response->assertSee('Bus Operations');
        $response->assertSee('Net Margin');
    }

    public function test_authorized_user_can_export_profit_and_loss_excel(): void
    {
        $response = $this->actingAs($this->bursar)
            ->get(route('admin.reports.profit-and-loss.export', [
                'project_id' => $this->busProject->id,
                'period' => 'this_year',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_sync_command_tags_untagged_transactions_cleanly(): void
    {
        // Create an untagged transport fee structure
        $fee = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'code' => 'BUS-FEE-TEST',
            'label' => 'School Bus Transport Route A',
            'category' => 'transport',
            'amount' => 150.00,
            'project_id' => null,
            'cost_center_id' => null,
        ]);

        $this->artisan('finance:sync-projects-cost-centers', ['--school' => $this->school->id])
            ->assertSuccessful();

        $fee->refresh();
        $this->assertNotNull($fee->project_id);
        $this->assertNotNull($fee->cost_center_id);
        $this->assertEquals('Bus Operations', $fee->project->name);
        $this->assertEquals('Transport', $fee->costCenter->name);
    }
}
