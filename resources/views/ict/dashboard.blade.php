@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-cyan-500/20 text-cyan-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </span>
                <h1 class="text-xl font-bold tracking-tight text-slate-50">ICT Coordination & Helpdesk Desk</h1>
                <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                    Lead: {{ $user->name }}
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">School ICT infrastructure readiness, teacher helpdesk requests & School ERP Implementation Project.</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            @if($erpProject)
                <a href="{{ route('admin.projects.show', [$erpProject, 'tab' => 'tasks']) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-500/30 bg-indigo-500/10 hover:bg-indigo-500/20 px-3.5 py-2 text-xs font-semibold text-indigo-300 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Manage ERP Tasks
                </a>
            @endif
            <a href="{{ route('ict.devices.index') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 px-3.5 py-2 text-xs font-semibold text-emerald-300 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                Lab & Devices
            </a>
            <a href="{{ route('ict.tickets.index') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-cyan-600/20 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                Tickets Queue ({{ $openTicketsCount }})
            </a>
        </div>
    </div>

    <!-- 4 High-Impact KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Support Tickets -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Open Tickets</span>
                @if($urgentTicketsCount > 0)
                    <span class="inline-flex px-1.5 py-0.5 text-[10px] font-bold rounded bg-rose-500/20 text-rose-300 border border-rose-500/30">
                        {{ $urgentTicketsCount }} Urgent
                    </span>
                @endif
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-2xl font-bold text-slate-50">{{ $openTicketsCount }}</span>
                <span class="text-xs text-slate-400">open / {{ $inProgressTicketsCount }} in progress</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-800/80 pt-2">
                <span>Resolved: <strong class="text-emerald-400">{{ $resolvedTicketsCount }}</strong></span>
                <a href="{{ route('ict.tickets.index') }}" class="text-cyan-400 hover:underline">View queue &rarr;</a>
            </div>
        </div>

        <!-- Card 2: Lab Readiness Rate -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Computer Lab Readiness</span>
                <span class="inline-flex px-1.5 py-0.5 text-[10px] font-bold rounded {{ $readinessRate >= 80 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                    {{ $readinessRate }}%
                </span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-2xl font-bold text-slate-50">{{ $workingDevices }} / {{ $totalDevices }}</span>
                <span class="text-xs text-slate-400">devices ready</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-800/80 pt-2">
                <span>Faulty: <strong class="{{ $faultyDevices > 0 ? 'text-rose-400' : 'text-slate-400' }}">{{ $faultyDevices }}</strong></span>
                <a href="{{ route('ict.devices.index') }}" class="text-emerald-400 hover:underline">Manage lab &rarr;</a>
            </div>
        </div>

        <!-- Card 3: ERP Rollout Progress -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">ERP Implementation</span>
                <span class="inline-flex px-1.5 py-0.5 text-[10px] font-bold rounded bg-indigo-500/20 text-indigo-300">
                    {{ $erpProgressPercentage }}%
                </span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-2xl font-bold text-slate-50">{{ $erpCompletedCount }} / {{ $erpTasksCount }}</span>
                <span class="text-xs text-slate-400">tasks done</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-800/80 pt-2">
                <span>Code: <strong class="text-slate-300 font-mono">{{ $erpProject?->code ?? 'PRJ-ERP-2026' }}</strong></span>
                @if($erpProject)
                    <a href="{{ route('admin.projects.show', [$erpProject, 'tab' => 'tasks']) }}" class="text-indigo-400 hover:underline">Roadmap &rarr;</a>
                @endif
            </div>
        </div>

        <!-- Card 4: Quick Helpdesk Link for Staff -->
        <div class="rounded-2xl border border-cyan-500/20 bg-gradient-to-br from-cyan-950/30 to-slate-900/80 p-4 flex flex-col justify-between">
            <div>
                <span class="text-xs font-medium text-cyan-300">Staff Support Action</span>
                <p class="text-xs text-slate-400 mt-1">Have a login issue, printer fault, or ERP allocation question?</p>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-800/80 flex items-center gap-2">
                <a href="{{ route('ict.tickets.create') }}" class="w-full text-center py-1.5 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold transition">
                    + Log Support Ticket
                </a>
            </div>
        </div>
    </div>

    <!-- Main Grid: ERP Implementation Tracker & Recent Tickets -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- ERP Implementation Highlights (Left Col 7) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                        <h2 class="text-sm font-bold text-slate-100">Live School ERP Rollout Roadmap</h2>
                    </div>
                    @if($erpProject)
                        <a href="{{ route('admin.projects.show', [$erpProject, 'tab' => 'tasks']) }}" class="text-xs text-indigo-400 hover:underline">
                            Full Roadmap &rarr;
                        </a>
                    @endif
                </div>

                <!-- Progress Bar -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex justify-between text-xs text-slate-400">
                        <span>Implementation Milestones Completion</span>
                        <span class="font-bold text-cyan-400">{{ $erpProgressPercentage }}%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-500 via-cyan-400 to-emerald-400 h-2 rounded-full" style="width: {{ $erpProgressPercentage }}%"></div>
                    </div>
                </div>

                <!-- Recent Implementation Tasks -->
                <div class="mt-4 space-y-2">
                    @forelse($recentErpTasks as $task)
                        <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-800/60 bg-slate-950/40">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="h-2 w-2 rounded-full {{ $task->status === 'completed' ? 'bg-emerald-400' : 'bg-amber-400' }} shrink-0"></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-slate-200 truncate {{ $task->status === 'completed' ? 'line-through text-slate-400' : '' }}">
                                        {{ $task->title }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">{{ $task->phase_milestone }} &bull; Assigned: {{ $task->assignee?->name ?? 'Unassigned' }}</p>
                                </div>
                            </div>
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $task->status_badge_class }} shrink-0">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No tasks found. Open the project to add rollout tasks.</p>
                    @endforelse
                </div>
            </div>

            <!-- Lab Readiness Quick Card -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        <h2 class="text-sm font-bold text-slate-100">Lab & Workstation Inspection Status</h2>
                    </div>
                    <a href="{{ route('ict.devices.index') }}" class="text-xs text-emerald-400 hover:underline">
                        View All Devices ({{ $totalDevices }}) &rarr;
                    </a>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @forelse($devices as $device)
                        <div class="p-3 rounded-xl border border-slate-800/60 bg-slate-950/40 flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-200 truncate">{{ $device->device_name }}</p>
                                <p class="text-[10px] text-slate-500">{{ $device->location }} @if($device->device_code)&bull; {{ $device->device_code }}@endif</p>
                            </div>
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $device->condition_badge_class }} shrink-0">
                                {{ ucfirst(str_replace('_', ' ', $device->condition_status)) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 col-span-2 text-center py-3">No devices logged yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Support Tickets Queue (Right Col 5) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                        <h2 class="text-sm font-bold text-slate-100">Recent Support Queue</h2>
                    </div>
                    <a href="{{ route('ict.tickets.index') }}" class="text-xs text-cyan-400 hover:underline">
                        Full Queue &rarr;
                    </a>
                </div>

                <div class="mt-3 space-y-2.5">
                    @forelse($recentTickets as $ticket)
                        <div class="p-3.5 rounded-xl border border-slate-800/80 bg-slate-950/50 hover:bg-slate-800/30 transition space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-[10px] text-slate-400">{{ $ticket->ticket_number }}</span>
                                        <span class="inline-flex px-1.5 py-0.2 text-[9px] font-bold rounded-full {{ $ticket->priority_badge_class }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                    </div>
                                    <a href="{{ route('ict.tickets.show', $ticket) }}" class="text-xs font-semibold text-slate-100 hover:text-cyan-400 transition block mt-0.5 truncate">
                                        {{ $ticket->title }}
                                    </a>
                                </div>
                                <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $ticket->status_badge_class }} shrink-0">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-800/40">
                                <span>From: <strong class="text-slate-300 font-medium">{{ $ticket->requester?->name ?? 'Staff' }}</strong></span>
                                <span>{{ $ticket->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <p class="text-xs text-slate-500">No support requests in queue. Everything is running smoothly!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
