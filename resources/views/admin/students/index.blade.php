@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Student Management</h1>
            <p class="text-slate-400 mt-1">Manage all students — profiles, subjects, activities, boarding, and more</p>
        </div>
        <a href="{{ route('admin.students.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            + New Student
        </a>
    </div>

    <!-- Filters -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-slate-400 mb-1 block">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, admission #, or registration #"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div class="min-w-[140px]">
                <label class="text-xs text-slate-400 mb-1 block">Grade</label>
                <select name="grade" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="">All Grades</option>
                    @foreach(['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Form 1','Form 2','Form 3','Form 4','Form 5','Form 6'] as $g)
                        <option value="{{ $g }}" {{ request('grade') == $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[120px]">
                <label class="text-xs text-slate-400 mb-1 block">Status</label>
                <select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="">Active</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                </select>
            </div>
            <div class="min-w-[120px]">
                <label class="text-xs text-slate-400 mb-1 block">Boarding</label>
                <select name="boarding" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="yes" {{ request('boarding') == 'yes' ? 'selected' : '' }}>Boarding</option>
                    <option value="no" {{ request('boarding') == 'no' ? 'selected' : '' }}>Day Scholar</option>
                </select>
            </div>
            <button type="submit" class="bg-slate-700 hover:bg-slate-600 text-slate-200 px-4 py-2 rounded-lg text-sm transition">Filter</button>
        </form>
    </div>

    <!-- Students Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Student</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Admission #</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Grade / Class</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">House</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Boarding</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-slate-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($students as $student)
                    <tr class="hover:bg-slate-800/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-sm font-bold flex-shrink-0">
                                    {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.students.show', $student) }}" class="text-slate-100 font-medium hover:text-indigo-400 transition">
                                        {{ $student->full_name }}
                                    </a>
                                    <p class="text-xs text-slate-500">{{ $student->gender ? ucfirst($student->gender) : '' }} {{ $student->date_of_birth ? '| ' . $student->date_of_birth->format('d M Y') : '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $student->admission_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $student->grade ?? '—' }} / {{ $student->class_name ?? '—' }}</td>
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
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.students.show', $student) }}" class="text-slate-400 hover:text-indigo-400 transition text-xs">View</a>
                            <span class="text-slate-600 mx-1">|</span>
                            <a href="{{ route('admin.students.edit', $student) }}" class="text-slate-400 hover:text-yellow-400 transition text-xs">Edit</a>
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

    {{ $students->withQueryString()->links() }}
</div>
@endsection
