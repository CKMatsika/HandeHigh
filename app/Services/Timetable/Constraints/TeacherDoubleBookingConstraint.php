<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class TeacherDoubleBookingConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $teacherSlots = [];

        foreach ($slots as $slot) {
            if (! $slot->teacher_id || $slot->status === 'cancelled') {
                continue;
            }

            $day = (string) $slot->day_of_week;
            $start = substr((string) ($slot->start_time instanceof \DateTimeInterface ? $slot->start_time->format('H:i') : $slot->start_time), 0, 5);
            $end = substr((string) ($slot->end_time instanceof \DateTimeInterface ? $slot->end_time->format('H:i') : $slot->end_time), 0, 5);

            $key = "{$slot->teacher_id}_{$day}";

            if (! isset($teacherSlots[$key])) {
                $teacherSlots[$key] = [];
            }

            foreach ($teacherSlots[$key] as $existing) {
                // Check time overlap: start1 < end2 && end1 > start2
                if ($start < $existing['end'] && $end > $existing['start']) {
                    $teacherName = $slot->teacher?->full_name ?? ($slot->teacher?->user?->name ?? "Teacher #{$slot->teacher_id}");
                    $classAName = $existing['slot']->schoolClass?->name ?? "Class #{$existing['slot']->school_class_id}";
                    $classBName = $slot->schoolClass?->name ?? "Class #{$slot->school_class_id}";
                    $subjectAName = $existing['slot']->subject?->name ?? 'Subject';
                    $subjectBName = $slot->subject?->name ?? 'Subject';

                    $conflicts[] = TimetableConflict::create(
                        type: 'TEACHER_DOUBLE_BOOKING',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Teacher {$teacherName} is scheduled to teach multiple classes simultaneously ({$classAName} [{$subjectAName}] and {$classBName} [{$subjectBName}]) on {$day} from {$start} to {$end}.",
                        details: [
                            'teacher_id' => $slot->teacher_id,
                            'teacher_name' => $teacherName,
                            'slot_a_id' => $existing['slot']->id,
                            'slot_b_id' => $slot->id,
                            'class_a' => $classAName,
                            'class_b' => $classBName,
                            'day' => $day,
                            'time_a' => "{$existing['start']} - {$existing['end']}",
                            'time_b' => "{$start} - {$end}",
                        ],
                        teacherId: $slot->teacher_id,
                        classIds: array_values(array_filter([$existing['slot']->school_class_id, $slot->school_class_id])),
                        roomId: $slot->room_id,
                        periodId: $slot->school_period_id,
                        slotId: $slot->id,
                        dayOfWeek: $day,
                        startTime: $start,
                        endTime: $end
                    );
                }
            }

            $teacherSlots[$key][] = [
                'start' => $start,
                'end' => $end,
                'slot' => $slot,
            ];
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'TEACHER_DOUBLE_BOOKING';
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
        return 'Teacher Double-Booking Prevention';
    }
}
