@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Cashbook Transaction</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $cashbook->description }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.cashbook.edit', $cashbook) }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200 hover:bg-slate-800 transition">Edit</a>
            <a href="{{ route('admin.cashbook.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-4">Transaction Details</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400">Type</span>
                        <p class="mt-1">
                            @if($cashbook->transaction_type === 'income')
                                <span class="px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-300">Income</span>
                            @elseif($cashbook->transaction_type === 'expense')
                                <span class="px-2 py-1 rounded-full bg-red-500/20 text-red-300">Expense</span>
                            @else
                                <span class="px-2 py-1 rounded-full bg-blue-500/20 text-blue-300">Transfer</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-400">Category</span>
                        <p class="text-slate-100 mt-1">{{ ucfirst($cashbook->category) }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Amount</span>
                        <p class="text-lg font-bold text-slate-50 mt-1">{{ $cashbook->formatted_amount }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Account</span>
                        <p class="text-slate-100 mt-1">{{ $cashbook->account->name ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Date</span>
                        <p class="text-slate-100 mt-1">{{ $cashbook->transaction_date?->format('M d, Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Payment Method</span>
                        <p class="text-slate-100 mt-1">{{ ucfirst(str_replace('_', ' ', $cashbook->payment_method ?? '')) }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Reference</span>
                        <p class="text-slate-100 font-mono mt-1">{{ $cashbook->reference_number ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Balance After</span>
                        <p class="text-slate-100 font-medium mt-1">{{ number_format($cashbook->balance_after ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <span class="text-slate-400">Created By</span>
                        <p class="text-slate-100 mt-1">{{ $cashbook->createdBy->name ?? '-' }}</p>
                    </div>
                </div>

                @if($cashbook->notes)
                    <div class="mt-4 pt-4 border-t border-slate-800">
                        <span class="text-slate-400 text-xs">Notes</span>
                        <p class="text-slate-100 text-xs mt-1">{{ $cashbook->notes }}</p>
                    </div>
                @endif

                @if($cashbook->relatedInvoice)
                    <div class="mt-4 pt-4 border-t border-slate-800">
                        <span class="text-slate-400 text-xs">Related Invoice</span>
                        <p class="text-xs mt-1">
                            <a href="{{ route('admin.invoices.show', $cashbook->relatedInvoice) }}" class="text-indigo-400 hover:text-indigo-300">
                                {{ $cashbook->relatedInvoice->number }}
                            </a>
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-3">Actions</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.cashbook.edit', $cashbook) }}" class="block w-full text-center rounded-lg bg-slate-800 px-4 py-2 text-xs text-slate-200 hover:bg-slate-700 transition">Edit</a>
                    <form method="POST" action="{{ route('admin.cashbook.destroy', $cashbook) }}" onsubmit="return confirm('Delete this transaction?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full rounded-lg bg-red-500/20 px-4 py-2 text-xs text-red-300 hover:bg-red-500/30 transition">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
