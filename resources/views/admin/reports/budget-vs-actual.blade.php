@extends('layouts.app')

@section('content')
    <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Budget vs Actual Performance Report</h1>
            <p class="text-xs text-slate-400 mt-1">General Ledger & Chart of Accounts comparison for Fiscal Year {{ $fiscalYear }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.reports.budget-vs-actual') }}" class="flex items-center gap-2">
                <label for="fiscal_year" class="text-xs text-slate-400">Fiscal Year:</label>
                <select id="fiscal_year" name="fiscal_year" onchange="this.form.submit()" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @foreach(range(now()->year - 2, now()->year + 2) as $year)
                        <option value="{{ $year }}" {{ $fiscalYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.budgets.index') }}" class="px-3 py-1.5 text-xs font-medium rounded-xl bg-indigo-600/80 text-white hover:bg-indigo-500 transition">
                Manage Budgets
            </a>
            <button onclick="window.print()" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700 transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print
            </button>
            <a href="{{ route('admin.reports.budget-vs-actual', ['fiscal_year' => $fiscalYear]) }}" class="text-xs text-slate-400 hover:text-white px-2 py-1.5">Refresh</a>
        </div>
    </div>

    <x-documents.school-header 
        :school="$school ?? null"
        title="OFFICIAL BUDGET VS ACTUAL PERFORMANCE REPORT"
        :subtitle="'Fiscal Year: ' . $fiscalYear . ' · Net Operating Surplus: $' . number_format($netOperatingSurplus ?? ($totalActualRevenue - $totalActualExpense), 2)"
        :date="now()"
    />

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                <span>Actual Revenue</span>
                <span class="text-emerald-400 font-mono text-[11px]">GL Credit</span>
            </div>
            <div class="text-xl font-bold text-emerald-400">${{ number_format($totalActualRevenue, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">
                Budgeted: <span class="font-mono text-slate-300">${{ number_format($totalBudgetedRevenue, 2) }}</span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                <span>Actual Expenditure</span>
                <span class="text-rose-400 font-mono text-[11px]">GL Debit</span>
            </div>
            <div class="text-xl font-bold text-slate-200">${{ number_format($totalActualExpense, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">
                Budgeted: <span class="font-mono text-slate-300">${{ number_format($totalBudgetedExpense, 2) }}</span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Net Surplus / (Deficit)</div>
            <div class="text-xl font-bold {{ $netOperatingSurplus >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                ${{ number_format($netOperatingSurplus, 2) }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Revenue minus Expenditure
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Variance</div>
            <div class="text-xl font-bold {{ $totalVariance >= 0 ? 'text-blue-400' : 'text-amber-400' }}">
                ${{ number_format($totalVariance, 2) }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Execution Variance: <span class="font-semibold {{ $totalVariancePercent >= 0 ? 'text-emerald-400' : 'text-amber-400' }}">{{ number_format($totalVariancePercent, 1) }}%</span>
            </div>
        </div>
    </div>

    <!-- 1. Revenue Budget vs Actual Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden mb-6">
        <div class="px-4 py-3 bg-slate-950/70 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                <h2 class="text-xs font-semibold text-slate-100 uppercase tracking-wider">1. Revenue Streams (Inflows)</h2>
            </div>
            <div class="text-xs font-mono font-semibold text-emerald-400">
                Actual: ${{ number_format($totalActualRevenue, 2) }}
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/40 text-slate-400 border-b border-slate-800/80">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-medium">Account Code & Name</th>
                        <th class="px-4 py-2.5 text-left font-medium">Budget Source</th>
                        <th class="px-4 py-2.5 text-right font-medium">Budgeted ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Actual ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Variance ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Performance %</th>
                        <th class="px-4 py-2.5 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($revenueComparison as $item)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-2.5">
                                <span class="font-mono text-emerald-300 font-semibold">{{ $item['account']->code ?? 'N/A' }}</span>
                                <span class="text-slate-200 ml-1.5">{{ $item['account']->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-400 text-[11px]">{{ $item['budget_name'] }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-300">{{ number_format($item['budgeted'], 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-emerald-400">{{ number_format($item['actual'], 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono {{ $item['variance'] >= 0 ? 'text-emerald-300' : 'text-slate-400' }}">
                                {{ $item['variance'] >= 0 ? '+' : '' }}{{ number_format($item['variance'], 2) }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono {{ $item['variance_percent'] >= 100 ? 'text-emerald-300' : 'text-slate-300' }}">
                                {{ number_format($item['variance_percent'], 1) }}%
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                @if($item['actual'] > 0 && $item['actual'] >= $item['budgeted'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-950/80 text-emerald-300 border border-emerald-700/50">Target Met</span>
                                @elseif($item['actual'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-950/80 text-blue-300 border border-blue-700/50">Active Inflow</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400 border border-slate-700">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-slate-500">No revenue accounts recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-950/80 text-slate-200 border-t border-slate-700">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold" colspan="2">Total Revenue Inflows</th>
                        <th class="px-4 py-2.5 text-right font-mono font-semibold">${{ number_format($totalBudgetedRevenue, 2) }}</th>
                        <th class="px-4 py-2.5 text-right font-mono font-bold text-emerald-400">${{ number_format($totalActualRevenue, 2) }}</th>
                        <th class="px-4 py-2.5 text-right font-mono font-semibold text-emerald-300">
                            {{ ($totalActualRevenue - $totalBudgetedRevenue) >= 0 ? '+' : '' }}${{ number_format($totalActualRevenue - $totalBudgetedRevenue, 2) }}
                        </th>
                        <th class="px-4 py-2.5 text-right font-mono font-semibold" colspan="2">
                            {{ $totalBudgetedRevenue > 0 ? number_format(($totalActualRevenue / $totalBudgetedRevenue) * 100, 1) . '%' : ($totalActualRevenue > 0 ? '100.0%' : '0.0%') }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 2. Expenditure Budget vs Actual Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden mb-6">
        <div class="px-4 py-3 bg-slate-950/70 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                <h2 class="text-xs font-semibold text-slate-100 uppercase tracking-wider">2. Operating Expenses (Outflows)</h2>
            </div>
            <div class="text-xs font-mono font-semibold text-slate-300">
                Actual: ${{ number_format($totalActualExpense, 2) }}
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/40 text-slate-400 border-b border-slate-800/80">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-medium">Account Code & Name</th>
                        <th class="px-4 py-2.5 text-left font-medium">Budget Source</th>
                        <th class="px-4 py-2.5 text-right font-medium">Budgeted ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Actual ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Variance / Savings ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Utilization %</th>
                        <th class="px-4 py-2.5 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($expenseComparison as $item)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-2.5">
                                <span class="font-mono text-rose-300 font-semibold">{{ $item['account']->code ?? 'N/A' }}</span>
                                <span class="text-slate-200 ml-1.5">{{ $item['account']->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-400 text-[11px]">{{ $item['budget_name'] }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-300">{{ number_format($item['budgeted'], 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-100">{{ number_format($item['actual'], 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono {{ $item['variance'] >= 0 ? 'text-emerald-300' : 'text-rose-400' }}">
                                {{ number_format($item['variance'], 2) }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono {{ $item['budgeted'] > 0 && ($item['actual'] / $item['budgeted']) > 1 ? 'text-rose-400' : 'text-slate-300' }}">
                                {{ $item['budgeted'] > 0 ? number_format(($item['actual'] / $item['budgeted']) * 100, 1) . '%' : ($item['actual'] > 0 ? 'Unbudgeted' : '0.0%') }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                @if($item['actual'] <= $item['budgeted'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-950/80 text-emerald-300 border border-emerald-700/50">Under Budget</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-950/80 text-rose-300 border border-rose-700/50">Over Budget</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-slate-500">No expense accounts recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-950/80 text-slate-200 border-t border-slate-700">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold" colspan="2">Total Operating Expenses</th>
                        <th class="px-4 py-2.5 text-right font-mono font-semibold">${{ number_format($totalBudgetedExpense, 2) }}</th>
                        <th class="px-4 py-2.5 text-right font-mono font-bold text-slate-100">${{ number_format($totalActualExpense, 2) }}</th>
                        <th class="px-4 py-2.5 text-right font-mono font-semibold {{ ($totalBudgetedExpense - $totalActualExpense) >= 0 ? 'text-emerald-300' : 'text-rose-400' }}">
                            ${{ number_format($totalBudgetedExpense - $totalActualExpense, 2) }}
                        </th>
                        <th class="px-4 py-2.5 text-right font-mono font-semibold" colspan="2">
                            {{ $totalBudgetedExpense > 0 ? number_format(($totalActualExpense / $totalBudgetedExpense) * 100, 1) . '%' : ($totalActualExpense > 0 ? 'Unbudgeted' : '0.0%') }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Quick Link Banner if no Budgets exist -->
    @if(empty($budgets) || $budgets->count() === 0)
        <div class="rounded-2xl border border-indigo-900/60 bg-indigo-950/30 p-4 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-semibold text-indigo-300">Set Official Fiscal Year {{ $fiscalYear }} Budget Targets</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Actual transactions are currently filtering live from the General Ledger. Create a formal budget to track line-by-line variances.</p>
            </div>
            <a href="{{ route('admin.budgets.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-500 transition whitespace-nowrap">
                + Create {{ $fiscalYear }} Budget
            </a>
        </div>
    @endif

    <x-documents.school-footer 
        :school="$school ?? null"
        :show-banking="false"
        notice="Official school departmental budget execution and General Ledger variance audit report."
    />
@endsection

