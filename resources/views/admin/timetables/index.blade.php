@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-50">Timetable Management</h1>
            <p class="text-sm text-slate-400">Manage academic timetables, period definitions, fixed activities, and exam schedules.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.timetables.periods.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Periods ({{ $periodsCount }})</span>
            </a>
            <a href="{{ route('admin.timetables.fixed-activities.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span>Fixed Activities ({{ $fixedActivitiesCount }})</span>
            </a>
            <a href="{{ route('admin.timetables.examinations.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <span>Examinations ({{ $examinationsCount }})</span>
            </a>
            <a href="{{ route('admin.timetables.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-blue-500 transition shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Create Timetable</span>
            </a>
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

    <!-- Timetables Grid -->
    @if($timetables->isEmpty())
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            <h3 class="mt-4 text-lg font-medium text-slate-200">No Timetables Configured</h3>
            <p class="mt-1 text-sm text-slate-400">Get started by defining school periods or creating a new academic timetable.</p>
            <div class="mt-6 flex justify-center gap-3">
                @if($periodsCount === 0)
                    <form action="{{ route('admin.timetables.periods.seed-defaults') }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-sm font-medium text-slate-200 hover:bg-slate-700 transition">Initialize Default Periods</button>
                    </form>
                @endif
                <a href="{{ route('admin.timetables.create') }}" class="rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-blue-500 transition">Create Timetable</a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($timetables as $timetable)
                <div class="flex flex-col justify-between rounded-xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold text-slate-100">{{ $timetable->name }}</h3>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $timetable->academic_year }} &bull; {{ $timetable->term }}</p>
                            </div>
                            <div>
                                @if($timetable->status === 'published')
                                    <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-400 border border-emerald-500/20">Published</span>
                                @elseif($timetable->status === 'generated')
                                    <span class="inline-flex items-center rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-medium text-blue-400 border border-blue-500/20">Generated</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-medium text-amber-400 border border-amber-500/20">Draft</span>
                                @endif
                            </div>
                        </div>

                        @if($timetable->description)
                            <p class="mt-3 text-xs text-slate-400 line-clamp-2">{{ $timetable->description }}</p>
                        @endif

                        <div class="mt-4 grid grid-cols-2 gap-3 py-3 border-y border-slate-800/80 text-xs">
                            <div>
                                <span class="text-slate-500 block">Total Slots</span>
                                <span class="font-medium text-slate-200 text-sm">{{ $timetable->slots_count }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Conflict Status</span>
                                @if($timetable->hasConflicts())
                                    <span class="font-medium text-rose-400 flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        {{ $timetable->getConflictCount() }} Conflicts
                                    </span>
                                @else
                                    <span class="font-medium text-emerald-400 flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Conflict-Free
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-2 pt-2">
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.timetables.show', $timetable) }}" class="rounded-lg bg-blue-600/20 px-3 py-1.5 text-xs font-medium text-blue-400 hover:bg-blue-600/30 transition">View Matrix</a>
                            <a href="{{ route('admin.timetables.conflicts', $timetable) }}" class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 transition">Diagnostics</a>
                            <a href="{{ route('admin.timetables.export', $timetable) }}" target="_blank" class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 transition" title="Print / Export">Print</a>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.timetables.edit', $timetable) }}" class="p-1.5 text-slate-400 hover:text-slate-200 transition" title="Edit Settings">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                            </a>
                            <form action="{{ route('admin.timetables.destroy', $timetable) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this timetable?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 transition" title="Delete Timetable">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
