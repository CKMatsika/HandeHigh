<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'class_id',
        'academic_year',
        'term',
        'grade',
        'class_name',
        'is_boarding',
        'has_transport',
        'enrollment_date',
        'status',
        'student_type',
        'extracurricular_activities',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'is_boarding' => 'boolean',
        'has_transport' => 'boolean',
        'student_type' => 'string',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function getStudentTypeLabelAttribute()
    {
        return match($this->student_type) {
            'boarding' => 'Boarding Student',
            'day' => 'Day Student',
            default => 'Day Student',
        };
    }

    public function getStudentTypeBadgeColorAttribute()
    {
        return match($this->student_type) {
            'boarding' => 'primary',
            'day' => 'info',
            default => 'secondary',
        };
    }

    public function getExtracurricularActivitiesArrayAttribute()
    {
        return $this->extracurricular_activities ? 
            json_decode($this->extracurricular_activities, true) : [];
    }

    public function setExtracurricularActivitiesArrayAttribute($value)
    {
        $this->extracurricular_activities = is_array($value) ? 
            json_encode($value) : $value;
    }

    public function isBoarder(): bool
    {
        return ($this->student_type === 'boarding') || (bool) $this->is_boarding;
    }

    public function isDayScholar(): bool
    {
        return ! $this->isBoarder();
    }
}
