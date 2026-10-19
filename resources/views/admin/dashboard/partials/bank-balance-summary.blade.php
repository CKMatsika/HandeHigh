<!-- Bank Balance Summary Component -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 backdrop-blur-xl p-6 space-y-6 shadow-xl relative overflow-hidden" id="bank-balances-section">
    <!-- Subtle Ambient Glow -->
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Section Header & Source Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                <h2 class="text-xl font-bold text-slate-50 tracking-tight">Bank Balance Summary</h2>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">Liquidity & Cash Accounts</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Real-time school bank balances, cash reserves, recent cashbook postings and reconciliation status</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.bank-accounts.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-slate-200 text-xs font-medium transition shadow-sm">
                <span>All Bank Accounts</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
            <a href="{{ route('admin.cashbook.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-700/50 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 text-xs font-medium transition shadow-sm">
                <span>General Cashbook</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- Liquidity KPI Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Net Combined Liquidity -->
        <div class="p-5 rounded-xl border border-slate-800 bg-gradient-to-br from-emerald-500/15 via-slate-900 to-slate-950 shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Total Liquid Reserves</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold">
                    {{ $bankBalanceSummary['active_accounts_count'] }} Accounts
                </span>
            </div>
            <div class="mt-3">
                <p class="text-3xl font-black text-slate-50 tracking-tight">
                    ${{ number_format($bankBalanceSummary['net_liquidity'], 2) }}
                </p>
                <div class="flex items-center gap-2 mt-2 flex-wrap text-xs text-slate-400">
                    @foreach($bankBalanceSummary['totals_by_currency'] as $curr => $cTotal)
                        <span class="px-2 py-0.5 rounded bg-slate-800/80 border border-slate-700 text-slate-300 font-mono text-[11px]">
                            {{ $curr }}: {{ number_format($cTotal, 2) }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Bank Accounts Total -->
        <div class="p-5 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Commercial Bank Accounts</span>
                <span class="text-[10px] text-blue-400">In Bank</span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-blue-400">
                    ${{ number_format($bankBalanceSummary['total_bank_balance'], 2) }}
                </p>
                <p class="text-xs text-slate-500 mt-1">Operating, Projects, and Special Purpose bank accounts</p>
            </div>
        </div>

        <!-- Cash-on-Hand / Petty Cash Total -->
        <div class="p-5 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Cash on Hand / Petty Cash</span>
                <span class="text-[10px] text-amber-400">Vault & Register</span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-amber-400">
                    ${{ number_format($bankBalanceSummary['total_cash_balance'], 2) }}
                </p>
                <p class="text-xs text-slate-500 mt-1">Disbursements, petty cash floats, and counter cash</p>
            </div>
        </div>
    </div>

    <!-- Bank Accounts Table with Source Links -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-200">School Bank & Cash Accounts</h3>
            <span class="text-xs text-slate-400">Direct drill-down into account ledgers & cashbook</span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/40">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="text-slate-400 bg-slate-950/80 uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Institution & Account</th>
                        <th class="px-4 py-3">Account Number / Code</th>
                        <th class="px-4 py-3 text-center">Currency</th>
                        <th class="px-4 py-3 text-center">Activity (7 Days)</th>
                        <th class="px-4 py-3 text-right">Available Balance</th>
                        <th class="px-4 py-3 text-right">Drill Down</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($bankBalanceSummary['accounts'] as $acc)
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl {{ $acc['is_bank'] ? 'bg-blue-500/20 text-blue-400' : 'bg-amber-500/20 text-amber-400' }} flex items-center justify-center font-bold text-xs shrink-0">
                                        @if($acc['is_bank'])
                                            🏦
                                        @else
                                            💵
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-100 flex items-center gap-2">
                                            <span>{{ $acc['bank_name'] }}</span>
                                            <span class="text-[10px] px-2 py-0.2 rounded bg-slate-800 text-slate-300 font-normal">
                                                {{ $acc['name'] }}
                                            </span>
                                        </p>
                                        <p class="text-[11px] text-slate-400">Branch: {{ $acc['branch'] }} • Last Activity: {{ $acc['last_transaction_date'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-300">
                                <div>{{ $acc['account_number'] }}</div>
                                <span class="text-[10px] text-slate-500">GL Code: {{ $acc['code'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded bg-slate-800 font-mono text-xs font-semibold text-slate-200">
                                    {{ $acc['currency'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($acc['recent_transactions_count'] > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300">
                                        {{ $acc['recent_transactions_count'] }} transactions
                                    </span>
                                @else
                                    <span class="text-slate-500 text-[11px] italic">No new entries</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-base font-extrabold text-emerald-400 font-mono">
                                    ${{ $acc['formatted_balance'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" 
                                            onclick='LeadershipDrilldown.open("bank_account", { account_id: "{{ $acc['id'] }}" }, "{{ $acc['bank_name'] }} - {{ $acc['name'] }}", "Recent transaction stream and balance audit", null, "{{ $acc['view_url'] }}")'
                                            class="px-2.5 py-1 text-[11px] rounded bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-medium transition border border-emerald-500/30">
                                        Inspect Ledger
                                    </button>
                                    <a href="{{ $acc['cashbook_url'] }}" 
                                       title="View Cashbook entries"
                                       class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 italic">No bank or cash accounts configured</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Cashbook Transactions Feed -->
    @if(count($bankBalanceSummary['recent_transactions']) > 0)
        <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-200">Recent Bank & Cashbook Postings</h3>
                <a href="{{ route('admin.cashbook.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300 transition">View Full Cashbook &rarr;</a>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/40">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="text-slate-400 bg-slate-950/80 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="px-4 py-2.5">Date</th>
                            <th class="px-4 py-2.5">Reference</th>
                            <th class="px-4 py-2.5">Account</th>
                            <th class="px-4 py-2.5">Description</th>
                            <th class="px-4 py-2.5 text-center">Type</th>
                            <th class="px-4 py-2.5 text-right">Amount</th>
                            <th class="px-4 py-2.5 text-right">Balance After</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($bankBalanceSummary['recent_transactions'] as $tx)
                            @php($isIncome = strtolower($tx['transaction_type']) === 'income')
                            <tr class="hover:bg-slate-850/40 transition">
                                <td class="px-4 py-2.5 text-slate-400">{{ $tx['transaction_date'] }}</td>
                                <td class="px-4 py-2.5 font-mono text-slate-300">{{ $tx['reference'] }}</td>
                                <td class="px-4 py-2.5 font-semibold text-slate-200">{{ $tx['account_name'] }}</td>
                                <td class="px-4 py-2.5 text-slate-300">{{ $tx['description'] }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    @if($isIncome)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400">Income</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400">Expense</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right font-bold {{ $isIncome ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $isIncome ? '+' : '-' }}${{ $tx['formatted_amount'] }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-slate-400">${{ $tx['balance_after'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
