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

            <!-- Zimbabwe Statutory & Tax Compliance Section -->
            <div class="mt-8 pt-6 border-t border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-emerald-400 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Zimbabwe Statutory & Tax Compliance Profile
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Captures employee identifiers for ZIMRA TaRMS, NSSA P4 returns, and NEC Collective Bargaining Councils.</p>
                    </div>
                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">ZIMRA & NSSA Verified</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">National ID Number</label>
                        <input type="text" name="national_id" value="{{ old('national_id', $employee->national_id) }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="e.g. 63-1234567-X-00">
                        @error('national_id')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">ZIMRA TIN (Taxpayer ID)</label>
                        <input type="text" name="zimra_tin" value="{{ old('zimra_tin', $employee->zimra_tin) }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="e.g. 2001234567">
                        @error('zimra_tin')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">NSSA Social Security Number</label>
                        <input type="text" name="nssa_number" value="{{ old('nssa_number', $employee->nssa_number) }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="e.g. 987654321">
                        @error('nssa_number')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">NEC Sector Code</label>
                        <select name="nec_sector_code"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="NEC-EDU" {{ old('nec_sector_code', $employee->nec_sector_code ?? 'NEC-EDU') == 'NEC-EDU' ? 'selected' : '' }}>NEC Educational Services (1.5%)</option>
                            <option value="NEC-COMM" {{ old('nec_sector_code', $employee->nec_sector_code) == 'NEC-COMM' ? 'selected' : '' }}>NEC Commercial & Administrative (2.0%)</option>
                            <option value="NEC-AGRIC" {{ old('nec_sector_code', $employee->nec_sector_code) == 'NEC-AGRIC' ? 'selected' : '' }}>NEC Agriculture (1.0%)</option>
                            <option value="NONE" {{ old('nec_sector_code', $employee->nec_sector_code) == 'NONE' ? 'selected' : '' }}>Exempt / None</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Medical Aid Contribution (USD)</label>
                        <input type="number" step="0.01" min="0" name="medical_aid_usd" value="{{ old('medical_aid_usd', $employee->medical_aid_usd ?? '0.00') }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <span class="text-[11px] text-slate-400">Qualifies for 50% ZIMRA Medical Tax Credit</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Medical Aid Contribution (ZWG)</label>
                        <input type="number" step="0.01" min="0" name="medical_aid_zwg" value="{{ old('medical_aid_zwg', $employee->medical_aid_zwg ?? '0.00') }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="mt-4 p-3 bg-slate-800/40 border border-slate-700/60 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="trade_union_member" name="trade_union_member" value="1" {{ old('trade_union_member', $employee->trade_union_member) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-blue-600 bg-slate-800 border-slate-700 focus:ring-blue-500">
                        <div>
                            <label for="trade_union_member" class="text-xs font-medium text-slate-200 cursor-pointer">Trade Union Membership (e.g. PTUZ / ZIMTA)</label>
                            <p class="text-[11px] text-slate-400">Deducts union subscription dues according to collective bargaining agreements</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-slate-300">Rate (%):</label>
                        <input type="number" step="0.01" min="0" max="100" name="trade_union_rate" value="{{ old('trade_union_rate', $employee->trade_union_rate ?? '1.00') }}"
                               class="w-20 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-xs text-slate-50">
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
