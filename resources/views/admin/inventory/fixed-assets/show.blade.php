@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 pb-12">
    <!-- Top Navigation & Breadcrumbs -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inventory.fixed-assets.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Fixed Asset Register
            </a>
            <span class="text-slate-600">/</span>
            <span class="text-xs font-semibold text-emerald-400">{{ $fixedAsset->asset_number }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.fixed-assets.depreciation') }}" class="rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-700 hover:text-white transition">
                Depreciation Schedules
            </a>
            <a href="{{ route('admin.inventory.fixed-assets.maintenance') }}" class="rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-700 hover:text-white transition">
                Maintenance Tracker
            </a>
        </div>
    </div>

    <!-- Asset Header & Quick Actions -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-xl backdrop-blur-xl">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-3xl font-extrabold tracking-tight text-white">{{ $fixedAsset->asset_number }}</h1>
                    
                    @if($fixedAsset->status === 'disposed')
                        <span class="rounded-full bg-rose-500/20 px-3 py-0.5 text-xs font-semibold text-rose-300 border border-rose-500/30">
                            Retired / Disposed ({{ ucfirst(str_replace('_', ' ', $fixedAsset->disposal_type)) }})
                        </span>
                    @elseif($fixedAsset->status === 'under_maintenance')
                        <span class="rounded-full bg-amber-500/20 px-3 py-0.5 text-xs font-semibold text-amber-300 border border-amber-500/30">
                            Under Maintenance
                        </span>
                    @elseif($fixedAsset->status === 'assigned')
                        <span class="rounded-full bg-emerald-500/20 px-3 py-0.5 text-xs font-semibold text-emerald-300 border border-emerald-500/30">
                            Assigned to Staff
                        </span>
                    @else
                        <span class="rounded-full bg-cyan-500/20 px-3 py-0.5 text-xs font-semibold text-cyan-300 border border-cyan-500/30">
                            In Store / Available
                        </span>
                    @endif

                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs font-medium text-slate-300 border border-slate-700">
                        {{ $fixedAsset->is_capitalized ? 'Capitalized Asset' : 'Below Threshold' }}
                    </span>
                    
                    @if($fixedAsset->depreciation_method && $fixedAsset->depreciation_method !== 'none')
                        <span class="rounded-full bg-violet-500/20 px-2.5 py-0.5 text-xs font-medium text-violet-300 border border-violet-500/30">
                            {{ ucfirst(str_replace('_', ' ', $fixedAsset->depreciation_method)) }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-base font-medium text-slate-300">
                    {{ $fixedAsset->item->name }} 
                    <span class="text-sm font-normal text-slate-400">· {{ $fixedAsset->item->category->name }} (Code: {{ $fixedAsset->item->code }})</span>
                </p>
            </div>

            <!-- Action Buttons -->
            @can('inventory.assets.manage')
                @if($fixedAsset->status !== 'disposed')
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="openModal('transferModal')" class="inline-flex items-center gap-1.5 rounded-xl bg-cyan-600 px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-cyan-600/30 hover:bg-cyan-500 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Transfer Asset
                        </button>

                        <button type="button" onclick="openModal('scheduleMaintenanceModal')" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-amber-600/30 hover:bg-amber-500 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Log / Schedule Service
                        </button>

                        <button type="button" onclick="openModal('depreciationModal')" class="inline-flex items-center gap-1.5 rounded-xl border border-violet-500/50 bg-violet-600/20 px-3.5 py-2 text-xs font-semibold text-violet-200 hover:bg-violet-600/30 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Depreciation Settings
                        </button>

                        <button type="button" onclick="openModal('disposeModal')" class="inline-flex items-center gap-1.5 rounded-xl border border-rose-500/40 bg-rose-950/30 px-3.5 py-2 text-xs font-semibold text-rose-300 hover:bg-rose-900/50 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Dispose Asset
                        </button>
                    </div>
                @endif
            @endcan
        </div>

        <!-- Disposal Banner (if retired) -->
        @if($fixedAsset->status === 'disposed')
            <div class="mt-5 rounded-xl border border-rose-800/80 bg-rose-950/40 p-4">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-rose-500/20 p-2 text-rose-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="flex-1 space-y-1">
                        <h3 class="text-sm font-semibold text-rose-200">Asset Retired / Disposed</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs text-rose-300/90 pt-1">
                            <div><span class="text-slate-400">Date:</span> {{ $fixedAsset->disposal_date ? $fixedAsset->disposal_date->format('d M Y') : 'N/A' }}</div>
                            <div><span class="text-slate-400">Method:</span> {{ ucfirst(str_replace('_', ' ', $fixedAsset->disposal_type)) }}</div>
                            <div><span class="text-slate-400">Proceeds:</span> ${{ number_format((float) $fixedAsset->disposal_proceeds, 2) }}</div>
                            <div><span class="text-slate-400">Disposed by:</span> {{ $fixedAsset->disposedByUser?->name ?? 'System' }}</div>
                        </div>
                        @if($fixedAsset->disposalJournalBatch)
                            <p class="text-xs text-rose-300 pt-1">
                                <span class="text-slate-400">GL Journal Voucher:</span> 
                                <span class="font-mono font-semibold">{{ $fixedAsset->disposalJournalBatch->batch_number }}</span>
                                ({{ $fixedAsset->disposalJournalBatch->description }})
                            </p>
                        @endif
                        @if($fixedAsset->disposal_notes)
                            <p class="text-xs text-slate-300 italic pt-1">"{{ $fixedAsset->disposal_notes }}"</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Financial & Depreciation Overview Metric Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Capitalized Cost -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Capitalized Cost</p>
            <p class="mt-2 text-2xl font-bold text-white">${{ number_format((float) $fixedAsset->capitalized_cost, 2) }}</p>
            <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                <span>Purchase: ${{ number_format((float) $fixedAsset->purchase_cost, 2) }}</span>
                <span>Acquired: {{ $fixedAsset->acquired_date->format('d M Y') }}</span>
            </div>
        </div>

        <!-- Card 2: Accumulated Depreciation -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Accumulated Depr.</p>
            <p class="mt-2 text-2xl font-bold text-amber-400">${{ number_format((float) ($fixedAsset->accumulated_depreciation ?? 0), 2) }}</p>
            @php
                $deprPct = (float) $fixedAsset->capitalized_cost > 0 
                    ? min(100, round(((float) ($fixedAsset->accumulated_depreciation ?? 0) / (float) $fixedAsset->capitalized_cost) * 100, 1))
                    : 0;
            @endphp
            <div class="mt-2">
                <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                    <span>Amortized</span>
                    <span class="font-semibold text-amber-300">{{ $deprPct }}%</span>
                </div>
                <div class="h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-300" style="width: {{ $deprPct }}%"></div>
                </div>
            </div>
        </div>

        <!-- Card 3: Carrying Book Value -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Net Book Value</p>
            <p class="mt-2 text-2xl font-bold text-emerald-400">${{ number_format((float) ($fixedAsset->book_value ?? $fixedAsset->net_book_value), 2) }}</p>
            <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                <span>Salvage Target:</span>
                <span class="font-medium text-slate-200">${{ number_format((float) ($fixedAsset->salvage_value ?? 0), 2) }}</span>
            </div>
        </div>

        <!-- Card 4: Depreciation Parameters -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Schedule Parameters</p>
            <div class="mt-2 text-sm font-semibold text-slate-200">
                {{ ucfirst(str_replace('_', ' ', $fixedAsset->depreciation_method ?: 'None')) }}
            </div>
            <div class="mt-2 space-y-1 text-xs text-slate-400">
                <div class="flex justify-between">
                    <span>Useful Life:</span>
                    <span class="text-slate-200">{{ $fixedAsset->useful_life_years ? $fixedAsset->useful_life_years.' yrs' : 'Not set' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Last Run:</span>
                    <span class="text-slate-200">{{ $fixedAsset->last_depreciation_date ? $fixedAsset->last_depreciation_date->format('d M Y') : 'Never' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Specifications, Custody & Location Details -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Asset Details Left (2 cols) -->
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h2 class="font-semibold text-slate-100 flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Custody, Location & Procurement
                    </h2>
                    @can('inventory.assets.manage')
                        @if($fixedAsset->status !== 'disposed')
                            <button type="button" onclick="openModal('transferModal')" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">
                                Transfer / Reassign &rarr;
                            </button>
                        @endif
                    @endcan
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <p class="text-xs text-slate-400">Current Custodian</p>
                        <p class="mt-1 font-medium text-slate-100">{{ $fixedAsset->custodian?->name ?? 'In Store / Unassigned' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Location / Room</p>
                        <p class="mt-1 font-medium text-slate-100">{{ $fixedAsset->location ?: 'Not specified' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Assigned Warehouse</p>
                        <p class="mt-1 font-medium text-slate-100">{{ $fixedAsset->warehouse?->name ?? 'No Warehouse' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Serial Number</p>
                        <p class="mt-1 font-mono text-slate-100">{{ $fixedAsset->serial_number ?: 'Not recorded' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Goods Received Voucher</p>
                        <p class="mt-1 font-medium text-slate-100">{{ $fixedAsset->goodsReceivedItem->voucher->voucher_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Supplier Invoice</p>
                        <p class="mt-1 font-medium text-slate-100">
                            @if($fixedAsset->supplierInvoiceItem?->supplierInvoice)
                                {{ $fixedAsset->supplierInvoiceItem->supplierInvoice->invoice_number }}
                                <span class="text-xs text-slate-400">({{ $fixedAsset->supplierInvoiceItem->supplierInvoice->vendor?->name ?? 'Supplier' }})</span>
                            @else
                                <span class="text-slate-500">Not invoiced yet</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($fixedAsset->notes)
                    <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <p class="text-xs text-slate-400">Asset Notes:</p>
                        <p class="mt-1 text-sm text-slate-300">{{ $fixedAsset->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Maintenance & Service Records -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h2 class="font-semibold text-slate-100 flex items-center gap-2">
                            <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Maintenance & Service History
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $fixedAsset->maintenances->count() }} maintenance record(s) logged</p>
                    </div>
                    @can('inventory.assets.manage')
                        @if($fixedAsset->status !== 'disposed')
                            <button type="button" onclick="openModal('scheduleMaintenanceModal')" class="rounded-lg bg-amber-600/20 border border-amber-500/40 px-3 py-1.5 text-xs font-semibold text-amber-300 hover:bg-amber-600/30 transition">
                                + Log Service
                            </button>
                        @endif
                    @endcan
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-800 text-xs uppercase text-slate-400">
                            <tr>
                                <th class="py-2.5 pr-3">Scheduled / Done</th>
                                <th class="py-2.5 pr-3">Type & Title</th>
                                <th class="py-2.5 pr-3">Service Provider</th>
                                <th class="py-2.5 pr-3">Status</th>
                                <th class="py-2.5 pr-3 text-right">Cost</th>
                                <th class="py-2.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($fixedAsset->maintenances as $maintenance)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3 pr-3 text-slate-300 text-xs">
                                        <div class="font-medium text-slate-200">{{ $maintenance->scheduled_date->format('d M Y') }}</div>
                                        @if($maintenance->completed_date)
                                            <div class="text-[11px] text-emerald-400">Completed: {{ $maintenance->completed_date->format('d M Y') }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-3 text-slate-200">
                                        <div class="font-semibold text-white">{{ $maintenance->title }}</div>
                                        <div class="text-xs text-slate-400">{{ ucfirst($maintenance->maintenance_type) }}</div>
                                        @if($maintenance->description)
                                            <div class="text-xs text-slate-500 italic mt-0.5 line-clamp-1">{{ $maintenance->description }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-3 text-xs text-slate-300">
                                        {{ $maintenance->service_provider ?: ($maintenance->technician?->name ?? 'Internal Staff') }}
                                    </td>
                                    <td class="py-3 pr-3 text-xs">
                                        @if($maintenance->status === 'completed')
                                            <span class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 font-medium text-emerald-300 border border-emerald-500/30">Completed</span>
                                        @elseif($maintenance->status === 'in_progress')
                                            <span class="rounded-full bg-amber-500/20 px-2.5 py-0.5 font-medium text-amber-300 border border-amber-500/30">In Progress</span>
                                        @elseif($maintenance->status === 'cancelled')
                                            <span class="rounded-full bg-slate-700 px-2.5 py-0.5 font-medium text-slate-300">Cancelled</span>
                                        @else
                                            <span class="rounded-full bg-blue-500/20 px-2.5 py-0.5 font-medium text-blue-300 border border-blue-500/30">Scheduled</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-3 text-right text-xs font-mono text-slate-200">
                                        @if($maintenance->actual_cost !== null)
                                            <span class="font-semibold text-emerald-300">${{ number_format((float) $maintenance->actual_cost, 2) }}</span>
                                        @elseif($maintenance->estimated_cost !== null)
                                            <span class="text-slate-400">Est: ${{ number_format((float) $maintenance->estimated_cost, 2) }}</span>
                                        @else
                                            <span class="text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right text-xs">
                                        @can('inventory.assets.manage')
                                            @if($maintenance->status !== 'completed' && $maintenance->status !== 'cancelled' && $fixedAsset->status !== 'disposed')
                                                <button type="button" onclick="prepareCompleteMaintenance({{ $maintenance->id }}, '{{ addslashes($maintenance->title) }}', {{ $maintenance->estimated_cost ?? 0 }})" class="rounded-lg bg-emerald-600 px-2.5 py-1 font-semibold text-white hover:bg-emerald-500 transition">
                                                    Mark Completed
                                                </button>
                                            @elseif($maintenance->journalBatch)
                                                <span class="font-mono text-[11px] text-slate-400" title="{{ $maintenance->journalBatch->description }}">
                                                    JV: {{ $maintenance->journalBatch->batch_number }}
                                                </span>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-slate-500">
                                        No maintenance or service history recorded for this asset yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Depreciation Amortization Run History -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h2 class="font-semibold text-slate-100 flex items-center gap-2">
                            <svg class="h-4 w-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Depreciation Journal History
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Amortisation entries posted to the general ledger</p>
                    </div>
                    <span class="text-xs font-medium text-slate-400">Total: ${{ number_format((float) ($fixedAsset->accumulated_depreciation ?? 0), 2) }}</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-800 text-xs uppercase text-slate-400">
                            <tr>
                                <th class="py-2.5 pr-3">Depr. Date</th>
                                <th class="py-2.5 pr-3">Period</th>
                                <th class="py-2.5 pr-3">Method</th>
                                <th class="py-2.5 pr-3 text-right">Depreciation</th>
                                <th class="py-2.5 pr-3 text-right">Ending Book Value</th>
                                <th class="py-2.5 text-right">Journal Batch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($fixedAsset->depreciations as $depr)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3 pr-3 text-xs text-slate-200 font-medium">{{ $depr->depreciation_date->format('d M Y') }}</td>
                                    <td class="py-3 pr-3 text-xs text-slate-400">{{ $depr->period_start->format('d M') }} - {{ $depr->period_end->format('d M Y') }}</td>
                                    <td class="py-3 pr-3 text-xs text-slate-300">{{ ucfirst(str_replace('_', ' ', $depr->method)) }}</td>
                                    <td class="py-3 pr-3 text-right text-xs font-mono font-bold text-amber-400">${{ number_format((float) $depr->depreciation_amount, 2) }}</td>
                                    <td class="py-3 pr-3 text-right text-xs font-mono text-emerald-400">${{ number_format((float) $depr->book_value_after, 2) }}</td>
                                    <td class="py-3 text-right text-xs font-mono text-slate-300">
                                        {{ $depr->journalBatch?->batch_number ?? 'Auto-run' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-slate-500">
                                        No depreciation batches run on this asset yet. Monthly depreciation can be triggered under Depreciation Schedules.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Custody & Transfer History -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
                <h2 class="font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                    <svg class="h-4 w-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Custody history & location audit trail
                </h2>
                <div class="mt-4 space-y-3">
                    @forelse($fixedAsset->assignments as $assignment)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-200">
                                    {{ ucfirst(str_replace('_', ' ', $assignment->action)) }}
                                    <span class="text-xs font-normal text-slate-400">by {{ $assignment->assignedBy->name }}</span>
                                </p>
                                <span class="text-xs text-slate-400">{{ $assignment->occurred_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-300">
                                <div>
                                    <span class="text-slate-500">Custodian:</span> 
                                    {{ $assignment->fromUser?->name ?? 'Stores' }} &rarr; <span class="font-medium text-emerald-400">{{ $assignment->toUser?->name ?? 'Stores' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Location:</span> 
                                    {{ $assignment->from_location ?: 'Store' }} &rarr; <span class="font-medium text-cyan-400">{{ $assignment->to_location ?: 'Store' }}</span>
                                </div>
                            </div>
                            @if($assignment->notes)
                                <p class="mt-2 text-xs italic text-slate-400 border-t border-slate-800/80 pt-2">{{ $assignment->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">No custody transfers recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column: Accounting Setup & Quick Details -->
        <div class="space-y-6">
            <!-- Chart of Accounts Linking -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
                <h3 class="font-semibold text-slate-100 border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg class="h-4 w-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Chart of Accounts Mapping
                </h3>
                <div class="mt-4 space-y-4 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Fixed Asset Balance Sheet Account</span>
                        <div class="font-medium text-slate-200 bg-slate-950/70 p-2.5 rounded-lg border border-slate-800">
                            {{ $fixedAsset->assetAccount ? $fixedAsset->assetAccount->code.' · '.$fixedAsset->assetAccount->name : 'Not Capitalized (Expensed at purchase)' }}
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Accumulated Depreciation Account (Contra Asset)</span>
                        <div class="font-medium text-slate-200 bg-slate-950/70 p-2.5 rounded-lg border border-slate-800">
                            {{ $fixedAsset->accumulatedDepreciationAccount ? $fixedAsset->accumulatedDepreciationAccount->code.' · '.$fixedAsset->accumulatedDepreciationAccount->name : 'System Default (Account 2302)' }}
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Depreciation Expense Account (P&L)</span>
                        <div class="font-medium text-slate-200 bg-slate-950/70 p-2.5 rounded-lg border border-slate-800">
                            {{ $fixedAsset->depreciationExpenseAccount ? $fixedAsset->depreciationExpenseAccount->code.' · '.$fixedAsset->depreciationExpenseAccount->name : 'System Default (Account 7000)' }}
                        </div>
                    </div>
                </div>

                @can('inventory.assets.manage')
                    @if($fixedAsset->status !== 'disposed')
                        <button type="button" onclick="openModal('depreciationModal')" class="mt-4 w-full rounded-xl border border-slate-700 bg-slate-800 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition">
                            Edit GL Accounts
                        </button>
                    @endif
                @endcan
            </div>

            <!-- Asset Life & Service Overview -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow">
                <h3 class="font-semibold text-slate-100 border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Asset Health & Service Status
                </h3>
                
                @php
                    $nextService = $fixedAsset->maintenances->whereNotNull('next_service_due')->sortByDesc('next_service_due')->first()?->next_service_due;
                    $totalMaintenanceCost = $fixedAsset->maintenances->sum('actual_cost');
                @endphp

                <div class="mt-4 space-y-3 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-800/80">
                        <span class="text-slate-400">Total Service Spend:</span>
                        <span class="font-mono font-semibold text-slate-200">${{ number_format((float) $totalMaintenanceCost, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-800/80">
                        <span class="text-slate-400">Next Service Due:</span>
                        <span class="font-medium {{ $nextService && $nextService->isPast() ? 'text-rose-400' : 'text-slate-200' }}">
                            {{ $nextService ? $nextService->format('d M Y') : 'None scheduled' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-800/80">
                        <span class="text-slate-400">Can Depreciate:</span>
                        <span class="font-medium {{ $fixedAsset->can_depreciate ? 'text-emerald-400' : 'text-slate-400' }}">
                            {{ $fixedAsset->can_depreciate ? 'Yes (Active & Capitalized)' : 'No' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 1: TRANSFER ASSET                                        -->
<!-- ============================================================== -->
<div id="transferModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-semibold text-white">Transfer Asset: {{ $fixedAsset->asset_number }}</h3>
            <button type="button" onclick="closeModal('transferModal')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.inventory.fixed-assets.transfer', $fixedAsset) }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="transfer_to_user_id" class="block text-xs font-medium text-slate-300">New Custodian (Staff)</label>
                <select id="transfer_to_user_id" name="to_user_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">Store / No Custodian</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('to_user_id', $fixedAsset->custodian_user_id) == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="transfer_to_warehouse_id" class="block text-xs font-medium text-slate-300">Destination Warehouse (Optional)</label>
                <select id="transfer_to_warehouse_id" name="to_warehouse_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">No warehouse assignment</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected(old('to_warehouse_id', $fixedAsset->warehouse_id) == $wh->id)>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="transfer_to_location" class="block text-xs font-medium text-slate-300">Room / Destination Location <span class="text-rose-400">*</span></label>
                <input id="transfer_to_location" name="to_location" required maxlength="255" value="{{ old('to_location', $fixedAsset->location) }}" placeholder="e.g. Science Lab 2, Room B-104" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            </div>

            <div>
                <label for="transfer_notes" class="block text-xs font-medium text-slate-300">Reason / Transfer Notes</label>
                <textarea id="transfer_notes" name="notes" rows="2" maxlength="1000" placeholder="e.g. Relocated for Term 1 practical examinations" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100"></textarea>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="closeModal('transferModal')" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800">Cancel</button>
                <button type="submit" class="rounded-lg bg-cyan-600 px-4 py-2 text-xs font-semibold text-white hover:bg-cyan-500 shadow-lg shadow-cyan-600/30">Confirm Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: DEPRECIATION SETTINGS                                 -->
<!-- ============================================================== -->
<div id="depreciationModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-semibold text-white">Depreciation Parameters & Accounts</h3>
            <button type="button" onclick="closeModal('depreciationModal')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.inventory.fixed-assets.depreciation.update', $fixedAsset) }}" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="depr_method" class="block text-xs font-medium text-slate-300">Method <span class="text-rose-400">*</span></label>
                    <select id="depr_method" name="depreciation_method" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="none" @selected(old('depreciation_method', $fixedAsset->depreciation_method) === 'none')>None (No Depreciation)</option>
                        <option value="straight_line" @selected(old('depreciation_method', $fixedAsset->depreciation_method) === 'straight_line')>Straight Line</option>
                        <option value="reducing_balance" @selected(old('depreciation_method', $fixedAsset->depreciation_method) === 'reducing_balance')>Reducing Balance</option>
                    </select>
                </div>
                <div>
                    <label for="depr_useful_life" class="block text-xs font-medium text-slate-300">Useful Life (Years)</label>
                    <input id="depr_useful_life" type="number" step="0.5" min="0.1" max="100" name="useful_life_years" value="{{ old('useful_life_years', $fixedAsset->useful_life_years) }}" placeholder="e.g. 5" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                </div>
            </div>

            <div>
                <label for="depr_salvage" class="block text-xs font-medium text-slate-300">Salvage / Scrap Value ($)</label>
                <input id="depr_salvage" type="number" step="0.01" min="0" name="salvage_value" value="{{ old('salvage_value', $fixedAsset->salvage_value) }}" placeholder="0.00" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            </div>

            <div>
                <label for="depr_acc_account" class="block text-xs font-medium text-slate-300">Accumulated Depreciation Account</label>
                <select id="depr_acc_account" name="accumulated_depreciation_account_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">Auto-resolve from Category / Account 2302</option>
                    @foreach($assetAccounts as $acc)
                        <option value="{{ $acc->id }}" @selected(old('accumulated_depreciation_account_id', $fixedAsset->accumulated_depreciation_account_id) == $acc->id)>
                            {{ $acc->code }} - {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="depr_exp_account" class="block text-xs font-medium text-slate-300">Depreciation Expense Account</label>
                <select id="depr_exp_account" name="depreciation_expense_account_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">Auto-resolve from Account 7000 (Depreciation Expense)</option>
                    @foreach($expenseAccounts as $acc)
                        <option value="{{ $acc->id }}" @selected(old('depreciation_expense_account_id', $fixedAsset->depreciation_expense_account_id) == $acc->id)>
                            {{ $acc->code }} - {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="closeModal('depreciationModal')" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800">Cancel</button>
                <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-xs font-semibold text-white hover:bg-violet-500 shadow-lg shadow-violet-600/30">Save Parameters</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 3: SCHEDULE MAINTENANCE                                  -->
<!-- ============================================================== -->
<div id="scheduleMaintenanceModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-semibold text-white">Log / Schedule Service</h3>
            <button type="button" onclick="closeModal('scheduleMaintenanceModal')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.inventory.fixed-assets.maintenance.store', $fixedAsset) }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="fixed_asset_id" value="{{ $fixedAsset->id }}">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="maint_type" class="block text-xs font-medium text-slate-300">Service Type <span class="text-rose-400">*</span></label>
                    <select id="maint_type" name="maintenance_type" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="preventive">Preventative Service</option>
                        <option value="repair">Emergency Repair</option>
                        <option value="inspection">Safety Inspection</option>
                        <option value="calibration">Calibration / Tuning</option>
                    </select>
                </div>
                <div>
                    <label for="maint_date" class="block text-xs font-medium text-slate-300">Scheduled Date <span class="text-rose-400">*</span></label>
                    <input id="maint_date" type="date" name="scheduled_date" required value="{{ date('Y-m-d') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                </div>
            </div>

            <div>
                <label for="maint_title" class="block text-xs font-medium text-slate-300">Service Title / Work Order <span class="text-rose-400">*</span></label>
                <input id="maint_title" name="title" required maxlength="255" placeholder="e.g. Annual optical microscope alignment & cleaning" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="maint_provider" class="block text-xs font-medium text-slate-300">External Vendor / Company</label>
                    <input id="maint_provider" name="service_provider" maxlength="255" placeholder="e.g. Precision Lab Services Ltd" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                </div>
                <div>
                    <label for="maint_tech" class="block text-xs font-medium text-slate-300">Internal Technician</label>
                    <select id="maint_tech" name="technician_user_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">None / External Vendor</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="maint_cost" class="block text-xs font-medium text-slate-300">Estimated Cost ($)</label>
                <input id="maint_cost" type="number" step="0.01" min="0" name="estimated_cost" placeholder="0.00" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            </div>

            <div>
                <label for="maint_desc" class="block text-xs font-medium text-slate-300">Detailed Scope / Fault Description</label>
                <textarea id="maint_desc" name="description" rows="2" placeholder="Describe the service procedure or problem reported..." class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100"></textarea>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="closeModal('scheduleMaintenanceModal')" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800">Cancel</button>
                <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-500 shadow-lg shadow-amber-600/30">Schedule Service</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 4: COMPLETE MAINTENANCE                                  -->
<!-- ============================================================== -->
<div id="completeMaintenanceModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-semibold text-white">Complete Maintenance Work</h3>
            <button type="button" onclick="closeModal('completeMaintenanceModal')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="completeMaintenanceForm" method="POST" action="" class="mt-4 space-y-4">
            @csrf
            <div>
                <p id="completeMaintenanceTitle" class="text-sm font-semibold text-amber-300"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="comp_date" class="block text-xs font-medium text-slate-300">Completion Date <span class="text-rose-400">*</span></label>
                    <input id="comp_date" type="date" name="completed_date" required value="{{ date('Y-m-d') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                </div>
                <div>
                    <label for="comp_cost" class="block text-xs font-medium text-slate-300">Actual Cost ($)</label>
                    <input id="comp_cost" type="number" step="0.01" min="0" name="actual_cost" placeholder="0.00" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3 space-y-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="post_to_gl_checkbox" name="post_to_gl" value="1" onchange="toggleGlInputs(this.checked)" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500">
                    <span class="text-xs font-semibold text-slate-200">Post service expense to General Ledger journal</span>
                </label>

                <div id="gl_accounts_section" class="hidden space-y-3 pt-2 border-t border-slate-800">
                    <div>
                        <label for="comp_expense_acc" class="block text-xs text-slate-400">Maintenance Expense Account</label>
                        <select id="comp_expense_acc" name="expense_account_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100">
                            <option value="">Auto-resolve (Account 6400 / 6402)</option>
                            @foreach($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="comp_payment_acc" class="block text-xs text-slate-400">Paid from Bank / Cash Account</label>
                        <select id="comp_payment_acc" name="payment_account_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100">
                            <option value="">Auto-resolve Primary Bank</option>
                            @foreach($bankAndCashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label for="comp_next_due" class="block text-xs font-medium text-slate-300">Next Preventative Service Due (Optional)</label>
                <input id="comp_next_due" type="date" name="next_service_due" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            </div>

            <div>
                <label for="comp_notes" class="block text-xs font-medium text-slate-300">Completion Notes / Spares Replaced</label>
                <textarea id="comp_notes" name="notes" rows="2" placeholder="Parts changed, diagnostic results..." class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100"></textarea>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="closeModal('completeMaintenanceModal')" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800">Cancel</button>
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500 shadow-lg shadow-emerald-600/30">Finish & Record</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 5: DISPOSE / WRITE OFF ASSET                             -->
<!-- ============================================================== -->
<div id="disposeModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-2xl border border-rose-800/80 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-semibold text-rose-300">Retire / Dispose Asset: {{ $fixedAsset->asset_number }}</h3>
            <button type="button" onclick="closeModal('disposeModal')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.inventory.fixed-assets.dispose', $fixedAsset) }}" class="mt-4 space-y-4" onsubmit="return confirm('Are you sure you want to retire or dispose of this asset? This will calculate gain/loss and post balanced journal entries to the general ledger.');">
            @csrf

            <div class="rounded-xl border border-amber-500/30 bg-amber-950/30 p-3 text-xs text-amber-200">
                Current Net Book Value: <strong>${{ number_format((float) ($fixedAsset->book_value ?? $fixedAsset->net_book_value), 2) }}</strong>.
                Disposing this asset writes off its original cost and accumulated depreciation, recognizing any gain or loss on disposal in the General Ledger.
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="disp_date" class="block text-xs font-medium text-slate-300">Disposal Date <span class="text-rose-400">*</span></label>
                    <input id="disp_date" type="date" name="disposal_date" required value="{{ date('Y-m-d') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                </div>
                <div>
                    <label for="disp_type" class="block text-xs font-medium text-slate-300">Disposal Reason / Type <span class="text-rose-400">*</span></label>
                    <select id="disp_type" name="disposal_type" required onchange="toggleProceedsInput(this.value)" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="scrap">Scrapped / Obsolete</option>
                        <option value="sale">Sold (Proceeds Received)</option>
                        <option value="write_off">Written Off (Damaged)</option>
                        <option value="lost">Lost / Stolen</option>
                        <option value="donated">Donated / Transferred</option>
                    </select>
                </div>
            </div>

            <div id="proceeds_section" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="disp_proceeds" class="block text-xs font-medium text-slate-300">Sale Proceeds ($)</label>
                    <input id="disp_proceeds" type="number" step="0.01" min="0" name="disposal_proceeds" placeholder="0.00" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                </div>
                <div>
                    <label for="disp_bank" class="block text-xs font-medium text-slate-300">Deposit Into Account</label>
                    <select id="disp_bank" name="bank_account_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Auto-resolve Primary Bank</option>
                        @foreach($bankAndCashAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="disp_notes" class="block text-xs font-medium text-slate-300">Disposal Authority & Justification</label>
                <textarea id="disp_notes" name="disposal_notes" rows="2" maxlength="2000" placeholder="e.g. Board resolution ref #2026/08, equipment beyond economical repair" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100"></textarea>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="closeModal('disposeModal')" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800">Cancel</button>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-500 shadow-lg shadow-rose-600/30">Confirm Disposal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function toggleGlInputs(checked) {
        const section = document.getElementById('gl_accounts_section');
        if (checked) {
            section.classList.remove('hidden');
        } else {
            section.classList.add('hidden');
        }
    }

    function toggleProceedsInput(type) {
        const section = document.getElementById('proceeds_section');
        if (type === 'sale') {
            section.classList.remove('hidden');
        } else {
            section.classList.add('hidden');
        }
    }

    function prepareCompleteMaintenance(id, title, estCost) {
        const form = document.getElementById('completeMaintenanceForm');
        form.action = `/admin/inventory/fixed-assets/maintenance/${id}/complete`;
        document.getElementById('completeMaintenanceTitle').textContent = `Completing: ${title}`;
        document.getElementById('comp_cost').value = estCost > 0 ? estCost.toFixed(2) : '';
        openModal('completeMaintenanceModal');
    }
</script>
@endsection
