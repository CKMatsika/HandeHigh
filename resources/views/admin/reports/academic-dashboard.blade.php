@extends('layouts.app')

@section('title', 'Academic & Operations Dashboard - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Academic & Operations Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Real-time academic performance, enrollment distribution, and attendance overview for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.reports.enrollment-summary') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">Enrollment Summary</a>
            <a href="{{ route('admin.reports.academic-performance') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition">Performance Summary</a>
            <a href="{{ route('admin.reports.attendance-summary') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium shadow-sm transition">Attendance Summary</a>
        </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total Enrollment -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Enrolled</p>
                <span class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($kpis['total_students']) }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                <span class="text-blue-600 font-medium">{{ $kpis['male_students'] }} Boys</span> &bull; 
                <span class="text-purple-600 font-medium">{{ $kpis['female_students'] }} Girls ({{ $kpis['female_pct'] }}%)</span>
            </p>
        </div>

        <!-- Attendance Rate (30 Days) -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Attendance Rate</p>
                <span class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $kpis['attendance_rate'] }}%</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($kpis['attendance_records_count']) }} logs in last 30 days</p>
        </div>

        <!-- Academic Average & Pass Rate -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">School Academic Mean</p>
                <span class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $kpis['overall_average'] }}%</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pass rate: <span class="font-bold text-emerald-600">{{ $kpis['pass_rate'] }}%</span> ({{ number_format($kpis['results_count']) }} results)</p>
        </div>

        <!-- Academic Structure -->
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Faculty & Classes</p>
                <span class="p-2 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $kpis['active_classes_count'] }} <span class="text-sm font-normal text-gray-500">Classes</span></h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $kpis['active_subjects_count'] }} Subjects &bull; {{ $kpis['teachers_count'] }} Teaching Staff</p>
        </div>
    </div>

    <!-- Charts & Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Enrollment by Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Enrollment by Grade / Form</h3>
                <a href="{{ route('admin.reports.enrollment-by-class') }}" class="text-xs text-indigo-600 hover:underline font-medium">View Form Matrix &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase">
                            <th class="pb-3">Grade / Form</th>
                            <th class="pb-3 text-right">Boys</th>
                            <th class="pb-3 text-right">Girls</th>
                            <th class="pb-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($kpis['grade_enrollment'] as $item)
                        <tr>
                            <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $item['grade'] }}</td>
                            <td class="py-2.5 text-right text-gray-600 dark:text-gray-400">{{ $item['male'] }}</td>
                            <td class="py-2.5 text-right text-gray-600 dark:text-gray-400">{{ $item['female'] }}</td>
                            <td class="py-2.5 text-right font-bold text-gray-900 dark:text-white">{{ $item['total'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-gray-500 text-xs">No enrollment records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Performing Subjects -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Top Performing Subjects</h3>
                <a href="{{ route('admin.reports.subject-performance') }}" class="text-xs text-indigo-600 hover:underline font-medium">View All Subjects &rarr;</a>
            </div>
            <div class="space-y-4">
                @forelse($kpis['top_subjects'] as $subject)
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $subject['name'] }}</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $subject['average'] }}% <span class="text-xs font-normal text-gray-500">({{ $subject['candidates'] }} candidates)</span></span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ min(100, $subject['average']) }}%"></div>
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-gray-500 text-xs">
                    No academic examination results recorded yet.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
