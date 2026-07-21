<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;

class SmartRuleController extends Controller
{
    public function index()
    {
        $school = auth()->user()->school;
        
        $feeRules = [
            'restrict_exam_access' => $this->getSetting($school, 'fees.restrict_exam_access', false),
            'restrict_result_access' => $this->getSetting($school, 'fees.restrict_result_access', false),
            'restrict_next_term_registration' => $this->getSetting($school, 'fees.restrict_next_term_registration', false),
            'late_payment_penalties' => $this->getSetting($school, 'fees.late_payment_penalties', false),
            'late_payment_penalty_type' => $this->getSetting($school, 'fees.late_payment_penalty_type', 'fixed'),
            'late_payment_penalty_amount' => $this->getSetting($school, 'fees.late_payment_penalty_amount', 0),
            'late_payment_penalty_percentage' => $this->getSetting($school, 'fees.late_payment_penalty_percentage', 0),
            'grace_period_days' => $this->getSetting($school, 'fees.grace_period_days', 0),
            'minimum_payment_percentage' => $this->getSetting($school, 'fees.minimum_payment_percentage', 100),
        ];

        $attendanceRules = [
            'max_absence_days' => $this->getSetting($school, 'attendance.max_absence_days', 10),
            'absence_warning_threshold' => $this->getSetting($school, 'attendance.absence_warning_threshold', 5),
            'auto_fail_threshold' => $this->getSetting($school, 'attendance.auto_fail_threshold', 20),
        ];

        $academicRules = [
            'min_passing_grade' => $this->getSetting($school, 'academic.min_passing_grade', 50),
            'max_failed_subjects' => $this->getSetting($school, 'academic.max_failed_subjects', 2),
            'promotion_criteria' => $this->getSetting($school, 'academic.promotion_criteria', 'both'),
            'require_attendance_for_promotion' => $this->getSetting($school, 'academic.require_attendance_for_promotion', true),
        ];

        return view('admin.smart-rules.index', compact('feeRules', 'attendanceRules', 'academicRules'));
    }

    public function updateFeeRules(Request $request)
    {
        $school = auth()->user()->school;
        
        $rules = [
            'restrict_exam_access' => 'boolean',
            'restrict_result_access' => 'boolean',
            'restrict_next_term_registration' => 'boolean',
            'late_payment_penalties' => 'boolean',
            'late_payment_penalty_type' => 'required|in:fixed,percentage',
            'late_payment_penalty_amount' => 'required|numeric|min:0',
            'late_payment_penalty_percentage' => 'required|numeric|min:0|max:100',
            'grace_period_days' => 'required|integer|min:0|max:365',
            'minimum_payment_percentage' => 'required|integer|min:0|max:100',
        ];

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            $this->setSetting($school, "fees.{$key}", $value);
        }

        return redirect()->route('admin.smart-rules.index')
            ->with('success', 'Fee rules updated successfully.');
    }

    public function updateAttendanceRules(Request $request)
    {
        $school = auth()->user()->school;
        
        $rules = [
            'max_absence_days' => 'required|integer|min:0|max:365',
            'absence_warning_threshold' => 'required|integer|min:0|max:365',
            'auto_fail_threshold' => 'required|integer|min:0|max:365',
        ];

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            $this->setSetting($school, "attendance.{$key}", $value);
        }

        return redirect()->route('admin.smart-rules.index')
            ->with('success', 'Attendance rules updated successfully.');
    }

    public function updateAcademicRules(Request $request)
    {
        $school = auth()->user()->school;
        
        $rules = [
            'min_passing_grade' => 'required|integer|min:0|max:100',
            'max_failed_subjects' => 'required|integer|min:0|max:20',
            'promotion_criteria' => 'required|in:grades,attendance,both',
            'require_attendance_for_promotion' => 'boolean',
        ];

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            $this->setSetting($school, "academic.{$key}", $value);
        }

        return redirect()->route('admin.smart-rules.index')
            ->with('success', 'Academic rules updated successfully.');
    }

    protected function getSetting($school, $key, $default = null)
    {
        $setting = $school->settings()->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    protected function setSetting($school, $key, $value)
    {
        $school->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
