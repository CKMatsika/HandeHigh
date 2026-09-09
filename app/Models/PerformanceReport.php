<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReport extends Model
{
    use HasFactory;

    // Report Lifecycle Constants
    public const STATUS_NOT_AVAILABLE = 'NOT_AVAILABLE';
    public const STATUS_AVAILABLE_FOR_TEACHERS = 'AVAILABLE_FOR_TEACHERS';
    public const STATUS_TEACHER_ENTRY_IN_PROGRESS = 'TEACHER_ENTRY_IN_PROGRESS';
    public const STATUS_TEACHER_ENTRY_COMPLETE = 'TEACHER_ENTRY_COMPLETE';
    public const STATUS_AWAITING_LEADERSHIP = 'AWAITING_LEADERSHIP';
    public const STATUS_LEADERSHIP_IN_PROGRESS = 'LEADERSHIP_IN_PROGRESS';
    public const STATUS_READY_FOR_FINALISATION = 'READY_FOR_FINALISATION';
    public const STATUS_FINALIZED = 'FINALIZED';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_LOCKED = 'LOCKED';

    protected $fillable = [
        'school_id',
        'student_id',
        'school_class_id',
        'academic_year',
        'term',
        'grade_scheme_id',
        'status',
        'version',
        'is_locked',
        'term_average',
        'overall_grade',
        'total_subjects',
        'subjects_passed',
        'subjects_failed',
        'overall_status',
        'headmaster_comment',
        'headmaster_formatting',
        'headmaster_user_id',
        'headmaster_signed_at',
        'headmaster_signature_path',
        'headmaster_signature_meta',
        'deputy_comment',
        'deputy_formatting',
        'deputy_user_id',
        'deputy_signed_at',
        'deputy_signature_path',
        'deputy_signature_meta',
        'stamp_applied_at',
        'stamp_applied_by',
        'stamp_path',
        'stamp_meta',
        'finalized_at',
        'finalized_by',
        'published_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'term_average' => 'decimal:2',
        'total_subjects' => 'integer',
        'subjects_passed' => 'integer',
        'subjects_failed' => 'integer',
        'headmaster_formatting' => 'array',
        'headmaster_signature_meta' => 'array',
        'deputy_formatting' => 'array',
        'deputy_signature_meta' => 'array',
        'stamp_meta' => 'array',
        'headmaster_signed_at' => 'datetime',
        'deputy_signed_at' => 'datetime',
        'stamp_applied_at' => 'datetime',
        'finalized_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function gradeScheme(): BelongsTo
    {
        return $this->belongsTo(GradeScheme::class, 'grade_scheme_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(PerformanceReportSubject::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PerformanceReportAudit::class)->latest();
    }

    public function headmasterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'headmaster_user_id');
    }

    public function deputyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deputy_user_id');
    }

    public function stampUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stamp_applied_by');
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    // Status Helpers
    public function isFinalized(): bool
    {
        return in_array($this->status, [self::STATUS_FINALIZED, self::STATUS_PUBLISHED, self::STATUS_LOCKED]) || $this->is_locked;
    }

    public function canTeacherEdit(): bool
    {
        return in_array($this->status, [
            self::STATUS_AVAILABLE_FOR_TEACHERS,
            self::STATUS_TEACHER_ENTRY_IN_PROGRESS,
            self::STATUS_TEACHER_ENTRY_COMPLETE,
        ]) && !$this->is_locked;
    }

    public function canLeadershipEdit(): bool
    {
        return in_array($this->status, [
            self::STATUS_TEACHER_ENTRY_COMPLETE,
            self::STATUS_AWAITING_LEADERSHIP,
            self::STATUS_LEADERSHIP_IN_PROGRESS,
            self::STATUS_READY_FOR_FINALISATION,
        ]) && !$this->is_locked;
    }

    public function areAllSubjectsComplete(): bool
    {
        if ($this->subjects()->count() === 0) {
            return false;
        }

        return $this->subjects()->where('status', '!=', 'complete')->count() === 0;
    }

    public function isHeadmasterSigned(): bool
    {
        return !is_null($this->headmaster_signed_at);
    }

    public function isDeputySigned(): bool
    {
        return !is_null($this->deputy_signed_at);
    }

    public function isStampApplied(): bool
    {
        return !is_null($this->stamp_applied_at);
    }
}
