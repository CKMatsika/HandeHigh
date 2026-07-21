<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaCommitteeMember;
use App\Models\SdaRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SdaCommitteeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $committees = SdaCommittee::withCount(['activeMembers', 'activeMembers as executive_count' => function($query) {
            $query->whereHas('role', function($q) {
                $q->where('is_executive', true);
            });
        }])->orderBy('name')->get();

        return view('admin.sda.committees.index', compact('committees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sda.committees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'requires_financial_approval' => 'boolean',
            'requires_procurement_approval' => 'boolean',
        ]);

        $committee = SdaCommittee::create($validated);

        return redirect()
            ->route('admin.sda.committees.show', $committee)
            ->with('success', 'Committee created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SdaCommittee $committee)
    {
        $committee->load([
            'activeMembers.user',
            'activeMembers.role',
            'activeMembers' => function($query) {
                $query->orderBy('is_chairperson', 'desc')
                    ->orderBy('is_secretary', 'desc')
                    ->orderBy('is_treasurer', 'desc')
                    ->orderBy('start_date', 'desc');
            }
        ]);

        $availableUsers = User::whereDoesntHave('sdaCommitteeMembers', function($query) use ($committee) {
            $query->where('sda_committee_id', $committee->id)
                ->where('is_active', true);
        })->get();

        $roles = SdaRole::orderBy('name')->get();

        return view('admin.sda.committees.show', compact('committee', 'availableUsers', 'roles'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SdaCommittee $committee)
    {
        return view('admin.sda.committees.edit', compact('committee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SdaCommittee $committee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'requires_financial_approval' => 'boolean',
            'requires_procurement_approval' => 'boolean',
        ]);

        $committee->update($validated);

        return redirect()
            ->route('admin.sda.committees.show', $committee)
            ->with('success', 'Committee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SdaCommittee $committee)
    {
        if ($committee->activeMembers()->exists()) {
            return redirect()
                ->route('admin.sda.committees.show', $committee)
                ->with('error', 'Cannot delete a committee with active members. Please remove all members first.');
        }

        $committee->delete();

        return redirect()
            ->route('admin.sda.committees.index')
            ->with('success', 'Committee deleted successfully.');
    }
}
