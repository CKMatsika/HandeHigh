@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Trial Balance</h1>
            <p class="text-xs text-slate-400 mt-1">As of {{ $asOf }}</p>
        </div>
        <a href="{{ route('admin.reports.trial-balance') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Account</th>
                        <th class="px-4 py-3 text-right font-medium">Debit</th>
                        <th class="px-4 py-3 text-right font-medium">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($rows as $row)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($row['debit'], 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($row['credit'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-950/60 text-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Totals</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalDebit, 2) }}</th>
                        <th class="px-4 py-3 text-right">{{ number_format($totalCredit, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

