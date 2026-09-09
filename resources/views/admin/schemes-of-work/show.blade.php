@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-slate-50 tracking-tight">{{ $scheme->title }}</h1>
            <span class="inline-flex items-center rounded-full border border-{{ $scheme->status_color }}-500/30 bg-{{ $scheme->status_color }}-500/10 px-3 py-0.5 text-xs font-semibold text-{{ $scheme->status_color }}-400 capitalize">
                {{ $scheme->status }}
            </span>
        </div>
        <p class="text-xs text-slate-400 mt-1">
            Teacher: <span class="text-slate-200 font-medium">{{ $scheme->teacher->full_name ?? '' }}</span> &bull; {{ $scheme->subject->name ?? '' }} &bull; {{ $scheme->schoolClass->name ?? '' }} &bull; {{ $scheme->academic_year }} {{ $scheme->term }}
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.schemes-of-work.print', $scheme) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2 text-xs font-semibold text-indigo-300 hover:bg-indigo-500/20 hover:text-white transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print MoPSE Document
        </a>
        <a href="{{ route('admin.schemes-of-work.teacher', $scheme->teacher) }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back to Teacher Schemes
        </a>
    </div>
</div>

<div class="space-y-6 text-xs text-slate-100">
    <!-- Admin Review & MoPSE Meta Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 backdrop-blur-xl p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row items-center justify-between border-b border-slate-800 pb-4 gap-2">
            <div class="text-center sm:text-left">
                <span class="text-[11px] font-bold tracking-widest text-indigo-400 uppercase">Ministry of Primary and Secondary Education (MoPSE)</span>
                <h2 class="text-base font-bold text-slate-100">{{ $scheme->school->name ?? 'Hande High School' }}</h2>
                <div class="text-[11px] text-slate-400">Teacher: <span class="text-slate-200 font-medium">{{ $scheme->teacher->full_name ?? '' }}</span></div>
            </div>
            <div class="text-right flex flex-col sm:items-end">
                <span class="text-[11px] font-semibold text-slate-300">Level / Form: <span class="text-indigo-400 font-bold">{{ $scheme->schoolClass->name ?? '-' }}</span></span>
                <span class="text-[11px] text-slate-400">Year: {{ $scheme->academic_year }} &bull; {{ $scheme->term }}</span>
                @if($scheme->submitted_at)
                    <span class="text-[10px] text-slate-400">Submitted: {{ $scheme->submitted_at->format('d M Y H:i') }}</span>
                @endif
                @if($scheme->reviewed_at)
                    <span class="text-[10px] text-emerald-400">Reviewed by {{ $scheme->reviewer->name ?? 'Admin' }}: {{ $scheme->reviewed_at->format('d M Y H:i') }}</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-950/60 p-4 rounded-xl border border-slate-800/80">
            <div>
                <span class="text-[10px] font-semibold text-indigo-400 uppercase tracking-wider block mb-1">General Topic / Theme</span>
                <p class="text-xs font-semibold text-slate-100">{{ $scheme->general_topic ?? $scheme->title }}</p>
                @if($scheme->syllabus_reference)
                    <span class="text-[10px] text-slate-400 mt-2 block"><strong class="text-slate-300">Syllabus Ref:</strong> {{ $scheme->syllabus_reference }}</span>
                @endif
            </div>
            <div class="md:col-span-2">
                <span class="text-[10px] font-semibold text-indigo-400 uppercase tracking-wider block mb-1">General Aims (Broad Scheme Aims)</span>
                <p class="text-xs text-slate-200 whitespace-pre-line leading-relaxed">{{ $scheme->aims ?? 'Aims not specified.' }}</p>
            </div>
        </div>

        @if(!empty($scheme->cross_cutting_themes))
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <span class="text-[10px] font-semibold uppercase text-slate-400 tracking-wider">Cross-Cutting Themes:</span>
                @foreach($scheme->cross_cutting_themes as $theme)
                    <span class="inline-flex items-center rounded-lg bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-0.5 text-[11px] font-medium text-indigo-300">
                        {{ $theme }}
                    </span>
                @endforeach
            </div>
        @endif

        @if($scheme->review_notes)
            <div class="rounded-xl border {{ $scheme->status === 'rejected' ? 'border-rose-500/40 bg-rose-500/10 text-rose-100' : 'border-emerald-500/40 bg-emerald-500/10 text-emerald-100' }} p-3 text-xs">
                <strong>Review Notes / Feedback:</strong> {{ $scheme->review_notes }}
            </div>
        @endif

        @if($scheme->status === 'submitted')
            <div class="pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <form method="POST" action="{{ route('admin.schemes-of-work.approve', $scheme) }}" class="inline">
                    @csrf
                    <input type="text" name="review_notes" placeholder="Approval comment (optional)"
                        class="rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 w-64 mr-2 focus:border-indigo-500 focus:outline-none">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white hover:bg-emerald-500 shadow-md shadow-emerald-500/20 transition">
                        Approve Scheme
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.schemes-of-work.reject', $scheme) }}" class="inline flex gap-2" onsubmit="return confirm('Reject this scheme with feedback?')">
                    @csrf
                    <input type="text" name="review_notes" placeholder="Rejection feedback (required)" required
                        class="rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 w-64 focus:border-rose-500 focus:outline-none">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-rose-600 px-5 py-2 text-xs font-semibold text-white hover:bg-rose-500 shadow-md shadow-rose-500/20 transition">
                        Reject Scheme
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Timetable Synchronization -->
    @if($timetableSlots->count() > 0)
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 backdrop-blur-xl p-5 shadow-xl">
            <h2 class="text-sm font-semibold text-slate-100 mb-1 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-cyan-500"></span>
                Timetable Schedule Alignment ({{ $activeTimetable->name ?? '' }})
            </h2>
            <p class="text-[11px] text-slate-400 mb-4">Scheduled teaching periods for this teacher and subject mapped against scheme units</p>

            @foreach($timetableSlots as $day => $slots)
                <div class="mb-4 last:mb-0">
                    <h3 class="text-xs font-bold text-slate-300 mb-2 uppercase tracking-wider">{{ $day }}</h3>
                    <div class="overflow-x-auto rounded-xl border border-slate-800">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-950 text-slate-300">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Time</th>
                                    <th class="px-3 py-2 text-left font-medium">Class</th>
                                    <th class="px-3 py-2 text-left font-medium">Subject</th>
                                    <th class="px-3 py-2 text-left font-medium">Room</th>
                                    <th class="px-3 py-2 text-left font-medium">Aligned Scheme Topics</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 bg-slate-900/50">
                                @foreach($slots as $slot)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="px-3 py-2 text-slate-300 font-mono">{{ $slot->getFormattedTime() }}</td>
                                        <td class="px-3 py-2 text-slate-300">{{ $slot->schoolClass->name ?? '-' }}</td>
                                        <td class="px-3 py-2 text-slate-300">{{ $slot->subject->name ?? '-' }}</td>
                                        <td class="px-3 py-2 text-slate-400">{{ $slot->room->name ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            @php($matchingItems = $scheme->items->where('day_of_week', $day))
                                            @if($matchingItems->count() > 0)
                                                <div class="space-y-1">
                                                    @foreach($matchingItems as $item)
                                                        <div class="text-[11px]">
                                                            <span class="text-indigo-400 font-medium">Wk{{ $item->week_number }}:</span>
                                                            <span class="text-slate-200">{{ $item->topic }}</span>
                                                            @if($item->sub_topic)
                                                                <span class="text-slate-400"> - {{ $item->sub_topic }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-[11px] text-slate-500">General scheme unit</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- MoPSE 8-Column Scheme-Cum Plan Matrix Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 backdrop-blur-xl p-5 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-100 uppercase tracking-wider flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                Scheme-Cum Plan Matrix (8 Columns)
            </h2>
            <span class="text-[11px] text-slate-400">{{ $scheme->items->count() }} Units Submitted</span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-950 text-slate-200 uppercase tracking-wider text-[10px] font-bold border-b border-slate-800">
                    <tr>
                        <th class="px-3.5 py-3 border-r border-slate-800 w-28">Week Ending</th>
                        <th class="px-3.5 py-3 border-r border-slate-800 w-44">Content / Topic</th>
                        <th class="px-3.5 py-3 border-r border-slate-800 w-64">Objectives</th>
                        <th class="px-3.5 py-3 border-r border-slate-800 w-40">Competencies / Skills</th>
                        <th class="px-3.5 py-3 border-r border-slate-800 w-36">SOM / Media</th>
                        <th class="px-3.5 py-3 border-r border-slate-800 w-36">Facility / Equipment</th>
                        <th class="px-3.5 py-3 border-r border-slate-800 w-48">Methods / Activities</th>
                        <th class="px-3.5 py-3 w-40">Evaluation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 bg-slate-900/50">
                    @forelse($scheme->items as $item)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3.5 py-3 border-r border-slate-800 align-top">
                                <div class="font-bold text-indigo-400">Week {{ $item->week_number }}</div>
                                <div class="text-[11px] text-slate-300 font-mono mt-0.5">
                                    {{ $item->week_ending ? $item->week_ending->format('d/m/Y') : ($item->day_of_week ?? '-') }}
                                </div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-800 align-top">
                                <div class="font-semibold text-slate-100 leading-snug">{{ $item->topic }}</div>
                                @if($item->sub_topic)
                                    <div class="text-[11px] text-slate-400 mt-1 italic">{{ $item->sub_topic }}</div>
                                @endif
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-800 align-top">
                                <div class="text-[11px] text-slate-200 whitespace-pre-line leading-relaxed">{{ $item->objectives }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-800 align-top">
                                <div class="text-[11px] text-slate-300 whitespace-pre-line leading-relaxed">{{ $item->competencies_skills ?? '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-800 align-top">
                                <div class="text-[11px] text-slate-300 whitespace-pre-line leading-relaxed">{{ $item->som_media ?? $item->resources ?? '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-800 align-top">
                                <div class="text-[11px] text-slate-300 whitespace-pre-line leading-relaxed">{{ $item->facility_equipment ?? '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-800 align-top">
                                <div class="text-[11px] text-slate-300 whitespace-pre-line leading-relaxed">{{ $item->methods_activities ?? $item->teaching_methods ?? '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 align-top">
                                <div class="text-[11px] text-slate-400 whitespace-pre-line italic leading-relaxed">{{ $item->evaluation ?? $item->remarks ?? 'Pending delivery' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-slate-500 text-xs">No scheme units recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
