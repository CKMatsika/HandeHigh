@extends('layouts.app')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Teacher Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Welcome back, {{ $user->name }}!</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($user->hasRole('ict-coordinator'))
                <a href="{{ route('ict.dashboard') }}" class="inline-flex items-center rounded-full border border-cyan-500/40 bg-cyan-500/10 px-4 py-1.5 text-xs font-semibold text-cyan-300 hover:bg-cyan-500/20 transition">
                    ICT Coordinator Desk &rarr;
                </a>
            @endif
            <a href="{{ route('teacher.timetable.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-800 transition">
                My Timetable
            </a>
            <a href="{{ route('ict.tickets.create') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-800 transition">
                + Request ICT Help
            </a>
            <a href="{{ route('teacher.profile') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                Profile
            </a>
        </div>
    </div>

    @if($user->hasRole('ict-coordinator'))
        <div class="mb-6 rounded-2xl border border-cyan-500/30 bg-gradient-to-r from-cyan-950/40 via-slate-900 to-slate-900/90 p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-500/20 text-cyan-300 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs uppercase tracking-wider font-bold text-cyan-400">ICT Leadership Role</span>
                        <span class="px-2 py-0.5 text-[10px] rounded-full bg-cyan-500/20 text-cyan-300 font-semibold">School ERP Team Leader</span>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5">You are the school's designated ICT Coordinator & Lead for the School ERP Implementation Project.</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('ict.dashboard') }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg shadow-cyan-600/20 transition">
                    Open Coordinator Desk &rarr;
                </a>
            </div>
        </div>
    @endif

    {{-- Responsive Key Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: Class Managed (Admin) --}}
        @if($managedClasses->isNotEmpty())
            <a href="{{ route('teacher.classes.show', $managedClasses->first()->id) }}"
                class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 block hover:border-emerald-500 hover:bg-slate-800/80 hover:shadow-lg hover:shadow-emerald-500/10 transition group cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-400 group-hover:text-emerald-300 transition">Class Managed (Admin)</p>
                        <p class="text-base font-semibold text-slate-50 mt-0.5">
                            {{ $managedClasses->pluck('name')->join(', ') }}
                        </p>
                        <p class="text-[10px] text-slate-500 mt-0.5">
                            {{ $managedClasses->sum('real_student_count') }} Enrolled · Daily Register &rarr;
                        </p>
                    </div>
                    <span class="h-8 w-8 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    </span>
                </div>
            </a>
        @else
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-400">Class Managed (Admin)</p>
                        <p class="text-sm font-semibold text-slate-300 mt-0.5">None Assigned</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">No administrative register duties</p>
                    </div>
                    <span class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center shrink-0">
                        <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                    </span>
                </div>
            </div>
        @endif

        {{-- Card 2: Classes Taught --}}
        <a href="#classes-taught-section"
            class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 block hover:border-cyan-500 hover:bg-slate-800/80 hover:shadow-lg hover:shadow-cyan-500/10 transition group cursor-pointer">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 group-hover:text-cyan-300 transition">Classes Taught</p>
                    <p class="text-lg font-semibold text-slate-50 mt-0.5">{{ $teachingClasses->count() }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">
                        {{ $curricula->count() }} subject allocation(s) · View below &rarr;
                    </p>
                </div>
                <span class="h-8 w-8 rounded-full bg-cyan-500/20 flex items-center justify-center shrink-0">
                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                </span>
            </div>
        </a>

        {{-- Card 3: Active Assignments / Assessments --}}
        <a href="{{ route('admin.exams.performance-reports.my-subjects') }}"
            class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 block hover:border-amber-500 hover:bg-slate-800/80 hover:shadow-lg hover:shadow-amber-500/10 transition group cursor-pointer">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 group-hover:text-amber-300 transition">Active Assignments</p>
                    <p class="text-lg font-semibold text-slate-50 mt-0.5">{{ $assignments->count() }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">Enter marks & remarks &rarr;</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-amber-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                </span>
            </div>
        </a>

        {{-- Card 4: Avg Attendance --}}
        <a href="{{ route('teacher.timetable.index') }}"
            class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 block hover:border-cyan-500 hover:bg-slate-800/80 hover:shadow-lg hover:shadow-cyan-500/10 transition group cursor-pointer">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 group-hover:text-cyan-300 transition">Today's Attendance</p>
                    <p class="text-lg font-semibold text-slate-50 mt-0.5">
                        {{ $attendance->isNotEmpty() ? round($attendance->avg('rate')) . '%' : 'N/A' }}
                    </p>
                    <p class="text-[10px] text-slate-500 mt-0.5">View timetable & register &rarr;</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-cyan-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                </span>
            </div>
        </a>

        {{-- Card 5: Project Involvement --}}
        @if($user->hasRole('ict-coordinator'))
            <a href="{{ route('ict.dashboard') }}" class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 block hover:border-teal-500 hover:bg-slate-800/80 hover:shadow-lg hover:shadow-teal-500/10 transition group cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-400 group-hover:text-teal-300 transition">ERP Projects & Tasks</p>
                        <p class="text-lg font-semibold text-slate-50 mt-0.5">{{ $teacherProjects->count() }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">Lead implementation &rarr;</p>
                    </div>
                    <span class="h-8 w-8 rounded-full bg-teal-500/20 flex items-center justify-center">
                        <span class="h-2 w-2 rounded-full bg-teal-400"></span>
                    </span>
                </div>
            </a>
        @else
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-400">Project Involvement</p>
                        <p class="text-lg font-semibold text-slate-50 mt-0.5">{{ $teacherProjects->count() }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">Active school projects</p>
                    </div>
                    <span class="h-8 w-8 rounded-full bg-teal-500/20 flex items-center justify-center">
                        <span class="h-2 w-2 rounded-full bg-teal-400"></span>
                    </span>
                </div>
            </div>
        @endif

        {{-- Card 6: Schemes of Work --}}
        <a href="{{ route('teacher.schemes-of-work.index') }}"
            class="rounded-2xl border border-slate-800 bg-gradient-to-br from-cyan-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-cyan-500 hover:bg-slate-800 hover:shadow-lg hover:shadow-cyan-500/10 group cursor-pointer">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 group-hover:text-cyan-300 transition">Schemes of Work</p>
                    <p class="text-lg font-semibold text-slate-50 mt-0.5">{{ $schemesCount }}</p>
                    <div class="flex gap-1.5 mt-1 flex-wrap">
                        @if($draftSchemesCount > 0)
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-500/20 text-slate-400">{{ $draftSchemesCount }} draft</span>
                        @endif
                        @if($submittedSchemesCount > 0)
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-amber-500/20 text-amber-400">{{ $submittedSchemesCount }} submitted</span>
                        @endif
                        @if($approvedSchemesCount > 0)
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-emerald-500/20 text-emerald-400">{{ $approvedSchemesCount }} approved</span>
                        @endif
                    </div>
                </div>
                <span class="h-8 w-8 rounded-full bg-cyan-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                </span>
            </div>
            <div class="mt-2 text-xs text-cyan-400 opacity-0 group-hover:opacity-100 transition-opacity">
                Manage schemes &rarr;
            </div>
        </a>

        {{-- Card 7: Student Reports --}}
        <a href="{{ route('admin.exams.performance-reports.my-subjects') }}"
            class="rounded-2xl border border-slate-800 bg-gradient-to-br from-purple-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-purple-500 hover:bg-slate-800 hover:shadow-lg hover:shadow-purple-500/10 group cursor-pointer sm:col-span-2 lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 group-hover:text-purple-300 transition">Student Performance Reports</p>
                    <p class="text-lg font-semibold text-slate-50 mt-0.5">End-of-Term Marks & Remarks</p>
                    <p class="text-[10px] text-slate-400 mt-1">Enter individual student evaluation marks across all your taught subjects</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-purple-500/20 flex items-center justify-center shrink-0">
                    <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                </span>
            </div>
            <div class="mt-2 text-xs text-purple-400 opacity-0 group-hover:opacity-100 transition-opacity">
                Enter student marks &rarr;
            </div>
        </a>
    </div>

    {{-- Today's Schedule & Career Guidance & Announcements --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Today's Schedule -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm h-full flex flex-col">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                        <h2 class="text-sm font-medium text-slate-50">Today's Schedule ({{ now()->englishDayOfWeek }})</h2>
                    </div>
                    <a href="{{ route('teacher.timetable.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300 transition">Full Timetable &rarr;</a>
                </div>
                <div class="p-4 space-y-2 flex-1">
                    @forelse($schedule as $period)
                        <a href="{{ route('teacher.timetable.index') }}" class="block p-3 rounded-xl border border-slate-700 bg-slate-800/50 hover:bg-slate-800 hover:border-cyan-500/40 transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-slate-50 font-mono">{{ $period->time }}</p>
                                    <p class="text-xs text-cyan-300 font-medium mt-1">{{ $period->class }} — {{ $period->subject }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $period->room }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-8">
                            <svg class="w-8 h-8 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-xs text-slate-400">No periods scheduled for today</p>
                            <a href="{{ route('teacher.timetable.index') }}" class="mt-2 inline-flex items-center text-xs text-cyan-400 hover:text-cyan-300">
                                View full weekly timetable &rarr;
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Career Guidance -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm h-full flex flex-col">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                        <h2 class="text-sm font-medium text-slate-50">Career Guidance</h2>
                    </div>
                    <a href="{{ route('teacher.career-guidance.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">Manage &rarr;</a>
                </div>
                <div class="p-4 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="text-center my-3">
                            <p class="text-3xl font-bold {{ $careerAssessmentsCount > 0 ? 'text-emerald-400' : 'text-slate-400' }}">{{ $careerAssessmentsCount }}</p>
                            <p class="text-xs text-slate-400 mt-1">students assessed</p>
                        </div>
                        @if($careerStudentTotal > 0)
                            <div class="w-full bg-slate-800 rounded-full h-2 mb-2">
                                <div class="bg-indigo-500 h-2 rounded-full transition-all" style="width: {{ min(100, round(($careerAssessedStudents / $careerStudentTotal) * 100)) }}%"></div>
                            </div>
                            <p class="text-[10px] text-slate-500 text-center">{{ $careerAssessedStudents }} of {{ $careerStudentTotal }} total students</p>
                        @endif
                    </div>
                    <div class="mt-4 space-y-2">
                        <a href="{{ route('teacher.career-guidance.index') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-800/40 hover:bg-slate-800 transition text-xs">
                            <span class="text-slate-300">View Student Assessments</span>
                            <span class="text-indigo-400 font-semibold">&rarr;</span>
                        </a>
                        <a href="{{ route('teacher.career-guidance.index') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-800/40 hover:bg-slate-800 transition text-xs">
                            <span class="text-slate-300">Generate Guidance Reports</span>
                            <span class="text-indigo-400 font-semibold">&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Announcements -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm h-full flex flex-col">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <h2 class="text-sm font-medium text-slate-50">Staff Announcements</h2>
                    </div>
                    <span class="text-[10px] text-slate-500">{{ $announcements->count() }} active</span>
                </div>
                <div class="p-4 space-y-2.5 flex-1">
                    @forelse($announcements as $announcement)
                        <div class="p-3 rounded-xl border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between gap-2 mb-1.5">
                                <p class="text-xs font-semibold text-slate-100">{{ $announcement->title }}</p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ 
                                    $announcement->priority === 'high' ? 'bg-red-500/20 text-red-400' : 
                                    ($announcement->priority === 'medium' ? 'bg-amber-500/20 text-amber-400' : 
                                    'bg-slate-700 text-slate-400') }}">
                                    {{ ucfirst($announcement->priority) }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-300">{{ $announcement->message }}</p>
                            <p class="text-[10px] text-slate-500 mt-2">{{ $announcement->date->format('M j, Y h:i A') }}</p>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <svg class="w-8 h-8 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <p class="text-xs text-slate-400">No staff announcements</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">School broadcasts and staff notices will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Classes Managed & Classes Taught Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" id="classes-taught-section">
        <!-- 1. Administrative Class (Class Teacher) -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <h2 class="text-sm font-semibold text-slate-50">Class Managed (Administrative)</h2>
                </div>
                <span class="text-[10px] text-emerald-400 font-medium px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                    Class Teacher Register
                </span>
            </div>
            <div class="p-4 space-y-3">
                @forelse($managedClasses as $class)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl border border-slate-700/60 bg-slate-800/60 hover:bg-slate-800/90 transition">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-xs font-bold text-slate-100">{{ $class->name }}</p>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold">
                                    Grade {{ $class->grade }}
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">
                                <strong class="text-slate-200">{{ $class->real_student_count ?? ($class->enrollments_count ?? 0) }} Enrolled Students</strong> · Year {{ $class->academic_year ?? date('Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('teacher.dashboard', ['class_id' => $class->id]) }}#students-section"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/20 transition">
                                Filter Students &darr;
                            </a>
                            <a href="{{ route('teacher.classes.show', $class->id) }}"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700 transition">
                                Open Roster &rarr;
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-6 rounded-xl border border-dashed border-slate-800 text-center">
                        <p class="text-xs text-slate-400 font-medium">No administrative class assigned</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">You do not currently have class teacher register responsibilities.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 2. Classes & Subjects Taught (Subject Specialisations) -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                    <h2 class="text-sm font-semibold text-slate-50">Classes & Subjects Taught</h2>
                </div>
                <span class="text-[10px] text-cyan-300 font-medium px-2.5 py-0.5 rounded-full bg-cyan-500/10 border border-cyan-500/20">
                    {{ $teachingClasses->count() }} Classes · {{ $curricula->count() }} Allocations
                </span>
            </div>
            <div class="p-4 space-y-2.5 max-h-96 overflow-y-auto">
                @forelse($curricula as $curr)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-xl border border-slate-800 bg-slate-800/40 hover:bg-slate-800/80 transition">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-100">{{ $curr->class->name ?? 'Class #' . $curr->class_id }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-cyan-500/20 text-cyan-300 font-bold border border-cyan-500/30">
                                    {{ $curr->subject->name ?? 'Subject' }}
                                </span>
                                @if($curr->class && $curr->class->grade)
                                    <span class="text-[10px] text-slate-400 font-medium">Grade {{ $curr->class->grade }}</span>
                                @endif
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">
                                {{ $curr->weekly_periods }} periods/wk · 
                                <strong class="text-slate-200">{{ $curr->class?->real_student_count ?? ($curr->class?->enrollments_count ?? 0) }} students</strong> · 
                                {{ $curr->term ?? 'All Terms' }} ({{ $curr->academic_year }})
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="{{ route('teacher.dashboard', ['class_id' => $curr->class_id]) }}#students-section"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-cyan-500/10 text-cyan-300 border border-cyan-500/30 hover:bg-cyan-500/20 transition">
                                Students &darr;
                            </a>
                            <a href="{{ route('teacher.classes.show', $curr->class_id) }}"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700 transition">
                                Roster &rarr;
                            </a>
                            <a href="{{ route('admin.exams.performance-reports.my-subjects') }}"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-purple-500/10 text-purple-300 border border-purple-500/20 hover:bg-purple-500/20 transition">
                                Marks
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-6 rounded-xl border border-dashed border-slate-800 text-center">
                        <p class="text-xs text-slate-400 font-medium">No subject teaching classes allocated yet</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">Your subject allocations will appear here once assigned by administration.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Students in My Classes (Interactive Filterable List) --}}
    <div class="mt-6" id="students-section">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
            {{-- Header --}}
            <div class="px-5 py-4 border-b border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-violet-400 shrink-0"></span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-50 flex items-center gap-2">
                            <span>{{ $selectedClass ? 'Students in ' . $selectedClass->name : 'Students in My Classes' }}</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-violet-500/20 text-violet-300 font-bold border border-violet-500/30">
                                {{ $studentsCount }} {{ Str::plural('student', $studentsCount) }}
                            </span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            @if($selectedClass)
                                Filtered strictly to {{ $selectedClass->name }} (Grade {{ $selectedClass->grade ?? 'N/A' }}).
                            @else
                                Real students enrolled across all your assigned teaching and managed classes.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($selectedClass)
                        <a href="{{ route('teacher.dashboard') }}#students-section"
                            class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                            &times; View All Classes
                        </a>
                        <a href="{{ route('teacher.classes.show', $selectedClass->id) }}"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white transition shadow-sm">
                            Open {{ $selectedClass->name }} Full Roster &rarr;
                        </a>
                    @endif
                </div>
            </div>

            {{-- Interactive Class Filter Pills --}}
            @if($allClasses->count() > 0)
                <div class="px-5 py-3 border-b border-slate-800 bg-slate-950/40 flex items-center gap-2 overflow-x-auto">
                    <span class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold shrink-0">Filter By Class:</span>
                    <a href="{{ route('teacher.dashboard') }}#students-section"
                        class="px-3 py-1 rounded-full text-xs font-semibold shrink-0 transition {{ !$selectedClassId ? 'bg-violet-600 text-white shadow-sm shadow-violet-600/30' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        All Classes ({{ $careerStudentTotal }})
                    </a>
                    @foreach($allClasses as $c)
                        <a href="{{ route('teacher.dashboard', ['class_id' => $c->id]) }}#students-section"
                            class="px-3 py-1 rounded-full text-xs font-semibold shrink-0 transition {{ $selectedClassId === $c->id ? 'bg-cyan-600 text-white shadow-sm shadow-cyan-600/30 ring-2 ring-cyan-400' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            {{ $c->name }} ({{ $c->real_student_count }})
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Live Search Input --}}
            <div class="p-4 border-b border-slate-800/60 bg-slate-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="teacherStudentSearch" onkeyup="filterTeacherStudents()" placeholder="Search students in class..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-800 border border-slate-700 text-slate-200 placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                </div>
                <div class="text-xs text-slate-400">
                    Showing <span id="visibleStudentCount" class="text-slate-200 font-semibold">{{ $students->count() }}</span> students
                </div>
            </div>

            {{-- Student Cards Grid --}}
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5" id="studentCardsGrid">
                    @forelse($students as $student)
                        <div class="p-3.5 rounded-xl border border-slate-800 bg-slate-800/50 hover:border-slate-700 hover:bg-slate-800/80 transition student-card shadow-sm"
                            data-search="{{ strtolower($student->first_name . ' ' . $student->last_name . ' ' . ($student->admission_number ?? '') . ' ' . ($student->registration_number ?? '') . ' ' . ($student->class_name ?? '')) }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-100 truncate">
                                        {{ $student->first_name }} {{ $student->last_name }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="text-cyan-300 font-semibold">{{ $student->class_name ?? ($selectedClass->name ?? 'Assigned') }}</span>
                                        @if($student->grade)
                                            <span>· Grade {{ $student->grade }}</span>
                                        @endif
                                    </p>
                                    @if($student->admission_number || $student->registration_number)
                                        <p class="text-[10px] font-mono text-slate-500 mt-1">
                                            {{ $student->admission_number ?? $student->registration_number }}
                                        </p>
                                    @endif
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full capitalize shrink-0 font-medium {{ 
                                    $student->status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-700 text-slate-400' 
                                }}">
                                    {{ $student->status }}
                                </span>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-800 flex items-center justify-between text-[10px]">
                                <span class="text-slate-400 capitalize">
                                    {{ $student->gender ?? 'Student' }} · {{ $student->is_boarding ? 'Boarding' : 'Day Scholar' }}
                                </span>
                                <a href="{{ route('admin.exams.performance-reports.my-subjects') }}" class="text-cyan-400 hover:text-cyan-300 font-semibold transition">
                                    Enter Marks &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <svg class="w-10 h-10 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <p class="text-xs font-semibold text-slate-300">
                                No students found in {{ $selectedClass ? $selectedClass->name : 'your assigned classes' }}
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Students enrolled in your assigned classes will filter through here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Schemes of Work --}}
    <div class="mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                    <h2 class="text-sm font-semibold text-slate-50">Recent Schemes of Work</h2>
                </div>
                <a href="{{ route('teacher.schemes-of-work.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300 transition font-medium">View All &rarr;</a>
            </div>
            <div class="p-4">
                @forelse($recentSchemes as $scheme)
                    <a href="{{ route('teacher.schemes-of-work.show', $scheme) }}" class="flex items-center justify-between py-3 border-b border-slate-800 last:border-0 hover:bg-slate-800/40 -mx-4 px-4 transition">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $scheme->title }}</p>
                            <p class="text-[10px] text-slate-400 mt-1">{{ $scheme->subject->name ?? '' }} — {{ $scheme->schoolClass->name ?? '' }}</p>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-{{ $scheme->status_color }}-500/20 text-{{ $scheme->status_color }}-400 capitalize">
                            {{ $scheme->status }}
                        </span>
                    </a>
                @empty
                    <div class="text-center py-6">
                        <p class="text-xs text-slate-500">No schemes of work yet</p>
                        <a href="{{ route('teacher.schemes-of-work.create') }}" class="mt-2 inline-flex items-center rounded-full bg-cyan-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-cyan-500 transition">
                            Create your first scheme
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Attendance Summary --}}
    <div class="mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <h2 class="text-sm font-semibold text-slate-50">Today's Attendance Summary</h2>
                </div>
                <a href="{{ route('teacher.timetable.index') }}" class="text-xs text-emerald-400 hover:text-emerald-300 transition font-medium">
                    Attendance & Timetable &rarr;
                </a>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($attendance as $record)
                        <div class="p-3.5 rounded-xl border border-slate-700 bg-slate-800/50">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-semibold text-slate-50">{{ $record->class }}</p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ 
                                    $record->rate >= 90 ? 'bg-emerald-500/20 text-emerald-400' : 
                                    ($record->rate >= 75 ? 'bg-amber-500/20 text-amber-400' : 
                                    'bg-red-500/20 text-red-400') }}">
                                    {{ $record->rate }}%
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <p>Present: <strong class="text-slate-200">{{ $record->present }}</strong></p>
                                <p>Total Marked: <strong class="text-slate-200">{{ $record->total }}</strong></p>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-6">
                            <p class="text-xs text-slate-400">No attendance marked for today yet</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">Attendance records taken today will summarize here automatically.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Client-side Search Script --}}
    <script>
        function filterTeacherStudents() {
            const query = document.getElementById('teacherStudentSearch').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.student-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const searchData = card.getAttribute('data-search') || '';
                if (!query || searchData.includes(query)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const countEl = document.getElementById('visibleStudentCount');
            if (countEl) {
                countEl.textContent = visibleCount;
            }
        }
    </script>
@endsection
