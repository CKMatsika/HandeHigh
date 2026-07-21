@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Student statement</h1>
            <p class="text-xs text-slate-400 mt-1">Generate a statement for a student with invoices, payments, and running balance.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-200 mb-4">
            <div><span class="text-slate-400">Student:</span> {{ $student->first_name }} {{ $student->last_name }}</div>
            <div><span class="text-slate-400">Grade/Class:</span> {{ $student->grade }} {{ $student->class_name }}</div>
            <div><span class="text-slate-400">School:</span> {{ $school->name }}</div>
        </div>

        <form method="POST" action="{{ route('admin.students.statement.show', $student) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-100">
            @csrf

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="academic_year">Academic year</label>
                <select id="academic_year" name="academic_year" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $academicYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="term">Term (optional)</label>
                <select id="term" name="term" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    <option value="">All terms</option>
                    @foreach($terms as $t)
                        <option value="{{ $t }}" {{ $term === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Generate statement</button>
            </div>
        </form>
    </div>
@endsection
