<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BedAssignment extends Model
{
    protected $fillable = [
        'school_id',
        'bed_id',
        'student_id',
        'academic_year',
        'term',
        'assigned_date',
        'released_date',
        'is_current',
        'notes',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'assigned_date' => 'date',
        'released_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
