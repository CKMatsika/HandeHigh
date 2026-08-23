<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'timetable_id',
        'school_class_id',
        'subject_id',
        'teacher_id',
        'room_id',
        'weekly_periods',
        'preferred_days',
        'preferred_periods',
        'max_daily_lessons',
        'is_double_period_allowed',
        'priority',
    ];

    protected $casts = [
        'weekly_periods' => 'integer',
        'preferred_days' => 'array',
        'preferred_periods' => 'array',
        'max_daily_lessons' => 'integer',
        'is_double_period_allowed' => 'boolean',
        'priority' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
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
}
