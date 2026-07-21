@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Budget vs Actual Report</h1>
            <p class="text-xs text-slate-400 mt-1">Fiscal Year {{ $fiscalYear }}</p>
        </div>
        <a href="{{ route('admin.reports.budget-vs-actual') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Budget</div>
            <div class="text-lg font-semibold text-blue-300">{{ number_format($totalBudget, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Actual</div>
            <div class="text-lg font-semibold text-slate-200">{{ number_format($totalActual, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Variance</div>
            <div class="text-lg font-semibold {{ $totalVariance >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                {{ number_format($totalVariance, 2) }}
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Variance %</div>
            <div class="text-lg font-semibold {{ $totalVariancePercent >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                {{ number_format($totalVariancePercent, 1) }}%
            </div>
        </div>
    </div>

    <!-- Detailed Comparison -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Budget</th>
                        <th class="px-4 py-3 text-left font-medium">Account</th>
                        <th class="px-4 py-3 text-right font-medium">Budgeted</th>
                        <th class="px-4 py-3 text-right font-medium">Actual</th>
                        <th class="px-4 py-3 text-right font-medium">Variance</th>
                        <th class="px-4 py-3 text-right font-medium">Variance %</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($budgetComparison as $item)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $item['budget_name'] }}</td>
                            <td class="px-4 py-3">
                                <span class="font-mono">{{ $item['account']->code ?? 'N/A' }}</span><br>
                                <span class="text-slate-400">{{ $item['account']->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($item['budgeted'], 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item['actual'], 2) }}</td>
                            <td class="px-4 py-3 text-right {{ $item['variance'] >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                                {{ number_format($item['variance'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right {{ $item['variance_percent'] >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                                {{ number_format($item['variance_percent'], 1) }}%
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item['variance_percent'] >= 0)
                                    <span class="px-2 py-1 text-xs bg-emerald-900/30 text-emerald-300 rounded">Under Budget</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-red-900/30 text-red-300 rounded">Over Budget</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-950/60 text-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left" colspan="2">Totals</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalBudget, 2) }}</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalActual, 2) }}</th>
                        <th class="px-4 py-3 text-right {{ $totalVariance >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                            {{ number_format($totalVariance, 2) }}
                        </th>
                        <th class="px-4 py-3 text-right {{ $totalVariancePercent >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                            {{ number_format($totalVariancePercent, 1) }}%
                        </th>
                        <th class="px-4 py-3 text-center">
                            @if($totalVariancePercent >= 0)
                                <span class="px-2 py-1 text-xs bg-emerald-900/30 text-emerald-300 rounded">Under Budget</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-red-900/30 text-red-300 rounded">Over Budget</span>
                            @endif
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if(empty($budgetComparison))
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 text-center">
            <div class="text-slate-400 text-sm">No budget data found for fiscal year {{ $fiscalYear }}</div>
            <div class="text-slate-500 text-xs mt-2">Please create budgets and budget lines to see comparisons</div>
        </div>
    @endif
@endsection
