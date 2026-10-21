@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.inventory.fixed-assets.index') }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">&larr; Fixed Asset Register</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-50">Equipment Maintenance & Service Tracking</h1>
            <p class="mt-1 text-xs text-slate-400">Schedule preventive maintenance, track repairs, manage technician services, and record service expenses.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-medium text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Total Records</span>
            <p class="mt-1 text-xl font-bold text-slate-100">{{ number_format($maintenances->total()) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">All logged services</p>
        </div>
        <div class="rounded-2xl border border-amber-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-amber-400">Due Soon (14 Days)</span>
            <p class="mt-1 text-xl font-bold text-amber-300">{{ number_format($dueSoonCount) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Upcoming scheduled services</p>
        </div>
        <div class="rounded-2xl border border-rose-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-rose-400">Overdue Maintenance</span>
            <p class="mt-1 text-xl font-bold text-rose-300">{{ number_format($overdueCount) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Needs immediate attention</p>
        </div>
        <div class="rounded-2xl border border-emerald-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-emerald-400">Total Incurred Cost</span>
            @php
                $totalMaintenanceCost = $maintenances->where('status', 'completed')->sum('cost');
            @endphp
            <p class="mt-1 text-xl font-bold text-emerald-300">${{ number_format((float) $totalMaintenanceCost, 2) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Completed maintenance cost</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
        <form method="GET" action="{{ route('admin.inventory.fixed-assets.maintenance') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div>
                <label for="status" class="mb-1 block text-xs font-medium text-slate-300">Status</label>
                <select id="status" name="status" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100">
                    <option value="">All Statuses</option>
                    <option value="scheduled" @selected(request('status') === 'scheduled')>Scheduled</option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>In Progress</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>
            <div>
                <label for="priority" class="mb-1 block text-xs font-medium text-slate-300">Priority</label>
                <select id="priority" name="priority" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100">
                    <option value="">All Priorities</option>
                    <option value="routine" @selected(request('priority') === 'routine')>Routine</option>
                    <option value="urgent" @selected(request('priority') === 'urgent')>Urgent</option>
                    <option value="critical" @selected(request('priority') === 'critical')>Critical</option>
                </select>
            </div>
            <div>
                <label for="maintenance_type" class="mb-1 block text-xs font-medium text-slate-300">Type</label>
                <select id="maintenance_type" name="maintenance_type" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100">
                    <option value="">All Types</option>
                    <option value="preventive" @selected(request('maintenance_type') === 'preventive')>Preventive</option>
                    <option value="corrective" @selected(request('maintenance_type') === 'corrective')>Corrective / Repair</option>
                    <option value="inspection" @selected(request('maintenance_type') === 'inspection')>Inspection</option>
                    <option value="calibration" @selected(request('maintenance_type') === 'calibration')>Calibration</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition">Filter</button>
                <a href="{{ route('admin.inventory.fixed-assets.maintenance') }}" class="rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-400 hover:text-slate-200 transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Maintenances Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80 shadow-xl shadow-slate-950/40">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Asset</th>
                        <th class="px-4 py-3">Task Title</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Service Provider</th>
                        <th class="px-4 py-3">Scheduled Date</th>
                        <th class="px-4 py-3 text-right">Cost</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($maintenances as $item)
                        <tr class="text-slate-300 hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3.5 font-mono">
                                <a href="{{ route('admin.inventory.fixed-assets.show', $item->asset) }}" class="font-semibold text-emerald-400 hover:text-emerald-300 hover:underline">
                                    {{ $item->asset->asset_number }}
                                </a>
                                <p class="text-[10px] text-slate-500 font-sans">{{ $item->asset->item->name }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-medium text-slate-100">{{ $item->title }}</p>
                                @if($item->description)
                                    <p class="text-[10px] text-slate-400 truncate max-w-xs">{{ $item->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 capitalize text-slate-300">{{ str_replace('_', ' ', $item->maintenance_type) }}</td>
                            <td class="px-4 py-3.5">
                                @if($item->priority === 'critical')
                                    <span class="rounded bg-rose-500/20 px-2 py-0.5 text-[10px] font-semibold text-rose-300 border border-rose-500/30">Critical</span>
                                @elseif($item->priority === 'urgent')
                                    <span class="rounded bg-amber-500/20 px-2 py-0.5 text-[10px] font-semibold text-amber-300 border border-amber-500/30">Urgent</span>
                                @else
                                    <span class="rounded bg-slate-700/60 px-2 py-0.5 text-[10px] font-semibold text-slate-300 border border-slate-600">Routine</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-slate-300">{{ $item->service_provider ?: ($item->technician?->name ?? 'Internal Technician') }}</td>
                            <td class="px-4 py-3.5">
                                <span class="{{ $item->status === 'scheduled' && $item->scheduled_date && $item->scheduled_date->isPast() ? 'text-rose-400 font-semibold' : 'text-slate-300' }}">
                                    {{ $item->scheduled_date ? $item->scheduled_date->format('d M Y') : '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-medium font-mono text-slate-200">
                                ${{ number_format((float) $item->cost, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($item->status === 'completed')
                                    <span class="inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold text-emerald-300 border border-emerald-500/30">Completed</span>
                                @elseif($item->status === 'in_progress')
                                    <span class="inline-flex rounded-full bg-blue-500/15 px-2 py-0.5 text-[10px] font-semibold text-blue-300 border border-blue-500/30">In Progress</span>
                                @elseif($item->status === 'cancelled')
                                    <span class="inline-flex rounded-full bg-slate-700 px-2 py-0.5 text-[10px] font-semibold text-slate-400">Cancelled</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold text-amber-300 border border-amber-500/30">Scheduled</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('admin.inventory.fixed-assets.show', $item->asset) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-800/80 px-2.5 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-700 hover:text-white transition">
                                    Asset details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                <p class="text-sm font-medium">No maintenance records found.</p>
                                <p class="text-xs text-slate-600 mt-1">Maintenance schedules can be logged directly from any asset's detail page.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($maintenances->hasPages())
            <div class="border-t border-slate-800 px-4 py-3 bg-slate-950/40">{{ $maintenances->links() }}</div>
        @endif
    </div>
</div>
@endsection
