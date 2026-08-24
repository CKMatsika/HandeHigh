<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'school_id',
        'name',
        'grade',
        'academic_year',
        'term',
        'teacher_id',
    ];

    protected $casts = [
        'academic_year' => 'string',
        'term' => 'string',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function curricula()
    {
        return $this->hasMany(Curriculum::class, 'class_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function students()
    {
        return $this->hasManyThrough(Student::class, Enrollment::class, 'class_id', 'id', 'id', 'student_id');
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'class_id');
    }
}
