@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('admin.procurement.purchase-orders.show', $voucher->purchaseOrder) }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Back to purchase order</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">Goods received {{ $voucher->voucher_number }}</h1>
        <p class="mt-1 text-sm text-slate-400">Posted to stock · Purchase order {{ $voucher->purchaseOrder->purchase_order_number }}</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-700 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif

    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 md:grid-cols-3">
        <div><p class="text-xs uppercase text-slate-500">Supplier</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->purchaseOrder->vendor->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Warehouse</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->warehouse->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Received date</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->received_date?->format('d M Y') }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Received by</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->receiver->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Posted at</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->posted_at?->format('d M Y H:i') }}</p></div>
        @if($voucher->notes)
            <div class="md:col-span-3"><p class="text-xs uppercase text-slate-500">Notes</p><p class="mt-1 whitespace-pre-wrap text-sm text-slate-200">{{ $voucher->notes }}</p></div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Quantity received</th><th class="px-5 py-3">PO unit cost</th><th class="px-5 py-3">Line value</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($voucher->items as $line)
                        <tr class="text-slate-300">
                            <td class="px-5 py-3">{{ $line->item->name }} <span class="text-xs text-slate-500">{{ $line->item->code }}</span></td>
                            <td class="px-5 py-3">{{ number_format((float) $line->quantity_received, 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->unit_cost, 4) }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
