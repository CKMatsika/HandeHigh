@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.finance-dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Financial Reports</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-300">Fee Collections</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">Fee Collections Register</h1>
            <p class="text-xs text-slate-400 mt-0.5">Itemized transaction log of all payments and receipts received</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-800 text-slate-200 border border-slate-700 hover:bg-slate-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Register
            </button>
            <a href="{{ route('admin.reports.fee-collections.export', request()->query()) }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 shadow-lg shadow-emerald-900/30 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <form method="GET" action="{{ route('admin.reports.fee-collections') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">From Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date', $start_date) }}" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">To Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date', $end_date) }}" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Methods</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="ecocash" {{ request('payment_method') == 'ecocash' ? 'selected' : '' }}>EcoCash</option>
                        <option value="zipit" {{ request('payment_method') == 'zipit' ? 'selected' : '' }}>ZIPIT</option>
                        <option value="swipe" {{ request('payment_method') == 'swipe' ? 'selected' : '' }}>POS / Swipe</option>
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
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Academic Year</label>
                    <select name="academic_year" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Years</option>
                        @foreach($available_years as $yr)
                            <option value="{{ $yr }}" {{ request('academic_year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                <span class="text-[11px] text-slate-500">Showing {{ $transaction_count }} collection transactions</span>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.reports.fee-collections') }}" class="px-3 py-1.5 text-xs text-slate-400 hover:text-slate-200 transition">Reset</a>
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="text-xs text-slate-400 font-medium">Total Collections in Period</div>
            <div class="text-2xl font-bold text-emerald-400 mt-1">${{ number_format($total_collected, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $transaction_count }} recorded payments & receipts</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="text-xs text-slate-400 font-medium">Top Payment Method</div>
            @php $topMethod = $by_method->first(); @endphp
            <div class="text-xl font-bold text-slate-100 mt-1">{{ $topMethod ? $topMethod['method'] : 'N/A' }}</div>
            <div class="text-xs text-emerald-400 mt-1">{{ $topMethod ? '$' . number_format($topMethod['total'], 2) . ' (' . $topMethod['count'] . ' txns)' : '-' }}</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="text-xs text-slate-400 font-medium">Top Performing Form</div>
            @php $topForm = $by_form->first(); @endphp
            <div class="text-xl font-bold text-slate-100 mt-1">{{ $topForm ? $topForm['form'] : 'N/A' }}</div>
            <div class="text-xs text-blue-400 mt-1">{{ $topForm ? '$' . number_format($topForm['total'], 2) . ' (' . $topForm['count'] . ' txns)' : '-' }}</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="text-xs text-slate-400 font-medium">Average Collection / Txn</div>
            <div class="text-2xl font-bold text-slate-100 mt-1">
                ${{ $transaction_count > 0 ? number_format($total_collected / $transaction_count, 2) : '0.00' }}
            </div>
            <div class="text-xs text-slate-500 mt-1">Mean transaction value</div>
        </div>
    </div>

    <x-documents.school-header 
        :school="$school ?? null"
        title="Official Fee Collections & Revenue Audit Register"
        :subtitle="'Period: ' . $start_date . ' to ' . $end_date . ' · Total Collections: $' . number_format($total_collected, 2)"
        :date="now()"
    />

    <!-- Transactions Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-200">Collections Log ({{ $collections->count() }} Transactions)</h3>
            <span class="text-xs text-slate-400">Total: <strong class="text-emerald-400 font-mono">${{ number_format($total_collected, 2) }}</strong></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-950/60 text-slate-400 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Receipt / Ref</th>
                        <th class="px-4 py-3">Student Name</th>
                        <th class="px-4 py-3">Form / Class</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Fee Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Cashier</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($collections as $item)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3 text-slate-300 font-mono whitespace-nowrap">{{ $item['date'] }}</td>
                            <td class="px-4 py-3 font-mono font-medium text-blue-400 whitespace-nowrap">{{ $item['reference'] }}</td>
                            <td class="px-4 py-3 text-slate-200">
                                {{ $item['student_name'] }}
                                @if($item['admission_number'] !== 'N/A')
                                    <span class="text-[10px] text-slate-500 font-mono block">{{ $item['admission_number'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ $item['form'] }} ({{ $item['class_name'] }})</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $item['payment_method'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $item['fee_type'] }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-emerald-400">${{ number_format($item['amount'], 2) }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $item['cashier'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">No collection transactions found for the specified period and filters.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($collections->isNotEmpty())
                    <tfoot class="bg-slate-950 font-bold text-slate-200 border-t-2 border-slate-800">
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-left">Total Fee Collections</td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-400 text-sm">${{ number_format($total_collected, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <x-documents.school-footer 
        :school="$school ?? null"
        :show-banking="false"
        notice="Official financial fee collection register. Audited and generated from verified school financial journals."
    />
</div>
@endsection
