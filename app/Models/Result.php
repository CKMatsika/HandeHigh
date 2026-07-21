<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'class_id',
        'subject_id',
        'academic_year',
        'term',
        'total_score',
        'max_total_score',
        'average',
        'grade',
        'remarks',
    ];

    protected $casts = [
        'total_score' => 'decimal:2',
        'max_total_score' => 'decimal:2',
        'average' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
