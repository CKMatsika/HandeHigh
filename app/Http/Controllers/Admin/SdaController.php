<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaCommitteeMember;
use App\Models\SdaRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SdaController extends Controller
{
    /**
     * Display the SDA dashboard.
     */
    public function dashboard()
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403, 'No school associated with your account');
        }

        // Get SDA statistics
        $stats = [
            'total_committees' => SdaCommittee::active()->count(),
            'total_members' => SdaCommitteeMember::active()->count(),
            'executive_members' => SdaCommitteeMember::active()->executive()->count(),
            'committees_with_financial_approval' => SdaCommittee::active()->requiresFinancialApproval()->count(),
            'committees_with_procurement_approval' => SdaCommittee::active()->requiresProcurementApproval()->count(),
        ];

        // Get active committees with member counts
        $committees = SdaCommittee::active()
            ->withCount(['activeMembers', 'activeMembers as executive_count' => function ($query) {
                $query->whereHas('role', function ($q) {
                    $q->where('is_executive', true);
                });
            }])
            ->get();

        // Get recent SDA activities
        $recentActivities = SdaCommitteeMember::with(['user', 'committee', 'role'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.sda.dashboard', compact('stats', 'committees', 'recentActivities'));
    }

    /**
     * Display all committees.
     */
    public function committeesIndex()
    {
        $committees = SdaCommittee::active()
            ->with(['activeMembers' => function ($query) {
                $query->with(['user', 'role'])->orderBy('appointment_date', 'desc');
            }])
            ->get();

        return view('admin.sda.committees.index', compact('committees'));
    }

    /**
     * Show a specific committee.
     */
    public function committeesShow(SdaCommittee $committee)
    {
        $committee->load([
            'activeMembers' => function ($query) {
                $query->with(['user', 'role'])->orderBy('appointment_date', 'desc');
            }
        ]);

        return view('admin.sda.committees.show', compact('committee'));
    }

    /**
     * Display committee members management.
     */
    public function membersIndex()
    {
        $members = SdaCommitteeMember::with(['user', 'committee', 'role'])
            ->orderBy('appointment_date', 'desc')
            ->paginate(20);

        $committees = SdaCommittee::active()->orderBy('name')->get();
        $roles = SdaRole::active()->ordered()->get();

        return view('admin.sda.members.index', compact('members', 'committees', 'roles'));
    }

    /**
     * Store a new committee member.
     */
    public function membersStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'sda_committee_id' => 'required|exists:sda_committees,id',
            'sda_role_id' => 'required|exists:sda_roles,id',
            'appointment_date' => 'required|date',
            'end_date' => 'nullable|date|after:appointment_date',
            'notes' => 'nullable|string',
        ]);

        // Check if user is already active in this committee
        $existing = SdaCommitteeMember::where('user_id', $request->user_id)
            ->where('sda_committee_id', $request->sda_committee_id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return back()->with('error', 'User is already an active member of this committee.');
        }

        $member = SdaCommitteeMember::create($request->all());

        // Update user's SDA summary
        $member->user->updateSdaPositionsSummary();

        return back()->with('success', 'Committee member added successfully.');
    }

    /**
     * Update a committee member.
     */
    public function membersUpdate(Request $request, SdaCommitteeMember $member)
    {
        $request->validate([
            'sda_role_id' => 'required|exists:sda_roles,id',
            'appointment_date' => 'required|date',
            'end_date' => 'nullable|date|after:appointment_date',
            'notes' => 'nullable|string',
        ]);

        $member->update($request->all());

        // Update user's SDA summary
        $member->user->updateSdaPositionsSummary();

        return back()->with('success', 'Committee member updated successfully.');
    }

    /**
     * Remove a committee member.
     */
    public function membersDestroy(SdaCommitteeMember $member)
    {
        $user = $member->user;
        
        $member->delete();

        // Update user's SDA summary
        $user->updateSdaPositionsSummary();

        return back()->with('success', 'Committee member removed successfully.');
    }

    /**
     * Toggle committee member active status.
     */
    public function membersToggleActive(SdaCommitteeMember $member)
    {
        $member->update(['is_active' => !$member->is_active]);

        // Update user's SDA summary
        $member->user->updateSdaPositionsSummary();

        return back()->with('success', 'Committee member status updated successfully.');
    }

    /**
     * Display SDA roles management.
     */
    public function rolesIndex()
    {
        $roles = SdaRole::active()->ordered()->get();

        return view('admin.sda.roles.index', compact('roles'));
    }

    /**
     * Store a new SDA role.
     */
    public function rolesStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_executive' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $role = SdaRole::create($request->all());

        return back()->with('success', 'SDA role created successfully.');
    }

    /**
     * Update an SDA role.
     */
    public function rolesUpdate(Request $request, SdaRole $role)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_executive' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $role->update($request->all());

        return back()->with('success', 'SDA role updated successfully.');
    }

    /**
     * Get users for autocomplete.
     */
    public function usersSearch(Request $request)
    {
        $query = $request->get('q');

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Get committee members for API.
     */
    public function committeeMembers(SdaCommittee $committee)
    {
        $members = $committee->activeMembers()
            ->with(['user', 'role'])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user' => [
                        'id' => $member->user->id,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                    ],
                    'role' => [
                        'id' => $member->role->id,
                        'name' => $member->role->name,
                        'is_executive' => $member->role->is_executive,
                    ],
                    'appointment_date' => $member->appointment_date->format('Y-m-d'),
                    'has_financial_approval' => $member->hasFinancialApproval(),
                    'has_procurement_approval' => $member->hasProcurementApproval(),
                ];
            });

        return response()->json($members);
    }
}
