@extends('layouts.app')

@section('content')
@php
    $singular = ['categories' => 'category', 'items' => 'item', 'warehouses' => 'warehouse'][$kind];
    $title = ($record ? 'Edit ' : 'Add ') . ucfirst($singular);
    $routeName = 'admin.inventory.' . $kind . '.' . ($record ? 'update' : 'store');
    $formAction = $record ? route($routeName, $record) : route($routeName);
@endphp
<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <a href="{{ route('admin.inventory.' . $kind . '.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Back to {{ $kind }}</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">{{ $title }}</h1>
    </div>

    <form method="POST" action="{{ $formAction }}" class="space-y-5 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        @csrf
        @if($record)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="code" class="mb-1 block text-xs font-medium text-slate-300">Code</label>
                <input id="code" name="code" value="{{ old('code', $record?->code) }}" required maxlength="50" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('code')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="name" class="mb-1 block text-xs font-medium text-slate-300">Name</label>
                <input id="name" name="name" value="{{ old('name', $record?->name) }}" required maxlength="255" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('name')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>

            @if($kind === 'items')
                <div>
                    <label for="inventory_category_id" class="mb-1 block text-xs font-medium text-slate-300">Category</label>
                    <select id="inventory_category_id" name="inventory_category_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('inventory_category_id', $record?->inventory_category_id) == $category->id)>{{ $category->name }} ({{ $category->code }})</option>
                        @endforeach
                    </select>
                    @error('inventory_category_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="item_type" class="mb-1 block text-xs font-medium text-slate-300">Item type</label>
                    <select id="item_type" name="item_type" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        @foreach(['consumable' => 'Consumable stock', 'asset' => 'Asset', 'non_stock' => 'Non-stock purchase'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('item_type', $record?->item_type ?? 'consumable') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('item_type')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="unit_of_measure" class="mb-1 block text-xs font-medium text-slate-300">Base unit of measure</label>
                    <input id="unit_of_measure" name="unit_of_measure" value="{{ old('unit_of_measure', $record?->unit_of_measure) }}" required maxlength="30" placeholder="e.g. bag, kg, each" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    @error('unit_of_measure')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
            @elseif($kind === 'warehouses')
                <div>
                    <label for="location" class="mb-1 block text-xs font-medium text-slate-300">Location</label>
                    <input id="location" name="location" value="{{ old('location', $record?->location) }}" maxlength="255" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    @error('location')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="manager_user_id" class="mb-1 block text-xs font-medium text-slate-300">Warehouse custodian</label>
                    <select id="manager_user_id" name="manager_user_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('manager_user_id', $record?->manager_user_id) == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('manager_user_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
            @endif

            @if($kind === 'categories' && auth()->user()->can('accounts.edit'))
                <div class="sm:col-span-2">
                    <label for="issue_expense_account_id" class="mb-1 block text-xs font-medium text-slate-300">Default stock-issue expense account</label>
                    <select id="issue_expense_account_id" name="issue_expense_account_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Default: Supplies (6500)</option>
                        @foreach($expenseAccounts as $account)
                            <option value="{{ $account->id }}" @selected(old('issue_expense_account_id', $record?->issue_expense_account_id) == $account->id)>{{ $account->code }} · {{ $account->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Finance can assign category issues to the appropriate expense ledger.</p>
                    @error('issue_expense_account_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="asset_account_id" class="mb-1 block text-xs font-medium text-slate-300">Fixed-asset account</label>
                    <select id="asset_account_id" name="asset_account_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Not mapped</option>
                        @foreach($assetAccounts as $account)
                            <option value="{{ $account->id }}" @selected(old('asset_account_id', $record?->asset_account_id) == $account->id)>{{ $account->code }} · {{ $account->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Required before capitalized items in this category can be received.</p>
                    @error('asset_account_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
            @endif

            @if($kind !== 'warehouses')
                <div class="{{ $kind === 'categories' ? 'sm:col-span-2' : '' }}">
                    <label for="description" class="mb-1 block text-xs font-medium text-slate-300">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('description', $record?->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
            @endif

            <div>
                <label for="is_active" class="mb-1 block text-xs font-medium text-slate-300">Status</label>
                <select id="is_active" name="is_active" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="1" @selected((string) old('is_active', $record?->is_active ?? 1) === '1')>Active</option>
                    <option value="0" @selected((string) old('is_active', $record?->is_active ?? 1) === '0')>Inactive</option>
                </select>
                @error('is_active')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
            <a href="{{ route('admin.inventory.' . $kind . '.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Cancel</a>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">{{ $record ? 'Save changes' : 'Create' }}</button>
        </div>
    </form>
</div>
@endsection
