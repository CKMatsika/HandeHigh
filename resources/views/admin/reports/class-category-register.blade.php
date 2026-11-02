@extends('layouts.app')

@section('title', 'Class & Category Register - ' . $school->name)

@section('content')
<style>
    @media print {
        aside, header, nav, .no-print { display: none !important; }
        body, main, .print-area { background: #fff !important; color: #111827 !important; }
        .print-area { border: 0 !important; box-shadow: none !important; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af !important; color: #111827 !important; padding: 6px 8px !important; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
    }
</style>

<div class="space-y-6 print-area">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Class &amp; Category Student Register</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Print active students by grade, class/stream, and Day, Boarding, or Scholarship category.</p>
        </div>
        <button type="button" onclick="window.print()" class="no-print rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Print Register</button>
    </div>

    <form method="GET" action="{{ route('admin.reports.class-category-register') }}" class="no-print grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Grade / Form
            <select name="grade" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All grades</option>
                @foreach($availableGrades as $grade)
                    <option value="{{ $grade }}" @selected(($filters['grade'] ?? '') === $grade)>{{ $grade }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Class / Stream
            <select name="class_name" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All classes</option>
                @foreach($availableClasses as $className)
                    <option value="{{ $className }}" @selected(($filters['class_name'] ?? '') === $className)>{{ $className }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Student category
            <select name="category" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All categories</option>
                <option value="day" @selected(($filters['category'] ?? '') === 'day')>Day</option>
                <option value="boarding" @selected(($filters['category'] ?? '') === 'boarding')>Boarding</option>
                <option value="scholarship" @selected(($filters['category'] ?? '') === 'scholarship')>Scholarship</option>
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Apply filters</button>
            <a href="{{ route('admin.reports.class-category-register') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:border-gray-600 dark:text-gray-200">Clear</a>
        </div>
    </form>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 border-b border-gray-200 pb-4 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $school->name }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ $filters['grade'] ?? 'All grades' }} · {{ $filters['class_name'] ?? 'All classes' }} · {{ ucfirst($filters['category'] ?? 'All categories') }}
                <span class="ml-2">| {{ $students->count() }} active students</span>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Generated {{ now()->format('d M Y H:i') }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-900/60 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th class="px-3 py-2">Student name</th>
                        <th class="px-3 py-2">Admission / Registration</th>
                        <th class="px-3 py-2">Grade / Form</th>
                        <th class="px-3 py-2">Class / Stream</th>
                        <th class="px-3 py-2">Category</th>
                        <th class="px-3 py-2">Gender</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($students as $student)
                        @php
                            $category = $student->funding_status === 'scholarship'
                                ? 'Scholarship'
                                : ($student->report_is_boarding ? 'Boarding' : 'Day');
                        @endphp
                        <tr>
                            <td class="px-3 py-2">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ trim($student->first_name . ' ' . $student->other_names . ' ' . $student->last_name) }}</td>
                            <td class="px-3 py-2">{{ $student->admission_number ?: ($student->registration_number ?: '—') }}</td>
                            <td class="px-3 py-2">{{ $student->report_grade ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $student->report_class_name ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $category }}</td>
                            <td class="px-3 py-2">{{ ucfirst($student->gender ?: '—') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-gray-500">No active students match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
