<div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
    <div class="p-4 border-b border-slate-800">
        <h3 class="text-sm font-semibold text-slate-50">Loan Records</h3>
    </div>
    @php $loans = $employee->loans()->with('repayments')->latest()->get(); @endphp
    @if($loans->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Monthly</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Paid</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($loans as $loan)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3 text-xs text-slate-100 capitalize">{{ $loan->loan_type }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">${{ number_format($loan->loan_amount, 2) }}</td>
                            <td class="px-4 py-3 text-xs {{ $loan->balance > 0 ? 'text-amber-400' : 'text-emerald-400' }}">${{ number_format($loan->balance, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">${{ number_format($loan->monthly_installment, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-emerald-400">${{ number_format($loan->total_paid, 2) }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $loan->status === 'active' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                    {{ $loan->status === 'settled' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $loan->status === 'defaulted' ? 'bg-red-500/20 text-red-400' : '' }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <a href="{{ route('admin.loans.show', $loan) }}" class="text-emerald-400 hover:text-emerald-300">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-8 text-center">
            <p class="text-sm text-slate-400">No loan records found.</p>
            <a href="{{ route('admin.loans.create') }}" class="text-xs text-emerald-400 hover:text-emerald-300 mt-2 inline-block">Issue Loan</a>
        </div>
    @endif
</div>
