@extends('layouts.app')
@section('content')
<div class="space-y-6 text-slate-200">
    <a href="{{ route('admin.students.show', $student) }}" class="text-indigo-300">Back to {{ $student->full_name }}</a>
    <h1 class="text-2xl font-bold">Scholarships · {{ $student->category_label }}</h1>
    <a href="{{ route('admin.funding.index') }}" class="text-indigo-300">Sponsor / BEAM Funding and checker approvals</a>
    <a href="{{ route('admin.funding.reports') }}" class="text-indigo-300 ml-4">Scholarship and sponsor reports</a>
    @if(session('success'))<p class="text-green-300">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <p>Residency determines the normal fee structure and hostel eligibility. Approved coverage reduces only the selected unpaid votes. Existing invoices require an explicit application.</p>
    <details class="bg-slate-900 rounded-xl p-4" open>
        <summary class="font-semibold">New award (pending approval)</summary>
        <form method="POST" action="{{ route('admin.students.scholarships.store', $student) }}" class="space-y-4 mt-4">@csrf
            @include('admin.students._scholarship-fields', ['award' => null])
            <button class="bg-indigo-600 rounded-lg px-4 py-2">Save pending award</button>
        </form>
    </details>
    @if(auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
    <details class="bg-slate-900 rounded-xl p-4">
        <summary>Management accounting policy and account mappings</summary>
        <p class="text-sm mt-3">Approve whether remission is scholarship expense or contra-revenue, and whether a sponsor commitment qualifies as a receivable. Configure the corresponding ledger account before approving awards.</p>
        @foreach($mappings as $mapping)<p>{{ $mapping->source_key }} → {{ $mapping->account?->code }} {{ $mapping->account?->name }} · {{ $mapping->description }}</p>@endforeach
        <form method="POST" action="{{ route('admin.students.scholarships.mapping', $student) }}" class="space-y-3 mt-3">@csrf
            <select name="source_key" class="bg-slate-800 rounded-lg"><option value="SCHOOL_REMISSION">School remission</option><option value="SPONSOR_RECEIVABLE">Sponsor receivable</option></select>
            <select name="account_id" required class="bg-slate-800 rounded-lg">@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
            <input name="approval_reference" required placeholder="Management policy approval reference" class="bg-slate-800 rounded-lg">
            <button class="bg-indigo-600 rounded-lg px-4 py-2">Save approved mapping</button>
        </form>
    </details>
    @endif
    @forelse($awards as $award)
        <section class="bg-slate-900 rounded-xl p-4 space-y-4">
            <h2 class="text-lg font-semibold">{{ $award->reference }} · {{ $award->name }} · {{ ucfirst($award->status) }}</h2>
            <p>{{ $award->sponsor }} · {{ $award->coverage_value }} {{ $award->coverage_basis === 'percentage' ? '%' : 'amount cap' }} · {{ implode(', ', $award->covered_votes) }} · {{ $award->starts_on->toDateString() }} – {{ $award->ends_on->toDateString() }}</p>
            <p>Treatment: {{ str_replace('_',' ', $award->accounting_treatment) }} · Document: {{ $award->supporting_reference ?: 'None supplied' }} · Approval: {{ $award->approval_reference ?: 'Pending' }}</p>
            <p>Year: {{ $award->academic_year ?: 'Legacy / review required' }} · Utilised: {{ number_format($award->utilised_amount,2) }} · Remaining amount cap: {{ $award->remaining_amount===null?'Percentage award':number_format($award->remaining_amount,2) }} · Settled: {{ number_format($award->settled_amount,2) }}</p>
            @if($award->status === 'pending')
                <details><summary>Edit pending terms</summary><form method="POST" action="{{ route('admin.students.scholarships.update', [$student, $award]) }}" class="space-y-3">@csrf @method('PUT')
                    @include('admin.students._scholarship-fields', ['award' => $award])<button class="bg-indigo-600 rounded-lg px-4 py-2">Save changes</button>
                </form></details>
                @if(auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']) && (int)$award->created_by!==(int)auth()->id())
                    <form method="POST" action="{{ route('admin.students.scholarships.approve', [$student, $award]) }}">@csrf
                        <input name="approval_reference" required placeholder="Approval reference" class="bg-slate-800 rounded-lg"><button class="bg-green-700 rounded-lg px-4 py-2">Approve</button>
                    </form>
                @endif
            @endif
            @if($award->status === 'approved')
                <form method="POST" action="{{ route('admin.students.scholarships.apply', [$student, $award]) }}" class="flex flex-wrap gap-3">@csrf
                    <select name="invoice_id" required class="bg-slate-800 rounded-lg">@foreach($invoices as $invoice)<option value="{{ $invoice->id }}">{{ $invoice->number }} · {{ $invoice->balance }} outstanding</option>@endforeach</select>
                    <input type="date" name="accounting_date" required value="{{ date('Y-m-d') }}" class="bg-slate-800 rounded-lg"><button class="bg-indigo-600 rounded-lg px-4 py-2">Apply to selected invoice</button>
                </form>
            @endif
            @if($award->status !== 'closed' && auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
                <form method="POST" action="{{ route('admin.students.scholarships.close', [$student, $award]) }}">@csrf
                    <input name="reason" required placeholder="Reason for closing award" class="bg-slate-800 rounded-lg"><button class="bg-slate-700 rounded-lg px-4 py-2">Close for future use</button>
                </form>
            @endif
            @foreach($award->applications as $application)
                <div class="border-t border-slate-700 pt-3">
                    <p>SCH-{{ $application->id }} · {{ $application->invoice?->number }} · {{ $application->amount }} · {{ $application->status }} · Journal #{{ $application->journal_batch_id }}</p>
                    @if(($application->award_snapshot['accounting_treatment'] ?? '') === 'sponsor_receivable')
                        <p>Sponsor outstanding: {{ number_format($application->sponsor_balance, 2) }}</p>
                        @if($application->sponsor_balance > 0)
                            <details><summary>Record money actually received from sponsor</summary>
                                <form method="POST" action="{{ route('admin.students.scholarships.sponsor-receipt', [$student, $application]) }}" class="space-y-3">@csrf
                                    <input name="reference" required maxlength="100" placeholder="Unique receipt / bank reference" class="bg-slate-800 rounded-lg">
                                    <input name="amount" type="number" min="0.01" step="0.01" max="{{ $application->sponsor_balance }}" required placeholder="Amount received" class="bg-slate-800 rounded-lg">
                                    <input name="received_on" type="date" required class="bg-slate-800 rounded-lg">
                                    <select name="account_id" required class="bg-slate-800 rounded-lg">@foreach($accounts->whereIn('category', ['bank','cash']) as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select>
                                    <label><input name="cash_received" type="checkbox" value="1" required> I confirm money has actually been received.</label>
                                    <button class="bg-green-700 rounded-lg px-4 py-2">Record sponsor receipt</button>
                                </form>
                            </details>
                        @endif
                        @foreach($application->sponsorReceipts as $receipt)
                            <p>Sponsor receipt {{ $receipt->reference }} · {{ $receipt->amount }} · {{ $receipt->received_on->toDateString() }} {{ $receipt->reversed_on ? '(reversed)' : '' }}</p>
                            @if(!$receipt->reversed_on && auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
                                <form method="POST" action="{{ route('admin.students.scholarships.sponsor-receipt.reverse', [$student,$receipt]) }}">@csrf
                                    <input name="accounting_date" type="date" required class="bg-slate-800 rounded-lg"><input name="reason" required placeholder="Receipt reversal reason" class="bg-slate-800 rounded-lg"><button class="bg-amber-800 rounded-lg px-4 py-2">Reverse sponsor receipt</button>
                                </form>
                            @endif
                        @endforeach
                    @endif
                    @if($application->status === 'posted' && auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster']))
                        <form method="POST" action="{{ route('admin.students.scholarships.reverse', [$student, $application]) }}">@csrf
                            <input type="date" name="accounting_date" required value="{{ date('Y-m-d') }}" class="bg-slate-800 rounded-lg"><input name="reason" required placeholder="Correction reason" class="bg-slate-800 rounded-lg"><button class="bg-amber-800 rounded-lg px-4 py-2">Request coverage reversal</button>
                        </form>
                    @endif
                </div>
            @endforeach
            <details><summary>Audit history</summary>@foreach($award->events as $event)<p>{{ $event->created_at }} · {{ $event->action }} · User #{{ $event->user_id }} · {{ json_encode($event->new_values) }}</p>@endforeach</details>
        </section>
    @empty<p>No scholarship awards recorded.</p>@endforelse
</div>
@endsection
