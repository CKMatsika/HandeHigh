<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReportSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_report_id',
        'school_id',
        'student_id',
        'subject_id',
        'curriculum_id',
        'assigned_teacher_id',
        'mark_obtained',
        'max_mark',
        'percentage',
        'grade',
        'is_pass',
        'result_status',
        'comment',
        'comment_font',
        'comment_font_size',
        'comment_formatting',
        'status',
        'entered_by',
        'last_updated_by',
        'completed_at',
    ];

    protected $casts = [
        'mark_obtained' => 'decimal:2',
        'max_mark' => 'decimal:2',
        'percentage' => 'decimal:2',
        'is_pass' => 'boolean',
        'comment_font_size' => 'integer',
        'comment_formatting' => 'array',
        'completed_at' => 'datetime',
    ];

    public function performanceReport(): BelongsTo
    {
        return $this->belongsTo(PerformanceReport::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_teacher_id');
    }

    public function enteredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function lastUpdatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function getEffectiveTeacherNameAttribute(): string
    {
        if ($this->assignedTeacher) {
            return $this->assignedTeacher->name;
        }
        if ($this->curriculum && $this->curriculum->teacher) {
            return $this->curriculum->teacher->name;
        }
        return 'Subject Teacher';
    }
}
