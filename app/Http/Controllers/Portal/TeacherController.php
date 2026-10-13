<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CareerGuidanceAssessment;
use App\Models\Project;
use App\Models\SchemeOfWork;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['teacher', 'super-admin'])) {
            abort(403);
        }

        $managedClasses = collect();
        $teachingClasses = collect();
        $teacherClasses = collect();
        $curricula = collect();
        $students = collect();
        $assignments = collect();
        $attendance = collect();
        $announcements = collect();
        $schedule = collect();
        $teacherProjects = collect();
        $careerAssessmentsCount = 0;
        $careerAssessedStudents = 0;
        $careerStudentTotal = 0;
        
        if ($user->hasRole('teacher')) {
            // 1. Classes managed administratively (Class Teacher - attendance register, roll call)
            $managedClasses = $school->classes()
                ->where('teacher_id', $user->id)
                ->withCount('enrollments')
                ->get();
            $teacherClasses = $managedClasses;

            // 2. Curricula and subjects taught by this teacher
            $curricula = $school->curricula()
                ->where('teacher_id', $user->id)
                ->with(['class.enrollments', 'subject'])
                ->get();
            
            // 3. Classes where this teacher teaches subjects
            $teachingClassIds = $curricula->pluck('class_id')->unique()->filter()->values();
            $teachingClasses = $school->classes()
                ->whereIn('id', $teachingClassIds)
                ->withCount('enrollments')
                ->get();

            // All associated class IDs & names for this teacher
            $allClassIds = $managedClasses->pluck('id')->merge($teachingClassIds)->unique()->filter()->values();
            $allClassNames = $managedClasses->pluck('name')->merge($teachingClasses->pluck('name'))->unique()->filter()->values()->all();
            
            // Get students across managed classes and teaching classes
            $studentsQuery = $school->students();
            if ($allClassIds->isNotEmpty() || !empty($allClassNames)) {
                $studentsQuery->where(function ($query) use ($allClassIds, $allClassNames) {
                    $query->whereHas('enrollments', function ($eq) use ($allClassIds) {
                        $eq->whereIn('class_id', $allClassIds);
                    });
                    if (!empty($allClassNames)) {
                        $query->orWhereIn('class_name', $allClassNames);
                    }
                });
            } else {
                $studentsQuery->whereRaw('1 = 0');
            }
            $students = (clone $studentsQuery)->limit(12)->get();
            
            // Get teacher record and schemes of work
            $teacher = Teacher::where('school_id', $school->id)->where('user_id', $user->id)->first();
            $schemesCount = 0;
            $draftSchemesCount = 0;
            $submittedSchemesCount = 0;
            $approvedSchemesCount = 0;
            $recentSchemes = collect();
            
            if ($teacher) {
                $schemesQuery = SchemeOfWork::where('school_id', $school->id)->where('teacher_id', $teacher->id);
                $schemesCount = $schemesQuery->count();
                $draftSchemesCount = (clone $schemesQuery)->where('status', 'draft')->count();
                $submittedSchemesCount = (clone $schemesQuery)->where('status', 'submitted')->count();
                $approvedSchemesCount = (clone $schemesQuery)->where('status', 'approved')->count();
                $recentSchemes = SchemeOfWork::with(['subject', 'schoolClass'])
                    ->where('school_id', $school->id)
                    ->where('teacher_id', $teacher->id)
                    ->latest()
                    ->limit(5)
                    ->get();
            }
            
            // Get projects where teacher is involved
            $teacherProjects = Project::where('school_id', $school->id)
                ->where(function($query) use ($user) {
                    $query->where('project_manager_id', $user->id)
                          ->orWhere('team_lead_id', $user->id)
                          ->orWhereJsonContains('team_members', $user->id);
                })->get();
            
            // Sample assignments
            $assignments = collect([
                (object)['title' => 'Math Quiz Chapter 5', 'class' => 'Form 1A', 'subject' => 'Mathematics', 'due_date' => now()->addDays(3), 'submissions' => 15, 'total' => 25],
                (object)['title' => 'Science Lab Report', 'class' => 'Form 2B', 'subject' => 'Science', 'due_date' => now()->addDays(5), 'submissions' => 8, 'total' => 20],
                (object)['title' => 'English Essay', 'class' => 'Form 1A', 'subject' => 'English', 'due_date' => now()->subDays(1), 'submissions' => 23, 'total' => 25],
            ]);
            
            // Sample attendance records for today
            $attendance = collect([
                (object)['class' => 'Form 1A', 'date' => now(), 'present' => 23, 'total' => 25, 'rate' => 92],
                (object)['class' => 'Form 2B', 'date' => now(), 'present' => 18, 'total' => 20, 'rate' => 90],
            ]);
            
            // Sample announcements
            $announcements = collect([
                (object)['title' => 'Staff Meeting Tomorrow', 'message' => 'Important staff meeting at 3 PM in conference room', 'date' => now()->subHours(2), 'priority' => 'high'],
                (object)['title' => 'New Curriculum Update', 'message' => 'Updated curriculum guidelines are now available', 'date' => now()->subDays(1), 'priority' => 'medium'],
            ]);
            
            // Career guidance stats for teacher's students
            $allClassStudentIds = (clone $studentsQuery)->pluck('id');
            $careerStudentTotal = $allClassStudentIds->count();
            $careerAssessmentsCount = CareerGuidanceAssessment::whereIn('student_id', $allClassStudentIds)
                ->where('status', 'published')->count();
            $careerAssessedStudents = $careerAssessmentsCount;
            
            // Sample daily schedule
            $schedule = collect([
                (object)['time' => '8:00-9:00', 'class' => 'Form 1A', 'subject' => 'Mathematics', 'room' => 'Room 101'],
                (object)['time' => '9:00-10:00', 'class' => 'Form 2B', 'subject' => 'Science', 'room' => 'Lab 2'],
                (object)['time' => '10:15-11:15', 'class' => 'Form 1A', 'subject' => 'Mathematics', 'room' => 'Room 101'],
                (object)['time' => '11:15-12:15', 'class' => 'Form 2B', 'subject' => 'Science', 'room' => 'Lab 2'],
            ]);
        } else {
            // For super admin, show sample data
            $managedClasses = $school->classes()->limit(1)->get();
            $teachingClasses = $school->classes()->limit(3)->get();
            $teacherClasses = $managedClasses;
            $curricula = $school->curricula()->with(['class', 'subject'])->limit(5)->get();
            $students = $school->students()->limit(5)->get();
            $teacherProjects = Project::where('school_id', $school->id)->limit(5)->get();
            
            $schemesCount = SchemeOfWork::where('school_id', $school->id)->count();
            $draftSchemesCount = SchemeOfWork::where('school_id', $school->id)->where('status', 'draft')->count();
            $submittedSchemesCount = SchemeOfWork::where('school_id', $school->id)->where('status', 'submitted')->count();
            $approvedSchemesCount = SchemeOfWork::where('school_id', $school->id)->where('status', 'approved')->count();
            $recentSchemes = SchemeOfWork::with(['subject', 'schoolClass'])
                ->where('school_id', $school->id)
                ->latest()
                ->limit(5)
                ->get();
            
            $assignments = collect([
                (object)['title' => 'Sample Assignment', 'class' => 'Sample Class', 'subject' => 'Sample Subject', 'due_date' => now()->addDays(3), 'submissions' => 5, 'total' => 10],
            ]);
            
            $attendance = collect([
                (object)['class' => 'Sample Class', 'date' => now(), 'present' => 15, 'total' => 20, 'rate' => 75],
            ]);
            
            $announcements = collect([
                (object)['title' => 'Sample Announcement', 'message' => 'This is a sample announcement for testing', 'date' => now(), 'priority' => 'medium'],
            ]);
            
            $schedule = collect([
                (object)['time' => '8:00-9:00', 'class' => 'Sample Class', 'subject' => 'Sample Subject', 'room' => 'Room 101'],
            ]);
        }

        return view('portal.teacher.dashboard', [
            'school' => $school,
            'user' => $user,
            'managedClasses' => $managedClasses,
            'teacherClasses' => $teacherClasses,
            'teachingClasses' => $teachingClasses,
            'curricula' => $curricula,
            'students' => $students,
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
}
