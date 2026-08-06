@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.departments.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-blue-500/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">{{ $department->name }}</h1>
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                    {{ $department->is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">
                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Head of Department</p>
            <p class="text-sm font-semibold text-slate-50 mt-1">{{ $department->head_of_department ?? 'Not assigned' }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Employees</p>
            <p class="text-sm font-semibold text-slate-50 mt-1">{{ $department->employees->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Budget</p>
            <p class="text-sm font-semibold text-slate-50 mt-1">{{ $department->budget ? '$' . number_format($department->budget, 2) : 'Not set' }}</p>
        </div>
    </div>

    <!-- Description -->
    @if($department->description)
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-50 mb-3">Description</h3>
        <p class="text-sm text-slate-300">{{ $department->description }}</p>
    </div>
    @endif

    <!-- Employees in this department -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-50 mb-4">Employees ({{ $department->employees->count() }})</h3>
        <div class="space-y-2">
            @forelse($department->employees as $emp)
                <div class="flex items-center justify-between bg-slate-800/50 rounded-lg px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center">
                            <span class="text-xs text-blue-400 font-bold">{{ substr($emp->first_name, 0, 1) }}{{ substr($emp->last_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <a href="{{ route('admin.employees.show', $emp) }}" class="text-sm font-medium text-slate-50 hover:text-blue-400 transition">{{ $emp->full_name }}</a>
                            <p class="text-xs text-slate-400">{{ $emp->position }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                        {{ $emp->employment_status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                        {{ $emp->employment_status === 'on_leave' ? 'bg-blue-500/20 text-blue-400' : '' }}
                        {{ $emp->employment_status === 'terminated' ? 'bg-red-500/20 text-red-400' : '' }}
                        {{ $emp->employment_status === 'resigned' ? 'bg-amber-500/20 text-amber-400' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $emp->employment_status)) }}
                    </span>
                </div>
            @empty
                <p class="text-center py-6 text-sm text-slate-500">No employees in this department.</p>
            @endforelse
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
        <a href="{{ route('admin.departments.edit', $department) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">Edit Department</a>
        <form action="{{ route('admin.departments.destroy', $department) }}" method="POST" class="inline">
            @csrf @method('DELETE')
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors" onclick="return confirm('Delete this department?')">Delete</button>
        </form>
    </div>
</div>
@endsection
