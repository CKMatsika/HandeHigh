@extends('layouts.app')

@section('content')
    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
            .bg-slate-900, .bg-slate-950 { background: white !important; border: 1px solid #333 !important; }
            .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-50 { color: black !important; }
            .text-slate-400, .text-slate-500 { color: #555 !important; }
            .border-slate-800, .border-slate-700 { border-color: #333 !important; }
        }
    </style>

    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">General Ledger</h1>
            <p class="text-xs text-slate-400 mt-1">Detailed journal transaction ledger for {{ $start }} to {{ $end }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Print Ledger</button>
            <a href="{{ route('admin.reports.general-ledger') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Refresh</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="no-print rounded-2xl border border-slate-800 bg-slate-900/80 p-4 mb-4">
        <form method="GET" class="flex flex-wrap gap-4 text-xs">
            <div>
                <label class="block text-slate-400 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $start }}" 
                       class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-slate-200">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $end }}" 
                       class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-slate-200">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">Account</label>
                <select name="account_id" 
                        class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-slate-200">
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
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Ledger Entries -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 overflow-hidden space-y-6 shadow-xl">
        <x-documents.school-header 
            title="General Ledger Detail Register"
            :subtitle="'Period: ' . $start . ' to ' . $end"
            reference="GL-RPT-{{ date('Ymd') }}"
            :date="now()"
        />

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-4 py-2.5 text-left font-medium">Date</th>
                        <th class="px-4 py-2.5 text-left font-medium">Account</th>
                        <th class="px-4 py-2.5 text-left font-medium">Batch Description</th>
                        <th class="px-4 py-2.5 text-left font-medium">Reference</th>
                        <th class="px-4 py-2.5 text-right font-medium">Debit ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Credit ($)</th>
                        <th class="px-4 py-2.5 text-left font-medium">Memo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-2.5 font-medium text-slate-300">
                                {{ $entry->journalBatch->transaction_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="font-mono text-slate-200">{{ $entry->account->code }}</span> - 
                                <span class="text-slate-400">{{ $entry->account->name }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-200">{{ $entry->journalBatch->description }}</td>
                            <td class="px-4 py-2.5 font-mono text-slate-400">{{ $entry->journalBatch->reference_number ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-100 font-medium">
                                {{ $entry->entry_type === 'debit' ? number_format($entry->amount, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-100 font-medium">
                                {{ $entry->entry_type === 'credit' ? number_format($entry->amount, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-400">{{ $entry->memo ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">No journal transactions recorded for the selected criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($entries->hasPages())
            <div class="px-4 py-3 bg-slate-950/60 rounded-xl flex justify-between items-center no-print">
                <div class="text-xs text-slate-400">
                    Showing {{ $entries->firstItem() }} to {{ $entries->lastItem() }} of {{ $entries->total() }} entries
                </div>
                {{ $entries->links() }}
            </div>
        @endif

        <x-documents.school-footer />
    </div>
@endsection
