@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.timetables.show', $timetable) }}" class="text-slate-400 hover:text-slate-200 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Timetable Diagnostics & Conflict Engine</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1 pl-8">{{ $timetable->name }} &bull; {{ $timetable->academic_year }} - {{ $timetable->term }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.timetables.show', $timetable) }}" class="rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700 transition">Back to Matrix</a>
            @if(($validation['summary']['hard_conflicts_count'] ?? 0) === 0 && ! $timetable->isPublished())
                <form action="{{ route('admin.timetables.publish', $timetable) }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-emerald-500 transition shadow-sm">
                        Publish Timetable
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Alert / Messages -->
    @if(session('error'))
        <div class="rounded-lg bg-rose-500/10 border border-rose-500/20 p-4 text-sm text-rose-400">
            {{ session('error') }}
        </div>
    @endif

    <!-- Score & Overview -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-100">Schedule Health Overview</h2>
                <p class="text-xs text-slate-400 mt-0.5">Deterministic constraint engine validation report.</p>
            </div>
            <div class="flex items-center gap-6">
                <div>
                    <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Health Score</span>
                    <span class="text-2xl font-bold {{ ($validation['score'] ?? 100) >= 80 ? 'text-emerald-400' : (($validation['score'] ?? 100) >= 50 ? 'text-amber-400' : 'text-rose-400') }}">{{ $validation['score'] ?? 100 }}%</span>
                </div>
                <div>
                    <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Hard Conflicts</span>
                    <span class="text-2xl font-bold {{ ($validation['summary']['hard_conflicts_count'] ?? 0) > 0 ? 'text-rose-400' : 'text-emerald-400' }}">{{ $validation['summary']['hard_conflicts_count'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Soft Warnings</span>
                    <span class="text-2xl font-bold text-amber-400">{{ $validation['summary']['soft_warnings_count'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Hard Conflicts Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-200 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                <span>Hard Conflicts ({{ count($validation['hard_conflicts'] ?? []) }})</span>
            </h2>
            <span class="text-xs text-slate-400">Strict constraints that prevent timetable publication</span>
        </div>

        @if(empty($validation['hard_conflicts']))
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-6 text-center">
                <svg class="mx-auto h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <h3 class="mt-2 text-sm font-medium text-emerald-300">No Hard Conflicts Detected</h3>
                <p class="text-xs text-slate-400 mt-0.5">All teacher double-bookings, class collisions, room overlaps, and fixed event constraints are satisfied.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($validation['hard_conflicts'] as $conflict)
                    <div class="rounded-xl border border-rose-500/30 bg-rose-500/5 p-4 text-xs">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="inline-flex items-center rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-semibold text-rose-300 uppercase tracking-wider">{{ $conflict->type }}</span>
                                <p class="mt-2 text-sm font-medium text-slate-100">{{ $conflict->message }}</p>
                            </div>
                            <span class="rounded bg-rose-500/20 px-2 py-1 text-[11px] font-bold text-rose-300">BLOCKING</span>
                        </div>
                        @if(!empty($conflict->details))
                            <div class="mt-3 rounded-lg bg-slate-900/60 p-2.5 border border-slate-800/80 text-[11px] text-slate-400 space-y-1">
                                @foreach($conflict->details as $key => $val)
                                    <div><span class="text-slate-500">{{ ucwords(str_replace('_', ' ', $key)) }}:</span> <span class="text-slate-300 font-mono">{{ is_array($val) ? json_encode($val) : $val }}</span></div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Soft Warnings Section -->
    <div class="space-y-4 pt-4 border-t border-slate-800">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-200 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                <span>Soft Warnings & Distribution ({{ count($validation['soft_warnings'] ?? []) }})</span>
            </h2>
            <span class="text-xs text-slate-400">Heuristic suggestions for balanced workload & learning efficacy</span>
        </div>

        @if(empty($validation['soft_warnings']))
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 text-center text-xs text-slate-400">
                No soft constraint warnings found. Schedule distribution is well balanced.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($validation['soft_warnings'] as $warning)
                    <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs">
                        <span class="inline-flex items-center rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-semibold text-amber-300 uppercase tracking-wider">{{ $warning->type }}</span>
                        <p class="mt-2 text-xs font-medium text-slate-200">{{ $warning->message }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Curriculum Requirements Section -->
    <div class="space-y-4 pt-4 border-t border-slate-800">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-200 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                <span>Weekly Curriculum Period Requirements</span>
            </h2>
            <span class="text-xs text-slate-400">Comparison against configured syllabus required weekly periods</span>
        </div>

        @if(empty($validation['curriculum_requirements']['items']))
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 text-center text-xs text-slate-400">
                No curriculum requirements registered for this academic year/term.
            </div>
        @else
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-800/60 text-slate-300 font-semibold">
                            <th class="p-3">Class</th>
                            <th class="p-3">Subject</th>
                            <th class="p-3 text-center">Required Periods</th>
                            <th class="p-3 text-center">Scheduled Periods</th>
                            <th class="p-3 text-center">Difference</th>
                            <th class="p-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($validation['curriculum_requirements']['items'] as $item)
                            <tr class="hover:bg-slate-800/20">
                                <td class="p-3 font-medium text-slate-200">{{ $item['class_name'] }}</td>
                                <td class="p-3 text-slate-300">{{ $item['subject_name'] }}</td>
                                <td class="p-3 text-center text-slate-300 font-mono">{{ $item['required_periods'] }}/wk</td>
                                <td class="p-3 text-center text-slate-300 font-mono">{{ $item['scheduled_periods'] }}/wk</td>
                                <td class="p-3 text-center font-mono">
                                    @if($item['difference'] === 0)
                                        <span class="text-emerald-400">0</span>
                                    @elseif($item['difference'] > 0)
                                        <span class="text-blue-400">+{{ $item['difference'] }}</span>
                                    @else
                                        <span class="text-rose-400">{{ $item['difference'] }}</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    @if($item['is_met'])
                                        <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-400 border border-emerald-500/20">Met</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-semibold text-rose-400 border border-rose-500/20">Deficit</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
