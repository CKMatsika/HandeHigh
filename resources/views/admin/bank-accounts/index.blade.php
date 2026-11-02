@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Bank Accounts</h1>
            <p class="text-xs text-slate-400 mt-1">Manage accounts and review today's posted movements and current ledger balance.</p>
        </div>
        <a href="{{ route('admin.bank-accounts.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Bank Account
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($bankAccounts as $account)
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 hover:bg-slate-900 transition-colors">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-50">{{ $account->name }}</h3>
                        <p class="text-xs text-slate-400">{{ $account->code }}</p>
                    </div>
                    @if($account->is_active)
                        <span class="inline-flex items-center rounded-full bg-emerald-500/20 px-2 py-1 text-xs font-medium text-emerald-400">
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-red-500/20 px-2 py-1 text-xs font-medium text-red-400">
                            Inactive
                        </span>
                    @endif
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400">Account Code:</span>
                        <span class="text-slate-300">{{ $account->code }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400">Type:</span>
                        <span class="text-slate-300">{{ ucfirst($account->type) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400">Category:</span>
                        <span class="text-slate-300">{{ ucfirst($account->category) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400">Currency:</span>
                        <span class="text-slate-300">{{ $account->currency ?? 'USD' }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-800 pt-3">
                        <div class="rounded-lg bg-slate-950/60 p-2">
                            <div class="text-[11px] text-slate-400">Today's opening</div>
                            <div class="mt-1 text-sm font-semibold text-slate-100">${{ number_format((float) $account->today_opening_balance, 2) }}</div>
                        </div>
                        <div class="rounded-lg bg-emerald-950/30 p-2">
                            <div class="text-[11px] text-slate-400">Today in</div>
                            <div class="mt-1 text-sm font-semibold text-emerald-300">+${{ number_format((float) $account->today_total_in, 2) }}</div>
                        </div>
                        <div class="rounded-lg bg-rose-950/20 p-2">
                            <div class="text-[11px] text-slate-400">Today out</div>
                            <div class="mt-1 text-sm font-semibold text-rose-300">−${{ number_format((float) $account->today_total_out, 2) }}</div>
                        </div>
                        <div class="rounded-lg border border-indigo-800/70 bg-indigo-950/30 p-2">
                            <div class="text-[11px] text-indigo-200">Closing balance now</div>
                            <div class="mt-1 text-sm font-bold text-white">${{ number_format((float) $account->current_closing_balance, 2) }}</div>
                        </div>
                    </div>
                    <div class="text-[10px] text-slate-500">Posted ledger movements through {{ \Carbon\Carbon::parse($today)->format('d M Y') }}.</div>
                    @if($account->recent_transactions > 0)
                        <div class="text-xs text-slate-400">
                            {{ $account->recent_transactions }} transactions in last 7 days
                        </div>
                    @endif
                </div>
                
                <div class="flex gap-2 mt-4 pt-3 border-t border-slate-800">
                    <a href="{{ route('admin.bank-accounts.show', $account->id) }}" class="flex-1 text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 py-1.5 px-2 rounded text-center transition-colors">
                        View
                    </a>
                    <a href="{{ route('admin.bank-accounts.edit', $account->id) }}" class="flex-1 text-xs bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 py-1.5 px-2 rounded text-center transition-colors">
                        Edit
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    @if($bankAccounts->isEmpty())
        <div class="text-center py-12">
            <div class="text-slate-400 mb-4">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3 3z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-50 mb-2">No bank accounts found</h3>
            <p class="text-sm text-slate-400 mb-4">Get started by adding your first bank account.</p>
            <a href="{{ route('admin.bank-accounts.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                Add Bank Account
            </a>
        </div>
    @endif
</div>
@endsection
