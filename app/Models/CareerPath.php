<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerPath extends Model
{
    protected $fillable = [
        'school_id', 'name', 'slug', 'description', 'typical_subjects',
        'subject_requirements', 'education_level', 'skills', 'outlook', 'is_active',
    ];

    protected $casts = [
        'subject_requirements' => 'array',
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function studentInterests()
    {
        return $this->hasMany(StudentCareerInterest::class, 'career_path_id');
    }
}
