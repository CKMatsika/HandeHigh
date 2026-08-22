@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">Finance & Accounting Intelligence</h1>
            <p class="text-xs text-slate-400 mt-1">Real-time financial status, fee collection KPIs, and aged debtors tracking</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reports.debtors-aging') }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-red-500/20 text-red-300 border border-red-500/30 hover:bg-red-500/30 transition">
                Debtors Aging Matrix
            </a>
            <a href="{{ route('admin.reports.fee-collections') }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/30 transition">
                Fee Collections Register
            </a>
        </div>
    </div>

    <!-- Core KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Total Billed (Invoiced)</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-100 mt-2">${{ number_format($kpis['total_billed'], 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">Cumulative student billings</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Total Collected</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-emerald-400 mt-2">${{ number_format($kpis['total_collected'], 2) }}</div>
            <div class="text-xs text-emerald-500/80 mt-1">{{ $kpis['collection_rate'] }}% collection efficiency</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Total Outstanding Debt</span>
                <span class="p-2 rounded-xl bg-red-500/10 text-red-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-red-400 mt-2">${{ number_format($kpis['total_outstanding'], 2) }}</div>
            <div class="text-xs text-red-400/70 mt-1">{{ count($kpis['top_debtors']) }} active debtor accounts</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Critical Overdue (>90 Days)</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-amber-400 mt-2">${{ number_format($kpis['aging_totals']['bucket_91_120'] + $kpis['aging_totals']['bucket_120_plus'], 2) }}</div>
            <div class="text-xs text-amber-400/70 mt-1">{{ $kpis['bucket_counts']['91_120'] + $kpis['bucket_counts']['120_plus'] }} overdue invoices requiring recovery</div>
        </div>
    </div>

    <!-- Aging Overview Matrix Grid -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-200">Debtors Aging Buckets</h2>
            <a href="{{ route('admin.reports.debtors-aging') }}" class="text-xs text-blue-400 hover:text-blue-300">View Full Aging Analysis &rarr;</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                <div class="text-xs text-emerald-400 font-medium">Current</div>
                <div class="text-lg font-bold text-slate-100 mt-1">${{ number_format($kpis['aging_totals']['current'], 2) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ $kpis['bucket_counts']['current'] }} invoices</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                <div class="text-xs text-teal-400 font-medium">1–30 Days</div>
                <div class="text-lg font-bold text-slate-100 mt-1">${{ number_format($kpis['aging_totals']['bucket_1_30'], 2) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ $kpis['bucket_counts']['1_30'] }} invoices</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                <div class="text-xs text-yellow-400 font-medium">31–60 Days</div>
                <div class="text-lg font-bold text-slate-100 mt-1">${{ number_format($kpis['aging_totals']['bucket_31_60'], 2) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ $kpis['bucket_counts']['31_60'] }} invoices</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                <div class="text-xs text-orange-400 font-medium">61–90 Days</div>
                <div class="text-lg font-bold text-slate-100 mt-1">${{ number_format($kpis['aging_totals']['bucket_61_90'], 2) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ $kpis['bucket_counts']['61_90'] }} invoices</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                <div class="text-xs text-red-400 font-medium">91–120 Days</div>
                <div class="text-lg font-bold text-slate-100 mt-1">${{ number_format($kpis['aging_totals']['bucket_91_120'], 2) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ $kpis['bucket_counts']['91_120'] }} invoices</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                <div class="text-xs text-red-500 font-medium">120+ Days</div>
                <div class="text-lg font-bold text-slate-100 mt-1">${{ number_format($kpis['aging_totals']['bucket_120_plus'], 2) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ $kpis['bucket_counts']['120_plus'] }} invoices</div>
            </div>
        </div>
    </div>

    <!-- Two-Column Section: Top Debtors & Outstanding By Form -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top 10 Debtors -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-200">Top Debtor Accounts</h3>
                <a href="{{ route('admin.reports.outstanding-fees') }}" class="text-xs text-blue-400 hover:text-blue-300">View All Outstanding &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="text-slate-400 bg-slate-950/40">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Student / Payer</th>
                            <th class="px-3 py-2 text-left font-medium">Adm #</th>
                            <th class="px-3 py-2 text-left font-medium">Form / Class</th>
                            <th class="px-3 py-2 text-right font-medium">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($kpis['top_debtors'] as $debtor)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="px-3 py-2.5 font-medium text-slate-200">{{ $debtor['student_name'] }}</td>
                                <td class="px-3 py-2.5 text-slate-400 font-mono">{{ $debtor['admission_number'] }}</td>
                                <td class="px-3 py-2.5 text-slate-400">{{ $debtor['form'] }} ({{ $debtor['class_name'] }})</td>
                                <td class="px-3 py-2.5 text-right font-semibold text-red-400">${{ number_format($debtor['total_debt'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">No outstanding debtor balances recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Collections by Payment Method & Form Summary -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-3">Collections by Payment Method</h3>
                <div class="space-y-3">
                    @forelse($kpis['collections_by_method'] as $method)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-medium text-slate-300">{{ $method['method'] }} ({{ $method['count'] }} txns)</span>
                                <span class="font-semibold text-emerald-400">${{ number_format($method['total'], 2) }}</span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $kpis['total_collected'] > 0 ? min(100, ($method['total'] / $kpis['total_collected']) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 py-3 text-center">No payment transactions recorded.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <h3 class="text-sm font-semibold text-slate-200 mb-3">Outstanding Arrears by Form</h3>
                <div class="space-y-2">
                    @forelse($kpis['outstanding_by_form'] as $formItem)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/40 border border-slate-800/80">
                            <div>
                                <span class="text-xs font-semibold text-slate-200">{{ $formItem['form'] }}</span>
                                <span class="text-[11px] text-slate-500 ml-2">({{ $formItem['student_count'] }} students)</span>
                            </div>
                            <span class="text-xs font-bold text-red-400">${{ number_format($formItem['total_outstanding'], 2) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 py-3 text-center">No form arrears recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
