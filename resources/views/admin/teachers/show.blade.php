@extends('layouts.app')

@section('content')
@php
    $tabs = [
        'overview' => 'Overview',
        'qualifications' => 'Qualifications',
        'subjects' => 'Subjects',
        'classes' => 'Classes',
        'roles' => 'Roles',
    ];
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.teachers.index') }}" class="text-slate-400 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-indigo-500/20 flex items-center justify-center">
                    <span class="text-lg text-indigo-400 font-bold">{{ substr($teacher->first_name, 0, 1) }}{{ substr($teacher->last_name, 0, 1) }}</span>
                </div>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-slate-50">{{ $teacher->full_name }}</h1>
                    <p class="text-xs text-slate-400">{{ $teacher->employee_id }} · {{ $teacher->specialization ?? 'No Specialization' }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-xs font-medium text-white hover:bg-amber-700">Edit</a>
            <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST" class="inline" onsubmit="return confirm('Delete this teacher?')">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center rounded-lg bg-red-600/20 px-3 py-2 text-xs font-medium text-red-400 hover:bg-red-600/30">Delete</button>
            </form>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-800">
        <nav class="flex gap-6">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.teachers.show', ['teacher' => $teacher, 'tab' => $key]) }}"
                   class="pb-3 text-xs font-medium transition-colors border-b-2
                   {{ $tab === $key ? 'text-indigo-400 border-indigo-400' : 'text-slate-400 border-transparent hover:text-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Tab Content -->
    @if($tab === 'overview')
        @include('admin.teachers._overview')
    @elseif($tab === 'qualifications')
        @include('admin.teachers._qualifications')
    @elseif($tab === 'subjects')
        @include('admin.teachers._subjects')
    @elseif($tab === 'classes')
        @include('admin.teachers._classes')
    @elseif($tab === 'roles')
        @include('admin.teachers._roles')
    @endif
</div>
@endsection
