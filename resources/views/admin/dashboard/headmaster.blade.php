@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Headmaster Dashboard</h1>
            <p class="text-slate-400 mt-1">School overview and management insights</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                <option>This Term</option>
                <option>Last Term</option>
                <option>This Year</option>
            </select>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Total Students</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">{{ $stats['total_students'] }}</p>
                    <p class="text-xs text-green-400 mt-2">↑ 5% from last term</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Total Teachers</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">{{ $stats['total_teachers'] }}</p>
                    <p class="text-xs text-green-400 mt-2">↑ 2 new this month</p>
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
                    <p class="text-2xl font-bold text-slate-50 mt-1">{{ $stats['total_classes'] }}</p>
                    <p class="text-xs text-slate-400 mt-2">Active this term</p>
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
                    <p class="text-2xl font-bold text-slate-50 mt-1">${{ number_format($stats['total_fees_collected'], 0) }}</p>
                    <p class="text-xs text-green-400 mt-2">↑ 12% from last month</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Management Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                <a href="{{ route('admin.projects.index') }}" class="w-full bg-teal-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-teal-600 transition">
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
                <a href="{{ route('admin.projects.index', ['status' => 'active']) }}" class="w-full bg-indigo-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-indigo-600 transition">
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
                <a href="{{ route('admin.schemes-of-work.index') }}" class="w-full bg-cyan-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-cyan-600 transition">
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
                <button class="w-full bg-blue-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-blue-600 transition">
                    Review Applications
                </button>
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
                <p class="text-xs text-slate-400">85% attendance rate</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <a href="/admin/enrollments" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-2 text-sm hover:bg-slate-700 transition">
                    Manage Enrollments
                </a>
                <a href="/admin/attendance" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-2 text-sm hover:bg-slate-700 transition">
                    View Attendance
                </a>
                <a href="/admin/communication" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-2 text-sm hover:bg-slate-700 transition">
                    Send Communications
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-lg font-semibold text-slate-50 mb-4">Recent Activities</h3>
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                <span class="text-sm text-slate-300">New enrollment application received</span>
                <span class="text-xs text-slate-500 ml-auto">2 hours ago</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-sm text-slate-300">Teacher evaluation completed</span>
                <span class="text-xs text-slate-500 ml-auto">5 hours ago</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                <span class="text-sm text-slate-300">Monthly fee collection report generated</span>
                <span class="text-xs text-slate-500 ml-auto">1 day ago</span>
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
@endsection
