@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Bills (Accounts Payable)</h1>
            <p class="text-xs text-slate-400 mt-1">Manage vendor bills and payments.</p>
        </div>
        <a href="{{ route('admin.bills.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Create Bill</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Bill #</th>
                        <th class="px-4 py-3 text-left font-medium">Vendor</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Due Date</th>
                        <th class="px-4 py-3 text-right font-medium">Amount</th>
                        <th class="px-4 py-3 text-right font-medium">Paid</th>
                        <th class="px-4 py-3 text-right font-medium">Balance</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($bills as $bill)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono font-medium">{{ $bill->bill_number }}</td>
                            <td class="px-4 py-3">{{ $bill->vendor->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $bill->bill_date?->format('M d, Y') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $isOverdue = $bill->due_date && $bill->due_date->isPast() && in_array($bill->status, ['pending', 'partial']);
                                @endphp
                                <span class="{{ $isOverdue ? 'text-red-400' : '' }}">{{ $bill->due_date?->format('M d, Y') ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format($bill->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-400">{{ number_format($bill->paid_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right {{ $bill->balance > 0 ? 'text-amber-400' : 'text-emerald-400' }}">{{ number_format($bill->balance, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($bill->status === 'paid')
                                    <span class="px-2 py-1 rounded-full text-xs bg-emerald-500/20 text-emerald-300">Paid</span>
                                @elseif($bill->status === 'partial')
                                    <span class="px-2 py-1 rounded-full text-xs bg-amber-500/20 text-amber-300">Partial</span>
                                @elseif($bill->status === 'overdue' || ($bill->due_date && $bill->due_date->isPast() && in_array($bill->status, ['pending', 'partial'])))
                                    <span class="px-2 py-1 rounded-full text-xs bg-red-500/20 text-red-300">Overdue</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs bg-slate-500/20 text-slate-300">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.bills.show', $bill) }}" class="text-indigo-400 hover:text-indigo-300">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-slate-500">No bills found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $bills->links() }}
        </div>
    </div>
@endsection
