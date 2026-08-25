<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Dormitory;
use App\Models\Employee;
use App\Models\Hostel;
use App\Rules\TenantExists;
use App\Services\Residency\BoardingStaffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DormitoryController extends Controller
{
    protected BoardingStaffService $boardingStaffService;

    public function __construct(BoardingStaffService $boardingStaffService)
    {
        $this->boardingStaffService = $boardingStaffService;
    }

    public function index(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $query = Dormitory::where('school_id', $school->id)
            ->with(['hostel', 'supervisor', 'prefect', 'beds.currentAssignment.student']);

        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->hostel_id);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        $dormitories = $query->orderBy('name')->paginate(20);
        $hostels = Hostel::where('school_id', $school->id)->active()->get();

        return view('admin.dormitories.index', compact('dormitories', 'hostels'));
    }

    public function create()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $hostels = Hostel::where('school_id', $school->id)->active()->get();
        $employees = Employee::where('school_id', $school->id)
            ->active()
            ->get()
            ->filter(fn ($emp) => $emp->isNonTeaching());

        return view('admin.dormitories.create', compact('hostels', 'employees'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'hostel_id' => ['nullable', TenantExists::make('hostels')],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,mixed'],
            'capacity' => ['required', 'integer', 'min:1'],
            'supervisor_id' => ['nullable', TenantExists::make('employees')],
            'description' => ['nullable', 'string', 'max:1000'],
            'create_beds' => ['boolean'],
        ]);

        $dormitory = Dormitory::create([
            'school_id' => $school->id,
            'hostel_id' => $validated['hostel_id'] ?? null,
            'name' => $validated['name'],
            'gender' => $validated['gender'],
            'capacity' => $validated['capacity'],
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        if (! empty($validated['supervisor_id'])) {
            $employee = Employee::where('school_id', $school->id)->findOrFail($validated['supervisor_id']);
            $this->boardingStaffService->assignDormitorySupervisor($dormitory, $employee);
        }

        // Auto-create beds up to capacity if requested
        if ($request->boolean('create_beds', true)) {
            for ($i = 1; $i <= (int) $validated['capacity']; $i++) {
                Bed::create([
                    'dormitory_id' => $dormitory->id,
                    'bed_number' => 'Bed-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'is_available' => true,
                ]);
            }
        }

        return redirect()->route('admin.dormitories.show', $dormitory)->with('success', 'Dormitory created successfully.');
    }

    public function show(Dormitory $dormitory)
    {
        $school = Auth::user()?->school;
        if (! $school || $dormitory->school_id !== $school->id) {
            abort(403);
        }

        $dormitory->load(['hostel', 'supervisor', 'prefect', 'beds.currentAssignment.student']);

        return view('admin.dormitories.show', compact('dormitory'));
    }

    public function addBed(Request $request, Dormitory $dormitory)
    {
        $school = Auth::user()?->school;
        if (! $school || $dormitory->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'bed_number' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Bed::create([
            'dormitory_id' => $dormitory->id,
            'bed_number' => $validated['bed_number'],
            'description' => $validated['description'] ?? null,
            'is_available' => true,
        ]);

        $dormitory->increment('capacity');

        return redirect()->route('admin.dormitories.show', $dormitory)->with('success', 'Bed added successfully.');
    }
}
