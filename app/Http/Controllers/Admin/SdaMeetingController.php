<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaMeeting;
use App\Models\SdaMeetingAttendance;
use App\Models\SdaMeetingMinute;
use App\Models\User;
use Illuminate\Http\Request;

class SdaMeetingController extends Controller
{
    /**
     * Display a listing of meetings.
     */
    public function index()
    {
        $meetings = SdaMeeting::with(['committee', 'attendances.user'])
            ->orderBy('meeting_date', 'desc')
            ->get();

        return view('admin.sda.meetings.index', compact('meetings'));
    }

    /**
     * Show the form for creating a new meeting.
     */
    public function create()
    {
        $committees = SdaCommittee::active()->get();
        return view('admin.sda.meetings.create', compact('committees'));
    }

    /**
     * Store a newly created meeting.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sda_committee_id' => 'required|exists:sda_committees,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date|after:now',
            'venue' => 'nullable|string|max:255',
            'meeting_type' => 'required|in:regular,emergency,annual,special',
            'agenda' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:15',
        ]);

        $meeting = SdaMeeting::create($validated);

        return redirect()
            ->route('admin.sda.meetings.show', $meeting)
            ->with('success', 'Meeting scheduled successfully.');
    }

    /**
     * Display the specified meeting.
     */
    public function show(SdaMeeting $meeting)
    {
        $meeting->load([
            'committee.activeMembers.user',
            'committee.activeMembers.role',
            'attendances.user',
            'minutes.recorder',
            'resolutions.proposer',
            'resolutions.seconder'
        ]);

        $availableMembers = $meeting->committee->activeMembers()
            ->whereDoesntHave('attendances', function($query) use ($meeting) {
                $query->where('sda_meeting_id', $meeting->id);
            })
            ->get();

        return view('admin.sda.meetings.show', compact('meeting', 'availableMembers'));
    }

    /**
     * Show the form for editing the meeting.
     */
    public function edit(SdaMeeting $meeting)
    {
        if (!$meeting->canBeEdited()) {
            return redirect()
                ->route('admin.sda.meetings.show', $meeting)
                ->with('error', 'This meeting cannot be edited.');
        }

        $committees = SdaCommittee::active()->get();
        return view('admin.sda.meetings.edit', compact('meeting', 'committees'));
    }

    /**
     * Update the specified meeting.
     */
    public function update(Request $request, SdaMeeting $meeting)
    {
        if (!$meeting->canBeEdited()) {
            return redirect()
                ->route('admin.sda.meetings.show', $meeting)
                ->with('error', 'This meeting cannot be edited.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date|after:now',
            'venue' => 'nullable|string|max:255',
            'meeting_type' => 'required|in:regular,emergency,annual,special',
            'agenda' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:15',
        ]);

        $meeting->update($validated);

        return redirect()
            ->route('admin.sda.meetings.show', $meeting)
            ->with('success', 'Meeting updated successfully.');
    }

    /**
     * Start the meeting.
     */
    public function start(SdaMeeting $meeting)
    {
        if ($meeting->status !== 'scheduled') {
            return redirect()
                ->route('admin.sda.meetings.show', $meeting)
                ->with('error', 'Meeting cannot be started.');
        }

        $meeting->update(['status' => 'in_progress']);

        return redirect()
            ->route('admin.sda.meetings.show', $meeting)
            ->with('success', 'Meeting started successfully.');
    }

    /**
     * Complete the meeting.
     */
    public function complete(SdaMeeting $meeting)
    {
        if ($meeting->status !== 'in_progress') {
            return redirect()
                ->route('admin.sda.meetings.show', $meeting)
                ->with('error', 'Meeting is not in progress.');
        }

        $meeting->update(['status' => 'completed']);

        return redirect()
            ->route('admin.sda.meetings.show', $meeting)
            ->with('success', 'Meeting completed successfully.');
    }

    /**
     * Cancel the meeting.
     */
    public function cancel(Request $request, SdaMeeting $meeting)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $meeting->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return redirect()
            ->route('admin.sda.meetings.show', $meeting)
            ->with('success', 'Meeting cancelled successfully.');
    }

    /**
     * Record attendance for the meeting.
     */
    public function recordAttendance(Request $request, SdaMeeting $meeting)
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.attendance_status' => 'required|in:present,absent,apologized,late',
            'attendances.*.arrival_time' => 'nullable|date_format:H:i',
            'attendances.*.apology_reason' => 'nullable|string|max:255',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        foreach ($validated['attendances'] as $attendance) {
            SdaMeetingAttendance::updateOrCreate(
                [
                    'sda_meeting_id' => $meeting->id,
                    'user_id' => $attendance['user_id'],
                ],
                $attendance
            );
        }

        return redirect()
            ->route('admin.sda.meetings.show', $meeting)
            ->with('success', 'Attendance recorded successfully.');
    }

    /**
     * Remove the specified meeting.
     */
    public function destroy(SdaMeeting $meeting)
    {
        if (!$meeting->canBeEdited()) {
            return redirect()
                ->route('admin.sda.meetings.show', $meeting)
                ->with('error', 'This meeting cannot be deleted.');
        }

        $meeting->delete();

        return redirect()
            ->route('admin.sda.meetings.index')
            ->with('success', 'Meeting deleted successfully.');
    }
}
