@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('admin.procurement.goods-received-vouchers.show', $supplierInvoice->goodsReceivedVoucher) }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Back to GRV</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">Supplier invoice {{ $supplierInvoice->invoice_number }}</h1>
        <p class="mt-1 text-sm text-slate-400">{{ ucfirst(str_replace('_', ' ', $supplierInvoice->status)) }} · GRV {{ $supplierInvoice->goodsReceivedVoucher->voucher_number }}</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-700 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-700 bg-rose-950/50 px-4 py-3 text-sm text-rose-200">{{ $errors->first() }}</div>
    @endif

    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 md:grid-cols-3">
        <div><p class="text-xs uppercase text-slate-500">Supplier</p><p class="mt-1 text-sm text-slate-200">{{ $supplierInvoice->vendor->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Invoice date</p><p class="mt-1 text-sm text-slate-200">{{ $supplierInvoice->invoice_date?->format('d M Y') }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Due date</p><p class="mt-1 text-sm text-slate-200">{{ $supplierInvoice->due_date?->format('d M Y') }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Submitted by</p><p class="mt-1 text-sm text-slate-200">{{ $supplierInvoice->submitter->name }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Invoice total</p><p class="mt-1 text-sm text-slate-200">{{ number_format((float) $supplierInvoice->total_amount, 2) }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">GRV cost basis</p><p class="mt-1 text-sm text-slate-200">{{ number_format($stockCostBasis, 2) }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Price variance</p><p class="mt-1 text-sm {{ abs($priceVariance) >= 0.01 ? 'text-amber-300' : 'text-emerald-300' }}">{{ number_format($priceVariance, 2) }}</p></div>
        @if($supplierInvoice->notes)
            <div class="md:col-span-3"><p class="text-xs uppercase text-slate-500">Notes</p><p class="mt-1 whitespace-pre-wrap text-sm text-slate-200">{{ $supplierInvoice->notes }}</p></div>
        @endif
        @if($supplierInvoice->approval_notes)
            <div class="md:col-span-3"><p class="text-xs uppercase text-slate-500">Latest review comments</p><p class="mt-1 whitespace-pre-wrap text-sm text-slate-200">{{ $supplierInvoice->approval_notes }}</p></div>
        @endif
    </section>

    @if($supplierInvoice->status === 'pending_review' && auth()->user()->can('procurement.supplier-invoices.approve'))
        <section class="rounded-2xl border border-amber-700/50 bg-amber-950/20 p-5">
            <h2 class="font-semibold text-amber-100">Finance review</h2>
            <p class="mt-1 text-sm text-slate-400">Approve to post the supplier liability and clear the received-not-invoiced accrual, or return it for correction.</p>
            <form method="POST" action="{{ route('admin.procurement.supplier-invoices.review', $supplierInvoice) }}" class="mt-4 space-y-3">
                @csrf
                <textarea name="comments" rows="2" maxlength="2000" placeholder="Review comments" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100"></textarea>
                <div class="flex flex-wrap gap-3">
                    <button name="decision" value="approved" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Approve and post</button>
                    <button name="decision" value="returned" class="rounded-lg border border-amber-700 px-4 py-2 text-sm font-semibold text-amber-200 hover:bg-amber-950">Return for correction</button>
                </div>
            </form>
        </section>
    @elseif($supplierInvoice->status === 'returned' && (int) $supplierInvoice->submitted_by === (int) auth()->id())
        <a href="{{ route('admin.procurement.supplier-invoices.edit', $supplierInvoice) }}" class="inline-flex rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-500">Correct and resubmit</a>
    @endif

    @if($supplierInvoice->bill)
        <section class="rounded-2xl border border-emerald-800/50 bg-emerald-950/20 p-5">
            <h2 class="font-semibold text-emerald-100">Accounts payable bill posted</h2>
            <a href="{{ route('admin.bills.show', $supplierInvoice->bill) }}" class="mt-1 inline-block font-mono text-sm text-emerald-300 hover:text-emerald-200">{{ $supplierInvoice->bill->bill_number }}</a>
        </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Quantity</th><th class="px-5 py-3">Invoice unit price</th><th class="px-5 py-3">GRV cost basis</th><th class="px-5 py-3">Invoice line total</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($supplierInvoice->items as $line)
                        <tr class="text-slate-300">
                            <td class="px-5 py-3">{{ $line->goodsReceivedItem->item->name }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->quantity_invoiced, 4) }} {{ $line->goodsReceivedItem->item->unit_of_measure }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->unit_price, 4) }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->stock_cost_basis, 2) }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <h2 class="font-semibold text-slate-100">Review history</h2>
        <div class="mt-4 space-y-3">
            @forelse($supplierInvoice->reviews as $review)
                <div class="border-l-2 {{ $review->decision === 'approved' ? 'border-emerald-500' : 'border-amber-500' }} pl-3">
                    <p class="text-sm text-slate-200">{{ ucfirst($review->decision) }} by {{ $review->reviewer->name }}</p>
                    <p class="text-xs text-slate-500">{{ $review->reviewed_at->format('d M Y H:i') }}{{ $review->comments ? ' · ' . $review->comments : '' }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No review decisions recorded.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
