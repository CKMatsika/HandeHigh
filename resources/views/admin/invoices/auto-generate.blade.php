@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Auto Generate Invoices</h1>
            <p class="text-xs text-slate-400 mt-1">Automatically generate invoices for all active students based on fee structures.</p>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-950/60 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back to Invoices
        </a>
    </div>

    <form method="POST" action="{{ route('admin.invoices.process-auto-generate') }}" class="space-y-6">
        @csrf

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Target Term</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="target_academic_year">Academic Year</label>
                    <input type="text" id="target_academic_year" name="target_academic_year" required 
                           value="{{ now()->year }}-{{ now()->year + 1 }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100"
                           placeholder="e.g., 2025-2026">
                </div>
                
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="target_term">Term</label>
                    <select id="target_term" name="target_term" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                        <option value="Term 1">Term 1</option>
                        <option value="Term 2">Term 2</option>
                        <option value="Term 3">Term 3</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="generation_date">Generation Date</label>
                    <input type="date" id="generation_date" name="generation_date" required 
                           value="{{ now()->format('Y-m-d') }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                </div>
                
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" 
                           value="{{ now()->addDays(30)->format('Y-m-d') }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Fee Options</h2>
            
            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <input type="checkbox" id="include_boarding" name="include_boarding" value="1" 
                           class="rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0">
                    <label for="include_boarding" class="text-xs text-slate-300">
                        Include boarding fees for boarding students
                    </label>
                </div>
                
                <div class="flex items-center space-x-3">
                    <input type="checkbox" id="include_transport" name="include_transport" value="1" 
                           class="rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0">
                    <label for="include_transport" class="text-xs text-slate-300">
                        Include transport fees for students with transport
                    </label>
                </div>
                
                <div class="flex items-center space-x-3">
                    <input type="checkbox" id="include_optional_fees" name="include_optional_fees" value="1" 
                           class="rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0">
                    <label for="include_optional_fees" class="text-xs text-slate-300">
                        Include optional fees (activities, extracurriculars, etc.)
                    </label>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-4">
            <h2 class="text-sm font-medium text-amber-200 mb-3">Generation Summary</h2>
            
            <div class="text-xs text-amber-300 space-y-1">
                <p>• Invoices will be generated for all <strong>active enrollments</strong></p>
                <p>• Fee structures must be configured for each grade level</p>
                <p>• Duplicate invoices for the same student/term will be skipped</p>
                <p>• All invoices will be created with <strong>"unpaid"</strong> status</p>
                <p>• Ledger entries will be automatically created for accounting</p>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.invoices.index') }}" 
               class="rounded-full border border-slate-700 bg-slate-950/60 px-6 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit" 
                    class="rounded-full bg-emerald-500 px-6 py-2 text-xs font-medium text-white hover:bg-emerald-600 transition">
                Generate Invoices
            </button>
        </div>
    </form>
@endsection
