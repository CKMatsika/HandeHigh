@extends('layouts.app')

@section('content')
    @php
        $totalPaid = (float) ($invoice->allocations?->sum('amount') ?? 0);
        $branding = $invoice->school_branding;
    @endphp

    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
            table { width: 100% !important; }
            .bg-slate-900, .bg-slate-950 { background: white !important; border: 1px solid #333 !important; }
            .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-50 { color: black !important; }
            .text-slate-400, .text-slate-500 { color: #555 !important; }
            .border-slate-800, .border-slate-700 { border-color: #333 !important; }
        }
    </style>

    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Invoice Print Preview</h1>
            <p class="text-xs text-slate-400 mt-1">Official student tuition & fees billing document.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Print Invoice</button>
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-6 py-6 shadow-xl">
        <!-- Unified Document Header -->
        <x-documents.school-header 
            :school="$invoice->school"
            :snapshot="$invoice->school_snapshot"
            title="Official Student Invoice"
            :subtitle="$invoice->academic_year . ' · ' . $invoice->term"
            :reference="$invoice->number"
            :date="$invoice->issued_at"
            :status="ucfirst($invoice->status)"
            :statusClass="$invoice->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($invoice->status === 'partial' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-slate-800 text-slate-300')"
        />

        <!-- Student & Billing Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 py-3 px-4 rounded-xl bg-slate-950/50 border border-slate-800 text-xs">
            <div>
                <span class="text-slate-400 block text-[11px] uppercase tracking-wider">Student Name</span>
                <strong class="text-slate-100">{{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</strong>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] uppercase tracking-wider">Admission #</span>
                <span class="font-mono text-slate-200">{{ $invoice->student?->admission_number ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] uppercase tracking-wider">Form / Class</span>
                <span class="text-slate-200">{{ $invoice->student?->grade }} {{ $invoice->student?->class_name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] uppercase tracking-wider">Due Date</span>
                <span class="text-slate-200 font-medium">{{ $invoice->due_date?->format('d M Y') ?? 'On Receipt' }}</span>
            </div>
        </div>

        <!-- Totals Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-xs">
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3">
                <div class="text-[11px] uppercase tracking-wider text-slate-400 mb-1">Invoice Total</div>
                <div class="text-base font-bold text-slate-100 font-mono">${{ number_format($invoice->total_amount, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3">
                <div class="text-[11px] uppercase tracking-wider text-slate-400 mb-1">Amount Paid</div>
                <div class="text-base font-bold text-emerald-400 font-mono">${{ number_format($totalPaid, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3">
                <div class="text-[11px] uppercase tracking-wider text-slate-400 mb-1">Balance Due</div>
                <div class="text-base font-bold text-rose-400 font-mono">${{ number_format($invoice->balance, 2) }}</div>
            </div>
        </div>

        <!-- Itemized Table -->
        <div class="mt-6">
            <div class="text-xs uppercase tracking-wider font-semibold text-slate-400 mb-2">Itemized Fee Charges</div>
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                        <th class="text-left py-2 font-medium">Description</th>
                        <th class="text-right py-2 font-medium">Qty</th>
                        <th class="text-right py-2 font-medium">Unit Price</th>
                        <th class="text-right py-2 font-medium">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2.5 align-middle">{{ $item->description }}</td>
                            <td class="py-2.5 align-middle text-right font-mono">{{ $item->quantity }}</td>
                            <td class="py-2.5 align-middle text-right font-mono">${{ number_format($item->unit_amount, 2) }}</td>
                            <td class="py-2.5 align-middle text-right font-semibold font-mono text-slate-100">${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-700">
                        <td class="py-3" colspan="3"><span class="font-bold uppercase tracking-wider text-slate-300">Total Invoice Amount</span></td>
                        <td class="py-3 text-right font-bold font-mono text-sm text-slate-50">${{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Unified Document Footer -->
        <x-documents.school-footer 
            :school="$invoice->school"
            :snapshot="$invoice->school_snapshot"
            :showBanking="true"
        />
    </div>
@endsection
