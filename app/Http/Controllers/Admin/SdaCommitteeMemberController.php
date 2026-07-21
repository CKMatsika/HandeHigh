<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaCommitteeMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SdaCommitteeMemberController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, SdaCommittee $committee)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'sda_role_id' => 'required|exists:sda_roles,id',
            'is_chairperson' => 'boolean',
            'is_secretary' => 'boolean',
            'is_treasurer' => 'boolean',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        // Ensure only one chairperson per committee
        if ($request->has('is_chairperson') && $request->is_chairperson) {
            $committee->activeMembers()->update(['is_chairperson' => false]);
        }

        // Ensure only one secretary per committee
        if ($request->has('is_secretary') && $request->is_secretary) {
            $committee->activeMembers()->update(['is_secretary' => false]);
        }

        // Ensure only one treasurer per committee
        if ($request->has('is_treasurer') && $request->is_treasurer) {
            $committee->activeMembers()->update(['is_treasurer' => false]);
        }

        // Deactivate any existing active membership for this user in this committee
        $committee->activeMembers()
            ->where('user_id', $validated['user_id'])
            ->update(['is_active' => false]);

        // Create new membership
        $committee->members()->create($validated);

        return redirect()
            ->route('admin.sda.committees.show', $committee)
            ->with('success', 'Member added to committee successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SdaCommittee $committee, SdaCommitteeMember $member)
    {
        $validated = $request->validate([
            'sda_role_id' => 'required|exists:sda_roles,id',
            'is_chairperson' => 'boolean',
            'is_secretary' => 'boolean',
            'is_treasurer' => 'boolean',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // If making this member a chairperson, remove chairperson status from others
        if ($request->has('is_chairperson') && $request->is_chairperson) {
            $committee->activeMembers()
                ->where('id', '!=', $member->id)
                ->update(['is_chairperson' => false]);
        }

        // If making this member a secretary, remove secretary status from others
        if ($request->has('is_secretary') && $request->is_secretary) {
            $committee->activeMembers()
                ->where('id', '!=', $member->id)
                ->update(['is_secretary' => false]);
        }

        // If making this member a treasurer, remove treasurer status from others
        if ($request->has('is_treasurer') && $request->is_treasurer) {
            $committee->activeMembers()
                ->where('id', '!=', $member->id)
                ->update(['is_treasurer' => false]);
        }

        $member->update($validated);

        return redirect()
            ->route('admin.sda.committees.show', $committee)
            ->with('success', 'Member updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SdaCommittee $committee, SdaCommitteeMember $member)
    {
        $member->update(['is_active' => false]);

        return redirect()
            ->route('admin.sda.committees.show', $committee)
            ->with('success', 'Member removed from committee successfully.');
    }
}
