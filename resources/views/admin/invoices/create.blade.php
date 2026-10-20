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

            <!-- Saved Fee Structures Picker (Manual Selection by Ticking) -->
            <div class="pt-4 border-t border-slate-800">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 mb-5 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-2.5 border-b border-slate-800/80">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400 font-semibold text-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-xs font-semibold text-slate-100">Select Fee Structures to Invoice (Tick to Include)</h3>
                                <p class="text-[11px] text-slate-400">Tick individual fee structures to include on this invoice. Items and totals update automatically.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="selectRecommendedFees()" class="px-2.5 py-1 bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 hover:bg-indigo-600/30 text-[11px] font-medium rounded-lg transition">
                                Select Recommended
                            </button>
                            <button type="button" onclick="clearAllFeeSelections()" class="px-2.5 py-1 bg-slate-800 text-slate-300 border border-slate-700 hover:bg-slate-700 text-[11px] font-medium rounded-lg transition">
                                Clear All
                            </button>
                        </div>
                    </div>

                    @if($feeStructures->isNotEmpty())
                        <div id="single-fee-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                            @foreach($feeStructures as $fee)
                                @php
                                    $isBoarding = str_contains(strtolower($fee->category ?? ''), 'boarding') || $fee->service_type === 'boarding' || str_contains(strtolower($fee->label ?? ''), 'boarding');
                                    $isTransport = str_contains(strtolower($fee->category ?? ''), 'transport') || $fee->service_type === 'transport';
                                @endphp
                                <label class="single-fee-card flex items-start gap-2.5 p-3 rounded-xl border border-slate-800 bg-slate-950/50 hover:border-slate-700 hover:bg-slate-900/60 cursor-pointer transition select-none"
                                       data-fee-id="{{ $fee->id }}"
                                       data-label="{{ $fee->label }}"
                                       data-category="{{ $fee->category ?? 'fees' }}"
                                       data-amount="{{ (float) $fee->amount }}"
                                       data-grade="{{ strtolower($fee->grade ?? '') }}"
                                       data-is-boarding="{{ $isBoarding ? '1' : '0' }}"
                                       data-is-transport="{{ $isTransport ? '1' : '0' }}"
                                       data-is-optional="{{ $fee->is_optional ? '1' : '0' }}"
                                       data-vote-code="{{ $fee->vote_code ?? '' }}"
                                       data-residency="{{ $fee->residency_type ?? 'all' }}">
                                    <input type="checkbox"
                                           class="single-fee-checkbox mt-0.5 rounded border-slate-600 bg-slate-900 text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                                           value="{{ $fee->id }}"
                                           onchange="onFeeCheckboxToggle(this)">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1.5">
                                            <span class="text-xs font-semibold text-slate-200 truncate">{{ $fee->label }}</span>
                                            <span class="text-xs font-bold text-emerald-400 font-mono shrink-0">${{ number_format($fee->amount, 2) }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                            @if($isBoarding)
                                                <span class="rounded bg-purple-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-purple-300">Boarding</span>
                                            @elseif($isTransport)
                                                <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-sky-300">Transport</span>
                                            @else
                                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-300">{{ ucfirst($fee->category ?? 'Fee') }}</span>
                                            @endif

                                            @if($fee->grade)
                                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-400">{{ $fee->grade }}</span>
                                            @else
                                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-400">All Grades</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">No saved fee structures found for this school.</p>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-100">Invoice Items</h3>
                        <p class="text-xs text-slate-400">Add official vote schedules or individual fee and custom items.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="addInvoiceItem(); document.querySelector('#invoice-items .invoice-item:last-child .fee-select')?.focus()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition">
                            Add from Fee Structure
                        </button>
                        <button type="button" onclick="loadVoteSchedule('boarding')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600/20 hover:bg-purple-600/30 text-purple-200 text-xs font-medium rounded-lg border border-purple-500/30 transition">
                            Load Boarding Schedule ($470.00)
                        </button>
                        <button type="button" onclick="loadVoteSchedule('day')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-600/20 hover:bg-sky-600/30 text-sky-200 text-xs font-medium rounded-lg border border-sky-500/30 transition">
                            Load Day Schedule ($135.00)
                        </button>
                        <button type="button" onclick="addInvoiceItem()" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium rounded-lg transition shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Manual Item
                        </button>
                    </div>
                </div>

                <!-- Notification status -->
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
const standardVotes = @json($standardVotes);
let itemCount = 0;

function getFeeTypeOptions(selectedFeeId = null) {
    let options = '<option value="">-- Choose Preset Fee (or enter custom) --</option>';
    const groups = {
        'Boarding Votes': [],
        'Day-Scholar Votes': [],
        'Other Levies & Fees': [],
    };

    feeStructures.forEach(fee => {
        const group = fee.residency_type === 'boarding' && fee.vote_code
            ? 'Boarding Votes'
            : fee.residency_type === 'day' && fee.vote_code
                ? 'Day-Scholar Votes'
                : 'Other Levies & Fees';
        groups[group].push(fee);
    });

    Object.entries(groups).forEach(([groupName, fees]) => {
        if (!fees.length) return;
        options += `<optgroup label="${groupName}">`;
        fees.forEach(fee => {
            const selected = (selectedFeeId && String(selectedFeeId) === String(fee.id)) ? 'selected' : '';
            const gradeTag = fee.grade ? ` [${fee.grade}]` : ' [All Grades]';
            const optTag = fee.is_optional ? ' (Optional)' : '';
            options += `<option value="${fee.id}"
                data-label="${escapeHtml(fee.label)}"
                data-category="${escapeHtml(fee.category || 'fees')}"
                data-amount="${parseFloat(fee.amount || 0).toFixed(2)}"
                data-vote-code="${escapeHtml(fee.vote_code || '')}"
                ${selected}>
                ${escapeHtml(fee.label)}${gradeTag} — $${parseFloat(fee.amount || 0).toFixed(2)} (${capitalize(fee.category || 'Fee')}${optTag})
            </option>`;
        });
        options += '</optgroup>';
    });

    return options;
}

function getVoteCodeOptions(selectedVoteCode = '') {
    let options = `<option value="NONE" ${!selectedVoteCode ? 'selected' : ''}>NONE (unassigned)</option>`;
    Object.entries(standardVotes).forEach(([code, vote]) => {
        options += `<option value="${escapeHtml(code)}" ${selectedVoteCode === code ? 'selected' : ''}>[${escapeHtml(code)}] ${escapeHtml(vote.name)}</option>`;
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
    const voteCode = preset ? (preset.vote_code || '') : '';

    const row = document.createElement('div');
    row.className = 'invoice-item bg-slate-800/60 hover:bg-slate-800/90 rounded-xl p-3.5 border border-slate-700/60 transition space-y-3';
    row.id = `item-row-${index}`;
    if (feeId) {
        row.setAttribute('data-linked-fee-id', feeId);
    }
    
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

            <div class="md:col-span-4">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">MOPSE Vote / Sub-account</label>
                <select name="items[${index}][vote_code]" class="vote-select w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                    ${getVoteCodeOptions(voteCode)}
                </select>
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
        const prevFeeId = row.getAttribute('data-linked-fee-id');
        if (prevFeeId) {
            const prevCb = document.querySelector(`.single-fee-checkbox[value="${prevFeeId}"]`);
            if (prevCb) {
                prevCb.checked = false;
                const prevCard = prevCb.closest('.single-fee-card');
                if (prevCard) {
                    prevCard.classList.remove('border-indigo-500/60', 'bg-indigo-500/10');
                    prevCard.classList.add('border-slate-800', 'bg-slate-950/50');
                }
            }
        }

        const selectedOpt = this.options[this.selectedIndex];
        if (this.value && selectedOpt) {
            descInput.value = selectedOpt.dataset.label || '';
            catInput.value = selectedOpt.dataset.category || 'fees';
            priceInput.value = selectedOpt.dataset.amount || '0.00';
            row.querySelector('.vote-select').value = selectedOpt.dataset.voteCode || 'NONE';
            row.setAttribute('data-linked-fee-id', this.value);

            const newCb = document.querySelector(`.single-fee-checkbox[value="${this.value}"]`);
            if (newCb) {
                newCb.checked = true;
                const newCard = newCb.closest('.single-fee-card');
                if (newCard) {
                    newCard.classList.add('border-indigo-500/60', 'bg-indigo-500/10');
                    newCard.classList.remove('border-slate-800', 'bg-slate-950/50');
                }
            }
        } else {
            row.removeAttribute('data-linked-fee-id');
            row.querySelector('.vote-select').value = 'NONE';
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
        const linkedFeeId = row.getAttribute('data-linked-fee-id');
        if (linkedFeeId) {
            const cb = document.querySelector(`.single-fee-checkbox[value="${linkedFeeId}"]`);
            if (cb) {
                cb.checked = false;
                const card = cb.closest('.single-fee-card');
                if (card) {
                    card.classList.remove('border-indigo-500/60', 'bg-indigo-500/10');
                    card.classList.add('border-slate-800', 'bg-slate-950/50');
                }
            }
        }
        row.remove();
        calculateTotal();
    }
}

function onFeeCheckboxToggle(checkbox) {
    const feeId = checkbox.value;
    const card = checkbox.closest('.single-fee-card');

    if (checkbox.checked) {
        if (card) {
            card.classList.add('border-indigo-500/60', 'bg-indigo-500/10');
            card.classList.remove('border-slate-800', 'bg-slate-950/50');
        }

        // Add item row if not already in list
        const existingRow = document.querySelector(`.invoice-item[data-linked-fee-id="${feeId}"]`);
        if (!existingRow) {
            addInvoiceItem({
                fee_structure_id: feeId,
                label: card.dataset.label,
                category: card.dataset.category,
                amount: card.dataset.amount,
                vote_code: card.dataset.voteCode,
                quantity: 1,
            });
        }
    } else {
        if (card) {
            card.classList.remove('border-indigo-500/60', 'bg-indigo-500/10');
            card.classList.add('border-slate-800', 'bg-slate-950/50');
        }

        // Remove row linked to this fee
        const linkedRow = document.querySelector(`.invoice-item[data-linked-fee-id="${feeId}"]`);
        if (linkedRow) {
            linkedRow.remove();
            calculateTotal();
        }
    }
}

function selectRecommendedFees() {
    const studentSelect = document.getElementById('studentSelect');
    const opt = studentSelect ? studentSelect.options[studentSelect.selectedIndex] : null;

    if (!studentSelect?.value || !opt) {
        showNotification('Please select a student first.', 'warning');
        return;
    }

    const studentGrade = (opt.dataset.grade || '').trim().toLowerCase();
    const isBoarding = opt.dataset.boarding === '1';
    const hasTransport = opt.dataset.transport === '1';

    let count = 0;
    document.querySelectorAll('.single-fee-checkbox').forEach(cb => {
        const card = cb.closest('.single-fee-card');
        const feeGrade = (card.dataset.grade || '').trim().toLowerCase();
        const feeIsBoarding = card.dataset.isBoarding === '1';
        const feeIsTransport = card.dataset.isTransport === '1';
        const feeIsOptional = card.dataset.isOptional === '1';

        // Check if grade matches
        if (feeGrade && studentGrade && feeGrade !== studentGrade) {
            if (cb.checked) {
                cb.checked = false;
                onFeeCheckboxToggle(cb);
            }
            return;
        }

        // Don't select boarding fee for day scholars
        if (feeIsBoarding && !isBoarding) {
            if (cb.checked) {
                cb.checked = false;
                onFeeCheckboxToggle(cb);
            }
            return;
        }

        // Don't select transport fee for students without transport
        if (feeIsTransport && !hasTransport) {
            if (cb.checked) {
                cb.checked = false;
                onFeeCheckboxToggle(cb);
            }
            return;
        }

        if (feeIsOptional) {
            return; // skip optional
        }

        cb.checked = true;
        onFeeCheckboxToggle(cb);
        count++;
    });

    showNotification(`Selected ${count} recommended fee structure(s) for ${opt.dataset.grade || 'student'} (${isBoarding ? 'Boarder' : 'Day Scholar'}).`, 'success');
}

function clearAllFeeSelections() {
    document.querySelectorAll('.single-fee-checkbox').forEach(cb => {
        if (cb.checked) {
            cb.checked = false;
            onFeeCheckboxToggle(cb);
        }

        function loadVoteSchedule(residency) {
            const studentSelect = document.getElementById('studentSelect');
            const studentOption = studentSelect?.options[studentSelect.selectedIndex];
            if (!studentSelect?.value || !studentOption) {
                showNotification('Select a student before loading a residency schedule.', 'warning');
                return;
            }
            const studentResidency = studentOption.dataset.boarding === '1' ? 'boarding' : 'day';
            if (studentResidency !== residency) {
                showNotification(`This student is a ${studentResidency === 'boarding' ? 'Boarder' : 'Day Scholar'}. Load the matching schedule instead.`, 'warning');
                return;
            }

            const year = document.getElementById('academicYearSelect')?.value;
            const term = document.getElementById('termSelect')?.value;
            const prefix = residency === 'boarding' ? 'BOARD-PKG-' : 'DAY-PKG-';
            const packageFee = feeStructures.find(fee =>
                fee.code === `${prefix}${year}` &&
                fee.academic_year === year &&
                fee.term === term &&
                fee.residency_type === residency
            );
            const schedule = packageFee
                ? feeStructures.filter(fee => Number(fee.parent_id) === Number(packageFee.id) && fee.vote_code)
                    .sort((a, b) => Number(a.allocation_priority || 10) - Number(b.allocation_priority || 10))
                : [];
            const expectedTotal = residency === 'boarding' ? 470 : 135;
            const scheduleTotal = schedule.reduce((total, fee) => total + Number(fee.amount || 0), 0);

            if (schedule.length !== 7 || Math.abs(scheduleTotal - expectedTotal) >= 0.01) {
                showNotification(`The official ${residency} schedule for ${year} ${term} is missing or does not total $${expectedTotal.toFixed(2)}. No invoice lines were changed.`, 'warning');
                return;
            }

            clearAllFeeSelections();
            schedule.forEach(fee => {
                const checkbox = document.querySelector(`.single-fee-checkbox[value="${fee.id}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    const card = checkbox.closest('.single-fee-card');
                    if (card) {
                        card.classList.add('border-indigo-500/60', 'bg-indigo-500/10');
                        card.classList.remove('border-slate-800', 'bg-slate-950/50');
                    }
                }
                addInvoiceItem({
                    fee_structure_id: fee.id,
                    label: fee.label,
                    category: fee.category,
                    amount: fee.amount,
                    vote_code: fee.vote_code,
                    quantity: 1,
                });
            });
            showNotification(`Loaded the ${residency} vote schedule totaling $${scheduleTotal.toFixed(2)}.`, 'success');
        }
    });
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

// When student changes, update badge (without auto-forcing fee items)
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

        // Highlight matching fees for user's convenience
        const studentGrade = (opt.dataset.grade || '').trim().toLowerCase();
        document.querySelectorAll('.single-fee-card').forEach(card => {
            const feeGrade = (card.dataset.grade || '').trim().toLowerCase();
            if (!feeGrade || !studentGrade || feeGrade === studentGrade) {
                card.style.opacity = '1';
            } else {
                card.style.opacity = '0.55';
            }
        });
    } else {
        badgeContainer.classList.add('hidden');
        document.querySelectorAll('.single-fee-card').forEach(card => { card.style.opacity = '1'; });
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.getElementById('studentSelect');
    if (studentSelect && studentSelect.value) {
        studentSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
