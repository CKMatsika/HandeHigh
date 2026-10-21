@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="flex items-center gap-2 text-xs text-indigo-300">
                <a href="{{ route('admin.procurement.rfq.show', $quotation->rfq) }}" class="hover:text-indigo-200">&larr; Back to {{ $quotation->rfq->rfq_number }}</a>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-50">Quotation {{ $quotation->quotation_number }}</h1>
                @php
                    $quoteBadge = match($quotation->status) {
                        'received' => 'bg-slate-800 text-slate-300 border-slate-700',
                        'selected' => 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60',
                        'rejected' => 'bg-rose-950/60 text-rose-400 border-rose-800/60',
                        default => 'bg-slate-800 text-slate-300 border-slate-700',
                    };
                @endphp
                <span class="inline-flex rounded-full border px-3 py-0.5 text-xs font-semibold capitalize {{ $quoteBadge }}">
                    {{ $quotation->status }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-400">Supplier: <span class="font-medium text-slate-200">{{ $quotation->vendor->name }}</span> · RFQ: <span class="font-mono text-indigo-300">{{ $quotation->rfq->rfq_number }}</span></p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.procurement.rfq.comparison', $quotation->rfq) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Compare with Other Bids
            </a>
        </div>
    </div>

    {{-- Details Card --}}
    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 md:grid-cols-4">
        <div>
            <p class="text-xs uppercase text-slate-500">Supplier</p>
            <p class="mt-1 text-sm font-semibold text-slate-200">{{ $quotation->vendor->name }}</p>
            <p class="text-[11px] text-slate-500">{{ $quotation->vendor->email ?? $quotation->vendor->phone }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Quote Date</p>
            <p class="mt-1 text-sm text-slate-200">{{ $quotation->quotation_date?->format('d M Y') }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Delivery Lead Time</p>
            <p class="mt-1 text-sm font-semibold text-indigo-300">{{ $quotation->lead_time_days }} calendar day(s)</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Payment Terms</p>
            <p class="mt-1 text-sm text-slate-200">{{ $quotation->payment_terms ?: 'Standard terms' }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Valid Until</p>
            <p class="mt-1 text-sm text-slate-200">{{ $quotation->valid_until?->format('d M Y') ?? 'Not specified' }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Recorded By</p>
            <p class="mt-1 text-sm text-slate-200">{{ $quotation->creator?->name }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-500">Total Quoted Amount</p>
            <p class="mt-1 text-base font-bold font-mono text-emerald-400">{{ number_format((float) $quotation->total_amount, 2) }}</p>
        </div>
        @if($quotation->notes)
            <div class="md:col-span-4 border-t border-slate-800 pt-3">
                <p class="text-xs uppercase text-slate-500">Supplier Notes / Warranties</p>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-300">{{ $quotation->notes }}</p>
            </div>
        @endif
    </section>

    {{-- Line Items Breakdown --}}
    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4">
            <h2 class="font-semibold text-slate-100">Item Price Breakdown</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Item</th>
                        <th class="px-5 py-3 text-right">Quantity</th>
                        <th class="px-5 py-3 text-right">Unit Price</th>
                        <th class="px-5 py-3 text-right">Line Total</th>
                        <th class="px-5 py-3">Supplier Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @foreach($quotation->items as $line)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-100">{{ $line->item->name }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $line->item->code }}</div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                {{ number_format((float) $line->quantity, 4) }} {{ $line->item->unit_of_measure }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono">
                                {{ number_format((float) $line->unit_price, 4) }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono font-semibold text-slate-100">
                                {{ number_format((float) $line->total_price, 2) }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400">
                                {{ $line->remarks ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-700 bg-slate-950/80">
                    <tr>
                        <th colspan="3" class="px-5 py-4 text-right text-sm font-semibold text-slate-300">Total Quoted Bid:</th>
                        <th class="px-5 py-4 text-right font-mono text-base font-bold text-emerald-400">
                            {{ number_format((float) $quotation->total_amount, 2) }}
                        </th>
                        <th class="px-5 py-4"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</div>
@endsection
