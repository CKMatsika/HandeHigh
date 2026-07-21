<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'school_id',
        'curriculum_id',
        'title',
        'type',
        'exam_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'max_score',
        'instructions',
        'is_published',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'max_score' => 'decimal:2',
        'is_published' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'exam_id');
    }
}
