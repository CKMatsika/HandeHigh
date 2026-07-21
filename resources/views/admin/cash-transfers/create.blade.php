@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Create Cash Transfer</h1>
            <p class="text-gray-600 mt-1">Transfer funds between cash and bank accounts</p>
        </div>
        <a href="{{ route('admin.cash-transfers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
            Back to Transfers
        </a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form method="POST" action="{{ route('admin.cash-transfers.store') }}">
            @csrf
            <div class="px-6 py-4 space-y-6">
                <!-- From Account -->
                <div>
                    <label for="from_account" class="block text-sm font-medium text-gray-700 mb-2">
                        Transfer From (Cash Account)
                    </label>
                    <select id="from_account" name="from_account" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select cash account to transfer from</option>
                        @foreach($cashAccounts as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('from_account')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- To Account -->
                <div>
                    <label for="to_account" class="block text-sm font-medium text-gray-700 mb-2">
                        Transfer To (Bank Account)
                    </label>
                    <select id="to_account" name="to_account" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select bank account to transfer to</option>
                        @foreach($bankAccounts as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('to_account')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Amount
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required 
                               class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                               placeholder="0.00">
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <input type="text" id="description" name="description" required maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., Daily cash deposit, Weekly banking, etc.">
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Transfer Date -->
                <div>
                    <label for="transfer_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Transfer Date
                    </label>
                    <input type="date" id="transfer_date" name="transfer_date" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           value="{{ now()->format('Y-m-d') }}">
                    @error('transfer_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Accounting Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-800 mb-2">Accounting Information</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• This will create a double-entry accounting transaction</li>
                        <li>• Source account will be credited (decreased)</li>
                        <li>• Destination account will be debited (increased)</li>
                        <li>• Both cashbook and general ledger will be updated</li>
                    </ul>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                <a href="{{ route('admin.cash-transfers.index') }}" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                    Complete Transfer
                </button>
            </div>
        </form>
    </div>
@endsection
