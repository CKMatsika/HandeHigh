@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Student statement</h1>
            <p class="text-xs text-slate-400 mt-1">Invoices, payments, and running balance for {{ $student->first_name }} {{ $student->last_name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button onclick="window.print()" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">Print</button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-200 mb-4">
            <div><span class="text-slate-400">Student:</span> {{ $student->first_name }} {{ $student->last_name }}</div>
            <div><span class="text-slate-400">Grade/Class:</span> {{ $student->grade }} {{ $student->class_name }}</div>
            <div><span class="text-slate-400">School:</span> {{ $school->name }}</div>
            <div><span class="text-slate-400">Academic Year:</span> {{ $academicYear }}</div>
            <div><span class="text-slate-400">Term:</span> {{ $term ?? 'All terms' }}</div>
            <div><span class="text-slate-400">Opening Balance:</span> {{ number_format($openingBalance, 2) }}</div>
            <div><span class="text-slate-400">Closing Balance:</span> {{ number_format($closingBalance, 2) }}</div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Reference</th>
                        <th class="px-4 py-3 text-left font-medium">Description</th>
                        <th class="px-4 py-3 text-right font-medium">Debit</th>
                        <th class="px-4 py-3 text-right font-medium">Credit</th>
                        <th class="px-4 py-3 text-right font-medium">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @if($openingBalance != 0)
                        <tr class="bg-slate-800/50 font-medium">
                            <td colspan="3" class="px-4 py-3">Opening Balance (Brought Forward)</td>
                            <td class="px-4 py-3 text-right">{{ number_format($openingBalance, 2) }}</td>
                            <td class="px-4 py-3 text-right"></td>
                            <td class="px-4 py-3 text-right">{{ number_format($openingBalance, 2) }}</td>
                        </tr>
                    @endif
                    @forelse($events as $event)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $event['date']->format('M j, Y') }}</td>
                            <td class="px-4 py-3">{{ $event['reference'] }}</td>
                            <td class="px-4 py-3">{{ $event['description'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $event['debit'] ? number_format($event['debit'], 2) : '' }}</td>
                            <td class="px-4 py-3 text-right">{{ $event['credit'] ? number_format($event['credit'], 2) : '' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($event['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No activity found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style media="print">
        .sidebar, header, nav, button:not(.print-btn) {
            display: none !important;
        }
        body {
            background: white;
            color: black;
        }
        .bg-slate-900, .bg-slate-800, .bg-slate-950 {
            background: white !important;
            border: 1px solid #333 !important;
        }
        .text-slate-50, .text-slate-200, .text-slate-300 {
            color: black !important;
        }
        .text-slate-400 {
            color: #666 !important;
        }
        .divide-slate-800 > * + * {
            border-top-color: #333 !important;
        }
        .hover\:bg-slate-800\/40:hover {
            background: transparent !important;
        }
        .rounded-2xl {
            border-radius: 0 !important;
        }
        .rounded-full {
            border-radius: 4px !important;
        }
        .border-slate-700, .border-slate-800 {
            border-color: #333 !important;
        }
        .bg-emerald-500 {
            background: #333 !important;
            color: white !important;
        }
    </style>
@endsection
