@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Smart Rules</h1>
            <p class="text-xs text-slate-400 mt-1">Configure automated restrictions and policies for your school.</p>
        </div>
    </div>

    <!-- Fee Rules -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-lg font-semibold text-slate-50 mb-4">Fee Restrictions</h2>
        <form action="{{ route('admin.smart-rules.fees.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <div>
                            <label class="text-sm font-medium text-slate-300">Restrict Exam Access</label>
                            <p class="text-xs text-slate-400">Block students from accessing exams if fees are unpaid</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="restrict_exam_access" value="1" {{ $feeRules['restrict_exam_access'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <div>
                            <label class="text-sm font-medium text-slate-300">Restrict Result Access</label>
                            <p class="text-xs text-slate-400">Block students from viewing results if fees are unpaid</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="restrict_result_access" value="1" {{ $feeRules['restrict_result_access'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <div>
                            <label class="text-sm font-medium text-slate-300">Restrict Next Term Registration</label>
                            <p class="text-xs text-slate-400">Block registration for next term if current fees are unpaid</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="restrict_next_term_registration" value="1" {{ $feeRules['restrict_next_term_registration'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <div>
                            <label class="text-sm font-medium text-slate-300">Late Payment Penalties</label>
                            <p class="text-xs text-slate-400">Automatically apply penalties for late payments</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="late_payment_penalties" value="1" {{ $feeRules['late_payment_penalties'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="p-3 bg-slate-800/50 rounded-lg">
                        <label class="text-sm font-medium text-slate-300">Penalty Type</label>
                        <select name="late_payment_penalty_type" class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="fixed" {{ $feeRules['late_payment_penalty_type'] === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                            <option value="percentage" {{ $feeRules['late_payment_penalty_type'] === 'percentage' ? 'selected' : '' }}>Percentage</option>
                        </select>
                    </div>

                    <div class="p-3 bg-slate-800/50 rounded-lg">
                        <label class="text-sm font-medium text-slate-300">
                            {{ $feeRules['late_payment_penalty_type'] === 'percentage' ? 'Penalty Percentage' : 'Penalty Amount' }}
                        </label>
                        <input type="number" name="{{ $feeRules['late_payment_penalty_type'] === 'percentage' ? 'late_payment_penalty_percentage' : 'late_payment_penalty_amount' }}" 
                               value="{{ $feeRules['late_payment_penalty_type'] === 'percentage' ? $feeRules['late_payment_penalty_percentage'] : $feeRules['late_payment_penalty_amount'] }}"
                               step="0.01" min="0"
                               class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="{{ $feeRules['late_payment_penalty_type'] === 'percentage' ? '5.0' : '50.00' }}">
                    </div>

                    <div class="p-3 bg-slate-800/50 rounded-lg">
                        <label class="text-sm font-medium text-slate-300">Grace Period (Days)</label>
                        <input type="number" name="grace_period_days" value="{{ $feeRules['grace_period_days'] }}" 
                               min="0" max="365"
                               class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="7">
                    </div>

                    <div class="p-3 bg-slate-800/50 rounded-lg">
                        <label class="text-sm font-medium text-slate-300">Minimum Payment Percentage</label>
                        <input type="number" name="minimum_payment_percentage" value="{{ $feeRules['minimum_payment_percentage'] }}" 
                               min="0" max="100"
                               class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="100">
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Update Fee Rules
                </button>
            </div>
        </form>
    </div>

    <!-- Attendance Rules -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-lg font-semibold text-slate-50 mb-4">Attendance Rules</h2>
        <form action="{{ route('admin.smart-rules.attendance.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-3 bg-slate-800/50 rounded-lg">
                    <label class="text-sm font-medium text-slate-300">Maximum Absence Days</label>
                    <input type="number" name="max_absence_days" value="{{ $attendanceRules['max_absence_days'] }}" 
                           min="0" max="365"
                           class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="10">
                </div>

                <div class="p-3 bg-slate-800/50 rounded-lg">
                    <label class="text-sm font-medium text-slate-300">Warning Threshold</label>
                    <input type="number" name="absence_warning_threshold" value="{{ $attendanceRules['absence_warning_threshold'] }}" 
                           min="0" max="365"
                           class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="5">
                </div>

                <div class="p-3 bg-slate-800/50 rounded-lg">
                    <label class="text-sm font-medium text-slate-300">Auto-Fail Threshold</label>
                    <input type="number" name="auto_fail_threshold" value="{{ $attendanceRules['auto_fail_threshold'] }}" 
                           min="0" max="365"
                           class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="20">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Update Attendance Rules
                </button>
            </div>
        </form>
    </div>

    <!-- Academic Rules -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-lg font-semibold text-slate-50 mb-4">Academic Rules</h2>
        <form action="{{ route('admin.smart-rules.academic.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div class="p-3 bg-slate-800/50 rounded-lg">
                        <label class="text-sm font-medium text-slate-300">Minimum Passing Grade</label>
                        <input type="number" name="min_passing_grade" value="{{ $academicRules['min_passing_grade'] }}" 
                               min="0" max="100"
                               class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="50">
                    </div>

                    <div class="p-3 bg-slate-800/50 rounded-lg">
                        <label class="text-sm font-medium text-slate-300">Maximum Failed Subjects</label>
                        <input type="number" name="max_failed_subjects" value="{{ $academicRules['max_failed_subjects'] }}" 
                               min="0" max="20"
                               class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="2">
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="p-3 bg-slate-800/50 rounded-lg">
                        <label class="text-sm font-medium text-slate-300">Promotion Criteria</label>
                        <select name="promotion_criteria" class="mt-2 w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="grades" {{ $academicRules['promotion_criteria'] === 'grades' ? 'selected' : '' }}>Grades Only</option>
                            <option value="attendance" {{ $academicRules['promotion_criteria'] === 'attendance' ? 'selected' : '' }}>Attendance Only</option>
                            <option value="both" {{ $academicRules['promotion_criteria'] === 'both' ? 'selected' : '' }}>Both Grades & Attendance</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <div>
                            <label class="text-sm font-medium text-slate-300">Require Attendance for Promotion</label>
                            <p class="text-xs text-slate-400">Students must meet attendance requirements for promotion</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="require_attendance_for_promotion" value="1" {{ $academicRules['require_attendance_for_promotion'] ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Update Academic Rules
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
