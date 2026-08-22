@extends('layouts.app')

@section('title', 'Enrollment by Form & Class - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Enrollment by Form & Class Matrix</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Class teacher allocations, gender splits, and stream totals for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Matrix
            </button>
            <a href="{{ route('admin.reports.enrollment-summary') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">Enrollment Summary</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.enrollment-by-class') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Filter by Form</label>
                <select name="grade" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Forms</option>
                    @foreach($available_grades as $g)
                        <option value="{{ $g }}" {{ request('grade') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Filter</button>
                <a href="{{ route('admin.reports.enrollment-by-class') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Matrix Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-5 py-3">Grade / Form</th>
                        <th class="px-5 py-3">Class Name</th>
                        <th class="px-5 py-3">Class Teacher</th>
                        <th class="px-5 py-3 text-right">Boys</th>
                        <th class="px-5 py-3 text-right">Girls</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">% of Form</th>
                        <th class="px-5 py-3 text-right">% of School</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($matrix as $row)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $row['grade'] }}</td>
                        <td class="px-5 py-3 font-semibold text-indigo-600 dark:text-indigo-400">{{ $row['class_name'] }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $row['class_teacher'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">{{ $row['male'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">{{ $row['female'] }}</td>
                        <td class="px-5 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $row['total'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-500">{{ $row['form_percentage'] }}%</td>
                        <td class="px-5 py-3 text-right text-gray-500">{{ $row['school_percentage'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-6 text-center text-gray-500 text-sm">No class enrollment records found.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-900/50 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-gray-900 dark:text-white uppercase">Grand Total (School Population)</td>
                        <td class="px-5 py-3 text-right text-blue-600">{{ $grand_male }}</td>
                        <td class="px-5 py-3 text-right text-purple-600">{{ $grand_female }}</td>
                        <td class="px-5 py-3 text-right text-gray-900 dark:text-white">{{ $grand_total }}</td>
                        <td class="px-5 py-3 text-right">-</td>
                        <td class="px-5 py-3 text-right">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
