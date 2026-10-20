@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit invoice</h1>
            <p class="text-xs text-slate-400 mt-1">Adjust invoice items. Add, remove, or modify items. Total cannot be reduced below what has already been paid.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100">
            <ul class="list-disc ml-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs mb-4">
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Invoice</div>
                <div class="text-slate-200 font-medium">{{ $invoice->number }}</div>
                <div class="text-slate-400 mt-1">Student: {{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Current totals</div>
                <div class="text-slate-200">Total: {{ number_format($invoice->total_amount, 2) }}</div>
                <div class="text-slate-200">Paid: {{ number_format($totalPaid, 2) }}</div>
                <div class="text-slate-200">Balance: {{ number_format($invoice->balance, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Rule</div>
                <div class="text-slate-200">New total must be &gt;= {{ number_format($totalPaid, 2) }}</div>
                <div class="text-slate-400 mt-1">If total changes, a ledger adjustment is posted automatically.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="space-y-4 text-xs text-slate-100">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="issued_at">Issued date</label>
                    <input id="issued_at" type="date" name="issued_at" value="{{ old('issued_at', $invoice->issued_at?->format('Y-m-d')) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="due_date">Due date</label>
                    <input id="due_date" type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-slate-100">Items</h2>
                    <div class="flex flex-wrap gap-2">
                        @if($convertSchedule)
                            <button type="button" onclick="convertToVoteBreakdown()" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 transition-colors">
                                Convert to Official Vote Breakdown
                            </button>
                        @endif
                        <button type="button" onclick="addNewItem(); document.querySelector('#invoice-items tr:last-child .item-fee-select')?.focus()" class="px-3 py-1 bg-indigo-700 text-white text-xs rounded hover:bg-indigo-600 transition-colors">
                            Add from Fee Structure
                        </button>
                        <button type="button" onclick="addNewItem()" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                            Add Manual Item
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-slate-100">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400">
                                <th class="text-left py-2 font-medium">Description</th>
                                <th class="text-left py-2 font-medium">Fee Structure</th>
                                <th class="text-left py-2 font-medium">MOPSE Vote</th>
                                <th class="text-left py-2 font-medium">Category</th>
                                <th class="text-right py-2 font-medium">Qty</th>
                                <th class="text-right py-2 font-medium">Unit Price</th>
                                <th class="text-right py-2 font-medium">Total</th>
                                <th class="text-center py-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invoice-items">
                            @foreach($invoice->items as $index => $item)
                                <tr class="border-b border-slate-800/70 invoice-item" data-item-id="{{ $item->id }}" data-paid="{{ (float) $item->paid_amount }}">
                                    <td class="py-2 align-middle">
                                        <input type="text" name="items[{{ $index }}][description]" value="{{ old('items.' . $index . '.description', $item->description) }}" required class="w-72 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs item-description">
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}" class="item-id">
                                    </td>
                                    <td class="py-2 align-middle">
                                        <select name="items[{{ $index }}][fee_structure_id]" class="w-56 rounded border border-slate-700 bg-slate-950/60 px-2 py-2 text-xs item-fee-select">
                                            <option value="">Manual / no preset</option>
                                            @foreach($feeStructures->groupBy('residency_type') as $residency => $fees)
                                                <optgroup label="{{ ucfirst($residency) }} Fee Structures">
                                                    @foreach($fees as $fee)
                                                        <option value="{{ $fee->id }}" data-label="{{ $fee->label }}" data-category="{{ $fee->category }}" data-amount="{{ $fee->amount }}" data-vote-code="{{ $fee->vote_code }}" {{ $item->fee_structure_id == $fee->id ? 'selected' : '' }}>
                                                            {{ $fee->label }} — ${{ number_format($fee->amount, 2) }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 align-middle">
                                        <select name="items[{{ $index }}][vote_code]" class="w-52 rounded border border-slate-700 bg-slate-950/60 px-2 py-2 text-xs item-vote-select">
                                            <option value="NONE" {{ $item->effective_vote_code ? '' : 'selected' }}>NONE (unassigned)</option>
                                            @foreach($standardVotes as $code => $vote)
                                                <option value="{{ $code }}" {{ $item->effective_vote_code === $code ? 'selected' : '' }}>[{{ $code }}] {{ $vote['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 align-middle">
                                        <input type="text" name="items[{{ $index }}][category]" value="{{ old('items.' . $index . '.category', $item->category) }}" class="w-28 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs item-category">
                                    </td>
                                    <td class="py-2 align-middle text-right">
                                        <input type="number" min="1" step="1" name="items[{{ $index }}][quantity]" value="{{ old('items.' . $index . '.quantity', $item->quantity) }}" required class="w-20 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-right item-quantity">
                                    </td>
                                    <td class="py-2 align-middle text-right">
                                        <input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_amount]" value="{{ old('items.' . $index . '.unit_amount', $item->unit_amount) }}" required class="w-28 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-right item-unit-price">
                                    </td>
                                    <td class="py-2 align-middle text-right">
                                        <span class="item-total w-28 inline-block text-right">{{ number_format($item->line_total, 2) }}</span>
                                        <span class="block text-[10px] text-emerald-300">Paid: ${{ number_format($item->paid_amount, 2) }}</span>
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        <button type="button" onclick="removeItem(this)" {{ $item->paid_amount > 0 ? 'disabled title=Paid-item-cannot-be-removed' : '' }} class="text-red-400 hover:text-red-300 text-xs disabled:cursor-not-allowed disabled:opacity-40">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 p-3 bg-slate-800 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-300">Total Amount:</span>
                        <span id="total-amount" class="text-lg font-bold text-emerald-500">${{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm font-medium text-slate-300">Minimum Allowed:</span>
                        <span class="text-sm font-bold text-amber-500">${{ number_format($totalPaid, 2) }}</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="edit_reason">Reason for invoice edit</label>
                <input id="edit_reason" type="text" name="edit_reason" value="{{ old('edit_reason') }}" maxlength="500" placeholder="Optional audit note" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>

            <div class="flex justify-end gap-2">
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">Save changes</button>
                <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Cancel</a>
            </div>
        </form>
    </div>

<script>
let newItemIndex = {{ $invoice->items->count() }};
const feeStructures = @json($feeStructures);
const standardVotes = @json($standardVotes);
const convertSchedule = @json($convertSchedule ?? []);

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));
}

function feeOptions(selectedId = '') {
    let html = '<option value="">Manual / no preset</option>';
    const groups = {};
    feeStructures.forEach(fee => (groups[fee.residency_type || 'all'] ||= []).push(fee));
    Object.entries(groups).forEach(([residency, fees]) => {
        html += `<optgroup label="${escapeHtml(residency)} Fee Structures">`;
        fees.forEach(fee => {
            html += `<option value="${fee.id}" data-label="${escapeHtml(fee.label)}" data-category="${escapeHtml(fee.category)}" data-amount="${Number(fee.amount || 0)}" data-vote-code="${escapeHtml(fee.vote_code || '')}" ${String(fee.id) === String(selectedId) ? 'selected' : ''}>${escapeHtml(fee.label)} — $${Number(fee.amount || 0).toFixed(2)}</option>`;
        });
        html += '</optgroup>';
    });
    return html;
}

function voteOptions(selectedCode = '') {
    let html = `<option value="NONE" ${selectedCode ? '' : 'selected'}>NONE (unassigned)</option>`;
    Object.entries(standardVotes).forEach(([code, vote]) => {
        html += `<option value="${escapeHtml(code)}" ${selectedCode === code ? 'selected' : ''}>[${escapeHtml(code)}] ${escapeHtml(vote.name)}</option>`;
    });
    return html;
}

function addNewItem() {
    const tbody = document.getElementById('invoice-items');
    const newRow = document.createElement('tr');
    newRow.className = 'border-b border-slate-800/70 invoice-item';
    newRow.dataset.newItem = 'true';
    
    newRow.innerHTML = `
        <td class="py-2 align-middle">
            <input type="text" name="items[${newItemIndex}][description]" required class="w-72 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs item-description">
            <input type="hidden" name="items[${newItemIndex}][id]" value="" class="item-id">
        </td>
        <td class="py-2 align-middle">
            <select name="items[${newItemIndex}][fee_structure_id]" class="w-56 rounded border border-slate-700 bg-slate-950/60 px-2 py-2 text-xs item-fee-select">
                ${feeOptions()}
            </select>
        </td>
        <td class="py-2 align-middle">
            <select name="items[${newItemIndex}][vote_code]" class="w-52 rounded border border-slate-700 bg-slate-950/60 px-2 py-2 text-xs item-vote-select">
                ${voteOptions()}
            </select>
        </td>
        <td class="py-2 align-middle">
            <input type="text" name="items[${newItemIndex}][category]" value="fees" class="w-28 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs item-category">
        </td>
        <td class="py-2 align-middle text-right">
            <input type="number" min="1" step="1" name="items[${newItemIndex}][quantity]" value="1" required class="w-20 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-right item-quantity">
        </td>
        <td class="py-2 align-middle text-right">
            <input type="number" min="0" step="0.01" name="items[${newItemIndex}][unit_amount]" value="0.00" required class="w-28 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-right item-unit-price">
        </td>
        <td class="py-2 align-middle text-right">
            <span class="item-total w-28 inline-block text-right">$0.00</span>
        </td>
        <td class="py-2 align-middle text-center">
            <button type="button" onclick="removeItem(this)" class="text-red-400 hover:text-red-300 text-xs">Remove</button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    newItemIndex++;
    
    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    newRow.querySelector('.item-fee-select').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (!this.value) {
            newRow.querySelector('.item-vote-select').value = 'NONE';
            return;
        }
        newRow.querySelector('.item-description').value = option.dataset.label || '';
        newRow.querySelector('.item-category').value = option.dataset.category || 'fees';
        newRow.querySelector('.item-unit-price').value = Number(option.dataset.amount || 0).toFixed(2);
        newRow.querySelector('.item-vote-select').value = option.dataset.voteCode || 'NONE';
        calculateTotal();
    });
    calculateTotal();
}

function addScheduleItem(fee) {
    addNewItem();
    const row = document.querySelector('#invoice-items tr:last-child');
    row.querySelector('.item-fee-select').value = String(fee.id);
    row.querySelector('.item-vote-select').value = fee.vote_code || 'NONE';
    row.querySelector('.item-description').value = fee.label || '';
    row.querySelector('.item-category').value = fee.category || 'fees';
    row.querySelector('.item-quantity').value = '1';
    row.querySelector('.item-unit-price').value = Number(fee.amount || 0).toFixed(2);
    calculateTotal();
}

function convertToVoteBreakdown() {
    if (!convertSchedule.length || !confirm('Replace the unpaid lump-sum line with its seven official vote lines? The schedule total must remain unchanged.')) return;
    document.getElementById('invoice-items').replaceChildren();
    newItemIndex = 0;
    convertSchedule.forEach(addScheduleItem);
    calculateTotal();
}

function removeItem(button) {
    if (Number(button.closest('tr')?.dataset.paid || 0) > 0) {
        alert('This item has recorded payments and cannot be removed.');
        return;
    }
    if (confirm('Are you sure you want to remove this item?')) {
        const row = button.closest('tr');
        row.remove();
        calculateTotal();
    }
    }

    document.querySelectorAll('.item-fee-select').forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const option = this.options[this.selectedIndex];
            if (!this.value) {
                row.querySelector('.item-vote-select').value = 'NONE';
                return;
            }
            row.querySelector('.item-description').value = option.dataset.label || '';
            row.querySelector('.item-category').value = option.dataset.category || 'fees';
            row.querySelector('.item-unit-price').value = Number(option.dataset.amount || 0).toFixed(2);
            row.querySelector('.item-vote-select').value = option.dataset.voteCode || 'NONE';
            calculateTotal();
        });
    });

function calculateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('.invoice-item');
    
    rows.forEach(row => {
        const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.item-unit-price').value) || 0;
        const lineTotal = quantity * unitPrice;
        
        row.querySelector('.item-total').textContent = '$' + lineTotal.toFixed(2);
        total += lineTotal;
    });
    
    document.getElementById('total-amount').textContent = '$' + total.toFixed(2);
}

// Add event listeners to existing inputs
document.querySelectorAll('.invoice-item input').forEach(input => {
    input.addEventListener('input', calculateTotal);
});

// Initial calculation
calculateTotal();
</script>
@endsection
