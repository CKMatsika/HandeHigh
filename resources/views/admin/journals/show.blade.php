@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Journal {{ $journalBatch->batch_number }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $journalBatch->description }}</p>
        </div>
        <a href="{{ route('admin.journals.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 text-xs text-slate-300 space-y-2">
            <div class="flex justify-between"><span class="text-slate-400">Date</span><span>{{ $journalBatch->transaction_date->format('Y-m-d') }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Reference</span><span>{{ $journalBatch->reference_number ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Status</span>
                <span class="px-2 py-1 rounded-full {{ $journalBatch->status === 'posted' ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">{{ ucfirst($journalBatch->status) }}</span>
            </div>
            <div class="flex justify-between"><span class="text-slate-400">Debit Total</span><span>{{ number_format($journalBatch->debit_total, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Credit Total</span><span>{{ number_format($journalBatch->credit_total, 2) }}</span></div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 text-xs text-slate-300 space-y-2">
            <div class="flex justify-between"><span class="text-slate-400">Created By</span><span>{{ $journalBatch->creator?->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Approved By</span><span>{{ $journalBatch->approver?->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Posted At</span><span>{{ $journalBatch->posted_at ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Notes</span><span>{{ $journalBatch->notes ?? '-' }}</span></div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Account</th>
                        <th class="px-4 py-3 text-left font-medium">Memo</th>
                        <th class="px-4 py-3 text-left font-medium">Debit</th>
                        <th class="px-4 py-3 text-left font-medium">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($journalBatch->entries as $entry)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $entry->account?->code }} - {{ $entry->account?->name }}</td>
                            <td class="px-4 py-3">{{ $entry->memo ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $entry->entry_type === 'debit' ? number_format($entry->amount, 2) : '-' }}</td>
                            <td class="px-4 py-3">{{ $entry->entry_type === 'credit' ? number_format($entry->amount, 2) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

