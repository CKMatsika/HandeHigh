@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Add Payment Method</h1>
            <p class="text-xs text-slate-400 mt-1">Create a new payment method option</p>
        </div>
        <a href="{{ route('admin.payment-methods.index') }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Cancel</a>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
        <form action="{{ route('admin.payment-methods.store') }}" method="POST">
            @csrf
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-medium text-slate-300 mb-2">Payment Method Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="e.g., EcoCash">
                        @error('name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="code" class="block text-xs font-medium text-slate-300 mb-2">Code *</label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="e.g., ecocash">
                        @error('code')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="block text-xs font-medium text-slate-300 mb-2">Payment Type *</label>
                        <select id="type" name="type" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Select payment type</option>
                            <option value="mobile_money" {{ old('type') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                            <option value="card" {{ old('type') == 'card' ? 'selected' : '' }}>Card Payment</option>
                            <option value="bank_transfer" {{ old('type') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="online" {{ old('type') == 'online' ? 'selected' : '' }}>Online Payment</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="provider" class="block text-xs font-medium text-slate-300 mb-2">Provider *</label>
                        <input type="text" id="provider" name="provider" value="{{ old('provider') }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="e.g., Econet Wireless">
                        @error('provider')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="currency" class="block text-xs font-medium text-slate-300 mb-2">Currency *</label>
                        <select id="currency" name="currency" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="ZWL" {{ old('currency') == 'ZWL' ? 'selected' : '' }}>ZWL - Zimbabwe Dollar</option>
                            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                        </select>
                        @error('currency')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}
                                   class="rounded border-slate-700 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0">
                            <span class="text-xs text-slate-300">Active</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="Describe this payment method...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-700 pt-4">
                    <h3 class="text-sm font-medium text-slate-200 mb-4">Transaction Fees</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="transaction_fee_percentage" class="block text-xs font-medium text-slate-300 mb-2">Percentage Fee (%)</label>
                            <input type="number" id="transaction_fee_percentage" name="transaction_fee_percentage" 
                                   value="{{ old('transaction_fee_percentage', 0) }}" required min="0" max="100" step="0.01"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="0.00">
                            @error('transaction_fee_percentage')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="fixed_transaction_fee" class="block text-xs font-medium text-slate-300 mb-2">Fixed Fee ($)</label>
                            <input type="number" id="fixed_transaction_fee" name="fixed_transaction_fee" 
                                   value="{{ old('fixed_transaction_fee', 0) }}" required min="0" step="0.01"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="0.00">
                            @error('fixed_transaction_fee')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-700 pt-4">
                    <h3 class="text-sm font-medium text-slate-200 mb-4">Transaction Limits</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="minimum_amount" class="block text-xs font-medium text-slate-300 mb-2">Minimum Amount ($)</label>
                            <input type="number" id="minimum_amount" name="minimum_amount" 
                                   value="{{ old('minimum_amount', 0) }}" required min="0" step="0.01"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="0.00">
                            @error('minimum_amount')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="maximum_amount" class="block text-xs font-medium text-slate-300 mb-2">Maximum Amount ($)</label>
                            <input type="number" id="maximum_amount" name="maximum_amount" 
                                   value="{{ old('maximum_amount') }}" min="0" step="0.01"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Leave empty for no limit">
                            @error('maximum_amount')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <a href="{{ route('admin.payment-methods.index') }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Cancel</a>
                    <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Create Payment Method</button>
                </div>
            </div>
        </form>
    </div>
@endsection
