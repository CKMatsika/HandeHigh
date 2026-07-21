<?php

namespace App\Http\Controllers\Sda;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaResolution;
use App\Models\SdaTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SdaTaskController extends Controller
{
    /**
     * Display a listing of tasks.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get tasks assigned to the user from their committees
        $tasks = SdaTask::where('assigned_to', $user->id)
            ->whereHas('committee', function($query) use ($user) {
                $query->whereHas('members', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->with(['committee', 'assigner', 'resolution'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('sda.tasks.index', compact('tasks'));
    }

    /**
     * Display the specified task.
     */
    public function show(SdaTask $task)
    {
        $user = Auth::user();
        
        // Check if task is assigned to the user
        if ($task->assigned_to !== $user->id) {
            abort(403, 'You are not authorized to view this task.');
        }

        $task->load(['committee', 'assigner', 'resolution']);

        return view('sda.tasks.show', compact('task'));
    }

    /**
     * Show the form for creating a new task (for committee leaders).
     */
    public function create()
    {
        $user = Auth::user();
        
        // Check if user is a committee leader
        $committees = SdaCommittee::whereHas('members', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->whereIn('role_id', function($q) {
                      $q->select('id')->from('sda_roles')
                        ->whereIn('name', ['Chairman', 'Secretary', 'Treasurer']);
                  });
        })->active()->get();

        if ($committees->isEmpty()) {
            return redirect()->route('sda.dashboard')
                ->with('error', 'Only committee leaders can assign tasks.');
        }

        $resolutions = SdaResolution::passed()
            ->whereHas('meeting', function($query) use ($user) {
                $query->whereHas('committee', function($q) use ($user) {
                    $q->whereHas('members', function($subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id);
                    });
                });
            })
            ->get();

        $users = User::all();

        return view('sda.tasks.create', compact('committees', 'resolutions', 'users'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'sda_committee_id' => 'required|exists:sda_committees,id',
            'assigned_to' => 'required|exists:users,id|different:assigned_by',
            'sda_resolution_id' => 'nullable|exists:sda_resolutions,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date|after:today',
        ]);

        // Check if user is a committee leader
        $committee = SdaCommittee::findOrFail($validated['sda_committee_id']);
        $canAssign = $committee->members()
            ->where('user_id', $user->id)
            ->whereIn('role_id', function($q) {
                $q->select('id')->from('sda_roles')
                  ->whereIn('name', ['Chairman', 'Secretary', 'Treasurer']);
            })
            ->exists();

        if (!$canAssign) {
            return redirect()->route('sda.dashboard')
                ->with('error', 'You are not authorized to assign tasks for this committee.');
        }

        $validated['assigned_by'] = $user->id;
        $validated['status'] = 'pending';

        $task = SdaTask::create($validated);

        return redirect()
            ->route('sda.tasks.show', $task)
            ->with('success', 'Task assigned successfully.');
    }

    /**
     * Show the form for editing the task.
     */
    public function edit(SdaTask $task)
    {
        $user = Auth::user();
        
        // Check if task is assigned to the user and is editable
        if ($task->assigned_to !== $user->id || $task->isCompleted()) {
            abort(403, 'You are not authorized to edit this task.');
        }

        return view('sda.tasks.edit', compact('task'));
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, SdaTask $task)
    {
        $user = Auth::user();
        
        // Check if task is assigned to the user and is editable
        if ($task->assigned_to !== $user->id || $task->isCompleted()) {
            abort(403, 'You are not authorized to edit this task.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date|after:today',
        ]);

        $task->update($validated);

        return redirect()
            ->route('sda.tasks.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Start the task.
     */
    public function start(SdaTask $task)
    {
        $user = Auth::user();
        
        if ($task->assigned_to !== $user->id || $task->status !== 'pending') {
            abort(403, 'You are not authorized to start this task.');
        }

        $task->start();

        return redirect()
            ->route('sda.tasks.show', $task)
            ->with('success', 'Task started successfully.');
    }

    /**
     * Complete the task.
     */
    public function complete(Request $request, SdaTask $task)
    {
        $user = Auth::user();
        
        if (!$task->canBeCompleted() || $task->assigned_to !== $user->id) {
            abort(403, 'You are not authorized to complete this task.');
        }

        $validated = $request->validate([
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        $task->complete($validated['completion_notes'] ?? null);

        return redirect()
            ->route('sda.tasks.show', $task)
            ->with('success', 'Task completed successfully.');
    }

    /**
     * Cancel the task.
     */
    public function cancel(Request $request, SdaTask $task)
    {
        $user = Auth::user();
        
        if ($task->assigned_to !== $user->id || $task->isCompleted()) {
            abort(403, 'You are not authorized to cancel this task.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:255',
        ]);

        $task->cancel($validated['cancellation_reason']);

        return redirect()
            ->route('sda.tasks.show', $task)
            ->with('success', 'Task cancelled successfully.');
    }
}
