@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Bank Reconciliations</h1>
            <p class="text-xs text-slate-400 mt-1">Advanced bank reconciliation with auto-matching and transaction management.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.bank-reconciliations.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">New Reconciliation</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-200 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-200 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Quick Actions Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <h3 class="text-sm font-medium text-slate-200 mb-3">Quick Actions</h3>
            <div class="space-y-2">
                <button onclick="showImportModal()" class="w-full px-3 py-2 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 transition">
                    📁 Import Bank Statement
                </button>
                <button onclick="showAutoMatchModal()" class="w-full px-3 py-2 bg-emerald-600 text-white text-xs rounded hover:bg-emerald-700 transition">
                    🤖 Auto-Match Transactions
                </button>
                <button onclick="showBankChargeModal()" class="w-full px-3 py-2 bg-amber-600 text-white text-xs rounded hover:bg-amber-700 transition">
                    💳 Add Bank Charge
                </button>
            </div>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <h3 class="text-sm font-medium text-slate-200 mb-3">Bank Accounts</h3>
            @php
                $bankAccounts = \App\Models\Account::where('school_id', auth()->user()->school->id)
                    ->where('type', 'asset')
                    ->where(function($query) {
                        $query->where('category', 'bank')->orWhere('code', 'like', '13%');
                    })
                    ->active()
                    ->orderBy('code')
                    ->get();
            @endphp
            <div class="space-y-1">
                @foreach($bankAccounts as $account)
                    <a href="{{ route('admin.bank-reconciliations.transactions', $account->id) }}" 
                       class="block px-2 py-1 text-xs text-slate-300 hover:bg-slate-700 rounded transition">
                        {{ $account->code }} - {{ $account->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <h3 class="text-sm font-medium text-slate-200 mb-3">Recent Activity</h3>
            @php
                $recentBankTx = \App\Models\BankTransaction::whereHas('account', function($q) {
                    $q->where('school_id', auth()->user()->school->id);
                })->latest()->take(3)->get();
            @endphp
            <div class="space-y-1">
                @foreach($recentBankTx as $tx)
                    <div class="text-xs text-slate-400">
                        <div class="font-medium text-slate-200">{{ $tx->description }}</div>
                        <div>{{ $tx->transaction_date->format('M j') }} - ${{ number_format($tx->amount, 2) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <h3 class="text-sm font-medium text-slate-200 mb-3">Matching Status</h3>
            @php
                $bankStats = [
                    'unmatched' => \App\Models\BankTransaction::whereHas('account', function($q) {
                        $q->where('school_id', auth()->user()->school->id);
                    })->unmatched()->count(),
                    'matched' => \App\Models\BankTransaction::whereHas('account', function($q) {
                        $q->where('school_id', auth()->user()->school->id);
                    })->matched()->count(),
                ];
            @endphp
            <div class="space-y-2">
                <div class="flex justify-between text-xs">
                    <span class="text-slate-400">Unmatched:</span>
                    <span class="text-red-400 font-medium">{{ $bankStats['unmatched'] }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-400">Matched:</span>
                    <span class="text-emerald-400 font-medium">{{ $bankStats['matched'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Bank Account</th>
                        <th class="px-4 py-3 text-right font-medium">Book Balance</th>
                        <th class="px-4 py-3 text-right font-medium">Bank Balance</th>
                        <th class="px-4 py-3 text-right font-medium">Difference</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($recons as $rec)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $rec->reconciliation_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <div>
                                    <div class="font-medium">{{ $rec->bankAccount?->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-400">{{ $rec->bankAccount?->code ?? '-' }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-mono">${{ number_format($rec->book_balance, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono">${{ number_format($rec->bank_balance, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono {{ $rec->reconciled_balance < 0 ? 'text-red-400' : ($rec->reconciled_balance > 0 ? 'text-emerald-400' : 'text-slate-400') }}">
                                ${{ number_format(abs($rec->reconciled_balance), 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs {{ $rec->status === 'completed' ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">
                                    {{ ucfirst($rec->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.bank-reconciliations.show', $rec) }}" class="p-1 text-slate-400 hover:text-white" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @if($rec->bankAccount)
                                        <a href="{{ route('admin.bank-reconciliations.transactions', $rec->bankAccount->id) }}" class="p-1 text-slate-400 hover:text-white" title="Transactions">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div>No reconciliations found.</div>
                                    <a href="{{ route('admin.bank-reconciliations.create') }}" class="text-indigo-400 hover:text-indigo-300">Create your first reconciliation</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-800/50">
            {{ $recons->links() }}
        </div>
    </div>

    <!-- Import Statement Modal -->
    <div id="importModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Import Bank Statement</h3>
            <form action="{{ route('admin.bank-reconciliations.import-statement') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Bank Account</label>
                        <select name="account_id" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Statement File (CSV)</label>
                        <input type="file" name="statement_file" accept=".csv" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="skip_header" value="1" checked class="rounded border-slate-600 bg-slate-700">
                            <span class="text-xs text-slate-300">Skip header row</span>
                        </label>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="hideImportModal()" class="px-4 py-2 bg-slate-600 text-white text-xs rounded hover:bg-slate-500">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">Import</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Auto Match Modal -->
    <div id="autoMatchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Auto-Match Transactions</h3>
            <form action="{{ route('admin.bank-reconciliations.auto-match') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Bank Account</label>
                        <select name="account_id" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Confidence Threshold</label>
                        <select name="confidence_threshold" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                            <option value="0.9">Very High (90%)</option>
                            <option value="0.8" selected>High (80%)</option>
                            <option value="0.7">Medium (70%)</option>
                            <option value="0.5">Low (50%)</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="hideAutoMatchModal()" class="px-4 py-2 bg-slate-600 text-white text-xs rounded hover:bg-slate-500">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs rounded hover:bg-emerald-700">Auto-Match</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bank Charge Modal -->
    <div id="bankChargeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Add Bank Charge</h3>
            <form action="{{ route('admin.bank-reconciliations.add-bank-charge') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Bank Account</label>
                        <select name="account_id" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Date</label>
                        <input type="date" name="date" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                        <input type="text" name="description" required placeholder="Bank service charge" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Amount</label>
                        <input type="number" name="amount" required step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Reference (Optional)</label>
                        <input type="text" name="reference" placeholder="Transaction reference" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="hideBankChargeModal()" class="px-4 py-2 bg-slate-600 text-white text-xs rounded hover:bg-slate-500">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white text-xs rounded hover:bg-amber-700">Add Charge</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
        }
        function hideImportModal() {
            document.getElementById('importModal').classList.add('hidden');
        }
        function showAutoMatchModal() {
            document.getElementById('autoMatchModal').classList.remove('hidden');
        }
        function hideAutoMatchModal() {
            document.getElementById('autoMatchModal').classList.add('hidden');
        }
        function showBankChargeModal() {
            document.getElementById('bankChargeModal').classList.remove('hidden');
        }
        function hideBankChargeModal() {
            document.getElementById('bankChargeModal').classList.add('hidden');
        }
    </script>
@endsection

