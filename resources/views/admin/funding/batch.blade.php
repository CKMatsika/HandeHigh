@extends('layouts.app')
@section('content')
<div class="space-y-5 text-slate-200">
    <a href="{{ route('admin.funding.index') }}" class="text-indigo-300">Back to sponsor funding</a>
    <h1 class="text-2xl font-bold">{{ $claimBatch->reference }} · {{ $claimBatch->academic_year }} · {{ $claimBatch->status }}</h1>
    @if(session('success'))<p class="text-green-300">{{ session('success') }}</p>@endif
    @foreach($errors->all() as $error)<p class="text-red-300">{{ $error }}</p>@endforeach
    <p>Verify whether each item is an outstanding claim, a bank deposit, an allocation or an adjustment. Bank deposits and other non-claim items stay staged; this screen never infers cash from a description.</p>
    <p>For a prior receivable, state whether its outstanding balance is already in sponsor control, in student control, or absent from the reviewed opening Trial Balance. Unknown inclusion cannot be recognised. An absent opening balance requires an approved opening-equity correction, never new fee revenue.</p>
    @foreach($rows as $row)
        <section class="bg-slate-900 p-4 rounded-xl space-y-3">
            <h2>Row {{ $row->row_number }} · {{ $row->reference_number }} · {{ $row->status }}</h2>
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">@foreach($row->raw_data as $key=>$value)<div><dt class="text-slate-400">{{ str_replace('_',' ',$key) }}</dt><dd>{{ is_array($value)?json_encode($value):$value }}</dd></div>@endforeach</dl>
            @if($claimBatch->status==='pending_verification')
                <form method="POST" action="{{ route('admin.funding.review',[$claimBatch,$row->id]) }}" class="flex flex-wrap gap-3">@csrf
                    <select name="classification" required class="bg-slate-800 rounded-lg"><option value="">Verify classification</option>@foreach(['prior_receivable'=>'Recognised prior-year receivable','current_receivable'=>'Approved current-year invoice obligation','pending_claim'=>'Pending / not recognisable','bank_deposit'=>'Actual historical bank deposit','allocation'=>'Historical allocation','adjustment'=>'Historical adjustment','rejected'=>'Rejected claim','disputed'=>'Unrecognised disputed claim'] as $value=>$label)<option value="{{ $value }}" @selected(($row->parsed_data['classification']??'')===$value)>{{ $label }}</option>@endforeach</select>
                    <select name="opening_inclusion" class="bg-slate-800 rounded-lg">@foreach(['unknown'=>'Opening inclusion unverified','sponsor_control'=>'Already in opening Sponsor Receivables','student_control'=>'Already in opening Student Debtors','not_in_opening'=>'Verified absent from opening balances','current_invoice'=>'Transfer from a posted current invoice'] as $value=>$label)<option value="{{ $value }}" @selected(($row->parsed_data['opening_inclusion']??'unknown')===$value)>{{ $label }}</option>@endforeach</select>
                    <input name="opening_journal_batch_id" type="number" min="1" placeholder="Existing opening journal ID (if included)" value="{{ $row->parsed_data['opening_journal_batch_id']??'' }}" class="bg-slate-800 rounded-lg">
                    <input name="opening_invoice_number" placeholder="Existing opening or current invoice reference" value="{{ $row->raw_data['opening_invoice_number']??'' }}" class="bg-slate-800 rounded-lg">
                    <input name="accounting_date" type="date" value="{{ $row->parsed_data['accounting_date']??$row->raw_data['accounting_date']??'' }}" class="bg-slate-800 rounded-lg" title="Posting date for current-year invoice transfer">
                    <input name="currency" maxlength="3" placeholder="Verified ledger currency" value="{{ $row->parsed_data['currency']??$row->raw_data['currency']??'' }}" class="bg-slate-800 rounded-lg">
                    <input name="recognition_evidence" required maxlength="4000" placeholder="Evidence of recognition and opening inclusion" value="{{ $row->parsed_data['recognition_evidence']??'' }}" class="bg-slate-800 rounded-lg">
                    <button class="bg-indigo-600 px-4 py-2 rounded-lg">Verify row without posting</button>
                </form>
            @endif
        </section>
    @endforeach
    {{ $rows->links() }}
    @if($claimBatch->status==='pending_verification' && $claimBatch->created_by!==auth()->id() && auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
        <form method="POST" action="{{ route('admin.funding.batch.approve',$claimBatch) }}">@csrf<input name="approval_reference" required placeholder="Independent checker approval reference" class="bg-slate-800 rounded-lg"><button class="bg-green-700 px-4 py-2 rounded-lg">Approve verified claims and controlled opening postings</button></form>
    @endif
</div>
@endsection
