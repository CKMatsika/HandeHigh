@extends('layouts.app')

@section('content')
@php
    $editing = $stockRequest !== null;
    $formLines = old('items', $editing
        ? $stockRequest->items->map(fn ($line) => [
            'inventory_item_id' => $line->inventory_item_id,
            'quantity_requested' => $line->quantity_requested,
            'notes' => $line->notes,
        ])->all()
        : []);
    $formLines = array_pad($formLines, 6, []);
@endphp
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ $editing ? route('admin.inventory.stock-requests.show', $stockRequest) : route('admin.inventory.stock-requests.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Back to stock requests</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">{{ $editing ? 'Correct stock request' : 'New stock request' }}</h1>
        <p class="mt-1 text-sm text-slate-400">Submit stock needs to the approver. The stores person issues approved quantities from a selected warehouse.</p>
    </div>

    <form method="POST" action="{{ $editing ? route('admin.inventory.stock-requests.update', $stockRequest) : route('admin.inventory.stock-requests.store') }}" class="space-y-5 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        @csrf
        @if($editing)
            @method('PUT')
        @endif
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="department_id" class="mb-1 block text-xs font-medium text-slate-300">Department / cost centre</label>
                <select id="department_id" name="department_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">General / unassigned</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id', $stockRequest?->department_id) == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="needed_by" class="mb-1 block text-xs font-medium text-slate-300">Needed by</label>
                <input id="needed_by" name="needed_by" type="date" value="{{ old('needed_by', $stockRequest?->needed_by?->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('needed_by')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="purpose" class="mb-1 block text-xs font-medium text-slate-300">Purpose / activity</label>
                <textarea id="purpose" name="purpose" rows="3" required maxlength="2000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('purpose', $stockRequest?->purpose) }}</textarea>
                @error('purpose')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <section class="space-y-3">
            <div>
                <h2 class="font-semibold text-slate-100">Requested items</h2>
                <p class="text-xs text-slate-400">Choose active consumable stock items. Leave unused rows blank.</p>
            </div>
            @error('items')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
            @foreach($formLines as $index => $line)
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-800 p-3 sm:grid-cols-12">
                    <div class="sm:col-span-5">
                        <label class="mb-1 block text-xs text-slate-400">Item</label>
                        <select name="items[{{ $index }}][inventory_item_id]" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                            <option value="">Select item</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(old("items.$index.inventory_item_id", $line['inventory_item_id'] ?? '') == $item->id)>{{ $item->name }} ({{ $item->code }}) — {{ $item->unit_of_measure }}</option>
                            @endforeach
                        </select>
                        @error("items.$index.inventory_item_id")<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs text-slate-400">Quantity</label>
                        <input name="items[{{ $index }}][quantity_requested]" type="number" min="0.0001" step="0.0001" value="{{ old("items.$index.quantity_requested", $line['quantity_requested'] ?? '') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        @error("items.$index.quantity_requested")<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-4">
                        <label class="mb-1 block text-xs text-slate-400">Line note</label>
                        <input name="items[{{ $index }}][notes]" value="{{ old("items.$index.notes", $line['notes'] ?? '') }}" maxlength="1000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    </div>
                </div>
            @endforeach
        </section>

        <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
            <a href="{{ route('admin.inventory.stock-requests.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Cancel</a>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">{{ $editing ? 'Resubmit for approval' : 'Submit request' }}</button>
        </div>
    </form>
</div>
@endsection
