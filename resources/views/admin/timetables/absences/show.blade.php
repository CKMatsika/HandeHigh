@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.timetables.absences.index') }}" class="text-slate-400 hover:text-slate-200 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Absence Impact: {{ $absence->teacher?->full_name }}</h1>
                <p class="text-xs text-slate-400">Dates: {{ $absence->start_date->format('d M Y') }} &rarr; {{ $absence->end_date->format('d M Y') }} &bull; Reason: {{ $absence->reason }}</p>
            </div>
        </div>
    </div>

    <!-- Affected Lessons Breakdown -->
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60 shadow-sm">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-200">Affected Lesson Slots ({{ $affectedSlots->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-800/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Occurrence Date</th>
                        <th class="px-4 py-3">Day & Time</th>
                        <th class="px-4 py-3">Class</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Room</th>
                        <th class="px-4 py-3">Coverage Status</th>
                        <th class="px-4 py-3 text-right">Substitute Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($affectedSlots as $slot)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-semibold text-indigo-300">{{ $slot->occurrence_date }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-200">{{ $slot->day_of_week }}</span>
                                <span class="font-mono text-[11px] text-slate-400 ml-1">{{ $slot->getFormattedTime() }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-100">{{ $slot->schoolClass?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-200">{{ $slot->subject?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $slot->room?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($slot->is_covered)
                                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">
                                        Covered: {{ $slot->existing_substitution?->substituteTeacher?->full_name }}
                                    </span>
                                @else
                                    <span class="rounded-full bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-rose-400 border border-rose-500/20">Uncovered</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.timetables.substitutions.recommend', ['slot' => $slot->id, 'date' => $slot->occurrence_date]) }}" class="rounded bg-purple-600/20 border border-purple-500/30 px-2.5 py-1 text-xs font-medium text-purple-300 hover:bg-purple-600/30 transition">
                                    {{ $slot->is_covered ? 'Change Cover' : 'Assign Substitute' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No timetable lessons fall within this absence range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
