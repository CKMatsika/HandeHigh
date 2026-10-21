@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.inventory.fixed-assets.index') }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">&larr; Fixed Asset Register</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-50">Asset Depreciation Schedules</h1>
            <p class="mt-1 text-xs text-slate-400">Manage straight-line and reducing-balance depreciation, period closing, and automated GL postings.</p>
        </div>
        <div>
            <button onclick="document.getElementById('runDepreciationModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-xs font-semibold text-white shadow-lg shadow-emerald-900/30 hover:from-emerald-500 hover:to-teal-500 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Run Monthly Depreciation
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-medium text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Portfolio Financial Summary -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Capitalized Cost Basis</span>
            <p class="mt-1 text-xl font-bold text-slate-100">${{ number_format((float) $totalCapitalizedCost, 2) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">{{ $eligibleAssets->count() }} depreciable assets</p>
        </div>
        <div class="rounded-2xl border border-amber-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-amber-400">Accumulated Depreciation</span>
            <p class="mt-1 text-xl font-bold text-amber-300">${{ number_format((float) $totalAccumulatedDepr, 2) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Total booked write-down</p>
        </div>
        <div class="rounded-2xl border border-emerald-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-emerald-400">Net Book Value (NBV)</span>
            <p class="mt-1 text-xl font-bold text-emerald-300">${{ number_format((float) $totalNetBookValue, 2) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Carrying asset valuation</p>
        </div>
        <div class="rounded-2xl border border-cyan-900/40 bg-slate-900/80 p-4">
            <span class="text-[11px] font-medium uppercase tracking-wider text-cyan-400">Monthly Run Preview</span>
            @php
                $monthlyRunEst = $eligibleAssets->sum(function($a) {
                    return app(\App\Services\SchoolFixedAssetService::class)->calculateDepreciationAmount($a);
                });
            @endphp
            <p class="mt-1 text-xl font-bold text-cyan-300">${{ number_format($monthlyRunEst, 2) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Est. per period amortisation</p>
        </div>
    </div>

    <!-- Active Capitalized Assets Amortisation Preview -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-100">Depreciable Assets Portfolio</h2>
                <p class="text-xs text-slate-400">Assets currently eligible for monthly depreciation under straight-line or reducing balance.</p>
            </div>
            <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs text-slate-400 font-mono">{{ $eligibleAssets->count() }} active assets</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-3 py-2.5">Tag</th>
                        <th class="px-3 py-2.5">Asset / Item</th>
                        <th class="px-3 py-2.5">Method</th>
                        <th class="px-3 py-2.5">Useful Life</th>
                        <th class="px-3 py-2.5 text-right">Cost</th>
                        <th class="px-3 py-2.5 text-right">Salvage</th>
                        <th class="px-3 py-2.5 text-right">Accumulated</th>
                        <th class="px-3 py-2.5 text-right">Net Book Value</th>
                        <th class="px-3 py-2.5 text-right">Monthly Depr.</th>
                        <th class="px-3 py-2.5">Last Depr. Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($eligibleAssets as $asset)
                        @php
                            $previewAmt = app(\App\Services\SchoolFixedAssetService::class)->calculateDepreciationAmount($asset);
                        @endphp
                        <tr class="text-slate-300 hover:bg-slate-800/40">
                            <td class="px-3 py-2.5 font-mono text-emerald-400 font-semibold">
                                <a href="{{ route('admin.inventory.fixed-assets.show', $asset) }}" class="hover:underline">
                                    {{ $asset->asset_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2.5 text-slate-100">{{ $asset->item->name }}</td>
                            <td class="px-3 py-2.5 capitalize text-slate-400">{{ str_replace('_', ' ', $asset->depreciation_method ?: 'straight_line') }}</td>
                            <td class="px-3 py-2.5">{{ $asset->useful_life_years }} yrs</td>
                            <td class="px-3 py-2.5 text-right font-medium">${{ number_format((float) $asset->capitalized_cost, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-slate-400">${{ number_format((float) $asset->salvage_value, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-amber-300 font-mono">${{ number_format((float) $asset->accumulated_depreciation, 2) }}</td>
                            <td class="px-3 py-2.5 text-right font-semibold text-emerald-400 font-mono">${{ number_format((float) $asset->net_book_value, 2) }}</td>
                            <td class="px-3 py-2.5 text-right font-bold text-cyan-300 font-mono">${{ number_format($previewAmt, 2) }}</td>
                            <td class="px-3 py-2.5 text-slate-400">{{ $asset->last_depreciation_date ? $asset->last_depreciation_date->format('d M Y') : 'Never' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-6 text-center text-slate-500">No active capitalized assets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Depreciation Posting History -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-100">Depreciation Journal Batches & History</h2>
                <p class="text-xs text-slate-400">Audit trail of all executed depreciation runs with general ledger batch cross-references.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-3 py-2.5">Date</th>
                        <th class="px-3 py-2.5">Period Name</th>
                        <th class="px-3 py-2.5">Asset</th>
                        <th class="px-3 py-2.5">Method</th>
                        <th class="px-3 py-2.5 text-right">Amortised Amount</th>
                        <th class="px-3 py-2.5 text-right">Accumulated After</th>
                        <th class="px-3 py-2.5 text-right">NBV After</th>
                        <th class="px-3 py-2.5">GL Batch</th>
                        <th class="px-3 py-2.5">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($depreciations as $entry)
                        <tr class="text-slate-300 hover:bg-slate-800/40">
                            <td class="px-3 py-2.5">{{ $entry->depreciation_date->format('d M Y') }}</td>
                            <td class="px-3 py-2.5 font-medium text-slate-100">{{ $entry->period_name }}</td>
                            <td class="px-3 py-2.5 font-mono text-emerald-400">
                                <a href="{{ route('admin.inventory.fixed-assets.show', $entry->asset) }}" class="hover:underline">
                                    {{ $entry->asset->asset_number }}
                                </a>
                                <span class="text-slate-400 font-sans">({{ $entry->asset->item->name }})</span>
                            </td>
                            <td class="px-3 py-2.5 capitalize text-slate-400">{{ str_replace('_', ' ', $entry->method) }}</td>
                            <td class="px-3 py-2.5 text-right font-bold text-amber-300 font-mono">${{ number_format((float) $entry->depreciation_amount, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-slate-300 font-mono">${{ number_format((float) $entry->accumulated_depreciation_after, 2) }}</td>
                            <td class="px-3 py-2.5 text-right font-semibold text-emerald-400 font-mono">${{ number_format((float) $entry->book_value_after, 2) }}</td>
                            <td class="px-3 py-2.5 font-mono text-[11px]">
                                @if($entry->journalBatch)
                                    <span class="rounded bg-indigo-500/20 px-2 py-0.5 text-indigo-300 border border-indigo-500/30">
                                        {{ $entry->journalBatch->batch_number }}
                                    </span>
                                @else
                                    <span class="text-slate-600">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-400">{{ $entry->recorder?->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-8 text-center text-slate-500">No depreciation entries recorded yet. Use the button above to run your first batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($depreciations->hasPages())
            <div class="border-t border-slate-800 pt-3">{{ $depreciations->links() }}</div>
        @endif
    </div>
</div>

<!-- Run Depreciation Modal -->
<div id="runDepreciationModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
    <div class="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-slate-100">Run Monthly Depreciation Batch</h3>
            <button onclick="document.getElementById('runDepreciationModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200 text-lg">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.inventory.fixed-assets.depreciation.run') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="period_name" class="block text-xs font-medium text-slate-300">Period Name</label>
                <input id="period_name" name="period_name" type="text" required value="{{ now()->format('F Y') }}"
                       class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100 focus:border-emerald-500 focus:outline-none">
                <p class="text-[10px] text-slate-500 mt-0.5">e.g. October 2026, 2026 Term 1 Amortisation</p>
            </div>
            <div>
                <label for="depreciation_date" class="block text-xs font-medium text-slate-300">Posting Date</label>
                <input id="depreciation_date" name="depreciation_date" type="date" required value="{{ now()->toDateString() }}"
                       class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100 focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3 text-xs text-slate-400 space-y-1">
                <p class="font-medium text-slate-200">Execution Summary:</p>
                <p>&bull; Will process all {{ $eligibleAssets->count() }} active capitalized assets.</p>
                <p>&bull; Calculates straight-line or reducing-balance per asset parameters.</p>
                <p>&bull; Posts balanced general ledger journal batch debiting Depreciation Expense and crediting Accumulated Depreciation.</p>
                <p>&bull; Capped strictly at salvage value so assets are never over-depreciated.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('runDepreciationModal').classList.add('hidden')"
                        class="rounded-xl border border-slate-700 px-4 py-2 text-xs font-medium text-slate-300 hover:bg-slate-800">Cancel</button>
                <button type="submit"
                        class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-xs font-semibold text-white hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-900/30">
                    Post Depreciation Batch
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
