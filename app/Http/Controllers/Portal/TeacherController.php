<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CareerGuidanceAssessment;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Message;
use App\Models\Project;
use App\Models\SchemeOfWork;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['teacher', 'super-admin'])) {
            abort(403);
        }

        $teacher = $this->getOrCreateTeacherRecord($user, $school);
        $teacherIds = array_values(array_unique(array_filter([
            $user->id,
            $teacher?->id,
            $teacher?->user_id,
        ])));

        $managedClasses = collect();
        $teachingClasses = collect();
        $teacherClasses = collect();
        $allClasses = collect();
        $curricula = collect();
        $students = collect();
        $assignments = collect();
        $attendance = collect();
        $announcements = collect();
        $schedule = collect();
        $teacherProjects = collect();
        $schemesCount = 0;
        $draftSchemesCount = 0;
        $submittedSchemesCount = 0;
        $approvedSchemesCount = 0;
        $recentSchemes = collect();
        $careerAssessmentsCount = 0;
        $careerAssessedStudents = 0;
        $careerStudentTotal = 0;
        $studentsCount = 0;

        $selectedClassId = $request->filled('class_id') ? (int) $request->get('class_id') : null;
        $selectedClass = null;

        if ($user->hasRole('teacher')) {
            // 1. Classes managed administratively (Class Teacher - attendance register, roll call)
            $managedClasses = $school->classes()
                ->whereIn('teacher_id', $teacherIds)
                ->get();
            $teacherClasses = $managedClasses;

            // 2. Curricula and subjects taught by this teacher
            $curricula = $school->curricula()
                ->whereIn('teacher_id', $teacherIds)
                ->with(['class', 'subject'])
                ->get();

            // 3. Timetable slots where this teacher is scheduled
            $activeTimetable = Timetable::where('school_id', $school->id)
                ->where(function ($q) {
                    $q->where('status', 'published')->orWhere('is_active', true);
                })
                ->latest()
                ->first();

            $timetableSlots = TimetableSlot::whereIn('teacher_id', $teacherIds)
                ->when($activeTimetable, fn($q) => $q->where('timetable_id', $activeTimetable->id))
                ->with(['schoolClass', 'subject', 'room'])
                ->get();
            $timetableClassIds = $timetableSlots->pluck('school_class_id')->filter()->unique()->values();

            // 4. Schemes of work created by or assigned to this teacher
            $schemesOfWork = SchemeOfWork::where('school_id', $school->id)
                ->whereIn('teacher_id', $teacherIds)
                ->with(['schoolClass', 'subject'])
                ->get();
            $schemeClassIds = $schemesOfWork->pluck('school_class_id')->filter()->unique()->values();

            // Combine all teaching class IDs
            $teachingClassIds = $curricula->pluck('class_id')
                ->merge($timetableClassIds)
                ->merge($schemeClassIds)
                ->unique()
                ->filter()
                ->values();

            $teachingClasses = $school->classes()
                ->whereIn('id', $teachingClassIds)
                ->get();

            // All associated classes for this teacher
            $allClasses = $managedClasses->merge($teachingClasses)->unique('id')->values();
            $allClassIds = $allClasses->pluck('id')->filter()->values();
            $allClassNames = $allClasses->pluck('name')->filter()->unique()->values()->all();

            // Calculate real student counts for each class
            foreach ($allClasses as $c) {
                $variants = $this->getClassNameVariations($c->name);
                $cCount = Student::where('school_id', $school->id)
                    ->where(function ($q) use ($c, $variants) {
                        $q->whereIn('class_name', $variants)
                          ->orWhereHas('enrollments', function ($eq) use ($c, $variants) {
                              $eq->where('class_id', $c->id)
                                 ->orWhereIn('class_name', $variants);
                          });
                    })
                    ->count();
                $c->real_student_count = $cCount;
                $c->enrollments_count = $cCount;
            }

            // Sync counts across collections
            foreach ($managedClasses as $mc) {
                $matched = $allClasses->firstWhere('id', $mc->id);
                $mc->real_student_count = $matched?->real_student_count ?? 0;
                $mc->enrollments_count = $matched?->real_student_count ?? 0;
            }
            foreach ($teachingClasses as $tc) {
                $matched = $allClasses->firstWhere('id', $tc->id);
                $tc->real_student_count = $matched?->real_student_count ?? 0;
                $tc->enrollments_count = $matched?->real_student_count ?? 0;
            }
            foreach ($curricula as $curr) {
                if ($curr->class) {
                    $matched = $allClasses->firstWhere('id', $curr->class->id);
                    $curr->class->real_student_count = $matched?->real_student_count ?? 0;
                    $curr->class->enrollments_count = $matched?->real_student_count ?? 0;
                }
            }

            // Check if a specific class is selected
            if ($selectedClassId) {
                $selectedClass = $allClasses->firstWhere('id', $selectedClassId);
            }

            // Build students query
            if ($selectedClass) {
                $selVariants = $this->getClassNameVariations($selectedClass->name);
                $studentsQuery = $school->students()
                    ->where(function ($q) use ($selectedClass, $selVariants) {
                        $q->whereIn('class_name', $selVariants)
                          ->orWhereHas('enrollments', function ($eq) use ($selectedClass, $selVariants) {
                              $eq->where('class_id', $selectedClass->id)
                                 ->orWhereIn('class_name', $selVariants);
                          });
                    });
            } elseif ($allClassIds->isNotEmpty() || !empty($allClassNames)) {
                $allVariants = [];
                foreach ($allClassNames as $cName) {
                    $allVariants = array_merge($allVariants, $this->getClassNameVariations($cName));
                }
                $allVariants = array_values(array_unique($allVariants));

                $studentsQuery = $school->students()
                    ->where(function ($q) use ($allClassIds, $allVariants) {
                        $q->whereIn('class_name', $allVariants)
                          ->orWhereHas('enrollments', function ($eq) use ($allClassIds, $allVariants) {
                              $eq->whereIn('class_id', $allClassIds)
                                 ->orWhereIn('class_name', $allVariants);
                          });
                    });
            } else {
                $studentsQuery = $school->students()->whereRaw('1 = 0');
            }

            if ($request->filled('student_search')) {
                $term = trim($request->get('student_search'));
                $studentsQuery->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                      ->orWhere('last_name', 'like', "%{$term}%")
                      ->orWhere('admission_number', 'like', "%{$term}%")
                      ->orWhere('registration_number', 'like', "%{$term}%");
                });
            }

            $studentsCount = (clone $studentsQuery)->count();
            $students = (clone $studentsQuery)
                ->with('enrollments')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(60)
                ->get();

            // Schemes of work metrics
            if ($teacher) {
                $schemesQuery = SchemeOfWork::where('school_id', $school->id)->whereIn('teacher_id', $teacherIds);
                $schemesCount = $schemesQuery->count();
                $draftSchemesCount = (clone $schemesQuery)->where('status', 'draft')->count();
                $submittedSchemesCount = (clone $schemesQuery)->where('status', 'submitted')->count();
                $approvedSchemesCount = (clone $schemesQuery)->where('status', 'approved')->count();
                $recentSchemes = SchemeOfWork::with(['subject', 'schoolClass'])
                    ->where('school_id', $school->id)
                    ->whereIn('teacher_id', $teacherIds)
                    ->latest()
                    ->limit(5)
                    ->get();
            }

            // Projects where teacher is involved
            try {
                if (Schema::hasColumn('projects', 'project_manager_id')) {
                    $teacherProjects = Project::where('school_id', $school->id)
                        ->where(function($query) use ($user) {
                            $query->where('project_manager_id', $user->id);
                            if (Schema::hasColumn('projects', 'team_lead_id')) {
                                $query->orWhere('team_lead_id', $user->id);
                            }
                            if (Schema::hasColumn('projects', 'team_members')) {
                                $query->orWhereJsonContains('team_members', $user->id);
                            }
                        })->get();
                }
            } catch (\Throwable $e) {
                Log::warning('Could not load teacher projects: ' . $e->getMessage());
                $teacherProjects = collect();
            }

            // Real assignments and assessments
            $curriculaIds = $curricula->pluck('id')->filter()->values();
            if ($curriculaIds->isNotEmpty()) {
                $assignments = Exam::whereIn('curriculum_id', $curriculaIds)
                    ->with(['curriculum.class', 'curriculum.subject', 'assessments'])
                    ->latest('exam_date')
                    ->limit(5)
                    ->get()
                    ->map(function ($exam) {
                        return (object)[
                            'title' => $exam->title,
                            'class' => $exam->curriculum?->class?->name ?? 'General',
                            'subject' => $exam->curriculum?->subject?->name ?? 'Subject',
                            'due_date' => $exam->exam_date,
                            'submissions' => $exam->assessments->count(),
                            'total' => $exam->curriculum?->class?->real_student_count ?? $exam->assessments->count(),
                        ];
                    });
            }

            // Real attendance records for today
            if ($allClassIds->isNotEmpty() || !empty($allClassNames)) {
                $classStudentIds = (clone $studentsQuery)->pluck('id');
                if ($classStudentIds->isNotEmpty()) {
                    $todayAttendances = Attendance::where('school_id', $school->id)
                        ->where('attendable_type', Student::class)
                        ->whereIn('attendable_id', $classStudentIds)
                        ->whereDate('attendance_date', today())
                        ->get();

                    if ($todayAttendances->isNotEmpty()) {
                        $totalMarked = $todayAttendances->count();
                        $presentCount = $todayAttendances->where('status', 'present')->count();
                        $rate = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100) : 0;
                        $attendance->push((object)[
                            'class' => $selectedClass?->name ?? ($allClasses->count() === 1 ? $allClasses->first()->name : 'My Classes'),
                            'date' => today(),
                            'present' => $presentCount,
                            'total' => $totalMarked,
                            'rate' => $rate,
                        ]);
                    }
                }
            }

            // Real staff announcements
            try {
                $announcements = Message::where('school_id', $school->id)
                    ->where('type', 'announcement')
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($msg) {
                        return (object)[
                            'title' => 'School Notice',
                            'message' => $msg->content,
                            'date' => $msg->created_at,
                            'priority' => 'medium',
                        ];
                    });
            } catch (\Throwable $e) {
                $announcements = collect();
            }

            // Career guidance stats
            try {
                $allClassStudentIds = (clone $studentsQuery)->pluck('id');
                $careerStudentTotal = $allClasses->sum('real_student_count') ?: $allClassStudentIds->count();
                $careerAssessmentsCount = $careerStudentTotal > 0
                    ? CareerGuidanceAssessment::whereIn('student_id', $allClassStudentIds)->where('status', 'published')->count()
                    : 0;
                $careerAssessedStudents = $careerAssessmentsCount;
            } catch (\Throwable $e) {
                Log::warning('Could not query career guidance stats: ' . $e->getMessage());
                $careerStudentTotal = 0;
                $careerAssessmentsCount = 0;
                $careerAssessedStudents = 0;
            }

            // Real schedule for today
            $todayDay = strtolower(now()->englishDayOfWeek);
            $todaySlots = TimetableSlot::whereIn('teacher_id', $teacherIds)
                ->when($activeTimetable, fn($q) => $q->where('timetable_id', $activeTimetable->id))
                ->where('day_of_week', $todayDay)
                ->with(['schoolClass', 'subject', 'room'])
                ->orderBy('start_time')
                ->get();

            $schedule = $todaySlots->map(function ($slot) {
                return (object)[
                    'time' => substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5),
                    'class' => $slot->schoolClass?->name ?? 'Class',
                    'subject' => $slot->subject?->name ?? 'Subject',
                    'room' => $slot->room?->name ?? 'Classroom',
                ];
            });
        } else {
            // For super admin
            $managedClasses = $school->classes()->limit(1)->get();
            $teachingClasses = $school->classes()->limit(3)->get();
            $teacherClasses = $managedClasses;
            $allClasses = $managedClasses->merge($teachingClasses)->unique('id')->values();
            $curricula = $school->curricula()->with(['class', 'subject'])->limit(5)->get();
            $students = $school->students()->limit(12)->get();
            $studentsCount = $students->count();
            $careerStudentTotal = $school->students()->count();

            try {
                $teacherProjects = Project::where('school_id', $school->id)->limit(5)->get();
            } catch (\Throwable $e) {
                $teacherProjects = collect();
            }

            $schemesCount = SchemeOfWork::where('school_id', $school->id)->count();
            $draftSchemesCount = SchemeOfWork::where('school_id', $school->id)->where('status', 'draft')->count();
            $submittedSchemesCount = SchemeOfWork::where('school_id', $school->id)->where('status', 'submitted')->count();
            $approvedSchemesCount = SchemeOfWork::where('school_id', $school->id)->where('status', 'approved')->count();
            $recentSchemes = SchemeOfWork::with(['subject', 'schoolClass'])
                ->where('school_id', $school->id)
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('portal.teacher.dashboard', [
            'school' => $school,
            'user' => $user,
            'teacher' => $teacher,
            'managedClasses' => $managedClasses,
            'teacherClasses' => $teacherClasses,
            'teachingClasses' => $teachingClasses,
            'allClasses' => $allClasses,
            'selectedClass' => $selectedClass,
            'selectedClassId' => $selectedClassId,
            'curricula' => $curricula,
            'students' => $students,
            'studentsCount' => $studentsCount,
            'assignments' => $assignments,
            'attendance' => $attendance,
            'announcements' => $announcements,
            'schedule' => $schedule,
            'teacherProjects' => $teacherProjects,
            'schemesCount' => $schemesCount,
            'draftSchemesCount' => $draftSchemesCount,
            'submittedSchemesCount' => $submittedSchemesCount,
            'approvedSchemesCount' => $approvedSchemesCount,
            'recentSchemes' => $recentSchemes,
            'careerAssessmentsCount' => $careerAssessmentsCount,
            'careerAssessedStudents' => $careerAssessedStudents,
            'careerStudentTotal' => $careerStudentTotal,
        ]);
    }

    public function showClass(Request $request, SchoolClass $class)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $class->school_id !== $school->id) {
            abort(403);
        }

        $teacher = $this->getOrCreateTeacherRecord($user, $school);
        $teacherIds = array_values(array_unique(array_filter([
            $user->id,
            $teacher?->id,
            $teacher?->user_id,
        ])));

        $isAuthorized = $user->hasRole(['super-admin', 'school-admin'])
            || in_array($class->teacher_id, $teacherIds)
            || Curriculum::where('school_id', $school->id)->where('class_id', $class->id)->whereIn('teacher_id', $teacherIds)->exists()
            || TimetableSlot::where('school_class_id', $class->id)->whereIn('teacher_id', $teacherIds)->exists()
            || SchemeOfWork::where('school_id', $school->id)->where('school_class_id', $class->id)->whereIn('teacher_id', $teacherIds)->exists();

        if (! $isAuthorized && ! $user->hasRole('teacher')) {
            abort(403, 'You are not assigned to this class.');
        }

        $classVariants = $this->getClassNameVariations($class->name);

        $studentsQuery = Student::where('school_id', $school->id)
            ->where(function ($q) use ($class, $classVariants) {
                $q->whereIn('class_name', $classVariants)
                  ->orWhereHas('enrollments', function ($eq) use ($class, $classVariants) {
                      $eq->where('class_id', $class->id)
                         ->orWhereIn('class_name', $classVariants);
                  });
            });

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->with('enrollments')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $teacherCurricula = Curriculum::where('school_id', $school->id)
            ->where('class_id', $class->id)
            ->whereIn('teacher_id', $teacherIds)
            ->with('subject')
            ->get();

        return view('portal.teacher.class-roster', [
            'school' => $school,
            'user' => $user,
            'teacher' => $teacher,
            'class' => $class,
            'students' => $students,
            'teacherCurricula' => $teacherCurricula,
        ]);
    }

    public function profile()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['teacher', 'super-admin'])) {
            abort(403);
        }

        return view('portal.teacher.profile', [
            'school' => $school,
            'user' => $user,
        ]);
    }

    protected function getOrCreateTeacherRecord($user, $school): ?Teacher
    {
        if (! $school || ! $user) {
            return null;
        }

        $teacher = Teacher::where('school_id', $school->id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', $user->email);
            })
            ->first();

        if ($teacher) {
            if (! $teacher->user_id) {
                $teacher->update(['user_id' => $user->id]);
            }
            return $teacher;
        }

        // Secondary fallback: match by name parts if created without matching email
        $nameParts = array_filter(explode(' ', trim($user->name)));
        if (! empty($nameParts)) {
            $teacher = Teacher::where('school_id', $school->id)
                ->where(function ($q) use ($nameParts) {
                    foreach ($nameParts as $part) {
                        if (strlen($part) >= 3) {
                            $q->orWhere('last_name', 'like', "%{$part}%")
                              ->orWhere('first_name', 'like', "%{$part}%");
                        }
                    }
                })
                ->first();

            if ($teacher) {
                if (! $teacher->user_id) {
                    $teacher->update(['user_id' => $user->id]);
                }
                return $teacher;
            }
        }

        if ($user->hasRole('teacher')) {
            $nameParts = explode(' ', trim($user->name), 2);
            return Teacher::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'employee_id' => 'TCH-' . strtoupper(Str::random(6)),
                'first_name' => $nameParts[0] ?? 'Teacher',
                'last_name' => $nameParts[1] ?? '',
                'email' => $user->email,
                'status' => true,
            ]);
        }

        return null;
    }

    protected function getClassNameVariations(string $name): array
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return [];
        }

        $variations = [
            $trimmed,
            preg_replace('/\s+/', ' ', $trimmed),
            str_replace(' ', '', $trimmed),
        ];

        if (preg_match('/^(?:Form|Grade)\s*(\d+)\s*(.*)$/i', $trimmed, $matches)) {
            $gradeNum = $matches[1];
            $stream = trim($matches[2]);

            if ($stream !== '') {
                $variations[] = "Form {$gradeNum} {$stream}";
                $variations[] = "Form {$gradeNum}{$stream}";
                $variations[] = "Form{$gradeNum}{$stream}";
                $variations[] = "{$gradeNum}{$stream}";
                $variations[] = "{$gradeNum} {$stream}";
            } else {
                $variations[] = "Form {$gradeNum}";
                $variations[] = "Form{$gradeNum}";
                $variations[] = "Grade {$gradeNum}";
                $variations[] = "{$gradeNum}";
            }
        } elseif (preg_match('/^(\d+)\s*([A-Za-z].*)$/', $trimmed, $matches)) {
            $gradeNum = $matches[1];
            $stream = trim($matches[2]);
            $variations[] = "Form {$gradeNum} {$stream}";
            $variations[] = "Form {$gradeNum}{$stream}";
            $variations[] = "Form{$gradeNum}{$stream}";
        }

        return array_values(array_unique(array_filter(array_map('trim', $variations))));
    }
}

