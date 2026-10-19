@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Invoices</h1>
            <p class="text-xs text-slate-400 mt-1">Track fee invoices, balances, and payment status.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.school-setup.logs', ['module' => 'accounting']) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-800/80 px-3.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition" title="View invoice deletion and activity logs">
                <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Audit Trail
            </a>
            <a href="{{ route('admin.payments.advance.create') }}" class="inline-flex items-center rounded-full bg-emerald-600 hover:bg-emerald-500 px-4 py-1.5 text-xs font-semibold text-white shadow-sm transition gap-1.5" title="Receipt advance payment from parents without an existing invoice">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Receive Advance Payment
            </a>
            <a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 transition">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create
            </a>
            <a href="{{ route('admin.invoices.bulk.create') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Bulk Create
            </a>
            <a href="{{ route('admin.invoices.auto-generate') }}" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                Auto Generate
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="bg-emerald-900/50 border border-emerald-700 text-emerald-200 rounded-lg p-4 text-sm mb-4">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-900/50 border border-rose-700 text-rose-200 rounded-lg p-4 text-sm mb-4">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.invoices.index') }}" class="mb-4 flex flex-wrap gap-3 text-xs text-slate-100">
        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="status">Status</label>
            <select id="status" name="status" class="w-40 rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                <option value="">All</option>
                @foreach($statusOptions as $s)
                    <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Filter
            </button>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        @if($invoices->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Invoice</th>
                        <th class="text-left py-2 font-medium">Student</th>
                        <th class="text-left py-2 font-medium">Guardian</th>
                        <th class="text-left py-2 font-medium">Year / Term</th>
                        <th class="text-left py-2 font-medium">Amounts</th>
                        <th class="text-left py-2 font-medium">Status</th>
                        <th class="text-left py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">
                                <div class="font-medium">{{ $invoice->number }}</div>
                                <div class="text-[11px] text-slate-400">Issued {{ $invoice->issued_at?->format('Y-m-d') }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                <div class="font-medium">{{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $invoice->student?->grade }} {{ $invoice->student?->class_name }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                @if($invoice->guardian)
                                    <div class="font-medium">{{ $invoice->guardian->first_name }} {{ $invoice->guardian->last_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $invoice->guardian->email }}</div>
                                @else
                                    <span class="text-[11px] text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                <div>{{ $invoice->academic_year }}</div>
                                <div class="text-[11px] text-slate-400">{{ $invoice->term }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                <div>Total: {{ number_format($invoice->total_amount, 2) }}</div>
                                <div class="text-[11px] text-slate-400">Balance: {{ number_format($invoice->balance, 2) }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                @php
                                    $statusColor = [
                                        'unpaid' => 'border-rose-500/60 bg-rose-500/10 text-rose-200',
                                        'partial' => 'border-amber-500/60 bg-amber-500/10 text-amber-200',
                                        'paid' => 'border-emerald-500/60 bg-emerald-500/10 text-emerald-200',
                                        'cancelled' => 'border-slate-600 bg-slate-800 text-slate-200',
                                    ][$invoice->status] ?? 'border-slate-600 bg-slate-800 text-slate-200';
                                @endphp
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] capitalize {{ $statusColor }}">
                                    {{ $invoice->status }}
                                </span>
                            </td>
                            <td class="py-2 align-middle">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">
                                        View
                                    </a>
                                    @if($invoice->status !== 'cancelled')
                                        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">
                                            Edit
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.invoices.print', $invoice) }}" class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-800 transition">
                                        Print
                                    </a>
                                    @if($invoice->student)
                                        <a href="{{ route('admin.students.statement.create', $invoice->student) }}" class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-800 transition">
                                            Statement
                                        </a>
                                    @endif
                                    @if($invoice->balance > 0 && $invoice->status !== 'cancelled')
                                        <a href="{{ route('admin.payments.create', $invoice) }}" class="rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-emerald-600 transition">
                                            Record payment
                                        </a>
                                    @endif
                                    @if($invoice->status !== 'cancelled')
                                        <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this invoice?')">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-amber-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-amber-600 transition">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                    @php
                                        $paidTotal = (float) $invoice->allocations->sum('amount');
                                        $canDelete = $paidTotal == 0;
                                    @endphp
                                    @if($canDelete)
                                        <button type="button" 
                                                onclick="openDeleteInvoiceModal('{{ route('admin.invoices.destroy', $invoice) }}', '{{ $invoice->number }}', '{{ addslashes(($invoice->student?->first_name ?? '') . ' ' . ($invoice->student?->last_name ?? '')) }}', '{{ number_format($invoice->total_amount, 2) }}', '{{ $invoice->academic_year }} - {{ $invoice->term }}')"
                                                class="rounded-full bg-rose-500/20 border border-rose-500/30 px-3 py-1 text-[11px] font-medium text-rose-300 hover:bg-rose-600 hover:text-white transition">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-xs text-slate-400 mb-4">No invoices found yet.</p>
                <a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 transition">
                    Create your first invoice
                </a>
            </div>
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
