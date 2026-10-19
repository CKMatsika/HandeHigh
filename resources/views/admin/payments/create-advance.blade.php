@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Receive Advance Fee Payment</h1>
            <p class="text-xs text-slate-400 mt-1">Receipt advance student fees today without an existing invoice, with future invoice allocation capability.</p>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back to Invoices
        </a>
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

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('payments.advance.store') }}" class="space-y-5 text-xs text-slate-100">
            @csrf

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-slate-100 border-b border-slate-800 pb-2">Student & Prepayment Information</h2>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="student_id">Select Student <span class="text-rose-400">*</span></label>
                    <select id="student_id" name="student_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs">
                        <option value="">-- Choose student --</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}" {{ (old('student_id', $selectedStudentId) == $st->id) ? 'selected' : '' }}>
                                {{ $st->last_name }}, {{ $st->first_name }} &bull; {{ $st->admission_number ?? 'No Adm #' }} &bull; {{ $st->grade }} {{ $st->class_name }} ({{ $st->is_boarding ? 'Boarding' : 'Day' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="mopse_receipt_number">
                            MOPSE Serial Number / Receipt Book No.
                            <span class="text-emerald-400 font-normal">(Printed on hardcopy)</span>
                        </label>
                        <input id="mopse_receipt_number" type="text" name="mopse_receipt_number" value="{{ old('mopse_receipt_number') }}" class="w-full rounded-lg border border-emerald-500/60 bg-slate-950 px-3 py-2 text-xs font-mono text-emerald-300 placeholder-slate-600 focus:border-emerald-400 focus:outline-none" placeholder="e.g. 134201" autofocus>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="amount">Amount Received ($) <span class="text-rose-400">*</span></label>
                        <input id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-mono font-bold text-white focus:border-emerald-500 focus:outline-none" placeholder="e.g. 135.00 or 470.00">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="method">Payment Method <span class="text-rose-400">*</span></label>
                        <select id="method" name="method" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs">
                            @foreach($methods as $key => $label)
                                <option value="{{ $key }}" {{ old('method', 'cash') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="account_id">Deposit Account</label>
                        <select id="account_id" name="account_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs">
                            <option value="">-- Auto-select based on payment method --</option>
                            @if(isset($bankAccounts) && $bankAccounts->count())
                                <optgroup label="Bank Accounts">
                                    @foreach($bankAccounts->filter(fn($a) => $a->category === 'bank' || str_starts_with($a->code, '13')) as $acc)
                                        <option value="{{ $acc->id }}" data-category="bank" data-code="{{ $acc->code }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->code }} &bull; {{ $acc->name }} ({{ $acc->currency ?? 'USD' }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Cash Accounts">
                                    @foreach($bankAccounts->filter(fn($a) => $a->category !== 'bank' && !str_starts_with($a->code, '13')) as $acc)
                                        <option value="{{ $acc->id }}" data-category="cash" data-code="{{ $acc->code }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->code }} &bull; {{ $acc->name }} ({{ $acc->currency ?? 'USD' }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="paid_at">Date Received <span class="text-rose-400">*</span></label>
                        <input id="paid_at" type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="notes">Prepayment Target / Notes</label>
                        <input id="notes" type="text" name="notes" value="{{ old('notes') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs" placeholder="e.g. Next Term fees advance">
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 text-[11px] text-slate-400 leading-relaxed">
                    <strong class="text-emerald-400">Advance Receipt Workflow:</strong> This payment will immediately issue an official MOPSE printed receipt and post double-entry accounting (Debit Cash/Bank, Credit Deferred Prepaid Fees). The unallocated credit balance is preserved on the student's account and can be applied to any invoice in the correct term.
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-5 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 hover:bg-emerald-600 px-6 py-2 text-xs font-semibold text-white shadow-lg shadow-emerald-500/20 transition gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Issue Advance Receipt & Voucher
                </button>
            </div>
        </form>
    </div>
@endsection
