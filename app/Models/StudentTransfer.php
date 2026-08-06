<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransfer extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'transfer_type',
        'destination_school',
        'destination_address',
        'destination_contact',
        'transfer_date',
        'reason',
        'academic_year',
        'term',
        'grade_at_transfer',
        'class_at_transfer',
        'conduct_remarks',
        'academic_remarks',
        'status',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
