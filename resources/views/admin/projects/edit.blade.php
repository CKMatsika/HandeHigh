@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">Edit Project</h1>
                <p class="text-xs text-slate-400 mt-1">{{ $project->name }} - {{ $project->code }}</p>
            </div>
            <a href="{{ route('admin.projects.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition">
                Back to Projects
            </a>
        </div>

        <form method="POST" action="{{ route('admin.projects.update', $project->id) }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Basic Information -->
            <div class="bg-slate-900/80 rounded-lg border border-slate-800 p-6">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Project Code</label>
                        <input type="text" name="code" required maxlength="50" 
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $project->code }}">
                        @error('code')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Project Name</label>
                        <input type="text" name="name" required maxlength="255" 
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $project->name }}">
                        @error('name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Project Type</label>
                        <select name="project_type" required 
                                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="infrastructure" {{ $project->project_type === 'infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                            <option value="academic" {{ $project->project_type === 'academic' ? 'selected' : '' }}>Academic</option>
                            <option value="maintenance" {{ $project->project_type === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="technology" {{ $project->project_type === 'technology' ? 'selected' : '' }}>Technology</option>
                            <option value="other" {{ $project->project_type === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('project_type')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                        <select name="status" required 
                                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="planning" {{ $project->status === 'planning' ? 'selected' : '' }}>Planning</option>
                            <option value="approved" {{ $project->status === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="active" {{ $project->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ $project->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $project->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Description</label>
                    <textarea name="description" rows="4" required maxlength="1000"
                              class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Describe the project goals, objectives, and deliverables">{{ $project->description }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                </div>
            </div>
            
            <!-- Timeline & Budget -->
            <div class="bg-slate-900/80 rounded-lg border border-slate-800 p-6">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">Timeline & Budget</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Start Date</label>
                        <input type="date" name="start_date" required 
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $project->start_date->format('Y-m-d') }}">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">End Date</label>
                        <input type="date" name="end_date" required 
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $project->end_date->format('Y-m-d') }}">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Budget Amount</label>
                    <input type="number" name="budget_amount" required min="0" step="0.01"
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $project->budget_amount }}">
                        @error('budget_amount')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.projects.show', $project->id) }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition font-medium">
                    Update Project
                </button>
            </div>
        </form>
    </div>
@endsection
