<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class RoomDoubleBookingConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $roomSlots = [];

        foreach ($slots as $slot) {
            if (! $slot->room_id || $slot->status === 'cancelled') {
                continue;
            }

            $day = (string) $slot->day_of_week;
            $start = substr((string) ($slot->start_time instanceof \DateTimeInterface ? $slot->start_time->format('H:i') : $slot->start_time), 0, 5);
            $end = substr((string) ($slot->end_time instanceof \DateTimeInterface ? $slot->end_time->format('H:i') : $slot->end_time), 0, 5);

            $key = "{$slot->room_id}_{$day}";

            if (! isset($roomSlots[$key])) {
                $roomSlots[$key] = [];
            }

            foreach ($roomSlots[$key] as $existing) {
                if ($start < $existing['end'] && $end > $existing['start']) {
                    $roomName = $slot->room?->name ?? "Room #{$slot->room_id}";
                    $classAName = $existing['slot']->schoolClass?->name ?? "Class #{$existing['slot']->school_class_id}";
                    $classBName = $slot->schoolClass?->name ?? "Class #{$slot->school_class_id}";

                    $conflicts[] = TimetableConflict::create(
                        type: 'ROOM_DOUBLE_BOOKING',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Room {$roomName} is double-booked by {$classAName} and {$classBName} on {$day} from {$start} to {$end}.",
                        details: [
                            'room_id' => $slot->room_id,
                            'room_name' => $roomName,
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

            $roomSlots[$key][] = [
                'start' => $start,
                'end' => $end,
                'slot' => $slot,
            ];
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'ROOM_DOUBLE_BOOKING';
    }

    public function getSeverity(): string
    {
        return TimetableConflict::SEVERITY_HARD;
    }

    public function getWeight(): int
    {
        return 90;
    }

    public function getName(): string
    {
        return 'Room Double-Booking Prevention';
    }
}
