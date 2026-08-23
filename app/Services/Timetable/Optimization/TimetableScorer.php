<?php

namespace App\Services\Timetable\Optimization;

use App\Models\SchoolPeriod;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Support\Collection;

class TimetableScorer
{
    public const DEFAULT_WEIGHTS = [
        'subject_daily_spread' => 20,
        'teacher_workload_balance' => 20,
        'class_workload_balance' => 15,
        'consecutive_lesson_penalty' => 15,
        'core_subject_morning_preference' => 10,
        'teacher_free_period_balance' => 10,
        'room_utilization' => 5,
        'gap_penalty' => 5,
    ];

    /**
     * Score a set of timetable slots.
     *
     * @param  Timetable  $timetable
     * @param  Collection  $slots
     * @param  array  $customWeights
     * @return ScoreBreakdown
     */
    public function score(Timetable $timetable, Collection $slots, array $customWeights = []): ScoreBreakdown
    {
        $weights = array_merge(self::DEFAULT_WEIGHTS, $customWeights);
        $totalWeight = array_sum($weights);
        if ($totalWeight <= 0) {
            $weights = self::DEFAULT_WEIGHTS;
            $totalWeight = 100;
        }

        // Active lesson slots only
        $lessonSlots = $slots->filter(fn ($s) => $s->status !== 'cancelled' && $s->isLesson());

        if ($lessonSlots->isEmpty()) {
            return new ScoreBreakdown(
                totalScore: 0.00,
                categories: array_fill_keys(array_keys($weights), 0.00),
                weights: $weights,
                rawScores: array_fill_keys(array_keys($weights), 0.00),
                explanations: ['No active lesson slots found to evaluate.']
            );
        }

        // 1. Subject Daily Spread (Weight: 20)
        // Ratio of (class-subject-day pairs with <= 2 lessons) over all pairs
        $spreadScore = $this->evaluateSubjectDailySpread($lessonSlots);

        // 2. Teacher Workload Balance (Weight: 20)
        // Uniform daily load per teacher across the week (low variance)
        $teacherBalanceScore = $this->evaluateTeacherWorkloadBalance($lessonSlots);

        // 3. Class Workload Balance (Weight: 15)
        // Uniform daily load per class across the week
        $classBalanceScore = $this->evaluateClassWorkloadBalance($lessonSlots);

        // 4. Consecutive Lesson Penalty (Weight: 15)
        // Discourages > 2 consecutive lessons of the exact same subject for a class
        $consecutiveScore = $this->evaluateConsecutiveLessons($lessonSlots);

        // 5. Core Subject Morning Preference (Weight: 10)
        // Mathematics, Science, English scheduled in morning periods (e.g. sequence 1-4)
        $morningCoreScore = $this->evaluateMorningCorePreference($lessonSlots);

        // 6. Teacher Free Period Balance (Weight: 10)
        // Avoids erratic single-period isolated gaps in teacher daily schedules
        $teacherFreePeriodScore = $this->evaluateTeacherFreePeriods($lessonSlots);

        // 7. Room Utilization (Weight: 5)
        // Appropriately assigned room matching
        $roomUtilizationScore = $this->evaluateRoomUtilization($lessonSlots);

        // 8. Class Gap Penalty (Weight: 5)
        // Avoids student class schedule gaps between lessons
        $classGapScore = $this->evaluateClassGaps($lessonSlots);

        $rawScores = [
            'subject_daily_spread' => round($spreadScore, 4),
            'teacher_workload_balance' => round($teacherBalanceScore, 4),
            'class_workload_balance' => round($classBalanceScore, 4),
            'consecutive_lesson_penalty' => round($consecutiveScore, 4),
            'core_subject_morning_preference' => round($morningCoreScore, 4),
            'teacher_free_period_balance' => round($teacherFreePeriodScore, 4),
            'room_utilization' => round($roomUtilizationScore, 4),
            'gap_penalty' => round($classGapScore, 4),
        ];

        $categories = [];
        $totalCalculatedScore = 0.00;

        foreach ($weights as $key => $weight) {
            $catScore = ($rawScores[$key] ?? 0.0) * $weight;
            $categories[$key] = round($catScore, 2);
            $totalCalculatedScore += $catScore;
        }

        // Normalize if sum of weights != 100
        $normalizedTotalScore = ($totalCalculatedScore / $totalWeight) * 100.0;
        $normalizedTotalScore = max(0.00, min(100.00, $normalizedTotalScore));

        $explanations = [
            "Subject Daily Spread: {$categories['subject_daily_spread']}/{$weights['subject_daily_spread']}",
            "Teacher Workload Balance: {$categories['teacher_workload_balance']}/{$weights['teacher_workload_balance']}",
            "Class Workload Balance: {$categories['class_workload_balance']}/{$weights['class_workload_balance']}",
            "Consecutive Lesson Distribution: {$categories['consecutive_lesson_penalty']}/{$weights['consecutive_lesson_penalty']}",
            "Morning Core Subjects: {$categories['core_subject_morning_preference']}/{$weights['core_subject_morning_preference']}",
            "Teacher Free Periods: {$categories['teacher_free_period_balance']}/{$weights['teacher_free_period_balance']}",
            "Room Utilization: {$categories['room_utilization']}/{$weights['room_utilization']}",
            "Student Class Continuity (No Gaps): {$categories['gap_penalty']}/{$weights['gap_penalty']}",
        ];

        return new ScoreBreakdown(
            totalScore: round($normalizedTotalScore, 2),
            categories: $categories,
            weights: $weights,
            rawScores: $rawScores,
            explanations: $explanations
        );
    }

    protected function evaluateSubjectDailySpread(Collection $slots): float
    {
        $grouped = [];
        foreach ($slots as $slot) {
            $key = "{$slot->school_class_id}_{$slot->subject_id}_{$slot->day_of_week}";
            $grouped[$key] = ($grouped[$key] ?? 0) + 1;
        }

        if (empty($grouped)) {
            return 1.0;
        }

        $validCount = 0;
        foreach ($grouped as $count) {
            if ($count <= 2) {
                $validCount++;
            }
        }

        return $validCount / count($grouped);
    }

    protected function evaluateTeacherWorkloadBalance(Collection $slots): float
    {
        $teacherDays = [];
        foreach ($slots as $slot) {
            if ($slot->teacher_id) {
                $teacherDays[$slot->teacher_id][$slot->day_of_week] = ($teacherDays[$slot->teacher_id][$slot->day_of_week] ?? 0) + 1;
            }
        }

        if (empty($teacherDays)) {
            return 1.0;
        }

        $scores = [];
        foreach ($teacherDays as $days) {
            $counts = array_values($days);
            $max = max($counts);
            $min = min($counts);
            $diff = $max - $min;
            // Diff <= 2 is ideal (1.0), Diff 3 is 0.8, Diff >= 5 is 0.4
            $score = max(0.0, 1.0 - ($diff * 0.15));
            $scores[] = $score;
        }

        return array_sum($scores) / count($scores);
    }

    protected function evaluateClassWorkloadBalance(Collection $slots): float
    {
        $classDays = [];
        foreach ($slots as $slot) {
            if ($slot->school_class_id) {
                $classDays[$slot->school_class_id][$slot->day_of_week] = ($classDays[$slot->school_class_id][$slot->day_of_week] ?? 0) + 1;
            }
        }

        if (empty($classDays)) {
            return 1.0;
        }

        $scores = [];
        foreach ($classDays as $days) {
            $counts = array_values($days);
            $max = max($counts);
            $min = min($counts);
            $diff = $max - $min;
            $score = max(0.0, 1.0 - ($diff * 0.15));
            $scores[] = $score;
        }

        return array_sum($scores) / count($scores);
    }

    protected function evaluateConsecutiveLessons(Collection $slots): float
    {
        // Group by class & day, sort by period sequence or start time
        $grouped = $slots->groupBy(fn ($s) => "{$s->school_class_id}_{$s->day_of_week}");
        $totalSequences = 0;
        $penaltyCount = 0;

        foreach ($grouped as $daySlots) {
            $sorted = $daySlots->sortBy(function ($s) {
                return $s->schoolPeriod?->period_sequence ?? $s->start_time;
            })->values();

            $currentSubjectId = null;
            $streak = 0;

            foreach ($sorted as $s) {
                $totalSequences++;
                if ($s->subject_id === $currentSubjectId) {
                    $streak++;
                    if ($streak > 2) {
                        $penaltyCount++;
                    }
                } else {
                    $currentSubjectId = $s->subject_id;
                    $streak = 1;
                }
            }
        }

        if ($totalSequences === 0) {
            return 1.0;
        }

        return max(0.0, 1.0 - ($penaltyCount / $totalSequences));
    }

    protected function evaluateMorningCorePreference(Collection $slots): float
    {
        $coreKeywords = ['math', 'mathematics', 'physic', 'chemist', 'biolog', 'science', 'english'];
        $coreSlots = 0;
        $morningCoreSlots = 0;

        foreach ($slots as $slot) {
            $subjectName = strtolower($slot->subject?->name ?? '');
            $isCore = false;
            foreach ($coreKeywords as $kw) {
                if (str_contains($subjectName, $kw)) {
                    $isCore = true;
                    break;
                }
            }

            if ($isCore) {
                $coreSlots++;
                $seq = $slot->schoolPeriod?->period_sequence ?? 1;
                if ($seq <= 4 || (is_string($slot->start_time) && substr($slot->start_time, 0, 2) < '12')) {
                    $morningCoreSlots++;
                }
            }
        }

        if ($coreSlots === 0) {
            return 1.0;
        }

        return $morningCoreSlots / $coreSlots;
    }

    protected function evaluateTeacherFreePeriods(Collection $slots): float
    {
        // Check for isolated single-period gaps in teacher daily schedules
        $grouped = $slots->whereNotNull('teacher_id')->groupBy(fn ($s) => "{$s->teacher_id}_{$s->day_of_week}");
        $totalDays = count($grouped);
        if ($totalDays === 0) {
            return 1.0;
        }

        $isolatedGaps = 0;
        foreach ($grouped as $daySlots) {
            $seqs = $daySlots->map(fn ($s) => $s->schoolPeriod?->period_sequence)->filter()->sort()->values()->toArray();
            if (count($seqs) < 2) {
                continue;
            }

            for ($i = 0; $i < count($seqs) - 1; $i++) {
                $diff = $seqs[$i + 1] - $seqs[$i];
                if ($diff === 2) { // 1 isolated gap period
                    $isolatedGaps++;
                }
            }
        }

        return max(0.0, 1.0 - ($isolatedGaps / ($totalDays * 2)));
    }

    protected function evaluateRoomUtilization(Collection $slots): float
    {
        $withRoom = $slots->whereNotNull('room_id')->count();
        $total = $slots->count();

        if ($total === 0) {
            return 1.0;
        }

        return $withRoom / $total;
    }

    protected function evaluateClassGaps(Collection $slots): float
    {
        $grouped = $slots->groupBy(fn ($s) => "{$s->school_class_id}_{$s->day_of_week}");
        $totalDays = count($grouped);
        if ($totalDays === 0) {
            return 1.0;
        }

        $gapDays = 0;
        foreach ($grouped as $daySlots) {
            $seqs = $daySlots->map(fn ($s) => $s->schoolPeriod?->period_sequence)->filter()->sort()->values()->toArray();
            if (count($seqs) < 2) {
                continue;
            }

            for ($i = 0; $i < count($seqs) - 1; $i++) {
                if (($seqs[$i + 1] - $seqs[$i]) > 1) {
                    $gapDays++;
                    break;
                }
            }
        }

        return max(0.0, 1.0 - ($gapDays / $totalDays));
    }
}
