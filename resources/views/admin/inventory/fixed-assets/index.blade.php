@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.inventory.index') }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">&larr; Stores Dashboard</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-50">Fixed Asset Register</h1>
            <p class="mt-1 text-xs text-slate-400">Track capitalized assets, depreciation schedules, custody transfers, and equipment maintenance.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.inventory.fixed-assets.maintenance') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition">
                <svg class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Maintenance & Repairs
            </a>
            <a href="{{ route('admin.inventory.fixed-assets.depreciation') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-emerald-900/30 hover:from-emerald-500 hover:to-teal-500 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Depreciation Schedules
            </a>
        </div>
    </div>

    <!-- Stats Overview Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Total Assets</span>
            <p class="mt-1 text-xl font-bold text-slate-100">{{ number_format($stats['total_assets']) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">All registered units</p>
        </div>
        <div class="rounded-2xl border border-indigo-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-indigo-400">Capitalized Assets</span>
            <p class="mt-1 text-xl font-bold text-indigo-300">{{ number_format($stats['capitalized_count']) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">${{ number_format((float) $stats['total_capitalized_cost'], 2) }} cost</p>
        </div>
        <div class="rounded-2xl border border-amber-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-amber-400">Accumulated Depr.</span>
            <p class="mt-1 text-xl font-bold text-amber-300">${{ number_format((float) $stats['total_accumulated_depreciation'], 2) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">To-date amortization</p>
        </div>
        <div class="rounded-2xl border border-emerald-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-emerald-400">Net Book Value</span>
            @php
                $nbv = max(0, (float) $stats['total_capitalized_cost'] - (float) $stats['total_accumulated_depreciation']);
            @endphp
            <p class="mt-1 text-xl font-bold text-emerald-300">${{ number_format($nbv, 2) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Current balance sheet value</p>
        </div>
        <div class="rounded-2xl border border-rose-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-rose-400">Disposed / Written Off</span>
            <p class="mt-1 text-xl font-bold text-rose-300">{{ number_format($stats['disposed_count']) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Retired items</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
        <form method="GET" action="{{ route('admin.inventory.fixed-assets.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-slate-300">Search Tag / Item / Serial</label>
                <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="e.g. FA-01, Projector, Dell..."
                       class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-medium text-slate-300">Status</label>
                <select id="status" name="status" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100">
                    <option value="">All Statuses</option>
                    <option value="in_store" @selected(request('status') === 'in_store')>In Store</option>
                    <option value="assigned" @selected(request('status') === 'assigned')>Assigned to Staff</option>
                    <option value="disposed" @selected(request('status') === 'disposed')>Disposed (Sold/Donated)</option>
                    <option value="written_off" @selected(request('status') === 'written_off')>Written Off / Scrapped</option>
                </select>
            </div>
            <div>
                <label for="capitalized" class="mb-1 block text-xs font-medium text-slate-300">Classification</label>
                <select id="capitalized" name="capitalized" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100">
                    <option value="">All Classifications</option>
                    <option value="yes" @selected(request('capitalized') === 'yes')>Capitalized Fixed Assets</option>
                    <option value="no" @selected(request('capitalized') === 'no')>Low-Value (Expensed) Assets</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition">Filter</button>
                <a href="{{ route('admin.inventory.fixed-assets.index') }}" class="rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-400 hover:text-slate-200 transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Assets Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80 shadow-xl shadow-slate-950/40">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Asset Tag</th>
                        <th class="px-4 py-3">Item & Category</th>
                        <th class="px-4 py-3">Serial</th>
                        <th class="px-4 py-3">Location / Room</th>
                        <th class="px-4 py-3">Custodian</th>
                        <th class="px-4 py-3 text-right">Cost</th>
                        <th class="px-4 py-3 text-right">Acc. Depr</th>
                        <th class="px-4 py-3 text-right">Net Book Value</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($assets as $asset)
                        <tr class="text-slate-300 hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3.5 font-mono font-semibold">
                                <a href="{{ route('admin.inventory.fixed-assets.show', $asset) }}" class="text-emerald-400 hover:text-emerald-300 hover:underline">
                                    {{ $asset->asset_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-medium text-slate-100">{{ $asset->item->name }}</p>
                                <p class="text-[10px] text-slate-500">{{ $asset->item->category?->name ?? 'General' }}</p>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-slate-400">{{ $asset->serial_number ?: '-' }}</td>
                            <td class="px-4 py-3.5">{{ $asset->location ?: $asset->warehouse?->name ?: 'Unassigned' }}</td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $asset->custodian ? 'bg-cyan-400' : 'bg-slate-600' }}"></span>
                                    {{ $asset->custodian?->name ?? 'Stores' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-medium text-slate-200">
                                ${{ number_format((float) ($asset->is_capitalized ? $asset->capitalized_cost : $asset->purchase_cost), 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-amber-300/90 font-mono">
                                {{ $asset->is_capitalized ? '$'.number_format((float) $asset->accumulated_depreciation, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-emerald-400 font-mono">
                                {{ $asset->is_capitalized ? '$'.number_format((float) $asset->net_book_value, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if(in_array($asset->status, ['disposed', 'written_off']))
                                    <span class="inline-flex rounded-full bg-rose-500/15 px-2 py-0.5 text-[10px] font-semibold text-rose-300 border border-rose-500/30">
                                        {{ ucfirst($asset->status) }}
                                    </span>
                                @elseif($asset->status === 'assigned')
                                    <span class="inline-flex rounded-full bg-cyan-500/15 px-2 py-0.5 text-[10px] font-semibold text-cyan-300 border border-cyan-500/30">
                                        Assigned
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-700/60 px-2 py-0.5 text-[10px] font-semibold text-slate-300 border border-slate-600">
                                        In Store
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('admin.inventory.fixed-assets.show', $asset) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-800/80 px-2.5 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-700 hover:text-white transition">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-slate-500">
                                <p class="text-sm font-medium">No assets matching the current filters.</p>
                                <p class="text-xs text-slate-600 mt-1">Capitalized and tracked items appear here automatically upon receiving purchase orders.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($assets->hasPages())
            <div class="border-t border-slate-800 px-4 py-3 bg-slate-950/40">{{ $assets->links() }}</div>
        @endif
    </div>
</div>
@endsection
