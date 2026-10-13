@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Edit Dormitory</h1>
            <p class="text-xs text-slate-400 mt-1">Update dormitory details and configuration</p>
        </div>
        <a href="{{ route('admin.dormitories.show', $dormitory) }}" class="text-slate-400 hover:text-slate-200 text-sm">
            &larr; Back to Dormitory
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

    <form method="POST" action="{{ route('admin.dormitories.update', $dormitory) }}" class="bg-slate-900/80 rounded-xl border border-slate-800 p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Dormitory Name *</label>
                <input type="text" name="name" value="{{ old('name', $dormitory->name) }}" required
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Parent Hostel (Optional)</label>
                <select name="hostel_id"
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Independent Dormitory Unit</option>
                    @foreach($hostels as $hostel)
                        <option value="{{ $hostel->id }}" {{ (old('hostel_id', $dormitory->hostel_id) == $hostel->id) ? 'selected' : '' }}>
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
                    <option value="female" {{ old('gender', $dormitory->gender) === 'female' ? 'selected' : '' }}>Female / Girls</option>
                    <option value="male" {{ old('gender', $dormitory->gender) === 'male' ? 'selected' : '' }}>Male / Boys</option>
                    <option value="mixed" {{ old('gender', $dormitory->gender) === 'mixed' ? 'selected' : '' }}>Mixed</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-2">Capacity (Beds) *</label>
                <input type="number" name="capacity" value="{{ old('capacity', $dormitory->capacity) }}" min="1" required
                    class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">Supervisor (Staff)</label>
            <select name="supervisor_id"
                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">None Assigned</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('supervisor_id', $dormitory->supervisor_id) == $employee->id ? 'selected' : '' }}>
                        {{ $employee->full_name }} ({{ $employee->employee_number ?? 'Staff' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-2">Description</label>
            <textarea name="description" rows="3"
                class="w-full px-3 py-2 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description', $dormitory->description) }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $dormitory->is_active) ? 'checked' : '' }}
                class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-blue-500">
            <label for="is_active" class="text-xs text-slate-300">Active Dormitory</label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <a href="{{ route('admin.dormitories.show', $dormitory) }}" class="px-4 py-2 border border-slate-700 rounded-lg text-slate-300 text-sm hover:bg-slate-800">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                Update Dormitory
            </button>
        </div>
    </form>
</div>
@endsection
