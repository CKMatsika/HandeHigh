@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-50">Bulk Invoice &amp; Statement Printing</h1>
                <p class="mt-1 text-sm text-slate-400">Print existing financial documents for an active class cohort. This screen does not create or change invoices, payments, or balances.</p>
            </div>
            <a href="{{ route('admin.invoices.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200 hover:bg-slate-800">Back to Invoices</a>
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-rose-700 bg-rose-950/50 p-4 text-sm text-rose-200">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.invoices.bulk-print.output') }}" method="GET" target="_blank" class="rounded-xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-xs text-slate-300">
                    Academic Year
                    <select name="academic_year" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" @selected((string) old('academic_year', (string) now()->year) === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs text-slate-300">
                    Term
                    <select name="term" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Full academic year</option>
                        @foreach(['Term 1', 'Term 2', 'Term 3'] as $term)
                            <option value="{{ $term }}" @selected(old('term') === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs text-slate-300">
                    Form / Grade
                    <select name="grade" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">All forms</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade }}" @selected(old('grade') === $grade)>{{ $grade }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs text-slate-300">
                    Class / Stream
                    <select name="class_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">All classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->grade }} · {{ $class->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs text-slate-300 md:col-span-2">
                    Documents
                    <select name="document_type" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="invoices" @selected(old('document_type', 'invoices') === 'invoices')>Invoices</option>
                        <option value="statements" @selected(old('document_type') === 'statements')>Statements</option>
                        <option value="both" @selected(old('document_type') === 'both')>Invoices and statements</option>
                    </select>
                </label>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 pt-4">
                <p class="text-xs text-slate-400">Print runs are limited to 60 students. Narrow large cohorts by class to keep printing responsive.</p>
                <button type="submit" class="rounded-full bg-violet-600 px-5 py-2 text-sm font-semibold text-white hover:bg-violet-500">Prepare Print Batch</button>
            </div>
        </form>
    </div>
@endsection
