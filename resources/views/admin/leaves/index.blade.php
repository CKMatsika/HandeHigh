@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Leave Management</h1>
            <p class="text-xs text-slate-400 mt-1">Manage employee leave requests and approvals.</p>
        </div>
        <a href="{{ route('admin.leaves.create') }}" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            New Leave Request
        </a>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <select name="status" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <select name="leave_type" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                    <option value="">All Types</option>
                    <option value="annual" {{ request('leave_type') == 'annual' ? 'selected' : '' }}>Annual</option>
                    <option value="sick" {{ request('leave_type') == 'sick' ? 'selected' : '' }}>Sick</option>
                    <option value="maternity" {{ request('leave_type') == 'maternity' ? 'selected' : '' }}>Maternity</option>
                    <option value="paternity" {{ request('leave_type') == 'paternity' ? 'selected' : '' }}>Paternity</option>
                    <option value="study" {{ request('leave_type') == 'study' ? 'selected' : '' }}>Study</option>
                    <option value="compassionate" {{ request('leave_type') == 'compassionate' ? 'selected' : '' }}>Compassionate</option>
                    <option value="unpaid" {{ request('leave_type') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="other" {{ request('leave_type') == 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div>
                <select name="employee_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white text-xs px-4 py-2 rounded-lg transition-colors">Filter</button>
                <a href="{{ route('admin.leaves.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-4 py-2 rounded-lg transition-colors">Clear</a>
            </div>
        </form>
    </div>

    <!-- Leaves Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Duration</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Days</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($leaves as $leave)
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-rose-500/20 flex items-center justify-center">
                                        <span class="text-xs text-rose-400 font-bold">{{ substr($leave->employee->first_name ?? '?', 0, 1) }}{{ substr($leave->employee->last_name ?? '', 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-50">{{ $leave->employee->full_name ?? 'N/A' }}</div>
                                        <div class="text-xs text-slate-400">{{ $leave->employee->employee_id ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $leave->leave_type === 'sick' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $leave->leave_type === 'annual' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                    {{ $leave->leave_type === 'maternity' ? 'bg-pink-500/20 text-pink-400' : '' }}
                                    {{ $leave->leave_type === 'study' ? 'bg-violet-500/20 text-violet-400' : '' }}
                                    {{ $leave->leave_type === 'compassionate' ? 'bg-orange-500/20 text-orange-400' : '' }}
                                    {{ $leave->leave_type === 'unpaid' ? 'bg-slate-500/20 text-slate-400' : '' }}
                                    {{ !in_array($leave->leave_type, ['sick','annual','maternity','study','compassionate','unpaid']) ? 'bg-rose-500/20 text-rose-400' : '' }}">
                                    {{ ucfirst($leave->leave_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                {{ $leave->start_date->format('M d') }} → {{ $leave->end_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $leave->total_days }} day{{ $leave->total_days > 1 ? 's' : '' }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $leave->status === 'pending' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                    {{ $leave->status === 'approved' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $leave->status === 'rejected' ? 'bg-red-500/20 text-red-400' : '' }}
                                    {{ $leave->status === 'cancelled' ? 'bg-slate-500/20 text-slate-400' : '' }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <a href="{{ route('admin.leaves.show', $leave) }}" class="text-rose-400 hover:text-rose-300">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                <h3 class="text-lg font-medium text-slate-50 mb-2">No leave requests found</h3>
                                <p class="text-sm text-slate-400 mb-4">Submit a new leave request to get started.</p>
                                <a href="{{ route('admin.leaves.create') }}" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 transition-colors">
                                    New Leave Request
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leaves->hasPages())
            <div class="px-4 py-3 border-t border-slate-800">{{ $leaves->links() }}</div>
        @endif
    </div>
</div>
@endsection
