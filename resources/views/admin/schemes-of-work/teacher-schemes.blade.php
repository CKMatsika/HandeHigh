@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">{{ $teacher->full_name }} - Schemes of Work</h1>
        <p class="text-xs text-slate-400 mt-1">{{ $teacher->specialization ?? '' }}</p>
    </div>
    <a href="{{ route('admin.schemes-of-work.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
        Back to Teachers
    </a>
</div>

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
    @if($schemes->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Title</th>
                        <th class="px-4 py-3 text-left font-medium">Subject</th>
                        <th class="px-4 py-3 text-left font-medium">Class</th>
                        <th class="px-4 py-3 text-left font-medium">Year / Term</th>
                        <th class="px-4 py-3 text-left font-medium">Sessions</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($schemes as $scheme)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-100">{{ $scheme->title }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $scheme->subject->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $scheme->schoolClass->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $scheme->academic_year }} / {{ $scheme->term }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-slate-700 px-2 py-0.5 text-[11px] font-medium text-slate-200">
                                    {{ $scheme->items->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border border-{{ $scheme->status_color }}-500/30 bg-{{ $scheme->status_color }}-500/10 px-2 py-0.5 text-[11px] font-medium text-{{ $scheme->status_color }}-400 capitalize">
                                    {{ $scheme->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.schemes-of-work.show', $scheme) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $schemes->links() }}</div>
    @else
        <div class="text-center py-12">
            <p class="text-sm text-slate-400">No schemes of work found for this teacher.</p>
        </div>
    @endif
</div>
@endsection
