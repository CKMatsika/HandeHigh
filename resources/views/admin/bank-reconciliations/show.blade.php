@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Reconciliation {{ $bankReconciliation->reconciliation_date->format('Y-m-d') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $bankReconciliation->bankAccount?->account_name ?? '-' }}</p>
        </div>
        <a href="{{ route('admin.bank-reconciliations.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 text-xs text-slate-200 space-y-2">
        <div class="flex justify-between"><span class="text-slate-400">Book Balance</span><span>{{ number_format($bankReconciliation->book_balance, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-400">Bank Balance</span><span>{{ number_format($bankReconciliation->bank_balance, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-400">Difference</span><span>{{ number_format($bankReconciliation->reconciled_balance, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-400">Status</span><span>{{ ucfirst($bankReconciliation->status) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-400">Notes</span><span>{{ $bankReconciliation->notes ?? '-' }}</span></div>
    </div>
@endsection

