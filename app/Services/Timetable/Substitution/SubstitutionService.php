<?php

namespace App\Services\Timetable\Substitution;

use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use App\Models\User;
use App\Services\Timetable\Operations\TimetableOperationalChangeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SubstitutionService
{
    public function __construct(
        protected SubstituteCandidateFinder $candidateFinder,
        protected SubstituteEligibilityService $eligibilityService,
        protected SubstitutionScorer $scorer,
        protected TimetableOperationalChangeService $changeService
    ) {}

    /**
     * Generate ranked substitute recommendations for a timetable slot on a specific date.
     *
     * @return Collection<SubstitutionResult>
     */
    public function recommendSubstitutes(TimetableSlot $slot, string|Carbon $date): Collection
    {
        $timetable = $slot->timetable;
        if (! $timetable) {
            throw new InvalidArgumentException("Slot #{$slot->id} is missing an associated timetable.");
        }

        $candidates = $this->candidateFinder->findCandidates($timetable->school_id, $slot->teacher_id);
        $results = collect();

        foreach ($candidates as $candidate) {
            $eligibility = $this->eligibilityService->checkEligibility($candidate, $timetable, $slot, $date);

            if ($eligibility['eligible']) {
                $scored = $this->scorer->scoreCandidate(
                    $candidate,
                    $timetable,
                    $slot,
                    $date,
                    $eligibility['is_subject_qualified']
                );
                $results->push($scored);
            }
        }

        return $results->sortByDesc('score')->values();
    }

    /**
     * Create a pending substitute recommendation for administrator review.
     */
    public function createPendingSubstitution(
        TimetableSlot $slot,
        Teacher $substituteTeacher,
        string|Carbon $date,
        ?TeacherAbsence $absence = null,
        ?string $reason = null,
        ?string $notes = null
    ): TimetableSubstitution {
        $timetable = $slot->timetable;
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $eligibility = $this->eligibilityService->checkEligibility($substituteTeacher, $timetable, $slot, $dateStr);
        if (! $eligibility['eligible']) {
            throw new InvalidArgumentException("Teacher is not eligible: " . implode(', ', $eligibility['reasons']));
        }

        $scored = $this->scorer->scoreCandidate(
            $substituteTeacher,
            $timetable,
            $slot,
            $dateStr,
            $eligibility['is_subject_qualified']
        );

        return TimetableSubstitution::create([
            'school_id' => $timetable->school_id,
            'timetable_id' => $timetable->id,
            'timetable_slot_id' => $slot->id,
            'teacher_absence_id' => $absence?->id,
            'original_teacher_id' => $slot->teacher_id,
            'substitute_teacher_id' => $substituteTeacher->id,
            'date' => $dateStr,
            'status' => 'pending',
            'score' => $scored->score,
            'score_breakdown' => $scored->breakdown,
            'reason' => $reason ?? 'Teacher absence cover',
            'notes' => $notes,
        ]);
    }

    /**
     * Approve and apply a substitution after real-time revalidation.
     */
    public function approveSubstitution(
        TimetableSubstitution $substitution,
        User $approver,
        ?int $expectedRevision = null
    ): TimetableSubstitution {
        $slot = $substitution->slot;
        $timetable = $substitution->timetable;
        $substituteTeacher = $substitution->substituteTeacher;

        // 1. Real-time revalidation immediately before assignment
        $eligibility = $this->eligibilityService->checkEligibility(
            $substituteTeacher,
            $timetable,
            $slot,
            $substitution->date
        );

        if (! $eligibility['eligible']) {
            $reasons = implode(', ', $eligibility['reasons']);
            $substitution->status = 'cancelled';
            $substitution->notes = "Approval failed revalidation: {$reasons}";
            $substitution->save();

            throw new InvalidArgumentException("Cannot approve substitution: Teacher became unavailable ({$reasons}).");
        }

        // 2. Apply the operational change through the change service
        return $this->changeService->assignSubstitute(
            timetable: $timetable,
            slot: $slot,
            substituteTeacher: $substituteTeacher,
            date: $substitution->date,
            reason: $substitution->reason ?? 'Approved substitution',
            notes: $substitution->notes,
            actor: $approver,
            absence: $substitution->teacherAbsence,
            expectedRevision: $expectedRevision,
            existingSubstitution: $substitution
        );
    }

    /**
     * Reject a pending substitution.
     */
    public function rejectSubstitution(TimetableSubstitution $substitution, User $actor, ?string $reason = null): TimetableSubstitution
    {
        $substitution->status = 'rejected';
        if ($reason) {
            $substitution->notes = ($substitution->notes ? $substitution->notes . ' | ' : '') . "Rejection reason: {$reason}";
        }
        $substitution->save();

        return $substitution;
    }
}
