@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Receipt {{ $receipt->receipt_number }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $receipt->receipt_date->format('M j, Y') }} - {{ ucfirst($receipt->type) }}</p>
        </div>
        <div class="flex items-center space-x-2">
            @if(!$receipt->journalBatch || $receipt->journalBatch->status !== 'posted')
                <a href="{{ route('admin.receipts.edit', $receipt) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Edit</a>
                <form action="{{ route('admin.receipts.destroy', $receipt) }}" method="POST" class="inline" onsubmit="return confirm('Delete this receipt?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-full bg-red-500 px-4 py-2 text-xs font-medium text-white hover:bg-red-600 transition">Delete</button>
                </form>
            @endif
            <a href="{{ route('admin.receipts.print', $receipt) }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition" target="_blank">Print</a>
            <a href="{{ route('admin.receipts.duplicate', $receipt) }}" class="rounded-full bg-purple-500 px-4 py-2 text-xs font-medium text-white hover:bg-purple-600 transition">Duplicate</a>
            <a href="{{ route('admin.receipts.index') }}" class="rounded-full bg-slate-600 px-4 py-2 text-xs font-medium text-white hover:bg-slate-500 transition">Back to List</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-200 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-200 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Total Amount</div>
            <div class="text-lg font-semibold text-slate-100">${{ number_format($receipt->total_amount, 2) }}</div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Tax Amount</div>
            <div class="text-lg font-semibold text-slate-100">${{ number_format($receipt->tax_amount, 2) }}</div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Grand Total</div>
            <div class="text-lg font-semibold text-emerald-400">${{ number_format($receipt->grand_total, 2) }}</div>
        </div>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-100 mb-4">Receipt Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Receipt Number</div>
                        <div class="text-sm text-slate-200 font-mono">{{ $receipt->receipt_number }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Date</div>
                        <div class="text-sm text-slate-200">{{ $receipt->receipt_date->format('M j, Y') }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Type</div>
                        <div class="text-sm text-slate-200">{{ ucfirst($receipt->type) }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Payment Method</div>
                        <div class="text-sm text-slate-200">{{ ucfirst(str_replace('_', ' ', $receipt->payment_method)) }}</div>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Customer</div>
                        <div class="text-sm text-slate-200">
                            @if($receipt->customer)
                                {{ $receipt->customer->name }}
                            @elseif($receipt->customer_name)
                                {{ $receipt->customer_name }}
                            @else
                                Walk-in Customer
                            @endif
                        </div>
                    </div>
                    
                    @if($receipt->reference)
                        <div>
                            <div class="text-xs text-slate-400 mb-1">Reference</div>
                            <div class="text-sm text-slate-200">{{ $receipt->reference }}</div>
                        </div>
                    @endif
                    
                    @if($receipt->description)
                        <div>
                            <div class="text-xs text-slate-400 mb-1">Description</div>
                            <div class="text-sm text-slate-200">{{ $receipt->description }}</div>
                        </div>
                    @endif
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Created By</div>
                        <div class="text-sm text-slate-200">{{ $receipt->creator?->name ?? 'System' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-100 mb-4">Receipt Items</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Description</th>
                        <th class="px-4 py-3 text-left font-medium">Category</th>
                        <th class="px-4 py-3 text-right font-medium">Quantity</th>
                        <th class="px-4 py-3 text-right font-medium">Unit Price</th>
                        <th class="px-4 py-3 text-right font-medium">Tax Rate</th>
                        <th class="px-4 py-3 text-right font-medium">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($receipt->items as $item)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $item->description }}</td>
                            <td class="px-4 py-3">{{ $item->category ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($item->tax_rate > 0)
                                    {{ $item->tax_rate }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono">${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-950/60">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-medium">Subtotal:</td>
                        <td class="px-4 py-3 text-right font-mono">${{ number_format($receipt->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-medium">Tax:</td>
                        <td class="px-4 py-3 text-right font-mono">${{ number_format($receipt->tax_amount, 2) }}</td>
                    </tr>
                    <tr class="border-t border-slate-700">
                        <td colspan="5" class="px-4 py-3 text-right font-semibold text-emerald-400">Grand Total:</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-emerald-400">${{ number_format($receipt->grand_total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($receipt->journalBatch)
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-slate-100 mb-4">Accounting Entries</h2>
            
            <div class="mb-4">
                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $receipt->journalBatch->status === 'posted' ? 'bg-emerald-900/50 text-emerald-300 border border-emerald-800/50' : 'bg-amber-900/50 text-amber-300 border border-amber-800/50' }}">
                    {{ ucfirst($receipt->journalBatch->status) }}
                </span>
                <span class="ml-2 text-xs text-slate-400">
                    Batch: {{ $receipt->journalBatch->batch_number }}
                </span>
            </div>
            
            @if($receipt->journalBatch->entries->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-950/60 text-slate-300">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Account</th>
                                <th class="px-4 py-3 text-left font-medium">Type</th>
                                <th class="px-4 py-3 text-right font-medium">Amount</th>
                                <th class="px-4 py-3 text-left font-medium">Memo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($receipt->journalBatch->entries as $entry)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="px-4 py-3">{{ $entry->account?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $entry->entry_type === 'debit' ? 'bg-red-900/50 text-red-300 border border-red-800/50' : 'bg-green-900/50 text-green-300 border border-green-800/50' }}">
                                            {{ ucfirst($entry->entry_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">${{ number_format($entry->amount, 2) }}</td>
                                    <td class="px-4 py-3">{{ $entry->memo }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
@endsection
