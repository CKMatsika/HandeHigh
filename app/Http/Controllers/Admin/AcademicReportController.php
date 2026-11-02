<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceRegisterExport;
use App\Exports\EnrollmentSummaryExport;
use App\Exports\ExamResultsExport;
use App\Exports\StudentRegisterExport;
use App\Exports\SubjectPerformanceExport;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Academic\AcademicReportingService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AcademicReportController extends Controller
{
    public function __construct(
        protected AcademicReportingService $reportingService
    ) {}

    protected function getTenantSchool(Request $request): School
    {
        $school = app(TenantContext::class)->school() ?? $request->user()?->school;

        if (!$school) {
            abort(403, 'A valid school tenant context is required.');
        }

        return $school;
    }

    /**
     * Report 10: Executive Academic Dashboard
     */
    public function academicDashboard(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $kpis = $this->reportingService->getAcademicDashboardKPIs($school);

        return view('admin.reports.academic-dashboard', compact('kpis', 'school'));
    }

    /**
     * Report 1: Enrollment Summary
     */
    public function enrollmentSummary(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['grade', 'form', 'class_name', 'gender', 'status', 'is_boarding']);
        $report = $this->reportingService->getEnrollmentSummary($school, $filters);

        $availableGrades = Student::where('school_id', $school->id)->distinct()->pluck('grade')->filter()->sort()->values();
        $availableClasses = Student::where('school_id', $school->id)->distinct()->pluck('class_name')->filter()->sort()->values();

        return view('admin.reports.enrollment-summary', array_merge($report, [
            'school' => $school,
            'available_grades' => $availableGrades,
            'available_classes' => $availableClasses,
            'filters' => $filters,
        ]));
    }

    public function exportEnrollmentSummary(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['grade', 'form', 'class_name', 'gender', 'status', 'is_boarding']);
        $report = $this->reportingService->getEnrollmentSummary($school, $filters);

        $filename = 'enrollment-summary-' . ($school->code ?: $school->id) . '-' . date('Ymd') . '.xlsx';
        return Excel::download(new EnrollmentSummaryExport($report), $filename);
    }

    /**
     * Report 2: Enrollment by Form / Class
     */
    public function enrollmentByClass(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['grade', 'status']);
        $report = $this->reportingService->getEnrollmentByClass($school, $filters);

        $availableGrades = Student::where('school_id', $school->id)->distinct()->pluck('grade')->filter()->sort()->values();

        return view('admin.reports.enrollment-by-class', array_merge($report, [
            'school' => $school,
            'available_grades' => $availableGrades,
            'filters' => $filters,
        ]));
    }

    /**
     * Report 3: Student Register
     */
    public function studentRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['grade', 'form', 'class_name', 'gender', 'status', 'is_boarding', 'funding_status', 'search']);
        $report = $this->reportingService->getStudentRegister($school, $filters, 25);

        $availableGrades = Student::where('school_id', $school->id)->distinct()->pluck('grade')->filter()->sort()->values();
        $availableClasses = Student::where('school_id', $school->id)->distinct()->pluck('class_name')->filter()->sort()->values();

        return view('admin.reports.student-register', array_merge($report, [
            'school' => $school,
            'available_grades' => $availableGrades,
            'available_classes' => $availableClasses,
            'filters' => $filters,
        ]));
    }

    /** Printable class and student-category listing for active students. */
    public function classCategoryRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->validate([
            'grade' => ['nullable', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'in:day,boarding,scholarship'],
        ]);

        $allStudents = $this->getClassCategoryStudents($school);

        $availableGrades = $allStudents->pluck('report_grade')->filter()->unique()->sort()->values();
        $availableClasses = $allStudents
            ->when(!empty($filters['grade']), fn ($rows) => $rows->where('report_grade', $filters['grade']))
            ->pluck('report_class_name')->filter()->unique()->sort()->values();

        $students = $this->filterClassCategoryStudents($allStudents, $filters);

        return view('admin.reports.class-category-register', compact('school', 'filters', 'students', 'availableGrades', 'availableClasses'));
    }

    public function exportClassCategoryRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->validate([
            'grade' => ['nullable', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'in:day,boarding,scholarship'],
        ]);

        $students = $this->filterClassCategoryStudents($this->getClassCategoryStudents($school), $filters);
        $filename = 'class-category-register-' . ($school->code ?: $school->id) . '-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new \App\Exports\ClassCategoryRegisterExport($students), $filename);
    }

    private function filterClassCategoryStudents($students, array $filters)
    {
        return $students
            ->when(!empty($filters['grade']), fn ($rows) => $rows->where('report_grade', $filters['grade']))
            ->when(!empty($filters['class_name']), fn ($rows) => $rows->where('report_class_name', $filters['class_name']))
            ->when(($filters['category'] ?? null) === 'scholarship', fn ($rows) => $rows->where('funding_status', 'scholarship'))
            ->when(($filters['category'] ?? null) === 'boarding', fn ($rows) => $rows->filter(fn ($student) => $student->funding_status !== 'scholarship' && (bool) $student->report_is_boarding))
            ->when(($filters['category'] ?? null) === 'day', fn ($rows) => $rows->filter(fn ($student) => $student->funding_status !== 'scholarship' && ! (bool) $student->report_is_boarding))
            ->sortBy(fn ($student) => sprintf('%s|%s|%s|%s', $student->report_grade, $student->report_class_name, $student->last_name, $student->first_name))
            ->values();
    }

    private function getClassCategoryStudents(School $school)
    {
        return Student::query()
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->with([
                // latestOfMany joins enrollments to an aggregate subquery, so qualify
                // the selected columns to avoid MySQL's ambiguous student_id error.
                'currentEnrollment' => fn ($q) => $q->select(
                    'enrollments.id',
                    'enrollments.student_id',
                    'enrollments.class_id',
                    'enrollments.grade',
                    'enrollments.class_name',
                    'enrollments.is_boarding'
                ),
                'currentEnrollment.class:id,name,grade',
            ])
            ->get(['id', 'first_name', 'other_names', 'last_name', 'admission_number', 'registration_number', 'grade', 'class_name', 'is_boarding', 'funding_status', 'gender'])
            ->map(function (Student $student) {
                $enrollment = $student->currentEnrollment;
                $student->report_grade = $enrollment?->grade ?: $enrollment?->class?->grade ?: $student->getRawOriginal('grade');
                $student->report_class_name = $enrollment?->class_name ?: $enrollment?->class?->name ?: $student->getRawOriginal('class_name');
                $student->report_is_boarding = $enrollment?->is_boarding ?? (bool) $student->getRawOriginal('is_boarding');
                return $student;
            });
    }

    public function exportStudentRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['grade', 'form', 'class_name', 'gender', 'status', 'is_boarding', 'funding_status', 'search']);
        $report = $this->reportingService->getStudentRegister($school, $filters, 0);

        $filename = 'student-register-' . ($school->code ?: $school->id) . '-' . date('Ymd') . '.xlsx';
        return Excel::download(new StudentRegisterExport($report), $filename);
    }

    /**
     * Report 4: Academic Performance Summary
     */
    public function academicPerformance(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['academic_year', 'term', 'class_id', 'subject_id']);
        $report = $this->reportingService->getAcademicPerformanceSummary($school, $filters);

        $availableClasses = SchoolClass::where('school_id', $school->id)->orderBy('name')->get();
        $availableSubjects = Subject::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.reports.academic-performance', array_merge($report, [
            'school' => $school,
            'available_classes' => $availableClasses,
            'available_subjects' => $availableSubjects,
            'filters' => $filters,
        ]));
    }

    /**
     * Report 5: Subject Performance
     */
    public function subjectPerformance(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['academic_year', 'term', 'subject_id', 'class_id']);
        $report = $this->reportingService->getSubjectPerformance($school, $filters);

        $availableClasses = SchoolClass::where('school_id', $school->id)->orderBy('name')->get();
        $availableSubjects = Subject::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.reports.subject-performance', array_merge($report, [
            'school' => $school,
            'available_classes' => $availableClasses,
            'available_subjects' => $availableSubjects,
            'filters' => $filters,
        ]));
    }

    public function exportSubjectPerformance(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['academic_year', 'term', 'subject_id', 'class_id']);
        $report = $this->reportingService->getSubjectPerformance($school, $filters);

        $filename = 'subject-performance-' . ($school->code ?: $school->id) . '-' . date('Ymd') . '.xlsx';
        return Excel::download(new SubjectPerformanceExport($report), $filename);
    }

    /**
     * Report 6: Student Academic Profile
     */
    public function studentAcademicProfile(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $studentId = $request->get('student_id');
        $filters = $request->only(['academic_year', 'term']);

        $students = Student::where('school_id', $school->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $selectedStudent = null;
        $profile = null;

        if ($studentId) {
            $selectedStudent = Student::where('school_id', $school->id)->where('id', $studentId)->first();
            if (!$selectedStudent) {
                abort(404, 'Student not found in current school.');
            }
            $profile = $this->reportingService->getStudentAcademicProfile($school, $selectedStudent, $filters);
        } elseif ($students->isNotEmpty()) {
            $selectedStudent = $students->first();
            $profile = $this->reportingService->getStudentAcademicProfile($school, $selectedStudent, $filters);
        }

        return view('admin.reports.student-academic-profile', [
            'school' => $school,
            'students' => $students,
            'selected_student' => $selectedStudent,
            'profile' => $profile,
            'filters' => $filters,
        ]);
    }

    /**
     * Report 7: Attendance Summary
     */
    public function attendanceSummary(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['start_date', 'end_date', 'grade', 'form', 'class_name', 'gender']);
        $report = $this->reportingService->getAttendanceSummary($school, $filters);

        $availableGrades = Student::where('school_id', $school->id)->distinct()->pluck('grade')->filter()->sort()->values();
        $availableClasses = Student::where('school_id', $school->id)->distinct()->pluck('class_name')->filter()->sort()->values();

        return view('admin.reports.attendance-summary', array_merge($report, [
            'school' => $school,
            'available_grades' => $availableGrades,
            'available_classes' => $availableClasses,
            'filters' => $filters,
        ]));
    }

    /**
     * Report 8: Student Attendance Register
     */
    public function attendanceRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['start_date', 'end_date', 'status', 'grade', 'form', 'class_name', 'student_id']);
        $report = $this->reportingService->getAttendanceRegister($school, $filters, 25);

        $availableGrades = Student::where('school_id', $school->id)->distinct()->pluck('grade')->filter()->sort()->values();
        $availableClasses = Student::where('school_id', $school->id)->distinct()->pluck('class_name')->filter()->sort()->values();

        return view('admin.reports.attendance-register', array_merge($report, [
            'school' => $school,
            'available_grades' => $availableGrades,
            'available_classes' => $availableClasses,
            'filters' => $filters,
        ]));
    }

    public function exportAttendanceRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['start_date', 'end_date', 'status', 'grade', 'form', 'class_name', 'student_id']);
        $report = $this->reportingService->getAttendanceRegister($school, $filters, 0);

        $filename = 'attendance-register-' . ($school->code ?: $school->id) . '-' . date('Ymd') . '.xlsx';
        return Excel::download(new AttendanceRegisterExport($report), $filename);
    }

    /**
     * Report 9: Examination Results Register
     */
    public function examResultsRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['academic_year', 'term', 'class_id', 'subject_id', 'student_id', 'grade']);
        $report = $this->reportingService->getExamResultsRegister($school, $filters, 25);

        $availableClasses = SchoolClass::where('school_id', $school->id)->orderBy('name')->get();
        $availableSubjects = Subject::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.reports.exam-results', array_merge($report, [
            'school' => $school,
            'available_classes' => $availableClasses,
            'available_subjects' => $availableSubjects,
            'filters' => $filters,
        ]));
    }

    public function exportExamResultsRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['academic_year', 'term', 'class_id', 'subject_id', 'student_id', 'grade']);
        $report = $this->reportingService->getExamResultsRegister($school, $filters, 0);

        $filename = 'exam-results-' . ($school->code ?: $school->id) . '-' . date('Ymd') . '.xlsx';
        return Excel::download(new ExamResultsExport($report), $filename);
    }
}
