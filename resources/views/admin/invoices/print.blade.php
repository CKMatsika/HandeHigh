@extends('layouts.app')

@section('content')
    @php
        $totalPaid = (float) ($invoice->allocations?->sum('amount') ?? 0);
    @endphp

    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
            table { width: 100% !important; }
        }
    </style>

    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Invoice print</h1>
            <p class="text-xs text-slate-400 mt-1">Use your browser print dialog to save as PDF or print.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Print</button>
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Invoice</div>
                <div class="text-lg font-semibold text-slate-50">{{ $invoice->number }}</div>
                <div class="text-xs text-slate-400 mt-1">Issued {{ $invoice->issued_at?->format('Y-m-d') }} · Due {{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $invoice->academic_year }} · {{ $invoice->term }}</div>
            </div>
            <div class="text-right text-xs text-slate-300">
                <div><span class="text-slate-400">School:</span> {{ $school->name }}</div>
                <div><span class="text-slate-400">Student:</span> {{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</div>
                <div><span class="text-slate-400">Grade/Class:</span> {{ $invoice->student?->grade }} {{ $invoice->student?->class_name }}</div>
                @if($invoice->guardian)
                    <div><span class="text-slate-400">Guardian:</span> {{ $invoice->guardian->first_name }} {{ $invoice->guardian->last_name }}</div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 text-xs">
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Totals</div>
                <div class="text-slate-200">Total: {{ number_format($invoice->total_amount, 2) }}</div>
                <div class="text-slate-200">Paid: {{ number_format($totalPaid, 2) }}</div>
                <div class="text-slate-200">Balance: {{ number_format($invoice->balance, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Status</div>
                <div class="text-slate-200 capitalize">{{ $invoice->status }}</div>
                <div class="text-slate-400 mt-1">Type: {{ $invoice->type }}</div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Notes</div>
                <div class="text-slate-200">Please pay via cash, bank, or mobile money.</div>
            </div>
        </div>

        <div class="mt-6">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Items</div>
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Description</th>
                        <th class="text-right py-2 font-medium">Qty</th>
                        <th class="text-right py-2 font-medium">Unit</th>
                        <th class="text-right py-2 font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">{{ $item->description }}</td>
                            <td class="py-2 align-middle text-right">{{ $item->quantity }}</td>
                            <td class="py-2 align-middle text-right">{{ number_format($item->unit_amount, 2) }}</td>
                            <td class="py-2 align-middle text-right font-medium">{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-slate-800">
                        <td class="py-2" colspan="3"><span class="font-semibold">Grand Total</span></td>
                        <td class="py-2 text-right font-semibold">{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-6 text-xs text-slate-400">
            Printed on {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
@endsection
