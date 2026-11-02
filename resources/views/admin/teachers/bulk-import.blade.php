@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-50">Bulk Import Teachers</h1>
        <p class="mt-1 text-sm text-slate-400">Upload a completed workbook to add new teacher profiles and linked accounts.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-rose-700 bg-rose-950/40 p-4 text-sm text-rose-200">
            <p class="font-semibold">Please correct the upload issue:</p>
            <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
        <h2 class="font-medium text-slate-100">1. Download and complete the template</h2>
        <a href="{{ route('admin.teachers.bulk-import.template') }}" class="inline-flex rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">Download Excel template</a>
        <div class="text-sm text-slate-300 space-y-2">
            <p>Required: <strong>first_name, last_name, email</strong>. Optional employee IDs are assigned automatically when blank.</p>
            <p>Gender must be male, female, or other. Enter dates as YYYY-MM-DD. Experience is years; salary is a non-negative amount.</p>
            <p>Each teacher needs a unique email because the ERP creates a linked login. New accounts receive a unique random password that is never shown in this report; the teacher can use the normal password reset flow.</p>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.teachers.bulk-import.store') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
        @csrf
        <h2 class="font-medium text-slate-100">2. Upload the completed workbook</h2>
        <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-3 text-sm text-slate-200">
        <p class="text-xs text-slate-400">Maximum file size: 10 MB. Existing email addresses or employee IDs are skipped; existing profiles are never edited.</p>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-500">Import teachers</button>
            <a href="{{ route('admin.teachers.index') }}" class="rounded-lg bg-slate-800 px-5 py-2 text-sm text-slate-200">Cancel</a>
        </div>
    </form>
</div>
@endsection
