@extends('layouts.app')

@section('content')
<style>
    @media print {
        aside, header, nav, .no-print { display: none !important; }
        main { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        body { background: white !important; color: black !important; font-size: 11px !important; }
        .print-container { border: 1px solid #1e293b !important; padding: 16px !important; border-radius: 0 !important; box-shadow: none !important; }
        .print-table th, .print-table td { border-color: #cbd5e1 !important; color: black !important; padding: 4px 6px !important; }
        .print-table th { background: #f1f5f9 !important; font-weight: bold !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .print-badge { border: 1px solid #64748b !important; color: black !important; background: transparent !important; }
        .page-break { page-break-before: always; }
    }
</style>

<div class="space-y-6">
    <!-- Action Bar & Breadcrumbs (No Print) -->
    <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.finance-dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Financial Reports</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-300">Daily Cash Banking Handover</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">Daily Banking & Cash Handover Report</h1>
            <p class="text-xs text-slate-400 mt-0.5">End-of-day cash reconciliation from Accounts Clerk to Senior Person for commercial bank deposit</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button onclick="window.print()" class="px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Official Handover Document
            </button>
            <a href="{{ route('admin.reports.daily-banking-handover.export', ['date' => $date, 'method' => $method]) }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export CSV
            </a>
            <a href="{{ route('admin.reports.cashbook-summary') }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800 transition">
                Cashbook &rarr;
            </a>
        </div>
    </div>

    <!-- Filter Form (No Print) -->
    <div class="no-print rounded-2xl border border-slate-800 bg-slate-900/90 p-4 shadow-xl">
        <form method="GET" action="{{ route('admin.reports.daily-banking-handover') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3.5 items-end">
            <div>
                <label for="date" class="block text-[11px] font-medium text-slate-400 mb-1">Collection Date</label>
                <input type="date" id="date" name="date" value="{{ $date }}" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label for="method" class="block text-[11px] font-medium text-slate-400 mb-1">Receipt Channel / Method</label>
                <select id="method" name="method" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500">
                    <option value="cash" {{ $method === 'cash' ? 'selected' : '' }}>Physical Cash Only (Default)</option>
                    <option value="all" {{ $method === 'all' ? 'selected' : '' }}>All Payment Methods</option>
                    <option value="bank_transfer" {{ $method === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / Direct Deposit</option>
                    <option value="ecocash" {{ $method === 'ecocash' ? 'selected' : '' }}>EcoCash / Mobile Money</option>
                    <option value="pos" {{ $method === 'pos' ? 'selected' : '' }}>POS Swipe / Card</option>
                </select>
            </div>

            <div>
                <label for="bank_account_id" class="block text-[11px] font-medium text-slate-400 mb-1">Target School Bank Account</label>
                <select id="bank_account_id" name="bank_account_id" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500">
                    <option value="">-- Auto Primary School Bank --</option>
                    @foreach($bankAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ ($selectedBankAccount?->id == $acc->id) ? 'selected' : '' }}>
                            {{ $acc->bank_name ?? $acc->name }} ({{ $acc->account_number ?? $acc->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="banking_ref" class="block text-[11px] font-medium text-slate-400 mb-1">Bank Deposit Slip Reference (Optional)</label>
                <input type="text" id="banking_ref" name="banking_ref" value="{{ $bankingRef }}" placeholder="e.g. SLIP-CBZ-8821" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs shadow-md transition text-center">
                    Filter & Load
                </button>
                <a href="{{ route('admin.reports.daily-banking-handover') }}" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs border border-slate-700 transition">
                    Today
                </a>
            </div>
        </form>
    </div>

    <!-- OFFICIAL HANDOVER DOCUMENT CONTAINER -->
    <div class="print-container rounded-2xl border border-slate-800 bg-slate-900/95 p-6 md:p-8 shadow-2xl space-y-6 text-slate-100">
        
        <!-- Document Header / Crest -->
        <div class="border-b-2 border-slate-700 pb-5">
            <div class="text-center space-y-1">
                <div class="text-[12px] font-bold tracking-widest uppercase text-amber-400">Buhera Rural District Council</div>
                <div class="text-2xl font-black uppercase tracking-wider text-slate-100">Hande Secondary School</div>
                <div class="text-[11px] text-slate-400">P.O. Box 90, Buhera · Manicaland, Zimbabwe · Tel: +263 77 123 4567</div>
                <div class="inline-block mt-2 px-4 py-1 rounded-full bg-indigo-950/80 border border-indigo-700/60 text-indigo-300 font-bold uppercase tracking-wider text-xs">
                    Official Daily Cash Banking & Handover Report
                </div>
            </div>

            <!-- Meta Details Bar -->
            <div class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs bg-slate-950/70 p-3.5 rounded-xl border border-slate-800">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Collection Date:</span>
                    <span class="font-bold text-slate-200">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Payment Mode:</span>
                    <span class="font-bold text-slate-200 uppercase">{{ ucfirst(str_replace('_', ' ', $method)) }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Target Bank Account:</span>
                    <span class="font-bold text-slate-200">
                        {{ $selectedBankAccount ? ($selectedBankAccount->bank_name ?? $selectedBankAccount->name) . ' (' . ($selectedBankAccount->account_number ?? $selectedBankAccount->code) . ')' : 'Official School Bank Account' }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Deposit Reference / Slip:</span>
                    <span class="font-bold font-mono text-emerald-400">{{ $bankingRef ?: 'PENDING DEPOSIT' }}</span>
                </div>
            </div>
        </div>

        <!-- SECTION 1: VOTE BREAKDOWN SUMMARY -->
        <div>
            <div class="flex items-center justify-between mb-2.5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    1. Summary of Collections by Fee Vote / Sub-Account
                </h2>
                <span class="text-[11px] text-slate-400 font-medium">Reconciled to Official Hande Fee Schedule</span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="print-table w-full text-xs">
                    <thead class="bg-slate-950 text-slate-300 border-b border-slate-800 text-[11px]">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-12 font-semibold">Priority</th>
                            <th class="py-2.5 px-3 text-left font-semibold">Vote Code</th>
                            <th class="py-2.5 px-4 text-left font-semibold">Vote Description / Sub-Account</th>
                            <th class="py-2.5 px-3 text-left font-semibold">Category</th>
                            <th class="py-2.5 px-4 text-right font-semibold">Amount Collected ($)</th>
                            <th class="py-2.5 px-3 text-right font-semibold">% Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @php $voteCounter = 1; @endphp
                        @foreach($voteCategories as $vCode => $vote)
                            @php 
                                $pct = $totalCash > 0 ? ($vote['amount'] / $totalCash) * 100 : 0;
                            @endphp
                            <tr class="hover:bg-slate-800/30 transition {{ $vote['amount'] > 0 ? 'bg-slate-900/40' : 'opacity-60' }}">
                                <td class="py-2 px-3 text-center text-slate-500 font-mono">{{ $voteCounter++ }}</td>
                                <td class="py-2 px-3 font-mono font-semibold text-indigo-300">{{ $vCode }}</td>
                                <td class="py-2 px-4 font-medium text-slate-200">{{ $vote['name'] ?? $vote['label'] ?? $vCode }}</td>
                                <td class="py-2 px-3 text-slate-400 uppercase text-[10px]">{{ $vote['category'] ?? 'levy' }}</td>
                                <td class="py-2 px-4 text-right font-mono font-bold text-slate-100">
                                    ${{ number_format($vote['amount'], 2) }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono text-[11px] text-slate-400">
                                    {{ number_format($pct, 1) }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-950 font-bold border-t-2 border-slate-700">
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-right uppercase tracking-wider text-slate-300">
                                Total Collections for Banking:
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-base text-emerald-400">
                                ${{ number_format($totalCash, 2) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-slate-400">100.0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Amount in Words Box -->
            <div class="mt-3 p-3 rounded-xl bg-slate-950/80 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="text-xs">
                    <span class="text-slate-400 uppercase font-semibold text-[10px] mr-1">Total Amount in Words:</span>
                    <span class="font-bold text-amber-300 font-serif italic">{{ $totalInWords }}</span>
                </div>
                <div class="text-xs text-slate-400">
                    <span>Total Receipts Audited:</span>
                    <span class="font-bold text-slate-200 ml-1">{{ $payments->count() }} transactions</span>
                </div>
            </div>
        </div>

        <!-- SECTION 2: ITEMIZED RECEIPTS AUDIT SCHEDULE -->
        <div class="pt-2">
            <div class="flex items-center justify-between mb-2.5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    2. Itemized Receipts Breakdown with MOPSE Book Serial Numbers
                </h2>
                <span class="text-[11px] text-slate-400 font-medium">Chronological Transaction Log</span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="print-table w-full text-xs">
                    <thead class="bg-slate-950 text-slate-300 border-b border-slate-800 text-[11px]">
                        <tr>
                            <th class="py-2 px-3 text-left font-semibold">Receipt #</th>
                            <th class="py-2 px-3 text-left font-semibold">MOPSE Serial #</th>
                            <th class="py-2 px-2 text-left font-semibold">Time</th>
                            <th class="py-2 px-3 text-left font-semibold">Student Name</th>
                            <th class="py-2 px-2 text-left font-semibold">Adm #</th>
                            <th class="py-2 px-2 text-left font-semibold">Form / Class</th>
                            <th class="py-2 px-2 text-left font-semibold">Channel</th>
                            <th class="py-2 px-3 text-right font-semibold">Amount ($)</th>
                            <th class="py-2 px-2 text-left font-semibold">Accounts Clerk</th>
                            <th class="no-print py-2 px-2 text-center font-semibold">Voucher</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @forelse($payments as $p)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-2 px-3 font-mono font-semibold text-blue-400 whitespace-nowrap">
                                    PAY-{{ $p->id }}
                                </td>
                                <td class="py-2 px-3 font-mono font-bold whitespace-nowrap">
                                    @if($p->mopse_receipt_number)
                                        <span class="px-2 py-0.5 rounded bg-emerald-950/70 border border-emerald-600/60 text-emerald-300">
                                            #{{ $p->mopse_receipt_number }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 italic text-[11px]">Electronic</span>
                                    @endif
                                </td>
                                <td class="py-2 px-2 text-slate-400 font-mono whitespace-nowrap">
                                    {{ $p->created_at?->format('H:i') ?? '—' }}
                                </td>
                                <td class="py-2 px-3 font-medium text-slate-100">
                                    {{ $p->student?->last_name }} {{ $p->student?->first_name }}
                                    @if($p->student?->is_boarding)
                                        <span class="ml-1 text-[9px] px-1.5 py-0.2 rounded bg-indigo-900/60 text-indigo-300 border border-indigo-700/50">Boarding</span>
                                    @endif
                                    @if($p->is_advance)
                                        <span class="ml-1 text-[9px] px-1.5 py-0.2 rounded bg-amber-900/60 text-amber-300 border border-amber-700/50">Advance Fee</span>
                                    @endif
                                </td>
                                <td class="py-2 px-2 font-mono text-slate-400">{{ $p->student?->admission_number ?? '—' }}</td>
                                <td class="py-2 px-2 text-slate-300 whitespace-nowrap">
                                    {{ $p->student?->grade ?? '—' }} {{ $p->student?->class_name ?? '' }}
                                </td>
                                <td class="py-2 px-2 uppercase text-[10px] text-slate-400">
                                    {{ str_replace('_', ' ', $p->method) }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-slate-100 whitespace-nowrap">
                                    ${{ number_format($p->amount, 2) }}
                                </td>
                                <td class="py-2 px-2 text-slate-400 text-[11px] truncate max-w-[100px]">
                                    {{ $p->creator?->name ?? 'Clerk' }}
                                </td>
                                <td class="no-print py-2 px-2 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.payments.receipt-mopse', $p) }}" target="_blank" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] border border-slate-700">
                                        MOPSE
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-500 text-xs">
                                    No receipts recorded for {{ \Carbon\Carbon::parse($date)->format('d F Y') }} with method '{{ $method }}'.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-950 font-bold border-t border-slate-700">
                        <tr>
                            <td colspan="7" class="py-2.5 px-3 text-right uppercase tracking-wider text-slate-400">Subtotal:</td>
                            <td class="py-2.5 px-3 text-right font-mono text-emerald-400 font-bold">${{ number_format($totalCash, 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- SECTION 3: OFFICIAL HANDOVER & BANK ACCEPTANCE SIGNATURE BLOCKS -->
        <div class="pt-4 border-t-2 border-slate-700">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                3. Authorised Handover & Banking Acceptance Verification
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                <!-- Accounts Clerk Handover -->
                <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="font-bold text-slate-200 uppercase text-[11px]">A. Handed Over By (Accounts Clerk / Bursar)</span>
                        <span class="text-[10px] text-slate-500 font-mono">STEP 1</span>
                    </div>

                    <div class="space-y-2 text-slate-300">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Staff Officer Name:</span>
                            <span class="font-bold text-slate-100">{{ $clerkUser->name ?? 'Accounts Clerk' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Designation / Role:</span>
                            <span class="font-medium text-slate-200">Accounts Clerk / Assistant Bursar</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Total Physical Cash Handed Over:</span>
                            <span class="font-mono font-bold text-emerald-400">${{ number_format($totalCash, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Date & Generation Timestamp:</span>
                            <span class="font-mono text-slate-200">{{ now()->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-dashed border-slate-700 space-y-1">
                        <div class="h-10 border-b border-slate-600"></div>
                        <div class="flex justify-between text-[10px] text-slate-400 pt-1">
                            <span>Accounts Clerk Signature</span>
                            <span>Date: _____ / _____ / 2026</span>
                        </div>
                    </div>
                </div>

                <!-- Senior Person Acceptance (Head / Deputy Head / SDC Treasurer) -->
                <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="font-bold text-slate-200 uppercase text-[11px]">B. Received & Verified By (Authorised Senior Person)</span>
                        <span class="text-[10px] text-slate-500 font-mono">STEP 2</span>
                    </div>

                    <div class="space-y-2 text-slate-300">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Senior Officer Name:</span>
                            <span class="text-slate-200 border-b border-slate-600 min-w-[160px] inline-block">&nbsp;</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Designation:</span>
                            <span class="text-slate-200">[  ] Headmaster &nbsp; [  ] Deputy Head &nbsp; [  ] SDC Treasurer</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Physical Cash Verification:</span>
                            <span class="text-emerald-400 font-bold">[  ] Verified Correct & Counted</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Bank Deposit Slip / Reference:</span>
                            <span class="font-mono text-slate-200 border-b border-slate-600 min-w-[140px] inline-block font-semibold">
                                {{ $bankingRef ?: '____________________' }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-dashed border-slate-700 space-y-1">
                        <div class="h-10 border-b border-slate-600"></div>
                        <div class="flex justify-between text-[10px] text-slate-400 pt-1">
                            <span>Authorised Senior Signature</span>
                            <span>Date: _____ / _____ / 2026</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Official School Stamp & Note Box -->
            <div class="mt-4 p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-[11px] text-slate-400 space-y-1">
                    <div class="font-bold text-slate-300 uppercase">MOPSE Financial Compliance Requirement:</div>
                    <p>This daily banking handover register must be signed in duplicate before cash is removed from the school premises. The certified commercial bank stamped deposit slip must be attached to the original copy and filed in the Treasury audit vault.</p>
                </div>
                <div class="w-48 h-20 border-2 border-dashed border-slate-600 rounded-xl flex items-center justify-center text-slate-500 text-[10px] uppercase font-bold tracking-wider text-center p-2 shrink-0">
                    Official School Date Stamp
                </div>
            </div>
        </div>

        <!-- Footer / Printflow Slogan -->
        <div class="text-center text-[10px] text-slate-500 pt-2 border-t border-slate-800">
            Hande Secondary School · Powered by Hande Management System · Printed: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</div>
@endsection
