<?php

namespace App\Services;

use App\Models\GoodsReceivedVoucher;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\ProcurementRequisition;
use App\Models\PurchaseOrder;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryReceivingService
{
    public function receive(
        PurchaseOrder $purchaseOrder,
        InventoryWarehouse $warehouse,
        School $school,
        User $receiver,
        array $quantities,
        string $receivedDate,
        ?string $notes
    ): GoodsReceivedVoucher {
        return DB::transaction(function () use ($purchaseOrder, $warehouse, $school, $receiver, $quantities, $receivedDate, $notes) {
            $order = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $order->school_id === (int) $school->id, 404);
            abort_unless(in_array($order->status, ['issued', 'partially_received'], true), 409);

            $orderItems = $order->items()->with('item')->get()->keyBy('id');
            $providedIds = array_map('intval', array_keys($quantities));
            $expectedIds = $orderItems->keys()->map(fn ($id) => (int) $id)->all();
            sort($providedIds);
            sort($expectedIds);

            if ($providedIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'received_quantities' => 'Receipt quantities must match the purchase order lines.',
                ]);
            }

            $receivingLines = [];
            foreach ($orderItems as $orderItem) {
                $quantity = (float) $quantities[$orderItem->id];
                $previouslyReceived = (float) $orderItem->goodsReceivedItems()->sum('quantity_received');
                $outstanding = (float) $orderItem->quantity_ordered - $previouslyReceived;

                if ($quantity < 0 || $quantity > $outstanding) {
                    throw ValidationException::withMessages([
                        "received_quantities.{$orderItem->id}" => "Quantity cannot exceed the outstanding quantity of {$outstanding}.",
                    ]);
                }

                if ($quantity > 0) {
                    $receivingLines[] = [$orderItem, $quantity];
                }
            }

            if ($receivingLines === []) {
                throw ValidationException::withMessages([
                    'received_quantities' => 'Enter a quantity greater than zero for at least one line.',
                ]);
            }

            $voucher = GoodsReceivedVoucher::create([
                'school_id' => $school->id,
                'purchase_order_id' => $order->id,
                'inventory_warehouse_id' => $warehouse->id,
                'voucher_number' => 'GRV-'.Str::upper((string) Str::ulid()),
                'received_date' => $receivedDate,
                'received_by' => $receiver->id,
                'notes' => $notes,
                'posted_at' => now(),
            ]);

            $transactionGroupId = (string) Str::uuid();
            foreach ($receivingLines as [$orderItem, $quantity]) {
                $item = $orderItem->item;
                InventoryItem::where('school_id', $school->id)
                    ->whereKey($item->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $unitCost = (float) $orderItem->unit_cost;
                $receivedItem = $voucher->items()->create([
                    'purchase_order_item_id' => $orderItem->id,
                    'inventory_item_id' => $item->id,
                    'quantity_received' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => round($quantity * $unitCost, 2),
                ]);
                $assetService = app(SchoolFixedAssetService::class);
                $assetService->recordReceivedAssets($receivedItem, $warehouse, $receiver);
                if ($assetService->shouldCapitalize($item, $unitCost)) {
                    continue;
                }

                $balance = InventoryBalance::where('school_id', $school->id)
                    ->where('inventory_item_id', $item->id)
                    ->where('inventory_warehouse_id', $warehouse->id)
                    ->lockForUpdate()
                    ->first();

                if (! $balance) {
                    $balance = InventoryBalance::create([
                        'school_id' => $school->id,
                        'inventory_item_id' => $item->id,
                        'inventory_warehouse_id' => $warehouse->id,
                        'quantity_on_hand' => 0,
                        'average_unit_cost' => 0,
                        'reorder_level' => 0,
                    ]);
                }

                $oldQuantity = (float) $balance->quantity_on_hand;
                $oldAverageCost = (float) $balance->average_unit_cost;
                $newQuantity = $oldQuantity + $quantity;
                $newAverageCost = $newQuantity > 0
                    ? round((($oldQuantity * $oldAverageCost) + ($quantity * $unitCost)) / $newQuantity, 4)
                    : 0;

                $balance->update([
                    'quantity_on_hand' => $newQuantity,
                    'average_unit_cost' => $newAverageCost,
                ]);

                InventoryTransaction::create([
                    'school_id' => $school->id,
                    'inventory_item_id' => $item->id,
                    'inventory_warehouse_id' => $warehouse->id,
                    'movement_type' => 'receipt',
                    'quantity_delta' => $quantity,
                    'unit_cost' => $unitCost,
                    'quantity_after' => $newQuantity,
                    'average_unit_cost_after' => $newAverageCost,
                    'reference_type' => GoodsReceivedVoucher::class,
                    'reference_id' => $voucher->id,
                    'transaction_group_id' => $transactionGroupId,
                    'notes' => $notes,
                    'recorded_by' => $receiver->id,
                    'occurred_at' => $receivedDate,
                ]);
            }

            $fullyReceived = $orderItems->every(function ($orderItem) {
                return (float) $orderItem->quantity_ordered
                    <= (float) $orderItem->goodsReceivedItems()->sum('quantity_received');
            });

            $order->update(['status' => $fullyReceived ? 'received' : 'partially_received']);
            ProcurementRequisition::whereKey($order->procurement_requisition_id)
                ->update(['status' => $fullyReceived ? 'received' : 'partially_received']);
            app(ProcurementAccountingService::class)->postGoodsReceivedVoucher($voucher);

            return $voucher;
        });
    }
}
