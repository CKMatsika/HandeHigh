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
        $filters = $request->only(['grade', 'form', 'class_name', 'gender', 'status', 'is_boarding', 'search']);
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

    public function exportStudentRegister(Request $request)
    {
        $school = $this->getTenantSchool($request);
        $filters = $request->only(['grade', 'form', 'class_name', 'gender', 'status', 'is_boarding', 'search']);
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
