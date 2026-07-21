@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">New Interbank Transfer</h1>
            <p class="text-xs text-slate-400 mt-1">Record fund movement between bank accounts.</p>
        </div>
        <a href="{{ route('admin.interbank-transfers.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.interbank-transfers.store') }}" class="space-y-4 text-sm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Transfer Date</label>
                    <input type="date" name="transfer_date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">From Account</label>
                    <select name="from_bank_account_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        <option value="">Select account</option>
                        @foreach($bankAccounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->code }} - {{ $acct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">To Account</label>
                    <select name="to_bank_account_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        <option value="">Select account</option>
                        @foreach($bankAccounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->code }} - {{ $acct->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Reference</label>
                    <input name="reference" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Description</label>
                    <input name="description" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.interbank-transfers.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Save</button>
            </div>
        </form>
    </div>
@endsection

