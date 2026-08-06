@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Accounts Clerk Dashboard</h1>
            <p class="text-slate-400 mt-1">Financial transactions and fee management</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                <option>This Month</option>
                <option>Last Month</option>
                <option>This Quarter</option>
            </select>
        </div>
    </div>

    <!-- Financial Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Total Revenue</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">${{ number_format($stats['financial_overview']['total_revenue'] ?? 0, 0) }}</p>
                    <p class="text-xs text-green-400 mt-2">↑ 8% from last month</p>
                </div>
                <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Net Income</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">${{ number_format($stats['financial_overview']['net_income'] ?? 0, 0) }}</p>
                    <p class="text-xs text-green-400 mt-2">↑ 12% from last month</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Collection Rate</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">{{ $stats['fee_collection']['collection_rate'] ?? '0%' }}</p>
                    <p class="text-xs text-green-400 mt-2">↑ 3% from last month</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Outstanding Fees</p>
                    <p class="text-2xl font-bold text-slate-50 mt-1">${{ number_format($stats['fee_collection']['outstanding_fees'] ?? 0, 0) }}</p>
                    <p class="text-xs text-orange-400 mt-2">↓ 5% from last month</p>
                </div>
                <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
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

    <!-- Fee Collection Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Fee Collection</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Total Fees</span>
                    <span class="text-sm text-slate-300">${{ number_format($stats['fee_collection']['total_fees'] ?? 0, 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Collected Fees</span>
                    <span class="text-sm text-green-400">${{ number_format($stats['fee_collection']['collected_fees'] ?? 0, 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Outstanding</span>
                    <span class="text-sm text-orange-400">${{ number_format($stats['fee_collection']['outstanding_fees'] ?? 0, 0) }}</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-3">
                    <div class="bg-green-500 h-3 rounded-full" style="width: {{ ($stats['fee_collection']['total_fees'] ?? 0) > 0 ? (($stats['fee_collection']['collected_fees'] ?? 0) / ($stats['fee_collection']['total_fees'] ?? 1)) * 100 : 0 }}%"></div>
                </div>
                <p class="text-xs text-slate-400">{{ $stats['fee_collection']['collection_rate'] ?? '0%' }} collection rate</p>
            </div>
        </div>

        <!-- Accounts Receivable -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-lg font-semibold text-slate-50 mb-4">Accounts Receivable</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-300">Total Arrears</span>
                    <span class="text-sm text-slate-300">${{ number_format($stats['accounts_receivable']['total_arrears'] ?? 0, 0) }}</span>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">0-30 days</span>
                        <span class="text-xs text-yellow-400">${{ number_format($stats['accounts_receivable']['over_30_days'] ?? 0, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">31-60 days</span>
                        <span class="text-xs text-orange-400">${{ number_format($stats['accounts_receivable']['over_60_days'] ?? 0, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">60+ days</span>
                        <span class="text-xs text-red-400">${{ number_format($stats['accounts_receivable']['over_90_days'] ?? 0, 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-lg font-semibold text-slate-50 mb-4">Today's Transactions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-slate-50">{{ $stats['recent_transactions']['payments_today'] ?? 0 }}</div>
                <p class="text-xs text-slate-400 mt-1">Payments</p>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-slate-50">{{ $stats['recent_transactions']['invoices_today'] ?? 0 }}</div>
                <p class="text-xs text-slate-400 mt-1">Invoices</p>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-slate-50">{{ $stats['recent_transactions']['receipts_today'] ?? 0 }}</div>
                <p class="text-xs text-slate-400 mt-1">Receipts</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-lg font-semibold text-slate-50 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <a href="/admin/invoices" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-700 transition text-center">
                Record Payment
            </a>
            <a href="/admin/invoices" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-700 transition text-center">
                Generate Invoice
            </a>
            <a href="/admin/receipts" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-700 transition text-center">
                Print Receipt
            </a>
            <a href="/admin/accounts" class="block bg-slate-800 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-700 transition text-center">
                View Accounts
            </a>
        </div>
    </div>
</div>
@endsection
