<div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
    <div class="p-4 border-b border-slate-800">
        <h3 class="text-sm font-semibold text-slate-50">Payroll History</h3>
    </div>
    @php $payrollItems = $employee->payrollItems()->with('payroll')->latest()->get(); @endphp
    @if($payrollItems->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Period</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Gross</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">PAYE</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">NSSA</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">AIDS Levy</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Loan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Net Pay</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($payrollItems as $item)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3 text-xs text-slate-100">{{ \Carbon\Carbon::create($item->payroll->period_year, $item->payroll->period_month)->format('M Y') }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">${{ number_format($item->gross_pay, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-amber-400">${{ number_format($item->paye, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-amber-400">${{ number_format($item->nssa_employee, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-amber-400">${{ number_format($item->aids_levy, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-red-400">${{ number_format($item->loan_repayment, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-emerald-400 font-medium">${{ number_format($item->net_pay, 2) }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $item->status === 'active' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                    {{ $item->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : '' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-8 text-center">
            <p class="text-sm text-slate-400">No payroll history available.</p>
        </div>
    @endif
</div>
