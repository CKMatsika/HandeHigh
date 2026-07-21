@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">General Ledger</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $start }} to {{ $end }}</p>
        </div>
        <a href="{{ route('admin.reports.general-ledger') }}" class="text-xs text-slate-300 hover:text-white">Refresh</a>
    </div>

    <!-- Filters -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 mb-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div>
                <label class="block text-xs text-slate-400 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $start }}" 
                       class="px-3 py-1 text-xs bg-slate-800 border border-slate-700 rounded text-slate-200">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $end }}" 
                       class="px-3 py-1 text-xs bg-slate-800 border border-slate-700 rounded text-slate-200">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Account</label>
                <select name="account_id" 
                        class="px-3 py-1 text-xs bg-slate-800 border border-slate-700 rounded text-slate-200">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $id => $name)
                        <option value="{{ $id }}" {{ $accountId == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="px-4 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Ledger Entries -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Account</th>
                        <th class="px-4 py-3 text-left font-medium">Description</th>
                        <th class="px-4 py-3 text-left font-medium">Reference</th>
                        <th class="px-4 py-3 text-right font-medium">Debit</th>
                        <th class="px-4 py-3 text-right font-medium">Credit</th>
                        <th class="px-4 py-3 text-left font-medium">Memo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($entries as $entry)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                {{ $entry->journalBatch->transaction_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono">{{ $entry->account->code }}</span><br>
                                <span class="text-slate-400">{{ $entry->account->name }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $entry->journalBatch->description }}</td>
                            <td class="px-4 py-3 font-mono">{{ $entry->journalBatch->reference_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                {{ $entry->entry_type === 'debit' ? number_format($entry->amount, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ $entry->entry_type === 'credit' ? number_format($entry->amount, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ $entry->memo ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-4 py-3 bg-slate-950/60 flex justify-between items-center">
            <div class="text-xs text-slate-400">
                Showing {{ $entries->firstItem() }} to {{ $entries->lastItem() }} 
                of {{ $entries->total() }} entries
            </div>
            {{ $entries->links() }}
        </div>
    </div>
@endsection
