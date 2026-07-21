@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Cash Flow Statement</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $start }} to {{ $end }}</p>
        </div>
        <a href="{{ route('admin.reports.cash-flow') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
    </div>

    <!-- Operating Activities -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden mb-4">
        <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Cash Flows from Operating Activities</div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">
            <div>
                <div class="text-xs text-slate-400 mb-2">Cash Inflows</div>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span>Student Fee Collections</span>
                        <span class="text-emerald-300">{{ number_format($operatingInflows, 2) }}</span>
                    </div>
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-400 mb-2">Cash Outflows</div>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span>Operating Expenses</span>
                        <span class="text-red-300">{{ number_format($operatingOutflows, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-4 py-3 text-xs font-semibold text-slate-100 bg-slate-950/60 flex justify-between">
            <span>Net Cash from Operating Activities</span>
            <span class="{{ $netOperating >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                {{ number_format($netOperating, 2) }}
            </span>
        </div>
    </div>

    <!-- Investing Activities -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden mb-4">
        <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Cash Flows from Investing Activities</div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">
            <div>
                <div class="text-xs text-slate-400 mb-2">Cash Inflows</div>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span>Sale of Assets</span>
                        <span class="text-emerald-300">{{ number_format($investingInflows, 2) }}</span>
                    </div>
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-400 mb-2">Cash Outflows</div>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span>Purchase of Assets</span>
                        <span class="text-red-300">{{ number_format($investingOutflows, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-4 py-3 text-xs font-semibold text-slate-100 bg-slate-950/60 flex justify-between">
            <span>Net Cash from Investing Activities</span>
            <span class="{{ $netInvesting >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                {{ number_format($netInvesting, 2) }}
            </span>
        </div>
    </div>

    <!-- Financing Activities -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden mb-4">
        <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Cash Flows from Financing Activities</div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">
            <div>
                <div class="text-xs text-slate-400 mb-2">Cash Inflows</div>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span>Loans Received</span>
                        <span class="text-emerald-300">{{ number_format($financingInflows, 2) }}</span>
                    </div>
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-400 mb-2">Cash Outflows</div>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span>Loan Repayments</span>
                        <span class="text-red-300">{{ number_format($financingOutflows, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-4 py-3 text-xs font-semibold text-slate-100 bg-slate-950/60 flex justify-between">
            <span>Net Cash from Financing Activities</span>
            <span class="{{ $netFinancing >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                {{ number_format($netFinancing, 2) }}
            </span>
        </div>
    </div>

    <!-- Summary -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-slate-200 font-semibold">Net Increase/Decrease in Cash</span>
                <span class="{{ $netCashFlow >= 0 ? 'text-emerald-300' : 'text-red-300' }} font-semibold">
                    {{ number_format($netCashFlow, 2) }}
                </span>
            </div>
        </div>
    </div>
@endsection
