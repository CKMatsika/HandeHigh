<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Cashbook;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\Budget;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AIFinanceService
{
    /**
     * Calculate overall financial health score (0-100)
     */
    public function getFinancialHealthScore(int $schoolId): array
    {
        $scores = [];

        // 1. Fee Collection Rate (25 points)
        $invoices = Invoice::where('school_id', $schoolId)->where('issued_at', '>=', now()->subMonths(6))->get();
        $totalBilled = $invoices->sum('total_amount');
        $totalCollected = $totalBilled - $invoices->sum('balance');
        $collectionRate = $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;
        $scores['collection_rate'] = [
            'score' => min(25, ($collectionRate / 100) * 25),
            'max' => 25,
            'value' => round($collectionRate, 1),
            'label' => 'Fee Collection Rate',
            'status' => $collectionRate >= 80 ? 'good' : ($collectionRate >= 60 ? 'warning' : 'danger'),
        ];

        // 2. Budget Adherence (25 points)
        $currentYear = now()->year;
        $budgets = Budget::where('school_id', $schoolId)->where('fiscal_year', $currentYear)->where('status', 'active')->get();
        $totalBudgeted = $budgets->sum('total_budgeted');
        $totalActual = $budgets->sum('total_actual');
        $budgetAdherence = $totalBudgeted > 0 ? max(0, 100 - abs(($totalActual - $totalBudgeted) / $totalBudgeted) * 100) : 50;
        $scores['budget_adherence'] = [
            'score' => min(25, ($budgetAdherence / 100) * 25),
            'max' => 25,
            'value' => round($budgetAdherence, 1),
            'label' => 'Budget Adherence',
            'status' => $budgetAdherence >= 90 ? 'good' : ($budgetAdherence >= 70 ? 'warning' : 'danger'),
        ];

        // 3. Cash Position (25 points)
        $recentCashIn = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'income')
            ->whereDate('transaction_date', '>=', now()->subMonth())
            ->sum('amount');
        $recentCashOut = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'expense')
            ->whereDate('transaction_date', '>=', now()->subMonth())
            ->sum('amount');
        $cashRatio = $recentCashOut > 0 ? ($recentCashIn / $recentCashOut) * 100 : 100;
        $scores['cash_position'] = [
            'score' => min(25, (min($cashRatio, 150) / 150) * 25),
            'max' => 25,
            'value' => round($cashRatio, 1),
            'label' => 'Cash Flow Ratio',
            'status' => $cashRatio >= 110 ? 'good' : ($cashRatio >= 90 ? 'warning' : 'danger'),
        ];

        // 4. Outstanding Bills (25 points)
        $pendingBills = Bill::where('school_id', $schoolId)->whereIn('status', ['pending', 'partial', 'overdue'])->sum('balance');
        $totalBills = Bill::where('school_id', $schoolId)->sum('total_amount');
        $billHealth = $totalBills > 0 ? max(0, 100 - ($pendingBills / $totalBills) * 100) : 100;
        $scores['bill_health'] = [
            'score' => min(25, ($billHealth / 100) * 25),
            'max' => 25,
            'value' => round($billHealth, 1),
            'label' => 'Bill Payment Health',
            'status' => $billHealth >= 80 ? 'good' : ($billHealth >= 60 ? 'warning' : 'danger'),
        ];

        $totalScore = array_sum(array_column($scores, 'score'));
        $overallStatus = $totalScore >= 80 ? 'healthy' : ($totalScore >= 60 ? 'needs_attention' : 'critical');

        return [
            'score' => round($totalScore),
            'status' => $overallStatus,
            'breakdown' => $scores,
        ];
    }

    /**
     * Detect anomalous transactions
     */
    public function detectAnomalies(int $schoolId): array
    {
        $anomalies = [];

        // 1. Unusually large cashbook transactions
        $avgCashbook = Cashbook::where('school_id', $schoolId)
            ->whereDate('transaction_date', '>=', now()->subMonths(3))
            ->avg('amount') ?? 0;

        $largeTransactions = Cashbook::where('school_id', $schoolId)
            ->whereDate('transaction_date', '>=', now()->subMonths(3))
            ->where('amount', '>', $avgCashbook * 3)
            ->where('amount', '>', 100)
            ->with('account', 'createdBy')
            ->orderByDesc('amount')
            ->limit(5)
            ->get();

        foreach ($largeTransactions as $txn) {
            $anomalies[] = [
                'type' => 'large_transaction',
                'severity' => 'high',
                'title' => 'Unusually Large Transaction',
                'description' => ucfirst($txn->transaction_type) . ': ' . $txn->description . ' ($' . number_format($txn->amount, 2) . ')',
                'detail' => 'This is ' . number_format(($txn->amount / max($avgCashbook, 1)), 1) . 'x the average transaction amount ($' . number_format($avgCashbook, 2) . ')',
                'date' => $txn->transaction_date,
                'model_type' => 'cashbook',
                'model_id' => $txn->id,
            ];
        }

        // 2. Overdue invoices
        $overdueInvoices = Invoice::where('school_id', $schoolId)
            ->where('balance', '>', 0)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now()->subDays(30))
            ->with('student')
            ->limit(5)
            ->get();

        foreach ($overdueInvoices as $inv) {
            $daysOverdue = $inv->due_date->diffInDays(now());
            $anomalies[] = [
                'type' => 'overdue_invoice',
                'severity' => $daysOverdue > 90 ? 'critical' : ($daysOverdue > 60 ? 'high' : 'medium'),
                'title' => 'Significantly Overdue Invoice',
                'description' => 'Invoice ' . $inv->number . ' - $' . number_format($inv->balance, 2) . ' outstanding',
                'detail' => $daysOverdue . ' days overdue for ' . ($inv->student->first_name ?? 'Student'),
                'date' => $inv->due_date,
                'model_type' => 'invoice',
                'model_id' => $inv->id,
            ];
        }

        // 3. Overdue bills
        $overdueBills = Bill::where('school_id', $schoolId)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now())
            ->with('vendor')
            ->limit(5)
            ->get();

        foreach ($overdueBills as $bill) {
            $daysOverdue = $bill->due_date->diffInDays(now());
            $anomalies[] = [
                'type' => 'overdue_bill',
                'severity' => $daysOverdue > 60 ? 'critical' : 'high',
                'title' => 'Overdue Vendor Bill',
                'description' => 'Bill ' . $bill->bill_number . ' - $' . number_format($bill->balance, 2) . ' owed to ' . ($bill->vendor->name ?? 'Vendor'),
                'detail' => $daysOverdue . ' days overdue',
                'date' => $bill->due_date,
                'model_type' => 'bill',
                'model_id' => $bill->id,
            ];
        }

        // 4. Expense spikes (current month vs 3-month average)
        $currentMonthExpenses = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        $avgMonthlyExpenses = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'expense')
            ->whereDate('transaction_date', '>=', now()->subMonths(3)->startOfMonth())
            ->whereDate('transaction_date', '<', now()->startOfMonth())
            ->sum('amount') / 3;

        if ($avgMonthlyExpenses > 0 && $currentMonthExpenses > $avgMonthlyExpenses * 1.3) {
            $anomalies[] = [
                'type' => 'expense_spike',
                'severity' => 'medium',
                'title' => 'Expense Spike Detected',
                'description' => 'This month\'s expenses ($' . number_format($currentMonthExpenses, 2) . ') are ' . number_format(($currentMonthExpenses / $avgMonthlyExpenses) * 100 - 100, 0) . '% above the 3-month average',
                'detail' => '3-month average: $' . number_format($avgMonthlyExpenses, 2) . '/month',
                'date' => now(),
                'model_type' => null,
                'model_id' => null,
            ];
        }

        usort($anomalies, fn($a, $b) => $a['date'] <=> $b['date']);

        return array_reverse($anomalies);
    }

    /**
     * Forecast cash flow for next 3 months
     */
    public function forecastCashFlow(int $schoolId): array
    {
        $months = [];
        for ($i = 1; $i <= 3; $i++) {
            $targetMonth = now()->addMonths($i);
            $monthName = $targetMonth->format('F Y');

            // Average income for same month over past 2 years
            $avgIncome = Cashbook::where('school_id', $schoolId)
                ->where('transaction_type', 'income')
                ->whereMonth('transaction_date', $targetMonth->month)
                ->whereYear('transaction_date', '>=', now()->year - 2)
                ->avg('amount') ?? 0;

            // Average expense for same month
            $avgExpense = Cashbook::where('school_id', $schoolId)
                ->where('transaction_type', 'expense')
                ->whereMonth('transaction_date', $targetMonth->month)
                ->whereYear('transaction_date', '>=', now()->year - 2)
                ->avg('amount') ?? 0;

            // Expected invoices (recurring fees)
            $expectedRevenue = Invoice::where('school_id', $schoolId)
                ->whereMonth('issued_at', $targetMonth->month)
                ->whereYear('issued_at', '>=', now()->year - 1)
                ->avg('total_amount') ?? $avgIncome;

            // Expected bills (recurring)
            $expectedBills = Bill::where('school_id', $schoolId)
                ->whereMonth('due_date', $targetMonth->month)
                ->whereYear('due_date', '>=', now()->year - 1)
                ->avg('total_amount') ?? $avgExpense;

            $netFlow = $expectedRevenue - $expectedBills;

            $months[] = [
                'month' => $monthName,
                'projected_income' => round($expectedRevenue, 2),
                'projected_expenses' => round($expectedBills, 2),
                'net_flow' => round($netFlow, 2),
                'confidence' => $netFlow >= 0 ? 'moderate' : 'low',
            ];
        }

        return $months;
    }

    /**
     * Generate smart financial insights
     */
    public function getSmartInsights(int $schoolId): array
    {
        $insights = [];

        // Revenue trend (last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = Cashbook::where('school_id', $schoolId)
                ->where('transaction_type', 'income')
                ->whereMonth('transaction_date', $month->month)
                ->whereYear('transaction_date', $month->year)
                ->sum('amount');
            $monthlyRevenue[$month->format('M Y')] = $revenue;
        }

        $revenueValues = array_values($monthlyRevenue);
        if (count($revenueValues) >= 2) {
            $recent = end($revenueValues);
            $previous = prev($revenueValues);
            if ($previous > 0) {
                $change = (($recent - $previous) / $previous) * 100;
                $insights[] = [
                    'type' => 'trend',
                    'icon' => $change >= 0 ? 'trending_up' : 'trending_down',
                    'title' => 'Revenue ' . ($change >= 0 ? 'Increased' : 'Decreased'),
                    'description' => 'Revenue ' . ($change >= 0 ? 'grew' : 'fell') . ' by ' . number_format(abs($change), 1) . '% from ' . now()->subMonths(1)->format('M') . ' to ' . now()->format('M'),
                    'impact' => $change >= 0 ? 'positive' : 'negative',
                    'actionable' => $change < -10 ? 'Review fee collection and consider follow-up with outstanding accounts' : null,
                ];
            }
        }

        // Top expense category
        $topCategory = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'expense')
            ->whereDate('transaction_date', '>=', now()->subMonths(3))
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        if ($topCategory) {
            $insights[] = [
                'type' => 'expense',
                'icon' => 'category',
                'title' => 'Top Spending Category',
                'description' => ucfirst($topCategory->category) . ' accounts for $' . number_format($topCategory->total, 2) . ' in the last 3 months',
                'impact' => 'neutral',
                'actionable' => 'Consider negotiating bulk rates or finding cost-efficient alternatives for ' . $topCategory->category,
            ];
        }

        // Collection rate trend
        $recentCollectionRate = $this->getCollectionRate($schoolId, now()->subMonth(), now());
        $prevCollectionRate = $this->getCollectionRate($schoolId, now()->subMonths(2), now()->subMonth());

        if ($recentCollectionRate > 0 && $prevCollectionRate > 0) {
            $rateChange = $recentCollectionRate - $prevCollectionRate;
            $insights[] = [
                'type' => 'collection',
                'icon' => $rateChange >= 0 ? 'check_circle' : 'warning',
                'title' => 'Collection Rate ' . ($rateChange >= 0 ? 'Improved' : 'Declined'),
                'description' => 'Fee collection rate is now ' . number_format($recentCollectionRate, 1) . '% (was ' . number_format($prevCollectionRate, 1) . '%)',
                'impact' => $rateChange >= 0 ? 'positive' : 'negative',
                'actionable' => $rateChange < -5 ? 'Consider sending reminder notices to parents with outstanding fees' : null,
            ];
        }

        // Cash reserve warning
        $cashIn = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'income')
            ->whereDate('transaction_date', '>=', now()->subMonth())
            ->sum('amount');
        $cashOut = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'expense')
            ->whereDate('transaction_date', '>=', now()->subMonth())
            ->sum('amount');

        if ($cashOut > $cashIn && $cashIn > 0) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'alert',
                'title' => 'Cash Outflow Exceeds Inflow',
                'description' => 'Last month: spent $' . number_format($cashOut, 2) . ' but received $' . number_format($cashIn, 2) . '. Net outflow: $' . number_format($cashOut - $cashIn, 2),
                'impact' => 'negative',
                'actionable' => 'Review expenses and accelerate receivables collection to improve cash position',
            ];
        }

        return $insights;
    }

    /**
     * Get budget optimization recommendations
     */
    public function getBudgetRecommendations(int $schoolId): array
    {
        $recommendations = [];
        $currentYear = now()->year;

        $budgets = Budget::where('school_id', $schoolId)
            ->where('fiscal_year', $currentYear)
            ->with('lines.account')
            ->get();

        foreach ($budgets as $budget) {
            foreach ($budget->lines as $line) {
                if (!$line->budgeted_amount || $line->budgeted_amount == 0) continue;

                $variancePercent = $line->variance_percentage ?? 0;

                // Over budget warning
                if ($variancePercent < -15) {
                    $recommendations[] = [
                        'type' => 'over_budget',
                        'priority' => 'high',
                        'title' => $line->account->name . ' is over budget',
                        'description' => 'Spent ' . number_format(abs($variancePercent), 1) . '% more than budgeted ($' . number_format($line->budgeted_amount, 2) . ' budgeted)',
                        'suggestion' => 'Consider increasing budget allocation or reducing spending in this category',
                        'account' => $line->account->name ?? 'Unknown',
                        'budget_id' => $budget->id,
                    ];
                }

                // Under-utilized budget
                if ($variancePercent > 30 && $line->budgeted_amount > 100) {
                    $recommendations[] = [
                        'type' => 'under_utilized',
                        'priority' => 'low',
                        'title' => $line->account->name . ' has excess budget',
                        'description' => 'Only ' . number_format(100 - $variancePercent, 1) . '% of budget used ($' . number_format($line->budgeted_amount, 2) . ' allocated)',
                        'suggestion' => 'Consider reallocating unused funds to higher-priority areas',
                        'account' => $line->account->name ?? 'Unknown',
                        'budget_id' => $budget->id,
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Get revenue breakdown by source
     */
    public function getRevenueBreakdown(int $schoolId, string $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $categories = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'income')
            ->whereDate('transaction_date', '>=', $startDate)
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $total = $categories->sum('total');

        return $categories->map(function ($cat) use ($total) {
            return [
                'category' => ucfirst($cat->category),
                'amount' => $cat->total,
                'count' => $cat->count,
                'percentage' => $total > 0 ? round(($cat->total / $total) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Get expense breakdown by category
     */
    public function getExpenseBreakdown(int $schoolId, string $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $categories = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'expense')
            ->whereDate('transaction_date', '>=', $startDate)
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $total = $categories->sum('total');

        return $categories->map(function ($cat) use ($total) {
            return [
                'category' => ucfirst($cat->category),
                'amount' => $cat->total,
                'count' => $cat->count,
                'percentage' => $total > 0 ? round(($cat->total / $total) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Get key financial metrics summary
     */
    public function getKeyMetrics(int $schoolId): array
    {
        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();

        $totalRevenueYTD = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'income')
            ->whereDate('transaction_date', '>=', $yearStart)
            ->sum('amount');

        $totalExpensesYTD = Cashbook::where('school_id', $schoolId)
            ->where('transaction_type', 'expense')
            ->whereDate('transaction_date', '>=', $yearStart)
            ->sum('amount');

        $totalOutstanding = Invoice::where('school_id', $schoolId)
            ->where('balance', '>', 0)
            ->where('status', '!=', 'paid')
            ->sum('balance');

        $totalBillsOwed = Bill::where('school_id', $schoolId)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('balance');

        $netPosition = $totalRevenueYTD - $totalExpensesYTD;

        return [
            'revenue_ytd' => round($totalRevenueYTD, 2),
            'expenses_ytd' => round($totalExpensesYTD, 2),
            'net_position' => round($netPosition, 2),
            'outstanding_receivables' => round($totalOutstanding, 2),
            'outstanding_payables' => round($totalBillsOwed, 2),
            'months_data' => now()->month,
            'avg_monthly_revenue' => round($totalRevenueYTD / max(now()->month, 1), 2),
            'avg_monthly_expenses' => round($totalExpensesYTD / max(now()->month, 1), 2),
        ];
    }

    private function getCollectionRate(int $schoolId, Carbon $start, Carbon $end): float
    {
        $invoices = Invoice::where('school_id', $schoolId)
            ->whereBetween('issued_at', [$start, $end])
            ->get();

        $totalBilled = $invoices->sum('total_amount');
        $totalCollected = $totalBilled - $invoices->sum('balance');

        return $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;
    }
}
