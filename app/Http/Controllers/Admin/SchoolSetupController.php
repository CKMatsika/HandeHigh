<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolSetupController extends Controller
{
    public function index()
    {
        $school = auth()->user()->school;
        $settings = $school->settings()->pluck('value', 'key')->toArray();
        $recentLogs = AuditLog::where('school_id', $school->id)
            ->with('user')
            ->latest()
            ->take(50)
            ->get();

        return view('admin.school-setup.index', compact('school', 'settings', 'recentLogs'));
    }

    public function edit()
    {
        $school = auth()->user()->school;
        $settings = $school->settings()->pluck('value', 'key')->toArray();

        return view('admin.school-setup.edit', compact('school', 'settings'));
    }

    public function update(Request $request)
    {
        $school = auth()->user()->school;
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'website' => 'nullable|url|max:255',
            'motto' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'established_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'school_type' => 'required|in:primary,secondary,tertiary,mixed',
            'academic_year_start' => 'required|date',
            'academic_year_end' => 'required|date|after:academic_year_start',
            'terms_per_year' => 'required|integer|min:1|max:4',
            'grading_scale' => 'required|array',
            'attendance_threshold' => 'required|integer|min:50|max:100',
            'max_class_size' => 'required|integer|min:10|max:100',
        ]);

        // Update school basic info
        $school->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'website' => $request->website,
            'motto' => $request->motto,
            'description' => $request->description,
            'established_year' => $request->established_year,
            'school_type' => $request->school_type,
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($school->logo) {
                Storage::disk('public')->delete($school->logo);
            }
            $logoPath = $request->file('logo')->store('school-logos', 'public');
            $school->update(['logo' => $logoPath]);
        }

        // Update school settings
        $settings = [
            'academic_year_start' => $request->academic_year_start,
            'academic_year_end' => $request->academic_year_end,
            'terms_per_year' => $request->terms_per_year,
            'grading_scale' => json_encode($request->grading_scale),
            'attendance_threshold' => $request->attendance_threshold,
            'max_class_size' => $request->max_class_size,
            'timezone' => $request->timezone ?? 'UTC',
            'currency' => $request->currency ?? 'USD',
            'language' => $request->language ?? 'en',
            'date_format' => $request->date_format ?? 'Y-m-d',
            'time_format' => $request->time_format ?? '24h',
        ];

        foreach ($settings as $key => $value) {
            $school->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Log the update
        AuditService::log($school->id, auth()->id(), 'update', 'school', $school->id, 
            'School setup updated', request()->ip(), request()->userAgent(), 'school-setup');

        return redirect()->route('admin.school-setup.index')
            ->with('success', 'School setup updated successfully.');
    }

    public function settings()
    {
        $school = auth()->user()->school;
        $settings = $school->settings()->pluck('value', 'key')->toArray();

        // Group settings by category
        $groupedSettings = [
            'academic' => [
                'academic_year_start' => $settings['academic_year_start'] ?? null,
                'academic_year_end' => $settings['academic_year_end'] ?? null,
                'terms_per_year' => $settings['terms_per_year'] ?? 3,
                'grading_scale' => json_decode($settings['grading_scale'] ?? '[]', true),
                'attendance_threshold' => $settings['attendance_threshold'] ?? 75,
                'max_class_size' => $settings['max_class_size'] ?? 30,
            ],
            'system' => [
                'timezone' => $settings['timezone'] ?? 'UTC',
                'currency' => $settings['currency'] ?? 'USD',
                'language' => $settings['language'] ?? 'en',
                'date_format' => $settings['date_format'] ?? 'Y-m-d',
                'time_format' => $settings['time_format'] ?? '24h',
            ],
            'notifications' => [
                'email_notifications' => $settings['email_notifications'] ?? true,
                'sms_notifications' => $settings['sms_notifications'] ?? false,
                'parent_notifications' => $settings['parent_notifications'] ?? true,
                'teacher_notifications' => $settings['teacher_notifications'] ?? true,
            ],
            'security' => [
                'password_expiry_days' => $settings['password_expiry_days'] ?? 90,
                'session_timeout' => $settings['session_timeout'] ?? 120,
                'failed_login_attempts' => $settings['failed_login_attempts'] ?? 5,
                'require_2fa' => $settings['require_2fa'] ?? false,
            ],
        ];

        return view('admin.school-setup.settings', compact('school', 'groupedSettings'));
    }

    public function updateSettings(Request $request)
    {
        $school = auth()->user()->school;
        
        $request->validate([
            'academic_year_start' => 'required|date',
            'academic_year_end' => 'required|date|after:academic_year_start',
            'terms_per_year' => 'required|integer|min:1|max:4',
            'attendance_threshold' => 'required|integer|min:50|max:100',
            'max_class_size' => 'required|integer|min:10|max:100',
            'timezone' => 'required|string',
            'currency' => 'required|string|max:3',
            'language' => 'required|string|max:5',
            'date_format' => 'required|string',
            'time_format' => 'required|in:12h,24h',
        ]);

        // Update settings
        $settings = [
            'academic_year_start' => $request->academic_year_start,
            'academic_year_end' => $request->academic_year_end,
            'terms_per_year' => $request->terms_per_year,
            'grading_scale' => json_encode($request->grading_scale ?? []),
            'attendance_threshold' => $request->attendance_threshold,
            'max_class_size' => $request->max_class_size,
            'timezone' => $request->timezone,
            'currency' => $request->currency,
            'language' => $request->language,
            'date_format' => $request->date_format,
            'time_format' => $request->time_format,
            'email_notifications' => $request->boolean('email_notifications'),
            'sms_notifications' => $request->boolean('sms_notifications'),
            'parent_notifications' => $request->boolean('parent_notifications'),
            'teacher_notifications' => $request->boolean('teacher_notifications'),
            'password_expiry_days' => $request->password_expiry_days ?? 90,
            'session_timeout' => $request->session_timeout ?? 120,
            'failed_login_attempts' => $request->failed_login_attempts ?? 5,
            'require_2fa' => $request->boolean('require_2fa'),
        ];

        foreach ($settings as $key => $value) {
            $school->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Log the update
        AuditService::log($school->id, auth()->id(), 'update', 'school_settings', $school->id, 
            'School settings updated', request()->ip(), request()->userAgent(), 'school-setup');

        return redirect()->route('admin.school-setup.settings')
            ->with('success', 'School settings updated successfully.');
    }

    public function logs(Request $request)
    {
        $school = auth()->user()->school;
        
        $query = AuditLog::where('school_id', $school->id)
            ->with('user');

        // Filters
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(50);
        $users = $school->users()->pluck('name', 'id')->toArray();
        
        $actions = ['create', 'update', 'delete', 'login', 'logout', 'view', 'export'];
        $modules = ['students', 'teachers', 'classes', 'subjects', 'fees', 'exams', 'library', 'school-setup'];

        return view('admin.school-setup.logs', compact('logs', 'users', 'actions', 'modules'));
    }
}
