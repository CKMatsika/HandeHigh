<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Models\AllowanceType;
use App\Models\EmployeeAllowance;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    // Zimbabwe PAYE tax brackets (USD, 2025/26)
    const PAYE_BRACKETS = [
        ['min' => 0,     'max' => 300,    'rate' => 0],
        ['min' => 300.01, 'max' => 1000,   'rate' => 20],
        ['min' => 1000.01, 'max' => 2000,  'rate' => 25],
        ['min' => 2000.01, 'max' => 3000,  'rate' => 30],
        ['min' => 3000.01, 'max' => 6000,  'rate' => 35],
        ['min' => 6000.01, 'max' => PHP_FLOAT_MAX, 'rate' => 40],
    ];

    const AIDS_LEVY_RATE = 3; // 3% of PAYE
    const NSSA_EMPLOYEE_RATE = 3.5; // 3.5% of pensionable earnings
    const NSSA_EMPLOYER_RATE = 3.5; // 3.5% of pensionable earnings
    const NSSA_MAX_EARNINGS = 700; // Maximum pensionable earnings per month

    public function index(Request $request)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $query = Payroll::where('school_id', $school->id)->with('createdBy');

        if ($request->filled('year')) {
            $query->where('period_year', $request->year);
        }

        $payrolls = $query->latest()->paginate(20);
        $years = Payroll::where('school_id', $school->id)
            ->selectRaw('DISTINCT period_year')->pluck('period_year');

        return view('admin.payrolls.index', compact('school', 'payrolls', 'years'));
    }

    public function create()
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $employees = Employee::where('school_id', $school->id)
            ->active()
            ->with(['activeAllowances.allowanceType', 'activeLoans', 'department'])
            ->get();

        $allowanceTypes = AllowanceType::where('school_id', $school->id)
            ->where('is_active', true)->get();

        return view('admin.payrolls.create', compact('school', 'employees', 'allowanceTypes'));
    }

    public function process(Request $request)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020|max:2030',
            'processed_date' => 'required|date',
            'notes' => 'nullable|string',
            'employees' => 'required|array|min:1',
            'employees.*.id' => 'required|exists:employees,id',
            'employees.*.basic_salary' => 'required|numeric|min:0',
            'employees.*.housing_allowance' => 'nullable|numeric|min:0',
            'employees.*.transport_allowance' => 'nullable|numeric|min:0',
            'employees.*.communication_allowance' => 'nullable|numeric|min:0',
            'employees.*.education_allowance' => 'nullable|numeric|min:0',
            'employees.*.leave_allowance' => 'nullable|numeric|min:0',
            'employees.*.bonus' => 'nullable|numeric|min:0',
            'employees.*.overtime' => 'nullable|numeric|min:0',
            'employees.*.other_earnings' => 'nullable|numeric|min:0',
            'employees.*.other_earnings_desc' => 'nullable|string',
            'employees.*.school_top_up' => 'nullable|numeric|min:0',
            'employees.*.trade_union' => 'nullable|numeric|min:0',
            'employees.*.nec' => 'nullable|numeric|min:0',
            'employees.*.loan_repayment' => 'nullable|numeric|min:0',
            'employees.*.other_deductions' => 'nullable|numeric|min:0',
            'employees.*.other_deductions_desc' => 'nullable|string',
            'employees.*.payment_method' => 'nullable|string',
            'employees.*.bank_account' => 'nullable|string',
        ]);

        // Check if payroll already exists for this period
        $existing = Payroll::where('school_id', $school->id)
            ->where('period_month', $validated['period_month'])
            ->where('period_year', $validated['period_year'])
            ->first();

        if ($existing) {
            return back()->with('error', 'Payroll already exists for this period.');
        }

        try {
            DB::beginTransaction();

            $payroll = Payroll::create([
                'school_id' => $school->id,
                'period_month' => $validated['period_month'],
                'period_year' => $validated['period_year'],
                'processed_date' => $validated['processed_date'],
                'status' => 'draft',
                'created_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            foreach ($validated['employees'] as $empData) {
                $employee = Employee::find($empData['id']);
                if (!$employee || $employee->school_id !== $school->id) continue;

                $basicSalary = $empData['basic_salary'] ?? 0;
                $housing = $empData['housing_allowance'] ?? 0;
                $transport = $empData['transport_allowance'] ?? 0;
                $communication = $empData['communication_allowance'] ?? 0;
                $education = $empData['education_allowance'] ?? 0;
                $leaveAllow = $empData['leave_allowance'] ?? 0;
                $bonus = $empData['bonus'] ?? 0;
                $overtime = $empData['overtime'] ?? 0;
                $otherEarnings = $empData['other_earnings'] ?? 0;
                $schoolTopUp = $empData['school_top_up'] ?? 0;

                $grossPay = $basicSalary + $housing + $transport + $communication
                    + $education + $leaveAllow + $bonus + $overtime
                    + $otherEarnings + $schoolTopUp;

                // --- Statutory Deductions ---

                // NSSA (pensionable earnings capped)
                $pensionableEarnings = min($basicSalary, self::NSSA_MAX_EARNINGS);
                $nssaEmployee = round($pensionableEarnings * self::NSSA_EMPLOYEE_RATE / 100, 2);
                $nssaEmployer = round($pensionableEarnings * self::NSSA_EMPLOYER_RATE / 100, 2);

                // PAYE (taxable pay = gross pay)
                $taxablePay = $grossPay;
                $paye = $this->calculatePAYE($taxablePay);

                // AIDS Levy = 3% of PAYE
                $aidsLevy = round($paye * self::AIDS_LEVY_RATE / 100, 2);

                // Other deductions
                $tradeUnion = $empData['trade_union'] ?? 0;
                $nec = $empData['nec'] ?? 0;
                $loanRepayment = $empData['loan_repayment'] ?? 0;
                $otherDeductions = $empData['other_deductions'] ?? 0;

                $totalDeductionAmount = $paye + $aidsLevy + $nssaEmployee
                    + $tradeUnion + $nec + $loanRepayment + $otherDeductions;

                $netPay = max(0, $grossPay - $totalDeductionAmount);

                $item = PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'housing_allowance' => $housing,
                    'transport_allowance' => $transport,
                    'communication_allowance' => $communication,
                    'education_allowance' => $education,
                    'leave_allowance' => $leaveAllow,
                    'bonus' => $bonus,
                    'overtime' => $overtime,
                    'other_earnings' => $otherEarnings,
                    'other_earnings_desc' => $empData['other_earnings_desc'] ?? null,
                    'school_top_up' => $schoolTopUp,
                    'gross_pay' => $grossPay,
                    'paye' => $paye,
                    'aids_levy' => $aidsLevy,
                    'nssa_employee' => $nssaEmployee,
                    'nssa_employer' => $nssaEmployer,
                    'trade_union' => $tradeUnion,
                    'nec' => $nec,
                    'loan_repayment' => $loanRepayment,
                    'other_deductions' => $otherDeductions,
                    'other_deductions_desc' => $empData['other_deductions_desc'] ?? null,
                    'total_deductions' => $totalDeductionAmount,
                    'net_pay' => $netPay,
                    'payment_method' => $empData['payment_method'] ?? 'bank_transfer',
                    'bank_account' => $empData['bank_account'] ?? null,
                    'status' => 'active',
                ]);

                // Record loan repayment against active loans
                if ($loanRepayment > 0) {
                    $activeLoan = Loan::where('employee_id', $employee->id)
                        ->where('status', 'active')->first();
                    if ($activeLoan) {
                        $activeLoan->increment('total_paid', $loanRepayment);
                        $activeLoan->decrement('balance', $loanRepayment);
                        if ($activeLoan->balance <= 0) {
                            $activeLoan->update(['status' => 'settled', 'settled_date' => now()]);
                        }
                        \App\Models\LoanRepayment::create([
                            'loan_id' => $activeLoan->id,
                            'payroll_item_id' => $item->id,
                            'amount' => $loanRepayment,
                            'payment_date' => $validated['processed_date'],
                            'payment_method' => 'payroll_deduction',
                        ]);
                    }
                }

                $totalGross += $grossPay;
                $totalDeductions += $totalDeductionAmount;
                $totalNet += $netPay;
            }

            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
            ]);

            DB::commit();

            return redirect()->route('admin.payrolls.show', $payroll)
                ->with('success', 'Payroll processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payroll processing failed: ' . $e->getMessage());
        }
    }

    public function show(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        $payroll->load(['items.employee.department', 'createdBy']);

        return view('admin.payrolls.show', compact('school', 'payroll'));
    }

    public function approve(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        $payroll->update(['status' => 'processed']);

        return redirect()->route('admin.payrolls.show', $payroll)
            ->with('success', 'Payroll approved.');
    }

    public function markPaid(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        $payroll->update(['status' => 'paid']);
        $payroll->items()->update(['status' => 'paid']);

        return redirect()->route('admin.payrolls.show', $payroll)
            ->with('success', 'Payroll marked as paid.');
    }

    public function destroy(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        if ($payroll->status === 'paid') {
            return back()->with('error', 'Cannot delete a paid payroll.');
        }

        $payroll->items()->delete();
        $payroll->delete();

        return redirect()->route('admin.payrolls.index')
            ->with('success', 'Payroll deleted.');
    }

    /**
     * Calculate Zimbabwe PAYE tax using progressive brackets.
     */
    private function calculatePAYE($taxableIncome)
    {
        $tax = 0;
        foreach (self::PAYE_BRACKETS as $bracket) {
            if ($taxableIncome > $bracket['min']) {
                $incomeInBracket = min($taxableIncome, $bracket['max']) - $bracket['min'];
                $tax += $incomeInBracket * $bracket['rate'] / 100;
            }
        }
        return round($tax, 2);
    }
}
