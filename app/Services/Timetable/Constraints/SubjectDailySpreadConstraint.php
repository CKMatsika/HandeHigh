<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class SubjectDailySpreadConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $counts = [];

        foreach ($slots as $slot) {
            if ($slot->status === 'cancelled' || ! $slot->isLesson()) {
                continue;
            }

            $day = (string) $slot->day_of_week;
            $key = "{$slot->school_class_id}_{$slot->subject_id}_{$day}";
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            $slotMap[$key][] = $slot;
        }

        foreach ($counts as $key => $count) {
            if ($count > 2) {
                $sampleSlot = $slotMap[$key][0];
                $className = $sampleSlot->schoolClass?->name ?? "Class #{$sampleSlot->school_class_id}";
                $subjectName = $sampleSlot->subject?->name ?? "Subject #{$sampleSlot->subject_id}";
                $day = (string) $sampleSlot->day_of_week;

                $conflicts[] = TimetableConflict::create(
                    type: 'SUBJECT_DAILY_SPREAD_WARNING',
                    severity: TimetableConflict::SEVERITY_SOFT,
                    message: "Subject '{$subjectName}' for {$className} is scheduled {$count} times on {$day}. Recommend spreading across different days.",
                    details: [
                        'school_class_id' => $sampleSlot->school_class_id,
                        'subject_id' => $sampleSlot->subject_id,
                        'day' => $day,
                        'frequency' => $count,
                    ],
                    classIds: [$sampleSlot->school_class_id],
                    dayOfWeek: $day
                );
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'SUBJECT_DAILY_SPREAD';
    }

    public function getSeverity(): string
    {
        return TimetableConflict::SEVERITY_SOFT;
    }

    public function getWeight(): int
    {
        return 20;
    }

    public function getName(): string
    {
        return 'Subject Daily Distribution';
    }
}
