<?php

namespace Tests\Feature\Procurement;

use App\Models\Department;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryWarehouse;
use App\Models\ProcurementRequisition;
use App\Models\ProcurementRequisitionReview;
use App\Models\PurchaseOrder;
use App\Models\School;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequisitionPurchaseOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private InventoryItem $item;

    private Department $department;

    private InventoryWarehouse $warehouse;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
        $this->school = School::create(['name' => 'Procurement Workflow School', 'code' => 'PROC']);
        $category = InventoryCategory::create([
            'school_id' => $this->school->id,
            'code' => 'FOOD',
            'name' => 'Food Supplies',
            'is_active' => true,
        ]);
        $this->item = InventoryItem::create([
            'school_id' => $this->school->id,
            'inventory_category_id' => $category->id,
            'code' => 'MEALIE-001',
            'name' => 'Mealie Meal',
            'item_type' => 'consumable',
            'unit_of_measure' => 'bag',
            'is_active' => true,
        ]);
        $this->department = Department::create([
            'school_id' => $this->school->id,
            'name' => 'Kitchen',
            'is_active' => true,
        ]);
        $this->warehouse = InventoryWarehouse::create([
            'school_id' => $this->school->id,
            'code' => 'MAIN',
            'name' => 'Main Store',
            'is_active' => true,
        ]);
        $this->vendor = Vendor::create([
            'school_id' => $this->school->id,
            'code' => 'SUP-001',
            'name' => 'Local Supplier',
            'vendor_type' => 'supplier',
            'payment_terms' => 'net_30',
            'is_active' => true,
        ]);
    }

    public function test_requester_submits_approver_records_decision_and_procurement_issues_purchase_order(): void
    {
        $requester = $this->userWithRole('teacher');
        $approver = $this->userWithRole('headmaster');
        $procurementOfficer = $this->userWithRole('procurement-officer');

        $this->actingAs($requester)
            ->get(route('admin.procurement.requisitions.create'))
            ->assertOk()
            ->assertSee('New requisition');

        $this->actingAs($requester)
            ->post(route('admin.procurement.requisitions.store'), [
                'department_id' => $this->department->id,
                'delivery_warehouse_id' => $this->warehouse->id,
                'purpose' => 'Kitchen supplies for the school meal programme.',
                'items' => [
                    ['inventory_item_id' => $this->item->id, 'quantity' => 12, 'notes' => 'Deliver to stores'],
                    ['inventory_item_id' => '', 'quantity' => '', 'notes' => ''],
                ],
            ])
            ->assertRedirect();

        $requisition = ProcurementRequisition::with('items')->firstOrFail();
        $this->assertSame('draft', $requisition->status);
        $this->assertCount(1, $requisition->items);

        $this->actingAs($requester)
            ->from(route('admin.procurement.requisitions.show', $requisition))
            ->post(route('admin.procurement.requisitions.submit', $requisition))
            ->assertRedirect(route('admin.procurement.requisitions.show', $requisition));
        $this->assertSame('submitted', $requisition->fresh()->status);

        $this->actingAs($requester)
            ->post(route('admin.procurement.requisitions.review', $requisition), [
                'decision' => 'approved',
                'comments' => 'Approved for the kitchen.',
            ])
            ->assertForbidden();

        $this->actingAs($approver)
            ->from(route('admin.procurement.requisitions.show', $requisition))
            ->post(route('admin.procurement.requisitions.review', $requisition), [
                'decision' => 'approved',
                'comments' => 'Approved for the kitchen.',
            ])
            ->assertRedirect(route('admin.procurement.requisitions.show', $requisition));
        $this->assertSame('approved', $requisition->fresh()->status);
        $this->assertDatabaseHas('procurement_requisition_reviews', [
            'procurement_requisition_id' => $requisition->id,
            'reviewed_by' => $approver->id,
            'decision' => 'approved',
        ]);

        $this->actingAs($procurementOfficer)
            ->get(route('admin.procurement.purchase-orders.create', $requisition))
            ->assertOk()
            ->assertSee('Create purchase order');

        $line = $requisition->items->first();
        $this->actingAs($procurementOfficer)
            ->post(route('admin.procurement.purchase-orders.store', $requisition), [
                'vendor_id' => $this->vendor->id,
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => now()->addDays(4)->toDateString(),
                'unit_costs' => [$line->id => '2.75'],
            ])
            ->assertRedirect(route('admin.procurement.index'));

        $purchaseOrder = PurchaseOrder::with('items')->firstOrFail();
        $this->assertSame('draft', $purchaseOrder->status);
        $this->assertSame('purchase_order_draft', $requisition->fresh()->status);
        $this->assertEquals(33.00, (float) $purchaseOrder->items->first()->line_total);

        $this->actingAs($procurementOfficer)
            ->post(route('admin.procurement.purchase-orders.store', $requisition), [
                'vendor_id' => $this->vendor->id,
                'order_date' => now()->toDateString(),
                'unit_costs' => [$line->id => '2.75'],
            ])
            ->assertStatus(409);
        $this->assertDatabaseCount('purchase_orders', 1);

        $this->actingAs($procurementOfficer)
            ->post(route('admin.procurement.purchase-orders.issue', $purchaseOrder))
            ->assertRedirect();

        $this->assertSame('issued', $purchaseOrder->fresh()->status);
        $this->assertSame('ordered', $requisition->fresh()->status);
        $this->assertNotNull($purchaseOrder->fresh()->issued_at);

        $this->actingAs($requester)
            ->get(route('admin.procurement.purchase-orders.show', $purchaseOrder))
            ->assertOk()
            ->assertSee($purchaseOrder->purchase_order_number);
    }

    public function test_rejected_requisition_can_be_resubmitted_and_review_history_is_preserved(): void
    {
        $requester = $this->userWithRole('teacher');
        $approver = $this->userWithRole('headmaster');

        $this->actingAs($requester)
            ->post(route('admin.procurement.requisitions.store'), [
                'purpose' => 'Kitchen supplies.',
                'items' => [
                    ['inventory_item_id' => $this->item->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();
        $requisition = ProcurementRequisition::with('items')->firstOrFail();

        $this->actingAs($requester)
            ->post(route('admin.procurement.requisitions.submit', $requisition))
            ->assertRedirect();
        $this->actingAs($approver)
            ->post(route('admin.procurement.requisitions.review', $requisition), [
                'decision' => 'rejected',
                'comments' => 'Please confirm the required quantity.',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $requisition->fresh()->status);
        $this->assertDatabaseHas('procurement_requisition_reviews', [
            'procurement_requisition_id' => $requisition->id,
            'decision' => 'rejected',
            'comments' => 'Please confirm the required quantity.',
        ]);

        $this->actingAs($requester)
            ->post(route('admin.procurement.requisitions.submit', $requisition))
            ->assertRedirect();
        $this->actingAs($approver)
            ->post(route('admin.procurement.requisitions.review', $requisition), [
                'decision' => 'approved',
                'comments' => 'Quantity confirmed.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $requisition->fresh()->status);
        $this->assertSame(2, ProcurementRequisitionReview::where('procurement_requisition_id', $requisition->id)->count());
    }

    public function test_requester_cannot_open_another_users_requisition_in_the_same_school(): void
    {
        $requester = $this->userWithRole('teacher');
        $otherRequester = $this->userWithRole('teacher');

        $this->actingAs($otherRequester)
            ->post(route('admin.procurement.requisitions.store'), [
                'purpose' => 'Private request.',
                'items' => [
                    ['inventory_item_id' => $this->item->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();
        $otherRequisition = ProcurementRequisition::firstOrFail();

        $this->actingAs($requester)
            ->get(route('admin.procurement.requisitions.show', $otherRequisition))
            ->assertForbidden();
    }

    public function test_approver_cannot_approve_their_own_requisition(): void
    {
        $requesterApprover = $this->userWithRole('school-admin');
        $this->actingAs($requesterApprover)
            ->post(route('admin.procurement.requisitions.store'), [
                'purpose' => 'Request created by an approver.',
                'items' => [
                    ['inventory_item_id' => $this->item->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();
        $requisition = ProcurementRequisition::firstOrFail();

        $this->actingAs($requesterApprover)
            ->post(route('admin.procurement.requisitions.submit', $requisition))
            ->assertRedirect();

        $this->actingAs($requesterApprover)
            ->post(route('admin.procurement.requisitions.review', $requisition), [
                'decision' => 'approved',
            ])
            ->assertForbidden();

        $this->assertSame('submitted', $requisition->fresh()->status);
        $this->assertDatabaseCount('procurement_requisition_reviews', 0);
    }

    public function test_requisition_validation_rejects_foreign_school_items_and_departments(): void
    {
        $requester = $this->userWithRole('teacher');
        $foreignSchool = School::create(['name' => 'Other Procurement School', 'code' => 'OPROC']);
        $foreignCategory = InventoryCategory::create([
            'school_id' => $foreignSchool->id,
            'code' => 'OTHER',
            'name' => 'Other Supplies',
            'is_active' => true,
        ]);
        $foreignItem = InventoryItem::create([
            'school_id' => $foreignSchool->id,
            'inventory_category_id' => $foreignCategory->id,
            'code' => 'OTHER-ITEM',
            'name' => 'Foreign item',
            'item_type' => 'consumable',
            'unit_of_measure' => 'each',
            'is_active' => true,
        ]);
        $foreignDepartment = Department::create([
            'school_id' => $foreignSchool->id,
            'name' => 'Foreign department',
            'is_active' => true,
        ]);

        $this->actingAs($requester)
            ->post(route('admin.procurement.requisitions.store'), [
                'department_id' => $foreignDepartment->id,
                'purpose' => 'Must not cross school boundary.',
                'items' => [
                    ['inventory_item_id' => $foreignItem->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasErrors(['department_id', 'items.0.inventory_item_id']);

        $this->assertDatabaseCount('procurement_requisitions', 0);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['school_id' => $this->school->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
