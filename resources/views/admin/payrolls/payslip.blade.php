<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $employee->first_name }} {{ $employee->last_name }} - {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white !important; color: black !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-border { border-color: #cbd5e1 !important; }
            .print-bg { background-color: #f8fafc !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 p-4 md:p-8 font-sans antialiased">
    <div class="max-w-4xl mx-auto">
        <!-- Print Button Header -->
        <div class="no-print mb-4 flex items-center justify-between">
            <a href="{{ route('admin.payrolls.show', $payroll) }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900">
                ← Back to Payroll
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save PDF
            </button>
        </div>

        <!-- Payslip Card Container -->
        <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6 md:p-8 print-border">
            <!-- Header -->
            <div class="border-b border-slate-200 pb-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 uppercase tracking-wide">{{ $school->name }}</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Zimbabwe Statutory Compliant Payslip</p>
                    <p class="text-xs text-slate-500">{{ $school->address ?? 'Harare, Zimbabwe' }}</p>
                </div>
                <div class="text-left md:text-right">
                    <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-md uppercase tracking-wider print-bg">
                        Pay Period: {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}
                    </span>
                    <p class="text-xs text-slate-500 mt-1">Payment Date: {{ $payroll->processed_date ? \Carbon\Carbon::parse($payroll->processed_date)->format('d M Y') : 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Currency Mode: <strong class="text-slate-800">{{ $item->currency_mode }}</strong></p>
                </div>
            </div>

            <!-- Employee & Statutory Info Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-lg border border-slate-100 mb-6 text-xs print-bg">
                <div>
                    <span class="text-slate-500 block">Employee Name:</span>
                    <span class="font-bold text-slate-900">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Employee ID:</span>
                    <span class="font-semibold text-slate-800">{{ $employee->employee_id }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">National ID:</span>
                    <span class="font-semibold text-slate-800">{{ $employee->national_id ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">ZIMRA TIN:</span>
                    <span class="font-semibold text-slate-800">{{ $employee->zimra_tin ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">NSSA Number:</span>
                    <span class="font-semibold text-slate-800">{{ $employee->nssa_number ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">NEC Sector:</span>
                    <span class="font-semibold text-slate-800">{{ $employee->nec_sector_code ?? 'NEC-EDU' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Department & Role:</span>
                    <span class="font-semibold text-slate-800">{{ $employee->department?->name ?? 'General' }} • {{ $employee->position }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Payment Method:</span>
                    <span class="font-semibold text-slate-800">{{ ucfirst(str_replace('_', ' ', $item->payment_method)) }}</span>
                </div>
            </div>

            <!-- Dual-Currency Breakdown Tables -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Earnings Column -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <div class="bg-slate-100 px-4 py-2 border-b border-slate-200 print-bg">
                        <h2 class="text-xs font-bold uppercase text-slate-700">Gross Earnings</h2>
                    </div>
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-right">USD ($)</th>
                                <th class="px-3 py-2 text-right">ZWG</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Basic Salary</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format($item->basic_salary_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($item->basic_salary_zwg, 2) }}</td>
                            </tr>
                            @if($item->allowances_usd > 0 || $item->allowances_zwg > 0)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Allowances (Housing/Transport/Top-up)</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format($item->allowances_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($item->allowances_zwg, 2) }}</td>
                            </tr>
                            @endif
                            @if($item->bonus_usd > 0 || $item->bonus_zwg > 0)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Bonus</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format($item->bonus_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($item->bonus_zwg, 2) }}</td>
                            </tr>
                            @endif
                            @if($item->overtime_usd > 0 || $item->overtime_zwg > 0)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Overtime</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format($item->overtime_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($item->overtime_zwg, 2) }}</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                            <tr>
                                <td class="px-3 py-2 text-slate-900">Total Gross Pay</td>
                                <td class="px-3 py-2 text-right font-mono text-slate-900">${{ number_format($item->gross_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-slate-900">{{ number_format($item->gross_zwg, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Deductions Column -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <div class="bg-slate-100 px-4 py-2 border-b border-slate-200 print-bg">
                        <h2 class="text-xs font-bold uppercase text-slate-700">Deductions (Statutory & Voluntary)</h2>
                    </div>
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-right">USD ($)</th>
                                <th class="px-3 py-2 text-right">ZWG</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-3 py-2 text-slate-700">
                                    ZIMRA PAYE Tax
                                    @if($item->medical_aid_tax_credit_usd > 0 || $item->medical_aid_tax_credit_zwg > 0)
                                        <span class="block text-[10px] text-emerald-600">(50% Med Credit Applied)</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-amber-700">${{ number_format($item->paye_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-amber-700">{{ number_format($item->paye_zwg, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-slate-700">AIDS Levy (3% of PAYE)</td>
                                <td class="px-3 py-2 text-right font-mono text-amber-700">${{ number_format($item->aids_levy_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-amber-700">{{ number_format($item->aids_levy_zwg, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-slate-700">NSSA Social Security (4.5%)</td>
                                <td class="px-3 py-2 text-right font-mono text-blue-700">${{ number_format($item->nssa_employee_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-blue-700">{{ number_format($item->nssa_employee_zwg, 2) }}</td>
                            </tr>
                            @if($item->nec_employee_usd > 0 || $item->nec_employee_zwg > 0)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">NEC Sector Contribution</td>
                                <td class="px-3 py-2 text-right font-mono text-purple-700">${{ number_format($item->nec_employee_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-purple-700">{{ number_format($item->nec_employee_zwg, 2) }}</td>
                            </tr>
                            @endif
                            @if($item->trade_union_usd > 0 || $item->trade_union_zwg > 0)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Trade Union Dues</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format($item->trade_union_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($item->trade_union_zwg, 2) }}</td>
                            </tr>
                            @endif
                            @if($item->medical_aid_usd > 0 || $item->medical_aid_zwg > 0)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Medical Aid Premium</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format($item->medical_aid_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($item->medical_aid_zwg, 2) }}</td>
                            </tr>
                            @endif
                            @if($item->loan_repayment_usd > 0 || $item->loan_repayment_zwg > 0)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Loan Repayment</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format($item->loan_repayment_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($item->loan_repayment_zwg, 2) }}</td>
                            </tr>
                            @endif
                            @if($item->other_deductions_usd > 0 || $item->other_deductions_zwg > 0)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Other Deductions</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format($item->other_deductions_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($item->other_deductions_zwg, 2) }}</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                            <tr>
                                <td class="px-3 py-2 text-rose-700">Total Deductions</td>
                                <td class="px-3 py-2 text-right font-mono text-rose-700">${{ number_format($item->total_deductions_usd, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-rose-700">{{ number_format($item->total_deductions_zwg, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Net Pay & Statutory Leave (Zimbabwe Labour Act) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Net Pay Summary Box -->
                <div class="md:col-span-2 bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between print-bg">
                    <div>
                        <h3 class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Net Take-Home Pay</h3>
                        <p class="text-xs text-emerald-600 mt-0.5">Amount payable after all statutory and voluntary deductions</p>
                    </div>
                    <div class="mt-3 sm:mt-0 flex gap-6 text-right font-mono">
                        @if($item->gross_usd > 0 || $item->net_pay_usd > 0)
                        <div>
                            <span class="text-[10px] text-emerald-700 uppercase font-bold block">Net USD ($)</span>
                            <span class="text-xl font-extrabold text-emerald-800">${{ number_format($item->net_pay_usd, 2) }}</span>
                        </div>
                        @endif
                        @if($item->gross_zwg > 0 || $item->net_pay_zwg > 0)
                        <div>
                            <span class="text-[10px] text-emerald-700 uppercase font-bold block">Net ZWG</span>
                            <span class="text-xl font-extrabold text-emerald-800">ZWG {{ number_format($item->net_pay_zwg, 2) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Statutory Leave Balances Card -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 print-bg flex flex-col justify-between text-xs">
                    <div class="flex justify-between items-center border-b border-amber-200 pb-1.5">
                        <span class="font-bold text-amber-900 text-[11px] uppercase">Leave Balances</span>
                        <span class="text-[9px] text-amber-700 font-semibold">Zim Labour Act Sec 14A</span>
                    </div>
                    <div class="grid grid-cols-3 gap-1 text-center my-1">
                        <div class="bg-white/80 p-1 rounded border border-amber-100">
                            <span class="text-[9px] text-slate-500 block">Accrued</span>
                            <span class="font-bold text-slate-800">{{ number_format($item->leave_days_accrued, 1) }}d</span>
                        </div>
                        <div class="bg-white/80 p-1 rounded border border-amber-100">
                            <span class="text-[9px] text-slate-500 block">Taken</span>
                            <span class="font-bold text-rose-700">{{ number_format($item->leave_days_taken, 1) }}d</span>
                        </div>
                        <div class="bg-emerald-100/70 p-1 rounded border border-emerald-200">
                            <span class="text-[9px] text-emerald-800 font-semibold block">Available</span>
                            <span class="font-extrabold text-emerald-900">{{ number_format($item->leave_balance, 1) }}d</span>
                        </div>
                    </div>
                    <div class="text-[9px] text-slate-500 italic text-center">Accrues 2.5 days/month</div>
                </div>
            </div>

            <!-- Employer Statutory Contributions (Zimbabwe Labour Act Transparent Disclosure) -->
            <div class="border-t border-slate-200 pt-4 mb-4">
                <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Employer Statutory Contributions (Paid on your behalf)</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-slate-600 bg-slate-50 p-3 rounded border border-slate-200 print-bg">
                    <div>
                        <span class="block text-slate-400">Employer NSSA (4.5%):</span>
                        <span class="font-mono font-semibold">${{ number_format($item->nssa_employer_usd, 2) }} / ZWG {{ number_format($item->nssa_employer_zwg, 2) }}</span>
                    </div>
                    <div>
                        <span class="block text-slate-400">Employer NEC Share:</span>
                        <span class="font-mono font-semibold">${{ number_format($item->nec_employer_usd, 2) }} / ZWG {{ number_format($item->nec_employer_zwg, 2) }}</span>
                    </div>
                    <div class="col-span-2">
                        <span class="block text-slate-400">Statutory Notice:</span>
                        <span class="text-[10px] text-slate-500">Calculated in accordance with ZIMRA, NSSA Act [Chapter 17:04], and applicable NEC CBA.</span>
                    </div>
                </div>
            </div>

            <div class="text-center text-[10px] text-slate-400 border-t border-slate-100 pt-3">
                This is a computer-generated document and requires no physical signature. Generated on {{ now()->format('d M Y H:i') }}.
            </div>
        </div>
    </div>
</body>
</html>
