<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\Timetable\Operations\TimetableTodayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherTimetableController extends Controller
{
    public function __construct(
        protected TimetableTodayService $todayService
    ) {}

    /**
     * Teacher's My Timetable view (Today, Week, Substitutions, Free Periods).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            abort(403, 'Teacher profile not found for the authenticated user.');
        }

        $dateStr = $request->get('date', now()->toDateString());
        $dateObj = Carbon::parse($dateStr);
        $timetable = $this->todayService->getActiveTimetable($user->school_id);

        $todayData = $this->todayService->getTeacherToday($teacher, $timetable, $dateObj);

        // Weekly schedule
        $weeklySlots = $timetable ? $timetable->slots()
            ->where('teacher_id', $teacher->id)
            ->with(['schoolClass', 'subject', 'room', 'schoolPeriod'])
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week') : collect();

        return view('portal.teacher.timetable', [
            'teacher' => $teacher,
            'timetable' => $timetable,
            'date' => $dateStr,
            'todayData' => $todayData,
            'weeklySlots' => $weeklySlots,
        ]);
    }

    /**
     * API/JSON endpoint for teacher's today schedule.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            return response()->json(['error' => 'Teacher profile not found.'], 404);
        }

        $dateStr = $request->get('date', now()->toDateString());
        $dateObj = Carbon::parse($dateStr);
        $timetable = $this->todayService->getActiveTimetable($user->school_id);

        $todayData = $this->todayService->getTeacherToday($teacher, $timetable, $dateObj);

        return response()->json([
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->full_name,
            ],
            'date' => $todayData['date'],
            'day' => $todayData['day'],
            'current_lesson' => $todayData['current_lesson'] ? [
                'subject' => $todayData['current_lesson']->subject?->name,
                'class' => $todayData['current_lesson']->schoolClass?->name,
                'room' => $todayData['current_lesson']->room?->name,
                'time' => $todayData['current_lesson']->getFormattedTime(),
                'is_substitute' => $todayData['current_lesson']->is_substitute_duty ?? false,
            ] : null,
            'next_lesson' => $todayData['next_lesson'] ? [
                'subject' => $todayData['next_lesson']->subject?->name,
                'class' => $todayData['next_lesson']->schoolClass?->name,
                'room' => $todayData['next_lesson']->room?->name,
                'time' => $todayData['next_lesson']->getFormattedTime(),
                'is_substitute' => $todayData['next_lesson']->is_substitute_duty ?? false,
            ] : null,
            'lessons' => $todayData['lessons']->map(fn ($l) => [
                'id' => $l->id,
                'subject' => $l->subject?->name,
                'class' => $l->schoolClass?->name,
                'room' => $l->room?->name,
                'start_time' => $l->start_time,
                'end_time' => $l->end_time,
                'status' => $l->status,
                'is_substitute' => $l->is_substitute_duty ?? false,
            ]),
            'free_periods_count' => $todayData['free_periods']->count(),
        ]);
    }
}
