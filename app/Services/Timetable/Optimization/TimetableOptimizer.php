<?php

namespace App\Services\Timetable\Optimization;

use App\Models\SchoolPeriod;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Services\Timetable\TimetableConflictService;
use Illuminate\Support\Collection;

class TimetableOptimizer
{
    public function __construct(
        protected TimetableScorer $scorer,
        protected TimetableConflictService $conflictService
    ) {
    }

    /**
     * Run deterministic neighborhood optimization.
     *
     * @param  Timetable  $timetable
     * @param  Collection  $slots
     * @param  array  $options (weights, max_iterations)
     * @return array ['slots' => Collection, 'score_breakdown' => ScoreBreakdown, 'improved' => bool]
     */
    public function optimize(Timetable $timetable, Collection $slots, array $options = []): array
    {
        $customWeights = $options['weights'] ?? [];
        $maxIterations = (int) ($options['max_iterations'] ?? 20);

        // Baseline score
        $currentSlots = $slots->values();
        $bestBreakdown = $this->scorer->score($timetable, $currentSlots, $customWeights);
        $bestScore = $bestBreakdown->totalScore;

        $periods = SchoolPeriod::where('school_id', $timetable->school_id)
            ->active()
            ->where('period_type', 'lesson')
            ->orderBy('period_sequence')
            ->get();

        $days = $timetable->settings['school_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $improved = false;

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $iterationImproved = false;

            for ($i = 0; $i < $currentSlots->count(); $i++) {
                $slotA = $currentSlots[$i];

                // Never modify locked or non-lesson slots
                if ($slotA->isLocked() || ! $slotA->isLesson()) {
                    continue;
                }

                // Try moving slotA to an alternative open period/day
                foreach ($days as $day) {
                    foreach ($periods as $period) {
                        if (strcasecmp($slotA->day_of_week, $day) === 0 && (int) $slotA->school_period_id === (int) $period->id) {
                            continue;
                        }

                        // Create candidate move
                        $candidateSlot = new TimetableSlot($slotA->toArray());
                        $candidateSlot->day_of_week = $day;
                        $candidateSlot->school_period_id = $period->id;
                        $candidateSlot->start_time = substr((string) $period->start_time, 0, 5);
                        $candidateSlot->end_time = substr((string) $period->end_time, 0, 5);

                        $candidateList = $currentSlots->map(function ($s, $idx) use ($i, $candidateSlot) {
                            return ($idx === $i) ? $candidateSlot : $s;
                        });

                        // 1. Must have zero hard conflicts
                        $conflicts = $this->conflictService->detectConflicts($timetable, $candidateList);
                        if ($conflicts['has_hard_conflicts']) {
                            continue;
                        }

                        // 2. Score check
                        $newBreakdown = $this->scorer->score($timetable, $candidateList, $customWeights);
                        if ($newBreakdown->totalScore > $bestScore + 0.05) {
                            $currentSlots = $candidateList;
                            $bestScore = $newBreakdown->totalScore;
                            $bestBreakdown = $newBreakdown;
                            $bestBreakdown->explanations[] = "Optimized slot for {$candidateSlot->subject?->name} on {$day} (Score improved to {$bestScore})";
                            $improved = true;
                            $iterationImproved = true;
                            break 2;
                        }
                    }
                }
            }

            if (! $iterationImproved) {
                break; // Local optimum reached
            }
        }

        return [
            'slots' => $currentSlots,
            'score_breakdown' => $bestBreakdown,
            'improved' => $improved,
        ];
    }
}
