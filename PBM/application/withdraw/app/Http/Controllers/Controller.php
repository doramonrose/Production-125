<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
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

    /**
     * ตรวจว่าผู้อนุมัติตาม withdraw_set_approver เปิดรับการแจ้งเตือน (notification/อีเมล) หรือไม่
     * หากไม่พบแถวที่ตรงกับ step + staff หรือยังไม่มีคอลัมน์ ให้ถือว่าเปิด (พฤติกรรมเดิม)
     */
    protected function withdrawSetApproverNotificationsEnabled(string $stepCode, string $staffId): bool
    {
        $staffId = trim($staffId);
        if ($staffId === '' || ! Schema::hasTable('withdraw_set_approver')) {
            return true;
        }
        if (! Schema::hasColumn('withdraw_set_approver', 'withdraw_set_approver_notification_enabled')) {
            return true;
        }

        $code = trim($stepCode);
        $query = DB::table('withdraw_set_approver')
            ->where('withdraw_set_approver_status', 1)
            ->where('withdraw_set_approver_staff_id', $staffId);
        if ($code !== '') {
            $query->where('withdraw_set_approver_step_code', $code);
        }

        $row = $query->first(['withdraw_set_approver_notification_enabled']);
        if ($row === null) {
            return true;
        }

        return (int) ($row->withdraw_set_approver_notification_enabled ?? 1) === 1;
    }
}
