<?php

namespace Tests\Feature\Procurement;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\ProcurementComparison;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementRequisition;
use App\Models\ProcurementRfq;
use App\Models\PurchaseOrder;
use App\Models\School;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementRfqComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_complete_rfq_quotations_and_bid_comparison_cycle(): void
    {
        $school = School::create(['name' => 'Harare High School', 'code' => 'HHS']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('super-admin');

        $procurementOfficer = User::factory()->create(['school_id' => $school->id]);
        $procurementOfficer->assignRole('procurement-officer');

        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'name' => 'Science Equipment',
            'code' => 'SCI',
            'is_active' => true,
        ]);

        $item1 = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'SCI-001',
            'name' => 'Test Tubes 50ml',
            'unit_of_measure' => 'box',
            'cost_method' => 'weighted_average',
            'unit_cost' => 10.00,
            'is_active' => true,
        ]);

        $item2 = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'SCI-002',
            'name' => 'Bunsen Burners',
            'unit_of_measure' => 'piece',
            'cost_method' => 'weighted_average',
            'unit_cost' => 25.00,
            'is_active' => true,
        ]);

        $vendorA = Vendor::create([
            'school_id' => $school->id,
            'code' => 'VEND-001',
            'name' => 'Apex Scientific Ltd',
            'email' => 'sales@apexscientific.com',
            'is_active' => true,
        ]);

        $vendorB = Vendor::create([
            'school_id' => $school->id,
            'code' => 'VEND-002',
            'name' => 'Prime Lab Supplies',
            'email' => 'bids@primelab.com',
            'is_active' => true,
        ]);

        // 1. Super admin / Procurement Officer can access RFQ index and create form
        $this->actingAs($procurementOfficer)
            ->get(route('admin.procurement.rfq.index'))
            ->assertOk()
            ->assertSee('Requests for Quotations');

        $this->actingAs($procurementOfficer)
            ->get(route('admin.procurement.rfq.create'))
            ->assertOk()
            ->assertSee('Create Request for Quotation');

        // 2. Store new RFQ
        $rfqResponse = $this->actingAs($procurementOfficer)
            ->post(route('admin.procurement.rfq.store'), [
                'title' => 'Term 3 Chemistry Laboratory Supplies',
                'submission_deadline' => now()->addDays(5)->toDateString(),
                'terms_and_conditions' => 'Delivery within 10 days of order. 30 days payment terms.',
                'vendor_ids' => [$vendorA->id, $vendorB->id],
                'items' => [
                    [
                        'inventory_item_id' => $item1->id,
                        'quantity' => 20,
                        'specifications' => 'Borosilicate glass pack of 50',
                    ],
                    [
                        'inventory_item_id' => $item2->id,
                        'quantity' => 10,
                        'specifications' => 'Standard brass needle valve',
                    ],
                ],
            ]);

        $rfq = ProcurementRfq::where('school_id', $school->id)->firstOrFail();
        $rfqResponse->assertRedirect(route('admin.procurement.rfq.show', $rfq));
        $this->assertSame('draft', $rfq->status);
        $this->assertCount(2, $rfq->items);
        $this->assertCount(2, $rfq->vendors);

        // 3. Publish RFQ
        $this->actingAs($procurementOfficer)
            ->post(route('admin.procurement.rfq.publish', $rfq))
            ->assertRedirect();

        $rfq->refresh();
        $this->assertSame('published', $rfq->status);
        $this->assertNotNull($rfq->published_at);

        // 4. Submit Quotation for Vendor A
        $rfqItem1 = $rfq->items->firstWhere('inventory_item_id', $item1->id);
        $rfqItem2 = $rfq->items->firstWhere('inventory_item_id', $item2->id);

        $this->actingAs($procurementOfficer)
            ->post(route('admin.procurement.rfq.quotations.store', $rfq), [
                'vendor_id' => $vendorA->id,
                'quotation_number' => 'APEX-Q-2026',
                'quotation_date' => now()->toDateString(),
                'lead_time_days' => 5,
                'payment_terms' => '30 Days Net',
                'unit_prices' => [
                    $rfqItem1->id => 12.00,
                    $rfqItem2->id => 28.00,
                ],
            ])
            ->assertRedirect(route('admin.procurement.rfq.show', $rfq));

        // 5. Submit Quotation for Vendor B (Lower bid)
        $this->actingAs($procurementOfficer)
            ->post(route('admin.procurement.rfq.quotations.store', $rfq), [
                'vendor_id' => $vendorB->id,
                'quotation_number' => 'PRIME-2026-09',
                'quotation_date' => now()->toDateString(),
                'lead_time_days' => 7,
                'payment_terms' => '30 Days Net',
                'unit_prices' => [
                    $rfqItem1->id => 9.50,
                    $rfqItem2->id => 24.00,
                ],
            ])
            ->assertRedirect(route('admin.procurement.rfq.show', $rfq));

        $this->assertCount(2, $rfq->fresh()->quotations);

        // 6. View Comparison Report
        $this->actingAs($procurementOfficer)
            ->get(route('admin.procurement.rfq.comparison', $rfq))
            ->assertOk()
            ->assertSee('Item Price Comparison Matrix')
            ->assertSee('Apex Scientific Ltd')
            ->assertSee('Prime Lab Supplies');

        $quoteB = $rfq->quotations()->where('vendor_id', $vendorB->id)->firstOrFail();

        // 7. Store comparison selection (Vendor B chosen)
        $this->actingAs($procurementOfficer)
            ->post(route('admin.procurement.rfq.comparison.store', $rfq), [
                'winning_quotation_id' => $quoteB->id,
                'selection_justification' => 'Prime Lab Supplies provided the lowest overall bid conforming to lab safety specifications with reliable 7-day delivery.',
            ])
            ->assertRedirect(route('admin.procurement.rfq.comparison', $rfq));

        $comparison = ProcurementComparison::where('procurement_rfq_id', $rfq->id)->firstOrFail();
        $this->assertSame('submitted', $comparison->status);
        $this->assertSame($quoteB->id, $comparison->winning_quotation_id);

        // 8. Super Admin approves comparison -> Automatically generates Purchase Order
        $this->actingAs($admin)
            ->post(route('admin.procurement.rfq.comparison.approve', $rfq))
            ->assertRedirect(route('admin.procurement.rfq.comparison', $rfq));

        $comparison->refresh();
        $rfq->refresh();

        $this->assertSame('approved', $comparison->status);
        $this->assertSame('awarded', $rfq->status);
        $this->assertNotNull($comparison->purchase_order_id);

        $po = PurchaseOrder::findOrFail($comparison->purchase_order_id);
        $this->assertSame($vendorB->id, $po->vendor_id);
        $this->assertSame('draft', $po->status);
        $this->assertCount(2, $po->items);

        // Verify line items on PO match winning quotation prices:
        $poItem1 = $po->items->firstWhere('inventory_item_id', $item1->id);
        $this->assertEquals(20, (float) $poItem1->quantity_ordered);
        $this->assertEquals(9.50, (float) $poItem1->unit_cost);
        $this->assertEquals(190.00, (float) $poItem1->line_total);
    }
}
