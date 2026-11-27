<?php
/**
 * Core Projection, Detail, Snapshot & Cursor Service
 * Pharmacy CMU Portal Integration Standard — ระบบบริหารโครงการ (pbm_project)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/portal.php';

if (!function_exists('pm_portal_now_iso')) {
    function pm_portal_now_iso(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok')))->format(DateTimeInterface::RFC3339);
    }
}

if (!function_exists('pm_portal_to_rfc3339')) {
    function pm_portal_to_rfc3339(?string $dt, ?string $fallback = null): string
    {
        $tz = new DateTimeZone('Asia/Bangkok');
        $raw = trim((string)$dt);
        if ($raw !== '' && $raw !== '0000-00-00' && $raw !== '0000-00-00 00:00:00') {
            // แปลงปี พ.ศ. หากหลงมาใน datetime string
            if (preg_match('/^(\d{4})-(\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?)/', $raw, $m)) {
                $y = (int)$m[1];
                if ($y >= 2400) {
                    $raw = ($y - 543) . '-' . $m[2];
                }
            }
            try {
                $d = new DateTimeImmutable($raw, $tz);
                if ((int)$d->format('Y') >= 1970 && (int)$d->format('Y') <= 2100) {
                    return $d->setTimezone($tz)->format(DateTimeInterface::RFC3339);
                }
            } catch (Throwable $e) {
                // fall through
            }
        }
        if ($fallback !== null && $fallback !== '') {
            return pm_portal_to_rfc3339($fallback, null);
        }
        return pm_portal_now_iso();
    }
}

if (!function_exists('pm_portal_validate_staff_id')) {
    function pm_portal_validate_staff_id(string $staffId): bool
    {
        return (bool)preg_match('/^[0-9]{1,20}$/', $staffId);
    }
}

if (!function_exists('pm_portal_money')) {
    /**
     * @return array{amount:string,currency:string}
     */
    function pm_portal_money($amount, string $currency = 'THB'): array
    {
        $num = is_numeric($amount) ? (float)$amount : 0.0;
        return [
            'amount' => number_format($num, 2, '.', ''),
            'currency' => strtoupper($currency),
        ];
    }
}

if (!function_exists('pm_portal_trim_text')) {
    function pm_portal_trim_text(?string $text, int $maxLen = 0): string
    {
        $s = html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/\s+/u', ' ', trim($s)) ?? trim($s);
        if ($maxLen > 0 && mb_strlen($s, 'UTF-8') > $maxLen) {
            $s = rtrim(mb_substr($s, 0, $maxLen - 1, 'UTF-8')) . '…';
        }
        return $s;
    }
}

if (!function_exists('pm_portal_ensure_cache_dir')) {
    function pm_portal_ensure_cache_dir(string $sub = ''): string
    {
        $cfg = pm_portal_config();
        $base = rtrim((string)$cfg['cache_dir'], '/');
        $dir = $sub !== '' ? ($base . '/' . trim($sub, '/')) : $base;
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        return $dir;
    }
}

if (!function_exists('pm_portal_load_staff_map')) {
    /**
     * โหลดแผนที่ staff_id (person.staff.id = staff_list.id) ทั้งหมดครั้งเดียวต่อ request
     * @return array<string, array{staff_id:string,code:string,display_name:string,unit_code:string,job_code:string,email:string}>
     */
    function pm_portal_load_staff_map(mysqli $db_person): array
    {
        static $cache = [];
        $connKey = spl_object_id($db_person);
        if (isset($cache[$connKey])) {
            return $cache[$connKey];
        }
        $map = [];
        // ลองอ่านจาก staff_list ก่อน (มี name_prefix ครบ) ถ้าไม่มีค่อย fallback ไปที่ staff
        $q = @mysqli_query(
            $db_person,
            "SELECT id, code, IFNULL(name_prefix,'') AS prefix, IFNULL(fname,'') AS fname, IFNULL(lname,'') AS lname,
                    IFNULL(unit_code,'') AS unit_code, IFNULL(job_code,'') AS job_code, IFNULL(email,'') AS email
             FROM staff_list"
        );
        if (!$q) {
            $q = @mysqli_query(
                $db_person,
                "SELECT id, code, '' AS prefix, IFNULL(fname,'') AS fname, IFNULL(lname,'') AS lname,
                        IFNULL(unit_code,'') AS unit_code, IFNULL(job_code,'') AS job_code, IFNULL(email,'') AS email
                 FROM staff"
            );
        }
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $id = (string)((int)($r['id'] ?? 0));
            if ($id === '0') {
                continue;
            }
            $prefix = trim((string)($r['prefix'] ?? ''));
            $fn = trim((string)($r['fname'] ?? ''));
            $ln = trim((string)($r['lname'] ?? ''));
            $name = trim(($prefix !== '' ? $prefix : '') . $fn . ($ln !== '' ? ' ' . $ln : ''));
            if ($name === '') {
                $name = 'บุคลากร #' . $id;
            }
            $map[$id] = [
                'staff_id' => $id,
                'code' => trim((string)($r['code'] ?? '')),
                'display_name' => $name,
                'unit_code' => trim((string)($r['unit_code'] ?? '')),
                'job_code' => trim((string)($r['job_code'] ?? '')),
                'email' => trim((string)($r['email'] ?? '')),
            ];
        }
        $cache[$connKey] = $map;
        return $map;
    }
}

if (!function_exists('pm_portal_staff_person_obj')) {
    /**
     * @param array<string, array{staff_id:string,code:string,display_name:string,unit_code:string,job_code:string,email:string}> $staffMap
     * @return array{staff_id:?string,display_name:string}
     */
    function pm_portal_staff_person_obj($staffId, array $staffMap, bool $allowNull = false): ?array
    {
        $s = trim((string)$staffId);
        if ($s !== '' && ctype_digit($s) && (int)$s > 0 && isset($staffMap[$s])) {
            return [
                'staff_id' => $s,
                'display_name' => $staffMap[$s]['display_name'],
            ];
        }
        if ($allowNull) {
            return [
                'staff_id' => null,
                'display_name' => 'ไม่ระบุผู้ขอ',
            ];
        }
        return null;
    }
}

if (!function_exists('pm_portal_parse_chain_json')) {
    /**
     * แปลง JSON ลำดับผู้รับรอง/ผู้อนุมัติ เป็น [sort_order => ['staff_id' => int, 'position_title' => string]]
     * @return array<int, array{staff_id:int,position_title:string}>
     */
    function pm_portal_parse_chain_json(?string $raw): array
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return [];
        }
        $arr = json_decode($raw, true);
        if (!is_array($arr)) {
            return [];
        }
        $out = [];
        $seq = 0;
        foreach ($arr as $item) {
            if (!is_array($item)) {
                continue;
            }
            $seq++;
            $so = (int)($item['sort_order'] ?? 0);
            if ($so <= 0) {
                $so = $seq;
            }
            $sid = (int)($item['staff_id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $out[$so] = [
                'staff_id' => $sid,
                'position_title' => trim((string)($item['position_title'] ?? $item['position'] ?? '')),
            ];
        }
        ksort($out, SORT_NUMERIC);
        return $out;
    }
}

if (!function_exists('pm_portal_db_fingerprint')) {
    function pm_portal_db_fingerprint(mysqli $db_Project): string
    {
        $tables = [
            "SELECT 'pp', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(dates),'') FROM pre_project",
            "SELECT 'ir', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(COALESCE(updated_at,created_at)),'') FROM project_include_request",
            "SELECT 'cr', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(COALESCE(updated_at,created_at)),'') FROM project_change_request",
            "SELECT 'ocd', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(decided_at),'') FROM project_operate_certifier_decision",
            "SELECT 'oad', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(decided_at),'') FROM project_operate_approver_decision",
            "SELECT 'icd', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(decided_at),'') FROM project_include_request_certifier_decision",
            "SELECT 'ccd', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(decided_at),'') FROM project_change_certifier_decision",
            "SELECT 'cad', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(decided_at),'') FROM project_change_approver_decision",
            "SELECT 'rcd', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(decided_at),'') FROM project_result_report_certifier_decision",
            "SELECT 'oal', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(created_at),'') FROM project_operate_action_log",
            "SELECT 'ial', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(created_at),'') FROM project_include_request_action_log",
            "SELECT 'prl', COUNT(*), IFNULL(MAX(id),0), IFNULL(MAX(created_at),'') FROM project_plan_review_log",
        ];
        $parts = [];
        foreach ($tables as $sql) {
            $q = @mysqli_query($db_Project, $sql);
            if ($q && ($r = mysqli_fetch_row($q))) {
                $parts[] = implode(':', $r);
            }
        }
        return hash('sha256', implode('|', $parts));
    }
}

if (!function_exists('pm_portal_seen_pending_sync')) {
    /**
     * จำงาน pending ที่เคยส่งให้ staff_id (31 วัน) เพื่อส่ง state=cancelled เมื่อหลุดจากคิวโดยไม่ได้ทำเอง
     * @param array<string, array<string,mixed>> $activeItemsById
     * @param array<string, array<string,mixed>> $completedItemsById
     * @return array<int, array<string,mixed>> cancelled work items to include
     */
    function pm_portal_seen_pending_sync(string $staffId, array $activeItemsById, array $completedItemsById): array
    {
        $dir = pm_portal_ensure_cache_dir('seen_pending');
        $file = $dir . '/staff_' . $staffId . '.json';
        $now = time();
        $ttl = 31 * 86400;
        $stored = [];
        if (is_file($file) && is_readable($file)) {
            $raw = @file_get_contents($file);
            if (is_string($raw) && $raw !== '') {
                $dec = json_decode($raw, true);
                if (is_array($dec)) {
                    $stored = $dec;
                }
            }
        }

        // บันทึกงาน pending ปัจจุบัน
        foreach ($activeItemsById as $wid => $wi) {
            if (($wi['state'] ?? '') === 'pending') {
                $stored[$wid] = [
                    'work_item' => $wi,
                    'last_pending_at' => $now,
                    'cancelled_at' => null,
                ];
            }
        }

        // งานที่ทำเสร็จเองแล้วให้ลบออกจาก seen_pending (จะถูกส่งเป็น completed แทน)
        foreach ($completedItemsById as $wid => $_) {
            unset($stored[$wid]);
        }

        $cancelled = [];
        $nextStored = [];
        foreach ($stored as $wid => $entry) {
            if (!is_array($entry) || empty($entry['work_item']) || !is_array($entry['work_item'])) {
                continue;
            }
            if (isset($activeItemsById[$wid])) {
                $nextStored[$wid] = $entry;
                continue;
            }
            $cancelledAt = isset($entry['cancelled_at']) && is_int($entry['cancelled_at']) ? $entry['cancelled_at'] : $now;
            if (($now - $cancelledAt) > $ttl) {
                continue;
            }
            $entry['cancelled_at'] = $cancelledAt;
            $nextStored[$wid] = $entry;

            $wi = $entry['work_item'];
            $wi['state'] = 'cancelled';
            $wi['allowed_actions'] = [];
            $wi['action_url'] = null;
            $wi['revision'] = max(1, (int)($wi['revision'] ?? 1)) + 1;
            $wi['updated_at'] = (new DateTimeImmutable('@' . $cancelledAt))
                ->setTimezone(new DateTimeZone('Asia/Bangkok'))
                ->format(DateTimeInterface::RFC3339);
            $cancelled[] = $wi;
        }

        @file_put_contents($file, json_encode($nextStored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $cancelled;
    }
}

if (!function_exists('pm_portal_is_within_30_days')) {
    function pm_portal_is_within_30_days(?string $dt): bool
    {
        $raw = trim((string)$dt);
        if ($raw === '' || $raw === '0000-00-00 00:00:00') {
            return false;
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return false;
        }
        return (time() - $ts) <= (30 * 86400);
    }
}

if (!function_exists('pm_portal_project_date_range')) {
    /**
     * แปลง start_month/start_year .. end_month/end_year ของโครงการเป็น {start: YYYY-MM-DD, end: YYYY-MM-DD}
     * @return array{start:string,end:string}|null
     */
    function pm_portal_project_date_range(array $pre): ?array
    {
        $sm = (int)($pre['start_month'] ?? 0);
        $sy = (int)($pre['start_year'] ?? 0);
        $em = (int)($pre['end_month'] ?? 0);
        $ey = (int)($pre['end_year'] ?? 0);
        if ($sm < 1 || $sm > 12 || $em < 1 || $em > 12 || $sy <= 0 || $ey <= 0) {
            return null;
        }
        if ($sy >= 2400) {
            $sy -= 543;
        }
        if ($ey >= 2400) {
            $ey -= 543;
        }
        if ($sy < 2000 || $sy > 2100 || $ey < 2000 || $ey > 2100) {
            return null;
        }
        $start = sprintf('%04d-%02d-01', $sy, $sm);
        $lastDay = (int)date('t', strtotime(sprintf('%04d-%02d-01', $ey, $em)));
        $end = sprintf('%04d-%02d-%02d', $ey, $em, $lastDay);
        if ($end < $start) {
            return null;
        }
        return ['start' => $start, 'end' => $end];
    }
}

if (!function_exists('pm_portal_eval_chain')) {
    /**
     * ประเมินสถานะสายอนุมัติลำดับ 1..N
     * @param array<int, array{staff_id:int,position_title:string}> $chain
     * @param array<int, array<string,mixed>> $decBySort
     * @return array{
     *   all_approved: bool,
     *   has_rejected: bool,
     *   active_sort: int,
     *   active_staff_id: int,
     *   rejected_sort: int,
     *   rejected_staff_id: int,
     *   approved_count: int,
     *   total_count: int
     * }
     */
    function pm_portal_eval_chain(array $chain, array $decBySort): array
    {
        $sorts = array_keys($chain);
        sort($sorts, SORT_NUMERIC);
        $total = count($sorts);
        $approvedCount = 0;
        $hasRejected = false;
        $rejectedSort = 0;
        $rejectedSid = 0;
        $activeSort = 0;
        $activeSid = 0;

        foreach ($sorts as $so) {
            $dec = strtolower(trim((string)($decBySort[$so]['decision'] ?? '')));
            if ($dec === 'approved') {
                $approvedCount++;
                continue;
            }
            if ($dec === 'rejected') {
                $hasRejected = true;
                if ($rejectedSort === 0) {
                    $rejectedSort = $so;
                    $rejectedSid = (int)($chain[$so]['staff_id'] ?? 0);
                }
                break;
            }
            if ($activeSort === 0) {
                $activeSort = $so;
                $activeSid = (int)($chain[$so]['staff_id'] ?? 0);
            }
            break;
        }

        return [
            'all_approved' => ($total > 0 && $approvedCount === $total),
            'has_rejected' => $hasRejected,
            'active_sort' => $activeSort,
            'active_staff_id' => $activeSid,
            'rejected_sort' => $rejectedSort,
            'rejected_staff_id' => $rejectedSid,
            'approved_count' => $approvedCount,
            'total_count' => $total,
        ];
    }
}

if (!function_exists('pm_portal_load_bulk_data')) {
    /**
     * โหลดข้อมูล PBM ทั้งชุดแบบ batch (ประหยัด query มาก เพราะข้อมูลมี ~200 โครงการ)
     * @return array<string, mixed>
     */
    function pm_portal_load_bulk_data(mysqli $db_Project): array
    {
        $projects = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM pre_project WHERE status='1' ORDER BY id DESC");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $projects[(int)$r['id']] = $r;
        }

        $projHeads = [];
        $q = @mysqli_query($db_Project, "SELECT project_id, staff_project FROM project_head WHERE IFNULL(status,'1') NOT IN ('99','99.0')");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $pid = (int)$r['project_id'];
            $sid = (int)$r['staff_project'];
            if ($pid > 0 && $sid > 0) {
                $projHeads[$pid][$sid] = $sid;
            }
        }

        $projStaffs = [];
        $q = @mysqli_query($db_Project, "SELECT project_id, staff_project FROM project_staff WHERE IFNULL(status,'1') NOT IN ('99','99.0')");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $pid = (int)$r['project_id'];
            $sid = (int)$r['staff_project'];
            if ($pid > 0 && $sid > 0) {
                $projStaffs[$pid][$sid] = $sid;
            }
        }

        $projPersons = [];
        $q = @mysqli_query($db_Project, "SELECT project_id, staff_project FROM project_person WHERE IFNULL(status,'1') NOT IN ('99','99.0')");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $pid = (int)$r['project_id'];
            $sid = (int)$r['staff_project'];
            if ($pid > 0 && $sid > 0) {
                $projPersons[$pid][$sid] = $sid;
            }
        }

        $opCertDec = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_operate_certifier_decision");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $opCertDec[(int)$r['project_id']][(int)$r['sort_order']] = $r;
        }

        $opApprDec = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_operate_approver_decision");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $opApprDec[(int)$r['project_id']][(int)$r['sort_order']] = $r;
        }

        $repCertDec = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_result_report_certifier_decision");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $repCertDec[(int)$r['project_id']][(int)$r['sort_order']] = $r;
        }

        $opLogs = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_operate_action_log ORDER BY id ASC");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $opLogs[(int)$r['project_id']][] = $r;
        }

        $planLogs = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_plan_review_log ORDER BY id ASC");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $planLogs[(int)$r['project_id']][] = $r;
        }

        // Include requests
        $incReqs = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_include_request ORDER BY id DESC");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $incReqs[(int)$r['id']] = $r;
        }

        $incCerts = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_include_request_certifier ORDER BY request_id ASC, sort_order ASC");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $incCerts[(int)$r['request_id']][(int)$r['sort_order']] = [
                'staff_id' => (int)$r['staff_id'],
                'position_title' => trim((string)($r['position_title'] ?? '')),
            ];
        }

        $incDecs = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_include_request_certifier_decision");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $incDecs[(int)$r['request_id']][(int)$r['sort_order']] = $r;
        }

        $incItems = [];
        $projToIncReq = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_include_request_item ORDER BY request_id ASC, sort_order ASC, id ASC");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $rid = (int)$r['request_id'];
            $pid = (int)$r['project_id'];
            $incItems[$rid][] = $r;
            if ($pid > 0 && $rid > 0) {
                $projToIncReq[$pid][$rid] = $rid;
            }
        }

        $incLogs = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_include_request_action_log ORDER BY id ASC");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $incLogs[(int)$r['request_id']][] = $r;
        }

        // Change requests
        $chgReqs = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_change_request WHERE status='1' ORDER BY id DESC");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $chgReqs[(int)$r['id']] = $r;
        }

        $chgCertDec = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_change_certifier_decision");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $chgCertDec[(int)$r['request_id']][(int)$r['sort_order']] = $r;
        }

        $chgApprDec = [];
        $q = @mysqli_query($db_Project, "SELECT * FROM project_change_approver_decision");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $chgApprDec[(int)$r['request_id']][(int)$r['sort_order']] = $r;
        }

        $units = [];
        $q = @mysqli_query($db_Project, "SELECT id, code, name FROM set_unit");
        while ($q && ($r = mysqli_fetch_assoc($q))) {
            $units[(string)$r['id']] = trim((string)($r['name'] ?? ''));
            if (trim((string)($r['code'] ?? '')) !== '') {
                $units[trim((string)$r['code'])] = trim((string)($r['name'] ?? ''));
            }
        }

        return [
            'projects' => $projects,
            'projHeads' => $projHeads,
            'projStaffs' => $projStaffs,
            'projPersons' => $projPersons,
            'opCertDec' => $opCertDec,
            'opApprDec' => $opApprDec,
            'repCertDec' => $repCertDec,
            'opLogs' => $opLogs,
            'planLogs' => $planLogs,
            'incReqs' => $incReqs,
            'incCerts' => $incCerts,
            'incDecs' => $incDecs,
            'incItems' => $incItems,
            'projToIncReq' => $projToIncReq,
            'incLogs' => $incLogs,
            'chgReqs' => $chgReqs,
            'chgCertDec' => $chgCertDec,
            'chgApprDec' => $chgApprDec,
            'units' => $units,
        ];
    }
}

if (!function_exists('pm_portal_build_project_bundle')) {
    /**
     * สร้าง Record + WorkItems + Events ของโครงการ PROJ-{pid} สำหรับ $staffId (หรือทุกคนถ้า $staffId === null)
     * @return array{visible:bool,record:array<string,mixed>,work_items:array<int,array<string,mixed>>,events:array<int,array<string,mixed>>,involved_staff:array<int,string>}
     */
    function pm_portal_build_project_bundle(
        array $proj,
        array $bulk,
        array $staffMap,
        ?string $targetStaffId,
        bool $actionsEnabled
    ): array {
        $cfg = pm_portal_config();
        $webBase = rtrim((string)$cfg['portal_web_base'], '/');
        $pid = (int)$proj['id'];
        $recordId = 'PROJ-' . $pid;
        $refNo = trim((string)($proj['code_pre_project'] ?? ''));
        if ($refNo === '') {
            $refNo = $recordId;
        }
        $subject = pm_portal_trim_text((string)($proj['name_project'] ?? ''));
        if ($subject === '') {
            $subject = 'โครงการ ' . $refNo;
        }

        $requesterSid = (int)($proj['staff_insert'] ?? 0);
        $requester = pm_portal_staff_person_obj($requesterSid, $staffMap, true);

        $opCertChain = pm_portal_parse_chain_json((string)($proj['operate_certifiers_json'] ?? ''));
        // Fallback: ถ้า operate_certifiers_json ว่าง ให้ดูจาก include request ล่าสุด (เหมือน project_approve.php)
        if (empty($opCertChain) && !empty($bulk['projToIncReq'][$pid])) {
            $rids = array_values($bulk['projToIncReq'][$pid]);
            rsort($rids, SORT_NUMERIC);
            foreach ($rids as $rid) {
                if (!empty($bulk['incCerts'][$rid])) {
                    $opCertChain = $bulk['incCerts'][$rid];
                    break;
                }
            }
        }
        $opApprChain = pm_portal_parse_chain_json((string)($proj['operate_approvers_json'] ?? ''));
        $repCertChain = pm_portal_parse_chain_json((string)($proj['result_report_certifiers_json'] ?? ''));
        $incCertJsonChain = pm_portal_parse_chain_json((string)($proj['include_certifiers_json'] ?? ''));

        $opCertDec = $bulk['opCertDec'][$pid] ?? [];
        $opApprDec = $bulk['opApprDec'][$pid] ?? [];
        $repCertDec = $bulk['repCertDec'][$pid] ?? [];
        $opLogs = $bulk['opLogs'][$pid] ?? [];
        $planLogs = $bulk['planLogs'][$pid] ?? [];

        $certEval = pm_portal_eval_chain($opCertChain, $opCertDec);
        $apprEval = pm_portal_eval_chain($opApprChain, $opApprDec);
        $repEval = pm_portal_eval_chain($repCertChain, $repCertDec);

        $st = trim((string)($proj['status_project'] ?? '0'));
        $notifyResult = trim((string)($proj['notify_result'] ?? ''));
        $cancelledAt = trim((string)($proj['cancelled_at'] ?? ''));
        $repSubmittedAt = trim((string)($proj['result_report_submitted_at'] ?? ''));
        $repCompletedAt = trim((string)($proj['result_report_completed_at'] ?? ''));
        $repClosedAt = trim((string)($proj['result_report_plan_closed_at'] ?? ''));
        $opReplyNote = trim((string)($proj['operate_submitter_reply_note'] ?? ''));
        $opReplyAt = trim((string)($proj['operate_submitter_reply_updated_at'] ?? ''));

        $operateFinalApproved = ($st === '1') && !empty($opApprChain) && $apprEval['all_approved'];

        // Map state / source_state / current_step
        $state = 'draft';
        $sourceState = ['code' => $st !== '' ? $st : '0', 'label' => 'ร่าง'];
        $currentStep = null;

        if ($cancelledAt !== '' && $cancelledAt !== '0000-00-00 00:00:00') {
            $state = 'cancelled';
            $sourceState = ['code' => 'cancelled', 'label' => 'ยกเลิกโครงการ'];
            $currentStep = null;
        } elseif ($repClosedAt !== '' || $repCompletedAt !== '') {
            $state = 'completed';
            $sourceState = ['code' => 'completed', 'label' => 'ปิดโครงการแล้ว'];
            $currentStep = null;
        } elseif ($operateFinalApproved) {
            if ($repSubmittedAt !== '' && $repSubmittedAt !== '0000-00-00 00:00:00') {
                $state = 'in_execution';
                $sourceState = ['code' => 'report_review', 'label' => 'รอรับรองรายงานผลโครงการ'];
                $currentStep = ['code' => 'report_certify', 'label' => 'ผู้รับรองรายงานผลพิจารณา'];
            } else {
                $state = 'approved';
                $sourceState = ['code' => '1_approved', 'label' => 'อนุมัติดำเนินโครงการ'];
                $currentStep = null;
            }
        } elseif ($st === '1') {
            $state = 'under_review';
            $sourceState = ['code' => '1', 'label' => 'ขอดำเนินการ'];
            if (!empty($opCertChain) && !$certEval['all_approved']) {
                $currentStep = ['code' => 'operate_certify', 'label' => 'ผู้รับรองพิจารณาขอดำเนินโครงการ'];
            } elseif (empty($opApprChain)) {
                $currentStep = ['code' => 'operate_plan_assign', 'label' => 'หน่วยแผนเลือกผู้อนุมัติ'];
            } else {
                $currentStep = ['code' => 'operate_approve', 'label' => 'ผู้อนุมัติพิจารณาขอดำเนินโครงการ'];
            }
        } elseif ($st === '8') {
            $state = 'under_review';
            $sourceState = ['code' => '8', 'label' => 'ตีกลับให้แก้ไข'];
            if ($opReplyNote !== '') {
                $currentStep = ['code' => 'operate_recheck', 'label' => 'แก้ไขกลับมาแล้ว รอผู้พิจารณาตรวจสอบซ้ำ'];
            } else {
                $currentStep = ['code' => 'requester_fix', 'label' => 'ส่งกลับให้ผู้ขอแก้ไข'];
            }
        } elseif ($st === '4' || strpos($notifyResult, 'บรรจุแผน') === 0) {
            if ($notifyResult === 'ไม่บรรจุแผน') {
                $state = 'rejected';
                $sourceState = ['code' => '4_rejected', 'label' => 'ไม่บรรจุแผน'];
            } else {
                $state = 'approved';
                $sourceState = ['code' => '4', 'label' => $notifyResult !== '' ? $notifyResult : 'บรรจุแผน'];
            }
            $currentStep = null;
        } elseif ($st === '3') {
            $state = 'under_review';
            $sourceState = ['code' => '3', 'label' => 'รอบรรจุ'];
            $currentStep = ['code' => 'include_review', 'label' => 'รอพิจารณาบรรจุแผน'];
        } elseif ($st === '2') {
            $state = 'submitted';
            $sourceState = ['code' => '2', 'label' => 'ส่งร่าง'];
            $currentStep = ['code' => 'unit_review', 'label' => 'รอส่งขออนุมัติบรรจุแผน'];
        }

        // Involved staff set (สิทธิ์เห็น Record นี้ตาม staffCanViewPreProjectManageDetail)
        $involved = [];
        $addInv = static function ($id) use (&$involved): void {
            $n = (int)$id;
            if ($n > 0) {
                $involved[(string)$n] = (string)$n;
            }
        };
        $addInv($requesterSid);
        foreach (($bulk['projHeads'][$pid] ?? []) as $s) { $addInv($s); }
        foreach (($bulk['projStaffs'][$pid] ?? []) as $s) { $addInv($s); }
        foreach (($bulk['projPersons'][$pid] ?? []) as $s) { $addInv($s); }
        foreach ($opCertChain as $c) { $addInv($c['staff_id']); }
        foreach ($opApprChain as $c) { $addInv($c['staff_id']); }
        foreach ($repCertChain as $c) { $addInv($c['staff_id']); }
        foreach ($incCertJsonChain as $c) { $addInv($c['staff_id']); }
        foreach ($opCertDec as $d) { $addInv($d['staff_id'] ?? 0); $addInv($d['decided_by_staff_id'] ?? 0); }
        foreach ($opApprDec as $d) { $addInv($d['staff_id'] ?? 0); $addInv($d['decided_by_staff_id'] ?? 0); }
        foreach ($repCertDec as $d) { $addInv($d['staff_id'] ?? 0); $addInv($d['decided_by_staff_id'] ?? 0); }
        foreach (($bulk['projToIncReq'][$pid] ?? []) as $rid) {
            foreach (($bulk['incCerts'][$rid] ?? []) as $c) {
                $addInv($c['staff_id']);
            }
        }

        $visible = ($targetStaffId === null) || isset($involved[$targetStaffId]);

        // Deterministic monotonic revision (increases on every decision / log / reply / status change)
        $rev = 1
            + count($opCertDec)
            + count($opApprDec)
            + count($repCertDec)
            + count($opLogs)
            + count($planLogs)
            + ($opReplyNote !== '' ? 1 : 0)
            + ($operateFinalApproved ? 1 : 0)
            + ($cancelledAt !== '' ? 1 : 0)
            + ($repClosedAt !== '' || $repCompletedAt !== '' ? 1 : 0);

        // Timestamps
        $submittedRaw = trim((string)($proj['submit_date'] ?? ''));
        if ($submittedRaw === '' || $submittedRaw === '0000-00-00 00:00:00') {
            $submittedRaw = trim((string)($proj['dates'] ?? ''));
        }
        $latestRaw = trim((string)($proj['dates'] ?? ''));
        foreach ([$opReplyAt, $proj['notify_result_at'] ?? '', $proj['plan_admin_operate_rechain_submitted_at'] ?? '', $repSubmittedAt, $repClosedAt, $cancelledAt] as $tsCand) {
            $tsCand = trim((string)$tsCand);
            if ($tsCand !== '' && $tsCand > $latestRaw) {
                $latestRaw = $tsCand;
            }
        }
        foreach ($opLogs as $lg) {
            $ca = trim((string)($lg['created_at'] ?? ''));
            if ($ca !== '' && $ca > $latestRaw) {
                $latestRaw = $ca;
            }
        }

        $submittedAt = pm_portal_to_rfc3339($submittedRaw);
        $updatedAt = pm_portal_to_rfc3339($latestRaw, $submittedRaw);

        $loc = pm_portal_trim_text((string)($proj['location'] ?? ''), 255);

        $record = [
            'id' => $recordId,
            'reference_no' => $refNo,
            'subject' => $subject,
            'requester' => $requester,
            'state' => $state,
            'current_step' => $currentStep,
            'submitted_at' => $submittedAt,
            'updated_at' => $updatedAt,
            'revision' => $rev,
            'detail_url' => $webBase . '/records/' . rawurlencode($recordId),
            'source_state' => $sourceState,
            'location' => $loc !== '' ? $loc : null,
        ];

        // Build WorkItems for this project
        $workItems = [];
        $pushWi = static function (array $wi) use (&$workItems, $targetStaffId): void {
            if ($targetStaffId === null || ($wi['assignee']['staff_id'] ?? '') === $targetStaffId) {
                $workItems[] = $wi;
            }
        };

        // 1) Operate certifiers
        if ($st === '1' || $st === '8') {
            foreach ($opCertChain as $so => $c) {
                $cSid = (string)$c['staff_id'];
                if (!isset($staffMap[$cSid])) {
                    continue;
                }
                $decRow = $opCertDec[$so] ?? null;
                $dec = strtolower(trim((string)($decRow['decision'] ?? '')));
                $decAt = trim((string)($decRow['decided_at'] ?? ''));
                $wid = 'WI-OP-CERT-' . $pid . '-' . $so . '-' . $cSid;

                $isPendingNormal = ($st === '1' && !$certEval['all_approved'] && $certEval['active_sort'] === $so && $dec === '');
                $isPendingRecheck = ($st === '8' && $opReplyNote !== '' && $dec === 'rejected');

                if ($isPendingNormal || $isPendingRecheck) {
                    $assignedRaw = $isPendingRecheck ? ($opReplyAt !== '' ? $opReplyAt : $latestRaw) : $submittedRaw;
                    $pushWi([
                        'id' => $wid,
                        'record_id' => $recordId,
                        'assignee' => ['staff_id' => $cSid],
                        'type' => 'review',
                        'state' => 'pending',
                        'assigned_at' => pm_portal_to_rfc3339($assignedRaw, $updatedAt),
                        'due_at' => null,
                        'priority' => 'normal',
                        'allowed_actions' => $actionsEnabled ? ['approve', 'return'] : [],
                        'revision' => $rev,
                        'updated_at' => $updatedAt,
                        'title' => $isPendingRecheck ? 'พิจารณาตรวจสอบขอดำเนินโครงการซ้ำ' : 'พิจารณาตรวจสอบขอดำเนินโครงการ',
                        'action_url' => $webBase . '/records/' . rawurlencode($recordId) . '/work',
                    ]);
                } elseif ($dec !== '' && pm_portal_is_within_30_days($decAt)) {
                    $pushWi([
                        'id' => $wid,
                        'record_id' => $recordId,
                        'assignee' => ['staff_id' => $cSid],
                        'type' => 'review',
                        'state' => 'completed',
                        'assigned_at' => pm_portal_to_rfc3339($submittedRaw, $decAt),
                        'due_at' => null,
                        'priority' => 'normal',
                        'allowed_actions' => [],
                        'revision' => $rev,
                        'updated_at' => pm_portal_to_rfc3339($decAt, $updatedAt),
                        'title' => 'พิจารณาตรวจสอบขอดำเนินโครงการ',
                        'action_url' => null,
                    ]);
                }
            }

            // 2) Operate approvers (only after certifiers all approved and approvers chain exists)
            if ($certEval['all_approved'] && !empty($opApprChain)) {
                $rechainAt = trim((string)($proj['plan_admin_operate_rechain_submitted_at'] ?? ''));
                foreach ($opApprChain as $so => $c) {
                    $aSid = (string)$c['staff_id'];
                    if (!isset($staffMap[$aSid])) {
                        continue;
                    }
                    $decRow = $opApprDec[$so] ?? null;
                    $dec = strtolower(trim((string)($decRow['decision'] ?? '')));
                    $decAt = trim((string)($decRow['decided_at'] ?? ''));
                    $wid = 'WI-OP-APPR-' . $pid . '-' . $so . '-' . $aSid;

                    $isPendingNormal = ($st === '1' && !$apprEval['all_approved'] && $apprEval['active_sort'] === $so && $dec === '');
                    $isPendingRecheck = ($st === '8' && $opReplyNote !== '' && $dec === 'rejected');

                    if ($isPendingNormal || $isPendingRecheck) {
                        $assignedRaw = $isPendingRecheck ? ($opReplyAt !== '' ? $opReplyAt : $latestRaw) : ($rechainAt !== '' ? $rechainAt : $updatedAt);
                        $pushWi([
                            'id' => $wid,
                            'record_id' => $recordId,
                            'assignee' => ['staff_id' => $aSid],
                            'type' => 'review',
                            'state' => 'pending',
                            'assigned_at' => pm_portal_to_rfc3339($assignedRaw, $updatedAt),
                            'due_at' => null,
                            'priority' => 'normal',
                            'allowed_actions' => $actionsEnabled ? ['approve', 'return'] : [],
                            'revision' => $rev,
                            'updated_at' => $updatedAt,
                            'title' => $isPendingRecheck ? 'พิจารณาอนุมัติดำเนินโครงการซ้ำ' : 'พิจารณาอนุมัติดำเนินโครงการ',
                            'action_url' => $webBase . '/records/' . rawurlencode($recordId) . '/work',
                        ]);
                    } elseif ($dec !== '' && pm_portal_is_within_30_days($decAt)) {
                        $pushWi([
                            'id' => $wid,
                            'record_id' => $recordId,
                            'assignee' => ['staff_id' => $aSid],
                            'type' => 'review',
                            'state' => 'completed',
                            'assigned_at' => pm_portal_to_rfc3339($rechainAt !== '' ? $rechainAt : $submittedRaw, $decAt),
                            'due_at' => null,
                            'priority' => 'normal',
                            'allowed_actions' => [],
                            'revision' => $rev,
                            'updated_at' => pm_portal_to_rfc3339($decAt, $updatedAt),
                            'title' => 'พิจารณาอนุมัติดำเนินโครงการ',
                            'action_url' => null,
                        ]);
                    }
                }
            }

            // 3) Requester fix when status_project = '8' and requester hasn't replied yet
            if ($st === '8' && $opReplyNote === '' && $requesterSid > 0 && isset($staffMap[(string)$requesterSid])) {
                $rSid = (string)$requesterSid;
                $pushWi([
                    'id' => 'WI-OP-FIX-' . $pid . '-' . $rSid,
                    'record_id' => $recordId,
                    'assignee' => ['staff_id' => $rSid],
                    'type' => 'correct',
                    'state' => 'pending',
                    'assigned_at' => $updatedAt,
                    'due_at' => null,
                    'priority' => 'high',
                    'allowed_actions' => [],
                    'revision' => $rev,
                    'updated_at' => $updatedAt,
                    'title' => 'แก้ไขโครงการตามที่ถูกส่งกลับ',
                    'action_url' => $webBase . '/records/' . rawurlencode($recordId) . '/work',
                ]);
            }
        }

        // 4) Result report certifiers
        if ($repSubmittedAt !== '' && $repSubmittedAt !== '0000-00-00 00:00:00' && !empty($repCertChain)) {
            $repOpen = ($repCompletedAt === '' && $repClosedAt === '');
            foreach ($repCertChain as $so => $c) {
                $rcSid = (string)$c['staff_id'];
                if (!isset($staffMap[$rcSid])) {
                    continue;
                }
                $decRow = $repCertDec[$so] ?? null;
                $dec = strtolower(trim((string)($decRow['decision'] ?? '')));
                $decAt = trim((string)($decRow['decided_at'] ?? ''));
                $wid = 'WI-REP-CERT-' . $pid . '-' . $so . '-' . $rcSid;

                if ($repOpen && $repEval['active_sort'] === $so && $dec === '') {
                    $pushWi([
                        'id' => $wid,
                        'record_id' => $recordId,
                        'assignee' => ['staff_id' => $rcSid],
                        'type' => 'verify',
                        'state' => 'pending',
                        'assigned_at' => pm_portal_to_rfc3339($repSubmittedAt, $updatedAt),
                        'due_at' => null,
                        'priority' => 'normal',
                        'allowed_actions' => $actionsEnabled ? ['approve', 'return'] : [],
                        'revision' => $rev,
                        'updated_at' => $updatedAt,
                        'title' => 'พิจารณารับรองรายงานผลโครงการ',
                        'action_url' => $webBase . '/records/' . rawurlencode($recordId) . '/work',
                    ]);
                } elseif ($dec !== '' && pm_portal_is_within_30_days($decAt)) {
                    $pushWi([
                        'id' => $wid,
                        'record_id' => $recordId,
                        'assignee' => ['staff_id' => $rcSid],
                        'type' => 'verify',
                        'state' => 'completed',
                        'assigned_at' => pm_portal_to_rfc3339($repSubmittedAt, $decAt),
                        'due_at' => null,
                        'priority' => 'normal',
                        'allowed_actions' => [],
                        'revision' => $rev,
                        'updated_at' => pm_portal_to_rfc3339($decAt, $updatedAt),
                        'title' => 'พิจารณารับรองรายงานผลโครงการ',
                        'action_url' => null,
                    ]);
                }
            }
        }

        // Build Events from project_operate_action_log + project_plan_review_log
        $events = [];
        $reqSidStr = ($requesterSid > 0 && isset($staffMap[(string)$requesterSid])) ? (string)$requesterSid : '';
        foreach ($opLogs as $lg) {
            $lgId = (int)($lg['id'] ?? 0);
            $at = trim((string)($lg['action_type'] ?? ''));
            $actorSid = (int)($lg['actor_staff_id'] ?? 0);
            $actorObj = pm_portal_staff_person_obj($actorSid, $staffMap, false);
            $note = pm_portal_trim_text((string)($lg['note'] ?? ''), 500);
            $occ = pm_portal_to_rfc3339((string)($lg['created_at'] ?? ''), $updatedAt);
            $so = (int)($lg['sort_order'] ?? 0);

            $evType = 'approved';
            $msg = 'ดำเนินการพิจารณาขอดำเนินโครงการ';
            $recips = [];
            if ($at === 'certifier_approved' || $at === 'certifier_recheck_approved') {
                $evType = 'approved';
                $msg = ($at === 'certifier_recheck_approved' ? 'ผู้พิจารณาตรวจสอบซ้ำและเห็นชอบ' : 'ผู้พิจารณาเห็นชอบ/อนุมัติ') . ($note !== '' ? ': ' . $note : '');
                if ($reqSidStr !== '' && $reqSidStr !== (string)$actorSid) {
                    $recips[$reqSidStr] = $reqSidStr;
                }
                // แจ้งผู้รับผิดชอบลำดับถัดไปถ้ามี
                $nextSid = $certEval['all_approved'] ? $apprEval['active_staff_id'] : $certEval['active_staff_id'];
                if ($nextSid > 0 && isset($staffMap[(string)$nextSid]) && (string)$nextSid !== (string)$actorSid) {
                    $recips[(string)$nextSid] = (string)$nextSid;
                }
            } elseif ($at === 'certifier_rejected') {
                $evType = 'returned';
                $msg = $note !== '' ? $note : 'ส่งกลับให้ผู้ขอแก้ไขโครงการ';
                if ($reqSidStr !== '' && $reqSidStr !== (string)$actorSid) {
                    $recips[$reqSidStr] = $reqSidStr;
                }
            } elseif ($at === 'submitter_reply_saved') {
                $evType = 'submitted';
                $msg = 'ผู้ขอส่งคำชี้แจงหลังแก้ไข' . ($note !== '' ? ': ' . $note : '');
                if ($certEval['has_rejected'] && $certEval['rejected_staff_id'] > 0 && isset($staffMap[(string)$certEval['rejected_staff_id']])) {
                    $ns = (string)$certEval['rejected_staff_id'];
                    if ($ns !== (string)$actorSid) {
                        $recips[$ns] = $ns;
                    }
                }
            } else {
                continue;
            }

            $events[] = [
                'id' => 'EV-OP-' . $pid . '-' . $lgId,
                'record_id' => $recordId,
                'work_item_id' => ($so > 0 && $actorSid > 0) ? ('WI-OP-CERT-' . $pid . '-' . $so . '-' . $actorSid) : null,
                'type' => $evType,
                'actor' => $actorObj,
                'occurred_at' => $occ,
                'message' => $msg,
                'recipients' => array_values($recips),
            ];
        }

        foreach ($planLogs as $pl) {
            $plId = (int)($pl['id'] ?? 0);
            $at = trim((string)($pl['action_type'] ?? ''));
            $actorSid = (int)($pl['actor_staff_id'] ?? 0);
            $actorObj = pm_portal_staff_person_obj($actorSid, $staffMap, false);
            $note = pm_portal_trim_text((string)($pl['note'] ?? ''), 500);
            $occ = pm_portal_to_rfc3339((string)($pl['created_at'] ?? ''), $updatedAt);

            $evType = null;
            $msg = '';
            $recips = [];
            if ($at === 'plan_admin_operate_rechain') {
                $evType = 'assigned';
                $msg = 'หน่วยแผนส่งต่อให้ผู้อนุมัติพิจารณาขอดำเนินโครงการ';
                if ($reqSidStr !== '' && $reqSidStr !== (string)$actorSid) {
                    $recips[$reqSidStr] = $reqSidStr;
                }
                if (!empty($opApprChain)) {
                    $firstAppr = reset($opApprChain);
                    $faSid = (string)($firstAppr['staff_id'] ?? 0);
                    if ($faSid !== '0' && isset($staffMap[$faSid]) && $faSid !== (string)$actorSid) {
                        $recips[$faSid] = $faSid;
                    }
                }
            } elseif ($at === 'notify_plan_result') {
                $evType = ($notifyResult === 'ไม่บรรจุแผน') ? 'rejected' : 'approved';
                $msg = 'แจ้งผลการบรรจุแผน' . ($note !== '' ? ': ' . $note : '');
                if ($reqSidStr !== '' && $reqSidStr !== (string)$actorSid) {
                    $recips[$reqSidStr] = $reqSidStr;
                }
            }
            if ($evType !== null) {
                $events[] = [
                    'id' => 'EV-PL-' . $pid . '-' . $plId,
                    'record_id' => $recordId,
                    'work_item_id' => null,
                    'type' => $evType,
                    'actor' => $actorObj,
                    'occurred_at' => $occ,
                    'message' => $msg,
                    'recipients' => array_values($recips),
                ];
            }
        }

        return [
            'visible' => $visible,
            'record' => $record,
            'work_items' => $workItems,
            'events' => $events,
            'involved_staff' => array_values($involved),
        ];
    }
}

if (!function_exists('pm_portal_build_include_bundle')) {
    /**
     * สร้าง Record + WorkItems + Events ของคำขออนุมัติบรรจุแผน INC-{rid}
     * @return array{visible:bool,record:array<string,mixed>,work_items:array<int,array<string,mixed>>,events:array<int,array<string,mixed>>,involved_staff:array<int,string>}
     */
    function pm_portal_build_include_bundle(
        array $req,
        array $bulk,
        array $staffMap,
        ?string $targetStaffId,
        bool $actionsEnabled
    ): array {
        $cfg = pm_portal_config();
        $webBase = rtrim((string)$cfg['portal_web_base'], '/');
        $rid = (int)$req['id'];
        $recordId = 'INC-' . $rid;
        $by = trim((string)($req['budget_year'] ?? ''));
        $refNo = 'INC-' . ($by !== '' ? $by : date('Y') + 543) . '-' . str_pad((string)$rid, 3, '0', STR_PAD_LEFT);
        $subject = pm_portal_trim_text((string)($req['subject'] ?? ''));
        if ($subject === '') {
            $subject = 'คำขออนุมัติบรรจุแผน #' . $rid;
        }

        $subSid = (int)($req['submitter_staff_id'] ?? 0);
        $requester = pm_portal_staff_person_obj($subSid, $staffMap, true);

        $certs = $bulk['incCerts'][$rid] ?? [];
        $decs = $bulk['incDecs'][$rid] ?? [];
        $logs = $bulk['incLogs'][$rid] ?? [];
        $eval = pm_portal_eval_chain($certs, $decs);

        $st = strtolower(trim((string)($req['status'] ?? 'submitted')));
        $puDec = strtolower(trim((string)($req['plan_unit_decision'] ?? '')));
        $replyNote = trim((string)($req['submitter_reply_note'] ?? ''));
        $replyAt = trim((string)($req['submitter_reply_updated_at'] ?? ''));
        $puReplyNote = trim((string)($req['plan_unit_submitter_reply_note'] ?? ''));

        $state = 'under_review';
        $sourceState = ['code' => $st !== '' ? $st : 'submitted', 'label' => 'รอผู้รับรองพิจารณา'];
        $currentStep = ['code' => 'include_certify', 'label' => 'ผู้รับรองพิจารณาคำขอบรรจุแผน'];

        if ($st === 'cancelled') {
            $state = 'cancelled';
            $sourceState = ['code' => 'cancelled', 'label' => 'ยกเลิกคำขอ'];
            $currentStep = null;
        } elseif ($puDec === 'included') {
            $state = 'approved';
            $sourceState = ['code' => 'included', 'label' => 'บรรจุแผนแล้ว'];
            $currentStep = null;
        } elseif ($puDec === 'rejected') {
            $state = 'rejected';
            $sourceState = ['code' => 'plan_rejected', 'label' => 'ไม่บรรจุแผน'];
            $currentStep = null;
        } elseif ($puDec === 'returned') {
            $state = 'under_review';
            $sourceState = ['code' => 'plan_returned', 'label' => 'หน่วยแผนส่งกลับแก้ไข'];
            $currentStep = ['code' => 'requester_fix', 'label' => 'ผู้ขอแก้ไขตามหน่วยแผนส่งกลับ'];
        } elseif ($st === 'rejected') {
            if ($replyNote !== '') {
                $state = 'under_review';
                $sourceState = ['code' => 'recheck', 'label' => 'แก้ไขกลับมาแล้ว รอตรวจสอบซ้ำ'];
                $currentStep = ['code' => 'include_recheck', 'label' => 'ผู้รับรองพิจารณาซ้ำ'];
            } else {
                $state = 'under_review';
                $sourceState = ['code' => 'rejected', 'label' => 'ตีกลับให้แก้ไข'];
                $currentStep = ['code' => 'requester_fix', 'label' => 'ผู้ขอแก้ไขคำขอบรรจุแผน'];
            }
        } elseif ($eval['all_approved']) {
            $state = 'under_review';
            $sourceState = ['code' => 'waiting_plan', 'label' => 'รอหน่วยแผนพิจารณา'];
            $currentStep = ['code' => 'plan_unit_review', 'label' => 'หน่วยแผนและงบประมาณพิจารณา'];
        }

        $involved = [];
        $addInv = static function ($id) use (&$involved): void {
            $n = (int)$id;
            if ($n > 0) {
                $involved[(string)$n] = (string)$n;
            }
        };
        $addInv($subSid);
        foreach ($certs as $c) { $addInv($c['staff_id']); }
        foreach ($decs as $d) { $addInv($d['staff_id'] ?? 0); $addInv($d['decided_by_staff_id'] ?? 0); }
        $addInv($req['plan_unit_staff_id'] ?? 0);

        $visible = ($targetStaffId === null) || isset($involved[$targetStaffId]);

        $rev = 1 + count($decs) + count($logs) + ($replyNote !== '' ? 1 : 0) + ($puDec !== '' ? 1 : 0);
        $createdAt = trim((string)($req['created_at'] ?? ''));
        $latestRaw = trim((string)($req['updated_at'] ?? ''));
        if ($latestRaw === '') {
            $latestRaw = $createdAt;
        }
        foreach ($logs as $lg) {
            $ca = trim((string)($lg['created_at'] ?? ''));
            if ($ca !== '' && $ca > $latestRaw) {
                $latestRaw = $ca;
            }
        }
        $submittedAt = pm_portal_to_rfc3339($createdAt);
        $updatedAt = pm_portal_to_rfc3339($latestRaw, $createdAt);

        $record = [
            'id' => $recordId,
            'reference_no' => $refNo,
            'subject' => $subject,
            'requester' => $requester,
            'state' => $state,
            'current_step' => $currentStep,
            'submitted_at' => $submittedAt,
            'updated_at' => $updatedAt,
            'revision' => $rev,
            'detail_url' => $webBase . '/records/' . rawurlencode($recordId),
            'source_state' => $sourceState,
        ];

        $workItems = [];
        $pushWi = static function (array $wi) use (&$workItems, $targetStaffId): void {
            if ($targetStaffId === null || ($wi['assignee']['staff_id'] ?? '') === $targetStaffId) {
                $workItems[] = $wi;
            }
        };

        if ($st !== 'cancelled') {
            foreach ($certs as $so => $c) {
                $cSid = (string)$c['staff_id'];
                if (!isset($staffMap[$cSid])) {
                    continue;
                }
                $decRow = $decs[$so] ?? null;
                $dec = strtolower(trim((string)($decRow['decision'] ?? '')));
                $decAt = trim((string)($decRow['decided_at'] ?? ''));
                $wid = 'WI-INC-CERT-' . $rid . '-' . $so . '-' . $cSid;

                $isPendingNormal = ($st === 'submitted' && !$eval['all_approved'] && $eval['active_sort'] === $so && $dec === '');
                $isPendingRecheck = ($st === 'rejected' && $replyNote !== '' && $dec === 'rejected');

                if ($isPendingNormal || $isPendingRecheck) {
                    $pushWi([
                        'id' => $wid,
                        'record_id' => $recordId,
                        'assignee' => ['staff_id' => $cSid],
                        'type' => 'review',
                        'state' => 'pending',
                        'assigned_at' => pm_portal_to_rfc3339($isPendingRecheck && $replyAt !== '' ? $replyAt : $createdAt, $updatedAt),
                        'due_at' => null,
                        'priority' => 'normal',
                        'allowed_actions' => $actionsEnabled ? ['approve', 'return'] : [],
                        'revision' => $rev,
                        'updated_at' => $updatedAt,
                        'title' => $isPendingRecheck ? 'พิจารณาคำขอบรรจุแผนซ้ำ' : 'พิจารณาคำขออนุมัติบรรจุแผน',
                        'action_url' => $webBase . '/records/' . rawurlencode($recordId) . '/work',
                    ]);
                } elseif ($dec !== '' && pm_portal_is_within_30_days($decAt)) {
                    $pushWi([
                        'id' => $wid,
                        'record_id' => $recordId,
                        'assignee' => ['staff_id' => $cSid],
                        'type' => 'review',
                        'state' => 'completed',
                        'assigned_at' => $submittedAt,
                        'due_at' => null,
                        'priority' => 'normal',
                        'allowed_actions' => [],
                        'revision' => $rev,
                        'updated_at' => pm_portal_to_rfc3339($decAt, $updatedAt),
                        'title' => 'พิจารณาคำขออนุมัติบรรจุแผน',
                        'action_url' => null,
                    ]);
                }
            }

            if ((($st === 'rejected' && $replyNote === '') || ($puDec === 'returned' && $puReplyNote === '')) && $subSid > 0 && isset($staffMap[(string)$subSid])) {
                $rSid = (string)$subSid;
                $pushWi([
                    'id' => 'WI-INC-FIX-' . $rid . '-' . $rSid,
                    'record_id' => $recordId,
                    'assignee' => ['staff_id' => $rSid],
                    'type' => 'correct',
                    'state' => 'pending',
                    'assigned_at' => $updatedAt,
                    'due_at' => null,
                    'priority' => 'high',
                    'allowed_actions' => [],
                    'revision' => $rev,
                    'updated_at' => $updatedAt,
                    'title' => 'แก้ไขคำขอบรรจุแผนตามที่ถูกส่งกลับ',
                    'action_url' => $webBase . '/records/' . rawurlencode($recordId) . '/work',
                ]);
            }
        }

        $events = [];
        $subSidStr = ($subSid > 0 && isset($staffMap[(string)$subSid])) ? (string)$subSid : '';
        foreach ($logs as $lg) {
            $lgId = (int)($lg['id'] ?? 0);
            $at = trim((string)($lg['action_type'] ?? ''));
            $actorSid = (int)($lg['actor_staff_id'] ?? 0);
            $actorObj = pm_portal_staff_person_obj($actorSid, $staffMap, false);
            $note = pm_portal_trim_text((string)($lg['note'] ?? ''), 500);
            $occ = pm_portal_to_rfc3339((string)($lg['created_at'] ?? ''), $updatedAt);
            $so = (int)($lg['sort_order'] ?? 0);

            $evType = null;
            $msg = '';
            $recips = [];
            if ($at === 'certifier_approved' || $at === 'certifier_recheck_approved') {
                $evType = 'approved';
                $msg = 'ผู้รับรองเห็นชอบคำขอบรรจุแผน' . ($note !== '' ? ': ' . $note : '');
                if ($subSidStr !== '' && $subSidStr !== (string)$actorSid) {
                    $recips[$subSidStr] = $subSidStr;
                }
                if ($eval['active_staff_id'] > 0 && isset($staffMap[(string)$eval['active_staff_id']])) {
                    $ns = (string)$eval['active_staff_id'];
                    if ($ns !== (string)$actorSid) {
                        $recips[$ns] = $ns;
                    }
                }
            } elseif ($at === 'certifier_rejected' || $at === 'plan_unit_returned') {
                $evType = 'returned';
                $msg = $note !== '' ? $note : 'ส่งกลับคำขอบรรจุแผนให้แก้ไข';
                if ($subSidStr !== '' && $subSidStr !== (string)$actorSid) {
                    $recips[$subSidStr] = $subSidStr;
                }
            } elseif ($at === 'plan_unit_included') {
                $evType = 'approved';
                $msg = 'หน่วยแผนอนุมัติบรรจุแผน' . ($note !== '' ? ': ' . $note : '');
                if ($subSidStr !== '' && $subSidStr !== (string)$actorSid) {
                    $recips[$subSidStr] = $subSidStr;
                }
            } elseif ($at === 'plan_unit_rejected') {
                $evType = 'rejected';
                $msg = $note !== '' ? $note : 'หน่วยแผนไม่บรรจุแผน';
                if ($subSidStr !== '' && $subSidStr !== (string)$actorSid) {
                    $recips[$subSidStr] = $subSidStr;
                }
            } elseif ($at === 'admin_recall_include_send') {
                $evType = 'cancelled';
                $msg = 'ยกเลิก/ดึงกลับคำขอบรรจุแผน' . ($note !== '' ? ': ' . $note : '');
                if ($subSidStr !== '' && $subSidStr !== (string)$actorSid) {
                    $recips[$subSidStr] = $subSidStr;
                }
            } elseif ($at === 'submitter_reply_saved') {
                $evType = 'submitted';
                $msg = 'ผู้ขอส่งคำชี้แจงคำขอบรรจุแผน' . ($note !== '' ? ': ' . $note : '');
                if ($eval['rejected_staff_id'] > 0 && isset($staffMap[(string)$eval['rejected_staff_id']])) {
                    $ns = (string)$eval['rejected_staff_id'];
                    if ($ns !== (string)$actorSid) {
                        $recips[$ns] = $ns;
                    }
                }
            }
            if ($evType !== null) {
                $events[] = [
                    'id' => 'EV-INC-' . $rid . '-' . $lgId,
                    'record_id' => $recordId,
                    'work_item_id' => ($so > 0 && $actorSid > 0) ? ('WI-INC-CERT-' . $rid . '-' . $so . '-' . $actorSid) : null,
                    'type' => $evType,
                    'actor' => $actorObj,
                    'occurred_at' => $occ,
                    'message' => $msg,
                    'recipients' => array_values($recips),
                ];
            }
        }

        return [
            'visible' => $visible,
            'record' => $record,
            'work_items' => $workItems,
            'events' => $events,
            'involved_staff' => array_values($involved),
        ];
    }
}

if (!function_exists('pm_portal_build_change_bundle')) {
    /**
     * สร้าง Record + WorkItems + Events ของคำขอเปลี่ยนแปลงโครงการ CHG-{rid}
     * @return array{visible:bool,record:array<string,mixed>,work_items:array<int,array<string,mixed>>,events:array<int,array<string,mixed>>,involved_staff:array<int,string>}
     */
    function pm_portal_build_change_bundle(
        array $req,
        array $bulk,
        array $staffMap,
        ?string $targetStaffId,
        bool $actionsEnabled
    ): array {
        $cfg = pm_portal_config();
        $webBase = rtrim((string)$cfg['portal_web_base'], '/');
        $rid = (int)$req['id'];
        $pid = (int)($req['project_id'] ?? 0);
        $recordId = 'CHG-' . $rid;
        $pCode = trim((string)($req['project_code'] ?? ''));
        $refNo = ($pCode !== '' ? $pCode : ('PROJ-' . $pid)) . '/CHG-' . $rid;
        $pName = pm_portal_trim_text((string)($req['project_name'] ?? ''));
        $subject = 'ขอเปลี่ยนแปลงโครงการ: ' . ($pName !== '' ? $pName : $refNo);

        $subSid = (int)($req['staff_insert'] ?? 0);
        $requester = pm_portal_staff_person_obj($subSid, $staffMap, true);

        $certChain = pm_portal_parse_chain_json((string)($req['certifiers_json'] ?? ''));
        $apprChain = pm_portal_parse_chain_json((string)($req['approvers_json'] ?? ''));
        $certDec = $bulk['chgCertDec'][$rid] ?? [];
        $apprDec = $bulk['chgApprDec'][$rid] ?? [];
        $certEval = pm_portal_eval_chain($certChain, $certDec);
        $apprEval = pm_portal_eval_chain($apprChain, $apprDec);

        $rs = strtolower(trim((string)($req['req_status'] ?? 'pending')));
        $state = 'under_review';
        $sourceState = ['code' => $rs !== '' ? $rs : 'pending', 'label' => 'รอผู้รับรองพิจารณา'];
        $currentStep = ['code' => 'change_certify', 'label' => 'ผู้รับรองพิจารณาคำขอเปลี่ยนแปลง'];

        if ($rs === 'waiting_plan') {
            $state = 'under_review';
            $sourceState = ['code' => 'waiting_plan', 'label' => 'รอหน่วยแผนเลือกผู้อนุมัติ'];
            $currentStep = ['code' => 'change_plan_assign', 'label' => 'หน่วยแผนเลือกผู้อนุมัติ'];
        } elseif ($rs === 'pending_approve') {
            $state = 'under_review';
            $sourceState = ['code' => 'pending_approve', 'label' => 'รอผู้อนุมัติพิจารณา'];
            $currentStep = ['code' => 'change_approve', 'label' => 'ผู้อนุมัติพิจารณาคำขอเปลี่ยนแปลง'];
        } elseif ($rs === 'approved') {
            $state = 'approved';
            $sourceState = ['code' => 'approved', 'label' => 'อนุมัติเปลี่ยนแปลงโครงการ'];
            $currentStep = null;
        } elseif ($rs === 'applied') {
            $state = 'completed';
            $sourceState = ['code' => 'applied', 'label' => 'ปรับปรุงโครงการเรียบร้อย'];
            $currentStep = null;
        } elseif ($rs === 'rejected') {
            $state = 'rejected';
            $sourceState = ['code' => 'rejected', 'label' => 'ตีกลับ/ไม่อนุมัติ'];
            $currentStep = null;
        }

        $involved = [];
        $addInv = static function ($id) use (&$involved): void {
            $n = (int)$id;
            if ($n > 0) {
                $involved[(string)$n] = (string)$n;
            }
        };
        $addInv($subSid);
        if (isset($bulk['projects'][$pid])) {
            $addInv($bulk['projects'][$pid]['staff_insert'] ?? 0);
        }
        foreach (($bulk['projHeads'][$pid] ?? []) as $s) { $addInv($s); }
        foreach ($certChain as $c) { $addInv($c['staff_id']); }
        foreach ($apprChain as $c) { $addInv($c['staff_id']); }
        foreach ($certDec as $d) { $addInv($d['staff_id'] ?? 0); $addInv($d['decided_by_staff_id'] ?? 0); }
        foreach ($apprDec as $d) { $addInv($d['staff_id'] ?? 0); $addInv($d['decided_by_staff_id'] ?? 0); }

        $visible = ($targetStaffId === null) || isset($involved[$targetStaffId]);
        $rev = 1 + count($certDec) + count($apprDec) + (in_array($rs, ['waiting_plan', 'pending_approve', 'approved', 'applied', 'rejected'], true) ? 1 : 0);

        $createdAt = trim((string)($req['created_at'] ?? ''));
        $updatedRaw = trim((string)($req['updated_at'] ?? ''));
        $submittedAt = pm_portal_to_rfc3339($createdAt);
        $updatedAt = pm_portal_to_rfc3339($updatedRaw, $createdAt);

        $record = [
            'id' => $recordId,
            'reference_no' => $refNo,
            'subject' => $subject,
            'requester' => $requester,
            'state' => $state,
            'current_step' => $currentStep,
            'submitted_at' => $submittedAt,
            'updated_at' => $updatedAt,
            'revision' => $rev,
            'detail_url' => $webBase . '/records/' . rawurlencode($recordId),
            'source_state' => $sourceState,
        ];

        $workItems = [];
        $pushWi = static function (array $wi) use (&$workItems, $targetStaffId): void {
            if ($targetStaffId === null || ($wi['assignee']['staff_id'] ?? '') === $targetStaffId) {
                $workItems[] = $wi;
            }
        };

        foreach ($certChain as $so => $c) {
            $cSid = (string)$c['staff_id'];
            if (!isset($staffMap[$cSid])) {
                continue;
            }
            $decRow = $certDec[$so] ?? null;
            $dec = strtolower(trim((string)($decRow['decision'] ?? '')));
            $decAt = trim((string)($decRow['decided_at'] ?? ''));
            $wid = 'WI-CHG-CERT-' . $rid . '-' . $so . '-' . $cSid;

            if ($rs === 'pending' && $certEval['active_sort'] === $so && $dec === '') {
                $pushWi([
                    'id' => $wid,
                    'record_id' => $recordId,
                    'assignee' => ['staff_id' => $cSid],
                    'type' => 'review',
                    'state' => 'pending',
                    'assigned_at' => $submittedAt,
                    'due_at' => null,
                    'priority' => 'normal',
                    'allowed_actions' => $actionsEnabled ? ['approve', 'reject'] : [],
                    'revision' => $rev,
                    'updated_at' => $updatedAt,
                    'title' => 'พิจารณาตรวจสอบคำขอเปลี่ยนแปลงโครงการ',
                    'action_url' => $webBase . '/records/' . rawurlencode($recordId) . '/work',
                ]);
            } elseif ($dec !== '' && pm_portal_is_within_30_days($decAt)) {
                $pushWi([
                    'id' => $wid,
                    'record_id' => $recordId,
                    'assignee' => ['staff_id' => $cSid],
                    'type' => 'review',
                    'state' => 'completed',
                    'assigned_at' => $submittedAt,
                    'due_at' => null,
                    'priority' => 'normal',
                    'allowed_actions' => [],
                    'revision' => $rev,
                    'updated_at' => pm_portal_to_rfc3339($decAt, $updatedAt),
                    'title' => 'พิจารณาตรวจสอบคำขอเปลี่ยนแปลงโครงการ',
                    'action_url' => null,
                ]);
            }
        }

        $rechainAt = trim((string)($req['plan_rechain_submitted_at'] ?? ''));
        foreach ($apprChain as $so => $c) {
            $aSid = (string)$c['staff_id'];
            if (!isset($staffMap[$aSid])) {
                continue;
            }
            $decRow = $apprDec[$so] ?? null;
            $dec = strtolower(trim((string)($decRow['decision'] ?? '')));
            $decAt = trim((string)($decRow['decided_at'] ?? ''));
            $wid = 'WI-CHG-APPR-' . $rid . '-' . $so . '-' . $aSid;

            if ($rs === 'pending_approve' && $apprEval['active_sort'] === $so && $dec === '') {
                $pushWi([
                    'id' => $wid,
                    'record_id' => $recordId,
                    'assignee' => ['staff_id' => $aSid],
                    'type' => 'review',
                    'state' => 'pending',
                    'assigned_at' => pm_portal_to_rfc3339($rechainAt !== '' ? $rechainAt : $updatedRaw, $updatedAt),
                    'due_at' => null,
                    'priority' => 'normal',
                    'allowed_actions' => $actionsEnabled ? ['approve', 'reject'] : [],
                    'revision' => $rev,
                    'updated_at' => $updatedAt,
                    'title' => 'พิจารณาอนุมัติคำขอเปลี่ยนแปลงโครงการ',
                    'action_url' => $webBase . '/records/' . rawurlencode($recordId) . '/work',
                ]);
            } elseif ($dec !== '' && pm_portal_is_within_30_days($decAt)) {
                $pushWi([
                    'id' => $wid,
                    'record_id' => $recordId,
                    'assignee' => ['staff_id' => $aSid],
                    'type' => 'review',
                    'state' => 'completed',
                    'assigned_at' => pm_portal_to_rfc3339($rechainAt !== '' ? $rechainAt : $createdAt, $decAt),
                    'due_at' => null,
                    'priority' => 'normal',
                    'allowed_actions' => [],
                    'revision' => $rev,
                    'updated_at' => pm_portal_to_rfc3339($decAt, $updatedAt),
                    'title' => 'พิจารณาอนุมัติคำขอเปลี่ยนแปลงโครงการ',
                    'action_url' => null,
                ]);
            }
        }

        // Events from decisions
        $events = [];
        $subSidStr = ($subSid > 0 && isset($staffMap[(string)$subSid])) ? (string)$subSid : '';
        foreach ($certDec as $so => $d) {
            $dec = strtolower(trim((string)($d['decision'] ?? '')));
            if ($dec === '') {
                continue;
            }
            $actorSid = (int)($d['decided_by_staff_id'] ?? $d['staff_id'] ?? 0);
            $note = pm_portal_trim_text((string)($d['certifier_note'] ?? ''), 500);
            $recips = [];
            if ($subSidStr !== '' && $subSidStr !== (string)$actorSid) {
                $recips[$subSidStr] = $subSidStr;
            }
            $events[] = [
                'id' => 'EV-CHGC-' . $rid . '-' . $so,
                'record_id' => $recordId,
                'work_item_id' => $actorSid > 0 ? ('WI-CHG-CERT-' . $rid . '-' . $so . '-' . $actorSid) : null,
                'type' => $dec === 'approved' ? 'approved' : 'rejected',
                'actor' => pm_portal_staff_person_obj($actorSid, $staffMap, false),
                'occurred_at' => pm_portal_to_rfc3339((string)($d['decided_at'] ?? ''), $updatedAt),
                'message' => ($dec === 'approved' ? 'ผู้รับรองเห็นชอบคำขอเปลี่ยนแปลง' : 'ผู้รับรองตีกลับคำขอเปลี่ยนแปลง') . ($note !== '' ? ': ' . $note : ''),
                'recipients' => array_values($recips),
            ];
        }
        foreach ($apprDec as $so => $d) {
            $dec = strtolower(trim((string)($d['decision'] ?? '')));
            if ($dec === '') {
                continue;
            }
            $actorSid = (int)($d['decided_by_staff_id'] ?? $d['staff_id'] ?? 0);
            $note = pm_portal_trim_text((string)($d['certifier_note'] ?? ''), 500);
            $recips = [];
            if ($subSidStr !== '' && $subSidStr !== (string)$actorSid) {
                $recips[$subSidStr] = $subSidStr;
            }
            $events[] = [
                'id' => 'EV-CHGA-' . $rid . '-' . $so,
                'record_id' => $recordId,
                'work_item_id' => $actorSid > 0 ? ('WI-CHG-APPR-' . $rid . '-' . $so . '-' . $actorSid) : null,
                'type' => $dec === 'approved' ? 'approved' : 'rejected',
                'actor' => pm_portal_staff_person_obj($actorSid, $staffMap, false),
                'occurred_at' => pm_portal_to_rfc3339((string)($d['decided_at'] ?? ''), $updatedAt),
                'message' => ($dec === 'approved' ? 'ผู้อนุมัติเห็นชอบคำขอเปลี่ยนแปลง' : 'ผู้อนุมัติไม่อนุมัติคำขอเปลี่ยนแปลง') . ($note !== '' ? ': ' . $note : ''),
                'recipients' => array_values($recips),
            ];
        }

        return [
            'visible' => $visible,
            'record' => $record,
            'work_items' => $workItems,
            'events' => $events,
            'involved_staff' => array_values($involved),
        ];
    }
}

if (!function_exists('pm_portal_build_staff_projection')) {
    /**
     * คำนวณ records + work_items + events + summary ของบุคลากรคนหนึ่งครั้งเดียวแล้วใช้ร่วมกันทุก endpoint
     * @return array{records:array<int,array<string,mixed>>,work_items:array<int,array<string,mixed>>,events:array<int,array<string,mixed>>,summary:array<string,mixed>}
     */
    function pm_portal_build_staff_projection(
        mysqli $db_Project,
        mysqli $db_person,
        string $staffId,
        bool $useCache = true
    ): array {
        $cfg = pm_portal_config();
        $staffMap = pm_portal_load_staff_map($db_person);
        $nowIso = pm_portal_now_iso();

        // unknown numeric staff_id -> empty feed (200)
        if (!isset($staffMap[$staffId])) {
            return [
                'records' => [],
                'work_items' => [],
                'events' => [],
                'summary' => [
                    'pending_work' => 0,
                    'in_progress_work' => 0,
                    'overdue_work' => 0,
                    'as_of' => $nowIso,
                ],
            ];
        }

        $fp = pm_portal_db_fingerprint($db_Project);
        $cacheDir = pm_portal_ensure_cache_dir('projection');
        $cacheKey = 'proj_' . $staffId . '_' . substr($fp, 0, 16) . '_' . ($cfg['actions_enabled'] ? 'a1' : 'a0');
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';

        if ($useCache && is_file($cacheFile) && (time() - (int)@filemtime($cacheFile)) <= (int)$cfg['feed_cache_ttl']) {
            $raw = @file_get_contents($cacheFile);
            if (is_string($raw) && $raw !== '') {
                $dec = json_decode($raw, true);
                if (is_array($dec) && isset($dec['records'], $dec['work_items'], $dec['events'], $dec['summary'])) {
                    $dec['summary']['as_of'] = $nowIso;
                    return $dec;
                }
            }
        }

        $bulk = pm_portal_load_bulk_data($db_Project);
        $actionsEnabled = (bool)$cfg['actions_enabled'];

        $recordsById = [];
        $allRecordsLookup = [];
        $activeWorkById = [];
        $completedWorkById = [];
        $eventsForStaff = [];

        foreach ($bulk['projects'] as $proj) {
            $b = pm_portal_build_project_bundle($proj, $bulk, $staffMap, $staffId, $actionsEnabled);
            $allRecordsLookup[$b['record']['id']] = $b['record'];
            if ($b['visible']) {
                $recordsById[$b['record']['id']] = $b['record'];
            }
            foreach ($b['work_items'] as $wi) {
                if (in_array($wi['state'], ['pending', 'in_progress'], true)) {
                    $activeWorkById[$wi['id']] = $wi;
                } else {
                    $completedWorkById[$wi['id']] = $wi;
                }
                $recordsById[$b['record']['id']] = $b['record'];
            }
            foreach ($b['events'] as $ev) {
                $actorId = is_array($ev['actor']) ? (string)($ev['actor']['staff_id'] ?? '') : '';
                if ($actorId !== $staffId && in_array($staffId, $ev['recipients'], true)) {
                    $eventsForStaff[] = $ev;
                }
            }
        }

        foreach ($bulk['incReqs'] as $req) {
            $b = pm_portal_build_include_bundle($req, $bulk, $staffMap, $staffId, $actionsEnabled);
            $allRecordsLookup[$b['record']['id']] = $b['record'];
            if ($b['visible']) {
                $recordsById[$b['record']['id']] = $b['record'];
            }
            foreach ($b['work_items'] as $wi) {
                if (in_array($wi['state'], ['pending', 'in_progress'], true)) {
                    $activeWorkById[$wi['id']] = $wi;
                } else {
                    $completedWorkById[$wi['id']] = $wi;
                }
                $recordsById[$b['record']['id']] = $b['record'];
            }
            foreach ($b['events'] as $ev) {
                $actorId = is_array($ev['actor']) ? (string)($ev['actor']['staff_id'] ?? '') : '';
                if ($actorId !== $staffId && in_array($staffId, $ev['recipients'], true)) {
                    $eventsForStaff[] = $ev;
                }
            }
        }

        foreach ($bulk['chgReqs'] as $req) {
            $b = pm_portal_build_change_bundle($req, $bulk, $staffMap, $staffId, $actionsEnabled);
            $allRecordsLookup[$b['record']['id']] = $b['record'];
            if ($b['visible']) {
                $recordsById[$b['record']['id']] = $b['record'];
            }
            foreach ($b['work_items'] as $wi) {
                if (in_array($wi['state'], ['pending', 'in_progress'], true)) {
                    $activeWorkById[$wi['id']] = $wi;
                } else {
                    $completedWorkById[$wi['id']] = $wi;
                }
                $recordsById[$b['record']['id']] = $b['record'];
            }
            foreach ($b['events'] as $ev) {
                $actorId = is_array($ev['actor']) ? (string)($ev['actor']['staff_id'] ?? '') : '';
                if ($actorId !== $staffId && in_array($staffId, $ev['recipients'], true)) {
                    $eventsForStaff[] = $ev;
                }
            }
        }

        // งานที่หลุดจากคิวโดยไม่ได้ทำเอง -> ส่ง state=cancelled ต่อ 30 วัน พร้อม Record ของมัน
        $cancelledItems = pm_portal_seen_pending_sync($staffId, $activeWorkById, $completedWorkById);
        foreach ($cancelledItems as $cwi) {
            $rid = (string)$cwi['record_id'];
            if (isset($allRecordsLookup[$rid]) && !isset($recordsById[$rid])) {
                $recordsById[$rid] = $allRecordsLookup[$rid];
            }
        }

        $workItems = array_merge(array_values($activeWorkById), array_values($completedWorkById), $cancelledItems);
        usort($workItems, static function (array $a, array $b): int {
            $rank = ['pending' => 0, 'in_progress' => 1, 'completed' => 2, 'cancelled' => 3];
            $ra = $rank[$a['state']] ?? 9;
            $rb = $rank[$b['state']] ?? 9;
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            $c = strcmp((string)$b['updated_at'], (string)$a['updated_at']);
            return $c !== 0 ? $c : strcmp((string)$a['id'], (string)$b['id']);
        });

        $records = array_values($recordsById);
        usort($records, static function (array $a, array $b): int {
            $c = strcmp((string)$b['updated_at'], (string)$a['updated_at']);
            return $c !== 0 ? $c : strcmp((string)$a['id'], (string)$b['id']);
        });

        usort($eventsForStaff, static function (array $a, array $b): int {
            $c = strcmp((string)$b['occurred_at'], (string)$a['occurred_at']);
            return $c !== 0 ? $c : strcmp((string)$b['id'], (string)$a['id']);
        });

        // จำกัดไม่เกิน 2,000 รายการต่อ feed (events ไม่เกิน 300)
        if (count($records) > 2000) {
            $records = array_slice($records, 0, 2000);
        }
        if (count($workItems) > 2000) {
            $workItems = array_slice($workItems, 0, 2000);
        }
        if (count($eventsForStaff) > 300) {
            $eventsForStaff = array_slice($eventsForStaff, 0, 300);
        }

        // Summary จาก WorkItem ชุดเดียวกัน
        $pendingCount = 0;
        $inProgressCount = 0;
        $overdueCount = 0;
        $nowTs = time();
        foreach ($workItems as $wi) {
            if ($wi['state'] === 'pending') {
                $pendingCount++;
            } elseif ($wi['state'] === 'in_progress') {
                $inProgressCount++;
            } else {
                continue;
            }
            if (!empty($wi['due_at'])) {
                $dueTs = strtotime((string)$wi['due_at']);
                if ($dueTs !== false && $dueTs < $nowTs) {
                    $overdueCount++;
                }
            }
        }

        $result = [
            'records' => $records,
            'work_items' => $workItems,
            'events' => $eventsForStaff,
            'summary' => [
                'pending_work' => $pendingCount,
                'in_progress_work' => $inProgressCount,
                'overdue_work' => $overdueCount,
                'as_of' => $nowIso,
            ],
        ];

        if ($useCache) {
            @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        return $result;
    }
}

if (!function_exists('pm_portal_guess_mime')) {
    function pm_portal_guess_mime(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $map = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'zip' => 'application/zip',
            'txt' => 'text/plain',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }
}

if (!function_exists('pm_portal_get_record_detail')) {
    /**
     * อ่านรายละเอียด Record สดจากฐานข้อมูลเสมอ (ไม่ใช้ feed cache)
     * @return array<string, mixed>|null
     */
    function pm_portal_get_record_detail(
        mysqli $db_Project,
        mysqli $db_person,
        string $staffId,
        string $recordId
    ): ?array {
        $cfg = pm_portal_config();
        $webBase = rtrim((string)$cfg['portal_web_base'], '/');
        $staffMap = pm_portal_load_staff_map($db_person);
        if (!isset($staffMap[$staffId])) {
            return null;
        }
        $bulk = pm_portal_load_bulk_data($db_Project);
        $actionsEnabled = (bool)$cfg['actions_enabled'];

        // 1) PROJ-{id}
        if (preg_match('/^PROJ-([0-9]+)$/', $recordId, $m)) {
            $pid = (int)$m[1];
            if (!isset($bulk['projects'][$pid])) {
                return null;
            }
            $proj = $bulk['projects'][$pid];
            $b = pm_portal_build_project_bundle($proj, $bulk, $staffMap, $staffId, $actionsEnabled);
            if (!$b['visible']) {
                return null;
            }

            $unitCode = trim((string)($proj['code_unit'] ?? ''));
            $unitName = $bulk['units'][$unitCode] ?? $unitCode;

            // งบประมาณรวม
            $totalBudget = 0.0;
            $qBud = @mysqli_query($db_Project, "SELECT SUM(IFNULL(budget_amount,0)) AS s FROM project_budget WHERE project_id='" . $pid . "' AND IFNULL(status,'1') NOT IN ('99','99.0')");
            if ($qBud && ($rb = mysqli_fetch_assoc($qBud))) {
                $totalBudget = (float)($rb['s'] ?? 0);
            }
            if ($totalBudget <= 0 && !empty($proj['notify_approved_budget'])) {
                $totalBudget = (float)$proj['notify_approved_budget'];
            }

            $headsNames = [];
            foreach (($bulk['projHeads'][$pid] ?? []) as $hs) {
                if (isset($staffMap[(string)$hs])) {
                    $headsNames[] = $staffMap[(string)$hs]['display_name'];
                }
            }
            $coordsNames = [];
            foreach (($bulk['projStaffs'][$pid] ?? []) as $cs) {
                if (isset($staffMap[(string)$cs])) {
                    $coordsNames[] = $staffMap[(string)$cs]['display_name'];
                }
            }

            $fields = [
                ['key' => 'fiscal_year', 'label' => 'ปีงบประมาณ', 'type' => 'text', 'value' => trim((string)($proj['year_project'] ?? '')) ?: null],
                ['key' => 'unit', 'label' => 'หน่วยงานรับผิดชอบ', 'type' => 'text', 'value' => $unitName !== '' ? $unitName : null],
                ['key' => 'budget_total', 'label' => 'วงเงินงบประมาณ', 'type' => 'money', 'value' => pm_portal_money($totalBudget)],
            ];
            $range = pm_portal_project_date_range($proj);
            if ($range !== null) {
                $fields[] = ['key' => 'project_period', 'label' => 'ระยะเวลาดำเนินโครงการ', 'type' => 'date_range', 'value' => $range];
            }
            $loc = pm_portal_trim_text((string)($proj['location'] ?? ''), 255);
            if ($loc !== '') {
                $fields[] = ['key' => 'location', 'label' => 'สถานที่ดำเนินการ', 'type' => 'text', 'value' => $loc];
            }
            if (!empty($headsNames)) {
                $fields[] = ['key' => 'project_heads', 'label' => 'หัวหน้าโครงการ', 'type' => 'text', 'value' => implode(', ', $headsNames)];
            }
            if (!empty($coordsNames)) {
                $fields[] = ['key' => 'project_coordinators', 'label' => 'ผู้ประสานงาน', 'type' => 'text', 'value' => implode(', ', $coordsNames)];
            }
            $docNo = trim((string)($proj['approve_memo_doc_no'] ?? ''));
            if ($docNo !== '') {
                $fields[] = ['key' => 'memo_doc_no', 'label' => 'เลขที่บันทึกอนุมัติโครงการ', 'type' => 'text', 'value' => $docNo];
            }

            $sections = [
                [
                    'key' => 'project_overview',
                    'title' => 'ข้อมูลโครงการ',
                    'fields' => $fields,
                ],
            ];

            $detailFields = [];
            $obj = pm_portal_trim_text((string)($proj['objective'] ?? ''), 1500);
            if ($obj !== '') {
                $detailFields[] = ['key' => 'objective', 'label' => 'วัตถุประสงค์', 'type' => 'text', 'value' => $obj];
            }
            $reason = pm_portal_trim_text((string)($proj['reson'] ?? ''), 1500);
            if ($reason !== '') {
                $detailFields[] = ['key' => 'rationale', 'label' => 'หลักการและเหตุผล', 'type' => 'text', 'value' => $reason];
            }
            $target = pm_portal_trim_text((string)($proj['target_project'] ?? ''), 1000);
            if ($target !== '') {
                $detailFields[] = ['key' => 'target_group', 'label' => 'กลุ่มเป้าหมาย', 'type' => 'text', 'value' => $target];
            }
            $reply = pm_portal_trim_text((string)($proj['operate_submitter_reply_note'] ?? ''), 1000);
            if ($reply !== '') {
                $detailFields[] = ['key' => 'submitter_reply', 'label' => 'คำชี้แจงหลังแก้ไข', 'type' => 'text', 'value' => $reply];
            }
            if (!empty($detailFields)) {
                $sections[] = [
                    'key' => 'project_details',
                    'title' => 'สาระสำคัญโครงการ',
                    'fields' => $detailFields,
                ];
            }

            // Attachments from project_file
            $attachments = [];
            $qF = @mysqli_query($db_Project, "SELECT id, files, stored_file FROM project_file WHERE code_pre_project='" . $pid . "' AND IFNULL(status,'1') NOT IN ('99','99.0') ORDER BY id ASC");
            while ($qF && ($fr = mysqli_fetch_assoc($qF))) {
                $fid = (int)$fr['id'];
                $fname = trim((string)($fr['files'] ?? ''));
                $stored = trim((string)($fr['stored_file'] ?? ''));
                if ($stored === '') {
                    $stored = $fname;
                }
                if ($fid <= 0 || $stored === '') {
                    continue;
                }
                $attachments[] = [
                    'id' => 'PF-' . $fid,
                    'name' => $fname !== '' ? $fname : $stored,
                    'url' => $webBase . '/records/' . rawurlencode($recordId) . '/attachments/' . rawurlencode('PF-' . $fid),
                    'mime_type' => pm_portal_guess_mime($fname !== '' ? $fname : $stored),
                ];
            }

            $events = $b['events'];
            usort($events, static fn(array $x, array $y): int => strcmp((string)$y['occurred_at'], (string)$x['occurred_at']));

            return [
                'record' => $b['record'],
                'work_items' => $b['work_items'],
                'sections' => $sections,
                'attachments' => $attachments,
                'events' => $events,
            ];
        }

        // 2) INC-{id}
        if (preg_match('/^INC-([0-9]+)$/', $recordId, $m)) {
            $rid = (int)$m[1];
            if (!isset($bulk['incReqs'][$rid])) {
                return null;
            }
            $req = $bulk['incReqs'][$rid];
            $b = pm_portal_build_include_bundle($req, $bulk, $staffMap, $staffId, $actionsEnabled);
            if (!$b['visible']) {
                return null;
            }

            $itemList = [];
            $items = $bulk['incItems'][$rid] ?? [];
            foreach ($items as $it) {
                $ipid = (int)($it['project_id'] ?? 0);
                if (isset($bulk['projects'][$ipid])) {
                    $pp = $bulk['projects'][$ipid];
                    $itemList[] = trim((string)($pp['code_pre_project'] ?? '')) . ' ' . pm_portal_trim_text((string)($pp['name_project'] ?? ''), 120);
                }
            }

            $fields = [
                ['key' => 'budget_year', 'label' => 'ปีงบประมาณ', 'type' => 'text', 'value' => trim((string)($req['budget_year'] ?? '')) ?: null],
                ['key' => 'section_name', 'label' => 'ส่วนงาน', 'type' => 'text', 'value' => pm_portal_trim_text((string)($req['section_name'] ?? '')) ?: null],
                ['key' => 'project_count', 'label' => 'จำนวนโครงการในคำขอ', 'type' => 'text', 'value' => count($items) . ' โครงการ'],
            ];
            if (!empty($itemList)) {
                $fields[] = ['key' => 'projects', 'label' => 'รายการโครงการ', 'type' => 'text', 'value' => implode(' | ', $itemList)];
            }
            $reply = pm_portal_trim_text((string)($req['submitter_reply_note'] ?? ''), 1000);
            if ($reply !== '') {
                $fields[] = ['key' => 'submitter_reply', 'label' => 'คำชี้แจงผู้ยื่นคำขอ', 'type' => 'text', 'value' => $reply];
            }

            $events = $b['events'];
            usort($events, static fn(array $x, array $y): int => strcmp((string)$y['occurred_at'], (string)$x['occurred_at']));

            return [
                'record' => $b['record'],
                'work_items' => $b['work_items'],
                'sections' => [
                    [
                        'key' => 'include_request_details',
                        'title' => 'รายละเอียดคำขออนุมัติบรรจุแผน',
                        'fields' => $fields,
                    ],
                ],
                'attachments' => [],
                'events' => $events,
            ];
        }

        // 3) CHG-{id}
        if (preg_match('/^CHG-([0-9]+)$/', $recordId, $m)) {
            $rid = (int)$m[1];
            if (!isset($bulk['chgReqs'][$rid])) {
                return null;
            }
            $req = $bulk['chgReqs'][$rid];
            $b = pm_portal_build_change_bundle($req, $bulk, $staffMap, $staffId, $actionsEnabled);
            if (!$b['visible']) {
                return null;
            }

            $fields = [
                ['key' => 'project_code', 'label' => 'รหัสโครงการ', 'type' => 'text', 'value' => trim((string)($req['project_code'] ?? '')) ?: null],
                ['key' => 'project_name', 'label' => 'ชื่อโครงการ', 'type' => 'text', 'value' => pm_portal_trim_text((string)($req['project_name'] ?? '')) ?: null],
                ['key' => 'budget_amount', 'label' => 'วงเงินงบประมาณ', 'type' => 'money', 'value' => pm_portal_money($req['budget_amount'] ?? 0)],
                ['key' => 'budget_source', 'label' => 'แหล่งงบประมาณ', 'type' => 'text', 'value' => pm_portal_trim_text((string)($req['budget_source'] ?? ''), 1000) ?: null],
                ['key' => 'reason', 'label' => 'เหตุผลและความจำเป็นในการเปลี่ยนแปลง', 'type' => 'text', 'value' => pm_portal_trim_text((string)($req['reason'] ?? ''), 1500) ?: null],
            ];

            $detailsArr = json_decode((string)($req['details_json'] ?? ''), true);
            if (is_array($detailsArr) && !empty($detailsArr)) {
                $lines = [];
                foreach ($detailsArr as $idx => $d) {
                    if (!is_array($d)) {
                        continue;
                    }
                    $orig = pm_portal_trim_text((string)($d['original'] ?? ''), 250);
                    $chg = pm_portal_trim_text((string)($d['changed'] ?? ''), 250);
                    $lines[] = ($idx + 1) . '. เดิม: ' . ($orig !== '' ? $orig : '-') . ' -> ขอเปลี่ยนเป็น: ' . ($chg !== '' ? $chg : '-');
                }
                if (!empty($lines)) {
                    $fields[] = ['key' => 'change_items', 'label' => 'รายละเอียดการเปลี่ยนแปลง', 'type' => 'text', 'value' => implode(' ; ', $lines)];
                }
            }

            $attachments = [];
            $qF = @mysqli_query($db_Project, "SELECT id, original_name, stored_file FROM project_change_request_file WHERE request_id='" . $rid . "' AND status='1' ORDER BY id ASC");
            while ($qF && ($fr = mysqli_fetch_assoc($qF))) {
                $fid = (int)$fr['id'];
                $origName = trim((string)($fr['original_name'] ?? ''));
                $stored = trim((string)($fr['stored_file'] ?? ''));
                if ($fid <= 0 || $stored === '') {
                    continue;
                }
                $attachments[] = [
                    'id' => 'CHGF-' . $fid,
                    'name' => $origName !== '' ? $origName : $stored,
                    'url' => $webBase . '/records/' . rawurlencode($recordId) . '/attachments/' . rawurlencode('CHGF-' . $fid),
                    'mime_type' => pm_portal_guess_mime($origName !== '' ? $origName : $stored),
                ];
            }

            $events = $b['events'];
            usort($events, static fn(array $x, array $y): int => strcmp((string)$y['occurred_at'], (string)$x['occurred_at']));

            return [
                'record' => $b['record'],
                'work_items' => $b['work_items'],
                'sections' => [
                    [
                        'key' => 'change_request_details',
                        'title' => 'รายละเอียดคำขอเปลี่ยนแปลงโครงการ',
                        'fields' => $fields,
                    ],
                ],
                'attachments' => $attachments,
                'events' => $events,
            ];
        }

        return null;
    }
}

if (!function_exists('pm_portal_paginate_snapshot')) {
    /**
     * แบ่งหน้าด้วย snapshot (TTL 10 นาที) + HMAC-signed opaque cursor ผูก staff_id + endpoint + snapshot_id
     * @param array<int, array<string,mixed>> $items
     * @return array{ok:bool,status:int,error_code?:string,message?:string,data?:array<int,array<string,mixed>>,next_cursor?:?string,snapshot_id?:string}
     */
    function pm_portal_paginate_snapshot(
        string $staffId,
        string $endpoint,
        array $items,
        int $limit,
        ?string $cursor,
        ?string $requestedSnapshotId
    ): array {
        $cfg = pm_portal_config();
        $ttl = (int)$cfg['snapshot_ttl'];
        $secret = (string)($cfg['api_token'] !== '' ? $cfg['api_token'] : 'pbm-portal-fallback-key');
        $snapDir = pm_portal_ensure_cache_dir('snapshots');

        $encodeCursor = static function (array $payload) use ($secret): string {
            $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $b64 = rtrim(strtr(base64_encode((string)$json), '+/', '-_'), '=');
            $sig = hash_hmac('sha256', $b64, $secret);
            return $b64 . '.' . substr($sig, 0, 32);
        };

        $decodeCursor = static function (string $tok) use ($secret): ?array {
            $parts = explode('.', $tok, 2);
            if (count($parts) !== 2) {
                return null;
            }
            [$b64, $sig] = $parts;
            $expected = substr(hash_hmac('sha256', $b64, $secret), 0, 32);
            if (!hash_equals($expected, $sig)) {
                return null;
            }
            $pad = strlen($b64) % 4;
            if ($pad > 0) {
                $b64 .= str_repeat('=', 4 - $pad);
            }
            $raw = base64_decode(strtr($b64, '-_', '+/'), true);
            if (!is_string($raw)) {
                return null;
            }
            $dec = json_decode($raw, true);
            return is_array($dec) ? $dec : null;
        };

        if ($cursor !== null && $cursor !== '') {
            $cur = $decodeCursor($cursor);
            if ($cur === null) {
                return ['ok' => false, 'status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'cursor ไม่ถูกต้อง'];
            }
            if ((string)($cur['s'] ?? '') !== $staffId || (string)($cur['e'] ?? '') !== $endpoint) {
                return ['ok' => false, 'status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'cursor ไม่ตรงกับผู้ใช้หรือ endpoint'];
            }
            $snapId = (string)($cur['k'] ?? '');
            if ($requestedSnapshotId !== null && $requestedSnapshotId !== '' && $requestedSnapshotId !== $snapId) {
                return ['ok' => false, 'status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'snapshot_id ไม่ตรงกับ cursor'];
            }
            $offset = (int)($cur['o'] ?? -1);
            if ($snapId === '' || !preg_match('/^[a-f0-9]{16,64}$/', $snapId) || $offset < 0) {
                return ['ok' => false, 'status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'cursor ไม่ถูกต้อง'];
            }
            $snapFile = $snapDir . '/snap_' . $snapId . '.json';
            if (!is_file($snapFile) || (time() - (int)@filemtime($snapFile)) > $ttl) {
                @unlink($snapFile);
                return ['ok' => false, 'status' => 410, 'error_code' => 'CURSOR_EXPIRED', 'message' => 'snapshot หมดอายุแล้ว กรุณาเริ่มอ่านใหม่ตั้งแต่หน้าแรก'];
            }
            $rawSnap = @file_get_contents($snapFile);
            $snapData = is_string($rawSnap) ? json_decode($rawSnap, true) : null;
            if (!is_array($snapData) || (string)($snapData['staff_id'] ?? '') !== $staffId || (string)($snapData['endpoint'] ?? '') !== $endpoint) {
                return ['ok' => false, 'status' => 410, 'error_code' => 'CURSOR_EXPIRED', 'message' => 'snapshot หมดอายุแล้ว กรุณาเริ่มอ่านใหม่ตั้งแต่หน้าแรก'];
            }
            $snapItems = is_array($snapData['items'] ?? null) ? $snapData['items'] : [];
        } else {
            // สร้าง snapshot ใหม่
            $snapId = bin2hex(random_bytes(16));
            $offset = 0;
            $snapItems = array_values($items);
            $snapFile = $snapDir . '/snap_' . $snapId . '.json';
            @file_put_contents($snapFile, json_encode([
                'staff_id' => $staffId,
                'endpoint' => $endpoint,
                'created_at' => time(),
                'items' => $snapItems,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        $slice = array_slice($snapItems, $offset, $limit);
        $nextOffset = $offset + count($slice);
        $nextCursor = ($nextOffset < count($snapItems))
            ? $encodeCursor(['s' => $staffId, 'e' => $endpoint, 'k' => $snapId, 'o' => $nextOffset])
            : null;

        return [
            'ok' => true,
            'status' => 200,
            'data' => $slice,
            'next_cursor' => $nextCursor,
            'snapshot_id' => $snapId,
        ];
    }
}
