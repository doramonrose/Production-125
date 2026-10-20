<?php

namespace App\Support;

/**
 * Insert invisible word-break opportunities at Thai word boundaries for mPDF.
 * Uses the same dictionary as mPDF (vendor/mpdf/mpdf/data/linebrdictT.dat).
 *
 * Instead of U+200B (which THSarabunNew lacks a glyph for), this inserts a
 * tiny HTML space that mPDF treats as a valid line-break opportunity.
 */
class ThaiLineBreak
{
    private const DICT_NODE_TYPE_SPLIT = 0x01;

    private const DICT_NODE_TYPE_LINEAR = 0x02;

    private const DICT_INTERMEDIATE_MATCH = 0x03;

    private const DICT_FINAL_MATCH = 0x04;

    private const BREAK_MARKER = '<span style="font-size:0.01pt"> </span>';

    private static ?string $thaiDictionary = null;

    public static function insertBreakOpportunities(string $text): string
    {
        if ($text === '' || ! preg_match('/[\x{0E00}-\x{0E7F}]/u', $text)) {
            return $text;
        }

        return preg_replace_callback(
            '/[\x{0E00}-\x{0E7F}]+/u',
            static fn (array $match): string => self::insertBreakOpportunitiesInThaiRun($match[0]),
            $text
        ) ?? $text;
    }

    private static function insertBreakOpportunitiesInThaiRun(string $text): string
    {
        $codepoints = self::utf8ToCodepoints($text);
        $length = count($codepoints);
        if ($length < 2) {
            return $text;
        }

        $dictionary = self::loadThaiDictionary();
        if ($dictionary === '') {
            return $text;
        }

        $wordEndIndexes = self::findWordEndIndexes($codepoints, $dictionary);

        $output = '';
        foreach ($codepoints as $index => $codepoint) {
            $output .= self::codepointToUtf8($codepoint);
            if (in_array($index, $wordEndIndexes, true)) {
                $output .= self::BREAK_MARKER;
            }
        }

        return $output;
    }

    /**
     * @param  list<int>  $codepoints
     * @return list<int>
     */
    private static function findWordEndIndexes(array $codepoints, string $dictionary): array
    {
        $wordEndIndexes = [];
        $length = count($codepoints);
        $rolloverMatches = [];
        $pointer = 0;

        while ($pointer < $length - 3) {
            $matches = $rolloverMatches !== [] ? $rolloverMatches : self::matchDictionaryWords($dictionary, $codepoints, $pointer);
            $rolloverMatches = [];

            if (count($matches) === 1) {
                $matchIndex = $matches[0];
                if (isset($codepoints[$matchIndex + 1]) && self::isThaiRepeatCharacter($codepoints[$matchIndex + 1])) {
                    $matchIndex++;
                }
                $wordEndIndexes[] = $matchIndex;
                $pointer = $matchIndex + 1;
            } elseif ($matches === []) {
                $pointer++;
                while ($pointer < $length && $codepoints[$pointer] <= 0xFF) {
                    $pointer++;
                }
            } else {
                $foundSecondMatch = false;
                for ($matchOffset = count($matches) - 1; $matchOffset >= 0; $matchOffset--) {
                    $firstMatch = $matches[$matchOffset];
                    $secondMatches = self::matchDictionaryWords($dictionary, $codepoints, $firstMatch + 1);
                    if ($secondMatches !== []) {
                        $wordEndIndexes[] = $firstMatch;
                        $pointer = $firstMatch + 1;
                        $rolloverMatches = $secondMatches;
                        $foundSecondMatch = true;
                        break;
                    }
                }
                if (! $foundSecondMatch) {
                    $lastMatch = $matches[count($matches) - 1];
                    $wordEndIndexes[] = $lastMatch;
                    $pointer = $lastMatch + 1;
                    while ($pointer < $length && $codepoints[$pointer] <= 0xFF) {
                        $pointer++;
                    }
                }
            }
        }

        return array_values(array_unique($wordEndIndexes));
    }

    /**
     * @param  list<int>  $codepoints
     * @return list<int>
     */
    private static function matchDictionaryWords(string $dictionary, array $codepoints, int $pointer): array
    {
        $dictionaryPointer = 0;
        $matches = [];
        $length = count($codepoints);

        while (true) {
            $nodeType = ord($dictionary[$dictionaryPointer]);
            $characterByte = ($codepoints[$pointer] ?? 0) & 0xFF;

            if ($nodeType === self::DICT_INTERMEDIATE_MATCH) {
                if (isset($codepoints[$pointer]) && ! self::isThaiMark($codepoints[$pointer])) {
                    $matches[] = $pointer - 1;
                }
                $dictionaryPointer++;
            } elseif ($nodeType === self::DICT_FINAL_MATCH) {
                if (isset($codepoints[$pointer]) && ! self::isThaiMark($codepoints[$pointer])) {
                    $matches[] = $pointer - 1;
                }

                return $matches;
            } elseif ($nodeType === self::DICT_NODE_TYPE_LINEAR) {
                $dictionaryPointer++;
                $expectedByte = ord($dictionary[$dictionaryPointer]);
                if ($characterByte === $expectedByte) {
                    $pointer++;
                    if ($pointer > $length - 1) {
                        $nextNode = ord($dictionary[$dictionaryPointer + 1]);
                        if ($nextNode === self::DICT_INTERMEDIATE_MATCH || $nextNode === self::DICT_FINAL_MATCH) {
                            if (isset($codepoints[$pointer]) && ! self::isThaiMark($codepoints[$pointer])) {
                                $matches[] = $pointer - 1;
                            }
                        }

                        return $matches;
                    }
                    $dictionaryPointer++;

                    continue;
                }

                return $matches;
            } elseif ($nodeType === self::DICT_NODE_TYPE_SPLIT) {
                $dictionaryPointer++;
                $splitValue = ord($dictionary[$dictionaryPointer]);
                if ($characterByte < $splitValue) {
                    $dictionaryPointer += 5;
                } else {
                    $dictionaryPointer++;
                    $offset = (ord($dictionary[$dictionaryPointer]) * 16777216)
                        + (ord($dictionary[$dictionaryPointer + 1]) << 16)
                        + (ord($dictionary[$dictionaryPointer + 2]) << 8)
                        + ord($dictionary[$dictionaryPointer + 3]);
                    $dictionaryPointer = $offset;
                }
            } else {
                return $matches;
            }
        }
    }

    private static function loadThaiDictionary(): string
    {
        if (self::$thaiDictionary !== null) {
            return self::$thaiDictionary;
        }

        $dictionaryPath = dirname(__DIR__, 2).'/vendor/mpdf/mpdf/data/linebrdictT.dat';
        if (! is_readable($dictionaryPath)) {
            self::$thaiDictionary = '';

            return self::$thaiDictionary;
        }

        self::$thaiDictionary = (string) file_get_contents($dictionaryPath);

        return self::$thaiDictionary;
    }

    private static function isThaiRepeatCharacter(int $codepoint): bool
    {
        return in_array($codepoint, [0x0E2F, 0x0E46, 0x0EC6], true);
    }

    private static function isThaiMark(int $codepoint): bool
    {
        return ($codepoint >= 0x0E31 && $codepoint <= 0x0E3A)
            || ($codepoint >= 0x0E47 && $codepoint <= 0x0E4E);
    }

    /**
     * @return list<int>
     */
    private static function utf8ToCodepoints(string $text): array
    {
        $codepoints = [];
        $length = mb_strlen($text, 'UTF-8');
        for ($index = 0; $index < $length; $index++) {
            $character = mb_substr($text, $index, 1, 'UTF-8');
            $packed = @unpack('N', @mb_convert_encoding($character, 'UCS-4BE', 'UTF-8'));
            $codepoints[] = is_array($packed) ? (int) ($packed[1] ?? 0) : 0;
        }

        return $codepoints;
    }

    private static function codepointToUtf8(int $codepoint): string
    {
        $converted = @mb_convert_encoding(pack('N', $codepoint), 'UTF-8', 'UCS-4BE');

        return is_string($converted) ? $converted : '';
    }
}
