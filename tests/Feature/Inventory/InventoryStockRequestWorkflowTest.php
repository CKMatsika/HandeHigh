<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\Department;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStockIssue;
use App\Models\InventoryStockRequest;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\School;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryStockRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_staff_request_is_approved_and_stores_issue_posts_inventory_and_department_expense(): void
    {
        [$school, $item, $warehouse, $department] = $this->fixture();
        $teacher = $this->userWithRole($school, 'teacher');
        $headmaster = $this->userWithRole($school, 'headmaster');
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $expenseAccount = Account::where('school_id', $school->id)->where('code', '6502')->firstOrFail();
        $item->category->update(['issue_expense_account_id' => $expenseAccount->id]);

        $balance = InventoryBalance::create([
            'school_id' => $school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $warehouse->id,
            'quantity_on_hand' => 8,
            'average_unit_cost' => 2.75,
            'reorder_level' => 2,
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.inventory.stock-requests.create'))
            ->assertOk()
            ->assertSee('New stock request');

        $this->actingAs($teacher)
            ->post(route('admin.inventory.stock-requests.store'), [
                'department_id' => $department->id,
                'needed_by' => now()->addDays(2)->toDateString(),
                'purpose' => 'Meal preparation for the boarding house.',
                'items' => [
                    ['inventory_item_id' => $item->id, 'quantity_requested' => 3, 'notes' => 'Kitchen use'],
                    ['inventory_item_id' => '', 'quantity_requested' => '', 'notes' => ''],
                ],
            ])
            ->assertRedirect();

        $stockRequest = InventoryStockRequest::with('items')->firstOrFail();
        $this->assertSame('pending_review', $stockRequest->status);
        $this->assertSame($teacher->id, $stockRequest->requested_by);

        $this->actingAs($headmaster)
            ->post(route('admin.inventory.stock-requests.approve', $stockRequest))
            ->assertRedirect();
        $this->assertSame('approved', $stockRequest->fresh()->status);
        $this->assertDatabaseHas('inventory_stock_request_reviews', [
            'inventory_stock_request_id' => $stockRequest->id,
            'reviewed_by' => $headmaster->id,
            'decision' => 'approved',
        ]);

        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.stock-requests.issue', $stockRequest), [
                'inventory_warehouse_id' => $warehouse->id,
                'issued_date' => now()->toDateString(),
                'notes' => 'Issued to kitchen staff.',
                'issued_quantities' => [$stockRequest->items->first()->id => 2],
            ])
            ->assertRedirect();

        $this->assertSame('partially_issued', $stockRequest->fresh()->status);
        $this->assertEquals(2, (float) $stockRequest->items()->first()->fresh()->quantity_issued);
        $this->assertEquals(6, (float) $balance->fresh()->quantity_on_hand);
        $issue = InventoryStockIssue::with('items')->firstOrFail();
        $this->assertEquals(5.50, (float) $issue->items->sum('line_total'));
        $this->assertDatabaseHas('inventory_transactions', [
            'reference_type' => InventoryStockIssue::class,
            'reference_id' => $issue->id,
            'movement_type' => 'issue',
            'quantity_delta' => -2,
            'quantity_after' => 6,
        ]);

        $journal = JournalBatch::where('source_type', 'inventory_stock_issue')
            ->where('source_id', $issue->id)
            ->firstOrFail();
        $this->assertSame('posted', $journal->status);
        $this->assertEquals(5.5, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $expenseAccount->id)
            ->where('entry_type', 'debit')
            ->sum('amount'));
        $this->assertEquals($department->id, JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $expenseAccount->id)
            ->value('cost_center_id'));
        $inventoryAccount = Account::where('school_id', $school->id)->where('code', '1505')->firstOrFail();
        $this->assertEquals(5.5, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $inventoryAccount->id)
            ->where('entry_type', 'credit')
            ->sum('amount'));
    }

    public function test_stock_request_cannot_be_issued_above_warehouse_availability(): void
    {
        [$school, $item, $warehouse] = $this->fixture();
        $teacher = $this->userWithRole($school, 'teacher');
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $stockRequest = InventoryStockRequest::create([
            'school_id' => $school->id,
            'request_number' => 'SR-OVER',
            'requested_by' => $teacher->id,
            'purpose' => 'Test stock availability.',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);
        $requestItem = $stockRequest->items()->create([
            'inventory_item_id' => $item->id,
            'quantity_requested' => 4,
            'quantity_issued' => 0,
        ]);
        $balance = InventoryBalance::create([
            'school_id' => $school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $warehouse->id,
            'quantity_on_hand' => 3,
            'average_unit_cost' => 1.5,
        ]);

        $this->actingAs($storesPerson)
            ->from(route('admin.inventory.stock-requests.show', $stockRequest))
            ->post(route('admin.inventory.stock-requests.issue', $stockRequest), [
                'inventory_warehouse_id' => $warehouse->id,
                'issued_date' => now()->toDateString(),
                'issued_quantities' => [$requestItem->id => 4],
            ])
            ->assertSessionHasErrors(['issued_quantities.'.$requestItem->id]);

        $this->assertSame(0, InventoryStockIssue::count());
        $this->assertSame(0, InventoryTransaction::count());
        $this->assertEquals(3, (float) $balance->fresh()->quantity_on_hand);
        $this->assertSame('approved', $stockRequest->fresh()->status);
    }

    public function test_reviewer_can_return_request_and_requester_can_correct_and_resubmit(): void
    {
        [$school, $item] = $this->fixture();
        $teacher = $this->userWithRole($school, 'teacher');
        $headmaster = $this->userWithRole($school, 'headmaster');

        $this->actingAs($teacher)
            ->post(route('admin.inventory.stock-requests.store'), [
                'purpose' => 'Request for laboratory practicals.',
                'items' => [
                    ['inventory_item_id' => $item->id, 'quantity_requested' => 2],
                ],
            ])
            ->assertRedirect();
        $stockRequest = InventoryStockRequest::with('items')->firstOrFail();

        $this->actingAs($headmaster)
            ->post(route('admin.inventory.stock-requests.return', $stockRequest), [
                'review_notes' => 'Please reduce the requested quantity.',
            ])
            ->assertRedirect();
        $this->assertSame('returned', $stockRequest->fresh()->status);
        $this->assertDatabaseHas('inventory_stock_request_reviews', [
            'inventory_stock_request_id' => $stockRequest->id,
            'reviewed_by' => $headmaster->id,
            'decision' => 'returned',
            'comments' => 'Please reduce the requested quantity.',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.inventory.stock-requests.edit', $stockRequest))
            ->assertOk()
            ->assertSee('Correct stock request');
        $this->actingAs($teacher)
            ->put(route('admin.inventory.stock-requests.update', $stockRequest), [
                'purpose' => 'Reduced request for laboratory practicals.',
                'items' => [
                    ['inventory_item_id' => $item->id, 'quantity_requested' => 1],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('pending_review', $stockRequest->fresh()->status);
        $this->assertSame('Please reduce the requested quantity.', $stockRequest->fresh()->review_notes);
        $this->assertEquals(1, (float) $stockRequest->items()->first()->quantity_requested);
        $this->assertSame(1, $stockRequest->reviews()->count());
    }

    private function fixture(): array
    {
        $school = School::create(['name' => 'Stock Issue School', 'code' => 'STK']);
        app(ChartOfAccountsSeeder::class)->seedAccountsForSchool($school);
        $department = Department::create([
            'school_id' => $school->id,
            'name' => 'Kitchen',
            'is_active' => true,
        ]);
        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'code' => 'FOOD',
            'name' => 'Food Supplies',
            'is_active' => true,
        ]);
        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'MEALIE-001',
            'name' => 'Mealie Meal',
            'item_type' => 'consumable',
            'unit_of_measure' => 'bag',
            'is_active' => true,
        ]);
        $warehouse = InventoryWarehouse::create([
            'school_id' => $school->id,
            'code' => 'MAIN',
            'name' => 'Main Store',
            'is_active' => true,
        ]);

        return [$school, $item, $warehouse, $department];
    }

    private function userWithRole(School $school, string $role): User
    {
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
