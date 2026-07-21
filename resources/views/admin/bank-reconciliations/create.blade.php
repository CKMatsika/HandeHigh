@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">New Bank Reconciliation</h1>
            <p class="text-xs text-slate-400 mt-1">Record balances and differences.</p>
        </div>
        <a href="{{ route('admin.bank-reconciliations.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.bank-reconciliations.store') }}" class="space-y-4 text-sm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Bank Account</label>
                    <select name="bank_account_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        <option value="">Select account</option>
                        @foreach($bankAccounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_name }} ({{ $acct->account_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Reconciliation Date</label>
                    <input type="date" name="reconciliation_date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Book Balance</label>
                    <input type="number" step="0.01" name="book_balance" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Bank Balance</label>
                    <input type="number" step="0.01" name="bank_balance" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
            </div>

            <div>
                <label class="block text-slate-400 text-xs mb-1">Notes</label>
                <textarea name="notes" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" rows="3"></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.bank-reconciliations.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Save</button>
            </div>
        </form>
    </div>
@endsection

