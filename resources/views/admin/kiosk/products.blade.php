@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Tuckshop Products & Inventory</h1>
            <p class="text-xs text-slate-400 mt-1">Manage retail items, pricing, and live inventory levels</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.kiosk.index') }}" class="text-slate-400 hover:text-slate-200 text-sm">
                &larr; Sales Dashboard
            </a>
            <a href="{{ route('admin.kiosk.pos') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm transition font-medium">
                🛒 POS Terminal
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Add Product Form Card -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-5">
        <h3 class="text-sm font-semibold text-slate-100 mb-3">Add New Inventory Item</h3>
        <form method="POST" action="{{ route('admin.kiosk.products.store') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Code *</label>
                <input type="text" name="code" placeholder="e.g. SNK-01" required
                    class="w-full px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Product Name *</label>
                <input type="text" name="name" placeholder="e.g. Fresh Milk 500ml, Pen Blue" required
                    class="w-full px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Category *</label>
                <select name="category" required class="w-full px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs">
                    <option value="snacks">Snacks & Drinks</option>
                    <option value="stationery">Stationery</option>
                    <option value="toiletries">Toiletries</option>
                    <option value="general">General</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Selling Price ($) *</label>
                <input type="number" step="0.01" name="unit_price" placeholder="1.50" required
                    class="w-full px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Initial Stock *</label>
                <input type="number" name="stock_quantity" value="50" min="0" required
                    class="w-full px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="md:col-span-6 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2 rounded-lg text-xs font-medium transition">
                    + Add Product
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-xs">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Code</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-300">Category</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-300">Price</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-300">Stock Qty</th>
                        <th class="px-4 py-3 text-center font-medium text-slate-300">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-mono text-slate-300">{{ $product->code }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-100">{{ $product->name }}</td>
                            <td class="px-4 py-3 capitalize text-slate-300">{{ $product->category }}</td>
                            <td class="px-4 py-3 text-right font-medium text-emerald-400">${{ number_format($product->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-bold {{ $product->stock_quantity <= 5 ? 'text-red-400' : 'text-slate-200' }}">
                                    {{ $product->stock_quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $product->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-400' }}">
                                    {{ $product->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                No products created in inventory yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $products->links() }}
    </div>
</div>
@endsection
