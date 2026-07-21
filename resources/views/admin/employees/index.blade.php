@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Employee Management</h1>
            <p class="text-xs text-slate-400 mt-1">Manage non-teaching staff and HR operations.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.departments.index') }}" class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-medium text-white hover:bg-purple-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Departments
            </a>
            <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Add Employee
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="...", "bg-slateP-4">
偶尔
        <.
        <form.md:grid;4 gap-
             <divInvestigators="GET" class="文化传播 md:School 4路站 md
            < 4 gap-结构调整
               .
                <<|code_suffix|>                    <charges
                    <optionainment="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="status" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                        <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                        <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    </select>
                </div>
                <div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Search employees...">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-4 py-2 rounded-lg transition-colors">
                        Filter
                    </button>
                    <a href="{{ route('admin.employees.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-4 py-2 rounded-lg transition-colors">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Employee ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Position</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Hire Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($employee->profile_photo)
                                        <img src="{{ Storage::url($employee->profile_photo) }}" alt="{{ $employee->full_name }}" class="w-8 h-8 rounded-full object-cover">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center">
                                            <span class="text-xs text-slate-300 font-medium">{{ substr($employee->first_name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-slate-50">{{ $employee->full_name }}</div>
                                        <div class="text-xs text-slate-400">{{ $employee->email }}</div>
                                        <div class="text-xs text-slate-400">{{ $employee->phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300 font-mono">
                                {{ $employee->employee_id }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                {{ $employee->department->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                {{ $employee->position }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $employee->employment_status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $employee->employment_status === 'terminated' ? 'bg-red-500/20 text-red-400' : '' }}
                                    {{ $employee->employment_status === 'resigned' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                    {{ $employee->employment_status === 'on_leave' ? 'bg-blue-500/20 text-blue-400' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $employee->employment_status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                {{ $employee->hire_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div class="flex gap-1">
                                    <a href="{{ route('admin.employees.show', $employee) }}" class="text-blue-400 hover:text-blue-300">
                                        View
                                    </a>
                                    <a href="{{ route('admin.employees.edit', $employee) }}" class="text-amber-400 hover:text-amber-300">
                                        Edit
                                    </a>
                                    @if($employee->employment_status === 'active')
                                        <form action="{{ route('admin.employees.terminate', $employee) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="text-red-400 hover:text-red-300"
                                                    onclick="return confirm('Are you sure you want to terminate this employee?')">
                                                Terminate
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-300"
                                                onclick="return confirm('Are you sure you want to delete this employee?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                <div class="mb-4">
                                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-slate-50 mb-2">No employees found</h3>
                                <p class="text-sm text-slate-400 mb-4">Start by adding your first employee.</p>
                                <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                                    Add Employee
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($employees->hasPages())
            <div class="px-4 py-3 border-t border-slate-800">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
