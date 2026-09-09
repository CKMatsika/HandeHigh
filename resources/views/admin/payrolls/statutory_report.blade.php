@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header with Tabs and Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.payrolls.show', $payroll) }}" class="text-slate-400 hover:text-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-50 tracking-tight">Statutory Compliance Returns</h1>
            </div>
            <p class="text-sm text-slate-400 mt-1">
                Period: <span class="text-slate-200 font-semibold">{{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}</span> | Status: <span class="uppercase font-semibold text-emerald-400">{{ $payroll->status }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-sm font-medium transition flex items-center gap-2 border border-slate-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Schedule
            </button>
            <div class="relative inline-block text-left" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold transition flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download CSV
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-56 bg-slate-800 rounded-xl shadow-xl border border-slate-700 py-2 z-50 text-xs">
                    <a href="{{ route('admin.payrolls.export-statutory', [$payroll, 'tarms']) }}" class="block px-4 py-2.5 text-slate-200 hover:bg-slate-700">ZIMRA TaRMS Return CSV</a>
                    <a href="{{ route('admin.payrolls.export-statutory', [$payroll, 'nssa']) }}" class="block px-4 py-2.5 text-slate-200 hover:bg-slate-700">NSSA P4 Return Schedule CSV</a>
                    <a href="{{ route('admin.payrolls.export-statutory', [$payroll, 'nec']) }}" class="block px-4 py-2.5 text-slate-200 hover:bg-slate-700">NEC Council Contribution CSV</a>
                    <a href="{{ route('admin.payrolls.export-statutory', [$payroll, 'summary']) }}" class="block px-4 py-2.5 text-slate-200 hover:bg-slate-700">Master Payroll Summary CSV</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-800 space-x-4">
        <a href="{{ route('admin.payrolls.statutory-report', [$payroll, 'type' => 'zimra-p2']) }}"
           class="py-3 px-4 text-sm font-medium border-b-2 transition {{ $reportType === 'zimra-p2' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            ZIMRA P2 / TaRMS Return
        </a>
        <a href="{{ route('admin.payrolls.statutory-report', [$payroll, 'type' => 'nssa-p4']) }}"
           class="py-3 px-4 text-sm font-medium border-b-2 transition {{ $reportType === 'nssa-p4' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            NSSA P4 Monthly Schedule
        </a>
        <a href="{{ route('admin.payrolls.statutory-report', [$payroll, 'type' => 'nec']) }}"
           class="py-3 px-4 text-sm font-medium border-b-2 transition {{ $reportType === 'nec' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            NEC Contribution Schedule
        </a>
        <a href="{{ route('admin.payrolls.statutory-report', [$payroll, 'type' => 'master-summary']) }}"
           class="py-3 px-4 text-sm font-medium border-b-2 transition {{ $reportType === 'master-summary' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            Master Payroll Summary
        </a>
    </div>

    <!-- Report Table Container -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        @if($reportType === 'zimra-p2')
            <!-- ZIMRA P2 / TaRMS Return -->
            <div class="p-6 border-b border-slate-800 bg-slate-800/40 flex justify-between items-center">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">ZIMRA P2 Monthly PAYE & AIDS Levy Remittance</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Complies with Section 73 of the Income Tax Act & ZIMRA TaRMS multi-currency filing requirements.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">Total Tax Remittance:</span>
                    <div class="text-lg font-bold font-mono text-emerald-400">
                        ${{ number_format($payroll->total_paye_usd + $payroll->total_aids_levy_usd, 2) }} USD
                        @if(($payroll->total_paye_zwg + $payroll->total_aids_levy_zwg) > 0)
                        | {{ number_format($payroll->total_paye_zwg + $payroll->total_aids_levy_zwg, 2) }} ZWG
                        @endif
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800/80 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-700">
                        <tr>
                            <th class="py-3 px-4">TIN / Nat ID</th>
                            <th class="py-3 px-4">Employee</th>
                            <th class="py-3 px-4 text-right">Gross USD</th>
                            <th class="py-3 px-4 text-right">Gross PAYE USD</th>
                            <th class="py-3 px-4 text-right">Med Credit (50%)</th>
                            <th class="py-3 px-4 text-right">Net PAYE USD</th>
                            <th class="py-3 px-4 text-right">AIDS Levy (3%)</th>
                            <th class="py-3 px-4 text-right">Total Tax (USD)</th>
                            <th class="py-3 px-4 text-right">Gross ZWG</th>
                            <th class="py-3 px-4 text-right">Net PAYE ZWG</th>
                            <th class="py-3 px-4 text-right">AIDS Levy ZWG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach($payroll->items as $item)
                        @php $emp = $item->employee; @endphp
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 font-mono text-slate-400">
                                <div>{{ $emp?->zimra_tin ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-500">{{ $emp?->national_id ?? '' }}</div>
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-200">
                                {{ $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($item->gross_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($item->paye_usd + $item->medical_aid_tax_credit_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-400">${{ number_format($item->medical_aid_tax_credit_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-semibold text-slate-100">${{ number_format($item->paye_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->aids_levy_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">${{ number_format($item->paye_usd + $item->aids_levy_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($item->gross_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-semibold">{{ number_format($item->paye_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">{{ number_format($item->aids_levy_zwg, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-800 font-bold text-slate-100 border-t border-slate-700">
                        <tr>
                            <td colspan="2" class="py-3 px-4">TOTALS</td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($payroll->total_gross_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($payroll->total_paye_usd + $payroll->items->sum('medical_aid_tax_credit_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-400">${{ number_format($payroll->items->sum('medical_aid_tax_credit_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($payroll->total_paye_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->total_aids_levy_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-400">${{ number_format($payroll->total_paye_usd + $payroll->total_aids_levy_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($payroll->total_gross_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($payroll->total_paye_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">{{ number_format($payroll->total_aids_levy_zwg, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        @elseif($reportType === 'nssa-p4')
            <!-- NSSA P4 Schedule -->
            <div class="p-6 border-b border-slate-800 bg-slate-800/40 flex justify-between items-center">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">NSSA Social Security Scheme (P4 Return Schedule)</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Statutory 4.5% Employee + 4.5% Employer Contribution capped at $700.00 USD / ZWG 19,600.00.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">Total NSSA Remittance (Emp + Empr):</span>
                    <div class="text-lg font-bold font-mono text-emerald-400">
                        ${{ number_format($payroll->items->sum('nssa_employee_usd') + $payroll->total_employer_nssa_usd, 2) }} USD
                        @if(($payroll->items->sum('nssa_employee_zwg') + $payroll->total_employer_nssa_zwg) > 0)
                        | {{ number_format($payroll->items->sum('nssa_employee_zwg') + $payroll->total_employer_nssa_zwg, 2) }} ZWG
                        @endif
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800/80 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-700">
                        <tr>
                            <th class="py-3 px-4">NSSA # / Nat ID</th>
                            <th class="py-3 px-4">Employee</th>
                            <th class="py-3 px-4 text-right">Basic Salary USD</th>
                            <th class="py-3 px-4 text-right">Insurable USD</th>
                            <th class="py-3 px-4 text-right">Employee 4.5%</th>
                            <th class="py-3 px-4 text-right">Employer 4.5%</th>
                            <th class="py-3 px-4 text-right">Total NSSA USD</th>
                            <th class="py-3 px-4 text-right">Basic ZWG</th>
                            <th class="py-3 px-4 text-right">Employee 4.5%</th>
                            <th class="py-3 px-4 text-right">Employer 4.5%</th>
                            <th class="py-3 px-4 text-right">Total NSSA ZWG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach($payroll->items as $item)
                        @php $emp = $item->employee; @endphp
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 font-mono text-slate-400">
                                <div>{{ $emp?->nssa_number ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-500">{{ $emp?->national_id ?? '' }}</div>
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-200">
                                {{ $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($item->basic_salary_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-400">${{ number_format(min($item->basic_salary_usd, 700.00), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->nssa_employee_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-400">${{ number_format($item->nssa_employer_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">${{ number_format($item->nssa_employee_usd + $item->nssa_employer_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($item->basic_salary_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">{{ number_format($item->nssa_employee_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-400">{{ number_format($item->nssa_employer_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">{{ number_format($item->nssa_employee_zwg + $item->nssa_employer_zwg, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-800 font-bold text-slate-100 border-t border-slate-700">
                        <tr>
                            <td colspan="2" class="py-3 px-4">TOTALS</td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($payroll->items->sum('basic_salary_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">-</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->items->sum('nssa_employee_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-400">${{ number_format($payroll->total_employer_nssa_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-400">${{ number_format($payroll->items->sum('nssa_employee_usd') + $payroll->total_employer_nssa_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($payroll->items->sum('basic_salary_zwg'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">{{ number_format($payroll->items->sum('nssa_employee_zwg'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-400">{{ number_format($payroll->total_employer_nssa_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-400">{{ number_format($payroll->items->sum('nssa_employee_zwg') + $payroll->total_employer_nssa_zwg, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        @elseif($reportType === 'nec')
            <!-- NEC Schedule -->
            <div class="p-6 border-b border-slate-800 bg-slate-800/40 flex justify-between items-center">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">National Employment Council (NEC) Monthly Contribution Schedule</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Calculated based on Sector Code Agreements (e.g. Educational Services, Commercial).</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">Total NEC Remittance:</span>
                    <div class="text-lg font-bold font-mono text-emerald-400">
                        ${{ number_format($payroll->items->sum('nec_employee_usd') + $payroll->total_employer_nec_usd, 2) }} USD
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800/80 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-700">
                        <tr>
                            <th class="py-3 px-4">Employee</th>
                            <th class="py-3 px-4">Sector Code</th>
                            <th class="py-3 px-4 text-right">Basic Salary USD</th>
                            <th class="py-3 px-4 text-right">Employee Portion</th>
                            <th class="py-3 px-4 text-right">Employer Portion</th>
                            <th class="py-3 px-4 text-right">Total NEC (USD)</th>
                            <th class="py-3 px-4 text-right">Basic ZWG</th>
                            <th class="py-3 px-4 text-right">Employee ZWG</th>
                            <th class="py-3 px-4 text-right">Employer ZWG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach($payroll->items as $item)
                        @php $emp = $item->employee; @endphp
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 font-medium text-slate-200">
                                <div>{{ $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A' }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $emp?->national_id ?? '' }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-400">{{ $emp?->nec_sector_code ?? 'NEC-EDU' }}</td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($item->basic_salary_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->nec_employee_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-400">${{ number_format($item->nec_employer_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">${{ number_format($item->nec_employee_usd + $item->nec_employer_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($item->basic_salary_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">{{ number_format($item->nec_employee_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-400">{{ number_format($item->nec_employer_zwg, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-800 font-bold text-slate-100 border-t border-slate-700">
                        <tr>
                            <td colspan="2" class="py-3 px-4">TOTALS</td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($payroll->items->sum('basic_salary_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->items->sum('nec_employee_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-400">${{ number_format($payroll->total_employer_nec_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-400">${{ number_format($payroll->items->sum('nec_employee_usd') + $payroll->total_employer_nec_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($payroll->items->sum('basic_salary_zwg'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">{{ number_format($payroll->items->sum('nec_employee_zwg'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-blue-400">{{ number_format($payroll->total_employer_nec_zwg, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        @elseif($reportType === 'master-summary')
            <!-- Consolidated Master Summary -->
            <div class="p-6 border-b border-slate-800 bg-slate-800/40 flex justify-between items-center">
                <div>
                    <h3 class="text-base font-semibold text-slate-100">Consolidated Master Payroll Summary Sheet</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Complete multi-currency earnings, all statutory deductions, net payouts, and leave balance.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800/80 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-700">
                        <tr>
                            <th class="py-3 px-4">Employee</th>
                            <th class="py-3 px-4 text-right">Gross (USD)</th>
                            <th class="py-3 px-4 text-right">PAYE</th>
                            <th class="py-3 px-4 text-right">AIDS Levy</th>
                            <th class="py-3 px-4 text-right">NSSA</th>
                            <th class="py-3 px-4 text-right">NEC</th>
                            <th class="py-3 px-4 text-right">Union</th>
                            <th class="py-3 px-4 text-right">Med Aid</th>
                            <th class="py-3 px-4 text-right">Loans</th>
                            <th class="py-3 px-4 text-right font-bold text-slate-100">Net Pay (USD)</th>
                            <th class="py-3 px-4 text-right font-bold text-slate-100">Net Pay (ZWG)</th>
                            <th class="py-3 px-4 text-center">Leave Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach($payroll->items as $item)
                        @php $emp = $item->employee; @endphp
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 font-medium text-slate-200">
                                <div>{{ $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A' }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $emp?->employee_id }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-semibold">${{ number_format($item->gross_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->paye_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->aids_levy_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->nssa_employee_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->nec_employee_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->trade_union_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($item->medical_aid_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-amber-400">${{ number_format($item->loan_repayment_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">${{ number_format($item->net_pay_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">{{ number_format($item->net_pay_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-center font-bold text-amber-400">{{ number_format($item->leave_balance, 1) }} d</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-800 font-bold text-slate-100 border-t border-slate-700">
                        <tr>
                            <td class="py-3 px-4">TOTALS</td>
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($payroll->total_gross_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->total_paye_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->total_aids_levy_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->items->sum('nssa_employee_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->items->sum('nec_employee_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->items->sum('trade_union_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-400">${{ number_format($payroll->items->sum('medical_aid_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-amber-400">${{ number_format($payroll->items->sum('loan_repayment_usd'), 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-400">${{ number_format($payroll->total_net_usd, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-400">{{ number_format($payroll->total_net_zwg, 2) }}</td>
                            <td class="py-3 px-4 text-center">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
