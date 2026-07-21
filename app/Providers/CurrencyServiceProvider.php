<?php

namespace App\Providers;

use App\Services\CurrencyService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Create @currency directive
        Blade::directive('currency', function ($expression) {
            return "<?php echo App\Services\CurrencyService::formatSchoolCurrency($expression); ?>";
        });

        // Create @currencySymbol directive
        Blade::directive('currencySymbol', function () {
            return "<?php echo App\Services\CurrencyService::getCurrency(App\Services\CurrencyService::getSchoolCurrency())['symbol']; ?>";
        });

        // Create @convertCurrency directive
        Blade::directive('convertCurrency', function ($expression) {
            // @convertCurrency($amount, $fromCurrency)
            return "<?php echo App\Services\CurrencyService::convertAndFormatToSchoolCurrency($expression); ?>";
        });
    }
}
