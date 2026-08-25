@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Tuckshop & Kiosk Sales</h1>
            <p class="text-xs text-slate-400 mt-1">Retail point-of-sale, stock tracking, and automated revenue accounting</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.kiosk.products') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-2 rounded-lg text-sm transition">
                Inventory Products
            </a>
            <a href="{{ route('admin.kiosk.pos') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm transition font-medium">
                🛒 Open POS Terminal
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">Today's Total Sales</span>
            <span class="text-2xl font-bold text-emerald-400">${{ number_format($todayTotal, 2) }}</span>
            <span class="text-xs text-slate-400 block mt-1">Direct Cashbook & GL Credited</span>
        </div>

        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-2">This Month's Revenue</span>
            <span class="text-2xl font-bold text-blue-400">${{ number_format($monthTotal, 2) }}</span>
            <span class="text-xs text-slate-400 block mt-1">Account 5803 Canteen Sales</span>
        </div>

        <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5 flex flex-col justify-between">
            <div>
                <span class="text-xs text-slate-400 uppercase tracking-wider block mb-1">Quick Point-of-Sale</span>
                <p class="text-xs text-slate-300">Ring up student snack and stationery purchases instantly.</p>
            </div>
            <a href="{{ route('admin.kiosk.pos') }}" class="text-xs font-semibold text-emerald-400 hover:underline mt-2">
                Launch Point of Sale &rarr;
            </a>
        </div>
    </div>

    <!-- Filters & Sales History -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 overflow-hidden">
        <div class="p-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
            <h3 class="text-sm font-semibold text-slate-100">Sales Transactions</h3>
            <form method="GET" action="{{ route('admin.kiosk.index') }}" class="flex gap-2 text-xs">
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="px-2 py-1 border border-slate-700 rounded bg-slate-800 text-slate-200">
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                    class="px-2 py-1 border border-slate-700 rounded bg-slate-800 text-slate-200">
                <button type="submit" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 px-3 py-1 rounded text-slate-200">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-xs">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Receipt #</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Customer / Notes</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Payment</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Cashier</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-300">Grand Total</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-mono font-semibold text-blue-400">{{ $sale->receipt_number }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $sale->sale_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $sale->customer_name ?? 'Walk-in / Student' }}</td>
                            <td class="px-4 py-3 capitalize text-slate-300">{{ str_replace('_', ' ', $sale->payment_method) }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $sale->cashier?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-100">${{ number_format($sale->grand_total, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.kiosk.sales.show', $sale) }}" class="text-blue-400 hover:text-blue-300 font-medium">
                                    Receipt &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                No sales recorded for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $sales->links() }}
    </div>
</div>
@endsection
