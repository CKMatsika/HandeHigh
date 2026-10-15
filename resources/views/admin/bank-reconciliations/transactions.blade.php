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
                                    @if($tx->status === 'unmatched' || $tx->status === 'partially_matched')
                                        <button type="button" onclick="showMatchModal({{ $tx->id }}, 'bank', {{ (float) $tx->unmatched_amount }}, '{{ addslashes($tx->description) }}', '{{ $tx->transaction_date->format('M j, Y') }}')" class="text-indigo-400 hover:text-indigo-300 font-medium text-xs">
                                            Match
                                        </button>
                                    @else
                                        @php
                                            $matchedCashbook = $tx->cashbookMatches->first();
                                        @endphp
                                        <button type="button" onclick="showUnmatchModal({{ $tx->id }}, {{ $matchedCashbook ? $matchedCashbook->id : 'null' }})" class="text-red-400 hover:text-red-300 font-medium text-xs">
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
                                    @if($tx->status === 'unmatched' || $tx->status === 'partially_matched')
                                        <button type="button" onclick="showMatchModal({{ $tx->id }}, 'cashbook', {{ (float) $tx->unmatched_amount }}, '{{ addslashes($tx->description) }}', '{{ $tx->transaction_date->format('M j, Y') }}')" class="text-indigo-400 hover:text-indigo-300 font-medium text-xs">
                                            Match
                                        </button>
                                    @else
                                        @php
                                            $matchedBank = $tx->bankMatches->first();
                                        @endphp
                                        <button type="button" onclick="showUnmatchModal({{ $matchedBank ? $matchedBank->id : 'null' }}, {{ $tx->id }})" class="text-red-400 hover:text-red-300 font-medium text-xs">
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
    <div id="matchModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-6 w-full max-w-2xl transform transition-all">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-1.5 bg-indigo-500/20 text-indigo-400 rounded-lg text-sm">🔗</span>
                    <h3 class="text-base font-semibold text-slate-100" id="matchModalTitle">Match Transactions</h3>
                </div>
                <button type="button" onclick="hideMatchModal()" class="text-slate-400 hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <!-- Source Transaction Summary -->
            <div class="mb-4 p-3 bg-slate-950/70 border border-slate-800 rounded-xl flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400" id="sourceTypeLabel">Selected Bank Transaction</span>
                    <div class="text-sm font-semibold text-slate-100 mt-0.5" id="sourceDescLabel">-</div>
                    <div class="text-xs text-slate-400" id="sourceDateLabel">-</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Unmatched Amount</div>
                    <div class="text-lg font-mono font-bold text-indigo-400" id="sourceAmountLabel">$0.00</div>
                </div>
            </div>

            <form id="matchForm" action="{{ route('admin.bank-reconciliations.manual-match') }}" method="POST">
                @csrf
                <input type="hidden" id="matchBankTxId" name="bank_transaction_id">
                <input type="hidden" id="matchCashbookTxId" name="cashbook_transaction_id">
                
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-medium text-slate-300" id="availableMatchesHeading">Select Transaction to Match</label>
                            <span class="text-[11px] text-slate-400" id="matchesCountLabel"></span>
                        </div>
                        <div id="availableMatches" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            <div class="p-4 text-center text-slate-400 text-xs">Loading available matches...</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">Match Amount ($)</label>
                        <input type="number" id="matchAmount" name="match_amount" required step="0.01" min="0.01" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-[11px] text-slate-400 mt-1">Amount to reconcile between the selected pair.</p>
                    </div>

                    <div class="flex justify-end space-x-3 pt-3 border-t border-slate-800">
                        <button type="button" onclick="hideMatchModal()" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-medium rounded-lg hover:bg-slate-700 border border-slate-700 transition">Cancel</button>
                        <button type="submit" id="submitMatchBtn" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-500 transition shadow-lg shadow-indigo-600/30">Confirm Match</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const accountId = {{ $account->id }};
        const availableMatchesUrl = "{{ route('admin.bank-reconciliations.available-matches') }}";

        const localBankUnmatched = [
            @foreach($bankTransactions as $tx)
                @if($tx->status !== 'matched')
                {
                    id: {{ $tx->id }},
                    date: '{{ $tx->transaction_date->format('M j, Y') }}',
                    description: '{{ addslashes($tx->description) }}',
                    reference_number: '{{ addslashes($tx->reference_number ?? '') }}',
                    amount: {{ (float) $tx->amount }},
                    unmatched_amount: {{ (float) $tx->unmatched_amount }},
                    category: '{{ $tx->transaction_type }}',
                    status: '{{ $tx->status }}'
                },
                @endif
            @endforeach
        ];

        const localCashbookUnmatched = [
            @foreach($cashbookTransactions as $tx)
                @if($tx->status !== 'matched')
                {
                    id: {{ $tx->id }},
                    date: '{{ $tx->transaction_date->format('M j, Y') }}',
                    description: '{{ addslashes($tx->description) }}',
                    reference_number: '{{ addslashes($tx->reference_number ?? '') }}',
                    amount: {{ (float) $tx->amount }},
                    unmatched_amount: {{ (float) $tx->unmatched_amount }},
                    category: '{{ addslashes($tx->category_label ?? $tx->category) }}',
                    status: '{{ $tx->status }}'
                },
                @endif
            @endforeach
        ];

        let currentSourceType = '';
        let currentSourceId = null;
        let currentSourceUnmatchedAmount = 0;

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

        function showMatchModal(transactionId, type, unmatchedAmount, description, date) {
            currentSourceType = type;
            currentSourceId = transactionId;
            currentSourceUnmatchedAmount = parseFloat(unmatchedAmount) || 0;

            // Reset inputs
            document.getElementById('matchBankTxId').value = type === 'bank' ? transactionId : '';
            document.getElementById('matchCashbookTxId').value = type === 'cashbook' ? transactionId : '';
            document.getElementById('matchAmount').value = currentSourceUnmatchedAmount.toFixed(2);
            document.getElementById('matchAmount').max = currentSourceUnmatchedAmount;

            // Update UI headers & summaries
            document.getElementById('sourceTypeLabel').textContent = type === 'bank' ? 'Selected Bank Transaction' : 'Selected Cashbook Transaction';
            document.getElementById('sourceDescLabel').textContent = description || 'Transaction #' + transactionId;
            document.getElementById('sourceDateLabel').textContent = date || '';
            document.getElementById('sourceAmountLabel').textContent = '$' + currentSourceUnmatchedAmount.toFixed(2);
            document.getElementById('availableMatchesHeading').textContent = type === 'bank' ? 'Select Matching Cashbook Transaction' : 'Select Matching Bank Transaction';

            // First render local candidates immediately (0ms)
            const localCandidates = type === 'bank' ? localCashbookUnmatched : localBankUnmatched;
            renderCandidates(localCandidates, currentSourceUnmatchedAmount);

            // Open modal
            document.getElementById('matchModal').classList.remove('hidden');

            // Then fetch from server to get full list (including outside current date range)
            fetch(`${availableMatchesUrl}?account_id=${accountId}&type=${type}&transaction_id=${transactionId}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.success && Array.isArray(data.matches)) {
                        renderCandidates(data.matches, currentSourceUnmatchedAmount);
                    }
                })
                .catch(err => {
                    console.warn('Could not fetch remote candidates, using local candidates', err);
                });
        }

        function renderCandidates(candidates, targetAmount) {
            const matchesDiv = document.getElementById('availableMatches');
            const countLabel = document.getElementById('matchesCountLabel');

            if (!candidates || candidates.length === 0) {
                matchesDiv.innerHTML = `
                    <div class="p-6 text-center border border-slate-800 rounded-xl bg-slate-950/40">
                        <p class="text-sm text-slate-300 font-medium">No unmatched ${currentSourceType === 'bank' ? 'cashbook' : 'bank'} transactions found.</p>
                        <p class="text-xs text-slate-500 mt-1">Ensure corresponding transactions are entered for this account.</p>
                    </div>
                `;
                countLabel.textContent = '0 available';
                return;
            }

            countLabel.textContent = `${candidates.length} available`;

            // Sort so exact amount match is first
            const sorted = [...candidates].sort((a, b) => {
                const diffA = Math.abs(a.unmatched_amount - targetAmount);
                const diffB = Math.abs(b.unmatched_amount - targetAmount);
                return diffA - diffB;
            });

            let html = '';
            sorted.forEach((item, index) => {
                const isExact = Math.abs(item.unmatched_amount - targetAmount) < 0.005;
                const isFirst = index === 0;

                html += `
                    <label class="candidate-option flex items-center justify-between p-3 rounded-xl border border-slate-800 bg-slate-950/50 hover:bg-slate-800/60 hover:border-slate-700 cursor-pointer transition">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="candidate_tx_radio" value="${item.id}" data-unmatched="${item.unmatched_amount}" ${isFirst ? 'checked' : ''} onchange="onCandidateSelected(${item.id}, ${item.unmatched_amount})" class="w-4 h-4 text-indigo-600 bg-slate-800 border-slate-600 focus:ring-indigo-500 focus:ring-offset-slate-900">
                            <div>
                                <div class="text-xs font-semibold text-slate-200">${escapeHtml(item.description)}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    <span>${item.date}</span>
                                    ${item.category ? `<span class="mx-1">•</span><span class="capitalize">${escapeHtml(item.category)}</span>` : ''}
                                    ${item.reference_number ? `<span class="mx-1">•</span><span>Ref: ${escapeHtml(item.reference_number)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono text-xs font-bold text-slate-100">$${parseFloat(item.unmatched_amount).toFixed(2)}</div>
                            ${isExact ? `<span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Exact Match</span>` : ''}
                        </div>
                    </label>
                `;
            });

            matchesDiv.innerHTML = html;

            // Select the first one by default
            if (sorted.length > 0) {
                onCandidateSelected(sorted[0].id, sorted[0].unmatched_amount);
            }
        }

        function onCandidateSelected(candidateId, candidateUnmatched) {
            if (currentSourceType === 'bank') {
                document.getElementById('matchCashbookTxId').value = candidateId;
            } else {
                document.getElementById('matchBankTxId').value = candidateId;
            }

            const matchAmount = Math.min(currentSourceUnmatchedAmount, parseFloat(candidateUnmatched) || 0);
            const amountInput = document.getElementById('matchAmount');
            amountInput.value = matchAmount.toFixed(2);
            amountInput.max = matchAmount.toFixed(2);
        }

        function hideMatchModal() {
            document.getElementById('matchModal').classList.add('hidden');
        }

        document.getElementById('matchForm').addEventListener('submit', function(e) {
            const bankId = document.getElementById('matchBankTxId').value;
            const cashbookId = document.getElementById('matchCashbookTxId').value;
            const amount = parseFloat(document.getElementById('matchAmount').value);

            if (!bankId || !cashbookId) {
                e.preventDefault();
                alert('Please select a corresponding transaction to match.');
                return false;
            }

            if (!amount || amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid match amount greater than 0.');
                return false;
            }

            const btn = document.getElementById('submitMatchBtn');
            btn.disabled = true;
            btn.textContent = 'Matching...';
        });

        function showUnmatchModal(bankTxId, cashbookTxId) {
            if (confirm('Are you sure you want to unmatch this transaction?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.bank-reconciliations.unmatch") }}';
                
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);
                
                if (bankTxId) {
                    const bInput = document.createElement('input');
                    bInput.type = 'hidden';
                    bInput.name = 'bank_transaction_id';
                    bInput.value = bankTxId;
                    form.appendChild(bInput);
                }
                
                if (cashbookTxId) {
                    const cInput = document.createElement('input');
                    cInput.type = 'hidden';
                    cInput.name = 'cashbook_transaction_id';
                    cInput.value = cashbookTxId;
                    form.appendChild(cInput);
                }
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, m => map[m]);
        }

        // Auto-refresh every 30 seconds only if no modal is open
        setInterval(() => {
            const modals = ['matchModal', 'addBankTxModal', 'addCashbookTxModal'];
            const isAnyModalOpen = modals.some(id => {
                const el = document.getElementById(id);
                return el && !el.classList.contains('hidden');
            });
            if (!isAnyModalOpen) {
                window.location.reload();
            }
        }, 30000);
    </script>
@endsection
