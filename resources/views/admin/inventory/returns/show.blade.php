@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.inventory.returns.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300">&larr; Back to Supplier Returns</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-50 sm:text-3xl">Supplier Return Note {{ $purchaseReturn->return_number }}</h1>
            <p class="mt-1 text-sm text-slate-400">Debit Note Reference: <span class="font-mono text-amber-300">{{ $purchaseReturn->debit_note_number }}</span> · Status: <span class="text-emerald-400 font-semibold">{{ ucfirst($purchaseReturn->status) }}</span></p>
        </div>
        <a href="{{ route('admin.inventory.returns.print', $purchaseReturn) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Debit Note
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-700 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif

    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 md:grid-cols-3">
        <div><p class="text-xs uppercase text-slate-500">Supplier</p><p class="mt-1 text-sm text-slate-200 font-medium">{{ $purchaseReturn->vendor->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Warehouse</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseReturn->warehouse->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Return Date</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseReturn->return_date?->format('d M Y') }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">GRN Reference</p><p class="mt-1 text-sm font-mono text-emerald-300"><a href="{{ route('admin.procurement.goods-received-vouchers.show', $purchaseReturn->goodsReceivedVoucher) }}" class="hover:underline">{{ $purchaseReturn->goodsReceivedVoucher->voucher_number }}</a></p></div>
        <div><p class="text-xs uppercase text-slate-500">PO Reference</p><p class="mt-1 text-sm font-mono text-indigo-300"><a href="{{ route('admin.procurement.purchase-orders.show', $purchaseReturn->purchaseOrder) }}" class="hover:underline">{{ $purchaseReturn->purchaseOrder->purchase_order_number }}</a></p></div>
        <div><p class="text-xs uppercase text-slate-500">Primary Reason</p><p class="mt-1 text-sm text-rose-300 font-medium">{{ ucfirst(str_replace('_', ' ', $purchaseReturn->reason)) }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Processed By</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseReturn->creator->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Posted At</p><p class="mt-1 text-sm text-slate-200">{{ $purchaseReturn->posted_at?->format('d M Y, H:i') }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Debit Note Reference</p><p class="mt-1 text-sm font-mono text-amber-300 font-semibold">{{ $purchaseReturn->debit_note_number }}</p></div>
        @if($purchaseReturn->reason_details)
            <div class="md:col-span-3">
                <p class="text-xs uppercase text-slate-500">Inspection &amp; Rejection Details</p>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-200">{{ $purchaseReturn->reason_details }}</p>
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4">
            <h2 class="font-semibold text-slate-100">Returned Stock Lines (Deducted from Warehouse)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Item</th>
                        <th class="px-5 py-3 text-right">Quantity Returned</th>
                        <th class="px-5 py-3 text-right">Unit Cost</th>
                        <th class="px-5 py-3 text-right">Debit Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @php($grandTotal = 0)
                    @foreach($purchaseReturn->items as $line)
                        @php($grandTotal += (float) $line->total_cost)
                        <tr>
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-slate-200">{{ $line->item->name }}</div>
                                <div class="text-xs text-slate-500">{{ $line->item->code }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-rose-300 font-semibold">
                                -{{ number_format((float) $line->quantity_returned, 2) }} {{ $line->item->unit_of_measure }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-slate-400">
                                {{ number_format((float) $line->unit_cost, 2) }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-emerald-400 font-semibold">
                                {{ number_format((float) $line->total_cost, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-800 bg-slate-950/40 text-sm font-semibold">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-slate-400">Total Debit Note Amount:</td>
                        <td class="px-5 py-3 text-right font-mono text-amber-300 text-base font-bold">{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</div>
@endsection
