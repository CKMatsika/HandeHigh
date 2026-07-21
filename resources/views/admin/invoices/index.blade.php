@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Invoices</h1>
            <p class="text-xs text-slate-400 mt-1">Track fee invoices, balances, and payment status.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 transition">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create
            </a>
            <a href="{{ route('admin.invoices.bulk.create') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Bulk Create
            </a>
            <a href="{{ route('admin.invoices.auto-generate') }}" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                Auto Generate
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="bg-emerald-900/50 border border-emerald-700 text-emerald-200 rounded-lg p-4 text-sm mb-4">
            {{ session('status') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.invoices.index') }}" class="mb-4 flex flex-wrap gap-3 text-xs text-slate-100">
        <div>
            <label class="block text-[11px] font-medium mb-1 text-slate-300" for="status">Status</label>
            <select id="status" name="status" class="w-40 rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                <option value="">All</option>
                @foreach($statusOptions as $s)
                    <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Filter
            </button>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        @if($invoices->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Invoice</th>
                        <th class="text-left py-2 font-medium">Student</th>
                        <th class="text-left py-2 font-medium">Guardian</th>
                        <th class="text-left py-2 font-medium">Year / Term</th>
                        <th class="text-left py-2 font-medium">Amounts</th>
                        <th class="text-left py-2 font-medium">Status</th>
                        <th class="text-left py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">
                                <div class="font-medium">{{ $invoice->number }}</div>
                                <div class="text-[11px] text-slate-400">Issued {{ $invoice->issued_at?->format('Y-m-d') }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                <div class="font-medium">{{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $invoice->student?->grade }} {{ $invoice->student?->class_name }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                @if($invoice->guardian)
                                    <div class="font-medium">{{ $invoice->guardian->first_name }} {{ $invoice->guardian->last_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $invoice->guardian->email }}</div>
                                @else
                                    <span class="text-[11px] text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                <div>{{ $invoice->academic_year }}</div>
                                <div class="text-[11px] text-slate-400">{{ $invoice->term }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                <div>Total: {{ number_format($invoice->total_amount, 2) }}</div>
                                <div class="text-[11px] text-slate-400">Balance: {{ number_format($invoice->balance, 2) }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                @php
                                    $statusColor = [
                                        'unpaid' => 'border-rose-500/60 bg-rose-500/10 text-rose-200',
                                        'partial' => 'border-amber-500/60 bg-amber-500/10 text-amber-200',
                                        'paid' => 'border-emerald-500/60 bg-emerald-500/10 text-emerald-200',
                                        'cancelled' => 'border-slate-600 bg-slate-800 text-slate-200',
                                    ][$invoice->status] ?? 'border-slate-600 bg-slate-800 text-slate-200';
                                @endphp
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] capitalize {{ $statusColor }}">
                                    {{ $invoice->status }}
                                </span>
                            </td>
                            <td class="py-2 align-middle">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">
                                        View
                                    </a>
                                    @if($invoice->status !== 'cancelled')
                                        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">
                                            Edit
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.invoices.print', $invoice) }}" class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-800 transition">
                                        Print
                                    </a>
                                    @if($invoice->student)
                                        <a href="{{ route('admin.students.statement.create', $invoice->student) }}" class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-800 transition">
                                            Statement
                                        </a>
                                    @endif
                                    @if($invoice->balance > 0 && $invoice->status !== 'cancelled')
                                        <a href="{{ route('admin.payments.create', $invoice) }}" class="rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-emerald-600 transition">
                                            Record payment
                                        </a>
                                    @endif
                                    @if($invoice->status !== 'cancelled')
                                        <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this invoice?')">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-amber-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-amber-600 transition">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                    @if($invoice->status === 'cancelled' && $invoice->balance === $invoice->total_amount)
                                        <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this invoice?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-full bg-red-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-red-600 transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-xs text-slate-400 mb-4">No invoices found yet.</p>
                <a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 transition">
                    Create your first invoice
                </a>
            </div>
        @endif
    </div>
@endsection
