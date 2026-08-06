@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.credit-notes.index') }}" class="text-slate-400 hover:text-slate-200 transition">← Credit Notes</a>
                <span class="text-slate-600">/</span>
                <h1 class="text-lg font-semibold text-slate-50">{{ $creditNote->credit_note_number }}</h1>
            </div>
            <div class="flex items-center gap-2">
                @if($creditNote->status === 'draft')
                    <a href="{{ route('admin.credit-notes.edit', $creditNote) }}"
                       class="px-3 py-1.5 text-xs bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg transition">Edit</a>
                    <form method="POST" action="{{ route('admin.credit-notes.issue', $creditNote) }}" class="inline">
                        @csrf
                        <button class="px-3 py-1.5 text-xs bg-amber-700 hover:bg-amber-600 text-white rounded-lg transition">
                            Issue & Post to Ledger
                        </button>
                    </form>
                @endif
                @if(in_array($creditNote->status, ['draft','issued']) && $creditNote->balance > 0)
                    <button onclick="document.getElementById('applyModal').classList.remove('hidden')"
                            class="px-3 py-1.5 text-xs bg-green-700 hover:bg-green-600 text-white rounded-lg transition">
                        Apply to Invoice
                    </button>
                @endif
                @if(in_array($creditNote->status, ['draft','issued']))
                    <form method="POST" action="{{ route('admin.credit-notes.cancel', $creditNote) }}" class="inline"
                          onsubmit="return confirm('Cancel this credit note?')">
                        @csrf
                        <button class="px-3 py-1.5 text-xs bg-red-900 hover:bg-red-800 text-red-300 rounded-lg transition">Cancel</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-200 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-200 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        {{-- Status Banner --}}
        @php
            $banners = [
                'draft'     => ['bg'=>'bg-slate-800/80','text'=>'text-slate-300','icon'=>'📄','label'=>'Draft — not yet posted to the general ledger.'],
                'issued'    => ['bg'=>'bg-amber-900/30','text'=>'text-amber-300','icon'=>'📤','label'=>'Issued — posted to the general ledger. Available balance to apply.'],
                'applied'   => ['bg'=>'bg-green-900/30','text'=>'text-green-300','icon'=>'✅','label'=>'Fully Applied — entire credit has been used.'],
                'cancelled' => ['bg'=>'bg-red-900/30','text'=>'text-red-400','icon'=>'🚫','label'=>'Cancelled.'],
            ];
            $b = $banners[$creditNote->status] ?? $banners['draft'];
        @endphp
        <div class="{{ $b['bg'] }} border border-slate-700 rounded-xl px-4 py-3 mb-6 text-sm {{ $b['text'] }}">
            {{ $b['icon'] }} {{ $b['label'] }}
        </div>

        {{-- Details Card --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 mb-6">
            <h2 class="text-sm font-semibold text-slate-300 mb-4">Credit Note Details</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs text-slate-500 mb-0.5">Credit Note #</dt>
                    <dd class="text-indigo-300 font-mono">{{ $creditNote->credit_note_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 mb-0.5">Date</dt>
                    <dd class="text-slate-200">{{ $creditNote->credit_note_date->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 mb-0.5">Type</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $creditNote->type==='student' ? 'bg-blue-900/40 text-blue-300' : 'bg-purple-900/40 text-purple-300' }}">
                            {{ ucfirst($creditNote->type) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 mb-0.5">Recipient</dt>
                    <dd class="text-slate-200">
                        @if($creditNote->type === 'student' && $creditNote->student)
                            {{ $creditNote->student->first_name }} {{ $creditNote->student->last_name }}
                        @elseif($creditNote->customer)
                            {{ $creditNote->customer->name }}
                        @else —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 mb-0.5">Linked Invoice</dt>
                    <dd class="text-slate-200 font-mono">{{ $creditNote->invoice?->invoice_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 mb-0.5">Created By</dt>
                    <dd class="text-slate-200">{{ $creditNote->creator?->name ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Amounts --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-400 mb-1">Total Amount</p>
                <p class="text-xl font-bold text-slate-100">{{ number_format($creditNote->total_amount, 2) }}</p>
            </div>
            <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-400 mb-1">Applied</p>
                <p class="text-xl font-bold text-green-400">{{ number_format($creditNote->applied_amount, 2) }}</p>
            </div>
            <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-400 mb-1">Available Balance</p>
                <p class="text-xl font-bold {{ $creditNote->balance > 0 ? 'text-amber-400' : 'text-slate-400' }}">
                    {{ number_format($creditNote->balance, 2) }}
                </p>
            </div>
        </div>

        {{-- Reason & Notes --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-sm font-semibold text-slate-300 mb-3">Reason & Notes</h2>
            <p class="text-slate-200 text-sm mb-4 whitespace-pre-line">{{ $creditNote->reason }}</p>
            @if($creditNote->notes)
                <p class="text-slate-400 text-xs whitespace-pre-line border-t border-slate-800 pt-3">{{ $creditNote->notes }}</p>
            @endif
        </div>
    </div>

    {{-- Apply to Invoice Modal --}}
    <div id="applyModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-slate-100 font-semibold mb-4">Apply Credit to Invoice</h3>
            <form method="POST" action="{{ route('admin.credit-notes.apply', $creditNote) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs text-slate-400 mb-1">Invoice *</label>
                    <select name="invoice_id" required
                            class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select Invoice —</option>
                        @foreach(\App\Models\Invoice::where('school_id', $creditNote->school_id)->whereIn('status',['sent','partial','overdue'])->get() as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->invoice_number }} ({{ number_format($inv->total_amount,2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-5">
                    <label class="block text-xs text-slate-400 mb-1">Amount to Apply * (max {{ number_format($creditNote->balance, 2) }})</label>
                    <input type="number" name="apply_amount" step="0.01" min="0.01"
                           max="{{ $creditNote->balance }}" value="{{ $creditNote->balance }}"
                           class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('applyModal').classList.add('hidden')"
                            class="px-4 py-2 text-sm text-slate-400 hover:text-slate-200 transition">Cancel</button>
                    <button type="submit"
                            class="bg-green-700 hover:bg-green-600 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                        Apply Credit
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
