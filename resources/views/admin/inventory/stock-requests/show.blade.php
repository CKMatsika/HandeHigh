@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ route('admin.inventory.stock-requests.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Back to stock requests</a>
        <div class="mt-2 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <h1 class="text-2xl font-bold text-slate-50">{{ $stockRequest->request_number }}</h1>
                <p class="mt-1 text-sm text-slate-400">Requested by {{ $stockRequest->requester->name }} · {{ $stockRequest->department?->name ?? 'General' }}</p>
            </div>
            <span class="w-fit rounded-full bg-slate-700 px-3 py-1 text-sm text-slate-200">{{ str_replace('_', ' ', ucfirst($stockRequest->status)) }}</span>
        </div>
    </div>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <h2 class="font-semibold text-slate-100">Request details</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-slate-300">{{ $stockRequest->purpose }}</p>
        <p class="mt-2 text-xs text-slate-400">Needed by: {{ $stockRequest->needed_by?->format('d M Y') ?? 'Not specified' }} · Submitted: {{ $stockRequest->submitted_at?->format('d M Y H:i') ?? '-' }}</p>
    </section>

    @if($stockRequest->reviews->isNotEmpty())
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <h2 class="font-semibold text-slate-100">Review history</h2>
            <div class="mt-3 space-y-3">
                @foreach($stockRequest->reviews as $review)
                    <div class="rounded-lg border border-slate-700 bg-slate-950/50 p-3">
                        <p class="text-sm font-semibold text-slate-200">
                            {{ ucfirst($review->decision) }} by {{ $review->reviewer->name }}
                            <span class="font-normal text-slate-400">· {{ $review->reviewed_at->format('d M Y H:i') }}</span>
                        </p>
                        @if($review->comments)
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-300">{{ $review->comments }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Requested</th><th class="px-4 py-3">Issued</th><th class="px-4 py-3">Remaining</th><th class="px-4 py-3">Note</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($stockRequest->items as $line)
                        <tr class="text-slate-300">
                            <td class="px-4 py-3 text-slate-100">{{ $line->item->name }} <span class="text-xs text-slate-500">({{ $line->item->code }})</span></td>
                            <td class="px-4 py-3">{{ number_format((float) $line->quantity_requested, 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $line->quantity_issued, 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-4 py-3">{{ number_format(max(0, (float) $line->quantity_requested - (float) $line->quantity_issued), 4) }} {{ $line->item->unit_of_measure }}</td>
                            <td class="px-4 py-3">{{ $line->notes ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if($stockRequest->status === 'returned' && (int) $stockRequest->requested_by === (int) auth()->id())
        <a href="{{ route('admin.inventory.stock-requests.edit', $stockRequest) }}" class="inline-flex rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">Correct and resubmit</a>
    @endif

    @can('inventory.stock-requests.approve')
        @if($stockRequest->status === 'pending_review' && (int) $stockRequest->requested_by !== (int) auth()->id())
            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <form method="POST" action="{{ route('admin.inventory.stock-requests.approve', $stockRequest) }}" class="rounded-2xl border border-emerald-800/70 bg-slate-900/80 p-5">
                    @csrf
                    <h2 class="font-semibold text-slate-100">Approve request</h2>
                    <p class="mt-1 text-sm text-slate-400">Approval allows the Stores Person to issue stock against this request.</p>
                    <button class="mt-4 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.inventory.stock-requests.return', $stockRequest) }}" class="rounded-2xl border border-amber-800/70 bg-slate-900/80 p-5">
                    @csrf
                    <h2 class="font-semibold text-slate-100">Return for correction</h2>
                    <label for="review_notes" class="mb-1 mt-3 block text-xs text-slate-300">Reason / instructions</label>
                    <textarea id="review_notes" name="review_notes" required maxlength="2000" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('review_notes') }}</textarea>
                    @error('review_notes')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    <button class="mt-3 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">Return to requester</button>
                </form>
            </section>
        @endif
    @endcan

    @can('inventory.stock-issues.create')
        @if(in_array($stockRequest->status, ['approved', 'partially_issued'], true))
            <form method="POST" action="{{ route('admin.inventory.stock-requests.issue', $stockRequest) }}" class="space-y-4 rounded-2xl border border-cyan-800/70 bg-slate-900/80 p-5">
                @csrf
                <div>
                    <h2 class="font-semibold text-slate-100">Issue stock</h2>
                    <p class="mt-1 text-sm text-slate-400">Choose a warehouse and enter quantities to issue now. Partial issues are supported.</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="inventory_warehouse_id" class="mb-1 block text-xs text-slate-300">Warehouse</label>
                        <select id="inventory_warehouse_id" name="inventory_warehouse_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                            <option value="">Select warehouse</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('inventory_warehouse_id') == $warehouse->id)>{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                            @endforeach
                        </select>
                        @error('inventory_warehouse_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="issued_date" class="mb-1 block text-xs text-slate-300">Issue date</label>
                        <input id="issued_date" name="issued_date" type="date" required value="{{ old('issued_date', now()->toDateString()) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        @error('issued_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2 pr-3">Item</th><th class="py-2 pr-3">Remaining</th><th class="py-2">Issue now</th></tr></thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($stockRequest->items as $line)
                                @php($remaining = max(0, (float) $line->quantity_requested - (float) $line->quantity_issued))
                                <tr>
                                    <td class="py-3 pr-3 text-slate-200">{{ $line->item->name }} <span class="text-xs text-slate-500">{{ $line->item->unit_of_measure }}</span></td>
                                    <td class="py-3 pr-3 text-slate-300">{{ number_format($remaining, 4) }}</td>
                                    <td class="py-3"><input name="issued_quantities[{{ $line->id }}]" type="number" min="0" max="{{ $remaining }}" step="0.0001" value="{{ old("issued_quantities.$line->id", 0) }}" class="w-40 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100"></td>
                                </tr>
                                @error("issued_quantities.$line->id")<tr><td colspan="3" class="pb-2 text-xs text-rose-400">{{ $message }}</td></tr>@enderror
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('issued_quantities')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
                <div>
                    <label for="notes" class="mb-1 block text-xs text-slate-300">Issue note</label>
                    <textarea id="notes" name="notes" rows="2" maxlength="2000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Post stock issue</button>
            </form>
        @endif
    @endcan

    @if($stockRequest->issues->isNotEmpty())
        <section class="space-y-3">
            <h2 class="font-semibold text-slate-100">Issue history</h2>
            @foreach($stockRequest->issues as $issue)
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                    <div class="flex flex-wrap justify-between gap-2">
                        <p class="font-mono text-sm text-slate-100">{{ $issue->issue_number }}</p>
                        <p class="text-xs text-slate-400">{{ $issue->issued_date->format('d M Y') }} · {{ $issue->warehouse->name }} · issued by {{ $issue->issuer->name }}</p>
                    </div>
                    <div class="mt-3 space-y-1 text-sm text-slate-300">
                        @foreach($issue->items as $line)
                            <p>{{ $line->item->name }} — {{ number_format((float) $line->quantity_issued, 4) }} {{ $line->item->unit_of_measure }} at {{ number_format((float) $line->unit_cost, 2) }}</p>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</div>
@endsection
