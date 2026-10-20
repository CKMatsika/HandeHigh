<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryWarehouse;
use App\Models\ProcurementRequisition;
use App\Models\ProcurementRequisitionReview;
use App\Models\PurchaseOrder;
use App\Models\School;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProcurementController extends Controller
{
    public function dashboard()
    {
        $school = $this->school();
        $user = auth()->user();
        $canReview = $user->can('procurement.approve') || $user->can('procurement.view');

        $requisitions = ProcurementRequisition::where('school_id', $school->id)
            ->when(! $canReview, fn ($query) => $query->where('requested_by', $user->id))
            ->with(['requester', 'department', 'items.item', 'purchaseOrder'])
            ->latest()
            ->paginate(15, ['*'], 'requisitions_page');

        $purchaseOrders = PurchaseOrder::where('school_id', $school->id)
            ->when(! $canReview, fn ($query) => $query->whereHas('requisition', fn ($q) => $q->where('requested_by', $user->id)))
            ->with(['vendor', 'requisition'])
            ->latest()
            ->limit(10)
            ->get();

        $requisitionStats = ProcurementRequisition::where('school_id', $school->id)
            ->when(! $canReview, fn ($query) => $query->where('requested_by', $user->id));
        $purchaseOrderStats = PurchaseOrder::where('school_id', $school->id)
            ->when(! $canReview, fn ($query) => $query->whereHas('requisition', fn ($q) => $q->where('requested_by', $user->id)));
        $stats = [
            'awaiting_review' => (clone $requisitionStats)->where('status', 'submitted')->count(),
            'approved' => (clone $requisitionStats)->where('status', 'approved')->count(),
            'draft_orders' => (clone $purchaseOrderStats)->where('status', 'draft')->count(),
            'issued_orders' => (clone $purchaseOrderStats)->where('status', 'issued')->count(),
        ];

        return view('admin.procurement.dashboard', compact('school', 'requisitions', 'purchaseOrders', 'stats'));
    }

    public function createRequisition()
    {
        $school = $this->school();

        return view('admin.procurement.requisition-form', [
            'requisition' => null,
            'items' => InventoryItem::where('school_id', $school->id)->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('school_id', $school->id)->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => InventoryWarehouse::where('school_id', $school->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeRequisition(Request $request)
    {
        $school = $this->school();
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn ($line) => is_array($line) && (filled($line['inventory_item_id'] ?? null) || filled($line['quantity'] ?? null)))
                ->values()
                ->all(),
        ]);
        $validated = $this->validateRequisition($request, $school);

        $requisition = DB::transaction(function () use ($validated, $school) {
            $requisition = ProcurementRequisition::create([
                'school_id' => $school->id,
                'requisition_number' => 'REQ-'.Str::upper((string) Str::ulid()),
                'requested_by' => auth()->id(),
                'department_id' => $validated['department_id'] ?? null,
                'delivery_warehouse_id' => $validated['delivery_warehouse_id'] ?? null,
                'needed_by' => $validated['needed_by'] ?? null,
                'purpose' => $validated['purpose'],
            ]);

            foreach ($validated['items'] as $line) {
                $requisition->items()->create($line);
            }

            return $requisition;
        });

        return redirect()->route('admin.procurement.requisitions.show', $requisition)
            ->with('success', 'Requisition saved as draft.');
    }

    public function showRequisition(ProcurementRequisition $requisition)
    {
        $this->authorizeRequisition($requisition);
        $requisition->load([
            'requester',
            'department',
            'deliveryWarehouse',
            'items.item.category',
            'reviews.reviewer',
            'purchaseOrder.vendor',
            'purchaseOrder.items.item',
        ]);

        return view('admin.procurement.requisition-show', compact('requisition'));
    }

    public function submitRequisition(ProcurementRequisition $requisition)
    {
        $this->authorizeRequisition($requisition);
        abort_unless((int) $requisition->requested_by === (int) auth()->id(), 403);

        DB::transaction(function () use ($requisition) {
            $lockedRequisition = ProcurementRequisition::whereKey($requisition->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedRequisition->requested_by === (int) auth()->id(), 403);
            abort_unless(in_array($lockedRequisition->status, ['draft', 'rejected'], true), 409);
            abort_if($lockedRequisition->items()->count() === 0, 422, 'A requisition must have at least one item.');

            $lockedRequisition->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        });

        return back()->with('success', 'Requisition submitted for approval.');
    }

    public function reviewRequisition(Request $request, ProcurementRequisition $requisition)
    {
        abort_unless($request->user()->can('procurement.approve'), 403);
        $this->assertSchoolRecord($requisition);
        abort_unless($requisition->status === 'submitted', 409);
        abort_if((int) $requisition->requested_by === (int) auth()->id(), 403, 'Requesters cannot approve their own requisitions.');

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($requisition, $validated) {
            $lockedRequisition = ProcurementRequisition::whereKey($requisition->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedRequisition->status === 'submitted', 409);
            abort_if((int) $lockedRequisition->requested_by === (int) auth()->id(), 403);
            $lockedRequisition->update(['status' => $validated['decision']]);
            ProcurementRequisitionReview::create([
                'procurement_requisition_id' => $lockedRequisition->id,
                'reviewed_by' => auth()->id(),
                'decision' => $validated['decision'],
                'comments' => $validated['comments'] ?? null,
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', 'Requisition '.$validated['decision'].'.');
    }

    public function createPurchaseOrder(ProcurementRequisition $requisition)
    {
        $this->authorizePurchaseOrderCreation($requisition);
        abort_unless($requisition->status === 'approved', 409);
        abort_if($requisition->purchaseOrder()->exists(), 409);
        $requisition->load('items.item');

        $school = $this->school();
        $vendors = Vendor::where('school_id', $school->id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.procurement.purchase-order-form', compact('requisition', 'vendors'));
    }

    public function storePurchaseOrder(Request $request, ProcurementRequisition $requisition)
    {
        $this->authorizePurchaseOrderCreation($requisition);
        abort_unless($requisition->status === 'approved', 409);
        abort_if($requisition->purchaseOrder()->exists(), 409);
        $school = $this->school();
        $requisition->load('items');
        $expectedIds = $requisition->items->modelKeys();

        $validated = $request->validate([
            'vendor_id' => ['required', Rule::exists('vendors', 'id')->where('school_id', $school->id)->where('is_active', true)],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'unit_costs' => ['required', 'array'],
            'unit_costs.*' => ['required', 'numeric', 'min:0'],
        ]);

        $costs = $validated['unit_costs'];
        $submittedIds = array_map('intval', array_keys($costs));
        sort($expectedIds);
        sort($submittedIds);
        if ($expectedIds !== $submittedIds) {
            return back()->withErrors(['unit_costs' => 'Enter a unit cost for every requested item and no others.'])->withInput();
        }

        DB::transaction(function () use ($requisition, $school, $validated, $costs) {
            $lockedRequisition = ProcurementRequisition::whereKey($requisition->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedRequisition->status === 'approved', 409);
            abort_if($lockedRequisition->purchaseOrder()->exists(), 409);

            $purchaseOrder = PurchaseOrder::create([
                'school_id' => $school->id,
                'procurement_requisition_id' => $lockedRequisition->id,
                'vendor_id' => $validated['vendor_id'],
                'purchase_order_number' => 'PO-'.Str::upper((string) Str::ulid()),
                'status' => 'draft',
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($lockedRequisition->items as $line) {
                $unitCost = (float) $costs[$line->id];
                $purchaseOrder->items()->create([
                    'procurement_requisition_item_id' => $line->id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'quantity_ordered' => $line->quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => round((float) $line->quantity * $unitCost, 2),
                ]);
            }

            $lockedRequisition->update(['status' => 'purchase_order_draft']);
        });

        return redirect()->route('admin.procurement.index')->with('success', 'Purchase order draft created.');
    }

    public function issuePurchaseOrder(PurchaseOrder $purchaseOrder)
    {
        abort_unless(auth()->user()->can('procurement.purchase-orders.issue'), 403);
        $this->assertSchoolRecord($purchaseOrder);
        abort_unless($purchaseOrder->status === 'draft', 409);

        DB::transaction(function () use ($purchaseOrder) {
            $order = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->status === 'draft', 409);

            $order->update([
                'status' => 'issued',
                'issued_by' => auth()->id(),
                'issued_at' => now(),
            ]);
            $order->requisition()->update(['status' => 'ordered']);
        });

        return back()->with('success', 'Purchase order marked as issued to supplier.');
    }

    public function showPurchaseOrder(PurchaseOrder $purchaseOrder)
    {
        $this->authorizePurchaseOrderView($purchaseOrder);
        $purchaseOrder->load(['vendor', 'creator', 'issuer', 'requisition.requester', 'items.item']);

        return view('admin.procurement.purchase-order-show', compact('purchaseOrder'));
    }

    private function validateRequisition(Request $request, School $school): array
    {
        return $request->validate([
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('school_id', $school->id)],
            'delivery_warehouse_id' => ['nullable', Rule::exists('inventory_warehouses', 'id')->where('school_id', $school->id)],
            'needed_by' => ['nullable', 'date', 'after_or_equal:today'],
            'purpose' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'distinct',
                Rule::exists('inventory_items', 'id')->where('school_id', $school->id),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function authorizeRequisition(ProcurementRequisition $requisition): void
    {
        $this->assertSchoolRecord($requisition);
        $user = auth()->user();
        abort_unless(
            (int) $requisition->requested_by === (int) $user->id
                || $user->can('procurement.view')
                || $user->can('procurement.approve'),
            403
        );
    }

    private function authorizePurchaseOrderCreation(ProcurementRequisition $requisition): void
    {
        abort_unless(auth()->user()->can('procurement.purchase-orders.create'), 403);
        $this->assertSchoolRecord($requisition);
    }

    private function authorizePurchaseOrderView(PurchaseOrder $purchaseOrder): void
    {
        $this->assertSchoolRecord($purchaseOrder);
        $user = auth()->user();
        abort_unless(
            $user->can('procurement.view')
                || $user->can('procurement.approve')
                || (int) $purchaseOrder->requisition()->value('requested_by') === (int) $user->id,
            403
        );
    }

    private function assertSchoolRecord($record): void
    {
        abort_unless((int) $record->school_id === (int) $this->school()->id, 404);
    }

    private function school(): School
    {
        $user = auth()->user();
        $school = $user?->school ?? ($user?->hasRole('super-admin') ? School::first() : null);
        abort_unless($school, 403);

        return $school;
    }
}
