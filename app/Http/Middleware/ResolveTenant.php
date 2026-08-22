<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->school_id) {
            abort(403);
        }

        $school = School::find($user->school_id);

        if (! $school) {
            abort(403);
        }

        app(TenantContext::class)->set($school);

        return $next($request);
    }
}
