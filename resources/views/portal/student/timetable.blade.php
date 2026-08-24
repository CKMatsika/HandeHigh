@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-50">My Class Timetable</h1>
            <p class="text-xs text-slate-400 mt-1">Daily schedule, room locations, subject teachers, and live operational updates.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="rounded-full bg-cyan-500/10 px-3 py-1 text-xs font-semibold text-cyan-400 border border-cyan-500/20">
                Class: {{ $schoolClass?->name ?? 'Unassigned' }}
            </span>
        </div>
    </div>

    <!-- Today's Overview Banner -->
    <div class="rounded-xl border border-cyan-500/30 bg-gradient-to-r from-cyan-950/40 to-slate-900/60 p-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="text-xs uppercase tracking-wider font-semibold text-cyan-400">Today &bull; {{ $todayData['day'] }}, {{ $todayData['date'] }}</span>
                @if($todayData['current_lesson'])
                    <div class="mt-1 flex items-center gap-2">
                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-sm font-bold text-slate-100">Current Class:</span>
                        <span class="text-sm font-semibold text-emerald-400">{{ $todayData['current_lesson']->subject?->name }}</span>
                        <span class="text-xs text-slate-400">with {{ $todayData['current_lesson']->active_substitute?->full_name ?? $todayData['current_lesson']->teacher?->full_name }} in {{ $todayData['current_lesson']->room?->name ?? 'Room' }}</span>
                    </div>
                @else
                    <div class="mt-1 text-sm font-semibold text-slate-300">No active lesson in session right now.</div>
                @endif
            </div>

            @if($todayData['next_lesson'])
                <div class="rounded-lg bg-slate-800/80 px-4 py-2 border border-slate-700/60">
                    <span class="text-[11px] text-slate-400 uppercase tracking-wider block font-semibold">Next Lesson:</span>
                    <span class="text-xs font-bold text-cyan-300">{{ $todayData['next_lesson']->subject?->name }}</span>
                    <span class="text-[11px] text-slate-400 block">{{ $todayData['next_lesson']->getFormattedTime() }} &bull; {{ $todayData['next_lesson']->room?->name ?? 'Room' }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Weekly Schedule Grid -->
    <div class="space-y-4">
        <h2 class="text-sm font-bold text-slate-200">Weekly Schedule</h2>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $dayName)
                @php
                    $daySlots = $weeklySlots[$dayName] ?? collect();
                @endphp
                <div class="rounded-xl border {{ $todayData['day'] === $dayName ? 'border-cyan-500/50 bg-cyan-950/20' : 'border-slate-800 bg-slate-900/60' }} p-3.5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-3">
                        <span class="text-xs font-bold {{ $todayData['day'] === $dayName ? 'text-cyan-400' : 'text-slate-300' }}">{{ $dayName }}</span>
                        <span class="text-[11px] text-slate-500">{{ $daySlots->count() }} lessons</span>
                    </div>

                    <div class="space-y-2">
                        @forelse($daySlots as $slot)
                            <div class="rounded-lg border border-slate-800/80 bg-slate-800/50 p-2.5 hover:border-slate-700 transition">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-slate-100">{{ $slot->subject?->name }}</span>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $slot->getFormattedTime() }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                                    <span>{{ $slot->teacher?->full_name }}</span>
                                    <span>{{ $slot->room?->name ?? '—' }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-[11px] text-slate-500">No scheduled classes</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
