<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolClassController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $classes = SchoolClass::where('school_id', $school->id)
            ->with('teacher')
            ->orderBy('academic_year', 'desc')
            ->orderBy('grade')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.classes.index', [
            'school' => $school,
            'classes' => $classes,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $teachers = $school->users()->whereHas('roles', function ($q) {
            $q->where('name', 'teacher');
        })->get();

        return view('admin.classes.create', [
            'school' => $school,
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
            'name' => ['required', 'string', 'max:255'],
            'grade' => ['required', 'string', 'max:50'],
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:50'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        $school->classes()->create($validated);

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class created successfully.');
    }

    public function show(SchoolClass $class)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $class->school_id !== $school->id) {
            abort(403);
        }

        $class->load(['teacher', 'curricula.subject', 'enrollments.student']);

        return view('admin.classes.show', [
            'school' => $school,
            'class' => $class,
        ]);
    }

    public function edit(SchoolClass $class)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $class->school_id !== $school->id) {
            abort(403);
        }

        $teachers = $school->users()->whereHas('roles', function ($q) {
            $q->where('name', 'teacher');
        })->get();

        return view('admin.classes.edit', [
            'school' => $school,
            'class' => $class,
            'teachers' => $teachers,
        ]);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $class->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'grade' => ['required', 'string', 'max:50'],
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:50'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        $class->update($validated);

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class updated successfully.');
    }

    public function destroy(SchoolClass $class)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $class->school_id !== $school->id) {
            abort(403);
        }

        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class deleted successfully.');
    }
}
