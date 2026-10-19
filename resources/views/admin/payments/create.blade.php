@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Record Fee Payment</h1>
            <p class="text-xs text-slate-400 mt-1">Receive fee payment, allocate across vote sub-accounts, and issue official MOPSE receipt.</p>
        </div>
        <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back to Invoice
        </a>
    </div>

    @if(isset($totalAdvanceAvailable) && $totalAdvanceAvailable > 0)
        <div class="mb-5 rounded-2xl border border-indigo-500/40 bg-indigo-500/10 p-4 text-xs text-indigo-100 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-300 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div>
                    <span class="font-semibold text-indigo-200">Prepayment Credit Available:</span>
                    This student has <strong class="text-emerald-400 font-mono">${{ number_format($totalAdvanceAvailable, 2) }}</strong> in unallocated advance payments on account.
                </div>
            </div>
            <button type="button" onclick="document.getElementById('apply-advance-card').scrollIntoView({behavior: 'smooth'})" class="inline-flex items-center rounded-full bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 transition shrink-0">
                Apply Existing Advance Credit &darr;
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-xs text-slate-100">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-2">Invoice Summary</h2>
            <p class="text-[11px] text-slate-400 mb-1">Invoice Number: <strong class="text-slate-200">{{ $invoice->number }}</strong></p>
            <p class="text-[11px] text-slate-400 mb-1">Period: {{ $invoice->academic_year }} · {{ $invoice->term }}</p>
            <p class="text-[11px] text-slate-400 mb-1">Issued: {{ $invoice->issued_at?->format('Y-m-d') }}</p>
            <div class="mt-3 pt-2 border-t border-slate-800 flex justify-between">
                <span class="text-slate-400">Total Invoice:</span>
                <span class="font-mono font-semibold">${{ number_format($invoice->total_amount, 2) }}</span>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-slate-400">Current Balance:</span>
                <span class="font-mono font-bold text-rose-400">${{ number_format($invoice->balance, 2) }}</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-2">Student Information</h2>
            <p class="text-xs text-slate-200 font-semibold mb-1">{{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</p>
            <p class="text-[11px] text-slate-400 mb-1">Reg / Adm: {{ $invoice->student?->admission_number ?? '—' }}</p>
            <p class="text-[11px] text-slate-400 mb-1">Grade / Class: {{ $invoice->student?->grade }} {{ $invoice->student?->class_name }}</p>
            <p class="text-[11px] text-slate-400 mt-2">
                Residency: 
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $invoice->student?->is_boarding ? 'bg-amber-500/20 text-amber-300' : 'bg-blue-500/20 text-blue-300' }}">
                    {{ $invoice->student?->is_boarding ? 'Boarding Student' : 'Day-Scholar Student' }}
                </span>
            </p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-2">Guardian / Payer</h2>
            @if($invoice->guardian)
                <p class="text-xs text-slate-200 mb-1">{{ $invoice->guardian->first_name }} {{ $invoice->guardian->last_name }}</p>
                <p class="text-[11px] text-slate-400 mb-1">{{ $invoice->guardian->phone ?? 'No phone' }}</p>
                <p class="text-[11px] text-slate-400 mb-1">{{ $invoice->guardian->email ?? 'No email' }}</p>
            @else
                <p class="text-[11px] text-slate-400">No guardian record attached to student.</p>
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

    <form id="payment-form" method="POST" action="{{ route('admin.payments.store', $invoice) }}" class="space-y-6 text-xs text-slate-100">
        @csrf

        <!-- Payment Meta Grid -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
            <h2 class="text-sm font-semibold text-slate-100 border-b border-slate-800 pb-2">1. Payment & MOPSE Receipt Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="mopse_receipt_number">
                        MOPSE Serial Number / Receipt Book No.
                        <span class="text-emerald-400 font-normal">(Printed on hardcopy)</span>
                    </label>
                    <input id="mopse_receipt_number" type="text" name="mopse_receipt_number" value="{{ old('mopse_receipt_number') }}" class="w-full rounded-lg border border-emerald-500/60 bg-slate-950/80 px-3 py-2 text-xs font-mono text-emerald-300 placeholder-slate-600 focus:border-emerald-400 focus:outline-none" placeholder="e.g. 134201" autofocus>
                    <p class="mt-1 text-[10px] text-slate-500">Ministry printed book serialized voucher number.</p>
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="amount">
                        Total Amount Paid ($) <span class="text-rose-400">*</span>
                    </label>
                    <input id="amount" type="number" step="0.01" min="0.01" max="{{ $invoice->balance }}" name="amount" value="{{ old('amount', $invoice->balance) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs font-mono font-bold text-white focus:border-emerald-500 focus:outline-none">
                    <p class="mt-1 text-[10px] text-slate-500">Max balance due: ${{ number_format($invoice->balance, 2) }}</p>
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="paid_at">
                        Date Received <span class="text-rose-400">*</span>
                    </label>
                    <input id="paid_at" type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="method">Payment Method <span class="text-rose-400">*</span></label>
                    <select id="method" name="method" class="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs">
                        @foreach($methods as $key => $label)
                            <option value="{{ $key }}" {{ old('method', 'cash') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="account_id">Deposit Into Account (Bank / Cash)</label>
                    <select id="account_id" name="account_id" class="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs">
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

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="reference">Internal Reference / Notes</label>
                    <input id="reference" type="text" name="reference" value="{{ old('reference') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs" placeholder="Bank ref, Ecocash txn, payer note">
                </div>
            </div>
        </div>

        <!-- Sub-Account / Vote Allocation Section -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-100">2. Vote Sub-Account Payment Allocation (FIFO Auto-Distribution)</h2>
                    <p class="text-[11px] text-slate-400">Amounts are automatically distributed in priority order (FIFO) awaiting your review or manual adjustments.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-re-fifo" class="inline-flex items-center rounded-full bg-slate-800 hover:bg-slate-700 px-3 py-1 text-[11px] font-medium text-slate-200 border border-slate-700 transition">
                        <svg class="w-3.5 h-3.5 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Re-run FIFO Distribution
                    </button>
                </div>
            </div>

            @if(isset($items) && $items->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-slate-200 divide-y divide-slate-800">
                        <thead>
                            <tr class="text-slate-400 text-[11px] text-left uppercase">
                                <th class="py-2.5 px-3 font-semibold">Priority</th>
                                <th class="py-2.5 px-3 font-semibold">Sub-Account / Vote</th>
                                <th class="py-2.5 px-3 font-semibold text-right">Vote Total</th>
                                <th class="py-2.5 px-3 font-semibold text-right">Already Paid</th>
                                <th class="py-2.5 px-3 font-semibold text-right">Outstanding</th>
                                <th class="py-2.5 px-3 font-semibold text-right w-44">This Allocation ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-sans">
                            @foreach($items as $item)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-2.5 px-3">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-800 text-[10px] font-mono font-bold text-slate-300">
                                            {{ $item['priority'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 font-medium text-slate-100">
                                        {{ $item['description'] }}
                                        @if($item['vote_code'])
                                            <span class="text-[10px] text-slate-500 font-mono ml-1">[{{ $item['vote_code'] }}]</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-slate-300">${{ number_format($item['line_total'], 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono text-emerald-400">${{ number_format($item['paid'], 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-semibold text-rose-300">${{ number_format($item['balance'], 2) }}</td>
                                    <td class="py-2.5 px-3 text-right">
                                        <input type="number" 
                                               step="0.01" 
                                               min="0" 
                                               max="{{ $item['balance'] }}" 
                                               name="allocations[{{ $item['id'] }}]" 
                                               value="{{ old('allocations.' . $item['id'], '0.00') }}"
                                               data-item-id="{{ $item['id'] }}"
                                               data-balance="{{ $item['balance'] }}"
                                               data-priority="{{ $item['priority'] }}"
                                               class="item-alloc-input w-36 text-right rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-xs font-mono font-bold text-emerald-300 focus:border-emerald-500 focus:outline-none">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-700 bg-slate-950/60 font-semibold">
                                <td colspan="5" class="py-3 px-3 text-right uppercase tracking-wider text-slate-300">
                                    Total Allocated Across Votes:
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-sm font-bold" id="total-allocated-display">
                                    $0.00
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="allocation-status-banner" class="rounded-xl border p-3 flex items-center justify-between text-xs transition">
                    <div id="allocation-status-msg" class="font-medium">
                        Calculating distribution...
                    </div>
                    <div id="allocation-diff-badge" class="font-mono font-bold">
                    </div>
                </div>
            @else
                <p class="text-xs text-slate-400">No itemized fee votes found on this invoice.</p>
            @endif
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-5 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit" id="btn-submit-payment" class="inline-flex items-center rounded-full bg-emerald-500 hover:bg-emerald-600 px-6 py-2 text-xs font-semibold text-white shadow-lg shadow-emerald-500/20 transition gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Confirm Payment & Generate Official Receipt
            </button>
        </div>
    </form>

    @if(isset($advancePayments) && $advancePayments->count())
        <!-- Apply Advance Credit Section -->
        <div id="apply-advance-card" class="mt-8 rounded-2xl border border-indigo-500/30 bg-slate-900/80 p-5 space-y-4">
            <div class="border-b border-slate-800 pb-3">
                <h2 class="text-sm font-semibold text-indigo-300">Apply Existing Advance Payment Credit to this Invoice</h2>
                <p class="text-[11px] text-slate-400">Use pre-collected advance funds on this student's account without accepting new cash.</p>
            </div>

            <form method="POST" action="{{ route('invoices.apply-advance', $invoice) }}" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300">Select Advance Prepayment</label>
                        <select name="payment_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs">
                            @foreach($advancePayments as $adv)
                                <option value="{{ $adv->id }}">
                                    PAY-{{ $adv->id }} {{ $adv->mopse_receipt_number ? '(MOPSE: ' . $adv->mopse_receipt_number . ')' : '' }} &bull; Available: ${{ number_format($adv->advance_balance, 2) }} ({{ $adv->paid_at?->format('Y-m-d') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300">Credit Amount to Apply ($)</label>
                        <input type="number" step="0.01" min="0.01" max="{{ min($totalAdvanceAvailable, (float)$invoice->balance) }}" name="amount" value="{{ min($totalAdvanceAvailable, (float)$invoice->balance) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-mono font-bold text-emerald-300">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-500 py-2 text-xs font-semibold text-white transition">
                            Apply Advance Credit to Invoice
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const amountInput = document.getElementById('amount');
            const itemInputs = Array.from(document.querySelectorAll('.item-alloc-input'));
            const totalAllocatedDisplay = document.getElementById('total-allocated-display');
            const statusBanner = document.getElementById('allocation-status-banner');
            const statusMsg = document.getElementById('allocation-status-msg');
            const diffBadge = document.getElementById('allocation-diff-badge');
            const btnReFifo = document.getElementById('btn-re-fifo');
            const paymentForm = document.getElementById('payment-form');
            const methodSelect = document.getElementById('method');
            const accountSelect = document.getElementById('account_id');

            // FIFO Auto-Allocation
            function runFifoAllocation() {
                const totalAmount = parseFloat(amountInput.value) || 0;
                let remaining = totalAmount;

                // Sort inputs by data-priority
                const sorted = [...itemInputs].sort((a, b) => {
                    return (parseInt(a.dataset.priority) || 10) - (parseInt(b.dataset.priority) || 10);
                });

                sorted.forEach(input => {
                    const balance = parseFloat(input.dataset.balance) || 0;
                    if (remaining > 0) {
                        const take = Math.min(remaining, balance);
                        input.value = take.toFixed(2);
                        remaining -= take;
                    } else {
                        input.value = "0.00";
                    }
                });

                // If remaining payment exceeds item balances, add excess to highest priority or last item
                if (remaining > 0.001 && sorted.length > 0) {
                    const lastInput = sorted[sorted.length - 1];
                    const currentVal = parseFloat(lastInput.value) || 0;
                    lastInput.value = (currentVal + remaining).toFixed(2);
                }

                updateAllocationSummary();
            }

            function updateAllocationSummary() {
                const totalAmount = parseFloat(amountInput.value) || 0;
                let totalAllocated = 0;

                itemInputs.forEach(input => {
                    totalAllocated += parseFloat(input.value) || 0;
                });

                totalAllocatedDisplay.textContent = '$' + totalAllocated.toFixed(2);

                const diff = totalAllocated - totalAmount;

                if (Math.abs(diff) < 0.01) {
                    statusBanner.className = 'rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-3 flex items-center justify-between text-xs text-emerald-200';
                    statusMsg.innerHTML = '<span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> <strong>Allocations Balanced:</strong> All votes correctly match the total payment amount. Ready to save.</span>';
                    diffBadge.textContent = 'Balanced ($' + totalAllocated.toFixed(2) + ')';
                    diffBadge.className = 'font-mono font-bold text-emerald-400';
                } else if (diff > 0) {
                    statusBanner.className = 'rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 flex items-center justify-between text-xs text-amber-200';
                    statusMsg.innerHTML = '<strong>Over-allocated:</strong> Sub-account allocations exceed total amount by $' + diff.toFixed(2) + '. Adjust rows to balance.';
                    diffBadge.textContent = '+$' + diff.toFixed(2);
                    diffBadge.className = 'font-mono font-bold text-amber-400';
                } else {
                    statusBanner.className = 'rounded-xl border border-rose-500/40 bg-rose-500/10 p-3 flex items-center justify-between text-xs text-rose-200';
                    statusMsg.innerHTML = '<strong>Under-allocated:</strong> Sub-account allocations are short by $' + Math.abs(diff).toFixed(2) + '. Click "Re-run FIFO" or adjust manually.';
                    diffBadge.textContent = '-$' + Math.abs(diff).toFixed(2);
                    diffBadge.className = 'font-mono font-bold text-rose-400';
                }
            }

            // Sync account with payment method
            function syncAccountWithMethod() {
                if (!methodSelect || !accountSelect) return;
                const method = methodSelect.value;
                if (accountSelect.value && accountSelect.value !== '') return;

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

            // Event Listeners
            amountInput.addEventListener('input', runFifoAllocation);
            if (btnReFifo) btnReFifo.addEventListener('click', runFifoAllocation);

            itemInputs.forEach(input => {
                input.addEventListener('input', updateAllocationSummary);
            });

            if (methodSelect) {
                methodSelect.addEventListener('change', function () {
                    if (accountSelect) accountSelect.value = '';
                    syncAccountWithMethod();
                });
                syncAccountWithMethod();
            }

            // Form Submit Safety
            if (paymentForm) {
                paymentForm.addEventListener('submit', function (e) {
                    const totalAmount = parseFloat(amountInput.value) || 0;
                    let totalAllocated = 0;
                    itemInputs.forEach(input => {
                        totalAllocated += parseFloat(input.value) || 0;
                    });

                    if (itemInputs.length > 0 && Math.abs(totalAllocated - totalAmount) > 0.05) {
                        e.preventDefault();
                        alert('Cannot save: Sub-account allocations ($' + totalAllocated.toFixed(2) + ') do not match the total payment amount ($' + totalAmount.toFixed(2) + '). Please balance the allocations first.');
                    }
                });
            }

            // Initial allocation run on page load
            if (itemInputs.length > 0) {
                runFifoAllocation();
            }
        });
    </script>
@endsection
