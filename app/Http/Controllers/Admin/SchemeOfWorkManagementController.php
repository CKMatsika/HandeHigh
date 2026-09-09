<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchemeOfWork;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchemeOfWorkManagementController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $teachers = Teacher::where('school_id', $school->id)
            ->active()
            ->whereHas('user', function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->where('name', 'teacher');
                });
            })
            ->withCount(['schemesOfWork as total_schemes' => function ($q) use ($school) {
                $q->where('school_id', $school->id);
            }])
            ->withCount(['schemesOfWork as submitted_schemes' => function ($q) use ($school) {
                $q->where('school_id', $school->id)->where('status', 'submitted');
            }])
            ->withCount(['schemesOfWork as approved_schemes' => function ($q) use ($school) {
                $q->where('school_id', $school->id)->where('status', 'approved');
            }])
            ->get();

        return view('admin.schemes-of-work.index', [
            'school' => $school,
            'teachers' => $teachers,
        ]);
    }

    public function teacherSchemes(Teacher $teacher)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $teacher->school_id !== $school->id) {
            abort(403);
        }

        $schemes = SchemeOfWork::with(['subject', 'schoolClass', 'items'])
            ->where('school_id', $school->id)
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->paginate(15);

        return view('admin.schemes-of-work.teacher-schemes', [
            'school' => $school,
            'teacher' => $teacher,
            'schemes' => $schemes,
        ]);
    }

    public function show(SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        $schemeOfWork->load(['subject', 'schoolClass', 'items', 'teacher', 'reviewer']);

        $activeTimetable = Timetable::where('school_id', $school->id)
            ->where('academic_year', $schemeOfWork->academic_year)
            ->where('term', $schemeOfWork->term)
            ->where('status', 'published')
            ->first();

        $timetableSlots = collect();
        if ($activeTimetable) {
            $timetableSlots = TimetableSlot::where('timetable_id', $activeTimetable->id)
                ->where('teacher_id', $schemeOfWork->teacher_id)
                ->where('subject_id', $schemeOfWork->subject_id)
                ->with(['schoolClass', 'subject', 'room'])
                ->get()
                ->groupBy('day_of_week');
        }

        return view('admin.schemes-of-work.show', [
            'school' => $school,
            'scheme' => $schemeOfWork,
            'timetableSlots' => $timetableSlots,
            'activeTimetable' => $activeTimetable,
        ]);
    }

    public function approve(Request $request, SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        if ($schemeOfWork->status !== 'submitted') {
            abort(403, 'Only submitted schemes can be approved.');
        }

        $schemeOfWork->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'review_notes' => $request->input('review_notes'),
        ]);

        return redirect()->back()
            ->with('status', 'Scheme of work approved.');
    }

    public function reject(Request $request, SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        if ($schemeOfWork->status !== 'submitted') {
            abort(403, 'Only submitted schemes can be rejected.');
        }

        $request->validate([
            'review_notes' => 'required|string',
        ]);

        $schemeOfWork->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'review_notes' => $request->input('review_notes'),
        ]);

        return redirect()->back()
            ->with('status', 'Scheme of work rejected with feedback.');
    }

    public function print(SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        $schemeOfWork->load(['subject', 'schoolClass', 'items', 'teacher', 'reviewer']);

        return view('portal.teacher.schemes-of-work.print', [
            'school' => $school,
            'user' => $user,
            'scheme' => $schemeOfWork,
        ]);
    }
}
