@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ict.dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; ICT Desk</a>
                <span class="text-slate-600">/</span>
                <h1 class="text-xl font-bold tracking-tight text-slate-50">Computer Lab & Device Readiness</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1">Track hardware health, workstation uptime, and equipment readiness across all school computer labs.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('addDeviceModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-emerald-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                + Register Device / PC
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" action="{{ route('ict.devices.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div>
                <label class="block font-medium text-slate-400 mb-1">Filter by Location</label>
                <select name="location" onchange="this.form.submit()"
                        class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-200 focus:border-cyan-500 focus:outline-none">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc }}" {{ request('location') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium text-slate-400 mb-1">Filter by Condition</label>
                <select name="condition" onchange="this.form.submit()"
                        class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-200 focus:border-cyan-500 focus:outline-none">
                    <option value="">All Conditions</option>
                    <option value="working" {{ request('condition') === 'working' ? 'selected' : '' }}>Working</option>
                    <option value="needs_attention" {{ request('condition') === 'needs_attention' ? 'selected' : '' }}>Needs Attention</option>
                    <option value="faulty" {{ request('condition') === 'faulty' ? 'selected' : '' }}>Faulty</option>
                    <option value="under_repair" {{ request('condition') === 'under_repair' ? 'selected' : '' }}>Under Repair</option>
                </select>
            </div>
            <div class="flex items-end">
                <a href="{{ route('ict.devices.index') }}" class="w-full text-center py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Devices Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Device / Asset</th>
                        <th class="py-3 px-4">Location</th>
                        <th class="py-3 px-4">Condition Status</th>
                        <th class="py-3 px-4">Quick Condition Update</th>
                        <th class="py-3 px-4">Last Inspected</th>
                        <th class="py-3 px-4">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($devices as $device)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-100 block">{{ $device->device_name }}</span>
                                @if($device->device_code)
                                    <span class="font-mono text-[10px] text-cyan-400">{{ $device->device_code }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-200">
                                {{ $device->location }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $device->condition_badge_class }}">
                                    {{ ucfirst(str_replace('_', ' ', $device->condition_status)) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <!-- Inline 1-Click Condition Changer -->
                                <form method="POST" action="{{ route('ict.devices.update-condition', $device) }}" class="flex items-center gap-1.5">
                                    @csrf
                                    @method('PUT')
                                    <select name="condition_status" onchange="this.form.submit()"
                                            class="rounded-lg bg-slate-950 border border-slate-700 py-1 px-2 text-[11px] text-slate-200 focus:border-cyan-500 focus:outline-none">
                                        <option value="working" {{ $device->condition_status === 'working' ? 'selected' : '' }}>Working</option>
                                        <option value="needs_attention" {{ $device->condition_status === 'needs_attention' ? 'selected' : '' }}>Needs Attention</option>
                                        <option value="faulty" {{ $device->condition_status === 'faulty' ? 'selected' : '' }}>Faulty</option>
                                        <option value="under_repair" {{ $device->condition_status === 'under_repair' ? 'selected' : '' }}>Under Repair</option>
                                    </select>
                                </form>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-400 text-[11px]">
                                {{ $device->last_checked_at ? $device->last_checked_at->format('M d, Y') : 'Not yet' }}
                                @if($device->inspector)
                                    <span class="block text-[10px] text-slate-500">by {{ $device->inspector->name }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 max-w-xs text-slate-400 text-[11px] truncate">
                                {{ $device->notes ?? '&mdash;' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400">
                                No devices or computers registered in this location.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($devices->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $devices->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Add Device Modal -->
    <div id="addDeviceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-slate-100">Register Computer Lab Item / Device</h3>
                <button onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('ict.devices.store') }}" class="mt-4 space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-medium text-slate-300 mb-1">Device Name *</label>
                    <input type="text" name="device_name" required placeholder="e.g. Student Workstation PC #16"
                           class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-100 focus:border-cyan-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-slate-300 mb-1">Asset / Device Code</label>
                        <input type="text" name="device_code" placeholder="e.g. LAB1-PC16"
                               class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-100 focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-300 mb-1">Location / Room *</label>
                        <input type="text" name="location" required list="locationList" placeholder="e.g. Computer Lab 1"
                               class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-100 focus:border-cyan-500 focus:outline-none">
                        <datalist id="locationList">
                            <option value="Computer Lab 1"></option>
                            <option value="Computer Lab 2"></option>
                            <option value="Staff Room"></option>
                            <option value="Administration Office"></option>
                            <option value="Library"></option>
                        </datalist>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-300 mb-1">Condition Status *</label>
                    <select name="condition_status" required
                            class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-100 focus:border-cyan-500 focus:outline-none">
                        <option value="working" selected>Working (Ready for classes)</option>
                        <option value="needs_attention">Needs Attention (Minor issue / slow / software update)</option>
                        <option value="faulty">Faulty (Not booting / broken peripheral)</option>
                        <option value="under_repair">Under Repair (Sent for technician servicing)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-300 mb-1">Notes / Specifications</label>
                    <textarea name="notes" rows="3" placeholder="e.g. Intel Core i5, 8GB RAM, Windows 10, connected to Lab LAN"
                              class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-100 focus:border-cyan-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')"
                            class="px-3.5 py-2 text-xs rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold">
                        Save Device
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
