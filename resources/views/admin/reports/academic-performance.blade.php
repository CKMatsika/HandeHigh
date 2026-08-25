@extends('layouts.app')

@section('title', 'Academic Performance Summary - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Academic Performance Summary</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Term examinations, pass/fail analytics, and class rankings for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
            <a href="{{ route('admin.reports.subject-performance') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">Subject Performance</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.academic-performance') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Academic Year</label>
                <input type="text" name="academic_year" value="{{ request('academic_year') }}" placeholder="e.g. 2026" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Term</label>
                <select name="term" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Terms</option>
                    <option value="Term 1" {{ request('term') === 'Term 1' ? 'selected' : '' }}>Term 1</option>
                    <option value="Term 2" {{ request('term') === 'Term 2' ? 'selected' : '' }}>Term 2</option>
                    <option value="Term 3" {{ request('term') === 'Term 3' ? 'selected' : '' }}>Term 3</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Class</label>
                <select name="class_id" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Classes</option>
                    @foreach($available_classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Subject</label>
                <select name="subject_id" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Subjects</option>
                    @foreach($available_subjects as $sub)
                        <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Filter</button>
                <a href="{{ route('admin.reports.academic-performance') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Centralized School Document Header -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <x-documents.school-header 
            :school="$school ?? null"
            title="Institutional Academic Performance Summary"
            :subtitle="(request('academic_year') ? 'Year: ' . request('academic_year') : 'All Academic Years') . (request('term') ? ' · ' . request('term') : '')"
            reference="ACAD-PERF-{{ date('Ymd') }}"
            :date="now()"
        />
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Total Entries</p>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($total_entries) }}</h3>
            <p class="text-xs text-gray-500 mt-1">Assessment records evaluated</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Mean Score</p>
            <h3 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $average_mark }}%</h3>
            <p class="text-xs text-gray-500 mt-1">Average student achievement</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Passed (&ge;50%)</p>
            <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($pass_count) }} <span class="text-sm font-normal text-gray-500">({{ $pass_rate }}%)</span></h3>
            <p class="text-xs text-gray-500 mt-1">Met or exceeded standard</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase text-gray-500">Failed (&lt;50%)</p>
            <h3 class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ number_format($fail_count) }} <span class="text-sm font-normal text-gray-500">({{ $total_entries > 0 ? round(100 - $pass_rate, 1) : 0 }}%)</span></h3>
            <p class="text-xs text-gray-500 mt-1">Requires remedial attention</p>
        </div>
    </div>

    <!-- Grade Distribution Cards -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Grade Distribution Matrix</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg text-center border border-emerald-200 dark:border-emerald-800">
                <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Grade A (75%+)</p>
                <h4 class="text-xl font-bold text-emerald-900 dark:text-emerald-100 mt-1">{{ $grade_distribution['A'] }}</h4>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $total_entries > 0 ? round(($grade_distribution['A'] / $total_entries) * 100, 1) : 0 }}%</p>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-center border border-blue-200 dark:border-blue-800">
                <p class="text-xs font-bold text-blue-800 dark:text-blue-300">Grade B (65-74%)</p>
                <h4 class="text-xl font-bold text-blue-900 dark:text-blue-100 mt-1">{{ $grade_distribution['B'] }}</h4>
                <p class="text-[11px] text-blue-600 dark:text-blue-400 mt-0.5">{{ $total_entries > 0 ? round(($grade_distribution['B'] / $total_entries) * 100, 1) : 0 }}%</p>
            </div>
            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-center border border-indigo-200 dark:border-indigo-800">
                <p class="text-xs font-bold text-indigo-800 dark:text-indigo-300">Grade C (50-64%)</p>
                <h4 class="text-xl font-bold text-indigo-900 dark:text-indigo-100 mt-1">{{ $grade_distribution['C'] }}</h4>
                <p class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $total_entries > 0 ? round(($grade_distribution['C'] / $total_entries) * 100, 1) : 0 }}%</p>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg text-center border border-amber-200 dark:border-amber-800">
                <p class="text-xs font-bold text-amber-800 dark:text-amber-300">Grade D (40-49%)</p>
                <h4 class="text-xl font-bold text-amber-900 dark:text-amber-100 mt-1">{{ $grade_distribution['D'] }}</h4>
                <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">{{ $total_entries > 0 ? round(($grade_distribution['D'] / $total_entries) * 100, 1) : 0 }}%</p>
            </div>
            <div class="p-3 bg-orange-50 dark:bg-orange-900/30 rounded-lg text-center border border-orange-200 dark:border-orange-800">
                <p class="text-xs font-bold text-orange-800 dark:text-orange-300">Grade E (30-39%)</p>
                <h4 class="text-xl font-bold text-orange-900 dark:text-orange-100 mt-1">{{ $grade_distribution['E'] }}</h4>
                <p class="text-[11px] text-orange-600 dark:text-orange-400 mt-0.5">{{ $total_entries > 0 ? round(($grade_distribution['E'] / $total_entries) * 100, 1) : 0 }}%</p>
            </div>
            <div class="p-3 bg-rose-50 dark:bg-rose-900/30 rounded-lg text-center border border-rose-200 dark:border-rose-800">
                <p class="text-xs font-bold text-rose-800 dark:text-rose-300">Grade U / Fail (&lt;30%)</p>
                <h4 class="text-xl font-bold text-rose-900 dark:text-rose-100 mt-1">{{ $grade_distribution['U/F'] }}</h4>
                <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">{{ $total_entries > 0 ? round(($grade_distribution['U/F'] / $total_entries) * 100, 1) : 0 }}%</p>
            </div>
        </div>
    </div>

    <!-- Class Breakdown Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Performance by Class</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-5 py-3">Class</th>
                        <th class="px-5 py-3 text-right">Candidates</th>
                        <th class="px-5 py-3 text-right">Mean Average</th>
                        <th class="px-5 py-3 text-right">Passed</th>
                        <th class="px-5 py-3 text-right">Failed</th>
                        <th class="px-5 py-3 text-right">Pass Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($by_class as $row)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-5 py-3 font-semibold text-indigo-600 dark:text-indigo-400">{{ $row['class_name'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">{{ $row['candidates'] }}</td>
                        <td class="px-5 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $row['average'] }}%</td>
                        <td class="px-5 py-3 text-right text-emerald-600 font-medium">{{ $row['pass_count'] }}</td>
                        <td class="px-5 py-3 text-right text-rose-600 font-medium">{{ $row['fail_count'] }}</td>
                        <td class="px-5 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $row['pass_rate'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-gray-500 text-sm">No class result data found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <x-documents.school-footer :school="$school ?? null" />
        </div>
    </div>
</div>
@endsection
