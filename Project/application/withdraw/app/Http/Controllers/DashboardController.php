<?php

namespace App\Http\Controllers;

use App\Support\WithdrawSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** @var array<int, string> */
    private const THAI_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    /** @var array<string, string> */
    private const SALARY_SUBTYPE_LABELS = [
        'salary_civil_servant' => 'เงินเดือนข้าราชการ',
        'salary_university_staff' => 'เงินเดือนพนักงานมหาวิทยาลัย',
        'position_allowance' => 'เงินประจำตำแหน่ง',
        'pts' => 'เงิน พ.ต.ส.',
        'salary' => 'เงินเดือนข้าราชการ',
    ];

    /** @var array<string, string> */
    private const TYPE_ACCENTS = [
        'total' => '#2563eb',
        'general' => '#3b82f6',
        'project' => '#059669',
        'salary' => '#7c3aed',
    ];

    public function index(Request $request): View
    {
        $yearOptions = $this->fiscalYearOptions();
        $currentYear = (string) $this->currentFiscalYear();
        if (! in_array($currentYear, $yearOptions, true)) {
            array_unshift($yearOptions, $currentYear);
        }

        $rawYear = $request->query('year');
        if ($rawYear === null) {
            $selectedYear = $currentYear;
        } else {
            $selectedYear = trim((string) $rawYear);
            if ($selectedYear === 'all') {
                $selectedYear = '';
            } elseif ($selectedYear !== '' && ! in_array($selectedYear, $yearOptions, true)) {
                $selectedYear = $currentYear;
            }
        }

        return view('dashboard.index', [
            'title' => 'Dashboard',
            'selectedYear' => $selectedYear,
            'currentYear' => $currentYear,
            'yearOptions' => $yearOptions,
            'report' => $this->buildExecutiveReport($selectedYear === '' ? null : (int) $selectedYear),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExecutiveReport(?int $fiscalYear): array
    {
        $displayYear = $fiscalYear ?? $this->currentFiscalYear();
        $empty = $this->emptyReport($displayYear, $fiscalYear === null);
        if (! Schema::hasTable('withdraw')) {
            return $empty;
        }

        $query = DB::table('withdraw');
        if ($fiscalYear !== null) {
            $query->where('withdraw_fiscal_year', $fiscalYear);
        }

        $rows = $query->get([
            'withdraw_id',
            'withdraw_type',
            'withdraw_sub_type',
            'withdraw_status',
            'withdraw_total_amount',
            'withdraw_date_create',
            'withdraw_fiscal_year',
        ]);

        $typeKeys = [WithdrawSchema::TYPE_GENERAL, WithdrawSchema::TYPE_PROJECT, WithdrawSchema::TYPE_SALARY];
        $typeAmounts = array_fill_keys($typeKeys, 0.0);
        $typeCounts = array_fill_keys($typeKeys, 0);
        $typeStatus = [];
        foreach ($typeKeys as $type) {
            $typeStatus[$type] = [
                'requested' => 0.0,
                'approved' => 0.0,
                'approval' => 0.0,
                'review' => 0.0,
                'draft' => 0.0,
            ];
        }
        $statusBuckets = [
            'draft' => ['label' => 'ร่าง', 'count' => 0, 'amount' => 0.0],
            'review' => ['label' => 'ระหว่างตรวจสอบ', 'count' => 0, 'amount' => 0.0],
            'approval' => ['label' => 'รออนุมัติ', 'count' => 0, 'amount' => 0.0],
            'approved' => ['label' => 'อนุมัติแล้ว', 'count' => 0, 'amount' => 0.0],
            'cancelled' => ['label' => 'ยกเลิก', 'count' => 0, 'amount' => 0.0],
        ];
        $monthKeys = $fiscalYear !== null
            ? $this->fiscalMonthKeys($fiscalYear)
            : $this->fiscalMonthLabels();
        $monthly = [];
        foreach (array_keys($monthKeys) as $monthKey) {
            $monthly[$monthKey] = array_fill_keys($typeKeys, 0.0);
        }
        $salarySubtypeAmounts = [];
        $byYear = [];
        $activeWithdraws = [];

        foreach ($rows as $row) {
            $type = trim((string) ($row->withdraw_type ?? ''));
            if (! in_array($type, $typeKeys, true)) {
                $type = WithdrawSchema::TYPE_GENERAL;
            }
            $amount = (float) ($row->withdraw_total_amount ?? 0);
            $bucket = $this->statusBucket(trim((string) ($row->withdraw_status ?? 'saved')));
            $statusBuckets[$bucket]['count']++;
            $statusBuckets[$bucket]['amount'] += $amount;

            $yearLabel = trim((string) ($row->withdraw_fiscal_year ?? ''));
            if ($yearLabel === '') {
                $yearLabel = 'ไม่ระบุปี';
            }
            if (! isset($byYear[$yearLabel])) {
                $byYear[$yearLabel] = ['requested' => 0.0, 'approved' => 0.0];
            }
            if ($bucket !== 'cancelled') {
                $byYear[$yearLabel]['requested'] += $amount;
            }
            if ($bucket === 'approved') {
                $byYear[$yearLabel]['approved'] += $amount;
            }

            if ($bucket === 'cancelled') {
                continue;
            }

            $withdrawId = (int) ($row->withdraw_id ?? 0);
            if ($withdrawId > 0) {
                $activeWithdraws[$withdrawId] = [
                    'type' => $type,
                    'sub_type' => trim((string) ($row->withdraw_sub_type ?? '')),
                    'amount' => $amount,
                ];
            }

            $typeAmounts[$type] += $amount;
            $typeCounts[$type]++;
            $typeStatus[$type]['requested'] += $amount;
            if (isset($typeStatus[$type][$bucket])) {
                $typeStatus[$type][$bucket] += $amount;
            }

            $monthKey = $this->monthKeyForRow($row->withdraw_date_create ?? '', $fiscalYear, $monthKeys);
            if ($monthKey !== '' && array_key_exists($monthKey, $monthly)) {
                $monthly[$monthKey][$type] += $amount;
            }

            if ($type === WithdrawSchema::TYPE_SALARY && $bucket === 'approved') {
                $subType = trim((string) ($row->withdraw_sub_type ?? ''));
                if ($subType === '' || $subType === 'salary') {
                    $subType = 'salary_civil_servant';
                }
                $salarySubtypeAmounts[$subType] = ($salarySubtypeAmounts[$subType] ?? 0) + $amount;
            }
        }

        $activeAmount = array_sum($typeAmounts);
        $approvedAmount = $statusBuckets['approved']['amount'];
        $salaryApproved = $typeStatus[WithdrawSchema::TYPE_SALARY]['approved'];
        $salaryShare = $approvedAmount > 0
            ? round(($salaryApproved / $approvedAmount) * 100, 1)
            : 0.0;

        uksort($byYear, static function (string $a, string $b): int {
            return strnatcmp($a, $b);
        });

        $donuts = [[
            'id' => 'chartTotal',
            'label' => 'เบิกจ่ายรวม',
            'menu_key' => null,
            'requested' => round($activeAmount, 2),
            'approved' => round($approvedAmount, 2),
            'percent' => $this->approvedPercent($approvedAmount, $activeAmount),
            'accent' => self::TYPE_ACCENTS['total'],
            'tone' => 'blue',
            'count' => array_sum($typeCounts),
        ]];
        $tones = [
            WithdrawSchema::TYPE_GENERAL => 'blue',
            WithdrawSchema::TYPE_PROJECT => 'emerald',
            WithdrawSchema::TYPE_SALARY => 'violet',
        ];
        $menus = [
            WithdrawSchema::TYPE_GENERAL => 'withdraw-general',
            WithdrawSchema::TYPE_PROJECT => 'withdraw-project',
            WithdrawSchema::TYPE_SALARY => 'withdraw-salary',
        ];
        foreach ($typeKeys as $type) {
            $donuts[] = [
                'id' => 'chart'.ucfirst($type),
                'label' => WithdrawSchema::TYPE_LABELS[$type] ?? $type,
                'menu_key' => $menus[$type],
                'requested' => round($typeStatus[$type]['requested'], 2),
                'approved' => round($typeStatus[$type]['approved'], 2),
                'percent' => $this->approvedPercent($typeStatus[$type]['approved'], $typeStatus[$type]['requested']),
                'accent' => self::TYPE_ACCENTS[$type],
                'tone' => $tones[$type],
                'count' => $typeCounts[$type],
            ];
        }

        return [
            'fiscal_year' => $displayYear,
            'all_years' => $fiscalYear === null,
            'has_data' => $rows->isNotEmpty(),
            'kpis' => [
                'active_count' => array_sum($typeCounts),
                'active_amount' => $activeAmount,
                'approved_count' => $statusBuckets['approved']['count'],
                'approved_amount' => $approvedAmount,
                'approved_pct' => $activeAmount > 0 ? round(($approvedAmount / $activeAmount) * 100, 1) : 0,
                'waiting_count' => $statusBuckets['approval']['count'],
                'waiting_amount' => $statusBuckets['approval']['amount'],
                'review_count' => $statusBuckets['review']['count'],
                'review_amount' => $statusBuckets['review']['amount'],
            ],
            'salary_share' => $salaryShare,
            'donuts' => $donuts,
            'type_labels' => array_map(
                static fn (string $type): string => WithdrawSchema::TYPE_LABELS[$type] ?? $type,
                $typeKeys
            ),
            'type_amounts' => array_values($typeAmounts),
            'type_counts' => array_values($typeCounts),
            'type_compare' => [
                'requested' => array_map(static fn (string $type): float => round($typeStatus[$type]['requested'], 2), $typeKeys),
                'approved' => array_map(static fn (string $type): float => round($typeStatus[$type]['approved'], 2), $typeKeys),
                'waiting' => array_map(static fn (string $type): float => round($typeStatus[$type]['approval'], 2), $typeKeys),
                'review' => array_map(static fn (string $type): float => round($typeStatus[$type]['review'], 2), $typeKeys),
            ],
            'month_labels' => array_values($monthKeys),
            'monthly' => [
                'general' => array_values(array_map(static fn (array $row): float => round($row[WithdrawSchema::TYPE_GENERAL], 2), $monthly)),
                'project' => array_values(array_map(static fn (array $row): float => round($row[WithdrawSchema::TYPE_PROJECT], 2), $monthly)),
                'salary' => array_values(array_map(static fn (array $row): float => round($row[WithdrawSchema::TYPE_SALARY], 2), $monthly)),
            ],
            'status_labels' => array_column($statusBuckets, 'label'),
            'status_counts' => array_column($statusBuckets, 'count'),
            'status_amounts' => array_values(array_map(static fn (array $bucket): float => round($bucket['amount'], 2), $statusBuckets)),
            'salary_subtype_labels' => array_map(
                fn (string $key): string => self::SALARY_SUBTYPE_LABELS[$key] ?? $key,
                array_keys($salarySubtypeAmounts)
            ),
            'salary_subtype_amounts' => array_values(array_map(static fn (float $amount): float => round($amount, 2), $salarySubtypeAmounts)),
            'category_pay' => $this->buildCategoryPayChart(array_keys($activeWithdraws)),
            'departments' => $this->buildDepartmentChart($activeWithdraws),
            'by_year' => [
                'labels' => array_map(static fn (string $year): string => 'ปี '.$year, array_keys($byYear)),
                'requested' => array_values(array_map(static fn (array $row): float => round($row['requested'], 2), $byYear)),
                'approved' => array_values(array_map(static fn (array $row): float => round($row['approved'], 2), $byYear)),
            ],
        ];
    }

    /**
     * @param  list<int>  $withdrawIds
     * @return array{labels: list<string>, amounts: list<float>, counts: list<int>}
     */
    private function buildCategoryPayChart(array $withdrawIds): array
    {
        if ($withdrawIds === [] || ! Schema::hasTable('withdraw_item')) {
            return $this->emptyNamedSeries();
        }

        $items = DB::table('withdraw_item')
            ->whereIn('withdraw_id', $withdrawIds)
            ->get([
                'withdraw_item_category',
                'withdraw_item_amount',
                'withdraw_item_expense_chart_id',
            ]);

        $expensePayById = [];
        $expenseIds = [];
        foreach ($items as $item) {
            $expenseId = (int) ($item->withdraw_item_expense_chart_id ?? 0);
            if ($expenseId > 0) {
                $expenseIds[$expenseId] = $expenseId;
            }
        }
        if ($expenseIds !== [] && Schema::hasTable('withdraw_set_expenses') && Schema::hasColumn('withdraw_set_expenses', 'withdraw_pay_code')) {
            $expensePayById = DB::table('withdraw_set_expenses')
                ->whereIn('withdraw_set_expenses_id', array_values($expenseIds))
                ->pluck('withdraw_pay_code', 'withdraw_set_expenses_id')
                ->map(static fn ($code): string => trim((string) $code))
                ->all();
        }

        $payCodes = [];
        $prepared = [];
        foreach ($items as $item) {
            $payCode = trim((string) ($item->withdraw_item_category ?? ''));
            if ($payCode === '' || ! ctype_digit($payCode)) {
                $expenseId = (int) ($item->withdraw_item_expense_chart_id ?? 0);
                $payCode = trim((string) ($expensePayById[$expenseId] ?? ''));
            }
            if ($payCode !== '' && ctype_digit($payCode)) {
                $payCodes[$payCode] = $payCode;
            }
            $prepared[] = [
                'pay_code' => $payCode,
                'amount' => (float) ($item->withdraw_item_amount ?? 0),
            ];
        }

        $categoryByPay = $this->categoryNameByPayCode(array_values($payCodes));
        $buckets = [];
        foreach ($prepared as $row) {
            $label = trim((string) ($categoryByPay[$row['pay_code']] ?? ''));
            if ($label === '') {
                $label = 'ไม่ระบุหมวด';
            }
            if (! isset($buckets[$label])) {
                $buckets[$label] = ['amount' => 0.0, 'count' => 0];
            }
            $buckets[$label]['amount'] += $row['amount'];
            $buckets[$label]['count']++;
        }

        return $this->topNamedSeries($buckets);
    }

    /**
     * @param  array<int, array{type: string, sub_type: string, amount: float}>  $withdraws
     * @return array{labels: list<string>, amounts: list<float>, counts: list<int>}
     */
    private function buildDepartmentChart(array $withdraws): array
    {
        if ($withdraws === [] || ! Schema::hasTable('withdraw_document') || ! Schema::hasColumn('withdraw_document', 'department_code')) {
            return $this->emptyNamedSeries();
        }

        $documents = [];
        $rows = DB::table('withdraw_document')
            ->whereIn('withdraw_id', array_keys($withdraws))
            ->get(['withdraw_id', 'withdraw_document_type', 'department_code']);
        foreach ($rows as $row) {
            $documents[(int) $row->withdraw_id][] = [
                'type' => trim((string) ($row->withdraw_document_type ?? '')),
                'code' => trim((string) ($row->department_code ?? '')),
            ];
        }

        $codes = [];
        $prepared = [];
        foreach ($withdraws as $withdrawId => $withdraw) {
            $code = $this->departmentCodeForWithdraw(
                $withdraw['type'],
                $withdraw['sub_type'],
                $documents[$withdrawId] ?? []
            );
            if ($code !== '') {
                $codes[$code] = $code;
            }
            $prepared[] = [
                'code' => $code,
                'amount' => (float) $withdraw['amount'],
            ];
        }

        $names = $this->departmentNameByCode(array_values($codes));
        $buckets = [];
        foreach ($prepared as $row) {
            $label = trim((string) ($names[$row['code']] ?? ''));
            if ($label === '') {
                $label = $row['code'] !== '' ? $row['code'] : 'ไม่ระบุส่วนงาน';
            }
            if (! isset($buckets[$label])) {
                $buckets[$label] = ['amount' => 0.0, 'count' => 0];
            }
            $buckets[$label]['amount'] += $row['amount'];
            $buckets[$label]['count']++;
        }

        return $this->topNamedSeries($buckets);
    }

    /**
     * @param  list<array{type: string, code: string}>  $documents
     */
    private function departmentCodeForWithdraw(string $type, string $subType, array $documents): string
    {
        $preferred = match ($type) {
            WithdrawSchema::TYPE_PROJECT => ['project'],
            WithdrawSchema::TYPE_SALARY => $subType === 'pts' ? ['salary_pts', 'salary'] : ['salary', 'salary_pts'],
            default => ['general'],
        };

        foreach ($preferred as $documentType) {
            foreach ($documents as $document) {
                if ($document['type'] === $documentType && $document['code'] !== '') {
                    return $document['code'];
                }
            }
        }

        foreach ($documents as $document) {
            $documentType = $document['type'];
            if ($documentType === '' || str_contains($documentType, 'finance') || str_starts_with($documentType, 'project_procurement')) {
                continue;
            }
            if ($document['code'] !== '') {
                return $document['code'];
            }
        }

        return '';
    }

    /**
     * @param  list<string>  $payCodes
     * @return array<string, string>
     */
    private function categoryNameByPayCode(array $payCodes): array
    {
        $payCodes = array_values(array_filter($payCodes, static fn (string $code): bool => $code !== '' && ctype_digit($code)));
        if ($payCodes === []) {
            return [];
        }

        try {
            if (! Schema::connection('budget')->hasTable('budget_set_pay_list') || ! Schema::connection('budget')->hasTable('budget_set_category_pay')) {
                return [];
            }

            $rows = DB::connection('budget')
                ->table('budget_set_pay_list as p')
                ->leftJoin('budget_set_category_pay as c', 'p.budget_category_pay_code', '=', 'c.budget_category_pay_code')
                ->whereIn('p.budget_pay_code', $payCodes)
                ->get(['p.budget_pay_code', 'c.budget_category_pay_name']);
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row->budget_pay_code ?? ''));
            $name = trim((string) ($row->budget_category_pay_name ?? ''));
            if ($code !== '' && $name !== '') {
                $map[$code] = $name;
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, string>
     */
    private function departmentNameByCode(array $codes): array
    {
        $wanted = [];
        foreach ($codes as $code) {
            $code = trim($code);
            if ($code !== '') {
                $wanted[$code] = $code;
            }
        }
        if ($wanted === []) {
            return [];
        }

        $url = trim((string) config('services.department_api.url', ''));
        if ($url === '') {
            return [];
        }

        try {
            $request = Http::acceptJson()->timeout(8);
            $token = trim((string) config('services.department_api.token', ''));
            if ($token !== '') {
                $request = str_starts_with($token, 'Bearer ')
                    ? $request->withHeaders(['Authorization' => $token])
                    : $request->withToken($token);
            }
            $response = $request->get($url);
            if (! $response->successful()) {
                return [];
            }
            $rows = $response->json('data');
        } catch (\Throwable $e) {
            return [];
        }

        if (! is_array($rows)) {
            return [];
        }

        $names = [];
        foreach ($rows as $item) {
            if (! is_array($item)) {
                continue;
            }
            $unitCode = trim((string) ($item['unit_code'] ?? ''));
            $unitName = trim((string) ($item['agency_name'] ?? $item['unit_name'] ?? ''));
            $jobCode = trim((string) ($item['job_code'] ?? ''));
            $jobName = trim((string) ($item['job_name'] ?? ''));
            $depCode = trim((string) ($item['dep_code'] ?? $item['department_code'] ?? $item['code'] ?? ''));
            $depName = trim((string) ($item['dep_name'] ?? $item['department_name'] ?? $item['name'] ?? ''));

            if ($unitCode !== '' && $unitName !== '' && isset($wanted[$unitCode]) && ! isset($names[$unitCode])) {
                $names[$unitCode] = $unitName;
            }
            if ($jobCode !== '' && $jobName !== '' && isset($wanted[$jobCode]) && ! isset($names[$jobCode])) {
                $names[$jobCode] = $jobName;
            }
            if ($depCode !== '' && $depName !== '' && isset($wanted[$depCode]) && ! isset($names[$depCode])) {
                $names[$depCode] = $depName;
            }
        }

        return $names;
    }

    /**
     * @param  array<string, array{amount: float, count: int}>  $buckets
     * @return array{labels: list<string>, amounts: list<float>, counts: list<int>}
     */
    private function topNamedSeries(array $buckets, int $limit = 8): array
    {
        if ($buckets === []) {
            return $this->emptyNamedSeries();
        }

        uasort($buckets, static fn (array $left, array $right): int => $right['amount'] <=> $left['amount']);
        if (count($buckets) > $limit) {
            $top = array_slice($buckets, 0, $limit, true);
            $restAmount = 0.0;
            $restCount = 0;
            foreach (array_slice($buckets, $limit, null, true) as $row) {
                $restAmount += $row['amount'];
                $restCount += $row['count'];
            }
            if ($restAmount > 0 || $restCount > 0) {
                $top['อื่นๆ'] = ['amount' => $restAmount, 'count' => $restCount];
            }
            $buckets = $top;
        }

        return [
            'labels' => array_keys($buckets),
            'amounts' => array_values(array_map(static fn (array $row): float => round($row['amount'], 2), $buckets)),
            'counts' => array_values(array_map(static fn (array $row): int => $row['count'], $buckets)),
        ];
    }

    /**
     * @return array{labels: list<string>, amounts: list<float>, counts: list<int>}
     */
    private function emptyNamedSeries(): array
    {
        return [
            'labels' => [],
            'amounts' => [],
            'counts' => [],
        ];
    }

    private function approvedPercent(float $approved, float $requested): int
    {
        if ($requested <= 0) {
            return 0;
        }

        return (int) max(0, min(100, round(($approved / $requested) * 100)));
    }

    /**
     * @param  array<string, string>  $monthKeys
     */
    private function monthKeyForRow(mixed $createdAt, ?int $fiscalYear, array $monthKeys): string
    {
        $created = trim((string) $createdAt);
        if (strlen($created) < 7) {
            return '';
        }

        if ($fiscalYear !== null) {
            $key = substr($created, 0, 7);

            return array_key_exists($key, $monthKeys) ? $key : '';
        }

        $month = (int) substr($created, 5, 2);

        return ($month >= 1 && $month <= 12) ? (string) $month : '';
    }

    /**
     * @return list<string>
     */
    private function fiscalYearOptions(): array
    {
        if (! Schema::hasTable('withdraw') || ! Schema::hasColumn('withdraw', 'withdraw_fiscal_year')) {
            return [];
        }

        return DB::table('withdraw')
            ->whereNotNull('withdraw_fiscal_year')
            ->distinct()
            ->orderByDesc('withdraw_fiscal_year')
            ->pluck('withdraw_fiscal_year')
            ->map(static fn ($year): string => trim((string) $year))
            ->filter(static fn (string $year): bool => $year !== '')
            ->values()
            ->all();
    }

    private function statusBucket(string $status): string
    {
        return match ($status) {
            'approved' => 'approved',
            'cancelled' => 'cancelled',
            'pending_approval' => 'approval',
            'saved', '' => 'draft',
            default => 'review',
        };
    }

    private function currentFiscalYear(): int
    {
        $now = now();
        $buddhistYear = (int) $now->year + 543;

        return (int) $now->month >= 10 ? $buddhistYear + 1 : $buddhistYear;
    }

    /**
     * @return array<string, string>
     */
    private function fiscalMonthKeys(int $fiscalYear): array
    {
        $startYear = $fiscalYear - 543 - 1;
        $keys = [];
        for ($offset = 0; $offset < 12; $offset++) {
            $month = (($offset + 9) % 12) + 1;
            $year = $startYear + intdiv($offset + 9, 12);
            $key = sprintf('%04d-%02d', $year, $month);
            $keys[$key] = self::THAI_MONTHS[$month - 1];
        }

        return $keys;
    }

    /**
     * @return array<string, string>
     */
    private function fiscalMonthLabels(): array
    {
        $keys = [];
        foreach ([10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9] as $month) {
            $keys[(string) $month] = self::THAI_MONTHS[$month - 1];
        }

        return $keys;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(int $fiscalYear, bool $allYears): array
    {
        $months = array_values($allYears ? $this->fiscalMonthLabels() : $this->fiscalMonthKeys($fiscalYear));
        $zeros = [0, 0, 0];

        return [
            'fiscal_year' => $fiscalYear,
            'all_years' => $allYears,
            'has_data' => false,
            'kpis' => [
                'active_count' => 0,
                'active_amount' => 0,
                'approved_count' => 0,
                'approved_amount' => 0,
                'approved_pct' => 0,
                'waiting_count' => 0,
                'waiting_amount' => 0,
                'review_count' => 0,
                'review_amount' => 0,
            ],
            'salary_share' => 0,
            'donuts' => [],
            'type_labels' => array_values(WithdrawSchema::TYPE_LABELS),
            'type_amounts' => $zeros,
            'type_counts' => $zeros,
            'type_compare' => [
                'requested' => $zeros,
                'approved' => $zeros,
                'waiting' => $zeros,
                'review' => $zeros,
            ],
            'month_labels' => $months,
            'monthly' => [
                'general' => array_fill(0, 12, 0),
                'project' => array_fill(0, 12, 0),
                'salary' => array_fill(0, 12, 0),
            ],
            'status_labels' => ['ร่าง', 'ระหว่างตรวจสอบ', 'รออนุมัติ', 'อนุมัติแล้ว', 'ยกเลิก'],
            'status_counts' => [0, 0, 0, 0, 0],
            'status_amounts' => [0, 0, 0, 0, 0],
            'salary_subtype_labels' => [],
            'salary_subtype_amounts' => [],
            'category_pay' => $this->emptyNamedSeries(),
            'departments' => $this->emptyNamedSeries(),
            'by_year' => [
                'labels' => [],
                'requested' => [],
                'approved' => [],
            ],
        ];
    }
}
