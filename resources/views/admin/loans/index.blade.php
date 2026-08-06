@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Loan Management</h1>
            <p class="text-xs text-slate-400 mt-1">Manage employee loans and repayment tracking.</p>
        </div>
        <a href="{{ route('admin.loans.create') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Loan
        </a>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <select name="status" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="settled" {{ request('status') == 'settled' ? 'selected' : '' }}>Settled</option>
                    <option value="defaulted" {{ request('status') == 'defaulted' ? 'selected' : '' }}>Defaulted</option>
                </select>
            </div>
            <div>
                <select name="employee_id" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-4 py-2 rounded-lg">Filter</button>
            <a href="{{ route('admin.loans.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-4 py-2 rounded-lg">Clear</a>
        </form>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Monthly</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Disbursed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($loans as $loan)
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-3 text-sm text-slate-50">{{ $loan->employee?->full_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="capitalize">{{ $loan->loan_type }}</span>
                                @if($loan->loan_provider)
                                    <p class="text-[10px] text-slate-400">{{ $loan->loan_provider }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-100">${{ number_format($loan->loan_amount, 2) }}</td>
                            <td class="px-4 py-3 text-xs {{ $loan->balance > 0 ? 'text-amber-400' : 'text-emerald-400' }}">${{ number_format($loan->balance, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">${{ number_format($loan->monthly_installment, 2) }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $loan->status === 'active' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                    {{ $loan->status === 'settled' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $loan->status === 'defaulted' ? 'bg-red-500/20 text-red-400' : '' }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $loan->disbursed_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-xs">
                                <a href="{{ route('admin.loans.show', $loan) }}" class="text-emerald-400 hover:text-emerald-300">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                                <p class="text-sm mb-4">No loans recorded yet.</p>
                                <a href="{{ route('admin.loans.create') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Issue Loan</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($loans->hasPages())<div class="px-4 py-3 border-t border-slate-800">{{ $loans->links() }}</div>@endif
    </div>
</div>
@endsection
