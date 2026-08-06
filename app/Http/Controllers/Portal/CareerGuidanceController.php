<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CareerGuidanceAssessment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CareerGuidanceController extends Controller
{
    public function index()
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $teacher = Auth::user();

        $students = Student::where('school_id', $school->id)
            ->whereHas('currentEnrollment.class', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->with(['currentEnrollment.class', 'careerAssessment'])
            ->get();

        // Also get students via curriculum (subject teacher)
        $curriculaStudents = Student::where('school_id', $school->id)
            ->whereHas('results', function ($q) use ($teacher, $school) {
                $q->whereHas('subject.curricula', function ($q2) use ($teacher) {
                    $q2->where('teacher_id', $teacher->id);
                });
            })
            ->with(['currentEnrollment.class', 'careerAssessment'])
            ->get();

        $allStudents = $students->merge($curriculaStudents)->unique('id');

        return view('portal.career-guidance.index', compact('school', 'allStudents'));
    }

    public function show(CareerGuidanceAssessment $assessment)
    {
        $school = Auth::user()->school;
        if (!$school || $assessment->school_id !== $school->id) abort(403);

        $assessment->load(['student', 'generatedBy']);

        return view('portal.career-guidance.show', compact('school', 'assessment'));
    }
}
