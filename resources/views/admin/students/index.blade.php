@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/management-preview.css') }}">
@endpush

@section('content')
<div class="management-screen students-screen">
    <div class="ms-page-header">
        <div>
            <p class="ms-eyebrow">ACADEMIC / STUDENT RECORDS</p>
            <h1 class="ms-title">Student Management</h1>
            <p class="ms-description">Manage student profiles, class placements and residency.</p>
        </div>
        <a href="{{ route('admin.students.create') }}" class="ms-button ms-button-primary">
            New Student
        </a>
    </div>

    <!-- Filters -->
    <div class="ms-filter">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <label class="text-xs text-slate-400">Category
                <select name="category" class="block bg-slate-800 rounded-lg px-3 py-2 text-slate-100">
                    <option value="">All categories</option>
                    @foreach(['boarding' => 'Boarding', 'day' => 'Day', 'scholarship' => 'Scholarship'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs text-slate-400">Funding
                <select name="funding_status" class="block bg-slate-800 rounded-lg px-3 py-2 text-slate-100">
                    <option value="">All funding</option>
                    <option value="regular" @selected(request('funding_status') === 'regular')>Regular</option>
                    <option value="scholarship" @selected(request('funding_status') === 'scholarship')>Scholarship</option>
                </select>
            </label>
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-slate-400 mb-1 block">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, admission #, or registration #"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div class="min-w-[140px]">
                <label class="text-xs text-slate-400 mb-1 block">Grade</label>
                <select name="grade" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="">All Grades</option>
                    @foreach($gradeOptions as $g)
                        <option value="{{ $g }}" {{ request('grade') == $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[120px]">
                <label class="text-xs text-slate-400 mb-1 block">Status</label>
                <select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="active" @selected((!request()->has('status') && !request()->filled('search')) || request('status') === 'active')>Active</option>
                    <option value="all" @selected(request('status') === 'all' || (request()->filled('search') && !request()->filled('status')))>All statuses</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                </select>
            </div>
            <div class="min-w-[120px]">
                <label class="text-xs text-slate-400 mb-1 block">Residency</label>
                <select name="boarding" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="yes" {{ request('boarding') == 'yes' ? 'selected' : '' }}>Boarding</option>
                    <option value="no" {{ request('boarding') == 'no' ? 'selected' : '' }}>Day Scholar</option>
                </select>
            </div>
            <button type="submit" class="ms-button ms-button-primary">Apply filters</button>
        </form>
    </div>

    <!-- Students Table -->
    <div class="ms-table-card">
        <div class="ms-table-scroll">
            <table class="w-full text-sm ms-table">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Student</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Admission #</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Grade / Class</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">House</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Category / Residency</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-slate-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($students as $student)
                    @php
                        $activePlacement = $student->currentEnrollment;
                        $displayGrade = $activePlacement?->class?->grade ?? $activePlacement?->grade ?? $student->grade;
                        $displayClass = $activePlacement?->class?->name ?? $activePlacement?->class_name ?? $student->class_name;
                    @endphp
                    <tr class="hover:bg-slate-800/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="ms-avatar w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">
                                    {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.students.show', $student) }}" class="ms-student-name">
                                        {{ $student->full_name }}
                                    </a>
                                    <p class="text-xs text-slate-500">{{ $student->gender ? ucfirst($student->gender) : '' }} {{ $student->date_of_birth ? '| ' . $student->date_of_birth->format('d M Y') : '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $student->admission_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $displayGrade ?? '—' }} / {{ $displayClass ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($student->house)
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full" style="background: {{ $student->house->color }}20; color: {{ $student->house->color }}">
                                    {{ $student->house->emoji }} {{ $student->house->name }}
                                </span>
                            @else
                                <span class="text-slate-500 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-indigo-300 block">{{ $student->category_label }}</span>
                            @if($student->is_boarding && $student->currentBedAssignment)
                                <span class="text-xs text-blue-400">{{ $student->currentBedAssignment->bed->dormitory->name ?? '' }} (Bed {{ $student->currentBedAssignment->bed->bed_number ?? '' }})</span>
                            @elseif($student->is_boarding)
                                <span class="text-xs text-yellow-400">Boarding (no bed)</span>
                            @else
                                <span class="text-xs text-slate-500">Day Scholar</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = ['active' => 'green', 'inactive' => 'slate', 'graduated' => 'blue', 'transferred' => 'yellow', 'expelled' => 'red'];
                                $color = $statusColors[$student->status] ?? 'slate';
                            @endphp
                            <span class="text-xs px-2 py-1 rounded-full bg-{{ $color }}-500/20 text-{{ $color }}-400">{{ ucfirst($student->status) }}</span>
                            @if($student->currentEnrollment && $student->status !== 'active')
                                <div class="mt-1 text-[10px] text-amber-300">Active enrollment — profile status needs review</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.students.show', $student) }}" class="ms-table-action">View</a>
                            <a href="{{ route('admin.students.edit', $student) }}" class="ms-table-action">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                            No students found. <a href="{{ route('admin.students.create') }}" class="text-indigo-400 hover:underline">Add your first student</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="ms-pagination">{{ $students->withQueryString()->links() }}</div>
</div>
@endsection
