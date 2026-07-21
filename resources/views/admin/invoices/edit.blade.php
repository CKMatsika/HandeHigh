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
                    <button type="button" onclick="addNewItem()" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                        Add Item
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-slate-100">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400">
                                <th class="text-left py-2 font-medium">Description</th>
                                <th class="text-left py-2 font-medium">Category</th>
                                <th class="text-right py-2 font-medium">Qty</th>
                                <th class="text-right py-2 font-medium">Unit Price</th>
                                <th class="text-right py-2 font-medium">Total</th>
                                <th class="text-center py-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invoice-items">
                            @foreach($invoice->items as $index => $item)
                                <tr class="border-b border-slate-800/70 invoice-item" data-item-id="{{ $item->id }}">
                                    <td class="py-2 align-middle">
                                        <input type="text" name="items[{{ $index }}][description]" value="{{ old('items.' . $index . '.description', $item->description) }}" required class="w-72 rounded border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs item-description">
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}" class="item-id">
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
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        <button type="button" onclick="removeItem(this)" class="text-red-400 hover:text-red-300 text-xs">Remove</button>
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

            <div class="flex justify-end gap-2">
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">Save changes</button>
                <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Cancel</a>
            </div>
        </form>
    </div>

<script>
let newItemIndex = {{ $invoice->items->count() }};

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
    
    // Add event listeners to new inputs
    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
}

function removeItem(button) {
    if (confirm('Are you sure you want to remove this item?')) {
        const row = button.closest('tr');
        row.remove();
        calculateTotal();
    }
}

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
