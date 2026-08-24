<?php

namespace App\Listeners\Timetable;

use App\Events\Timetable\SubstituteAssigned;
use App\Models\User;
use App\Notifications\Timetable\SubstituteAssignedNotification;

class SendSubstituteNotifications
{
    public function handle(SubstituteAssigned $event): void
    {
        $substitution = $event->substitution;
        $slot = $event->slot;
        $timetable = $event->timetable;

        if (! $substitution || ! $timetable) {
            return;
        }

        // 1. Notify Substitute Teacher
        if ($substitution->substituteTeacher && $substitution->substituteTeacher->user) {
            $subUser = $substitution->substituteTeacher->user;
            if ($subUser->school_id === $timetable->school_id) {
                try {
                    $subUser->notify(new SubstituteAssignedNotification($substitution, 'substitute'));
                } catch (\Throwable $e) {
                    \Log::warning("Failed to notify substitute teacher: " . $e->getMessage());
                }
            }
        }

        // 2. Notify Original Absent Teacher
        if ($substitution->originalTeacher && $substitution->originalTeacher->user) {
            $origUser = $substitution->originalTeacher->user;
            if ($origUser->school_id === $timetable->school_id) {
                try {
                    $origUser->notify(new SubstituteAssignedNotification($substitution, 'original_teacher'));
                } catch (\Throwable $e) {
                    \Log::warning("Failed to notify original teacher: " . $e->getMessage());
                }
            }
        }

        // 3. Notify Class Students
        if ($slot && $slot->schoolClass) {
            $enrollments = $slot->schoolClass->enrollments()
                ->where('school_id', $timetable->school_id)
                ->with('student.user')
                ->get();

            foreach ($enrollments as $enrollment) {
                $studentUser = $enrollment->student?->user;
                if ($studentUser && $studentUser->school_id === $timetable->school_id) {
                    try {
                        $studentUser->notify(new SubstituteAssignedNotification($substitution, 'student'));
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to notify student: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
