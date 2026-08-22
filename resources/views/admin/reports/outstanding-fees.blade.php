@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.finance-dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Financial Reports</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-300">Outstanding Fees</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">Outstanding Fees Register</h1>
            <p class="text-xs text-slate-400 mt-0.5">Student fee balances, charge totals, and collection progress</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reports.outstanding-fees.export', request()->query()) }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 shadow-lg shadow-emerald-900/30 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <form method="GET" action="{{ route('admin.reports.outstanding-fees') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
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
                        <option value="class" {{ request('group_by') == 'class' ? 'selected' : '' }}>Class</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                <span class="text-[11px] text-slate-500">Showing {{ $total_students }} debtor students</span>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.reports.outstanding-fees') }}" class="px-3 py-1.5 text-xs text-slate-400 hover:text-slate-200 transition">Reset</a>
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="text-xs text-slate-400 font-medium">Students with Arrears</div>
            <div class="text-2xl font-bold text-slate-100 mt-1">{{ $total_students }}</div>
            <div class="text-xs text-slate-500 mt-1">Active debtor accounts</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="text-xs text-slate-400 font-medium">Total Charges Billed</div>
            <div class="text-2xl font-bold text-slate-100 mt-1">${{ number_format($total_charges, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">Cumulative invoices</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="text-xs text-slate-400 font-medium">Total Paid to Date</div>
            <div class="text-2xl font-bold text-emerald-400 mt-1">${{ number_format($total_paid, 2) }}</div>
            <div class="text-xs text-emerald-500 mt-1">{{ $overall_collection_rate }}% recovered</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="text-xs text-slate-400 font-medium">Total Arrears Balance</div>
            <div class="text-2xl font-bold text-red-400 mt-1">${{ number_format($total_outstanding, 2) }}</div>
            <div class="text-xs text-red-500/80 mt-1">Unpaid fee balance</div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            @if($group_by !== 'none' && $grouped_data->isNotEmpty())
                <div class="divide-y divide-slate-800">
                    @foreach($grouped_data as $group)
                        <div class="p-4 bg-slate-950/60">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                                <h3 class="text-sm font-bold text-blue-400 flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    {{ $group['group_label'] }} ({{ $group['student_count'] }} students)
                                </h3>
                                <div class="flex items-center gap-4 text-xs font-mono">
                                    <span class="text-slate-400">Billed: ${{ number_format($group['total_charges'], 2) }}</span>
                                    <span class="text-emerald-400">Paid: ${{ number_format($group['total_paid'], 2) }}</span>
                                    <span class="text-red-400 font-bold">Outstanding: ${{ number_format($group['total_outstanding'], 2) }}</span>
                                </div>
                            </div>

                            <table class="w-full text-xs">
                                <thead class="text-slate-400 bg-slate-900/80">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium">Admission #</th>
                                        <th class="px-3 py-2 text-left font-medium">Student Name</th>
                                        <th class="px-3 py-2 text-left font-medium">Form / Class</th>
                                        <th class="px-3 py-2 text-center font-medium">Invoices</th>
                                        <th class="px-3 py-2 text-right font-medium">Total Charges</th>
                                        <th class="px-3 py-2 text-right font-medium">Total Paid</th>
                                        <th class="px-3 py-2 text-right font-medium text-red-400">Outstanding</th>
                                        <th class="px-3 py-2 text-right font-medium">Collection %</th>
                                        <th class="px-3 py-2 text-right font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/50">
                                    @foreach($group['rows'] as $row)
                                        <tr class="hover:bg-slate-800/30 transition">
                                            <td class="px-3 py-2.5 font-mono text-slate-400">{{ $row['admission_number'] }}</td>
                                            <td class="px-3 py-2.5 font-medium text-slate-200">{{ $row['student_name'] }}</td>
                                            <td class="px-3 py-2.5 text-slate-400">{{ $row['form'] }} ({{ $row['class_name'] }})</td>
                                            <td class="px-3 py-2.5 text-center text-slate-400">{{ $row['invoice_count'] }}</td>
                                            <td class="px-3 py-2.5 text-right font-mono text-slate-300">${{ number_format($row['total_charges'], 2) }}</td>
                                            <td class="px-3 py-2.5 text-right font-mono text-emerald-400">${{ number_format($row['total_paid'], 2) }}</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-red-400">${{ number_format($row['outstanding'], 2) }}</td>
                                            <td class="px-3 py-2.5 text-right font-mono text-slate-300">{{ $row['collection_rate'] }}%</td>
                                            <td class="px-3 py-2.5 text-right">
                                                @if($row['student_id'])
                                                    <a href="{{ route('admin.reports.student-statement', ['student_id' => $row['student_id']]) }}" class="text-blue-400 hover:text-blue-300">Statement &rarr;</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            @else
                <table class="w-full text-xs">
                    <thead class="text-slate-300 bg-slate-950/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Admission #</th>
                            <th class="px-4 py-3 text-left font-medium">Student Name</th>
                            <th class="px-4 py-3 text-left font-medium">Form / Class</th>
                            <th class="px-4 py-3 text-center font-medium">Invoices</th>
                            <th class="px-4 py-3 text-right font-medium">Total Charges</th>
                            <th class="px-4 py-3 text-right font-medium">Total Paid</th>
                            <th class="px-4 py-3 text-right font-medium text-red-400">Outstanding</th>
                            <th class="px-4 py-3 text-right font-medium">Collection %</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($rows as $row)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="px-4 py-3 font-mono text-slate-400">{{ $row['admission_number'] }}</td>
                                <td class="px-4 py-3 font-medium text-slate-200">{{ $row['student_name'] }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $row['form'] }} ({{ $row['class_name'] }})</td>
                                <td class="px-4 py-3 text-center text-slate-400">{{ $row['invoice_count'] }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-300">${{ number_format($row['total_charges'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-400">${{ number_format($row['total_paid'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-red-400">${{ number_format($row['outstanding'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-300">{{ $row['collection_rate'] }}%</td>
                                <td class="px-4 py-3 text-right">
                                    @if($row['student_id'])
                                        <a href="{{ route('admin.reports.student-statement', ['student_id' => $row['student_id']]) }}" class="text-blue-400 hover:text-blue-300">Statement &rarr;</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-slate-500">No outstanding fee balances found matching the specified criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot class="bg-slate-950 font-bold text-slate-200 border-t-2 border-slate-800">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-left">Totals</td>
                                <td class="px-4 py-3 text-right font-mono">${{ number_format($total_charges, 2) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-400 font-mono">${{ number_format($total_paid, 2) }}</td>
                                <td class="px-4 py-3 text-right text-red-400 font-mono text-sm">${{ number_format($total_outstanding, 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ $overall_collection_rate }}%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
