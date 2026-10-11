<?php

namespace App\Support;

class AmountInWords
{
    private const SMALL = ['zero','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen'];
    private const TENS = ['', '', 'twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'];

    private static function english(int $number): string
    {
        if ($number < 20) { return self::SMALL[$number]; }
        if ($number < 100) { return self::TENS[intdiv($number,10)].($number % 10 ? ' '.self::SMALL[$number % 10] : ''); }
        foreach ([1000000000000=>'trillion',1000000000=>'billion',1000000=>'million',1000=>'thousand',100=>'hundred'] as $scale=>$label) {
            if($number >= $scale) { return self::english(intdiv($number,$scale)).' '.$label.($number % $scale ? ' '.self::english($number % $scale) : ''); }
        }
        return '';
    }

    public static function money($amount, ?string $locale = null): string
    {
        if (($locale ?? app()->getLocale()) === 'bn') { return BanglaAmountWords::money($amount); }
        [$whole,$fraction] = explode('.',number_format(abs((float)$amount),2,'.',''));
        return ucwords(((float)$amount < 0 ? 'minus ' : '').self::english((int)$whole).' taka'
            .((int)$fraction ? ' '.self::english((int)$fraction).' paisa' : '').' only');
    }
}
