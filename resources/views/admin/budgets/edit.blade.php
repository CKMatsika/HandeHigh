@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Budget</h1>
            <p class="text-xs text-slate-400 mt-1">Update budget information</p>
        </div>
        <a href="{{ route('admin.budgets.show', $budget) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Cancel</a>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
        <form action="{{ route('admin.budgets.update', $budget) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-xs font-medium text-slate-300 mb-2">Budget Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $budget->name) }}" required
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Enter budget name">
                    @error('name')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="Enter budget description">{{ old('description', $budget->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="fiscal_year" class="block text-xs font-medium text-slate-300 mb-2">Fiscal Year *</label>
                        <input type="number" id="fiscal_year" name="fiscal_year" value="{{ old('fiscal_year', $budget->fiscal_year) }}" required min="2000" max="2100"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="e.g., 2024">
                        @error('fiscal_year')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="budget_type" class="block text-xs font-medium text-slate-300 mb-2">Budget Type *</label>
                        <select id="budget_type" name="budget_type" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="annual" {{ old('budget_type', $budget->budget_type) == 'annual' ? 'selected' : '' }}>Annual</option>
                            <option value="term" {{ old('budget_type', $budget->budget_type) == 'term' ? 'selected' : '' }}>Term</option>
                            <option value="monthly" {{ old('budget_type', $budget->budget_type) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                        @error('budget_type')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <div class="text-xs text-slate-400">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-{{ $budget->status_color }}-900/50 text-{{ $budget->status_color }}-300 border border-{{ $budget->status_color }}-800/50">
                            {{ $budget->status_label }}
                        </span>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.budgets.show', $budget) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Cancel</a>
                        <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Update Budget</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Budget Lines Management -->
    <div class="mt-6 bg-slate-900/50 border border-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-100">Budget Lines</h2>
            <a href="{{ route('admin.budgets.lines.create', $budget) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-600 transition">Add Line</a>
        </div>
        
        @if($budget->lines->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-950/60 text-slate-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Account</th>
                            <th class="px-4 py-3 text-left font-medium">Cost Center</th>
                            <th class="px-4 py-3 text-right font-medium">Budgeted</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($budget->lines as $line)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-4 py-3">{{ $line->account?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $line->costCenter?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($line->budgeted_amount, 2) }}</td>
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
                    <a href="{{ route('admin.budgets.lines.create', $budget) }}" class="text-indigo-400 hover:text-indigo-300">Add your first budget line</a>
                </div>
            </div>
        @endif
    </div>
@endsection
