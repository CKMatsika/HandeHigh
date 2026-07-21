@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Class</h1>
            <p class="text-xs text-slate-400 mt-1">Update class details for {{ $class->name }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.classes.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <form method="POST" action="{{ route('admin.classes.update', $class) }}" class="space-y-4 text-xs text-slate-100">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="name">Class name</label>
                    <input id="name" name="name" type="text" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('name', $class->name) }}">
                    @error('name')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="grade">Grade</label>
                    <input id="grade" name="grade" type="text" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('grade', $class->grade) }}">
                    @error('grade')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="academic_year">Academic year</label>
                    <input id="academic_year" name="academic_year" type="text" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('academic_year', $class->academic_year) }}">
                    @error('academic_year')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="term">Term (optional)</label>
                    <input id="term" name="term" type="text" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('term', $class->term) }}">
                    @error('term')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="teacher_id">Teacher (optional)</label>
                    <select id="teacher_id" name="teacher_id" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                        <option value="">Select teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('teacher_id', $class->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    @error('teacher_id')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Update Class</button>
            </div>
        </form>
    </div>
@endsection
