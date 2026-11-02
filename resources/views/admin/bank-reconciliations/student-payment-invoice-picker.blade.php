@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <p class="text-xs text-slate-400"><a class="text-indigo-300 hover:text-indigo-200" href="{{ route('admin.bank-reconciliations.index') }}">Bank Reconciliations</a> / <a class="text-indigo-300 hover:text-indigo-200" href="{{ $queue === 'deferred' ? route('admin.bank-reconciliations.student-payment-allocation.deferred', ['account_id' => $account->id, 'page' => request('page', 1)]) : route('admin.bank-reconciliations.student-payment-allocation', ['account_id' => $account->id, 'page' => request('page', 1)]) }}">{{ $queue === 'deferred' ? 'Deferred Payment Allocations' : 'Payment Allocation' }}</a> / Choose Invoice</p>
        <h1 class="mt-1 text-xl font-semibold text-white">Choose an invoice for {{ trim($student->first_name . ' ' . $student->other_names . ' ' . $student->last_name) }}</h1>
        <p class="mt-1 text-sm text-slate-400">This list is limited to this student’s outstanding Term 1 2026 invoices. Selecting one returns you to the same imported payment card.</p>
    </div>

    <section class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white">Payment: ${{ number_format((float) $bankTransaction->amount, 2) }}</span>
            <span class="text-sm text-slate-300">{{ $bankTransaction->transaction_date->format('d M Y') }}</span>
            <span class="text-sm text-slate-300">Bank reference: {{ $bankTransaction->reference_number ?: 'Blank' }}</span>
            <span class="text-xs text-slate-500">Source line #{{ $bankTransaction->id }}</span>
        </div>
    </section>

    <form method="GET" action="{{ route('admin.bank-reconciliations.student-payment-allocation.invoice-picker', ['transaction' => $bankTransaction->id]) }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-800 bg-slate-900/70 p-4">
        <input type="hidden" name="account_id" value="{{ $account->id }}">
        <input type="hidden" name="student_id" value="{{ $student->id }}">
        <input type="hidden" name="page" value="{{ request('page', 1) }}">
        <label class="min-w-64 flex-1 text-xs text-slate-300">Search invoice number
            <input type="search" name="q" value="{{ request('q') }}" placeholder="For example INV-2026-001" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
        </label>
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Search invoices</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <div class="border-b border-slate-800 px-4 py-3 text-sm font-medium text-slate-200">{{ $invoices->total() }} outstanding Term 1 2026 invoice(s)</div>
        @if($invoices->isEmpty())
            <div class="p-8 text-center text-sm text-slate-400">No outstanding Term 1 2026 invoices were found for this student{{ request('q') ? ' with that invoice number' : '' }}.</div>
        @else
            <div class="divide-y divide-slate-800">
                @foreach($invoices as $invoice)
                    @php($canAcceptPayment = (int) round((float) $invoice->balance * 100) >= (int) round((float) $bankTransaction->amount * 100))
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="min-w-56 flex-1">
                            <div class="font-semibold text-white">{{ $invoice->number }}</div>
                            <div class="mt-1 text-xs text-slate-400">Term {{ $invoice->term }} · Academic year {{ $invoice->academic_year }} · Status {{ ucfirst($invoice->status) }}</div>
                            <div class="mt-1 text-xs text-slate-400">Issued {{ $invoice->issued_at?->format('d M Y') ?? '—' }} · Total ${{ number_format((float) $invoice->total_amount, 2) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-400">Outstanding balance</div>
                            <div class="text-lg font-semibold {{ $canAcceptPayment ? 'text-emerald-300' : 'text-amber-300' }}">${{ number_format((float) $invoice->balance, 2) }}</div>
                        </div>
                        @if($canAcceptPayment)
                            <a href="{{ $queue === 'deferred' ? route('admin.bank-reconciliations.student-payment-allocation.deferred', ['account_id' => $account->id, 'page' => request('page', 1), 'selected_transaction' => $bankTransaction->id, 'student_id' => $student->id, 'invoice_id' => $invoice->id]) : route('admin.bank-reconciliations.student-payment-allocation', ['account_id' => $account->id, 'page' => request('page', 1), 'selected_transaction' => $bankTransaction->id, 'student_id' => $student->id, 'invoice_id' => $invoice->id]) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">Select this invoice</a>
                        @else
                            <span class="max-w-40 text-right text-xs text-amber-300">Balance is less than this payment; it cannot accept the full amount.</span>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="border-t border-slate-800 p-3">{{ $invoices->links() }}</div>
        @endif
    </div>

    <div class="flex justify-end">
        <a href="{{ $queue === 'deferred' ? route('admin.bank-reconciliations.student-payment-allocation.deferred', ['account_id' => $account->id, 'page' => request('page', 1)]) : route('admin.bank-reconciliations.student-payment-allocation', ['account_id' => $account->id, 'page' => request('page', 1)]) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Return without selecting</a>
    </div>
</div>
@endsection
