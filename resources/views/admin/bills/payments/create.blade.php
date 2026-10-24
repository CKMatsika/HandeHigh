@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Record Bill Payment</h1>
            <p class="text-xs text-slate-400 mt-1">
                Bill #{{ $bill->bill_number }} &bull; {{ $bill->vendor->name ?? $bill->supplier_name ?? 'Vendor' }}
            </p>
        </div>
        <a href="{{ route('admin.bills.show', $bill) }}" class="text-xs text-slate-300 hover:text-white">Back to Bill</a>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs text-rose-300">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <form method="POST" action="{{ route('admin.bills.payments.store', $bill) }}" class="space-y-4 text-sm">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Payment Amount (USD)</label>
                        <input type="number" step="0.01" max="{{ $bill->balance }}" name="amount" value="{{ old('amount', $bill->balance) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 font-semibold" required />
                        <span class="text-[11px] text-slate-500">Remaining Balance: USD {{ number_format($bill->balance, 2) }}</span>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Paying Bank / Cash Account (General Ledger)</label>
                        <select name="account_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                            <option value="">-- Auto-select from payment method --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->category) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                            <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer (EFT/RTGS)</option>
                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="check" {{ old('payment_method') === 'check' ? 'selected' : '' }}>Cheque</option>
                            <option value="mobile_money" {{ old('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money (EcoCash/OneMoney)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Reference / Cheque Number</label>
                        <input name="reference" value="{{ old('reference') }}" placeholder="e.g. CABS-EFT-99482" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs mb-1">Legacy Bank Account (Optional)</label>
                        <select name="bank_account_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                            <option value="">-- None --</option>
                            @foreach($bankAccounts as $bank)
                                <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                    {{ $bank->bank_name }} - {{ $bank->account_number }} ({{ $bank->account_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 text-xs mb-1">Payment Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" placeholder="Optional payment justification or approval details">{{ old('notes') }}</textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-800">
                    <a href="{{ route('admin.bills.show', $bill) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-xs text-slate-300 hover:bg-slate-800">Cancel</a>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-xs font-semibold text-white hover:bg-emerald-500 shadow-lg shadow-emerald-900/30">
                        Confirm & Post Payment to GL & Cashbook
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 space-y-3 text-xs">
                <h3 class="font-semibold text-slate-200">Bill Summary</h3>
                <div class="flex justify-between py-1 border-b border-slate-800/60 text-slate-400">
                    <span>Total Amount:</span>
                    <span class="text-slate-100 font-medium">USD {{ number_format($bill->total_amount, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800/60 text-slate-400">
                    <span>Paid to Date:</span>
                    <span class="text-emerald-400 font-medium">USD {{ number_format($bill->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800/60 text-slate-400">
                    <span>Outstanding Balance:</span>
                    <span class="text-rose-400 font-semibold">USD {{ number_format($bill->balance, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 text-slate-400">
                    <span>Current Status:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $bill->status === 'paid' ? 'bg-emerald-900/40 text-emerald-300' : 'bg-amber-900/40 text-amber-300' }}">
                        {{ strtoupper($bill->status) }}
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 text-xs text-slate-400 space-y-2">
                <div class="font-semibold text-slate-200 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Automatic Accounting Integration
                </div>
                <p>
                    Recording this payment automatically:
                </p>
                <ul class="list-disc list-inside space-y-1 text-slate-300">
                    <li>Debits Accounts Payable (3100)</li>
                    <li>Credits selected Bank / Cash Account</li>
                    <li>Creates an Expense entry in Cashbook</li>
                    <li>Synchronises CashbookTransaction for Bank Rec</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
