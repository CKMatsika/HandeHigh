<?php

namespace App\Services;

use App\Models\Account;
use App\Models\GoodsReceivedVoucherItem;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\InventorySetting;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\ProcurementSupplierInvoice;
use App\Models\School;
use App\Models\SchoolFixedAsset;
use App\Models\SchoolFixedAssetAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SchoolFixedAssetService
{
    public function shouldCapitalize(InventoryItem $item, float $unitCost): bool
    {
        if ($item->item_type !== 'asset') {
            return false;
        }

        $threshold = InventorySetting::where('school_id', $item->school_id)
            ->value('asset_capitalization_threshold');

        return $threshold === null || $unitCost >= (float) $threshold;
    }

    public function recordReceivedAssets(
        GoodsReceivedVoucherItem $receivedItem,
        InventoryWarehouse $warehouse,
        User $receiver
    ): void {
        $receivedItem->loadMissing(['item.category.assetAccount', 'voucher']);
        $item = $receivedItem->item;
        if ($item->item_type !== 'asset') {
            return;
        }

        $quantity = (float) $receivedItem->quantity_received;
        if ($quantity <= 0 || abs($quantity - round($quantity)) > 0.00005) {
            throw ValidationException::withMessages([
                'received_quantities' => "{$item->name} is an individually tracked asset and must be received in whole units.",
            ]);
        }

        $capitalized = $this->shouldCapitalize($item, (float) $receivedItem->unit_cost);
        $assetAccount = $capitalized ? $this->categoryAssetAccount($item) : null;

        for ($index = 0; $index < (int) round($quantity); $index++) {
            SchoolFixedAsset::create([
                'school_id' => $receivedItem->voucher->school_id,
                'inventory_item_id' => $item->id,
                'goods_received_voucher_item_id' => $receivedItem->id,
                'inventory_warehouse_id' => $warehouse->id,
                'asset_account_id' => $assetAccount?->id,
                'created_by' => $receiver->id,
                'asset_number' => 'FA-'.Str::upper((string) Str::ulid()),
                'status' => 'in_store',
                'is_capitalized' => $capitalized,
                'purchase_cost' => $receivedItem->unit_cost,
                'capitalized_cost' => $capitalized ? $receivedItem->unit_cost : 0,
                'acquired_date' => $receivedItem->voucher->received_date,
                'location' => $warehouse->name,
            ]);
        }
    }

    public function applySupplierInvoice(ProcurementSupplierInvoice $invoice): void
    {
        $invoice->loadMissing('items.goodsReceivedItem.item');

        foreach ($invoice->items as $invoiceItem) {
            $receivedItem = $invoiceItem->goodsReceivedItem;
            $item = $receivedItem->item;
            if ($item->item_type !== 'asset') {
                continue;
            }

            $quantity = (float) $invoiceItem->quantity_invoiced;
            if (abs($quantity - round($quantity)) > 0.00005) {
                throw ValidationException::withMessages([
                    'invoice' => "{$item->name} is individually tracked and must be invoiced in whole units.",
                ]);
            }

            $assets = SchoolFixedAsset::where('goods_received_voucher_item_id', $receivedItem->id)
                ->whereNull('supplier_invoice_item_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->limit((int) round($quantity))
                ->get();
            if ($assets->count() !== (int) round($quantity)) {
                throw ValidationException::withMessages([
                    'invoice' => "There are not enough uninvoiced asset records for {$item->name}.",
                ]);
            }

            foreach ($assets as $asset) {
                $asset->update([
                    'supplier_invoice_item_id' => $invoiceItem->id,
                    'purchase_cost' => $invoiceItem->unit_price,
                    'capitalized_cost' => $asset->is_capitalized ? $invoiceItem->unit_price : 0,
                ]);
            }
        }
    }

    public function updateCustody(
        SchoolFixedAsset $asset,
        ?User $custodian,
        string $serialNumber,
        string $location,
        ?string $notes,
        User $assignedBy
    ): SchoolFixedAsset {
        return DB::transaction(function () use ($asset, $custodian, $serialNumber, $location, $notes, $assignedBy) {
            $record = SchoolFixedAsset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $record->school_id === (int) $assignedBy->school_id, 404);
            abort_unless($record->status !== 'disposed', 409);
            if ($custodian) {
                abort_unless((int) $custodian->school_id === (int) $record->school_id, 404);
            }

            $fromCustodianId = $record->custodian_user_id;
            $fromLocation = $record->location;
            $isStoreReturn = $fromCustodianId !== null && $custodian === null;
            $isAssignment = $fromCustodianId === null && $custodian !== null;
            $unitCost = 0.0;
            $assignment = null;

            if (! $record->is_capitalized && ($isAssignment || $isStoreReturn)) {
                $unitCost = $isAssignment
                    ? $this->removeLowValueAssetFromStock($record)
                    : $this->returnLowValueAssetToStock($record);
            }

            if ($fromCustodianId !== $custodian?->id || $fromLocation !== $location) {
                $action = $isAssignment
                    ? 'assigned'
                    : ($isStoreReturn ? 'returned' : ($custodian ? 'transferred' : 'location_changed'));
                $assignment = SchoolFixedAssetAssignment::create([
                    'school_fixed_asset_id' => $record->id,
                    'from_user_id' => $fromCustodianId,
                    'to_user_id' => $custodian?->id,
                    'assigned_by' => $assignedBy->id,
                    'action' => $action,
                    'from_location' => $fromLocation,
                    'to_location' => $location,
                    'unit_cost' => $unitCost,
                    'notes' => $notes,
                    'occurred_at' => now(),
                ]);
            }

            $record->update([
                'custodian_user_id' => $custodian?->id,
                'status' => $custodian ? 'assigned' : 'in_store',
                'serial_number' => $serialNumber !== '' ? $serialNumber : null,
                'location' => $location,
                'notes' => $notes,
            ]);

            if ($assignment && ! $record->is_capitalized && ($isAssignment || $isStoreReturn)) {
                $this->postLowValueAssetAccounting($record, $assignment, $isStoreReturn, $unitCost);
            }

            return $record;
        });
    }

    public function assetAccountFor(GoodsReceivedVoucherItem $receivedItem): ?Account
    {
        $receivedItem->loadMissing(['item.category.assetAccount']);
        if (! $this->shouldCapitalize($receivedItem->item, (float) $receivedItem->unit_cost)) {
            return null;
        }

        return $this->categoryAssetAccount($receivedItem->item);
    }

    private function categoryAssetAccount(InventoryItem $item): Account
    {
        $account = $item->category->assetAccount;
        if (! $account
            || (int) $account->school_id !== (int) $item->school_id
            || $account->type !== 'asset'
            || $account->category !== 'fixed_asset'
            || ! $account->is_active
            || ! $account->is_postable) {
            throw ValidationException::withMessages([
                'asset_account_id' => "Map an active, postable fixed-asset account to the {$item->category->name} category before receiving capitalized assets.",
            ]);
        }

        return $account;
    }

    private function removeLowValueAssetFromStock(SchoolFixedAsset $asset): float
    {
        $item = InventoryItem::where('school_id', $asset->school_id)
            ->whereKey($asset->inventory_item_id)
            ->lockForUpdate()
            ->firstOrFail();
        $balance = InventoryBalance::where('school_id', $asset->school_id)
            ->where('inventory_item_id', $asset->inventory_item_id)
            ->where('inventory_warehouse_id', $asset->inventory_warehouse_id)
            ->lockForUpdate()
            ->first();
        if (! $balance || (float) $balance->quantity_on_hand < 1) {
            throw ValidationException::withMessages([
                'asset' => "{$item->name} is no longer available in its store warehouse.",
            ]);
        }

        $unitCost = (float) $balance->average_unit_cost;
        if ($unitCost <= 0) {
            throw ValidationException::withMessages([
                'asset' => "{$item->name} has no stock cost and cannot be assigned until its inventory value is established.",
            ]);
        }
        $quantityAfter = round((float) $balance->quantity_on_hand - 1, 4);
        $balance->update(['quantity_on_hand' => $quantityAfter]);
        InventoryTransaction::create([
            'school_id' => $asset->school_id,
            'inventory_item_id' => $asset->inventory_item_id,
            'inventory_warehouse_id' => $asset->inventory_warehouse_id,
            'movement_type' => 'issue',
            'quantity_delta' => -1,
            'unit_cost' => $unitCost,
            'quantity_after' => $quantityAfter,
            'average_unit_cost_after' => $unitCost,
            'reference_type' => SchoolFixedAsset::class,
            'reference_id' => $asset->id,
            'notes' => 'Low-value asset assigned to a custodian',
            'recorded_by' => auth()->id(),
            'occurred_at' => now(),
        ]);

        return $unitCost;
    }

    private function returnLowValueAssetToStock(SchoolFixedAsset $asset): float
    {
        $item = InventoryItem::where('school_id', $asset->school_id)
            ->whereKey($asset->inventory_item_id)
            ->lockForUpdate()
            ->firstOrFail();
        $assignment = $asset->assignments()
            ->where('action', 'assigned')
            ->latest('occurred_at')
            ->first();
        $unitCost = (float) ($assignment?->unit_cost ?? $asset->purchase_cost);
        if ($unitCost <= 0) {
            throw ValidationException::withMessages([
                'asset' => "{$item->name} has no recorded issue cost and cannot be returned to stock.",
            ]);
        }

        $balance = InventoryBalance::where('school_id', $asset->school_id)
            ->where('inventory_item_id', $asset->inventory_item_id)
            ->where('inventory_warehouse_id', $asset->inventory_warehouse_id)
            ->lockForUpdate()
            ->first();
        if (! $balance) {
            $balance = InventoryBalance::create([
                'school_id' => $asset->school_id,
                'inventory_item_id' => $asset->inventory_item_id,
                'inventory_warehouse_id' => $asset->inventory_warehouse_id,
                'quantity_on_hand' => 0,
                'average_unit_cost' => 0,
                'reorder_level' => 0,
            ]);
        }

        $oldQuantity = (float) $balance->quantity_on_hand;
        $oldCost = (float) $balance->average_unit_cost;
        $newQuantity = $oldQuantity + 1;
        $newAverageCost = round((($oldQuantity * $oldCost) + $unitCost) / $newQuantity, 4);
        $balance->update([
            'quantity_on_hand' => $newQuantity,
            'average_unit_cost' => $newAverageCost,
        ]);
        InventoryTransaction::create([
            'school_id' => $asset->school_id,
            'inventory_item_id' => $asset->inventory_item_id,
            'inventory_warehouse_id' => $asset->inventory_warehouse_id,
            'movement_type' => 'receipt',
            'quantity_delta' => 1,
            'unit_cost' => $unitCost,
            'quantity_after' => $newQuantity,
            'average_unit_cost_after' => $newAverageCost,
            'reference_type' => SchoolFixedAsset::class,
            'reference_id' => $asset->id,
            'notes' => 'Low-value asset returned to store',
            'recorded_by' => auth()->id(),
            'occurred_at' => now(),
        ]);

        return $unitCost;
    }

    private function postLowValueAssetAccounting(
        SchoolFixedAsset $asset,
        SchoolFixedAssetAssignment $assignment,
        bool $isReturn,
        float $unitCost
    ): void {
        $school = School::findOrFail($asset->school_id);
        $accounts = app(ProcurementAccountingService::class)->procurementAccounts($school);
        $expenseAccount = $asset->item->category->issueExpenseAccount;
        if (! $expenseAccount) {
            app(AccountingService::class)->ensureChartOfAccountsExist($school);
            app(\Database\Seeders\ChartOfAccountsSeeder::class)->seedAccountsForSchool($school);
            $expenseAccount = Account::where('school_id', $school->id)->where('code', '6500')->first();
        }
        if (! $expenseAccount
            || (int) $expenseAccount->school_id !== (int) $school->id
            || $expenseAccount->type !== 'expense'
            || ! $expenseAccount->is_active
            || ! $expenseAccount->is_postable) {
            throw new InvalidArgumentException('A valid active, postable expense account is required for a low-value asset assignment.');
        }

        $memo = $isReturn ? 'Return low-value asset to inventory' : 'Assign low-value asset to custodian';
        $journalAmount = round($unitCost, 2);
        $entries = $isReturn
            ? [
                ['account_id' => $accounts['inventory']->id, 'entry_type' => 'debit', 'amount' => $journalAmount, 'memo' => $memo],
                ['account_id' => $expenseAccount->id, 'entry_type' => 'credit', 'amount' => $journalAmount, 'memo' => $memo],
            ]
            : [
                ['account_id' => $expenseAccount->id, 'entry_type' => 'debit', 'amount' => $journalAmount, 'memo' => $memo],
                ['account_id' => $accounts['inventory']->id, 'entry_type' => 'credit', 'amount' => $journalAmount, 'memo' => $memo],
            ];

        app(AccountingService::class)->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => now()->toDateString(),
            'reference_number' => $asset->asset_number,
            'description' => $memo.' - '.$asset->asset_number,
            'source_type' => 'school_fixed_asset_assignment',
            'source_id' => $assignment->id,
            'status' => 'posted',
            'created_by' => auth()->id(),
            'entries' => $entries,
        ]);
    }
}
