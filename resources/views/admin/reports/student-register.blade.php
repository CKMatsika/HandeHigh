@extends('layouts.app')

@section('title', 'Student Master Register - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Student Master Register</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Complete student records directory with guardian information for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Register
            </button>
            <a href="{{ route('admin.reports.student-register.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.student-register') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Search Student</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or Adm #" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
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
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Filter</button>
                <a href="{{ route('admin.reports.student-register') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Register Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="font-semibold text-gray-900 dark:text-white">Registered Students ({{ number_format($total_students) }} Total)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Adm #</th>
                        <th class="px-4 py-3">Student Name</th>
                        <th class="px-4 py-3">Gender</th>
                        <th class="px-4 py-3">DOB / Age</th>
                        <th class="px-4 py-3">Form / Class</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Guardian</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $s)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 font-mono font-medium text-xs text-gray-900 dark:text-white">{{ $s['admission_number'] }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $s['full_name'] }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $s['gender'] }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs">{{ $s['date_of_birth'] }} ({{ $s['age'] }} yrs)</td>
                        <td class="px-4 py-3 font-medium text-indigo-600 dark:text-indigo-400">{{ $s['grade'] }} &bull; {{ $s['class_name'] }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $s['is_boarding'] === 'Boarding' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $s['is_boarding'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">{{ $s['status'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $s['guardian_name'] }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ $s['guardian_phone'] }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.reports.student-academic-profile', ['student_id' => $s['id']]) }}" class="text-xs text-indigo-600 hover:underline font-medium">Academic Profile</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-5 py-6 text-center text-gray-500 text-sm">No student records found.</td>
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
    </div>
</div>
@endsection
