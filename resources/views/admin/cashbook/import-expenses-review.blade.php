@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.cashbook.index') }}" class="text-sm text-slate-400 hover:text-white">← Cashbook</a>
            <h1 class="mt-2 text-2xl font-semibold text-slate-50">Review Expense Import</h1>
            <p class="mt-1 text-sm text-slate-400">Batch {{ $batch->batch_number }} · {{ $batch->file_name }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm {{ $batch->status === 'validated' ? 'bg-emerald-900/60 text-emerald-300' : (in_array($batch->status, ['posted', 'partially_posted'], true) ? 'bg-blue-900/60 text-blue-300' : 'bg-amber-900/60 text-amber-200') }}">{{ ucfirst(str_replace('_', ' ', $batch->status)) }}</span>
    </div>

    @if(session('success'))<div class="rounded-lg border border-emerald-800 bg-emerald-950/30 p-3 text-sm text-emerald-200">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-lg border border-red-800 bg-red-950/30 p-3 text-sm text-red-200">{{ $errors->first() }}</div>@endif

    @php
        $readyRows = $batch->stagingRows->where('status', 'validated')->count();
        $heldRows = $batch->stagingRows->where('status', 'unresolved')->count();
        $totalAmount = $batch->stagingRows->sum(fn ($row) => (float) $row->amount);
        $readyAmount = $batch->stagingRows->where('status', 'validated')->sum(fn ($row) => (float) $row->amount);
        $cashAccounts = $accounts->filter(fn ($account) => in_array($account->category, ['cash', 'bank'], true));
        $offsetAccounts = $accounts->filter(fn ($account) => in_array($account->type, ['expense', 'liability'], true));
    @endphp

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><div class="text-xs text-slate-400">Rows ready</div><div class="mt-1 text-2xl font-semibold text-emerald-300">{{ $readyRows }}</div></div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><div class="text-xs text-slate-400">Rows held</div><div class="mt-1 text-2xl font-semibold text-amber-200">{{ $heldRows }}</div></div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><div class="text-xs text-slate-400">Source expense total</div><div class="mt-1 text-2xl font-semibold text-slate-100">${{ number_format($totalAmount, 2) }}</div></div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 text-sm text-slate-300">
        Review the source reference and evidence, then choose the cash/bank account and expense offset account for each row. You can post validated rows while other rows remain held; only the rows marked Validated will post, and held rows will stay in this batch for later review. Each post is transactional and creates a balanced journal through the existing accounting service.
    </div>

    <div class="space-y-4">
        @foreach($batch->stagingRows as $row)
            @php($mapping = $row->parsed_data ?? [])
            @php($raw = $row->raw_data ?? [])
            <article class="rounded-xl border {{ $row->status === 'validated' ? 'border-emerald-900/70' : ($row->status === 'posted' ? 'border-blue-900/70' : 'border-amber-900/70') }} bg-slate-900 p-4 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-slate-100">CSV row {{ $raw['_csv_row'] ?? $row->row_number }} · ${{ number_format((float) $row->amount, 2) }} · {{ $row->accounting_date?->format('d M Y') ?? 'Invalid date' }}</div>
                        <div class="mt-1 text-sm text-slate-300">{{ $row->description }}</div>
                        <div class="mt-1 text-xs text-slate-400">Source: {{ $row->reference_number ?: 'Missing reference' }} · Voucher: {{ $mapping['voucher_number'] ?? '—' }} · Evidence: {{ $mapping['source_evidence'] ?? '—' }}</div>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs {{ $row->status === 'validated' ? 'bg-emerald-900/60 text-emerald-300' : ($row->status === 'posted' ? 'bg-blue-900/60 text-blue-300' : 'bg-amber-900/60 text-amber-200') }}">{{ ucfirst($row->status) }}</span>
                </div>

                @if($row->status !== 'posted')
                    <form action="{{ route('admin.cashbook.import-expenses.mapping', [$batch, $row]) }}" method="POST" class="grid gap-3 md:grid-cols-4 items-end">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs text-slate-400">Cash / bank account</label>
                            <select name="cash_account_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                <option value="">Select account</option>
                                @foreach($cashAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((int) ($mapping['cash_account_id'] ?? 0) === $account->id)>{{ $account->code }} · {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-slate-400">Expense / liability account</label>
                            <select name="offset_account_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                                <option value="">Select account</option>
                                @foreach($offsetAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((int) ($mapping['offset_account_id'] ?? 0) === $account->id)>{{ $account->code }} · {{ $account->name }} ({{ ucfirst($account->type) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-slate-400">Cashbook category</label>
                            <input name="category" value="{{ $mapping['category'] ?? '' }}" maxlength="50" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        </div>
                        <button type="submit" class="rounded-lg bg-slate-700 px-3 py-2 text-sm font-medium text-white hover:bg-slate-600">Save mapping</button>
                    </form>
                @else
                    <div class="text-xs text-slate-400">Posted cash account: {{ $row->account_code }} · Journal batch: {{ $row->journal_batch_id }}</div>
                @endif

                @if($row->validation_errors)
                    <ul class="list-disc space-y-1 pl-5 text-xs text-amber-200">
                        @foreach($row->validation_errors as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                @endif
            </article>
        @endforeach
    </div>

    @if($batch->status !== 'posted')
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-900 p-4">
            <form action="{{ route('admin.cashbook.import-expenses.validate', $batch) }}" method="POST">
                @csrf
                <button class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-100 hover:bg-slate-800">Revalidate batch</button>
            </form>
            @if($readyRows > 0)
                <form action="{{ route('admin.cashbook.import-expenses.post', $batch) }}" method="POST" class="flex flex-wrap items-center gap-3" onsubmit="return confirm('Post the {{ $readyRows }} currently validated expense row(s), totaling ${{ number_format($readyAmount, 2) }}, to Cashbook and the General Ledger? Held rows will remain in this batch. Posted transactions require reversals for correction.');">
                    @csrf
                    <label class="flex items-center gap-2 text-xs text-slate-300"><input type="checkbox" name="confirm_reviewed" value="1" required> I reviewed the {{ $readyRows }} ready row(s) and their account mappings.</label>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">Post {{ $readyRows }} validated expense{{ $readyRows === 1 ? '' : 's' }} · ${{ number_format($readyAmount, 2) }}</button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
