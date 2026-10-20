<?php

namespace App\Http\Controllers;

use App\Services\ProjectPbmService;
use App\Support\WithdrawDisbursementTotals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $yearOptions = $this->fiscalYearOptions();
        $currentYear = $this->currentFiscalYear($yearOptions);
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

        $managesQuery = DB::table('budget_manage as bm')
            ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
            ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
            ->leftJoin('budget_set_category_budget as bcb', 'bm.budget_category_budget_code', '=', 'bcb.budget_category_budget_code')
            ->select([
                'bm.budget_manage_id',
                'bm.budget_manage_code',
                'bm.budget_source_code',
                'bm.budget_manage_amount',
                'bm.budget_manage_balance',
                'bm.budget_manage_year',
                'bm.budget_plan_code',
                'bm.budget_fund_code',
                'bm.budget_category_budget_code',
                'bp.budget_plan_name',
                'bf.budget_fund_name',
                'bcb.budget_category_budget_name',
            ]);

        if ($selectedYear !== '') {
            $managesQuery->where('bm.budget_manage_year', $selectedYear);
        }

        $manages = $managesQuery->get();

        $withdrawService = app(WithdrawDisbursementTotals::class);
        $withdrawGrouped = $withdrawService->forManageRows($manages);
        $planUsedById = $this->planUsedByManageId($manages);

        $makeBucket = static fn (): array => [
            'balance' => 0.0,
            'wait' => 0.0,
            'withdraw' => 0.0,
            'amount' => 0.0,
            'remain' => 0.0,
            'plan_used' => 0.0,
        ];

        $summary = [
            'total' => $makeBucket(),
            'income' => $makeBucket(),
            'gov' => $makeBucket(),
            'external' => $makeBucket(),
        ];

        $byYear = [];
        $byPlan = [];
        $byCategory = [];
        $byFund = [];

        $counts = [
            'by_source' => ['income' => 0, 'gov' => 0, 'external' => 0, 'other' => 0],
            'with_withdraw' => 0,
            'with_wait' => 0,
            'with_plan' => 0,
            'no_activity' => 0,
            'fully_disbursed' => 0,
        ];

        foreach ($manages as $m) {
            $id = (int) ($m->budget_manage_id ?? 0);
            $src = (int) ($m->budget_source_code ?? 0);
            $balance = (float) ($m->budget_manage_balance ?? 0);
            $amount = (float) ($m->budget_manage_amount ?? 0);
            // Use per-id totals so shared budget codes are not double-counted in aggregates.
            $withdraw = (float) ($withdrawGrouped['approved_by_id'][$id] ?? 0);
            $wait = (float) ($withdrawGrouped['pending_by_id'][$id] ?? 0);
            $remain = $balance - $withdraw;
            $planUsed = (float) ($planUsedById[$id] ?? 0);

            $this->addSummaryBucket($summary['total'], $balance, $wait, $withdraw, $amount, $remain, $planUsed);

            $key = match ($src) {
                1 => 'income',
                2 => 'gov',
                3 => 'external',
                default => null,
            };

            if ($key !== null) {
                $this->addSummaryBucket($summary[$key], $balance, $wait, $withdraw, $amount, $remain, $planUsed);
                $counts['by_source'][$key]++;
            } else {
                $counts['by_source']['other']++;
            }

            if ($withdraw > 0) {
                $counts['with_withdraw']++;
            }
            if ($wait > 0) {
                $counts['with_wait']++;
            }
            if ($planUsed > 0) {
                $counts['with_plan']++;
            }
            if ($withdraw <= 0 && $wait <= 0 && $planUsed <= 0) {
                $counts['no_activity']++;
            }
            if ($balance > 0 && $withdraw >= $balance) {
                $counts['fully_disbursed']++;
            }

            $yearLabel = trim((string) ($m->budget_manage_year ?? ''));
            if ($yearLabel === '') {
                $yearLabel = 'ไม่ระบุปี';
            }
            $this->addNamedBucket($byYear, $yearLabel, $balance, $wait, $withdraw, $amount, $remain, $planUsed);

            $planLabel = trim((string) ($m->budget_plan_name ?? ''));
            if ($planLabel === '') {
                $planLabel = 'ไม่ระบุแผน';
            }
            $this->addNamedBucket($byPlan, $planLabel, $balance, $wait, $withdraw, $amount, $remain, $planUsed);

            $categoryLabel = trim((string) ($m->budget_category_budget_name ?? ''));
            if ($categoryLabel === '') {
                $categoryLabel = 'ไม่ระบุหมวด';
            }
            $this->addNamedBucket($byCategory, $categoryLabel, $balance, $wait, $withdraw, $amount, $remain, $planUsed);

            $fundLabel = trim((string) ($m->budget_fund_name ?? ''));
            if ($fundLabel === '') {
                $fundLabel = 'ไม่ระบุกองทุน';
            }
            $this->addNamedBucket($byFund, $fundLabel, $balance, $wait, $withdraw, $amount, $remain, $planUsed);
        }

        $withdrawDocStats = $this->withdrawDocumentStats($manages);

        $pctDisbursed = static function (array $bucket): int {
            $budget = (float) ($bucket['amount'] ?? 0);
            $withdraw = (float) ($bucket['withdraw'] ?? 0);
            if ($budget <= 0) {
                return 0;
            }

            return (int) max(0, min(100, round(($withdraw / $budget) * 100)));
        };

        $chart = [
            'total' => $pctDisbursed($summary['total']),
            'income' => $pctDisbursed($summary['income']),
            'gov' => $pctDisbursed($summary['gov']),
            'external' => $pctDisbursed($summary['external']),
        ];

        $sourceCompare = [
            'labels' => ['เงินรายได้', 'เงินแผ่นดิน', 'เงินนอกงบประมาณ'],
            'balance' => [
                round($summary['income']['balance'], 2),
                round($summary['gov']['balance'], 2),
                round($summary['external']['balance'], 2),
            ],
            'withdraw' => [
                round($summary['income']['withdraw'], 2),
                round($summary['gov']['withdraw'], 2),
                round($summary['external']['withdraw'], 2),
            ],
            'wait' => [
                round($summary['income']['wait'], 2),
                round($summary['gov']['wait'], 2),
                round($summary['external']['wait'], 2),
            ],
            'remain' => [
                round($summary['income']['remain'], 2),
                round($summary['gov']['remain'], 2),
                round($summary['external']['remain'], 2),
            ],
        ];

        $statusMix = [
            'labels' => ['เบิกจ่ายแล้ว', 'รอเบิกจ่าย', 'คงเหลือ'],
            'values' => [
                round(max(0, $summary['total']['withdraw']), 2),
                round(max(0, $summary['total']['wait']), 2),
                round(max(0, $summary['total']['remain']), 2),
            ],
        ];

        $charts = [
            'by_year' => $this->namedBucketsToChart($byYear, null, 'label'),
            'by_plan' => $this->namedBucketsToChart($byPlan, 8),
            'by_category' => $this->namedBucketsToChart($byCategory, 8),
            'by_fund' => $this->namedBucketsToChart($byFund, 8),
            'source_compare' => $sourceCompare,
            'status_mix' => $statusMix,
            'monthly_withdraw' => $this->monthlyWithdrawTrend($manages),
        ];

        $totalBalance = (float) $summary['total']['balance'];
        $totalWithdraw = (float) $summary['total']['withdraw'];
        $totalWait = (float) $summary['total']['wait'];
        $totalRemain = (float) $summary['total']['remain'];
        $totalPlan = (float) $summary['total']['plan_used'];
        $pctOfBalance = $totalBalance > 0
            ? (int) max(0, min(100, round(($totalWithdraw / $totalBalance) * 100)))
            : 0;

        return view('dashboard.index', [
            'summary' => $summary,
            'chart' => $chart,
            'charts' => $charts,
            'yearOptions' => $yearOptions,
            'selectedYear' => $selectedYear,
            'currentYear' => $currentYear,
            'kpis' => [
                'balance' => $totalBalance,
                'withdraw' => $totalWithdraw,
                'wait' => $totalWait,
                'remain' => $totalRemain,
                'plan_used' => $totalPlan,
                'pct_of_amount' => $chart['total'],
                'pct_of_balance' => $pctOfBalance,
            ],
            'stats' => [
                'manage_count' => $manages->count(),
                'by_source' => $counts['by_source'],
                'with_withdraw' => $counts['with_withdraw'],
                'with_wait' => $counts['with_wait'],
                'with_plan' => $counts['with_plan'],
                'no_activity' => $counts['no_activity'],
                'fully_disbursed' => $counts['fully_disbursed'],
                'withdraw_docs_approved' => $withdrawDocStats['approved'],
                'withdraw_docs_pending' => $withdrawDocStats['pending'],
                'withdraw_docs_total' => $withdrawDocStats['total'],
                'plan_coverage_pct' => $manages->count() > 0
                    ? (int) round(($counts['with_plan'] / $manages->count()) * 100)
                    : 0,
                'disburse_coverage_pct' => $manages->count() > 0
                    ? (int) round(($counts['with_withdraw'] / $manages->count()) * 100)
                    : 0,
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $manages
     * @return array{approved: int, pending: int, total: int}
     */
    private function withdrawDocumentStats($manages): array
    {
        $empty = ['approved' => 0, 'pending' => 0, 'total' => 0];
        $ids = $manages
            ->pluck('budget_manage_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return $empty;
        }

        try {
            if (
                ! Schema::connection('withdraw')->hasTable('withdraw')
                || ! Schema::connection('withdraw')->hasTable('withdraw_item')
            ) {
                return $empty;
            }

            $pendingStatuses = [
                'pending_primary_review',
                'processing',
                'pending_additional_review',
                'pending_finance_review',
                'pending_revision',
                'pending_approval',
            ];
            $activeStatuses = array_merge(['approved'], $pendingStatuses);

            $docIds = [];

            $fromItems = DB::connection('withdraw')
                ->table('withdraw_item as i')
                ->join('withdraw as p', 'p.withdraw_id', '=', 'i.withdraw_id')
                ->whereIn('i.withdraw_item_budget_manage_id', $ids)
                ->where('i.withdraw_item_budget_manage_id', '>', 0)
                ->whereIn('p.withdraw_status', $activeStatuses)
                ->distinct()
                ->pluck('p.withdraw_id');

            foreach ($fromItems as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $docIds[$id] = $id;
                }
            }

            if (Schema::connection('withdraw')->hasColumn('withdraw', 'withdraw_budget_manage_id')) {
                $fromParent = DB::connection('withdraw')
                    ->table('withdraw as p')
                    ->leftJoin('withdraw_item as i', 'i.withdraw_id', '=', 'p.withdraw_id')
                    ->whereIn('p.withdraw_budget_manage_id', $ids)
                    ->where('p.withdraw_budget_manage_id', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('i.withdraw_item_budget_manage_id')
                            ->orWhere('i.withdraw_item_budget_manage_id', 0);
                    })
                    ->whereIn('p.withdraw_status', $activeStatuses)
                    ->distinct()
                    ->pluck('p.withdraw_id');

                foreach ($fromParent as $id) {
                    $id = (int) $id;
                    if ($id > 0) {
                        $docIds[$id] = $id;
                    }
                }
            }

            if ($docIds === []) {
                return $empty;
            }

            $rows = DB::connection('withdraw')
                ->table('withdraw')
                ->whereIn('withdraw_id', array_values($docIds))
                ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(withdraw_status, ''))) = 'approved' THEN 1 ELSE 0 END) as approved_count")
                ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(withdraw_status, ''))) IN ('".implode("','", $pendingStatuses)."') THEN 1 ELSE 0 END) as pending_count")
                ->first();

            $approved = (int) ($rows->approved_count ?? 0);
            $pending = (int) ($rows->pending_count ?? 0);

            return [
                'approved' => $approved,
                'pending' => $pending,
                'total' => $approved + $pending,
            ];
        } catch (Throwable $e) {
            Log::warning('Failed to load withdraw document stats for dashboard.', [
                'message' => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    /**
     * Current Buddhist fiscal year label, preferring a year that exists in options.
     *
     * @param  list<string>  $yearOptions
     */
    private function currentFiscalYear(array $yearOptions): string
    {
        $today = now();
        $fiscalYear = (int) $today->format('Y') + 543;
        if ((int) $today->format('n') >= 10) {
            $fiscalYear++;
        }
        $buddhist = (string) $fiscalYear;

        if (in_array($buddhist, $yearOptions, true)) {
            return $buddhist;
        }

        return $yearOptions[0] ?? $buddhist;
    }

    /**
     * Fiscal years available for the dashboard filter (newest first).
     *
     * @return list<string>
     */
    private function fiscalYearOptions(): array
    {
        $fromSet = DB::table('budget_set_year')
            ->where('budget_year_status', 1)
            ->orderByDesc('budget_year_name')
            ->pluck('budget_year_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->values()
            ->all();

        $fromData = DB::table('budget_manage')
            ->whereNotNull('budget_manage_year')
            ->where('budget_manage_year', '!=', '')
            ->distinct()
            ->orderByDesc('budget_manage_year')
            ->pluck('budget_manage_year')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->values()
            ->all();

        $merged = [];
        foreach (array_merge($fromSet, $fromData) as $year) {
            $merged[$year] = $year;
        }

        $years = array_values($merged);
        rsort($years, SORT_STRING);

        return $years;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $manages
     * @return array<int, float>
     */
    private function planUsedByManageId($manages): array
    {
        $ids = $manages
            ->pluck('budget_manage_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $planUsedById = [];
        if ($ids !== []) {
            $planUsedById = DB::table('budget_manage_expenses_sub')
                ->whereIn('budget_manage_id', $ids)
                ->groupBy('budget_manage_id')
                ->selectRaw('budget_manage_id, SUM(budget_expenses_sub_total) as total')
                ->pluck('total', 'budget_manage_id')
                ->map(fn ($total) => (float) $total)
                ->all();
        }

        $pbmPlanByCode = app(ProjectPbmService::class)->planTotalsIndexedByBudgetCode();
        if ($pbmPlanByCode !== []) {
            foreach ($manages as $row) {
                $manageId = (int) ($row->budget_manage_id ?? 0);
                $code = trim((string) ($row->budget_manage_code ?? ''));
                if ($manageId <= 0 || $code === '') {
                    continue;
                }
                $pbmTotal = (float) ($pbmPlanByCode[$code] ?? 0);
                if ($pbmTotal <= 0) {
                    continue;
                }
                $planUsedById[$manageId] = round(((float) ($planUsedById[$manageId] ?? 0)) + $pbmTotal, 2);
            }
        }

        return $planUsedById;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $manages
     * @return array{labels: list<string>, approved: list<float>, pending: list<float>}
     */
    private function monthlyWithdrawTrend($manages): array
    {
        $empty = [
            'labels' => [],
            'approved' => [],
            'pending' => [],
        ];

        $ids = $manages
            ->pluck('budget_manage_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return $empty;
        }

        $months = [];
        $now = now();
        for ($i = 11; $i >= 0; $i--) {
            $dt = $now->copy()->subMonths($i);
            $key = $dt->format('Y-m');
            $months[$key] = [
                'label' => $this->thaiMonthLabel((int) $dt->format('n'), (int) $dt->format('Y')),
                'approved' => 0.0,
                'pending' => 0.0,
            ];
        }

        try {
            if (
                ! Schema::connection('withdraw')->hasTable('withdraw')
                || ! Schema::connection('withdraw')->hasTable('withdraw_item')
            ) {
                return $this->monthlyTrendPayload($months);
            }

            $pendingList = "'".implode("','", [
                'pending_primary_review',
                'processing',
                'pending_additional_review',
                'pending_finance_review',
                'pending_revision',
                'pending_approval',
            ])."'";

            $dateExpr = Schema::connection('withdraw')->hasColumn('withdraw', 'withdraw_date_create')
                ? 'p.withdraw_date_create'
                : (Schema::connection('withdraw')->hasColumn('withdraw', 'created_at')
                    ? 'p.created_at'
                    : null);

            if ($dateExpr === null) {
                return $this->monthlyTrendPayload($months);
            }

            $rows = DB::connection('withdraw')
                ->table('withdraw_item as i')
                ->join('withdraw as p', 'p.withdraw_id', '=', 'i.withdraw_id')
                ->where(function ($q) use ($ids) {
                    $q->whereIn('i.withdraw_item_budget_manage_id', $ids)
                        ->where('i.withdraw_item_budget_manage_id', '>', 0);
                })
                ->whereNotNull($dateExpr)
                ->where($dateExpr, '!=', '')
                ->selectRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') as ym")
                ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(p.withdraw_status, ''))) = 'approved' THEN i.withdraw_item_amount ELSE 0 END) as approved_amount")
                ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(p.withdraw_status, ''))) IN ({$pendingList}) THEN i.withdraw_item_amount ELSE 0 END) as pending_amount")
                ->groupBy('ym')
                ->get();

            foreach ($rows as $row) {
                $ym = (string) ($row->ym ?? '');
                if (! isset($months[$ym])) {
                    continue;
                }
                $months[$ym]['approved'] += (float) ($row->approved_amount ?? 0);
                $months[$ym]['pending'] += (float) ($row->pending_amount ?? 0);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to build monthly withdraw trend for dashboard.', [
                'message' => $e->getMessage(),
            ]);
        }

        return $this->monthlyTrendPayload($months);
    }

    /**
     * @param  array<string, array{label: string, approved: float, pending: float}>  $months
     * @return array{labels: list<string>, approved: list<float>, pending: list<float>}
     */
    private function monthlyTrendPayload(array $months): array
    {
        return [
            'labels' => array_values(array_map(static fn (array $m): string => $m['label'], $months)),
            'approved' => array_values(array_map(static fn (array $m): float => round($m['approved'], 2), $months)),
            'pending' => array_values(array_map(static fn (array $m): float => round($m['pending'], 2), $months)),
        ];
    }

    private function thaiMonthLabel(int $month, int $year): string
    {
        $names = [
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

        $buddhistYear = $year >= 2400 ? $year : $year + 543;
        $shortYear = substr((string) $buddhistYear, -2);

        return ($names[$month] ?? $month).' '.$shortYear;
    }

    /**
     * @param  array<string, array{balance: float, wait: float, withdraw: float, amount: float, remain: float, plan_used: float}>  $buckets
     * @return array{labels: list<string>, balance: list<float>, withdraw: list<float>, wait: list<float>, remain: list<float>, plan_used: list<float>}
     */
    private function namedBucketsToChart(array $buckets, ?int $limit = null, string $sortBy = 'balance'): array
    {
        $rows = [];
        foreach ($buckets as $label => $bucket) {
            $rows[] = [
                'label' => (string) $label,
                'balance' => round((float) $bucket['balance'], 2),
                'withdraw' => round((float) $bucket['withdraw'], 2),
                'wait' => round((float) $bucket['wait'], 2),
                'remain' => round((float) $bucket['remain'], 2),
                'plan_used' => round((float) $bucket['plan_used'], 2),
            ];
        }

        usort($rows, static function (array $a, array $b) use ($sortBy): int {
            if ($sortBy === 'label') {
                return strcmp((string) $a['label'], (string) $b['label']);
            }

            return ($b[$sortBy] ?? 0) <=> ($a[$sortBy] ?? 0);
        });

        if ($limit !== null && count($rows) > $limit) {
            $rows = array_slice($rows, 0, $limit);
        }

        return [
            'labels' => array_column($rows, 'label'),
            'balance' => array_column($rows, 'balance'),
            'withdraw' => array_column($rows, 'withdraw'),
            'wait' => array_column($rows, 'wait'),
            'remain' => array_column($rows, 'remain'),
            'plan_used' => array_column($rows, 'plan_used'),
        ];
    }

    /**
     * @param  array<string, array{balance: float, wait: float, withdraw: float, amount: float, remain: float, plan_used: float}>  $buckets
     */
    private function addNamedBucket(
        array &$buckets,
        string $label,
        float $balance,
        float $wait,
        float $withdraw,
        float $amount,
        float $remain,
        float $planUsed
    ): void {
        if (! isset($buckets[$label])) {
            $buckets[$label] = [
                'balance' => 0.0,
                'wait' => 0.0,
                'withdraw' => 0.0,
                'amount' => 0.0,
                'remain' => 0.0,
                'plan_used' => 0.0,
            ];
        }

        $this->addSummaryBucket($buckets[$label], $balance, $wait, $withdraw, $amount, $remain, $planUsed);
    }

    private function addSummaryBucket(
        array &$bucket,
        float $balance,
        float $wait,
        float $withdraw,
        float $amount,
        float $remain,
        float $planUsed = 0.0
    ): void {
        $bucket['balance'] += $balance;
        $bucket['wait'] += $wait;
        $bucket['withdraw'] += $withdraw;
        $bucket['amount'] += $amount;
        $bucket['remain'] += $remain;
        $bucket['plan_used'] = ($bucket['plan_used'] ?? 0) + $planUsed;
    }
}
