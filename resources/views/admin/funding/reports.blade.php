@extends('layouts.app')
@section('content')
<div class="space-y-5 text-slate-200">
    <a href="{{ route('admin.funding.index') }}" class="text-indigo-300">Back to sponsor funding</a>
    <h1 class="text-2xl font-bold">Scholarship and BEAM reports</h1>
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="report" class="bg-slate-800 rounded-lg">@foreach(['beneficiaries'=>'Scholarship Beneficiary Register','internal_expenditure'=>'Internal Scholarship Expenditure','external_receivables'=>'External Sponsor Receivables','beam_claims'=>'BEAM Claims by Academic Year','beam_ageing'=>'BEAM Outstanding and Ageing','payments'=>'Payments and Allocation History','coverage'=>'Coverage by Fee Vote','controls'=>'Student / Sponsor / GL Reconciliation'] as $key=>$label)<option value="{{ $key }}" @selected($selected===$key)>{{ $label }}</option>@endforeach</select>
        <input type="date" name="as_of" value="{{ $report['as_of'] }}" class="bg-slate-800 rounded-lg"><input name="academic_year" pattern="[0-9]{4}" value="{{ request('academic_year') }}" placeholder="All claim years" class="bg-slate-800 rounded-lg"><button class="bg-indigo-600 px-4 py-2 rounded-lg">View</button><button name="export" value="1" class="bg-slate-700 px-4 py-2 rounded-lg">Export CSV</button>
    </form>
    <p>Receivable and GL controls use the selected accounting date across all years. Claim-year filters narrow detail rows without hiding control-account differences. Ageing uses claim submission date where available, otherwise recognition date. Beneficiary award metadata is current.</p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-slate-900 p-4 rounded-xl">Student GL {{ number_format($report['student_control']['gl_ar_balance'],2) }} · Subledger {{ number_format($report['student_control']['subledger_debtors_balance'],2) }} · Difference {{ number_format($report['student_control']['difference'],2) }}</div>
        <div class="bg-slate-900 p-4 rounded-xl">Student responsibility {{ number_format($report['student_vs_sponsor']['student_outstanding'],2) }} · Sponsor responsibility {{ number_format($report['student_vs_sponsor']['sponsor_outstanding'],2) }}</div>
        <div class="bg-slate-900 p-4 rounded-xl">Trial Balance difference {{ number_format($report['trial_balance']['difference'],2) }}</div>
    </div>
    @php($rows=collect($report[$selected]))
    <div class="overflow-x-auto bg-slate-900 rounded-xl p-4">
        @if($rows->isNotEmpty())<table class="w-full text-sm"><thead><tr>@foreach(array_keys((array)$rows->first()) as $key)<th class="text-left px-3 py-2">{{ str_replace('_',' ',$key) }}</th>@endforeach</tr></thead><tbody>
            @foreach($rows as $row)<tr class="border-t border-slate-700">@foreach(array_keys((array)$rows->first()) as $key)@php($value=((array)$row)[$key]??null)<td class="px-3 py-2">@if($key==='student_id' && $value)<a class="text-indigo-300" href="{{ route('admin.students.scholarships',$value) }}">{{ $value }}</a>@else{{ is_array($value)?json_encode($value):$value }}@endif</td>@endforeach</tr>@endforeach
        </tbody></table>@else<p>No matching records.</p>@endif
    </div>
</div>
@endsection
