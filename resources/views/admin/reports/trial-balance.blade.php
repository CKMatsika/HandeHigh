@extends('layouts.app')

@section('content')
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
            <h1 class="text-lg font-semibold text-slate-50">Trial Balance</h1>
            <p class="text-xs text-slate-400 mt-1">General Ledger debit/credit balance verification as of {{ $asOf }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Print Report</button>
            <a href="{{ route('admin.reports.trial-balance') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Refresh</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-6 py-6 shadow-xl space-y-6">
        <x-documents.school-header 
            title="General Ledger Trial Balance"
            :subtitle="'As of ' . $asOf"
            reference="RPT-TB-{{ date('Ymd') }}"
            :date="now()"
            status="Reconciled"
            statusClass="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
        />

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-4 py-2.5 text-left font-medium">Account Code & Name</th>
                        <th class="px-4 py-2.5 text-right font-medium">Debit ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Credit ($)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($rows as $row)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-2.5 font-mono text-slate-200">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-100">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-100">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-700 bg-slate-950/60 font-bold text-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left uppercase tracking-wider">Total Balancing Sum</th>
                        <th class="px-4 py-3 text-right font-mono text-sm {{ round($totalDebit, 2) === round($totalCredit, 2) ? 'text-emerald-400' : 'text-rose-400' }}">${{ number_format($totalDebit, 2) }}</th>
                        <th class="px-4 py-3 text-right font-mono text-sm {{ round($totalDebit, 2) === round($totalCredit, 2) ? 'text-emerald-400' : 'text-rose-400' }}">${{ number_format($totalCredit, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <x-documents.school-footer />
    </div>
@endsection
