@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Balance Sheet</h1>
            <p class="text-xs text-slate-400 mt-1">As of {{ $asOf }}</p>
        </div>
        <a href="{{ route('admin.reports.balance-sheet') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Assets</div>
            <div class="divide-y divide-slate-800">
                @foreach($assets as $row)
                    <div class="flex items-center justify-between px-4 py-2 text-xs">
                        <span class="font-mono">{{ $row->account->code }} - {{ $row->account->name }}</span>
                        <span>{{ number_format($row->balance, 2) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="px-4 py-3 text-xs font-semibold text-slate-100 bg-slate-950/60 flex justify-between">
                <span>Total Assets</span>
                <span>{{ number_format($totalAssets, 2) }}</span>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Liabilities</div>
                <div class="divide-y divide-slate-800">
                    @foreach($liabilities as $row)
                        <div class="flex items-center justify-between px-4 py-2 text-xs">
                            <span class="font-mono">{{ $row->account->code }} - {{ $row->account->name }}</span>
                            <span>{{ number_format($row->balance, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="px-4 py-3 text-xs font-semibold text-slate-100 bg-slate-950/60 flex justify-between">
                    <span>Total Liabilities</span>
                    <span>{{ number_format($totalLiabilities, 2) }}</span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Equity</div>
                <div class="divide-y divide-slate-800">
                    @foreach($equity as $row)
                        <div class="flex items-center justify-between px-4 py-2 text-xs">
                            <span class="font-mono">{{ $row->account->code }} - {{ $row->account->name }}</span>
                            <span>{{ number_format($row->balance, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="px-4 py-3 text-xs font-semibold text-slate-100 bg-slate-950/60 flex justify-between">
                    <span>Total Equity</span>
                    <span>{{ number_format($totalEquity, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 mt-4 p-4 flex justify-between text-sm">
        <span class="text-slate-200 font-semibold">Balance Check (Assets vs Liabilities + Equity)</span>
        <span class="{{ abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01 ? 'text-emerald-300' : 'text-red-300' }}">
            {{ number_format($totalAssets - ($totalLiabilities + $totalEquity), 2) }}
        </span>
    </div>
@endsection

