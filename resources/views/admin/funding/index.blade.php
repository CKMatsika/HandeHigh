@extends('layouts.app')
@section('content')
<div class="space-y-6 text-slate-200">
    <h1 class="text-2xl font-bold">Scholarship sponsors and BEAM</h1>
    <a href="{{ route('admin.funding.reports') }}" class="text-indigo-300">Beneficiaries, claims, ageing, payments and reconciliation reports</a>
    @if(session('success'))<p class="text-green-300">{{ session('success') }}</p>@endif
    @foreach($errors->all() as $error)<p class="text-red-300">{{ $error }}</p>@endforeach
    <p>Historical descriptions such as BEAM 2023 are unverified until classified. Uploads create staging rows only. A different management checker must approve awards, claim recognition, consolidated settlements and financial corrections.</p>
    @if(auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
    <details class="bg-slate-900 rounded-xl p-4"><summary>Register a sponsor</summary>
        <form method="POST" action="{{ route('admin.funding.sponsors') }}" class="flex flex-wrap gap-3 mt-3">@csrf
            <input name="code" required pattern="[A-Z0-9_-]{1,50}" placeholder="Sponsor code, e.g. BEAM" class="bg-slate-800 rounded-lg">
            <input name="name" required maxlength="255" placeholder="Sponsor / funding organisation" class="bg-slate-800 rounded-lg">
            <select name="category" class="bg-slate-800 rounded-lg">@foreach(['government','company','ngo','donor','individual'] as $category)<option>{{ $category }}</option>@endforeach</select>
            <button class="bg-indigo-600 px-4 py-2 rounded-lg">Register</button>
        </form>
    </details>
    @foreach($sponsors as $sponsor)
        <details class="bg-slate-900 rounded-xl p-4"><summary>{{ $sponsor->name }} · {{ $sponsor->code }} · receivable mapping</summary>
            <form method="POST" action="{{ route('admin.funding.mapping',$sponsor) }}" class="flex flex-wrap gap-3 mt-3">@csrf
                <select name="account_id" required class="bg-slate-800 rounded-lg">@foreach($accounts->where('type','asset')->where('category','receivable') as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                <input name="approval_reference" required placeholder="Approved accounting policy reference" class="bg-slate-800 rounded-lg"><button class="bg-indigo-600 px-4 py-2 rounded-lg">Save mapping</button>
            </form>
        </details>
    @endforeach
    @endif
    <section class="bg-slate-900 rounded-xl p-4 space-y-3">
        <h2 class="text-lg font-semibold">Stage historical BEAM claims</h2>
        <a href="{{ route('admin.funding.template') }}" class="text-indigo-300">Download historical claim CSV template</a>
        <p class="text-sm">Use one claim year per batch. Supply a consolidated row per student/sponsor/year. Fee-vote allocation is a quoted JSON object in the CSV, for example {"TUITION":100}. No actual school records should be uploaded during rehearsal.</p>
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.funding.import') }}" class="flex flex-wrap gap-3">@csrf
            <select name="funding_sponsor_id" required class="bg-slate-800 rounded-lg">@foreach($sponsors->where('category','government') as $sponsor)<option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>@endforeach</select>
            <input name="academic_year" required pattern="[0-9]{4}" placeholder="Claim year, e.g. 2023" class="bg-slate-800 rounded-lg">
            <input name="reference" required maxlength="100" placeholder="Unique batch reference" class="bg-slate-800 rounded-lg">
            <input name="file" type="file" required accept=".csv"><button class="bg-indigo-600 px-4 py-2 rounded-lg">Stage and preview</button>
        </form>
        @foreach($batches as $batch)<p><a class="text-indigo-300" href="{{ route('admin.funding.batch',$batch) }}">{{ $batch->reference }} · {{ $batch->academic_year }} · {{ $batch->sponsor->name }} · {{ $batch->status }}</a></p>@endforeach
    </section>
    <section class="bg-slate-900 rounded-xl p-4 space-y-3">
        <h2 class="text-lg font-semibold">Prepare an actual sponsor payment</h2>
        <p>Allocate one bank receipt across recognised obligations, students and years. Preparing saves a pending receipt only; an independent checker posts the bank and sponsor entries.</p>
        <form method="POST" action="{{ route('admin.funding.settlements') }}" id="funding-settlement" class="space-y-3">@csrf
            <div class="flex flex-wrap gap-3">
                <select name="funding_sponsor_id" required class="bg-slate-800 rounded-lg">@foreach($sponsors as $sponsor)<option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>@endforeach</select>
                <input name="reference" required maxlength="100" placeholder="Unique bank settlement reference" class="bg-slate-800 rounded-lg">
                <input name="received_on" type="date" required class="bg-slate-800 rounded-lg">
                <input name="amount" type="number" min="0.01" step="0.01" required placeholder="Actual amount received" class="bg-slate-800 rounded-lg">
                <select name="bank_account_id" required class="bg-slate-800 rounded-lg">@foreach($accounts->whereIn('category',['bank','cash']) as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                <input name="supporting_reference" required placeholder="Bank statement / receipt document" class="bg-slate-800 rounded-lg">
            </div>
            <div data-allocation-rows>
                <div class="flex flex-wrap gap-3 mb-2" data-allocation-row>
                    <select name="allocations[0][target]" class="bg-slate-800 rounded-lg"><option value="">Select recognised claim or scholarship</option>@foreach($targets as $target)<option value="{{ $target['key'] }}" data-sponsor="{{ $target['sponsor_id'] }}">{{ $target['label'] }}</option>@endforeach</select>
                    <input name="allocations[0][amount]" type="number" min="0.01" step="0.01" placeholder="Allocated amount" class="bg-slate-800 rounded-lg">
                </div>
            </div>
            <button type="button" data-add-allocation class="bg-slate-700 px-4 py-2 rounded-lg">Add another claim / year</button>
            <label class="block"><input name="cash_received" type="checkbox" value="1" required> Money has actually been received and the reference matches bank evidence.</label>
            <button class="bg-green-700 px-4 py-2 rounded-lg">Prepare for checker</button>
        </form>
        @foreach($settlements as $settlement)
            <div class="border-t border-slate-700 pt-3"><p>{{ $settlement->reference }} · {{ $settlement->received_on->toDateString() }} · {{ $settlement->amount }} · {{ $settlement->status }}</p>
                @foreach($settlement->allocations as $allocation)<p class="text-sm">{{ ucfirst($allocation->target_type) }} #{{ $allocation->target_id }} · {{ $allocation->amount }}</p>@endforeach
                @if($settlement->status==='pending' && $settlement->created_by!==auth()->id() && auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
                    <form method="POST" action="{{ route('admin.funding.settlement.approve',$settlement) }}">@csrf<input name="approval_reference" required placeholder="Checker approval reference" class="bg-slate-800 rounded-lg"><button class="bg-green-700 px-4 py-2 rounded-lg">Approve and post receipt</button></form>
                @endif
            </div>
        @endforeach
    </section>
    <section class="bg-slate-900 rounded-xl p-4 space-y-3">
        <h2 class="text-lg font-semibold">Pending, disputed and rejected claims</h2>
        <p>Nomination is not a receivable. When evidence changes, prepare recognition against the original claim and obtain a different management checker. Posted history cannot be amended here.</p>
        @foreach($pendingClaims as $claim)
            <details><summary>#{{ $claim->id }} · {{ $claim->student->full_name }} · {{ $claim->academic_year }} · {{ $claim->reference }} · {{ $claim->status }}</summary>
                <form method="POST" action="{{ route('admin.funding.claim.recognition',$claim) }}" class="flex flex-wrap gap-3 my-3">@csrf
                    <input name="approved_amount" type="number" min="0.01" step="0.01" required value="{{ $claim->approved_amount }}" placeholder="Verified approved claim amount" class="bg-slate-800 rounded-lg">
                    @foreach($claim->fee_votes as $vote=>$amount)<label>{{ $vote }}<input name="fee_votes[{{ $vote }}]" type="number" min="0.01" step="0.01" value="{{ $amount }}" required class="bg-slate-800 rounded-lg"></label>@endforeach
                    <input name="recognised_amount" type="number" min="0.01" step="0.01" max="{{ $claim->approved_amount }}" required placeholder="Verified recognised amount" class="bg-slate-800 rounded-lg">
                    <input name="currency" required maxlength="3" placeholder="Verified ledger currency" value="{{ $claim->currency }}" class="bg-slate-800 rounded-lg">
                    <input name="received_before_cutover" type="number" min="0" step="0.01" required value="{{ $claim->received_before_cutover }}" placeholder="Previously received before cutover" class="bg-slate-800 rounded-lg">
                    <select name="opening_inclusion" class="bg-slate-800 rounded-lg"><option value="sponsor_control">Already in opening Sponsor Receivables</option><option value="student_control">Already in opening Student Debtors</option><option value="not_in_opening">Verified missing from opening balances</option><option value="current_invoice">Posted current invoice</option></select>
                    <input name="opening_journal_batch_id" type="number" min="1" placeholder="Existing opening journal ID if included" class="bg-slate-800 rounded-lg"><input name="invoice_id" type="number" min="1" placeholder="Matched invoice ID for student transfer" class="bg-slate-800 rounded-lg"><input name="accounting_date" type="date" class="bg-slate-800 rounded-lg">
                    <input name="recognition_evidence" required placeholder="Verified obligation and opening evidence" class="bg-slate-800 rounded-lg"><input name="supporting_reference" required placeholder="Supporting documentation" class="bg-slate-800 rounded-lg"><button class="bg-indigo-600 px-4 py-2 rounded-lg">Prepare recognition only</button>
                </form>
                @if($claim->status==='recognition_pending' && (int)$claim->recognition_prepared_by!==(int)auth()->id() && auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
                    <p>Proposed recognition: {{ $claim->recognised_amount }} · Previously received: {{ $claim->received_before_cutover }} · {{ $claim->opening_inclusion }} · {{ $claim->recognition_evidence }}</p>
                    <form method="POST" action="{{ route('admin.funding.claim.recognition.approve',$claim) }}">@csrf<input name="approval_reference" required placeholder="Independent checker reference" class="bg-slate-800 rounded-lg"><button class="bg-green-700 px-4 py-2 rounded-lg">Approve recognition and controlled posting</button></form>
                @endif
            </details>
        @endforeach
    </section>
    <section class="bg-slate-900 rounded-xl p-4 space-y-3">
        <h2 class="text-lg font-semibold">Controlled financial corrections</h2>
        <form method="POST" action="{{ route('admin.funding.corrections') }}" class="flex flex-wrap gap-3">@csrf
            <select name="type" class="bg-slate-800 rounded-lg"><option value="claim">Claim</option><option value="settlement">Settlement</option></select>
            <input name="id" type="number" min="1" required placeholder="Record number from report" class="bg-slate-800 rounded-lg"><input name="accounting_date" type="date" required class="bg-slate-800 rounded-lg"><input name="reason" required placeholder="Correction reason" class="bg-slate-800 rounded-lg"><button class="bg-amber-800 px-4 py-2 rounded-lg">Request correction</button>
        </form>
        @foreach($approvals as $approval)
            <div><p>Request #{{ $approval->id }} · {{ $approval->action }} · {{ $approval->payload['date'] }} · {{ $approval->payload['reason'] }} · Maker #{{ $approval->requested_by }}</p>
                @if($approval->requested_by!==auth()->id() && auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
                    <form method="POST" action="{{ route('admin.funding.correction.approve',$approval) }}">@csrf<input name="approval_reference" required placeholder="Checker approval reference" class="bg-slate-800 rounded-lg"><button class="bg-amber-800 px-4 py-2 rounded-lg">Approve correction</button></form>
                @endif
            </div>
        @endforeach
    </section>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const form=document.getElementById('funding-settlement');
    const filter=function(){const sponsor=form.querySelector('[name="funding_sponsor_id"]').value; form.querySelectorAll('[data-sponsor]').forEach(function(option){option.disabled=option.dataset.sponsor!==sponsor; if(option.disabled&&option.selected){option.parentElement.value='';}});};
    form.querySelector('[name="funding_sponsor_id"]').addEventListener('change',filter);
    form.querySelector('[data-add-allocation]').addEventListener('click',function(){const container=form.querySelector('[data-allocation-rows]'); const row=container.firstElementChild.cloneNode(true); const index=container.children.length; row.querySelectorAll('[name]').forEach(function(input){input.name=input.name.replace(/\[\d+\]/,'['+index+']'); input.value='';}); container.appendChild(row); filter();});
    filter();
});
</script>
@endpush
