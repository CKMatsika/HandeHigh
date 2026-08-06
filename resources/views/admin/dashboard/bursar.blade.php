@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Bursar Dashboard</h1>
            <p class="text-slate-400 mt-1">Comprehensive financial management and accounting overview</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                <option>{{ $stats['monthly_performance']['current_month'] }}</option>
                <option>Last Month</option>
                <option>This Quarter</option>
                <option>This Year</option>
            </select>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                Export Report
            </button>
        </div>
    </div>

    <!-- Key Financial Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Revenue -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Total Revenue</p>
                    <p class="text-2xl font-bold text-slate-100 mt-1">{{ App\Services\CurrencyService::format($stats['financial_overview']['total_revenue'] ?? 0, $currentCurrency) }}</p>
                    <p class="text-xs text-green-400 mt-2">{{ $stats['financial_overview']['revenue_growth'] ?? '0%' }} from last month</p>
                </div>
                <div class="bg-green-500/20 rounded-lg p-3">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Net Income -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Net Income</p>
                    <p class="text-2xl font-bold text-slate-100 mt-1">{{ App\Services\CurrencyService::format($stats['financial_overview']['net_income'] ?? 0, $currentCurrency) }}</p>
                    <p class="text-xs text-green-400 mt-2">Margin: {{ $stats['financial_overview']['profit_margin'] ?? '0%' }}</p>
                </div>
                <div class="bg-blue-500/20 rounded-lg p-3">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Cash Balance -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Cash Balance</p>
                    <p class="text-2xl font-bold text-slate-100 mt-1">{{ App\Services\CurrencyService::format($stats['cash_flow']['closing_balance'] ?? 0, $currentCurrency) }}</p>
                    <p class="text-xs text-green-400 mt-2">Net Flow: {{ App\Services\CurrencyService::format($stats['cash_flow']['net_cash_flow'] ?? 0, $currentCurrency) }}</p>
                </div>
                <div class="bg-yellow-500/20 rounded-lg p-3">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Outstanding Receivables -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Outstanding Receivables</p>
                    <p class="text-2xl font-bold text-slate-100 mt-1">{{ App\Services\CurrencyService::format($stats['accounts_receivable']['total_arrears'] ?? 0, $currentCurrency) }}</p>
                    <p class="text-xs text-orange-400 mt-2">Collection Rate: {{ $stats['accounts_receivable']['collection_rate'] ?? '0%' }}</p>
                </div>
                <div class="bg-red-500/20 rounded-lg p-3">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Overview Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Streams -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Revenue Streams</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Tuition Fees</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['revenue_streams']['tuition_fees'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Boarding Fees</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['revenue_streams']['boarding_fees'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Extra Curricular</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['revenue_streams']['extra_curricular'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Donations</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['revenue_streams']['donations'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Investments</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['revenue_streams']['investments'] ?? 0, $currentCurrency) }}</span>
                </div>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Expense Breakdown</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Salaries</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['expense_tracking']['salaries'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Operations</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['expense_tracking']['operations'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Maintenance</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['expense_tracking']['maintenance'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Utilities</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['expense_tracking']['utilities'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Supplies</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['expense_tracking']['supplies'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Other</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['expense_tracking']['other_expenses'] ?? 0, $currentCurrency) }}</span>
                </div>
            </div>
        </div>

        <!-- Budget Performance -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Budget Performance</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-400 text-sm">Budget Used</span>
                        <span class="text-slate-100 font-medium">{{ $stats['budget_management']['utilization_rate'] ?? '0%' }}</span>
                    </div>
                    <div class="w-full bg-slate-700 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $stats['budget_management']['utilization_rate'] ?? '0%' }}"></div>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400 text-sm">Annual Budget</span>
                        <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['budget_management']['annual_budget'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-slate-400 text-sm">Remaining</span>
                        <span class="text-green-400 font-medium">{{ App\Services\CurrencyService::format($stats['budget_management']['budget_remaining'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-slate-400 text-sm">Variance</span>
                        <span class="text-yellow-400 font-medium">{{ $stats['budget_management']['budget_variance'] ?? '0%' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Flow & Bank Accounts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Cash Flow Statement -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Cash Flow Statement</h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-slate-400 text-sm">Opening Balance</p>
                        <p class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['cash_flow']['opening_balance'] ?? 0, $currentCurrency) }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-sm">Closing Balance</p>
                        <p class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['cash_flow']['closing_balance'] ?? 0, $currentCurrency) }}</p>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="text-green-400 text-sm">Cash Inflows</span>
                        <span class="text-green-400 font-medium">+{{ App\Services\CurrencyService::format($stats['cash_flow']['cash_inflows'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-red-400 text-sm">Cash Outflows</span>
                        <span class="text-red-400 font-medium">-{{ App\Services\CurrencyService::format($stats['cash_flow']['cash_outflows'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-700">
                        <span class="text-slate-300 text-sm font-medium">Net Cash Flow</span>
                        <span class="text-green-400 font-medium">+{{ App\Services\CurrencyService::format($stats['cash_flow']['net_cash_flow'] ?? 0, $currentCurrency) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Accounts Summary -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Bank Accounts Summary</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Total Balance</span>
                    <span class="text-slate-100 font-medium text-lg">{{ App\Services\CurrencyService::format($stats['bank_accounts']['total_balance'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400 text-sm">Main Account</span>
                        <span class="text-slate-100">{{ App\Services\CurrencyService::format($stats['bank_accounts']['main_account'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400 text-sm">Savings Account</span>
                        <span class="text-slate-100">{{ App\Services\CurrencyService::format($stats['bank_accounts']['savings_account'] ?? 0, $currentCurrency) }}</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="text-yellow-400 text-sm">Pending Deposits</span>
                        <span class="text-yellow-400">{{ App\Services\CurrencyService::format($stats['bank_accounts']['pending_deposits'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-orange-400 text-sm">Pending Withdrawals</span>
                        <span class="text-orange-400">{{ App\Services\CurrencyService::format($stats['bank_accounts']['pending_withdrawals'] ?? 0, $currentCurrency) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accounts Receivable & Payable -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Accounts Receivable Aging -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Accounts Receivable Aging</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Total Arrears</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['accounts_receivable']['total_arrears'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-green-400 text-sm">Current (0-30 days)</span>
                        <span class="text-green-400">{{ App\Services\CurrencyService::format($stats['accounts_receivable']['current'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-yellow-400 text-sm">31-60 days</span>
                        <span class="text-yellow-400">{{ App\Services\CurrencyService::format($stats['accounts_receivable']['over_30_days'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-orange-400 text-sm">61-90 days</span>
                        <span class="text-orange-400">{{ App\Services\CurrencyService::format($stats['accounts_receivable']['over_60_days'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-red-400 text-sm">90+ days</span>
                        <span class="text-red-400">{{ App\Services\CurrencyService::format($stats['accounts_receivable']['over_90_days'] ?? 0, $currentCurrency) }}</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-300 text-sm font-medium">Collection Rate</span>
                        <span class="text-green-400 font-medium">{{ $stats['accounts_receivable']['collection_rate'] ?? '0%' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Payable -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Accounts Payable</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Total Payables</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['accounts_payable']['total_payables'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-green-400 text-sm">Current</span>
                        <span class="text-green-400">{{ App\Services\CurrencyService::format($stats['accounts_payable']['current'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-yellow-400 text-sm">31-60 days</span>
                        <span class="text-yellow-400">{{ App\Services\CurrencyService::format($stats['accounts_payable']['over_30_days'] ?? 0, $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-red-400 text-sm">60+ days</span>
                        <span class="text-red-400">{{ App\Services\CurrencyService::format($stats['accounts_payable']['over_60_days'] ?? 0, $currentCurrency) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Health & Tax Compliance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Financial Health Indicators -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Financial Health Indicators</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-slate-400 text-sm">Current Ratio</p>
                    <p class="text-2xl font-bold text-green-400">{{ $stats['financial_health']['current_ratio'] ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Healthy (>1.5)</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-slate-400 text-sm">Debt to Equity</p>
                    <p class="text-2xl font-bold text-blue-400">{{ $stats['financial_health']['debt_to_equity'] ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Low Risk (<0.5)</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-slate-400 text-sm">Cash Balance</p>
                    <p class="text-xl font-bold text-yellow-400">{{ App\Services\CurrencyService::format($stats['financial_health']['cash_balance'] ?? 0, $currentCurrency) }}</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-slate-400 text-sm">Investments</p>
                    <p class="text-xl font-bold text-purple-400">{{ App\Services\CurrencyService::format($stats['financial_health']['investments'] ?? 0, $currentCurrency) }}</p>
                </div>
            </div>
        </div>

        <!-- Tax & Compliance -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Tax & Compliance</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">VAT Collected</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['tax_compliance']['vat_collected'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">VAT Payable</span>
                    <span class="text-orange-400 font-medium">{{ App\Services\CurrencyService::format($stats['tax_compliance']['vat_payable'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Payroll Tax</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['tax_compliance']['payroll_tax'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-sm">Corporate Tax</span>
                    <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['tax_compliance']['corporate_tax'] ?? 0, $currentCurrency) }}</span>
                </div>
                <div class="pt-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-300 text-sm font-medium">Compliance Status</span>
                        <span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-sm">{{ $stats['tax_compliance']['compliance_status'] ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Finance Advisor -->
    <a href="{{ route('admin.ai-finance.dashboard') }}" class="block rounded-2xl border border-violet-500/30 bg-gradient-to-r from-violet-500/10 to-indigo-500/10 p-6 hover:from-violet-500/20 hover:to-indigo-500/20 transition">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-violet-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-violet-300">AI Finance Advisor</h3>
                <p class="text-xs text-slate-400 mt-1">Get intelligent insights, anomaly detection, cash flow forecasting, and budget recommendations powered by AI.</p>
            </div>
            <svg class="w-5 h-5 text-violet-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </a>

    <!-- Today's Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Today's Transactions -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Today's Activity</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-slate-100">{{ $stats['recent_transactions']['payments_today'] ?? 0 }}</p>
                    <p class="text-slate-400 text-sm mt-1">Payments</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-slate-100">{{ $stats['recent_transactions']['invoices_today'] ?? 0 }}</p>
                    <p class="text-slate-400 text-sm mt-1">Invoices</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-slate-100">{{ $stats['recent_transactions']['receipts_today'] ?? 0 }}</p>
                    <p class="text-slate-400 text-sm mt-1">Receipts</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-2xl font-bold text-green-400">{{ App\Services\CurrencyService::format($stats['recent_transactions']['transaction_value_today'] ?? 0, $currentCurrency) }}</p>
                    <p class="text-slate-400 text-sm mt-1">Total Value</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="/admin/invoices" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                    <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Record Payment
                </a>
                <a href="/admin/invoices" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                    <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Generate Invoice
                </a>
                <a href="/admin/receipts" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                    <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 0h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                    </svg>
                    Print Receipt
                </a>
                <a href="/admin/reports/trial-balance" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                    <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v8m5 4h2"></path>
                    </svg>
                    Financial Reports
                </a>
                <a href="/admin/bank-accounts" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                    <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    Bank Accounts
                </a>
                <a href="/admin/journals" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                    <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    Journal Entries
                </a>
                <a href="/admin/budgets" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                    <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Budget Management
                </a>
                <a href="/admin/currency" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                    <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Currency Settings
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
