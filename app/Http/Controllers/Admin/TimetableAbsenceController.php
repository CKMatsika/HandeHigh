<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolPeriod;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Services\Timetable\Substitution\TeacherAbsenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableAbsenceController extends Controller
{
    public function __construct(
        protected TeacherAbsenceService $absenceService
    ) {}

    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        $query = TeacherAbsence::where('school_id', $schoolId)->with(['teacher', 'recordedBy', 'substitutions']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('date')) {
            $query->where('start_date', '<=', $request->date)
                ->where('end_date', '>=', $request->date);
        }

        $absences = $query->latest('start_date')->paginate(15);
        $teachers = Teacher::where('school_id', $schoolId)->where('status', true)->orderBy('first_name')->get();
        $periods = SchoolPeriod::where('school_id', $schoolId)->where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.timetables.absences.index', [
            'absences' => $absences,
            'teachers' => $teachers,
            'periods' => $periods,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:255',
            'affected_period_ids' => 'nullable|array',
            'affected_period_ids.*' => 'exists:school_periods,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->absenceService->recordAbsence(
            schoolId: $request->user()->school_id,
            teacherId: (int) $validated['teacher_id'],
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            reason: $validated['reason'],
            affectedPeriodIds: $validated['affected_period_ids'] ?? null,
            notes: $validated['notes'] ?? null,
            recorder: $request->user()
        );

        return redirect()->route('admin.timetables.absences.index')->with('success', 'Teacher absence recorded successfully.');
    }

    public function show(Request $request, TeacherAbsence $absence): View|JsonResponse
    {
        $affectedSlots = $this->absenceService->findAffectedSlots($absence);

        if ($request->wantsJson()) {
            return response()->json([
                'absence' => $absence->load('teacher'),
                'affected_slots' => $affectedSlots,
            ]);
        }

        return view('admin.timetables.absences.show', [
            'absence' => $absence,
            'affectedSlots' => $affectedSlots,
        ]);
    }

    public function cancel(Request $request, TeacherAbsence $absence): RedirectResponse
    {
        $absence->status = 'cancelled';
        $absence->save();

        return redirect()->back()->with('success', 'Absence marked as cancelled.');
    }
}
