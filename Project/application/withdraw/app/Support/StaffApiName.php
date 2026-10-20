<?php

namespace App\Support;

final class StaffApiName
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function prefixFromRow(array $row): string
    {
        $full = trim((string) ($row['name_prefix_full'] ?? ''));
        if ($full !== '') {
            return $full;
        }

        return trim((string) ($row['name_prefix'] ?? $row['prefix'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function composeDisplayName(array $row): string
    {
        $name = trim(
            self::prefixFromRow($row).
            (string) ($row['fname'] ?? $row['first_name'] ?? '').
            ' '.
            (string) ($row['lname'] ?? $row['last_name'] ?? '')
        );
        if ($name === '') {
            $name = trim((string) ($row['name'] ?? $row['full_name'] ?? $row['fullname'] ?? ''));
        }
        if ($name === '') {
            $name = trim(
                (string) ($row['e_fname'] ?? '').
                ' '.
                (string) ($row['e_lname'] ?? '')
            );
        }

        return $name !== '' ? (preg_replace('/\s+/', ' ', $name) ?? $name) : '';
    }
}
