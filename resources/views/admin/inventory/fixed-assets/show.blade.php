@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ route('admin.inventory.fixed-assets.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Back to fixed assets</a>
        <div class="mt-2 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <h1 class="text-2xl font-bold text-slate-50">{{ $fixedAsset->asset_number }}</h1>
                <p class="mt-1 text-sm text-slate-400">{{ $fixedAsset->item->name }} · {{ $fixedAsset->item->category->name }}</p>
            </div>
            <span class="w-fit rounded-full bg-slate-700 px-3 py-1 text-sm text-slate-200">{{ $fixedAsset->is_capitalized ? 'Capitalized' : 'Below threshold' }} · {{ str_replace('_', ' ', ucfirst($fixedAsset->status)) }}</span>
        </div>
    </div>

    <section class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:grid-cols-2">
        <div><p class="text-xs text-slate-400">Serial number</p><p class="mt-1 text-sm text-slate-100">{{ $fixedAsset->serial_number ?: 'Not recorded' }}</p></div>
        <div><p class="text-xs text-slate-400">Location / warehouse</p><p class="mt-1 text-sm text-slate-100">{{ $fixedAsset->location ?: $fixedAsset->warehouse?->name ?: 'Not recorded' }}</p></div>
        <div><p class="text-xs text-slate-400">Custodian</p><p class="mt-1 text-sm text-slate-100">{{ $fixedAsset->custodian?->name ?? 'Stores Person' }}</p></div>
        <div><p class="text-xs text-slate-400">Acquired</p><p class="mt-1 text-sm text-slate-100">{{ $fixedAsset->acquired_date->format('d M Y') }}</p></div>
        <div><p class="text-xs text-slate-400">Purchase cost</p><p class="mt-1 text-sm text-slate-100">{{ number_format((float) $fixedAsset->purchase_cost, 2) }}</p></div>
        <div><p class="text-xs text-slate-400">Capitalized cost</p><p class="mt-1 text-sm text-slate-100">{{ number_format((float) $fixedAsset->capitalized_cost, 2) }}</p></div>
        <div><p class="text-xs text-slate-400">Fixed-asset account</p><p class="mt-1 text-sm text-slate-100">{{ $fixedAsset->assetAccount ? $fixedAsset->assetAccount->code.' · '.$fixedAsset->assetAccount->name : 'Not capitalized' }}</p></div>
        <div><p class="text-xs text-slate-400">Asset note</p><p class="mt-1 text-sm text-slate-100">{{ $fixedAsset->notes ?: 'None' }}</p></div>
        <div><p class="text-xs text-slate-400">Goods received voucher</p><p class="mt-1 text-sm text-slate-100">{{ $fixedAsset->goodsReceivedItem->voucher->voucher_number }}</p></div>
        <div><p class="text-xs text-slate-400">Supplier invoice</p><p class="mt-1 text-sm text-slate-100">{{ $fixedAsset->supplierInvoiceItem?->supplierInvoice?->invoice_number ?? 'Not invoiced yet' }}</p></div>
    </section>

    @can('inventory.assets.manage')
        @if($fixedAsset->status !== 'disposed')
            <form method="POST" action="{{ route('admin.inventory.fixed-assets.custody', $fixedAsset) }}" class="space-y-4 rounded-2xl border border-cyan-800/70 bg-slate-900/80 p-5">
                @csrf
                @method('PUT')
                <div>
                    <h2 class="font-semibold text-slate-100">Custody and asset details</h2>
                    @if(! $fixedAsset->is_capitalized)
                        <p class="mt-1 text-sm text-slate-400">Assigning a low-value asset removes it from stock and posts its cost to the category expense account. Returning it reverses that movement.</p>
                    @endif
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="custodian_user_id" class="mb-1 block text-xs text-slate-300">Custodian</label>
                        <select id="custodian_user_id" name="custodian_user_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                            <option value="">In store / no staff custodian</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('custodian_user_id', $fixedAsset->custodian_user_id) == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('custodian_user_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="serial_number" class="mb-1 block text-xs text-slate-300">Serial number</label>
                        <input id="serial_number" name="serial_number" maxlength="100" value="{{ old('serial_number', $fixedAsset->serial_number) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        @error('serial_number')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="location" class="mb-1 block text-xs text-slate-300">Location</label>
                        <input id="location" name="location" required maxlength="255" value="{{ old('location', $fixedAsset->location ?: $fixedAsset->warehouse?->name) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        @error('location')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="notes" class="mb-1 block text-xs text-slate-300">Asset / custody note</label>
                    <textarea id="notes" name="notes" maxlength="2000" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">{{ old('notes', $fixedAsset->notes) }}</textarea>
                    @error('asset')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Save asset custody</button>
            </form>
        @endif
    @endcan

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <h2 class="font-semibold text-slate-100">Custody history</h2>
        <div class="mt-3 space-y-3">
            @forelse($fixedAsset->assignments as $assignment)
                <div class="rounded-lg border border-slate-700 bg-slate-950/50 p-3">
                    <p class="text-sm font-semibold text-slate-200">
                        {{ ucfirst($assignment->action) }} by {{ $assignment->assignedBy->name }}
                        <span class="font-normal text-slate-400">· {{ $assignment->occurred_at->format('d M Y H:i') }}</span>
                    </p>
                    <p class="mt-1 text-sm text-slate-300">
                        {{ $assignment->fromUser?->name ?? 'Stores' }} → {{ $assignment->toUser?->name ?? 'Stores' }}
                        · {{ $assignment->from_location ?: '-' }} → {{ $assignment->to_location ?: '-' }}
                    </p>
                    @if($assignment->notes)<p class="mt-1 text-sm text-slate-400">{{ $assignment->notes }}</p>@endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No custody changes have been recorded.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
