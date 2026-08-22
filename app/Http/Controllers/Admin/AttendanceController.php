<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Staff;
use App\Rules\TenantExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * Display attendance records and forms for marking student/staff attendance.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $selectedDate = $request->input('date', now()->toDateString());
        $selectedType = $request->input('type');
        $selectedStatus = $request->input('status');

        $query = Attendance::where('school_id', $school->id)
            ->with(['attendable', 'markedBy'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($selectedDate) {
            $query->whereDate('attendance_date', $selectedDate);
        }

        if ($selectedType) {
            $query->where('attendable_type', $this->resolveType($selectedType));
        }

        if ($selectedStatus) {
            $query->where('status', $selectedStatus);
        }

        $attendances = $query->paginate(20)->appends($request->only(['date', 'type', 'status']));

        $students = $school->students()->orderBy('first_name')->orderBy('last_name')->get();
        $staff = Staff::where('school_id', $school->id)->orderBy('first_name')->orderBy('last_name')->get();

        $dailyStats = Attendance::where('school_id', $school->id)
            ->whereDate('attendance_date', $selectedDate)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('admin.attendance.index', [
            'school' => $school,
            'students' => $students,
            'staff' => $staff,
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
            'selectedType' => $selectedType,
            'selectedStatus' => $selectedStatus,
            'dailyStats' => $dailyStats,
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Mark or update attendance for a student.
     */
    public function storeStudent(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => ['required', TenantExists::make('students')],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in($this->statuses())],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $student = $school->students()->findOrFail($validated['student_id']);

        Attendance::updateOrCreate(
            [
                'school_id' => $school->id,
                'attendable_type' => Student::class,
                'attendable_id' => $student->id,
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'status' => $validated['status'],
                'check_in_time' => $validated['check_in_time'] ?? null,
                'check_out_time' => $validated['check_out_time'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'marked_by' => $user->id,
            ]
        );

        return redirect()
            ->route('admin.attendance.index', ['date' => $validated['attendance_date']])
            ->with('success', 'Student attendance recorded.');
    }

    /**
     * Mark or update attendance for a staff member.
     */
    public function storeStaff(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'staff_id' => ['required', TenantExists::make('staff')],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in($this->statuses())],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $staff = Staff::where('school_id', $school->id)->findOrFail($validated['staff_id']);

        Attendance::updateOrCreate(
            [
                'school_id' => $school->id,
                'attendable_type' => Staff::class,
                'attendable_id' => $staff->id,
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'status' => $validated['status'],
                'check_in_time' => $validated['check_in_time'] ?? null,
                'check_out_time' => $validated['check_out_time'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'marked_by' => $user->id,
            ]
        );

        return redirect()
            ->route('admin.attendance.index', ['date' => $validated['attendance_date']])
            ->with('success', 'Staff attendance recorded.');
    }

    /**
     * Update an existing attendance record (status, times, notes).
     */
    public function update(Request $request, Attendance $attendance)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $attendance->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in($this->statuses())],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->update(array_merge($validated, ['marked_by' => $user->id]));

        return redirect()
            ->route('admin.attendance.index', ['date' => $attendance->attendance_date->toDateString()])
            ->with('success', 'Attendance updated.');
    }

    /**
     * Resolve a friendly type string to the morph class.
     */
    protected function resolveType(?string $type): ?string
    {
        return match ($type) {
            'student' => Student::class,
            'staff' => Staff::class,
            default => null,
        };
    }

    /**
     * Allowed attendance statuses.
     */
    protected function statuses(): array
    {
        return ['present', 'absent', 'late', 'excused', 'sick_leave', 'vacation'];
    }
}
