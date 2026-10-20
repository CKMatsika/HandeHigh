<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\BillItem;
use App\Models\GoodsReceivedVoucher;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventorySetting;
use App\Models\InventoryWarehouse;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\ProcurementRequisition;
use App\Models\ProcurementSupplierInvoice;
use App\Models\PurchaseOrder;
use App\Models\School;
use App\Models\SchoolFixedAsset;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SchoolFixedAssetService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolFixedAssetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_capitalized_receipts_create_individual_assets_and_post_to_the_mapped_account(): void
    {
        [$school, $item, $warehouse, $storesPerson, $category] = $this->assetFixture('CAPITAL', 500);
        $this->assertTrue(app(SchoolFixedAssetService::class)->shouldCapitalize($item, 500));
        $order = $this->issuedOrder($school, $item, $storesPerson, 2, 700);
        $this->receive($order, $warehouse, $storesPerson, 2);

        $assets = SchoolFixedAsset::where('school_id', $school->id)->orderBy('id')->get();
        $this->assertCount(2, $assets);
        $this->assertTrue($assets->every(fn (SchoolFixedAsset $asset) => $asset->is_capitalized));
        $this->assertSame(0, InventoryBalance::where('school_id', $school->id)->count());
        $this->assertDatabaseHas('school_fixed_assets', [
            'school_id' => $school->id,
            'inventory_item_id' => $item->id,
            'asset_account_id' => $category->asset_account_id,
            'purchase_cost' => 700,
            'capitalized_cost' => 700,
        ]);

        $voucher = GoodsReceivedVoucher::firstOrFail();
        $journal = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'goods_received_voucher')
            ->where('source_id', $voucher->id)
            ->firstOrFail();
        $this->assertEquals(1400, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', $category->asset_account_id)
            ->where('entry_type', 'debit')
            ->sum('amount'));
        $this->assertEquals(1400, (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('account_id', Account::where('school_id', $school->id)->where('code', '3103')->value('id'))
            ->where('entry_type', 'credit')
            ->sum('amount'));

        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('Capitalized asset value')
            ->assertSee('1,400.00');
        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.fixed-assets.index'))
            ->assertOk()
            ->assertSee($assets[0]->asset_number);
        $this->actingAs($storesPerson)
            ->get(route('admin.inventory.fixed-assets.show', $assets[0]))
            ->assertOk()
            ->assertSee('Custody history')
            ->assertSee($voucher->voucher_number);

        $staff = $this->userWithRole($school, 'teacher');
        $this->actingAs($storesPerson)
            ->put(route('admin.inventory.fixed-assets.custody', $assets[0]), [
                'custodian_user_id' => $staff->id,
                'serial_number' => 'SERIAL-CAP-001',
                'location' => 'Science Block',
                'notes' => 'Assigned to laboratory.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('school_fixed_assets', [
            'id' => $assets[0]->id,
            'custodian_user_id' => $staff->id,
            'serial_number' => 'SERIAL-CAP-001',
            'location' => 'Science Block',
            'status' => 'assigned',
            'notes' => 'Assigned to laboratory.',
        ]);
        $this->assertDatabaseHas('school_fixed_asset_assignments', [
            'school_fixed_asset_id' => $assets[0]->id,
            'to_user_id' => $staff->id,
            'action' => 'assigned',
        ]);

        $voucher = GoodsReceivedVoucher::firstOrFail();
        $receivedItem = $voucher->items()->firstOrFail();
        $this->actingAs($storesPerson)
            ->post(route('admin.procurement.supplier-invoices.store', $voucher), [
                'invoice_number' => 'INV-'.$school->code,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'quantities' => [$receivedItem->id => 2],
                'unit_prices' => [$receivedItem->id => 750],
            ])
            ->assertRedirect();

        $invoice = ProcurementSupplierInvoice::firstOrFail();
        $accountsClerk = $this->userWithRole($school, 'accounts-clerk');
        $this->actingAs($accountsClerk)
            ->post(route('admin.procurement.supplier-invoices.review', $invoice), [
                'decision' => 'approved',
                'comments' => 'Supplier invoice checked.',
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame('posted', $invoice->status);
        $this->assertDatabaseHas('school_fixed_assets', [
            'id' => $assets[0]->id,
            'supplier_invoice_item_id' => $invoice->items()->firstOrFail()->id,
            'purchase_cost' => 750,
            'capitalized_cost' => 750,
        ]);
        $billItem = BillItem::where('bill_id', $invoice->bill_id)->firstOrFail();
        $this->assertSame($category->asset_account_id, $billItem->expense_account_id);
        $invoiceJournal = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'bill')
            ->where('source_id', $invoice->bill_id)
            ->firstOrFail();
        $this->assertEquals(100, (float) JournalEntry::where('journal_batch_id', $invoiceJournal->id)
            ->where('account_id', $category->asset_account_id)
            ->where('entry_type', 'debit')
            ->sum('amount'));

        $otherSchool = School::create(['name' => 'Other Fixed Asset School', 'code' => 'OTHER-FA']);
        $otherHeadmaster = $this->userWithRole($otherSchool, 'headmaster');
        $this->actingAs($otherHeadmaster)
            ->get(route('admin.inventory.fixed-assets.show', $assets[0]))
            ->assertNotFound();
    }

    public function test_an_unset_capitalization_threshold_capitalizes_all_asset_items(): void
    {
        [$school, $item] = $this->assetFixture('UNSETTHRESHOLD', 500);
        InventorySetting::where('school_id', $school->id)
            ->update(['asset_capitalization_threshold' => null]);

        $this->assertTrue(app(SchoolFixedAssetService::class)->shouldCapitalize($item, 0.01));
    }

    public function test_assigning_and_returning_a_low_value_asset_posts_inventory_and_expense_movements(): void
    {
        [$school, $item, $warehouse, $storesPerson] = $this->assetFixture('LOWVALUE', 500);
        $order = $this->issuedOrder($school, $item, $storesPerson, 1, 100.126);
        $this->receive($order, $warehouse, $storesPerson, 1);
        $asset = SchoolFixedAsset::where('school_id', $school->id)->firstOrFail();
        $this->assertFalse($asset->is_capitalized);
        $balance = InventoryBalance::where('school_id', $school->id)->firstOrFail();
        $this->assertEquals(1, (float) $balance->quantity_on_hand);

        $staff = $this->userWithRole($school, 'teacher');
        $this->actingAs($storesPerson)
            ->put(route('admin.inventory.fixed-assets.custody', $asset), [
                'custodian_user_id' => $staff->id,
                'serial_number' => 'SERIAL-LOW-001',
                'location' => 'Classroom 1',
            ])
            ->assertRedirect();

        $balance->refresh();
        $this->assertEquals(0, (float) $balance->quantity_on_hand);
        $expenseAccountId = Account::where('school_id', $school->id)->where('code', '6500')->value('id');
        $inventoryAccountId = Account::where('school_id', $school->id)->where('code', '1505')->value('id');
        $assignmentJournal = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'school_fixed_asset_assignment')
            ->firstOrFail();
        $this->assertEquals(100.13, (float) JournalEntry::where('journal_batch_id', $assignmentJournal->id)
            ->where('account_id', $expenseAccountId)
            ->where('entry_type', 'debit')
            ->sum('amount'));
        $this->assertEquals(100.13, (float) JournalEntry::where('journal_batch_id', $assignmentJournal->id)
            ->where('account_id', $inventoryAccountId)
            ->where('entry_type', 'credit')
            ->sum('amount'));

        $this->actingAs($storesPerson)
            ->put(route('admin.inventory.fixed-assets.custody', $asset), [
                'custodian_user_id' => '',
                'serial_number' => 'SERIAL-LOW-001',
                'location' => 'Main Store',
            ])
            ->assertRedirect();

        $balance->refresh();
        $this->assertEquals(1, (float) $balance->quantity_on_hand);
        $returnJournal = JournalBatch::where('school_id', $school->id)
            ->where('source_type', 'school_fixed_asset_assignment')
            ->latest('id')
            ->firstOrFail();
        $this->assertEquals(100.13, (float) JournalEntry::where('journal_batch_id', $returnJournal->id)
            ->where('account_id', $inventoryAccountId)
            ->where('entry_type', 'debit')
            ->sum('amount'));
        $this->assertEquals(100.13, (float) JournalEntry::where('journal_batch_id', $returnJournal->id)
            ->where('account_id', $expenseAccountId)
            ->where('entry_type', 'credit')
            ->sum('amount'));
    }

    private function assetFixture(string $code, float $threshold): array
    {
        $school = School::create(['name' => "{$code} School", 'code' => $code]);
        (new ChartOfAccountsSeeder)->seedAccountsForSchool($school);
        $assetAccount = Account::where('school_id', $school->id)->where('code', '2301')->firstOrFail();
        $expenseAccount = Account::where('school_id', $school->id)->where('code', '6500')->firstOrFail();
        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'code' => 'EQUIPMENT',
            'name' => 'Equipment',
            'issue_expense_account_id' => $expenseAccount->id,
            'asset_account_id' => $assetAccount->id,
            'is_active' => true,
        ]);
        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => "{$code}-ITEM",
            'name' => "{$code} asset",
            'item_type' => 'asset',
            'unit_of_measure' => 'each',
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
            'code' => "{$code}-SUP",
            'name' => 'Equipment Supplier',
            'vendor_type' => 'supplier',
            'payment_terms' => 'net_30',
            'is_active' => true,
        ]);
        InventorySetting::create([
            'school_id' => $school->id,
            'valuation_method' => 'weighted_average',
            'asset_capitalization_threshold' => $threshold,
        ]);
        $storesPerson = $this->userWithRole($school, 'stores-person');

        return [$school, $item, $warehouse, $storesPerson, $category, $vendor];
    }

    private function issuedOrder(
        School $school,
        InventoryItem $item,
        User $storesPerson,
        int $quantity,
        float $unitCost
    ): PurchaseOrder {
        $vendor = Vendor::where('school_id', $school->id)->firstOrFail();
        $requester = $this->userWithRole($school, 'teacher');
        $procurementOfficer = $this->userWithRole($school, 'procurement-officer');
        $requisition = ProcurementRequisition::create([
            'school_id' => $school->id,
            'requisition_number' => 'REQ-'.fake()->unique()->numerify('######'),
            'requested_by' => $requester->id,
            'purpose' => 'School equipment purchase.',
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

        return $order;
    }

    private function receive(
        PurchaseOrder $order,
        InventoryWarehouse $warehouse,
        User $storesPerson,
        int $quantity
    ): void {
        $this->actingAs($storesPerson)
            ->post(route('admin.procurement.goods-received-vouchers.store', $order), [
                'inventory_warehouse_id' => $warehouse->id,
                'received_date' => now()->toDateString(),
                'received_quantities' => [$order->items()->firstOrFail()->id => $quantity],
            ])
            ->assertRedirect();
    }

    private function userWithRole(School $school, string $role): User
    {
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
