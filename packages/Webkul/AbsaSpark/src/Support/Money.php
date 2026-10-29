<?php

namespace Webkul\AbsaSpark\Support;

use InvalidArgumentException;

class Money
{
    /**
     * Convert a decimal amount, such as an order total, into whole cents.
     */
    public static function toCents(string|int|float $amount): int
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('The amount is not a number.');
        }

        $rounded = round((float) $amount, 2);

        if ($rounded <= 0) {
            throw new InvalidArgumentException('The amount must be greater than zero.');
        }

        return (int) round($rounded * 100);
    }

    /**
     * Convert whole cents back into the two-decimal amount Fiserv expects.
     */
    public static function toDecimal(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * Read a gateway amount such as "199.00" into whole cents without float drift, or null when it is malformed.
     */
    public static function parseCents(mixed $amount): ?int
    {
        if (! is_string($amount) && ! is_int($amount) && ! is_float($amount)) {
            return null;
        }

        if (preg_match('/^([0-9]+)(?:\.([0-9]{1,2}))?$/D', (string) $amount, $parts) !== 1) {
            return null;
        }

        $digits = ltrim($parts[1].str_pad($parts[2] ?? '', 2, '0'), '0');

        if (strlen($digits) > strlen((string) PHP_INT_MAX)) {
            return null;
        }

        return (int) ($digits === '' ? '0' : $digits);
    }
}
