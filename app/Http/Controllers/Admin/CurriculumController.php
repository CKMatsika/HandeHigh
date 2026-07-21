<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurriculumController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $curricula = Curriculum::where('school_id', $school->id)
            ->with(['class', 'subject', 'teacher'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('term')
            ->orderBy('class.grade')
            ->orderBy('subject.code')
            ->paginate(20);

        return view('admin.curricula.index', [
            'school' => $school,
            'curricula' => $curricula,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $classes = $school->classes()->orderBy('grade')->orderBy('name')->get();
        $subjects = $school->subjects()->orderBy('is_core', 'desc')->orderBy('code')->get();
        $teachers = $school->users()->whereHas('roles', function ($q) {
            $q->where('name', 'teacher');
        })->get();

        return view('admin.curricula.create', [
            'school' => $school,
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:50'],
            'weekly_periods' => ['required', 'integer', 'min:1', 'max:20'],
            'objectives' => ['nullable', 'string'],
            'materials' => ['nullable', 'string'],
        ]);

        $school->curricula()->create($validated);

        return redirect()->route('admin.curricula.index')
            ->with('success', 'Curriculum created successfully.');
    }

    public function show(Curriculum $curriculum)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $curriculum->school_id !== $school->id) {
            abort(403);
        }

        $curriculum->load(['class', 'subject', 'teacher', 'exams']);

        return view('admin.curricula.show', [
            'school' => $school,
            'curriculum' => $curriculum,
        ]);
    }

    public function edit(Curriculum $curriculum)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $curriculum->school_id !== $school->id) {
            abort(403);
        }

        $classes = $school->classes()->orderBy('grade')->orderBy('name')->get();
        $subjects = $school->subjects()->orderBy('is_core', 'desc')->orderBy('code')->get();
        $teachers = $school->users()->whereHas('roles', function ($q) {
            $q->where('name', 'teacher');
        })->get();

        return view('admin.curricula.edit', [
            'school' => $school,
            'curriculum' => $curriculum,
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }

    public function update(Request $request, Curriculum $curriculum)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $curriculum->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:50'],
            'weekly_periods' => ['required', 'integer', 'min:1', 'max:20'],
            'objectives' => ['nullable', 'string'],
            'materials' => ['nullable', 'string'],
        ]);

        $curriculum->update($validated);

        return redirect()->route('admin.curricula.index')
            ->with('success', 'Curriculum updated successfully.');
    }

    public function destroy(Curriculum $curriculum)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $curriculum->school_id !== $school->id) {
            abort(403);
        }

        $curriculum->delete();

        return redirect()->route('admin.curricula.index')
            ->with('success', 'Curriculum deleted successfully.');
    }
}
