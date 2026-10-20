<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WithdrawMemoTemplate
{
    /** @var array<string, string> */
    public const KIND_LABELS = [
        'general' => 'เบิกจ่ายทั่วไป',
        'project' => 'เบิกจ่ายเงินโครงการ',
        'salary' => 'เบิกจ่ายเงินเดือน/ค่าจ้าง',
        'travel' => 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน',
    ];

    /** @var array<string, array<string, string>> */
    public const SUB_TYPE_LABELS = [
        'general' => [
            'default' => 'ทั่วไป',
        ],
        'project' => [
            'default' => 'โครงการ',
        ],
        'salary' => [
            'salary_civil_servant' => 'เงินเดือนข้าราชการ',
            'salary_university_staff' => 'เงินเดือนพนักงานมหาวิทยาลัย',
            'position_allowance' => 'เงินประจำตำแหน่ง',
            'pts' => 'เงิน พ.ต.ส.',
        ],
        'travel' => [
            'default' => 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน',
        ],
    ];

    /** @var array<int, string> */
    public const BLOCK_KEYS = ['paragraph1', 'paragraph2', 'items', 'paragraph3', 'paragraph4'];

    /** @var array<int, string> */
    public const PARAGRAPH_KEYS = ['paragraph1', 'paragraph2', 'paragraph3', 'paragraph4'];

    /** @var array<string, string> */
    public const BLOCK_LABELS = [
        'paragraph1' => 'ย่อหน้า 1',
        'paragraph2' => 'ย่อหน้า 2',
        'items' => 'รายการเบิกจ่าย',
        'paragraph3' => 'ย่อหน้า 3',
        'paragraph4' => 'ย่อหน้า 4',
    ];

    /** @var array<string, string> */
    public const EXTRA_APPROVAL_STEP_OPTIONS = [
        'reviewer_head' => 'ผู้ตรวจสอบ/หัวหน้างาน',
        'finance_officer' => 'งานการเงิน',
    ];

    /** @var array<string, int> */
    private const EXTRA_APPROVAL_STEP_ORDERS = [
        'reviewer_head' => 10,
        'finance_officer' => 40,
    ];

    /** @var array<string, string> */
    private const FIXED_APPROVAL_STEP_NAMES = [
        'finance_officer' => 'งานการเงิน',
        'finance_manager' => 'หัวหน้างานการเงิน',
        'faculty_secretary' => 'เลขาคณะ',
        'vice_dean' => 'รองคณบดี',
        'dean' => 'คณบดี',
    ];

    /** @var array<int, string> */
    public const DEFAULT_GENERAL_APPROVAL_STEP_CODES = [
        'reviewer_head',
        'finance_officer',
        'finance_manager',
        'faculty_secretary',
        'vice_dean',
        'dean',
    ];

    public static function normalizeKind(string $kind): string
    {
        $kind = strtolower(trim($kind));

        return in_array($kind, ['general', 'project', 'salary', 'travel'], true) ? $kind : 'general';
    }

    public static function normalizeSubType(string $kind, string $subType): string
    {
        $kind = self::normalizeKind($kind);
        $subType = trim($subType);
        if ($kind === 'salary' && $subType === 'salary') {
            $subType = 'salary_civil_servant';
        }
        $allowed = array_keys(self::SUB_TYPE_LABELS[$kind] ?? []);

        if ($subType === '' || ! in_array($subType, $allowed, true)) {
            if ($kind === 'salary') {
                return 'salary_civil_servant';
            }

            return 'default';
        }

        return $subType;
    }

    /**
     * @return array<int, string>
     */
    public static function normalizeBlockOrder(mixed $raw): array
    {
        $blocks = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
        if (! is_array($blocks)) {
            $blocks = [];
        }

        $normalized = [];
        foreach ($blocks as $block) {
            $key = trim((string) $block);
            if ($key !== '' && in_array($key, self::BLOCK_KEYS, true) && ! in_array($key, $normalized, true)) {
                $normalized[] = $key;
            }
        }

        foreach (self::BLOCK_KEYS as $fallback) {
            if (! in_array($fallback, $normalized, true)) {
                $normalized[] = $fallback;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultParagraphEnabled(): array
    {
        return array_fill_keys(self::PARAGRAPH_KEYS, true);
    }

    /**
     * @return array<string, bool>
     */
    public static function normalizeParagraphEnabled(mixed $raw): array
    {
        $source = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
        if (! is_array($source)) {
            $source = [];
        }

        $enabled = self::defaultParagraphEnabled();
        foreach (self::PARAGRAPH_KEYS as $key) {
            if (! array_key_exists($key, $source)) {
                continue;
            }
            $value = $source[$key];
            $enabled[$key] = ! in_array($value, [false, 0, '0', 'false', 'off'], true);
        }

        return $enabled;
    }

    /**
     * @param  array<int, string>  $blockOrder
     * @param  array<string, bool>  $paragraphEnabled
     * @return array<int, string>
     */
    public static function filterEnabledBlocks(array $blockOrder, array $paragraphEnabled): array
    {
        $enabled = self::normalizeParagraphEnabled($paragraphEnabled);

        return array_values(array_filter(
            $blockOrder,
            static function ($key) use ($enabled): bool {
                $block = trim((string) $key);
                if (! in_array($block, self::PARAGRAPH_KEYS, true)) {
                    return true;
                }

                return ($enabled[$block] ?? true) === true;
            }
        ));
    }

    /**
     * @return array<string, bool>
     */
    public static function paragraphEnabledForWithdraw(object $row): array
    {
        [$kind, $subType] = self::kindForWithdraw($row);
        $template = self::resolve($kind, $subType);

        return self::normalizeParagraphEnabled($template['paragraph_enabled'] ?? null);
    }

    /**
     * @return array<int, string>
     */
    public static function blockOrderForWithdraw(object $row): array
    {
        [$kind, $subType] = self::kindForWithdraw($row);
        $template = self::resolve($kind, $subType);

        return self::normalizeBlockOrder($template['block_order'] ?? null);
    }

    /**
     * @return array{0:string,1:string}
     */
    private static function kindForWithdraw(object $row): array
    {
        $type = strtolower(trim((string) ($row->withdraw_type ?? 'general')));
        if ($type === 'project') {
            return ['project', 'default'];
        }
        if ($type === 'travel' || ($type === 'salary' && trim((string) ($row->withdraw_sub_type ?? '')) === 'travel_leave')) {
            return ['travel', 'default'];
        }
        if ($type === 'salary' || str_starts_with($type, 'salary')) {
            return ['salary', trim((string) ($row->withdraw_sub_type ?? 'salary_civil_servant'))];
        }

        return ['general', 'default'];
    }

    /**
     * @return array<int, string>
     */
    public static function normalizeApprovalStepCodes(mixed $raw): array
    {
        $codes = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
        if (! is_array($codes)) {
            return [];
        }

        $normalized = array_values(array_unique(array_filter(array_map(
            static fn ($code) => trim((string) $code),
            $codes
        ), static fn ($code) => $code !== '')));

        return self::sortApprovalStepCodes($normalized);
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public static function sortApprovalStepCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        $orderMap = [];
        foreach (self::approvalStepOptions() as $option) {
            $code = (string) ($option['code'] ?? '');
            if ($code !== '') {
                $orderMap[$code] = (int) ($option['order'] ?? 0);
            }
        }

        usort($codes, static function (string $a, string $b) use ($orderMap): int {
            $orderCompare = ($orderMap[$a] ?? PHP_INT_MAX) <=> ($orderMap[$b] ?? PHP_INT_MAX);

            return $orderCompare !== 0 ? $orderCompare : strcmp($a, $b);
        });

        return $codes;
    }

    /**
     * @return array<int, array{staff_id:string,status:string}>
     */
    public static function normalizeProcessActors(mixed $raw): array
    {
        $rows = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            if ($staffId === '') {
                continue;
            }
            $normalized[] = [
                'staff_id' => $staffId,
                'status' => trim((string) ($row['status'] ?? '')),
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function resolve(string $kind, string $subType = ''): ?array
    {
        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return null;
        }

        $kind = self::normalizeKind($kind);
        $subType = self::normalizeSubType($kind, $subType);

        $row = DB::table('withdraw_set_memo_template')
            ->where('withdraw_set_memo_template_kind', $kind)
            ->where('withdraw_set_memo_template_sub_type', $subType)
            ->where('withdraw_set_memo_template_status', 1)
            ->first();

        if (! $row && $kind === 'project') {
            $row = DB::table('withdraw_set_memo_template')
                ->where('withdraw_set_memo_template_kind', 'general')
                ->where('withdraw_set_memo_template_sub_type', 'default')
                ->where('withdraw_set_memo_template_status', 1)
                ->first();
        }

        if (! $row) {
            return null;
        }

        return self::mapRow($row);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function catalog(): array
    {
        $catalog = [];
        foreach (self::KIND_LABELS as $kind => $kindLabel) {
            foreach (self::SUB_TYPE_LABELS[$kind] ?? [] as $subType => $subLabel) {
                $template = self::resolve($kind, $subType);
                $catalog[] = [
                    'kind' => $kind,
                    'kind_label' => $kindLabel,
                    'sub_type' => $subType,
                    'sub_type_label' => $subLabel,
                    'template' => $template,
                ];
            }
        }

        return $catalog;
    }

    /**
     * @return array<int, array{code:string,label:string,order:int}>
     */
    public static function approvalStepOptions(): array
    {
        $options = [];
        foreach (self::EXTRA_APPROVAL_STEP_OPTIONS as $code => $label) {
            $options[$code] = [
                'code' => $code,
                'label' => $label,
                'order' => self::EXTRA_APPROVAL_STEP_ORDERS[$code] ?? 0,
            ];
        }

        if (Schema::hasTable('withdraw_set_approver')) {
            $rows = DB::table('withdraw_set_approver')
                ->where('withdraw_set_approver_status', 1)
                ->orderBy('withdraw_set_approver_step_order')
                ->orderBy('withdraw_set_approver_id')
                ->get([
                    'withdraw_set_approver_step_code',
                    'withdraw_set_approver_step_name',
                    'withdraw_set_approver_step_order',
                ]);

            foreach ($rows as $row) {
                $code = trim((string) ($row->withdraw_set_approver_step_code ?? ''));
                if ($code === '') {
                    continue;
                }
                $options[$code] = [
                    'code' => $code,
                    'label' => trim((string) ($row->withdraw_set_approver_step_name ?? $code)),
                    'order' => (int) ($row->withdraw_set_approver_step_order ?? 0),
                ];
            }
        }

        uasort($options, static fn ($a, $b) => ($a['order'] <=> $b['order']) ?: strcmp($a['label'], $b['label']));

        return array_values($options);
    }

    /**
     * @return array<int, array{code:string,name:string,order:int}>
     */
    public static function resolveApprovalRouteSteps(string $kind, string $subType, float $totalAmount = 0): array
    {
        $kind = self::normalizeKind($kind);
        $subType = self::normalizeSubType($kind, $subType);
        $template = self::resolve($kind, $subType);
        $allowedCodes = self::normalizeApprovalStepCodes($template['approval_step_codes'] ?? []);
        if ($allowedCodes === []) {
            $allowedCodes = in_array($kind, ['salary', 'travel'], true)
                ? ['finance_manager', 'faculty_secretary', 'vice_dean', 'dean']
                : self::DEFAULT_GENERAL_APPROVAL_STEP_CODES;
        }

        $labelByCode = self::FIXED_APPROVAL_STEP_NAMES;
        foreach (self::EXTRA_APPROVAL_STEP_OPTIONS as $code => $label) {
            $labelByCode[$code] = $label;
        }
        foreach (self::approvalStepOptions() as $option) {
            $code = (string) ($option['code'] ?? '');
            if ($code !== '') {
                $labelByCode[$code] = (string) ($option['label'] ?? $code);
            }
        }

        $orderByCode = self::EXTRA_APPROVAL_STEP_ORDERS;
        foreach (self::approvalStepOptions() as $option) {
            $code = (string) ($option['code'] ?? '');
            if ($code !== '') {
                $orderByCode[$code] = (int) ($option['order'] ?? 50);
            }
        }

        $steps = [];
        foreach ($allowedCodes as $code) {
            if ($code === 'additional_reviewer') {
                continue;
            }
            if ($code === 'dean' && $totalAmount > 0 && $totalAmount <= 100000) {
                continue;
            }
            $steps[] = [
                'code' => $code,
                'name' => (string) ($labelByCode[$code] ?? $code),
                'order' => (int) ($orderByCode[$code] ?? 50),
            ];
        }

        usort($steps, static fn (array $a, array $b) => ($a['order'] <=> $b['order']) ?: strcmp($a['code'], $b['code']));

        return $steps;
    }

    /**
     * @param  array<int, array{code:string,name:string,order:int}>  $fixedSteps
     * @return array<int, array{code:string,name:string,order:int}>
     */
    public static function filterFixedApprovalSteps(array $fixedSteps, string $kind, string $subType): array
    {
        $routeSteps = self::resolveApprovalRouteSteps($kind, $subType);
        $allowed = array_map(static fn (array $step) => (string) ($step['code'] ?? ''), $routeSteps);
        $allowed = array_values(array_filter($allowed, static fn (string $code) => ! in_array($code, ['reviewer_head', 'additional_reviewer'], true)));

        if ($allowed === []) {
            return $fixedSteps;
        }

        $filtered = array_values(array_filter(
            $fixedSteps,
            static fn (array $step) => in_array((string) ($step['code'] ?? ''), $allowed, true)
        ));

        $orderMap = [];
        foreach ($routeSteps as $routeStep) {
            $orderMap[(string) ($routeStep['code'] ?? '')] = (int) ($routeStep['order'] ?? 0);
        }

        foreach ($filtered as &$step) {
            $code = (string) ($step['code'] ?? '');
            if (array_key_exists($code, $orderMap)) {
                $step['order'] = $orderMap[$code];
            }
        }
        unset($step);

        usort($filtered, static fn (array $a, array $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));

        return $filtered;
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapRow(object $row): array
    {
        return [
            'id' => (int) ($row->withdraw_set_memo_template_id ?? 0),
            'kind' => (string) ($row->withdraw_set_memo_template_kind ?? ''),
            'sub_type' => (string) ($row->withdraw_set_memo_template_sub_type ?? ''),
            'name' => trim((string) ($row->withdraw_set_memo_template_name ?? '')),
            'subject' => trim((string) ($row->withdraw_set_memo_template_subject ?? '')),
            'to' => trim((string) ($row->withdraw_set_memo_template_to ?? '')),
            'paragraph1' => trim((string) ($row->withdraw_set_memo_template_paragraph1 ?? '')),
            'paragraph2' => trim((string) ($row->withdraw_set_memo_template_paragraph2 ?? '')),
            'paragraph3' => trim((string) ($row->withdraw_set_memo_template_paragraph3 ?? '')),
            'paragraph4' => Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_paragraph4')
                ? trim((string) ($row->withdraw_set_memo_template_paragraph4 ?? ''))
                : '',
            'paragraph_enabled' => self::normalizeParagraphEnabled(
                Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_paragraph_enabled')
                    ? json_decode((string) ($row->withdraw_set_memo_template_paragraph_enabled ?? ''), true)
                    : null
            ),
            'block_order' => self::normalizeBlockOrder(
                json_decode((string) ($row->withdraw_set_memo_template_block_order ?? ''), true)
            ),
            'approval_step_codes' => self::normalizeApprovalStepCodes(
                json_decode((string) ($row->withdraw_set_memo_template_approval_step_codes ?? ''), true)
            ),
            'process_actors' => self::normalizeProcessActors(
                json_decode((string) ($row->withdraw_set_memo_template_process_actors ?? ''), true)
            ),
            'process_actors_enabled' => self::resolveProcessActorsEnabled($row),
            'status' => (int) ($row->withdraw_set_memo_template_status ?? 1),
        ];
    }

    public static function resolveProcessActorsEnabled(object $row): bool
    {
        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_process_actors_enabled')) {
            return (int) ($row->withdraw_set_memo_template_process_actors_enabled ?? 0) === 1;
        }

        $actors = self::normalizeProcessActors(
            json_decode((string) ($row->withdraw_set_memo_template_process_actors ?? ''), true)
        );

        return count($actors) > 0;
    }
}
