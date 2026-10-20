@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Procurement</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $school->name }} · Requisitions and purchase orders</p>
        </div>
        @can('procurement.requisitions.create')
            <a href="{{ route('admin.procurement.requisitions.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                New requisition
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Awaiting approval</p>
            <p class="mt-2 text-2xl font-bold text-amber-300">{{ number_format($stats['awaiting_review']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Approved, no PO yet</p>
            <p class="mt-2 text-2xl font-bold text-emerald-300">{{ number_format($stats['approved']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Draft purchase orders</p>
            <p class="mt-2 text-2xl font-bold text-slate-100">{{ number_format($stats['draft_orders']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Issued purchase orders</p>
            <p class="mt-2 text-2xl font-bold text-sky-300">{{ number_format($stats['issued_orders']) }}</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4">
            <h2 class="font-semibold text-slate-100">Requisitions</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Requester</th><th class="px-5 py-3">Department</th><th class="px-5 py-3">Needed by</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($requisitions as $requisition)
                        <tr class="text-slate-300">
                            <td class="px-5 py-3 font-mono text-xs">{{ $requisition->requisition_number }}</td>
                            <td class="px-5 py-3">{{ $requisition->requester->name }}</td>
                            <td class="px-5 py-3">{{ $requisition->department?->name ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $requisition->needed_by?->format('d M Y') ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-slate-700 px-2.5 py-1 text-xs">{{ str_replace('_', ' ', ucfirst($requisition->status)) }}</span>
                            </td>
                            <td class="px-5 py-3"><a href="{{ route('admin.procurement.requisitions.show', $requisition) }}" class="text-indigo-300 hover:text-indigo-200">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No requisitions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-800 px-5 py-3">{{ $requisitions->links() }}</div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4">
            <h2 class="font-semibold text-slate-100">Recent purchase orders</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr><th class="px-5 py-3">PO number</th><th class="px-5 py-3">Supplier</th><th class="px-5 py-3">Requisition</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($purchaseOrders as $purchaseOrder)
                        <tr class="text-slate-300">
                            <td class="px-5 py-3 font-mono text-xs">{{ $purchaseOrder->purchase_order_number }}</td>
                            <td class="px-5 py-3">{{ $purchaseOrder->vendor->name }}</td>
                            <td class="px-5 py-3 font-mono text-xs">{{ $purchaseOrder->requisition->requisition_number }}</td>
                            <td class="px-5 py-3">{{ $purchaseOrder->order_date?->format('d M Y') }}</td>
                            <td class="px-5 py-3"><span class="rounded-full bg-slate-700 px-2.5 py-1 text-xs">{{ ucfirst($purchaseOrder->status) }}</span></td>
                            <td class="px-5 py-3"><a href="{{ route('admin.procurement.purchase-orders.show', $purchaseOrder) }}" class="text-indigo-300 hover:text-indigo-200">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No purchase orders created.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
