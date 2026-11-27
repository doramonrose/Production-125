<?php
/**
 * Portal Web Resolver & Authenticated Attachment Download
 * Routes:
 *   GET /application/project/portal/records/{recordId}
 *   GET /application/project/portal/records/{recordId}/work
 *   GET /application/project/portal/records/{recordId}/attachments/{fileId}
 */
declare(strict_types=1);

session_start();
date_default_timezone_set('Asia/Bangkok');

require_once dirname(__DIR__) . '/config/portal.php';
require_once dirname(__DIR__) . '/fn/portal_api_service.php';
require_once dirname(__DIR__, 2) . '/config/config_db.php';

$renderHtmlErr = static function (int $status, string $title, string $message): void {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: private, no-store');
    $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $m = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo "<!doctype html><html lang=\"th\"><head><meta charset=\"utf-8\"><title>{$t}</title>"
        . "<style>body{font-family:system-ui,sans-serif;background:#f8fafc;color:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}"
        . ".box{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:28px 32px;max-width:480px;box-shadow:0 4px 20px rgba(0,0,0,.06);}"
        . "h1{margin:0 0 10px;font-size:20px;color:#b91c1c;}p{margin:0 0 16px;line-height:1.6;color:#334155;}"
        . "a{display:inline-block;padding:8px 16px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-size:14px;}</style></head>"
        . "<body><div class=\"box\"><h1>{$t}</h1><p>{$m}</p><a href=\"/application/project/\">กลับหน้าหลักระบบบริหารโครงการ</a></div></body></html>";
    exit;
};

$uriPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$relPath = $uriPath;
if (preg_match('#/portal(/.*)?$#', $uriPath, $pm)) {
    $relPath = (string)($pm[1] ?? '/');
}
$relPath = '/' . ltrim($relPath, '/');
if ($relPath !== '/' && substr($relPath, -1) === '/') {
    $relPath = rtrim($relPath, '/');
}

// 1) ตรวจ session ของระบบต้นทาง — ถ้ายังไม่ login ให้เก็บ return URL อย่างปลอดภัยแล้วพาไป login
$sessCode = trim((string)($_SESSION['code'] ?? ''));
$sessEmail = trim((string)($_SESSION['r_email'] ?? ''));
$me = null;
if ($sessCode !== '' && $sessEmail !== '' && isset($db_person) && $db_person instanceof mysqli) {
    $st = mysqli_prepare($db_person, "SELECT id, code, email, job_code, unit_code FROM staff WHERE code = ? AND email = ? AND work_status IN ('0','1') LIMIT 1");
    if ($st) {
        mysqli_stmt_bind_param($st, 'ss', $sessCode, $sessEmail);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $me = $rs ? mysqli_fetch_assoc($rs) : null;
        mysqli_stmt_close($st);
    }
}

if (!$me) {
    // ตรวจ return URL ให้เข้มงวด (ป้องกัน open redirect)
    if (preg_match('#^/application/project/portal/records/[A-Za-z0-9._%-]+(?:/work|/attachments/[A-Za-z0-9._%-]+)?$#', $uriPath)) {
        $_SESSION['pbm_portal_return_url'] = $uriPath;
    }
    header('Location: /application/project/login_cmu.php?side=user');
    exit;
}

$myStaffId = (string)((int)($me['id'] ?? 0));
$isPlanSide = (trim((string)($me['job_code'] ?? '')) === '120103');
$sidePrefix = $isPlanSide ? '/application/project/Plan/page.php' : '/application/project/User/page.php';

// 2) Attachment download: /records/{recordId}/attachments/{fileId}
if (preg_match('#^/records/([^/]+)/attachments/([^/]+)$#', $relPath, $m)) {
    $recordId = rawurldecode($m[1]);
    $fileId = rawurldecode($m[2]);

    $detail = pm_portal_get_record_detail($db_Project, $db_person, $myStaffId, $recordId);
    if ($detail === null && !$isPlanSide) {
        $renderHtmlErr(403, 'ไม่มีสิทธิ์เข้าถึงเอกสารแนบ (403)', 'บัญชีของคุณไม่มีสิทธิ์เข้าถึงเอกสารแนบของรายการนี้');
    }

    $diskPath = null;
    $downloadName = '';

    if (preg_match('/^PF-([0-9]+)$/', $fileId, $fm) && preg_match('/^PROJ-([0-9]+)$/', $recordId, $rm)) {
        $fid = (int)$fm[1];
        $pid = (int)$rm[1];
        $q = mysqli_query($db_Project, "SELECT files, stored_file FROM project_file WHERE id='{$fid}' AND code_pre_project='{$pid}' AND IFNULL(status,'1') NOT IN ('99','99.0') LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        if (!$row) {
            $renderHtmlErr(404, 'ไม่พบเอกสารแนบ (404)', 'ไม่พบไฟล์เอกสารที่ร้องขอหรือไฟล์ถูกลบแล้ว');
        }
        $stored = basename(trim((string)($row['stored_file'] ?? '')));
        if ($stored === '') {
            $stored = basename(trim((string)($row['files'] ?? '')));
        }
        $downloadName = trim((string)($row['files'] ?? '')) ?: $stored;
        $baseDir = realpath(dirname(__DIR__) . '/document/project_files');
        if ($baseDir !== false && $stored !== '') {
            $cand = realpath($baseDir . '/' . $stored);
            if ($cand !== false && strpos($cand, $baseDir . DIRECTORY_SEPARATOR) === 0 && is_file($cand)) {
                $diskPath = $cand;
            }
        }
    } elseif (preg_match('/^CHGF-([0-9]+)$/', $fileId, $fm) && preg_match('/^CHG-([0-9]+)$/', $recordId, $rm)) {
        $fid = (int)$fm[1];
        $rid = (int)$rm[1];
        $q = mysqli_query($db_Project, "SELECT original_name, stored_file FROM project_change_request_file WHERE id='{$fid}' AND request_id='{$rid}' AND status='1' LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        if (!$row) {
            $renderHtmlErr(404, 'ไม่พบเอกสารแนบ (404)', 'ไม่พบไฟล์เอกสารที่ร้องขอหรือไฟล์ถูกลบแล้ว');
        }
        $stored = basename(trim((string)($row['stored_file'] ?? '')));
        $downloadName = trim((string)($row['original_name'] ?? '')) ?: $stored;
        foreach (['change_requests', 'project_files'] as $sub) {
            $baseDir = realpath(dirname(__DIR__) . '/document/' . $sub);
            if ($baseDir !== false && $stored !== '') {
                $cand = realpath($baseDir . '/' . $stored);
                if ($cand !== false && strpos($cand, $baseDir . DIRECTORY_SEPARATOR) === 0 && is_file($cand)) {
                    $diskPath = $cand;
                    break;
                }
            }
        }
    } else {
        $renderHtmlErr(404, 'ไม่พบเอกสารแนบ (404)', 'รหัสเอกสารแนบไม่ถูกต้อง');
    }

    if ($diskPath === null || !is_readable($diskPath)) {
        $renderHtmlErr(404, 'ไม่พบไฟล์บนเซิร์ฟเวอร์ (404)', 'ไม่พบไฟล์ต้นฉบับของเอกสารแนบนี้');
    }

    $mime = pm_portal_guess_mime($downloadName);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($diskPath));
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    $safeAscii = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'attachment';
    header('Content-Disposition: inline; filename="' . $safeAscii . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    readfile($diskPath);
    exit;
}

// 3) Record / Work resolver: /records/{recordId} หรือ /records/{recordId}/work
if (preg_match('#^/records/([^/]+)(/work)?$#', $relPath, $m)) {
    $recordId = rawurldecode($m[1]);
    $wantWork = !empty($m[2]);

    // ตรวจว่ามีรายการจริงในฐานหรือไม่ (เพื่อแยก 404 ออกจาก 403)
    $exists = false;
    $yearParam = '';
    $chgProjectId = 0;
    if (preg_match('/^PROJ-([0-9]+)$/', $recordId, $rm)) {
        $pid = (int)$rm[1];
        $q = mysqli_query($db_Project, "SELECT id, year_project FROM pre_project WHERE id='{$pid}' AND status='1' LIMIT 1");
        if ($q && ($r = mysqli_fetch_assoc($q))) {
            $exists = true;
            $yearParam = trim((string)($r['year_project'] ?? ''));
        }
    } elseif (preg_match('/^INC-([0-9]+)$/', $recordId, $rm)) {
        $rid = (int)$rm[1];
        $q = mysqli_query($db_Project, "SELECT id, budget_year FROM project_include_request WHERE id='{$rid}' LIMIT 1");
        if ($q && ($r = mysqli_fetch_assoc($q))) {
            $exists = true;
            $yearParam = trim((string)($r['budget_year'] ?? ''));
        }
    } elseif (preg_match('/^CHG-([0-9]+)$/', $recordId, $rm)) {
        $rid = (int)$rm[1];
        $q = mysqli_query($db_Project, "SELECT id, project_id FROM project_change_request WHERE id='{$rid}' AND status='1' LIMIT 1");
        if ($q && ($r = mysqli_fetch_assoc($q))) {
            $exists = true;
            $chgProjectId = (int)($r['project_id'] ?? 0);
        }
    }

    if (!$exists) {
        $renderHtmlErr(404, 'ไม่พบรายการ (404)', 'ไม่พบรายการที่ร้องขอ หรือรายการถูกลบออกจากระบบแล้ว');
    }

    $detail = pm_portal_get_record_detail($db_Project, $db_person, $myStaffId, $recordId);
    if ($detail === null && !$isPlanSide) {
        $renderHtmlErr(403, 'ไม่มีสิทธิ์เข้าถึงรายการนี้ (403)', 'บัญชีของคุณไม่ได้เป็นผู้ขอ ผู้รับผิดชอบ หรือผู้มีสิทธิ์ดูรายการนี้');
    }

    $pendingWi = null;
    $doneWi = null;
    if (is_array($detail)) {
        foreach (($detail['work_items'] ?? []) as $wi) {
            if (($wi['state'] ?? '') === 'pending' && $pendingWi === null) {
                $pendingWi = $wi;
            } elseif (($wi['state'] ?? '') === 'completed' && $doneWi === null) {
                $doneWi = $wi;
            }
        }
    }

    $yq = $yearParam !== '' ? ('&years_select=' . rawurlencode($yearParam)) : '';

    // ลำดับการพาไปหน้าปลายทางตามข้อ 5 ของคู่มือ
    if (preg_match('/^PROJ-([0-9]+)$/', $recordId, $rm)) {
        $pid = (int)$rm[1];
        // 1. มีงานค้างในรายการนี้
        if ($pendingWi !== null) {
            if (($pendingWi['type'] ?? '') === 'correct') {
                header('Location: ' . $sidePrefix . '?page=project_manage&op=add_project&project_id=' . $pid . $yq);
                exit;
            }
            $flow = (($pendingWi['type'] ?? '') === 'verify') ? 'report' : 'operate';
            header('Location: ' . $sidePrefix . '?page=project_approve&flow=' . $flow . '&tab=pending&open_flow=' . $flow . '&open_project_id=' . $pid . '&project_id=' . $pid . $yq);
            exit;
        }
        // 2. เป็นผู้ขอ / ผู้รับผิดชอบ / ดูรายละเอียดโครงการโดยตรง
        if (!$wantWork) {
            header('Location: ' . $sidePrefix . '?page=project_manage&op=add_project&project_id=' . $pid . $yq);
            exit;
        }
        // 4. เคยดำเนินการในรายการนี้
        if ($doneWi !== null) {
            $flow = (($doneWi['type'] ?? '') === 'verify') ? 'report' : 'operate';
            header('Location: ' . $sidePrefix . '?page=project_approve&flow=' . $flow . '&tab=done&open_flow=' . $flow . '&open_project_id=' . $pid . '&project_id=' . $pid . $yq);
            exit;
        }
        header('Location: ' . $sidePrefix . '?page=project_manage&op=add_project&project_id=' . $pid . $yq);
        exit;
    }

    if (preg_match('/^INC-([0-9]+)$/', $recordId, $rm)) {
        $rid = (int)$rm[1];
        if ($pendingWi !== null) {
            $tab = (($pendingWi['type'] ?? '') === 'correct') ? 'sent' : 'pending';
            header('Location: ' . $sidePrefix . '?page=project_approve&flow=include&tab=' . $tab . '&request_id=' . $rid . $yq);
            exit;
        }
        $isReq = is_array($detail) && ((string)($detail['record']['requester']['staff_id'] ?? '') === $myStaffId);
        if ($isReq) {
            header('Location: ' . $sidePrefix . '?page=project_approve&flow=include&tab=sent&request_id=' . $rid . $yq);
            exit;
        }
        $tab = ($doneWi !== null) ? 'done' : 'pending';
        header('Location: ' . $sidePrefix . '?page=project_approve&flow=include&tab=' . $tab . '&request_id=' . $rid . $yq);
        exit;
    }

    if (preg_match('/^CHG-([0-9]+)$/', $recordId, $rm)) {
        $rid = (int)$rm[1];
        if ($pendingWi !== null) {
            header('Location: ' . $sidePrefix . '?page=project_approve&flow=change&tab=pending&open_flow=change&open_project_id=' . $chgProjectId . '&change_request_id=' . $rid . '&project_id=' . $chgProjectId);
            exit;
        }
        if ($doneWi !== null) {
            header('Location: ' . $sidePrefix . '?page=project_approve&flow=change&tab=done&open_flow=change&open_project_id=' . $chgProjectId . '&change_request_id=' . $rid . '&project_id=' . $chgProjectId);
            exit;
        }
        header('Location: ' . $sidePrefix . '?page=project_manage&op=add_project&project_id=' . $chgProjectId . '&pm_scroll=pmChangeHistory');
        exit;
    }
}

$renderHtmlErr(404, 'ไม่พบหน้าปลายทาง (404)', 'ไม่พบเส้นทางที่ร้องขอในระบบบริหารโครงการ');
