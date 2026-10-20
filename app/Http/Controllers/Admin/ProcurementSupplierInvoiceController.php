<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\GoodsReceivedVoucher;
use App\Models\ProcurementSupplierInvoice;
use App\Models\ProcurementSupplierInvoiceReview;
use App\Models\School;
use App\Services\ProcurementAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProcurementSupplierInvoiceController extends Controller
{
    public function create(GoodsReceivedVoucher $voucher)
    {
        abort_unless(auth()->user()->can('procurement.supplier-invoices.create'), 403);
        $this->assertSchoolRecord($voucher);
        $voucher->load(['purchaseOrder.vendor', 'items.item', 'items.supplierInvoiceItems.supplierInvoice']);
        $remainingQuantities = $this->remainingQuantities($voucher);
        abort_if($voucher->supplierInvoices()->where('status', 'pending_review')->exists(), 409);
        abort_unless(collect($remainingQuantities)->sum() > 0, 409);

        return view('admin.procurement.supplier-invoice-form', [
            'voucher' => $voucher,
            'invoice' => null,
            'remainingQuantities' => $remainingQuantities,
        ]);
    }

    public function store(Request $request, GoodsReceivedVoucher $voucher)
    {
        abort_unless($request->user()->can('procurement.supplier-invoices.create'), 403);
        $this->assertSchoolRecord($voucher);
        $validated = $this->validateInvoice($request, $voucher);
        $invoice = $this->saveInvoice($validated, $voucher, $request->user(), null);

        return redirect()->route('admin.procurement.supplier-invoices.show', $invoice)
            ->with('success', 'Supplier invoice submitted for review.');
    }

    public function show(ProcurementSupplierInvoice $supplierInvoice)
    {
        $this->authorizeView($supplierInvoice);
        $supplierInvoice->load([
            'goodsReceivedVoucher.purchaseOrder.vendor',
            'goodsReceivedVoucher.warehouse',
            'vendor',
            'items.goodsReceivedItem.item',
            'reviews.reviewer',
            'bill',
        ]);
        $stockCostBasis = (float) $supplierInvoice->items->sum('stock_cost_basis');
        $priceVariance = round((float) $supplierInvoice->total_amount - $stockCostBasis, 2);

        return view('admin.procurement.supplier-invoice-show', compact(
            'supplierInvoice',
            'stockCostBasis',
            'priceVariance'
        ));
    }

    public function edit(ProcurementSupplierInvoice $supplierInvoice)
    {
        $this->authorizeCorrection($supplierInvoice);
        $voucher = $supplierInvoice->goodsReceivedVoucher()
            ->with(['purchaseOrder.vendor', 'items.item', 'items.supplierInvoiceItems.supplierInvoice'])
            ->firstOrFail();

        return view('admin.procurement.supplier-invoice-form', [
            'voucher' => $voucher,
            'invoice' => $supplierInvoice->load('items'),
            'remainingQuantities' => $this->remainingQuantities($voucher),
        ]);
    }

    public function update(Request $request, ProcurementSupplierInvoice $supplierInvoice)
    {
        $this->authorizeCorrection($supplierInvoice);
        $voucher = $supplierInvoice->goodsReceivedVoucher;
        $validated = $this->validateInvoice($request, $voucher, $supplierInvoice);
        $invoice = $this->saveInvoice($validated, $voucher, $request->user(), $supplierInvoice);

        return redirect()->route('admin.procurement.supplier-invoices.show', $invoice)
            ->with('success', 'Corrected supplier invoice resubmitted for review.');
    }

    public function review(Request $request, ProcurementSupplierInvoice $supplierInvoice)
    {
        abort_unless($request->user()->can('procurement.supplier-invoices.approve'), 403);
        $this->assertSchoolRecord($supplierInvoice);
        abort_unless($supplierInvoice->status === 'pending_review', 409);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'returned'])],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($supplierInvoice, $validated, $request) {
            $invoice = ProcurementSupplierInvoice::whereKey($supplierInvoice->id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($invoice->status === 'pending_review', 409);
            abort_if(
                (int) $invoice->submitted_by === (int) $request->user()->id,
                403,
                'Supplier invoice submitters cannot approve their own invoices.'
            );

            ProcurementSupplierInvoiceReview::create([
                'procurement_supplier_invoice_id' => $invoice->id,
                'reviewed_by' => $request->user()->id,
                'decision' => $validated['decision'],
                'comments' => $validated['comments'] ?? null,
                'reviewed_at' => now(),
            ]);

            if ($validated['decision'] === 'returned') {
                $invoice->update([
                    'status' => 'returned',
                    'approval_notes' => $validated['comments'] ?? null,
                ]);

                return;
            }

            $voucher = GoodsReceivedVoucher::whereKey($invoice->goods_received_voucher_id)
                ->lockForUpdate()
                ->firstOrFail();
            $invoice->load('items.goodsReceivedItem');
            $billAmount = (float) $invoice->total_amount;
            abort_unless($billAmount > 0, 422, 'Supplier invoice total must be greater than zero.');

            foreach ($invoice->items as $line) {
                $receivedLine = $line->goodsReceivedItem;
                $alreadyInvoiced = (float) $receivedLine->supplierInvoiceItems()
                    ->whereHas('supplierInvoice', fn ($query) => $query
                        ->where('status', 'posted')
                        ->where('id', '!=', $invoice->id))
                    ->sum('quantity_invoiced');
                $remaining = (float) $receivedLine->quantity_received - $alreadyInvoiced;
                if ((float) $line->quantity_invoiced > $remaining) {
                    throw ValidationException::withMessages([
                        'invoice' => 'Another invoice has already claimed part of this GRV quantity. Correct and resubmit this invoice.',
                    ]);
                }
            }

            $accounting = app(ProcurementAccountingService::class);
            $accounts = $accounting->procurementAccounts($invoice->school);
            $bill = Bill::create([
                'school_id' => $invoice->school_id,
                'vendor_id' => $invoice->vendor_id,
                'expense_account_id' => $accounts['inventory']->id,
                'bill_number' => 'PROC-'.Str::upper((string) Str::ulid()),
                'vendor_bill_number' => $invoice->invoice_number,
                'bill_date' => $invoice->invoice_date,
                'due_date' => $invoice->due_date,
                'total_amount' => $billAmount,
                'paid_amount' => 0,
                'balance' => $billAmount,
                'status' => 'pending',
                'description' => 'Supplier invoice '.$invoice->invoice_number.' against GRV '.$voucher->voucher_number,
                'notes' => $invoice->notes,
                'created_by' => $invoice->submitted_by,
            ]);

            foreach ($invoice->items as $line) {
                $receivedItem = $line->goodsReceivedItem;
                $lineTotal = (float) $line->line_total;
                BillItem::create([
                    'bill_id' => $bill->id,
                    'expense_account_id' => $accounts['inventory']->id,
                    'description' => $receivedItem->item->name
                        .' - '.number_format((float) $line->quantity_invoiced, 4)
                        .' '.$receivedItem->item->unit_of_measure,
                    'category' => 'inventory',
                    'quantity' => 1,
                    'unit_price' => $lineTotal,
                    'line_total' => $lineTotal,
                ]);
            }

            $accounting->postSupplierInvoice($bill, $invoice);
            $invoice->update([
                'bill_id' => $bill->id,
                'status' => 'posted',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'posted_at' => now(),
                'approval_notes' => $validated['comments'] ?? null,
            ]);
        });

        return back()->with('success', 'Supplier invoice reviewed and posted to accounts payable.');
    }

    private function validateInvoice(
        Request $request,
        GoodsReceivedVoucher $voucher,
        ?ProcurementSupplierInvoice $invoice = null
    ): array {
        $school = $this->school();
        $vendorId = $voucher->purchaseOrder()->value('vendor_id');
        $invoiceNumberRule = Rule::unique('procurement_supplier_invoices', 'invoice_number')
            ->where('school_id', $school->id)
            ->where('vendor_id', $vendorId);
        if ($invoice) {
            $invoiceNumberRule->ignore($invoice->id);
        }

        return $request->validate([
            'invoice_number' => ['required', 'string', 'max:100', $invoiceNumberRule],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['required', 'numeric', 'min:0'],
            'unit_prices' => ['required', 'array', 'min:1'],
            'unit_prices.*' => ['required', 'numeric', 'min:0', 'max:999999999.9999'],
        ]);
    }

    private function saveInvoice(
        array $validated,
        GoodsReceivedVoucher $voucher,
        \App\Models\User $user,
        ?ProcurementSupplierInvoice $invoice
    ): ProcurementSupplierInvoice {
        return DB::transaction(function () use ($validated, $voucher, $user, $invoice) {
            if ($invoice) {
                $invoice = ProcurementSupplierInvoice::whereKey($invoice->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                abort_unless($invoice->status === 'returned', 409);
            }

            $lockedVoucher = GoodsReceivedVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedVoucher->school_id === (int) $this->school()->id, 404);
            if (! $invoice) {
                abort_if($lockedVoucher->supplierInvoices()->where('status', 'pending_review')->exists(), 409);
            }
            $receivedLines = $lockedVoucher->items()->with('item')->get()->keyBy('id');
            $quantityIds = array_map('intval', array_keys($validated['quantities']));
            $priceIds = array_map('intval', array_keys($validated['unit_prices']));
            $expectedIds = $receivedLines->keys()->map(fn ($id) => (int) $id)->all();
            sort($quantityIds);
            sort($priceIds);
            sort($expectedIds);

            if ($quantityIds !== $expectedIds || $priceIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'quantities' => 'Invoice quantities and prices must match all GRV lines.',
                ]);
            }

            $invoiceLines = [];
            $totalAmount = 0.0;
            foreach ($receivedLines as $receivedLine) {
                $quantity = (float) $validated['quantities'][$receivedLine->id];
                $unitPrice = (float) $validated['unit_prices'][$receivedLine->id];
                $postedInvoiceLines = $receivedLine->supplierInvoiceItems()
                    ->whereHas('supplierInvoice', fn ($query) => $query
                        ->where('status', 'posted')
                        ->when($invoice, fn ($q) => $q->where('id', '!=', $invoice->id)))
                    ->get();
                $alreadyInvoiced = (float) $postedInvoiceLines->sum('quantity_invoiced');
                $alreadyCostBasis = (float) $postedInvoiceLines->sum('stock_cost_basis');
                $remaining = (float) $receivedLine->quantity_received - $alreadyInvoiced;

                if ($quantity < 0 || $quantity > $remaining) {
                    throw ValidationException::withMessages([
                        "quantities.{$receivedLine->id}" => "Invoiced quantity cannot exceed the uninvoiced GRV quantity of {$remaining}.",
                    ]);
                }

                if ($quantity > 0) {
                    $lineTotal = round($quantity * $unitPrice, 2);
                    $stockCostBasis = abs($quantity - $remaining) < 0.0001
                        ? round((float) $receivedLine->line_total - $alreadyCostBasis, 2)
                        : round($quantity * (float) $receivedLine->unit_cost, 2);
                    $invoiceLines[] = compact('receivedLine', 'quantity', 'unitPrice', 'lineTotal', 'stockCostBasis');
                    $totalAmount += $lineTotal;
                }
            }

            if ($invoiceLines === [] || round($totalAmount, 2) <= 0) {
                throw ValidationException::withMessages([
                    'quantities' => 'Enter an invoiced quantity and a supplier invoice value greater than zero.',
                ]);
            }

            $invoiceData = [
                'school_id' => $lockedVoucher->school_id,
                'goods_received_voucher_id' => $lockedVoucher->id,
                'vendor_id' => $lockedVoucher->purchaseOrder()->value('vendor_id'),
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'total_amount' => round($totalAmount, 2),
                'status' => 'pending_review',
                'submitted_by' => $user->id,
                'approval_notes' => null,
                'notes' => $validated['notes'] ?? null,
            ];

            if ($invoice) {
                $invoice->update($invoiceData);
                $invoice->items()->delete();
            } else {
                $invoice = ProcurementSupplierInvoice::create($invoiceData);
            }

            foreach ($invoiceLines as $line) {
                $invoice->items()->create([
                    'goods_received_voucher_item_id' => $line['receivedLine']->id,
                    'quantity_invoiced' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'line_total' => $line['lineTotal'],
                    'stock_cost_basis' => $line['stockCostBasis'],
                ]);
            }

            return $invoice;
        });
    }

    private function remainingQuantities(GoodsReceivedVoucher $voucher): array
    {
        return $voucher->items->mapWithKeys(function ($receivedLine) {
            $invoiced = (float) $receivedLine->supplierInvoiceItems
                ->filter(fn ($line) => $line->supplierInvoice?->status === 'posted')
                ->sum('quantity_invoiced');

            return [$receivedLine->id => max(0, (float) $receivedLine->quantity_received - $invoiced)];
        })->all();
    }

    private function authorizeView(ProcurementSupplierInvoice $supplierInvoice): void
    {
        $user = auth()->user();
        abort_unless(
            $user->can('procurement.supplier-invoices.view')
                || $user->can('procurement.supplier-invoices.create')
                || $user->can('procurement.supplier-invoices.approve'),
            403
        );
        $this->assertSchoolRecord($supplierInvoice);
    }

    private function authorizeCorrection(ProcurementSupplierInvoice $supplierInvoice): void
    {
        abort_unless(auth()->user()->can('procurement.supplier-invoices.create'), 403);
        $this->assertSchoolRecord($supplierInvoice);
        abort_unless(
            (int) $supplierInvoice->submitted_by === (int) auth()->id()
                && $supplierInvoice->status === 'returned',
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
