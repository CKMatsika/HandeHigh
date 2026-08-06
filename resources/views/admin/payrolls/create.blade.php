@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-50">Process Payroll</h1>
        <p class="text-xs text-slate-400 mt-1">Enter earnings and deductions for each employee. PAYE, NSSA, and AIDS Levy are auto-calculated.</p>
    </div>

    <form method="POST" action="{{ route('admin.payrolls.process') }}">
        @csrf
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 mb-6">
            <h3 class="text-sm font-medium text-slate-50 mb-4">Payroll Period</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Month</label>
                    <select name="period_month" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Year</label>
                    <select name="period_year" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                        @foreach(range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Processed Date</label>
                    <input type="date" name="processed_date" value="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-medium text-slate-400 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs"></textarea>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-medium text-slate-50">Employee Payroll Entries</h3>
                <span class="text-xs text-slate-400">{{ $employees->count() }} active employees</span>
            </div>

            @if($employees->isEmpty())
                <div class="p-12 text-center text-slate-400">
                    <p class="text-sm">No active employees found. Add employees first.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300 sticky left-0 bg-slate-900">Employee</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Basic Salary</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Housing</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Transport</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Communication</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Education</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Leave</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">School Top-Up</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Bonus</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Overtime</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Trade Union</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">NEC</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-300">Loan Repayment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($employees as $emp)
                                <tr class="hover:bg-slate-800/50">
                                    <td class="px-3 py-2 text-slate-50 font-medium sticky left-0 bg-slate-900">
                                        <input type="hidden" name="employees[{{ $loop->index }}][id]" value="{{ $emp->id }}">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" checked onchange="this.closest('tr').querySelectorAll('input[type=number]').forEach(i => i.disabled = !this.checked)" class="rounded bg-slate-700 border-slate-600 text-emerald-600">
                                            <div>
                                                <p class="text-xs font-medium">{{ $emp->full_name }}</p>
                                                <p class="text-[10px] text-slate-400">{{ $emp->department?->name ?? 'No Dept' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][basic_salary]" value="{{ $emp->basic_salary ?? 0 }}" class="w-24 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][housing_allowance]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][transport_allowance]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][communication_allowance]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][education_allowance]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][leave_allowance]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][school_top_up]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][bonus]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][overtime]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][trade_union]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][nec]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][loan_repayment]" value="0" class="w-20 px-2 py-1.5 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end mt-6">
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-6 py-3 text-sm font-medium text-white hover:bg-emerald-700 transition-colors" {{ $employees->isEmpty() ? 'disabled' : '' }}>
                Process Payroll
            </button>
        </div>
    </form>
</div>
@endsection
