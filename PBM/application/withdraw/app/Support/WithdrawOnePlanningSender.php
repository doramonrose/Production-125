<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ส่งงบประมาณที่ใช้ไป One Planning (ใช้ร่วมหลายประเภทเบิกจ่าย)
 */
class WithdrawOnePlanningSender
{
    /**
     * @param  list<array<string, mixed>>|null  $payloads
     * @return array{
     *   success_count:int,
     *   failed_count:int,
     *   ref_status_name:string,
     *   trigger_source:string,
     *   results:list<array<string,mixed>>,
     *   skipped?:bool
     * }
     */
    public static function send(
        int $withdrawId,
        string $refStatusName,
        string $triggerSource = 'manual',
        string $staffId = '',
        ?array $payloads = null,
        string $refIdPrefix = 'withdraw'
    ): array {
        if ($withdrawId <= 0) {
            return [
                'skipped' => true,
                'success_count' => 0,
                'failed_count' => 0,
                'ref_status_name' => $refStatusName,
                'trigger_source' => $triggerSource,
                'results' => [],
            ];
        }

        $payloads = is_array($payloads)
            ? $payloads
            : self::buildPayloadsFromItems($withdrawId, $refStatusName, $refIdPrefix);

        if ($payloads === []) {
            return [
                'skipped' => true,
                'success_count' => 0,
                'failed_count' => 0,
                'ref_status_name' => $refStatusName,
                'trigger_source' => $triggerSource,
                'results' => [],
            ];
        }

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($payloads as $payload) {
            $payload['ref_status_name'] = $refStatusName;
            $apiResult = OnePlanningClient::addBudgetUsed($payload);
            $ok = (bool) ($apiResult['ok'] ?? false);
            if ($ok) {
                $successCount++;
            } else {
                $failedCount++;
            }

            self::storeSendLog(
                $withdrawId,
                $payload,
                $triggerSource,
                $ok,
                (string) ($apiResult['message'] ?? ''),
                $apiResult['raw'] ?? null,
                $staffId,
                $refIdPrefix
            );

            $results[] = [
                'budget_code' => (string) ($payload['budget_code'] ?? ''),
                'budget_value' => (string) ($payload['budget_value'] ?? ''),
                'comment' => (string) ($payload['comment'] ?? ''),
                'ref_id' => (string) ($payload['ref_id'] ?? ''),
                'ref_status_name' => (string) ($payload['ref_status_name'] ?? $refStatusName),
                'ok' => $ok,
                'message' => (string) ($apiResult['message'] ?? ''),
                'http_status' => (int) ($apiResult['http_status'] ?? 0),
                'error' => $apiResult['error'] ?? null,
            ];
        }

        if (Schema::hasTable('withdraw_action_logs')) {
            $currentStatus = trim((string) (DB::table('withdraw')->where('withdraw_id', $withdrawId)->value('withdraw_status') ?? ''));
            DB::table('withdraw_action_logs')->insert([
                'withdraw_id' => $withdrawId,
                'action_code' => 'one_planning_send',
                'action_label' => 'ส่งรายการไป One Planning ('.$refStatusName.') สำเร็จ '.$successCount.'/'.max(count($payloads), 1),
                'from_status' => $currentStatus !== '' ? $currentStatus : null,
                'to_status' => $currentStatus !== '' ? $currentStatus : null,
                'actor_staff_id' => trim($staffId) !== '' ? trim($staffId) : null,
                'actor_name' => null,
                'comment' => null,
                'meta_json' => json_encode([
                    'trigger_source' => $triggerSource,
                    'ref_status_name' => $refStatusName,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ], JSON_UNESCAPED_UNICODE),
                'action_at' => now(),
            ]);
        }

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'ref_status_name' => $refStatusName,
            'trigger_source' => $triggerSource,
            'results' => $results,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function buildPayloadsFromItems(
        int $withdrawId,
        string $refStatusName = 'อยู่ระหว่างดำเนินการ',
        string $refIdPrefix = 'withdraw'
    ): array {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_item')) {
            return [];
        }

        $hasBudgetManageColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id');
        $hasBudgetManageCodeColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_code');
        $hasFinanceAmountColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_finance_amount');

        $selects = [
            'withdraw_item_category as category',
            'withdraw_item_amount as amount',
        ];
        $selects[] = $hasFinanceAmountColumn
            ? 'withdraw_item_finance_amount as finance_amount'
            : DB::raw('NULL as finance_amount');
        $selects[] = $hasBudgetManageColumn
            ? 'withdraw_item_budget_manage_id as budget_manage_id'
            : DB::raw('0 as budget_manage_id');
        $selects[] = $hasBudgetManageCodeColumn
            ? 'withdraw_item_budget_manage_code as budget_manage_code'
            : DB::raw("'' as budget_manage_code");

        $rows = DB::table('withdraw_item')
            ->where('withdraw_id', $withdrawId)
            ->orderBy('withdraw_item_order')
            ->orderBy('withdraw_item_id')
            ->get($selects);

        $missingIds = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row->budget_manage_code ?? ''));
            $manageId = (int) ($row->budget_manage_id ?? 0);
            if ($code === '' && $manageId > 0) {
                $missingIds[$manageId] = $manageId;
            }
        }
        $codeByManageId = $missingIds !== []
            ? WithdrawSchema::budgetManageCodesByIds(array_values($missingIds))
            : [];

        $grouped = [];
        foreach ($rows as $row) {
            $budgetManageId = (int) ($row->budget_manage_id ?? 0);
            $budgetCode = trim((string) ($row->budget_manage_code ?? ''));
            if ($budgetCode === '' && $budgetManageId > 0) {
                $budgetCode = trim((string) ($codeByManageId[$budgetManageId] ?? ''));
            }

            $financeAmount = $row->finance_amount !== null ? (float) $row->finance_amount : null;
            $amount = ($financeAmount !== null && $financeAmount > 0)
                ? $financeAmount
                : (float) ($row->amount ?? 0);
            if ($budgetCode === '' || ! is_finite($amount) || $amount <= 0) {
                continue;
            }

            $category = trim((string) ($row->category ?? ''));
            if (! isset($grouped[$budgetCode])) {
                $grouped[$budgetCode] = [
                    'budget_code' => $budgetCode,
                    'budget_manage_id' => $budgetManageId,
                    'amount' => 0.0,
                    'categories' => [],
                ];
            }
            $grouped[$budgetCode]['amount'] += $amount;
            if ($grouped[$budgetCode]['budget_manage_id'] <= 0 && $budgetManageId > 0) {
                $grouped[$budgetCode]['budget_manage_id'] = $budgetManageId;
            }
            if ($category !== '') {
                $grouped[$budgetCode]['categories'][$category] = true;
            }
        }

        $refCode = (string) config('services.one_planning.ref_code', 'BGAPMC');
        $departmentId = (string) config('services.one_planning.budget_departments_id', '11');
        $normalizedStatus = trim($refStatusName) !== '' ? trim($refStatusName) : 'อยู่ระหว่างดำเนินการ';
        $lastByBudget = self::loadLatestSuccessfulRefIds($withdrawId);

        return collect($grouped)
            ->values()
            ->map(function (array $group, int $idx) use ($withdrawId, $refCode, $departmentId, $normalizedStatus, $lastByBudget, $refIdPrefix) {
                $categories = array_keys($group['categories']);
                $amount = round((float) $group['amount'], 2);
                $budgetCode = (string) $group['budget_code'];
                $previousRefId = (string) ($lastByBudget[$budgetCode] ?? '');

                return [
                    'ref_code' => $refCode,
                    'ref_id' => self::buildRefId($withdrawId, $idx + 1, $previousRefId, $refIdPrefix),
                    'ref_status_name' => $normalizedStatus,
                    'ref_edit_status' => 'Y',
                    'budget_code' => $budgetCode,
                    'budget_departments_id' => $departmentId,
                    'budget_value' => number_format($amount, 2, '.', ''),
                    'comment' => implode(', ', $categories),
                    'budget_manage_id' => (int) $group['budget_manage_id'],
                    'categories' => $categories,
                ];
            })
            ->filter(fn (array $payload) => (float) $payload['budget_value'] > 0)
            ->values()
            ->all();
    }

    /**
     * รูปแบบ: {prefix}_{withdrawId}_{ลำดับงบ} (ยาวไม่เกิน 20 ตัวอักษร)
     */
    public static function buildRefId(
        int $withdrawId,
        int $budgetIndex = 1,
        ?string $previousRefId = null,
        string $prefix = 'withdraw'
    ): string {
        $safePrefix = preg_replace('/[^A-Za-z0-9]/', '', trim($prefix)) ?: 'withdraw';
        $budgetIndex = max(1, $budgetIndex);
        $expected = mb_substr($safePrefix.'_'.$withdrawId.'_'.$budgetIndex, 0, 20);
        $previousRefId = trim((string) $previousRefId);

        if (
            $previousRefId !== ''
            && strlen($previousRefId) <= 20
            && preg_match(
                '/^'.preg_quote($safePrefix, '/').'_'.preg_quote((string) $withdrawId, '/').'_\d+$/',
                $previousRefId
            ) === 1
        ) {
            return $previousRefId;
        }

        return $expected;
    }

    /**
     * @return array<string, string>
     */
    private static function loadLatestSuccessfulRefIds(int $withdrawId): array
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_one_planning_send')) {
            return [];
        }

        $rows = DB::table('withdraw_one_planning_send')
            ->where('withdraw_id', $withdrawId)
            ->where('is_success', 1)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->get(['budget_code', 'ref_id']);

        $latest = [];
        foreach ($rows as $row) {
            $budgetCode = trim((string) ($row->budget_code ?? ''));
            if ($budgetCode === '' || isset($latest[$budgetCode])) {
                continue;
            }
            $latest[$budgetCode] = trim((string) ($row->ref_id ?? ''));
        }

        return $latest;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  mixed  $responseRaw
     */
    private static function storeSendLog(
        int $withdrawId,
        array $payload,
        string $triggerSource,
        bool $isSuccess,
        string $responseMessage = '',
        $responseRaw = null,
        string $staffId = '',
        string $refIdPrefix = 'withdraw'
    ): void {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_one_planning_send')) {
            return;
        }

        $responseJson = null;
        if (is_array($responseRaw) || is_object($responseRaw)) {
            $responseJson = json_encode($responseRaw, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($responseRaw) && trim($responseRaw) !== '') {
            $responseJson = $responseRaw;
        }

        DB::table('withdraw_one_planning_send')->insert([
            'withdraw_id' => $withdrawId,
            'ref_id' => (string) ($payload['ref_id'] ?? self::buildRefId(
                $withdrawId,
                1,
                null,
                $refIdPrefix
            )),
            'ref_status_name' => (string) ($payload['ref_status_name'] ?? ''),
            'budget_code' => (string) ($payload['budget_code'] ?? ''),
            'budget_value' => (float) ($payload['budget_value'] ?? 0),
            'comment' => (string) ($payload['comment'] ?? ''),
            'budget_departments_id' => (string) ($payload['budget_departments_id'] ?? ''),
            'trigger_source' => trim($triggerSource) !== '' ? trim($triggerSource) : 'manual',
            'is_success' => $isSuccess ? 1 : 0,
            'response_message' => $responseMessage !== '' ? mb_substr($responseMessage, 0, 255) : null,
            'response_json' => $responseJson,
            'staff_id' => trim($staffId) !== '' ? trim($staffId) : null,
            'sent_at' => now(),
        ]);
    }
}
