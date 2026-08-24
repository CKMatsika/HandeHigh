<?php

namespace App\Services\Timetable\Operations;

use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimetableTodayService
{
    /**
     * Get the active published operational timetable for a school.
     */
    public function getActiveTimetable(int $schoolId): ?Timetable
    {
        return Timetable::where('school_id', $schoolId)
            ->where(function ($q) {
                $q->where('status', 'published')
                    ->orWhere('is_operational', true);
            })
            ->latest('published_at')
            ->first();
    }

    /**
     * Get today's full school schedule for a timetable.
     */
    public function getTodaySchedule(Timetable $timetable, ?string $dayOfWeek = null, ?Carbon $date = null): Collection
    {
        $dateObj = $date ?? now();
        $day = $dayOfWeek ?? $dateObj->format('l');
        $dateStr = $dateObj->toDateString();

        $slots = $timetable->slots()
            ->where('day_of_week', $day)
            ->with([
                'schoolClass',
                'subject',
                'teacher',
                'room',
                'schoolPeriod',
                'substitutions' => fn ($q) => $q->where('date', $dateStr)->where('status', 'approved')->with('substituteTeacher'),
            ])
            ->orderBy('start_time')
            ->get();

        // Attach active substitute metadata if present
        return $slots->map(function ($slot) {
            $sub = $slot->substitutions->first();
            $slot->active_substitute = $sub?->substituteTeacher;
            $slot->is_substituted = $sub !== null;
            return $slot;
        });
    }

    /**
     * Get today's lessons for a specific teacher, including substitute duties.
     */
    public function getTeacherToday(Teacher $teacher, ?Timetable $timetable = null, ?Carbon $date = null): array
    {
        $dateObj = $date ?? now();
        $day = $dateObj->format('l');
        $dateStr = $dateObj->toDateString();

        $activeTimetable = $timetable ?? $this->getActiveTimetable($teacher->school_id);
        if (! $activeTimetable) {
            return [
                'timetable' => null,
                'day' => $day,
                'date' => $dateStr,
                'lessons' => collect(),
                'substitutions' => collect(),
                'current_lesson' => null,
                'next_lesson' => null,
                'remaining_lessons' => collect(),
                'completed_lessons' => collect(),
                'free_periods' => collect(),
            ];
        }

        // 1. Regular assigned slots
        $regularSlots = $activeTimetable->slots()
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $day)
            ->with([
                'schoolClass',
                'subject',
                'room',
                'schoolPeriod',
                'substitutions' => fn ($q) => $q->where('date', $dateStr)->where('status', 'approved')->with('substituteTeacher'),
            ])
            ->orderBy('start_time')
            ->get();

        // 2. Slots where teacher is covering as substitute
        $substituteSlots = $activeTimetable->slots()
            ->where('day_of_week', $day)
            ->whereHas('substitutions', function ($q) use ($teacher, $dateStr) {
                $q->where('substitute_teacher_id', $teacher->id)
                    ->where('date', $dateStr)
                    ->where('status', 'approved');
            })
            ->with([
                'schoolClass',
                'subject',
                'teacher',
                'room',
                'schoolPeriod',
                'substitutions' => fn ($q) => $q->where('date', $dateStr)->where('status', 'approved')->with('substituteTeacher'),
            ])
            ->get();

        $allLessons = $regularSlots->concat($substituteSlots)
            ->unique('id')
            ->sortBy('start_time')
            ->values()
            ->map(function ($slot) use ($teacher) {
                $sub = $slot->substitutions->first();
                $slot->active_substitute = $sub?->substituteTeacher;
                $slot->is_substituted = $sub !== null;
                $slot->is_substitute_duty = ($sub && $sub->substitute_teacher_id === $teacher->id);
                return $slot;
            });

        $timing = $this->calculateLessonTimings($allLessons, $dateObj);

        // Calculate free periods
        $allPeriods = SchoolPeriod::where('school_id', $teacher->school_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $busyPeriodIds = $allLessons->where('status', '!=', 'cancelled')->pluck('school_period_id')->filter()->toArray();
        $freePeriods = $allPeriods->filter(fn ($p) => $p->isLesson() && ! in_array($p->id, $busyPeriodIds))->values();

        return [
            'timetable' => $activeTimetable,
            'day' => $day,
            'date' => $dateStr,
            'lessons' => $allLessons,
            'substitutions' => $substituteSlots,
            'current_lesson' => $timing['current'],
            'next_lesson' => $timing['next'],
            'remaining_lessons' => $timing['remaining'],
            'completed_lessons' => $timing['completed'],
            'free_periods' => $freePeriods,
        ];
    }

    /**
     * Get today's lessons for a class.
     */
    public function getClassToday(SchoolClass $schoolClass, ?Timetable $timetable = null, ?Carbon $date = null): array
    {
        $dateObj = $date ?? now();
        $day = $dateObj->format('l');
        $dateStr = $dateObj->toDateString();

        $activeTimetable = $timetable ?? $this->getActiveTimetable($schoolClass->school_id);
        if (! $activeTimetable) {
            return [
                'timetable' => null,
                'day' => $day,
                'date' => $dateStr,
                'lessons' => collect(),
                'current_lesson' => null,
                'next_lesson' => null,
                'remaining_lessons' => collect(),
                'completed_lessons' => collect(),
            ];
        }

        $lessons = $activeTimetable->slots()
            ->where('school_class_id', $schoolClass->id)
            ->where('day_of_week', $day)
            ->with([
                'subject',
                'teacher',
                'room',
                'schoolPeriod',
                'substitutions' => fn ($q) => $q->where('date', $dateStr)->where('status', 'approved')->with('substituteTeacher'),
            ])
            ->orderBy('start_time')
            ->get()
            ->map(function ($slot) {
                $sub = $slot->substitutions->first();
                $slot->active_substitute = $sub?->substituteTeacher;
                $slot->is_substituted = $sub !== null;
                return $slot;
            });

        $timing = $this->calculateLessonTimings($lessons, $dateObj);

        return [
            'timetable' => $activeTimetable,
            'day' => $day,
            'date' => $dateStr,
            'lessons' => $lessons,
            'current_lesson' => $timing['current'],
            'next_lesson' => $timing['next'],
            'remaining_lessons' => $timing['remaining'],
            'completed_lessons' => $timing['completed'],
        ];
    }

    /**
     * Get room occupancy overview for today.
     */
    public function getRoomOccupancyToday(int $schoolId, ?Timetable $timetable = null, ?Carbon $date = null): array
    {
        $dateObj = $date ?? now();
        $day = $dateObj->format('l');
        $dateStr = $dateObj->toDateString();

        $activeTimetable = $timetable ?? $this->getActiveTimetable($schoolId);
        $rooms = Room::where('school_id', $schoolId)->where('is_active', true)->get();
        $periods = SchoolPeriod::where('school_id', $schoolId)->where('is_active', true)->orderBy('sort_order')->get();

        if (! $activeTimetable) {
            return [
                'rooms' => $rooms,
                'periods' => $periods,
                'matrix' => [],
            ];
        }

        $slots = $activeTimetable->slots()
            ->where('day_of_week', $day)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('room_id')
            ->with(['schoolClass', 'subject', 'teacher'])
            ->get();

        $matrix = [];
        foreach ($rooms as $room) {
            $matrix[$room->id] = [];
            foreach ($periods as $period) {
                $occupiedSlot = $slots->first(function ($s) use ($room, $period) {
                    return $s->room_id === $room->id && ($s->school_period_id === $period->id || (
                        $s->start_time < $period->end_time && $s->end_time > $period->start_time
                    ));
                });
                $matrix[$room->id][$period->id] = $occupiedSlot;
            }
        }

        return [
            'rooms' => $rooms,
            'periods' => $periods,
            'matrix' => $matrix,
        ];
    }

    /**
     * Compute current, next, completed, and remaining lessons based on time.
     */
    public function calculateLessonTimings(Collection $slots, Carbon $now): array
    {
        $currentTime = $now->format('H:i:s');
        $activeSlots = $slots->where('status', '!=', 'cancelled')->values();

        $currentLesson = null;
        $nextLesson = null;
        $completed = collect();
        $remaining = collect();

        foreach ($activeSlots as $slot) {
            $start = substr((string) $slot->start_time, 0, 8);
            $end = substr((string) $slot->end_time, 0, 8);

            if ($start <= $currentTime && $currentTime < $end) {
                $currentLesson = $slot;
            } elseif ($currentTime < $start) {
                if ($nextLesson === null) {
                    $nextLesson = $slot;
                }
                $remaining->push($slot);
            } else {
                $completed->push($slot);
            }
        }

        return [
            'current' => $currentLesson,
            'next' => $nextLesson,
            'remaining' => $remaining,
            'completed' => $completed,
        ];
    }
}
