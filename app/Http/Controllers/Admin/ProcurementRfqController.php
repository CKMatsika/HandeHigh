<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ProcurementComparison;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementQuotationItem;
use App\Models\ProcurementRequisition;
use App\Models\ProcurementRfq;
use App\Models\ProcurementRfqItem;
use App\Models\PurchaseOrder;
use App\Models\School;
use App\Models\Vendor;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProcurementRfqController extends Controller
{
    protected function school(): School
    {
        return app(TenantContext::class)->school()
            ?? auth()->user()?->school
            ?? abort(404, 'School context missing.');
    }

    public function index(Request $request)
    {
        $school = $this->school();
        $rfqs = ProcurementRfq::where('school_id', $school->id)
            ->with(['requisition', 'creator', 'vendors', 'quotations.vendor', 'comparison'])
            ->withCount(['items', 'quotations'])
            ->latest()
            ->paginate(15);

        return view('admin.procurement.rfq.index', compact('school', 'rfqs'));
    }

    public function create(Request $request)
    {
        $school = $this->school();
        $requisitionId = $request->query('requisition_id');
        $selectedRequisition = null;

        if ($requisitionId) {
            $selectedRequisition = ProcurementRequisition::where('school_id', $school->id)
                ->where('status', 'approved')
                ->with('items.item')
                ->find($requisitionId);
        }

        $approvedRequisitions = ProcurementRequisition::where('school_id', $school->id)
            ->where('status', 'approved')
            ->doesntHave('purchaseOrder')
            ->orderBy('requisition_number')
            ->get();

        $items = InventoryItem::where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $vendors = Vendor::where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.procurement.rfq.create', compact('school', 'approvedRequisitions', 'selectedRequisition', 'items', 'vendors'));
    }

    public function store(Request $request)
    {
        $school = $this->school();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'procurement_requisition_id' => ['nullable', Rule::exists('procurement_requisitions', 'id')->where('school_id', $school->id)],
            'submission_deadline' => ['required', 'date', 'after_or_equal:today'],
            'terms_and_conditions' => ['nullable', 'string', 'max:5000'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => [Rule::exists('vendors', 'id')->where('school_id', $school->id)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', Rule::exists('inventory_items', 'id')->where('school_id', $school->id)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.specifications' => ['nullable', 'string', 'max:1000'],
        ]);

        $rfq = DB::transaction(function () use ($school, $validated, $request) {
            $rfq = ProcurementRfq::create([
                'school_id' => $school->id,
                'procurement_requisition_id' => $validated['procurement_requisition_id'] ?? null,
                'rfq_number' => 'RFQ-'.Str::upper(Str::random(8)),
                'title' => $validated['title'],
                'submission_deadline' => $validated['submission_deadline'],
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $itemData) {
                $rfq->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'quantity' => $itemData['quantity'],
                    'specifications' => $itemData['specifications'] ?? null,
                ]);
            }

            if (! empty($validated['vendor_ids'])) {
                $rfq->vendors()->sync($validated['vendor_ids']);
            }

            return $rfq;
        });

        return redirect()->route('admin.procurement.rfq.show', $rfq)
            ->with('success', "RFQ {$rfq->rfq_number} created successfully.");
    }

    public function show(ProcurementRfq $rfq)
    {
        $school = $this->school();
        abort_unless((int) $rfq->school_id === (int) $school->id, 404);

        $rfq->load([
            'requisition',
            'creator',
            'items.item',
            'vendors',
            'quotations.vendor',
            'quotations.items.item',
            'comparison.winningQuotation.vendor',
            'comparison.purchaseOrder',
        ]);

        return view('admin.procurement.rfq.show', compact('school', 'rfq'));
    }

    public function publish(ProcurementRfq $rfq)
    {
        $school = $this->school();
        abort_unless((int) $rfq->school_id === (int) $school->id, 404);
        abort_unless($rfq->status === 'draft', 409, 'Only draft RFQs can be published.');

        $rfq->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', "RFQ {$rfq->rfq_number} published. Vendors can now submit quotations.");
    }

    public function createQuotation(ProcurementRfq $rfq)
    {
        $school = $this->school();
        abort_unless((int) $rfq->school_id === (int) $school->id, 404);
        abort_if(in_array($rfq->status, ['cancelled', 'awarded']), 409, 'Cannot add quotations to closed or awarded RFQs.');

        $rfq->load(['items.item', 'vendors']);
        $vendors = Vendor::where('school_id', $school->id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.procurement.rfq.quotation-create', compact('school', 'rfq', 'vendors'));
    }

    public function storeQuotation(Request $request, ProcurementRfq $rfq)
    {
        $school = $this->school();
        abort_unless((int) $rfq->school_id === (int) $school->id, 404);

        $rfq->load('items');

        $validated = $request->validate([
            'vendor_id' => [
                'required',
                Rule::exists('vendors', 'id')->where('school_id', $school->id),
                Rule::unique('procurement_quotations', 'vendor_id')
                    ->where('procurement_rfq_id', $rfq->id),
            ],
            'quotation_number' => ['required', 'string', 'max:50'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'unit_prices' => ['required', 'array'],
            'unit_prices.*' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'array'],
        ]);

        $quotation = DB::transaction(function () use ($school, $rfq, $validated, $request) {
            $totalAmount = 0;
            $itemsData = [];

            foreach ($rfq->items as $rfqItem) {
                $unitPrice = (float) ($validated['unit_prices'][$rfqItem->id] ?? 0);
                $totalPrice = round($rfqItem->quantity * $unitPrice, 2);
                $totalAmount += $totalPrice;

                $itemsData[] = [
                    'procurement_rfq_item_id' => $rfqItem->id,
                    'inventory_item_id' => $rfqItem->inventory_item_id,
                    'quantity' => $rfqItem->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'remarks' => $validated['remarks'][$rfqItem->id] ?? null,
                ];
            }

            $quotation = ProcurementQuotation::create([
                'school_id' => $school->id,
                'procurement_rfq_id' => $rfq->id,
                'vendor_id' => $validated['vendor_id'],
                'quotation_number' => $validated['quotation_number'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'] ?? null,
                'lead_time_days' => $validated['lead_time_days'],
                'payment_terms' => $validated['payment_terms'] ?? null,
                'total_amount' => $totalAmount,
                'status' => 'received',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($itemsData as $item) {
                $quotation->items()->create($item);
            }

            return $quotation;
        });

        return redirect()->route('admin.procurement.rfq.show', $rfq)
            ->with('success', "Quotation {$quotation->quotation_number} from {$quotation->vendor->name} recorded successfully.");
    }

    public function showQuotation(ProcurementQuotation $quotation)
    {
        $school = $this->school();
        abort_unless((int) $quotation->school_id === (int) $school->id, 404);

        $quotation->load(['rfq', 'vendor', 'creator', 'items.item']);

        return view('admin.procurement.rfq.quotation-show', compact('school', 'quotation'));
    }

    public function comparison(ProcurementRfq $rfq)
    {
        $school = $this->school();
        abort_unless((int) $rfq->school_id === (int) $school->id, 404);

        $rfq->load([
            'items.item',
            'quotations.vendor',
            'quotations.items',
            'comparison.winningQuotation.vendor',
            'comparison.purchaseOrder',
        ]);

        abort_if($rfq->quotations->isEmpty(), 422, 'At least one quotation must be submitted before generating a comparison report.');

        // Build comparison matrix
        $matrix = [];
        foreach ($rfq->items as $rfqItem) {
            $matrix[$rfqItem->id] = [
                'item' => $rfqItem->item,
                'quantity' => $rfqItem->quantity,
                'specifications' => $rfqItem->specifications,
                'quotes' => [],
            ];

            foreach ($rfq->quotations as $quotation) {
                $quoteLine = $quotation->items->firstWhere('procurement_rfq_item_id', $rfqItem->id);
                $matrix[$rfqItem->id]['quotes'][$quotation->id] = [
                    'unit_price' => $quoteLine ? (float) $quoteLine->unit_price : null,
                    'total_price' => $quoteLine ? (float) $quoteLine->total_price : null,
                ];
            }
        }

        return view('admin.procurement.rfq.comparison', compact('school', 'rfq', 'matrix'));
    }

    public function printComparison(ProcurementRfq $rfq)
    {
        $school = $this->school();
        abort_unless((int) $rfq->school_id === (int) $school->id, 404);

        $rfq->load([
            'items.item',
            'quotations.vendor',
            'quotations.items',
            'comparison.winningQuotation.vendor',
            'comparison.evaluator',
            'comparison.approver',
            'comparison.purchaseOrder',
        ]);

        abort_if($rfq->quotations->isEmpty(), 422, 'At least one quotation must be submitted before generating a comparison report.');

        $matrix = [];
        foreach ($rfq->items as $rfqItem) {
            $matrix[$rfqItem->id] = [
                'item' => $rfqItem->item,
                'quantity' => $rfqItem->quantity,
                'specifications' => $rfqItem->specifications,
                'quotes' => [],
            ];

            foreach ($rfq->quotations as $quotation) {
                $quoteLine = $quotation->items->firstWhere('procurement_rfq_item_id', $rfqItem->id);
                $matrix[$rfqItem->id]['quotes'][$quotation->id] = [
                    'unit_price' => $quoteLine ? (float) $quoteLine->unit_price : null,
                    'total_price' => $quoteLine ? (float) $quoteLine->total_price : null,
                ];
            }
        }

        $branding = app(\App\Services\Branding\SchoolDocumentBrandingService::class)->getBrandingPayload($school);

        return view('admin.procurement.rfq.comparison-print', compact('school', 'rfq', 'matrix', 'branding'));
    }

    public function storeComparison(Request $request, ProcurementRfq $rfq)
    {
        $school = $this->school();
        abort_unless((int) $rfq->school_id === (int) $school->id, 404);

        $validated = $request->validate([
            'winning_quotation_id' => [
                'required',
                Rule::exists('procurement_quotations', 'id')
                    ->where('procurement_rfq_id', $rfq->id),
            ],
            'selection_justification' => ['required', 'string', 'max:3000'],
        ]);

        $winningQuote = ProcurementQuotation::findOrFail($validated['winning_quotation_id']);

        $comparison = DB::transaction(function () use ($school, $rfq, $winningQuote, $validated, $request) {
            $comparison = ProcurementComparison::updateOrCreate(
                ['school_id' => $school->id, 'procurement_rfq_id' => $rfq->id],
                [
                    'comparison_number' => 'CMP-'.Str::upper(Str::random(8)),
                    'evaluation_date' => now()->toDateString(),
                    'winning_quotation_id' => $winningQuote->id,
                    'winning_vendor_id' => $winningQuote->vendor_id,
                    'selection_justification' => $validated['selection_justification'],
                    'evaluated_by' => $request->user()->id,
                    'status' => 'submitted',
                ]
            );

            // Mark quotations status
            $rfq->quotations()->where('id', '!=', $winningQuote->id)->update(['status' => 'rejected']);
            $winningQuote->update(['status' => 'selected']);

            return $comparison;
        });

        return redirect()->route('admin.procurement.rfq.comparison', $rfq)
            ->with('success', 'Quotation comparison report and vendor selection saved.');
    }

    public function approveComparison(Request $request, ProcurementRfq $rfq)
    {
        $school = $this->school();
        abort_unless((int) $rfq->school_id === (int) $school->id, 404);
        abort_unless($request->user()->can('procurement.approve'), 403);

        $comparison = $rfq->comparison;
        abort_unless($comparison && $comparison->status === 'submitted', 409, 'Comparison must be submitted for approval.');

        $winningQuote = $comparison->winningQuotation;
        abort_unless($winningQuote, 409, 'Winning quotation missing.');

        DB::transaction(function () use ($school, $rfq, $comparison, $winningQuote, $request) {
            $comparison->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $rfq->update(['status' => 'awarded']);

            // Auto-generate Purchase Order if not already created
            if (! $comparison->purchase_order_id) {
                $requisition = $rfq->requisition;
                if (! $requisition || $requisition->purchaseOrder) {
                    $requisition = ProcurementRequisition::create([
                        'school_id' => $school->id,
                        'requisition_number' => 'REQ-'.Str::upper(Str::random(8)),
                        'requested_by' => $request->user()->id,
                        'purpose' => "Tender Requisition for RFQ {$rfq->rfq_number}: {$rfq->title}",
                        'status' => 'approved',
                        'needed_by' => now()->addDays($winningQuote->lead_time_days ?: 7)->toDateString(),
                    ]);

                    $rfq->update(['procurement_requisition_id' => $requisition->id]);
                }

                $po = PurchaseOrder::create([
                    'school_id' => $school->id,
                    'procurement_requisition_id' => $requisition->id,
                    'vendor_id' => $winningQuote->vendor_id,
                    'purchase_order_number' => 'PO-'.Str::upper((string) Str::ulid()),
                    'status' => 'draft',
                    'order_date' => now()->toDateString(),
                    'expected_delivery_date' => now()->addDays($winningQuote->lead_time_days ?: 7)->toDateString(),
                    'notes' => "Awarded from RFQ {$rfq->rfq_number} · Quote #{$winningQuote->quotation_number}",
                    'created_by' => $request->user()->id,
                ]);

                foreach ($winningQuote->items as $quoteItem) {
                    $reqItem = $requisition->items->firstWhere('inventory_item_id', $quoteItem->inventory_item_id);
                    if (! $reqItem) {
                        $reqItem = $requisition->items()->create([
                            'inventory_item_id' => $quoteItem->inventory_item_id,
                            'quantity' => $quoteItem->quantity,
                            'notes' => $quoteItem->remarks,
                        ]);
                    }

                    $po->items()->create([
                        'procurement_requisition_item_id' => $reqItem->id,
                        'inventory_item_id' => $quoteItem->inventory_item_id,
                        'quantity_ordered' => $quoteItem->quantity,
                        'unit_cost' => $quoteItem->unit_price,
                        'line_total' => $quoteItem->total_price,
                    ]);
                }

                $comparison->update(['purchase_order_id' => $po->id]);
            }
        });

        return redirect()->route('admin.procurement.rfq.comparison', $rfq)
            ->with('success', 'Bid evaluation approved and Purchase Order draft generated for the winning supplier!');
    }
}
