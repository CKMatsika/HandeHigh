@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">{{ $scheme->title }}</h1>
        <p class="text-xs text-slate-400 mt-1">{{ $scheme->teacher->full_name ?? '' }} | {{ $scheme->subject->name ?? '' }} - {{ $scheme->schoolClass->name ?? '' }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.schemes-of-work.teacher', $scheme->teacher) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back to Teacher
        </a>
    </div>
</div>

<div class="space-y-6">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <div class="flex items-center gap-3 mb-4">
            <span class="inline-flex items-center rounded-full border border-{{ $scheme->status_color }}-500/30 bg-{{ $scheme->status_color }}-500/10 px-3 py-1 text-xs font-medium text-{{ $scheme->status_color }}-400 capitalize">
                {{ $scheme->status }}
            </span>
            @if($scheme->submitted_at)
                <span class="text-[11px] text-slate-400">Submitted: {{ $scheme->submitted_at->format('d M Y H:i') }}</span>
            @endif
            @if($scheme->reviewed_at)
                <span class="text-[11px] text-slate-400">Reviewed by {{ $scheme->reviewer->name ?? '' }}: {{ $scheme->reviewed_at->format('d M Y H:i') }}</span>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs mb-4 pb-4 border-b border-slate-800">
            <div><span class="text-slate-400">Subject:</span> <span class="text-slate-100">{{ $scheme->subject->name ?? '-' }}</span></div>
            <div><span class="text-slate-400">Class:</span> <span class="text-slate-100">{{ $scheme->schoolClass->name ?? '-' }}</span></div>
            <div><span class="text-slate-400">Year:</span> <span class="text-slate-100">{{ $scheme->academic_year }}</span></div>
            <div><span class="text-slate-400">Term:</span> <span class="text-slate-100">{{ $scheme->term }}</span></div>
        </div>

        @if($scheme->description)
            <p class="text-xs text-slate-300 mb-4">{{ $scheme->description }}</p>
        @endif

        @if($scheme->review_notes && $scheme->status === 'rejected')
            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100 mb-4">
                <strong>Rejection Feedback:</strong> {{ $scheme->review_notes }}
            </div>
        @endif

        @if($scheme->status === 'submitted')
            <div class="flex gap-2 mb-4">
                <form method="POST" action="{{ route('admin.schemes-of-work.approve', $scheme) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                        Approve
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.schemes-of-work.reject', $scheme) }}" class="inline flex gap-2" onsubmit="return confirm('Reject this scheme?')">
                    @csrf
                    <input type="text" name="review_notes" placeholder="Rejection reason (required)" required
                        class="rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-1.5 text-xs text-slate-100 w-64">
                    <button type="submit" class="inline-flex items-center rounded-full bg-red-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-red-600 transition">
                        Reject
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if($timetableSlots->count() > 0)
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Timetable Schedule ({{ $activeTimetable->name ?? '' }})</h2>
            <p class="text-[11px] text-slate-400 mb-4">Scheduled periods for this teacher and subject</p>

            @foreach($timetableSlots as $day => $slots)
                <div class="mb-4">
                    <h3 class="text-xs font-medium text-slate-300 mb-2">{{ $day }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-950/60 text-slate-300">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Time</th>
                                    <th class="px-3 py-2 text-left font-medium">Class</th>
                                    <th class="px-3 py-2 text-left font-medium">Subject</th>
                                    <th class="px-3 py-2 text-left font-medium">Room</th>
                                    <th class="px-3 py-2 text-left font-medium">Matching Scheme Topics</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach($slots as $slot)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="px-3 py-2 text-slate-300">{{ $slot->getFormattedTime() }}</td>
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
                                                <span class="text-[11px] text-slate-500">No matching topics</span>
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
    @else
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-2">Timetable Schedule</h2>
            <p class="text-xs text-slate-400">No published timetable found for {{ $scheme->academic_year }} - {{ $scheme->term }}</p>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Scheme Sessions</h2>

        <div class="space-y-4">
            @php($grouped = $scheme->items->groupBy('week_number'))
            @foreach($grouped as $week => $items)
                <div>
                    <h3 class="text-xs font-medium text-slate-300 mb-2">Week {{ $week }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-950/60 text-slate-300">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Day</th>
                                    <th class="px-3 py-2 text-left font-medium">Topic</th>
                                    <th class="px-3 py-2 text-left font-medium">Objectives</th>
                                    <th class="px-3 py-2 text-left font-medium">Methods</th>
                                    <th class="px-3 py-2 text-left font-medium">Resources</th>
                                    <th class="px-3 py-2 text-left font-medium">Assessment</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach($items as $item)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="px-3 py-2 text-slate-300">{{ $item->day_of_week }}</td>
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-100">{{ $item->topic }}</div>
                                            @if($item->sub_topic)
                                                <div class="text-[11px] text-slate-400">{{ $item->sub_topic }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-slate-300 max-w-xs">{{ $item->objectives }}</td>
                                        <td class="px-3 py-2 text-slate-400">{{ $item->teaching_methods ?? '-' }}</td>
                                        <td class="px-3 py-2 text-slate-400">{{ $item->resources ?? '-' }}</td>
                                        <td class="px-3 py-2 text-slate-400">{{ $item->assessment ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
