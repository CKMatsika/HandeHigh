@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs text-slate-400"><a class="text-indigo-300 hover:text-indigo-200" href="{{ route('admin.bank-reconciliations.index') }}">Bank Reconciliations</a> / Student Payment Allocation</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-50">Review Student Payment Allocations</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-400">Review imported student-register credits, verify the student and Term 1 2026 invoice, then post up to five payments at a time. Posting creates the ERP receipt, allocation, journal and cashbook match.</p>
        </div>
        <a href="{{ route('admin.bank-reconciliations.index') }}" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-200 hover:bg-slate-800">Back to bank reconciliations</a>
    </div>

    @if(session('allocation_posted'))
        <div class="rounded-lg border border-emerald-700 bg-emerald-950/50 p-4 text-sm text-emerald-100">
            <p class="font-semibold">{{ count(session('allocation_posted')) }} payment(s) posted.</p>
            <div class="mt-2 flex flex-wrap gap-3">
                @foreach(session('allocation_posted') as $posted)
                    <a class="underline" target="_blank" href="{{ $posted['receipt_url'] }}">Payment #{{ $posted['payment_id'] }} · ${{ number_format($posted['amount'], 2) }} · Print receipt</a>
                @endforeach
            </div>
        </div>
    @endif
    @if(session('allocation_errors'))
        <div class="rounded-lg border border-amber-700 bg-amber-950/40 p-4 text-sm text-amber-100">
            <p class="font-semibold">Some selected lines were held; their transactions were not posted.</p>
            <ul class="mt-2 list-inside list-disc">@foreach(session('allocation_errors') as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-800 bg-slate-900/60 p-4">
        <label class="min-w-64 flex-1 text-xs text-slate-300">Bank account
            <select name="account_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                @foreach($accounts as $option)<option value="{{ $option->id }}" @selected($account?->id === $option->id)>{{ $option->code }} · {{ $option->name }}</option>@endforeach
            </select>
        </label>
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Show imported lines</button>
    </form>

    <div class="rounded-xl border {{ $period?->isOpen() ? 'border-emerald-800 bg-emerald-950/30' : 'border-amber-700 bg-amber-950/30' }} p-4 text-sm">
        @if($period?->isOpen())
            <p class="font-semibold text-emerald-200">January 2026 posting period is open: {{ $period->name }} ({{ $period->start_date->format('d M Y') }} – {{ $period->end_date->format('d M Y') }}).</p>
            <p class="mt-1 text-emerald-100/80">Payment date is taken from each imported line. The review screen will block posting unless that date falls in an explicitly open accounting period.</p>
        @else
            <p class="font-semibold text-amber-200">Posting is disabled: no explicitly open accounting period covers 30 January 2026.</p>
            <p class="mt-1 text-amber-100/80">Have Finance configure or formally reopen the correct historical period, then refresh this page. This screen will not bypass period controls.</p>
        @endif
    </div>

    @if(!$account)
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-8 text-center text-sm text-slate-400">No active bank account is available for this school.</div>
    @elseif(!$transactions || $transactions->isEmpty())
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-8 text-center text-sm text-slate-300">No unmatched imported student-register credits remain for {{ $account->name }}.</div>
    @else
        <form method="POST" action="{{ route('admin.bank-reconciliations.student-payment-allocation.post') }}" id="allocation-form" class="space-y-3">
            @csrf
            <input type="hidden" name="account_id" value="{{ $account->id }}">
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-800 bg-slate-900 p-3 text-sm">
                <div class="text-slate-300">{{ $transactions->total() }} pending imported lines · Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }} · Posting limit: 5 lines per batch</div>
                <div class="text-xs text-slate-400">Original imported bank lines are retained and matched to the payment cashbook entry.</div>
            </div>

            @foreach($transactions as $transaction)
                @php($review = $transaction->allocation_review)
                @php($source = $review['source'])
                @php($student = $review['student'])
                @php($invoice = $review['invoice'])
                <article class="rounded-xl border {{ $review['ready'] ? 'border-slate-700' : 'border-amber-800/70' }} bg-slate-900/80 p-4" data-line="{{ $transaction->id }}">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1.3fr)]">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="checkbox" class="line-select rounded border-slate-600 bg-slate-950 text-indigo-500" name="selected_ids[]" value="{{ $transaction->id }}" aria-label="Select bank line {{ $transaction->id }}">
                                <span class="text-lg font-semibold text-white">${{ number_format((float) $transaction->amount, 2) }}</span>
                                <span class="rounded-full bg-slate-800 px-2 py-1 text-xs text-slate-300">{{ $transaction->transaction_date->format('d M Y') }}</span>
                                <span class="text-xs text-slate-500">Imported line #{{ $transaction->id }}</span>
                            </div>
                            <p class="text-sm text-slate-200">{{ $source['student_name'] ?? $transaction->description }}</p>
                            <p class="text-xs text-slate-400">Source student number: {{ $source['student_number'] ?? 'not parsed' }} · Source row: {{ $source['source_row'] ?? '—' }} · Payment part: {{ $source['installment'] ?? '—' }}</p>
                            <p class="text-xs text-slate-400">Bank reference: <span class="font-medium text-slate-200">{{ $transaction->reference_number ?: 'Blank' }}</span>
                                @if($review['reference_needs_review'])
                                    <span class="ml-1 rounded bg-amber-900/60 px-2 py-1 text-amber-200">{{ $transaction->reference_number ? 'Repeated reference (' . $review['reference_count'] . ' lines)' : 'No source reference' }}</span>
                                @endif
                            </p>
                            @if($review['match_basis'])<p class="text-xs text-indigo-300">Suggested match: {{ $review['match_basis'] }}. Check it before posting.</p>@endif
                            @if($review['reason'])<p class="review-reason text-xs text-amber-300">Review needed: {{ $review['reason'] }}</p>@endif
                            <p class="manual-match-note hidden text-xs text-amber-200">Manual student selected. Confirm the identity and invoice before posting.</p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="text-xs text-slate-300">Student (suggested value is not accepted until you select this line)</label>
                                <select class="student-select mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" name="items[{{ $transaction->id }}][student_id]" data-invoices-url-template="{{ route('admin.bank-reconciliations.student-payment-allocation.invoices', ['student' => '__STUDENT__']) }}">
                                    <option value="">Choose student…</option>
                                    @foreach($review['candidates'] as $candidate)
                                        <option value="{{ $candidate->id }}" @selected($student?->id === $candidate->id)>{{ trim($candidate->first_name . ' ' . $candidate->other_names . ' ' . $candidate->last_name) }} · #{{ $candidate->admission_number ?: $candidate->registration_number ?: $candidate->id }}</option>
                                    @endforeach
                                </select>
                                <div class="mt-2 flex gap-2">
                                    <input type="search" class="student-search min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white" placeholder="Search name or student number">
                                    <button type="button" class="student-search-button rounded-lg border border-slate-700 px-3 text-xs text-slate-200 hover:bg-slate-800" data-url="{{ route('admin.bank-reconciliations.student-payment-allocation.students') }}">Search</button>
                                </div>
                                <p class="student-search-results mt-1 text-xs text-slate-400" aria-live="polite"></p>
                            </div>
                            <div>
                                <label class="text-xs text-slate-300">Open Term 1 2026 invoice</label>
                                <select class="invoice-select mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" name="items[{{ $transaction->id }}][invoice_id]" data-selected="{{ $invoice?->id }}">
                                    <option value="">Choose invoice…</option>
                                    @if($invoice)<option value="{{ $invoice->id }}" selected>{{ $invoice->number }} · balance ${{ number_format((float) $invoice->balance, 2) }}</option>@endif
                                </select>
                                <p class="invoice-status mt-1 text-xs text-slate-400" aria-live="polite">Invoices are loaded for the selected student only.</p>
                                @if($review['reference_needs_review'])
                                    <label class="mt-3 flex items-start gap-2 text-xs text-amber-200">
                                        <input type="checkbox" name="items[{{ $transaction->id }}][confirm_reference_review]" value="1" class="mt-0.5 rounded border-amber-700 bg-slate-950">
                                        I reviewed this blank or repeated source reference; keep its original value on the receipt.
                                    </label>
                                @endif
                                <button type="button" class="post-single-line mt-3 w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-50" @disabled(!$period?->isOpen())>
                                    Post this payment now
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-900 bg-indigo-950/30 p-4">
                <p class="text-sm text-slate-300">Each selected line is posted in its own database transaction. A failed line rolls back by itself and remains available for review.</p>
                <button type="submit" id="post-selected" @disabled(!$period?->isOpen()) class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">Post selected payments</button>
            </div>
            <div class="flex justify-center">{{ $transactions->links() }}</div>
        </form>
    @endif
</div>

<script>
(() => {
    const form = document.getElementById('allocation-form');
    if (!form) return;
    const searchBase = @json(route('admin.bank-reconciliations.student-payment-allocation.students'));

    async function loadInvoices(card, studentId) {
        const select = card.querySelector('.invoice-select');
        const status = card.querySelector('.invoice-status');
        if (!studentId) { select.innerHTML = '<option value="">Choose student first…</option>'; status.textContent = 'Choose a student to load their open Term 1 2026 invoices.'; return; }
        if (select.dataset.loadedStudent === String(studentId)) return;
        select.dataset.loadedStudent = String(studentId);
        select.innerHTML = '<option value="">Loading this student’s invoices…</option>';
        status.textContent = 'Looking up this student’s open Term 1 2026 invoices…';
        const url = select.dataset.urlTemplate.replace('__STUDENT__', encodeURIComponent(studentId));
        let invoices = [];
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, signal: controller.signal});
            if (!response.ok) throw new Error('Invoice lookup failed');
            invoices = await response.json();
        } catch (error) {
            select.dataset.loadedStudent = '';
            select.innerHTML = '<option value="">Could not load invoices</option>';
            status.textContent = error.name === 'AbortError'
                ? 'Invoice lookup took over 15 seconds. Retry by selecting the student again.'
                : 'Invoice lookup failed. Check your connection and select the student again to retry.';
            return;
        } finally {
            clearTimeout(timeout);
        }
        const preferredInvoice = select.dataset.selected;
        select.innerHTML = '<option value="">Choose invoice…</option>';
        invoices.forEach(invoice => {
            const option = document.createElement('option');
            option.value = invoice.id; option.textContent = invoice.label; select.append(option);
        });
        if (preferredInvoice && invoices.some(invoice => String(invoice.id) === String(preferredInvoice))) select.value = String(preferredInvoice);
        else if (invoices.length === 1) select.value = String(invoices[0].id);
        status.textContent = invoices.length ? `${invoices.length} open Term 1 2026 invoice(s) loaded for this student.` : 'No open Term 1 2026 invoice was found for this student.';
    }

    form.querySelectorAll('.student-select').forEach(select => {
        const card = select.closest('[data-line]');
        const invoiceSelect = card.querySelector('.invoice-select');
        if (invoiceSelect.options.length > 1) invoiceSelect.dataset.loadedStudent = select.value;
        select.addEventListener('change', () => {
            invoiceSelect.dataset.selected = '';
            invoiceSelect.dataset.loadedStudent = '';
            card.querySelector('.review-reason')?.classList.add('hidden');
            card.querySelector('.manual-match-note')?.classList.toggle('hidden', !select.value);
            loadInvoices(card, select.value);
        });
        invoiceSelect.addEventListener('focus', () => {
            if (select.value && invoiceSelect.options.length <= 1) loadInvoices(card, select.value);
        });
    });

    form.querySelectorAll('.student-search-button').forEach(button => button.addEventListener('click', async () => {
        const card = button.closest('[data-line]');
        const search = card.querySelector('.student-search').value.trim();
        const resultText = card.querySelector('.student-search-results');
        if (search.length < 2) { resultText.textContent = 'Enter at least two characters.'; return; }
        resultText.textContent = 'Searching…';
        const url = new URL(button.dataset.url, window.location.origin); url.searchParams.set('q', search);
        const response = await fetch(url, {headers: {'Accept': 'application/json'}});
        if (!response.ok) { resultText.textContent = 'Student search failed. Refresh and try again.'; return; }
        const students = await response.json();
        const select = card.querySelector('.student-select');
        students.forEach(student => {
            if ([...select.options].some(option => option.value === String(student.id))) return;
            const option = document.createElement('option'); option.value = student.id;
            option.textContent = `${student.label} · #${student.admission_number || student.registration_number || student.id}`;
            select.append(option);
        });
        resultText.textContent = students.length ? `${students.length} student(s) found. Select the correct student above.` : 'No students found.';
    }));

    form.querySelectorAll('.line-select').forEach(box => box.addEventListener('change', () => {
        const selected = form.querySelectorAll('.line-select:checked').length;
        if (selected > 5) { box.checked = false; alert('Post no more than five payment lines in one batch.'); }
    }));

    form.querySelectorAll('.post-single-line').forEach(button => button.addEventListener('click', () => {
        const card = button.closest('[data-line]');
        const student = card.querySelector('.student-select');
        const invoice = card.querySelector('.invoice-select');
        if (!student.value) { alert('Select and verify the student first.'); student.focus(); return; }
        if (!invoice.value) { alert('Select this student’s Term 1 2026 invoice first.'); invoice.focus(); return; }
        const referenceReview = card.querySelector('input[name$="[confirm_reference_review]"]');
        if (referenceReview && !referenceReview.checked) { alert('Review and confirm the blank or repeated bank reference first.'); referenceReview.focus(); return; }
        form.querySelectorAll('.line-select').forEach(box => { box.checked = box.closest('[data-line]') === card; });
        form.requestSubmit();
    }));

    form.addEventListener('submit', event => {
        const selected = [...form.querySelectorAll('.line-select:checked')];
        if (selected.length < 1 || selected.length > 5) { event.preventDefault(); alert('Select between one and five payment lines.'); return; }
        const incomplete = selected.some(box => {
            const card = box.closest('[data-line]');
            return !card.querySelector('.student-select').value || !card.querySelector('.invoice-select').value;
        });
        if (incomplete) { event.preventDefault(); alert('Choose a student and Term 1 2026 invoice for every selected line.'); return; }
        if (!confirm(`Post ${selected.length} payment(s) through the ERP, create printable receipts and match each imported bank line?`)) event.preventDefault();
    });
})();
</script>
@endsection
