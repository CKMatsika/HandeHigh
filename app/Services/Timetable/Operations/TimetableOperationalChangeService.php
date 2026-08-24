<?php

namespace App\Services\Timetable\Operations;

use App\Events\Timetable\LessonCancelled;
use App\Events\Timetable\LessonMoved;
use App\Events\Timetable\LessonRestored;
use App\Events\Timetable\RoomChanged;
use App\Events\Timetable\SubstituteAssigned;
use App\Events\Timetable\TeacherChanged;
use App\Events\Timetable\TimetableChanged;
use App\Models\Room;
use App\Models\SchoolPeriod;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableOperationalChange;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use App\Models\User;
use App\Services\Timetable\TimetableConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TimetableOperationalChangeService
{
    public function __construct(
        protected TimetableConflictService $conflictService
    ) {}

    /**
     * Change the teacher for an existing timetable lesson slot.
     */
    public function changeTeacher(
        Timetable $timetable,
        TimetableSlot $slot,
        Teacher $newTeacher,
        string $reason,
        ?string $notes = null,
        ?User $actor = null,
        ?int $expectedRevision = null
    ): TimetableOperationalChange {
        return DB::transaction(function () use ($timetable, $slot, $newTeacher, $reason, $notes, $actor, $expectedRevision) {
            $this->validateTenant($timetable, $slot);
            $this->validateRevision($timetable, $expectedRevision);

            if ($newTeacher->school_id !== $timetable->school_id) {
                throw new InvalidArgumentException("Teacher #{$newTeacher->id} does not belong to this school.");
            }

            if ($slot->isLocked()) {
                throw new InvalidArgumentException("Slot #{$slot->id} is locked and cannot be modified.");
            }

            // Check if new teacher has conflict at this slot time
            $conflictCheck = $this->conflictService->checkSlotConflicts($timetable, [
                'school_class_id' => $slot->school_class_id,
                'subject_id' => $slot->subject_id,
                'teacher_id' => $newTeacher->id,
                'room_id' => $slot->room_id,
                'school_period_id' => $slot->school_period_id,
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'slot_type' => $slot->slot_type,
                'status' => 'scheduled',
            ], ignoreSlotId: $slot->id);

            if (! empty($conflictCheck['hard'])) {
                $msg = $conflictCheck['hard'][0]->message;
                throw new InvalidArgumentException("Cannot assign teacher: {$msg}");
            }

            $oldTeacher = $slot->teacher;
            $beforeState = [
                'teacher_id' => $slot->teacher_id,
                'teacher_name' => $oldTeacher?->full_name,
            ];

            $slot->teacher_id = $newTeacher->id;
            $slot->save();

            $afterState = [
                'teacher_id' => $newTeacher->id,
                'teacher_name' => $newTeacher->full_name,
            ];

            $newRevision = $timetable->incrementRevision();

            $change = TimetableOperationalChange::create([
                'school_id' => $timetable->school_id,
                'timetable_id' => $timetable->id,
                'timetable_slot_id' => $slot->id,
                'change_type' => 'teacher_change',
                'reason' => $reason,
                'notes' => $notes,
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'changed_by' => $actor?->id,
                'revision' => $newRevision,
            ]);

            event(new TeacherChanged($timetable, $slot, $oldTeacher, $newTeacher, $change, $actor));
            event(new TimetableChanged($timetable, $change, $actor));

            return $change;
        });
    }

    /**
     * Change the room for an existing timetable lesson slot.
     */
    public function changeRoom(
        Timetable $timetable,
        TimetableSlot $slot,
        Room $newRoom,
        string $reason,
        ?string $notes = null,
        ?User $actor = null,
        ?int $expectedRevision = null
    ): TimetableOperationalChange {
        return DB::transaction(function () use ($timetable, $slot, $newRoom, $reason, $notes, $actor, $expectedRevision) {
            $this->validateTenant($timetable, $slot);
            $this->validateRevision($timetable, $expectedRevision);

            if ($newRoom->school_id !== $timetable->school_id) {
                throw new InvalidArgumentException("Room #{$newRoom->id} does not belong to this school.");
            }

            if ($slot->isLocked()) {
                throw new InvalidArgumentException("Slot #{$slot->id} is locked and cannot be modified.");
            }

            // Check if new room is available at this time
            $conflictCheck = $this->conflictService->checkSlotConflicts($timetable, [
                'school_class_id' => $slot->school_class_id,
                'subject_id' => $slot->subject_id,
                'teacher_id' => $slot->teacher_id,
                'room_id' => $newRoom->id,
                'school_period_id' => $slot->school_period_id,
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'slot_type' => $slot->slot_type,
                'status' => 'scheduled',
            ], ignoreSlotId: $slot->id);

            if (! empty($conflictCheck['hard'])) {
                $msg = $conflictCheck['hard'][0]->message;
                throw new InvalidArgumentException("Cannot assign room: {$msg}");
            }

            $oldRoom = $slot->room;
            $beforeState = [
                'room_id' => $slot->room_id,
                'room_name' => $oldRoom?->name,
            ];

            $slot->room_id = $newRoom->id;
            $slot->save();

            $afterState = [
                'room_id' => $newRoom->id,
                'room_name' => $newRoom->name,
            ];

            $newRevision = $timetable->incrementRevision();

            $change = TimetableOperationalChange::create([
                'school_id' => $timetable->school_id,
                'timetable_id' => $timetable->id,
                'timetable_slot_id' => $slot->id,
                'change_type' => 'room_change',
                'reason' => $reason,
                'notes' => $notes,
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'changed_by' => $actor?->id,
                'revision' => $newRevision,
            ]);

            event(new RoomChanged($timetable, $slot, $oldRoom, $newRoom, $change, $actor));
            event(new TimetableChanged($timetable, $change, $actor));

            return $change;
        });
    }

    /**
     * Move an existing lesson slot to a different day, time, period, and/or room.
     */
    public function moveLesson(
        Timetable $timetable,
        TimetableSlot $slot,
        string $newDay,
        string $newStartTime,
        string $newEndTime,
        ?SchoolPeriod $newPeriod = null,
        ?Room $newRoom = null,
        string $reason = 'Operational reschedule',
        ?string $notes = null,
        ?User $actor = null,
        ?int $expectedRevision = null
    ): TimetableOperationalChange {
        return DB::transaction(function () use ($timetable, $slot, $newDay, $newStartTime, $newEndTime, $newPeriod, $newRoom, $reason, $notes, $actor, $expectedRevision) {
            $this->validateTenant($timetable, $slot);
            $this->validateRevision($timetable, $expectedRevision);

            if ($slot->isLocked()) {
                throw new InvalidArgumentException("Slot #{$slot->id} is locked and cannot be moved.");
            }

            $roomId = $newRoom ? $newRoom->id : $slot->room_id;
            if ($newRoom && $newRoom->school_id !== $timetable->school_id) {
                throw new InvalidArgumentException("Room #{$newRoom->id} does not belong to this school.");
            }

            $periodId = $newPeriod ? $newPeriod->id : $slot->school_period_id;
            if ($newPeriod && $newPeriod->school_id !== $timetable->school_id) {
                throw new InvalidArgumentException("Period #{$newPeriod->id} does not belong to this school.");
            }

            // Validate constraints at target slot
            $conflictCheck = $this->conflictService->checkSlotConflicts($timetable, [
                'school_class_id' => $slot->school_class_id,
                'subject_id' => $slot->subject_id,
                'teacher_id' => $slot->teacher_id,
                'room_id' => $roomId,
                'school_period_id' => $periodId,
                'day_of_week' => $newDay,
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'slot_type' => $slot->slot_type,
                'status' => 'scheduled',
            ], ignoreSlotId: $slot->id);

            if (! empty($conflictCheck['hard'])) {
                $msg = $conflictCheck['hard'][0]->message;
                throw new InvalidArgumentException("Cannot move lesson: {$msg}");
            }

            $beforeState = [
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'school_period_id' => $slot->school_period_id,
                'room_id' => $slot->room_id,
            ];

            $slot->day_of_week = $newDay;
            $slot->start_time = $newStartTime;
            $slot->end_time = $newEndTime;
            $slot->school_period_id = $periodId;
            if ($newRoom) {
                $slot->room_id = $newRoom->id;
            }
            $slot->save();

            $afterState = [
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'school_period_id' => $slot->school_period_id,
                'room_id' => $slot->room_id,
            ];

            $newRevision = $timetable->incrementRevision();

            $change = TimetableOperationalChange::create([
                'school_id' => $timetable->school_id,
                'timetable_id' => $timetable->id,
                'timetable_slot_id' => $slot->id,
                'change_type' => 'lesson_moved',
                'reason' => $reason,
                'notes' => $notes,
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'changed_by' => $actor?->id,
                'revision' => $newRevision,
            ]);

            event(new LessonMoved($timetable, $slot, $beforeState, $afterState, $change, $actor));
            event(new TimetableChanged($timetable, $change, $actor));

            return $change;
        });
    }

    /**
     * Cancel an existing timetable lesson slot operationally without deleting the historical record.
     */
    public function cancelLesson(
        Timetable $timetable,
        TimetableSlot $slot,
        string $reason,
        ?string $notes = null,
        ?User $actor = null,
        ?int $expectedRevision = null
    ): TimetableOperationalChange {
        return DB::transaction(function () use ($timetable, $slot, $reason, $notes, $actor, $expectedRevision) {
            $this->validateTenant($timetable, $slot);
            $this->validateRevision($timetable, $expectedRevision);

            if ($slot->isLocked()) {
                throw new InvalidArgumentException("Slot #{$slot->id} is locked and cannot be cancelled.");
            }

            $beforeState = [
                'status' => $slot->status,
            ];

            $slot->status = 'cancelled';
            $slot->save();

            $afterState = [
                'status' => 'cancelled',
            ];

            $newRevision = $timetable->incrementRevision();

            $change = TimetableOperationalChange::create([
                'school_id' => $timetable->school_id,
                'timetable_id' => $timetable->id,
                'timetable_slot_id' => $slot->id,
                'change_type' => 'lesson_cancelled',
                'reason' => $reason,
                'notes' => $notes,
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'changed_by' => $actor?->id,
                'revision' => $newRevision,
            ]);

            event(new LessonCancelled($timetable, $slot, $change, $actor));
            event(new TimetableChanged($timetable, $change, $actor));

            return $change;
        });
    }

    /**
     * Restore a previously cancelled lesson slot.
     */
    public function restoreLesson(
        Timetable $timetable,
        TimetableSlot $slot,
        string $reason = 'Restored lesson',
        ?string $notes = null,
        ?User $actor = null,
        ?int $expectedRevision = null
    ): TimetableOperationalChange {
        return DB::transaction(function () use ($timetable, $slot, $reason, $notes, $actor, $expectedRevision) {
            $this->validateTenant($timetable, $slot);
            $this->validateRevision($timetable, $expectedRevision);

            if ($slot->status !== 'cancelled') {
                throw new InvalidArgumentException("Slot #{$slot->id} is not cancelled.");
            }

            // Revalidate that restoring this slot does not cause hard conflicts
            $conflictCheck = $this->conflictService->checkSlotConflicts($timetable, [
                'school_class_id' => $slot->school_class_id,
                'subject_id' => $slot->subject_id,
                'teacher_id' => $slot->teacher_id,
                'room_id' => $slot->room_id,
                'school_period_id' => $slot->school_period_id,
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'slot_type' => $slot->slot_type,
                'status' => 'scheduled',
            ], ignoreSlotId: $slot->id);

            if (! empty($conflictCheck['hard'])) {
                $msg = $conflictCheck['hard'][0]->message;
                throw new InvalidArgumentException("Cannot restore lesson due to conflicts: {$msg}");
            }

            $beforeState = [
                'status' => $slot->status,
            ];

            $slot->status = 'scheduled';
            $slot->save();

            $afterState = [
                'status' => 'scheduled',
            ];

            $newRevision = $timetable->incrementRevision();

            $change = TimetableOperationalChange::create([
                'school_id' => $timetable->school_id,
                'timetable_id' => $timetable->id,
                'timetable_slot_id' => $slot->id,
                'change_type' => 'lesson_restored',
                'reason' => $reason,
                'notes' => $notes,
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'changed_by' => $actor?->id,
                'revision' => $newRevision,
            ]);

            event(new LessonRestored($timetable, $slot, $change, $actor));
            event(new TimetableChanged($timetable, $change, $actor));

            return $change;
        });
    }

    /**
     * Assign and approve a substitute teacher for a specific slot and date.
     */
    public function assignSubstitute(
        Timetable $timetable,
        TimetableSlot $slot,
        Teacher $substituteTeacher,
        string|Carbon $date,
        string $reason,
        ?string $notes = null,
        ?User $actor = null,
        ?TeacherAbsence $absence = null,
        ?int $expectedRevision = null,
        ?TimetableSubstitution $existingSubstitution = null
    ): TimetableSubstitution {
        return DB::transaction(function () use ($timetable, $slot, $substituteTeacher, $date, $reason, $notes, $actor, $absence, $expectedRevision, $existingSubstitution) {
            $this->validateTenant($timetable, $slot);
            $this->validateRevision($timetable, $expectedRevision);

            if ($substituteTeacher->school_id !== $timetable->school_id) {
                throw new InvalidArgumentException("Substitute teacher #{$substituteTeacher->id} does not belong to this school.");
            }

            $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

            // Revalidate substitute availability on this date/time
            $conflictCheck = $this->conflictService->checkSlotConflicts($timetable, [
                'school_class_id' => $slot->school_class_id,
                'subject_id' => $slot->subject_id,
                'teacher_id' => $substituteTeacher->id,
                'room_id' => $slot->room_id,
                'school_period_id' => $slot->school_period_id,
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'slot_type' => $slot->slot_type,
                'status' => 'scheduled',
            ], ignoreSlotId: $slot->id);

            if (! empty($conflictCheck['hard'])) {
                $msg = $conflictCheck['hard'][0]->message;
                throw new InvalidArgumentException("Substitute teacher has a scheduling conflict: {$msg}");
            }

            // Check if substitute teacher is absent on this date
            $isAbsent = TeacherAbsence::where('teacher_id', $substituteTeacher->id)
                ->where('status', 'active')
                ->where('start_date', '<=', $dateStr)
                ->where('end_date', '>=', $dateStr)
                ->exists();

            if ($isAbsent) {
                throw new InvalidArgumentException("Substitute teacher is marked as absent on {$dateStr}.");
            }

            // Check if already assigned as substitute for another slot on same date and overlapping time
            $existingSub = TimetableSubstitution::where('substitute_teacher_id', $substituteTeacher->id)
                ->where('date', $dateStr)
                ->where('status', 'approved')
                ->when($existingSubstitution?->id, fn ($q) => $q->where('id', '!=', $existingSubstitution->id))
                ->whereHas('slot', function ($q) use ($slot) {
                    $q->where('day_of_week', $slot->day_of_week)
                        ->where(function ($sq) use ($slot) {
                            $sq->where('start_time', '<', $slot->end_time)
                                ->where('end_time', '>', $slot->start_time);
                        });
                })
                ->exists();

            if ($existingSub) {
                throw new InvalidArgumentException("Substitute teacher is already assigned to another class at this time.");
            }

            if ($existingSubstitution) {
                $substitution = $existingSubstitution;
                $substitution->status = 'approved';
                $substitution->approved_by = $actor?->id;
                $substitution->approved_at = now();
                $substitution->save();
            } else {
                $substitution = TimetableSubstitution::create([
                    'school_id' => $timetable->school_id,
                    'timetable_id' => $timetable->id,
                    'timetable_slot_id' => $slot->id,
                    'teacher_absence_id' => $absence?->id,
                    'original_teacher_id' => $slot->teacher_id,
                    'substitute_teacher_id' => $substituteTeacher->id,
                    'date' => $dateStr,
                    'status' => 'approved',
                    'reason' => $reason,
                    'notes' => $notes,
                    'approved_by' => $actor?->id,
                    'approved_at' => now(),
                ]);
            }

            $newRevision = $timetable->incrementRevision();

            $change = TimetableOperationalChange::create([
                'school_id' => $timetable->school_id,
                'timetable_id' => $timetable->id,
                'timetable_slot_id' => $slot->id,
                'change_type' => 'substitute_assigned',
                'reason' => $reason,
                'notes' => "Substitute: {$substituteTeacher->full_name} covering for {$slot->teacher?->full_name} on {$dateStr}. " . ($notes ?? ''),
                'before_state' => ['teacher_id' => $slot->teacher_id, 'teacher_name' => $slot->teacher?->full_name],
                'after_state' => ['substitute_teacher_id' => $substituteTeacher->id, 'substitute_name' => $substituteTeacher->full_name, 'date' => $dateStr],
                'changed_by' => $actor?->id,
                'revision' => $newRevision,
            ]);

            event(new SubstituteAssigned($timetable, $slot, $substitution, $change, $actor));
            event(new TimetableChanged($timetable, $change, $actor));

            return $substitution;
        });
    }

    protected function validateTenant(Timetable $timetable, TimetableSlot $slot): void
    {
        if ($slot->timetable_id !== $timetable->id) {
            throw new InvalidArgumentException("Slot #{$slot->id} does not belong to Timetable #{$timetable->id}.");
        }
    }

    protected function validateRevision(Timetable $timetable, ?int $expectedRevision): void
    {
        if ($expectedRevision !== null && $timetable->revision !== $expectedRevision) {
            throw new RuntimeException("Stale timetable revision. Current version is {$timetable->revision}, but version {$expectedRevision} was expected.");
        }
    }
}
