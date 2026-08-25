@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Account: {{ $account->code }} — {{ $account->name }}</h1>
            <p class="text-xs text-slate-400 mt-1">Modify account classification, parent hierarchy, postable status, and details.</p>
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
        <form method="POST" action="{{ route('admin.accounts.update', $account) }}" class="space-y-5 text-xs text-slate-300">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Account Code <span class="text-rose-400">*</span></label>
                    <input name="code" value="{{ old('code', $account->code) }}" class="w-full font-mono rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" required />
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Account Name <span class="text-rose-400">*</span></label>
                    <input name="name" value="{{ old('name', $account->name) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Account Type <span class="text-rose-400">*</span></label>
                    <select name="type" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" required>
                        @foreach(['asset' => 'Asset (1000–1999)', 'liability' => 'Liability (2000–3999)', 'equity' => 'Equity (4000–4999)', 'revenue' => 'Revenue (5000–5999)', 'expense' => 'Expense (6000–6999)'] as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" @selected(old('type', $account->type) === $typeKey)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Category <span class="text-rose-400">*</span></label>
                    <input name="category" value="{{ old('category', $account->category) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" required />
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Parent / Header Account</label>
                    <select name="parent_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">None (Top-Level Root Class)</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id', $account->parent_id) == $parent->id)>
                                {{ $parent->code }} — {{ $parent->name }} ({{ ucfirst($parent->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3 flex items-start gap-3">
                    <input type="checkbox" name="is_postable" id="is_postable" value="1" @checked(old('is_postable', $account->is_postable)) class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500 mt-1" />
                    <div>
                        <label for="is_postable" class="text-slate-200 font-medium cursor-pointer">Postable Account</label>
                        <p class="text-[11px] text-slate-500">If unchecked, this account acts as a summary/header grouping and will reject direct journal entry postings.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3 flex items-start gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $account->is_active)) class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500 mt-1" />
                    <div>
                        <label for="is_active" class="text-slate-200 font-medium cursor-pointer">Active Account</label>
                        <p class="text-[11px] text-slate-500">Deactivated accounts preserve full historical transaction integrity but cannot be selected for new transactions.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Current Ledger Balance</label>
                    <div class="w-full font-mono rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-slate-300">
                        ${{ $account->formatted_balance }} (Tree Rollup: ${{ $account->formatted_tree_balance }})
                    </div>
                </div>
                <div>
                    <label class="block text-slate-400 font-medium mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $account->sort_order) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
            </div>

            <div>
                <label class="block text-slate-400 font-medium mb-1">Description / Notes</label>
                <textarea name="description" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500" rows="3">{{ old('description', $account->description) }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-800">
                <div>
                    @if ($account->canBeDeleted())
                        <span class="text-[11px] text-slate-500">This account has no transactions or child accounts.</span>
                    @else
                        <span class="text-[11px] text-amber-400/80">Protected: Contains {{ $account->journalEntries()->count() }} journal entries / {{ $account->children()->count() }} subaccounts.</span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.accounts.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-300 hover:text-white transition">Cancel</a>
                    <button type="submit" class="rounded-full bg-indigo-500 px-5 py-2 text-xs font-medium text-white hover:bg-indigo-600 shadow-md shadow-indigo-500/20 transition">Update Account</button>
                </div>
            </div>
        </form>
    </div>
@endsection
