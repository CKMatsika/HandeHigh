@extends('layouts.app')

@section('content')
    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: #111827 !important; }
            .print-document { box-shadow: none !important; border: 0 !important; border-radius: 0 !important; }
            .print-page-break { break-after: page; page-break-after: always; }
            .print-page-break:last-child { break-after: auto; page-break-after: auto; }
        }
    </style>

    <div class="no-print mx-auto mb-5 flex max-w-5xl flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Print Batch Preview</h1>
            <p class="text-xs text-slate-400">
                {{ $academicYear }}{{ $term ? ' · ' . $term : ' · Full year' }}
                · {{ $grade ?: 'All forms' }}
                · {{ $selectedClass?->name ?? 'All classes' }}
                · {{ $students->count() }} students
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">Print / Save as PDF</button>
            <a href="{{ route('admin.invoices.bulk-print') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200 hover:bg-slate-800">Change Filters</a>
        </div>
    </div>

    @php($documentCount = 0)

    @if(in_array($documentType, ['invoices', 'both'], true))
        @foreach($invoices as $invoice)
            @php($documentCount++)
            <article class="print-document print-page-break mx-auto mb-6 max-w-5xl rounded-xl border border-slate-300 bg-white p-6 text-slate-900 shadow-sm">
                <x-documents.school-header
                    :school="$school"
                    title="Student Invoice"
                    :subtitle="'Academic Year ' . $academicYear . ($term ? ' · ' . $term : ' · Full Year')"
                    :reference="$invoice->number"
                    :date="$invoice->issued_at"
                />

                <div class="my-5 grid grid-cols-2 gap-4 rounded-lg border border-slate-300 p-4 text-sm">
                    <div><div class="text-xs uppercase text-slate-500">Student</div><strong>{{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</strong></div>
                    <div><div class="text-xs uppercase text-slate-500">Admission number</div><strong>{{ $invoice->student?->admission_number ?: '—' }}</strong></div>
                    <div><div class="text-xs uppercase text-slate-500">Form / Class</div><strong>{{ $invoice->enrollment?->grade ?: $invoice->student?->grade ?: '—' }} / {{ $invoice->enrollment?->class_name ?: $invoice->enrollment?->class?->name ?: $invoice->student?->class_name ?: '—' }}</strong></div>
                    <div><div class="text-xs uppercase text-slate-500">Due date</div><strong>{{ $invoice->due_date?->format('d M Y') ?: '—' }}</strong></div>
                </div>

                <table class="w-full border-collapse text-sm">
                    <thead><tr class="border-b-2 border-slate-500 text-left"><th class="py-2">Description</th><th class="py-2">Fee vote</th><th class="py-2 text-right">Amount</th></tr></thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                            <tr class="border-b border-slate-200"><td class="py-2">{{ $item->description ?: $item->category ?: 'Fee' }}</td><td class="py-2">{{ $item->effective_vote_code ?: '—' }}</td><td class="py-2 text-right">{{ number_format((float) $item->line_total, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-3 text-center text-slate-500">No invoice lines recorded.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="font-bold"><td colspan="2" class="py-3 text-right">Invoice total</td><td class="py-3 text-right">{{ number_format((float) $invoice->total_amount, 2) }}</td></tr><tr><td colspan="2" class="text-right">Balance due</td><td class="text-right">{{ number_format((float) $invoice->balance, 2) }}</td></tr></tfoot>
                </table>

                <x-documents.school-footer :school="$school" :showBanking="true" />
            </article>
        @endforeach
    @endif

    @if(in_array($documentType, ['statements', 'both'], true))
        @foreach($students as $student)
            @php($documentCount++)
            @php($statement = $statements->get($student->id))
            @php($cohortEnrollment = $cohortEnrollments->get($student->id))
            <article class="print-document print-page-break mx-auto mb-6 max-w-5xl rounded-xl border border-slate-300 bg-white p-6 text-slate-900 shadow-sm">
                <x-documents.school-header
                    :school="$school"
                    title="Official Student Account Statement"
                    :subtitle="'Academic Year ' . $academicYear . ($term ? ' · ' . $term : ' · Full Year')"
                    :reference="'STMT-' . ($student->admission_number ?: $student->id)"
                    :date="now()"
                />

                <div class="my-5 grid grid-cols-2 gap-4 rounded-lg border border-slate-300 p-4 text-sm md:grid-cols-4">
                    <div><div class="text-xs uppercase text-slate-500">Student</div><strong>{{ $student->first_name }} {{ $student->last_name }}</strong></div>
                    <div><div class="text-xs uppercase text-slate-500">Admission number</div><strong>{{ $student->admission_number ?: '—' }}</strong></div>
                    <div><div class="text-xs uppercase text-slate-500">Form / Class</div><strong>{{ $cohortEnrollment?->grade ?: $cohortEnrollment?->class?->grade ?: $student->grade ?: '—' }} / {{ $cohortEnrollment?->class_name ?: $cohortEnrollment?->class?->name ?: $student->class_name ?: '—' }}</strong></div>
                    <div><div class="text-xs uppercase text-slate-500">Closing balance</div><strong>{{ number_format((float) $statement['closingBalance'], 2) }}</strong></div>
                </div>

                <table class="w-full border-collapse text-xs">
                    <thead><tr class="border-b-2 border-slate-500 text-left uppercase"><th class="py-2">Date</th><th class="py-2">Reference</th><th class="py-2">Description</th><th class="py-2 text-right">Debit</th><th class="py-2 text-right">Credit</th><th class="py-2 text-right">Balance</th></tr></thead>
                    <tbody>
                        @if($statement['openingBalance'] != 0)
                            <tr class="border-b border-slate-200"><td colspan="3" class="py-2">Opening balance</td><td class="text-right">{{ number_format((float) $statement['openingBalance'], 2) }}</td><td></td><td class="text-right">{{ number_format((float) $statement['openingBalance'], 2) }}</td></tr>
                        @endif
                        @forelse($statement['events'] as $event)
                            <tr class="border-b border-slate-200"><td class="py-2">{{ $event['date']?->format('d M Y') ?: '—' }}</td><td class="py-2">{{ $event['reference'] ?: '—' }}</td><td class="py-2">{{ $event['description'] }}</td><td class="text-right">{{ $event['debit'] ? number_format((float) $event['debit'], 2) : '—' }}</td><td class="text-right">{{ $event['credit'] ? number_format((float) $event['credit'], 2) : '—' }}</td><td class="text-right">{{ number_format((float) $event['balance'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-center text-slate-500">No transactions recorded for this period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="border-t-2 border-slate-500 font-bold"><td colspan="5" class="py-3 text-right">Closing account balance</td><td class="text-right">{{ number_format((float) $statement['closingBalance'], 2) }}</td></tr></tfoot>
                </table>

                <x-documents.school-footer :school="$school" :showBanking="true" />
            </article>
        @endforeach
    @endif

    @if($documentCount === 0)
        <div class="mx-auto max-w-5xl rounded-xl border border-amber-700 bg-amber-950/40 p-5 text-sm text-amber-100">
            No existing {{ $documentType === 'invoices' ? 'invoices' : ($documentType === 'statements' ? 'students' : 'documents') }} matched the selected academic year, term and class filters.
        </div>
    @endif
@endsection
