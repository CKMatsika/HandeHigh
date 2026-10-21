@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="flex items-center gap-2 text-xs text-emerald-400">
                <a href="{{ route('admin.inventory.items.index') }}" class="hover:text-emerald-300">&larr; Back to Inventory Catalog</a>
                <span>&bull;</span>
                <span class="text-slate-400">Stores Ledger</span>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-50">{{ $item->name }}</h1>
                <span class="rounded-md bg-slate-800 px-2.5 py-1 font-mono text-xs font-semibold text-emerald-400 border border-slate-700">
                    {{ $item->code }}
                </span>
                <span class="rounded-full bg-slate-800 px-3 py-0.5 text-xs text-slate-300 border border-slate-700">
                    {{ $item->category->name }}
                </span>
                <span class="rounded-full bg-slate-800 px-3 py-0.5 text-xs text-slate-400 border border-slate-700">
                    {{ ucfirst(str_replace('_', ' ', $item->item_type)) }} · {{ $item->unit_of_measure }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-400">Official Stock Bin Card and historical inventory ledger movements.</p>
        </div>

        <div class="flex items-center gap-3 no-print">
            <button type="button" onclick="window.print()" class="rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700">
                <svg class="inline-block h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Bin Card
            </button>
            <a href="{{ route('admin.inventory.items.edit', $item) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                Edit Item
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-400">Current Stock On Hand</div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-slate-50">{{ number_format($totalOnHand, 2) }}</span>
                <span class="text-xs text-slate-400">{{ $item->unit_of_measure }}</span>
            </div>
            <div class="mt-1 text-xs {{ $totalOnHand > 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                {{ $totalOnHand > 0 ? 'In Stock' : 'Out of Stock / Depleted' }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-400">Weighted Average Cost</div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-slate-50">{{ number_format($averageCost, 2) }}</span>
                <span class="text-xs text-slate-400">per {{ $item->unit_of_measure }}</span>
            </div>
            <div class="mt-1 text-xs text-slate-400">Moving inventory valuation</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-400">Stock Asset Valuation</div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-emerald-400 font-mono">{{ number_format($totalOnHand * $averageCost, 2) }}</span>
            </div>
            <div class="mt-1 text-xs text-slate-400">Quantity on hand &times; Average cost</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-400">Active Warehouses</div>
            <div class="mt-2 text-sm text-slate-200">
                @if($item->balances->isNotEmpty())
                    <ul class="space-y-1 text-xs">
                        @foreach($item->balances as $balance)
                            <li class="flex justify-between">
                                <span class="text-slate-400">{{ $balance->warehouse->name }}:</span>
                                <span class="font-semibold text-slate-100">{{ number_format((float) $balance->quantity_on_hand, 2) }} {{ $item->unit_of_measure }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span class="text-xs text-slate-500">No warehouse stock allocated.</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('admin.inventory.items.bincard', $item) }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 no-print">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4 sm:items-end">
            <div>
                <label for="warehouse_id" class="block text-xs font-medium uppercase tracking-wider text-slate-400 mb-1">Warehouse Filter</label>
                <select id="warehouse_id" name="warehouse_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-emerald-500 focus:outline-none">
                    <option value="">All Warehouses (Consolidated)</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected((string)$warehouseId === (string)$wh->id)>{{ $wh->name }} ({{ $wh->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-xs font-medium uppercase tracking-wider text-slate-400 mb-1">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-emerald-500 focus:outline-none">
            </div>
            <div>
                <label for="end_date" class="block text-xs font-medium uppercase tracking-wider text-slate-400 mb-1">End Date</label>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-emerald-500 focus:outline-none">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                    Apply Filter
                </button>
                @if($warehouseId || $startDate || $endDate)
                    <a href="{{ route('admin.inventory.items.bincard', $item) }}" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Stock Ledger / Bin Card Movements Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80 shadow-sm">
        <div class="border-b border-slate-800 px-5 py-4 flex items-center justify-between">
            <h2 class="font-semibold text-slate-100">Stock Movements Ledger</h2>
            <span class="text-xs text-slate-400">{{ $transactions->total() }} recorded transaction(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Date &amp; Time</th>
                        <th class="px-4 py-3">Warehouse</th>
                        <th class="px-4 py-3">Movement Type</th>
                        <th class="px-4 py-3 text-right">In (+)</th>
                        <th class="px-4 py-3 text-right">Out (&minus;)</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3 text-right">Moving Unit Cost</th>
                        <th class="px-4 py-3">Recorded By</th>
                        <th class="px-4 py-3">Notes / Ref</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($transactions as $txn)
                        @php
                            $isPositive = (float) $txn->quantity_delta > 0;
                            $movementBadge = match($txn->movement_type) {
                                'goods_received', 'receive' => 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60',
                                'purchase_return', 'return' => 'bg-amber-950/60 text-amber-400 border-amber-800/60',
                                'issue', 'stock_issue' => 'bg-blue-950/60 text-blue-400 border-blue-800/60',
                                'adjustment', 'stock_take' => 'bg-purple-950/60 text-purple-400 border-purple-800/60',
                                default => 'bg-slate-800 text-slate-300 border-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-mono text-slate-400 whitespace-nowrap">
                                {{ $txn->occurred_at?->format('d M Y, H:i') ?? $txn->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-200">
                                {{ $txn->warehouse->name }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold capitalize {{ $movementBadge }}">
                                    {{ str_replace('_', ' ', $txn->movement_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium {{ $isPositive ? 'text-emerald-400' : 'text-slate-500' }}">
                                {{ $isPositive ? '+' . number_format((float) $txn->quantity_delta, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium {{ ! $isPositive ? 'text-rose-400' : 'text-slate-500' }}">
                                {{ ! $isPositive ? number_format(abs((float) $txn->quantity_delta), 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-100">
                                {{ number_format((float) $txn->quantity_after, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-slate-300">
                                {{ number_format((float) ($txn->average_unit_cost_after ?? $txn->unit_cost), 2) }}
                            </td>
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap">
                                {{ $txn->recorder->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-3 text-slate-400 max-w-xs truncate" title="{{ $txn->notes }}">
                                {{ $txn->notes ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                No stock movements recorded for this item under the selected filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="border-t border-slate-800 px-4 py-3 no-print">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
