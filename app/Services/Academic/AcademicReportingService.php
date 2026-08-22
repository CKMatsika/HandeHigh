<?php

namespace App\Services\Academic;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Result;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcademicReportingService
{
    /**
     * Report 1: Enrollment Summary
     */
    public function getEnrollmentSummary(School $school, array $filters = []): array
    {
        $query = Student::query()
            ->where('students.school_id', $school->id);

        if (!empty($filters['grade'])) {
            $query->where('students.grade', $filters['grade']);
        } elseif (!empty($filters['form'])) {
            $query->where('students.grade', $filters['form']);
        }

        if (!empty($filters['class_name'])) {
            $query->where('students.class_name', $filters['class_name']);
        }

        if (!empty($filters['gender'])) {
            $query->where('students.gender', strtolower($filters['gender']));
        }

        if (!empty($filters['status'])) {
            $query->where('students.status', $filters['status']);
        }

        if (!empty($filters['is_boarding'])) {
            $query->where('students.is_boarding', $filters['is_boarding'] === 'yes' || $filters['is_boarding'] === '1' || $filters['is_boarding'] === true);
        }

        $students = $query->get();
        $totalCount = $students->count();

        $maleCount = $students->where('gender', 'male')->count();
        $femaleCount = $students->where('gender', 'female')->count();
        $otherGenderCount = $totalCount - ($maleCount + $femaleCount);

        $malePct = $totalCount > 0 ? round(($maleCount / $totalCount) * 100, 1) : 0.0;
        $femalePct = $totalCount > 0 ? round(($femaleCount / $totalCount) * 100, 1) : 0.0;

        // Group by Form/Grade
        $byGrade = $students->groupBy(function ($s) {
            return $s->grade ?: 'Unassigned';
        })->map(function ($group, $grade) use ($totalCount) {
            $count = $group->count();
            $male = $group->where('gender', 'male')->count();
            $female = $group->where('gender', 'female')->count();
            return [
                'grade' => $grade,
                'total' => $count,
                'male' => $male,
                'female' => $female,
                'percentage' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0.0,
            ];
        })->sortBy('grade');

        // Group by Class
        $byClass = $students->groupBy(function ($s) {
            return $s->class_name ?: 'Unassigned';
        })->map(function ($group, $className) use ($totalCount) {
            $count = $group->count();
            $male = $group->where('gender', 'male')->count();
            $female = $group->where('gender', 'female')->count();
            return [
                'class_name' => $className,
                'total' => $count,
                'male' => $male,
                'female' => $female,
                'percentage' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0.0,
            ];
        })->sortBy('class_name');

        // Group by Status
        $byStatus = $students->groupBy(function ($s) {
            return ucfirst($s->status ?: 'Active');
        })->map(function ($group, $status) use ($totalCount) {
            $count = $group->count();
            return [
                'status' => $status,
                'total' => $count,
                'percentage' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0.0,
            ];
        });

        // Boarding vs Day
        $boardingCount = $students->where('is_boarding', true)->count();
        $dayCount = $totalCount - $boardingCount;

        // Age brackets
        $ageBrackets = [
            'Under 13' => 0,
            '13 - 14' => 0,
            '15 - 16' => 0,
            '17 - 18' => 0,
            '19+' => 0,
            'Unknown' => 0,
        ];

        foreach ($students as $student) {
            if (!$student->date_of_birth) {
                $ageBrackets['Unknown']++;
                continue;
            }
            $age = Carbon::parse($student->date_of_birth)->age;
            if ($age < 13) {
                $ageBrackets['Under 13']++;
            } elseif ($age <= 14) {
                $ageBrackets['13 - 14']++;
            } elseif ($age <= 16) {
                $ageBrackets['15 - 16']++;
            } elseif ($age <= 18) {
                $ageBrackets['17 - 18']++;
            } else {
                $ageBrackets['19+']++;
            }
        }

        return [
            'total_enrollment' => $totalCount,
            'male_total' => $maleCount,
            'female_total' => $femaleCount,
            'other_gender_total' => $otherGenderCount,
            'male_percentage' => $malePct,
            'female_percentage' => $femalePct,
            'boarding_total' => $boardingCount,
            'day_total' => $dayCount,
            'by_grade' => $byGrade,
            'by_class' => $byClass,
            'by_status' => $byStatus,
            'age_brackets' => $ageBrackets,
            'filters' => $filters,
        ];
    }

    /**
     * Report 2: Enrollment Matrix by Form and Class
     */
    public function getEnrollmentByClass(School $school, array $filters = []): array
    {
        $studentsQuery = Student::where('school_id', $school->id);

        if (!empty($filters['status'])) {
            $studentsQuery->where('status', $filters['status']);
        }
        if (!empty($filters['grade'])) {
            $studentsQuery->where('grade', $filters['grade']);
        }

        $students = $studentsQuery->get();
        $totalSchool = $students->count();

        // Get class teacher details from classes table
        $classes = SchoolClass::where('school_id', $school->id)->with('teacher')->get()->keyBy('name');

        $matrix = [];
        $formTotals = [];

        $groupedByGrade = $students->groupBy(function ($s) {
            return $s->grade ?: 'Unassigned';
        });

        foreach ($groupedByGrade as $grade => $gradeStudents) {
            $formTotal = $gradeStudents->count();
            $formMale = $gradeStudents->where('gender', 'male')->count();
            $formFemale = $gradeStudents->where('gender', 'female')->count();

            $formTotals[$grade] = [
                'grade' => $grade,
                'total' => $formTotal,
                'male' => $formMale,
                'female' => $formFemale,
                'percentage' => $totalSchool > 0 ? round(($formTotal / $totalSchool) * 100, 1) : 0.0,
            ];

            $byClass = $gradeStudents->groupBy(function ($s) {
                return $s->class_name ?: 'General';
            });

            foreach ($byClass as $className => $classStudents) {
                $classTotal = $classStudents->count();
                $classMale = $classStudents->where('gender', 'male')->count();
                $classFemale = $classStudents->where('gender', 'female')->count();
                $teacherName = isset($classes[$className]) && $classes[$className]->teacher
                    ? $classes[$className]->teacher->name
                    : 'Not Assigned';

                $matrix[] = [
                    'grade' => $grade,
                    'class_name' => $className,
                    'class_teacher' => $teacherName,
                    'male' => $classMale,
                    'female' => $classFemale,
                    'total' => $classTotal,
                    'form_percentage' => $formTotal > 0 ? round(($classTotal / $formTotal) * 100, 1) : 0.0,
                    'school_percentage' => $totalSchool > 0 ? round(($classTotal / $totalSchool) * 100, 1) : 0.0,
                ];
            }
        }

        return [
            'matrix' => collect($matrix)->sortBy(['grade', 'class_name'])->values(),
            'form_totals' => $formTotals,
            'grand_total' => $totalSchool,
            'grand_male' => $students->where('gender', 'male')->count(),
            'grand_female' => $students->where('gender', 'female')->count(),
            'filters' => $filters,
        ];
    }

    /**
     * Report 3: Student Register
     */
    public function getStudentRegister(School $school, array $filters = [], int $perPage = 25): array
    {
        $query = Student::query()
            ->where('school_id', $school->id)
            ->with(['guardians']);

        if (!empty($filters['grade'])) {
            $query->where('grade', $filters['grade']);
        } elseif (!empty($filters['form'])) {
            $query->where('grade', $filters['form']);
        }

        if (!empty($filters['class_name'])) {
            $query->where('class_name', $filters['class_name']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', strtolower($filters['gender']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_boarding']) && $filters['is_boarding'] !== '') {
            $query->where('is_boarding', $filters['is_boarding'] === 'yes' || $filters['is_boarding'] === '1' || $filters['is_boarding'] === true);
        }

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('admission_number', 'like', "%{$s}%")
                    ->orWhere('registration_number', 'like', "%{$s}%");
            });
        }

        $query->orderBy('grade')->orderBy('class_name')->orderBy('last_name')->orderBy('first_name');

        $summaryQuery = clone $query;
        $totalRecords = $summaryQuery->count();

        if ($perPage > 0) {
            $students = $query->paginate($perPage)->withQueryString();
        } else {
            $students = $query->get();
        }

        $rows = ($students instanceof LengthAwarePaginator ? $students->getCollection() : $students)->map(function ($s) {
            $primaryGuardian = $s->guardians->where('pivot.is_primary', true)->first() ?: $s->guardians->first();
            $age = $s->date_of_birth ? Carbon::parse($s->date_of_birth)->age : null;

            return [
                'id' => $s->id,
                'admission_number' => $s->admission_number ?: 'N/A',
                'full_name' => "{$s->first_name} {$s->last_name}",
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'gender' => ucfirst($s->gender ?: 'unknown'),
                'date_of_birth' => $s->date_of_birth ? Carbon::parse($s->date_of_birth)->format('Y-m-d') : 'N/A',
                'age' => $age !== null ? $age : 'N/A',
                'grade' => $s->grade ?: 'N/A',
                'class_name' => $s->class_name ?: 'N/A',
                'is_boarding' => $s->is_boarding ? 'Boarding' : 'Day',
                'status' => ucfirst($s->status ?: 'Active'),
                'guardian_name' => $primaryGuardian ? "{$primaryGuardian->first_name} {$primaryGuardian->last_name}" : 'N/A',
                'guardian_phone' => $primaryGuardian ? ($primaryGuardian->phone ?: 'N/A') : 'N/A',
                'guardian_email' => $primaryGuardian ? ($primaryGuardian->email ?: 'N/A') : 'N/A',
            ];
        });

        return [
            'paginator' => $students instanceof LengthAwarePaginator ? $students : null,
            'rows' => $rows,
            'total_students' => $totalRecords,
            'filters' => $filters,
        ];
    }

    /**
     * Report 4: Academic Performance Summary
     */
    public function getAcademicPerformanceSummary(School $school, array $filters = []): array
    {
        $query = Result::where('results.school_id', $school->id)
            ->with(['student', 'class', 'subject']);

        if (!empty($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }
        if (!empty($filters['term'])) {
            $query->where('term', $filters['term']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        $results = $query->get();
        $totalEntries = $results->count();

        if ($totalEntries === 0) {
            return [
                'total_entries' => 0,
                'average_mark' => 0.0,
                'pass_count' => 0,
                'fail_count' => 0,
                'pass_rate' => 0.0,
                'grade_distribution' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'U/F' => 0],
                'by_form' => collect(),
                'by_class' => collect(),
                'by_subject' => collect(),
                'top_performers' => collect(),
                'low_performers' => collect(),
                'filters' => $filters,
            ];
        }

        // Calculate normalized scores (percentage)
        $scores = $results->map(function ($r) {
            $max = (float)($r->max_total_score ?: 100);
            $raw = (float)$r->total_score;
            $pct = $max > 0 ? ($raw / $max) * 100 : $raw;
            return [
                'result' => $r,
                'pct' => round($pct, 2),
                'passed' => $pct >= 50.0,
            ];
        });

        $meanScore = round($scores->avg('pct'), 2);
        $passCount = $scores->where('passed', true)->count();
        $failCount = $totalEntries - $passCount;
        $passRate = round(($passCount / $totalEntries) * 100, 1);

        // Grade distribution
        $gradeDist = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'U/F' => 0];
        foreach ($scores as $s) {
            $p = $s['pct'];
            if ($p >= 75.0) $gradeDist['A']++;
            elseif ($p >= 65.0) $gradeDist['B']++;
            elseif ($p >= 50.0) $gradeDist['C']++;
            elseif ($p >= 40.0) $gradeDist['D']++;
            elseif ($p >= 30.0) $gradeDist['E']++;
            else $gradeDist['U/F']++;
        }

        // By Class
        $byClass = $results->groupBy(function ($r) {
            return $r->class ? $r->class->name : 'Unassigned';
        })->map(function ($group, $className) {
            $count = $group->count();
            $avg = $group->map(function ($r) {
                $max = (float)($r->max_total_score ?: 100);
                return $max > 0 ? ((float)$r->total_score / $max) * 100 : (float)$r->total_score;
            })->avg();
            $passes = $group->filter(function ($r) {
                $max = (float)($r->max_total_score ?: 100);
                $pct = $max > 0 ? ((float)$r->total_score / $max) * 100 : (float)$r->total_score;
                return $pct >= 50.0;
            })->count();

            return [
                'class_name' => $className,
                'candidates' => $count,
                'average' => round($avg, 2),
                'pass_count' => $passes,
                'fail_count' => $count - $passes,
                'pass_rate' => $count > 0 ? round(($passes / $count) * 100, 1) : 0.0,
            ];
        })->values();

        // By Subject
        $bySubject = $results->groupBy(function ($r) {
            return $r->subject ? $r->subject->name : 'Unknown';
        })->map(function ($group, $subjectName) {
            $count = $group->count();
            $avg = $group->map(function ($r) {
                $max = (float)($r->max_total_score ?: 100);
                return $max > 0 ? ((float)$r->total_score / $max) * 100 : (float)$r->total_score;
            })->avg();
            $passes = $group->filter(function ($r) {
                $max = (float)($r->max_total_score ?: 100);
                $pct = $max > 0 ? ((float)$r->total_score / $max) * 100 : (float)$r->total_score;
                return $pct >= 50.0;
            })->count();

            return [
                'subject_name' => $subjectName,
                'candidates' => $count,
                'average' => round($avg, 2),
                'pass_count' => $passes,
                'fail_count' => $count - $passes,
                'pass_rate' => $count > 0 ? round(($passes / $count) * 100, 1) : 0.0,
            ];
        })->sortByDesc('average')->values();

        // Student overall aggregates for top/low performers
        $studentAverages = $results->groupBy('student_id')->map(function ($group) {
            $student = $group->first()->student;
            $avg = $group->map(function ($r) {
                $max = (float)($r->max_total_score ?: 100);
                return $max > 0 ? ((float)$r->total_score / $max) * 100 : (float)$r->total_score;
            })->avg();

            return [
                'student_id' => $group->first()->student_id,
                'admission_number' => $student ? $student->admission_number : 'N/A',
                'student_name' => $student ? "{$student->first_name} {$student->last_name}" : 'Unknown',
                'grade' => $student ? $student->grade : 'N/A',
                'class_name' => $student ? $student->class_name : 'N/A',
                'subjects_count' => $group->count(),
                'average' => round($avg, 2),
            ];
        });

        $topPerformers = $studentAverages->sortByDesc('average')->take(10)->values();
        $lowPerformers = $studentAverages->sortBy('average')->take(10)->values();

        return [
            'total_entries' => $totalEntries,
            'average_mark' => $meanScore,
            'pass_count' => $passCount,
            'fail_count' => $failCount,
            'pass_rate' => $passRate,
            'grade_distribution' => $gradeDist,
            'by_class' => $byClass,
            'by_subject' => $bySubject,
            'top_performers' => $topPerformers,
            'low_performers' => $lowPerformers,
            'filters' => $filters,
        ];
    }

    /**
     * Report 5: Subject Performance
     */
    public function getSubjectPerformance(School $school, array $filters = []): array
    {
        $query = Result::where('results.school_id', $school->id)
            ->with(['subject', 'class', 'student']);

        if (!empty($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }
        if (!empty($filters['term'])) {
            $query->where('term', $filters['term']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        $results = $query->get();

        $grouped = $results->groupBy('subject_id')->map(function ($group) {
            $subject = $group->first()->subject;
            $count = $group->count();

            $scores = $group->map(function ($r) {
                $max = (float)($r->max_total_score ?: 100);
                return $max > 0 ? ((float)$r->total_score / $max) * 100 : (float)$r->total_score;
            });

            $avg = $scores->avg();
            $high = $scores->max();
            $low = $scores->min();
            $passes = $scores->filter(fn($s) => $s >= 50.0)->count();
            $fails = $count - $passes;
            $passRate = $count > 0 ? round(($passes / $count) * 100, 1) : 0.0;

            return [
                'subject_id' => $subject ? $subject->id : null,
                'subject_code' => $subject ? $subject->code : 'N/A',
                'subject_name' => $subject ? $subject->name : 'Unknown Subject',
                'candidates' => $count,
                'average' => round($avg, 2),
                'highest' => round($high, 2),
                'lowest' => round($low, 2),
                'pass_count' => $passes,
                'fail_count' => $fails,
                'pass_rate' => $passRate,
            ];
        })->sortByDesc('average')->values();

        return [
            'rows' => $grouped,
            'total_subjects' => $grouped->count(),
            'grand_candidates' => $results->count(),
            'filters' => $filters,
        ];
    }

    /**
     * Report 6: Student Academic Profile
     */
    public function getStudentAcademicProfile(School $school, Student $student, array $filters = []): array
    {
        if ((int)$student->school_id !== (int)$school->id) {
            abort(404, 'Student not found in current school.');
        }

        $student->load(['guardians', 'enrollments']);

        // Results
        $resultsQuery = Result::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->with(['subject', 'class'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('term', 'desc');

        if (!empty($filters['academic_year'])) {
            $resultsQuery->where('academic_year', $filters['academic_year']);
        }
        if (!empty($filters['term'])) {
            $resultsQuery->where('term', $filters['term']);
        }

        $results = $resultsQuery->get();

        $resultsRows = $results->map(function ($r) {
            $max = (float)($r->max_total_score ?: 100);
            $raw = (float)$r->total_score;
            $pct = $max > 0 ? ($raw / $max) * 100 : $raw;

            $grade = $r->grade;
            if (!$grade) {
                if ($pct >= 75) $grade = 'A';
                elseif ($pct >= 65) $grade = 'B';
                elseif ($pct >= 50) $grade = 'C';
                elseif ($pct >= 40) $grade = 'D';
                elseif ($pct >= 30) $grade = 'E';
                else $grade = 'U';
            }

            return [
                'subject_name' => $r->subject ? $r->subject->name : 'Unknown',
                'subject_code' => $r->subject ? $r->subject->code : 'N/A',
                'academic_year' => $r->academic_year,
                'term' => $r->term,
                'class_name' => $r->class ? $r->class->name : 'N/A',
                'total_score' => $raw,
                'max_score' => $max,
                'percentage' => round($pct, 2),
                'grade' => $grade,
                'remarks' => $r->remarks ?: ($pct >= 50 ? 'Pass' : 'Needs Improvement'),
            ];
        });

        $cumulativeAverage = $resultsRows->count() > 0 ? round($resultsRows->avg('percentage'), 2) : 0.0;

        // Attendance stats for student
        $attendanceRecords = Attendance::where('school_id', $school->id)
            ->where('attendable_type', Student::class)
            ->where('attendable_id', $student->id)
            ->get();

        $totalDays = $attendanceRecords->count();
        $daysPresent = $attendanceRecords->where('status', 'present')->count();
        $daysLate = $attendanceRecords->where('status', 'late')->count();
        $daysAbsent = $attendanceRecords->where('status', 'absent')->count();
        $daysExcused = $attendanceRecords->whereIn('status', ['excused', 'sick_leave'])->count();
        $attendanceRate = $totalDays > 0 ? round((($daysPresent + $daysLate) / $totalDays) * 100, 1) : 100.0;

        // Term progression
        $progression = $resultsRows->groupBy(function ($r) {
            return "{$r['academic_year']} - {$r['term']}";
        })->map(function ($group, $period) {
            return [
                'period' => $period,
                'average' => round($group->avg('percentage'), 2),
                'subjects_count' => $group->count(),
            ];
        })->values();

        return [
            'student' => $student,
            'results_rows' => $resultsRows,
            'cumulative_average' => $cumulativeAverage,
            'attendance' => [
                'total_days' => $totalDays,
                'present' => $daysPresent,
                'late' => $daysLate,
                'absent' => $daysAbsent,
                'excused' => $daysExcused,
                'attendance_rate' => $attendanceRate,
            ],
            'progression' => $progression,
            'filters' => $filters,
        ];
    }

    /**
     * Report 7: Attendance Summary
     */
    public function getAttendanceSummary(School $school, array $filters = []): array
    {
        $query = Attendance::where('attendances.school_id', $school->id)
            ->where('attendances.attendable_type', Student::class);

        if (!empty($filters['start_date'])) {
            $query->where('attendance_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('attendance_date', '<=', $filters['end_date']);
        }

        // Join students to enable form/class/gender filtering
        $query->join('students', function ($join) use ($school) {
            $join->on('students.id', '=', 'attendances.attendable_id')
                ->where('students.school_id', '=', $school->id);
        });

        if (!empty($filters['grade'])) {
            $query->where('students.grade', $filters['grade']);
        } elseif (!empty($filters['form'])) {
            $query->where('students.grade', $filters['form']);
        }

        if (!empty($filters['class_name'])) {
            $query->where('students.class_name', $filters['class_name']);
        }

        if (!empty($filters['gender'])) {
            $query->where('students.gender', strtolower($filters['gender']));
        }

        $records = $query->select([
            'attendances.*',
            'students.grade as student_grade',
            'students.class_name as student_class',
            'students.gender as student_gender',
        ])->get();

        $totalRecords = $records->count();

        $presentCount = $records->where('status', 'present')->count();
        $absentCount = $records->where('status', 'absent')->count();
        $lateCount = $records->where('status', 'late')->count();
        $excusedCount = $records->where('status', 'excused')->count();
        $sickCount = $records->where('status', 'sick_leave')->count();

        $overallAttendanceRate = $totalRecords > 0
            ? round((($presentCount + $lateCount) / $totalRecords) * 100, 1)
            : 0.0;

        // Breakdown by Form/Grade
        $byGrade = $records->groupBy('student_grade')->map(function ($group, $grade) {
            $total = $group->count();
            $present = $group->where('status', 'present')->count();
            $late = $group->where('status', 'late')->count();
            $absent = $group->where('status', 'absent')->count();
            $rate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0.0;

            return [
                'grade' => $grade ?: 'Unassigned',
                'total' => $total,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'rate' => $rate,
            ];
        })->sortBy('grade')->values();

        // Breakdown by Class
        $byClass = $records->groupBy('student_class')->map(function ($group, $className) {
            $total = $group->count();
            $present = $group->where('status', 'present')->count();
            $late = $group->where('status', 'late')->count();
            $absent = $group->where('status', 'absent')->count();
            $rate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0.0;

            return [
                'class_name' => $className ?: 'Unassigned',
                'total' => $total,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'rate' => $rate,
            ];
        })->sortBy('class_name')->values();

        // Breakdown by Gender
        $byGender = $records->groupBy('student_gender')->map(function ($group, $gender) {
            $total = $group->count();
            $present = $group->where('status', 'present')->count();
            $late = $group->where('status', 'late')->count();
            $rate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0.0;

            return [
                'gender' => ucfirst($gender ?: 'Unknown'),
                'total' => $total,
                'present' => $present,
                'late' => $late,
                'rate' => $rate,
            ];
        })->values();

        return [
            'total_records' => $totalRecords,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'excused_count' => $excusedCount,
            'sick_count' => $sickCount,
            'overall_rate' => $overallAttendanceRate,
            'by_grade' => $byGrade,
            'by_class' => $byClass,
            'by_gender' => $byGender,
            'filters' => $filters,
        ];
    }

    /**
     * Report 8: Student Attendance Register
     */
    public function getAttendanceRegister(School $school, array $filters = [], int $perPage = 25): array
    {
        $query = Attendance::where('attendances.school_id', $school->id)
            ->where('attendances.attendable_type', Student::class)
            ->with(['markedBy'])
            ->join('students', function ($join) use ($school) {
                $join->on('students.id', '=', 'attendances.attendable_id')
                    ->where('students.school_id', '=', $school->id);
            });

        if (!empty($filters['start_date'])) {
            $query->where('attendance_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('attendance_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['status'])) {
            $query->where('attendances.status', $filters['status']);
        }
        if (!empty($filters['grade'])) {
            $query->where('students.grade', $filters['grade']);
        } elseif (!empty($filters['form'])) {
            $query->where('students.grade', $filters['form']);
        }
        if (!empty($filters['class_name'])) {
            $query->where('students.class_name', $filters['class_name']);
        }
        if (!empty($filters['student_id'])) {
            $query->where('attendances.attendable_id', $filters['student_id']);
        }

        $query->select([
            'attendances.*',
            'students.first_name as student_first_name',
            'students.last_name as student_last_name',
            'students.admission_number as student_admission_number',
            'students.grade as student_grade',
            'students.class_name as student_class',
        ])->orderBy('attendances.attendance_date', 'desc')
          ->orderBy('students.grade')
          ->orderBy('students.class_name')
          ->orderBy('students.last_name');

        $totalCount = (clone $query)->count();

        if ($perPage > 0) {
            $records = $query->paginate($perPage)->withQueryString();
        } else {
            $records = $query->get();
        }

        $rows = ($records instanceof LengthAwarePaginator ? $records->getCollection() : $records)->map(function ($att) {
            return [
                'id' => $att->id,
                'date' => Carbon::parse($att->attendance_date)->format('Y-m-d'),
                'admission_number' => $att->student_admission_number ?: 'N/A',
                'student_name' => "{$att->student_first_name} {$att->student_last_name}",
                'grade' => $att->student_grade ?: 'N/A',
                'class_name' => $att->student_class ?: 'N/A',
                'status' => ucfirst(str_replace('_', ' ', $att->status)),
                'check_in' => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i') : '-',
                'check_out' => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i') : '-',
                'marked_by' => $att->markedBy ? $att->markedBy->name : 'System',
                'notes' => $att->notes ?: '',
            ];
        });

        return [
            'paginator' => $records instanceof LengthAwarePaginator ? $records : null,
            'rows' => $rows,
            'total_records' => $totalCount,
            'filters' => $filters,
        ];
    }

    /**
     * Report 9: Examination Results Register
     */
    public function getExamResultsRegister(School $school, array $filters = [], int $perPage = 25): array
    {
        $query = Result::where('results.school_id', $school->id)
            ->with(['student', 'class', 'subject']);

        if (!empty($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }
        if (!empty($filters['term'])) {
            $query->where('term', $filters['term']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        if (!empty($filters['grade'])) {
            $query->where('grade', $filters['grade']);
        }

        $query->orderBy('academic_year', 'desc')
            ->orderBy('term', 'desc')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->orderBy('total_score', 'desc');

        $totalCount = (clone $query)->count();

        if ($perPage > 0) {
            $results = $query->paginate($perPage)->withQueryString();
        } else {
            $results = $query->get();
        }

        $rows = ($results instanceof LengthAwarePaginator ? $results->getCollection() : $results)->map(function ($r) {
            $max = (float)($r->max_total_score ?: 100);
            $raw = (float)$r->total_score;
            $pct = $max > 0 ? ($raw / $max) * 100 : $raw;

            $grade = $r->grade;
            if (!$grade) {
                if ($pct >= 75) $grade = 'A';
                elseif ($pct >= 65) $grade = 'B';
                elseif ($pct >= 50) $grade = 'C';
                elseif ($pct >= 40) $grade = 'D';
                elseif ($pct >= 30) $grade = 'E';
                else $grade = 'U';
            }

            return [
                'id' => $r->id,
                'academic_year' => $r->academic_year ?: 'N/A',
                'term' => $r->term ?: 'N/A',
                'admission_number' => $r->student ? $r->student->admission_number : 'N/A',
                'student_name' => $r->student ? "{$r->student->first_name} {$r->student->last_name}" : 'Unknown',
                'class_name' => $r->class ? $r->class->name : 'N/A',
                'subject_code' => $r->subject ? $r->subject->code : 'N/A',
                'subject_name' => $r->subject ? $r->subject->name : 'Unknown Subject',
                'score' => number_format($raw, 2),
                'max_score' => number_format($max, 2),
                'percentage' => round($pct, 2),
                'grade' => $grade,
                'remarks' => $r->remarks ?: ($pct >= 50 ? 'Pass' : 'Fail'),
            ];
        });

        return [
            'paginator' => $results instanceof LengthAwarePaginator ? $results : null,
            'rows' => $rows,
            'total_results' => $totalCount,
            'filters' => $filters,
        ];
    }

    /**
     * Report 10: Executive Academic Dashboard KPIs
     */
    public function getAcademicDashboardKPIs(School $school): array
    {
        // Total Students
        $students = Student::where('school_id', $school->id)->get();
        $totalStudents = $students->count();
        $maleStudents = $students->where('gender', 'male')->count();
        $femaleStudents = $students->where('gender', 'female')->count();

        // Active Classes & Subjects
        $activeClassesCount = SchoolClass::where('school_id', $school->id)->count();
        if ($activeClassesCount === 0) {
            $activeClassesCount = $students->pluck('class_name')->filter()->unique()->count();
        }

        $activeSubjectsCount = Subject::where('school_id', $school->id)->count();

        // Total Teaching Staff
        $teachersCount = Teacher::where('school_id', $school->id)->count();
        if ($teachersCount === 0) {
            $teachersCount = User::where('school_id', $school->id)->role('teacher')->count();
        }

        // Attendance stats for last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30)->toDateString();
        $attRecords = Attendance::where('school_id', $school->id)
            ->where('attendable_type', Student::class)
            ->where('attendance_date', '>=', $thirtyDaysAgo)
            ->get();

        $attTotal = $attRecords->count();
        $attPresent = $attRecords->where('status', 'present')->count();
        $attLate = $attRecords->where('status', 'late')->count();
        $attendanceRate = $attTotal > 0 ? round((($attPresent + $attLate) / $attTotal) * 100, 1) : 0.0;

        // Academic performance stats
        $results = Result::where('school_id', $school->id)->get();
        $resultsCount = $results->count();

        if ($resultsCount > 0) {
            $scores = $results->map(function ($r) {
                $max = (float)($r->max_total_score ?: 100);
                return $max > 0 ? ((float)$r->total_score / $max) * 100 : (float)$r->total_score;
            });
            $overallAverage = round($scores->avg(), 1);
            $passCount = $scores->filter(fn($s) => $s >= 50.0)->count();
            $passRate = round(($passCount / $resultsCount) * 100, 1);
        } else {
            $overallAverage = 0.0;
            $passRate = 0.0;
        }

        // Enrollment by Grade
        $gradeEnrollment = $students->groupBy(function ($s) {
            return $s->grade ?: 'Unassigned';
        })->map(function ($group, $grade) {
            return [
                'grade' => $grade,
                'total' => $group->count(),
                'male' => $group->where('gender', 'male')->count(),
                'female' => $group->where('gender', 'female')->count(),
            ];
        })->sortBy('grade')->values();

        // Top 5 Subjects
        $topSubjects = Result::where('results.school_id', $school->id)
            ->with('subject')
            ->get()
            ->groupBy('subject_id')
            ->map(function ($group) {
                $subject = $group->first()->subject;
                $avg = $group->map(function ($r) {
                    $max = (float)($r->max_total_score ?: 100);
                    return $max > 0 ? ((float)$r->total_score / $max) * 100 : (float)$r->total_score;
                })->avg();
                return [
                    'name' => $subject ? $subject->name : 'Unknown',
                    'average' => round($avg, 1),
                    'candidates' => $group->count(),
                ];
            })
            ->sortByDesc('average')
            ->take(5)
            ->values();

        return [
            'total_students' => $totalStudents,
            'male_students' => $maleStudents,
            'female_students' => $femaleStudents,
            'female_pct' => $totalStudents > 0 ? round(($femaleStudents / $totalStudents) * 100, 1) : 0.0,
            'active_classes_count' => $activeClassesCount,
            'active_subjects_count' => $activeSubjectsCount,
            'teachers_count' => $teachersCount,
            'attendance_rate' => $attendanceRate,
            'attendance_records_count' => $attTotal,
            'overall_average' => $overallAverage,
            'pass_rate' => $passRate,
            'results_count' => $resultsCount,
            'grade_enrollment' => $gradeEnrollment,
            'top_subjects' => $topSubjects,
        ];
    }
}
