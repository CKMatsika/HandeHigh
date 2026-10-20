@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-50">Stores & Inventory</h1>
        <p class="mt-1 text-sm text-slate-400">{{ $school->name }} · Stores Person workspace</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Active items</p>
            <p class="mt-2 text-2xl font-bold text-slate-100">{{ number_format($stats['items']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Categories</p>
            <p class="mt-2 text-2xl font-bold text-slate-100">{{ number_format($stats['categories']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Warehouses</p>
            <p class="mt-2 text-2xl font-bold text-slate-100">{{ number_format($stats['warehouses']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Items at reorder level</p>
            <p class="mt-2 text-2xl font-bold text-amber-300">{{ number_format($stats['low_stock']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Costing method</p>
            <p class="mt-2 text-lg font-bold text-slate-100">{{ str_replace('_', ' ', ucfirst($stats['valuation_method'])) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <p class="text-sm text-slate-400">Stock value</p>
            <p class="mt-2 text-lg font-bold text-slate-100">{{ number_format((float) $stats['inventory_value'], 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
        @can('inventory.catalog.manage')
            <a href="{{ route('admin.inventory.items.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50">
                <h2 class="font-semibold text-slate-100">Item catalogue</h2>
                <p class="mt-1 text-sm text-slate-400">Maintain stocked supplies, assets, and non-stock items.</p>
            </a>
            <a href="{{ route('admin.inventory.categories.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50">
                <h2 class="font-semibold text-slate-100">Categories</h2>
                <p class="mt-1 text-sm text-slate-400">Organize items using school-specific categories.</p>
            </a>
        @else
            <a href="{{ route('admin.inventory.items.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50">
                <h2 class="font-semibold text-slate-100">Item catalogue</h2>
                <p class="mt-1 text-sm text-slate-400">Browse the school inventory catalogue.</p>
            </a>
            <a href="{{ route('admin.inventory.categories.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50">
                <h2 class="font-semibold text-slate-100">Categories</h2>
                <p class="mt-1 text-sm text-slate-400">Browse inventory categories.</p>
            </a>
        @endcan
        <a href="{{ route('admin.inventory.warehouses.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50">
            <h2 class="font-semibold text-slate-100">Warehouses</h2>
            <p class="mt-1 text-sm text-slate-400">View the school’s separate stock locations.</p>
        </a>
        @can('inventory.stock-requests.view')
            <a href="{{ route('admin.inventory.stock-requests.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50">
                <h2 class="font-semibold text-slate-100">Stock requests</h2>
                <p class="mt-1 text-sm text-slate-400">Review staff requests and record controlled stock issues.</p>
            </a>
        @endcan
        @can('inventory.stock-takes.view')
            <a href="{{ route('admin.inventory.stock-takes.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50">
                <h2 class="font-semibold text-slate-100">Stock takes</h2>
                <p class="mt-1 text-sm text-slate-400">Record physical counts and review approved stock adjustments.</p>
            </a>
        @endcan
        @can('inventory.stock-levels.manage')
            <a href="{{ route('admin.inventory.stock-levels') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 hover:border-emerald-500/50">
                <h2 class="font-semibold text-slate-100">Stock on hand & reorder</h2>
                <p class="mt-1 text-sm text-slate-400">View warehouse balances and set reorder alerts and quantities.</p>
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-slate-100">Low stock by warehouse</h2>
                <span class="text-xs text-slate-400">Configured reorder level reached</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr><th class="py-2 pr-3">Item</th><th class="py-2 pr-3">Warehouse</th><th class="py-2 pr-3">On hand</th><th class="py-2 pr-3">Reorder at</th><th class="py-2">Suggested order</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($lowStockBalances as $balance)
                            <tr>
                                <td class="py-3 pr-3 text-slate-200">{{ $balance->item->name }} <span class="text-xs text-slate-500">({{ $balance->item->code }})</span></td>
                                <td class="py-3 pr-3 text-slate-300">{{ $balance->warehouse->name }}</td>
                                <td class="py-3 pr-3 text-amber-300">{{ number_format((float) $balance->quantity_on_hand, 4) }} {{ $balance->item->unit_of_measure }}</td>
                                <td class="py-3 pr-3 text-slate-300">{{ number_format((float) $balance->reorder_level, 4) }}</td>
                                <td class="py-3 text-slate-300">{{ number_format($balance->reorder_quantity !== null ? (float) $balance->reorder_quantity : max(0, (float) $balance->reorder_level - (float) $balance->quantity_on_hand), 4) }} {{ $balance->item->unit_of_measure }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">No low-stock balances yet. Reorder alerts appear once stock balances and levels are configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <h2 class="mb-4 font-semibold text-slate-100">Recent stock movements</h2>
            <div class="space-y-3">
                @forelse($recentTransactions as $transaction)
                    <div class="flex items-start justify-between gap-3 border-b border-slate-800 pb-3 last:border-0">
                        <div>
                            <p class="text-sm text-slate-200">{{ $transaction->item->name }} <span class="text-xs text-slate-500">· {{ $transaction->warehouse->name }}</span></p>
                            <p class="mt-1 text-xs text-slate-400">{{ str_replace('_', ' ', ucfirst($transaction->movement_type)) }} · {{ $transaction->occurred_at->format('d M Y H:i') }}</p>
                        </div>
                        <span class="text-sm font-medium {{ (float) $transaction->quantity_delta < 0 ? 'text-rose-300' : 'text-emerald-300' }}">
                            {{ (float) $transaction->quantity_delta > 0 ? '+' : '' }}{{ number_format((float) $transaction->quantity_delta, 4) }}
                        </span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-500">No stock movements recorded yet. Receipts, issues, transfers, and opening balances will populate this ledger in later phases.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
