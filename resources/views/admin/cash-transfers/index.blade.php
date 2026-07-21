@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Cash Transfers</h1>
            <p class="text-gray-600 mt-1">Manage transfers between cash and bank accounts</p>
        </div>
        <a href="{{ route('admin.cash-transfers.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
            New Transfer
        </a>
    </div>

    <!-- Account Balances Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Cash Accounts -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Cash Accounts</h3>
            <div class="space-y-3">
                @foreach($cashAccounts as $account)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div>
                            <div class="font-medium text-gray-800">{{ $account->name }}</div>
                            <div class="text-sm text-gray-500">{{ $account->code }}</div>
                        </div>
                        <div class="text-lg font-semibold text-green-600">
                            ${{ number_format($account->balance ?? 0, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Bank Accounts -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Bank Accounts</h3>
            <div class="space-y-3">
                @foreach($bankAccounts as $account)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div>
                            <div class="font-medium text-gray-800">{{ $account->name }}</div>
                            <div class="text-sm text-gray-500">{{ $account->code }}</div>
                        </div>
                        <div class="text-lg font-semibold text-blue-600">
                            ${{ number_format($account->balance ?? 0, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Transfers -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Recent Transfers</h3>
        </div>
        <div class="p-6">
            @if($recentTransfers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentTransfers as $transfer)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $transfer->transaction_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $transfer->description }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $transfer->account->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $transfer->transaction_type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $transfer->transaction_type === 'income' ? 'Transfer In' : 'Transfer Out' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium {{ $transfer->transaction_type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transfer->transaction_type === 'income' ? '+' : '-' }}${{ number_format($transfer->amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        ${{ number_format($transfer->balance_after, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-500">No transfers found</div>
                    <a href="{{ route('admin.cash-transfers.create') }}" class="text-blue-500 hover:text-blue-600 mt-2 inline-block">
                        Create your first transfer
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
