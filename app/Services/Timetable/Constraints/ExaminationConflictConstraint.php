<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Timetable;
use App\Models\TimetableExamination;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class ExaminationConflictConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $examinations = $context['examinations'] ?? null;

        if ($examinations === null) {
            $examinations = TimetableExamination::where('school_id', $timetable->school_id)
                ->where(function ($q) use ($timetable) {
                    $q->where('timetable_id', $timetable->id)
                        ->orWhereNull('timetable_id');
                })
                ->get();
        }

        if ($examinations->isEmpty()) {
            return [];
        }

        foreach ($slots as $slot) {
            if ($slot->status === 'cancelled') {
                continue;
            }

            $slotDay = (string) $slot->day_of_week;
            $slotStart = substr((string) ($slot->start_time instanceof \DateTimeInterface ? $slot->start_time->format('H:i') : $slot->start_time), 0, 5);
            $slotEnd = substr((string) ($slot->end_time instanceof \DateTimeInterface ? $slot->end_time->format('H:i') : $slot->end_time), 0, 5);

            foreach ($examinations as $exam) {
                if (! $exam->overlapsWith($slotDay, $slotStart, $slotEnd)) {
                    continue;
                }

                $appliesToClass = ($exam->school_class_id === null || $exam->school_class_id === $slot->school_class_id);
                $appliesToRoom = ($exam->room_id !== null && $exam->room_id === $slot->room_id);
                $appliesToSupervisor = ($exam->supervisor_teacher_id !== null && $exam->supervisor_teacher_id === $slot->teacher_id);

                if ($appliesToClass || $appliesToRoom || $appliesToSupervisor) {
                    $className = $slot->schoolClass?->name ?? "Class #{$slot->school_class_id}";
                    $subjectName = $slot->subject?->name ?? 'Subject';
                    $type = $exam->isNational() ? 'NATIONAL_EXAMINATION_CONFLICT' : 'EXAMINATION_CONFLICT';
                    $severity = TimetableConflict::SEVERITY_HARD;

                    $conflicts[] = TimetableConflict::create(
                        type: $type,
                        severity: $severity,
                        message: "Lesson ({$className} - {$subjectName}) conflicts with {$exam->exam_type} examination '{$exam->title}' on {$slotDay} from {$exam->getFormattedTime()}.",
                        details: [
                            'examination_id' => $exam->id,
                            'examination_title' => $exam->title,
                            'exam_type' => $exam->exam_type,
                            'is_national' => $exam->isNational(),
                            'slot_id' => $slot->id,
                            'class_name' => $className,
                            'subject_name' => $subjectName,
                            'day' => $slotDay,
                            'exam_time' => $exam->getFormattedTime(),
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
        return 'EXAMINATION_CONFLICT';
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
        return 'Examination Conflict Prevention';
    }
}
