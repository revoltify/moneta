<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public const CURRENCIES = [
        'BDT' => '৳',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'AED' => 'AED ',
        'SGD' => 'S$',
        'MYR' => 'RM ',
    ];

    public static function toMinorUnits(string|int|float $amount): int
    {
        if (is_int($amount)) {
            return $amount * 100;
        }

        $normalized = str_replace([',', ' ', '৳'], '', (string) $amount);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException("Invalid money amount: {$amount}");
        }

        return (int) round((float) $normalized * 100);
    }

    public static function format(int $minorUnits, string $currency = 'BDT'): string
    {
        $symbol = self::CURRENCIES[$currency] ?? $currency.' ';
        $sign = $minorUnits < 0 ? '-' : '';

        return $sign.$symbol.number_format(abs($minorUnits) / 100, 2);
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::CURRENCIES);
    }
}
