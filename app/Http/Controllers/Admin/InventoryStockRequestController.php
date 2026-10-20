<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryStockRequest;
use App\Models\InventoryWarehouse;
use App\Models\School;
use App\Services\InventoryStockIssueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryStockRequestController extends Controller
{
    public function index()
    {
        $school = $this->school();
        $query = InventoryStockRequest::where('school_id', $school->id)
            ->with(['requester', 'department'])
            ->latest('submitted_at');

        if (! auth()->user()->can('inventory.stock-requests.approve')
            && ! auth()->user()->can('inventory.stock-issues.create')) {
            $query->where('requested_by', auth()->id());
        }

        $requests = $query->paginate(25);

        return view('admin.inventory.stock-requests.index', compact('requests'));
    }

    public function create()
    {
        return view('admin.inventory.stock-requests.form', $this->formData());
    }

    public function store(Request $request)
    {
        $school = $this->school();
        $validated = $this->validateRequest($request, $school);
        $lines = $this->validatedLines($validated['items']);

        $stockRequest = DB::transaction(function () use ($school, $validated, $lines) {
            $record = InventoryStockRequest::create([
                'school_id' => $school->id,
                'request_number' => 'SR-'.Str::upper((string) Str::ulid()),
                'requested_by' => auth()->id(),
                'department_id' => $validated['department_id'] ?? null,
                'needed_by' => $validated['needed_by'] ?? null,
                'purpose' => $validated['purpose'],
                'status' => 'pending_review',
                'submitted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $record->items()->create($line);
            }

            return $record;
        });

        return redirect()->route('admin.inventory.stock-requests.show', $stockRequest)
            ->with('success', 'Stock request submitted for approval.');
    }

    public function show(InventoryStockRequest $stockRequest)
    {
        $this->assertSchoolRecord($stockRequest);
        $this->authorizeRequestAccess($stockRequest);
        $stockRequest->load([
            'requester',
            'department',
            'reviews.reviewer',
            'items.item.category',
            'issues.warehouse',
            'issues.issuer',
            'issues.items.item',
        ]);
        $warehouses = auth()->user()->can('inventory.stock-issues.create')
            ? InventoryWarehouse::where('school_id', $stockRequest->school_id)->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('admin.inventory.stock-requests.show', compact('stockRequest', 'warehouses'));
    }

    public function edit(InventoryStockRequest $stockRequest)
    {
        $this->assertSchoolRecord($stockRequest);
        abort_unless(
            (int) $stockRequest->requested_by === (int) auth()->id()
            && $stockRequest->status === 'returned',
            403
        );
        $stockRequest->load('items');

        return view('admin.inventory.stock-requests.form', $this->formData($stockRequest));
    }

    public function update(Request $request, InventoryStockRequest $stockRequest)
    {
        $this->assertSchoolRecord($stockRequest);
        abort_unless(
            (int) $stockRequest->requested_by === (int) auth()->id()
            && $stockRequest->status === 'returned',
            403
        );

        $school = $this->school();
        $validated = $this->validateRequest($request, $school);
        $lines = $this->validatedLines($validated['items']);

        DB::transaction(function () use ($stockRequest, $validated, $lines) {
            $record = InventoryStockRequest::whereKey($stockRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'returned', 409);
            $record->update([
                'department_id' => $validated['department_id'] ?? null,
                'needed_by' => $validated['needed_by'] ?? null,
                'purpose' => $validated['purpose'],
                'status' => 'pending_review',
                'submitted_at' => now(),
            ]);
            $record->items()->delete();
            foreach ($lines as $line) {
                $record->items()->create($line);
            }
        });

        return redirect()->route('admin.inventory.stock-requests.show', $stockRequest)
            ->with('success', 'Corrected stock request resubmitted for approval.');
    }

    public function approve(InventoryStockRequest $stockRequest)
    {
        $this->assertSchoolRecord($stockRequest);
        abort_unless((int) $stockRequest->requested_by !== (int) auth()->id(), 403);

        DB::transaction(function () use ($stockRequest) {
            $record = InventoryStockRequest::whereKey($stockRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending_review', 409);
            $reviewedAt = now();
            $record->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => $reviewedAt,
                'review_notes' => null,
            ]);
            $record->reviews()->create([
                'reviewed_by' => auth()->id(),
                'decision' => 'approved',
                'reviewed_at' => $reviewedAt,
            ]);
        });

        return back()->with('success', 'Stock request approved.');
    }

    public function returnForCorrection(Request $request, InventoryStockRequest $stockRequest)
    {
        $this->assertSchoolRecord($stockRequest);
        abort_unless((int) $stockRequest->requested_by !== (int) auth()->id(), 403);
        abort_unless($stockRequest->status === 'pending_review', 409);
        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($stockRequest, $validated) {
            $record = InventoryStockRequest::whereKey($stockRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending_review', 409);
            $reviewedAt = now();
            $record->update([
                'status' => 'returned',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => $reviewedAt,
                'review_notes' => $validated['review_notes'],
            ]);
            $record->reviews()->create([
                'reviewed_by' => auth()->id(),
                'decision' => 'returned',
                'comments' => $validated['review_notes'],
                'reviewed_at' => $reviewedAt,
            ]);
        });

        return back()->with('success', 'Stock request returned to the requester for correction.');
    }

    public function issue(Request $request, InventoryStockRequest $stockRequest, InventoryStockIssueService $issueService)
    {
        $this->assertSchoolRecord($stockRequest);
        $school = $this->school();
        $validated = $request->validate([
            'inventory_warehouse_id' => [
                'required',
                Rule::exists('inventory_warehouses', 'id')
                    ->where('school_id', $school->id)
                    ->where('is_active', true),
            ],
            'issued_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'issued_quantities' => ['required', 'array'],
            'issued_quantities.*' => ['required', 'numeric', 'min:0'],
        ]);

        $issueService->issue(
            $stockRequest,
            InventoryWarehouse::findOrFail($validated['inventory_warehouse_id']),
            $school,
            auth()->user(),
            $validated['issued_quantities'],
            $validated['issued_date'],
            $validated['notes'] ?? null
        );

        return back()->with('success', 'Stock issue posted to the inventory ledger and accounting.');
    }

    private function validateRequest(Request $request, School $school): array
    {
        return $request->validate([
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('school_id', $school->id),
            ],
            'needed_by' => ['nullable', 'date'],
            'purpose' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'nullable',
                'distinct',
                Rule::exists('inventory_items', 'id')
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->where('item_type', 'consumable'),
            ],
            'items.*.quantity_requested' => ['nullable', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validatedLines(array $items): array
    {
        $lines = [];
        foreach ($items as $index => $line) {
            $itemId = $line['inventory_item_id'] ?? null;
            $quantity = $line['quantity_requested'] ?? null;
            if (blank($itemId) && blank($quantity)) {
                continue;
            }
            if (blank($itemId) || blank($quantity)) {
                throw ValidationException::withMessages([
                    "items.{$index}.inventory_item_id" => 'Choose an item and enter its requested quantity.',
                ]);
            }
            $lines[] = [
                'inventory_item_id' => (int) $itemId,
                'quantity_requested' => $quantity,
                'quantity_issued' => 0,
                'notes' => $line['notes'] ?? null,
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one stock item and quantity.',
            ]);
        }

        return $lines;
    }

    private function formData(?InventoryStockRequest $stockRequest = null): array
    {
        $school = $this->school();

        return [
            'stockRequest' => $stockRequest,
            'items' => InventoryItem::where('school_id', $school->id)
                ->where('is_active', true)
                ->where('item_type', 'consumable')
                ->orderBy('name')
                ->get(),
            'departments' => Department::where('school_id', $school->id)->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function authorizeRequestAccess(InventoryStockRequest $stockRequest): void
    {
        if ((int) $stockRequest->requested_by !== (int) auth()->id()
            && ! auth()->user()->can('inventory.stock-requests.approve')
            && ! auth()->user()->can('inventory.stock-issues.create')) {
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

    private function assertSchoolRecord(InventoryStockRequest $record): void
    {
        abort_unless((int) $record->school_id === (int) $this->school()->id, 404);
    }
}
