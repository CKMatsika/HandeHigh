<?php

namespace App\Services\Finance;

class CurrencyWordsService
{
    private static array $units = [
        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
        14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
        18 => 'eighteen', 19 => 'nineteen'
    ];

    private static array $tens = [
        20 => 'twenty', 30 => 'thirty', 40 => 'forty', 50 => 'fifty',
        60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety'
    ];

    /**
     * Convert numeric amount to words (e.g. 135.00 -> "One hundred and thirty-five dollars only")
     */
    public static function toWords(float|int|string $amount, string $currency = 'dollars'): string
    {
        $amount = (float) $amount;
        $dollars = (int) floor($amount);
        $cents = (int) round(($amount - $dollars) * 100);

        $dollarWords = self::convertNumber($dollars);
        if ($dollarWords === '') {
            $dollarWords = 'zero';
        }

        $formatted = ucfirst(trim($dollarWords)) . ' ' . $currency;

        if ($cents > 0) {
            $centWords = self::convertNumber($cents);
            $formatted .= ' and ' . trim($centWords) . ' cents';
        }

        return $formatted . ' only';
    }

    private static function convertNumber(int $num): string
    {
        if ($num < 20) {
            return self::$units[$num] ?? '';
        }

        if ($num < 100) {
            $ten = (int) (floor($num / 10) * 10);
            $unit = $num % 10;
            return self::$tens[$ten] . ($unit > 0 ? '-' . self::$units[$unit] : '');
        }

        if ($num < 1000) {
            $hundred = (int) floor($num / 100);
            $rest = $num % 100;
            $res = self::$units[$hundred] . ' hundred';
            if ($rest > 0) {
                $res .= ' and ' . self::convertNumber($rest);
            }
            return $res;
        }

        if ($num < 1000000) {
            $thousands = (int) floor($num / 1000);
            $rest = $num % 1000;
            $res = self::convertNumber($thousands) . ' thousand';
            if ($rest > 0) {
                $res .= ($rest < 100 ? ' and ' : ' ') . self::convertNumber($rest);
            }
            return $res;
        }

        $millions = (int) floor($num / 1000000);
        $rest = $num % 1000000;
        $res = self::convertNumber($millions) . ' million';
        if ($rest > 0) {
            $res .= ' ' . self::convertNumber($rest);
        }
        return $res;
    }
}
