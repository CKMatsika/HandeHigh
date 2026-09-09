@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-slate-50 tracking-tight">Preview: {{ $scheme->title }}</h1>
            <span class="inline-flex items-center rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-0.5 text-xs font-semibold text-blue-400 capitalize">
                Preview Mode
            </span>
        </div>
        <p class="text-xs text-slate-400 mt-1">Review your MoPSE Scheme-Cum Plan before submitting to administration</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('teacher.schemes-of-work.print', $scheme) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2 text-xs font-semibold text-indigo-300 hover:bg-indigo-500/20 hover:text-white transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Preview
        </a>
        <a href="{{ route('teacher.schemes-of-work.edit', $scheme) }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Edit Scheme
        </a>
        <form method="POST" action="{{ route('teacher.schemes-of-work.submit', $scheme) }}" class="inline">
            @csrf
            <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white hover:bg-emerald-500 shadow-lg shadow-emerald-500/20 transition">
                Submit for Review
            </button>
        </form>
    </div>
</div>

<div class="space-y-6 text-xs text-slate-100">
    <!-- MoPSE Official Scheme Card Header -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 backdrop-blur-xl p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row items-center justify-between border-b border-slate-800 pb-4 gap-2">
            <div class="text-center sm:text-left">
                <span class="text-[11px] font-bold tracking-widest text-indigo-400 uppercase">Ministry of Primary and Secondary Education (MoPSE)</span>
                <h2 class="text-base font-bold text-slate-100">{{ $scheme->school->name ?? 'Hande High School' }}</h2>
                <div class="text-[11px] text-slate-400">Teacher: <span class="text-slate-200 font-medium">{{ $scheme->teacher->full_name ?? Auth::user()->name }}</span></div>
            </div>
            <div class="text-right flex flex-col sm:items-end">
                <span class="text-[11px] font-semibold text-slate-300">Level: <span class="text-indigo-400 font-bold">{{ $scheme->schoolClass->name ?? '-' }}</span></span>
                <span class="text-[11px] text-slate-400">Academic Year: {{ $scheme->academic_year }} &bull; {{ $scheme->term }}</span>
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
    </div>

    <!-- MoPSE 8-Column Scheme-Cum Plan Matrix Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 backdrop-blur-xl p-5 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-100 uppercase tracking-wider flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                Scheme-Cum Plan Grid (8 Columns)
            </h2>
            <span class="text-[11px] text-slate-400">{{ $scheme->items->count() }} Teaching Units / Weeks</span>
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
                            <td colspan="8" class="text-center py-6 text-slate-500 text-xs">No scheme units recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
