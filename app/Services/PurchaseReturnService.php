<?php

namespace App\Services;

use App\Models\GoodsReceivedVoucher;
use App\Models\GoodsReceivedVoucherItem;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseReturnService
{
    /**
     * Create and post a return to vendor from a Goods Received Voucher.
     */
    public function processReturn(
        GoodsReceivedVoucher $voucher,
        array $returnQuantities,
        string $returnDate,
        string $reason,
        ?string $reasonDetails,
        ?string $debitNoteNumber,
        User $user,
        School $school
    ): PurchaseReturn {
        return DB::transaction(function () use ($voucher, $returnQuantities, $returnDate, $reason, $reasonDetails, $debitNoteNumber, $user, $school) {
            $lockedVoucher = GoodsReceivedVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedVoucher->school_id === (int) $school->id, 404);

            $voucher->load(['items.item', 'purchaseOrder.vendor', 'warehouse']);
            $order = $voucher->purchaseOrder;
            $warehouse = $voucher->warehouse;

            $itemsKeyed = $voucher->items->keyBy('id');
            $submittedIds = array_map('intval', array_keys($returnQuantities));
            $expectedIds = $itemsKeyed->keys()->map(fn ($id) => (int) $id)->all();
            sort($submittedIds);
            sort($expectedIds);

            if ($submittedIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'return_quantities' => 'Return quantities must match voucher item lines.',
                ]);
            }

            $validLines = [];
            foreach ($itemsKeyed as $voucherItem) {
                $qtyToReturn = (float) $returnQuantities[$voucherItem->id];
                if ($qtyToReturn < 0) {
                    throw ValidationException::withMessages([
                        "return_quantities.{$voucherItem->id}" => 'Return quantity cannot be negative.',
                    ]);
                }

                if ($qtyToReturn > 0) {
                    $previouslyReturned = (float) PurchaseReturnItem::where('goods_received_voucher_item_id', $voucherItem->id)
                        ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'posted'))
                        ->sum('quantity_returned');
                    $maxReturnable = max(0, (float) $voucherItem->quantity_received - $previouslyReturned);

                    if ($qtyToReturn > $maxReturnable) {
                        throw ValidationException::withMessages([
                            "return_quantities.{$voucherItem->id}" => "Return quantity ({$qtyToReturn}) cannot exceed remaining returnable balance ({$maxReturnable}).",
                        ]);
                    }

                    // Check warehouse stock on hand
                    $balance = InventoryBalance::where('school_id', $school->id)
                        ->where('inventory_warehouse_id', $warehouse->id)
                        ->where('inventory_item_id', $voucherItem->inventory_item_id)
                        ->lockForUpdate()
                        ->first();

                    $availableOnHand = $balance ? (float) $balance->quantity_on_hand : 0;
                    if ($qtyToReturn > $availableOnHand) {
                        throw ValidationException::withMessages([
                            "return_quantities.{$voucherItem->id}" => "Cannot return {$qtyToReturn} units of {$voucherItem->item->name}. Only {$availableOnHand} units currently available in {$warehouse->name}.",
                        ]);
                    }

                    $validLines[] = [
                        'voucher_item' => $voucherItem,
                        'quantity' => $qtyToReturn,
                        'unit_cost' => (float) $voucherItem->unit_cost,
                        'balance' => $balance,
                    ];
                }
            }

            if (empty($validLines)) {
                throw ValidationException::withMessages([
                    'return_quantities' => 'Please enter a return quantity greater than zero for at least one item.',
                ]);
            }

            // Create Purchase Return record
            $returnNumber = 'RET-'.Str::upper((string) Str::ulid());
            $debitNote = $debitNoteNumber ?: 'DN-'.Str::upper(Str::random(8));

            $purchaseReturn = PurchaseReturn::create([
                'school_id' => $school->id,
                'return_number' => $returnNumber,
                'goods_received_voucher_id' => $voucher->id,
                'purchase_order_id' => $order->id,
                'vendor_id' => $order->vendor_id,
                'inventory_warehouse_id' => $warehouse->id,
                'return_date' => $returnDate,
                'reason' => $reason,
                'reason_details' => $reasonDetails,
                'debit_note_number' => $debitNote,
                'status' => 'posted',
                'created_by' => $user->id,
                'posted_at' => now(),
            ]);

            $transactionGroupId = (string) Str::uuid();

            foreach ($validLines as $line) {
                $voucherItem = $line['voucher_item'];
                $quantity = $line['quantity'];
                $unitCost = $line['unit_cost'];
                $balance = $line['balance'];
                $totalCost = round($quantity * $unitCost, 2);

                $purchaseReturn->items()->create([
                    'goods_received_voucher_item_id' => $voucherItem->id,
                    'inventory_item_id' => $voucherItem->inventory_item_id,
                    'quantity_returned' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'remarks' => $reason,
                ]);

                // Reduce inventory stock on hand
                $oldQty = (float) $balance->quantity_on_hand;
                $newQty = max(0, $oldQty - $quantity);
                $balance->update(['quantity_on_hand' => $newQty]);

                // Record immutable inventory ledger transaction
                InventoryTransaction::create([
                    'school_id' => $school->id,
                    'inventory_item_id' => $voucherItem->inventory_item_id,
                    'inventory_warehouse_id' => $warehouse->id,
                    'movement_type' => 'supplier_return',
                    'quantity_delta' => -1 * $quantity,
                    'quantity_after' => $newQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->id,
                    'transaction_group_id' => $transactionGroupId,
                    'recorded_by' => $user->id,
                    'occurred_at' => now(),
                    'notes' => "Return to Vendor {$order->vendor->name} (DN: {$debitNote}) - Reason: {$reason}",
                ]);
            }

            return $purchaseReturn;
        });
    }
}
