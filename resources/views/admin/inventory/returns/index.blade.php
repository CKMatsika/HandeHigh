@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-400 ring-1 ring-inset ring-rose-500/20">Purchases &amp; Stores</span>
                <span class="text-xs text-slate-500">·</span>
                <span class="text-xs text-slate-400">Debit Notes</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-50 sm:text-3xl">Supplier Returns (Debit Notes)</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $school->name }} · Defective, damaged or rejected goods returned to vendors with stock adjustments.</p>
        </div>
        <a href="{{ route('admin.dashboard.stores-manager') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700">
            &larr; Back to Stores Manager
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-700 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Return #</th>
                        <th class="px-5 py-3.5">Debit Note #</th>
                        <th class="px-5 py-3.5">Supplier</th>
                        <th class="px-5 py-3.5">GRN Reference</th>
                        <th class="px-5 py-3.5">Return Date</th>
                        <th class="px-5 py-3.5">Reason</th>
                        <th class="px-5 py-3.5">Items</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($returns as $ret)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4 font-mono font-medium text-rose-300">
                                <a href="{{ route('admin.inventory.returns.show', $ret) }}" class="hover:underline">{{ $ret->return_number }}</a>
                            </td>
                            <td class="px-5 py-4 font-mono text-amber-300">{{ $ret->debit_note_number ?? '—' }}</td>
                            <td class="px-5 py-4 font-medium text-slate-200">{{ $ret->vendor->name }}</td>
                            <td class="px-5 py-4 font-mono text-xs text-emerald-300">
                                <a href="{{ route('admin.procurement.goods-received-vouchers.show', $ret->goodsReceivedVoucher) }}" class="hover:underline">{{ $ret->goodsReceivedVoucher->voucher_number }}</a>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400">{{ $ret->return_date?->format('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full bg-rose-500/10 px-2 py-0.5 text-xs font-semibold text-rose-300 border border-rose-500/20">
                                    {{ ucfirst(str_replace('_', ' ', $ret->reason)) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs">{{ $ret->items->count() }} items</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.inventory.returns.show', $ret) }}" class="rounded border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs text-slate-200 hover:bg-slate-700">
                                    View Return Note
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                <p class="text-sm font-medium">No purchase returns logged yet.</p>
                                <p class="text-xs text-slate-600 mt-1">To return defective or damaged goods, open a Goods Received Note and click "Return Items".</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="border-t border-slate-800 p-4">{{ $returns->links() }}</div>
        @endif
    </section>
</div>
@endsection
