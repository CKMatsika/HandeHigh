@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">New Receipt</h1>
            <p class="text-xs text-slate-400 mt-1">Record non-student income.</p>
        </div>
        <a href="{{ route('admin.receipts.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.receipts.store') }}" class="space-y-4 text-sm" id="receipt-form">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Date</label>
                    <input type="date" name="receipt_date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Type</label>
                    <select name="type" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                        <option value="sale">Sale</option>
                        <option value="service">Service</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="check">Check</option>
                        <option value="credit_card">Credit Card</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Customer (optional)</label>
                    <select name="customer_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                        <option value="">Walk-in</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Customer Name (if walk-in)</label>
                    <input name="customer_name" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Bank Account (if bank)</label>
                    <select name="bank_account_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                        <option value="">None</option>
                        @foreach($bankAccounts as $acct)
                            <option value="{{ $acct->id }}">{{ $acct->account_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/40">
                <div class="flex items-center justify-between px-4 py-3 text-xs text-slate-300">
                    <div class="font-semibold">Items</div>
                    <button type="button" onclick="addItem()" class="rounded-full border border-slate-700 px-3 py-1 hover:bg-slate-800/60">Add Line</button>
                </div>
                <div class="divide-y divide-slate-800" id="items-container">
                    @php $oldItems = old('items', [['description' => '', 'category' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0]]); @endphp
                    @foreach($oldItems as $i => $item)
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-2 px-4 py-3">
                            <div class="md:col-span-2">
                                <label class="block text-[11px] text-slate-400 mb-1">Description</label>
                                <input name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Category</label>
                                <input name="items[{{ $i }}][category]" value="{{ $item['category'] ?? '' }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Qty</label>
                                <input type="number" step="0.01" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Unit Price</label>
                                <input type="number" step="0.01" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Tax %</label>
                                <input type="number" step="0.01" name="items[{{ $i }}][tax_rate]" value="{{ $item['tax_rate'] ?? 0 }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.receipts.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Save</button>
            </div>
        </form>
    </div>

    <script>
        let itemIndex = {{ count($oldItems) }};
        function addItem() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 md:grid-cols-5 gap-2 px-4 py-3 border-t border-slate-800';
            row.innerHTML = `
                <div class="md:col-span-2">
                    <label class="block text-[11px] text-slate-400 mb-1">Description</label>
                    <input name="items[${itemIndex}][description]" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Category</label>
                    <input name="items[${itemIndex}][category]" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Qty</label>
                    <input type="number" step="0.01" name="items[${itemIndex}][quantity]" value="1" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Unit Price</label>
                    <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" value="0" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Tax %</label>
                    <input type="number" step="0.01" name="items[${itemIndex}][tax_rate]" value="0" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
            `;
            container.appendChild(row);
            itemIndex++;
        }
    </script>
@endsection

