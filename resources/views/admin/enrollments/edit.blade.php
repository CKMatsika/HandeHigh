@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit enrollment</h1>
            <p class="text-xs text-slate-400 mt-1">Update academic year, term, class, and services for this enrollment.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.enrollments.show', $enrollment) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <form method="POST" action="{{ route('admin.enrollments.update', $enrollment) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-100">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="academic_year">Academic year</label>
                <input id="academic_year" type="text" name="academic_year" value="{{ old('academic_year', $enrollment->academic_year) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="term">Term</label>
                <select id="term" name="term" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" required>
                    @foreach($terms as $t)
                        <option value="{{ $t }}" {{ old('term', $enrollment->term) === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="grade">Grade</label>
                <input id="grade" type="text" name="grade" value="{{ old('grade', $enrollment->grade) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="class_name">Class</label>
                <input id="class_name" type="text" name="class_name" value="{{ old('class_name', $enrollment->class_name) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="enrollment_date">Enrollment date</label>
                <input id="enrollment_date" type="date" name="enrollment_date" value="{{ old('enrollment_date', $enrollment->enrollment_date?->format('Y-m-d')) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="status">Status</label>
                <select id="status" name="status" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" required>
                    @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $enrollment->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center mt-5">
                <label class="inline-flex items-center gap-2 text-[11px] text-slate-300">
                    <input type="checkbox" name="is_boarding" value="1" {{ old('is_boarding', $enrollment->is_boarding) ? 'checked' : '' }} class="h-3 w-3 rounded border-slate-600 bg-slate-900 text-indigo-500">
                    <span>Boarding</span>
                </label>
            </div>

            <div class="flex items-center mt-5">
                <label class="inline-flex items-center gap-2 text-[11px] text-slate-300">
                    <input type="checkbox" name="has_transport" value="1" {{ old('has_transport', $enrollment->has_transport) ? 'checked' : '' }} class="h-3 w-3 rounded border-slate-600 bg-slate-900 text-indigo-500">
                    <span>Transport</span>
                </label>
            </div>

            <div class="md:col-span-2 flex justify-end gap-2 mt-2">
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">Save changes</button>
                <a href="{{ route('admin.enrollments.show', $enrollment) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Cancel</a>
            </div>
        </form>
    </div>
@endsection
