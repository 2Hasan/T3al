<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Normalises Bahraini mobile numbers to a single stored format (9733xxxxxxx),
 * so the same customer is never registered twice with different spellings.
 */
class BahrainPhone
{
    public static function normalize(string $input): string
    {
        $digits = preg_replace('/\D+/', '', strtr($input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));

        $digits = preg_replace('/^(00)?973/', '', $digits);

        if (! preg_match('/^[3-9]\d{7}$/', $digits)) {
            throw new InvalidArgumentException(__('app.invalid_phone'));
        }

        return '973'.$digits;
    }

    /**
     * Formats a stored number for display, e.g. 97336123456 -> +973 3612 3456.
     */
    public static function format(string $stored): string
    {
        if (! preg_match('/^973(\d{4})(\d{4})$/', $stored, $parts)) {
            return $stored;
        }

        return "+973 {$parts[1]} {$parts[2]}";
    }

    public static function isValid(string $input): bool
    {
        try {
            static::normalize($input);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
