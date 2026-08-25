@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Add Account / Subaccount</h1>
            <p class="text-xs text-slate-400 mt-1">Create a new ledger account or hierarchical subaccount in the Chart of Accounts.</p>
        </div>
        <a href="{{ route('admin.accounts.index') }}" class="text-xs text-slate-300 hover:text-white transition">← Back to Accounts</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs text-rose-300">
            <p class="font-semibold mb-1">Please correct the following errors:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.accounts.store') }}" class="space-y-5 text-xs text-slate-300">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Account Code <span class="text-rose-400">*</span></label>
                    <input name="code" value="{{ old('code') }}" placeholder="e.g. 5101, 1103" class="w-full font-mono rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500" required />
                    <p class="text-[10px] text-slate-500 mt-1">Assets (1000s), Liabilities (2000-3000s), Equity (4000s), Revenue (5000s), Expenses (6000s).</p>
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Account Name <span class="text-rose-400">*</span></label>
                    <input name="name" value="{{ old('name') }}" placeholder="e.g. Form 1 Tuition Revenue" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Account Type <span class="text-rose-400">*</span></label>
                    <select name="type" id="accountTypeSelect" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" required>
                        @foreach(['asset' => 'Asset (1000–1999)', 'liability' => 'Liability (2000–3999)', 'equity' => 'Equity (4000–4999)', 'revenue' => 'Revenue (5000–5999)', 'expense' => 'Expense (6000–6999)'] as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" @selected(old('type', $selectedParent?->type) === $typeKey)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Category <span class="text-rose-400">*</span></label>
                    <input name="category" value="{{ old('category', $selectedParent?->category ?? 'tuition_revenue') }}" placeholder="e.g. current_asset, tuition_revenue" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500" required />
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Parent / Header Account</label>
                    <select name="parent_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">None (Top-Level Root Class)</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id', $selectedParent?->id) == $parent->id)>
                                {{ $parent->code }} — {{ $parent->name }} ({{ ucfirst($parent->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3 flex items-start gap-3">
                    <input type="checkbox" name="is_postable" id="is_postable" value="1" @checked(old('is_postable', true)) class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500 mt-1" />
                    <div>
                        <label for="is_postable" class="text-slate-200 font-medium cursor-pointer">Postable Account</label>
                        <p class="text-[11px] text-slate-500">Check this for detail/subaccounts that receive journal entries. Uncheck for summary/header classification headers (e.g. 5000 REVENUE).</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3 flex items-start gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500 mt-1" />
                    <div>
                        <label for="is_active" class="text-slate-200 font-medium cursor-pointer">Active Account</label>
                        <p class="text-[11px] text-slate-500">Active accounts are selectable in vouchers, fee structures, kiosk products, and commercial projects.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Opening Balance</label>
                    <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', '0.00') }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
            </div>

            <div>
                <label class="block text-slate-400 font-medium mb-1">Description / Notes</label>
                <textarea name="description" placeholder="Optional notes regarding the purpose or usage of this ledger account..." class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                <a href="{{ route('admin.accounts.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-300 hover:text-white transition">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-5 py-2 text-xs font-medium text-white hover:bg-indigo-600 shadow-md shadow-indigo-500/20 transition">Create Account</button>
            </div>
        </form>
    </div>
@endsection
