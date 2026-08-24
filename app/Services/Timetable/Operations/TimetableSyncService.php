<?php

namespace App\Services\Timetable\Operations;

use App\Models\Timetable;
use App\Models\TimetableOperationalChange;
use Illuminate\Support\Collection;

class TimetableSyncService
{
    /**
     * Get revision metadata for a timetable.
     */
    public function getVersion(Timetable $timetable): array
    {
        $latestChange = $timetable->operationalChanges()->latest('created_at')->first();

        return [
            'timetable_id' => $timetable->id,
            'school_id' => $timetable->school_id,
            'status' => $timetable->status,
            'revision' => (int) ($timetable->revision ?? 1),
            'published_at' => $timetable->published_at?->toIso8601String(),
            'last_changed_at' => $latestChange?->created_at?->toIso8601String(),
            'total_changes' => $timetable->operationalChanges()->count(),
        ];
    }

    /**
     * Get operational changes since a specific revision.
     */
    public function getChangesSince(Timetable $timetable, int $sinceRevision): Collection
    {
        return $timetable->operationalChanges()
            ->where('revision', '>', $sinceRevision)
            ->with(['slot.schoolClass', 'slot.subject', 'slot.teacher', 'slot.room', 'changedBy'])
            ->orderBy('revision', 'asc')
            ->get();
    }

    /**
     * Get human-readable change diff history.
     */
    public function getChangeHistory(Timetable $timetable, int $limit = 50): Collection
    {
        return $timetable->operationalChanges()
            ->with(['slot.schoolClass', 'slot.subject', 'slot.teacher', 'slot.room', 'changedBy'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($change) {
                return [
                    'id' => $change->id,
                    'revision' => $change->revision,
                    'change_type' => $change->change_type,
                    'change_type_label' => $change->change_type_label,
                    'reason' => $change->reason,
                    'notes' => $change->notes,
                    'changed_by' => $change->changedBy?->name ?? 'System',
                    'created_at' => $change->created_at->format('d M Y H:i'),
                    'class_name' => $change->slot?->schoolClass?->name,
                    'subject_name' => $change->slot?->subject?->name,
                    'day_of_week' => $change->slot?->day_of_week,
                    'time' => $change->slot?->getFormattedTime(),
                    'before_state' => $change->before_state,
                    'after_state' => $change->after_state,
                ];
            });
    }
}
