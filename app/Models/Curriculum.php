<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    protected $fillable = [
        'school_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'academic_year',
        'term',
        'weekly_periods',
        'objectives',
        'materials',
    ];

    protected $casts = [
        'academic_year' => 'string',
        'term' => 'string',
        'weekly_periods' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'curriculum_id');
    }
}
