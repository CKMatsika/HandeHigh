@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Student Fee Collection Report</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $start }} to {{ $end }}</p>
        </div>
        <a href="{{ route('admin.reports.student-fee-collection') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
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
                    <option value="month" {{ $groupBy == 'month' ? 'selected' : '' }}>Month</option>
                    <option value="class" {{ $groupBy == 'class' ? 'selected' : '' }}>Class</option>
                    <option value="fee_type" {{ $groupBy == 'fee_type' ? 'selected' : '' }}>Fee Type</option>
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Billed</div>
            <div class="text-lg font-semibold text-blue-300">{{ number_format($totalBilled, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Total Collected</div>
            <div class="text-lg font-semibold text-emerald-300">{{ number_format($totalCollected, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Outstanding Balance</div>
            <div class="text-lg font-semibold text-red-300">{{ number_format($totalBalance, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Collection Rate</div>
            <div class="text-lg font-semibold {{ $overallCollectionRate >= 80 ? 'text-emerald-300' : ($overallCollectionRate >= 60 ? 'text-yellow-300' : 'text-red-300') }}">
                {{ number_format($overallCollectionRate, 1) }}%
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
                            {{ $groupBy === 'month' ? 'Month' : ($groupBy === 'class' ? 'Class' : 'Fee Type') }}
                        </th>
                        <th class="px-4 py-3 text-right font-medium">Invoices</th>
                        <th class="px-4 py-3 text-right font-medium">Billed</th>
                        <th class="px-4 py-3 text-right font-medium">Collected</th>
                        <th class="px-4 py-3 text-right font-medium">Balance</th>
                        <th class="px-4 py-3 text-right font-medium">Collection Rate</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($collections as $key => $collection)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                @if($groupBy === 'month')
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $key)->format('F Y') }}
                                @else
                                    {{ $key }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ $collection['invoice_count'] }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($collection['total_billed'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-300">{{ number_format($collection['total_collected'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-red-300">{{ number_format($collection['balance'], 2) }}</td>
                            <td class="px-4 py-3 text-right {{ $collection['collection_rate'] >= 80 ? 'text-emerald-300' : ($collection['collection_rate'] >= 60 ? 'text-yellow-300' : 'text-red-300') }}">
                                {{ number_format($collection['collection_rate'], 1) }}%
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($collection['collection_rate'] >= 80)
                                    <span class="px-2 py-1 text-xs bg-emerald-900/30 text-emerald-300 rounded">Excellent</span>
                                @elseif($collection['collection_rate'] >= 60)
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
                        <th class="px-4 py-3 text-right">{{ $collections->sum('invoice_count') }}</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalBilled, 2) }}</th>
                        <th class="px-4 py-3 text-right text-emerald-300">{{ number_format($totalCollected, 2) }}</th>
                        <th class="px-4 py-3 text-right text-red-300">{{ number_format($totalBalance, 2) }}</th>
                        <th class="px-4 py-3 text-right {{ $overallCollectionRate >= 80 ? 'text-emerald-300' : ($overallCollectionRate >= 60 ? 'text-yellow-300' : 'text-red-300') }}">
                            {{ number_format($overallCollectionRate, 1) }}%
                        </th>
                        <th class="px-4 py-3 text-center">
                            @if($overallCollectionRate >= 80)
                                <span class="px-2 py-1 text-xs bg-emerald-900/30 text-emerald-300 rounded">Excellent</span>
                            @elseif($overallCollectionRate >= 60)
                                <span class="px-2 py-1 text-xs bg-yellow-900/30 text-yellow-300 rounded">Good</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-red-900/30 text-red-300 rounded">Poor</span>
                            @endif
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($collections->isEmpty())
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 text-center">
            <div class="text-slate-400 text-sm">No fee collection data found for the selected period</div>
            <div class="text-slate-500 text-xs mt-2">Try adjusting the date range or check if invoices have been created</div>
        </div>
    @endif
@endsection
