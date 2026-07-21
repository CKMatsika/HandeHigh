<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['student', 'super-admin'])) {
            abort(403);
        }

        $student = null;
        $enrollment = null;
        $invoices = collect();
        $results = collect();
        $assignments = collect();
        $attendance = collect();
        $announcements = collect();
        $timetable = collect();
        
        if ($user->hasRole('student')) {
            $student = $school->students()->where('user_id', $user->id)->first();
            if (! $student) {
                abort(403);
            }
            $enrollment = $student->enrollments()->with('class')->first();
            $invoices = $student->invoices()->orderBy('issued_at', 'desc')->limit(5)->get();
            $results = $student->results()->with('subject')->orderBy('created_at', 'desc')->limit(5)->get();
            
            // Sample assignments (would come from assignments table)
            $assignments = collect([
                (object)['title' => 'Mathematics Homework', 'subject' => 'Mathematics', 'due_date' => now()->addDays(2), 'status' => 'pending'],
                (object)['title' => 'Science Project', 'subject' => 'Science', 'due_date' => now()->addDays(5), 'status' => 'pending'],
                (object)['title' => 'English Essay', 'subject' => 'English', 'due_date' => now()->subDays(1), 'status' => 'submitted'],
            ]);
            
            // Sample attendance data
            $attendance = collect([
                (object)['date' => now()->subDays(1), 'status' => 'present'],
                (object)['date' => now()->subDays(2), 'status' => 'present'],
                (object)['date' => now()->subDays(3), 'status' => 'absent'],
                (object)['date' => now()->subDays(4), 'status' => 'present'],
                (object)['date' => now()->subDays(5), 'status' => 'present'],
            ]);
            
            // Sample announcements
            $announcements = collect([
                (object)['title' => 'School Sports Day', 'message' => 'Annual sports day will be held next Friday', 'date' => now()->subDays(1), 'priority' => 'high'],
                (object)['title' => 'Math Competition', 'message' => 'Inter-school mathematics competition next month', 'date' => now()->subDays(3), 'priority' => 'medium'],
                (object)['title' => 'Library Hours', 'message' => 'Library will remain open until 6 PM during exams', 'date' => now()->subDays(5), 'priority' => 'low'],
            ]);
            
            // Sample timetable
            $timetable = collect([
                (object)['day' => 'Monday', 'periods' => [
                    (object)['time' => '8:00-9:00', 'subject' => 'Mathematics', 'teacher' => 'Mr. Smith'],
                    (object)['time' => '9:00-10:00', 'subject' => 'English', 'teacher' => 'Ms. Johnson'],
                    (object)['time' => '10:15-11:15', 'subject' => 'Science', 'teacher' => 'Dr. Brown'],
                    (object)['time' => '11:15-12:15', 'subject' => 'History', 'teacher' => 'Mr. Davis'],
                ]],
                (object)['day' => 'Tuesday', 'periods' => [
                    (object)['time' => '8:00-9:00', 'subject' => 'Physics', 'teacher' => 'Dr. Wilson'],
                    (object)['time' => '9:00-10:00', 'subject' => 'Chemistry', 'teacher' => 'Ms. Taylor'],
                    (object)['time' => '10:15-11:15', 'subject' => 'Mathematics', 'teacher' => 'Mr. Smith'],
                    (object)['time' => '11:15-12:15', 'subject' => 'Physical Education', 'teacher' => 'Mr. Martinez'],
                ]],
            ]);
        } else {
            // For super admin, show sample data
            $enrollment = null;
            $invoices = $school->invoices()->orderBy('issued_at', 'desc')->limit(5)->get();
            $results = collect(); // Empty for now
            
            // Sample assignments for super admin
            $assignments = collect([
                (object)['title' => 'Sample Assignment', 'subject' => 'Sample Subject', 'due_date' => now()->addDays(3), 'status' => 'pending'],
            ]);
            
            $attendance = collect();
            $announcements = collect([
                (object)['title' => 'Sample Announcement', 'message' => 'This is a sample announcement for testing', 'date' => now(), 'priority' => 'medium'],
            ]);
            $timetable = collect();
        }

        return view('portal.student.dashboard', [
            'school' => $school,
            'user' => $user,
            'student' => $student,
            'enrollment' => $enrollment,
            'invoices' => $invoices,
            'results' => $results,
            'assignments' => $assignments,
            'attendance' => $attendance,
            'announcements' => $announcements,
            'timetable' => $timetable,
        ]);
    }

    public function profile()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['student', 'super-admin'])) {
            abort(403);
        }

        $student = null;
        if ($user->hasRole('student')) {
            $student = $school->students()->where('user_id', $user->id)->first();
            if (! $student) {
                abort(403);
            }
        } else {
            // For super admin, show sample data
            $student = null;
        }

        return view('portal.student.profile', [
            'school' => $school,
            'user' => $user,
            'student' => $student,
        ]);
    }

    public function fees()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['student', 'super-admin'])) {
            abort(403);
        }

        $student = null;
        if ($user->hasRole('student')) {
            $student = $school->students()->where('user_id', $user->id)->first();
            if (! $student) {
                abort(403);
            }
        } else {
            // For super admin, show sample data
            $student = null;
        }

        $invoices = collect();
        if ($student) {
            $invoices = $student->invoices()->with('items')->orderBy('issued_at', 'desc')->paginate(10);
        }

        return view('portal.student.fees', [
            'school' => $school,
            'user' => $user,
            'student' => $student,
            'invoices' => $invoices,
        ]);
    }

    public function results()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['student', 'super-admin'])) {
            abort(403);
        }

        $student = null;
        if ($user->hasRole('student')) {
            $student = $school->students()->where('user_id', $user->id)->first();
            if (! $student) {
                abort(403);
            }
        } else {
            // For super admin, show sample data
            $student = null;
        }

        $results = collect();
        if ($student) {
            $results = $student->results()->with('subject', 'class')->orderBy('created_at', 'desc')->paginate(10);
        }

        return view('portal.student.results', [
            'school' => $school,
            'user' => $user,
            'student' => $student,
            'results' => $results,
        ]);
    }
}
