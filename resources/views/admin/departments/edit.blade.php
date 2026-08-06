@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.departments.show', $department) }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Edit — {{ $department->name }}</h1>
            <p class="text-xs text-slate-400 mt-1">Update department information.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('admin.departments.update', $department) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Basic Information</h3>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Department Name *</label>
                        <input type="text" name="name" value="{{ old('name', $department->name) }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Head of Department</label>
                        <input type="text" name="head_of_department" value="{{ old('head_of_department', $department->head_of_department) }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Budget</label>
                        <input type="number" name="budget" value="{{ old('budget', $department->budget) }}" step="0.01" min="0"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $department->is_active) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                        <span class="ml-3 text-sm text-slate-300">Active Department</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Additional Details</h3>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                        <textarea name="description" rows="8" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $department->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Update Department</button>
                <a href="{{ route('admin.departments.show', $department) }}" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium py-2 px-4 rounded-lg text-center transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
