<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class ClassDoubleBookingConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $classSlots = [];

        foreach ($slots as $slot) {
            if (! $slot->school_class_id || $slot->status === 'cancelled') {
                continue;
            }

            $day = (string) $slot->day_of_week;
            $start = substr((string) ($slot->start_time instanceof \DateTimeInterface ? $slot->start_time->format('H:i') : $slot->start_time), 0, 5);
            $end = substr((string) ($slot->end_time instanceof \DateTimeInterface ? $slot->end_time->format('H:i') : $slot->end_time), 0, 5);

            $key = "{$slot->school_class_id}_{$day}";

            if (! isset($classSlots[$key])) {
                $classSlots[$key] = [];
            }

            foreach ($classSlots[$key] as $existing) {
                if ($start < $existing['end'] && $end > $existing['start']) {
                    $className = $slot->schoolClass?->name ?? "Class #{$slot->school_class_id}";
                    $subjectAName = $existing['slot']->subject?->name ?? 'Subject';
                    $subjectBName = $slot->subject?->name ?? 'Subject';

                    $conflicts[] = TimetableConflict::create(
                        type: 'CLASS_DOUBLE_BOOKING',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Class {$className} has overlapping lessons ({$subjectAName} and {$subjectBName}) on {$day} from {$start} to {$end}.",
                        details: [
                            'school_class_id' => $slot->school_class_id,
                            'class_name' => $className,
                            'slot_a_id' => $existing['slot']->id,
                            'slot_b_id' => $slot->id,
                            'subject_a' => $subjectAName,
                            'subject_b' => $subjectBName,
                            'day' => $day,
                            'time_a' => "{$existing['start']} - {$existing['end']}",
                            'time_b' => "{$start} - {$end}",
                        ],
                        teacherId: $slot->teacher_id,
                        classIds: [$slot->school_class_id],
                        roomId: $slot->room_id,
                        periodId: $slot->school_period_id,
                        slotId: $slot->id,
                        dayOfWeek: $day,
                        startTime: $start,
                        endTime: $end
                    );
                }
            }

            $classSlots[$key][] = [
                'start' => $start,
                'end' => $end,
                'slot' => $slot,
            ];
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'CLASS_DOUBLE_BOOKING';
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
        return 'Class Double-Booking Prevention';
    }
}
