@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Edit Teacher</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $teacher->full_name }}</p>
        </div>

        <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}">
            @csrf @method('PUT')
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-semibold text-slate-50 mb-4">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $teacher->first_name) }}" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $teacher->last_name) }}" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Email *</label>
                            <input type="email" name="email" value="{{ old('email', $teacher->email) }}" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $teacher->phone) }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Gender</label>
                            <select name="gender" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                                <option value="">Select</option>
                                <option value="male" {{ old('gender', $teacher->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $teacher->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $teacher->gender) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $teacher->date_of_birth?->format('Y-m-d')) }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Address</label>
                            <textarea name="address" rows="2" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">{{ old('address', $teacher->address) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-6">
                    <h3 class="text-sm font-semibold text-slate-50 mb-4">Professional Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Employee ID</label>
                            <input type="text" name="employee_id" value="{{ old('employee_id', $teacher->employee_id) }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Specialization</label>
                            <input type="text" name="specialization" value="{{ old('specialization', $teacher->specialization) }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Highest Qualification</label>
                            <input type="text" name="qualification" value="{{ old('qualification', $teacher->qualification) }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Experience (Years)</label>
                            <input type="number" name="experience_years" value="{{ old('experience_years', $teacher->experience_years) }}" min="0" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Hire Date</label>
                            <input type="date" name="hire_date" value="{{ old('hire_date', $teacher->hire_date?->format('Y-m-d')) }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Salary ($)</label>
                            <input type="number" step="0.01" name="salary" value="{{ old('salary', $teacher->salary) }}" min="0" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-6">
                    <h3 class="text-sm font-semibold text-slate-50 mb-4">User Account</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">New Password</label>
                            <input type="password" name="password" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" placeholder="Leave blank to keep current">
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-6">
                    <h3 class="text-sm font-semibold text-slate-50 mb-4">Subject Assignments</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-60 overflow-y-auto">
                        @forelse($subjects as $subj)
                            <label class="flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-slate-800/50 cursor-pointer">
                                <input type="checkbox" name="subjects[]" value="{{ $subj->id }}"
                                    {{ $teacher->subjects->contains($subj->id) ? 'checked' : '' }}
                                    class="rounded bg-slate-700 border-slate-600 text-emerald-600">
                                <span class="text-xs text-slate-100">{{ $subj->name }}</span>
                            </label>
                        @empty
                            <p class="text-xs text-slate-400 col-span-3">No subjects created yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <a href="{{ route('admin.teachers.show', $teacher) }}" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700">Cancel</a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white hover:bg-emerald-700">Update Teacher</button>
            </div>
        </form>
    </div>
</div>
@endsection
