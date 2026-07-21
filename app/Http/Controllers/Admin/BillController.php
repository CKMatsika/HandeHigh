<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Vendor;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $bills = Bill::where('school_id', $school->id)
            ->with('vendor')
            ->when($request->status, function($query, $status) {
                if ($status === 'pending') {
                    $query->where('status', 'pending');
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.bills.index', compact('bills'));
    }

    public function create()
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $vendors = Vendor::where('school_id', $school->id)->get();
        return view('admin.bills.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'bill_number' => ['required', 'string', 'max:255'],
            'vendor_bill_number' => ['nullable', 'string', 'max:255'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after:bill_date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $bill = Bill::create([
            'school_id' => $school->id,
            'vendor_id' => $validated['vendor_id'],
            'bill_number' => $validated['bill_number'],
            'vendor_bill_number' => $validated['vendor_bill_number'],
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'],
            'total_amount' => $validated['total_amount'],
            'paid_amount' => 0,
            'balance' => $validated['total_amount'],
            'status' => 'pending',
            'description' => $validated['description'],
            'notes' => $validated['notes'],
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('admin.bills.index')
            ->with('status', 'Bill created successfully');
    }

    public function show(Bill $bill)
    {
        $this->authorizeSchoolAccess($bill);
        
        $bill->load(['vendor', 'items', 'payments']);
        return view('admin.bills.show', compact('bill'));
    }

    public function edit(Bill $bill)
    {
        $this->authorizeSchoolAccess($bill);
        
        $user = auth()->user();
        $school = $user?->school;

        $vendors = Vendor::where('school_id', $school->id)->get();
        return view('admin.bills.edit', compact('bill', 'vendors'));
    }

    public function update(Request $request, Bill $bill)
    {
        $this->authorizeSchoolAccess($bill);

        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'bill_number' => ['required', 'string', 'max:255'],
            'vendor_bill_number' => ['nullable', 'string', 'max:255'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after:bill_date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $bill->update($validated);

        return redirect()
            ->route('admin.bills.index')
            ->with('status', 'Bill updated successfully');
    }

    private function authorizeSchoolAccess($model)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school || $model->school_id !== $school->id) {
            abort(403);
        }
    }
}
