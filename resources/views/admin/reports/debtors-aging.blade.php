@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.finance-dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Financial Reports</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-300">Debtors Aging</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">Debtors Aging Analysis</h1>
            <p class="text-xs text-slate-400 mt-0.5">As of {{ $as_of_date }} | Grouped by: {{ ucfirst($group_by) }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reports.debtors-aging.export', request()->query()) }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 shadow-lg shadow-emerald-900/30 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <form method="GET" action="{{ route('admin.reports.debtors-aging') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Academic Year</label>
                    <select name="academic_year" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Years</option>
                        @foreach($available_years as $year)
                            <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Term</label>
                    <select name="term" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Terms</option>
                        <option value="Term 1" {{ request('term') == 'Term 1' ? 'selected' : '' }}>Term 1</option>
                        <option value="Term 2" {{ request('term') == 'Term 2' ? 'selected' : '' }}>Term 2</option>
                        <option value="Term 3" {{ request('term') == 'Term 3' ? 'selected' : '' }}>Term 3</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Form / Grade</label>
                    <select name="form" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Forms</option>
                        @foreach($available_forms as $f)
                            <option value="{{ $f }}" {{ request('form') == $f ? 'selected' : '' }}>{{ $f }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Class</label>
                    <select name="class_name" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Classes</option>
                        @foreach($available_classes as $c)
                            <option value="{{ $c }}" {{ request('class_name') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Group By</label>
                    <select name="group_by" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                        <option value="none" {{ request('group_by') == 'none' ? 'selected' : '' }}>Flat List</option>
                        <option value="form" {{ request('group_by', 'form') == 'form' ? 'selected' : '' }}>Form / Grade</option>
                        <option value="class" {{ request('group_by') == 'class' ? 'selected' : '' }}>Class Stream</option>
                        <option value="student" {{ request('group_by') == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="fee_type" {{ request('group_by') == 'fee_type' ? 'selected' : '' }}>Fee Type</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">As Of Date</label>
                    <input type="date" name="as_of_date" value="{{ request('as_of_date', $as_of_date) }}" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                <div class="text-[11px] text-slate-500">
                    Showing {{ $rows->count() }} overdue/unpaid invoice records
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.reports.debtors-aging') }}" class="px-3 py-1.5 text-xs text-slate-400 hover:text-slate-200 transition">Reset Filters</a>
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Aging Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-slate-400 font-medium">Total Invoiced</div>
            <div class="text-base font-bold text-slate-100 mt-1">${{ number_format($totals['total_invoiced'], 2) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Original charges</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-emerald-400 font-medium">Current (Not Due)</div>
            <div class="text-base font-bold text-emerald-300 mt-1">${{ number_format($totals['current'], 2) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ $bucket_counts['current'] }} invoices</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-teal-400 font-medium">1–30 Days</div>
            <div class="text-base font-bold text-teal-300 mt-1">${{ number_format($totals['bucket_1_30'], 2) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ $bucket_counts['1_30'] }} invoices</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-yellow-400 font-medium">31–60 Days</div>
            <div class="text-base font-bold text-yellow-300 mt-1">${{ number_format($totals['bucket_31_60'], 2) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ $bucket_counts['31_60'] }} invoices</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-orange-400 font-medium">61–90 Days</div>
            <div class="text-base font-bold text-orange-300 mt-1">${{ number_format($totals['bucket_61_90'], 2) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ $bucket_counts['61_90'] }} invoices</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-red-400 font-medium">91–120 Days</div>
            <div class="text-base font-bold text-red-300 mt-1">${{ number_format($totals['bucket_91_120'], 2) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ $bucket_counts['91_120'] }} invoices</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="text-xs text-red-500 font-medium">120+ Days</div>
            <div class="text-base font-bold text-red-400 mt-1">${{ number_format($totals['bucket_120_plus'], 2) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ $bucket_counts['120_plus'] }} invoices</div>
        </div>
    </div>

    <!-- Outstanding Debt Banner -->
    <div class="rounded-2xl border border-red-900/30 bg-red-950/20 p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-red-500/20 text-red-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-200">Total Outstanding Debt</span>
                <p class="text-[11px] text-slate-400">Total Invoiced: ${{ number_format($totals['total_invoiced'], 2) }} | Paid: ${{ number_format($totals['total_paid'], 2) }} | Credit Adjustments: ${{ number_format($totals['total_credit'], 2) }}</p>
            </div>
        </div>
        <div class="text-xl font-bold text-red-400 font-mono">
            ${{ number_format($totals['total_outstanding'], 2) }}
        </div>
    </div>

    <!-- Data Matrix Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            @if($group_by !== 'none' && $grouped_data->isNotEmpty())
                <!-- Grouped View -->
                <div class="divide-y divide-slate-800">
                    @foreach($grouped_data as $group)
                        <div class="p-4 bg-slate-950/60">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                                <h3 class="text-sm font-bold text-blue-400 flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    {{ $group['group_label'] }} ({{ count($group['rows']) }} invoices)
                                </h3>
                                <div class="flex items-center gap-4 text-xs font-mono">
                                    <span class="text-slate-400">Invoiced: ${{ number_format($group['total_invoiced'], 2) }}</span>
                                    <span class="text-emerald-400">Paid: ${{ number_format($group['total_paid'], 2) }}</span>
                                    <span class="text-red-400 font-bold">Outstanding: ${{ number_format($group['total_outstanding'], 2) }}</span>
                                </div>
                            </div>

                            <table class="w-full text-xs">
                                <thead class="text-slate-400 bg-slate-900/80">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium">Invoice #</th>
                                        <th class="px-3 py-2 text-left font-medium">Student / Adm #</th>
                                        <th class="px-3 py-2 text-left font-medium">Due Date</th>
                                        <th class="px-3 py-2 text-right font-medium">Original</th>
                                        <th class="px-3 py-2 text-right font-medium">Paid</th>
                                        <th class="px-3 py-2 text-right font-medium text-emerald-400">Current</th>
                                        <th class="px-3 py-2 text-right font-medium text-teal-400">1–30d</th>
                                        <th class="px-3 py-2 text-right font-medium text-yellow-400">31–60d</th>
                                        <th class="px-3 py-2 text-right font-medium text-orange-400">61–90d</th>
                                        <th class="px-3 py-2 text-right font-medium text-red-400">91–120d</th>
                                        <th class="px-3 py-2 text-right font-medium text-red-500">120+d</th>
                                        <th class="px-3 py-2 text-right font-medium text-red-400">Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/50">
                                    @foreach($group['rows'] as $row)
                                        <tr class="hover:bg-slate-800/30 transition">
                                            <td class="px-3 py-2 font-mono font-medium text-blue-400">{{ $row['invoice_number'] }}</td>
                                            <td class="px-3 py-2 text-slate-200">
                                                {{ $row['student_name'] }}
                                                <span class="text-[10px] text-slate-500 font-mono block">{{ $row['admission_number'] }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-slate-400">
                                                {{ $row['due_date'] }}
                                                @if($row['days_overdue'] > 0)
                                                    <span class="text-[10px] text-red-400/80 block">{{ $row['days_overdue'] }}d overdue</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right text-slate-300 font-mono">${{ number_format($row['original_amount'], 2) }}</td>
                                            <td class="px-3 py-2 text-right text-emerald-400 font-mono">${{ number_format($row['paid_amount'], 2) }}</td>
                                            <td class="px-3 py-2 text-right text-slate-400 font-mono">{{ $row['current_amount'] > 0 ? '$' . number_format($row['current_amount'], 2) : '-' }}</td>
                                            <td class="px-3 py-2 text-right text-slate-400 font-mono">{{ $row['bucket_1_30_amount'] > 0 ? '$' . number_format($row['bucket_1_30_amount'], 2) : '-' }}</td>
                                            <td class="px-3 py-2 text-right text-slate-400 font-mono">{{ $row['bucket_31_60_amount'] > 0 ? '$' . number_format($row['bucket_31_60_amount'], 2) : '-' }}</td>
                                            <td class="px-3 py-2 text-right text-slate-400 font-mono">{{ $row['bucket_61_90_amount'] > 0 ? '$' . number_format($row['bucket_61_90_amount'], 2) : '-' }}</td>
                                            <td class="px-3 py-2 text-right text-slate-400 font-mono">{{ $row['bucket_91_120_amount'] > 0 ? '$' . number_format($row['bucket_91_120_amount'], 2) : '-' }}</td>
                                            <td class="px-3 py-2 text-right text-slate-400 font-mono">{{ $row['bucket_120_plus_amount'] > 0 ? '$' . number_format($row['bucket_120_plus_amount'], 2) : '-' }}</td>
                                            <td class="px-3 py-2 text-right font-bold text-red-400 font-mono">${{ number_format($row['outstanding'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Flat Table View -->
                <table class="w-full text-xs">
                    <thead class="text-slate-300 bg-slate-950/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Invoice #</th>
                            <th class="px-4 py-3 text-left font-medium">Student / Adm #</th>
                            <th class="px-4 py-3 text-left font-medium">Form / Class</th>
                            <th class="px-4 py-3 text-left font-medium">Due Date</th>
                            <th class="px-4 py-3 text-right font-medium">Original</th>
                            <th class="px-4 py-3 text-right font-medium">Paid</th>
                            <th class="px-4 py-3 text-right font-medium text-emerald-400">Current</th>
                            <th class="px-4 py-3 text-right font-medium text-teal-400">1–30d</th>
                            <th class="px-4 py-3 text-right font-medium text-yellow-400">31–60d</th>
                            <th class="px-4 py-3 text-right font-medium text-orange-400">61–90d</th>
                            <th class="px-4 py-3 text-right font-medium text-red-400">91–120d</th>
                            <th class="px-4 py-3 text-right font-medium text-red-500">120+d</th>
                            <th class="px-4 py-3 text-right font-medium text-red-400">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($rows as $row)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="px-4 py-3 font-mono font-medium text-blue-400">{{ $row['invoice_number'] }}</td>
                                <td class="px-4 py-3 text-slate-200">
                                    {{ $row['student_name'] }}
                                    <span class="text-[10px] text-slate-500 font-mono block">{{ $row['admission_number'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-400">{{ $row['form'] }} ({{ $row['class_name'] }})</td>
                                <td class="px-4 py-3 text-slate-400">
                                    {{ $row['due_date'] }}
                                    @if($row['days_overdue'] > 0)
                                        <span class="text-[10px] text-red-400/80 block">{{ $row['days_overdue'] }}d overdue</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-300 font-mono">${{ number_format($row['original_amount'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-400 font-mono">${{ number_format($row['paid_amount'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 font-mono">{{ $row['current_amount'] > 0 ? '$' . number_format($row['current_amount'], 2) : '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 font-mono">{{ $row['bucket_1_30_amount'] > 0 ? '$' . number_format($row['bucket_1_30_amount'], 2) : '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 font-mono">{{ $row['bucket_31_60_amount'] > 0 ? '$' . number_format($row['bucket_31_60_amount'], 2) : '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 font-mono">{{ $row['bucket_61_90_amount'] > 0 ? '$' . number_format($row['bucket_61_90_amount'], 2) : '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 font-mono">{{ $row['bucket_91_120_amount'] > 0 ? '$' . number_format($row['bucket_91_120_amount'], 2) : '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 font-mono">{{ $row['bucket_120_plus_amount'] > 0 ? '$' . number_format($row['bucket_120_plus_amount'], 2) : '-' }}</td>
                                <td class="px-4 py-3 text-right font-bold text-red-400 font-mono">${{ number_format($row['outstanding'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-4 py-8 text-center text-slate-500">
                                    No overdue or outstanding invoices match the selected criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot class="bg-slate-950 font-bold text-slate-200 border-t-2 border-slate-800">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-left">Grand Totals</td>
                                <td class="px-4 py-3 text-right font-mono">${{ number_format($totals['total_invoiced'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-400 font-mono">${{ number_format($totals['total_paid'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-300 font-mono">${{ number_format($totals['current'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-teal-300 font-mono">${{ number_format($totals['bucket_1_30'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-yellow-300 font-mono">${{ number_format($totals['bucket_31_60'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-orange-300 font-mono">${{ number_format($totals['bucket_61_90'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-red-300 font-mono">${{ number_format($totals['bucket_91_120'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-red-400 font-mono">${{ number_format($totals['bucket_120_plus'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-red-400 font-mono">${{ number_format($totals['total_outstanding'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
