@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Teacher Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Welcome back, {{ $user->name }}!</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('teacher.profile') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Profile</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Assigned Classes</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $teacherClasses->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-violet-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-violet-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Subjects Teaching</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $curricula->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Active Assignments</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $assignments->where('due_date', '>', now())->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-amber-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Avg Attendance</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $attendance->isNotEmpty() ? round($attendance->avg('rate')) : 0 }}%</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-cyan-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Project Involvement</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $teacherProjects->count() ?? 0 }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-teal-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-teal-400"></span>
                </span>
            </div>
        </div>

        <a href="{{ route('teacher.schemes-of-work.index') }}" class="rounded-2xl border border-slate-800 bg-gradient-to-br from-cyan-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-cyan-500 hover:bg-slate-800 hover:shadow-lg hover:shadow-cyan-500/10 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Schemes of Work</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $schemesCount }}</p>
                    <div class="flex gap-2 mt-1">
                        @if($draftSchemesCount > 0)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-500/20 text-slate-400">{{ $draftSchemesCount }} draft</span>
                        @endif
                        @if($submittedSchemesCount > 0)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500/20 text-amber-400">{{ $submittedSchemesCount }} submitted</span>
                        @endif
                        @if($approvedSchemesCount > 0)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">{{ $approvedSchemesCount }} approved</span>
                        @endif
                    </div>
                </div>
                <span class="h-8 w-8 rounded-full bg-cyan-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                </span>
            </div>
            <div class="mt-2 text-xs text-cyan-400 opacity-0 group-hover:opacity-100 transition-opacity">
                View all schemes &rarr;
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Today's Schedule -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Today's Schedule</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">Full Schedule</a>
                </div>
                <div class="p-4 space-y-2">
                    @forelse($schedule as $period)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-50">{{ $period->time }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $period->class }} - {{ $period->subject }}</p>
                                    <p class="text-[10px] text-slate-500 mt-2">{{ $period->room }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No classes scheduled</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Career Guidance -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Career Guidance</h2>
                    <a href="{{ route('teacher.career-guidance.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">Manage</a>
                </div>
                <div class="p-4">
                    <div class="text-center mb-4">
                        <p class="text-2xl font-bold {{ $careerAssessmentsCount > 0 ? 'text-emerald-400' : 'text-slate-500' }}">{{ $careerAssessmentsCount }}</p>
                        <p class="text-[10px] text-slate-400">students assessed</p>
                    </div>
                    @if($careerStudentTotal > 0)
                        <div class="w-full bg-slate-800 rounded-full h-2 mb-3">
                            <div class="bg-indigo-500 h-2 rounded-full transition-all" style="width: {{ min(100, round(($careerAssessedStudents / $careerStudentTotal) * 100)) }}%"></div>
                        </div>
                        <p class="text-[10px] text-slate-500 text-center">{{ $careerAssessedStudents }}/{{ $careerStudentTotal }} students</p>
                    @endif
                    <div class="mt-3 space-y-2">
                        <a href="{{ route('teacher.career-guidance.index') }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-800/50 transition">
                            <span class="text-[10px] text-slate-400">View Student Assessments</span>
                            <span class="text-xs text-indigo-400">&rarr;</span>
                        </a>
                        <a href="{{ route('teacher.career-guidance.index') }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-800/50 transition">
                            <span class="text-[10px] text-slate-400">Generate New Assessments</span>
                            <span class="text-xs text-indigo-400">&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Staff Announcements</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($announcements as $announcement)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between mb-2">
                                <p class="text-xs font-medium text-slate-50">{{ $announcement->title }}</p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ 
                                    $announcement->priority === 'high' ? 'bg-red-500/20 text-red-400' : 
                                    ($announcement->priority === 'medium' ? 'bg-amber-500/20 text-amber-400' : 
                                    'bg-slate-700 text-slate-400') }}">
                                    {{ ucfirst($announcement->priority) }}
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-400">{{ $announcement->message }}</p>
                            <p class="text-[10px] text-slate-500 mt-2">{{ $announcement->date->format('M j, Y h:i A') }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No announcements</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Assigned Classes & Students -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Assigned Classes -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Assigned Classes</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">Manage Classes</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($teacherClasses as $class)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $class->name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $class->grade }} • {{ $class->academic_year }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-slate-50">{{ $class->students_count ?? 0 }}</p>
                            <p class="text-[10px] text-slate-400">students</p>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No classes assigned</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Students -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Recent Students</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All Students</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($students as $student)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $student->first_name }} {{ $student->last_name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $student->grade }} • {{ $student->class_name ?? 'No Class' }}</p>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">
                            {{ ucfirst($student->status) }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No students found</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Schemes of Work -->
    <div class="mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Recent Schemes of Work</h2>
                <a href="{{ route('teacher.schemes-of-work.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
            </div>
            <div class="p-4">
                @forelse($recentSchemes as $scheme)
                    <a href="{{ route('teacher.schemes-of-work.show', $scheme) }}" class="flex items-center justify-between py-3 border-b border-slate-800 last:border-0 hover:bg-slate-800/40 -mx-4 px-4 transition">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $scheme->title }}</p>
                            <p class="text-[10px] text-slate-400 mt-1">{{ $scheme->subject->name ?? '' }} - {{ $scheme->schoolClass->name ?? '' }}</p>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-{{ $scheme->status_color }}-500/20 text-{{ $scheme->status_color }}-400 capitalize">
                            {{ $scheme->status }}
                        </span>
                    </a>
                @empty
                    <div class="text-center py-6">
                        <p class="text-xs text-slate-500">No schemes of work yet</p>
                        <a href="{{ route('teacher.schemes-of-work.create') }}" class="mt-2 inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                            Create your first scheme
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Attendance Summary -->
    <div class="mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Today's Attendance Summary</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View Full Report</a>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($attendance as $record)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-medium text-slate-50">{{ $record->class }}</p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ 
                                    $record->rate >= 90 ? 'bg-emerald-500/20 text-emerald-400' : 
                                    ($record->rate >= 75 ? 'bg-amber-500/20 text-amber-400' : 
                                    'bg-red-500/20 text-red-400') }}">
                                    {{ $record->rate }}%
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] text-slate-400">Present: {{ $record->present }}</p>
                                <p class="text-[10px] text-slate-400">Total: {{ $record->total }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4 col-span-full">No attendance records for today</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
