@extends('layouts.app')

@section('title', 'Archived Students')

@section('content')
<div class="management-screen students-screen space-y-5">
    <div class="ms-page-header">
        <div>
            <p class="ms-eyebrow">ACADEMIC / STUDENT RECORDS</p>
            <h1 class="ms-title">Archived Students</h1>
            <p class="ms-description">Archived profiles are hidden from active student and enrolment lists. Their linked academic and financial history remains intact.</p>
        </div>
        <a href="{{ route('admin.students.index') }}" class="ms-button border border-slate-600 text-slate-200">Back to Students</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.students.archived') }}" class="ms-filter flex flex-wrap items-end gap-3">
        <div class="min-w-[240px] flex-1">
            <label for="archived-student-search" class="mb-1 block text-xs text-slate-400">Search archived student</label>
            <input id="archived-student-search" type="search" name="search" value="{{ request('search') }}" placeholder="Name, admission #, or registration #" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100">
        </div>
        <button type="submit" class="ms-button ms-button-primary">Search</button>
    </form>

    <div class="ms-table-card overflow-x-auto">
        <table class="ms-table w-full text-sm">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left">Student</th>
                    <th class="px-4 py-3 text-left">Grade / Class</th>
                    <th class="px-4 py-3 text-left">Archived on</th>
                    <th class="px-4 py-3 text-left">Reason</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($students as $student)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.students.show', $student) }}" class="ms-student-name">{{ $student->full_name }}</a>
                            <div class="text-xs text-slate-400">{{ $student->admission_number ?: ($student->registration_number ?: 'No admission number') }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">
                            {{ $student->latestEnrollment?->class?->grade ?: ($student->latestEnrollment?->grade ?: ($student->getRawOriginal('grade') ?: '—')) }} /
                            {{ $student->latestEnrollment?->class?->name ?: ($student->latestEnrollment?->class_name ?: ($student->getRawOriginal('class_name') ?: '—')) }}
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $student->archived_at?->format('d M Y H:i') ?: '—' }}</td>
                        <td class="max-w-sm px-4 py-3 text-slate-300">{{ $student->archive_reason ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.students.show', $student) }}" class="ms-table-action">View history</a>
                            @can('delete', $student)
                                <form method="POST" action="{{ route('admin.students.restore-archived', $student) }}" class="inline" onsubmit="return confirm('Restore this student to their previous status and reactivate enrolments that were active when archived?');">
                                    @csrf
                                    <button type="submit" class="ms-table-action text-emerald-300">Restore</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No archived students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="ms-pagination">{{ $students->links() }}</div>
</div>
@endsection
