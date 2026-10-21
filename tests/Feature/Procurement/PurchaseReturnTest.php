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
use App\Models\PurchaseReturn;
use App\Models\School;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_receiving_with_delivery_note_and_processing_goods_return(): void
    {
        $school = School::create(['name' => 'Bulawayo High School', 'code' => 'BHS']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('super-admin');

        $storesPerson = User::factory()->create(['school_id' => $school->id]);
        $storesPerson->assignRole('stores-person');

        $warehouse = InventoryWarehouse::create([
            'school_id' => $school->id,
            'name' => 'Main Science Stores',
            'code' => 'MSS-01',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'name' => 'General Consumables',
            'code' => 'GEN',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'CON-001',
            'name' => 'Whiteboard Markers Pack',
            'unit_of_measure' => 'pack',
            'cost_method' => 'weighted_average',
            'unit_cost' => 5.00,
            'is_active' => true,
        ]);

        $vendor = Vendor::create([
            'school_id' => $school->id,
            'code' => 'STAT-001',
            'name' => 'Stationery World Pvt Ltd',
            'email' => 'sales@stationeryworld.com',
            'is_active' => true,
        ]);

        $requisition = ProcurementRequisition::create([
            'school_id' => $school->id,
            'requisition_number' => 'REQ-STORES-001',
            'requested_by' => $admin->id,
            'purpose' => 'Consumables for Term 3',
            'status' => 'approved',
        ]);

        $reqItem = $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 50,
        ]);

        $order = PurchaseOrder::create([
            'school_id' => $school->id,
            'procurement_requisition_id' => $requisition->id,
            'vendor_id' => $vendor->id,
            'purchase_order_number' => 'PO-TEST-001',
            'status' => 'issued',
            'order_date' => now()->toDateString(),
            'created_by' => $admin->id,
            'issued_by' => $admin->id,
            'issued_at' => now(),
        ]);

        $poItem = $order->items()->create([
            'procurement_requisition_item_id' => $reqItem->id,
            'inventory_item_id' => $item->id,
            'quantity_ordered' => 50,
            'unit_cost' => 5.00,
            'line_total' => 250.00,
        ]);

        // 1. Stores Manager dashboard displays approved issued PO
        $this->actingAs($storesPerson)
            ->get(route('admin.dashboard.stores-manager'))
            ->assertOk()
            ->assertSee($order->purchase_order_number)
            ->assertSee('Receive Goods / GRN');

        // 2. Receive goods with Delivery Note Number & Carrier
        $grnResponse = $this->actingAs($storesPerson)
            ->post(route('admin.procurement.goods-received-vouchers.store', $order), [
                'inventory_warehouse_id' => $warehouse->id,
                'received_date' => now()->toDateString(),
                'delivery_note_number' => 'DN-77889',
                'delivery_note_date' => now()->toDateString(),
                'carrier_name' => 'Swift Courier Express',
                'received_quantities' => [
                    $poItem->id => 50,
                ],
            ]);

        $grn = GoodsReceivedVoucher::where('purchase_order_id', $order->id)->firstOrFail();
        $this->assertSame('DN-77889', $grn->delivery_note_number);
        $this->assertSame('Swift Courier Express', $grn->carrier_name);

        // Check stock balance after receiving
        $balance = InventoryBalance::where('school_id', $school->id)
            ->where('inventory_item_id', $item->id)
            ->where('inventory_warehouse_id', $warehouse->id)
            ->firstOrFail();
        $this->assertEquals(50, (float) $balance->quantity_on_hand);

        // 3. Stores Manager Dashboard shows recent GRN with D/Note
        $this->actingAs($storesPerson)
            ->get(route('admin.dashboard.stores-manager'))
            ->assertOk()
            ->assertSee($grn->voucher_number)
            ->assertSee('DN-77889');

        // 4. Return defective goods (10 defective packs returned to supplier)
        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.returns.create', $grn))
            ->assertOk()
            ->assertSee('Return Goods to Vendor')
            ->assertSee($grn->voucher_number);

        $grnItem = $grn->items->first();

        $returnResponse = $this->actingAs($storesPerson)
            ->post(route('admin.inventory.returns.store', $grn), [
                'return_date' => now()->toDateString(),
                'reason' => 'defective',
                'reason_details' => '10 marker packs were dried out on delivery inspection.',
                'debit_note_number' => 'DN-DEBIT-001',
                'return_quantities' => [
                    $grnItem->id => 10,
                ],
            ]);

        $purchaseReturn = PurchaseReturn::where('goods_received_voucher_id', $grn->id)->firstOrFail();
        $returnResponse->assertRedirect(route('admin.inventory.returns.show', $purchaseReturn));
        $this->assertSame('posted', $purchaseReturn->status);
        $this->assertSame('DN-DEBIT-001', $purchaseReturn->debit_note_number);

        // Stock on hand decremented by returned quantity
        $balance->refresh();
        $this->assertEquals(40, (float) $balance->quantity_on_hand);

        // Verify inventory transaction logged
        $transaction = InventoryTransaction::where('school_id', $school->id)
            ->where('inventory_item_id', $item->id)
            ->where('movement_type', 'supplier_return')
            ->firstOrFail();
        $this->assertEquals(-10, (float) $transaction->quantity_delta);

        // 5. Viewing returns index and show views
        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.returns.index'))
            ->assertOk()
            ->assertSee($purchaseReturn->return_number)
            ->assertSee('Stationery World Pvt Ltd');

        $this->actingAs($admin)
            ->get(route('admin.inventory.returns.show', $purchaseReturn))
            ->assertOk()
            ->assertSee($purchaseReturn->return_number)
            ->assertSee('DN-DEBIT-001')
            ->assertSee('10 marker packs were dried out');
    }
}
