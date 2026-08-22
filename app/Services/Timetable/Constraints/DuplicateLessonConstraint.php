<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class DuplicateLessonConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $seen = [];

        foreach ($slots as $slot) {
            if ($slot->status === 'cancelled') {
                continue;
            }

            $day = (string) $slot->day_of_week;
            $start = substr((string) ($slot->start_time instanceof \DateTimeInterface ? $slot->start_time->format('H:i') : $slot->start_time), 0, 5);
            $key = "{$slot->school_class_id}_{$slot->subject_id}_{$day}_{$start}";

            if (isset($seen[$key])) {
                $className = $slot->schoolClass?->name ?? "Class #{$slot->school_class_id}";
                $subjectName = $slot->subject?->name ?? "Subject #{$slot->subject_id}";

                $conflicts[] = TimetableConflict::create(
                    type: 'DUPLICATE_LESSON',
                    severity: TimetableConflict::SEVERITY_HARD,
                    message: "Duplicate lesson detected for {$className} in {$subjectName} on {$day} at {$start}.",
                    details: [
                        'school_class_id' => $slot->school_class_id,
                        'subject_id' => $slot->subject_id,
                        'day' => $day,
                        'start_time' => $start,
                        'slot_a_id' => $seen[$key]->id,
                        'slot_b_id' => $slot->id,
                    ],
                    teacherId: $slot->teacher_id,
                    classIds: [$slot->school_class_id],
                    roomId: $slot->room_id,
                    periodId: $slot->school_period_id,
                    slotId: $slot->id,
                    dayOfWeek: $day,
                    startTime: $start
                );
            } else {
                $seen[$key] = $slot;
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'DUPLICATE_LESSON';
    }

    public function getSeverity(): string
    {
        return TimetableConflict::SEVERITY_HARD;
    }

    public function getWeight(): int
    {
        return 100;
    }

    public function getName(): string
    {
        return 'Duplicate Lesson Prevention';
    }
}
