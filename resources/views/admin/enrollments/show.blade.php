@extends('layouts.app')

@section('content')
    @php
        $student = $enrollment->student;
        $primaryGuardian = $student?->guardians?->firstWhere('pivot.is_primary', true) ?? $student?->guardians?->first();
        $invoice = $enrollment->invoices->sortByDesc('issued_at')->first();
    @endphp

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Enrollment</h1>
            <p class="text-xs text-slate-400 mt-1">View enrollment details and linked invoices.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Edit</a>
            <a href="{{ route('admin.enrollments.print', $enrollment) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Print</a>
            <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Student</h2>
            <div class="text-xs text-slate-200 space-y-1">
                <div><span class="text-slate-400">Name:</span> {{ $student?->first_name }} {{ $student?->last_name }}</div>
                <div><span class="text-slate-400">Grade/Class:</span> {{ $student?->grade }} {{ $student?->class_name }}</div>
                <div><span class="text-slate-400">Boarding:</span> {{ $student?->is_boarding ? 'Yes' : 'No' }}</div>
                <div><span class="text-slate-400">Transport:</span> {{ $student?->has_transport ? 'Yes' : 'No' }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Guardian</h2>
            @if($primaryGuardian)
                <div class="text-xs text-slate-200 space-y-1">
                    <div><span class="text-slate-400">Name:</span> {{ $primaryGuardian->first_name }} {{ $primaryGuardian->last_name }}</div>
                    <div><span class="text-slate-400">Email:</span> {{ $primaryGuardian->email ?: '—' }}</div>
                    <div><span class="text-slate-400">Phone:</span> {{ $primaryGuardian->phone ?: '—' }}</div>
                </div>
            @else
                <p class="text-xs text-slate-400">No guardian linked.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-100 mb-3">Enrollment</h2>
            <div class="text-xs text-slate-200 space-y-1">
                <div><span class="text-slate-400">Year/Term:</span> {{ $enrollment->academic_year }} · {{ $enrollment->term }}</div>
                <div><span class="text-slate-400">Grade/Class:</span> {{ $enrollment->grade }} {{ $enrollment->class_name }}</div>
                <div><span class="text-slate-400">Date:</span> {{ $enrollment->enrollment_date?->format('Y-m-d') }}</div>
                <div><span class="text-slate-400">Status:</span> {{ ucfirst($enrollment->status) }}</div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mt-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Invoices</h2>
        @if($enrollment->invoices->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Invoice</th>
                        <th class="text-left py-2 font-medium">Issued</th>
                        <th class="text-left py-2 font-medium">Total</th>
                        <th class="text-left py-2 font-medium">Balance</th>
                        <th class="text-left py-2 font-medium">Status</th>
                        <th class="text-left py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrollment->invoices->sortByDesc('issued_at') as $inv)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle font-medium">{{ $inv->number }}</td>
                            <td class="py-2 align-middle">{{ $inv->issued_at?->format('Y-m-d') }}</td>
                            <td class="py-2 align-middle">{{ number_format($inv->total_amount, 2) }}</td>
                            <td class="py-2 align-middle">{{ number_format($inv->balance, 2) }}</td>
                            <td class="py-2 align-middle capitalize">{{ $inv->status }}</td>
                            <td class="py-2 align-middle">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.invoices.show', $inv) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">View</a>
                                    <a href="{{ route('admin.invoices.print', $inv) }}" class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-800 transition">Print</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-xs text-slate-400">No invoices linked to this enrollment.</p>
        @endif
    </div>
@endsection
