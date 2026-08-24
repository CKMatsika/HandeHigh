@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.timetables.index') }}" class="text-slate-400 hover:text-slate-200 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Master Timetable Operations</h1>
                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-400 border border-emerald-500/20">Live Operational Mode</span>
                @if($timetable)
                    <span class="rounded-full bg-indigo-500/10 px-2.5 py-0.5 text-xs font-medium text-indigo-300 border border-indigo-500/20">Rev: v{{ $timetable->revision ?? 1 }}</span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-1 pl-8">Real-time daily operations, live substitutions, room reassignments, and auditable schedule adjustments.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.timetables.absences.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3.5 py-2 text-xs font-medium text-amber-300 hover:bg-amber-500/20 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Teacher Absences ({{ $stats['absent_teachers'] }})</span>
            </a>

            <a href="{{ route('admin.timetables.substitutions.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-purple-500/30 bg-purple-500/10 px-3.5 py-2 text-xs font-medium text-purple-300 hover:bg-purple-500/20 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                <span>Substitutions ({{ $stats['pending_substitutions'] }} Pending)</span>
            </a>

            @if($timetable)
                <a href="{{ route('admin.timetables.changes', $timetable) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700 transition">
                    <svg class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Change History</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Total Lessons Today</span>
                <span class="rounded-lg bg-indigo-500/10 p-2 text-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-50">{{ $stats['total_slots'] }}</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Absent Staff</span>
                <span class="rounded-lg bg-rose-500/10 p-2 text-rose-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-rose-400">{{ $stats['absent_teachers'] }}</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Pending Subs</span>
                <span class="rounded-lg bg-amber-500/10 p-2 text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-amber-400">{{ $stats['pending_substitutions'] }}</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Approved Covers</span>
                <span class="rounded-lg bg-emerald-500/10 p-2 text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-emerald-400">{{ $stats['approved_substitutions'] }}</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-sm backdrop-blur">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Cancelled Today</span>
                <span class="rounded-lg bg-slate-800 p-2 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-400">{{ $stats['cancelled_slots'] }}</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4 shadow-sm">
        <form action="{{ route('admin.timetables.operations') }}" method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none" onchange="this.form.submit()">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Day of Week</label>
                <select name="day" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none" onchange="this.form.submit()">
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $d)
                        <option value="{{ $d }}" {{ $day === $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Class</label>
                <select name="school_class_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Teacher</label>
                <select name="teacher_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none" onchange="this.form.submit()">
                    <option value="">All Teachers</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Room</label>
                <select name="room_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none" onchange="this.form.submit()">
                    <option value="">All Rooms</option>
                    @foreach($rooms as $r)
                        <option value="{{ $r->id }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 transition">Filter</button>
                <a href="{{ route('admin.timetables.operations') }}" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Slots Operational Table -->
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-800/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Time & Period</th>
                        <th class="px-4 py-3">Class</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Teacher / Substitute</th>
                        <th class="px-4 py-3">Room</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Operational Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($slots as $slot)
                        @php
                            $activeSub = $slot->substitutions->where('status', 'approved')->first();
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition {{ $slot->status === 'cancelled' ? 'opacity-60 bg-slate-950/30' : '' }}">
                            <td class="px-4 py-3 font-medium text-slate-200">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-[11px] text-slate-300">{{ $slot->getFormattedTime() }}</span>
                                    @if($slot->schoolPeriod)
                                        <span class="text-[11px] text-slate-400">{{ $slot->schoolPeriod->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-100">
                                {{ $slot->schoolClass?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-200">
                                <div class="font-medium">{{ $slot->subject?->name ?? $slot->activity_name ?? '—' }}</div>
                                @if($slot->is_locked)
                                    <span class="inline-flex items-center gap-1 text-[10px] text-amber-400"><svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg> Locked</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($activeSub)
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-purple-400 flex items-center gap-1">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                            {{ $activeSub->substituteTeacher?->full_name }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 line-through">Orig: {{ $slot->teacher?->full_name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-200 font-medium">{{ $slot->teacher?->full_name ?? 'Unassigned' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ $slot->room?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($slot->status === 'cancelled')
                                    <span class="rounded-full bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-rose-400 border border-rose-500/20">Cancelled</span>
                                @elseif($activeSub)
                                    <span class="rounded-full bg-purple-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-purple-400 border border-purple-500/20">Substituted</span>
                                @else
                                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">Scheduled</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Recommend Substitute -->
                                    <a href="{{ route('admin.timetables.substitutions.recommend', ['slot' => $slot->id, 'date' => $date]) }}" class="rounded bg-purple-600/20 border border-purple-500/30 px-2 py-1 text-[11px] font-medium text-purple-300 hover:bg-purple-600/30 transition" title="Recommend Substitute">
                                        Substitute
                                    </a>

                                    @if($slot->status === 'cancelled')
                                        <!-- Restore Button -->
                                        <form action="{{ route('admin.timetables.slots.restore', ['timetable' => $timetable->id, 'slot' => $slot->id]) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded bg-emerald-600/20 border border-emerald-500/30 px-2 py-1 text-[11px] font-medium text-emerald-300 hover:bg-emerald-600/30 transition">Restore</button>
                                        </form>
                                    @else
                                        <!-- Cancel Button -->
                                        <form action="{{ route('admin.timetables.slots.cancel', ['timetable' => $timetable->id, 'slot' => $slot->id]) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this lesson slot?');">
                                            @csrf
                                            <input type="hidden" name="reason" value="Operational administrative cancellation">
                                            <button type="submit" class="rounded bg-rose-600/20 border border-rose-500/30 px-2 py-1 text-[11px] font-medium text-rose-300 hover:bg-rose-600/30 transition">Cancel</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No lesson slots found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
