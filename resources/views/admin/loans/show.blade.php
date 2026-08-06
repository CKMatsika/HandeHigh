@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Loan Details</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $loan->employee?->full_name ?? 'N/A' }} — {{ ucfirst($loan->loan_type) }} Loan</p>
        </div>
        <div class="flex items-center gap-2">
            @if($loan->status === 'active')
                <form method="POST" action="{{ route('admin.loans.settle', $loan) }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Mark this loan as settled?')" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-700">Settle Loan</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Loan Amount</p>
            <p class="text-lg font-semibold text-slate-50">${{ number_format($loan->loan_amount, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Balance</p>
            <p class="text-lg font-semibold {{ $loan->balance > 0 ? 'text-amber-400' : 'text-emerald-400' }}">${{ number_format($loan->balance, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Monthly Installment</p>
            <p class="text-lg font-semibold text-slate-50">${{ number_format($loan->monthly_installment, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Status</p>
            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium mt-1
                {{ $loan->status === 'active' ? 'bg-amber-500/20 text-amber-400' : '' }}
                {{ $loan->status === 'settled' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                {{ $loan->status === 'defaulted' ? 'bg-red-500/20 text-red-400' : '' }}">
                {{ ucfirst($loan->status) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-sm font-medium text-slate-50 mb-4">Loan Information</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-400">Loan Type</dt>
                    <dd class="text-xs text-slate-50 capitalize">{{ $loan->loan_type }}</dd>
                </div>
                @if($loan->loan_provider)
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-400">Provider</dt>
                    <dd class="text-xs text-slate-50">{{ $loan->loan_provider }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-400">Interest Rate</dt>
                    <dd class="text-xs text-slate-50">{{ $loan->interest_rate }}%</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-400">Repayment Period</dt>
                    <dd class="text-xs text-slate-50">{{ $loan->repayment_period_months }} months</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-400">Disbursed</dt>
                    <dd class="text-xs text-slate-50">{{ $loan->disbursed_date->format('d M Y') }}</dd>
                </div>
                @if($loan->first_payment_date)
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-400">First Payment</dt>
                    <dd class="text-xs text-slate-50">{{ $loan->first_payment_date->format('d M Y') }}</dd>
                </div>
                @endif
                @if($loan->settled_date)
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-400">Settled Date</dt>
                    <dd class="text-xs text-slate-50">{{ $loan->settled_date->format('d M Y') }}</dd>
                </div>
                @endif
                @if($loan->purpose)
                <div class="flex justify-between">
                    <dt class="text-xs text-slate-400">Purpose</dt>
                    <dd class="text-xs text-slate-50">{{ $loan->purpose }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-slate-50">Repayment History</h3>
                @if($loan->status === 'active')
                    <button onclick="document.getElementById('repaymentForm').classList.toggle('hidden')" class="text-xs text-emerald-400 hover:text-emerald-300">+ Record Payment</button>
                @endif
            </div>

            <form id="repaymentForm" method="POST" action="{{ route('admin.loans.repayments.store', $loan) }}" class="hidden mb-4 p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Amount</label>
                        <input type="number" step="0.01" name="amount" max="{{ $loan->balance }}" class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-slate-50 text-xs" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Date</label>
                        <input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-slate-50 text-xs" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Method</label>
                        <select name="payment_method" class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-slate-50 text-xs" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="payroll_deduction">Payroll Deduction</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Notes</label>
                        <input type="text" name="notes" class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-slate-50 text-xs">
                    </div>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-1.5 rounded">Record</button>
                </div>
            </form>

            @if($loan->repayments->isEmpty())
                <p class="text-xs text-slate-400 text-center py-6">No repayments recorded.</p>
            @else
                <div class="space-y-2">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs text-slate-400">
                                <th class="text-left pb-2">Date</th>
                                <th class="text-left pb-2">Method</th>
                                <th class="text-right pb-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($loan->repayments as $repayment)
                                <tr class="text-xs">
                                    <td class="py-1.5 text-slate-50">{{ $repayment->payment_date->format('d M Y') }}</td>
                                    <td class="py-1.5 text-slate-300 capitalize">{{ str_replace('_', ' ', $repayment->payment_method) }}</td>
                                    <td class="py-1.5 text-right text-emerald-400">${{ number_format($repayment->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($loan->notes)
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-xs font-medium text-slate-400 mb-2">Notes</h3>
            <p class="text-sm text-slate-50">{{ $loan->notes }}</p>
        </div>
    @endif
</div>
@endsection
