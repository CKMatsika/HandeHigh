<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableSubstitution extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'timetable_id',
        'timetable_slot_id',
        'teacher_absence_id',
        'original_teacher_id',
        'substitute_teacher_id',
        'date',
        'status',
        'score',
        'score_breakdown',
        'reason',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'score' => 'decimal:2',
        'score_breakdown' => 'array',
        'approved_at' => 'datetime',
        'status' => 'string',
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

    public function teacherAbsence(): BelongsTo
    {
        return $this->belongsTo(TeacherAbsence::class);
    }

    public function originalTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'original_teacher_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
