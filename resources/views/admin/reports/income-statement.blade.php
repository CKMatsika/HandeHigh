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
            .shadow-xl, .shadow-lg { box-shadow: none !important; }
        }
    </style>

    <div class="space-y-6">
        <!-- Page Title & Top Actions (Screen only) -->
        <div class="no-print flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-50 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        📈
                    </span>
                    Income &amp; Expenditure Statement
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Financial performance statement (Profit &amp; Loss) covering <strong class="text-slate-200">{{ $start }}</strong> to <strong class="text-slate-200">{{ $end }}</strong>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.reports.income-expenditure.export', ['start_date' => $start, 'end_date' => $end]) }}" 
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-700 hover:text-white transition shadow-sm">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </a>
                <button onclick="window.print()" 
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white px-3.5 py-1.5 text-xs font-medium transition shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Statement
                </button>
                <a href="{{ request()->url() }}" 
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-800 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset
                </a>
            </div>
        </div>

        <!-- Filter Bar with Presets (Screen only) -->
        <div class="no-print bg-slate-900/90 rounded-2xl border border-slate-800 p-4 shadow-sm">
            <form id="filterForm" method="GET" action="{{ request()->url() }}" class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 text-xs">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="block text-slate-400 font-medium mb-1">Start Date</label>
                        <input type="date" id="input_start_date" name="start_date" value="{{ $start }}"
                               class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-950 text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-slate-400 font-medium mb-1">End Date</label>
                        <input type="date" id="input_end_date" name="end_date" value="{{ $end }}"
                               class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-950 text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <button type="submit" 
                                class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-1.5 rounded-lg transition shadow-sm">
                            Apply Filter
                        </button>
                    </div>
                </div>

                <!-- Quick Presets -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-slate-500 text-[11px] mr-1">Presets:</span>
                    <button type="button" onclick="setPreset('month')" 
                            class="px-2.5 py-1 rounded border border-slate-800 bg-slate-800/60 hover:bg-slate-700 text-slate-300 text-[11px] transition">
                        This Month
                    </button>
                    <button type="button" onclick="setPreset('quarter')" 
                            class="px-2.5 py-1 rounded border border-slate-800 bg-slate-800/60 hover:bg-slate-700 text-slate-300 text-[11px] transition">
                        This Quarter
                    </button>
                    <button type="button" onclick="setPreset('ytd')" 
                            class="px-2.5 py-1 rounded border border-slate-800 bg-slate-800/60 hover:bg-slate-700 text-slate-300 text-[11px] transition">
                        Year to Date
                    </button>
                    <button type="button" onclick="setPreset('all')" 
                            class="px-2.5 py-1 rounded border border-slate-800 bg-slate-800/60 hover:bg-slate-700 text-slate-300 text-[11px] transition">
                        All Time
                    </button>
                </div>
            </form>
        </div>

        <!-- High-level Metric Cards (Screen only) -->
        <div class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Revenue -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                    <span class="uppercase tracking-wider font-semibold">Total Revenue (Income)</span>
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                </div>
                <div class="text-2xl font-bold font-mono text-emerald-400">
                    ${{ number_format($totalRevenue, 2) }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    {{ count($revenueAccounts) }} active revenue {{ count($revenueAccounts) === 1 ? 'account' : 'accounts' }}
                </div>
            </div>

            <!-- Total Expenses -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                    <span class="uppercase tracking-wider font-semibold">Total Expenditure</span>
                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                </div>
                <div class="text-2xl font-bold font-mono text-rose-400">
                    ${{ number_format($totalExpenses, 2) }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    {{ count($expenseAccounts) }} active expense {{ count($expenseAccounts) === 1 ? 'account' : 'accounts' }}
                </div>
            </div>

            <!-- Net Operating Position -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                    <span class="uppercase tracking-wider font-semibold">Net Operating Position</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $netIncome >= 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                        {{ $netIncome >= 0 ? 'Surplus' : 'Deficit' }}
                    </span>
                </div>
                <div class="text-2xl font-bold font-mono {{ $netIncome >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    ${{ number_format($netIncome, 2) }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Operating profit / loss for period
                </div>
            </div>

            <!-- Operating Margin -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                    <span class="uppercase tracking-wider font-semibold">Operating Margin</span>
                    <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                </div>
                <div class="text-2xl font-bold font-mono text-slate-100">
                    {{ $totalRevenue > 0 ? number_format(($netIncome / $totalRevenue) * 100, 1) : 0 }}%
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Expense ratio: {{ $totalRevenue > 0 ? number_format(($totalExpenses / $totalRevenue) * 100, 1) : 0 }}%
                </div>
            </div>
        </div>

        <!-- Official Report Document Container -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-6 py-6 shadow-xl space-y-6">
            <x-documents.school-header 
                title="Income & Expenditure Statement"
                :subtitle="'Statement of Financial Performance from ' . $start . ' to ' . $end"
                reference="RPT-IE-{{ date('Ymd') }}"
                :date="now()"
            />

            <!-- Two-Column Financial Ledger Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- ================= Operating Revenue ================= -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/50 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-emerald-400 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
                            <span>Operating Revenue (Income)</span>
                            <span class="text-[11px] font-mono font-normal text-slate-400">${{ number_format($totalRevenue, 2) }}</span>
                        </div>

                        @if(!empty($incomeByCategory) && count($incomeByCategory) > 0)
                            <div class="divide-y divide-slate-800/70 text-xs">
                                @foreach($incomeByCategory as $catGroup)
                                    @php
                                        $catName = is_array($catGroup) ? ($catGroup['category'] ?? 'Operating Revenue') : ($catGroup->category ?? 'Operating Revenue');
                                        $catTotal = is_array($catGroup) ? ($catGroup['total'] ?? 0) : ($catGroup->total ?? 0);
                                        $catAccounts = is_array($catGroup) ? ($catGroup['accounts'] ?? []) : ($catGroup->accounts ?? []);
                                    @endphp
                                    <div class="bg-slate-900/40">
                                        <!-- Category Subheader -->
                                        <div class="px-4 py-2 bg-slate-900/60 border-b border-slate-800/50 flex items-center justify-between text-[11px]">
                                            <span class="font-semibold text-slate-300 uppercase tracking-wide">
                                                {{ ucwords(str_replace('_', ' ', (string)$catName)) }}
                                            </span>
                                            <span class="font-mono font-medium text-emerald-400/90">${{ number_format($catTotal, 2) }}</span>
                                        </div>
                                        <!-- Category Accounts -->
                                        <div class="divide-y divide-slate-800/40">
                                            @foreach($catAccounts as $row)
                                                @php
                                                    $code = is_array($row) ? ($row['code'] ?? ($row['account']->code ?? '')) : ($row->account->code ?? $row->code ?? '');
                                                    $name = is_array($row) ? ($row['name'] ?? ($row['account']->name ?? '')) : ($row->account->name ?? $row->name ?? '');
                                                    $amount = is_array($row) ? ($row['amount'] ?? $row['balance'] ?? 0) : ($row->balance ?? $row->amount ?? 0);
                                                @endphp
                                                <div class="flex items-center justify-between px-4 py-2 text-xs hover:bg-slate-800/30 transition">
                                                    <span class="font-mono text-slate-300">{{ $code }} - {{ $name }}</span>
                                                    <span class="font-mono font-medium text-slate-100">${{ number_format($amount, 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="divide-y divide-slate-800/70 text-xs">
                                @forelse($revenueAccounts as $row)
                                    @php
                                        $code = is_array($row) ? ($row['code'] ?? ($row['account']->code ?? '')) : ($row->account->code ?? $row->code ?? '');
                                        $name = is_array($row) ? ($row['name'] ?? ($row['account']->name ?? '')) : ($row->account->name ?? $row->name ?? '');
                                        $amount = is_array($row) ? ($row['amount'] ?? $row['balance'] ?? 0) : ($row->balance ?? $row->amount ?? 0);
                                    @endphp
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="font-mono text-slate-300">{{ $code }} - {{ $name }}</span>
                                        <span class="font-mono font-medium text-slate-100">${{ number_format($amount, 2) }}</span>
                                    </div>
                                @empty
                                    <div class="px-4 py-8 text-center text-slate-500 text-xs">
                                        No operating revenue recorded for this period.
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    <div class="px-4 py-3 text-xs font-bold text-slate-100 bg-slate-950 border-t border-slate-800 flex justify-between">
                        <span class="uppercase tracking-wider text-slate-300">Total Operating Revenue</span>
                        <span class="font-mono text-emerald-400 text-sm">${{ number_format($totalRevenue, 2) }}</span>
                    </div>
                </div>

                <!-- ================= Operating Expenditure ================= -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/50 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-rose-400 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
                            <span>Operating Expenditure</span>
                            <span class="text-[11px] font-mono font-normal text-slate-400">${{ number_format($totalExpenses, 2) }}</span>
                        </div>

                        @if(!empty($expenditureByCategory) && count($expenditureByCategory) > 0)
                            <div class="divide-y divide-slate-800/70 text-xs">
                                @foreach($expenditureByCategory as $catGroup)
                                    @php
                                        $catName = is_array($catGroup) ? ($catGroup['category'] ?? 'Operating Expense') : ($catGroup->category ?? 'Operating Expense');
                                        $catTotal = is_array($catGroup) ? ($catGroup['total'] ?? 0) : ($catGroup->total ?? 0);
                                        $catAccounts = is_array($catGroup) ? ($catGroup['accounts'] ?? []) : ($catGroup->accounts ?? []);
                                    @endphp
                                    <div class="bg-slate-900/40">
                                        <!-- Category Subheader -->
                                        <div class="px-4 py-2 bg-slate-900/60 border-b border-slate-800/50 flex items-center justify-between text-[11px]">
                                            <span class="font-semibold text-slate-300 uppercase tracking-wide">
                                                {{ ucwords(str_replace('_', ' ', (string)$catName)) }}
                                            </span>
                                            <span class="font-mono font-medium text-rose-400/90">${{ number_format($catTotal, 2) }}</span>
                                        </div>
                                        <!-- Category Accounts -->
                                        <div class="divide-y divide-slate-800/40">
                                            @foreach($catAccounts as $row)
                                                @php
                                                    $code = is_array($row) ? ($row['code'] ?? ($row['account']->code ?? '')) : ($row->account->code ?? $row->code ?? '');
                                                    $name = is_array($row) ? ($row['name'] ?? ($row['account']->name ?? '')) : ($row->account->name ?? $row->name ?? '');
                                                    $amount = is_array($row) ? ($row['amount'] ?? $row['balance'] ?? 0) : ($row->balance ?? $row->amount ?? 0);
                                                @endphp
                                                <div class="flex items-center justify-between px-4 py-2 text-xs hover:bg-slate-800/30 transition">
                                                    <span class="font-mono text-slate-300">{{ $code }} - {{ $name }}</span>
                                                    <span class="font-mono font-medium text-slate-100">${{ number_format($amount, 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="divide-y divide-slate-800/70 text-xs">
                                @forelse($expenseAccounts as $row)
                                    @php
                                        $code = is_array($row) ? ($row['code'] ?? ($row['account']->code ?? '')) : ($row->account->code ?? $row->code ?? '');
                                        $name = is_array($row) ? ($row['name'] ?? ($row['account']->name ?? '')) : ($row->account->name ?? $row->name ?? '');
                                        $amount = is_array($row) ? ($row['amount'] ?? $row['balance'] ?? 0) : ($row->balance ?? $row->amount ?? 0);
                                    @endphp
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="font-mono text-slate-300">{{ $code }} - {{ $name }}</span>
                                        <span class="font-mono font-medium text-slate-100">${{ number_format($amount, 2) }}</span>
                                    </div>
                                @empty
                                    <div class="px-4 py-8 text-center text-slate-500 text-xs">
                                        No operating expenses recorded for this period.
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    <div class="px-4 py-3 text-xs font-bold text-slate-100 bg-slate-950 border-t border-slate-800 flex justify-between">
                        <span class="uppercase tracking-wider text-slate-300">Total Operating Expenses</span>
                        <span class="font-mono text-rose-400 text-sm">${{ number_format($totalExpenses, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Net Operating Surplus / Deficit Summary Banner -->
            <div class="rounded-xl border border-slate-700 bg-slate-950 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">
                        Accounting Summary
                    </span>
                    <span class="text-sm font-semibold text-slate-200">
                        Net Operating Surplus / (Deficit)
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-lg font-bold font-mono {{ $netIncome >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                        ${{ number_format($netIncome, 2) }}
                    </span>
                    <span class="text-xs text-slate-400 block font-mono">
                        (Revenue ${{ number_format($totalRevenue, 2) }} - Expenses ${{ number_format($totalExpenses, 2) }})
                    </span>
                </div>
            </div>

            <x-documents.school-footer :school="$school ?? null" />
        </div>
    </div>

    <script>
        function setPreset(type) {
            const startInput = document.getElementById('input_start_date');
            const endInput = document.getElementById('input_end_date');
            const now = new Date();
            
            let start, end;
            const formatDate = (d) => d.toISOString().split('T')[0];

            if (type === 'month') {
                start = new Date(now.getFullYear(), now.getMonth(), 1);
                end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            } else if (type === 'quarter') {
                const currentQuarter = Math.floor(now.getMonth() / 3);
                start = new Date(now.getFullYear(), currentQuarter * 3, 1);
                end = new Date(now.getFullYear(), (currentQuarter + 1) * 3, 0);
            } else if (type === 'ytd') {
                start = new Date(now.getFullYear(), 0, 1);
                end = now;
            } else if (type === 'all') {
                start = new Date(2020, 0, 1);
                end = now;
            }

            if (start && end) {
                startInput.value = formatDate(start);
                endInput.value = formatDate(end);
                document.getElementById('filterForm').submit();
            }
        }
    </script>
@endsection
