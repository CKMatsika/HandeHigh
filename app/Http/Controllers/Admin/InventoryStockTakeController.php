<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\InventoryStockTake;
use App\Models\InventoryStockTakeReview;
use App\Models\InventoryWarehouse;
use App\Models\School;
use App\Services\InventoryStockTakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryStockTakeController extends Controller
{
    public function index()
    {
        $school = $this->school();
        $query = InventoryStockTake::where('school_id', $school->id)
            ->with(['warehouse', 'taker'])
            ->latest('submitted_at');

        if (! auth()->user()->can('inventory.stock-takes.approve')) {
            $query->where('taken_by', auth()->id());
        }

        $stockTakes = $query->paginate(25);

        return view('admin.inventory.stock-takes.index', compact('stockTakes'));
    }

    public function create(Request $request)
    {
        $school = $this->school();
        $warehouses = InventoryWarehouse::where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $warehouseId = $request->integer('warehouse_id') ?: $warehouses->first()?->id;
        $warehouse = $warehouses->firstWhere('id', $warehouseId);
        $countLines = $warehouse
            ? $this->countLines($school, $warehouse)
            : collect();

        return view('admin.inventory.stock-takes.form', compact('warehouses', 'warehouse', 'countLines'))
            ->with('stockTake', null);
    }

    public function store(Request $request, InventoryStockTakeService $stockTakeService)
    {
        $school = $this->school();
        $validated = $this->validateCount($request, $school);
        $warehouse = InventoryWarehouse::where('school_id', $school->id)
            ->where('is_active', true)
            ->findOrFail($validated['inventory_warehouse_id']);

        $stockTake = $stockTakeService->submit(
            $school,
            $warehouse,
            auth()->user(),
            $validated['counts'],
            $validated['system_quantities'],
            $validated['stock_take_date'],
            $validated['notes'] ?? null
        );

        return redirect()->route('admin.inventory.stock-takes.show', $stockTake)
            ->with('success', 'Stock count submitted for approval. Adjustments will post only after approval.');
    }

    public function show(InventoryStockTake $stockTake)
    {
        $this->assertSchoolRecord($stockTake);
        $this->authorizeStockTakeAccess($stockTake);
        $stockTake->load([
            'warehouse',
            'taker',
            'reviews.reviewer',
            'items.item',
        ]);

        return view('admin.inventory.stock-takes.show', compact('stockTake'));
    }

    public function edit(InventoryStockTake $stockTake)
    {
        $this->assertSchoolRecord($stockTake);
        abort_unless(
            (int) $stockTake->taken_by === (int) auth()->id()
            && $stockTake->status === 'returned',
            403
        );
        $stockTake->load(['warehouse', 'items']);
        $previousCounts = $stockTake->items->keyBy('inventory_item_id');
        $countLines = $this->countLines($this->school(), $stockTake->warehouse)
            ->map(function ($line) use ($previousCounts) {
                $line->counted_quantity = $previousCounts->get($line->item->id)?->counted_quantity;

                return $line;
            });
        $warehouses = collect();
        $warehouse = $stockTake->warehouse;

        return view('admin.inventory.stock-takes.form', compact('stockTake', 'warehouses', 'warehouse', 'countLines'));
    }

    public function update(Request $request, InventoryStockTake $stockTake, InventoryStockTakeService $stockTakeService)
    {
        $this->assertSchoolRecord($stockTake);
        abort_unless(
            (int) $stockTake->taken_by === (int) auth()->id()
            && $stockTake->status === 'returned',
            403
        );

        $validated = $this->validateCount($request, $this->school(), false);
        $stockTakeService->resubmit(
            $stockTake,
            $this->school(),
            auth()->user(),
            $validated['counts'],
            $validated['system_quantities'],
            $validated['stock_take_date'],
            $validated['notes'] ?? null
        );

        return redirect()->route('admin.inventory.stock-takes.show', $stockTake)
            ->with('success', 'Corrected stock count resubmitted for approval.');
    }

    public function approve(InventoryStockTake $stockTake, InventoryStockTakeService $stockTakeService)
    {
        $this->assertSchoolRecord($stockTake);
        abort_unless((int) $stockTake->taken_by !== (int) auth()->id(), 403);

        $stockTakeService->approve($stockTake, auth()->user());

        return back()->with('success', 'Stock count approved and any resulting inventory and accounting adjustments were posted.');
    }

    public function returnForCorrection(Request $request, InventoryStockTake $stockTake)
    {
        $this->assertSchoolRecord($stockTake);
        abort_unless((int) $stockTake->taken_by !== (int) auth()->id(), 403);
        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($stockTake, $validated) {
            $record = InventoryStockTake::whereKey($stockTake->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending_review', 409);
            $reviewedAt = now();
            $record->update([
                'status' => 'returned',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => $reviewedAt,
                'review_notes' => $validated['review_notes'],
            ]);
            InventoryStockTakeReview::create([
                'inventory_stock_take_id' => $record->id,
                'reviewed_by' => auth()->id(),
                'decision' => 'returned',
                'comments' => $validated['review_notes'],
                'reviewed_at' => $reviewedAt,
            ]);
        });

        return back()->with('success', 'Stock count returned to the Stores Person for a recount or correction.');
    }

    public function stockLevels(Request $request)
    {
        $school = $this->school();
        $warehouses = InventoryWarehouse::where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $warehouseId = $request->integer('warehouse_id') ?: $warehouses->first()?->id;
        $warehouse = $warehouses->firstWhere('id', $warehouseId);
        $items = $warehouse
            ? InventoryItem::where('school_id', $school->id)
                ->where('is_active', true)
                ->where('item_type', 'consumable')
                ->with(['balances' => fn ($query) => $query->where('inventory_warehouse_id', $warehouse->id)])
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.inventory.stock-levels', compact('warehouses', 'warehouse', 'items'));
    }

    public function updateStockLevels(Request $request)
    {
        $school = $this->school();
        $validated = $request->validate([
            'inventory_warehouse_id' => [
                'required',
                Rule::exists('inventory_warehouses', 'id')
                    ->where('school_id', $school->id)
                    ->where('is_active', true),
            ],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.reorder_level' => ['required', 'numeric', 'min:0'],
            'levels.*.reorder_quantity' => ['nullable', 'numeric', 'gt:0'],
        ]);
        $warehouse = InventoryWarehouse::where('school_id', $school->id)
            ->where('is_active', true)
            ->findOrFail($validated['inventory_warehouse_id']);
        $items = InventoryItem::where('school_id', $school->id)
            ->where('is_active', true)
            ->where('item_type', 'consumable')
            ->orderBy('id')
            ->get();
        $expectedIds = $items->modelKeys();
        $providedIds = array_map('intval', array_keys($validated['levels']));
        sort($expectedIds);
        sort($providedIds);
        if ($expectedIds !== $providedIds) {
            return back()->withErrors([
                'levels' => 'Reorder levels must be provided for every active consumable item.',
            ])->withInput();
        }

        DB::transaction(function () use ($items, $school, $warehouse, $validated) {
            foreach ($items as $item) {
                InventoryItem::where('school_id', $school->id)
                    ->whereKey($item->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $balance = InventoryBalance::firstOrNew([
                    'school_id' => $school->id,
                    'inventory_item_id' => $item->id,
                    'inventory_warehouse_id' => $warehouse->id,
                ]);
                $balance->reorder_level = $validated['levels'][$item->id]['reorder_level'];
                $balance->reorder_quantity = $validated['levels'][$item->id]['reorder_quantity'] ?? null;
                $balance->save();
            }
        });

        return redirect()->route('admin.inventory.stock-levels', ['warehouse_id' => $warehouse->id])
            ->with('success', 'Stock on hand and reorder levels updated.');
    }

    private function validateCount(Request $request, School $school, bool $includeWarehouse = true): array
    {
        $rules = [
            'stock_take_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'counts' => ['required', 'array', 'min:1'],
            'counts.*' => ['required', 'numeric', 'min:0'],
            'system_quantities' => ['required', 'array', 'min:1'],
            'system_quantities.*' => ['required', 'numeric', 'min:0'],
        ];
        if ($includeWarehouse) {
            $rules['inventory_warehouse_id'] = [
                'required',
                Rule::exists('inventory_warehouses', 'id')
                    ->where('school_id', $school->id)
                    ->where('is_active', true),
            ];
        }

        return $request->validate($rules);
    }

    private function countLines(School $school, InventoryWarehouse $warehouse)
    {
        return InventoryItem::where('school_id', $school->id)
            ->where('is_active', true)
            ->where('item_type', 'consumable')
            ->with(['balances' => fn ($query) => $query->where('inventory_warehouse_id', $warehouse->id)])
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => (object) [
                'item' => $item,
                'system_quantity' => $item->balances->first()?->quantity_on_hand ?? 0,
                'counted_quantity' => null,
            ]);
    }

    private function authorizeStockTakeAccess(InventoryStockTake $stockTake): void
    {
        if ((int) $stockTake->taken_by !== (int) auth()->id()
            && ! auth()->user()->can('inventory.stock-takes.approve')) {
            abort(403);
        }
    }

    private function school(): School
    {
        $user = auth()->user();
        $school = $user?->school ?? ($user?->hasRole('super-admin') ? School::first() : null);
        abort_unless($school, 403);

        return $school;
    }

    private function assertSchoolRecord(InventoryStockTake $stockTake): void
    {
        abort_unless((int) $stockTake->school_id === (int) $this->school()->id, 404);
    }
}
