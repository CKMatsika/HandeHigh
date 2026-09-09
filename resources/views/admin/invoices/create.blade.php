@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Create Invoice</h1>
            <p class="text-xs text-slate-400 mt-1">Generate a student invoice linked with preset fee structures and automated pricing.</p>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Invoices
        </a>
    </div>

    @if(session('status'))
        <div class="bg-emerald-900/50 border border-emerald-700 text-emerald-200 rounded-xl p-4 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="bg-rose-950/60 border border-rose-800 text-rose-200 rounded-xl p-4 text-sm">
            <div class="font-semibold flex items-center gap-2 mb-1 text-rose-300">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs pl-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-xl p-6">
        <form action="{{ route('admin.invoices.store') }}" method="POST" id="invoiceForm" class="space-y-6">
            @csrf

            <!-- Student and Invoice Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Select Student *</label>
                    <select name="student_id" id="studentSelect" required
                            class="w-full px-3.5 py-2.5 bg-slate-800/90 border border-slate-700 rounded-xl text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="">-- Choose a student --</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" 
                                    data-first-name="{{ $student->first_name }}"
                                    data-last-name="{{ $student->last_name }}"
                                    data-grade="{{ $student->grade }}"
                                    data-class="{{ $student->class_name }}"
                                    data-boarding="{{ $student->is_boarding ? '1' : '0' }}"
                                    data-transport="{{ $student->has_transport ? '1' : '0' }}"
                                    {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->last_name }}, {{ $student->first_name }} ({{ $student->admission_number ?? $student->registration_number ?? 'ID: ' . $student->id }}) • {{ $student->grade ?? 'Ungraded' }} {{ $student->class_name ?? '' }} • {{ $student->is_boarding ? 'Boarder' : 'Day' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Student Info Badge -->
                    <div id="studentInfoBadge" class="hidden mt-2 flex-wrap items-center gap-2 text-xs">
                        <span class="px-2.5 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 font-medium" id="badgeGrade">Grade: —</span>
                        <span class="px-2.5 py-1 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400 font-medium" id="badgeResidency">Residency: —</span>
                        <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-medium hidden" id="badgeTransport">Transport Enrolled</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Academic Year *</label>
                    <select name="academic_year" id="academicYearSelect" required
                            class="w-full px-3.5 py-2.5 bg-slate-800/90 border border-slate-700 rounded-xl text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ old('academic_year', date('Y')) == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Term *</label>
                    <select name="term" id="termSelect" required
                            class="w-full px-3.5 py-2.5 bg-slate-800/90 border border-slate-700 rounded-xl text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        @foreach($terms as $term)
                            <option value="{{ $term }}" {{ old('term', 'Term 1') == $term ? 'selected' : '' }}>
                                {{ $term }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Issue Date *</label>
                    <input type="date" name="issued_at" required
                           value="{{ old('issued_at', now()->format('Y-m-d')) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-800/90 border border-slate-700 rounded-xl text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Due Date</label>
                    <input type="date" name="due_date"
                           value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-800/90 border border-slate-700 rounded-xl text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                </div>
            </div>

            <!-- Invoice Items Section -->
            <div class="pt-4 border-t border-slate-800">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-100">Invoice Items & Fee Types</h3>
                        <p class="text-xs text-slate-400">Select pre-configured fee types or enter custom fees with saved values.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="autoFillApplicableFees()" id="btnAutoFill"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-600/30 text-xs font-medium rounded-lg transition shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Auto-fill Student Fees
                        </button>
                        <button type="button" onclick="addInvoiceItem()" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium rounded-lg transition shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Item
                        </button>
                    </div>
                </div>

                <!-- Notification status for auto-fill -->
                <div id="feeNotification" class="hidden mb-3 p-2.5 bg-blue-500/10 border border-blue-500/30 text-blue-300 rounded-lg text-xs flex items-center justify-between">
                    <span id="feeNotificationText"></span>
                    <button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-blue-400 hover:text-white">&times;</button>
                </div>

                <!-- Items Container -->
                <div id="invoice-items" class="space-y-3">
                    <!-- Dynamic items rendered here -->
                </div>

                <!-- Total Display Card -->
                <div class="mt-4 p-4 bg-slate-800/80 rounded-xl border border-slate-700/60 flex flex-col sm:flex-row justify-between items-center gap-2">
                    <div class="text-xs text-slate-400">
                        Total items: <span id="items-count" class="font-medium text-slate-200">0</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-slate-300">Invoice Total:</span>
                        <span id="total-amount" class="text-xl font-bold text-emerald-400">$0.00</span>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-800">
                <a href="{{ route('admin.invoices.index') }}" 
                   class="px-5 py-2.5 bg-slate-800 text-slate-300 text-sm font-medium rounded-xl border border-slate-700 hover:bg-slate-700 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-xl shadow-lg shadow-blue-600/30 transition">
                    Create Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Embedded Fee Structures JSON -->
<script>
const feeStructures = @json($feeStructures);
let itemCount = 0;

function getFeeTypeOptions(selectedFeeId = null) {
    let options = '<option value="">-- Choose Preset Fee (or enter custom) --</option>';
    
    // Group fees by category or list them
    feeStructures.forEach(fee => {
        const selected = (selectedFeeId && String(selectedFeeId) === String(fee.id)) ? 'selected' : '';
        const gradeTag = fee.grade ? ` [${fee.grade}]` : ' [All Grades]';
        const optTag = fee.is_optional ? ' (Optional)' : '';
        options += `<option value="${fee.id}" 
            data-label="${escapeHtml(fee.label)}" 
            data-category="${escapeHtml(fee.category || 'fees')}" 
            data-amount="${parseFloat(fee.amount || 0).toFixed(2)}"
            data-grade="${escapeHtml(fee.grade || '')}"
            data-year="${escapeHtml(fee.academic_year || '')}"
            data-term="${escapeHtml(fee.term || '')}"
            data-service="${escapeHtml(fee.service_type || '')}"
            ${selected}>
            ${escapeHtml(fee.label)}${gradeTag} — $${parseFloat(fee.amount || 0).toFixed(2)} (${capitalize(fee.category || 'Fee')}${optTag})
        </option>`;
    });

    return options;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function addInvoiceItem(preset = null) {
    const container = document.getElementById('invoice-items');
    const index = itemCount++;
    
    const feeId = preset ? (preset.fee_structure_id || preset.id || '') : '';
    const description = preset ? (preset.label || preset.description || '') : '';
    const category = preset ? (preset.category || 'fees') : '';
    const quantity = preset ? (preset.quantity || 1) : 1;
    const unitAmount = preset ? parseFloat(preset.amount || preset.unit_amount || 0).toFixed(2) : '0.00';

    const row = document.createElement('div');
    row.className = 'invoice-item bg-slate-800/60 hover:bg-slate-800/90 rounded-xl p-3.5 border border-slate-700/60 transition space-y-3';
    row.id = `item-row-${index}`;
    
    row.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <!-- Fee Type Dropdown -->
            <div class="md:col-span-4">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Fee Type / Preset</label>
                <select name="items[${index}][fee_structure_id]" class="fee-select w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    ${getFeeTypeOptions(feeId)}
                </select>
            </div>

            <!-- Description -->
            <div class="md:col-span-3">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Description *</label>
                <input type="text" name="items[${index}][description]" required value="${escapeHtml(description)}"
                       placeholder="e.g. Tuition Fee" 
                       class="item-description w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-slate-100 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Category -->
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Category</label>
                <input type="text" name="items[${index}][category]" value="${escapeHtml(category)}"
                       placeholder="tuition / boarding" 
                       class="item-category w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-slate-100 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Qty -->
            <div class="md:col-span-1">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Qty *</label>
                <input type="number" name="items[${index}][quantity]" required min="1" step="1" value="${quantity}"
                       class="item-quantity w-full px-2.5 py-2 bg-slate-900 border border-slate-700 rounded-lg text-slate-100 text-xs text-center focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Unit Price -->
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Unit Price ($) *</label>
                <div class="flex items-center gap-1.5">
                    <input type="number" name="items[${index}][unit_amount]" required min="0" step="0.01" value="${unitAmount}"
                           class="item-unit-amount w-full px-2.5 py-2 bg-slate-900 border border-slate-700 rounded-lg text-slate-100 text-xs font-semibold focus:ring-2 focus:ring-blue-500">
                    <button type="button" onclick="removeInvoiceItem(${index})" title="Remove item"
                            class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;

    container.appendChild(row);

    // Bind event listeners
    const feeSelect = row.querySelector('.fee-select');
    const descInput = row.querySelector('.item-description');
    const catInput = row.querySelector('.item-category');
    const qtyInput = row.querySelector('.item-quantity');
    const priceInput = row.querySelector('.item-unit-amount');

    feeSelect.addEventListener('change', function() {
        const selectedOpt = this.options[this.selectedIndex];
        if (this.value && selectedOpt) {
            descInput.value = selectedOpt.dataset.label || '';
            catInput.value = selectedOpt.dataset.category || 'fees';
            priceInput.value = selectedOpt.dataset.amount || '0.00';
        }
        calculateTotal();
    });

    qtyInput.addEventListener('input', calculateTotal);
    priceInput.addEventListener('input', calculateTotal);

    calculateTotal();
}

function removeInvoiceItem(index) {
    const row = document.getElementById(`item-row-${index}`);
    if (row) {
        row.remove();
        calculateTotal();
    }
}

function calculateTotal() {
    let total = 0;
    const items = document.querySelectorAll('.invoice-item');
    
    items.forEach(item => {
        const qty = parseFloat(item.querySelector('.item-quantity')?.value) || 0;
        const price = parseFloat(item.querySelector('.item-unit-amount')?.value) || 0;
        total += qty * price;
    });

    document.getElementById('total-amount').textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('items-count').textContent = items.length;
}

function autoFillApplicableFees() {
    const studentSelect = document.getElementById('studentSelect');
    const selectedOpt = studentSelect.options[studentSelect.selectedIndex];
    
    if (!studentSelect.value || !selectedOpt) {
        showNotification('Please select a student first.', 'warning');
        return;
    }

    const studentGrade = (selectedOpt.dataset.grade || '').trim().toLowerCase();
    const isBoarding = selectedOpt.dataset.boarding === '1';
    const hasTransport = selectedOpt.dataset.transport === '1';

    // Filter fees
    const matchingFees = feeStructures.filter(fee => {
        const feeGrade = (fee.grade || '').trim().toLowerCase();
        const feeCat = (fee.category || '').trim().toLowerCase();
        const feeService = (fee.service_type || '').trim().toLowerCase();

        // 1. Grade check: matches student grade OR is global (no grade specified)
        if (feeGrade && studentGrade && feeGrade !== studentGrade) {
            return false;
        }

        // 2. Boarding check: If fee is boarding and student is not boarding, exclude
        const isBoardingFee = feeCat.includes('boarding') || feeService === 'boarding';
        if (isBoardingFee && !isBoarding) {
            return false;
        }

        // 3. Transport check: If fee is transport and student has no transport, exclude
        const isTransportFee = feeCat.includes('transport') || feeService === 'transport';
        if (isTransportFee && !hasTransport) {
            return false;
        }

        // 4. Optional check: Skip optional subject fees by default in auto-fill
        if (fee.is_optional && (feeCat === 'subject' || fee.subject_name)) {
            return false;
        }

        return true;
    });

    if (matchingFees.length === 0) {
        showNotification(`No specific preset fees found for ${selectedOpt.dataset.grade || 'this grade'}. You can choose preset fee types or add items manually.`, 'warning');
        if (document.querySelectorAll('.invoice-item').length === 0) {
            addInvoiceItem();
        }
        return;
    }

    // Clear existing items and populate matching fees
    const container = document.getElementById('invoice-items');
    container.innerHTML = '';
    itemCount = 0;

    matchingFees.forEach(fee => {
        addInvoiceItem({
            fee_structure_id: fee.id,
            label: fee.label,
            category: fee.category,
            amount: fee.amount,
            quantity: 1,
        });
    });

    showNotification(`Successfully loaded ${matchingFees.length} applicable fee item(s) for ${selectedOpt.dataset.grade || 'Student'} (${isBoarding ? 'Boarder' : 'Day Scholar'}).`, 'success');
}

function showNotification(text, type = 'info') {
    const box = document.getElementById('feeNotification');
    const span = document.getElementById('feeNotificationText');
    if (!box || !span) return;

    span.textContent = text;
    box.className = `mb-3 p-3 rounded-xl text-xs flex items-center justify-between ${
        type === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' :
        type === 'warning' ? 'bg-amber-500/10 border border-amber-500/30 text-amber-300' :
        'bg-blue-500/10 border border-blue-500/30 text-blue-300'
    }`;
    box.classList.remove('hidden');
}

// When student changes, update badge and auto-fill fees
document.getElementById('studentSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const badgeContainer = document.getElementById('studentInfoBadge');
    
    if (this.value && opt) {
        badgeContainer.classList.remove('hidden');
        badgeContainer.classList.add('flex');
        
        document.getElementById('badgeGrade').textContent = 'Grade: ' + (opt.dataset.grade || 'Not assigned') + (opt.dataset.class ? ' (' + opt.dataset.class + ')' : '');
        document.getElementById('badgeResidency').textContent = 'Residency: ' + (opt.dataset.boarding === '1' ? 'Boarder' : 'Day Scholar');
        
        const transportBadge = document.getElementById('badgeTransport');
        if (opt.dataset.transport === '1') {
            transportBadge.classList.remove('hidden');
        } else {
            transportBadge.classList.add('hidden');
        }

        // Auto-fill fees automatically for ease of use
        autoFillApplicableFees();
    } else {
        badgeContainer.classList.add('hidden');
    }
});

// Initialize with 1 empty item if none present
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.getElementById('studentSelect');
    if (studentSelect && studentSelect.value) {
        studentSelect.dispatchEvent(new Event('change'));
    } else if (document.querySelectorAll('.invoice-item').length === 0) {
        addInvoiceItem();
    }
});
</script>
@endsection
