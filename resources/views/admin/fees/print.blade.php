@extends('layouts.app')

@section('content')
    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
            table { width: 100% !important; }
        }
    </style>

    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Fee structure print</h1>
            <p class="text-xs text-slate-400 mt-1">Academic year {{ $academicYear }}{{ $term ? ' · ' . $term : '' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Print</button>
            <a href="{{ route('admin.fees.index', ['academic_year' => $academicYear, 'term' => $term]) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Fee structure</div>
                <div class="text-lg font-semibold text-slate-50">{{ $school->name }}</div>
                <div class="text-xs text-slate-400 mt-1">Academic year {{ $academicYear }}{{ $term ? ' · ' . $term : '' }}</div>
            </div>
            <div class="text-right text-xs text-slate-400">Printed on {{ now()->format('Y-m-d H:i') }}</div>
        </div>

        <div class="mt-6">
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Grade</th>
                        <th class="text-left py-2 font-medium">Category</th>
                        <th class="text-left py-2 font-medium">Code</th>
                        <th class="text-left py-2 font-medium">Label</th>
                        <th class="text-right py-2 font-medium">Amount</th>
                        <th class="text-left py-2 font-medium">Service</th>
                        <th class="text-left py-2 font-medium">Optional</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($feeStructures as $fee)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">{{ $fee->grade ?: 'All' }}</td>
                            <td class="py-2 align-middle capitalize">{{ $fee->category }}</td>
                            <td class="py-2 align-middle">{{ $fee->code }}</td>
                            <td class="py-2 align-middle">{{ $fee->label }}</td>
                            <td class="py-2 align-middle text-right font-medium">{{ number_format($fee->amount, 2) }}</td>
                            <td class="py-2 align-middle">{{ $fee->service_type ? ucfirst($fee->service_type) : '—' }}</td>
                            <td class="py-2 align-middle">{{ $fee->is_optional ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
