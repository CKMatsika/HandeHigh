@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Bank Reconciliation - {{ $account->name }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $account->code }} - Transaction matching and management</p>
        </div>
        <a href="{{ route('admin.bank-reconciliations.index') }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Back to Reconciliations</a>
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <h3 class="text-xs font-medium text-slate-400 mb-2">Bank Transactions</h3>
            <div class="text-lg font-semibold text-slate-100">{{ $summary['bank_summary']['total_transactions'] }}</div>
            <div class="text-xs text-slate-400 mt-1">${{ number_format($summary['bank_summary']['total_amount'], 2) }}</div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <h3 class="text-xs font-medium text-slate-400 mb-2">Cashbook Transactions</h3>
            <div class="text-lg font-semibold text-slate-100">{{ $summary['cashbook_summary']['total_transactions'] }}</div>
            <div class="text-xs text-slate-400 mt-1">${{ number_format($summary['cashbook_summary']['total_amount'], 2) }}</div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <h3 class="text-xs font-medium text-slate-400 mb-2">Matched Amount</h3>
            <div class="text-lg font-semibold text-emerald-400">${{ number_format($summary['bank_summary']['matched_amount'], 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $summary['bank_summary']['matched'] }} transactions</div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <h3 class="text-xs font-medium text-slate-400 mb-2">Unmatched Amount</h3>
            <div class="text-lg font-semibold text-red-400">${{ number_format($summary['bank_summary']['unmatched_amount'], 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $summary['bank_summary']['unmatched'] }} transactions</div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4 mb-6">
        <form method="GET" class="flex items-end space-x-4">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">Filter</button>
        </form>
    </div>

    <!-- Transaction Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bank Transactions -->
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-100">Bank Transactions</h3>
                <button onclick="showAddBankTxModal()" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">
                    + Add Transaction
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-950/60 text-slate-300">
                        <tr>
                            <th class="px-2 py-2 text-left">Date</th>
                            <th class="px-2 py-2 text-left">Description</th>
                            <th class="px-2 py-2 text-right">Amount</th>
                            <th class="px-2 py-2 text-center">Status</th>
                            <th class="px-2 py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($bankTransactions as $tx)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-2 py-2">{{ $tx->transaction_date->format('M j') }}</td>
                                <td class="px-2 py-2">
                                    <div class="truncate max-w-xs" title="{{ $tx->description }}">
                                        {{ $tx->description }}
                                    </div>
                                    @if($tx->reference_number)
                                        <div class="text-xs text-slate-400">{{ $tx->reference_number }}</div>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-right font-mono">${{ number_format($tx->amount, 2) }}</td>
                                <td class="px-2 py-2 text-center">
                                    <span class="px-1 py-0.5 rounded-full text-xs bg-{{ $tx->status_color }}-900/50 text-{{ $tx->status_color }}-300">
                                        {{ $tx->status_label }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    @if($tx->status === 'unmatched')
                                        <button onclick="showMatchModal({{ $tx->id }}, 'bank')" class="text-indigo-400 hover:text-indigo-300 text-xs">
                                            Match
                                        </button>
                                    @else
                                        <button onclick="showUnmatchModal({{ $tx->id }})" class="text-red-400 hover:text-red-300 text-xs">
                                            Unmatch
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cashbook Transactions -->
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-100">Cashbook Transactions</h3>
                <button onclick="showAddCashbookTxModal()" class="px-3 py-1 bg-emerald-600 text-white text-xs rounded hover:bg-emerald-700">
                    + Add Transaction
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-950/60 text-slate-300">
                        <tr>
                            <th class="px-2 py-2 text-left">Date</th>
                            <th class="px-2 py-2 text-left">Description</th>
                            <th class="px-2 py-2 text-right">Amount</th>
                            <th class="px-2 py-2 text-center">Status</th>
                            <th class="px-2 py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($cashbookTransactions as $tx)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-2 py-2">{{ $tx->transaction_date->format('M j') }}</td>
                                <td class="px-2 py-2">
                                    <div class="truncate max-w-xs" title="{{ $tx->description }}">
                                        {{ $tx->description }}
                                    </div>
                                    <div class="text-xs text-slate-400">{{ $tx->category_label }}</div>
                                </td>
                                <td class="px-2 py-2 text-right font-mono">${{ number_format($tx->amount, 2) }}</td>
                                <td class="px-2 py-2 text-center">
                                    <span class="px-1 py-0.5 rounded-full text-xs bg-{{ $tx->status_color }}-900/50 text-{{ $tx->status_color }}-300">
                                        {{ $tx->status_label }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    @if($tx->status === 'unmatched')
                                        <button onclick="showMatchModal({{ $tx->id }}, 'cashbook')" class="text-indigo-400 hover:text-indigo-300 text-xs">
                                            Match
                                        </button>
                                    @else
                                        <button onclick="showUnmatchModal({{ $tx->id }})" class="text-red-400 hover:text-red-300 text-xs">
                                            Unmatch
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Bank Transaction Modal -->
    <div id="addBankTxModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Add Bank Transaction</h3>
            <form action="{{ route('admin.bank-reconciliations.add-bank-transaction') }}" method="POST">
                @csrf
                <input type="hidden" name="account_id" value="{{ $account->id }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Date</label>
                        <input type="date" name="date" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                        <input type="text" name="description" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Amount</label>
                        <input type="number" name="amount" required step="0.01" min="0" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Type</label>
                        <select name="transaction_type" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Reference (Optional)</label>
                        <input type="text" name="reference" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="hideAddBankTxModal()" class="px-4 py-2 bg-slate-600 text-white text-xs rounded hover:bg-slate-500">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Cashbook Transaction Modal -->
    <div id="addCashbookTxModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Add Cashbook Transaction</h3>
            <form action="{{ route('admin.bank-reconciliations.add-cashbook-transaction') }}" method="POST">
                @csrf
                <input type="hidden" name="account_id" value="{{ $account->id }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Date</label>
                        <input type="date" name="date" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                        <input type="text" name="description" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Amount</label>
                        <input type="number" name="amount" required step="0.01" min="0" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Type</label>
                        <select name="transaction_type" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Category</label>
                        <select name="category" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                            <option value="transfer">Transfer</option>
                            <option value="bank_charge">Bank Charge</option>
                            <option value="interest">Interest</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Reference (Optional)</label>
                        <input type="text" name="reference" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="hideAddCashbookTxModal()" class="px-4 py-2 bg-slate-600 text-white text-xs rounded hover:bg-slate-500">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs rounded hover:bg-emerald-700">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Match Transactions Modal -->
    <div id="matchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-6 w-full max-w-2xl">
            <h3 class="text-lg font-semibold text-slate-100 mb-4">Match Transactions</h3>
            <form id="matchForm" method="POST">
                @csrf
                <input type="hidden" id="matchBankTxId" name="bank_transaction_id">
                <input type="hidden" id="matchCashbookTxId" name="cashbook_transaction_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Match Amount</label>
                        <input type="number" id="matchAmount" name="match_amount" required step="0.01" min="0" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-200">
                    </div>
                    <div id="availableMatches" class="space-y-2 max-h-60 overflow-y-auto">
                        <!-- Available matches will be loaded here -->
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="hideMatchModal()" class="px-4 py-2 bg-slate-600 text-white text-xs rounded hover:bg-slate-500">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">Match</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentMatchType = '';
        let currentTransactionId = null;

        function showAddBankTxModal() {
            document.getElementById('addBankTxModal').classList.remove('hidden');
        }
        function hideAddBankTxModal() {
            document.getElementById('addBankTxModal').classList.add('hidden');
        }

        function showAddCashbookTxModal() {
            document.getElementById('addCashbookTxModal').classList.remove('hidden');
        }
        function hideAddCashbookTxModal() {
            document.getElementById('addCashbookTxModal').classList.add('hidden');
        }

        function showMatchModal(transactionId, type) {
            currentMatchType = type;
            currentTransactionId = transactionId;
            
            if (type === 'bank') {
                document.getElementById('matchBankTxId').value = transactionId;
                loadCashbookMatches(transactionId);
            } else {
                document.getElementById('matchCashbookTxId').value = transactionId;
                loadBankMatches(transactionId);
            }
            
            document.getElementById('matchModal').classList.remove('hidden');
        }

        function hideMatchModal() {
            document.getElementById('matchModal').classList.add('hidden');
        }

        function loadCashbookMatches(bankTxId) {
            // This would be an AJAX call to load available cashbook transactions
            // For now, we'll show a placeholder
            const matchesDiv = document.getElementById('availableMatches');
            matchesDiv.innerHTML = '<p class="text-slate-400 text-sm">Loading available matches...</p>';
        }

        function loadBankMatches(cashbookTxId) {
            // This would be an AJAX call to load available bank transactions
            // For now, we'll show a placeholder
            const matchesDiv = document.getElementById('availableMatches');
            matchesDiv.innerHTML = '<p class="text-slate-400 text-sm">Loading available matches...</p>';
        }

        function showUnmatchModal(transactionId) {
            if (confirm('Are you sure you want to unmatch this transaction?')) {
                // Submit unmatch form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.bank-reconciliations.unmatch") }}';
                
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);
                
                const bankTxId = document.createElement('input');
                bankTxId.type = 'hidden';
                bankTxId.name = 'bank_transaction_id';
                bankTxId.value = transactionId;
                form.appendChild(bankTxId);
                
                // You would need to also pass the cashbook transaction ID
                // This is simplified for demonstration
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-refresh every 30 seconds to show updated matching status
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
@endsection
