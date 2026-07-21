@extends('layouts.app')

@section('content')
    @php
        $totalPaid = (float) ($invoice->allocations?->sum('amount') ?? 0);
        $payments = $invoice->allocations
            ?->loadMissing('payment')
            ?->map(fn($a) => $a->payment)
            ?->filter();
    @endphp

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Invoice</h1>
            <p class="text-xs text-slate-400 mt-1">View invoice items, payments, and balances.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($invoice->status !== 'cancelled')
                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Edit</a>
                <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this invoice?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-full bg-amber-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-amber-600 transition">Cancel</button>
                </form>
            @endif
            <a href="{{ route('admin.invoices.print', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Print</a>
            @if($invoice->balance > 0 && $invoice->status !== 'cancelled')
                <a href="{{ route('admin.payments.create', $invoice) }}" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">Record payment</a>
            @endif
            @if($invoice->status === 'cancelled' && $invoice->balance === $invoice->total_amount)
                <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this invoice?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center rounded-full bg-red-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-red-600 transition">Delete</button>
                </form>
            @endif
            <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Invoice</h2>
            <div class="text-xs text-slate-200 space-y-1">
                <div><span class="text-slate-400">Number:</span> {{ $invoice->number }}</div>
                <div><span class="text-slate-400">Issued:</span> {{ $invoice->issued_at?->format('Y-m-d') }}</div>
                <div><span class="text-slate-400">Due:</span> {{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</div>
                <div><span class="text-slate-400">Year/Term:</span> {{ $invoice->academic_year }} · {{ $invoice->term }}</div>
                <div><span class="text-slate-400">Status:</span> <span class="capitalize">{{ $invoice->status }}</span></div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Student</h2>
            <div class="text-xs text-slate-200 space-y-1">
                <div><span class="text-slate-400">Name:</span> {{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</div>
                <div><span class="text-slate-400">Grade/Class:</span> {{ $invoice->student?->grade }} {{ $invoice->student?->class_name }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Amounts</h2>
            <div class="text-xs text-slate-200 space-y-1">
                <div><span class="text-slate-400">Total:</span> {{ number_format($invoice->total_amount, 2) }}</div>
                <div><span class="text-slate-400">Paid:</span> {{ number_format($totalPaid, 2) }}</div>
                <div><span class="text-slate-400">Balance:</span> {{ number_format($invoice->balance, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mt-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Invoice items</h2>
        @if($invoice->items->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Description</th>
                        <th class="text-left py-2 font-medium">Category</th>
                        <th class="text-right py-2 font-medium">Qty</th>
                        <th class="text-right py-2 font-medium">Unit</th>
                        <th class="text-right py-2 font-medium">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">{{ $item->description }}</td>
                            <td class="py-2 align-middle capitalize">{{ $item->category ?: '—' }}</td>
                            <td class="py-2 align-middle text-right">{{ $item->quantity }}</td>
                            <td class="py-2 align-middle text-right">{{ number_format($item->unit_amount, 2) }}</td>
                            <td class="py-2 align-middle text-right font-medium">{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-xs text-slate-400">No line items found.</p>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mt-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Payments</h2>
        @if($invoice->allocations->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Date</th>
                        <th class="text-left py-2 font-medium">Method</th>
                        <th class="text-left py-2 font-medium">Reference</th>
                        <th class="text-right py-2 font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->allocations as $allocation)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">{{ $allocation->payment?->paid_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-2 align-middle">{{ $allocation->payment?->method ? ucfirst(str_replace('_', ' ', $allocation->payment->method)) : '—' }}</td>
                            <td class="py-2 align-middle">{{ $allocation->payment?->reference ?: '—' }}</td>
                            <td class="py-2 align-middle text-right font-medium">{{ number_format($allocation->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-xs text-slate-400">No payments recorded yet.</p>
        @endif
    </div>
@endsection
