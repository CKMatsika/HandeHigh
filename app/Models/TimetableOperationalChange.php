<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableOperationalChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'timetable_id',
        'timetable_slot_id',
        'change_type',
        'reason',
        'notes',
        'before_state',
        'after_state',
        'changed_by',
        'revision',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'revision' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(TimetableSlot::class, 'timetable_slot_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getChangeTypeLabelAttribute(): string
    {
        return match ($this->change_type) {
            'teacher_change' => 'Teacher Changed',
            'room_change' => 'Room Changed',
            'lesson_moved' => 'Lesson Moved',
            'lesson_cancelled' => 'Lesson Cancelled',
            'lesson_restored' => 'Lesson Restored',
            'substitute_assigned' => 'Substitute Assigned',
            default => ucfirst(str_replace('_', ' ', $this->change_type)),
        };
    }
}
