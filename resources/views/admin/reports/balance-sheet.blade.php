@extends('layouts.app')

@section('content')
    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
            .bg-slate-900, .bg-slate-950 { background: white !important; border: 1px solid #333 !important; }
            .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-50 { color: black !important; }
            .text-slate-400, .text-slate-500 { color: #555 !important; }
            .border-slate-800, .border-slate-700 { border-color: #333 !important; }
        }
    </style>

    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Statement of Financial Position (Balance Sheet)</h1>
            <p class="text-xs text-slate-400 mt-1">Asset, Liability, and Equity position as of {{ $asOf }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Print Balance Sheet</button>
            <a href="{{ route('admin.reports.balance-sheet') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Refresh</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-6 py-6 shadow-xl space-y-6">
        <x-documents.school-header 
            title="Statement of Financial Position (Balance Sheet)"
            :subtitle="'As of ' . $asOf"
            reference="RPT-BS-{{ date('Ymd') }}"
            :date="now()"
        />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Assets -->
            <div class="rounded-xl border border-slate-800 bg-slate-950/50 overflow-hidden">
                <div class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-emerald-400 bg-slate-950/80 border-b border-slate-800">
                    Total Assets
                </div>
                <div class="divide-y divide-slate-800/70 text-xs">
                    @forelse($assets as $row)
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <span class="font-mono text-slate-300">{{ $row->account->code }} - {{ $row->account->name }}</span>
                            <span class="font-mono font-medium text-slate-100">${{ number_format($row->balance, 2) }}</span>
                        </div>
                    @empty
                        <div class="px-4 py-4 text-center text-slate-500 text-xs">No asset accounts recorded.</div>
                    @endforelse
                </div>
                <div class="px-4 py-3 text-xs font-bold text-slate-100 bg-slate-950 border-t border-slate-800 flex justify-between">
                    <span class="uppercase tracking-wider text-slate-300">Total Assets</span>
                    <span class="font-mono text-emerald-400">${{ number_format($totalAssets, 2) }}</span>
                </div>
            </div>

            <!-- Liabilities & Equity -->
            <div class="space-y-6">
                <div class="rounded-xl border border-slate-800 bg-slate-950/50 overflow-hidden">
                    <div class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-amber-400 bg-slate-950/80 border-b border-slate-800">
                        Liabilities
                    </div>
                    <div class="divide-y divide-slate-800/70 text-xs">
                        @forelse($liabilities as $row)
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="font-mono text-slate-300">{{ $row->account->code }} - {{ $row->account->name }}</span>
                                <span class="font-mono font-medium text-slate-100">${{ number_format($row->balance, 2) }}</span>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-center text-slate-500 text-xs">No liability accounts recorded.</div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 text-xs font-bold text-slate-100 bg-slate-950 border-t border-slate-800 flex justify-between">
                        <span class="uppercase tracking-wider text-slate-300">Total Liabilities</span>
                        <span class="font-mono text-amber-400">${{ number_format($totalLiabilities, 2) }}</span>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/50 overflow-hidden">
                    <div class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-blue-400 bg-slate-950/80 border-b border-slate-800">
                        Equity & Reserves
                    </div>
                    <div class="divide-y divide-slate-800/70 text-xs">
                        @forelse($equity as $row)
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <span class="font-mono text-slate-300">{{ $row->account->code }} - {{ $row->account->name }}</span>
                                <span class="font-mono font-medium text-slate-100">${{ number_format($row->balance, 2) }}</span>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-center text-slate-500 text-xs">No equity accounts recorded.</div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 text-xs font-bold text-slate-100 bg-slate-950 border-t border-slate-800 flex justify-between">
                        <span class="uppercase tracking-wider text-slate-300">Total Equity</span>
                        <span class="font-mono text-blue-400">${{ number_format($totalEquity, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-700 bg-slate-950 px-6 py-4 flex items-center justify-between">
            <span class="text-sm font-bold uppercase tracking-wider text-slate-200">Accounting Equation Balance Verification</span>
            <span class="text-xs font-mono font-bold px-3 py-1 rounded-full {{ abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }}">
                {{ abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01 ? '✓ Perfectly Balanced (Variance: $0.00)' : 'Variance: $' . number_format($totalAssets - ($totalLiabilities + $totalEquity), 2) }}
            </span>
        </div>

        <x-documents.school-footer />
    </div>
@endsection
