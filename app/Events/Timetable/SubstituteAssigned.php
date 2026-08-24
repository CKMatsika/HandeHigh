<?php

namespace App\Events\Timetable;

use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use App\Models\TimetableOperationalChange;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubstituteAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Timetable $timetable,
        public TimetableSlot $slot,
        public TimetableSubstitution $substitution,
        public TimetableOperationalChange $change,
        public ?User $actor = null
    ) {}
}
