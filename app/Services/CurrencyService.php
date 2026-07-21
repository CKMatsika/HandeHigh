<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    const CURRENCIES = [
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'code' => 'USD',
            'decimal_places' => 2,
        ],
        'ZIG' => [
            'name' => 'Zimbabwe Gold',
            'symbol' => 'ZIG',
            'code' => 'ZIG',
            'decimal_places' => 2,
        ],
    ];

    /**
     * Get all available currencies
     */
    public static function getCurrencies(): array
    {
        return self::CURRENCIES;
    }

    /**
     * Get currency information by code
     */
    public static function getCurrency(string $code): ?array
    {
        return self::CURRENCIES[$code] ?? null;
    }

    /**
     * Format amount with currency symbol
     */
    public static function format(float $amount, string $currencyCode = 'USD'): string
    {
        $currency = self::getCurrency($currencyCode);
        if (!$currency) {
            return number_format($amount, 2);
        }

        $formattedAmount = number_format(
            $amount,
            $currency['decimal_places']
        );

        return $currency['symbol'] . $formattedAmount;
    }

    /**
     * Get exchange rate between currencies
     * For now, we'll use a simple 1:1 conversion, but this can be extended
     * to fetch real-time rates from an API
     */
    public static function getExchangeRate(string $from, string $to): float
    {
        // Cache exchange rates for 1 hour
        $cacheKey = "exchange_rate_{$from}_{$to}";
        
        return Cache::remember($cacheKey, 3600, function () use ($from, $to) {
            // For demonstration, using static rates
            // In production, you'd fetch from an API like Central Bank of Zimbabwe
            $rates = [
                'USD_ZIG' => 13.5, // 1 USD = 13.5 ZIG (example rate)
                'ZIG_USD' => 0.074, // 1 ZIG = 0.074 USD
            ];
            
            return $rates["{$from}_{$to}"] ?? 1.0;
        });
    }

    /**
     * Convert amount from one currency to another
     */
    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = self::getExchangeRate($from, $to);
        return $amount * $rate;
    }

    /**
     * Get current school's currency
     */
    public static function getSchoolCurrency(): string
    {
        if (auth()->check() && auth()->user()->school) {
            return auth()->user()->school->currency ?? 'USD';
        }
        
        return 'USD'; // Default fallback
    }

    /**
     * Format amount in school's currency
     */
    public static function formatSchoolCurrency(float $amount): string
    {
        $currencyCode = self::getSchoolCurrency();
        return self::format($amount, $currencyCode);
    }

    /**
     * Convert amount to school's currency and format
     */
    public static function convertAndFormatToSchoolCurrency(float $amount, string $fromCurrency = 'USD'): string
    {
        $schoolCurrency = self::getSchoolCurrency();
        
        if ($fromCurrency !== $schoolCurrency) {
            $amount = self::convert($amount, $fromCurrency, $schoolCurrency);
        }
        
        return self::format($amount, $schoolCurrency);
    }
}
