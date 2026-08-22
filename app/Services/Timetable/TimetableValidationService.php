<?php

namespace App\Services\Timetable;

use App\Models\Curriculum;
use App\Models\Timetable;

class TimetableValidationService
{
    public function __construct(
        protected TimetableConflictService $conflictService
    ) {
    }

    /**
     * Validate an entire timetable for health, conflict-freedom, and curriculum fulfillment.
     */
    public function validate(Timetable $timetable): array
    {
        $conflictResults = $this->conflictService->detectConflicts($timetable);
        $curriculumRequirements = $this->validateCurriculumRequirements($timetable);

        $hardCount = $conflictResults['hard_count'];
        $softCount = $conflictResults['soft_count'];
        $unmetCount = $curriculumRequirements['unmet_count'];

        $isPublishable = ($hardCount === 0);

        // Calculate a deterministic score (0 - 100)
        $score = 100;
        $score -= ($hardCount * 25);
        $score -= ($softCount * 5);
        $score -= ($unmetCount * 3);
        $score = max(0, min(100, $score));

        return [
            'is_valid' => ($hardCount === 0),
            'is_publishable' => $isPublishable,
            'score' => $score,
            'hard_conflicts' => $conflictResults['hard'],
            'soft_warnings' => $conflictResults['soft'],
            'curriculum_requirements' => $curriculumRequirements,
            'summary' => [
                'hard_conflicts_count' => $hardCount,
                'soft_warnings_count' => $softCount,
                'total_slots' => $timetable->slots()->count(),
                'curriculum_unmet_count' => $unmetCount,
            ],
        ];
    }

    /**
     * Validate whether the timetable is allowed to be published.
     */
    public function validateForPublish(Timetable $timetable): array
    {
        $validation = $this->validate($timetable);

        if (! $validation['is_publishable']) {
            $messages = array_map(fn ($c) => $c->message, $validation['hard_conflicts']);

            return [
                'allowed' => false,
                'message' => 'Cannot publish timetable with active hard conflicts. Please resolve all hard conflicts first.',
                'errors' => $messages,
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Timetable is valid and ready to be published.',
            'warnings' => array_map(fn ($c) => $c->message, $validation['soft_warnings']),
        ];
    }

    /**
     * Compare scheduled lesson periods with normalized Curriculum required weekly periods.
     */
    public function validateCurriculumRequirements(Timetable $timetable): array
    {
        $curricula = Curriculum::where('school_id', $timetable->school_id)
            ->where('academic_year', $timetable->academic_year)
            ->when($timetable->term, fn ($q) => $q->where('term', $timetable->term))
            ->with(['class', 'subject', 'teacher'])
            ->get();

        $slots = $timetable->slots()
            ->where('status', '!=', 'cancelled')
            ->get();

        $results = [];
        $unmetCount = 0;

        foreach ($curricula as $curriculum) {
            $classId = $curriculum->class_id;
            $subjectId = $curriculum->subject_id;
            $required = $curriculum->weekly_periods ?? 1;

            $scheduled = $slots->filter(function ($s) use ($classId, $subjectId) {
                return $s->school_class_id === $classId && $s->subject_id === $subjectId && $s->isLesson();
            })->count();

            $difference = $scheduled - $required;
            $isMet = ($scheduled >= $required);

            if (! $isMet) {
                $unmetCount++;
            }

            $results[] = [
                'curriculum_id' => $curriculum->id,
                'class_name' => $curriculum->class?->name ?? "Class #{$classId}",
                'subject_name' => $curriculum->subject?->name ?? "Subject #{$subjectId}",
                'required_periods' => $required,
                'scheduled_periods' => $scheduled,
                'difference' => $difference,
                'is_met' => $isMet,
                'status' => $difference === 0 ? 'exact' : ($difference > 0 ? 'surplus' : 'deficit'),
            ];
        }

        return [
            'items' => $results,
            'unmet_count' => $unmetCount,
            'total_curricula' => $curricula->count(),
        ];
    }
}
