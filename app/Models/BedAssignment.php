<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BedAssignment extends Model
{
    protected $fillable = [
        'bed_id',
        'student_id',
        'academic_year',
        'term',
        'assigned_date',
        'released_date',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

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
