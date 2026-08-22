@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.timetables.show', $timetable) }}" class="text-slate-400 hover:text-slate-300 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-50">Edit Timetable</h1>
            <p class="text-xs text-slate-400 mt-1">Update timetable details and general properties.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-rose-500/10 border border-rose-500/20 p-4 text-sm text-rose-400">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6 shadow-sm">
        <form action="{{ route('admin.timetables.update', $timetable) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Timetable Name *</label>
                <input type="text" name="name" required value="{{ old('name', $timetable->name) }}"
                       class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Academic Year</label>
                    <input type="text" disabled value="{{ $timetable->academic_year }}" class="w-full px-3 py-2 bg-slate-800/50 border border-slate-800 rounded-lg text-slate-400 text-xs cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Term</label>
                    <input type="text" disabled value="{{ $timetable->term }}" class="w-full px-3 py-2 bg-slate-800/50 border border-slate-800 rounded-lg text-slate-400 text-xs cursor-not-allowed">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="4"
                          class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs"
                          placeholder="Optional notes or description...">{{ old('description', $timetable->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('admin.timetables.show', $timetable) }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-medium transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-medium transition shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
