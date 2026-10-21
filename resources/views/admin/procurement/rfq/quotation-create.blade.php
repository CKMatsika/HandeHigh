@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('admin.procurement.rfq.show', $rfq) }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Back to {{ $rfq->rfq_number }}</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-50">Record Supplier Quotation</h1>
            <p class="mt-1 text-sm text-slate-400">Capture received vendor pricing, delivery timeline, and commercial terms for tender {{ $rfq->rfq_number }}.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-700/60 bg-rose-950/40 p-4 text-sm text-rose-200">
            <p class="font-semibold">Please correct the following errors:</p>
            <ul class="mt-1 list-disc pl-5 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.procurement.rfq.quotations.store', $rfq) }}" class="space-y-6" id="quotationForm">
        @csrf

        {{-- Supplier & Commercial Details --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-5">
            <h2 class="text-base font-semibold text-slate-100 border-b border-slate-800 pb-3">1. Supplier & Commercial Terms</h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Supplier / Vendor *</label>
                    <select name="vendor_id" required class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">-- Select Supplier --</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ (string) old('vendor_id') === (string) $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Quotation / Ref Number *</label>
                    <input type="text" name="quotation_number" value="{{ old('quotation_number') }}" required placeholder="e.g. QT-2026-904" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Quotation Date *</label>
                    <input type="date" name="quotation_date" value="{{ old('quotation_date', now()->format('Y-m-d')) }}" required class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Valid Until</label>
                    <input type="date" name="valid_until" value="{{ old('valid_until', now()->addDays(30)->format('Y-m-d')) }}" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Delivery Lead Time (Days) *</label>
                    <input type="number" name="lead_time_days" value="{{ old('lead_time_days', 7) }}" min="0" required class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Payment Terms</label>
                    <input type="text" name="payment_terms" value="{{ old('payment_terms', '30 Days Net') }}" placeholder="e.g. Net 30 days, Cash on Delivery" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Supplier Notes & Warranties</label>
                    <textarea name="notes" rows="2" placeholder="e.g. 1 year manufacturer warranty included. Free delivery on orders over $1,000." class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Line Items Pricing --}}
        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
            <div class="border-b border-slate-800 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-100">2. Quoted Item Prices</h2>
                <p class="text-xs text-slate-400">Enter the supplier's unit price for each requested item.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="quoteLinesTable">
                    <thead class="bg-slate-950/60 text-xs uppercase text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3">Item Details</th>
                            <th class="px-5 py-3 text-right">Required Qty</th>
                            <th class="px-5 py-3 w-44 text-right">Quoted Unit Price *</th>
                            <th class="px-5 py-3 text-right w-40">Line Total</th>
                            <th class="px-5 py-3 min-w-[200px]">Vendor Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach($rfq->items as $rfqItem)
                            @php
                                $qty = (float) $rfqItem->quantity;
                                $unitPrice = old("unit_prices.{$rfqItem->id}", 0);
                                $lineTotal = $qty * (float) $unitPrice;
                            @endphp
                            <tr class="quote-row hover:bg-slate-800/30" data-qty="{{ $qty }}">
                                <td class="px-5 py-3.5">
                                    <div class="font-medium text-slate-100">{{ $rfqItem->item->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $rfqItem->item->code }}</div>
                                    @if($rfqItem->specifications)
                                        <div class="mt-0.5 text-xs text-indigo-300/80 italic">Spec: {{ $rfqItem->specifications }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right font-semibold text-slate-200">
                                    {{ number_format($qty, 4) }} {{ $rfqItem->item->unit_of_measure }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="relative">
                                        <input type="number" step="0.0001" min="0" name="unit_prices[{{ $rfqItem->id }}]" value="{{ $unitPrice }}" required class="unit-price-input w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-right text-sm font-mono text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono font-semibold text-slate-100 line-total-cell">
                                    {{ number_format($lineTotal, 2) }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <input type="text" name="remarks[{{ $rfqItem->id }}]" value="{{ old("remarks.{$rfqItem->id}") }}" placeholder="Brand, model, or alternative" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-xs text-slate-100 focus:border-indigo-500">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-700 bg-slate-950/80">
                        <tr>
                            <th colspan="3" class="px-5 py-4 text-right text-sm font-semibold text-slate-300">Grand Total Quoted:</th>
                            <th class="px-5 py-4 text-right font-mono text-base font-bold text-emerald-400" id="grandTotalCell">0.00</th>
                            <th class="px-5 py-4"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('admin.procurement.rfq.show', $rfq) }}" class="rounded-lg border border-slate-700 bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-700">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none">Save Supplier Quotation</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('quoteLinesTable');
    const grandTotalCell = document.getElementById('grandTotalCell');

    function calculateTotals() {
        let grandTotal = 0;
        const rows = table.querySelectorAll('.quote-row');

        rows.forEach(row => {
            const qty = parseFloat(row.dataset.qty) || 0;
            const input = row.querySelector('.unit-price-input');
            const unitPrice = parseFloat(input.value) || 0;
            const lineTotal = qty * unitPrice;

            const totalCell = row.querySelector('.line-total-cell');
            totalCell.textContent = lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            grandTotal += lineTotal;
        });

        grandTotalCell.textContent = grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    table.addEventListener('input', function (e) {
        if (e.target.classList.contains('unit-price-input')) {
            calculateTotals();
        }
    });

    calculateTotals();
});
</script>
@endsection
