<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaMeeting;
use App\Models\SdaMeetingMinute;
use Illuminate\Http\Request;

class SdaMinuteController extends Controller
{
    /**
     * Display a listing of meeting minutes.
     */
    public function index()
    {
        $minutes = SdaMeetingMinute::with(['meeting.committee', 'recorder'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.sda.minutes.index', compact('minutes'));
    }

    /**
     * Show the form for creating meeting minutes.
     */
    public function create(SdaMeeting $meeting)
    {
        if ($meeting->status !== 'completed') {
            return redirect()
                ->route('admin.sda.meetings.show', $meeting)
                ->with('error', 'Minutes can only be created for completed meetings.');
        }

        if ($meeting->minutes) {
            return redirect()
                ->route('admin.sda.minutes.edit', $meeting->minutes)
                ->with('info', 'Minutes already exist for this meeting.');
        }

        return view('admin.sda.minutes.create', compact('meeting'));
    }

    /**
     * Store newly created meeting minutes.
     */
    public function store(Request $request, SdaMeeting $meeting)
    {
        if ($meeting->status !== 'completed') {
            return redirect()
                ->route('admin.sda.meetings.show', $meeting)
                ->with('error', 'Minutes can only be created for completed meetings.');
        }

        if ($meeting->minutes) {
            return redirect()
                ->route('admin.sda.minutes.edit', $meeting->minutes)
                ->with('info', 'Minutes already exist for this meeting.');
        }

        $validated = $request->validate([
            'opening_remarks' => 'nullable|string',
            'previous_minutes_summary' => 'nullable|string',
            'matters_arising' => 'nullable|string',
            'new_business' => 'nullable|string',
            'other_business' => 'nullable|string',
            'closing_remarks' => 'nullable|string',
            'next_meeting_date' => 'nullable|date|after:today',
        ]);

        $validated['sda_meeting_id'] = $meeting->id;
        $validated['recorded_by'] = auth()->id();
        $validated['status'] = 'draft';

        $minutes = SdaMeetingMinute::create($validated);

        return redirect()
            ->route('admin.sda.minutes.show', $minutes)
            ->with('success', 'Meeting minutes created successfully.');
    }

    /**
     * Display the specified meeting minutes.
     */
    public function show(SdaMeetingMinute $minutes)
    {
        $minutes->load(['meeting.committee', 'meeting.attendances.user', 'recorder']);
        return view('admin.sda.minutes.show', compact('minutes'));
    }

    /**
     * Show the form for editing the meeting minutes.
     */
    public function edit(SdaMeetingMinute $minutes)
    {
        if (!$minutes->canBeEdited()) {
            return redirect()
                ->route('admin.sda.minutes.show', $minutes)
                ->with('error', 'These minutes cannot be edited.');
        }

        return view('admin.sda.minutes.edit', compact('minutes'));
    }

    /**
     * Update the specified meeting minutes.
     */
    public function update(Request $request, SdaMeetingMinute $minutes)
    {
        if (!$minutes->canBeEdited()) {
            return redirect()
                ->route('admin.sda.minutes.show', $minutes)
                ->with('error', 'These minutes cannot be edited.');
        }

        $validated = $request->validate([
            'opening_remarks' => 'nullable|string',
            'previous_minutes_summary' => 'nullable|string',
            'matters_arising' => 'nullable|string',
            'new_business' => 'nullable|string',
            'other_business' => 'nullable|string',
            'closing_remarks' => 'nullable|string',
            'next_meeting_date' => 'nullable|date|after:today',
        ]);

        $minutes->update($validated);

        return redirect()
            ->route('admin.sda.minutes.show', $minutes)
            ->with('success', 'Meeting minutes updated successfully.');
    }

    /**
     * Submit minutes for chairman review.
     */
    public function submit(SdaMeetingMinute $minutes)
    {
        if (!$minutes->canBeEdited()) {
            return redirect()
                ->route('admin.sda.minutes.show', $minutes)
                ->with('error', 'These minutes cannot be submitted.');
        }

        $minutes->update(['status' => 'review']);

        return redirect()
            ->route('admin.sda.minutes.show', $minutes)
            ->with('success', 'Minutes submitted for chairman review.');
    }

    /**
     * Chairman reviews the minutes.
     */
    public function chairmanReview(Request $request, SdaMeetingMinute $minutes)
    {
        if ($minutes->status !== 'review') {
            return redirect()
                ->route('admin.sda.minutes.show', $minutes)
                ->with('error', 'These minutes are not ready for review.');
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'chairman_review_notes' => 'required|string|max:1000',
        ]);

        if ($validated['action'] === 'approve') {
            $minutes->markAsReviewed($validated['chairman_review_notes']);
            $minutes->approve();
        } else {
            $minutes->markAsReviewed($validated['chairman_review_notes']);
            $minutes->update(['status' => 'draft']);
        }

        return redirect()
            ->route('admin.sda.minutes.show', $minutes)
            ->with('success', 'Minutes reviewed successfully.');
    }

    /**
     * Approve the minutes.
     */
    public function approve(SdaMeetingMinute $minutes)
    {
        if (!in_array($minutes->status, ['review', 'draft'])) {
            return redirect()
                ->route('admin.sda.minutes.show', $minutes)
                ->with('error', 'These minutes cannot be approved.');
        }

        $minutes->approve();

        return redirect()
            ->route('admin.sda.minutes.show', $minutes)
            ->with('success', 'Minutes approved successfully.');
    }

    /**
     * Publish the minutes.
     */
    public function publish(SdaMeetingMinute $minutes)
    {
        if (!$minutes->isApproved()) {
            return redirect()
                ->route('admin.sda.minutes.show', $minutes)
                ->with('error', 'Only approved minutes can be published.');
        }

        $minutes->publish();

        return redirect()
            ->route('admin.sda.minutes.show', $minutes)
            ->with('success', 'Minutes published successfully.');
    }

    /**
     * Download minutes as PDF.
     */
    public function download(SdaMeetingMinute $minutes)
    {
        if (!$minutes->isApproved()) {
            return redirect()
                ->route('admin.sda.minutes.show', $minutes)
                ->with('error', 'Only approved minutes can be downloaded.');
        }

        // Generate PDF here (you'll need to implement PDF generation)
        // For now, return a simple text view
        return view('admin.sda.minutes.download', compact('minutes'));
    }

    /**
     * Remove the specified minutes.
     */
    public function destroy(SdaMeetingMinute $minutes)
    {
        if (!$minutes->canBeEdited()) {
            return redirect()
                ->route('admin.sda.minutes.show', $minutes)
                ->with('error', 'These minutes cannot be deleted.');
        }

        $minutes->delete();

        return redirect()
            ->route('admin.sda.meetings.show', $minutes->meeting)
            ->with('success', 'Meeting minutes deleted successfully.');
    }
}
