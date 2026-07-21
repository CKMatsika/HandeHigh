<?php

namespace App\Http\Controllers\Sda;

use App\Http\Controllers\Controller;
use App\Models\SdaMeeting;
use App\Models\SdaResolution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SdaResolutionController extends Controller
{
    /**
     * Display a listing of resolutions.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get resolutions from meetings of committees the user belongs to
        $resolutions = SdaResolution::whereHas('meeting', function($query) use ($user) {
            $query->whereHas('committee', function($q) use ($user) {
                $q->whereHas('members', function($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id);
                });
            });
        })
        ->with(['meeting', 'proposer', 'seconder', 'tasks'])
        ->orderBy('created_at', 'desc')
        ->get();

        return view('sda.resolutions.index', compact('resolutions'));
    }

    /**
     * Display the specified resolution.
     */
    public function show(SdaResolution $resolution)
    {
        $user = Auth::user();
        
        // Check if user belongs to the meeting's committee
        $belongs = $resolution->meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to view this resolution.');
        }

        $resolution->load([
            'meeting.committee',
            'proposer',
            'seconder',
            'tasks.assignee'
        ]);

        return view('sda.resolutions.show', compact('resolution'));
    }

    /**
     * Show the form for creating a new resolution.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get meetings from committees the user belongs to
        $meetings = SdaMeeting::whereHas('committee', function($query) use ($user) {
            $query->whereHas('members', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })
        ->where('status', 'in_progress')
        ->with(['committee'])
        ->get();

        $users = User::all();

        return view('sda.resolutions.create', compact('meetings', 'users'));
    }

    /**
     * Store a newly created resolution.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'sda_meeting_id' => 'required|exists:sda_meetings,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'background' => 'nullable|string',
            'implementation_plan' => 'nullable|string',
            'resolution_type' => 'required|in:policy,budget,procurement,appointment,general',
            'priority' => 'required|in:low,medium,high,urgent',
            'seconded_by' => 'required|exists:users,id|different:proposed_by',
            'implementation_deadline' => 'nullable|date|after:today',
        ]);

        // Check if user belongs to the meeting's committee
        $meeting = SdaMeeting::findOrFail($validated['sda_meeting_id']);
        $belongs = $meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            return redirect()->route('sda.dashboard')
                ->with('error', 'You are not authorized to propose resolutions for this meeting.');
        }

        $validated['proposed_by'] = $user->id;
        $validated['status'] = 'proposed';
        $resolution = SdaResolution::create($validated);

        return redirect()
            ->route('sda.resolutions.show', $resolution)
            ->with('success', 'Resolution proposed successfully.');
    }

    /**
     * Show the form for editing the resolution.
     */
    public function edit(SdaResolution $resolution)
    {
        $user = Auth::user();
        
        // Check if user is the proposer and resolution is editable
        if ($resolution->proposed_by !== $user->id || !in_array($resolution->status, ['proposed', 'seconded'])) {
            abort(403, 'You are not authorized to edit this resolution.');
        }

        // Check if user belongs to the meeting's committee
        $belongs = $resolution->meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to edit this resolution.');
        }

        $meetings = SdaMeeting::whereHas('committee', function($query) use ($user) {
            $query->whereHas('members', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })
        ->where('status', 'in_progress')
        ->with(['committee'])
        ->get();

        $users = User::all();

        return view('sda.resolutions.edit', compact('resolution', 'meetings', 'users'));
    }

    /**
     * Update the specified resolution.
     */
    public function update(Request $request, SdaResolution $resolution)
    {
        $user = Auth::user();
        
        // Check if user is the proposer and resolution is editable
        if ($resolution->proposed_by !== $user->id || !in_array($resolution->status, ['proposed', 'seconded'])) {
            abort(403, 'You are not authorized to edit this resolution.');
        }

        // Check if user belongs to the meeting's committee
        $belongs = $resolution->meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to edit this resolution.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'background' => 'nullable|string',
            'implementation_plan' => 'nullable|string',
            'resolution_type' => 'required|in:policy,budget,procurement,appointment,general',
            'priority' => 'required|in:low,medium,high,urgent',
            'seconded_by' => 'required|exists:users,id|different:proposed_by',
            'implementation_deadline' => 'nullable|date|after:today',
        ]);

        $resolution->update($validated);

        return redirect()
            ->route('sda.resolutions.show', $resolution)
            ->with('success', 'Resolution updated successfully.');
    }

    /**
     * Second the resolution.
     */
    public function second(SdaResolution $resolution)
    {
        $user = Auth::user();
        
        if ($resolution->status !== 'proposed') {
            abort(403, 'This resolution cannot be seconded.');
        }

        // Check if user belongs to the meeting's committee
        $belongs = $resolution->meeting->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to second this resolution.');
        }

        $resolution->update(['status' => 'seconded']);

        return redirect()
            ->route('sda.resolutions.show', $resolution)
            ->with('success', 'Resolution seconded successfully.');
    }
}
