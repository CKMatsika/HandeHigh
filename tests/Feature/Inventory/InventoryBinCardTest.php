<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryBinCardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $storesPerson;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->school = School::create(['name' => 'St Marks High School', 'code' => 'SMHS']);
        $this->storesPerson = User::factory()->create(['school_id' => $this->school->id]);
        $this->storesPerson->assignRole('stores-person');
    }

    public function test_stores_person_can_view_item_bincard_with_stock_balance_and_movements(): void
    {
        $warehouse = InventoryWarehouse::create([
            'school_id' => $this->school->id,
            'name' => 'Main Stationery Store',
            'code' => 'MSS-01',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create([
            'school_id' => $this->school->id,
            'name' => 'Classroom Supplies',
            'code' => 'CLS',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $this->school->id,
            'inventory_category_id' => $category->id,
            'code' => 'CLS-CHALK',
            'name' => 'White Dustless Chalk 100pk',
            'item_type' => 'consumable',
            'unit_of_measure' => 'box',
            'is_active' => true,
        ]);

        InventoryBalance::create([
            'school_id' => $this->school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $warehouse->id,
            'quantity_on_hand' => 80.00,
            'average_unit_cost' => 4.50,
        ]);

        // Transaction 1: Receipt
        InventoryTransaction::create([
            'school_id' => $this->school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $warehouse->id,
            'movement_type' => 'goods_received',
            'quantity_delta' => 100.00,
            'unit_cost' => 4.50,
            'quantity_after' => 100.00,
            'average_unit_cost_after' => 4.50,
            'notes' => 'Received from supplier GRV-1001',
            'recorded_by' => $this->storesPerson->id,
            'occurred_at' => now()->subDays(2),
        ]);

        // Transaction 2: Issue to Grade 7
        InventoryTransaction::create([
            'school_id' => $this->school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $warehouse->id,
            'movement_type' => 'issue',
            'quantity_delta' => -20.00,
            'unit_cost' => 4.50,
            'quantity_after' => 80.00,
            'average_unit_cost_after' => 4.50,
            'notes' => 'Issued to Grade 7 Department',
            'recorded_by' => $this->storesPerson->id,
            'occurred_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->storesPerson)
            ->get(route('admin.inventory.items.bincard', $item));

        $response->assertOk();
        $response->assertSee('White Dustless Chalk 100pk');
        $response->assertSee('CLS-CHALK');
        $response->assertSee('Classroom Supplies');
        $response->assertSee('80.00'); // Current stock on hand
        $response->assertSee('4.50');  // Average cost
        $response->assertSee('Main Stationery Store');
        $response->assertSee('Received from supplier GRV-1001');
        $response->assertSee('Issued to Grade 7 Department');
        $response->assertSee('+100.00');
        $response->assertSee('20.00');
        $response->assertSee('Print Bin Card');
    }

    public function test_bincard_warehouse_and_date_filtering(): void
    {
        $wh1 = InventoryWarehouse::create([
            'school_id' => $this->school->id,
            'name' => 'North Campus Stores',
            'code' => 'NC-01',
            'is_active' => true,
        ]);

        $wh2 = InventoryWarehouse::create([
            'school_id' => $this->school->id,
            'name' => 'South Campus Stores',
            'code' => 'SC-01',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create([
            'school_id' => $this->school->id,
            'name' => 'Sports',
            'code' => 'SPT',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $this->school->id,
            'inventory_category_id' => $category->id,
            'code' => 'SPT-BALL',
            'name' => 'Size 5 Soccer Ball',
            'item_type' => 'consumable',
            'unit_of_measure' => 'piece',
            'is_active' => true,
        ]);

        InventoryBalance::create([
            'school_id' => $this->school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $wh1->id,
            'quantity_on_hand' => 15.00,
            'average_unit_cost' => 20.00,
        ]);

        InventoryBalance::create([
            'school_id' => $this->school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $wh2->id,
            'quantity_on_hand' => 10.00,
            'average_unit_cost' => 20.00,
        ]);

        // North Campus Transaction on 10 Oct
        InventoryTransaction::create([
            'school_id' => $this->school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $wh1->id,
            'movement_type' => 'goods_received',
            'quantity_delta' => 15.00,
            'unit_cost' => 20.00,
            'quantity_after' => 15.00,
            'average_unit_cost_after' => 20.00,
            'notes' => 'Batch North Campus',
            'recorded_by' => $this->storesPerson->id,
            'occurred_at' => now()->subDays(10),
        ]);

        // South Campus Transaction on 2 Oct
        InventoryTransaction::create([
            'school_id' => $this->school->id,
            'inventory_item_id' => $item->id,
            'inventory_warehouse_id' => $wh2->id,
            'movement_type' => 'goods_received',
            'quantity_delta' => 10.00,
            'unit_cost' => 20.00,
            'quantity_after' => 10.00,
            'average_unit_cost_after' => 20.00,
            'notes' => 'Batch South Campus',
            'recorded_by' => $this->storesPerson->id,
            'occurred_at' => now()->subDays(2),
        ]);

        // Filter by warehouse 1
        $responseWh1 = $this->actingAs($this->storesPerson)
            ->get(route('admin.inventory.items.bincard', [$item, 'warehouse_id' => $wh1->id]));

        $responseWh1->assertOk();
        $responseWh1->assertSee('Batch North Campus');
        $responseWh1->assertDontSee('Batch South Campus');
        $responseWh1->assertSee('15.00');

        // Filter by warehouse 2
        $responseWh2 = $this->actingAs($this->storesPerson)
            ->get(route('admin.inventory.items.bincard', [$item, 'warehouse_id' => $wh2->id]));

        $responseWh2->assertOk();
        $responseWh2->assertSee('Batch South Campus');
        $responseWh2->assertDontSee('Batch North Campus');
        $responseWh2->assertSee('10.00');

        // Filter by date range excluding North Campus
        $responseDate = $this->actingAs($this->storesPerson)
            ->get(route('admin.inventory.items.bincard', [
                $item,
                'start_date' => now()->subDays(3)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $responseDate->assertOk();
        $responseDate->assertSee('Batch South Campus');
        $responseDate->assertDontSee('Batch North Campus');
    }

    public function test_bincard_cross_tenant_isolation(): void
    {
        $schoolForeign = School::create(['name' => 'Foreign School', 'code' => 'FOR']);
        $cat = InventoryCategory::create([
            'school_id' => $schoolForeign->id,
            'name' => 'Other Category',
            'code' => 'OTH',
            'is_active' => true,
        ]);

        $foreignItem = InventoryItem::create([
            'school_id' => $schoolForeign->id,
            'inventory_category_id' => $cat->id,
            'code' => 'FOR-ITEM',
            'name' => 'Foreign Item',
            'item_type' => 'consumable',
            'unit_of_measure' => 'unit',
            'is_active' => true,
        ]);

        $this->actingAs($this->storesPerson)
            ->get(route('admin.inventory.items.bincard', $foreignItem))
            ->assertStatus(404);
    }

    public function test_catalog_index_contains_bincard_link(): void
    {
        $category = InventoryCategory::create([
            'school_id' => $this->school->id,
            'name' => 'General',
            'code' => 'GEN',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $this->school->id,
            'inventory_category_id' => $category->id,
            'code' => 'TEST-ITEM',
            'name' => 'Sample Catalog Item',
            'item_type' => 'consumable',
            'unit_of_measure' => 'box',
            'is_active' => true,
        ]);

        $this->actingAs($this->storesPerson)
            ->get(route('admin.inventory.items.index'))
            ->assertOk()
            ->assertSee('Bin Card')
            ->assertSee(route('admin.inventory.items.bincard', $item));
    }
}
