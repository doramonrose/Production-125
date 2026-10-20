<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Handoff จากระบบโครงการ → ฟอร์มเบิกจ่ายเงินโครงการ
 *
 * ระบบโครงการส่ง JSON ตาม {@see self::normalize()} แล้วระบบนี้เก็บใน cache
 * แล้ว redirect ไป /withdraw/project?handoff=TOKEN เพื่อให้ล็อกอินแล้วยังเปิดฟอร์มได้
 */
class ProjectWithdrawHandoff
{
    public const CACHE_PREFIX = 'withdraw.project.handoff.';

    /** Cache mirror TTL (DB is source of truth for pending list). */
    public const TTL_SECONDS = 86400 * 30;

    public const DB_RETENTION_DAYS = 30;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONSUMED = 'consumed';

    public const STATUS_DISMISSED = 'dismissed';

    /** @var array<string, string> */
    private const MONTH_LABELS = [
        '1' => 'ม.ค.', '01' => 'ม.ค.', 'jan' => 'ม.ค.', 'ม.ค.' => 'ม.ค.', 'มกราคม' => 'ม.ค.',
        '2' => 'ก.พ.', '02' => 'ก.พ.', 'feb' => 'ก.พ.', 'ก.พ.' => 'ก.พ.', 'กุมภาพันธ์' => 'ก.พ.',
        '3' => 'มี.ค.', '03' => 'มี.ค.', 'mar' => 'มี.ค.', 'มี.ค.' => 'มี.ค.', 'มีนาคม' => 'มี.ค.',
        '4' => 'เม.ย.', '04' => 'เม.ย.', 'apr' => 'เม.ย.', 'เม.ย.' => 'เม.ย.', 'เมษายน' => 'เม.ย.',
        '5' => 'พ.ค.', '05' => 'พ.ค.', 'may' => 'พ.ค.', 'พ.ค.' => 'พ.ค.', 'พฤษภาคม' => 'พ.ค.',
        '6' => 'มิ.ย.', '06' => 'มิ.ย.', 'jun' => 'มิ.ย.', 'มิ.ย.' => 'มิ.ย.', 'มิถุนายน' => 'มิ.ย.',
        '7' => 'ก.ค.', '07' => 'ก.ค.', 'jul' => 'ก.ค.', 'ก.ค.' => 'ก.ค.', 'กรกฎาคม' => 'ก.ค.',
        '8' => 'ส.ค.', '08' => 'ส.ค.', 'aug' => 'ส.ค.', 'ส.ค.' => 'ส.ค.', 'สิงหาคม' => 'ส.ค.',
        '9' => 'ก.ย.', '09' => 'ก.ย.', 'sep' => 'ก.ย.', 'ก.ย.' => 'ก.ย.', 'กันยายน' => 'ก.ย.',
        '10' => 'ต.ค.', 'oct' => 'ต.ค.', 'ต.ค.' => 'ต.ค.', 'ตุลาคม' => 'ต.ค.',
        '11' => 'พ.ย.', 'nov' => 'พ.ย.', 'พ.ย.' => 'พ.ย.', 'พฤศจิกายน' => 'พ.ย.',
        '12' => 'ธ.ค.', 'dec' => 'ธ.ค.', 'ธ.ค.' => 'ธ.ค.', 'ธันวาคม' => 'ธ.ค.',
        'mon_01' => 'ม.ค.', 'mon_02' => 'ก.พ.', 'mon_03' => 'มี.ค.', 'mon_04' => 'เม.ย.',
        'mon_05' => 'พ.ค.', 'mon_06' => 'มิ.ย.', 'mon_07' => 'ก.ค.', 'mon_08' => 'ส.ค.',
        'mon_09' => 'ก.ย.', 'mon_10' => 'ต.ค.', 'mon_11' => 'พ.ย.', 'mon_12' => 'ธ.ค.',
    ];

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function store(array $payload): string
    {
        $token = Str::lower(Str::random(40));
        Cache::put(self::CACHE_PREFIX.$token, $payload, self::TTL_SECONDS);
        self::persistToDatabase($token, $payload, self::STATUS_PENDING);

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function peek(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || ! preg_match('/^[a-z0-9]{20,64}$/', $token)) {
            return null;
        }

        $payload = Cache::get(self::CACHE_PREFIX.$token);
        if (is_array($payload)) {
            // Backfill DB so pending list survives after cache TTL.
            self::persistToDatabase($token, $payload, self::STATUS_PENDING);

            return $payload;
        }

        $fromDb = self::loadPendingPayloadFromDatabase($token);
        if (is_array($fromDb)) {
            Cache::put(self::CACHE_PREFIX.$token, $fromDb, self::TTL_SECONDS);

            return $fromDb;
        }

        $fromProject = self::loadPendingPayloadFromProjectConfirm($token);
        if (is_array($fromProject)) {
            Cache::put(self::CACHE_PREFIX.$token, $fromProject, self::TTL_SECONDS);
            self::persistToDatabase($token, $fromProject, self::STATUS_PENDING);

            return $fromProject;
        }

        return null;
    }

    public static function forget(string $token, string $status = self::STATUS_CONSUMED): void
    {
        $token = trim($token);
        if ($token === '') {
            return;
        }
        Cache::forget(self::CACHE_PREFIX.$token);
        self::markDatabaseStatus($token, $status);
    }

    public static function dismiss(string $token): bool
    {
        $token = trim($token);
        if ($token === '' || ! preg_match('/^[a-z0-9]{20,64}$/', $token)) {
            return false;
        }
        Cache::forget(self::CACHE_PREFIX.$token);
        self::markDatabaseStatus($token, self::STATUS_DISMISSED);

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listPending(?string $staffId = null, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $local = self::listPendingLocal($staffId, $limit);
        $fromProject = self::fetchPendingFromProjectApp($staffId, $limit);

        return self::mergePendingLists($local, $fromProject, $limit);
    }

    /**
     * Pending handoffs from withdraw DB only (no project-app callback — avoids recursion).
     *
     * @return list<array<string, mixed>>
     */
    public static function listPendingLocal(?string $staffId = null, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $staffId = trim((string) $staffId);

        if (! Schema::hasTable('withdraw_project_handoff')) {
            return [];
        }

        self::purgeExpiredPending();

        $query = DB::table('withdraw_project_handoff')
            ->where('status', self::STATUS_PENDING)
            ->where(function ($inner) {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($staffId !== '') {
            $query->where(function ($inner) use ($staffId) {
                $inner->where('applicant_staff_id', $staffId)
                    ->orWhereNull('applicant_staff_id')
                    ->orWhere('applicant_staff_id', '');
            });
        }

        return $query->get()->map(static function ($row) {
            $items = [];
            $payload = json_decode((string) ($row->payload_json ?? ''), true);
            if (is_array($payload) && is_array($payload['items'] ?? null)) {
                $items = $payload['items'];
            }

            $lineLabels = [];
            $lineIds = [];
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $label = trim((string) ($item['name'] ?? $item['line_detail'] ?? ''));
                if ($label !== '') {
                    $lineLabels[] = $label;
                }
                $lineId = trim((string) ($item['id'] ?? $item['line_id'] ?? ''));
                if ($lineId !== '') {
                    $lineIds[] = $lineId;
                }
            }

            return [
                'token' => (string) ($row->token ?? ''),
                'project_id' => (string) ($row->project_id ?? ''),
                'project_code' => (string) ($row->project_code ?? ''),
                'project_name' => (string) ($row->project_name ?? ''),
                'activity_id' => (string) ($row->activity_id ?? ($payload['activity_id'] ?? '')),
                'activity_name' => (string) ($row->activity_name ?? ''),
                'applicant_staff_id' => (string) ($row->applicant_staff_id ?? ''),
                'item_count' => (int) ($row->item_count ?? count($items)),
                'project_amount' => (float) ($row->project_amount ?? 0),
                'created_at' => (string) ($row->created_at ?? ''),
                'expires_at' => (string) ($row->expires_at ?? ''),
                'resume_url' => self::formUrl((string) ($row->token ?? '')),
                'source' => 'withdraw',
                'line_labels' => array_values(array_unique(array_filter($lineLabels))),
                'line_ids' => array_values(array_unique(array_filter($lineIds))),
            ];
        })->filter(static fn (array $row) => trim((string) ($row['token'] ?? '')) !== '')->values()->all();
    }

    /**
     * ดึงรายการค้างจาก API ฝั่งระบบโครงการ
     * รองรับทั้ง data[] (handoff) และ confirmed_lines[] (reconcile=pending_handoff)
     *
     * @return list<array<string, mixed>>
     */
    public static function fetchPendingFromProjectApp(?string $staffId = null, int $limit = 20): array
    {
        $base = rtrim((string) config('services.project_app.base_url', ''), '/');
        $path = trim((string) config(
            'services.project_app.pending_withdraw_path',
            '/User/api_pbm_withdraw_pending.php'
        ));
        if ($base === '' || $path === '') {
            return [];
        }
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        $staffId = trim((string) $staffId);
        $out = [];
        $seenTokens = [];

        // 1) เรียกแบบไม่ระบุโครงการ (ได้ handoff pending รวม)
        foreach (self::requestProjectPendingApi($base.$path, [
            'staff_id' => $staffId !== '' ? $staffId : null,
            'limit' => max(1, min(50, $limit)),
        ]) as $row) {
            $token = trim((string) ($row['token'] ?? ''));
            if ($token === '' || isset($seenTokens[$token])) {
                continue;
            }
            $seenTokens[$token] = true;
            $out[] = $row;
        }

        // 2) เรียกรายโครงการเพื่อได้ confirmed_lines (API ส่งชุดนี้เมื่อมี project_code)
        foreach (self::resolveProjectCodesForPendingLookup($limit) as $projectCode) {
            $payload = self::requestProjectPendingApiRaw($base.$path, [
                'project_code' => $projectCode,
                'staff_id' => $staffId !== '' ? $staffId : null,
                'limit' => max(1, min(50, $limit)),
            ]);
            if ($payload === null) {
                continue;
            }

            foreach (self::normalizeProjectPendingDataRows($payload['data'] ?? []) as $row) {
                $token = trim((string) ($row['token'] ?? ''));
                if ($token === '' || isset($seenTokens[$token])) {
                    continue;
                }
                $seenTokens[$token] = true;
                $out[] = $row;
            }

            foreach (self::normalizeProjectConfirmedPendingLines($payload['confirmed_lines'] ?? [], $projectCode) as $row) {
                $token = trim((string) ($row['token'] ?? ''));
                if ($token === '' || isset($seenTokens[$token])) {
                    // merge line details into existing card when same token
                    if ($token !== '' && isset($seenTokens[$token])) {
                        foreach ($out as $idx => $existing) {
                            if (($existing['token'] ?? '') !== $token) {
                                continue;
                            }
                            $out[$idx]['item_count'] = max((int) ($existing['item_count'] ?? 0), (int) ($row['item_count'] ?? 0));
                            $out[$idx]['project_amount'] = max((float) ($existing['project_amount'] ?? 0), (float) ($row['project_amount'] ?? 0));
                            if (trim((string) ($existing['activity_name'] ?? '')) === '' && trim((string) ($row['activity_name'] ?? '')) !== '') {
                                $out[$idx]['activity_name'] = $row['activity_name'];
                            }
                            $out[$idx]['line_labels'] = array_values(array_unique(array_filter(array_merge(
                                is_array($existing['line_labels'] ?? null) ? $existing['line_labels'] : [],
                                is_array($row['line_labels'] ?? null) ? $row['line_labels'] : []
                            ))));
                            break;
                        }
                    }
                    continue;
                }
                $seenTokens[$token] = true;
                $out[] = $row;
            }
        }

        usort($out, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return array_slice($out, 0, max(1, min(50, $limit)));
    }

    /**
     * @return list<string>
     */
    private static function resolveProjectCodesForPendingLookup(int $limit = 20): array
    {
        $codes = [];

        try {
            if (Schema::hasTable('withdraw_project_handoff')) {
                $codes = array_merge($codes, DB::table('withdraw_project_handoff')
                    ->where('status', self::STATUS_PENDING)
                    ->whereNotNull('project_code')
                    ->where('project_code', '!=', '')
                    ->orderByDesc('created_at')
                    ->limit(30)
                    ->pluck('project_code')
                    ->map(static fn ($v) => trim((string) $v))
                    ->filter()
                    ->all());
            }
        } catch (\Throwable) {
            // ignore
        }

        try {
            if (Schema::hasTable('withdraw') && Schema::hasColumn('withdraw', 'withdraw_project_code')) {
                $codes = array_merge($codes, DB::table('withdraw')
                    ->where('withdraw_type', 'project')
                    ->whereNotNull('withdraw_project_code')
                    ->where('withdraw_project_code', '!=', '')
                    ->orderByDesc('withdraw_id')
                    ->limit(40)
                    ->pluck('withdraw_project_code')
                    ->map(static fn ($v) => trim((string) $v))
                    ->filter()
                    ->all());
            }
        } catch (\Throwable) {
            // ignore
        }

        // Always include known active demos/projects from recent confirms if table exists.
        try {
            if (self::hasProjectConfirmTable()) {
                $codes = array_merge($codes, DB::table('project.pbm_withdraw_confirm')
                    ->where('status', self::STATUS_PENDING)
                    ->whereNotNull('project_code')
                    ->where('project_code', '!=', '')
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->pluck('project_code')
                    ->map(static fn ($v) => trim((string) $v))
                    ->filter()
                    ->all());
            }
        } catch (\Throwable) {
            // ignore
        }

        $unique = [];
        foreach ($codes as $code) {
            if ($code === '' || isset($unique[$code])) {
                continue;
            }
            $unique[$code] = true;
            if (count($unique) >= max(5, min(20, $limit))) {
                break;
            }
        }

        // Ensure S69-style codes recently used still get checked even if not in withdraw yet.
        return array_keys($unique);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    private static function requestProjectPendingApi(string $url, array $query): array
    {
        $payload = self::requestProjectPendingApiRaw($url, $query);
        if ($payload === null) {
            return [];
        }

        return self::normalizeProjectPendingDataRows($payload['data'] ?? []);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>|null
     */
    private static function requestProjectPendingApiRaw(string $url, array $query): ?array
    {
        $query = array_filter($query, static fn ($value) => $value !== null && $value !== '');
        $fullUrl = $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);

        try {
            $response = Http::acceptJson()->timeout(12)->get($fullUrl);
            if (! $response->successful()) {
                Log::warning('ProjectWithdrawHandoff: project pending API failed', [
                    'status' => $response->status(),
                    'url' => $fullUrl,
                ]);

                return null;
            }

            $json = $response->json();
            if (! is_array($json) || ($json['status'] ?? '') !== 'success') {
                return null;
            }

            return $json;
        } catch (\Throwable $e) {
            Log::warning('ProjectWithdrawHandoff: project pending API exception', [
                'message' => $e->getMessage(),
                'url' => $fullUrl,
            ]);

            return null;
        }
    }

    /**
     * @param  mixed  $rows
     * @return list<array<string, mixed>>
     */
    private static function normalizeProjectPendingDataRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $token = trim((string) ($row['token'] ?? $row['pending_token'] ?? ''));
            if ($token === '') {
                continue;
            }
            $out[] = [
                'token' => $token,
                'project_id' => (string) ($row['project_id'] ?? ''),
                'project_code' => (string) ($row['project_code'] ?? ''),
                'project_name' => (string) ($row['project_name'] ?? ''),
                'activity_name' => (string) ($row['activity_name'] ?? ''),
                'applicant_staff_id' => (string) ($row['applicant_staff_id'] ?? ''),
                'item_count' => (int) ($row['item_count'] ?? 0),
                'project_amount' => (float) ($row['project_amount'] ?? 0),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'expires_at' => (string) ($row['expires_at'] ?? ''),
                'resume_url' => trim((string) ($row['resume_url'] ?? '')) !== ''
                    ? (string) $row['resume_url']
                    : self::formUrl($token),
                'source' => (string) ($row['source'] ?? 'project'),
                'line_labels' => [],
            ];
        }

        return $out;
    }

    /**
     * แปลง confirmed_lines ที่ยังรอบันทึก เป็นการ์ดรายการค้าง — แยกทีละครั้ง (line) ตามระบบโครงการ
     * และเก็บข้อมูลสำหรับสร้าง handoff ใหม่ในระบบเบิกจ่ายเอง (ไม่ต้องยืนยันซ้ำที่โครงการ)
     *
     * @param  mixed  $rows
     * @return list<array<string, mixed>>
     */
    private static function normalizeProjectConfirmedPendingLines(mixed $rows, string $fallbackProjectCode = ''): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $groups = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $reconcile = trim((string) ($row['reconcile'] ?? ''));
            $rawStatus = trim((string) ($row['withdraw_raw_status'] ?? ''));
            $withdrawId = (int) ($row['withdraw_id'] ?? 0);
            $externalWithdrawId = (int) ($row['external_withdraw_id'] ?? 0);
            $isPending = in_array($reconcile, ['pending_handoff', 'confirmed_no_withdraw'], true)
                || in_array($rawStatus, ['pending_handoff', 'confirmed_local'], true)
                || ($withdrawId <= 0 && $externalWithdrawId <= 0 && $reconcile !== 'has_withdraw');

            if (! $isPending) {
                continue;
            }

            $projectCode = trim((string) ($row['project_code'] ?? $fallbackProjectCode));
            $activityId = trim((string) ($row['activity_id'] ?? ''));
            $reportId = trim((string) ($row['report_id'] ?? ''));
            $lineId = trim((string) ($row['line_id'] ?? ''));
            $amount = (float) ($row['amount'] ?? 0);
            $label = trim((string) ($row['line_detail'] ?? ''));
            $activityDetail = trim((string) ($row['activity_detail'] ?? ''));

            // One card per project withdrawal instance (line) — matches รายงานการใช้จ่าย rows.
            if ($lineId !== '') {
                $groupKey = 'line:'.$projectCode.':'.$lineId;
                $stableToken = 'project-pending:'.$projectCode.':line:'.$lineId;
            } else {
                $fallbackKey = md5(implode('|', [
                    $projectCode,
                    $reportId,
                    $activityId,
                    $label,
                    number_format($amount, 2, '.', ''),
                    (string) ($row['confirmed_at'] ?? ''),
                ]));
                $groupKey = 'line-fallback:'.$fallbackKey;
                $stableToken = 'project-pending:'.$projectCode.':x:'.$fallbackKey;
            }

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'token' => $stableToken,
                    'project_id' => (string) ($row['project_id'] ?? ''),
                    'project_code' => $projectCode,
                    'project_name' => (string) ($row['project_name'] ?? ''),
                    'activity_id' => $activityId,
                    'activity_name' => (string) ($row['activity_name'] ?? ''),
                    'activity_detail' => $activityDetail,
                    'applicant_staff_id' => '',
                    'item_count' => 1,
                    'project_amount' => $amount,
                    'created_at' => (string) ($row['confirmed_at'] ?? ''),
                    'expires_at' => '',
                    'resume_url' => '',
                    'source' => 'project.confirmed_lines',
                    'line_labels' => $label !== '' ? [$label] : [],
                    'line_ids' => $lineId !== '' ? [$lineId] : [],
                    'needs_reconfirm' => false,
                    'can_recreate' => true,
                    'recreate' => [
                        'project_id' => (string) ($row['project_id'] ?? ''),
                        'project_code' => $projectCode,
                        'project_name' => (string) ($row['project_name'] ?? ''),
                        'activity_id' => $activityId,
                        'activity_name' => (string) ($row['activity_name'] ?? ''),
                        'activity_detail' => $activityDetail,
                        'report_id' => $reportId,
                        'line_ids' => $lineId !== '' ? [$lineId] : [],
                        'lines' => [[
                            'line_id' => $lineId,
                            'name' => $label !== '' ? $label : (string) ($row['activity_name'] ?? 'รายการเบิกจ่าย'),
                            'amount' => $amount,
                            'month' => (string) ($row['disburse_month'] ?? ''),
                            'activity_detail' => $activityDetail,
                            'project_activity_budget_id' => (string) ($row['project_activity_budget_id'] ?? ''),
                        ]],
                    ],
                ];
                continue;
            }

            // Same line_id seen twice — keep the richer/newer row.
            if ($amount > (float) ($groups[$groupKey]['project_amount'] ?? 0)) {
                $groups[$groupKey]['project_amount'] = $amount;
                $groups[$groupKey]['recreate']['lines'][0]['amount'] = $amount;
            }
            if ($label !== '' && ($groups[$groupKey]['line_labels'][0] ?? '') === '') {
                $groups[$groupKey]['line_labels'] = [$label];
                $groups[$groupKey]['recreate']['lines'][0]['name'] = $label;
            }
            if ($activityDetail !== '' && trim((string) ($groups[$groupKey]['activity_detail'] ?? '')) === '') {
                $groups[$groupKey]['activity_detail'] = $activityDetail;
                $groups[$groupKey]['recreate']['activity_detail'] = $activityDetail;
                $groups[$groupKey]['recreate']['lines'][0]['activity_detail'] = $activityDetail;
            }
            if (trim((string) ($groups[$groupKey]['created_at'] ?? '')) === '' && trim((string) ($row['confirmed_at'] ?? '')) !== '') {
                $groups[$groupKey]['created_at'] = (string) $row['confirmed_at'];
            }
        }

        $out = [];
        foreach ($groups as $row) {
            $token = trim((string) ($row['token'] ?? ''));
            if ($token === '') {
                continue;
            }
            $row['line_labels'] = array_values(array_unique(array_filter($row['line_labels'])));
            $row['line_ids'] = array_values(array_unique(array_filter($row['line_ids'] ?? [])));
            $row['recreate']['line_ids'] = array_values(array_unique(array_filter($row['recreate']['line_ids'])));
            if (trim((string) ($row['project_name'] ?? '')) === '') {
                $row['project_name'] = self::resolveProjectName(
                    (string) ($row['project_id'] ?? ''),
                    (string) ($row['project_code'] ?? '')
                );
                $row['recreate']['project_name'] = $row['project_name'];
            }
            // Prefer showing the per-instance purpose (ครั้ง) in the subtitle.
            $lineLabel = trim((string) ($row['line_labels'][0] ?? ''));
            $activityName = trim((string) ($row['activity_name'] ?? ''));
            if ($lineLabel !== '') {
                $row['activity_name'] = $activityName !== ''
                    ? $activityName.' · '.$lineLabel
                    : $lineLabel;
            }
            $out[] = $row;
        }

        return $out;
    }

    public static function resolveProjectName(string $projectId = '', string $projectCode = ''): string
    {
        $projectId = trim($projectId);
        $projectCode = trim($projectCode);

        try {
            if ($projectCode !== '') {
                $name = trim((string) (DB::table('project.pre_project')
                    ->where('code_pre_project', $projectCode)
                    ->value('name_project') ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
            if ($projectId !== '' && ctype_digit($projectId)) {
                $name = trim((string) (DB::table('project.pre_project')
                    ->where('id', (int) $projectId)
                    ->value('name_project') ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }

    /**
     * สร้าง handoff ใหม่จากรายการที่ยืนยันในระบบโครงการแล้ว แต่ลิงก์เดิมใช้ไม่ได้
     *
     * @param  array<string, mixed>  $recreate
     */
    public static function recreateHandoffFromProjectLines(array $recreate): ?string
    {
        $projectCode = trim((string) ($recreate['project_code'] ?? ''));
        $lines = is_array($recreate['lines'] ?? null) ? $recreate['lines'] : [];

        // If lines not provided, reload from project API.
        if ($lines === [] && $projectCode !== '') {
            $base = rtrim((string) config('services.project_app.base_url', ''), '/');
            $path = trim((string) config(
                'services.project_app.pending_withdraw_path',
                '/User/api_pbm_withdraw_pending.php'
            ));
            if ($base !== '' && $path !== '') {
                if (! str_starts_with($path, '/')) {
                    $path = '/'.$path;
                }
                $payload = self::requestProjectPendingApiRaw($base.$path, [
                    'project_code' => $projectCode,
                ]);
                $wantedLineIds = array_map('strval', is_array($recreate['line_ids'] ?? null) ? $recreate['line_ids'] : []);
                $reportId = trim((string) ($recreate['report_id'] ?? ''));
                $activityId = trim((string) ($recreate['activity_id'] ?? ''));
                foreach (($payload['confirmed_lines'] ?? []) as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $reconcile = trim((string) ($row['reconcile'] ?? ''));
                    $withdrawId = (int) ($row['withdraw_id'] ?? 0);
                    $externalWithdrawId = (int) ($row['external_withdraw_id'] ?? 0);
                    $isPending = in_array($reconcile, ['pending_handoff', 'confirmed_no_withdraw'], true)
                        || ($withdrawId <= 0 && $externalWithdrawId <= 0 && $reconcile !== 'has_withdraw');
                    if (! $isPending) {
                        continue;
                    }
                    if ($reportId !== '' && trim((string) ($row['report_id'] ?? '')) !== $reportId) {
                        continue;
                    }
                    if ($activityId !== '' && trim((string) ($row['activity_id'] ?? '')) !== $activityId) {
                        continue;
                    }
                    $lineId = trim((string) ($row['line_id'] ?? ''));
                    if ($wantedLineIds !== [] && $lineId !== '' && ! in_array($lineId, $wantedLineIds, true)) {
                        continue;
                    }
                    $label = trim((string) ($row['line_detail'] ?? ''));
                    $lines[] = [
                        'line_id' => $lineId,
                        'name' => $label !== '' ? $label : (string) ($row['activity_name'] ?? 'รายการเบิกจ่าย'),
                        'amount' => (float) ($row['amount'] ?? 0),
                        'month' => (string) ($row['disburse_month'] ?? ''),
                        'activity_detail' => (string) ($row['activity_detail'] ?? ''),
                        'project_activity_budget_id' => (string) ($row['project_activity_budget_id'] ?? ''),
                    ];
                    if (trim((string) ($recreate['activity_name'] ?? '')) === '') {
                        $recreate['activity_name'] = (string) ($row['activity_name'] ?? '');
                    }
                    if (trim((string) ($recreate['activity_detail'] ?? '')) === '') {
                        $recreate['activity_detail'] = (string) ($row['activity_detail'] ?? '');
                    }
                    if (trim((string) ($recreate['project_id'] ?? '')) === '') {
                        $recreate['project_id'] = (string) ($row['project_id'] ?? '');
                    }
                }
            }
        }

        if ($projectCode === '' || $lines === []) {
            return null;
        }

        $projectId = trim((string) ($recreate['project_id'] ?? ''));
        $projectName = trim((string) ($recreate['project_name'] ?? ''));
        if ($projectName === '') {
            $projectName = self::resolveProjectName($projectId, $projectCode);
        }
        $activityName = trim((string) ($recreate['activity_name'] ?? ''));
        $activityDetail = trim((string) ($recreate['activity_detail'] ?? ''));
        if ($activityDetail === '') {
            foreach ($lines as $line) {
                $candidate = trim((string) ($line['activity_detail'] ?? ''));
                if ($candidate !== '') {
                    $activityDetail = $candidate;
                    break;
                }
            }
        }

        $items = [];
        $total = 0.0;
        $wantedLineIds = [];
        $wantedLabels = [];
        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }
            $amount = self::toAmount($line['amount'] ?? 0);
            $name = trim((string) ($line['name'] ?? $line['line_detail'] ?? ''));
            if ($name === '' && $amount <= 0) {
                continue;
            }
            $total += $amount;
            $lineId = trim((string) ($line['line_id'] ?? ''));
            if ($lineId !== '') {
                $wantedLineIds[] = $lineId;
            }
            if ($name !== '') {
                $wantedLabels[] = $name;
            }
            $items[] = [
                'id' => $lineId,
                'name' => $name !== '' ? $name : 'รายการเบิกจ่าย',
                'amount' => $amount,
                'month' => (string) ($line['month'] ?? $line['disburse_month'] ?? ''),
            ];
        }

        if ($items === []) {
            return null;
        }

        // Reuse an existing pending handoff for the same project lines (avoid duplicate cards).
        $existingToken = self::findMatchingPendingHandoffToken(
            $projectCode,
            $wantedLineIds,
            $wantedLabels,
            count($items),
            $total > 0 ? $total : self::toAmount($recreate['project_amount'] ?? 0)
        );
        if ($existingToken !== null) {
            return $existingToken;
        }

        $normalized = self::normalize([
            'project_id' => $projectId,
            'project_code' => $projectCode,
            'project_name' => $projectName,
            'activity_id' => (string) ($recreate['activity_id'] ?? ''),
            'activity_name' => $activityName,
            'activity_detail' => $activityDetail,
            'subject' => $activityName !== '' ? 'ขออนุมัติเบิกจ่าย'.$activityName : 'ขออนุมัติเบิกค่าใช้จ่ายโครงการ',
            'project_amount' => $total > 0 ? $total : self::toAmount($recreate['project_amount'] ?? 0),
            'items' => $items,
        ]);

        $token = self::store($normalized);

        // Collapse older pending handoffs that cover the same confirmed lines.
        self::dismissDuplicatePendingHandoffs(
            $projectCode,
            $token,
            $wantedLineIds,
            $wantedLabels,
            count($items),
            (float) ($normalized['project_amount'] ?? $total)
        );

        return $token;
    }

    /**
     * @param  list<string>  $lineIds
     * @param  list<string>  $labels
     */
    private static function findMatchingPendingHandoffToken(
        string $projectCode,
        array $lineIds,
        array $labels,
        int $itemCount,
        float $amount
    ): ?string {
        if ($projectCode === '' || ! Schema::hasTable('withdraw_project_handoff')) {
            return null;
        }

        $rows = DB::table('withdraw_project_handoff')
            ->where('status', self::STATUS_PENDING)
            ->where('project_code', $projectCode)
            ->where(function ($inner) {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['token', 'item_count', 'project_amount', 'payload_json']);

        $wantedIds = self::normalizeSignatureParts($lineIds);
        $wantedLabels = self::normalizeSignatureParts($labels);

        foreach ($rows as $row) {
            $payload = json_decode((string) ($row->payload_json ?? ''), true);
            $items = is_array($payload) && is_array($payload['items'] ?? null) ? $payload['items'] : [];
            $haveIds = [];
            $haveLabels = [];
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id = trim((string) ($item['id'] ?? $item['line_id'] ?? ''));
                if ($id !== '') {
                    $haveIds[] = $id;
                }
                $label = trim((string) ($item['name'] ?? $item['line_detail'] ?? ''));
                if ($label !== '') {
                    $haveLabels[] = $label;
                }
            }
            if (self::pendingSignaturesMatch(
                $wantedIds,
                $wantedLabels,
                $itemCount,
                $amount,
                self::normalizeSignatureParts($haveIds),
                self::normalizeSignatureParts($haveLabels),
                (int) ($row->item_count ?? count($items)),
                (float) ($row->project_amount ?? 0)
            )) {
                return (string) $row->token;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $lineIds
     * @param  list<string>  $labels
     */
    private static function dismissDuplicatePendingHandoffs(
        string $projectCode,
        string $keepToken,
        array $lineIds,
        array $labels,
        int $itemCount,
        float $amount
    ): void {
        if ($projectCode === '' || $keepToken === '' || ! Schema::hasTable('withdraw_project_handoff')) {
            return;
        }

        $rows = DB::table('withdraw_project_handoff')
            ->where('status', self::STATUS_PENDING)
            ->where('project_code', $projectCode)
            ->where('token', '!=', $keepToken)
            ->limit(30)
            ->get(['token', 'item_count', 'project_amount', 'payload_json']);

        $wantedIds = self::normalizeSignatureParts($lineIds);
        $wantedLabels = self::normalizeSignatureParts($labels);

        foreach ($rows as $row) {
            $token = trim((string) ($row->token ?? ''));
            if ($token === '') {
                continue;
            }
            $payload = json_decode((string) ($row->payload_json ?? ''), true);
            $items = is_array($payload) && is_array($payload['items'] ?? null) ? $payload['items'] : [];
            $haveIds = [];
            $haveLabels = [];
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id = trim((string) ($item['id'] ?? $item['line_id'] ?? ''));
                if ($id !== '') {
                    $haveIds[] = $id;
                }
                $label = trim((string) ($item['name'] ?? $item['line_detail'] ?? ''));
                if ($label !== '') {
                    $haveLabels[] = $label;
                }
            }
            if (! self::pendingSignaturesMatch(
                $wantedIds,
                $wantedLabels,
                $itemCount,
                $amount,
                self::normalizeSignatureParts($haveIds),
                self::normalizeSignatureParts($haveLabels),
                (int) ($row->item_count ?? count($items)),
                (float) ($row->project_amount ?? 0)
            )) {
                continue;
            }
            self::dismiss($token);
        }
    }

    /**
     * @param  list<string>  $parts
     * @return list<string>
     */
    private static function normalizeSignatureParts(array $parts): array
    {
        $out = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value === '') {
                continue;
            }
            $out[$value] = $value;
        }
        $values = array_values($out);
        sort($values);

        return $values;
    }

    /**
     * @param  list<string>  $aIds
     * @param  list<string>  $aLabels
     * @param  list<string>  $bIds
     * @param  list<string>  $bLabels
     */
    private static function pendingSignaturesMatch(
        array $aIds,
        array $aLabels,
        int $aCount,
        float $aAmount,
        array $bIds,
        array $bLabels,
        int $bCount,
        float $bAmount
    ): bool {
        if ($aIds !== [] && $bIds !== [] && $aIds === $bIds) {
            return true;
        }
        if ($aLabels !== [] && $bLabels !== [] && $aLabels === $bLabels) {
            return true;
        }

        return $aCount > 0
            && $aCount === $bCount
            && abs($aAmount - $bAmount) < 0.005;
    }

    /**
     * Content fingerprint used to collapse duplicate pending cards for the same project lines.
     *
     * @param  array<string, mixed>  $row
     */
    private static function pendingContentKey(array $row): string
    {
        $code = trim((string) ($row['project_code'] ?? ''));
        $ids = [];
        if (is_array($row['line_ids'] ?? null)) {
            $ids = $row['line_ids'];
        } elseif (is_array($row['recreate']['line_ids'] ?? null)) {
            $ids = $row['recreate']['line_ids'];
        }
        $ids = self::normalizeSignatureParts(array_map('strval', $ids));
        if ($ids !== []) {
            return $code.'|id:'.implode(',', $ids);
        }

        $labels = is_array($row['line_labels'] ?? null) ? $row['line_labels'] : [];
        if ($labels === [] && is_array($row['recreate']['lines'] ?? null)) {
            foreach ($row['recreate']['lines'] as $line) {
                if (! is_array($line)) {
                    continue;
                }
                $label = trim((string) ($line['name'] ?? $line['line_detail'] ?? ''));
                if ($label !== '') {
                    $labels[] = $label;
                }
            }
        }
        $labels = self::normalizeSignatureParts(array_map('strval', $labels));
        if ($labels !== []) {
            return $code.'|label:'.implode(',', $labels);
        }

        return $code.'|meta:'
            .(int) ($row['item_count'] ?? 0).':'
            .number_format((float) ($row['project_amount'] ?? 0), 2, '.', '').':'
            .trim((string) ($row['activity_name'] ?? ''));
    }

    /**
     * Prefer a usable withdraw handoff token over a synthetic project-pending card.
     *
     * @param  array<string, mixed>  $row
     */
    private static function pendingRowPreferenceScore(array $row): int
    {
        $token = trim((string) ($row['token'] ?? ''));
        $source = trim((string) ($row['source'] ?? ''));
        $score = ((int) ($row['item_count'] ?? 0) * 10)
            + ((float) ($row['project_amount'] ?? 0) > 0 ? 5 : 0)
            + (trim((string) ($row['project_name'] ?? '')) !== '' ? 2 : 0)
            + (is_array($row['recreate'] ?? null) ? 3 : 0)
            + (is_array($row['line_labels'] ?? null) && $row['line_labels'] !== [] ? 2 : 0);

        if ($source === 'withdraw' || (! str_starts_with($token, 'project-pending:') && $token !== '')) {
            $score += 50;
        }
        if ($source === 'project.confirmed_lines' || str_starts_with($token, 'project-pending:')) {
            $score += 10;
        }

        return $score;
    }

    /**
     * @param  array<string, mixed>  $preferred
     * @param  array<string, mixed>  $other
     * @return array<string, mixed>
     */
    private static function mergePendingRowDetails(array $preferred, array $other): array
    {
        $merged = $preferred;
        foreach (['project_name', 'activity_name', 'activity_id', 'project_id', 'resume_url'] as $field) {
            if (trim((string) ($merged[$field] ?? '')) === '' && trim((string) ($other[$field] ?? '')) !== '') {
                $merged[$field] = $other[$field];
            }
        }
        $merged['item_count'] = max((int) ($preferred['item_count'] ?? 0), (int) ($other['item_count'] ?? 0));
        $merged['project_amount'] = max((float) ($preferred['project_amount'] ?? 0), (float) ($other['project_amount'] ?? 0));
        $merged['line_labels'] = array_values(array_unique(array_filter(array_merge(
            is_array($preferred['line_labels'] ?? null) ? $preferred['line_labels'] : [],
            is_array($other['line_labels'] ?? null) ? $other['line_labels'] : []
        ))));
        $merged['line_ids'] = array_values(array_unique(array_filter(array_merge(
            is_array($preferred['line_ids'] ?? null) ? $preferred['line_ids'] : [],
            is_array($other['line_ids'] ?? null) ? $other['line_ids'] : [],
            is_array($preferred['recreate']['line_ids'] ?? null) ? $preferred['recreate']['line_ids'] : [],
            is_array($other['recreate']['line_ids'] ?? null) ? $other['recreate']['line_ids'] : []
        ))));
        if (! empty($other['recreate']) && empty($merged['recreate'])) {
            $merged['recreate'] = $other['recreate'];
            $merged['can_recreate'] = true;
        } elseif (! empty($other['recreate']) && ! empty($merged['recreate'])) {
            $merged['recreate']['line_ids'] = array_values(array_unique(array_filter(array_merge(
                is_array($merged['recreate']['line_ids'] ?? null) ? $merged['recreate']['line_ids'] : [],
                is_array($other['recreate']['line_ids'] ?? null) ? $other['recreate']['line_ids'] : []
            ))));
            $merged['recreate']['lines'] = array_values(array_merge(
                is_array($merged['recreate']['lines'] ?? null) ? $merged['recreate']['lines'] : [],
                is_array($other['recreate']['lines'] ?? null) ? $other['recreate']['lines'] : []
            ));
            // de-dupe recreate lines by line_id/name
            $seenLine = [];
            $uniqueLines = [];
            foreach ($merged['recreate']['lines'] as $line) {
                if (! is_array($line)) {
                    continue;
                }
                $key = trim((string) ($line['line_id'] ?? '')) !== ''
                    ? 'id:'.trim((string) $line['line_id'])
                    : 'name:'.trim((string) ($line['name'] ?? '')).':'.number_format((float) ($line['amount'] ?? 0), 2, '.', '');
                if (isset($seenLine[$key])) {
                    continue;
                }
                $seenLine[$key] = true;
                $uniqueLines[] = $line;
            }
            $merged['recreate']['lines'] = $uniqueLines;
            $merged['can_recreate'] = true;
        }
        if (! empty($other['can_recreate'])) {
            $merged['can_recreate'] = true;
        }

        return $merged;
    }

    /**
     * @param  list<array<string, mixed>>  $primary
     * @param  list<array<string, mixed>>  $secondary
     * @return list<array<string, mixed>>
     */
    private static function mergePendingLists(array $primary, array $secondary, int $limit): array
    {
        $map = [];
        foreach ([$primary, $secondary] as $list) {
            foreach ($list as $row) {
                $token = trim((string) ($row['token'] ?? ''));
                if ($token === '') {
                    continue;
                }
                if (! isset($map[$token])) {
                    $map[$token] = $row;
                    continue;
                }
                $prev = $map[$token];
                if (self::pendingRowPreferenceScore($row) > self::pendingRowPreferenceScore($prev)) {
                    $map[$token] = self::mergePendingRowDetails($row, $prev);
                } else {
                    $map[$token] = self::mergePendingRowDetails($prev, $row);
                }
            }
        }

        // Authoritative confirmed_lines per project — drop stale local cards that don't match.
        $confirmedByProject = [];
        foreach ($map as $row) {
            $source = trim((string) ($row['source'] ?? ''));
            $token = trim((string) ($row['token'] ?? ''));
            if ($source !== 'project.confirmed_lines' && ! str_starts_with($token, 'project-pending:')) {
                continue;
            }
            $code = trim((string) ($row['project_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $confirmedByProject[$code][self::pendingContentKey($row)] = true;
        }

        $byContent = [];
        foreach ($map as $row) {
            $code = trim((string) ($row['project_code'] ?? ''));
            $token = trim((string) ($row['token'] ?? ''));
            $source = trim((string) ($row['source'] ?? ''));
            $contentKey = self::pendingContentKey($row);

            if ($code !== '' && isset($confirmedByProject[$code])) {
                $isConfirmedCard = $source === 'project.confirmed_lines' || str_starts_with($token, 'project-pending:');
                if (! $isConfirmedCard && ! isset($confirmedByProject[$code][$contentKey])) {
                    // Local/demo handoff that does not match project pending lines.
                    continue;
                }
            }

            if (! isset($byContent[$contentKey])) {
                $byContent[$contentKey] = $row;
                continue;
            }

            $prev = $byContent[$contentKey];
            if (self::pendingRowPreferenceScore($row) > self::pendingRowPreferenceScore($prev)) {
                $byContent[$contentKey] = self::mergePendingRowDetails($row, $prev);
            } else {
                $byContent[$contentKey] = self::mergePendingRowDetails($prev, $row);
            }
        }

        $merged = array_values($byContent);
        usort($merged, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return array_slice($merged, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function persistToDatabase(string $token, array $payload, string $status): void
    {
        if (! Schema::hasTable('withdraw_project_handoff')) {
            return;
        }

        $now = now();
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $row = [
            'token' => $token,
            'status' => $status,
            'project_id' => trim((string) ($payload['project_id'] ?? '')) ?: null,
            'project_code' => trim((string) ($payload['project_code'] ?? '')) ?: null,
            'project_name' => trim((string) ($payload['project_name'] ?? '')) ?: null,
            'activity_id' => trim((string) ($payload['activity_id'] ?? '')) ?: null,
            'activity_name' => trim((string) ($payload['activity_name'] ?? '')) ?: null,
            'applicant_staff_id' => trim((string) ($payload['applicant_staff_id'] ?? '')) ?: null,
            'item_count' => count($items),
            'project_amount' => isset($payload['project_amount']) ? (float) $payload['project_amount'] : null,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            'expires_at' => $now->copy()->addDays(self::DB_RETENTION_DAYS),
            'consumed_at' => $status === self::STATUS_PENDING ? null : $now,
            'updated_at' => $now,
        ];

        $exists = DB::table('withdraw_project_handoff')->where('token', $token)->exists();
        if ($exists) {
            DB::table('withdraw_project_handoff')->where('token', $token)->update($row);
        } else {
            $row['created_at'] = $now;
            DB::table('withdraw_project_handoff')->insert($row);
        }

        self::persistToProjectConfirm($token, $payload, $status, $row);
    }

    /**
     * Mirror pending confirms into project schema for the project-app API.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $row
     */
    private static function persistToProjectConfirm(string $token, array $payload, string $status, array $row): void
    {
        if (! self::hasProjectConfirmTable()) {
            return;
        }

        $projectRow = [
            'token' => $token,
            'status' => $status,
            'project_id' => $row['project_id'] ?? null,
            'project_code' => $row['project_code'] ?? null,
            'project_name' => $row['project_name'] ?? null,
            'activity_id' => $row['activity_id'] ?? null,
            'activity_name' => $row['activity_name'] ?? null,
            'applicant_staff_id' => $row['applicant_staff_id'] ?? null,
            'item_count' => (int) ($row['item_count'] ?? 0),
            'project_amount' => $row['project_amount'] ?? null,
            'payload_json' => $row['payload_json'] ?? '{}',
            'resume_url' => self::formUrl($token),
            'expires_at' => $row['expires_at'] ?? null,
            'consumed_at' => $row['consumed_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? now(),
        ];

        $exists = DB::table('project.pbm_withdraw_confirm')->where('token', $token)->exists();
        if ($exists) {
            DB::table('project.pbm_withdraw_confirm')->where('token', $token)->update($projectRow);
        } else {
            $projectRow['created_at'] = $row['created_at'] ?? now();
            DB::table('project.pbm_withdraw_confirm')->insert($projectRow);
        }
    }

    private static function hasProjectConfirmTable(): bool
    {
        try {
            return Schema::hasTable('project.pbm_withdraw_confirm');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function loadPendingPayloadFromDatabase(string $token): ?array
    {
        if (! Schema::hasTable('withdraw_project_handoff')) {
            return null;
        }

        $row = DB::table('withdraw_project_handoff')
            ->where('token', $token)
            ->where('status', self::STATUS_PENDING)
            ->where(function ($inner) {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $row) {
            return null;
        }

        $payload = json_decode((string) ($row->payload_json ?? ''), true);

        return is_array($payload) ? $payload : null;
    }

    private static function markDatabaseStatus(string $token, string $status): void
    {
        if (! Schema::hasTable('withdraw_project_handoff')) {
            return;
        }

        $payload = [
            'status' => $status,
            'updated_at' => now(),
        ];
        if (in_array($status, [self::STATUS_CONSUMED, self::STATUS_DISMISSED], true)) {
            $payload['consumed_at'] = now();
        }

        DB::table('withdraw_project_handoff')
            ->where('token', $token)
            ->update($payload);

        if (self::hasProjectConfirmTable()) {
            DB::table('project.pbm_withdraw_confirm')
                ->where('token', $token)
                ->update($payload);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function loadPendingPayloadFromProjectConfirm(string $token): ?array
    {
        if (! self::hasProjectConfirmTable()) {
            return null;
        }

        $row = DB::table('project.pbm_withdraw_confirm')
            ->where('token', $token)
            ->where('status', self::STATUS_PENDING)
            ->where(function ($inner) {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $row) {
            return null;
        }

        $payload = json_decode((string) ($row->payload_json ?? ''), true);

        return is_array($payload) ? $payload : null;
    }

    private static function purgeExpiredPending(): void
    {
        if (! Schema::hasTable('withdraw_project_handoff')) {
            return;
        }

        DB::table('withdraw_project_handoff')
            ->where('status', self::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => self::STATUS_DISMISSED,
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);

        if (self::hasProjectConfirmTable()) {
            DB::table('project.pbm_withdraw_confirm')
                ->where('status', self::STATUS_PENDING)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->update([
                    'status' => self::STATUS_DISMISSED,
                    'consumed_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function normalize(array $raw): array
    {
        $raw = self::unwrapNestedPayload($raw);
        $projectId = self::firstString($raw, ['project_id', 'id_project']);
        $projectCode = self::firstString($raw, ['project_code', 'code_pre_project', 'code']);
        $projectName = self::firstString($raw, ['project_name', 'name_project', 'name']);
        $activityId = self::firstString($raw, ['activity_id', 'id_activity']);
        $activityName = self::firstString($raw, ['activity_name', 'name_activity']);
        $activityDetail = self::firstString($raw, [
            'activity_detail',
            'activity_details',
            'project_activity_detail',
            'detail_activity',
            'รายละเอียดการจัดกิจกรรม',
            'รายละเอียดการดำเนินการ',
            'detail',
            'description',
        ]);
        $subject = self::firstString($raw, ['subject', 'withdraw_subject']);
        if ($subject === '' && $activityName !== '') {
            $subject = 'ขออนุมัติเบิกจ่าย'.$activityName;
        }

        $budgetManageId = (int) self::firstString($raw, ['budget_manage_id', 'budget_id']);
        $budgetManageCode = self::firstString($raw, ['budget_manage_code', 'budget_code']);
        // จำนวนเงินโครงการต้องมาจาก payload ของระบบโครงการ (ชื่อ/รหัสโครงการมาทางเดียวกัน)
        $projectAmount = self::firstAmount($raw, [
            'project_amount',
            'project_budget_amount',
            'project_budget_total',
            'project_total',
            'project_total_amount',
            'budget_total',
            'total_budget',
            'approved_budget',
            'estimated_total',
            'estimate_total',
            'estimated_amount',
            'expense_estimate',
            'expense_total',
            'all_total',
            'all_total_sum',
            'ALL_total_sum',
            'budget_amount',
            'amount_project',
            'projectAmount',
            'budgetTotal',
            'total_amount',
            'total',
            'amount',
        ]);

        $itemsRaw = $raw['items'] ?? $raw['disbursement_items'] ?? $raw['lines'] ?? [];
        if (is_string($itemsRaw)) {
            $decodedItems = json_decode($itemsRaw, true);
            $itemsRaw = is_array($decodedItems) ? $decodedItems : [];
        }
        if ((! is_array($itemsRaw) || $itemsRaw === []) && isset($raw['items_json'])) {
            $decodedItems = is_string($raw['items_json'])
                ? json_decode($raw['items_json'], true)
                : $raw['items_json'];
            $itemsRaw = is_array($decodedItems) ? $decodedItems : [];
        }
        if (! is_array($itemsRaw)) {
            $itemsRaw = [];
        }

        $items = [];
        foreach ($itemsRaw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $categoryCode = self::firstString($row, ['category_code', 'category', 'pay_code', 'budget_pay_code']);
            $categoryName = self::firstString($row, ['category_name', 'pay_name', 'budget_pay_name']);
            $expenseName = self::firstString($row, ['expense_name', 'expense_chart_name', 'item_name']);
            $description = self::firstString($row, ['name', 'description', 'detail', 'note']);
            $month = self::normalizeMonthLabel(self::firstString($row, ['month', 'month_key', 'period']));
            $amount = self::toAmount($row['amount'] ?? $row['disburse'] ?? $row['withdraw_amount'] ?? 0);
            $itemName = $description !== '' ? $description : ($expenseName !== '' ? $expenseName : $categoryName);
            $itemName = self::stripMonthSuffix($itemName);

            if ($itemName === '' && $amount <= 0 && $categoryCode === '') {
                continue;
            }

            $itemBudgetId = (int) self::firstString($row, ['budget_manage_id', 'budget_id']);
            $itemBudgetCode = self::firstString($row, ['budget_manage_code', 'budget_code']);

            $items[] = [
                'id' => self::firstString($row, ['id', 'item_id', 'project_item_id']),
                'category_code' => $categoryCode,
                'category_name' => $categoryName,
                'expense_chart_id' => (int) self::firstString($row, ['expense_chart_id']),
                'expense_name' => $expenseName,
                'month' => $month,
                'name' => $itemName,
                'amount' => $amount,
                'budget_manage_id' => $itemBudgetId > 0 ? $itemBudgetId : $budgetManageId,
                'budget_manage_code' => $itemBudgetCode !== '' ? $itemBudgetCode : $budgetManageCode,
            ];
        }

        return [
            'source' => 'project',
            'project_id' => $projectId,
            'project_code' => $projectCode,
            'project_name' => $projectName,
            'activity_id' => $activityId,
            'activity_name' => $activityName,
            'activity_detail' => $activityDetail,
            'subject' => $subject,
            'budget_manage_id' => $budgetManageId,
            'budget_manage_code' => $budgetManageCode,
            'project_amount' => $projectAmount,
            'applicant_staff_id' => self::firstString($raw, ['applicant_staff_id', 'staff_code', 'PharCode']),
            'items' => $items,
        ];
    }

    /**
     * ยอดงบประมาณของโครงการ จาก API detailProjectPBM (budget_total)
     */
    public static function resolveProjectAmount(string $projectId = '', string $projectCode = ''): float
    {
        $code = trim($projectCode);
        if ($code === '') {
            $code = trim($projectId);
        }
        if ($code === '') {
            return 0.0;
        }

        try {
            $cached = Cache::remember(
                'withdraw.project.amount.v1.'.md5($code),
                60,
                static fn (): float => self::fetchProjectAmountFromDetailApi($code)
            );

            return is_numeric($cached) ? (float) $cached : 0.0;
        } catch (\Throwable) {
            return self::fetchProjectAmountFromDetailApi($code);
        }
    }

    public static function fetchProjectAmountFromDetailApi(string $projectCode): float
    {
        $code = trim($projectCode);
        if ($code === '') {
            return 0.0;
        }

        $url = trim((string) config(
            'services.project_pbm_api.detail_url',
            'https://api.pharmacy.cmu.ac.th/api/detailProjectPBM'
        ));
        if ($url === '') {
            return 0.0;
        }

        $token = self::resolveApiBearerToken();
        if ($token === '') {
            Log::warning('ProjectWithdrawHandoff: empty API token for detailProjectPBM');

            return 0.0;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->withToken($token)
                ->get($url, ['code_pre_project' => $code]);

            if (! $response->successful()) {
                Log::warning('ProjectWithdrawHandoff: detailProjectPBM failed', [
                    'status' => $response->status(),
                    'code_pre_project' => $code,
                ]);

                return 0.0;
            }

            $json = $response->json();
            if (! is_array($json)) {
                return 0.0;
            }

            $data = $json['data'] ?? null;
            if (is_array($data) && array_is_list($data) && isset($data[0]) && is_array($data[0])) {
                $data = $data[0];
            }
            if (! is_array($data)) {
                return 0.0;
            }

            return self::firstAmount($data, [
                'budget_total',
                'approved_budget',
                'project_amount',
                'budget_amount',
                'estimated_total',
                'total_budget',
            ]);
        } catch (\Throwable $e) {
            Log::warning('ProjectWithdrawHandoff: detailProjectPBM exception', [
                'code_pre_project' => $code,
                'message' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    private static function resolveApiBearerToken(): string
    {
        $tokenUrl = trim((string) (
            config('services.staff_rx_api.token_refresh_url')
            ?: config('services.auth_api.token_refresh_url', '')
            ?: env('STAFF_RX_TOKEN_REFRESH_URL', 'https://api.pharmacy.cmu.ac.th/api/tokenRefresh')
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
                        return str_starts_with($token, 'Bearer ') ? trim(substr($token, 7)) : $token;
                    }
                }
            }

            $body = trim((string) $response->body());
            if ($body !== '' && ! str_starts_with($body, '{') && ! str_starts_with($body, '[')) {
                return str_starts_with($body, 'Bearer ') ? trim(substr($body, 7)) : $body;
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }

    public static function resolveProjectNumericId(string $projectId = '', string $projectCode = ''): int
    {
        $id = trim($projectId);
        $code = trim($projectCode);

        if ($id !== '' && ctype_digit($id)) {
            return (int) $id;
        }

        try {
            if ($code !== '') {
                $numericId = (int) (DB::table('project.pre_project')
                    ->where('code_pre_project', $code)
                    ->value('id') ?? 0);
                if ($numericId > 0) {
                    return $numericId;
                }
            }

            if ($id !== '' && ! ctype_digit($id)) {
                return (int) (DB::table('project.pre_project')
                    ->where('code_pre_project', $id)
                    ->value('id') ?? 0);
            }
        } catch (\Throwable) {
            return 0;
        }

        return 0;
    }

    public static function encodePayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($json) || $json === '') {
            return '';
        }

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    /**
     * รับได้ทั้ง JSON ดิบ และ base64url(JSON) ที่ใช้กับลายเซ็น PBM
     *
     * @return array<string, mixed>|null
     */
    public static function decodePayload(string $payloadInput): ?array
    {
        $payloadInput = trim($payloadInput);
        if ($payloadInput === '') {
            return null;
        }

        $asJson = json_decode($payloadInput, true);
        if (is_array($asJson)) {
            return $asJson;
        }

        $normalized = strtr($payloadInput, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if (! is_string($decoded) || $decoded === '') {
            return null;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload) ? $payload : null;
    }

    public static function signatureFor(string $payloadInput): string
    {
        $secret = (string) config('pbm_bridge.secret');
        if ($secret === '') {
            return '';
        }

        return hash_hmac('sha256', $payloadInput, hash('sha256', $secret));
    }

    public static function hasValidSignature(string $payloadInput, string $signatureInput): bool
    {
        $expected = self::signatureFor($payloadInput);
        if ($expected === '' || trim($signatureInput) === '') {
            return false;
        }

        return hash_equals($expected, trim($signatureInput));
    }

    public static function formUrl(string $token): string
    {
        return url('/withdraw/project').'?'.http_build_query(['handoff' => $token]);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private static function unwrapNestedPayload(array $raw): array
    {
        foreach (['payload_json', 'payload', 'data', 'json', 'body'] as $key) {
            if (! array_key_exists($key, $raw)) {
                continue;
            }
            $value = $raw[$key];
            $nested = null;
            if (is_array($value)) {
                $nested = $value;
            } elseif (is_string($value) && trim($value) !== '') {
                $decoded = self::decodePayload($value);
                if (is_array($decoded)) {
                    $nested = $decoded;
                }
            }
            if (! is_array($nested)) {
                continue;
            }

            // Nested/signed payload must win over flat form fields (outer items often has only 1 row).
            $merged = array_merge($raw, $nested);
            foreach (['payload_json', 'payload', 'data', 'json', 'body', 'sig', 'signature'] as $wrapperKey) {
                unset($merged[$wrapperKey]);
            }

            return self::preferRicherItems($merged, $raw, $nested);
        }

        return $raw;
    }

    /**
     * Keep the longest usable items list when both outer and nested provide items.
     *
     * @param  array<string, mixed>  $merged
     * @param  array<string, mixed>  $outer
     * @param  array<string, mixed>  $nested
     * @return array<string, mixed>
     */
    private static function preferRicherItems(array $merged, array $outer, array $nested): array
    {
        $outerItems = self::coerceItemsList($outer['items'] ?? $outer['disbursement_items'] ?? $outer['lines'] ?? $outer['items_json'] ?? null);
        $nestedItems = self::coerceItemsList($nested['items'] ?? $nested['disbursement_items'] ?? $nested['lines'] ?? $nested['items_json'] ?? null);

        if (count($nestedItems) >= count($outerItems) && $nestedItems !== []) {
            $merged['items'] = $nestedItems;
        } elseif ($outerItems !== []) {
            $merged['items'] = $outerItems;
        }

        return $merged;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function coerceItemsList(mixed $items): array
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($items)) {
            return [];
        }

        $list = [];
        foreach ($items as $row) {
            if (is_array($row)) {
                $list[] = $row;
            }
        }

        return $list;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $keys
     */
    private static function firstString(array $source, array $keys): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $source)) {
                continue;
            }
            $value = $source[$key];
            if (is_scalar($value)) {
                $text = trim((string) $value);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    /**
     * อ่านจำนวนเงินจาก payload ของระบบโครงการ (รองรับคอมมา และชื่อฟิลด์หลายแบบ)
     *
     * @param  array<string, mixed>  $source
     * @param  list<string>  $keys
     */
    public static function firstAmount(array $source, array $keys): float
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $source)) {
                continue;
            }
            $amount = self::toAmount($source[$key]);
            if ($amount > 0) {
                return $amount;
            }
        }

        return 0.0;
    }

    public static function toAmount(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : 0.0;
        }
        if (! is_scalar($value)) {
            return 0.0;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        // รองรับทั้ง 18251.50 และ 18,251.50
        $normalized = str_replace([',', ' ', 'บาท'], '', $text);
        $normalized = preg_replace('/[^\d.\-]/', '', $normalized) ?? '';
        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            return 0.0;
        }

        $amount = (float) $normalized;

        return is_finite($amount) ? $amount : 0.0;
    }

    private static function stripMonthSuffix(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $months = array_unique(array_values(self::MONTH_LABELS));
        foreach ($months as $month) {
            $suffix = ' ('.$month.')';
            if (str_ends_with($name, $suffix)) {
                return trim(substr($name, 0, -strlen($suffix)));
            }
        }

        return $name;
    }

    private static function normalizeMonthLabel(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        $lookup = mb_strtolower($value, 'UTF-8');
        if (isset(self::MONTH_LABELS[$value])) {
            return self::MONTH_LABELS[$value];
        }
        if (isset(self::MONTH_LABELS[$lookup])) {
            return self::MONTH_LABELS[$lookup];
        }

        return $value;
    }
}
