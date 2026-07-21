@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Chart of Accounts</h1>
            <p class="text-xs text-slate-400 mt-1">Manage accounts for double-entry posting.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.accounts.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Add Account</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 mb-3">
        <form method="GET" class="flex items-center gap-3 text-xs">
            <select name="type" class="rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Types</option>
                @foreach(['asset','liability','equity','revenue','expense'] as $t)
                    <option value="{{ $t }}" @selected($type === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            <button class="rounded-full bg-indigo-500 px-4 py-2 font-medium text-white hover:bg-indigo-600 transition" type="submit">Filter</button>
            <a href="{{ route('admin.accounts.index') }}" class="text-slate-300 hover:text-white text-xs">Reset</a>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Code</th>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Parent</th>
                        <th class="px-4 py-3 text-left font-medium">Active</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $account->code }}</td>
                            <td class="px-4 py-3 font-medium">{{ $account->name }}</td>
                            <td class="px-4 py-3 capitalize">{{ $account->type }}</td>
                            <td class="px-4 py-3">{{ $account->parent?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full {{ $account->is_active ? 'bg-emerald-500/20 text-emerald-200' : 'bg-slate-700 text-slate-300' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 text-xs">
                                    <a href="{{ route('admin.accounts.edit', $account) }}" class="text-indigo-400 hover:text-indigo-300">Edit</a>
                                    <form action="{{ route('admin.accounts.toggle', $account) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="text-amber-400 hover:text-amber-300">
                                            {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $accounts->links() }}
        </div>
    </div>
@endsection

