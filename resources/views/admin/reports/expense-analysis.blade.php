@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Expense Analysis Report</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $start }} to {{ $end }}</p>
        </div>
        <a href="{{ route('admin.reports.expense-analysis') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
    </div>

    <!-- Filters -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 mb-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div>
                <label class="block text-xs text-slate-400 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $start }}" 
                       class="px-3 py-1 text-xs bg-slate-800 border border-slate-700 rounded text-slate-200">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $end }}" 
                       class="px-3 py-1 text-xs bg-slate-800 border border-slate-700 rounded text-slate-200">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Group By</label>
                <select name="group_by" 
                        class="px-3 py-1 text-xs bg-slate-800 border border-slate-700 rounded text-slate-200">
                    <option value="category" {{ $groupBy == 'category' ? 'selected' : '' }}>Category</option>
                    <option value="account" {{ $groupBy == 'account' ? 'selected' : '' }}>Account</option>
                    <option value="month" {{ $groupBy == 'month' ? 'selected' : '' }}>Month</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="px-4 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Expenses</div>
            <div class="text-lg font-semibold text-red-300">{{ number_format($totalExpenses, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Average Expense</div>
            <div class="text-lg font-semibold text-slate-200">{{ number_format($averageExpense, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Top Expense</div>
            <div class="text-lg font-semibold text-orange-300">
                @if($topExpense)
                    @if($groupBy === 'category')
                        {{ $topExpense['total'] }}
                    @elseif($groupBy === 'account')
                        {{ $topExpense['total'] }}
                    @else
                        {{ $topExpense['total'] }}
                    @endif
                @else
                    0.00
                @endif
            </div>
        </div>
    </div>

    <!-- Detailed Breakdown -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">
                            {{ $groupBy === 'category' ? 'Category' : ($groupBy === 'account' ? 'Account' : 'Month') }}
                        </th>
                        <th class="px-4 py-3 text-right font-medium">Count</th>
                        <th class="px-4 py-3 text-right font-medium">Amount</th>
                        <th class="px-4 py-3 text-right font-medium">% of Total</th>
                        <th class="px-4 py-3 text-left font-medium">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($expenses as $key => $expense)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                @if($groupBy === 'category')
                                    {{ $key }}
                                @elseif($groupBy === 'account')
                                    <span class="font-mono">{{ $expense['account']->code }}</span><br>
                                    <span class="text-slate-400">{{ $expense['account']->name }}</span>
                                @else
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $key)->format('F Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ $expense['count'] }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($expense['total'], 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($totalExpenses > 0)
                                    {{ number_format(($expense['total'] / $totalExpenses) * 100, 1) }}%
                                @else
                                    0.0%
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-400 text-xs">
                                @if($groupBy === 'category')
                                    {{ $expense['accounts']->count() }} accounts
                                @elseif($groupBy === 'account')
                                    {{ $expense['account']->category }}
                                @else
                                    {{ $expense['entries']->count() }} transactions
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-950/60 text-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Totals</th>
                        <th class="px-4 py-3 text-right">{{ $expenses->sum('count') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ number_format($totalExpenses, 2) }}</th>
                        <th class="px-4 py-3 text-right">100.0%</th>
                        <th class="px-4 py-3 text-left"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($expenses->isEmpty())
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 text-center">
            <div class="text-slate-400 text-sm">No expense data found for the selected period</div>
            <div class="text-slate-500 text-xs mt-2">Try adjusting the date range or check if expense transactions have been recorded</div>
        </div>
    @endif
@endsection
