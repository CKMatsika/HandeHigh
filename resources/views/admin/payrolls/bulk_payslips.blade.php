<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Payslips - {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }} - {{ $school->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .payslip-page { page-break-after: always; box-shadow: none !important; border: 1px solid #e2e8f0 !important; margin-bottom: 0 !important; }
            .payslip-page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans antialiased p-4 md:p-8">

    <!-- Print / Action Toolbar -->
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.payrolls.show', $payroll) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition flex items-center gap-2">
                ← Back to Payroll
            </a>
            <span class="text-sm font-semibold text-slate-700">Total Payslips: {{ $payroll->items->count() }}</span>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save All as PDF
            </button>
        </div>
    </div>

    <!-- Payslip Iteration -->
    <div class="max-w-4xl mx-auto space-y-8">
        @foreach($payroll->items as $item)
            @php $emp = $item->employee; @endphp
            <div class="payslip-page bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                <!-- Header -->
                <div class="border-b border-slate-200 pb-6 flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $school->name }}</h1>
                        <p class="text-xs text-slate-500 mt-1">{{ $school->address ?? 'Harare, Zimbabwe' }} | Tel: {{ $school->phone ?? 'N/A' }}</p>
                        <div class="inline-block mt-2 px-2.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[11px] font-semibold tracking-wider uppercase border border-blue-100">
                            Confidential Employee Payslip
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pay Period</div>
                        <div class="text-lg font-bold text-slate-900 mt-0.5">
                            {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">Pay Date: {{ $payroll->processed_date->format('d M Y') }}</div>
                    </div>
                </div>

                <!-- Employee & Statutory Info -->
                <div class="grid grid-cols-2 gap-6 my-6 p-4 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                    <div class="space-y-1.5">
                        <div><span class="text-slate-500 font-medium">Employee Name:</span> <strong class="text-slate-900 font-semibold">{{ $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A' }}</strong></div>
                        <div><span class="text-slate-500 font-medium">Employee ID:</span> <span class="font-mono text-slate-700">{{ $emp?->employee_id ?? 'N/A' }}</span></div>
                        <div><span class="text-slate-500 font-medium">Department:</span> <span class="text-slate-700">{{ $emp?->department?->name ?? 'General' }}</span></div>
                        <div><span class="text-slate-500 font-medium">Designation:</span> <span class="text-slate-700">{{ $emp?->position ?? 'Staff' }}</span></div>
                    </div>
                    <div class="space-y-1.5 border-l border-slate-200 pl-6">
                        <div><span class="text-slate-500 font-medium">National ID:</span> <span class="font-mono text-slate-700">{{ $emp?->national_id ?? 'N/A' }}</span></div>
                        <div><span class="text-slate-500 font-medium">ZIMRA TIN:</span> <span class="font-mono text-slate-700">{{ $emp?->zimra_tin ?? 'N/A' }}</span></div>
                        <div><span class="text-slate-500 font-medium">NSSA Number:</span> <span class="font-mono text-slate-700">{{ $emp?->nssa_number ?? 'N/A' }}</span></div>
                        <div><span class="text-slate-500 font-medium">NEC Council:</span> <span class="text-slate-700">{{ $emp?->nec_sector_code ?? 'NEC-EDU' }}</span></div>
                    </div>
                </div>

                <!-- Dual Currency Earnings & Deductions Breakdown -->
                <div class="grid grid-cols-2 gap-6">
                    <!-- Earnings -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <div class="bg-emerald-50 px-4 py-2.5 border-b border-slate-200 flex justify-between font-semibold text-emerald-900 text-xs">
                            <span>Earnings & Allowances</span>
                            <div class="flex gap-4">
                                <span class="w-16 text-right">USD</span>
                                <span class="w-16 text-right">ZWG</span>
                            </div>
                        </div>
                        <div class="p-4 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-700">
                                <span>Basic Salary</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->basic_salary_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->basic_salary_zwg, 2) }}</span>
                                </div>
                            </div>
                            @if($item->allowances_usd > 0 || $item->allowances_zwg > 0)
                            <div class="flex justify-between text-slate-700">
                                <span>Allowances</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->allowances_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->allowances_zwg, 2) }}</span>
                                </div>
                            </div>
                            @endif
                            @if($item->bonus_usd > 0 || $item->bonus_zwg > 0)
                            <div class="flex justify-between text-slate-700">
                                <span>Bonus</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->bonus_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->bonus_zwg, 2) }}</span>
                                </div>
                            </div>
                            @endif
                            @if($item->overtime_usd > 0 || $item->overtime_zwg > 0)
                            <div class="flex justify-between text-slate-700">
                                <span>Overtime</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->overtime_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->overtime_zwg, 2) }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="bg-slate-50 px-4 py-2.5 border-t border-slate-200 flex justify-between font-bold text-xs text-slate-900">
                            <span>TOTAL GROSS EARNINGS</span>
                            <div class="flex gap-4 font-mono">
                                <span class="w-16 text-right text-emerald-700">${{ number_format($item->gross_usd, 2) }}</span>
                                <span class="w-16 text-right text-emerald-700">{{ number_format($item->gross_zwg, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Statutory & Other Deductions -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <div class="bg-rose-50 px-4 py-2.5 border-b border-slate-200 flex justify-between font-semibold text-rose-900 text-xs">
                            <span>Statutory & Other Deductions</span>
                            <div class="flex gap-4">
                                <span class="w-16 text-right">USD</span>
                                <span class="w-16 text-right">ZWG</span>
                            </div>
                        </div>
                        <div class="p-4 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-700">
                                <span>ZIMRA PAYE Tax</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->paye_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->paye_zwg, 2) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between text-slate-700">
                                <span>ZIMRA AIDS Levy (3%)</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->aids_levy_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->aids_levy_zwg, 2) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between text-slate-700">
                                <span>NSSA Pension (4.5%)</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->nssa_employee_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->nssa_employee_zwg, 2) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between text-slate-700">
                                <span>NEC Council</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->nec_employee_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->nec_employee_zwg, 2) }}</span>
                                </div>
                            </div>
                            @if($item->medical_aid_usd > 0 || $item->medical_aid_zwg > 0)
                            <div class="flex justify-between text-slate-700">
                                <span>Medical Aid</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->medical_aid_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->medical_aid_zwg, 2) }}</span>
                                </div>
                            </div>
                            @endif
                            @if($item->loan_repayment_usd > 0 || $item->loan_repayment_zwg > 0)
                            <div class="flex justify-between text-slate-700">
                                <span>Staff Loan Repayment</span>
                                <div class="flex gap-4 font-mono">
                                    <span class="w-16 text-right">${{ number_format($item->loan_repayment_usd, 2) }}</span>
                                    <span class="w-16 text-right">{{ number_format($item->loan_repayment_zwg, 2) }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="bg-slate-50 px-4 py-2.5 border-t border-slate-200 flex justify-between font-bold text-xs text-slate-900">
                            <span>TOTAL DEDUCTIONS</span>
                            <div class="flex gap-4 font-mono">
                                <span class="w-16 text-right text-rose-700">${{ number_format($item->total_deductions_usd, 2) }}</span>
                                <span class="w-16 text-right text-rose-700">{{ number_format($item->total_deductions_zwg, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Pay & Statutory Leave Summary (Zimbabwe Labour Act) -->
                <div class="grid grid-cols-2 gap-6 mt-6">
                    <!-- Net Pay Summary -->
                    <div class="p-5 bg-gradient-to-br from-blue-900 to-indigo-900 text-white rounded-xl flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-semibold tracking-wider uppercase text-blue-200">Net Take-Home Pay</div>
                            <div class="text-xs text-blue-200 mt-1">Payment Method: {{ ucfirst(str_replace('_', ' ', $item->payment_method ?? 'Bank Transfer')) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-black font-mono tracking-tight">${{ number_format($item->net_pay_usd, 2) }} <span class="text-xs font-normal text-blue-200">USD</span></div>
                            @if($item->net_pay_zwg > 0)
                            <div class="text-sm font-semibold font-mono text-emerald-300">{{ number_format($item->net_pay_zwg, 2) }} <span class="text-xs font-normal text-blue-200">ZWG</span></div>
                            @endif
                        </div>
                    </div>

                    <!-- Statutory Leave Balances (Zimbabwe Labour Act: 2.5 days/mo) -->
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs flex flex-col justify-between">
                        <div class="flex justify-between items-center border-b border-amber-200/60 pb-2">
                            <span class="font-bold text-amber-950 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Statutory Leave Summary
                            </span>
                            <span class="text-[10px] text-amber-800 font-semibold uppercase">Zim Labour Act (Sec 14A)</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center my-1.5">
                            <div class="bg-white/80 p-1.5 rounded border border-amber-100">
                                <div class="text-slate-500 text-[10px]">Accrued</div>
                                <div class="font-bold text-slate-800">{{ number_format($item->leave_days_accrued, 1) }} d</div>
                            </div>
                            <div class="bg-white/80 p-1.5 rounded border border-amber-100">
                                <div class="text-slate-500 text-[10px]">Taken</div>
                                <div class="font-bold text-rose-700">{{ number_format($item->leave_days_taken, 1) }} d</div>
                            </div>
                            <div class="bg-emerald-100/70 p-1.5 rounded border border-emerald-200">
                                <div class="text-emerald-800 text-[10px] font-semibold">Available</div>
                                <div class="font-extrabold text-emerald-900">{{ number_format($item->leave_balance, 1) }} d</div>
                            </div>
                        </div>
                        <div class="text-[10px] text-slate-500 italic text-center">Accrues at standard statutory rate of 2.5 working days per month.</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</body>
</html>
