@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">Project Details</h1>
                <p class="text-xs text-slate-400 mt-1">{{ $project->name }} - {{ $project->code }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.projects.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition">
                    Back to Projects
                </a>
                <a href="{{ route('admin.projects.edit', $project->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                    Edit Project
                </a>
            </div>
        </div>

        <!-- Project Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Basic Info -->
            <div class="bg-slate-900/80 rounded-lg border border-slate-800 p-6">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">Project Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-400">Status:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                            {{ $project->status === 'planning' ? 'bg-gray-500/20 text-gray-400' : '' }}
                            {{ $project->status === 'approved' ? 'bg-blue-500/20 text-blue-400' : '' }}
                            {{ $project->status === 'active' ? 'bg-green-500/20 text-green-400' : '' }}
                            {{ $project->status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                            {{ $project->status === 'cancelled' ? 'bg-red-500/20 text-red-400' : '' }}">
                            {{ ucfirst($project->status) }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-400">Type:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                            {{ $project->project_type === 'infrastructure' ? 'bg-blue-500/20 text-blue-400' : '' }}
                            {{ $project->project_type === 'academic' ? 'bg-green-500/20 text-green-400' : '' }}
                            {{ $project->project_type === 'maintenance' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                            {{ $project->project_type === 'technology' ? 'bg-purple-500/20 text-purple-400' : '' }}
                            {{ $project->project_type === 'other' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                            {{ ucfirst($project->project_type) }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-400">Duration:</span>
                        <span class="text-sm text-slate-300">
                            {{ $project->start_date->format('M d, Y') }} - {{ $project->end_date->format('M d, Y') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Budget Overview -->
            <div class="bg-slate-900/80 rounded-lg border border-slate-800 p-6">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">Budget Overview</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-400">Total Budget:</span>
                        <span class="text-lg font-semibold text-slate-50">${{ number_format($project->budget_amount, 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-400">Spent:</span>
                        <span class="text-lg font-semibold text-red-400">${{ number_format($project->total_spent, 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-400">Remaining:</span>
                        <span class="text-lg font-semibold {{ $project->remaining_budget > 0 ? 'text-green-400' : 'text-red-400' }}">
                            ${{ number_format($project->remaining_budget, 2) }}
                        </span>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-slate-400 mb-2">
                            <span>Budget Utilization</span>
                            <span>{{ round($project->budget_amount > 0 ? ($project->total_spent / $project->budget_amount * 100) : 0) }}%</span>
                        </div>
                        <div class="w-full bg-slate-700 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" style="width: {{ $project->budget_amount > 0 ? min(100, ($project->total_spent / $project->budget_amount * 100)) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-slate-900/80 rounded-lg border border-slate-800 p-6">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">Timeline</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-slate-400">Start Date:</span>
                        <span class="text-sm text-slate-300">{{ $project->start_date->format('F d, Y') }}</span>
                    </div>
                    
                    <div>
                        <span class="text-sm text-slate-400">End Date:</span>
                        <span class="text-sm text-slate-300">{{ $project->end_date->format('F d, Y') }}</span>
                    </div>
                    
                    <div>
                        <span class="text-sm text-slate-400">Days Remaining:</span>
                        <span class="text-sm text-slate-300">{{ $project->end_date->diffInDays(now())->format('%a') }} days</span>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-slate-900/80 rounded-lg border border-slate-800 p-6">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">Description</h3>
                <p class="text-sm text-slate-300 leading-relaxed">{{ $project->description }}</p>
            </div>
        </div>
    </div>
@endsection
