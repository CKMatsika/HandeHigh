@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Procurement Officer Dashboard</h1>
            <p class="text-slate-400 mt-1">Manage procurement requests, vendors, and spending analysis</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                <option>This Month</option>
                <option>Last Month</option>
                <option>This Quarter</option>
                <option>This Year</option>
            </select>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                New Request
            </button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Requests -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Total Requests</p>
                    <p class="text-2xl font-bold text-slate-100 mt-1">{{ $stats['procurement_overview']['total_requests'] }}</p>
                    <p class="text-xs text-blue-400 mt-2">{{ $stats['procurement_overview']['pending_approval'] }} pending approval</p>
                </div>
                <div class="bg-blue-500/20 rounded-lg p-3">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- In Progress -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">In Progress</p>
                    <p class="text-2xl font-bold text-slate-100 mt-1">{{ $stats['procurement_overview']['in_progress'] }}</p>
                    <p class="text-xs text-yellow-400 mt-2">Active procurement</p>
                </div>
                <div class="bg-yellow-500/20 rounded-lg p-3">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Completed</p>
                    <p class="text-2xl font-bold text-slate-100 mt-1">{{ $stats['procurement_overview']['completed'] }}</p>
                    <p class="text-xs text-green-400 mt-2">This month</p>
                </div>
                <div class="bg-green-500/20 rounded-lg p-3">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Vendors -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">Active Vendors</p>
                    <p class="text-2xl font-bold text-slate-100 mt-1">{{ $stats['vendor_management']['active_vendors'] }}</p>
                    <p class="text-xs text-purple-400 mt-2">{{ $stats['vendor_management']['new_vendors'] }} new this month</p>
                </div>
                <div class="bg-purple-500/20 rounded-lg p-3">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Procurement Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Project Procurement Status -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Project Procurement</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-400 text-sm">Active Projects</span>
                        <span class="text-slate-100 font-medium">{{ $stats['projects']['active_count'] ?? 0 }}</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-orange-400 text-sm">Procurement Needed</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-slate-700 rounded-full h-2">
                                    <div class="bg-orange-500 h-2 rounded-full" style="width: {{ ($stats['projects']['procurement_needed'] ?? 0) > 0 ? min(100, (($stats['projects']['procurement_needed'] ?? 0) / ($stats['projects']['active_count'] ?? 1)) * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-orange-400 text-sm">{{ $stats['projects']['procurement_needed'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-green-400 text-sm">Procurement Complete</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-slate-700 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($stats['projects']['active_count'] ?? 0) > 0 ? (($stats['projects']['procurement_complete'] ?? 0) / ($stats['projects']['active_count'] ?? 1)) * 100 : 0 }}%"></div>
                                </div>
                                <span class="text-green-400 text-sm">{{ $stats['projects']['procurement_complete'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.projects.index', ['status' => 'active']) }}" class="w-full bg-teal-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-teal-600 transition">
                    Manage Project Procurement
                </a>
            </div>
        </div>

        <!-- Budget vs Actual -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Project Budget vs Actual</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-400 text-sm">Total Budget</span>
                        <span class="text-slate-100 font-medium">${{ number_format($stats['projects']['total_budget'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-400 text-sm">Spent on Procurement</span>
                        <span class="text-orange-500 font-medium">${{ number_format($stats['projects']['procurement_spent'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-400 text-sm">Remaining Budget</span>
                        <span class="text-green-500 font-medium">${{ number_format(($stats['projects']['total_budget'] ?? 0) - ($stats['projects']['procurement_spent'] ?? 0), 2) }}</span>
                    </div>
                    <div class="w-full bg-slate-700 rounded-full h-2">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ ($stats['projects']['total_budget'] ?? 0) > 0 ? min(100, (($stats['projects']['procurement_spent'] ?? 0) / ($stats['projects']['total_budget'] ?? 1)) * 100) : 0 }}%"></div>
                    </div>
                </div>
                <a href="{{ route('admin.bills.index') }}" class="w-full bg-purple-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-purple-600 transition">
                    View Procurement Bills
                </a>
            </div>
        </div>
    </div>

    <!-- Detailed Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Procurement Status -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Procurement Status</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-400 text-sm">Request Status</span>
                        <span class="text-slate-100 font-medium">{{ $stats['procurement_overview']['total_requests'] }} Total</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-yellow-400 text-sm">Pending Approval</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-slate-700 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ ($stats['procurement_overview']['pending_approval'] / $stats['procurement_overview']['total_requests']) * 100 }}%"></div>
                                </div>
                                <span class="text-yellow-400 text-sm">{{ $stats['procurement_overview']['pending_approval'] }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-400 text-sm">In Progress</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-slate-700 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($stats['procurement_overview']['in_progress'] / $stats['procurement_overview']['total_requests']) * 100 }}%"></div>
                                </div>
                                <span class="text-blue-400 text-sm">{{ $stats['procurement_overview']['in_progress'] }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-green-400 text-sm">Completed</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-slate-700 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($stats['procurement_overview']['completed'] / $stats['procurement_overview']['total_requests']) * 100 }}%"></div>
                                </div>
                                <span class="text-green-400 text-sm">{{ $stats['procurement_overview']['completed'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spending Analysis -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Spending Analysis</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-400 text-sm">Budget Utilization</span>
                        <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::format($stats['spending_analysis']['ytd_spending'], $currentCurrency) }} / {{ App\Services\CurrencyService::format($stats['spending_analysis']['budget_allocated'], $currentCurrency) }}</span>
                    </div>
                    <div class="w-full bg-slate-700 rounded-full h-3">
                        <div class="bg-gradient-to-r from-green-500 to-blue-500 h-3 rounded-full" style="width: {{ ($stats['spending_analysis']['ytd_spending'] / $stats['spending_analysis']['budget_allocated']) * 100 }}%"></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">{{ round(($stats['spending_analysis']['ytd_spending'] / $stats['spending_analysis']['budget_allocated']) * 100) }}% of budget used</p>
                </div>
                <div class="pt-3 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400 text-sm">Remaining Budget</span>
                        <span class="text-green-400 font-medium">{{ App\Services\CurrencyService::format($stats['spending_analysis']['remaining_budget'], $currentCurrency) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-slate-400 text-sm">YTD Spending</span>
                        <span class="text-blue-400 font-medium">{{ App\Services\CurrencyService::format($stats['spending_analysis']['ytd_spending'], $currentCurrency) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Management & Recent Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Vendor Management -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Vendor Management</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-slate-100">{{ $stats['vendor_management']['active_vendors'] }}</p>
                    <p class="text-slate-400 text-sm mt-1">Active Vendors</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-green-400">{{ $stats['vendor_management']['new_vendors'] }}</p>
                    <p class="text-slate-400 text-sm mt-1">New This Month</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-red-400">{{ $stats['vendor_management']['blacklisted_vendors'] }}</p>
                    <p class="text-slate-400 text-sm mt-1">Blacklisted</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-blue-400">{{ round($stats['vendor_management']['active_vendors'] / 4) }}</p>
                    <p class="text-slate-400 text-sm mt-1">Avg. Rating</p>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Recent Activities</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-slate-100">{{ $stats['recent_activities']['quotes_received'] }}</p>
                    <p class="text-slate-400 text-sm mt-1">Quotes Received</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-green-400">{{ $stats['recent_activities']['contracts_signed'] }}</p>
                    <p class="text-slate-400 text-sm mt-1">Contracts Signed</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-blue-400">{{ $stats['recent_activities']['deliveries_received'] }}</p>
                    <p class="text-slate-400 text-sm mt-1">Deliveries Received</p>
                </div>
                <div class="text-center p-4 bg-slate-700 rounded-lg">
                    <p class="text-3xl font-bold text-yellow-400">{{ round($stats['recent_activities']['quotes_received'] * 0.8) }}</p>
                    <p class="text-slate-400 text-sm mt-1">Pending Orders</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-slate-100 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <a href="/admin/vendors" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Request
            </a>
            <a href="/admin/vendors" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Manage Vendors
            </a>
            <a href="/admin/invoices" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Record Payment
            </a>
            <a href="/admin/reports/expense-analysis" class="block bg-slate-700 text-slate-300 rounded-lg px-4 py-3 text-sm hover:bg-slate-600 transition text-center">
                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v8m5 4h2"></path>
                </svg>
                Reports
            </a>
        </div>
    </div>
</div>
@endsection
