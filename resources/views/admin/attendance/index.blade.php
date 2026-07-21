@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Attendance</h1>
            <p class="text-xs text-slate-400 mt-1">Mark and review student/staff attendance.</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="flex items-center gap-2 text-xs">
                <input type="date" name="date" value="{{ $selectedDate }}" class="rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                <select name="type" class="rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Types</option>
                    <option value="student" @selected($selectedType === 'student')>Students</option>
                    <option value="staff" @selected($selectedType === 'staff')>Staff</option>
                </select>
                <select name="status" class="rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ Str::headline($status) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 font-medium text-white hover:bg-indigo-600 transition">Filter</button>
                <a href="{{ route('admin.attendance.index') }}" class="rounded-full border border-slate-700 px-3 py-2 text-slate-200 hover:bg-slate-800/70 transition">Reset</a>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-3">Daily Summary ({{ $selectedDate }})</div>
            <div class="grid grid-cols-2 gap-3 text-xs">
                @foreach($statuses as $status)
                    <div class="rounded-xl bg-slate-900/80 border border-slate-800 px-3 py-2">
                        <div class="text-slate-400">{{ Str::headline($status) }}</div>
                        <div class="text-lg font-semibold text-slate-100">{{ $dailyStats[$status] ?? 0 }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 space-y-3">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>Mark Student Attendance</span>
                <span class="rounded-full bg-indigo-500/20 px-3 py-1 text-indigo-200">Students</span>
            </div>
            <form action="{{ route('admin.attendance.student.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">
                <div class="space-y-1">
                    <label class="text-slate-400">Student</label>
                    <select name="student_id" required class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->full_name ?? $student->first_name . ' ' . $student->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-slate-400">Status</label>
                        <select name="status" required class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ Str::headline($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-slate-400">Check In</label>
                        <input type="time" name="check_in_time" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-slate-400">Check Out</label>
                        <input type="time" name="check_out_time" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-slate-400">Notes</label>
                        <input type="text" name="notes" placeholder="Optional note" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 font-medium text-white hover:bg-indigo-600 transition">Save</button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 space-y-3">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>Mark Staff Attendance</span>
                <span class="rounded-full bg-emerald-500/20 px-3 py-1 text-emerald-200">Staff</span>
            </div>
            <form action="{{ route('admin.attendance.staff.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">
                <div class="space-y-1">
                    <label class="text-slate-400">Staff Member</label>
                    <select name="staff_id" required class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select staff</option>
                        @foreach($staff as $member)
                            <option value="{{ $member->id }}">{{ $member->full_name ?? $member->first_name . ' ' . $member->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-slate-400">Status</label>
                        <select name="status" required class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ Str::headline($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-slate-400">Check In</label>
                        <input type="time" name="check_in_time" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-slate-400">Check Out</label>
                        <input type="time" name="check_out_time" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-slate-400">Notes</label>
                        <input type="text" name="notes" placeholder="Optional note" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-full bg-emerald-500 px-4 py-2 font-medium text-white hover:bg-emerald-600 transition">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Check In</th>
                        <th class="px-4 py-3 text-left font-medium">Check Out</th>
                        <th class="px-4 py-3 text-left font-medium">Notes</th>
                        <th class="px-4 py-3 text-left font-medium">Marked By</th>
                        <th class="px-4 py-3 text-left font-medium">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($attendances as $attendance)
                        @php
                            $type = class_basename($attendance->attendable_type);
                            $name = $attendance->attendable?->full_name ?? ($attendance->attendable?->first_name . ' ' . $attendance->attendable?->last_name);
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition align-top">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $attendance->attendance_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="rounded-full bg-slate-800 px-2 py-1 text-[10px] uppercase tracking-wide">{{ $type }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $name ?? '-' }}</td>
                            <td class="px-4 py-3">{!! $attendance->status_badge !!}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $attendance->check_in_time ? $attendance->check_in_time : '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $attendance->check_out_time ? $attendance->check_out_time : '-' }}</td>
                            <td class="px-4 py-3 max-w-xs">
                                <div class="text-slate-300">{{ $attendance->notes ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-400">{{ $attendance->markedBy?->name ?? 'System' }}</td>
                            <td class="px-4 py-3">
                                <form action="{{ route('admin.attendance.update', $attendance) }}" method="POST" class="space-y-2">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-3 gap-2">
                                        <select name="status" class="rounded-lg border border-slate-800 bg-slate-900/70 px-2 py-1 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach($statuses as $status)
                                                <option value="{{ $status }}" @selected($attendance->status === $status)>{{ Str::headline($status) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="time" name="check_in_time" value="{{ $attendance->check_in_time }}" class="rounded-lg border border-slate-800 bg-slate-900/70 px-2 py-1 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                                        <input type="time" name="check_out_time" value="{{ $attendance->check_out_time }}" class="rounded-lg border border-slate-800 bg-slate-900/70 px-2 py-1 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                    <input type="text" name="notes" value="{{ $attendance->notes }}" placeholder="Notes" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-2 py-1 text-slate-100 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div class="text-right">
                                        <button type="submit" class="rounded-full bg-emerald-500 px-3 py-1 text-xs font-medium text-white hover:bg-emerald-600 transition">Update</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">No attendance records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $attendances->links() }}
        </div>
    </div>
@endsection

