<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;

class AuditMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only audit authenticated requests
        if (!auth()->check()) {
            return $response;
        }

        $user = auth()->user();
        $route = $request->route();
        
        // Skip certain routes that don't need auditing
        $skipRoutes = [
            'dashboard',
            'profile',
            'notifications.index',
            'search',
        ];

        if ($route && !in_array($route->getName(), $skipRoutes)) {
            $action = $this->determineAction($request);
            $module = $this->determineModule($route);
            
            AuditService::log($action, null, null, $module);
        }

        return $response;
    }

    protected function determineAction(Request $request)
    {
        $method = $request->method();
        $routeName = $request->route()?->getName();
        
        if ($method === 'GET' && str_contains($routeName, 'index')) {
            return 'view';
        }
        
        if ($method === 'GET' && str_contains($routeName, 'show')) {
            return 'view';
        }
        
        if ($method === 'POST') {
            return 'create';
        }
        
        if ($method === 'PUT' || $method === 'PATCH') {
            return 'update';
        }
        
        if ($method === 'DELETE') {
            return 'delete';
        }
        
        return 'access';
    }

    protected function determineModule($route)
    {
        if (!$route) return 'system';
        
        $routeName = $route->getName();
        
        $moduleMap = [
            'students.' => 'students',
            'teachers.' => 'teachers',
            'parents.' => 'parents',
            'librarian.' => 'library',
            'accounting.' => 'accounting',
            'enrollment.' => 'enrollment',
            'academics.' => 'academics',
            'exams.' => 'exams',
            'reports.' => 'reports',
            'admin.' => 'administration',
        ];

        foreach ($moduleMap as $prefix => $module) {
            if (str_starts_with($routeName, $prefix)) {
                return $module;
            }
        }

        return 'general';
    }
}
