@extends('layouts.app')

@section('content')
    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Departmental Performance Report</h1>
            <p class="text-xs text-slate-400 mt-1">Fiscal Year {{ $fiscalYear }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
            <a href="{{ route('admin.reports.departmental-performance') }}" class="text-xs text-slate-300 hover:text-white px-3 py-2">Refresh</a>
        </div>
    </div>

    <x-documents.school-header 
        :school="$school ?? null"
        title="Official Departmental Financial Performance & Efficiency Audit"
        :subtitle="'Fiscal Year: ' . $fiscalYear . ' · Net Performance: $' . number_format($overallNetPerformance, 2) . ' · Budget Utilization: ' . number_format($overallBudgetUtilization, 1) . '%'"
        :date="now()"
    />

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Budget</div>
            <div class="text-lg font-semibold text-blue-300">{{ number_format($totalBudget, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Expenses</div>
            <div class="text-lg font-semibold text-red-300">{{ number_format($totalExpenses, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Revenue</div>
            <div class="text-lg font-semibold text-emerald-300">{{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Net Performance</div>
            <div class="text-lg font-semibold {{ $overallNetPerformance >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                {{ number_format($overallNetPerformance, 2) }}
            </div>
        </div>
    </div>

    <!-- Detailed Department Performance -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Department</th>
                        <th class="px-4 py-3 text-right font-medium">Budget</th>
                        <th class="px-4 py-3 text-right font-medium">Expenses</th>
                        <th class="px-4 py-3 text-right font-medium">Revenue</th>
                        <th class="px-4 py-3 text-right font-medium">Budget Utilization</th>
                        <th class="px-4 py-3 text-right font-medium">Net Performance</th>
                        <th class="px-4 py-3 text-right font-medium">Efficiency Ratio</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($departmentPerformance as $perf)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $perf['department']->name }}</div>
                                <div class="text-slate-400 text-xs">{{ $perf['department']->head_of_department ?? 'No Head' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($perf['budget'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-red-300">{{ number_format($perf['expenses'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-300">{{ number_format($perf['revenue'], 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="{{ $perf['budget_utilization'] > 100 ? 'text-red-300' : ($perf['budget_utilization'] > 80 ? 'text-yellow-300' : 'text-emerald-300') }}">
                                    {{ number_format($perf['budget_utilization'], 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right {{ $perf['net_performance'] >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                                {{ number_format($perf['net_performance'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="{{ $perf['efficiency_ratio'] >= 100 ? 'text-emerald-300' : ($perf['efficiency_ratio'] >= 80 ? 'text-yellow-300' : 'text-red-300') }}">
                                    {{ number_format($perf['efficiency_ratio'], 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($perf['net_performance'] >= 0 && $perf['budget_utilization'] <= 100)
                                    <span class="px-2 py-1 text-xs bg-emerald-900/30 text-emerald-300 rounded">Excellent</span>
                                @elseif($perf['net_performance'] >= 0 || $perf['budget_utilization'] <= 110)
                                    <span class="px-2 py-1 text-xs bg-yellow-900/30 text-yellow-300 rounded">Good</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-red-900/30 text-red-300 rounded">Poor</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-950/60 text-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Totals</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalBudget, 2) }}</th>
                        <th class="px-4 py-3 text-right text-red-300">{{ number_format($totalExpenses, 2) }}</th>
                        <th class="px-4 py-3 text-right text-emerald-300">{{ number_format($totalRevenue, 2) }}</th>
                        <th class="px-4 py-3 text-right {{ $overallBudgetUtilization > 100 ? 'text-red-300' : ($overallBudgetUtilization > 80 ? 'text-yellow-300' : 'text-emerald-300') }}">
                            {{ number_format($overallBudgetUtilization, 1) }}%
                        </th>
                        <th class="px-4 py-3 text-right {{ $overallNetPerformance >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                            {{ number_format($overallNetPerformance, 2) }}
                        </th>
                        <th class="px-4 py-3 text-right {{ $overallEfficiencyRatio >= 100 ? 'text-emerald-300' : ($overallEfficiencyRatio >= 80 ? 'text-yellow-300' : 'text-red-300') }}">
                            {{ number_format($overallEfficiencyRatio, 1) }}%
                        </th>
                            <th class="px-4 py-3 text-center">
                                @if($overallNetPerformance >= 0 && $overallBudgetUtilization <= 100)
                                    <span class="px-2 py-1 text-xs bg-emerald-900/30 text-emerald-300 rounded">Excellent</span>
                                @elseif($overallNetPerformance >= 0 || $overallBudgetUtilization <= 110)
                                    <span class="px-2 py-1 text-xs bg-yellow-900/30 text-yellow-300 rounded">Good</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-red-900/30 text-red-300 rounded">Poor</span>
                                @endif
                            </th>
                        </tr>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if(empty($departmentPerformance))
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 text-center">
            <div class="text-slate-400 text-sm">No departments found</div>
            <div class="text-slate-500 text-xs mt-2">Please create departments and assign budgets to see performance data</div>
        </div>
    @endif

    <x-documents.school-footer 
        :school="$school ?? null"
        :show-banking="false"
        notice="Official departmental financial performance and budget utilization statement."
    />
@endsection
