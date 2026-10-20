<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStockTake;
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

class InventoryStockTakeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_approved_stock_take_posts_quantity_variances_and_inventory_adjustment_journal(): void
    {
        [$school, $item, $warehouse] = $this->fixture();
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $headmaster = $this->userWithRole($school, 'headmaster');
        $otherItem = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $item->inventory_category_id,
            'code' => 'FLOUR-001',
            'name' => 'Flour',
            'item_type' => 'consumable',
            'unit_of_measure' => 'bag',
            'is_active' => true,
        ]);
        $positiveBalance = $this->balance($school, $item, $warehouse, 8, 2.75);
        $negativeBalance = $this->balance($school, $otherItem, $warehouse, 4, 1.5);

        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.stock-takes.create', ['warehouse_id' => $warehouse->id]))
            ->assertOk()
            ->assertSee('Physical count');

        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.stock-takes.store'), [
                'inventory_warehouse_id' => $warehouse->id,
                'stock_take_date' => now()->toDateString(),
                'counts' => [
                    $item->id => 9,
                    $otherItem->id => 2,
                ],
                'system_quantities' => [
                    $item->id => 8,
                    $otherItem->id => 4,
                ],
            ])
            ->assertRedirect();
        $stockTake = InventoryStockTake::with('items')->firstOrFail();
        $this->assertSame('pending_review', $stockTake->status);
        $this->assertEquals(8, (float) $stockTake->items->firstWhere('inventory_item_id', $item->id)->system_quantity);

        $this->actingAs($headmaster)
            ->post(route('admin.inventory.stock-takes.approve', $stockTake))
            ->assertRedirect();

        $this->assertSame('posted', $stockTake->fresh()->status);
        $this->assertEquals(9, (float) $positiveBalance->fresh()->quantity_on_hand);
        $this->assertEquals(2, (float) $negativeBalance->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_transactions', [
            'reference_type' => InventoryStockTake::class,
            'reference_id' => $stockTake->id,
            'inventory_item_id' => $item->id,
            'movement_type' => 'adjustment',
            'quantity_delta' => 1,
            'quantity_after' => 9,
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'reference_type' => InventoryStockTake::class,
            'reference_id' => $stockTake->id,
            'inventory_item_id' => $otherItem->id,
            'movement_type' => 'adjustment',
            'quantity_delta' => -2,
            'quantity_after' => 2,
        ]);

        $journal = JournalBatch::where('source_type', 'inventory_stock_take')
            ->where('source_id', $stockTake->id)
            ->firstOrFail();
        $this->assertSame('posted', $journal->status);
        $inventoryAccount = Account::where('school_id', $school->id)->where('code', '1505')->firstOrFail();
        $varianceAccount = Account::where('school_id', $school->id)->where('code', '6595')->firstOrFail();
        $this->assertEquals(2.75, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $inventoryAccount->id)
            ->where('entry_type', 'debit')
            ->sum('amount'));
        $this->assertEquals(3.0, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $varianceAccount->id)
            ->where('entry_type', 'debit')
            ->sum('amount'));
        $this->assertEquals(3.0, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $inventoryAccount->id)
            ->where('entry_type', 'credit')
            ->sum('amount'));
        $this->assertEquals(2.75, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $varianceAccount->id)
            ->where('entry_type', 'credit')
            ->sum('amount'));
    }

    public function test_stock_take_cannot_post_against_a_changed_stock_balance(): void
    {
        [$school, $item, $warehouse] = $this->fixture();
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $headmaster = $this->userWithRole($school, 'headmaster');
        $balance = $this->balance($school, $item, $warehouse, 5, 4);

        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.stock-takes.store'), [
                'inventory_warehouse_id' => $warehouse->id,
                'stock_take_date' => now()->toDateString(),
                'counts' => [$item->id => 5],
                'system_quantities' => [$item->id => 5],
            ])
            ->assertRedirect();
        $stockTake = InventoryStockTake::firstOrFail();
        $balance->update(['quantity_on_hand' => 4]);

        $this->actingAs($headmaster)
            ->from(route('admin.inventory.stock-takes.show', $stockTake))
            ->post(route('admin.inventory.stock-takes.approve', $stockTake))
            ->assertSessionHasErrors('stock_take');

        $this->assertSame('pending_review', $stockTake->fresh()->status);
        $this->assertSame(0, InventoryTransaction::count());
        $this->assertSame(0, JournalBatch::where('source_type', 'inventory_stock_take')->count());
    }

    public function test_stores_person_can_manage_reorder_levels_without_changing_on_hand_stock(): void
    {
        [$school, $item, $warehouse] = $this->fixture();
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $balance = $this->balance($school, $item, $warehouse, 3, 1.25);

        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.stock-levels', ['warehouse_id' => $warehouse->id]))
            ->assertOk()
            ->assertSee('Stock on hand and reorder levels')
            ->assertSee('Mealie Meal');

        $this->actingAs($storesPerson)
            ->put(route('admin.inventory.stock-levels.update'), [
                'inventory_warehouse_id' => $warehouse->id,
                'levels' => [
                    $item->id => ['reorder_level' => 5, 'reorder_quantity' => 10],
                ],
            ])
            ->assertRedirect();

        $this->assertEquals(3, (float) $balance->fresh()->quantity_on_hand);
        $this->assertEquals(5, (float) $balance->fresh()->reorder_level);
        $this->assertEquals(10, (float) $balance->fresh()->reorder_quantity);
        $this->assertSame(0, InventoryTransaction::count());
    }

    public function test_stock_take_approval_is_separated_from_counting_and_reorder_management(): void
    {
        [$school, $item, $warehouse] = $this->fixture();
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $teacher = $this->userWithRole($school, 'teacher');
        $this->balance($school, $item, $warehouse, 2, 3);

        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.stock-takes.store'), [
                'inventory_warehouse_id' => $warehouse->id,
                'stock_take_date' => now()->toDateString(),
                'counts' => [$item->id => 2],
                'system_quantities' => [$item->id => 2],
            ])
            ->assertRedirect();
        $stockTake = InventoryStockTake::firstOrFail();

        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.stock-takes.approve', $stockTake))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->get(route('admin.inventory.stock-takes.index'))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->get(route('admin.inventory.stock-levels'))
            ->assertForbidden();

        $this->assertSame('pending_review', $stockTake->fresh()->status);
    }

    public function test_stock_take_submission_is_rejected_if_stock_changes_while_the_count_form_is_open(): void
    {
        [$school, $item, $warehouse] = $this->fixture();
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $balance = $this->balance($school, $item, $warehouse, 5, 2);

        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.stock-takes.create', ['warehouse_id' => $warehouse->id]))
            ->assertOk();
        $balance->update(['quantity_on_hand' => 6]);

        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.stock-takes.store'), [
                'inventory_warehouse_id' => $warehouse->id,
                'stock_take_date' => now()->toDateString(),
                'counts' => [$item->id => 5],
                'system_quantities' => [$item->id => 5],
            ])
            ->assertSessionHasErrors('system_quantities.'.$item->id);

        $this->assertSame(0, InventoryStockTake::count());
    }

    public function test_returned_stock_take_can_be_resubmitted_with_review_history_preserved(): void
    {
        [$school, $item, $warehouse] = $this->fixture();
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $headmaster = $this->userWithRole($school, 'headmaster');

        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.stock-takes.store'), [
                'inventory_warehouse_id' => $warehouse->id,
                'stock_take_date' => now()->toDateString(),
                'counts' => [$item->id => 0],
                'system_quantities' => [$item->id => 0],
            ])
            ->assertRedirect();
        $stockTake = InventoryStockTake::firstOrFail();

        $this->actingAs($headmaster)
            ->post(route('admin.inventory.stock-takes.return', $stockTake), [
                'review_notes' => 'Please recount the store room.',
            ])
            ->assertRedirect();
        $this->assertSame('returned', $stockTake->fresh()->status);

        $this->actingAs($storesPerson)
            ->put(route('admin.inventory.stock-takes.update', $stockTake), [
                'stock_take_date' => now()->toDateString(),
                'counts' => [$item->id => 0],
                'system_quantities' => [$item->id => 0],
            ])
            ->assertRedirect();

        $this->assertSame('pending_review', $stockTake->fresh()->status);
        $this->assertSame('Please recount the store room.', $stockTake->fresh()->review_notes);
        $this->assertDatabaseHas('inventory_stock_take_reviews', [
            'inventory_stock_take_id' => $stockTake->id,
            'decision' => 'returned',
            'comments' => 'Please recount the store room.',
        ]);
        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.stock-takes.show', $stockTake))
            ->assertOk()
            ->assertSee('Review history')
            ->assertSee('Please recount the store room.');
    }

    private function fixture(): array
    {
        $school = School::create(['name' => 'Stock Take School', 'code' => 'STK']);
        app(ChartOfAccountsSeeder::class)->seedAccountsForSchool($school);
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

        return [$school, $item, $warehouse];
    }

    private function balance(School $school, InventoryItem $item, InventoryWarehouse $warehouse, float $quantity, float $unitCost): InventoryBalance
    {
        return InventoryBalance::create([
            'school_id' => $school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $warehouse->id,
            'quantity_on_hand' => $quantity,
            'average_unit_cost' => $unitCost,
        ]);
    }

    private function userWithRole(School $school, string $role): User
    {
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
