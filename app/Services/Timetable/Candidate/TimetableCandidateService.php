<?php

namespace App\Services\Timetable\Candidate;

use App\Models\Timetable;
use App\Models\TimetableCandidate;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Timetable\TimetableConflictService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimetableCandidateService
{
    public function __construct(
        protected TimetableConflictService $conflictService
    ) {
    }

    /**
     * Apply a candidate schedule to a timetable transactionally.
     *
     * @param  Timetable  $timetable
     * @param  TimetableCandidate  $candidate
     * @param  User|null  $user
     * @return array
     *
     * @throws ValidationException
     */
    public function applyCandidate(Timetable $timetable, TimetableCandidate $candidate, ?User $user = null): array
    {
        // 1. Multi-tenant ownership check
        if ((int) $candidate->school_id !== (int) $timetable->school_id || (int) $candidate->timetable_id !== (int) $timetable->id) {
            throw ValidationException::withMessages([
                'candidate' => ['The specified candidate schedule does not belong to this school timetable.'],
            ]);
        }

        $allocations = $candidate->allocations ?? [];
        if (empty($allocations)) {
            throw ValidationException::withMessages([
                'candidate' => ['The candidate schedule contains no slot allocations to apply.'],
            ]);
        }

        // 2. Pre-application Revalidation: Convert candidate allocations to temporary slots and verify hard constraints
        $testSlots = collect();
        foreach ($allocations as $item) {
            $testSlot = new TimetableSlot($item);
            $testSlot->timetable_id = $timetable->id;
            $testSlots->push($testSlot);
        }

        $revalidation = $this->conflictService->detectConflicts($timetable, $testSlots);
        if ($revalidation['has_hard_conflicts']) {
            $errorMessages = array_map(fn ($c) => $c->message, $revalidation['hard']);
            throw ValidationException::withMessages([
                'candidate' => array_merge(
                    ['Cannot apply candidate because environmental constraints have changed and introduced hard conflicts.'],
                    $errorMessages
                ),
            ]);
        }

        // 3. Transactional Application
        return DB::transaction(function () use ($timetable, $candidate, $allocations, $revalidation) {
            // Keep locked slots, delete unlocked slots
            $lockedSlots = $timetable->slots()->where('is_locked', true)->get();
            $timetable->slots()->where('is_locked', false)->delete();

            $createdSlots = [];
            foreach ($allocations as $alloc) {
                // If a locked slot already exists at this exact period/class, skip inserting duplicate
                $alreadyExists = $lockedSlots->first(function ($ls) use ($alloc) {
                    return (int) $ls->school_class_id === (int) ($alloc['school_class_id'] ?? null)
                        && strcasecmp((string) $ls->day_of_week, (string) ($alloc['day_of_week'] ?? '')) === 0
                        && (int) $ls->school_period_id === (int) ($alloc['school_period_id'] ?? null);
                });

                if ($alreadyExists) {
                    continue;
                }

                $createdSlots[] = $timetable->slots()->create([
                    'school_class_id' => $alloc['school_class_id'],
                    'subject_id' => $alloc['subject_id'],
                    'teacher_id' => $alloc['teacher_id'] ?? null,
                    'room_id' => $alloc['room_id'] ?? null,
                    'school_period_id' => $alloc['school_period_id'] ?? null,
                    'day_of_week' => $alloc['day_of_week'],
                    'start_time' => $alloc['start_time'],
                    'end_time' => $alloc['end_time'],
                    'slot_type' => $alloc['slot_type'] ?? 'lesson',
                    'status' => 'scheduled',
                    'is_locked' => (bool) ($alloc['is_locked'] ?? false),
                    'ai_score' => $candidate->score,
                ]);
            }

            // Sync all conflict flags
            $this->conflictService->syncSlotConflicts($timetable);

            // Update candidate and timetable status
            $candidate->update([
                'is_applied' => true,
                'applied_at' => now(),
            ]);

            $timetable->update([
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            AuditService::log('apply_candidate', $timetable, "Applied candidate #{$candidate->candidate_number} (Score: {$candidate->score}) to timetable {$timetable->name}", 'timetable');

            return [
                'success' => true,
                'message' => "Candidate #{$candidate->candidate_number} applied successfully.",
                'applied_slots_count' => count($createdSlots),
                'candidate' => $candidate->fresh(),
                'timetable' => $timetable->fresh(),
            ];
        });
    }
}
