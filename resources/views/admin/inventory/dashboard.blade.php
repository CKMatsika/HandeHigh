@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-400 ring-1 ring-inset ring-emerald-500/20">Stores & Inventory</span>
                <span class="text-xs text-slate-500">·</span>
                <span class="text-xs text-slate-400">Stores Manager Workspace</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-50 sm:text-3xl">Stores Manager Dashboard</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $school->name }} · Approved Purchase Orders, GRN Inward Receipts, Delivery Notes, Supplier Returns &amp; Physical Stock Ledger</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="#approved-pos" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Receive Approved PO
            </a>
            <a href="{{ route('admin.inventory.returns.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-800/80 bg-rose-950/40 px-3 py-2 text-xs font-semibold text-rose-300 hover:bg-rose-900/60 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H4m0 0l4-4m-4 4l4 4m6 4v1a3 3 0 003 3h3a3 3 0 003-3V7a3 3 0 00-3-3h-3a3 3 0 00-3 3v1"/></svg>
                Supplier Returns
            </a>
            <a href="{{ route('admin.inventory.stock-requests.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Stock Request
            </a>
            <a href="{{ route('admin.inventory.stock-takes.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-cyan-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-cyan-500 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Start Stock Take
            </a>
            <a href="{{ route('admin.inventory.items.create') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Add Item
            </a>
        </div>
    </div>

    <!-- Primary Inventory KPIs -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm hover:border-slate-700 transition">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Active items</p>
                <span class="rounded-lg bg-blue-500/10 p-2 text-blue-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-100">{{ number_format($stats['items']) }}</p>
            <p class="mt-1 text-xs text-slate-400">Across {{ number_format($stats['categories']) }} categories · {{ number_format($stats['warehouses']) }} locations</p>
        </div>

        <div class="rounded-2xl border border-amber-900/40 bg-slate-900/80 p-5 shadow-sm hover:border-amber-700/60 transition">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium uppercase tracking-wider text-amber-300">Items at reorder level</p>
                <span class="rounded-lg bg-amber-500/10 p-2 text-amber-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-bold text-amber-300">{{ number_format($stats['low_stock']) }}</p>
            <a href="{{ route('admin.inventory.stock-levels') }}" class="mt-1 inline-block text-xs text-amber-400/80 hover:underline">View stock level alerts &rarr;</a>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm hover:border-slate-700 transition">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Stock value</p>
                <span class="rounded-lg bg-emerald-500/10 p-2 text-emerald-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-bold text-emerald-300">{{ number_format((float) $stats['inventory_value'], 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">Costing: {{ str_replace('_', ' ', ucfirst($stats['valuation_method'])) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm hover:border-slate-700 transition">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Capitalized asset value</p>
                <span class="rounded-lg bg-indigo-500/10 p-2 text-indigo-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-bold text-indigo-300">{{ number_format((float) $stats['fixed_asset_value'], 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ number_format($stats['total_fixed_assets'] ?? 0) }} registered assets</p>
        </div>
    </div>

    <!-- Stores Receiving & Returns Operations KPIs -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="#approved-pos" class="group rounded-2xl border border-indigo-900/50 bg-slate-900/70 p-4 hover:border-indigo-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Approved POs Pending Inward</span>
                <span class="inline-flex items-center rounded-full bg-indigo-500/10 px-2 py-0.5 text-xs font-semibold text-indigo-300">
                    {{ $stats['pending_po_receipts'] ?? 0 }} awaiting receipt
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-300 group-hover:text-indigo-200">Receive goods against supplier POs &rarr;</p>
        </a>

        <a href="#recent-grns" class="group rounded-2xl border border-emerald-900/50 bg-slate-900/70 p-4 hover:border-emerald-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Goods Received Notes (GRN)</span>
                <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-300">
                    {{ $stats['total_grvs'] ?? 0 }} vouchers
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-300 group-hover:text-emerald-200">View logged receipts &amp; stocks &rarr;</p>
        </a>

        <a href="#recent-grns" class="group rounded-2xl border border-cyan-900/50 bg-slate-900/70 p-4 hover:border-cyan-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Delivery Notes (D/Notes)</span>
                <span class="inline-flex items-center rounded-full bg-cyan-500/10 px-2 py-0.5 text-xs font-semibold text-cyan-300">
                    {{ $stats['active_delivery_notes'] ?? 0 }} tracked
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-300 group-hover:text-cyan-200">Supplier D/Note references &rarr;</p>
        </a>

        <a href="{{ route('admin.inventory.returns.index') }}" class="group rounded-2xl border border-rose-900/50 bg-slate-900/70 p-4 hover:border-rose-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Supplier Returns (Debit Notes)</span>
                <span class="inline-flex items-center rounded-full bg-rose-500/10 px-2 py-0.5 text-xs font-semibold text-rose-300">
                    {{ $stats['total_returns'] ?? 0 }} processed
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-300 group-hover:text-rose-200">Return damaged / rejected items &rarr;</p>
        </a>
    </div>

    <!-- Section 1: Approved Purchase Orders Awaiting Receipt (Pending Inward Deliveries) -->
    <section id="approved-pos" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-800/80 pb-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                    <h2 class="text-base font-bold text-slate-100">Approved Purchase Orders Awaiting Delivery / Receipt</h2>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Approved supplier orders ready for warehouse inspection and Goods Received Note (GRN) posting.</p>
            </div>
            <a href="{{ route('admin.procurement.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">Procurement Hub &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 border-b border-slate-800 bg-slate-950/40">
                    <tr>
                        <th class="py-2.5 px-3">PO Number</th>
                        <th class="py-2.5 px-3">Supplier</th>
                        <th class="py-2.5 px-3">Order Date</th>
                        <th class="py-2.5 px-3">Delivery Warehouse</th>
                        <th class="py-2.5 px-3">Items Ordered</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($approvedPurchaseOrders as $po)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-3 font-mono font-medium text-indigo-300">
                                <a href="{{ route('admin.procurement.purchase-orders.show', $po) }}" class="hover:underline">{{ $po->purchase_order_number }}</a>
                            </td>
                            <td class="py-3 px-3 text-slate-200 font-medium">{{ $po->vendor->name }}</td>
                            <td class="py-3 px-3 text-slate-400 text-xs">{{ $po->order_date?->format('d M Y') ?? '—' }}</td>
                            <td class="py-3 px-3 text-slate-300 text-xs">{{ $po->requisition?->deliveryWarehouse?->name ?? 'Default Warehouse' }}</td>
                            <td class="py-3 px-3 text-slate-300 text-xs">
                                {{ $po->items->count() }} line items
                            </td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $po->status === 'partially_received' ? 'bg-amber-500/10 text-amber-300 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20' }}">
                                    {{ ucfirst(str_replace('_', ' ', $po->status)) }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('admin.procurement.goods-received-vouchers.create', $po) }}" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 transition">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Receive Goods / GRN
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                <p class="text-sm font-medium">No issued purchase orders awaiting receipt at this time.</p>
                                <p class="text-xs text-slate-600 mt-1">Purchase orders issued by procurement will appear here automatically for stores receiving.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Section 2: Recent Goods Received Notes (GRN / GRV) with Delivery Notes (D/Notes) -->
    <section id="recent-grns" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-800/80 pb-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <h2 class="text-base font-bold text-slate-100">Recent Goods Received Notes (GRN / GRV) &amp; Delivery Notes (D/Notes)</h2>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Physical deliveries received into store, cross-referenced with supplier delivery notes.</p>
            </div>
            <span class="text-xs text-slate-400">Total GRVs: {{ number_format($stats['total_grvs'] ?? 0) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 border-b border-slate-800 bg-slate-950/40">
                    <tr>
                        <th class="py-2.5 px-3">GRN / Voucher #</th>
                        <th class="py-2.5 px-3">PO Reference</th>
                        <th class="py-2.5 px-3">Supplier Delivery Note (D/Note)</th>
                        <th class="py-2.5 px-3">Supplier</th>
                        <th class="py-2.5 px-3">Warehouse</th>
                        <th class="py-2.5 px-3">Received Date</th>
                        <th class="py-2.5 px-3">Received By</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recentGoodsReceived as $grv)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-3 font-mono font-medium text-emerald-300">
                                <a href="{{ route('admin.procurement.goods-received-vouchers.show', $grv) }}" class="hover:underline">{{ $grv->voucher_number }}</a>
                            </td>
                            <td class="py-3 px-3 font-mono text-xs text-indigo-300">
                                <a href="{{ route('admin.procurement.purchase-orders.show', $grv->purchaseOrder) }}" class="hover:underline">{{ $grv->purchaseOrder->purchase_order_number }}</a>
                            </td>
                            <td class="py-3 px-3">
                                @if($grv->delivery_note_number)
                                    <span class="inline-flex items-center rounded-md bg-cyan-500/10 px-2 py-0.5 text-xs font-mono font-medium text-cyan-300 ring-1 ring-inset ring-cyan-500/20">
                                        {{ $grv->delivery_note_number }}
                                    </span>
                                    @if($grv->delivery_note_date)
                                        <span class="text-[10px] text-slate-500 block">{{ $grv->delivery_note_date->format('d M Y') }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-500">Not recorded</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-200 text-xs font-medium">{{ $grv->purchaseOrder->vendor->name }}</td>
                            <td class="py-3 px-3 text-slate-300 text-xs">{{ $grv->warehouse->name }}</td>
                            <td class="py-3 px-3 text-slate-400 text-xs">{{ $grv->received_date?->format('d M Y') }}</td>
                            <td class="py-3 px-3 text-slate-400 text-xs">{{ $grv->receiver->name }}</td>
                            <td class="py-3 px-3 text-right space-x-1">
                                <a href="{{ route('admin.procurement.goods-received-vouchers.show', $grv) }}" class="inline-flex items-center rounded border border-slate-700 bg-slate-800 px-2 py-1 text-xs text-slate-200 hover:bg-slate-700">
                                    View GRN
                                </a>
                                <a href="{{ route('admin.inventory.returns.create', $grv) }}" class="inline-flex items-center rounded border border-rose-800/80 bg-rose-950/40 px-2 py-1 text-xs text-rose-300 hover:bg-rose-900/60" title="Return damaged or rejected items to vendor">
                                    Return Items
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                <p class="text-sm font-medium">No Goods Received Notes posted yet.</p>
                                <p class="text-xs text-slate-600 mt-1">Receive an approved purchase order to log your first GRN and supplier delivery note.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Section 3: Recent Goods Returns to Vendor (Debit Notes) -->
    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-800/80 pb-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                    <h2 class="text-base font-bold text-slate-100">Recent Goods Returns to Vendor (Purchase Returns &amp; Debit Notes)</h2>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Defective, damaged, or rejected stock returned to suppliers with formal debit note tracking.</p>
            </div>
            <a href="{{ route('admin.inventory.returns.index') }}" class="text-xs font-semibold text-rose-400 hover:text-rose-300">All Returns &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 border-b border-slate-800 bg-slate-950/40">
                    <tr>
                        <th class="py-2.5 px-3">Return #</th>
                        <th class="py-2.5 px-3">Debit Note #</th>
                        <th class="py-2.5 px-3">Supplier</th>
                        <th class="py-2.5 px-3">GRN Reference</th>
                        <th class="py-2.5 px-3">Return Date</th>
                        <th class="py-2.5 px-3">Reason</th>
                        <th class="py-2.5 px-3">Items Returned</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recentReturns as $ret)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-3 font-mono font-medium text-rose-300">
                                <a href="{{ route('admin.inventory.returns.show', $ret) }}" class="hover:underline">{{ $ret->return_number }}</a>
                            </td>
                            <td class="py-3 px-3 font-mono text-xs text-amber-300">{{ $ret->debit_note_number ?? '—' }}</td>
                            <td class="py-3 px-3 text-slate-200 font-medium text-xs">{{ $ret->vendor->name }}</td>
                            <td class="py-3 px-3 font-mono text-xs text-emerald-300">{{ $ret->goodsReceivedVoucher->voucher_number }}</td>
                            <td class="py-3 px-3 text-slate-400 text-xs">{{ $ret->return_date?->format('d M Y') }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-semibold text-rose-300 border border-rose-500/20">
                                    {{ ucfirst(str_replace('_', ' ', $ret->reason)) }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-300 text-xs">
                                {{ $ret->items->count() }} items
                            </td>
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('admin.inventory.returns.show', $ret) }}" class="inline-flex items-center rounded border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs text-slate-200 hover:bg-slate-700">
                                    View Return Note
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                <p class="text-sm font-medium">No purchase returns or debit notes logged.</p>
                                <p class="text-xs text-slate-600 mt-1">If items from a GRN fail inspection, use "Return Items" to issue a debit note and deduct stock.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Data Tables: Low Stock Alerts & Recent Ledger Audit -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-100">Low stock by warehouse</h2>
                    <p class="text-xs text-slate-400">Items reaching or falling below safety reorder levels</p>
                </div>
                <a href="{{ route('admin.inventory.stock-levels') }}" class="text-xs text-emerald-400 hover:underline">Manage All &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500 border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 pr-3">Item</th>
                            <th class="py-2.5 pr-3">Warehouse</th>
                            <th class="py-2.5 pr-3 text-right">On hand</th>
                            <th class="py-2.5 pr-3 text-right">Reorder at</th>
                            <th class="py-2.5 text-right">Suggested order</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($lowStockBalances as $balance)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3 pr-3 text-slate-200">
                                    <div class="font-medium">{{ $balance->item->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $balance->item->code }}</div>
                                </td>
                                <td class="py-3 pr-3 text-slate-300 text-xs">{{ $balance->warehouse->name }}</td>
                                <td class="py-3 pr-3 text-right text-amber-300 font-semibold">{{ number_format((float) $balance->quantity_on_hand, 2) }} <span class="text-xs text-slate-500 font-normal">{{ $balance->item->unit_of_measure }}</span></td>
                                <td class="py-3 pr-3 text-right text-slate-400">{{ number_format((float) $balance->reorder_level, 2) }}</td>
                                <td class="py-3 text-right text-emerald-400 font-medium">{{ number_format($balance->reorder_quantity !== null ? (float) $balance->reorder_quantity : max(0, (float) $balance->reorder_level - (float) $balance->quantity_on_hand), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">
                                    <p class="text-sm">No low-stock alerts at this time.</p>
                                    <p class="text-xs text-slate-600 mt-1">Reorder alerts trigger automatically when warehouse balances drop below safety levels.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-100">Recent stock movements</h2>
                    <p class="text-xs text-slate-400">Immutable inventory ledger transactions</p>
                </div>
                <span class="text-xs text-slate-500">Live feed</span>
            </div>
            <div class="space-y-3">
                @forelse($recentTransactions as $transaction)
                    <div class="flex items-start justify-between gap-3 border-b border-slate-800/60 pb-3 last:border-0 hover:bg-slate-800/20 px-2 py-1.5 rounded-lg transition">
                        <div>
                            <p class="text-sm font-medium text-slate-200">{{ $transaction->item->name }} <span class="text-xs text-slate-400 font-normal">· {{ $transaction->warehouse->name }}</span></p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                <span class="inline-flex items-center rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-medium text-slate-300">
                                    {{ str_replace('_', ' ', ucfirst($transaction->movement_type)) }}
                                </span>
                                · {{ $transaction->occurred_at->format('d M Y, H:i') }}
                                @if($transaction->recorder)
                                    · by {{ $transaction->recorder->name }}
                                @endif
                            </p>
                        </div>
                        <span class="text-sm font-semibold {{ (float) $transaction->quantity_delta < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                            {{ (float) $transaction->quantity_delta > 0 ? '+' : '' }}{{ number_format((float) $transaction->quantity_delta, 2) }}
                            <span class="text-xs font-normal text-slate-500">{{ $transaction->item->unit_of_measure }}</span>
                        </span>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-500">
                        <p class="text-sm">No stock movements recorded yet.</p>
                        <p class="text-xs text-slate-600 mt-1">Receipts, stock issues, returns, and physical take adjustments will log here automatically.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
