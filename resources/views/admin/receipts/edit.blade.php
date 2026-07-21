@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Receipt {{ $receipt->receipt_number }}</h1>
            <p class="text-xs text-slate-400 mt-1">Update receipt information</p>
        </div>
        <a href="{{ route('admin.receipts.show', $receipt) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Cancel</a>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6">
        <form action="{{ route('admin.receipts.update', $receipt) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="receipt_date" class="block text-xs font-medium text-slate-300 mb-2">Receipt Date *</label>
                        <input type="date" id="receipt_date" name="receipt_date" value="{{ old('receipt_date', $receipt->receipt_date->format('Y-m-d')) }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        @error('receipt_date')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-xs font-medium text-slate-300 mb-2">Receipt Type *</label>
                        <select id="type" name="type" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="sale" {{ old('type', $receipt->type) == 'sale' ? 'selected' : '' }}>Sale</option>
                            <option value="service" {{ old('type', $receipt->type) == 'service' ? 'selected' : '' }}>Service</option>
                            <option value="other" {{ old('type', $receipt->type) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_id" class="block text-xs font-medium text-slate-300 mb-2">Customer</label>
                        <select id="customer_id" name="customer_id"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Select customer (optional)</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $receipt->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="customer_name" class="block text-xs font-medium text-slate-300 mb-2">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $receipt->customer_name) }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="Walk-in customer name">
                        @error('customer_name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="payment_method" class="block text-xs font-medium text-slate-300 mb-2">Payment Method *</label>
                        <select id="payment_method" name="payment_method" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="cash" {{ old('payment_method', $receipt->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank_transfer" {{ old('payment_method', $receipt->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="check" {{ old('payment_method', $receipt->payment_method) == 'check' ? 'selected' : '' }}>Check</option>
                            <option value="credit_card" {{ old('payment_method', $receipt->payment_method) == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                            <option value="mobile_money" {{ old('payment_method', $receipt->payment_method) == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference" class="block text-xs font-medium text-slate-300 mb-2">Reference</label>
                        <input type="text" id="reference" name="reference" value="{{ old('reference', $receipt->reference) }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="Transaction reference">
                        @error('reference')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="Receipt description...">{{ old('description', $receipt->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-700 pt-4">
                    <h3 class="text-sm font-medium text-slate-200 mb-4">Receipt Items</h3>
                    
                    <div id="items-container" class="space-y-4">
                        @foreach($receipt->items as $index => $item)
                            <div class="item-row bg-slate-800/50 rounded-lg p-4" data-index="{{ $index }}">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-medium text-slate-300">Item {{ $index + 1 }}</span>
                                    @if($loop->last)
                                        <button type="button" onclick="removeItem(this)" class="text-red-400 hover:text-red-300 text-xs">Remove</button>
                                    @endif
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-300 mb-1">Description *</label>
                                        <input type="text" name="items[{{ $index }}][description]" value="{{ old("items.$index.description", $item->description) }}" required
                                               class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                               placeholder="Item description">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-slate-300 mb-1">Category</label>
                                        <input type="text" name="items[{{ $index }}][category]" value="{{ old("items.$index.category", $item->category) }}"
                                               class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                               placeholder="Category">
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-300 mb-1">Quantity *</label>
                                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ old("items.$index.quantity", $item->quantity) }}" required min="1" step="0.01"
                                               class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                               placeholder="1">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-slate-300 mb-1">Unit Price ($) *</label>
                                        <input type="number" name="items[{{ $index }}][unit_price]" value="{{ old("items.$index.unit_price", $item->unit_price) }}" required min="0" step="0.01"
                                               class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                               placeholder="0.00">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-slate-300 mb-1">Tax Rate (%)</label>
                                        <input type="number" name="items[{{ $index }}][tax_rate]" value="{{ old("items.$index.tax_rate", $item->tax_rate) }}" min="0" max="100" step="0.01"
                                               class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <button type="button" onclick="addItem()" class="mt-3 px-3 py-1.5 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 transition">
                        + Add Item
                    </button>
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <a href="{{ route('admin.receipts.show', $receipt) }}" class="rounded-full bg-slate-700 px-4 py-2 text-xs font-medium text-white hover:bg-slate-600 transition">Cancel</a>
                    <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Update Receipt</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let itemIndex = {{ $receipt->items->count() }};

        function addItem() {
            const container = document.getElementById('items-container');
            const itemHtml = `
                <div class="item-row bg-slate-800/50 rounded-lg p-4" data-index="${itemIndex}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-slate-300">Item ${itemIndex + 1}</span>
                        <button type="button" onclick="removeItem(this)" class="text-red-400 hover:text-red-300 text-xs">Remove</button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Description *</label>
                            <input type="text" name="items[${itemIndex}][description]" required
                                   class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                   placeholder="Item description">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Category</label>
                            <input type="text" name="items[${itemIndex}][category]"
                                   class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                   placeholder="Category">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Quantity *</label>
                            <input type="number" name="items[${itemIndex}][quantity]" required min="1" step="0.01"
                                   class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                   placeholder="1">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Unit Price ($) *</label>
                            <input type="number" name="items[${itemIndex}][unit_price]" required min="0" step="0.01"
                                   class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                   placeholder="0.00">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Tax Rate (%)</label>
                            <input type="number" name="items[${itemIndex}][tax_rate]" min="0" max="100" step="0.01"
                                   class="w-full px-2 py-1.5 bg-slate-700 border border-slate-600 rounded text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                   placeholder="0">
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', itemHtml);
            itemIndex++;
        }

        function removeItem(button) {
            const itemRow = button.closest('.item-row');
            itemRow.remove();
        }
    </script>
@endsection
