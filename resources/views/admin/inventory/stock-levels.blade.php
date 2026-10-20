@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div>
        <a href="{{ route('admin.inventory.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Stores dashboard</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">Stock on hand and reorder levels</h1>
        <p class="mt-1 text-sm text-slate-400">Review warehouse balances and configure replenishment alerts for each consumable item.</p>
    </div>

    <form method="GET" action="{{ route('admin.inventory.stock-levels') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-800 bg-slate-900/80 p-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="warehouse_id" class="mb-1 block text-xs text-slate-300">Warehouse</label>
            <select id="warehouse_id" name="warehouse_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                @foreach($warehouses as $option)
                    <option value="{{ $option->id }}" @selected($warehouse?->id === $option->id)>{{ $option->name }} ({{ $option->code }})</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg border border-slate-600 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-slate-800">Show warehouse stock</button>
    </form>

    @if($warehouse)
        <form method="POST" action="{{ route('admin.inventory.stock-levels.update') }}" class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
            @csrf
            @method('PUT')
            <input type="hidden" name="inventory_warehouse_id" value="{{ $warehouse->id }}">
            @error('levels')<p class="m-4 rounded-lg border border-rose-700/50 bg-rose-950/30 p-3 text-sm text-rose-300">{{ $message }}</p>@enderror
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase text-slate-400"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">On hand</th><th class="px-4 py-3">Reorder at</th><th class="px-4 py-3">Reorder quantity</th><th class="px-4 py-3">Suggested order</th></tr></thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($items as $item)
                            @php($balance = $item->balances->first())
                            @php($onHand = (float) ($balance?->quantity_on_hand ?? 0))
                            @php($reorderLevel = (float) ($balance?->reorder_level ?? 0))
                            @php($reorderQuantity = $balance?->reorder_quantity === null ? null : (float) $balance->reorder_quantity)
                            <tr>
                                <td class="px-4 py-3 text-slate-100">{{ $item->name }} <span class="text-xs text-slate-500">({{ $item->code }})</span></td>
                                <td class="px-4 py-3 {{ $reorderLevel > 0 && $onHand <= $reorderLevel ? 'text-amber-300' : 'text-slate-300' }}">{{ number_format($onHand, 4) }} {{ $item->unit_of_measure }}</td>
                                <td class="px-4 py-3">
                                    <input name="levels[{{ $item->id }}][reorder_level]" type="number" min="0" step="0.0001" required value="{{ old('levels.'.$item->id.'.reorder_level', $balance?->reorder_level ?? 0) }}" class="w-32 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                </td>
                                <td class="px-4 py-3">
                                    <input name="levels[{{ $item->id }}][reorder_quantity]" type="number" min="0.0001" step="0.0001" value="{{ old('levels.'.$item->id.'.reorder_quantity', $balance?->reorder_quantity) }}" class="w-32 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                </td>
                                <td class="px-4 py-3 text-slate-300">
                                    @if($reorderLevel > 0 && $onHand <= $reorderLevel)
                                        {{ number_format($reorderQuantity ?? max(0, $reorderLevel - $onHand), 4) }} {{ $item->unit_of_measure }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No active consumable items are available to configure.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->isNotEmpty())
                <div class="border-t border-slate-800 px-4 py-3"><button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save stock levels</button></div>
            @endif
        </form>
    @else
        <p class="rounded-2xl border border-amber-700/50 bg-amber-950/30 p-5 text-sm text-amber-200">Create and activate a warehouse before configuring stock levels.</p>
    @endif
</div>
@endsection
