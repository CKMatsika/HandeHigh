@extends('layouts.app')

@section('content')
    <h1 class="text-lg font-semibold text-slate-50 mb-4">Record payment</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-xs text-slate-100">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-2">Invoice</h2>
            <p class="text-[11px] text-slate-400 mb-1">{{ $invoice->number }}</p>
            <p class="text-[11px] text-slate-400 mb-1">Issued: {{ $invoice->issued_at?->format('Y-m-d') }}</p>
            <p class="text-[11px] text-slate-400 mb-1">Year / Term: {{ $invoice->academic_year }} · {{ $invoice->term }}</p>
            <p class="mt-2 text-xs text-slate-200">Total: {{ number_format($invoice->total_amount, 2) }}</p>
            <p class="text-xs text-slate-200">Current balance: {{ number_format($invoice->balance, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-2">Student</h2>
            <p class="text-xs text-slate-200 mb-1">{{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</p>
            <p class="text-[11px] text-slate-400 mb-1">{{ $invoice->student?->grade }} {{ $invoice->student?->class_name }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-2">Guardian</h2>
            @if($invoice->guardian)
                <p class="text-xs text-slate-200 mb-1">{{ $invoice->guardian->first_name }} {{ $invoice->guardian->last_name }}</p>
                <p class="text-[11px] text-slate-400 mb-1">{{ $invoice->guardian->email }}</p>
                <p class="text-[11px] text-slate-400 mb-1">{{ $invoice->guardian->phone }}</p>
            @else
                <p class="text-[11px] text-slate-400">No guardian linked.</p>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100">
            <ul class="list-disc ml-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.payments.store', $invoice) }}" class="space-y-4 text-xs text-slate-100 max-w-md">
        @csrf

        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="amount">Amount</label>
            <input id="amount" type="number" step="0.01" min="0.01" max="{{ $invoice->balance }}" name="amount" value="{{ old('amount', $invoice->balance) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            <p class="mt-1 text-[11px] text-slate-500">Maximum allowed is the current balance.</p>
        </div>

        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="method">Method</label>
            <select id="method" name="method" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                @foreach($methods as $key => $label)
                    <option value="{{ $key }}" {{ old('method') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="account_id">Deposit Into Account (Bank / Cash)</label>
            <select id="account_id" name="account_id" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                <option value="">-- Auto-select based on payment method --</option>
                @if(isset($bankAccounts) && $bankAccounts->count())
                    <optgroup label="Bank Accounts">
                        @foreach($bankAccounts->filter(fn($a) => $a->category === 'bank' || str_starts_with($a->code, '13')) as $acc)
                            <option value="{{ $acc->id }}" data-category="bank" data-code="{{ $acc->code }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} &bull; {{ $acc->name }} ({{ $acc->currency ?? 'USD' }})
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Cash & Other Accounts">
                        @foreach($bankAccounts->filter(fn($a) => $a->category !== 'bank' && !str_starts_with($a->code, '13')) as $acc)
                            <option value="{{ $acc->id }}" data-category="cash" data-code="{{ $acc->code }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} &bull; {{ $acc->name }} ({{ $acc->currency ?? 'USD' }})
                            </option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
            <p class="mt-1 text-[11px] text-slate-500">Payments will immediately reflect in this account's Cash Book & Bank Reconciliation.</p>
        </div>

        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="reference">Reference</label>
            <input id="reference" type="text" name="reference" value="{{ old('reference') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="Receipt no., bank ref, check no., etc.">
        </div>

        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="paid_at">Date received</label>
            <input id="paid_at" type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
        </div>

        <div class="flex justify-end gap-3 mt-4">
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                Save payment & Generate Receipt
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const methodSelect = document.getElementById('method');
            const accountSelect = document.getElementById('account_id');

            if (!methodSelect || !accountSelect) return;

            function syncAccountWithMethod() {
                const method = methodSelect.value;
                if (accountSelect.value && accountSelect.value !== '') {
                    // User already manually picked one, don't overwrite if valid
                    return;
                }

                const options = Array.from(accountSelect.options);
                let targetOption = null;

                if (method === 'bank') {
                    targetOption = options.find(opt => opt.dataset.category === 'bank' || opt.dataset.code?.startsWith('13'));
                } else if (method === 'cash') {
                    targetOption = options.find(opt => opt.dataset.category === 'cash' || opt.dataset.code === '1102' || opt.dataset.code === '1100');
                } else if (method === 'mobile_money') {
                    targetOption = options.find(opt => opt.text.toLowerCase().includes('mobile') || opt.dataset.code === '1303') 
                        || options.find(opt => opt.dataset.category === 'bank');
                }

                if (targetOption) {
                    targetOption.selected = true;
                }
            }

            methodSelect.addEventListener('change', function () {
                accountSelect.value = ''; // Reset so sync picks appropriate category
                syncAccountWithMethod();
            });

            // Initial run if no account is selected yet
            if (!accountSelect.value) {
                syncAccountWithMethod();
            }
        });
    </script>
@endsection
