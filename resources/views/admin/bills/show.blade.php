@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Bill {{ $bill->bill_number }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $bill->vendor->name ?? 'Unknown Vendor' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.bills.edit', $bill) }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200 hover:bg-slate-800 transition">Edit</a>
            <a href="{{ route('admin.bills.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-4">Bill Details</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400">Bill Number</span>
                        <p class="text-slate-100 font-mono mt-1">{{ $bill->bill_number }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Vendor Bill #</span>
                        <p class="text-slate-100 mt-1">{{ $bill->vendor_bill_number ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Bill Date</span>
                        <p class="text-slate-100 mt-1">{{ $bill->bill_date?->format('M d, Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Due Date</span>
                        <p class="text-slate-100 mt-1">{{ $bill->due_date?->format('M d, Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Description</span>
                        <p class="text-slate-100 mt-1">{{ $bill->description ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Notes</span>
                        <p class="text-slate-100 mt-1">{{ $bill->notes ?? '-' }}</p>
                    </div>
                </div>
            </div>

            @if($bill->items->count())
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-800">
                        <h2 class="text-sm font-medium text-slate-50">Line Items</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-950/60 text-slate-300">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Description</th>
                                    <th class="px-4 py-3 text-left font-medium">Category</th>
                                    <th class="px-4 py-3 text-right font-medium">Qty</th>
                                    <th class="px-4 py-3 text-right font-medium">Unit Price</th>
                                    <th class="px-4 py-3 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach($bill->items as $item)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="px-4 py-3">{{ $item->description }}</td>
                                        <td class="px-4 py-3 text-slate-400">{{ $item->category ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-medium">{{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($bill->payments->count())
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-800">
                        <h2 class="text-sm font-medium text-slate-50">Payment History</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-950/60 text-slate-300">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Date</th>
                                    <th class="px-4 py-3 text-right font-medium">Amount</th>
                                    <th class="px-4 py-3 text-left font-medium">Method</th>
                                    <th class="px-4 py-3 text-left font-medium">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach($bill->payments as $payment)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="px-4 py-3">{{ $payment->payment_date?->format('M d, Y') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right text-emerald-400 font-medium">{{ number_format($payment->amount, 2) }}</td>
                                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                        <td class="px-4 py-3 font-mono text-slate-400">{{ $payment->reference ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-4">Summary</h2>
                <div class="space-y-3 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Status</span>
                        @if($bill->status === 'paid')
                            <span class="px-2 py-1 rounded-full text-xs bg-emerald-500/20 text-emerald-300">Paid</span>
                        @elseif($bill->status === 'partial')
                            <span class="px-2 py-1 rounded-full text-xs bg-amber-500/20 text-amber-300">Partial</span>
                        @elseif($bill->status === 'overdue')
                            <span class="px-2 py-1 rounded-full text-xs bg-red-500/20 text-red-300">Overdue</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs bg-slate-500/20 text-slate-300">Pending</span>
                        @endif
                    </div>
                    <div class="border-t border-slate-800"></div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Amount</span>
                        <span class="text-slate-100 font-medium">{{ number_format($bill->total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Paid Amount</span>
                        <span class="text-emerald-400">{{ number_format($bill->paid_amount, 2) }}</span>
                    </div>
                    <div class="border-t border-slate-800"></div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Balance Due</span>
                        <span class="text-lg font-bold {{ $bill->balance > 0 ? 'text-amber-400' : 'text-emerald-400' }}">{{ number_format($bill->balance, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-3">Actions</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.bills.edit', $bill) }}" class="block w-full text-center rounded-lg bg-slate-800 px-4 py-2 text-xs text-slate-200 hover:bg-slate-700 transition">Edit Bill</a>
                </div>
            </div>
        </div>
    </div>
@endsection
