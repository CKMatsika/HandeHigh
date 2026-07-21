@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Bank Account Details</h1>
            <p class="text-xs text-slate-400 mt-1">View bank account information and transactions.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.bank-accounts.index') }}" class="inline-flex items-center rounded-full bg-slate-700 px-4 py-1.5 text-xs font-medium text-white hover:bg-slate-600 transition">
                Back to Accounts
            </a>
            <a href="{{ route('admin.bank-accounts.edit', $bankAccount) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Edit Account
            </a>
        </div>
    </div>

    <!-- Account Details -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 mb-6">
        <h2 class="text-sm font-medium text-slate-50 mb-4">Account Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-slate-400 mb-1">Account Name</p>
                <p class="text-sm font-medium text-slate-50">{{ $bankAccount->name }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Account Number</p>
                <p class="text-sm font-medium text-slate-50">{{ $bankAccount->code }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Account Type</p>
                <p class="text-sm font-medium text-slate-50">{{ ucfirst($bankAccount->type) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Category</p>
                <p class="text-sm font-medium text-slate-50">{{ ucfirst($bankAccount->category) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Current Balance</p>
                <p class="text-sm font-medium text-slate-50">{{ \App\Services\CurrencyService::format($bankAccount->current_balance) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Status</p>
                <p class="text-sm font-medium {{ $bankAccount->is_active ? 'text-green-400' : 'text-red-400' }}">
                    {{ $bankAccount->is_active ? 'Active' : 'Inactive' }}
                </p>
            </div>
            @if($bankAccount->description)
            <div class="md:col-span-2 lg:col-span-3">
                <p class="text-xs text-slate-400 mb-1">Description</p>
                <p class="text-sm text-slate-50">{{ $bankAccount->description }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-medium text-slate-50">Recent Transactions</h2>
            <a href="{{ route('admin.cashbook.index', ['account_id' => $bankAccount->id]) }}" class="text-xs text-indigo-400 hover:text-indigo-300">
                View All Transactions
            </a>
        </div>
        
        @if($recentTransactions->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-700">
                        <th class="text-left text-xs font-medium text-slate-400 pb-3">Date</th>
                        <th class="text-left text-xs font-medium text-slate-400 pb-3">Description</th>
                        <th class="text-left text-xs font-medium text-slate-400 pb-3">Type</th>
                        <th class="text-right text-xs font-medium text-slate-400 pb-3">Amount</th>
                        <th class="text-right text-xs font-medium text-slate-400 pb-3">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTransactions as $transaction)
                    <tr class="border-b border-slate-800/50 last:border-b-0">
                        <td class="py-3 text-sm text-slate-300">{{ $transaction->transaction_date->format('M d, Y') }}</td>
                        <td class="py-3 text-sm text-slate-50">{{ $transaction->description }}</td>
                        <td class="py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $transaction->transaction_type === 'income' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                {{ ucfirst($transaction->transaction_type) }}
                            </span>
                        </td>
                        <td class="py-3 text-sm text-right {{ $transaction->transaction_type === 'income' ? 'text-green-400' : 'text-red-400' }}">
                            {{ $transaction->transaction_type === 'income' ? '+' : '-' }}{{ \App\Services\CurrencyService::format($transaction->amount) }}
                        </td>
                        <td class="py-3 text-sm text-right text-slate-300">
                            {{ \App\Services\CurrencyService::format($transaction->balance_after) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-8">
            <p class="text-sm text-slate-400">No transactions found for this account.</p>
        </div>
        @endif
    </div>
@endsection
