<?php

namespace App\Support;

class Phone
{
    /**
     * Eastern-Arabic digits (Persian ۰-۹, Arabic ٠-٩) mapped to Latin.
     * Guests type phones in whatever script their keyboard offers; the
     * canonical shape is always the Latin local form.
     */
    private const EASTERN_DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * Latin digits, separators dropped, and the international +98 / 98
     * prefix collapsed into the local 0 prefix — the same shape the
     * booking flow stores and compares.
     */
    public static function normalize(string $phone): string
    {
        $latin = strtr($phone, self::EASTERN_DIGITS);

        $digits = (string) preg_replace('/\D+/', '', $latin);

        if (strlen($digits) === 12 && str_starts_with($digits, '98')) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    /**
     * An Iranian mobile number in canonical local form: 09XXXXXXXXX.
     */
    public static function isMobile(string $phone): bool
    {
        return (bool) preg_match('/^09\d{9}$/', $phone);
    }

    /**
     * LIKE patterns that reach a stored phone no matter which digit script
     * the guest typed it in — the booking flow stores what was typed, so a
     * phone-first lookup has to speak all three scripts. The short tail
     * matters as much as the script: guests also type separators ("+98 912
     * 123 4567") and LIKE cannot ignore punctuation, so a wider tail sweeps
     * the candidates and the exact normalized comparison decides.
     */
    public static function likePatterns(string $phone): array
    {
        $patterns = [];

        foreach ([substr($phone, -10), substr($phone, -4)] as $tail) {
            $patterns[] = '%'.$tail.'%';
            $patterns[] = '%'.strtr($tail, array_flip(array_slice(self::EASTERN_DIGITS, 0, 10, true))).'%';
            $patterns[] = '%'.strtr($tail, array_flip(array_slice(self::EASTERN_DIGITS, 10, 10, true))).'%';
        }

        return array_unique($patterns);
    }
}
