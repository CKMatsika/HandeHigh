<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log($action, $model = null, $description = null, $module = null)
    {
        $user = Auth::user();
        $school = $user?->school ?? session('current_school');

        if (!$school) {
            return null;
        }

        $logData = [
            'school_id' => $school->id,
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module ?? static::detectModule($model),
            'description' => $description ?? static::generateDescription($action, $model, $user),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ];

        if ($model) {
            $logData['model_type'] = get_class($model);
            $logData['model_id'] = $model->id;
            
            if ($model->wasChanged()) {
                $logData['old_values'] = $model->getOriginal();
                $logData['new_values'] = $model->getAttributes();
            }
        }

        return AuditLog::create($logData);
    }

    public static function logLogin($user)
    {
        $school = $user->school;
        
        return AuditLog::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'action' => 'login',
            'module' => 'auth',
            'description' => "User {$user->name} logged in",
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logLogout($user)
    {
        $school = $user->school;
        
        return AuditLog::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'action' => 'logout',
            'module' => 'auth',
            'description' => "User {$user->name} logged out",
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logFailedLogin($email, $school = null)
    {
        return AuditLog::create([
            'school_id' => $school?->id,
            'user_id' => null,
            'action' => 'failed_login',
            'module' => 'auth',
            'description' => "Failed login attempt for email: {$email}",
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    protected static function detectModule($model)
    {
        if (!$model) return 'system';

        $modelClass = get_class($model);
        
        $moduleMap = [
            'App\\Models\\Student' => 'students',
            'App\\Models\\Teacher' => 'teachers',
            'App\\Models\\Parent' => 'parents',
            'App\\Models\\Invoice' => 'accounting',
            'App\\Models\\Payment' => 'accounting',
            'App\\Models\\Book' => 'library',
            'App\\Models\\BorrowRecord' => 'library',
            'App\\Models\\Assessment' => 'academics',
            'App\\Models\\Result' => 'academics',
            'App\\Models\\Enrollment' => 'enrollment',
            'App\\Models\\User' => 'users',
        ];

        return $moduleMap[$modelClass] ?? 'general';
    }

    protected static function generateDescription($action, $model, $user)
    {
        $userName = $user?->name ?? 'System';
        $modelName = $model ? class_basename($model) : '';
        
        if ($model && $model->id) {
            return "{$userName} {$action} {$modelName} #{$model->id}";
        }
        
        return "{$userName} performed {$action}";
    }
}
