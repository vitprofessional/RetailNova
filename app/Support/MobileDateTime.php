<?php

namespace App\Support;

use Carbon\Carbon;

/** Consistent date and time labels for mobile transaction cards. */
class MobileDateTime
{
    public static function date(mixed $value): string
    {
        if (!$value) return '';
        try { return LocalizedNumber::digits(Carbon::parse($value)->format('d/m/Y')); }
        catch (\Throwable) { return ''; }
    }

    public static function time(mixed $value): string
    {
        if (!$value) return '';
        try {
            $time = Carbon::parse($value);
            if (str_starts_with(app()->getLocale(), 'bn')) {
                $hour = (int) $time->format('G');
                $period = $hour < 12 ? 'সকাল' : ($hour < 18 ? 'বিকাল' : 'রাত');
                return $period.' '.LocalizedNumber::digits($time->format('g:i'));
            }
            return $time->format('g:i A');
        } catch (\Throwable) { return ''; }
    }
}
