@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ route('admin.procurement.purchase-orders.show', $purchaseOrder) }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Back to purchase order</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">Receive goods</h1>
        <p class="mt-1 text-sm text-slate-400">{{ $purchaseOrder->purchase_order_number }} · Record this delivery against the supplier order. Posting updates stock on hand.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-rose-700 bg-rose-950/50 px-4 py-3 text-sm text-rose-200">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.procurement.goods-received-vouchers.store', $purchaseOrder) }}" class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        @csrf
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="inventory_warehouse_id" class="mb-1 block text-xs font-medium text-slate-300">Receiving warehouse</label>
                <select id="inventory_warehouse_id" name="inventory_warehouse_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('inventory_warehouse_id', $defaultWarehouseId) == $warehouse->id)>{{ $warehouse->name }} · {{ $warehouse->code }}</option>
                    @endforeach
                </select>
                @error('inventory_warehouse_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="received_date" class="mb-1 block text-xs font-medium text-slate-300">Date received</label>
                <input type="date" id="received_date" name="received_date" value="{{ old('received_date', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('received_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="delivery_note_number" class="mb-1 block text-xs font-medium text-slate-300">Supplier Delivery Note (D/Note) #</label>
                <input type="text" id="delivery_note_number" name="delivery_note_number" value="{{ old('delivery_note_number') }}" placeholder="e.g. DN-84920" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('delivery_note_number')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="delivery_note_date" class="mb-1 block text-xs font-medium text-slate-300">Delivery Note Date</label>
                <input type="date" id="delivery_note_date" name="delivery_note_date" value="{{ old('delivery_note_date', now()->toDateString()) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('delivery_note_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label for="carrier_name" class="mb-1 block text-xs font-medium text-slate-300">Carrier / Transporter / Driver</label>
                <input type="text" id="carrier_name" name="carrier_name" value="{{ old('carrier_name') }}" placeholder="e.g. DHL Express, Swift Logistics, Driver John Doe" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('carrier_name')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Ordered</th><th class="px-4 py-3">Previously received</th><th class="px-4 py-3">Outstanding</th><th class="px-4 py-3">Receive now</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($purchaseOrder->items as $line)
                        @php($previouslyReceived = (float) $line->goodsReceivedItems->sum('quantity_received'))
                        @php($outstanding = max(0, (float) $line->quantity_ordered - $previouslyReceived))
                        <tr>
                            <td class="px-4 py-3 text-slate-200">{{ $line->item->name }} <span class="text-xs text-slate-500">{{ $line->item->code }}</span></td>
                            <td class="px-4 py-3 text-slate-300">{{ number_format((float) $line->quantity_ordered, 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ number_format($previouslyReceived, 4) }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ number_format($outstanding, 4) }}</td>
                            <td class="px-4 py-3">
                                <input type="number" name="received_quantities[{{ $line->id }}]" value="{{ old('received_quantities.' . $line->id, 0) }}" min="0" max="{{ $outstanding }}" step="0.0001" required class="w-36 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                @error('received_quantities.' . $line->id)<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error('received_quantities')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror

        <div>
            <label for="notes" class="mb-1 block text-xs font-medium text-slate-300">Delivery notes</label>
            <textarea id="notes" name="notes" rows="2" maxlength="2000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
            <a href="{{ route('admin.procurement.purchase-orders.show', $purchaseOrder) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Post goods received voucher</button>
        </div>
    </form>
</div>
@endsection
