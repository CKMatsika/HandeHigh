@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Dormitories & Bed Allocation</h1>
            <p class="text-xs text-slate-400 mt-1">Manage dormitory buildings, bed inventories, and student boarding assignments</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.hostels.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-2 rounded-lg text-sm transition">
                Hostels Overview
            </a>
            <a href="{{ route('admin.dormitories.create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm transition font-medium">
                + New Dormitory
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-4">
        <form method="GET" action="{{ route('admin.dormitories.index') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">Filter by Hostel</label>
                <select name="hostel_id" class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs">
                    <option value="">All Hostels</option>
                    @foreach($hostels as $hostel)
                        <option value="{{ $hostel->id }}" {{ request('hostel_id') == $hostel->id ? 'selected' : '' }}>
                            {{ $hostel->name }} ({{ ucfirst($hostel->gender) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">Gender</label>
                <select name="gender" class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs">
                    <option value="">All Genders</option>
                    <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Female / Girls</option>
                    <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Male / Boys</option>
                </select>
            </div>

            <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-1.5 rounded-lg text-xs transition">
                Filter
            </button>
            <a href="{{ route('admin.dormitories.index') }}" class="text-xs text-slate-400 hover:text-slate-200 px-2 py-1.5">
                Reset
            </a>
        </form>
    </div>

    <!-- Dormitories Table -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-xs">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Dormitory</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Hostel</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Gender</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Supervisor / Matron</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Capacity</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Occupancy</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($dormitories as $dormitory)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-100 block">{{ $dormitory->name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $dormitory->description ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ $dormitory->hostel?->name ?? 'Independent Unit' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase {{ $dormitory->gender === 'female' ? 'bg-pink-500/10 text-pink-400' : 'bg-blue-500/10 text-blue-400' }}">
                                    {{ $dormitory->gender }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ $dormitory->supervisor?->full_name ?? ($dormitory->hostel?->supervisor?->full_name ?? '—') }}
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $dormitory->capacity }} Beds</td>
                            <td class="px-4 py-3">
                                @php
                                    $occupied = $dormitory->beds->filter(fn ($b) => $b->currentAssignment)->count();
                                    $totalBeds = $dormitory->beds->count();
                                @endphp
                                <span class="font-medium text-slate-200">{{ $occupied }} / {{ $totalBeds }}</span>
                                <span class="text-[11px] text-slate-500">({{ $totalBeds - $occupied }} free)</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.dormitories.show', $dormitory) }}" class="text-blue-400 hover:text-blue-300 font-medium">
                                    Manage Beds &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                No dormitories found matching current criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $dormitories->links() }}
    </div>
</div>
@endsection
