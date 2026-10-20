@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ route('admin.inventory.stock-takes.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Back to stock takes</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">{{ $stockTake ? 'Correct stock take' : 'Start stock take' }}</h1>
        <p class="mt-1 text-sm text-slate-400">Record the physical quantity for every active consumable in the selected warehouse. Approval posts any variance to stock and accounting.</p>
    </div>

    @if(! $stockTake)
        <form method="GET" action="{{ route('admin.inventory.stock-takes.create') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-800 bg-slate-900/80 p-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="warehouse_id" class="mb-1 block text-xs text-slate-300">Warehouse</label>
                <select id="warehouse_id" name="warehouse_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                    @foreach($warehouses as $option)
                        <option value="{{ $option->id }}" @selected($warehouse?->id === $option->id)>{{ $option->name }} ({{ $option->code }})</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg border border-slate-600 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-slate-800">Load warehouse stock</button>
        </form>
    @endif

    @if($warehouse)
        <form method="POST" action="{{ $stockTake ? route('admin.inventory.stock-takes.update', $stockTake) : route('admin.inventory.stock-takes.store') }}" class="space-y-5">
            @csrf
            @if($stockTake)
                @method('PUT')
            @else
                <input type="hidden" name="inventory_warehouse_id" value="{{ $warehouse->id }}">
            @endif
            <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                <p class="text-sm text-slate-300">Warehouse: <span class="font-semibold text-slate-100">{{ $warehouse->name }} ({{ $warehouse->code }})</span></p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="stock_take_date" class="mb-1 block text-xs text-slate-300">Count date</label>
                        <input id="stock_take_date" name="stock_take_date" type="date" required value="{{ old('stock_take_date', $stockTake?->stock_take_date?->toDateString() ?? now()->toDateString()) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        @error('stock_take_date')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="notes" class="mb-1 block text-xs text-slate-300">Notes</label>
                        <input id="notes" name="notes" maxlength="2000" value="{{ old('notes', $stockTake?->notes) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        @error('notes')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            @error('counts')<p class="rounded-lg border border-rose-700/50 bg-rose-950/30 p-3 text-sm text-rose-300">{{ $message }}</p>@enderror
            @error('system_quantities')<p class="rounded-lg border border-rose-700/50 bg-rose-950/30 p-3 text-sm text-rose-300">{{ $message }}</p>@enderror
            @error('stock_take')<p class="rounded-lg border border-rose-700/50 bg-rose-950/30 p-3 text-sm text-rose-300">{{ $message }}</p>@enderror
            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-950/60 text-xs uppercase text-slate-400"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">System on hand</th><th class="px-4 py-3">Physical count</th></tr></thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse($countLines as $line)
                                <tr>
                                    <td class="px-4 py-3 text-slate-100">{{ $line->item->name }} <span class="text-xs text-slate-500">({{ $line->item->code }})</span></td>
                                    <td class="px-4 py-3 text-slate-300">{{ number_format((float) $line->system_quantity, 4) }} {{ $line->item->unit_of_measure }}</td>
                                    <td class="px-4 py-3">
                                        <input name="system_quantities[{{ $line->item->id }}]" type="hidden" value="{{ $line->system_quantity }}">
                                        <input name="counts[{{ $line->item->id }}]" type="number" min="0" step="0.0001" required value="{{ old('counts.'.$line->item->id, $line->counted_quantity) }}" class="w-36 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                        @error('counts.'.$line->item->id)<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                                        @error('system_quantities.'.$line->item->id)<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">There are no active consumable items to count for this warehouse.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            @if($countLines->isNotEmpty())
                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">{{ $stockTake ? 'Resubmit count' : 'Submit count for approval' }}</button>
            @endif
        </form>
    @else
        <p class="rounded-2xl border border-amber-700/50 bg-amber-950/30 p-5 text-sm text-amber-200">Create and activate a warehouse before starting a stock take.</p>
    @endif
</div>
@endsection
