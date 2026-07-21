@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">{{ $paymentMethod->name }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $paymentMethod->provider }} - {{ $paymentMethod->type_label }}</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.payment-methods.edit', $paymentMethod) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Edit</a>
            <a href="{{ route('admin.payment-methods.index') }}" class="rounded-full bg-slate-600 px-4 py-2 text-xs font-medium text-white hover:bg-slate-500 transition">Back to List</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Status</div>
            <div class="text-lg font-semibold text-slate-100">
                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $paymentMethod->is_active ? 'bg-emerald-900/50 text-emerald-300 border border-emerald-800/50' : 'bg-red-900/50 text-red-300 border border-red-800/50' }}">
                    {{ $paymentMethod->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Transaction Fee</div>
            <div class="text-lg font-semibold text-slate-100">
                @if($paymentMethod->transaction_fee_percentage > 0)
                    {{ $paymentMethod->transaction_fee_percentage }}%
                @endif
                @if($paymentMethod->fixed_transaction_fee > 0)
                    + ${{ number_format($paymentMethod->fixed_transaction_fee, 2) }}
                @endif
                @if($paymentMethod->transaction_fee_percentage == 0 && $paymentMethod->fixed_transaction_fee == 0)
                    No Fee
                @endif
            </div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Currency</div>
            <div class="text-lg font-semibold text-slate-100">{{ $paymentMethod->currency }}</div>
        </div>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-100 mb-4">Payment Method Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Method Name</div>
                        <div class="text-sm text-slate-200">{{ $paymentMethod->name }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Code</div>
                        <div class="text-sm text-slate-200 font-mono">{{ $paymentMethod->code }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Type</div>
                        <div class="text-sm text-slate-200">{{ $paymentMethod->type_label }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Provider</div>
                        <div class="text-sm text-slate-200">{{ $paymentMethod->provider }}</div>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Currency</div>
                        <div class="text-sm text-slate-200">{{ $paymentMethod->currency }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Minimum Amount</div>
                        <div class="text-sm text-slate-200">${{ number_format($paymentMethod->minimum_amount, 2) }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Maximum Amount</div>
                        <div class="text-sm text-slate-200">
                            @if($paymentMethod->maximum_amount)
                                ${{ number_format($paymentMethod->maximum_amount, 2) }}
                            @else
                                No Limit
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Created</div>
                        <div class="text-sm text-slate-200">{{ $paymentMethod->created_at->format('M j, Y g:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        @if($paymentMethod->description)
            <div class="mt-6 pt-6 border-t border-slate-800">
                <div class="text-xs text-slate-400 mb-1">Description</div>
                <div class="text-sm text-slate-200">{{ $paymentMethod->description }}</div>
            </div>
        @endif
    </div>

    @if($paymentMethod->payments->count() > 0)
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-slate-100 mb-4">Recent Payments ({{ $paymentMethod->payments->count() }} total)</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-950/60 text-slate-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Payment ID</th>
                            <th class="px-4 py-3 text-left font-medium">Invoice</th>
                            <th class="px-4 py-3 text-left font-medium">Student</th>
                            <th class="px-4 py-3 text-right font-medium">Amount</th>
                            <th class="px-4 py-3 text-right font-medium">Fee</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($paymentMethod->payments as $payment)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-4 py-3 font-mono">{{ $payment->reference }}</td>
                                <td class="px-4 py-3">
                                    @if($payment->invoice)
                                        <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="text-indigo-400 hover:text-indigo-300">
                                            #{{ $payment->invoice->invoice_number }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($payment->invoice && $payment->invoice->student)
                                        {{ $payment->invoice->student->full_name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono">${{ number_format($payment->amount, 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-400">${{ number_format($payment->transaction_fee, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $payment->status === 'completed' ? 'bg-emerald-900/50 text-emerald-300 border border-emerald-800/50' : 'bg-amber-900/50 text-amber-300 border border-amber-800/50' }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $payment->created_at->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($paymentMethod->payments->count() >= 10)
                <div class="mt-4 text-center">
                    <a href="#" class="text-indigo-400 hover:text-indigo-300 text-xs">View all payments</a>
                </div>
            @endif
        </div>
    @else
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
            <div class="text-center py-8 text-slate-500">
                <div class="flex flex-col items-center justify-center space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <div>No payments have been made using this payment method yet.</div>
                </div>
            </div>
        </div>
    @endif
@endsection
