<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareerPath;
use App\Models\CareerGuidanceAssessment;
use App\Models\StudentCareerInterest;
use App\Models\Student;
use App\Models\Subject;
use App\Rules\TenantExists;
use App\Services\CareerGuidanceEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CareerGuidanceController extends Controller
{
    public function index()
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $assessments = CareerGuidanceAssessment::where('school_id', $school->id)
            ->with('student', 'generatedBy')
            ->latest()
            ->paginate(20);

        $pathCount = CareerPath::where('school_id', $school->id)->count();
        $assessedCount = CareerGuidanceAssessment::where('school_id', $school->id)
            ->where('status', 'published')->count();
        $recentCount = CareerGuidanceAssessment::where('school_id', $school->id)
            ->where('created_at', '>=', now()->subDays(30))->count();

        return view('admin.career-guidance.index', compact(
            'school', 'assessments', 'pathCount', 'assessedCount', 'recentCount'
        ));
    }

    public function assess(Request $request, CareerGuidanceEngine $engine)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'student_id' => ['required', TenantExists::make('students')],
        ]);

        $student = Student::where('school_id', $school->id)->findOrFail($validated['student_id']);

        // Check existing assessment today
        $existing = CareerGuidanceAssessment::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', today())
            ->first();

        if ($existing) {
            return back()->with('info', 'Assessment already generated today for this student.');
        }

        $assessment = $engine->assess($student, Auth::id());

        return redirect()->route('admin.career-guidance.show', $assessment)
            ->with('success', 'Career assessment generated for ' . $student->first_name);
    }

    public function show(CareerGuidanceAssessment $assessment)
    {
        $school = Auth::user()->school;
        if (!$school || $assessment->school_id !== $school->id) abort(403);

        $assessment->load(['student.user', 'generatedBy']);

        return view('admin.career-guidance.show', compact('school', 'assessment'));
    }

    // --- Career Paths ---

    public function paths()
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $paths = CareerPath::where('school_id', $school->id)->latest()->paginate(20);
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.career-guidance.paths', compact('school', 'paths', 'subjects'));
    }

    public function storePath(Request $request)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'typical_subjects' => 'nullable|string',
            'education_level' => 'nullable|string|max:255',
            'skills' => 'nullable|string',
            'outlook' => 'nullable|string',
            'subject_requirements' => 'nullable|array',
            'subject_requirements.*.subject_id' => ['required', TenantExists::make('subjects')],
            'subject_requirements.*.subject_name' => 'required|string',
            'subject_requirements.*.min_score' => 'required|numeric|min:0|max:100',
        ]);

        CareerPath::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . $school->id,
            'description' => $validated['description'] ?? null,
            'typical_subjects' => $validated['typical_subjects'] ?? null,
            'education_level' => $validated['education_level'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'outlook' => $validated['outlook'] ?? null,
            'subject_requirements' => $validated['subject_requirements'] ?? [],
        ]);

        return redirect()->route('admin.career-guidance.paths')
            ->with('success', 'Career path created.');
    }

    public function updatePath(Request $request, CareerPath $path)
    {
        $school = Auth::user()->school;
        if (!$school || $path->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'typical_subjects' => 'nullable|string',
            'education_level' => 'nullable|string|max:255',
            'skills' => 'nullable|string',
            'outlook' => 'nullable|string',
            'is_active' => 'boolean',
            'subject_requirements' => 'nullable|array',
            'subject_requirements.*.subject_id' => ['required', TenantExists::make('subjects')],
            'subject_requirements.*.subject_name' => 'required|string',
            'subject_requirements.*.min_score' => 'required|numeric|min:0|max:100',
        ]);

        $path->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'typical_subjects' => $validated['typical_subjects'] ?? null,
            'education_level' => $validated['education_level'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'outlook' => $validated['outlook'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'subject_requirements' => $validated['subject_requirements'] ?? [],
        ]);

        return redirect()->route('admin.career-guidance.paths')
            ->with('success', 'Career path updated.');
    }

    public function destroyPath(CareerPath $path)
    {
        $school = Auth::user()->school;
        if (!$school || $path->school_id !== $school->id) abort(403);

        $path->delete();
        return back()->with('success', 'Career path deleted.');
    }

    // --- Student Interests ---

    public function interests()
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $interests = StudentCareerInterest::where('school_id', $school->id)
            ->with(['student', 'careerPath'])
            ->latest()
            ->paginate(20);

        return view('admin.career-guidance.interests', compact('school', 'interests'));
    }

    // --- Students without assessments ---

    public function students()
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $assessedIds = CareerGuidanceAssessment::where('school_id', $school->id)
            ->pluck('student_id');

        $students = Student::where('school_id', $school->id)
            ->whereNotIn('id', $assessedIds)
            ->with('currentEnrollment.class')
            ->latest()
            ->paginate(50);

        $assessedStudents = Student::whereIn('id', $assessedIds)->count();

        return view('admin.career-guidance.students', compact('school', 'students', 'assessedStudents'));
    }
}
