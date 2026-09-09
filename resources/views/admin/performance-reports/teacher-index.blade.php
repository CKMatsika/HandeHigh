@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header & Navigation -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/80 p-6 rounded-2xl border border-slate-800 backdrop-blur-xl shadow-xl">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-400 mb-1">
                <span>Academic</span>
                <span>/</span>
                <span>Exams & Assignments</span>
                <span>/</span>
                <span class="text-slate-400">End-of-Term Reports</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-3">
                <span>My Subject Performance Reports</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                    Teacher Portal
                </span>
            </h1>
            <p class="text-sm text-slate-400 mt-1">Capture marks, calculate automatic grades, and submit subject evaluations for assigned classes.</p>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('admin.exams.performance-reports.my-subjects') }}" class="flex items-center gap-2 flex-wrap">
            <select name="academic_year" class="bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2 font-medium focus:ring-2 focus:ring-indigo-500" onchange="this.form.submit()">
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" {{ $academicYear == $year ? 'selected' : '' }}>Year {{ $year }}</option>
                @endforeach
            </select>

            <select name="term" class="bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2 font-medium focus:ring-2 focus:ring-indigo-500" onchange="this.form.submit()">
                @foreach($terms as $t)
                    <option value="{{ $t }}" {{ $term == $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Examination Timetable Completion Banner -->
    <div class="p-5 rounded-2xl border {{ $releaseStatus['is_available'] ? 'bg-emerald-950/30 border-emerald-500/30' : 'bg-amber-950/30 border-amber-500/30' }} backdrop-blur-xl">
        <div class="flex items-start gap-3.5">
            <div class="p-2.5 rounded-xl {{ $releaseStatus['is_available'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                @if($releaseStatus['is_available'])
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @endif
            </div>
            <div>
                <h2 class="text-sm font-bold {{ $releaseStatus['is_available'] ? 'text-emerald-300' : 'text-amber-300' }}">
                    {{ $releaseStatus['is_available'] ? 'End-of-Term Reports Active & Available' : 'Examination Period Active / Reports Awaiting Release' }}
                </h2>
                <p class="text-xs text-slate-300 mt-0.5">{{ $releaseStatus['message'] }}</p>
            </div>
        </div>
    </div>

    <!-- Assigned Classes & Subjects Grid -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white tracking-tight">Your Assigned Teaching Allocations ({{ count($assignments) }})</h2>
        </div>

        @if(empty($assignments))
            <div class="p-12 text-center bg-slate-900/60 rounded-2xl border border-slate-800">
                <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-500 mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-300">No Teaching Assignments Found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">You have no curriculum allocations assigned for {{ $term }} — {{ $academicYear }}. Contact the Academic Administrator if this is unexpected.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($assignments as $item)
                    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 flex flex-col justify-between hover:border-indigo-500/50 transition duration-200 shadow-lg group">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        {{ $item['class']->grade ?? 'Class' }}
                                    </span>
                                    <h3 class="text-lg font-black text-white tracking-tight mt-1.5 group-hover:text-indigo-300 transition">
                                        {{ $item['class']->name }}
                                    </h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-indigo-400 bg-slate-950 px-2.5 py-1 rounded-lg border border-slate-800">
                                        {{ $item['subject']->name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="space-y-1.5 pt-2">
                                <div class="flex justify-between text-xs font-semibold">
                                    <span class="text-slate-400">Progress</span>
                                    <span class="{{ $item['completion_percentage'] == 100 ? 'text-emerald-400' : 'text-indigo-400' }}">
                                        {{ $item['completed_students'] }} / {{ $item['total_students'] }} Students ({{ $item['completion_percentage'] }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-800">
                                    <div class="h-2 rounded-full transition-all duration-500 {{ $item['completion_percentage'] == 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $item['completion_percentage'] }}%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="pt-5 mt-4 border-t border-slate-800/80 flex items-center justify-between">
                            <span class="text-xs font-medium {{ $item['pending_students'] > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                                {{ $item['pending_students'] > 0 ? $item['pending_students'] . ' Outstanding' : 'All Complete ✓' }}
                            </span>

                            @if($releaseStatus['is_available'] || auth()->user()->hasAnyRole(['super-admin', 'school-admin']))
                                <a href="{{ route('admin.exams.performance-reports.teacher-entry', [$item['class']->id, $item['subject']->id, 'academic_year' => $academicYear, 'term' => $term]) }}" 
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-lg shadow-indigo-600/20">
                                    <span>Enter / Edit Marks</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            @else
                                <span class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 text-slate-500 cursor-not-allowed">
                                    Locked (Exam Active)
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
