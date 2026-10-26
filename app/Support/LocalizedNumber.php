<?php

namespace App\Support;

/** Shows numbers with Bengali digits (০-৯) while the interface language is Bangla. */
class LocalizedNumber
{
    private const BENGALI = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    public static function digits(string|int|float|null $value, ?string $locale = null): string
    {
        $text = (string) ($value ?? '');
        $locale ??= app()->getLocale();

        return str_starts_with((string) $locale, 'bn') ? strtr($text, array_combine(range(0, 9), self::BENGALI)) : $text;
    }

    /** Formats a whole number with thousands separators, then localizes the digits. */
    public static function count(int|float|null $value, ?string $locale = null): string
    {
        return self::digits(number_format((float) ($value ?? 0)), $locale);
    }

    /** Money with the business currency (see Currency::format), digits localized. */
    public static function money(int|float|string|null $value, ?string $locale = null): string
    {
        $number = is_numeric($value) ? (float) $value : 0.0;
        $decimals = abs($number - round($number)) < 0.000001 ? 0 : 2;
        $formatted = self::groupIndian(abs($number), $decimals);
        $symbol = Currency::symbol();
        $money = Currency::position() === 'right' ? $formatted.$symbol : $symbol.$formatted;
        if ($number < 0) {
            $money = '−'.$money;
        }

        return self::digits($money, $locale);
    }

    /** Quantity/count formatted with Indian lakh grouping (1,25,880). */
    public static function grouped(int|float|string|null $value, ?string $locale = null, int $decimals = 0): string
    {
        $number = is_numeric($value) ? (float) $value : 0.0;
        return self::digits(self::groupIndian($number, $decimals), $locale);
    }

    private static function groupIndian(float $number, int $decimals): string
    {
        $fixed = number_format($number, $decimals, '.', '');
        [$integer, $fraction] = array_pad(explode('.', $fixed, 2), 2, '');
        if (strlen($integer) > 3) {
            $last = substr($integer, -3);
            $rest = substr($integer, 0, -3);
            $integer = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$last;
        }

        $fraction = rtrim($fraction, '0');
        return $integer.($fraction !== '' ? '.'.$fraction : '');
    }
}
