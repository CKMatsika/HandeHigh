@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Fee structure</h1>
            <p class="text-xs text-slate-400 mt-1">Configure tuition, levies, boarding, transport, and other charges that drive auto-invoicing.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.fees.print', ['academic_year' => $academicYear, 'term' => $term]) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                Print
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.fees.index') }}" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-xs text-slate-100">
        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="academic_year">Academic year</label>
            <input id="academic_year" type="text" name="academic_year" value="{{ $academicYear }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
        </div>
        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="term">Term</label>
            <select id="term" name="term" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                <option value="">All</option>
                @foreach($terms as $t)
                    <option value="{{ $t }}" {{ $term === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition mt-4 md:mt-0">
                Filter
            </button>
            <a href="{{ route('admin.fees.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition mt-4 md:mt-0">
                Reset
            </a>
        </div>
    </form>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100">
            <ul class="list-disc ml-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-6">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Add fee item</h2>
        <form method="POST" action="{{ route('admin.fees.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs text-slate-100">
            @csrf
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_academic_year">Academic year</label>
                <input id="new_academic_year" type="text" name="academic_year" value="{{ old('academic_year', $academicYear) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_term">Term</label>
                <select id="new_term" name="term" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" required>
                    @foreach($terms as $t)
                        <option value="{{ $t }}" {{ old('term', $term ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_grade">Grade</label>
                <input id="new_grade" type="text" name="grade" value="{{ old('grade') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="Blank = all grades">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_category">Category</label>
                <select id="new_category" name="category" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" required>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_code">Code</label>
                <input id="new_code" type="text" name="code" value="{{ old('code') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. TUITION_G7">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_label">Label</label>
                <input id="new_label" type="text" name="label" value="{{ old('label') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Grade 7 Term 1 Tuition">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_amount">Amount</label>
                <input id="new_amount" type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_subject_name">Subject name (optional)</label>
                <input id="new_subject_name" type="text" name="subject_name" value="{{ old('subject_name') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_service_type">Service type (optional)</label>
                <select id="new_service_type" name="service_type" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    <option value="">None</option>
                    <option value="boarding" {{ old('service_type') === 'boarding' ? 'selected' : '' }}>Boarding</option>
                    <option value="transport" {{ old('service_type') === 'transport' ? 'selected' : '' }}>Transport</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="new_revenue_account_id">Revenue Account (Chart of Accounts)</label>
                <select id="new_revenue_account_id" name="revenue_account_id" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    <option value="">-- Auto-Map by Category / Default --</option>
                    @foreach($revenueAccounts ?? [] as $revAcc)
                        @if($revAcc->type === 'revenue' || str_starts_with($revAcc->code, '5'))
                            <option value="{{ $revAcc->id }}" {{ old('revenue_account_id') == $revAcc->id ? 'selected' : '' }}>
                                {{ $revAcc->code }} - {{ $revAcc->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="flex items-center mt-5">
                <label class="inline-flex items-center gap-2 text-[11px] text-slate-300">
                    <input type="checkbox" name="is_optional" value="1" {{ old('is_optional') ? 'checked' : '' }} class="h-3 w-3 rounded border-slate-600 bg-slate-900 text-indigo-500">
                    <span>Optional fee</span>
                </label>
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                    Add fee item
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Existing fee items</h2>
        @if($feeStructures->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Year / Term</th>
                        <th class="text-left py-2 font-medium">Grade</th>
                        <th class="text-left py-2 font-medium">Category</th>
                        <th class="text-left py-2 font-medium">Code</th>
                        <th class="text-left py-2 font-medium">Label</th>
                        <th class="text-left py-2 font-medium">Amount</th>
                        <th class="text-left py-2 font-medium">Revenue Account</th>
                        <th class="text-left py-2 font-medium">Service</th>
                        <th class="text-left py-2 font-medium">Optional</th>
                        <th class="text-left py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($feeStructures as $fee)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">
                                <div>{{ $fee->academic_year }}</div>
                                <div class="text-[11px] text-slate-400">{{ $fee->term }}</div>
                            </td>
                            <td class="py-2 align-middle">{{ $fee->grade ?: 'All' }}</td>
                            <td class="py-2 align-middle capitalize">{{ $fee->category }}</td>
                            <td class="py-2 align-middle">{{ $fee->code }}</td>
                            <td class="py-2 align-middle">{{ $fee->label }}</td>
                            <td class="py-2 align-middle font-semibold text-emerald-400">${{ number_format($fee->amount, 2) }}</td>
                            <td class="py-2 align-middle">
                                @if($fee->revenueAccount)
                                    <span class="inline-flex items-center rounded-md bg-blue-950/60 border border-blue-700/50 px-2 py-0.5 text-[11px] text-blue-300 font-mono">
                                        {{ $fee->revenueAccount->code }} - {{ $fee->revenueAccount->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-slate-800/60 border border-slate-700 px-2 py-0.5 text-[11px] text-slate-400 font-mono">
                                        Auto ({{ ucfirst($fee->category) }})
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                @if($fee->service_type)
                                    <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-950/70 px-2 py-0.5 text-[11px]">{{ ucfirst($fee->service_type) }}</span>
                                @else
                                    <span class="text-[11px] text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                @if($fee->is_optional)
                                    <span class="inline-flex items-center rounded-full border border-amber-500/60 bg-amber-500/10 px-2 py-0.5 text-[11px] text-amber-200">Optional</span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-emerald-500/60 bg-emerald-500/10 px-2 py-0.5 text-[11px] text-emerald-200">Core</span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.fees.show', $fee) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">View</a>
                                    <form method="POST" action="{{ route('admin.fees.update', $fee) }}" class="flex flex-wrap items-center gap-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="academic_year" value="{{ $fee->academic_year }}">
                                        <input type="hidden" name="term" value="{{ $fee->term }}">
                                        <input type="hidden" name="grade" value="{{ $fee->grade }}">
                                        <input type="hidden" name="category" value="{{ $fee->category }}">
                                        <input type="text" name="code" value="{{ $fee->code }}" class="w-24 rounded border border-slate-700 bg-slate-950/70 px-2 py-1 text-[11px]">
                                        <input type="text" name="label" value="{{ $fee->label }}" class="w-36 rounded border border-slate-700 bg-slate-950/70 px-2 py-1 text-[11px]">
                                        <input type="number" step="0.01" min="0" name="amount" value="{{ $fee->amount }}" class="w-20 rounded border border-slate-700 bg-slate-950/70 px-2 py-1 text-[11px]">
                                        <select name="revenue_account_id" class="w-32 rounded border border-slate-700 bg-slate-950/70 px-1 py-1 text-[11px]">
                                            <option value="">Auto GL</option>
                                            @foreach($revenueAccounts ?? [] as $revAcc)
                                                @if($revAcc->type === 'revenue' || str_starts_with($revAcc->code, '5'))
                                                    <option value="{{ $revAcc->id }}" {{ $fee->revenue_account_id == $revAcc->id ? 'selected' : '' }}>
                                                        {{ $revAcc->code }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="subject_name" value="{{ $fee->subject_name }}">
                                        <input type="hidden" name="service_type" value="{{ $fee->service_type }}">
                                        <label class="inline-flex items-center gap-1 text-[11px] text-slate-300 ml-1">
                                            <input type="checkbox" name="is_optional" value="1" {{ $fee->is_optional ? 'checked' : '' }} class="h-3 w-3 rounded border-slate-600 bg-slate-900 text-indigo-500">
                                            <span>Opt</span>
                                        </label>
                                        <button type="submit" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition ml-1">Save</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.fees.destroy', $fee) }}" onsubmit="return confirm('Delete this fee item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-full bg-rose-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-rose-600 transition">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $feeStructures->links() }}
            </div>
        @else
            <p class="text-xs text-slate-400">No fee items defined yet. Start by adding tuition and any boarding or transport charges.</p>
        @endif
    </div>
@endsection
