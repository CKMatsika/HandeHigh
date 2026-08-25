@extends('layouts.app')

@section('title', 'Enrollment Summary Report - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="no-print flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Enrollment Summary Report</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Demographic breakdown, form/class distributions, and age demographics for {{ $school->display_name ?: $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
            <a href="{{ route('admin.reports.enrollment-summary.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="no-print bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.enrollment-summary') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
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
                    <option value="">All Genders</option>
                    <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>Transferred</option>
                    <option value="graduated" {{ request('status') === 'graduated' ? 'selected' : '' }}>Graduated</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Filter</button>
                <a href="{{ route('admin.reports.enrollment-summary') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    <x-documents.school-header 
        :school="$school ?? null"
        title="Official Student Enrollment & Demographic Summary Report"
        :subtitle="'Grade: ' . (request('grade') ?: 'All Grades') . ' · Class: ' . (request('class_name') ?: 'All Classes') . ' · Status: ' . (request('status') ? ucfirst(request('status')) : 'All')"
        :date="now()"
    />

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Total Enrolled</p>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($total_enrollment) }}</h3>
            <p class="text-xs text-gray-500 mt-1">Across all active streams</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Male Students</p>
            <h3 class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($male_total) }} <span class="text-sm font-normal text-gray-500">({{ $male_percentage }}%)</span></h3>
            <p class="text-xs text-gray-500 mt-1">Boys enrolled</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Female Students</p>
            <h3 class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ number_format($female_total) }} <span class="text-sm font-normal text-gray-500">({{ $female_percentage }}%)</span></h3>
            <p class="text-xs text-gray-500 mt-1">Girls enrolled</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Boarding vs Day</p>
            <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($boarding_total) }} <span class="text-sm font-normal text-gray-500">Boarders</span></h3>
            <p class="text-xs text-gray-500 mt-1">{{ number_format($day_total) }} Day Scholars</p>
        </div>
    </div>

    <!-- Enrollment by Form Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Enrollment by Grade / Form</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-5 py-3">Grade / Form</th>
                        <th class="px-5 py-3 text-right">Boys</th>
                        <th class="px-5 py-3 text-right">Girls</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">% of Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($by_grade as $item)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $item['grade'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">{{ $item['male'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">{{ $item['female'] }}</td>
                        <td class="px-5 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $item['total'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-400">{{ $item['percentage'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-gray-500 text-sm">No students match the criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-900/50 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                    <tr>
                        <td class="px-5 py-3 text-gray-900 dark:text-white">TOTAL</td>
                        <td class="px-5 py-3 text-right text-blue-600">{{ $male_total }}</td>
                        <td class="px-5 py-3 text-right text-purple-600">{{ $female_total }}</td>
                        <td class="px-5 py-3 text-right text-gray-900 dark:text-white">{{ $total_enrollment }}</td>
                        <td class="px-5 py-3 text-right">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Age Demographics Breakdown -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Student Age Demographics</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($age_brackets as $bracket => $count)
            <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-lg text-center border border-gray-100 dark:border-gray-800">
                <p class="text-xs text-gray-500 font-medium">{{ $bracket }}</p>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ $count }}</h4>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $total_enrollment > 0 ? round(($count / $total_enrollment) * 100, 1) : 0 }}%</p>
            </div>
            @endforeach
        </div>
    </div>

    <x-documents.school-footer 
        :school="$school ?? null"
        :show-banking="false"
        notice="Official institutional enrollment demographic report generated from verified student registry."
    />
</div>
@endsection
