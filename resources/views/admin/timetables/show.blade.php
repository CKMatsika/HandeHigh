@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.timetables.index') }}" class="text-slate-400 hover:text-slate-200 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">{{ $timetable->name }}</h1>
                @if($timetable->isPublished())
                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-400 border border-emerald-500/20">Published</span>
                @else
                    <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-400 border border-amber-500/20">Draft</span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-1 pl-8">{{ $timetable->academic_year }} &bull; {{ $timetable->term }} &bull; {{ $timetable->slots->count() }} Total Slots</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.timetables.requirements.index', $timetable) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700 transition">
                <svg class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                <span>Requirements</span>
            </a>

            <a href="{{ route('admin.timetables.candidates.index', $timetable) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700 transition">
                <svg class="h-4 w-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                <span>Candidates</span>
            </a>

            <a href="{{ route('admin.timetables.generate.show', $timetable) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-500/40 bg-indigo-600/20 px-3.5 py-2 text-xs font-medium text-indigo-300 hover:bg-indigo-600/30 transition">
                <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                <span>Generate & Optimize</span>
            </a>

            <a href="{{ route('admin.timetables.conflicts', $timetable) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium {{ ($validation['summary']['hard_conflicts_count'] ?? 0) > 0 ? 'text-rose-400 border-rose-500/30' : 'text-slate-300' }} hover:bg-slate-700 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>Diagnostics ({{ $validation['summary']['hard_conflicts_count'] ?? 0 }} Hard / {{ $validation['summary']['soft_warnings_count'] ?? 0 }} Soft)</span>
            </a>

            <a href="{{ route('admin.timetables.export', ['timetable' => $timetable, 'type' => $viewType, 'class_id' => $classId, 'teacher_id' => $teacherId]) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                <span>Print View</span>
            </a>

            @if($timetable->isPublished())
                <form action="{{ route('admin.timetables.unpublish', $timetable) }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg border border-amber-600/40 bg-amber-500/10 px-3.5 py-2 text-xs font-medium text-amber-400 hover:bg-amber-500/20 transition">Revert to Draft</button>
                </form>
            @else
                <form action="{{ route('admin.timetables.publish', $timetable) }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-emerald-500 transition shadow-sm {{ ($validation['summary']['hard_conflicts_count'] ?? 0) > 0 ? 'opacity-50 cursor-not-allowed' : '' }}" {{ ($validation['summary']['hard_conflicts_count'] ?? 0) > 0 ? 'disabled' : '' }}>
                        Publish Timetable
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Alert / Messages -->
    @if(session('success'))
        <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-4 text-sm text-emerald-400">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-rose-500/10 border border-rose-500/20 p-4 text-sm text-rose-400">
            {{ session('error') }}
        </div>
    @endif

    <!-- Health & Diagnostics Bar -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs text-slate-500 block">Timetable Health Score</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-bold {{ ($validation['score'] ?? 100) >= 80 ? 'text-emerald-400' : (($validation['score'] ?? 100) >= 50 ? 'text-amber-400' : 'text-rose-400') }}">{{ $validation['score'] ?? 100 }}%</span>
                <span class="text-xs text-slate-400">{{ ($validation['score'] ?? 100) === 100 ? 'Optimal' : 'Needs attention' }}</span>
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs text-slate-500 block">Hard Conflicts (Blocking)</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-bold {{ ($validation['summary']['hard_conflicts_count'] ?? 0) > 0 ? 'text-rose-400' : 'text-slate-200' }}">{{ $validation['summary']['hard_conflicts_count'] ?? 0 }}</span>
                <span class="text-xs text-slate-400">Must be 0 to publish</span>
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs text-slate-500 block">Soft Warnings</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-amber-400">{{ $validation['summary']['soft_warnings_count'] ?? 0 }}</span>
                <span class="text-xs text-slate-400">Workload & distribution</span>
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <span class="text-xs text-slate-500 block">Curriculum Unmet</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-slate-200">{{ $validation['summary']['curriculum_unmet_count'] ?? 0 }}</span>
                <span class="text-xs text-slate-400">Subject deficit</span>
            </div>
        </div>
    </div>

    <!-- View Mode Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.timetables.show', ['timetable' => $timetable, 'view' => 'master']) }}" class="rounded-lg px-3.5 py-1.5 text-xs font-medium transition {{ $viewType === 'master' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">Master Matrix</a>
            <a href="{{ route('admin.timetables.show', ['timetable' => $timetable, 'view' => 'class', 'class_id' => $classId ?? $classes->first()?->id]) }}" class="rounded-lg px-3.5 py-1.5 text-xs font-medium transition {{ $viewType === 'class' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">By Class</a>
            <a href="{{ route('admin.timetables.show', ['timetable' => $timetable, 'view' => 'teacher', 'teacher_id' => $teacherId ?? $teachers->first()?->id]) }}" class="rounded-lg px-3.5 py-1.5 text-xs font-medium transition {{ $viewType === 'teacher' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">By Teacher</a>
        </div>

        @if($viewType === 'class')
            <form method="GET" action="{{ route('admin.timetables.show', $timetable) }}" class="flex items-center gap-2">
                <input type="hidden" name="view" value="class">
                <label class="text-xs text-slate-400">Select Class:</label>
                <select name="class_id" onchange="this.form.submit()" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ (string)$classId === (string)$c->id ? 'selected' : '' }}>{{ $c->name }} (Grade {{ $c->grade }})</option>
                    @endforeach
                </select>
            </form>
        @elseif($viewType === 'teacher')
            <form method="GET" action="{{ route('admin.timetables.show', $timetable) }}" class="flex items-center gap-2">
                <input type="hidden" name="view" value="teacher">
                <label class="text-xs text-slate-400">Select Teacher:</label>
                <select name="teacher_id" onchange="this.form.submit()" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ (string)$teacherId === (string)$t->id ? 'selected' : '' }}>{{ $t->full_name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    <!-- Master Grid View -->
    @if($viewType === 'master' && $masterData)
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-800/60">
                            <th class="p-3 font-semibold text-slate-300 sticky left-0 bg-slate-800/90 min-w-[120px]">Class / Day</th>
                            @foreach($masterData['periods'] as $p)
                                <th class="p-3 font-semibold text-slate-300 text-center border-l border-slate-800 min-w-[140px]">
                                    <div>{{ $p->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-normal mt-0.5">{{ $p->getFormattedTime() }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($masterData['days'] as $day)
                            <tr class="bg-slate-800/30 font-semibold text-blue-400">
                                <td colspan="{{ count($masterData['periods']) + 1 }}" class="px-3 py-2 uppercase tracking-wider text-[11px] bg-slate-800/50">
                                    {{ $day }}
                                </td>
                            </tr>
                            @foreach($masterData['classes'] as $cls)
                                <tr class="hover:bg-slate-800/20 transition">
                                    <td class="p-3 font-medium text-slate-200 sticky left-0 bg-slate-900/95 border-r border-slate-800">
                                        {{ $cls->name }}
                                    </td>
                                    @foreach($masterData['periods'] as $p)
                                        @php
                                            $matchingSlot = $masterData['slots']->first(function($s) use ($day, $p, $cls) {
                                                return $s->school_class_id === $cls->id &&
                                                       strcasecmp($s->day_of_week, $day) === 0 &&
                                                       (($s->school_period_id && $s->school_period_id === $p->id) ||
                                                        (substr($s->start_time, 0, 5) === substr($p->start_time, 0, 5)));
                                            });
                                        @endphp
                                        <td class="p-2 border-l border-slate-800/80 text-center align-top">
                                            @if($matchingSlot)
                                                <div class="rounded-lg p-2 {{ $matchingSlot->hasConflicts() ? 'bg-rose-500/10 border border-rose-500/30' : 'bg-slate-800/90 border border-slate-700/60' }} text-left shadow-sm">
                                                    <div class="font-semibold text-slate-100 flex items-center justify-between">
                                                        <span>{{ $matchingSlot->subject?->name ?? 'Lesson' }}</span>
                                                        <div class="flex items-center gap-1">
                                                            @if($matchingSlot->isLocked())
                                                                <span class="text-amber-400 text-[10px]" title="Locked Slot">🔒</span>
                                                            @endif
                                                            @if($matchingSlot->hasConflicts())
                                                                <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse" title="Conflict detected"></span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $matchingSlot->teacher?->full_name ?? 'No teacher' }}</div>
                                                    @if($matchingSlot->room)
                                                        <div class="text-[10px] text-slate-500 mt-0.5 flex items-center gap-1">
                                                            <span>Room: {{ $matchingSlot->room->name }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif(! $p->isLesson())
                                                <div class="rounded-lg p-2 bg-slate-800/30 text-slate-500 text-[11px] italic text-center">
                                                    {{ $p->name }}
                                                </div>
                                            @else
                                                <div class="h-full min-h-[42px] rounded-lg border border-dashed border-slate-800/60 flex items-center justify-center text-slate-600 text-[10px]">
                                                    Free
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Single Class Grid View -->
    @if($viewType === 'class' && $classData)
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 bg-slate-800/40 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-200">Class Timetable: {{ $classData['class']?->name }} (Grade {{ $classData['class']?->grade }})</h3>
                <span class="text-xs text-slate-400">{{ $timetable->academic_year }} - {{ $timetable->term }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-800/60">
                            <th class="p-3 font-semibold text-slate-300 min-w-[100px]">Day</th>
                            @foreach($classData['periods'] as $p)
                                <th class="p-3 font-semibold text-slate-300 text-center border-l border-slate-800 min-w-[130px]">
                                    <div>{{ $p->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-normal mt-0.5">{{ $p->getFormattedTime() }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($classData['days'] as $day)
                            <tr class="hover:bg-slate-800/20 transition">
                                <td class="p-3 font-semibold text-blue-400 border-r border-slate-800 bg-slate-800/20">
                                    {{ $day }}
                                </td>
                                @foreach($classData['periods'] as $p)
                                    @php
                                        $cell = $classData['grid'][$day][$p->id] ?? null;
                                        $slot = $cell['slot'] ?? null;
                                    @endphp
                                    <td class="p-2 border-l border-slate-800/80 align-top">
                                        @if($slot)
                                            <div class="rounded-lg p-2.5 {{ $slot->hasConflicts() ? 'bg-rose-500/10 border border-rose-500/30' : 'bg-slate-800 border border-slate-700/60' }} shadow-sm">
                                                <div class="font-semibold text-slate-100">{{ $slot->subject?->name }}</div>
                                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $slot->teacher?->full_name }}</div>
                                                @if($slot->room)
                                                    <div class="text-[10px] text-slate-500 mt-0.5">Room: {{ $slot->room->name }}</div>
                                                @endif
                                            </div>
                                        @elseif(! $p->isLesson())
                                            <div class="p-2 bg-slate-800/30 rounded-lg text-slate-500 text-[11px] text-center italic">
                                                {{ $p->name }}
                                            </div>
                                        @else
                                            <div class="p-2 rounded-lg border border-dashed border-slate-800 text-slate-600 text-center text-[10px]">
                                                Free Period
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Single Teacher Grid View -->
    @if($viewType === 'teacher' && $teacherData)
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 bg-slate-800/40 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-200">Teacher Timetable: {{ $teacherData['teacher']?->full_name }}</h3>
                <span class="text-xs text-slate-400">{{ $timetable->academic_year }} - {{ $timetable->term }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-800/60">
                            <th class="p-3 font-semibold text-slate-300 min-w-[100px]">Day</th>
                            @foreach($teacherData['periods'] as $p)
                                <th class="p-3 font-semibold text-slate-300 text-center border-l border-slate-800 min-w-[130px]">
                                    <div>{{ $p->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-normal mt-0.5">{{ $p->getFormattedTime() }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($teacherData['days'] as $day)
                            <tr class="hover:bg-slate-800/20 transition">
                                <td class="p-3 font-semibold text-blue-400 border-r border-slate-800 bg-slate-800/20">
                                    {{ $day }}
                                </td>
                                @foreach($teacherData['periods'] as $p)
                                    @php
                                        $cell = $teacherData['grid'][$day][$p->id] ?? null;
                                        $slot = $cell['slot'] ?? null;
                                    @endphp
                                    <td class="p-2 border-l border-slate-800/80 align-top">
                                        @if($slot)
                                            <div class="rounded-lg p-2.5 {{ $slot->hasConflicts() ? 'bg-rose-500/10 border border-rose-500/30' : 'bg-slate-800 border border-slate-700/60' }} shadow-sm">
                                                <div class="font-semibold text-slate-100">{{ $slot->schoolClass?->name }}</div>
                                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $slot->subject?->name }}</div>
                                                @if($slot->room)
                                                    <div class="text-[10px] text-slate-500 mt-0.5">Room: {{ $slot->room->name }}</div>
                                                @endif
                                            </div>
                                        @elseif(! $p->isLesson())
                                            <div class="p-2 bg-slate-800/30 rounded-lg text-slate-500 text-[11px] text-center italic">
                                                {{ $p->name }}
                                            </div>
                                        @else
                                            <div class="p-2 rounded-lg border border-dashed border-slate-800 text-slate-600 text-center text-[10px]">
                                                Free Period
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
