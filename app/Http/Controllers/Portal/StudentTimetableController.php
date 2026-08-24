<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Timetable\Operations\TimetableTodayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentTimetableController extends Controller
{
    public function __construct(
        protected TimetableTodayService $todayService
    ) {}

    /**
     * Student's My Class Timetable view.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (! $student) {
            abort(403, 'Student profile not found for the authenticated user.');
        }

        $latestEnrollment = $student->enrollments()->latest('enrollment_date')->first();
        $schoolClass = $latestEnrollment?->class
            ?? SchoolClass::where('school_id', $user->school_id)->where('name', $student->class_name)->first();

        $dateStr = $request->get('date', now()->toDateString());
        $dateObj = Carbon::parse($dateStr);
        $timetable = $this->todayService->getActiveTimetable($user->school_id);

        $todayData = $schoolClass ? $this->todayService->getClassToday($schoolClass, $timetable, $dateObj) : [
            'timetable' => $timetable,
            'day' => $dateObj->format('l'),
            'date' => $dateStr,
            'lessons' => collect(),
            'current_lesson' => null,
            'next_lesson' => null,
            'remaining_lessons' => collect(),
            'completed_lessons' => collect(),
        ];

        // Weekly schedule
        $weeklySlots = ($timetable && $schoolClass) ? $timetable->slots()
            ->where('school_class_id', $schoolClass->id)
            ->with(['subject', 'teacher', 'room', 'schoolPeriod'])
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week') : collect();

        return view('portal.student.timetable', [
            'student' => $student,
            'schoolClass' => $schoolClass,
            'timetable' => $timetable,
            'date' => $dateStr,
            'todayData' => $todayData,
            'weeklySlots' => $weeklySlots,
        ]);
    }

    /**
     * API/JSON endpoint for student's class schedule.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (! $student) {
            return response()->json(['error' => 'Student profile not found.'], 404);
        }

        $latestEnrollment = $student->enrollments()->latest('enrollment_date')->first();
        $schoolClass = $latestEnrollment?->class
            ?? SchoolClass::where('school_id', $user->school_id)->where('name', $student->class_name)->first();

        if (! $schoolClass) {
            return response()->json(['error' => 'Class assignment not found.'], 404);
        }

        $dateStr = $request->get('date', now()->toDateString());
        $dateObj = Carbon::parse($dateStr);
        $timetable = $this->todayService->getActiveTimetable($user->school_id);

        $todayData = $this->todayService->getClassToday($schoolClass, $timetable, $dateObj);

        return response()->json([
            'class' => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
            ],
            'date' => $todayData['date'],
            'day' => $todayData['day'],
            'current_lesson' => $todayData['current_lesson'] ? [
                'subject' => $todayData['current_lesson']->subject?->name,
                'teacher' => $todayData['current_lesson']->active_substitute?->full_name ?? $todayData['current_lesson']->teacher?->full_name,
                'room' => $todayData['current_lesson']->room?->name,
                'time' => $todayData['current_lesson']->getFormattedTime(),
                'is_substituted' => $todayData['current_lesson']->is_substituted,
            ] : null,
            'next_lesson' => $todayData['next_lesson'] ? [
                'subject' => $todayData['next_lesson']->subject?->name,
                'teacher' => $todayData['next_lesson']->active_substitute?->full_name ?? $todayData['next_lesson']->teacher?->full_name,
                'room' => $todayData['next_lesson']->room?->name,
                'time' => $todayData['next_lesson']->getFormattedTime(),
                'is_substituted' => $todayData['next_lesson']->is_substituted,
            ] : null,
            'lessons' => $todayData['lessons']->map(fn ($l) => [
                'id' => $l->id,
                'subject' => $l->subject?->name,
                'teacher' => $l->active_substitute?->full_name ?? $l->teacher?->full_name,
                'room' => $l->room?->name,
                'start_time' => $l->start_time,
                'end_time' => $l->end_time,
                'status' => $l->status,
                'is_substituted' => $l->is_substituted,
            ]),
        ]);
    }
}
