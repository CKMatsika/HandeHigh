@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            @if($user->profile_photo)
                <img src="{{ Storage::url($user->profile_photo) }}" alt="{{ $user->name }}" class="w-14 h-14 rounded-full object-cover border border-slate-700">
            @else
                <div class="w-14 h-14 rounded-full bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 font-bold text-lg">
                    {{ substr($user->name, 0, 2) }}
                </div>
            @endif
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold tracking-tight text-slate-50">{{ $user->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">{{ $user->email }} &bull; {{ $user->phone ?? 'No phone' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.user-management.edit', $user) }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm transition font-medium">
                Edit User
            </a>
            <a href="{{ route('admin.user-management.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-2 rounded-lg text-sm transition">
                &larr; Back to Users
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Personal & Contact Information -->
        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5 space-y-4 md:col-span-2">
            <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Profile Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block mb-1">Full Name</span>
                    <span class="text-slate-200 font-medium text-sm">{{ $user->name }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Email Address</span>
                    <span class="text-slate-200 font-medium text-sm">{{ $user->email }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Phone Number</span>
                    <span class="text-slate-200 font-medium text-sm">{{ $user->phone ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Gender</span>
                    <span class="text-slate-200 font-medium text-sm capitalize">{{ $user->gender ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Date of Birth</span>
                    <span class="text-slate-200 font-medium text-sm">{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('M d, Y') : '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Address</span>
                    <span class="text-slate-200 font-medium text-sm">{{ $user->address ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Emergency Contact</span>
                    <span class="text-slate-200 font-medium text-sm">{{ $user->emergency_contact ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Emergency Phone</span>
                    <span class="text-slate-200 font-medium text-sm">{{ $user->emergency_phone ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Roles & Permissions -->
        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5 space-y-4">
            <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Access Control</h3>
            
            <div>
                <span class="text-xs text-slate-400 block mb-2">Assigned Roles</span>
                <div class="flex flex-wrap gap-1.5">
                    @forelse($user->roles as $role)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            {{ ucfirst($role->name) }}
                        </span>
                    @empty
                        <span class="text-xs text-slate-500">No roles assigned</span>
                    @endforelse
                </div>
            </div>

            <div class="pt-2">
                <span class="text-xs text-slate-400 block mb-2">Direct Permissions</span>
                <div class="flex flex-wrap gap-1.5 max-h-48 overflow-y-auto">
                    @forelse($user->permissions as $permission)
                        <span class="px-2 py-0.5 rounded text-xs bg-slate-800 text-slate-300 border border-slate-700">
                            {{ str_replace('_', ' ', $permission->name) }}
                        </span>
                    @empty
                        <span class="text-xs text-slate-500">No direct permissions assigned</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
