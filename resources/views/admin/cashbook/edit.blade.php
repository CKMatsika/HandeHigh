@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Cashbook Transaction</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $cashbook->description }}</p>
        </div>
        <a href="{{ route('admin.cashbook.show', $cashbook) }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.cashbook.update', $cashbook) }}" class="space-y-4 text-sm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Account</label>
                    <select name="bank_account_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" {{ $cashbook->bank_account_id == $account->id ? 'selected' : '' }}>{{ $account->account_name }} ({{ $account->account_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Transaction Type</label>
                    <select name="transaction_type" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type }}" {{ old('transaction_type', $cashbook->transaction_type) === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Category</label>
                    <select name="category" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category', $cashbook->category) === $cat ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Amount</label>
                    <input type="number" step="0.01" name="amount" value="{{ old('amount', $cashbook->amount) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
            </div>

            <div>
                <label class="block text-slate-400 text-xs mb-1">Description</label>
                <input name="description" value="{{ old('description', $cashbook->description) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Transaction Date</label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', $cashbook->transaction_date?->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method }}" {{ old('payment_method', $cashbook->payment_method) === $method ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Reference #</label>
                    <input name="reference_number" value="{{ old('reference_number', $cashbook->reference_number) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
            </div>

            <div>
                <label class="block text-slate-400 text-xs mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">{{ old('notes', $cashbook->notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.cashbook.show', $cashbook) }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Update</button>
            </div>
        </form>
    </div>
@endsection
