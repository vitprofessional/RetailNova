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

    public static function format(int $amount): string
    {
        self::load();
        $symbol     = self::$settings->currencySymbol ?? '';
        $position   = self::$settings->currencyPosition ?? 'left';
        $negParen   = (bool)(self::$settings->currencyNegParentheses ?? true);
        $abs        = number_format(abs($amount));
        $value      = $position === 'left' ? ($symbol.$abs) : ($abs.$symbol);
        if ($amount < 0) {
            return $negParen ? '(' . $value . ')' : '-' . $value;
        }
        return $value;
    }
}
