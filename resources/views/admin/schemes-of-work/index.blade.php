@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">Schemes of Work (MoPSE Zimbabwe Standard)</h1>
        <p class="text-xs text-slate-400 mt-1">Supervise and review teacher Scheme-Cum Plans across all departments</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('teacher.schemes-of-work.create') }}" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 shadow-md shadow-indigo-500/20 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            + Create New Scheme
        </a>
    </div>
</div>

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
    @if($teachers->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Teacher</th>
                        <th class="px-4 py-3 text-left font-medium">Specialization</th>
                        <th class="px-4 py-3 text-left font-medium">Total Schemes</th>
                        <th class="px-4 py-3 text-left font-medium">Submitted</th>
                        <th class="px-4 py-3 text-left font-medium">Approved</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($teachers as $teacher)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-100">{{ $teacher->full_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $teacher->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $teacher->specialization ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-slate-700 px-2 py-0.5 text-[11px] font-medium text-slate-200">
                                    {{ $teacher->total_schemes }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-amber-500/20 px-2 py-0.5 text-[11px] font-medium text-amber-400">
                                    {{ $teacher->submitted_schemes }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-emerald-500/20 px-2 py-0.5 text-[11px] font-medium text-emerald-400">
                                    {{ $teacher->approved_schemes }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.schemes-of-work.teacher', $teacher) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">
                                    View Schemes
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-sm text-slate-400">No teachers found.</p>
        </div>
    @endif
</div>
@endsection
