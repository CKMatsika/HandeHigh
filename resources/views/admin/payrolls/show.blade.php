@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">Payroll Detail</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                    {{ $payroll->status === 'draft' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : '' }}
                    {{ $payroll->status === 'processed' ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : '' }}
                    {{ $payroll->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : '' }}">
                    {{ ucfirst($payroll->status) }}
                </span>
                @if($payroll->is_locked)
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Locked & Compliant
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Period: <span class="text-slate-200 font-medium">{{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}</span> 
                • Processed: {{ $payroll->processed_date ? \Carbon\Carbon::parse($payroll->processed_date)->format('M d, Y') : 'N/A' }}
                @if($payroll->locked_at)
                    • Locked at: {{ $payroll->locked_at->format('M d, Y H:i') }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Statutory Compliance Reports -->
            <a href="{{ route('admin.payrolls.statutory-report', [$payroll, 'type' => 'zimra-p2']) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 border border-slate-700 px-3.5 py-2 text-xs font-medium text-slate-200 hover:bg-slate-700 shadow-sm transition">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Statutory Returns
            </a>

            <!-- Bulk Print Payslips -->
            <a href="{{ route('admin.payrolls.bulk-payslips', $payroll) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 border border-slate-700 px-3.5 py-2 text-xs font-medium text-slate-200 hover:bg-slate-700 shadow-sm transition">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Bulk Print Payslips
            </a>

            <!-- ZIMRA CSV Export -->
            <a href="{{ route('admin.payrolls.export-tarms', $payroll) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-indigo-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                ZIMRA TaRMS CSV
            </a>

            @if($payroll->status === 'draft')
                <form method="POST" action="{{ route('admin.payrolls.approve', $payroll) }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Approve this payroll? This will lock the run, accrue statutory leave (+2.5 days), and auto-post balanced entries to Finance General Ledger.')" class="rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-700 shadow-sm transition">
                        Approve & Post to GL
                    </button>
                </form>
            @endif

            @if($payroll->status === 'processed')
                <form method="POST" action="{{ route('admin.payrolls.mark-paid', $payroll) }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Mark this payroll as paid?')" class="rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-emerald-700 shadow-sm transition">
                        Mark as Paid
                    </button>
                </form>
            @endif

            @if(!$payroll->is_locked && $payroll->status === 'draft')
                <form method="POST" action="{{ route('admin.payrolls.destroy', $payroll) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete this draft payroll?')" class="rounded-lg bg-red-600/20 px-3.5 py-2 text-xs font-medium text-red-400 hover:bg-red-600/30 transition">Delete</button>
                </form>
            @endif
        </div>
    </div>

    <!-- General Ledger Auto-Posting Confirmation Banner -->
    @if($payroll->journalBatch)
        <div class="p-4 rounded-xl bg-slate-900 border border-emerald-500/30 bg-emerald-950/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-emerald-500/20 text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-100">Auto-Posted to Accounting General Ledger</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Journal Batch <span class="font-mono text-emerald-400 font-bold">#{{ $payroll->journalBatch->batch_number }}</span> posted on {{ $payroll->journalBatch->transaction_date->format('M d, Y') }}. Double-entry control accounts balanced.
                    </p>
                </div>
            </div>
            <div class="text-right text-xs">
                <span class="px-2 py-1 rounded bg-emerald-500/20 text-emerald-400 font-semibold border border-emerald-500/30">GL Posted</span>
            </div>
        </div>
    @endif

    <!-- Dual-Currency Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Gross Pay (USD & ZWG) -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/90 p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Gross Payroll</p>
            <div class="mt-2 flex items-baseline justify-between">
                <div>
                    <span class="text-xs text-slate-400">USD</span>
                    <p class="text-lg font-bold text-slate-100">${{ number_format($payroll->total_gross_usd, 2) }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">ZWG</span>
                    <p class="text-lg font-bold text-slate-100">ZWG {{ number_format($payroll->total_gross_zwg, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Statutory Deductions (PAYE + AIDS Levy) -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/90 p-4 shadow-sm">
            <p class="text-xs font-medium text-amber-400/90 uppercase tracking-wider">ZIMRA Tax (PAYE + AIDS)</p>
            <div class="mt-2 flex items-baseline justify-between">
                <div>
                    <span class="text-xs text-slate-400">USD</span>
                    <p class="text-lg font-bold text-amber-400">${{ number_format($payroll->total_paye_usd + $payroll->total_aids_levy_usd, 2) }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">ZWG</span>
                    <p class="text-lg font-bold text-amber-400">ZWG {{ number_format($payroll->total_paye_zwg + $payroll->total_aids_levy_zwg, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Total Deductions -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/90 p-4 shadow-sm">
            <p class="text-xs font-medium text-rose-400/90 uppercase tracking-wider">Total Deductions</p>
            <div class="mt-2 flex items-baseline justify-between">
                <div>
                    <span class="text-xs text-slate-400">USD</span>
                    <p class="text-lg font-bold text-rose-400">${{ number_format($payroll->total_deductions_usd, 2) }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">ZWG</span>
                    <p class="text-lg font-bold text-rose-400">ZWG {{ number_format($payroll->total_deductions_zwg, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Net Pay -->
        <div class="rounded-xl border border-emerald-900/40 bg-emerald-950/20 p-4 shadow-sm">
            <p class="text-xs font-medium text-emerald-400 uppercase tracking-wider">Net Payable</p>
            <div class="mt-2 flex items-baseline justify-between">
                <div>
                    <span class="text-xs text-slate-400">USD</span>
                    <p class="text-lg font-bold text-emerald-400">${{ number_format($payroll->total_net_usd, 2) }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">ZWG</span>
                    <p class="text-lg font-bold text-emerald-400">ZWG {{ number_format($payroll->total_net_zwg, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statutory Compliance Remittance Summary Panel -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- ZIMRA Breakdown -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-xs font-semibold text-slate-200 uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span> ZIMRA Statutory Due
            </h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-slate-300">
                    <span>PAYE (USD / ZWG):</span>
                    <span class="font-medium">${{ number_format($payroll->total_paye_usd, 2) }} / ZWG {{ number_format($payroll->total_paye_zwg, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-300">
                    <span>AIDS Levy 3% (USD / ZWG):</span>
                    <span class="font-medium">${{ number_format($payroll->total_aids_levy_usd, 2) }} / ZWG {{ number_format($payroll->total_aids_levy_zwg, 2) }}</span>
                </div>
                <div class="pt-2 border-t border-slate-800 flex justify-between font-semibold text-slate-100">
                    <span>Total ZIMRA Remittance:</span>
                    <span class="text-amber-400">${{ number_format($payroll->total_paye_usd + $payroll->total_aids_levy_usd, 2) }} + ZWG {{ number_format($payroll->total_paye_zwg + $payroll->total_aids_levy_zwg, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- NSSA Breakdown -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-xs font-semibold text-slate-200 uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> NSSA Social Security Due
            </h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-slate-300">
                    <span>Employee Share 4.5%:</span>
                    <span class="font-medium">${{ number_format($payroll->items->sum('nssa_employee_usd'), 2) }} / ZWG {{ number_format($payroll->items->sum('nssa_employee_zwg'), 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-300">
                    <span>Employer Share 4.5%:</span>
                    <span class="font-medium">${{ number_format($payroll->total_employer_nssa_usd, 2) }} / ZWG {{ number_format($payroll->total_employer_nssa_zwg, 2) }}</span>
                </div>
                <div class="pt-2 border-t border-slate-800 flex justify-between font-semibold text-slate-100">
                    <span>Total NSSA Remittance:</span>
                    <span class="text-emerald-400">${{ number_format($payroll->items->sum('nssa_employee_usd') + $payroll->total_employer_nssa_usd, 2) }} + ZWG {{ number_format($payroll->items->sum('nssa_employee_zwg') + $payroll->total_employer_nssa_zwg, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- NEC Breakdown -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-xs font-semibold text-slate-200 uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span> NEC Council CBA Due
            </h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-slate-300">
                    <span>Employee Share:</span>
                    <span class="font-medium">${{ number_format($payroll->items->sum('nec_employee_usd'), 2) }} / ZWG {{ number_format($payroll->items->sum('nec_employee_zwg'), 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-300">
                    <span>Employer Share:</span>
                    <span class="font-medium">${{ number_format($payroll->total_employer_nec_usd, 2) }} / ZWG {{ number_format($payroll->total_employer_nec_zwg, 2) }}</span>
                </div>
                <div class="pt-2 border-t border-slate-800 flex justify-between font-semibold text-slate-100">
                    <span>Total NEC Remittance:</span>
                    <span class="text-purple-400">${{ number_format($payroll->items->sum('nec_employee_usd') + $payroll->total_employer_nec_usd, 2) }} + ZWG {{ number_format($payroll->items->sum('nec_employee_zwg') + $payroll->total_employer_nec_zwg, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Itemized Breakdown Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-50">Employee Compliance Breakdown</h3>
            <span class="text-xs text-slate-400">{{ $payroll->items->count() }} employees</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-800/60">
                    <tr>
                        <th class="px-3 py-3 text-left font-medium text-slate-300">Employee / TIN / ID</th>
                        <th class="px-3 py-3 text-left font-medium text-slate-300">Currency</th>
                        <th class="px-3 py-3 text-right font-medium text-slate-300">Gross Pay</th>
                        <th class="px-3 py-3 text-right font-medium text-slate-300">PAYE</th>
                        <th class="px-3 py-3 text-right font-medium text-slate-300">AIDS Levy</th>
                        <th class="px-3 py-3 text-right font-medium text-slate-300">NSSA (4.5%)</th>
                        <th class="px-3 py-3 text-right font-medium text-slate-300">NEC</th>
                        <th class="px-3 py-3 text-right font-medium text-slate-300">Med Credit</th>
                        <th class="px-3 py-3 text-right font-medium text-slate-300">Total Deduct</th>
                        <th class="px-3 py-3 text-right font-medium text-emerald-400">Net Pay</th>
                        <th class="px-3 py-3 text-center font-medium text-slate-300">Leave Due</th>
                        <th class="px-3 py-3 text-center font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($payroll->items as $item)
                        @php $emp = $item->employee; @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-3">
                                <p class="text-slate-100 font-semibold">{{ $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A' }}</p>
                                <div class="text-[10px] text-slate-400 space-x-1 mt-0.5">
                                    <span>TIN: {{ $emp?->zimra_tin ?? 'N/A' }}</span> • 
                                    <span>ID: {{ $emp?->national_id ?? $emp?->employee_id ?? 'N/A' }}</span> •
                                    <span>Sector: {{ $emp?->nec_sector_code ?? 'NEC-EDU' }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $item->currency_mode }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                @if($item->gross_usd > 0) <div>${{ number_format($item->gross_usd, 2) }}</div> @endif
                                @if($item->gross_zwg > 0) <div class="text-slate-400">ZWG {{ number_format($item->gross_zwg, 2) }}</div> @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-amber-400">
                                @if($item->gross_usd > 0) <div>${{ number_format($item->paye_usd, 2) }}</div> @endif
                                @if($item->gross_zwg > 0) <div>ZWG {{ number_format($item->paye_zwg, 2) }}</div> @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-amber-400">
                                @if($item->gross_usd > 0) <div>${{ number_format($item->aids_levy_usd, 2) }}</div> @endif
                                @if($item->gross_zwg > 0) <div>ZWG {{ number_format($item->aids_levy_zwg, 2) }}</div> @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-blue-400">
                                @if($item->gross_usd > 0) <div>${{ number_format($item->nssa_employee_usd, 2) }}</div> @endif
                                @if($item->gross_zwg > 0) <div>ZWG {{ number_format($item->nssa_employee_zwg, 2) }}</div> @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-purple-400">
                                @if($item->gross_usd > 0) <div>${{ number_format($item->nec_employee_usd, 2) }}</div> @endif
                                @if($item->gross_zwg > 0) <div>ZWG {{ number_format($item->nec_employee_zwg, 2) }}</div> @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-cyan-400">
                                @if($item->medical_aid_tax_credit_usd > 0) <div>-${{ number_format($item->medical_aid_tax_credit_usd, 2) }}</div> @endif
                                @if($item->medical_aid_tax_credit_zwg > 0) <div>-ZWG {{ number_format($item->medical_aid_tax_credit_zwg, 2) }}</div> @endif
                                @if($item->medical_aid_tax_credit_usd == 0 && $item->medical_aid_tax_credit_zwg == 0) <span class="text-slate-500">-</span> @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-rose-400">
                                @if($item->gross_usd > 0) <div>${{ number_format($item->total_deductions_usd, 2) }}</div> @endif
                                @if($item->gross_zwg > 0) <div>ZWG {{ number_format($item->total_deductions_zwg, 2) }}</div> @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-emerald-400">
                                @if($item->gross_usd > 0) <div>${{ number_format($item->net_pay_usd, 2) }}</div> @endif
                                @if($item->gross_zwg > 0) <div>ZWG {{ number_format($item->net_pay_zwg, 2) }}</div> @endif
                            </td>
                            <td class="px-3 py-3 text-center font-bold text-amber-400">
                                {{ number_format($item->leave_balance, 1) }} d
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($emp)
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.payrolls.payslip', [$payroll, $emp]) }}" target="_blank" class="inline-flex items-center px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-[11px] font-medium text-slate-200 border border-slate-700 transition">
                                            View Slip
                                        </a>
                                        @if($emp->email)
                                            <form method="POST" action="{{ route('admin.payrolls.email-payslip', [$payroll, $emp]) }}" class="inline">
                                                @csrf
                                                <button type="submit" title="Email payslip to {{ $emp->email }}" onclick="return confirm('Send payslip by email to {{ $emp->email }}?')" class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($payroll->notes)
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes</h3>
            <p class="text-xs text-slate-200">{{ $payroll->notes }}</p>
        </div>
    @endif
</div>
@endsection
