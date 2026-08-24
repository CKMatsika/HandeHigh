<?php

namespace App\Services\Timetable\Substitution;

use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Carbon\Carbon;

class SubstitutionScorer
{
    /**
     * Score an eligible substitute teacher candidate.
     */
    public function scoreCandidate(
        Teacher $teacher,
        Timetable $timetable,
        TimetableSlot $slot,
        string|Carbon $date,
        bool $isSubjectQualified
    ): SubstitutionResult {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $score = 0.0;
        $breakdown = [];
        $highlights = [];
        $warnings = [];

        // 1. Subject Qualification (Up to 40 pts)
        if ($isSubjectQualified) {
            $score += 40;
            $breakdown['subject_qualification'] = 40;
            $highlights[] = 'Qualified subject specialist';
        } else {
            $breakdown['subject_qualification'] = 10;
            $score += 10;
            $warnings[] = 'Not directly qualified for this subject';
        }

        // 2. Free Period Availability (Up to 25 pts)
        $score += 25;
        $breakdown['free_period'] = 25;
        $highlights[] = "Free on {$slot->day_of_week} {$slot->getFormattedTime()}";

        // 3. Daily Workload Balance (Up to 15 pts)
        $dailyLessonsCount = $timetable->slots()
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $slot->day_of_week)
            ->where('status', '!=', 'cancelled')
            ->count();

        if ($dailyLessonsCount <= 2) {
            $score += 15;
            $breakdown['workload_balance'] = 15;
            $highlights[] = 'Low daily teaching load';
        } elseif ($dailyLessonsCount <= 4) {
            $score += 10;
            $breakdown['workload_balance'] = 10;
        } else {
            $score += 5;
            $breakdown['workload_balance'] = 5;
            $warnings[] = 'Moderate daily teaching load';
        }

        // 4. Same Department / Faculty (Up to 10 pts)
        $origTeacher = $slot->teacher;
        $sameSpecialization = ($origTeacher && $teacher->specialization && $origTeacher->specialization === $teacher->specialization);
        if ($sameSpecialization) {
            $score += 10;
            $breakdown['department_match'] = 10;
            $highlights[] = 'Same academic specialization';
        } else {
            $score += 5;
            $breakdown['department_match'] = 5;
        }

        // 5. Class Familiarity / Experience (Up to 10 pts)
        $teachesThisClass = $timetable->slots()
            ->where('teacher_id', $teacher->id)
            ->where('school_class_id', $slot->school_class_id)
            ->exists();

        if ($teachesThisClass) {
            $score += 10;
            $breakdown['class_familiarity'] = 10;
            $highlights[] = 'Already teaches this class in another subject';
        } else {
            $breakdown['class_familiarity'] = 0;
        }

        return new SubstitutionResult(
            teacher: $teacher,
            score: round($score, 2),
            breakdown: $breakdown,
            highlights: $highlights,
            warnings: $warnings,
            isEligible: true
        );
    }
}
