<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'school_id',
        'code',
        'name',
        'description',
        'is_core',
    ];

    protected $casts = [
        'is_core' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function curricula()
    {
        return $this->hasMany(Curriculum::class, 'subject_id');
    }

    public function exams()
    {
        return $this->hasManyThrough(Exam::class, Curriculum::class, 'subject_id', 'curriculum_id');
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'subject_id');
    }
}
