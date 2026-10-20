<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProjectPbmService
{
    /** @var list<string> */
    private const FISCAL_MONTH_KEYS = [
        'mon_10', 'mon_11', 'mon_12',
        'mon_01', 'mon_02', 'mon_03',
        'mon_04', 'mon_05', 'mon_06',
        'mon_07', 'mon_08', 'mon_09',
    ];

    /**
     * แผนการใช้งบจากโครงการ PBM ที่ผูกกับรหัสงบประมาณ
     *
     * @return array{
     *   budget_code: string,
     *   found: bool,
     *   source: string,
     *   project_count: int,
     *   plan_total: float,
     *   projects: list<array<string, mixed>>
     * }
     */
    public function spendingPlanByBudgetCode(string $budgetCode): array
    {
        $budgetCode = trim($budgetCode);
        $empty = [
            'budget_code' => $budgetCode,
            'found' => false,
            'source' => '',
            'project_count' => 0,
            'plan_total' => 0.0,
            'projects' => [],
        ];

        if ($budgetCode === '') {
            return $empty;
        }

        $fetcher = function () use ($budgetCode, $empty): array {
            $payload = $this->fetchDetailByBudgetCode($budgetCode);
            if ($payload === null) {
                return $empty;
            }

            $projects = $this->extractPbmProjects($payload);
            if ($projects === []) {
                return array_merge($empty, [
                    'source' => (string) ($payload['source'] ?? ''),
                ]);
            }

            return $this->buildProjectSpendingRows($budgetCode, $projects, (string) ($payload['source'] ?? 'project'));
        };

        try {
            $cached = Cache::remember('budget.pbm.spending-plan.v2.'.$budgetCode, 60, $fetcher);
        } catch (\Throwable) {
            $cached = $fetcher();
        }

        return is_array($cached) ? $cached : $empty;
    }

    /**
     * ยอดแผนโครงการรวมตามรหัสงบ สำหรับหน้ารายการบริหารงบ
     *
     * @return array<string, float> budget_code => plan_total
     */
    public function planTotalsIndexedByBudgetCode(): array
    {
        $fetcher = function (): array {
            $url = trim((string) config(
                'services.project_pbm_api.detail_url',
                'https://api.pharmacy.cmu.ac.th/api/detailProjectPBM'
            ));
            if ($url === '') {
                return [];
            }

            $token = $this->resolveBearerToken();
            if ($token === '') {
                Log::warning('ProjectPbmService: empty API token for plan totals');

                return [];
            }

            try {
                $response = Http::acceptJson()
                    ->timeout(30)
                    ->withToken($token)
                    ->get($url, ['summary' => 'budget']);

                if (! $response->successful()) {
                    Log::warning('ProjectPbmService: plan totals failed', [
                        'status' => $response->status(),
                    ]);

                    return [];
                }

                $rows = $response->json('data');
                if (! is_array($rows)) {
                    return [];
                }

                $map = [];
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $code = trim((string) ($row['budget_code'] ?? ''));
                    if ($code === '') {
                        continue;
                    }
                    $map[$code] = round((float) ($row['plan_total'] ?? 0), 2);
                }

                return $map;
            } catch (\Throwable $e) {
                Log::warning('ProjectPbmService: plan totals exception', [
                    'message' => $e->getMessage(),
                ]);

                return [];
            }
        };

        try {
            $cached = Cache::remember('budget.pbm.plan-totals.v1', 60, $fetcher);
        } catch (\Throwable) {
            $cached = $fetcher();
        }

        return is_array($cached) ? $cached : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchDetailByBudgetCode(string $budgetCode): ?array
    {
        $url = trim((string) config(
            'services.project_pbm_api.detail_url',
            'https://api.pharmacy.cmu.ac.th/api/detailProjectPBM'
        ));
        if ($url === '') {
            return null;
        }

        $token = $this->resolveBearerToken();
        if ($token === '') {
            Log::warning('ProjectPbmService: empty API token');

            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(30)
                ->withToken($token)
                ->get($url, ['budget_code' => $budgetCode]);

            if ($response->status() === 404) {
                return null;
            }
            if (! $response->successful()) {
                Log::warning('ProjectPbmService: detailProjectPBM failed', [
                    'status' => $response->status(),
                    'budget_code' => $budgetCode,
                ]);

                return null;
            }

            $json = $response->json();

            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            Log::warning('ProjectPbmService: detailProjectPBM exception', [
                'budget_code' => $budgetCode,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function extractPbmProjects(array $payload): array
    {
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            return [];
        }

        $rows = $this->isAssoc($data) ? [$data] : $data;

        return collect($rows)
            ->filter(static fn ($row) => is_array($row))
            ->filter(static function (array $row): bool {
                return trim((string) ($row['code_pre_project'] ?? '')) !== '';
            })
            ->values()
            ->all();
    }

    /**
     * รวมยอดทั้งโครงการเป็นแถวเดียว ไม่แยกหมวดค่าใช้จ่าย
     *
     * @param  list<array<string, mixed>>  $projects
     * @return array<string, mixed>
     */
    private function buildProjectSpendingRows(string $budgetCode, array $projects, string $source): array
    {
        $rows = [];
        $planTotal = 0.0;

        foreach ($projects as $project) {
            $projectName = trim((string) ($project['name_project'] ?? ''));
            $projectCode = trim((string) ($project['code_pre_project'] ?? ''));
            if ($projectName === '') {
                $projectName = $projectCode !== '' ? $projectCode : 'โครงการ';
            }

            $months = array_fill(0, 12, 0.0);
            $summaryPlan = $project['spending_plan']['summary']['plan'] ?? null;
            if (is_array($summaryPlan)) {
                $months = $this->monthsFromPlanBag($summaryPlan);
            }

            $items = $project['spending_plan']['items'] ?? [];
            $itemTotal = 0.0;
            if (is_array($items)) {
                $itemMonths = array_fill(0, 12, 0.0);
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    if (trim((string) ($item['expenses_name'] ?? '')) === 'ไม่ใช้งบประมาณ') {
                        continue;
                    }
                    $lineMonths = $this->monthsFromItem($item);
                    $lineTotal = round(array_sum($lineMonths), 2);
                    $estimated = $this->toAmount($item['estimated_total'] ?? ($item['plan']['total'] ?? $lineTotal));
                    if ($lineTotal <= 0 && $estimated > 0) {
                        $lineTotal = $estimated;
                    }
                    if ($lineTotal <= 0 && $estimated <= 0) {
                        continue;
                    }
                    $itemTotal = round($itemTotal + max($lineTotal, $estimated), 2);
                    foreach ($lineMonths as $i => $value) {
                        $itemMonths[$i] = round(($itemMonths[$i] ?? 0) + (float) $value, 2);
                    }
                }
                if (array_sum($months) <= 0 && array_sum($itemMonths) > 0) {
                    $months = $itemMonths;
                }
            }

            $monthTotal = round(array_sum($months), 2);
            $combined = $this->toAmount(
                $project['budget_total']
                ?? $project['approved_budget']
                ?? ($project['spending_plan']['summary']['estimated_total'] ?? 0)
            );
            if ($combined <= 0) {
                $combined = $itemTotal > 0 ? $itemTotal : $monthTotal;
            }
            if ($monthTotal <= 0 && $combined > 0) {
                $monthTotal = $combined;
            }
            if ($monthTotal <= 0 && $combined <= 0) {
                continue;
            }

            $rows[] = [
                'code' => $projectCode,
                'name' => $projectName,
                'label' => trim($projectCode.($projectCode !== '' ? ' — ' : '').$projectName),
                'amount' => $combined,
                'months' => $months,
                'total' => $monthTotal > 0 ? $monthTotal : $combined,
            ];
            $planTotal = round($planTotal + ($monthTotal > 0 ? $monthTotal : $combined), 2);
        }

        return [
            'budget_code' => $budgetCode,
            'found' => $rows !== [],
            'source' => $source !== '' ? $source : 'project',
            'project_count' => count($rows),
            'plan_total' => $planTotal,
            'projects' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<float>
     */
    private function monthsFromItem(array $item): array
    {
        $planMonths = $item['plan_months'] ?? null;
        if (is_array($planMonths) && $planMonths !== []) {
            $byKey = [];
            foreach ($planMonths as $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $key = (string) ($entry['key'] ?? '');
                if ($key !== '') {
                    $byKey[$key] = $this->toAmount($entry['amount'] ?? 0);
                }
            }
            if ($byKey !== []) {
                return array_map(fn (string $key) => $byKey[$key] ?? 0.0, self::FISCAL_MONTH_KEYS);
            }
        }

        $plan = is_array($item['plan'] ?? null) ? $item['plan'] : [];

        return $this->monthsFromPlanBag($plan);
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return list<float>
     */
    private function monthsFromPlanBag(array $plan): array
    {
        return array_map(fn (string $key) => $this->toAmount($plan[$key] ?? 0), self::FISCAL_MONTH_KEYS);
    }

    private function toAmount(mixed $value): float
    {
        return round((float) $value, 2);
    }

    /**
     * @param  array<mixed>  $value
     */
    private function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    public function resolveBearerToken(): string
    {
        $staticToken = trim((string) config('services.staff_rx_api.api_token', ''));
        if ($staticToken !== '') {
            return $this->stripBearerPrefix($staticToken);
        }

        $tokenUrl = trim((string) (
            config('services.staff_rx_api.token_refresh_url')
            ?: config('services.auth_api.token_refresh_url', '')
        ));
        if ($tokenUrl === '') {
            return '';
        }

        try {
            $response = Http::acceptJson()->timeout(15)->get($tokenUrl);
            if (! $response->successful()) {
                return '';
            }

            $json = $response->json();
            if (is_array($json)) {
                foreach (['raw_token', 'token', 'access_token'] as $key) {
                    $token = trim((string) ($json[$key] ?? ''));
                    if ($token !== '') {
                        return $this->stripBearerPrefix($token);
                    }
                }
            }

            $body = trim((string) $response->body());
            if ($body !== '' && ! str_starts_with($body, '{') && ! str_starts_with($body, '[')) {
                return $this->stripBearerPrefix($body);
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }

    private function stripBearerPrefix(string $token): string
    {
        $token = trim($token);
        if (str_starts_with($token, 'Bearer ')) {
            return trim(substr($token, 7));
        }

        return $token;
    }
}
