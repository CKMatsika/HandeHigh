@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="flex items-center gap-2 text-xs text-indigo-300">
                <a href="{{ route('admin.procurement.rfq.index') }}" class="hover:text-indigo-200">&larr; All RFQs</a>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-50">{{ $rfq->rfq_number }}</h1>
                @php
                    $statusClasses = match($rfq->status) {
                        'draft' => 'bg-slate-800 text-slate-300 border-slate-700',
                        'published' => 'bg-sky-950/60 text-sky-400 border-sky-800/60',
                        'closed' => 'bg-amber-950/60 text-amber-400 border-amber-800/60',
                        'awarded' => 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60',
                        'cancelled' => 'bg-rose-950/60 text-rose-400 border-rose-800/60',
                        default => 'bg-slate-800 text-slate-300 border-slate-700',
                    };
                @endphp
                <span class="inline-flex rounded-full border px-3 py-0.5 text-xs font-semibold capitalize {{ $statusClasses }}">
                    {{ $rfq->status }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-400">{{ $rfq->title }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($rfq->status === 'draft')
                @can('procurement.rfq.publish')
                    <form method="POST" action="{{ route('admin.procurement.rfq.publish', $rfq) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Publish RFQ (Open for Bids)
                        </button>
                    </form>
                @endcan
            @endif

            @if(in_array($rfq->status, ['draft', 'published', 'closed']) && !in_array($rfq->status, ['awarded', 'cancelled']))
                @can('procurement.quotations.create')
                    <a href="{{ route('admin.procurement.rfq.quotations.create', $rfq) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Record Supplier Quotation
                    </a>
                @endcan
            @endif

            @if($rfq->quotations->isNotEmpty())
                <a href="{{ route('admin.procurement.rfq.comparison', $rfq) }}" class="inline-flex items-center gap-2 rounded-lg border border-indigo-700 bg-indigo-950/60 px-4 py-2.5 text-sm font-semibold text-indigo-200 hover:bg-indigo-900/60">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Quotation Comparison Matrix
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-700/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Awarded Banner if comparison is approved --}}
    @if($rfq->comparison && $rfq->comparison->status === 'approved')
        <div class="rounded-2xl border border-emerald-800 bg-gradient-to-r from-emerald-950/60 via-slate-900/80 to-slate-900/80 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-emerald-500/10 p-2.5 text-emerald-400 ring-1 ring-emerald-500/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-emerald-200">Awarded to: {{ $rfq->comparison->winningVendor->name ?? 'Supplier' }}</h2>
                        <p class="text-xs text-slate-400">Winning Quote: {{ $rfq->comparison->winningQuotation->quotation_number }} (Total: {{ number_format((float) $rfq->comparison->winningQuotation->total_amount, 2) }})</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($rfq->comparison->purchaseOrder)
                        <a href="{{ route('admin.procurement.purchase-orders.show', $rfq->comparison->purchaseOrder) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                            View Purchase Order ({{ $rfq->comparison->purchaseOrder->purchase_order_number }}) &rarr;
                        </a>
                    @endif
                    <a href="{{ route('admin.procurement.rfq.comparison', $rfq) }}" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700">
                        Evaluation Report
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Key Information Grid --}}
    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 md:grid-cols-4">
        <div>
            <p class="text-xs uppercase text-slate-500">Linked Requisition</p>
            @if($rfq->requisition)
                <a href="{{ route('admin.procurement.requisitions.show', $rfq->requisition) }}" class="mt-1 inline-block text-sm font-mono text-indigo-300 hover:underline">
                    {{ $rfq->requisition->requisition_number }}
                </a>
            @else
                <p class="mt-1 text-sm text-slate-400">Direct RFQ</p>
            @endif
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Submission Deadline</p>
            <p class="mt-1 text-sm font-semibold {{ $rfq->submission_deadline?->isPast() ? 'text-amber-400' : 'text-slate-200' }}">
                {{ $rfq->submission_deadline?->format('d M Y') }}
                @if($rfq->submission_deadline?->isPast())
                    <span class="text-[11px] font-normal text-rose-400">(Closed)</span>
                @else
                    <span class="text-[11px] font-normal text-slate-400">({{ $rfq->submission_deadline?->diffForHumans() }})</span>
                @endif
            </p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Created By</p>
            <p class="mt-1 text-sm text-slate-200">{{ $rfq->creator?->name }}</p>
            <p class="text-[11px] text-slate-500">{{ $rfq->created_at->format('d M Y H:i') }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Published At</p>
            <p class="mt-1 text-sm text-slate-200">{{ $rfq->published_at?->format('d M Y H:i') ?? 'Not published' }}</p>
        </div>
        @if($rfq->terms_and_conditions)
            <div class="md:col-span-4 border-t border-slate-800 pt-3">
                <p class="text-xs uppercase text-slate-500">Terms & Delivery Conditions</p>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-300">{{ $rfq->terms_and_conditions }}</p>
            </div>
        @endif
    </section>

    {{-- Line Items Table --}}
    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-slate-100">Items Requested for Quotation</h2>
                <p class="text-xs text-slate-400">Specifications sent to suppliers</p>
            </div>
            <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-300 font-medium">{{ $rfq->items->count() }} item(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr>
                        <th class="px-5 py-3">#</th>
                        <th class="px-5 py-3">Item Details</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3 text-right">Quantity Required</th>
                        <th class="px-5 py-3">Specifications / Instructions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @foreach($rfq->items as $idx => $line)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $idx + 1 }}</td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-100">{{ $line->item->name }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $line->item->code }}</div>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400">
                                {{ $line->item->category?->name ?? 'General' }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-100">
                                {{ number_format((float) $line->quantity, 4) }} {{ $line->item->unit_of_measure }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-300">
                                {{ $line->specifications ?: 'Standard specifications' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Invited Vendors --}}
    @if($rfq->vendors->isNotEmpty())
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Invited Suppliers</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($rfq->vendors as $vendor)
                    @php
                        $hasQuoted = $rfq->quotations->where('vendor_id', $vendor->id)->first();
                    @endphp
                    <div class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <div class="min-w-0 pr-2">
                            <p class="text-sm font-medium text-slate-200 truncate">{{ $vendor->name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ $vendor->email ?? $vendor->phone ?? 'Registered Supplier' }}</p>
                        </div>
                        @if($hasQuoted)
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-400 bg-emerald-950/50 px-2 py-0.5 rounded-full border border-emerald-800/60">
                                Quoted
                            </span>
                        @else
                            <span class="text-[11px] text-slate-500 bg-slate-800 px-2 py-0.5 rounded-full">
                                Pending
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Received Quotations Section --}}
    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-slate-100">Received Quotations & Bids</h2>
                <p class="text-xs text-slate-400">Supplier submissions for price and lead-time evaluation</p>
            </div>
            <div class="flex items-center gap-2">
                @if($rfq->quotations->isNotEmpty())
                    <a href="{{ route('admin.procurement.rfq.comparison', $rfq) }}" class="rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                        View Comparison Matrix
                    </a>
                @endif
                @can('procurement.quotations.create')
                    @if(!in_array($rfq->status, ['awarded', 'cancelled']))
                        <a href="{{ route('admin.procurement.rfq.quotations.create', $rfq) }}" class="rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700">
                            + Add Quote
                        </a>
                    @endif
                @endcan
            </div>
        </div>

        @if($rfq->quotations->isEmpty())
            <div class="px-6 py-8 text-center text-xs text-slate-500">
                No quotations have been recorded yet. Click "Record Supplier Quotation" to enter bids submitted by vendors.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Quote #</th>
                            <th class="px-5 py-3">Supplier</th>
                            <th class="px-5 py-3">Submission Date</th>
                            <th class="px-5 py-3">Delivery Lead Time</th>
                            <th class="px-5 py-3 text-right">Total Bid Amount</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach($rfq->quotations as $quote)
                            @php
                                $quoteBadge = match($quote->status) {
                                    'received' => 'bg-slate-800 text-slate-300 border-slate-700',
                                    'selected' => 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60',
                                    'rejected' => 'bg-rose-950/60 text-rose-400 border-rose-800/60',
                                    default => 'bg-slate-800 text-slate-300',
                                };
                            @endphp
                            <tr class="hover:bg-slate-800/40">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.procurement.quotations.show', $quote) }}" class="font-mono font-semibold text-indigo-300 hover:underline">
                                        {{ $quote->quotation_number }}
                                    </a>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-100">{{ $quote->vendor->name }}</div>
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-400">
                                    {{ $quote->quotation_date?->format('d M Y') }}
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-300">
                                    {{ $quote->lead_time_days }} day(s)
                                </td>
                                <td class="px-5 py-3 text-right font-mono font-semibold text-slate-100">
                                    {{ number_format((float) $quote->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize {{ $quoteBadge }}">
                                        {{ $quote->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.procurement.quotations.show', $quote) }}" class="rounded bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-200 hover:bg-slate-700">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
