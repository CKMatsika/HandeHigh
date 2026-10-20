@extends('layouts.app')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.invoices.index') }}" class="hover:text-slate-200 transition">Invoices</a>
                <span>/</span>
                <span class="text-indigo-400">Bulk Invoicing & Preview</span>
            </div>
            <h1 class="text-xl font-bold text-slate-50">Bulk Invoicing & Student Billing</h1>
            <p class="text-xs text-slate-400 mt-1">Filter by Form, Class, and Boarding status to preview individual student billing before generating invoices.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.fees.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-900/60 px-3.5 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800 transition">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Fee Structures
            </a>
            <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/60 px-3.5 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800 transition">
                Back to Invoices
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs text-emerald-300 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs text-rose-300">
            <p class="font-semibold mb-1">Please fix the following issues:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 1. Filter & Targeting Toolbar (GET) --}}
    <form method="GET" action="{{ route('admin.invoices.bulk.create') }}" class="mb-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400 font-semibold text-xs">1</span>
                    <h2 class="text-sm font-semibold text-slate-100">Target Cohort & Academic Period</h2>
                </div>
                <span class="text-[11px] text-slate-400">Adjust filters to automatically recalculate billing preview</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1" for="filter_year">Academic Year</label>
                    <select id="filter_year" name="academic_year" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ (string)$selectedYear === (string)$year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1" for="filter_term">Term</label>
                    <select id="filter_term" name="term" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach($terms as $term)
                            <option value="{{ $term }}" {{ $selectedTerm === $term ? 'selected' : '' }}>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1" for="filter_grade">Form / Grade</label>
                    <select id="filter_grade" name="grade" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">All Forms</option>
                        @foreach($grades as $g)
                            <option value="{{ $g }}" {{ $selectedGrade === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1" for="filter_class">Class / Stream</label>
                    <select id="filter_class" name="class_name" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">All Classes</option>
                        @foreach($classes as $c)
                            <option value="{{ $c }}" {{ $selectedClass === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1" for="filter_residency">Student Residency</label>
                    <select id="filter_residency" name="residency" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="all" {{ $selectedResidency === 'all' ? 'selected' : '' }}>All Students</option>
                        <option value="boarding" {{ $selectedResidency === 'boarding' ? 'selected' : '' }}>Boarders Only (With Boarding Fee)</option>
                        <option value="day" {{ $selectedResidency === 'day' ? 'selected' : '' }}>Day Scholars Only (No Boarding Fee)</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-4 pt-3 border-t border-slate-800/60">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="include_optional" name="include_optional" value="1" {{ $includeOptional ? 'checked' : '' }}
                           class="rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
                    <label for="include_optional" class="text-xs text-slate-300 cursor-pointer">
                        Include optional extracurricular & activity fees if configured
                    </label>
                </div>

                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-500 transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Apply Filter & Calculate Preview
                </button>
            </div>
        </div>
    </form>

    {{-- 2. Batch Invoicing POST Form --}}
    <form id="bulk-invoice-form" method="POST" action="{{ route('admin.invoices.bulk.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="academic_year" value="{{ $selectedYear }}">
        <input type="hidden" name="term" value="{{ $selectedTerm }}">
        <input type="hidden" name="grade" value="{{ $selectedGrade }}">
        <input type="hidden" name="include_optional" value="{{ $includeOptional ? '1' : '0' }}">

        {{-- 2. Fee Structure Selection (Manual Ticking) Card --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-400 font-semibold text-xs">2</span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-100">Select Fee Structures to Invoice (Tick to Include)</h2>
                        <p class="text-[11px] text-slate-400">Manually select which saved fee structures to bill. The student preview below recalculates immediately.</p>
                    </div>
                </div>
                
                @if($feeStructures->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="btn-fee-select-all" class="px-2.5 py-1 rounded-lg border border-slate-700 bg-slate-800 text-[11px] font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
                            Select All
                        </button>
                        <button type="button" id="btn-fee-clear-all" class="px-2.5 py-1 rounded-lg border border-slate-700 bg-slate-800 text-[11px] font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
                            Clear All
                        </button>
                        <button type="button" id="btn-fee-boarding-only" class="px-2.5 py-1 rounded-lg border border-purple-800/80 bg-purple-950/40 text-[11px] font-medium text-purple-300 hover:bg-purple-900/60 transition">
                            Boarding Only
                        </button>
                        <button type="button" id="btn-fee-tuition-only" class="px-2.5 py-1 rounded-lg border border-indigo-800/80 bg-indigo-950/40 text-[11px] font-medium text-indigo-300 hover:bg-indigo-900/60 transition">
                            Exclude Boarding Charge
                        </button>
                    </div>
                @endif
            </div>

            @if($feeStructures->isNotEmpty())
                <div class="space-y-3">
                    <p class="text-xs text-slate-300">
                        Tick the fee structures you want to include on this invoice run. Unchecked items will be completely omitted:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($feeStructures as $fee)
                            @php
                                $isBoarding = str_contains(strtolower($fee->category ?? ''), 'boarding') || strtolower((string) $fee->service_type) === 'boarding' || str_contains(strtolower($fee->label ?? ''), 'boarding');
                                $isTransport = str_contains(strtolower($fee->category ?? ''), 'transport') || strtolower((string) $fee->service_type) === 'transport' || str_contains(strtolower($fee->label ?? ''), 'transport');
                                $isChecked = in_array($fee->id, $selectedFeeStructureIds);
                            @endphp
                            <label class="fee-card flex items-start gap-3 p-3.5 rounded-xl border {{ $isChecked ? 'border-indigo-500/60 bg-indigo-500/10' : 'border-slate-800 bg-slate-950/50' }} hover:border-slate-700 hover:bg-slate-900/60 transition cursor-pointer select-none">
                                <input type="checkbox" name="fee_structure_ids[]" value="{{ $fee->id }}"
                                       class="fee-structure-checkbox mt-0.5 rounded border-slate-600 bg-slate-900 text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                                       data-id="{{ $fee->id }}"
                                       data-amount="{{ (float) $fee->amount }}"
                                       data-label="{{ $fee->label }}"
                                       data-category="{{ strtolower($fee->category ?? 'fees') }}"
                                       data-service="{{ strtolower($fee->service_type ?? '') }}"
                                       data-grade="{{ strtolower($fee->grade ?? '') }}"
                                       data-is-boarding="{{ $isBoarding ? '1' : '0' }}"
                                       data-is-transport="{{ $isTransport ? '1' : '0' }}"
                                       data-is-optional="{{ $fee->is_optional ? '1' : '0' }}"
                                       data-parent-id="{{ $fee->parent_id ?? '' }}"
                                       data-residency="{{ strtolower($fee->residency_type ?? 'all') }}"
                                       data-vote-code="{{ $fee->vote_code ?? '' }}"
                                       {{ $isChecked ? 'checked' : '' }}>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold text-slate-100 text-xs truncate">{{ $fee->label }}</span>
                                        <span class="text-emerald-400 font-bold font-mono text-xs shrink-0">${{ number_format($fee->amount, 2) }}</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                        @if($isBoarding)
                                            <span class="rounded bg-purple-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-purple-300">Boarders Only</span>
                                        @elseif($isTransport)
                                            <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-sky-300">Transport Only</span>
                                        @else
                                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-300">All Students</span>
                                        @endif

                                        @if($fee->grade)
                                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-400">{{ $fee->grade }}</span>
                                        @endif

                                        @if($fee->is_optional)
                                            <span class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[10px] text-amber-300">Optional</span>
                                        @endif
                                        @if($fee->vote_code)
                                            <span class="rounded bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-mono text-emerald-300">[{{ $fee->vote_code }}]</span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <div class="rounded-xl bg-slate-950/40 border border-slate-800/60 p-3 text-[11px] text-slate-400 flex items-start gap-2">
                        <svg class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>
                            <strong>Manual Selection Active:</strong> Only fee structures ticked above will be billed. Boarding fees still strictly apply only to boarders. Ticking/unticking recalculates student totals in the preview table below in real time.
                        </span>
                    </div>
                </div>
            @else
                <div class="rounded-xl bg-amber-500/10 border border-amber-500/30 p-4 text-xs text-amber-300 space-y-2">
                    <div class="font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        No Fee Structure Configured for {{ $selectedYear }} {{ $selectedTerm }} {{ $selectedGrade ? "({$selectedGrade})" : "" }}
                    </div>
                    <p>
                        We could not find active fee structure entries for this selection. You can either <a href="{{ route('admin.fees.index') }}" class="underline font-semibold text-amber-200 hover:text-white">configure fee structures under Finance &gt; Fees</a>, or manually define custom fee items below for this batch.
                    </p>
                </div>
            @endif
        </div>

        {{-- Invoice Dates Card --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-800/80">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400 font-semibold text-xs">3</span>
                <h2 class="text-sm font-semibold text-slate-100">Invoice Dates & Scheduling</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1" for="issued_at">Issue Date</label>
                    <input type="date" id="issued_at" name="issued_at" value="{{ $issuedAt }}" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1" for="due_date">Payment Due Date</label>
                    <input type="date" id="due_date" name="due_date" value="{{ $dueDate }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Batch Overview KPI Stats Bar --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="text-[11px] font-medium text-slate-400">Total Matching Students</div>
                <div class="text-2xl font-bold text-slate-50 mt-1">{{ count($studentPreviews) }}</div>
                <div class="text-[11px] text-slate-400 mt-1">Filtered from active enrollments</div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="text-[11px] font-medium text-slate-400">
                    @if($selectedResidency === 'boarding')
                        Target: Boarders Only
                    @elseif($selectedResidency === 'day')
                        Target: Day Scholars Only
                    @else
                        Boarders vs Day Scholars
                    @endif
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-bold text-purple-400">{{ $totalBoarders }}</span>
                    <span class="text-xs text-slate-400">Boarder{{ $totalBoarders === 1 ? '' : 's' }}</span>
                    <span class="text-slate-600">/</span>
                    <span class="text-xl font-bold text-sky-400">{{ $totalDayScholars }}</span>
                    <span class="text-xs text-slate-400">Day</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    @if($selectedResidency === 'boarding')
                        Class Cohort: {{ $cohortBoarderCount ?? $totalBoarders }} Boarders • {{ $cohortDayCount ?? 0 }} Day ({{ $cohortTotalCount ?? $totalBoarders }} total)
                    @elseif($selectedResidency === 'day')
                        Class Cohort: {{ $cohortDayCount ?? $totalDayScholars }} Day • {{ $cohortBoarderCount ?? 0 }} Boarders ({{ $cohortTotalCount ?? $totalDayScholars }} total)
                    @else
                        Cohort total: {{ $cohortTotalCount ?? count($studentPreviews) }} enrolled students
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="text-[11px] font-medium text-slate-400">Ready to Invoice</div>
                <div class="text-2xl font-bold text-emerald-400 mt-1" id="kpi-ready-count">{{ $readyCount }}</div>
                <div class="text-[11px] text-slate-400 mt-1">
                    @if($alreadyInvoicedCount > 0)
                        <span class="text-amber-400">{{ $alreadyInvoicedCount }} already invoiced (unselected)</span>
                    @else
                        Zero duplicate invoices detected
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-indigo-500/30 bg-indigo-500/10 p-4">
                <div class="text-[11px] font-semibold text-indigo-300">Total Batch Invoice Value</div>
                <div class="text-2xl font-extrabold text-indigo-100 mt-1" id="kpi-total-amount">${{ number_format($totalBatchAmount, 2) }}</div>
                <div class="text-[11px] text-indigo-300/80 mt-1">Sum of selected student billing</div>
            </div>
        </div>

        {{-- 4. Interactive Live Preview Table --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-950/40">
                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-200 cursor-pointer">
                        <input type="checkbox" id="master-select-checkbox" checked
                               class="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500">
                        <span>Select All Ready Students</span>
                    </label>
                    <span class="text-slate-600">|</span>
                    <span class="text-xs text-slate-400">
                        <strong class="text-indigo-400" id="selected-students-count">{{ $readyCount }}</strong> of {{ count($studentPreviews) }} selected
                    </span>
                </div>

                <div class="text-xs text-slate-400 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>Values previewed below are exact amounts that will be charged</span>
                </div>
            </div>

            @if(count($studentPreviews) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th scope="col" class="py-3 px-4 w-10">Select</th>
                                <th scope="col" class="py-3 px-4">Student</th>
                                <th scope="col" class="py-3 px-4">Form & Class</th>
                                <th scope="col" class="py-3 px-4">Residency</th>
                                <th scope="col" class="py-3 px-4">Applied Fee Items</th>
                                <th scope="col" class="py-3 px-4 text-right">Total Invoice Value</th>
                                <th scope="col" class="py-3 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach($studentPreviews as $preview)
                                @php
                                    $student = $preview['student'];
                                    $enr = $preview['enrollment'];
                                    $isAlreadyInvoiced = (bool) $preview['existing_invoice'];
                                @endphp
                                <tr class="hover:bg-slate-800/30 transition {{ $isAlreadyInvoiced ? 'opacity-60 bg-slate-950/20' : '' }}"
                                    data-student-row
                                    data-is-boarder="{{ $preview['is_boarder'] ? '1' : '0' }}"
                                    data-has-transport="{{ $preview['has_transport'] ? '1' : '0' }}"
                                    data-grade="{{ strtolower($preview['grade'] === '—' ? '' : $preview['grade']) }}"
                                    data-already="{{ $isAlreadyInvoiced ? '1' : '0' }}">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" name="enrollment_ids[]" value="{{ $enr->id }}"
                                               data-amount="{{ $preview['total_amount'] }}"
                                               data-already="{{ $isAlreadyInvoiced ? '1' : '0' }}"
                                               class="student-checkbox rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500"
                                               {{ $isAlreadyInvoiced ? '' : 'checked' }}>
                                    </td>
                                    <td class="py-3 px-4">
                                        <a href="{{ route('admin.students.show', $student) }}" target="_blank" class="font-medium text-slate-100 hover:text-indigo-400 transition flex items-center gap-1.5">
                                            <span>{{ $student->first_name }} {{ $student->last_name }}</span>
                                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                        <div class="text-[11px] text-slate-400">
                                            Reg: {{ $student->admission_number ?? $student->national_id ?? "ID #{$student->id}" }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 font-medium text-slate-200">
                                            {{ $preview['grade'] }}
                                        </span>
                                        <span class="text-slate-400 text-[11px] block">
                                            Class: {{ $preview['class_name'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if($preview['is_boarder'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-500/20 px-2.5 py-0.5 text-[11px] font-semibold text-purple-300 border border-purple-500/30">
                                                <span class="h-1.5 w-1.5 rounded-full bg-purple-400"></span>
                                                Boarder
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-sky-500/20 px-2.5 py-0.5 text-[11px] font-semibold text-sky-300 border border-sky-500/30">
                                                <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                                                Day Scholar
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="student-applied-items flex flex-wrap gap-1 max-w-md">
                                            @forelse($preview['applied_items'] as $item)
                                                @php
                                                    $isBoardingLine = str_contains(strtolower($item['category'] ?? ''), 'boarding') || str_contains(strtolower($item['label'] ?? ''), 'boarding');
                                                @endphp
                                                <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] {{ $isBoardingLine ? 'bg-purple-950/70 border border-purple-800/80 text-purple-200 font-medium' : 'bg-slate-800 text-slate-300' }}">
                                                    <span>{{ $item['label'] }}</span>
                                                    @if($item['vote_code'])
                                                        <span class="font-mono text-emerald-300">[{{ $item['vote_code'] }}]</span>
                                                    @endif
                                                    <span class="text-slate-400 font-mono">${{ number_format($item['amount'], 2) }}</span>
                                                </span>
                                            @empty
                                                <span class="text-xs text-amber-400 italic">No fee structure items</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="student-total-amount text-sm font-bold font-mono text-emerald-400">
                                            ${{ number_format($preview['total_amount'], 2) }}
                                        </div>
                                        <div class="student-items-count text-[10px] text-slate-400">
                                            {{ count($preview['applied_items']) }} item(s)
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        @if($isAlreadyInvoiced)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-medium text-amber-300 border border-amber-500/30">
                                                Already Invoiced ({{ $preview['existing_invoice']->number }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-medium text-emerald-300 border border-emerald-500/30">
                                                Ready to Bill
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 px-4 text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <h3 class="text-sm font-semibold text-slate-200">No active students found for this filter</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        Try selecting <strong>All Forms</strong>, <strong>All Classes</strong>, or adjust the <strong>Student Residency</strong> filter to match active students in the system.
                    </p>
                </div>
            @endif
        </div>

        {{-- Optional custom fee items can be assigned to an official vote --}}
        <div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-100">Add Custom Fee Items for this Batch</h3>
                        <p class="text-xs text-slate-400 mt-1">Optional: mapped votes are retained for FIFO allocation and MOPSE receipts.</p>
                    </div>
                    <button type="button" id="add-custom-fee-item" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700">Add Item</button>
                </div>
                <div id="custom-fee-items" class="space-y-3"></div>
                <template id="custom-fee-item-template">
                    <div class="fee-row grid grid-cols-1 sm:grid-cols-5 gap-3">
                        <input type="text" name="custom_fee_items[__INDEX__][description]" placeholder="Description"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100">
                        <select name="custom_fee_items[__INDEX__][category]" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100">
                            <option value="tuition">Tuition</option>
                            <option value="boarding">Boarding</option>
                            <option value="levy">General Levy</option>
                            <option value="other">Other</option>
                        </select>
                        <select name="custom_fee_items[__INDEX__][vote_code]" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100">
                            <option value="NONE">No vote assignment</option>
                            @foreach($standardVotes as $voteCode => $vote)
                                <option value="{{ $voteCode }}">{{ $vote['name'] ?? $voteCode }} [{{ $voteCode }}]</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="0" name="custom_fee_items[__INDEX__][amount]" placeholder="Amount"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs text-slate-100">
                        <button type="button" class="remove-custom-fee-item rounded-lg border border-rose-900/70 bg-rose-950/30 px-3 py-2 text-xs text-rose-300">Remove</button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Sticky Bottom Action Controls --}}
        <div class="sticky bottom-4 rounded-2xl border border-slate-700/80 bg-slate-900/95 backdrop-blur-xl p-4 shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/20 text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-xs text-slate-400">Ready to Generate:</div>
                    <div class="text-sm font-bold text-slate-100">
                        <span id="footer-count">{{ $readyCount }}</span> Invoices • <span class="text-emerald-400 font-mono" id="footer-total">${{ number_format($totalBatchAmount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.invoices.index') }}" class="rounded-xl border border-slate-700 bg-slate-950/60 px-5 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 transition">
                    Cancel
                </a>
                <button type="submit" id="submit-btn" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-indigo-500 transition shadow-lg shadow-indigo-600/30 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>Generate Invoices Now</span>
                </button>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const masterCheckbox = document.getElementById('master-select-checkbox');
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            const feeCheckboxes = document.querySelectorAll('.fee-structure-checkbox');
            const countLabel = document.getElementById('selected-students-count');
            const footerCount = document.getElementById('footer-count');
            const footerTotal = document.getElementById('footer-total');
            const kpiTotal = document.getElementById('kpi-total-amount');
            const kpiReady = document.getElementById('kpi-ready-count');
            const submitBtn = document.getElementById('submit-btn');
            const customItemsContainer = document.getElementById('custom-fee-items');
            const customItemTemplate = document.getElementById('custom-fee-item-template');
            let customItemIndex = 0;

            function escapeHtml(str) {
                if (!str) return '';
                return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function updateMetrics() {
                let selectedCount = 0;
                let selectedTotal = 0.0;
                const hasFeeStructure = document.querySelectorAll('.fee-structure-checkbox:checked').length > 0;
                const hasCustomItems = Array.from(customItemsContainer?.querySelectorAll('.fee-row') || []).some(row => {
                    const description = row.querySelector('[name$="[description]"]')?.value.trim();
                    const amount = Number(row.querySelector('[name$="[amount]"]')?.value);
                    return Boolean(description) && Number.isFinite(amount) && amount >= 0;
                });

                studentCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        selectedCount++;
                        selectedTotal += parseFloat(cb.getAttribute('data-amount') || 0);
                    }
                });

                const formattedTotal = '$' + selectedTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                if (countLabel) countLabel.textContent = selectedCount;
                if (footerCount) footerCount.textContent = selectedCount;
                if (footerTotal) footerTotal.textContent = formattedTotal;
                if (kpiTotal) kpiTotal.textContent = formattedTotal;
                if (kpiReady) kpiReady.textContent = selectedCount;

                if (submitBtn) {
                    submitBtn.disabled = selectedCount === 0 || (!hasFeeStructure && !hasCustomItems);
                }
            }

            function recalculateFeePreviews() {
                const allFees = [];

                feeCheckboxes.forEach(cb => {
                    const card = cb.closest('.fee-card');
                    allFees.push({
                        id: cb.dataset.id,
                        label: cb.dataset.label,
                        amount: parseFloat(cb.dataset.amount) || 0,
                        category: cb.dataset.category || 'fees',
                        isBoarding: cb.dataset.isBoarding === '1',
                        isTransport: cb.dataset.isTransport === '1',
                        isOptional: cb.dataset.isOptional === '1',
                        parentId: cb.dataset.parentId || '',
                        residency: cb.dataset.residency || 'all',
                        voteCode: cb.dataset.voteCode || '',
                        grade: cb.dataset.grade || '',
                        selected: cb.checked,
                    });
                    if (cb.checked) {
                        if (card) {
                            card.classList.add('border-indigo-500/60', 'bg-indigo-500/10');
                            card.classList.remove('border-slate-800', 'bg-slate-950/50');
                        }
                    } else {
                        if (card) {
                            card.classList.remove('border-indigo-500/60', 'bg-indigo-500/10');
                            card.classList.add('border-slate-800', 'bg-slate-950/50');
                        }
                    }
                });

                const studentRows = document.querySelectorAll('tr[data-student-row]');
                studentRows.forEach(row => {
                    const isBoarder = row.dataset.isBoarder === '1';
                    const hasTransport = row.dataset.hasTransport === '1';
                    const studentGrade = (row.dataset.grade || '').trim().toLowerCase();
                    const studentCheckbox = row.querySelector('.student-checkbox');
                    const appliedItemsDiv = row.querySelector('.student-applied-items');
                    const totalAmountDiv = row.querySelector('.student-total-amount');
                    const itemsCountDiv = row.querySelector('.student-items-count');

                    let studentTotal = 0;
                    let appliedItemsHtml = '';
                    let appliedCount = 0;

                    const applicableFees = new Map();
                    const addFeeIfApplicable = fee => {
                        const normalizedFeeGrade = fee.grade.replace(/^form\s*/, '').replace(/^grade\s*/, '');
                        const normalizedStudentGrade = studentGrade.replace(/^form\s*/, '').replace(/^grade\s*/, '');
                        const gradeMatches = !fee.grade || (studentGrade && normalizedFeeGrade === normalizedStudentGrade);
                        const residencyMatches = fee.residency === 'all' || fee.residency === (isBoarder ? 'boarding' : 'day');
                        const includeOptional = document.querySelector('[name="include_optional"]')?.value === '1';
                        if (!gradeMatches || !residencyMatches || (fee.isOptional && !includeOptional)) return;
                        if (fee.isBoarding && !isBoarder) return;
                        if (fee.isTransport && !hasTransport) return;
                        applicableFees.set(fee.id, fee);
                    };

                    allFees.filter(fee => fee.selected).forEach(fee => {
                        const subVotes = allFees.filter(child => child.parentId === fee.id && child.voteCode);
                        if (subVotes.length) {
                            if (fee.residency === (isBoarder ? 'boarding' : 'day')) {
                                subVotes.forEach(addFeeIfApplicable);
                            }
                            return;
                        }
                        addFeeIfApplicable(fee);
                    });

                    const customItems = [];
                    Array.from(customItemsContainer?.querySelectorAll('.fee-row') || []).forEach(customRow => {
                        const description = customRow.querySelector('[name$="[description]"]')?.value.trim();
                        const amount = Number(customRow.querySelector('[name$="[amount]"]')?.value);
                        const category = customRow.querySelector('[name$="[category]"]')?.value || 'other';
                        const voteCode = customRow.querySelector('[name$="[vote_code]"]')?.value || 'NONE';
                        if (!description || !Number.isFinite(amount) || amount < 0) return;
                        const isBoarding = voteCode === 'BOARDING_FEES' || category === 'boarding' || /boarding/i.test(description);
                        if ((isBoarding && !isBoarder) || (voteCode === 'FOOD' && isBoarder)) return;
                        customItems.push({ label: description, amount, isBoarding, voteCode: voteCode === 'NONE' ? '' : voteCode });
                    });

                    [...applicableFees.values(), ...customItems].forEach(fee => {

                        studentTotal += fee.amount;
                        appliedCount++;

                        const badgeClass = fee.isBoarding
                            ? 'bg-purple-950/70 border border-purple-800/80 text-purple-200 font-medium'
                            : 'bg-slate-800 text-slate-300';

                        appliedItemsHtml += `<span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] ${badgeClass}">
                            <span>${escapeHtml(fee.label)}</span>
                            ${fee.voteCode ? `<span class="font-mono text-emerald-300">[${escapeHtml(fee.voteCode)}]</span>` : ''}
                            <span class="text-slate-400 font-mono">$${fee.amount.toFixed(2)}</span>
                        </span> `;
                    });

                    if (appliedCount === 0) {
                        appliedItemsHtml = '<span class="text-xs text-amber-400 italic">No fee structure items</span>';
                    }

                    if (appliedItemsDiv) appliedItemsDiv.innerHTML = appliedItemsHtml;
                    if (totalAmountDiv) totalAmountDiv.textContent = '$' + studentTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    if (itemsCountDiv) itemsCountDiv.textContent = `${appliedCount} item(s)`;
                    if (studentCheckbox) {
                        studentCheckbox.setAttribute('data-amount', studentTotal);
                    }
                });

                updateMetrics();
            }

            // Quick button listeners
            document.getElementById('btn-fee-select-all')?.addEventListener('click', function() {
                feeCheckboxes.forEach(cb => { cb.checked = true; });
                recalculateFeePreviews();
            });

            document.getElementById('btn-fee-clear-all')?.addEventListener('click', function() {
                feeCheckboxes.forEach(cb => { cb.checked = false; });
                recalculateFeePreviews();
            });

            document.getElementById('btn-fee-boarding-only')?.addEventListener('click', function() {
                feeCheckboxes.forEach(cb => {
                    const isPackage = feeCheckboxes.some(child => child.dataset.parentId === cb.dataset.id && child.dataset.voteCode);
                    cb.checked = cb.dataset.isBoarding === '1' && !isPackage;
                });
                recalculateFeePreviews();
            });

            document.getElementById('btn-fee-tuition-only')?.addEventListener('click', function() {
                feeCheckboxes.forEach(cb => {
                    const isPackage = feeCheckboxes.some(child => child.dataset.parentId === cb.dataset.id && child.dataset.voteCode);
                    cb.checked = cb.dataset.isBoarding !== '1' && !isPackage;
                });
                recalculateFeePreviews();
            });

            // Fee structure checkboxes change
            feeCheckboxes.forEach(cb => {
                cb.addEventListener('change', recalculateFeePreviews);
            });

            document.getElementById('add-custom-fee-item')?.addEventListener('click', function () {
                const row = customItemTemplate?.content.cloneNode(true);
                if (!row) return;
                row.querySelectorAll('[name]').forEach(input => {
                    input.name = input.name.replace('__INDEX__', String(customItemIndex));
                });
                customItemsContainer.appendChild(row);
                customItemIndex++;
                recalculateFeePreviews();
            });
            customItemsContainer?.addEventListener('input', recalculateFeePreviews);
            customItemsContainer?.addEventListener('change', recalculateFeePreviews);
            customItemsContainer?.addEventListener('click', event => {
                if (event.target.closest('.remove-custom-fee-item')) {
                    event.target.closest('.fee-row')?.remove();
                    recalculateFeePreviews();
                }
            });

            // Master select checkbox
            if (masterCheckbox) {
                masterCheckbox.addEventListener('change', function () {
                    studentCheckboxes.forEach(cb => {
                        if (cb.getAttribute('data-already') === '1' && masterCheckbox.checked) {
                            cb.checked = false;
                        } else {
                            cb.checked = masterCheckbox.checked;
                        }
                    });
                    updateMetrics();
                });
            }

            // Student checkboxes change
            studentCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateMetrics);
            });

            // Initial calculation
            recalculateFeePreviews();
        });
    </script>
@endsection
