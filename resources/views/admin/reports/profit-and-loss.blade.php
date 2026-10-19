@extends('layouts.app')

@section('content')
    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
            .bg-slate-900, .bg-slate-950, .bg-slate-900\/80 { background: white !important; border: 1px solid #ddd !important; }
            .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-50 { color: black !important; }
            .text-slate-400, .text-slate-500 { color: #555 !important; }
            .text-emerald-400 { color: #047857 !important; }
            .text-rose-400 { color: #b91c1c !important; }
            .border-slate-800, .border-slate-700 { border-color: #ddd !important; }
            .shadow-xl, .shadow-lg, .shadow-sm { box-shadow: none !important; }
        }
    </style>

    <div class="space-y-6">
        <!-- Page Title & Top Actions (Screen only) -->
        <div class="no-print flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30 shadow-inner">
                        📊
                    </span>
                    Project &amp; Cost Center Profit &amp; Loss
                </h1>
                <p class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                    <span>Performance Statement for:</span>
                    @if($project)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            📁 Project: {{ $project->name }} ({{ $project->code ?? 'N/A' }})
                        </span>
                    @elseif($costCenter)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            🏢 Cost Center: {{ $costCenter->name }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                            🌐 All Activities &amp; Cost Centers Combined
                        </span>
                    @endif
                    <span class="text-slate-500">•</span>
                    <span class="text-slate-300 font-medium">{{ $period_label }} ({{ $start_date }} to {{ $end_date }})</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.reports.profit-and-loss.export', request()->query()) }}" 
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/90 px-3.5 py-2 text-xs font-medium text-slate-200 hover:bg-slate-700 hover:text-white transition shadow-sm hover:border-slate-600">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </a>
                <button onclick="window.print()" 
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white px-3.5 py-2 text-xs font-medium transition shadow-sm shadow-blue-500/20">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Statement
                </button>
                <a href="{{ route('admin.reports.profit-and-loss') }}" 
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset
                </a>
            </div>
        </div>

        <!-- Filter Bar (Screen only) -->
        <div class="no-print bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-sm">
            <form id="filterForm" method="GET" action="{{ route('admin.reports.profit-and-loss') }}" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Project Selector -->
                    <div>
                        <label class="block text-slate-400 font-medium mb-1.5 flex items-center justify-between">
                            <span>Project (e.g. Bus, Tuckshop, Truck)</span>
                            @if($selectedProjectId)
                                <a href="{{ request()->fullUrlWithQuery(['project_id' => null]) }}" class="text-[10px] text-rose-400 hover:underline">Clear</a>
                            @endif
                        </label>
                        <select name="project_id" class="w-full px-3 py-2 border border-slate-700 rounded-xl bg-slate-950 text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">-- All Projects --</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}" {{ (string)$selectedProjectId === (string)$p->id ? 'selected' : '' }}>
                                    📁 {{ $p->name }} ({{ $p->code ?? 'PRJ-' . $p->id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cost Center Selector -->
                    <div>
                        <label class="block text-slate-400 font-medium mb-1.5 flex items-center justify-between">
                            <span>Cost Center (Department)</span>
                            @if($selectedCostCenterId)
                                <a href="{{ request()->fullUrlWithQuery(['cost_center_id' => null]) }}" class="text-[10px] text-rose-400 hover:underline">Clear</a>
                            @endif
                        </label>
                        <select name="cost_center_id" class="w-full px-3 py-2 border border-slate-700 rounded-xl bg-slate-950 text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">-- All Cost Centers --</option>
                            @foreach($costCenters as $cc)
                                <option value="{{ $cc->id }}" {{ (string)$selectedCostCenterId === (string)$cc->id ? 'selected' : '' }}>
                                    🏢 {{ $cc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Period Preset -->
                    <div>
                        <label class="block text-slate-400 font-medium mb-1.5">Period</label>
                        <select name="period" id="period_select" onchange="handlePeriodChange(this.value)" class="w-full px-3 py-2 border border-slate-700 rounded-xl bg-slate-950 text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="this_month" {{ $selectedPeriod === 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $selectedPeriod === 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="term_1" {{ $selectedPeriod === 'term_1' ? 'selected' : '' }}>Term 1 (Jan - Apr)</option>
                            <option value="term_2" {{ $selectedPeriod === 'term_2' ? 'selected' : '' }}>Term 2 (May - Aug)</option>
                            <option value="term_3" {{ $selectedPeriod === 'term_3' ? 'selected' : '' }}>Term 3 (Sep - Dec)</option>
                            <option value="this_year" {{ $selectedPeriod === 'this_year' ? 'selected' : '' }}>This Full Year</option>
                            <option value="last_year" {{ $selectedPeriod === 'last_year' ? 'selected' : '' }}>Last Year</option>
                            <option value="all_time" {{ $selectedPeriod === 'all_time' ? 'selected' : '' }}>All Time</option>
                            <option value="custom" {{ $selectedPeriod === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-2 px-4 rounded-xl transition shadow-sm shadow-emerald-500/20 flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Generate Statement
                        </button>
                    </div>
                </div>

                <!-- Custom Date Range Row (visible if custom or specific dates selected) -->
                <div id="custom_date_row" class="{{ $selectedPeriod === 'custom' ? '' : 'hidden' }} pt-2 border-t border-slate-800/80 flex flex-wrap items-center gap-3">
                    <span class="text-slate-400 font-medium">Custom Range:</span>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ $start_date }}"
                               class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-950 text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500">
                        <span class="text-slate-500">to</span>
                        <input type="date" name="end_date" value="{{ $end_date }}"
                               class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-950 text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>
            </form>
        </div>

        <!-- Printable Header (Print only) -->
        <div class="hidden print:block text-center border-b border-gray-300 pb-4 mb-6">
            <h1 class="text-xl font-bold uppercase tracking-wide">{{ $school->name ?? 'School System' }}</h1>
            <h2 class="text-sm font-semibold text-gray-700 mt-1">PROFIT AND LOSS ACCOUNT STATEMENT (INCOME &amp; EXPENDITURE)</h2>
            <p class="text-xs text-gray-600 mt-1">
                @if($project)
                    Target Activity: <strong>Project - {{ $project->name }} ({{ $project->code ?? 'N/A' }})</strong>
                @elseif($costCenter)
                    Target Activity: <strong>Cost Center - {{ $costCenter->name }}</strong>
                @else
                    Target Activity: <strong>General School Operations</strong>
                @endif
                | Period: <strong>{{ $period_label }}</strong> ({{ $start_date }} to {{ $end_date }})
            </p>
            <p class="text-[10px] text-gray-400 mt-1">Generated: {{ now()->format('d M Y H:i:s') }}</p>
        </div>

        <!-- Executive KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Revenue -->
            <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-4 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-400">Total Revenue / Income</span>
                    <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs">
                        💵 Inflow
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-100">
                    ${{ number_format($total_revenue, 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400 flex items-center gap-1">
                    <span class="text-emerald-400 font-semibold">{{ count($revenue_by_account) }}</span> revenue stream(s)
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-400"></div>
            </div>

            <!-- Direct Operating Costs -->
            <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-4 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-400">Direct Operating Costs</span>
                    <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs">
                        ⚙️ COGS / Ops
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold text-amber-300">
                    ${{ number_format($direct_costs, 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400">
                    Gross Profit: <strong class="text-slate-200">${{ number_format($gross_profit, 2) }}</strong> ({{ $gross_margin_percent }}%)
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-yellow-400"></div>
            </div>

            <!-- Overheads & Total Expenses -->
            <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-4 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-400">Total Expenditure</span>
                    <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 text-xs">
                        📉 Outflow
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold text-rose-300">
                    ${{ number_format($total_expenditure, 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400">
                    Overheads: <strong class="text-slate-300">${{ number_format($overhead_costs, 2) }}</strong>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 to-red-400"></div>
            </div>

            <!-- Net Profit / Loss -->
            <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-4 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-400">Net Profit / (Loss)</span>
                    <span class="p-1.5 rounded-lg {{ $net_profit_loss >= 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }} text-xs font-bold">
                        {{ $net_profit_loss >= 0 ? '✓ Surplus' : '⚠ Deficit' }}
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold {{ $net_profit_loss >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    ${{ number_format($net_profit_loss, 2) }}
                </div>
                <div class="mt-1 text-[11px] text-slate-400">
                    Net Margin: <strong class="{{ $net_profit_loss >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ $net_margin_percent }}%</strong>
                    @if($budget_amount > 0)
                        • Budget: <span class="text-slate-300">${{ number_format($budget_amount, 2) }}</span>
                    @endif
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 {{ $net_profit_loss >= 0 ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-gradient-to-r from-rose-500 to-red-400' }}"></div>
            </div>
        </div>

        <!-- Budget Tracking Card (if project or cost center has budget) -->
        @if($budget_amount > 0)
            <div class="no-print bg-slate-900/80 rounded-2xl border border-slate-800 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="text-xs font-semibold text-slate-300 flex items-center gap-2">
                        <span>🎯 Budget Utilization &amp; Variance</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $budget_variance >= 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                            {{ $budget_variance >= 0 ? 'Within Budget' : 'Over Budget' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Allocated: <strong class="text-slate-200">${{ number_format($budget_amount, 2) }}</strong> | 
                        Spent: <strong class="text-rose-300">${{ number_format($total_expenditure, 2) }}</strong> | 
                        Variance Remaining: <strong class="{{ $budget_variance >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">${{ number_format($budget_variance, 2) }}</strong>
                    </p>
                </div>
                <div class="w-full md:w-64">
                    <div class="flex justify-between text-[11px] text-slate-400 mb-1">
                        <span>Progress</span>
                        <span class="font-bold text-slate-200">{{ $budget_utilization_percent }}%</span>
                    </div>
                    <div class="w-full bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-800">
                        <div class="h-2.5 rounded-full {{ $budget_utilization_percent > 100 ? 'bg-rose-500' : ($budget_utilization_percent > 85 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                             style="width: {{ min(100, $budget_utilization_percent) }}%"></div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Profit & Loss Statement Table -->
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-100 flex items-center gap-2">
                        <span>📑</span> Statement of Profit &amp; Loss
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Formal multi-step Income &amp; Expenditure layout</p>
                </div>
                <span class="text-xs text-slate-400">Currency: USD ($)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <!-- SECTION 1: REVENUE -->
                    <thead>
                        <tr class="bg-slate-950/80 text-slate-300 border-b border-slate-800">
                            <th class="py-3 px-6 font-semibold uppercase tracking-wider text-[11px]">1. Operating Revenue &amp; Income</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Code</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Category</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Entries</th>
                            <th class="py-3 px-6 font-semibold text-right text-[11px]">Amount ($)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($revenue_by_account as $rev)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-2.5 px-6 font-medium text-slate-200">
                                    {{ $rev['name'] }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $rev['code'] }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400">
                                    {{ ucwords(str_replace('_', ' ', $rev['category'])) }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300">
                                        {{ $rev['entries_count'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-6 text-right font-semibold text-emerald-400">
                                    ${{ number_format($rev['amount'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 px-6 text-center text-slate-500 italic">
                                    No revenue or income recorded for this period.
                                </td>
                            </tr>
                        @endforelse
                        <!-- Revenue Subtotal -->
                        <tr class="bg-emerald-950/20 border-t-2 border-emerald-500/30 font-bold text-slate-100">
                            <td colspan="4" class="py-3 px-6 text-emerald-400 uppercase tracking-wider text-[11px]">
                                Total Operating Revenue (A)
                            </td>
                            <td class="py-3 px-6 text-right text-emerald-400 text-sm">
                                ${{ number_format($total_revenue, 2) }}
                            </td>
                        </tr>
                    </tbody>

                    <!-- SECTION 2: DIRECT OPERATING COSTS -->
                    <thead>
                        <tr class="bg-slate-950/80 text-slate-300 border-t-2 border-b border-slate-800">
                            <th class="py-3 px-6 font-semibold uppercase tracking-wider text-[11px]">2. Direct Operating Costs / COGS</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Code</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Category</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Entries</th>
                            <th class="py-3 px-6 font-semibold text-right text-[11px]">Amount ($)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @php
                            $directAccounts = collect($expense_by_account)->where('is_direct', true);
                            $overheadAccounts = collect($expense_by_account)->where('is_direct', false);
                        @endphp

                        @forelse($directAccounts as $exp)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-2.5 px-6 font-medium text-slate-200">
                                    {{ $exp['name'] }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $exp['code'] }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400">
                                    {{ ucwords(str_replace('_', ' ', $exp['category'])) }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300">
                                        {{ $exp['entries_count'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-6 text-right font-semibold text-amber-300">
                                    ${{ number_format($exp['amount'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 px-6 text-center text-slate-500 italic">
                                    No direct operating costs tagged for this period.
                                </td>
                            </tr>
                        @endforelse
                        <!-- Direct Costs Subtotal -->
                        <tr class="bg-amber-950/20 border-t border-amber-500/30 font-bold text-slate-100">
                            <td colspan="4" class="py-3 px-6 text-amber-400 uppercase tracking-wider text-[11px]">
                                Total Direct Costs (B)
                            </td>
                            <td class="py-3 px-6 text-right text-amber-400 text-sm">
                                ${{ number_format($direct_costs, 2) }}
                            </td>
                        </tr>
                        <!-- GROSS PROFIT ROW -->
                        <tr class="bg-slate-950 border-t-2 border-b-2 border-slate-700 font-extrabold text-slate-100">
                            <td colspan="4" class="py-3.5 px-6 uppercase tracking-wider text-xs flex items-center gap-2">
                                <span>GROSS PROFIT / (LOSS) MARGIN (A - B)</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $gross_profit >= 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }}">
                                    {{ $gross_margin_percent }}% Margin
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-right text-base {{ $gross_profit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                ${{ number_format($gross_profit, 2) }}
                            </td>
                        </tr>
                    </tbody>

                    <!-- SECTION 3: OVERHEADS & OPERATING EXPENSES -->
                    <thead>
                        <tr class="bg-slate-950/80 text-slate-300 border-t-2 border-b border-slate-800">
                            <th class="py-3 px-6 font-semibold uppercase tracking-wider text-[11px]">3. Overheads &amp; Operating Expenses</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Code</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Category</th>
                            <th class="py-3 px-4 font-semibold text-center text-[11px]">Entries</th>
                            <th class="py-3 px-6 font-semibold text-right text-[11px]">Amount ($)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($overheadAccounts as $exp)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-2.5 px-6 font-medium text-slate-200">
                                    {{ $exp['name'] }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $exp['code'] }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400">
                                    {{ ucwords(str_replace('_', ' ', $exp['category'])) }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-400">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300">
                                        {{ $exp['entries_count'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-6 text-right font-semibold text-rose-300">
                                    ${{ number_format($exp['amount'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 px-6 text-center text-slate-500 italic">
                                    No overhead expenses recorded for this period.
                                </td>
                            </tr>
                        @endforelse
                        <!-- Overheads Subtotal -->
                        <tr class="bg-rose-950/20 border-t border-rose-500/30 font-bold text-slate-100">
                            <td colspan="4" class="py-3 px-6 text-rose-400 uppercase tracking-wider text-[11px]">
                                Total Overheads &amp; Operating Expenses (C)
                            </td>
                            <td class="py-3 px-6 text-right text-rose-400 text-sm">
                                ${{ number_format($overhead_costs, 2) }}
                            </td>
                        </tr>
                    </tbody>

                    <!-- SECTION 4: FINAL NET PROFIT / LOSS SUMMARY -->
                    <tfoot>
                        <tr class="bg-slate-950 border-t-4 border-slate-700 text-slate-100">
                            <td colspan="4" class="py-4 px-6 font-bold uppercase tracking-wider text-xs">
                                TOTAL EXPENDITURE (B + C)
                            </td>
                            <td class="py-4 px-6 text-right font-bold text-rose-400 text-base">
                                ${{ number_format($total_expenditure, 2) }}
                            </td>
                        </tr>
                        <tr class="{{ $net_profit_loss >= 0 ? 'bg-emerald-950/40 text-emerald-300 border-emerald-500/40' : 'bg-rose-950/40 text-rose-300 border-rose-500/40' }} border-t-2 border-b-2 font-black">
                            <td colspan="4" class="py-4 px-6 uppercase tracking-wider text-sm flex items-center gap-2">
                                <span>NET PROFIT / (LOSS) FOR THE PERIOD (A - B - C)</span>
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $net_profit_loss >= 0 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                                    {{ $net_margin_percent }}% Net Margin
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right text-lg font-black {{ $net_profit_loss >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                ${{ number_format($net_profit_loss, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Monthly Trend Breakdown (Screen only) -->
        @if(count($monthly_trend) > 1)
            <div class="no-print bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                            <span>📈</span> Monthly Performance Trend
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Month-by-month trajectory of revenue, expenses, and net profit</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950/80 text-slate-300 border-b border-slate-800">
                                <th class="py-2.5 px-4 font-semibold text-[11px]">Month</th>
                                <th class="py-2.5 px-4 font-semibold text-right text-[11px]">Revenue ($)</th>
                                <th class="py-2.5 px-4 font-semibold text-right text-[11px]">Expenditure ($)</th>
                                <th class="py-2.5 px-4 font-semibold text-right text-[11px]">Net Margin ($)</th>
                                <th class="py-2.5 px-6 font-semibold text-[11px]">Visual Trajectory</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach($monthly_trend as $m)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-2 px-4 font-medium text-slate-200">{{ $m['label'] }}</td>
                                    <td class="py-2 px-4 text-right font-medium text-emerald-400">${{ number_format($m['revenue'], 2) }}</td>
                                    <td class="py-2 px-4 text-right font-medium text-rose-400">${{ number_format($m['expenditure'], 2) }}</td>
                                    <td class="py-2 px-4 text-right font-bold {{ $m['net'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                        ${{ number_format($m['net'], 2) }}
                                    </td>
                                    <td class="py-2 px-6">
                                        @php
                                            $maxVal = max(1.0, (float)$total_revenue, (float)$total_expenditure);
                                            $revPct = round(($m['revenue'] / $maxVal) * 100);
                                            $expPct = round(($m['expenditure'] / $maxVal) * 100);
                                        @endphp
                                        <div class="flex items-center gap-1.5 h-3">
                                            <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $revPct }}%" title="Revenue: ${{ number_format($m['revenue'], 2) }}"></div>
                                            <div class="bg-rose-500 h-2 rounded-full" style="width: {{ $expPct }}%" title="Expenditure: ${{ number_format($m['expenditure'], 2) }}"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Audit Trail / Drilldown Transactions -->
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                        <span>🔍</span> Drilldown Transaction Audit Trail
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Underlying source journal entries, invoices, bills, receipts, and cashbook records</p>
                </div>
                <div class="no-print flex items-center gap-2">
                    <input type="text" id="transaction_search" placeholder="Search memo, ref, account..." 
                           onkeyup="filterTransactions(this.value)"
                           class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-950 text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500 w-56">
                    <span class="text-xs text-slate-400">Total: <strong class="text-slate-200" id="tx_count">{{ count($drilldown) }}</strong></span>
                </div>
            </div>

            <div class="overflow-x-auto max-h-96">
                <table id="drilldown_table" class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-0 bg-slate-950 text-slate-300 border-b border-slate-800 z-10">
                        <tr>
                            <th class="py-2.5 px-4 font-semibold text-[11px]">Date</th>
                            <th class="py-2.5 px-4 font-semibold text-[11px]">Source / Ref</th>
                            <th class="py-2.5 px-4 font-semibold text-[11px]">Account</th>
                            <th class="py-2.5 px-6 font-semibold text-[11px]">Description / Memo</th>
                            <th class="py-2.5 px-4 font-semibold text-center text-[11px]">Type</th>
                            <th class="py-2.5 px-6 font-semibold text-right text-[11px]">Revenue Impact</th>
                            <th class="py-2.5 px-6 font-semibold text-right text-[11px]">Expense Impact</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($drilldown as $tx)
                            <tr class="tx-row hover:bg-slate-800/30 transition">
                                <td class="py-2 px-4 whitespace-nowrap text-slate-300 font-mono text-[11px]">
                                    {{ $tx['date'] }}
                                </td>
                                <td class="py-2 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-300">
                                        {{ strtoupper($tx['source_type']) }}
                                    </span>
                                    <span class="font-mono text-[11px] text-slate-400 ml-1">{{ $tx['reference'] }}</span>
                                </td>
                                <td class="py-2 px-4 whitespace-nowrap">
                                    <span class="font-mono text-[11px] text-slate-400">{{ $tx['account_code'] }}</span>
                                    <span class="text-slate-200 ml-1">{{ $tx['account_name'] }}</span>
                                </td>
                                <td class="py-2 px-6 text-slate-300 max-w-xs truncate" title="{{ $tx['memo'] }}">
                                    {{ $tx['memo'] ?: 'N/A' }}
                                </td>
                                <td class="py-2 px-4 text-center">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono {{ $tx['entry_type'] === 'debit' ? 'bg-blue-500/10 text-blue-400' : 'bg-emerald-500/10 text-emerald-400' }}">
                                        {{ strtoupper($tx['entry_type']) }}
                                    </span>
                                </td>
                                <td class="py-2 px-6 text-right font-medium text-emerald-400 whitespace-nowrap">
                                    @if($tx['revenue_effect'] != 0)
                                        ${{ number_format($tx['revenue_effect'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-2 px-6 text-right font-medium text-rose-400 whitespace-nowrap">
                                    @if($tx['expense_effect'] != 0)
                                        ${{ number_format($tx['expense_effect'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 px-4 text-center text-slate-500 italic">
                                    No transaction records found matching the active filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function handlePeriodChange(val) {
            const customRow = document.getElementById('custom_date_row');
            if (val === 'custom') {
                customRow.classList.remove('hidden');
            } else {
                customRow.classList.add('hidden');
            }
        }

        function filterTransactions(query) {
            const rows = document.querySelectorAll('#drilldown_table .tx-row');
            let visibleCount = 0;
            const q = query.toLowerCase();

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(q)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const countEl = document.getElementById('tx_count');
            if (countEl) countEl.innerText = visibleCount;
        }
    </script>
@endsection
