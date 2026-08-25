@extends('layouts.app')

@section('content')
    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Debtor & Creditor Summary</h1>
            <p class="text-xs text-slate-400 mt-1">Outstanding student invoices and vendor bills.</p>
        </div>
        <button onclick="window.print()" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Summary
        </button>
    </div>

    <x-documents.school-header 
        :school="$school ?? null"
        title="Official Debtor & Creditor Summary Statement"
        subtitle="Consolidated Receivables and Payables Overview"
        :date="now()"
    />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Debtors (Students/Guardians)</div>
            <div class="divide-y divide-slate-800">
                @forelse($debtors as $debtor)
                    <div class="flex items-center justify-between px-4 py-2 text-xs">
                        <span>Guardian #{{ $debtor->guardian_id ?? 'N/A' }}</span>
                        <span>{{ number_format($debtor->outstanding, 2) }}</span>
                    </div>
                @empty
                    <div class="px-4 py-4 text-slate-500 text-xs">No outstanding invoices.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Creditors (Vendors)</div>
            <div class="divide-y divide-slate-800">
                @forelse($creditors as $cred)
                    <div class="flex items-center justify-between px-4 py-2 text-xs">
                        <span>{{ $cred->vendor?->name ?? 'Vendor' }}</span>
                        <span>{{ number_format($cred->outstanding, 2) }}</span>
                    </div>
                @empty
                    <div class="px-4 py-4 text-slate-500 text-xs">No outstanding bills.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden mt-4">
        <div class="px-4 py-3 text-xs text-slate-300 bg-slate-950/60">Customers (non-student)</div>
        <div class="divide-y divide-slate-800">
            @forelse($customers as $cust)
                <div class="flex items-center justify-between px-4 py-2 text-xs">
                    <span>{{ $cust->name }}</span>
                    <span>{{ $cust->outstanding_balance ?? 0 }}</span>
                </div>
            @empty
                <div class="px-4 py-4 text-slate-500 text-xs">No customers found.</div>
            @endforelse
        </div>
    </div>

    <x-documents.school-footer 
        :school="$school ?? null"
        :show-banking="false"
        notice="Official consolidated balance sheet subledger debtor and creditor audit summary."
    />
@endsection

