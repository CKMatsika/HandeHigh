@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">{{ $student->first_name }}'s Fees</h1>
            <p class="text-xs text-slate-400 mt-1">Invoice history and payment status for {{ $student->first_name }} {{ $student->last_name }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('parent.dashboard') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back to Dashboard</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Invoice #</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Due Date</th>
                        <th class="px-4 py-3 text-left font-medium">Amount</th>
                        <th class="px-4 py-3 text-left font-medium">Paid</th>
                        <th class="px-4 py-3 text-left font-medium">Balance</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-medium">{{ $invoice->number }}</td>
                            <td class="px-4 py-3">{{ $invoice->issued_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3">{{ $invoice->due_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3">${{ number_format($invoice->total, 2) }}</td>
                            <td class="px-4 py-3">${{ number_format($invoice->paid_amount, 2) }}</td>
                            <td class="px-4 py-3">${{ number_format($invoice->balance, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $invoice->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : ($invoice->status === 'partial' ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400') }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No invoices found for {{ $student->first_name }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $invoices->links() }}
@endsection
