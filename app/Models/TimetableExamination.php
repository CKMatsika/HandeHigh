<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableExamination extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'timetable_id',
        'exam_id',
        'title',
        'exam_type',
        'subject_id',
        'school_class_id',
        'room_id',
        'supervisor_teacher_id',
        'exam_date',
        'day_of_week',
        'start_time',
        'end_time',
        'is_locked',
        'notes',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'is_locked' => 'boolean',
        'exam_type' => 'string',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function supervisorTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'supervisor_teacher_id');
    }

    public function isNational(): bool
    {
        return $this->exam_type === 'national';
    }

    public function isHardLocked(): bool
    {
        return $this->is_locked || $this->isNational();
    }

    public function scopeNational($query)
    {
        return $query->where('exam_type', 'national');
    }

    public function scopeByDay($query, string $day)
    {
        return $query->where('day_of_week', $day);
    }

    public function getFormattedTime(): string
    {
        $start = is_string($this->start_time) ? substr($this->start_time, 0, 5) : Carbon::parse($this->start_time)->format('H:i');
        $end = is_string($this->end_time) ? substr($this->end_time, 0, 5) : Carbon::parse($this->end_time)->format('H:i');

        return "{$start} - {$end}";
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
