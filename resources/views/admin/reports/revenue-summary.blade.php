@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">School Revenue Summary</h1>
            <p class="text-xs text-slate-400 mt-1">Itemized revenue recognition across Tuition, Boarding, Transport, Exams, Projects, and Kiosk</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reports.accounting-reconciliation') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3 py-1.5 rounded-lg text-xs transition">
                Accounting Reconciliation &rarr;
            </a>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                🖨️ Print Report
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-4">
        <form method="GET" action="{{ route('admin.reports.school-revenue-summary') }}" class="flex flex-wrap gap-4 items-end text-xs">
            <div>
                <label class="block text-slate-300 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $summary['start_date'] }}"
                    class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs">
            </div>
            <div>
                <label class="block text-slate-300 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $summary['end_date'] }}"
                    class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs">
            </div>
            <button type="submit" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 px-4 py-1.5 rounded-lg transition">
                Update Report
            </button>
        </form>
    </div>

    <!-- High-level Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">Total Recognized Revenue</span>
            <span class="text-2xl font-bold text-emerald-400">${{ number_format($summary['total_revenue'], 2) }}</span>
            <span class="text-xs text-slate-500 block mt-1">Accrual-based GL billings</span>
        </div>

        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">Total Cash Collected</span>
            <span class="text-2xl font-bold text-blue-400">${{ number_format($summary['cash_collected']['total_cash_collected'], 2) }}</span>
            <span class="text-xs text-slate-500 block mt-1">Student fees, kiosk & receipts</span>
        </div>

        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">Accounts Receivable (GL 1201)</span>
            <span class="text-2xl font-bold text-amber-400">${{ number_format($summary['accounts_receivable_balance'], 2) }}</span>
            <span class="text-xs text-slate-500 block mt-1">General Ledger Asset balance</span>
        </div>

        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">Outstanding Invoices</span>
            <span class="text-2xl font-bold text-rose-400">${{ number_format($summary['outstanding_student_fees'], 2) }}</span>
            <span class="text-xs text-slate-500 block mt-1">Sum of unpaid invoice balances</span>
        </div>
    </div>

    <!-- Detailed Revenue Breakdown Table -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 overflow-hidden space-y-6">
        <x-documents.school-header 
            title="Institutional Revenue Summary"
            :subtitle="'Period: ' . $summary['start_date'] . ' to ' . $summary['end_date']"
            reference="REV-SUM-{{ date('Ymd') }}"
            :date="now()"
        />

        <div class="px-2 py-1 text-xs text-slate-400 border-b border-slate-800 pb-2">
            Itemized Revenue Recognition Breakdown
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-xs">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Revenue Stream</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">GL Account Code</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-300">Recognized Amount</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-300">% Contribution</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @php
                        $tot = $summary['total_revenue'] > 0 ? $summary['total_revenue'] : 1;
                        $rows = [
                            ['Tuition Fees', '5100', $summary['categories']['tuition_revenue']],
                            ['Boarding & Hostel Fees', '5200', $summary['categories']['boarding_revenue']],
                            ['Transport Fees', '5300', $summary['categories']['transport_revenue']],
                            ['Examination Fees (ZIMSEC / Cambridge)', '5500', $summary['categories']['examination_revenue']],
                            ['Levies & Other School Fees', '5400 / 5600 / 5700', $summary['categories']['levy_revenue']],
                            ['Commercial School Projects', 'Project GL / 5800', $summary['categories']['project_revenue']],
                            ['Tuckshop & Kiosk Sales', '5803', $summary['categories']['kiosk_revenue']],
                            ['Other Miscellaneous Revenue', '5800 / 5900', $summary['categories']['other_revenue']],
                        ];
                    @endphp

                    @foreach($rows as $row)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-semibold text-slate-200">{{ $row[0] }}</td>
                            <td class="px-4 py-3 font-mono text-slate-400">{{ $row[1] }}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-100">${{ number_format($row[2], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-400">{{ number_format(($row[2] / $tot) * 100, 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-800/50 font-bold text-slate-100">
                    <tr>
                        <td class="px-4 py-3" colspan="2">Total Recognized School Revenue</td>
                        <td class="px-4 py-3 text-right text-emerald-400">${{ number_format($summary['total_revenue'], 2) }}</td>
                        <td class="px-4 py-3 text-right">100.0%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <x-documents.school-footer />
    </div>
</div>
@endsection
