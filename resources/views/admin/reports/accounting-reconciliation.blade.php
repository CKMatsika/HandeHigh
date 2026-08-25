@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">General Ledger Reconciliation</h1>
            <p class="text-xs text-slate-400 mt-1">Audit verification matrix between source subsystem documents and double-entry General Ledger postings</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reports.school-revenue-summary') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3 py-1.5 rounded-lg text-xs transition">
                &larr; Revenue Summary
            </a>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                🖨️ Print Audit
            </button>
        </div>
    </div>

    <!-- Centralized Document Header (Visible in Print/PDF & Web) -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 shadow-xl">
        <x-documents.school-header 
            title="General Ledger Reconciliation Audit"
            subtitle="Source Subsystem vs Double-Entry Ledger Verification"
            reference="AUDIT-REC-{{ date('Ymd') }}"
            :date="now()"
            :status="$reconciliation['is_fully_reconciled'] ? 'Balanced' : 'Drift Detected'"
            :statusClass="$reconciliation['is_fully_reconciled'] ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'"
        />
    </div>

    <!-- Status Banner -->
    <div class="p-4 rounded-xl border {{ $reconciliation['is_fully_reconciled'] ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300' : 'bg-amber-500/10 border-amber-500/30 text-amber-300' }} flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ $reconciliation['is_fully_reconciled'] ? '✓' : '⚠️' }}</span>
            <div>
                <h3 class="text-sm font-bold">{{ $reconciliation['is_fully_reconciled'] ? 'General Ledger Is Perfectly Reconciled' : 'Reconciliation Discrepancy Detected' }}</h3>
                <p class="text-xs opacity-80">All source modules (Invoices, Receipts, Kiosk, Cashbook) match journal postings with zero unposted drift.</p>
            </div>
        </div>
        <span class="text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider {{ $reconciliation['is_fully_reconciled'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
            {{ $reconciliation['is_fully_reconciled'] ? '100% Balanced' : 'Review Required' }}
        </span>
    </div>

    <!-- 3 Domain Matrices -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Invoices & AR -->
        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-100">1. Invoicing vs AR Ledger</h4>
                <span class="text-xs px-2 py-0.5 rounded {{ $reconciliation['invoices']['is_reconciled'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }}">
                    {{ $reconciliation['invoices']['is_reconciled'] ? 'Matched' : 'Drift' }}
                </span>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">Total Invoices Billed:</span>
                    <span class="font-bold text-slate-200">${{ number_format($reconciliation['invoices']['invoices_total'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">GL 1201 AR Debits:</span>
                    <span class="font-bold text-slate-200">${{ number_format($reconciliation['invoices']['gl_ar_debits'], 2) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-800 pt-2 font-semibold">
                    <span class="text-slate-400">Discrepancy:</span>
                    <span class="{{ $reconciliation['invoices']['discrepancy'] == 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        ${{ number_format($reconciliation['invoices']['discrepancy'], 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Payments vs Settlement -->
        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-100">2. Payments vs AR Credits</h4>
                <span class="text-xs px-2 py-0.5 rounded {{ $reconciliation['payments']['is_reconciled'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }}">
                    {{ $reconciliation['payments']['is_reconciled'] ? 'Matched' : 'Drift' }}
                </span>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">Student Fee Payments:</span>
                    <span class="font-bold text-slate-200">${{ number_format($reconciliation['payments']['payments_total'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">GL 1201 AR Credits:</span>
                    <span class="font-bold text-slate-200">${{ number_format($reconciliation['payments']['gl_ar_credits'], 2) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-800 pt-2 font-semibold">
                    <span class="text-slate-400">Discrepancy:</span>
                    <span class="{{ $reconciliation['payments']['discrepancy'] == 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        ${{ number_format($reconciliation['payments']['discrepancy'], 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Kiosk vs GL -->
        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-100">3. Kiosk Sales vs GL 5803</h4>
                <span class="text-xs px-2 py-0.5 rounded {{ $reconciliation['kiosk']['is_reconciled'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }}">
                    {{ $reconciliation['kiosk']['is_reconciled'] ? 'Matched' : 'Drift' }}
                </span>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">POS Retail Total:</span>
                    <span class="font-bold text-slate-200">${{ number_format($reconciliation['kiosk']['kiosk_sales_total'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">GL 5803 Credits:</span>
                    <span class="font-bold text-slate-200">${{ number_format($reconciliation['kiosk']['gl_kiosk_credits'], 2) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-800 pt-2 font-semibold">
                    <span class="text-slate-400">Discrepancy:</span>
                    <span class="{{ $reconciliation['kiosk']['discrepancy'] == 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        ${{ number_format($reconciliation['kiosk']['discrepancy'], 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-4">
        <x-documents.school-footer />
    </div>
</div>
@endsection
