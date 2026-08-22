<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $school = auth()->user()->school;
        
        $query = User::where('school_id', $school->id)
            ->with('roles');

        // Filters
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(50);
        $roles = Role::where('guard_name', 'web')->get();

        return view('admin.user-management.index', compact('users', 'roles'));
    }

    public function create()
    {
        $school = auth()->user()->school;
        $roles = Role::where('guard_name', 'web')->get();
        $permissions = Permission::all();

        return view('admin.user-management.create', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $school = auth()->user()->school;
        
        $user = User::create([
            'school_id' => $school->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'emergency_contact' => $request->emergency_contact,
            'emergency_phone' => $request->emergency_phone,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->update(['profile_photo' => $photoPath]);
        }

        // Assign roles
        $roles = Role::whereIn('id', $request->roles)->get();
        foreach ($roles as $role) {
            $user->assignRole($role);
        }

        // Assign additional permissions
        if ($request->filled('permissions')) {
            $permissions = Permission::whereIn('id', $request->permissions)->get();
            foreach ($permissions as $permission) {
                $user->givePermissionTo($permission);
            }
        }

        // Log the creation
        AuditService::log($school->id, auth()->id(), 'create', 'user', $user->id, 
            "User created: {$user->name}", request()->ip(), request()->userAgent(), 'user-management');

        return redirect()->route('admin.user-management.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $this->authorizeSchoolAccess($user);
        $user->load('roles', 'permissions');

        return view('admin.user-management.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorizeSchoolAccess($user);
        $school = auth()->user()->school;
        $roles = Role::where('guard_name', 'web')->get();
        $permissions = Permission::all();
        $userRoles = $user->roles->pluck('id')->toArray();
        $userPermissions = $user->permissions->pluck('id')->toArray();

        return view('admin.user-management.edit', compact('user', 'roles', 'permissions', 'userRoles', 'userPermissions'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeSchoolAccess($user);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'emergency_contact' => $request->emergency_contact,
            'emergency_phone' => $request->emergency_phone,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $photoPath = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->update(['profile_photo' => $photoPath]);
        }

        // Update roles
        $user->syncRoles($request->roles);

        // Update permissions
        if ($request->filled('permissions')) {
            $user->syncPermissions($request->permissions);
        } else {
            $user->syncPermissions([]);
        }

        // Log the update
        AuditService::log($user->school_id, auth()->id(), 'update', 'user', $user->id, 
            "User updated: {$user->name}", request()->ip(), request()->userAgent(), 'user-management');

        return redirect()->route('admin.user-management.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeSchoolAccess($user);
        
        // Prevent deletion of the current user
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.user-management.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Delete profile photo if exists
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $userName = $user->name;
        $user->delete();

        // Log the deletion
        AuditService::log($user->school_id, auth()->id(), 'delete', 'user', $user->id, 
            "User deleted: {$userName}", request()->ip(), request()->userAgent(), 'user-management');

        return redirect()->route('admin.user-management.index')
            ->with('success', 'User deleted successfully.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeSchoolAccess($user);
        
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Log the password reset
        AuditService::log($user->school_id, auth()->id(), 'update', 'user', $user->id, 
            "Password reset for: {$user->name}", request()->ip(), request()->userAgent(), 'user-management');

        return redirect()->route('admin.user-management.show', $user)
            ->with('success', 'Password reset successfully.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeSchoolAccess($user);
        
        // Prevent deactivation of the current user
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.user-management.index')
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        // Log the status change
        AuditService::log($user->school_id, auth()->id(), 'update', 'user', $user->id, 
            "User {$status}: {$user->name}", request()->ip(), request()->userAgent(), 'user-management');

        return redirect()->route('admin.user-management.index')
            ->with('success', "User {$status} successfully.");
    }

    protected function authorizeSchoolAccess(User $user)
    {
        if ($user->school_id !== auth()->user()->school_id) {
            abort(403);
        }
    }
}
