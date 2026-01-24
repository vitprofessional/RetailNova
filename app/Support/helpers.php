<?php

if (!function_exists('numberToWords')) {
    /**
     * Convert a number to words (English)
     *
     * @param float|int $number
     * @return string
     */
    function numberToWords($number)
    {
        $number = (int) $number; // Convert to integer for word conversion
        
        if ($number == 0) {
            return 'zero';
        }

        $ones = [
            '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
            'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
            'seventeen', 'eighteen', 'nineteen'
        ];

        $tens = [
            '', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'
        ];

        $scales = ['', 'thousand', 'million', 'billion', 'trillion'];

        $words = [];
        $scaleIndex = 0;

        if ($number < 0) {
            $words[] = 'minus';
            $number = abs($number);
        }

        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk > 0) {
                $chunkWords = [];

                // Hundreds
                $hundreds = (int)($chunk / 100);
                if ($hundreds > 0) {
                    $chunkWords[] = $ones[$hundreds] . ' hundred';
                }

                // Tens and ones
                $remainder = $chunk % 100;
                if ($remainder >= 20) {
                    $tensDigit = (int)($remainder / 10);
                    $onesDigit = $remainder % 10;
                    $chunkWords[] = $tens[$tensDigit];
                    if ($onesDigit > 0) {
                        $chunkWords[] = $ones[$onesDigit];
                    }
                } elseif ($remainder > 0) {
                    $chunkWords[] = $ones[$remainder];
                }

                // Add scale (thousand, million, etc.)
                if ($scaleIndex > 0 && $scales[$scaleIndex]) {
                    $chunkWords[] = $scales[$scaleIndex];
                }

                $words[] = implode(' ', array_reverse($chunkWords));
            }

            $number = (int)($number / 1000);
            $scaleIndex++;
        }

        return implode(' ', array_reverse($words));
    }
}
