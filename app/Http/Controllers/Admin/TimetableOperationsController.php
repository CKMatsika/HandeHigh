<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use App\Services\Timetable\Operations\TimetableOperationalChangeService;
use App\Services\Timetable\Operations\TimetableSyncService;
use App\Services\Timetable\Operations\TimetableTodayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableOperationsController extends Controller
{
    public function __construct(
        protected TimetableOperationalChangeService $changeService,
        protected TimetableTodayService $todayService,
        protected TimetableSyncService $syncService
    ) {}

    /**
     * Master Operational Timetable Dashboard.
     */
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $activeTimetable = $this->todayService->getActiveTimetable($schoolId);

        $selectedTimetableId = $request->get('timetable_id', $activeTimetable?->id);
        $timetable = $selectedTimetableId ? Timetable::where('school_id', $schoolId)->find($selectedTimetableId) : $activeTimetable;

        $dateStr = $request->get('date', now()->toDateString());
        $dateObj = Carbon::parse($dateStr);
        $dayOfWeek = $request->get('day', $dateObj->format('l'));
        $viewMode = $request->get('view', 'day'); // 'day', 'week', 'matrix'

        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('name')->get();
        $teachers = Teacher::where('school_id', $schoolId)->where('status', true)->orderBy('first_name')->get();
        $rooms = Room::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name')->get();
        $periods = SchoolPeriod::where('school_id', $schoolId)->where('is_active', true)->orderBy('sort_order')->get();
        $allTimetables = Timetable::where('school_id', $schoolId)->latest()->get();

        // Operational stats
        $absencesCount = TeacherAbsence::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->count();

        $pendingSubsCount = TimetableSubstitution::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->where('date', $dateStr)
            ->count();

        $approvedSubsCount = TimetableSubstitution::where('school_id', $schoolId)
            ->where('status', 'approved')
            ->where('date', $dateStr)
            ->count();

        $slotsQuery = $timetable ? $timetable->slots()->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod', 'substitutions' => fn ($q) => $q->where('date', $dateStr)->where('status', 'approved')->with('substituteTeacher')]) : null;

        if ($slotsQuery) {
            if ($viewMode === 'day') {
                $slotsQuery->where('day_of_week', $dayOfWeek);
            }
            if ($request->filled('school_class_id')) {
                $slotsQuery->where('school_class_id', $request->school_class_id);
            }
            if ($request->filled('teacher_id')) {
                $slotsQuery->where('teacher_id', $request->teacher_id);
            }
            if ($request->filled('room_id')) {
                $slotsQuery->where('room_id', $request->room_id);
            }
            if ($request->filled('subject_id')) {
                $slotsQuery->where('subject_id', $request->subject_id);
            }
            if ($request->filled('status')) {
                $slotsQuery->where('status', $request->status);
            }
            $slots = $slotsQuery->orderBy('start_time')->get();
        } else {
            $slots = collect();
        }

        return view('admin.timetables.operations', [
            'timetable' => $timetable,
            'allTimetables' => $allTimetables,
            'date' => $dateStr,
            'day' => $dayOfWeek,
            'viewMode' => $viewMode,
            'classes' => $classes,
            'teachers' => $teachers,
            'rooms' => $rooms,
            'subjects' => $subjects,
            'periods' => $periods,
            'slots' => $slots,
            'stats' => [
                'absent_teachers' => $absencesCount,
                'pending_substitutions' => $pendingSubsCount,
                'approved_substitutions' => $approvedSubsCount,
                'total_slots' => $slots->count(),
                'cancelled_slots' => $slots->where('status', 'cancelled')->count(),
            ],
        ]);
    }

    /**
     * Change teacher for a lesson slot.
     */
    public function changeTeacher(Request $request, Timetable $timetable, TimetableSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'new_teacher_id' => 'required|exists:teachers,id',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'expected_revision' => 'nullable|integer',
        ]);

        $newTeacher = Teacher::where('school_id', $timetable->school_id)->findOrFail($validated['new_teacher_id']);

        try {
            $change = $this->changeService->changeTeacher(
                timetable: $timetable,
                slot: $slot,
                newTeacher: $newTeacher,
                reason: $validated['reason'],
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                expectedRevision: $validated['expected_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Teacher updated successfully.',
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Change room for a lesson slot.
     */
    public function changeRoom(Request $request, Timetable $timetable, TimetableSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'new_room_id' => 'required|exists:rooms,id',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'expected_revision' => 'nullable|integer',
        ]);

        $newRoom = Room::where('school_id', $timetable->school_id)->findOrFail($validated['new_room_id']);

        try {
            $change = $this->changeService->changeRoom(
                timetable: $timetable,
                slot: $slot,
                newRoom: $newRoom,
                reason: $validated['reason'],
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                expectedRevision: $validated['expected_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully.',
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Move a lesson slot.
     */
    public function moveLesson(Request $request, Timetable $timetable, TimetableSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'new_day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
            'new_school_period_id' => 'nullable|exists:school_periods,id',
            'new_room_id' => 'nullable|exists:rooms,id',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'expected_revision' => 'nullable|integer',
        ]);

        $newPeriod = ! empty($validated['new_school_period_id']) ? SchoolPeriod::where('school_id', $timetable->school_id)->findOrFail($validated['new_school_period_id']) : null;
        $newRoom = ! empty($validated['new_room_id']) ? Room::where('school_id', $timetable->school_id)->findOrFail($validated['new_room_id']) : null;

        try {
            $change = $this->changeService->moveLesson(
                timetable: $timetable,
                slot: $slot,
                newDay: $validated['new_day_of_week'],
                newStartTime: $validated['new_start_time'],
                newEndTime: $validated['new_end_time'],
                newPeriod: $newPeriod,
                newRoom: $newRoom,
                reason: $validated['reason'],
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                expectedRevision: $validated['expected_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Lesson moved successfully.',
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a lesson slot.
     */
    public function cancelLesson(Request $request, Timetable $timetable, TimetableSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'expected_revision' => 'nullable|integer',
        ]);

        try {
            $change = $this->changeService->cancelLesson(
                timetable: $timetable,
                slot: $slot,
                reason: $validated['reason'],
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                expectedRevision: $validated['expected_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Lesson cancelled successfully.',
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Restore a cancelled lesson slot.
     */
    public function restoreLesson(Request $request, Timetable $timetable, TimetableSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'expected_revision' => 'nullable|integer',
        ]);

        try {
            $change = $this->changeService->restoreLesson(
                timetable: $timetable,
                slot: $slot,
                reason: $validated['reason'] ?? 'Restored lesson',
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                expectedRevision: $validated['expected_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Lesson restored successfully.',
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * View diff & revision history.
     */
    public function changes(Request $request, Timetable $timetable): View
    {
        $history = $this->syncService->getChangeHistory($timetable, 100);
        $version = $this->syncService->getVersion($timetable);

        return view('admin.timetables.changes', [
            'timetable' => $timetable,
            'history' => $history,
            'version' => $version,
        ]);
    }
}
