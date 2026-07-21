@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Budget Line</h1>
            <p class="text-xs text-slate-400 mt-1">Update budget line for {{ $budget->name }}</p>
        </div>
        <a href="{{ route('admin.budgets.show', $budget) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Cancel</a>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
        <form action="{{ route('admin.budgets.lines.update', [$budget, $line]) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label for="account_id" class="block text-xs font-medium text-slate-300 mb-2">Account *</label>
                    <select id="account_id" name="account_id" required
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Select an account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id', $line->account_id) == $account->id ? 'selected' : '' }}>
                                {{ $account->name }} ({{ $account->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('account_id')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cost_center_id" class="block text-xs font-medium text-slate-300 mb-2">Cost Center</label>
                    <select id="cost_center_id" name="cost_center_id"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Select a cost center (optional)</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('cost_center_id', $line->cost_center_id) == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('cost_center_id')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="period" class="block text-xs font-medium text-slate-300 mb-2">Period</label>
                        <select id="period" name="period"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">All Periods</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ old('period', $line->period) == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->format('F') }}
                                </option>
                            @endfor
                        </select>
                        @error('period')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="budgeted_amount" class="block text-xs font-medium text-slate-300 mb-2">Budgeted Amount *</label>
                        <input type="number" id="budgeted_amount" name="budgeted_amount" value="{{ old('budgeted_amount', $line->budgeted_amount) }}" required min="0" step="0.01"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="0.00">
                        @error('budgeted_amount')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-medium text-slate-300 mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="Add any notes about this budget line...">{{ old('notes', $line->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <a href="{{ route('admin.budgets.show', $budget) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Cancel</a>
                    <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Update Budget Line</button>
                </div>
            </div>
        </form>
    </div>
@endsection
