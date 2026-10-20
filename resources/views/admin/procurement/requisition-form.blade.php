@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ route('admin.procurement.index') }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Procurement</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">New requisition</h1>
        <p class="mt-1 text-sm text-slate-400">Save a draft, review it, then submit it for approval.</p>
    </div>

    <form method="POST" action="{{ route('admin.procurement.requisitions.store') }}" class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        @csrf
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="department_id" class="mb-1 block text-xs font-medium text-slate-300">Requesting department</label>
                <select id="department_id" name="department_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">Select department (optional)</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="delivery_warehouse_id" class="mb-1 block text-xs font-medium text-slate-300">Requested delivery warehouse</label>
                <select id="delivery_warehouse_id" name="delivery_warehouse_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">Stores to assign</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('delivery_warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
                @error('delivery_warehouse_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="needed_by" class="mb-1 block text-xs font-medium text-slate-300">Needed by</label>
                <input type="date" id="needed_by" name="needed_by" value="{{ old('needed_by') }}" min="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('needed_by')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="purpose" class="mb-1 block text-xs font-medium text-slate-300">Purpose / justification</label>
            <textarea id="purpose" name="purpose" rows="3" required maxlength="2000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('purpose') }}</textarea>
            @error('purpose')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-100">Requested items</h2>
                <button type="button" id="add-item" class="rounded-lg border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:bg-slate-800">Add another item</button>
            </div>
            @error('items')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
            @error('items.*.inventory_item_id')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
            @error('items.*.quantity')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
            <div id="items" class="space-y-3">
                @foreach(old('items', [['inventory_item_id' => '', 'quantity' => '', 'notes' => '']]) as $index => $line)
                    <div class="item-row grid grid-cols-1 gap-3 rounded-xl border border-slate-800 p-4 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <label class="mb-1 block text-xs text-slate-400">Catalogue item</label>
                            <select name="items[{{ $index }}][inventory_item_id]" class="item-select w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                <option value="">Select item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" @selected(($line['inventory_item_id'] ?? '') == $item->id)>{{ $item->name }} · {{ $item->code }} ({{ $item->unit_of_measure }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs text-slate-400">Quantity</label>
                            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}" min="0.0001" step="0.0001" class="item-quantity w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        </div>
                        <div class="md:col-span-3">
                            <label class="mb-1 block text-xs text-slate-400">Line note</label>
                            <input name="items[{{ $index }}][notes]" value="{{ $line['notes'] ?? '' }}" maxlength="1000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        </div>
                        <div class="flex items-end md:col-span-1">
                            <button type="button" class="remove-item w-full rounded-lg border border-slate-700 px-3 py-2 text-xs text-rose-300 hover:bg-slate-800">Remove</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
            <a href="{{ route('admin.procurement.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save draft</button>
        </div>
    </form>
</div>

<template id="item-template">
    <div class="item-row grid grid-cols-1 gap-3 rounded-xl border border-slate-800 p-4 md:grid-cols-12">
        <div class="md:col-span-6">
            <label class="mb-1 block text-xs text-slate-400">Catalogue item</label>
            <select class="item-select w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                <option value="">Select item</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}">{{ $item->name }} · {{ $item->code }} ({{ $item->unit_of_measure }})</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-xs text-slate-400">Quantity</label>
            <input type="number" min="0.0001" step="0.0001" class="item-quantity w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-xs text-slate-400">Line note</label>
            <input maxlength="1000" class="item-notes w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
        </div>
        <div class="flex items-end md:col-span-1">
            <button type="button" class="remove-item w-full rounded-lg border border-slate-700 px-3 py-2 text-xs text-rose-300 hover:bg-slate-800">Remove</button>
        </div>
    </div>
</template>
<script>
    (() => {
        const container = document.getElementById('items');
        const template = document.getElementById('item-template');
        const addButton = document.getElementById('add-item');
        let nextIndex = {{ count(old('items', [['inventory_item_id' => '', 'quantity' => '', 'notes' => '']])) }};

        const updateRows = () => {
            const rows = [...container.querySelectorAll('.item-row')];
            rows.forEach((row, index) => {
                row.querySelector('.item-select').name = `items[${index}][inventory_item_id]`;
                row.querySelector('.item-quantity').name = `items[${index}][quantity]`;
                row.querySelector('.item-notes').name = `items[${index}][notes]`;
                row.querySelector('.remove-item').disabled = rows.length === 1;
                row.querySelector('.remove-item').classList.toggle('opacity-40', rows.length === 1);
            });
        };

        addButton.addEventListener('click', () => {
            const row = template.content.firstElementChild.cloneNode(true);
            row.dataset.row = String(nextIndex++);
            container.appendChild(row);
            updateRows();
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-item');
            if (button && container.querySelectorAll('.item-row').length > 1) {
                button.closest('.item-row').remove();
                updateRows();
            }
        });

        updateRows();
    })();
</script>
@endsection
