@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ route('admin.procurement.requisitions.show', $requisition) }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Back to requisition</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">Create purchase order</h1>
        <p class="mt-1 text-sm text-slate-400">{{ $requisition->requisition_number }} · Enter supplier and quoted unit costs. This creates a draft, not an issued order.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-rose-700 bg-rose-950/50 px-4 py-3 text-sm text-rose-200">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.procurement.purchase-orders.store', $requisition) }}" class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        @csrf
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="md:col-span-1">
                <label for="vendor_id" class="mb-1 block text-xs font-medium text-slate-300">Supplier</label>
                <select id="vendor_id" name="vendor_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">Select active supplier</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->name }} · {{ $vendor->code }}</option>
                    @endforeach
                </select>
                @error('vendor_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="order_date" class="mb-1 block text-xs font-medium text-slate-300">Order date</label>
                <input type="date" id="order_date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('order_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="expected_delivery_date" class="mb-1 block text-xs font-medium text-slate-300">Expected delivery</label>
                <input type="date" id="expected_delivery_date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('expected_delivery_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Quantity</th><th class="px-4 py-3">Unit cost</th><th class="px-4 py-3">Estimated line total</th></tr></thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($requisition->items as $line)
                        <tr>
                            <td class="px-4 py-3 text-slate-200">{{ $line->item->name }} <span class="text-xs text-slate-500">{{ $line->item->code }}</span></td>
                            <td class="px-4 py-3 text-slate-300">{{ number_format((float) $line->quantity, 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-4 py-3"><input type="number" name="unit_costs[{{ $line->id }}]" value="{{ old('unit_costs.' . $line->id) }}" min="0" step="0.0001" required class="w-36 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100"></td>
                            <td class="px-4 py-3 text-slate-400">Calculated on save</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error('unit_costs')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror

        <div>
            <label for="notes" class="mb-1 block text-xs font-medium text-slate-300">Order notes</label>
            <textarea id="notes" name="notes" rows="2" maxlength="2000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
            <a href="{{ route('admin.procurement.requisitions.show', $requisition) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Cancel</a>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save PO draft</button>
        </div>
    </form>
</div>
@endsection
