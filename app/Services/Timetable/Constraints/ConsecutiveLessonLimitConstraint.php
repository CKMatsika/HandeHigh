<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class ConsecutiveLessonLimitConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $byClassAndDay = [];

        foreach ($slots as $slot) {
            if ($slot->status === 'cancelled' || ! $slot->isLesson()) {
                continue;
            }

            $key = "{$slot->school_class_id}_{$slot->day_of_week}";
            $byClassAndDay[$key][] = $slot;
        }

        foreach ($byClassAndDay as $classDaySlots) {
            // Sort by start_time
            usort($classDaySlots, function ($a, $b) {
                $aTime = substr((string) ($a->start_time instanceof \DateTimeInterface ? $a->start_time->format('H:i') : $a->start_time), 0, 5);
                $bTime = substr((string) ($b->start_time instanceof \DateTimeInterface ? $b->start_time->format('H:i') : $b->start_time), 0, 5);

                return strcmp($aTime, $bTime);
            });

            $consecutiveCount = 1;
            $currentSubjectId = null;

            for ($i = 0; $i < count($classDaySlots); $i++) {
                $slot = $classDaySlots[$i];
                if ($slot->subject_id === $currentSubjectId) {
                    $consecutiveCount++;
                    if ($consecutiveCount > 2) {
                        $className = $slot->schoolClass?->name ?? "Class #{$slot->school_class_id}";
                        $subjectName = $slot->subject?->name ?? "Subject #{$slot->subject_id}";

                        $conflicts[] = TimetableConflict::create(
                            type: 'EXCESSIVE_CONSECUTIVE_LESSONS',
                            severity: TimetableConflict::SEVERITY_WARNING,
                            message: "Class {$className} has {$consecutiveCount} consecutive periods of {$subjectName} on {$slot->day_of_week}.",
                            details: [
                                'school_class_id' => $slot->school_class_id,
                                'subject_id' => $slot->subject_id,
                                'day' => $slot->day_of_week,
                                'consecutive_count' => $consecutiveCount,
                            ],
                            classIds: [$slot->school_class_id],
                            dayOfWeek: (string) $slot->day_of_week
                        );
                    }
                } else {
                    $currentSubjectId = $slot->subject_id;
                    $consecutiveCount = 1;
                }
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'CONSECUTIVE_LESSON_LIMIT';
    }

    public function getSeverity(): string
    {
        return TimetableConflict::SEVERITY_SOFT;
    }

    public function getWeight(): int
    {
        return 10;
    }

    public function getName(): string
    {
        return 'Consecutive Lessons Threshold';
    }
}
