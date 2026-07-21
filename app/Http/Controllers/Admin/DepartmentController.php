<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $school = auth()->user()->school;
        $departments = Department::where('school_id', $school->id)
            ->withCount('employees')
            ->latest()
            ->get();

        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'head_of_department' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $school = auth()->user()->school;
        
        $department = Department::create([
            'school_id' => $school->id,
            'name' => $request->name,
            'description' => $request->description,
            'head_of_department' => $request->head_of_department,
            'budget' => $request->budget,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Log the creation
        AuditService::log($school->id, auth()->id(), 'create', 'department', $department->id, 
            "Department created: {$department->name}", request()->ip(), request()->userAgent(), 'hr-management');

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department)
    {
        $this->authorizeSchoolAccess($department);
        $department->load('employees');

        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $this->authorizeSchoolAccess($department);

        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $this->authorizeSchoolAccess($department);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'head_of_department' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $department->update([
            'name' => $request->name,
            'description' => $request->description,
            'head_of_department' => $request->head_of_department,
            'budget' => $request->budget,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Log the update
        AuditService::log($department->school_id, auth()->id(), 'update', 'department', $department->id, 
            "Department updated: {$department->name}", request()->ip(), request()->userAgent(), 'hr-management');

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $this->authorizeSchoolAccess($department);
        
        // Check if department has employees
        if ($department->employees()->count() > 0) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'Cannot delete department with assigned employees. Please reassign or delete employees first.');
        }

        $departmentName = $department->name;
        $department->delete();

        // Log the deletion
        AuditService::log($department->school_id, auth()->id(), 'delete', 'department', $department->id, 
            "Department deleted: {$departmentName}", request()->ip(), request()->userAgent(), 'hr-management');

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    protected function authorizeSchoolAccess(Department $department)
    {
        if ($department->school_id !== auth()->user()->school_id) {
            abort(403);
        }
    }
}
