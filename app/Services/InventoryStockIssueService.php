<?php

namespace App\Services;

use App\Models\Account;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\InventoryStockIssue;
use App\Models\InventoryStockRequest;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\JournalBatch;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class InventoryStockIssueService
{
    public function issue(
        InventoryStockRequest $stockRequest,
        InventoryWarehouse $warehouse,
        School $school,
        User $issuer,
        array $quantities,
        string $issuedDate,
        ?string $notes
    ): InventoryStockIssue {
        return DB::transaction(function () use ($stockRequest, $warehouse, $school, $issuer, $quantities, $issuedDate, $notes) {
            $request = InventoryStockRequest::whereKey($stockRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $request->school_id === (int) $school->id, 404);
            abort_unless(in_array($request->status, ['approved', 'partially_issued'], true), 409);
            abort_unless(
                (int) $warehouse->school_id === (int) $school->id && $warehouse->is_active,
                404
            );

            $requestItems = $request->items()->with('item.category.issueExpenseAccount')->orderBy('inventory_item_id')->get()->keyBy('id');
            $providedIds = array_map('intval', array_keys($quantities));
            $expectedIds = $requestItems->keys()->map(fn ($id) => (int) $id)->all();
            sort($providedIds);
            sort($expectedIds);

            if ($providedIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'issued_quantities' => 'Issue quantities must match the approved request lines.',
                ]);
            }

            $issueLines = [];
            foreach ($requestItems as $requestItem) {
                $quantity = (float) $quantities[$requestItem->id];
                $remaining = (float) $requestItem->quantity_requested - (float) $requestItem->quantity_issued;

                if ($quantity < 0 || $quantity > $remaining) {
                    throw ValidationException::withMessages([
                        "issued_quantities.{$requestItem->id}" => "Quantity cannot exceed the remaining approved quantity of {$remaining}.",
                    ]);
                }

                if ($quantity > 0) {
                    $issueLines[] = [$requestItem, $quantity];
                }
            }

            if ($issueLines === []) {
                throw ValidationException::withMessages([
                    'issued_quantities' => 'Enter a quantity greater than zero for at least one line.',
                ]);
            }

            $issue = InventoryStockIssue::create([
                'school_id' => $school->id,
                'inventory_stock_request_id' => $request->id,
                'inventory_warehouse_id' => $warehouse->id,
                'issue_number' => 'ISS-'.Str::upper((string) Str::ulid()),
                'issued_date' => $issuedDate,
                'issued_by' => $issuer->id,
                'notes' => $notes,
                'posted_at' => now(),
            ]);

            $transactionGroupId = (string) Str::uuid();
            foreach ($issueLines as [$requestItem, $quantity]) {
                $item = $requestItem->item;
                InventoryItem::where('school_id', $school->id)
                    ->whereKey($item->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $balance = InventoryBalance::where('school_id', $school->id)
                    ->where('inventory_item_id', $item->id)
                    ->where('inventory_warehouse_id', $warehouse->id)
                    ->lockForUpdate()
                    ->first();
                $available = (float) ($balance?->quantity_on_hand ?? 0);

                if (! $balance || $quantity > $available) {
                    throw ValidationException::withMessages([
                        "issued_quantities.{$requestItem->id}" => "Only {$available} {$item->unit_of_measure} is available in {$warehouse->name}.",
                    ]);
                }

                $unitCost = (float) $balance->average_unit_cost;
                if ($unitCost <= 0) {
                    throw ValidationException::withMessages([
                        "issued_quantities.{$requestItem->id}" => "{$item->name} has no recorded stock cost and cannot be issued until its inventory value is established.",
                    ]);
                }

                $quantityAfter = round($available - $quantity, 4);
                $lineTotal = round($quantity * $unitCost, 2);
                $issue->items()->create([
                    'inventory_stock_request_item_id' => $requestItem->id,
                    'inventory_item_id' => $item->id,
                    'quantity_issued' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                $requestItem->update([
                    'quantity_issued' => (float) $requestItem->quantity_issued + $quantity,
                ]);
                $balance->update(['quantity_on_hand' => $quantityAfter]);

                InventoryTransaction::create([
                    'school_id' => $school->id,
                    'inventory_item_id' => $item->id,
                    'inventory_warehouse_id' => $warehouse->id,
                    'movement_type' => 'issue',
                    'quantity_delta' => -$quantity,
                    'unit_cost' => $unitCost,
                    'quantity_after' => $quantityAfter,
                    'average_unit_cost_after' => $unitCost,
                    'reference_type' => InventoryStockIssue::class,
                    'reference_id' => $issue->id,
                    'transaction_group_id' => $transactionGroupId,
                    'notes' => $notes,
                    'recorded_by' => $issuer->id,
                    'occurred_at' => $issuedDate,
                ]);
            }

            $fullyIssued = $request->items()->get()->every(
                fn ($line) => (float) $line->quantity_issued >= (float) $line->quantity_requested
            );
            $request->update(['status' => $fullyIssued ? 'issued' : 'partially_issued']);

            $this->postIssueAccounting($issue, $request);

            return $issue;
        });
    }

    private function postIssueAccounting(InventoryStockIssue $issue, InventoryStockRequest $request): void
    {
        $existing = JournalBatch::where('school_id', $issue->school_id)
            ->where('source_type', 'inventory_stock_issue')
            ->where('source_id', $issue->id)
            ->first();
        if ($existing) {
            return;
        }

        $issue->loadMissing('items.item.category.issueExpenseAccount');
        $schools = School::findOrFail($issue->school_id);
        $inventoryAccount = app(ProcurementAccountingService::class)->procurementAccounts($schools)['inventory'];
        $accounts = Account::where('school_id', $issue->school_id)
            ->where('code', '6500')
            ->first();
        if (! $accounts) {
            app(\App\Services\AccountingService::class)->ensureChartOfAccountsExist($schools);
            app(\Database\Seeders\ChartOfAccountsSeeder::class)->seedAccountsForSchool($schools);
            $accounts = Account::where('school_id', $issue->school_id)->where('code', '6500')->first();
        }
        $this->assertExpenseAccount($accounts, $issue->school_id);

        $amountByAccount = [];
        foreach ($issue->items as $line) {
            $configuredAccount = $line->item->category->issueExpenseAccount;
            $account = $configuredAccount ?? $accounts;
            $this->assertExpenseAccount($account, $issue->school_id);

            $key = $account->id.'|'.($request->department_id ?? '');
            $amountByAccount[$key] ??= [
                'account_id' => $account->id,
                'cost_center_id' => $request->department_id,
                'amount' => 0.0,
            ];
            $amountByAccount[$key]['amount'] += (float) $line->line_total;
        }

        $total = round((float) $issue->items()->sum('line_total'), 2);
        if ($total <= 0) {
            throw new InvalidArgumentException('A stock issue without a positive inventory cost cannot be posted.');
        }

        $entries = [];
        foreach ($amountByAccount as $group) {
            $entries[] = [
                'account_id' => $group['account_id'],
                'entry_type' => 'debit',
                'amount' => round($group['amount'], 2),
                'cost_center_id' => $group['cost_center_id'],
                'memo' => 'Stock issued - '.$issue->issue_number,
            ];
        }
        $entries[] = [
            'account_id' => $inventoryAccount->id,
            'entry_type' => 'credit',
            'amount' => $total,
            'memo' => 'Inventory issued - '.$issue->issue_number,
        ];

        app(AccountingService::class)->createJournalBatch([
            'school_id' => $issue->school_id,
            'transaction_date' => $issue->issued_date->toDateString(),
            'reference_number' => $issue->issue_number,
            'description' => 'Inventory issued for '.$request->request_number,
            'source_type' => 'inventory_stock_issue',
            'source_id' => $issue->id,
            'status' => 'posted',
            'created_by' => $issue->issued_by,
            'entries' => $entries,
        ]);
    }

    private function assertExpenseAccount(?Account $account, int $schoolId): void
    {
        if (! $account
            || (int) $account->school_id !== $schoolId
            || ! $account->is_active
            || ! $account->is_postable
            || $account->type !== 'expense') {
            throw new InvalidArgumentException('Each stock issue expense account must be active, postable, and an expense account.');
        }
    }
}
