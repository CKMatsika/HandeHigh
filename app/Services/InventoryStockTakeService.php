<?php

namespace App\Services;

use App\Models\Account;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\InventoryStockTake;
use App\Models\InventoryStockTakeReview;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\JournalBatch;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class InventoryStockTakeService
{
    public function submit(
        School $school,
        InventoryWarehouse $warehouse,
        User $taker,
        array $counts,
        array $systemQuantities,
        string $stockTakeDate,
        ?string $notes
    ): InventoryStockTake {
        return $this->saveCount(null, $school, $warehouse, $taker, $counts, $systemQuantities, $stockTakeDate, $notes);
    }

    public function resubmit(
        InventoryStockTake $stockTake,
        School $school,
        User $taker,
        array $counts,
        array $systemQuantities,
        string $stockTakeDate,
        ?string $notes
    ): InventoryStockTake {
        return $this->saveCount($stockTake, $school, null, $taker, $counts, $systemQuantities, $stockTakeDate, $notes);
    }

    public function approve(InventoryStockTake $stockTake, User $reviewer): InventoryStockTake
    {
        return DB::transaction(function () use ($stockTake, $reviewer) {
            $record = InventoryStockTake::whereKey($stockTake->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending_review', 409);
            $existingJournal = JournalBatch::where('school_id', $record->school_id)
                ->where('source_type', 'inventory_stock_take')
                ->where('source_id', $record->id)
                ->exists();
            abort_if($existingJournal, 409, 'This stock take already has an accounting journal.');
            $items = $record->items()->with('item')->orderBy('inventory_item_id')->get();
            $warehouse = InventoryWarehouse::whereKey($record->inventory_warehouse_id)
                ->where('school_id', $record->school_id)
                ->firstOrFail();
            $inventoryAccount = null;
            $varianceAccount = null;
            $debitInventory = 0.0;
            $creditInventory = 0.0;
            $debitVariance = 0.0;
            $creditVariance = 0.0;
            $transactionGroupId = (string) Str::uuid();

            foreach ($items as $line) {
                InventoryItem::where('school_id', $record->school_id)
                    ->whereKey($line->inventory_item_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $balance = InventoryBalance::where('school_id', $record->school_id)
                    ->where('inventory_item_id', $line->inventory_item_id)
                    ->where('inventory_warehouse_id', $record->inventory_warehouse_id)
                    ->lockForUpdate()
                    ->first();
                $currentQuantity = (float) ($balance?->quantity_on_hand ?? 0);
                $currentCost = (float) ($balance?->average_unit_cost ?? 0);

                if (abs($currentQuantity - (float) $line->system_quantity) > 0.00005
                    || abs($currentCost - (float) $line->system_average_unit_cost) > 0.00005) {
                    throw ValidationException::withMessages([
                        'stock_take' => "{$line->item->name} changed in the stock ledger after the count was submitted. Return the count for a recount.",
                    ]);
                }

                $difference = round((float) $line->counted_quantity - $currentQuantity, 4);
                if ($difference === 0.0) {
                    continue;
                }

                if ($currentCost <= 0) {
                    throw ValidationException::withMessages([
                        'stock_take' => "{$line->item->name} has no recorded stock cost, so its count variance cannot be posted.",
                    ]);
                }

                if (! $balance && (float) $line->counted_quantity > 0) {
                    throw ValidationException::withMessages([
                        'stock_take' => "{$line->item->name} has no recorded stock cost, so its count variance cannot be posted.",
                    ]);
                }

                $variance = round(abs($difference) * $currentCost, 2);
                if ($variance >= 0.01) {
                    $accounts = $this->stockTakeAccounts(School::findOrFail($record->school_id));
                    $inventoryAccount = $accounts['inventory'];
                    $varianceAccount = $accounts['variance'];

                    if ($difference > 0) {
                        $debitInventory += $variance;
                        $creditVariance += $variance;
                    } else {
                        $debitVariance += $variance;
                        $creditInventory += $variance;
                    }
                }

                $quantityAfter = (float) $line->counted_quantity;
                if ($balance) {
                    $balance->update(['quantity_on_hand' => $quantityAfter]);
                } elseif ($quantityAfter > 0) {
                    $balance = InventoryBalance::create([
                        'school_id' => $record->school_id,
                        'inventory_item_id' => $line->inventory_item_id,
                        'inventory_warehouse_id' => $record->inventory_warehouse_id,
                        'quantity_on_hand' => $quantityAfter,
                        'average_unit_cost' => $currentCost,
                        'reorder_level' => 0,
                    ]);
                }

                InventoryTransaction::create([
                    'school_id' => $record->school_id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'inventory_warehouse_id' => $record->inventory_warehouse_id,
                    'movement_type' => 'adjustment',
                    'quantity_delta' => $difference,
                    'unit_cost' => $currentCost,
                    'quantity_after' => $quantityAfter,
                    'average_unit_cost_after' => $currentCost,
                    'reference_type' => InventoryStockTake::class,
                    'reference_id' => $record->id,
                    'transaction_group_id' => $transactionGroupId,
                    'notes' => 'Approved stock take '.$record->stock_take_number,
                    'recorded_by' => $reviewer->id,
                    'occurred_at' => $record->stock_take_date->toDateString(),
                ]);
            }

            $entries = [];
            if ($debitInventory >= 0.01) {
                $entries[] = [
                    'account_id' => $inventoryAccount->id,
                    'entry_type' => 'debit',
                    'amount' => round($debitInventory, 2),
                    'memo' => 'Stock count surplus - '.$record->stock_take_number,
                ];
            }
            if ($debitVariance >= 0.01) {
                $entries[] = [
                    'account_id' => $varianceAccount->id,
                    'entry_type' => 'debit',
                    'amount' => round($debitVariance, 2),
                    'memo' => 'Stock count shortage - '.$record->stock_take_number,
                ];
            }
            if ($creditInventory >= 0.01) {
                $entries[] = [
                    'account_id' => $inventoryAccount->id,
                    'entry_type' => 'credit',
                    'amount' => round($creditInventory, 2),
                    'memo' => 'Stock count shortage - '.$record->stock_take_number,
                ];
            }
            if ($creditVariance >= 0.01) {
                $entries[] = [
                    'account_id' => $varianceAccount->id,
                    'entry_type' => 'credit',
                    'amount' => round($creditVariance, 2),
                    'memo' => 'Stock count surplus - '.$record->stock_take_number,
                ];
            }

            if ($entries !== []) {
                app(AccountingService::class)->createJournalBatch([
                    'school_id' => $record->school_id,
                    'transaction_date' => $record->stock_take_date->toDateString(),
                    'reference_number' => $record->stock_take_number,
                    'description' => 'Inventory count adjustment '.$record->stock_take_number,
                    'source_type' => 'inventory_stock_take',
                    'source_id' => $record->id,
                    'status' => 'posted',
                    'created_by' => $reviewer->id,
                    'entries' => $entries,
                ]);
            }

            $reviewedAt = now();
            $record->update([
                'status' => 'posted',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => $reviewedAt,
                'review_notes' => null,
                'posted_at' => $reviewedAt,
            ]);
            InventoryStockTakeReview::create([
                'inventory_stock_take_id' => $record->id,
                'reviewed_by' => $reviewer->id,
                'decision' => 'approved',
                'reviewed_at' => $reviewedAt,
            ]);

            return $record;
        });
    }

    private function saveCount(
        ?InventoryStockTake $stockTake,
        School $school,
        ?InventoryWarehouse $warehouse,
        User $taker,
        array $counts,
        array $systemQuantities,
        string $stockTakeDate,
        ?string $notes
    ): InventoryStockTake {
        return DB::transaction(function () use ($stockTake, $school, $warehouse, $taker, $counts, $systemQuantities, $stockTakeDate, $notes) {
            if ($stockTake) {
                $record = InventoryStockTake::whereKey($stockTake->id)->lockForUpdate()->firstOrFail();
                abort_unless((int) $record->school_id === (int) $school->id, 404);
                abort_unless((int) $record->taken_by === (int) $taker->id && $record->status === 'returned', 403);
                $warehouse = InventoryWarehouse::whereKey($record->inventory_warehouse_id)
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();
                $record->update([
                    'status' => 'pending_review',
                    'stock_take_date' => $stockTakeDate,
                    'notes' => $notes,
                    'submitted_at' => now(),
                ]);
                $record->items()->delete();
            } else {
                $warehouse = InventoryWarehouse::whereKey($warehouse?->id)
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();
                $record = InventoryStockTake::create([
                    'school_id' => $school->id,
                    'inventory_warehouse_id' => $warehouse->id,
                    'stock_take_number' => 'STK-'.Str::upper((string) Str::ulid()),
                    'status' => 'pending_review',
                    'taken_by' => $taker->id,
                    'stock_take_date' => $stockTakeDate,
                    'notes' => $notes,
                    'submitted_at' => now(),
                ]);
            }

            $items = InventoryItem::where('school_id', $school->id)
                ->where('is_active', true)
                ->where('item_type', 'consumable')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $expectedIds = $items->modelKeys();
            $providedIds = array_map('intval', array_keys($counts));
            $snapshotIds = array_map('intval', array_keys($systemQuantities));
            sort($expectedIds);
            sort($providedIds);
            sort($snapshotIds);
            if ($expectedIds !== $providedIds || $expectedIds !== $snapshotIds) {
                throw ValidationException::withMessages([
                    'counts' => 'A physical count is required for every active consumable item in the school catalogue.',
                ]);
            }

            foreach ($items as $item) {
                $balance = InventoryBalance::where('school_id', $school->id)
                    ->where('inventory_item_id', $item->id)
                    ->where('inventory_warehouse_id', $warehouse->id)
                    ->lockForUpdate()
                    ->first();
                $snapshotQuantity = (float) $systemQuantities[$item->id];
                $currentQuantity = (float) ($balance?->quantity_on_hand ?? 0);
                if (abs($snapshotQuantity - $currentQuantity) > 0.00005) {
                    throw ValidationException::withMessages([
                        "system_quantities.{$item->id}" => "{$item->name} stock changed after this count form was loaded. Reload the warehouse and recount.",
                    ]);
                }

                $record->items()->create([
                    'inventory_item_id' => $item->id,
                    'system_quantity' => $snapshotQuantity,
                    'system_average_unit_cost' => $balance?->average_unit_cost ?? 0,
                    'counted_quantity' => $counts[$item->id],
                ]);
            }

            return $record;
        });
    }

    private function stockTakeAccounts(School $school): array
    {
        app(AccountingService::class)->ensureChartOfAccountsExist($school);
        $codes = ['1505', '6595'];
        if (Account::where('school_id', $school->id)->whereIn('code', $codes)->count() !== count($codes)) {
            app(\Database\Seeders\ChartOfAccountsSeeder::class)->seedAccountsForSchool($school);
        }

        $accounts = Account::where('school_id', $school->id)->whereIn('code', $codes)->get()->keyBy('code');
        foreach (['1505' => 'asset', '6595' => 'expense'] as $code => $type) {
            $account = $accounts->get($code);
            if (! $account || ! $account->is_active || ! $account->is_postable || $account->type !== $type) {
                throw new InvalidArgumentException("Inventory stock-take account {$code} must be active and postable as a {$type} account.");
            }
        }

        return [
            'inventory' => $accounts->get('1505'),
            'variance' => $accounts->get('6595'),
        ];
    }
}
