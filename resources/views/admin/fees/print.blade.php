@extends('layouts.app')

@section('content')
    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
            table { width: 100% !important; }
            .bg-slate-900, .bg-slate-950 { background: white !important; border: 1px solid #333 !important; }
            .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-50 { color: black !important; }
            .text-slate-400, .text-slate-500 { color: #555 !important; }
            .border-slate-800, .border-slate-700 { border-color: #333 !important; }
        }
    </style>

    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Fee Structure Print Schedule</h1>
            <p class="text-xs text-slate-400 mt-1">Official published fee tariff for Academic Year {{ $academicYear }}{{ $term ? ' · ' . $term : '' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Print Schedule</button>
            <a href="{{ route('admin.fees.index', ['academic_year' => $academicYear, 'term' => $term]) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-6 py-6 shadow-xl">
        <x-documents.school-header 
            :school="$school ?? null"
            title="Official Approved Fee Schedule"
            :subtitle="'Academic Year ' . $academicYear . ($term ? ' · ' . $term : '')"
            reference="FEE-SCHED-{{ $academicYear }}"
            :date="now()"
            status="Published"
            statusClass="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
        />

        <div class="mt-6">
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                        <th class="text-left py-2 font-medium">Grade / Form</th>
                        <th class="text-left py-2 font-medium">Category</th>
                        <th class="text-left py-2 font-medium">Code</th>
                        <th class="text-left py-2 font-medium">Fee Description</th>
                        <th class="text-right py-2 font-medium">Tariff Amount</th>
                        <th class="text-left py-2 font-medium">Service Type</th>
                        <th class="text-left py-2 font-medium">Optional</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($feeStructures as $fee)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2.5 align-middle font-medium">{{ $fee->grade ?: 'All Grades' }}</td>
                            <td class="py-2.5 align-middle capitalize text-slate-300">{{ $fee->category }}</td>
                            <td class="py-2.5 align-middle font-mono text-slate-400">{{ $fee->code }}</td>
                            <td class="py-2.5 align-middle font-semibold text-slate-100">{{ $fee->label }}</td>
                            <td class="py-2.5 align-middle text-right font-bold font-mono text-emerald-400">${{ number_format($fee->amount, 2) }}</td>
                            <td class="py-2.5 align-middle text-slate-300">{{ $fee->service_type ? ucfirst($fee->service_type) : '—' }}</td>
                            <td class="py-2.5 align-middle text-slate-400">{{ $fee->is_optional ? 'Yes' : 'Compulsory' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-documents.school-footer 
            :school="$school ?? null"
            :showBanking="true"
            customNote="All school fees must be paid into the official school accounts detailed below before term commencement."
        />
    </div>
@endsection
