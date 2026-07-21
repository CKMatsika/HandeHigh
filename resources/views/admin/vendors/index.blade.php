@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Vendors</h1>
            <p class="text-xs text-slate-400 mt-1">Suppliers and service providers.</p>
        </div>
        <a href="{{ route('admin.vendors.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Add Vendor</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Code</th>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Phone</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($vendors as $vendor)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $vendor->code }}</td>
                            <td class="px-4 py-3 font-medium">{{ $vendor->name }}</td>
                            <td class="px-4 py-3">{{ $vendor->email ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $vendor->phone ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs {{ $vendor->is_active ? 'bg-emerald-500/20 text-emerald-200' : 'bg-slate-700 text-slate-300' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.vendors.edit', $vendor) }}" class="text-indigo-400 hover:text-indigo-300">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No vendors found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $vendors->links() }}
        </div>
    </div>
@endsection

