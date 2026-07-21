<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $subjects = Subject::where('school_id', $school->id)
            ->orderBy('is_core', 'desc')
            ->orderBy('code')
            ->paginate(20);

        return view('admin.subjects.index', [
            'school' => $school,
            'subjects' => $subjects,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        return view('admin.subjects.create', [
            'school' => $school,
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
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code,NULL,id,school_id,'.$school->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_core' => ['boolean'],
        ]);

        $school->subjects()->create($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $subject->school_id !== $school->id) {
            abort(403);
        }

        $subject->load(['curricula.class', 'curricula.teacher']);

        return view('admin.subjects.show', [
            'school' => $school,
            'subject' => $subject,
        ]);
    }

    public function edit(Subject $subject)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $subject->school_id !== $school->id) {
            abort(403);
        }

        return view('admin.subjects.edit', [
            'school' => $school,
            'subject' => $subject,
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $subject->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code,'.$subject->id.',id,school_id,'.$school->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_core' => ['boolean'],
        ]);

        $subject->update($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $subject->school_id !== $school->id) {
            abort(403);
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }
}
