@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.leaves.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">New Leave Request</h1>
            <p class="text-xs text-slate-400 mt-1">Create a leave request for an employee.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('admin.leaves.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Leave Details</h3>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Employee *</label>
                        <select name="employee_id" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-rose-500">
                            <option value="">Select Employee</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} ({{ $emp->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Leave Type *</label>
                        <select name="leave_type" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-rose-500">
                            <option value="">Select Type</option>
                            <option value="annual" {{ old('leave_type') == 'annual' ? 'selected' : '' }}>Annual Leave</option>
                            <option value="sick" {{ old('leave_type') == 'sick' ? 'selected' : '' }}>Sick Leave</option>
                            <option value="maternity" {{ old('leave_type') == 'maternity' ? 'selected' : '' }}>Maternity Leave</option>
                            <option value="paternity" {{ old('leave_type') == 'paternity' ? 'selected' : '' }}>Paternity Leave</option>
                            <option value="study" {{ old('leave_type') == 'study' ? 'selected' : '' }}>Study Leave</option>
                            <option value="compassionate" {{ old('leave_type') == 'compassionate' ? 'selected' : '' }}>Compassionate Leave</option>
                            <option value="unpaid" {{ old('leave_type') == 'unpaid' ? 'selected' : '' }}>Unpaid Leave</option>
                            <option value="other" {{ old('leave_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Start Date *</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-rose-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">End Date *</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-rose-500">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Reason</h3>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Reason for Leave</label>
                        <textarea name="reason" rows="6" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500"
                                  placeholder="Provide details about the reason for this leave request...">{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Submit Leave Request</button>
                <a href="{{ route('admin.leaves.index') }}" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium py-2 px-4 rounded-lg text-center transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
