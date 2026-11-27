<?php
/**
 * Pharmacy CMU Portal Integration Standard — Front Controller
 * Base URL: https://pbm.pharmacy.cmu.ac.th/application/project/api/portal
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', '0');

require_once dirname(__DIR__, 2) . '/config/portal.php';
require_once dirname(__DIR__, 2) . '/fn/portal_api_service.php';
require_once dirname(__DIR__, 2) . '/fn/portal_api_actions.php';
require_once dirname(__DIR__, 2) . '/fn/portal_webhook.php';

$requestId = 'req_' . bin2hex(random_bytes(8));

$sendJson = static function (int $status, array $payload, array $extraHeaders = []): void {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    foreach ($extraHeaders as $k => $v) {
        header($k . ': ' . $v);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$sendOk = static function (array $data, ?array $feedMeta = null) use ($sendJson, $requestId): void {
    $meta = [
        'generated_at' => pm_portal_now_iso(),
        'request_id' => $requestId,
    ];
    if ($feedMeta !== null) {
        $meta['next_cursor'] = $feedMeta['next_cursor'];
        $meta['snapshot_id'] = $feedMeta['snapshot_id'];
    }
    $sendJson(200, ['data' => $data, 'meta' => $meta]);
};

$sendErr = static function (int $status, string $code, string $message, array $extraHeaders = []) use ($sendJson, $requestId): void {
    $sendJson($status, [
        'error' => [
            'code' => $code,
            'message' => $message,
            'request_id' => $requestId,
        ],
    ], $extraHeaders);
};

// 1) Bearer Token authentication (constant-time, header only — never from query string)
$cfg = pm_portal_config();
$expectedToken = (string)($cfg['api_token'] ?? '');
$authHeader = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if ($authHeader === '' && function_exists('apache_request_headers')) {
    $hdrs = apache_request_headers();
    if (is_array($hdrs)) {
        foreach ($hdrs as $hk => $hv) {
            if (strtolower((string)$hk) === 'authorization') {
                $authHeader = (string)$hv;
                break;
            }
        }
    }
}
$providedToken = '';
if (preg_match('/^\s*Bearer\s+([A-Za-z0-9._~+\/-]+=*)\s*$/', $authHeader, $tm)) {
    $providedToken = $tm[1];
}
if ($expectedToken === '' || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    $sendErr(401, 'UNAUTHORIZED', 'โทเคนยืนยันตัวตนไม่ถูกต้องหรือยังไม่ได้ตั้งค่า');
}

// 2) Rate limit (>= 600 req/min per token)
$rateDir = pm_portal_ensure_cache_dir('ratelimit');
$bucketMin = (int)floor(time() / 60);
$rateFile = $rateDir . '/rl_' . hash('sha256', $expectedToken) . '_' . $bucketMin . '.cnt';
$rlCount = 0;
$fp = @fopen($rateFile, 'c+');
if ($fp !== false) {
    if (@flock($fp, LOCK_EX)) {
        $rlCount = (int)trim((string)stream_get_contents($fp));
        $rlCount++;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)$rlCount);
        fflush($fp);
        @flock($fp, LOCK_UN);
    }
    fclose($fp);
}
if ($rlCount > (int)$cfg['rate_limit_per_min']) {
    $retryAfter = max(1, 60 - (time() % 60));
    $sendErr(429, 'RATE_LIMITED', 'เรียกใช้งานเกินอัตราที่กำหนด กรุณารอสักครู่แล้วลองใหม่', ['Retry-After' => (string)$retryAfter]);
}

// 3) Parse route relative to /api/portal
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$uriPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$relPath = $uriPath;
if (preg_match('#/api/portal(/.*)?$#', $uriPath, $pm)) {
    $relPath = (string)($pm[1] ?? '/');
}
$relPath = '/' . ltrim($relPath, '/');
if ($relPath !== '/' && substr($relPath, -1) === '/') {
    $relPath = rtrim($relPath, '/');
}

// 4) Connect databases
try {
    $dbConfigPath = dirname(__DIR__, 3) . '/config/config_db.php';
    if (!is_file($dbConfigPath)) {
        throw new RuntimeException('missing config_db.php');
    }
    require $dbConfigPath;
    if (!isset($db_Project, $db_person) || !($db_Project instanceof mysqli) || !($db_person instanceof mysqli)) {
        throw new RuntimeException('database connections unavailable');
    }
} catch (Throwable $e) {
    error_log('[pbm_portal_api] db connect error: ' . $e->getMessage());
    $sendErr(503, 'SOURCE_UNAVAILABLE', 'ระบบฐานข้อมูลต้นทางไม่พร้อมใช้งานชั่วคราว');
}

$parseLimit = static function () use ($sendErr): int {
    if (!isset($_GET['limit']) || $_GET['limit'] === '') {
        return 50;
    }
    $raw = (string)$_GET['limit'];
    if (!preg_match('/^(?:[1-9][0-9]?|100)$/', $raw)) {
        $sendErr(422, 'INVALID_REQUEST', 'ค่า limit ต้องเป็นจำนวนเต็มระหว่าง 1 ถึง 100');
    }
    return (int)$raw;
};

$parseCursor = static function () use ($sendErr): ?string {
    if (!isset($_GET['cursor']) || $_GET['cursor'] === '') {
        return null;
    }
    if (!is_string($_GET['cursor']) || strlen($_GET['cursor']) > 2048) {
        $sendErr(422, 'INVALID_REQUEST', 'รูปแบบ cursor ไม่ถูกต้อง');
    }
    return $_GET['cursor'];
};

$parseSnapshotId = static function () use ($sendErr): ?string {
    if (!isset($_GET['snapshot_id']) || $_GET['snapshot_id'] === '') {
        return null;
    }
    if (!is_string($_GET['snapshot_id']) || strlen($_GET['snapshot_id']) > 128) {
        $sendErr(422, 'INVALID_REQUEST', 'รูปแบบ snapshot_id ไม่ถูกต้อง');
    }
    return $_GET['snapshot_id'];
};

try {
    // GET /health
    if ($method === 'GET' && $relPath === '/health') {
        foreach (['pre_project', 'project_include_request', 'project_change_request'] as $tbl) {
            $q = @mysqli_query($db_Project, "SELECT 1 FROM `{$tbl}` LIMIT 1");
            if ($q === false) {
                throw new RuntimeException('table check failed: ' . $tbl);
            }
        }
        $qP = @mysqli_query($db_person, "SELECT 1 FROM staff LIMIT 1");
        if ($qP === false) {
            throw new RuntimeException('person staff table check failed');
        }
        $sendOk([
            'system' => (string)$cfg['system_code'],
            'state' => 'ready',
        ]);
    }

    // GET /capabilities
    if ($method === 'GET' && $relPath === '/capabilities') {
        $sendOk([
            'actions' => (bool)$cfg['actions_enabled'],
        ]);
    }

    // GET /staff/{staffId}/records
    if ($method === 'GET' && preg_match('#^/staff/([^/]+)/records$#', $relPath, $m)) {
        $staffId = rawurldecode($m[1]);
        if (!pm_portal_validate_staff_id($staffId)) {
            $sendErr(422, 'INVALID_REQUEST', 'รูปแบบ staff_id ไม่ถูกต้อง (ต้องเป็นตัวเลขเท่านั้น)');
        }
        $limit = $parseLimit();
        $cursor = $parseCursor();
        $snapId = $parseSnapshotId();

        $proj = pm_portal_build_staff_projection($db_Project, $db_person, $staffId, true);
        $paged = pm_portal_paginate_snapshot($staffId, 'records', $proj['records'], $limit, $cursor, $snapId);
        if (!$paged['ok']) {
            $sendErr((int)$paged['status'], (string)$paged['error_code'], (string)$paged['message']);
        }
        $sendOk($paged['data'], [
            'next_cursor' => $paged['next_cursor'],
            'snapshot_id' => $paged['snapshot_id'],
        ]);
    }

    // GET /staff/{staffId}/work-items
    if ($method === 'GET' && preg_match('#^/staff/([^/]+)/work-items$#', $relPath, $m)) {
        $staffId = rawurldecode($m[1]);
        if (!pm_portal_validate_staff_id($staffId)) {
            $sendErr(422, 'INVALID_REQUEST', 'รูปแบบ staff_id ไม่ถูกต้อง (ต้องเป็นตัวเลขเท่านั้น)');
        }
        $limit = $parseLimit();
        $cursor = $parseCursor();
        $snapId = $parseSnapshotId();

        $proj = pm_portal_build_staff_projection($db_Project, $db_person, $staffId, true);
        $paged = pm_portal_paginate_snapshot($staffId, 'work-items', $proj['work_items'], $limit, $cursor, $snapId);
        if (!$paged['ok']) {
            $sendErr((int)$paged['status'], (string)$paged['error_code'], (string)$paged['message']);
        }
        $sendOk($paged['data'], [
            'next_cursor' => $paged['next_cursor'],
            'snapshot_id' => $paged['snapshot_id'],
        ]);
    }

    // GET /staff/{staffId}/events
    if ($method === 'GET' && preg_match('#^/staff/([^/]+)/events$#', $relPath, $m)) {
        $staffId = rawurldecode($m[1]);
        if (!pm_portal_validate_staff_id($staffId)) {
            $sendErr(422, 'INVALID_REQUEST', 'รูปแบบ staff_id ไม่ถูกต้อง (ต้องเป็นตัวเลขเท่านั้น)');
        }
        $limit = $parseLimit();
        $cursor = $parseCursor();
        $snapId = $parseSnapshotId();

        $proj = pm_portal_build_staff_projection($db_Project, $db_person, $staffId, true);
        $paged = pm_portal_paginate_snapshot($staffId, 'events', $proj['events'], $limit, $cursor, $snapId);
        if (!$paged['ok']) {
            $sendErr((int)$paged['status'], (string)$paged['error_code'], (string)$paged['message']);
        }
        $sendOk($paged['data'], [
            'next_cursor' => $paged['next_cursor'],
            'snapshot_id' => $paged['snapshot_id'],
        ]);
    }

    // GET /staff/{staffId}/summary
    if ($method === 'GET' && preg_match('#^/staff/([^/]+)/summary$#', $relPath, $m)) {
        $staffId = rawurldecode($m[1]);
        if (!pm_portal_validate_staff_id($staffId)) {
            $sendErr(422, 'INVALID_REQUEST', 'รูปแบบ staff_id ไม่ถูกต้อง (ต้องเป็นตัวเลขเท่านั้น)');
        }
        $proj = pm_portal_build_staff_projection($db_Project, $db_person, $staffId, true);
        $sendOk($proj['summary']);
    }

    // GET /staff/{staffId}/records/{recordId}
    if ($method === 'GET' && preg_match('#^/staff/([^/]+)/records/([^/]+)$#', $relPath, $m)) {
        $staffId = rawurldecode($m[1]);
        $recordId = rawurldecode($m[2]);
        if (!pm_portal_validate_staff_id($staffId)) {
            $sendErr(422, 'INVALID_REQUEST', 'รูปแบบ staff_id ไม่ถูกต้อง (ต้องเป็นตัวเลขเท่านั้น)');
        }
        if ($recordId === '' || mb_strlen($recordId, 'UTF-8') > 255) {
            $sendErr(422, 'INVALID_REQUEST', 'รหัสรายการไม่ถูกต้อง');
        }
        $detail = pm_portal_get_record_detail($db_Project, $db_person, $staffId, $recordId);
        if ($detail === null) {
            $sendErr(404, 'NOT_FOUND', 'ไม่พบรายการ หรือรายการไม่อยู่ในสิทธิ์ของบุคลากรนี้');
        }
        $sendOk($detail);
    }

    // POST /staff/{staffId}/work-items/{workItemId}/actions
    if ($method === 'POST' && preg_match('#^/staff/([^/]+)/work-items/([^/]+)/actions$#', $relPath, $m)) {
        $staffId = rawurldecode($m[1]);
        $workItemId = rawurldecode($m[2]);
        if (!pm_portal_validate_staff_id($staffId)) {
            $sendErr(422, 'INVALID_REQUEST', 'รูปแบบ staff_id ไม่ถูกต้อง (ต้องเป็นตัวเลขเท่านั้น)');
        }
        if ($workItemId === '' || mb_strlen($workItemId, 'UTF-8') > 255) {
            $sendErr(422, 'INVALID_REQUEST', 'รหัสงานไม่ถูกต้อง');
        }

        $idemKey = trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ''));
        if ($idemKey === '' || mb_strlen($idemKey, 'UTF-8') > 255) {
            $sendErr(422, 'INVALID_REQUEST', 'ต้องส่งส่วนหัว Idempotency-Key');
        }

        $rawBody = (string)file_get_contents('php://input');
        $body = json_decode($rawBody, true);
        if (!is_array($body)) {
            $sendErr(422, 'INVALID_REQUEST', 'รูปแบบข้อมูล JSON ไม่ถูกต้อง');
        }
        $allowedKeys = ['action', 'note', 'expected_revision'];
        foreach (array_keys($body) as $bk) {
            if (!in_array((string)$bk, $allowedKeys, true)) {
                $sendErr(422, 'INVALID_REQUEST', 'พบฟิลด์ที่ไม่อนุญาตในคำขอ');
            }
        }
        if (!isset($body['action'], $body['expected_revision']) || !array_key_exists('note', $body)) {
            $sendErr(422, 'INVALID_REQUEST', 'ข้อมูลคำขอไม่ครบถ้วน (ต้องระบุ action, note, expected_revision)');
        }
        if (!is_string($body['action']) || (!is_string($body['note']) && $body['note'] !== null) || !is_int($body['expected_revision'])) {
            $sendErr(422, 'INVALID_REQUEST', 'ชนิดข้อมูลของ action, note หรือ expected_revision ไม่ถูกต้อง');
        }

        $res = pm_portal_perform_action(
            $db_Project,
            $db_person,
            $staffId,
            $workItemId,
            $body['action'],
            (string)($body['note'] ?? ''),
            $body['expected_revision'],
            $idemKey
        );

        if ((int)$res['status'] !== 200) {
            $sendErr((int)$res['status'], (string)($res['error_code'] ?? 'INVALID_REQUEST'), (string)($res['message'] ?? 'ไม่สามารถดำเนินการได้'));
        }

        if (empty($res['replayed']) && !empty($res['affected_staff'])) {
            pm_portal_notify_staff_changed($res['affected_staff'], true);
        }

        $sendOk($res['body']);
    }

    $sendErr(404, 'NOT_FOUND', 'ไม่พบเส้นทาง API ที่ร้องขอ');
} catch (Throwable $e) {
    error_log('[pbm_portal_api] unhandled error: ' . $e->getMessage());
    $sendErr(503, 'SOURCE_UNAVAILABLE', 'ระบบต้นทางขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง');
}
