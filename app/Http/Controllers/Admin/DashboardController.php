<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\Cashbook;
use App\Models\Account;
use App\Models\Project;
use App\Models\SchemeOfWork;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function headmaster()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        // Get statistics for headmaster dashboard
        $stats = [
            'total_students' => $school->students()->count(),
            'total_teachers' => $school->teachers()->count(),
            'total_classes' => $school->classes()->count(),
            'total_subjects' => $school->subjects()->count(),
            'pending_enrollments' => $school->enrollments()->where('status', 'pending')->count(),
            'today_attendance' => $school->attendances()->whereDate('attendance_date', now()->toDateString())->count(),
            'total_fees_collected' => $school->payments()->sum('amount'),
            'pending_procurement' => 0,
        ];

        // Schemes of work stats
        $schemesStats = [
            'total' => SchemeOfWork::where('school_id', $school->id)->count(),
            'submitted' => SchemeOfWork::where('school_id', $school->id)->where('status', 'submitted')->count(),
            'approved' => SchemeOfWork::where('school_id', $school->id)->where('status', 'approved')->count(),
        ];

        $recentSubmittedSchemes = SchemeOfWork::with(['teacher', 'subject', 'schoolClass'])
            ->where('school_id', $school->id)
            ->where('status', 'submitted')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard.headmaster', compact('stats', 'schemesStats', 'recentSubmittedSchemes'));
    }

    public function deputyHeadmaster()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        // Get statistics for deputy headmaster dashboard
        $stats = [
            'academic_performance' => [
                'average_grade' => '75%',
                'pass_rate' => '92%',
                'top_performers' => 15,
            ],
            'teacher_performance' => [
                'total_teachers' => $school->teachers()->count(),
                'active_teachers' => $school->teachers()->where('status', 'active')->count(),
                'pending_evaluations' => 5,
            ],
            'curriculum_status' => [
                'total_subjects' => $school->subjects()->count(),
                'active_curriculum' => 1,
                'pending_updates' => 3,
            ],
            'discipline_cases' => [
                'total_cases' => 12,
                'resolved_cases' => 8,
                'pending_cases' => 4,
            ],
        ];

        // Schemes of work stats
        $schemesStats = [
            'total' => SchemeOfWork::where('school_id', $school->id)->count(),
            'submitted' => SchemeOfWork::where('school_id', $school->id)->where('status', 'submitted')->count(),
            'approved' => SchemeOfWork::where('school_id', $school->id)->where('status', 'approved')->count(),
        ];

        $recentSubmittedSchemes = SchemeOfWork::with(['teacher', 'subject', 'schoolClass'])
            ->where('school_id', $school->id)
            ->where('status', 'submitted')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard.deputy-headmaster', compact('stats', 'schemesStats', 'recentSubmittedSchemes'));
    }

    public function accountsClerk()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        // Get real data from the system
        $currentDate = now();
        $startOfMonth = $currentDate->copy()->startOfMonth();
        
        // Get invoices and payments data
        $invoices = Invoice::where('school_id', $school->id);
        $payments = Payment::where('school_id', $school->id);
        $bills = Bill::where('school_id', $school->id);
        $cashbookEntries = Cashbook::where('school_id', $school->id);
        
        // Current period data
        $currentInvoices = $invoices->where('invoice_date', '>=', $startOfMonth)->get();
        $currentPayments = $payments->where('paid_at', '>=', $startOfMonth)->get();
        $currentBills = $bills->where('bill_date', '>=', $startOfMonth)->get();
        $todayPayments = $payments->where('paid_at', '>=', $currentDate->copy()->startOfDay())->get();
        $todayInvoices = $invoices->where('invoice_date', '>=', $currentDate->copy()->startOfDay())->get();
        
        // Calculate financial metrics
        $totalRevenue = $currentInvoices->sum('total_amount');
        $totalExpenses = $currentBills->sum('total_amount');
        $netIncome = $totalRevenue - $totalExpenses;
        
        // Get accounts receivable
        $outstandingBalance = $invoices->where('status', '!=', 'paid')->sum('balance');
        $collectedFees = $currentPayments->sum('amount');
        $totalFees = $currentInvoices->sum('total_amount');
        $collectionRate = $totalFees > 0 ? round(($collectedFees / $totalFees) * 100, 1) : 0;
        
        // Aging breakdown
        $over30Days = $invoices->where('status', '!=', 'paid')->where('due_date', '<', $currentDate->copy()->subDays(30))->sum('balance');
        $over60Days = $invoices->where('status', '!=', 'paid')->where('due_date', '<', $currentDate->copy()->subDays(60))->sum('balance');
        $over90Days = $invoices->where('status', '!=', 'paid')->where('due_date', '<', $currentDate->copy()->subDays(90))->sum('balance');
        
        // Today's transactions
        $paymentsToday = $todayPayments->count();
        $invoicesToday = $todayInvoices->count();
        $receiptsToday = $cashbookEntries->where('transaction_date', '>=', $currentDate->copy()->startOfDay())->count();

        // Get statistics for accounts clerk dashboard
        $stats = [
            'financial_overview' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
            ],
            'fee_collection' => [
                'total_fees' => $totalFees,
                'collected_fees' => $collectedFees,
                'outstanding_fees' => $outstandingBalance,
                'collection_rate' => $collectionRate . '%',
            ],
            'recent_transactions' => [
                'payments_today' => $paymentsToday,
                'invoices_today' => $invoicesToday,
                'receipts_today' => $receiptsToday,
            ],
            'accounts_receivable' => [
                'total_arrears' => $outstandingBalance,
                'over_30_days' => $over30Days,
                'over_60_days' => $over60Days,
                'over_90_days' => $over90Days,
            ],
        ];

        return view('admin.dashboard.accounts-clerk', compact('stats'));
    }

    public function bursar()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        // Auto-sync unposted financial transactions into General Ledger
        try {
            app(\App\Services\AccountingService::class)->syncExistingTransactions($school);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Bursar dashboard accounting sync: " . $e->getMessage());
        }

        // Get real data from the system
        $currentDate = now();
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $startOfYear = $currentDate->copy()->startOfYear();
        
        // Get invoices and payments data
        $invoices = Invoice::where('school_id', $school->id)->with('items');
        $payments = Payment::where('school_id', $school->id);
        $bills = Bill::where('school_id', $school->id)->with(['items', 'expenseAccount']);
        $cashbookEntries = Cashbook::where('school_id', $school->id);
        
        // Current period data (supporting issued_at or created_at)
        $currentInvoices = Invoice::where('school_id', $school->id)
            ->where(function ($q) use ($startOfMonth) {
                $q->where('issued_at', '>=', $startOfMonth)
                  ->orWhere('created_at', '>=', $startOfMonth);
            })
            ->with('items.feeStructure')
            ->get();

        $currentPayments = $payments->where('paid_at', '>=', $startOfMonth)->get();
        $currentBills = Bill::where('school_id', $school->id)
            ->where(function ($q) use ($startOfMonth) {
                $q->where('bill_date', '>=', $startOfMonth)
                  ->orWhere('created_at', '>=', $startOfMonth);
            })
            ->with(['items', 'expenseAccount'])
            ->get();

        $todayPayments = Payment::where('school_id', $school->id)
            ->where('paid_at', '>=', $currentDate->copy()->startOfDay())
            ->get();

        $todayInvoices = Invoice::where('school_id', $school->id)
            ->where(function ($q) use ($currentDate) {
                $q->where('issued_at', '>=', $currentDate->copy()->startOfDay())
                  ->orWhere('created_at', '>=', $currentDate->copy()->startOfDay());
            })
            ->get();
        
        // Calculate financial metrics
        $totalRevenue = (float) $currentInvoices->sum('total_amount');
        if ($totalRevenue == 0) {
            $totalRevenue = (float) Invoice::where('school_id', $school->id)->sum('total_amount');
        }

        $totalExpenses = (float) $currentBills->sum('total_amount');
        if ($totalExpenses == 0) {
            $totalExpenses = (float) Bill::where('school_id', $school->id)->sum('total_amount');
        }

        $netIncome = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0;
        
        // Get cashbook balances
        $cashbookBalance = $cashbookEntries->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->first()?->balance_after ?? 0;
        $openingBalance = $cashbookEntries->where('transaction_date', '<', $startOfMonth)->orderBy('transaction_date', 'desc')->first()?->balance_after ?? 0;
        $cashInflows = (float) $cashbookEntries->where('transaction_type', 'income')->where('transaction_date', '>=', $startOfMonth)->sum('amount');
        $cashOutflows = (float) $cashbookEntries->where('transaction_type', 'expense')->where('transaction_date', '>=', $startOfMonth)->sum('amount');
        $netCashFlow = $cashInflows - $cashOutflows;
        
        // Get accounts receivable
        $outstandingBalance = (float) Invoice::where('school_id', $school->id)->where('status', '!=', 'paid')->sum('balance');
        $currentAr = (float) Invoice::where('school_id', $school->id)->where('status', '!=', 'paid')->where('due_date', '>=', $currentDate)->sum('balance');
        $over30Days = (float) Invoice::where('school_id', $school->id)->where('status', '!=', 'paid')->where('due_date', '<', $currentDate->copy()->subDays(30))->sum('balance');
        $over60Days = (float) Invoice::where('school_id', $school->id)->where('status', '!=', 'paid')->where('due_date', '<', $currentDate->copy()->subDays(60))->sum('balance');
        $over90Days = (float) Invoice::where('school_id', $school->id)->where('status', '!=', 'paid')->where('due_date', '<', $currentDate->copy()->subDays(90))->sum('balance');
        
        $collectionRate = $totalRevenue > 0 ? (max(0, $totalRevenue - $outstandingBalance) / $totalRevenue) * 100 : 0;
        
        // Get accounts payable
        $totalPayables = (float) Bill::where('school_id', $school->id)->where('status', '!=', 'paid')->sum('balance');
        $currentPayables = (float) Bill::where('school_id', $school->id)->where('status', '!=', 'paid')->where('due_date', '>=', $currentDate)->sum('balance');
        $payablesOver30Days = (float) Bill::where('school_id', $school->id)->where('status', '!=', 'paid')->where('due_date', '<', $currentDate->copy()->subDays(30))->sum('balance');
        $payablesOver60Days = (float) Bill::where('school_id', $school->id)->where('status', '!=', 'paid')->where('due_date', '<', $currentDate->copy()->subDays(60))->sum('balance');
        
        // Get bank accounts
        $bankAccounts = Account::where('school_id', $school->id)
            ->whereIn('type', ['asset'])
            ->where('is_active', true)
            ->get();
        
        $totalBankBalance = (float) $bankAccounts->sum('current_balance');
        $mainAccount = (float) ($bankAccounts->where('code', '1301')->first()?->current_balance ?? 0);
        $savingsAccount = (float) ($bankAccounts->where('code', '1302')->first()?->current_balance ?? 0);
        
        // Get project data
        $projects = Project::where('school_id', $school->id)->get();
        $activeProjects = $projects->whereIn('status', ['approved', 'in_progress']);
        $totalProjectBudget = (float) $activeProjects->sum('budget_amount');
        $totalProjectSpent = (float) $activeProjects->sum('total_spent');
        
        // Calculate financial ratios safely
        $currentAssets = max($cashbookBalance, $totalBankBalance);
        $currentRatio = $totalPayables > 0 ? round($currentAssets / $totalPayables, 2) : 0;
        
        // Detailed Revenue Streams Calculation (General Ledger + Itemized Categories)
        $invoicesForBreakdown = $currentInvoices->isNotEmpty() ? $currentInvoices : Invoice::where('school_id', $school->id)->with('items')->get();
        $allInvoiceItems = $invoicesForBreakdown->flatMap->items;

        $tuitionTotal = (float) $allInvoiceItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            $desc = strtolower($item->description ?? '');
            return str_contains($cat, 'tuition') || str_contains($desc, 'tuition') || in_array($cat, ['fees', 'fee', 'tuition_fee', '']);
        })->sum('line_total');

        $boardingTotal = (float) $allInvoiceItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            $desc = strtolower($item->description ?? '');
            return str_contains($cat, 'boarding') || str_contains($desc, 'boarding') || str_contains($desc, 'hostel');
        })->sum('line_total');

        $extraCurricularTotal = (float) $allInvoiceItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            $desc = strtolower($item->description ?? '');
            return str_contains($cat, 'transport') || str_contains($cat, 'exam') || str_contains($cat, 'extra') || str_contains($desc, 'sport') || str_contains($desc, 'club');
        })->sum('line_total');

        $donationsTotal = (float) $allInvoiceItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            return str_contains($cat, 'donation') || str_contains($cat, 'grant');
        })->sum('line_total');

        $investmentsTotal = (float) $allInvoiceItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            $desc = strtolower($item->description ?? '');
            return str_contains($cat, 'book') || str_contains($cat, 'uniform') || str_contains($cat, 'kiosk') || str_contains($cat, 'canteen') || str_contains($cat, 'levy') || str_contains($desc, 'levy');
        })->sum('line_total');

        // Fallback: If items were not itemized, assign entire revenue to Tuition Fees
        if ($tuitionTotal == 0 && $boardingTotal == 0 && $extraCurricularTotal == 0 && $donationsTotal == 0 && $investmentsTotal == 0 && $totalRevenue > 0) {
            $tuitionTotal = $totalRevenue;
        }

        // Detailed Expense Breakdown Calculation (General Ledger + Bills)
        $billsForBreakdown = $currentBills->isNotEmpty() ? $currentBills : Bill::where('school_id', $school->id)->with(['items', 'expenseAccount'])->get();
        $allBillItems = $billsForBreakdown->flatMap->items;

        $salariesExpense = (float) $allBillItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            return str_contains($cat, 'salary') || str_contains($cat, 'wage') || str_contains($cat, 'payroll');
        })->sum('line_total');

        $operationsExpense = (float) $allBillItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            return str_contains($cat, 'operation') || str_contains($cat, 'rent') || str_contains($cat, 'service');
        })->sum('line_total');

        $maintenanceExpense = (float) $allBillItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            return str_contains($cat, 'maintain') || str_contains($cat, 'repair');
        })->sum('line_total');

        $utilitiesExpense = (float) $allBillItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            return str_contains($cat, 'utilit') || str_contains($cat, 'electric') || str_contains($cat, 'water') || str_contains($cat, 'internet');
        })->sum('line_total');

        $suppliesExpense = (float) $allBillItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            return str_contains($cat, 'supply') || str_contains($cat, 'material') || str_contains($cat, 'stationery');
        })->sum('line_total');

        $otherExpense = (float) $allBillItems->filter(function ($item) {
            $cat = strtolower($item->category ?? '');
            return !in_array($cat, ['salary', 'salaries', 'operations', 'maintenance', 'utilities', 'supplies']);
        })->sum('line_total');

        if ($salariesExpense == 0 && $operationsExpense == 0 && $maintenanceExpense == 0 && $utilitiesExpense == 0 && $suppliesExpense == 0 && $otherExpense == 0 && $totalExpenses > 0) {
            $operationsExpense = $totalExpenses;
        }

        // Get comprehensive statistics for bursar dashboard
        $stats = [
            // Financial Overview
            'financial_overview' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'profit_margin' => round($profitMargin, 1) . '%',
                'revenue_growth' => '+12%',
                'expense_growth' => '+8%',
            ],
            
            // Budget Management
            'budget_management' => [
                'annual_budget' => $totalProjectBudget,
                'budget_used' => $totalProjectSpent,
                'budget_remaining' => $totalProjectBudget - $totalProjectSpent,
                'utilization_rate' => $totalProjectBudget > 0 ? round(($totalProjectSpent / $totalProjectBudget) * 100, 1) . '%' : '0%',
                'budget_variance' => $totalProjectBudget > 0 ? round((($totalProjectBudget - $totalProjectSpent) / $totalProjectBudget) * 100, 1) . '%' : '0%',
            ],
            
            // Cash Flow Management
            'cash_flow' => [
                'opening_balance' => $openingBalance,
                'cash_inflows' => $cashInflows,
                'cash_outflows' => $cashOutflows,
                'closing_balance' => $cashbookBalance,
                'net_cash_flow' => $netCashFlow,
            ],
            
            // Expense Tracking
            'expense_tracking' => [
                'total_expenses' => $totalExpenses,
                'salaries' => $salariesExpense,
                'operations' => $operationsExpense,
                'maintenance' => $maintenanceExpense,
                'utilities' => $utilitiesExpense,
                'supplies' => $suppliesExpense,
                'other_expenses' => $otherExpense,
            ],
            
            // Revenue Streams
            'revenue_streams' => [
                'tuition_fees' => $tuitionTotal,
                'boarding_fees' => $boardingTotal,
                'extra_curricular' => $extraCurricularTotal,
                'donations' => $donationsTotal,
                'investments' => $investmentsTotal,
            ],
            
            // Accounts Receivable
            'accounts_receivable' => [
                'total_arrears' => $outstandingBalance,
                'current' => $currentAr,
                'over_30_days' => $over30Days,
                'over_60_days' => $over60Days,
                'over_90_days' => $over90Days,
                'collection_rate' => round($collectionRate, 1) . '%',
            ],
            
            // Accounts Payable
            'accounts_payable' => [
                'total_payables' => $totalPayables,
                'current' => $currentPayables,
                'over_30_days' => $payablesOver30Days,
                'over_60_days' => $payablesOver60Days,
            ],
            
            // Bank Accounts
            'bank_accounts' => [
                'total_balance' => $totalBankBalance,
                'main_account' => $mainAccount,
                'savings_account' => $savingsAccount,
                'pending_deposits' => 0,
                'pending_withdrawals' => 0,
            ],
            
            // Procurement Status
            'procurement_status' => [
                'pending_requests' => $currentBills->where('status', 'pending')->count(),
                'approved_requests' => $currentBills->where('status', 'approved')->count(),
                'total_value' => $currentBills->sum('total_amount'),
                'monthly_spend' => $totalExpenses,
            ],
            
            // Financial Health
            'financial_health' => [
                'cash_balance' => $cashbookBalance,
                'bank_balance' => $totalBankBalance,
                'investments' => 0,
                'liabilities' => $totalPayables,
                'current_ratio' => $currentRatio,
                'debt_to_equity' => 0,
            ],
            
            // Recent Transactions
            'recent_transactions' => [
                'payments_today' => $todayPayments->count(),
                'invoices_today' => $todayInvoices->count(),
                'receipts_today' => $cashbookEntries->where('transaction_date', '>=', $currentDate->copy()->startOfDay())->count(),
                'total_transactions_today' => $todayPayments->count() + $todayInvoices->count(),
                'transaction_value_today' => $todayPayments->sum('amount') + $todayInvoices->sum('total_amount'),
            ],
            
            // Tax & Compliance
            'tax_compliance' => [
                'vat_collected' => 0,
                'vat_payable' => 0,
                'payroll_tax' => 0,
                'corporate_tax' => 0,
                'compliance_status' => 'Good',
            ],
            
            // Monthly Performance
            'monthly_performance' => [
                'current_month' => $currentDate->format('F Y'),
                'revenue_vs_budget' => 100,
                'expenses_vs_budget' => 100,
                'profit_margin' => round($profitMargin, 1),
                'cash_flow_positive' => $netCashFlow >= 0,
            ],
        ];

        return view('admin.dashboard.bursar', compact('stats'));
    }

    public function procurementOfficer()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        // Get statistics for procurement officer dashboard
        $stats = [
            'procurement_overview' => [
                'total_requests' => 23,
                'pending_approval' => 8,
                'in_progress' => 12,
                'completed' => 3,
            ],
            'vendor_management' => [
                'active_vendors' => 45,
                'new_vendors' => 8,
                'blacklisted_vendors' => 2,
            ],
            'spending_analysis' => [
                'ytd_spending' => 125000,
                'budget_allocated' => 200000,
                'remaining_budget' => 75000,
            ],
            'recent_activities' => [
                'quotes_received' => 15,
                'contracts_signed' => 5,
                'deliveries_received' => 8,
            ],
        ];

        return view('admin.dashboard.procurement-officer', compact('stats'));
    }
}
