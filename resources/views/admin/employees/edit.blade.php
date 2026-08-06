@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.employees.show', $employee) }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Edit — {{ $employee->full_name }}</h1>
            <p class="text-xs text-slate-400 mt-1">Update employee information.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('admin.employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Personal Information</h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $employee->email) }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Phone *</label>
                        <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Date of Birth *</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth->format('Y-m-d')) }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Address</label>
                        <textarea name="address" rows="2" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('address', $employee->address) }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Emergency Contact</label>
                            <input type="text" name="emergency_contact" value="{{ old('emergency_contact', $employee->emergency_contact) }}"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Emergency Phone</label>
                            <input type="text" name="emergency_phone" value="{{ old('emergency_phone', $employee->emergency_phone) }}"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Profile Photo</label>
                        @if($employee->profile_photo)
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ Storage::url($employee->profile_photo) }}" class="w-10 h-10 rounded-full object-cover">
                                <span class="text-xs text-slate-400">Current photo</span>
                            </div>
                        @endif
                        <input type="file" name="profile_photo" accept="image/*"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Employment Information</h3>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Employee ID *</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id', $employee->employee_id) }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Department *</label>
                        <select name="department_id" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id', $employee->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Position *</label>
                        <input type="text" name="position" value="{{ old('position', $employee->position) }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Employment Type *</label>
                        <select name="employment_type" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="full_time" {{ old('employment_type', $employee->employment_type) == 'full_time' ? 'selected' : '' }}>Full Time</option>
                            <option value="part_time" {{ old('employment_type', $employee->employment_type) == 'part_time' ? 'selected' : '' }}>Part Time</option>
                            <option value="contract" {{ old('employment_type', $employee->employment_type) == 'contract' ? 'selected' : '' }}>Contract</option>
                            <option value="intern" {{ old('employment_type', $employee->employment_type) == 'intern' ? 'selected' : '' }}>Intern</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Employment Status *</label>
                        <select name="employment_status" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="active" {{ old('employment_status', $employee->employment_status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="on_leave" {{ old('employment_status', $employee->employment_status) == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                            <option value="resigned" {{ old('employment_status', $employee->employment_status) == 'resigned' ? 'selected' : '' }}>Resigned</option>
                            <option value="terminated" {{ old('employment_status', $employee->employment_status) == 'terminated' ? 'selected' : '' }}>Terminated</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Hire Date *</label>
                        <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date->format('Y-m-d')) }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Salary *</label>
                        <input type="number" name="salary" value="{{ old('salary', $employee->salary) }}" step="0.01" min="0" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Work Schedule</label>
                        <textarea name="work_schedule" rows="2" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('work_schedule', $employee->work_schedule) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Update Employee</button>
                <a href="{{ route('admin.employees.show', $employee) }}" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium py-2 px-4 rounded-lg text-center transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
