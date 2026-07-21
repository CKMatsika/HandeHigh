<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaRole;
use Illuminate\Http\Request;

class SdaRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = SdaRole::orderBy('name')->get();
        
        return view('admin.sda.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sda.roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sda_roles,name',
            'description' => 'nullable|string',
            'is_executive' => 'boolean',
            'can_approve_budgets' => 'boolean',
            'can_manage_members' => 'boolean',
            'can_view_reports' => 'boolean',
            'is_active' => 'boolean',
        ]);

        SdaRole::create($validated);

        return redirect()
            ->route('admin.sda.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SdaRole $role)
    {
        $role->load('activeMembers.user', 'activeMembers.committee');
        
        return view('admin.sda.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SdaRole $role)
    {
        return view('admin.sda.roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SdaRole $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sda_roles,name,' . $role->id,
            'description' => 'nullable|string',
            'is_executive' => 'boolean',
            'can_approve_budgets' => 'boolean',
            'can_manage_members' => 'boolean',
            'can_view_reports' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $role->update($validated);

        return redirect()
            ->route('admin.sda.roles.show', $role)
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SdaRole $role)
    {
        if ($role->activeMembers()->exists()) {
            return redirect()
                ->route('admin.sda.roles.show', $role)
                ->with('error', 'Cannot delete a role that is assigned to active members.');
        }

        $role->delete();

        return redirect()
            ->route('admin.sda.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
