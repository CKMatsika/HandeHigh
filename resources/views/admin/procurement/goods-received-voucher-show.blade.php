@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('admin.procurement.purchase-orders.show', $voucher->purchaseOrder) }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Back to purchase order</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-50">Goods received {{ $voucher->voucher_number }}</h1>
            <p class="mt-1 text-sm text-slate-400">Posted to stock · Purchase order {{ $voucher->purchaseOrder->purchase_order_number }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.procurement.goods-received-vouchers.print', $voucher) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print GRV
            </a>
            @can('procurement.supplier-invoices.create')
                @if($hasPendingInvoice)
                    <span class="inline-flex rounded-lg border border-amber-700/50 px-4 py-2 text-sm text-amber-200">Supplier invoice under review</span>
                @elseif($unbilledQuantity <= 0)
                    <span class="inline-flex rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-400">Received quantity fully invoiced</span>
                @else
                    <a href="{{ route('admin.procurement.supplier-invoices.create', $voucher) }}" class="inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Record supplier invoice</a>
                @endif
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-700 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif

    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 md:grid-cols-3">
        <div><p class="text-xs uppercase text-slate-500">Supplier</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->purchaseOrder->vendor->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Warehouse</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->warehouse->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Received date</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->received_date?->format('d M Y') }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Delivery Note (D/Note) #</p><p class="mt-1 text-sm font-mono text-cyan-300">{{ $voucher->delivery_note_number ?? 'Not recorded' }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Delivery Note Date</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->delivery_note_date?->format('d M Y') ?? '—' }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Carrier / Transporter</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->carrier_name ?? '—' }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Received by</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->receiver->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Posted at</p><p class="mt-1 text-sm text-slate-200">{{ $voucher->posted_at?->format('d M Y H:i') }}</p></div>
        @if($voucher->notes)
            <div class="md:col-span-3"><p class="text-xs uppercase text-slate-500">Notes</p><p class="mt-1 whitespace-pre-wrap text-sm text-slate-200">{{ $voucher->notes }}</p></div>
        @endif
    </section>

    @if($voucher->supplierInvoices()->exists())
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
            <div class="border-b border-slate-800 px-5 py-4"><h2 class="font-semibold text-slate-100">Supplier invoices</h2></div>
            <div class="divide-y divide-slate-800">
                @foreach($voucher->supplierInvoices()->latest('invoice_date')->get() as $invoice)
                    <a href="{{ route('admin.procurement.supplier-invoices.show', $invoice) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-slate-800/50">
                        <span class="font-mono text-indigo-300">{{ $invoice->invoice_number }}</span>
                        <span class="text-slate-400">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }} · {{ number_format((float) $invoice->total_amount, 2) }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

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
