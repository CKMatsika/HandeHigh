@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.students.index') }}" class="text-slate-400 hover:text-slate-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-lg font-bold">
                    {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-50">{{ $student->full_name }}</h1>
                    <p class="text-slate-400 text-sm">{{ $student->admission_number ?? 'No admission #' }} | {{ $student->grade ?? 'Ungraded' }} {{ $student->class_name ?? '' }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @php
                $statusColors = ['active' => 'green', 'inactive' => 'slate', 'graduated' => 'blue', 'transferred' => 'yellow', 'expelled' => 'red'];
                $color = $statusColors[$student->status] ?? 'slate';
            @endphp
            <span class="text-xs px-3 py-1 rounded-full bg-{{ $color }}-500/20 text-{{ $color }}-400 font-medium">{{ ucfirst($student->status) }}</span>
            <a href="{{ route('admin.students.edit', $student) }}" class="bg-slate-700 hover:bg-slate-600 text-slate-200 px-3 py-2 rounded-lg text-sm transition">Edit Profile</a>
        </div>
    </div>

    <!-- Tabs -->
    @php
        $tabs = [
            'overview' => 'Overview',
            'subjects' => 'Subjects',
            'positions' => 'Leadership',
            'clubs' => 'Clubs',
            'sports' => 'Sports',
            'boarding' => 'Boarding',
            'assets' => 'Assets',
            'library' => 'Library',
        ];
    @endphp
    <div class="flex gap-1 border-b border-slate-800 overflow-x-auto">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.students.show', ['student' => $student, 'tab' => $key]) }}"
               class="px-4 py-2.5 text-sm font-medium whitespace-nowrap transition {{ $activeTab === $key ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-slate-400 hover:text-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Tab Content -->
    @if($activeTab === 'overview')
        @include('admin.students._overview')
    @elseif($activeTab === 'subjects')
        @include('admin.students._subjects')
    @elseif($activeTab === 'positions')
        @include('admin.students._positions')
    @elseif($activeTab === 'clubs')
        @include('admin.students._clubs')
    @elseif($activeTab === 'sports')
        @include('admin.students._sports')
    @elseif($activeTab === 'boarding')
        @include('admin.students._boarding')
    @elseif($activeTab === 'assets')
        @include('admin.students._assets')
    @elseif($activeTab === 'library')
        @include('admin.students._library')
    @endif
</div>
@endsection
