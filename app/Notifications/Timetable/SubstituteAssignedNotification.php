<?php

namespace App\Notifications\Timetable;

use App\Models\TimetableSubstitution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubstituteAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TimetableSubstitution $substitution,
        public string $recipientRole = 'substitute' // 'substitute', 'original_teacher', 'student', 'admin'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $slot = $this->substitution->slot;
        $dateStr = $this->substitution->date instanceof \Carbon\Carbon ? $this->substitution->date->format('l, d M Y') : (string) $this->substitution->date;

        $msg = match ($this->recipientRole) {
            'substitute' => "You have been assigned as substitute teacher for " . ($slot?->schoolClass?->name ?? 'Class') . " (" . ($slot?->subject?->name ?? 'Subject') . ") on {$dateStr} at " . ($slot?->getFormattedTime() ?? '') . ".",
            'original_teacher' => "A substitute (" . ($this->substitution->substituteTeacher?->full_name ?? 'Teacher') . ") has been assigned to cover your lesson for " . ($slot?->schoolClass?->name ?? 'Class') . " on {$dateStr}.",
            default => "Substitute teacher (" . ($this->substitution->substituteTeacher?->full_name ?? 'Teacher') . ") assigned for " . ($slot?->subject?->name ?? 'Subject') . " on {$dateStr}.",
        };

        return [
            'type' => 'substitute_assigned',
            'substitution_id' => $this->substitution->id,
            'timetable_id' => $this->substitution->timetable_id,
            'slot_id' => $this->substitution->timetable_slot_id,
            'date' => $dateStr,
            'title' => 'Substitute Teacher Assigned',
            'message' => $msg,
            'original_teacher' => $this->substitution->originalTeacher?->full_name,
            'substitute_teacher' => $this->substitution->substituteTeacher?->full_name,
            'class' => $slot?->schoolClass?->name,
            'subject' => $slot?->subject?->name,
            'room' => $slot?->room?->name,
            'time' => $slot?->getFormattedTime(),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
