<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\KioskProduct;
use App\Models\KioskSale;
use App\Models\KioskSaleItem;
use App\Models\Project;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\Finance\ChartOfAccountsService;
use App\Services\Finance\FeeRevenueAccountResolver;
use App\Services\Finance\ProjectRevenueService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ChartOfAccountsHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $admin;
    protected AccountingService $accountingService;
    protected ChartOfAccountsService $coaService;

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

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
        ]);
        $this->admin->assignRole('school-admin');

        $this->accountingService = app(AccountingService::class);
        $this->coaService = app(ChartOfAccountsService::class);
    }

    /** @test */
    public function it_creates_multi_level_account_hierarchy_with_depth_and_traversal_helpers()
    {
        // 1. Root Class: 5000 REVENUE (Header)
        $root = Account::create([
            'school_id' => $this->school->id,
            'code' => '5000',
            'name' => 'REVENUE',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => null,
            'is_postable' => false,
            'is_active' => true,
        ]);

        // 2. Parent: 5100 Tuition Fees (Postable/Summary)
        $parent = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Tuition Fees',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $root->id,
            'is_postable' => true,
            'is_active' => true,
        ]);

        // 3. Child: 5101 Form 1 Tuition (Detail)
        $child = Account::create([
            'school_id' => $this->school->id,
            'code' => '5101',
            'name' => 'Form 1 Tuition',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $parent->id,
            'is_postable' => true,
            'is_active' => true,
        ]);

        // 4. Subaccount: 5101-A Form 1 Day Scholars
        $subaccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5101-A',
            'name' => 'Form 1 Day Scholars',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $child->id,
            'is_postable' => true,
            'is_active' => true,
        ]);

        // Verify root properties
        $this->assertTrue($root->is_root);
        $this->assertFalse($root->is_leaf);
        $this->assertEquals(0, $root->depth);
        $this->assertFalse($root->is_postable);

        // Verify child / subaccount hierarchy traversal
        $this->assertEquals(3, $subaccount->depth);
        $this->assertTrue($subaccount->is_leaf);
        $this->assertFalse($subaccount->is_root);
        $this->assertTrue($subaccount->isDescendantOf($root));
        $this->assertTrue($subaccount->isDescendantOf($parent));
        $this->assertTrue($subaccount->isDescendantOf($child));

        // Ancestors
        $ancestors = $subaccount->ancestors();
        $this->assertCount(3, $ancestors);
        $this->assertEquals(['5101', '5100', '5000'], $ancestors->pluck('code')->all());

        // Descendants of root
        $descendants = $root->descendants();
        $this->assertCount(3, $descendants);
        $this->assertTrue($descendants->contains('code', '5101-A'));
    }

    /** @test */
    public function it_enforces_code_uniqueness_per_school_while_allowing_duplicates_across_schools()
    {
        Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Tuition Fees',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        // Same school duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Duplicate Tuition',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);
    }

    /** @test */
    public function it_allows_same_account_code_across_different_schools()
    {
        $schoolB = School::create(['name' => 'Other Academy', 'code' => 'OA', 'is_active' => true]);

        $accA = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'School A Tuition',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $accB = Account::create([
            'school_id' => $schoolB->id,
            'code' => '5100',
            'name' => 'School B Tuition',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->assertEquals($accA->code, $accB->code);
        $this->assertNotEquals($accA->school_id, $accB->school_id);
    }

    /** @test */
    public function it_prevents_self_parenting()
    {
        $account = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Tuition Fees',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Account cannot be its own parent.");

        $account->validateNoHierarchyCycle($account->id);
    }

    /** @test */
    public function it_prevents_circular_hierarchy_assignment()
    {
        $parent = Account::create([
            'school_id' => $this->school->id,
            'code' => '5000',
            'name' => 'Parent Account',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => false,
        ]);

        $child = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Child Account',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $parent->id,
            'is_active' => true,
            'is_postable' => true,
        ]);

        $grandchild = Account::create([
            'school_id' => $this->school->id,
            'code' => '5101',
            'name' => 'Grandchild Account',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $child->id,
            'is_active' => true,
            'is_postable' => true,
        ]);

        // Attempting to set parent's parent to grandchild should fail
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Circular hierarchy detected");

        $parent->validateNoHierarchyCycle($grandchild->id);
    }

    /** @test */
    public function it_prevents_cross_tenant_parent_assignment()
    {
        $schoolB = School::create(['name' => 'Foreign School B', 'code' => 'FSB', 'is_active' => true]);

        $foreignParent = Account::create([
            'school_id' => $schoolB->id,
            'code' => '1000',
            'name' => 'Foreign Assets',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
            'is_postable' => false,
        ]);

        $myAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1100',
            'name' => 'My Cash',
            'type' => 'asset',
            'category' => 'cash',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Parent account must belong to the same school tenant.");

        $myAccount->validateNoHierarchyCycle($foreignParent->id);
    }

    /** @test */
    public function it_blocks_journal_posting_to_non_postable_header_accounts()
    {
        $headerAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5000',
            'name' => 'REVENUE HEADER',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => false,
        ]);

        $bankAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1101',
            'name' => 'Bank Account',
            'type' => 'asset',
            'category' => 'bank',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot post journal entry to non-postable header account: 5000 - REVENUE HEADER.");

        $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Test Header Posting',
            'status' => 'posted',
            'entries' => [
                ['account_id' => $bankAccount->id, 'entry_type' => 'debit', 'amount' => 100],
                ['account_id' => $headerAccount->id, 'entry_type' => 'credit', 'amount' => 100],
            ],
        ]);
    }

    /** @test */
    public function it_blocks_journal_posting_to_deactivated_accounts()
    {
        $inactiveAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5199',
            'name' => 'Old Inactive Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => false,
            'is_postable' => true,
        ]);

        $bankAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1101',
            'name' => 'Bank Account',
            'type' => 'asset',
            'category' => 'bank',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot post journal entry to deactivated account: 5199 - Old Inactive Revenue.");

        $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Test Inactive Posting',
            'status' => 'posted',
            'entries' => [
                ['account_id' => $bankAccount->id, 'entry_type' => 'debit', 'amount' => 100],
                ['account_id' => $inactiveAccount->id, 'entry_type' => 'credit', 'amount' => 100],
            ],
        ]);
    }

    /** @test */
    public function it_blocks_cross_tenant_journal_entry_posting()
    {
        $schoolB = School::create(['name' => 'Foreign School C', 'code' => 'FSC', 'is_active' => true]);

        $foreignAccount = Account::create([
            'school_id' => $schoolB->id,
            'code' => '5100',
            'name' => 'Foreign Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $bankAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1101',
            'name' => 'Bank Account',
            'type' => 'asset',
            'category' => 'bank',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cross-tenant violation: Account 5100 does not belong to school {$this->school->id}.");

        $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Cross tenant test',
            'status' => 'posted',
            'entries' => [
                ['account_id' => $bankAccount->id, 'entry_type' => 'debit', 'amount' => 100],
                ['account_id' => $foreignAccount->id, 'entry_type' => 'credit', 'amount' => 100],
            ],
        ]);
    }

    /** @test */
    public function it_resolves_custom_revenue_account_on_fee_structure_when_specified()
    {
        $customAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5105',
            'name' => 'Special Science Lab Levy Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $feeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 3',
            'category' => 'levy',
            'code' => 'SCI-LAB-01',
            'label' => 'Science Practical Lab Levy',
            'amount' => 45.00,
            'revenue_account_id' => $customAccount->id,
        ]);

        $item = new InvoiceItem([
            'fee_structure_id' => $feeStructure->id,
            'category' => 'levy',
            'description' => 'Science Practical Lab Levy',
            'amount' => 45.00,
        ]);
        $item->setRelation('feeStructure', $feeStructure);

        $resolver = app(FeeRevenueAccountResolver::class);
        $resolved = $resolver->resolveAccountForItem($this->school, $item);

        $this->assertEquals($customAccount->id, $resolved->id);
        $this->assertEquals('5105', $resolved->code);
    }

    /** @test */
    public function it_falls_back_to_category_default_when_fee_structure_has_no_custom_revenue_account()
    {
        $feeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'category' => 'boarding',
            'code' => 'BOARD-01',
            'label' => 'Boarding Accommodation Term 1',
            'amount' => 300.00,
            'revenue_account_id' => null,
        ]);

        $item = new InvoiceItem([
            'fee_structure_id' => $feeStructure->id,
            'category' => 'boarding',
            'description' => 'Boarding Accommodation Term 1',
            'amount' => 300.00,
        ]);
        $item->setRelation('feeStructure', $feeStructure);

        $resolver = app(FeeRevenueAccountResolver::class);
        $resolved = $resolver->resolveAccountForItem($this->school, $item);

        $this->assertEquals('5200', $resolved->code);
        $this->assertEquals('Boarding Fees', $resolved->name);
    }

    /** @test */
    public function it_uses_custom_revenue_account_for_commercial_projects()
    {
        $customProjectRevenueAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5810',
            'name' => 'Poultry Project Commercial Income',
            'type' => 'revenue',
            'category' => 'other_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $cashAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1102',
            'name' => 'Petty Cash',
            'type' => 'asset',
            'category' => 'cash',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $project = Project::create([
            'school_id' => $this->school->id,
            'code' => 'PRJ-BROILER-26',
            'name' => 'Broiler Chicken Batch A',
            'budget_amount' => 2000,
            'status' => 'active',
            'project_type' => 'commercial',
            'revenue_account_id' => $customProjectRevenueAccount->id,
        ]);

        $projectRevenueService = app(ProjectRevenueService::class);
        $receipt = $projectRevenueService->recordProjectIncome($project, [
            'amount' => 450.00,
            'payment_method' => 'cash',
            'payer_name' => 'Local Supermarket',
            'description' => 'Sale of 90 dressed broilers',
        ]);

        $this->assertNotNull($receipt);

        $batch = JournalBatch::where('school_id', $this->school->id)
            ->where('source_type', 'receipt')
            ->where('source_id', $receipt->id)
            ->first();

        $this->assertNotNull($batch);
        $creditEntry = $batch->entries()->where('entry_type', 'credit')->first();
        $this->assertEquals($customProjectRevenueAccount->id, $creditEntry->account_id);
    }

    /** @test */
    public function it_posts_kiosk_sale_to_custom_product_revenue_account()
    {
        $customSnackRevenueAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5806',
            'name' => 'Bakery & Snack Sales Revenue',
            'type' => 'revenue',
            'category' => 'other_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $cashAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1102',
            'name' => 'Petty Cash',
            'type' => 'asset',
            'category' => 'cash',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $product = KioskProduct::create([
            'school_id' => $this->school->id,
            'code' => 'PIE-BEEF',
            'name' => 'Beef Pie Deluxe',
            'category' => 'snacks',
            'unit_price' => 2.00,
            'stock_quantity' => 50,
            'is_active' => true,
            'revenue_account_id' => $customSnackRevenueAccount->id,
        ]);

        $sale = KioskSale::create([
            'school_id' => $this->school->id,
            'receipt_number' => 'KS-TEST-001',
            'customer_type' => 'guest',
            'subtotal' => 10.00,
            'tax_total' => 0.00,
            'grand_total' => 10.00,
            'amount_paid' => 10.00,
            'change_given' => 0.00,
            'payment_method' => 'cash',
            'status' => 'completed',
            'sale_date' => now()->toDateString(),
        ]);

        KioskSaleItem::create([
            'kiosk_sale_id' => $sale->id,
            'kiosk_product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 2.00,
            'line_total' => 10.00,
        ]);

        $batch = $this->accountingService->postKioskSale($sale);

        $this->assertNotNull($batch);
        $creditEntry = $batch->entries()->where('entry_type', 'credit')->first();
        $this->assertEquals($customSnackRevenueAccount->id, $creditEntry->account_id);
    }

    /** @test */
    public function it_prevents_deleting_account_with_journal_entry_history()
    {
        $account = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Tuition Fees',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $cashAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1102',
            'name' => 'Petty Cash',
            'type' => 'asset',
            'category' => 'cash',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Payment',
            'status' => 'posted',
            'entries' => [
                ['account_id' => $cashAccount->id, 'entry_type' => 'debit', 'amount' => 50],
                ['account_id' => $account->id, 'entry_type' => 'credit', 'amount' => 50],
            ],
        ]);

        $this->assertFalse($account->canBeDeleted());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot delete account [5100] because it contains historical general ledger transactions.");

        $this->coaService->deleteAccount($account);
    }

    /** @test */
    public function it_prevents_deleting_account_with_child_subaccounts()
    {
        $parent = Account::create([
            'school_id' => $this->school->id,
            'code' => '5000',
            'name' => 'Revenue Header',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => false,
        ]);

        $child = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Tuition Fees',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $parent->id,
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->assertFalse($parent->canBeDeleted());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot delete account [5000] because it has child subaccounts.");

        $this->coaService->deleteAccount($parent);
    }

    /** @test */
    public function it_calculates_recursive_tree_balance_rollup_accurately()
    {
        $root = Account::create([
            'school_id' => $this->school->id,
            'code' => '6000',
            'name' => 'TOTAL EXPENSES',
            'type' => 'expense',
            'category' => 'other_expense',
            'is_active' => true,
            'is_postable' => false,
        ]);

        $subGroup = Account::create([
            'school_id' => $this->school->id,
            'code' => '6100',
            'name' => 'Salaries & Benefits',
            'type' => 'expense',
            'category' => 'salary_expense',
            'parent_id' => $root->id,
            'is_active' => true,
            'is_postable' => false,
        ]);

        $teachingSalaries = Account::create([
            'school_id' => $this->school->id,
            'code' => '6101',
            'name' => 'Teaching Staff Salaries',
            'type' => 'expense',
            'category' => 'salary_expense',
            'parent_id' => $subGroup->id,
            'is_active' => true,
            'is_postable' => true,
        ]);

        $adminSalaries = Account::create([
            'school_id' => $this->school->id,
            'code' => '6102',
            'name' => 'Administrative Staff Salaries',
            'type' => 'expense',
            'category' => 'salary_expense',
            'parent_id' => $subGroup->id,
            'is_active' => true,
            'is_postable' => true,
        ]);

        $bank = Account::create([
            'school_id' => $this->school->id,
            'code' => '1301',
            'name' => 'Bank Account',
            'type' => 'asset',
            'category' => 'bank',
            'is_active' => true,
            'is_postable' => true,
        ]);

        // Post expenses
        $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Teaching payroll',
            'status' => 'posted',
            'entries' => [
                ['account_id' => $teachingSalaries->id, 'entry_type' => 'debit', 'amount' => 1500.00],
                ['account_id' => $bank->id, 'entry_type' => 'credit', 'amount' => 1500.00],
            ],
        ]);

        $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Admin payroll',
            'status' => 'posted',
            'entries' => [
                ['account_id' => $adminSalaries->id, 'entry_type' => 'debit', 'amount' => 800.00],
                ['account_id' => $bank->id, 'entry_type' => 'credit', 'amount' => 800.00],
            ],
        ]);

        // Verify individual balances
        $this->assertEquals(1500.00, $teachingSalaries->fresh()->current_balance);
        $this->assertEquals(800.00, $adminSalaries->fresh()->current_balance);
        $this->assertEquals(0.00, $subGroup->fresh()->current_balance);
        $this->assertEquals(0.00, $root->fresh()->current_balance);

        // Verify tree rollup balances
        $this->assertEquals(2300.00, $subGroup->fresh()->tree_balance);
        $this->assertEquals(2300.00, $root->fresh()->tree_balance);
    }

    /** @test */
    public function it_renders_chart_of_accounts_tree_view_and_table_view_for_authenticated_admin()
    {
        (new ChartOfAccountsSeeder())->seedAccountsForSchool($this->school);

        // Tree View
        $response = $this->actingAs($this->admin)->get(route('admin.accounts.index', ['view' => 'tree']));
        $response->assertStatus(200);
        $response->assertSee('Chart of Accounts');
        $response->assertSee('Tree View');
        $response->assertSee('1000');
        $response->assertSee('5000');
        $response->assertSee('5100');

        // Table View
        $tableResponse = $this->actingAs($this->admin)->get(route('admin.accounts.index', ['view' => 'table']));
        $tableResponse->assertStatus(200);
        $tableResponse->assertSee('Table View');
    }

    /** @test */
    public function it_creates_a_new_subaccount_via_admin_controller_with_parent_preselected()
    {
        $parent = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Tuition Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.accounts.store'), [
            'code' => '5101',
            'name' => 'Form 1 Tuition Term 1',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $parent->id,
            'is_postable' => 1,
            'is_active' => 1,
            'opening_balance' => 0,
        ]);

        $response->assertRedirect(route('admin.accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'school_id' => $this->school->id,
            'code' => '5101',
            'name' => 'Form 1 Tuition Term 1',
            'parent_id' => $parent->id,
            'is_postable' => 1,
        ]);
    }

    /** @test */
    public function it_filters_accounts_by_type_postable_and_active_status()
    {
        Account::create([
            'school_id' => $this->school->id,
            'code' => '1000',
            'name' => 'Assets Header',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
            'is_postable' => false,
        ]);

        Account::create([
            'school_id' => $this->school->id,
            'code' => '1101',
            'name' => 'Cash on Hand',
            'type' => 'asset',
            'category' => 'cash',
            'is_active' => true,
            'is_postable' => true,
        ]);

        Account::create([
            'school_id' => $this->school->id,
            'code' => '5000',
            'name' => 'Revenue Header',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => false,
        ]);

        $res = $this->coaService->getAccountTree($this->school, [
            'type' => 'asset',
            'is_postable' => '1',
        ]);

        $this->assertTrue($res->contains('code', '1101'));
        $this->assertFalse($res->contains('code', '1000'));
        $this->assertFalse($res->contains('code', '5000'));
    }

    /** @test */
    public function it_searches_accounts_by_code_and_name()
    {
        Account::create([
            'school_id' => $this->school->id,
            'code' => '1201',
            'name' => 'Student Fees Receivable',
            'type' => 'asset',
            'category' => 'receivable',
            'is_active' => true,
            'is_postable' => true,
        ]);

        Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Tuition Fees',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $res = $this->coaService->getAccountTree($this->school, ['search' => 'Receivable']);
        $this->assertTrue($res->contains('code', '1201'));
        $this->assertFalse($res->contains('code', '5100'));
    }

    /** @test */
    public function it_allows_safe_deletion_of_leaf_account_without_journal_history()
    {
        $account = Account::create([
            'school_id' => $this->school->id,
            'code' => '5999',
            'name' => 'Temporary Test Account',
            'type' => 'revenue',
            'category' => 'other_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->assertTrue($account->canBeDeleted());

        $response = $this->actingAs($this->admin)->delete(route('admin.accounts.destroy', $account));
        $response->assertRedirect(route('admin.accounts.index'));
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    /** @test */
    public function it_handles_kiosk_sales_with_mixed_products_correctly_grouping_credits_to_respective_revenue_accounts()
    {
        $snackAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5806',
            'name' => 'Snack Sales',
            'type' => 'revenue',
            'category' => 'other_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $drinksAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5807',
            'name' => 'Cold Beverages',
            'type' => 'revenue',
            'category' => 'other_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $cashAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1102',
            'name' => 'Petty Cash',
            'type' => 'asset',
            'category' => 'cash',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $pie = KioskProduct::create([
            'school_id' => $this->school->id,
            'code' => 'SNK-01',
            'name' => 'Pie',
            'category' => 'food',
            'unit_price' => 2.00,
            'stock_quantity' => 10,
            'is_active' => true,
            'revenue_account_id' => $snackAccount->id,
        ]);

        $juice = KioskProduct::create([
            'school_id' => $this->school->id,
            'code' => 'DRK-01',
            'name' => 'Juice',
            'category' => 'drinks',
            'unit_price' => 1.50,
            'stock_quantity' => 10,
            'is_active' => true,
            'revenue_account_id' => $drinksAccount->id,
        ]);

        $sale = KioskSale::create([
            'school_id' => $this->school->id,
            'receipt_number' => 'KS-MIX-01',
            'customer_type' => 'guest',
            'subtotal' => 5.50,
            'tax_total' => 0.00,
            'grand_total' => 5.50,
            'amount_paid' => 5.50,
            'change_given' => 0.00,
            'payment_method' => 'cash',
            'status' => 'completed',
            'sale_date' => now()->toDateString(),
        ]);

        KioskSaleItem::create([
            'kiosk_sale_id' => $sale->id,
            'kiosk_product_id' => $pie->id,
            'quantity' => 2,
            'unit_price' => 2.00,
            'line_total' => 4.00,
        ]);

        KioskSaleItem::create([
            'kiosk_sale_id' => $sale->id,
            'kiosk_product_id' => $juice->id,
            'quantity' => 1,
            'unit_price' => 1.50,
            'line_total' => 1.50,
        ]);

        $batch = $this->accountingService->postKioskSale($sale);

        $snackCredit = $batch->entries()->where('account_id', $snackAccount->id)->first();
        $drinkCredit = $batch->entries()->where('account_id', $drinksAccount->id)->first();
        $cashDebit = $batch->entries()->where('account_id', $cashAccount->id)->first();

        $this->assertEquals(4.00, $snackCredit->amount);
        $this->assertEquals(1.50, $drinkCredit->amount);
        $this->assertEquals(5.50, $cashDebit->amount);
    }

    /** @test */
    public function it_prevents_unauthorized_tenant_from_editing_or_deleting_other_school_accounts()
    {
        $schoolB = School::create(['name' => 'Foreign High', 'code' => 'FH', 'is_active' => true]);
        $foreignAccount = Account::create([
            'school_id' => $schoolB->id,
            'code' => '5100',
            'name' => 'Foreign Tuition',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        // Trying to edit foreign account should be rejected (403 Forbidden or 404 Not Found via TenantScope)
        $responseEdit = $this->actingAs($this->admin)->get(route('admin.accounts.edit', $foreignAccount));
        $this->assertTrue(in_array($responseEdit->status(), [403, 404]));

        // Trying to delete foreign account should be rejected (403 Forbidden or 404 Not Found via TenantScope)
        $responseDelete = $this->actingAs($this->admin)->delete(route('admin.accounts.destroy', $foreignAccount));
        $this->assertTrue(in_array($responseDelete->status(), [403, 404]));
    }

    /** @test */
    public function it_safely_updates_account_and_validates_cycles_on_edit_submission()
    {
        $parent = Account::create([
            'school_id' => $this->school->id,
            'code' => '5000',
            'name' => 'Main Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => false,
        ]);

        $child = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Child Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $parent->id,
            'is_active' => true,
            'is_postable' => true,
        ]);

        // Attempt to make parent's parent = child
        $response = $this->actingAs($this->admin)->put(route('admin.accounts.update', $parent), [
            'code' => '5000',
            'name' => 'Main Revenue Updated',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'parent_id' => $child->id,
        ]);

        $response->assertSessionHasErrors('error');
    }
}

