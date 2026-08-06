<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCareerInterest extends Model
{
    protected $table = 'student_career_interests';

    protected $fillable = [
        'school_id', 'student_id', 'desired_career', 'career_path_id',
        'reason', 'preferred_subjects', 'hobbies',
    ];

    protected $casts = [
        'preferred_subjects' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function careerPath()
    {
        return $this->belongsTo(CareerPath::class, 'career_path_id');
    }
}
