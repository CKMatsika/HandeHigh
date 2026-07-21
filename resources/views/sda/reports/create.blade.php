@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Create SDA Report</h1>
            <p class="text-xs text-slate-400 mt-1">Generate a new School Development Association report.</p>
        </div>
        <a href="{{ route('sda.reports.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-900/50 border border-green-700 text-green-200 rounded-lg p-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-200 rounded-lg p-4 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('sda.reports.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Report Title *</label>
                    <input type="text" name="title" required maxlength="255"
                           value="{{ old('title') }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter report title">
                    @error('title')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Report Type *</label>
                    <select name="report_type" required
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select report type</option>
                        <option value="monthly" {{ old('report_type') == 'monthly' ? 'selected' : '' }}>Monthly Report</option>
                        <option value="quarterly" {{ old('report_type') == 'quarterly' ? 'selected' : '' }}>Quarterly Report</option>
                        <option value="annual" {{ old('report_type') == 'annual' ? 'selected' : '' }}>Annual Report</option>
                        <option value="special" {{ old('report_type') == 'special' ? 'selected' : '' }}>Special Report</option>
                        <option value="financial" {{ old('report_type') == 'financial' ? 'selected' : '' }}>Financial Report</option>
                        <option value="activity" {{ old('report_type') == 'activity' ? 'selected' : '' }}>Activity Report</option>
                    </select>
                    @error('report_type')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Committee *</label>
                    <select name="sda_committee_id" required
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select committee</option>
                        @foreach($committees as $committee)
                            <option value="{{ $committee->id }}" {{ old('sda_committee_id') == $committee->id ? 'selected' : '' }}>
                                {{ $committee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sda_committee_id')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Report Period *</label>
                    <input type="text" name="report_period" required maxlength="100"
                           value="{{ old('report_period', now()->format('F Y')) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., January 2025, Q1 2025, 2025">
                    @error('report_period')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-2">Executive Summary</label>
                    <textarea name="executive_summary" rows="4"
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Brief overview of the report highlights">{{ old('executive_summary') }}</textarea>
                    @error('executive_summary')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-2">Report Content *</label>
                    <textarea name="content" rows="8" required
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Enter the detailed report content...">{{ old('content') }}</textarea>
                    @error('content')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Recommendations</label>
                    <textarea name="recommendations" rows="4"
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Key recommendations and action items">{{ old('recommendations') }}</textarea>
                    @error('recommendations')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Attachments</label>
                    <div class="space-y-2">
                        <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-slate-400">Upload supporting documents (PDF, DOC, XLS)</p>
                    </div>
                    @error('attachments.*')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-700">
                <a href="{{ route('sda.reports.index') }}" 
                   class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Create Report
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
