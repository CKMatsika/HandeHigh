@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Deputy Headmaster Dashboard</h1>
            <p class="text-slate-400 mt-1">Academic performance and curriculum management</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                <option>This Term</option>
                <option>Last Term</option>
                <option>This Year</option>
            </select>
        </div>
    </div>

    <!-- Academic Performance Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Average Grade</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">{{ $stats['academic_performance']['average_grade'] }}</p>
                    <p class="text-xs text-green-400 mt-2">↑ 3% from last term</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Pass Rate</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">{{ $stats['academic_performance']['pass_rate'] }}</p>
                    <p class="text-xs text-green-400 mt-2">↑ 2% from last term</p>
                </div>
                <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Top Performers</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">{{ $stats['academic_performance']['top_performers'] }}</p>
                    <p class="text-xs text-slate-400 mt-2">Students with A+</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Total Teachers</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">{{ $stats['teacher_performance']['total_teachers'] }}</p>
                    <p class="text-xs text-green-400 mt-2">{{ $stats['teacher_performance']['active_teachers'] }} active</p>
                </div>
                <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Management Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Academic Projects -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Academic Projects</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Active Projects</span>
                    <span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['projects']['academic_active'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Completed This Term</span>
                    <span class="bg-green-500/20 text-green-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['projects']['academic_completed'] ?? 0 }}</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($stats['projects']['academic_total'] ?? 0) > 0 ? (($stats['projects']['academic_completed'] ?? 0) / ($stats['projects']['academic_total'] ?? 1)) * 100 : 0 }}%"></div>
                </div>
                <a href="{{ route('admin.projects.index', ['project_type' => 'academic']) }}" class="w-full bg-indigo-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-indigo-600 transition">
                    View Academic Projects
                </a>
            </div>
        </div>

        <!-- Infrastructure Projects -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Infrastructure Projects</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">In Progress</span>
                    <span class="bg-orange-500/20 text-orange-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['projects']['infrastructure_active'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Budget Utilization</span>
                    <span class="text-sm font-semibold text-slate-50">{{ ($stats['projects']['infrastructure_budget'] ?? 0) > 0 ? round((($stats['projects']['infrastructure_spent'] ?? 0) / ($stats['projects']['infrastructure_budget'] ?? 1)) * 100, 1) : 0 }}%</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-2">
                    <div class="bg-orange-500 h-2 rounded-full" style="width: {{ ($stats['projects']['infrastructure_budget'] ?? 0) > 0 ? min(100, (($stats['projects']['infrastructure_spent'] ?? 0) / ($stats['projects']['infrastructure_budget'] ?? 1)) * 100) : 0 }}%"></div>
                </div>
                <a href="{{ route('admin.projects.index', ['project_type' => 'infrastructure']) }}" class="w-full bg-orange-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-orange-600 transition">
                    View Infrastructure Projects
                </a>
            </div>
        </div>
    </div>

    <!-- Management Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Schemes of Work -->
        <div class="rounded-2xl border border-slate-800 bg-gradient-to-br from-cyan-500/20 via-slate-900 to-slate-950 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Schemes of Work</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Total Schemes</span>
                    <span class="bg-slate-500/20 text-slate-400 px-2 py-1 rounded-full text-xs font-medium">{{ $schemesStats['total'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Pending Review</span>
                    <span class="bg-amber-500/20 text-amber-400 px-2 py-1 rounded-full text-xs font-medium">{{ $schemesStats['submitted'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Approved</span>
                    <span class="bg-green-500/20 text-green-400 px-2 py-1 rounded-full text-xs font-medium">{{ $schemesStats['approved'] }}</span>
                </div>
                <a href="{{ route('admin.schemes-of-work.index') }}" class="w-full bg-cyan-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-cyan-600 transition">
                    Review Schemes
                </a>
            </div>
        </div>

        <!-- Teacher Performance -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Teacher Performance</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Pending Evaluations</span>
                    <span class="bg-yellow-500/20 text-yellow-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['teacher_performance']['pending_evaluations'] }}</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: 75%"></div>
                </div>
                <p class="text-xs text-slate-400">75% of teachers evaluated this term</p>
                <button class="w-full bg-blue-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-blue-600 transition">
                    View Teacher Reports
                </button>
            </div>
        </div>

        <!-- Curriculum Status -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Curriculum Status</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Total Subjects</span>
                    <span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['curriculum_status']['total_subjects'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Pending Updates</span>
                    <span class="bg-orange-500/20 text-orange-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['curriculum_status']['pending_updates'] }}</span>
                </div>
                <button class="w-full bg-slate-800 text-slate-300 rounded-lg px-4 py-2 text-sm font-medium hover:bg-slate-700 transition">
                    Manage Curriculum
                </button>
            </div>
        </div>
    </div>

    <!-- Discipline Cases -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-lg font-semibold text-slate-50 mb-4">Discipline Cases</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-slate-50">{{ $stats['discipline_cases']['total_cases'] }}</div>
                <p class="text-xs text-slate-400 mt-1">Total Cases</p>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-400">{{ $stats['discipline_cases']['resolved_cases'] }}</div>
                <p class="text-xs text-slate-400 mt-1">Resolved</p>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-400">{{ $stats['discipline_cases']['pending_cases'] }}</div>
                <p class="text-xs text-slate-400 mt-1">Pending</p>
            </div>
        </div>
    </div>

    <!-- Recent Submitted Schemes of Work -->
    @if($recentSubmittedSchemes->count() > 0)
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-50">Schemes Pending Review</h3>
            <a href="{{ route('admin.schemes-of-work.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300 transition">View All &rarr;</a>
        </div>
        <div class="space-y-3">
            @foreach($recentSubmittedSchemes as $scheme)
                <a href="{{ route('admin.schemes-of-work.show', $scheme) }}" class="flex items-center justify-between py-3 border-b border-slate-800 last:border-0 hover:bg-slate-800/40 -mx-6 px-6 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-amber-400 rounded-full"></div>
                        <div>
                            <p class="text-sm text-slate-200">{{ $scheme->teacher->full_name ?? 'Unknown' }}</p>
                            <p class="text-xs text-slate-400">{{ $scheme->title }} - {{ $scheme->subject->name ?? '' }} ({{ $scheme->schoolClass->name ?? '' }})</p>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500">{{ $scheme->submitted_at?->diffForHumans() }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-lg font-semibold text-slate-50 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <a href="/admin/subjects" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-700 transition text-center">
                Manage Subjects
            </a>
            <a href="/admin/teachers" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-700 transition text-center">
                Teacher Evaluations
            </a>
            <a href="/admin/attendance" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-700 transition text-center">
                View Attendance
            </a>
        </div>
    </div>
</div>
@endsection
