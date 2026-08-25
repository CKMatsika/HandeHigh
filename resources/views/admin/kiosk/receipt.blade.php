@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Sales Receipt</h1>
            <p class="text-xs text-slate-400 mt-1">Receipt #{{ $sale->receipt_number }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition">
                🖨️ Print
            </button>
            <a href="{{ route('admin.kiosk.pos') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3 py-1.5 rounded-lg text-xs transition">
                New Sale
            </a>
        </div>
    </div>

    @php
        $branding = $sale->school?->branding_data ?? [
            'name' => 'Hande High School',
            'formatted_address' => '',
            'formatted_contacts' => '',
            'logo_url' => null,
            'logo_base64' => null,
        ];
    @endphp

    <!-- Printable Receipt Box -->
    <div class="bg-slate-900/90 rounded-xl border border-slate-800 p-6 text-xs text-slate-300 space-y-4 font-mono shadow-xl">
        <div class="text-center pb-3 border-b border-slate-800 space-y-1 font-sans">
            @if(!empty($branding['logo_base64']) || !empty($branding['logo_url']))
                <div class="flex justify-center mb-1">
                    <img src="{{ $branding['logo_base64'] ?: $branding['logo_url'] }}" alt="{{ $branding['name'] }}" class="h-10 w-10 object-contain">
                </div>
            @endif
            <h2 class="text-base font-bold text-slate-100 uppercase tracking-wider">{{ $branding['name'] }}</h2>
            <p class="text-xs text-slate-400">Tuckshop & Kiosk Department</p>
            @if(!empty($branding['formatted_contacts']))
                <p class="text-[10px] text-slate-500">{{ $branding['formatted_contacts'] }}</p>
            @endif
            <p class="text-[11px] text-slate-500">{{ now()->format('d M Y H:i:s') }}</p>
        </div>

        <div class="flex justify-between text-[11px]">
            <span>Receipt: <strong class="text-slate-100">{{ $sale->receipt_number }}</strong></span>
            <span>Date: {{ $sale->sale_date->format('Y-m-d') }}</span>
        </div>

        <div class="flex justify-between text-[11px]">
            <span>Customer: {{ $sale->customer_name ?? 'Walk-in' }}</span>
            <span>Cashier: {{ $sale->cashier?->name ?? 'Staff' }}</span>
        </div>

        <div class="border-t border-b border-slate-800 py-3 my-2">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-800/60 pb-1">
                        <th class="pb-1">Item</th>
                        <th class="text-center pb-1">Qty</th>
                        <th class="text-right pb-1">Price</th>
                        <th class="text-right pb-1">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/30">
                    @foreach($sale->items as $item)
                        <tr>
                            <td class="py-1.5 text-slate-100">{{ $item->product?->name ?? 'Product' }}</td>
                            <td class="py-1.5 text-center text-slate-300">{{ $item->quantity }}</td>
                            <td class="py-1.5 text-right text-slate-300">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-1.5 text-right font-semibold text-slate-100">${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space-y-1.5 text-right text-xs">
            <div class="flex justify-between">
                <span class="text-slate-400">Subtotal:</span>
                <span>${{ number_format($sale->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm font-bold text-emerald-400 border-t border-slate-800 pt-2">
                <span>Grand Total:</span>
                <span>${{ number_format($sale->grand_total, 2) }}</span>
            </div>
            <div class="flex justify-between text-[11px] text-slate-400">
                <span>Payment Method:</span>
                <span class="capitalize">{{ str_replace('_', ' ', $sale->payment_method) }}</span>
            </div>
        </div>

        @if($sale->journalBatch)
            <div class="pt-3 border-t border-slate-800 text-[10px] text-slate-500 font-sans">
                ✓ GL Journal Batch #{{ $sale->journalBatch->id }} automatically posted (DR Cash / CR 5803 Canteen Revenue).
            </div>
        @endif
    </div>
</div>
@endsection
