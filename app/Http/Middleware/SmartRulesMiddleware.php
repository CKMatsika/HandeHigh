<?php

namespace App\Http\Middleware;

use App\Services\SmartRulesService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmartRulesMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user || !$user->school) {
            return $next($request);
        }

        // Initialize smart rules service for the school
        $smartRules = new SmartRulesService($user->school);

        // Apply restrictions based on route and user type
        $this->applyRestrictions($request, $smartRules, $user);

        return $next($request);
    }

    protected function applyRestrictions(Request $request, SmartRulesService $smartRules, $user)
    {
        $routeName = $request->route()?->getName();
        
        if (!$routeName) return;

        // Student-specific restrictions
        if ($user->hasRole('student')) {
            $student = $user->student;
            
            if (!$student) return;

            // Exam access restrictions
            if (str_contains($routeName, 'exam') || str_contains($routeName, 'assessment')) {
                $examAccess = $smartRules->canAccessExams($student);
                if (!$examAccess['allowed']) {
                    abort(403, $examAccess['reason']);
                }
            }

            // Result access restrictions
            if (str_contains($routeName, 'result') || str_contains($routeName, 'grade')) {
                $resultAccess = $smartRules->canViewResults($student);
                if (!$resultAccess['allowed']) {
                    abort(403, $resultAccess['reason']);
                }
            }

            // Registration restrictions
            if (str_contains($routeName, 'enroll') || str_contains($routeName, 'register')) {
                $registrationAccess = $smartRules->canRegisterNextTerm($student);
                if (!$registrationAccess['allowed']) {
                    abort(403, $registrationAccess['reason']);
                }
            }
        }

        // Parent-specific restrictions (check their children)
        if ($user->hasRole('parent')) {
            $parent = $user->parent;
            
            if (!$parent) return;

            foreach ($parent->students as $student) {
                // Parents can't view restricted results
                if (str_contains($routeName, 'result') || str_contains($routeName, 'grade')) {
                    $resultAccess = $smartRules->canViewResults($student);
                    if (!$resultAccess['allowed']) {
                        abort(403, "Results access restricted for {$student->full_name}: {$resultAccess['reason']}");
                    }
                }
            }
        }
    }
}
