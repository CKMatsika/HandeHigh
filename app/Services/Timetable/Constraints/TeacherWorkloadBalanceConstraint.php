<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class TeacherWorkloadBalanceConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $teacherDailyCounts = [];

        foreach ($slots as $slot) {
            if (! $slot->teacher_id || $slot->status === 'cancelled') {
                continue;
            }

            $teacherId = $slot->teacher_id;
            $day = (string) $slot->day_of_week;

            $teacherDailyCounts[$teacherId][$day] = ($teacherDailyCounts[$teacherId][$day] ?? 0) + 1;
        }

        foreach ($teacherDailyCounts as $teacherId => $days) {
            $maxPeriods = ! empty($days) ? max($days) : 0;
            if ($maxPeriods >= 7) {
                $teacher = $slots->firstWhere('teacher_id', $teacherId)?->teacher;
                $teacherName = $teacher?->full_name ?? "Teacher #{$teacherId}";

                $conflicts[] = TimetableConflict::create(
                    type: 'TEACHER_OVERLOAD_WARNING',
                    severity: TimetableConflict::SEVERITY_WARNING,
                    message: "Teacher {$teacherName} has {$maxPeriods} periods scheduled on a single day. Consider rebalancing.",
                    details: [
                        'teacher_id' => $teacherId,
                        'daily_breakdown' => $days,
                        'max_daily' => $maxPeriods,
                    ],
                    teacherId: $teacherId
                );
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'TEACHER_WORKLOAD_BALANCE';
    }

    public function getSeverity(): string
    {
        return TimetableConflict::SEVERITY_SOFT;
    }

    public function getWeight(): int
    {
        return 15;
    }

    public function getName(): string
    {
        return 'Teacher Workload Daily Balance';
    }
}
