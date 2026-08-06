@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.leaves.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-rose-500/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">Leave Request</h1>
                <p class="text-xs text-slate-400">{{ $leave->employee->full_name ?? 'N/A' }} — {{ ucfirst($leave->leave_type) }} Leave</p>
            </div>
        </div>
    </div>

    <!-- Status Banner -->
    <div class="rounded-xl border p-4
        {{ $leave->status === 'pending' ? 'border-amber-500/30 bg-amber-500/10' : '' }}
        {{ $leave->status === 'approved' ? 'border-emerald-500/30 bg-emerald-500/10' : '' }}
        {{ $leave->status === 'rejected' ? 'border-red-500/30 bg-red-500/10' : '' }}
        {{ $leave->status === 'cancelled' ? 'border-slate-500/30 bg-slate-500/10' : '' }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium
                    {{ $leave->status === 'pending' ? 'bg-amber-500/20 text-amber-400' : '' }}
                    {{ $leave->status === 'approved' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                    {{ $leave->status === 'rejected' ? 'bg-red-500/20 text-red-400' : '' }}
                    {{ $leave->status === 'cancelled' ? 'bg-slate-500/20 text-slate-400' : '' }}">
                    {{ ucfirst($leave->status) }}
                </span>
                <span class="text-sm text-slate-300">{{ $leave->leave_type_label ?? ucfirst($leave->leave_type) }} Leave</span>
            </div>
            @if($leave->status === 'pending')
                <div class="flex gap-2">
                    <form action="{{ route('admin.leaves.approve', $leave) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm transition-colors" onclick="return confirm('Approve this leave request?')">Approve</button>
                    </form>
                    <button onclick="openRejectModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">Reject</button>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Leave Details -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Leave Details</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-xs text-slate-400">Leave Type</span><span class="text-xs text-slate-100">{{ ucfirst($leave->leave_type) }}</span></div>
                <div class="flex justify-between"><span class="text-xs text-slate-400">Start Date</span><span class="text-xs text-slate-100">{{ $leave->start_date->format('M d, Y') }}</span></div>
                <div class="flex justify-between"><span class="text-xs text-slate-400">End Date</span><span class="text-xs text-slate-100">{{ $leave->end_date->format('M d, Y') }}</span></div>
                <div class="flex justify-between"><span class="text-xs text-slate-400">Total Days</span><span class="text-xs text-slate-100">{{ $leave->total_days }} day{{ $leave->total_days > 1 ? 's' : '' }}</span></div>
                @if($leave->reason)
                    <div class="border-t border-slate-700 pt-3">
                        <p class="text-xs text-slate-400 mb-1">Reason</p>
                        <p class="text-xs text-slate-100">{{ $leave->reason }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Employee Info -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Employee Information</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-xs text-slate-400">Name</span><a href="{{ route('admin.employees.show', $leave->employee) }}" class="text-xs text-rose-400 hover:text-rose-300">{{ $leave->employee->full_name ?? 'N/A' }}</a></div>
                <div class="flex justify-between"><span class="text-xs text-slate-400">Employee ID</span><span class="text-xs text-slate-100">{{ $leave->employee->employee_id ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-xs text-slate-400">Department</span><span class="text-xs text-slate-100">{{ $leave->employee->department->name ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-xs text-slate-400">Position</span><span class="text-xs text-slate-100">{{ $leave->employee->position ?? 'N/A' }}</span></div>
            </div>
        </div>
    </div>

    <!-- Approval Info -->
    @if($leave->approved_by || $leave->rejection_reason)
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-50 mb-4">Resolution</h3>
        <div class="space-y-3">
            @if($leave->approved_by)
                <div class="flex justify-between"><span class="text-xs text-slate-400">Approved By</span><span class="text-xs text-slate-100">{{ $leave->approvedBy->name ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-xs text-slate-400">Approved At</span><span class="text-xs text-slate-100">{{ $leave->approved_at?->format('M d, Y H:i') }}</span></div>
            @endif
            @if($leave->rejection_reason)
                <div class="border-t border-slate-700 pt-3">
                    <p class="text-xs text-slate-400 mb-1">Rejection Reason</p>
                    <p class="text-xs text-red-400">{{ $leave->rejection_reason }}</p>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Cancel for pending -->
    @if($leave->status === 'pending')
    <div class="flex gap-3">
        <form action="{{ route('admin.leaves.cancel', $leave) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-sm transition-colors" onclick="return confirm('Cancel this leave request?')">Cancel Request</button>
        </form>
        <form action="{{ route('admin.leaves.destroy', $leave) }}" method="POST" class="inline">
            @csrf @method('DELETE')
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors" onclick="return confirm('Delete this leave record?')">Delete</button>
        </form>
    </div>
    @endif
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50" onclick="if(event.target===this)closeRejectModal()">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 w-full max-w-md mx-4" onclick="event.stopPropagation()">
        <h3 class="text-lg font-semibold text-slate-50 mb-4">Reject Leave Request</h3>
        <form action="{{ route('admin.leaves.reject', $leave) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Reason for Rejection *</label>
                    <textarea name="rejection_reason" required rows="3"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Explain why this leave request is being rejected..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Confirm Rejection</button>
                    <button type="button" onclick="closeRejectModal()" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 py-2 px-4 rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal() { document.getElementById('rejectModal').classList.remove('hidden'); document.getElementById('rejectModal').classList.add('flex'); }
function closeRejectModal() { document.getElementById('rejectModal').classList.add('hidden'); document.getElementById('rejectModal').classList.remove('flex'); }
</script>
@endsection
