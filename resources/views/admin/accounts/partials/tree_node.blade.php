@php
    $hasChildren = $account->children && $account->children->isNotEmpty();
    $typeBadge = match($account->type) {
        'asset' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
        'liability' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'equity' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        'revenue' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'expense' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        default => 'bg-slate-800 text-slate-300 border-slate-700',
    };
@endphp

<div class="tree-node hover:bg-slate-800/40 transition group {{ ! $account->is_active ? 'opacity-60' : '' }}" data-account-id="{{ $account->id }}">
    <div class="px-4 py-2.5 grid grid-cols-12 items-center text-xs">
        <!-- Account Code & Name with indentation -->
        <div class="col-span-6 flex items-center gap-2" style="padding-left: {{ $depth * 24 }}px;">
            @if ($hasChildren)
                <button type="button" onclick="toggleTreeNode(this, 'child-group-{{ $account->id }}')" class="w-4 h-4 rounded flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700/60 transition">
                    <svg class="w-3.5 h-3.5 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            @else
                <div class="w-4 h-4 flex items-center justify-center text-slate-700">
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-700"></div>
                </div>
            @endif

            <span class="font-mono font-bold text-slate-100 bg-slate-800/80 px-2 py-0.5 rounded text-[11px] border border-slate-700/60 shadow-sm">
                {{ $account->code }}
            </span>

            <span class="font-medium text-slate-200 {{ ! $account->is_postable ? 'font-bold text-slate-100 uppercase tracking-wide' : '' }}">
                {{ $account->name }}
            </span>

            @if (! $account->is_postable)
                <span class="text-[9px] uppercase tracking-wider font-semibold px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-300 border border-amber-500/20">Header</span>
            @endif

            @if (! $account->is_active)
                <span class="text-[9px] uppercase tracking-wider font-semibold px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">Deactivated</span>
            @endif
        </div>

        <!-- Type & Category -->
        <div class="col-span-2 flex items-center gap-1.5">
            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $typeBadge }}">
                {{ ucfirst($account->type) }}
            </span>
            <span class="text-[10px] text-slate-500 truncate" title="{{ $account->category }}">{{ $account->category }}</span>
        </div>

        <!-- Balance -->
        <div class="col-span-2 text-right font-mono">
            @if ($hasChildren)
                <div class="text-slate-200 font-semibold">${{ $account->formatted_tree_balance }}</div>
                <div class="text-[10px] text-slate-500 font-normal">Direct: ${{ $account->formatted_balance }}</div>
            @else
                <div class="text-slate-200">${{ $account->formatted_balance }}</div>
            @endif
        </div>

        <!-- Actions -->
        <div class="col-span-2 text-right">
            <div class="flex items-center justify-end gap-1 opacity-80 group-hover:opacity-100 transition">
                <a href="{{ route('admin.accounts.create', ['parent_id' => $account->id]) }}" title="Add Subaccount under {{ $account->code }}" class="p-1 rounded-lg text-slate-400 hover:text-indigo-400 hover:bg-slate-800 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </a>
                <a href="{{ route('admin.accounts.edit', $account) }}" title="Edit Account" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <form method="POST" action="{{ route('admin.accounts.toggle', $account) }}" class="inline">
                    @csrf
                    @method('PUT')
                    <button type="submit" title="{{ $account->is_active ? 'Deactivate' : 'Activate' }}" class="p-1 rounded-lg text-slate-400 hover:text-amber-400 hover:bg-slate-800 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </button>
                </form>
                @if ($account->canBeDeleted())
                    <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" class="inline" onsubmit="return confirm('Delete account {{ $account->code }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Delete Account" class="p-1 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($hasChildren)
    <div id="child-group-{{ $account->id }}" class="child-group">
        @foreach ($account->children as $child)
            @include('admin.accounts.partials.tree_node', ['account' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
@endif

<script>
    if (typeof toggleTreeNode !== 'function') {
        function toggleTreeNode(btn, groupId) {
            const group = document.getElementById(groupId);
            if (!group) return;
            const svg = btn.querySelector('svg');
            if (group.style.display === 'none') {
                group.style.display = 'block';
                if (svg) svg.classList.remove('-rotate-90');
            } else {
                group.style.display = 'none';
                if (svg) svg.classList.add('-rotate-90');
            }
        }
    }
</script>
