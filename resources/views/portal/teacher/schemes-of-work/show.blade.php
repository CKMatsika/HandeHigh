@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">{{ $scheme->title }}</h1>
        <p class="text-xs text-slate-400 mt-1">{{ $scheme->subject->name ?? '' }} - {{ $scheme->schoolClass->name ?? '' }} | {{ $scheme->academic_year }} {{ $scheme->term }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('teacher.schemes-of-work.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back to List
        </a>
        @if(in_array($scheme->status, ['draft', 'rejected']))
            <a href="{{ route('teacher.schemes-of-work.edit', $scheme) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Edit
            </a>
        @endif
        @if(in_array($scheme->status, ['draft', 'preview', 'rejected']))
            <form method="POST" action="{{ route('teacher.schemes-of-work.submit', $scheme) }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                    Submit for Review
                </button>
            </form>
        @endif
    </div>
</div>

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
    <div class="flex items-center gap-3 mb-4">
        <span class="inline-flex items-center rounded-full border border-{{ $scheme->status_color }}-500/30 bg-{{ $scheme->status_color }}-500/10 px-3 py-1 text-xs font-medium text-{{ $scheme->status_color }}-400 capitalize">
            {{ $scheme->status }}
        </span>
        @if($scheme->submitted_at)
            <span class="text-[11px] text-slate-400">Submitted: {{ $scheme->submitted_at->format('d M Y H:i') }}</span>
        @endif
        @if($scheme->reviewed_at)
            <span class="text-[11px] text-slate-400">Reviewed: {{ $scheme->reviewed_at->format('d M Y H:i') }}</span>
        @endif
    </div>

    @if($scheme->description)
        <p class="text-xs text-slate-300 mb-4">{{ $scheme->description }}</p>
    @endif

    @if($scheme->review_notes && $scheme->status === 'rejected')
        <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100 mb-4">
            <strong>Feedback:</strong> {{ $scheme->review_notes }}
        </div>
    @endif

    <div class="space-y-4">
        @php($grouped = $scheme->items->groupBy('week_number'))
        @foreach($grouped as $week => $items)
            <div>
                <h3 class="text-sm font-semibold text-slate-100 mb-2">Week {{ $week }}</h3>
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
@endsection
