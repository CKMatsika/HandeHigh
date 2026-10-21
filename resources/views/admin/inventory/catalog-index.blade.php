@extends('layouts.app')

@section('content')
@php
    $titles = ['categories' => 'Inventory Categories', 'items' => 'Inventory Items', 'warehouses' => 'Warehouses'];
    $createRoutes = [
        'categories' => 'admin.inventory.categories.create',
        'items' => 'admin.inventory.items.create',
        'warehouses' => 'admin.inventory.warehouses.create',
    ];
    $addLabels = ['categories' => 'Category', 'items' => 'Item', 'warehouses' => 'Warehouse'];
@endphp
<div class="space-y-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <a href="{{ route('admin.inventory.index') }}" class="text-xs text-emerald-300 hover:text-emerald-200">&larr; Stores dashboard</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-50">{{ $titles[$kind] }}</h1>
        </div>
        @can($kind === 'warehouses' ? 'inventory.warehouses.manage' : 'inventory.catalog.manage')
            <a href="{{ route($createRoutes[$kind]) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                Add {{ $addLabels[$kind] }}
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                    @if($kind === 'categories')
                        <tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Items</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr>
                    @elseif($kind === 'items')
                        <tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Unit</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr>
                    @else
                        <tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Location</th><th class="px-4 py-3">Custodian</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr>
                    @endif
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($records as $record)
                        <tr class="text-slate-300">
                            <td class="px-4 py-3 font-mono">{{ $record->code }}</td>
                            <td class="px-4 py-3 font-medium text-slate-100">{{ $record->name }}</td>
                            @if($kind === 'categories')
                                <td class="px-4 py-3">{{ $record->items_count }}</td>
                            @elseif($kind === 'items')
                                <td class="px-4 py-3">{{ $record->category->name }}</td>
                                <td class="px-4 py-3">{{ str_replace('_', ' ', ucfirst($record->item_type)) }}</td>
                                <td class="px-4 py-3">{{ $record->unit_of_measure }}</td>
                            @else
                                <td class="px-4 py-3">{{ $record->location ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $record->manager?->name ?: '-' }}</td>
                            @endif
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs {{ $record->is_active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-700 text-slate-300' }}">
                                    {{ $record->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    @if($kind === 'items')
                                        <a href="{{ route('admin.inventory.items.bincard', $record) }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">Bin Card</a>
                                    @endif
                                    @if($kind === 'categories' && auth()->user()->can('accounts.edit'))
                                        <a href="{{ route('admin.inventory.' . $kind . '.edit', $record) }}" class="text-emerald-300 hover:text-emerald-200">Edit</a>
                                    @else
                                    @can($kind === 'warehouses' ? 'inventory.warehouses.manage' : 'inventory.catalog.manage')
                                        <a href="{{ route('admin.inventory.' . $kind . '.edit', $record) }}" class="text-emerald-300 hover:text-emerald-200">Edit</a>
                                    @else
                                        @if($kind !== 'items')
                                            <span class="text-slate-600">-</span>
                                        @endif
                                    @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $kind === 'items' ? 7 : ($kind === 'categories' ? 5 : 6) }}" class="px-4 py-8 text-center text-slate-500">No records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-800 px-4 py-3">{{ $records->links() }}</div>
    </div>
</div>
@endsection
