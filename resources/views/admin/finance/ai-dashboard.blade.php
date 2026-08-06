@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">AI Financial Advisor</h1>
            <p class="text-xs text-slate-400 mt-1">Intelligent insights and predictions for your school's finances.</p>
        </div>
        <a href="{{ route('admin.ai-finance.dashboard') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
    </div>

    <!-- Financial Health Score + Key Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <!-- Health Score -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-xs font-medium text-slate-400 mb-4">Financial Health</h2>
            <div class="flex items-center justify-center mb-4">
                <div class="relative w-32 h-32">
                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#1e293b" stroke-width="10"/>
                        <circle cx="60" cy="60" r="54" fill="none"
                                stroke="{{ $healthScore['status'] === 'healthy' ? '#22c55e' : ($healthScore['status'] === 'needs_attention' ? '#eab308' : '#ef4444') }}"
                                stroke-width="10"
                                stroke-dasharray="{{ ($healthScore['score'] / 100) * 339.292 }} 339.292"
                                stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <span class="text-3xl font-bold text-slate-50">{{ $healthScore['score'] }}</span>
                            <span class="text-xs text-slate-400 block">/100</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <span class="px-3 py-1 rounded-full text-xs font-medium
                    {{ $healthScore['status'] === 'healthy' ? 'bg-emerald-500/20 text-emerald-300' : ($healthScore['status'] === 'needs_attention' ? 'bg-amber-500/20 text-amber-300' : 'bg-red-500/20 text-red-300') }}">
                    {{ ucfirst(str_replace('_', ' ', $healthScore['status'])) }}
                </span>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($healthScore['breakdown'] as $key => $item)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">{{ $item['label'] }}</span>
                        <span class="text-slate-200">{{ number_format($item['value'], 1) }}%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $item['status'] === 'good' ? 'bg-emerald-500' : ($item['status'] === 'warning' ? 'bg-amber-500' : 'bg-red-500') }}"
                             style="width: {{ ($item['value'] / 100) * 100 }}%"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Revenue (YTD)</p>
                        <p class="text-lg font-bold text-emerald-400">${{ number_format($metrics['revenue_ytd'], 0) }}</p>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500">Avg ${{ number_format($metrics['avg_monthly_revenue'], 0) }}/mo</p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Expenses (YTD)</p>
                        <p class="text-lg font-bold text-red-400">${{ number_format($metrics['expenses_ytd'], 0) }}</p>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500">Avg ${{ number_format($metrics['avg_monthly_expenses'], 0) }}/mo</p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full {{ $metrics['net_position'] >= 0 ? 'bg-blue-500/20' : 'bg-amber-500/20' }} flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $metrics['net_position'] >= 0 ? 'text-blue-400' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Net Position</p>
                        <p class="text-lg font-bold {{ $metrics['net_position'] >= 0 ? 'text-blue-400' : 'text-amber-400' }}">
                            ${{ number_format($metrics['net_position'], 0) }}
                        </p>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500">Revenue - Expenses</p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Outstanding AR</p>
                        <p class="text-lg font-bold text-amber-400">${{ number_format($metrics['outstanding_receivables'], 0) }}</p>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500">Unpaid invoices</p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Outstanding AP</p>
                        <p class="text-lg font-bold text-purple-400">${{ number_format($metrics['outstanding_payables'], 0) }}</p>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500">Unpaid bills</p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-cyan-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Cash Flow Ratio</p>
                        <p class="text-lg font-bold text-cyan-400">{{ $healthScore['breakdown']['cash_position']['value'] ?? 0 }}%</p>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500">Income vs expenses</p>
            </div>
        </div>
    </div>

    <!-- Smart Insights -->
    @if(count($insights) > 0)
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 mb-6">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Smart Insights</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($insights as $insight)
                    <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center
                                {{ ($insight['impact'] ?? '') === 'positive' ? 'bg-emerald-500/20' : (($insight['impact'] ?? '') === 'negative' ? 'bg-red-500/20' : 'bg-blue-500/20') }}">
                                @if(($insight['icon'] ?? '') === 'trending_up')
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                @elseif(($insight['icon'] ?? '') === 'trending_down')
                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/></svg>
                                @elseif(($insight['icon'] ?? '') === 'alert')
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                @elseif(($insight['icon'] ?? '') === 'warning')
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-xs font-medium text-slate-200">{{ $insight['title'] }}</h3>
                                <p class="text-[11px] text-slate-400 mt-1">{{ $insight['description'] }}</p>
                                @if($insight['actionable'] ?? null)
                                    <p class="text-[10px] text-indigo-400 mt-2 italic">{{ $insight['actionable'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Cash Flow Forecast -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-sm font-medium text-slate-50 mb-4">3-Month Cash Flow Forecast</h2>
            <div class="space-y-4">
                @foreach($forecast as $month)
                    <div class="rounded-xl border border-slate-700 bg-slate-800/50 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-medium text-slate-200">{{ $month['month'] }}</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] {{ $month['confidence'] === 'moderate' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                                {{ ucfirst($month['confidence']) }} confidence
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-xs">
                            <div>
                                <span class="text-slate-500">Income</span>
                                <p class="text-emerald-400 font-medium mt-1">${{ number_format($month['projected_income'], 0) }}</p>
                            </div>
                            <div>
                                <span class="text-slate-500">Expenses</span>
                                <p class="text-red-400 font-medium mt-1">${{ number_format($month['projected_expenses'], 0) }}</p>
                            </div>
                            <div>
                                <span class="text-slate-500">Net</span>
                                <p class="font-medium mt-1 {{ $month['net_flow'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                    ${{ number_format($month['net_flow'], 0) }}
                                </p>
                            </div>
                        </div>
                        <div class="w-full bg-slate-700 rounded-full h-1.5 mt-3">
                            <div class="h-1.5 rounded-full {{ $month['net_flow'] >= 0 ? 'bg-emerald-500' : 'bg-red-500' }}"
                                 style="width: {{ min(100, abs($month['net_flow']) / max($month['projected_income'], 1) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Anomalies -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Anomaly Detection</h2>
            @if(count($anomalies) > 0)
                <div class="space-y-3 max-h-[400px] overflow-y-auto">
                    @foreach($anomalies as $anomaly)
                        <div class="rounded-xl border p-3 {{ ($anomaly['severity'] ?? '') === 'critical' ? 'border-red-500/30 bg-red-500/5' : (($anomaly['severity'] ?? '') === 'high' ? 'border-amber-500/30 bg-amber-500/5' : 'border-slate-700 bg-slate-800/50') }}">
                            <div class="flex items-start gap-2">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase mt-0.5
                                    {{ ($anomaly['severity'] ?? '') === 'critical' ? 'bg-red-500/20 text-red-300' : (($anomaly['severity'] ?? '') === 'high' ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-600 text-slate-300') }}">
                                    {{ $anomaly['severity'] ?? 'low' }}
                                </span>
                                <div class="flex-1">
                                    <h4 class="text-xs font-medium text-slate-200">{{ $anomaly['title'] }}</h4>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $anomaly['description'] }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ $anomaly['detail'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-12 h-12 mx-auto bg-emerald-500/20 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-400">No anomalies detected</p>
                    <p class="text-[10px] text-slate-500 mt-1">All transactions look normal</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Revenue & Expense Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Revenue by Category (This Month)</h2>
            @if(count($revenueBreakdown) > 0)
                <div class="space-y-3">
                    @foreach($revenueBreakdown as $item)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-300">{{ $item['category'] }}</span>
                                <span class="text-slate-200 font-medium">${{ number_format($item['amount'], 2) }} <span class="text-slate-500">({{ $item['percentage'] }}%)</span></span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $item['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-500 text-center py-4">No revenue data this month</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Expenses by Category (This Month)</h2>
            @if(count($expenseBreakdown) > 0)
                <div class="space-y-3">
                    @foreach($expenseBreakdown as $item)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-300">{{ $item['category'] }}</span>
                                <span class="text-slate-200 font-medium">${{ number_format($item['amount'], 2) }} <span class="text-slate-500">({{ $item['percentage'] }}%)</span></span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full" style="width: {{ $item['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-500 text-center py-4">No expense data this month</p>
            @endif
        </div>
    </div>

    <!-- Budget Recommendations -->
    @if(count($recommendations) > 0)
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Budget Recommendations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($recommendations as $rec)
                    <div class="rounded-xl border p-4 {{ ($rec['priority'] ?? '') === 'high' ? 'border-red-500/30 bg-red-500/5' : 'border-slate-700 bg-slate-800/50' }}">
                        <div class="flex items-start gap-2">
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ ($rec['priority'] ?? '') === 'high' ? 'bg-red-500/20 text-red-300' : 'bg-blue-500/20 text-blue-300' }}">
                                {{ $rec['priority'] ?? 'info' }}
                            </span>
                            <div>
                                <h4 class="text-xs font-medium text-slate-200">{{ $rec['title'] }}</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $rec['description'] }}</p>
                                <p class="text-[10px] text-indigo-400 mt-1 italic">{{ $rec['suggestion'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
