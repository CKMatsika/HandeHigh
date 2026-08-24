<?php

namespace App\Events\Timetable;

use App\Models\TeacherAbsence;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TeacherAbsenceRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TeacherAbsence $absence,
        public Collection $affectedSlots,
        public ?User $actor = null
    ) {}
}
