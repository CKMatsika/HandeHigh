<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Services\Timetable\Operations\TimetableOperationalChangeService;
use App\Services\Timetable\Operations\TimetableSyncService;
use App\Services\Timetable\Operations\TimetableTodayService;
use App\Services\Timetable\Substitution\SubstitutionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableAPIController extends Controller
{
    public function __construct(
        protected TimetableTodayService $todayService,
        protected TimetableSyncService $syncService,
        protected TimetableOperationalChangeService $changeService,
        protected SubstitutionService $substitutionService
    ) {}

    /**
     * GET /api/timetable/version
     */
    public function version(Request $request, ?Timetable $timetable = null): JsonResponse
    {
        $target = $timetable ?? $this->todayService->getActiveTimetable($request->user()->school_id);
        if (! $target) {
            return response()->json(['error' => 'No active timetable found.'], 404);
        }

        return response()->json($this->syncService->getVersion($target));
    }

    /**
     * GET /api/timetable/changes?since_revision=X
     */
    public function changes(Request $request, Timetable $timetable): JsonResponse
    {
        $sinceRevision = (int) $request->get('since_revision', 0);
        $changes = $this->syncService->getChangesSince($timetable, $sinceRevision);

        return response()->json([
            'timetable_id' => $timetable->id,
            'current_revision' => $timetable->revision,
            'since_revision' => $sinceRevision,
            'changes' => $changes,
        ]);
    }

    /**
     * GET /api/timetable/today
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $dateStr = $request->get('date', now()->toDateString());
        $dateObj = Carbon::parse($dateStr);
        $timetable = $this->todayService->getActiveTimetable($user->school_id);

        if (! $timetable) {
            return response()->json(['error' => 'No active timetable found.'], 404);
        }

        // Return persona-specific schedule
        if ($user->hasRole('teacher')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                return response()->json($this->todayService->getTeacherToday($teacher, $timetable, $dateObj));
            }
        }

        if ($user->hasRole('student')) {
            $student = Student::where('user_id', $user->id)->first();
            $enrollment = $student?->enrollments()->latest('enrollment_date')->first();
            $class = $enrollment?->class ?? SchoolClass::where('school_id', $user->school_id)->where('name', $student?->class_name)->first();
            if ($class) {
                return response()->json($this->todayService->getClassToday($class, $timetable, $dateObj));
            }
        }

        // Default admin view: full today schedule
        $schedule = $this->todayService->getTodaySchedule($timetable, $dateObj->format('l'), $dateObj);
        return response()->json([
            'timetable_id' => $timetable->id,
            'date' => $dateStr,
            'day' => $dateObj->format('l'),
            'slots' => $schedule,
        ]);
    }

    /**
     * GET /api/timetable/class/{class}
     */
    public function classSchedule(Request $request, SchoolClass $class): JsonResponse
    {
        if ($class->school_id !== $request->user()->school_id) {
            return response()->json(['error' => 'Unauthorized cross-tenant access.'], 403);
        }

        $dateStr = $request->get('date', now()->toDateString());
        $timetable = $this->todayService->getActiveTimetable($class->school_id);

        return response()->json($this->todayService->getClassToday($class, $timetable, Carbon::parse($dateStr)));
    }

    /**
     * GET /api/timetable/teacher/{teacher}
     */
    public function teacherSchedule(Request $request, Teacher $teacher): JsonResponse
    {
        if ($teacher->school_id !== $request->user()->school_id) {
            return response()->json(['error' => 'Unauthorized cross-tenant access.'], 403);
        }

        $dateStr = $request->get('date', now()->toDateString());
        $timetable = $this->todayService->getActiveTimetable($teacher->school_id);

        return response()->json($this->todayService->getTeacherToday($teacher, $timetable, Carbon::parse($dateStr)));
    }

    /**
     * GET /api/timetable/room/{room}
     */
    public function roomSchedule(Request $request, Room $room): JsonResponse
    {
        if ($room->school_id !== $request->user()->school_id) {
            return response()->json(['error' => 'Unauthorized cross-tenant access.'], 403);
        }

        $timetable = $this->todayService->getActiveTimetable($room->school_id);
        if (! $timetable) {
            return response()->json(['error' => 'No active timetable.'], 404);
        }

        $slots = $timetable->slots()
            ->where('room_id', $room->id)
            ->where('status', '!=', 'cancelled')
            ->with(['schoolClass', 'subject', 'teacher', 'schoolPeriod'])
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'room' => $room,
            'slots' => $slots,
        ]);
    }

    /**
     * POST /api/timetable/{timetable}/slots/{slot}/teacher
     */
    public function changeTeacher(Request $request, Timetable $timetable, TimetableSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'new_teacher_id' => 'required|exists:teachers,id',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'expected_revision' => 'nullable|integer',
        ]);

        $teacher = Teacher::where('school_id', $timetable->school_id)->findOrFail($validated['new_teacher_id']);

        try {
            $change = $this->changeService->changeTeacher(
                timetable: $timetable,
                slot: $slot,
                newTeacher: $teacher,
                reason: $validated['reason'],
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                expectedRevision: $validated['expected_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/timetable/{timetable}/slots/{slot}/room
     */
    public function changeRoom(Request $request, Timetable $timetable, TimetableSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'new_room_id' => 'required|exists:rooms,id',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'expected_revision' => 'nullable|integer',
        ]);

        $room = Room::where('school_id', $timetable->school_id)->findOrFail($validated['new_room_id']);

        try {
            $change = $this->changeService->changeRoom(
                timetable: $timetable,
                slot: $slot,
                newRoom: $room,
                reason: $validated['reason'],
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                expectedRevision: $validated['expected_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/timetable/{timetable}/slots/{slot}/move
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
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/timetable/{timetable}/slots/{slot}/cancel
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
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/timetable/{timetable}/slots/{slot}/restore
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
                'change' => $change,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/timetable/slots/{slot}/substitutes
     */
    public function recommendSubstitutes(Request $request, TimetableSlot $slot): JsonResponse
    {
        $dateStr = $request->get('date', now()->toDateString());
        $recommendations = $this->substitutionService->recommendSubstitutes($slot, $dateStr);

        return response()->json([
            'slot_id' => $slot->id,
            'date' => $dateStr,
            'recommendations' => $recommendations->map(fn ($r) => $r->toArray()),
        ]);
    }

    /**
     * POST /api/timetable/{timetable}/slots/{slot}/substitute
     */
    public function assignSubstitute(Request $request, Timetable $timetable, TimetableSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'substitute_teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'teacher_absence_id' => 'nullable|exists:teacher_absences,id',
            'expected_revision' => 'nullable|integer',
        ]);

        $substitute = Teacher::where('school_id', $timetable->school_id)->findOrFail($validated['substitute_teacher_id']);
        $absence = ! empty($validated['teacher_absence_id']) ? TeacherAbsence::where('school_id', $timetable->school_id)->find($validated['teacher_absence_id']) : null;

        try {
            $substitution = $this->changeService->assignSubstitute(
                timetable: $timetable,
                slot: $slot,
                substituteTeacher: $substitute,
                date: $validated['date'],
                reason: $validated['reason'] ?? 'Assigned substitute',
                notes: $validated['notes'] ?? null,
                actor: $request->user(),
                absence: $absence,
                expectedRevision: $validated['expected_revision'] ?? null
            );

            return response()->json([
                'success' => true,
                'substitution' => $substitution,
                'revision' => $timetable->fresh()->revision,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
