@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Receipts</h1>
            <p class="text-xs text-slate-400 mt-1">Non-student sales and income receipts.</p>
        </div>
        <a href="{{ route('admin.receipts.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">New Receipt</a>
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

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Number</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Customer</th>
                        <th class="px-4 py-3 text-left font-medium">Method</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($receipts as $receipt)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">
                                <a href="{{ route('admin.receipts.show', $receipt) }}" class="text-indigo-400 hover:text-indigo-300">
                                    {{ $receipt->receipt_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $receipt->receipt_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3">{{ $receipt->customer?->name ?? $receipt->customer_name ?? 'Walk-in' }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('_',' ', $receipt->payment_method) }}</td>
                            <td class="px-4 py-3 text-right font-mono">${{ number_format($receipt->grand_total, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($receipt->journalBatch && $receipt->journalBatch->status === 'posted')
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-emerald-900/50 text-emerald-300 border border-emerald-800/50">
                                        Posted
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-900/50 text-amber-300 border border-amber-800/50">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.receipts.show', $receipt) }}" class="p-1 text-slate-400 hover:text-white" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    
                                    <a href="{{ route('admin.receipts.print', $receipt) }}" class="p-1 text-slate-400 hover:text-white" title="Print" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                    
                                    @if(!$receipt->journalBatch || $receipt->journalBatch->status !== 'posted')
                                        <a href="{{ route('admin.receipts.edit', $receipt) }}" class="p-1 text-slate-400 hover:text-white" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        
                                        <a href="{{ route('admin.receipts.duplicate', $receipt) }}" class="p-1 text-slate-400 hover:text-white" title="Duplicate">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7H5a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-3M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-3" />
                                            </svg>
                                        </a>
                                        
                                        <form action="{{ route('admin.receipts.destroy', $receipt) }}" method="POST" class="inline" onsubmit="return confirm('Delete this receipt?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-red-400 hover:text-red-300" title="Delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <div>No receipts found.</div>
                                    <a href="{{ route('admin.receipts.create') }}" class="text-indigo-400 hover:text-indigo-300">Create your first receipt</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-800/50">
            {{ $receipts->links() }}
        </div>
    </div>
@endsection

