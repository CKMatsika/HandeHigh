<?php

namespace App\Listeners\Timetable;

use App\Events\Timetable\LessonCancelled;
use App\Events\Timetable\LessonMoved;
use App\Events\Timetable\LessonRestored;
use App\Events\Timetable\RoomChanged;
use App\Events\Timetable\TeacherChanged;
use App\Events\Timetable\TimetableChanged;
use App\Models\User;
use App\Notifications\Timetable\LessonCancelledNotification;
use App\Notifications\Timetable\TimetableOperationalChangeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTimetableChangeNotifications
{
    public function handle(object $event): void
    {
        $timetable = $event->timetable ?? null;
        $change = $event->change ?? null;
        $slot = $event->slot ?? ($change?->slot ?? null);

        if (! $timetable || ! $change) {
            return;
        }

        $recipients = collect();

        // 1. Notify Teacher(s)
        if ($slot && $slot->teacher && $slot->teacher->user) {
            $recipients->push($slot->teacher->user);
        }

        if ($event instanceof TeacherChanged) {
            if ($event->oldTeacher && $event->oldTeacher->user) {
                $recipients->push($event->oldTeacher->user);
            }
            if ($event->newTeacher && $event->newTeacher->user) {
                $recipients->push($event->newTeacher->user);
            }
        }

        // 2. Notify Class Students (if applicable)
        if ($slot && $slot->schoolClass) {
            $enrollments = $slot->schoolClass->enrollments()
                ->where('school_id', $timetable->school_id)
                ->with('student.user')
                ->get();

            foreach ($enrollments as $enrollment) {
                if ($enrollment->student?->user) {
                    $recipients->push($enrollment->student->user);
                }
            }
        }

        // 3. Deduplicate recipients & filter tenant
        $uniqueRecipients = $recipients
            ->filter(fn ($u) => $u instanceof User && $u->school_id === $timetable->school_id)
            ->unique('id');

        $notification = ($event instanceof LessonCancelled)
            ? new LessonCancelledNotification($change)
            : new TimetableOperationalChangeNotification($change);

        foreach ($uniqueRecipients as $user) {
            try {
                $user->notify($notification);
            } catch (\Throwable $e) {
                // Log and continue gracefully
                \Log::warning("Could not send timetable change notification to user #{$user->id}: " . $e->getMessage());
            }
        }
    }
}
