<?php

namespace App\Services\Timetable\Generation;

use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;

class LockedSlotManager
{
    /**
     * Get all locked slots for a timetable.
     */
    public function getLockedSlots(Timetable $timetable): Collection
    {
        return $timetable->slots()
            ->where('is_locked', true)
            ->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod'])
            ->get();
    }

    /**
     * Lock a specific timetable slot.
     */
    public function lockSlot(TimetableSlot $slot): TimetableSlot
    {
        $slot->update(['is_locked' => true]);

        return $slot->fresh();
    }

    /**
     * Unlock a specific timetable slot.
     */
    public function unlockSlot(TimetableSlot $slot): TimetableSlot
    {
        $slot->update(['is_locked' => false]);

        return $slot->fresh();
    }

    /**
     * Get slots that should be preserved during regeneration.
     *
     * In a full regeneration with preserve_locked=true, all locked slots are preserved.
     * In a partial regeneration (e.g. filter by teacher/class/subject/day), unaffected slots AND all locked slots are preserved.
     */
    public function getPreservedSlots(Timetable $timetable, array $options = []): Collection
    {
        $preserveLocked = $options['preserve_locked'] ?? true;
        $filterClassId = $options['class_id'] ?? null;
        $filterTeacherId = $options['teacher_id'] ?? null;
        $filterSubjectId = $options['subject_id'] ?? null;
        $filterDay = $options['day_of_week'] ?? null;

        $query = $timetable->slots()
            ->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod']);

        // If not a partial regeneration, only preserve locked slots (if preserveLocked is true)
        $isPartial = ! empty($filterClassId) || ! empty($filterTeacherId) || ! empty($filterSubjectId) || ! empty($filterDay);

        if (! $isPartial) {
            if ($preserveLocked) {
                return $query->where('is_locked', true)->get();
            }

            return collect();
        }

        // In partial regeneration:
        // Preserved = (locked slots) OR (slots outside the regeneration scope)
        return $query->get()->filter(function (TimetableSlot $slot) use ($preserveLocked, $filterClassId, $filterTeacherId, $filterSubjectId, $filterDay) {
            if ($preserveLocked && $slot->isLocked()) {
                return true;
            }

            // Check if slot is OUTSIDE the partial regeneration target
            if ($filterClassId && (int) $slot->school_class_id !== (int) $filterClassId) {
                return true;
            }
            if ($filterTeacherId && (int) $slot->teacher_id !== (int) $filterTeacherId) {
                return true;
            }
            if ($filterSubjectId && (int) $slot->subject_id !== (int) $filterSubjectId) {
                return true;
            }
            if ($filterDay && strcasecmp((string) $slot->day_of_week, (string) $filterDay) !== 0) {
                return true;
            }

            return false;
        })->values();
    }
}
