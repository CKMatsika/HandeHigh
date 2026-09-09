@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-50">Process Zimbabwean Multi-Currency Payroll</h1>
        <p class="text-xs text-slate-400 mt-1">Enter earnings and deductions in USD and ZWG. ZIMRA PAYE progressive brackets, 3% AIDS Levy, 4.5% NSSA, NEC CBA rates, and 50% Medical Aid tax credits calculate automatically.</p>
    </div>

    <form method="POST" action="{{ route('admin.payrolls.process') }}">
        @csrf
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 mb-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Payroll Period & Date
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Month</label>
                    <select name="period_month" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:ring-1 focus:ring-indigo-500" required>
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Year</label>
                    <select name="period_year" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:ring-1 focus:ring-indigo-500" required>
                        @foreach(range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Processed Date</label>
                    <input type="date" name="processed_date" value="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:ring-1 focus:ring-indigo-500" required>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-medium text-slate-400 mb-1">Notes / Period Description</label>
                <textarea name="notes" rows="2" placeholder="Optional notes for this payroll run..." class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs"></textarea>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-50">Employee Earnings & Deductions (USD & ZWG)</h3>
                <span class="text-xs text-slate-400">{{ $employees->count() }} active employees</span>
            </div>

            @if($employees->isEmpty())
                <div class="p-12 text-center text-slate-400">
                    <p class="text-sm">No active employees found. Add employees first.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-800/70 text-slate-300">
                            <tr>
                                <th class="px-3 py-3 text-left sticky left-0 bg-slate-900 z-10">Employee / Statutory</th>
                                <th class="px-2 py-3 text-center bg-slate-800/90 border-r border-slate-700 font-semibold text-slate-100" colspan="3">USD Earnings ($)</th>
                                <th class="px-2 py-3 text-center bg-slate-800/50 border-r border-slate-700 font-semibold text-slate-200" colspan="2">ZWG Earnings</th>
                                <th class="px-2 py-3 text-center bg-slate-800/90 border-r border-slate-700 font-semibold text-cyan-300">Medical Aid</th>
                                <th class="px-2 py-3 text-center bg-slate-800/50 font-semibold text-rose-300" colspan="2">Other Deductions</th>
                            </tr>
                            <tr class="bg-slate-850 text-[11px] text-slate-400 border-b border-slate-700">
                                <th class="px-3 py-2 text-left sticky left-0 bg-slate-900">Name & ID</th>
                                <th class="px-2 py-2 text-left">Basic ($)</th>
                                <th class="px-2 py-2 text-left">Allowances ($)</th>
                                <th class="px-2 py-2 text-left border-r border-slate-700">Bonus/OT ($)</th>
                                <th class="px-2 py-2 text-left">Basic (ZWG)</th>
                                <th class="px-2 py-2 text-left border-r border-slate-700">Allowances (ZWG)</th>
                                <th class="px-2 py-2 text-left border-r border-slate-700">USD / ZWG (50% Tax Credit)</th>
                                <th class="px-2 py-2 text-left">Loan ($ / ZWG)</th>
                                <th class="px-2 py-2 text-left">Other ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($employees as $emp)
                                <tr class="hover:bg-slate-800/50 transition">
                                    <td class="px-3 py-3 text-slate-50 font-medium sticky left-0 bg-slate-900 z-10 min-w-[180px]">
                                        <input type="hidden" name="employees[{{ $loop->index }}][id]" value="{{ $emp->id }}">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" checked onchange="this.closest('tr').querySelectorAll('input[type=number]').forEach(i => i.disabled = !this.checked)" class="rounded bg-slate-700 border-slate-600 text-emerald-600">
                                            <div>
                                                <p class="text-xs font-semibold text-slate-100">{{ $emp->first_name }} {{ $emp->last_name }}</p>
                                                <p class="text-[10px] text-slate-400">{{ $emp->department?->name ?? 'Staff' }} • {{ $emp->nec_sector_code ?? 'NEC-EDU' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- USD Earnings -->
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][basic_salary_usd]" value="{{ $emp->salary ?? 0 }}" class="w-20 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][housing_allowance_usd]" value="0" placeholder="0.00" class="w-20 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-2 py-2 border-r border-slate-700">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][bonus_usd]" value="0" placeholder="0.00" class="w-20 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>

                                    <!-- ZWG Earnings -->
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][basic_salary_zwg]" value="0" placeholder="0.00" class="w-24 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-2 py-2 border-r border-slate-700">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][housing_allowance_zwg]" value="0" placeholder="0.00" class="w-20 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>

                                    <!-- Medical Aid (USD & ZWG) -->
                                    <td class="px-2 py-2 border-r border-slate-700">
                                        <div class="flex gap-1">
                                            <input type="number" step="0.01" name="employees[{{ $loop->index }}][medical_aid_usd]" value="{{ $emp->medical_aid_usd ?? 0 }}" placeholder="USD" title="USD Premium (50% Tax Credit)" class="w-16 px-1.5 py-1 bg-slate-800 border border-slate-700 rounded text-cyan-300 text-xs">
                                            <input type="number" step="0.01" name="employees[{{ $loop->index }}][medical_aid_zwg]" value="{{ $emp->medical_aid_zwg ?? 0 }}" placeholder="ZWG" title="ZWG Premium (50% Tax Credit)" class="w-16 px-1.5 py-1 bg-slate-800 border border-slate-700 rounded text-cyan-300 text-xs">
                                        </div>
                                    </td>

                                    <!-- Loans & Other Deductions -->
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][loan_repayment_usd]" value="0" placeholder="0.00" class="w-18 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" name="employees[{{ $loop->index }}][other_deductions_usd]" value="0" placeholder="0.00" class="w-18 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-slate-50 text-xs">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between mt-6">
            <p class="text-xs text-slate-400">
                <strong class="text-slate-300">Statutory Notice:</strong> ZIMRA PAYE bands, 3% AIDS Levy, 4.5% NSSA (capped), and NEC CBA rates are automatically processed according to statutory rate tables.
            </p>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-6 py-2.5 text-xs font-semibold text-white hover:bg-emerald-700 shadow-md transition" {{ $employees->isEmpty() ? 'disabled' : '' }}>
                Process Compliant Payroll
            </button>
        </div>
    </form>
</div>
@endsection
