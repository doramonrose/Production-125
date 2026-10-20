<?php

namespace App\Support;

final class ThaiDigits
{
    /** @var array<string, string> */
    private const DIGIT_MAP = [
        '0' => '๐',
        '1' => '๑',
        '2' => '๒',
        '3' => '๓',
        '4' => '๔',
        '5' => '๕',
        '6' => '๖',
        '7' => '๗',
        '8' => '๘',
        '9' => '๙',
    ];

    public static function inString(string $text): string
    {
        return self::toArabic($text);
    }

    /** Convert Thai digits (๐-๙) to Arabic digits (0-9). */
    public static function toArabic(string $text): string
    {
        if ($text === '') {
            return '';
        }

        return strtr($text, array_flip(self::DIGIT_MAP));
    }

    public static function formatAmount(float $amount): string
    {
        if (! is_finite($amount)) {
            return '-';
        }

        return number_format($amount, 2, '.', ',');
    }
}
