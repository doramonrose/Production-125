<?php

namespace App\Support;

final class ThaiBahtText
{
    public static function fromAmount(float $amount): string
    {
        if (! is_finite($amount) || $amount < 0) {
            return '-';
        }

        $digitText = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $unitText = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        $convertInteger = static function (string $numText) use ($digitText, $unitText): string {
            if ($numText === '' || preg_match('/^0+$/', $numText)) {
                return 'ศูนย์';
            }

            $output = '';
            $length = strlen($numText);
            for ($index = 0; $index < $length; $index += 1) {
                $digit = (int) $numText[$index];
                $position = $length - $index - 1;
                $unitIndex = $position % 6;
                if ($digit === 0) {
                    if ($unitIndex === 0 && $position >= 6) {
                        $output .= $unitText[6];
                    }
                    continue;
                }
                if ($unitIndex === 0 && $position >= 6) {
                    $output .= $unitText[6];
                }
                if ($unitIndex === 1) {
                    if ($digit === 1) {
                        $output .= $unitText[1];
                    } elseif ($digit === 2) {
                        $output .= 'ยี่'.$unitText[1];
                    } else {
                        $output .= $digitText[$digit].$unitText[1];
                    }
                    continue;
                }
                if ($unitIndex === 0 && $digit === 1 && $length > 1) {
                    $output .= 'เอ็ด';
                    continue;
                }
                $output .= $digitText[$digit].$unitText[$unitIndex];
            }

            return $output;
        };

        $fixed = number_format($amount, 2, '.', '');
        [$bahtPartRaw, $satangPartRaw] = explode('.', $fixed);
        $bahtText = $convertInteger($bahtPartRaw);
        if ((int) $satangPartRaw === 0) {
            return $bahtText.'บาทถ้วน';
        }

        return $bahtText.'บาท'.$convertInteger($satangPartRaw).'สตางค์';
    }

    public static function formatAmount(float $amount): string
    {
        if (! is_finite($amount)) {
            return '0.-';
        }
        if (abs($amount - round($amount)) < 0.005) {
            return number_format($amount, 0, '.', ',').'.-';
        }

        return number_format($amount, 2, '.', ',');
    }
}
