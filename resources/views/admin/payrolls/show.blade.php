@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Payroll Detail</h1>
            <p class="text-xs text-slate-400 mt-1">{{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($payroll->status === 'draft')
                <form method="POST" action="{{ route('admin.payrolls.approve', $payroll) }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Approve this payroll?')" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-medium text-white hover:bg-blue-700">Approve</button>
                </form>
            @endif
            @if($payroll->status === 'processed')
                <form method="POST" action="{{ route('admin.payrolls.mark-paid', $payroll) }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Mark as paid?')" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-700">Mark as Paid</button>
                </form>
            @endif
            @if($payroll->status !== 'paid')
                <form method="POST" action="{{ route('admin.payrolls.destroy', $payroll) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete this payroll?')" class="rounded-lg bg-red-600/20 px-4 py-2 text-xs font-medium text-red-400 hover:bg-red-600/30">Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Gross Pay</p>
            <p class="text-lg font-semibold text-slate-50">${{ number_format($payroll->total_gross, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Total Deductions</p>
            <p class="text-lg font-semibold text-red-400">${{ number_format($payroll->total_deductions, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Net Pay</p>
            <p class="text-lg font-semibold text-emerald-400">${{ number_format($payroll->total_net, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Status</p>
            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium mt-1
                {{ $payroll->status === 'draft' ? 'bg-amber-500/20 text-amber-400' : '' }}
                {{ $payroll->status === 'processed' ? 'bg-blue-500/20 text-blue-400' : '' }}
                {{ $payroll->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : '' }}">
                {{ ucfirst($payroll->status) }}
            </span>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="p-4 border-b border-slate-800">
            <h3 class="text-sm font-medium text-slate-50">Employee Breakdown</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Employee</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Gross</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">PAYE</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">AIDS Levy</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">NSSA</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Loan</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Other Deduct</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Net Pay</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($payroll->items as $item)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-3 py-2 text-xs">
                                <p class="text-slate-50 font-medium">{{ $item->employee?->full_name ?? 'N/A' }}</p>
                                <p class="text-[10px] text-slate-400">{{ $item->employee?->department?->name }}</p>
                            </td>
                            <td class="px-3 py-2 text-xs text-slate-100">${{ number_format($item->gross_pay, 2) }}</td>
                            <td class="px-3 py-2 text-xs text-amber-400">${{ number_format($item->paye, 2) }}</td>
                            <td class="px-3 py-2 text-xs text-amber-400">${{ number_format($item->aids_levy, 2) }}</td>
                            <td class="px-3 py-2 text-xs text-amber-400">${{ number_format($item->nssa_employee, 2) }}</td>
                            <td class="px-3 py-2 text-xs text-red-400">${{ number_format($item->loan_repayment, 2) }}</td>
                            <td class="px-3 py-2 text-xs text-red-400">${{ number_format($item->trade_union + $item->nec + $item->other_deductions, 2) }}</td>
                            <td class="px-3 py-2 text-xs text-emerald-400 font-medium">${{ number_format($item->net_pay, 2) }}</td>
                            <td class="px-3 py-2 text-xs">
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
    </div>

    @if($payroll->notes)
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-xs font-medium text-slate-400 mb-2">Notes</h3>
            <p class="text-sm text-slate-50">{{ $payroll->notes }}</p>
        </div>
    @endif
</div>
@endsection
