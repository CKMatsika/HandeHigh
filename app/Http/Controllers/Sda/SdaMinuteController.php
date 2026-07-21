<?php

namespace App\Http\Controllers\Sda;

use App\Http\Controllers\Controller;
use App\Models\SdaMeeting;
use App\Models\SdaMeetingMinute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SdaMinuteController extends Controller
{
    /**
     * Display a listing of minutes.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get minutes from meetings of committees the user belongs to
        $minutes = SdaMeetingMinute::whereHas('meeting', function($query) use ($user) {
            $query->whereHas('committee', function($q) use ($user) {
                $q->whereHas('members', function($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id);
                });
            });
        })
        ->with(['meeting.committee', 'recorder'])
        ->orderBy('created_at', 'desc')
        ->get();

        return view('sda.minutes.index', compact('minutes'));
    }

    /**
     * Display the specified minutes.
     */
    public function show(SdaMeetingMinute $minute)
    {
        $user = Auth::user();
        
        // Check if user belongs to the meeting's committee
        $belongs = $minute->meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to view these minutes.');
        }

        $minute->load(['meeting.committee', 'recorder', 'meeting.attendances.user']);

        return view('sda.minutes.show', compact('minute'));
    }

    /**
     * Show the form for creating new minutes.
     */
    public function create(SdaMeeting $meeting)
    {
        $user = Auth::user();
        
        // Check if meeting is completed
        if ($meeting->status !== 'completed') {
            return redirect()
                ->route('sda.meetings.show', $meeting)
                ->with('error', 'Minutes can only be created for completed meetings.');
        }

        // Check if user belongs to the meeting's committee
        $belongs = $meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to create minutes for this meeting.');
        }

        // Check if minutes already exist
        if ($meeting->minutes) {
            return redirect()
                ->route('sda.minutes.show', $meeting->minutes)
                ->with('error', 'Minutes already exist for this meeting.');
        }

        $meeting->load(['committee', 'attendances.user']);

        return view('sda.minutes.create', compact('meeting'));
    }

    /**
     * Store newly created minutes.
     */
    public function store(Request $request, SdaMeeting $meeting)
    {
        $user = Auth::user();
        
        // Check if meeting is completed
        if ($meeting->status !== 'completed') {
            return redirect()
                ->route('sda.meetings.show', $meeting)
                ->with('error', 'Minutes can only be created for completed meetings.');
        }

        // Check if user belongs to the meeting's committee
        $belongs = $meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to create minutes for this meeting.');
        }

        // Check if minutes already exist
        if ($meeting->minutes) {
            return redirect()
                ->route('sda.minutes.show', $meeting->minutes)
                ->with('error', 'Minutes already exist for this meeting.');
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'key_decisions' => 'nullable|string',
            'action_items' => 'nullable|string',
            'next_meeting_date' => 'nullable|date|after:today',
            'next_meeting_agenda' => 'nullable|string',
        ]);

        $validated['sda_meeting_id'] = $meeting->id;
        $validated['recorded_by'] = $user->id;
        $validated['status'] = 'draft';

        $minute = SdaMeetingMinute::create($validated);

        return redirect()
            ->route('sda.minutes.show', $minute)
            ->with('success', 'Minutes created successfully.');
    }

    /**
     * Show the form for editing the minutes.
     */
    public function edit(SdaMeetingMinute $minute)
    {
        $user = Auth::user();
        
        // Check if user is the recorder and minutes are editable
        if ($minute->recorded_by !== $user->id || !$minute->canBeEdited()) {
            abort(403, 'You are not authorized to edit these minutes.');
        }

        // Check if user belongs to the meeting's committee
        $belongs = $minute->meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to edit these minutes.');
        }

        $minute->load(['meeting.committee', 'meeting.attendances.user']);

        return view('sda.minutes.edit', compact('minute'));
    }

    /**
     * Update the specified minutes.
     */
    public function update(Request $request, SdaMeetingMinute $minute)
    {
        $user = Auth::user();
        
        // Check if user is the recorder and minutes are editable
        if ($minute->recorded_by !== $user->id || !$minute->canBeEdited()) {
            abort(403, 'You are not authorized to edit these minutes.');
        }

        // Check if user belongs to the meeting's committee
        $belongs = $minute->meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to edit these minutes.');
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'key_decisions' => 'nullable|string',
            'action_items' => 'nullable|string',
            'next_meeting_date' => 'nullable|date|after:today',
            'next_meeting_agenda' => 'nullable|string',
        ]);

        $minute->update($validated);

        return redirect()
            ->route('sda.minutes.show', $minute)
            ->with('success', 'Minutes updated successfully.');
    }

    /**
     * Submit the minutes for review.
     */
    public function submit(SdaMeetingMinute $minute)
    {
        $user = Auth::user();
        
        if (!$minute->canBeEdited() || $minute->recorded_by !== $user->id) {
            abort(403, 'You are not authorized to submit these minutes.');
        }

        $minute->submit();

        return redirect()
            ->route('sda.minutes.show', $minute)
            ->with('success', 'Minutes submitted for review successfully.');
    }

    /**
     * Approve the minutes.
     */
    public function approve(SdaMeetingMinute $minute)
    {
        $user = Auth::user();
        
        // Check if user is committee chairman or secretary
        $canApprove = $minute->meeting->committee->members()
            ->where('user_id', $user->id)
            ->whereIn('role_id', function($q) {
                $q->select('id')->from('sda_roles')
                  ->whereIn('name', ['Chairman', 'Secretary']);
            })
            ->exists();

        if (!$canApprove) {
            abort(403, 'You are not authorized to approve these minutes.');
        }

        $minute->approve($user->id);

        return redirect()
            ->route('sda.minutes.show', $minute)
            ->with('success', 'Minutes approved successfully.');
    }

    /**
     * Publish the minutes.
     */
    public function publish(SdaMeetingMinute $minute)
    {
        $user = Auth::user();
        
        // Check if user is committee chairman
        $canPublish = $minute->meeting->committee->members()
            ->where('user_id', $user->id)
            ->whereHas('role', function($q) {
                $q->where('name', 'Chairman');
            })
            ->exists();

        if (!$canPublish) {
            abort(403, 'You are not authorized to publish these minutes.');
        }

        $minute->publish();

        return redirect()
            ->route('sda.minutes.show', $minute)
            ->with('success', 'Minutes published successfully.');
    }
}
