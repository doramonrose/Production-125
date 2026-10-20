<?php
/**
 * API ฝั่งระบบโครงการ: รายการยืนยันเบิกจ่ายจากรายงานการใช้จ่าย
 * และรายการที่ยืนยันแล้วแต่ยังไม่บันทึกในระบบเบิกจ่าย (pending handoff)
 *
 * เรียกใช้:
 *   GET /project/User/api_pbm_withdraw_pending.php?project_code=S69-007
 *   GET /project/User/api_pbm_withdraw_pending.php?project_id=91
 *   GET /project/Plan/api_pbm_withdraw_pending.php?project_code=S69-007
 *
 * Query (optional):
 *   - project_code / project_id  (แนะนำส่งอย่างน้อยหนึ่งอย่าง เพื่อดึงรายการยืนยันจากรายงานการใช้จ่าย)
 *   - staff_id   (ถ้าไม่ส่ง จะใช้ $_SESSION['code'] เมื่อมี session)
 *   - limit      (default 20, max 50) — จำกัดเฉพาะ pending_confirms
 *
 * Response JSON:
 * {
 *   "status": "success",
 *   "meta": {
 *     "count": N,                    // จำนวน pending_confirms
 *     "confirmed_count": M,          // จำนวนรายการยืนยันในรายงานการใช้จ่าย
 *     "has_withdraw_count": ...,
 *     "pending_handoff_count": ...,
 *     "confirmed_no_withdraw_count": ...,
 *     "project_code": "...",
 *     "project_id": 91,
 *     "staff_id": "...",
 *     "table_ready": true|false
 *   },
 *   "data": [ ... pending_confirms ... ],
 *   "confirmed_lines": [
 *     {
 *       "line_id": 24,
 *       "amount": 50,
 *       "line_detail": "...",
 *       "activity_name": "...",
 *       "confirmed_at": "...",
 *       "reconcile": "has_withdraw|pending_handoff|confirmed_no_withdraw",
 *       "withdraw_id": 23,
 *       "withdraw_status": "อนุมัติ",
 *       "resume_url": "https://.../withdraw/project?handoff=..."
 *     }
 *   ]
 * }
 *
 * แหล่งข้อมูล pending: ตาราง project.pbm_withdraw_confirm หรือ fallback API เบิกจ่าย
 * แหล่งข้อมูล confirmed_lines: project_budget_disburse_line (รายงานการใช้จ่าย) + จับคู่กับ withdraws
 */
ob_start();
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

include_once __DIR__ . '/../../config/config_db.php';
include_once __DIR__ . '/../fn/main_project.php';

$fail = static function (int $code, string $msg) {
	while (ob_get_level() > 0) {
		@ob_end_clean();
	}
	http_response_code($code);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode([
		'status' => 'error',
		'message' => $msg,
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
};

if (!isset($db_Project) || !($db_Project instanceof mysqli)) {
	$fail(500, 'ไม่สามารถเชื่อมต่อฐานข้อมูลโครงการได้');
}

@mysqli_set_charset($db_Project, 'utf8mb4');

$projectCode = trim((string)($_GET['project_code'] ?? $_POST['project_code'] ?? ''));
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
$staffId = trim((string)($_GET['staff_id'] ?? $_POST['staff_id'] ?? ($_SESSION['code'] ?? '')));
$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 20);
if ($limit < 1) {
	$limit = 20;
}
if ($limit > 50) {
	$limit = 50;
}

$pre = null;
if ($projectId > 0) {
	$pre = function_exists('getPreProjectID') ? getPreProjectID($db_Project, $projectId) : null;
} elseif ($projectCode !== '') {
	$pre = function_exists('pm_find_pre_project_by_code')
		? pm_find_pre_project_by_code($db_Project, $projectCode)
		: null;
}
if (is_array($pre)) {
	$projectId = (int)($pre['id'] ?? $projectId);
	if ($projectCode === '') {
		$projectCode = trim((string)($pre['code_pre_project'] ?? ''));
	}
}

$tableExists = false;
$check = @mysqli_query($db_Project, "SHOW TABLES LIKE 'pbm_withdraw_confirm'");
if ($check && mysqli_num_rows($check) > 0) {
	$tableExists = true;
}

$items = [];

if ($tableExists) {
	$sql = "SELECT token, status, project_id, project_code, project_name, activity_id, activity_name,
	               applicant_staff_id, item_count, project_amount, resume_url, created_at, expires_at
	        FROM pbm_withdraw_confirm
	        WHERE status = 'pending'
	          AND (expires_at IS NULL OR expires_at > NOW())";
	$types = '';
	$params = [];

	if ($projectCode !== '') {
		$sql .= ' AND project_code = ?';
		$types .= 's';
		$params[] = $projectCode;
	}
	if ($staffId !== '') {
		$sql .= " AND (applicant_staff_id = ? OR applicant_staff_id IS NULL OR applicant_staff_id = '')";
		$types .= 's';
		$params[] = $staffId;
	}

	$sql .= ' ORDER BY created_at DESC LIMIT ?';
	$types .= 'i';
	$params[] = $limit;

	$stmt = mysqli_prepare($db_Project, $sql);
	if ($stmt) {
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$items[] = [
					'token' => (string)($row['token'] ?? ''),
					'project_id' => (string)($row['project_id'] ?? ''),
					'project_code' => (string)($row['project_code'] ?? ''),
					'project_name' => (string)($row['project_name'] ?? ''),
					'activity_id' => (string)($row['activity_id'] ?? ''),
					'activity_name' => (string)($row['activity_name'] ?? ''),
					'applicant_staff_id' => (string)($row['applicant_staff_id'] ?? ''),
					'item_count' => (int)($row['item_count'] ?? 0),
					'project_amount' => isset($row['project_amount']) ? (float)$row['project_amount'] : 0.0,
					'resume_url' => (string)($row['resume_url'] ?? ''),
					'created_at' => (string)($row['created_at'] ?? ''),
					'expires_at' => (string)($row['expires_at'] ?? ''),
					'source' => 'project.pbm_withdraw_confirm',
				];
			}
		}
		mysqli_stmt_close($stmt);
	}
}

// Fallback: ถามระบบเบิกจ่ายโดยตรง ถ้าตารางยังไม่มีหรือว่าง
if ($items === [] && function_exists('pm_pbm_fetch_pending_confirms')) {
	$pendRes = pm_pbm_fetch_pending_confirms($projectCode, $staffId);
	if (!empty($pendRes['ok']) && is_array($pendRes['data'] ?? null)) {
		foreach ($pendRes['data'] as $row) {
			if (!is_array($row)) {
				continue;
			}
			$row['source'] = 'withdraw.external.pending-confirms';
			$items[] = $row;
			if (count($items) >= $limit) {
				break;
			}
		}
	}
}

// รายการยืนยันจากรายงานการใช้จ่าย + สถานะว่ามีในระบบเบิกจ่ายแล้วหรือยัง
$confirmedLines = [];
if ($projectId > 0 && function_exists('pm_reconcile_confirmed_disburse_lines')) {
	$confirmedLines = pm_reconcile_confirmed_disburse_lines(
		$db_Project,
		$projectId,
		$projectCode,
		null,
		$items
	);
}

$hasWithdraw = 0;
$pendingHandoff = 0;
$confirmedOnly = 0;
foreach ($confirmedLines as $cl) {
	$rc = (string)($cl['reconcile'] ?? '');
	if ($rc === 'has_withdraw') {
		$hasWithdraw++;
	} elseif ($rc === 'pending_handoff') {
		$pendingHandoff++;
	} else {
		$confirmedOnly++;
	}
}

while (ob_get_level() > 0) {
	@ob_end_clean();
}

echo json_encode([
	'status' => 'success',
	'meta' => [
		'count' => count($items),
		'confirmed_count' => count($confirmedLines),
		'has_withdraw_count' => $hasWithdraw,
		'pending_handoff_count' => $pendingHandoff,
		'confirmed_no_withdraw_count' => $confirmedOnly,
		'project_code' => $projectCode,
		'project_id' => $projectId,
		'staff_id' => $staffId,
		'table_ready' => $tableExists,
	],
	'data' => $items,
	'confirmed_lines' => $confirmedLines,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
