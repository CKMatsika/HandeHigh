@extends('layouts.app')

@section('title', 'Student Attendance Register - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Student Attendance Register</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Detailed itemized daily attendance logs for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Register
            </button>
            <a href="{{ route('admin.reports.attendance-register.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.attendance-register') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Statuses</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                    <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Late</option>
                    <option value="excused" {{ request('status') === 'excused' ? 'selected' : '' }}>Excused</option>
                    <option value="sick_leave" {{ request('status') === 'sick_leave' ? 'selected' : '' }}>Sick Leave</option>
                </select>
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
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Filter</button>
                <a href="{{ route('admin.reports.attendance-register') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Register Table Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 overflow-hidden">
        <x-documents.school-header 
            :school="$school ?? null"
            title="Official Daily Student Attendance Register"
            :subtitle="(request('start_date') ? 'From ' . request('start_date') : 'Current Session') . (request('end_date') ? ' to ' . request('end_date') : '')"
            reference="ATT-REG-{{ date('Ymd') }}"
            :date="now()"
        />

        <div class="px-2 py-2 border-b border-gray-200 dark:border-gray-700 mb-3 flex justify-between items-center text-xs text-gray-500">
            <h3 class="font-semibold text-gray-900 dark:text-white">Attendance Records ({{ number_format($total_records) }} Total)</h3>
            <span>Class Teacher Daily Register Session</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Adm #</th>
                        <th class="px-4 py-3">Student Name</th>
                        <th class="px-4 py-3">Form / Class</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-center">Check-in</th>
                        <th class="px-4 py-3 text-center">Check-out</th>
                        <th class="px-4 py-3">Marked By</th>
                        <th class="px-4 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $att)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-gray-900 dark:text-white font-medium text-xs">{{ $att['date'] }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $att['admission_number'] }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $att['student_name'] }}</td>
                        <td class="px-4 py-3 font-medium text-indigo-600 dark:text-indigo-400">{{ $att['grade'] }} &bull; {{ $att['class_name'] }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full 
                                {{ $att['status'] === 'Present' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : '' }}
                                {{ $att['status'] === 'Late' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : '' }}
                                {{ $att['status'] === 'Absent' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' : '' }}
                                {{ in_array($att['status'], ['Excused', 'Sick leave']) ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : '' }}
                            ">
                                {{ $att['status'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs font-mono text-gray-500">{{ $att['check_in'] }}</td>
                        <td class="px-4 py-3 text-center text-xs font-mono text-gray-500">{{ $att['check_out'] }}</td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $att['marked_by'] }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $att['notes'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-5 py-6 text-center text-gray-500 text-sm">No attendance records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paginator)
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $paginator->links() }}
        </div>
        @endif

        <x-documents.school-footer :school="$school ?? null" />
    </div>
</div>
@endsection
