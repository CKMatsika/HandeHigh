<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Hostel;
use App\Rules\TenantExists;
use App\Services\Residency\BoardingStaffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HostelController extends Controller
{
    protected BoardingStaffService $boardingStaffService;

    public function __construct(BoardingStaffService $boardingStaffService)
    {
        $this->boardingStaffService = $boardingStaffService;
    }

    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $hostels = Hostel::where('school_id', $school->id)
            ->with(['supervisor', 'dormitories.beds'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.hostels.index', compact('hostels'));
    }

    public function create()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $employees = Employee::where('school_id', $school->id)
            ->active()
            ->get()
            ->filter(fn ($emp) => $emp->isNonTeaching());

        return view('admin.hostels.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'supervisor_id' => ['nullable', TenantExists::make('employees')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $hostel = Hostel::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'gender' => $validated['gender'],
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($validated['supervisor_id'])) {
            $employee = Employee::where('school_id', $school->id)->findOrFail($validated['supervisor_id']);
            $this->boardingStaffService->assignHostelSupervisor($hostel, $employee);
        }

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel created successfully.');
    }

    public function show(Hostel $hostel)
    {
        $school = Auth::user()?->school;
        if (! $school || $hostel->school_id !== $school->id) {
            abort(403);
        }

        $hostel->load(['supervisor', 'dormitories.beds.currentAssignment.student', 'dormitories.supervisor']);

        return view('admin.hostels.show', compact('hostel'));
    }

    public function edit(Hostel $hostel)
    {
        $school = Auth::user()?->school;
        if (! $school || $hostel->school_id !== $school->id) {
            abort(403);
        }

        $employees = Employee::where('school_id', $school->id)
            ->active()
            ->get()
            ->filter(fn ($emp) => $emp->isNonTeaching());

        return view('admin.hostels.edit', compact('hostel', 'employees'));
    }

    public function update(Request $request, Hostel $hostel)
    {
        $school = Auth::user()?->school;
        if (! $school || $hostel->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'supervisor_id' => ['nullable', TenantExists::make('employees')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $hostel->update([
            'name' => $validated['name'],
            'gender' => $validated['gender'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($validated['supervisor_id'])) {
            $employee = Employee::where('school_id', $school->id)->findOrFail($validated['supervisor_id']);
            $this->boardingStaffService->assignHostelSupervisor($hostel, $employee);
        } else {
            $hostel->update(['supervisor_id' => null]);
        }

        return redirect()->route('admin.hostels.show', $hostel)->with('success', 'Hostel updated successfully.');
    }

    public function destroy(Hostel $hostel)
    {
        $school = Auth::user()?->school;
        if (! $school || $hostel->school_id !== $school->id) {
            abort(403);
        }

        $hostel->delete();

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel deleted successfully.');
    }
}
