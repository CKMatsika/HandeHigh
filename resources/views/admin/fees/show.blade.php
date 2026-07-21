@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Fee item</h1>
            <p class="text-xs text-slate-400 mt-1">View fee structure details.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.fees.index', ['academic_year' => $fee->academic_year, 'term' => $fee->term]) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-200">
            <div><span class="text-slate-400">Academic year:</span> {{ $fee->academic_year }}</div>
            <div><span class="text-slate-400">Term:</span> {{ $fee->term }}</div>
            <div><span class="text-slate-400">Grade:</span> {{ $fee->grade ?: 'All' }}</div>
            <div><span class="text-slate-400">Category:</span> <span class="capitalize">{{ $fee->category }}</span></div>
            <div><span class="text-slate-400">Code:</span> {{ $fee->code }}</div>
            <div><span class="text-slate-400">Label:</span> {{ $fee->label }}</div>
            <div><span class="text-slate-400">Amount:</span> {{ number_format($fee->amount, 2) }}</div>
            <div><span class="text-slate-400">Optional:</span> {{ $fee->is_optional ? 'Yes' : 'No' }}</div>
            <div><span class="text-slate-400">Service type:</span> {{ $fee->service_type ?: '—' }}</div>
            <div><span class="text-slate-400">Subject name:</span> {{ $fee->subject_name ?: '—' }}</div>
        </div>
    </div>
@endsection
