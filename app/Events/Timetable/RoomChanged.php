<?php

namespace App\Events\Timetable;

use App\Models\Room;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\TimetableOperationalChange;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Timetable $timetable,
        public TimetableSlot $slot,
        public ?Room $oldRoom,
        public Room $newRoom,
        public TimetableOperationalChange $change,
        public ?User $actor = null
    ) {}
}
