@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">{{ $budget->name }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $budget->fiscal_year }} {{ $budget->budget_type }} budget</p>
        </div>
        <div class="flex items-center space-x-2">
            @if($budget->canBeEdited())
                <a href="{{ route('admin.budgets.edit', $budget) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Edit Budget</a>
            @endif
            
            @if($budget->canBeSubmitted())
                <form action="{{ route('admin.budgets.submit', $budget) }}" method="POST" class="inline" onsubmit="return confirm('Submit this budget for review?');">
                    @csrf
                    <button type="submit" class="rounded-full bg-blue-500 px-4 py-2 text-xs font-medium text-white hover:bg-blue-600 transition">Submit for Review</button>
                </form>
            @endif
            
            @if($budget->status === 'approved' && $budget->canBeActivated())
                <form action="{{ route('admin.budgets.activate', $budget) }}" method="POST" class="inline" onsubmit="return confirm('Activate this budget?');">
                    @csrf
                    <button type="submit" class="rounded-full bg-emerald-500 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-600 transition">Activate Budget</button>
                </form>
            @endif
            
            @if($budget->status === 'active')
                <form action="{{ route('admin.budgets.close', $budget) }}" method="POST" class="inline" onsubmit="return confirm('Close this budget?');">
                    @csrf
                    <button type="submit" class="rounded-full bg-slate-600 px-4 py-2 text-xs font-medium text-white hover:bg-slate-500 transition">Close Budget</button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-200 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Budget Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Status</div>
            <div class="text-lg font-semibold text-slate-100">
                <span class="px-2 py-1 rounded-full text-xs font-medium bg-{{ $budget->status_color }}-900/50 text-{{ $budget->status_color }}-300 border border-{{ $budget->status_color }}-800/50">
                    {{ $budget->status_label }}
                </span>
            </div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Total Budgeted</div>
            <div class="text-lg font-semibold text-slate-100">{{ number_format($budget->total_budgeted, 2) }}</div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Total Actual</div>
            <div class="text-lg font-semibold text-slate-100">{{ number_format($budget->total_actual, 2) }}</div>
        </div>
        
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-4">
            <div class="text-xs text-slate-400 mb-1">Variance</div>
            <div class="text-lg font-semibold {{ $budget->total_variance < 0 ? 'text-red-400' : 'text-green-400' }}">
                {{ number_format($budget->total_variance, 2) }}
            </div>
        </div>
    </div>

    <!-- Budget Details -->
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-100 mb-4">Budget Details</h2>
        
        @if($budget->description)
            <div class="mb-4">
                <div class="text-xs text-slate-400 mb-1">Description</div>
                <div class="text-sm text-slate-200">{{ $budget->description }}</div>
            </div>
        @endif
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-slate-400 mb-1">Created By</div>
                <div class="text-sm text-slate-200">{{ $budget->creator?->name ?? 'N/A' }}</div>
            </div>
            
            @if($budget->submitter)
                <div>
                    <div class="text-xs text-slate-400 mb-1">Submitted By</div>
                    <div class="text-sm text-slate-200">{{ $budget->submitter->name }} on {{ $budget->submitted_at->format('M j, Y') }}</div>
                </div>
            @endif
            
            @if($budget->bursarReviewer)
                <div>
                    <div class="text-xs text-slate-400 mb-1">Bursar Review</div>
                    <div class="text-sm text-slate-200">{{ $budget->bursarReviewer->name }} on {{ $budget->bursar_reviewed_at->format('M j, Y') }}</div>
                </div>
            @endif
            
            @if($budget->committeeReviewer)
                <div>
                    <div class="text-xs text-slate-400 mb-1">Committee Review</div>
                    <div class="text-sm text-slate-200">{{ $budget->committeeReviewer->name }} on {{ $budget->committee_reviewed_at->format('M j, Y') }}</div>
                </div>
            @endif
        </div>
        
        @if($budget->rejection_reason)
            <div class="mt-4 p-3 bg-red-900/20 border border-red-800 rounded-lg">
                <div class="text-xs text-red-400 mb-1">Rejection Reason</div>
                <div class="text-sm text-red-200">{{ $budget->rejection_reason }}</div>
            </div>
        @endif
    </div>

    <!-- Budget Lines -->
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-100">Budget Lines ({{ $budget->lines->count() }})</h2>
            @if($budget->canBeEdited())
                <a href="{{ route('admin.budgets.lines.create', $budget) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-600 transition">Add Line</a>
            @endif
        </div>
        
        @if($budget->lines->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-950/60 text-slate-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Account</th>
                            <th class="px-4 py-3 text-left font-medium">Cost Center</th>
                            <th class="px-4 py-3 text-left font-medium">Period</th>
                            <th class="px-4 py-3 text-right font-medium">Budgeted</th>
                            <th class="px-4 py-3 text-right font-medium">Actual</th>
                            <th class="px-4 py-3 text-right font-medium">Variance</th>
                            @if($budget->canBeEdited())
                                <th class="px-4 py-3 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($budget->lines as $line)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-4 py-3">{{ $line->account?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $line->costCenter?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $line->period ?? 'All' }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($line->budgeted_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($line->actual_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono {{ $line->variance < 0 ? 'text-red-400' : 'text-green-400' }}">
                                    {{ number_format($line->variance, 2) }}
                                </td>
                                @if($budget->canBeEdited())
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('admin.budgets.lines.edit', [$budget, $line]) }}" class="p-1 text-slate-400 hover:text-white" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('admin.budgets.lines.destroy', [$budget, $line]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this budget line?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-red-400 hover:text-red-300" title="Delete">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-slate-500">
                <div class="flex flex-col items-center justify-center space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <div>No budget lines found.</div>
                    @if($budget->canBeEdited())
                        <a href="{{ route('admin.budgets.lines.create', $budget) }}" class="text-indigo-400 hover:text-indigo-300">Add your first budget line</a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Review Actions -->
    @if(auth()->user()->hasRole('bursar') && $budget->canBeReviewedByBursar())
        <div id="review" class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-slate-100 mb-4">Bursar Review</h2>
            
            <form action="{{ route('admin.budgets.bursar-review', $budget) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Decision</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="approved" value="1" class="mr-2" required>
                                <span class="text-sm text-slate-200">Approve and send to Finance Committee</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="approved" value="0" class="mr-2" required>
                                <span class="text-sm text-slate-200">Reject and return to Accounts Clerk</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-xs font-medium text-slate-300 mb-2">Review Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Add your review comments..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="submit" class="rounded-full bg-amber-500 px-4 py-2 text-xs font-medium text-white hover:bg-amber-600 transition">Submit Review</button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    @if(auth()->user()->hasRole(['super-admin', 'school-admin', 'bursar']) && $budget->canBeReviewedByCommittee())
        <div id="review" class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-slate-100 mb-4">Finance Committee Review</h2>
            
            <form action="{{ route('admin.budgets.committee-review', $budget) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Decision</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="approved" value="1" class="mr-2" required>
                                <span class="text-sm text-slate-200">Approve Budget</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="approved" value="0" class="mr-2" required>
                                <span class="text-sm text-slate-200">Reject and return to Accounts Clerk</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-xs font-medium text-slate-300 mb-2">Review Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Add your review comments..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="submit" class="rounded-full bg-purple-500 px-4 py-2 text-xs font-medium text-white hover:bg-purple-600 transition">Submit Review</button>
                    </div>
                </div>
            </form>
        </div>
    @endif
@endsection
