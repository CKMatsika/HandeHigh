<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Employee;
use App\Rules\TenantExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $query = Loan::where('school_id', $school->id)->with('employee');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('loan_type')) {
            $query->where('loan_type', $request->loan_type);
        }

        $loans = $query->latest()->paginate(20);
        $employees = Employee::where('school_id', $school->id)->active()->get();

        return view('admin.loans.index', compact('school', 'loans', 'employees'));
    }

    public function create()
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $employees = Employee::where('school_id', $school->id)->active()->get();

        return view('admin.loans.create', compact('school', 'employees'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'employee_id' => ['required', TenantExists::make('employees')],
            'loan_type' => 'required|in:school,bank,sacco,other',
            'loan_provider' => 'nullable|string|max:255',
            'loan_amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'repayment_period_months' => 'required|integer|min:1',
            'disbursed_date' => 'required|date',
            'first_payment_date' => 'nullable|date',
            'purpose' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $interest = $validated['loan_amount'] * ($validated['interest_rate'] / 100);
        $totalAmount = $validated['loan_amount'] + $interest;
        $monthlyInstallment = round($totalAmount / $validated['repayment_period_months'], 2);

        Loan::create([
            'school_id' => $school->id,
            'employee_id' => $validated['employee_id'],
            'loan_type' => $validated['loan_type'],
            'loan_provider' => $validated['loan_provider'],
            'loan_amount' => $validated['loan_amount'],
            'interest_rate' => $validated['interest_rate'],
            'repayment_period_months' => $validated['repayment_period_months'],
            'monthly_installment' => $monthlyInstallment,
            'total_paid' => 0,
            'balance' => $validated['loan_amount'] + $interest,
            'status' => 'active',
            'disbursed_date' => $validated['disbursed_date'],
            'first_payment_date' => $validated['first_payment_date'],
            'purpose' => $validated['purpose'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.loans.index')
            ->with('success', 'Loan created successfully.');
    }

    public function show(Loan $loan)
    {
        $school = Auth::user()->school;
        if (!$school || $loan->school_id !== $school->id) abort(403);

        $loan->load(['employee', 'repayments']);

        return view('admin.loans.show', compact('school', 'loan'));
    }

    public function settle(Loan $loan)
    {
        $school = Auth::user()->school;
        if (!$school || $loan->school_id !== $school->id) abort(403);

        if ($loan->status !== 'active') {
            return back()->with('error', 'Loan is not active.');
        }

        $loan->update([
            'status' => 'settled',
            'settled_date' => now(),
            'balance' => 0,
        ]);

        return redirect()->route('admin.loans.show', $loan)
            ->with('success', 'Loan marked as settled.');
    }

    public function recordRepayment(Request $request, Loan $loan)
    {
        $school = Auth::user()->school;
        if (!$school || $loan->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,payroll_deduction,other',
            'notes' => 'nullable|string',
        ]);

        if ($validated['amount'] > $loan->balance) {
            return back()->with('error', 'Repayment amount exceeds remaining balance.');
        }

        LoanRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $loan->increment('total_paid', $validated['amount']);
        $loan->decrement('balance', $validated['amount']);

        if ($loan->balance <= 0) {
            $loan->update(['status' => 'settled', 'settled_date' => now()]);
        }

        return redirect()->route('admin.loans.show', $loan)
            ->with('success', 'Repayment recorded successfully.');
    }
}
