<?php

namespace App\Services\Timetable\Substitution;

use App\Models\Curriculum;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use Carbon\Carbon;

class SubstituteEligibilityService
{
    /**
     * Check if a teacher is eligible to substitute for a specific slot on a specific date.
     *
     * @return array ['eligible' => bool, 'reasons' => string[], 'is_subject_qualified' => bool]
     */
    public function checkEligibility(
        Teacher $teacher,
        Timetable $timetable,
        TimetableSlot $slot,
        string|Carbon $date
    ): array {
        $reasons = [];
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        // 1. Tenant Check
        if ($teacher->school_id !== $timetable->school_id) {
            return [
                'eligible' => false,
                'reasons' => ['Teacher belongs to a different school.'],
                'is_subject_qualified' => false,
            ];
        }

        // 2. Active Status
        if (! $teacher->status) {
            $reasons[] = 'Teacher is inactive.';
        }

        // 3. Absence Check
        $absents = TeacherAbsence::where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->get();

        $absent = $absents->first(function ($absence) use ($dateStr, $slot) {
            return $absence->isActiveOn($dateStr, $slot->school_period_id);
        });

        if ($absent) {
            $reasons[] = 'Teacher is on recorded absence.';
        }

        // 4. Regular Lesson Double-Booking Check
        $hasLessonConflict = $timetable->slots()
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $slot->day_of_week)
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $slot->id)
            ->where(function ($q) use ($slot) {
                $q->where('start_time', '<', $slot->end_time)
                    ->where('end_time', '>', $slot->start_time);
            })
            ->exists();

        if ($hasLessonConflict) {
            $reasons[] = 'Teacher is already scheduled to teach another class at this time.';
        }

        // 5. Existing Substitution Conflict Check
        $hasSubConflict = TimetableSubstitution::where('substitute_teacher_id', $teacher->id)
            ->where('date', $dateStr)
            ->where('status', 'approved')
            ->where('timetable_slot_id', '!=', $slot->id)
            ->whereHas('slot', function ($q) use ($slot) {
                $q->where('day_of_week', $slot->day_of_week)
                    ->where(function ($sq) use ($slot) {
                        $sq->where('start_time', '<', $slot->end_time)
                            ->where('end_time', '>', $slot->start_time);
                    });
            })
            ->exists();

        if ($hasSubConflict) {
            $reasons[] = 'Teacher is already assigned as a substitute for another class at this time.';
        }

        // 6. Subject Qualification Check
        $isSubjectQualified = $this->isQualifiedForSubject($teacher, $slot->subject_id, $timetable->school_id);

        $eligible = empty($reasons);

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'is_subject_qualified' => $isSubjectQualified,
        ];
    }

    /**
     * Check if a teacher is qualified for a subject (by registered teacher_subjects or curriculum).
     */
    public function isQualifiedForSubject(Teacher $teacher, ?int $subjectId, int $schoolId): bool
    {
        if (! $subjectId) {
            return true;
        }

        // Check teacher_subjects relation
        $hasSubject = $teacher->subjects()->where('subjects.id', $subjectId)->exists();
        if ($hasSubject) {
            return true;
        }

        // Check curriculum assignment
        return Curriculum::where('school_id', $schoolId)
            ->where('subject_id', $subjectId)
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->user_id)
                    ->orWhere('teacher_id', $teacher->id);
            })
            ->exists();
    }
}
