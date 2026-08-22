@extends('layouts.app')

@section('title', 'Attendance Summary Report - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Attendance Summary Report</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Institutional attendance rates, present/absent trends, and class aggregates for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
            <a href="{{ route('admin.reports.attendance-register') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">Attendance Register</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.attendance-summary') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Grade / Form</label>
                <select name="grade" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Grades</option>
                    @foreach($available_grades as $g)
                        <option value="{{ $g }}" {{ request('grade') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Class</label>
                <select name="class_name" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Classes</option>
                    @foreach($available_classes as $c)
                        <option value="{{ $c }}" {{ request('class_name') === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Gender</label>
                <select name="gender" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All</option>
                    <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Filter</button>
                <a href="{{ route('admin.reports.attendance-summary') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Attendance Rate</p>
            <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $overall_rate }}%</h3>
            <p class="text-xs text-gray-500 mt-1">{{ number_format($total_records) }} logs</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Present</p>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($present_count) }}</h3>
            <p class="text-xs text-gray-500 mt-1">{{ $total_records > 0 ? round(($present_count / $total_records) * 100, 1) : 0 }}% on time</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Late</p>
            <h3 class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($late_count) }}</h3>
            <p class="text-xs text-gray-500 mt-1">{{ $total_records > 0 ? round(($late_count / $total_records) * 100, 1) : 0 }}% late arrivals</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Absent</p>
            <h3 class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ number_format($absent_count) }}</h3>
            <p class="text-xs text-gray-500 mt-1">{{ $total_records > 0 ? round(($absent_count / $total_records) * 100, 1) : 0 }}% unexcused</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Excused / Sick</p>
            <h3 class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($excused_count + $sick_count) }}</h3>
            <p class="text-xs text-gray-500 mt-1">Authorized leave</p>
        </div>
    </div>

    <!-- Attendance Breakdown by Class -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Attendance Rate by Class</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3 text-right">Total Days Marked</th>
                        <th class="px-5 py-3 text-right">Present</th>
                        <th class="px-5 py-3 text-right">Late</th>
                        <th class="px-5 py-3 text-right">Absent</th>
                        <th class="px-5 py-3 text-right">Attendance Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($by_class as $row)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-5 py-3 font-semibold text-indigo-600 dark:text-indigo-400">{{ $row['class_name'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">{{ $row['total'] }}</td>
                        <td class="px-5 py-3 text-right text-emerald-600 font-medium">{{ $row['present'] }}</td>
                        <td class="px-5 py-3 text-right text-amber-600 font-medium">{{ $row['late'] }}</td>
                        <td class="px-5 py-3 text-right text-rose-600 font-medium">{{ $row['absent'] }}</td>
                        <td class="px-5 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $row['rate'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-gray-500 text-sm">No attendance records found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
