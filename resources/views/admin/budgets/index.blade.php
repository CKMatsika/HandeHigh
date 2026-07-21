@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Budgets</h1>
            <p class="text-xs text-slate-400 mt-1">Plan and track spending with full workflow support.</p>
        </div>
        <a href="{{ route('admin.budgets.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">New Budget</a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-200 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Year</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Budgeted</th>
                        <th class="px-4 py-3 text-right font-medium">Actual</th>
                        <th class="px-4 py-3 text-right font-medium">Variance</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($budgets as $budget)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-medium">
                                <a href="{{ route('admin.budgets.show', $budget) }}" class="text-indigo-400 hover:text-indigo-300">
                                    {{ $budget->name }}
                                </a>
                                @if($budget->status === 'active')
                                    <span class="ml-2 px-1.5 py-0.5 text-[10px] font-medium rounded bg-emerald-900/50 text-emerald-300 border border-emerald-800/50">Active</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $budget->fiscal_year }}</td>
                            <td class="px-4 py-3 capitalize">{{ $budget->budget_type }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-{{ $budget->status_color }}-900/50 text-{{ $budget->status_color }}-300 border border-{{ $budget->status_color }}-800/50">
                                    {{ $budget->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($budget->total_budgeted, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($budget->total_actual, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono {{ $budget->total_variance < 0 ? 'text-red-400' : 'text-green-400' }}">
                                {{ number_format($budget->total_variance, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.budgets.show', $budget) }}" class="p-1 text-slate-400 hover:text-white" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    
                                    @if($budget->canBeEdited())
                                        <a href="{{ route('admin.budgets.edit', $budget) }}" class="p-1 text-slate-400 hover:text-white" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        
                                        @if($budget->canBeSubmitted())
                                            <form action="{{ route('admin.budgets.submit', $budget) }}" method="POST" class="inline" onsubmit="return confirm('Submit this budget for review?');">
                                                @csrf
                                                <button type="submit" class="p-1 text-blue-400 hover:text-blue-300" title="Submit for Review">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('bursar') && $budget->canBeReviewedByBursar())
                                        <a href="{{ route('admin.budgets.show', $budget) }}#review" class="p-1 text-amber-400 hover:text-amber-300" title="Review as Bursar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <div>No budgets found. Create your first budget to get started.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-800/50">
            {{ $budgets->links() }}
        </div>
    </div>

    <div class="mt-8 bg-slate-900/50 border border-slate-800 rounded-xl p-4 text-sm text-slate-400">
        <h3 class="font-medium text-slate-200 mb-2">Budget Workflow</h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-xs">
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">1. Draft</div>
                <div class="text-slate-500 mt-1">Created by Accounts Clerk</div>
            </div>
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">2. Submitted</div>
                <div class="text-slate-500 mt-1">Sent to Bursar</div>
            </div>
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">3. Bursar Review</div>
                <div class="text-slate-500 mt-1">Under Bursar review</div>
            </div>
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">4. Committee</div>
                <div class="text-slate-500 mt-1">Finance Committee review</div>
            </div>
            <div class="p-3 rounded-lg border border-slate-800 bg-slate-900/50">
                <div class="font-medium text-slate-200">5. Approved</div>
                <div class="text-slate-500 mt-1">Budget approved</div>
            </div>
        </div>
    </div>
@endsection
