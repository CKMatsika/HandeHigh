@extends('layouts.app')

@section('title', 'Subject Performance Report - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Subject Performance Report</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Subject-by-subject assessment averages, score ranges, and pass rate analysis for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
            <a href="{{ route('admin.reports.subject-performance.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.subject-performance') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
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
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Filter</button>
                <a href="{{ route('admin.reports.subject-performance') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Subject Ranking ({{ number_format($total_subjects) }} Subjects, {{ number_format($grand_candidates) }} Candidates)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-5 py-3">Subject Code</th>
                        <th class="px-5 py-3">Subject Name</th>
                        <th class="px-5 py-3 text-right">Candidates</th>
                        <th class="px-5 py-3 text-right">Mean Average</th>
                        <th class="px-5 py-3 text-right">Highest</th>
                        <th class="px-5 py-3 text-right">Lowest</th>
                        <th class="px-5 py-3 text-right">Passed</th>
                        <th class="px-5 py-3 text-right">Failed</th>
                        <th class="px-5 py-3 text-right">Pass Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $s)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-5 py-3 font-mono font-medium text-gray-900 dark:text-white text-xs">{{ $s['subject_code'] }}</td>
                        <td class="px-5 py-3 font-semibold text-indigo-600 dark:text-indigo-400">{{ $s['subject_name'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">{{ $s['candidates'] }}</td>
                        <td class="px-5 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $s['average'] }}%</td>
                        <td class="px-5 py-3 text-right text-emerald-600 font-medium">{{ $s['highest'] }}%</td>
                        <td class="px-5 py-3 text-right text-rose-600 font-medium">{{ $s['lowest'] }}%</td>
                        <td class="px-5 py-3 text-right text-emerald-600 font-medium">{{ $s['pass_count'] }}</td>
                        <td class="px-5 py-3 text-right text-rose-600 font-medium">{{ $s['fail_count'] }}</td>
                        <td class="px-5 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $s['pass_rate'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-5 py-6 text-center text-gray-500 text-sm">No subject assessment results recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
