@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-50 tracking-tight">Headmaster Dashboard</h1>
            <p class="text-slate-400 mt-1 text-sm">Executive school oversight, academic reports, workforce analytics & financial liquidity</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-indigo-500">
                <option>This Term (Current)</option>
                <option>Last Term</option>
                <option>Current Academic Year</option>
            </select>
        </div>
    </div>

    <!-- Quick Navigation Bar for Executive Reports -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-800/80 text-xs scrollbar-none">
        <a href="#overview-section" class="px-3.5 py-1.5 rounded-lg bg-indigo-600/30 text-indigo-200 border border-indigo-500/30 hover:bg-indigo-600/50 font-semibold transition shrink-0 flex items-center gap-1.5">
            <span>⚡ Overview</span>
        </a>
        <a href="#enrollment-reports-section" class="px-3.5 py-1.5 rounded-lg bg-slate-850 hover:bg-slate-800 text-slate-300 border border-slate-800 font-medium transition shrink-0 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            <span>Enrollment by Class, Stream & Gender</span>
        </a>
        <a href="#teachers-reports-section" class="px-3.5 py-1.5 rounded-lg bg-slate-850 hover:bg-slate-800 text-slate-300 border border-slate-800 font-medium transition shrink-0 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Teachers Report</span>
        </a>
        <a href="#employees-reports-section" class="px-3.5 py-1.5 rounded-lg bg-slate-850 hover:bg-slate-800 text-slate-300 border border-slate-800 font-medium transition shrink-0 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-purple-400"></span>
            <span>Employees Report</span>
        </a>
        <a href="#bank-balances-section" class="px-3.5 py-1.5 rounded-lg bg-slate-850 hover:bg-slate-800 text-slate-300 border border-slate-800 font-medium transition shrink-0 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Bank Balance Summary</span>
        </a>
        <a href="#projects-section" class="px-3.5 py-1.5 rounded-lg bg-slate-850 hover:bg-slate-800 text-slate-300 border border-slate-800 font-medium transition shrink-0 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            <span>Projects & Operations</span>
        </a>
    </div>

    <!-- Key Metrics / Overview Section -->
    <div id="overview-section" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 hover:border-slate-700 transition cursor-pointer group"
             onclick="LeadershipDrilldown.open('students', { status: 'active' }, 'Total Enrolled Students', 'All active students in the institution', null, '{{ route('admin.students.index') }}')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400 group-hover:text-blue-400 transition">Total Students</p>
                    <p class="text-3xl font-black text-slate-50 mt-1">{{ number_format($stats['total_students']) }}</p>
                    <p class="text-xs text-blue-400 mt-2 font-medium">Click to inspect roster ↗</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 hover:border-slate-700 transition cursor-pointer group"
             onclick="LeadershipDrilldown.open('teachers', { status: 1 }, 'Total Teaching Staff', 'All appointed academic educators', null, '{{ route('admin.teachers.index') }}')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400 group-hover:text-green-400 transition">Total Teachers</p>
                    <p class="text-3xl font-black text-slate-50 mt-1">{{ number_format($stats['total_teachers']) }}</p>
                    <p class="text-xs text-green-400 mt-2 font-medium">Click to inspect directory ↗</p>
                </div>
                <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Total Classes</p>
                    <p class="text-3xl font-black text-slate-50 mt-1">{{ number_format($stats['total_classes']) }}</p>
                    <a href="#enrollment-reports-section" class="text-xs text-purple-400 mt-2 block font-medium hover:underline">View class breakdown ↓</a>
                </div>
                <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Fees Collected</p>
                    <p class="text-3xl font-black text-slate-50 mt-1">${{ number_format($stats['total_fees_collected'], 0) }}</p>
                    <a href="#bank-balances-section" class="text-xs text-yellow-400 mt-2 block font-medium hover:underline">View bank summary ↓</a>
                </div>
                <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. ENROLLMENT REPORTS BY CLASS, GENDER & STREAM -->
    @include('admin.dashboard.partials.enrollment-report')

    <!-- 2. TEACHERS REPORTS -->
    @include('admin.dashboard.partials.teachers-report')

    <!-- 3. EMPLOYEES REPORTS -->
    @include('admin.dashboard.partials.employees-report')

    <!-- 4. BANK BALANCE SUMMARY -->
    @include('admin.dashboard.partials.bank-balance-summary')

    <!-- Project Management Overview -->
    <div id="projects-section" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Active Projects -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Active Projects</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">In Progress</span>
                    <span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['projects']['active'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Pending Approval</span>
                    <span class="bg-yellow-500/20 text-yellow-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['projects']['pending'] ?? 0 }}</span>
                </div>
                <a href="{{ route('admin.projects.index') }}" class="w-full block text-center bg-teal-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-teal-600 transition">
                    View All Projects
                </a>
            </div>
        </div>

        <!-- Project Budget Status -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Project Budget Status</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Total Budget</span>
                    <span class="text-lg font-semibold text-slate-50">${{ number_format($stats['projects']['total_budget'] ?? 0, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Spent</span>
                    <span class="text-lg font-semibold text-orange-500">${{ number_format($stats['projects']['total_spent'] ?? 0, 2) }}</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-2">
                    <div class="bg-orange-500 h-2 rounded-full" style="width: {{ ($stats['projects']['total_budget'] ?? 0) > 0 ? min(100, (($stats['projects']['total_spent'] ?? 0) / ($stats['projects']['total_budget'] ?? 1)) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        <!-- Recent Project Activity -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Recent Activity</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Completed This Month</span>
                    <span class="bg-green-500/20 text-green-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['projects']['completed_this_month'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Overdue</span>
                    <span class="bg-red-500/20 text-red-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['projects']['overdue'] ?? 0 }}</span>
                </div>
                <a href="{{ route('admin.projects.index', ['status' => 'active']) }}" class="w-full block text-center bg-indigo-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-indigo-600 transition">
                    Track Progress
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Alerts -->
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
                <a href="{{ route('admin.schemes-of-work.index') }}" class="w-full block text-center bg-cyan-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-cyan-600 transition">
                    Review Schemes
                </a>
            </div>
        </div>

        <!-- Pending Enrollments -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Pending Enrollments</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">New Applications</span>
                    <span class="bg-yellow-500/20 text-yellow-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['pending_enrollments'] }}</span>
                </div>
                <a href="{{ route('admin.enrollments.index') }}" class="w-full block text-center bg-blue-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-blue-600 transition">
                    Review Applications
                </a>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Today's Attendance</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Records Taken</span>
                    <span class="bg-green-500/20 text-green-400 px-2 py-1 rounded-full text-xs font-medium">{{ $stats['today_attendance'] }}</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: 85%"></div>
                </div>
                <a href="/admin/attendance" class="text-xs text-cyan-400 hover:underline block">View attendance records &rarr;</a>
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
</div>

<!-- Interactive Drilldown Modal Component -->
@include('admin.dashboard.partials.drilldown-modal')
@endsection
