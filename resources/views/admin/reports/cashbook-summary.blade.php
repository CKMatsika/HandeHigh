@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.finance-dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Financial Reports</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-300">Cashbook & Bank Summary</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">Cashbook & Bank Summary</h1>
            <p class="text-xs text-slate-400 mt-0.5">Liquidity overview, bank account balances, and cashbook journal audit</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Summary
            </button>
            <a href="{{ route('admin.bank-reconciliations.index') }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-blue-600/20 text-blue-300 border border-blue-500/30 hover:bg-blue-600/30 transition">
                Bank Reconciliations &rarr;
            </a>
        </div>
    </div>

    <x-documents.school-header 
        :school="$school ?? null"
        title="Official Cashbook & Treasury Liquidity Summary"
        :subtitle="'Total Bank Balances: $' . number_format($total_bank_balance, 2) . ' · Total Receipts: $' . number_format($total_cash_receipts, 2)"
        :date="now()"
    />

    <!-- Liquidity KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <span class="text-xs font-medium text-slate-400">Total Bank Balances</span>
            <div class="text-2xl font-bold text-emerald-400 mt-1">${{ number_format($total_bank_balance, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ count($bank_accounts) }} active school bank accounts</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <span class="text-xs font-medium text-slate-400">Total Cumulative Cash Receipts</span>
            <div class="text-2xl font-bold text-slate-100 mt-1">${{ number_format($total_cash_receipts, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">Recorded direct receipts</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <span class="text-xs font-medium text-slate-400">Total Fee Payments Recorded</span>
            <div class="text-2xl font-bold text-blue-400 mt-1">${{ number_format($total_payments, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">System student collections</div>
        </div>
    </div>

    <!-- Bank Accounts Grid -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
        <h2 class="text-sm font-semibold text-slate-200 mb-4">Bank Accounts & Current Balances</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($bank_accounts as $acc)
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-200">{{ $acc->bank_name }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">{{ $acc->currency ?? 'USD' }}</span>
                    </div>
                    <div class="text-[11px] text-slate-400 font-mono mt-1">Acc: {{ $acc->account_number }}</div>
                    <div class="text-lg font-bold text-emerald-400 font-mono mt-3">${{ number_format($acc->current_balance, 2) }}</div>
                    <div class="text-[10px] text-slate-500 mt-1">Branch: {{ $acc->branch ?? 'Main' }}</div>
                </div>
            @empty
                <div class="col-span-3 text-center py-6 text-slate-500 text-xs">No bank accounts registered.</div>
            @endforelse
        </div>
    </div>

    <!-- Recent Cashbook Activity -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
        <h2 class="text-sm font-semibold text-slate-200 mb-4">Recent Cashbook Audit Entries</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="text-slate-300 bg-slate-950/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Description</th>
                        <th class="px-4 py-3 text-left font-medium">Reference</th>
                        <th class="px-4 py-3 text-right font-medium">Debit ($)</th>
                        <th class="px-4 py-3 text-right font-medium">Credit ($)</th>
                        <th class="px-4 py-3 text-right font-medium">Balance ($)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($recent_cashbook as $entry)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3 font-mono text-slate-300 whitespace-nowrap">{{ $entry->transaction_date ? $entry->transaction_date->format('Y-m-d') : 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-200">{{ $entry->description }}</td>
                            <td class="px-4 py-3 font-mono text-blue-400">{{ $entry->reference ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-400">{{ $entry->debit > 0 ? '$' . number_format($entry->debit, 2) : '-' }}</td>
                            <td class="px-4 py-3 text-right font-mono text-red-400">{{ $entry->credit > 0 ? '$' . number_format($entry->credit, 2) : '-' }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-200">${{ number_format($entry->running_balance ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No cashbook transactions recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-documents.school-footer 
        :school="$school ?? null"
        :show-banking="false"
        notice="Official treasury and cashbook summary. Generated from recorded bank accounts and general ledger receipts."
    />
</div>
@endsection
