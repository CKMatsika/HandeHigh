<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Curriculum;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class TeacherSubjectEligibilityConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $teacherSubjectsCache = [];

        foreach ($slots as $slot) {
            if (! $slot->teacher_id || ! $slot->subject_id || $slot->status === 'cancelled') {
                continue;
            }

            if (! isset($teacherSubjectsCache[$slot->teacher_id])) {
                $teacher = Teacher::with('subjects')->find($slot->teacher_id);
                if (! $teacher) {
                    $teacherSubjectsCache[$slot->teacher_id] = [
                        'teacher' => null,
                        'subject_ids' => [],
                        'curriculum_subject_ids' => [],
                    ];
                } else {
                    $subjectIds = $teacher->subjects->pluck('id')->toArray();
                    
                    // Also check if teacher has curriculum assignments for this subject
                    $curriculumSubjectIds = Curriculum::where('school_id', $timetable->school_id)
                        ->where(function ($q) use ($teacher) {
                            $q->where('teacher_id', $teacher->user_id)
                                ->orWhere('teacher_id', $teacher->id);
                        })
                        ->pluck('subject_id')
                        ->toArray();

                    $teacherSubjectsCache[$slot->teacher_id] = [
                        'teacher' => $teacher,
                        'subject_ids' => array_unique(array_merge($subjectIds, $curriculumSubjectIds)),
                    ];
                }
            }

            $teacherData = $teacherSubjectsCache[$slot->teacher_id];
            $assignedSubjectIds = $teacherData['subject_ids'];

            // Only enforce if the teacher has at least one explicit subject assignment registered
            if (! empty($assignedSubjectIds) && ! in_array($slot->subject_id, $assignedSubjectIds, true)) {
                $teacherName = $teacherData['teacher']?->full_name ?? "Teacher #{$slot->teacher_id}";
                $subjectName = $slot->subject?->name ?? "Subject #{$slot->subject_id}";

                $conflicts[] = TimetableConflict::create(
                    type: 'TEACHER_SUBJECT_MISMATCH',
                    severity: TimetableConflict::SEVERITY_HARD,
                    message: "Teacher {$teacherName} is not assigned or qualified to teach subject '{$subjectName}'.",
                    details: [
                        'teacher_id' => $slot->teacher_id,
                        'teacher_name' => $teacherName,
                        'subject_id' => $slot->subject_id,
                        'subject_name' => $subjectName,
                        'assigned_subject_ids' => $assignedSubjectIds,
                        'slot_id' => $slot->id,
                    ],
                    teacherId: $slot->teacher_id,
                    classIds: array_values(array_filter([$slot->school_class_id])),
                    roomId: $slot->room_id,
                    periodId: $slot->school_period_id,
                    slotId: $slot->id,
                    dayOfWeek: (string) $slot->day_of_week,
                    startTime: substr((string) ($slot->start_time instanceof \DateTimeInterface ? $slot->start_time->format('H:i') : $slot->start_time), 0, 5),
                    endTime: substr((string) ($slot->end_time instanceof \DateTimeInterface ? $slot->end_time->format('H:i') : $slot->end_time), 0, 5)
                );
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'TEACHER_SUBJECT_MISMATCH';
    }

    public function getSeverity(): string
    {
        return TimetableConflict::SEVERITY_HARD;
    }

    public function getWeight(): int
    {
        return 80;
    }

    public function getName(): string
    {
        return 'Teacher Subject Qualification & Assignment Matching';
    }
}
