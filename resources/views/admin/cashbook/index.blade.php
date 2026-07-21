@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Cashbook</h1>
            <p class="text-xs text-slate-400 mt-1">Track all financial transactions and bank account activities.</p>
        </div>
        <a href="{{ route('admin.cashbook.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Transaction
        </a>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <select name="transaction_type" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    @foreach($transactionTypes as $type)
                        <option value="{{ $type }}" {{ request('transaction_type') == $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="account_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Accounts</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->code }} - {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="category" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $category)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="From">
            </div>
            <div>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="To">
            </div>
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-4 py-2 rounded-lg transition-colors">
                    Filter
                </button>
                <a href="{{ route('admin.cashbook.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-4 py-2 rounded-lg transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-300">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-300">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-3 text-xs text-slate-300">
                                {{ $transaction->transaction_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                <div>
                                    <div class="font-medium">{{ $transaction->description }}</div>
                                    @if($transaction->reference_number)
                                        <div class="text-slate-400">Ref: {{ $transaction->reference_number }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                <span class="inline-flex items-center rounded-full bg-slate-800 px-2 py-1 text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $transaction->category)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                {{ $transaction->bankAccount->account_name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $transaction->transaction_type === 'income' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ ucfirst($transaction->transaction_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-right font-medium">
                                <span class="{{ $transaction->transaction_type === 'income' ? 'text-emerald-500' : 'text-red-500' }}">
                                    {{ $transaction->transaction_type === 'income' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-right text-slate-300">
                                ${{ number_format($transaction->balance_after, 2) }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div class="flex gap-1">
                                    <a href="{{ route('admin.cashbook.show', $transaction) }}" class="text-blue-400 hover:text-blue-300">
                                        View
                                    </a>
                                    <a href="{{ route('admin.cashbook.edit', $transaction) }}" class="text-amber-400 hover:text-amber-300">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.cashbook.destroy', $transaction) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-300"
                                                onclick="return confirm('Are you sure you want to delete this transaction?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                                <div class="mb-4">
                                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-slate-50 mb-2">No transactions found</h3>
                                <p class="text-sm text-slate-400 mb-4">Start by adding your first transaction.</p>
                                <a href="{{ route('admin.cashbook.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                                    Add Transaction
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($transactions->hasPages())
            <div class="px-4 py-3 border-t border-slate-800">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
