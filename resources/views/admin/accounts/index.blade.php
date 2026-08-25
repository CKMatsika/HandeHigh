@extends('layouts.app')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-50 flex items-center gap-2">
                <span>Chart of Accounts</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Sage / QuickBooks Style</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">Multi-level hierarchical general ledger chart of accounts with posting safeguards, tenant isolation, and automated revenue routing.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-xl bg-slate-900 border border-slate-800 p-1">
                <a href="{{ route('admin.accounts.index', array_merge(request()->query(), ['view' => 'tree'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $viewMode === 'tree' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                    Tree View
                </a>
                <a href="{{ route('admin.accounts.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $viewMode === 'table' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                    Table View
                </a>
            </div>
            <a href="{{ route('admin.accounts.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-500 shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Account</span>
            </a>
        </div>
    </div>

    <!-- Overview Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-3.5 backdrop-blur">
            <span class="text-[11px] font-medium text-slate-400">Total Accounts</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-slate-100">{{ $statistics['total_accounts'] }}</span>
                <span class="text-[10px] text-slate-500 font-mono">GL</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-3.5 backdrop-blur">
            <span class="text-[11px] font-medium text-slate-400">Postable Detail</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-emerald-400">{{ $statistics['postable_accounts'] }}</span>
                <span class="text-[10px] text-emerald-500/70">Entries OK</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-3.5 backdrop-blur">
            <span class="text-[11px] font-medium text-slate-400">Header Classes</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-amber-400">{{ $statistics['header_accounts'] }}</span>
                <span class="text-[10px] text-amber-500/70">Summary</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-3.5 backdrop-blur">
            <span class="text-[11px] font-medium text-slate-400">Active / Deactive</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-sky-400">{{ $statistics['active_accounts'] }}</span>
                <span class="text-[10px] text-slate-500">/ {{ $statistics['inactive_accounts'] }}</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-3.5 backdrop-blur">
            <span class="text-[11px] font-medium text-slate-400">Revenue Accounts</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-emerald-300">{{ $statistics['by_type']['revenue'] }}</span>
                <span class="text-[10px] text-slate-500">5000s</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-3.5 backdrop-blur">
            <span class="text-[11px] font-medium text-slate-400">Expense Accounts</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl font-bold text-rose-300">{{ $statistics['by_type']['expense'] }}</span>
                <span class="text-[10px] text-slate-500">6000s</span>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 mb-6">
        <form method="GET" action="{{ route('admin.accounts.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}" />
            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Account Classification</label>
                <select name="type" class="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Types (Assets, Liabilities, Equity, Revenue, Expense)</option>
                    @foreach(['asset' => 'Asset (1000s)', 'liability' => 'Liability (2000-3000s)', 'equity' => 'Equity (4000s)', 'revenue' => 'Revenue (5000s)', 'expense' => 'Expense (6000s)'] as $k => $label)
                        <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Posting Status</label>
                <select name="is_postable" class="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All (Headers & Postable Detail)</option>
                    <option value="1" @selected($isPostable === '1')>Postable Detail Accounts Only</option>
                    <option value="0" @selected($isPostable === '0')>Header / Summary Accounts Only</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Active State</label>
                <select name="is_active" class="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Accounts</option>
                    <option value="1" @selected($isActive === '1')>Active Only</option>
                    <option value="0" @selected($isActive === '0')>Deactivated Only</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Search Code / Name</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="e.g. 5100, Tuition, Cash..." class="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full rounded-xl bg-slate-800 px-4 py-2 text-xs font-medium text-slate-200 hover:bg-slate-700 transition">Filter</button>
                @if ($type || $search || $isActive !== null || $isPostable !== null)
                    <a href="{{ route('admin.accounts.index', ['view' => $viewMode]) }}" class="rounded-xl border border-slate-800 px-3 py-2 text-xs text-slate-400 hover:text-slate-200 transition">Reset</a>
                @endif
            </div>
        </form>
    </div>

    @if ($viewMode === 'tree')
        <!-- Recursive Tree View -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 overflow-hidden shadow-xl">
            <div class="border-b border-slate-800 bg-slate-950/60 px-4 py-3 grid grid-cols-12 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                <div class="col-span-6">Account Code & Name</div>
                <div class="col-span-2">Type / Category</div>
                <div class="col-span-2 text-right">Balance (USD)</div>
                <div class="col-span-2 text-right">Actions</div>
            </div>

            @if ($tree->isEmpty())
                <div class="p-8 text-center text-xs text-slate-500">
                    No accounts found matching the specified filters.
                </div>
            @else
                <div class="divide-y divide-slate-800/60" id="accountTreeContainer">
                    @foreach ($tree as $rootAccount)
                        @include('admin.accounts.partials.tree_node', ['account' => $rootAccount, 'depth' => 0])
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <!-- Flat Table View -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/60 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Code</th>
                            <th class="py-3.5 px-4 font-semibold">Name</th>
                            <th class="py-3.5 px-4 font-semibold">Type</th>
                            <th class="py-3.5 px-4 font-semibold">Category</th>
                            <th class="py-3.5 px-4 font-semibold">Parent</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Posting</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Balance</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($accounts as $acc)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-200">{{ $acc->code }}</td>
                                <td class="py-3 px-4 text-slate-100 font-medium">{{ $acc->name }}</td>
                                <td class="py-3 px-4">
                                    @php
                                        $typeBadge = match($acc->type) {
                                            'asset' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
                                            'liability' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                            'equity' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                            'revenue' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                            'expense' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                            default => 'bg-slate-800 text-slate-300 border-slate-700',
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $typeBadge }}">
                                        {{ ucfirst($acc->type) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-400">{{ $acc->category }}</td>
                                <td class="py-3 px-4 text-slate-400 font-mono text-[11px]">{{ $acc->parent ? $acc->parent->code . ' - ' . $acc->parent->name : '—' }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if ($acc->is_postable)
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Postable</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] bg-amber-500/10 text-amber-300 border border-amber-500/20">Header</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if ($acc->is_active)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400">Active</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-rose-500/10 text-rose-400">Deactivated</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-200">
                                    ${{ $acc->formatted_balance }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.accounts.create', ['parent_id' => $acc->id]) }}" title="Add Subaccount" class="p-1 rounded-lg text-slate-400 hover:text-indigo-400 hover:bg-slate-800 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </a>
                                        <a href="{{ route('admin.accounts.edit', $acc) }}" title="Edit Account" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.accounts.toggle', $acc) }}" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" title="{{ $acc->is_active ? 'Deactivate' : 'Activate' }}" class="p-1 rounded-lg text-slate-400 hover:text-amber-400 hover:bg-slate-800 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        </form>
                                        @if ($acc->canBeDeleted())
                                            <form method="POST" action="{{ route('admin.accounts.destroy', $acc) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete account {{ $acc->code }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Delete Account" class="p-1 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-slate-500">No accounts registered for this school.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($accounts && $accounts->hasPages())
                <div class="border-t border-slate-800 px-4 py-3 bg-slate-950/40">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection
