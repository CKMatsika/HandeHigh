@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Payroll Management</h1>
            <p class="text-xs text-slate-400 mt-1">Process payroll with ZIMRA PAYE, NSSA, AIDS Levy calculations.</p>
        </div>
        <a href="{{ route('admin.payrolls.create') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
            Process Payroll
        </a>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="flex items-end gap-3">
            <div>
                <select name="year" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                    <option value="">All Years</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-4 py-2 rounded-lg">Filter</button>
            <a href="{{ route('admin.payrolls.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-4 py-2 rounded-lg">Clear</a>
        </form>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Period</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Employees</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Gross Pay</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Deductions</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Net Pay</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($payrolls as $payroll)
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-3 text-sm text-slate-50 font-medium">
                                {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $payroll->items_count ?? $payroll->items->count() }}</td>
                            <td class="px-4 py-3 text-xs text-slate-100">${{ number_format($payroll->total_gross, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-red-400">${{ number_format($payroll->total_deductions, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-emerald-400 font-medium">${{ number_format($payroll->total_net, 2) }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $payroll->status === 'draft' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                    {{ $payroll->status === 'processed' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                    {{ $payroll->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $payroll->status === 'cancelled' ? 'bg-red-500/20 text-red-400' : '' }}">
                                    {{ ucfirst($payroll->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $payroll->processed_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-xs">
                                <a href="{{ route('admin.payrolls.show', $payroll) }}" class="text-emerald-400 hover:text-emerald-300">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                                <h3 class="text-lg font-medium text-slate-50 mb-2">No payrolls processed</h3>
                                <p class="text-sm text-slate-400 mb-4">Process your first payroll run.</p>
                                <a href="{{ route('admin.payrolls.create') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Process Payroll</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payrolls->hasPages())<div class="px-4 py-3 border-t border-slate-800">{{ $payrolls->links() }}</div>@endif
    </div>
</div>
@endsection
