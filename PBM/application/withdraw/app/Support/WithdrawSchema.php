<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Unified withdraw storage: one record table plus typed lookup rows.
 * Add a new disbursement kind by inserting into withdraw_type (and a memo template).
 */
class WithdrawSchema
{
    public const TABLE = 'withdraw';

    public const ITEM_TABLE = 'withdraw_item';

    public const FILE_TABLE = 'withdraw_file';

    public const REQ_DOCUMENT_TABLE = 'withdraw_req_document';

    public const APPROVAL_STEP_TABLE = 'withdraw_approval_step';

    public const ACTION_LOG_TABLE = 'withdraw_action_logs';

    public const PROJECT_PROCUREMENT_TABLE = 'withdraw_project_procurement';

    public const PROJECT_PROCUREMENT_FILE_TABLE = 'withdraw_project_procurement_file';

    public const BUDGET_TABLE = 'withdraw_budget';

    public const STATUS_TABLE = 'withdraw_status';

    public const TYPE_TABLE = 'withdraw_type';

    public const TYPE_GENERAL = 'general';

    public const TYPE_SALARY = 'salary';

    public const TYPE_PROJECT = 'project';

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        self::TYPE_GENERAL => 'เบิกจ่ายทั่วไป',
        self::TYPE_PROJECT => 'เบิกจ่ายเงินโครงการ',
        self::TYPE_SALARY => 'เบิกจ่ายเงินเดือน/ค่าจ้าง',
    ];

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, string>
     */
    public static function budgetManageCodesByIds(array $ids): array
    {
        $normalizedIds = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalizedIds[$id] = $id;
            }
        }
        if ($normalizedIds === []) {
            return [];
        }

        try {
            if (! Schema::connection('budget')->hasTable('budget_manage')
                || ! Schema::connection('budget')->hasColumn('budget_manage', 'budget_manage_code')) {
                return [];
            }

            $codes = [];
            $rows = DB::connection('budget')
                ->table('budget_manage')
                ->whereIn('budget_manage_id', array_values($normalizedIds))
                ->get(['budget_manage_id', 'budget_manage_code']);
            foreach ($rows as $row) {
                $id = (int) ($row->budget_manage_id ?? 0);
                $code = trim((string) ($row->budget_manage_code ?? ''));
                if ($id > 0 && $code !== '') {
                    $codes[$id] = $code;
                }
            }

            return $codes;
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function budgetManageCodeById(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        return trim((string) (self::budgetManageCodesByIds([$id])[$id] ?? ''));
    }
}
