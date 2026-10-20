<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Schema;

abstract class Controller
{
    /**
     * @var array<string, array<int, string>>
     */
    private static array $tableColumnsCache = [];

    protected function applyStaffCreateAudit(string $table, array $payload): array
    {
        return $this->applyStaffAudit($table, $payload, true);
    }

    protected function applyStaffModifyAudit(string $table, array $payload): array
    {
        return $this->applyStaffAudit($table, $payload, false);
    }

    protected function currentActorId(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return session('staff_id');
        }

        foreach (['staff_id', 'staff_code', 'user_id', 'id'] as $field) {
            $value = data_get($user, $field);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return session('staff_id');
    }

    /**
     * Store dates as Gregorian (ค.ศ.). Years in Buddhist era (พ.ศ., typically >= 2400) are converted.
     */
    protected function normalizeStoredDate(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '' || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})(.*)$/', $raw, $m)) {
            return $raw;
        }

        $year = (int) $m[1];
        if ($year >= 2400) {
            $year -= 543;
        }

        return sprintf('%04d-%s-%s%s', $year, $m[2], $m[3], $m[4]);
    }

    private function applyStaffAudit(string $table, array $payload, bool $isCreate): array
    {
        $actorId = $this->currentActorId();
        if ($actorId === null || $actorId === '') {
            return $payload;
        }

        $columns = $this->tableColumns($table);
        $columnSet = array_flip($columns);

        $exactColumn = $isCreate ? 'staff_create' : 'staff_modify';
        if (isset($columnSet[$exactColumn]) && ! array_key_exists($exactColumn, $payload)) {
            $payload[$exactColumn] = $actorId;
        }

        $suffix = $isCreate ? '_staff_create' : '_staff_modify';
        foreach ($columns as $column) {
            if (str_ends_with($column, $suffix) && ! array_key_exists($column, $payload)) {
                $payload[$column] = $actorId;
            }
        }

        return $payload;
    }

    /**
     * @return array<int, string>
     */
    private function tableColumns(string $table): array
    {
        if (! array_key_exists($table, self::$tableColumnsCache)) {
            self::$tableColumnsCache[$table] = Schema::getColumnListing($table);
        }

        return self::$tableColumnsCache[$table];
    }
}
