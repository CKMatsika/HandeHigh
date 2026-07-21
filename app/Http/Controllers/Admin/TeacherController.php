<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $school = $request->user()?->school;

        if (! $school) {
            abort(403);
        }

        $teachers = User::where('school_id', $school->id)
            ->whereHas('roles', function($query) {
                $query->where('name', 'teacher');
            })
            ->with('school')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.teachers.index', [
            'teachers' => $teachers,
            'school' => $school,
        ]);
    }

    public function create(Request $request)
    {
        $school = $request->user()?->school;

        if (! $school) {
            abort(403);
        }

        return view('admin.teachers.create', [
            'school' => $school,
        ]);
    }

    public function store(Request $request)
    {
        $school = $request->user()?->school;

        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_date' => ['nullable', 'date'],
            'password' => ['required', Password::defaults()],
        ]);

        $teacher = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'school_id' => $school->id,
            'password' => Hash::make($validated['password']),
        ]);

        // Assign teacher role
        $teacher->assignRole('teacher');

        // Store teacher-specific data in metadata or separate table
        $teacher->metadata = [
            'qualification' => $validated['qualification'] ?? null,
            'specialization' => $validated['specialization'] ?? null,
            'employment_date' => $validated['employment_date'] ?? null,
        ];
        $teacher->save();

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Teacher created successfully.');
    }

    public function show(Request $request, User $teacher)
    {
        $school = $request->user()?->school;

        if (! $school || $teacher->school_id !== $school->id || !$teacher->hasRole('teacher')) {
            abort(403);
        }

        $teacherClasses = $school->classes()->where('teacher_id', $teacher->id)->get();
        $curricula = $school->curricula()->where('teacher_id', $teacher->id)->with(['class', 'subject'])->get();

        return view('admin.teachers.show', [
            'teacher' => $teacher,
            'school' => $school,
            'teacherClasses' => $teacherClasses,
            'curricula' => $curricula,
        ]);
    }

    public function edit(Request $request, User $teacher)
    {
        $school = $request->user()?->school;

        if (! $school || $teacher->school_id !== $school->id || !$teacher->hasRole('teacher')) {
            abort(403);
        }

        return view('admin.teachers.edit', [
            'teacher' => $teacher,
            'school' => $school,
        ]);
    }

    public function update(Request $request, User $teacher)
    {
        $school = $request->user()?->school;

        if (! $school || $teacher->school_id !== $school->id || !$teacher->hasRole('teacher')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $teacher->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_date' => ['nullable', 'date'],
            'password' => ['nullable', Password::defaults()],
        ]);

        $teacher->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $teacher->password = Hash::make($validated['password']);
            $teacher->save();
        }

        // Update teacher-specific metadata
        $metadata = $teacher->metadata ?? [];
        $metadata['qualification'] = $validated['qualification'] ?? null;
        $metadata['specialization'] = $validated['specialization'] ?? null;
        $metadata['employment_date'] = $validated['employment_date'] ?? null;
        $teacher->metadata = $metadata;
        $teacher->save();

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Request $request, User $teacher)
    {
        $school = $request->user()?->school;

        if (! $school || $teacher->school_id !== $school->id || !$teacher->hasRole('teacher')) {
            abort(403);
        }

        // Check if teacher has assigned classes or curricula
        $hasClasses = $school->classes()->where('teacher_id', $teacher->id)->exists();
        $hasCurricula = $school->curricula()->where('teacher_id', $teacher->id)->exists();

        if ($hasClasses || $hasCurricula) {
            return redirect()
                ->route('admin.teachers.index')
                ->with('error', 'Cannot delete teacher. Teacher has assigned classes or subjects.');
        }

        $teacher->delete();

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
    }
}
