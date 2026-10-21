@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Procurement & Tender Hub</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $school->name }} · End-to-end procurement lifecycle from RFQ to Purchase Orders & GRN</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.dashboard.stores-manager') }}" class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-700">
                Stores Manager Desk &rarr;
            </a>
            <a href="{{ route('admin.procurement.rfq.index') }}" class="rounded-lg border border-indigo-700/60 bg-indigo-950/60 px-4 py-2.5 text-sm font-semibold text-indigo-200 hover:bg-indigo-900/60">
                RFQs & Bids
            </a>
            @can('procurement.requisitions.create')
                <a href="{{ route('admin.procurement.requisitions.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    + New Requisition
                </a>
            @endcan
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Requisitions Pending</p>
            <p class="mt-2 text-2xl font-bold text-amber-300">{{ number_format($stats['awaiting_review']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Awaiting review</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Approved Requisitions</p>
            <p class="mt-2 text-2xl font-bold text-emerald-400">{{ number_format($stats['approved']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Ready for RFQ or PO</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active RFQs</p>
            <p class="mt-2 text-2xl font-bold text-indigo-400">{{ number_format($stats['open_rfqs'] ?? 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Open for bidding</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pending Awards</p>
            <p class="mt-2 text-2xl font-bold text-amber-400">{{ number_format($stats['pending_comparisons'] ?? 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Evaluation submitted</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Draft POs</p>
            <p class="mt-2 text-2xl font-bold text-slate-200">{{ number_format($stats['draft_orders']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Awaiting issue</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Issued POs</p>
            <p class="mt-2 text-2xl font-bold text-sky-400">{{ number_format($stats['issued_orders']) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Dispatched to vendor</p>
        </div>
    </div>

    {{-- Requisitions Table --}}
    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-slate-100">Department Requisitions</h2>
                <p class="text-xs text-slate-400">Internal supply and expenditure requests</p>
            </div>
            <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-300">{{ $requisitions->total() }} total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">Requester</th>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Needed by</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($requisitions as $requisition)
                        <tr class="text-slate-300 hover:bg-slate-800/40">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-indigo-300">
                                <a href="{{ route('admin.procurement.requisitions.show', $requisition) }}" class="hover:underline">
                                    {{ $requisition->requisition_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3">{{ $requisition->requester->name }}</td>
                            <td class="px-5 py-3">{{ $requisition->department?->name ?? 'General' }}</td>
                            <td class="px-5 py-3">{{ $requisition->needed_by?->format('d M Y') ?? '-' }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $reqBadge = match($requisition->status) {
                                        'approved' => 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60',
                                        'submitted' => 'bg-amber-950/60 text-amber-400 border-amber-800/60',
                                        'rejected' => 'bg-rose-950/60 text-rose-400 border-rose-800/60',
                                        default => 'bg-slate-800 text-slate-300 border-slate-700',
                                    };
                                @endphp
                                <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize {{ $reqBadge }}">
                                    {{ str_replace('_', ' ', $requisition->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($requisition->status === 'approved' && !$requisition->purchaseOrder)
                                        <a href="{{ route('admin.procurement.rfq.create') }}?requisition_id={{ $requisition->id }}" class="rounded bg-indigo-600/80 px-2 py-1 text-xs font-medium text-white hover:bg-indigo-600" title="Invite competitive supplier quotes">
                                            Issue RFQ
                                        </a>
                                        <a href="{{ route('admin.procurement.purchase-orders.create', $requisition) }}" class="rounded bg-slate-800 px-2 py-1 text-xs font-medium text-slate-200 hover:bg-slate-700" title="Direct Purchase Order without tender">
                                            Direct PO
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.procurement.requisitions.show', $requisition) }}" class="text-xs text-indigo-300 hover:text-indigo-200 hover:underline">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No requisitions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-800 px-5 py-3">{{ $requisitions->links() }}</div>
    </section>

    {{-- Purchase Orders Table --}}
    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-slate-100">Recent Purchase Orders</h2>
                <p class="text-xs text-slate-400">Contracts issued to suppliers awaiting delivery & GRN</p>
            </div>
            <a href="{{ route('admin.dashboard.stores-manager') }}" class="text-xs text-indigo-300 hover:underline">Stores Receiving Desk &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr>
                        <th class="px-5 py-3">PO Number</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Requisition</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($purchaseOrders as $purchaseOrder)
                        <tr class="text-slate-300 hover:bg-slate-800/40">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-indigo-300">
                                <a href="{{ route('admin.procurement.purchase-orders.show', $purchaseOrder) }}" class="hover:underline">
                                    {{ $purchaseOrder->purchase_order_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-200">{{ $purchaseOrder->vendor->name }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-400">{{ $purchaseOrder->requisition?->requisition_number ?? 'Direct' }}</td>
                            <td class="px-5 py-3">{{ $purchaseOrder->order_date?->format('d M Y') }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $poBadge = match($purchaseOrder->status) {
                                        'issued' => 'bg-sky-950/60 text-sky-400 border-sky-800/60',
                                        'received' => 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60',
                                        'partially_received' => 'bg-amber-950/60 text-amber-400 border-amber-800/60',
                                        default => 'bg-slate-800 text-slate-300 border-slate-700',
                                    };
                                @endphp
                                <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize {{ $poBadge }}">
                                    {{ ucfirst($purchaseOrder->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if(in_array($purchaseOrder->status, ['issued', 'partially_received']))
                                        <a href="{{ route('admin.procurement.goods-received-vouchers.create', $purchaseOrder) }}" class="rounded bg-indigo-600 px-2 py-1 text-xs font-medium text-white hover:bg-indigo-500">
                                            Receive Goods (GRN)
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.procurement.purchase-orders.show', $purchaseOrder) }}" class="text-xs text-indigo-300 hover:text-indigo-200 hover:underline">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No purchase orders created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
