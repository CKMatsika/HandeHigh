<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\GradeBand;
use App\Models\GradeScheme;
use App\Models\PerformanceReport;
use App\Models\PerformanceReportSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\ExamReleasePolicyService;
use App\Services\Academic\GradeCalculationService;
use App\Services\Academic\PerformanceReportLifecycleService;
use App\Services\Academic\PerformanceReportPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PerformanceReportController extends Controller
{
    public function __construct(
        protected GradeCalculationService $gradeService,
        protected ExamReleasePolicyService $releasePolicyService,
        protected PerformanceReportLifecycleService $lifecycleService,
        protected PerformanceReportPdfService $pdfService
    ) {}

    protected function getTenantSchool(Request $request): School
    {
        $school = $request->user()?->school;
        if (!$school) {
            abort(403, 'A valid school tenant context is required.');
        }
        return $school;
    }

    /**
     * Entry point: routes teachers to My Subjects, leadership to the Admin Matrix.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isLeadership = $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']);

        if (!$isLeadership && $user->hasRole('teacher')) {
            return redirect()->route('admin.exams.performance-reports.my-subjects');
        }

        return $this->leadershipDashboard($request);
    }

    /**
     * Teacher Dashboard: "My Subject Reports"
     */
    public function mySubjects(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        $academicYear = $request->get('academic_year', (string)date('Y'));
        $term = $request->get('term', 'Term 1');

        $releaseStatus = $this->releasePolicyService->getReleaseStatus($school, $academicYear, $term);

        // Fetch teaching assignments: if leadership/super-admin, show all school curricula; otherwise show user's assigned curricula
        $isLeadership = $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']);
        $curriculaQuery = Curriculum::where('school_id', $school->id)
            ->where('academic_year', $academicYear)
            ->where(function ($q) use ($term) {
                $q->whereNull('term')->orWhere('term', $term);
            });

        if (!$isLeadership) {
            $curriculaQuery->where('teacher_id', $user->id);
        }

        $curricula = $curriculaQuery->with(['class', 'subject', 'teacher'])->get();

        // If no curricula assignments found in database, fallback to available classes & core subjects for administrators
        if ($curricula->isEmpty() && $isLeadership) {
            $allClasses = SchoolClass::where('school_id', $school->id)->get();
            $allSubjects = Subject::where('school_id', $school->id)->get();
            foreach ($allClasses as $c) {
                foreach ($allSubjects as $s) {
                    $curricula->push(new Curriculum([
                        'school_id' => $school->id,
                        'class_id' => $c->id,
                        'subject_id' => $s->id,
                        'academic_year' => $academicYear,
                        'term' => $term,
                    ]));
                }
            }
        }

        // If no curricula assignments yet, check if admin has given access or fallback
        $assignments = [];
        foreach ($curricula as $curr) {
            $class = $curr->class;
            $subject = $curr->subject;

            if (!$class || !$subject) continue;

            // Count enrolled students in this class
            $studentIds = Student::where('school_id', $school->id)
                ->where(function ($q) use ($class) {
                    $q->where('class_name', $class->name)
                      ->orWhere('grade', $class->grade);
                })
                ->pluck('id');

            $totalStudents = $studentIds->count();

            // Count completed and pending subject entries
            $completedCount = PerformanceReportSubject::where('school_id', $school->id)
                ->where('subject_id', $subject->id)
                ->where('status', 'complete')
                ->whereHas('performanceReport', function ($q) use ($academicYear, $term, $studentIds) {
                    $q->where('academic_year', $academicYear)
                      ->where('term', $term)
                      ->whereIn('student_id', $studentIds);
                })
                ->count();

            $assignments[] = [
                'curriculum' => $curr,
                'class' => $class,
                'subject' => $subject,
                'total_students' => $totalStudents,
                'completed_students' => $completedCount,
                'pending_students' => max(0, $totalStudents - $completedCount),
                'completion_percentage' => $totalStudents > 0 ? round(($completedCount / $totalStudents) * 100) : 0,
            ];
        }

        $academicYears = [date('Y') - 1, date('Y'), date('Y') + 1];
        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.performance-reports.teacher-index', compact(
            'assignments',
            'releaseStatus',
            'academicYear',
            'term',
            'academicYears',
            'terms',
            'school'
        ));
    }

    /**
     * Subject Teacher Mark & Comment Capture Interface
     */
    public function teacherEntry(Request $request, SchoolClass $class, Subject $subject)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if ($class->school_id !== $school->id || $subject->school_id !== $school->id) {
            abort(403);
        }

        $academicYear = $request->get('academic_year', (string)date('Y'));
        $term = $request->get('term', 'Term 1');

        // Verify Subject-Level Teacher Authorization
        $isLeadership = $user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']);
        if (!$isLeadership) {
            $isAssigned = Curriculum::where('school_id', $school->id)
                ->where('class_id', $class->id)
                ->where('subject_id', $subject->id)
                ->where('teacher_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                abort(403, 'Unauthorized. You are not assigned to teach this subject to this class.');
            }
        }

        $releaseStatus = $this->releasePolicyService->getReleaseStatus($school, $academicYear, $term);
        if (!$releaseStatus['is_available'] && !$isLeadership) {
            return redirect()->route('admin.exams.performance-reports.my-subjects')
                ->with('error', $releaseStatus['message']);
        }

        // Get students in this class
        $students = Student::where('school_id', $school->id)
            ->where(function ($q) use ($class) {
                $q->where('class_name', $class->name)
                  ->orWhere('grade', $class->grade);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Ensure performance reports and subject items exist for all students
        $reportItems = [];
        foreach ($students as $student) {
            $report = $this->lifecycleService->getOrInitializeReport($student, $academicYear, $term, $class);
            $subjectItem = $report->subjects()->where('subject_id', $subject->id)->first();
            
            if (!$subjectItem) {
                $subjectItem = PerformanceReportSubject::create([
                    'performance_report_id' => $report->id,
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'assigned_teacher_id' => $user->id,
                    'status' => 'not_started',
                ]);
            }

            $reportItems[] = [
                'student' => $student,
                'report' => $report,
                'subjectItem' => $subjectItem,
            ];
        }

        $defaultScheme = $this->gradeService->resolveDefaultScheme($school);

        return view('admin.performance-reports.teacher-entry', compact(
            'class',
            'subject',
            'reportItems',
            'releaseStatus',
            'academicYear',
            'term',
            'defaultScheme',
            'school'
        ));
    }

    /**
     * Save subject marks and comments (AJAX or Form Post).
     */
    public function saveSubjectEvaluation(Request $request, PerformanceReportSubject $subjectItem)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if ($subjectItem->school_id !== $school->id) {
            abort(403);
        }

        // Policy authorization check
        $policy = app(\App\Policies\PerformanceReportPolicy::class);
        if (!$policy->editSubject($user, $subjectItem)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to edit this subject entry or report is locked.'], 403);
            }
            abort(403, 'Unauthorized to edit this subject entry or report is locked.');
        }

        $validated = $request->validate([
            'mark_obtained' => 'nullable|numeric|min:0|max:1000',
            'max_mark' => 'nullable|numeric|min:1|max:1000',
            'result_status' => 'required|string|in:present,absent,no_result,withheld,cancelled',
            'comment' => 'nullable|string|max:2000',
            'comment_font' => 'nullable|string|in:Arial,Times New Roman,Calibri,Georgia,Verdana',
            'comment_font_size' => 'nullable|integer|in:8,9,10,11,12,14,16,18',
            'bold' => 'nullable|boolean',
            'italic' => 'nullable|boolean',
            'alignment' => 'nullable|string|in:left,center,right,justify',
            'action' => 'required|string|in:save_draft,mark_complete',
        ]);

        try {
            $updatedItem = $this->lifecycleService->saveSubjectEvaluation($subjectItem, $validated, $user);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $validated['action'] === 'mark_complete' ? 'Subject evaluation marked complete.' : 'Evaluation draft saved.',
                    'data' => [
                        'id' => $updatedItem->id,
                        'percentage' => $updatedItem->percentage,
                        'grade' => $updatedItem->grade,
                        'is_pass' => $updatedItem->is_pass,
                        'status' => $updatedItem->status,
                        'completed_at' => $updatedItem->completed_at?->format('d M Y H:i'),
                    ],
                ]);
            }

            return back()->with('success', 'Subject evaluation saved successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Real-time calculation API endpoint for client-side reactive preview.
     */
    public function calculateGradePreview(Request $request): JsonResponse
    {
        $school = $this->getTenantSchool($request);
        $mark = $request->has('mark') && $request->mark !== '' ? (float)$request->mark : null;
        $maxMark = $request->filled('max_mark') ? (float)$request->max_mark : 100.0;
        $resultStatus = $request->get('result_status', 'present');

        $percentage = $this->gradeService->calculatePercentage($mark, $maxMark);
        $evaluation = $this->gradeService->evaluateGrade($percentage, $resultStatus, null, $school);

        return response()->json([
            'percentage' => $percentage,
            'grade' => $evaluation['grade'],
            'is_pass' => $evaluation['is_pass'],
            'description' => $evaluation['description'],
            'color' => $evaluation['color'],
            'badge_class' => $evaluation['badge_class'],
        ]);
    }

    /**
     * School Leadership & Admin Dashboard: Matrix of reports, completion, and approvals.
     */
    public function leadershipDashboard(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $academicYear = $request->get('academic_year', (string)date('Y'));
        $term = $request->get('term', 'Term 1');
        $classId = $request->get('class_id');
        $statusFilter = $request->get('status');

        $releaseStatus = $this->releasePolicyService->getReleaseStatus($school, $academicYear, $term);

        $classes = SchoolClass::where('school_id', $school->id)->orderBy('grade')->orderBy('name')->get();
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();

        // Reports query with filters
        $reportsQuery = PerformanceReport::where('school_id', $school->id)
            ->where('academic_year', $academicYear)
            ->where('term', $term)
            ->when($classId, fn($q) => $q->where('school_class_id', $classId))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->with(['student', 'schoolClass', 'subjects.subject', 'headmasterUser', 'deputyUser'])
            ->latest();

        $reports = $reportsQuery->paginate(20)->withQueryString();

        // KPI Counts
        $totalReportsCount = PerformanceReport::where('school_id', $school->id)->where('academic_year', $academicYear)->where('term', $term)->count();
        $awaitingLeadershipCount = PerformanceReport::where('school_id', $school->id)->where('academic_year', $academicYear)->where('term', $term)->where('status', PerformanceReport::STATUS_AWAITING_LEADERSHIP)->count();
        $finalizedCount = PerformanceReport::where('school_id', $school->id)->where('academic_year', $academicYear)->where('term', $term)->whereIn('status', [PerformanceReport::STATUS_FINALIZED, PerformanceReport::STATUS_PUBLISHED, PerformanceReport::STATUS_LOCKED])->count();
        $inProgressCount = PerformanceReport::where('school_id', $school->id)->where('academic_year', $academicYear)->where('term', $term)->where('status', PerformanceReport::STATUS_TEACHER_ENTRY_IN_PROGRESS)->count();

        $academicYears = [date('Y') - 1, date('Y'), date('Y') + 1];
        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.performance-reports.leadership-index', compact(
            'reports',
            'classes',
            'subjects',
            'releaseStatus',
            'academicYear',
            'term',
            'classId',
            'statusFilter',
            'totalReportsCount',
            'awaitingLeadershipCount',
            'finalizedCount',
            'inProgressCount',
            'academicYears',
            'terms',
            'school'
        ));
    }

    /**
     * Show Complete Consolidated Student Performance Report
     */
    public function show(Request $request, PerformanceReport $report)
    {
        $school = $this->getTenantSchool($request);
        if ($report->school_id !== $school->id) {
            abort(403);
        }

        $report->loadMissing([
            'student',
            'schoolClass',
            'subjects.subject',
            'subjects.assignedTeacher',
            'audits.user',
            'gradeScheme.bands',
            'headmasterUser',
            'deputyUser',
            'stampUser',
        ]);

        return view('admin.performance-reports.show', compact('report', 'school'));
    }

    /**
     * Save Headmaster or Deputy Headmaster Comments
     */
    public function saveLeadershipComment(Request $request, PerformanceReport $report)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if ($report->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:headmaster,deputy',
            'comment' => 'required|string|max:3000',
            'comment_font' => 'nullable|string|in:Arial,Times New Roman,Calibri,Georgia,Verdana',
            'comment_font_size' => 'nullable|integer|in:8,9,10,11,12,14,16,18',
            'bold' => 'nullable|boolean',
            'italic' => 'nullable|boolean',
            'alignment' => 'nullable|string|in:left,center,right,justify',
        ]);

        // Verify role authorization
        if ($validated['type'] === 'headmaster' && !$user->hasAnyRole(['super-admin', 'school-admin', 'headmaster'])) {
            abort(403, 'Unauthorized to add Headmaster comment.');
        }

        if ($validated['type'] === 'deputy' && !$user->hasAnyRole(['super-admin', 'school-admin', 'deputy-headmaster'])) {
            abort(403, 'Unauthorized to add Deputy Headmaster comment.');
        }

        $formatting = [
            'font' => $validated['comment_font'] ?? 'Arial',
            'font_size' => $validated['comment_font_size'] ?? 11,
            'bold' => !empty($validated['bold']),
            'italic' => !empty($validated['italic']),
            'alignment' => $validated['alignment'] ?? 'left',
        ];

        try {
            $this->lifecycleService->saveLeadershipComment($report, $validated['type'], $validated['comment'], $formatting, $user);
            return back()->with('success', ucfirst($validated['type']) . ' comment saved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Digitally sign the report (Headmaster or Deputy Headmaster).
     */
    public function applySignature(Request $request, PerformanceReport $report)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if ($report->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'role_type' => 'required|string|in:headmaster,deputy',
        ]);

        if ($validated['role_type'] === 'headmaster' && !$user->hasAnyRole(['super-admin', 'school-admin', 'headmaster'])) {
            abort(403, 'Unauthorized to apply Headmaster digital signature.');
        }

        if ($validated['role_type'] === 'deputy' && !$user->hasAnyRole(['super-admin', 'school-admin', 'deputy-headmaster'])) {
            abort(403, 'Unauthorized to apply Deputy Headmaster digital signature.');
        }

        try {
            $this->lifecycleService->applyDigitalSignature($report, $validated['role_type'], $user);
            return back()->with('success', 'Digital signature applied successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Apply Official Digital School Stamp
     */
    public function applyStamp(Request $request, PerformanceReport $report)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if ($report->school_id !== $school->id) {
            abort(403);
        }

        if (!$user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster'])) {
            abort(403, 'Unauthorized to apply official school stamp.');
        }

        try {
            $this->lifecycleService->applyOfficialStamp($report, $user);
            return back()->with('success', 'Official school digital stamp applied successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Finalize and Lock Report
     */
    public function finalize(Request $request, PerformanceReport $report)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if ($report->school_id !== $school->id) {
            abort(403);
        }

        if (!$user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster'])) {
            abort(403, 'Unauthorized to finalize performance report.');
        }

        try {
            $this->lifecycleService->finalizeReport($report, $user);
            return back()->with('success', 'Performance report finalized and permanently locked.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reopen an already finalized report
     */
    public function reopen(Request $request, PerformanceReport $report)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if ($report->school_id !== $school->id) {
            abort(403);
        }

        if (!$user->hasAnyRole(['super-admin', 'school-admin', 'headmaster'])) {
            abort(403, 'Unauthorized to reopen performance report.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->lifecycleService->reopenReport($report, $validated['reason'], $user);
            return back()->with('success', 'Performance report successfully reopened for amendments.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export / Download PDF
     */
    public function exportPdf(PerformanceReport $report)
    {
        $school = auth()->user()?->school;
        if (!$school || $report->school_id !== $school->id) {
            abort(403);
        }

        return $this->pdfService->downloadPdf($report);
    }

    /**
     * Stream PDF in browser
     */
    public function streamPdf(PerformanceReport $report)
    {
        $school = auth()->user()?->school;
        if (!$school || $report->school_id !== $school->id) {
            abort(403);
        }

        return $this->pdfService->streamPdf($report);
    }

    /**
     * Update School Report Release Policy Settings
     */
    public function updateReleasePolicy(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if (!$user->hasAnyRole(['super-admin', 'school-admin', 'headmaster'])) {
            abort(403);
        }

        $validated = $request->validate([
            'performance_report_release_policy' => 'required|string|in:auto_immediate,auto_delay_hours,scheduled_datetime,manual',
            'performance_report_delay_hours' => 'nullable|integer|min:0|max:168',
            'performance_report_scheduled_release_at' => 'nullable|date',
            'academic_year' => 'nullable|string',
            'term' => 'nullable|string',
            'manual_unlock' => 'nullable|boolean',
        ]);

        SchoolSetting::updateOrCreate(
            ['school_id' => $school->id, 'key' => 'performance_report_release_policy'],
            ['value' => $validated['performance_report_release_policy'], 'category' => 'academics', 'type' => 'string']
        );

        if (isset($validated['performance_report_delay_hours'])) {
            SchoolSetting::updateOrCreate(
                ['school_id' => $school->id, 'key' => 'performance_report_delay_hours'],
                ['value' => (string)$validated['performance_report_delay_hours'], 'category' => 'academics', 'type' => 'integer']
            );
        }

        if (isset($validated['performance_report_scheduled_release_at'])) {
            SchoolSetting::updateOrCreate(
                ['school_id' => $school->id, 'key' => 'performance_report_scheduled_release_at'],
                ['value' => $validated['performance_report_scheduled_release_at'], 'category' => 'academics', 'type' => 'string']
            );
        }

        if (!empty($validated['academic_year']) && !empty($validated['term'])) {
            SchoolSetting::updateOrCreate(
                ['school_id' => $school->id, 'key' => "performance_report_unlocked_{$validated['academic_year']}_{$validated['term']}"],
                ['value' => !empty($validated['manual_unlock']) ? '1' : '0', 'category' => 'academics', 'type' => 'boolean']
            );
        }

        return back()->with('success', 'Performance report release policy updated successfully.');
    }

    /**
     * Batch initialize performance reports for enrolled students.
     */
    public function initializeReports(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $user = $request->user();

        if (!$user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster'])) {
            abort(403);
        }

        $academicYear = $request->input('academic_year', (string)date('Y'));
        $term = $request->input('term', 'Term 1');
        $classId = $request->input('class_id') ? (int)$request->input('class_id') : null;

        $createdCount = $this->lifecycleService->initializeTermReports($school, $academicYear, $term, $classId);

        return redirect()->route('admin.exams.performance-reports.index', [
            'academic_year' => $academicYear,
            'term' => $term,
            'class_id' => $classId,
        ])->with('success', "Successfully initialized {$createdCount} student performance report(s) for {$term} ({$academicYear}).");
    }
}

