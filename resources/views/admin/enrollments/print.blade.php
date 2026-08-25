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

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-6 py-6 space-y-6">
        <x-documents.school-header 
            :school="$school ?? null"
            title="Official Student Enrollment Record"
            :subtitle="$enrollment->academic_year . ' · ' . $enrollment->term . ' · ' . $enrollment->grade . ' ' . $enrollment->class_name"
            :document-number="'ENR-' . str_pad($enrollment->id, 5, '0', STR_PAD_LEFT)"
            :date="$enrollment->enrollment_date ?? now()"
            :status="ucfirst($enrollment->status)"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 text-xs">
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2 font-semibold">Student Information</div>
                <div class="space-y-1">
                    <div><span class="text-slate-400">Name:</span> <span class="font-medium text-slate-200">{{ $student?->first_name }} {{ $student?->last_name }}</span></div>
                    <div><span class="text-slate-400">Admission #:</span> <span class="font-mono text-slate-200">{{ $student?->admission_number ?? 'N/A' }}</span></div>
                    <div><span class="text-slate-400">Grade/Class:</span> <span class="text-slate-200">{{ $student?->grade }} {{ $student?->class_name }}</span></div>
                    <div><span class="text-slate-400">Residency / Boarding:</span> <span class="text-slate-200">{{ $student?->is_boarding ? 'Boarding Student' : 'Day Scholar' }}</span></div>
                    <div><span class="text-slate-400">Transport:</span> <span class="text-slate-200">{{ $student?->has_transport ? 'Yes' : 'No' }}</span></div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2 font-semibold">Guardian Information</div>
                @if($primaryGuardian)
                    <div class="space-y-1">
                        <div><span class="text-slate-400">Name:</span> <span class="font-medium text-slate-200">{{ $primaryGuardian->first_name }} {{ $primaryGuardian->last_name }}</span></div>
                        <div><span class="text-slate-400">Relationship:</span> <span class="text-slate-200">{{ ucfirst($primaryGuardian->pivot->relationship ?? 'Guardian') }}</span></div>
                        <div><span class="text-slate-400">Email:</span> <span class="text-slate-200">{{ $primaryGuardian->email ?: '—' }}</span></div>
                        <div><span class="text-slate-400">Phone:</span> <span class="text-slate-200">{{ $primaryGuardian->phone ?: '—' }}</span></div>
                    </div>
                @else
                    <div class="text-slate-400">No guardian linked.</div>
                @endif
            </div>
        </div>

        <div class="mt-6 text-xs">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2 font-semibold">Latest Fee Invoice</div>
            @if($invoice)
                <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4">
                    <div class="flex items-center justify-between">
                        <div class="font-medium text-slate-50 font-mono">{{ $invoice->number }}</div>
                        <div class="text-slate-300">Issued {{ $invoice->issued_at?->format('Y-m-d') }}</div>
                    </div>
                    <div class="mt-2 text-slate-200">Total ${{ number_format($invoice->total_amount, 2) }} · Balance ${{ number_format($invoice->balance, 2) }} · Status <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold bg-slate-800 text-slate-200">{{ ucfirst($invoice->status) }}</span></div>
                </div>
            @else
                <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-4 text-slate-400">No invoice linked.</div>
            @endif
        </div>

        <x-documents.school-footer 
            :school="$school ?? null"
            :show-banking="false"
            notice="Official computerized enrollment record. Valid without signature when generated from verified school management portal."
        />
    </div>
@endsection
