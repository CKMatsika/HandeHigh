<?php

namespace App\Notifications\Timetable;

use App\Models\TimetableOperationalChange;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TimetableOperationalChangeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TimetableOperationalChange $change,
        public string $customMessage = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $slot = $this->change->slot;
        return [
            'type' => 'timetable_change',
            'change_type' => $this->change->change_type,
            'change_id' => $this->change->id,
            'timetable_id' => $this->change->timetable_id,
            'slot_id' => $this->change->timetable_slot_id,
            'title' => $this->change->change_type_label,
            'message' => $this->customMessage ?: "Timetable change: {$this->change->change_type_label} for " . ($slot?->schoolClass?->name ?? 'Class') . " (" . ($slot?->subject?->name ?? 'Subject') . "). Reason: {$this->change->reason}",
            'reason' => $this->change->reason,
            'notes' => $this->change->notes,
            'revision' => $this->change->revision,
            'before_state' => $this->change->before_state,
            'after_state' => $this->change->after_state,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
