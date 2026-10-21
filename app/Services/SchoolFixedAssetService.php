<?php

namespace App\Services;

use App\Models\Account;
use App\Models\GoodsReceivedVoucherItem;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\InventorySetting;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\JournalBatch;
use App\Models\ProcurementSupplierInvoice;
use App\Models\School;
use App\Models\SchoolFixedAsset;
use App\Models\SchoolFixedAssetAssignment;
use App\Models\SchoolFixedAssetDepreciation;
use App\Models\SchoolFixedAssetMaintenance;
use App\Models\User;
use Carbon\Carbon;
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

        $school = School::findOrFail($receivedItem->voucher->school_id);
        $capitalized = $this->shouldCapitalize($item, (float) $receivedItem->unit_cost);
        $assetAccount = $capitalized ? $this->categoryAssetAccount($item) : null;
        $accumAccount = $capitalized ? $this->resolveAccumulatedDepreciationAccount($item, $school) : null;
        $deprecAccount = $capitalized ? $this->resolveDepreciationExpenseAccount($item, $school) : null;

        for ($index = 0; $index < (int) round($quantity); $index++) {
            SchoolFixedAsset::create([
                'school_id' => $receivedItem->voucher->school_id,
                'inventory_item_id' => $item->id,
                'goods_received_voucher_item_id' => $receivedItem->id,
                'inventory_warehouse_id' => $warehouse->id,
                'asset_account_id' => $assetAccount?->id,
                'accumulated_depreciation_account_id' => $accumAccount?->id,
                'depreciation_expense_account_id' => $deprecAccount?->id,
                'created_by' => $receiver->id,
                'asset_number' => 'FA-'.Str::upper((string) Str::ulid()),
                'status' => 'in_store',
                'is_capitalized' => $capitalized,
                'purchase_cost' => $receivedItem->unit_cost,
                'capitalized_cost' => $capitalized ? $receivedItem->unit_cost : 0,
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5.00,
                'salvage_value' => 0.00,
                'accumulated_depreciation' => 0.00,
                'book_value' => $capitalized ? $receivedItem->unit_cost : 0,
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
                $cost = $asset->is_capitalized ? $invoiceItem->unit_price : 0;
                $bookValue = $asset->is_capitalized
                    ? max((float) $asset->salvage_value, (float) $invoiceItem->unit_price - (float) $asset->accumulated_depreciation)
                    : 0;

                $asset->update([
                    'supplier_invoice_item_id' => $invoiceItem->id,
                    'purchase_cost' => $invoiceItem->unit_price,
                    'capitalized_cost' => $cost,
                    'book_value' => $bookValue,
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

    public function updateDepreciationParameters(
        SchoolFixedAsset $asset,
        array $data,
        User $user
    ): SchoolFixedAsset {
        $record = SchoolFixedAsset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
        abort_unless((int) $record->school_id === (int) $user->school_id, 404);
        abort_unless(! in_array($record->status, ['disposed', 'written_off']), 409);

        $school = School::findOrFail($record->school_id);
        $method = $data['depreciation_method'] ?? $record->depreciation_method;
        $usefulLife = isset($data['useful_life_years']) ? (float) $data['useful_life_years'] : (float) $record->useful_life_years;
        $salvageValue = isset($data['salvage_value']) ? round((float) $data['salvage_value'], 2) : (float) $record->salvage_value;
        $rate = isset($data['depreciation_rate']) ? (float) $data['depreciation_rate'] : $record->depreciation_rate;

        $accumulatedAccountId = ! empty($data['accumulated_depreciation_account_id'])
            ? $data['accumulated_depreciation_account_id']
            : ($record->accumulated_depreciation_account_id ?: $this->resolveAccumulatedDepreciationAccount($record, $school)->id);

        $expenseAccountId = ! empty($data['depreciation_expense_account_id'])
            ? $data['depreciation_expense_account_id']
            : ($record->depreciation_expense_account_id ?: $this->resolveDepreciationExpenseAccount($record, $school)->id);

        $record->update([
            'depreciation_method' => $method,
            'useful_life_years' => $usefulLife,
            'salvage_value' => $salvageValue,
            'depreciation_rate' => $rate,
            'accumulated_depreciation_account_id' => $accumulatedAccountId,
            'depreciation_expense_account_id' => $expenseAccountId,
            'book_value' => max($salvageValue, round((float) $record->capitalized_cost - (float) $record->accumulated_depreciation, 2)),
        ]);

        return $record;
    }

    public function calculateDepreciationAmount(SchoolFixedAsset $asset, ?Carbon $asOfDate = null): float
    {
        if (! $asset->is_capitalized || in_array($asset->status, ['disposed', 'written_off'])) {
            return 0.0;
        }

        $cost = (float) $asset->capitalized_cost;
        $salvage = (float) $asset->salvage_value;
        $accumulated = (float) $asset->accumulated_depreciation;
        $nbv = max($salvage, round($cost - $accumulated, 2));
        $maxDepreciable = max(0.0, round($nbv - $salvage, 2));

        if ($maxDepreciable <= 0.0) {
            return 0.0;
        }

        $method = $asset->depreciation_method ?: 'straight_line';

        if ($method === 'straight_line') {
            $usefulMonths = max(1.0, (float) ($asset->useful_life_years ?: 5) * 12);
            $monthly = round(($cost - $salvage) / $usefulMonths, 2);

            return min($monthly, $maxDepreciable);
        }

        if ($method === 'reducing_balance') {
            $rawRate = (float) ($asset->depreciation_rate ?: (100 / max(1.0, (float) ($asset->useful_life_years ?: 5))));
            $rateFactor = $rawRate > 1.0 ? ($rawRate / 100) : $rawRate;
            $annual = $nbv * $rateFactor;
            $monthly = round($annual / 12, 2);

            return min($monthly, $maxDepreciable);
        }

        return 0.0;
    }

    public function runDepreciationBatch(
        School $school,
        string $periodName,
        Carbon|string $depreciationDate,
        User $user,
        ?array $assetIds = null
    ): array {
        return DB::transaction(function () use ($school, $periodName, $depreciationDate, $user, $assetIds) {
            $date = is_string($depreciationDate) ? Carbon::parse($depreciationDate) : $depreciationDate;

            $query = SchoolFixedAsset::where('school_id', $school->id)
                ->where('is_capitalized', true)
                ->whereNotIn('status', ['disposed', 'written_off'])
                ->lockForUpdate();

            if (! empty($assetIds)) {
                $query->whereIn('id', $assetIds);
            }

            $assets = $query->get();
            $depreciations = [];
            $glDebits = [];
            $glCredits = [];
            $totalAmount = 0.0;

            foreach ($assets as $asset) {
                $amount = $this->calculateDepreciationAmount($asset, $date);
                if ($amount <= 0.0) {
                    continue;
                }

                $bookValueBefore = $asset->net_book_value;
                $newAccumulated = round((float) $asset->accumulated_depreciation + $amount, 2);
                $bookValueAfter = max((float) $asset->salvage_value, round((float) $asset->capitalized_cost - $newAccumulated, 2));

                $expenseAccount = $asset->depreciationExpenseAccount ?: $this->resolveDepreciationExpenseAccount($asset, $school);
                $accumulatedAccount = $asset->accumulatedDepreciationAccount ?: $this->resolveAccumulatedDepreciationAccount($asset, $school);

                $deprecRecord = SchoolFixedAssetDepreciation::create([
                    'school_id' => $school->id,
                    'school_fixed_asset_id' => $asset->id,
                    'depreciation_date' => $date->toDateString(),
                    'period_name' => $periodName,
                    'method' => $asset->depreciation_method ?: 'straight_line',
                    'depreciation_amount' => $amount,
                    'book_value_before' => $bookValueBefore,
                    'book_value_after' => $bookValueAfter,
                    'accumulated_depreciation_after' => $newAccumulated,
                    'notes' => "Monthly depreciation for {$periodName}",
                    'recorded_by' => $user->id,
                ]);

                $asset->update([
                    'accumulated_depreciation' => $newAccumulated,
                    'book_value' => $bookValueAfter,
                    'last_depreciation_date' => $date->toDateString(),
                    'accumulated_depreciation_account_id' => $accumulatedAccount->id,
                    'depreciation_expense_account_id' => $expenseAccount->id,
                ]);

                $depreciations[] = $deprecRecord;
                $totalAmount = round($totalAmount + $amount, 2);

                $glDebits[$expenseAccount->id] = round(($glDebits[$expenseAccount->id] ?? 0.0) + $amount, 2);
                $glCredits[$accumulatedAccount->id] = round(($glCredits[$accumulatedAccount->id] ?? 0.0) + $amount, 2);
            }

            $journalBatch = null;
            if ($totalAmount > 0.0) {
                $entries = [];
                foreach ($glDebits as $accId => $amt) {
                    $entries[] = [
                        'account_id' => $accId,
                        'entry_type' => 'debit',
                        'amount' => $amt,
                        'memo' => "Depreciation expense - {$periodName}",
                    ];
                }
                foreach ($glCredits as $accId => $amt) {
                    $entries[] = [
                        'account_id' => $accId,
                        'entry_type' => 'credit',
                        'amount' => $amt,
                        'memo' => "Accumulated depreciation - {$periodName}",
                    ];
                }

                $journalBatch = app(AccountingService::class)->createJournalBatch([
                    'school_id' => $school->id,
                    'transaction_date' => $date->toDateString(),
                    'reference_number' => 'DEP-'.Str::upper(Str::slug($periodName)).'-'.time(),
                    'description' => "Fixed asset depreciation run - {$periodName}",
                    'source_type' => 'fixed_asset_depreciation',
                    'status' => 'posted',
                    'created_by' => $user->id,
                    'entries' => $entries,
                ]);

                SchoolFixedAssetDepreciation::whereIn('id', collect($depreciations)->pluck('id'))
                    ->update(['journal_batch_id' => $journalBatch->id]);
            }

            return [
                'total_amount' => $totalAmount,
                'assets_count' => count($depreciations),
                'depreciations' => $depreciations,
                'journal_batch' => $journalBatch,
            ];
        });
    }

    public function transferAsset(SchoolFixedAsset $asset, array $data, User $user): SchoolFixedAsset
    {
        return DB::transaction(function () use ($asset, $data, $user) {
            $record = SchoolFixedAsset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $record->school_id === (int) $user->school_id, 404);
            abort_unless(! in_array($record->status, ['disposed', 'written_off']), 409);

            $school = School::findOrFail($record->school_id);
            $custodianId = $data['to_custodian_user_id'] ?? $data['to_user_id'] ?? null;
            $toCustodian = ! empty($custodianId)
                ? $school->users()->findOrFail($custodianId)
                : null;
            $toWarehouse = ! empty($data['to_warehouse_id'])
                ? InventoryWarehouse::where('school_id', $school->id)->findOrFail($data['to_warehouse_id'])
                : $record->warehouse;

            $fromLocation = $record->location;
            $toLocation = $data['to_location'] ?? ($toWarehouse?->name ?: $fromLocation);
            $notes = $data['notes'] ?? null;

            SchoolFixedAssetAssignment::create([
                'school_fixed_asset_id' => $record->id,
                'from_user_id' => $record->custodian_user_id,
                'to_user_id' => $toCustodian?->id,
                'assigned_by' => $user->id,
                'action' => 'transfer',
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'unit_cost' => (float) $record->purchase_cost,
                'notes' => $notes,
                'occurred_at' => now(),
            ]);

            $record->update([
                'location' => $toLocation,
                'custodian_user_id' => $toCustodian?->id,
                'inventory_warehouse_id' => $toWarehouse?->id,
                'status' => $toCustodian ? 'assigned' : 'in_store',
                'notes' => $notes ?: $record->notes,
            ]);

            return $record;
        });
    }

    public function disposeAsset(SchoolFixedAsset $asset, array $data, User $user): SchoolFixedAsset
    {
        return DB::transaction(function () use ($asset, $data, $user) {
            $record = SchoolFixedAsset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $record->school_id === (int) $user->school_id, 404);
            if (in_array($record->status, ['disposed', 'written_off'])) {
                throw ValidationException::withMessages([
                    'disposal' => 'This asset has already been disposed or written off.',
                ]);
            }

            $school = School::findOrFail($record->school_id);
            $disposalDate = $data['disposal_date'] ?? now()->toDateString();
            $disposalType = $data['disposal_type'] ?? 'scrapped';
            $proceeds = round((float) ($data['disposal_proceeds'] ?? $data['proceeds'] ?? 0.0), 2);
            $notes = $data['disposal_notes'] ?? $data['notes'] ?? null;
            $journalBatch = null;

            if ($record->is_capitalized) {
                $cost = round((float) $record->capitalized_cost, 2);
                $acc = round((float) $record->accumulated_depreciation, 2);
                $nbv = max(0.0, round($cost - $acc, 2));
                $gainOrLoss = round($proceeds - $nbv, 2);

                $assetAccount = $record->assetAccount ?: $this->categoryAssetAccount($record->item);
                $accumAccount = $record->accumulatedDepreciationAccount ?: $this->resolveAccumulatedDepreciationAccount($record, $school);

                $entries = [];

                $entries[] = [
                    'account_id' => $assetAccount->id,
                    'entry_type' => 'credit',
                    'amount' => $cost,
                    'memo' => "Asset disposal ({$record->asset_number}) - Cost write-off",
                ];

                if ($acc > 0.0) {
                    $entries[] = [
                        'account_id' => $accumAccount->id,
                        'entry_type' => 'debit',
                        'amount' => $acc,
                        'memo' => "Asset disposal ({$record->asset_number}) - Reverse accumulated depreciation",
                    ];
                }

                if ($proceeds > 0.0) {
                    $proceedsAccountId = $data['proceeds_account_id'] ?? $data['bank_account_id'] ?? null;
                    if (! $proceedsAccountId) {
                        throw ValidationException::withMessages([
                            'proceeds_account_id' => 'Select a cash or bank account to receive disposal proceeds.',
                        ]);
                    }
                    $proceedsAccount = Account::where('school_id', $school->id)->findOrFail($proceedsAccountId);
                    $entries[] = [
                        'account_id' => $proceedsAccount->id,
                        'entry_type' => 'debit',
                        'amount' => $proceeds,
                        'memo' => "Asset disposal ({$record->asset_number}) - Sale proceeds",
                    ];
                }

                if ($gainOrLoss > 0.0) {
                    $gainAccount = $this->resolveDisposalGainAccount($school);
                    $entries[] = [
                        'account_id' => $gainAccount->id,
                        'entry_type' => 'credit',
                        'amount' => $gainOrLoss,
                        'memo' => "Asset disposal ({$record->asset_number}) - Gain on sale",
                    ];
                } elseif ($gainOrLoss < 0.0) {
                    $lossAccount = $this->resolveDisposalLossAccount($school);
                    $entries[] = [
                        'account_id' => $lossAccount->id,
                        'entry_type' => 'debit',
                        'amount' => abs($gainOrLoss),
                        'memo' => "Asset disposal ({$record->asset_number}) - Loss on disposal",
                    ];
                }

                $journalBatch = app(AccountingService::class)->createJournalBatch([
                    'school_id' => $school->id,
                    'transaction_date' => $disposalDate,
                    'reference_number' => 'DISP-'.$record->asset_number,
                    'description' => "Asset disposal - {$record->asset_number} ({$disposalType})",
                    'source_type' => 'school_fixed_asset_disposal',
                    'source_id' => $record->id,
                    'status' => 'posted',
                    'created_by' => $user->id,
                    'entries' => $entries,
                ]);
            }

            $newStatus = 'disposed';

            $record->update([
                'status' => $newStatus,
                'disposal_date' => $disposalDate,
                'disposal_type' => $disposalType,
                'disposal_proceeds' => $proceeds,
                'disposal_notes' => $notes,
                'disposal_journal_batch_id' => $journalBatch?->id,
                'disposed_by' => $user->id,
                'book_value' => 0.00,
            ]);

            SchoolFixedAssetAssignment::create([
                'school_fixed_asset_id' => $record->id,
                'from_user_id' => $record->custodian_user_id,
                'to_user_id' => null,
                'assigned_by' => $user->id,
                'action' => $newStatus,
                'from_location' => $record->location,
                'to_location' => ucfirst($newStatus),
                'unit_cost' => $proceeds,
                'notes' => "Asset {$newStatus} ({$disposalType}). Proceeds: {$proceeds}. Notes: {$notes}",
                'occurred_at' => now(),
            ]);

            return $record;
        });
    }

    public function scheduleMaintenance(SchoolFixedAsset $asset, array $data, User $user): SchoolFixedAssetMaintenance
    {
        $asset = SchoolFixedAsset::whereKey($asset->id)->firstOrFail();
        abort_unless((int) $asset->school_id === (int) $user->school_id, 404);
        abort_unless(! in_array($asset->status, ['disposed', 'written_off']), 409);

        $maintenance = SchoolFixedAssetMaintenance::create([
            'school_id' => $asset->school_id,
            'school_fixed_asset_id' => $asset->id,
            'title' => $data['title'],
            'maintenance_type' => $data['maintenance_type'] ?? 'preventive',
            'priority' => $data['priority'] ?? 'routine',
            'status' => 'scheduled',
            'service_provider' => $data['service_provider'] ?? null,
            'cost' => $data['cost'] ?? $data['estimated_cost'] ?? 0.00,
            'scheduled_date' => $data['scheduled_date'] ?? now()->toDateString(),
            'next_service_due' => $data['next_service_due'] ?? null,
            'description' => $data['description'] ?? null,
            'performed_by' => $data['performed_by'] ?? $data['technician_user_id'] ?? null,
            'recorded_by' => $user->id,
            'expense_account_id' => $data['expense_account_id'] ?? null,
        ]);

        $asset->update(['status' => 'under_maintenance']);

        return $maintenance;
    }

    public function completeMaintenance(
        SchoolFixedAssetMaintenance $maintenance,
        array $data,
        User $user
    ): SchoolFixedAssetMaintenance {
        return DB::transaction(function () use ($maintenance, $data, $user) {
            $record = SchoolFixedAssetMaintenance::whereKey($maintenance->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $record->school_id === (int) $user->school_id, 404);

            $school = School::findOrFail($record->school_id);
            $cost = round((float) ($data['cost'] ?? $data['actual_cost'] ?? $record->cost), 2);
            $completedDate = $data['completed_date'] ?? now()->toDateString();
            $journalBatch = null;

            if ($cost > 0.0 && (! empty($data['post_accounting']) || ! empty($data['post_to_gl']))) {
                $expenseAccount = ! empty($data['expense_account_id'])
                    ? Account::where('school_id', $school->id)->findOrFail($data['expense_account_id'])
                    : $this->resolveMaintenanceExpenseAccount($record->asset, $school);

                $creditAccountId = $data['credit_account_id'] ?? $data['payment_account_id'] ?? null;
                $creditAccount = $creditAccountId
                    ? Account::where('school_id', $school->id)->findOrFail($creditAccountId)
                    : (Account::where('school_id', $school->id)->where('code', '3101')->first()
                        ?: Account::where('school_id', $school->id)->where('code', '1100')->first());

                if ($expenseAccount && $creditAccount) {
                    $journalBatch = app(AccountingService::class)->createJournalBatch([
                        'school_id' => $school->id,
                        'transaction_date' => $completedDate,
                        'reference_number' => 'MAINT-'.$record->id.'-'.time(),
                        'description' => "Asset maintenance expense - {$record->asset->asset_number} ({$record->title})",
                        'source_type' => 'fixed_asset_maintenance',
                        'source_id' => $record->id,
                        'status' => 'posted',
                        'created_by' => $user->id,
                        'entries' => [
                            ['account_id' => $expenseAccount->id, 'entry_type' => 'debit', 'amount' => $cost, 'memo' => "Maintenance - {$record->title}"],
                            ['account_id' => $creditAccount->id, 'entry_type' => 'credit', 'amount' => $cost, 'memo' => "Maintenance cost - {$record->service_provider}"],
                        ],
                    ]);
                }
            }

            $record->update([
                'status' => 'completed',
                'completed_date' => $completedDate,
                'cost' => $cost,
                'next_service_due' => $data['next_service_due'] ?? $record->next_service_due,
                'description' => $data['description'] ?? $data['notes'] ?? $record->description,
                'service_provider' => $data['service_provider'] ?? $record->service_provider,
                'journal_batch_id' => $journalBatch?->id ?? $record->journal_batch_id,
            ]);

            $record->asset->update([
                'status' => $record->asset->custodian_user_id ? 'assigned' : 'in_store',
            ]);

            return $record;
        });
    }

    public function resolveAccumulatedDepreciationAccount(InventoryItem|SchoolFixedAsset $context, School $school): Account
    {
        $assetAccount = $context instanceof SchoolFixedAsset
            ? $context->assetAccount
            : $context->category?->assetAccount;

        $targetCode = '2302';
        if ($assetAccount) {
            $prefix = substr($assetAccount->code, 0, 2);
            if ($prefix === '22') {
                $targetCode = '2202';
            } elseif ($prefix === '24') {
                $targetCode = '2402';
            }
        }

        $account = Account::where('school_id', $school->id)
            ->where('code', $targetCode)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            $account = Account::where('school_id', $school->id)
                ->where('type', 'asset')
                ->where('category', 'fixed_asset')
                ->where('name', 'like', '%Accumulated Depreciation%')
                ->where('is_active', true)
                ->first();
        }

        if (! $account) {
            $account = Account::create([
                'school_id' => $school->id,
                'code' => $targetCode,
                'name' => 'Accumulated Depreciation - Fixed Assets',
                'type' => 'asset',
                'category' => 'fixed_asset',
                'is_active' => true,
                'is_postable' => true,
                'currency' => $school->currency ?: 'USD',
            ]);
        }

        return $account;
    }

    public function resolveDepreciationExpenseAccount(InventoryItem|SchoolFixedAsset $context, School $school): Account
    {
        $account = Account::where('school_id', $school->id)
            ->where('code', '7000')
            ->where('is_active', true)
            ->first();

        if (! $account) {
            $account = Account::where('school_id', $school->id)
                ->where('type', 'expense')
                ->where('name', 'like', '%Depreciation%')
                ->where('is_active', true)
                ->first();
        }

        if (! $account) {
            $account = Account::create([
                'school_id' => $school->id,
                'code' => '7000',
                'name' => 'Depreciation',
                'type' => 'expense',
                'category' => 'other_expense',
                'is_active' => true,
                'is_postable' => true,
                'currency' => $school->currency ?: 'USD',
            ]);
        }

        return $account;
    }

    public function resolveDisposalGainAccount(School $school): Account
    {
        $account = Account::where('school_id', $school->id)
            ->whereIn('code', ['5850', '5810', '5800'])
            ->where('is_active', true)
            ->first();

        if (! $account) {
            $account = Account::create([
                'school_id' => $school->id,
                'code' => '5850',
                'name' => 'Gain on Disposal of Assets',
                'type' => 'revenue',
                'category' => 'other_revenue',
                'is_active' => true,
                'is_postable' => true,
                'currency' => $school->currency ?: 'USD',
            ]);
        }

        return $account;
    }

    public function resolveDisposalLossAccount(School $school): Account
    {
        $account = Account::where('school_id', $school->id)
            ->whereIn('code', ['7050', '7200', '6595'])
            ->where('is_active', true)
            ->first();

        if (! $account) {
            $account = Account::create([
                'school_id' => $school->id,
                'code' => '7050',
                'name' => 'Loss on Disposal of Assets',
                'type' => 'expense',
                'category' => 'other_expense',
                'is_active' => true,
                'is_postable' => true,
                'currency' => $school->currency ?: 'USD',
            ]);
        }

        return $account;
    }

    public function resolveMaintenanceExpenseAccount(SchoolFixedAsset $asset, School $school): Account
    {
        $code = '6402';
        $account = Account::where('school_id', $school->id)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            $account = Account::where('school_id', $school->id)
                ->where('type', 'expense')
                ->where('name', 'like', '%Maintenance%')
                ->where('is_active', true)
                ->first();
        }

        if (! $account) {
            $account = Account::create([
                'school_id' => $school->id,
                'code' => '6400',
                'name' => 'Maintenance and Repairs',
                'type' => 'expense',
                'category' => 'maintenance_expense',
                'is_active' => true,
                'is_postable' => true,
                'currency' => $school->currency ?: 'USD',
            ]);
        }

        return $account;
    }
}
