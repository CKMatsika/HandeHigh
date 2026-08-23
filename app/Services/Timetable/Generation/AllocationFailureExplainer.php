<?php

namespace App\Services\Timetable\Generation;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use Illuminate\Support\Collection;

class AllocationFailureExplainer
{
    /**
     * Diagnose why a requirement could not be fully allocated.
     *
     * @param  Timetable  $timetable
     * @param  array  $requirement (class_id, subject_id, teacher_id, room_id, required, allocated)
     * @param  Collection  $currentSlots (currently allocated temporary/permanent slots)
     * @param  array  $context (fixedActivities, examinations, periods, days)
     * @return array
     */
    public function explainFailure(
        Timetable $timetable,
        array $requirement,
        Collection $currentSlots,
        array $context = []
    ): array {
        $classId = $requirement['school_class_id'] ?? $requirement['class_id'];
        $subjectId = $requirement['subject_id'];
        $teacherId = $requirement['teacher_id'] ?? null;
        $roomId = $requirement['room_id'] ?? null;
        $required = $requirement['weekly_periods'] ?? 1;
        $allocated = $requirement['allocated_count'] ?? 0;

        $class = SchoolClass::find($classId);
        $subject = Subject::find($subjectId);
        $teacher = $teacherId ? Teacher::find($teacherId) : null;

        $className = $class?->name ?? "Class #{$classId}";
        $subjectName = $subject?->name ?? "Subject #{$subjectId}";
        $teacherName = $teacher?->full_name ?? ($teacherId ? "Teacher #{$teacherId}" : 'Unassigned');

        $reasons = [];

        $days = $context['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periods = $context['periods'] ?? collect();
        $fixedActivities = $context['fixed_activities'] ?? collect();
        $examinations = $context['examinations'] ?? collect();

        $occupiedByClass = 0;
        $occupiedByTeacher = 0;
        $occupiedByRoom = 0;
        $blockedByActivities = 0;
        $blockedByExams = 0;
        $blockedByLocked = 0;

        foreach ($days as $day) {
            foreach ($periods as $period) {
                // Check class occupancy
                $classSlot = $currentSlots->first(function ($s) use ($classId, $day, $period) {
                    return (int) $s->school_class_id === (int) $classId
                        && strcasecmp($s->day_of_week, $day) === 0
                        && ((int) $s->school_period_id === (int) $period->id || $s->start_time === $period->start_time);
                });

                if ($classSlot) {
                    $occupiedByClass++;
                    if ($classSlot->isLocked()) {
                        $blockedByLocked++;
                    }
                }

                // Check teacher occupancy
                if ($teacherId) {
                    $teacherSlot = $currentSlots->first(function ($s) use ($teacherId, $day, $period) {
                        return (int) $s->teacher_id === (int) $teacherId
                            && strcasecmp($s->day_of_week, $day) === 0
                            && ((int) $s->school_period_id === (int) $period->id || $s->start_time === $period->start_time);
                    });
                    if ($teacherSlot) {
                        $occupiedByTeacher++;
                    }
                }

                // Check room occupancy
                if ($roomId) {
                    $roomSlot = $currentSlots->first(function ($s) use ($roomId, $day, $period) {
                        return (int) $s->room_id === (int) $roomId
                            && strcasecmp($s->day_of_week, $day) === 0
                            && ((int) $s->school_period_id === (int) $period->id || $s->start_time === $period->start_time);
                    });
                    if ($roomSlot) {
                        $occupiedByRoom++;
                    }
                }

                // Check fixed activity block
                $hasFixed = $fixedActivities->first(function ($fa) use ($classId, $day, $period) {
                    if (strcasecmp($fa->day_of_week, $day) !== 0) {
                        return false;
                    }
                    if ($fa->school_class_id && (int) $fa->school_class_id !== (int) $classId) {
                        return false;
                    }

                    return (int) $fa->school_period_id === (int) $period->id || $fa->start_time === $period->start_time;
                });
                if ($hasFixed) {
                    $blockedByActivities++;
                }

                // Check examination block
                $hasExam = $examinations->first(function ($ex) use ($classId, $day, $period) {
                    if (strcasecmp($ex->day_of_week, $day) !== 0) {
                        return false;
                    }
                    if ($ex->school_class_id && (int) $ex->school_class_id !== (int) $classId) {
                        return false;
                    }

                    return (int) $ex->school_period_id === (int) $period->id || $ex->start_time === $period->start_time;
                });
                if ($hasExam) {
                    $blockedByExams++;
                }
            }
        }

        if ($occupiedByTeacher > 0 && $teacherId) {
            $reasons[] = "Teacher {$teacherName} is already scheduled in {$occupiedByTeacher} matching period(s).";
        }
        if ($occupiedByClass > 0) {
            $reasons[] = "Class {$className} schedule grid is already filled in {$occupiedByClass} available period(s).";
        }
        if ($occupiedByRoom > 0 && $roomId) {
            $reasons[] = "Target room #{$roomId} is occupied across {$occupiedByRoom} period(s).";
        }
        if ($blockedByActivities > 0) {
            $reasons[] = "{$blockedByActivities} period(s) are blocked by scheduled fixed school activities (e.g. assembly/sports).";
        }
        if ($blockedByExams > 0) {
            $reasons[] = "{$blockedByExams} period(s) are reserved for examination or national examination sessions.";
        }
        if ($blockedByLocked > 0) {
            $reasons[] = "{$blockedByLocked} period(s) contain administrator-locked allocations that cannot be overwritten.";
        }

        if (empty($reasons)) {
            $reasons[] = "All available time periods on configured school days exceed maximum daily limits or have simultaneous hard constraint overlaps.";
        }

        $summary = "Unable to fully allocate {$subjectName} for {$className} (Required: {$required}, Allocated: {$allocated}). " . implode(' ', $reasons);

        return [
            'class_id' => $classId,
            'class_name' => $className,
            'subject_id' => $subjectId,
            'subject_name' => $subjectName,
            'teacher_id' => $teacherId,
            'teacher_name' => $teacherName,
            'required_periods' => $required,
            'allocated_periods' => $allocated,
            'deficit' => $required - $allocated,
            'reasons' => $reasons,
            'summary' => $summary,
        ];
    }
}
