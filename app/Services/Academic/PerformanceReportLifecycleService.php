<?php

namespace App\Services\Academic;

use App\Models\Curriculum;
use App\Models\PerformanceReport;
use App\Models\PerformanceReportAudit;
use App\Models\PerformanceReportSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;

class PerformanceReportLifecycleService
{
    public function __construct(
        protected GradeCalculationService $gradeService,
        protected ExamReleasePolicyService $releasePolicyService
    ) {}

    /**
     * Get or initialize a PerformanceReport for a student in a given academic period.
     */
    public function getOrInitializeReport(Student $student, string $academicYear, string $term, ?SchoolClass $class = null): PerformanceReport
    {
        $school = $student->school;
        $scheme = $this->gradeService->resolveDefaultScheme($school);

        // Find or create report
        $report = PerformanceReport::firstOrCreate(
            [
                'school_id' => $school->id,
                'student_id' => $student->id,
                'academic_year' => $academicYear,
                'term' => $term,
                'version' => '1.0',
            ],
            [
                'school_class_id' => $class?->id ?? SchoolClass::where('school_id', $school->id)->where('name', $student->class_name)->first()?->id,
                'grade_scheme_id' => $scheme?->id,
                'status' => PerformanceReport::STATUS_AVAILABLE_FOR_TEACHERS,
                'version' => '1.0',
                'is_locked' => false,
            ]
        );

        // Initialize subject line items from Curricula or school subjects
        $this->syncReportSubjects($report, $class);

        return $report->fresh(['subjects.subject', 'subjects.assignedTeacher', 'student', 'schoolClass', 'gradeScheme.bands']);
    }

    /**
     * Batch initialize performance reports for all enrolled students in a term/year or specific class.
     */
    public function initializeTermReports(School $school, string $academicYear, string $term, ?int $classId = null): int
    {
        $classes = SchoolClass::where('school_id', $school->id)
            ->when($classId, fn($q) => $q->where('id', $classId))
            ->get();

        $count = 0;
        foreach ($classes as $class) {
            $students = Student::where('school_id', $school->id)
                ->where(function ($q) use ($class) {
                    $q->where('class_name', $class->name)
                      ->orWhere('grade', $class->grade);
                })
                ->get();

            foreach ($students as $student) {
                $this->getOrInitializeReport($student, $academicYear, $term, $class);
                $count++;
            }
        }

        // If no class records match, initialize all active students directly
        if ($count === 0 && !$classId) {
            $allStudents = Student::where('school_id', $school->id)->get();
            foreach ($allStudents as $student) {
                $this->getOrInitializeReport($student, $academicYear, $term, null);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Sync subjects for a student report from curriculum assignments or active school subjects.
     */
    public function syncReportSubjects(PerformanceReport $report, ?SchoolClass $class = null): void
    {
        $school = $report->school;
        $classId = $report->school_class_id ?? $class?->id;

        // Fetch curricula matching this class, academic year, and term
        $curricula = Curriculum::where('school_id', $school->id)
            ->when($classId, fn($q) => $q->where('class_id', $classId))
            ->where('academic_year', $report->academic_year)
            ->where(function ($q) use ($report) {
                $q->whereNull('term')->orWhere('term', $report->term);
            })
            ->with(['subject', 'teacher'])
            ->get();

        if ($curricula->isNotEmpty()) {
            foreach ($curricula as $curr) {
                PerformanceReportSubject::firstOrCreate(
                    [
                        'performance_report_id' => $report->id,
                        'subject_id' => $curr->subject_id,
                    ],
                    [
                        'school_id' => $school->id,
                        'student_id' => $report->student_id,
                        'curriculum_id' => $curr->id,
                        'assigned_teacher_id' => $curr->teacher_id,
                        'status' => 'not_started',
                    ]
                );
            }
        } else {
            // Fallback: create items for all core subjects or student subjects
            $subjects = Subject::where('school_id', $school->id)->get();
            foreach ($subjects as $subj) {
                PerformanceReportSubject::firstOrCreate(
                    [
                        'performance_report_id' => $report->id,
                        'subject_id' => $subj->id,
                    ],
                    [
                        'school_id' => $school->id,
                        'student_id' => $report->student_id,
                        'status' => 'not_started',
                    ]
                );
            }
        }
    }

    /**
     * Save subject mark and teacher formatted comment.
     */
    public function saveSubjectEvaluation(
        PerformanceReportSubject $subjectItem,
        array $data,
        User $user
    ): PerformanceReportSubject {
        $report = $subjectItem->performanceReport;

        if ($report->isFinalized() || $report->is_locked) {
            throw new \DomainException('Cannot modify a finalized or locked performance report.');
        }

        $oldValues = $subjectItem->only(['mark_obtained', 'max_mark', 'percentage', 'grade', 'is_pass', 'comment', 'status']);

        $markObtained = isset($data['mark_obtained']) && $data['mark_obtained'] !== '' ? (float)$data['mark_obtained'] : null;
        $maxMark = isset($data['max_mark']) && (float)$data['max_mark'] > 0 ? (float)$data['max_mark'] : 100.0;
        $resultStatus = $data['result_status'] ?? 'present';
        $comment = $this->sanitizeComment($data['comment'] ?? '');
        $commentFont = $data['comment_font'] ?? 'Arial';
        $commentFontSize = isset($data['comment_font_size']) ? (int)$data['comment_font_size'] : 11;

        // Authoritative server-side grade calculation
        $percentage = $this->gradeService->calculatePercentage($markObtained, $maxMark);
        $gradeEvaluation = $this->gradeService->evaluateGrade($percentage, $resultStatus, $report->gradeScheme, $report->school);

        $isDraft = ($data['action'] ?? 'save_draft') === 'save_draft';
        $newStatus = $isDraft ? 'draft' : 'complete';

        $subjectItem->update([
            'mark_obtained' => $markObtained,
            'max_mark' => $maxMark,
            'percentage' => $percentage,
            'grade' => $gradeEvaluation['grade'],
            'is_pass' => $gradeEvaluation['is_pass'],
            'result_status' => $resultStatus,
            'comment' => $comment,
            'comment_font' => $commentFont,
            'comment_font_size' => $commentFontSize,
            'comment_formatting' => [
                'font' => $commentFont,
                'font_size' => $commentFontSize,
                'bold' => !empty($data['bold']),
                'italic' => !empty($data['italic']),
                'alignment' => $data['alignment'] ?? 'left',
            ],
            'status' => $newStatus,
            'entered_by' => $subjectItem->entered_by ?? $user->id,
            'last_updated_by' => $user->id,
            'completed_at' => $newStatus === 'complete' ? now() : null,
        ]);

        // Recalculate parent report summary aggregates
        $this->recalculateReportSummary($report);

        // Audit Trail Logging
        $action = $newStatus === 'complete' ? 'SUBJECT_COMPLETED' : ($subjectItem->wasRecentlyCreated ? 'MARK_ENTERED' : 'MARK_UPDATED');
        $this->logAudit(
            $report,
            $action,
            "Subject evaluation updated for {$subjectItem->subject->name}: {$gradeEvaluation['grade']} ({$percentage}%)",
            $user,
            $oldValues,
            $subjectItem->fresh()->toArray(),
            $subjectItem->id
        );

        return $subjectItem->fresh();
    }

    /**
     * Recalculate overall term averages and statuses on the parent report.
     */
    public function recalculateReportSummary(PerformanceReport $report): void
    {
        $subjects = $report->subjects()->get();
        $aggregates = $this->gradeService->calculateTermAggregates($subjects, $report->gradeScheme);

        // Determine next lifecycle status
        $allComplete = $subjects->isNotEmpty() && $subjects->where('status', '!=', 'complete')->count() === 0;
        $anyInProgress = $subjects->where('status', '!=', 'not_started')->count() > 0;

        $newStatus = $report->status;
        if (!$report->isFinalized()) {
            if ($allComplete) {
                $newStatus = PerformanceReport::STATUS_AWAITING_LEADERSHIP;
            } elseif ($anyInProgress) {
                $newStatus = PerformanceReport::STATUS_TEACHER_ENTRY_IN_PROGRESS;
            } else {
                $newStatus = PerformanceReport::STATUS_AVAILABLE_FOR_TEACHERS;
            }
        }

        $report->update([
            'term_average' => $aggregates['term_average'],
            'overall_grade' => $aggregates['overall_grade'],
            'total_subjects' => $aggregates['total_subjects'],
            'subjects_passed' => $aggregates['subjects_passed'],
            'subjects_failed' => $aggregates['subjects_failed'],
            'overall_status' => $aggregates['overall_status'],
            'status' => $newStatus,
        ]);
    }

    /**
     * Save Leadership Comments (Headmaster or Deputy Headmaster)
     */
    public function saveLeadershipComment(
        PerformanceReport $report,
        string $type, // 'headmaster' or 'deputy'
        string $comment,
        array $formatting,
        User $user
    ): PerformanceReport {
        if ($report->isFinalized() || $report->is_locked) {
            throw new \DomainException('Cannot modify comments on a finalized report.');
        }

        $sanitized = $this->sanitizeComment($comment);
        $oldComment = $type === 'headmaster' ? $report->headmaster_comment : $report->deputy_comment;

        if ($type === 'headmaster') {
            $report->update([
                'headmaster_comment' => $sanitized,
                'headmaster_formatting' => $formatting,
                'headmaster_user_id' => $user->id,
            ]);
            $action = 'HEADMASTER_COMMENT_ADDED';
        } else {
            $report->update([
                'deputy_comment' => $sanitized,
                'deputy_formatting' => $formatting,
                'deputy_user_id' => $user->id,
            ]);
            $action = 'DEPUTY_COMMENT_ADDED';
        }

        $this->logAudit(
            $report,
            $action,
            ucfirst($type) . " comment saved by {$user->name}",
            $user,
            ['comment' => $oldComment],
            ['comment' => $sanitized, 'formatting' => $formatting]
        );

        return $report->fresh();
    }

    /**
     * Apply Digital Signature for Headmaster or Deputy Headmaster
     */
    public function applyDigitalSignature(
        PerformanceReport $report,
        string $roleType, // 'headmaster' or 'deputy'
        User $signer,
        ?string $signaturePath = null
    ): PerformanceReport {
        if ($report->isFinalized() || $report->is_locked) {
            throw new \DomainException('Cannot sign a finalized report.');
        }

        $timestamp = now();
        $meta = [
            'signer_id' => $signer->id,
            'signer_name' => $signer->name,
            'role' => $roleType,
            'signed_at' => $timestamp->toIso8601String(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'hash' => sha1("{$report->id}:{$report->student_id}:{$report->term_average}:{$timestamp->timestamp}"),
        ];

        if ($roleType === 'headmaster') {
            $report->update([
                'headmaster_user_id' => $signer->id,
                'headmaster_signed_at' => $timestamp,
                'headmaster_signature_path' => $signaturePath,
                'headmaster_signature_meta' => $meta,
            ]);
        } else {
            $report->update([
                'deputy_user_id' => $signer->id,
                'deputy_signed_at' => $timestamp,
                'deputy_signature_path' => $signaturePath,
                'deputy_signature_meta' => $meta,
            ]);
        }

        $this->logAudit(
            $report,
            'SIGNATURE_APPLIED',
            "Digital signature applied for {$roleType} by {$signer->name}",
            $signer,
            null,
            $meta
        );

        return $report->fresh();
    }

    /**
     * Apply Official Digital School Stamp
     */
    public function applyOfficialStamp(PerformanceReport $report, User $user, ?string $stampPath = null): PerformanceReport
    {
        if ($report->isFinalized() || $report->is_locked) {
            throw new \DomainException('Cannot stamp a finalized report.');
        }

        $timestamp = now();
        $meta = [
            'applied_by_id' => $user->id,
            'applied_by_name' => $user->name,
            'applied_at' => $timestamp->toIso8601String(),
            'school_name' => $report->school->name,
            'ip' => request()->ip(),
        ];

        $report->update([
            'stamp_applied_at' => $timestamp,
            'stamp_applied_by' => $user->id,
            'stamp_path' => $stampPath,
            'stamp_meta' => $meta,
        ]);

        $this->logAudit(
            $report,
            'STAMP_APPLIED',
            "Official school digital stamp applied by {$user->name}",
            $user,
            null,
            $meta
        );

        return $report->fresh();
    }

    /**
     * Finalize and strictly lock the performance report.
     */
    public function finalizeReport(PerformanceReport $report, User $user): PerformanceReport
    {
        if ($report->isFinalized()) {
            throw new \DomainException('Report is already finalized.');
        }

        // Validate all subjects are complete
        if (!$report->areAllSubjectsComplete()) {
            throw new \DomainException('All subject marks and evaluations must be completed before finalization.');
        }

        $report->update([
            'status' => PerformanceReport::STATUS_FINALIZED,
            'is_locked' => true,
            'finalized_at' => now(),
            'finalized_by' => $user->id,
            'published_at' => now(),
        ]);

        $this->logAudit(
            $report,
            'REPORT_FINALIZED',
            "Performance report finalized and locked for student {$report->student->full_name}",
            $user,
            ['status' => $report->getOriginal('status'), 'is_locked' => false],
            ['status' => PerformanceReport::STATUS_FINALIZED, 'is_locked' => true]
        );

        return $report->fresh();
    }

    /**
     * Reopen an already finalized report with audit trail and version increment.
     */
    public function reopenReport(PerformanceReport $report, string $reason, User $user): PerformanceReport
    {
        $oldVersion = $report->version;
        $newVersion = number_format((float)$oldVersion + 1.0, 1);

        $report->update([
            'status' => PerformanceReport::STATUS_AWAITING_LEADERSHIP,
            'is_locked' => false,
            'version' => $newVersion,
        ]);

        // Unlock subjects
        $report->subjects()->update(['status' => 'draft']);

        $this->logAudit(
            $report,
            'REPORT_REOPENED',
            "Report version {$oldVersion} reopened to v{$newVersion}. Reason: {$reason}",
            $user,
            ['version' => $oldVersion, 'is_locked' => true],
            ['version' => $newVersion, 'is_locked' => false, 'reason' => $reason]
        );

        return $report->fresh();
    }

    /**
     * Record an audit log entry.
     */
    protected function logAudit(
        PerformanceReport $report,
        string $action,
        string $description,
        User $user,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $subjectId = null
    ): PerformanceReportAudit {
        return PerformanceReportAudit::create([
            'school_id' => $report->school_id,
            'performance_report_id' => $report->id,
            'performance_report_subject_id' => $subjectId,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }

    /**
     * Sanitize rich text comments to prevent XSS while preserving safe formatting tags.
     */
    public function sanitizeComment(string $html): string
    {
        // If HTML Purifier is available, use it; otherwise apply safe tag stripping
        if (class_exists(\Mews\Purifier\Facades\Purifier::class)) {
            try {
                return Purifier::clean($html, [
                    'HTML.Allowed' => 'p,b,strong,i,em,u,span[style],ul,ol,li,br,div[style]',
                    'CSS.AllowedProperties' => 'font-family,font-size,text-align,font-weight,font-style,text-decoration',
                ]);
            } catch (\Throwable $e) {
                // fallback
            }
        }

        return strip_tags($html, '<p><b><strong><i><em><u><span><ul><ol><li><br>');
    }
}
