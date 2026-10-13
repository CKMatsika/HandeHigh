@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-bold tracking-tight text-slate-50">{{ $project->name }}</h1>
                    <span class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full 
                        {{ $project->status === 'planning' ? 'bg-slate-700/60 text-slate-300 border border-slate-600' : '' }}
                        {{ $project->status === 'approved' ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : '' }}
                        {{ $project->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : '' }}
                        {{ $project->status === 'completed' ? 'bg-purple-500/20 text-purple-400 border border-purple-500/30' : '' }}
                        {{ $project->status === 'cancelled' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : '' }}">
                        {{ ucfirst($project->status) }}
                    </span>
                    <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                        {{ $project->code }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1.5">
                    Type: <span class="text-slate-300 font-medium capitalize">{{ $project->project_type }}</span> &bull; 
                    Duration: <span class="text-slate-300">{{ $project->start_date->format('M d, Y') }} &mdash; {{ $project->end_date->format('M d, Y') }}</span>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.projects.index') }}" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs px-3.5 py-2 rounded-lg border border-slate-700 transition">
                    &larr; Projects
                </a>
                <a href="{{ route('admin.projects.edit', $project->id) }}" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs px-3.5 py-2 rounded-lg transition">
                    Edit Project
                </a>
                @if($project->code === 'PRJ-ERP-2026' || $project->project_type === 'technology')
                    <a href="{{ route('ict.dashboard') }}" class="inline-flex items-center gap-1.5 bg-cyan-600 hover:bg-cyan-500 text-white text-xs px-3.5 py-2 rounded-lg transition">
                        ICT Desk &rarr;
                    </a>
                @endif
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-slate-800 space-x-2">
            <a href="{{ route('admin.projects.show', [$project, 'tab' => 'tasks']) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition flex items-center gap-2 {{ $activeTab === 'tasks' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                <span>Implementation Roadmap & Tasks</span>
                <span class="px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'tasks' ? 'bg-cyan-500/20 text-cyan-300' : 'bg-slate-800 text-slate-400' }}">
                    {{ $project->tasks->count() }}
                </span>
            </a>
            <a href="{{ route('admin.projects.show', [$project, 'tab' => 'financials']) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition flex items-center gap-2 {{ $activeTab === 'financials' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                <span>Budget & Financials</span>
            </a>
            <a href="{{ route('admin.projects.show', [$project, 'tab' => 'overview']) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition flex items-center gap-2 {{ $activeTab === 'overview' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                <span>Project Overview & Scope</span>
            </a>
        </div>

        @if($activeTab === 'tasks')
            <!-- IMPLEMENTATION ROADMAP & TASKS TAB -->
            <div class="space-y-6">
                <!-- Progress Header Banner -->
                <div class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900/90 to-slate-950 p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs uppercase tracking-wider font-semibold text-cyan-400">Implementation Milestones</span>
                            <h2 class="text-lg font-bold text-slate-100 mt-1">ERP Rollout Execution Progress</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Track every critical deliverable from teacher class allocations to go-live training.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="document.getElementById('addTaskModal').classList.remove('hidden')"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold shadow-lg shadow-cyan-600/20 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add Task / Milestone
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300 font-medium">Overall Rollout Completion</span>
                            <span class="text-cyan-400 font-bold">{{ $project->progress_percentage }}% ({{ $project->completed_tasks_count }} of {{ $project->tasks_count }} completed)</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden border border-slate-700/50">
                            <div class="bg-gradient-to-r from-cyan-500 to-emerald-400 h-3 rounded-full transition-all duration-500" style="width: {{ $project->progress_percentage }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Tasks Grouped by Phase Milestone -->
                @forelse($tasksByPhase as $phaseName => $tasks)
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="h-3 w-3 rounded-full bg-cyan-400"></span>
                                <h3 class="text-sm font-bold text-slate-100 tracking-wide">{{ $phaseName }}</h3>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono">
                                    {{ $tasks->where('status', 'completed')->count() }}/{{ $tasks->count() }} done
                                </span>
                            </div>
                            <span class="text-xs text-slate-400">
                                {{ round($tasks->count() > 0 ? ($tasks->where('status', 'completed')->count() / $tasks->count()) * 100 : 0) }}% complete
                            </span>
                        </div>

                        <div class="space-y-2.5">
                            @foreach($tasks as $task)
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-3.5 rounded-xl border border-slate-800/80 bg-slate-950/60 hover:bg-slate-800/40 transition">
                                    <div class="flex items-start gap-3.5 flex-1 min-w-0">
                                        <!-- 1-Click Complete Toggle -->
                                        <form method="POST" action="{{ route('admin.projects.tasks.toggle', [$project, $task]) }}" class="mt-0.5 shrink-0">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="{{ $task->status === 'completed' ? 'Mark In Progress' : 'Mark Completed' }}"
                                                    class="h-5 w-5 rounded border flex items-center justify-center transition {{ $task->status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-600 bg-slate-900 hover:border-cyan-400 text-transparent' }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                        </form>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-semibold text-slate-100 {{ $task->status === 'completed' ? 'line-through text-slate-400' : '' }}">
                                                    {{ $task->title }}
                                                </span>
                                                <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $task->priority_badge_class }}">
                                                    {{ ucfirst($task->priority) }}
                                                </span>
                                                <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $task->status_badge_class }}">
                                                    {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                                </span>
                                            </div>
                                            @if($task->description)
                                                <p class="text-xs text-slate-400 mt-1 leading-relaxed">{{ $task->description }}</p>
                                            @endif
                                            <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500 mt-1.5">
                                                <span>Assigned: <strong class="text-slate-300 font-medium">{{ $task->assignee?->name ?? 'Unassigned' }}</strong></span>
                                                @if($task->due_date)
                                                    <span>Due: <span class="{{ $task->is_overdue ? 'text-red-400 font-semibold' : 'text-slate-300' }}">{{ $task->due_date->format('M d, Y') }}</span></span>
                                                @endif
                                                @if($task->completed_at)
                                                    <span class="text-emerald-400">Done: {{ $task->completed_at->format('M d') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0 md:self-center">
                                        <form method="POST" action="{{ route('admin.projects.tasks.destroy', [$project, $task]) }}" onsubmit="return confirm('Remove this task?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-500 hover:text-red-400 p-1.5 rounded transition text-xs" title="Delete Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-800 p-10 text-center">
                        <p class="text-sm text-slate-400">No implementation tasks registered yet for this project.</p>
                        <button onclick="document.getElementById('addTaskModal').classList.remove('hidden')"
                                class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold transition">
                            Create First Task
                        </button>
                    </div>
                @endforelse
            </div>

            <!-- Add Task Modal -->
            <div id="addTaskModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <h3 class="text-sm font-bold text-slate-100">Add Implementation Task / Milestone</h3>
                        <button onclick="document.getElementById('addTaskModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
                            &times;
                        </button>
                    </div>

                    <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Task Title *</label>
                            <input type="text" name="title" required placeholder="e.g. Verify Form 2 Math teaching class allocations"
                                   class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Phase / Milestone *</label>
                                <input type="text" name="phase_milestone" list="phaseSuggestions" required placeholder="Select or type phase"
                                       class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">
                                <datalist id="phaseSuggestions">
                                    <option value="Phase 1: Academic & Subject Allocations"></option>
                                    <option value="Phase 2: Teacher Portal Adoption"></option>
                                    <option value="Phase 3: Finance, Invoices & Clearances"></option>
                                    <option value="Phase 4: Staff Training & Go-Live"></option>
                                    <option value="Post Go-Live Support & Review"></option>
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Priority *</label>
                                <select name="priority" required
                                        class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">
                                    <option value="medium">Medium</option>
                                    <option value="high" selected>High</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Assigned Lead / Staff</label>
                                <select name="assigned_to"
                                        class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">
                                    <option value="">Unassigned</option>
                                    @foreach($staffMembers as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Due Date</label>
                                <input type="date" name="due_date"
                                       class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Task Description / Requirements</label>
                            <textarea name="description" rows="3" placeholder="What needs to be configured, tested, or signed off?"
                                      class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <button type="button" onclick="document.getElementById('addTaskModal').classList.add('hidden')"
                                    class="px-3.5 py-2 text-xs rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-xs rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold">
                                Save Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @elseif($activeTab === 'financials')
            <!-- BUDGET & FINANCIALS TAB -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Budget Overview Card -->
                <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 md:col-span-2 space-y-4">
                    <h3 class="text-base font-semibold text-slate-50">Project Budget & Expenditure</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800">
                            <span class="text-xs text-slate-400">Total Budget</span>
                            <p class="text-xl font-bold text-slate-100 mt-1">${{ number_format($project->budget_amount, 2) }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800">
                            <span class="text-xs text-slate-400">Spent to Date</span>
                            <p class="text-xl font-bold text-red-400 mt-1">${{ number_format($project->total_spent, 2) }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800">
                            <span class="text-xs text-slate-400">Remaining</span>
                            <p class="text-xl font-bold {{ $project->remaining_budget >= 0 ? 'text-emerald-400' : 'text-red-400' }} mt-1">
                                ${{ number_format($project->remaining_budget, 2) }}
                            </p>
                        </div>
                    </div>

                    <!-- Utilization Bar -->
                    <div class="space-y-1.5 pt-2">
                        <div class="flex justify-between text-xs text-slate-400">
                            <span>Budget Utilization</span>
                            <span>{{ round($project->budget_amount > 0 ? ($project->total_spent / $project->budget_amount * 100) : 0) }}%</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2.5">
                            <div class="bg-red-500 h-2.5 rounded-full" style="width: {{ $project->budget_amount > 0 ? min(100, ($project->total_spent / $project->budget_amount * 100)) : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Record Income Card -->
                <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-slate-50">Project Income / Grants</h3>
                    <p class="text-xs text-slate-400">Record donations, ministry grants or school budget allocation for this project.</p>
                    <form method="POST" action="{{ route('admin.projects.income', $project) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Amount ($) *</label>
                            <input type="number" step="0.01" name="amount" required
                                   class="w-full rounded-lg bg-slate-950 border border-slate-800 px-3 py-1.5 text-xs text-slate-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Payment Method *</label>
                            <select name="payment_method" required class="w-full rounded-lg bg-slate-950 border border-slate-800 px-3 py-1.5 text-xs text-slate-100">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Date *</label>
                            <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required
                                   class="w-full rounded-lg bg-slate-950 border border-slate-800 px-3 py-1.5 text-xs text-slate-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Description *</label>
                            <input type="text" name="description" placeholder="e.g. ERP Implementation Grant" required
                                   class="w-full rounded-lg bg-slate-950 border border-slate-800 px-3 py-1.5 text-xs text-slate-100">
                        </div>
                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold transition">
                            Record Income
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- PROJECT OVERVIEW TAB -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Basic Info -->
                <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 uppercase tracking-wider text-slate-400">Project Details</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Project Code:</span>
                            <span class="font-mono text-slate-200">{{ $project->code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Category:</span>
                            <span class="text-slate-200 capitalize">{{ $project->project_type }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Status:</span>
                            <span class="text-slate-200 capitalize">{{ $project->status }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Tasks Count:</span>
                            <span class="text-slate-200 font-bold">{{ $project->tasks->count() }} ({{ $project->completed_tasks_count }} done)</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 uppercase tracking-wider text-slate-400">Timeline & Schedule</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Start Date:</span>
                            <span class="text-slate-200">{{ $project->start_date->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Target Completion:</span>
                            <span class="text-slate-200">{{ $project->end_date->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Days Remaining:</span>
                            <span class="text-slate-200 font-semibold">{{ $project->end_date->isPast() ? 'Passed' : $project->end_date->diffInDays(now()) . ' days' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Scope / Description -->
                <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 uppercase tracking-wider text-slate-400">Project Scope</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">{{ $project->description }}</p>
                </div>
            </div>
        @endif
    </div>
@endsection
