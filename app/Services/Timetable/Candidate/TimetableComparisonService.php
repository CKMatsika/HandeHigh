<?php

namespace App\Services\Timetable\Candidate;

use App\Models\Timetable;
use App\Models\TimetableCandidate;
use App\Services\Timetable\Optimization\TimetableScorer;
use Illuminate\Support\Collection;

class TimetableComparisonService
{
    public function __construct(
        protected TimetableScorer $scorer
    ) {
    }

    /**
     * Compare a collection of candidates.
     *
     * @param  Timetable  $timetable
     * @param  Collection|array  $candidates
     * @return array
     */
    public function compare(Timetable $timetable, Collection|array $candidates): array
    {
        if (is_array($candidates)) {
            $candidates = collect($candidates);
        }

        // Current schedule baseline score
        $currentSlots = $timetable->slots()->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod'])->get();
        $currentBreakdown = $this->scorer->score($timetable, $currentSlots);

        $compared = [];
        foreach ($candidates as $candidate) {
            /** @var TimetableCandidate $candidate */
            $breakdown = $candidate->score_breakdown ?? [];
            $categories = $breakdown['categories'] ?? [];

            $compared[] = [
                'id' => $candidate->id,
                'candidate_number' => $candidate->candidate_number,
                'score' => (float) $candidate->score,
                'hard_conflicts_count' => $candidate->hard_conflicts_count,
                'soft_warnings_count' => $candidate->soft_warnings_count,
                'allocations_count' => count($candidate->allocations ?? []),
                'unallocated_count' => count($candidate->unallocated_requirements ?? []),
                'is_applied' => $candidate->is_applied,
                'categories' => $categories,
                'metrics' => $candidate->metrics ?? [],
                'score_delta_vs_current' => round((float) $candidate->score - (float) $currentBreakdown->totalScore, 2),
            ];
        }

        // Sort by score descending
        usort($compared, fn ($a, $b) => $b['score'] <=> $a['score']);

        return [
            'current_schedule' => [
                'total_slots' => $currentSlots->count(),
                'score' => $currentBreakdown->totalScore,
                'breakdown' => $currentBreakdown->toArray(),
            ],
            'candidates' => $compared,
            'best_candidate' => $compared[0] ?? null,
        ];
    }
}
