<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'withdraw_salary',
            'withdraw_salary_item',
            'withdraw_salary_document',
            'withdraw_salary_file',
            'withdraw_salary_approval_step',
            'withdraw_salary_action_logs',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = DB::select(sprintf('SHOW FULL COLUMNS FROM `%s`', $table));
            foreach ($columns as $column) {
                $from = (string) ($column->Field ?? '');
                if (! str_starts_with($from, 'withdraw_general_')) {
                    continue;
                }

                $to = 'withdraw_salary_'.substr($from, strlen('withdraw_general_'));
                if ($to === $from) {
                    continue;
                }

                $type = (string) ($column->Type ?? '');
                $nullable = ((string) ($column->Null ?? 'YES')) === 'YES' ? 'NULL' : 'NOT NULL';
                $default = $this->buildDefaultClause($column->Default ?? null, $nullable);
                $extra = trim((string) ($column->Extra ?? ''));
                $comment = (string) ($column->Comment ?? '');

                $statement = sprintf(
                    'ALTER TABLE `%s` CHANGE `%s` `%s` %s %s %s %s',
                    $table,
                    $from,
                    $to,
                    $type,
                    $nullable,
                    $default,
                    $extra
                );

                if ($comment !== '') {
                    $statement .= " COMMENT '".str_replace("'", "''", $comment)."'";
                }

                DB::statement(trim($statement));
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'withdraw_salary',
            'withdraw_salary_item',
            'withdraw_salary_document',
            'withdraw_salary_file',
            'withdraw_salary_approval_step',
            'withdraw_salary_action_logs',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = DB::select(sprintf('SHOW FULL COLUMNS FROM `%s`', $table));
            foreach ($columns as $column) {
                $from = (string) ($column->Field ?? '');
                if (! str_starts_with($from, 'withdraw_salary_')) {
                    continue;
                }

                $to = 'withdraw_general_'.substr($from, strlen('withdraw_salary_'));
                if ($to === $from) {
                    continue;
                }

                $type = (string) ($column->Type ?? '');
                $nullable = ((string) ($column->Null ?? 'YES')) === 'YES' ? 'NULL' : 'NOT NULL';
                $default = $this->buildDefaultClause($column->Default ?? null, $nullable);
                $extra = trim((string) ($column->Extra ?? ''));
                $comment = (string) ($column->Comment ?? '');

                $statement = sprintf(
                    'ALTER TABLE `%s` CHANGE `%s` `%s` %s %s %s %s',
                    $table,
                    $from,
                    $to,
                    $type,
                    $nullable,
                    $default,
                    $extra
                );

                if ($comment !== '') {
                    $statement .= " COMMENT '".str_replace("'", "''", $comment)."'";
                }

                DB::statement(trim($statement));
            }
        }
    }

    private function buildDefaultClause(mixed $default, string $nullable): string
    {
        if ($default === null) {
            return $nullable === 'NULL' ? 'DEFAULT NULL' : '';
        }

        $value = (string) $default;
        $upper = strtoupper($value);
        if (in_array($upper, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()', 'NULL'], true)) {
            return 'DEFAULT '.$upper;
        }

        return "DEFAULT '".str_replace("'", "''", $value)."'";
    }
};

