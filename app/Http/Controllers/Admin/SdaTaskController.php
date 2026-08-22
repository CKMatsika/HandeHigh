<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaResolution;
use App\Models\SdaTask;
use App\Models\User;
use App\Rules\TenantExists;
use Illuminate\Http\Request;

class SdaTaskController extends Controller
{
    /**
     * Display a listing of tasks.
     */
    public function index()
    {
        $tasks = SdaTask::with(['committee', 'assignee', 'assigner', 'resolution'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('admin.sda.tasks.index', compact('tasks'));
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        $committees = SdaCommittee::active()->get();
        $resolutions = SdaResolution::passed()->get();
        $users = User::all();
        return view('admin.sda.tasks.create', compact('committees', 'resolutions', 'users'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sda_committee_id' => 'required|exists:sda_committees,id',
            'assigned_to' => ['required', TenantExists::make('users')],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date|after:today',
            'sda_resolution_id' => 'nullable|exists:sda_resolutions,id',
        ]);

        $validated['assigned_by'] = auth()->id();
        $validated['status'] = 'pending';

        $task = SdaTask::create($validated);

        return redirect()
            ->route('admin.sda.tasks.show', $task)
            ->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified task.
     */
    public function show(SdaTask $task)
    {
        $task->load(['committee', 'assignee', 'assigner', 'resolution']);
        return view('admin.sda.tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the task.
     */
    public function edit(SdaTask $task)
    {
        if ($task->isCompleted() || $task->status === 'cancelled') {
            return redirect()
                ->route('admin.sda.tasks.show', $task)
                ->with('error', 'This task cannot be edited.');
        }

        $committees = SdaCommittee::active()->get();
        $resolutions = SdaResolution::passed()->get();
        $users = User::all();
        return view('admin.sda.tasks.edit', compact('task', 'committees', 'resolutions', 'users'));
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, SdaTask $task)
    {
        if ($task->isCompleted() || $task->status === 'cancelled') {
            return redirect()
                ->route('admin.sda.tasks.show', $task)
                ->with('error', 'This task cannot be edited.');
        }

        $validated = $request->validate([
            'assigned_to' => ['required', TenantExists::make('users')],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date|after:today',
            'sda_resolution_id' => 'nullable|exists:sda_resolutions,id',
        ]);

        $task->update($validated);

        return redirect()
            ->route('admin.sda.tasks.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Start the task.
     */
    public function start(SdaTask $task)
    {
        if ($task->status !== 'pending') {
            return redirect()
                ->route('admin.sda.tasks.show', $task)
                ->with('error', 'This task cannot be started.');
        }

        $task->start();

        return redirect()
            ->route('admin.sda.tasks.show', $task)
            ->with('success', 'Task started successfully.');
    }

    /**
     * Complete the task.
     */
    public function complete(Request $request, SdaTask $task)
    {
        if (!$task->canBeCompleted()) {
            return redirect()
                ->route('admin.sda.tasks.show', $task)
                ->with('error', 'This task cannot be completed.');
        }

        $validated = $request->validate([
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        $task->complete($validated['completion_notes']);

        return redirect()
            ->route('admin.sda.tasks.show', $task)
            ->with('success', 'Task completed successfully.');
    }

    /**
     * Cancel the task.
     */
    public function cancel(Request $request, SdaTask $task)
    {
        if ($task->isCompleted()) {
            return redirect()
                ->route('admin.sda.tasks.show', $task)
                ->with('error', 'This task cannot be cancelled.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $task->cancel($validated['cancellation_reason']);

        return redirect()
            ->route('admin.sda.tasks.show', $task)
            ->with('success', 'Task cancelled successfully.');
    }

    /**
     * Reassign the task.
     */
    public function reassign(Request $request, SdaTask $task)
    {
        if ($task->isCompleted() || $task->status === 'cancelled') {
            return redirect()
                ->route('admin.sda.tasks.show', $task)
                ->with('error', 'This task cannot be reassigned.');
        }

        $validated = $request->validate([
            'assigned_to' => ['required', TenantExists::make('users'), 'different:' . $task->assigned_to],
            'reassignment_reason' => 'required|string|max:500',
        ]);

        $task->update([
            'assigned_to' => $validated['assigned_to'],
            'assigned_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.sda.tasks.show', $task)
            ->with('success', 'Task reassigned successfully.');
    }

    /**
     * Display tasks for a specific committee.
     */
    public function committeeTasks(SdaCommittee $committee)
    {
        $tasks = $committee->tasks()
            ->with(['assignee', 'assigner', 'resolution'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('admin.sda.tasks.committee', compact('committee', 'tasks'));
    }

    /**
     * Display tasks assigned to a specific user.
     */
    public function userTasks(User $user)
    {
        $tasks = $user->assignedTasks()
            ->with(['committee', 'assigner', 'resolution'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('admin.sda.tasks.user', compact('user', 'tasks'));
    }

    /**
     * Display overdue tasks.
     */
    public function overdue()
    {
        $tasks = SdaTask::overdue()
            ->with(['committee', 'assignee', 'assigner', 'resolution'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('admin.sda.tasks.overdue', compact('tasks'));
    }

    /**
     * Display high priority tasks.
     */
    public function highPriority()
    {
        $tasks = SdaTask::highPriority()
            ->with(['committee', 'assignee', 'assigner', 'resolution'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('admin.sda.tasks.high_priority', compact('tasks'));
    }

    /**
     * Remove the specified task.
     */
    public function destroy(SdaTask $task)
    {
        if ($task->isCompleted()) {
            return redirect()
                ->route('admin.sda.tasks.show', $task)
                ->with('error', 'Completed tasks cannot be deleted.');
        }

        $task->delete();

        return redirect()
            ->route('admin.sda.tasks.index')
            ->with('success', 'Task deleted successfully.');
    }
}
