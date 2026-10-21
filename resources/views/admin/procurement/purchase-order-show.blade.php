@extends('layouts.app')

@section('content')
@php($total = (float) $purchaseOrder->items->sum('line_total'))
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('admin.procurement.index') }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Procurement</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-50">Purchase order {{ $purchaseOrder->purchase_order_number }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ ucfirst($purchaseOrder->status) }} · Requisition {{ $purchaseOrder->requisition->requisition_number }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.procurement.purchase-orders.print', $purchaseOrder) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print LPO
            </a>
            @can('procurement.purchase-orders.issue')
                @if($purchaseOrder->status === 'draft')
                    <form method="POST" action="{{ route('admin.procurement.purchase-orders.issue', $purchaseOrder) }}">
                        @csrf
                        <button class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">Mark issued to supplier</button>
                    </form>
                @endif
            @endcan
            @can('procurement.grv.create')
                @if(in_array($purchaseOrder->status, ['issued', 'partially_received'], true))
                    <a href="{{ route('admin.procurement.goods-received-vouchers.create', $purchaseOrder) }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Receive goods</a>
                @endif
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-700 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif

    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 md:grid-cols-3">
        <div><p class="text-xs uppercase text-slate-500">Supplier</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseOrder->vendor->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Order date</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseOrder->order_date?->format('d M Y') }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Expected delivery</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseOrder->expected_delivery_date?->format('d M Y') ?? 'Not specified' }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Created by</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseOrder->creator->name }}</p></div>
        @if($purchaseOrder->issuer)
            <div><p class="text-xs uppercase text-slate-500">Issued by</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseOrder->issuer->name }} · {{ $purchaseOrder->issued_at?->format('d M Y H:i') }}</p></div>
        @endif
        @if($purchaseOrder->notes)
            <div class="md:col-span-3"><p class="text-xs uppercase text-slate-500">Notes</p><p class="mt-1 whitespace-pre-wrap text-sm text-slate-200">{{ $purchaseOrder->notes }}</p></div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400"><tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Quantity</th><th class="px-5 py-3">Unit cost</th><th class="px-5 py-3">Line total</th></tr></thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($purchaseOrder->items as $line)
                        <tr class="text-slate-300">
                            <td class="px-5 py-3">{{ $line->item->name }} <span class="text-xs text-slate-500">{{ $line->item->code }}</span></td>
                            <td class="px-5 py-3">{{ number_format((float) $line->quantity_ordered, 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->unit_cost, 4) }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-700"><tr><th colspan="3" class="px-5 py-4 text-right text-sm text-slate-300">Estimated order total</th><th class="px-5 py-4 text-left text-slate-100">{{ number_format($total, 2) }}</th></tr></tfoot>
            </table>
        </div>
    </section>

    @if($purchaseOrder->goodsReceivedVouchers()->exists())
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
            <div class="border-b border-slate-800 px-5 py-4"><h2 class="font-semibold text-slate-100">Goods received vouchers</h2></div>
            <div class="divide-y divide-slate-800">
                @foreach($purchaseOrder->goodsReceivedVouchers()->latest('received_date')->get() as $voucher)
                    <a href="{{ route('admin.procurement.goods-received-vouchers.show', $voucher) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-slate-800/50">
                        <span class="font-mono text-indigo-300">{{ $voucher->voucher_number }}</span>
                        <span class="text-slate-400">{{ $voucher->received_date?->format('d M Y') }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
