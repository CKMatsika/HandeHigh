<?php

namespace App\Services\Academic;

use App\Models\GradeBand;
use App\Models\GradeScheme;
use App\Models\School;

class GradeCalculationService
{
    /**
     * Calculate percentage from mark obtained and max mark.
     */
    public function calculatePercentage(?float $markObtained, float $maxMark = 100.0): ?float
    {
        if ($markObtained === null || $maxMark <= 0) {
            return null;
        }

        $percentage = ($markObtained / $maxMark) * 100.0;
        return round(min(100.0, max(0.0, $percentage)), 2);
    }

    /**
     * Evaluate grade, pass/fail status, description, and styling for a percentage or special status.
     *
     * @param float|null $percentage
     * @param string $resultStatus 'present', 'absent', 'no_result', 'withheld', 'cancelled'
     * @param GradeScheme|null $scheme
     * @return array
     */
    public function evaluateGrade(?float $percentage, string $resultStatus = 'present', ?GradeScheme $scheme = null, ?School $school = null): array
    {
        $status = strtolower($resultStatus);

        // Handle special non-sitting statuses
        if ($status === 'absent') {
            return [
                'grade' => 'ABS',
                'is_pass' => false,
                'description' => 'Absent',
                'color' => 'text-rose-500 font-bold',
                'badge_class' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                'is_special' => true,
            ];
        }

        if ($status === 'no_result') {
            return [
                'grade' => 'NR',
                'is_pass' => false,
                'description' => 'No Result',
                'color' => 'text-amber-500 font-bold',
                'badge_class' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                'is_special' => true,
            ];
        }

        if ($status === 'withheld') {
            return [
                'grade' => 'W',
                'is_pass' => false,
                'description' => 'Result Withheld',
                'color' => 'text-purple-500 font-bold',
                'badge_class' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                'is_special' => true,
            ];
        }

        if ($status === 'cancelled') {
            return [
                'grade' => 'CAN',
                'is_pass' => false,
                'description' => 'Cancelled',
                'color' => 'text-slate-500 font-bold',
                'badge_class' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                'is_special' => true,
            ];
        }

        if ($percentage === null) {
            return [
                'grade' => '-',
                'is_pass' => null,
                'description' => 'Pending',
                'color' => 'text-slate-400',
                'badge_class' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                'is_special' => false,
            ];
        }

        // Resolve active scheme if not provided
        $effectiveScheme = $scheme ?? $this->resolveDefaultScheme($school);

        if ($effectiveScheme && $effectiveScheme->bands->isNotEmpty()) {
            foreach ($effectiveScheme->bands as $band) {
                if ($percentage >= (float)$band->min_percentage && $percentage <= (float)$band->max_percentage) {
                    return [
                        'grade' => $band->grade,
                        'is_pass' => (bool)$band->is_pass,
                        'description' => $band->description ?? ($band->is_pass ? 'Pass' : 'Fail'),
                        'color' => $band->is_pass ? 'text-emerald-400 font-semibold' : 'text-rose-500 font-bold',
                        'badge_class' => $band->is_pass 
                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' 
                            : 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                        'is_special' => false,
                    ];
                }
            }
        }

        // Standard ZIMSEC O-Level Default Fallback Scheme
        return $this->evaluateDefaultZimsecOLevel($percentage);
    }

    /**
     * Default ZIMSEC-aligned O-Level Grading Scheme
     */
    protected function evaluateDefaultZimsecOLevel(float $percentage): array
    {
        if ($percentage >= 75.0) {
            return [
                'grade' => 'A',
                'is_pass' => true,
                'description' => 'Distinction',
                'color' => 'text-emerald-400 font-semibold',
                'badge_class' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                'is_special' => false,
            ];
        }

        if ($percentage >= 65.0) {
            return [
                'grade' => 'B',
                'is_pass' => true,
                'description' => 'Merit',
                'color' => 'text-emerald-400 font-semibold',
                'badge_class' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                'is_special' => false,
            ];
        }

        if ($percentage >= 50.0) {
            return [
                'grade' => 'C',
                'is_pass' => true,
                'description' => 'Credit / Pass',
                'color' => 'text-emerald-400 font-semibold',
                'badge_class' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                'is_special' => false,
            ];
        }

        if ($percentage >= 45.0) {
            return [
                'grade' => 'D',
                'is_pass' => true,
                'description' => 'Pass',
                'color' => 'text-teal-400 font-semibold',
                'badge_class' => 'bg-teal-500/10 text-teal-400 border-teal-500/30',
                'is_special' => false,
            ];
        }

        if ($percentage >= 40.0) {
            return [
                'grade' => 'E',
                'is_pass' => true,
                'description' => 'Pass',
                'color' => 'text-yellow-400 font-semibold',
                'badge_class' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                'is_special' => false,
            ];
        }

        return [
            'grade' => 'U',
            'is_pass' => false,
            'description' => 'Ungraded / Fail',
            'color' => 'text-rose-500 font-bold',
            'badge_class' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
            'is_special' => false,
        ];
    }

    /**
     * Resolve default grade scheme for school or system
     */
    public function resolveDefaultScheme(?School $school = null): ?GradeScheme
    {
        if ($school) {
            $scheme = GradeScheme::where('school_id', $school->id)->where('is_active', true)->where('is_default', true)->with('bands')->first();
            if ($scheme) {
                return $scheme;
            }
        }

        return GradeScheme::whereNull('school_id')->where('is_active', true)->where('is_default', true)->with('bands')->first()
            ?? GradeScheme::where('is_active', true)->with('bands')->first();
    }

    /**
     * Calculate term aggregates across all subjects for a student report.
     *
     * @param iterable $reportSubjects
     * @return array
     */
    public function calculateTermAggregates(iterable $reportSubjects, ?GradeScheme $scheme = null): array
    {
        $totalSubjects = 0;
        $validPercentagesSum = 0;
        $validSubjectsCount = 0;
        $subjectsPassed = 0;
        $subjectsFailed = 0;

        foreach ($reportSubjects as $subjectItem) {
            $totalSubjects++;
            $status = strtolower($subjectItem->result_status ?? 'present');

            if ($status === 'present' && $subjectItem->percentage !== null) {
                $percentage = (float)$subjectItem->percentage;
                $validPercentagesSum += $percentage;
                $validSubjectsCount++;

                if ($subjectItem->is_pass) {
                    $subjectsPassed++;
                } else {
                    $subjectsFailed++;
                }
            } elseif (in_array($status, ['absent', 'no_result', 'withheld', 'cancelled'])) {
                // Treated as not passed
                $subjectsFailed++;
            }
        }

        $termAverage = $validSubjectsCount > 0 ? round($validPercentagesSum / $validSubjectsCount, 2) : null;
        
        // Overall Grade from Term Average
        $overallGrade = null;
        if ($termAverage !== null) {
            $eval = $this->evaluateGrade($termAverage, 'present', $scheme);
            $overallGrade = $eval['grade'];
        }

        // Overall Academic Standing Description
        $overallStatus = $this->determineOverallStatus($termAverage, $subjectsPassed, $subjectsFailed, $totalSubjects);

        return [
            'term_average' => $termAverage,
            'overall_grade' => $overallGrade,
            'total_subjects' => $totalSubjects,
            'subjects_passed' => $subjectsPassed,
            'subjects_failed' => $subjectsFailed,
            'overall_status' => $overallStatus,
        ];
    }

    /**
     * Determine overall academic standing text
     */
    protected function determineOverallStatus(?float $average, int $passed, int $failed, int $total): string
    {
        if ($average === null || $total === 0) {
            return 'Pending Marks';
        }

        if ($failed === 0 && $average >= 75.0) {
            return 'DISTINCTION / EXCELLENT';
        }

        if ($failed === 0 && $average >= 65.0) {
            return 'MERIT / VERY GOOD';
        }

        if ($passed >= 5 && $failed <= 1 && $average >= 50.0) {
            return 'SATISFACTORY / GOOD';
        }

        if ($failed >= 3 || $average < 45.0) {
            return 'AT RISK / NEEDS SUPPORT';
        }

        return 'SATISFACTORY PASS';
    }
}
