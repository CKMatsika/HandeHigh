<?php

namespace App\Services\Timetable;

use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Services\Timetable\Constraints\ActivePeriodConstraint;
use App\Services\Timetable\Constraints\ClassDoubleBookingConstraint;
use App\Services\Timetable\Constraints\ConsecutiveLessonLimitConstraint;
use App\Services\Timetable\Constraints\CoreSubjectMorningPreferenceConstraint;
use App\Services\Timetable\Constraints\DuplicateLessonConstraint;
use App\Services\Timetable\Constraints\ExaminationConflictConstraint;
use App\Services\Timetable\Constraints\FixedActivityConflictConstraint;
use App\Services\Timetable\Constraints\RoomDoubleBookingConstraint;
use App\Services\Timetable\Constraints\SubjectDailySpreadConstraint;
use App\Services\Timetable\Constraints\TeacherDoubleBookingConstraint;
use App\Services\Timetable\Constraints\TeacherSubjectEligibilityConstraint;
use App\Services\Timetable\Constraints\TeacherWorkloadBalanceConstraint;
use App\Services\Timetable\Constraints\TenantIsolationConstraint;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use Illuminate\Support\Collection;

class TimetableConflictService
{
    /** @var TimetableConstraintInterface[] */
    protected array $hardConstraints = [];

    /** @var TimetableConstraintInterface[] */
    protected array $softConstraints = [];

    public function __construct()
    {
        $this->registerDefaultConstraints();
    }

    protected function registerDefaultConstraints(): void
    {
        // Hard constraints (must be zero for publishable timetable)
        $this->hardConstraints = [
            new TenantIsolationConstraint(),
            new TeacherDoubleBookingConstraint(),
            new ClassDoubleBookingConstraint(),
            new RoomDoubleBookingConstraint(),
            new FixedActivityConflictConstraint(),
            new ExaminationConflictConstraint(),
            new TeacherSubjectEligibilityConstraint(),
            new ActivePeriodConstraint(),
            new DuplicateLessonConstraint(),
        ];

        // Soft constraints (warnings & scoring foundation for Phase 3D)
        $this->softConstraints = [
            new SubjectDailySpreadConstraint(),
            new TeacherWorkloadBalanceConstraint(),
            new ConsecutiveLessonLimitConstraint(),
            new CoreSubjectMorningPreferenceConstraint(),
        ];
    }

    /**
     * Run all constraint evaluations on a timetable.
     *
     * @param  Timetable  $timetable
     * @param  Collection|null  $slots
     * @param  array  $context
     * @return array ['hard' => TimetableConflict[], 'soft' => TimetableConflict[], 'all' => TimetableConflict[]]
     */
    public function detectConflicts(Timetable $timetable, ?Collection $slots = null, array $context = []): array
    {
        if ($slots === null) {
            $slots = $timetable->slots()
                ->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod'])
                ->get();
        }

        $hardConflicts = [];
        foreach ($this->hardConstraints as $constraint) {
            $results = $constraint->evaluate($timetable, $slots, $context);
            foreach ($results as $conflict) {
                $hardConflicts[] = $conflict;
            }
        }

        $softConflicts = [];
        foreach ($this->softConstraints as $constraint) {
            $results = $constraint->evaluate($timetable, $slots, $context);
            foreach ($results as $conflict) {
                $softConflicts[] = $conflict;
            }
        }

        return [
            'hard' => $hardConflicts,
            'soft' => $softConflicts,
            'all' => array_merge($hardConflicts, $softConflicts),
            'has_hard_conflicts' => ! empty($hardConflicts),
            'hard_count' => count($hardConflicts),
            'soft_count' => count($softConflicts),
        ];
    }

    /**
     * Check conflicts for a proposed single slot (or edit) against existing timetable slots.
     */
    public function checkSlotConflicts(
        Timetable $timetable,
        array $slotData,
        ?int $ignoreSlotId = null
    ): array {
        // Build a temporary slot instance
        $tempSlot = new TimetableSlot($slotData);
        $tempSlot->timetable_id = $timetable->id;
        $tempSlot->id = $ignoreSlotId ?? 0;

        // Fetch existing slots except the one being edited
        $existingSlots = $timetable->slots()
            ->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod'])
            ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
            ->get();

        $allSlots = $existingSlots->concat([$tempSlot]);

        return $this->detectConflicts($timetable, $allSlots);
    }

    /**
     * Determine if timetable has any hard conflicts.
     */
    public function hasHardConflicts(Timetable $timetable): bool
    {
        $result = $this->detectConflicts($timetable);

        return $result['has_hard_conflicts'];
    }

    /**
     * Sync conflict data directly onto the timetable_slots table records.
     */
    public function syncSlotConflicts(Timetable $timetable): void
    {
        $result = $this->detectConflicts($timetable);
        $conflictsBySlotId = [];

        foreach ($result['all'] as $conflict) {
            if ($conflict->slotId) {
                $conflictsBySlotId[$conflict->slotId][] = $conflict->toArray();
            }
        }

        foreach ($timetable->slots as $slot) {
            $slotConflicts = $conflictsBySlotId[$slot->id] ?? null;
            $hasHard = false;

            if ($slotConflicts) {
                foreach ($slotConflicts as $c) {
                    if (($c['severity'] ?? '') === TimetableConflict::SEVERITY_HARD) {
                        $hasHard = true;
                        break;
                    }
                }
            }

            $slot->update([
                'status' => $hasHard ? 'conflict' : 'scheduled',
                'conflicts' => $slotConflicts,
            ]);
        }
    }
}
