<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableFixedActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'timetable_id',
        'name',
        'activity_type',
        'day_of_week',
        'start_time',
        'end_time',
        'school_period_id',
        'school_class_id',
        'teacher_id',
        'room_id',
        'is_locked',
        'description',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'activity_type' => 'string',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function schoolPeriod(): BelongsTo
    {
        return $this->belongsTo(SchoolPeriod::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeByDay($query, string $day)
    {
        return $query->where('day_of_week', $day);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function getFormattedTime(): string
    {
        $start = is_string($this->start_time) ? substr($this->start_time, 0, 5) : Carbon::parse($this->start_time)->format('H:i');
        $end = is_string($this->end_time) ? substr($this->end_time, 0, 5) : Carbon::parse($this->end_time)->format('H:i');

        return "{$start} - {$end}";
    }

    public function appliesToClass(?int $classId): bool
    {
        if ($this->school_class_id === null) {
            return true; // School-wide fixed activity
        }

        return $this->school_class_id === $classId;
    }

    public function appliesToTeacher(?int $teacherId): bool
    {
        if ($this->teacher_id === null) {
            return true; // All teachers involved/locked
        }

        return $this->teacher_id === $teacherId;
    }

    public function overlapsWith(string $day, string $startTime, string $endTime): bool
    {
        if (strcasecmp($this->day_of_week, $day) !== 0) {
            return false;
        }

        $myStart = substr((string) $this->start_time, 0, 5);
        $myEnd = substr((string) $this->end_time, 0, 5);
        $targetStart = substr($startTime, 0, 5);
        $targetEnd = substr($endTime, 0, 5);

        return $myStart < $targetEnd && $myEnd > $targetStart;
    }
}
