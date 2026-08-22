<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Models\TimetableFixedActivity;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class FixedActivityConflictConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $fixedActivities = $context['fixed_activities'] ?? null;

        if ($fixedActivities === null) {
            $fixedActivities = TimetableFixedActivity::where('school_id', $timetable->school_id)
                ->where(function ($q) use ($timetable) {
                    $q->where('timetable_id', $timetable->id)
                        ->orWhereNull('timetable_id');
                })
                ->get();
        }

        if ($fixedActivities->isEmpty()) {
            return [];
        }

        foreach ($slots as $slot) {
            if ($slot->status === 'cancelled') {
                continue;
            }

            $slotDay = (string) $slot->day_of_week;
            $slotStart = substr((string) ($slot->start_time instanceof \DateTimeInterface ? $slot->start_time->format('H:i') : $slot->start_time), 0, 5);
            $slotEnd = substr((string) ($slot->end_time instanceof \DateTimeInterface ? $slot->end_time->format('H:i') : $slot->end_time), 0, 5);

            foreach ($fixedActivities as $activity) {
                if (! $activity->overlapsWith($slotDay, $slotStart, $slotEnd)) {
                    continue;
                }

                // Check if activity applies to this slot's class or teacher
                $appliesToClass = $activity->appliesToClass($slot->school_class_id);
                $appliesToTeacher = $activity->appliesToTeacher($slot->teacher_id);

                if ($appliesToClass || $appliesToTeacher) {
                    $className = $slot->schoolClass?->name ?? "Class #{$slot->school_class_id}";
                    $subjectName = $slot->subject?->name ?? 'Subject';

                    $conflicts[] = TimetableConflict::create(
                        type: 'FIXED_EVENT_CONFLICT',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Lesson ({$className} - {$subjectName}) conflicts with locked fixed activity '{$activity->name}' on {$slotDay} from {$activity->getFormattedTime()}.",
                        details: [
                            'fixed_activity_id' => $activity->id,
                            'fixed_activity_name' => $activity->name,
                            'activity_type' => $activity->activity_type,
                            'slot_id' => $slot->id,
                            'class_name' => $className,
                            'subject_name' => $subjectName,
                            'day' => $slotDay,
                            'activity_time' => $activity->getFormattedTime(),
                            'slot_time' => "{$slotStart} - {$slotEnd}",
                        ],
                        teacherId: $slot->teacher_id,
                        classIds: [$slot->school_class_id],
                        roomId: $slot->room_id,
                        periodId: $slot->school_period_id,
                        slotId: $slot->id,
                        dayOfWeek: $slotDay,
                        startTime: $slotStart,
                        endTime: $slotEnd
                    );
                }
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'FIXED_EVENT_CONFLICT';
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
        return 'Fixed Activity Conflict Prevention';
    }
}
