@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Bulk Create Invoices</h1>
            <p class="text-xs text-slate-400 mt-1">Create multiple invoices at once for selected students.</p>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-950/60 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back to Invoices
        </a>
    </div>

    <form method="POST" action="{{ route('admin.invoices.bulk.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Invoice Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="academic_year">Academic Year</label>
                    <select id="academic_year" name="academic_year" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="term">Term</label>
                    <select id="term" name="term" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                        @foreach($terms as $term)
                            <option value="{{ $term }}">{{ $term }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="issued_at">Issue Date</label>
                    <input type="date" id="issued_at" name="issued_at" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                </div>
                
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Select Students</h2>
            
            <div class="max-h-60 overflow-y-auto space-y-2">
                @foreach($enrollments->groupBy('academic_year')->sortKeys() as $year => $yearEnrollments)
                    <div class="border-l-2 border-slate-700 pl-3">
                        <h3 class="text-xs font-medium text-slate-300 mb-2">{{ $year }}</h3>
                        
                        @foreach($yearEnrollments->groupBy('term')->sortKeys() as $term => $termEnrollments)
                            <div class="ml-3 mb-2">
                                <h4 class="text-[11px] text-slate-400 mb-1">{{ $term }}</h4>
                                
                                @foreach($termEnrollments as $enrollment)
                                    <div class="flex items-center space-x-2 py-1">
                                        <input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->id }}" 
                                               class="rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0">
                                        <label class="text-xs text-slate-300">
                                            {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }} 
                                            (Grade {{ $enrollment->grade }} - {{ $enrollment->class_name }})
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-medium text-slate-50 mb-4">Fee Items</h2>
            
            <div id="fee-items" class="space-y-3">
                <div class="fee-item grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300">Description</label>
                        <input type="text" name="fee_items[0][description]" required 
                               class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100"
                               placeholder="e.g., Tuition Fees">
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300">Category</label>
                        <select name="fee_items[0][category]" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                            <option value="tuition">Tuition</option>
                            <option value="boarding">Boarding</option>
                            <option value="transport">Transport</option>
                            <option value="activities">Activities</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300">Amount</label>
                        <input type="number" name="fee_items[0][amount]" step="0.01" min="0" required 
                               class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100"
                               placeholder="0.00">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="button" onclick="removeFeeItem(this)" 
                                class="rounded-full bg-red-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-600 transition">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
            
            <button type="button" onclick="addFeeItem()" 
                    class="mt-3 rounded-full border border-slate-700 bg-slate-950/60 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                Add Fee Item
            </button>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.invoices.index') }}" 
               class="rounded-full border border-slate-700 bg-slate-950/60 px-6 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit" 
                    class="rounded-full bg-indigo-500 px-6 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Create Invoices
            </button>
        </div>
    </form>

    <script>
        let feeItemIndex = 1;
        
        function addFeeItem() {
            const container = document.getElementById('fee-items');
            const newItem = document.createElement('div');
            newItem.className = 'fee-item grid grid-cols-1 md:grid-cols-4 gap-3';
            newItem.innerHTML = `
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300">Description</label>
                    <input type="text" name="fee_items[${feeItemIndex}][description]" required 
                           class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100"
                           placeholder="e.g., Tuition Fees">
                </div>
                
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300">Category</label>
                    <select name="fee_items[${feeItemIndex}][category]" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100">
                        <option value="tuition">Tuition</option>
                        <option value="boarding">Boarding</option>
                        <option value="transport">Transport</option>
                        <option value="activities">Activities</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300">Amount</label>
                    <input type="number" name="fee_items[${feeItemIndex}][amount]" step="0.01" min="0" required 
                           class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-100"
                           placeholder="0.00">
                </div>
                
                <div class="flex items-end">
                    <button type="button" onclick="removeFeeItem(this)" 
                            class="rounded-full bg-red-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-600 transition">
                        Remove
                    </button>
                </div>
            `;
            container.appendChild(newItem);
            feeItemIndex++;
        }
        
        function removeFeeItem(button) {
            const item = button.closest('.fee-item');
            if (document.querySelectorAll('.fee-item').length > 1) {
                item.remove();
            }
        }
    </script>
@endsection
