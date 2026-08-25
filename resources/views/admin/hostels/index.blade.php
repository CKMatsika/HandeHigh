@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Hostel & Boarding Management</h1>
            <p class="text-xs text-slate-400 mt-1">Manage boarding hostels, supervisory staff (Matrons & Boarding Masters), and dormitories</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dormitories.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-2 rounded-lg text-sm transition">
                View Dormitories
            </a>
            <a href="{{ route('admin.hostels.create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm transition font-medium">
                + New Hostel
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($hostels as $hostel)
            <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5 flex flex-col justify-between hover:border-slate-700 transition">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $hostel->gender === 'female' ? 'bg-pink-500/10 text-pink-400 border border-pink-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20' }}">
                            {{ $hostel->gender === 'female' ? "Girls' Hostel" : "Boys' Hostel" }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-xs {{ $hostel->is_active ? 'text-emerald-400' : 'text-slate-500' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $hostel->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                            {{ $hostel->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-100 mb-1">{{ $hostel->name }}</h3>
                    <p class="text-xs text-slate-400 mb-4">{{ $hostel->description ?? 'No description provided.' }}</p>

                    <div class="bg-slate-800/50 rounded-lg p-3 space-y-2 text-xs mb-4">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Supervisor:</span>
                            <span class="font-medium text-slate-200">
                                @if($hostel->supervisor)
                                    {{ $hostel->supervisor->full_name }} ({{ $hostel->supervisor->position }})
                                @else
                                    <span class="text-amber-400">Unassigned ({{ $hostel->gender === 'female' ? 'Matron' : 'Boarding Master' }} Required)</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Dormitories:</span>
                            <span class="font-medium text-slate-200">{{ $hostel->dormitories->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Total Capacity:</span>
                            <span class="font-medium text-slate-200">{{ $hostel->total_capacity }} Beds</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-800 text-xs">
                    <a href="{{ route('admin.hostels.show', $hostel) }}" class="text-blue-400 hover:text-blue-300 font-medium">
                        View Facility &rarr;
                    </a>
                    <a href="{{ route('admin.hostels.edit', $hostel) }}" class="text-slate-400 hover:text-slate-300">
                        Edit
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900/80 border border-slate-800 rounded-xl p-12 text-center">
                <p class="text-slate-400 text-sm">No hostels registered yet.</p>
                <a href="{{ route('admin.hostels.create') }}" class="inline-block mt-4 text-xs font-medium text-blue-400 hover:underline">
                    Create the first hostel
                </a>
            </div>
        @endforelse
    </div>

    <div>
        {{ $hostels->links() }}
    </div>
</div>
@endsection
