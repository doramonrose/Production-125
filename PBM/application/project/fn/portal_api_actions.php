<?php
/**
 * Actions Service (POST /staff/{staffId}/work-items/{workItemId}/actions)
 * Pharmacy CMU Portal Integration Standard (ข้อ 10) — ระบบบริหารโครงการ (pbm_project)
 */
declare(strict_types=1);

require_once __DIR__ . '/portal_api_service.php';

if (!function_exists('pm_portal_ensure_idempotency_table')) {
    function pm_portal_ensure_idempotency_table(mysqli $db_Project): void
    {
        static $done = [];
        $k = spl_object_id($db_Project);
        if (isset($done[$k])) {
            return;
        }
        @mysqli_query(
            $db_Project,
            "CREATE TABLE IF NOT EXISTS portal_action_idempotency (
                idempotency_key VARCHAR(255) NOT NULL PRIMARY KEY,
                staff_id VARCHAR(20) NOT NULL,
                work_item_id VARCHAR(255) NOT NULL,
                payload_hash CHAR(64) NOT NULL,
                response_json LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        // ล้างรายการเก่าเกิน 24 ชม. แบบเบา ๆ
        @mysqli_query($db_Project, "DELETE FROM portal_action_idempotency WHERE created_at < (NOW() - INTERVAL 24 HOUR) LIMIT 50");
        $done[$k] = true;
    }
}

if (!function_exists('pm_portal_perform_action')) {
    /**
     * @return array{
     *   status: int,
     *   error_code?: string,
     *   message?: string,
     *   body?: array{record:array<string,mixed>,work_item:array<string,mixed>},
     *   affected_staff?: array<int,string>,
     *   replayed?: bool
     * }
     */
    function pm_portal_perform_action(
        mysqli $db_Project,
        mysqli $db_person,
        string $staffId,
        string $workItemId,
        string $action,
        string $note,
        int $expectedRevision,
        string $idempotencyKey
    ): array {
        $cfg = pm_portal_config();
        if (empty($cfg['actions_enabled'])) {
            return [
                'status' => 403,
                'error_code' => 'FORBIDDEN',
                'message' => 'ระบบยังไม่เปิดรับการดำเนินการผ่าน Portal',
            ];
        }

        if (!pm_portal_validate_staff_id($staffId)) {
            return ['status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'รูปแบบรหัสบุคลากรไม่ถูกต้อง'];
        }
        $staffMap = pm_portal_load_staff_map($db_person);
        if (!isset($staffMap[$staffId])) {
            return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบข้อมูลบุคลากรในระบบ'];
        }

        $idempotencyKey = trim($idempotencyKey);
        if ($idempotencyKey === '' || mb_strlen($idempotencyKey, 'UTF-8') > 255) {
            return ['status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'ต้องระบุ Idempotency-Key'];
        }

        $allowedActionEnums = ['approve', 'return', 'reject', 'complete', 'acknowledge'];
        if (!in_array($action, $allowedActionEnums, true)) {
            return ['status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'ประเภทคำสั่ง (action) ไม่ถูกต้อง'];
        }
        $note = trim(strip_tags($note));
        if (mb_strlen($note, 'UTF-8') > 1000) {
            return ['status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'ความเห็นต้องไม่เกิน 1,000 ตัวอักษร'];
        }
        if (in_array($action, ['return', 'reject'], true) && $note === '') {
            return ['status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'การส่งกลับแก้ไขหรือไม่อนุมัติต้องระบุเหตุผล'];
        }
        if ($expectedRevision < 1) {
            return ['status' => 422, 'error_code' => 'INVALID_REQUEST', 'message' => 'expected_revision ไม่ถูกต้อง'];
        }

        pm_portal_ensure_idempotency_table($db_Project);

        $payloadHash = hash('sha256', json_encode([
            'staff_id' => $staffId,
            'work_item_id' => $workItemId,
            'action' => $action,
            'note' => $note,
            'expected_revision' => $expectedRevision,
        ], JSON_UNESCAPED_UNICODE));

        // ตรวจ Idempotency-Key ก่อนล็อก
        $keyEsc = mysqli_real_escape_string($db_Project, $idempotencyKey);
        $qIdem = @mysqli_query(
            $db_Project,
            "SELECT staff_id, work_item_id, payload_hash, response_json
             FROM portal_action_idempotency
             WHERE idempotency_key='{$keyEsc}'
               AND created_at >= (NOW() - INTERVAL 24 HOUR)
             LIMIT 1"
        );
        if ($qIdem && ($idemRow = mysqli_fetch_assoc($qIdem))) {
            if (hash_equals((string)$idemRow['payload_hash'], $payloadHash)) {
                $cachedBody = json_decode((string)$idemRow['response_json'], true);
                if (is_array($cachedBody) && isset($cachedBody['record'], $cachedBody['work_item'])) {
                    return ['status' => 200, 'body' => $cachedBody, 'affected_staff' => [], 'replayed' => true];
                }
            }
            return [
                'status' => 409,
                'error_code' => 'CONFLICT',
                'message' => 'Idempotency-Key นี้ถูกใช้งานแล้วด้วยข้อมูลคำขอที่ต่างกัน',
            ];
        }

        // แยกประเภท WorkItem เพื่อกำหนด lock key ต่อ Record
        $lockName = 'pbm_portal_act_' . hash('crc32b', $workItemId);
        if (preg_match('/^WI-(OP-CERT|OP-APPR|OP-FIX|REP-CERT)-([0-9]+)-/', $workItemId, $lm)) {
            $lockName = 'pbm_portal_act_PROJ_' . $lm[2];
        } elseif (preg_match('/^WI-(INC-CERT|INC-FIX)-([0-9]+)-/', $workItemId, $lm)) {
            $lockName = 'pbm_portal_act_INC_' . $lm[2];
        } elseif (preg_match('/^WI-(CHG-CERT|CHG-APPR)-([0-9]+)-/', $workItemId, $lm)) {
            $lockName = 'pbm_portal_act_CHG_' . $lm[2];
        }

        $lockEsc = mysqli_real_escape_string($db_Project, $lockName);
        $qLock = @mysqli_query($db_Project, "SELECT GET_LOCK('{$lockEsc}', 5) AS got");
        $gotLock = $qLock && ($lr = mysqli_fetch_assoc($qLock)) && ((int)($lr['got'] ?? 0) === 1);
        if (!$gotLock) {
            return [
                'status' => 409,
                'error_code' => 'CONFLICT',
                'message' => 'รายการกำลังถูกดำเนินการอยู่ กรุณาลองใหม่อีกครั้ง',
            ];
        }

        try {
            // ตรวจ Idempotency-Key อีกครั้งภายใต้ lock (กัน race condition)
            $qIdem2 = @mysqli_query(
                $db_Project,
                "SELECT payload_hash, response_json
                 FROM portal_action_idempotency
                 WHERE idempotency_key='{$keyEsc}'
                   AND created_at >= (NOW() - INTERVAL 24 HOUR)
                 LIMIT 1"
            );
            if ($qIdem2 && ($idemRow2 = mysqli_fetch_assoc($qIdem2))) {
                if (hash_equals((string)$idemRow2['payload_hash'], $payloadHash)) {
                    $cachedBody = json_decode((string)$idemRow2['response_json'], true);
                    if (is_array($cachedBody) && isset($cachedBody['record'], $cachedBody['work_item'])) {
                        return ['status' => 200, 'body' => $cachedBody, 'affected_staff' => [], 'replayed' => true];
                    }
                }
                return [
                    'status' => 409,
                    'error_code' => 'CONFLICT',
                    'message' => 'Idempotency-Key นี้ถูกใช้งานแล้วด้วยข้อมูลคำขอที่ต่างกัน',
                ];
            }

            // อ่านข้อมูลสด (ไม่ใช้ cache ของ feed)
            $fresh = pm_portal_build_staff_projection($db_Project, $db_person, $staffId, false);
            $targetWi = null;
            foreach ($fresh['work_items'] as $wi) {
                if ((string)($wi['id'] ?? '') === $workItemId) {
                    $targetWi = $wi;
                    break;
                }
            }
            if ($targetWi === null || ($targetWi['state'] ?? '') !== 'pending') {
                return [
                    'status' => 404,
                    'error_code' => 'NOT_FOUND',
                    'message' => 'งานนี้ไม่อยู่ในคิวของคุณแล้ว หรือถูกดำเนินการไปแล้ว',
                ];
            }

            $allowed = is_array($targetWi['allowed_actions'] ?? null) ? $targetWi['allowed_actions'] : [];
            if (!in_array($action, $allowed, true)) {
                return [
                    'status' => 403,
                    'error_code' => 'FORBIDDEN',
                    'message' => 'งานนี้ไม่สามารถทำคำสั่งดังกล่าวผ่าน Portal ได้ กรุณาดำเนินการในระบบบริหารโครงการ',
                ];
            }

            $currentRev = (int)($targetWi['revision'] ?? 0);
            if ($currentRev !== $expectedRevision) {
                return [
                    'status' => 409,
                    'error_code' => 'CONFLICT',
                    'message' => 'รายการมีการเปลี่ยนแปลงแล้ว กรุณาโหลดหน้าใหม่',
                ];
            }

            $sid = (int)$staffId;
            $noteEsc = mysqli_real_escape_string($db_Project, $note);
            $recordId = (string)$targetWi['record_id'];
            $affectedStaff = [$staffId];

            mysqli_begin_transaction($db_Project);

            // 1) Operate certifier / approver (WI-OP-CERT-{pid}-{so}-{sid} or WI-OP-APPR-{pid}-{so}-{sid})
            if (preg_match('/^WI-OP-(CERT|APPR)-([0-9]+)-([0-9]+)-([0-9]+)$/', $workItemId, $m)) {
                $isAppr = ($m[1] === 'APPR');
                $pid = (int)$m[2];
                $so = (int)$m[3];
                if ((int)$m[4] !== $sid) {
                    mysqli_rollback($db_Project);
                    return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบงานในคิวของคุณ'];
                }
                $decTable = $isAppr ? 'project_operate_approver_decision' : 'project_operate_certifier_decision';
                $decision = ($action === 'approve') ? 'approved' : 'rejected';

                $qProj = mysqli_query($db_Project, "SELECT id, staff_insert, code_pre_project, name_project, status_project, operate_certifiers_json, operate_approvers_json, operate_submitter_reply_note FROM pre_project WHERE id='{$pid}' AND status='1' LIMIT 1");
                $proj = $qProj ? mysqli_fetch_assoc($qProj) : null;
                if (!$proj) {
                    mysqli_rollback($db_Project);
                    return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบข้อมูลโครงการ'];
                }
                $isRecheck = ((string)($proj['status_project'] ?? '') === '8' && trim((string)($proj['operate_submitter_reply_note'] ?? '')) !== '');

                $ok = mysqli_query(
                    $db_Project,
                    "INSERT INTO `{$decTable}` (project_id, sort_order, staff_id, decision, certifier_note, decided_at, decided_by_staff_id)
                     VALUES ('{$pid}', '{$so}', '{$sid}', '{$decision}', '{$noteEsc}', NOW(), '{$sid}')
                     ON DUPLICATE KEY UPDATE
                       decision=VALUES(decision),
                       certifier_note=VALUES(certifier_note),
                       decided_at=VALUES(decided_at),
                       decided_by_staff_id=VALUES(decided_by_staff_id),
                       staff_id=VALUES(staff_id)"
                );
                if (!$ok) {
                    throw new RuntimeException('บันทึกผลการพิจารณาไม่สำเร็จ');
                }

                $logType = ($decision === 'rejected')
                    ? 'certifier_rejected'
                    : ($isRecheck ? 'certifier_recheck_approved' : 'certifier_approved');
                mysqli_query(
                    $db_Project,
                    "INSERT INTO project_operate_action_log (project_id, action_type, actor_staff_id, sort_order, note, created_at)
                     VALUES ('{$pid}', '{$logType}', '{$sid}', '{$so}', " . ($note !== '' ? "'{$noteEsc}'" : "NULL") . ", NOW())"
                );

                if ($action === 'return') {
                    mysqli_query(
                        $db_Project,
                        "UPDATE pre_project
                         SET status_project='8', dates=NOW(),
                             operate_submitter_reply_note=NULL,
                             operate_submitter_reply_updated_at=NULL,
                             operate_submitter_reply_staff_id=NULL
                         WHERE id='{$pid}' LIMIT 1"
                    );
                } elseif ($isRecheck && $action === 'approve') {
                    mysqli_query(
                        $db_Project,
                        "UPDATE pre_project SET status_project='1', dates=NOW() WHERE id='{$pid}' AND status_project='8' LIMIT 1"
                    );
                } else {
                    mysqli_query($db_Project, "UPDATE pre_project SET dates=NOW() WHERE id='{$pid}' LIMIT 1");
                }

                $affectedStaff[] = (string)((int)($proj['staff_insert'] ?? 0));
                foreach (pm_portal_parse_chain_json((string)($proj['operate_certifiers_json'] ?? '')) as $c) {
                    $affectedStaff[] = (string)$c['staff_id'];
                }
                foreach (pm_portal_parse_chain_json((string)($proj['operate_approvers_json'] ?? '')) as $c) {
                    $affectedStaff[] = (string)$c['staff_id'];
                }
            }
            // 2) Include request certifier (WI-INC-CERT-{rid}-{so}-{sid})
            elseif (preg_match('/^WI-INC-CERT-([0-9]+)-([0-9]+)-([0-9]+)$/', $workItemId, $m)) {
                $rid = (int)$m[1];
                $so = (int)$m[2];
                if ((int)$m[3] !== $sid) {
                    mysqli_rollback($db_Project);
                    return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบงานในคิวของคุณ'];
                }
                $qReq = mysqli_query($db_Project, "SELECT * FROM project_include_request WHERE id='{$rid}' LIMIT 1");
                $req = $qReq ? mysqli_fetch_assoc($qReq) : null;
                if (!$req) {
                    mysqli_rollback($db_Project);
                    return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบคำขอบรรจุแผน'];
                }
                $isRecheck = (strtolower(trim((string)($req['status'] ?? ''))) === 'rejected' && trim((string)($req['submitter_reply_note'] ?? '')) !== '');
                $decision = ($action === 'approve') ? 'approved' : 'rejected';

                $ok = mysqli_query(
                    $db_Project,
                    "INSERT INTO project_include_request_certifier_decision (request_id, sort_order, staff_id, decision, certifier_note, decided_at, decided_by_staff_id)
                     VALUES ('{$rid}', '{$so}', '{$sid}', '{$decision}', '{$noteEsc}', NOW(), '{$sid}')
                     ON DUPLICATE KEY UPDATE
                       decision=VALUES(decision),
                       certifier_note=VALUES(certifier_note),
                       decided_at=VALUES(decided_at),
                       decided_by_staff_id=VALUES(decided_by_staff_id),
                       staff_id=VALUES(staff_id)"
                );
                if (!$ok) {
                    throw new RuntimeException('บันทึกผลการพิจารณาคำขอบรรจุแผนไม่สำเร็จ');
                }

                $logType = ($decision === 'rejected')
                    ? 'certifier_rejected'
                    : ($isRecheck ? 'certifier_recheck_approved' : 'certifier_approved');
                mysqli_query(
                    $db_Project,
                    "INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, sort_order, note, created_at)
                     VALUES ('{$rid}', '{$logType}', '{$sid}', '{$so}', '{$noteEsc}', NOW())"
                );

                if ($action === 'return') {
                    mysqli_query(
                        $db_Project,
                        "UPDATE project_include_request
                         SET status='rejected', admin_fix_sent_at=NULL, admin_fix_sent_by=NULL,
                             submitter_reply_note=NULL, submitter_reply_updated_at=NULL, updated_at=NOW()
                         WHERE id='{$rid}' LIMIT 1"
                    );
                    mysqli_query(
                        $db_Project,
                        "UPDATE pre_project
                         SET status_project='8', dates=NOW()
                         WHERE status='1' AND status_project IN ('2','3','4','8')
                           AND id IN (SELECT project_id FROM project_include_request_item WHERE request_id='{$rid}')"
                    );
                } else {
                    if ($isRecheck) {
                        mysqli_query(
                            $db_Project,
                            "UPDATE project_include_request SET status='submitted', updated_at=NOW() WHERE id='{$rid}' LIMIT 1"
                        );
                        mysqli_query(
                            $db_Project,
                            "UPDATE pre_project
                             SET status_project='3', dates=NOW()
                             WHERE status='1' AND status_project IN ('8','3')
                               AND id IN (SELECT project_id FROM project_include_request_item WHERE request_id='{$rid}')"
                        );
                    } else {
                        mysqli_query($db_Project, "UPDATE project_include_request SET updated_at=NOW() WHERE id='{$rid}' LIMIT 1");
                    }
                    // ถ้าผู้รับรองครบทุกคนแล้ว -> อัปเดตโครงการเป็น status_project='4' (เหมือน project_approve.php)
                    $qRem = mysqli_query(
                        $db_Project,
                        "SELECT COUNT(*) AS c
                         FROM project_include_request_certifier c
                         LEFT JOIN project_include_request_certifier_decision d
                           ON d.request_id = c.request_id AND d.sort_order = c.sort_order
                         WHERE c.request_id='{$rid}' AND (d.decision IS NULL OR d.decision <> 'approved')"
                    );
                    $remRow = $qRem ? mysqli_fetch_assoc($qRem) : ['c' => 1];
                    if ((int)($remRow['c'] ?? 1) === 0) {
                        mysqli_query(
                            $db_Project,
                            "UPDATE pre_project
                             SET status_project='4', dates=NOW()
                             WHERE status='1' AND status_project IN ('2','3','8')
                               AND id IN (SELECT project_id FROM project_include_request_item WHERE request_id='{$rid}')"
                        );
                    }
                }

                $affectedStaff[] = (string)((int)($req['submitter_staff_id'] ?? 0));
                $qC = mysqli_query($db_Project, "SELECT staff_id FROM project_include_request_certifier WHERE request_id='{$rid}'");
                while ($qC && ($cr = mysqli_fetch_assoc($qC))) {
                    $affectedStaff[] = (string)((int)$cr['staff_id']);
                }
            }
            // 3) Change request certifier / approver (WI-CHG-CERT-{rid}-{so}-{sid} or WI-CHG-APPR-{rid}-{so}-{sid})
            elseif (preg_match('/^WI-CHG-(CERT|APPR)-([0-9]+)-([0-9]+)-([0-9]+)$/', $workItemId, $m)) {
                $isAppr = ($m[1] === 'APPR');
                $rid = (int)$m[2];
                $so = (int)$m[3];
                if ((int)$m[4] !== $sid) {
                    mysqli_rollback($db_Project);
                    return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบงานในคิวของคุณ'];
                }
                $qReq = mysqli_query($db_Project, "SELECT * FROM project_change_request WHERE id='{$rid}' AND status='1' LIMIT 1");
                $req = $qReq ? mysqli_fetch_assoc($qReq) : null;
                if (!$req) {
                    mysqli_rollback($db_Project);
                    return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบคำขอเปลี่ยนแปลงโครงการ'];
                }
                $pid = (int)($req['project_id'] ?? 0);
                $decTable = $isAppr ? 'project_change_approver_decision' : 'project_change_certifier_decision';
                $decision = ($action === 'approve') ? 'approved' : 'rejected';

                $ok = mysqli_query(
                    $db_Project,
                    "INSERT INTO `{$decTable}` (request_id, project_id, sort_order, staff_id, decision, certifier_note, decided_at, decided_by_staff_id)
                     VALUES ('{$rid}', '{$pid}', '{$so}', '{$sid}', '{$decision}', '{$noteEsc}', NOW(), '{$sid}')
                     ON DUPLICATE KEY UPDATE
                       decision=VALUES(decision),
                       certifier_note=VALUES(certifier_note),
                       decided_at=VALUES(decided_at),
                       decided_by_staff_id=VALUES(decided_by_staff_id),
                       staff_id=VALUES(staff_id)"
                );
                if (!$ok) {
                    throw new RuntimeException('บันทึกผลการพิจารณาคำขอเปลี่ยนแปลงไม่สำเร็จ');
                }

                if ($action === 'reject') {
                    mysqli_query($db_Project, "UPDATE project_change_request SET req_status='rejected', updated_at=NOW() WHERE id='{$rid}' LIMIT 1");
                } else {
                    $chain = pm_portal_parse_chain_json((string)($isAppr ? ($req['approvers_json'] ?? '') : ($req['certifiers_json'] ?? '')));
                    $qAllDec = mysqli_query($db_Project, "SELECT sort_order, decision FROM `{$decTable}` WHERE request_id='{$rid}'");
                    $decMap = [];
                    while ($qAllDec && ($dr = mysqli_fetch_assoc($qAllDec))) {
                        $decMap[(int)$dr['sort_order']] = $dr;
                    }
                    $evalAfter = pm_portal_eval_chain($chain, $decMap);
                    if ($evalAfter['all_approved']) {
                        $nextStatus = $isAppr ? 'approved' : 'waiting_plan';
                        mysqli_query($db_Project, "UPDATE project_change_request SET req_status='{$nextStatus}', updated_at=NOW() WHERE id='{$rid}' LIMIT 1");
                    } else {
                        mysqli_query($db_Project, "UPDATE project_change_request SET updated_at=NOW() WHERE id='{$rid}' LIMIT 1");
                    }
                }

                $affectedStaff[] = (string)((int)($req['staff_insert'] ?? 0));
                foreach (pm_portal_parse_chain_json((string)($req['certifiers_json'] ?? '')) as $c) {
                    $affectedStaff[] = (string)$c['staff_id'];
                }
                foreach (pm_portal_parse_chain_json((string)($req['approvers_json'] ?? '')) as $c) {
                    $affectedStaff[] = (string)$c['staff_id'];
                }
            }
            // 4) Result report certifier (WI-REP-CERT-{pid}-{so}-{sid})
            elseif (preg_match('/^WI-REP-CERT-([0-9]+)-([0-9]+)-([0-9]+)$/', $workItemId, $m)) {
                $pid = (int)$m[1];
                $so = (int)$m[2];
                if ((int)$m[3] !== $sid) {
                    mysqli_rollback($db_Project);
                    return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบงานในคิวของคุณ'];
                }
                $qProj = mysqli_query($db_Project, "SELECT id, staff_insert, result_report_certifiers_json FROM pre_project WHERE id='{$pid}' AND status='1' LIMIT 1");
                $proj = $qProj ? mysqli_fetch_assoc($qProj) : null;
                if (!$proj) {
                    mysqli_rollback($db_Project);
                    return ['status' => 404, 'error_code' => 'NOT_FOUND', 'message' => 'ไม่พบโครงการ'];
                }
                $decision = ($action === 'approve') ? 'approved' : 'rejected';
                $ok = mysqli_query(
                    $db_Project,
                    "INSERT INTO project_result_report_certifier_decision (project_id, sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at, created_at)
                     VALUES ('{$pid}', '{$so}', '{$sid}', '{$sid}', '{$decision}', '{$noteEsc}', NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                       decision=VALUES(decision),
                       certifier_note=VALUES(certifier_note),
                       decided_at=VALUES(decided_at),
                       decided_by_staff_id=VALUES(decided_by_staff_id)"
                );
                if (!$ok) {
                    throw new RuntimeException('บันทึกผลการรับรองรายงานผลไม่สำเร็จ');
                }
                if ($action === 'return') {
                    mysqli_query(
                        $db_Project,
                        "UPDATE pre_project
                         SET dates=NOW(), result_report_submitter_reply_note=NULL, result_report_submitter_reply_updated_at=NULL
                         WHERE id='{$pid}' LIMIT 1"
                    );
                } else {
                    mysqli_query($db_Project, "UPDATE pre_project SET dates=NOW() WHERE id='{$pid}' LIMIT 1");
                }
                $affectedStaff[] = (string)((int)($proj['staff_insert'] ?? 0));
                foreach (pm_portal_parse_chain_json((string)($proj['result_report_certifiers_json'] ?? '')) as $c) {
                    $affectedStaff[] = (string)$c['staff_id'];
                }
            } else {
                mysqli_rollback($db_Project);
                return ['status' => 403, 'error_code' => 'FORBIDDEN', 'message' => 'ไม่รองรับคำสั่งสำหรับงานประเภทนี้ผ่าน Portal'];
            }

            mysqli_commit($db_Project);

            // อ่าน Record และ WorkItem ล่าสุดหลัง commit
            $after = pm_portal_build_staff_projection($db_Project, $db_person, $staffId, false);
            $updatedRecord = null;
            foreach ($after['records'] as $r) {
                if ((string)($r['id'] ?? '') === $recordId) {
                    $updatedRecord = $r;
                    break;
                }
            }
            $updatedWi = null;
            foreach ($after['work_items'] as $wi) {
                if ((string)($wi['id'] ?? '') === $workItemId) {
                    $updatedWi = $wi;
                    break;
                }
            }
            if ($updatedRecord === null) {
                return [
                    'status' => 503,
                    'error_code' => 'SOURCE_UNAVAILABLE',
                    'message' => 'บันทึกผลสำเร็จแล้วแต่ไม่สามารถอ่านข้อมูลล่าสุดได้ กรุณาลองใหม่ด้วยคำขอเดิม',
                ];
            }
            if ($updatedWi === null) {
                $updatedWi = $targetWi;
            }
            $updatedWi['id'] = $workItemId;
            $updatedWi['state'] = 'completed';
            $updatedWi['allowed_actions'] = [];
            $updatedWi['action_url'] = null;
            $updatedWi['revision'] = max($expectedRevision + 1, (int)($updatedWi['revision'] ?? 1));
            $updatedWi['updated_at'] = pm_portal_now_iso();

            $body = [
                'record' => $updatedRecord,
                'work_item' => $updatedWi,
            ];

            $respJsonEsc = mysqli_real_escape_string($db_Project, (string)json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $widEsc = mysqli_real_escape_string($db_Project, $workItemId);
            $sidEsc = mysqli_real_escape_string($db_Project, $staffId);
            @mysqli_query(
                $db_Project,
                "INSERT INTO portal_action_idempotency (idempotency_key, staff_id, work_item_id, payload_hash, response_json, created_at)
                 VALUES ('{$keyEsc}', '{$sidEsc}', '{$widEsc}', '{$payloadHash}', '{$respJsonEsc}', NOW())
                 ON DUPLICATE KEY UPDATE response_json=VALUES(response_json)"
            );

            return [
                'status' => 200,
                'body' => $body,
                'affected_staff' => array_values(array_unique(array_filter($affectedStaff, static fn($x) => preg_match('/^[0-9]{1,20}$/', (string)$x) && (int)$x > 0))),
            ];
        } catch (Throwable $e) {
            @mysqli_rollback($db_Project);
            error_log('[pbm_portal_action] ' . $e->getMessage());
            return [
                'status' => 503,
                'error_code' => 'SOURCE_UNAVAILABLE',
                'message' => 'ระบบต้นทางขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง',
            ];
        } finally {
            @mysqli_query($db_Project, "SELECT RELEASE_LOCK('{$lockEsc}')");
        }
    }
}
