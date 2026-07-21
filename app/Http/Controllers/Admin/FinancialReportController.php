<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Invoice;
use App\Models\Budget;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
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
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $start = $request->input('start_date', now()->startOfYear()->toDateString());
        $end = $request->input('end_date', now()->toDateString());

        $revenueAccounts = $this->accountsWithBalances($school->id, 'revenue', $start, $end);
        $expenseAccounts = $this->accountsWithBalances($school->id, 'expense', $start, $end);

        $totalRevenue = $revenueAccounts->sum('balance');
        $totalExpenses = $expenseAccounts->sum('balance');
        $netIncome = $totalRevenue - $totalExpenses;

        return view('admin.reports.income-statement', compact(
            'revenueAccounts',
            'expenseAccounts',
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'start',
            'end'
        ));
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
        // This would need to be enhanced based on your actual department-account relationships
        // For now, we'll assume expenses are tagged to departments via cost_center_id
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
        // This would need to be enhanced based on your actual department-account relationships
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
        $groupBy = $request->input('group_by', 'category'); // category, month, account

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
        } else { // account
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
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->toDateString());
        $groupBy = $request->input('group_by', 'month'); // month, class, fee_type

        $query = Invoice::where('school_id', $school->id)
            ->whereBetween('issued_at', [$start, $end])
            ->with(['student']);

        if ($groupBy === 'class') {
            $collections = $query->get()
                ->groupBy(function ($invoice) {
                    // Get class name through student's enrollment or class_name field
                    return $invoice->student?->class_name ?? 'Unassigned';
                })
                ->map(function ($classInvoices) {
                    $totalBilled = $classInvoices->sum('total_amount');
                    $totalCollected = $classInvoices->sum('total_amount') - $classInvoices->sum('balance');
                    $collectionRate = $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;
                    
                    return [
                        'total_billed' => $totalBilled,
                        'total_collected' => $totalCollected,
                        'balance' => $totalBilled - $totalCollected,
                        'collection_rate' => $collectionRate,
                        'invoice_count' => $classInvoices->count(),
                    ];
                });
        } elseif ($groupBy === 'fee_type') {
            $collections = $query->get()
                ->groupBy('type')
                ->map(function ($typeInvoices) {
                    $totalBilled = $typeInvoices->sum('total_amount');
                    $totalCollected = $typeInvoices->sum('total_amount') - $typeInvoices->sum('balance');
                    $collectionRate = $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;
                    
                    return [
                        'total_billed' => $totalBilled,
                        'total_collected' => $totalCollected,
                        'balance' => $totalBilled - $totalCollected,
                        'collection_rate' => $collectionRate,
                        'invoice_count' => $typeInvoices->count(),
                    ];
                });
        } else { // month
            $collections = $query->get()
                ->groupBy(function ($invoice) {
                    return $invoice->issued_at->format('Y-m');
                })
                ->map(function ($monthInvoices) {
                    $totalBilled = $monthInvoices->sum('total_amount');
                    $totalCollected = $monthInvoices->sum('total_amount') - $monthInvoices->sum('balance');
                    $collectionRate = $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;
                    
                    return [
                        'total_billed' => $totalBilled,
                        'total_collected' => $totalCollected,
                        'balance' => $totalBilled - $totalCollected,
                        'collection_rate' => $collectionRate,
                        'invoice_count' => $monthInvoices->count(),
                    ];
                });
        }

        $totalBilled = $collections->sum('total_billed');
        $totalCollected = $collections->sum('total_collected');
        $totalBalance = $collections->sum('balance');
        $overallCollectionRate = $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;

        return view('admin.reports.student-fee-collection', compact(
            'collections',
            'totalBilled',
            'totalCollected',
            'totalBalance',
            'overallCollectionRate',
            'groupBy',
            'start',
            'end'
        ));
    }

    public function agedReceivables(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $asOf = $request->input('date') ? Carbon::parse($request->input('date')) : now();
        
        $invoices = Invoice::where('school_id', $school->id)
            ->where('balance', '>', 0)
            ->where('status', '!=', 'paid')
            ->with(['student', 'guardian'])
            ->get();

        $agedBuckets = [
            'current' => collect(),
            '0-30' => collect(),
            '31-60' => collect(),
            '61-90' => collect(),
            '90+' => collect(),
        ];

        $totalByBucket = [
            'current' => 0,
            '0-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0,
        ];

        foreach ($invoices as $invoice) {
            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date) : null;
            
            if (!$dueDate) {
                continue; // Skip invoices without due dates
            }
            
            $daysOverdue = $asOf->greaterThan($dueDate) 
                ? $asOf->diffInDays($dueDate) 
                : 0;

            $bucket = match(true) {
                $daysOverdue <= 0 => 'current',
                $daysOverdue <= 30 => '0-30',
                $daysOverdue <= 60 => '31-60',
                $daysOverdue <= 90 => '61-90',
                default => '90+',
            };

            $agedBuckets[$bucket]->push($invoice);
            $totalByBucket[$bucket] += $invoice->balance;
        }

        $grandTotal = array_sum($totalByBucket);

        return view('admin.reports.aged-receivables', compact(
            'agedBuckets',
            'totalByBucket',
            'grandTotal',
            'asOf'
        ));
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

        // Cash flows from operating activities
        $operatingInflows = $this->getCashFlowByCategory($school->id, 'operating', 'inflow', $start, $end);
        $operatingOutflows = $this->getCashFlowByCategory($school->id, 'operating', 'outflow', $start, $end);
        $netOperating = $operatingInflows - $operatingOutflows;

        // Cash flows from investing activities
        $investingInflows = $this->getCashFlowByCategory($school->id, 'investing', 'inflow', $start, $end);
        $investingOutflows = $this->getCashFlowByCategory($school->id, 'investing', 'outflow', $start, $end);
        $netInvesting = $investingInflows - $investingOutflows;

        // Cash flows from financing activities
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
        $cashAccountCodes = ['1100', '1301', '1302', '1303']; // Cash and bank accounts
        
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
