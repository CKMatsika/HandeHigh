@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <a href="{{ route('admin.inventory.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Stores dashboard</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-50">Asset capitalization settings</h1>
        <p class="mt-1 text-sm text-slate-400">Finance controls the per-unit cost threshold used when receiving inventory items marked as assets.</p>
    </div>

    <form method="POST" action="{{ route('admin.inventory.settings.update') }}" class="space-y-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        @csrf
        @method('PUT')
        <div>
            <label for="asset_capitalization_threshold" class="mb-1 block text-xs font-medium text-slate-300">Capitalization threshold per unit</label>
            <input id="asset_capitalization_threshold" name="asset_capitalization_threshold" type="number" min="0" step="0.01" value="{{ old('asset_capitalization_threshold', $setting->asset_capitalization_threshold) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <p class="mt-1 text-xs text-slate-500">Leave blank to capitalize every item marked Asset. When set, a unit is capitalized if its received unit cost is at or above the threshold.</p>
            @error('asset_capitalization_threshold')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end border-t border-slate-800 pt-4">
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save threshold</button>
        </div>
    </form>
</div>
@endsection
