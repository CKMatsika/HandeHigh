@extends('layouts.app')

@section('content')
    <!-- Header with Sync and Print Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-bold text-slate-50">Fee Structure & Vote Allocations</h1>
            <p class="text-xs text-slate-400 mt-1">Configure parent packages (Boarding $470 / Day $135) and sub-vote accounts for FIFO receipt allocation.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <form method="POST" action="{{ route('admin.fees.sync-hande') }}" onsubmit="return confirm('Sync official Hande High 2026 fee votes schedule (Boarding $470 and Day $135 with 7 sub-accounts each)?');" class="inline">
                @csrf
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                <input type="hidden" name="term" value="{{ $term ?: 'Term 3' }}">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500 shadow-md shadow-emerald-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Sync Hande High 2026 Schedule
                </button>
            </form>

            <a href="{{ route('admin.fees.print', ['academic_year' => $academicYear, 'term' => $term]) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Official Print Schedule
            </a>
        </div>
    </div>

    <!-- Status Alerts -->
    @if(session('status'))
        <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100">
            <ul class="list-disc ml-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.fees.index') }}" class="mb-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs text-slate-100 bg-slate-900/60 p-3.5 rounded-2xl border border-slate-800">
        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-400" for="academic_year">Academic Year</label>
            <input id="academic_year" type="text" name="academic_year" value="{{ $academicYear }}" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs focus:border-indigo-500">
        </div>
        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-400" for="term">Term</label>
            <select id="term" name="term" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs focus:border-indigo-500">
                <option value="">All Terms</option>
                @foreach($terms as $t)
                    <option value="{{ $t }}" {{ $term === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2 flex items-end gap-2">
            <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-500 transition shadow-md">
                Filter Tariffs
            </button>
            <a href="{{ route('admin.fees.index') }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition">
                Reset
            </a>
        </div>
    </form>

    <!-- Add Fee Item Card with Vote & Parent Package Mapping -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-5 py-5 mb-6 shadow-xl">
        <h2 class="text-sm font-semibold text-slate-100 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
            Add Fee Tariff or Sub-Vote Account
        </h2>
        
        <form method="POST" action="{{ route('admin.fees.store') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs text-slate-100">
            @csrf
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_academic_year">Academic Year</label>
                <input id="new_academic_year" type="text" name="academic_year" value="{{ old('academic_year', $academicYear) }}" required class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_term">Term</label>
                <select id="new_term" name="term" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs" required>
                    @foreach($terms as $t)
                        <option value="{{ $t }}" {{ old('term', $term ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_grade">Grade / Form</label>
                <input id="new_grade" type="text" name="grade" list="fee-grade-options" value="{{ old('grade') }}" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs" placeholder="Blank = All Grades">
                <datalist id="fee-grade-options">
                    @foreach($classGradeOptions as $gradeOption)
                        <option value="{{ $gradeOption }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-[10px] text-slate-500">Use a registered grade/form, or an exact class name to scope this fee to that stream.</p>
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_category">Category</label>
                <select id="new_category" name="category" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs" required>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Parent Package & Vote Code Mapping -->
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_parent_id">Parent Package (Optional)</label>
                <select id="new_parent_id" name="parent_id" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs">
                    <option value="">-- None (Main / Bundled Fee) --</option>
                    @foreach($parentFees as $parentFee)
                        <option value="{{ $parentFee->id }}" {{ old('parent_id') == $parentFee->id ? 'selected' : '' }}>
                            {{ $parentFee->label }} (${{ number_format($parentFee->amount, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_vote_code">MOPSE Vote Account</label>
                <select id="new_vote_code" name="vote_code" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs">
                    <option value="">-- General / Unclassified --</option>
                    @foreach($standardVotes as $code => $v)
                        <option value="{{ $code }}" {{ old('vote_code') === $code ? 'selected' : '' }}>
                            {{ $code }} - {{ $v['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_residency_type">Student Residency</label>
                <select id="new_residency_type" name="residency_type" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs">
                    <option value="all" {{ old('residency_type') === 'all' ? 'selected' : '' }}>All Students</option>
                    <option value="boarding" {{ old('residency_type') === 'boarding' ? 'selected' : '' }}>Boarding Only</option>
                    <option value="day" {{ old('residency_type') === 'day' ? 'selected' : '' }}>Day-Scholars Only</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_allocation_priority">FIFO Priority (1 = High)</label>
                <input id="new_allocation_priority" type="number" min="1" max="99" name="allocation_priority" value="{{ old('allocation_priority', 10) }}" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_code">Fee Code</label>
                <input id="new_code" type="text" name="code" value="{{ old('code') }}" required class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs font-mono" placeholder="e.g. B-TUI-2026">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_label">Label / Description</label>
                <input id="new_label" type="text" name="label" value="{{ old('label') }}" required class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs" placeholder="e.g. Tuition">
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_amount">Amount ($)</label>
                <input id="new_amount" type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}" required class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs font-mono">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_revenue_account_id">GL Revenue Account (Chart of Accounts)</label>
                <select id="new_revenue_account_id" name="revenue_account_id" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs">
                    <option value="">-- Auto-Map by Category --</option>
                    @foreach($revenueAccounts ?? [] as $revAcc)
                        @if($revAcc->type === 'revenue' || str_starts_with($revAcc->code, '5'))
                            <option value="{{ $revAcc->id }}" {{ old('revenue_account_id') == $revAcc->id ? 'selected' : '' }}>
                                {{ $revAcc->code }} - {{ $revAcc->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_project_id">Project (P&L Tag)</label>
                <select id="new_project_id" name="project_id" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs">
                    <option value="">-- None / General --</option>
                    @foreach($projects ?? [] as $proj)
                        <option value="{{ $proj->id }}" {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                            {{ $proj->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-400" for="new_cost_center_id">Cost Center / Dept</label>
                <select id="new_cost_center_id" name="cost_center_id" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-xs">
                    <option value="">-- None / General --</option>
                    @foreach($costCenters ?? [] as $cc)
                        <option value="{{ $cc->id }}" {{ old('cost_center_id') == $cc->id ? 'selected' : '' }}>
                            {{ $cc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3 flex items-center gap-4 mt-2">
                <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                    <input type="checkbox" name="is_optional" value="1" {{ old('is_optional') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-500">
                    <span>Optional Fee Item</span>
                </label>
            </div>

            <div class="flex justify-end mt-2">
                <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-500 transition shadow-md">
                    Add Fee Item
                </button>
            </div>
        </form>
    </div>

    <!-- Existing Fee Items Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-5 py-5 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-100 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                Active Fee Tariffs & Vote Breakdown Hierarchy
            </h2>
            <span class="text-xs text-slate-400">{{ $feeStructures->total() }} fee items defined</span>
        </div>

        @if($feeStructures->count())
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-slate-100">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="text-left py-2.5 font-semibold">Year / Term</th>
                            <th class="text-left py-2.5 font-semibold">Residency / Grade</th>
                            <th class="text-left py-2.5 font-semibold">Hierarchy / Vote Code</th>
                            <th class="text-left py-2.5 font-semibold">Tariff Label & Breakdown</th>
                            <th class="text-right py-2.5 font-semibold">Amount ($)</th>
                            <th class="text-center py-2.5 font-semibold">Priority</th>
                            <th class="text-left py-2.5 font-semibold">Revenue Account</th>
                            <th class="text-left py-2.5 font-semibold">Cost Center</th>
                            <th class="text-right py-2.5 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        @foreach($feeStructures as $fee)
                            @php
                                $isParent = ($fee->parent_id === null && $fee->subVotes->count() > 0);
                                $isChild = ($fee->parent_id !== null);
                            @endphp
                            <tr class="hover:bg-slate-800/30 transition {{ $isParent ? 'bg-indigo-950/20' : ($isChild ? 'bg-slate-950/40' : '') }}">
                                <td class="py-3 align-top whitespace-nowrap">
                                    <div class="font-bold text-slate-200">{{ $fee->academic_year }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $fee->term }}</div>
                                </td>
                                <td class="py-3 align-top whitespace-nowrap">
                                    @if($fee->residency_type === 'boarding')
                                        <span class="px-2 py-0.5 rounded bg-indigo-950/70 border border-indigo-700/60 text-indigo-300 font-semibold text-[10px]">Boarding</span>
                                    @elseif($fee->residency_type === 'day')
                                        <span class="px-2 py-0.5 rounded bg-teal-950/70 border border-teal-700/60 text-teal-300 font-semibold text-[10px]">Day Scholar</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 text-[10px]">All Students</span>
                                    @endif
                                    <div class="text-[10px] text-slate-500 mt-1">{{ $fee->grade ?: 'All Grades' }}</div>
                                </td>
                                <td class="py-3 align-top whitespace-nowrap">
                                    <div class="font-mono font-semibold {{ $isParent ? 'text-indigo-300' : 'text-slate-300' }}">
                                        {{ $fee->code }}
                                    </div>
                                    @if($fee->vote_code)
                                        <span class="inline-block mt-0.5 text-[9px] px-1.5 py-0.5 rounded bg-slate-800/80 text-amber-300 font-mono border border-amber-600/30">
                                            {{ $fee->vote_code }}
                                        </span>
                                    @endif
                                    @if($isChild)
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <span>↳</span> Sub-account
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 align-top">
                                    <div class="font-bold {{ $isParent ? 'text-indigo-200 text-sm' : 'text-slate-100' }}">
                                        {{ $fee->label }}
                                    </div>

                                    <!-- If Parent Package, display its sub-votes breakdown badges -->
                                    @if($isParent)
                                        <div class="mt-2 p-2 rounded-lg bg-slate-950/80 border border-indigo-900/40 space-y-1">
                                            <div class="text-[10px] font-semibold uppercase text-indigo-400 tracking-wider">
                                                Allocated Sub-Votes ({{ $fee->subVotes->count() }}):
                                            </div>
                                            <div class="flex flex-wrap gap-1.5 pt-1">
                                                @foreach($fee->subVotes as $sv)
                                                    <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded bg-slate-900 border border-slate-700 text-slate-300 font-mono">
                                                        <span class="text-indigo-400 font-semibold">{{ $sv->vote_code ?? $sv->label }}:</span>
                                                        <span class="font-bold text-emerald-400">${{ number_format($sv->amount, 2) }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 align-top text-right whitespace-nowrap">
                                    <span class="font-bold font-mono text-emerald-400 {{ $isParent ? 'text-base' : 'text-xs' }}">
                                        ${{ number_format($fee->amount, 2) }}
                                    </span>
                                </td>
                                <td class="py-3 align-top text-center font-mono text-slate-400">
                                    {{ $fee->allocation_priority ?? '—' }}
                                </td>
                                <td class="py-3 align-top whitespace-nowrap">
                                    @if($fee->revenueAccount)
                                        <span class="inline-flex items-center rounded-md bg-blue-950/60 border border-blue-700/50 px-2 py-0.5 text-[10px] text-blue-300 font-mono">
                                            {{ $fee->revenueAccount->code }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 text-[10px]">Auto ({{ ucfirst($fee->category) }})</span>
                                    @endif
                                </td>
                                <td class="py-3 align-top whitespace-nowrap">
                                    @if($fee->costCenter)
                                        <span class="text-slate-300 text-[11px]">{{ $fee->costCenter->name }}</span>
                                    @elseif($fee->project)
                                        <span class="text-indigo-300 text-[11px]">{{ $fee->project->name }}</span>
                                    @else
                                        <span class="text-slate-500 text-[11px]">—</span>
                                    @endif
                                </td>
                                <td class="py-3 align-top text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.fees.show', $fee) }}" class="rounded-lg bg-slate-800 px-2.5 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-700 transition">
                                            View
                                        </a>
                                        <form method="POST" action="{{ route('admin.fees.destroy', $fee) }}" onsubmit="return confirm('Delete this fee item?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30 px-2 py-1 text-[11px] font-medium hover:bg-rose-500/30 transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-800">
                {{ $feeStructures->links() }}
            </div>
        @else
            <div class="py-10 text-center text-slate-400 text-xs">
                No fee items defined yet. Click "Sync Hande High 2026 Schedule" to auto-populate the official Boarding ($470) and Day-Scholar ($135) vote hierarchy.
            </div>
        @endif
    </div>
@endsection
