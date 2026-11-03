@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('admin.cashbook.index') }}" class="text-sm text-slate-400 hover:text-white">← Cashbook</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-50">Import Cashbook Expenses</h1>
        <p class="mt-1 text-sm text-slate-400">Upload expense rows as CSV, review account mappings and validation, then post approved rows to Cashbook and the General Ledger.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-800 bg-red-950/40 p-4 text-sm text-red-200">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-xl border border-amber-800/70 bg-amber-950/20 p-4 text-sm text-amber-100">
        This uploader is for <strong>expenses only</strong>. Student receipts, bank-statement lines, transfers and opening balances are excluded. Do not re-import January–March student receipts that have already been captured.
    </div>

    <section class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
        <div>
            <h2 class="font-medium text-slate-100">Saved import batches</h2>
            <p class="mt-1 text-xs text-slate-400">Uploaded rows remain here until you finish review or posting.</p>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-800">
            <table class="min-w-full text-sm text-slate-300">
                <thead class="bg-slate-800 text-left text-xs uppercase text-slate-400">
                    <tr>
                        <th class="px-3 py-3">Batch / source file</th>
                        <th class="px-3 py-3">Uploaded</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Rows</th>
                        <th class="px-3 py-3">Amount</th>
                        <th class="px-3 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($batches as $batch)
                        @php
                            $batchStatusClass = match ($batch->status) {
                                'posted' => 'bg-blue-900/50 text-blue-200',
                                'validated' => 'bg-emerald-900/50 text-emerald-200',
                                default => 'bg-amber-900/50 text-amber-200',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-3">
                                <div class="font-medium text-slate-100">{{ $batch->batch_number }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $batch->file_name ?: 'CSV upload' }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div>{{ $batch->created_at?->format('d M Y, H:i') ?? '—' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $batch->uploader?->name ?? 'Uploader unavailable' }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs {{ $batchStatusClass }}">{{ ucfirst(str_replace('_', ' ', $batch->status)) }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="text-emerald-300">{{ $batch->validated_rows_count }} ready</span>
                                <span class="text-slate-500">/ {{ $batch->total_rows }}</span>
                                @if($batch->held_rows_count > 0)
                                    <div class="mt-1 text-xs text-amber-200">{{ $batch->held_rows_count }} held</div>
                                @endif
                                @if($batch->posted_rows_count > 0)
                                    <div class="mt-1 text-xs text-blue-200">{{ $batch->posted_rows_count }} posted</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">${{ number_format((float) $batch->amount_total, 2) }}</td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('admin.cashbook.import-expenses.show', $batch) }}" class="inline-flex rounded-lg border border-slate-600 px-3 py-2 text-xs font-medium text-slate-100 hover:bg-slate-800">
                                    {{ $batch->status === 'posted' ? 'View batch' : 'Continue review' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-400">No Cashbook expense imports yet. Uploaded batches will appear here for you to continue.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($batches->hasPages())
            <div>{{ $batches->links() }}</div>
        @endif
    </section>

    <section class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-medium text-slate-100">1. Download the CSV template</h2>
                <p class="mt-1 text-xs text-slate-400">Use ISO dates (YYYY-MM-DD), positive amounts and one expense per row.</p>
            </div>
            <a href="{{ route('admin.cashbook.import-expenses.template') }}" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-100 hover:bg-slate-800">Download CSV template</a>
        </div>
        <div class="overflow-x-auto rounded-lg border border-slate-800">
            <table class="min-w-full text-xs text-slate-300">
                <thead class="bg-slate-800 text-left"><tr><th class="p-2">Required columns</th></tr></thead>
                <tbody><tr><td class="p-3 leading-6">transaction_date, source_reference, voucher_number, description, amount, category, cash_account_code, offset_account_code, source_evidence, notes</td></tr></tbody>
            </table>
        </div>
        <p class="text-xs text-slate-400">Use a stable source reference such as <code class="text-slate-200">AFC-JAN-row-42</code>. The source_evidence should identify the source workbook sheet and row. Cash and expense accounts are rechecked against this school’s active Chart of Accounts.</p>
    </section>

    <section class="rounded-xl border border-slate-800 bg-slate-900/80 p-5">
        <h2 class="font-medium text-slate-100">2. Upload for review</h2>
        <form class="mt-4 space-y-4" action="{{ route('admin.cashbook.import-expenses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" required class="block w-full rounded-lg border border-slate-700 bg-slate-950 p-3 text-sm text-slate-200">
            <p class="text-xs text-slate-400">Maximum 50 rows per batch. Upload is parsed directly into private school-scoped staging; the uploaded file is not published or retained as a public file.</p>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Stage CSV for review</button>
        </form>
    </section>
</div>
@endsection
