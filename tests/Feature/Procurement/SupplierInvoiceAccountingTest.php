<?php

namespace Tests\Feature\Procurement;

use App\Models\Account;
use App\Models\GoodsReceivedVoucher;
use App\Models\GoodsReceivedVoucherItem;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryWarehouse;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\ProcurementRequisition;
use App\Models\ProcurementSupplierInvoice;
use App\Models\PurchaseOrder;
use App\Models\School;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ProcurementAccountingService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierInvoiceAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_invoice_review_clears_grv_accrual_and_posts_price_variance_to_accounts(): void
    {
        [$school, $item, $warehouse, $vendor] = $this->fixture();
        $requester = $this->userWithRole($school, 'teacher');
        $procurementOfficer = $this->userWithRole($school, 'procurement-officer');
        $storesPerson = $this->userWithRole($school, 'stores-person');
        $accountsClerk = $this->userWithRole($school, 'accounts-clerk');
        [$order, $voucher, $receivedLine] = $this->issuedOrderAndVoucher(
            $school,
            $item,
            $warehouse,
            $vendor,
            $requester,
            $procurementOfficer,
            $storesPerson
        );

        app(ProcurementAccountingService::class)->postGoodsReceivedVoucher($voucher);
        $this->assertDatabaseHas('journal_batches', [
            'school_id' => $school->id,
            'source_type' => 'goods_received_voucher',
            'source_id' => $voucher->id,
            'status' => 'posted',
        ]);

        $this->actingAs($storesPerson)
            ->get(route('admin.procurement.supplier-invoices.create', $voucher))
            ->assertOk()
            ->assertSee('Record supplier invoice');

        $this->actingAs($storesPerson)
            ->post(route('admin.procurement.supplier-invoices.store', $voucher), [
                'invoice_number' => 'INV-1001',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'quantities' => [$receivedLine->id => 5],
                'unit_prices' => [$receivedLine->id => 2.5],
            ])
            ->assertRedirect();

        $invoice = ProcurementSupplierInvoice::with('items')->firstOrFail();
        $this->assertSame('pending_review', $invoice->status);
        $this->assertEquals(12.50, (float) $invoice->total_amount);
        $this->assertEquals(10.00, (float) $invoice->items->sum('stock_cost_basis'));
        $this->assertSame(0, JournalBatch::where('source_type', 'bill')->count());
        $this->actingAs($storesPerson)
            ->get(route('admin.procurement.supplier-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('INV-1001');

        $this->actingAs($accountsClerk)
            ->post(route('admin.procurement.supplier-invoices.review', $invoice), [
                'decision' => 'returned',
                'comments' => 'Please verify the supplier price.',
            ])
            ->assertRedirect();
        $this->assertSame('returned', $invoice->fresh()->status);

        $this->actingAs($storesPerson)
            ->get(route('admin.procurement.supplier-invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('Correct supplier invoice');

        $this->actingAs($storesPerson)
            ->put(route('admin.procurement.supplier-invoices.update', $invoice), [
                'invoice_number' => 'INV-1001',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'quantities' => [$receivedLine->id => 5],
                'unit_prices' => [$receivedLine->id => 2.5],
            ])
            ->assertRedirect();
        $this->assertSame('pending_review', $invoice->fresh()->status);
        $this->assertSame(1, $invoice->reviews()->count());

        $this->actingAs($accountsClerk)
            ->post(route('admin.procurement.supplier-invoices.review', $invoice), [
                'decision' => 'approved',
                'comments' => 'Price difference reviewed and accepted.',
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame('posted', $invoice->status);
        $this->assertNotNull($invoice->bill_id);
        $this->assertSame('pending', $invoice->bill->status);
        $this->assertEquals(12.50, (float) $invoice->bill->total_amount);

        $journal = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'bill')
            ->where('source_id', $invoice->bill_id)
            ->firstOrFail();
        $this->assertSame('posted', $journal->status);

        $grni = Account::where('school_id', $school->id)->where('code', '3103')->firstOrFail();
        $variance = Account::where('school_id', $school->id)->where('code', '6590')->firstOrFail();
        $payables = Account::where('school_id', $school->id)->where('code', '3100')->firstOrFail();
        $this->assertEquals(10, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $grni->id)->where('entry_type', 'debit')->sum('amount'));
        $this->assertEquals(2.5, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $variance->id)->where('entry_type', 'debit')->sum('amount'));
        $this->assertEquals(12.5, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $payables->id)->where('entry_type', 'credit')->sum('amount'));
        $this->assertSame(2, $invoice->reviews()->count());

        $this->actingAs($accountsClerk)
            ->get(route('admin.bills.show', $invoice->bill))
            ->assertOk()
            ->assertDontSee('Edit Bill');
        $this->actingAs($accountsClerk)
            ->get(route('admin.bills.edit', $invoice->bill))
            ->assertStatus(409);
    }

    public function test_invoice_quantity_cannot_exceed_the_uninvoiced_received_quantity(): void
    {
        [$school, $item, $warehouse, $vendor] = $this->fixture();
        $requester = $this->userWithRole($school, 'teacher');
        $procurementOfficer = $this->userWithRole($school, 'procurement-officer');
        $storesPerson = $this->userWithRole($school, 'stores-person');
        [, $voucher, $receivedLine] = $this->issuedOrderAndVoucher(
            $school,
            $item,
            $warehouse,
            $vendor,
            $requester,
            $procurementOfficer,
            $storesPerson
        );

        $this->actingAs($storesPerson)
            ->from(route('admin.procurement.goods-received-vouchers.show', $voucher))
            ->post(route('admin.procurement.supplier-invoices.store', $voucher), [
                'invoice_number' => 'INV-OVER',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'quantities' => [$receivedLine->id => 6],
                'unit_prices' => [$receivedLine->id => 2],
            ])
            ->assertSessionHasErrors(['quantities.'.$receivedLine->id]);

        $this->assertDatabaseCount('procurement_supplier_invoices', 0);
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_supplier_invoice_submitter_cannot_approve_their_own_invoice(): void
    {
        [$school, $item, $warehouse, $vendor] = $this->fixture();
        $requester = $this->userWithRole($school, 'teacher');
        $procurementOfficer = $this->userWithRole($school, 'procurement-officer');
        $accountsClerk = $this->userWithRole($school, 'accounts-clerk');
        [, $voucher, $receivedLine] = $this->issuedOrderAndVoucher(
            $school,
            $item,
            $warehouse,
            $vendor,
            $requester,
            $procurementOfficer,
            $accountsClerk
        );

        $this->actingAs($accountsClerk)
            ->post(route('admin.procurement.supplier-invoices.store', $voucher), [
                'invoice_number' => 'INV-SELF',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'quantities' => [$receivedLine->id => 5],
                'unit_prices' => [$receivedLine->id => 2],
            ])
            ->assertRedirect();
        $invoice = ProcurementSupplierInvoice::firstOrFail();

        $this->actingAs($accountsClerk)
            ->post(route('admin.procurement.supplier-invoices.review', $invoice), [
                'decision' => 'approved',
            ])
            ->assertForbidden();

        $this->assertSame('pending_review', $invoice->fresh()->status);
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('procurement_supplier_invoice_reviews', 0);
    }

    private function fixture(): array
    {
        $school = School::create(['name' => 'Supplier Invoice School', 'code' => 'SUPINV']);
        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'code' => 'SUPPLIES',
            'name' => 'General Supplies',
            'is_active' => true,
        ]);
        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => 'RICE-001',
            'name' => 'Rice',
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
            'code' => 'SUP-100',
            'name' => 'Rice Supplier',
            'vendor_type' => 'supplier',
            'payment_terms' => 'net_30',
            'is_active' => true,
        ]);

        return [$school, $item, $warehouse, $vendor];
    }

    private function issuedOrderAndVoucher(
        School $school,
        InventoryItem $item,
        InventoryWarehouse $warehouse,
        Vendor $vendor,
        User $requester,
        User $procurementOfficer,
        User $storesPerson
    ): array {
        $requisition = ProcurementRequisition::create([
            'school_id' => $school->id,
            'requisition_number' => 'REQ-'.fake()->unique()->numerify('######'),
            'requested_by' => $requester->id,
            'purpose' => 'Food supplies.',
            'status' => 'ordered',
        ]);
        $requestLine = $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 5,
        ]);
        $order = PurchaseOrder::create([
            'school_id' => $school->id,
            'procurement_requisition_id' => $requisition->id,
            'vendor_id' => $vendor->id,
            'purchase_order_number' => 'PO-'.fake()->unique()->numerify('######'),
            'status' => 'received',
            'order_date' => now()->toDateString(),
            'created_by' => $procurementOfficer->id,
            'issued_by' => $procurementOfficer->id,
            'issued_at' => now(),
        ]);
        $orderLine = $order->items()->create([
            'procurement_requisition_item_id' => $requestLine->id,
            'inventory_item_id' => $item->id,
            'quantity_ordered' => 5,
            'unit_cost' => 2,
            'line_total' => 10,
        ]);
        $voucher = GoodsReceivedVoucher::create([
            'school_id' => $school->id,
            'purchase_order_id' => $order->id,
            'inventory_warehouse_id' => $warehouse->id,
            'voucher_number' => 'GRV-'.fake()->unique()->numerify('######'),
            'received_date' => now()->toDateString(),
            'received_by' => $storesPerson->id,
            'posted_at' => now(),
        ]);
        $receivedLine = GoodsReceivedVoucherItem::create([
            'goods_received_voucher_id' => $voucher->id,
            'purchase_order_item_id' => $orderLine->id,
            'inventory_item_id' => $item->id,
            'quantity_received' => 5,
            'unit_cost' => 2,
            'line_total' => 10,
        ]);

        return [$order, $voucher, $receivedLine];
    }

    private function userWithRole(School $school, string $role): User
    {
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
