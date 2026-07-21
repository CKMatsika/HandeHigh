<?php

namespace App\Http\Controllers\Sda;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaMeeting;
use App\Models\SdaMeetingAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SdaMeetingController extends Controller
{
    /**
     * Display a listing of meetings.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get meetings from committees the user belongs to
        $meetings = SdaMeeting::whereHas('committee', function($query) use ($user) {
            $query->whereHas('members', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })->with(['committee', 'attendances.user'])
        ->orderByDesc('meeting_date')
        ->paginate(20);

        $committees = SdaCommittee::where('school_id', $user->school->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('sda.meetings.index', compact('meetings', 'committees'));
    }

    /**
     * Display the specified meeting.
     */
    public function show(SdaMeeting $meeting)
    {
        $user = Auth::user();
        
        // Check if user belongs to this meeting's committee
        $belongs = $meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to view this meeting.');
        }

        $meeting->load([
            'committee.activeMembers.user',
            'committee.activeMembers.role',
            'attendances.user',
            'minutes.recorder',
            'resolutions.proposer',
            'resolutions.seconder'
        ]);

        $userAttendance = $meeting->attendances()->where('user_id', $user->id)->first();

        return view('sda.meetings.show', compact('meeting', 'userAttendance'));
    }

    /**
     * Show the form for creating a new meeting (for chairman/secretary only).
     */
    public function create()
    {
        $user = Auth::user();
        
        // Check if user is chairman or secretary of any committee
        $committees = SdaCommittee::whereHas('members', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->whereIn('role_id', function($q) {
                      $q->select('id')->from('sda_roles')
                        ->whereIn('name', ['Chairman', 'Secretary']);
                  });
        })->active()->get();

        if ($committees->isEmpty()) {
            return redirect()->route('sda.dashboard')
                ->with('error', 'Only chairmen and secretaries can schedule meetings.');
        }

        return view('sda.meetings.create', compact('committees'));
    }

    /**
     * Store a newly created meeting.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Validate committee ownership
        $committee = SdaCommittee::findOrFail($request->sda_committee_id);
        $canSchedule = $committee->members()
            ->where('user_id', $user->id)
            ->whereIn('role_id', function($q) {
                $q->select('id')->from('sda_roles')
                  ->whereIn('name', ['Chairman', 'Secretary']);
            })
            ->exists();

        if (!$canSchedule) {
            return redirect()->route('sda.dashboard')
                ->with('error', 'You are not authorized to schedule meetings for this committee.');
        }

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
            ->route('sda.meetings.show', $meeting)
            ->with('success', 'Meeting scheduled successfully.');
    }

    /**
     * Record attendance for the meeting.
     */
    public function recordAttendance(Request $request, SdaMeeting $meeting)
    {
        $user = Auth::user();
        
        // Check if user belongs to this meeting's committee
        $belongs = $meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to record attendance for this meeting.');
        }

        $validated = $request->validate([
            'attendance_status' => 'required|in:present,absent,apologized,late',
            'arrival_time' => 'nullable|date_format:H:i',
            'apology_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);

        SdaMeetingAttendance::updateOrCreate(
            [
                'sda_meeting_id' => $meeting->id,
                'user_id' => $user->id,
            ],
            $validated
        );

        return redirect()
            ->route('sda.meetings.show', $meeting)
            ->with('success', 'Attendance recorded successfully.');
    }
}
