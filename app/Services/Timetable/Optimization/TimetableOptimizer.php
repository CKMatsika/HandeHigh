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
        $maxIterations = min(3, max(1, (int) ($options['max_iterations'] ?? 1)));

        // Baseline score
        $currentSlots = $slots->values();
        $bestBreakdown = $this->scorer->score($timetable, $currentSlots, $customWeights);
        $bestScore = $bestBreakdown->totalScore;

        $periods = SchoolPeriod::where('school_id', $timetable->school_id)
            ->active()
            ->where('period_type', 'lesson')
            ->orderBy('period_sequence')
            ->get();

        $context = [
            'periods' => $periods,
            'periods_by_id' => $periods->keyBy('id')->all(),
            'fixed_activities' => \App\Models\TimetableFixedActivity::where('school_id', $timetable->school_id)
                ->where(function ($q) use ($timetable) {
                    $q->where('timetable_id', $timetable->id)->orWhereNull('timetable_id');
                })->get(),
            'examinations' => \App\Models\TimetableExamination::where('school_id', $timetable->school_id)
                ->where(function ($q) use ($timetable) {
                    $q->where('timetable_id', $timetable->id)->orWhereNull('timetable_id');
                })->get(),
            'hard_only' => true,
        ];

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

                        $otherSlots = $currentSlots->filter(fn ($s, $idx) => $idx !== $i);

                        // 1. Must have zero hard conflicts
                        if ($this->hasSlotConflictFast($candidateSlot, $otherSlots, $context)) {
                            continue;
                        }

                        $candidateList = $currentSlots->map(function ($s, $idx) use ($i, $candidateSlot) {
                            return ($idx === $i) ? $candidateSlot : $s;
                        });

                        // 2. Score check
                        $newBreakdown = $this->scorer->score($timetable, $candidateList, $customWeights);
                        if ($newBreakdown->totalScore > $bestScore + 0.05) {
                            $currentSlots = $candidateList;
                            $bestScore = $newBreakdown->totalScore;
                            $bestBreakdown = $newBreakdown;
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

    /**
     * High-performance incremental conflict check for a candidate slot move.
     */
    protected function hasSlotConflictFast(TimetableSlot $slot, Collection $currentSlots, array $context): bool
    {
        $day = (string) $slot->day_of_week;
        $start = (string) $slot->start_time;
        $end = (string) $slot->end_time;
        $classId = (int) $slot->school_class_id;
        $teacherId = $slot->teacher_id ? (int) $slot->teacher_id : null;
        $roomId = $slot->room_id ? (int) $slot->room_id : null;

        // 1. Period check
        $period = $context['periods_by_id'][$slot->school_period_id] ?? null;
        if ($period && (! $period->is_active || ! $period->isLesson())) {
            return true;
        }

        // 2. Check against other slots
        foreach ($currentSlots as $existing) {
            if ($existing->status === 'cancelled' || strcasecmp($existing->day_of_week, $day) !== 0) {
                continue;
            }

            $eStart = (string) $existing->start_time;
            $eEnd = (string) $existing->end_time;

            // Overlap condition: start < eEnd && end > eStart
            if ($start < $eEnd && $end > $eStart) {
                // Class double-booking
                if ((int) $existing->school_class_id === $classId) {
                    return true;
                }

                // Teacher double-booking
                if ($teacherId && (int) $existing->teacher_id === $teacherId) {
                    return true;
                }

                // Room double-booking
                if ($roomId && (int) $existing->room_id === $roomId) {
                    return true;
                }
            }
        }

        // 3. Check Fixed Activities
        $fixedActivities = $context['fixed_activities'] ?? [];
        foreach ($fixedActivities as $activity) {
            if ($activity->overlapsWith($day, $start, $end)) {
                if ($activity->appliesToClass($classId) || ($teacherId && $activity->appliesToTeacher($teacherId))) {
                    return true;
                }
            }
        }

        // 4. Check Examinations
        $examinations = $context['examinations'] ?? [];
        foreach ($examinations as $exam) {
            if ($exam->overlapsWith($day, $start, $end)) {
                $appliesToClass = ($exam->school_class_id === null || (int) $exam->school_class_id === $classId);
                $appliesToRoom = ($roomId && $exam->room_id !== null && (int) $exam->room_id === $roomId);
                $appliesToSupervisor = ($teacherId && $exam->supervisor_teacher_id !== null && (int) $exam->supervisor_teacher_id === $teacherId);

                if ($appliesToClass || $appliesToRoom || $appliesToSupervisor) {
                    return true;
                }
            }
        }

        return false;
    }
}
