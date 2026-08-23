<?php

namespace App\Services\Timetable\Generation;

use App\Models\Curriculum;
use App\Models\School;
use App\Models\Timetable;
use App\Models\TimetableCandidate;
use App\Models\TimetableGenerationRun;
use App\Models\TimetableRequirement;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Timetable\Optimization\ScoreBreakdown;
use App\Services\Timetable\Optimization\TimetableOptimizer;
use App\Services\Timetable\Optimization\TimetableScorer;
use App\Services\Timetable\TimetableConflictService;
use Illuminate\Support\Collection;

class TimetableGenerator
{
    public function __construct(
        protected DeterministicAllocator $allocator,
        protected LockedSlotManager $lockedSlotManager,
        protected TimetableOptimizer $optimizer,
        protected TimetableScorer $scorer,
        protected TimetableConflictService $conflictService
    ) {
    }

    /**
     * Generate candidates for a timetable.
     *
     * @param  Timetable  $timetable
     * @param  array  $options (seed, candidate_count, weights, preserve_locked, class_id, teacher_id, etc.)
     * @param  User|null  $user
     * @return array ['run' => TimetableGenerationRun, 'candidates' => TimetableCandidate[], 'results' => GenerationResult[]]
     */
    public function generate(Timetable $timetable, array $options = [], ?User $user = null): array
    {
        $startedAt = now();
        $seed = $options['seed'] ?? (string) date('YmdHis');
        $candidateCount = max(1, min(10, (int) ($options['candidate_count'] ?? 1)));
        $preserveLocked = $options['preserve_locked'] ?? true;
        $customWeights = $options['weights'] ?? [];

        // 1. Gather scheduling requirements
        $requirements = $this->resolveRequirements($timetable, $options);

        // 2. Gather preserved slots
        $preservedSlots = $this->lockedSlotManager->getPreservedSlots($timetable, $options);

        // 3. Create Generation Run record
        $run = TimetableGenerationRun::create([
            'school_id' => $timetable->school_id,
            'timetable_id' => $timetable->id,
            'user_id' => $user?->id,
            'seed' => $seed,
            'status' => 'pending',
            'configuration' => [
                'seed' => $seed,
                'candidate_count' => $candidateCount,
                'preserve_locked' => $preserveLocked,
                'weights' => $customWeights,
                'filters' => array_filter([
                    'class_id' => $options['class_id'] ?? null,
                    'teacher_id' => $options['teacher_id'] ?? null,
                    'subject_id' => $options['subject_id'] ?? null,
                    'day_of_week' => $options['day_of_week'] ?? null,
                ]),
            ],
            'started_at' => $startedAt,
        ]);

        $candidates = [];
        $results = [];
        $bestScore = 0.00;

        for ($i = 0; $i < $candidateCount; $i++) {
            $candidateIndex = $i;

            // Run allocation
            $allocOptions = array_merge($options, [
                'seed' => $seed,
                'candidate_index' => $candidateIndex,
            ]);

            $allocationData = $this->allocator->allocate($timetable, $preservedSlots, $requirements, $allocOptions);
            $allocatedSlots = $allocationData['slots'];

            // Run optimization
            $optimizeResult = $this->optimizer->optimize($timetable, $allocatedSlots, ['weights' => $customWeights]);
            $optimizedSlots = $optimizeResult['slots'];
            /** @var ScoreBreakdown $scoreBreakdown */
            $scoreBreakdown = $optimizeResult['score_breakdown'];

            // Validate against constraints
            $conflictData = $this->conflictService->detectConflicts($timetable, $optimizedSlots);
            $hardCount = $conflictData['hard_count'];
            $softCount = $conflictData['soft_count'];

            $status = ($hardCount === 0 && empty($allocationData['unallocated_requirements'])) ? 'SUCCESS' : (empty($allocationData['unallocated_requirements']) ? 'PARTIAL' : 'PARTIAL');
            if ($hardCount > 0 && count($optimizedSlots) === 0) {
                $status = 'FAILED';
            }

            $unallocatedList = $allocationData['unallocated_requirements'];
            $explanation = ! empty($unallocatedList)
                ? implode(' | ', array_column($unallocatedList, 'summary'))
                : 'All curriculum requirements allocated without hard conflicts.';

            $result = new GenerationResult(
                status: $status,
                seed: $seed,
                timetableId: $timetable->id,
                candidateNumber: $i + 1,
                score: $scoreBreakdown->totalScore,
                scoreBreakdown: $scoreBreakdown->toArray(),
                hardConflictCount: $hardCount,
                softWarningCount: $softCount,
                allocations: $optimizedSlots->map(fn ($s) => $s->toArray())->toArray(),
                allocatedCount: $allocationData['total_allocated'],
                unallocatedCount: count($unallocatedList),
                unallocatedRequirements: $unallocatedList,
                conflicts: array_map(fn ($c) => $c->toArray(), $conflictData['hard']),
                warnings: array_map(fn ($c) => $c->toArray(), $conflictData['soft']),
                metrics: [
                    'slots_count' => $optimizedSlots->count(),
                    'total_required' => $allocationData['total_required'],
                    'optimization_improved' => $optimizeResult['improved'],
                ],
                explanation: $explanation
            );

            $results[] = $result;

            // Persist candidate record
            $candidateModel = TimetableCandidate::create([
                'school_id' => $timetable->school_id,
                'generation_run_id' => $run->id,
                'timetable_id' => $timetable->id,
                'candidate_number' => $i + 1,
                'score' => $result->score,
                'hard_conflicts_count' => $result->hardConflictCount,
                'soft_warnings_count' => $result->softWarningCount,
                'score_breakdown' => $result->scoreBreakdown,
                'allocations' => $result->allocations,
                'unallocated_requirements' => $result->unallocatedRequirements,
                'metrics' => $result->metrics,
                'is_applied' => false,
            ]);

            $candidates[] = $candidateModel;

            if ($result->score > $bestScore) {
                $bestScore = $result->score;
            }
        }

        // Update run completion
        $run->update([
            'status' => 'completed',
            'score' => $bestScore,
            'result_summary' => [
                'candidates_count' => count($candidates),
                'best_score' => $bestScore,
                'success' => true,
            ],
            'completed_at' => now(),
        ]);

        AuditService::log('generate', $timetable, "Generated {$candidateCount} candidate schedule(s) (Seed: {$seed})", 'timetable');

        return [
            'run' => $run->fresh(['candidates']),
            'candidates' => $candidates,
            'results' => $results,
        ];
    }

    /**
     * Resolve requirements from explicit TimetableRequirement table or fallback to Curriculum.
     */
    public function resolveRequirements(Timetable $timetable, array $options = []): Collection
    {
        $explicitReqs = TimetableRequirement::where('school_id', $timetable->school_id)
            ->where(function ($q) use ($timetable) {
                $q->where('timetable_id', $timetable->id)->orWhereNull('timetable_id');
            })
            ->with(['schoolClass', 'subject', 'teacher', 'room'])
            ->get();

        if ($explicitReqs->isNotEmpty()) {
            return $this->filterRequirements($explicitReqs, $options);
        }

        // Fallback: derive dynamically from Curriculum
        $curricula = Curriculum::where('school_id', $timetable->school_id)
            ->where('academic_year', $timetable->academic_year)
            ->when($timetable->term, fn ($q) => $q->where('term', $timetable->term))
            ->with(['class', 'subject', 'teacher'])
            ->get();

        $derived = $curricula->map(function (Curriculum $c) use ($timetable) {
            // Find Teacher model from user_id if teacher is linked
            $teacherId = null;
            if ($c->teacher_id) {
                $teacher = \App\Models\Teacher::where('school_id', $timetable->school_id)
                    ->where(function ($q) use ($c) {
                        $q->where('user_id', $c->teacher_id)->orWhere('id', $c->teacher_id);
                    })
                    ->first();
                $teacherId = $teacher?->id;
            }

            return [
                'school_id' => $timetable->school_id,
                'timetable_id' => $timetable->id,
                'school_class_id' => $c->class_id,
                'subject_id' => $c->subject_id,
                'teacher_id' => $teacherId,
                'room_id' => null,
                'weekly_periods' => $c->weekly_periods ?? 1,
                'max_daily_lessons' => 2,
                'is_double_period_allowed' => false,
                'priority' => 1,
            ];
        });

        return $this->filterRequirements($derived, $options);
    }

    protected function filterRequirements(Collection $reqs, array $options): Collection
    {
        $filterClassId = $options['class_id'] ?? null;
        $filterTeacherId = $options['teacher_id'] ?? null;
        $filterSubjectId = $options['subject_id'] ?? null;

        return $reqs->filter(function ($r) use ($filterClassId, $filterTeacherId, $filterSubjectId) {
            $cId = is_array($r) ? ($r['school_class_id'] ?? $r['class_id'] ?? null) : $r->school_class_id;
            $tId = is_array($r) ? ($r['teacher_id'] ?? null) : $r->teacher_id;
            $sId = is_array($r) ? ($r['subject_id'] ?? null) : $r->subject_id;

            if ($filterClassId && (int) $cId !== (int) $filterClassId) {
                return false;
            }
            if ($filterTeacherId && (int) $tId !== (int) $filterTeacherId) {
                return false;
            }
            if ($filterSubjectId && (int) $sId !== (int) $filterSubjectId) {
                return false;
            }

            return true;
        })->values();
    }
}
