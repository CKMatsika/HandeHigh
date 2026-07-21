@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Payment Methods</h1>
            <p class="text-xs text-slate-400 mt-1">Manage payment options for Zimbabwe and international transactions</p>
        </div>
        <a href="{{ route('admin.payment-methods.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Add Payment Method</a>
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
                        <th class="px-4 py-3 text-left font-medium">Payment Method</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Provider</th>
                        <th class="px-4 py-3 text-left font-medium">Currency</th>
                        <th class="px-4 py-3 text-right font-medium">Transaction Fee</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($paymentMethods as $method)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-900/50 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-100">{{ $method->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $method->code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300">
                                    {{ $method->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $method->provider }}</td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs">{{ $method->currency }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="text-xs">
                                    @if($method->transaction_fee_percentage > 0)
                                        <div>{{ $method->transaction_fee_percentage }}%</div>
                                    @endif
                                    @if($method->fixed_transaction_fee > 0)
                                        <div class="text-slate-400">+ ${{ number_format($method->fixed_transaction_fee, 2) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $method->is_active ? 'bg-emerald-900/50 text-emerald-300 border border-emerald-800/50' : 'bg-red-900/50 text-red-300 border border-red-800/50' }}">
                                    {{ $method->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.payment-methods.show', $method) }}" class="p-1 text-slate-400 hover:text-white" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.payment-methods.edit', $method) }}" class="p-1 text-slate-400 hover:text-white" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.payment-methods.toggle', $method) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1 {{ $method->is_active ? 'text-amber-400 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300' }}" title="{{ $method->is_active ? 'Deactivate' : 'Activate' }}">
                                            @if($method->is_active)
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
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
                                    <div>No payment methods found.</div>
                                    <a href="{{ route('admin.payment-methods.create') }}" class="text-indigo-400 hover:text-indigo-300">Add your first payment method</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-800/50">
            {{ $paymentMethods->links() }}
        </div>
    </div>

    <div class="mt-8 bg-slate-900/50 border border-slate-800 rounded-xl p-4 text-sm text-slate-400">
        <h3 class="font-medium text-slate-200 mb-2">Payment Method Types</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">Mobile Money</div>
                <div class="text-slate-500 mt-1">EcoCash, OneMoney, Omari, Innbucks</div>
            </div>
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">Cards</div>
                <div class="text-slate-500 mt-1">Visa, Mastercard, American Express</div>
            </div>
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">Bank Transfer</div>
                <div class="text-slate-500 mt-1">Direct bank transfers</div>
            </div>
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">Online</div>
                <div class="text-slate-500 mt-1">PayPal, Stripe, etc.</div>
            </div>
        </div>
    </div>
@endsection
