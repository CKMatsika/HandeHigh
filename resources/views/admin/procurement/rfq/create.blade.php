@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('admin.procurement.rfq.index') }}" class="text-xs text-indigo-300 hover:text-indigo-200">&larr; Back to RFQs</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-50">Create Request for Quotation (RFQ)</h1>
            <p class="mt-1 text-sm text-slate-400">Invite vendors to submit competitive quotations for school requisitions or inventory replenishment.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-700/60 bg-rose-950/40 p-4 text-sm text-rose-200">
            <p class="font-semibold">Please correct the following errors:</p>
            <ul class="mt-1 list-disc pl-5 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.procurement.rfq.store') }}" class="space-y-6" id="rfqForm">
        @csrf

        {{-- Basic Information Card --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-5">
            <h2 class="text-base font-semibold text-slate-100 border-b border-slate-800 pb-3">1. RFQ Details</h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">RFQ Title / Subject *</label>
                    <input type="text" name="title" value="{{ old('title', $selectedRequisition ? 'Tender for ' . $selectedRequisition->requisition_number . ' - ' . $selectedRequisition->purpose : '') }}" required placeholder="e.g. Supply of Chemistry Lab Glassware & Reagents (Term 3)" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Link Approved Requisition (Optional)</label>
                    <select name="procurement_requisition_id" id="requisitionSelect" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">-- Direct RFQ / No Requisition --</option>
                        @foreach($approvedRequisitions as $req)
                            <option value="{{ $req->id }}" {{ (string) old('procurement_requisition_id', $selectedRequisition?->id) === (string) $req->id ? 'selected' : '' }}>
                                {{ $req->requisition_number }} - {{ Str::limit($req->purpose, 45) }} ({{ $req->department?->name ?? 'General' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-slate-500">Linking an approved requisition associates this tender with original department requests.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Submission Deadline *</label>
                    <input type="date" name="submission_deadline" value="{{ old('submission_deadline', now()->addDays(7)->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" required class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <p class="mt-1 text-[11px] text-slate-500">Suppliers must submit quotes before 17:00 on this date.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Terms, Conditions & Delivery Requirements</label>
                    <textarea name="terms_and_conditions" rows="3" placeholder="e.g. Delivery required within 14 days of PO. Payment terms: 30 days after GRN inspection. All prices inclusive of VAT." class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ old('terms_and_conditions') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Invited Vendors Card --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-100">2. Invited Suppliers / Vendors</h2>
                    <p class="text-xs text-slate-400">Select which registered vendors will be notified and invited to bid.</p>
                </div>
                <span class="text-xs text-indigo-300 font-medium">Optional target selection</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-56 overflow-y-auto p-1">
                @forelse($vendors as $vendor)
                    <label class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3 hover:border-slate-700 cursor-pointer">
                        <input type="checkbox" name="vendor_ids[]" value="{{ $vendor->id }}" {{ in_array($vendor->id, old('vendor_ids', [])) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-200 truncate">{{ $vendor->name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ $vendor->email ?? $vendor->phone ?? 'No contact' }}</p>
                        </div>
                    </label>
                @empty
                    <div class="col-span-full py-4 text-center text-xs text-slate-500">
                        No active vendors registered. You can add quotes from any vendor later.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Line Items Card --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-100">3. Items to Quote</h2>
                    <p class="text-xs text-slate-400">Specify inventory items, required quantities, and technical specifications.</p>
                </div>
                <button type="button" id="addItemBtn" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Item Line
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="itemsTable">
                    <thead class="bg-slate-950/60 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-4 py-3 min-w-[260px]">Inventory Item *</th>
                            <th class="px-4 py-3 w-36">Quantity *</th>
                            <th class="px-4 py-3 min-w-[220px]">Specifications / Instructions</th>
                            <th class="px-4 py-3 w-12 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800" id="itemsTableBody">
                        @php
                            $defaultItems = [];
                            if ($selectedRequisition && $selectedRequisition->items) {
                                foreach ($selectedRequisition->items as $ri) {
                                    $defaultItems[] = [
                                        'inventory_item_id' => $ri->inventory_item_id,
                                        'quantity' => (float) $ri->quantity_requested,
                                        'specifications' => $ri->notes,
                                    ];
                                }
                            }
                            $formItems = old('items', $defaultItems);
                            if (empty($formItems)) {
                                $formItems = [['inventory_item_id' => '', 'quantity' => 1, 'specifications' => '']];
                            }
                        @endphp

                        @foreach($formItems as $index => $line)
                            <tr class="item-row" data-index="{{ $index }}">
                                <td class="px-4 py-3">
                                    <select name="items[{{ $index }}][inventory_item_id]" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500">
                                        <option value="">-- Select Item --</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" {{ (string) ($line['inventory_item_id'] ?? '') === (string) $item->id ? 'selected' : '' }}>
                                                {{ $item->name }} ({{ $item->code }}) [{{ $item->unit_of_measure }}]
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" name="items[{{ $index }}][specifications]" value="{{ $line['specifications'] ?? '' }}" placeholder="e.g. Borosilicate 3.3, 500ml" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500">
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" class="remove-row-btn text-slate-500 hover:text-rose-400 p-1" title="Remove line">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('admin.procurement.rfq.index') }}" class="rounded-lg border border-slate-700 bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-700">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none">Save RFQ as Draft</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('itemsTableBody');
    const addBtn = document.getElementById('addItemBtn');
    let rowIndex = {{ count($formItems) }};

    const itemOptionsHtml = `@foreach($items as $item)<option value="{{ $item->id }}">{{ addslashes($item->name) }} ({{ $item->code }}) [{{ $item->unit_of_measure }}]</option>@endforeach`;

    addBtn.addEventListener('click', function () {
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.dataset.index = rowIndex;
        tr.innerHTML = `
            <td class="px-4 py-3">
                <select name="items[${rowIndex}][inventory_item_id]" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500">
                    <option value="">-- Select Item --</option>
                    ${itemOptionsHtml}
                </select>
            </td>
            <td class="px-4 py-3">
                <input type="number" step="0.0001" min="0.0001" name="items[${rowIndex}][quantity]" value="1" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500">
            </td>
            <td class="px-4 py-3">
                <input type="text" name="items[${rowIndex}][specifications]" placeholder="e.g. Technical grade or brand specs" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500">
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" class="remove-row-btn text-slate-500 hover:text-rose-400 p-1" title="Remove line">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </td>
        `;
        tableBody.appendChild(tr);
        rowIndex++;
    });

    tableBody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row-btn');
        if (btn) {
            const rows = tableBody.querySelectorAll('.item-row');
            if (rows.length > 1) {
                btn.closest('.item-row').remove();
            } else {
                alert('At least one item line is required for an RFQ.');
            }
        }
    });

    // Handle reload when changing linked requisition
    const reqSelect = document.getElementById('requisitionSelect');
    reqSelect.addEventListener('change', function () {
        if (this.value) {
            window.location.href = "{{ route('admin.procurement.rfq.create') }}?requisition_id=" + this.value;
        }
    });
});
</script>
@endsection
