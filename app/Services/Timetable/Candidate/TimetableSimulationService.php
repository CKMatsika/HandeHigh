<?php

namespace App\Services\Timetable\Candidate;

use App\Models\Timetable;
use App\Models\TimetableCandidate;
use App\Models\TimetableSlot;
use App\Services\Timetable\Generation\DeterministicAllocator;
use App\Services\Timetable\Generation\LockedSlotManager;
use App\Services\Timetable\Generation\TimetableGenerator;
use App\Services\Timetable\Optimization\TimetableOptimizer;
use App\Services\Timetable\Optimization\TimetableScorer;
use App\Services\Timetable\TimetableConflictService;
use Illuminate\Support\Collection;

class TimetableSimulationService
{
    public function __construct(
        protected TimetableGenerator $generator,
        protected DeterministicAllocator $allocator,
        protected LockedSlotManager $lockedSlotManager,
        protected TimetableOptimizer $optimizer,
        protected TimetableScorer $scorer,
        protected TimetableConflictService $conflictService
    ) {
    }

    /**
     * Simulate generation without modifying the database.
     *
     * @param  Timetable  $timetable
     * @param  array  $options (seed, weights, preserve_locked, filters)
     * @return array
     */
    public function simulateGeneration(Timetable $timetable, array $options = []): array
    {
        $currentSlots = $timetable->slots()->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod'])->get();
        $currentBreakdown = $this->scorer->score($timetable, $currentSlots, $options['weights'] ?? []);
        $currentScore = $currentBreakdown->totalScore;

        // Requirements & Preserved Slots
        $requirements = $this->generator->resolveRequirements($timetable, $options);
        $preservedSlots = $this->lockedSlotManager->getPreservedSlots($timetable, $options);
        $lockedCount = $preservedSlots->where('is_locked', true)->count();

        // Run in-memory allocation
        $allocOptions = array_merge($options, [
            'seed' => $options['seed'] ?? 'simulate_' . time(),
            'candidate_index' => 0,
        ]);

        $allocationData = $this->allocator->allocate($timetable, $preservedSlots, $requirements, $allocOptions);
        $allocatedSlots = $allocationData['slots'];

        // Run in-memory optimization
        $optimizeResult = $this->optimizer->optimize($timetable, $allocatedSlots, ['weights' => $options['weights'] ?? []]);
        $simulatedSlots = $optimizeResult['slots'];
        $projectedBreakdown = $optimizeResult['score_breakdown'];
        $projectedScore = $projectedBreakdown->totalScore;

        // Evaluate conflicts
        $conflicts = $this->conflictService->detectConflicts($timetable, $simulatedSlots);
        $hardCount = $conflicts['hard_count'];
        $softCount = $conflicts['soft_count'];

        // Calculate affected entities
        $affectedClassIds = $simulatedSlots->pluck('school_class_id')->unique()->filter()->values();
        $affectedTeacherIds = $simulatedSlots->pluck('teacher_id')->unique()->filter()->values();
        $affectedRoomIds = $simulatedSlots->pluck('room_id')->unique()->filter()->values();

        $unallocated = $allocationData['unallocated_requirements'] ?? [];
        $totalRequired = $allocationData['total_required'] ?? 0;
        $totalAllocated = $allocationData['total_allocated'] ?? 0;

        $isSafe = ($hardCount === 0);
        $status = $isSafe ? 'SAFE TO APPLY' : 'BLOCKED (Contains Hard Conflicts)';

        return [
            'status' => $status,
            'is_safe' => $isSafe,
            'current_score' => round($currentScore, 2),
            'projected_score' => round($projectedScore, 2),
            'score_delta' => round($projectedScore - $currentScore, 2),
            'affected_slots' => $simulatedSlots->count(),
            'affected_classes' => $affectedClassIds->count(),
            'affected_teachers' => $affectedTeacherIds->count(),
            'affected_rooms' => $affectedRoomIds->count(),
            'hard_conflicts' => $hardCount,
            'soft_warnings' => $softCount,
            'locked_slots_preserved' => $lockedCount,
            'requirements_fulfilled' => $totalAllocated,
            'requirements_remaining' => max(0, $totalRequired - $totalAllocated),
            'score_breakdown' => $projectedBreakdown->toArray(),
            'unallocated_details' => $unallocated,
            'hard_conflict_details' => array_map(fn ($c) => $c->toArray(), $conflicts['hard']),
            'soft_warning_details' => array_map(fn ($c) => $c->toArray(), $conflicts['soft']),
        ];
    }

    /**
     * Simulate applying an existing candidate.
     */
    public function simulateCandidate(Timetable $timetable, TimetableCandidate $candidate): array
    {
        $currentSlots = $timetable->slots()->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod'])->get();
        $currentBreakdown = $this->scorer->score($timetable, $currentSlots);

        $allocations = $candidate->allocations ?? [];
        $existingLockedSlots = $timetable->slots()->where('is_locked', true)->get();
        $simulatedSlots = collect();

        foreach ($existingLockedSlots as $ls) {
            $simulatedSlots->push($ls);
        }

        foreach ($allocations as $alloc) {
            $isLockedMatch = $existingLockedSlots->first(function ($ls) use ($alloc) {
                return (int) $ls->school_class_id === (int) ($alloc['school_class_id'] ?? null)
                    && strcasecmp((string) $ls->day_of_week, (string) ($alloc['day_of_week'] ?? '')) === 0
                    && (int) $ls->school_period_id === (int) ($alloc['school_period_id'] ?? null);
            });

            if (! $isLockedMatch) {
                $slot = new TimetableSlot($alloc);
                $slot->timetable_id = $timetable->id;
                $slot->school_id = $timetable->school_id;
                $simulatedSlots->push($slot);
            }
        }

        $conflicts = $this->conflictService->detectConflicts($timetable, $simulatedSlots);
        $hardCount = $conflicts['hard_count'];
        $softCount = $conflicts['soft_count'];

        $affectedClassIds = $simulatedSlots->pluck('school_class_id')->unique()->filter()->values();
        $affectedTeacherIds = $simulatedSlots->pluck('teacher_id')->unique()->filter()->values();
        $affectedRoomIds = $simulatedSlots->pluck('room_id')->unique()->filter()->values();
        $lockedCount = $existingLockedSlots->count();

        $isSafe = ($hardCount === 0);

        return [
            'status' => $isSafe ? 'SAFE TO APPLY' : 'BLOCKED (Contains Hard Conflicts)',
            'is_safe' => $isSafe,
            'current_score' => round($currentBreakdown->totalScore, 2),
            'projected_score' => round((float) $candidate->score, 2),
            'score_delta' => round((float) $candidate->score - $currentBreakdown->totalScore, 2),
            'affected_slots' => $simulatedSlots->count(),
            'affected_classes' => $affectedClassIds->count(),
            'affected_teachers' => $affectedTeacherIds->count(),
            'affected_rooms' => $affectedRoomIds->count(),
            'hard_conflicts' => $hardCount,
            'soft_warnings' => $softCount,
            'locked_slots_preserved' => $lockedCount,
            'score_breakdown' => $candidate->score_breakdown,
            'hard_conflict_details' => array_map(fn ($c) => $c->toArray(), $conflicts['hard']),
            'soft_warning_details' => array_map(fn ($c) => $c->toArray(), $conflicts['soft']),
        ];
    }
}
