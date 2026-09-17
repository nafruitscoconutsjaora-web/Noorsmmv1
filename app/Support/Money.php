<?php

declare(strict_types=1);

namespace App\Support;

class Money
{
    private const SCALE = 8;

    public static function add(string|float|int $a, string|float|int $b): string
    {
        return bcadd(self::toStr($a), self::toStr($b), self::SCALE);
    }

    public static function sub(string|float|int $a, string|float|int $b): string
    {
        return bcsub(self::toStr($a), self::toStr($b), self::SCALE);
    }

    public static function mul(string|float|int $a, string|float|int $b): string
    {
        return bcmul(self::toStr($a), self::toStr($b), self::SCALE);
    }

    public static function div(string|float|int $a, string|float|int $b): string
    {
        $divisor = self::toStr($b);
        if (bccomp($divisor, '0', self::SCALE) === 0) {
            throw new \InvalidArgumentException("Division by zero");
        }
        return bcdiv(self::toStr($a), $divisor, self::SCALE);
    }

    public static function cmp(string|float|int $a, string|float|int $b): int
    {
        return bccomp(self::toStr($a), self::toStr($b), self::SCALE);
    }

    public static function gte(string|float|int $a, string|float|int $b): bool
    {
        return self::cmp($a, $b) >= 0;
    }

    public static function lte(string|float|int $a, string|float|int $b): bool
    {
        return self::cmp($a, $b) <= 0;
    }

    public static function gt(string|float|int $a, string|float|int $b): bool
    {
        return self::cmp($a, $b) > 0;
    }

    public static function lt(string|float|int $a, string|float|int $b): bool
    {
        return self::cmp($a, $b) < 0;
    }

    public static function eq(string|float|int $a, string|float|int $b): bool
    {
        return self::cmp($a, $b) === 0;
    }

    public static function format(string|float|int $amount, string $currency = 'INR', int $decimals = 2): string
    {
        $symbol = match (strtoupper($currency)) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            default => $currency . ' '
        };

        $val = (float)self::toStr($amount);
        return $symbol . number_format($val, $decimals, '.', ',');
    }

    private static function toStr(string|float|int $val): string
    {
        if (is_string($val)) {
            return trim($val);
        }
        return number_format((float)$val, self::SCALE, '.', '');
    }
}
