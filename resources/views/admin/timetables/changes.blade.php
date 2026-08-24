@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.timetables.operations') }}" class="text-slate-400 hover:text-slate-200 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Timetable Change History</h1>
                <p class="text-xs text-slate-400">Auditable log of live operational changes, teacher reassignments, and cancellations.</p>
            </div>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs text-slate-300">
            Current Revision: <span class="font-bold text-indigo-400">v{{ $version['revision'] }}</span>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-800/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Rev</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Lesson Details</th>
                        <th class="px-4 py-3">Change Summary</th>
                        <th class="px-4 py-3">Reason & Notes</th>
                        <th class="px-4 py-3">Author</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($history as $item)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-400">{{ $item['created_at'] }}</td>
                            <td class="px-4 py-3 font-semibold text-indigo-400">v{{ $item['revision'] }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-slate-800 px-2 py-0.5 font-medium text-slate-200">{{ $item['change_type_label'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-200">{{ $item['class_name'] ?? '—' }} &bull; {{ $item['subject_name'] ?? '—' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $item['day_of_week'] ?? '' }} {{ $item['time'] ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-[11px]">
                                @if(!empty($item['before_state']) || !empty($item['after_state']))
                                    <div class="space-y-0.5">
                                        @if(isset($item['before_state']['teacher_name']) && isset($item['after_state']['teacher_name']))
                                            <div><span class="line-through text-rose-400">{{ $item['before_state']['teacher_name'] }}</span> &rarr; <span class="text-emerald-400 font-semibold">{{ $item['after_state']['teacher_name'] }}</span></div>
                                        @endif
                                        @if(isset($item['before_state']['room_name']) && isset($item['after_state']['room_name']))
                                            <div><span class="line-through text-rose-400">{{ $item['before_state']['room_name'] }}</span> &rarr; <span class="text-emerald-400 font-semibold">{{ $item['after_state']['room_name'] }}</span></div>
                                        @endif
                                        @if(isset($item['after_state']['substitute_name']))
                                            <div>Substitute: <span class="text-purple-400 font-semibold">{{ $item['after_state']['substitute_name'] }}</span></div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-slate-200 font-medium">{{ $item['reason'] }}</div>
                                @if($item['notes'])
                                    <div class="text-[11px] text-slate-400">{{ $item['notes'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ $item['changed_by'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No operational changes recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
