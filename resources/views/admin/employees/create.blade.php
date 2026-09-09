@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.employees.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Add New Employee</h1>
            <p class="text-xs text-slate-400 mt-1">Create a new employee record for non-teaching staff.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('admin.employees.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Personal Information</h3>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">First Name *</label>
                            <input type="text" name="first_name" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="John">
                            @error('first_name')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Last Name *</label>
                            <input type="text" name="last_name" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Doe">
                            @error('last_name')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Email Address *</label>
                        <input type="email" name="email" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="john.doe@example.com">
                        @error('email')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Phone Number *</label>
                        <input type="text" name="phone" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="+1234567890">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Date of Birth *</label>
                        <input type="date" name="date_of_birth" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('date_of_birth')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Address</label>
                        <textarea name="address" rows="3"
                                  class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="123 Main St, City, State"></textarea>
                        @error('address')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Emergency Contact</label>
                            <input type="text" name="emergency_contact"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Jane Doe">
                            @error('emergency_contact')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Emergency Phone</label>
                            <input type="text" name="emergency_phone"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="+1234567890">
                            @error('emergency_phone')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Profile Photo</label>
                        <input type="file" name="profile_photo" accept="image/*"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-600 file:text-white hover:file:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('profile_photo')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Employment Information</h3>
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Employee ID *</label>
                        <input type="text" name="employee_id" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="EMP001">
                        @error('employee_id')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Department *</label>
                        <div class="flex gap-2">
                            <select name="department_id" required
                                    class="flex-1 px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            <a href="{{ route('admin.departments.create') }}" class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition-colors" target="_blank">
                                + Add
                            </a>
                        </div>
                        @error('department_id')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Position *</label>
                        <input type="text" name="position" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Administrative Assistant">
                        @error('position')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Employment Type *</label>
                        <select name="employment_type" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="intern">Intern</option>
                        </select>
                        @error('employment_type')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Employment Status *</label>
                        <select name="employment_status" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="active">Active</option>
                            <option value="on_leave">On Leave</option>
                            <option value="resigned">Resigned</option>
                            <option value="terminated">Terminated</option>
                        </select>
                        @error('employment_status')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Hire Date *</label>
                        <input type="date" name="hire_date" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('hire_date')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Salary *</label>
                        <input type="number" name="salary" required step="0.01" min="0"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="50000.00">
                        @error('salary')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Work Schedule</label>
                        <textarea name="work_schedule" rows="2"
                                  class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Monday - Friday, 9:00 AM - 5:00 PM"></textarea>
                        @error('work_schedule')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
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
                        <input type="text" name="national_id" value="{{ old('national_id') }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="e.g. 63-1234567-X-00">
                        @error('national_id')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">ZIMRA TIN (Taxpayer ID)</label>
                        <input type="text" name="zimra_tin" value="{{ old('zimra_tin') }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="e.g. 2001234567">
                        @error('zimra_tin')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">NSSA Social Security Number</label>
                        <input type="text" name="nssa_number" value="{{ old('nssa_number') }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="e.g. 987654321">
                        @error('nssa_number')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">NEC Sector Code</label>
                        <select name="nec_sector_code"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="NEC-EDU" {{ old('nec_sector_code', 'NEC-EDU') == 'NEC-EDU' ? 'selected' : '' }}>NEC Educational Services (1.5%)</option>
                            <option value="NEC-COMM" {{ old('nec_sector_code') == 'NEC-COMM' ? 'selected' : '' }}>NEC Commercial & Administrative (2.0%)</option>
                            <option value="NEC-AGRIC" {{ old('nec_sector_code') == 'NEC-AGRIC' ? 'selected' : '' }}>NEC Agriculture (1.0%)</option>
                            <option value="NONE" {{ old('nec_sector_code') == 'NONE' ? 'selected' : '' }}>Exempt / None</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Medical Aid Contribution (USD)</label>
                        <input type="number" step="0.01" min="0" name="medical_aid_usd" value="{{ old('medical_aid_usd', '0.00') }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <span class="text-[11px] text-slate-400">Qualifies for 50% ZIMRA Medical Tax Credit</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Medical Aid Contribution (ZWG)</label>
                        <input type="number" step="0.01" min="0" name="medical_aid_zwg" value="{{ old('medical_aid_zwg', '0.00') }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="mt-4 p-3 bg-slate-800/40 border border-slate-700/60 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="trade_union_member" name="trade_union_member" value="1" {{ old('trade_union_member') ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-blue-600 bg-slate-800 border-slate-700 focus:ring-blue-500">
                        <div>
                            <label for="trade_union_member" class="text-xs font-medium text-slate-200 cursor-pointer">Trade Union Membership (e.g. PTUZ / ZIMTA)</label>
                            <p class="text-[11px] text-slate-400">Deducts union subscription dues according to collective bargaining agreements</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-slate-300">Rate (%):</label>
                        <input type="number" step="0.01" min="0" max="100" name="trade_union_rate" value="{{ old('trade_union_rate', '1.00') }}"
                               class="w-20 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-xs text-slate-50">
                    </div>
                </div>
            </div>

            <!-- User Account Creation -->
            <div class="mt-8 pt-6 border-t border-slate-800">
                <h3 class="text-sm font-semibold text-slate-50 mb-4">User Account</h3>
                
                <div class="flex items-center justify-between p-4 bg-slate-800/50 rounded-lg">
                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer mr-4">
                            <input type="checkbox" name="create_user_account" value="1" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                        <div>
                            <div class="text-sm font-medium text-slate-300">Create User Account</div>
                            <div class="text-xs text-slate-400">Create a system account for this employee</div>
                        </div>
                    </div>
                </div>

                <div id="user-account-fields" class="mt-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Password *</label>
                            <input type="password" name="password"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                            @error('password')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Confirm Password *</label>
                            <input type="password" name="password_confirmation"
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="••••••••">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Create Employee
                </button>
                <a href="{{ route('admin.employees.index') }}" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium py-2 px-4 rounded-lg text-center transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.querySelector('input[name="create_user_account"]');
    const userFields = document.getElementById('user-account-fields');
    
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            userFields.classList.remove('hidden');
        } else {
            userFields.classList.add('hidden');
        }
    });
});
</script>
@endsection
