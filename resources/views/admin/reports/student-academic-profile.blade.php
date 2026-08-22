@extends('layouts.app')

@section('title', 'Student Academic Profile - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Student Academic Profile</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Comprehensive student transcript, assessment history, and attendance records for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Profile
            </button>
            <a href="{{ route('admin.reports.student-register') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">Back to Register</a>
        </div>
    </div>

    <!-- Student Selector -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.student-academic-profile') }}" class="flex flex-col sm:flex-row items-end gap-3">
            <div class="flex-1 w-full">
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Select Student</label>
                <select name="student_id" onchange="this.form.submit()" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    @foreach($students as $s)
                        <option value="{{ $s->id }}" {{ $selected_student && $selected_student->id === $s->id ? 'selected' : '' }}>
                            {{ $s->admission_number }} - {{ $s->last_name }}, {{ $s->first_name }} ({{ $s->grade }} {{ $s->class_name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Load Profile</button>
            </div>
        </form>
    </div>

    @if($selected_student && $profile)
    <!-- Student Demographic & KPI Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-5">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $selected_student->first_name }} {{ $selected_student->last_name }}</h2>
                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">{{ $selected_student->admission_number }}</span>
                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">{{ ucfirst($selected_student->status ?: 'Active') }}</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $selected_student->grade }} &bull; {{ $selected_student->class_name }} &bull; Gender: {{ ucfirst($selected_student->gender ?: 'N/A') }} &bull; DOB: {{ $selected_student->date_of_birth ? $selected_student->date_of_birth->format('Y-m-d') : 'N/A' }}
                </p>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Cumulative Mean</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $profile['cumulative_average'] }}%</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Attendance Rate</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $profile['attendance']['attendance_rate'] }}%</p>
                </div>
            </div>
        </div>

        <!-- Attendance Stats Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 text-center">
            <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-lg">
                <p class="text-xs text-gray-500">Days Present</p>
                <p class="text-lg font-bold text-emerald-600">{{ $profile['attendance']['present'] }}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-lg">
                <p class="text-xs text-gray-500">Days Late</p>
                <p class="text-lg font-bold text-amber-600">{{ $profile['attendance']['late'] }}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-lg">
                <p class="text-xs text-gray-500">Days Absent</p>
                <p class="text-lg font-bold text-rose-600">{{ $profile['attendance']['absent'] }}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-lg">
                <p class="text-xs text-gray-500">Days Excused / Sick</p>
                <p class="text-lg font-bold text-blue-600">{{ $profile['attendance']['excused'] }}</p>
            </div>
        </div>
    </div>

    <!-- Academic Results Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Subject Assessment & Examination Results</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-5 py-3">Academic Period</th>
                        <th class="px-5 py-3">Subject Code</th>
                        <th class="px-5 py-3">Subject Name</th>
                        <th class="px-5 py-3 text-right">Score</th>
                        <th class="px-5 py-3 text-right">Max</th>
                        <th class="px-5 py-3 text-right">Percentage</th>
                        <th class="px-5 py-3 text-center">Grade</th>
                        <th class="px-5 py-3">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($profile['results_rows'] as $r)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $r['academic_year'] }} &bull; {{ $r['term'] }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $r['subject_code'] }}</td>
                        <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $r['subject_name'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-900 dark:text-white">{{ $r['total_score'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-500">{{ $r['max_score'] }}</td>
                        <td class="px-5 py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">{{ $r['percentage'] }}%</td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2 py-0.5 text-xs font-bold rounded-md {{ in_array($r['grade'], ['A', 'B', 'C']) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">{{ $r['grade'] }}</span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $r['remarks'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-6 text-center text-gray-500 text-sm">No exam result records found for this student.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center text-gray-500 shadow-sm border border-gray-200 dark:border-gray-700">
        Please select a student above to inspect their academic profile.
    </div>
    @endif
</div>
@endsection
