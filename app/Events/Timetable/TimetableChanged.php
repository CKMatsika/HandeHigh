<?php

namespace App\Events\Timetable;

use App\Models\Timetable;
use App\Models\TimetableOperationalChange;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimetableChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Timetable $timetable,
        public ?TimetableOperationalChange $change = null,
        public ?User $actor = null
    ) {}
}
