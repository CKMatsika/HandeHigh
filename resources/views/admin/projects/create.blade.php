@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">Create New Project</h1>
                <p class="text-xs text-slate-400 mt-1">Set up a new project with budget and timeline</p>
            </div>
            <a href="{{ route('admin.projects.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition">
                Back to Projects
            </a>
        </div>

        <form method="POST" action="{{ route('admin.projects.store') }}" class="space-y-6">
            @csrf
            
            <!-- Basic Information -->
            <div class="bg-slate-900/80 rounded-lg border border-slate-800 p-6">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Project Code</label>
                        <input type="text" name="code" required maxlength="50" 
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., PRJ-2025-001" value="{{ old('code') }}">
                        @error('code')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Project Name</label>
                        <input type="text" name="name" required maxlength="255" 
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter project name" value="{{ old('name') }}">
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
                            <option value="">Select project type</option>
                            <option value="infrastructure" {{ old('project_type') == 'infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                            <option value="academic" {{ old('project_type') == 'academic' ? 'selected' : '' }}>Academic</option>
                            <option value="maintenance" {{ old('project_type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="technology" {{ old('project_type') == 'technology' ? 'selected' : '' }}>Technology</option>
                            <option value="other" {{ old('project_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('project_type')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                        <select name="status" required 
                                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="planning" {{ old('status') == 'planning' ? 'selected' : '' }}>Planning</option>
                            <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                              placeholder="Describe the project goals, objectives, and deliverables">{{ old('description') }}</textarea>
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
                               value="{{ old('start_date') }}">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">End Date</label>
                        <input type="date" name="end_date" required 
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               value="{{ old('end_date') }}">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Budget Amount</label>
                    <input type="number" name="budget_amount" required min="0" step="0.01"
                               class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-300 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="0.00" value="{{ old('budget_amount') }}">
                        @error('budget_amount')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition font-medium">
                    Create Project
                </button>
            </div>
        </form>
    </div>
@endsection
