@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">New Journal</h1>
            <p class="text-xs text-slate-400 mt-1">Post a manual journal entry.</p>
        </div>
        <a href="{{ route('admin.journals.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.journals.store') }}" class="space-y-4 text-sm" id="journal-form">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Transaction Date</label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Reference</label>
                    <input name="reference_number" value="{{ old('reference_number') }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Description</label>
                    <input name="description" value="{{ old('description') }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/40">
                <div class="flex items-center justify-between px-4 py-3 text-xs text-slate-300">
                    <div class="font-semibold">Entries</div>
                    <button type="button" onclick="addEntryRow()" class="rounded-full border border-slate-700 px-3 py-1 hover:bg-slate-800/60">Add Line</button>
                </div>
                <div class="divide-y divide-slate-800" id="entries-container">
                    @php $oldEntries = old('entries', [['account_id' => null, 'entry_type' => 'debit', 'amount' => null, 'memo' => null], ['account_id' => null, 'entry_type' => 'credit', 'amount' => null, 'memo' => null]]); @endphp
                    @foreach($oldEntries as $i => $entry)
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-2 px-4 py-3">
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Account</label>
                                <select name="entries[{{ $i }}][account_id]" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                                    <option value="">Select account</option>
                                    @foreach($accounts as $acct)
                                        <option value="{{ $acct->id }}" @selected($entry['account_id'] == $acct->id)>{{ $acct->code }} - {{ $acct->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Type</label>
                                <select name="entries[{{ $i }}][entry_type]" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                                    <option value="debit" @selected(($entry['entry_type'] ?? '') === 'debit')>Debit</option>
                                    <option value="credit" @selected(($entry['entry_type'] ?? '') === 'credit')>Credit</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Amount</label>
                                <input type="number" step="0.01" min="0" name="entries[{{ $i }}][amount]" value="{{ $entry['amount'] ?? '' }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[11px] text-slate-400 mb-1">Memo</label>
                                <input name="entries[{{ $i }}][memo]" value="{{ $entry['memo'] ?? '' }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.journals.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Post Journal</button>
            </div>
        </form>
    </div>

    <script>
        let entryIndex = {{ count($oldEntries) }};
        function addEntryRow() {
            const container = document.getElementById('entries-container');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 md:grid-cols-5 gap-2 px-4 py-3 border-t border-slate-800';
            row.innerHTML = `
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Account</label>
                    <select name="entries[${entryIndex}][account_id]" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        <option value=\"\">Select account</option>
                        @foreach($accounts as $acct)
                            <option value=\"{{ $acct->id }}\">{{ $acct->code }} - {{ $acct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Type</label>
                    <select name="entries[${entryIndex}][entry_type]" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                        <option value=\"debit\">Debit</option>
                        <option value=\"credit\">Credit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Amount</label>
                    <input type="number" step="0.01" min="0" name="entries[${entryIndex}][amount]" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] text-slate-400 mb-1">Memo</label>
                    <input name="entries[${entryIndex}][memo]" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
            `;
            container.appendChild(row);
            entryIndex++;
        }
    </script>
@endsection

