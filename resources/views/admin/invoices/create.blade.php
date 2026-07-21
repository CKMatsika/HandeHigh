@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Create Invoice</h1>
            <p class="text-xs text-slate-400 mt-1">Generate a new invoice for a student.</p>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
    </div>

    @if(session('status'))
        <div class="bg-emerald-900/50 border border-emerald-700 text-emerald-200 rounded-lg p-4 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-900/50 border border-red-700 text-red-200 rounded-lg p-4 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('admin.invoices.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Student and Invoice Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Student *</label>
                    <select name="student_id" required
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->last_name }}, {{ $student->first_name }} ({{ $student->student_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Academic Year *</label>
                    <select name="academic_year" required
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select academic year</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ old('academic_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Term *</label>
                    <select name="term" required
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select term</option>
                        @foreach($terms as $term)
                            <option value="{{ $term }}" {{ old('term') == $term ? 'selected' : '' }}>
                                {{ $term }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Issue Date *</label>
                    <input type="date" name="issued_at" required
                           value="{{ old('issued_at', now()->format('Y-m-d')) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Due Date</label>
                    <input type="date" name="due_date"
                           value="{{ old('due_date') }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Invoice Items -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-slate-200">Invoice Items</h3>
                    <button type="button" onclick="addInvoiceItem()" 
                            class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                        Add Item
                    </button>
                </div>

                <div id="invoice-items" class="space-y-3">
                    <!-- Initial item row -->
                    <div class="invoice-item grid grid-cols-1 md:grid-cols-12 gap-3 p-3 bg-slate-800/50 rounded-lg">
                        <div class="md:col-span-5">
                            <input type="text" name="items[0][description]" required
                                   placeholder="Description" 
                                   value="{{ old('items.0.description') }}"
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-3">
                            <input type="text" name="items[0][category]" 
                                   placeholder="Category (optional)" 
                                   value="{{ old('items.0.category') }}"
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <input type="number" name="items[0][quantity]" required min="1" step="1"
                                   placeholder="Qty" 
                                   value="{{ old('items.0.quantity', 1) }}"
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <input type="number" name="items[0][unit_amount]" required min="0" step="0.01"
                                   placeholder="Unit Price" 
                                   value="{{ old('items.0.unit_amount') }}"
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Total Display -->
                <div class="mt-4 p-3 bg-slate-800 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-300">Total Amount:</span>
                        <span id="total-amount" class="text-lg font-bold text-emerald-500">$0.00</span>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-700">
                <a href="{{ route('admin.invoices.index') }}" 
                   class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Create Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let itemCount = 1;

function addInvoiceItem() {
    const container = document.getElementById('invoice-items');
    const newItem = document.createElement('div');
    newItem.className = 'invoice-item grid grid-cols-1 md:grid-cols-12 gap-3 p-3 bg-slate-800/50 rounded-lg';
    
    newItem.innerHTML = `
        <div class="md:col-span-5">
            <input type="text" name="items[${itemCount}][description]" required
                   placeholder="Description" 
                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="md:col-span-3">
            <input type="text" name="items[${itemCount}][category]" 
                   placeholder="Category (optional)" 
                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="md:col-span-2">
            <input type="number" name="items[${itemCount}][quantity]" required min="1" step="1"
                   placeholder="Qty" value="1"
                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="md:col-span-2">
            <input type="number" name="items[${itemCount}][unit_amount]" required min="0" step="0.01"
                   placeholder="Unit Price" 
                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    `;
    
    container.appendChild(newItem);
    itemCount++;
    
    // Add event listeners to new inputs
    newItem.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
}

function calculateTotal() {
    let total = 0;
    const items = document.querySelectorAll('.invoice-item');
    
    items.forEach(item => {
        const quantity = parseFloat(item.querySelector('input[name*="quantity"]').value) || 0;
        const unitAmount = parseFloat(item.querySelector('input[name*="unit_amount"]').value) || 0;
        total += quantity * unitAmount;
    });
    
    document.getElementById('total-amount').textContent = '$' + total.toFixed(2);
}

// Add event listeners to initial inputs
document.querySelectorAll('.invoice-item input').forEach(input => {
    input.addEventListener('input', calculateTotal);
});
</script>
@endsection
