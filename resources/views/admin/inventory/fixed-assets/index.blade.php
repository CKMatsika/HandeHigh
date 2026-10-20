@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div>
        <a href="{{ route('admin.inventory.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Stores dashboard</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">Fixed asset register</h1>
        <p class="mt-1 text-sm text-slate-400">Assets are created from goods receipts. Maintain serial numbers, locations and custodian assignments here.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400"><tr><th class="px-4 py-3">Asset tag</th><th class="px-4 py-3">Item</th><th class="px-4 py-3">Serial</th><th class="px-4 py-3">Location</th><th class="px-4 py-3">Custodian</th><th class="px-4 py-3">Cost</th><th class="px-4 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($assets as $asset)
                        <tr class="text-slate-300">
                            <td class="px-4 py-3 font-mono"><a href="{{ route('admin.inventory.fixed-assets.show', $asset) }}" class="text-emerald-300 hover:text-emerald-200">{{ $asset->asset_number }}</a></td>
                            <td class="px-4 py-3 text-slate-100">{{ $asset->item->name }}</td>
                            <td class="px-4 py-3">{{ $asset->serial_number ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $asset->location ?: $asset->warehouse?->name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $asset->custodian?->name ?? 'Stores' }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $asset->purchase_cost, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs {{ $asset->is_capitalized ? 'bg-indigo-500/15 text-indigo-300' : 'bg-slate-700 text-slate-300' }}">
                                    {{ $asset->is_capitalized ? 'Capitalized' : 'Low value' }} · {{ str_replace('_', ' ', ucfirst($asset->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Assets are added here when asset-type purchase orders are received.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-800 px-4 py-3">{{ $assets->links() }}</div>
    </div>
</div>
@endsection
