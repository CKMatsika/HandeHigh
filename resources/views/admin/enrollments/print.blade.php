@extends('layouts.app')

@section('content')
    @php
        $student = $enrollment->student;
        $primaryGuardian = $student?->guardians?->firstWhere('pivot.is_primary', true) ?? $student?->guardians?->first();
        $invoice = $enrollment->invoices->sortByDesc('issued_at')->first();
    @endphp

    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
        }
    </style>

    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Enrollment print</h1>
            <p class="text-xs text-slate-400 mt-1">Use your browser print dialog to save as PDF or print.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Print</button>
            <a href="{{ route('admin.enrollments.show', $enrollment) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Enrollment</div>
                <div class="text-lg font-semibold text-slate-50">{{ $student?->first_name }} {{ $student?->last_name }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $enrollment->academic_year }} · {{ $enrollment->term }} · {{ $enrollment->grade }} {{ $enrollment->class_name }}</div>
            </div>
            <div class="text-right text-xs text-slate-300">
                <div><span class="text-slate-400">School:</span> {{ $school->name }}</div>
                <div><span class="text-slate-400">Date:</span> {{ $enrollment->enrollment_date?->format('Y-m-d') }}</div>
                <div><span class="text-slate-400">Status:</span> {{ ucfirst($enrollment->status) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 text-xs">
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Student</div>
                <div><span class="text-slate-400">Name:</span> {{ $student?->first_name }} {{ $student?->last_name }}</div>
                <div><span class="text-slate-400">Grade/Class:</span> {{ $student?->grade }} {{ $student?->class_name }}</div>
                <div><span class="text-slate-400">Boarding:</span> {{ $student?->is_boarding ? 'Yes' : 'No' }}</div>
                <div><span class="text-slate-400">Transport:</span> {{ $student?->has_transport ? 'Yes' : 'No' }}</div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Guardian</div>
                @if($primaryGuardian)
                    <div><span class="text-slate-400">Name:</span> {{ $primaryGuardian->first_name }} {{ $primaryGuardian->last_name }}</div>
                    <div><span class="text-slate-400">Email:</span> {{ $primaryGuardian->email ?: '—' }}</div>
                    <div><span class="text-slate-400">Phone:</span> {{ $primaryGuardian->phone ?: '—' }}</div>
                @else
                    <div class="text-slate-400">No guardian linked.</div>
                @endif
            </div>
        </div>

        <div class="mt-6 text-xs">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Latest invoice</div>
            @if($invoice)
                <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                    <div class="flex items-center justify-between">
                        <div class="font-medium text-slate-50">{{ $invoice->number }}</div>
                        <div class="text-slate-300">Issued {{ $invoice->issued_at?->format('Y-m-d') }}</div>
                    </div>
                    <div class="mt-2 text-slate-200">Total {{ number_format($invoice->total_amount, 2) }} · Balance {{ number_format($invoice->balance, 2) }} · Status {{ ucfirst($invoice->status) }}</div>
                </div>
            @else
                <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4 text-slate-400">No invoice linked.</div>
            @endif
        </div>
    </div>
@endsection
