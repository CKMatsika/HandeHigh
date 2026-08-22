<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Employee;
use App\Rules\TenantExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $query = Leave::where('school_id', $school->id)
            ->with('employee', 'approvedBy');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $leaves = $query->latest()->paginate(20);
        $employees = Employee::where('school_id', $school->id)->active()->get();

        return view('admin.leaves.index', compact('school', 'leaves', 'employees'));
    }

    public function create()
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $employees = Employee::where('school_id', $school->id)->active()->get();

        return view('admin.leaves.create', compact('school', 'employees'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'employee_id' => ['required', TenantExists::make('employees')],
            'leave_type' => 'required|in:annual,sick,maternity,paternity,study,compassionate,unpaid,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);
        $validated['total_days'] = $start->diffInDays($end) + 1;
        $validated['school_id'] = $school->id;
        $validated['status'] = 'pending';

        Leave::create($validated);

        return redirect()->route('admin.leaves.index')
            ->with('success', 'Leave request created successfully.');
    }

    public function show(Leave $leave)
    {
        $school = Auth::user()->school;
        if (!$school || $leave->school_id !== $school->id) abort(403);

        $leave->load('employee', 'approvedBy');

        return view('admin.leaves.show', compact('school', 'leave'));
    }

    public function approve(Leave $leave)
    {
        $school = Auth::user()->school;
        if (!$school || $leave->school_id !== $school->id) abort(403);

        $leave->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.leaves.show', $leave)
            ->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, Leave $leave)
    {
        $school = Auth::user()->school;
        if (!$school || $leave->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->route('admin.leaves.show', $leave)
            ->with('success', 'Leave request rejected.');
    }

    public function cancel(Leave $leave)
    {
        $school = Auth::user()->school;
        if (!$school || $leave->school_id !== $school->id) abort(403);

        $leave->update(['status' => 'cancelled']);

        return redirect()->route('admin.leaves.show', $leave)
            ->with('success', 'Leave request cancelled.');
    }

    public function destroy(Leave $leave)
    {
        $school = Auth::user()->school;
        if (!$school || $leave->school_id !== $school->id) abort(403);

        $leave->delete();

        return redirect()->route('admin.leaves.index')
            ->with('success', 'Leave record deleted.');
    }
}
