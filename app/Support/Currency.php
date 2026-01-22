<?php

namespace App\Support;

use App\Models\BusinessSetup;

class Currency
{
    protected static ?BusinessSetup $settings = null;

    protected static function load(): void
    {
        if (self::$settings === null) {
            self::$settings = BusinessSetup::orderBy('id','desc')->first();
        }
    }

    public static function symbol(): string
    {
        self::load();
        $settings = self::$settings;
        return ($settings && $settings->currencySymbol) ? $settings->currencySymbol : '৳';
    }

    public static function position(): string
    {
        self::load();
        $settings = self::$settings;
        return ($settings && $settings->currencyPosition) ? $settings->currencyPosition : 'left';
    }

    /**
     * Format an amount with the configured currency symbol/position.
     *
     * @param float|int|string $amount       Numeric amount to format.
     * @param int              $decimals     Number of decimal places to show (default 2).
     * @param bool             $trimZeros    If true, trim trailing zeros and decimal point.
     */
    public static function format($amount, int $decimals = 2, bool $trimZeros = false): string
    {
        self::load();

        $settings = self::$settings;
        $negParen = (bool)($settings->currencyNegParentheses ?? true);
        $num      = is_numeric($amount) ? (float)$amount : 0.0;
        $abs      = number_format(abs($num), $decimals, '.', ',');
        $abs      = ($trimZeros && $decimals > 0) ? rtrim(rtrim($abs, '0'), '.') : $abs;

        $symbol   = self::symbol();
        $value    = self::position() === 'right' ? ($abs . $symbol) : ($symbol . $abs);

        if ($num < 0) {
            return $negParen ? '(' . $value . ')' : '-' . $value;
        }

        return $value;
    }
}
