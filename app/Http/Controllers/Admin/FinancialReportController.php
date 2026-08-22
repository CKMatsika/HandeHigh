<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DebtorsAgingExport;
use App\Exports\FeeCollectionsExport;
use App\Exports\OutstandingFeesExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Student;
use App\Services\Finance\FinanceReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends Controller
{
    protected FinanceReportingService $reportingService;

    public function __construct(FinanceReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Management Finance Dashboard
     */
    public function dashboard(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $kpis = $this->reportingService->getFinanceDashboardKPIs($school);

        return view('admin.reports.finance-dashboard', compact('kpis'));
    }

    /**
     * Debtors Aging Matrix Report
     */
    public function debtorsAging(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $filters = $request->only([
            'academic_year', 'term', 'form', 'class_name', 'student_id',
            'fee_type', 'min_balance', 'as_of_date', 'group_by', 'start_date', 'end_date'
        ]);

        $reportData = $this->reportingService->getDebtorsAging($school, $filters);

        return view('admin.reports.debtors-aging', $reportData);
    }

    /**
     * Export Debtors Aging to Excel/CSV
     */
    public function exportDebtorsAging(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $filters = $request->only([
            'academic_year', 'term', 'form', 'class_name', 'student_id',
            'fee_type', 'min_balance', 'as_of_date', 'group_by', 'start_date', 'end_date'
        ]);

        $reportData = $this->reportingService->getDebtorsAging($school, $filters);
        $filename = 'debtors-aging-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new DebtorsAgingExport($reportData), $filename);
    }

    /**
     * Student Account Statement (Index / Selector)
     */
    public function studentStatement(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $students = Student::where('school_id', $school->id)->orderBy('first_name')->get();
        $selectedStudentId = $request->input('student_id');
        $statementData = null;

        if ($selectedStudentId) {
            $student = Student::where('school_id', $school->id)->findOrFail($selectedStudentId);
            $filters = $request->only(['academic_year', 'term', 'start_date', 'end_date']);
            $statementData = $this->reportingService->getStudentStatement($school, $student, $filters);
        }

        $years = Invoice::where('school_id', $school->id)->distinct()->pluck('academic_year')->filter()->sortDesc()->values();

        return view('admin.reports.student-statement', compact('students', 'selectedStudentId', 'statementData', 'years'));
    }

    /**
     * Itemized Fee Collections Report
     */
    public function feeCollections(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'payment_method', 'form', 'class_name', 'academic_year', 'term'
        ]);

        $reportData = $this->reportingService->getFeeCollections($school, $filters);

        $availableForms = Student::where('school_id', $school->id)->distinct()->pluck('grade')->filter()->sort()->values();
        $availableClasses = Student::where('school_id', $school->id)->distinct()->pluck('class_name')->filter()->sort()->values();
        $availableYears = Invoice::where('school_id', $school->id)->distinct()->pluck('academic_year')->filter()->sortDesc()->values();

        return view('admin.reports.fee-collections', array_merge($reportData, [
            'available_forms' => $availableForms,
            'available_classes' => $availableClasses,
            'available_years' => $availableYears,
        ]));
    }

    /**
     * Export Fee Collections to Excel/CSV
     */
    public function exportFeeCollections(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $filters = $request->only([
            'start_date', 'end_date', 'payment_method', 'form', 'class_name', 'academic_year', 'term'
        ]);

        $reportData = $this->reportingService->getFeeCollections($school, $filters);
        $filename = 'fee-collections-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new FeeCollectionsExport($reportData), $filename);
    }

    /**
     * Outstanding Fees Register
     */
    public function outstandingFees(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $filters = $request->only([
            'academic_year', 'term', 'form', 'class_name', 'min_balance', 'group_by'
        ]);

        $reportData = $this->reportingService->getOutstandingFees($school, $filters);

        $availableForms = Student::where('school_id', $school->id)->distinct()->pluck('grade')->filter()->sort()->values();
        $availableClasses = Student::where('school_id', $school->id)->distinct()->pluck('class_name')->filter()->sort()->values();
        $availableYears = Invoice::where('school_id', $school->id)->distinct()->pluck('academic_year')->filter()->sortDesc()->values();

        return view('admin.reports.outstanding-fees', array_merge($reportData, [
            'available_forms' => $availableForms,
            'available_classes' => $availableClasses,
            'available_years' => $availableYears,
        ]));
    }

    /**
     * Export Outstanding Fees to Excel/CSV
     */
    public function exportOutstandingFees(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $filters = $request->only([
            'academic_year', 'term', 'form', 'class_name', 'min_balance', 'group_by'
        ]);

        $reportData = $this->reportingService->getOutstandingFees($school, $filters);
        $filename = 'outstanding-fees-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new OutstandingFeesExport($reportData), $filename);
    }

    /**
     * Fee Collection Periodic Summary
     */
    public function collectionSummary(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $filters = $request->only(['year']);
        $reportData = $this->reportingService->getCollectionSummary($school, $filters);

        return view('admin.reports.collection-summary', $reportData);
    }

    /**
     * Income and Expenditure Report
     */
    public function incomeExpenditure(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $filters = $request->only(['start_date', 'end_date']);
        $reportData = $this->reportingService->getIncomeExpenditure($school, $filters);

        return view('admin.reports.income-statement', array_merge($reportData, [
            'totalRevenue' => $reportData['total_income'],
            'totalExpenses' => $reportData['total_expenditure'],
            'netIncome' => $reportData['net_surplus_deficit'],
            'revenueAccounts' => $reportData['revenue_accounts'],
            'expenseAccounts' => $reportData['expense_accounts'],
            'start' => $reportData['start_date'],
            'end' => $reportData['end_date'],
        ]));
    }

    /**
     * Cashbook and Bank Accounts Summary
     */
    public function cashbookSummary(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $reportData = $this->reportingService->getCashbookBankSummary($school);

        return view('admin.reports.cashbook-summary', $reportData);
    }

    // ==========================================
    // Backwards-Compatible Financial Reports
    // ==========================================

    public function trialBalance(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $asOf = $request->input('date', now()->toDateString());

        $accounts = Account::where('school_id', $school->id)
            ->with(['journalEntries' => function ($q) use ($asOf) {
                $q->whereDate('created_at', '<=', $asOf);
            }])
            ->orderBy('code')
            ->get();

        $rows = $accounts->map(function ($account) {
            $debits = $account->journalEntries->where('entry_type', 'debit')->sum('amount');
            $credits = $account->journalEntries->where('entry_type', 'credit')->sum('amount');
            $balance = in_array($account->type, ['asset', 'expense']) ? ($debits - $credits) : ($credits - $debits);

            return [
                'account' => $account,
                'debit' => $balance > 0 ? $balance : 0,
                'credit' => $balance < 0 ? abs($balance) : 0,
            ];
        });

        $totalDebit = $rows->sum('debit');
        $totalCredit = $rows->sum('credit');

        return view('admin.reports.trial-balance', compact('rows', 'totalDebit', 'totalCredit', 'asOf'));
    }

    public function incomeStatement(Request $request)
    {
        return $this->incomeExpenditure($request);
    }

    public function balanceSheet(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $asOf = $request->input('date', now()->toDateString());

        $assets = $this->accountsWithBalances($school->id, 'asset', null, $asOf);
        $liabilities = $this->accountsWithBalances($school->id, 'liability', null, $asOf);
        $equity = $this->accountsWithBalances($school->id, 'equity', null, $asOf);

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        return view('admin.reports.balance-sheet', compact(
            'assets',
            'liabilities',
            'equity',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'asOf'
        ));
    }

    public function budgetVsActual(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $fiscalYear = $request->input('fiscal_year', now()->year);
        
        $budgets = Budget::where('school_id', $school->id)
            ->where('fiscal_year', $fiscalYear)
            ->with('budgetLines')
            ->get();

        $budgetComparison = [];
        $totalBudget = 0;
        $totalActual = 0;
        $totalVariance = 0;

        foreach ($budgets as $budget) {
            foreach ($budget->budgetLines ?? [] as $line) {
                $actualAmount = $this->getActualExpense($school->id, $line->account_id, $fiscalYear);
                $variance = $line->budgeted_amount - $actualAmount;
                $variancePercent = $line->budgeted_amount > 0 ? ($variance / $line->budgeted_amount) * 100 : 0;

                $budgetComparison[] = [
                    'budget_name' => $budget->name,
                    'account' => $line->account,
                    'budgeted' => $line->budgeted_amount,
                    'actual' => $actualAmount,
                    'variance' => $variance,
                    'variance_percent' => $variancePercent,
                ];

                $totalBudget += $line->budgeted_amount;
                $totalActual += $actualAmount;
                $totalVariance += $variance;
            }
        }

        $totalVariancePercent = $totalBudget > 0 ? ($totalVariance / $totalBudget) * 100 : 0;

        return view('admin.reports.budget-vs-actual', compact(
            'budgetComparison',
            'totalBudget',
            'totalActual',
            'totalVariance',
            'totalVariancePercent',
            'fiscalYear'
        ));
    }

    protected function getActualExpense(int $schoolId, int $accountId, int $fiscalYear): float
    {
        return JournalEntry::whereHas('account', function ($q) use ($schoolId, $accountId) {
                $q->where('school_id', $schoolId)
                  ->where('id', $accountId);
            })
            ->whereHas('batch', function ($q) use ($schoolId, $fiscalYear) {
                $q->where('school_id', $schoolId)
                  ->whereYear('transaction_date', $fiscalYear);
            })
            ->where('entry_type', 'debit')
            ->sum('amount');
    }

    public function departmentalPerformance(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $fiscalYear = $request->input('fiscal_year', now()->year);
        
        $departments = Department::where('school_id', $school->id)
            ->where('is_active', true)
            ->get();

        $departmentPerformance = [];
        $totalBudget = 0;
        $totalExpenses = 0;
        $totalRevenue = 0;

        foreach ($departments as $department) {
            $budgetAmount = $department->budget ?? 0;
            $expenses = $this->getDepartmentExpenses($school->id, $department->id, $fiscalYear);
            $revenue = $this->getDepartmentRevenue($school->id, $department->id, $fiscalYear);
            
            $budgetUtilization = $budgetAmount > 0 ? ($expenses / $budgetAmount) * 100 : 0;
            $netPerformance = $revenue - $expenses;
            $efficiencyRatio = $expenses > 0 ? ($revenue / $expenses) * 100 : 0;

            $departmentPerformance[] = [
                'department' => $department,
                'budget' => $budgetAmount,
                'expenses' => $expenses,
                'revenue' => $revenue,
                'budget_utilization' => $budgetUtilization,
                'net_performance' => $netPerformance,
                'efficiency_ratio' => $efficiencyRatio,
            ];

            $totalBudget += $budgetAmount;
            $totalExpenses += $expenses;
            $totalRevenue += $revenue;
        }

        $overallBudgetUtilization = $totalBudget > 0 ? ($totalExpenses / $totalBudget) * 100 : 0;
        $overallNetPerformance = $totalRevenue - $totalExpenses;
        $overallEfficiencyRatio = $totalExpenses > 0 ? ($totalRevenue / $totalExpenses) * 100 : 0;

        return view('admin.reports.departmental-performance', compact(
            'departmentPerformance',
            'totalBudget',
            'totalExpenses',
            'totalRevenue',
            'overallBudgetUtilization',
            'overallNetPerformance',
            'overallEfficiencyRatio',
            'fiscalYear'
        ));
    }

    protected function getDepartmentExpenses(int $schoolId, int $departmentId, int $fiscalYear): float
    {
        return JournalEntry::whereHas('batch', function ($q) use ($schoolId, $fiscalYear) {
                $q->where('school_id', $schoolId)
                  ->whereYear('transaction_date', $fiscalYear);
            })
            ->where('cost_center_id', $departmentId)
            ->where('entry_type', 'debit')
            ->sum('amount');
    }

    protected function getDepartmentRevenue(int $schoolId, int $departmentId, int $fiscalYear): float
    {
        return JournalEntry::whereHas('batch', function ($q) use ($schoolId, $fiscalYear) {
                $q->where('school_id', $schoolId)
                  ->whereYear('transaction_date', $fiscalYear);
            })
            ->where('cost_center_id', $departmentId)
            ->where('entry_type', 'credit')
            ->sum('amount');
    }

    public function expenseAnalysis(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->toDateString());
        $groupBy = $request->input('group_by', 'category');

        $expenseAccounts = Account::where('school_id', $school->id)
            ->where('type', 'expense')
            ->with(['journalEntries' => function ($q) use ($school, $start, $end) {
                $q->whereHas('batch', function ($subQ) use ($school, $start, $end) {
                    $subQ->where('school_id', $school->id)
                          ->whereBetween('transaction_date', [$start, $end]);
                });
            }])
            ->get();

        if ($groupBy === 'category') {
            $expenses = $expenseAccounts->groupBy('category')
                ->map(function ($categoryAccounts) {
                    $total = $categoryAccounts->sum(function ($account) {
                        return $account->journalEntries->where('entry_type', 'debit')->sum('amount');
                    });
                    
                    return [
                        'total' => $total,
                        'count' => $categoryAccounts->count(),
                        'accounts' => $categoryAccounts,
                    ];
                })
                ->sortByDesc('total');
        } elseif ($groupBy === 'month') {
            $expenses = collect();
            $expenseAccounts->each(function ($account) use (&$expenses) {
                $account->journalEntries->where('entry_type', 'debit')
                    ->each(function ($entry) use (&$expenses, $account) {
                        $month = $entry->batch->transaction_date->format('Y-m');
                        if (!$expenses->has($month)) {
                            $expenses->put($month, collect());
                        }
                        $expenses[$month]->push($entry);
                    });
            });

            $expenses = $expenses->map(function ($monthEntries) {
                return [
                    'total' => $monthEntries->sum('amount'),
                    'count' => $monthEntries->count(),
                    'entries' => $monthEntries,
                ];
            })->sortByDesc(function ($item) {
                return $item['total'];
            });
        } else {
            $expenses = $expenseAccounts->map(function ($account) {
                $total = $account->journalEntries->where('entry_type', 'debit')->sum('amount');
                
                return [
                    'account' => $account,
                    'total' => $total,
                    'count' => $account->journalEntries->where('entry_type', 'debit')->count(),
                ];
            })->sortByDesc('total');
        }

        $totalExpenses = $expenses->sum('total');
        $topExpense = $expenses->first();
        $averageExpense = $expenses->isNotEmpty() ? $totalExpenses / $expenses->count() : 0;

        return view('admin.reports.expense-analysis', compact(
            'expenses',
            'totalExpenses',
            'topExpense',
            'averageExpense',
            'groupBy',
            'start',
            'end'
        ));
    }

    public function studentFeeCollection(Request $request)
    {
        return $this->feeCollections($request);
    }

    public function agedReceivables(Request $request)
    {
        return $this->debtorsAging($request);
    }

    public function generalLedger(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->toDateString());
        $accountId = $request->input('account_id');

        $query = JournalEntry::with(['account', 'batch'])
            ->whereHas('batch', function ($q) use ($school, $start, $end) {
                $q->where('school_id', $school->id)
                  ->whereBetween('transaction_date', [$start, $end]);
            });

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $entries = $query->orderBy('created_at', 'desc')->paginate(50);

        $accounts = Account::where('school_id', $school->id)
            ->orderBy('code')
            ->pluck('name', 'id');

        return view('admin.reports.general-ledger', compact(
            'entries',
            'accounts',
            'accountId',
            'start',
            'end'
        ));
    }

    public function cashFlowStatement(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $start = $request->input('start_date', now()->startOfYear()->toDateString());
        $end = $request->input('end_date', now()->toDateString());

        $operatingInflows = $this->getCashFlowByCategory($school->id, 'operating', 'inflow', $start, $end);
        $operatingOutflows = $this->getCashFlowByCategory($school->id, 'operating', 'outflow', $start, $end);
        $netOperating = $operatingInflows - $operatingOutflows;

        $investingInflows = $this->getCashFlowByCategory($school->id, 'investing', 'inflow', $start, $end);
        $investingOutflows = $this->getCashFlowByCategory($school->id, 'investing', 'outflow', $start, $end);
        $netInvesting = $investingInflows - $investingOutflows;

        $financingInflows = $this->getCashFlowByCategory($school->id, 'financing', 'inflow', $start, $end);
        $financingOutflows = $this->getCashFlowByCategory($school->id, 'financing', 'outflow', $start, $end);
        $netFinancing = $financingInflows - $financingOutflows;

        $netCashFlow = $netOperating + $netInvesting + $netFinancing;

        return view('admin.reports.cash-flow', compact(
            'operatingInflows',
            'operatingOutflows',
            'netOperating',
            'investingInflows',
            'investingOutflows',
            'netInvesting',
            'financingInflows',
            'financingOutflows',
            'netFinancing',
            'netCashFlow',
            'start',
            'end'
        ));
    }

    protected function getCashFlowByCategory(int $schoolId, string $activity, string $flowType, string $startDate, string $endDate): float
    {
        $cashAccountCodes = ['1100', '1301', '1302', '1303'];
        
        $query = JournalEntry::whereHas('account', function ($q) use ($schoolId, $cashAccountCodes) {
                $q->where('school_id', $schoolId)
                  ->whereIn('code', $cashAccountCodes);
            })
            ->whereHas('batch', function ($q) use ($schoolId, $startDate, $endDate) {
                $q->where('school_id', $schoolId)
                  ->whereBetween('transaction_date', [$startDate, $endDate]);
            });

        if ($flowType === 'inflow') {
            $query->where('entry_type', 'debit');
        } else {
            $query->where('entry_type', 'credit');
        }

        return $query->sum('amount');
    }

    protected function accountsWithBalances(int $schoolId, string $type, ?string $startDate, ?string $endDate)
    {
        return Account::where('school_id', $schoolId)
            ->where('type', $type)
            ->with(['journalEntries' => function ($q) use ($startDate, $endDate) {
                if ($startDate) {
                    $q->whereDate('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $q->whereDate('created_at', '<=', $endDate);
                }
            }])
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                $debits = $account->journalEntries->where('entry_type', 'debit')->sum('amount');
                $credits = $account->journalEntries->where('entry_type', 'credit')->sum('amount');
                $balance = in_array($account->type, ['asset', 'expense']) ? ($debits - $credits) : ($credits - $debits);
                $account->calculated_balance = $balance;
                return (object) [
                    'account' => $account,
                    'balance' => $balance,
                ];
            });
    }
}
