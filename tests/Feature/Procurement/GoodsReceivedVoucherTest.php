<?php

namespace Tests\Feature\Procurement;

use App\Models\GoodsReceivedVoucher;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\ProcurementRequisition;
use App\Models\PurchaseOrder;
use App\Models\School;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoodsReceivedVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_stores_person_posts_partial_receipts_updates_weighted_average_and_cannot_overreceive(): void
    {
        [$school, $item, $warehouse, $vendor] = $this->inventoryFixture();
        $requester = $this->userWithRole($school, 'teacher');
        $procurementOfficer = $this->userWithRole($school, 'procurement-officer');
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $order = $this->issuedOrder($school, $item, $vendor, $requester, $procurementOfficer, 10, 2.75);

        $this->actingAs($storesPerson)
            ->get(route('admin.procurement.goods-received-vouchers.create', $order))
            ->assertOk()
            ->assertSee('Receive goods');

        $this->actingAs($storesPerson)
            ->post(route('admin.procurement.goods-received-vouchers.store', $order), [
                'inventory_warehouse_id' => $warehouse->id,
                'received_date' => now()->toDateString(),
                'received_quantities' => [$order->items->first()->id => 4],
            ])
            ->assertRedirect();

        $this->assertSame('partially_received', $order->fresh()->status);
        $this->assertSame('partially_received', $order->requisition->fresh()->status);
        $balance = InventoryBalance::where('inventory_item_id', $item->id)->firstOrFail();
        $this->assertEquals(4, (float) $balance->quantity_on_hand);
        $this->assertEquals(2.75, (float) $balance->average_unit_cost);
        $this->assertDatabaseCount('inventory_transactions', 1);

        $this->actingAs($storesPerson)
            ->from(route('admin.procurement.purchase-orders.show', $order))
            ->post(route('admin.procurement.goods-received-vouchers.store', $order), [
                'inventory_warehouse_id' => $warehouse->id,
                'received_date' => now()->toDateString(),
                'received_quantities' => [$order->items->first()->id => 7],
            ])
            ->assertSessionHasErrors(['received_quantities.'.$order->items->first()->id]);

        $this->assertSame(1, GoodsReceivedVoucher::count());
        $this->assertEquals(4, (float) $balance->fresh()->quantity_on_hand);

        $this->actingAs($storesPerson)
            ->post(route('admin.procurement.goods-received-vouchers.store', $order), [
                'inventory_warehouse_id' => $warehouse->id,
                'received_date' => now()->toDateString(),
                'received_quantities' => [$order->items->first()->id => 6],
            ])
            ->assertRedirect();

        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame('received', $order->requisition->fresh()->status);
        $this->assertEquals(10, (float) $balance->fresh()->quantity_on_hand);
        $this->assertSame(2, InventoryTransaction::count());

        $secondOrder = $this->issuedOrder($school, $item, $vendor, $requester, $procurementOfficer, 10, 4.25);
        $this->actingAs($storesPerson)
            ->post(route('admin.procurement.goods-received-vouchers.store', $secondOrder), [
                'inventory_warehouse_id' => $warehouse->id,
                'received_date' => now()->toDateString(),
                'received_quantities' => [$secondOrder->items->first()->id => 10],
            ])
            ->assertRedirect();

        $this->assertEquals(20, (float) $balance->fresh()->quantity_on_hand);
        $this->assertEquals(3.5, (float) $balance->fresh()->average_unit_cost);
        $this->assertSame(3, InventoryTransaction::count());
    }

    public function test_only_authorized_stores_users_can_post_school_scoped_receipts(): void
    {
        [$school, $item, , $vendor] = $this->inventoryFixture();
        $requester = $this->userWithRole($school, 'teacher');
        $procurementOfficer = $this->userWithRole($school, 'procurement-officer');
        $order = $this->issuedOrder($school, $item, $vendor, $requester, $procurementOfficer, 2, 5);

        $this->actingAs($requester)
            ->get(route('admin.procurement.goods-received-vouchers.create', $order))
            ->assertForbidden();

        $otherSchool = School::create(['name' => 'Other Receiving School', 'code' => 'OTHER-GRV']);
        $foreignUser = $this->userWithRole($otherSchool, 'stores-person');
        $this->actingAs($foreignUser)
            ->get(route('admin.procurement.goods-received-vouchers.create', $order))
            ->assertNotFound();

        $this->assertSame(0, GoodsReceivedVoucher::count());
        $this->assertSame(0, InventoryTransaction::count());
    }

    private function inventoryFixture(): array
    {
        $school = School::create(['name' => 'Goods Receipt School', 'code' => 'GRV']);
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
        $vendor = Vendor::create([
            'school_id' => $school->id,
            'code' => 'SUP-001',
            'name' => 'Local Supplier',
            'vendor_type' => 'supplier',
            'payment_terms' => 'net_30',
            'is_active' => true,
        ]);

        return [$school, $item, $warehouse, $vendor];
    }

    private function issuedOrder(
        School $school,
        InventoryItem $item,
        Vendor $vendor,
        User $requester,
        User $procurementOfficer,
        int $quantity,
        float $unitCost
    ): PurchaseOrder {
        $requisition = ProcurementRequisition::create([
            'school_id' => $school->id,
            'requisition_number' => 'REQ-'.fake()->unique()->numerify('######'),
            'requested_by' => $requester->id,
            'purpose' => 'Goods required for the school kitchen.',
            'status' => 'ordered',
        ]);
        $requestLine = $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => $quantity,
        ]);
        $order = PurchaseOrder::create([
            'school_id' => $school->id,
            'procurement_requisition_id' => $requisition->id,
            'vendor_id' => $vendor->id,
            'purchase_order_number' => 'PO-'.fake()->unique()->numerify('######'),
            'status' => 'issued',
            'order_date' => now()->toDateString(),
            'created_by' => $procurementOfficer->id,
            'issued_by' => $procurementOfficer->id,
            'issued_at' => now(),
        ]);
        $order->items()->create([
            'procurement_requisition_item_id' => $requestLine->id,
            'inventory_item_id' => $item->id,
            'quantity_ordered' => $quantity,
            'unit_cost' => $unitCost,
            'line_total' => $quantity * $unitCost,
        ]);

        return $order->load('items', 'requisition');
    }

    private function userWithRole(School $school, string $role): User
    {
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
