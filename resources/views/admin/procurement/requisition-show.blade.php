@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('admin.procurement.index') }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Procurement</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-50">Requisition {{ $requisition->requisition_number }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ str_replace('_', ' ', ucfirst($requisition->status)) }} · Requested by {{ $requisition->requester->name }}</p>
        </div>
        @if(in_array($requisition->status, ['draft', 'rejected'], true) && (int) $requisition->requested_by === (int) auth()->id())
            <form method="POST" action="{{ route('admin.procurement.requisitions.submit', $requisition) }}">
                @csrf
                <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Submit for approval</button>
            </form>
        @endif
        @can('procurement.purchase-orders.create')
            @if($requisition->status === 'approved' && !$requisition->purchaseOrder)
                <a href="{{ route('admin.procurement.purchase-orders.create', $requisition) }}" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">Create purchase order</a>
            @endif
        @endcan
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-700 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-700 bg-rose-950/50 px-4 py-3 text-sm text-rose-200">{{ $errors->first() }}</div>
    @endif

    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 md:grid-cols-3">
        <div><p class="text-xs uppercase text-slate-500">Department</p><p class="mt-1 text-sm text-slate-200">{{ $requisition->department?->name ?? 'Not specified' }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Delivery warehouse</p><p class="mt-1 text-sm text-slate-200">{{ $requisition->deliveryWarehouse?->name ?? 'Stores to assign' }}</p></div>
        <div><p class="text-xs uppercase text-slate-500">Needed by</p><p class="mt-1 text-sm text-slate-200">{{ $requisition->needed_by?->format('d M Y') ?? 'Not specified' }}</p></div>
        <div class="md:col-span-3"><p class="text-xs uppercase text-slate-500">Purpose</p><p class="mt-1 whitespace-pre-wrap text-sm text-slate-200">{{ $requisition->purpose }}</p></div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="border-b border-slate-800 px-5 py-4"><h2 class="font-semibold text-slate-100">Requested items</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400"><tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Quantity</th><th class="px-5 py-3">Notes</th></tr></thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($requisition->items as $line)
                        <tr class="text-slate-300">
                            <td class="px-5 py-3">{{ $line->item->name }} <span class="text-xs text-slate-500">{{ $line->item->code }}</span></td>
                            <td class="px-5 py-3">{{ $line->item->category->name }}</td>
                            <td class="px-5 py-3">{{ number_format((float) $line->quantity, 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-5 py-3">{{ $line->notes ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @can('procurement.approve')
        @if($requisition->status === 'submitted' && (int) $requisition->requested_by !== (int) auth()->id())
            <section class="rounded-2xl border border-amber-700/50 bg-amber-950/20 p-5">
                <h2 class="font-semibold text-amber-100">Approval decision</h2>
                <p class="mt-1 text-sm text-slate-400">No monetary threshold is applied in this initial workflow. A permitted approver must review the request.</p>
                <form method="POST" action="{{ route('admin.procurement.requisitions.review', $requisition) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="comments" rows="2" maxlength="2000" placeholder="Approval or rejection comments" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100"></textarea>
                    <div class="flex flex-wrap gap-3">
                        <button name="decision" value="approved" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Approve requisition</button>
                        <button name="decision" value="rejected" class="rounded-lg border border-rose-700 px-4 py-2 text-sm font-semibold text-rose-200 hover:bg-rose-950">Reject requisition</button>
                    </div>
                </form>
            </section>
        @endif
    @endcan

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <h2 class="font-semibold text-slate-100">Approval history</h2>
        <div class="mt-4 space-y-3">
            @forelse($requisition->reviews as $review)
                <div class="border-l-2 {{ $review->decision === 'approved' ? 'border-emerald-500' : 'border-rose-500' }} pl-3">
                    <p class="text-sm text-slate-200">{{ ucfirst($review->decision) }} by {{ $review->reviewer->name }}</p>
                    <p class="text-xs text-slate-500">{{ $review->reviewed_at->format('d M Y H:i') }}{{ $review->comments ? ' · ' . $review->comments : '' }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No approval decisions recorded.</p>
            @endforelse
        </div>
    </section>

    @if($requisition->purchaseOrder)
        <section class="flex flex-col justify-between gap-3 rounded-2xl border border-emerald-800/50 bg-emerald-950/20 p-5 sm:flex-row sm:items-center">
            <div><h2 class="font-semibold text-emerald-100">Purchase order created</h2><p class="mt-1 font-mono text-sm text-emerald-300">{{ $requisition->purchaseOrder->purchase_order_number }} · {{ ucfirst($requisition->purchaseOrder->status) }}</p></div>
            <a href="{{ route('admin.procurement.purchase-orders.show', $requisition->purchaseOrder) }}" class="text-sm font-semibold text-emerald-300 hover:text-emerald-200">View purchase order &rarr;</a>
        </section>
    @endif
</div>
@endsection
