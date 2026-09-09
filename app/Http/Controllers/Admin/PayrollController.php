<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Models\AllowanceType;
use App\Models\Loan;
use App\Rules\TenantExists;
use App\Services\AccountingService;
use App\Services\Payroll\ZimbabwePayrollService;
use App\Services\Payroll\TarmsExportService;
use App\Services\Payroll\ComplianceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PayrollController extends Controller
{
    protected ZimbabwePayrollService $payrollService;
    protected TarmsExportService $tarmsExportService;
    protected ComplianceReportService $complianceReportService;
    protected AccountingService $accountingService;

    public function __construct(
        ZimbabwePayrollService $payrollService,
        TarmsExportService $tarmsExportService,
        ComplianceReportService $complianceReportService,
        AccountingService $accountingService
    ) {
        $this->payrollService = $payrollService;
        $this->tarmsExportService = $tarmsExportService;
        $this->complianceReportService = $complianceReportService;
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $school = Auth::user()->school;
        if (!$school) abort(403);

        $query = Payroll::where('school_id', $school->id)->with(['createdBy', 'journalBatch']);

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

        $statutoryConfig = $this->payrollService->loadStatutoryRates($school->id);

        return view('admin.payrolls.create', compact('school', 'employees', 'allowanceTypes', 'statutoryConfig'));
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
            'employees.*.id' => ['required', TenantExists::make('employees')],

            // USD Earnings & Deductions
            'employees.*.basic_salary' => 'nullable|numeric|min:0',
            'employees.*.basic_salary_usd' => 'nullable|numeric|min:0',
            'employees.*.housing_allowance' => 'nullable|numeric|min:0',
            'employees.*.housing_allowance_usd' => 'nullable|numeric|min:0',
            'employees.*.transport_allowance' => 'nullable|numeric|min:0',
            'employees.*.transport_allowance_usd' => 'nullable|numeric|min:0',
            'employees.*.communication_allowance' => 'nullable|numeric|min:0',
            'employees.*.communication_allowance_usd' => 'nullable|numeric|min:0',
            'employees.*.education_allowance' => 'nullable|numeric|min:0',
            'employees.*.education_allowance_usd' => 'nullable|numeric|min:0',
            'employees.*.leave_allowance' => 'nullable|numeric|min:0',
            'employees.*.leave_allowance_usd' => 'nullable|numeric|min:0',
            'employees.*.bonus' => 'nullable|numeric|min:0',
            'employees.*.bonus_usd' => 'nullable|numeric|min:0',
            'employees.*.overtime' => 'nullable|numeric|min:0',
            'employees.*.overtime_usd' => 'nullable|numeric|min:0',
            'employees.*.other_earnings' => 'nullable|numeric|min:0',
            'employees.*.other_earnings_usd' => 'nullable|numeric|min:0',
            'employees.*.other_earnings_desc' => 'nullable|string',
            'employees.*.school_top_up' => 'nullable|numeric|min:0',
            'employees.*.school_top_up_usd' => 'nullable|numeric|min:0',
            'employees.*.trade_union' => 'nullable|numeric|min:0',
            'employees.*.trade_union_usd' => 'nullable|numeric|min:0',
            'employees.*.nec' => 'nullable|numeric|min:0',
            'employees.*.nec_usd' => 'nullable|numeric|min:0',
            'employees.*.medical_aid_usd' => 'nullable|numeric|min:0',
            'employees.*.loan_repayment' => 'nullable|numeric|min:0',
            'employees.*.loan_repayment_usd' => 'nullable|numeric|min:0',
            'employees.*.other_deductions' => 'nullable|numeric|min:0',
            'employees.*.other_deductions_usd' => 'nullable|numeric|min:0',
            'employees.*.other_deductions_desc' => 'nullable|string',

            // ZWG Earnings & Deductions
            'employees.*.basic_salary_zwg' => 'nullable|numeric|min:0',
            'employees.*.housing_allowance_zwg' => 'nullable|numeric|min:0',
            'employees.*.transport_allowance_zwg' => 'nullable|numeric|min:0',
            'employees.*.communication_allowance_zwg' => 'nullable|numeric|min:0',
            'employees.*.education_allowance_zwg' => 'nullable|numeric|min:0',
            'employees.*.leave_allowance_zwg' => 'nullable|numeric|min:0',
            'employees.*.bonus_zwg' => 'nullable|numeric|min:0',
            'employees.*.overtime_zwg' => 'nullable|numeric|min:0',
            'employees.*.other_earnings_zwg' => 'nullable|numeric|min:0',
            'employees.*.school_top_up_zwg' => 'nullable|numeric|min:0',
            'employees.*.trade_union_zwg' => 'nullable|numeric|min:0',
            'employees.*.nec_zwg' => 'nullable|numeric|min:0',
            'employees.*.medical_aid_zwg' => 'nullable|numeric|min:0',
            'employees.*.loan_repayment_zwg' => 'nullable|numeric|min:0',
            'employees.*.other_deductions_zwg' => 'nullable|numeric|min:0',

            // Compliance options
            'employees.*.nec_sector_code' => 'nullable|string',
            'employees.*.trade_union_member' => 'nullable|boolean',
            'employees.*.payment_method' => 'nullable|string',
            'employees.*.bank_account' => 'nullable|string',
        ]);

        // Strict Check: Check if payroll already exists for this period (Immutable anti-tampering)
        $existing = Payroll::where('school_id', $school->id)
            ->where('period_month', $validated['period_month'])
            ->where('period_year', $validated['period_year'])
            ->first();

        if ($existing) {
            return back()->with('error', "Payroll for period {$validated['period_year']}/{$validated['period_month']} already exists and is locked against duplicate processing.");
        }

        try {
            DB::beginTransaction();

            $payroll = Payroll::create([
                'school_id' => $school->id,
                'period_month' => $validated['period_month'],
                'period_year' => $validated['period_year'],
                'processed_date' => $validated['processed_date'],
                'status' => 'draft',
                'is_locked' => false,
                'created_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalGrossUsd = 0;
            $totalGrossZwg = 0;
            $totalDeductionsUsd = 0;
            $totalDeductionsZwg = 0;
            $totalNetUsd = 0;
            $totalNetZwg = 0;
            $totalPayeUsd = 0;
            $totalPayeZwg = 0;
            $totalAidsUsd = 0;
            $totalAidsZwg = 0;
            $totalEmployerNssaUsd = 0;
            $totalEmployerNssaZwg = 0;
            $totalEmployerNecUsd = 0;
            $totalEmployerNecZwg = 0;

            foreach ($validated['employees'] as $empData) {
                $employee = Employee::find($empData['id']);
                if (!$employee || $employee->school_id !== $school->id) continue;

                // Run Zimbabwean Compliance & Tax Engine
                $calc = $this->payrollService->calculateEmployeePayroll(
                    $employee,
                    $empData,
                    $school->id,
                    $validated['processed_date']
                );

                $item = PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'currency_mode' => $calc['currency_mode'],

                    // USD Breakdown
                    'basic_salary_usd' => $calc['basic_salary_usd'],
                    'allowances_usd' => $calc['allowances_usd'],
                    'bonus_usd' => $calc['bonus_usd'],
                    'overtime_usd' => $calc['overtime_usd'],
                    'gross_usd' => $calc['gross_usd'],
                    'paye_usd' => $calc['paye_usd'],
                    'aids_levy_usd' => $calc['aids_levy_usd'],
                    'nssa_employee_usd' => $calc['nssa_employee_usd'],
                    'nssa_employer_usd' => $calc['nssa_employer_usd'],
                    'nec_employee_usd' => $calc['nec_employee_usd'],
                    'nec_employer_usd' => $calc['nec_employer_usd'],
                    'trade_union_usd' => $calc['trade_union_usd'],
                    'medical_aid_usd' => $calc['medical_aid_usd'],
                    'medical_aid_tax_credit_usd' => $calc['medical_aid_tax_credit_usd'],
                    'loan_repayment_usd' => $calc['loan_repayment_usd'],
                    'other_deductions_usd' => $calc['other_deductions_usd'],
                    'total_deductions_usd' => $calc['total_deductions_usd'],
                    'net_pay_usd' => $calc['net_pay_usd'],

                    // ZWG Breakdown
                    'basic_salary_zwg' => $calc['basic_salary_zwg'],
                    'allowances_zwg' => $calc['allowances_zwg'],
                    'bonus_zwg' => $calc['bonus_zwg'],
                    'overtime_zwg' => $calc['overtime_zwg'],
                    'gross_zwg' => $calc['gross_zwg'],
                    'paye_zwg' => $calc['paye_zwg'],
                    'aids_levy_zwg' => $calc['aids_levy_zwg'],
                    'nssa_employee_zwg' => $calc['nssa_employee_zwg'],
                    'nssa_employer_zwg' => $calc['nssa_employer_zwg'],
                    'nec_employee_zwg' => $calc['nec_employee_zwg'],
                    'nec_employer_zwg' => $calc['nec_employer_zwg'],
                    'trade_union_zwg' => $calc['trade_union_zwg'],
                    'medical_aid_zwg' => $calc['medical_aid_zwg'],
                    'medical_aid_tax_credit_zwg' => $calc['medical_aid_tax_credit_zwg'],
                    'loan_repayment_zwg' => $calc['loan_repayment_zwg'],
                    'other_deductions_zwg' => $calc['other_deductions_zwg'],
                    'total_deductions_zwg' => $calc['total_deductions_zwg'],
                    'net_pay_zwg' => $calc['net_pay_zwg'],

                    // Legacy Compatibility Mapping
                    'basic_salary' => $calc['basic_salary'],
                    'housing_allowance' => $empData['housing_allowance'] ?? $empData['housing_allowance_usd'] ?? 0,
                    'transport_allowance' => $empData['transport_allowance'] ?? $empData['transport_allowance_usd'] ?? 0,
                    'communication_allowance' => $empData['communication_allowance'] ?? $empData['communication_allowance_usd'] ?? 0,
                    'education_allowance' => $empData['education_allowance'] ?? $empData['education_allowance_usd'] ?? 0,
                    'leave_allowance' => $empData['leave_allowance'] ?? $empData['leave_allowance_usd'] ?? 0,
                    'bonus' => $calc['bonus_usd'] ?: $calc['bonus_zwg'],
                    'overtime' => $calc['overtime_usd'] ?: $calc['overtime_zwg'],
                    'other_earnings' => $empData['other_earnings'] ?? $empData['other_earnings_usd'] ?? 0,
                    'other_earnings_desc' => $empData['other_earnings_desc'] ?? null,
                    'school_top_up' => $empData['school_top_up'] ?? $empData['school_top_up_usd'] ?? 0,
                    'gross_pay' => $calc['gross_pay'],
                    'paye' => $calc['paye'],
                    'aids_levy' => $calc['aids_levy'],
                    'nssa_employee' => $calc['nssa_employee'],
                    'nssa_employer' => $calc['nssa_employer'],
                    'trade_union' => $calc['trade_union'],
                    'nec' => $calc['nec'],
                    'loan_repayment' => $calc['loan_repayment'],
                    'other_deductions' => $calc['other_deductions'],
                    'other_deductions_desc' => $empData['other_deductions_desc'] ?? null,
                    'total_deductions' => $calc['total_deductions'],
                    'net_pay' => $calc['net_pay'],
                    'payment_method' => $empData['payment_method'] ?? 'bank_transfer',
                    'bank_account' => $empData['bank_account'] ?? null,
                    'status' => 'active',

                    // Statutory Leave Snapshot (Zimbabwe Labour Act: 2.5 days per month)
                    'leave_days_accrued' => $calc['leave_days_accrued'],
                    'leave_days_taken' => $calc['leave_days_taken'],
                    'leave_balance' => $calc['leave_balance'],
                ]);

                // Record loan repayments against active loans
                $loanDeductionTotal = $calc['loan_repayment_usd'] + $calc['loan_repayment_zwg'];
                if ($loanDeductionTotal > 0) {
                    $activeLoan = Loan::where('employee_id', $employee->id)
                        ->where('status', 'active')->first();
                    if ($activeLoan) {
                        $activeLoan->increment('total_paid', $loanDeductionTotal);
                        $activeLoan->decrement('balance', $loanDeductionTotal);
                        if ($activeLoan->balance <= 0) {
                            $activeLoan->update(['status' => 'settled', 'settled_date' => now()]);
                        }
                        \App\Models\LoanRepayment::create([
                            'loan_id' => $activeLoan->id,
                            'payroll_item_id' => $item->id,
                            'amount' => $loanDeductionTotal,
                            'payment_date' => $validated['processed_date'],
                            'payment_method' => 'payroll_deduction',
                        ]);
                    }
                }

                // Accumulate totals
                $totalGrossUsd += $calc['gross_usd'];
                $totalGrossZwg += $calc['gross_zwg'];
                $totalDeductionsUsd += $calc['total_deductions_usd'];
                $totalDeductionsZwg += $calc['total_deductions_zwg'];
                $totalNetUsd += $calc['net_pay_usd'];
                $totalNetZwg += $calc['net_pay_zwg'];
                $totalPayeUsd += $calc['paye_usd'];
                $totalPayeZwg += $calc['paye_zwg'];
                $totalAidsUsd += $calc['aids_levy_usd'];
                $totalAidsZwg += $calc['aids_levy_zwg'];
                $totalEmployerNssaUsd += $calc['nssa_employer_usd'];
                $totalEmployerNssaZwg += $calc['nssa_employer_zwg'];
                $totalEmployerNecUsd += $calc['nec_employer_usd'];
                $totalEmployerNecZwg += $calc['nec_employer_zwg'];
            }

            $payroll->update([
                'total_gross' => $totalGrossUsd ?: $totalGrossZwg,
                'total_deductions' => $totalDeductionsUsd ?: $totalDeductionsZwg,
                'total_net' => $totalNetUsd ?: $totalNetZwg,
                'total_employer_nssa' => $totalEmployerNssaUsd ?: $totalEmployerNssaZwg,

                'total_gross_usd' => $totalGrossUsd,
                'total_gross_zwg' => $totalGrossZwg,
                'total_deductions_usd' => $totalDeductionsUsd,
                'total_deductions_zwg' => $totalDeductionsZwg,
                'total_net_usd' => $totalNetUsd,
                'total_net_zwg' => $totalNetZwg,
                'total_paye_usd' => $totalPayeUsd,
                'total_paye_zwg' => $totalPayeZwg,
                'total_aids_levy_usd' => $totalAidsUsd,
                'total_aids_levy_zwg' => $totalAidsZwg,
                'total_employer_nssa_usd' => $totalEmployerNssaUsd,
                'total_employer_nssa_zwg' => $totalEmployerNssaZwg,
                'total_employer_nec_usd' => $totalEmployerNecUsd,
                'total_employer_nec_zwg' => $totalEmployerNecZwg,
            ]);

            DB::commit();

            return redirect()->route('admin.payrolls.show', $payroll)
                ->with('success', 'Payroll draft created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payroll processing failed: ' . $e->getMessage());
        }
    }

    public function show(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        $payroll->load(['items.employee.department', 'createdBy', 'journalBatch.entries.account']);

        return view('admin.payrolls.show', compact('school', 'payroll'));
    }

    public function payslip(Payroll $payroll, Employee $employee)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id || $employee->school_id !== $school->id) {
            abort(403);
        }

        $item = $payroll->items()->where('employee_id', $employee->id)->firstOrFail();

        return view('admin.payrolls.payslip', compact('school', 'payroll', 'employee', 'item'));
    }

    public function bulkPayslips(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        $payroll->load(['items.employee.department', 'school']);

        return view('admin.payrolls.bulk_payslips', compact('school', 'payroll'));
    }

    public function emailPayslip(Payroll $payroll, Employee $employee)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id || $employee->school_id !== $school->id) {
            abort(403);
        }

        if (empty($employee->email)) {
            return back()->with('error', "Employee {$employee->first_name} does not have a valid email address.");
        }

        $item = $payroll->items()->where('employee_id', $employee->id)->firstOrFail();

        try {
            Mail::send('emails.payslip', ['school' => $school, 'payroll' => $payroll, 'employee' => $employee, 'item' => $item], function ($message) use ($employee, $payroll, $school) {
                $message->to($employee->email, $employee->first_name . ' ' . $employee->last_name)
                    ->subject("Payslip for " . \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') . " - {$school->name}");
            });

            return back()->with('success', "Payslip successfully dispatched to {$employee->email}.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed to email payslip: " . $e->getMessage());
        }
    }

    public function statutoryReport(Payroll $payroll, Request $request)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        $reportType = $request->query('type', 'zimra-p2');
        $payroll->load(['items.employee.department', 'school']);

        return view('admin.payrolls.statutory_report', compact('school', 'payroll', 'reportType'));
    }

    public function exportStatutory(Payroll $payroll, string $type)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        return match ($type) {
            'tarms', 'zimra' => $this->tarmsExportService->exportCsv($payroll),
            'nssa' => $this->complianceReportService->exportNssaP4Csv($payroll),
            'nec' => $this->complianceReportService->exportNecCsv($payroll),
            'summary', 'master' => $this->complianceReportService->exportMasterSummaryCsv($payroll),
            default => abort(404),
        };
    }

    public function exportTarms(Payroll $payroll)
    {
        return $this->exportStatutory($payroll, 'tarms');
    }

    public function approve(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        if ($payroll->is_locked) {
            return back()->with('error', 'This payroll is already locked and cannot be re-approved.');
        }

        try {
            DB::beginTransaction();

            // 1. Lock and approve payroll
            $payroll->update([
                'status' => 'processed',
                'is_locked' => true,
                'locked_at' => now(),
            ]);

            // 2. Accrue statutory leave (2.5 days per month) to employees
            foreach ($payroll->items as $item) {
                if ($item->employee) {
                    $item->employee->increment('leave_days_accrued', 2.5);
                    $item->employee->increment('leave_balance', 2.5);
                }
            }

            // 3. Automatically post to Accounting General Ledger with Statutory Control Accounts
            $journalBatch = $this->accountingService->postPayroll($payroll);

            DB::commit();

            return redirect()->route('admin.payrolls.show', $payroll)
                ->with('success', "Payroll approved, locked for ZIMRA compliance, leave accrued (+2.5 days), and automatically posted to General Ledger (Batch #{$journalBatch->batch_number}).");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payroll approval failed: ' . $e->getMessage());
        }
    }

    public function markPaid(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        try {
            DB::beginTransaction();

            $payroll->update([
                'status' => 'paid',
                'is_locked' => true,
                'locked_at' => $payroll->locked_at ?? now(),
            ]);
            $payroll->items()->update(['status' => 'paid']);

            // Ensure posted to General Ledger if not already posted
            if (!$payroll->journal_batch_id) {
                $this->accountingService->postPayroll($payroll);
            }

            DB::commit();

            return redirect()->route('admin.payrolls.show', $payroll)
                ->with('success', 'Payroll marked as paid and completed.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Mark paid failed: ' . $e->getMessage());
        }
    }

    public function destroy(Payroll $payroll)
    {
        $school = Auth::user()->school;
        if (!$school || $payroll->school_id !== $school->id) abort(403);

        // Strict Anti-Tampering: Locked or Paid payroll cannot be deleted
        if ($payroll->is_locked || $payroll->status === 'paid' || $payroll->status === 'processed') {
            return back()->with('error', 'Immutable Compliance Violation: Cannot delete an approved, processed, or paid payroll period.');
        }

        $payroll->items()->delete();
        $payroll->delete();

        return redirect()->route('admin.payrolls.index')
            ->with('success', 'Draft payroll deleted.');
    }
}
