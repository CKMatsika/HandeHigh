@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Teachers</h1>
            <p class="text-xs text-slate-400 mt-1">Manage teaching staff, qualifications, subjects, and role assignments.</p>
        </div>
        <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Teacher
        </a>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or employee ID..." class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
            </div>
            <div>
                <select name="status" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <select name="specialization" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                    <option value="">All Specializations</option>
                    @foreach($specializations as $spec)
                        <option value="{{ $spec }}" {{ request('specialization') == $spec ? 'selected' : '' }}>{{ $spec }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-4 py-2 rounded-lg">Filter</button>
            <a href="{{ route('admin.teachers.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-4 py-2 rounded-lg">Clear</a>
        </form>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Teacher</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Employee ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Specialization</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Class Teacher</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Subjects</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($teachers as $teacher)
                        <tr class="hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                        <span class="text-xs text-indigo-400 font-bold">{{ substr($teacher->first_name, 0, 1) }}{{ substr($teacher->last_name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-50">{{ $teacher->full_name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $teacher->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $teacher->employee_id }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $teacher->specialization ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs">
                                @php
                                    $ctClass = \App\Models\SchoolClass::where('teacher_id', $teacher->user_id)->first();
                                @endphp
                                @if($ctClass)
                                    <span class="text-emerald-400">{{ $ctClass->name }}</span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @php
                                    $subjCount = $teacher->subjects->count();
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 bg-slate-700 text-slate-300 text-[10px]">{{ $subjCount }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $teacher->status ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $teacher->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.teachers.show', $teacher) }}" class="text-emerald-400 hover:text-emerald-300">Profile</a>
                                    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-amber-400 hover:text-amber-300">Edit</a>
                                    <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST" class="inline" onsubmit="return confirm('Delete this teacher?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-300">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                <p class="text-sm mb-4">No teachers found.</p>
                                <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Add Teacher</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($teachers->hasPages())
            <div class="px-4 py-3 border-t border-slate-800">{{ $teachers->links() }}</div>
        @endif
    </div>
</div>
@endsection
