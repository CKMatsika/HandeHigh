@extends('layouts.app')

@section('content')
    @php
        $totalPaid = (float) ($invoice->allocations?->sum('amount') ?? 0);
        $payments = $invoice->allocations
            ?->loadMissing('payment')
            ?->map(fn($a) => $a->payment)
            ?->filter();
    @endphp

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Invoice</h1>
            <p class="text-xs text-slate-400 mt-1">View invoice items, payments, and balances.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($invoice->status !== 'cancelled')
                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Edit</a>
                <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this invoice?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-full bg-amber-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-amber-600 transition">Cancel</button>
                </form>
            @endif
            <a href="{{ route('admin.invoices.print', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Print</a>
            @if($invoice->balance > 0 && $invoice->status !== 'cancelled')
                <a href="{{ route('admin.payments.create', $invoice) }}" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">Record payment</a>
            @endif
            @if($totalPaid == 0)
                <button type="button" 
                        onclick="openDeleteInvoiceModal('{{ route('admin.invoices.destroy', $invoice) }}', '{{ $invoice->number }}', '{{ addslashes(($invoice->student?->first_name ?? '') . ' ' . ($invoice->student?->last_name ?? '')) }}', '{{ number_format($invoice->total_amount, 2) }}', '{{ $invoice->academic_year }} - {{ $invoice->term }}')"
                        class="inline-flex items-center rounded-full bg-rose-500/20 border border-rose-500/40 px-4 py-1.5 text-xs font-medium text-rose-300 hover:bg-rose-600 hover:text-white transition">
                    Delete
                </button>
            @endif
            <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Invoice</h2>
            <div class="text-xs text-slate-200 space-y-1">
                <div><span class="text-slate-400">Number:</span> {{ $invoice->number }}</div>
                <div><span class="text-slate-400">Issued:</span> {{ $invoice->issued_at?->format('Y-m-d') }}</div>
                <div><span class="text-slate-400">Due:</span> {{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</div>
                <div><span class="text-slate-400">Year/Term:</span> {{ $invoice->academic_year }} · {{ $invoice->term }}</div>
                <div><span class="text-slate-400">Status:</span> <span class="capitalize">{{ $invoice->status }}</span></div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Student</h2>
            <div class="text-xs text-slate-200 space-y-1">
                <div><span class="text-slate-400">Name:</span> {{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</div>
                <div><span class="text-slate-400">Grade/Class:</span> {{ $invoice->student?->grade }} {{ $invoice->student?->class_name }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Amounts</h2>
            <div class="text-xs text-slate-200 space-y-1">
                <div><span class="text-slate-400">Total:</span> {{ number_format($invoice->total_amount, 2) }}</div>
                <div><span class="text-slate-400">Paid:</span> {{ number_format($totalPaid, 2) }}</div>
                <div><span class="text-slate-400">Balance:</span> {{ number_format($invoice->balance, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mt-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Invoice items</h2>
        @if($invoice->items->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Description</th>
                        <th class="text-left py-2 font-medium">Category</th>
                        <th class="text-right py-2 font-medium">Qty</th>
                        <th class="text-right py-2 font-medium">Unit</th>
                        <th class="text-right py-2 font-medium">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">{{ $item->description }}</td>
                            <td class="py-2 align-middle capitalize">{{ $item->category ?: '—' }}</td>
                            <td class="py-2 align-middle text-right">{{ $item->quantity }}</td>
                            <td class="py-2 align-middle text-right">{{ number_format($item->unit_amount, 2) }}</td>
                            <td class="py-2 align-middle text-right font-medium">{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-xs text-slate-400">No line items found.</p>
        @endif
    </div>

    @if(session('receipt_url'))
        <div class="mb-4 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 flex items-center justify-between text-xs text-emerald-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-300 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <p class="font-semibold text-emerald-100">{{ session('status', 'Payment recorded successfully!') }}</p>
                    <p class="text-[11px] text-emerald-300/80">Official student fee receipt is ready for printing and records.</p>
                </div>
            </div>
            <a href="{{ session('receipt_url') }}" target="_blank" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-600 transition gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Official Receipt
            </a>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mt-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Payments</h2>
        @if($invoice->allocations->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Date</th>
                        <th class="text-left py-2 font-medium">Method</th>
                        <th class="text-left py-2 font-medium">Account</th>
                        <th class="text-left py-2 font-medium">Reference</th>
                        <th class="text-right py-2 font-medium">Amount</th>
                        <th class="text-right py-2 font-medium">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->allocations as $allocation)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">{{ $allocation->payment?->paid_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-2 align-middle">{{ $allocation->payment?->method ? ucfirst(str_replace('_', ' ', $allocation->payment->method)) : '—' }}</td>
                            <td class="py-2 align-middle text-slate-400">{{ $allocation->payment?->account ? ($allocation->payment->account->code . ' · ' . $allocation->payment->account->name) : 'Cash/Bank' }}</td>
                            <td class="py-2 align-middle">{{ $allocation->payment?->reference ?: '—' }}</td>
                            <td class="py-2 align-middle text-right font-medium text-emerald-400">{{ number_format($allocation->amount, 2) }}</td>
                            <td class="py-2 align-middle text-right">
                                @if($allocation->payment)
                                    <a href="{{ route('admin.payments.receipt', $allocation->payment) }}" target="_blank" class="inline-flex items-center rounded-full bg-emerald-500/20 border border-emerald-500/40 px-3 py-1 text-[11px] font-medium text-emerald-300 hover:bg-emerald-600 hover:text-white transition">
                                        Print Receipt
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-xs text-slate-400">No payments recorded yet.</p>
        @endif
    </div>

    <!-- Delete Invoice Modal with Audit Reason -->
    <div id="deleteInvoiceModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" onclick="closeDeleteInvoiceModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div class="relative inline-block align-bottom bg-slate-900 border border-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="deleteInvoiceForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-slate-100" id="modal-title">Delete Invoice Permanently</h3>
                                <p class="text-xs text-slate-400">Remove from active listings with recorded audit tracking</p>
                            </div>
                        </div>

                        <!-- Invoice info pill box -->
                        <div class="rounded-xl bg-slate-950/60 border border-slate-800 p-3 mb-4 text-xs space-y-1.5">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Invoice:</span>
                                <span id="modalInvoiceNumber" class="font-mono font-medium text-slate-200">INV-XXXX</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Student:</span>
                                <span id="modalStudentName" class="font-medium text-slate-200">—</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Period:</span>
                                <span id="modalPeriod" class="text-slate-300">—</span>
                            </div>
                            <div class="flex justify-between border-t border-slate-800 pt-1.5">
                                <span class="text-slate-400">Total Billed:</span>
                                <span id="modalAmount" class="font-semibold text-emerald-400">$0.00</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="deletion_reason" class="block text-xs font-medium text-slate-300 mb-1.5">
                                Reason for Deletion <span class="text-rose-400">*</span>
                            </label>
                            <textarea id="deletion_reason" 
                                      name="deletion_reason" 
                                      rows="3" 
                                      required
                                      placeholder="e.g., Mistakes on dates and fee structure lines during bulk generation, re-invoicing correctly..." 
                                      class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
                            <p class="text-[11px] text-slate-400 mt-1.5">
                                🔒 This deletion and your reason will be permanently recorded in the audit trail for administrative review and compliance.
                            </p>
                        </div>
                    </div>

                    <div class="bg-slate-950/50 px-6 py-3 border-t border-slate-800 flex justify-end gap-2">
                        <button type="button" 
                                onclick="closeDeleteInvoiceModal()" 
                                class="rounded-full border border-slate-700 bg-slate-800 px-4 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="rounded-full bg-rose-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-rose-700 transition">
                            Confirm & Delete Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteInvoiceModal(actionUrl, invoiceNumber, studentName, amount, period) {
            const form = document.getElementById('deleteInvoiceForm');
            form.action = actionUrl;
            document.getElementById('modalInvoiceNumber').innerText = invoiceNumber;
            document.getElementById('modalStudentName').innerText = studentName;
            document.getElementById('modalAmount').innerText = '$' + amount;
            document.getElementById('modalPeriod').innerText = period;
            document.getElementById('deletion_reason').value = '';
            document.getElementById('deleteInvoiceModal').classList.remove('hidden');
        }

        function closeDeleteInvoiceModal() {
            document.getElementById('deleteInvoiceModal').classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteInvoiceModal();
            }
        });
    </script>
@endsection
