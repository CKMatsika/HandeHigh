<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><p class="text-xs text-slate-400">Full Name</p><p class="text-sm text-slate-100">{{ $employee->full_name }}</p></div>
                <div><p class="text-xs text-slate-400">Email</p><p class="text-sm text-slate-100">{{ $employee->email }}</p></div>
                <div><p class="text-xs text-slate-400">Phone</p><p class="text-sm text-slate-100">{{ $employee->phone }}</p></div>
                <div><p class="text-xs text-slate-400">Date of Birth</p><p class="text-sm text-slate-100">{{ $employee->date_of_birth?->format('M d, Y') }}</p></div>
                <div><p class="text-xs text-slate-400">Address</p><p class="text-sm text-slate-100">{{ $employee->address ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Emergency Contact</p><p class="text-sm text-slate-100">{{ $employee->emergency_contact ?? '—' }} {{ $employee->emergency_phone ? "({$employee->emergency_phone})" : '' }}</p></div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Employment Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><p class="text-xs text-slate-400">Department</p><p class="text-sm text-slate-100">{{ $employee->department->name ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Position</p><p class="text-sm text-slate-100">{{ $employee->position }}</p></div>
                <div><p class="text-xs text-slate-400">Type</p><p class="text-sm text-slate-100">{{ ucfirst(str_replace('_', ' ', $employee->employment_type)) }}</p></div>
                <div><p class="text-xs text-slate-400">Hire Date</p><p class="text-sm text-slate-100">{{ $employee->hire_date->format('M d, Y') }}</p></div>
                <div><p class="text-xs text-slate-400">Work Schedule</p><p class="text-sm text-slate-100">{{ $employee->work_schedule ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Salary</p><p class="text-sm text-slate-100">${{ number_format($employee->salary, 2) }}</p></div>
            </div>
            @if($employee->termination_date)
                <div class="border-t border-slate-700 mt-4 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><p class="text-xs text-red-400">Termination Date</p><p class="text-sm text-slate-100">{{ $employee->termination_date->format('M d, Y') }}</p></div>
                        <div><p class="text-xs text-red-400">Reason</p><p class="text-sm text-slate-100">{{ $employee->termination_reason }}</p></div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <p class="text-xs text-slate-400 mb-1">Employee ID</p>
            <p class="text-lg font-semibold text-slate-50">{{ $employee->employee_id }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <p class="text-xs text-slate-400 mb-1">Status</p>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium
                {{ $employee->employment_status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                {{ $employee->employment_status === 'terminated' ? 'bg-red-500/20 text-red-400' : '' }}
                {{ $employee->employment_status === 'resigned' ? 'bg-amber-500/20 text-amber-400' : '' }}
                {{ $employee->employment_status === 'on_leave' ? 'bg-blue-500/20 text-blue-400' : '' }}">
                {{ ucfirst(str_replace('_', ' ', $employee->employment_status)) }}
            </span>
        </div>
        @if($employee->user)
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <p class="text-xs text-slate-400 mb-1">User Account</p>
                <p class="text-sm text-emerald-400">{{ $employee->user->email }}</p>
            </div>
        @endif
        @php
            $activeLoansCount = $employee->activeLoans->count();
            $activeLeaves = $employee->leaves->where('status', 'approved')->where('end_date', '>=', now())->count();
        @endphp
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-center">
                <p class="text-xl font-semibold text-amber-400">{{ $activeLoansCount }}</p>
                <p class="text-[10px] text-slate-400">Active Loans</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-center">
                <p class="text-xl font-semibold text-blue-400">{{ $activeLeaves }}</p>
                <p class="text-[10px] text-slate-400">On Leave</p>
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <p class="text-xs text-slate-400 mb-1">Qualification Count</p>
            <p class="text-lg font-semibold text-slate-50">{{ $employee->qualifications->count() }}</p>
        </div>
    </div>
</div>
