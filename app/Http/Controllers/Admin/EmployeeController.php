<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Services\AuditService;
use App\Rules\TenantExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $school = auth()->user()->school;
        
        $query = Employee::where('school_id', $school->id)
            ->with('department', 'user');

        // Filters
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        
        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest()->paginate(50);
        $departments = Department::where('school_id', $school->id)->get();

        return view('admin.employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        $school = auth()->user()->school;
        $departments = Department::where('school_id', $school->id)->get();

        return view('admin.employees.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'employee_id' => 'required|string|max:50|unique:employees,employee_id',
            'department_id' => ['required', TenantExists::make('departments')],
            'position' => 'required|string|max:255',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'employment_status' => 'required|in:active,terminated,resigned,on_leave',
            'date_of_birth' => 'required|date',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'national_id' => 'nullable|string|max:50',
            'zimra_tin' => 'nullable|string|max:50',
            'nssa_number' => 'nullable|string|max:50',
            'nec_sector_code' => 'nullable|string|max:50',
            'medical_aid_usd' => 'nullable|numeric|min:0',
            'medical_aid_zwg' => 'nullable|numeric|min:0',
            'trade_union_member' => 'nullable|boolean',
            'trade_union_rate' => 'nullable|numeric|min:0',
            'trade_union_flat_amount' => 'nullable|numeric|min:0',
            'work_schedule' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'create_user_account' => 'boolean',
            'password' => 'required_if:create_user_account,1|string|min:8',
        ]);

        $school = auth()->user()->school;
        
        $employee = Employee::create([
            'school_id' => $school->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'employee_id' => $request->employee_id,
            'national_id' => $request->national_id,
            'zimra_tin' => $request->zimra_tin,
            'nssa_number' => $request->nssa_number,
            'nec_sector_code' => $request->nec_sector_code ?? 'NEC-EDU',
            'department_id' => $request->department_id,
            'position' => $request->position,
            'employment_type' => $request->employment_type,
            'employment_status' => $request->employment_status,
            'date_of_birth' => $request->date_of_birth,
            'hire_date' => $request->hire_date,
            'salary' => $request->salary,
            'medical_aid_usd' => $request->medical_aid_usd ?? 0,
            'medical_aid_zwg' => $request->medical_aid_zwg ?? 0,
            'trade_union_member' => $request->boolean('trade_union_member'),
            'trade_union_rate' => $request->trade_union_rate ?? 0,
            'trade_union_flat_amount' => $request->trade_union_flat_amount ?? 0,
            'work_schedule' => $request->work_schedule,
            'address' => $request->address,
            'emergency_contact' => $request->emergency_contact,
            'emergency_phone' => $request->emergency_phone,
        ]);

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('employee-photos', 'public');
            $employee->update(['profile_photo' => $photoPath]);
        }

        // Create user account if requested
        if ($request->boolean('create_user_account')) {
            $user = User::create([
                'school_id' => $school->id,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assign staff role
            $staffRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff', 'school_id' => $school->id]);
            $user->assignRole($staffRole);

            $employee->update(['user_id' => $user->id]);
        }

        // Log the creation
        AuditService::log('create', $employee, "Employee created: {$employee->first_name} {$employee->last_name}", 'hr-management');

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $this->authorizeSchoolAccess($employee);
        $employee->load([
            'department',
            'user',
            'qualifications',
            'leaves',
            'payrollItems.payroll',
            'activeLoans',
            'positionAssignments.position',
        ]);

        $tab = request()->query('tab', 'overview');

        $school = auth()->user()->school;

        return view('admin.employees.show', compact('employee', 'tab', 'school'));
    }

    public function addQualification(Request $request, Employee $employee)
    {
        $this->authorizeSchoolAccess($employee);
        $school = auth()->user()->school;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'grade' => 'nullable|string|max:50',
            'year_start' => 'nullable|integer|min:1900|max:2099',
            'year_end' => 'nullable|integer|min:1900|max:2099',
            'notes' => 'nullable|string|max:500',
        ]);

        \App\Models\Qualification::create([
            'school_id' => $school->id,
            'qualifiable_id' => $employee->id,
            'qualifiable_type' => \App\Models\Employee::class,
            ...$validated,
        ]);

        return back()->with('success', 'Qualification added.');
    }

    public function deleteQualification(Request $request, Employee $employee, \App\Models\Qualification $qualification)
    {
        $this->authorizeSchoolAccess($employee);
        if ($qualification->qualifiable_id !== $employee->id || $qualification->qualifiable_type !== \App\Models\Employee::class) {
            abort(403);
        }
        $qualification->delete();
        return back()->with('success', 'Qualification deleted.');
    }

    public function edit(Employee $employee)
    {
        $this->authorizeSchoolAccess($employee);
        $school = auth()->user()->school;
        $departments = Department::where('school_id', $school->id)->get();

        return view('admin.employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeSchoolAccess($employee);
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . ($employee->user_id ?? 0),
            'phone' => 'required|string|max:20',
            'employee_id' => 'required|string|max:50|unique:employees,employee_id,' . $employee->id,
            'department_id' => ['required', TenantExists::make('departments')],
            'position' => 'required|string|max:255',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'employment_status' => 'required|in:active,terminated,resigned,on_leave',
            'date_of_birth' => 'required|date',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'national_id' => 'nullable|string|max:50',
            'zimra_tin' => 'nullable|string|max:50',
            'nssa_number' => 'nullable|string|max:50',
            'nec_sector_code' => 'nullable|string|max:50',
            'medical_aid_usd' => 'nullable|numeric|min:0',
            'medical_aid_zwg' => 'nullable|numeric|min:0',
            'trade_union_member' => 'nullable|boolean',
            'trade_union_rate' => 'nullable|numeric|min:0',
            'trade_union_flat_amount' => 'nullable|numeric|min:0',
            'work_schedule' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $employee->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'employee_id' => $request->employee_id,
            'national_id' => $request->national_id,
            'zimra_tin' => $request->zimra_tin,
            'nssa_number' => $request->nssa_number,
            'nec_sector_code' => $request->nec_sector_code ?? $employee->nec_sector_code ?? 'NEC-EDU',
            'department_id' => $request->department_id,
            'position' => $request->position,
            'employment_type' => $request->employment_type,
            'employment_status' => $request->employment_status,
            'date_of_birth' => $request->date_of_birth,
            'hire_date' => $request->hire_date,
            'salary' => $request->salary,
            'medical_aid_usd' => $request->medical_aid_usd ?? 0,
            'medical_aid_zwg' => $request->medical_aid_zwg ?? 0,
            'trade_union_member' => $request->boolean('trade_union_member'),
            'trade_union_rate' => $request->trade_union_rate ?? 0,
            'trade_union_flat_amount' => $request->trade_union_flat_amount ?? 0,
            'work_schedule' => $request->work_schedule,
            'address' => $request->address,
            'emergency_contact' => $request->emergency_contact,
            'emergency_phone' => $request->emergency_phone,
        ]);

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }
            $photoPath = $request->file('profile_photo')->store('employee-photos', 'public');
            $employee->update(['profile_photo' => $photoPath]);
        }

        // Update associated user account if exists
        if ($employee->user) {
            $employee->user->update([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);
        }

        // Log the update
        AuditService::log($employee->school_id, auth()->id(), 'update', 'employee', $employee->id, 
            "Employee updated: {$employee->first_name} {$employee->last_name}", request()->ip(), request()->userAgent(), 'hr-management');

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeSchoolAccess($employee);
        
        // Delete profile photo if exists
        if ($employee->profile_photo) {
            Storage::disk('public')->delete($employee->profile_photo);
        }

        // Delete associated user account if exists
        if ($employee->user) {
            $employee->user->delete();
        }

        $employeeName = $employee->first_name . ' ' . $employee->last_name;
        $employee->delete();

        // Log the deletion
        AuditService::log($employee->school_id, auth()->id(), 'delete', 'employee', $employee->id, 
            "Employee deleted: {$employeeName}", request()->ip(), request()->userAgent(), 'hr-management');

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    public function terminate(Request $request, Employee $employee)
    {
        $this->authorizeSchoolAccess($employee);
        
        $request->validate([
            'termination_date' => 'required|date',
            'termination_reason' => 'required|string|max:500',
        ]);

        $employee->update([
            'employment_status' => 'terminated',
            'termination_date' => $request->termination_date,
            'termination_reason' => $request->termination_reason,
        ]);

        // Deactivate associated user account if exists
        if ($employee->user) {
            $employee->user->update(['is_active' => false]);
        }

        // Log the termination
        AuditService::log($employee->school_id, auth()->id(), 'update', 'employee', $employee->id, 
            "Employee terminated: {$employee->first_name} {$employee->last_name}", request()->ip(), request()->userAgent(), 'hr-management');

        return redirect()->route('admin.employees.show', $employee)
            ->with('success', 'Employee terminated successfully.');
    }

    protected function authorizeSchoolAccess(Employee $employee)
    {
        if ($employee->school_id !== auth()->user()->school_id) {
            abort(403);
        }
    }
}
