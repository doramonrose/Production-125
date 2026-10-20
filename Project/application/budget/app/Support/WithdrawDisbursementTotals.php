<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class WithdrawDisbursementTotals
{
    /**
     * Statuses that are still in the approval pipeline (not draft, cancelled, or approved).
     *
     * @var list<string>
     */
    private const PENDING_STATUSES = [
        'pending_primary_review',
        'processing',
        'pending_additional_review',
        'pending_finance_review',
        'pending_revision',
        'pending_approval',
    ];

    /**
     * @var list<string>
     */
    private const LAST_APPROVER_STEP_CODES = [
        'faculty_secretary',
        'vice_dean',
        'dean',
    ];

    /**
     * @param  iterable<int, object>  $rows
     * @return array{approved_by_code: array<string, float>, pending_by_code: array<string, float>, approved_by_id: array<int, float>, pending_by_id: array<int, float>}
     */
    public function forManageRows(iterable $rows): array
    {
        $empty = $this->emptyTotals();

        $ids = [];
        $codeById = [];
        foreach ($rows as $row) {
            $id = (int) ($row->budget_manage_id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $ids[$id] = $id;
            $code = trim((string) ($row->budget_manage_code ?? ''));
            if ($code !== '') {
                $codeById[$id] = $code;
            }
        }

        if ($ids === []) {
            return $empty;
        }

        return $this->aggregate($ids, $codeById);
    }

    /**
     * @return array{approved: float, pending: float}
     */
    public function forManage(int $budgetManageId, string $budgetManageCode = ''): array
    {
        $row = (object) [
            'budget_manage_id' => $budgetManageId,
            'budget_manage_code' => $budgetManageCode,
        ];
        $grouped = $this->forManageRows([$row]);

        return $this->amountsFor($budgetManageId, $budgetManageCode, $grouped);
    }

    /**
     * @param  array{approved_by_code: array<string, float>, pending_by_code: array<string, float>, approved_by_id: array<int, float>, pending_by_id: array<int, float>}  $grouped
     * @return array{approved: float, pending: float}
     */
    public function amountsFor(int $budgetManageId, string $budgetManageCode, array $grouped): array
    {
        $code = trim($budgetManageCode);

        $approved = $code !== ''
            ? ($grouped['approved_by_code'][$code] ?? null)
            : null;
        $pending = $code !== ''
            ? ($grouped['pending_by_code'][$code] ?? null)
            : null;

        return [
            'approved' => (float) ($approved ?? $grouped['approved_by_id'][$budgetManageId] ?? 0),
            'pending' => (float) ($pending ?? $grouped['pending_by_id'][$budgetManageId] ?? 0),
        ];
    }

    /**
     * @return array{kind: string, kind_label: string, budget_code: string, total: float, items: list<array<string, mixed>>, groups: list<array<string, mixed>>}
     */
    public function itemsForManage(int $budgetManageId, string $budgetManageCode, string $kind): array
    {
        $kind = in_array($kind, ['pending', 'all'], true) ? $kind : 'approved';
        $kindLabel = match ($kind) {
            'pending' => 'รอเบิกจ่าย',
            'all' => 'รายการเบิกจ่าย',
            default => 'เบิกจ่าย',
        };
        $payload = [
            'kind' => $kind,
            'kind_label' => $kindLabel,
            'budget_code' => trim($budgetManageCode),
            'total' => 0.0,
            'items' => [],
            'groups' => [],
        ];

        $manageIds = $this->resolveManageIds($budgetManageId, $budgetManageCode);
        $budgetCode = trim($budgetManageCode);
        if ($manageIds === [] && $budgetCode === '') {
            return $payload;
        }

        $statuses = match ($kind) {
            'pending' => self::PENDING_STATUSES,
            'all' => array_merge(['approved'], self::PENDING_STATUSES),
            default => ['approved'],
        };

        try {
            $rows = [];
            $this->appendItemDetails($rows, $this->queryItemDetails(
                $manageIds,
                $statuses,
                'withdraw',
                'withdraw_item',
                'withdraw_id',
                'withdraw_status',
                'withdraw_item_budget_manage_id',
                'withdraw_item_amount',
                'withdraw_subject',
                'withdraw_date_create',
                'withdraw_finance_document_date',
                'withdraw_item_name',
                'withdraw_item_category',
                '',
                '',
                $budgetCode
            ));
            $this->appendItemDetails($rows, $this->queryParentFallbackDetails(
                $manageIds,
                $statuses,
                'withdraw',
                'withdraw_item',
                'withdraw_id',
                'withdraw_status',
                'withdraw_item_budget_manage_id',
                'withdraw_item_amount',
                'withdraw_budget_manage_id',
                'withdraw_subject',
                'withdraw_date_create',
                'withdraw_finance_document_date',
                'withdraw_item_name',
                'withdraw_item_category',
                '',
                ''
            ));

            // Deduplicate when the same line matches both manage id and budget code.
            $rows = $this->uniqueItemDetailRows($rows);

            $documentNumbers = $this->documentNumbersFor($rows);
            $applicantNames = $this->staffNamesFor($rows);
            $approvalDates = $this->approvalDatesFor($rows);
            $items = [];
            $total = 0.0;
            foreach ($rows as $row) {
                $amount = (float) ($row['amount'] ?? 0);
                $total += $amount;
                $source = (string) ($row['source'] ?? '');
                $documentId = (int) ($row['document_id'] ?? 0);
                $status = trim((string) ($row['status'] ?? ''));
                $docKey = $source.':'.$documentId;
                $applicantId = trim((string) ($row['applicant_staff_id'] ?? ''));
                if ($applicantId === '') {
                    $applicantId = trim((string) ($row['staff_create'] ?? ''));
                }
                $requestRaw = trim((string) ($row['date_create'] ?? ''));
                $approvedRaw = trim((string) ($approvalDates[$docKey] ?? ''));
                $items[] = [
                    'source' => $source,
                    'source_label' => (string) ($row['source_label'] ?? ''),
                    'document_id' => $documentId,
                    'document_number' => $documentNumbers[$docKey] ?? '',
                    'subject' => trim((string) ($row['subject'] ?? '')),
                    'applicant_name' => trim((string) ($applicantNames[$applicantId] ?? '')),
                    'request_date' => $this->formatDisplayDate($requestRaw),
                    'approved_date' => $this->formatDisplayDate($approvedRaw),
                    'date' => $this->formatDisplayDate($requestRaw),
                    'sort_date' => $requestRaw,
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'item_name' => trim((string) ($row['item_name'] ?? '')),
                    'item_category' => trim((string) ($row['item_category'] ?? '')),
                    'amount' => $amount,
                ];
            }

            usort($items, function (array $a, array $b): int {
                $dateCmp = strcmp((string) ($b['sort_date'] ?? ''), (string) ($a['sort_date'] ?? ''));
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }

                return ((int) ($b['document_id'] ?? 0)) <=> ((int) ($a['document_id'] ?? 0));
            });

            $items = array_map(function (array $item): array {
                unset($item['sort_date']);

                return $item;
            }, $items);

            $payload['total'] = $total;
            $payload['items'] = $items;
            $payload['groups'] = $this->groupByDocument($items);
        } catch (Throwable $e) {
            Log::warning('Failed to load withdraw disbursement items.', [
                'message' => $e->getMessage(),
            ]);
        }

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function groupByDocument(array $items): array
    {
        $groups = [];
        $indexByKey = [];

        foreach ($items as $item) {
            $key = ((string) ($item['source'] ?? '')).':'
                .((int) ($item['document_id'] ?? 0)).':'
                .((string) ($item['document_number'] ?? ''));

            if (! isset($indexByKey[$key])) {
                $indexByKey[$key] = count($groups);
                $groups[] = [
                    'source' => (string) ($item['source'] ?? ''),
                    'source_label' => (string) ($item['source_label'] ?? '-'),
                    'document_id' => (int) ($item['document_id'] ?? 0),
                    'document_number' => (string) ($item['document_number'] ?? ''),
                    'subject' => (string) ($item['subject'] ?? ''),
                    'applicant_name' => (string) ($item['applicant_name'] ?? ''),
                    'request_date' => (string) ($item['request_date'] ?? ''),
                    'approved_date' => (string) ($item['approved_date'] ?? ''),
                    'date' => (string) ($item['date'] ?? ''),
                    'status' => (string) ($item['status'] ?? ''),
                    'status_label' => (string) ($item['status_label'] ?? '-'),
                    'amount' => 0.0,
                    'items' => [],
                ];
            }

            $index = $indexByKey[$key];
            $groups[$index]['items'][] = $item;
            $groups[$index]['amount'] += (float) ($item['amount'] ?? 0);
        }

        return $groups;
    }

    /**
     * @param  array<int, int>  $manageIds
     * @param  array<int, string>  $codeById
     * @return array{approved_by_code: array<string, float>, pending_by_code: array<string, float>, approved_by_id: array<int, float>, pending_by_id: array<int, float>}
     */
    private function aggregate(array $manageIds, array $codeById): array
    {
        $totals = $this->emptyTotals();

        try {
            foreach ($this->sumByManageId($manageIds) as $manageId => $amounts) {
                $id = (int) $manageId;
                if ($id <= 0) {
                    continue;
                }

                $approved = (float) ($amounts['approved'] ?? 0);
                $pending = (float) ($amounts['pending'] ?? 0);

                $totals['approved_by_id'][$id] = ($totals['approved_by_id'][$id] ?? 0) + $approved;
                $totals['pending_by_id'][$id] = ($totals['pending_by_id'][$id] ?? 0) + $pending;
            }

            // Prefer code-level totals so orphan/mismatched manage ids that still carry
            // the correct budget code (e.g. pending salary lines) are included.
            $codes = [];
            foreach ($codeById as $code) {
                $code = trim((string) $code);
                if ($code !== '') {
                    $codes[$code] = $code;
                }
            }
            foreach ($this->sumByBudgetCode(array_values($codes)) as $code => $amounts) {
                $totals['approved_by_code'][$code] = (float) ($amounts['approved'] ?? 0);
                $totals['pending_by_code'][$code] = (float) ($amounts['pending'] ?? 0);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to load withdraw disbursement totals.', [
                'message' => $e->getMessage(),
            ]);
        }

        return $totals;
    }

    /**
     * @param  array<int, int>  $manageIds
     * @return array<int, array{approved: float, pending: float}>
     */
    private function sumByManageId(array $manageIds): array
    {
        $sums = [];

        $this->addSourceSums($sums, $this->queryItemSums(
            $manageIds,
            'withdraw',
            'withdraw_item',
            'withdraw_id',
            'withdraw_status',
            'withdraw_item_budget_manage_id',
            'withdraw_item_amount'
        ));
        $this->addSourceSums($sums, $this->queryParentFallbackSums(
            $manageIds,
            'withdraw',
            'withdraw_item',
            'withdraw_id',
            'withdraw_status',
            'withdraw_item_budget_manage_id',
            'withdraw_item_amount',
            'withdraw_budget_manage_id'
        ));

        return $sums;
    }

    /**
     * @param  array<int, array{approved: float, pending: float}>  $sums
     * @param  iterable<int, object>  $rows
     */
    private function addSourceSums(array &$sums, iterable $rows): void
    {
        foreach ($rows as $row) {
            $id = (int) ($row->budget_manage_id ?? 0);
            if ($id <= 0) {
                continue;
            }

            $sums[$id]['approved'] = ($sums[$id]['approved'] ?? 0) + (float) ($row->approved_amount ?? 0);
            $sums[$id]['pending'] = ($sums[$id]['pending'] ?? 0) + (float) ($row->pending_amount ?? 0);
        }
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, array{approved: float, pending: float}>
     */
    private function sumByBudgetCode(array $codes): array
    {
        $sums = [];
        $codes = array_values(array_filter(array_map(
            static fn ($code) => trim((string) $code),
            $codes
        ), static fn (string $code) => $code !== ''));

        if ($codes === []) {
            return $sums;
        }

        if (
            ! Schema::connection('withdraw')->hasTable('withdraw')
            || ! Schema::connection('withdraw')->hasTable('withdraw_item')
            || ! Schema::connection('withdraw')->hasColumn('withdraw_item', 'withdraw_item_budget_manage_code')
            || ! Schema::connection('withdraw')->hasColumn('withdraw_item', 'withdraw_item_amount')
            || ! Schema::connection('withdraw')->hasColumn('withdraw', 'withdraw_status')
        ) {
            return $sums;
        }

        $pendingList = "'".implode("','", self::PENDING_STATUSES)."'";

        $rows = DB::connection('withdraw')
            ->table('withdraw_item as i')
            ->join('withdraw as p', 'p.withdraw_id', '=', 'i.withdraw_id')
            ->whereIn('i.withdraw_item_budget_manage_code', $codes)
            ->whereRaw("TRIM(COALESCE(i.withdraw_item_budget_manage_code, '')) <> ''")
            ->groupBy('i.withdraw_item_budget_manage_code')
            ->selectRaw('i.withdraw_item_budget_manage_code as budget_manage_code')
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(p.withdraw_status, ''))) = 'approved' THEN i.withdraw_item_amount ELSE 0 END) as approved_amount")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(p.withdraw_status, ''))) IN ({$pendingList}) THEN i.withdraw_item_amount ELSE 0 END) as pending_amount")
            ->get();

        foreach ($rows as $row) {
            $code = trim((string) ($row->budget_manage_code ?? ''));
            if ($code === '') {
                continue;
            }
            $sums[$code] = [
                'approved' => (float) ($row->approved_amount ?? 0),
                'pending' => (float) ($row->pending_amount ?? 0),
            ];
        }

        return $sums;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function uniqueItemDetailRows(array $rows): array
    {
        $unique = [];
        $seen = [];

        foreach ($rows as $row) {
            $key = ((string) ($row['source'] ?? '')).':'
                .((int) ($row['document_id'] ?? 0)).':'
                .((string) ($row['item_name'] ?? '')).':'
                .((string) ($row['item_category'] ?? '')).':'
                .number_format((float) ($row['amount'] ?? 0), 2, '.', '');

            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $row;
        }

        return $unique;
    }

    /**
     * @param  array<int, int>  $manageIds
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function queryItemSums(
        array $manageIds,
        string $parentTable,
        string $itemTable,
        string $parentIdColumn,
        string $statusColumn,
        string $itemBudgetIdColumn,
        string $amountColumn
    ) {
        $empty = collect();

        if (
            ! Schema::connection('withdraw')->hasTable($parentTable)
            || ! Schema::connection('withdraw')->hasTable($itemTable)
            || ! Schema::connection('withdraw')->hasColumn($parentTable, $parentIdColumn)
            || ! Schema::connection('withdraw')->hasColumn($parentTable, $statusColumn)
            || ! Schema::connection('withdraw')->hasColumn($itemTable, $parentIdColumn)
            || ! Schema::connection('withdraw')->hasColumn($itemTable, $itemBudgetIdColumn)
            || ! Schema::connection('withdraw')->hasColumn($itemTable, $amountColumn)
        ) {
            return $empty;
        }

        $pendingList = "'".implode("','", self::PENDING_STATUSES)."'";

        return DB::connection('withdraw')
            ->table("{$itemTable} as i")
            ->join("{$parentTable} as p", "p.{$parentIdColumn}", '=', "i.{$parentIdColumn}")
            ->whereIn("i.{$itemBudgetIdColumn}", $manageIds)
            ->where("i.{$itemBudgetIdColumn}", '>', 0)
            ->groupBy("i.{$itemBudgetIdColumn}")
            ->selectRaw("`i`.`{$itemBudgetIdColumn}` as budget_manage_id")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(`p`.`{$statusColumn}`, ''))) = 'approved' THEN `i`.`{$amountColumn}` ELSE 0 END) as approved_amount")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(`p`.`{$statusColumn}`, ''))) IN ({$pendingList}) THEN `i`.`{$amountColumn}` ELSE 0 END) as pending_amount")
            ->get();
    }

    /**
     * Items that were not mapped at line level still count via the parent document's budget.
     *
     * @param  array<int, int>  $manageIds
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function queryParentFallbackSums(
        array $manageIds,
        string $parentTable,
        string $itemTable,
        string $parentIdColumn,
        string $statusColumn,
        string $itemBudgetIdColumn,
        string $amountColumn,
        string $parentBudgetIdColumn
    ) {
        $empty = collect();

        if (
            ! Schema::connection('withdraw')->hasTable($parentTable)
            || ! Schema::connection('withdraw')->hasTable($itemTable)
            || ! Schema::connection('withdraw')->hasColumn($parentTable, $parentIdColumn)
            || ! Schema::connection('withdraw')->hasColumn($parentTable, $statusColumn)
            || ! Schema::connection('withdraw')->hasColumn($parentTable, $parentBudgetIdColumn)
            || ! Schema::connection('withdraw')->hasColumn($itemTable, $parentIdColumn)
            || ! Schema::connection('withdraw')->hasColumn($itemTable, $itemBudgetIdColumn)
            || ! Schema::connection('withdraw')->hasColumn($itemTable, $amountColumn)
        ) {
            return $empty;
        }

        $pendingList = "'".implode("','", self::PENDING_STATUSES)."'";

        return DB::connection('withdraw')
            ->table("{$itemTable} as i")
            ->join("{$parentTable} as p", "p.{$parentIdColumn}", '=', "i.{$parentIdColumn}")
            ->where(function ($q) use ($itemBudgetIdColumn) {
                $q->whereNull("i.{$itemBudgetIdColumn}")
                    ->orWhere("i.{$itemBudgetIdColumn}", 0);
            })
            ->whereIn("p.{$parentBudgetIdColumn}", $manageIds)
            ->where("p.{$parentBudgetIdColumn}", '>', 0)
            ->groupBy("p.{$parentBudgetIdColumn}")
            ->selectRaw("`p`.`{$parentBudgetIdColumn}` as budget_manage_id")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(`p`.`{$statusColumn}`, ''))) = 'approved' THEN `i`.`{$amountColumn}` ELSE 0 END) as approved_amount")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(`p`.`{$statusColumn}`, ''))) IN ({$pendingList}) THEN `i`.`{$amountColumn}` ELSE 0 END) as pending_amount")
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function resolveManageIds(int $budgetManageId, string $budgetManageCode): array
    {
        $ids = [];
        if ($budgetManageId > 0) {
            $ids[$budgetManageId] = $budgetManageId;
        }

        $code = trim($budgetManageCode);
        if ($code !== '') {
            $matched = DB::table('budget_manage')
                ->where('budget_manage_code', $code)
                ->pluck('budget_manage_id');
            foreach ($matched as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }

        return array_values($ids);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  iterable<int, object>  $sourceRows
     */
    private function appendItemDetails(array &$rows, iterable $sourceRows): void
    {
        foreach ($sourceRows as $row) {
            $source = trim((string) ($row->source ?? ''));
            $rows[] = [
                'source' => $source,
                'source_label' => $this->typeLabel($source, (string) ($row->source_label ?? ''), (string) ($row->sub_type ?? '')),
                'document_id' => (int) ($row->document_id ?? 0),
                'status' => (string) ($row->status ?? ''),
                'subject' => (string) ($row->subject ?? ''),
                'date_create' => (string) ($row->date_create ?? ''),
                'finance_date' => (string) ($row->finance_date ?? ''),
                'applicant_staff_id' => trim((string) ($row->applicant_staff_id ?? '')),
                'staff_create' => trim((string) ($row->staff_create ?? '')),
                'item_name' => (string) ($row->item_name ?? ''),
                'item_category' => (string) ($row->item_category ?? ''),
                'amount' => (float) ($row->amount ?? 0),
            ];
        }
    }

    /**
     * @param  array<int, int>  $manageIds
     * @param  list<string>  $statuses
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function queryItemDetails(
        array $manageIds,
        array $statuses,
        string $parentTable,
        string $itemTable,
        string $parentIdColumn,
        string $statusColumn,
        string $itemBudgetIdColumn,
        string $amountColumn,
        string $subjectColumn,
        string $dateCreateColumn,
        string $financeDateColumn,
        string $itemNameColumn,
        string $itemCategoryColumn,
        string $source,
        string $sourceLabel,
        string $budgetManageCode = ''
    ) {
        if (! $this->itemTablesReady($parentTable, $itemTable, $parentIdColumn, $statusColumn, $itemBudgetIdColumn, $amountColumn)) {
            return collect();
        }

        $budgetManageCode = trim($budgetManageCode);
        $hasCodeColumn = Schema::connection('withdraw')->hasColumn($itemTable, 'withdraw_item_budget_manage_code');
        if ($manageIds === [] && (! $hasCodeColumn || $budgetManageCode === '')) {
            return collect();
        }

        $query = DB::connection('withdraw')
            ->table("{$itemTable} as i")
            ->join("{$parentTable} as p", "p.{$parentIdColumn}", '=', "i.{$parentIdColumn}")
            ->whereIn("p.{$statusColumn}", $statuses)
            ->where(function ($q) use ($manageIds, $itemBudgetIdColumn, $hasCodeColumn, $budgetManageCode) {
                $hasIdFilter = false;
                if ($manageIds !== []) {
                    $q->where(function ($inner) use ($manageIds, $itemBudgetIdColumn) {
                        $inner->whereIn("i.{$itemBudgetIdColumn}", $manageIds)
                            ->where("i.{$itemBudgetIdColumn}", '>', 0);
                    });
                    $hasIdFilter = true;
                }
                if ($hasCodeColumn && $budgetManageCode !== '') {
                    if ($hasIdFilter) {
                        $q->orWhere('i.withdraw_item_budget_manage_code', $budgetManageCode);
                    } else {
                        $q->where('i.withdraw_item_budget_manage_code', $budgetManageCode);
                    }
                }
            });

        return $this->selectItemDetailColumns(
            $query,
            $parentTable,
            $itemTable,
            $parentIdColumn,
            $statusColumn,
            $amountColumn,
            $subjectColumn,
            $dateCreateColumn,
            $financeDateColumn,
            $itemNameColumn,
            $itemCategoryColumn,
            $source,
            $sourceLabel
        )->get();
    }

    /**
     * @param  array<int, int>  $manageIds
     * @param  list<string>  $statuses
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function queryParentFallbackDetails(
        array $manageIds,
        array $statuses,
        string $parentTable,
        string $itemTable,
        string $parentIdColumn,
        string $statusColumn,
        string $itemBudgetIdColumn,
        string $amountColumn,
        string $parentBudgetIdColumn,
        string $subjectColumn,
        string $dateCreateColumn,
        string $financeDateColumn,
        string $itemNameColumn,
        string $itemCategoryColumn,
        string $source,
        string $sourceLabel
    ) {
        if (
            ! $this->itemTablesReady($parentTable, $itemTable, $parentIdColumn, $statusColumn, $itemBudgetIdColumn, $amountColumn)
            || ! Schema::connection('withdraw')->hasColumn($parentTable, $parentBudgetIdColumn)
        ) {
            return collect();
        }

        $query = DB::connection('withdraw')
            ->table("{$itemTable} as i")
            ->join("{$parentTable} as p", "p.{$parentIdColumn}", '=', "i.{$parentIdColumn}")
            ->where(function ($q) use ($itemBudgetIdColumn) {
                $q->whereNull("i.{$itemBudgetIdColumn}")
                    ->orWhere("i.{$itemBudgetIdColumn}", 0);
            })
            ->whereIn("p.{$parentBudgetIdColumn}", $manageIds)
            ->where("p.{$parentBudgetIdColumn}", '>', 0)
            ->whereIn("p.{$statusColumn}", $statuses);

        return $this->selectItemDetailColumns(
            $query,
            $parentTable,
            $itemTable,
            $parentIdColumn,
            $statusColumn,
            $amountColumn,
            $subjectColumn,
            $dateCreateColumn,
            $financeDateColumn,
            $itemNameColumn,
            $itemCategoryColumn,
            $source,
            $sourceLabel
        )->get();
    }

    private function itemTablesReady(
        string $parentTable,
        string $itemTable,
        string $parentIdColumn,
        string $statusColumn,
        string $itemBudgetIdColumn,
        string $amountColumn
    ): bool {
        return Schema::connection('withdraw')->hasTable($parentTable)
            && Schema::connection('withdraw')->hasTable($itemTable)
            && Schema::connection('withdraw')->hasColumn($parentTable, $parentIdColumn)
            && Schema::connection('withdraw')->hasColumn($parentTable, $statusColumn)
            && Schema::connection('withdraw')->hasColumn($itemTable, $parentIdColumn)
            && Schema::connection('withdraw')->hasColumn($itemTable, $itemBudgetIdColumn)
            && Schema::connection('withdraw')->hasColumn($itemTable, $amountColumn);
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    private function selectItemDetailColumns(
        $query,
        string $parentTable,
        string $itemTable,
        string $parentIdColumn,
        string $statusColumn,
        string $amountColumn,
        string $subjectColumn,
        string $dateCreateColumn,
        string $financeDateColumn,
        string $itemNameColumn,
        string $itemCategoryColumn,
        string $source,
        string $sourceLabel
    ) {
        $escapedSource = str_replace("'", "''", $source);
        $escapedLabel = str_replace("'", "''", $sourceLabel);
        $select = [
            "p.{$parentIdColumn} as document_id",
            "p.{$statusColumn} as status",
            "i.{$amountColumn} as amount",
        ];

        if (Schema::connection('withdraw')->hasColumn($parentTable, 'withdraw_type')) {
            $select[] = 'p.withdraw_type as source';
            $select[] = DB::raw("'' as source_label");
        } else {
            $select[] = DB::raw("'{$escapedSource}' as source");
            $select[] = DB::raw("'{$escapedLabel}' as source_label");
        }

        $select[] = Schema::connection('withdraw')->hasColumn($parentTable, 'withdraw_sub_type')
            ? 'p.withdraw_sub_type as sub_type'
            : DB::raw("'' as sub_type");

        $select[] = Schema::connection('withdraw')->hasColumn($parentTable, $subjectColumn)
            ? "p.{$subjectColumn} as subject"
            : DB::raw("'' as subject");
        $select[] = Schema::connection('withdraw')->hasColumn($parentTable, $dateCreateColumn)
            ? "p.{$dateCreateColumn} as date_create"
            : DB::raw("'' as date_create");
        $select[] = Schema::connection('withdraw')->hasColumn($parentTable, $financeDateColumn)
            ? "p.{$financeDateColumn} as finance_date"
            : DB::raw("'' as finance_date");
        $select[] = Schema::connection('withdraw')->hasColumn($itemTable, $itemNameColumn)
            ? "i.{$itemNameColumn} as item_name"
            : DB::raw("'' as item_name");
        $select[] = Schema::connection('withdraw')->hasColumn($itemTable, $itemCategoryColumn)
            ? "i.{$itemCategoryColumn} as item_category"
            : DB::raw("'' as item_category");

        $applicantColumn = $parentTable.'_applicant_staff_id';
        $select[] = Schema::connection('withdraw')->hasColumn($parentTable, $applicantColumn)
            ? "p.{$applicantColumn} as applicant_staff_id"
            : DB::raw("'' as applicant_staff_id");
        $select[] = Schema::connection('withdraw')->hasColumn($parentTable, 'staff_create')
            ? 'p.staff_create as staff_create'
            : DB::raw("'' as staff_create");

        return $query->select($select);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, string>
     */
    private function documentNumbersFor(array $rows): array
    {
        $numbers = [];
        if ($rows === [] || ! Schema::connection('withdraw')->hasTable('withdraw_document')) {
            return $numbers;
        }

        $idsByType = [];
        foreach ($rows as $row) {
            $source = (string) ($row['source'] ?? '');
            $documentId = (int) ($row['document_id'] ?? 0);
            if ($source === '' || $documentId <= 0) {
                continue;
            }
            $idsByType[$source][$documentId] = $documentId;
        }

        foreach ($idsByType as $type => $ids) {
            $select = ['withdraw_id', 'document_number', 'document_date'];
            $hasRunningNo = Schema::connection('withdraw')->hasColumn('withdraw_document', 'running_no');
            if ($hasRunningNo) {
                $select[] = 'running_no';
            }

            $docs = DB::connection('withdraw')
                ->table('withdraw_document')
                ->where('withdraw_document_type', $type)
                ->whereIn('withdraw_id', array_values($ids))
                ->get($select);

            foreach ($docs as $doc) {
                $key = $type.':'.((int) $doc->withdraw_id);
                $prefix = trim((string) ($doc->document_number ?? ''));
                $runningNo = $hasRunningNo ? (int) ($doc->running_no ?? 0) : 0;
                $numbers[$key] = ($prefix !== '' && $runningNo > 0) ? $prefix.$runningNo : $prefix;
                $numbers[$key.':date'] = trim((string) ($doc->document_date ?? ''));
            }
        }

        return $numbers;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, string>
     */
    private function staffNamesFor(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            foreach (['applicant_staff_id', 'staff_create'] as $key) {
                $id = trim((string) ($row[$key] ?? ''));
                if ($id !== '') {
                    $ids[$id] = $id;
                }
            }
        }

        if ($ids === []) {
            return [];
        }

        $names = [];
        $defaultConnection = (string) config('database.default', 'mysql');
        if ($defaultConnection !== 'withdraw') {
            $this->fillStaffNamesFromUsers($names, array_values($ids), $defaultConnection);
        }
        $this->fillStaffNamesFromUsers($names, array_values($ids), 'withdraw');

        return $names;
    }

    /**
     * @param  array<string, string>  $names
     * @param  list<string>  $ids
     */
    private function fillStaffNamesFromUsers(array &$names, array $ids, string $connection): void
    {
        try {
            if (! Schema::connection($connection)->hasTable('users')) {
                return;
            }

            $query = DB::connection($connection)->table('users');
            $hasStaffCode = Schema::connection($connection)->hasColumn('users', 'staff_code');
            $hasUserId = Schema::connection($connection)->hasColumn('users', 'user_id');
            $hasNameTh = Schema::connection($connection)->hasColumn('users', 'name_th');
            $hasName = Schema::connection($connection)->hasColumn('users', 'name');
            $hasNameEn = Schema::connection($connection)->hasColumn('users', 'name_en');

            if (! $hasStaffCode && ! $hasUserId) {
                return;
            }

            $query->where(function ($q) use ($ids, $hasStaffCode, $hasUserId) {
                if ($hasStaffCode) {
                    $q->whereIn('staff_code', $ids);
                }
                if ($hasUserId) {
                    $method = $hasStaffCode ? 'orWhereIn' : 'whereIn';
                    $q->{$method}('user_id', $ids);
                }
            });

            $select = [];
            if ($hasStaffCode) {
                $select[] = 'staff_code';
            }
            if ($hasUserId) {
                $select[] = 'user_id';
            }
            if ($hasNameTh) {
                $select[] = 'name_th';
            }
            if ($hasName) {
                $select[] = 'name';
            }
            if ($hasNameEn) {
                $select[] = 'name_en';
            }

            foreach ($query->get($select) as $row) {
                $name = '';
                if ($hasNameTh) {
                    $name = trim((string) ($row->name_th ?? ''));
                }
                if ($name === '' && $hasName) {
                    $name = trim((string) ($row->name ?? ''));
                }
                if ($name === '' && $hasNameEn) {
                    $name = trim((string) ($row->name_en ?? ''));
                }
                if ($name === '') {
                    continue;
                }

                if ($hasStaffCode) {
                    $staffCode = trim((string) ($row->staff_code ?? ''));
                    if ($staffCode !== '' && ! isset($names[$staffCode])) {
                        $names[$staffCode] = $name;
                    }
                }
                if ($hasUserId) {
                    $userId = trim((string) ($row->user_id ?? ''));
                    if ($userId !== '' && ! isset($names[$userId])) {
                        $names[$userId] = $name;
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning('Failed to resolve withdraw requester names.', [
                'connection' => $connection,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, string>
     */
    private function approvalDatesFor(array $rows): array
    {
        $dates = [];
        $idsBySource = [];
        foreach ($rows as $row) {
            $source = (string) ($row['source'] ?? '');
            $id = (int) ($row['document_id'] ?? 0);
            if ($source === '' || $id <= 0) {
                continue;
            }
            $idsBySource[$source][$id] = $id;
        }

        foreach ($idsBySource as $source => $ids) {
            $cfg = [
                'table' => 'withdraw_approval_step',
                'id' => 'withdraw_id',
                'at' => 'withdraw_approval_approved_at',
                'status' => 'withdraw_approval_status',
                'code' => 'withdraw_approval_step_code',
                'order' => 'withdraw_approval_step_order',
            ];

            try {
                $schema = Schema::connection('withdraw');
                if (
                    ! $schema->hasTable($cfg['table'])
                    || ! $schema->hasColumn($cfg['table'], $cfg['id'])
                    || ! $schema->hasColumn($cfg['table'], $cfg['at'])
                    || ! $schema->hasColumn($cfg['table'], $cfg['status'])
                ) {
                    continue;
                }

                $select = [$cfg['id'], $cfg['at'], $cfg['status']];
                $hasCode = $schema->hasColumn($cfg['table'], $cfg['code']);
                $hasOrder = $schema->hasColumn($cfg['table'], $cfg['order']);
                if ($hasCode) {
                    $select[] = $cfg['code'];
                }
                if ($hasOrder) {
                    $select[] = $cfg['order'];
                }

                $steps = DB::connection('withdraw')
                    ->table($cfg['table'])
                    ->whereIn($cfg['id'], array_values($ids))
                    ->get($select);

                $grouped = [];
                foreach ($steps as $step) {
                    $grouped[(int) $step->{$cfg['id']}][] = $step;
                }

                foreach ($grouped as $docId => $docSteps) {
                    $executive = [];
                    $any = [];
                    foreach ($docSteps as $step) {
                        if (strtolower(trim((string) ($step->{$cfg['status']} ?? ''))) !== 'approved') {
                            continue;
                        }
                        $at = trim((string) ($step->{$cfg['at']} ?? ''));
                        if ($at === '') {
                            continue;
                        }
                        $order = $hasOrder ? (int) ($step->{$cfg['order']} ?? 0) : 0;
                        $entry = ['at' => $at, 'order' => $order];
                        $any[] = $entry;
                        $code = $hasCode ? trim((string) ($step->{$cfg['code']} ?? '')) : '';
                        if (in_array($code, self::LAST_APPROVER_STEP_CODES, true)) {
                            $executive[] = $entry;
                        }
                    }

                    $pool = $executive !== [] ? $executive : $any;
                    if ($pool === []) {
                        continue;
                    }
                    usort($pool, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);
                    $dates[$source.':'.$docId] = $pool[array_key_last($pool)]['at'];
                }
            } catch (Throwable $e) {
                Log::warning('Failed to load withdraw approval dates.', [
                    'source' => $source,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $dates;
    }

    private function formatDisplayDate(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        $year = 0;
        $month = 0;
        $day = 0;

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
        } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $raw, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($year < 100) {
                $year += 2500;
            }
        } else {
            return $raw;
        }

        $thaiMonths = [
            1 => 'ม.ค.',
            2 => 'ก.พ.',
            3 => 'มี.ค.',
            4 => 'เม.ย.',
            5 => 'พ.ค.',
            6 => 'มิ.ย.',
            7 => 'ก.ค.',
            8 => 'ส.ค.',
            9 => 'ก.ย.',
            10 => 'ต.ค.',
            11 => 'พ.ย.',
            12 => 'ธ.ค.',
        ];

        if ($day < 1 || $month < 1 || $month > 12 || ! isset($thaiMonths[$month])) {
            return $raw;
        }

        $buddhistYear = $year >= 2400 ? $year : $year + 543;

        return $day.' '.$thaiMonths[$month].' '.$buddhistYear;
    }

    private function statusLabel(string $status): string
    {
        $map = [
            'saved' => 'ร่าง',
            'cancelled' => 'ยกเลิกรายการ',
            'pending_primary_review' => 'รอตรวจสอบ/อนุมัติ',
            'processing' => 'รอตรวจสอบ/อนุมัติ',
            'pending_additional_review' => 'รอตรวจสอบ/อนุมัติ',
            'pending_finance_review' => 'รอการเงินตรวจสอบเอกสาร',
            'pending_revision' => 'รอแก้ไข',
            'pending_approval' => 'รออนุมัติ',
            'approved' => 'อนุมัติ',
        ];

        $normalized = strtolower(trim($status));

        return $map[$normalized] ?? ($status !== '' ? $status : '-');
    }

    private function typeLabel(string $type, string $fallback = '', string $subType = ''): string
    {
        if (strtolower(trim($type)) === 'salary' && strtolower(trim($subType)) === 'travel_leave') {
            return 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน';
        }

        $map = [
            'general' => 'เบิกทั่วไป',
            'salary' => 'เบิกเงินเดือน',
        ];
        $normalized = strtolower(trim($type));
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        return $fallback !== '' ? $fallback : ($type !== '' ? $type : '-');
    }

    /**
     * @return array{approved_by_code: array<string, float>, pending_by_code: array<string, float>, approved_by_id: array<int, float>, pending_by_id: array<int, float>}
     */
    private function emptyTotals(): array
    {
        return [
            'approved_by_code' => [],
            'pending_by_code' => [],
            'approved_by_id' => [],
            'pending_by_id' => [],
        ];
    }
}
