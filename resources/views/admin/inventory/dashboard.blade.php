@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-400 ring-1 ring-inset ring-emerald-500/20">Stores & Inventory</span>
                <span class="text-xs text-slate-500">·</span>
                <span class="text-xs text-slate-400">Workspace</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-50 sm:text-3xl">Stores Manager Dashboard</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $school->name }} · Real-time inventory balances, stock movements, requests, physical counts & asset register</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
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

    <!-- Secondary Operations KPIs -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.inventory.stock-requests.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/60 p-4 hover:border-amber-500/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Pending Stock Requests</span>
                <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-semibold text-amber-300">
                    {{ $stats['pending_requests'] ?? 0 }} pending
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-300 group-hover:text-amber-200">Review staff requisition queues &rarr;</p>
        </a>

        <a href="{{ route('admin.inventory.stock-takes.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/60 p-4 hover:border-cyan-500/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Stock Takes in Progress</span>
                <span class="inline-flex items-center rounded-full bg-cyan-500/10 px-2 py-0.5 text-xs font-semibold text-cyan-300">
                    {{ $stats['pending_stock_takes'] ?? 0 }} active
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-300 group-hover:text-cyan-200">Open physical stock counts &rarr;</p>
        </a>

        <a href="{{ route('admin.inventory.fixed-assets.depreciation') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/60 p-4 hover:border-violet-500/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Depreciation Schedules</span>
                <span class="inline-flex items-center rounded-full bg-violet-500/10 px-2 py-0.5 text-xs font-semibold text-violet-300">
                    Monthly Run
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-300 group-hover:text-violet-200">Run straight-line amortisation &rarr;</p>
        </a>

        <a href="{{ route('admin.inventory.fixed-assets.maintenance') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/60 p-4 hover:border-emerald-500/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Asset Maintenance Tasks</span>
                <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-300">
                    {{ $stats['pending_maintenance'] ?? 0 }} scheduled
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-300 group-hover:text-emerald-200">Track repairs & service logs &rarr;</p>
        </a>
    </div>

    <!-- Quick Navigation Modules -->
    <div>
        <h2 class="text-base font-semibold text-slate-200 mb-3">Inventory & Asset Management Modules</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-4">
            <a href="{{ route('admin.inventory.items.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-emerald-500/10 p-2.5 text-emerald-400 group-hover:bg-emerald-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-emerald-300 transition">Item Catalogue</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">Maintain stocked supplies, assets, consumable units, and codes.</p>
            </a>

            <a href="{{ route('admin.inventory.categories.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-blue-500/10 p-2.5 text-blue-400 group-hover:bg-blue-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-emerald-300 transition">Categories</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">Organize stock using school-specific inventory and asset categories.</p>
            </a>

            <a href="{{ route('admin.inventory.warehouses.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-amber-500/10 p-2.5 text-amber-400 group-hover:bg-amber-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-emerald-300 transition">Warehouses & Stores</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">View and manage physical store locations and warehouse managers.</p>
            </a>

            <a href="{{ route('admin.inventory.stock-requests.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-amber-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-amber-500/10 p-2.5 text-amber-400 group-hover:bg-amber-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-amber-300 transition">Stock Requests</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">Review staff requisitions, approvals, and post controlled stock issues.</p>
            </a>

            <a href="{{ route('admin.inventory.stock-takes.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-cyan-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-cyan-500/10 p-2.5 text-cyan-400 group-hover:bg-cyan-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-cyan-300 transition">Stock Takes</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">Conduct physical counts, review variances, and approve stock adjustments.</p>
            </a>

            <a href="{{ route('admin.inventory.stock-levels') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-violet-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-violet-500/10 p-2.5 text-violet-400 group-hover:bg-violet-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-violet-300 transition">Stock on Hand & Reorder</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">View balances by warehouse and configure reorder minimums.</p>
            </a>

            <a href="{{ route('admin.inventory.fixed-assets.index') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-indigo-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-indigo-500/10 p-2.5 text-indigo-400 group-hover:bg-indigo-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-indigo-300 transition">Fixed Asset Register</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">Asset tags, serials, custodians, room allocations, transfers & disposals.</p>
            </a>

            <a href="{{ route('admin.inventory.fixed-assets.depreciation') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-violet-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-violet-500/10 p-2.5 text-violet-400 group-hover:bg-violet-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-violet-300 transition">Depreciation Schedules</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">Run monthly straight-line amortisation and post journal entries.</p>
            </a>

            <a href="{{ route('admin.inventory.fixed-assets.maintenance') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-amber-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-amber-500/10 p-2.5 text-amber-400 group-hover:bg-amber-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-amber-300 transition">Asset Maintenance</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">Preventative maintenance, repair logs, service vendors, and GL postings.</p>
            </a>

            <a href="{{ route('admin.inventory.settings.edit') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50 hover:bg-slate-900 transition">
                <div class="flex items-center gap-3">
                    <span class="rounded-xl bg-teal-500/10 p-2.5 text-teal-400 group-hover:bg-teal-500/20 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    </span>
                    <h3 class="font-semibold text-slate-100 group-hover:text-teal-300 transition">Asset Capitalization</h3>
                </div>
                <p class="mt-2 text-sm text-slate-400">Threshold: {{ $stats['capitalization_threshold'] === null ? 'All asset items' : number_format((float) $stats['capitalization_threshold'], 2) }}</p>
            </a>
        </div>
    </div>

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
