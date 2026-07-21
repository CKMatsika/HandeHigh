@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Curriculum</h1>
            <p class="text-xs text-slate-400 mt-1">Update curriculum assignment for {{ $curriculum->class->name }} - {{ $curriculum->subject->name }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.curricula.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <form method="POST" action="{{ route('admin.curricula.update', $curriculum) }}" class="space-y-4 text-xs text-slate-100">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="class_id">Class</label>
                    <select id="class_id" name="class_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                        <option value="">Select class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id', $curriculum->class_id) == $class->id ? 'selected' : '' }}>{{ $class->name }} ({{ $class->grade }})</option>
                        @endforeach
                    </select>
                    @error('class_id')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                        <option value="">Select subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ old('subject_id', $curriculum->subject_id) == $subject->id ? 'selected' : '' }}>{{ $subject->name }} ({{ $subject->code }})</option>
                        @endforeach
                    </select>
                    @error('subject_id')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="teacher_id">Teacher (optional)</label>
                    <select id="teacher_id" name="teacher_id" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                        <option value="">Select teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('teacher_id', $curriculum->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    @error('teacher_id')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="academic_year">Academic year</label>
                    <input id="academic_year" name="academic_year" type="text" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('academic_year', $curriculum->academic_year) }}">
                    @error('academic_year')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="term">Term (optional)</label>
                    <input id="term" name="term" type="text" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('term', $curriculum->term) }}">
                    @error('term')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="weekly_periods">Weekly periods</label>
                    <input id="weekly_periods" name="weekly_periods" type="number" min="1" max="20" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('weekly_periods', $curriculum->weekly_periods) }}">
                    @error('weekly_periods')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="objectives">Objectives</label>
                    <textarea id="objectives" name="objectives" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">{{ old('objectives', $curriculum->objectives) }}</textarea>
                    @error('objectives')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="materials">Materials</label>
                    <textarea id="materials" name="materials" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">{{ old('materials', $curriculum->materials) }}</textarea>
                    @error('materials')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Update Curriculum</button>
            </div>
        </form>
    </div>
@endsection
