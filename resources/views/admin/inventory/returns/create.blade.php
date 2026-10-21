@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('admin.procurement.goods-received-vouchers.show', $voucher) }}" class="text-xs text-indigo-400 hover:text-indigo-300">&larr; Back to GRN {{ $voucher->voucher_number }}</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-50 sm:text-3xl">Return Goods to Vendor (Debit Note)</h1>
        <p class="mt-1 text-sm text-slate-400">{{ $voucher->purchaseOrder->vendor->name }} · Return items from GRN {{ $voucher->voucher_number }}. Posting deducts stock on hand.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-rose-700 bg-rose-950/50 px-4 py-3 text-sm text-rose-200">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.inventory.returns.store', $voucher) }}" class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        @csrf

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="return_date" class="mb-1 block text-xs font-medium text-slate-300">Return Date</label>
                <input type="date" id="return_date" name="return_date" value="{{ old('return_date', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('return_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="reason" class="mb-1 block text-xs font-medium text-slate-300">Return Reason</label>
                <select id="reason" name="reason" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    <option value="">Select reason</option>
                    <option value="defective" @selected(old('reason') === 'defective')>Defective / Malfunctioning</option>
                    <option value="damaged" @selected(old('reason') === 'damaged')>Damaged in Transit / Crushed</option>
                    <option value="wrong_specification" @selected(old('reason') === 'wrong_specification')>Wrong Specification / Item</option>
                    <option value="excess_delivery" @selected(old('reason') === 'excess_delivery')>Excess Delivery / Not Ordered</option>
                    <option value="expired" @selected(old('reason') === 'expired')>Expired / Near Expiry</option>
                    <option value="other" @selected(old('reason') === 'other')>Other (See details)</option>
                </select>
                @error('reason')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="debit_note_number" class="mb-1 block text-xs font-medium text-slate-300">Debit Note Reference # (Optional)</label>
                <input type="text" id="debit_note_number" name="debit_note_number" value="{{ old('debit_note_number') }}" placeholder="Auto-generated if empty" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('debit_note_number')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-3">
                <label for="reason_details" class="mb-1 block text-xs font-medium text-slate-300">Explanation &amp; Inspection Findings</label>
                <textarea id="reason_details" name="reason_details" rows="2" placeholder="Describe the physical defects or rejection findings..." class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('reason_details') }}</textarea>
                @error('reason_details')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-slate-200 mb-2">Select Items &amp; Quantities to Return</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3 text-right">Received Qty</th>
                            <th class="px-4 py-3 text-right">Prev. Returned</th>
                            <th class="px-4 py-3 text-right">Max Returnable</th>
                            <th class="px-4 py-3 text-right">Return Now</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach($voucher->items as $item)
                            @php($prevReturned = (float) ($returnedQuantities[$item->id] ?? 0))
                            @php($maxReturnable = max(0, (float) $item->quantity_received - $prevReturned))
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-200">{{ $item->item->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->item->code }}</div>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-400">{{ number_format((float) $item->quantity_received, 2) }}</td>
                                <td class="px-4 py-3 text-right text-rose-400">{{ number_format($prevReturned, 2) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-400 font-semibold">{{ number_format($maxReturnable, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <input type="number" name="return_quantities[{{ $item->id }}]" value="{{ old('return_quantities.' . $item->id, 0) }}" min="0" max="{{ $maxReturnable }}" step="0.0001" class="w-32 rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-right text-sm text-slate-100">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-800 pt-4">
            <a href="{{ route('admin.procurement.goods-received-vouchers.show', $voucher) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500">
                Post Return &amp; Issue Debit Note
            </button>
        </div>
    </form>
</div>
@endsection
