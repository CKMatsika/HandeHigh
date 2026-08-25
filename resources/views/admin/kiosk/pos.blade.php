@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6" x-data="kioskPos()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">🛒 Point-of-Sale Terminal</h1>
            <p class="text-xs text-slate-400 mt-1">Direct sales checkout with real-time stock decrement and cashbook recognition</p>
        </div>
        <a href="{{ route('admin.kiosk.index') }}" class="text-slate-400 hover:text-slate-200 text-sm">
            &larr; Sales Dashboard
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-lg text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Products Grid (8 cols) -->
        <div class="lg:col-span-7 flex flex-col gap-4">
            <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-4">
                <input type="text" x-model="search" placeholder="🔍 Search product by name or code..."
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($products as $product)
                    <button type="button"
                        x-show="matchesSearch('{{ strtolower($product->name) }}', '{{ strtolower($product->code) }}')"
                        @click="addItem({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->unit_price }}, {{ $product->stock_quantity }})"
                        class="bg-slate-900/80 hover:bg-slate-800/80 border border-slate-800 hover:border-slate-700 rounded-xl p-4 text-left transition flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-mono text-slate-400 block">{{ $product->code }}</span>
                            <h4 class="text-sm font-semibold text-slate-100 mt-1 line-clamp-2">{{ $product->name }}</h4>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-sm font-bold text-emerald-400">${{ number_format($product->unit_price, 2) }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full {{ $product->stock_quantity > 0 ? 'bg-slate-800 text-slate-400' : 'bg-red-500/10 text-red-400' }}">
                                Stock: {{ $product->stock_quantity }}
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Checkout Cart (5 cols) -->
        <div class="lg:col-span-5">
            <form method="POST" action="{{ route('admin.kiosk.sales.store') }}" class="bg-slate-900/80 rounded-xl border border-slate-800 p-5 flex flex-col justify-between">
                @csrf

                <div>
                    <h3 class="text-sm font-semibold text-slate-100 mb-4 pb-2 border-b border-slate-800 flex items-center justify-between">
                        <span>Current Cart</span>
                        <span class="text-xs text-slate-400" x-text="cart.length + ' items'"></span>
                    </h3>

                    <!-- Cart Items List -->
                    <div class="space-y-3 max-h-72 overflow-y-auto mb-4">
                        <template x-for="(item, index) in cart" :key="item.id">
                            <div class="bg-slate-800/50 rounded-lg p-3 flex items-center justify-between text-xs">
                                <div class="flex-1 pr-2">
                                    <span class="font-medium text-slate-100 block" x-text="item.name"></span>
                                    <span class="text-slate-400 text-[11px]" x-text="'$' + item.price.toFixed(2) + ' each'"></span>
                                    <input type="hidden" :name="'items[' + index + '][kiosk_product_id]'" :value="item.id">
                                    <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.qty">
                                    <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.price">
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="decrementQty(index)" class="h-6 w-6 rounded bg-slate-700 text-slate-200 hover:bg-slate-600 flex items-center justify-center font-bold">-</button>
                                    <span class="font-bold text-slate-100 px-1" x-text="item.qty"></span>
                                    <button type="button" @click="incrementQty(index)" class="h-6 w-6 rounded bg-slate-700 text-slate-200 hover:bg-slate-600 flex items-center justify-center font-bold">+</button>
                                    <span class="font-semibold text-emerald-400 w-14 text-right" x-text="'$' + (item.price * item.qty).toFixed(2)"></span>
                                    <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-300 ml-1">✕</button>
                                </div>
                            </div>
                        </template>

                        <div x-show="cart.length === 0" class="py-12 text-center text-slate-500 text-xs">
                            Cart is currently empty. Click items on the left to add.
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="space-y-3 pt-3 border-t border-slate-800 text-xs">
                        <div>
                            <label class="block text-slate-400 mb-1">Customer / Student Name (Optional)</label>
                            <input type="text" name="customer_name" placeholder="Walk-in Student"
                                class="w-full px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs">
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1">Payment Method *</label>
                            <select name="payment_method" required class="w-full px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs">
                                <option value="cash">Cash (Petty Cash 1102)</option>
                                <option value="mobile_money">EcoCash / Mobile Money (1303)</option>
                                <option value="bank_transfer">Bank / Card Swipe (1301)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Totals & Checkout Button -->
                <div class="pt-4 mt-4 border-t border-slate-800">
                    <div class="flex justify-between items-center text-sm mb-4">
                        <span class="text-slate-400">Total Payable:</span>
                        <span class="text-xl font-bold text-emerald-400" x-text="'$' + total.toFixed(2)"></span>
                    </div>

                    <button type="submit" :disabled="cart.length === 0"
                        class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-medium py-2.5 rounded-lg text-sm transition">
                        ✓ Complete Checkout & Post Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function kioskPos() {
    return {
        search: '',
        cart: [],
        matchesSearch(name, code) {
            if (!this.search) return true;
            const q = this.search.toLowerCase();
            return name.includes(q) || code.includes(q);
        },
        addItem(id, name, price, stock) {
            const existing = this.cart.find(item => item.id === id);
            if (existing) {
                if (existing.qty < stock) {
                    existing.qty++;
                } else {
                    alert('Cannot add more than available inventory stock (' + stock + ').');
                }
            } else {
                if (stock > 0) {
                    this.cart.push({ id, name, price: parseFloat(price), qty: 1, stock });
                } else {
                    alert('Item is currently out of stock.');
                }
            }
        },
        incrementQty(index) {
            const item = this.cart[index];
            if (item.qty < item.stock) {
                item.qty++;
            } else {
                alert('Cannot exceed stock limit (' + item.stock + ').');
            }
        },
        decrementQty(index) {
            if (this.cart[index].qty > 1) {
                this.cart[index].qty--;
            } else {
                this.removeItem(index);
            }
        },
        removeItem(index) {
            this.cart.splice(index, 1);
        },
        get total() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        }
    }
}
</script>
@endsection
