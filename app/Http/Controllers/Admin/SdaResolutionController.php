<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaMeeting;
use App\Models\SdaResolution;
use App\Models\SdaTask;
use App\Models\User;
use Illuminate\Http\Request;

class SdaResolutionController extends Controller
{
    /**
     * Display a listing of resolutions.
     */
    public function index()
    {
        $resolutions = SdaResolution::with(['meeting', 'proposer', 'seconder', 'tasks'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.sda.resolutions.index', compact('resolutions'));
    }

    /**
     * Show the form for creating a new resolution.
     */
    public function create()
    {
        $meetings = SdaMeeting::where('status', 'in_progress')->get();
        $users = User::all();
        return view('admin.sda.resolutions.create', compact('meetings', 'users'));
    }

    /**
     * Store a newly created resolution.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sda_meeting_id' => 'required|exists:sda_meetings,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'background' => 'nullable|string',
            'implementation_plan' => 'nullable|string',
            'resolution_type' => 'required|in:policy,budget,procurement,appointment,general',
            'priority' => 'required|in:low,medium,high,urgent',
            'proposed_by' => 'required|exists:users,id',
            'seconded_by' => 'required|exists:users,id|different:proposed_by',
            'implementation_deadline' => 'nullable|date|after:today',
        ]);

        $validated['status'] = 'proposed';
        $resolution = SdaResolution::create($validated);

        return redirect()
            ->route('admin.sda.resolutions.show', $resolution)
            ->with('success', 'Resolution proposed successfully.');
    }

    /**
     * Display the specified resolution.
     */
    public function show(SdaResolution $resolution)
    {
        $resolution->load([
            'meeting.committee',
            'proposer',
            'seconder',
            'tasks.assignee'
        ]);

        return view('admin.sda.resolutions.show', compact('resolution'));
    }

    /**
     * Show the form for editing the resolution.
     */
    public function edit(SdaResolution $resolution)
    {
        if (!in_array($resolution->status, ['proposed', 'seconded'])) {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'This resolution cannot be edited.');
        }

        $meetings = SdaMeeting::where('status', 'in_progress')->get();
        $users = User::all();
        return view('admin.sda.resolutions.edit', compact('resolution', 'meetings', 'users'));
    }

    /**
     * Update the specified resolution.
     */
    public function update(Request $request, SdaResolution $resolution)
    {
        if (!in_array($resolution->status, ['proposed', 'seconded'])) {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'This resolution cannot be edited.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'background' => 'nullable|string',
            'implementation_plan' => 'nullable|string',
            'resolution_type' => 'required|in:policy,budget,procurement,appointment,general',
            'priority' => 'required|in:low,medium,high,urgent',
            'proposed_by' => 'required|exists:users,id',
            'seconded_by' => 'required|exists:users,id|different:proposed_by',
            'implementation_deadline' => 'nullable|date|after:today',
        ]);

        $resolution->update($validated);

        return redirect()
            ->route('admin.sda.resolutions.show', $resolution)
            ->with('success', 'Resolution updated successfully.');
    }

    /**
     * Second the resolution.
     */
    public function second(SdaResolution $resolution)
    {
        if ($resolution->status !== 'proposed') {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'This resolution cannot be seconded.');
        }

        $resolution->update(['status' => 'seconded']);

        return redirect()
            ->route('admin.sda.resolutions.show', $resolution)
            ->with('success', 'Resolution seconded successfully.');
    }

    /**
     * Start debate on the resolution.
     */
    public function debate(SdaResolution $resolution)
    {
        if ($resolution->status !== 'seconded') {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'This resolution cannot be debated.');
        }

        $resolution->update(['status' => 'debated']);

        return redirect()
            ->route('admin.sda.resolutions.show', $resolution)
            ->with('success', 'Debate started on resolution.');
    }

    /**
     * Record vote on the resolution.
     */
    public function vote(Request $request, SdaResolution $resolution)
    {
        if (!$resolution->canBeVoted()) {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'This resolution cannot be voted on.');
        }

        $validated = $request->validate([
            'votes_for' => 'required|integer|min:0',
            'votes_against' => 'required|integer|min:0',
            'votes_abstained' => 'required|integer|min:0',
        ]);

        $resolution->recordVote(
            $validated['votes_for'],
            $validated['votes_against'],
            $validated['votes_abstained']
        );

        return redirect()
            ->route('admin.sda.resolutions.show', $resolution)
            ->with('success', 'Vote recorded successfully.');
    }

    /**
     * Mark resolution as implemented.
     */
    public function implement(Request $request, SdaResolution $resolution)
    {
        if (!$resolution->isPassed()) {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'Only passed resolutions can be implemented.');
        }

        $validated = $request->validate([
            'implementation_notes' => 'nullable|string|max:1000',
        ]);

        $resolution->markAsImplemented($validated['implementation_notes']);

        return redirect()
            ->route('admin.sda.resolutions.show', $resolution)
            ->with('success', 'Resolution marked as implemented.');
    }

    /**
     * Cancel the resolution.
     */
    public function cancel(Request $request, SdaResolution $resolution)
    {
        if (in_array($resolution->status, ['implemented', 'cancelled'])) {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'This resolution cannot be cancelled.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $resolution->cancel($validated['cancellation_reason']);

        return redirect()
            ->route('admin.sda.resolutions.show', $resolution)
            ->with('success', 'Resolution cancelled successfully.');
    }

    /**
     * Create tasks from resolution.
     */
    public function createTasks(Request $request, SdaResolution $resolution)
    {
        if (!$resolution->isPassed()) {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'Tasks can only be created from passed resolutions.');
        }

        $validated = $request->validate([
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.description' => 'required|string',
            'tasks.*.assigned_to' => 'required|exists:users,id',
            'tasks.*.priority' => 'required|in:low,medium,high,urgent',
            'tasks.*.due_date' => 'required|date|after:today',
        ]);

        foreach ($validated['tasks'] as $taskData) {
            SdaTask::create([
                'sda_committee_id' => $resolution->meeting->sda_committee_id,
                'assigned_to' => $taskData['assigned_to'],
                'assigned_by' => auth()->id(),
                'sda_resolution_id' => $resolution->id,
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'priority' => $taskData['priority'],
                'due_date' => $taskData['due_date'],
                'status' => 'pending',
            ]);
        }

        return redirect()
            ->route('admin.sda.resolutions.show', $resolution)
            ->with('success', 'Tasks created successfully.');
    }

    /**
     * Remove the specified resolution.
     */
    public function destroy(SdaResolution $resolution)
    {
        if (!in_array($resolution->status, ['proposed', 'seconded'])) {
            return redirect()
                ->route('admin.sda.resolutions.show', $resolution)
                ->with('error', 'This resolution cannot be deleted.');
        }

        $resolution->delete();

        return redirect()
            ->route('admin.sda.resolutions.index')
            ->with('success', 'Resolution deleted successfully.');
    }
}
