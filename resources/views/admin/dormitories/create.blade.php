@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Create Dormitory</h1>
            <p class="text-xs text-slate-400 mt-1">Add a new residential dormitory and initialize bed inventory</p>
        </div>
        <a href="{{ route('admin.dormitories.index') }}" class="text-slate-400 hover:text-slate-200 text-sm">
            &larr; Back to Dormitories
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

    <form method="POST" action="{{ route('admin.dormitories.store') }}" class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Dormitory Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g. Block A - Room 101, Chaminuka West">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Parent Hostel (Optional)</label>
                <select name="hostel_id"
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Independent Dormitory Unit</option>
                    @foreach($hostels as $hostel)
                        <option value="{{ $hostel->id }}" {{ (old('hostel_id') == $hostel->id || request('hostel_id') == $hostel->id) ? 'selected' : '' }}>
                            {{ $hostel->name }} ({{ ucfirst($hostel->gender) }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Gender *</label>
                <select name="gender" required
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female / Girls</option>
                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male / Boys</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Capacity (Number of Beds) *</label>
                <input type="number" name="capacity" value="{{ old('capacity', 20) }}" min="1" required
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">Dedicated Supervisor / Matron (Optional)</label>
            <select name="supervisor_id"
                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Inherit from Hostel or Leave Unassigned</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('supervisor_id') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->full_name }} — {{ $employee->position }}
                    </option>
                @endforeach
            </select>
            <p class="text-[11px] text-slate-400 mt-1">Supervisors must be Non-Teaching HR staff.</p>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">Description / Location Details</label>
            <textarea name="description" rows="3"
                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500"
                placeholder="Floor, wing, room number, or special instructions">{{ old('description') }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="create_beds" id="create_beds" value="1" {{ old('create_beds', true) ? 'checked' : '' }}
                class="rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-blue-500">
            <label for="create_beds" class="text-xs text-slate-300">Automatically generate numbered bed records (Bed-01, Bed-02, ...) matching capacity</label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <a href="{{ route('admin.dormitories.index') }}" class="px-4 py-2 rounded-lg text-sm text-slate-400 hover:text-slate-200">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition">
                Create Dormitory
            </button>
        </div>
    </form>
</div>
@endsection
