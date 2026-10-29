@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/management-preview.css') }}">
@endpush

@section('content')
    <div class="management-screen enrollments-screen">
    <div class="ms-page-header">
        <div>
            <p class="ms-eyebrow">ACADEMIC / STUDENT RECORDS</p>
            <h1 class="ms-title">Enrollments</h1>
            <p class="ms-description">Review student placements, academic periods and fee status.</p>
        </div>
        <div class="ms-actions">
            <a href="{{ route('admin.enrollments.bulk-create') }}" class="ms-button ms-button-secondary">
                Bulk Enroll
            </a>
            <a href="{{ route('admin.enrollments.create') }}" class="ms-button ms-button-primary">
                New Enrollment
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.enrollments.index') }}" class="ms-filter">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="enrollment-search" class="mb-1 block text-[11px] font-medium text-slate-400">Search student</label>
                <input id="enrollment-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Name, admission number or registration number"
                    class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label for="enrollment-year" class="mb-1 block text-[11px] font-medium text-slate-400">Academic year</label>
                <select id="enrollment-year" name="academic_year" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100">
                    <option value="">All years</option>
                    @foreach($academicYearOptions as $year)
                        <option value="{{ $year }}" @selected(request('academic_year') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="enrollment-term" class="mb-1 block text-[11px] font-medium text-slate-400">Term</label>
                <select id="enrollment-term" name="term" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100">
                    <option value="">All terms</option>
                    @foreach($termOptions as $termOption)
                        <option value="{{ $termOption }}" @selected(request('term') === (string) $termOption)>{{ $termOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="enrollment-grade" class="mb-1 block text-[11px] font-medium text-slate-400">Grade / Form</label>
                <select id="enrollment-grade" name="grade" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100">
                    <option value="">All grades</option>
                    @foreach($gradeOptions as $gradeOption)
                        <option value="{{ $gradeOption }}" @selected(request('grade') === (string) $gradeOption)>{{ $gradeOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="enrollment-class" class="mb-1 block text-[11px] font-medium text-slate-400">Class / Stream</label>
                <select id="enrollment-class" name="class_name" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100">
                    <option value="">All classes</option>
                    @foreach($classOptions as $classOption)
                        <option value="{{ $classOption }}" @selected(request('class_name', request('stream')) === (string) $classOption)>{{ $classOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="enrollment-residency" class="mb-1 block text-[11px] font-medium text-slate-400">Residency</label>
                <select id="enrollment-residency" name="residency" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100">
                    <option value="">All students</option>
                    <option value="boarding" @selected(request('residency') === 'boarding')>Boarding</option>
                    <option value="day" @selected(request('residency') === 'day')>Day scholar</option>
                </select>
            </div>
            <div>
                <label for="enrollment-gender" class="mb-1 block text-[11px] font-medium text-slate-400">Gender</label>
                <select id="enrollment-gender" name="gender" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100">
                    <option value="">All genders</option>
                    <option value="female" @selected(request('gender') === 'female')>Female</option>
                    <option value="male" @selected(request('gender') === 'male')>Male</option>
                </select>
            </div>
            <div>
                <label for="enrollment-status" class="mb-1 block text-[11px] font-medium text-slate-400">Status</label>
                <select id="enrollment-status" name="status" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100">
                    <option value="">All statuses</option>
                    @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" @selected(request('status') === (string) $statusOption)>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-800 pt-3">
            <p class="text-[11px] text-slate-400">{{ number_format($enrollments->total()) }} enrolment{{ $enrollments->total() === 1 ? '' : 's' }} found</p>
            <div class="flex gap-2">
                <a href="{{ route('admin.enrollments.index') }}" class="rounded-xl border border-slate-700 px-4 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800">Clear filters</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Search / Filter</button>
            </div>
        </div>
    </form>

    @if (session('import_summary'))
        @php
            $summary = session('import_summary');
        @endphp
        <div class="mb-4 rounded-xl border border-slate-700 bg-slate-900 px-4 py-4" role="status">
            <h2 class="mb-2 text-sm font-semibold text-slate-100">Bulk enrolment import summary</h2>
            <dl class="grid grid-cols-2 gap-2 text-xs text-slate-300 sm:grid-cols-4">
                <div><dt>New students created</dt><dd class="font-semibold">{{ $summary['new_students_created'] ?? 0 }}</dd></div>
                <div><dt>Existing students skipped</dt><dd class="font-semibold">{{ $summary['existing_students_skipped'] ?? 0 }}</dd></div>
                <div><dt>Ambiguous students skipped</dt><dd class="font-semibold">{{ $summary['ambiguous_students_skipped'] ?? 0 }}</dd></div>
                <div><dt>Invalid rows</dt><dd class="font-semibold">{{ $summary['invalid_rows'] ?? 0 }}</dd></div>
            </dl>
            @if (!empty($summary['rows']))
                <div class="mt-3 max-h-64 overflow-auto">
                    <table class="min-w-full text-xs text-slate-300">
                        <thead><tr class="border-b border-slate-700 text-left text-slate-400"><th class="py-1 pr-3">Excel row</th><th class="py-1 pr-3">Result</th><th class="py-1">Reason</th></tr></thead>
                        <tbody>
                            @foreach ($summary['rows'] as $result)
                                <tr class="border-b border-slate-800"><td class="py-1 pr-3">{{ $result['row_number'] ?? '—' }}</td><td class="py-1 pr-3">{{ str_replace('_', ' ', $result['outcome'] ?? '') }}</td><td class="py-1">{{ $result['reason'] ?? '' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <div class="ms-table-card">
        @if($enrollments->count())
            <div class="ms-table-scroll"><table class="min-w-full text-xs text-slate-100 ms-table">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Student</th>
                        <th class="text-left py-2 font-medium">Guardian</th>
                        <th class="text-left py-2 font-medium">Year / Term</th>
                        <th class="text-left py-2 font-medium">Grade / Class</th>
                        <th class="text-left py-2 font-medium">Invoice</th>
                        <th class="text-left py-2 font-medium">Status</th>
                        <th class="text-left py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrollments as $enrollment)
                        @php
                            $student = $enrollment->student;
                            $primaryGuardian = $student?->guardians?->firstWhere('pivot.is_primary', true) ?? $student?->guardians?->first();
                            $invoice = $enrollment->invoices->sortByDesc('issued_at')->first();
                            $placementGrade = $enrollment->class?->grade ?? $enrollment->grade;
                            $placementClass = $enrollment->class?->name ?? $enrollment->class_name;
                            $studentPlacementGrade = $student?->currentEnrollment?->class?->grade ?? $student?->currentEnrollment?->grade ?? $student?->grade;
                            $studentPlacementClass = $student?->currentEnrollment?->class?->name ?? $student?->currentEnrollment?->class_name ?? $student?->class_name;
                        @endphp
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">
                                <div class="font-medium">{{ $student?->first_name }} {{ $student?->last_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $studentPlacementGrade }} {{ $studentPlacementClass }}</div>
                                <div class="text-[10px] text-slate-500">Admission # {{ $student?->admission_number ?: '—' }} · ERP ID {{ $student?->id ?: '—' }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                @if($primaryGuardian)
                                    <div class="font-medium">{{ $primaryGuardian->first_name }} {{ $primaryGuardian->last_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $primaryGuardian->email }}</div>
                                @else
                                    <span class="text-[11px] text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                <div>{{ $enrollment->academic_year }}</div>
                                <div class="text-[11px] text-slate-400">{{ $enrollment->term }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                <div>{{ $placementGrade }}</div>
                                <div class="text-[11px] text-slate-400">{{ $placementClass }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                @if($invoice)
                                    <div class="font-medium">{{ $invoice->number }}</div>
                                    <div class="text-[11px] text-slate-400">Total {{ number_format($invoice->total_amount, 2) }} · Balance {{ number_format($invoice->balance, 2) }}</div>
                                @else
                                    <span class="text-[11px] text-slate-500">No invoice</span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-950/70 px-2 py-0.5 text-[11px] capitalize">
                                    {{ $enrollment->status }}
                                </span>
                                @if($enrollment->possible_duplicate)
                                    <div class="mt-1"><span class="inline-flex rounded-full border border-amber-700/70 bg-amber-950/40 px-2 py-0.5 text-[10px] text-amber-300">Possible duplicate · verify</span></div>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.enrollments.show', $enrollment) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">View</a>
                                    <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">Edit</a>
                                    <a href="{{ route('admin.enrollments.print', $enrollment) }}" class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-800 transition">Print</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>

            <div class="mt-4">
                {{ $enrollments->links() }}
            </div>
        @else
            <p class="text-xs text-slate-400">{{ request()->hasAny(['search', 'academic_year', 'term', 'grade', 'class_name', 'stream', 'residency', 'gender', 'status']) ? 'No enrolments match these search filters.' : 'No enrollments have been captured yet.' }}</p>
        @endif
    </div>
    </div>
@endsection
