<?php

namespace Tests\Feature\Procurement;

use App\Models\GoodsReceivedVoucher;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryWarehouse;
use App\Models\ProcurementComparison;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementRequisition;
use App\Models\ProcurementRfq;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\School;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementPrintDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_purchase_order_print_renders_with_official_branding_and_line_items(): void
    {
        $school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
            'motto' => 'Strive for Excellence',
            'phone' => '+263 77 123 4567',
            'email' => 'procurement@handehigh.ac.zw',
        ]);

        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('super-admin');

        $warehouse = InventoryWarehouse::create([
            'school_id' => $school->id,
            'name' => 'Main Stores',
            'code' => 'MS-01',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'name' => 'Stationery',
            'code' => 'STAT',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'STAT-001',
            'name' => 'Exercise Books A4',
            'unit_of_measure' => 'pack',
            'is_active' => true,
        ]);

        $vendor = Vendor::create([
            'school_id' => $school->id,
            'code' => 'VEND-001',
            'name' => 'Premier School Supplies',
            'contact_person' => 'John Dube',
            'phone' => '+263 71 999 8888',
            'email' => 'sales@premier.co.zw',
            'is_active' => true,
        ]);

        $requisition = ProcurementRequisition::create([
            'school_id' => $school->id,
            'requisition_number' => 'REQ-TEST-001',
            'requested_by' => $admin->id,
            'purpose' => 'Term 1 exercise books replenishment',
            'status' => 'approved',
            'delivery_warehouse_id' => $warehouse->id,
        ]);

        $reqItem = $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 100,
            'notes' => 'College ruled 72 pages',
        ]);

        $po = PurchaseOrder::create([
            'school_id' => $school->id,
            'procurement_requisition_id' => $requisition->id,
            'vendor_id' => $vendor->id,
            'purchase_order_number' => 'PO-TEST-001',
            'status' => 'issued',
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Deliver directly to Main Stores. Delivery note required.',
            'created_by' => $admin->id,
            'issued_by' => $admin->id,
            'issued_at' => now(),
        ]);

        $po->items()->create([
            'procurement_requisition_item_id' => $reqItem->id,
            'inventory_item_id' => $item->id,
            'quantity_ordered' => 100,
            'unit_cost' => 15.50,
            'line_total' => 1550.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.procurement.purchase-orders.print', $po));

        $response->assertOk();
        $response->assertSee('LOCAL PURCHASE ORDER');
        $response->assertSee('PO-TEST-001');
        $response->assertSee('Hande High School');
        $response->assertSee('Strive for Excellence');
        $response->assertSee('Premier School Supplies');
        $response->assertSee('Exercise Books A4');
        $response->assertSee('STAT-001');
        $response->assertSee('1,550.00');
        $response->assertSee('Main Stores');
        $response->assertSee('Prepared By');
        $response->assertSee('Supplier Acceptance');
    }

    public function test_goods_received_voucher_print_renders_with_carrier_and_delivery_note_logistics(): void
    {
        $school = School::create(['name' => 'Hande High School', 'code' => 'HHS']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('super-admin');

        $warehouse = InventoryWarehouse::create([
            'school_id' => $school->id,
            'name' => 'Central Warehouse',
            'code' => 'CW-01',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'name' => 'Science',
            'code' => 'SCI',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'SCI-002',
            'name' => 'Microscope Slides 100pk',
            'unit_of_measure' => 'pack',
            'is_active' => true,
        ]);

        $vendor = Vendor::create([
            'school_id' => $school->id,
            'code' => 'VEND-002',
            'name' => 'Scientific Instruments Ltd',
            'is_active' => true,
        ]);

        $requisition = ProcurementRequisition::create([
            'school_id' => $school->id,
            'requisition_number' => 'REQ-TEST-002',
            'requested_by' => $admin->id,
            'purpose' => 'Science lab supplies',
            'status' => 'approved',
        ]);

        $reqItem = $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 20,
        ]);

        $po = PurchaseOrder::create([
            'school_id' => $school->id,
            'procurement_requisition_id' => $requisition->id,
            'vendor_id' => $vendor->id,
            'purchase_order_number' => 'PO-TEST-002',
            'status' => 'received',
            'order_date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $poItem = $po->items()->create([
            'procurement_requisition_item_id' => $reqItem->id,
            'inventory_item_id' => $item->id,
            'quantity_ordered' => 20,
            'unit_cost' => 25.00,
            'line_total' => 500.00,
        ]);

        $voucher = GoodsReceivedVoucher::create([
            'school_id' => $school->id,
            'purchase_order_id' => $po->id,
            'inventory_warehouse_id' => $warehouse->id,
            'voucher_number' => 'GRV-TEST-001',
            'received_date' => now()->toDateString(),
            'delivery_note_number' => 'DN-99482',
            'delivery_note_date' => now()->toDateString(),
            'carrier_name' => 'Swift Courier Express',
            'notes' => 'All boxes inspected and received in good order.',
            'received_by' => $admin->id,
            'posted_at' => now(),
        ]);

        $voucher->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'inventory_item_id' => $item->id,
            'quantity_received' => 20,
            'unit_cost' => 25.00,
            'line_total' => 500.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.procurement.goods-received-vouchers.print', $voucher));

        $response->assertOk();
        $response->assertSee('GOODS RECEIVED NOTE');
        $response->assertSee('GRV-TEST-001');
        $response->assertSee('DN-99482');
        $response->assertSee('Swift Courier Express');
        $response->assertSee('PO-TEST-002');
        $response->assertSee('Central Warehouse');
        $response->assertSee('Microscope Slides 100pk');
        $response->assertSee('500.00');
        $response->assertSee('Received By (Stores Clerk)');
        $response->assertSee('Delivered By (Driver / Carrier)');
    }

    public function test_rfq_comparison_print_renders_comparative_matrix_and_award_recommendation(): void
    {
        $school = School::create(['name' => 'Hande High School', 'code' => 'HHS']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('super-admin');

        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'name' => 'ICT Equipment',
            'code' => 'ICT',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'ICT-001',
            'name' => 'Cat6 Ethernet Cable Drum 305m',
            'unit_of_measure' => 'drum',
            'is_active' => true,
        ]);

        $vendor1 = Vendor::create(['school_id' => $school->id, 'code' => 'VEND-003', 'name' => 'TechZone Solutions', 'is_active' => true]);
        $vendor2 = Vendor::create(['school_id' => $school->id, 'code' => 'VEND-004', 'name' => 'Connect Cables Ltd', 'is_active' => true]);

        $rfq = ProcurementRfq::create([
            'school_id' => $school->id,
            'rfq_number' => 'RFQ-TEST-001',
            'title' => 'Supply of Computer Lab Networking Cables',
            'submission_deadline' => now()->addDays(7)->toDateString(),
            'status' => 'awarded',
            'created_by' => $admin->id,
        ]);

        $rfqItem = $rfq->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 5,
            'specifications' => 'Pure copper, shielded twisted pair (STP)',
        ]);

        $quote1 = ProcurementQuotation::create([
            'school_id' => $school->id,
            'procurement_rfq_id' => $rfq->id,
            'vendor_id' => $vendor1->id,
            'quotation_number' => 'QT-TZ-101',
            'quotation_date' => now()->toDateString(),
            'lead_time_days' => 3,
            'payment_terms' => '30 Days Net',
            'total_amount' => 600.00,
            'status' => 'selected',
            'created_by' => $admin->id,
        ]);

        $quote1->items()->create([
            'procurement_rfq_item_id' => $rfqItem->id,
            'inventory_item_id' => $item->id,
            'quantity' => 5,
            'unit_price' => 120.00,
            'total_price' => 600.00,
        ]);

        $quote2 = ProcurementQuotation::create([
            'school_id' => $school->id,
            'procurement_rfq_id' => $rfq->id,
            'vendor_id' => $vendor2->id,
            'quotation_number' => 'QT-CC-202',
            'quotation_date' => now()->toDateString(),
            'lead_time_days' => 7,
            'payment_terms' => 'Immediate on delivery',
            'total_amount' => 750.00,
            'status' => 'rejected',
            'created_by' => $admin->id,
        ]);

        $quote2->items()->create([
            'procurement_rfq_item_id' => $rfqItem->id,
            'inventory_item_id' => $item->id,
            'quantity' => 5,
            'unit_price' => 150.00,
            'total_price' => 750.00,
        ]);

        $comparison = ProcurementComparison::create([
            'school_id' => $school->id,
            'procurement_rfq_id' => $rfq->id,
            'comparison_number' => 'CMP-TEST-001',
            'evaluation_date' => now()->toDateString(),
            'winning_quotation_id' => $quote1->id,
            'winning_vendor_id' => $vendor1->id,
            'selection_justification' => 'TechZone offers the lowest responsive price with shorter lead time.',
            'evaluated_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.procurement.rfq.comparison.print', $rfq));

        $response->assertOk();
        $response->assertSee('BID COMPARISON SCHEDULE');
        $response->assertSee('RFQ-TEST-001');
        $response->assertSee('TechZone Solutions');
        $response->assertSee('Connect Cables Ltd');
        $response->assertSee('600.00');
        $response->assertSee('750.00');
        $response->assertSee('AWARDED');
        $response->assertSee('TechZone offers the lowest responsive price');
        $response->assertSee('Tender Committee Award Determination');
    }

    public function test_purchase_return_debit_note_print_renders_financial_adjustment_notice(): void
    {
        $school = School::create(['name' => 'Hande High School', 'code' => 'HHS']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('super-admin');

        $warehouse = InventoryWarehouse::create([
            'school_id' => $school->id,
            'name' => 'Textbooks Store',
            'code' => 'TB-01',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'name' => 'Books',
            'code' => 'BK',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'BK-001',
            'name' => 'O-Level Mathematics Book 4',
            'unit_of_measure' => 'copy',
            'is_active' => true,
        ]);

        $vendor = Vendor::create(['school_id' => $school->id, 'code' => 'VEND-005', 'name' => 'Book Publishers Ltd', 'is_active' => true]);

        $requisition = ProcurementRequisition::create([
            'school_id' => $school->id,
            'requisition_number' => 'REQ-BK-001',
            'requested_by' => $admin->id,
            'purpose' => 'Math textbooks',
            'status' => 'approved',
        ]);

        $reqItem = $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 50,
        ]);

        $po = PurchaseOrder::create([
            'school_id' => $school->id,
            'procurement_requisition_id' => $requisition->id,
            'vendor_id' => $vendor->id,
            'purchase_order_number' => 'PO-BK-001',
            'status' => 'received',
            'order_date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $poItem = $po->items()->create([
            'procurement_requisition_item_id' => $reqItem->id,
            'inventory_item_id' => $item->id,
            'quantity_ordered' => 50,
            'unit_cost' => 20.00,
            'line_total' => 1000.00,
        ]);

        $grv = GoodsReceivedVoucher::create([
            'school_id' => $school->id,
            'purchase_order_id' => $po->id,
            'inventory_warehouse_id' => $warehouse->id,
            'voucher_number' => 'GRV-BK-001',
            'received_date' => now()->toDateString(),
            'received_by' => $admin->id,
            'posted_at' => now(),
        ]);

        $grvItem = $grv->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'inventory_item_id' => $item->id,
            'quantity_received' => 50,
            'unit_cost' => 20.00,
            'line_total' => 1000.00,
        ]);

        $purchaseReturn = PurchaseReturn::create([
            'school_id' => $school->id,
            'return_number' => 'RET-BK-001',
            'goods_received_voucher_id' => $grv->id,
            'purchase_order_id' => $po->id,
            'vendor_id' => $vendor->id,
            'inventory_warehouse_id' => $warehouse->id,
            'return_date' => now()->toDateString(),
            'reason' => 'defective_goods',
            'reason_details' => '10 copies had misprinted upside-down pages.',
            'debit_note_number' => 'DN-RET-001',
            'status' => 'posted',
            'created_by' => $admin->id,
            'posted_at' => now(),
        ]);

        $purchaseReturn->items()->create([
            'goods_received_voucher_item_id' => $grvItem->id,
            'inventory_item_id' => $item->id,
            'quantity_returned' => 10,
            'unit_cost' => 20.00,
            'total_cost' => 200.00,
            'remarks' => 'Misprinted pages 45-80',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.inventory.returns.print', $purchaseReturn));

        $response->assertOk();
        $response->assertSee('COMMERCIAL DEBIT NOTE');
        $response->assertSee('DN-RET-001');
        $response->assertSee('RET-BK-001');
        $response->assertSee('Book Publishers Ltd');
        $response->assertSee('O-Level Mathematics Book 4');
        $response->assertSee('200.00');
        $response->assertSee('10 copies had misprinted upside-down pages.');
        $response->assertSee('Returned By (Stores Officer)');
        $response->assertSee('Authorized By (Bursar / Finance)');
    }

    public function test_cross_tenant_isolation_on_all_print_routes(): void
    {
        $schoolA = School::create(['name' => 'School A', 'code' => 'SCHA']);
        $schoolB = School::create(['name' => 'School B', 'code' => 'SCHB']);

        $userB = User::factory()->create(['school_id' => $schoolB->id]);
        $userB->assignRole('super-admin');

        $vendorA = Vendor::create(['school_id' => $schoolA->id, 'code' => 'VEND-A', 'name' => 'Vendor A', 'is_active' => true]);
        $whA = InventoryWarehouse::create(['school_id' => $schoolA->id, 'name' => 'Warehouse A', 'code' => 'WHA', 'is_active' => true]);
        $catA = InventoryCategory::create(['school_id' => $schoolA->id, 'name' => 'Cat A', 'code' => 'CATA', 'is_active' => true]);
        $itemA = InventoryItem::create([
            'school_id' => $schoolA->id,
            'inventory_category_id' => $catA->id,
            'code' => 'ITM-A',
            'name' => 'Item A',
            'unit_of_measure' => 'unit',
            'is_active' => true,
        ]);

        $reqA = ProcurementRequisition::create([
            'school_id' => $schoolA->id,
            'requisition_number' => 'REQ-A',
            'requested_by' => $userB->id,
            'purpose' => 'Test',
            'status' => 'approved',
        ]);

        $poA = PurchaseOrder::create([
            'school_id' => $schoolA->id,
            'procurement_requisition_id' => $reqA->id,
            'vendor_id' => $vendorA->id,
            'purchase_order_number' => 'PO-A',
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'created_by' => $userB->id,
        ]);

        $grvA = GoodsReceivedVoucher::create([
            'school_id' => $schoolA->id,
            'purchase_order_id' => $poA->id,
            'inventory_warehouse_id' => $whA->id,
            'voucher_number' => 'GRV-A',
            'received_date' => now()->toDateString(),
            'received_by' => $userB->id,
            'posted_at' => now(),
        ]);

        $retA = PurchaseReturn::create([
            'school_id' => $schoolA->id,
            'return_number' => 'RET-A',
            'goods_received_voucher_id' => $grvA->id,
            'purchase_order_id' => $poA->id,
            'vendor_id' => $vendorA->id,
            'inventory_warehouse_id' => $whA->id,
            'return_date' => now()->toDateString(),
            'reason' => 'defective',
            'debit_note_number' => 'DN-A',
            'status' => 'posted',
            'created_by' => $userB->id,
        ]);

        $rfqA = ProcurementRfq::create([
            'school_id' => $schoolA->id,
            'rfq_number' => 'RFQ-A',
            'title' => 'RFQ A',
            'submission_deadline' => now()->addDays(7)->toDateString(),
            'status' => 'published',
            'created_by' => $userB->id,
        ]);

        // School B user should receive 404/403 when trying to access School A's print layouts
        $this->actingAs($userB)->get(route('admin.procurement.purchase-orders.print', $poA))->assertStatus(404);
        $this->actingAs($userB)->get(route('admin.procurement.goods-received-vouchers.print', $grvA))->assertStatus(404);
        $this->actingAs($userB)->get(route('admin.inventory.returns.print', $retA))->assertStatus(404);
        $this->actingAs($userB)->get(route('admin.procurement.rfq.comparison.print', $rfqA))->assertStatus(404);
    }
}
