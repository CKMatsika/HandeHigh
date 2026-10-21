@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="flex items-center gap-2 text-xs text-indigo-300">
                <a href="{{ route('admin.procurement.rfq.show', $rfq) }}" class="hover:text-indigo-200">&larr; Back to RFQ {{ $rfq->rfq_number }}</a>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-50">Bid Comparison & Evaluation Report</h1>
                @php
                    $compStatus = $rfq->comparison?->status ?? 'pending_evaluation';
                    $compBadge = match($compStatus) {
                        'approved' => 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60',
                        'submitted' => 'bg-amber-950/60 text-amber-400 border-amber-800/60',
                        default => 'bg-slate-800 text-slate-300 border-slate-700',
                    };
                @endphp
                <span class="inline-flex rounded-full border px-3 py-0.5 text-xs font-semibold capitalize {{ $compBadge }}">
                    {{ str_replace('_', ' ', $compStatus) }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-400">Competitive schedule of quotations, commercial scoring, and award recommendation for {{ $rfq->title }}.</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" onclick="window.print()" class="rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700">
                Print Report
            </button>
            <a href="{{ route('admin.procurement.rfq.show', $rfq) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500">
                View RFQ
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-700/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Purchase Order Generated Alert (if approved) --}}
    @if($rfq->comparison && $rfq->comparison->status === 'approved' && $rfq->comparison->purchaseOrder)
        <div class="rounded-2xl border border-emerald-700/80 bg-emerald-950/40 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-emerald-500/20 p-2.5 text-emerald-400 ring-1 ring-emerald-500/30">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-emerald-200">Contract Awarded & Purchase Order Generated</h2>
                        <p class="text-xs text-slate-300">Winning supplier: <strong class="text-white">{{ $rfq->comparison->winningVendor->name }}</strong>. Purchase Order draft has been placed for store dispatch.</p>
                    </div>
                </div>
                <a href="{{ route('admin.procurement.purchase-orders.show', $rfq->comparison->purchaseOrder) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500">
                    Open Purchase Order ({{ $rfq->comparison->purchaseOrder->purchase_order_number }}) &rarr;
                </a>
            </div>
        </div>
    @endif

    {{-- Supplier Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @php
            $lowestQuote = $rfq->quotations->sortBy('total_amount')->first();
        @endphp
        @foreach($rfq->quotations as $quote)
            @php
                $isWinner = $rfq->comparison && (int) $rfq->comparison->winning_quotation_id === (int) $quote->id;
                $isLowest = $lowestQuote && (int) $lowestQuote->id === (int) $quote->id;
            @endphp
            <div class="rounded-2xl border {{ $isWinner ? 'border-emerald-600 bg-emerald-950/20 ring-1 ring-emerald-500/30' : ($isLowest ? 'border-indigo-600/60 bg-slate-900/90' : 'border-slate-800 bg-slate-900/80') }} p-5 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-100">{{ $quote->vendor->name }}</h3>
                            @if($isWinner)
                                <span class="rounded bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">Selected Winner</span>
                            @elseif($isLowest)
                                <span class="rounded bg-indigo-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">Lowest Bid</span>
                            @endif
                        </div>
                        <p class="text-xs font-mono text-slate-400 mt-0.5">Ref: {{ $quote->quotation_number }}</p>
                    </div>
                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-300 font-medium capitalize">{{ $quote->status }}</span>
                </div>

                <div class="space-y-1.5 text-xs border-t border-slate-800 pt-3">
                    <div class="flex justify-between text-slate-400">
                        <span>Quoted Total:</span>
                        <span class="font-mono font-bold text-sm text-emerald-400">{{ number_format((float) $quote->total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Lead Time:</span>
                        <span class="text-slate-200 font-medium">{{ $quote->lead_time_days }} days</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Payment Terms:</span>
                        <span class="text-slate-200">{{ $quote->payment_terms ?: 'Standard' }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Quote Date:</span>
                        <span class="text-slate-300">{{ $quote->quotation_date?->format('d M Y') }}</span>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-800">
                    <a href="{{ route('admin.procurement.quotations.show', $quote) }}" class="text-xs text-indigo-300 hover:text-indigo-200 hover:underline">
                        View Full Quotation &rarr;
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Side-by-Side Item Comparison Matrix --}}
    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold text-slate-100">Item Price Comparison Matrix</h2>
                <p class="text-xs text-slate-400">Direct per-line cost evaluation across all bidding vendors</p>
            </div>
            <span class="text-xs text-slate-500">All prices in local currency</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3 min-w-[200px]">Item Description</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        @foreach($rfq->quotations as $quote)
                            <th class="px-4 py-3 text-center border-l border-slate-800 min-w-[180px]">
                                <div class="font-bold text-slate-200">{{ $quote->vendor->name }}</div>
                                <div class="text-[10px] text-slate-500 font-normal">({{ $quote->lead_time_days }}d lead)</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @foreach($matrix as $rfqItemId => $row)
                        @php
                            // Find lowest unit price for this item
                            $prices = collect($row['quotes'])->pluck('unit_price')->filter(fn($p) => $p !== null);
                            $minPrice = $prices->isNotEmpty() ? $prices->min() : null;
                        @endphp
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-100">{{ $row['item']->name }}</div>
                                <div class="text-xs text-slate-500">{{ $row['item']->code }}</div>
                                @if(!empty($row['specifications']))
                                    <div class="text-[11px] text-indigo-300/80 italic mt-0.5">Spec: {{ $row['specifications'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-slate-200">
                                {{ number_format((float) $row['quantity'], 4) }} <span class="text-xs text-slate-500">{{ $row['item']->unit_of_measure }}</span>
                            </td>

                            @foreach($rfq->quotations as $quote)
                                @php
                                    $qData = $row['quotes'][$quote->id] ?? null;
                                    $isLowestItemPrice = $qData && $minPrice !== null && (float) $qData['unit_price'] === (float) $minPrice;
                                @endphp
                                <td class="px-4 py-3 border-l border-slate-800 text-right font-mono {{ $isLowestItemPrice ? 'bg-emerald-950/20' : '' }}">
                                    @if($qData && $qData['unit_price'] !== null)
                                        <div class="text-xs {{ $isLowestItemPrice ? 'text-emerald-400 font-bold' : 'text-slate-300' }}">
                                            @ {{ number_format((float) $qData['unit_price'], 4) }}
                                        </div>
                                        <div class="text-sm font-semibold {{ $isLowestItemPrice ? 'text-emerald-300' : 'text-slate-100' }}">
                                            {{ number_format((float) $qData['total_price'], 2) }}
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-600">No bid</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-700 bg-slate-950">
                    <tr>
                        <th colspan="2" class="px-5 py-4 text-right text-sm font-bold text-slate-200">Total Evaluated Bid:</th>
                        @foreach($rfq->quotations as $quote)
                            @php
                                $isWinner = $rfq->comparison && (int) $rfq->comparison->winning_quotation_id === (int) $quote->id;
                            @endphp
                            <th class="px-4 py-4 text-right border-l border-slate-800 font-mono {{ $isWinner ? 'bg-emerald-950/40 text-emerald-300' : 'text-slate-100' }}">
                                <div class="text-base font-bold">{{ number_format((float) $quote->total_amount, 2) }}</div>
                                <div class="text-[11px] font-normal text-slate-400">{{ $quote->lead_time_days }} days lead</div>
                            </th>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Evaluation Decision & Award Section --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-6">
        <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-semibold text-slate-100">Procurement Committee Evaluation & Award Recommendation</h2>
            <p class="text-xs text-slate-400">Formalize supplier selection based on best value for money, compliance to technical specifications, and delivery capability.</p>
        </div>

        @if($rfq->comparison)
            {{-- Comparison Already Submitted or Approved --}}
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <div>
                        <p class="text-xs uppercase text-slate-500">Selected Supplier</p>
                        <p class="mt-1 text-sm font-bold text-emerald-400">{{ $rfq->comparison->winningVendor->name }}</p>
                        <p class="text-xs text-slate-400">Quote: {{ $rfq->comparison->winningQuotation->quotation_number }} ({{ number_format((float) $rfq->comparison->winningQuotation->total_amount, 2) }})</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-slate-500">Evaluation Status</p>
                        <p class="mt-1 text-sm font-semibold capitalize {{ $compBadge }} inline-block px-2.5 py-0.5 rounded-full border">
                            {{ $rfq->comparison->status }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Evaluated by {{ $rfq->comparison->evaluator?->name }} on {{ $rfq->comparison->evaluation_date?->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-slate-500">Approval State</p>
                        @if($rfq->comparison->status === 'approved')
                            <p class="mt-1 text-sm font-semibold text-emerald-400">Approved by {{ $rfq->comparison->approver?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $rfq->comparison->approved_at?->format('d M Y H:i') }}</p>
                        @else
                            <p class="mt-1 text-sm font-semibold text-amber-400">Pending Executive Approval</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <p class="text-xs uppercase text-slate-500">Selection Justification / Evaluation Notes</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-200">{{ $rfq->comparison->selection_justification }}</p>
                </div>

                {{-- Approval Action if submitted and not yet approved --}}
                @if($rfq->comparison->status === 'submitted')
                    @can('procurement.approve')
                        <div class="rounded-xl border border-indigo-700/60 bg-indigo-950/30 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-bold text-indigo-100">Authorize Award & Generate Purchase Order</h3>
                                <p class="text-xs text-indigo-300">Approving this bid evaluation will immediately award the tender to {{ $rfq->comparison->winningVendor->name }} and automatically create a Purchase Order draft.</p>
                            </div>
                            <form method="POST" action="{{ route('admin.procurement.rfq.comparison.approve', $rfq) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500">
                                    Approve Award & Create PO
                                </button>
                            </form>
                        </div>
                    @endcan
                @endif
            </div>
        @else
            {{-- Initial Submission Form --}}
            @can('procurement.comparison.create')
                <form method="POST" action="{{ route('admin.procurement.rfq.comparison.store', $rfq) }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Select Winning Supplier & Quotation *</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($rfq->quotations as $quote)
                                <label class="flex items-start gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-4 hover:border-indigo-600/60 cursor-pointer">
                                    <input type="radio" name="winning_quotation_id" value="{{ $quote->id }}" required class="mt-1 h-4 w-4 border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <p class="text-sm font-bold text-slate-100">{{ $quote->vendor->name }}</p>
                                        <p class="text-xs text-slate-400">Quote #{{ $quote->quotation_number }}</p>
                                        <p class="text-sm font-mono font-bold text-emerald-400 mt-1">{{ number_format((float) $quote->total_amount, 2) }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $quote->lead_time_days }} days delivery lead time</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Selection Justification & Evaluation Rationale *</label>
                        <textarea name="selection_justification" rows="4" required placeholder="State the committee's justification for selection (e.g., Evaluated as the lowest responsive bid conforming to technical specifications, delivery within required 7-day period, and satisfactory payment terms)." class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ old('selection_justification') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Save Bid Selection & Evaluation Report
                        </button>
                    </div>
                </form>
            @else
                <p class="text-xs text-slate-500">You do not have permission to submit bid evaluation reports.</p>
            @endcan
        @endif
    </div>
</div>
@endsection
