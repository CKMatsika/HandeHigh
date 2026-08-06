<!-- Assets Tab -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
    <h3 class="text-lg font-semibold text-slate-50 mb-6">School Assets Allocated</h3>

    <!-- Allocate Asset Form -->
    <form method="POST" action="{{ route('admin.students.allocate-asset', $student) }}" class="mb-6 bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Asset</label>
                <select name="school_asset_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="">Select asset...</option>
                    @foreach($schoolAssets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->name }} ({{ $asset->asset_code ?? 'N/A' }}) — {{ $asset->available_quantity }} available</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Quantity</label>
                <input type="number" name="quantity" value="1" min="1" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Condition at Issue</label>
                <select name="condition_at_issue" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="new">New</option>
                    <option value="good" selected>Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="text-xs text-slate-400 mb-1 block">Date</label>
                    <input type="date" name="allocated_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm transition">Allocate</button>
            </div>
        </div>
        <div class="mt-3">
            <label class="text-xs text-slate-400 mb-1 block">Notes (optional)</label>
            <input type="text" name="notes" placeholder="Optional notes" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
        </div>
    </form>

    <!-- Allocated Assets List -->
    <div class="space-y-2">
        @forelse($student->allocatedAssets as $asset)
            <div class="flex items-center justify-between bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700/50">
                <div>
                    <p class="text-slate-100 text-sm font-medium">{{ $asset->schoolAsset->name ?? 'Unknown Asset' }}</p>
                    <p class="text-slate-500 text-xs">
                        Qty: {{ $asset->quantity }} | Condition: {{ $asset->condition_at_issue ?? 'N/A' }} | Allocated: {{ $asset->allocated_date }}
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.students.return-asset', ['student' => $student, 'asset' => $asset]) }}" class="inline flex items-center gap-2">
                    @csrf @method('DELETE')
                    <select name="condition_at_return" class="rounded border border-slate-700 bg-slate-800 text-slate-100 px-2 py-1 text-xs">
                        <option value="good">Good</option>
                        <option value="new">New</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                        <option value="damaged">Damaged</option>
                        <option value="lost">Lost</option>
                    </select>
                    <button type="submit" class="text-red-400 hover:text-red-300 text-xs transition" onclick="return confirm('Mark as returned?')">Return</button>
                </form>
            </div>
        @empty
            <div class="text-center py-8 text-slate-500 text-sm">No assets allocated.</div>
        @endforelse
    </div>
</div>
