@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Add Subject</h1>
            <p class="text-xs text-slate-400 mt-1">Create a new subject for the school.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.subjects.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <form method="POST" action="{{ route('admin.subjects.store') }}" class="space-y-4 text-xs text-slate-100">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="code">Subject code</label>
                    <input id="code" name="code" type="text" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('code') }}">
                    @error('code')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="name">Subject name</label>
                    <input id="name" name="name" type="text" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" value="{{ old('name') }}">
                    @error('name')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="description">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-[11px] font-medium text-slate-300">
                        <input type="checkbox" name="is_core" value="1" {{ old('is_core') ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-950/60 text-indigo-500">
                        Core subject
                    </label>
                    @error('is_core')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Create Subject</button>
            </div>
        </form>
    </div>
@endsection
