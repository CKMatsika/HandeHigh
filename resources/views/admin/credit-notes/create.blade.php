@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.credit-notes.index') }}" class="text-slate-400 hover:text-slate-200 transition">
                ← Credit Notes
            </a>
            <span class="text-slate-600">/</span>
            <h1 class="text-lg font-semibold text-slate-50">New Credit Note</h1>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-200 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <form method="POST" action="{{ route('admin.credit-notes.store') }}" x-data="creditNoteForm()">
                @csrf

                {{-- Type --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium text-slate-300 mb-2">Credit Note For *</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                            <input type="radio" name="type" value="student" x-model="type"
                                   class="accent-indigo-500" @change="clearRecipient()"> Student
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                            <input type="radio" name="type" value="customer" x-model="type"
                                   class="accent-indigo-500" @change="clearRecipient()"> Customer
                        </label>
                    </div>
                </div>

                {{-- Student --}}
                <div x-show="type === 'student'" x-cloak class="mb-5">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Student *</label>
                    <select name="student_id" class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select Student —</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>
                                {{ $s->first_name }} {{ $s->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer --}}
                <div x-show="type === 'customer'" x-cloak class="mb-5">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Customer *</label>
                    <select name="customer_id" class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select Customer —</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Linked Invoice --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Linked Invoice <span class="text-slate-500">(optional)</span></label>
                    <select name="invoice_id" class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">— None —</option>
                        @foreach($invoices as $inv)
                            <option value="{{ $inv->id }}" @selected(old('invoice_id') == $inv->id)>
                                {{ $inv->invoice_number }} ({{ number_format($inv->total_amount,2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-5">
                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Total Amount *</label>
                        <input type="number" name="total_amount" step="0.01" min="0.01"
                               value="{{ old('total_amount') }}"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    {{-- Date --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Credit Note Date *</label>
                        <input type="date" name="credit_note_date" value="{{ old('credit_note_date', date('Y-m-d')) }}"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Status --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Status *</label>
                    <select name="status" class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="draft"  @selected(old('status','draft')==='draft')>Draft (not yet posted)</option>
                        <option value="issued" @selected(old('status')==='issued')>Issue now (posts to ledger)</option>
                    </select>
                </div>

                {{-- Reason --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Reason *</label>
                    <textarea name="reason" rows="3"
                              class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                              placeholder="Reason for this credit note…">{{ old('reason') }}</textarea>
                </div>

                {{-- Notes --}}
                <div class="mb-6">
                    <label class="block text-xs font-medium text-slate-300 mb-1">Internal Notes <span class="text-slate-500">(optional)</span></label>
                    <textarea name="notes" rows="2"
                              class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                              placeholder="Additional notes…">{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.credit-notes.index') }}"
                       class="px-5 py-2 text-sm text-slate-400 hover:text-slate-200 transition">Cancel</a>
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-6 py-2 rounded-lg transition">
                        Create Credit Note
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function creditNoteForm() {
    return {
        type: '{{ old('type', 'student') }}',
        clearRecipient() {}
    };
}
</script>
@endpush
