@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">Projects</h1>
                <p class="text-xs text-slate-400 mt-1">Manage and track all school projects</p>
            </div>
            <a href="{{ route('admin.projects.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                New Project
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-slate-900/80 rounded-lg border border-slate-800 p-4">
            <form method="GET" action="{{ route('admin.projects.index') }}" class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Projects</option>
                        <option value="planning" {{ request('status') == 'planning' ? 'selected' : '' }}>Planning</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Project Type</label>
                    <select name="project_type" class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        <option value="infrastructure" {{ request('project_type') == 'infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                        <option value="academic" {{ request('project_type') == 'academic' ? 'selected' : '' }}>Academic</option>
                        <option value="maintenance" {{ request('project_type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="technology" {{ request('project_type') == 'technology' ? 'selected' : '' }}>Technology</option>
                        <option value="other" {{ request('project_type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.projects.index') }}" class="text-slate-400 hover:text-slate-300 px-4 py-2 transition">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Projects Table -->
        <div class="bg-slate-900/80 rounded-lg border border-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-700">
                    <thead class="bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Budget</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Spent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Remaining</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Progress</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-slate-900/50">
                        @if($projects->count() > 0)
                            @foreach($projects as $project)
                                <tr class="hover:bg-slate-800 transition-colors">
                                    <td class="px-4 py-3 text-sm text-slate-300">{{ $project->code }}</td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-slate-50">{{ $project->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $project->description }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $project->project_type === 'infrastructure' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                            {{ $project->project_type === 'academic' ? 'bg-green-500/20 text-green-400' : '' }}
                                            {{ $project->project_type === 'maintenance' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                            {{ $project->project_type === 'technology' ? 'bg-purple-500/20 text-purple-400' : '' }}
                                            {{ $project->project_type === 'other' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                                            {{ ucfirst($project->project_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $project->status === 'planning' ? 'bg-gray-500/20 text-gray-400' : '' }}
                                            {{ $project->status === 'approved' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                            {{ $project->status === 'active' ? 'bg-green-500/20 text-green-400' : '' }}
                                            {{ $project->status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                            {{ $project->status === 'cancelled' ? 'bg-red-500/20 text-red-400' : '' }}">
                                            {{ ucfirst($project->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-300">${{ number_format($project->budget_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">${{ number_format($project->total_spent, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">${{ number_format($project->remaining_budget, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="flex-1 bg-slate-700 rounded-full h-2 mr-2">
                                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $project->budget_amount > 0 ? ($project->total_spent / $project->budget_amount * 100) : 0 }}%"></div>
                                            </div>
                                            <span class="text-xs text-slate-400">{{ round($project->budget_amount > 0 ? ($project->total_spent / $project->budget_amount * 100) : 0) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.projects.show', $project->id) }}" class="text-blue-400 hover:text-blue-300 transition">View</a>
                                            <a href="{{ route('admin.projects.edit', $project->id) }}" class="text-amber-400 hover:text-amber-300 transition">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                    No projects found
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($projects->hasPages())
                <div class="px-4 py-3 bg-slate-800 border-t border-slate-700">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
