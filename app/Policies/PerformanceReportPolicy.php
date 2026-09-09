<?php

namespace App\Policies;

use App\Models\Curriculum;
use App\Models\PerformanceReport;
use App\Models\PerformanceReportSubject;
use App\Models\User;
use App\Services\Academic\ExamReleasePolicyService;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerformanceReportPolicy
{
    use HandlesAuthorization;

    public function __construct(
        protected ExamReleasePolicyService $releasePolicyService
    ) {}

    /**
     * Determine whether the user can view the performance reports dashboard.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster', 'teacher']);
    }

    /**
     * Determine whether the user can view a complete consolidated student performance report.
     */
    public function view(User $user, PerformanceReport $report): bool
    {
        if ($user->school_id !== $report->school_id) {
            return false;
        }

        if ($user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster'])) {
            return true;
        }

        // Subject teacher can view report if they teach at least one subject to this student in this class/period
        if ($user->hasRole('teacher')) {
            return $this->isTeacherAssignedToReport($user, $report);
        }

        return false;
    }

    /**
     * Determine whether the user can edit a specific subject line on a performance report.
     */
    public function editSubject(User $user, PerformanceReportSubject $subjectItem): bool
    {
        $report = $subjectItem->performanceReport;

        if ($user->school_id !== $report->school_id) {
            return false;
        }

        // Finalized / Locked reports cannot be edited
        if ($report->isFinalized() || $report->is_locked) {
            return false;
        }

        // Check examination timetable release policy
        $school = $user->school;
        if (!$this->releasePolicyService->isReportAvailable($school, $report->academic_year, $report->term)) {
            // Admins can bypass if needed, normal teachers cannot
            if (!$user->hasAnyRole(['super-admin', 'school-admin'])) {
                return false;
            }
        }

        // Super-admin & school-admin can edit
        if ($user->hasAnyRole(['super-admin', 'school-admin'])) {
            return true;
        }

        // Teacher check: Must be the explicitly assigned teacher or curriculum teacher for this subject & class
        if ($user->hasRole('teacher')) {
            return $this->isTeacherAssignedToSubject($user, $subjectItem);
        }

        return false;
    }

    /**
     * Determine whether the user can enter/edit leadership comments.
     */
    public function editLeadership(User $user, PerformanceReport $report): bool
    {
        if ($user->school_id !== $report->school_id) {
            return false;
        }

        if ($report->isFinalized() || $report->is_locked) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']);
    }

    /**
     * Determine whether the user can digitally sign the report.
     */
    public function sign(User $user, PerformanceReport $report): bool
    {
        if ($user->school_id !== $report->school_id) {
            return false;
        }

        if ($report->isFinalized() || $report->is_locked) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']);
    }

    /**
     * Determine whether the user can apply the official digital stamp.
     */
    public function stamp(User $user, PerformanceReport $report): bool
    {
        if ($user->school_id !== $report->school_id) {
            return false;
        }

        if ($report->isFinalized() || $report->is_locked) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']);
    }

    /**
     * Determine whether the user can finalize the report.
     */
    public function finalize(User $user, PerformanceReport $report): bool
    {
        if ($user->school_id !== $report->school_id) {
            return false;
        }

        if ($report->isFinalized()) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']);
    }

    /**
     * Determine whether the user can reopen a finalized report.
     */
    public function reopen(User $user, PerformanceReport $report): bool
    {
        if ($user->school_id !== $report->school_id) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']);
    }

    /**
     * Check if teacher is assigned to this specific subject item
     */
    public function isTeacherAssignedToSubject(User $user, PerformanceReportSubject $subjectItem): bool
    {
        if ($subjectItem->assigned_teacher_id === $user->id) {
            return true;
        }

        $report = $subjectItem->performanceReport;

        // Check Curriculum assignment for class, subject, and teacher
        return Curriculum::where('school_id', $user->school_id)
            ->where('subject_id', $subjectItem->subject_id)
            ->where('teacher_id', $user->id)
            ->when($report->school_class_id, fn($q) => $q->where('class_id', $report->school_class_id))
            ->where('academic_year', $report->academic_year)
            ->where(function ($q) use ($report) {
                $q->whereNull('term')->orWhere('term', $report->term);
            })
            ->exists();
    }

    /**
     * Check if teacher is assigned to any subject in this report
     */
    public function isTeacherAssignedToReport(User $user, PerformanceReport $report): bool
    {
        return PerformanceReportSubject::where('performance_report_id', $report->id)
            ->where(function ($q) use ($user) {
                $q->where('assigned_teacher_id', $user->id)
                  ->orWhereHas('curriculum', fn($cq) => $cq->where('teacher_id', $user->id));
            })
            ->exists();
    }
}
