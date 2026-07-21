@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Aged Receivables</h1>
            <p class="text-xs text-slate-400 mt-1">As of {{ $asOf }}</p>
        </div>
        <a href="{{ route('admin.reports.aged-receivables') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">Current</div>
            <div class="text-lg font-semibold text-emerald-300">{{ number_format($totalByBucket['current'], 2) }}</div>
            <div class="text-xs text-slate-500">{{ $agedBuckets['current']->count() }} invoices</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">0-30 Days</div>
            <div class="text-lg font-semibold text-yellow-300">{{ number_format($totalByBucket['0-30'], 2) }}</div>
            <div class="text-xs text-slate-500">{{ $agedBuckets['0-30']->count() }} invoices</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">31-60 Days</div>
            <div class="text-lg font-semibold text-orange-300">{{ number_format($totalByBucket['31-60'], 2) }}</div>
            <div class="text-xs text-slate-500">{{ $agedBuckets['31-60']->count() }} invoices</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">61-90 Days</div>
            <div class="text-lg font-semibold text-red-300">{{ number_format($totalByBucket['61-90'], 2) }}</div>
            <div class="text-xs text-slate-500">{{ $agedBuckets['61-90']->count() }} invoices</div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 mb-1">90+ Days</div>
            <div class="text-lg font-semibold text-red-400">{{ number_format($totalByBucket['90+'], 2) }}</div>
            <div class="text-xs text-slate-500">{{ $agedBuckets['90+']->count() }} invoices</div>
        </div>
    </div>

    <!-- Detailed Breakdown -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Invoice</th>
                        <th class="px-4 py-3 text-left font-medium">Student/Guardian</th>
                        <th class="px-4 py-3 text-left font-medium">Due Date</th>
                        <th class="px-4 py-3 text-right font-medium">Current</th>
                        <th class="px-4 py-3 text-right font-medium">0-30</th>
                        <th class="px-4 py-3 text-right font-medium">31-60</th>
                        <th class="px-4 py-3 text-right font-medium">61-90</th>
                        <th class="px-4 py-3 text-right font-medium">90+</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($agedBuckets['current'] as $invoice)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $invoice->number }}</td>
                            <td class="px-4 py-3">{{ $invoice->student?->full_name ?? $invoice->guardian?->full_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $invoice->due_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right text-emerald-300">{{ number_format($invoice->balance, 2) }}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($invoice->balance, 2) }}</td>
                        </tr>
                    @endforeach
                    
                    @foreach($agedBuckets['0-30'] as $invoice)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $invoice->number }}</td>
                            <td class="px-4 py-3">{{ $invoice->student?->full_name ?? $invoice->guardian?->full_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $invoice->due_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right text-yellow-300">{{ number_format($invoice->balance, 2) }}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($invoice->balance, 2) }}</td>
                        </tr>
                    @endforeach
                    
                    @foreach($agedBuckets['31-60'] as $invoice)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $invoice->number }}</td>
                            <td class="px-4 py-3">{{ $invoice->student?->full_name ?? $invoice->guardian?->full_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $invoice->due_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right text-orange-300">{{ number_format($invoice->balance, 2) }}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($invoice->balance, 2) }}</td>
                        </tr>
                    @endforeach
                    
                    @foreach($agedBuckets['61-90'] as $invoice)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $invoice->number }}</td>
                            <td class="px-4 py-3">{{ $invoice->student?->full_name ?? $invoice->guardian?->full_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $invoice->due_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right text-red-300">{{ number_format($invoice->balance, 2) }}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($invoice->balance, 2) }}</td>
                        </tr>
                    @endforeach
                    
                    @foreach($agedBuckets['90+'] as $invoice)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $invoice->number }}</td>
                            <td class="px-4 py-3">{{ $invoice->student?->full_name ?? $invoice->guardian?->full_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $invoice->due_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right text-red-400">{{ number_format($invoice->balance, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($invoice->balance, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-950/60 text-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Totals</th>
                        <th class="px-4 py-3 text-left">{{ $agedBuckets['current']->count() + $agedBuckets['0-30']->count() + $agedBuckets['31-60']->count() + $agedBuckets['61-90']->count() + $agedBuckets['90+']->count() }} Invoices</th>
                        <th class="px-4 py-3"></th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalByBucket['current'], 2) }}</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalByBucket['0-30'], 2) }}</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalByBucket['31-60'], 2) }}</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalByBucket['61-90'], 2) }}</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalByBucket['90+'], 2) }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ number_format($grandTotal, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
