<?php
/**
 * Automated Test Suite — Pharmacy CMU Portal Integration Standard (pbm_project)
 * รันในฐานข้อมูลทดสอบแยก (สร้าง DB ชั่วคราวและลบทิ้งหลังจบ ไม่แตะฐาน production)
 *
 * วิธีรัน:
 *   docker exec app_pbm_php-fpm php /var/www/application/project/portal/test_suite.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set('Asia/Bangkok');

require_once '/var/www/application/config/config_db.php';
require_once '/var/www/application/project/config/portal.php';
require_once '/var/www/application/project/fn/portal_api_service.php';
require_once '/var/www/application/project/fn/portal_api_actions.php';

$passed = 0;
$failed = 0;
$assert = static function (bool $cond, string $name) use (&$passed, &$failed): void {
    if ($cond) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name}\n";
    }
};

$testDbName = trim((string)getenv('PORTAL_TEST_DB'));
if ($testDbName === '' || !preg_match('/^pbm_portal_test_[a-f0-9]{8}$/', $testDbName)) {
    fwrite(STDERR, "Set PORTAL_TEST_DB=pbm_portal_test_<hex8> (pre-created isolated DB)\n");
    exit(2);
}
$testCacheDir = sys_get_temp_dir() . '/pbm_portal_test_cache_' . bin2hex(random_bytes(4));
putenv('PORTAL_CACHE_DIR=' . $testCacheDir);

$dbTest = null;
try {
    if (!mysqli_select_db($db_Project, $testDbName)) {
        throw new RuntimeException('Cannot select isolated test database: ' . mysqli_error($db_Project));
    }
    $dbTest = $db_Project;

    $ddls = [
        "CREATE TABLE pre_project (
            id INT AUTO_INCREMENT PRIMARY KEY,
            year_project VARCHAR(6),
            code_pre_project VARCHAR(15),
            name_project TEXT,
            status INT DEFAULT 1,
            staff_insert VARCHAR(20),
            code_unit VARCHAR(20),
            status_project INT DEFAULT 0,
            submit_date DATETIME NULL,
            start_month VARCHAR(5) NULL,
            start_year VARCHAR(5) NULL,
            end_month VARCHAR(5) NULL,
            end_year VARCHAR(5) NULL,
            location VARCHAR(255) NULL,
            reson TEXT NULL,
            objective TEXT NULL,
            target_project TEXT NULL,
            dates DATETIME NULL,
            include_certifiers_json LONGTEXT NULL,
            operate_certifiers_json LONGTEXT NULL,
            operate_approvers_json LONGTEXT NULL,
            notify_result VARCHAR(100) NULL,
            notify_result_at DATETIME NULL,
            notify_approved_budget DECIMAL(15,2) NULL,
            result_report_certifiers_json LONGTEXT NULL,
            result_report_submitted_at DATETIME NULL,
            result_report_completed_at DATETIME NULL,
            result_report_plan_closed_at DATETIME NULL,
            result_report_submitter_reply_note MEDIUMTEXT NULL,
            result_report_submitter_reply_updated_at DATETIME NULL,
            cancelled_at DATETIME NULL,
            operate_submitter_reply_note MEDIUMTEXT NULL,
            operate_submitter_reply_updated_at DATETIME NULL,
            operate_submitter_reply_staff_id INT NULL,
            plan_admin_operate_rechain_submitted_at DATETIME NULL,
            approve_memo_doc_no VARCHAR(180) NULL
        ) ENGINE=InnoDB",
        "CREATE TABLE project_head (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, staff_project INT, status VARCHAR(10) DEFAULT '1')",
        "CREATE TABLE project_staff (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, staff_project INT, status VARCHAR(10) DEFAULT '1')",
        "CREATE TABLE project_person (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, staff_project INT, status VARCHAR(10) DEFAULT '1')",
        "CREATE TABLE project_budget (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, budget_amount DECIMAL(15,2) DEFAULT 0, status VARCHAR(10) DEFAULT '1')",
        "CREATE TABLE project_file (id INT AUTO_INCREMENT PRIMARY KEY, code_pre_project INT, files VARCHAR(255), stored_file VARCHAR(255), status VARCHAR(10) DEFAULT '1')",
        "CREATE TABLE set_unit (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(20), name VARCHAR(255))",
        "CREATE TABLE project_operate_certifier_decision (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, sort_order INT, staff_id INT, decision VARCHAR(16), certifier_note TEXT, decided_at DATETIME, decided_by_staff_id INT, UNIQUE KEY uq_op_c (project_id, sort_order))",
        "CREATE TABLE project_operate_approver_decision (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, sort_order INT, staff_id INT, decision VARCHAR(16), certifier_note TEXT, decided_at DATETIME, decided_by_staff_id INT, UNIQUE KEY uq_op_a (project_id, sort_order))",
        "CREATE TABLE project_result_report_certifier_decision (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, sort_order INT, staff_id INT, decided_by_staff_id INT, decision VARCHAR(16), certifier_note TEXT, decided_at DATETIME, created_at DATETIME, UNIQUE KEY uq_rep (project_id, sort_order))",
        "CREATE TABLE project_operate_action_log (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, action_type VARCHAR(32), actor_staff_id INT, sort_order INT, note TEXT, created_at DATETIME)",
        "CREATE TABLE project_plan_review_log (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, action_type VARCHAR(32), actor_staff_id INT, note TEXT, meta_json TEXT, created_at DATETIME)",
        "CREATE TABLE project_include_request (id INT AUTO_INCREMENT PRIMARY KEY, budget_year VARCHAR(10), section_name VARCHAR(255), subject VARCHAR(255), submitter_staff_id INT, status VARCHAR(32), plan_unit_decision VARCHAR(32) NULL, plan_unit_staff_id INT NULL, submitter_reply_note TEXT NULL, submitter_reply_updated_at DATETIME NULL, plan_unit_submitter_reply_note TEXT NULL, admin_fix_sent_at DATETIME NULL, admin_fix_sent_by INT NULL, created_at DATETIME, updated_at DATETIME NULL)",
        "CREATE TABLE project_include_request_certifier (id INT AUTO_INCREMENT PRIMARY KEY, request_id INT, sort_order INT, staff_id INT, position_title VARCHAR(255))",
        "CREATE TABLE project_include_request_certifier_decision (id INT AUTO_INCREMENT PRIMARY KEY, request_id INT, sort_order INT, staff_id INT, decision VARCHAR(16), certifier_note TEXT, decided_at DATETIME, decided_by_staff_id INT, UNIQUE KEY uq_inc (request_id, sort_order))",
        "CREATE TABLE project_include_request_item (id INT AUTO_INCREMENT PRIMARY KEY, request_id INT, project_id INT, sort_order INT DEFAULT 1)",
        "CREATE TABLE project_include_request_action_log (id INT AUTO_INCREMENT PRIMARY KEY, request_id INT, action_type VARCHAR(32), actor_staff_id INT, sort_order INT, note TEXT, created_at DATETIME)",
        "CREATE TABLE project_change_request (id INT AUTO_INCREMENT PRIMARY KEY, project_id INT, reason TEXT, details_json LONGTEXT, certifiers_json LONGTEXT, approvers_json LONGTEXT, project_name VARCHAR(255), project_code VARCHAR(32), budget_source TEXT, budget_amount DECIMAL(15,2), staff_insert INT, req_status VARCHAR(32), status VARCHAR(10) DEFAULT '1', plan_rechain_submitted_at DATETIME NULL, created_at DATETIME, updated_at DATETIME NULL)",
        "CREATE TABLE project_change_certifier_decision (id INT AUTO_INCREMENT PRIMARY KEY, request_id INT, project_id INT, sort_order INT, staff_id INT, decision VARCHAR(16), certifier_note TEXT, decided_at DATETIME, decided_by_staff_id INT, UNIQUE KEY uq_cc (request_id, sort_order))",
        "CREATE TABLE project_change_approver_decision (id INT AUTO_INCREMENT PRIMARY KEY, request_id INT, project_id INT, sort_order INT, staff_id INT, decision VARCHAR(16), certifier_note TEXT, decided_at DATETIME, decided_by_staff_id INT, UNIQUE KEY uq_ca (request_id, sort_order))",
        "CREATE TABLE project_change_request_file (id INT AUTO_INCREMENT PRIMARY KEY, request_id INT, original_name VARCHAR(255), stored_file VARCHAR(255), status VARCHAR(10) DEFAULT '1')",
    ];
    foreach ($ddls as $sql) {
        if (!mysqli_query($dbTest, $sql)) {
            throw new RuntimeException('DDL failed: ' . mysqli_error($dbTest));
        }
    }

    $staffMap = pm_portal_load_staff_map($db_person);
    $sids = array_map('strval', array_slice(array_keys($staffMap), 0, 4));
    [$sidReq, $sidCert1, $sidCert2, $sidUnrelated] = $sids;

    mysqli_query($dbTest, "INSERT INTO set_unit (id, code, name) VALUES (1, 'U01', 'หน่วยทดสอบ')");
    $opCertJson = mysqli_real_escape_string($dbTest, json_encode([
        ['sort_order' => 1, 'staff_id' => (int)$sidCert1],
        ['sort_order' => 2, 'staff_id' => (int)$sidCert2],
    ]));
    mysqli_query($dbTest, "INSERT INTO pre_project (id, year_project, code_pre_project, name_project, status, staff_insert, code_unit, status_project, submit_date, dates, location, objective, operate_certifiers_json, start_month, start_year, end_month, end_year)
        VALUES (101, '2570', 'S70-T01', 'โครงการทดสอบ Portal API', 1, '{$sidReq}', '1', 1, NOW(), NOW(), 'คณะเภสัชศาสตร์', 'ทดสอบระบบ', '{$opCertJson}', '10', '2569', '9', '2570')");
    mysqli_query($dbTest, "INSERT INTO project_budget (project_id, budget_amount, status) VALUES (101, 50000.00, '1')");

    $chgCertJson = mysqli_real_escape_string($dbTest, json_encode([
        ['sort_order' => 1, 'staff_id' => (int)$sidCert1],
    ]));
    mysqli_query($dbTest, "INSERT INTO project_change_request (id, project_id, reason, details_json, certifiers_json, project_name, project_code, budget_source, budget_amount, staff_insert, req_status, status, created_at, updated_at)
        VALUES (201, 101, 'ปรับกิจกรรม', '[]', '{$chgCertJson}', 'โครงการทดสอบ Portal API', 'S70-T01', 'งบรายได้', 50000.00, '{$sidReq}', 'pending', '1', NOW(), NOW())");

    echo "=== 1. Staff ID validation & unknown staff ===\n";
    $assert(pm_portal_validate_staff_id('153') === true, 'Valid numeric staff_id');
    $assert(pm_portal_validate_staff_id('E160028') === false, 'Reject E160028 staff code format (422)');
    $assert(pm_portal_validate_staff_id('user@cmu.ac.th') === false, 'Reject email format (422)');
    $unk = pm_portal_build_staff_projection($dbTest, $db_person, '999999999', false);
    $assert(empty($unk['records']) && empty($unk['work_items']) && $unk['summary']['pending_work'] === 0, 'Unknown numeric staff_id returns empty feed');
    $assert(pm_portal_get_record_detail($dbTest, $db_person, '999999999', 'PROJ-101') === null, 'Unknown staff_id detail returns null (404)');

    echo "=== 2. Permissions & Role Isolation ===\n";
    $pReq = pm_portal_build_staff_projection($dbTest, $db_person, $sidReq, false);
    $pC1 = pm_portal_build_staff_projection($dbTest, $db_person, $sidCert1, false);
    $pC2 = pm_portal_build_staff_projection($dbTest, $db_person, $sidCert2, false);
    $pUnrel = pm_portal_build_staff_projection($dbTest, $db_person, $sidUnrelated, false);

    $assert(count($pReq['records']) === 2 && $pReq['summary']['pending_work'] === 0, 'Requester sees PROJ-101 & CHG-201 with 0 pending work');
    $assert($pC1['summary']['pending_work'] === 2, 'Certifier 1 sees 2 pending work items (PROJ-101 & CHG-201)');
    $assert($pC2['summary']['pending_work'] === 0, 'Certifier 2 has 0 pending work while waiting for Certifier 1');
    $assert(empty($pUnrel['records']) && empty($pUnrel['work_items']), 'Unrelated staff sees 0 records and 0 work items');
    $assert(pm_portal_get_record_detail($dbTest, $db_person, $sidUnrelated, 'PROJ-101') === null, 'Unrelated staff cannot view PROJ-101 detail (404)');

    echo "=== 3. Stable ID/Revision, Pagination & Cursor Expiry (410) ===\n";
    $pC1Again = pm_portal_build_staff_projection($dbTest, $db_person, $sidCert1, false);
    $assert($pC1['records'][0]['revision'] === $pC1Again['records'][0]['revision'], 'Revision is stable across reads');
    $pg1 = pm_portal_paginate_snapshot($sidCert1, 'records', $pC1['records'], 1, null, null);
    $assert($pg1['ok'] && count($pg1['data']) === 1 && $pg1['next_cursor'] !== null, 'Page 1 returns 1 item + next_cursor');
    $pg2 = pm_portal_paginate_snapshot($sidCert1, 'records', $pC1['records'], 1, $pg1['next_cursor'], $pg1['snapshot_id']);
    $assert($pg2['ok'] && count($pg2['data']) === 1 && $pg2['next_cursor'] === null, 'Page 2 returns 2nd item + null next_cursor');
    @unlink($testCacheDir . '/snapshots/snap_' . $pg1['snapshot_id'] . '.json');
    $pgExp = pm_portal_paginate_snapshot($sidCert1, 'records', $pC1['records'], 1, $pg1['next_cursor'], $pg1['snapshot_id']);
    $assert(!$pgExp['ok'] && $pgExp['status'] === 410 && $pgExp['error_code'] === 'CURSOR_EXPIRED', 'Expired snapshot returns 410 CURSOR_EXPIRED');

    echo "=== 4. Section 10.6 Actions Tests (Isolated DB) ===\n";
    $opWi = null;
    $chgWi = null;
    foreach ($pC1['work_items'] as $wi) {
        if ($wi['record_id'] === 'PROJ-101') { $opWi = $wi; }
        if ($wi['record_id'] === 'CHG-201') { $chgWi = $wi; }
    }
    $assert($opWi !== null && $chgWi !== null, 'Found pending operate & change work items for Certifier 1');

    $rUnauth = pm_portal_perform_action($dbTest, $db_person, $sidCert2, $opWi['id'], 'approve', '', $opWi['revision'], 'idem-unauth-1');
    $assert($rUnauth['status'] === 404, '10.6.4 Out-of-queue staff gets 404');

    $rForbid = pm_portal_perform_action($dbTest, $db_person, $sidCert1, $opWi['id'], 'reject', 'เหตุผล', $opWi['revision'], 'idem-forbid-1');
    $assert($rForbid['status'] === 403, '10.6.4 Disallowed action (reject on operate certifier) gets 403');

    $rStale = pm_portal_perform_action($dbTest, $db_person, $sidCert1, $opWi['id'], 'approve', '', $opWi['revision'] + 99, 'idem-stale-1');
    $assert($rStale['status'] === 409, '10.6.5 Stale expected_revision gets 409');

    $rEmptyNote = pm_portal_perform_action($dbTest, $db_person, $sidCert1, $opWi['id'], 'return', '   ', $opWi['revision'], 'idem-empty-note');
    $assert($rEmptyNote['status'] === 422, '10.6.7 Return with empty note gets 422');

    $rApp = pm_portal_perform_action($dbTest, $db_person, $sidCert1, $opWi['id'], 'approve', 'เห็นชอบตามเสนอ', $opWi['revision'], 'idem-approve-1');
    $assert($rApp['status'] === 200 && $rApp['body']['work_item']['state'] === 'completed' && $rApp['body']['work_item']['allowed_actions'] === [] && $rApp['body']['work_item']['revision'] > $opWi['revision'], '10.6.1 Certifier 1 approve succeeds with completed state + incremented revision');

    $rReplay = pm_portal_perform_action($dbTest, $db_person, $sidCert1, $opWi['id'], 'approve', 'เห็นชอบตามเสนอ', $opWi['revision'], 'idem-approve-1');
    $assert($rReplay['status'] === 200 && !empty($rReplay['replayed']), '10.6.6 Same Idempotency-Key + same payload replays 200 without duplicating');
    $rConflict = pm_portal_perform_action($dbTest, $db_person, $sidCert1, $opWi['id'], 'approve', 'ข้อความต่างกัน', $opWi['revision'], 'idem-approve-1');
    $assert($rConflict['status'] === 409, '10.6.6 Same Idempotency-Key + different payload returns 409');

    $pC2After = pm_portal_build_staff_projection($dbTest, $db_person, $sidCert2, false);
    $c2Wi = null;
    foreach ($pC2After['work_items'] as $wi) {
        if ($wi['record_id'] === 'PROJ-101' && $wi['state'] === 'pending') { $c2Wi = $wi; }
    }
    $assert($c2Wi !== null, '10.6.1 Certifier 2 receives new pending WorkItem after Certifier 1 approves');

    $rRet = pm_portal_perform_action($dbTest, $db_person, $sidCert2, $c2Wi['id'], 'return', 'กรุณาปรับรายละเอียดงบประมาณ', $c2Wi['revision'], 'idem-return-1');
    $assert($rRet['status'] === 200 && $rRet['body']['record']['source_state']['code'] === '8', '10.6.2 Certifier 2 return sets status_project=8');
    $pReqAfterRet = pm_portal_build_staff_projection($dbTest, $db_person, $sidReq, false);
    $fixWi = null;
    foreach ($pReqAfterRet['work_items'] as $wi) {
        if ($wi['record_id'] === 'PROJ-101' && $wi['type'] === 'correct' && $wi['state'] === 'pending') { $fixWi = $wi; }
    }
    $assert($fixWi !== null && $fixWi['allowed_actions'] === [], '10.6.2 & 10.6.8 Requester gets correct WorkItem with allowed_actions=[]');
    $hasRetEvent = false;
    foreach ($pReqAfterRet['events'] as $ev) {
        if ($ev['record_id'] === 'PROJ-101' && $ev['type'] === 'returned' && strpos($ev['message'], 'งบประมาณ') !== false) {
            $hasRetEvent = true;
        }
    }
    $assert($hasRetEvent, '10.6.2 Requester receives returned Event with note');

    $rRej = pm_portal_perform_action($dbTest, $db_person, $sidCert1, $chgWi['id'], 'reject', 'ไม่เห็นชอบการเปลี่ยนแปลงนี้', $chgWi['revision'], 'idem-reject-chg');
    $assert($rRej['status'] === 200 && $rRej['body']['record']['state'] === 'rejected' && $rRej['body']['record']['current_step'] === null, '10.6.3 Change request reject sets state=rejected');

    echo "\nSUMMARY: {$passed} passed, {$failed} failed\n";
} finally {
    if (is_dir($testCacheDir)) {
        @exec('rm -rf ' . escapeshellarg($testCacheDir));
    }
}

exit($failed > 0 ? 1 : 0);
