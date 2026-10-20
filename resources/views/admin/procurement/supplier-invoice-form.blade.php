@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ $invoice ? route('admin.procurement.supplier-invoices.show', $invoice) : route('admin.procurement.goods-received-vouchers.show', $voucher) }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Back</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">{{ $invoice ? 'Correct supplier invoice' : 'Record supplier invoice' }}</h1>
        <p class="mt-1 text-sm text-slate-400">{{ $voucher->purchaseOrder->vendor->name }} · GRV {{ $voucher->voucher_number }}</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-rose-700 bg-rose-950/50 px-4 py-3 text-sm text-rose-200">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $invoice ? route('admin.procurement.supplier-invoices.update', $invoice) : route('admin.procurement.supplier-invoices.store', $voucher) }}" class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        @csrf
        @if($invoice)
            @method('PUT')
        @endif
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="invoice_number" class="mb-1 block text-xs font-medium text-slate-300">Supplier invoice number</label>
                <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $invoice?->invoice_number) }}" maxlength="100" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('invoice_number')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="invoice_date" class="mb-1 block text-xs font-medium text-slate-300">Invoice date</label>
                <input type="date" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $invoice?->invoice_date?->toDateString() ?? now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('invoice_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="due_date" class="mb-1 block text-xs font-medium text-slate-300">Due date</label>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $invoice?->due_date?->toDateString() ?? now()->addDays(30)->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @error('due_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>

        @php($invoiceLines = $invoice?->items?->keyBy('goods_received_voucher_item_id') ?? collect())
        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Received</th><th class="px-4 py-3">Uninvoiced</th><th class="px-4 py-3">GRV unit cost</th><th class="px-4 py-3">Invoice quantity</th><th class="px-4 py-3">Invoice unit price</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($voucher->items as $line)
                        @php($invoiceLine = $invoiceLines->get($line->id))
                        @php($remaining = $remainingQuantities[$line->id] ?? 0)
                        <tr>
                            <td class="px-4 py-3 text-slate-200">{{ $line->item->name }} <span class="text-xs text-slate-500">{{ $line->item->code }}</span></td>
                            <td class="px-4 py-3 text-slate-300">{{ number_format((float) $line->quantity_received, 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ number_format($remaining, 4) }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ number_format((float) $line->unit_cost, 4) }}</td>
                            <td class="px-4 py-3">
                                <input type="number" name="quantities[{{ $line->id }}]" value="{{ old('quantities.' . $line->id, $invoiceLine?->quantity_invoiced ?? $remaining) }}" min="0" max="{{ $remaining }}" step="0.0001" required class="w-32 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                @error('quantities.' . $line->id)<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" name="unit_prices[{{ $line->id }}]" value="{{ old('unit_prices.' . $line->id, $invoiceLine?->unit_price ?? $line->unit_cost) }}" min="0" max="999999999.9999" step="0.0001" required class="w-36 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                @error('unit_prices.' . $line->id)<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error('quantities')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
        @error('unit_prices')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
        <p class="text-xs text-slate-400">The invoice is held for authorized review. Approved invoices clear the GRV accrual to Accounts Payable; price differences post to the inventory price-variance account.</p>

        <div>
            <label for="notes" class="mb-1 block text-xs font-medium text-slate-300">Notes</label>
            <textarea id="notes" name="notes" rows="2" maxlength="2000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('notes', $invoice?->notes) }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-800 pt-4">
            <a href="{{ $invoice ? route('admin.procurement.supplier-invoices.show', $invoice) : route('admin.procurement.goods-received-vouchers.show', $voucher) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ $invoice ? 'Resubmit corrected invoice' : 'Submit invoice for review' }}</button>
        </div>
    </form>
</div>
@endsection
