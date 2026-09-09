<!-- Student Profile: Finance & Debtor Account Tab -->
<div class="space-y-6">
    <!-- Debtor Account Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Outstanding Debtor Balance -->
        @php
            $balance = $financeSummary['outstanding_balance'] ?? 0;
            $balanceColor = $balance > 0 ? 'rose' : ($balance < 0 ? 'amber' : 'emerald');
            $balanceStatus = $balance > 0 ? 'Outstanding Balance' : ($balance < 0 ? 'Credit Balance (Overpaid)' : 'Account Cleared');
        @endphp
        <div class="rounded-2xl border border-{{ $balanceColor }}-500/30 bg-{{ $balanceColor }}-950/20 p-5 backdrop-blur-sm relative overflow-hidden shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-{{ $balanceColor }}-300">Debtor Balance</span>
                <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-{{ $balanceColor }}-500/20 text-{{ $balanceColor }}-400 border border-{{ $balanceColor }}-500/30">
                    {{ $balanceStatus }}
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-50">
                    ${{ number_format(abs($balance), 2) }}
                    @if($balance < 0) <span class="text-xs text-amber-400 font-sans">(CR)</span> @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    {{ $financeSummary['unpaid_count'] > 0 ? $financeSummary['unpaid_count'] . ' unpaid invoice(s)' : 'All invoices settled' }}
                </p>
            </div>
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-{{ $balanceColor }}-500/10 rounded-full blur-xl pointer-events-none"></div>
        </div>

        <!-- Total Invoiced (Debits) -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Invoiced</span>
                <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5L19 7.5V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-100">
                    ${{ number_format($financeSummary['total_invoiced'], 2) }}
                </div>
                <p class="text-xs text-slate-400 mt-1">{{ $financeSummary['invoices_count'] }} total invoice(s) issued</p>
            </div>
        </div>

        <!-- Total Paid / Receipts (Credits) -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Paid & Receipts</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-emerald-400">
                    ${{ number_format($financeSummary['total_paid'], 2) }}
                </div>
                <p class="text-xs text-slate-400 mt-1">{{ $financeSummary['payments_count'] }} payment transaction(s)</p>
            </div>
        </div>

        <!-- Credit Notes / Adjustments -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Credit Adjustments</span>
                <div class="w-7 h-7 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-purple-400">
                    ${{ number_format($financeSummary['total_credits'], 2) }}
                </div>
                <p class="text-xs text-slate-400 mt-1">{{ count($creditNotes) }} credit note(s) applied</p>
            </div>
        </div>
    </div>

    <!-- Quick Financial Actions & Print Toolbar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 flex flex-wrap items-center justify-between gap-3 shadow-md">
        <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-slate-400">Quick Actions:</span>
            <a href="{{ route('admin.invoices.create') }}?student_id={{ $student->id }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium transition shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Invoice
            </a>
            @if($invoices->where('balance', '>', 0)->isNotEmpty())
                @php $firstUnpaid = $invoices->firstWhere('balance', '>', 0); @endphp
                <a href="{{ route('admin.payments.create', $firstUnpaid) }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Record Payment
                </a>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button type="button" 
                    onclick="printStudentStatement()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-sm">
                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Statement
            </button>
            <a href="{{ route('admin.students.statement.download', ['student' => $student, 'academic_year' => request('statement_year', request('academic_year', date('Y'))), 'term' => request('statement_term', request('term'))]) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-sm">
                <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5L19 7.5V19a2 2 0 01-2 2z"/></svg>
                PDF Statement
            </a>
            <a href="{{ route('admin.reports.general-ledger', ['search' => $student->admission_number ?? ($student->first_name . ' ' . $student->last_name)]) }}" 
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-sm">
                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Full School GL
            </a>
        </div>
    </div>

    <!-- Section 1: Invoices Register -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    Student Invoices
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Invoices billed to this student with direct access to General Ledger postings</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">{{ $invoices->count() }} invoice(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-3 py-2.5 text-left font-medium">Invoice #</th>
                        <th class="px-3 py-2.5 text-left font-medium">Date</th>
                        <th class="px-3 py-2.5 text-left font-medium">Period</th>
                        <th class="px-3 py-2.5 text-right font-medium">Total ($)</th>
                        <th class="px-3 py-2.5 text-right font-medium">Paid ($)</th>
                        <th class="px-3 py-2.5 text-right font-medium">Balance ($)</th>
                        <th class="px-3 py-2.5 text-center font-medium">Status</th>
                        <th class="px-3 py-2.5 text-center font-medium">GL Transaction</th>
                        <th class="px-3 py-2.5 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($invoices as $inv)
                        @php
                            $paidAmount = (float) $inv->allocations->sum('amount');
                            $statusMap = [
                                'paid' => ['color' => 'emerald', 'label' => 'Paid'],
                                'partial' => ['color' => 'amber', 'label' => 'Partial'],
                                'unpaid' => ['color' => 'rose', 'label' => 'Unpaid'],
                                'overdue' => ['color' => 'red', 'label' => 'Overdue'],
                                'cancelled' => ['color' => 'slate', 'label' => 'Cancelled'],
                            ];
                            $st = $statusMap[$inv->status] ?? ['color' => 'slate', 'label' => ucfirst($inv->status)];
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-2.5 font-mono font-medium text-slate-200">
                                <a href="{{ route('admin.invoices.show', $inv) }}" class="text-indigo-400 hover:underline">
                                    {{ $inv->number }}
                                </a>
                            </td>
                            <td class="px-3 py-2.5 text-slate-400">
                                {{ $inv->issued_at ? $inv->issued_at->format('d M Y') : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-300">
                                {{ $inv->academic_year ?? '' }} {{ $inv->term ? '- ' . $inv->term : '' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-medium text-slate-100">
                                ${{ number_format($inv->total_amount, 2) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-emerald-400 font-medium">
                                ${{ number_format($paidAmount, 2) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold {{ $inv->balance > 0 ? 'text-rose-400' : 'text-slate-400' }}">
                                ${{ number_format($inv->balance, 2) }}
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-{{ $st['color'] }}-500/20 text-{{ $st['color'] }}-400 border border-{{ $st['color'] }}-500/30">
                                    {{ $st['label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <a href="{{ route('admin.reports.general-ledger', ['search' => $inv->number]) }}" 
                                   title="View General Ledger account entries for {{ $inv->number }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[11px] font-medium hover:bg-blue-500/20 transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    GL Entry
                                </a>
                            </td>
                            <td class="px-3 py-2.5 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('admin.invoices.show', $inv) }}" 
                                   class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] transition">
                                    View
                                </a>
                                <a href="{{ route('admin.invoices.print', $inv) }}" 
                                   target="_blank"
                                   class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-300 text-[11px] transition">
                                    Print
                                </a>
                                @if($inv->balance > 0)
                                    <a href="{{ route('admin.payments.create', $inv) }}" 
                                       class="px-2 py-1 rounded-lg bg-emerald-600/30 hover:bg-emerald-600/50 text-emerald-300 border border-emerald-500/30 text-[11px] font-medium transition">
                                        Pay
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-slate-500">No invoices recorded for this student.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Receipts & Fee Payments -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Receipts & Fee Payments
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Recorded fee receipts with direct General Ledger account tracking</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">{{ $payments->count() }} receipt(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-3 py-2.5 text-left font-medium">Receipt / Ref #</th>
                        <th class="px-3 py-2.5 text-left font-medium">Payment Date</th>
                        <th class="px-3 py-2.5 text-left font-medium">Method</th>
                        <th class="px-3 py-2.5 text-left font-medium">Allocated Invoice</th>
                        <th class="px-3 py-2.5 text-right font-medium">Amount Received ($)</th>
                        <th class="px-3 py-2.5 text-center font-medium">Status</th>
                        <th class="px-3 py-2.5 text-center font-medium">GL Transaction</th>
                        <th class="px-3 py-2.5 text-right font-medium">Print Option</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($payments as $pmt)
                        @php
                            $receiptRef = $pmt->reference ?? ('REC-' . str_pad($pmt->id, 6, '0', STR_PAD_LEFT));
                            $methodIcons = [
                                'cash' => 'Cash',
                                'bank' => 'Bank Transfer',
                                'mobile_money' => 'Mobile Money',
                                'other' => 'Other',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-2.5 font-mono font-medium text-emerald-400">
                                {{ $receiptRef }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-300">
                                {{ $pmt->paid_at ? $pmt->paid_at->format('d M Y') : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-200">
                                <span class="capitalize px-2 py-0.5 rounded-lg bg-slate-800 border border-slate-700 text-[11px]">
                                    {{ $methodIcons[$pmt->method] ?? ucfirst($pmt->method) }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-slate-300 font-mono">
                                @forelse($pmt->allocations as $alloc)
                                    @if($alloc->invoice)
                                        <a href="{{ route('admin.invoices.show', $alloc->invoice) }}" class="text-indigo-400 hover:underline">
                                            {{ $alloc->invoice->number }}
                                        </a>
                                        <span class="text-slate-500">(${{ number_format($alloc->amount, 2) }})</span>
                                    @else
                                        <span class="text-slate-500">Unallocated</span>
                                    @endif
                                @empty
                                    <span class="text-slate-500">General Credit</span>
                                @endforelse
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold text-emerald-400">
                                ${{ number_format($pmt->amount, 2) }}
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    {{ ucfirst($pmt->status ?? 'completed') }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <a href="{{ route('admin.reports.general-ledger', ['search' => $receiptRef]) }}" 
                                   title="View General Ledger account entries for {{ $receiptRef }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[11px] font-medium hover:bg-blue-500/20 transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    GL Entry
                                </a>
                            </td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                <button type="button" 
                                        onclick="openReceiptPrintModal({{ json_encode([
                                            'receipt_number' => $receiptRef,
                                            'date' => $pmt->paid_at ? $pmt->paid_at->format('d M Y') : date('d M Y'),
                                            'amount' => number_format($pmt->amount, 2),
                                            'method' => ucfirst(str_replace('_', ' ', $pmt->method)),
                                            'student_name' => $student->full_name,
                                            'admission_number' => $student->admission_number ?? '—',
                                            'grade' => ($student->grade ?? '') . ' ' . ($student->class_name ?? ''),
                                            'school_name' => $school->name,
                                            'allocations' => $pmt->allocations->map(fn($a) => ['invoice' => $a->invoice?->number ?? 'Direct Fee', 'amount' => number_format($a->amount, 2)])->toArray(),
                                        ]) }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-[11px] font-medium transition">
                                    <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print Receipt
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-500">No payment receipts found for this student.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Student Account Statement (Live Running Ledger) -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    Student Account Statement
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Chronological debtor statement with live running balance</p>
            </div>
            
            <!-- Statement Period Selector -->
            <form method="GET" action="{{ route('admin.students.show', $student) }}" class="flex flex-wrap items-center gap-2 text-xs">
                <input type="hidden" name="tab" value="finance">
                <div>
                    <select name="statement_year" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs">
                        <option value="">All Academic Years</option>
                        @foreach($statementYears as $y)
                            <option value="{{ $y }}" {{ request('statement_year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="statement_term" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs">
                        <option value="">All Terms</option>
                        <option value="Term 1" {{ request('statement_term') == 'Term 1' ? 'selected' : '' }}>Term 1</option>
                        <option value="Term 2" {{ request('statement_term') == 'Term 2' ? 'selected' : '' }}>Term 2</option>
                        <option value="Term 3" {{ request('statement_term') == 'Term 3' ? 'selected' : '' }}>Term 3</option>
                    </select>
                </div>
                @if(request('statement_year') || request('statement_term'))
                    <a href="{{ route('admin.students.show', ['student' => $student, 'tab' => 'finance']) }}" 
                       class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 rounded-xl text-xs transition">
                        Reset Filter
                    </a>
                @endif
            </form>
        </div>

        <!-- Statement Metadata Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-950/60 p-3 rounded-xl border border-slate-800/80 text-xs">
            <div>
                <span class="text-slate-500 block text-[10px] uppercase">Opening Balance</span>
                <span class="font-mono font-semibold text-slate-200">
                    ${{ number_format($statementData['opening_balance'] ?? 0, 2) }}
                </span>
            </div>
            <div>
                <span class="text-slate-500 block text-[10px] uppercase">Period Debits (Billed)</span>
                <span class="font-mono font-semibold text-blue-400">
                    +${{ number_format($statementData['total_debits'] ?? 0, 2) }}
                </span>
            </div>
            <div>
                <span class="text-slate-500 block text-[10px] uppercase">Period Credits (Paid/Adjusted)</span>
                <span class="font-mono font-semibold text-emerald-400">
                    -${{ number_format($statementData['total_credits'] ?? 0, 2) }}
                </span>
            </div>
            <div>
                <span class="text-slate-500 block text-[10px] uppercase">Closing Balance Due</span>
                @php $closing = $statementData['closing_balance'] ?? 0; @endphp
                <span class="font-mono font-bold {{ $closing > 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                    ${{ number_format($closing, 2) }}
                </span>
            </div>
        </div>

        <!-- Statement Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-3 py-2.5 text-left font-medium">Date</th>
                        <th class="px-3 py-2.5 text-left font-medium">Type</th>
                        <th class="px-3 py-2.5 text-left font-medium">Reference</th>
                        <th class="px-3 py-2.5 text-left font-medium">Description</th>
                        <th class="px-3 py-2.5 text-right font-medium">Debit ($)</th>
                        <th class="px-3 py-2.5 text-right font-medium">Credit ($)</th>
                        <th class="px-3 py-2.5 text-right font-medium">Running Balance ($)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <!-- Opening Balance Row -->
                    <tr class="bg-slate-950/30 text-slate-400 font-medium">
                        <td class="px-3 py-2 font-mono">—</td>
                        <td class="px-3 py-2"><span class="px-2 py-0.5 rounded bg-slate-800 text-[10px]">OPENING</span></td>
                        <td class="px-3 py-2 font-mono">—</td>
                        <td class="px-3 py-2 italic">Balance brought forward</td>
                        <td class="px-3 py-2 text-right font-mono">—</td>
                        <td class="px-3 py-2 text-right font-mono">—</td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-slate-200">
                            ${{ number_format($statementData['opening_balance'] ?? 0, 2) }}
                        </td>
                    </tr>

                    @forelse($statementData['ledger_rows'] ?? [] as $row)
                        @php
                            $badgeColor = match($row['type']) {
                                'invoice' => 'blue',
                                'payment' => 'emerald',
                                'credit_note' => 'purple',
                                default => 'slate'
                            };
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-2.5 font-medium text-slate-300">{{ $row['date'] }}</td>
                            <td class="px-3 py-2.5">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-{{ $badgeColor }}-500/20 text-{{ $badgeColor }}-400 border border-{{ $badgeColor }}-500/30 uppercase">
                                    {{ str_replace('_', ' ', $row['type']) }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 font-mono text-slate-200">
                                @if($row['type'] === 'invoice')
                                    <a href="{{ route('admin.reports.general-ledger', ['search' => $row['reference']]) }}" class="text-indigo-400 hover:underline" title="View in GL">
                                        {{ $row['reference'] }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.reports.general-ledger', ['search' => $row['reference']]) }}" class="text-emerald-400 hover:underline" title="View in GL">
                                        {{ $row['reference'] }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-300">{{ $row['description'] }}</td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-100 font-medium">
                                {{ $row['debit'] > 0 ? '$' . number_format($row['debit'], 2) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-emerald-400 font-medium">
                                {{ $row['credit'] > 0 ? '$' . number_format($row['credit'], 2) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold {{ $row['running_balance'] > 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                                ${{ number_format($row['running_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-5 text-center text-slate-500">No statement transactions found for the selected period.</td>
                        </tr>
                    @endforelse

                    <!-- Closing Balance Summary Row -->
                    <tr class="bg-slate-950/60 font-bold border-t-2 border-slate-700">
                        <td colspan="4" class="px-3 py-3 text-slate-200 uppercase tracking-wider text-right">
                            Closing Outstanding Balance:
                        </td>
                        <td class="px-3 py-3 text-right font-mono text-blue-400">
                            ${{ number_format($statementData['total_debits'] ?? 0, 2) }}
                        </td>
                        <td class="px-3 py-3 text-right font-mono text-emerald-400">
                            ${{ number_format($statementData['total_credits'] ?? 0, 2) }}
                        </td>
                        <td class="px-3 py-3 text-right font-mono text-base {{ ($statementData['closing_balance'] ?? 0) > 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                            ${{ number_format($statementData['closing_balance'] ?? 0, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 4: General Ledger Account Transactions -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    Student General Ledger Account Postings
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Double-entry accounting journal postings for this student</p>
            </div>
            <a href="{{ route('admin.reports.general-ledger', ['search' => $student->admission_number ?? ($student->first_name . ' ' . $student->last_name)]) }}" 
               target="_blank"
               class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1 font-medium transition">
                <span>Open in GL Explorer</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-3 py-2.5 text-left font-medium">Date</th>
                        <th class="px-3 py-2.5 text-left font-medium">Account</th>
                        <th class="px-3 py-2.5 text-left font-medium">Reference</th>
                        <th class="px-3 py-2.5 text-left font-medium">Batch / Description</th>
                        <th class="px-3 py-2.5 text-right font-medium">Debit ($)</th>
                        <th class="px-3 py-2.5 text-right font-medium">Credit ($)</th>
                        <th class="px-3 py-2.5 text-left font-medium">Memo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($glEntries as $entry)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-2.5 font-medium text-slate-300">
                                {{ $entry->batch?->transaction_date ? \Carbon\Carbon::parse($entry->batch->transaction_date)->format('d M Y') : $entry->created_at->format('d M Y') }}
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="font-mono text-slate-200 font-semibold">{{ $entry->account?->code }}</span>
                                <span class="text-slate-400">- {{ $entry->account?->name }}</span>
                            </td>
                            <td class="px-3 py-2.5 font-mono text-indigo-400 font-medium">
                                {{ $entry->batch?->reference_number ?? '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-200">
                                {{ $entry->batch?->description ?? 'Journal posting' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-medium text-slate-100">
                                {{ $entry->entry_type === 'debit' ? '$' . number_format($entry->amount, 2) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-medium text-emerald-400">
                                {{ $entry->entry_type === 'credit' ? '$' . number_format($entry->amount, 2) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-400">
                                {{ $entry->memo ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        @if($studentLedgerEntries->isNotEmpty())
                            @foreach($studentLedgerEntries as $le)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="px-3 py-2.5 font-medium text-slate-300">
                                        {{ $le->entry_date ? $le->entry_date->format('d M Y') : '—' }}
                                    </td>
                                    <td class="px-3 py-2.5 font-mono text-slate-200">
                                        {{ $le->account_code }}
                                    </td>
                                    <td class="px-3 py-2.5 font-mono text-indigo-400">
                                        {{ $le->invoice?->number ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-200">
                                        {{ $le->description }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-slate-100">
                                        {{ $le->type === 'debit' ? '$' . number_format($le->amount, 2) : '—' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-emerald-400">
                                        {{ $le->type === 'credit' ? '$' . number_format($le->amount, 2) : '—' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-400">—</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-slate-500">No General Ledger postings found for this student.</td>
                            </tr>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Printable Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
        <div class="flex items-center justify-between p-4 border-b border-slate-800">
            <h4 class="text-sm font-semibold text-slate-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Official Payment Receipt
            </h4>
            <button type="button" onclick="closeReceiptModal()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Receipt Content Printable Area -->
        <div id="receiptPrintArea" class="p-6 space-y-4 bg-white text-slate-900">
            <div class="text-center border-b border-slate-300 pb-3">
                <h2 id="rcptSchoolName" class="text-lg font-bold text-slate-900 uppercase tracking-wide"></h2>
                <p class="text-xs text-slate-600">Official Student Fee Receipt</p>
                <div class="flex justify-between items-center mt-2 text-xs font-mono">
                    <span>Receipt No: <strong id="rcptNumber" class="text-slate-900"></strong></span>
                    <span>Date: <span id="rcptDate" class="text-slate-700"></span></span>
                </div>
            </div>

            <div class="space-y-1.5 text-xs text-slate-700 border-b border-slate-300 pb-3">
                <div class="flex justify-between"><span>Student:</span> <strong id="rcptStudentName" class="text-slate-900"></strong></div>
                <div class="flex justify-between"><span>Admission #:</span> <span id="rcptAdmission" class="font-mono"></span></div>
                <div class="flex justify-between"><span>Class / Grade:</span> <span id="rcptGrade"></span></div>
                <div class="flex justify-between"><span>Payment Method:</span> <span id="rcptMethod" class="font-medium text-slate-900"></span></div>
            </div>

            <div class="space-y-2 border-b border-slate-300 pb-3">
                <div class="flex justify-between font-bold text-xs text-slate-800 border-b border-slate-200 pb-1">
                    <span>Description / Allocation</span>
                    <span>Amount</span>
                </div>
                <div id="rcptAllocations" class="space-y-1 text-xs text-slate-700">
                    <!-- Dynamic allocation rows -->
                </div>
            </div>

            <div class="flex justify-between items-center pt-1 text-sm font-bold text-slate-900">
                <span>Total Amount Paid:</span>
                <span id="rcptTotal" class="font-mono text-base">$0.00</span>
            </div>

            <div class="pt-6 flex justify-between items-end text-[10px] text-slate-500">
                <div>
                    <p class="border-t border-slate-400 pt-1 w-28 text-center">Cashier Signature</p>
                </div>
                <div class="text-right">
                    <p class="italic">Thank you for your payment.</p>
                </div>
            </div>
        </div>

        <div class="p-4 bg-slate-950/80 border-t border-slate-800 flex justify-end gap-2">
            <button type="button" onclick="closeReceiptModal()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                Close
            </button>
            <button type="button" onclick="printReceiptDirect()" class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Receipt
            </button>
        </div>
    </div>
</div>

<script>
    function printStudentStatement() {
        const year = "{{ request('statement_year', request('academic_year', date('Y'))) }}";
        const term = "{{ request('statement_term', request('term', '')) }}";
        const url = "{{ route('admin.students.statement.print', ['student' => $student]) }}?academic_year=" + encodeURIComponent(year) + (term ? "&term=" + encodeURIComponent(term) : "");
        window.open(url, '_blank');
    }

    function openReceiptPrintModal(data) {
        document.getElementById('rcptSchoolName').innerText = data.school_name || 'Hande High School';
        document.getElementById('rcptNumber').innerText = data.receipt_number;
        document.getElementById('rcptDate').innerText = data.date;
        document.getElementById('rcptStudentName').innerText = data.student_name;
        document.getElementById('rcptAdmission').innerText = data.admission_number;
        document.getElementById('rcptGrade').innerText = data.grade;
        document.getElementById('rcptMethod').innerText = data.method;
        document.getElementById('rcptTotal').innerText = '$' + data.amount;

        const allocContainer = document.getElementById('rcptAllocations');
        allocContainer.innerHTML = '';
        if (data.allocations && data.allocations.length > 0) {
            data.allocations.forEach(a => {
                const row = document.createElement('div');
                row.className = 'flex justify-between';
                row.innerHTML = `<span>Invoice ${a.invoice}</span><span class="font-mono">$${a.amount}</span>`;
                allocContainer.appendChild(row);
            });
        } else {
            const row = document.createElement('div');
            row.className = 'flex justify-between';
            row.innerHTML = `<span>School Tuition & Fees</span><span class="font-mono">$${data.amount}</span>`;
            allocContainer.appendChild(row);
        }

        const modal = document.getElementById('receiptModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeReceiptModal() {
        const modal = document.getElementById('receiptModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function printReceiptDirect() {
        const printContents = document.getElementById('receiptPrintArea').innerHTML;
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Receipt</title>');
        printWindow.document.write('<style>body{font-family:sans-serif;margin:20px;color:#111;} .flex{display:flex;} .justify-between{justify-content:space-between;} .text-center{text-align:center;} .text-right{text-align:right;} .font-bold{font-weight:bold;} .border-b{border-bottom:1px solid #ddd;} .pb-3{padding-bottom:12px;} .pt-6{padding-top:24px;} .w-28{width:112px;} .space-y-2>*{margin-top:8px;} .space-y-1>*{margin-top:4px;} .font-mono{font-family:monospace;}</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(printContents);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 300);
    }
</script>
