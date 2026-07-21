<?php

namespace App\Http\Middleware;

use App\Services\CurrencyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CurrencyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Share currency information with all views
        View::share('currentCurrency', CurrencyService::getSchoolCurrency());
        View::share('currencyInfo', CurrencyService::getCurrency(CurrencyService::getSchoolCurrency()));
        View::share('availableCurrencies', CurrencyService::getCurrencies());

        return $next($request);
    }
}
