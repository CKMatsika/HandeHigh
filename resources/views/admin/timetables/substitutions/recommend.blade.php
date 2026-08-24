@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.timetables.operations') }}" class="text-slate-400 hover:text-slate-200 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Substitute Recommendations</h1>
                <p class="text-xs text-slate-400">Deterministic scoring & suitability ranking for lesson coverage.</p>
            </div>
        </div>
    </div>

    <!-- Target Slot Details Card -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5 shadow-sm">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-5 text-xs">
            <div>
                <span class="text-slate-400 block">Date & Day</span>
                <span class="font-semibold text-slate-100">{{ $date }} ({{ $slot->day_of_week }})</span>
            </div>
            <div>
                <span class="text-slate-400 block">Class & Subject</span>
                <span class="font-semibold text-slate-100">{{ $slot->schoolClass?->name }} &bull; {{ $slot->subject?->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Original Teacher</span>
                <span class="font-semibold text-rose-400">{{ $slot->teacher?->full_name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Time & Room</span>
                <span class="font-semibold text-slate-100">{{ $slot->getFormattedTime() }} &bull; {{ $slot->room?->name ?? 'Room' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Eligible Candidates</span>
                <span class="font-semibold text-emerald-400">{{ $recommendations->count() }} Available</span>
            </div>
        </div>
    </div>

    <!-- Ranked Candidates List -->
    <div class="space-y-3">
        @forelse($recommendations as $index => $rec)
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-sm hover:border-slate-700 transition flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-slate-300">#{{ $index + 1 }}</span>
                        <h3 class="text-sm font-bold text-slate-100">{{ $rec->teacher->full_name }}</h3>
                        <span class="text-xs text-slate-400 font-mono">{{ $rec->teacher->specialization ?? 'Teacher' }}</span>
                        <span class="rounded bg-indigo-500/10 px-2 py-0.5 text-xs font-bold text-indigo-400 border border-indigo-500/20">Score: {{ $rec->score }}/100</span>
                    </div>

                    <!-- Highlights & Breakdown -->
                    <div class="flex flex-wrap items-center gap-2 pt-1 pl-9">
                        @foreach($rec->highlights as $h)
                            <span class="inline-flex items-center gap-1 rounded bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-400">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                {{ $h }}
                            </span>
                        @endforeach
                        @foreach($rec->warnings as $w)
                            <span class="inline-flex items-center gap-1 rounded bg-amber-500/10 px-2 py-0.5 text-[11px] font-medium text-amber-400">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                {{ $w }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end md:self-center">
                    <form action="{{ route('admin.timetables.substitutions.store', $slot) }}" method="POST">
                        @csrf
                        <input type="hidden" name="substitute_teacher_id" value="{{ $rec->teacher->id }}">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <input type="hidden" name="auto_approve" value="1">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500 transition shadow-sm">
                            Assign & Approve Cover
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-8 text-center text-slate-400">
                No eligible substitute candidates found free at this time.
            </div>
        @endforelse
    </div>
</div>
@endsection
