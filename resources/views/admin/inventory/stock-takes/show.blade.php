@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ route('admin.inventory.stock-takes.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Back to stock takes</a>
        <div class="mt-2 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <h1 class="text-2xl font-bold text-slate-50">{{ $stockTake->stock_take_number }}</h1>
                <p class="mt-1 text-sm text-slate-400">{{ $stockTake->warehouse->name }} · Counted by {{ $stockTake->taker->name }} · {{ $stockTake->stock_take_date->format('d M Y') }}</p>
            </div>
            <span class="w-fit rounded-full bg-slate-700 px-3 py-1 text-sm text-slate-200">{{ str_replace('_', ' ', ucfirst($stockTake->status)) }}</span>
        </div>
    </div>

    @if($stockTake->notes)
        <p class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 text-sm text-slate-300">{{ $stockTake->notes }}</p>
    @endif
    @error('stock_take')<p class="rounded-lg border border-rose-700/50 bg-rose-950/30 p-3 text-sm text-rose-300">{{ $message }}</p>@enderror

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">System quantity</th><th class="px-4 py-3">Physical count</th><th class="px-4 py-3">Variance</th><th class="px-4 py-3">Unit cost</th><th class="px-4 py-3">Adjustment value</th></tr></thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($stockTake->items as $line)
                        @php($variance = (float) $line->counted_quantity - (float) $line->system_quantity)
                        @php($varianceValue = round(abs($variance) * (float) $line->system_average_unit_cost, 2))
                        <tr class="text-slate-300">
                            <td class="px-4 py-3 text-slate-100">{{ $line->item->name }} <span class="text-xs text-slate-500">({{ $line->item->code }})</span></td>
                            <td class="px-4 py-3">{{ number_format((float) $line->system_quantity, 4) }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $line->counted_quantity, 4) }}</td>
                            <td class="px-4 py-3 {{ $variance < 0 ? 'text-rose-300' : ($variance > 0 ? 'text-emerald-300' : '') }}">{{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 4) }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $line->system_average_unit_cost, 4) }}</td>
                            <td class="px-4 py-3 {{ $variance < 0 ? 'text-rose-300' : ($variance > 0 ? 'text-emerald-300' : '') }}">{{ $variance > 0 ? '+' : ($variance < 0 ? '-' : '') }}{{ number_format($varianceValue, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if($stockTake->reviews->isNotEmpty())
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <h2 class="font-semibold text-slate-100">Review history</h2>
            <div class="mt-3 space-y-3">
                @foreach($stockTake->reviews as $review)
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

    @if($stockTake->status === 'returned' && (int) $stockTake->taken_by === (int) auth()->id())
        <a href="{{ route('admin.inventory.stock-takes.edit', $stockTake) }}" class="inline-flex rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">Correct and resubmit</a>
    @endif

    @can('inventory.stock-takes.approve')
        @if($stockTake->status === 'pending_review' && (int) $stockTake->taken_by !== (int) auth()->id())
            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <form method="POST" action="{{ route('admin.inventory.stock-takes.approve', $stockTake) }}" class="rounded-2xl border border-emerald-800/70 bg-slate-900/80 p-5">
                    @csrf
                    <h2 class="font-semibold text-slate-100">Approve and post count</h2>
                    <p class="mt-1 text-sm text-slate-400">Posts approved quantity variances to inventory and the inventory adjustment account.</p>
                    <button class="mt-4 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Approve and post</button>
                </form>
                <form method="POST" action="{{ route('admin.inventory.stock-takes.return', $stockTake) }}" class="rounded-2xl border border-amber-800/70 bg-slate-900/80 p-5">
                    @csrf
                    <h2 class="font-semibold text-slate-100">Return for recount</h2>
                    <label for="review_notes" class="mb-1 mt-3 block text-xs text-slate-300">Reason / instructions</label>
                    <textarea id="review_notes" name="review_notes" required maxlength="2000" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('review_notes') }}</textarea>
                    @error('review_notes')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    <button class="mt-3 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">Return to Stores Person</button>
                </form>
            </section>
        @endif
    @endcan
</div>
@endsection
