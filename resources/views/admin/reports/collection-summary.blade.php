@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.finance-dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Financial Reports</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-300">Collection Summary</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">Fee Collection Summary & Trends</h1>
            <p class="text-xs text-slate-400 mt-0.5">Year-over-year comparison, monthly trajectories, and term collections</p>
        </div>
        <form method="GET" action="{{ route('admin.reports.collection-summary') }}" class="flex items-center gap-2">
            <select name="year" onchange="this.form.submit()" class="text-xs rounded-xl bg-slate-900 border border-slate-800 text-slate-200 px-3 py-2">
                @for($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ $current_year == $y ? 'selected' : '' }}>Fiscal Year {{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    <!-- YoY KPI Comparison -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <span class="text-xs font-medium text-slate-400">Total Collected ({{ $current_year }})</span>
            <div class="text-2xl font-bold text-emerald-400 mt-1">${{ number_format($total_current_year, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">Current year collections</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <span class="text-xs font-medium text-slate-400">Total Collected ({{ $previous_year }})</span>
            <div class="text-2xl font-bold text-slate-300 mt-1">${{ number_format($total_previous_year, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">Prior year benchmark</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <span class="text-xs font-medium text-slate-400">Year-over-Year Growth</span>
            <div class="text-2xl font-bold {{ $year_growth_percent >= 0 ? 'text-emerald-400' : 'text-red-400' }} mt-1">
                {{ $year_growth_percent >= 0 ? '+' : '' }}{{ $year_growth_percent }}%
            </div>
            <div class="text-xs text-slate-500 mt-1">Relative to {{ $previous_year }}</div>
        </div>
    </div>

    <!-- Monthly Collection Comparison Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
        <h2 class="text-sm font-semibold text-slate-200 mb-4">Monthly Collection Trajectory ({{ $current_year }} vs {{ $previous_year }})</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="text-slate-300 bg-slate-950/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Month</th>
                        <th class="px-4 py-3 text-right font-medium">{{ $current_year }} ($)</th>
                        <th class="px-4 py-3 text-right font-medium">{{ $previous_year }} ($)</th>
                        <th class="px-4 py-3 text-right font-medium">Variance ($)</th>
                        <th class="px-4 py-3 text-right font-medium">Growth %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($monthly_comparison as $m)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3 font-medium text-slate-200">{{ $m['month_name'] }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-emerald-400">${{ number_format($m['current_year'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-400">${{ number_format($m['previous_year'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono {{ $m['variance'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $m['variance'] >= 0 ? '+' : '' }}${{ number_format($m['variance'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono {{ $m['variance_percent'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $m['variance_percent'] >= 0 ? '+' : '' }}{{ $m['variance_percent'] }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Term Collections Breakdown -->
    @if($term_collections->isNotEmpty())
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
            <h2 class="text-sm font-semibold text-slate-200 mb-4">Term-by-Term Fee Recovery ({{ $current_year }})</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($term_collections as $term)
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                        <span class="text-xs font-bold text-blue-400">{{ $term['term'] }}</span>
                        <div class="mt-3 space-y-1.5 text-xs">
                            <div class="flex justify-between text-slate-400">
                                <span>Billed:</span>
                                <span class="font-mono text-slate-200">${{ number_format($term['billed'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Collected:</span>
                                <span class="font-mono text-emerald-400">${{ number_format($term['collected'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Outstanding:</span>
                                <span class="font-mono text-red-400">${{ number_format($term['outstanding'], 2) }}</span>
                            </div>
                            <div class="pt-2 border-t border-slate-800 flex justify-between font-semibold">
                                <span class="text-slate-300">Recovery Rate:</span>
                                <span class="text-emerald-400">{{ $term['collection_rate'] }}%</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
