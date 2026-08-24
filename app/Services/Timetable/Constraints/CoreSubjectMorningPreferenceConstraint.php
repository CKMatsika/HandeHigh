<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class CoreSubjectMorningPreferenceConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $subjectCache = [];

        foreach ($slots as $slot) {
            if ($slot->status === 'cancelled' || ! $slot->isLesson() || ! $slot->subject_id) {
                continue;
            }

            $subject = $subjectCache[$slot->subject_id] ?? null;
            if ($subject === null && ! array_key_exists($slot->subject_id, $subjectCache)) {
                $subject = $slot->relationLoaded('subject') ? $slot->subject : \App\Models\Subject::find($slot->subject_id);
                $subjectCache[$slot->subject_id] = $subject;
            }

            if (! $subject) {
                continue;
            }

            $isCore = (bool) $subject->is_core;
            $start = substr((string) ($slot->start_time instanceof \DateTimeInterface ? $slot->start_time->format('H:i') : $slot->start_time), 0, 5);

            // If core subject scheduled after 14:00 (late afternoon)
            if ($isCore && $start >= '14:00') {
                $className = $slot->schoolClass?->name ?? "Class #{$slot->school_class_id}";
                $subjectName = $slot->subject->name;

                $conflicts[] = TimetableConflict::create(
                    type: 'CORE_SUBJECT_AFTERNOON_WARNING',
                    severity: TimetableConflict::SEVERITY_WARNING,
                    message: "Core subject '{$subjectName}' for {$className} is scheduled late in the day at {$start}. Morning slots preferred.",
                    details: [
                        'school_class_id' => $slot->school_class_id,
                        'subject_id' => $slot->subject_id,
                        'start_time' => $start,
                        'day' => (string) $slot->day_of_week,
                    ],
                    classIds: [$slot->school_class_id],
                    dayOfWeek: (string) $slot->day_of_week,
                    startTime: $start
                );
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'CORE_SUBJECT_MORNING_PREFERENCE';
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
        return 'Core Subject Morning Preference';
    }
}
