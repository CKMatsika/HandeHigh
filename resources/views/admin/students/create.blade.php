@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.students.index') }}" class="text-slate-400 hover:text-slate-200 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-50">New Student</h1>
            <p class="text-slate-400 mt-1">Create a new student profile</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.students.store') }}">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Personal Info -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h3 class="text-sm font-semibold text-slate-300 mb-4">Personal Information</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">First Name *</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Last Name *</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Other Names</label>
                        <input type="text" name="other_names" value="{{ old('other_names') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Gender</label>
                        <select name="gender" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                            <option value="">Select...</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <!-- Academic Info -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h3 class="text-sm font-semibold text-slate-300 mb-4">Academic Details</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Admission Number</label>
                        <input type="text" name="admission_number" value="{{ old('admission_number') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Registration Number</label>
                        <input type="text" name="registration_number" value="{{ old('registration_number') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Grade</label>
                        <select name="grade" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                            <option value="">Select grade...</option>
                            @foreach(['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Form 1','Form 2','Form 3','Form 4','Form 5','Form 6'] as $g)
                                <option value="{{ $g }}" {{ old('grade') == $g ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Class</label>
                        <select name="class_name" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                            <option value="">Select class...</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->name }}" {{ old('class_name') == $class->name ? 'selected' : '' }}>{{ $class->name }} ({{ $class->grade }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">School House</label>
                        <select name="house_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                            <option value="">None</option>
                            @foreach($houses as $house)
                                <option value="{{ $house->id }}" {{ old('house_id') == $house->id ? 'selected' : '' }}>{{ $house->emoji }} {{ $house->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h3 class="text-sm font-semibold text-slate-300 mb-4">Additional Details</h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_boarding" value="1" {{ old('is_boarding') ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-600 bg-slate-700 text-indigo-500">
                        <label class="text-sm text-slate-300">Boarding Student</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="has_transport" value="1" {{ old('has_transport') ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-600 bg-slate-700 text-indigo-500">
                        <label class="text-sm text-slate-300">Uses Transport</label>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Guardians</label>
                        <select name="guardian_ids[]" multiple class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm" size="4">
                            @foreach($guardians as $guardian)
                                <option value="{{ $guardian->id }}">{{ $guardian->first_name ?? $guardian->name }} {{ $guardian->last_name ?? '' }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Hold Ctrl/Cmd to select multiple</p>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition">
                        Create Student
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
