<?php

namespace App\Services\Timetable\Constraints;

use App\Models\SchoolPeriod;
use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class ActivePeriodConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $periodsCache = $context['periods_by_id'] ?? [];

        foreach ($slots as $slot) {
            if ($slot->status === 'cancelled') {
                continue;
            }

            if ($slot->school_period_id) {
                $period = $periodsCache[$slot->school_period_id]
                    ?? ($slot->relationLoaded('schoolPeriod') ? $slot->schoolPeriod : null);

                if ($period === null && ! array_key_exists($slot->school_period_id, $periodsCache)) {
                    $period = SchoolPeriod::find($slot->school_period_id);
                    $periodsCache[$slot->school_period_id] = $period;
                }

                if (! $period) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'INVALID_PERIOD',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Slot references non-existent period #{$slot->school_period_id}.",
                        details: ['school_period_id' => $slot->school_period_id],
                        periodId: $slot->school_period_id,
                        slotId: $slot->id,
                        dayOfWeek: (string) $slot->day_of_week
                    );
                } elseif (! $period->is_active) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'INACTIVE_PERIOD',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Lesson is scheduled in an inactive period: '{$period->name}' (#{$period->id}).",
                        details: [
                            'school_period_id' => $period->id,
                            'period_name' => $period->name,
                            'is_active' => false,
                        ],
                        periodId: $period->id,
                        slotId: $slot->id,
                        dayOfWeek: (string) $slot->day_of_week
                    );
                } elseif (! $period->isLesson() && ($slot->slot_type ?? 'lesson') === 'lesson') {
                    $conflicts[] = TimetableConflict::create(
                        type: 'NON_LESSON_PERIOD_CONFLICT',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Academic lesson is scheduled during a non-lesson period: '{$period->name}' ({$period->period_type}).",
                        details: [
                            'school_period_id' => $period->id,
                            'period_name' => $period->name,
                            'period_type' => $period->period_type,
                        ],
                        periodId: $period->id,
                        slotId: $slot->id,
                        dayOfWeek: (string) $slot->day_of_week
                    );
                }
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'ACTIVE_PERIOD_CONSTRAINT';
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
        return 'Active Lesson Period Verification';
    }
}
