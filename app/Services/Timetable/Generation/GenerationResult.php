<?php

namespace App\Services\Timetable\Generation;

use App\Services\Timetable\Optimization\ScoreBreakdown;

class GenerationResult
{
    public function __construct(
        public string $status, // SUCCESS, PARTIAL, FAILED
        public ?string $seed,
        public int $timetableId,
        public int $candidateNumber,
        public float $score,
        public array $scoreBreakdown,
        public int $hardConflictCount,
        public int $softWarningCount,
        public array $allocations,
        public int $allocatedCount,
        public int $unallocatedCount,
        public array $unallocatedRequirements = [],
        public array $conflicts = [],
        public array $warnings = [],
        public array $metrics = [],
        public ?string $explanation = null
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status === 'SUCCESS' && $this->hardConflictCount === 0 && $this->unallocatedCount === 0;
    }

    public function isPublishable(): bool
    {
        return $this->hardConflictCount === 0 && ! empty($this->allocations);
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'seed' => $this->seed,
            'timetable_id' => $this->timetableId,
            'candidate_number' => $this->candidateNumber,
            'score' => $this->score,
            'score_breakdown' => $this->scoreBreakdown,
            'hard_conflict_count' => $this->hardConflictCount,
            'soft_warning_count' => $this->softWarningCount,
            'allocations_count' => count($this->allocations),
            'allocated_count' => $this->allocatedCount,
            'unallocated_count' => $this->unallocatedCount,
            'unallocated_requirements' => $this->unallocatedRequirements,
            'conflicts' => $this->conflicts,
            'warnings' => $this->warnings,
            'metrics' => $this->metrics,
            'explanation' => $this->explanation,
        ];
    }
}
