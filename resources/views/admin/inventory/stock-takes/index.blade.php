@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <a href="{{ route('admin.inventory.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Stores dashboard</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-50">Stock takes</h1>
            <p class="mt-1 text-sm text-slate-400">Record physical counts and submit variances for independent approval.</p>
        </div>
        @can('inventory.stock-takes.create')
            <a href="{{ route('admin.inventory.stock-takes.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Start stock take</a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    <tr><th class="px-4 py-3">Stock take</th><th class="px-4 py-3">Warehouse</th><th class="px-4 py-3">Counted by</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Submitted</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($stockTakes as $stockTake)
                        <tr class="text-slate-300">
                            <td class="px-4 py-3 font-mono text-slate-100">{{ $stockTake->stock_take_number }}</td>
                            <td class="px-4 py-3">{{ $stockTake->warehouse->name }}</td>
                            <td class="px-4 py-3">{{ $stockTake->taker->name }}</td>
                            <td class="px-4 py-3">{{ $stockTake->stock_take_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $stockTake->submitted_at?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-700 px-2 py-1 text-xs text-slate-200">{{ str_replace('_', ' ', ucfirst($stockTake->status)) }}</span></td>
                            <td class="px-4 py-3"><a href="{{ route('admin.inventory.stock-takes.show', $stockTake) }}" class="text-emerald-300 hover:text-emerald-200">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No stock takes have been submitted.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-800 px-4 py-3">{{ $stockTakes->links() }}</div>
    </div>
</div>
@endsection
