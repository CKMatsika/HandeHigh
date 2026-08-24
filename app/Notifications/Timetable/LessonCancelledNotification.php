<?php

namespace App\Notifications\Timetable;

use App\Models\TimetableOperationalChange;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LessonCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TimetableOperationalChange $change
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $slot = $this->change->slot;
        return [
            'type' => 'lesson_cancelled',
            'change_id' => $this->change->id,
            'timetable_id' => $this->change->timetable_id,
            'slot_id' => $this->change->timetable_slot_id,
            'title' => 'Lesson Cancelled',
            'message' => "The " . ($slot?->subject?->name ?? 'Lesson') . " lesson on " . ($slot?->day_of_week ?? '') . " " . ($slot?->getFormattedTime() ?? '') . " has been cancelled. Reason: {$this->change->reason}",
            'reason' => $this->change->reason,
            'notes' => $this->change->notes,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
