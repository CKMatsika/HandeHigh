<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'qualification',
        'specialization',
        'experience_years',
        'hire_date',
        'salary',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'experience_years' => 'integer',
        'status' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects');
    }

    public function qualifications()
    {
        return $this->morphMany(Qualification::class, 'qualifiable');
    }

    public function positionAssignments()
    {
        return $this->morphMany(StaffPositionAssignment::class, 'assignable');
    }

    public function activePositionAssignments()
    {
        return $this->positionAssignments()->where('is_active', true);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id', 'user_id');
    }

    public function subjectCurricula()
    {
        return Curriculum::where('teacher_id', $this->user_id);
    }

    public function schemesOfWork()
    {
        return $this->hasMany(SchemeOfWork::class, 'teacher_id', 'user_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'teacher_id', 'user_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->whereHas('subjects', function ($q) use ($subjectId) {
            $q->where('subject_id', $subjectId);
        });
    }

    public function getActivePositionsAttribute()
    {
        return $this->activePositionAssignments->map(function ($assignment) {
            return $assignment->position->name;
        })->unique()->values();
    }

    public function getClassTeacherClassAttribute()
    {
        return $this->classes()->first();
    }
}
