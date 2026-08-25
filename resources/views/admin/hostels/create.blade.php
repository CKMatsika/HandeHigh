@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Create Boarding Hostel</h1>
            <p class="text-xs text-slate-400 mt-1">Register a new residential hostel facility with supervisory oversight</p>
        </div>
        <a href="{{ route('admin.hostels.index') }}" class="text-slate-400 hover:text-slate-200 text-sm">
            &larr; Back to Hostels
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-lg text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.hostels.store') }}" class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Hostel Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g. Victoria Hall, Chitepo House">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Designated Gender *</label>
                <select name="gender" required
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Gender</option>
                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Girls' Hostel (Supervised by Matron)</option>
                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Boys' Hostel (Supervised by Boarding Master)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">Residential Supervisor (Matron / Boarding Master)</label>
            <select name="supervisor_id"
                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select Non-Teaching Staff Member (Optional)</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('supervisor_id') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->full_name }} — {{ $employee->position }} ({{ $employee->department?->name ?? 'General Staff' }})
                    </option>
                @endforeach
            </select>
            <p class="text-[11px] text-slate-400 mt-1">Supervisors must be Non-Teaching HR staff. Girls' hostels require a Matron; boys' hostels require a Boarding Master.</p>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">Description / Notes</label>
            <textarea name="description" rows="3"
                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500"
                placeholder="Optional facility location details, capacity notes, etc.">{{ old('description') }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                class="rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-blue-500">
            <label for="is_active" class="text-xs text-slate-300">Hostel is currently active and accepting dormitory allocations</label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <a href="{{ route('admin.hostels.index') }}" class="px-4 py-2 rounded-lg text-sm text-slate-400 hover:text-slate-200">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition">
                Create Hostel
            </button>
        </div>
    </form>
</div>
@endsection
