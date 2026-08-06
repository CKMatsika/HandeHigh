<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerGuidanceAssessment extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'assessment_date', 'subject_performance',
        'strengths', 'areas_for_improvement', 'suggested_careers',
        'improvement_tips', 'overall_feedback', 'student_response',
        'status', 'generated_by',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'subject_performance' => 'array',
        'strengths' => 'array',
        'areas_for_improvement' => 'array',
        'suggested_careers' => 'array',
        'improvement_tips' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
