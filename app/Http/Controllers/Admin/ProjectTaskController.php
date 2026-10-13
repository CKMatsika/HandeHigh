<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Project $project)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school || $project->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phase_milestone' => ['required', 'string', 'max:100'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        ProjectTask::create([
            'school_id' => $school->id,
            'project_id' => $project->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'phase_milestone' => $validated['phase_milestone'],
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'created_by' => $user->id,
            'due_date' => $validated['due_date'] ?? null,
            'progress_percent' => $validated['progress_percent'] ?? 0,
            'status' => ($validated['progress_percent'] ?? 0) >= 100 ? 'completed' : 'pending',
            'completed_at' => ($validated['progress_percent'] ?? 0) >= 100 ? now() : null,
        ]);

        return redirect()->route('admin.projects.show', [$project, 'tab' => 'tasks'])
            ->with('status', 'Implementation task added successfully.');
    }

    public function toggle(Project $project, ProjectTask $task)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school || $project->school_id !== $school->id || $task->project_id !== $project->id) {
            abort(403);
        }

        $isCompleted = $task->status === 'completed';
        $newStatus = $isCompleted ? 'in_progress' : 'completed';

        $task->update([
            'status' => $newStatus,
            'progress_percent' => $newStatus === 'completed' ? 100 : 50,
            'completed_at' => $newStatus === 'completed' ? now() : null,
        ]);

        return back()->with('status', "Task marked as {$newStatus}.");
    }

    public function update(Request $request, Project $project, ProjectTask $task)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school || $project->school_id !== $school->id || $task->project_id !== $project->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phase_milestone' => ['required', 'string', 'max:100'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['required', 'in:pending,in_progress,completed,blocked'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
            $validated['progress_percent'] = 100;
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        $task->update($validated);

        return redirect()->route('admin.projects.show', [$project, 'tab' => 'tasks'])
            ->with('status', 'Task updated successfully.');
    }

    public function destroy(Project $project, ProjectTask $task)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school || $project->school_id !== $school->id || $task->project_id !== $project->id) {
            abort(403);
        }

        $task->delete();

        return redirect()->route('admin.projects.show', [$project, 'tab' => 'tasks'])
            ->with('status', 'Task removed.');
    }
}
