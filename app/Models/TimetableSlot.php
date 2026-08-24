<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'timetable_id',
        'school_class_id',
        'subject_id',
        'teacher_id',
        'room_id',
        'school_period_id',
        'day_of_week',
        'start_time',
        'end_time',
        'status',
        'is_locked',
        'slot_type',
        'activity_name',
        'conflicts',
        'ai_score',
    ];

    protected $casts = [
        'conflicts' => 'array',
        'ai_score' => 'decimal:2',
        'start_time' => 'string',
        'end_time' => 'string',
        'status' => 'string',
        'is_locked' => 'boolean',
        'slot_type' => 'string',
    ];

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function schoolPeriod(): BelongsTo
    {
        return $this->belongsTo(SchoolPeriod::class);
    }

    public function operationalChanges(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TimetableOperationalChange::class, 'timetable_slot_id');
    }

    public function substitutions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TimetableSubstitution::class, 'timetable_slot_id');
    }

    public function activeSubstitutionForDate(string|\Carbon\Carbon $date): ?TimetableSubstitution
    {
        $dateStr = $date instanceof \Carbon\Carbon ? $date->toDateString() : \Carbon\Carbon::parse($date)->toDateString();
        return $this->substitutions()
            ->where('date', $dateStr)
            ->where('status', 'approved')
            ->first();
    }

    public function hasConflicts(): bool
    {
        return ! empty($this->conflicts) || $this->status === 'conflict';
    }

    public function getDurationMinutes(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end);
    }

    public function getFormattedTime(): string
    {
        $start = is_string($this->start_time) ? substr($this->start_time, 0, 5) : Carbon::parse($this->start_time)->format('H:i');
        $end = is_string($this->end_time) ? substr($this->end_time, 0, 5) : Carbon::parse($this->end_time)->format('H:i');

        return "{$start} - {$end}";
    }

    public function isLesson(): bool
    {
        return ($this->slot_type ?? 'lesson') === 'lesson';
    }

    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }
}
