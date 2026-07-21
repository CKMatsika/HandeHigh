@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Enrollments</h1>
            <p class="text-xs text-slate-400 mt-1">Overview of enrolled students and their fee status.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.enrollments.bulk-create') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Bulk Enroll
            </a>
            <a href="{{ route('admin.enrollments.create') }}" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                New Enrollment
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        @if($enrollments->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Student</th>
                        <th class="text-left py-2 font-medium">Guardian</th>
                        <th class="text-left py-2 font-medium">Year / Term</th>
                        <th class="text-left py-2 font-medium">Grade / Class</th>
                        <th class="text-left py-2 font-medium">Invoice</th>
                        <th class="text-left py-2 font-medium">Status</th>
                        <th class="text-left py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrollments as $enrollment)
                        @php
                            $student = $enrollment->student;
                            $primaryGuardian = $student?->guardians?->firstWhere('pivot.is_primary', true) ?? $student?->guardians?->first();
                            $invoice = $enrollment->invoices->sortByDesc('issued_at')->first();
                        @endphp
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">
                                <div class="font-medium">{{ $student?->first_name }} {{ $student?->last_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $student?->grade }} {{ $student?->class_name }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                @if($primaryGuardian)
                                    <div class="font-medium">{{ $primaryGuardian->first_name }} {{ $primaryGuardian->last_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $primaryGuardian->email }}</div>
                                @else
                                    <span class="text-[11px] text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                <div>{{ $enrollment->academic_year }}</div>
                                <div class="text-[11px] text-slate-400">{{ $enrollment->term }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                <div>{{ $enrollment->grade }}</div>
                                <div class="text-[11px] text-slate-400">{{ $enrollment->class_name }}</div>
                            </td>
                            <td class="py-2 align-middle">
                                @if($invoice)
                                    <div class="font-medium">{{ $invoice->number }}</div>
                                    <div class="text-[11px] text-slate-400">Total {{ number_format($invoice->total_amount, 2) }} · Balance {{ number_format($invoice->balance, 2) }}</div>
                                @else
                                    <span class="text-[11px] text-slate-500">No invoice</span>
                                @endif
                            </td>
                            <td class="py-2 align-middle">
                                <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-950/70 px-2 py-0.5 text-[11px] capitalize">
                                    {{ $enrollment->status }}
                                </span>
                            </td>
                            <td class="py-2 align-middle">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.enrollments.show', $enrollment) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">View</a>
                                    <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">Edit</a>
                                    <a href="{{ route('admin.enrollments.print', $enrollment) }}" class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-800 transition">Print</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $enrollments->links() }}
            </div>
        @else
            <p class="text-xs text-slate-400">No enrollments have been captured yet.</p>
        @endif
    </div>
@endsection
