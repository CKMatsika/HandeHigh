@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="flex items-center gap-2 text-xs text-indigo-300">
                <a href="{{ route('admin.procurement.index') }}" class="hover:text-indigo-200">&larr; Procurement Hub</a>
            </div>
            <h1 class="mt-2 text-2xl font-bold text-slate-50">Requests for Quotations (RFQs)</h1>
            <p class="mt-1 text-sm text-slate-400">Competitive vendor bidding, price comparisons, and award evaluation cycle.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.procurement.index') }}" class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-700">
                Procurement Hub
            </a>
            @can('procurement.rfq.create')
                <a href="{{ route('admin.procurement.rfq.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create New RFQ
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-700/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Lifecycle Banner --}}
    <div class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900/90 via-slate-900/60 to-indigo-950/30 p-5">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Procurement Cycle Workflow</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 text-center">
            <div class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-3">
                <span class="text-xs font-bold text-slate-400">Step 1</span>
                <p class="mt-1 text-sm font-semibold text-slate-200">Requisition</p>
                <p class="text-[11px] text-slate-400">Department approval</p>
            </div>
            <div class="rounded-xl border border-indigo-900/50 bg-indigo-950/40 p-3 ring-1 ring-indigo-500/30">
                <span class="text-xs font-bold text-indigo-300">Step 2</span>
                <p class="mt-1 text-sm font-semibold text-indigo-100">Issue RFQ</p>
                <p class="text-[11px] text-indigo-300">Invite suppliers</p>
            </div>
            <div class="rounded-xl border border-indigo-900/50 bg-indigo-950/40 p-3 ring-1 ring-indigo-500/30">
                <span class="text-xs font-bold text-indigo-300">Step 3</span>
                <p class="mt-1 text-sm font-semibold text-indigo-100">Bids & Quotes</p>
                <p class="text-[11px] text-indigo-300">Collect vendor quotes</p>
            </div>
            <div class="rounded-xl border border-indigo-900/50 bg-indigo-950/40 p-3 ring-1 ring-indigo-500/30">
                <span class="text-xs font-bold text-indigo-300">Step 4</span>
                <p class="mt-1 text-sm font-semibold text-indigo-100">Comparison Matrix</p>
                <p class="text-[11px] text-indigo-300">Evaluate & select</p>
            </div>
            <div class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-3">
                <span class="text-xs font-bold text-slate-400">Step 5</span>
                <p class="mt-1 text-sm font-semibold text-slate-200">Purchase Order</p>
                <p class="text-[11px] text-slate-400">Auto-created on award</p>
            </div>
            <div class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-3">
                <span class="text-xs font-bold text-slate-400">Step 6</span>
                <p class="mt-1 text-sm font-semibold text-slate-200">GRN & D/Note</p>
                <p class="text-[11px] text-slate-400">Store receiving & returns</p>
            </div>
        </div>
    </div>

    {{-- RFQ Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80 shadow-sm">
        <div class="border-b border-slate-800 px-6 py-4 flex items-center justify-between">
            <h2 class="font-semibold text-slate-100">All RFQs & Tenders</h2>
            <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-300 font-medium">{{ $rfqs->total() }} records</span>
        </div>

        @if($rfqs->isEmpty())
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="mt-3 text-sm font-medium text-slate-300">No RFQs created yet</p>
                <p class="mt-1 text-xs text-slate-500">Initiate a request for quotation to solicit competitive vendor prices.</p>
                @can('procurement.rfq.create')
                    <div class="mt-4">
                        <a href="{{ route('admin.procurement.rfq.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Create First RFQ</a>
                    </div>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3 font-semibold">RFQ Number</th>
                            <th class="px-5 py-3 font-semibold">Title / Description</th>
                            <th class="px-5 py-3 font-semibold">Requisition</th>
                            <th class="px-5 py-3 font-semibold">Items & Vendors</th>
                            <th class="px-5 py-3 font-semibold">Quotes Received</th>
                            <th class="px-5 py-3 font-semibold">Deadline</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach($rfqs as $rfq)
                            @php
                                $statusClasses = match($rfq->status) {
                                    'draft' => 'bg-slate-800 text-slate-300 border-slate-700',
                                    'published' => 'bg-sky-950/60 text-sky-400 border-sky-800/60',
                                    'closed' => 'bg-amber-950/60 text-amber-400 border-amber-800/60',
                                    'awarded' => 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60',
                                    'cancelled' => 'bg-rose-950/60 text-rose-400 border-rose-800/60',
                                    default => 'bg-slate-800 text-slate-300 border-slate-700',
                                };
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.procurement.rfq.show', $rfq) }}" class="font-mono font-semibold text-indigo-300 hover:text-indigo-200 hover:underline">
                                        {{ $rfq->rfq_number }}
                                    </a>
                                    <div class="text-[11px] text-slate-500">By {{ $rfq->creator?->name }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-100">{{ $rfq->title }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    @if($rfq->requisition)
                                        <a href="{{ route('admin.procurement.requisitions.show', $rfq->requisition) }}" class="text-xs font-mono text-slate-300 hover:text-indigo-300">
                                            {{ $rfq->requisition->requisition_number }}
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-500">Direct / General</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="text-slate-200 font-medium">{{ $rfq->items_count }}</span> items ·
                                    <span class="text-slate-200 font-medium">{{ $rfq->vendors->count() }}</span> invited
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $rfq->quotations_count > 0 ? 'bg-indigo-950/60 text-indigo-300 border border-indigo-800/60' : 'bg-slate-800 text-slate-400' }}">
                                        {{ $rfq->quotations_count }} Quote(s)
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="{{ $rfq->submission_deadline?->isPast() && $rfq->status !== 'awarded' ? 'text-rose-400 font-semibold' : 'text-slate-300' }}">
                                        {{ $rfq->submission_deadline?->format('d M Y') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize {{ $statusClasses }}">
                                        {{ $rfq->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.procurement.rfq.show', $rfq) }}" class="rounded bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-200 hover:bg-slate-700">
                                            View
                                        </a>
                                        @if($rfq->quotations_count > 0)
                                            <a href="{{ route('admin.procurement.rfq.comparison', $rfq) }}" class="rounded bg-indigo-600/80 px-2.5 py-1 text-xs font-medium text-white hover:bg-indigo-600">
                                                Compare
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($rfqs->hasPages())
                <div class="border-t border-slate-800 px-6 py-4">
                    {{ $rfqs->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
