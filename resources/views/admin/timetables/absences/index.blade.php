@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.timetables.operations') }}" class="text-slate-400 hover:text-slate-200 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Teacher Absence Management</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1 pl-8">Record teacher absences, view affected timetable periods, and trigger substitute workflows.</p>
        </div>
    </div>

    <!-- Record Absence Form Card -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-200 mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            Record Teacher Absence
        </h2>

        <form action="{{ route('admin.timetables.absences.store') }}" method="POST" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Teacher *</label>
                <select name="teacher_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                    <option value="">Select Teacher...</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->full_name }} ({{ $t->specialization ?? 'Teacher' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Start Date *</label>
                <input type="date" name="start_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">End Date *</label>
                <input type="date" name="end_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Reason *</label>
                <select name="reason" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Official Duty / Workshop">Official Duty / Workshop</option>
                    <option value="Emergency">Emergency</option>
                    <option value="Personal Leave">Personal Leave</option>
                    <option value="Examination Duty">Examination Duty</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-500 transition shadow-sm">
                    Record Absence & Find Lessons
                </button>
            </div>
        </form>
    </div>

    <!-- Absences List -->
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-800/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Teacher</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Recorded By</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($absences as $abs)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-semibold text-slate-100">{{ $abs->teacher?->full_name }}</td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-300">
                                {{ $abs->start_date->format('d M Y') }} &rarr; {{ $abs->end_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-slate-200">{{ $abs->reason }}</td>
                            <td class="px-4 py-3">
                                @if($abs->status === 'active')
                                    <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-400 border border-amber-500/20">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-[11px] font-semibold text-slate-400">{{ ucfirst($abs->status) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ $abs->recordedBy?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.timetables.absences.show', $abs) }}" class="rounded bg-indigo-600/20 border border-indigo-500/30 px-2 py-1 text-[11px] font-medium text-indigo-300 hover:bg-indigo-600/30 transition">
                                        Affected Lessons
                                    </a>
                                    @if($abs->status === 'active')
                                        <form action="{{ route('admin.timetables.absences.cancel', $abs) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this absence?');">
                                            @csrf
                                            <button type="submit" class="rounded bg-slate-800 px-2 py-1 text-[11px] text-slate-400 hover:text-rose-400 transition">Cancel</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                No teacher absences recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
