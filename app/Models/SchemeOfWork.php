<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchemeOfWork extends Model
{
    use HasFactory;

    protected $table = 'schemes_of_work';

    protected $fillable = [
        'school_id',
        'teacher_id',
        'subject_id',
        'school_class_id',
        'title',
        'general_topic',
        'description',
        'aims',
        'syllabus_reference',
        'cross_cutting_themes',
        'academic_year',
        'term',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'cross_cutting_themes' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items()
    {
        return $this->hasMany(SchemeOfWorkItem::class, 'scheme_of_work_id')->orderBy('week_number')->orderBy('sort_order');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePreview($query)
    {
        return $query->where('status', 'preview');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForAcademicYear($query, $year, $term)
    {
        return $query->where('academic_year', $year)->where('term', $term);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPreview(): bool
    {
        return $this->status === 'preview';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'slate',
            'preview' => 'blue',
            'submitted' => 'amber',
            'approved' => 'emerald',
            'rejected' => 'red',
            default => 'slate',
        };
    }
}
