@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">{{ $hostel->name }}</h1>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $hostel->gender === 'female' ? 'bg-pink-500/10 text-pink-400 border border-pink-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20' }}">
                    {{ $hostel->gender === 'female' ? "Girls' Hostel" : "Boys' Hostel" }}
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">{{ $hostel->description ?? 'No description provided.' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dormitories.create') }}?hostel_id={{ $hostel->id }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm transition font-medium">
                + Add Dormitory
            </a>
            <a href="{{ route('admin.hostels.edit', $hostel) }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-2 rounded-lg text-sm transition">
                Edit Facility
            </a>
            <a href="{{ route('admin.hostels.index') }}" class="text-slate-400 hover:text-slate-200 text-sm">
                &larr; Back
            </a>
        </div>
    </div>

    <!-- Supervisor & Facility Stats Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">Designated Supervisor</span>
            @if($hostel->supervisor)
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-blue-500/20 border border-blue-500/30 flex items-center justify-center font-semibold text-blue-400">
                        {{ substr($hostel->supervisor->first_name, 0, 1) }}{{ substr($hostel->supervisor->last_name, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-100">{{ $hostel->supervisor->full_name }}</h4>
                        <p class="text-xs text-slate-400">{{ $hostel->supervisor->position }}</p>
                    </div>
                </div>
            @else
                <p class="text-xs text-amber-400 font-medium">No supervisor currently assigned. Girls' hostels require a Matron; boys' hostels require a Boarding Master.</p>
            @endif
        </div>

        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">Dormitories Under Hostel</span>
            <span class="text-2xl font-bold text-slate-100">{{ $hostel->dormitories->count() }}</span>
            <span class="text-xs text-slate-400 block mt-1">Independent Dormitory Units</span>
        </div>

        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">Capacity & Beds</span>
            <span class="text-2xl font-bold text-slate-100">{{ $hostel->total_capacity }}</span>
            <span class="text-xs text-slate-400 block mt-1">Total Residential Capacity</span>
        </div>
    </div>

    <!-- Dormitories List -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 overflow-hidden">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-100">Dormitories</h3>
            <span class="text-xs text-slate-400">{{ $hostel->dormitories->count() }} units</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-xs">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Dormitory Name</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Gender</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Supervisor / Matron</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Capacity</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Beds Configured</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($hostel->dormitories as $dormitory)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-semibold text-slate-200">{{ $dormitory->name }}</td>
                            <td class="px-4 py-3 capitalize text-slate-300">{{ $dormitory->gender }}</td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ $dormitory->supervisor?->full_name ?? ($hostel->supervisor?->full_name ?? '—') }}
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $dormitory->capacity }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $dormitory->beds->count() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.dormitories.show', $dormitory) }}" class="text-blue-400 hover:text-blue-300 font-medium">
                                    Manage Dormitory &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                No dormitories created under this hostel yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
