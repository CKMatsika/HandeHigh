<!-- Employees & Non-Teaching Staff Reports Component -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 backdrop-blur-xl p-6 space-y-6 shadow-xl relative overflow-hidden" id="employees-reports-section">
    <!-- Subtle Ambient Glow -->
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Section Header & Source Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </span>
                <h2 class="text-xl font-bold text-slate-50 tracking-tight">Employees Reports</h2>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20 font-medium">Non-Teaching & Administrative Staff</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Staff establishment, departmental distribution, employment status, and payroll summary</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-slate-200 text-xs font-medium transition shadow-sm">
                <span>Manage Employees</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
            <a href="{{ route('admin.departments.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-purple-700/50 bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 text-xs font-medium transition shadow-sm">
                <span>Departments</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Employees -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50 hover:border-slate-700 transition cursor-pointer group"
             onclick="LeadershipDrilldown.open('employees', { status: 'active' }, 'Active Staff Members', 'All currently active non-teaching staff', null, '{{ route('admin.employees.index', ['status' => 'active']) }}')">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400 group-hover:text-purple-400 transition">Total Staff</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-400 font-semibold border border-purple-500/20">Drill down ↗</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-50">{{ number_format($employeesReport['total_employees']) }}</span>
                <span class="text-xs text-emerald-400 font-medium">{{ $employeesReport['active_employees'] }} Active</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">{{ $employeesReport['on_leave_employees'] }} On Leave</p>
        </div>

        <!-- Full-Time vs Contract -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Employment Contract</span>
                <span class="text-[10px] text-slate-500">Terms</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <button type="button" 
                        class="text-left hover:opacity-80 transition group"
                        onclick="LeadershipDrilldown.open('employees', { employment_type: 'full_time' }, 'Full-Time Staff', 'Employees with permanent/full-time contracts', null, '{{ route('admin.employees.index', ['employment_type' => 'full_time']) }}')">
                    <span class="text-sm font-bold text-indigo-400 group-hover:underline">{{ $employeesReport['employment_types']['full_time'] }}</span>
                    <span class="text-[11px] text-slate-400 block">Full-Time</span>
                </button>
                <div class="h-6 w-px bg-slate-800"></div>
                <button type="button" 
                        class="text-right hover:opacity-80 transition group"
                        onclick="LeadershipDrilldown.open('employees', { employment_type: 'contract' }, 'Contract Staff', 'Employees on fixed-term contracts', null, '{{ route('admin.employees.index', ['employment_type' => 'contract']) }}')">
                    <span class="text-sm font-bold text-amber-400 group-hover:underline">{{ $employeesReport['employment_types']['contract'] }}</span>
                    <span class="text-[11px] text-slate-400 block">Contract</span>
                </button>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden flex">
                @php
                    $totEmp = max(1, $employeesReport['total_employees']);
                    $ftPct = round(($employeesReport['employment_types']['full_time'] / $totEmp) * 100);
                    $ctPct = round(($employeesReport['employment_types']['contract'] / $totEmp) * 100);
                @endphp
                <div class="bg-indigo-500 h-1.5" style="width: {{ $ftPct }}%"></div>
                <div class="bg-amber-500 h-1.5" style="width: {{ $ctPct }}%"></div>
            </div>
        </div>

        <!-- Monthly Payroll Estimate -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Monthly Payroll</span>
                <span class="text-[10px] text-emerald-400">Salaries</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-400">${{ number_format($employeesReport['monthly_payroll'], 2) }}</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Active staff salary total</p>
        </div>

        <!-- Departments Count -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Departments</span>
                <span class="text-[10px] text-purple-400">Units</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-purple-400">{{ count($employeesReport['by_department']) }}</span>
                <span class="text-xs text-slate-400">Active Units</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Across all school operations</p>
        </div>
    </div>

    <!-- Departmental Breakdown Cards -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-200">Departmental Staffing & Payroll</h3>
            <span class="text-xs text-slate-500">Click department to drill down to employees</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($employeesReport['by_department'] as $dept)
                <div class="p-4 rounded-xl border border-slate-800/80 bg-slate-950/50 hover:bg-slate-850 hover:border-slate-700 transition cursor-pointer group flex flex-col justify-between"
                     onclick='LeadershipDrilldown.open("employees", { department_id: "{{ $dept['id'] }}" }, "{{ $dept['name'] }} Staff", "Staff members assigned to {{ $dept['name'] }}", @json($dept['employees']), "{{ route('admin.employees.index', ['department_id' => $dept['id']]) }}")'>
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-slate-100 group-hover:text-purple-400 transition">{{ $dept['name'] }}</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $dept['active_count'] }} of {{ $dept['count'] }} active</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-lg bg-purple-500/10 text-purple-300 font-bold text-xs border border-purple-500/20">
                            {{ $dept['count'] }}
                        </span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-800/60 flex items-center justify-between text-[11px]">
                        <span class="text-slate-400">Monthly Payroll:</span>
                        <span class="font-bold text-emerald-400">${{ number_format($dept['salary_total'], 2) }}</span>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-500 italic py-4 text-center col-span-3">No department staff records found</p>
            @endforelse
        </div>
    </div>

    <!-- Searchable Employees Roster Table -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-200">Staff Member Roster</h3>
            <span class="text-xs text-slate-400">Quick source drill-down to employee files</span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/40">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="text-slate-400 bg-slate-950/80 uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Staff Name</th>
                        <th class="px-4 py-3">Employee ID</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3 text-center">Type</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($employeesReport['roster'] as $eItem)
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold text-xs">
                                        {{ substr($eItem['name'], 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-100">{{ $eItem['name'] }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $eItem['email'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-400">{{ $eItem['employee_id'] }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 font-medium">
                                    {{ $eItem['department_name'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $eItem['position'] }}</td>
                            <td class="px-4 py-3 text-center text-slate-400">{{ $eItem['employment_type'] }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($eItem['employment_status'] === 'Active')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-400">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/20 text-amber-400">{{ $eItem['employment_status'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ $eItem['view_url'] }}" class="inline-flex items-center gap-1 text-cyan-400 hover:text-cyan-300 font-medium">
                                    <span>Profile</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500 italic">No employee records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
