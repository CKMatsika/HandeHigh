<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Employee;
use App\Models\Account;
use App\Models\Cashbook;
use App\Models\Department;
use App\Models\SchemeOfWork;
use Illuminate\Support\Collection;

class LeadershipDashboardReportService
{
    /**
     * Get comprehensive Enrollment Report broken down by Class, Gender, and Stream.
     */
    public function getEnrollmentReport(School $school): array
    {
        $classes = SchoolClass::where('school_id', $school->id)
            ->with('teacher')
            ->get();

        $students = Student::where('school_id', $school->id)
            ->with(['currentEnrollment.class.teacher'])
            ->get();

        $totalStudents = $students->count();
        $maleStudents = $students->filter(fn ($s) => strtolower($s->gender ?? '') === 'male')->count();
        $femaleStudents = $students->filter(fn ($s) => strtolower($s->gender ?? '') === 'female')->count();
        $otherStudents = $totalStudents - ($maleStudents + $femaleStudents);

        $malePercentage = $totalStudents > 0 ? round(($maleStudents / $totalStudents) * 100, 1) : 0;
        $femalePercentage = $totalStudents > 0 ? round(($femaleStudents / $totalStudents) * 100, 1) : 0;

        $boarders = $students->filter(fn ($s) => (bool) ($s->is_boarding || $s->currentEnrollment?->is_boarding))->count();
        $dayScholars = $totalStudents - $boarders;
        $activeStudents = $students->filter(fn ($s) => strtolower($s->status ?? 'active') === 'active')->count();

        // Standard secondary grades order
        $standardGradeOrder = ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'];
        $foundGrades = $classes->pluck('grade')
            ->concat($students->map(fn ($s) => $s->grade ?: $s->currentEnrollment?->grade))
            ->filter()
            ->unique();

        $sortedGrades = $foundGrades->sort(function ($a, $b) use ($standardGradeOrder) {
            $posA = array_search($a, $standardGradeOrder);
            $posB = array_search($b, $standardGradeOrder);
            if ($posA !== false && $posB !== false) {
                return $posA <=> $posB;
            }
            if ($posA !== false) return -1;
            if ($posB !== false) return 1;
            return strnatcasecmp($a, $b);
        })->values();

        if ($sortedGrades->isEmpty()) {
            $sortedGrades = collect($standardGradeOrder);
        }

        $standardStreams = ['North', 'South', 'East', 'West'];
        $byGrade = [];
        $streamAggregates = [];

        foreach ($sortedGrades as $grade) {
            $gradeClasses = $classes->filter(fn ($c) => strcasecmp($c->grade, $grade) === 0);
            $gradeStudents = $students->filter(function ($s) use ($grade) {
                $g = $s->grade ?: $s->currentEnrollment?->grade;
                return strcasecmp($g ?? '', $grade) === 0;
            });

            // Find streams in this grade
            $gradeStreams = $gradeClasses->pluck('name')
                ->concat($gradeStudents->map(fn ($s) => $s->class_name ?: $s->currentEnrollment?->class_name ?: $s->currentEnrollment?->class?->name))
                ->filter()
                ->unique();

            if ($gradeStreams->isEmpty()) {
                $gradeStreams = collect($standardStreams);
            }

            $streamsData = [];
            $gradeMale = 0;
            $gradeFemale = 0;
            $gradeBoarders = 0;
            $gradeDay = 0;

            foreach ($gradeStreams as $stream) {
                $matchingClass = $gradeClasses->first(fn ($c) => strcasecmp($c->name, $stream) === 0);
                $teacherName = $matchingClass?->teacher?->name;

                $streamStudents = $gradeStudents->filter(function ($s) use ($stream) {
                    $stName = $s->class_name ?: $s->currentEnrollment?->class_name ?: $s->currentEnrollment?->class?->name;
                    return strcasecmp($stName ?? '', $stream) === 0;
                });

                $sTotal = $streamStudents->count();
                $sMale = $streamStudents->filter(fn ($s) => strtolower($s->gender ?? '') === 'male')->count();
                $sFemale = $streamStudents->filter(fn ($s) => strtolower($s->gender ?? '') === 'female')->count();
                $sOther = $sTotal - ($sMale + $sFemale);
                $sBoarders = $streamStudents->filter(fn ($s) => (bool) ($s->is_boarding || $s->currentEnrollment?->is_boarding))->count();
                $sDay = $sTotal - $sBoarders;

                $gradeMale += $sMale;
                $gradeFemale += $sFemale;
                $gradeBoarders += $sBoarders;
                $gradeDay += $sDay;

                // Stream aggregations across whole school
                if (!isset($streamAggregates[$stream])) {
                    $streamAggregates[$stream] = [
                        'stream' => $stream,
                        'total' => 0,
                        'male' => 0,
                        'female' => 0,
                    ];
                }
                $streamAggregates[$stream]['total'] += $sTotal;
                $streamAggregates[$stream]['male'] += $sMale;
                $streamAggregates[$stream]['female'] += $sFemale;

                $streamsData[] = [
                    'stream' => $stream,
                    'class_id' => $matchingClass?->id,
                    'teacher_name' => $teacherName,
                    'total' => $sTotal,
                    'male' => $sMale,
                    'female' => $sFemale,
                    'other' => $sOther,
                    'boarders' => $sBoarders,
                    'day_scholars' => $sDay,
                    'students' => $streamStudents->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->full_name,
                        'admission_number' => $s->admission_number ?? $s->registration_number ?? 'N/A',
                        'gender' => ucfirst(strtolower($s->gender ?? 'Unspecified')),
                        'is_boarding' => (bool) ($s->is_boarding || $s->currentEnrollment?->is_boarding),
                        'status' => ucfirst($s->status ?? 'Active'),
                        'view_url' => route('admin.students.show', $s->id),
                    ])->values()->all(),
                ];
            }

            $byGrade[] = [
                'grade' => $grade,
                'total' => $gradeStudents->count(),
                'male' => $gradeMale,
                'female' => $gradeFemale,
                'boarders' => $gradeBoarders,
                'day_scholars' => $gradeDay,
                'streams' => $streamsData,
            ];
        }

        return [
            'total_students' => $totalStudents,
            'male_students' => $maleStudents,
            'female_students' => $femaleStudents,
            'other_students' => $otherStudents,
            'male_percentage' => $malePercentage,
            'female_percentage' => $femalePercentage,
            'boarders' => $boarders,
            'day_scholars' => $dayScholars,
            'active_students' => $activeStudents,
            'by_grade' => $byGrade,
            'stream_aggregates' => array_values($streamAggregates),
        ];
    }

    /**
     * Get comprehensive Teachers Report with departmental & qualification drill-downs.
     */
    public function getTeachersReport(School $school): array
    {
        $teachers = Teacher::where('school_id', $school->id)
            ->with(['user', 'subjects', 'classes'])
            ->get();

        $totalTeachers = $teachers->count();
        $activeTeachers = $teachers->filter(fn ($t) => (bool) $t->status)->count();
        $inactiveTeachers = $totalTeachers - $activeTeachers;

        $maleTeachers = $teachers->filter(fn ($t) => strtolower($t->gender ?? '') === 'male')->count();
        $femaleTeachers = $teachers->filter(fn ($t) => strtolower($t->gender ?? '') === 'female')->count();

        // Group by Specialization
        $specializationGroups = [];
        foreach ($teachers as $teacher) {
            $spec = !empty(trim($teacher->specialization ?? '')) ? trim($teacher->specialization) : 'General Education';
            if (!isset($specializationGroups[$spec])) {
                $specializationGroups[$spec] = [
                    'name' => $spec,
                    'count' => 0,
                    'active_count' => 0,
                    'teachers' => [],
                ];
            }
            $specializationGroups[$spec]['count']++;
            if ($teacher->status) {
                $specializationGroups[$spec]['active_count']++;
            }
            $specializationGroups[$spec]['teachers'][] = [
                'id' => $teacher->id,
                'name' => "{$teacher->first_name} {$teacher->last_name}",
                'employee_id' => $teacher->employee_id,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'gender' => ucfirst(strtolower($teacher->gender ?? 'Unspecified')),
                'qualification' => $teacher->qualification ?? 'N/A',
                'specialization' => $teacher->specialization ?? 'General',
                'experience_years' => $teacher->experience_years ?? 0,
                'status' => $teacher->status ? 'Active' : 'Inactive',
                'view_url' => route('admin.teachers.show', $teacher->id),
            ];
        }

        // Group by Qualification
        $qualificationGroups = [];
        foreach ($teachers as $teacher) {
            $qual = !empty(trim($teacher->qualification ?? '')) ? trim($teacher->qualification) : 'Unspecified Qualification';
            if (!isset($qualificationGroups[$qual])) {
                $qualificationGroups[$qual] = [
                    'name' => $qual,
                    'count' => 0,
                    'teachers' => [],
                ];
            }
            $qualificationGroups[$qual]['count']++;
            $qualificationGroups[$qual]['teachers'][] = [
                'id' => $teacher->id,
                'name' => "{$teacher->first_name} {$teacher->last_name}",
                'employee_id' => $teacher->employee_id,
                'specialization' => $teacher->specialization ?? 'General',
                'view_url' => route('admin.teachers.show', $teacher->id),
            ];
        }

        // Schemes of work stats for teachers
        $schemesTotal = SchemeOfWork::where('school_id', $school->id)->count();
        $schemesApproved = SchemeOfWork::where('school_id', $school->id)->where('status', 'approved')->count();
        $schemesPending = SchemeOfWork::where('school_id', $school->id)->where('status', 'submitted')->count();

        // Teacher Class Allocation / Class Teachers
        $classTeachersCount = SchoolClass::where('school_id', $school->id)->whereNotNull('teacher_id')->distinct('teacher_id')->count('teacher_id');

        // Full list formatted for source drilldown
        $teacherRoster = $teachers->map(fn ($teacher) => [
            'id' => $teacher->id,
            'name' => "{$teacher->first_name} {$teacher->last_name}",
            'employee_id' => $teacher->employee_id,
            'email' => $teacher->email,
            'phone' => $teacher->phone ?? 'N/A',
            'gender' => ucfirst(strtolower($teacher->gender ?? 'Unspecified')),
            'qualification' => $teacher->qualification ?? 'Not Specified',
            'specialization' => $teacher->specialization ?? 'General Education',
            'experience_years' => (int) ($teacher->experience_years ?? 0),
            'status' => (bool) $teacher->status ? 'Active' : 'Inactive',
            'classes_count' => $teacher->classes->count(),
            'subjects_count' => $teacher->subjects->count(),
            'view_url' => route('admin.teachers.show', $teacher->id),
        ])->values()->all();

        return [
            'total_teachers' => $totalTeachers,
            'active_teachers' => $activeTeachers,
            'inactive_teachers' => $inactiveTeachers,
            'male_teachers' => $maleTeachers,
            'female_teachers' => $femaleTeachers,
            'class_teachers_count' => $classTeachersCount,
            'schemes_stats' => [
                'total' => $schemesTotal,
                'approved' => $schemesApproved,
                'pending' => $schemesPending,
            ],
            'by_specialization' => array_values($specializationGroups),
            'by_qualification' => array_values($qualificationGroups),
            'roster' => $teacherRoster,
        ];
    }

    /**
     * Get comprehensive Employees / Non-Teaching Staff Report.
     */
    public function getEmployeesReport(School $school): array
    {
        $employees = Employee::where('school_id', $school->id)
            ->with(['department', 'user'])
            ->get();

        $totalEmployees = $employees->count();
        $activeEmployees = $employees->filter(fn ($e) => strtolower($e->employment_status ?? '') === 'active')->count();
        $onLeaveEmployees = $employees->filter(fn ($e) => strtolower($e->employment_status ?? '') === 'on_leave')->count();
        $terminatedEmployees = $employees->filter(fn ($e) => in_array(strtolower($e->employment_status ?? ''), ['terminated', 'resigned']))->count();

        // Employment types
        $fullTime = $employees->filter(fn ($e) => strtolower($e->employment_type ?? '') === 'full_time')->count();
        $partTime = $employees->filter(fn ($e) => strtolower($e->employment_type ?? '') === 'part_time')->count();
        $contract = $employees->filter(fn ($e) => strtolower($e->employment_type ?? '') === 'contract')->count();
        $intern = $employees->filter(fn ($e) => strtolower($e->employment_type ?? '') === 'intern')->count();

        // Monthly payroll commitment
        $monthlyPayroll = (float) $employees->where('employment_status', 'active')->sum('salary');

        // Department breakdown
        $departments = Department::where('school_id', $school->id)->get();
        $deptMap = [];

        foreach ($departments as $dept) {
            $deptMap[$dept->id] = [
                'id' => $dept->id,
                'name' => $dept->name,
                'count' => 0,
                'active_count' => 0,
                'salary_total' => 0.0,
                'employees' => [],
            ];
        }

        $unassignedDept = [
            'id' => null,
            'name' => 'General / Administration',
            'count' => 0,
            'active_count' => 0,
            'salary_total' => 0.0,
            'employees' => [],
        ];

        foreach ($employees as $emp) {
            $empData = [
                'id' => $emp->id,
                'name' => "{$emp->first_name} {$emp->last_name}",
                'employee_id' => $emp->employee_id,
                'email' => $emp->email,
                'phone' => $emp->phone ?? 'N/A',
                'position' => $emp->position ?? 'Staff Member',
                'employment_type' => ucwords(str_replace('_', ' ', $emp->employment_type ?? 'full_time')),
                'employment_status' => ucfirst($emp->employment_status ?? 'active'),
                'salary' => (float) ($emp->salary ?? 0),
                'hire_date' => $emp->hire_date?->format('d M Y') ?? 'N/A',
                'department_name' => $emp->department?->name ?? 'General / Administration',
                'view_url' => route('admin.employees.show', $emp->id),
            ];

            if ($emp->department_id && isset($deptMap[$emp->department_id])) {
                $deptMap[$emp->department_id]['count']++;
                if ($emp->employment_status === 'active') {
                    $deptMap[$emp->department_id]['active_count']++;
                }
                $deptMap[$emp->department_id]['salary_total'] += (float) ($emp->salary ?? 0);
                $deptMap[$emp->department_id]['employees'][] = $empData;
            } else {
                $unassignedDept['count']++;
                if ($emp->employment_status === 'active') {
                    $unassignedDept['active_count']++;
                }
                $unassignedDept['salary_total'] += (float) ($emp->salary ?? 0);
                $unassignedDept['employees'][] = $empData;
            }
        }

        $byDepartment = array_values($deptMap);
        if ($unassignedDept['count'] > 0 || empty($byDepartment)) {
            $byDepartment[] = $unassignedDept;
        }

        // Full roster formatted for drilldown
        $employeeRoster = $employees->map(fn ($emp) => [
            'id' => $emp->id,
            'name' => "{$emp->first_name} {$emp->last_name}",
            'employee_id' => $emp->employee_id,
            'email' => $emp->email,
            'phone' => $emp->phone ?? 'N/A',
            'department_id' => $emp->department_id,
            'department_name' => $emp->department?->name ?? 'General Administration',
            'position' => $emp->position ?? 'Staff Member',
            'employment_type' => ucwords(str_replace('_', ' ', $emp->employment_type ?? 'full_time')),
            'employment_status' => ucfirst($emp->employment_status ?? 'active'),
            'salary' => (float) ($emp->salary ?? 0),
            'hire_date' => $emp->hire_date?->format('d M Y') ?? 'N/A',
            'view_url' => route('admin.employees.show', $emp->id),
        ])->values()->all();

        return [
            'total_employees' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'on_leave_employees' => $onLeaveEmployees,
            'terminated_employees' => $terminatedEmployees,
            'employment_types' => [
                'full_time' => $fullTime,
                'part_time' => $partTime,
                'contract' => $contract,
                'intern' => $intern,
            ],
            'monthly_payroll' => $monthlyPayroll,
            'by_department' => $byDepartment,
            'roster' => $employeeRoster,
        ];
    }

    /**
     * Get comprehensive Bank Balance Summary and liquid accounts overview.
     */
    public function getBankBalanceSummary(School $school): array
    {
        $accounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function ($query) {
                $query->where('category', 'bank')
                      ->orWhere('category', 'cash')
                      ->orWhere('code', 'like', '13%')
                      ->orWhere('code', '1100');
            })
            ->where('is_active', true)
            ->withCount(['cashbookEntries as recent_transactions_count' => function ($query) {
                $query->where('transaction_date', '>=', now()->subDays(7));
            }])
            ->orderBy('code')
            ->get();

        $accountsData = [];
        $totalsByCurrency = [];
        $totalBankBalance = 0.0;
        $totalCashBalance = 0.0;

        foreach ($accounts as $acc) {
            $currency = $acc->currency ?: 'USD';
            $balance = (float) $acc->current_balance;
            $isBank = strtolower($acc->category ?? '') === 'bank' || str_starts_with($acc->code ?? '', '13');

            if (!isset($totalsByCurrency[$currency])) {
                $totalsByCurrency[$currency] = 0.0;
            }
            $totalsByCurrency[$currency] += $balance;

            if ($isBank) {
                $totalBankBalance += $balance;
            } else {
                $totalCashBalance += $balance;
            }

            $lastEntry = $acc->cashbookEntries()
                ->latest('transaction_date')
                ->latest('id')
                ->first();

            $accountsData[] = [
                'id' => $acc->id,
                'code' => $acc->code,
                'name' => $acc->name,
                'bank_name' => $acc->bank_name,
                'account_number' => $acc->account_number,
                'branch' => $acc->branch,
                'currency' => $currency,
                'current_balance' => $balance,
                'formatted_balance' => number_format($balance, 2),
                'recent_transactions_count' => (int) $acc->recent_transactions_count,
                'last_transaction_date' => $lastEntry?->transaction_date ? date('d M Y', strtotime($lastEntry->transaction_date)) : 'No recent activity',
                'is_bank' => $isBank,
                'account_type_label' => $isBank ? 'Bank Account' : 'Cash Account',
                'view_url' => route('admin.bank-accounts.show', $acc->id),
                'cashbook_url' => route('admin.cashbook.index', ['account_id' => $acc->id]),
            ];
        }

        // Recent cashbook transactions across all liquid accounts
        $recentTransactions = Cashbook::where('school_id', $school->id)
            ->with('account')
            ->latest('transaction_date')
            ->latest('id')
            ->take(8)
            ->get()
            ->map(fn ($cb) => [
                'id' => $cb->id,
                'transaction_date' => $cb->transaction_date ? date('d M Y', strtotime($cb->transaction_date)) : '',
                'reference' => $cb->reference_number ?? $cb->cheque_number ?? "CB-{$cb->id}",
                'account_name' => $cb->account?->name ?? 'Primary Account',
                'description' => $cb->description ?? 'Transaction',
                'transaction_type' => $cb->transaction_type ?? 'income',
                'amount' => (float) ($cb->amount ?? 0),
                'formatted_amount' => number_format((float) ($cb->amount ?? 0), 2),
                'balance_after' => $cb->balance_after !== null ? number_format((float) $cb->balance_after, 2) : 'N/A',
                'category' => ucfirst($cb->category ?? 'General'),
            ])->values()->all();

        return [
            'accounts' => $accountsData,
            'total_bank_balance' => $totalBankBalance,
            'total_cash_balance' => $totalCashBalance,
            'net_liquidity' => $totalBankBalance + $totalCashBalance,
            'totals_by_currency' => $totalsByCurrency,
            'active_accounts_count' => count($accountsData),
            'recent_transactions' => $recentTransactions,
        ];
    }

    /**
     * Handle dynamic AJAX drilldown queries from dashboards.
     */
    public function getDrilldownData(School $school, string $type, array $params = []): array
    {
        return match ($type) {
            'students' => $this->drilldownStudents($school, $params),
            'teachers' => $this->drilldownTeachers($school, $params),
            'employees' => $this->drilldownEmployees($school, $params),
            'bank_account' => $this->drilldownBankAccount($school, $params),
            default => ['success' => false, 'message' => 'Invalid drilldown type'],
        };
    }

    protected function drilldownStudents(School $school, array $params): array
    {
        $query = Student::where('school_id', $school->id)->with(['currentEnrollment.class']);

        if (!empty($params['grade'])) {
            $grade = $params['grade'];
            $query->where(function ($q) use ($grade) {
                $q->where('grade', $grade)
                  ->orWhereHas('enrollments', fn ($eq) => $eq->where('grade', $grade));
            });
        }

        if (!empty($params['stream'])) {
            $stream = $params['stream'];
            $query->where(function ($q) use ($stream) {
                $q->where('class_name', $stream)
                  ->orWhereHas('enrollments', function ($eq) use ($stream) {
                      $eq->where('class_name', $stream)
                         ->orWhereHas('class', fn ($cq) => $cq->where('name', $stream));
                  });
            });
        }

        if (!empty($params['gender'])) {
            $query->where('gender', $params['gender']);
        }

        if (!empty($params['is_boarding'])) {
            $isBoarding = filter_var($params['is_boarding'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_boarding', $isBoarding);
        }

        $records = $query->take(100)->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->full_name,
            'admission_number' => $s->admission_number ?? $s->registration_number ?? 'N/A',
            'grade' => $s->grade ?: ($s->currentEnrollment?->grade ?? 'N/A'),
            'stream' => $s->class_name ?: ($s->currentEnrollment?->class_name ?? 'N/A'),
            'gender' => ucfirst(strtolower($s->gender ?? 'Unspecified')),
            'is_boarding' => (bool) ($s->is_boarding || $s->currentEnrollment?->is_boarding),
            'status' => ucfirst($s->status ?? 'Active'),
            'view_url' => route('admin.students.show', $s->id),
        ]);

        return [
            'success' => true,
            'count' => $records->count(),
            'records' => $records,
            'source_url' => route('admin.students.index', array_filter([
                'grade' => $params['grade'] ?? null,
                'stream' => $params['stream'] ?? null,
                'gender' => $params['gender'] ?? null,
            ])),
        ];
    }

    protected function drilldownTeachers(School $school, array $params): array
    {
        $query = Teacher::where('school_id', $school->id)->with(['user', 'classes', 'subjects']);

        if (!empty($params['specialization'])) {
            $query->where('specialization', $params['specialization']);
        }

        if (!empty($params['qualification'])) {
            $query->where('qualification', 'like', "%{$params['qualification']}%");
        }

        if (!empty($params['gender'])) {
            $query->where('gender', $params['gender']);
        }

        if (isset($params['status'])) {
            $query->where('status', (bool) $params['status']);
        }

        $records = $query->take(100)->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => "{$t->first_name} {$t->last_name}",
            'employee_id' => $t->employee_id,
            'email' => $t->email,
            'phone' => $t->phone ?? 'N/A',
            'gender' => ucfirst(strtolower($t->gender ?? 'Unspecified')),
            'qualification' => $t->qualification ?? 'N/A',
            'specialization' => $t->specialization ?? 'General Education',
            'status' => $t->status ? 'Active' : 'Inactive',
            'classes_count' => $t->classes->count(),
            'view_url' => route('admin.teachers.show', $t->id),
        ]);

        return [
            'success' => true,
            'count' => $records->count(),
            'records' => $records,
            'source_url' => route('admin.teachers.index', array_filter([
                'specialization' => $params['specialization'] ?? null,
                'gender' => $params['gender'] ?? null,
            ])),
        ];
    }

    protected function drilldownEmployees(School $school, array $params): array
    {
        $query = Employee::where('school_id', $school->id)->with('department');

        if (!empty($params['department_id'])) {
            $query->where('department_id', $params['department_id']);
        }

        if (!empty($params['employment_type'])) {
            $query->where('employment_type', $params['employment_type']);
        }

        if (!empty($params['status'])) {
            $query->where('employment_status', $params['status']);
        }

        $records = $query->take(100)->get()->map(fn ($e) => [
            'id' => $e->id,
            'name' => "{$e->first_name} {$e->last_name}",
            'employee_id' => $e->employee_id,
            'department_name' => $e->department?->name ?? 'General Administration',
            'position' => $e->position ?? 'Staff',
            'employment_type' => ucwords(str_replace('_', ' ', $e->employment_type ?? 'full_time')),
            'employment_status' => ucfirst($e->employment_status ?? 'active'),
            'hire_date' => $e->hire_date?->format('d M Y') ?? 'N/A',
            'view_url' => route('admin.employees.show', $e->id),
        ]);

        return [
            'success' => true,
            'count' => $records->count(),
            'records' => $records,
            'source_url' => route('admin.employees.index', array_filter([
                'department_id' => $params['department_id'] ?? null,
                'status' => $params['status'] ?? null,
            ])),
        ];
    }

    protected function drilldownBankAccount(School $school, array $params): array
    {
        $accountId = $params['account_id'] ?? null;
        if (!$accountId) {
            return ['success' => false, 'message' => 'Missing account ID'];
        }

        $account = Account::where('school_id', $school->id)->where('id', $accountId)->firstOrFail();
        $transactions = Cashbook::where('account_id', $account->id)
            ->latest('transaction_date')
            ->latest('id')
            ->take(50)
            ->get()
            ->map(fn ($cb) => [
                'id' => $cb->id,
                'date' => $cb->transaction_date ? date('d M Y', strtotime($cb->transaction_date)) : '',
                'reference' => $cb->reference_number ?? "CB-{$cb->id}",
                'description' => $cb->description ?? 'Transaction',
                'type' => ucfirst($cb->transaction_type ?? 'income'),
                'amount' => number_format((float) ($cb->amount ?? 0), 2),
                'balance_after' => $cb->balance_after !== null ? number_format((float) $cb->balance_after, 2) : 'N/A',
            ]);

        return [
            'success' => true,
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'current_balance' => $account->formatted_balance,
                'currency' => $account->currency ?: 'USD',
            ],
            'count' => $transactions->count(),
            'records' => $transactions,
            'source_url' => route('admin.bank-accounts.show', $account->id),
        ];
    }
}
