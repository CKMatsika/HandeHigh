<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventorySetting;
use App\Models\InventoryWarehouse;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\School;
use App\Models\SchoolFixedAsset;
use App\Models\SchoolFixedAssetDepreciation;
use App\Models\SchoolFixedAssetMaintenance;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SchoolFixedAssetService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FixedAssetDepreciationDisposalMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    public function test_can_update_depreciation_parameters_and_calculate_straight_line_and_reducing_balance(): void
    {
        [$school, $asset, $storesPerson] = $this->createCapitalizedAssetFixture('DEP-TEST', 1200);

        // Update to Straight-Line: 5 years, $200 salvage value
        $this->actingAs($storesPerson)
            ->put(route('admin.inventory.fixed-assets.depreciation.update', $asset), [
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5,
                'salvage_value' => 200,
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame('straight_line', $asset->depreciation_method);
        $this->assertEquals(5.0, (float) $asset->useful_life_years);
        $this->assertEquals(200.0, (float) $asset->salvage_value);
        $this->assertEquals(0.2, (float) $asset->depreciation_rate);

        $service = app(SchoolFixedAssetService::class);
        // Depreciable cost = 1200 - 200 = 1000. Annual = 1000 / 5 = 200. Monthly = 200 / 12 = 16.67
        $monthlyDepr = $service->calculateDepreciationAmount($asset);
        $this->assertEquals(16.67, $monthlyDepr);

        // Test reducing balance: useful life 5 years -> rate = 2 / 5 = 0.40 (40% per annum)
        $asset->update([
            'depreciation_method' => 'reducing_balance',
            'depreciation_rate' => 0.40,
            'book_value' => 1200,
        ]);
        // Monthly = 1200 * (0.40 / 12) = 40.00
        $rbMonthlyDepr = $service->calculateDepreciationAmount($asset);
        $this->assertEquals(40.0, $rbMonthlyDepr);

        // Cannot depreciate below salvage value
        $asset->update([
            'depreciation_method' => 'straight_line',
            'book_value' => 210,
            'accumulated_depreciation' => 990,
        ]);
        // Even though straight line calculates 16.67, only 10.00 can be taken to reach 200 salvage
        $cappedDepr = $service->calculateDepreciationAmount($asset);
        $this->assertEquals(10.0, $cappedDepr);

        $asset->update([
            'book_value' => 200,
            'accumulated_depreciation' => 1000,
        ]);
        $this->assertEquals(0.0, $service->calculateDepreciationAmount($asset));
    }

    public function test_batch_depreciation_posts_balanced_gl_journal_entries(): void
    {
        [$school, $asset1, $storesPerson] = $this->createCapitalizedAssetFixture('RUN1', 1200);
        $asset1->update([
            'depreciation_method' => 'straight_line',
            'useful_life_years' => 5,
            'salvage_value' => 0,
            'depreciation_rate' => 0.20,
            'book_value' => 1200,
            'accumulated_depreciation' => 0,
        ]);

        [$schoolB, $assetB, $storesPersonB] = $this->createCapitalizedAssetFixture('RUNB', 1200);
        $assetB->update([
            'depreciation_method' => 'straight_line',
            'useful_life_years' => 5,
            'salvage_value' => 0,
            'depreciation_rate' => 0.20,
            'book_value' => 1200,
        ]);

        // Run batch for School A
        $response = $this->actingAs($storesPerson)
            ->post(route('admin.inventory.fixed-assets.depreciation.run'), [
                'period_name' => 'October 2026',
                'depreciation_date' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $asset1->refresh();
        $this->assertEquals(20.0, (float) $asset1->accumulated_depreciation); // 1200 / 60 months = 20.00
        $this->assertEquals(1180.0, (float) $asset1->book_value);
        $this->assertNotNull($asset1->last_depreciation_date);

        // School B asset should not be touched
        $assetB->refresh();
        $this->assertEquals(0.0, (float) $assetB->accumulated_depreciation);

        // Verify depreciation record
        $deprRecord = SchoolFixedAssetDepreciation::where('school_fixed_asset_id', $asset1->id)->firstOrFail();
        $this->assertEquals(20.0, (float) $deprRecord->depreciation_amount);
        $this->assertEquals(1180.0, (float) $deprRecord->book_value_after);
        $this->assertNotNull($deprRecord->journal_batch_id);

        // Verify balanced General Ledger Journal Batch
        $journal = JournalBatch::findOrFail($deprRecord->journal_batch_id);
        $this->assertSame($school->id, $journal->school_id);

        $totalDebits = (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('entry_type', 'debit')
            ->sum('amount');
        $totalCredits = (float) JournalEntry::where('journal_batch_id', $journal->id)
            ->where('entry_type', 'credit')
            ->sum('amount');

        $this->assertEquals(20.0, $totalDebits);
        $this->assertEquals(20.0, $totalCredits);
        $this->assertEquals($totalDebits, $totalCredits);

        // Depreciation expense account (7000) debited
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $journal->id,
            'account_id' => Account::where('school_id', $school->id)->where('code', '7000')->value('id'),
            'entry_type' => 'debit',
            'amount' => 20.0,
        ]);

        // Accumulated depreciation account (2302) credited
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $journal->id,
            'account_id' => Account::where('school_id', $school->id)->where('code', '2302')->value('id'),
            'entry_type' => 'credit',
            'amount' => 20.0,
        ]);
    }

    public function test_asset_transfer_updates_custody_and_records_assignment_audit(): void
    {
        [$school, $asset, $storesPerson] = $this->createCapitalizedAssetFixture('TRF-TEST', 1500);
        $newTeacher = $this->userWithRole($school, 'teacher');
        $newWarehouse = InventoryWarehouse::create([
            'school_id' => $school->id,
            'code' => 'LAB-STORE',
            'name' => 'Physics Laboratory Store',
            'is_active' => true,
        ]);

        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.fixed-assets.transfer', $asset), [
                'to_user_id' => $newTeacher->id,
                'to_warehouse_id' => $newWarehouse->id,
                'to_location' => 'Lab B-204',
                'notes' => 'Transferred for Form 4 Physics practical exams.',
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame($newTeacher->id, $asset->custodian_user_id);
        $this->assertSame($newWarehouse->id, $asset->warehouse_id);
        $this->assertSame('Lab B-204', $asset->location);
        $this->assertSame('assigned', $asset->status);

        // Audit assignment recorded
        $this->assertDatabaseHas('school_fixed_asset_assignments', [
            'school_fixed_asset_id' => $asset->id,
            'action' => 'transfer',
            'from_location' => 'Main Warehouse Room 1',
            'to_location' => 'Lab B-204',
            'to_user_id' => $newTeacher->id,
            'assigned_by' => $storesPerson->id,
            'notes' => 'Transferred for Form 4 Physics practical exams.',
        ]);
    }

    public function test_asset_disposal_by_sale_with_gain_posts_balanced_gl_journal(): void
    {
        [$school, $asset, $storesPerson] = $this->createCapitalizedAssetFixture('DISP-SALE', 1000);
        
        // Asset has cost 1000, acc depr 400 => Net Book Value = 600
        $asset->update([
            'accumulated_depreciation' => 400,
            'book_value' => 600,
        ]);

        $bankAccount = Account::where('school_id', $school->id)->where('code', '1100')->firstOrFail();

        // Sold for $750 => Gain on disposal = $150 ($750 - $600)
        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.fixed-assets.dispose', $asset), [
                'disposal_date' => now()->toDateString(),
                'disposal_type' => 'sale',
                'disposal_proceeds' => 750,
                'bank_account_id' => $bankAccount->id,
                'disposal_notes' => 'Sold to local college via board tender.',
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame('disposed', $asset->status);
        $this->assertSame('sale', $asset->disposal_type);
        $this->assertEquals(750.0, (float) $asset->disposal_proceeds);
        $this->assertEquals(0.0, (float) $asset->book_value);
        $this->assertNotNull($asset->disposal_journal_batch_id);
        $this->assertSame($storesPerson->id, $asset->disposed_by);

        // Verify Journal Entries
        $journal = JournalBatch::findOrFail($asset->disposal_journal_batch_id);
        $totalDebits = (float) JournalEntry::where('journal_batch_id', $journal->id)->where('entry_type', 'debit')->sum('amount');
        $totalCredits = (float) JournalEntry::where('journal_batch_id', $journal->id)->where('entry_type', 'credit')->sum('amount');

        // Debits: Bank Proceeds (750) + Acc Depr (400) = 1150
        // Credits: Fixed Asset Cost (1000) + Gain on Disposal (150) = 1150
        $this->assertEquals(1150.0, $totalDebits);
        $this->assertEquals(1150.0, $totalCredits);

        // Fixed asset cost credited
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $journal->id,
            'account_id' => $asset->asset_account_id,
            'entry_type' => 'credit',
            'amount' => 1000.0,
        ]);

        // Bank debited with proceeds
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $journal->id,
            'account_id' => $bankAccount->id,
            'entry_type' => 'debit',
            'amount' => 750.0,
        ]);

        // Accumulated depreciation debited
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $journal->id,
            'account_id' => Account::where('school_id', $school->id)->where('code', '2302')->value('id'),
            'entry_type' => 'debit',
            'amount' => 400.0,
        ]);
    }

    public function test_asset_disposal_by_scrap_with_loss_posts_balanced_gl_journal(): void
    {
        [$school, $asset, $storesPerson] = $this->createCapitalizedAssetFixture('DISP-SCRAP', 800);
        
        // Asset has cost 800, acc depr 200 => Net Book Value = 600
        $asset->update([
            'accumulated_depreciation' => 200,
            'book_value' => 600,
        ]);

        // Scrapped with 0 proceeds => Loss on disposal = 600
        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.fixed-assets.dispose', $asset), [
                'disposal_date' => now()->toDateString(),
                'disposal_type' => 'scrap',
                'disposal_proceeds' => 0,
                'disposal_notes' => 'Irreparably damaged in flood.',
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame('disposed', $asset->status);
        $this->assertSame('scrap', $asset->disposal_type);
        $this->assertEquals(0.0, (float) $asset->book_value);

        $journal = JournalBatch::findOrFail($asset->disposal_journal_batch_id);
        $totalDebits = (float) JournalEntry::where('journal_batch_id', $journal->id)->where('entry_type', 'debit')->sum('amount');
        $totalCredits = (float) JournalEntry::where('journal_batch_id', $journal->id)->where('entry_type', 'credit')->sum('amount');

        // Debits: Acc Depr (200) + Loss on Disposal (600) = 800
        // Credits: Fixed Asset Cost (800) = 800
        $this->assertEquals(800.0, $totalDebits);
        $this->assertEquals(800.0, $totalCredits);
    }

    public function test_asset_maintenance_scheduling_and_completion_with_gl_journal(): void
    {
        [$school, $asset, $storesPerson] = $this->createCapitalizedAssetFixture('MAINT-TEST', 2000);
        $technician = $this->userWithRole($school, 'teacher');

        // 1. Schedule maintenance
        $this->actingAs($storesPerson)
            ->post(route('admin.inventory.fixed-assets.maintenance.store', $asset), [
                'maintenance_type' => 'preventive',
                'title' => 'Annual Calibration & Laser Tuning',
                'description' => 'Precision alignment of optics and sensor recalibration.',
                'service_provider' => 'Precision Instruments Zimbabwe',
                'technician_user_id' => $technician->id,
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'estimated_cost' => 150.00,
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame('under_maintenance', $asset->status);

        $maintenance = SchoolFixedAssetMaintenance::where('school_fixed_asset_id', $asset->id)->firstOrFail();
        $this->assertSame('scheduled', $maintenance->status);
        $this->assertSame('Annual Calibration & Laser Tuning', $maintenance->title);

        // 2. Complete maintenance with GL posting
        $expenseAccount = Account::where('school_id', $school->id)->where('code', '6400')->firstOrFail();
        $bankAccount = Account::where('school_id', $school->id)->where('code', '1100')->firstOrFail();

        $this->actingAs($storesPerson)
            ->put(route('admin.inventory.fixed-assets.maintenance.complete', $maintenance), [
                'completed_date' => now()->toDateString(),
                'actual_cost' => 175.50,
                'post_to_gl' => '1',
                'expense_account_id' => $expenseAccount->id,
                'payment_account_id' => $bankAccount->id,
                'next_service_due' => now()->addMonths(6)->toDateString(),
                'notes' => 'Optics replaced and certified compliant.',
            ])
            ->assertRedirect();

        $maintenance->refresh();
        $this->assertSame('completed', $maintenance->status);
        $this->assertEquals(175.50, (float) $maintenance->actual_cost);
        $this->assertNotNull($maintenance->journal_batch_id);
        $this->assertNotNull($maintenance->next_service_due);

        $asset->refresh();
        $this->assertSame('assigned', $asset->status);

        // Verify balanced journal entry for maintenance
        $journal = JournalBatch::findOrFail($maintenance->journal_batch_id);
        $totalDebits = (float) JournalEntry::where('journal_batch_id', $journal->id)->where('entry_type', 'debit')->sum('amount');
        $totalCredits = (float) JournalEntry::where('journal_batch_id', $journal->id)->where('entry_type', 'credit')->sum('amount');

        $this->assertEquals(175.50, $totalDebits);
        $this->assertEquals(175.50, $totalCredits);

        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $journal->id,
            'account_id' => $expenseAccount->id,
            'entry_type' => 'debit',
            'amount' => 175.50,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $journal->id,
            'account_id' => $bankAccount->id,
            'entry_type' => 'credit',
            'amount' => 175.50,
        ]);
    }

    public function test_tenant_isolation_prevents_unauthorized_cross_school_asset_operations(): void
    {
        [$schoolA, $assetA, $userA] = $this->createCapitalizedAssetFixture('SCH-A', 1000);
        [$schoolB, $assetB, $userB] = $this->createCapitalizedAssetFixture('SCH-B', 1000);

        // User B cannot access School A asset
        $this->actingAs($userB)
            ->get(route('admin.inventory.fixed-assets.show', $assetA))
            ->assertNotFound();

        // User B cannot transfer School A asset
        $this->actingAs($userB)
            ->post(route('admin.inventory.fixed-assets.transfer', $assetA), [
                'to_location' => 'Hacked Room',
            ])
            ->assertNotFound();

        // User B cannot dispose School A asset
        $this->actingAs($userB)
            ->post(route('admin.inventory.fixed-assets.dispose', $assetA), [
                'disposal_date' => now()->toDateString(),
                'disposal_type' => 'scrap',
            ])
            ->assertNotFound();
    }

    private function createCapitalizedAssetFixture(string $prefix, float $cost): array
    {
        $school = School::create(['name' => "{$prefix} School", 'code' => $prefix]);
        (new ChartOfAccountsSeeder)->seedAccountsForSchool($school);

        $assetAccount = Account::where('school_id', $school->id)->where('code', '2301')->firstOrFail();
        $expenseAccount = Account::where('school_id', $school->id)->where('code', '6500')->firstOrFail();

        $category = InventoryCategory::create([
            'school_id' => $school->id,
            'code' => "{$prefix}-CAT",
            'name' => 'Lab Equipment',
            'issue_expense_account_id' => $expenseAccount->id,
            'asset_account_id' => $assetAccount->id,
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $school->id,
            'inventory_category_id' => $category->id,
            'code' => "{$prefix}-ITEM",
            'name' => "{$prefix} Digital Microscope",
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

        InventorySetting::create([
            'school_id' => $school->id,
            'valuation_method' => 'weighted_average',
            'asset_capitalization_threshold' => 500,
        ]);

        $storesPerson = $this->userWithRole($school, 'stores-person');

        $vendor = Vendor::create([
            'school_id' => $school->id,
            'code' => "{$prefix}-VND",
            'name' => "{$prefix} Supplier",
            'vendor_type' => 'supplier',
            'payment_terms' => 'net_30',
            'is_active' => true,
        ]);

        $requisition = \App\Models\ProcurementRequisition::create([
            'school_id' => $school->id,
            'requisition_number' => 'REQ-'.fake()->unique()->numerify('######'),
            'requested_by' => $storesPerson->id,
            'purpose' => 'Asset acquisition',
            'status' => 'ordered',
        ]);

        $reqItem = $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $order = \App\Models\PurchaseOrder::create([
            'school_id' => $school->id,
            'procurement_requisition_id' => $requisition->id,
            'vendor_id' => $vendor->id,
            'purchase_order_number' => 'PO-'.fake()->unique()->numerify('######'),
            'status' => 'issued',
            'order_date' => now()->toDateString(),
            'created_by' => $storesPerson->id,
            'issued_by' => $storesPerson->id,
            'issued_at' => now(),
        ]);

        $orderItem = $order->items()->create([
            'procurement_requisition_item_id' => $reqItem->id,
            'inventory_item_id' => $item->id,
            'quantity_ordered' => 1,
            'unit_cost' => $cost,
            'line_total' => $cost,
        ]);

        $voucher = \App\Models\GoodsReceivedVoucher::create([
            'school_id' => $school->id,
            'purchase_order_id' => $order->id,
            'inventory_warehouse_id' => $warehouse->id,
            'voucher_number' => 'GRV-'.fake()->unique()->numerify('#####'),
            'received_date' => now()->toDateString(),
            'received_by' => $storesPerson->id,
            'posted_at' => now(),
        ]);

        $voucherItem = $voucher->items()->create([
            'purchase_order_item_id' => $orderItem->id,
            'inventory_item_id' => $item->id,
            'quantity_received' => 1,
            'unit_cost' => $cost,
            'line_total' => $cost,
        ]);

        $asset = SchoolFixedAsset::create([
            'school_id' => $school->id,
            'inventory_item_id' => $item->id,
            'goods_received_voucher_item_id' => $voucherItem->id,
            'inventory_warehouse_id' => $warehouse->id,
            'custodian_user_id' => $storesPerson->id,
            'asset_account_id' => $assetAccount->id,
            'asset_number' => 'AST-'.fake()->unique()->numerify('#####'),
            'serial_number' => 'SN-'.fake()->unique()->numerify('#####'),
            'acquired_date' => now()->subMonths(6)->toDateString(),
            'purchase_cost' => $cost,
            'capitalized_cost' => $cost,
            'is_capitalized' => true,
            'status' => 'assigned',
            'location' => 'Main Warehouse Room 1',
            'depreciation_method' => 'straight_line',
            'useful_life_years' => 5,
            'salvage_value' => 0,
            'depreciation_rate' => 0.20,
            'accumulated_depreciation' => 0,
            'book_value' => $cost,
        ]);

        return [$school, $asset, $storesPerson];
    }

    private function userWithRole(School $school, string $role): User
    {
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
