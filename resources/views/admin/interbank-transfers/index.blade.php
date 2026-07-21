@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Interbank Transfers</h1>
            <p class="text-xs text-slate-400 mt-1">Move funds between bank accounts.</p>
        </div>
        <a href="{{ route('admin.interbank-transfers.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">New Transfer</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Number</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">From</th>
                        <th class="px-4 py-3 text-left font-medium">To</th>
                        <th class="px-4 py-3 text-left font-medium">Amount</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($transfers as $transfer)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $transfer->transfer_number }}</td>
                            <td class="px-4 py-3">{{ $transfer->transfer_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                            <div>
                                <div class="font-medium">{{ $transfer->fromBankAccount?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $transfer->fromBankAccount?->code ?? '-' }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <div class="font-medium">{{ $transfer->toBankAccount?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $transfer->toBankAccount?->code ?? '-' }}</div>
                            </div>
                        </td>
                            <td class="px-4 py-3">{{ number_format($transfer->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs {{ $transfer->status === 'completed' ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">{{ ucfirst($transfer->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No transfers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $transfers->links() }}
        </div>
    </div>
@endsection

