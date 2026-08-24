<?php

namespace App\Events\Timetable;

use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\Teacher;
use App\Models\TimetableOperationalChange;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeacherChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Timetable $timetable,
        public TimetableSlot $slot,
        public ?Teacher $oldTeacher,
        public Teacher $newTeacher,
        public TimetableOperationalChange $change,
        public ?User $actor = null
    ) {}
}
