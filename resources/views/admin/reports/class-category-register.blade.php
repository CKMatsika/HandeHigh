@extends('layouts.app')

@section('title', 'Class & Category Register - ' . $school->name)

@section('content')
<style>
    @media print {
        aside, header, nav, .no-print { display: none !important; }
        body, main, .print-area { background: #fff !important; color: #111827 !important; }
        .print-area { border: 0 !important; box-shadow: none !important; }
        .register-card, .register-table thead { background: #fff !important; }
        .print-area, .print-area * { color: #111827 !important; }
        .register-table { width: 100%; border-collapse: collapse; }
        .register-table th, .register-table td { border: 1px solid #9ca3af !important; padding: 6px 8px !important; }
        .register-table thead { display: table-header-group; }
        .register-table tr { break-inside: avoid; }
    }
</style>

<div class="class-category-register space-y-6 print-area text-slate-100">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Class &amp; Category Student Register</h1>
            <p class="text-sm text-slate-300">Print active students by grade, class/stream, and Day, Boarding, or Scholarship category.</p>
        </div>
        <div class="no-print flex gap-2">
            <a href="{{ route('admin.reports.class-category-register.export', request()->query()) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Download Excel</a>
            <button type="button" onclick="window.print()" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-500">Print Register</button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.reports.class-category-register') }}" class="no-print grid grid-cols-1 gap-4 rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <label class="text-sm font-medium text-slate-300">Grade / Form
            <select name="grade" class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-white">
                <option value="">All grades</option>
                @foreach($availableGrades as $grade)
                    <option value="{{ $grade }}" @selected(($filters['grade'] ?? '') === $grade)>{{ $grade }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium text-slate-300">Class / Stream
            <select name="class_name" class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-white">
                <option value="">All classes</option>
                @foreach($availableClasses as $className)
                    <option value="{{ $className }}" @selected(($filters['class_name'] ?? '') === $className)>{{ $className }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium text-slate-300">Student category
            <select name="category" class="mt-1 block w-full rounded-lg border border-slate-600 bg-slate-800 text-white">
                <option value="">All categories</option>
                <option value="day" @selected(($filters['category'] ?? '') === 'day')>Day</option>
                <option value="boarding" @selected(($filters['category'] ?? '') === 'boarding')>Boarding</option>
                <option value="scholarship" @selected(($filters['category'] ?? '') === 'scholarship')>Scholarship</option>
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-500">Apply filters</button>
            <a href="{{ route('admin.reports.class-category-register') }}" class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Clear</a>
        </div>
    </form>

    <section class="register-card rounded-xl border border-slate-700 bg-slate-900 p-5 text-slate-200 shadow-sm">
        <div class="mb-4 border-b border-slate-700 pb-4">
            <h2 class="text-lg font-bold text-white">{{ $school->name }}</h2>
            <p class="text-sm text-slate-300">
                {{ $filters['grade'] ?? 'All grades' }} · {{ $filters['class_name'] ?? 'All classes' }} · {{ ucfirst($filters['category'] ?? 'All categories') }}
                <span class="ml-2">| {{ $students->count() }} active students</span>
            </p>
            <p class="text-xs text-slate-400">Generated {{ now()->format('d M Y H:i') }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="register-table w-full text-left text-sm text-slate-200">
                <thead class="bg-slate-800 text-xs uppercase text-slate-300">
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
                <tbody class="divide-y divide-slate-700">
                    @forelse($students as $student)
                        @php
                            $category = $student->funding_status === 'scholarship'
                                ? 'Scholarship'
                                : ($student->report_is_boarding ? 'Boarding' : 'Day');
                        @endphp
                        <tr>
                            <td class="px-3 py-2">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-medium text-white">{{ trim($student->first_name . ' ' . $student->other_names . ' ' . $student->last_name) }}</td>
                            <td class="px-3 py-2">{{ $student->admission_number ?: ($student->registration_number ?: '—') }}</td>
                            <td class="px-3 py-2">{{ $student->report_grade ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $student->report_class_name ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $category }}</td>
                            <td class="px-3 py-2">{{ ucfirst($student->gender ?: '—') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-slate-400">No active students match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
