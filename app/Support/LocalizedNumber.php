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
        return self::digits(Currency::format((float) ($value ?? 0), 2, true), $locale);
    }
}
