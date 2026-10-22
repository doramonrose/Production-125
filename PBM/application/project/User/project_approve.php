<?php
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');
include_once(__DIR__ . '/../fn/main_staff.php');

if (function_exists('pm_ensure_midyear_schema') && isset($db_Project) && $db_Project) {
	pm_ensure_midyear_schema($db_Project);
}

if (function_exists('ensureIncludeRequestTables')) {
	ensureIncludeRequestTables($db_Project);
}
// -----------------------------------------------------------------------------
// ensure: action log table (เก็บทุกการกระทำเพื่อแสดงไทม์ไลน์)
// -----------------------------------------------------------------------------
@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_include_request_action_log (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	request_id INT UNSIGNED NOT NULL,
	action_type VARCHAR(32) NOT NULL DEFAULT '',
	actor_staff_id INT UNSIGNED NULL,
	sort_order TINYINT UNSIGNED NULL,
	note MEDIUMTEXT NULL,
	meta_json MEDIUMTEXT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_piral_req (request_id, created_at),
	KEY idx_piral_actor (actor_staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/**
 * หลังตีกลับแล้ว ยังต้องให้ผู้ยื่นส่งคำชี้แจงรอบใหม่หรือไม่
 * (เก็บคำชี้แจงเดิมไว้เสมอ — ใช้เวลตีกลับล่าสุดเทียบกับเวลาส่งคำชี้แจง)
 */
if (!function_exists('pm_include_submitter_needs_reply')) {
	function pm_include_submitter_needs_reply($db_Project, $request_id, $reply_note = '', $reply_updated_at = '', $last_reject_at = null): bool
	{
		$request_id = (int)$request_id;
		if ($request_id <= 0) {
			return false;
		}
		$note = trim((string)$reply_note);
		$replyAt = trim((string)$reply_updated_at);
		$lastRej = $last_reject_at !== null ? trim((string)$last_reject_at) : '';
		if ($lastRej === '' && $db_Project) {
			$q = @mysqli_query(
				$db_Project,
				"SELECT MAX(created_at) AS t
				 FROM project_include_request_action_log
				 WHERE request_id='" . $request_id . "' AND action_type='certifier_rejected'"
			);
			$r = $q ? mysqli_fetch_assoc($q) : null;
			$lastRej = trim((string)($r['t'] ?? ''));
			if ($lastRej === '') {
				$q2 = @mysqli_query(
					$db_Project,
					"SELECT MAX(decided_at) AS t
					 FROM project_include_request_certifier_decision
					 WHERE request_id='" . $request_id . "' AND decision='rejected'"
				);
				$r2 = $q2 ? mysqli_fetch_assoc($q2) : null;
				$lastRej = trim((string)($r2['t'] ?? ''));
			}
		}
		if ($note === '') {
			return true;
		}
		if ($replyAt === '') {
			return ($lastRej !== '');
		}
		if ($lastRej === '') {
			return false;
		}
		$tsRej = strtotime($lastRej);
		$tsReply = strtotime($replyAt);
		if ($tsRej === false || $tsReply === false) {
			return true;
		}
		return $tsRej > $tsReply;
	}
}

// การขอดำเนินการโครงการ (รายโครงการ): ตารางผลตรวจสอบตามลำดับ
@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_operate_certifier_decision (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	project_id INT UNSIGNED NOT NULL,
	sort_order TINYINT UNSIGNED NOT NULL,
	staff_id INT UNSIGNED NOT NULL,
	decision VARCHAR(16) NOT NULL DEFAULT '',
	certifier_note MEDIUMTEXT NULL,
	decided_at DATETIME NULL,
	decided_by_staff_id INT UNSIGNED NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uk_pocd_project_sort (project_id, sort_order),
	KEY idx_pocd_project (project_id),
	KEY idx_pocd_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_operate_action_log (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	project_id INT UNSIGNED NOT NULL,
	action_type VARCHAR(32) NOT NULL DEFAULT '',
	actor_staff_id INT UNSIGNED NULL,
	sort_order TINYINT UNSIGNED NULL,
	note MEDIUMTEXT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_poal_project (project_id, created_at),
	KEY idx_poal_actor (actor_staff_id),
	KEY idx_poal_sort (project_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
if (function_exists('pm_ensure_operate_approver_schema')) {
	pm_ensure_operate_approver_schema($db_Project);
}

// คอลัมน์การพิจารณาของหน่วยแผนและงบประมาณ (PLAN_ADMIN)
$chkPuDec = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_decision'");
if (!$chkPuDec || mysqli_num_rows($chkPuDec) === 0) {
	@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_decision VARCHAR(32) NULL DEFAULT NULL AFTER status");
}
$chkPuNote = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_note'");
if (!$chkPuNote || mysqli_num_rows($chkPuNote) === 0) {
	@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_note MEDIUMTEXT NULL AFTER plan_unit_decision");
}
$chkPuSid = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_staff_id'");
if (!$chkPuSid || mysqli_num_rows($chkPuSid) === 0) {
	@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_staff_id INT UNSIGNED NULL AFTER plan_unit_note");
}
$chkPuAt = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_decided_at'");
if (!$chkPuAt || mysqli_num_rows($chkPuAt) === 0) {
	@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_decided_at DATETIME NULL AFTER plan_unit_staff_id");
}
$chkPuAccBy = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_accepted_by'");
if (!$chkPuAccBy || mysqli_num_rows($chkPuAccBy) === 0) {
	@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_accepted_by INT UNSIGNED NULL DEFAULT NULL AFTER plan_unit_decided_at");
}
$chkPuAccAt = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_accepted_at'");
if (!$chkPuAccAt || mysqli_num_rows($chkPuAccAt) === 0) {
	@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_accepted_at DATETIME NULL DEFAULT NULL AFTER plan_unit_accepted_by");
}

// คอลัมน์คำชี้แจง/ตอบกลับกรณีหน่วยแผน "ตีกลับ"
$chkPuReply = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_submitter_reply_note'");
if (!$chkPuReply || mysqli_num_rows($chkPuReply) === 0) {
	@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_submitter_reply_note MEDIUMTEXT NULL AFTER plan_unit_decided_at");
}
$chkPuReplyAt = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_submitter_reply_updated_at'");
if (!$chkPuReplyAt || mysqli_num_rows($chkPuReplyAt) === 0) {
	@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_submitter_reply_updated_at DATETIME NULL AFTER plan_unit_submitter_reply_note");
}

$years_select = isset($_GET['years_select']) ? trim((string)$_GET['years_select']) : '';
if ($years_select === '') {
	$years_select = function_exists('getCurrentBudgetYear')
		? getCurrentBudgetYear()
		: (string)((int)date('Y') + 543 + ((int)date('n') >= 10 ? 1 : 0));
}

$project_group = isset($_GET['project_group']) ? trim((string)$_GET['project_group']) : 'draft';
if ($project_group === 'pending') {
	$project_group = 'draft';
} elseif ($project_group === 'history') {
	$project_group = 'plan';
}
if (!in_array($project_group, ['draft', 'plan'], true)) {
	$project_group = 'draft';
}

// ลิงก์ตรงเปิด modal อนุมัติ (ส่งให้ผู้ตรวจสอบตามลำดับ)
$pm_appr_open_request_id = isset($_GET['open_request_id']) ? (int)$_GET['open_request_id'] : 0;
if ($pm_appr_open_request_id <= 0 && isset($_GET['request_id'])) {
	$pm_appr_open_request_id = (int)$_GET['request_id'];
}
if ($pm_appr_open_request_id <= 0 && isset($_GET['change_request_id'])) {
	$pm_appr_open_request_id = (int)$_GET['change_request_id'];
}
$pm_appr_open_project_id = isset($_GET['open_project_id']) ? (int)$_GET['open_project_id'] : 0;
if ($pm_appr_open_project_id <= 0 && isset($_GET['project_id'])) {
	$pm_appr_open_project_id = (int)$_GET['project_id'];
}
$pm_appr_open_flow = isset($_GET['open_flow']) ? trim((string)$_GET['open_flow']) : 'include';
if (isset($_GET['change_request_id']) && (int)$_GET['change_request_id'] > 0) {
	$pm_appr_open_flow = 'change';
}
if (!in_array($pm_appr_open_flow, ['include', 'operate', 'report', 'change'], true)) {
	$pm_appr_open_flow = 'include';
}

$pm_appr_flow = isset($_GET['flow']) ? trim((string)$_GET['flow']) : '';
if ($pm_appr_flow === '') {
	$pm_appr_flow = $pm_appr_open_flow;
}
if (!in_array($pm_appr_flow, ['include', 'operate', 'report', 'change'], true)) {
	$pm_appr_flow = 'include';
}
$pm_appr_tab = isset($_GET['tab']) ? trim((string)$_GET['tab']) : 'pending';
if (!in_array($pm_appr_tab, ['sent', 'pending', 'done'], true)) {
	$pm_appr_tab = 'pending';
}
$pm_appr_flow_titles = [
	'include' => 'พิจารณาแผนงาน',
	'operate' => 'อนุมัติโครงการ',
	'report' => 'รายงานผล',
	'change' => 'เปลี่ยนแปลง/ยกเลิก',
];
$pm_appr_page_title = $pm_appr_flow_titles[$pm_appr_flow] ?? 'อนุมัติโครงการ';

$msg = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
$err = isset($_GET['err']) ? trim((string)$_GET['err']) : '';

// รองรับทั้ง query string และ POST body (บางครั้ง client/server ไม่คง $_GET['op'] บน POST)
$pm_appr_route_op = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
if ($pm_appr_route_op === '' && isset($_POST['op'])) {
	$pm_appr_route_op = trim((string)$_POST['op']);
}

$pm_appr_is_plan_admin = defined('PLAN_ADMIN') && PLAN_ADMIN;

// โมดัลอนุมัติอ่าน JSON — ถ้ามีความผิดพลาด session/ผู้ใช้ อย่าคืนเป็น HTML เพราะ jQuery พัง parse แล้วเหลือแค่โมดัลสรุปย่อ
$needs_json_modal_detail = (
	$pm_appr_route_op === 'get_operate_project_detail'
	|| $pm_appr_route_op === 'get_report_project_detail'
	|| $pm_appr_route_op === 'get_request_detail'
	|| $pm_appr_route_op === 'get_change_request_detail'
	|| $pm_appr_route_op === 'get_cancel_request_detail'
	|| $pm_appr_route_op === 'plan_admin_cancel_decide_ajax'
	|| $pm_appr_route_op === 'mark_approve_inbox_read'
);

$sess_email = $_SESSION['r_email'] ?? '';
$sess_code = $_SESSION['code'] ?? '';
if ($sess_email === '' || $sess_code === '') {
	if ($needs_json_modal_detail) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบข้อมูลผู้ใช้ — โปรดเข้าสู่ระบบใหม่'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	echo '<div class="alert alert-danger mb-0">ไม่พบข้อมูลผู้ใช้</div>';
	return;
}

$stmtMe = mysqli_prepare($db_person, 'SELECT id, code, unit_code FROM staff WHERE email = ? AND code = ? LIMIT 1');
if (!$stmtMe) {
	if ($needs_json_modal_detail) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => 0, 'msg' => 'เตรียมคำสั่งผู้ใช้ไม่สำเร็จ'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	echo '<div class="alert alert-danger mb-0">เตรียมคำสั่งไม่สำเร็จ</div>';
	return;
}
mysqli_stmt_bind_param($stmtMe, 'ss', $sess_email, $sess_code);
mysqli_stmt_execute($stmtMe);
$rsMe = mysqli_stmt_get_result($stmtMe);
$myRow = $rsMe ? mysqli_fetch_assoc($rsMe) : null;
if (!$myRow) {
	if ($needs_json_modal_detail) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบข้อมูลผู้ใช้ในระบบพนักงาน'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	echo '<div class="alert alert-danger mb-0">ไม่พบข้อมูลผู้ใช้</div>';
	return;
}

$sid = (int)($myRow['id'] ?? 0);
$staff_code = (string)($myRow['code'] ?? '');
$code_unit = (string)($myRow['unit_code'] ?? '');
if ($sid <= 0) {
	if ($needs_json_modal_detail) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => 0, 'msg' => 'ข้อมูลผู้ใช้ไม่สมบูรณ์'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	echo '<div class="alert alert-danger mb-0">ไม่พบข้อมูลผู้ใช้</div>';
	return;
}

if ($pm_appr_route_op === 'mark_approve_inbox_read') {
	if (ob_get_level() > 0) {
		@ob_clean();
	}
	header('Content-Type: application/json; charset=utf-8');
	$flowMk = isset($_POST['flow']) ? trim((string)$_POST['flow']) : '';
	$refMk = isset($_POST['ref_key']) ? trim((string)$_POST['ref_key']) : '';
	$okMk = function_exists('pm_approve_inbox_mark_read')
		? pm_approve_inbox_mark_read($db_Project, (int)$sid, $flowMk, $refMk)
		: false;
	echo json_encode(['ok' => $okMk ? 1 : 0], JSON_UNESCAPED_UNICODE);
	exit;
}

// -----------------------------------------------------------------------------
// AJAX: get request detail for modal
// -----------------------------------------------------------------------------
if ($pm_appr_route_op === 'get_request_detail') {
	// กัน output อื่นๆ ปน JSON (เช่น warning/notice)
	if (ob_get_level() > 0) {
		@ob_clean();
	}
	header('Content-Type: application/json; charset=utf-8');
	$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
	if ($request_id <= 0) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบคำขอ'], JSON_UNESCAPED_UNICODE);
		exit;
	}

	// permission: ต้องเป็นผู้ยื่น หรือ ผู้พิจารณาในคำขอ หรือ Admin หน่วยงานในขอบเขตโครงการของคำขอ
	$staffCodeEscPerm = mysqli_real_escape_string($db_Project, (string)$staff_code);
	$codeUnitEscPerm = mysqli_real_escape_string($db_Project, (string)$code_unit);
	$chkSql = "
		SELECT ir.id
		FROM project_include_request ir
		LEFT JOIN project_include_request_certifier c
			ON c.request_id = ir.id AND c.staff_id = '".(int)$sid."'
		WHERE ir.id = '".(int)$request_id."'
		  AND (
			ir.submitter_staff_id = '".(int)$sid."'
			OR c.staff_id = '".(int)$sid."'
			OR EXISTS (
				SELECT 1
				FROM project_include_request_item i
				JOIN pre_project p ON p.id = i.project_id AND p.status = '1'
				WHERE i.request_id = ir.id
				  AND (
					EXISTS (
						SELECT 1
						FROM set_department_admin sda
						WHERE (sda.staff_admin = '".$staffCodeEscPerm."' OR sda.staff_admin = '".(int)$sid."')
						  AND (
							sda.unit_code = p.code_unit
							OR EXISTS (
								SELECT 1 FROM set_unit su
								WHERE su.status != '99'
								  AND CAST(su.id AS CHAR) = sda.unit_code
								  AND (CAST(su.id AS CHAR) = IFNULL(p.code_unit,'') OR IFNULL(su.code,'') = IFNULL(p.code_unit,''))
							)
						  )
					)
					OR ('".$codeUnitEscPerm."' <> '' AND IFNULL(p.code_unit,'') <> '' AND p.code_unit = '".$codeUnitEscPerm."')
				  )
			)
		  )
		LIMIT 1
	";
	$chk = @mysqli_query($db_Project, $chkSql);
	if ((!$chk || mysqli_num_rows($chk) === 0) && !$pm_appr_is_plan_admin) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่มีสิทธิ์เข้าถึงคำขอนี้'], JSON_UNESCAPED_UNICODE);
		exit;
	}

	$req = null;
	$qReq = @mysqli_query($db_Project, "SELECT * FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
	if ($qReq) {
		$req = mysqli_fetch_assoc($qReq) ?: null;
	}
	if (!$req) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบข้อมูลคำขอ'], JSON_UNESCAPED_UNICODE);
		exit;
	}

	// ensure: submitter_reply_note column
	$chkReplyCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'submitter_reply_note'");
	if (!$chkReplyCol || mysqli_num_rows($chkReplyCol) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN submitter_reply_note MEDIUMTEXT NULL AFTER letter_body");
		// reload row (in case added)
		$qReq2 = @mysqli_query($db_Project, "SELECT * FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
		if ($qReq2) {
			$req = mysqli_fetch_assoc($qReq2) ?: $req;
		}
	}
	// ensure: submitter_reply_updated_at column (เวลาแก้ไข/ส่งคำชี้แจงล่าสุด)
	$chkReplyAtCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'submitter_reply_updated_at'");
	if (!$chkReplyAtCol || mysqli_num_rows($chkReplyAtCol) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN submitter_reply_updated_at DATETIME NULL AFTER submitter_reply_note");
		$qReq3 = @mysqli_query($db_Project, "SELECT * FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
		if ($qReq3) {
			$req = mysqli_fetch_assoc($qReq3) ?: $req;
		}
	}
	$chkPuDec2 = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_decision'");
	if (!$chkPuDec2 || mysqli_num_rows($chkPuDec2) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_decision VARCHAR(32) NULL DEFAULT NULL AFTER status");
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_note MEDIUMTEXT NULL AFTER plan_unit_decision");
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_staff_id INT UNSIGNED NULL AFTER plan_unit_note");
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_decided_at DATETIME NULL AFTER plan_unit_staff_id");
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_accepted_by INT UNSIGNED NULL DEFAULT NULL AFTER plan_unit_decided_at");
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_accepted_at DATETIME NULL DEFAULT NULL AFTER plan_unit_accepted_by");
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_submitter_reply_note MEDIUMTEXT NULL AFTER plan_unit_accepted_at");
		@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_submitter_reply_updated_at DATETIME NULL AFTER plan_unit_submitter_reply_note");
		$qReqPu = @mysqli_query($db_Project, "SELECT * FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
		if ($qReqPu) {
			$req = mysqli_fetch_assoc($qReqPu) ?: $req;
		}
	}
	// backfill: เคสข้อมูลเดิมมีคำชี้แจงแล้ว แต่ไม่มีเวลาแก้ไข
	$srNoteTmp = trim((string)($req['submitter_reply_note'] ?? ''));
	$srAtTmp = trim((string)($req['submitter_reply_updated_at'] ?? ''));
	if ($srNoteTmp !== '' && $srAtTmp === '') {
		// ใช้ updated_at (ของคำขอ) เป็นค่าเริ่มต้น ถ้าไม่มีให้ใช้ created_at
		$base = trim((string)($req['updated_at'] ?? ''));
		if ($base === '' || $base === '0000-00-00 00:00:00') {
			$base = trim((string)($req['created_at'] ?? ''));
		}
		if ($base !== '' && $base !== '0000-00-00 00:00:00') {
			$baseEsc = mysqli_real_escape_string($db_Project, $base);
			@mysqli_query($db_Project, "UPDATE project_include_request SET submitter_reply_updated_at='".$baseEsc."' WHERE id='".(int)$request_id."' AND (submitter_reply_updated_at IS NULL OR submitter_reply_updated_at='0000-00-00 00:00:00' OR submitter_reply_updated_at='') LIMIT 1");
			$qReq4 = @mysqli_query($db_Project, "SELECT * FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
			if ($qReq4) {
				$req = mysqli_fetch_assoc($qReq4) ?: $req;
			}
		}
	}

	// สรุปในตาราง DB บันทึกตอนยื่นคำขอ — คีย์กลุ่มอาจไม่ตรงกับการคำนวณความสอดคล้องปัจจุบัน จึงสร้างแถวสรุปโมดัลจากโครงการในคำขอจริง
	$summary = [];
	if (function_exists('buildIncludeRequestDetailSummaryRows')) {
		@include_once(__DIR__ . '/../../KPI/fn/main_kpi.php');
		$summary = buildIncludeRequestDetailSummaryRows($db_Project, $db_KPI, $request_id);
	}
	if (empty($summary)) {
		$qSum = @mysqli_query($db_Project, "SELECT * FROM project_include_request_summary WHERE request_id='".(int)$request_id."' ORDER BY sort_order ASC, id ASC");
		while ($qSum && ($r = mysqli_fetch_assoc($qSum))) {
			$summary[] = $r;
		}

		if (function_exists('buildIncludeRequestSummaryProjectBuckets')) {
			@include_once(__DIR__ . '/../../KPI/fn/main_kpi.php');
			$sumBuckets = buildIncludeRequestSummaryProjectBuckets($db_Project, $db_KPI, $request_id);
			foreach ($summary as &$srow) {
				$rk = (string)($srow['row_kind'] ?? '');
				if ($rk === 'data') {
					$lk = (string)($srow['section_group'] ?? '') . "\0" . (string)($srow['consistency_label'] ?? '');
					$items = $sumBuckets[$lk] ?? [];
					if (empty($items)) {
						foreach ($sumBuckets as $mk => $mit) {
							if (!is_array($mit) || empty($mit)) {
								continue;
							}
							$p = explode("\0", (string)$mk, 2);
							if (isset($p[1]) && (string)$p[1] === (string)($srow['consistency_label'] ?? '')) {
								$items = $mit;
								break;
							}
						}
					}
					$srow['include_project_items'] = $items;
				} else {
					$srow['include_project_items'] = [];
				}
			}
			unset($srow);
		}
	}

	// sender name
	$sender_id = (int)($req['submitter_staff_id'] ?? 0);
	$sender_name = '-';
	if ($sender_id > 0) {
		$qS = @mysqli_query($db_person, "SELECT fname, lname FROM staff WHERE id='".$sender_id."' LIMIT 1");
		if ($qS && ($sr = mysqli_fetch_assoc($qS))) {
			$sender_name = trim((string)(($sr['fname'] ?? '') . ' ' . ($sr['lname'] ?? '')));
			if ($sender_name === '') $sender_name = 'Staff #' . $sender_id;
		}
	}

	// ผู้รับรอง + สถานะลำดับ (สำหรับแผง UI หลังตารางความสอดคล้อง)
	$certifiers_payload = [];
	$is_reviewer = false;
	$my_sort_order = 0;
	$my_decision = '';
	$can_act_modal = false;
	$current_turn_sort = 0;
	$current_turn_name = '';
	$projects_pipeline_ok = false;

	$qCert = @mysqli_query(
		$db_Project,
		"SELECT sort_order, staff_id, position_title
		 FROM project_include_request_certifier
		 WHERE request_id='" . (int)$request_id . "'
		 ORDER BY sort_order ASC"
	);
	$certList = [];
	while ($qCert && ($cr = mysqli_fetch_assoc($qCert))) {
		$so = (int)($cr['sort_order'] ?? 0);
		if ($so <= 0) {
			continue;
		}
		$certList[$so] = [
			'staff_id' => (int)($cr['staff_id'] ?? 0),
			'position_title' => (string)($cr['position_title'] ?? ''),
		];
	}
	$myOrders = [];
	foreach ($certList as $so => $cInf) {
		if ((int)($cInf['staff_id'] ?? 0) === (int)$sid) {
			$myOrders[] = (int)$so;
		}
	}
	$is_reviewer = !empty($myOrders);

	$decisionsBySort = [];
	// รองรับทั้งกรณีมี/ไม่มีคอลัมน์ certifier_note
	$qDec = @mysqli_query(
		$db_Project,
		"SELECT sort_order, decision, decided_at, certifier_note
		 FROM project_include_request_certifier_decision
		 WHERE request_id='" . (int)$request_id . "'"
	);
	if (!$qDec) {
		$qDec = @mysqli_query(
			$db_Project,
			"SELECT sort_order, decision, decided_at
			 FROM project_include_request_certifier_decision
			 WHERE request_id='" . (int)$request_id . "'"
		);
	}
	while ($qDec && ($dr = mysqli_fetch_assoc($qDec))) {
		$dso = (int)($dr['sort_order'] ?? 0);
		if ($dso > 0) {
			$decisionsBySort[$dso] = [
				'decision' => (string)($dr['decision'] ?? ''),
				'decided_at' => (string)($dr['decided_at'] ?? ''),
				'certifier_note' => (string)($dr['certifier_note'] ?? ''),
			];
		}
	}

	$allSortOrders = array_keys($certList);
	sort($allSortOrders, SORT_NUMERIC);

	// เลือกลำดับของ "ฉัน" ที่ควรทำงานจริง (กรณีชื่อซ้ำหลายลำดับ)
	if ($is_reviewer) {
		sort($myOrders, SORT_NUMERIC);
		foreach ($myOrders as $candidateSo) {
			$cDec = (string)(($decisionsBySort[$candidateSo]['decision'] ?? '') ?: '');
			if ($cDec !== '') {
				continue; // ทำไปแล้ว
			}
			$prevOk = true;
			foreach ($allSortOrders as $pso) {
				if ($pso >= $candidateSo) break;
				$pd = (string)(($decisionsBySort[$pso]['decision'] ?? '') ?: '');
				if ($pd !== 'approved') { $prevOk = false; break; }
			}
			if ($prevOk) {
				$my_sort_order = (int)$candidateSo;
				break;
			}
		}
		// fallback: ถ้าไม่มีลำดับที่ทำได้ ให้เอาลำดับแรกของฉันไว้แสดงข้อมูล
		if ($my_sort_order <= 0) {
			$my_sort_order = (int)($myOrders[0] ?? 0);
		}
		if ($my_sort_order > 0) {
			$my_decision = (string)(($decisionsBySort[$my_sort_order]['decision'] ?? '') ?: '');
		}
	}

	$pendingCheckSql = "
		SELECT
			SUM(CASE WHEN p.status_project='4' THEN 1 ELSE 0 END) AS cnt4,
			COUNT(*) AS cntAll
		FROM pre_project p
		WHERE p.status='1'
		  AND p.id IN (
				SELECT i.project_id FROM project_include_request_item i WHERE i.request_id='" . (int)$request_id . "'
		  )
	";
	$qPending = @mysqli_query($db_Project, $pendingCheckSql);
	$pendingRow = $qPending ? mysqli_fetch_assoc($qPending) : null;
	$cnt4 = (int)($pendingRow['cnt4'] ?? 0);
	$cntAll = (int)($pendingRow['cntAll'] ?? 0);
	$projects_pipeline_ok = ($cntAll > 0 && $cnt4 === $cntAll);

	$project_ids_payload = [];
	$qPi = @mysqli_query(
		$db_Project,
		"SELECT project_id FROM project_include_request_item WHERE request_id='" . (int)$request_id . "' ORDER BY sort_order ASC"
	);
	while ($qPi && ($pi = mysqli_fetch_assoc($qPi))) {
		$ppid = (int)($pi['project_id'] ?? 0);
		if ($ppid > 0) {
			$project_ids_payload[] = $ppid;
		}
	}

	$reqStatusForModal = (string)($req['status'] ?? '');
	$is_submitter_detail = ((int)($req['submitter_staff_id'] ?? 0) === (int)$sid);
	$submitter_reply_note = trim((string)($req['submitter_reply_note'] ?? ''));
	$submitter_reply_updated_at = trim((string)($req['submitter_reply_updated_at'] ?? ''));
	if (function_exists('ensureIncludeRequestTables')) {
		ensureIncludeRequestTables($db_Project);
	}
	$returned_items = function_exists('pm_include_returned_items')
		? pm_include_returned_items($db_Project, $request_id)
		: [];
	$all_projects_for_return = [];
	$qProjRet = @mysqli_query(
		$db_Project,
		"SELECT i.project_id, i.return_selected, i.fix_status, i.fix_note, i.fix_at, i.return_cert_note,
		        p.code_pre_project, p.name_project, p.status_project
		 FROM project_include_request_item i
		 LEFT JOIN pre_project p ON p.id = i.project_id
		 WHERE i.request_id='" . (int)$request_id . "'
		 ORDER BY i.sort_order ASC, i.id ASC"
	);
	if (!$qProjRet) {
		$qProjRet = @mysqli_query(
			$db_Project,
			"SELECT i.project_id, p.code_pre_project, p.name_project, p.status_project
			 FROM project_include_request_item i
			 LEFT JOIN pre_project p ON p.id = i.project_id
			 WHERE i.request_id='" . (int)$request_id . "'
			 ORDER BY i.id ASC"
		);
	}
	while ($qProjRet && ($pr = mysqli_fetch_assoc($qProjRet))) {
		$all_projects_for_return[] = [
			'project_id' => (int)($pr['project_id'] ?? 0),
			'code' => (string)($pr['code_pre_project'] ?? ''),
			'name' => (string)($pr['name_project'] ?? ''),
			'status_project' => (string)($pr['status_project'] ?? ''),
			'return_selected' => (int)($pr['return_selected'] ?? 0),
			'fix_status' => (string)($pr['fix_status'] ?? ''),
			'fix_note' => (string)($pr['fix_note'] ?? ''),
			'fix_at' => (string)($pr['fix_at'] ?? ''),
			'return_cert_note' => (string)($pr['return_cert_note'] ?? ''),
		];
	}
	if (empty($all_projects_for_return) && !empty($project_ids_payload)) {
		foreach ($project_ids_payload as $__pidRet) {
			$__pidRet = (int)$__pidRet;
			if ($__pidRet <= 0) {
				continue;
			}
			$codeRet = '';
			$nameRet = '';
			$stRet = '';
			$qNm = @mysqli_query($db_Project, "SELECT code_pre_project, name_project, status_project FROM pre_project WHERE id='" . $__pidRet . "' LIMIT 1");
			if ($qNm && ($nm = mysqli_fetch_assoc($qNm))) {
				$codeRet = (string)($nm['code_pre_project'] ?? '');
				$nameRet = (string)($nm['name_project'] ?? '');
				$stRet = (string)($nm['status_project'] ?? '');
			}
			$all_projects_for_return[] = [
				'project_id' => $__pidRet,
				'code' => $codeRet,
				'name' => $nameRet,
				'status_project' => $stRet,
				'return_selected' => 0,
				'fix_status' => '',
				'fix_note' => '',
				'fix_at' => '',
				'return_cert_note' => '',
			];
		}
	}
	$pending_return_ids = [];
	foreach ($returned_items as $__rit) {
		$__pid = (int)($__rit['project_id'] ?? 0);
		$__fst = trim((string)($__rit['fix_status'] ?? ''));
		if ($__pid > 0 && ($__fst === '' || $__fst === 'pending')) {
			$pending_return_ids[$__pid] = $__pid;
		}
	}
	$all_returned_confirmed = function_exists('pm_include_all_returned_fixes_confirmed')
		&& pm_include_all_returned_fixes_confirmed($db_Project, $request_id);
	$admin_fix_sent_at = trim((string)($req['admin_fix_sent_at'] ?? ''));
	$is_unit_admin_detail = (function_exists('isUnitAdmin') && isUnitAdmin($db_Project, (string)$staff_code, (string)$code_unit, (int)$sid));
	$can_admin_send_fix = ($reqStatusForModal === 'rejected')
		&& $all_returned_confirmed
		&& $admin_fix_sent_at === ''
		&& !empty($returned_items)
		&& ($is_submitter_detail || $is_unit_admin_detail);
	// ยังแก้ได้เฉพาะโครงการที่ถูกเลือกตีกลับและยังไม่ยืนยันคำชี้แจง
	$hasEditableReturnedProjects = ($reqStatusForModal === 'rejected' && !empty($pending_return_ids));
	$show_edit_all_projects = ($reqStatusForModal === 'rejected' && ($is_submitter_detail || $is_unit_admin_detail) && $hasEditableReturnedProjects);
	$submitter_reply_can_edit = false; // ย้ายไปยืนยันรายโครงการ + Admin ส่งกลับ
	$budgetYearReq = trim((string)($req['budget_year'] ?? ''));
	$edit_all_projects_url = '';
	if ($show_edit_all_projects) {
		$ysEdit = $budgetYearReq !== '' ? $budgetYearReq : (string)$years_select;
		$edit_all_projects_url = '?page=project_manage&years_select=' . rawurlencode($ysEdit)
			. '&include_request_id=' . (int)$request_id . '&project_group=draft';
	}

	$staffName = function ($staff_id) use ($db_person) {
		$staff_id = (int)$staff_id;
		if ($staff_id <= 0) {
			return '-';
		}
		$q = @mysqli_query($db_person, "SELECT fname, lname FROM staff WHERE id='" . $staff_id . "' LIMIT 1");
		if ($q && ($sr = mysqli_fetch_assoc($q))) {
			$n = trim((string)(($sr['fname'] ?? '') . ' ' . ($sr['lname'] ?? '')));
			return $n !== '' ? $n : ('Staff #' . $staff_id);
		}
		return 'Staff #' . $staff_id;
	};

	// action logs (เรียงตามเวลา ไม่ซ่อน)
	$action_logs = [];
	$qLog = @mysqli_query(
		$db_Project,
		"SELECT id, action_type, actor_staff_id, sort_order, note, meta_json, created_at
		 FROM project_include_request_action_log
		 WHERE request_id='" . (int)$request_id . "'
		 ORDER BY created_at ASC, id ASC"
	);
	while ($qLog && ($lr = mysqli_fetch_assoc($qLog))) {
		$actorId = (int)($lr['actor_staff_id'] ?? 0);
		$lr['actor_name'] = $actorId > 0 ? $staffName($actorId) : '-';
		$action_logs[] = $lr;
	}

	// ใช้ action log เป็น "ประวัติ + สถานะล่าสุด" ของผู้รับรองแต่ละลำดับ (รองรับการตีกลับ/ผ่านซ้ำ)
	$lastDecisionFromLog = [];
	foreach ($action_logs as $__lg) {
		$__so = isset($__lg['sort_order']) ? (int)$__lg['sort_order'] : 0;
		if ($__so <= 0) continue;
		$__typ = (string)($__lg['action_type'] ?? '');
		$__at = (string)($__lg['created_at'] ?? '');
		$__note = (string)($__lg['note'] ?? '');
		if ($__typ === 'certifier_rejected') {
			$lastDecisionFromLog[$__so] = ['decision' => 'rejected', 'decided_at' => $__at, 'certifier_note' => $__note];
		} elseif ($__typ === 'certifier_approved' || $__typ === 'certifier_recheck_approved') {
			$lastDecisionFromLog[$__so] = ['decision' => 'approved', 'decided_at' => $__at, 'certifier_note' => $__note];
		}
	}
	if (!empty($lastDecisionFromLog)) {
		foreach ($lastDecisionFromLog as $__so => $__d) {
			if (!isset($decisionsBySort[$__so]) || !is_array($decisionsBySort[$__so])) {
				$decisionsBySort[$__so] = [];
			}
			$decisionsBySort[$__so]['decision'] = (string)($__d['decision'] ?? '');
			$decisionsBySort[$__so]['decided_at'] = (string)($__d['decided_at'] ?? '');
			$decisionsBySort[$__so]['certifier_note'] = (string)($__d['certifier_note'] ?? '');
		}
		// sync my_decision (ใช้ในการคำนวณสิทธิ์/คิว)
		if ($my_sort_order > 0) {
			$my_decision = (string)(($decisionsBySort[$my_sort_order]['decision'] ?? '') ?: '');
		}
	}

	foreach ($allSortOrders as $so) {
		$dec = (string)(($decisionsBySort[$so]['decision'] ?? '') ?: '');
		$decAt = (string)(($decisionsBySort[$so]['decided_at'] ?? '') ?: '');
		$decNote = (string)(($decisionsBySort[$so]['certifier_note'] ?? '') ?: '');
		$st = 'waiting_queue';
		if ($dec === 'approved') {
			$st = 'approved';
		} elseif ($dec === 'rejected') {
			$st = 'rejected';
		} else {
			$prevOk = true;
			foreach ($allSortOrders as $pso) {
				if ($pso >= $so) {
					break;
				}
				$pd = (string)(($decisionsBySort[$pso]['decision'] ?? '') ?: '');
				if ($pd !== 'approved') {
					$prevOk = false;
					break;
				}
			}
			$st = $prevOk ? 'waiting_turn' : 'waiting_queue';
			if ($st === 'waiting_turn' && $current_turn_sort <= 0) {
				$current_turn_sort = (int)$so;
				$sidTurn = (int)($certList[$so]['staff_id'] ?? 0);
				$current_turn_name = $staffName($sidTurn);
			}
		}
		$certifiers_payload[] = [
			'sort_order' => (int)$so,
			'staff_id' => (int)($certList[$so]['staff_id'] ?? 0),
			'staff_name' => $staffName((int)($certList[$so]['staff_id'] ?? 0)),
			'position_title' => (string)($certList[$so]['position_title'] ?? ''),
			'status' => $st,
			'decision' => $dec,
			'decided_at' => $decAt,
			'certifier_note' => $decNote,
			'can_edit_row' => ($pm_appr_is_plan_admin && $dec === '') ? 1 : 0,
		];
	}

	// ถึงคิวของผู้ใช้หรือไม่ (แยกจาก projects_pipeline_ok เพื่อให้ UI แสดงปุ่มได้ตามคิว
	// โดย backend จะเป็นด่านสุดท้ายในการอนุมัติ/ตีกลับ)
	if ($is_reviewer && $my_sort_order > 0 && $my_decision === '') {
		$prevOk = true;
		foreach ($allSortOrders as $prevSo) {
			if ($prevSo >= $my_sort_order) {
				break;
			}
			$prevDec = (string)(($decisionsBySort[$prevSo]['decision'] ?? '') ?: '');
			if ($prevDec !== 'approved') {
				$prevOk = false;
				break;
			}
		}
		$can_act_modal = $prevOk;
	}

	if ($reqStatusForModal === 'rejected') {
		// หลังถูกตีกลับ: Admin ส่งยืนยันการแก้ไขแล้ว → ผู้ที่ตีกลับพิจารณาซ้ำได้
		$adminFixSentModal = trim((string)($req['admin_fix_sent_at'] ?? $admin_fix_sent_at ?? ''));
		$srNoteNow = trim((string)($req['submitter_reply_note'] ?? ''));
		$rejectedSort = 0;
		foreach ($decisionsBySort as $__so => $__drow) {
			if ((string)($__drow['decision'] ?? '') === 'rejected') {
				$rejectedSort = (int)$__so;
				break;
			}
		}
		$canRecheckModal = ($adminFixSentModal !== '' || ($srNoteNow !== '' && empty($returned_items)));
		if ($canRecheckModal && $rejectedSort > 0 && $is_reviewer && in_array($rejectedSort, $myOrders, true)) {
			$my_sort_order = (int)$rejectedSort;
			$my_decision = 'rejected';
			$can_act_modal = true;
			$current_turn_sort = (int)$rejectedSort;
			$sidTurn = (int)($certList[$rejectedSort]['staff_id'] ?? 0);
			$current_turn_name = $staffName($sidTurn);
		} else {
			$can_act_modal = false;
		}
	}

	$all_certifiers_approved = false;
	if (!empty($allSortOrders)) {
		$all_certifiers_approved = true;
		foreach ($allSortOrders as $__soAp) {
			$__dAp = (string)(($decisionsBySort[$__soAp]['decision'] ?? '') ?: '');
			if ($__dAp !== 'approved') {
				$all_certifiers_approved = false;
				break;
			}
		}
	}
	$plan_unit_decision = trim((string)($req['plan_unit_decision'] ?? ''));
	$plan_unit_note = trim((string)($req['plan_unit_note'] ?? ''));
	$plan_unit_staff_id = (int)($req['plan_unit_staff_id'] ?? 0);
	$plan_unit_decided_at = trim((string)($req['plan_unit_decided_at'] ?? ''));
	$plan_unit_decider_name = $plan_unit_staff_id > 0 ? $staffName($plan_unit_staff_id) : '-';
	$plan_unit_accepted_by = (int)($req['plan_unit_accepted_by'] ?? 0);
	$plan_unit_accepted_at = trim((string)($req['plan_unit_accepted_at'] ?? ''));
	// ชื่อผู้รับเรื่อง: ใช้คนที่กดรับเท่านั้น (ไม่ใช้ชื่อผู้ล็อกอิน)
	if ($plan_unit_accepted_by <= 0 && $plan_unit_staff_id > 0) {
		$plan_unit_accepted_by = $plan_unit_staff_id;
	}
	$plan_unit_officer_name = $plan_unit_accepted_by > 0 ? $staffName($plan_unit_accepted_by) : '';
	// ข้อมูลเก่า: ผ่านครบลำดับแล้วแต่ยังรอหน่วยแผน ถูกดันเป็น 4 → คืนเป็นรอบรรจุ (3)
	if ($all_certifiers_approved && $plan_unit_decision === '' && $reqStatusForModal !== 'rejected' && !empty($project_ids_payload)) {
		$idsWaitPlan = implode(',', array_map('intval', $project_ids_payload));
		@mysqli_query(
			$db_Project,
			"UPDATE pre_project
			 SET status_project='3', dates=NOW()
			 WHERE status='1' AND status_project='4' AND id IN ($idsWaitPlan)"
		);
	}
	$can_act_plan_unit = $pm_appr_is_plan_admin
		&& $all_certifiers_approved
		&& $plan_unit_decision === ''
		&& $reqStatusForModal !== 'rejected';
	$can_accept_plan_unit = false;
	$plan_unit_is_accepted = ($plan_unit_accepted_by > 0);
	$can_decide_plan_unit = $can_act_plan_unit;
	$plan_unit_submitter_reply_note = trim((string)($req['plan_unit_submitter_reply_note'] ?? ''));
	$plan_unit_submitter_reply_updated_at = trim((string)($req['plan_unit_submitter_reply_updated_at'] ?? ''));
	$can_reply_plan_unit = (!$pm_appr_is_plan_admin)
		&& $is_submitter_detail
		&& $reqStatusForModal === 'rejected'
		&& $plan_unit_decision === 'returned';

	$staff_options_edit_row = [];
	if ($pm_appr_is_plan_admin && function_exists('getStaffSelect')) {
		foreach (getStaffSelect($db_person) as $__msx) {
			$__idm = (int)($__msx['id'] ?? 0);
			if ($__idm <= 0) {
				continue;
			}
			$__txm = trim((string)(($__msx['fname'] ?? '') . ' ' . ($__msx['lname'] ?? '')));
			if ($__txm === '') {
				$__txm = 'Staff #' . $__idm;
			}
			$staff_options_edit_row[] = [
				'id' => $__idm,
				'text' => $__txm,
				'position' => function_exists('pm_staff_position_label') ? pm_staff_position_label($db_person, $__msx) : '',
			];
		}
	}

	$secNm = trim((string)($req['section_name'] ?? ''));
	if ($secNm === '' || $secNm === '-') {
		$firstPidSec = (int)($project_ids_payload[0] ?? 0);
		if ($firstPidSec > 0 && function_exists('pm_project_unit_display_name')) {
			$qCuSec = @mysqli_query($db_Project, "SELECT code_unit FROM pre_project WHERE id='" . $firstPidSec . "' LIMIT 1");
			$rCuSec = $qCuSec ? mysqli_fetch_assoc($qCuSec) : null;
			$resolvedSec = pm_project_unit_display_name($db_Project, trim((string)($rCuSec['code_unit'] ?? '')), $db_person ?? null);
			if ($resolvedSec !== '') {
				$req['section_name'] = $resolvedSec;
			}
		}
	} elseif (function_exists('pm_project_unit_display_name')) {
		$maybeSec = pm_project_unit_display_name($db_Project, $secNm, $db_person ?? null);
		if ($maybeSec !== '' && $maybeSec !== $secNm) {
			$req['section_name'] = $maybeSec;
		}
	}
	if (function_exists('pm_include_request_subject_label')) {
		$req['subject'] = pm_include_request_subject_label($req['subject'] ?? '');
	}

	echo json_encode([
		'ok' => 1,
		'request' => $req,
		'plan_admin' => $pm_appr_is_plan_admin,
		'sender_name' => $sender_name,
		'summary' => $summary,
		'years_select' => $years_select,
		'project_group' => $project_group,
		'certifiers' => $certifiers_payload,
		'staff_options_edit_row' => $staff_options_edit_row,
		'project_ids' => $project_ids_payload,
		'show_edit_all_projects' => $show_edit_all_projects,
		'edit_all_projects_url' => $edit_all_projects_url,
		'submitter_reply' => [
			'can_edit' => $submitter_reply_can_edit,
			'note' => $submitter_reply_note,
			'updated_at' => $submitter_reply_updated_at,
		],
		'pipeline' => [
			'current_turn_sort' => $current_turn_sort,
			'current_turn_name' => $current_turn_name,
			'projects_pipeline_ok' => $projects_pipeline_ok,
			'can_act' => $can_act_modal,
			'my_sort_order' => $my_sort_order,
		],
		'flow_type' => 'include',
		'action_logs' => $action_logs,
		'projects_for_return' => $all_projects_for_return,
		'returned_items' => $returned_items,
		'can_admin_send_fix' => $can_admin_send_fix,
		'admin_fix_sent_at' => $admin_fix_sent_at,
		'all_returned_confirmed' => $all_returned_confirmed,
		'plan_unit_review' => [
			'show' => !empty($allSortOrders),
			'all_certifiers_approved' => $all_certifiers_approved,
			'can_act' => $can_decide_plan_unit,
			'can_accept' => $can_accept_plan_unit,
			'is_accepted' => $plan_unit_is_accepted,
			'can_reply' => $can_reply_plan_unit,
			'decision' => $plan_unit_decision,
			'note' => $plan_unit_note,
			'decided_at' => $plan_unit_decided_at,
			'decider_name' => $plan_unit_decider_name,
			'officer_name' => $plan_unit_officer_name,
			'accepted_at' => $plan_unit_accepted_at,
			'submitter_reply' => [
				'note' => $plan_unit_submitter_reply_note,
				'updated_at' => $plan_unit_submitter_reply_updated_at,
			],
		],
	], JSON_UNESCAPED_UNICODE);
	exit;
}

// -----------------------------------------------------------------------------
// AJAX: get operate project detail (รายโครงการ)
// -----------------------------------------------------------------------------
if ($pm_appr_route_op === 'get_operate_project_detail') {
	if (ob_get_level() > 0) {
		@ob_clean();
	}
	header('Content-Type: application/json; charset=utf-8');
	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	if ($project_id <= 0) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบโครงการ'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	try {
	// ensure columns for submitter reply in operate flow (DB เก่าบางชุดยังไม่มี)
	$chkOpReply = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'operate_submitter_reply_note'");
	if (!$chkOpReply || mysqli_num_rows($chkOpReply) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN operate_submitter_reply_note MEDIUMTEXT NULL");
	}
	$chkOpReplyAt = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'operate_submitter_reply_updated_at'");
	if (!$chkOpReplyAt || mysqli_num_rows($chkOpReplyAt) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN operate_submitter_reply_updated_at DATETIME NULL");
	}
	$chkOpReplySid = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'operate_submitter_reply_staff_id'");
	if (!$chkOpReplySid || mysqli_num_rows($chkOpReplySid) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN operate_submitter_reply_staff_id INT UNSIGNED NULL");
	}
	// ถ้ามี MYSQLI_REPORT_EXCEPTION อยู่ในสภาพแวดล้อม เจอคอลัมน์หายจาก SELECT → throw → HTTP 500
	$chkOpCertJson = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'operate_certifiers_json'");
	if (!$chkOpCertJson || mysqli_num_rows($chkOpCertJson) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN operate_certifiers_json MEDIUMTEXT NULL");
	}
	$chkNrPre = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'notify_result'");
	if (!$chkNrPre || mysqli_num_rows($chkNrPre) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN notify_result VARCHAR(100) NULL");
	}
	$chkPaRcPre = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'plan_admin_operate_rechain_submitted_at'");
	if (!$chkPaRcPre || mysqli_num_rows($chkPaRcPre) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN plan_admin_operate_rechain_submitted_at DATETIME NULL DEFAULT NULL");
	}
	if (function_exists('pm_ensure_operate_approver_schema')) {
		pm_ensure_operate_approver_schema($db_Project);
	}
	if (function_exists('pm_ensure_midyear_schema')) {
		pm_ensure_midyear_schema($db_Project);
	}
	$qProj = @mysqli_query($db_Project, "SELECT id, year_project, code_pre_project, name_project, code_unit, status_project, staff_insert, operate_certifiers_json, operate_approvers_json, dates, notify_result, plan_admin_operate_rechain_submitted_at, operate_submitter_reply_note, operate_submitter_reply_updated_at, operate_submitter_reply_staff_id, midyear_request, midyear_reason FROM pre_project WHERE id='" . $project_id . "' LIMIT 1");
	$proj = $qProj ? mysqli_fetch_assoc($qProj) : null;
	if (!$proj) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบข้อมูลโครงการ'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$projStatusNow = (string)($proj['status_project'] ?? '');
	$isOperateStage = ($projStatusNow === '1');
	$certRaw = trim((string)($proj['operate_certifiers_json'] ?? ''));
	$certArr = $certRaw !== '' ? json_decode($certRaw, true) : [];
	if (!is_array($certArr)) $certArr = [];
	$staffName = function($staff_id) use ($db_person) {
		$staff_id = (int)$staff_id;
		if ($staff_id <= 0) return '-';
		$q = @mysqli_query($db_person, "SELECT fname,lname FROM staff WHERE id='" . $staff_id . "' LIMIT 1");
		$r = $q ? mysqli_fetch_assoc($q) : null;
		$nm = trim((string)(($r['fname'] ?? '') . ' ' . ($r['lname'] ?? '')));
		return $nm !== '' ? $nm : ('Staff #' . $staff_id);
	};
	$unit_name = '-';
	$uc = trim((string)($proj['code_unit'] ?? ''));
	if (function_exists('pm_project_unit_display_name')) {
		$__un = pm_project_unit_display_name($db_Project, $uc, $db_person ?? null);
		if ($__un !== '') {
			$unit_name = $__un;
		}
	} elseif ($uc !== '') {
		$qU = @mysqli_query($db_person, "SELECT name FROM unit WHERE code='" . mysqli_real_escape_string($db_person, $uc) . "' LIMIT 1");
		$rU = $qU ? mysqli_fetch_assoc($qU) : null;
		if ($rU && trim((string)($rU['name'] ?? '')) !== '') {
			$unit_name = trim((string)$rU['name']);
		}
	}
	$qBg = @mysqli_query($db_Project, "SELECT IFNULL(SUM(pa.total),0) AS t FROM project_activity_budget pa WHERE pa.code_pre_project='" . $project_id . "' AND pa.status!='99'");
	$rBg = $qBg ? mysqli_fetch_assoc($qBg) : null;
	$budget_baht = (float)($rBg['t'] ?? 0);
	$budget_source = function_exists('resolveProjectBudgetSourceForDisplay')
		? resolveProjectBudgetSourceForDisplay($db_Project, $project_id)
		: '-';
	$operate_submitter_reply_note = trim((string)($proj['operate_submitter_reply_note'] ?? ''));
	$operate_submitter_reply_updated_at = trim((string)($proj['operate_submitter_reply_updated_at'] ?? ''));

	$decBySort = [];
	$qDec = @mysqli_query($db_Project, "SELECT sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at FROM project_operate_certifier_decision WHERE project_id='" . $project_id . "'");
	while ($qDec && ($rd = mysqli_fetch_assoc($qDec))) {
		$so = (int)($rd['sort_order'] ?? 0);
		if ($so > 0) {
			$decBySort[$so] = $rd;
		}
	}

	// ลำดับผู้ตรวจ: ใช้ operate_certifiers_json ก่อน ถ้าว่างให้ดึงจากผู้รับรองของคำขอบรรจุที่ผูกกับโครงการ (ตรงกับแท็บแผน)
	$certList = [];
	$seqNo = 0;
	foreach ($certArr as $idx => $c) {
		if (!is_array($c)) {
			continue;
		}
		$seqNo++;
		$so = (int)($c['sort_order'] ?? 0);
		if ($so <= 0) {
			$so = (int)$seqNo;
		}
		$sidC = (int)($c['staff_id'] ?? 0);
		if ($sidC <= 0) {
			continue;
		}
		$certList[$so] = [
			'staff_id' => $sidC,
			'position_title' => trim((string)($c['position'] ?? $c['position_title'] ?? '')),
		];
	}
	$request_id_operate = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
	if (count($certList) === 0) {
		if ($request_id_operate <= 0) {
			$qRid = @mysqli_query(
				$db_Project,
				"SELECT MAX(i.request_id) AS rid FROM project_include_request_item i
				 INNER JOIN project_include_request_certifier c ON c.request_id = i.request_id AND c.staff_id='" . (int)$sid . "'
				 WHERE i.project_id='" . $project_id . "'"
			);
			$rRid = $qRid ? mysqli_fetch_assoc($qRid) : null;
			$request_id_operate = (int)($rRid['rid'] ?? 0);
		}
		if ($request_id_operate > 0) {
			$qIncCert = @mysqli_query(
				$db_Project,
				"SELECT sort_order, staff_id, position_title FROM project_include_request_certifier
				 WHERE request_id='" . (int)$request_id_operate . "' ORDER BY sort_order ASC"
			);
			while ($qIncCert && ($cr = mysqli_fetch_assoc($qIncCert))) {
				$so = (int)($cr['sort_order'] ?? 0);
				if ($so <= 0) {
					continue;
				}
				$sidC = (int)($cr['staff_id'] ?? 0);
				if ($sidC <= 0) {
					continue;
				}
				$certList[$so] = [
					'staff_id' => $sidC,
					'position_title' => trim((string)($cr['position_title'] ?? '')),
				];
			}
		}
	}

	$allSortOrders = array_keys($certList);
	sort($allSortOrders, SORT_NUMERIC);

	$myOrders = [];
	foreach ($allSortOrders as $so) {
		if ((int)($certList[$so]['staff_id'] ?? 0) === (int)$sid) {
			$myOrders[] = (int)$so;
		}
	}
	sort($myOrders, SORT_NUMERIC);
	$my_sort_order = 0;
	foreach ($myOrders as $candidateSo) {
		$cDec = (string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '');
		if ($cDec === '') {
			$my_sort_order = (int)$candidateSo;
			break;
		}
	}
	// โหมดพิจารณาซ้ำหลังตีกลับ: ถ้าผู้ยื่นส่งคำชี้แจงแล้ว ให้ผูกคิวกับลำดับที่เคย reject
	if ($projStatusNow === '8' && $operate_submitter_reply_note !== '' && count($myOrders) > 0) {
		foreach ($myOrders as $candidateSo) {
			$cDec = (string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '');
			if ($cDec === 'rejected') {
				$my_sort_order = (int)$candidateSo;
				break;
			}
		}
	}
	if ($my_sort_order <= 0 && count($myOrders) > 0) {
		$my_sort_order = (int)$myOrders[0];
	}

	$current_turn_sort = 0;
	$current_turn_name = '-';
	$certifiers_payload = [];
	foreach ($allSortOrders as $so) {
		$dec = (string)(($decBySort[$so]['decision'] ?? '') ?: '');
		$decAt = (string)(($decBySort[$so]['decided_at'] ?? '') ?: '');
		$decNote = (string)(($decBySort[$so]['certifier_note'] ?? '') ?: '');
		$st = 'waiting_queue';
		if ($dec === 'approved') {
			$st = 'approved';
		} elseif ($dec === 'rejected') {
			$st = 'rejected';
		} else {
			$prevOk = true;
			foreach ($allSortOrders as $pso) {
				if ($pso >= $so) {
					break;
				}
				$pd = (string)(($decBySort[$pso]['decision'] ?? '') ?: '');
				if ($pd !== 'approved') {
					$prevOk = false;
					break;
				}
			}
			$st = $prevOk ? 'waiting_turn' : 'waiting_queue';
			if ($st === 'waiting_turn' && $current_turn_sort <= 0) {
				$current_turn_sort = (int)$so;
				$sidTurn = (int)($certList[$so]['staff_id'] ?? 0);
				$current_turn_name = $staffName($sidTurn);
			}
		}
		$sidC = (int)($certList[$so]['staff_id'] ?? 0);
		$certifiers_payload[] = [
			'sort_order' => (int)$so,
			'staff_id' => $sidC,
			'staff_name' => $staffName($sidC),
			'position_title' => (string)($certList[$so]['position_title'] ?? ''),
			'status' => $st,
			'decision' => $dec,
			'decided_at' => $decAt,
			'certifier_note' => $decNote,
			'note' => $decNote,
			'is_current_turn' => ((int)$so === (int)$current_turn_sort) ? 1 : 0,
			'can_edit_row' => ($pm_appr_is_plan_admin && $dec === ''),
		];
	}

	$my_decision = ($my_sort_order > 0) ? (string)(($decBySort[$my_sort_order]['decision'] ?? '') ?: '') : '';
	$can_act_modal = false;
	// อนุญาตทั้งสถานะปกติ (1) และสถานะพิจารณาซ้ำหลังตีกลับ (8)
	if (($projStatusNow === '1' || $projStatusNow === '8') && $my_sort_order > 0 && $my_decision === '') {
		$prevOk = true;
		foreach ($allSortOrders as $prevSo) {
			if ($prevSo >= $my_sort_order) {
				break;
			}
			$prevDec = (string)(($decBySort[$prevSo]['decision'] ?? '') ?: '');
			if ($prevDec !== 'approved') {
				$prevOk = false;
				break;
			}
		}
		$can_act_modal = $prevOk;
	}
	// โหมดพิจารณาซ้ำหลังตีกลับ (status=8 + ผู้ยื่นส่งคำชี้แจงแล้ว):
	// กรณีคิวของคนเดิมยังเป็น rejected ให้เปิดกดได้ทันที
	if (!$can_act_modal && $projStatusNow === '8' && $my_sort_order > 0) {
		$hasReply = ($operate_submitter_reply_note !== '');
		$isRejectedByMe = ($my_decision === 'rejected');
		if ($hasReply && $isRejectedByMe) {
			$can_act_modal = true;
		}
	}
	// action log สำหรับแสดงประวัติทุกขั้นใน modal (operate flow)
	$operate_action_logs = [];
	$qOlog = @mysqli_query(
		$db_Project,
		"SELECT id, action_type, actor_staff_id, sort_order, note, created_at
		 FROM project_operate_action_log
		 WHERE project_id='" . (int)$project_id . "'
		 ORDER BY id ASC"
	);
	while ($qOlog && ($ol = mysqli_fetch_assoc($qOlog))) {
		$operate_action_logs[] = [
			'action_type' => (string)($ol['action_type'] ?? ''),
			'sort_order' => isset($ol['sort_order']) ? (int)$ol['sort_order'] : null,
			'actor_name' => $staffName((int)($ol['actor_staff_id'] ?? 0)),
			'note' => (string)($ol['note'] ?? ''),
			'created_at' => (string)($ol['created_at'] ?? ''),
		];
	}
	if (empty($operate_action_logs)) {
		// fallback: ระบบเก่าก่อนมีตาราง log ให้เห็นสถานะล่าสุดจาก decision อย่างน้อย
		foreach ($allSortOrders as $soLog) {
			$decLog = (string)(($decBySort[$soLog]['decision'] ?? '') ?: '');
			if ($decLog !== 'approved' && $decLog !== 'rejected') {
				continue;
			}
			$atype = ($decLog === 'approved') ? 'certifier_approved' : 'certifier_rejected';
			$decAt = (string)(($decBySort[$soLog]['decided_at'] ?? '') ?: '');
			$noteLog = (string)(($decBySort[$soLog]['certifier_note'] ?? '') ?: '');
			$actorSid = (int)($decBySort[$soLog]['decided_by_staff_id'] ?? 0);
			if ($actorSid <= 0) {
				$actorSid = (int)($decBySort[$soLog]['staff_id'] ?? 0);
			}
			$operate_action_logs[] = [
				'action_type' => $atype,
				'sort_order' => (int)$soLog,
				'actor_name' => $staffName($actorSid),
				'note' => $noteLog,
				'created_at' => $decAt,
			];
		}
	}
	$chkPaRc = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'plan_admin_operate_rechain_submitted_at'");
	if (!$chkPaRc || mysqli_num_rows($chkPaRc) === 0) {
		@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN plan_admin_operate_rechain_submitted_at DATETIME NULL DEFAULT NULL");
	}
	// หลังครบผล operate ทุกลำดับ: Plan_Admin สามารถกำหนดลำดับผู้รับรองใหม่ (โมดัล / ให้ตรงกับหน้า project_manage)
	$notify_res_operate_txt = trim((string)($proj['notify_result'] ?? ''));
	$plan_rc_at_txt_ap = isset($proj['plan_admin_operate_rechain_submitted_at']) ? trim((string)$proj['plan_admin_operate_rechain_submitted_at']) : '';
	$all_operate_chain_done_appr = count($allSortOrders) > 0;
	foreach ($allSortOrders as $__so_wave) {
		if ((string)(($decBySort[$__so_wave]['decision'] ?? '') ?: '') !== 'approved') {
			$all_operate_chain_done_appr = false;
			break;
		}
	}
	$approverJsonOperate = trim((string)($proj['operate_approvers_json'] ?? ''));
	$useApproverChainDetail = $all_operate_chain_done_appr && $approverJsonOperate !== '';
	if ($useApproverChainDetail && function_exists('pm_parse_operate_chain_json')) {
		$apprList = pm_parse_operate_chain_json($approverJsonOperate);
		if (!empty($apprList)) {
			$certList = $apprList;
			$allSortOrders = array_keys($certList);
			sort($allSortOrders, SORT_NUMERIC);
			$decBySort = function_exists('pm_load_operate_decision_map')
				? pm_load_operate_decision_map($db_Project, (int)$project_id, 'project_operate_approver_decision')
				: [];
			$myOrders = [];
			foreach ($allSortOrders as $so) {
				if ((int)($certList[$so]['staff_id'] ?? 0) === (int)$sid) {
					$myOrders[] = (int)$so;
				}
			}
			sort($myOrders, SORT_NUMERIC);
			$my_sort_order = 0;
			foreach ($myOrders as $candidateSo) {
				$cDec = (string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '');
				if ($cDec === '') {
					$my_sort_order = (int)$candidateSo;
					break;
				}
			}
			if ($projStatusNow === '8' && $operate_submitter_reply_note !== '' && count($myOrders) > 0) {
				foreach ($myOrders as $candidateSo) {
					$cDec = (string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '');
					if ($cDec === 'rejected') {
						$my_sort_order = (int)$candidateSo;
						break;
					}
				}
			}
			if ($my_sort_order <= 0 && count($myOrders) > 0) {
				$my_sort_order = (int)$myOrders[0];
			}
			$current_turn_sort = 0;
			$current_turn_name = '-';
			$certifiers_payload = [];
			foreach ($allSortOrders as $so) {
				$dec = (string)(($decBySort[$so]['decision'] ?? '') ?: '');
				$decAt = (string)(($decBySort[$so]['decided_at'] ?? '') ?: '');
				$decNote = (string)(($decBySort[$so]['certifier_note'] ?? '') ?: '');
				$st = 'waiting_queue';
				if ($dec === 'approved') {
					$st = 'approved';
				} elseif ($dec === 'rejected') {
					$st = 'rejected';
				} else {
					$prevOk = true;
					foreach ($allSortOrders as $pso) {
						if ($pso >= $so) {
							break;
						}
						$pd = (string)(($decBySort[$pso]['decision'] ?? '') ?: '');
						if ($pd !== 'approved') {
							$prevOk = false;
							break;
						}
					}
					$st = $prevOk ? 'waiting_turn' : 'waiting_queue';
					if ($st === 'waiting_turn' && $current_turn_sort <= 0) {
						$current_turn_sort = (int)$so;
						$sidTurn = (int)($certList[$so]['staff_id'] ?? 0);
						$current_turn_name = $staffName($sidTurn);
					}
				}
				$sidC = (int)($certList[$so]['staff_id'] ?? 0);
				$certifiers_payload[] = [
					'sort_order' => (int)$so,
					'staff_id' => $sidC,
					'staff_name' => $staffName($sidC),
					'position_title' => (string)($certList[$so]['position_title'] ?? ''),
					'status' => $st,
					'decision' => $dec,
					'decided_at' => $decAt,
					'certifier_note' => $decNote,
					'note' => $decNote,
					'is_current_turn' => ((int)$so === (int)$current_turn_sort) ? 1 : 0,
					'can_edit_row' => ($pm_appr_is_plan_admin && $dec === ''),
				];
			}
			$my_decision = ($my_sort_order > 0) ? (string)(($decBySort[$my_sort_order]['decision'] ?? '') ?: '') : '';
			$can_act_modal = false;
			if (($projStatusNow === '1' || $projStatusNow === '8') && $my_sort_order > 0 && $my_decision === '') {
				$prevOk = true;
				foreach ($allSortOrders as $prevSo) {
					if ($prevSo >= $my_sort_order) {
						break;
					}
					$prevDec = (string)(($decBySort[$prevSo]['decision'] ?? '') ?: '');
					if ($prevDec !== 'approved') {
						$prevOk = false;
						break;
					}
				}
				$can_act_modal = $prevOk;
			}
			if (!$can_act_modal && $projStatusNow === '8' && $my_sort_order > 0) {
				if ($operate_submitter_reply_note !== '' && $my_decision === 'rejected') {
					$can_act_modal = true;
				}
			}
		}
	}
	$notify_ok_pa_rechain = function_exists('pm_notify_allows_plan_admin_operate_rechain')
		&& pm_notify_allows_plan_admin_operate_rechain($notify_res_operate_txt, $projStatusNow);
	$op_rechain_stage_ok = ($projStatusNow === '1');
	$show_plan_admin_modal_rechain = $pm_appr_is_plan_admin
		&& $op_rechain_stage_ok
		&& $notify_ok_pa_rechain
		&& $all_operate_chain_done_appr
		&& $plan_rc_at_txt_ap === ''
		&& $approverJsonOperate === '';
	$modal_rechain_staff_opts = [];
	$staff_options_edit_row = [];
	if ($show_plan_admin_modal_rechain && function_exists('getStaffSelect')) {
		foreach (getStaffSelect($db_person) as $__msx) {
			$__idm = (int)($__msx['id'] ?? 0);
			if ($__idm <= 0) {
				continue;
			}
			$__txm = trim((string)(($__msx['fname'] ?? '') . ' ' . ($__msx['lname'] ?? '')));
			if ($__txm === '') {
				$__txm = 'Staff #' . $__idm;
			}
			$modal_rechain_staff_opts[] = [
				'id' => $__idm,
				'text' => $__txm,
				'position' => function_exists('pm_staff_position_label') ? pm_staff_position_label($db_person, $__msx) : '',
			];
		}
	}
	if ($pm_appr_is_plan_admin && function_exists('getStaffSelect')) {
		foreach (getStaffSelect($db_person) as $__msx) {
			$__idm = (int)($__msx['id'] ?? 0);
			if ($__idm <= 0) {
				continue;
			}
			$__txm = trim((string)(($__msx['fname'] ?? '') . ' ' . ($__msx['lname'] ?? '')));
			if ($__txm === '') {
				$__txm = 'Staff #' . $__idm;
			}
			$staff_options_edit_row[] = [
				'id' => $__idm,
				'text' => $__txm,
				'position' => function_exists('pm_staff_position_label') ? pm_staff_position_label($db_person, $__msx) : '',
			];
		}
	}

	if ($operate_submitter_reply_note !== '') {
		$hasReplyLog = false;
		$hasRejectLog = false;
		$replySortForFallback = 0;
		$replyCreatedAtForFallback = '';
		foreach ($operate_action_logs as $__olx) {
			$__typ = (string)($__olx['action_type'] ?? '');
			if ($__typ === 'submitter_reply_saved') {
				$hasReplyLog = true;
				if ($replySortForFallback <= 0) {
					$replySortForFallback = (int)($__olx['sort_order'] ?? 0);
				}
				if ($replyCreatedAtForFallback === '') {
					$replyCreatedAtForFallback = (string)($__olx['created_at'] ?? '');
				}
			} elseif ($__typ === 'certifier_rejected') {
				$hasRejectLog = true;
			}
		}
		if (!$hasReplyLog) {
			$lastRejectedSo = 0;
			foreach ($allSortOrders as $soLog2) {
				$decLog2 = (string)(($decBySort[$soLog2]['decision'] ?? '') ?: '');
				if ($decLog2 === 'rejected') {
					$lastRejectedSo = (int)$soLog2;
				}
			}
			$operate_action_logs[] = [
				'action_type' => 'submitter_reply_saved',
				'sort_order' => $lastRejectedSo > 0 ? $lastRejectedSo : null,
				'actor_name' => $staffName((int)($proj['operate_submitter_reply_staff_id'] ?? 0)),
				'note' => $operate_submitter_reply_note,
				'created_at' => $operate_submitter_reply_updated_at,
			];
		}
		// ข้อมูลเก่าบางช่วงอาจมี "คำชี้แจง" แต่ไม่มี log การตีกลับเพราะเคยถูกอัปเดตทับ:
		// เติมสถานะตีกลับแบบ fallback เพื่อให้ timeline เรียงครบทุกขั้นก่อน "ผ่าน"
		if (!$hasRejectLog) {
			if ($replySortForFallback <= 0) {
				$replySortForFallback = 1;
			}
			array_unshift($operate_action_logs, [
				'action_type' => 'certifier_rejected',
				'sort_order' => $replySortForFallback,
				'actor_name' => '-',
				'note' => '',
				'created_at' => $replyCreatedAtForFallback !== '' ? $replyCreatedAtForFallback : $operate_submitter_reply_updated_at,
			]);
		}
	}
	$summary = [[
		'row_kind' => 'data',
		'consistency_label' => 'โครงการ',
		'project_count' => 1,
		'budget_baht' => $budget_baht,
		'include_project_items' => [[
			'project_id' => (int)$project_id,
			'name' => (string)($proj['name_project'] ?? ''),
			'kpi_names' => []
		]]
	]];
	$summary[] = [
		'row_kind' => 'total',
		'consistency_label' => 'รวม',
		'project_count' => 1,
		'budget_baht' => $budget_baht,
		'include_project_items' => []
	];
	$request_status_operate = 'pending';
	if ($projStatusNow === '8') {
		$request_status_operate = 'rejected';
	} elseif ($projStatusNow === '9') {
		$request_status_operate = 'rejected_final';
	}
	echo json_encode([
		'ok' => 1,
		'flow_type' => 'operate',
		'plan_admin_operate_rechain' => [
			'show' => $show_plan_admin_modal_rechain,
			'project_id' => (int)$project_id,
			'staff_options' => $modal_rechain_staff_opts,
			'can_decide' => $show_plan_admin_modal_rechain ? 1 : 0,
		],
		'request' => [
			'id' => (int)$project_id,
			'status' => $request_status_operate,
			'budget_year' => (string)($proj['year_project'] ?? $years_select),
			'created_at' => (string)($proj['dates'] ?? ''),
			'subject' => 'ขอดำเนินการโครงการ ' . (string)($proj['name_project'] ?? ''),
			'section_name' => $unit_name,
			'letter_body' => 'รหัสโครงการ ' . (string)($proj['code_pre_project'] ?? '-') . ' / ชื่อโครงการ ' . (string)($proj['name_project'] ?? '-'),
		],
		'plan_admin' => $pm_appr_is_plan_admin,
		'staff_options_edit_row' => $staff_options_edit_row,
		'sender_name' => $staffName((int)($proj['staff_insert'] ?? 0)),
		'summary' => $summary,
		'operate_detail' => [
			'project_code' => (string)($proj['code_pre_project'] ?? ''),
			'project_name' => (string)($proj['name_project'] ?? ''),
			'budget_baht' => $budget_baht,
			'budget_source' => $budget_source,
			'is_midyear' => ((int)($proj['midyear_request'] ?? 0) === 1) ? 1 : 0,
			'midyear_reason' => trim((string)($proj['midyear_reason'] ?? '')),
		],
		'years_select' => $years_select,
		'project_group' => $project_group,
		'include_request_id' => (int)$request_id_operate,
		'certifiers' => $certifiers_payload,
		'action_logs' => $operate_action_logs,
		'submitter_reply' => [
			'can_edit' => false,
			'note' => $operate_submitter_reply_note,
			'updated_at' => $operate_submitter_reply_updated_at,
		],
		'pipeline' => [
			'current_turn_sort' => $current_turn_sort,
			'current_turn_name' => $current_turn_name,
			'projects_pipeline_ok' => true,
			'can_act' => $can_act_modal,
			'my_sort_order' => $my_sort_order,
		],
		'plan_unit_review' => ['show' => false],
	], JSON_UNESCAPED_UNICODE);
	exit;
	} catch (Throwable $e_op_detail) {
		if (ob_get_level() > 0) {
			@ob_clean();
		}
		header('Content-Type: application/json; charset=utf-8');
		$detail = getenv('APP_DEBUG') === '1' ? (' ' . $e_op_detail->getFile() . ':' . $e_op_detail->getLine()) : '';
		echo json_encode([
			'ok' => 0,
			'msg' => 'โหลดรายละเอียดโครงการไม่สำเร็จ: ' . $e_op_detail->getMessage() . $detail,
		], JSON_UNESCAPED_UNICODE);
		exit;
	}
}

// -----------------------------------------------------------------------------
// AJAX: get report project detail (รายงานผลโครงการ)
// -----------------------------------------------------------------------------
if ($pm_appr_route_op === 'get_report_project_detail') {
	if (ob_get_level() > 0) {
		@ob_clean();
	}
	header('Content-Type: application/json; charset=utf-8');
	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	if ($project_id <= 0) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบโครงการ'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	if (function_exists('ensureProjectResultReportSchema')) {
		ensureProjectResultReportSchema($db_Project);
	}
	$qProj = @mysqli_query($db_Project, "SELECT id, year_project, code_pre_project, name_project, code_unit, status_project, staff_insert, result_report_certifiers_json, result_report_submitted_at, result_report_completed_at, result_report_plan_closed_at, result_report_plan_closed_by, result_report_plan_note, result_report_submitter_reply_note, result_report_submitter_reply_updated_at, result_report_submitter_reply_staff_id, dates FROM pre_project WHERE id='" . $project_id . "' AND status='1' LIMIT 1");
	$proj = $qProj ? mysqli_fetch_assoc($qProj) : null;
	if (!$proj) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบข้อมูลโครงการ'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$submittedAt = trim((string)($proj['result_report_submitted_at'] ?? ''));
	if ($submittedAt === '') {
		echo json_encode(['ok' => 0, 'msg' => 'โครงการนี้ยังไม่ได้ส่งรายงานผล'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$staffName = function($staff_id) use ($db_person) {
		$staff_id = (int)$staff_id;
		if ($staff_id <= 0) return '-';
		$q = @mysqli_query($db_person, "SELECT fname,lname FROM staff WHERE id='" . $staff_id . "' LIMIT 1");
		$r = $q ? mysqli_fetch_assoc($q) : null;
		$nm = trim((string)(($r['fname'] ?? '') . ' ' . ($r['lname'] ?? '')));
		return $nm !== '' ? $nm : ('Staff #' . $staff_id);
	};
	$unit_name = '-';
	$uc = trim((string)($proj['code_unit'] ?? ''));
	if (function_exists('pm_project_unit_display_name')) {
		$__un = pm_project_unit_display_name($db_Project, $uc, $db_person ?? null);
		if ($__un !== '') {
			$unit_name = $__un;
		}
	} elseif ($uc !== '') {
		$qU = @mysqli_query($db_person, "SELECT name FROM unit WHERE code='" . mysqli_real_escape_string($db_person, $uc) . "' LIMIT 1");
		$rU = $qU ? mysqli_fetch_assoc($qU) : null;
		if ($rU && trim((string)($rU['name'] ?? '')) !== '') {
			$unit_name = trim((string)$rU['name']);
		}
	}
	$qBg = @mysqli_query($db_Project, "SELECT IFNULL(SUM(pa.total),0) AS t FROM project_activity_budget pa WHERE pa.code_pre_project='" . $project_id . "' AND pa.status!='99'");
	$rBg = $qBg ? mysqli_fetch_assoc($qBg) : null;
	$budget_baht = (float)($rBg['t'] ?? 0);
	$budget_source = function_exists('resolveProjectBudgetSourceForDisplay')
		? resolveProjectBudgetSourceForDisplay($db_Project, $project_id)
		: '-';
	$certRaw = trim((string)($proj['result_report_certifiers_json'] ?? ''));
	$certArr = $certRaw !== '' ? json_decode($certRaw, true) : [];
	if (!is_array($certArr)) {
		$certArr = [];
	}
	$decBySort = [];
	$qDec = @mysqli_query($db_Project, "SELECT sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at FROM project_result_report_certifier_decision WHERE project_id='" . $project_id . "'");
	while ($qDec && ($rd = mysqli_fetch_assoc($qDec))) {
		$so = (int)($rd['sort_order'] ?? 0);
		if ($so > 0) {
			$decBySort[$so] = $rd;
		}
	}
	$certList = [];
	$seqNo = 0;
	foreach ($certArr as $c) {
		if (!is_array($c)) {
			continue;
		}
		$seqNo++;
		$so = (int)($c['sort_order'] ?? 0);
		if ($so <= 0) {
			$so = $seqNo;
		}
		$sidC = (int)($c['staff_id'] ?? 0);
		if ($sidC <= 0) {
			continue;
		}
		$certList[$so] = [
			'staff_id' => $sidC,
			'position_title' => trim((string)($c['position'] ?? $c['position_title'] ?? '')),
		];
	}
	if (count($certList) === 0) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบลำดับผู้ตรวจสอบรายงานผล'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$allSortOrders = array_keys($certList);
	sort($allSortOrders, SORT_NUMERIC);
	$myOrders = [];
	foreach ($allSortOrders as $so) {
		if ((int)($certList[$so]['staff_id'] ?? 0) === (int)$sid) {
			$myOrders[] = (int)$so;
		}
	}
	sort($myOrders, SORT_NUMERIC);
	$my_sort_order = 0;
	foreach ($myOrders as $candidateSo) {
		if ((string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '') === '') {
			$my_sort_order = (int)$candidateSo;
			break;
		}
	}
	if ($my_sort_order <= 0 && count($myOrders) > 0) {
		$my_sort_order = (int)$myOrders[0];
	}
	$current_turn_sort = 0;
	$current_turn_name = '-';
	$certifiers_payload = [];
	foreach ($allSortOrders as $so) {
		$dec = (string)(($decBySort[$so]['decision'] ?? '') ?: '');
		$decAt = (string)(($decBySort[$so]['decided_at'] ?? '') ?: '');
		$decNote = (string)(($decBySort[$so]['certifier_note'] ?? '') ?: '');
		$st = 'waiting_queue';
		if ($dec === 'approved') {
			$st = 'approved';
		} elseif ($dec === 'rejected') {
			$st = 'rejected';
		} else {
			$prevOk = true;
			foreach ($allSortOrders as $pso) {
				if ($pso >= $so) {
					break;
				}
				if ((string)(($decBySort[$pso]['decision'] ?? '') ?: '') !== 'approved') {
					$prevOk = false;
					break;
				}
			}
			$st = $prevOk ? 'waiting_turn' : 'waiting_queue';
			if ($st === 'waiting_turn' && $current_turn_sort <= 0) {
				$current_turn_sort = (int)$so;
				$current_turn_name = $staffName((int)($certList[$so]['staff_id'] ?? 0));
			}
		}
		$sidC = (int)($certList[$so]['staff_id'] ?? 0);
		$certifiers_payload[] = [
			'sort_order' => (int)$so,
			'staff_id' => $sidC,
			'staff_name' => $staffName($sidC),
			'position_title' => (string)($certList[$so]['position_title'] ?? ''),
			'status' => $st,
			'decision' => $dec,
			'decided_at' => $decAt,
			'certifier_note' => $decNote,
			'note' => $decNote,
			'is_current_turn' => ((int)$so === (int)$current_turn_sort) ? 1 : 0,
			'can_edit_row' => false,
		];
	}
	$my_decision = ($my_sort_order > 0) ? (string)(($decBySort[$my_sort_order]['decision'] ?? '') ?: '') : '';
	$can_act_modal = false;
	$completedAt = trim((string)($proj['result_report_completed_at'] ?? ''));
	if ($completedAt === '' && $my_sort_order > 0 && $my_decision === '') {
		$prevOk = true;
		foreach ($allSortOrders as $prevSo) {
			if ($prevSo >= $my_sort_order) {
				break;
			}
			if ((string)(($decBySort[$prevSo]['decision'] ?? '') ?: '') !== 'approved') {
				$prevOk = false;
				break;
			}
		}
		$can_act_modal = $prevOk;
	}
	$report_action_logs = [];
	foreach ($allSortOrders as $soLog) {
		$decLog = (string)(($decBySort[$soLog]['decision'] ?? '') ?: '');
		if ($decLog !== 'approved' && $decLog !== 'rejected') {
			continue;
		}
		$atype = ($decLog === 'approved') ? 'certifier_approved' : 'certifier_rejected';
		$report_action_logs[] = [
			'action_type' => $atype,
			'sort_order' => (int)$soLog,
			'actor_name' => $staffName((int)($decBySort[$soLog]['decided_by_staff_id'] ?? ($decBySort[$soLog]['staff_id'] ?? 0))),
			'note' => (string)(($decBySort[$soLog]['certifier_note'] ?? '') ?: ''),
			'created_at' => (string)(($decBySort[$soLog]['decided_at'] ?? '') ?: ''),
		];
	}
	$report_submitter_reply_note = trim((string)($proj['result_report_submitter_reply_note'] ?? ''));
	$report_submitter_reply_updated_at = trim((string)($proj['result_report_submitter_reply_updated_at'] ?? ''));
	$report_submitter_reply_staff_id = (int)($proj['result_report_submitter_reply_staff_id'] ?? 0);
	if ($report_submitter_reply_note !== '') {
		$rejSoForReply = 0;
		foreach ($allSortOrders as $soLog) {
			if ((string)(($decBySort[$soLog]['decision'] ?? '') ?: '') === 'rejected') {
				$rejSoForReply = (int)$soLog;
			}
		}
		if ($rejSoForReply <= 0 && !empty($allSortOrders)) {
			$rejSoForReply = (int)$allSortOrders[0];
		}
		$report_action_logs[] = [
			'action_type' => 'submitter_reply_saved',
			'sort_order' => $rejSoForReply,
			'actor_name' => $staffName($report_submitter_reply_staff_id),
			'note' => $report_submitter_reply_note,
			'created_at' => $report_submitter_reply_updated_at,
		];
	}
	$summary = [[
		'row_kind' => 'data',
		'consistency_label' => 'โครงการ',
		'project_count' => 1,
		'budget_baht' => $budget_baht,
		'include_project_items' => [[
			'project_id' => (int)$project_id,
			'name' => (string)($proj['name_project'] ?? ''),
			'kpi_names' => []
		]]
	]];
	$planClosedAt = trim((string)($proj['result_report_plan_closed_at'] ?? ''));
	$planClosedBy = (int)($proj['result_report_plan_closed_by'] ?? 0);
	$planClosedNote = trim((string)($proj['result_report_plan_note'] ?? ''));
	$unitReportDone = ($completedAt !== '');
	$can_act_plan_report = $pm_appr_is_plan_admin && $unitReportDone && $planClosedAt === '';
	if ($planClosedAt !== '') {
		$report_action_logs[] = [
			'action_type' => 'plan_closed',
			'sort_order' => 0,
			'actor_name' => $staffName($planClosedBy),
			'note' => $planClosedNote,
			'created_at' => $planClosedAt,
		];
	}
	$request_status_report = 'pending';
	if ($planClosedAt !== '') {
		$request_status_report = 'approved';
	} elseif ($unitReportDone) {
		$request_status_report = 'pending_plan';
	} else {
		foreach ($allSortOrders as $soChk) {
			if ((string)(($decBySort[$soChk]['decision'] ?? '') ?: '') === 'rejected') {
				$request_status_report = 'rejected';
				break;
			}
		}
	}
	echo json_encode([
		'ok' => 1,
		'flow_type' => 'report',
		'request' => [
			'id' => (int)$project_id,
			'status' => $request_status_report,
			'budget_year' => (string)($proj['year_project'] ?? $years_select),
			'created_at' => $submittedAt,
			'subject' => 'รายงานผลโครงการ ' . (string)($proj['name_project'] ?? ''),
			'section_name' => $unit_name,
			'letter_body' => 'รหัสโครงการ ' . (string)($proj['code_pre_project'] ?? '-') . ' / ชื่อโครงการ ' . (string)($proj['name_project'] ?? '-'),
		],
		'plan_admin' => $pm_appr_is_plan_admin,
		'sender_name' => $staffName((int)($proj['staff_insert'] ?? 0)),
		'summary' => $summary,
		'report_detail' => [
			'project_code' => (string)($proj['code_pre_project'] ?? ''),
			'project_name' => (string)($proj['name_project'] ?? ''),
			'budget_baht' => $budget_baht,
			'budget_source' => $budget_source,
		],
		'years_select' => $years_select,
		'project_group' => $project_group,
		'certifiers' => $certifiers_payload,
		'action_logs' => $report_action_logs,
		'submitter_reply' => [
			'can_edit' => false,
			'note' => $report_submitter_reply_note,
			'updated_at' => $report_submitter_reply_updated_at,
		],
		'pipeline' => [
			'current_turn_sort' => $current_turn_sort,
			'current_turn_name' => $current_turn_name,
			'projects_pipeline_ok' => true,
			'can_act' => $can_act_modal,
			'my_sort_order' => $my_sort_order,
		],
		'plan_unit_review' => ['show' => false],
		'plan_report_review' => [
			'show' => $can_act_plan_report,
			'can_act' => $can_act_plan_report ? 1 : 0,
			'unit_done' => $unitReportDone ? 1 : 0,
			'closed' => $planClosedAt !== '' ? 1 : 0,
		],
	], JSON_UNESCAPED_UNICODE);
	exit;
}

if ($pm_appr_route_op === 'download_change_file') {
	if (function_exists('pm_ensure_change_request_schema')) {
		pm_ensure_change_request_schema($db_Project);
	}
	$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : (isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0);
	if ($file_id <= 0) {
		http_response_code(400);
		echo 'ไม่พบไฟล์';
		exit;
	}
	$qF = @mysqli_query(
		$db_Project,
		"SELECT f.id, f.original_name, f.stored_file, f.request_id, r.project_id, r.staff_insert, r.certifiers_json, r.approvers_json
		 FROM project_change_request_file f
		 INNER JOIN project_change_request r ON r.id = f.request_id
		 WHERE f.id='" . $file_id . "' AND f.status='1' AND r.status='1' LIMIT 1"
	);
	$fr = $qF ? mysqli_fetch_assoc($qF) : null;
	if (!$fr) {
		http_response_code(404);
		echo 'ไม่พบไฟล์';
		exit;
	}
	$allowed = $pm_appr_is_plan_admin || ((int)($fr['staff_insert'] ?? 0) === (int)$sid);
	if (!$allowed) {
		foreach (['certifiers_json', 'approvers_json'] as $jk) {
			$arr = json_decode((string)($fr[$jk] ?? ''), true);
			if (!is_array($arr)) {
				continue;
			}
			foreach ($arr as $c) {
				if (is_array($c) && (int)($c['staff_id'] ?? 0) === (int)$sid) {
					$allowed = true;
					break 2;
				}
			}
		}
	}
	if (!$allowed) {
		http_response_code(403);
		echo 'ไม่มีสิทธิ์ดาวน์โหลดไฟล์นี้';
		exit;
	}
	$stored = basename((string)($fr['stored_file'] ?? ''));
	$dir = realpath(__DIR__ . '/../document/project_change_files');
	$path = ($dir && $stored !== '') ? ($dir . DIRECTORY_SEPARATOR . $stored) : '';
	if ($path === '' || !is_file($path)) {
		http_response_code(404);
		echo 'ไม่พบไฟล์บนเซิร์ฟเวอร์';
		exit;
	}
	$orig = preg_replace('/[^\p{L}\p{N}\.\-_\s]/u', '_', (string)($fr['original_name'] ?? 'document.pdf'));
	if ($orig === '') {
		$orig = 'document.pdf';
	}
	header('Content-Type: application/pdf');
	header('Content-Disposition: attachment; filename="' . rawurlencode($orig) . '"');
	header('Content-Length: ' . (string)filesize($path));
	readfile($path);
	exit;
}

if ($pm_appr_route_op === 'download_cancel_file') {
	if (function_exists('pm_ensure_cancel_request_schema')) {
		pm_ensure_cancel_request_schema($db_Project);
	}
	$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : (isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0);
	if ($file_id <= 0) {
		http_response_code(400);
		echo 'ไม่พบไฟล์';
		exit;
	}
	$qF = @mysqli_query(
		$db_Project,
		"SELECT f.id, f.original_name, f.stored_file, f.request_id, r.project_id, r.staff_insert
		 FROM project_cancel_request_file f
		 INNER JOIN project_cancel_request r ON r.id = f.request_id
		 WHERE f.id='" . $file_id . "' AND f.status='1' AND r.status='1' LIMIT 1"
	);
	$fr = $qF ? mysqli_fetch_assoc($qF) : null;
	if (!$fr) {
		http_response_code(404);
		echo 'ไม่พบไฟล์';
		exit;
	}
	$allowed = $pm_appr_is_plan_admin || ((int)($fr['staff_insert'] ?? 0) === (int)$sid);
	if (!$allowed && function_exists('staffCanViewPreProjectManageDetail')) {
		$staff_code_dl = (string)($myRow['code'] ?? '');
		$unit_code_dl = (string)($myRow['unit_code'] ?? '');
		$allowed = staffCanViewPreProjectManageDetail($db_Project, (int)($fr['project_id'] ?? 0), (int)$sid, $staff_code_dl, $unit_code_dl);
	}
	if (!$allowed) {
		http_response_code(403);
		echo 'ไม่มีสิทธิ์ดาวน์โหลดไฟล์นี้';
		exit;
	}
	$stored = basename((string)($fr['stored_file'] ?? ''));
	$dir = realpath(__DIR__ . '/../document/project_cancel_files');
	$path = ($dir && $stored !== '') ? ($dir . DIRECTORY_SEPARATOR . $stored) : '';
	if ($path === '' || !is_file($path)) {
		http_response_code(404);
		echo 'ไม่พบไฟล์บนเซิร์ฟเวอร์';
		exit;
	}
	$orig = preg_replace('/[^\p{L}\p{N}\.\-_\s]/u', '_', (string)($fr['original_name'] ?? 'document.pdf'));
	if ($orig === '') {
		$orig = 'document.pdf';
	}
	header('Content-Type: application/pdf');
	header('Content-Disposition: attachment; filename="' . rawurlencode($orig) . '"');
	header('Content-Length: ' . (string)filesize($path));
	readfile($path);
	exit;
}

if ($pm_appr_route_op === 'get_cancel_request_detail' || $pm_appr_route_op === 'plan_admin_cancel_decide_ajax') {
	if (ob_get_level() > 0) {
		@ob_clean();
	}
	header('Content-Type: application/json; charset=utf-8');
	if (function_exists('pm_ensure_cancel_request_schema')) {
		pm_ensure_cancel_request_schema($db_Project);
	}
	$respondCan = static function ($ok, $msg, $extra = []) {
		echo json_encode(array_merge(['ok' => (int)$ok, 'msg' => (string)$msg], is_array($extra) ? $extra : []), JSON_UNESCAPED_UNICODE);
		exit;
	};
	$staffNameCan = static function ($staff_id) use ($db_person) {
		$staff_id = (int)$staff_id;
		if ($staff_id <= 0) {
			return '-';
		}
		$q = @mysqli_query($db_person, "SELECT fname,lname FROM staff WHERE id='" . $staff_id . "' LIMIT 1");
		$r = $q ? mysqli_fetch_assoc($q) : null;
		$nm = trim((string)(($r['fname'] ?? '') . ' ' . ($r['lname'] ?? '')));
		return $nm !== '' ? $nm : ('Staff #' . $staff_id);
	};
	$request_id = isset($_POST['cancel_request_id']) ? (int)$_POST['cancel_request_id'] : 0;
	if ($request_id <= 0) {
		$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
	}
	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	$req = null;
	if ($request_id > 0) {
		$qReq = @mysqli_query($db_Project, "SELECT * FROM project_cancel_request WHERE id='" . (int)$request_id . "' AND status='1' LIMIT 1");
		$req = $qReq ? mysqli_fetch_assoc($qReq) : null;
	} elseif ($project_id > 0 && function_exists('pm_load_project_cancel_request')) {
		$req = pm_load_project_cancel_request($db_Project, $project_id);
	}
	if (!is_array($req)) {
		$respondCan(0, 'ไม่พบคำขอยกเลิกโครงการ');
	}
	$request_id = (int)($req['id'] ?? 0);
	$project_id = (int)($req['project_id'] ?? 0);
	$isSubmitter = ((int)($req['staff_insert'] ?? 0) === (int)$sid);
	$allowed = $pm_appr_is_plan_admin || $isSubmitter;
	if (!$allowed && function_exists('staffCanViewPreProjectManageDetail')) {
		$staff_code_can = (string)($myRow['code'] ?? '');
		$unit_code_can = (string)($myRow['unit_code'] ?? '');
		$allowed = staffCanViewPreProjectManageDetail($db_Project, $project_id, (int)$sid, $staff_code_can, $unit_code_can);
	}
	if (!$allowed) {
		$respondCan(0, 'ไม่มีสิทธิ์ดูคำขอยกเลิกนี้');
	}

	if ($pm_appr_route_op === 'plan_admin_cancel_decide_ajax') {
		if (!$pm_appr_is_plan_admin) {
			$respondCan(0, 'เฉพาะหน่วยแผนเท่านั้นที่พิจารณาคำขอยกเลิกได้');
		}
		$action = trim((string)($_POST['action'] ?? $_POST['decision'] ?? ''));
		if ($action !== 'approve' && $action !== 'reject') {
			$respondCan(0, 'คำสั่งไม่ถูกต้อง');
		}
		$reqStatus = trim((string)($req['req_status'] ?? ''));
		if ($reqStatus !== 'pending') {
			$respondCan(0, 'คำขอนี้ไม่อยู่ในสถานะที่สามารถพิจารณาได้');
		}
		$decide_note = trim((string)($_POST['decide_note'] ?? $_POST['note'] ?? ''));
		if ($action === 'reject' && $decide_note === '') {
			$respondCan(0, 'กรุณาระบุเหตุผลก่อนตีกลับ');
		}
		$esc = static function ($v) use ($db_Project) {
			return mysqli_real_escape_string($db_Project, (string)$v);
		};
		$newStatus = $action === 'approve' ? 'approved' : 'rejected';
		$okUp = @mysqli_query(
			$db_Project,
			"UPDATE project_cancel_request SET
				req_status='" . $esc($newStatus) . "',
				decided_at=NOW(),
				decided_by_staff_id='" . (int)$sid . "',
				decide_note='" . $esc($decide_note) . "',
				updated_at=NOW()
			 WHERE id='" . (int)$request_id . "' AND req_status='pending' AND status='1' LIMIT 1"
		);
		if (!$okUp || mysqli_affected_rows($db_Project) <= 0) {
			$respondCan(0, (mysqli_error($db_Project) ?: 'บันทึกผลการพิจารณาไม่สำเร็จ'));
		}
		$projName = trim((string)($req['project_name'] ?? ''));
		$projCode = trim((string)($req['project_code'] ?? ''));
		$link = '?page=project_manage&op=add_project&project_id=' . (int)$project_id;
		if ($action === 'approve') {
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project SET cancelled_at=NOW(), cancelled_by_staff_id='" . (int)$sid . "', dates=NOW()
				 WHERE id='" . (int)$project_id . "' AND status='1' LIMIT 1"
			);
			$ttl = 'อนุมัติยกเลิกโครงการแล้ว';
			$body = trim(($projCode !== '' ? $projCode . ' ' : '') . $projName);
			if (function_exists('pm_staff_notify')) {
				pm_staff_notify($db_Project, (int)($req['staff_insert'] ?? 0), 'cancel_approved', $ttl, $body, $link, 'project_cancel', $request_id);
			}
			$respondCan(1, 'อนุมัติการยกเลิกโครงการแล้ว', ['req_status' => 'approved']);
		}
		$ttl = 'ตีกลับคำขอยกเลิกโครงการ';
		$body = trim(($projCode !== '' ? $projCode . ' ' : '') . $projName);
		if ($decide_note !== '') {
			$body .= "\nเหตุผล: " . $decide_note;
		}
		if (function_exists('pm_staff_notify')) {
			pm_staff_notify($db_Project, (int)($req['staff_insert'] ?? 0), 'cancel_rejected', $ttl, $body, $link, 'project_cancel', $request_id);
		}
		if (function_exists('pm_approve_inbox_clear_read')) {
			pm_approve_inbox_clear_read($db_Project, 'change', 'cancel:' . (int)$request_id);
		}
		$respondCan(1, 'ตีกลับคำขอยกเลิกแล้ว ผู้ยื่นสามารถแก้ไขและส่งใหม่ได้', ['req_status' => 'rejected']);
	}

	$files = [];
	$qF = @mysqli_query($db_Project, "SELECT id, original_name FROM project_cancel_request_file WHERE request_id='" . (int)$request_id . "' AND status='1' ORDER BY id ASC");
	while ($qF && ($fr = mysqli_fetch_assoc($qF))) {
		$fid = (int)($fr['id'] ?? 0);
		$files[] = [
			'id' => $fid,
			'name' => (string)($fr['original_name'] ?? ''),
			'url' => '?page=project_approve&op=download_cancel_file&file_id=' . $fid,
		];
	}
	$reqStatus = trim((string)($req['req_status'] ?? ''));
	$can_decide = $pm_appr_is_plan_admin && $reqStatus === 'pending';
	$respondCan(1, '', [
		'request' => [
			'id' => $request_id,
			'project_id' => $project_id,
			'project_name' => (string)($req['project_name'] ?? ''),
			'project_code' => (string)($req['project_code'] ?? ''),
			'budget_source' => (string)($req['budget_source'] ?? '-'),
			'budget_amount' => (float)($req['budget_amount'] ?? 0),
			'reason' => (string)($req['reason'] ?? ''),
			'req_status' => $reqStatus,
			'decide_note' => (string)($req['decide_note'] ?? ''),
			'decided_at' => (string)($req['decided_at'] ?? ''),
			'submitted_at' => (string)($req['submitted_at'] ?? ''),
			'created_at' => (string)($req['created_at'] ?? ''),
			'sender_name' => $staffNameCan((int)($req['staff_insert'] ?? 0)),
			'files' => $files,
		],
		'plan_admin' => $pm_appr_is_plan_admin ? 1 : 0,
		'can_decide' => $can_decide ? 1 : 0,
		'years_select' => $years_select ?? '',
	]);
}

// -----------------------------------------------------------------------------
// AJAX: get change request detail (ขอเปลี่ยนแปลงโครงการ)
// -----------------------------------------------------------------------------
if ($pm_appr_route_op === 'get_change_request_detail') {
	if (ob_get_level() > 0) {
		@ob_clean();
	}
	header('Content-Type: application/json; charset=utf-8');
	if (function_exists('pm_ensure_change_request_schema')) {
		pm_ensure_change_request_schema($db_Project);
	}
	$request_id = isset($_POST['change_request_id']) ? (int)$_POST['change_request_id'] : 0;
	if ($request_id <= 0) {
		$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
	}
	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	if ($request_id <= 0 && $project_id > 0 && function_exists('pm_project_pending_change_request_id')) {
		$request_id = pm_project_pending_change_request_id($db_Project, $project_id);
	}
	if ($request_id <= 0) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบคำขอเปลี่ยนแปลง'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$qReq = @mysqli_query($db_Project, "SELECT * FROM project_change_request WHERE id='" . (int)$request_id . "' AND status='1' LIMIT 1");
	$req = $qReq ? mysqli_fetch_assoc($qReq) : null;
	if (!$req) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่พบคำขอเปลี่ยนแปลง'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$project_id = (int)($req['project_id'] ?? 0);
	$staffName = function ($staff_id) use ($db_person) {
		$staff_id = (int)$staff_id;
		if ($staff_id <= 0) {
			return '-';
		}
		$q = @mysqli_query($db_person, "SELECT fname,lname FROM staff WHERE id='" . $staff_id . "' LIMIT 1");
		$r = $q ? mysqli_fetch_assoc($q) : null;
		$nm = trim((string)(($r['fname'] ?? '') . ' ' . ($r['lname'] ?? '')));
		return $nm !== '' ? $nm : ('Staff #' . $staff_id);
	};
	$parseChain = static function ($json) {
		$arr = json_decode((string)$json, true);
		$list = [];
		$seq = 0;
		if (!is_array($arr)) {
			return $list;
		}
		foreach ($arr as $c) {
			if (!is_array($c)) {
				continue;
			}
			$seq++;
			$so = (int)($c['sort_order'] ?? 0);
			if ($so <= 0) {
				$so = $seq;
			}
			$sidC = (int)($c['staff_id'] ?? 0);
			if ($sidC <= 0) {
				continue;
			}
			$list[$so] = [
				'staff_id' => $sidC,
				'position_title' => trim((string)($c['position'] ?? $c['position_title'] ?? '')),
			];
		}
		return $list;
	};
	$loadDec = static function ($db, $table, $rid) {
		$map = [];
		$q = @mysqli_query($db, "SELECT sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at FROM `" . $table . "` WHERE request_id='" . (int)$rid . "'");
		while ($q && ($rd = mysqli_fetch_assoc($q))) {
			$so = (int)($rd['sort_order'] ?? 0);
			if ($so > 0) {
				$map[$so] = $rd;
			}
		}
		return $map;
	};
	$certList = $parseChain($req['certifiers_json'] ?? '');
	$apprList = $parseChain($req['approvers_json'] ?? '');
	$reqStatus = trim((string)($req['req_status'] ?? 'pending'));
	$useApprover = ($reqStatus === 'pending_approve' || $reqStatus === 'approved') && !empty($apprList);
	$activeList = $useApprover ? $apprList : $certList;
	$decBySort = $loadDec($db_Project, $useApprover ? 'project_change_approver_decision' : 'project_change_certifier_decision', $request_id);
	$allSortOrders = array_keys($activeList);
	sort($allSortOrders, SORT_NUMERIC);
	$myOrders = [];
	foreach ($allSortOrders as $so) {
		if ((int)($activeList[$so]['staff_id'] ?? 0) === (int)$sid) {
			$myOrders[] = (int)$so;
		}
	}
	sort($myOrders, SORT_NUMERIC);
	$my_sort_order = 0;
	foreach ($myOrders as $candidateSo) {
		if ((string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '') === '') {
			$my_sort_order = (int)$candidateSo;
			break;
		}
	}
	if ($my_sort_order <= 0 && count($myOrders) > 0) {
		$my_sort_order = (int)$myOrders[0];
	}
	$current_turn_sort = 0;
	$current_turn_name = '-';
	$certifiers_payload = [];
	foreach ($allSortOrders as $so) {
		$dec = (string)(($decBySort[$so]['decision'] ?? '') ?: '');
		$decAt = (string)(($decBySort[$so]['decided_at'] ?? '') ?: '');
		$decNote = (string)(($decBySort[$so]['certifier_note'] ?? '') ?: '');
		$st = 'waiting_queue';
		if ($dec === 'approved') {
			$st = 'approved';
		} elseif ($dec === 'rejected') {
			$st = 'rejected';
		} else {
			$prevOk = true;
			foreach ($allSortOrders as $pso) {
				if ($pso >= $so) {
					break;
				}
				if ((string)(($decBySort[$pso]['decision'] ?? '') ?: '') !== 'approved') {
					$prevOk = false;
					break;
				}
			}
			$st = $prevOk ? 'waiting_turn' : 'waiting_queue';
			if ($st === 'waiting_turn' && $current_turn_sort <= 0) {
				$current_turn_sort = (int)$so;
				$current_turn_name = $staffName((int)($activeList[$so]['staff_id'] ?? 0));
			}
		}
		$sidC = (int)($activeList[$so]['staff_id'] ?? 0);
		$certifiers_payload[] = [
			'sort_order' => (int)$so,
			'staff_id' => $sidC,
			'staff_name' => $staffName($sidC),
			'position_title' => (string)($activeList[$so]['position_title'] ?? ''),
			'status' => $st,
			'decision' => $dec,
			'decided_at' => $decAt,
			'certifier_note' => $decNote,
			'note' => $decNote,
			'is_current_turn' => ((int)$so === (int)$current_turn_sort) ? 1 : 0,
			'can_edit_row' => 0,
		];
	}
	$my_decision = ($my_sort_order > 0) ? (string)(($decBySort[$my_sort_order]['decision'] ?? '') ?: '') : '';
	$can_act_modal = false;
	if (in_array($reqStatus, ['pending', 'pending_approve'], true) && $my_sort_order > 0 && $my_decision === '') {
		$prevOk = true;
		foreach ($allSortOrders as $prevSo) {
			if ($prevSo >= $my_sort_order) {
				break;
			}
			if ((string)(($decBySort[$prevSo]['decision'] ?? '') ?: '') !== 'approved') {
				$prevOk = false;
				break;
			}
		}
		$can_act_modal = $prevOk;
	}
	$certsAllApproved = count($certList) > 0;
	$certDecMap = $loadDec($db_Project, 'project_change_certifier_decision', $request_id);
	$cSeq = 0;
	foreach ($certList as $soC => $_c) {
		$cSeq++;
		$soUse = (int)$soC > 0 ? (int)$soC : $cSeq;
		if ((string)(($certDecMap[$soUse]['decision'] ?? '') ?: '') !== 'approved') {
			$certsAllApproved = false;
			break;
		}
	}
	$show_plan_rechain = $pm_appr_is_plan_admin
		&& $certsAllApproved
		&& trim((string)($req['plan_rechain_submitted_at'] ?? '')) === ''
		&& trim((string)($req['approvers_json'] ?? '')) === ''
		&& in_array($reqStatus, ['pending', 'waiting_plan'], true);
	$modal_rechain_staff_opts = [];
	if ($show_plan_rechain && function_exists('getStaffSelect')) {
		foreach (getStaffSelect($db_person) as $__msx) {
			$__idm = (int)($__msx['id'] ?? 0);
			if ($__idm <= 0) {
				continue;
			}
			$__txm = trim((string)(($__msx['fname'] ?? '') . ' ' . ($__msx['lname'] ?? '')));
			if ($__txm === '') {
				$__txm = 'Staff #' . $__idm;
			}
			$modal_rechain_staff_opts[] = [
				'id' => $__idm,
				'text' => $__txm,
				'position' => function_exists('pm_staff_position_label') ? pm_staff_position_label($db_person, $__msx) : '',
			];
		}
	}
	$details = json_decode((string)($req['details_json'] ?? ''), true);
	if (!is_array($details)) {
		$details = [];
	}
	$files = [];
	$qF = @mysqli_query($db_Project, "SELECT id, original_name, stored_file FROM project_change_request_file WHERE request_id='" . (int)$request_id . "' AND status='1' ORDER BY id ASC");
	while ($qF && ($fr = mysqli_fetch_assoc($qF))) {
		$fid = (int)($fr['id'] ?? 0);
		$files[] = [
			'id' => $fid,
			'name' => (string)($fr['original_name'] ?? ''),
			'url' => '?page=project_approve&op=download_change_file&file_id=' . $fid,
		];
	}
	$unit_name = '-';
	$qProj = @mysqli_query($db_Project, "SELECT year_project, code_unit FROM pre_project WHERE id='" . (int)$project_id . "' LIMIT 1");
	$proj = $qProj ? mysqli_fetch_assoc($qProj) : null;
	$uc = trim((string)($proj['code_unit'] ?? ''));
	if (function_exists('pm_project_unit_display_name')) {
		$__un = pm_project_unit_display_name($db_Project, $uc, $db_person ?? null);
		if ($__un !== '') {
			$unit_name = $__un;
		}
	} elseif ($uc !== '') {
		$qU = @mysqli_query($db_person, "SELECT name FROM unit WHERE code='" . mysqli_real_escape_string($db_person, $uc) . "' LIMIT 1");
		$rU = $qU ? mysqli_fetch_assoc($qU) : null;
		if ($rU && trim((string)($rU['name'] ?? '')) !== '') {
			$unit_name = trim((string)$rU['name']);
		}
	}
	$request_status_out = 'pending';
	if ($reqStatus === 'approved') {
		$request_status_out = 'approved';
	} elseif ($reqStatus === 'rejected') {
		$request_status_out = 'rejected';
	}
	echo json_encode([
		'ok' => 1,
		'flow_type' => 'change',
		'plan_admin_change_rechain' => [
			'show' => $show_plan_rechain,
			'project_id' => (int)$project_id,
			'change_request_id' => (int)$request_id,
			'staff_options' => $modal_rechain_staff_opts,
		],
		'request' => [
			'id' => (int)$request_id,
			'status' => $request_status_out,
			'budget_year' => (string)($proj['year_project'] ?? $years_select),
			'created_at' => (string)($req['created_at'] ?? ''),
			'subject' => 'ขอเปลี่ยนแปลงโครงการ ' . (string)($req['project_name'] ?? ''),
			'section_name' => $unit_name,
			'letter_body' => (string)($req['reason'] ?? ''),
		],
		'plan_admin' => $pm_appr_is_plan_admin,
		'sender_name' => $staffName((int)($req['staff_insert'] ?? 0)),
		'summary' => [],
		'change_detail' => [
			'project_code' => (string)($req['project_code'] ?? ''),
			'project_name' => (string)($req['project_name'] ?? ''),
			'budget_baht' => (float)($req['budget_amount'] ?? 0),
			'budget_source' => (string)($req['budget_source'] ?? '-'),
			'reason' => (string)($req['reason'] ?? ''),
			'details' => $details,
			'files' => $files,
			'applied_at' => (string)($req['applied_at'] ?? ''),
			'applied_at_th' => function_exists('pm_change_thai_dt') ? pm_change_thai_dt($req['applied_at'] ?? '') : (string)($req['applied_at'] ?? ''),
			'applied_by_name' => $staffName((int)($req['applied_by_staff_id'] ?? 0)),
			'apply_note' => (string)($req['apply_note'] ?? ''),
			'manage_url' => '?page=project_manage&op=add_project&project_id=' . (int)$project_id . '&pm_scroll=pmChangeHistory',
		],
		'years_select' => $years_select,
		'project_group' => $project_group,
		'change_request_id' => (int)$request_id,
		'certifiers' => $certifiers_payload,
		'action_logs' => [],
		'submitter_reply' => ['can_edit' => false, 'note' => '', 'updated_at' => ''],
		'pipeline' => [
			'current_turn_sort' => $current_turn_sort,
			'current_turn_name' => $current_turn_name,
			'projects_pipeline_ok' => true,
			'can_act' => $can_act_modal,
			'my_sort_order' => $my_sort_order,
		],
		'plan_unit_review' => ['show' => false],
	], JSON_UNESCAPED_UNICODE);
	exit;
}

$op = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
if ($op === '' && isset($_POST['op'])) {
	$op = trim((string)$_POST['op']);
}
if ($op !== '') {
	$action = '';
	if ($op === 'certify_approve') $action = 'approve';
	if ($op === 'certify_reject') $action = 'reject';
	if ($op === 'certify_operate_approve') $action = 'approve';
	if ($op === 'certify_operate_reject') $action = 'reject';
	if ($op === 'certify_report_approve') $action = 'approve';
	if ($op === 'certify_report_reject') $action = 'reject';
	if ($op === 'plan_admin_report_approve') $action = 'approve';
	if ($op === 'plan_admin_report_reject') $action = 'reject';
	if ($op === 'certify_change_approve') $action = 'approve';
	if ($op === 'certify_change_reject') $action = 'reject';

	// AJAX: Admin หน่วยงานยืนยันการแก้ไขทั้งคำขอ → ส่งกลับผู้รับรองที่ตีกลับ
	if ($op === 'admin_send_include_fix') {
		$isAjax = true;
		@ini_set('display_errors', '0');
		if (ob_get_level() === 0) { @ob_start(); }
		$respondJson = function ($ok, $msg, $data = []) {
			if (ob_get_level() > 0) { @ob_clean(); }
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array_merge(['ok' => (int)$ok, 'msg' => (string)$msg], is_array($data) ? $data : []), JSON_UNESCAPED_UNICODE);
			exit;
		};
		$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
		if ($request_id <= 0) {
			$respondJson(0, 'ไม่พบคำขอ');
		}
		if (function_exists('ensureIncludeRequestTables')) {
			ensureIncludeRequestTables($db_Project);
		}
		$qReq = @mysqli_query($db_Project, "SELECT * FROM project_include_request WHERE id='" . (int)$request_id . "' LIMIT 1");
		$req = $qReq ? mysqli_fetch_assoc($qReq) : null;
		if (!$req || (string)($req['status'] ?? '') !== 'rejected') {
			$respondJson(0, 'คำขอนี้ไม่ได้อยู่ในสถานะตีกลับ');
		}
		if (trim((string)($req['admin_fix_sent_at'] ?? '')) !== '') {
			$respondJson(0, 'ส่งยืนยันการแก้ไขไปแล้ว');
		}
		$isSubmitter = ((int)($req['submitter_staff_id'] ?? 0) === (int)$sid);
		$isUa = function_exists('isUnitAdmin') && isUnitAdmin($db_Project, (string)$staff_code, (string)$code_unit, (int)$sid);
		if (!$isSubmitter && !$isUa) {
			$respondJson(0, 'ไม่มีสิทธิ์ยืนยันการแก้ไข');
		}
		if (!function_exists('pm_include_all_returned_fixes_confirmed') || !pm_include_all_returned_fixes_confirmed($db_Project, $request_id)) {
			$respondJson(0, 'ยังมีโครงการที่ถูกตีกลับยังไม่ได้ยืนยันการแก้ไขครบ');
		}
		$items = function_exists('pm_include_returned_items') ? pm_include_returned_items($db_Project, $request_id) : [];
		$notesSummary = [];
		foreach ($items as $it) {
			$notesSummary[] = [
				'project_id' => (int)($it['project_id'] ?? 0),
				'code' => (string)($it['code_pre_project'] ?? ''),
				'name' => (string)($it['name_project'] ?? ''),
				'fix_note' => (string)($it['fix_note'] ?? ''),
				'fix_at' => (string)($it['fix_at'] ?? ''),
			];
		}
		@mysqli_query(
			$db_Project,
			"UPDATE project_include_request_item
			 SET fix_status='admin_sent'
			 WHERE request_id='" . (int)$request_id . "' AND IFNULL(return_selected,0)=1"
		);
		@mysqli_query(
			$db_Project,
			"UPDATE project_include_request
			 SET admin_fix_sent_at=NOW(), admin_fix_sent_by='" . (int)$sid . "', updated_at=NOW()
			 WHERE id='" . (int)$request_id . "' LIMIT 1"
		);
		$metaEsc = mysqli_real_escape_string($db_Project, json_encode(['items' => $notesSummary], JSON_UNESCAPED_UNICODE));
		$noteEsc = mysqli_real_escape_string($db_Project, 'Admin ยืนยันการแก้ไขและส่งกลับผู้รับรอง');
		@mysqli_query(
			$db_Project,
			"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, sort_order, note, meta_json, created_at)
			 VALUES ('" . (int)$request_id . "', 'admin_fix_sent', '" . (int)$sid . "', 0, '" . $noteEsc . "', '" . $metaEsc . "', NOW())"
		);
		if (function_exists('pm_approve_inbox_clear_read')) {
			pm_approve_inbox_clear_read($db_Project, 'include', 'include:' . (int)$request_id);
		}
		$rejStaffIds = [];
		$qRejCert = @mysqli_query(
			$db_Project,
			"SELECT DISTINCT c.staff_id
			 FROM project_include_request_certifier c
			 JOIN project_include_request_certifier_decision d
			   ON d.request_id = c.request_id AND d.sort_order = c.sort_order
			 WHERE c.request_id='" . (int)$request_id . "'
			   AND LOWER(TRIM(IFNULL(d.decision,''))) = 'rejected'
			   AND IFNULL(c.staff_id,0) > 0"
		);
		while ($qRejCert && ($rr = mysqli_fetch_assoc($qRejCert))) {
			$rejSid = (int)($rr['staff_id'] ?? 0);
			if ($rejSid > 0) {
				$rejStaffIds[] = $rejSid;
			}
		}
		$subjFix = function_exists('pm_include_request_subject_label')
			? pm_include_request_subject_label((string)($req['subject'] ?? ''))
			: trim((string)($req['subject'] ?? ''));
		$linkFix = '?page=project_approve&flow=include&request_id=' . (int)$request_id;
		$bodyFix = 'คำขอ #' . (int)$request_id . ($subjFix !== '' ? (' — ' . $subjFix) : '') . "\nAdmin หน่วยงานยืนยันการแก้ไขและส่งกลับให้ตรวจสอบ";
		if (function_exists('pm_notify_revision_needed')) {
			pm_notify_revision_needed(
				$db_Project,
				$rejStaffIds,
				'include_fix_resent',
				'คำขอพิจารณาแผนงานถูกส่งกลับให้ตรวจสอบ',
				$bodyFix,
				$linkFix,
				'include_request',
				(int)$request_id,
				(int)$sid
			);
		}
		$respondJson(1, 'ส่งยืนยันการแก้ไขไปยังผู้รับรองแล้ว', ['request_id' => $request_id]);
	}

	// AJAX: ผู้ยื่นเสนอชี้แจง/ตอบกลับ (กรณีตีกลับ)
	if ($op === 'save_submitter_reply') {
		$isAjax = false;
		if (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1') $isAjax = true;
		if (!$isAjax && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') $isAjax = true;
		if ($isAjax) {
			@ini_set('display_errors', '0');
			@ini_set('html_errors', '0');
			if (ob_get_level() === 0) { @ob_start(); }
		}
		$respondJson = function ($ok, $msg, $data = []) use (&$isAjax) {
			$ok = (int)$ok;
			$msg = (string)$msg;
			if (ob_get_level() > 0) { @ob_clean(); }
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], is_array($data) ? $data : []), JSON_UNESCAPED_UNICODE);
			exit;
		};

		$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
		$note = isset($_POST['submitter_reply_note']) ? trim((string)$_POST['submitter_reply_note']) : '';
		if ($request_id <= 0) {
			$respondJson(0, 'ไม่พบคำขอ');
		}
		if ($note === '') {
			$respondJson(0, 'กรุณากรอกคำชี้แจงก่อนยืนยันส่งกลับผู้ตรวจสอบ');
		}
		// ensure column
		$chkReplyCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'submitter_reply_note'");
		if (!$chkReplyCol || mysqli_num_rows($chkReplyCol) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN submitter_reply_note MEDIUMTEXT NULL AFTER letter_body");
		}
		$chkReplyAtCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'submitter_reply_updated_at'");
		if (!$chkReplyAtCol || mysqli_num_rows($chkReplyAtCol) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN submitter_reply_updated_at DATETIME NULL AFTER submitter_reply_note");
		}
		$qReq = @mysqli_query($db_Project, "SELECT submitter_staff_id, status FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
		$req = $qReq ? mysqli_fetch_assoc($qReq) : null;
		if (!$req) {
			$respondJson(0, 'ไม่พบข้อมูลคำขอ');
		}
		if ((int)($req['submitter_staff_id'] ?? 0) !== (int)$sid) {
			$respondJson(0, 'ไม่มีสิทธิ์บันทึกข้อความชี้แจง');
		}
		if ((string)($req['status'] ?? '') !== 'rejected') {
			$respondJson(0, 'คำขอนี้ไม่ได้อยู่ในสถานะตีกลับ');
		}
		$noteEsc = mysqli_real_escape_string($db_Project, $note);
		$ok = @mysqli_query(
			$db_Project,
			"UPDATE project_include_request
			 SET submitter_reply_note='".$noteEsc."', submitter_reply_updated_at=NOW(), updated_at=NOW()
			 WHERE id='".(int)$request_id."' LIMIT 1"
		);
		if (!$ok) {
			$respondJson(0, 'บันทึกไม่สำเร็จ: ' . (mysqli_error($db_Project) ?: ''));
		}
		// ยืนยันส่งกลับผู้ตรวจสอบ: สถานะโครงการ = รอบรรจุโครงการ (3) และล็อกไม่ให้แก้ไข
		$lockIds = [];
		$qLockIds = @mysqli_query(
			$db_Project,
			"SELECT project_id FROM project_include_request_item WHERE request_id='" . (int)$request_id . "'"
		);
		while ($qLockIds && ($lr = mysqli_fetch_assoc($qLockIds))) {
			$pidL = (int)($lr['project_id'] ?? 0);
			if ($pidL > 0) $lockIds[$pidL] = $pidL;
		}
		if (!empty($lockIds)) {
			$idsCsv = implode(',', array_map('intval', array_values($lockIds)));
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project
				 SET status_project='3', dates=NOW()
				 WHERE status='1'
				   AND status_project IN ('8','3')
				   AND id IN ($idsCsv)"
			);
		}
		$qAt = @mysqli_query($db_Project, "SELECT submitter_reply_updated_at FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
		$atRow = $qAt ? mysqli_fetch_assoc($qAt) : null;
		// log action
		@mysqli_query(
			$db_Project,
			"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, note, created_at)
			 VALUES ('".(int)$request_id."', 'submitter_reply_saved', '".(int)$sid."', '".$noteEsc."', NOW())"
		);
		if (function_exists('pm_approve_inbox_clear_read')) {
			pm_approve_inbox_clear_read($db_Project, 'include', 'include:' . (int)$request_id);
		}
		$rejStaffIds = [];
		$qRejCert = @mysqli_query(
			$db_Project,
			"SELECT DISTINCT c.staff_id
			 FROM project_include_request_certifier c
			 JOIN project_include_request_certifier_decision d
			   ON d.request_id = c.request_id AND d.sort_order = c.sort_order
			 WHERE c.request_id='" . (int)$request_id . "'
			   AND LOWER(TRIM(IFNULL(d.decision,''))) = 'rejected'
			   AND IFNULL(c.staff_id,0) > 0"
		);
		while ($qRejCert && ($rr = mysqli_fetch_assoc($qRejCert))) {
			$rejSid = (int)($rr['staff_id'] ?? 0);
			if ($rejSid > 0) {
				$rejStaffIds[] = $rejSid;
			}
		}
		$subjFix = function_exists('pm_include_request_subject_label')
			? pm_include_request_subject_label((string)($req['subject'] ?? ''))
			: trim((string)($req['subject'] ?? ''));
		$linkFix = '?page=project_approve&flow=include&request_id=' . (int)$request_id;
		$bodyFix = 'คำขอ #' . (int)$request_id . ($subjFix !== '' ? (' — ' . $subjFix) : '') . "\nผู้ยื่นส่งคำชี้แจงกลับให้ตรวจสอบ";
		if (function_exists('pm_notify_revision_needed')) {
			pm_notify_revision_needed(
				$db_Project,
				$rejStaffIds,
				'include_fix_resent',
				'คำขอพิจารณาแผนงานถูกส่งกลับให้ตรวจสอบ',
				$bodyFix,
				$linkFix,
				'include_request',
				(int)$request_id,
				(int)$sid
			);
		}
		$respondJson(1, 'บันทึกคำชี้แจงเรียบร้อย — ส่งกลับผู้ตรวจสอบแล้ว (รอบรรจุโครงการ)', [
			'note' => $note,
			'updated_at' => (string)($atRow['submitter_reply_updated_at'] ?? ''),
			'status_project' => '3',
			'show_edit_all_projects' => false,
		]);
	}

	// AJAX: ผู้ยื่นเสนอชี้แจง/ตอบกลับ (กรณีหน่วยแผนตีกลับ) + ส่งกลับให้ Plan-Admin พิจารณาใหม่
	if ($op === 'save_plan_unit_submitter_reply') {
		$isAjax = false;
		if (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1') $isAjax = true;
		if (!$isAjax && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') $isAjax = true;
		if ($isAjax) {
			@ini_set('display_errors', '0');
			@ini_set('html_errors', '0');
			if (ob_get_level() === 0) { @ob_start(); }
		}
		$respondJson = function ($ok, $msg, $data = []) use (&$isAjax) {
			$ok = (int)$ok;
			$msg = (string)$msg;
			if (ob_get_level() > 0) { @ob_clean(); }
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], is_array($data) ? $data : []), JSON_UNESCAPED_UNICODE);
			exit;
		};

		$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
		$note = isset($_POST['plan_unit_submitter_reply_note']) ? trim((string)$_POST['plan_unit_submitter_reply_note']) : '';
		if ($request_id <= 0) {
			$respondJson(0, 'ไม่พบคำขอ');
		}
		if ($note === '') {
			$respondJson(0, 'กรุณากรอกข้อความชี้แจง/ตอบกลับ');
		}
		// ensure columns
		$chkPuReplyCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_submitter_reply_note'");
		if (!$chkPuReplyCol || mysqli_num_rows($chkPuReplyCol) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_submitter_reply_note MEDIUMTEXT NULL AFTER plan_unit_decided_at");
		}
		$chkPuReplyAtCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_submitter_reply_updated_at'");
		if (!$chkPuReplyAtCol || mysqli_num_rows($chkPuReplyAtCol) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_submitter_reply_updated_at DATETIME NULL AFTER plan_unit_submitter_reply_note");
		}

		$qReq = @mysqli_query($db_Project, "SELECT submitter_staff_id, status, plan_unit_decision FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
		$req = $qReq ? mysqli_fetch_assoc($qReq) : null;
		if (!$req) {
			$respondJson(0, 'ไม่พบข้อมูลคำขอ');
		}
		if ((int)($req['submitter_staff_id'] ?? 0) !== (int)$sid) {
			$respondJson(0, 'ไม่มีสิทธิ์ส่งคำชี้แจงถึงหน่วยแผน');
		}
		if ((string)($req['status'] ?? '') !== 'rejected') {
			$respondJson(0, 'คำขอนี้ไม่ได้อยู่ในสถานะตีกลับ');
		}
		if (trim((string)($req['plan_unit_decision'] ?? '')) !== 'returned') {
			$respondJson(0, 'คำขอนี้ไม่ได้ถูกตีกลับโดยหน่วยแผน');
		}

		$noteEsc = mysqli_real_escape_string($db_Project, $note);
		// 1) บันทึกข้อความตอบกลับ
		$ok = @mysqli_query(
			$db_Project,
			"UPDATE project_include_request
			 SET plan_unit_submitter_reply_note='".$noteEsc."', plan_unit_submitter_reply_updated_at=NOW(), updated_at=NOW()
			 WHERE id='".(int)$request_id."' LIMIT 1"
		);
		if (!$ok) {
			$respondJson(0, 'บันทึกไม่สำเร็จ: ' . (mysqli_error($db_Project) ?: ''));
		}
		// 2) ส่งกลับเข้า workflow ให้ Plan-Admin พิจารณาใหม่: คืนสถานะรอบรรจุ (3) + เคลียร์ผลเดิมของหน่วยแผน
		@mysqli_query(
			$db_Project,
			"UPDATE project_include_request
			 SET status='submitted',
				 plan_unit_decision=NULL,
				 plan_unit_note=NULL,
				 plan_unit_staff_id=NULL,
				 plan_unit_decided_at=NULL,
				 plan_unit_accepted_by=NULL,
				 plan_unit_accepted_at=NULL,
				 updated_at=NOW()
			 WHERE id='".(int)$request_id."' LIMIT 1"
		);
		// ดันโครงการกลับไปรอบรรจุโครงการ (3) — ยังอยู่ในหน้าร่างจนกว่าหน่วยแผนจะนำเข้าที่ประชุม
		@mysqli_query(
			$db_Project,
			"UPDATE pre_project
			 SET status_project='3', dates=NOW()
			 WHERE status='1'
			   AND status_project='8'
			   AND id IN (SELECT i.project_id FROM project_include_request_item i WHERE i.request_id='".(int)$request_id."')"
		);
		// log action
		@mysqli_query(
			$db_Project,
			"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, note, created_at)
			 VALUES ('".(int)$request_id."', 'plan_unit_submitter_reply_saved', '".(int)$sid."', '".$noteEsc."', NOW())"
		);
		$qAt = @mysqli_query($db_Project, "SELECT plan_unit_submitter_reply_updated_at FROM project_include_request WHERE id='".(int)$request_id."' LIMIT 1");
		$atRow = $qAt ? mysqli_fetch_assoc($qAt) : null;
		$respondJson(1, 'ส่งคำชี้แจงถึงหน่วยแผนเรียบร้อย', ['note' => $note, 'updated_at' => (string)($atRow['plan_unit_submitter_reply_updated_at'] ?? '')]);
	}

	// AJAX: หน่วยแผนและงบประมาณ (เฉพาะ PLAN_ADMIN)
	if ($op === 'plan_unit_accept') {
		$isAjaxPu = true;
		@ini_set('display_errors', '0');
		if (ob_get_level() === 0) {
			@ob_start();
		}
		$respondJsonPu = function ($ok, $msg, $data = []) {
			if (ob_get_level() > 0) {
				@ob_clean();
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array_merge(['ok' => (int)$ok, 'msg' => (string)$msg], is_array($data) ? $data : []), JSON_UNESCAPED_UNICODE);
			exit;
		};
		if (!$pm_appr_is_plan_admin) {
			$respondJsonPu(0, 'ไม่มีสิทธิ์รับเรื่องในฐานะหน่วยแผนและงบประมาณ');
		}
		$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
		if ($request_id <= 0) {
			$respondJsonPu(0, 'ไม่พบคำขอ');
		}
		$chkAccBy = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'plan_unit_accepted_by'");
		if (!$chkAccBy || mysqli_num_rows($chkAccBy) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_accepted_by INT UNSIGNED NULL DEFAULT NULL");
			@mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN plan_unit_accepted_at DATETIME NULL DEFAULT NULL");
		}
		$qPuReq = @mysqli_query(
			$db_Project,
			"SELECT id, status, IFNULL(plan_unit_decision,'') AS plan_unit_decision,
			        IFNULL(plan_unit_accepted_by,0) AS plan_unit_accepted_by
			 FROM project_include_request WHERE id='" . (int)$request_id . "' LIMIT 1"
		);
		$rowPu = $qPuReq ? mysqli_fetch_assoc($qPuReq) : null;
		if (!$rowPu) {
			$respondJsonPu(0, 'ไม่พบข้อมูลคำขอ');
		}
		if ((string)($rowPu['status'] ?? '') === 'rejected') {
			$respondJsonPu(0, 'คำขอนี้ถูกตีกลับแล้ว');
		}
		if (trim((string)($rowPu['plan_unit_decision'] ?? '')) !== '') {
			$respondJsonPu(0, 'คำขอนี้มีผลการพิจารณาจากหน่วยแผนแล้ว');
		}
		$accBy = (int)($rowPu['plan_unit_accepted_by'] ?? 0);
		if ($accBy > 0) {
			$respondJsonPu(0, 'มีผู้รับเรื่องไปแล้ว');
		}
		// ต้องผ่านผู้รับรองครบก่อน
		$qCertCnt = @mysqli_query($db_Project, "SELECT COUNT(*) AS c FROM project_include_request_certifier WHERE request_id='" . (int)$request_id . "'");
		$certCnt = (int)(($qCertCnt ? mysqli_fetch_assoc($qCertCnt) : null)['c'] ?? 0);
		$qApprCnt = @mysqli_query($db_Project, "SELECT COUNT(*) AS c FROM project_include_request_certifier_decision WHERE request_id='" . (int)$request_id . "' AND decision='approved'");
		$apprCnt = (int)(($qApprCnt ? mysqli_fetch_assoc($qApprCnt) : null)['c'] ?? 0);
		if ($certCnt <= 0 || $apprCnt < $certCnt) {
			$respondJsonPu(0, 'รอให้ผู้รับรองทุกลำดับผ่านก่อน');
		}
		$okAcc = @mysqli_query(
			$db_Project,
			"UPDATE project_include_request
			 SET plan_unit_accepted_by='" . (int)$sid . "',
			     plan_unit_accepted_at=NOW(),
			     updated_at=NOW()
			 WHERE id='" . (int)$request_id . "'
			   AND (plan_unit_accepted_by IS NULL OR plan_unit_accepted_by=0)
			   AND (plan_unit_decision IS NULL OR plan_unit_decision='')
			 LIMIT 1"
		);
		if (!$okAcc || mysqli_affected_rows($db_Project) <= 0) {
			$respondJsonPu(0, 'รับเรื่องไม่สำเร็จ (อาจมีผู้รับไปแล้ว)');
		}
		@mysqli_query(
			$db_Project,
			"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, sort_order, note, created_at)
			 VALUES ('" . (int)$request_id . "', 'plan_unit_accepted', '" . (int)$sid . "', 0, 'รับเรื่องเพื่อพิจารณา', NOW())"
		);
		$respondJsonPu(1, 'รับเรื่องเรียบร้อย', ['request_id' => $request_id]);
	}

	if ($op === 'plan_unit_decision') {
		$isAjaxPu = false;
		if (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1') {
			$isAjaxPu = true;
		}
		if (!$isAjaxPu && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			$isAjaxPu = true;
		}
		if ($isAjaxPu) {
			@ini_set('display_errors', '0');
			@ini_set('html_errors', '0');
			if (ob_get_level() === 0) {
				@ob_start();
			}
		}
		$respondJsonPu = function ($ok, $msg, $data = []) use (&$isAjaxPu) {
			$ok = (int)$ok;
			$msg = (string)$msg;
			if (ob_get_level() > 0) {
				@ob_clean();
			}
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], is_array($data) ? $data : []), JSON_UNESCAPED_UNICODE);
			exit;
		};
		if (!$pm_appr_is_plan_admin) {
			$respondJsonPu(0, 'ไม่มีสิทธิ์ดำเนินการในฐานะหน่วยแผนและงบประมาณ');
		}
		$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
		$plan_action = isset($_POST['plan_action']) ? trim((string)$_POST['plan_action']) : '';
		$pu_note = isset($_POST['plan_unit_note']) ? trim((string)$_POST['plan_unit_note']) : '';
		$ysPu = isset($_POST['years_select']) ? trim((string)$_POST['years_select']) : $years_select;
		$pgPu = isset($_POST['project_group']) ? trim((string)$_POST['project_group']) : $project_group;
		if (!in_array($pgPu, ['pending', 'history'], true)) {
			$pgPu = 'pending';
		}
		if ($request_id <= 0) {
			$respondJsonPu(0, 'ไม่พบคำขอ');
		}
		if (!in_array($plan_action, ['include', 'return', 'reject'], true)) {
			$respondJsonPu(0, 'คำสั่งไม่ถูกต้อง');
		}
		if (($plan_action === 'return' || $plan_action === 'reject') && $pu_note === '') {
			$respondJsonPu(0, 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจง');
		}
		$qPuReq = @mysqli_query($db_Project, "SELECT id, status, plan_unit_decision FROM project_include_request WHERE id='" . (int)$request_id . "' LIMIT 1");
		$rowPu = $qPuReq ? mysqli_fetch_assoc($qPuReq) : null;
		if (!$rowPu) {
			$respondJsonPu(0, 'ไม่พบข้อมูลคำขอ');
		}
		$puDecNow = trim((string)($rowPu['plan_unit_decision'] ?? ''));
		if ($puDecNow !== '') {
			$respondJsonPu(0, 'คำขอนี้มีผลการพิจารณาจากหน่วยแผนแล้ว');
		}
		if ((string)($rowPu['status'] ?? '') === 'rejected') {
			$respondJsonPu(0, 'คำขอนี้อยู่ในสถานะตีกลับแล้ว');
		}
		$qUnap = @mysqli_query(
			$db_Project,
			"SELECT 1 AS x
			 FROM project_include_request_certifier c1
			 LEFT JOIN project_include_request_certifier_decision d1
				ON d1.request_id = c1.request_id AND d1.sort_order = c1.sort_order
			 WHERE c1.request_id='" . (int)$request_id . "'
			   AND (d1.decision IS NULL OR d1.decision <> 'approved')
			 LIMIT 1"
		);
		if ($qUnap && mysqli_num_rows($qUnap) > 0) {
			$respondJsonPu(0, 'ยังไม่ครบทุกลำดับผู้รับรอง (ผ่าน)');
		}
		$qCrej = @mysqli_query(
			$db_Project,
			"SELECT 1 AS x FROM project_include_request_certifier_decision d2
			 WHERE d2.request_id='" . (int)$request_id . "' AND d2.decision='rejected' LIMIT 1"
		);
		if ($qCrej && mysqli_num_rows($qCrej) > 0) {
			$respondJsonPu(0, 'คำขอนี้มีการตีกลับจากผู้รับรองในลำดับก่อนหน้า');
		}
		$qHasC = @mysqli_query($db_Project, "SELECT 1 FROM project_include_request_certifier WHERE request_id='" . (int)$request_id . "' LIMIT 1");
		if (!$qHasC || mysqli_num_rows($qHasC) === 0) {
			$respondJsonPu(0, 'ไม่พบผู้รับรองของคำขอนี้');
		}
		$noteEscPu = mysqli_real_escape_string($db_Project, $pu_note);
		$decisionVal = $plan_action === 'include' ? 'included' : ($plan_action === 'return' ? 'returned' : 'rejected');
		$actionLogType = $plan_action === 'include' ? 'plan_unit_included' : ($plan_action === 'return' ? 'plan_unit_returned' : 'plan_unit_rejected');
		$okPu = @mysqli_query(
			$db_Project,
			"UPDATE project_include_request SET
				plan_unit_decision='" . mysqli_real_escape_string($db_Project, $decisionVal) . "',
				plan_unit_note='" . $noteEscPu . "',
				plan_unit_staff_id='" . (int)$sid . "',
				plan_unit_decided_at=NOW(),
				plan_unit_accepted_by=IFNULL(NULLIF(plan_unit_accepted_by,0), '" . (int)$sid . "'),
				plan_unit_accepted_at=IFNULL(plan_unit_accepted_at, NOW()),
				updated_at=NOW()
				" . ($plan_action === 'include' ? '' : ", status='rejected'") . "
			 WHERE id='" . (int)$request_id . "' AND (plan_unit_decision IS NULL OR plan_unit_decision='') LIMIT 1"
		);
		if (!$okPu || mysqli_affected_rows($db_Project) === 0) {
			$respondJsonPu(0, 'บันทึกไม่สำเร็จ: ' . (mysqli_error($db_Project) ?: ''));
		}
		@mysqli_query(
			$db_Project,
			"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, note, created_at)
			 VALUES ('" . (int)$request_id . "', '" . mysqli_real_escape_string($db_Project, $actionLogType) . "', '" . (int)$sid . "', '" . $noteEscPu . "', NOW())"
		);
		if ($plan_action === 'return' || $plan_action === 'reject') {
			$notifyPuIds = [];
			$qSubPu = @mysqli_query($db_Project, "SELECT submitter_staff_id FROM project_include_request WHERE id='" . (int)$request_id . "' LIMIT 1");
			$rSubPu = $qSubPu ? mysqli_fetch_assoc($qSubPu) : null;
			$notifyPuIds[] = (int)($rSubPu['submitter_staff_id'] ?? 0);
			$qItPu = @mysqli_query($db_Project, "SELECT project_id FROM project_include_request_item WHERE request_id='" . (int)$request_id . "'");
			while ($qItPu && ($itPu = mysqli_fetch_assoc($qItPu))) {
				$notifyPuIds[] = function_exists('pm_project_staff_insert_id')
					? pm_project_staff_insert_id($db_Project, (int)($itPu['project_id'] ?? 0))
					: 0;
			}
			$ttlPu = $plan_action === 'return'
				? 'หน่วยแผนตีกลับคำขอบรรจุให้แก้ไข'
				: 'หน่วยแผนไม่อนุมัติคำขอบรรจุโครงการ';
			$bodyPu = 'คำขอ #' . (int)$request_id;
			if ($pu_note !== '') {
				$bodyPu .= "\nข้อแก้ไข: " . $pu_note;
			}
			$linkPu = '?page=project_approve&flow=include&tab=sent&request_id=' . (int)$request_id;
			if (function_exists('pm_notify_revision_needed')) {
				pm_notify_revision_needed(
					$db_Project,
					$notifyPuIds,
					$plan_action === 'return' ? 'include_plan_returned' : 'include_plan_rejected',
					$ttlPu,
					$bodyPu,
					$linkPu,
					'include_request',
					(int)$request_id,
					(int)$sid
				);
			}
			if (function_exists('pm_approve_inbox_clear_read')) {
				pm_approve_inbox_clear_read($db_Project, 'include', 'include:' . (int)$request_id);
			}
		}
		if ($plan_action === 'return') {
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project
				 SET status_project='8', dates=NOW()
				 WHERE status='1'
				   AND status_project IN ('1','3','4')
				   AND id IN (SELECT i.project_id FROM project_include_request_item i WHERE i.request_id='" . (int)$request_id . "')"
			);
			$redirPu = '?page=project_approve&years_select=' . rawurlencode($ysPu) . '&project_group=history&msg=' . rawurlencode('บันทึกตีกลับเพื่อแก้ไข (หน่วยแผน) เรียบร้อย');
			$respondJsonPu(1, 'บันทึกตีกลับเพื่อแก้ไขเรียบร้อย', ['redirect' => $redirPu]);
		}
		if ($plan_action === 'reject') {
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project
				 SET status_project='9', dates=NOW()
				 WHERE status='1'
				   AND status_project IN ('1','3','4')
				   AND id IN (SELECT i.project_id FROM project_include_request_item i WHERE i.request_id='" . (int)$request_id . "')"
			);
			$redirPu = '?page=project_approve&years_select=' . rawurlencode($ysPu) . '&project_group=history&msg=' . rawurlencode('บันทึกไม่อนุมัติ (หน่วยแผน) เรียบร้อย');
			$respondJsonPu(1, 'บันทึกไม่อนุมัติเรียบร้อย', ['redirect' => $redirPu]);
		}
		// หน่วยแผนนำเข้าที่ประชุม → สถานะ 4
		@mysqli_query(
			$db_Project,
			"UPDATE pre_project
			 SET status_project='4', dates=NOW()
			 WHERE status='1'
			   AND status_project IN ('3','4')
			   AND id IN (SELECT i.project_id FROM project_include_request_item i WHERE i.request_id='" . (int)$request_id . "')"
		);
		$redirPu = '?page=project_approve&years_select=' . rawurlencode($ysPu) . '&project_group=' . rawurlencode($pgPu) . '&msg=' . rawurlencode('ขอบรรจุโครงการเรียบร้อย');
		$respondJsonPu(1, 'ขอบรรจุโครงการเรียบร้อย', ['redirect' => $redirPu]);
	}

	$isAjax = false;
	if (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1') $isAjax = true;
	if (!$isAjax && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') $isAjax = true;
	// กัน warning/notice ปน JSON (ทำให้ฝั่ง AJAX อ่านไม่ออก)
	if ($isAjax) {
		@ini_set('display_errors', '0');
		@ini_set('html_errors', '0');
		if (ob_get_level() === 0) {
			@ob_start();
		}
	}
	$ajaxDebug = [];

	$respond = function ($ok, $msg, $redirect = '') use (&$isAjax, &$ajaxDebug) {
		$ok = (int)$ok;
		$msg = (string)$msg;
		$redirect = (string)$redirect;
		if ($isAjax) {
			if (ob_get_level() > 0) { @ob_clean(); }
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['ok' => $ok, 'msg' => $msg, 'redirect' => $redirect, 'debug' => $ajaxDebug], JSON_UNESCAPED_UNICODE);
			exit;
		}
		$url = $redirect !== '' ? $redirect : ('?page=project_approve&err=' . rawurlencode($msg));
		if (!headers_sent()) {
			header('Location: ' . $url);
			exit;
		}
		echo '<script>window.location.href=' . json_encode($url) . ';</script>';
		exit;
	};

	$doRedirect = function ($url) use ($respond) {
		$url = (string)$url;
		$respond(1, '', $url);
	};

	if ($action !== '') {
		if ($op === 'certify_operate_approve' || $op === 'certify_operate_reject') {
			$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
			$certifier_note = isset($_POST['certifier_note']) ? trim((string)$_POST['certifier_note']) : '';
			$post_sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
			if ($project_id <= 0) {
				$respond(0, 'ไม่พบโครงการ');
			}
			if ($action === 'reject' && $certifier_note === '') {
				$respond(0, 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
			}
			if (function_exists('pm_ensure_operate_approver_schema')) {
				pm_ensure_operate_approver_schema($db_Project);
			}
			$qProj = @mysqli_query($db_Project, "SELECT id, staff_insert, code_pre_project, name_project, status_project, operate_certifiers_json, operate_approvers_json, plan_admin_operate_rechain_submitted_at, operate_submitter_reply_note FROM pre_project WHERE id='" . $project_id . "' AND status='1' LIMIT 1");
			$proj = $qProj ? mysqli_fetch_assoc($qProj) : null;
			if (!$proj) $respond(0, 'ไม่พบข้อมูลโครงการ');
			$projStatusNow = (string)($proj['status_project'] ?? '');
			$isOperateStage = ($projStatusNow === '1');
			$isOperateRecheckStage = ($projStatusNow === '8');
			if (!$isOperateStage && !$isOperateRecheckStage) $respond(0, 'โครงการนี้ไม่อยู่สถานะที่สามารถพิจารณาได้');
			$operateSubmitterReply = trim((string)($proj['operate_submitter_reply_note'] ?? ''));
			$certArr = json_decode((string)($proj['operate_certifiers_json'] ?? ''), true);
			if (!is_array($certArr)) {
				$certArr = [];
			}
			// สร้าง certList แบบเดียวกับ get_operate_project_detail (รองรับคนเดียวกันหลายลำดับ)
			$certList = [];
			$seqNo = 0;
			foreach ($certArr as $idx => $c) {
				if (!is_array($c)) {
					continue;
				}
				$seqNo++;
				$so = (int)($c['sort_order'] ?? 0);
				if ($so <= 0) {
					$so = (int)$seqNo;
				}
				$sidC = (int)($c['staff_id'] ?? 0);
				if ($sidC <= 0) {
					continue;
				}
				$certList[$so] = [
					'staff_id' => $sidC,
				];
			}
			$include_rid = isset($_POST['include_request_id']) ? (int)$_POST['include_request_id'] : 0;
			if (count($certList) === 0) {
				$request_id_operate = $include_rid;
				if ($request_id_operate <= 0) {
					$qRid = @mysqli_query(
						$db_Project,
						"SELECT MAX(i.request_id) AS rid FROM project_include_request_item i
						 INNER JOIN project_include_request_certifier c ON c.request_id = i.request_id AND c.staff_id='" . (int)$sid . "'
						 WHERE i.project_id='" . (int)$project_id . "'"
					);
					$rRid = $qRid ? mysqli_fetch_assoc($qRid) : null;
					$request_id_operate = (int)($rRid['rid'] ?? 0);
				}
				if ($request_id_operate > 0) {
					$qIncCert = @mysqli_query(
						$db_Project,
						"SELECT sort_order, staff_id FROM project_include_request_certifier
						 WHERE request_id='" . (int)$request_id_operate . "' ORDER BY sort_order ASC"
					);
					while ($qIncCert && ($cr = mysqli_fetch_assoc($qIncCert))) {
						$so = (int)($cr['sort_order'] ?? 0);
						if ($so <= 0) {
							continue;
						}
						$sidC = (int)($cr['staff_id'] ?? 0);
						if ($sidC <= 0) {
							continue;
						}
						$certList[$so] = [
							'staff_id' => $sidC,
						];
					}
				}
			}
			if (count($certList) === 0) {
				$respond(0, 'ไม่พบลำดับผู้ตรวจสอบของโครงการนี้');
			}
			$allSortOrders = array_keys($certList);
			sort($allSortOrders, SORT_NUMERIC);
			$decBySort = [];
			$qDec = @mysqli_query($db_Project, "SELECT sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at FROM project_operate_certifier_decision WHERE project_id='" . $project_id . "'");
			while ($qDec && ($rd = mysqli_fetch_assoc($qDec))) {
				$so = (int)($rd['sort_order'] ?? 0);
				if ($so > 0) {
					$decBySort[$so] = [
						'decision' => (string)($rd['decision'] ?? ''),
						'certifier_note' => (string)($rd['certifier_note'] ?? ''),
						'decided_at' => (string)($rd['decided_at'] ?? ''),
						'decided_by_staff_id' => (int)($rd['decided_by_staff_id'] ?? 0),
						'staff_id' => (int)($rd['staff_id'] ?? 0),
					];
				}
			}
			$operateDecisionTable = 'project_operate_certifier_decision';
			$operateActIsApprover = false;
			$certsAllApprovedAct = count($allSortOrders) > 0;
			foreach ($allSortOrders as $soCk) {
				if ((string)($decBySort[$soCk]['decision'] ?? '') !== 'approved') {
					$certsAllApprovedAct = false;
					break;
				}
			}
			$approverJsonAct = trim((string)($proj['operate_approvers_json'] ?? ''));
			if ($certsAllApprovedAct && $approverJsonAct !== '' && function_exists('pm_parse_operate_chain_json')) {
				$apprListAct = pm_parse_operate_chain_json($approverJsonAct);
				if (!empty($apprListAct)) {
					$certList = [];
					foreach ($apprListAct as $soA => $rowA) {
						$certList[(int)$soA] = ['staff_id' => (int)($rowA['staff_id'] ?? 0)];
					}
					$allSortOrders = array_keys($certList);
					sort($allSortOrders, SORT_NUMERIC);
					$decBySort = [];
					$qDecA = @mysqli_query($db_Project, "SELECT sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at FROM project_operate_approver_decision WHERE project_id='" . $project_id . "'");
					while ($qDecA && ($rd = mysqli_fetch_assoc($qDecA))) {
						$so = (int)($rd['sort_order'] ?? 0);
						if ($so > 0) {
							$decBySort[$so] = [
								'decision' => (string)($rd['decision'] ?? ''),
								'certifier_note' => (string)($rd['certifier_note'] ?? ''),
								'decided_at' => (string)($rd['decided_at'] ?? ''),
								'decided_by_staff_id' => (int)($rd['decided_by_staff_id'] ?? 0),
								'staff_id' => (int)($rd['staff_id'] ?? 0),
							];
						}
					}
					$operateDecisionTable = 'project_operate_approver_decision';
					$operateActIsApprover = true;
				}
			}
			$myOrders = [];
			foreach ($allSortOrders as $so) {
				if ((int)($certList[$so]['staff_id'] ?? 0) === (int)$sid) {
					$myOrders[] = (int)$so;
				}
			}
			sort($myOrders, SORT_NUMERIC);
			$mySortOrder = 0;
			foreach ($myOrders as $candidateSo) {
				$cDec = (string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '');
				if ($cDec === '') {
					$mySortOrder = (int)$candidateSo;
					break;
				}
			}
			// โหมดพิจารณาซ้ำหลังตีกลับ: เลือกลำดับที่เคย reject ของผู้ตรวจคนนี้
			if ($isOperateRecheckStage && $operateSubmitterReply !== '' && count($myOrders) > 0) {
				foreach ($myOrders as $candidateSo) {
					$cDec = (string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '');
					if ($cDec === 'rejected') {
						$mySortOrder = (int)$candidateSo;
						break;
					}
				}
			}
			if ($mySortOrder <= 0 && count($myOrders) > 0) {
				$mySortOrder = (int)$myOrders[0];
			}
			if ($mySortOrder <= 0) {
				$respond(0, 'ไม่มีสิทธิ์ตรวจสอบโครงการนี้');
			}
			// ถ้าฟอร์มส่ง sort_order มา ต้องตรงกับคิวที่คำนวณ (กันส่งคำขอไม่ตรงสถานะ)
			if ($post_sort_order > 0 && (int)$post_sort_order !== (int)$mySortOrder) {
				$respond(0, 'ลำดับการตรวจสอบไม่ตรงกับคิวปัจจุบัน กรุณารีเฟรชหน้าแล้วลองอีกครั้ง');
			}
			$alreadyDecision = (string)($decBySort[$mySortOrder]['decision'] ?? '');
			$allowRecheckAfterReply = ($isOperateRecheckStage && $operateSubmitterReply !== '' && $alreadyDecision === 'rejected');
			if (!$allowRecheckAfterReply && $alreadyDecision !== '') {
				$respond(0, 'คุณได้บันทึกผลการตรวจสอบโครงการนี้แล้ว');
			}
			if (!$allowRecheckAfterReply) {
				$prevOk = true;
				foreach ($allSortOrders as $prevSo) {
					if ($prevSo >= $mySortOrder) {
						break;
					}
					$prevDec = (string)($decBySort[$prevSo]['decision'] ?? '');
					if ($prevDec !== 'approved') {
						$prevOk = false;
						break;
					}
				}
				if (!$prevOk) {
					$respond(0, 'ยังไม่ถึงคิวของคุณ (รอผู้ตรวจสอบลำดับก่อนหน้า)');
				}
			}
			// ถ้ากำลัง "ผ่านซ้ำ" หลังเคยตีกลับ และยังไม่มี log reject เก่า -> backfill ก่อนอัปเดตทับ
			if ($allowRecheckAfterReply) {
				$qHasRejectLog = @mysqli_query(
					$db_Project,
					"SELECT id FROM project_operate_action_log
					 WHERE project_id='" . (int)$project_id . "'
					   AND sort_order='" . (int)$mySortOrder . "'
					   AND action_type='certifier_rejected'
					 ORDER BY id DESC LIMIT 1"
				);
				$hasRejectLog = $qHasRejectLog && mysqli_num_rows($qHasRejectLog) > 0;
				if (!$hasRejectLog) {
					$prevRow = $decBySort[$mySortOrder] ?? [];
					$prevNoteEsc = mysqli_real_escape_string($db_Project, (string)($prevRow['certifier_note'] ?? ''));
					$prevActor = (int)($prevRow['decided_by_staff_id'] ?? 0);
					if ($prevActor <= 0) {
						$prevActor = (int)($prevRow['staff_id'] ?? 0);
					}
					$prevAt = trim((string)($prevRow['decided_at'] ?? ''));
					$prevAtSql = ($prevAt !== '') ? ("'" . mysqli_real_escape_string($db_Project, $prevAt) . "'") : 'NOW()';
					@mysqli_query(
						$db_Project,
						"INSERT INTO project_operate_action_log (project_id, action_type, actor_staff_id, sort_order, note, created_at)
						 VALUES ('" . (int)$project_id . "', 'certifier_rejected', " . ($prevActor > 0 ? ("'" . $prevActor . "'") : "NULL") . ", '" . (int)$mySortOrder . "', " . ($prevNoteEsc !== '' ? ("'" . $prevNoteEsc . "'") : "NULL") . ", " . $prevAtSql . ")"
					);
				}
			}
			$decision = ($action === 'approve') ? 'approved' : 'rejected';
			$noteEsc = mysqli_real_escape_string($db_Project, $certifier_note);
			$ok = @mysqli_query(
				$db_Project,
				"INSERT INTO `" . $operateDecisionTable . "`
					(project_id, sort_order, staff_id, decision, certifier_note, decided_at, decided_by_staff_id)
				 VALUES
					('" . $project_id . "', '" . $mySortOrder . "', '" . (int)$sid . "', '" . mysqli_real_escape_string($db_Project, $decision) . "', '" . $noteEsc . "', NOW(), '" . (int)$sid . "')
				 ON DUPLICATE KEY UPDATE
					decision=VALUES(decision),
					certifier_note=VALUES(certifier_note),
					decided_at=VALUES(decided_at),
					decided_by_staff_id=VALUES(decided_by_staff_id),
					staff_id=VALUES(staff_id)"
			);
			if (!$ok) $respond(0, 'บันทึกผลไม่สำเร็จ: ' . (mysqli_error($db_Project) ?: ''));
			$logActionType = '';
			if ($decision === 'rejected') {
				$logActionType = 'certifier_rejected';
			} elseif ($allowRecheckAfterReply && $decision === 'approved') {
				$logActionType = 'certifier_recheck_approved';
			} elseif ($decision === 'approved') {
				$logActionType = 'certifier_approved';
			}
			if ($logActionType !== '') {
				$logNoteEsc = mysqli_real_escape_string($db_Project, $certifier_note);
				@mysqli_query(
					$db_Project,
					"INSERT INTO project_operate_action_log (project_id, action_type, actor_staff_id, sort_order, note, created_at)
					 VALUES ('" . (int)$project_id . "', '" . mysqli_real_escape_string($db_Project, $logActionType) . "', '" . (int)$sid . "', '" . (int)$mySortOrder . "', " . ($certifier_note !== '' ? ("'" . $logNoteEsc . "'") : "NULL") . ", NOW())"
				);
			}
			$allApproved = true;
			foreach ($allSortOrders as $so2) {
				$dv = (string)(($so2 === $mySortOrder) ? $decision : ($decBySort[$so2]['decision'] ?? ''));
				if ($dv !== 'approved') { $allApproved = false; break; }
			}
			if ($action === 'reject') {
				@mysqli_query(
					$db_Project,
					"UPDATE pre_project
					 SET status_project='8',
					     dates=NOW(),
					     operate_submitter_reply_note=NULL,
					     operate_submitter_reply_updated_at=NULL,
					     operate_submitter_reply_staff_id=NULL
					 WHERE id='" . $project_id . "' AND status='1' AND status_project='1'
					 LIMIT 1"
				);
				$submitterOp = (int)($proj['staff_insert'] ?? 0);
				$pLabelOp = trim((string)(($proj['code_pre_project'] ?? '') . ' ' . ($proj['name_project'] ?? '')));
				$bodyOp = $pLabelOp !== '' ? $pLabelOp : ('โครงการ #' . (int)$project_id);
				if ($certifier_note !== '') {
					$bodyOp .= "\nข้อแก้ไข: " . $certifier_note;
				}
				$linkOp = '?page=project_approve&flow=operate&tab=sent&open_project_id=' . (int)$project_id . '&project_id=' . (int)$project_id;
				if (function_exists('pm_notify_revision_needed')) {
					pm_notify_revision_needed($db_Project, [$submitterOp], 'operate_rejected', 'ขอดำเนินการโครงการถูกตีกลับให้แก้ไข', $bodyOp, $linkOp, 'pre_project', (int)$project_id, (int)$sid);
				} elseif ($submitterOp > 0 && $submitterOp !== (int)$sid && function_exists('pm_staff_notify')) {
					pm_staff_notify($db_Project, $submitterOp, 'operate_rejected', 'ขอดำเนินการโครงการถูกตีกลับให้แก้ไข', $bodyOp, $linkOp, 'pre_project', (int)$project_id);
				}
				if (function_exists('pm_approve_inbox_clear_read')) {
					pm_approve_inbox_clear_read($db_Project, 'operate', 'operate:' . (int)$project_id);
				}
				$respond(1, 'บันทึกตีกลับเรียบร้อย');
			}
			if ($allApproved) {
				if ($projStatusNow === '8') {
					@mysqli_query(
						$db_Project,
						"UPDATE pre_project SET status_project='1', dates=NOW() WHERE id='" . (int)$project_id . "' AND status='1' AND status_project='8' LIMIT 1"
					);
				}
				$rechainAtAct = trim((string)($proj['plan_admin_operate_rechain_submitted_at'] ?? ''));
				if (!empty($operateActIsApprover) || $rechainAtAct !== '') {
					$respond(1, 'อนุมัติเรียบร้อย (ครบทุกลำดับแล้ว)');
				}
				$respond(1, 'บันทึกอนุมัติเรียบร้อย รอหน่วยแผนเลือกผู้อนุมัติ');
			}
			$respond(1, 'บันทึกอนุมัติเรียบร้อย รอผู้ตรวจสอบลำดับถัดไป');
		}

		if ($op === 'certify_report_approve' || $op === 'certify_report_reject') {
			if (function_exists('ensureProjectResultReportSchema')) {
				ensureProjectResultReportSchema($db_Project);
			}
			$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
			$certifier_note = isset($_POST['certifier_note']) ? trim((string)$_POST['certifier_note']) : '';
			$post_sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
			if ($project_id <= 0) {
				$respond(0, 'ไม่พบโครงการ');
			}
			if ($action === 'reject' && $certifier_note === '') {
				$respond(0, 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
			}
			$qProj = @mysqli_query($db_Project, "SELECT id, staff_insert, code_pre_project, name_project, result_report_certifiers_json, result_report_submitted_at, result_report_completed_at, result_report_plan_closed_at FROM pre_project WHERE id='" . $project_id . "' AND status='1' LIMIT 1");
			$proj = $qProj ? mysqli_fetch_assoc($qProj) : null;
			if (!$proj) {
				$respond(0, 'ไม่พบข้อมูลโครงการ');
			}
			if (trim((string)($proj['result_report_submitted_at'] ?? '')) === '') {
				$respond(0, 'โครงการนี้ยังไม่ได้ส่งรายงานผล');
			}
			if (trim((string)($proj['result_report_plan_closed_at'] ?? '')) !== '') {
				$respond(0, 'รายงานผลโครงการนี้ปิดแล้ว');
			}
			if (trim((string)($proj['result_report_completed_at'] ?? '')) !== '') {
				$respond(0, 'ผู้ตรวจสอบหน่วยงานอนุมัติครบแล้ว รอหน่วยแผนปิดโครงการ');
			}
			$certArr = json_decode((string)($proj['result_report_certifiers_json'] ?? ''), true);
			if (!is_array($certArr)) {
				$certArr = [];
			}
			$certList = [];
			$seqNo = 0;
			foreach ($certArr as $c) {
				if (!is_array($c)) {
					continue;
				}
				$seqNo++;
				$so = (int)($c['sort_order'] ?? 0);
				if ($so <= 0) {
					$so = $seqNo;
				}
				$sidC = (int)($c['staff_id'] ?? 0);
				if ($sidC <= 0) {
					continue;
				}
				$certList[$so] = ['staff_id' => $sidC];
			}
			if (count($certList) === 0) {
				$respond(0, 'ไม่พบลำดับผู้ตรวจสอบรายงานผล');
			}
			$allSortOrders = array_keys($certList);
			sort($allSortOrders, SORT_NUMERIC);
			$decBySort = [];
			$qDec = @mysqli_query($db_Project, "SELECT sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at FROM project_result_report_certifier_decision WHERE project_id='" . $project_id . "'");
			while ($qDec && ($rd = mysqli_fetch_assoc($qDec))) {
				$so = (int)($rd['sort_order'] ?? 0);
				if ($so > 0) {
					$decBySort[$so] = $rd;
				}
			}
			$myOrders = [];
			foreach ($allSortOrders as $so) {
				if ((int)($certList[$so]['staff_id'] ?? 0) === (int)$sid) {
					$myOrders[] = (int)$so;
				}
			}
			sort($myOrders, SORT_NUMERIC);
			$mySortOrder = 0;
			foreach ($myOrders as $candidateSo) {
				if ((string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '') === '') {
					$mySortOrder = (int)$candidateSo;
					break;
				}
			}
			if ($mySortOrder <= 0 && count($myOrders) > 0) {
				$mySortOrder = (int)$myOrders[0];
			}
			if ($mySortOrder <= 0) {
				$respond(0, 'ไม่มีสิทธิ์ตรวจสอบรายงานผลโครงการนี้');
			}
			if ($post_sort_order > 0 && (int)$post_sort_order !== (int)$mySortOrder) {
				$respond(0, 'ลำดับการตรวจสอบไม่ตรงกับคิวปัจจุบัน กรุณารีเฟรชหน้าแล้วลองอีกครั้ง');
			}
			$alreadyDecision = (string)($decBySort[$mySortOrder]['decision'] ?? '');
			if ($alreadyDecision !== '') {
				$respond(0, 'คุณได้บันทึกผลการตรวจสอบรายงานผลนี้แล้ว');
			}
			$prevOk = true;
			foreach ($allSortOrders as $prevSo) {
				if ($prevSo >= $mySortOrder) {
					break;
				}
				if ((string)($decBySort[$prevSo]['decision'] ?? '') !== 'approved') {
					$prevOk = false;
					break;
				}
			}
			if (!$prevOk) {
				$respond(0, 'ยังไม่ถึงคิวของคุณ (รอผู้ตรวจสอบลำดับก่อนหน้า)');
			}
			$decision = ($action === 'approve') ? 'approved' : 'rejected';
			$noteEsc = mysqli_real_escape_string($db_Project, $certifier_note);
			$ok = @mysqli_query(
				$db_Project,
				"INSERT INTO project_result_report_certifier_decision
					(project_id, sort_order, staff_id, decision, certifier_note, decided_at, decided_by_staff_id)
				 VALUES
					('" . $project_id . "', '" . $mySortOrder . "', '" . (int)$sid . "', '" . mysqli_real_escape_string($db_Project, $decision) . "', '" . $noteEsc . "', NOW(), '" . (int)$sid . "')
				 ON DUPLICATE KEY UPDATE
					decision=VALUES(decision),
					certifier_note=VALUES(certifier_note),
					decided_at=VALUES(decided_at),
					decided_by_staff_id=VALUES(decided_by_staff_id),
					staff_id=VALUES(staff_id)"
			);
			if (!$ok) {
				$respond(0, 'บันทึกผลไม่สำเร็จ: ' . (mysqli_error($db_Project) ?: ''));
			}
			if ($action === 'reject') {
				$submitter = (int)($proj['staff_insert'] ?? 0);
				$pLabel = trim((string)(($proj['code_pre_project'] ?? '') . ' ' . ($proj['name_project'] ?? '')));
				$bodyRep = $pLabel !== '' ? $pLabel : ('โครงการ #' . (int)$project_id);
				if ($certifier_note !== '') {
					$bodyRep .= "\nข้อแก้ไข: " . $certifier_note;
				}
				$linkRep = '?page=project_approve&flow=report&tab=sent&open_project_id=' . (int)$project_id . '&project_id=' . (int)$project_id;
				if (function_exists('pm_notify_revision_needed')) {
					pm_notify_revision_needed($db_Project, [$submitter], 'report_rejected', 'รายงานผลโครงการถูกตีกลับให้แก้ไข', $bodyRep, $linkRep, 'pre_project', (int)$project_id, (int)$sid);
				} elseif ($submitter > 0 && $submitter !== (int)$sid && function_exists('pm_staff_notify')) {
					pm_staff_notify($db_Project, $submitter, 'report_rejected', 'รายงานผลโครงการถูกตีกลับให้แก้ไข', $bodyRep, $linkRep, 'pre_project', (int)$project_id);
				}
				if (function_exists('pm_approve_inbox_clear_read')) {
					pm_approve_inbox_clear_read($db_Project, 'report', 'report:' . (int)$project_id);
				}
				$respond(1, 'บันทึกตีกลับรายงานผลเรียบร้อย — ผู้รายงานสามารถแก้ไขและส่งใหม่ได้');
			}
			$allApproved = true;
			foreach ($allSortOrders as $so2) {
				$dv = (string)(($so2 === $mySortOrder) ? $decision : ($decBySort[$so2]['decision'] ?? ''));
				if ($dv !== 'approved') {
					$allApproved = false;
					break;
				}
			}
			if ($allApproved) {
				@mysqli_query($db_Project, "UPDATE pre_project SET result_report_completed_at=NOW(), dates=NOW() WHERE id='" . (int)$project_id . "' AND status='1' LIMIT 1");
				if (function_exists('pm_approve_inbox_clear_read')) {
					pm_approve_inbox_clear_read($db_Project, 'report', 'report:' . (int)$project_id);
				}
				$respond(1, 'ตรวจสอบรายงานผลเรียบร้อย — รอหน่วยแผนปิดโครงการ');
			}
			$respond(1, 'บันทึกอนุมัติรายงานผลเรียบร้อย รอผู้ตรวจสอบลำดับถัดไป');
		}

		if ($op === 'plan_admin_report_approve' || $op === 'plan_admin_report_reject') {
			if (!$pm_appr_is_plan_admin) {
				$respond(0, 'เฉพาะหน่วยแผนเท่านั้นที่ปิดโครงการได้');
			}
			if (function_exists('ensureProjectResultReportSchema')) {
				ensureProjectResultReportSchema($db_Project);
			}
			$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
			if ($project_id <= 0 && isset($_POST['request_id'])) {
				$project_id = (int)$_POST['request_id'];
			}
			$certifier_note = isset($_POST['certifier_note']) ? trim((string)$_POST['certifier_note']) : '';
			if ($project_id <= 0) {
				$respond(0, 'ไม่พบโครงการ');
			}
			if ($action === 'reject' && $certifier_note === '') {
				$respond(0, 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
			}
			$qProj = @mysqli_query(
				$db_Project,
				"SELECT id, staff_insert, code_pre_project, name_project, result_report_submitted_at, result_report_completed_at, result_report_plan_closed_at
				 FROM pre_project WHERE id='" . $project_id . "' AND status='1' LIMIT 1"
			);
			$proj = $qProj ? mysqli_fetch_assoc($qProj) : null;
			if (!$proj) {
				$respond(0, 'ไม่พบข้อมูลโครงการ');
			}
			if (trim((string)($proj['result_report_submitted_at'] ?? '')) === '') {
				$respond(0, 'โครงการนี้ยังไม่ได้ส่งรายงานผล');
			}
			if (trim((string)($proj['result_report_plan_closed_at'] ?? '')) !== '') {
				$respond(0, 'โครงการนี้ปิดแล้ว');
			}
			if (trim((string)($proj['result_report_completed_at'] ?? '')) === '') {
				$respond(0, 'ยังรอผู้ตรวจสอบหน่วยงานอนุมัติให้ครบก่อน');
			}
			$pLabel = trim((string)(($proj['code_pre_project'] ?? '') . ' ' . ($proj['name_project'] ?? '')));
			$bodyRep = $pLabel !== '' ? $pLabel : ('โครงการ #' . (int)$project_id);
			$submitter = (int)($proj['staff_insert'] ?? 0);
			$noteEsc = mysqli_real_escape_string($db_Project, $certifier_note);
			if ($action === 'reject') {
				@mysqli_query($db_Project, "DELETE FROM project_result_report_certifier_decision WHERE project_id='" . (int)$project_id . "'");
				@mysqli_query(
					$db_Project,
					"INSERT INTO project_result_report_certifier_decision
						(project_id, sort_order, staff_id, decision, certifier_note, decided_at, decided_by_staff_id)
					 VALUES
						('" . (int)$project_id . "', 1, '" . (int)$sid . "', 'rejected', '" . $noteEsc . "', NOW(), '" . (int)$sid . "')"
				);
				@mysqli_query(
					$db_Project,
					"UPDATE pre_project SET
						result_report_completed_at=NULL,
						result_report_plan_closed_at=NULL,
						result_report_plan_closed_by=NULL,
						result_report_plan_note='" . $noteEsc . "',
						dates=NOW()
					 WHERE id='" . (int)$project_id . "' AND status='1' LIMIT 1"
				);
				if ($certifier_note !== '') {
					$bodyRep .= "\nข้อแก้ไข: " . $certifier_note;
				}
				$linkRep = '?page=project_approve&flow=report&tab=sent&open_project_id=' . (int)$project_id . '&project_id=' . (int)$project_id;
				if (function_exists('pm_notify_revision_needed')) {
					pm_notify_revision_needed($db_Project, [$submitter], 'report_plan_returned', 'หน่วยแผนตีกลับรายงานผลให้แก้ไข', $bodyRep, $linkRep, 'pre_project', (int)$project_id, (int)$sid);
				}
				if (function_exists('pm_approve_inbox_clear_read')) {
					pm_approve_inbox_clear_read($db_Project, 'report', 'report:' . (int)$project_id);
				}
				$respond(1, 'ตีกลับรายงานผลเรียบร้อย — ผู้ยื่นสามารถแก้ไขและส่งใหม่ได้');
			}
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project SET
					result_report_plan_closed_at=NOW(),
					result_report_plan_closed_by='" . (int)$sid . "',
					result_report_plan_note='" . $noteEsc . "',
					dates=NOW()
				 WHERE id='" . (int)$project_id . "' AND status='1' LIMIT 1"
			);
			$linkClose = '?page=project_manage&op=add_project&project_id=' . (int)$project_id;
			if (function_exists('pm_notify_revision_needed')) {
				pm_notify_revision_needed($db_Project, [$submitter], 'report_plan_closed', 'หน่วยแผนปิดโครงการแล้ว', $bodyRep, $linkClose, 'pre_project', (int)$project_id, (int)$sid);
			}
			$respond(1, 'ปิดโครงการเรียบร้อย');
		}

		if ($op === 'certify_change_approve' || $op === 'certify_change_reject') {
			if (function_exists('pm_ensure_change_request_schema')) {
				pm_ensure_change_request_schema($db_Project);
			}
			$request_id = isset($_POST['change_request_id']) ? (int)$_POST['change_request_id'] : 0;
			if ($request_id <= 0) {
				$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
			}
			$certifier_note = isset($_POST['certifier_note']) ? trim((string)$_POST['certifier_note']) : '';
			$post_sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
			if ($request_id <= 0) {
				$respond(0, 'ไม่พบคำขอเปลี่ยนแปลง');
			}
			if ($action === 'reject' && $certifier_note === '') {
				$respond(0, 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
			}
			$qReq = @mysqli_query($db_Project, "SELECT * FROM project_change_request WHERE id='" . (int)$request_id . "' AND status='1' LIMIT 1");
			$req = $qReq ? mysqli_fetch_assoc($qReq) : null;
			if (!$req) {
				$respond(0, 'ไม่พบคำขอเปลี่ยนแปลง');
			}
			$reqStatus = trim((string)($req['req_status'] ?? ''));
			if (!in_array($reqStatus, ['pending', 'pending_approve'], true)) {
				$respond(0, 'คำขอนี้ไม่อยู่ในสถานะที่สามารถพิจารณาได้');
			}
			$useApprover = ($reqStatus === 'pending_approve');
			$chainJson = $useApprover ? (string)($req['approvers_json'] ?? '') : (string)($req['certifiers_json'] ?? '');
			$chainTable = $useApprover ? 'project_change_approver_decision' : 'project_change_certifier_decision';
			$certArr = json_decode($chainJson, true);
			if (!is_array($certArr)) {
				$certArr = [];
			}
			$certList = [];
			$seqNo = 0;
			foreach ($certArr as $c) {
				if (!is_array($c)) {
					continue;
				}
				$seqNo++;
				$so = (int)($c['sort_order'] ?? 0);
				if ($so <= 0) {
					$so = $seqNo;
				}
				$sidC = (int)($c['staff_id'] ?? 0);
				if ($sidC <= 0) {
					continue;
				}
				$certList[$so] = ['staff_id' => $sidC];
			}
			if (count($certList) === 0) {
				$respond(0, 'ไม่พบลำดับผู้พิจารณา');
			}
			$allSortOrders = array_keys($certList);
			sort($allSortOrders, SORT_NUMERIC);
			$decBySort = [];
			$qDec = @mysqli_query($db_Project, "SELECT sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at FROM `" . $chainTable . "` WHERE request_id='" . (int)$request_id . "'");
			while ($qDec && ($rd = mysqli_fetch_assoc($qDec))) {
				$so = (int)($rd['sort_order'] ?? 0);
				if ($so > 0) {
					$decBySort[$so] = $rd;
				}
			}
			$myOrders = [];
			foreach ($allSortOrders as $so) {
				if ((int)($certList[$so]['staff_id'] ?? 0) === (int)$sid) {
					$myOrders[] = (int)$so;
				}
			}
			sort($myOrders, SORT_NUMERIC);
			$mySortOrder = 0;
			foreach ($myOrders as $candidateSo) {
				if ((string)(($decBySort[$candidateSo]['decision'] ?? '') ?: '') === '') {
					$mySortOrder = (int)$candidateSo;
					break;
				}
			}
			if ($mySortOrder <= 0 && count($myOrders) > 0) {
				$mySortOrder = (int)$myOrders[0];
			}
			if ($mySortOrder <= 0) {
				$respond(0, 'ไม่มีสิทธิ์พิจารณาคำขอนี้');
			}
			if ($post_sort_order > 0 && (int)$post_sort_order !== (int)$mySortOrder) {
				$respond(0, 'ลำดับการตรวจสอบไม่ตรงกับคิวปัจจุบัน กรุณารีเฟรชหน้าแล้วลองอีกครั้ง');
			}
			$alreadyDecision = (string)($decBySort[$mySortOrder]['decision'] ?? '');
			if ($alreadyDecision !== '') {
				$respond(0, 'คุณได้บันทึกผลการพิจารณานี้แล้ว');
			}
			$prevOk = true;
			foreach ($allSortOrders as $prevSo) {
				if ($prevSo >= $mySortOrder) {
					break;
				}
				if ((string)($decBySort[$prevSo]['decision'] ?? '') !== 'approved') {
					$prevOk = false;
					break;
				}
			}
			if (!$prevOk) {
				$respond(0, 'ยังไม่ถึงคิวของคุณ (รอผู้พิจารณาลำดับก่อนหน้า)');
			}
			$decision = ($action === 'approve') ? 'approved' : 'rejected';
			$noteEsc = mysqli_real_escape_string($db_Project, $certifier_note);
			$pid = (int)($req['project_id'] ?? 0);
			$ok = @mysqli_query(
				$db_Project,
				"INSERT INTO `" . $chainTable . "`
					(request_id, project_id, sort_order, staff_id, decision, certifier_note, decided_at, decided_by_staff_id)
				 VALUES
					('" . (int)$request_id . "', '" . (int)$pid . "', '" . (int)$mySortOrder . "', '" . (int)$sid . "', '" . mysqli_real_escape_string($db_Project, $decision) . "', '" . $noteEsc . "', NOW(), '" . (int)$sid . "')
				 ON DUPLICATE KEY UPDATE
					decision=VALUES(decision),
					certifier_note=VALUES(certifier_note),
					decided_at=VALUES(decided_at),
					decided_by_staff_id=VALUES(decided_by_staff_id),
					staff_id=VALUES(staff_id)"
			);
			if (!$ok) {
				$respond(0, 'บันทึกผลไม่สำเร็จ: ' . (mysqli_error($db_Project) ?: ''));
			}
			$pName = trim((string)($req['project_name'] ?? ''));
			$pCode = trim((string)($req['project_code'] ?? ''));
			$link = '?page=project_approve&flow=change&tab=sent&open_flow=change&open_project_id=' . (int)$pid . '&change_request_id=' . (int)$request_id . '&project_id=' . (int)$pid;
			if ($action === 'reject') {
				@mysqli_query($db_Project, "UPDATE project_change_request SET req_status='rejected', updated_at=NOW() WHERE id='" . (int)$request_id . "' LIMIT 1");
				$submitter = (int)($req['staff_insert'] ?? 0);
				if ($submitter > 0 && function_exists('pm_staff_notify')) {
					pm_staff_notify($db_Project, $submitter, 'change_request', 'คำขอเปลี่ยนแปลงโครงการถูกตีกลับ', ($pCode !== '' ? $pCode . ' ' : '') . $pName, $link, 'project_change', $request_id);
				}
				if (function_exists('pm_approve_inbox_clear_read')) {
					pm_approve_inbox_clear_read($db_Project, 'change', 'change:' . (int)$request_id);
				}
				$respond(1, 'บันทึกตีกลับเรียบร้อย');
			}
			$allApproved = true;
			$nextSid = 0;
			foreach ($allSortOrders as $so2) {
				$dv = (string)(($so2 === $mySortOrder) ? $decision : ($decBySort[$so2]['decision'] ?? ''));
				if ($dv !== 'approved') {
					$allApproved = false;
					if ($dv === '' && $nextSid <= 0) {
						$nextSid = (int)($certList[$so2]['staff_id'] ?? 0);
					}
					break;
				}
			}
			if ($allApproved) {
				if ($useApprover) {
					@mysqli_query($db_Project, "UPDATE project_change_request SET req_status='approved', updated_at=NOW() WHERE id='" . (int)$request_id . "' LIMIT 1");
					$submitter = (int)($req['staff_insert'] ?? 0);
					$linkManage = '?page=project_manage&op=add_project&project_id=' . (int)$pid . '&pm_scroll=pmChangeHistory';
					if ($submitter > 0 && function_exists('pm_staff_notify')) {
						pm_staff_notify($db_Project, $submitter, 'change_request', 'คำขอเปลี่ยนแปลงโครงการได้รับอนุมัติ รอหน่วยแผนแก้ไขโครงการ', ($pCode !== '' ? $pCode . ' ' : '') . $pName, $linkManage, 'project_change', $request_id);
					}
					$planStaff = (int)($req['plan_rechain_staff_id'] ?? 0);
					if ($planStaff > 0 && $planStaff !== $submitter && function_exists('pm_staff_notify')) {
						pm_staff_notify($db_Project, $planStaff, 'change_request', 'คำขอเปลี่ยนแปลงได้รับอนุมัติ กรุณาแก้ไขโครงการ', ($pCode !== '' ? $pCode . ' ' : '') . $pName, $linkManage, 'project_change', $request_id);
					}
					$respond(1, 'อนุมัติเรียบร้อย — หน่วยแผนจะเป็นผู้แก้ไขโครงการ');
				}
				@mysqli_query($db_Project, "UPDATE project_change_request SET req_status='waiting_plan', updated_at=NOW() WHERE id='" . (int)$request_id . "' LIMIT 1");
				$respond(1, 'บันทึกอนุมัติเรียบร้อย รอหน่วยแผนเลือกผู้อนุมัติ');
			}
			if ($nextSid > 0 && function_exists('pm_staff_notify')) {
				$ttl = $useApprover ? 'มีคำขอเปลี่ยนแปลงโครงการรออนุมัติ' : 'มีคำขอเปลี่ยนแปลงโครงการรอพิจารณา';
				pm_staff_notify($db_Project, $nextSid, 'change_request', $ttl, ($pCode !== '' ? $pCode . ' ' : '') . $pName, $link, 'project_change', $request_id);
			}
			$respond(1, 'บันทึกอนุมัติเรียบร้อย รอผู้พิจารณาลำดับถัดไป');
		}

		$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : (isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0);
		$certifier_note = isset($_POST['certifier_note']) ? trim((string)$_POST['certifier_note']) : '';
		$post_sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
		$ajaxDebug = [
			'op' => $op,
			'action' => $action,
			'request_id' => $request_id,
			'post_sort_order' => $post_sort_order,
			'note_len' => strlen((string)$certifier_note),
		];
		// ให้ redirect กลับปี/กลุ่มเดิมจากฟอร์มใน modal
		if (isset($_POST['years_select'])) {
			$ys = trim((string)$_POST['years_select']);
			if ($ys !== '') {
				$years_select = $ys;
			}
		}
		if (isset($_POST['project_group'])) {
			$pg = trim((string)$_POST['project_group']);
			if ($pg === 'pending') {
				$pg = 'draft';
			} elseif ($pg === 'history') {
				$pg = 'plan';
			}
			if (in_array($pg, ['draft', 'plan'], true)) {
				$project_group = $pg;
			}
		}
		if ($request_id <= 0) {
			$redir = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=' . rawurlencode($project_group) . '&err=' . rawurlencode('ไม่พบคำขอที่ต้องการ');
			$respond(0, 'ไม่พบคำขอที่ต้องการ', $redir);
		}

		$qStReq = @mysqli_query($db_Project, "SELECT status FROM project_include_request WHERE id='" . (int)$request_id . "' LIMIT 1");
		$reqStRow = $qStReq ? mysqli_fetch_assoc($qStReq) : null;
		$reqRowStatus = (string)($reqStRow['status'] ?? '');
		$ajaxDebug['request_status'] = $reqRowStatus;

		$back = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=' . rawurlencode($project_group) . '&request_id=' . (int)$request_id;

		$returnProjectNotesPosted = [];
		if ($action === 'reject') {
			$rawNotesPosted = $_POST['return_project_notes'] ?? [];
			if (is_array($rawNotesPosted)) {
				foreach ($rawNotesPosted as $__nid => $__nval) {
					$__nid = (int)$__nid;
					$__nval = trim((string)$__nval);
					if ($__nid > 0 && $__nval !== '') {
						$returnProjectNotesPosted[$__nid] = $__nval;
					}
				}
			}
			if ($certifier_note === '' && !empty($returnProjectNotesPosted)) {
				$certifier_note = (string)reset($returnProjectNotesPosted);
			}
		}
		// บังคับ: ตีกลับต้องกรอกข้อเสนอแนะ
		if ($action === 'reject' && $certifier_note === '') {
			$respond(0, 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ', $back . '&err=' . rawurlencode('กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ'));
		}
		// บังคับ: ตีกลับต้องเลือกโครงการอย่างน้อย 1 รายการ
		$rejectSelectedProjectIds = [];
		if ($action === 'reject') {
			if (function_exists('ensureIncludeRequestTables')) {
				ensureIncludeRequestTables($db_Project);
			}
			$rawIdsEarly = $_POST['return_project_ids'] ?? [];
			if (!is_array($rawIdsEarly)) {
				$rawIdsEarly = preg_split('/\s*,\s*/', (string)$rawIdsEarly);
			}
			$allowedEarly = [];
			$qAllEarly = @mysqli_query(
				$db_Project,
				"SELECT project_id FROM project_include_request_item WHERE request_id='" . (int)$request_id . "'"
			);
			while ($qAllEarly && ($ae = mysqli_fetch_assoc($qAllEarly))) {
				$pidE = (int)($ae['project_id'] ?? 0);
				if ($pidE > 0) {
					$allowedEarly[$pidE] = $pidE;
				}
			}
			foreach ($rawIdsEarly as $__pidE) {
				$__pidE = (int)$__pidE;
				if ($__pidE > 0 && isset($allowedEarly[$__pidE])) {
					$rejectSelectedProjectIds[$__pidE] = $__pidE;
				}
			}
			if (empty($rejectSelectedProjectIds) && !empty($allowedEarly)) {
				$rejectSelectedProjectIds = $allowedEarly;
			}
			if (empty($rejectSelectedProjectIds)) {
				$respond(0, 'กรุณาเลือกโครงการที่ต้องการตีกลับอย่างน้อย 1 รายการ', $back . '&err=' . rawurlencode('กรุณาเลือกโครงการที่ต้องการตีกลับ'));
			}
		}

		// ตรวจสิทธิ์: ต้องเป็นผู้รับรองในคำขอนี้เท่านั้น
		// รองรับกรณีคนเดียวกันอยู่หลายลำดับ: ใช้ sort_order จาก modal ถ้ามี
		if ($post_sort_order > 0) {
			$stMyCert = mysqli_prepare(
				$db_Project,
				"SELECT sort_order, position_title
				 FROM project_include_request_certifier
				 WHERE request_id = ? AND staff_id = ? AND sort_order = ?
				 LIMIT 1"
			);
		} else {
			$stMyCert = mysqli_prepare(
				$db_Project,
				"SELECT sort_order, position_title
				 FROM project_include_request_certifier
				 WHERE request_id = ? AND staff_id = ?
				 ORDER BY sort_order ASC
				 LIMIT 1"
			);
		}
		if (!$stMyCert) {
			$respond(0, 'เตรียมคำสั่งไม่สำเร็จ', $back . '&err=' . rawurlencode('เตรียมคำสั่งไม่สำเร็จ'));
		}
		if ($post_sort_order > 0) {
			mysqli_stmt_bind_param($stMyCert, 'iii', $request_id, $sid, $post_sort_order);
		} else {
			mysqli_stmt_bind_param($stMyCert, 'ii', $request_id, $sid);
		}
		mysqli_stmt_execute($stMyCert);
		$myCert = mysqli_stmt_get_result($stMyCert);
		$myCertRow = $myCert ? mysqli_fetch_assoc($myCert) : null;
		if (!$myCertRow) {
			$respond(0, 'ไม่มีสิทธิ์อนุมัติคำขอนี้', $back . '&err=' . rawurlencode('ไม่มีสิทธิ์อนุมัติคำขอนี้'));
		}

		$mySortOrder = (int)($myCertRow['sort_order'] ?? 0);
		if ($mySortOrder <= 0) {
			$respond(0, 'ไม่พบลำดับผู้รับรองของคุณ', $back . '&err=' . rawurlencode('ไม่พบลำดับผู้รับรองของคุณ'));
		}
		$ajaxDebug['my_sort_order'] = $mySortOrder;

		// เช็คความพร้อมของ pipeline (ไม่บล็อคการ "บันทึกผล" เพื่อให้กดผ่าน/ตีกลับแล้วเก็บลงฐานข้อมูลได้)
		$pendingCheckSql = "
			SELECT
				SUM(CASE WHEN p.status_project='4' THEN 1 ELSE 0 END) AS cnt4,
				COUNT(*) AS cntAll
			FROM pre_project p
			WHERE p.status='1'
			  AND p.id IN (
					SELECT i.project_id
					FROM project_include_request_item i
					WHERE i.request_id='" . (int)$request_id . "'
			  )
		";
		$qPending = @mysqli_query($db_Project, $pendingCheckSql);
		$pendingRow = $qPending ? mysqli_fetch_assoc($qPending) : null;
		$cnt4 = (int)($pendingRow['cnt4'] ?? 0);
		$cntAll = (int)($pendingRow['cntAll'] ?? 0);
		if ($cntAll <= 0) {
			$respond(0, 'ไม่พบโครงการในคำขอนี้', $back . '&err=' . rawurlencode('ไม่พบโครงการในคำขอนี้'));
		}
		$projects_pipeline_ok = ($cnt4 === $cntAll);

		// ดึงลิสต์ผู้รับรองทั้งหมด (เพื่อเช็คคิวตาม sort_order)
		$certSql = "
			SELECT sort_order, staff_id
			FROM project_include_request_certifier
			WHERE request_id='" . (int)$request_id . "'
			ORDER BY sort_order ASC
		";
		$certRes = @mysqli_query($db_Project, $certSql);
		$certList = [];
		$allSortOrders = [];
		while ($certRes && $r = mysqli_fetch_assoc($certRes)) {
			$so = (int)($r['sort_order'] ?? 0);
			if ($so <= 0) continue;
			$certList[$so] = (int)($r['staff_id'] ?? 0);
			$allSortOrders[] = $so;
		}
		$totalCert = count($allSortOrders);
		if ($totalCert <= 0) {
			$respond(0, 'ไม่พบผู้รับรองของคำขอนี้', $back . '&err=' . rawurlencode('ไม่พบผู้รับรองของคำขอนี้'));
		}

		// ดึง decisions ที่มีอยู่แล้ว
		$decisionsSql = "
			SELECT sort_order, decision
			FROM project_include_request_certifier_decision
			WHERE request_id='" . (int)$request_id . "'
		";
		$decRes = @mysqli_query($db_Project, $decisionsSql);
		$decisionsBySort = [];
		while ($decRes && $d = mysqli_fetch_assoc($decRes)) {
			$so = (int)($d['sort_order'] ?? 0);
			if ($so <= 0) continue;
			$decisionsBySort[$so] = (string)($d['decision'] ?? '');
		}

		$alreadyDecision = isset($decisionsBySort[$mySortOrder]) ? (string)$decisionsBySort[$mySortOrder] : '';
		// โหมดพิจารณาซ้ำหลังตีกลับ: Admin ส่งยืนยันการแก้ไขแล้ว ให้คนที่ตีกลับพิจารณาใหม่ได้
		$allowRecheckAfterReply = false;
		$srNoteNow = '';
		$adminFixSentAt = '';
		if ($reqRowStatus === 'rejected') {
			$qSr = @mysqli_query($db_Project, "SELECT submitter_reply_note, IFNULL(admin_fix_sent_at,'') AS admin_fix_sent_at FROM project_include_request WHERE id='" . (int)$request_id . "' LIMIT 1");
			$srRow = $qSr ? mysqli_fetch_assoc($qSr) : null;
			$srNoteNow = trim((string)($srRow['submitter_reply_note'] ?? ''));
			$adminFixSentAt = trim((string)($srRow['admin_fix_sent_at'] ?? ''));
			$hasPerProjectFix = function_exists('pm_include_all_returned_fixes_confirmed')
				&& pm_include_all_returned_fixes_confirmed($db_Project, $request_id)
				&& $adminFixSentAt !== '';
			if (($hasPerProjectFix || $srNoteNow !== '') && $adminFixSentAt !== '' && $alreadyDecision === 'rejected') {
				$allowRecheckAfterReply = true;
			} elseif ($srNoteNow !== '' && $alreadyDecision === 'rejected' && $adminFixSentAt === '' && !$hasPerProjectFix) {
				// legacy: คำชี้แจงระดับคำขอโดยไม่มี admin_fix_sent
				$allowRecheckAfterReply = true;
			} else {
				$redir = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=' . rawurlencode($project_group) . '&err=' . rawurlencode('คำขอนี้ถูกตีกลับแล้ว');
				$respond(0, 'คำขอนี้ถูกตีกลับแล้ว ไม่สามารถบันทึกผลการรับรองได้', $redir);
			}
		}
		if (!$allowRecheckAfterReply && $alreadyDecision !== '') {
			$respond(0, 'คุณได้ทำการรับรอง/ไม่รับรองคำขอนี้แล้ว', $back . '&err=' . rawurlencode('คุณได้ทำการรับรอง/ไม่รับรองคำขอนี้แล้ว'));
		}

		// เช็คคิว: ต้องเป็นลำดับถัดไปเท่านั้น (ก่อนหน้าต้องอนุมัติทั้งหมดแล้ว)
		// ยกเว้นโหมดพิจารณาซ้ำหลังตีกลับ: ให้คนที่ตีกลับทำรายการได้ทันที
		if (!$allowRecheckAfterReply) {
			$prevOk = true;
			foreach ($allSortOrders as $prevSo) {
				if ($prevSo >= $mySortOrder) break;
				$prevDec = isset($decisionsBySort[$prevSo]) ? (string)$decisionsBySort[$prevSo] : '';
				if ($prevDec !== 'approved') {
					$prevOk = false;
					break;
				}
			}
			if (!$prevOk) {
				$respond(0, 'ยังไม่ถึงคิวของคุณ (รอผู้รับรองลำดับก่อนหน้า)', $back . '&err=' . rawurlencode('ยังไม่ถึงคิวของคุณ (รอผู้รับรองลำดับก่อนหน้า)'));
			}
		}

		// บันทึกการรับรอง/ไม่รับรอง (+ ข้อเสนอแนะ ถ้ามีคอลัมน์ certifier_note)
		$noteEsc = mysqli_real_escape_string($db_Project, $certifier_note);
		$decisionSqlWithNote = "
			INSERT INTO project_include_request_certifier_decision
				(request_id, sort_order, staff_id, decision, certifier_note, decided_at, decided_by_staff_id)
			VALUES
				('" . (int)$request_id . "', '" . (int)$mySortOrder . "', '" . (int)$sid . "', '" . ($action === 'approve' ? 'approved' : 'rejected') . "', '" . $noteEsc . "', NOW(), '" . (int)$sid . "')
			ON DUPLICATE KEY UPDATE
				decision=VALUES(decision),
				certifier_note=VALUES(certifier_note),
				decided_at=VALUES(decided_at),
				decided_by_staff_id=VALUES(decided_by_staff_id),
				staff_id=VALUES(staff_id)
		";
		$decisionSqlNoNote = "
			INSERT INTO project_include_request_certifier_decision
				(request_id, sort_order, staff_id, decision, decided_at, decided_by_staff_id)
			VALUES
				('" . (int)$request_id . "', '" . (int)$mySortOrder . "', '" . (int)$sid . "', '" . ($action === 'approve' ? 'approved' : 'rejected') . "', NOW(), '" . (int)$sid . "')
			ON DUPLICATE KEY UPDATE
				decision=VALUES(decision),
				decided_at=VALUES(decided_at),
				decided_by_staff_id=VALUES(decided_by_staff_id),
				staff_id=VALUES(staff_id)
		";

		$ok = @mysqli_query($db_Project, $decisionSqlWithNote);
		if (!$ok) {
			// fallback: กรณี DB ยังไม่มีคอลัมน์ certifier_note
			$ok = @mysqli_query($db_Project, $decisionSqlNoNote);
		}
		if (!$ok) {
			$ajaxDebug['db_error'] = mysqli_error($db_Project);
			$respond(0, 'บันทึกผลการรับรองไม่สำเร็จ: ' . (mysqli_error($db_Project) ?: ''), $back . '&err=' . rawurlencode('บันทึกผลการรับรองไม่สำเร็จ'));
		}
		// confirm saved row
		$qCk = @mysqli_query($db_Project, "SELECT decision, decided_at, certifier_note FROM project_include_request_certifier_decision WHERE request_id='".(int)$request_id."' AND sort_order='".(int)$mySortOrder."' LIMIT 1");
		$ajaxDebug['saved_row'] = $qCk ? (mysqli_fetch_assoc($qCk) ?: null) : null;

		if ($action === 'reject') {
			$rejIds = $rejectSelectedProjectIds;
			$rejCsv = implode(',', array_map('intval', array_values($rejIds)));
			$metaRej = mysqli_real_escape_string($db_Project, json_encode([
				'returned_project_ids' => array_values($rejIds),
			], JSON_UNESCAPED_UNICODE));
			@mysqli_query(
				$db_Project,
				"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, sort_order, note, meta_json, created_at)
				 VALUES ('".(int)$request_id."', 'certifier_rejected', '".(int)$sid."', '".(int)$mySortOrder."', '".$noteEsc."', '".$metaRej."', NOW())"
			);
			if (function_exists('ensureIncludeRequestTables')) {
				ensureIncludeRequestTables($db_Project);
			}
			$allowedIds = [];
			$qAllItems = @mysqli_query(
				$db_Project,
				"SELECT project_id FROM project_include_request_item WHERE request_id='" . (int)$request_id . "'"
			);
			while ($qAllItems && ($ai = mysqli_fetch_assoc($qAllItems))) {
				$pidA = (int)($ai['project_id'] ?? 0);
				if ($pidA > 0) {
					$allowedIds[$pidA] = $pidA;
				}
			}

			// เคลียร์ธงตีกลับรอบก่อนหน้าทั้งคำขอ แล้วตั้งเฉพาะที่เลือก
			@mysqli_query(
				$db_Project,
				"UPDATE project_include_request_item
				 SET return_selected=0
				 WHERE request_id='" . (int)$request_id . "'"
			);
			@mysqli_query(
				$db_Project,
				"UPDATE project_include_request_item
				 SET return_selected=1,
				     returned_at=NOW(),
				     returned_by_staff_id='" . (int)$sid . "',
				     returned_sort_order='" . (int)$mySortOrder . "',
				     return_cert_note='" . $noteEsc . "',
				     fix_note=NULL,
				     fix_at=NULL,
				     fix_staff_id=NULL,
				     fix_status='pending'
				 WHERE request_id='" . (int)$request_id . "'
				   AND project_id IN ($rejCsv)"
			);
			if (!empty($returnProjectNotesPosted)) {
				foreach ($rejIds as $__rpid) {
					$__rpid = (int)$__rpid;
					$__rn = trim((string)($returnProjectNotesPosted[$__rpid] ?? ''));
					if ($__rn === '') {
						continue;
					}
					$__rnEsc = mysqli_real_escape_string($db_Project, $__rn);
					@mysqli_query(
						$db_Project,
						"UPDATE project_include_request_item
						 SET return_cert_note='" . $__rnEsc . "'
						 WHERE request_id='" . (int)$request_id . "' AND project_id='" . $__rpid . "' LIMIT 1"
					);
				}
			}
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project
				SET status_project='8', dates=NOW()
				WHERE status='1'
				  AND status_project IN ('3','4','8')
				  AND id IN ($rejCsv)"
			);
			// โครงการที่ไม่ถูกเลือก — คงสถานะรอบรรจุ (3) ถ้าเคยเป็น 8 จากรอบก่อน
			$keepIds = array_diff($allowedIds, $rejIds);
			if (!empty($keepIds)) {
				$keepCsv = implode(',', array_map('intval', array_values($keepIds)));
				@mysqli_query(
					$db_Project,
					"UPDATE pre_project
					 SET status_project='3', dates=NOW()
					 WHERE status='1' AND status_project='8' AND id IN ($keepCsv)"
				);
			}

			// เก็บคำชี้แจงระดับคำขอเดิมไว้ใน log (ไม่ลบ)
			$qPrevReply = @mysqli_query(
				$db_Project,
				"SELECT submitter_reply_note, submitter_reply_updated_at
				 FROM project_include_request WHERE id='" . (int)$request_id . "' LIMIT 1"
			);
			$prevReplyRow = $qPrevReply ? mysqli_fetch_assoc($qPrevReply) : null;
			$prevNoteKeep = trim((string)($prevReplyRow['submitter_reply_note'] ?? ''));
			$prevAtKeep = trim((string)($prevReplyRow['submitter_reply_updated_at'] ?? ''));
			if ($prevNoteKeep !== '') {
				$metaKeep = mysqli_real_escape_string($db_Project, json_encode([
					'kept_note' => $prevNoteKeep,
					'kept_at' => $prevAtKeep,
					'reason' => 'certifier_rejected_keep_previous_reply',
					'returned_project_ids' => array_values($rejIds),
				], JSON_UNESCAPED_UNICODE));
				$prevNoteEsc = mysqli_real_escape_string($db_Project, $prevNoteKeep);
				@mysqli_query(
					$db_Project,
					"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, sort_order, note, meta_json, created_at)
					 VALUES ('" . (int)$request_id . "', 'submitter_reply_kept', '" . (int)$sid . "', '" . (int)$mySortOrder . "', '" . $prevNoteEsc . "', '" . $metaKeep . "', NOW())"
				);
			}
			@mysqli_query(
				$db_Project,
				"UPDATE project_include_request
				 SET status='rejected', admin_fix_sent_at=NULL, admin_fix_sent_by=NULL, updated_at=NOW()
				 WHERE id='" . (int)$request_id . "' LIMIT 1"
			);
			$ajaxDebug['reject_updated_projects'] = count($rejIds);
			$ajaxDebug['returned_project_ids'] = array_values($rejIds);

			$notifyIncIds = [];
			$qSubInc = @mysqli_query($db_Project, "SELECT submitter_staff_id FROM project_include_request WHERE id='" . (int)$request_id . "' LIMIT 1");
			$rSubInc = $qSubInc ? mysqli_fetch_assoc($qSubInc) : null;
			$notifyIncIds[] = (int)($rSubInc['submitter_staff_id'] ?? 0);
			foreach ($rejIds as $__npid) {
				$notifyIncIds[] = function_exists('pm_project_staff_insert_id')
					? pm_project_staff_insert_id($db_Project, (int)$__npid)
					: 0;
			}
			$bodyInc = 'คำขอ #' . (int)$request_id . ' — ตีกลับ ' . count($rejIds) . ' โครงการ';
			if ($certifier_note !== '') {
				$bodyInc .= "\nข้อแก้ไข: " . $certifier_note;
			}
			$linkInc = '?page=project_approve&flow=include&tab=sent&request_id=' . (int)$request_id;
			if (function_exists('pm_notify_revision_needed')) {
				pm_notify_revision_needed($db_Project, $notifyIncIds, 'include_rejected', 'คำขอบรรจุโครงการถูกตีกลับให้แก้ไข', $bodyInc, $linkInc, 'include_request', (int)$request_id, (int)$sid);
			}
			if (function_exists('pm_approve_inbox_clear_read')) {
				pm_approve_inbox_clear_read($db_Project, 'include', 'include:' . (int)$request_id);
			}

			$redir = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=history&msg=' . rawurlencode('บันทึกตีกลับเรียบร้อย (' . count($rejIds) . ' โครงการ)');
			$respond(1, 'บันทึกตีกลับเรียบร้อย — เลือก ' . count($rejIds) . ' โครงการ', $redir);
		}
		// log approve
		@mysqli_query(
			$db_Project,
			"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, sort_order, note, created_at)
			 VALUES ('".(int)$request_id."', '".($allowRecheckAfterReply ? "certifier_recheck_approved" : "certifier_approved")."', '".(int)$sid."', '".(int)$mySortOrder."', '".$noteEsc."', NOW())"
		);
		// ผ่านในโหมดพิจารณาซ้ำ: คืนสถานะคำขอและดันโครงการกลับเข้าคิว "รอบรรจุโครงการ" (3) — ล็อกแก้ไขไว้
		// (จะขึ้นเป็น 4 เมื่อผู้รับรองทุกลำดับผ่านครบ ตาม final check ด้านล่าง)
		if ($allowRecheckAfterReply && $action === 'approve') {
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project
				 SET status_project='3', dates=NOW()
				 WHERE status='1'
				   AND status_project IN ('8','3')
				   AND id IN (SELECT i.project_id FROM project_include_request_item i WHERE i.request_id='" . (int)$request_id . "')"
			);
			@mysqli_query(
				$db_Project,
				"UPDATE project_include_request
				 SET status='submitted', updated_at=NOW()
				 WHERE id='" . (int)$request_id . "' LIMIT 1"
			);
		}

		// ถ้า pipeline ยังไม่พร้อม: บันทึกผลผู้รับรองแล้ว แต่ยังไม่ทำ final update ของโครงการ
		if (!$projects_pipeline_ok) {
			$redir = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=' . rawurlencode('pending') . '&msg=' . rawurlencode('บันทึกผลการรับรองเรียบร้อย (คำขอยังไม่อยู่ในขั้นตอนรอรับรองครบทุกโครงการ)');
			$respond(1, 'บันทึกผลการรับรองเรียบร้อย', $redir);
		}

		// ตรวจว่าผู้รับรองทุกลำดับอนุมัติแล้วหรือยัง
		$finalCheckSql = "
			SELECT
				COUNT(*) AS totalCert,
				SUM(CASE WHEN d.decision='approved' THEN 1 ELSE 0 END) AS cntApproved,
				SUM(CASE WHEN d.decision='rejected' THEN 1 ELSE 0 END) AS cntRejected
			FROM project_include_request_certifier c
			LEFT JOIN project_include_request_certifier_decision d
				ON d.request_id=c.request_id AND d.sort_order=c.sort_order
			WHERE c.request_id='" . (int)$request_id . "'
		";
		$finalCheckRes = @mysqli_query($db_Project, $finalCheckSql);
		$finalCheckRow = $finalCheckRes ? mysqli_fetch_assoc($finalCheckRes) : null;
		$totalCert = (int)($finalCheckRow['totalCert'] ?? $totalCert);
		$cntApproved = (int)($finalCheckRow['cntApproved'] ?? 0);
		$cntRejected = (int)($finalCheckRow['cntRejected'] ?? 0);

		if ($cntRejected > 0) {
			$redir = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=' . rawurlencode('history') . '&msg=' . rawurlencode('ระบบพบว่ามีการไม่อนุมัติแล้ว');
			$respond(1, 'บันทึกแล้ว (มีการตีกลับในระบบ)', $redir);
		}

		if ($cntApproved >= $totalCert && $totalCert > 0) {
			$finalSql = "
				UPDATE pre_project
				-- ผ่านครบทุกลำดับผู้รับรอง → ยังคงสถานะรอบรรจุโครงการ (3) จนกว่าหน่วยแผนจะนำเข้าที่ประชุม
				SET status_project='3', dates=NOW()
				WHERE status='1'
				  AND status_project IN ('3','4')
				  AND id IN (SELECT i.project_id FROM project_include_request_item i WHERE i.request_id='" . (int)$request_id . "')
			";
			@mysqli_query($db_Project, $finalSql);
			@mysqli_query($db_Project, "UPDATE project_include_request SET status='approved', updated_at=NOW() WHERE id='" . (int)$request_id . "' LIMIT 1");

			$redir = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=' . rawurlencode('draft') . '&msg=' . rawurlencode('อนุมัติเรียบร้อย รอหน่วยแผนและงบประมาณพิจารณา (รอบรรจุโครงการ)');
			$respond(1, 'อนุมัติเรียบร้อย รอหน่วยแผนพิจารณา', $redir);
		}

		$redir = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=' . rawurlencode('pending') . '&msg=' . rawurlencode('บันทึกอนุมัติเรียบร้อย รอผู้รับรองลำดับถัดไป');
		$respond(1, 'บันทึกอนุมัติเรียบร้อย', $redir);
	}
}

$unit_list = [];
$unit_names = [];
$year_list = [];
try {
	$unit_list = getUnitSelect($db_person);
	if (is_array($unit_list)) {
		foreach ($unit_list as $u) {
			if (!is_array($u)) {
				continue;
			}
			$uc = (string)($u['code'] ?? '');
			if ($uc === '') {
				continue;
			}
			$__un = (string)($u['name'] ?? '-');
			$unit_names[$uc] = function_exists('planStripUnitCodeFromLabel')
				? planStripUnitCodeFromLabel($__un)
				: $__un;
		}
	}
	$year_list = getYearSelect($db_pbmBudget);
} catch (Throwable $e) {
	$unit_list = [];
	$unit_names = [];
	$year_list = [];
}
if (!is_array($year_list)) {
	$year_list = [];
}

// ซ่อมสถานะค้าง: คำขอที่ตีกลับแต่ผู้ยื่นส่งคำชี้แจงรอบล่าสุดแล้ว → โครงการต้องเป็นรอบรรจุ (3)
@mysqli_query(
	$db_Project,
	"UPDATE pre_project p
	 JOIN project_include_request_item i ON i.project_id = p.id
	 JOIN project_include_request ir ON ir.id = i.request_id
	 SET p.status_project='3', p.dates=NOW()
	 WHERE p.status='1'
	   AND p.status_project='8'
	   AND ir.status='rejected'
	   AND TRIM(IFNULL(ir.submitter_reply_note,'')) <> ''
	   AND ir.submitter_reply_updated_at IS NOT NULL
	   AND ir.submitter_reply_updated_at <> ''
	   AND ir.submitter_reply_updated_at >= IFNULL((
			SELECT MAX(l.created_at)
			FROM project_include_request_action_log l
			WHERE l.request_id = ir.id AND l.action_type = 'certifier_rejected'
	   ), '1970-01-01 00:00:00')"
);

// แสดงรายการโครงการที่:
// - คุณเป็นผู้รับรอง (พิจารณา) หรือ
// - คุณเป็นผู้ส่งคำขอ (เสนอเรื่อง)
// - คุณเป็น Admin หน่วยงานที่มีสิทธิ์ดูโครงการในชุดคำขอ
$yearsEsc = mysqli_real_escape_string($db_Project, $years_select);
$sidEsc = (int)$sid;
$staffCodeEsc = mysqli_real_escape_string($db_Project, (string)$staff_code);
$codeUnitEsc = mysqli_real_escape_string($db_Project, (string)$code_unit);

// helper: แปลงวันที่เป็นแบบไทยสั้น (เช่น 6 ก.พ. 69)
$thaiMonth = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
$fmtThaiShort = function($dt) use ($thaiMonth){
	$dt = trim((string)$dt);
	if ($dt === '' || $dt === '0000-00-00 00:00:00') return '-';
	$ts = strtotime($dt);
	if ($ts === false) return '-';
	$d = (int)date('j', $ts);
	$m = (int)date('n', $ts);
	$y = (int)date('Y', $ts) + 543;
	$yy = substr((string)$y, -2);
	$mm = $thaiMonth[$m] ?? '';
	return $d . ' ' . $mm . ' ' . $yy;
};

// ดึงรายการ "คำขอขอบรรจุ" แบบ 1 แถวต่อ 1 request (เหมือนภาพ)
// หมายเหตุ: "สถานะ" และ "วัตถุประสงค์" ให้เป็นตัวกรองฝั่งหน้าเว็บ
// ดังนั้น default (ไม่เลือก) ต้องแสดงทั้งหมดของปีนั้นที่เกี่ยวข้อง (ผู้ยื่น/ผู้พิจารณา)
// Plan (PLAN_ADMIN): แสดงเฉพาะคำขอที่ผู้รับรองทุกลำดับกด "ผ่าน" ครบแล้ว (ตรรกะเดียวกับ finalCheckSql ตอนบันทึก)
if ($pm_appr_is_plan_admin) {
	$reqSql = "
		SELECT
			ir.id AS request_id,
			ir.created_at,
			ir.subject,
			ir.submitter_staff_id,
			MAX(ir.status) AS request_status,
			MAX(IFNULL(ir.plan_unit_decision,'')) AS plan_unit_decision,
			MAX(IFNULL(ir.submitter_reply_note,'')) AS submitter_reply_note,
			MAX(IFNULL(ir.submitter_reply_updated_at,'')) AS submitter_reply_updated_at,
			MAX(IFNULL(ir.admin_fix_sent_at,'')) AS admin_fix_sent_at,
			0 AS is_reviewer,
			0 AS is_submitter,
			SUM(CASE WHEN p.status_project='4' THEN 1 ELSE 0 END) AS cnt_pending,
			SUM(CASE WHEN p.status_project='1' THEN 1 ELSE 0 END) AS cnt_operate,
			SUM(CASE WHEN TRIM(IFNULL(p.notify_result,'')) IN ('บรรจุแผน','บรรจุแผนและแก้ไข','ไม่ผ่านการพิจารณา') THEN 1 ELSE 0 END) AS cnt_notified,
			COUNT(*) AS cnt_projects,
			GROUP_CONCAT(DISTINCT CONCAT(IFNULL(p.code_pre_project,''), ' ', IFNULL(p.name_project,'')) SEPARATOR ' ') AS project_search
		FROM project_include_request ir
		JOIN project_include_request_item i ON i.request_id = ir.id
		JOIN pre_project p ON p.id = i.project_id
		WHERE p.status='1'
		  AND p.year_project='".$yearsEsc."'
		  AND EXISTS (SELECT 1 FROM project_include_request_certifier c0 WHERE c0.request_id = ir.id)
		  AND (
			ir.status='rejected'
			OR (
			  NOT EXISTS (
				SELECT 1
				FROM project_include_request_certifier c1
				LEFT JOIN project_include_request_certifier_decision d1
					ON d1.request_id = c1.request_id AND d1.sort_order = c1.sort_order
				WHERE c1.request_id = ir.id
				  AND (d1.decision IS NULL OR d1.decision <> 'approved')
			  )
			  AND NOT EXISTS (
				SELECT 1 FROM project_include_request_certifier_decision d2
				WHERE d2.request_id = ir.id AND d2.decision = 'rejected'
			  )
			)
		  )
		GROUP BY ir.id
		ORDER BY ir.created_at DESC, ir.id DESC
	";
} else {
	$reqSql = "
	SELECT
		ir.id AS request_id,
		ir.created_at,
		ir.subject,
		ir.submitter_staff_id,
		MAX(ir.status) AS request_status,
		MAX(IFNULL(ir.plan_unit_decision,'')) AS plan_unit_decision,
		MAX(IFNULL(ir.submitter_reply_note,'')) AS submitter_reply_note,
		MAX(IFNULL(ir.submitter_reply_updated_at,'')) AS submitter_reply_updated_at,
		MAX(IFNULL(ir.admin_fix_sent_at,'')) AS admin_fix_sent_at,
		MAX(CASE WHEN c.staff_id IS NULL THEN 0 ELSE 1 END) AS is_reviewer,
		MAX(CASE WHEN ir.submitter_staff_id = '".$sidEsc."' THEN 1 ELSE 0 END) AS is_submitter,
		MAX(
			CASE
				WHEN
					(
						EXISTS (
							SELECT 1
							FROM set_department_admin sda
							WHERE (sda.staff_admin = '".$staffCodeEsc."' OR sda.staff_admin = '".$sidEsc."')
							  AND (
								sda.unit_code = p.code_unit
								OR EXISTS (
									SELECT 1 FROM set_unit su
									WHERE su.status != '99'
									  AND CAST(su.id AS CHAR) = sda.unit_code
									  AND (CAST(su.id AS CHAR) = IFNULL(p.code_unit,'') OR IFNULL(su.code,'') = IFNULL(p.code_unit,''))
								)
							  )
						)
						OR ('".$codeUnitEsc."' <> '' AND IFNULL(p.code_unit,'') <> '' AND p.code_unit = '".$codeUnitEsc."')
					)
				THEN 1 ELSE 0
			END
		) AS is_unit_admin_scope,
		SUM(CASE WHEN p.status_project='4' THEN 1 ELSE 0 END) AS cnt_pending,
		SUM(CASE WHEN p.status_project='1' THEN 1 ELSE 0 END) AS cnt_operate,
		SUM(CASE WHEN TRIM(IFNULL(p.notify_result,'')) IN ('บรรจุแผน','บรรจุแผนและแก้ไข','ไม่ผ่านการพิจารณา') THEN 1 ELSE 0 END) AS cnt_notified,
		COUNT(*) AS cnt_projects,
		GROUP_CONCAT(DISTINCT CONCAT(IFNULL(p.code_pre_project,''), ' ', IFNULL(p.name_project,'')) SEPARATOR ' ') AS project_search
	FROM project_include_request ir
	JOIN project_include_request_item i ON i.request_id = ir.id
	JOIN pre_project p ON p.id = i.project_id
	LEFT JOIN project_include_request_certifier c
		ON c.request_id = ir.id AND c.staff_id = '".$sidEsc."'
	WHERE p.status='1'
	  AND p.year_project='".$yearsEsc."'
	  AND (
		ir.submitter_staff_id = '".$sidEsc."'
		OR c.staff_id = '".$sidEsc."'
		OR EXISTS (
			SELECT 1
			FROM set_department_admin sda
			WHERE (sda.staff_admin = '".$staffCodeEsc."' OR sda.staff_admin = '".$sidEsc."')
			  AND (
				sda.unit_code = p.code_unit
				OR EXISTS (
					SELECT 1 FROM set_unit su
					WHERE su.status != '99'
					  AND CAST(su.id AS CHAR) = sda.unit_code
					  AND (CAST(su.id AS CHAR) = IFNULL(p.code_unit,'') OR IFNULL(su.code,'') = IFNULL(p.code_unit,''))
				)
			  )
		)
		OR ('".$codeUnitEsc."' <> '' AND IFNULL(p.code_unit,'') <> '' AND p.code_unit = '".$codeUnitEsc."')
	  )
	GROUP BY ir.id
	ORDER BY ir.created_at DESC, ir.id DESC
";
}

$reqRes = @mysqli_query($db_Project, $reqSql);
$request_list = [];
if ($reqRes) {
	while ($row = mysqli_fetch_assoc($reqRes)) {
		$request_list[] = $row;
	}
}

// รายการขอดำเนินการ (รวมในหน้าเดียวกับคำขอขอบรรจุ — ไม่แยกแท็บ)
// - สถานะขอดำเนินการปกติ (status_project=1)
// - กรณีถูกตีกลับเพื่อแก้ไขหลังขอดำเนินการ (status_project=8 + notify_result=บรรจุแผน)
// แสดงให้: ผู้รับรองในลำดับ operate_certifiers_json (พิจารณา)
//          และผู้ยื่น/เจ้าของโครงการ (เสนอเรื่อง)
$plan_operate_projects = [];
$pm_is_unit_admin = function_exists('isUnitAdmin') ? isUnitAdmin($db_Project, $staff_code, $code_unit, (int)$sid) : false;
$pm_unit_names = function_exists('pm_build_project_unit_names_map')
	? pm_build_project_unit_names_map($db_Project, $db_person ?? null)
	: [];
if (!is_array($pm_unit_names) || empty($pm_unit_names)) {
	$pm_unit_names = [];
	if (function_exists('getUnitSelect')) {
		foreach (getUnitSelect($db_person) as $__u) {
			$__uc = (string)($__u['code'] ?? '');
			if ($__uc !== '') {
				$pm_unit_names[$__uc] = (string)($__u['name'] ?? '');
			}
		}
	}
}
$pm_manage_rows = function_exists('getManageableProjectList')
	? getManageableProjectList($db_Project, $years_select, $sid, $staff_code, $pm_is_unit_admin, $code_unit)
	: [];
if ((int)$sid > 0) {
	if (function_exists('pm_ensure_operate_approver_schema')) {
		pm_ensure_operate_approver_schema($db_Project);
	}
	if (function_exists('pm_reopen_operate_queue_after_submitter_reply')) {
		pm_reopen_operate_queue_after_submitter_reply($db_Project);
	}
	$qOperateInbox = @mysqli_query(
		$db_Project,
		"SELECT p.id, p.code_pre_project, p.name_project, p.code_unit, p.status_project,
		        p.notify_result, p.operate_certifiers_json, p.operate_approvers_json,
		        p.plan_admin_operate_rechain_submitted_at, p.dates, p.staff_insert,
		        p.midyear_request, p.operate_submitter_reply_note
		 FROM pre_project p
		 WHERE p.status='1'
		   AND p.year_project='" . $yearsEsc . "'
		   AND (
		        p.status_project='1'
		        OR (p.status_project='8' AND IFNULL(p.notify_result,'')='บรรจุแผน')
		        OR (p.status_project='8' AND IFNULL(p.midyear_request,0)=1)
		   )
		   AND TRIM(IFNULL(p.operate_certifiers_json,'')) <> ''"
	);
	while ($qOperateInbox && ($__pr = mysqli_fetch_assoc($qOperateInbox))) {
		$__pid = (int)($__pr['id'] ?? 0);
		$__st = (string)($__pr['status_project'] ?? '');
		$__nr = trim((string)($__pr['notify_result'] ?? ''));
		$__isMidyear = (int)($__pr['midyear_request'] ?? 0) === 1;
		if ($__pid > 0 && function_exists('pm_project_is_cancelled') && pm_project_is_cancelled($db_Project, $__pid, $__pr)) {
			continue;
		}
		$__allowOperatePlan = ($__st === '1') || ($__st === '8' && ($__nr === 'บรรจุแผน' || $__isMidyear));
		if ($__pid <= 0 || !$__allowOperatePlan) {
			continue;
		}
		$__certRaw = trim((string)($__pr['operate_certifiers_json'] ?? ''));
		$__certArr = $__certRaw !== '' ? json_decode($__certRaw, true) : [];
		if (!is_array($__certArr)) {
			$__certArr = [];
		}
		$__isOperateReviewer = false;
		foreach ($__certArr as $__c) {
			if (!is_array($__c)) {
				continue;
			}
			if ((int)($__c['staff_id'] ?? 0) === (int)$sid) {
				$__isOperateReviewer = true;
				break;
			}
		}
		$__apprRawInbox = trim((string)($__pr['operate_approvers_json'] ?? ''));
		$__apprArrInbox = $__apprRawInbox !== '' ? json_decode($__apprRawInbox, true) : [];
		if (!is_array($__apprArrInbox)) {
			$__apprArrInbox = [];
		}
		$__isOperateApprover = false;
		foreach ($__apprArrInbox as $__c) {
			if (!is_array($__c)) {
				continue;
			}
			if ((int)($__c['staff_id'] ?? 0) === (int)$sid) {
				$__isOperateApprover = true;
				break;
			}
		}
		$__certsDoneInbox = function_exists('pm_compute_operate_chain_all_approved')
			? pm_compute_operate_chain_all_approved($db_Project, $__pid, $__certRaw)
			: false;
		$__waitingPlanInbox = $__certsDoneInbox
			&& $__apprRawInbox === ''
			&& trim((string)($__pr['plan_admin_operate_rechain_submitted_at'] ?? '')) === '';
		$__isPlanPickInbox = (defined('PLAN_ADMIN') && PLAN_ADMIN) && $__waitingPlanInbox;
		$__isPlanFollowInbox = (defined('PLAN_ADMIN') && PLAN_ADMIN) && $__certsDoneInbox && !$__waitingPlanInbox;
		$__isOperateSubmitter = ((int)($__pr['staff_insert'] ?? 0) === (int)$sid);
		if (!$__isOperateSubmitter && (int)$sid > 0 && !(defined('PLAN_ADMIN') && PLAN_ADMIN)) {
			$qOpMem = @mysqli_query(
				$db_Project,
				"SELECT 1 FROM pre_project p
				 WHERE p.id='" . (int)$__pid . "'
				   AND p.status='1'
				   AND (
					EXISTS (SELECT 1 FROM project_head ph WHERE ph.project_id=p.id AND ph.status!='99' AND ph.staff_project='" . (int)$sid . "' LIMIT 1)
					OR EXISTS (SELECT 1 FROM project_staff ps WHERE ps.project_id=p.id AND ps.status!='99' AND ps.staff_project='" . (int)$sid . "' LIMIT 1)
					OR EXISTS (SELECT 1 FROM project_person pp WHERE pp.project_id=p.id AND pp.status!='99' AND pp.staff_project='" . (int)$sid . "' LIMIT 1)
				   )
				 LIMIT 1"
			);
			$__isOperateSubmitter = ($qOpMem && mysqli_num_rows($qOpMem) > 0);
		}
		if (!$__isOperateReviewer && !$__isOperateApprover && !$__isOperateSubmitter && !$__isPlanPickInbox && !$__isPlanFollowInbox) {
			continue;
		}
		$__operateObjective = ($__isOperateApprover || $__isOperateReviewer || $__isPlanPickInbox || $__isPlanFollowInbox) ? 'review' : 'propose';
		$__rid = 0;
		$qOpRid = @mysqli_query(
			$db_Project,
			"SELECT MAX(i.request_id) AS rid FROM project_include_request_item i WHERE i.project_id='" . (int)$__pid . "'"
		);
		$rOpRid = $qOpRid ? mysqli_fetch_assoc($qOpRid) : null;
		$__rid = (int)($rOpRid['rid'] ?? 0);
		$__sumQ = @mysqli_query($db_Project, "SELECT IFNULL(SUM(pa.total),0) AS t FROM project_activity_budget pa WHERE pa.code_pre_project='" . $__pid . "' AND pa.status!='99'");
		$__sumR = $__sumQ ? mysqli_fetch_assoc($__sumQ) : null;
		$__budget = (float)($__sumR['t'] ?? 0);
		$__uc = (string)($__pr['code_unit'] ?? '');
		$__unit_name = function_exists('pm_unit_name_from_map')
			? pm_unit_name_from_map($pm_unit_names, $__uc, $db_Project)
			: (($__uc !== '' && isset($pm_unit_names[$__uc])) ? (string)$pm_unit_names[$__uc] : '');
		if ($__unit_name === '') {
			$__unit_name = '-';
		}
		$__budget_src_lbl = function_exists('resolveProjectBudgetSourceForDisplay') ? resolveProjectBudgetSourceForDisplay($db_Project, $__pid) : '-';
		$__status_mod = 'pending';
		$__status_txt = 'รอดำเนินการ';
		$__status_icon = 'fa-hourglass-half';
		$__status_group = 'pending';
		$__certRaw = trim((string)($__pr['operate_certifiers_json'] ?? ''));
		$__certArr = $__certRaw !== '' ? json_decode($__certRaw, true) : [];
		$__requiredList = [];
		if (is_array($__certArr)) {
			$__seqNo = 0;
			foreach ($__certArr as $__c) {
				if (!is_array($__c)) continue;
				$__seqNo++;
				$__so = (int)($__c['sort_order'] ?? 0);
				if ($__so <= 0) $__so = (int)$__seqNo;
				$__requiredList[$__so] = true;
			}
		}
		$__requiredList = array_keys($__requiredList);
		sort($__requiredList, SORT_NUMERIC);
		if (empty($__requiredList) && $__rid > 0) {
			$qReqCert = @mysqli_query(
				$db_Project,
				"SELECT sort_order FROM project_include_request_certifier
				 WHERE request_id='" . (int)$__rid . "'
				 ORDER BY sort_order ASC"
			);
			while ($qReqCert && ($__rc = mysqli_fetch_assoc($qReqCert))) {
				$__so = (int)($__rc['sort_order'] ?? 0);
				if ($__so > 0) {
					$__requiredList[] = $__so;
				}
			}
			$__requiredList = array_values(array_unique(array_map('intval', $__requiredList)));
			sort($__requiredList, SORT_NUMERIC);
		}

		$__decOpBySo = [];
		$qOpAllDec = @mysqli_query(
			$db_Project,
			"SELECT sort_order, decision FROM project_operate_certifier_decision WHERE project_id='" . (int)$__pid . "'"
		);
		while ($qOpAllDec && ($__od = mysqli_fetch_assoc($qOpAllDec))) {
			$__sod = (int)($__od['sort_order'] ?? 0);
			if ($__sod > 0) {
				$__decOpBySo[$__sod] = (string)($__od['decision'] ?? '');
			}
		}
		$__anyOperateRejected = false;
		$__allOperateApproved = !empty($__requiredList);
		foreach ($__requiredList as $__soReq) {
			$__dv = (string)($__decOpBySo[$__soReq] ?? '');
			if ($__dv === 'rejected') {
				$__anyOperateRejected = true;
			}
			if ($__dv !== 'approved') {
				$__allOperateApproved = false;
			}
		}
		$__finalApprovedInbox = function_exists('pm_is_operate_final_approved')
			? pm_is_operate_final_approved($db_Project, $__pid, $__pr)
			: false;
		$__approversAllInbox = false;
		if ($__apprRawInbox !== '' && function_exists('pm_compute_operate_approver_chain_all_approved')) {
			$__approversAllInbox = pm_compute_operate_approver_chain_all_approved($db_Project, $__pid, $__pr);
		}
		$__replyNoteInbox = trim((string)($__pr['operate_submitter_reply_note'] ?? ''));
		if (function_exists('pm_resolve_ab_status')) {
			$__abOp = pm_resolve_ab_status($__pr, [
				'db' => $db_Project,
				'operate_all_approved' => !empty($__finalApprovedInbox),
				'operate_waiting_plan' => !empty($__waitingPlanInbox),
			]);
			$__status_mod = (string)($__abOp['mod'] ?? 'pending');
			$__status_txt = (string)($__abOp['label'] ?? 'รอดำเนินการ');
			$__status_icon = (string)($__abOp['icon'] ?? 'fa-hourglass-half');
			if (in_array($__status_mod, ['b3', 'b4_done', 'b5', 'approved'], true)) {
				$__status_group = 'done';
			} elseif (in_array($__status_mod, ['b_fix', 'a_fix', 'returned'], true)) {
				$__status_group = 'returned';
			} else {
				$__status_group = 'pending';
			}
		} elseif ($__finalApprovedInbox || $__approversAllInbox) {
			$__status_mod = 'approved';
			$__status_txt = 'อนุมัติ';
			$__status_icon = 'fa-check-circle';
			$__status_group = 'done';
		} elseif (($__st === '8' || $__anyOperateRejected) && $__replyNoteInbox === '') {
			$__status_mod = 'returned';
			$__status_txt = 'ตีกลับเพื่อแก้ไข';
			$__status_icon = 'fa-undo';
			$__status_group = 'returned';
		} elseif ($__waitingPlanInbox) {
			$__status_mod = 'pending';
			$__status_txt = ($__replyNoteInbox !== '') ? 'รอหน่วยแผนพิจารณา (ส่งกลับแล้ว)' : 'รอหน่วยแผนเลือกผู้อนุมัติ';
			$__status_icon = 'fa-hourglass-half';
			$__status_group = 'pending';
		} elseif ($__replyNoteInbox !== '' && !$__allOperateApproved) {
			$__status_mod = 'pending';
			$__status_txt = 'รอตรวจสอบ (ส่งกลับแล้ว)';
			$__status_icon = 'fa-hourglass-half';
			$__status_group = 'pending';
		} elseif ($__allOperateApproved && $__apprRawInbox !== '') {
			$__status_mod = 'pending';
			$__status_txt = 'รออนุมัติ';
			$__status_icon = 'fa-hourglass-half';
			$__status_group = 'pending';
		}
		$__nm = trim((string)($__pr['name_project'] ?? ''));
		$__subject = 'ขอดำเนินการโครงการ ' . ($__nm !== '' ? $__nm : '…');
		if ($__unit_name !== '' && $__unit_name !== '-') {
			$__subject .= ' (' . $__unit_name . ')';
		}
		$plan_operate_projects[] = [
			'id' => $__pid,
			'code_pre_project' => (string)($__pr['code_pre_project'] ?? ''),
			'name_project' => (string)($__pr['name_project'] ?? ''),
			'unit_name' => $__unit_name,
			'budget' => $__budget,
			'budget_source' => $__budget_src_lbl,
			'request_id' => $__rid,
			'dates' => (string)($__pr['dates'] ?? ''),
			'staff_insert' => (int)($__pr['staff_insert'] ?? 0),
			'subject' => $__subject,
			'status_mod' => $__status_mod,
			'status_txt' => $__status_txt,
			'status_icon' => $__status_icon,
			'status_group' => $__status_group,
			'objective_key' => $__operateObjective,
			'is_midyear' => !empty($__isMidyear),
			'is_submitter' => !empty($__isOperateSubmitter) ? 1 : 0,
			'is_reviewer' => (!empty($__isOperateReviewer) || !empty($__isOperateApprover) || !empty($__isPlanPickInbox) || !empty($__isPlanFollowInbox)) ? 1 : 0,
			'is_resubmitted' => ($__replyNoteInbox !== '' && $__status_group === 'pending') ? 1 : 0,
		];
	}
}

$plan_report_projects = [];
if (function_exists('ensureProjectResultReportSchema')) {
	ensureProjectResultReportSchema($db_Project);
}
// แสดงเฉพาะผู้ที่อยู่ในลำดับ result_report_certifiers_json (ไม่ใช้ PLAN_ADMIN ทั้งคณะ)
if ((int)$sid > 0 && function_exists('ensureProjectResultReportSchema')) {
	if (empty($pm_unit_names) && function_exists('getUnitSelect')) {
		foreach (getUnitSelect($db_person) as $__u) {
			$__uc = (string)($__u['code'] ?? '');
			if ($__uc !== '') {
				$pm_unit_names[$__uc] = (string)($__u['name'] ?? '');
			}
		}
	}
	$qReportInbox = @mysqli_query(
		$db_Project,
		"SELECT p.id, p.code_pre_project, p.name_project, p.code_unit, p.status_project,
		        p.notify_result, p.result_report_certifiers_json, p.result_report_submitted_at, p.result_report_completed_at,
		        p.result_report_plan_closed_at, p.dates, p.staff_insert
		 FROM pre_project p
		 WHERE p.status='1'
		   AND p.year_project='" . $yearsEsc . "'
		   AND TRIM(IFNULL(p.result_report_submitted_at,'')) <> ''
		   AND (
				TRIM(IFNULL(p.result_report_certifiers_json,'')) <> ''
				OR p.staff_insert='" . (int)$sid . "'
				" . ($pm_appr_is_plan_admin ? "OR (TRIM(IFNULL(p.result_report_completed_at,'')) <> '' AND TRIM(IFNULL(p.result_report_plan_closed_at,'')) = '')" : "") . "
		   )"
	);
	while ($qReportInbox && ($__pr = mysqli_fetch_assoc($qReportInbox))) {
		$__pid = (int)($__pr['id'] ?? 0);
		$__subAt = trim((string)($__pr['result_report_submitted_at'] ?? ''));
		if ($__pid <= 0 || $__subAt === '') {
			continue;
		}
		if (function_exists('pm_project_is_cancelled') && pm_project_is_cancelled($db_Project, $__pid, $__pr)) {
			continue;
		}
		$__certRaw = trim((string)($__pr['result_report_certifiers_json'] ?? ''));
		$__certArr = $__certRaw !== '' ? json_decode($__certRaw, true) : [];
		if (!is_array($__certArr)) {
			$__certArr = [];
		}
		$__isReviewer = false;
		foreach ($__certArr as $__c) {
			if (!is_array($__c)) {
				continue;
			}
			if ((int)($__c['staff_id'] ?? 0) === (int)$sid) {
				$__isReviewer = true;
				break;
			}
		}
		$__isReportSubmitter = ((int)($__pr['staff_insert'] ?? 0) === (int)$sid);
		$__planClosedAtInbox = trim((string)($__pr['result_report_plan_closed_at'] ?? ''));
		$__unitDoneInbox = trim((string)($__pr['result_report_completed_at'] ?? '')) !== '';
		$__isPlanReportReviewer = $pm_appr_is_plan_admin && $__unitDoneInbox && $__planClosedAtInbox === '';
		if (!$__isReviewer && !$__isReportSubmitter && !$__isPlanReportReviewer) {
			continue;
		}
		$__sumQ = @mysqli_query($db_Project, "SELECT IFNULL(SUM(pa.total),0) AS t FROM project_activity_budget pa WHERE pa.code_pre_project='" . $__pid . "' AND pa.status!='99'");
		$__sumR = $__sumQ ? mysqli_fetch_assoc($__sumQ) : null;
		$__budget = (float)($__sumR['t'] ?? 0);
		$__uc = (string)($__pr['code_unit'] ?? '');
		$__unit_name = function_exists('pm_unit_name_from_map')
			? pm_unit_name_from_map($pm_unit_names, $__uc, $db_Project)
			: (($__uc !== '' && isset($pm_unit_names[$__uc])) ? (string)$pm_unit_names[$__uc] : '');
		if ($__unit_name === '') {
			$__unit_name = '-';
		}
		$__budget_src_lbl = function_exists('resolveProjectBudgetSourceForDisplay') ? resolveProjectBudgetSourceForDisplay($db_Project, $__pid) : '-';
		$__requiredList = [];
		$__seqNo = 0;
		foreach ($__certArr as $__c) {
			if (!is_array($__c)) {
				continue;
			}
			$__seqNo++;
			$__so = (int)($__c['sort_order'] ?? 0);
			if ($__so <= 0) {
				$__so = $__seqNo;
			}
			if ((int)($__c['staff_id'] ?? 0) > 0) {
				$__requiredList[] = $__so;
			}
		}
		sort($__requiredList, SORT_NUMERIC);
		$__decRepBySo = [];
		$qRepDec = @mysqli_query($db_Project, "SELECT sort_order, decision FROM project_result_report_certifier_decision WHERE project_id='" . (int)$__pid . "'");
		while ($qRepDec && ($__rd = mysqli_fetch_assoc($qRepDec))) {
			$__sod = (int)($__rd['sort_order'] ?? 0);
			if ($__sod > 0) {
				$__decRepBySo[$__sod] = (string)($__rd['decision'] ?? '');
			}
		}
		$__completedAt = trim((string)($__pr['result_report_completed_at'] ?? ''));
		$__anyRejected = false;
		$__allApproved = !empty($__requiredList);
		foreach ($__requiredList as $__soReq) {
			$__dv = (string)($__decRepBySo[$__soReq] ?? '');
			if ($__dv === 'rejected') {
				$__anyRejected = true;
			}
			if ($__dv !== 'approved') {
				$__allApproved = false;
			}
		}
		$__status_mod = 'report_pending';
		$__status_txt = 'รอตรวจสอบรายงานผล';
		$__status_icon = 'fa-hourglass-half';
		$__status_group = 'pending';
		if (function_exists('pm_resolve_ab_status')) {
			$__abRep = pm_resolve_ab_status($__pr, [
				'db' => $db_Project,
				'report_rejected' => !empty($__anyRejected),
				'report_done' => $__allApproved ? count($__requiredList) : 0,
				'report_total' => count($__requiredList),
			]);
			$__status_mod = (string)($__abRep['mod'] ?? 'b4');
			$__status_txt = (string)($__abRep['label'] ?? 'B4 อยู่ระหว่างตรวจรายงาน');
			$__status_icon = (string)($__abRep['icon'] ?? 'fa-hourglass-half');
			if (in_array($__status_mod, ['b5', 'approved'], true) || $__planClosedAtInbox !== '') {
				$__status_group = 'done';
			} elseif (in_array($__status_mod, ['b4_done'], true) || ($__completedAt !== '' && $__planClosedAtInbox === '')) {
				$__status_group = $__isPlanReportReviewer ? 'pending' : 'done';
			} elseif (in_array($__status_mod, ['b_fix', 'returned'], true) || !empty($__anyRejected)) {
				$__status_group = 'returned';
			} else {
				$__status_group = 'pending';
			}
		} elseif ($__planClosedAtInbox !== '') {
			$__status_mod = 'b5';
			$__status_txt = 'B5 ปิดโครงการ';
			$__status_icon = 'fa-check-circle';
			$__status_group = 'done';
		} elseif ($__completedAt !== '' || $__allApproved) {
			$__status_mod = 'b4_done';
			$__status_txt = 'B4 รายงานผลเรียบร้อย';
			$__status_icon = 'fa-check-circle';
			$__status_group = $__isPlanReportReviewer ? 'pending' : 'done';
		} elseif ($__anyRejected) {
			$__status_mod = 'returned';
			$__status_txt = 'ตีกลับรายงานผล';
			$__status_icon = 'fa-undo';
			$__status_group = 'returned';
		}
		$__nm = trim((string)($__pr['name_project'] ?? ''));
		$__subject = 'รายงานผลโครงการ ' . ($__nm !== '' ? $__nm : '…');
		if ($__unit_name !== '' && $__unit_name !== '-') {
			$__subject .= ' (' . $__unit_name . ')';
		}
		$plan_report_projects[] = [
			'id' => $__pid,
			'code_pre_project' => (string)($__pr['code_pre_project'] ?? ''),
			'name_project' => (string)($__pr['name_project'] ?? ''),
			'unit_name' => $__unit_name,
			'budget' => $__budget,
			'budget_source' => $__budget_src_lbl,
			'dates' => (string)($__pr['result_report_submitted_at'] ?? ($__pr['dates'] ?? '')),
			'staff_insert' => (int)($__pr['staff_insert'] ?? 0),
			'subject' => $__subject,
			'status_mod' => $__status_mod,
			'status_txt' => $__status_txt,
			'status_icon' => $__status_icon,
			'status_group' => $__status_group,
			'objective_key' => ($__isReviewer || $__isPlanReportReviewer) ? 'review' : 'propose',
			'is_submitter' => $__isReportSubmitter ? 1 : 0,
			'is_reviewer' => ($__isReviewer || $__isPlanReportReviewer) ? 1 : 0,
		];
	}
}

$plan_change_projects = [];
if ((int)$sid > 0) {
	if (function_exists('pm_ensure_change_request_schema')) {
		pm_ensure_change_request_schema($db_Project);
	}
	if (empty($pm_unit_names) && function_exists('getUnitSelect')) {
		foreach (getUnitSelect($db_person) as $__u) {
			$__uc = (string)($__u['code'] ?? '');
			if ($__uc !== '') {
				$pm_unit_names[$__uc] = (string)($__u['name'] ?? '');
			}
		}
	}
	$qChgInbox = @mysqli_query(
		$db_Project,
		"SELECT r.id AS request_id, r.project_id, r.reason, r.certifiers_json, r.approvers_json, r.req_status,
		        r.project_name, r.project_code, r.budget_source, r.budget_amount, r.staff_insert, r.created_at,
		        r.plan_rechain_submitted_at, r.plan_rechain_staff_id, r.applied_at, r.applied_by_staff_id,
		        p.code_pre_project, p.name_project, p.code_unit, p.year_project
		 FROM project_change_request r
		 INNER JOIN pre_project p ON p.id = r.project_id AND p.status='1'
		 WHERE r.status='1'
		   AND p.year_project='" . $yearsEsc . "'
		   AND r.req_status IN ('pending','waiting_plan','pending_approve','approved','rejected')"
	);
	while ($qChgInbox && ($__cr = mysqli_fetch_assoc($qChgInbox))) {
		$__rid = (int)($__cr['request_id'] ?? 0);
		$__pid = (int)($__cr['project_id'] ?? 0);
		if ($__rid <= 0 || $__pid <= 0) {
			continue;
		}
		if (function_exists('pm_project_is_cancelled') && pm_project_is_cancelled($db_Project, $__pid, $__cr)) {
			continue;
		}
		$__reqSt = trim((string)($__cr['req_status'] ?? ''));
		$parseIds = static function ($json) {
			$out = [];
			$arr = json_decode((string)$json, true);
			if (!is_array($arr)) {
				return $out;
			}
			foreach ($arr as $c) {
				if (!is_array($c)) {
					continue;
				}
				$sidC = (int)($c['staff_id'] ?? 0);
				if ($sidC > 0) {
					$out[$sidC] = true;
				}
			}
			return $out;
		};
		$__isCert = isset($parseIds($__cr['certifiers_json'] ?? '')[(int)$sid]);
		$__isAppr = isset($parseIds($__cr['approvers_json'] ?? '')[(int)$sid]);
		$__isSubmitter = ((int)($__cr['staff_insert'] ?? 0) === (int)$sid);
		$__isPlanPick = $pm_appr_is_plan_admin && $__reqSt === 'waiting_plan';
		$__appliedAt = trim((string)($__cr['applied_at'] ?? ''));
		$__isPlanApply = $pm_appr_is_plan_admin && $__reqSt === 'approved' && $__appliedAt === '';
		$__isPlanFollow = $pm_appr_is_plan_admin && in_array($__reqSt, ['pending_approve', 'approved'], true);
		if (!$__isCert && !$__isAppr && !$__isSubmitter && !$__isPlanPick && !$__isPlanApply && !$__isPlanFollow) {
			continue;
		}
		$__uc = (string)($__cr['code_unit'] ?? '');
		$__unit_name = function_exists('pm_unit_name_from_map')
			? pm_unit_name_from_map($pm_unit_names, $__uc, $db_Project)
			: (($__uc !== '' && isset($pm_unit_names[$__uc])) ? (string)$pm_unit_names[$__uc] : '');
		if ($__unit_name === '') {
			$__unit_name = '-';
		}
		$__nm = trim((string)($__cr['project_name'] ?? ($__cr['name_project'] ?? '')));
		$__subject = 'ขอเปลี่ยนแปลงโครงการ ' . ($__nm !== '' ? $__nm : '…');
		if ($__unit_name !== '' && $__unit_name !== '-') {
			$__subject .= ' (' . $__unit_name . ')';
		}
		$__status_mod = 'changing';
		$__status_txt = 'เปลี่ยนแปลง';
		$__status_icon = 'fa-exchange-alt';
		$__status_group = 'pending';
		$__obj = ($__isCert || $__isAppr || $__isPlanPick || $__isPlanApply || $__isPlanFollow) ? 'review' : 'propose';
		if ($__reqSt === 'approved' && $__appliedAt !== '') {
			$__status_mod = 'approved';
			$__status_txt = 'แก้ไขโครงการแล้ว';
			$__status_icon = 'fa-check-circle';
			$__status_group = 'done';
		} elseif ($__reqSt === 'approved') {
			$__status_mod = 'changing';
			$__status_txt = 'รอหน่วยแผนแก้ไขโครงการ';
			$__status_icon = 'fa-edit';
			$__status_group = 'pending';
		} elseif ($__reqSt === 'rejected') {
			$__status_mod = 'returned';
			$__status_txt = 'ตีกลับการเปลี่ยนแปลง';
			$__status_icon = 'fa-undo';
			$__status_group = 'returned';
		} elseif ($__isPlanPick || $__reqSt === 'waiting_plan') {
			$__status_txt = 'รอหน่วยแผนเลือกผู้อนุมัติ';
			$__status_icon = 'fa-hourglass-half';
		} elseif ($__reqSt === 'pending_approve') {
			$__status_txt = 'รออนุมัติเปลี่ยนแปลง';
			$__status_icon = 'fa-hourglass-half';
		}
		$plan_change_projects[] = [
			'id' => $__pid,
			'request_id' => $__rid,
			'code_pre_project' => (string)($__cr['code_pre_project'] ?? ($__cr['project_code'] ?? '')),
			'name_project' => $__nm,
			'unit_name' => $__unit_name,
			'budget' => (float)($__cr['budget_amount'] ?? 0),
			'budget_source' => (string)($__cr['budget_source'] ?? '-'),
			'dates' => (string)($__cr['created_at'] ?? ''),
			'staff_insert' => (int)($__cr['staff_insert'] ?? 0),
			'subject' => $__subject,
			'status_mod' => $__status_mod,
			'status_txt' => $__status_txt,
			'status_icon' => $__status_icon,
			'status_group' => $__status_group,
			'objective_key' => $__obj,
			'is_submitter' => !empty($__isSubmitter) ? 1 : 0,
			'is_reviewer' => (!empty($__isCert) || !empty($__isAppr) || !empty($__isPlanPick) || !empty($__isPlanApply) || !empty($__isPlanFollow)) ? 1 : 0,
		];
	}
}

$plan_cancel_projects = [];
if ((int)$sid > 0) {
	if (function_exists('pm_ensure_cancel_request_schema')) {
		pm_ensure_cancel_request_schema($db_Project);
	}
	if (empty($pm_unit_names) && function_exists('getUnitSelect')) {
		foreach (getUnitSelect($db_person) as $__u) {
			$__uc = (string)($__u['code'] ?? '');
			if ($__uc !== '') {
				$pm_unit_names[$__uc] = (string)($__u['name'] ?? '');
			}
		}
	}
	$qCanInbox = @mysqli_query(
		$db_Project,
		"SELECT r.id AS request_id, r.project_id, r.reason, r.req_status, r.staff_insert, r.created_at, r.submitted_at,
		        r.project_name, r.project_code, r.budget_source, r.budget_amount,
		        p.code_pre_project, p.name_project, p.code_unit, p.year_project
		 FROM project_cancel_request r
		 INNER JOIN pre_project p ON p.id = r.project_id AND p.status='1'
		 WHERE r.status='1'
		   AND p.year_project='" . $yearsEsc . "'
		   AND r.req_status IN ('pending','approved','rejected')"
	);
	while ($qCanInbox && ($__cr = mysqli_fetch_assoc($qCanInbox))) {
		$__rid = (int)($__cr['request_id'] ?? 0);
		$__pid = (int)($__cr['project_id'] ?? 0);
		if ($__rid <= 0 || $__pid <= 0) {
			continue;
		}
		$__reqSt = trim((string)($__cr['req_status'] ?? ''));
		$__isSubmitter = ((int)($__cr['staff_insert'] ?? 0) === (int)$sid);
		$__isPlan = !empty($pm_appr_is_plan_admin);
		if (!$__isSubmitter && !$__isPlan) {
			continue;
		}
		$__uc = (string)($__cr['code_unit'] ?? '');
		$__unit_name = function_exists('pm_unit_name_from_map')
			? pm_unit_name_from_map($pm_unit_names, $__uc, $db_Project)
			: (($__uc !== '' && isset($pm_unit_names[$__uc])) ? (string)$pm_unit_names[$__uc] : '');
		if ($__unit_name === '') {
			$__unit_name = '-';
		}
		$__nm = trim((string)($__cr['project_name'] ?? ($__cr['name_project'] ?? '')));
		$__subject = 'ขอยกเลิกโครงการ ' . ($__nm !== '' ? $__nm : '…');
		if ($__unit_name !== '' && $__unit_name !== '-') {
			$__subject .= ' (' . $__unit_name . ')';
		}
		$__status_mod = 'cancel_pending';
		$__status_txt = 'ขอยกเลิก';
		$__status_icon = 'fa-ban';
		$__status_group = 'pending';
		$__obj = $__isPlan && $__reqSt === 'pending' ? 'review' : 'propose';
		if ($__reqSt === 'approved') {
			$__status_mod = 'cancelled';
			$__status_txt = 'ยกเลิก';
			$__status_icon = 'fa-ban';
			$__status_group = 'done';
		} elseif ($__reqSt === 'rejected') {
			$__status_mod = 'returned';
			$__status_txt = 'ตีกลับการยกเลิก';
			$__status_icon = 'fa-undo';
			$__status_group = 'returned';
		}
		$plan_cancel_projects[] = [
			'id' => $__pid,
			'request_id' => $__rid,
			'code_pre_project' => (string)($__cr['code_pre_project'] ?? ($__cr['project_code'] ?? '')),
			'name_project' => $__nm,
			'unit_name' => $__unit_name,
			'budget' => (float)($__cr['budget_amount'] ?? 0),
			'budget_source' => (string)($__cr['budget_source'] ?? '-'),
			'dates' => (string)(($__cr['submitted_at'] ?? '') !== '' ? $__cr['submitted_at'] : ($__cr['created_at'] ?? '')),
			'staff_insert' => (int)($__cr['staff_insert'] ?? 0),
			'subject' => $__subject,
			'status_mod' => $__status_mod,
			'status_txt' => $__status_txt,
			'status_icon' => $__status_icon,
			'status_group' => $__status_group,
			'objective_key' => $__obj,
			'is_submitter' => !empty($__isSubmitter) ? 1 : 0,
			'is_reviewer' => !empty($__isPlan) ? 1 : 0,
		];
	}
}

// Plan Admin: หลังแจ้งผล "บรรจุแผนและแก้ไข" และผู้รับผิดชอบยืนยันส่งกลับมาแล้ว → แสดงในหน้าอนุมัติเป็น "พิจารณาการแก้ไข"
$plan_fix_review_projects = [];
if ($pm_appr_is_plan_admin) {
	if (!isset($pm_manage_rows) && function_exists('getManageableProjectList')) {
		$pm_is_unit_admin = function_exists('isUnitAdmin') ? isUnitAdmin($db_Project, $staff_code, $code_unit, (int)$sid) : false;
		$pm_manage_rows = getManageableProjectList($db_Project, $years_select, $sid, $staff_code, $pm_is_unit_admin, $code_unit);
	}
	if (empty($pm_unit_names) && function_exists('getUnitSelect')) {
		$pm_unit_names = [];
		foreach (getUnitSelect($db_person) as $__u) {
			$__uc = (string)($__u['code'] ?? '');
			if ($__uc !== '') {
				$pm_unit_names[$__uc] = (string)($__u['name'] ?? '');
			}
		}
	}
	if (!empty($pm_manage_rows)) {
		foreach ($pm_manage_rows as $__pr) {
			$__pid = (int)($__pr['id'] ?? 0);
			$__st = (string)($__pr['status_project'] ?? '');
			$__nr = trim((string)($__pr['notify_result'] ?? ''));
			$__fixNote = trim((string)($__pr['user_fix_note'] ?? ''));
			if ($__pid <= 0 || $__st !== '8' || $__nr !== 'บรรจุแผนและแก้ไข' || $__fixNote === '') {
				continue;
			}
			$__uc = (string)($__pr['code_unit'] ?? '');
			$__unit_name = function_exists('pm_unit_name_from_map')
				? pm_unit_name_from_map($pm_unit_names, $__uc, $db_Project)
				: (($__uc !== '' && isset($pm_unit_names[$__uc])) ? (string)$pm_unit_names[$__uc] : '');
			if ($__unit_name === '') {
				$__unit_name = '-';
			}
			$__nm = trim((string)($__pr['name_project'] ?? ''));
			$__subject = 'พิจารณาการแก้ไขโครงการ ' . ($__nm !== '' ? $__nm : '…');
			if ($__unit_name !== '' && $__unit_name !== '-') {
				$__subject .= ' (' . $__unit_name . ')';
			}
			$__senderSid = (int)($__pr['user_fix_note_staff_id'] ?? 0);
			if ($__senderSid <= 0) {
				$__senderSid = (int)($__pr['staff_insert'] ?? 0);
			}
			$__dates = trim((string)($__pr['user_fix_note_at'] ?? ''));
			if ($__dates === '') {
				$__dates = (string)($__pr['dates'] ?? '');
			}
			$plan_fix_review_projects[] = [
				'id' => $__pid,
				'code_pre_project' => (string)($__pr['code_pre_project'] ?? ''),
				'name_project' => (string)($__pr['name_project'] ?? ''),
				'unit_name' => $__unit_name,
				'dates' => $__dates,
				'staff_insert' => $__senderSid,
				'subject' => $__subject,
				'fix_by_name' => '',
				'status_mod' => 'a2',
				'status_txt' => 'A2 อยู่ระหว่างพิจารณา งานแผน',
				'status_icon' => 'fa-search',
				'status_group' => 'pending',
				'is_submitter' => 0,
				'is_reviewer' => 1,
			];
		}
	}
}

$pm_appr_read_set = function_exists('pm_approve_inbox_read_key_set')
	? pm_approve_inbox_read_key_set($db_Project, (int)$sid)
	: [];

// ดึงข้อมูลคำขอ/ผู้รับรอง/ผลการรับรองแบบ cache
$requestIds = [];
foreach ($request_list as $r) {
	$rid = (int)($r['request_id'] ?? 0);
	if ($rid > 0) $requestIds[$rid] = $rid;
}
$requestIds = array_values($requestIds);

$certByReq = [];
$decByReq = [];
$certTotalByReq = [];

if (!empty($requestIds)) {
	$in = implode(',', array_map('intval', $requestIds));

	$certSql = "
		SELECT request_id, sort_order, staff_id, position_title
		FROM project_include_request_certifier
		WHERE request_id IN (" . $in . ")
		ORDER BY request_id, sort_order ASC
	";
	$certRes = @mysqli_query($db_Project, $certSql);
	while ($certRes && $cr = mysqli_fetch_assoc($certRes)) {
		$rid = (int)($cr['request_id'] ?? 0);
		$so = (int)($cr['sort_order'] ?? 0);
		if ($rid <= 0 || $so <= 0) continue;
		$certByReq[$rid][$so] = [
			'staff_id' => (int)($cr['staff_id'] ?? 0),
			'position_title' => (string)($cr['position_title'] ?? ''),
		];
	}
	// นับ total per request
	foreach ($certByReq as $rid => $bySo) {
		$certTotalByReq[$rid] = count($bySo);
	}

	$decSql = "
		SELECT request_id, sort_order, staff_id, decision, decided_at
		FROM project_include_request_certifier_decision
		WHERE request_id IN (" . $in . ")
	";
	$decRes = @mysqli_query($db_Project, $decSql);
	while ($decRes && $dr = mysqli_fetch_assoc($decRes)) {
		$rid = (int)($dr['request_id'] ?? 0);
		$so = (int)($dr['sort_order'] ?? 0);
		if ($rid <= 0 || $so <= 0) continue;
		$decByReq[$rid][$so] = [
			'staff_id' => (int)($dr['staff_id'] ?? 0),
			'decision' => (string)($dr['decision'] ?? ''),
			'decided_at' => (string)($dr['decided_at'] ?? ''),
		];
	}

	$lastRejectAtByReq = [];
	$qLastRej = @mysqli_query(
		$db_Project,
		"SELECT request_id, MAX(created_at) AS t
		 FROM project_include_request_action_log
		 WHERE request_id IN (" . $in . ") AND action_type='certifier_rejected'
		 GROUP BY request_id"
	);
	while ($qLastRej && ($lrj = mysqli_fetch_assoc($qLastRej))) {
		$rid = (int)($lrj['request_id'] ?? 0);
		if ($rid > 0) {
			$lastRejectAtByReq[$rid] = trim((string)($lrj['t'] ?? ''));
		}
	}
	foreach ($decByReq as $rid => $bySo) {
		foreach ($bySo as $drow) {
			if ((string)($drow['decision'] ?? '') !== 'rejected') continue;
			$dt = trim((string)($drow['decided_at'] ?? ''));
			if ($dt === '') continue;
			$cur = (string)($lastRejectAtByReq[$rid] ?? '');
			if ($cur === '' || strtotime($dt) > strtotime($cur)) {
				$lastRejectAtByReq[$rid] = $dt;
			}
		}
	}
} else {
	$lastRejectAtByReq = [];
}

// cache ชื่อผู้ส่ง (submitter_staff_id -> staff name)
$staffNameCache = [];
$getStaffNameById = function($staffId) use ($db_person, &$staffNameCache) {
	$staffId = (int)$staffId;
	if ($staffId <= 0) return '-';
	if (isset($staffNameCache[$staffId])) return $staffNameCache[$staffId];
	$q = @mysqli_query($db_person, "SELECT fname, lname FROM staff WHERE id='".$staffId."' LIMIT 1");
	$r = $q ? mysqli_fetch_assoc($q) : null;
	$name = trim((string)(($r['fname'] ?? '') . ' ' . ($r['lname'] ?? '')));
	if ($name === '') $name = 'Staff #' . $staffId;
	$staffNameCache[$staffId] = $name;
	return $name;
};

?>

<style>
	/* --- Header layout: บังคับเครื่องมืออยู่บรรทัดเดียว --- */
	.pm-appr-header{
		display:flex !important;
		align-items:center !important;
		justify-content:space-between !important;
		flex-wrap:nowrap !important;          /* ห้ามขึ้นบรรทัดใหม่ */
		gap: 14px;
	}
	.pm-appr-title{
		flex: 1 1 auto;
		min-width: 0;                         /* ให้หดได้ */
	}
	.pm-appr-title .pm-appr-title-text{
		display:inline-block;
		max-width: 100%;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;              /* ถ้าพื้นที่ไม่พอ ให้ตัด ... แทน */
		vertical-align: bottom;
	}
	.pm-appr-toolbar{
		flex: 0 0 auto;
		display:flex;
		align-items:center;
		justify-content:flex-end;
		gap:10px;
		flex-wrap:nowrap !important;          /* ห้ามขึ้นบรรทัดใหม่ */
		white-space: nowrap;
	}
	.pm-appr-toolbar #projectApproveSearch{
		width: 290px;
	}

	/* แถวปุ่ม รออนุมัติ/ประวัติ + ค้นหา (ขวาสุด) */
	.pm-appr-switch-row{
		display:flex;
		align-items:center;
		justify-content:flex-end;
		gap:12px;
		flex-wrap:wrap;
	}
	.pm-appr-switch-row .pm-group-switch{
		flex: 0 0 auto;
	}
	.pm-group-switch{
		display:inline-flex;
		gap:.45rem;
		padding:.28rem;
		border-radius:999px;
		background:#f3eef8;
	}
	.pm-group-btn{
		border-radius:999px;
		font-weight:600;
		padding:.42rem .95rem;
		text-decoration:none !important;
		font-size:.9rem;
	}
	.pm-group-btn.pm-active-draft{ background:#fef3c7; color:#92400e !important; border:1px solid #fde68a; }
	.pm-group-btn.pm-active-plan{ background:#dcfce7; color:#166534 !important; border:1px solid #bbf7d0; }
	.pm-group-btn.pm-inactive{
		background:#fff;
		color:#4b5563 !important;
		border:1px solid #d1d5db;
	}
	.pm-appr-switch-row .pm-appr-switch-search{
		flex: 0 0 auto;
		display:flex;
		justify-content:flex-end;
	}
	.pm-appr-switch-row .pm-appr-switch-search #projectApproveSearch{
		width: 360px;
	}
	@media (max-width: 991.98px){
		.pm-appr-switch-row .pm-appr-switch-search{ width:100%; }
		.pm-appr-switch-row .pm-appr-switch-search #projectApproveSearch{ width:100%; }
	}

	/* toolbar ให้เหมือนภาพตัวอย่าง */
	#projectApproveSearch{
		border-radius: 14px !important;
		padding: .5rem .95rem !important;
		border: 1px solid #d1d5db !important;
		box-shadow: 0 1px 0 rgba(15,23,42,.02);
		height: 42px;
		width: 290px;                         /* ลดให้พอดีใน header */
		flex: 0 0 auto;
	}

	/* select style: ให้เหมือนหน้า "จัดการโครงการ" (pm-year-select-wrap / pm-year-select) */
	.pm-appr-toolbar .pm-year-select-wrap{
		display: inline-block;
		vertical-align: middle;
		border-radius: .75rem;
		border: 2px solid rgba(146, 108, 184, .35);
		background: linear-gradient(180deg, #faf7fc 0%, #f3eef8 100%);
		box-shadow: 0 10px 18px -16px rgba(146, 108, 184, .45);
		overflow: hidden;
	}
	.pm-appr-toolbar .pm-year-select-wrap:focus-within{
		border-color: #926cb8;
		box-shadow: 0 0 0 .15rem rgba(146, 108, 184, .22);
	}
	.pm-appr-toolbar .pm-year-select{
		min-width: 0;
		height: 42px !important;
		border: none !important;
		background-color: transparent !important;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='10' viewBox='0 0 16 10'%3E%3Cpath fill='none' stroke='%234a3263' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' d='M2 2l6 6 6-6'/%3E%3C/svg%3E");
		background-repeat: no-repeat;
		background-position: right .75rem center;
		background-size: 14px 9px;
		box-shadow: none !important;
		color: #4a3263 !important;
		font-weight: 900 !important;
		padding-left: .85rem !important;
		padding-right: 2.25rem !important;
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
		cursor: pointer;
		white-space: nowrap;
	}
	.pm-appr-toolbar .pm-year-select:focus{ outline: none !important; box-shadow: none !important; }
	.pm-appr-toolbar .pm-year-select::-ms-expand{ display:none; }
	.pm-appr-toolbar .pm-year-select.pm-appr-year{ width: 130px !important; }
	.pm-appr-toolbar .pm-year-select.pm-appr-status{ width: 160px !important; }
		.pm-appr-toolbar .pm-year-select.pm-appr-objective{ width: 170px !important; }
	.pm-appr-tabs{
		display:flex;
		flex-wrap:wrap;
		gap:.35rem;
		background:#f3eef8;
		border-radius:.65rem;
		padding:.35rem;
		margin-bottom:1rem;
	}
	.pm-appr-tab{
		display:inline-flex;
		align-items:center;
		gap:.4rem;
		padding:.45rem .85rem;
		border-radius:.5rem;
		color:#475569;
		font-weight:700;
		font-size:.92rem;
		text-decoration:none !important;
		border:0;
		background:transparent;
		cursor:pointer;
	}
	.pm-appr-tab:hover{ color:#4a3263; }
	.pm-appr-tab.is-active{
		background:#fff;
		color:#4a3263;
		box-shadow:0 1px 4px rgba(74,50,99,.08);
	}
	.pm-appr-tab-badge{
		min-width:1.35rem;
		height:1.35rem;
		padding:0 .35rem;
		border-radius:999px;
		display:inline-flex;
		align-items:center;
		justify-content:center;
		font-size:.72rem;
		font-weight:800;
		background:#e2e8f0;
		color:#334155;
	}
	.pm-appr-tab-badge.is-hot{
		background:#926cb8;
		color:#fff;
	}
	#projectApproveTable tr[data-unread="1"] td{
		background:#faf5ff;
	}
	.pm-resubmit-pill{
		display:inline-flex;
		align-items:center;
		gap:.25rem;
		margin-top:.28rem;
		padding:.12rem .5rem;
		border-radius:999px;
		font-size:.72rem;
		font-weight:800;
		color:#6b21a8;
		background:#f3e8ff;
		border:1px solid #e9d5ff;
	}
	#projectApproveSearch:focus{
		border-color:#926cb8 !important;
		box-shadow: 0 0 0 .18rem rgba(146,108,184,.18) !important;
	}
	.pm-appr-year{
		min-width: 130px;
		width: 130px;
		height: 42px;
		border-radius: 12px !important;
		border: 2px solid rgba(146, 108, 184, .35) !important;
		box-shadow: 0 2px 10px -7px rgba(146,108,184,.45);
		font-weight: 800;
		color:#4a3263;
		background:#fff;
	}
	.pm-appr-year:focus{
		box-shadow: 0 0 0 .18rem rgba(146,108,184,.18) !important;
	}
	.pm-appr-filter{
		min-width: 150px;
		max-width: 160px;
		height: 42px;
		flex: 0 0 auto;
		white-space: nowrap;
		border-radius: 12px !important;
		border: 2px solid rgba(146, 108, 184, .35) !important;
		background:#fff;
		font-weight: 800;
		box-shadow: 0 1px 0 rgba(15,23,42,.02);
	}
	.pm-appr-filter:focus{
		border-color:#926cb8 !important;
		box-shadow: 0 0 0 .18rem rgba(146,108,184,.18) !important;
	}
	@media (max-width: 991.98px){
		#projectApproveSearch{ width: 100% !important; }
		.pm-appr-year{ min-width: 160px; width: 160px; }
		.pm-appr-filter{ min-width: 190px; max-width:none; }
		.pm-appr-toolbar .pm-year-select.pm-appr-year{ width: 160px !important; }
		.pm-appr-toolbar .pm-year-select.pm-appr-status{ width: 190px !important; }
		.pm-appr-toolbar .pm-year-select.pm-appr-objective{ width: 200px !important; }
		.pm-appr-header{ flex-wrap:wrap !important; }
		.pm-appr-toolbar{ flex-wrap:wrap !important; justify-content:flex-start; width:100%; }
		.pm-appr-title .pm-appr-title-text{ white-space: normal; overflow: visible; text-overflow: unset; }
	}

	#projectApproveTable thead th{
		background: #f3eef8 !important;
		color: #4a3263 !important;
		font-weight: 800 !important;
		font-size: .92rem !important;
		border-color: #e8def2 !important;
		vertical-align: middle;
		text-align: center !important;
	}
	#projectApproveTable{
		table-layout: fixed;
		width: 100%;
		max-width: 100%;
	}
	.pm-table-wrap{
		max-width: 100%;
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
	#projectApproveTable thead th.text-center{
		text-align: center !important;
	}
	#projectApproveTable thead th.pm-sort-th-start{
		text-align: center !important;
	}
	/* หัวเรียงลำดับ: สอง flex item ชัดเจน (label + ไอคอน) ไม่กินความกว้างเต็มเซลล์ */
	#projectApproveTable .pm-sortable{
		display:inline-flex;
		flex-direction: row;
		flex-wrap: nowrap;
		align-items: center;
		justify-content: center;
		gap: .35rem;
		white-space: nowrap;
		width: fit-content;
		max-width: 100%;
		box-sizing: border-box;
	}
	#projectApproveTable .pm-sort-label{
		flex: 0 0 auto;
	}
	#projectApproveTable .pm-sort-icons{
		display:inline-flex;
		flex-direction:column;
		line-height:1;
		gap:1px;
		flex-shrink:0;
	}
	#projectApproveTable .pm-sort-btn{
		border:none;
		background:transparent;
		color:#64748b;
		font-size:.72rem;
		padding:0;
		line-height:1;
		cursor:pointer;
	}
	#projectApproveTable .pm-sort-btn:hover{ color:#734d96; }
	#projectApproveTable .pm-sort-btn.active{ color:#734d96; font-weight:900; }

	/* คอลัมน์พอดีจอ: ไม่บังคับ min-width กว้างจนเกิดสกอลล์แนวนอน */
	#projectApproveTable th:nth-child(1),
	#projectApproveTable td:nth-child(1){
		width: 3.2%;
		min-width: 0;
		white-space: nowrap;
		padding-left: .35rem;
		padding-right: .35rem;
	}
	#projectApproveTable th:nth-child(2),
	#projectApproveTable td:nth-child(2){
		width: 200px;
		min-width: 200px;
		white-space: normal;
		text-align: center;
		vertical-align: middle;
		padding-left: .3rem;
		padding-right: .3rem;
	}
	#projectApproveTable th:nth-child(3),
	#projectApproveTable td:nth-child(3){
		width: 8%;
		min-width: 0;
		white-space: nowrap;
		font-size: .9rem;
	}
	#projectApproveTable th:nth-child(4),
	#projectApproveTable td:nth-child(4){
		width: auto;
		min-width: 0;
	}
	#projectApproveTable td:nth-child(4){
		overflow-wrap: anywhere;
		word-break: break-word;
	}
	#projectApproveTable th:nth-child(5),
	#projectApproveTable td:nth-child(5){
		width: 12%;
		min-width: 0;
		overflow-wrap: anywhere;
		word-break: break-word;
	}
	#projectApproveTable th:nth-child(6),
	#projectApproveTable td:nth-child(6){
		width: 11%;
		min-width: 0;
		white-space: normal;
		vertical-align: middle;
	}
	#projectApproveTable th:nth-child(7),
	#projectApproveTable td:nth-child(7){
		width: 12.5%;
		min-width: 0;
		white-space: normal;
		vertical-align: middle;
		padding-left: .3rem;
		padding-right: .3rem;
	}
	#projectApproveTable .pm-status-badge{
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: .22rem;
		width: auto;
		min-width: 12.5rem;
		max-width: 16rem;
		height: auto;
		min-height: 2.15rem;
		padding: .16rem .36rem;
		border-radius: 999px;
		font-weight: 600;
		font-size: .7rem;
		line-height: 1.15;
		letter-spacing: 0;
		border: 1px solid transparent;
		box-shadow: none;
		white-space: normal;
		box-sizing: border-box;
		text-align: center;
		overflow: hidden;
	}
	#projectApproveTable .pm-status-badge i{ font-size: .66rem; opacity: .85; flex-shrink: 0; }
	#projectApproveTable .pm-status-badge:hover{ transform: none; filter: none; }
	#projectApproveTable .pm-status-badge.pm-ab-chip{
		justify-content: flex-start;
		min-width: 0;
		max-width: 18.5rem;
		min-height: 2.45rem;
		padding: .22rem .78rem .22rem .22rem;
		gap: .42rem;
		text-align: left;
		overflow: visible;
	}

	/* ปุ่มแก้ไขใต้สถานะ (กรณีตีกลับ) */
	#projectApproveTable .pm-status-cell{
		display:flex;
		flex-direction:column;
		align-items:center;
		gap:.38rem;
		width:100%;
	}
	#projectApproveTable .pm-btn-edit-returned{
		display:inline-flex;
		align-items:center;
		justify-content:center;
		gap:.35rem;
		padding:.28rem .78rem;
		border-radius:.45rem;
		font-weight:600;
		font-size:.78rem;
		line-height:1.1;
		border:1px solid #f3e8c8;
		background:#fff;
		color:#b45309;
		box-shadow: none;
	}
	#projectApproveTable .pm-btn-edit-returned:hover{
		background:#fafafa;
	}
	#projectApproveTable .pm-btn-edit-returned:focus{
		outline:none;
		box-shadow: 0 0 0 .18rem rgba(146, 108, 184, .18);
	}
	#projectApproveTable .pm-returned-mini{
		display:inline-flex;
		align-items:center;
		gap:.35rem;
		font-weight:900;
		font-size:.78rem;
		color:#ea580c;
		white-space:nowrap;
	}
	#projectApproveTable .pm-returned-mini i{ font-size:.82rem; opacity:.95; }

	#projectApproveTable .pm-status-badge.pm-status-queue{
		background: #f3e8ff;
		color: #6b21a8;
		border-color: #e9d5ff;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-included_plan{
		background: #ccfbf1;
		color: #0f766e;
		border-color: #99f6e4;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-pending{
		background: #fef3c7;
		color: #92400e;
		border-color: #fde68a;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-consider_fix{
		background: #dcfce7;
		color: #166534;
		border-color: #bbf7d0;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-report_pending{
		background: #fef9c3;
		color: #854d0e;
		border-color: #fde68a;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-done{
		background: #e0f2fe;
		color: #1e40af;
		border-color: #bae6fd;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-returned{
		background: #ffedd5;
		color: #9a3412;
		border-color: #fed7aa;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-approved{
		background: #dcfce7;
		color: #166534;
		border-color: #bbf7d0;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-changing{
		background: #ffedd5;
		color: #9a3412;
		border-color: #fed7aa;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-cancel_pending{
		background: #fee2e2;
		color: #991b1b;
		border-color: #fecaca;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-cancelled{
		background: #fee2e2;
		color: #7f1d1d;
		border-color: #fecaca;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-rejected{
		background: #fee2e2;
		color: #991b1b;
		border-color: #fecaca;
		box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-a0,
	#projectApproveTable .pm-status-badge.pm-status-a1,
	#projectApproveTable .pm-status-badge.pm-status-b1{
		background: #fff6de; color: #c48a12; border-color: #f0d48a; box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-a2,
	#projectApproveTable .pm-status-badge.pm-status-b2{
		background: #f4eefb; color: #5b3a8c; border-color: #e0d0f2; box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-a_fix,
	#projectApproveTable .pm-status-badge.pm-status-b_fix{
		background: #fff1e6; color: #d35400; border-color: #f5c9a6; box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-a_reject{
		background: #fdecec; color: #d32f2f; border-color: #f5c2c2; box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-b3{
		background: #e8f4fc; color: #2b8bb8; border-color: #c5e4f5; box-shadow: none;
	}
	#projectApproveTable .pm-status-badge.pm-status-b4,
	#projectApproveTable .pm-status-badge.pm-status-b4_done,
	#projectApproveTable .pm-status-badge.pm-status-b5{
		background: #e8f6ea; color: #2e7d32; border-color: #c5e6c8; box-shadow: none;
	}
	#projectApproveTable tbody td{ vertical-align: top; padding-top: .55rem; padding-bottom: .55rem; }
	#projectApproveTable tbody tr:hover{ background: #fff !important; }
	#projectApproveTable .pm-project-name{
		font-weight: 400;
		color: #334155;
		line-height: 1.35;
		overflow-wrap: anywhere;
		word-break: break-word;
	}
	#projectApproveTable .pm-project-head{ display:block; margin-top:.12rem; font-size:.72rem; color:#64748b; }
	#projectApproveTable .pm-action-wrap{
		display:flex;
		flex-direction: column;
		align-items: stretch;
		gap: .3rem;
		width: 100%;
		max-width: 118px;
		margin: 0 auto;
	}
	#projectApproveTable .pm-action-wrap .btn{
		border-radius:.45rem;
		font-weight:600;
		font-size: .78rem;
		padding: .28rem .45rem;
		line-height: 1.2;
		white-space: nowrap;
		width: 100%;
		background:#fff !important;
		box-shadow: none !important;
	}
	@media (max-width: 1199.98px){
		#projectApproveTable th:nth-child(2),
		#projectApproveTable td:nth-child(2){ width: 12%; }
		#projectApproveTable th:nth-child(5),
		#projectApproveTable td:nth-child(5){ width: 11%; }
		#projectApproveTable th:nth-child(6),
		#projectApproveTable td:nth-child(6){ width: 10%; }
		#projectApproveTable th:nth-child(7),
		#projectApproveTable td:nth-child(7){ width: 13%; }
		#projectApproveTable .pm-action-wrap{ max-width: 108px; }
		#projectApproveTable .pm-status-badge{ font-size: .66rem; padding: .12rem .3rem; }
		.pm-obj-badge{ font-size: .68rem; padding: .26rem .42rem; }
	}
	@media (max-width: 991.98px){
		#projectApproveTable thead th{ font-size: .82rem !important; }
		#projectApproveTable tbody td{ font-size: .9rem; }
		#projectApproveTable th:nth-child(3),
		#projectApproveTable td:nth-child(3){ width: 7%; }
		#projectApproveTable .pm-action-wrap .btn{ font-size: .72rem; padding: .24rem .35rem; }
	}
	#projectApproveTable.pm-approve-plan-table th:nth-child(2),
	#projectApproveTable.pm-approve-plan-table td:nth-child(2),
	#projectApproveTable.pm-approve-plan-table th:nth-child(3),
	#projectApproveTable.pm-approve-plan-table td:nth-child(3),
	#projectApproveTable.pm-approve-plan-table th:nth-child(4),
	#projectApproveTable.pm-approve-plan-table td:nth-child(4),
	#projectApproveTable.pm-approve-plan-table th:nth-child(5),
	#projectApproveTable.pm-approve-plan-table td:nth-child(5),
	#projectApproveTable.pm-approve-plan-table th:nth-child(6),
	#projectApproveTable.pm-approve-plan-table td:nth-child(6),
	#projectApproveTable.pm-approve-plan-table th:nth-child(7),
	#projectApproveTable.pm-approve-plan-table td:nth-child(7){
		min-width: 0 !important;
		max-width: none !important;
	}
	#projectApproveTable.pm-approve-plan-table .pm-action-wrap{
		flex-direction: column;
		align-items: stretch;
		flex-wrap: nowrap;
		gap: .3rem;
		max-width: 118px;
		margin: 0 auto;
	}
	#projectApproveTable.pm-approve-plan-table .pm-action-wrap .btn + .btn{
		margin-left: 0 !important;
	}
	#projectApproveTable.pm-approve-plan-table .pm-action-wrap .btn{
		min-width: 0;
		width: 100%;
	}
	.pm-ellipsis-btn{
		border: 1px solid rgba(148,163,184,.65);
		background:#fff;
		border-radius:.55rem;
		padding:.25rem .5rem;
		font-weight:900;
	}
	.pm-ellipsis-btn:hover{ background:#f8fafc; }

	/* Callout ปุ่มแก้ไขทั้งหมดใน modal */
	#mdEditAllWrap{
		border:1px solid rgba(249,115,22,.22);
		border-radius:14px;
		background: linear-gradient(135deg, rgba(255, 247, 237, 1) 0%, rgba(255, 251, 235, 1) 65%, rgba(255, 255, 255, 1) 100%);
		padding: .75rem .85rem;
		text-align:right;
	}
	/* กล่องชี้แจงผู้ยื่นเสนอ (แสดงเฉพาะเมื่อถูกตีกลับ) */
	#mdSubmitterReplyWrap{
		border:1px solid rgba(249,115,22,.20);
		border-radius:16px;
		background: linear-gradient(135deg, rgba(255, 247, 237, 1) 0%, rgba(255, 251, 235, 1) 65%, rgba(255, 255, 255, 1) 100%);
		padding: .85rem .95rem;
	}
	#mdSubmitterReplyWrap .md-reply-title{
		font-weight:950;
		color:#9a3412;
	}
	#mdSubmitterReplyWrap .md-reply-title i{ color:#ea580c; margin-right:.4rem; }
	#mdSubmitterReplyWrap .md-reply-actions{
		display:flex;
		justify-content:flex-end;
		margin-top:.65rem;
	}
	#mdActionLogWrap{
		border:1px dashed rgba(59,130,246,.35);
		border-radius:16px;
		background: linear-gradient(135deg, rgba(239,246,255,1) 0%, rgba(255,255,255,1) 65%);
		padding:.85rem .95rem;
	}
	#mdActionLogWrap .md-log-item{
		display:flex;
		gap:.6rem;
		padding:.55rem .6rem;
		border-radius:12px;
		background:#fff;
		border:1px solid rgba(148,163,184,.35);
		margin-bottom:.5rem;
	}
	#mdActionLogWrap .md-log-item:last-child{ margin-bottom:0; }
	#mdActionLogWrap .md-log-dot{
		width:10px; height:10px; border-radius:999px;
		background:#3b82f6; margin-top:.35rem; flex:0 0 auto;
	}
	#mdActionLogWrap .md-log-title{ font-weight:950; color:#0f172a; }
	#mdActionLogWrap .md-log-meta{ font-size:.82rem; color:#64748b; margin-top:.15rem; }
	#mdActionLogWrap .md-log-note{ margin-top:.25rem; white-space:pre-line; }
	#mdSubmitterReply{
		border-radius: 14px;
		border-color: rgba(249,115,22,.25);
	}
	#mdSubmitterReply:focus{
		border-color: rgba(249,115,22,.55);
		box-shadow: 0 0 0 .18rem rgba(249,115,22,.18);
	}
	#mdSubmitterReplySave{
		border-radius: 12px;
		font-weight: 950;
		box-shadow: 0 14px 22px -18px rgba(124, 45, 18, .55);
	}
	#mdSubmitterReplySave:hover{ transform: translateY(-1px); filter:saturate(1.05); }
	#mdSubmitterReplySave:focus{ box-shadow: 0 0 0 .18rem rgba(249,115,22,.20), 0 14px 22px -18px rgba(124, 45, 18, .55); }
	#mdSubmitterReplyMsg{
		border-radius: 14px;
		padding: .55rem .75rem;
		font-weight: 800;
	}
	#mdSubmitterReplyMsg.alert-danger{ background:#fff1f2; border:1px solid rgba(244,63,94,.25); color:#9f1239; }
	#mdSubmitterReplyMsg.alert-success{ background:#ecfdf5; border:1px solid rgba(16,185,129,.22); color:#065f46; }
	#mdEditAllBtn{
		border-radius: 12px;
		font-weight: 950;
		box-shadow: 0 14px 22px -18px rgba(124, 45, 18, .55);
	}
	#mdEditAllBtn:hover{ filter:saturate(1.05); transform: translateY(-1px); }
	#mdEditAllBtn:focus{ box-shadow: 0 0 0 .18rem rgba(245, 158, 11, .22), 0 14px 22px -18px rgba(124, 45, 18, .55); }

	/* badge วัตถุประสงค์ */
	.pm-obj-badge{
		display:inline-flex;
		align-items:center;
		justify-content:center;
		gap:.28rem;
		padding:.3rem .55rem;
		border-radius:999px;
		font-weight:600;
		font-size:.72rem;
		line-height:1.2;
		border:1px solid transparent;
		box-shadow: none;
		white-space: normal;
		max-width: 100%;
		text-align: center;
		box-sizing: border-box;
	}
	.pm-obj-badge i{ font-size:.78rem; opacity:.85; }
	.pm-obj-badge.pm-obj-propose{
		background: #e0f2fe;
		color:#1e40af;
		border-color: #bae6fd;
	}
	.pm-obj-badge.pm-obj-review{
		background: #fef9c3;
		color:#854d0e;
		border-color: #fde68a;
	}
	.pm-obj-badge.pm-obj-notify{
		background: #ccfbf1;
		color:#0f766e;
		border-color: #99f6e4;
	}

	/* modal รายละเอียดคำขอ */
	#modalRequestDetail .modal-dialog{ max-width: 980px; }
	#modalRequestDetail .modal-header{
		display: flex;
		flex-wrap: nowrap;
		align-items: flex-start;
	}
	#modalRequestDetail .modal-header .md-title{
		flex: 1 1 auto;
		min-width: 0;
		margin-right: .5rem;
		padding-right: .25rem;
	}
	#modalRequestDetail .modal-header .close{
		flex: 0 0 auto;
		position: relative;
		z-index: 5;
		pointer-events: auto;
	}
	#modalRequestDetail .md-title{
		font-weight: 950;
		letter-spacing: .2px;
	}
	#modalRequestDetail label{
		font-weight: 900;
		color:#111827;
	}
	#modalRequestDetail .form-control{
		background:#fff;
		color:#4a3263;
		font-weight: 700;
	}
	#modalRequestDetail .form-control[readonly]{
		opacity: 1;
	}
	#modalRequestDetail .md-body{
		line-height: 1.6;
		white-space: pre-line;
	}
	#modalRequestDetail .md-section-above-table .text-110{
		line-height: 1.45;
		font-weight: 400 !important;
	}
	#modalRequestDetail .md-section-above-table .font-weight-bold{
		font-weight: 400 !important;
	}
	#modalRequestDetail .md-summary-table thead th{
		background:#f3eef8;
		color:#4a3263;
		font-weight: 400;
	}
	#modalRequestDetail .md-summary-table td, 
	#modalRequestDetail .md-summary-table th{
		vertical-align: middle;
	}
	#modalRequestDetail .md-summary-table td.align-top{
		vertical-align: top !important;
	}
	#modalRequestDetail .md-summary-table.md-operate{
		border: 1px solid #e8def2;
		border-radius: 10px;
		overflow: hidden;
		background: #ffffff;
	}
	#modalRequestDetail .md-summary-table.md-operate thead th{
		background:#f3eef8;
		color:#4a3263;
		font-size:1.02rem;
		letter-spacing:.01em;
		font-weight: 400;
	}
	#modalRequestDetail .md-summary-table.md-operate tbody td{
		padding: .9rem .85rem;
		font-size: 1.05rem;
		border-color: #e8def2;
		font-weight: 400;
	}
	#modalRequestDetail .md-summary-table.md-operate tbody td:first-child{
		width: 220px;
		font-weight: 400;
		background: #faf7fc;
		color: #4a3263;
	}
	#modalRequestDetail .md-summary-table.md-operate tbody td:last-child{
		font-weight: 400;
		color: #4a3263;
		background: #ffffff;
	}
	#modalRequestDetail .md-sum-proj-list li,
	#modalRequestDetail .md-sum-ind-list li{
		margin: .15rem 0;
		font-size: .925rem;
	}
	/* แผงลำดับการอนุมัติ (หลังตารางความสอดคล้อง) */
	#modalRequestDetail .md-sequential-wrap{ margin-top: 1.25rem; }
	#modalRequestDetail .md-seq-card{
		border-radius: 14px;
		overflow: hidden;
		border: 1px solid #e8def2;
		box-shadow: none;
		background: #faf7fc;
	}
	#modalRequestDetail .md-seq-head{
		background: linear-gradient(180deg, #faf7fc 0%, #f3eef8 100%);
		color: #4a3263;
		font-weight: 400;
		font-size: 1.05rem;
		padding: .85rem 1.15rem;
		letter-spacing: .02em;
		border-bottom: 1px solid #e8def2;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: .65rem;
	}
	#modalRequestDetail .md-seq-head.md-seq-head-toggle{
		cursor: pointer;
		user-select: none;
		width: 100%;
		border: 0;
		text-align: left;
	}
	#modalRequestDetail .md-seq-head.md-seq-head-toggle:hover{
		filter: brightness(.98);
	}
	#modalRequestDetail .md-seq-head-chevron{
		font-size: .85rem;
		opacity: .75;
		transition: transform .15s ease;
		flex-shrink: 0;
	}
	#modalRequestDetail .md-seq-head.is-collapsed .md-seq-head-chevron{
		transform: rotate(-90deg);
	}
	#modalRequestDetail .md-seq-fold-toggle{
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		border: 1px solid #e8def2;
		background: #faf7fc;
		color: #4a3263;
		font-weight: 400;
		font-size: .95rem;
		border-radius: 10px;
		padding: .55rem .8rem;
		margin-bottom: .65rem;
		cursor: pointer;
	}
	#modalRequestDetail .md-seq-fold-toggle .fa-chevron-down{
		transition: transform .15s ease;
	}
	#modalRequestDetail .md-seq-fold-toggle.is-collapsed .fa-chevron-down{
		transform: rotate(-90deg);
	}
	#modalRequestDetail .md-seq-body{
		padding: 1.1rem 1.2rem 1.25rem;
		background: linear-gradient(180deg, #ffffff 0%, #faf5ff 100%);
	}
	/* การ์ดย่อย: การขออนุมัติโครงการ — ธีมเขียว (แยกจากการ์ดลำดับสีฟ้า) */
	#modalRequestDetail .md-pa-rechain-nested{
		border-radius: 14px;
		overflow: hidden;
		border: 1px solid #cfe3bb;
		box-shadow: none;
		background: #f4f8ef;
	}
	#modalRequestDetail .md-pa-rechain-nested-head{
		background: linear-gradient(180deg, #f4f8ef 0%, #dcecc8 100%);
		color: #3d6b28;
		font-weight: 800;
		font-size: 1.05rem;
		padding: .75rem 1.1rem;
		letter-spacing: .02em;
		border-bottom: 1px solid #cfe3bb;
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
		align-items: center;
		gap: .55rem .75rem;
	}
	#modalRequestDetail .md-pa-rechain-nested-head .md-pa-rechain-head-sub{
		font-weight: 650;
		color: rgba(6, 95, 70, 0.88);
	}
	#modalRequestDetail .md-pa-rechain-nested-body{
		padding: 1rem 1.15rem 1.15rem;
		background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
	}
	#modalRequestDetail #mdPaRechainCertRows{
		display: flex;
		flex-direction: column;
		gap: .75rem;
	}
	#modalRequestDetail .md-pa-rc-row{
		background: rgba(255,255,255,.97);
		border: 1px solid rgba(52, 211, 153, 0.45);
		border-radius: 10px;
		padding: .75rem .85rem !important;
		box-shadow: 0 12px 22px -18px rgba(5, 150, 105, 0.28);
		margin-bottom: 0 !important;
	}
	#modalRequestDetail .md-pa-rc-row .md-pa-rc-lbl-inline{
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
		align-items: baseline;
		gap: .35rem .5rem;
		margin-bottom: .35rem;
	}
	#modalRequestDetail .md-pa-rc-row .md-pa-rc-hint{
		font-size: .78rem;
		color: #64748b;
		font-weight: 600;
		white-space: nowrap;
	}
	#modalRequestDetail .md-pa-rc-row .md-pa-rc-fields{ min-width: 0; }
	#modalRequestDetail #mdPlanAdminRechainWrap .select2-selection,
	#modalRequestDetail #mdPlanAdminRechainWrap select.form-control{
		border-radius: .35rem;
		min-height: 38px;
	}
	#modalRequestDetail #mdPlanAdminRechainWrap .select2-container--open{ z-index: 10620 !important; }
	#modalEditOperateCertRow.modal { z-index: 10650 !important; }
	#modalEditOperateCertRow + .modal-backdrop,
	body.modal-open .modal-backdrop.ecr-cert-backdrop { z-index: 10640 !important; }
	#modalEditOperateCertRow .select2-container--open { z-index: 10680 !important; }
	#modalRequestDetail .md-seq-row{ margin-bottom: .55rem; font-size: .95rem; }
	#modalRequestDetail .md-seq-k{
		font-weight: 800;
		color: #4a3263;
		margin-right: .35rem;
	}
	#modalRequestDetail .md-seq-submitter{
		color: #734d96;
		font-weight: 800;
	}
	#modalRequestDetail .md-seq-list{
		margin: .35rem 0 0;
		padding-left: 1.35rem;
		counter-reset: seq;
	}
	#modalRequestDetail .md-seq-list > li{
		margin-bottom: .5rem;
		padding: .55rem .75rem .55rem .35rem;
		border-radius: 10px;
		line-height: 1.35;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: .75rem;
		position: relative;
		padding-left: 2.5rem; /* เผื่อพื้นที่ให้เลขลำดับอยู่นอกกรอบ */
		background: #fff;
		border: 1px solid #e8def2;
		box-shadow: none;
	}
	#modalRequestDetail .md-seq-list > li.md-seq-li-active{
		background: #faf7fc;
		border-color: #e8def2;
		box-shadow: none;
	}
	#modalRequestDetail .md-seq-left{
		display:flex;
		flex-direction: column;
		min-width: 0;
		flex: 1 1 auto;
	}
	#modalRequestDetail .md-seq-step{
		position:absolute;
		left: -14px;
		top: .62rem; /* ให้ตรงกับบรรทัดชื่อ */
		transform: none;
		width: 34px;
		height: 34px;
		border-radius: 999px;
		display:flex;
		align-items:center;
		justify-content:center;
		font-weight: 800;
		color:#4a3263;
		letter-spacing:.02em;
		background: #eef6fb;
		box-shadow: none;
		border: 1px solid #b7d3e8;
	}
	#modalRequestDetail .md-seq-li-active .md-seq-step{
		background: #dbeafe;
		color: #1d4ed8;
	}
	#modalRequestDetail .md-seq-name{
		font-weight: 900;
		color: #0f172a;
		display:flex;
		align-items:center;
		gap:.45rem;
		min-width:0;
	}
	#modalRequestDetail .md-seq-name-text{
		min-width:0;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	#modalRequestDetail .md-seq-edit-inline{
		display:inline-flex;
		align-items:center;
		gap:.25rem;
		flex-shrink:0;
		padding:.12rem .5rem;
		border-radius:999px;
		border:1px solid #f59e0b;
		background:#fff7ed;
		color:#b45309;
		font-size:.72rem;
		font-weight:800;
		line-height:1.1;
		transition:all .15s ease;
	}
	#modalRequestDetail .md-seq-edit-inline:hover{
		background:#ffedd5;
		color:#9a3412;
		border-color:#ea580c;
	}
	#modalRequestDetail .md-seq-per-link{
		font-size: .78rem;
		font-weight: 800;
		vertical-align: middle;
		line-height: 1.2;
		white-space: nowrap;
	}
	#modalRequestDetail .md-seq-per-link:hover{
		text-decoration: underline !important;
	}
	#modalRequestDetail .md-seq-pos{
		font-size: .82rem;
		color: #64748b;
		font-weight: 700;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	#modalRequestDetail .md-seq-meta{
		display:flex;
		flex-wrap: wrap;
		gap: .4rem .45rem;
		margin-top: .35rem;
	}
	#modalRequestDetail .md-seq-chip{
		display:inline-flex;
		align-items:center;
		gap:.35rem;
		padding: .18rem .55rem;
		border-radius: 999px;
		font-size: .78rem;
		font-weight: 700;
		letter-spacing: .01em;
		box-shadow: none;
		border: 1px solid #e8def2;
		max-width: 100%;
	}
	#modalRequestDetail .md-seq-chip i{ opacity:.95; }
	#modalRequestDetail .md-seq-chip-date{
		background: #eef6fb;
		color:#3d7ea3;
		border-color: #b7d3e8;
	}
	#modalRequestDetail .md-seq-chip-note{
		background: #fff7ed;
		color:#c2410c;
		border-color: #fed7aa;
	}
	#modalRequestDetail .md-seq-chip-text{
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	#modalRequestDetail .md-seq-note-line{
		margin-top: .35rem;
		padding: 0;
		background: transparent;
		border: 0;
		box-shadow: none;
		color:#0f172a;
		font-size: .80rem;
		font-weight: 600;
		line-height: 1.55;
		white-space: normal;
		word-break: break-word;
	}
	#modalRequestDetail .md-seq-note-label{
		display:inline-flex;
		align-items:center;
		gap:.35rem;
		font-weight: 950;
		color:#ea580c !important;
		margin-right: .35rem;
	}
	#modalRequestDetail .md-seq-note-label i{
		opacity:.95;
		margin-right: .15rem;
		color:#ea580c !important;
	}
	#modalRequestDetail .md-seq-right{
		display:inline-flex;
		align-items:center;
		flex: 0 0 auto;
		white-space: nowrap;
	}
	#modalRequestDetail .md-seq-right-stack{
		display:flex;
		flex-direction:column;
		align-items:flex-end;
		gap:.35rem;
		height:100%;
	}
	#modalRequestDetail .md-seq-right{ align-self: stretch; }
	#modalRequestDetail .md-seq-pill-edit-bottom{ margin-top: auto; }
	#modalRequestDetail .md-seq-warn{
		font-weight: 950;
		color: #dc2626;
		margin-left: .35rem;
	}
	#modalRequestDetail .md-seq-muted{
		font-weight: 700;
		color: #6b7280;
		margin-left: .35rem;
		font-size: .9rem;
	}
	#modalRequestDetail .md-seq-pill{
		display: inline-flex;
		align-items: center;
		margin-left: .4rem;
		padding: .2rem .55rem;
		border-radius: 999px;
		font-size: .78rem;
		font-weight: 900;
		letter-spacing: .02em;
	}
	#modalRequestDetail .md-seq-pill-ok{
		background: #f4f8ef;
		color: #3d6b28;
		border: 1px solid #cfe3bb;
	}
	#modalRequestDetail .md-seq-pill-no{
		background: #fef2f2;
		color: #991b1b;
		border: 1px solid #fecaca;
	}
	#modalRequestDetail .md-seq-pill-edit{
		background: #fffbeb;
		color: #92400e;
		border: 1px solid #fde68a;
	}
	/* icon-only variant */
	#modalRequestDetail .md-seq-pill-edit.md-seq-pill-icononly i{ margin-right: 0 !important; }
	#modalRequestDetail .md-seq-reply-head{
		display:flex;
		align-items:center;
		flex-wrap:wrap;
		gap:.5rem;
	}

	/* คำชี้แจง: โทนเหลือง + เยื้องขวาเล็กน้อย */
	#modalRequestDetail .md-seq-note-line.md-seq-note-line-reply{
		margin-left: .85rem;
		padding: .25rem .55rem;
		border-radius: 12px;
		background: linear-gradient(135deg, rgba(254, 249, 195, 1), rgba(253, 230, 138, .65));
		border: 1px solid rgba(245, 158, 11, .28);
		color:#92400e;
	}
	/* ข้อเสนอแนะ/ข้อชี้แจง: ให้เล็กลงและเด่นชัด */
	#modalRequestDetail .md-seq-note-line{
		color:#0f172a;
		font-size: .80rem;
	}
	#modalRequestDetail .md-seq-note-line .md-seq-note-label{
		color:#ea580c !important;
	}
	#modalRequestDetail .md-seq-note-line.md-seq-note-line-reply{
		font-size: .78rem;
	}
	/* "ผ่าน" ให้เป็นเขียว (ไม่ใช่ส้มตาม label เดิม) */
	#modalRequestDetail .md-seq-note-line-pass{
		color:#065f46;
	}
	#modalRequestDetail .md-seq-note-line-pass .md-seq-note-label{
		color:#16a34a !important;
	}
	#modalRequestDetail .md-seq-note-line-pass .md-seq-note-label i{
		color:#16a34a !important;
	}
	/* "ข้อเสนอแนะ" ให้ตัวหนังสือแดงอ่อนๆ อ่านชัด */
	#modalRequestDetail .md-seq-note-line-suggest{
		color:#b91c1c;
		background: rgba(255, 241, 242, .55);
		border: 1px solid rgba(244, 63, 94, .22);
		border-radius: 12px;
		padding: .35rem .55rem;
	}
	#modalRequestDetail .md-seq-note-line-suggest .md-seq-note-label{
		color:#e11d48 !important;
	}
	#modalRequestDetail .md-seq-note-line-suggest .md-seq-note-label i{
		color:#e11d48 !important;
	}
	#modalRequestDetail .md-seq-note-line-latest{
		background: linear-gradient(135deg, #fef3c7, #fde68a) !important;
		border: 1px solid rgba(245, 158, 11, .55) !important;
		box-shadow: 0 0 0 2px rgba(245, 158, 11, .14);
	}
	#modalRequestDetail .md-seq-note-line.md-seq-note-line-reply .md-seq-note-label{
		color:#a16207 !important;
	}
	#modalRequestDetail .md-seq-note-line.md-seq-note-line-reply .md-seq-note-label i{
		color:#a16207 !important;
	}
	#modalRequestDetail .md-seq-hr{
		border: 0;
		border-top: 1px solid #e2e8f0;
		margin: .85rem 0 1rem;
	}
	#modalRequestDetail .md-seq-actor-title{
		font-weight: 950;
		color: #0f172a;
		font-size: 1rem;
		margin-bottom: .5rem;
	}
	#modalRequestDetail .md-review-title{
		font-weight: 900;
		color: #1d4ed8;
		font-size: .98rem;
		margin-bottom: .25rem;
	}
	#modalRequestDetail .md-seq-note-label{
		display:block;
		font-weight: 900;
		color: #1e40af;
		margin-bottom: .35rem;
		font-size: .92rem;
	}
	#modalRequestDetail .md-seq-note{
		border-radius: 10px;
		border: 1px solid #93c5fd;
		box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
		resize: vertical;
		min-height: 100px;
	}
	#modalRequestDetail .md-seq-actions{
		display: flex;
		flex-wrap: wrap;
		justify-content: flex-end;
		align-items: center;
		gap: .6rem;
		margin-top: .85rem;
	}
	/* ปุ่มบันทึก (ส่งคำชี้แจงให้หน่วยแผน) ให้ชิดขวาและดูเข้าธีม */
	#modalRequestDetail #mdPlanUnitReplyWrap .md-reply-actions{
		display:flex;
		justify-content:flex-end;
		margin-top: .65rem;
	}
	#modalRequestDetail #mdPlanUnitReplySend{
		font-weight: 900;
		padding: .45rem 1.05rem;
		border-radius: 10px;
		box-shadow: 0 10px 18px -12px rgba(15, 23, 42, 0.55);
	}
	#modalRequestDetail .btn-md-seq{
		font-weight: 900;
		padding: .48rem 1.15rem;
		border-radius: 10px;
		box-shadow: 0 8px 18px -8px rgba(15, 23, 42, 0.35);
	}
	#modalRequestDetail .btn-md-seq-pass{
		background: linear-gradient(135deg, #22c55e, #16a34a) !important;
		border: none !important;
		color: #fff !important;
	}
	#modalRequestDetail .btn-md-seq-back{
		background: linear-gradient(135deg, #f87171, #dc2626) !important;
		border: none !important;
		color: #fff !important;
	}
	#modalRequestDetail .md-seq-hint{
		margin: .75rem 0 0;
		font-size: .82rem;
		color: #64748b;
		font-weight: 600;
		padding: .5rem .65rem;
		border-radius: 8px;
		background: repeating-linear-gradient(
			90deg,
			transparent,
			transparent 6px,
			rgba(139, 92, 246, 0.12) 6px,
			rgba(139, 92, 246, 0.12) 8px
		);
		border: 1px dashed #c4b5fd;
	}
	#modalRequestDetail .md-plan-unit-wrap{
		margin-top: 1rem;
		padding-top: .65rem;
		border-top: 2px solid #a5b4fc;
	}
	#modalRequestDetail .md-plan-unit-head{
		padding: .9rem 1rem .85rem;
		border-radius: 12px;
		background: linear-gradient(145deg, #eef2ff 0%, #ede9fe 45%, #f5f3ff 100%);
		border: 1px solid #a5b4fc;
		box-shadow: 0 6px 20px -8px rgba(67, 56, 202, 0.35);
		margin-bottom: .75rem;
		position: relative;
	}
	#modalRequestDetail .md-plan-unit-head-badges{
		position: absolute;
		top: .85rem;
		right: .95rem;
		display: flex;
		flex-wrap: wrap;
		gap: .4rem .45rem;
		align-items: center;
		justify-content: flex-end;
		text-align: right;
	}
	#modalRequestDetail .md-plan-unit-head-badges .md-plan-unit-chip{
		background: rgba(255,255,255,.85);
	}
	#modalRequestDetail .md-plan-unit-section-title{
		font-size: .84rem;
		font-weight: 900;
		letter-spacing: .03em;
		color: #7c3aed;
		margin-bottom: .4rem;
	}
	#modalRequestDetail .md-plan-unit-title{
		font-size: 1.16rem;
		font-weight: 950;
		color: #312e81;
		letter-spacing: .02em;
		line-height: 1.3;
	}
	#modalRequestDetail #mdPlanUnitStatusLine.md-seq-warn{
		font-size: .95rem;
		font-weight: 950;
		padding: .2rem .55rem;
		border-radius: 8px;
		background: rgba(254, 226, 226, 0.95);
		border: 1px solid #fca5a5;
	}
	#modalRequestDetail .md-plan-unit-officer-line{
		font-size: .93rem;
		margin: .55rem 0 0;
		line-height: 1.55;
	}
	#modalRequestDetail .md-plan-unit-officer-label{
		font-weight: 900;
		color: #4338ca;
	}
	#modalRequestDetail .md-plan-unit-officer-name{
		font-weight: 950;
		color: #0f766e;
	}
	#modalRequestDetail .md-plan-unit-result-card{
		border-radius: 12px;
		padding: .65rem .75rem;
		border: 1px solid rgba(148, 163, 184, .55);
		box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
	}
	#modalRequestDetail .md-plan-unit-pill{
		display:inline-flex;
		align-items:center;
		gap:.35rem;
		font-weight: 950;
		padding: .18rem .55rem;
		border-radius: 999px;
		font-size: .84rem;
		letter-spacing: .01em;
		border: 1px solid transparent;
	}
	#modalRequestDetail .md-plan-unit-pill i{ opacity: .95; }
	#modalRequestDetail .md-plan-unit-pill-ok{
		background: linear-gradient(135deg, #22c55e, #16a34a);
		border-color: rgba(22,163,74,.65);
		color: #ffffff;
		box-shadow: 0 12px 22px -18px rgba(22, 163, 74, .95);
	}
	#modalRequestDetail .md-plan-unit-pill-ok i{ color:#ffffff; }
	#modalRequestDetail .md-plan-unit-pill-warn{
		/* "ตีกลับ" ต้องเด่นเป็นแดง */
		background: #fff7ed;
		border-color: rgba(220,38,38,.30);
		color: #b91c1c;
	}
	#modalRequestDetail .md-plan-unit-pill-no{
		background: #fef2f2;
		border-color: rgba(220,38,38,.28);
		color: #991b1b;
	}
	#modalRequestDetail .md-plan-unit-result-ok{ background: rgba(236, 253, 245, .65); border-color: rgba(34,197,94,.35); }
	#modalRequestDetail .md-plan-unit-result-warn{ background: rgba(255, 247, 237, .75); border-color: rgba(220,38,38,.25); }
	#modalRequestDetail .md-plan-unit-result-no{ background: rgba(254, 242, 242, .75); border-color: rgba(220,38,38,.25); }
	#modalRequestDetail .md-plan-unit-meta{
		display:flex;
		flex-wrap: wrap;
		align-items:center;
		gap: .5rem;
		margin-top: .45rem;
	}
	#modalRequestDetail .md-plan-unit-chip{
		display:inline-flex;
		align-items:center;
		gap:.35rem;
		padding:.14rem .5rem;
		border-radius: 999px;
		font-size: .78rem;
		font-weight: 900;
		background: rgba(255,255,255,.75);
		border: 1px solid rgba(148, 163, 184, .45);
		color:#334155;
	}
	#modalRequestDetail .md-plan-unit-chip i{ opacity:.9; }
	#modalRequestDetail .md-plan-unit-note{
		margin-top: .4rem;
		color:#0f172a;
		font-weight: 750;
	}
	/* ถ้า "ตีกลับ" ให้ข้อความข้อเสนอแนะเป็นสีแดงด้วย */
	#modalRequestDetail .md-plan-unit-result-warn .md-plan-unit-note{
		color:#b91c1c;
	}
	#modalRequestDetail .md-plan-unit-result-warn .md-plan-unit-note i{
		color:#e11d48;
	}
	/* เยื้องกรอบ "ตีกลับ/คำชี้แจง" ในไทม์ไลน์ของหน่วยแผน */
	#modalRequestDetail #mdPlanUnitTimeline .md-seq-note-line.md-seq-note-line-suggest,
	#modalRequestDetail #mdPlanUnitTimeline .md-seq-note-line.md-seq-note-line-reply{
		margin-left: .65rem;
	}
	#modalRequestDetail #mdPlanUnitTimeline .md-seq-note-line.md-seq-note-line-reply{
		margin-left: .95rem;
	}
	#modalRequestDetail .btn-md-plan-return{
		background: linear-gradient(135deg, #fb923c, #ea580c) !important;
		border: none !important;
		color: #fff !important;
	}
	#mdPlanUnitMsg{
		position: relative;
		z-index: 5;
		display: none;
		min-height: 2.25rem;
		font-weight: 800;
		border-width: 2px !important;
	}
	#mdPlanUnitMsg.alert-danger{
		background: #fef2f2 !important;
		color: #991b1b !important;
		border-color: #f87171 !important;
	}
	#mdPlanUnitMsg.alert-success{
		background: #ecfdf5 !important;
		color: #065f46 !important;
		border-color: #34d399 !important;
	}

	/* modal ยืนยัน (สวยๆ) */
	#modalConfirmAction .modal-dialog{ max-width: 460px; }
	#modalConfirmAction.cf-return-wide .modal-dialog{ max-width: 760px; }
	#modalConfirmAction .cf-return-table-wrap{
		max-height: 280px;
		overflow: auto;
		border: 1px solid #d8b4fe;
		border-radius: 10px;
		background: #fff;
	}
	#modalConfirmAction .cf-return-table{
		margin: 0;
		background: #fff;
	}
	#modalConfirmAction .cf-return-table th{
		background: #f8fafc;
		font-weight: 800;
		white-space: nowrap;
		text-align: center;
	}
	#modalConfirmAction .cf-return-table td{
		vertical-align: middle;
		font-weight: 650;
	}
	#modalConfirmAction .cf-return-table textarea.cf-return-note{
		min-width: 180px;
		font-weight: 600;
	}
	/* ให้ modal confirm อยู่เหนือ modal หลักเสมอ (กรณีซ้อน modal + กันชน Bootstrap 4/5) */
	#modalConfirmAction.pm-confirm-manual,
	#modalConfirmAction{ z-index: 200050 !important; }
	.modal-backdrop.confirm-backdrop{ z-index: 200040 !important; opacity: 0.55; }
	#modalConfirmAction, #modalConfirmAction *{ pointer-events: auto; }
	/* ข้อความ error/success ใต้ปุ่ม — ต้องเห็นชัด */
	#mdSeqAjaxMsg{
		position: relative;
		z-index: 5;
		display: none;
		min-height: 2.25rem;
		font-weight: 800;
		border-width: 2px !important;
	}
	#mdSeqAjaxMsg.alert-danger{
		background: #fef2f2 !important;
		color: #991b1b !important;
		border-color: #f87171 !important;
	}
	#mdSeqAjaxMsg.alert-success{
		background: #ecfdf5 !important;
		color: #065f46 !important;
		border-color: #34d399 !important;
	}
	#modalConfirmAction .cf-card{
		border-radius: 16px;
		overflow: hidden;
		border: 1px solid rgba(148, 163, 184, .55);
		box-shadow: 0 28px 60px -40px rgba(15, 23, 42, .6);
	}
	#modalConfirmAction .cf-head{
		padding: 1rem 1.1rem;
		background: linear-gradient(135deg, #f1f5f9 0%, #ffffff 100%);
		border-bottom: 1px solid rgba(148, 163, 184, .35);
		display:flex;
		align-items:center;
		gap:.75rem;
	}
	#modalConfirmAction .cf-icon{
		width: 44px;
		height: 44px;
		border-radius: 14px;
		display:flex;
		align-items:center;
		justify-content:center;
		color:#fff;
		font-size: 1.15rem;
		box-shadow: 0 14px 28px -18px rgba(15, 23, 42, .55);
		flex: 0 0 auto;
	}
	#modalConfirmAction .cf-title{
		font-weight: 950;
		color:#0f172a;
		letter-spacing:.01em;
		font-size: 1.1rem;
		margin:0;
	}
	#modalConfirmAction .cf-body{
		padding: .95rem 1.1rem 1.05rem;
		color:#334155;
		font-weight: 650;
		line-height: 1.55;
	}
	#modalConfirmAction .cf-footer{
		padding: .85rem 1.1rem 1rem;
		display:flex;
		justify-content:flex-end;
		gap:.6rem;
		background: #fff;
		border-top: 1px solid rgba(148, 163, 184, .25);
	}
	#modalConfirmAction .btn-cf{
		font-weight: 900;
		border-radius: 12px;
		padding: .5rem 1.05rem;
		box-shadow: 0 12px 22px -18px rgba(15, 23, 42, .6);
	}
	#modalConfirmAction .btn-cf-cancel{
		background:#fff !important;
		border: 1px solid rgba(148, 163, 184, .75) !important;
		color:#0f172a !important;
	}
	#modalConfirmAction .btn-cf-ok{
		border:none !important;
		color:#fff !important;
	}
	#modalConfirmAction .btn-cf-ok.cf-ok-pass{ background: linear-gradient(135deg, #22c55e, #16a34a) !important; }
	#modalConfirmAction .btn-cf-ok.cf-ok-back{ background: linear-gradient(135deg, #f87171, #dc2626) !important; }
	#modalConfirmAction .cf-icon.cf-ic-pass{ background: linear-gradient(135deg, #22c55e, #16a34a); }
	#modalConfirmAction .cf-icon.cf-ic-back{ background: linear-gradient(135deg, #f87171, #dc2626); }
</style>

<div class="card acard pm-list-card mb-4 shadow-sm border-0">
	<div class="card-header bgc-white border-b-1 brc-default-l2 py-3 pm-appr-header">
		<h5 class="card-title text-primary-d2 mb-0 text-120 pm-appr-title">
			<span class="pm-appr-title-text">
				<i class="fa fa-file-signature mr-2"></i> <?php echo htmlspecialchars((string)$pm_appr_page_title, ENT_QUOTES, 'UTF-8'); ?>
				<span class="text-secondary-d2 text-95 ml-1">
					ประจำปีงบประมาณ
					<span class="badge badge-pill px-3 py-1 ml-1" style="background:#f3eef8; color:#4a3263; font-weight:600; font-size:.9rem; border:1px solid #e8def2;">
						<?php echo htmlspecialchars((string)$years_select, ENT_QUOTES, 'UTF-8'); ?>
					</span>
				</span>
				<?php if ($pm_appr_is_plan_admin && $pm_appr_flow === 'include'): ?>
					<br><small class="text-muted font-weight-normal">แสดงเฉพาะคำขอที่ผู้รับรองทุกลำดับกดผ่านครบแล้ว</small>
				<?php endif; ?>
			</span>
		</h5>
		<div class="pm-appr-toolbar">
			<div class="pm-year-select-wrap">
				<select class="form-control pm-year-select pm-appr-year" onchange="location='?page=project_approve&flow=<?php echo rawurlencode((string)$pm_appr_flow); ?>&tab=<?php echo rawurlencode((string)$pm_appr_tab); ?>&years_select='+this.value">
					<?php foreach ($year_list as $y): ?>
						<option value="<?php echo htmlspecialchars($y['budget_year_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($years_select == ($y['budget_year_name'] ?? '')) ? 'selected' : ''; ?>>
							<?php echo htmlspecialchars($y['budget_year_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
						</option>
					<?php endforeach ?>
				</select>
			</div>
			<div class="pm-year-select-wrap">
				<select id="projectApproveStatusFilter" class="form-control pm-year-select pm-appr-status" title="เลือกสถานะ">
					<option value="">- เลือกสถานะ -</option>
					<option value="pending">รอดำเนินการ / อยู่ระหว่างดำเนินการ</option>
					<option value="done">เสร็จสิ้น</option>
					<option value="returned">ตีกลับ (แก้ไข)</option>
					<?php if ($pm_appr_is_plan_admin): ?>
						<option value="rejected">ไม่อนุมัติ</option>
					<?php endif; ?>
				</select>
			</div>
		</div>
	</div>

	<div class="card-body">
		<div class="pm-appr-tabs" id="pmApprStatusTabs">
			<button type="button" class="pm-appr-tab<?php echo $pm_appr_tab === 'sent' ? ' is-active' : ''; ?>" data-appr-tab="sent">
				<i class="fa fa-folder"></i> เอกสารส่ง
				<span class="pm-appr-tab-badge" data-tab-count="sent">0</span>
			</button>
			<button type="button" class="pm-appr-tab<?php echo $pm_appr_tab === 'pending' ? ' is-active' : ''; ?>" data-appr-tab="pending">
				<i class="fa fa-clock"></i> รอพิจารณา/อนุมัติ
				<span class="pm-appr-tab-badge" data-tab-count="pending">0</span>
			</button>
			<button type="button" class="pm-appr-tab<?php echo $pm_appr_tab === 'done' ? ' is-active' : ''; ?>" data-appr-tab="done">
				<i class="fa fa-check-circle"></i> พิจารณา/อนุมัติเสร็จสิ้น
				<span class="pm-appr-tab-badge" data-tab-count="done">0</span>
			</button>
		</div>
		<div class="mb-3">
			<div class="pm-appr-switch-row">
				<div class="pm-appr-switch-search">
					<input type="text" id="projectApproveSearch" class="form-control" placeholder="ค้นหา : รหัสโครงการ / ชื่อโครงการ / หน่วยงาน">
				</div>
			</div>
		</div>

		<?php if ($msg !== ''): ?>
			<div class="alert alert-success mb-3"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
		<?php endif ?>
		<?php if ($err !== ''): ?>
			<div class="alert alert-danger mb-3"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
		<?php endif ?>
		<div id="pmApprReturnedNotice" class="alert alert-warning mb-3" style="display:none;"></div>

		<div class="table-responsive pm-table-wrap">
			<table class="table table-bordered table-hover table-striped text-95 mb-0" id="projectApproveTable">
				<thead class="bgc-primary-l4 text-primary-d2 text-uppercase text-90">
					<tr>
						<th class="text-center border-0">ที่</th>
						<th class="text-center border-0">
							<div class="pm-sortable justify-content-center">
								<span class="pm-sort-label">สถานะ</span>
								<span class="pm-sort-icons">
									<button type="button" class="pm-sort-btn" data-sort-key="status" data-sort-dir="asc" title="ก-ฮ">▲</button>
									<button type="button" class="pm-sort-btn" data-sort-key="status" data-sort-dir="desc" title="ฮ-ก">▼</button>
								</span>
							</div>
						</th>
						<th class="text-center border-0">
							<div class="pm-sortable justify-content-center">
								<span class="pm-sort-label">วันที่ส่ง</span>
								<span class="pm-sort-icons">
									<button type="button" class="pm-sort-btn" data-sort-key="date" data-sort-dir="asc" title="เก่าไปใหม่">▲</button>
									<button type="button" class="pm-sort-btn" data-sort-key="date" data-sort-dir="desc" title="ใหม่ไปเก่า">▼</button>
								</span>
							</div>
						</th>
						<th class="text-center border-0">
							<div class="pm-sortable justify-content-center">
								<span class="pm-sort-label">เรื่อง</span>
								<span class="pm-sort-icons">
									<button type="button" class="pm-sort-btn" data-sort-key="subject" data-sort-dir="asc" title="ก-ฮ">▲</button>
									<button type="button" class="pm-sort-btn" data-sort-key="subject" data-sort-dir="desc" title="ฮ-ก">▼</button>
								</span>
							</div>
						</th>
						<th class="text-center border-0">
							<div class="pm-sortable justify-content-center">
								<span class="pm-sort-label">ผู้ส่ง</span>
								<span class="pm-sort-icons">
									<button type="button" class="pm-sort-btn" data-sort-key="sender" data-sort-dir="asc" title="ก-ฮ">▲</button>
									<button type="button" class="pm-sort-btn" data-sort-key="sender" data-sort-dir="desc" title="ฮ-ก">▼</button>
								</span>
							</div>
						</th>
						<th class="text-center border-0">
							<div class="pm-sortable">
								<span class="pm-sort-label">วัตถุประสงค์</span>
								<span class="pm-sort-icons">
									<button type="button" class="pm-sort-btn" data-sort-key="objective" data-sort-dir="asc" title="ก-ฮ">▲</button>
									<button type="button" class="pm-sort-btn" data-sort-key="objective" data-sort-dir="desc" title="ฮ-ก">▼</button>
								</span>
							</div>
						</th>
						<th class="text-center border-0">การจัดการ</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$pm_appr_row_no = 0;
						$pm_appr_has_any_row = false;
						if ($pm_appr_flow === 'include') {
							$pm_appr_has_any_row = (!empty($request_list) || !empty($plan_fix_review_projects));
						} elseif ($pm_appr_flow === 'operate') {
							$pm_appr_has_any_row = !empty($plan_operate_projects);
						} elseif ($pm_appr_flow === 'report') {
							$pm_appr_has_any_row = !empty($plan_report_projects);
						} elseif ($pm_appr_flow === 'change') {
							$pm_appr_has_any_row = (!empty($plan_change_projects) || !empty($plan_cancel_projects));
						}
					?>
					<?php if ($pm_appr_has_any_row): ?>
						<?php foreach ($request_list as $idx => $row): ?>
							<?php
								$request_id = (int)($row['request_id'] ?? 0);
								$created_at = (string)($row['created_at'] ?? '');
								$subject = trim((string)($row['subject'] ?? ''));
								if (function_exists('pm_include_request_subject_label')) {
									$subject = pm_include_request_subject_label($subject);
								}
								if ($subject === '') $subject = '(ไม่ระบุเรื่อง)';

								$is_submitter = !empty($row['is_submitter']) && (string)$row['is_submitter'] === '1';
								$is_reviewer = !empty($row['is_reviewer']) && (string)$row['is_reviewer'] === '1';
								if ($pm_appr_flow !== 'include') {
									continue;
								}
								$is_unit_admin_scope = !empty($row['is_unit_admin_scope']) && (string)$row['is_unit_admin_scope'] === '1';
								$request_status = (string)($row['request_status'] ?? 'submitted');
								$is_request_rejected = ($request_status === 'rejected');
								$objective_key = ($is_reviewer || $is_unit_admin_scope) ? 'review' : 'propose';
								$objective_txt = $objective_key === 'review' ? 'พิจารณา' : 'เสนอเรื่อง';

								$mySortOrder = 0;
								if ($request_id > 0 && isset($certByReq[$request_id]) && is_array($certByReq[$request_id])) {
									foreach ($certByReq[$request_id] as $so => $cRow) {
										if ((int)($cRow['staff_id'] ?? 0) === (int)$sid) {
											$mySortOrder = (int)$so;
											break;
										}
									}
								}
								$myDecision = '';
								if ($request_id > 0 && $mySortOrder > 0 && isset($decByReq[$request_id][$mySortOrder]['decision'])) {
									$myDecision = (string)$decByReq[$request_id][$mySortOrder]['decision'];
								}

								$submitter_reply_note_row = trim((string)($row['submitter_reply_note'] ?? ''));
								$submitter_reply_at_row = trim((string)($row['submitter_reply_updated_at'] ?? ''));
								$admin_fix_sent_row = trim((string)($row['admin_fix_sent_at'] ?? ''));
								$has_pending_return_row = false;
								$qPendRet = @mysqli_query(
									$db_Project,
									"SELECT 1 FROM project_include_request_item
									 WHERE request_id='" . (int)$request_id . "'
									   AND IFNULL(return_selected,0)=1
									   AND IFNULL(fix_status,'') IN ('','pending')
									 LIMIT 1"
								);
								$has_pending_return_row = ($qPendRet && mysqli_num_rows($qPendRet) > 0);
								$submitter_needs_reply_row = $is_request_rejected && ($has_pending_return_row || (
									!$has_pending_return_row
									&& $admin_fix_sent_row === ''
									&& pm_include_submitter_needs_reply(
										$db_Project,
										$request_id,
										$submitter_reply_note_row,
										$submitter_reply_at_row,
										(string)($lastRejectAtByReq[$request_id] ?? '')
									)
								));
								$cnt_operate = (int)($row['cnt_operate'] ?? 0);
								$cnt_projects = (int)($row['cnt_projects'] ?? 0);
								$cnt_notified = (int)($row['cnt_notified'] ?? 0);
								$pud_row_decision = trim((string)($row['plan_unit_decision'] ?? ''));
								$pm_include_all_notified = ($cnt_projects > 0 && $cnt_notified >= $cnt_projects);
								if ($is_request_rejected) {
									if ($admin_fix_sent_row !== '') {
										$status_group = 'pending';
										$status_txt = 'A2 อยู่ระหว่างตรวจสอบ ผู้ตรวจสอบหน่วยงาน';
										$status_mod = 'a2';
										$status_icon = 'fa-hourglass-half';
									} elseif (!$has_pending_return_row && function_exists('pm_include_all_returned_fixes_confirmed') && pm_include_all_returned_fixes_confirmed($db_Project, $request_id)) {
										$status_group = 'pending';
										$status_txt = 'A2 อยู่ระหว่างพิจารณา งานแผน';
										$status_mod = 'a2';
										$status_icon = 'fa-hourglass-half';
									} elseif ($has_pending_return_row) {
										$status_group = 'returned';
										$status_txt = 'A รอแก้ไข ผู้ตรวจสอบมีข้อแก้ไข';
										$status_mod = 'a_fix';
										$status_icon = 'fa-undo';
									} elseif (!$submitter_needs_reply_row && $submitter_reply_note_row !== '') {
										$status_group = 'pending';
										$status_txt = 'A2 อยู่ระหว่างพิจารณา งานแผน';
										$status_mod = 'a2';
										$status_icon = 'fa-hourglass-half';
									} else {
										$status_group = 'returned';
										$status_txt = 'A รอแก้ไข ผู้ตรวจสอบมีข้อแก้ไข';
										$status_mod = 'a_fix';
										$status_icon = 'fa-undo';
									}
								} else {
									$totalCert = (int)($certTotalByReq[$request_id] ?? 0);
									if ($totalCert <= 0 && isset($certByReq[$request_id]) && is_array($certByReq[$request_id])) {
										$totalCert = count($certByReq[$request_id]);
									}
									$decisions = isset($decByReq[$request_id]) && is_array($decByReq[$request_id]) ? $decByReq[$request_id] : [];
									$cntApproved = 0;
									$cntRejected = 0;
									foreach ($decisions as $__d) {
										$__v = (string)($__d['decision'] ?? '');
										if ($__v === 'approved') {
											$cntApproved++;
										} elseif ($__v === 'rejected') {
											$cntRejected++;
										}
									}
									$all_certs_passed = ($totalCert > 0 && $cntApproved >= $totalCert && $cntRejected === 0);
									$pudRowUser = trim((string)($row['plan_unit_decision'] ?? ''));

									if ($all_certs_passed && $pudRowUser === '') {
										$status_group = 'pending';
										$status_txt = 'A2 อยู่ระหว่างพิจารณา งานแผน';
										$status_mod = 'a2';
										$status_icon = 'fa-hourglass-half';
									} else {
										$status_group = $all_certs_passed ? 'done' : 'pending';
										if ($all_certs_passed) {
											$status_txt = 'เสร็จสิ้น';
											$status_mod = 'done';
											$status_icon = 'fa-check-circle';
										} else {
											$progInc = ($totalCert > 0) ? (' : ' . $cntApproved . '/' . $totalCert) : '';
											$status_txt = 'A2 อยู่ระหว่างตรวจสอบ ผู้ตรวจสอบหน่วยงาน' . $progInc;
											$status_mod = 'a2';
											$status_icon = 'fa-hourglass-half';
										}
									}
								}

								if ($pm_appr_is_plan_admin) {
									$pudRow = trim((string)($row['plan_unit_decision'] ?? ''));
									// คำขอขอบรรจุโครงการ — วัตถุประสงค์สำหรับหน่วยแผนคือ "แจ้งผล"
									$objective_key = 'notify';
									$objective_txt = 'แจ้งผล';
									if ($pudRow === 'included') {
										$status_group = 'done';
										if ($pm_include_all_notified) {
											$status_txt = 'เสร็จสิ้น';
											$status_mod = 'done';
											$status_icon = 'fa-check-circle';
										} else {
											$status_txt = 'A2 อยู่ระหว่างพิจารณา งานแผน';
											$status_mod = 'a2';
											$status_icon = 'fa-hourglass-half';
										}
									} elseif ($is_request_rejected && $pudRow === 'rejected') {
										$status_group = 'rejected';
										$status_txt = 'A ไม่ผ่านการพิจารณา';
										$status_mod = 'a_reject';
										$status_icon = 'fa-times-circle';
									} elseif ($is_request_rejected && $pudRow === 'returned') {
										$status_group = 'returned';
										$status_txt = 'A รอแก้ไข ผู้ตรวจสอบมีข้อแก้ไข';
										$status_mod = 'a_fix';
										$status_icon = 'fa-undo';
									} elseif (!$is_request_rejected) {
										$status_group = 'pending';
										$status_txt = 'A2 อยู่ระหว่างพิจารณา งานแผน';
										$status_mod = 'a2';
										$status_icon = 'fa-hourglass-half';
									}
								}

								$sender_id = (int)($row['submitter_staff_id'] ?? 0);
								$sender_name = $getStaffNameById($sender_id);
								$date_txt = $fmtThaiShort($created_at);

								$search_text = strtolower(trim($subject . ' ' . $sender_name . ' ' . $objective_txt . ' ' . (string)($row['project_search'] ?? '')));
								$created_ts = $created_at !== '' ? (int)strtotime($created_at) : 0;
								$row_project_group = ($cnt_projects > 0 && $cnt_operate >= $cnt_projects) ? 'plan' : 'draft';
								// คำขอที่ขอดำเนินการครบแล้ว → แสดงเป็นแถวรายโครงการแทน (ไม่ซ้ำ) ยกเว้นคงประวัติคำขอที่แจ้งผลครบแล้ว
								$pm_keep_include_history = $pm_include_all_notified
									|| in_array($pud_row_decision, ['included', 'returned', 'rejected'], true);
								if ($row_project_group === 'plan' && !$pm_appr_is_plan_admin && !$pm_keep_include_history) {
									continue;
								}
								$row_is_sub = $is_submitter ? '1' : '0';
								$row_is_rev = ($is_reviewer || $is_unit_admin_scope || !empty($pm_appr_is_plan_admin)) ? '1' : '0';
								$inbox_ref = 'include:' . (int)$request_id;
								$row_unread = (function_exists('pm_approve_inbox_row_unread') && pm_approve_inbox_row_unread($pm_appr_read_set, 'include', $inbox_ref, $row_is_rev, $row_is_sub, $status_group)) ? '1' : '0';
								$pm_appr_row_no++;
							?>
							<tr
								data-approve-row="1"
								data-flow="include"
								data-is-submitter="<?php echo $row_is_sub; ?>"
								data-is-reviewer="<?php echo $row_is_rev; ?>"
								data-inbox-ref="<?php echo htmlspecialchars($inbox_ref, ENT_QUOTES, 'UTF-8'); ?>"
								data-unread="<?php echo $row_unread; ?>"
								data-search="<?php echo htmlspecialchars($search_text, ENT_QUOTES, 'UTF-8'); ?>"
								data-date="<?php echo (int)$created_ts; ?>"
								data-subject="<?php echo htmlspecialchars(mb_strtolower($subject, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-sender="<?php echo htmlspecialchars(mb_strtolower($sender_name, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status="<?php echo htmlspecialchars(mb_strtolower($status_txt, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status-group="<?php echo htmlspecialchars($status_group, ENT_QUOTES, 'UTF-8'); ?>"
								data-project-group="<?php echo htmlspecialchars($row_project_group, ENT_QUOTES, 'UTF-8'); ?>"
								data-objective="<?php echo htmlspecialchars($objective_key, ENT_QUOTES, 'UTF-8'); ?>"
								data-request-id="<?php echo (int)$request_id; ?>"
							>
								<td class="text-center pm-col-no"><?php echo (int)$pm_appr_row_no; ?></td>
								<td class="text-center">
									<div class="pm-status-cell">
										<?php if ($is_request_rejected && $submitter_needs_reply_row): ?>
											<?php if ($is_submitter): ?>
												<button type="button"
													class="pm-btn-edit-returned text-nowrap"
													data-action="open-request-modal"
													data-request-id="<?php echo (int)$request_id; ?>"
													title="แก้ไขคำขอและโครงการ">
													<i class="fa fa-edit"></i> แก้ไข
												</button>
											<?php endif ?>
											<span class="pm-returned-mini">
												<i class="fa fa-undo"></i> ตีกลับเพื่อแก้ไข
											</span>
										<?php else: ?>
											<?php echo function_exists('pm_render_ab_badge')
												? pm_render_ab_badge(['label' => $status_txt, 'mod' => $status_mod])
												: '<span class="pm-status-badge pm-status-'.htmlspecialchars($status_mod, ENT_QUOTES, 'UTF-8').'"><i class="fa '.htmlspecialchars($status_icon, ENT_QUOTES, 'UTF-8').'"></i> '.htmlspecialchars($status_txt, ENT_QUOTES, 'UTF-8').'</span>';
											?>
											<?php if ($is_request_rejected && !$submitter_needs_reply_row && $submitter_reply_note_row !== ''): ?>
												<span class="pm-returned-mini d-block mt-1" title="ส่งคำชี้แจงแล้ว รอผู้ตรวจสอบพิจารณา">
													<i class="fa fa-reply"></i> ส่งคำชี้แจงแล้ว
												</span>
											<?php endif ?>
										<?php endif ?>
									</div>
								</td>
								<td class="text-center"><?php echo htmlspecialchars($date_txt, ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<div class="pm-project-name"><?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?></div>
								</td>
								<td><?php echo htmlspecialchars($sender_name, ENT_QUOTES, 'UTF-8'); ?></td>
								<td class="text-center">
									<?php
										$objBadgeCls = 'pm-obj-propose';
										$objBadgeIcon = 'fa-paper-plane';
										if ($objective_key === 'review') {
											$objBadgeCls = 'pm-obj-review';
											$objBadgeIcon = 'fa-search';
										} elseif ($objective_key === 'notify') {
											$objBadgeCls = 'pm-obj-notify';
											$objBadgeIcon = 'fa-bullhorn';
										}
									?>
									<span class="pm-obj-badge <?php echo htmlspecialchars($objBadgeCls, ENT_QUOTES, 'UTF-8'); ?>">
										<i class="fa <?php echo htmlspecialchars($objBadgeIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
										<?php echo htmlspecialchars($objective_txt, ENT_QUOTES, 'UTF-8'); ?>
									</span>
								</td>
								<td class="text-center">
									<div class="pm-action-wrap">
										<button type="button"
											class="btn btn-sm btn-outline-primary font-weight-bold"
											style="border-radius:.55rem;"
											data-action="open-request-modal"
											data-request-id="<?php echo (int)$request_id; ?>"
											title="จัดการ">
											<i class="fa fa-cog mr-1"></i> จัดการ
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach ?>

						<?php foreach ($plan_operate_projects as $pr): ?>
							<?php
								if ($pm_appr_flow !== 'operate') {
									continue;
								}
								$__subject = trim((string)($pr['subject'] ?? ''));
								if ($__subject === '') {
									$__nm = trim((string)($pr['name_project'] ?? ''));
									$__subject = 'ขอดำเนินการโครงการ ' . ($__nm !== '' ? $__nm : '…');
								}
								$__sender = $getStaffNameById((int)($pr['staff_insert'] ?? 0));
								$__created = (string)($pr['dates'] ?? '');
								$__date_txt = $fmtThaiShort($__created);
								$__created_ts = $__created !== '' ? (int)strtotime($__created) : 0;
								$__status_txt = (string)($pr['status_txt'] ?? 'รอดำเนินการ');
								$__status_mod = (string)($pr['status_mod'] ?? 'pending');
								$__status_icon = (string)($pr['status_icon'] ?? 'fa-hourglass-half');
								$__status_group = (string)($pr['status_group'] ?? 'pending');
								$__code = (string)($pr['code_pre_project'] ?? '');
								$__unit = (string)($pr['unit_name'] ?? '-');
								$__obj_key = (string)($pr['objective_key'] ?? 'review');
								if ($__obj_key !== 'propose') {
									$__obj_key = 'review';
								}
								$__obj_txt = $__obj_key === 'propose' ? 'เสนอเรื่อง' : 'พิจารณา';
								$__obj_cls = $__obj_key === 'propose' ? 'pm-obj-propose' : 'pm-obj-review';
								$__obj_icon = $__obj_key === 'propose' ? 'fa-paper-plane' : 'fa-search';
								$__search = strtolower(trim($__subject . ' ' . $__sender . ' ' . $__code . ' ' . $__unit . ' ' . $__obj_txt));
								$row_is_sub = !empty($pr['is_submitter']) ? '1' : '0';
								$row_is_rev = !empty($pr['is_reviewer']) ? '1' : '0';
								$inbox_ref = 'operate:' . (int)($pr['id'] ?? 0);
								$row_unread = (function_exists('pm_approve_inbox_row_unread') && pm_approve_inbox_row_unread($pm_appr_read_set, 'operate', $inbox_ref, $row_is_rev, $row_is_sub, $__status_group)) ? '1' : '0';
								$pm_appr_row_no++;
							?>
							<tr data-approve-row="1"
								data-flow="operate"
								data-is-submitter="<?php echo $row_is_sub; ?>"
								data-is-reviewer="<?php echo $row_is_rev; ?>"
								data-inbox-ref="<?php echo htmlspecialchars($inbox_ref, ENT_QUOTES, 'UTF-8'); ?>"
								data-unread="<?php echo $row_unread; ?>"
								data-search="<?php echo htmlspecialchars($__search, ENT_QUOTES, 'UTF-8'); ?>"
								data-date="<?php echo (int)$__created_ts; ?>"
								data-subject="<?php echo htmlspecialchars(mb_strtolower($__subject, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-sender="<?php echo htmlspecialchars(mb_strtolower($__sender, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status="<?php echo htmlspecialchars(mb_strtolower($__status_txt, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status-group="<?php echo htmlspecialchars($__status_group, ENT_QUOTES, 'UTF-8'); ?>"
								data-project-group="plan"
								data-objective="<?php echo htmlspecialchars($__obj_key, ENT_QUOTES, 'UTF-8'); ?>"
								data-request-id="<?php echo (int)($pr['request_id'] ?? 0); ?>">
								<td class="text-center pm-col-no"><?php echo (int)$pm_appr_row_no; ?></td>
								<td class="text-center">
									<?php echo function_exists('pm_render_ab_badge')
										? pm_render_ab_badge(['label' => $__status_txt, 'mod' => $__status_mod])
										: '<span class="pm-status-badge pm-status-'.htmlspecialchars($__status_mod, ENT_QUOTES, 'UTF-8').'"><i class="fa '.htmlspecialchars($__status_icon, ENT_QUOTES, 'UTF-8').'"></i> '.htmlspecialchars($__status_txt, ENT_QUOTES, 'UTF-8').'</span>';
									?>
								</td>
								<td class="text-center"><?php echo htmlspecialchars($__date_txt, ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<div class="pm-project-name"><?php echo htmlspecialchars($__subject, ENT_QUOTES, 'UTF-8'); ?></div>
									<?php if ($__code !== ''): ?>
										<small class="text-muted"><?php echo htmlspecialchars($__code, ENT_QUOTES, 'UTF-8'); ?></small>
									<?php endif; ?>
									<?php if (!empty($pr['is_midyear'])): ?>
										<div class="mt-1">
											<span class="badge badge-pill px-2 py-1" style="background:#e5e7eb;color:#374151;font-weight:800;border:1px solid #d1d5db;">ยื่นระหว่างปี</span>
										</div>
									<?php endif; ?>
									<?php if (!empty($pr['is_resubmitted'])): ?>
										<div>
											<span class="pm-resubmit-pill"><i class="fa fa-reply"></i> ผู้ยื่นแก้ไขส่งกลับแล้ว</span>
										</div>
									<?php endif; ?>
								</td>
								<td><?php echo htmlspecialchars($__sender, ENT_QUOTES, 'UTF-8'); ?></td>
								<td class="text-center">
									<span class="pm-obj-badge <?php echo htmlspecialchars($__obj_cls, ENT_QUOTES, 'UTF-8'); ?>">
										<i class="fa <?php echo htmlspecialchars($__obj_icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
										<?php echo htmlspecialchars($__obj_txt, ENT_QUOTES, 'UTF-8'); ?>
									</span>
								</td>
								<td class="text-center">
									<div class="pm-action-wrap">
										<button type="button"
											class="btn btn-outline-primary btn-sm font-weight-bold"
											style="border-radius:.55rem;"
											data-action="open-operate-modal"
											data-project-id="<?php echo (int)($pr['id'] ?? 0); ?>"
											data-request-id="<?php echo (int)($pr['request_id'] ?? 0); ?>"
											title="จัดการ">
											<i class="fa fa-cog mr-1"></i> จัดการ
										</button>
										<a href="?page=project_manage&op=add_project&project_id=<?php echo (int)($pr['id'] ?? 0); ?>&years_select=<?php echo rawurlencode((string)$years_select); ?>&back_to=<?php echo rawurlencode('?page=project_approve&years_select=' . rawurlencode((string)$years_select)); ?>"
											class="btn btn-info btn-sm font-weight-bold" style="border-radius:.55rem;" title="รายละเอียด">
											<i class="fa fa-file-alt mr-1"></i> รายละเอียด
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>

						<?php foreach ($plan_change_projects as $pr): ?>
							<?php
								if ($pm_appr_flow !== 'change') {
									continue;
								}
								$__subject = trim((string)($pr['subject'] ?? ''));
								if ($__subject === '') {
									$__nm = trim((string)($pr['name_project'] ?? ''));
									$__subject = 'ขอเปลี่ยนแปลงโครงการ ' . ($__nm !== '' ? $__nm : '…');
								}
								$__sender = $getStaffNameById((int)($pr['staff_insert'] ?? 0));
								$__created = (string)($pr['dates'] ?? '');
								$__date_txt = $fmtThaiShort($__created);
								$__created_ts = $__created !== '' ? (int)strtotime($__created) : 0;
								$__status_txt = (string)($pr['status_txt'] ?? 'เปลี่ยนแปลง');
								$__status_mod = (string)($pr['status_mod'] ?? 'changing');
								$__status_icon = (string)($pr['status_icon'] ?? 'fa-exchange-alt');
								$__status_group = (string)($pr['status_group'] ?? 'pending');
								$__code = (string)($pr['code_pre_project'] ?? '');
								$__unit = (string)($pr['unit_name'] ?? '-');
								$__obj_key = (string)($pr['objective_key'] ?? 'review');
								if ($__obj_key !== 'propose') {
									$__obj_key = 'review';
								}
								$__obj_txt = $__obj_key === 'propose' ? 'เสนอเรื่อง' : 'พิจารณา';
								$__obj_cls = $__obj_key === 'propose' ? 'pm-obj-propose' : 'pm-obj-review';
								$__obj_icon = $__obj_key === 'propose' ? 'fa-paper-plane' : 'fa-search';
								$__search = strtolower(trim($__subject . ' ' . $__sender . ' ' . $__code . ' ' . $__unit . ' ' . $__obj_txt . ' เปลี่ยนแปลง'));
								$row_is_sub = !empty($pr['is_submitter']) ? '1' : '0';
								$row_is_rev = !empty($pr['is_reviewer']) ? '1' : '0';
								$inbox_ref = 'change:' . (int)($pr['request_id'] ?? 0);
								$row_unread = (function_exists('pm_approve_inbox_row_unread') && pm_approve_inbox_row_unread($pm_appr_read_set, 'change', $inbox_ref, $row_is_rev, $row_is_sub, $__status_group)) ? '1' : '0';
								$pm_appr_row_no++;
							?>
							<tr data-approve-row="1"
								data-flow="change"
								data-is-submitter="<?php echo $row_is_sub; ?>"
								data-is-reviewer="<?php echo $row_is_rev; ?>"
								data-inbox-ref="<?php echo htmlspecialchars($inbox_ref, ENT_QUOTES, 'UTF-8'); ?>"
								data-unread="<?php echo $row_unread; ?>"
								data-search="<?php echo htmlspecialchars($__search, ENT_QUOTES, 'UTF-8'); ?>"
								data-date="<?php echo (int)$__created_ts; ?>"
								data-subject="<?php echo htmlspecialchars(mb_strtolower($__subject, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-sender="<?php echo htmlspecialchars(mb_strtolower($__sender, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status="<?php echo htmlspecialchars(mb_strtolower($__status_txt, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status-group="<?php echo htmlspecialchars($__status_group, ENT_QUOTES, 'UTF-8'); ?>"
								data-project-group="plan"
								data-objective="<?php echo htmlspecialchars($__obj_key, ENT_QUOTES, 'UTF-8'); ?>"
								data-request-id="<?php echo (int)($pr['request_id'] ?? 0); ?>">
								<td class="text-center pm-col-no"><?php echo (int)$pm_appr_row_no; ?></td>
								<td class="text-center">
									<?php echo function_exists('pm_render_ab_badge')
										? pm_render_ab_badge(['label' => $__status_txt, 'mod' => $__status_mod])
										: '<span class="pm-status-badge pm-status-'.htmlspecialchars($__status_mod, ENT_QUOTES, 'UTF-8').'"><i class="fa '.htmlspecialchars($__status_icon, ENT_QUOTES, 'UTF-8').'"></i> '.htmlspecialchars($__status_txt, ENT_QUOTES, 'UTF-8').'</span>';
									?>
								</td>
								<td class="text-center"><?php echo htmlspecialchars($__date_txt, ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<div class="pm-project-name"><?php echo htmlspecialchars($__subject, ENT_QUOTES, 'UTF-8'); ?></div>
									<?php if ($__code !== ''): ?>
										<small class="text-muted"><?php echo htmlspecialchars($__code, ENT_QUOTES, 'UTF-8'); ?></small>
									<?php endif; ?>
								</td>
								<td><?php echo htmlspecialchars($__sender, ENT_QUOTES, 'UTF-8'); ?></td>
								<td class="text-center">
									<span class="pm-obj-badge <?php echo htmlspecialchars($__obj_cls, ENT_QUOTES, 'UTF-8'); ?>">
										<i class="fa <?php echo htmlspecialchars($__obj_icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
										<?php echo htmlspecialchars($__obj_txt, ENT_QUOTES, 'UTF-8'); ?>
									</span>
								</td>
								<td class="text-center">
									<div class="pm-action-wrap">
										<button type="button"
											class="btn btn-outline-primary btn-sm font-weight-bold"
											style="border-radius:.55rem;"
											data-action="open-change-modal"
											data-project-id="<?php echo (int)($pr['id'] ?? 0); ?>"
											data-request-id="<?php echo (int)($pr['request_id'] ?? 0); ?>"
											title="จัดการ">
											<i class="fa fa-cog mr-1"></i> จัดการ
										</button>
										<a href="?page=project_manage&op=add_project&project_id=<?php echo (int)($pr['id'] ?? 0); ?>&years_select=<?php echo rawurlencode((string)$years_select); ?>&back_to=<?php echo rawurlencode('?page=project_approve&years_select=' . rawurlencode((string)$years_select)); ?>"
											class="btn btn-info btn-sm font-weight-bold" style="border-radius:.55rem;" title="รายละเอียด">
											<i class="fa fa-file-alt mr-1"></i> รายละเอียด
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>

						<?php foreach ($plan_cancel_projects as $pr): ?>
							<?php
								if ($pm_appr_flow !== 'change') {
									continue;
								}
								$__subject = trim((string)($pr['subject'] ?? ''));
								if ($__subject === '') {
									$__nm = trim((string)($pr['name_project'] ?? ''));
									$__subject = 'ขอยกเลิกโครงการ ' . ($__nm !== '' ? $__nm : '…');
								}
								$__sender = $getStaffNameById((int)($pr['staff_insert'] ?? 0));
								$__created = (string)($pr['dates'] ?? '');
								$__date_txt = $fmtThaiShort($__created);
								$__created_ts = $__created !== '' ? (int)strtotime($__created) : 0;
								$__status_txt = (string)($pr['status_txt'] ?? 'ขอยกเลิก');
								$__status_mod = (string)($pr['status_mod'] ?? 'cancel_pending');
								$__status_icon = (string)($pr['status_icon'] ?? 'fa-ban');
								$__status_group = (string)($pr['status_group'] ?? 'pending');
								$__code = (string)($pr['code_pre_project'] ?? '');
								$__unit = (string)($pr['unit_name'] ?? '-');
								$__obj_key = (string)($pr['objective_key'] ?? 'review');
								if ($__obj_key !== 'propose') {
									$__obj_key = 'review';
								}
								$__obj_txt = $__obj_key === 'propose' ? 'เสนอเรื่อง' : 'พิจารณา';
								$__obj_cls = $__obj_key === 'propose' ? 'pm-obj-propose' : 'pm-obj-review';
								$__obj_icon = $__obj_key === 'propose' ? 'fa-paper-plane' : 'fa-search';
								$__search = strtolower(trim($__subject . ' ' . $__sender . ' ' . $__code . ' ' . $__unit . ' ' . $__obj_txt . ' ยกเลิก'));
								$row_is_sub = !empty($pr['is_submitter']) ? '1' : '0';
								$row_is_rev = !empty($pr['is_reviewer']) ? '1' : '0';
								$inbox_ref = 'cancel:' . (int)($pr['request_id'] ?? 0);
								$row_unread = (function_exists('pm_approve_inbox_row_unread') && pm_approve_inbox_row_unread($pm_appr_read_set, 'change', $inbox_ref, $row_is_rev, $row_is_sub, $__status_group)) ? '1' : '0';
								$pm_appr_row_no++;
							?>
							<tr data-approve-row="1"
								data-flow="change"
								data-is-submitter="<?php echo $row_is_sub; ?>"
								data-is-reviewer="<?php echo $row_is_rev; ?>"
								data-inbox-ref="<?php echo htmlspecialchars($inbox_ref, ENT_QUOTES, 'UTF-8'); ?>"
								data-unread="<?php echo $row_unread; ?>"
								data-search="<?php echo htmlspecialchars($__search, ENT_QUOTES, 'UTF-8'); ?>"
								data-date="<?php echo (int)$__created_ts; ?>"
								data-subject="<?php echo htmlspecialchars(mb_strtolower($__subject, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-sender="<?php echo htmlspecialchars(mb_strtolower($__sender, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status="<?php echo htmlspecialchars(mb_strtolower($__status_txt, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status-group="<?php echo htmlspecialchars($__status_group, ENT_QUOTES, 'UTF-8'); ?>"
								data-project-group="plan"
								data-objective="<?php echo htmlspecialchars($__obj_key, ENT_QUOTES, 'UTF-8'); ?>"
								data-request-id="<?php echo (int)($pr['request_id'] ?? 0); ?>"
								data-budget="<?php echo htmlspecialchars((string)($pr['budget'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>"
								data-budget-source="<?php echo htmlspecialchars((string)($pr['budget_source'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>">
								<td class="text-center pm-col-no"><?php echo (int)$pm_appr_row_no; ?></td>
								<td class="text-center">
									<?php echo function_exists('pm_render_ab_badge')
										? pm_render_ab_badge(['label' => $__status_txt, 'mod' => $__status_mod])
										: '<span class="pm-status-badge pm-status-'.htmlspecialchars($__status_mod, ENT_QUOTES, 'UTF-8').'"><i class="fa '.htmlspecialchars($__status_icon, ENT_QUOTES, 'UTF-8').'"></i> '.htmlspecialchars($__status_txt, ENT_QUOTES, 'UTF-8').'</span>';
									?>
								</td>
								<td class="text-center"><?php echo htmlspecialchars($__date_txt, ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<div class="pm-project-name"><?php echo htmlspecialchars($__subject, ENT_QUOTES, 'UTF-8'); ?></div>
									<?php if ($__code !== ''): ?>
										<small class="text-muted"><?php echo htmlspecialchars($__code, ENT_QUOTES, 'UTF-8'); ?></small>
									<?php endif; ?>
								</td>
								<td><?php echo htmlspecialchars($__sender, ENT_QUOTES, 'UTF-8'); ?></td>
								<td class="text-center">
									<span class="pm-obj-badge <?php echo htmlspecialchars($__obj_cls, ENT_QUOTES, 'UTF-8'); ?>">
										<i class="fa <?php echo htmlspecialchars($__obj_icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
										<?php echo htmlspecialchars($__obj_txt, ENT_QUOTES, 'UTF-8'); ?>
									</span>
								</td>
								<td class="text-center">
									<div class="pm-action-wrap">
										<button type="button"
											class="btn btn-outline-danger btn-sm font-weight-bold"
											style="border-radius:.55rem;"
											data-action="open-cancel-modal"
											data-project-id="<?php echo (int)($pr['id'] ?? 0); ?>"
											data-request-id="<?php echo (int)($pr['request_id'] ?? 0); ?>"
											title="พิจารณาการยกเลิก">
											<i class="fa fa-cog mr-1"></i> จัดการ
										</button>
										<a href="?page=project_manage&op=add_project&project_id=<?php echo (int)($pr['id'] ?? 0); ?>&years_select=<?php echo rawurlencode((string)$years_select); ?>&back_to=<?php echo rawurlencode('?page=project_approve&years_select=' . rawurlencode((string)$years_select)); ?>"
											class="btn btn-info btn-sm font-weight-bold" style="border-radius:.55rem;" title="รายละเอียด">
											<i class="fa fa-file-alt mr-1"></i> รายละเอียด
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>

						<?php foreach ($plan_report_projects as $pr): ?>
							<?php
								if ($pm_appr_flow !== 'report') {
									continue;
								}
								$__subject = trim((string)($pr['subject'] ?? ''));
								if ($__subject === '') {
									$__nm = trim((string)($pr['name_project'] ?? ''));
									$__subject = 'รายงานผลโครงการ ' . ($__nm !== '' ? $__nm : '…');
								}
								$__sender = $getStaffNameById((int)($pr['staff_insert'] ?? 0));
								$__created = (string)($pr['dates'] ?? '');
								$__date_txt = $fmtThaiShort($__created);
								$__created_ts = $__created !== '' ? (int)strtotime($__created) : 0;
								$__status_txt = (string)($pr['status_txt'] ?? 'รอตรวจสอบรายงานผล');
								$__status_mod = (string)($pr['status_mod'] ?? 'report_pending');
								$__status_icon = (string)($pr['status_icon'] ?? 'fa-hourglass-half');
								$__status_group = (string)($pr['status_group'] ?? 'pending');
								$__code = (string)($pr['code_pre_project'] ?? '');
								$__unit = (string)($pr['unit_name'] ?? '-');
								$__search = strtolower(trim($__subject . ' ' . $__sender . ' ' . $__code . ' ' . $__unit . ' รายงานผล'));
								$row_is_sub = !empty($pr['is_submitter']) ? '1' : '0';
								$row_is_rev = !empty($pr['is_reviewer']) ? '1' : '0';
								$inbox_ref = 'report:' . (int)($pr['id'] ?? 0);
								$row_unread = (function_exists('pm_approve_inbox_row_unread') && pm_approve_inbox_row_unread($pm_appr_read_set, 'report', $inbox_ref, $row_is_rev, $row_is_sub, $__status_group)) ? '1' : '0';
								$pm_appr_row_no++;
							?>
							<tr data-approve-row="1"
								data-flow="report"
								data-is-submitter="<?php echo $row_is_sub; ?>"
								data-is-reviewer="<?php echo $row_is_rev; ?>"
								data-inbox-ref="<?php echo htmlspecialchars($inbox_ref, ENT_QUOTES, 'UTF-8'); ?>"
								data-unread="<?php echo $row_unread; ?>"
								data-search="<?php echo htmlspecialchars($__search, ENT_QUOTES, 'UTF-8'); ?>"
								data-date="<?php echo (int)$__created_ts; ?>"
								data-subject="<?php echo htmlspecialchars(mb_strtolower($__subject, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-sender="<?php echo htmlspecialchars(mb_strtolower($__sender, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status="<?php echo htmlspecialchars(mb_strtolower($__status_txt, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status-group="<?php echo htmlspecialchars($__status_group, ENT_QUOTES, 'UTF-8'); ?>"
								data-project-group="plan"
								data-objective="<?php echo htmlspecialchars((string)($pr['objective_key'] ?? 'review'), ENT_QUOTES, 'UTF-8'); ?>">
								<td class="text-center pm-col-no"><?php echo (int)$pm_appr_row_no; ?></td>
								<td class="text-center">
									<?php echo function_exists('pm_render_ab_badge')
										? pm_render_ab_badge(['label' => $__status_txt, 'mod' => $__status_mod])
										: '<span class="pm-status-badge pm-status-'.htmlspecialchars($__status_mod, ENT_QUOTES, 'UTF-8').'"><i class="fa '.htmlspecialchars($__status_icon, ENT_QUOTES, 'UTF-8').'"></i> '.htmlspecialchars($__status_txt, ENT_QUOTES, 'UTF-8').'</span>';
									?>
								</td>
								<td class="text-center"><?php echo htmlspecialchars($__date_txt, ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<div class="pm-project-name"><?php echo htmlspecialchars($__subject, ENT_QUOTES, 'UTF-8'); ?></div>
									<?php if ($__code !== ''): ?>
										<small class="text-muted"><?php echo htmlspecialchars($__code, ENT_QUOTES, 'UTF-8'); ?></small>
									<?php endif; ?>
								</td>
								<td><?php echo htmlspecialchars($__sender, ENT_QUOTES, 'UTF-8'); ?></td>
								<td class="text-center">
									<span class="pm-obj-badge pm-obj-review">
										<i class="fa fa-search"></i> พิจารณา
									</span>
								</td>
								<td class="text-center">
									<div class="pm-action-wrap">
										<button type="button"
											class="btn btn-outline-primary btn-sm font-weight-bold"
											style="border-radius:.55rem;"
											data-action="open-report-modal"
											data-project-id="<?php echo (int)($pr['id'] ?? 0); ?>"
											title="จัดการ">
											<i class="fa fa-cog mr-1"></i> จัดการ
										</button>
										<a href="?page=project_manage&op=add_project&project_id=<?php echo (int)($pr['id'] ?? 0); ?>&years_select=<?php echo rawurlencode((string)$years_select); ?>&back_to=<?php echo rawurlencode('?page=project_approve&years_select=' . rawurlencode((string)$years_select)); ?>"
											class="btn btn-info btn-sm font-weight-bold" style="border-radius:.55rem;" title="รายละเอียด">
											<i class="fa fa-file-alt mr-1"></i> รายละเอียด
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>

						<?php foreach ($plan_fix_review_projects as $pr): ?>
							<?php
								if ($pm_appr_flow !== 'include') {
									continue;
								}
								$__subject = trim((string)($pr['subject'] ?? ''));
								if ($__subject === '') {
									$__nm = trim((string)($pr['name_project'] ?? ''));
									$__subject = 'พิจารณาการแก้ไขโครงการ ' . ($__nm !== '' ? $__nm : '…');
								}
								$__sender = $getStaffNameById((int)($pr['staff_insert'] ?? 0));
								$__created = (string)($pr['dates'] ?? '');
								$__date_txt = $fmtThaiShort($__created);
								$__created_ts = $__created !== '' ? (int)strtotime($__created) : 0;
								$__status_txt = (string)($pr['status_txt'] ?? 'พิจารณาการแก้ไข');
								$__status_mod = (string)($pr['status_mod'] ?? 'consider_fix');
								$__status_icon = (string)($pr['status_icon'] ?? 'fa-search');
								$__status_group = (string)($pr['status_group'] ?? 'pending');
								$__code = (string)($pr['code_pre_project'] ?? '');
								$__unit = (string)($pr['unit_name'] ?? '-');
								$__search = strtolower(trim($__subject . ' ' . $__sender . ' ' . $__code . ' ' . $__unit . ' พิจารณาการแก้ไข แจ้งผล'));
								$__back = '?page=project_approve&years_select=' . rawurlencode((string)$years_select);
								$row_is_sub = !empty($pr['is_submitter']) ? '1' : '0';
								$row_is_rev = !empty($pr['is_reviewer']) ? '1' : '0';
								$inbox_ref = 'include-fix:' . (int)($pr['id'] ?? 0);
								$row_unread = (function_exists('pm_approve_inbox_row_unread') && pm_approve_inbox_row_unread($pm_appr_read_set, 'include', $inbox_ref, $row_is_rev, $row_is_sub, $__status_group)) ? '1' : '0';
								$pm_appr_row_no++;
							?>
							<tr data-approve-row="1"
								data-flow="include"
								data-is-submitter="<?php echo $row_is_sub; ?>"
								data-is-reviewer="<?php echo $row_is_rev; ?>"
								data-inbox-ref="<?php echo htmlspecialchars($inbox_ref, ENT_QUOTES, 'UTF-8'); ?>"
								data-unread="<?php echo $row_unread; ?>"
								data-search="<?php echo htmlspecialchars($__search, ENT_QUOTES, 'UTF-8'); ?>"
								data-date="<?php echo (int)$__created_ts; ?>"
								data-subject="<?php echo htmlspecialchars(mb_strtolower($__subject, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-sender="<?php echo htmlspecialchars(mb_strtolower($__sender, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status="<?php echo htmlspecialchars(mb_strtolower($__status_txt, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
								data-status-group="<?php echo htmlspecialchars($__status_group, ENT_QUOTES, 'UTF-8'); ?>"
								data-project-group="draft"
								data-objective="notify">
								<td class="text-center pm-col-no"><?php echo (int)$pm_appr_row_no; ?></td>
								<td class="text-center">
									<?php echo function_exists('pm_render_ab_badge')
										? pm_render_ab_badge(['label' => $__status_txt, 'mod' => $__status_mod])
										: '<span class="pm-status-badge pm-status-'.htmlspecialchars($__status_mod, ENT_QUOTES, 'UTF-8').'"><i class="fa '.htmlspecialchars($__status_icon, ENT_QUOTES, 'UTF-8').'"></i> '.htmlspecialchars($__status_txt, ENT_QUOTES, 'UTF-8').'</span>';
									?>
								</td>
								<td class="text-center"><?php echo htmlspecialchars($__date_txt, ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<div class="pm-project-name"><?php echo htmlspecialchars($__subject, ENT_QUOTES, 'UTF-8'); ?></div>
									<?php if ($__code !== ''): ?>
										<small class="text-muted"><?php echo htmlspecialchars($__code, ENT_QUOTES, 'UTF-8'); ?></small>
									<?php endif; ?>
								</td>
								<td><?php echo htmlspecialchars($__sender, ENT_QUOTES, 'UTF-8'); ?></td>
								<td class="text-center">
									<span class="pm-obj-badge pm-obj-notify">
										<i class="fa fa-bullhorn"></i> แจ้งผล
									</span>
								</td>
								<td class="text-center">
									<div class="pm-action-wrap">
										<a href="?page=project_manage&op=add_project&project_id=<?php echo (int)($pr['id'] ?? 0); ?>&years_select=<?php echo rawurlencode((string)$years_select); ?>&back_to=<?php echo rawurlencode($__back); ?>"
											class="btn btn-success btn-sm font-weight-bold" style="border-radius:.55rem;" title="เปิดดูโครงการและพิจารณาการแก้ไข">
											<i class="fa fa-eye mr-1"></i> ดูโครงการ
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>

						<?php if ($pm_appr_row_no <= 0): ?>
							<tr id="project-approve-empty-row"><td colspan="7" class="text-center text-secondary py-4">ไม่พบรายการอนุมัติโครงการ</td></tr>
						<?php endif; ?>
					<?php else: ?>
						<tr id="project-approve-empty-row"><td colspan="7" class="text-center text-secondary py-4">ไม่พบรายการอนุมัติโครงการ</td></tr>
					<?php endif ?>
				</tbody>
			</table>
		</div>

		<div class="d-flex justify-content-between align-items-center mt-3">
			<div class="text-muted small" id="projectApproveSummary">-</div>
			<div class="d-flex align-items-center gap-1" id="projectApprovePager"></div>
		</div>
	</div>
</div>

<!-- Modal: พิจารณาขอยกเลิกโครงการ -->
<div class="modal fade pm-memo-modal" id="modalPlanCancelRequest" tabindex="-1" role="dialog" aria-labelledby="pmPlanCanTitle" data-backdrop="static">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content pm-plan-can-content">
			<div class="modal-header pm-plan-can-header">
				<div class="pm-plan-can-header-left">
					<span class="pm-plan-can-header-icon"><i class="fa fa-ban"></i></span>
					<div>
						<h5 class="modal-title" id="pmPlanCanTitle">พิจารณาขอยกเลิกโครงการ</h5>
						<div class="pm-plan-can-header-sub">หน่วยแผนเป็นผู้พิจารณาอนุมัติการยกเลิก</div>
					</div>
				</div>
				<button type="button" class="close pm-plan-can-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body pm-plan-can-body">
				<input type="hidden" id="pmPlanCanRequestId" value="">
				<input type="hidden" id="pmPlanCanProjectId" value="">
				<div id="pmPlanCanStatus" class="pm-plan-can-status d-none"></div>
				<div class="pm-plan-can-summary">
					<div class="pm-plan-can-summary-name" id="pmPlanCanName">-</div>
					<div class="pm-plan-can-grid">
						<div class="k">ชื่อโครงการ</div>
						<div class="v" id="pmPlanCanName2">-</div>
						<div class="k">รหัส</div>
						<div class="v" id="pmPlanCanCode">-</div>
						<div class="k">แหล่งงบประมาณ</div>
						<div class="v" id="pmPlanCanBudgetSrc">-</div>
						<div class="k">งบประมาณ</div>
						<div class="v"><span id="pmPlanCanBudget">0</span> บาท</div>
						<div class="k">ผู้ยื่นคำขอ</div>
						<div class="v" id="pmPlanCanSender">-</div>
					</div>
				</div>
				<section class="pm-plan-can-section">
					<div class="pm-plan-can-section-title">เหตุผลการขอยกเลิก</div>
					<div id="pmPlanCanReason" class="pm-plan-can-reason">-</div>
				</section>
				<section class="pm-plan-can-section">
					<div class="pm-plan-can-section-title">เอกสารแนบ</div>
					<div id="pmPlanCanFiles" class="pm-plan-can-file-list"></div>
				</section>
				<section class="pm-plan-can-section" id="pmPlanCanNoteWrap">
					<div class="pm-plan-can-section-title">ความเห็น / เหตุผลการตีกลับ</div>
					<textarea id="pmPlanCanNote" class="form-control pm-plan-can-input" rows="3" placeholder="ระบุเมื่อตีกลับ (จำเป็น) หรือความเห็นเมื่ออนุมัติ (ถ้ามี)"></textarea>
				</section>
			</div>
			<div class="modal-footer pm-plan-can-footer">
				<button type="button" class="btn pm-plan-can-btn-close pm-plan-can-close" data-dismiss="modal" data-bs-dismiss="modal">ปิด</button>
				<button type="button" class="btn pm-plan-can-btn-reject d-none" id="btnPmPlanCanReject"><i class="fa fa-undo mr-1"></i> ตีกลับ</button>
				<button type="button" class="btn pm-plan-can-btn-approve d-none" id="btnPmPlanCanApprove"><i class="fa fa-check mr-1"></i> อนุมัติยกเลิก</button>
			</div>
		</div>
	</div>
</div>
<style>
	#modalPlanCancelRequest{z-index:10058!important;}
	#modalPlanCancelRequest .modal-dialog{max-width:860px;pointer-events:auto;}
	#modalPlanCancelRequest .pm-plan-can-content{border:0;border-radius:1.15rem;overflow:hidden;box-shadow:0 28px 60px -28px rgba(127,29,29,.45);}
	#modalPlanCancelRequest .pm-plan-can-header{background:linear-gradient(135deg,#fef2f2 0%,#fee2e2 55%,#fecaca 100%);border-bottom:1px solid rgba(248,113,113,.4);padding:.95rem 1.2rem;align-items:center;}
	#modalPlanCancelRequest .pm-plan-can-header-left{display:flex;align-items:flex-start;gap:.85rem;}
	#modalPlanCancelRequest .pm-plan-can-header-icon{width:2.7rem;height:2.7rem;border-radius:.9rem;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#f87171 0%,#dc2626 100%);color:#fff;font-size:1.05rem;}
	#modalPlanCancelRequest .pm-plan-can-header .modal-title{font-weight:900;color:#7f1d1d;margin:0;font-size:1.12rem;}
	#modalPlanCancelRequest .pm-plan-can-header-sub{font-size:.82rem;font-weight:650;color:#991b1b;opacity:.88;margin-top:.08rem;}
	#modalPlanCancelRequest .pm-plan-can-body{background:linear-gradient(180deg,#fff8f8 0%,#fff 42%);padding:1.1rem 1.2rem 1.25rem;}
	#modalPlanCancelRequest .pm-plan-can-status{margin-bottom:.85rem;padding:.7rem .85rem;border-radius:.75rem;font-weight:700;white-space:pre-wrap;}
	#modalPlanCancelRequest .pm-plan-can-status.is-pending{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;}
	#modalPlanCancelRequest .pm-plan-can-status.is-approved{background:#fef2f2;border:1px solid #dc2626;color:#7f1d1d;}
	#modalPlanCancelRequest .pm-plan-can-status.is-rejected{background:#fff7ed;border:1px solid #fdba74;color:#9a3412;}
	#modalPlanCancelRequest .pm-plan-can-summary{padding:1rem 1.05rem .95rem;border-radius:1rem;background:linear-gradient(145deg,#fff 0%,#fef2f2 100%);border:1px dashed rgba(239,68,68,.42);margin-bottom:1.05rem;}
	#modalPlanCancelRequest .pm-plan-can-summary-name{font-weight:900;font-size:1.08rem;color:#7f1d1d;line-height:1.35;margin-bottom:.65rem;}
	#modalPlanCancelRequest .pm-plan-can-grid{display:grid;grid-template-columns:140px 1fr;gap:.32rem .8rem;padding:.8rem .9rem;border-radius:.8rem;background:rgba(255,255,255,.82);border:1px solid rgba(254,202,202,.8);}
	#modalPlanCancelRequest .pm-plan-can-grid .k{font-weight:800;color:#b91c1c;font-size:.88rem;}
	#modalPlanCancelRequest .pm-plan-can-grid .v{font-weight:800;color:#0f172a;}
	#modalPlanCancelRequest .pm-plan-can-section{margin-bottom:1.05rem;}
	#modalPlanCancelRequest .pm-plan-can-section-title{font-weight:900;color:#0f172a;font-size:.98rem;margin-bottom:.35rem;}
	#modalPlanCancelRequest .pm-plan-can-reason{padding:.75rem .85rem;border-radius:.75rem;background:#fff;border:1px solid #fecaca;white-space:pre-wrap;font-weight:650;color:#334155;min-height:3.2rem;}
	#modalPlanCancelRequest .pm-plan-can-file-list{display:flex;flex-wrap:wrap;gap:.4rem;}
	#modalPlanCancelRequest .pm-plan-can-file-chip{display:inline-flex;align-items:center;gap:.4rem;padding:.32rem .65rem;border-radius:999px;background:#fff;border:1px solid #fecaca;color:#7f1d1d;font-weight:700;font-size:.82rem;text-decoration:none;}
	#modalPlanCancelRequest .pm-plan-can-input{border-radius:.7rem;border-color:#e2e8f0;}
	#modalPlanCancelRequest .pm-plan-can-footer{background:#fef2f2;border-top:1px solid rgba(254,202,202,.7);padding:.8rem 1.15rem;}
	#modalPlanCancelRequest .pm-plan-can-btn-close{border-radius:.7rem;border:1px solid #cbd5e1;background:#fff;color:#334155;font-weight:800;}
	#modalPlanCancelRequest .pm-plan-can-btn-reject{border-radius:.75rem;border:1px solid #fb923c;background:#fff;color:#c2410c;font-weight:900;padding:.45rem 1.1rem;}
	#modalPlanCancelRequest .pm-plan-can-btn-approve{border-radius:.75rem;border:0;background:linear-gradient(135deg,#f87171 0%,#dc2626 100%);color:#fff;font-weight:900;padding:.48rem 1.2rem;box-shadow:0 12px 22px -14px rgba(220,38,38,.95);}
	@media (max-width:640px){#modalPlanCancelRequest .pm-plan-can-grid{grid-template-columns:1fr;}}
</style>

<!-- Modal: รายละเอียดคำขอ -->
<div class="modal fade" id="modalRequestDetail" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-light">
				<h5 class="modal-title md-title"><i class="fa fa-file-alt mr-1"></i> <?php echo 'สรุปการขอพิจารณาแผนงาน ประจำปีงบประมาณ พ.ศ. ' . htmlspecialchars((string)$years_select, ENT_QUOTES, 'UTF-8'); ?></h5>
				<button type="button" class="close" id="mdRequestDetailCloseX" data-dismiss="modal" aria-label="Close"
					onclick="if(window.pmProjectApproveDetailClose){window.pmProjectApproveDetailClose();} return false;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="mdRequestId" value="">
				<div id="mdLetterTopBlock" class="md-letter-top-block" style="display:none;">
				<div class="form-group">
					<label>ส่วนงาน</label>
					<input type="text" class="form-control" id="mdSectionName">
				</div>
				<div class="form-row">
					<div class="form-group col-md-7">
						<label>ที่</label>
						<input type="text" class="form-control" id="mdDocNo" readonly>
					</div>
					<div class="form-group col-md-5">
						<label>วันที่</label>
						<input type="date" class="form-control" id="mdDate">
					</div>
				</div>
				<div class="form-group">
					<label>เรื่อง</label>
					<input type="text" class="form-control" id="mdSubject">
				</div>
				<div class="form-group">
					<div class="rounded p-2 md-body" id="mdBody">-</div>
				</div>
				</div>
				<div class="mb-3" id="mdEditAllWrap" style="display:none;">
					<a href="#" class="btn btn-warning btn-sm font-weight-bold" id="mdEditAllBtn">
						<i class="fa fa-edit mr-1"></i> แก้ไขโครงการที่ถูกตีกลับ
					</a>
					<small class="text-muted d-block mt-1">เปิดหน้าจัดการโครงการ แสดงเฉพาะโครงการที่ผู้รับรองเลือกตีกลับ เพื่อแก้ไขทีละโครงการ</small>
				</div>
				<div class="mb-3" id="mdAdminSendFixWrap" style="display:none;">
					<button type="button" class="btn btn-success btn-sm font-weight-bold" id="mdAdminSendFixBtn">
						<i class="fa fa-paper-plane mr-1"></i> ยืนยันการแก้ไข
					</button>
					<small class="text-muted d-block mt-1">โครงการที่ถูกตีกลับยืนยันคำชี้แจงครบแล้ว — กดเพื่อส่งกลับให้ผู้รับรองพิจารณา</small>
					<div id="mdAdminSendFixMsg" class="mt-2 small" style="display:none;"></div>
				</div>
				<div class="mb-3" id="mdReturnedFixHistoryWrap" style="display:none;">
					<div class="font-weight-bold text-secondary mb-1"><i class="fa fa-history mr-1"></i> โครงการที่ถูกตีกลับ / คำชี้แจงการแก้ไข</div>
					<div id="mdReturnedFixHistory" class="border rounded p-2 bg-light"></div>
				</div>
				<div class="md-section-above-table mb-3 pb-2 border-b-1 brc-default-l2" id="mdSectionAboveTableWrap">
					<div class="text-secondary text-95 font-weight-bold mb-1">ส่วนงาน</div>
					<div class="text-dark text-110 font-weight-bold" id="mdSectionAboveText">-</div>
				</div>
				<div class="mt-3">
					<div class="table-responsive">
						<table class="table table-bordered md-summary-table mb-0">
							<thead id="mdSummaryHead">
								<tr>
									<th class="text-center">ความสอดคล้อง</th>
									<th class="text-center" style="width:130px;">จำนวนโครงการ</th>
									<th class="text-center" style="width:140px;">งบประมาณ (บาท)</th>
									<th class="text-center" style="min-width:220px;">โครงการ</th>
									<th class="text-center" style="min-width:260px;">ตัวชี้วัดเป้าหมายโครงการ</th>
								</tr>
							</thead>
							<tbody id="mdSummaryBody">
								<tr><td colspan="5" class="text-center text-muted">-</td></tr>
							</tbody>
						</table>
					</div>
				</div>

				<div class="md-sequential-wrap" id="mdSequentialWrap" style="display:none;">
					<div class="md-seq-card">
						<div class="md-seq-head" id="mdSeqHead" role="button" tabindex="0">
							<span id="mdSeqHeadText">การขอบรรจุโครงการ (ส่งตามลำดับ)</span>
							<i class="fa fa-chevron-down md-seq-head-chevron" id="mdSeqHeadChevron" style="display:none;"></i>
						</div>
						<div class="md-seq-body" id="mdSeqBody">
							<button type="button" class="md-seq-fold-toggle" id="mdSeqCertFoldBtn" style="display:none;">
								<span id="mdSeqCertFoldText">การขอบรรจุแผน</span>
								<i class="fa fa-chevron-down"></i>
							</button>
							<div id="mdSeqCertWrap">
							<div class="md-seq-row">
								<span class="md-seq-k">ผู้ยื่นเสนอ :</span>
								<span class="md-seq-submitter" id="mdSeqSubmitter">-</span>
							</div>
							<div class="md-seq-row">
								<span class="md-seq-k">ลำดับการตรวจสอบ :</span>
							</div>
							<ol class="md-seq-list" id="mdSeqList"></ol>
							</div>
							<div class="md-plan-unit-wrap" id="mdPlanUnitWrap" style="display:none;">
								<div class="md-plan-unit-head">
									<div class="md-plan-unit-section-title">การพิจารณาจากหน่วยแผนและงบประมาณ</div>
									<div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2">
										<span class="md-plan-unit-title">หน่วยแผนและงบประมาณ</span>
										<span class="md-seq-warn" id="mdPlanUnitStatusLine" style="display:none;">รอตรวจสอบ!</span>
									</div>
									<div id="mdPlanUnitHeadBadges" class="md-plan-unit-head-badges" style="display:none;"></div>
									<div class="md-plan-unit-officer-line" id="mdPlanUnitOfficerLine"></div>
								</div>
								<div id="mdPlanUnitResult" style="display:none;">
									<div class="md-seq-note-line mb-0" id="mdPlanUnitResultInner"></div>
								</div>
								<div class="mt-2" id="mdPlanUnitTimelineWrap" style="display:none;">
									<div id="mdPlanUnitTimeline"></div>
								</div>
								<div id="mdPlanUnitActBlock">
									<div id="mdPlanUnitMsg" class="mt-2" style="display:none;"></div>
									<div class="md-seq-actions" id="mdPlanUnitDecideActions" style="display:none;">
										<button type="button" class="btn btn-md-seq btn-md-seq-pass" id="mdPlanBtnInclude">นำเข้าที่ประชุม</button>
									</div>
								</div>
								<div class="mt-3" id="mdPlanUnitReplyWrap" style="display:none;">
									<div class="md-reply-title mb-2"><i class="fa fa-paper-plane"></i>ส่งคำชี้แจงถึงหน่วยแผนและงบประมาณ</div>
									<label class="md-seq-note-label" for="mdPlanUnitReply">ข้อเสนอแนะ/ข้อชี้แจง :</label>
									<textarea class="form-control md-seq-note" id="mdPlanUnitReply" rows="3" placeholder="พิมพ์คำชี้แจง/ตอบกลับเพื่อส่งให้หน่วยแผนพิจารณาอีกครั้ง"></textarea>
									<div id="mdPlanUnitReplyMsg" class="mt-2" style="display:none;"></div>
									<div class="md-reply-actions">
										<button type="button" class="btn btn-warning btn-sm" id="mdPlanUnitReplySend">บันทึก</button>
									</div>
								</div>
							</div>
							<div class="mt-3" id="mdActionLogWrap" style="display:none;">
								<div class="md-reply-title mb-2"><i class="fa fa-stream"></i>ลำดับการกระทำ</div>
								<div id="mdActionLogList"></div>
							</div>

							<div class="mt-3" id="mdSubmitterReplyWrap" style="display:none;">
								<div class="md-reply-title mb-2"><i class="fa fa-comment-dots"></i>คำชี้แจง/ตอบกลับจากผู้ยื่นเสนอ</div>
								<label class="md-seq-note-label" for="mdSubmitterReply">ข้อเสนอแนะ/ข้อชี้แจง :</label>
								<textarea class="form-control" id="mdSubmitterReply" rows="3" placeholder="พิมพ์คำชี้แจง/ตอบกลับเพื่อประกอบการพิจารณา"></textarea>
								<div id="mdSubmitterReplyMsg" class="mt-2" style="display:none;"></div>
								<div class="md-reply-actions">
									<button type="button" class="btn btn-warning btn-sm" id="mdSubmitterReplySave" style="display:none;">
										ยืนยันส่งกลับผู้ตรวจสอบ
									</button>
								</div>
							</div>
							<hr class="md-seq-hr">
							<div id="mdSeqActorBlock" style="display:none;">
								<div class="md-review-title">การตรวจสอบ</div>
								<div class="md-seq-actor-title" id="mdSeqCurrentName"></div>
								<label class="md-seq-note-label" for="mdCertNote">ข้อเสนอแนะ/ข้อชี้แจง :</label>
								<form method="post" action="?page=project_approve" id="mdCertActionForm" class="mb-0">
									<input type="hidden" name="request_id" id="mdCertRequestId" value="">
									<input type="hidden" name="project_id" id="mdCertProjectId" value="">
									<input type="hidden" id="mdOperateRequestId" value="">
									<input type="hidden" name="flow_type" id="mdCertFlowType" value="include">
									<input type="hidden" name="op" id="mdCertOp" value="">
									<input type="hidden" name="sort_order" id="mdCertSortOrder" value="">
									<input type="hidden" name="years_select" id="mdCertYearsSelect" value="">
									<input type="hidden" name="project_group" id="mdCertProjectGroup" value="draft">
									<input type="hidden" id="mdIncludeProjectIds" value="">
									<textarea name="certifier_note" class="form-control md-seq-note" id="mdCertNote" rows="4" placeholder="ระบุข้อเสนอแนะหรือข้อชี้แจง (ถ้ามี)"></textarea>
									<div id="mdSeqAjaxMsg" class="mt-2" style="display:none;"></div>
									<div class="md-seq-actions">
										<button type="button" class="btn btn-md-seq btn-md-seq-pass" id="mdCertBtnPass"
											onclick="if(window.pmProjectApproveConfirm){window.pmProjectApproveConfirm('pass');} return false;">ผ่าน</button>
										<button type="button" class="btn btn-md-seq btn-md-seq-back" id="mdCertBtnBack"
											onclick="if(window.pmProjectApproveConfirm){window.pmProjectApproveConfirm('back');} return false;">ตีกลับเพื่อแก้ไข</button>
									</div>
								</form>
								<p class="md-seq-hint mb-0" id="mdSeqHint"><i class="fa fa-level-down text-secondary mr-1"></i> <strong>ผ่าน</strong> ระบบจะส่งคำขอไปยังผู้ตรวจสอบลำดับถัดไป</p>
							</div>
						</div>
					</div>

					<div class="mt-3 md-plan-admin-rechain-wrap" id="mdPlanAdminRechainWrap" style="display:none;">
						<div class="md-pa-rechain-nested">
							<div class="md-pa-rechain-nested-head">
								<div class="mb-0">
									เลือกผู้อนุมัติโครงการ <span class="md-pa-rechain-head-sub">(ระบบจะส่งตามลำดับ)</span>
								</div>
								<button type="button" class="btn btn-sm btn-outline-success bg-white shadow-sm flex-shrink-0 font-weight-bold" id="mdPaRechainBtnAdd">+ เพิ่ม</button>
							</div>
							<div class="md-pa-rechain-nested-body">
								<input type="hidden" id="mdPaRechainProjectId" value="">
								<div id="mdPaRechainCertRows"></div>
								<div class="text-center mt-3 pt-1">
									<button type="button" class="btn btn-success btn-lg font-weight-bold px-4" id="mdPaRechainSubmit">ส่งให้ผู้อนุมัติพิจารณา</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer d-flex flex-wrap align-items-center justify-content-between">
				<button type="button" class="btn btn-outline-primary btn-sm" id="mdCopyApproveLinkBtn" style="display:none;" title="คัดลอกลิงก์ส่งให้ผู้ตรวจสอบเปิดหน้านี้โดยตรง (ต้องล็อกอินด้วยบัญชีผู้ตรวจสอบ)">
					<i class="fa fa-link mr-1"></i> คัดลอกลิงก์ส่งผู้ตรวจสอบ
				</button>
				<button type="button" class="btn btn-secondary ml-auto" id="mdRequestDetailCloseBtn" data-dismiss="modal">ปิด</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal: แก้ไขผู้พิจารณา/ตำแหน่ง (Plan Admin — ลำดับขอดำเนินการ) -->
<div class="modal fade" id="modalEditOperateCertRow" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-light">
				<h5 class="modal-title">แก้ไขผู้พิจารณาโครงการ</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="ecrProjectId" value="">
				<input type="hidden" id="ecrSortOrder" value="">
				<div class="form-group">
					<label>ผู้พิจารณาโครงการ <span class="text-danger">*</span></label>
					<select class="form-control" id="ecrStaffSelect"></select>
					<small class="text-muted">พิมพ์ค้นหาได้</small>
				</div>
				<div class="form-group mb-0">
					<label>ตำแหน่ง <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="ecrPosition" placeholder="ตำแหน่ง* (กรอก)" autocomplete="off">
				</div>
				<div id="ecrMsg" class="mt-2 small" style="display:none;"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
				<button type="button" class="btn btn-primary" id="ecrSaveBtn">บันทึก</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal: ยืนยันการทำรายการ (สวยๆ) -->
<div class="modal fade" id="modalConfirmAction" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content cf-card">
			<div class="cf-head">
				<div class="cf-icon" id="cfIcon"><i class="fa fa-question"></i></div>
				<div style="min-width:0;">
					<div class="cf-title" id="cfTitle">ยืนยันการทำรายการ</div>
				</div>
				<button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Close" id="cfCloseX"
					onclick="if(window.pmProjectApproveConfirmClose){window.pmProjectApproveConfirmClose();} return false;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="cf-body" id="cfBody">-</div>
			<div class="cf-footer">
				<button type="button" class="btn btn-cf btn-cf-cancel" data-dismiss="modal" id="cfCancelBtn"
					onclick="if(window.pmProjectApproveConfirmClose){window.pmProjectApproveConfirmClose();} return false;">ยกเลิก</button>
				<button type="button" class="btn btn-cf btn-cf-ok" id="cfOkBtn">ยืนยัน</button>
			</div>
		</div>
	</div>
</div>

<script>
(function(){
	var pmApprYearsSelectPage = <?php echo json_encode((string)$years_select, JSON_UNESCAPED_UNICODE); ?>;
	var currentProjectGroup = <?php echo json_encode((string)$project_group, JSON_UNESCAPED_UNICODE); ?>;
	var currentApprFlow = <?php echo json_encode((string)$pm_appr_flow, JSON_UNESCAPED_UNICODE); ?>;
	var currentApprTab = <?php echo json_encode((string)$pm_appr_tab, JSON_UNESCAPED_UNICODE); ?>;
	var pmApprDeepLink = {
		requestId: <?php echo (int)$pm_appr_open_request_id; ?>,
		projectId: <?php echo (int)$pm_appr_open_project_id; ?>,
		flow: <?php echo json_encode((string)$pm_appr_open_flow, JSON_UNESCAPED_UNICODE); ?>,
		handled: false
	};
	// อย่า return ตั้งแต่ต้น — ถ้าไม่มีตารางใน DOM ตอนนี้ โค้ด modal/ยืนยัน/บันทึกจะไม่ถูก bind เลย
	var $table = $('#projectApproveTable');
	var tableOk = $table.length > 0;
	var perPage = 20;
	var currentPage = 1;
	var $rows = tableOk ? $table.find('tbody tr[data-approve-row="1"]') : $();
	var $search = $('#projectApproveSearch');
	var $statusFilter = $('#projectApproveStatusFilter');
	var $objectiveFilter = $('#projectApproveObjectiveFilter');
	var $pager = $('#projectApprovePager');
	var $summary = $('#projectApproveSummary');

	var sortKey = String(new URL(window.location.href).searchParams.get('s') || 'date');
	var sortDir = String(new URL(window.location.href).searchParams.get('d') || 'desc');
	var qInit = String(new URL(window.location.href).searchParams.get('q') || '').trim();
	var pInit = parseInt(new URL(window.location.href).searchParams.get('p') || '1', 10);
	if (!isNaN(pInit) && pInit > 0) currentPage = pInit;
	if (qInit !== '') $search.val(qInit);

	function rowMatchesTab($tr, tab){
		tab = String(tab || currentApprTab || 'pending');
		var isSub = String($tr.attr('data-is-submitter') || '') === '1';
		var isRev = String($tr.attr('data-is-reviewer') || '') === '1';
		var sg = String($tr.attr('data-status-group') || '');
		if (tab === 'sent') return isSub;
		if (tab === 'pending') return (isRev && sg === 'pending') || (isSub && sg === 'returned');
		if (tab === 'done') return sg === 'done' || (isRev && (sg === 'returned' || sg === 'rejected'));
		return true;
	}

	function getFilteredRows(){
		if (!tableOk) return $();
		var q = String($search.val() || '').toLowerCase().trim();
		var st = String($statusFilter.val() || '');
		var obj = $objectiveFilter.length ? String($objectiveFilter.val() || '') : '';
		return $rows.filter(function(){
			var $tr = $(this);
			var okQ = true;
			if (q) okQ = String($tr.attr('data-search') || '').indexOf(q) !== -1;
			if (!okQ) return false;
			if (st) {
				var sg = String($tr.attr('data-status-group') || '');
				if (sg !== st) return false;
			}
			if (obj) {
				var o = String($tr.attr('data-objective') || '');
				if (o !== obj) return false;
			}
			if (!rowMatchesTab($tr, currentApprTab)) return false;
			return true;
		});
	}

	function refreshTabBadges(){
		var nSent = 0, nPend = 0, nDone = 0, nSentFix = 0;
		$rows.each(function(){
			var $tr = $(this);
			if (rowMatchesTab($tr, 'sent')) {
				nSent++;
				if (String($tr.attr('data-is-submitter') || '') === '1'
					&& String($tr.attr('data-status-group') || '') === 'returned'
					&& String($tr.attr('data-unread') || '') === '1') {
					nSentFix++;
				}
			}
			if (rowMatchesTab($tr, 'pending')) nPend++;
			if (rowMatchesTab($tr, 'done')) nDone++;
		});
		var setBadge = function(tab, n, hot){
			var $b = $('#pmApprStatusTabs [data-tab-count="'+tab+'"]');
			$b.text(String(n));
			$b.toggleClass('is-hot', !!hot);
		};
		setBadge('sent', nSent, nSentFix > 0);
		setBadge('pending', nPend, nPend > 0);
		setBadge('done', nDone, false);
		var $note = $('#pmApprReturnedNotice');
		if ($note.length) {
			if (nSentFix > 0) {
				$note.text('มีเอกสารที่ถูกตีกลับให้แก้ไข ' + nSentFix + ' รายการ — ดูที่แท็บเอกสารส่ง').show();
			} else {
				$note.hide();
			}
		}
	}

	function syncApprTabUrl(){
		try {
			var u = new URL(window.location.href);
			u.searchParams.set('page', 'project_approve');
			u.searchParams.set('flow', currentApprFlow || 'include');
			u.searchParams.set('tab', currentApprTab || 'pending');
			window.history.replaceState({}, '', u.toString());
		} catch(e) {}
	}

	function getSortValue($tr, key){
		key = key || sortKey || 'date';
		if (key === 'date') return parseInt($tr.attr('data-date') || '0', 10) || 0;
		if (key === 'subject') return String($tr.attr('data-subject') || '').toLowerCase();
		if (key === 'sender') return String($tr.attr('data-sender') || '').toLowerCase();
		if (key === 'objective') return String($tr.attr('data-objective') || '').toLowerCase();
		if (key === 'code') return String($tr.attr('data-code') || '').toLowerCase();
		if (key === 'name') return String($tr.attr('data-name') || '').toLowerCase();
		if (key === 'unit') return String($tr.attr('data-unit') || '').toLowerCase();
		if (key === 'budget') return parseFloat($tr.attr('data-budget') || '0') || 0;
		if (key === 'status') return String($tr.attr('data-status') || '').toLowerCase();
		return '';
	}

	function renderPager(totalPages){
		$pager.empty();
		if (totalPages <= 1) return;
		var addBtn = function(label, page, disabled, active){
			var cls = 'btn btn-sm btn-outline-secondary';
			if (active) cls += ' active';
			var dis = disabled ? ' disabled' : '';
			$pager.append('<button type="button" class="'+cls+dis+'" data-page="'+page+'">'+label+'</button>');
		};
		addBtn('&laquo;', currentPage - 1, currentPage <= 1, false);
		for (var p = 1; p <= totalPages; p++) addBtn(String(p), p, false, p === currentPage);
		addBtn('&raquo;', currentPage + 1, currentPage >= totalPages, false);
	}

	function updateSortButtons(){
		$table.find('.pm-sort-btn').removeClass('active');
		if (!sortKey) return;
		var $btn = $table.find('.pm-sort-btn[data-sort-key="' + sortKey + '"][data-sort-dir="' + sortDir + '"]');
		if ($btn.length) $btn.addClass('active');
	}

	function renderTable(){
		if (!tableOk) return;
		var filtered = getFilteredRows();
		var arr = filtered.toArray();
		var keyNow = sortKey || 'date';
		var dirNow = sortDir || 'desc';
		arr.sort(function(a, b){
			var $a = $(a), $b = $(b);
			var va = getSortValue($a, keyNow), vb = getSortValue($b, keyNow);
			if (va < vb) return dirNow === 'asc' ? -1 : 1;
			if (va > vb) return dirNow === 'asc' ? 1 : -1;
			var ida = parseInt($a.attr('data-request-id') || '0', 10) || 0;
			var idb = parseInt($b.attr('data-request-id') || '0', 10) || 0;
			return idb - ida;
		});
		var total = arr.length;
		var totalPages = Math.max(1, Math.ceil(total / perPage));
		if (currentPage > totalPages) currentPage = totalPages;
		if (currentPage < 1) currentPage = 1;
		var start = (currentPage - 1) * perPage;
		var end = start + perPage;
		var $tbody = $table.find('tbody');

		// จัดลำดับแถวใน DOM ให้ตรงกับผลการเรียง
		for (var si = 0; si < arr.length; si++) {
			$tbody.append(arr[si]);
		}

		$rows.hide();
		$(arr.slice(start, end)).show().each(function(i){
			$(this).find('td.pm-col-no').text(start + i + 1);
		});

		$table.find('tbody tr#project-approve-empty-row').remove();
		if (total === 0) {
			$table.find('tbody').append('<tr id="project-approve-empty-row"><td colspan="7" class="text-center text-secondary py-4">ไม่พบโครงการ</td></tr>');
		}

		$summary.text('ทั้งหมด ' + total + ' โครงการ | หน้า ' + currentPage + '/' + totalPages + ' | แสดงครั้งละ ' + perPage + ' โครงการ');
		refreshTabBadges();
		renderPager(totalPages);
		updateSortButtons();
	}

	if (tableOk) {
	$search.on('input', function(){ currentPage = 1; renderTable(); });
	$statusFilter.on('change', function(){ currentPage = 1; renderTable(); });
	if ($objectiveFilter.length) {
		$objectiveFilter.on('change', function(){ currentPage = 1; renderTable(); });
	}
	$(document).on('click', '#pmApprStatusTabs [data-appr-tab]', function(e){
		e.preventDefault();
		var tab = String($(this).attr('data-appr-tab') || '');
		if (!tab) return;
		currentApprTab = tab;
		$('#pmApprStatusTabs .pm-appr-tab').removeClass('is-active');
		$(this).addClass('is-active');
		currentPage = 1;
		syncApprTabUrl();
		renderTable();
	});
	$pager.on('click', 'button[data-page]', function(){
		var p = parseInt($(this).attr('data-page') || '1', 10);
		if (isNaN(p) || $(this).is(':disabled')) return;
		currentPage = p;
		renderTable();
	});
	$table.on('click', '.pm-sort-btn', function(){
		var k = String($(this).attr('data-sort-key') || '');
		var d = String($(this).attr('data-sort-dir') || '');
		if (!k) return;
		sortKey = k;
		sortDir = (d === 'desc') ? 'desc' : 'asc';
		currentPage = 1;
		renderTable();
	});

	renderTable();
	} // tableOk: ตารางรายการเท่านั้น — โค้ดด้านล่าง (modal/บันทึก) รันเสมอ

	function pmApprMarkInboxRead($tr){
		if (!$tr || !$tr.length) return;
		var flow = String($tr.attr('data-flow') || currentApprFlow || '');
		var ref = String($tr.attr('data-inbox-ref') || '');
		if (!flow || !ref) return;
		if (String($tr.attr('data-unread') || '') !== '1') return;
		$tr.attr('data-unread', '0');
		$.ajax({
			url: '?page=project_approve',
			type: 'POST',
			dataType: 'json',
			data: { page: 'project_approve', op: 'mark_approve_inbox_read', flow: flow, ref_key: ref }
		});
	}

	// modal detail
	function fmtThaiDateTime(dt){
		if (!dt) return '-';
		var s = String(dt);
		// dt: "YYYY-mm-dd HH:ii:ss"
		var d = new Date(s.replace(' ', 'T'));
		if (isNaN(d.getTime())) return s;
		var months = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
		var day = d.getDate();
		var mon = months[d.getMonth()] || '';
		var y = d.getFullYear() + 543;
		return day + ' ' + mon + ' ' + String(y).slice(-2);
	}
	var lastDetailResp = null;
	function pmApprRenderReturnedFixUi(resp){
		resp = resp || {};
		var items = Array.isArray(resp.returned_items) ? resp.returned_items : [];
		var $hist = $('#mdReturnedFixHistory');
		var $wrap = $('#mdReturnedFixHistoryWrap');
		if (!items.length) {
			$wrap.hide();
			$hist.empty();
		} else {
			var html = '';
			items.forEach(function(it){
				var code = String(it.code_pre_project || it.code || '');
				var name = String(it.name_project || it.name || '');
				var fixNote = String(it.fix_note || '').trim();
				var fixSt = String(it.fix_status || '');
				var fixAt = String(it.fix_at || '');
				var stLabel = (fixSt === 'admin_sent') ? 'ส่งผู้รับรองแล้ว'
					: (fixSt === 'confirmed') ? 'ยืนยันการแก้ไขแล้ว'
					: 'รอแก้ไข/ยืนยัน';
				var stCls = (fixSt === 'confirmed' || fixSt === 'admin_sent') ? 'text-success' : 'text-warning';
				html += '<div class="mb-2 pb-2 border-bottom">'
					+ '<div class="font-weight-bold">' + $('<div/>').text((code ? code + ' — ' : '') + (name || ('โครงการ #' + (it.project_id || '')))).html() + '</div>'
					+ '<div class="small ' + stCls + '">' + stLabel + (fixAt ? (' · ' + fixAt) : '') + '</div>';
				if (fixNote) {
					html += '<div class="mt-1 text-90" style="white-space:pre-wrap;">ชี้แจง: ' + $('<div/>').text(fixNote).html() + '</div>';
				} else {
					html += '<div class="mt-1 text-muted text-85">ยังไม่มีคำชี้แจง</div>';
				}
				html += '</div>';
			});
			$hist.html(html);
			$wrap.show();
		}
		if (resp.can_admin_send_fix) {
			$('#mdAdminSendFixWrap').show();
			$('#mdAdminSendFixMsg').hide().text('');
		} else {
			$('#mdAdminSendFixWrap').hide();
		}
	}
	$(document).on('click', '#mdAdminSendFixBtn', function(e){
		e.preventDefault();
		var rid = parseInt($('#mdRequestId').val() || '0', 10);
		if (!rid) return;
		var $btn = $(this);
		$btn.prop('disabled', true);
		$('#mdAdminSendFixMsg').removeClass('text-danger text-success').addClass('text-muted').text('กำลังส่ง...').show();
		$.ajax({
			type: 'POST',
			url: '?page=project_approve',
			data: {
				ajax: '1',
				op: 'admin_send_include_fix',
				request_id: rid,
				years_select: String($('#mdCertYearsSelect').val() || '')
			},
			dataType: 'text'
		}).done(function(raw){
			var r = null;
			try { r = JSON.parse(String(raw || '')); } catch(_e) { r = null; }
			if (!r || !r.ok) {
				$('#mdAdminSendFixMsg').removeClass('text-muted text-success').addClass('text-danger').text((r && r.msg) ? r.msg : 'ส่งไม่สำเร็จ').show();
				$btn.prop('disabled', false);
				return;
			}
			$('#mdAdminSendFixMsg').removeClass('text-muted text-danger').addClass('text-success').text(r.msg || 'ส่งเรียบร้อย').show();
			openIncludeRequestModal(rid);
		}).fail(function(){
			$('#mdAdminSendFixMsg').removeClass('text-muted text-success').addClass('text-danger').text('ส่งไม่สำเร็จ').show();
			$btn.prop('disabled', false);
		});
	});
	var submitterReplyEditing = false;
	function fmtSumBudgetBaht(v){
		if (v === null || v === undefined || v === '') return '';
		var n = parseFloat(String(v).replace(/,/g, '').trim());
		if (!isFinite(n)) return $('<div/>').text(String(v)).html();
		return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}
	function renderSummaryRows(rows, respFull){
		var src = respFull || lastDetailResp;
		var $b = $('#mdSummaryBody');
		var $h = $('#mdSummaryHead');
		var $tbl = $('#mdSummaryBody').closest('table.md-summary-table');
		if (!$b.length) return;
		var flowType = String((src && src.flow_type) ? src.flow_type : 'include');
		if (flowType === 'operate' || flowType === 'report' || flowType === 'change') {
			if ($tbl.length) $tbl.addClass('md-operate');
			if ($h.length) {
				$h.html('<tr><th style="width:220px;">รายการ</th><th>รายละเอียด</th></tr>');
			}
			var od = flowType === 'report'
				? ((src && src.report_detail) ? src.report_detail : {})
				: (flowType === 'change'
					? ((src && src.change_detail) ? src.change_detail : {})
					: ((src && src.operate_detail) ? src.operate_detail : {}));
			var code = String(od.project_code || '-');
			var name = String(od.project_name || '-');
			var budgetFmt = fmtSumBudgetBaht(od.budget_baht);
			if (!budgetFmt) budgetFmt = '0.00';
			var bsrc = String(od.budget_source || '-');
			var html2 = ''
				+ '<tr><td>รหัสโครงการ</td><td>' + $('<div/>').text(code).html() + '</td></tr>'
				+ '<tr><td>ชื่อโครงการ</td><td>' + $('<div/>').text(name).html() + '</td></tr>'
				+ '<tr><td>งบประมาณ (บาท)</td><td>' + $('<div/>').text(budgetFmt).html() + '</td></tr>'
				+ '<tr><td>แหล่งงบประมาณ</td><td>' + $('<div/>').text(bsrc).html() + '</td></tr>';
			if (flowType === 'operate' && Number(od.is_midyear) === 1) {
				html2 += '<tr><td>เหตุผลการขอระหว่างปี</td><td>' + $('<div/>').text(String(od.midyear_reason || '-')).html().replace(/\n/g,'<br>') + '</td></tr>';
			}
			if (flowType === 'change') {
				html2 += '<tr><td>เหตุผลการขอเปลี่ยนแปลง</td><td>' + $('<div/>').text(String(od.reason || '-')).html().replace(/\n/g,'<br>') + '</td></tr>';
				var dets = Array.isArray(od.details) ? od.details : [];
				if (dets.length) {
					var dhtml = '<table class="table table-sm table-bordered mb-0"><thead><tr><th>รายละเอียดเดิม</th><th>รายละเอียดการเปลี่ยนแปลง</th></tr></thead><tbody>';
					dets.forEach(function(d){
						dhtml += '<tr><td>' + $('<div/>').text(String((d && d.original) ? d.original : '-')).html().replace(/\n/g,'<br>')
							+ '</td><td>' + $('<div/>').text(String((d && d.changed) ? d.changed : '-')).html().replace(/\n/g,'<br>') + '</td></tr>';
					});
					dhtml += '</tbody></table>';
					html2 += '<tr><td>รายละเอียดการเปลี่ยนแปลง</td><td>' + dhtml + '</td></tr>';
				}
				var files = Array.isArray(od.files) ? od.files : [];
				if (files.length) {
					var fhtml = '';
					files.forEach(function(f){
						var fn = String((f && f.name) ? f.name : 'ไฟล์ PDF');
						var fu = String((f && f.url) ? f.url : '#');
						fhtml += '<div><a href="' + $('<div/>').text(fu).html() + '" target="_blank" rel="noopener"><i class="fa fa-file-pdf text-danger mr-1"></i>' + $('<div/>').text(fn).html() + '</a></div>';
					});
					html2 += '<tr><td>เอกสารแนบ</td><td>' + fhtml + '</td></tr>';
				}
				var appliedAt = String(od.applied_at || '').trim();
				if (appliedAt) {
					html2 += '<tr><td>หน่วยแผนแก้ไขแล้ว</td><td>'
						+ $('<div/>').text(String(od.applied_by_name || '-') + ' · ' + String(od.applied_at_th || appliedAt)).html()
						+ (String(od.apply_note || '').trim() ? '<div class="mt-1">' + $('<div/>').text(String(od.apply_note)).html().replace(/\n/g,'<br>') + '</div>' : '')
						+ '</td></tr>';
				} else if (String((src && src.request && src.request.status) || '') === 'approved') {
					var mu = String(od.manage_url || '');
					html2 += '<tr><td>การแก้ไขโครงการ</td><td>รอหน่วยแผนแก้ไขโครงการ'
						+ (mu ? ' — <a href="' + $('<div/>').text(mu).html() + '">ไปที่หน้าโครงการ</a>' : '')
						+ '</td></tr>';
				}
			}
			$b.html(html2);
			return;
		}
		if ($tbl.length) $tbl.removeClass('md-operate');
		if ($h.length) {
			$h.html('<tr><th class="text-center">ความสอดคล้อง</th><th class="text-center" style="width:130px;">จำนวนโครงการ</th><th class="text-center" style="width:140px;">งบประมาณ (บาท)</th><th class="text-center" style="min-width:220px;">โครงการ</th><th class="text-center" style="min-width:260px;">ตัวชี้วัดเป้าหมายโครงการ</th></tr>');
		}
		if (!rows || !rows.length) {
			$b.html('<tr><td colspan="5" class="text-center text-muted">ไม่พบข้อมูล</td></tr>');
			return;
		}
		var ysLink = '';
		try {
			var yrSrc = src || lastDetailResp;
			if (yrSrc && yrSrc.request && yrSrc.request.budget_year) {
				ysLink = String(yrSrc.request.budget_year || '');
			}
		} catch (eLd) {}
		if (!ysLink && typeof pmApprYearsSelectPage !== 'undefined' && pmApprYearsSelectPage) {
			ysLink = String(pmApprYearsSelectPage);
		}
		if (!ysLink) {
			try { ysLink = String(new URL(window.location.href).searchParams.get('years_select') || ''); } catch (eYs) {}
		}
		var html = '';
		rows.forEach(function(r){
			var kind = String(r.row_kind || 'data');
			var label = String(r.consistency_label || '');
			var pc = r.project_count === null || r.project_count === undefined ? '' : String(r.project_count);
			var bbHtml = fmtSumBudgetBaht(r.budget_baht);
			var items = (r.include_project_items && r.include_project_items.length) ? r.include_project_items : [];
			var projHtml = '';
			var indHtml = '';
			if (kind === 'data') {
				if (items.length) {
					projHtml = '<ol class="mb-0 pl-3 md-sum-proj-list text-left">';
					indHtml = '<ol class="mb-0 pl-3 md-sum-ind-list text-left">';
					items.forEach(function(it){
						var pname = String((it && it.name) ? it.name : '');
						var pid = parseInt(it && it.project_id ? it.project_id : '0', 10);
						var href = '?page=project_manage&op=add_project&project_id=' + pid + '&years_select=' + encodeURIComponent(ysLink || '');
						projHtml += '<li><a href="' + href + '" data-project-id="' + pid + '" target="_blank" rel="noopener">' + $('<div/>').text(pname).html() + '</a></li>';
						var kpis = (it && it.kpi_names && it.kpi_names.length) ? it.kpi_names : [];
						var oneLine = kpis.length ? kpis.join(' ') : '-';
						indHtml += '<li>' + $('<div/>').text(oneLine).html() + '</li>';
					});
					projHtml += '</ol>';
					indHtml += '</ol>';
				} else {
					projHtml = '<span class="text-muted">-</span>';
					indHtml = '<span class="text-muted">-</span>';
				}
			}
			if (kind === 'header') {
				html += '<tr style="background:#f3f4f6;font-weight:900;"><td colspan="5">' + $('<div/>').text(label).html() + '</td></tr>';
				return;
			}
			if (kind === 'total') {
				html += '<tr style="font-weight:950;background:#fff7ed;"><td>' + $('<div/>').text(label || 'รวม').html() + '</td>'
					+ '<td class="text-center">' + $('<div/>').text(pc).html() + '</td>'
					+ '<td class="text-right">' + bbHtml + '</td>'
					+ '<td class="text-muted text-center">-</td><td class="text-muted text-center">-</td></tr>';
				return;
			}
			html += '<tr><td>' + $('<div/>').text(label).html() + '</td>'
				+ '<td class="text-center">' + $('<div/>').text(pc).html() + '</td>'
				+ '<td class="text-right">' + bbHtml + '</td>'
				+ '<td class="align-top">' + projHtml + '</td>'
				+ '<td class="align-top">' + indHtml + '</td></tr>';
		});
		$b.html(html);
	}

	var __mdPaRechainStaffOpts = [];
	var __mdOpStaffEditOpts = [];
	function mdPaRechainSelInit($sel){
		if (!$sel || !$sel.length || typeof $.fn.select2 !== 'function') return;
		try { if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy'); } catch(_e0){}
		// แนบกับ .modal-body (หรือ .modal-content) — อย่าใช้แค่ #modalRequestDetail (.modal) เพราะ Select2 คำนวณ offset กับ Bootstrap modal แล้วลิสต์เด้งไปด้านบน
		var $ddParent = $('#modalRequestDetail .modal-body');
		if (!$ddParent.length) {
			$ddParent = $('#modalRequestDetail .modal-content');
		}
		if (!$ddParent.length) {
			$ddParent = $('#modalRequestDetail');
		}
		$sel.select2({
			placeholder: '- เลือก -',
			allowClear: true,
			width: '100%',
			dropdownParent: $ddParent
		});
		$sel.off('change.pmCertPos').on('change.pmCertPos', function(){
			var pos = String($(this).find('option:selected').attr('data-position') || '');
			$(this).closest('.md-pa-rc-row').find('input.md-pa-rc-pos').val(pos);
		});
	}
	function mdPaRechainToggleRm(){
		var n = $('#mdPaRechainCertRows .md-pa-rc-row').length;
		$('#mdPaRechainCertRows .md-pa-rc-rm').css('visibility', n > 1 ? 'visible' : 'hidden');
	}
	function mdPaRechainAddRow(){
		var $c = $('#mdPaRechainCertRows');
		if (!$c.length) return;
		var idx = $c.find('.md-pa-rc-row').length + 1;
		var rm = '<button type="button" class="btn btn-sm btn-link text-danger p-0 md-pa-rc-rm" title="ลบแถว"><i class="fa fa-times"></i></button>';
		var $row = $('<div class="md-pa-rc-row d-flex align-items-start">'
			+ '<div class="flex-shrink-0 pt-2">' + rm + '</div>'
			+ '<div class="md-pa-rc-fields flex-grow-1 pl-2 w-100">'
			+ '<div class="row mx-0">'
			+ '<div class="col-md-6 col-12 mb-2 mb-md-0 pr-md-2">'
			+ '<div class="md-pa-rc-lbl-inline">'
			+ '<label class="mb-0 small font-weight-bold">ผู้อนุมัติโครงการ <span class="text-danger">*</span> <span class="text-muted md-pa-rc-no">(' + idx + ')</span></label>'
			+ '<span class="md-pa-rc-hint">พิมพ์ค้นหาได้</span>'
			+ '</div>'
			+ '<select class="form-control md-pa-rc-sel"></select>'
			+ '</div>'
			+ '<div class="col-md-6 col-12 pl-md-2">'
			+ '<label class="d-block small font-weight-bold mb-1">ตำแหน่ง <span class="text-danger">*</span></label>'
			+ '<input type="text" class="form-control md-pa-rc-pos" placeholder="ตำแหน่ง* (กรอก)" autocomplete="off">'
			+ '</div></div></div></div>');
		$c.append($row);
		var $sel = $row.find('select');
		$sel.append(new Option('- เลือก -', '', false, false));
		(__mdPaRechainStaffOpts || []).forEach(function(o){
			if (!o) return;
			var opt = new Option(o.text, String(o.id), false, false);
			if (o.position) $(opt).attr('data-position', String(o.position));
			$sel.append(opt);
		});
		mdPaRechainSelInit($sel);
		mdPaRechainToggleRm();
	}
	function mdPaRechainRenumber(){
		$('#mdPaRechainCertRows .md-pa-rc-row').each(function(i){
			$(this).find('.md-pa-rc-no').text('(' + (i + 1) + ')');
		});
	}
	function mdPaRechainResetRows(){
		var $c = $('#mdPaRechainCertRows');
		$c.find('select.md-pa-rc-sel').each(function(){
			try { if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy'); } catch(_e1){}
		});
		$c.empty();
		mdPaRechainAddRow();
		mdPaRechainRenumber();
		mdPaRechainToggleRm();
	}
	function mdPaRechainCollect(){
		var out = [];
		var err = '';
		$('#mdPaRechainCertRows .md-pa-rc-row').each(function(){
			var sid = String($(this).find('select').val() || '').trim();
			var pos = String($(this).find('input.md-pa-rc-pos').val() || '').trim();
			if (sid === '' && pos === '') return;
			if (sid === '' || pos === '') {
				err = 'กรุณากรอกผู้อนุมัติและตำแหน่งให้ครบทุกลำดับที่ระบุ';
				return false;
			}
			out.push({ staff_id: parseInt(sid, 10), position: pos });
		});
		if (err === '' && out.length === 0) err = 'กรุณาระบุผู้อนุมัติโครงการอย่างน้อย 1 คน';
		return { ok: err === '', out: out, err: err };
	}
	function renderPlanAdminRechainPanel(resp, flowType){
		var $wrap = $('#mdPlanAdminRechainWrap');
		if (!$wrap.length) return;
		if (String(flowType || '') !== 'operate' && String(flowType || '') !== 'change') {
			try {
				$('#mdPaRechainCertRows').find('select').each(function(){
					try { if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy'); } catch(_e2){}
				});
				$('#mdPaRechainCertRows').empty();
			} catch(_e3){}
			$wrap.hide();
			return;
		}
		var pr = (String(flowType || '') === 'change')
			? (resp && resp.plan_admin_change_rechain ? resp.plan_admin_change_rechain : null)
			: (resp && resp.plan_admin_operate_rechain ? resp.plan_admin_operate_rechain : null);
		if (!pr || !pr.show) {
			try {
				$('#mdPaRechainCertRows').find('select').each(function(){
					try { if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy'); } catch(_e4){}
				});
				$('#mdPaRechainCertRows').empty();
			} catch(_e5){}
			$wrap.hide();
			return;
		}
		__mdPaRechainStaffOpts = pr.staff_options || [];
		$('#mdPaRechainProjectId').val(String(pr.project_id || '0'));
		if (pr.change_request_id) {
			$('#mdCertRequestId').val(String(pr.change_request_id));
		}
		$wrap.show();
		mdPaRechainResetRows();
	}

	$(document).on('click', '#mdPaRechainBtnAdd', function(){
		mdPaRechainAddRow();
		mdPaRechainRenumber();
	});
	$(document).on('click', '#mdPaRechainCertRows .md-pa-rc-rm', function(){
		var $c = $('#mdPaRechainCertRows');
		if ($c.find('.md-pa-rc-row').length <= 1) return;
		var $row = $(this).closest('.md-pa-rc-row');
		try { $row.find('select').select2('destroy'); } catch(_e6){}
		$row.remove();
		mdPaRechainRenumber();
		mdPaRechainToggleRm();
	});
	$(document).on('click', '#mdPaRechainSubmit', function(){
		var pid = parseInt($('#mdPaRechainProjectId').val() || '0', 10);
		if (!pid) return;
		var pack = mdPaRechainCollect();
		if (!pack.ok) {
			if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'กรุณากรอกข้อมูล', text: pack.err || '' });
			else alert(pack.err || 'ข้อมูลไม่ครบ');
			return;
		}
		var $btn = $(this).prop('disabled', true);
		var flowNow = String($('#mdCertFlowType').val() || 'include');
		var rechainOp = (flowNow === 'change') ? 'plan_admin_change_rechain_ajax' : 'plan_admin_operate_rechain_ajax';
		var rechainData = {
			page: 'project_manage',
			op: rechainOp,
			project_id: pid,
			certifiers_json: JSON.stringify(pack.out)
		};
		if (flowNow === 'change') {
			rechainData.change_request_id = String($('#mdCertRequestId').val() || '');
		}
		$.ajax({
			url: '?page=project_manage&op=' + rechainOp,
			type: 'POST',
			dataType: 'json',
			data: rechainData
		}).done(function(res){
			if (res && Number(res.ok) === 1) {
				if (typeof Swal !== 'undefined') {
					Swal.fire({ icon: 'success', title: String(res.msg || 'บันทึกเรียบร้อย'), timer: 1400, showConfirmButton: false })
						.then(function(){ window.location.reload(); });
				} else {
					window.location.reload();
				}
				return;
			}
			var msg = (res && res.msg) ? res.msg : 'บันทึกไม่สำเร็จ';
			if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: msg });
			else alert(msg);
		}).fail(function(xhr){
			var hint = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
			try {
				if (xhr && xhr.responseText && /^\s*\{/.test(String(xhr.responseText))) {
					var jr = JSON.parse(xhr.responseText);
					if (jr && jr.msg) hint = String(jr.msg);
				} else if (xhr && xhr.status === 200 && xhr.responseText && xhr.responseText.indexOf('<!') !== -1) {
					hint = 'เซิร์ฟเวอร์ตอบเป็นหน้าเว็บแทน JSON — โปรดรีเฟรชแล้วลองใหม่ หรือติดต่อผู้ดูแลระบบ';
				}
			} catch (_eF) {}
			if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: hint });
			else alert(hint);
		}).always(function(){ $btn.prop('disabled', false); });
	});

	function mdEcrStaffSelectInit(){
		var $sel = $('#ecrStaffSelect');
		if (!$sel.length || typeof $.fn.select2 !== 'function') return;
		try { if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy'); } catch (_e0) {}
		var $dd = $('#modalEditOperateCertRow .modal-body');
		if (!$dd.length) $dd = $('#modalEditOperateCertRow .modal-content');
		$sel.select2({
			placeholder: '- เลือก -',
			allowClear: true,
			width: '100%',
			dropdownParent: $dd
		});
		$sel.off('change.pmCertPos').on('change.pmCertPos', function(){
			var p = String($(this).find('option:selected').attr('data-position') || '');
			$('#ecrPosition').val(p);
		});
	}
	function mdEcrOpenModal(sortOrder){
		var flow = String($('#mdCertFlowType').val() || 'include');
		var so = parseInt(sortOrder || '0', 10) || 0;
		if (so <= 0) return;
		var certs = (lastDetailResp && lastDetailResp.certifiers) ? lastDetailResp.certifiers : [];
		var row = null;
		certs.forEach(function(x){
			if (!x) return;
			if ((parseInt(String(x.sort_order || '0'), 10) || 0) === so) row = x;
		});
		if (!row) return;
		var pid = 0;
		if (flow === 'operate') {
			try {
				if (lastDetailResp && lastDetailResp.request && lastDetailResp.request.id != null) {
					pid = parseInt(lastDetailResp.request.id, 10) || 0;
				}
			} catch (_e1) {}
			if (!pid) pid = parseInt($('#mdCertProjectId').val() || '0', 10) || 0;
		} else {
			// include: ใช้ request_id
			try {
				if (lastDetailResp && lastDetailResp.request && lastDetailResp.request.id != null) {
					pid = parseInt(lastDetailResp.request.id, 10) || 0;
				}
			} catch (_e2) {}
			if (!pid) pid = parseInt($('#mdRequestId').val() || '0', 10) || 0;
		}
		$('#ecrProjectId').val(String(pid));
		$('#ecrSortOrder').val(String(so));
		var $sel = $('#ecrStaffSelect');
		$sel.empty();
		$sel.append(new Option('- เลือก -', '', false, false));
		(__mdOpStaffEditOpts || []).forEach(function(o){
			if (!o) return;
			var opt = new Option(o.text, String(o.id), false, false);
			if (o.position) $(opt).attr('data-position', String(o.position));
			$sel.append(opt);
		});
		var sid = parseInt(row.staff_id, 10) || 0;
		var savedPos = String(row.position_title || '').trim();
		if (sid) {
			$sel.val(String(sid));
		} else {
			$sel.val('');
		}
		mdEcrStaffSelectInit();
		$('#ecrPosition').val(savedPos);
		$('#ecrMsg').hide().text('').removeClass('alert alert-danger alert-success');
		$('#modalEditOperateCertRow').modal('show');
	}
	$(document).on('click', '.md-seq-edit-cert-row', function(e){
		e.preventDefault();
		var so = parseInt($(this).attr('data-sort-order') || '0', 10) || 0;
		mdEcrOpenModal(so);
		return false;
	});
	$('#modalEditOperateCertRow').on('show.bs.modal', function(){
		var z = 1050 + 10 * $('.modal:visible').length;
		$(this).css('z-index', z);
	});
	$('#modalEditOperateCertRow').on('shown.bs.modal', function(){
		var z = parseInt($(this).css('z-index'), 10) || 10650;
		$('.modal-backdrop').last().addClass('ecr-cert-backdrop').css('z-index', z - 1);
	});
	$('#modalEditOperateCertRow').on('hidden.bs.modal', function(){
		try {
			var $s = $('#ecrStaffSelect');
			if ($s.length && $s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
		} catch (_e2) {}
	});
	$(document).on('click', '#ecrSaveBtn', function(){
		var $btn = $(this).prop('disabled', true);
		var flow = String($('#mdCertFlowType').val() || 'include');
		var pid = parseInt($('#ecrProjectId').val() || '0', 10) || 0;
		var so = parseInt($('#ecrSortOrder').val() || '0', 10) || 0;
		var staffId = parseInt($('#ecrStaffSelect').val() || '0', 10) || 0;
		var pos = String($('#ecrPosition').val() || '').trim();
		var $msg = $('#ecrMsg');
		if (!pid || !so || !staffId || !pos) {
			$msg.attr('class', 'mt-2 small alert alert-danger').text('กรุณากรอกผู้พิจารณาและตำแหน่งให้ครบ').show();
			$btn.prop('disabled', false);
			return;
		}
		$msg.hide().text('');
		var url = (flow === 'operate')
			? '?page=project_manage&op=plan_admin_edit_operate_cert_row_ajax'
			: '?page=project_manage&op=plan_admin_edit_include_cert_row_ajax';
		var payload = {
			page: 'project_manage',
			op: (flow === 'operate') ? 'plan_admin_edit_operate_cert_row_ajax' : 'plan_admin_edit_include_cert_row_ajax',
			sort_order: so,
			staff_id: staffId,
			position: pos
		};
		if (flow === 'operate') payload.project_id = pid;
		else payload.request_id = pid;
		$.ajax({ url: url, type: 'POST', dataType: 'json', data: payload }).done(function(res){
			if (res && Number(res.ok) === 1) {
				if (typeof Swal !== 'undefined') {
					Swal.fire({ icon: 'success', title: String(res.msg || 'บันทึกเรียบร้อย'), timer: 1200, showConfirmButton: false });
				}
				$('#modalEditOperateCertRow').modal('hide');
				if (flow === 'operate') {
					var reqId = parseInt($('#mdOperateRequestId').val() || '0', 10) || 0;
					$.ajax({
						type: 'POST',
						url: '?page=project_approve&op=get_operate_project_detail',
						data: {
							page: 'project_approve',
							op: 'get_operate_project_detail',
							project_id: pid,
							request_id: reqId
						},
						dataType: 'text'
					}).done(function(raw){
						var rr = null;
						try { rr = JSON.parse(String(raw || '')); } catch (e2) { rr = null; }
						if (rr && rr.ok) {
							renderSequentialPanel(rr);
							try { renderPlanAdminRechainPanel(rr, 'operate'); } catch (_e3) {}
						}
					});
				} else {
					$.ajax({
						type: 'POST',
						url: '?page=project_approve&op=get_request_detail',
						data: { request_id: pid },
						dataType: 'text'
					}).done(function(raw){
						var rr = null;
						try { rr = JSON.parse(String(raw || '')); } catch (e3) { rr = null; }
						if (rr && rr.ok) {
							renderSequentialPanel(rr);
						}
					});
				}
				return;
			}
			var msg = (res && res.msg) ? String(res.msg) : 'บันทึกไม่สำเร็จ';
			$msg.attr('class', 'mt-2 small alert alert-danger').text(msg).show();
		}).fail(function(xhr){
			var hint = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
			try {
				if (xhr && xhr.responseText && /^\s*\{/.test(String(xhr.responseText))) {
					var jr = JSON.parse(xhr.responseText);
					if (jr && jr.msg) hint = String(jr.msg);
				}
			} catch (_eF) {}
			$msg.attr('class', 'mt-2 small alert alert-danger').text(hint).show();
		}).always(function(){ $btn.prop('disabled', false); });
	});

	function pmApprResolveLinkContext(resp){
		var flow = String((resp && resp.flow_type) ? resp.flow_type : ($('#mdCertFlowType').val() || 'include'));
		var yearsSelect = '';
		try {
			yearsSelect = String((resp && resp.request && resp.request.budget_year) ? resp.request.budget_year : '');
			if (!yearsSelect && resp && resp.years_select) yearsSelect = String(resp.years_select);
			if (!yearsSelect) yearsSelect = String(pmApprYearsSelectPage || '');
		} catch (eYs) {}
		var requestId = 0;
		var projectId = 0;
		if (flow === 'operate') {
			requestId = parseInt((resp && resp.include_request_id) ? resp.include_request_id : ($('#mdOperateRequestId').val() || '0'), 10) || 0;
			projectId = parseInt((resp && resp.request && resp.request.id) ? resp.request.id : ($('#mdCertProjectId').val() || '0'), 10) || 0;
			if (!projectId) projectId = parseInt($('#mdRequestId').val() || '0', 10) || 0;
		} else if (flow === 'change') {
			requestId = parseInt((resp && resp.change_request_id) ? resp.change_request_id : ($('#mdCertRequestId').val() || '0'), 10) || 0;
			projectId = parseInt($('#mdCertProjectId').val() || '0', 10) || 0;
		} else {
			requestId = parseInt((resp && resp.request && resp.request.id) ? resp.request.id : ($('#mdRequestId').val() || '0'), 10) || 0;
		}
		return { flow: flow, requestId: requestId, projectId: projectId, yearsSelect: yearsSelect };
	}
	function pmApprCopyLinkToClipboard(link, $btn){
		link = String(link || '').trim();
		if (!link) return;
		var done = function(){
			if ($btn && $btn.length) {
				var old = $btn.html();
				$btn.html('<i class="fa fa-check"></i> คัดลอกแล้ว');
				setTimeout(function(){ $btn.html(old); }, 1400);
			}
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(link).then(done).catch(function(){
				try { window.prompt('คัดลอกลิงก์นี้:', link); done(); } catch (ePr) {}
			});
		} else {
			try { window.prompt('คัดลอกลิงก์นี้:', link); done(); } catch (ePr2) {}
		}
	}

	function renderSequentialPanel(resp){
		lastDetailResp = resp || lastDetailResp;
		try { window.__pmLastDetailResp = lastDetailResp; } catch(_eLd) {}
		try { rememberIncludeProjectIds(lastDetailResp); } catch(_eRemIds) {}
		try { pmApprRenderReturnedFixUi(resp || lastDetailResp || {}); } catch(_eFixUiR) {}
		__mdOpStaffEditOpts = [];
		try {
			var _soe = (resp && resp.staff_options_edit_row && resp.staff_options_edit_row.length)
				? resp.staff_options_edit_row
				: (lastDetailResp && lastDetailResp.staff_options_edit_row && lastDetailResp.staff_options_edit_row.length ? lastDetailResp.staff_options_edit_row : []);
			if (_soe && _soe.length) {
				__mdOpStaffEditOpts = _soe;
			}
		} catch (_eSo) {}
		var $w = $('#mdSequentialWrap');
		if (!$w.length) return;
		var flowType = String((resp && resp.flow_type) ? resp.flow_type : '');
		if (!flowType && lastDetailResp && lastDetailResp.flow_type) {
			flowType = String(lastDetailResp.flow_type);
		}
		if (!flowType) {
			flowType = 'include';
		}
		var seqHeadFull =
			flowType === 'operate' ? 'การขอดำเนินการโครงการ (ส่งตามลำดับ)' :
			flowType === 'report' ? 'การส่งรายงานผลโครงการ (ส่งตามลำดับ)' :
			flowType === 'change' ? 'การขอเปลี่ยนแปลงโครงการ (ส่งตามลำดับ)' :
			'การขอบรรจุโครงการ (ส่งตามลำดับ)';
		var seqHeadShort =
			flowType === 'operate' ? 'การขอดำเนินโครงการ' :
			flowType === 'report' ? 'การรายงานผลโครงการ' :
			flowType === 'change' ? 'การขอเปลี่ยนแปลงโครงการ' :
			'การขอบรรจุแผน';
		$('#mdSeqHeadText').text(seqHeadFull);
		$('#mdSeqHead').removeClass('md-seq-head-toggle is-collapsed').attr('data-foldable', '0');
		$('#mdSeqHeadChevron').hide();
		$('#mdSeqBody').show();
		$('#mdSeqCertFoldBtn').hide().removeClass('is-collapsed');
		$('#mdSeqCertWrap').show();
		var certs = resp && resp.certifiers ? resp.certifiers : [];
		if (!certs.length) {
			if (flowType === 'operate' || flowType === 'report' || flowType === 'change') {
				$w.show();
				$('#mdSeqSubmitter').text(resp && resp.sender_name ? resp.sender_name : '-');
				var emptyMsg = flowType === 'report'
					? 'ยังไม่มีลำดับผู้ตรวจสอบรายงานผล'
					: (flowType === 'change'
						? 'ยังไม่มีลำดับผู้รับรองคำขอเปลี่ยนแปลง'
						: 'ยังไม่มีลำดับผู้ตรวจสอบในระบบการขอดำเนินการ (หรือยังไม่ได้บันทึกผู้ตรวจสอบในโครงการ)');
				$('#mdSeqList').html('<li class="text-muted py-2 px-2">' + emptyMsg + '</li>');
				try { $('#mdSeqActorBlock').hide(); } catch(_eAb0) {}
			} else {
				$w.hide();
			}
			try { $('#mdPlanUnitWrap').hide(); } catch(_epu0) {}
			try { renderPlanAdminRechainPanel(resp, flowType); } catch (_eRcEarly0) {}
			try { renderPlanUnitPanel(resp); } catch (_ePuEarly0) {}
			return;
		}
		$w.show();
		$('#mdSeqSubmitter').text(resp.sender_name || '-');

		var linkCtx = pmApprResolveLinkContext(resp);
		var baseApproveLink = '';
		if (linkCtx.requestId > 0) {
			baseApproveLink = pmApprBuildApproveLink(linkCtx.requestId, {
				flow: linkCtx.flow,
				projectId: linkCtx.projectId,
				yearsSelect: linkCtx.yearsSelect
			});
		}

		var pl = resp.pipeline || {};
		var sr = resp && resp.submitter_reply ? resp.submitter_reply : null;
		var srCanEdit = !!(sr && sr.can_edit);
		var srNote = sr && sr.note ? String(sr.note) : '';
		var srUpdatedAt = sr && sr.updated_at ? String(sr.updated_at) : '';
		var html = '';
		// จัดกลุ่ม log ตาม sort_order — คำชี้แจงที่ไม่มี sort_order ผูกกับผู้ตีกลับรอบนั้น (ตามเวลา)
		var logsAll = resp && resp.action_logs ? resp.action_logs : [];
		var logsBySo = {};
		var latestRejectedSo = 0;
		var latestRejectedAt = '';
		try {
			var logsChrono = (logsAll || []).slice().sort(function(a, b){
				var da = String((a && a.created_at) ? a.created_at : '');
				var db = String((b && b.created_at) ? b.created_at : '');
				if (da < db) return -1;
				if (da > db) return 1;
				var ia = parseInt((a && a.id) ? a.id : '0', 10) || 0;
				var ib = parseInt((b && b.id) ? b.id : '0', 10) || 0;
				return ia - ib;
			});
			var runningRejectSo = 0;
			logsChrono.forEach(function(it){
				var t = String(it.action_type || '');
				var so = parseInt(it.sort_order || '0', 10) || 0;
				if (t === 'certifier_rejected' && so > 0) {
					runningRejectSo = so;
					latestRejectedSo = so;
					latestRejectedAt = String(it.created_at || '').trim();
				}
				if (so <= 0 && (t === 'submitter_reply_saved' || t === 'submitter_reply_kept' || t === 'project_fix_confirmed' || t === 'admin_fix_sent' || t === 'project_edited')) {
					so = runningRejectSo;
					if (so <= 0 && certs && certs.length) {
						so = parseInt(certs[0].sort_order || '0', 10) || 1;
					}
				}
				if (so <= 0) return;
				if (!logsBySo[so]) logsBySo[so] = [];
				logsBySo[so].push(it);
			});
		} catch(_e1){}

		certs.forEach(function(c){
			var st = String(c.status || '');
			var tag = '';
			if (st === 'approved') tag = '';
			else if (st === 'rejected') tag = '<span class="md-seq-pill md-seq-pill-no" style="max-width:100%;white-space:normal;text-align:center;line-height:1.25;"><i class="fa fa-undo mr-1"></i>ตีกลับเพื่อแก้ไข</span>';
			else if (st === 'waiting_turn') tag = '<span class="md-seq-warn">รอตรวจสอบ!</span>';
			else tag = '<span class="md-seq-muted">รอลำดับก่อนหน้า</span>';

			var pos = String(c.position_title || '').trim();
			var decidedAt = String(c.decided_at || '').trim();
			var note = String(c.certifier_note || '').trim();

			var metaHtml = '';
			// แสดงประวัติการกระทำต่อเนื่องใต้ลำดับเดิม (จาก action log)
			var soNow = parseInt(c.sort_order || '0', 10) || 0;
			var bucket = (soNow > 0 && logsBySo[soNow]) ? logsBySo[soNow] : [];
			if (bucket && bucket.length > 1) {
				bucket = bucket.slice().sort(function(a, b){
					var da = String((a && a.created_at) ? a.created_at : '');
					var db = String((b && b.created_at) ? b.created_at : '');
					if (da < db) return -1;
					if (da > db) return 1;
					var ia = parseInt((a && a.id) ? a.id : '0', 10) || 0;
					var ib = parseInt((b && b.id) ? b.id : '0', 10) || 0;
					if (ia !== ib) return ia - ib;
					return 0;
				});
				var cleanedBucket = [];
				bucket.forEach(function(it){
					var typ = String((it && it.action_type) ? it.action_type : '');
					if (typ === 'admin_fix_sent') {
						return;
					}
					if (typ === 'submitter_reply_saved' || typ === 'project_fix_confirmed') {
						var nk = String((it && it.note) ? it.note : '').replace(/\s+/g, ' ').trim();
						var atMs = Date.parse(String((it && it.created_at) ? it.created_at : '').replace(' ', 'T')) || 0;
						var dup = cleanedBucket.some(function(prev){
							var pt = String((prev && prev.action_type) ? prev.action_type : '');
							if (pt !== 'submitter_reply_saved' && pt !== 'project_fix_confirmed') {
								return false;
							}
							var pnk = String((prev && prev.note) ? prev.note : '').replace(/\s+/g, ' ').trim();
							if (pnk !== nk) {
								return false;
							}
							var pMs = Date.parse(String((prev && prev.created_at) ? prev.created_at : '').replace(' ', 'T')) || 0;
							if (atMs > 0 && pMs > 0) {
								return Math.abs(atMs - pMs) <= 120000;
							}
							return true;
						});
						if (dup) {
							return;
						}
					}
					cleanedBucket.push(it);
				});
				bucket = cleanedBucket;
			}
			if (bucket && bucket.length) {
				metaHtml += '<div class="md-seq-meta"></div>';
				bucket.forEach(function(it){
					var typ = String(it.action_type || '');
					var at = String(it.created_at || '').trim();
					var noteTxt = String(it.note || '').trim();
					var label = '';
					var icon = 'fa-info-circle';
					if (typ === 'admin_fix_sent') {
						return;
					}
					if (typ === 'certifier_rejected') { label = 'ตีกลับเพื่อแก้ไข'; icon = 'fa-undo'; }
					else if (typ === 'certifier_approved' || typ === 'certifier_recheck_approved') { label = ''; icon = 'fa-check-circle'; }
					else if (typ === 'submitter_reply_saved' || typ === 'project_fix_confirmed') { label = 'คำชี้แจง'; icon = 'fa-reply'; }
					else if (typ === 'submitter_reply_kept') { label = 'คำชี้แจงเดิม (เก็บไว้)'; icon = 'fa-archive'; }
					else if (typ === 'project_edited') { label = 'บันทึกการแก้ไขโครงการ'; icon = 'fa-edit'; }
					else { label = typ || 'กิจกรรม'; }
					var dtxt = '';
					if (at) {
						dtxt = '<span class="md-seq-chip md-seq-chip-date" title="' + $('<div/>').text(at).html() + '">'
							+ '<i class="fa fa-calendar-alt"></i>'
							+ '<span class="md-seq-chip-text">วันที่: ' + $('<div/>').text(fmtThaiDateTime(at)).html() + '</span>'
							+ '</span>';
					}
					var extraCls = '';
					if (typ === 'certifier_approved' || typ === 'certifier_recheck_approved') extraCls = ' md-seq-note-line-pass';
					else if (typ === 'certifier_rejected') extraCls = ' md-seq-note-line-suggest';
					if (typ === 'certifier_rejected') {
						var isLatestRejected = (soNow > 0 && soNow === latestRejectedSo);
						if (isLatestRejected && latestRejectedAt !== '') {
							isLatestRejected = (String(at || '').trim() === latestRejectedAt);
						}
						if (isLatestRejected) extraCls += ' md-seq-note-line-latest';
					}
					if ((typ === 'certifier_approved' || typ === 'certifier_recheck_approved') && !dtxt && !noteTxt) {
						return;
					}
					var isReplyLine = (typ === 'submitter_reply_saved' || typ === 'project_fix_confirmed' || typ === 'submitter_reply_kept');
					metaHtml += '<div class="md-seq-note-line ' + (isReplyLine ? 'md-seq-note-line-reply' : '') + extraCls + '">'
						+ '<div class="md-seq-reply-head">';
					if (label) {
						metaHtml += '<span class="md-seq-note-label" style="margin-right:0;"><i class="fa ' + icon + '"></i>' + $('<div/>').text(label).html() + '</span>';
					}
					metaHtml += dtxt + '</div>';
					if (noteTxt) metaHtml += $('<div/>').text(noteTxt).html();
					if (typ === 'submitter_reply_saved' && srCanEdit) {
						metaHtml += ' <button type="button" class="btn btn-link btn-sm p-0 ml-2" id="mdSubmitterReplyEditBtn" style="font-weight:900;">แก้ไข</button>';
					}
					metaHtml += '</div>';
				});
			} else {
				// fallback ของเก่า (กรณีไม่มี log)
				if (decidedAt || note) {
					metaHtml += '<div class="md-seq-meta">';
					if (decidedAt) {
						metaHtml += '<span class="md-seq-chip md-seq-chip-date" title="' + $('<div/>').text(decidedAt).html() + '">'
							+ '<i class="fa fa-calendar-alt"></i>'
							+ '<span class="md-seq-chip-text">วันที่: ' + $('<div/>').text(fmtThaiDateTime(decidedAt)).html() + '</span>'
							+ '</span>';
					}
					metaHtml += '</div>';
					if (note) {
						var noteCls = 'md-seq-note-line';
						if (st === 'rejected') noteCls += ' md-seq-note-line-suggest';
						metaHtml += '<div class="' + noteCls + '">'
							+ '<span class="md-seq-note-label"><i class="fa fa-comment-dots"></i>ข้อเสนอแนะ</span>'
							+ $('<div/>').text(note).html()
							+ '</div>';
					}
				}
				if (st === 'rejected' && srNote) {
					var dtxt2 = '';
					if (srUpdatedAt) {
						dtxt2 = '<span class="md-seq-chip md-seq-chip-date" title="' + $('<div/>').text(srUpdatedAt).html() + '">'
							+ '<i class="fa fa-calendar-alt"></i>'
							+ '<span class="md-seq-chip-text">วันที่: ' + $('<div/>').text(fmtThaiDateTime(srUpdatedAt)).html() + '</span>'
							+ '</span>';
					}
					metaHtml += '<div class="md-seq-note-line md-seq-note-line-reply">'
						+ '<div class="md-seq-reply-head">'
						+ '<span class="md-seq-note-label" style="margin-right:0;"><i class="fa fa-reply"></i>คำชี้แจง</span>' + dtxt2
						+ '</div>'
						+ $('<div/>').text(srNote).html();
					if (srCanEdit) {
						metaHtml += ' <button type="button" class="btn btn-link btn-sm p-0 ml-2" id="mdSubmitterReplyEditBtn" style="font-weight:900;">แก้ไข</button>';
					}
					metaHtml += '</div>';
				}
			}
			var posHtml = pos ? '<div class="md-seq-pos">' + $('<div/>').text(pos).html() + '</div>' : '<div class="md-seq-pos">&nbsp;</div>';
			var activeCls = (st === 'waiting_turn') ? 'md-seq-li-active' : '';
			var soTxt = String(c.sort_order || '');
			var soStep = soTxt ? ('<span class="md-seq-step">' + $('<div/>').text(soTxt).html() + '</span>') : '';
			var editRowBtn = '';
			if (c.can_edit_row) {
				var soNum = parseInt(c.sort_order || '0', 10) || 0;
				if (soNum > 0) {
					editRowBtn = ' <button type="button" class="btn btn-sm md-seq-edit-cert-row md-seq-edit-inline" data-sort-order="' + soNum + '" title="แก้ไขชื่อและตำแหน่งผู้พิจารณา"><i class="fa fa-pen"></i><span>แก้ไข</span></button>';
				}
			}
			var nameHtml = '<div class="md-seq-name"><span class="md-seq-name-text">' + $('<div/>').text(c.staff_name || '-').html() + '</span>'
				+ (editRowBtn || '') + '</div>';
			var rightHtml = tag;
			if (st === 'rejected' && srNote) {
				rightHtml = '<div class="md-seq-right-stack">' + tag
					+ '<span class="md-seq-pill md-seq-pill-edit md-seq-pill-edit-bottom" title="ส่งคำชี้แจงแล้ว — รอบรรจุโครงการ"><i class="fa fa-hourglass-half mr-1"></i>รอบรรจุ</span>'
					+ '</div>';
			}
			html += '<li class="' + activeCls + '">'
				+ soStep
				+ '<div class="md-seq-left">'
				+ nameHtml
				+ posHtml
				+ metaHtml
				+ '</div>'
				+ (rightHtml ? ('<div class="md-seq-right">' + rightHtml + '</div>') : '')
				+ '</li>';
		});
		try {
			logsAll.forEach(function(it){
				if (String(it.action_type || '') !== 'plan_admin_operate_returned') return;
				var at = String(it.created_at || '').trim();
				var noteTxt = String(it.note || '').trim();
				var who = String(it.actor_name || 'หน่วยแผนและงบประมาณ');
				var dtxt = '';
				if (at) {
					dtxt = '<span class="md-seq-chip md-seq-chip-date" title="' + $('<div/>').text(at).html() + '">'
						+ '<i class="fa fa-calendar-alt"></i>'
						+ '<span class="md-seq-chip-text">วันที่: ' + $('<div/>').text(fmtThaiDateTime(at)).html() + '</span>'
						+ '</span>';
				}
				html += '<li>'
					+ '<span class="md-seq-step">แผน</span>'
					+ '<div class="md-seq-left">'
					+ '<div class="md-seq-name"><span class="md-seq-name-text">' + $('<div/>').text(who).html() + '</span></div>'
					+ '<div class="md-seq-pos">หน่วยแผนและงบประมาณ</div>'
					+ '<div class="md-seq-note-line md-seq-note-line-suggest">'
					+ '<div class="md-seq-reply-head">'
					+ '<span class="md-seq-note-label" style="margin-right:0;"><i class="fa fa-undo"></i>ตีกลับเพื่อแก้ไข</span>' + dtxt
					+ '</div>'
					+ (noteTxt ? $('<div/>').text(noteTxt).html() : '')
					+ '</div></div>'
					+ '<div class="md-seq-right"><span class="md-seq-pill md-seq-pill-no" style="max-width:100%;white-space:normal;text-align:center;line-height:1.25;"><i class="fa fa-undo mr-1"></i>ตีกลับเพื่อแก้ไข</span></div>'
					+ '</li>';
			});
		} catch (_ePlanRet) {}
		$('#mdSeqList').html(html);

		// ไม่แสดง action log แยกกล่อง — จะแทรกต่อจากไทม์ไลน์รายลำดับแทน
		try { $('#mdActionLogWrap').hide(); $('#mdActionLogList').empty(); } catch(eLog) {}

		// ช่องชี้แจงของผู้ยื่นเสนอ (เฉพาะกรณีตีกลับ)
		try {
			if (resp && resp.request && String(resp.request.status || '') === 'rejected') {
				$('#mdSubmitterReply').val(srNote);
				$('#mdSubmitterReplyMsg').hide().text('').removeClass('alert-danger alert-success');
				if (!srCanEdit) {
					// ผู้ตรวจ/คนอื่น: ไม่ต้องแสดงกล่องกรอกคำชี้แจงซ้ำ (แสดงเฉพาะข้อความคำชี้แจงในไทม์ไลน์)
					$('#mdSubmitterReplyWrap').hide();
					$('#mdSubmitterReplySave').hide();
					$('#mdSubmitterReply').prop('readonly', true);
				} else {
					// ผู้ยื่นเสนอ: ถ้ายืนยันแล้ว (มีข้อความแล้ว) ให้ซ่อนกล่อง (จนกด "แก้ไข" ในไทม์ไลน์)
					if (srNote && !submitterReplyEditing) {
						$('#mdSubmitterReplyWrap').hide();
						$('#mdSubmitterReplySave').hide();
						$('#mdSubmitterReply').prop('readonly', false);
					} else {
						$('#mdSubmitterReplyWrap').show();
						$('#mdSubmitterReply').prop('readonly', false);
						$('#mdSubmitterReplySave').show();
					}
				}
			} else {
				$('#mdSubmitterReplyWrap').hide();
				$('#mdSubmitterReplySave').hide();
				$('#mdSubmitterReplyMsg').hide().text('').removeClass('alert-danger alert-success');
			}
		} catch(e) {
			$('#mdSubmitterReplyWrap').hide();
			$('#mdSubmitterReplySave').hide();
			$('#mdSubmitterReplyMsg').hide().text('').removeClass('alert-danger alert-success');
		}

		// ผ่านครบทุกลำดับแล้ว — ซ่อนส่วน "การตรวจสอบ" (ฟอร์มผ่าน/ตีกลับ) เหลือเพียงประวัติด้านบน
		var allSeqApproved = false;
		try {
			if (certs && certs.length) {
				allSeqApproved = true;
				certs.forEach(function(c){
					if (String(c.status || '') !== 'approved') {
						allSeqApproved = false;
					}
				});
			}
		} catch (_eAllAp0) {
			allSeqApproved = false;
		}

		// แสดงแผงการให้ความเห็น + ปุ่ม ผ่าน/ตีกลับ เมื่อถึงคิวของผู้ใช้
		// (ไม่ซ่อนด้วยเงื่อนไข pipeline; ถ้า pipeline ไม่พร้อม ฝั่ง PHP จะกันไว้แล้ว และเราจะแสดงคำเตือน)
		var can = !!pl.can_act;
		var mySort = parseInt(pl.my_sort_order || '0', 10);
		if (!isFinite(mySort)) mySort = 0;
		var isReviewerInProject = mySort > 0;
		var planRechain = (flowType === 'operate')
			? (resp && resp.plan_admin_operate_rechain ? resp.plan_admin_operate_rechain : null)
			: (flowType === 'change' ? (resp && resp.plan_admin_change_rechain ? resp.plan_admin_change_rechain : null) : null);
		window.__pmPlanOperateDecide = (flowType === 'operate' && planRechain && (planRechain.show || Number(planRechain.can_decide) === 1));
		var planReportReview = (flowType === 'report' && resp && resp.plan_report_review) ? resp.plan_report_review : null;
		window.__pmPlanReportDecide = !!(planReportReview && (planReportReview.show || Number(planReportReview.can_act) === 1));
		var shouldShowReviewBlock = !allSeqApproved && (can || (flowType === 'operate' && isReviewerInProject));
		if (window.__pmPlanOperateDecide) {
			shouldShowReviewBlock = true;
			can = true;
		}
		if (window.__pmPlanReportDecide) {
			shouldShowReviewBlock = true;
			can = true;
		}
		var myReviewerName = '';
		try {
			(certs || []).forEach(function(c){
				var so = parseInt(c && c.sort_order ? c.sort_order : '0', 10);
				if (so === mySort) {
					myReviewerName = String((c && c.staff_name) ? c.staff_name : '');
				}
			});
		} catch (_eFindMe) {}
		var reqId = '';
		try {
			if (resp.request && resp.request.id != null) reqId = String(resp.request.id);
		} catch(e){}
		if (!reqId) reqId = String($('#mdRequestId').val() || '');
		if (shouldShowReviewBlock) {
			$('#mdSeqActorBlock').show();
			$('#mdSeqCurrentName').text(String(myReviewerName || pl.current_turn_name || ''));
			$('#mdCertRequestId').val(reqId);
			$('#mdCertSortOrder').val(String(pl.my_sort_order || ''));
			$('#mdCertYearsSelect').val(String(resp.years_select || ''));
			$('#mdCertProjectGroup').val(String(resp.project_group || 'draft'));
			$('#mdCertNote').val('');
			$('#mdCertOp').val('');
			if (can) {
				$('#mdCertNote').prop('readonly', false);
				$('#mdCertBtnPass, #mdCertBtnBack').prop('disabled', false);
				$('#mdSeqAjaxMsg').hide().text('').removeClass('alert-danger alert-success');
			} else {
				$('#mdCertNote').prop('readonly', true);
				$('#mdCertBtnPass, #mdCertBtnBack').prop('disabled', true);
				$('#mdSeqAjaxMsg')
					.text('รอลำดับก่อนหน้าพิจารณาให้เสร็จก่อน จึงจะสามารถดำเนินการได้')
					.show()
					.removeClass('alert-success')
					.addClass('alert-danger');
			}
			if (window.__pmPlanOperateDecide) {
				$('#mdSeqCurrentName').text('หน่วยแผนและงบประมาณ');
				$('#mdSeqHint').html('<i class="fa fa-level-down text-secondary mr-1"></i> <strong>ผ่าน</strong> = เลือกผู้อนุมัติโครงการด้านล่าง แล้วส่งตามลำดับ &nbsp;|&nbsp; <strong>ตีกลับ</strong> = ส่งให้ผู้ยื่นแก้ไข');
			} else if (window.__pmPlanReportDecide) {
				$('#mdSeqCurrentName').text('หน่วยแผนและงบประมาณ');
				$('#mdSeqHint').html('<i class="fa fa-level-down text-secondary mr-1"></i> <strong>ผ่าน</strong> = ปิดโครงการ (B5) &nbsp;|&nbsp; <strong>ตีกลับ</strong> = ส่งให้ผู้ยื่นแก้ไขรายงานผล');
			} else {
				$('#mdSeqHint').html('<i class="fa fa-level-down text-secondary mr-1"></i> <strong>ผ่าน</strong> ระบบจะส่งคำขอไปยังผู้ตรวจสอบลำดับถัดไป');
			}
		} else {
			$('#mdSeqActorBlock').hide();
		}
		if (!shouldShowReviewBlock) {
			$('#mdSeqAjaxMsg').hide().text('').removeClass('alert-danger alert-success');
		}
		try {
			var $ab = $('#mdSeqActorBlock');
			if ($ab.length) {
				$ab.prev('hr.md-seq-hr').toggle(!!shouldShowReviewBlock);
			}
		} catch (_eHrActor) {}

		try { renderPlanAdminRechainPanel(resp, flowType); } catch (_ePrcPan) {}

		renderPlanUnitPanel(resp);
		try { pmSeqApplyStageFold(resp, flowType, allSeqApproved, seqHeadShort, seqHeadFull); } catch (_eFold) {}
	}

	function pmSeqApplyStageFold(resp, flowType, allSeqApproved, seqHeadShort, seqHeadFull){
		var pu = resp && resp.plan_unit_review ? resp.plan_unit_review : null;
		var puDec = pu ? String(pu.decision || '').trim() : '';
		var stageDone = false;
		if (flowType === 'include') {
			stageDone = !!allSeqApproved && (puDec === 'included' || puDec === 'rejected');
		} else if (flowType === 'report') {
			var prr = resp && resp.plan_report_review ? resp.plan_report_review : null;
			stageDone = !!allSeqApproved && !!(prr && Number(prr.closed) === 1);
		} else if (flowType === 'operate' || flowType === 'change') {
			stageDone = !!allSeqApproved;
		}
		var $head = $('#mdSeqHead');
		var $body = $('#mdSeqBody');
		var $foldBtn = $('#mdSeqCertFoldBtn');
		var $certWrap = $('#mdSeqCertWrap');
		$head.removeClass('md-seq-head-toggle is-collapsed').attr('data-foldable', '0');
		$('#mdSeqHeadChevron').hide();
		$body.show();
		$foldBtn.hide().removeClass('is-collapsed');
		$certWrap.show();
		$('#mdSeqHeadText').text(seqHeadFull || $('#mdSeqHeadText').text());
		if (stageDone) {
			$('#mdSeqHeadText').text(seqHeadShort);
			$head.addClass('md-seq-head-toggle is-collapsed').attr('data-foldable', '1');
			$('#mdSeqHeadChevron').show();
			$body.hide();
			return;
		}
		if (allSeqApproved) {
			$foldBtn.show().addClass('is-collapsed');
			$('#mdSeqCertFoldText').text(seqHeadShort);
			$certWrap.hide();
		}
	}

	function renderPlanUnitPanel(resp){
		var $wrap = $('#mdPlanUnitWrap');
		if (!$wrap.length) return;
		var pu = resp && resp.plan_unit_review ? resp.plan_unit_review : null;
		if (!pu || !pu.show) {
			$wrap.hide();
			return;
		}
		$wrap.show();
		try { $('#mdPlanUnitMsg').hide().text('').removeClass('alert alert-danger alert-success'); } catch(_epum0) {}
		try { $('#mdPlanUnitReplyWrap').hide(); $('#mdPlanUnitReplyMsg').hide().text('').removeClass('alert-danger alert-success'); } catch(_epur0) {}
		try { $('#mdPlanUnitReply').val(''); } catch(_epur1) {}
		try { $('#mdPlanUnitTimelineWrap').hide(); $('#mdPlanUnitTimeline').empty(); } catch(_eputl0) {}
		try { $('#mdPlanUnitHeadBadges').hide().empty(); } catch(_ephb0) {}
		var officer = String(pu.officer_name || '').trim();
		if (officer) {
			$('#mdPlanUnitOfficerLine').html(
				'<span class="md-plan-unit-officer-label">รับเรื่องและแจ้งผล :</span> '
				+ '<span class="md-plan-unit-officer-name">' + $('<div/>').text(officer).html() + '</span>'
			).show();
		} else {
			$('#mdPlanUnitOfficerLine').html(
				'<span class="md-plan-unit-officer-label">รับเรื่องและแจ้งผล :</span> '
				+ '<span class="md-plan-unit-officer-name text-muted">—</span>'
			).show();
		}
		var allOk = !!pu.all_certifiers_approved;
		var dec = String(pu.decision || '').trim();
		$('#mdPlanUnitStatusLine').toggle(allOk && !dec);

		// ไทม์ไลน์การตอบกลับของหน่วยแผน (ใช้ action_logs)
		try {
			var logsAll = resp && resp.action_logs ? resp.action_logs : [];
			var puLogs = [];
			logsAll.forEach(function(it){
				var typ = String(it.action_type || '');
				if (typ === 'plan_unit_included' || typ === 'plan_unit_returned' || typ === 'plan_unit_rejected' || typ === 'plan_unit_submitter_reply_saved' || typ === 'plan_unit_accepted') {
					puLogs.push(it);
				}
			});
			if (puLogs.length) {
				var th = '';
				puLogs.forEach(function(it){
					var typ = String(it.action_type || '');
					if (typ === 'plan_unit_included') {
						return;
					}
					var at = String(it.created_at || '').trim();
					var noteTxt = String(it.note || '').trim();
					var label = 'กิจกรรม';
					var icon = 'fa-info-circle';
					var extraCls = '';
					if (typ === 'plan_unit_accepted') { label = 'รับเรื่อง'; icon = 'fa-handshake'; extraCls = ''; }
					else if (typ === 'plan_unit_returned') { label = 'ตีกลับเพื่อแก้ไข'; icon = 'fa-undo'; extraCls = ' md-seq-note-line-suggest'; }
					else if (typ === 'plan_unit_rejected') { label = 'ไม่อนุมัติ'; icon = 'fa-ban'; extraCls = ' md-seq-note-line-suggest'; }
					else if (typ === 'plan_unit_submitter_reply_saved') { label = 'คำชี้แจง'; icon = 'fa-reply'; extraCls = ' md-seq-note-line-reply'; }
					var dtxt = '';
					if (at) {
						dtxt = '<span class="md-seq-chip md-seq-chip-date" title="' + $('<div/>').text(at).html() + '">'
							+ '<i class="fa fa-calendar-alt"></i>'
							+ '<span class="md-seq-chip-text">วันที่: ' + $('<div/>').text(fmtThaiDateTime(at)).html() + '</span>'
							+ '</span>';
					}
					th += '<div class="md-seq-note-line' + extraCls + '">'
						+ '<div class="md-seq-reply-head">'
						+ '<span class="md-seq-note-label" style="margin-right:0;"><i class="fa ' + icon + '"></i>' + $('<div/>').text(label).html() + '</span>' + dtxt
						+ '</div>';
					if (noteTxt) th += $('<div/>').text(noteTxt).html();
					th += '</div>';
				});
				$('#mdPlanUnitTimeline').html(th);
				$('#mdPlanUnitTimelineWrap').show();
			}
		} catch(_eputl1) {}

		if (dec) {
			$('#mdPlanUnitActBlock').hide();
			var label = dec === 'included' ? 'นำเข้าที่ประชุมแล้ว' : (dec === 'returned' ? 'ตีกลับเพื่อแก้ไข' : 'ไม่อนุมัติ');
			var icon = dec === 'included' ? 'fa-check-double' : (dec === 'returned' ? 'fa-undo' : 'fa-ban');
			var pillCls = dec === 'included' ? 'md-plan-unit-pill-ok' : (dec === 'returned' ? 'md-plan-unit-pill-warn' : 'md-plan-unit-pill-no');
			var cardCls = dec === 'included' ? 'md-plan-unit-result-ok' : (dec === 'returned' ? 'md-plan-unit-result-warn' : 'md-plan-unit-result-no');
			var note = String(pu.note || '');
			var at = pu.decided_at ? fmtThaiDateTime(pu.decided_at) : '-';
			var who = String(pu.decider_name || '-');
			// แสดงป้าย/วันที่/โดย ที่มุมขวาบนของหัวข้อ (ตัดของเดิมในกรอบด้านล่างออก)
			var hb = ''
				+ '<span class="md-plan-unit-pill ' + pillCls + '"><i class="fa ' + icon + '"></i>' + $('<div/>').text(label).html() + '</span>'
				+ '<span class="md-plan-unit-chip" title="' + $('<div/>').text(String(pu.decided_at || '')).html() + '"><i class="fa fa-calendar-alt"></i>วันที่: ' + $('<div/>').text(at).html() + '</span>'
				+ '<span class="md-plan-unit-chip"><i class="fa fa-user"></i>โดย ' + $('<div/>').text(who).html() + '</span>';
			$('#mdPlanUnitHeadBadges').html(hb).show();

			// ไม่แสดงข้อความ note ใต้หัวข้อแล้ว (ให้ดูจากไทม์ไลน์แทน)
			$('#mdPlanUnitResultInner').empty();
			$('#mdPlanUnitResult').hide();

			// ผู้ยื่นเสนอ: ถ้าหน่วยแผน "ตีกลับ" ให้แสดงช่องส่งคำชี้แจงกลับไปให้หน่วยแผนได้
			var canReply = !!pu.can_reply;
			if (dec === 'returned' && canReply) {
				var sr = pu.submitter_reply || {};
				var srNote = sr && sr.note ? String(sr.note) : '';
				$('#mdPlanUnitReply').val(srNote);
				$('#mdPlanUnitReplyWrap').show();
			}
		} else {
			$('#mdPlanUnitResult').hide();
			$('#mdPlanUnitResultInner').empty();
			try { $('#mdPlanUnitDecideActions').hide(); } catch(_epa0) {}
			if (allOk && !dec) {
				$('#mdPlanUnitActBlock').show();
				$('#mdPlanUnitMsg').hide().text('').removeClass('alert alert-danger alert-success');
				if (pu.can_act || pu.can_accept) {
					$('#mdPlanUnitDecideActions').show();
					$('#mdPlanBtnInclude').prop('disabled', false);
					try { $('#mdPlanUnitNote').val('').prop('readonly', false); } catch(_epn0) {}
				} else {
					$('#mdPlanUnitActBlock').hide();
				}
			} else {
				$('#mdPlanUnitActBlock').hide();
			}
		}
	}

	function showPlanUnitMsg(kind, text){
		var $m = $('#mdPlanUnitMsg');
		if (!$m.length) return;
		if (kind === 'ok') {
			$m.hide().text('').removeClass('alert alert-danger alert-success');
			return;
		}
		$m.attr('class', 'alert alert-danger mt-2').text(String(text || '')).css({ display: 'block' }).show();
	}
	function showPlanUnitReplyMsg(kind, text){
		var $m = $('#mdPlanUnitReplyMsg');
		if (!$m.length) return;
		var cls = (kind === 'ok') ? 'alert alert-success' : 'alert alert-danger';
		$m.attr('class', cls + ' mt-2').text(String(text || '')).css({ display: 'block' }).show();
	}

	function submitPlanUnitAction(action){
		action = String(action || '');
		var rid = parseInt($('#mdRequestId').val() || '0', 10);
		if (!rid) return;
		var note = String($('#mdPlanUnitNote').val() || '');
		var ys = String((lastDetailResp && lastDetailResp.years_select) ? lastDetailResp.years_select : '');
		if (!ys) ys = String(new URL(window.location.href).searchParams.get('years_select') || '');
		var pg = String((lastDetailResp && lastDetailResp.project_group) ? lastDetailResp.project_group : 'draft');
		if (!pg) pg = 'draft';
		$('#mdPlanBtnInclude').prop('disabled', true);
		showPlanUnitMsg('ok', 'กำลังบันทึก...');
		$.ajax({
			type: 'POST',
			url: '?page=project_approve',
			data: {
				ajax: '1',
				op: 'plan_unit_decision',
				request_id: String(rid),
				plan_action: action,
				plan_unit_note: note,
				years_select: ys,
				project_group: pg
			},
			dataType: 'text'
		}).done(function(raw){
			var r = null;
			try { r = JSON.parse(String(raw || '')); } catch(e) { r = null; }
			if (!r || !r.ok) {
				var msg = (r && r.msg) ? String(r.msg) : 'บันทึกไม่สำเร็จ';
				showPlanUnitMsg('err', msg);
				$('#mdPlanBtnInclude').prop('disabled', false);
				return;
			}
			showPlanUnitMsg('ok', (r.msg && String(r.msg).trim()) ? r.msg : 'บันทึกสำเร็จ');
			var refreshUrl = '?page=project_approve&op=get_request_detail';
			var refreshData = { request_id: rid };
			if (flowType === 'operate') {
				refreshUrl = '?page=project_approve&op=get_operate_project_detail';
				refreshData = {
					page: 'project_approve',
					op: 'get_operate_project_detail',
					project_id: parseInt($('#mdCertProjectId').val() || '0', 10),
					request_id: parseInt($('#mdOperateRequestId').val() || '0', 10)
				};
			}
			$.ajax({
				type: 'POST',
				url: refreshUrl,
				data: refreshData,
				dataType: 'text'
			}).done(function(raw2){
				var rr = null;
				try { rr = JSON.parse(String(raw2 || '')); } catch(e2) { rr = null; }
				if (rr && rr.ok) {
					renderSequentialPanel(rr);
				}
			});
			var redir = r.redirect ? String(r.redirect) : '';
			if (redir) {
				setTimeout(function(){ window.location.href = redir; }, 1400);
			} else {
				$('#mdPlanBtnInclude').prop('disabled', false);
			}
		}).fail(function(xhr){
			var t = '';
			try { t = String(xhr && xhr.responseText ? xhr.responseText : '').trim(); } catch(e){}
			if (t.length > 300) t = t.slice(0, 300) + '...';
			showPlanUnitMsg('err', t || 'บันทึกไม่สำเร็จ');
			$('#mdPlanBtnInclude').prop('disabled', false);
		});
	}

	function submitPlanUnitSubmitterReply(){
		var rid = parseInt($('#mdRequestId').val() || '0', 10);
		if (!rid) return;
		var note = String($('#mdPlanUnitReply').val() || '').trim();
		if (!note) {
			showPlanUnitReplyMsg('err', 'กรุณากรอกข้อความชี้แจง/ตอบกลับ');
			return;
		}
		openConfirmModal({
			mode: 'pass',
			title: 'ยืนยันการบันทึก',
			body: 'ยืนยันการส่งคำชี้แจงให้หน่วยแผนและงบประมาณพิจารณาอีกครั้งหรือไม่?',
			okText: 'บันทึก',
			onConfirm: function(){
				$('#mdPlanUnitReplySend').prop('disabled', true);
				showPlanUnitReplyMsg('ok', 'กำลังบันทึก...');
				$.ajax({
					type: 'POST',
					url: '?page=project_approve&op=save_plan_unit_submitter_reply',
					data: { ajax: '1', op: 'save_plan_unit_submitter_reply', request_id: rid, plan_unit_submitter_reply_note: note },
					dataType: 'text'
				}).done(function(raw){
					var r = null;
					try { r = JSON.parse(String(raw || '')); } catch(e) { r = null; }
					if (!r || !r.ok) {
						var msg = (r && r.msg) ? String(r.msg) : 'บันทึกไม่สำเร็จ';
						showPlanUnitReplyMsg('err', msg);
						$('#mdPlanUnitReplySend').prop('disabled', false);
						return;
					}
					showPlanUnitReplyMsg('ok', (r.msg ? String(r.msg) : 'บันทึกสำเร็จ'));
					// ให้ผู้ใช้เห็นข้อความสำเร็จก่อน แล้วค่อยรีเฟรชข้อมูล
					setTimeout(function(){
						$.ajax({
							type: 'POST',
							url: '?page=project_approve&op=get_request_detail',
							data: { request_id: rid },
							dataType: 'text'
						}).done(function(raw2){
							var rr = null;
							try { rr = JSON.parse(String(raw2 || '')); } catch(e2) { rr = null; }
							if (rr && rr.ok) {
								renderSequentialPanel(rr);
							}
						});
						$('#mdPlanUnitReplySend').prop('disabled', false);
					}, 650);
				}).fail(function(xhr){
					var t = '';
					try { t = String(xhr && xhr.responseText ? xhr.responseText : '').trim(); } catch(e){}
					if (t.length > 240) t = t.slice(0, 240) + '...';
					showPlanUnitReplyMsg('err', t || 'บันทึกไม่สำเร็จ');
					$('#mdPlanUnitReplySend').prop('disabled', false);
				});
			}
		});
	}

	$(document).on('click', '#mdPlanUnitReplySend', function(e){
		e.preventDefault();
		submitPlanUnitSubmitterReply();
		return false;
	});

	// modal confirm (สวยๆ)
	var confirmCb = null;
	var confirmMode = '';
	var confirmUsePlanNote = false;
	// export globals (backup) เพื่อให้ onclick ทำงานชัวร์
	function hideConfirmModalSafe(){
		var $m = $('#modalConfirmAction');
		if ($m.hasClass('pm-confirm-manual')) {
			hideConfirmFallback();
			return;
		}
		try {
			if (typeof $m.modal === 'function') $m.modal('hide'); else hideConfirmFallback();
		} catch(e){ hideConfirmFallback(); }
	}
	window.pmProjectApproveConfirmClose = function(){
		confirmCb = null;
		confirmMode = '';
		confirmUsePlanNote = false;
		hideConfirmModalSafe();
	};
	function collectIncludeReturnProjects(){
		var seen = {};
		var list = [];
		function add(p){
			if (!p && p !== 0) return;
			if (typeof p !== 'object') {
				p = { project_id: p };
			}
			var pid = parseInt(p.project_id || p.id || p.projectId || 0, 10);
			if (!pid || seen[pid]) return;
			seen[pid] = 1;
			list.push({
				project_id: pid,
				code: String(p.code || p.code_pre_project || ''),
				name: String(p.name || p.name_project || p.project_name || ('โครงการ #' + pid))
			});
		}
		var src = lastDetailResp || window.__pmLastDetailResp || {};
		if (Array.isArray(src.projects_for_return)) {
			src.projects_for_return.forEach(add);
		}
		if (Array.isArray(src.summary)) {
			src.summary.forEach(function(row){
				var items = (row && row.include_project_items) ? row.include_project_items : [];
				if (Array.isArray(items)) items.forEach(add);
			});
		}
		if (Array.isArray(src.project_ids)) {
			src.project_ids.forEach(function(id){ add({ project_id: id }); });
		}
		var hiddenIds = String($('#mdIncludeProjectIds').val() || '').trim();
		if (hiddenIds) {
			hiddenIds.split(/[,\s]+/).forEach(function(id){ add({ project_id: id }); });
		}
		try {
			$('#mdSummaryBody a[href*="project_id="], #mdSummaryBody [data-project-id]').each(function(){
				var href = String($(this).attr('href') || '');
				var m = href.match(/[?&]project_id=(\d+)/);
				var fromData = $(this).attr('data-project-id');
				add({
					project_id: (m && m[1]) ? m[1] : fromData,
					name: String($(this).text() || '').trim()
				});
			});
		} catch (_eDom) {}
		var certPid = parseInt($('#mdCertProjectId').val() || '0', 10);
		if (certPid > 0) {
			add({ project_id: certPid });
		}
		return list;
	}
	function rememberIncludeProjectIds(resp){
		var storeIds = [];
		function pushStoreId(v){
			var n = parseInt(v, 10);
			if (n > 0 && storeIds.indexOf(n) === -1) storeIds.push(n);
		}
		resp = resp || lastDetailResp || window.__pmLastDetailResp || {};
		if (Array.isArray(resp.project_ids)) resp.project_ids.forEach(pushStoreId);
		if (Array.isArray(resp.projects_for_return)) {
			resp.projects_for_return.forEach(function(p){
				if (p && typeof p === 'object') pushStoreId(p.project_id || p.id);
				else pushStoreId(p);
			});
		}
		if (Array.isArray(resp.summary)) {
			resp.summary.forEach(function(row){
				var items = (row && row.include_project_items) ? row.include_project_items : [];
				if (Array.isArray(items)) {
					items.forEach(function(it){ pushStoreId(it && (it.project_id || it.id)); });
				}
			});
		}
		collectIncludeReturnProjects().forEach(function(p){ pushStoreId(p.project_id); });
		if (storeIds.length) {
			$('#mdIncludeProjectIds').val(storeIds.join(','));
		}
		return storeIds;
	}
	function buildIncludeReturnPickerHtml(){
		try { rememberIncludeProjectIds(lastDetailResp); } catch (_eRemPick) {}
		var plist = collectIncludeReturnProjects();
		var sharedNote = String($('#mdCertNote').val() || '').trim();
		var html = '<div class="cf-return-wrap text-left mt-2">';
		html += '<div class="font-weight-bold mb-2">ตีกลับโครงการ</div>';
		if (!plist.length) {
			html += '<div class="text-muted text-90">จะตีกลับโครงการทั้งหมดในคำขอนี้</div></div>';
			return html;
		}
		html += '<div id="cfReturnProjectList" class="cf-return-table-wrap">';
		html += '<table class="table table-bordered table-sm mb-0 cf-return-table"><thead><tr>';
		html += '<th>โครงการ</th><th>ข้อเสนอแนะ/ข้อชี้แจง</th><th class="text-center" style="width:108px;">เลือกตีกลับ</th>';
		html += '</tr></thead><tbody>';
		plist.forEach(function(p, idx){
			var pid = parseInt(p.project_id || 0, 10);
			if (!pid) return;
			var label = (idx + 1) + '. ' + String(p.name || ('โครงการ #' + pid));
			html += '<tr data-project-id="' + pid + '">';
			html += '<td class="text-left">' + $('<div/>').text(label).html() + '</td>';
			html += '<td><textarea class="form-control form-control-sm cf-return-note" data-project-id="' + pid + '" rows="2" placeholder="ระบุเหตุผลตีกลับโครงการนี้">' + $('<div/>').text(sharedNote).html() + '</textarea></td>';
			html += '<td class="text-center"><input type="checkbox" class="cf-return-project" value="' + pid + '" checked></td>';
			html += '</tr>';
		});
		html += '</tbody></table></div>';
		html += '<div class="text-muted text-85 mt-1">เลือกโครงการที่ต้องแก้ไขอย่างน้อย 1 รายการ และกรอกข้อเสนอแนะของโครงการที่เลือก</div></div>';
		return html;
	}
	function collectIncludeReturnPicks(){
		var picked = [];
		var notes = {};
		var missingNote = false;
		if (!$('#cfReturnProjectList').length) {
			return { picked: picked, notes: notes, missingNote: false };
		}
		$('#cfReturnProjectList .cf-return-project:checked').each(function(){
			var pid = parseInt($(this).val() || '0', 10);
			if (pid <= 0) return;
			var rowNote = String($(this).closest('tr').find('.cf-return-note').val() || '').trim();
			if (!rowNote) rowNote = String($('#mdCertNote').val() || '').trim();
			if (!rowNote) missingNote = true;
			picked.push(pid);
			notes[pid] = rowNote;
		});
		return { picked: picked, notes: notes, missingNote: missingNote };
	}

	function runConfirmOk(){
		// guard: ตีกลับต้องมีข้อความ / เลือกโครงการ
		if (confirmMode === 'back') {
			var flowType = String($('#mdCertFlowType').val() || 'include');
			if (flowType === 'include' && ($('#cfReturnProjectList').length || !confirmUsePlanNote)) {
				var pack = collectIncludeReturnPicks();
				if ($('#cfReturnProjectList').length) {
					if (!pack.picked.length) {
						showAjaxMsg('err', 'กรุณาเลือกโครงการที่ต้องการตีกลับอย่างน้อย 1 รายการ');
						$('#cfBody').find('.cf-guard-msg').remove();
						$('#cfBody').append('<div class="cf-guard-msg text-danger mt-2">กรุณาเลือกโครงการที่ต้องการตีกลับอย่างน้อย 1 รายการ</div>');
						return;
					}
					if (pack.missingNote) {
						showAjaxMsg('err', 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
						$('#cfBody').find('.cf-guard-msg').remove();
						$('#cfBody').append('<div class="cf-guard-msg text-danger mt-2">กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงของโครงการที่เลือกก่อนตีกลับ</div>');
						return;
					}
					window.__pmReturnProjectIds = pack.picked;
					window.__pmReturnProjectNotes = pack.notes;
					if (!String($('#mdCertNote').val() || '').trim()) {
						var firstNote = '';
						pack.picked.forEach(function(id){
							if (!firstNote && pack.notes[id]) firstNote = pack.notes[id];
						});
						if (firstNote) $('#mdCertNote').val(firstNote);
					}
				} else {
					var autoIds = [];
					var autoNotes = {};
					var sharedNoteOk = String($('#mdCertNote').val() || '').trim();
					collectIncludeReturnProjects().forEach(function(p){
						var pid = parseInt(p.project_id || 0, 10);
						if (pid > 0 && autoIds.indexOf(pid) === -1) {
							autoIds.push(pid);
							autoNotes[pid] = sharedNoteOk;
						}
					});
					window.__pmReturnProjectIds = autoIds;
					window.__pmReturnProjectNotes = autoNotes;
				}
			}
			var n = confirmUsePlanNote
				? String($('#mdPlanUnitNote').val() || '').trim()
				: String($('#mdCertNote').val() || '').trim();
			if (!n) {
				showAjaxMsg('err', 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
				$('#cfBody').find('.cf-guard-msg').remove();
				$('#cfBody').append('<div class="cf-guard-msg text-danger mt-2">กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ</div>');
				return;
			}
		}
		hideConfirmModalSafe();
		var cb = confirmCb;
		confirmCb = null;
		if (typeof cb === 'function') cb();
	}
	window.pmProjectApproveConfirmOk = function(){ runConfirmOk(); };
	window.pmProjectApproveConfirm = function(mode){
		if (window.__pmConfirmBusy) return;
		window.__pmConfirmBusy = true;
		setTimeout(function(){ window.__pmConfirmBusy = false; }, 400);
		try {
			mode = String(mode || '');
			if (window.__pmPlanOperateDecide) {
				if (mode === 'back') {
					openConfirmModal({
						mode: 'back',
						title: 'ยืนยันการตีกลับ',
						body: 'ยืนยันตีกลับให้ผู้ยื่นแก้ไขก่อนเลือกผู้อนุมัติหรือไม่?',
						okText: 'ตีกลับเพื่อแก้ไข',
						onConfirm: function(){ submitPlanOperateReturn(); }
					});
					return;
				}
				openConfirmModal({
					mode: 'pass',
					title: 'ยืนยันผ่านและเลือกผู้อนุมัติ',
					body: 'ระบบจะส่งเรื่องให้ผู้อนุมัติตามลำดับที่เลือกด้านล่าง',
					okText: 'ผ่าน',
					onConfirm: function(){ $('#mdPaRechainSubmit').trigger('click'); }
				});
				return;
			}
			if (mode === 'back') {
				var flowType = String($('#mdCertFlowType').val() || 'include');
				var pickHtml = '';
				if (flowType === 'include') {
					pickHtml = buildIncludeReturnPickerHtml();
				}
				openConfirmModal({
					mode: 'back',
					title: 'ยืนยันการตีกลับ',
					body: 'เลือกโครงการที่ต้องการตีกลับ และกรอกข้อเสนอแนะของโครงการที่เลือก',
					okText: 'ตีกลับโครงการ',
					extraHtml: pickHtml,
					onConfirm: function(){ submitCertAction('certify_reject'); }
				});
				return;
			}
			openConfirmModal({
				mode: 'pass',
				title: 'ยืนยันการผ่าน',
				body: 'ยืนยันการอนุมัติคำขอนี้หรือไม่?',
				okText: 'ผ่าน',
				onConfirm: function(){ submitCertAction('certify_approve'); }
			});
		} catch (e) {
			var m = String(e && e.message ? e.message : e);
			try { showAjaxMsg('err', 'ตรวจสอบปุ่มตีกลับไม่สำเร็จ: ' + m); } catch(ex){ try { window.alert(m); } catch(e2){} }
		}
	};
	function showConfirmFallback(){
		var $m = $('#modalConfirmAction');
		if (!$m.length) return;
		$m.addClass('show pm-confirm-manual').css({ display: 'block' }).attr('aria-modal', 'true').removeAttr('aria-hidden');
		if (!$('.modal-backdrop.confirm-backdrop').length) {
			$('body').append('<div class="modal-backdrop fade show confirm-backdrop"></div>');
		}
		$('body').addClass('modal-open');
		try { $m[0].scrollIntoView({ block: 'center' }); } catch(e){}
	}
	function hideConfirmFallback(){
		var $m = $('#modalConfirmAction');
		if (!$m.length) return;
		$m.removeClass('show pm-confirm-manual cf-return-wide').css({ display: 'none' }).removeAttr('aria-modal').attr('aria-hidden', 'true');
		$('.modal-backdrop.confirm-backdrop').remove();
		// ถ้าไม่มี modal รายละเอียด/อื่นค้างอยู่ ค่อยถอด modal-open
		var detailOpen = $('#modalRequestDetail').hasClass('show') || $('#modalRequestDetail').hasClass('in');
		if (!detailOpen && !$('.modal.show').length) $('body').removeClass('modal-open');
	}
	function isDetailModalOpen(){
		var $d = $('#modalRequestDetail');
		if (!$d.length) return false;
		return $d.hasClass('show') || $d.hasClass('in');
	}
	function hideDetailModalFallback(){
		var $d = $('#modalRequestDetail');
		if (!$d.length) return;
		$d.removeClass('show in pm-detail-manual').css({ display: 'none' }).removeAttr('aria-modal').attr('aria-hidden', 'true');
		$('.modal-backdrop').not('.confirm-backdrop').not('.ecr-cert-backdrop').remove();
		var confirmOpen = $('#modalConfirmAction').hasClass('show') || $('#modalConfirmAction').hasClass('pm-confirm-manual');
		var otherOpen = $('.modal.show, .modal.in').filter(function(){
			var id = String(this.id || '');
			return id && id !== 'modalRequestDetail';
		}).length > 0;
		if (!confirmOpen && !otherOpen) {
			$('body').removeClass('modal-open');
		}
	}
	function hideDetailModalSafe(){
		try {
			var $cf = $('#modalConfirmAction');
			if ($cf.hasClass('show') || $cf.hasClass('pm-confirm-manual')) {
				hideConfirmModalSafe();
			}
		} catch (eCf) {}
		var $d = $('#modalRequestDetail');
		try {
			if ($d.length && typeof $d.modal === 'function') {
				$d.modal('hide');
			}
		} catch (eHide) {}
		hideDetailModalFallback();
		setTimeout(hideDetailModalFallback, 120);
	}
	window.pmProjectApproveDetailClose = hideDetailModalSafe;

	function openConfirmModal(opts){
		opts = opts || {};
		confirmUsePlanNote = !!opts.usePlanNote;
		confirmCb = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
		var mode = String(opts.mode || 'pass'); // pass|back
		confirmMode = mode;
		$('#cfTitle').text(String(opts.title || 'ยืนยันการทำรายการ'));
		var bodyText = String(opts.body || '-');
		var extraHtml = String(opts.extraHtml || '');
		if (extraHtml) {
			$('#cfBody').html($('<div/>').text(bodyText).html() + extraHtml);
		} else {
			$('#cfBody').text(bodyText);
		}
		$('#modalConfirmAction').toggleClass('cf-return-wide', extraHtml.indexOf('cfReturnProjectList') !== -1);
		var $ic = $('#cfIcon');
		var $ok = $('#cfOkBtn');
		$ic.removeClass('cf-ic-pass cf-ic-back');
		$ok.removeClass('cf-ok-pass cf-ok-back');
		if (mode === 'back') {
			$ic.addClass('cf-ic-back').html('<i class="fa fa-undo"></i>');
			$ok.addClass('cf-ok-back').text(String(opts.okText || 'ตีกลับ'));
		} else {
			$ic.addClass('cf-ic-pass').html('<i class="fa fa-check"></i>');
			$ok.addClass('cf-ok-pass').text(String(opts.okText || 'ผ่าน'));
		}
		try {
			var $m = $('#modalConfirmAction');
			$m.appendTo('body');
			$('.modal-backdrop.confirm-backdrop').remove();

			// เปิด modal รายละเอียดอยู่: ห้ามใช้ jQuery .modal() ซ้ำ (โปรเจกต์โหลด Bootstrap 4+5 คู่กัน มักพังเงียบ ๆ)
			if (isDetailModalOpen()) {
				showConfirmFallback();
			} else if (typeof $m.modal === 'function') {
				try {
					$m.modal({ backdrop: true, keyboard: true, show: true });
				} catch (err) {
					try { showAjaxMsg('err', 'ไม่สามารถเปิดยืนยันได้ (Bootstrap modal): ' + String(err && err.message ? err.message : err)); } catch(ex){}
					showConfirmFallback();
				}
			} else {
				showConfirmFallback();
			}
		} catch (e) {
			try { showAjaxMsg('err', 'ไม่สามารถเปิดยืนยันได้: ' + String(e && e.message ? e.message : e)); } catch(ex){}
			showConfirmFallback();
		}

		// ถ้าเป็นตีกลับ: อย่าทับตารางเลือกโครงการ — ตรวจตอนกดยืนยัน
		setTimeout(function(){
			if (confirmMode === 'back' && $('#cfReturnProjectList').length) {
				$('#cfOkBtn').prop('disabled', false);
			} else if (confirmMode === 'back') {
				var n = confirmUsePlanNote
					? String($('#mdPlanUnitNote').val() || '').trim()
					: String($('#mdCertNote').val() || '').trim();
				$('#cfOkBtn').prop('disabled', !n);
			} else {
				$('#cfOkBtn').prop('disabled', false);
			}
		}, 0);
	}
	$(document).on('click', '#cfOkBtn', function(e){
		e.preventDefault();
		runConfirmOk();
	});

	$(document).on('click', '#mdSubmitterReplyEditBtn', function(e){
		e.preventDefault();
		submitterReplyEditing = true;
		if (lastDetailResp) {
			renderSequentialPanel(lastDetailResp);
		} else {
			$('#mdSubmitterReplyWrap').show();
			$('#mdSubmitterReplySave').show();
		}
		try { $('#mdSubmitterReply')[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch(ex){}
		$('#mdSubmitterReply').focus();
		return false;
	});
	// ปิดให้ชัวร์ (กันเคส data-dismiss ไม่ทำงานเมื่อซ้อน modal)
	$(document).on('click', '#cfCloseX, #cfCancelBtn, #modalConfirmAction [data-dismiss="modal"]', function(e){
		e.preventDefault();
		e.stopPropagation();
		confirmCb = null;
		confirmMode = '';
		hideConfirmModalSafe();
		return false;
	});
	// ปิด modal รายละเอียดคำขอ (กากบาท / ปุ่มปิด) — กัน Bootstrap 4+5 คู่กันแล้ว data-dismiss ไม่ทำงาน
	$(document).on('click', '#mdRequestDetailCloseX, #mdRequestDetailCloseBtn, #modalRequestDetail .modal-header .close, #modalRequestDetail .modal-footer [data-dismiss="modal"]', function(e){
		e.preventDefault();
		e.stopPropagation();
		hideDetailModalSafe();
		return false;
	});
	$('#modalConfirmAction').on('hidden.bs.modal', function(){
		confirmCb = null;
		confirmMode = '';
		confirmUsePlanNote = false;
		$('.modal-backdrop.confirm-backdrop').remove();
		$('#modalConfirmAction').removeClass('pm-confirm-manual');
	});

	// อัปเดตสถานะปุ่มยืนยันของ confirm เมื่อพิมพ์ข้อเสนอแนะ
	$(document).on('input', '#mdCertNote', function(){
		if (confirmMode !== 'back' || confirmUsePlanNote) return;
		if ($('#cfReturnProjectList').length) {
			$('#cfOkBtn').prop('disabled', false);
			return;
		}
		var n = String($('#mdCertNote').val() || '').trim();
		$('#cfOkBtn').prop('disabled', !n);
	});
	$(document).on('input', '#mdPlanUnitNote', function(){
		if (confirmMode !== 'back' || !confirmUsePlanNote) return;
		var n = String($('#mdPlanUnitNote').val() || '').trim();
		$('#cfOkBtn').prop('disabled', !n);
	});

	$(document).on('click', '#mdPlanBtnAccept', function(){
		var rid = parseInt($('#mdRequestId').val() || '0', 10);
		if (!rid) return;
		var $btn = $(this).prop('disabled', true);
		showPlanUnitMsg('ok', 'กำลังรับเรื่อง...');
		$.ajax({
			type: 'POST',
			url: '?page=project_approve',
			data: { ajax: '1', op: 'plan_unit_accept', request_id: String(rid) },
			dataType: 'text'
		}).done(function(raw){
			var r = null;
			try { r = JSON.parse(String(raw || '')); } catch(e) { r = null; }
			if (!r || !r.ok) {
				showPlanUnitMsg('err', (r && r.msg) ? String(r.msg) : 'รับเรื่องไม่สำเร็จ');
				$btn.prop('disabled', false);
				return;
			}
			showPlanUnitMsg('ok', (r.msg && String(r.msg).trim()) ? r.msg : 'รับเรื่องเรียบร้อย');
			openIncludeRequestModal(rid);
		}).fail(function(){
			showPlanUnitMsg('err', 'รับเรื่องไม่สำเร็จ');
			$btn.prop('disabled', false);
		});
	});
	$(document).on('click', '#mdPlanBtnInclude', function(){
		openConfirmModal({
			mode: 'pass',
			title: 'ยืนยันนำเข้าที่ประชุม',
			body: 'ยืนยันการขอบรรจุโครงการสำหรับคำขอนี้หรือไม่?',
			okText: 'นำเข้าที่ประชุม',
			onConfirm: function(){ submitPlanUnitAction('include'); }
		});
	});
	$(document).on('click', '#mdPlanBtnReturn', function(){
		var n = String($('#mdPlanUnitNote').val() || '').trim();
		if (!n) {
			showPlanUnitMsg('err', 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
			return;
		}
		openConfirmModal({
			mode: 'back',
			usePlanNote: true,
			title: 'ยืนยันการตีกลับ',
			body: 'ยืนยันการตีกลับเพื่อแก้ไขคำขอนี้หรือไม่?',
			okText: 'ตีกลับเพื่อแก้ไข',
			onConfirm: function(){ submitPlanUnitAction('return'); }
		});
	});
	$(document).on('click', '#mdPlanBtnReject', function(){
		var n = String($('#mdPlanUnitNote').val() || '').trim();
		if (!n) {
			showPlanUnitMsg('err', 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนไม่อนุมัติ');
			return;
		}
		openConfirmModal({
			mode: 'back',
			usePlanNote: true,
			title: 'ยืนยันการไม่อนุมัติ',
			body: 'ยืนยันการไม่อนุมัติคำขอนี้หรือไม่?',
			okText: 'ไม่อนุมัติ',
			onConfirm: function(){ submitPlanUnitAction('reject'); }
		});
	});

	$(document).on('click', '#mdCertBtnPass', function(e){
		e.preventDefault();
		if (window.__pmPlanOperateDecide) {
			openConfirmModal({
				mode: 'pass',
				title: 'ยืนยันผ่านและเลือกผู้อนุมัติ',
				body: 'ระบบจะส่งเรื่องให้ผู้อนุมัติตามลำดับที่เลือกด้านล่าง',
				okText: 'ผ่าน',
				onConfirm: function(){ $('#mdPaRechainSubmit').trigger('click'); }
			});
			return;
		}
		openConfirmModal({
			mode: 'pass',
			title: 'ยืนยันการผ่าน',
			body: 'ยืนยันการอนุมัติคำขอนี้หรือไม่?',
			okText: 'ผ่าน',
			onConfirm: function(){
				submitCertAction('certify_approve');
			}
		});
	});
	$(document).on('click', '#mdCertBtnBack', function(e){
		e.preventDefault();
		e.stopPropagation();
		if (typeof window.pmProjectApproveConfirm === 'function') {
			window.pmProjectApproveConfirm('back');
			return false;
		}
		if (window.__pmPlanOperateDecide) {
			openConfirmModal({
				mode: 'back',
				title: 'ยืนยันการตีกลับ',
				body: 'ยืนยันตีกลับให้ผู้ยื่นแก้ไขก่อนเลือกผู้อนุมัติหรือไม่?',
				okText: 'ตีกลับเพื่อแก้ไข',
				onConfirm: function(){ submitPlanOperateReturn(); }
			});
			return false;
		}
		var pickHtml = '';
		if (String($('#mdCertFlowType').val() || 'include') === 'include') {
			pickHtml = buildIncludeReturnPickerHtml();
		}
		openConfirmModal({
			mode: 'back',
			title: 'ยืนยันการตีกลับ',
			body: 'เลือกโครงการที่ต้องการตีกลับ และกรอกข้อเสนอแนะของโครงการที่เลือก',
			okText: 'ตีกลับโครงการ',
			extraHtml: pickHtml,
			onConfirm: function(){
				submitCertAction('certify_reject');
			}
		});
		return false;
	});

	function showAjaxMsg(kind, text){
		var $m = $('#mdSeqAjaxMsg');
		var t = String(text || '');
		if (!$m.length) {
			try { window.alert(t); } catch(e){}
			return;
		}
		var cls = (kind === 'ok')
			? 'alert alert-success py-2 mb-0'
			: 'alert alert-danger py-2 mb-0';
		$m.removeClass('d-none').attr('class', cls).text(t)
			.css({ display: 'block', visibility: 'visible', opacity: '1' });
		try { $m[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch(e){}
	}

	function submitPlanOperateReturn(){
		var noteNow = String($('#mdCertNote').val() || '').trim();
		if (!noteNow) {
			showAjaxMsg('err', 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
			return;
		}
		var pid = String($('#mdPaRechainProjectId').val() || $('#mdCertProjectId').val() || '').trim();
		if (!pid) {
			showAjaxMsg('err', 'ไม่พบรหัสโครงการ');
			return;
		}
		$('#mdCertBtnPass, #mdCertBtnBack').prop('disabled', true);
		showAjaxMsg('ok', 'กำลังบันทึก...');
		$.ajax({
			url: '?page=project_manage&op=plan_admin_operate_return_ajax',
			type: 'POST',
			dataType: 'json',
			data: {
				page: 'project_manage',
				op: 'plan_admin_operate_return_ajax',
				project_id: pid,
				certifier_note: noteNow
			}
		}).done(function(res){
			if (res && Number(res.ok) === 1) {
				showAjaxMsg('ok', String(res.msg || 'ตีกลับเรียบร้อย'));
				if (typeof Swal !== 'undefined') {
					Swal.fire({ icon: 'success', title: String(res.msg || 'ตีกลับเรียบร้อย'), timer: 1400, showConfirmButton: false })
						.then(function(){ window.location.reload(); });
				} else {
					window.location.reload();
				}
				return;
			}
			showAjaxMsg('err', (res && res.msg) ? String(res.msg) : 'บันทึกไม่สำเร็จ');
			$('#mdCertBtnPass, #mdCertBtnBack').prop('disabled', false);
		}).fail(function(){
			showAjaxMsg('err', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
			$('#mdCertBtnPass, #mdCertBtnBack').prop('disabled', false);
		});
	}

	function submitCertAction(op){
		op = String(op || '');
		if (!op) return;
		var flowType = String($('#mdCertFlowType').val() || 'include');
		if (flowType === 'operate') {
			op = (op === 'certify_reject') ? 'certify_operate_reject' : 'certify_operate_approve';
		} else if (flowType === 'report') {
			if (window.__pmPlanReportDecide) {
				op = (op === 'certify_reject') ? 'plan_admin_report_reject' : 'plan_admin_report_approve';
			} else {
				op = (op === 'certify_reject') ? 'certify_report_reject' : 'certify_report_approve';
			}
		} else if (flowType === 'change') {
			op = (op === 'certify_reject') ? 'certify_change_reject' : 'certify_change_approve';
		}
		// ตีกลับต้องมีข้อความ (ช่องรวม หรือช่องรายโครงการ)
		var noteNow = String($('#mdCertNote').val() || '').trim();
		if ((op === 'certify_reject' || op === 'certify_operate_reject' || op === 'certify_report_reject' || op === 'certify_change_reject' || op === 'plan_admin_report_reject') && !noteNow) {
			if (op === 'certify_reject' && flowType === 'include') {
				var notesMapEarly = (window.__pmReturnProjectNotes && typeof window.__pmReturnProjectNotes === 'object')
					? window.__pmReturnProjectNotes
					: {};
				Object.keys(notesMapEarly).forEach(function(k){
					if (!noteNow && String(notesMapEarly[k] || '').trim()) noteNow = String(notesMapEarly[k]).trim();
				});
			}
			if (!noteNow) {
				showAjaxMsg('err', 'กรุณากรอกข้อเสนอแนะ/ข้อชี้แจงก่อนตีกลับ');
				return;
			}
			$('#mdCertNote').val(noteNow);
		}
		$('#mdCertOp').val(op);
		$('#mdCertBtnPass, #mdCertBtnBack').prop('disabled', true);
		showAjaxMsg('ok', 'กำลังบันทึก...');

		var payload = {
			ajax: '1',
			op: op,
			request_id: String($('#mdCertRequestId').val() || ''),
			project_id: String($('#mdCertProjectId').val() || ''),
			include_request_id: String($('#mdOperateRequestId').val() || ''),
			sort_order: String($('#mdCertSortOrder').val() || ''),
			years_select: String($('#mdCertYearsSelect').val() || ''),
			project_group: String($('#mdCertProjectGroup').val() || 'draft'),
			flow_type: flowType,
			certifier_note: String($('#mdCertNote').val() || ''),
			change_request_id: String($('#mdCertRequestId').val() || '')
		};
		if ((op === 'certify_reject') && flowType === 'include') {
			var ids = Array.isArray(window.__pmReturnProjectIds) ? window.__pmReturnProjectIds : [];
			var notesMap = (window.__pmReturnProjectNotes && typeof window.__pmReturnProjectNotes === 'object')
				? window.__pmReturnProjectNotes
				: {};
			if (!ids.length && $('#cfReturnProjectList').length) {
				var pack = collectIncludeReturnPicks();
				ids = pack.picked;
				notesMap = pack.notes;
			}
			if (!ids.length) {
				collectIncludeReturnProjects().forEach(function(p){
					var pid = parseInt(p.project_id || 0, 10);
					if (pid > 0 && ids.indexOf(pid) === -1) ids.push(pid);
				});
				var sharedNote = String($('#mdCertNote').val() || '').trim();
				ids.forEach(function(pid){
					if (!notesMap[pid]) notesMap[pid] = sharedNote;
				});
			}
			payload['return_project_ids'] = ids;
			Object.keys(notesMap).forEach(function(k){
				payload['return_project_notes[' + k + ']'] = String(notesMap[k] || '');
			});
			if (!ids.length) {
				payload['return_all'] = '1';
			}
			if (!String(payload.certifier_note || '').trim()) {
				var firstNote = '';
				ids.forEach(function(id){
					if (!firstNote && notesMap[id]) firstNote = String(notesMap[id] || '');
				});
				if (firstNote) payload.certifier_note = firstNote;
			}
		}

		$.ajax({
			type: 'POST',
			url: '?page=project_approve',
			data: payload,
			dataType: 'text'
		}).done(function(resp){
			var r = null;
			try { r = JSON.parse(String(resp || '')); } catch(e) { r = null; }
			if (!r || !r.ok) {
				var msg = (r && r.msg) ? r.msg : '';
				if (!msg) {
					msg = String(resp || '').trim();
					if (msg.length > 600) msg = msg.slice(0, 600) + '...';
				}
				showAjaxMsg('err', msg || 'บันทึกไม่สำเร็จ');
				$('#mdCertBtnPass, #mdCertBtnBack').prop('disabled', false);
				return;
			}
			showAjaxMsg('ok', (r.msg && String(r.msg).trim()) ? r.msg : 'บันทึกสำเร็จ');

			// รีเฟรชข้อมูลลำดับใน modal ทันที เพื่อยืนยันว่า "ลงฐานข้อมูลแล้ว"
			if (flowType === 'operate') {
				var pid = parseInt(payload.project_id || '0', 10);
				if (pid) {
					$.ajax({
						type: 'POST',
						url: '?page=project_approve&op=get_operate_project_detail',
						data: {
							page: 'project_approve',
							op: 'get_operate_project_detail',
							project_id: pid,
							request_id: parseInt($('#mdOperateRequestId').val() || '0', 10)
						},
						dataType: 'text'
					}).done(function(raw){
						var rr = null;
						try { rr = JSON.parse(String(raw || '')); } catch(e) { rr = null; }
						if (rr && rr.ok) {
							renderSummaryRows(rr.summary || [], rr);
							renderSequentialPanel(rr);
						}
					});
				}
			} else if (flowType === 'report') {
				var pidRep = parseInt(payload.project_id || '0', 10);
				if (pidRep) {
					$.ajax({
						type: 'POST',
						url: '?page=project_approve&op=get_report_project_detail',
						data: {
							page: 'project_approve',
							op: 'get_report_project_detail',
							project_id: pidRep
						},
						dataType: 'text'
					}).done(function(raw){
						var rr = null;
						try { rr = JSON.parse(String(raw || '')); } catch(e) { rr = null; }
						if (rr && rr.ok) {
							renderSummaryRows(rr.summary || [], rr);
							renderSequentialPanel(rr);
						}
					});
				}
			} else if (flowType === 'change') {
				var ridChg = parseInt(payload.change_request_id || payload.request_id || '0', 10);
				var pidChg = parseInt(payload.project_id || '0', 10);
				if (ridChg || pidChg) {
					$.ajax({
						type: 'POST',
						url: '?page=project_approve&op=get_change_request_detail',
						data: {
							page: 'project_approve',
							op: 'get_change_request_detail',
							change_request_id: ridChg,
							project_id: pidChg,
							request_id: ridChg
						},
						dataType: 'text'
					}).done(function(raw){
						var rr = null;
						try { rr = JSON.parse(String(raw || '')); } catch(e) { rr = null; }
						if (rr && rr.ok) {
							renderSummaryRows(rr.summary || [], rr);
							renderSequentialPanel(rr);
						}
					});
				}
			} else {
				var rid = parseInt(payload.request_id || '0', 10);
				if (rid) {
					$.ajax({
						type: 'POST',
						url: '?page=project_approve&op=get_request_detail',
						data: { request_id: rid },
						dataType: 'text'
					}).done(function(raw){
						var rr = null;
						try { rr = JSON.parse(String(raw || '')); } catch(e) { rr = null; }
						if (rr && rr.ok) {
							renderSummaryRows(rr.summary || [], rr);
							renderSequentialPanel(rr);
						}
					});
				}
			}

			// ค่อยเปลี่ยนหน้า (ให้ผู้ใช้เห็นข้อความก่อน)
			var redir = r.redirect ? String(r.redirect) : '';
			if (redir) {
				setTimeout(function(){ window.location.href = redir; }, 1600);
			}
		}).fail(function(xhr){
			var t = '';
			try { t = String(xhr && xhr.responseText ? xhr.responseText : '').trim(); } catch(e){}
			if (t.length > 300) t = t.slice(0, 300) + '...';
			showAjaxMsg('err', t || 'บันทึกไม่สำเร็จ');
			$('#mdCertBtnPass, #mdCertBtnBack').prop('disabled', false);
		});
	}

	function pmApprBuildApproveLink(requestId, opts){
		opts = opts || {};
		var rid = parseInt(requestId, 10) || 0;
		if (!rid) return '';
		var u;
		try { u = new URL(window.location.href); } catch (eUrl) { return ''; }
		u.searchParams.set('page', 'project_approve');
		u.searchParams.set('open_request_id', String(rid));
		var ys = String(opts.yearsSelect || pmApprYearsSelectPage || '').trim();
		if (ys) u.searchParams.set('years_select', ys);
		var pg = String(opts.projectGroup || currentProjectGroup || '').trim();
		if (pg) u.searchParams.set('project_group', pg);
		if (String(opts.flow || '') === 'operate') {
			var pid = parseInt(opts.projectId, 10) || 0;
			if (pid > 0) {
				u.searchParams.set('open_flow', 'operate');
				u.searchParams.set('open_project_id', String(pid));
			}
		} else if (String(opts.flow || '') === 'change') {
			var pidC = parseInt(opts.projectId, 10) || 0;
			u.searchParams.set('open_flow', 'change');
			if (pidC > 0) u.searchParams.set('open_project_id', String(pidC));
			u.searchParams.set('change_request_id', String(rid));
		} else {
			u.searchParams.delete('open_flow');
			u.searchParams.delete('open_project_id');
			u.searchParams.delete('change_request_id');
		}
		u.searchParams.delete('op');
		u.searchParams.delete('msg');
		u.searchParams.delete('err');
		return u.toString();
	}
	function pmApprCleanDeepLinkFromUrl(){
		try {
			var u = new URL(window.location.href);
			u.searchParams.delete('open_request_id');
			u.searchParams.delete('open_project_id');
			u.searchParams.delete('open_flow');
			u.searchParams.delete('request_id');
			u.searchParams.delete('change_request_id');
			u.searchParams.delete('project_id');
			window.history.replaceState({}, '', u.pathname + (u.search ? u.search : ''));
		} catch (eCl) {}
	}
	function pmApprUpdateCopyLinkBtn(yearsSelect){
		var flow = String($('#mdCertFlowType').val() || 'include');
		var rid = 0;
		var pid = 0;
		if (flow === 'operate') {
			rid = parseInt($('#mdOperateRequestId').val() || '0', 10) || 0;
			pid = parseInt($('#mdCertProjectId').val() || '0', 10) || 0;
			if (!rid) return $('#mdCopyApproveLinkBtn').hide();
		} else if (flow === 'change') {
			rid = parseInt($('#mdCertRequestId').val() || '0', 10) || 0;
			pid = parseInt($('#mdCertProjectId').val() || '0', 10) || 0;
			if (!rid) return $('#mdCopyApproveLinkBtn').hide();
		} else {
			rid = parseInt($('#mdRequestId').val() || '0', 10) || 0;
		}
		if (!rid) {
			$('#mdCopyApproveLinkBtn').hide();
			return;
		}
		var link = pmApprBuildApproveLink(rid, {
			flow: flow,
			projectId: pid,
			yearsSelect: yearsSelect || pmApprYearsSelectPage
		});
		$('#mdCopyApproveLinkBtn').data('approve-link', link).show();
	}
	function openIncludeRequestModal(rid){
		rid = parseInt(rid, 10) || 0;
		if (!rid) return;
		$('#mdRequestId').val(String(rid));
		$('#mdSectionName').val('');
		$('#mdDocNo').val('');
		$('#mdDate').val('');
		$('#mdSubject').val('');
		$('#mdBody').text('กำลังโหลด...');
		$('#mdSummaryBody').html('<tr><td colspan="5" class="text-center text-muted">กำลังโหลด...</td></tr>');
		$('#mdSectionAboveText').text('กำลังโหลด...');
		$('#mdEditAllWrap').hide();
		$('#mdEditAllBtn').attr('href', '#');
		$('#mdCertFlowType').val('include');
		$('#mdCertProjectId').val('');
		$('#mdOperateRequestId').val('');
		$('#mdSeqHeadText').text('การขอบรรจุโครงการ (ส่งตามลำดับ)');
		$('#mdSummaryHead').html('<tr><th class="text-center">ความสอดคล้อง</th><th class="text-center" style="width:130px;">จำนวนโครงการ</th><th class="text-center" style="width:140px;">งบประมาณ (บาท)</th><th class="text-center" style="min-width:220px;">โครงการ</th><th class="text-center" style="min-width:260px;">ตัวชี้วัดเป้าหมายโครงการ</th></tr>');
		submitterReplyEditing = false;
		$('#mdSubmitterReplyWrap').hide();
		$('#mdSubmitterReply').val('').prop('readonly', true);
		$('#mdSubmitterReplySave').hide();
		$('#mdSubmitterReplyMsg').hide().text('').removeClass('alert-danger alert-success');
		$('#mdSequentialWrap').hide();
		$('#mdSeqList').empty();
		try { $('#mdPlanUnitWrap').hide(); } catch(_epu1) {}
		try { $('#modalRequestDetail').modal('show'); } catch(e){}

		$.ajax({
			type: 'POST',
			url: '?page=project_approve&op=get_request_detail',
			data: { request_id: rid },
			dataType: 'text'
		}).done(function(raw){
			var resp = null;
			try { resp = JSON.parse(String(raw || '')); } catch(e) { resp = null; }
			if (!resp || !resp.ok) {
				var msg = (resp && resp.msg) ? resp.msg : '';
				if (!msg) {
					msg = String(raw || '').trim();
					if (msg.length > 600) msg = msg.slice(0, 600) + '...';
					if (!msg) msg = 'โหลดไม่สำเร็จ';
				}
				$('#mdBody').text(msg);
				$('#mdSummaryBody').html('<tr><td colspan="5" class="text-center text-muted">ไม่พบข้อมูล</td></tr>');
				$('#mdSectionAboveText').text('-');
				$('#mdSequentialWrap').hide();
				try { $('#mdPlanUnitWrap').hide(); } catch(_epu2) {}
				return;
			}
			var req = resp.request || {};
			lastDetailResp = resp;
			try { window.__pmLastDetailResp = resp; } catch (_eLdSet) {}
			try { rememberIncludeProjectIds(resp); } catch (_eStoreIds) {}
			var ysTit = String(req.budget_year || '');
			if (!ysTit) ysTit = String(resp.years_select || '');
			if (!ysTit && typeof pmApprYearsSelectPage !== 'undefined') ysTit = String(pmApprYearsSelectPage || '');
			var ysEsc = $('<div/>').text(ysTit || '…').html();
			$('#modalRequestDetail .md-title').html('<i class="fa fa-file-alt mr-1"></i> สรุปการขอพิจารณาแผนงาน ประจำปีงบประมาณ พ.ศ. ' + ysEsc);
			$('#mdSectionName').val(String(req.section_name || ''));
			var secAbove = String(req.section_name || '').trim();
			$('#mdSectionAboveText').text(secAbove !== '' ? secAbove : '-');
			$('#mdSubject').val(String(req.subject || ''));
			// input type="date" ต้องใช้รูปแบบ YYYY-MM-DD
			var isoDate = '';
			try { isoDate = String(req.created_at || '').slice(0, 10); } catch(e) { isoDate = ''; }
			$('#mdDate').val(isoDate);
			$('#mdDocNo').val('PHARM/' + String(req.id || rid));
			$('#mdBody').text(String(req.letter_body || ''));
			renderSummaryRows(resp.summary || [], resp);
			renderSequentialPanel(resp);
			var showEdit = !!(resp && resp.show_edit_all_projects);
			var eurl = (resp && resp.edit_all_projects_url) ? String(resp.edit_all_projects_url) : '';
			if (showEdit && eurl) {
				$('#mdEditAllWrap').show();
				$('#mdEditAllBtn').attr('href', eurl);
			} else {
				$('#mdEditAllWrap').hide();
				$('#mdEditAllBtn').attr('href', '#');
			}
			try { pmApprRenderReturnedFixUi(resp); } catch(_eFixUi2) {}
			pmApprUpdateCopyLinkBtn(ysTit);
		}).fail(function(xhr){
			var t = '';
			try { t = String(xhr && xhr.responseText ? xhr.responseText : '').trim(); } catch(e){}
			if (t.length > 600) t = t.slice(0, 600) + '...';
			$('#mdBody').text(t || 'โหลดไม่สำเร็จ');
			$('#mdSummaryBody').html('<tr><td colspan="5" class="text-center text-muted">ไม่พบข้อมูล</td></tr>');
			$('#mdSectionAboveText').text('-');
			$('#mdSequentialWrap').hide();
			try { $('#mdPlanUnitWrap').hide(); } catch(_epu3) {}
			$('#mdCopyApproveLinkBtn').hide();
		});
	}
	$(document).on('click', '#projectApproveTable button[data-action="open-request-modal"]', function(){
		var rid = parseInt($(this).attr('data-request-id') || '0', 10);
		pmApprMarkInboxRead($(this).closest('tr'));
		openIncludeRequestModal(rid);
	});

	$(document).on('click', '#projectApproveTable button[data-action="open-operate-modal"]', function(){
		var pid = parseInt($(this).attr('data-project-id') || '0', 10);
		if (!pid) return;
		var reqId = parseInt($(this).attr('data-request-id') || '0', 10);
		var $row = $(this).closest('tr');
		pmApprMarkInboxRead($row);
		var rowCode = String(($row.find('td').eq(1).text() || '').trim());
		var rowName = String(($row.find('td .pm-project-name').first().text() || $row.find('td').eq(2).text() || '').trim());
		var rowUnit = String(($row.find('td').eq(3).text() || '').trim());
		var rowBudgetRaw = String(($row.attr('data-budget') || '').trim());
		var rowBudgetNum = parseFloat(rowBudgetRaw);
		if (!isFinite(rowBudgetNum)) rowBudgetNum = 0;
		var rowBudgetFmt = fmtSumBudgetBaht(rowBudgetNum);
		if (!rowBudgetFmt) rowBudgetFmt = '0.00';
		var rowBudgetSrc = String(($row.attr('data-budget-source') || '').trim());
		var renderOperateFallback = function(msg){
			var ysFb = '';
			try { ysFb = String(pmApprYearsSelectPage || ''); } catch(eYsFb) {}
			var ysEscFb = $('<div/>').text(ysFb || '...').html();
			$('#modalRequestDetail .md-title').html('<i class="fa fa-file-alt mr-1"></i> สรุปการขอดำเนินการโครงการ ประจำปีงบประมาณ พ.ศ. ' + ysEscFb);
			$('#mdSectionAboveText').text(rowUnit !== '' ? rowUnit : '-');
			$('#mdBody').text(String(msg || ('รหัสโครงการ ' + (rowCode || '-') + ' / ชื่อโครงการ ' + (rowName || '-'))));
			$('#mdSummaryHead').html('<tr><th style="width:220px;">รายการ</th><th>รายละเอียด</th></tr>');
			$('#mdSummaryBody').html(
				'<tr><td>รหัสโครงการ</td><td>' + $('<div/>').text(rowCode || '-').html() + '</td></tr>'
				+ '<tr><td>ชื่อโครงการ</td><td>' + $('<div/>').text(rowName || '-').html() + '</td></tr>'
				+ '<tr><td>งบประมาณ (บาท)</td><td>' + $('<div/>').text(rowBudgetFmt).html() + '</td></tr>'
				+ '<tr><td>แหล่งงบประมาณ</td><td>' + $('<div/>').text(rowBudgetSrc || '-').html() + '</td></tr>'
			);
		};
		$('#mdRequestId').val(String(pid));
		$('#mdSectionName').val('');
		$('#mdDocNo').val('');
		$('#mdDate').val('');
		$('#mdSubject').val('');
		$('#mdBody').text('กำลังโหลด...');
		$('#mdSummaryBody').html('<tr><td colspan="2" class="text-center text-muted">กำลังโหลด...</td></tr>');
		$('#mdSectionAboveText').text('กำลังโหลด...');
		$('#mdEditAllWrap').hide();
		$('#mdEditAllBtn').attr('href', '#');
		submitterReplyEditing = false;
		$('#mdSubmitterReplyWrap').hide();
		$('#mdSubmitterReply').val('').prop('readonly', true);
		$('#mdSubmitterReplySave').hide();
		$('#mdSubmitterReplyMsg').hide().text('').removeClass('alert-danger alert-success');
		$('#mdSequentialWrap').hide();
		$('#mdSeqList').empty();
		$('#mdCertFlowType').val('operate');
		$('#mdCertProjectId').val(String(pid));
		$('#mdOperateRequestId').val(reqId > 0 ? String(reqId) : '');
		$('#mdSeqHeadText').text('การขอดำเนินการโครงการ (ส่งตามลำดับ)');
		$('#mdSummaryHead').html('<tr><th style="width:220px;">รายการ</th><th>รายละเอียด</th></tr>');
		$('#modalRequestDetail .md-title').html('<i class="fa fa-file-alt mr-1"></i> สรุปการขอดำเนินการโครงการ ประจำปีงบประมาณ พ.ศ. ...');
		try { $('#mdPlanUnitWrap').hide(); } catch(_epu1) {}
		try { $('#modalRequestDetail').modal('show'); } catch(e){}

		$.ajax({
			type: 'POST',
			url: '?page=project_approve&op=get_operate_project_detail',
			data: { page: 'project_approve', op: 'get_operate_project_detail', project_id: pid, request_id: reqId },
			dataType: 'text'
		}).done(function(raw){
			var resp = null;
			try { resp = JSON.parse(String(raw || '')); } catch(e) { resp = null; }
			if (!resp || !resp.ok) {
				var msg = (resp && resp.msg) ? resp.msg : '';
				if (!msg) {
					msg = String(raw || '').trim();
					if (msg.length > 600) msg = msg.slice(0, 600) + '...';
					if (!msg) msg = 'โหลดไม่สำเร็จ';
				}
				renderOperateFallback(msg);
				$('#mdSequentialWrap').hide();
				try { $('#mdPlanUnitWrap').hide(); } catch(_epu2) {}
				return;
			}
			var req = resp.request || {};
			lastDetailResp = resp;
			try {
				var irid = parseInt(resp.include_request_id || '0', 10);
				if (irid > 0) {
					$('#mdOperateRequestId').val(String(irid));
				}
			} catch(_eIr) {}
			var ysTit = String(req.budget_year || '');
			if (!ysTit) ysTit = String(resp.years_select || '');
			if (!ysTit && typeof pmApprYearsSelectPage !== 'undefined') ysTit = String(pmApprYearsSelectPage || '');
			var ysEsc = $('<div/>').text(ysTit || '…').html();
			$('#modalRequestDetail .md-title').html('<i class="fa fa-file-alt mr-1"></i> สรุปการขอดำเนินการโครงการ ประจำปีงบประมาณ พ.ศ. ' + ysEsc);
			$('#mdSectionName').val(String(req.section_name || ''));
			var secAbove = String(req.section_name || '').trim();
			$('#mdSectionAboveText').text(secAbove !== '' ? secAbove : '-');
			$('#mdSubject').val(String(req.subject || ''));
			var isoDate = '';
			try { isoDate = String(req.created_at || '').slice(0, 10); } catch(e) { isoDate = ''; }
			$('#mdDate').val(isoDate);
			$('#mdDocNo').val('OPERATE/' + String(req.id || pid));
			$('#mdBody').text(String(req.letter_body || ''));
			renderSummaryRows(resp.summary || [], resp);
			renderSequentialPanel(resp);
			$('#mdEditAllWrap').hide();
			$('#mdEditAllBtn').attr('href', '#');
			pmApprUpdateCopyLinkBtn(ysTit);
		}).fail(function(xhr){
			var t = '';
			try { t = String(xhr && xhr.responseText ? xhr.responseText : '').trim(); } catch(e){}
			if (t.length > 600) t = t.slice(0, 600) + '...';
			renderOperateFallback(t || 'โหลดไม่สำเร็จ');
			$('#mdSequentialWrap').hide();
			try { $('#mdPlanUnitWrap').hide(); } catch(_epu3) {}
			$('#mdCopyApproveLinkBtn').hide();
		});
	});

	function openChangeRequestModal(pid, reqId){
		pid = parseInt(pid || '0', 10);
		reqId = parseInt(reqId || '0', 10);
		if (!pid && !reqId) return;
		$('#mdRequestId').val(reqId > 0 ? String(reqId) : String(pid));
		$('#mdSectionName').val('');
		$('#mdDocNo').val('');
		$('#mdDate').val('');
		$('#mdSubject').val('');
		$('#mdBody').text('กำลังโหลด...');
		$('#mdSummaryBody').html('<tr><td colspan="2" class="text-center text-muted">กำลังโหลด...</td></tr>');
		$('#mdSectionAboveText').text('กำลังโหลด...');
		$('#mdEditAllWrap').hide();
		$('#mdEditAllBtn').attr('href', '#');
		submitterReplyEditing = false;
		$('#mdSubmitterReplyWrap').hide();
		$('#mdSubmitterReply').val('').prop('readonly', true);
		$('#mdSubmitterReplySave').hide();
		$('#mdSubmitterReplyMsg').hide().text('').removeClass('alert-danger alert-success');
		$('#mdSequentialWrap').hide();
		$('#mdSeqList').empty();
		$('#mdCertFlowType').val('change');
		$('#mdCertProjectId').val(pid > 0 ? String(pid) : '');
		$('#mdCertRequestId').val(reqId > 0 ? String(reqId) : '');
		$('#mdOperateRequestId').val(reqId > 0 ? String(reqId) : '');
		$('#mdSeqHeadText').text('การขอเปลี่ยนแปลงโครงการ (ส่งตามลำดับ)');
		$('#mdSummaryHead').html('<tr><th style="width:220px;">รายการ</th><th>รายละเอียด</th></tr>');
		$('#modalRequestDetail .md-title').html('<i class="fa fa-exchange-alt mr-1"></i> สรุปการขอเปลี่ยนแปลงโครงการ');
		try { $('#mdPlanUnitWrap').hide(); } catch(_epuC1) {}
		try { $('#modalRequestDetail').modal('show'); } catch(e){}
		$.ajax({
			type: 'POST',
			url: '?page=project_approve&op=get_change_request_detail',
			data: {
				page: 'project_approve',
				op: 'get_change_request_detail',
				project_id: pid,
				change_request_id: reqId,
				request_id: reqId
			},
			dataType: 'text'
		}).done(function(raw){
			var resp = null;
			try { resp = JSON.parse(String(raw || '')); } catch(e) { resp = null; }
			if (!resp || !resp.ok) {
				var msg = (resp && resp.msg) ? resp.msg : (String(raw || '').trim() || 'โหลดไม่สำเร็จ');
				$('#mdBody').text(msg);
				return;
			}
			var req = resp.request || {};
			lastDetailResp = resp;
			if (resp.change_request_id) {
				$('#mdCertRequestId').val(String(resp.change_request_id));
			}
			var ysTit = String(req.budget_year || resp.years_select || pmApprYearsSelectPage || '');
			var ysEsc = $('<div/>').text(ysTit || '…').html();
			$('#modalRequestDetail .md-title').html('<i class="fa fa-exchange-alt mr-1"></i> สรุปการขอเปลี่ยนแปลงโครงการ ประจำปีงบประมาณ พ.ศ. ' + ysEsc);
			$('#mdSectionName').val(String(req.section_name || ''));
			$('#mdSectionAboveText').text(String(req.section_name || '').trim() !== '' ? String(req.section_name) : '-');
			$('#mdSubject').val(String(req.subject || ''));
			var isoDate = '';
			try { isoDate = String(req.created_at || '').slice(0, 10); } catch(e) { isoDate = ''; }
			$('#mdDate').val(isoDate);
			$('#mdDocNo').val('CHANGE/' + String(req.id || reqId || pid));
			$('#mdBody').text(String(req.letter_body || ''));
			renderSummaryRows(resp.summary || [], resp);
			renderSequentialPanel(resp);
			$('#mdEditAllWrap').hide();
			pmApprUpdateCopyLinkBtn(ysTit);
		}).fail(function(){
			$('#mdBody').text('โหลดไม่สำเร็จ');
		});
	}
	$(document).on('click', '#projectApproveTable button[data-action="open-change-modal"]', function(){
		pmApprMarkInboxRead($(this).closest('tr'));
		openChangeRequestModal($(this).attr('data-project-id'), $(this).attr('data-request-id'));
	});

	function pmPlanCanEsc(s){ return $('<div/>').text(String(s||'')).html(); }
	function pmPlanCanFmtBaht(n){
		var x = Number(n);
		if (!isFinite(x)) x = 0;
		try { return x.toLocaleString('en-US', { maximumFractionDigits: 2 }); } catch(e){ return String(x); }
	}
	function pmPlanCanShow(){
		var $m = $('#modalPlanCancelRequest');
		try { $m.appendTo(document.body); } catch(e){}
		if (typeof $.fn.modal === 'function') $m.modal('show');
		else $m.addClass('show').css('display','block');
	}
	function pmPlanCanHide(){
		var $m = $('#modalPlanCancelRequest');
		if (typeof $.fn.modal === 'function') $m.modal('hide');
		else $m.removeClass('show').css('display','none');
	}
	function openCancelRequestModal(pid, rid){
		pid = parseInt(pid||'0', 10);
		rid = parseInt(rid||'0', 10);
		$('#pmPlanCanRequestId').val(rid ? String(rid) : '');
		$('#pmPlanCanProjectId').val(pid ? String(pid) : '');
		$('#pmPlanCanName, #pmPlanCanName2, #pmPlanCanCode, #pmPlanCanBudgetSrc, #pmPlanCanSender').text('-');
		$('#pmPlanCanBudget').text('0');
		$('#pmPlanCanReason').text('กำลังโหลด...');
		$('#pmPlanCanFiles').html('');
		$('#pmPlanCanNote').val('');
		$('#pmPlanCanStatus').addClass('d-none').removeClass('is-pending is-approved is-rejected').text('');
		$('#btnPmPlanCanApprove, #btnPmPlanCanReject').addClass('d-none').prop('disabled', true);
		pmPlanCanShow();
		$.ajax({
			type: 'POST',
			url: '?page=project_approve&op=get_cancel_request_detail',
			data: { page:'project_approve', op:'get_cancel_request_detail', project_id: pid, request_id: rid, cancel_request_id: rid },
			dataType: 'json'
		}).done(function(res){
			if (!res || !res.ok) {
				$('#pmPlanCanReason').text((res && res.msg) ? res.msg : 'โหลดไม่สำเร็จ');
				return;
			}
			var req = res.request || {};
			$('#pmPlanCanRequestId').val(String(req.id || rid || ''));
			$('#pmPlanCanProjectId').val(String(req.project_id || pid || ''));
			$('#pmPlanCanName, #pmPlanCanName2').text(req.project_name || '-');
			$('#pmPlanCanCode').text(req.project_code || '-');
			$('#pmPlanCanBudgetSrc').text(req.budget_source || '-');
			$('#pmPlanCanBudget').text(pmPlanCanFmtBaht(req.budget_amount));
			$('#pmPlanCanSender').text(req.sender_name || '-');
			$('#pmPlanCanReason').text(req.reason || '-');
			var st = String(req.req_status || '');
			var $st = $('#pmPlanCanStatus').removeClass('d-none is-pending is-approved is-rejected');
			if (st === 'approved') $st.addClass('is-approved').text('สถานะ: อนุมัติยกเลิกแล้ว — โครงการนี้ถูกยกเลิก');
			else if (st === 'rejected') $st.addClass('is-rejected').text('สถานะ: ตีกลับ' + (String(req.decide_note||'').trim() !== '' ? ('\nเหตุผล: ' + req.decide_note) : ''));
			else $st.addClass('is-pending').text('สถานะ: รอหน่วยแผนพิจารณาอนุมัติการยกเลิก');
			var files = Array.isArray(req.files) ? req.files : [];
			if (!files.length) {
				$('#pmPlanCanFiles').html('<span class="text-muted" style="font-weight:650;">ไม่มีเอกสารแนบ</span>');
			} else {
				var html = '';
				files.forEach(function(f){
					html += '<a class="pm-plan-can-file-chip" href="'+pmPlanCanEsc(f.url||'#')+'" target="_blank" rel="noopener"><i class="fa fa-file-pdf"></i>'+pmPlanCanEsc(f.name||'document.pdf')+'</a>';
				});
				$('#pmPlanCanFiles').html(html);
			}
			var canDecide = Number(res.can_decide||0) === 1;
			$('#btnPmPlanCanApprove, #btnPmPlanCanReject').toggleClass('d-none', !canDecide).prop('disabled', !canDecide);
			$('#pmPlanCanNoteWrap').toggle(canDecide || st === 'rejected');
			if (!canDecide) $('#pmPlanCanNote').prop('readonly', st !== 'rejected').prop('disabled', !canDecide && st !== 'rejected');
			else $('#pmPlanCanNote').prop('readonly', false).prop('disabled', false);
			if (st === 'rejected') $('#pmPlanCanNote').val(String(req.decide_note||'')).prop('readonly', true);
		}).fail(function(){
			$('#pmPlanCanReason').text('โหลดไม่สำเร็จ');
		});
	}
	function pmPlanCanDecide(action){
		var rid = parseInt($('#pmPlanCanRequestId').val()||'0', 10);
		var pid = parseInt($('#pmPlanCanProjectId').val()||'0', 10);
		var note = String($('#pmPlanCanNote').val()||'').trim();
		if (!rid) return;
		if (action === 'reject' && !note) {
			if (typeof Swal !== 'undefined') Swal.fire({ icon:'warning', title:'กรุณาระบุเหตุผลก่อนตีกลับ' });
			else alert('กรุณาระบุเหตุผลก่อนตีกลับ');
			return;
		}
		var doPost = function(){
			$('#btnPmPlanCanApprove, #btnPmPlanCanReject').prop('disabled', true);
			$.ajax({
				type: 'POST',
				url: '?page=project_approve&op=plan_admin_cancel_decide_ajax',
				data: { page:'project_approve', op:'plan_admin_cancel_decide_ajax', request_id: rid, cancel_request_id: rid, project_id: pid, action: action, decide_note: note },
				dataType: 'json'
			}).done(function(res){
				if (res && res.ok === 1) {
					pmPlanCanHide();
					if (typeof Swal !== 'undefined') {
						Swal.fire({ icon:'success', title: (res.msg || 'บันทึกแล้ว'), timer: 1800, showConfirmButton:false }).then(function(){ window.location.reload(); });
					} else {
						alert(res.msg || 'บันทึกแล้ว');
						window.location.reload();
					}
					return;
				}
				var err = (res && res.msg) ? res.msg : 'บันทึกไม่สำเร็จ';
				if (typeof Swal !== 'undefined') Swal.fire({ icon:'error', title: err });
				else alert(err);
			}).fail(function(){
				if (typeof Swal !== 'undefined') Swal.fire({ icon:'error', title:'เชื่อมต่อไม่สำเร็จ' });
				else alert('เชื่อมต่อไม่สำเร็จ');
			}).always(function(){
				$('#btnPmPlanCanApprove, #btnPmPlanCanReject').prop('disabled', false);
			});
		};
		if (typeof Swal !== 'undefined') {
			Swal.fire({
				icon: 'question',
				title: action === 'approve' ? 'ยืนยันอนุมัติยกเลิกโครงการ?' : 'ยืนยันตีกลับคำขอยกเลิก?',
				text: action === 'approve' ? 'เมื่ออนุมัติแล้ว โครงการจะแสดงสถานะยกเลิก' : 'ผู้ยื่นจะสามารถแก้ไขเหตุผล/เอกสารแล้วส่งใหม่ได้',
				showCancelButton: true,
				confirmButtonText: action === 'approve' ? 'อนุมัติยกเลิก' : 'ตีกลับ',
				cancelButtonText: 'ยกเลิก',
				confirmButtonColor: action === 'approve' ? '#dc2626' : '#ea580c'
			}).then(function(r){ if (r && r.isConfirmed) doPost(); });
		} else if (confirm(action === 'approve' ? 'ยืนยันอนุมัติยกเลิกโครงการ?' : 'ยืนยันตีกลับคำขอยกเลิก?')) {
			doPost();
		}
	}
	$(document).on('click', '#projectApproveTable button[data-action="open-cancel-modal"]', function(){
		pmApprMarkInboxRead($(this).closest('tr'));
		openCancelRequestModal($(this).attr('data-project-id'), $(this).attr('data-request-id'));
	});
	$(document).on('click', '#modalPlanCancelRequest .pm-plan-can-close, #modalPlanCancelRequest [data-dismiss="modal"]', function(e){
		e.preventDefault();
		pmPlanCanHide();
	});
	$(document).on('click', '#btnPmPlanCanApprove', function(){ pmPlanCanDecide('approve'); });
	$(document).on('click', '#btnPmPlanCanReject', function(){ pmPlanCanDecide('reject'); });

	$(document).on('click', '#projectApproveTable button[data-action="open-report-modal"]', function(){
		var pid = parseInt($(this).attr('data-project-id') || '0', 10);
		if (!pid) return;
		var $row = $(this).closest('tr');
		pmApprMarkInboxRead($row);
		var rowCode = String(($row.find('td').eq(1).text() || '').trim());
		var rowName = String(($row.find('td .pm-project-name').first().text() || $row.find('td').eq(2).text() || '').trim());
		var rowUnit = String(($row.find('td').eq(3).text() || '').trim());
		var rowBudgetRaw = String(($row.attr('data-budget') || '').trim());
		var rowBudgetNum = parseFloat(rowBudgetRaw);
		if (!isFinite(rowBudgetNum)) rowBudgetNum = 0;
		var rowBudgetFmt = fmtSumBudgetBaht(rowBudgetNum);
		if (!rowBudgetFmt) rowBudgetFmt = '0.00';
		var rowBudgetSrc = String(($row.attr('data-budget-source') || '').trim());
		var renderReportFallback = function(msg){
			var ysFb = '';
			try { ysFb = String(pmApprYearsSelectPage || ''); } catch(eYsFb) {}
			var ysEscFb = $('<div/>').text(ysFb || '...').html();
			$('#modalRequestDetail .md-title').html('<i class="fa fa-file-alt mr-1"></i> สรุปการส่งรายงานผลโครงการ ประจำปีงบประมาณ พ.ศ. ' + ysEscFb);
			$('#mdSectionAboveText').text(rowUnit !== '' ? rowUnit : '-');
			$('#mdBody').text(String(msg || ('รหัสโครงการ ' + (rowCode || '-') + ' / ชื่อโครงการ ' + (rowName || '-'))));
			$('#mdSummaryHead').html('<tr><th style="width:220px;">รายการ</th><th>รายละเอียด</th></tr>');
			$('#mdSummaryBody').html(
				'<tr><td>รหัสโครงการ</td><td>' + $('<div/>').text(rowCode || '-').html() + '</td></tr>'
				+ '<tr><td>ชื่อโครงการ</td><td>' + $('<div/>').text(rowName || '-').html() + '</td></tr>'
				+ '<tr><td>งบประมาณ (บาท)</td><td>' + $('<div/>').text(rowBudgetFmt).html() + '</td></tr>'
				+ '<tr><td>แหล่งงบประมาณ</td><td>' + $('<div/>').text(rowBudgetSrc || '-').html() + '</td></tr>'
			);
		};
		$('#mdRequestId').val(String(pid));
		$('#mdSectionName').val('');
		$('#mdDocNo').val('');
		$('#mdDate').val('');
		$('#mdSubject').val('');
		$('#mdBody').text('กำลังโหลด...');
		$('#mdSummaryBody').html('<tr><td colspan="2" class="text-center text-muted">กำลังโหลด...</td></tr>');
		$('#mdSectionAboveText').text('กำลังโหลด...');
		$('#mdEditAllWrap').hide();
		$('#mdEditAllBtn').attr('href', '#');
		submitterReplyEditing = false;
		$('#mdSubmitterReplyWrap').hide();
		$('#mdSubmitterReply').val('').prop('readonly', true);
		$('#mdSubmitterReplySave').hide();
		$('#mdSubmitterReplyMsg').hide().text('').removeClass('alert-danger alert-success');
		$('#mdSequentialWrap').hide();
		$('#mdSeqList').empty();
		$('#mdCertFlowType').val('report');
		$('#mdCertProjectId').val(String(pid));
		$('#mdOperateRequestId').val('');
		$('#mdSeqHeadText').text('การส่งรายงานผลโครงการ (ส่งตามลำดับ)');
		$('#mdSummaryHead').html('<tr><th style="width:220px;">รายการ</th><th>รายละเอียด</th></tr>');
		$('#modalRequestDetail .md-title').html('<i class="fa fa-file-alt mr-1"></i> สรุปการส่งรายงานผลโครงการ ประจำปีงบประมาณ พ.ศ. ...');
		try { $('#mdPlanUnitWrap').hide(); } catch(_epuR1) {}
		try { $('#modalRequestDetail').modal('show'); } catch(e){}

		$.ajax({
			type: 'POST',
			url: '?page=project_approve&op=get_report_project_detail',
			data: { page: 'project_approve', op: 'get_report_project_detail', project_id: pid },
			dataType: 'text'
		}).done(function(raw){
			var resp = null;
			try { resp = JSON.parse(String(raw || '')); } catch(e) { resp = null; }
			if (!resp || !resp.ok) {
				var msg = (resp && resp.msg) ? resp.msg : '';
				if (!msg) {
					msg = String(raw || '').trim();
					if (msg.length > 600) msg = msg.slice(0, 600) + '...';
					if (!msg) msg = 'โหลดไม่สำเร็จ';
				}
				renderReportFallback(msg);
				$('#mdSequentialWrap').hide();
				try { $('#mdPlanUnitWrap').hide(); } catch(_epuR2) {}
				return;
			}
			var req = resp.request || {};
			lastDetailResp = resp;
			var ysTit = String(req.budget_year || '');
			if (!ysTit) ysTit = String(resp.years_select || '');
			if (!ysTit && typeof pmApprYearsSelectPage !== 'undefined') ysTit = String(pmApprYearsSelectPage || '');
			var ysEsc = $('<div/>').text(ysTit || '…').html();
			$('#modalRequestDetail .md-title').html('<i class="fa fa-file-alt mr-1"></i> สรุปการส่งรายงานผลโครงการ ประจำปีงบประมาณ พ.ศ. ' + ysEsc);
			$('#mdSectionName').val(String(req.section_name || ''));
			var secAbove = String(req.section_name || '').trim();
			$('#mdSectionAboveText').text(secAbove !== '' ? secAbove : '-');
			$('#mdSubject').val(String(req.subject || ''));
			var isoDate = '';
			try { isoDate = String(req.created_at || '').slice(0, 10); } catch(e) { isoDate = ''; }
			$('#mdDate').val(isoDate);
			$('#mdDocNo').val('REPORT/' + String(req.id || pid));
			$('#mdBody').text(String(req.letter_body || ''));
			renderSummaryRows(resp.summary || [], resp);
			renderSequentialPanel(resp);
			$('#mdEditAllWrap').hide();
			$('#mdEditAllBtn').attr('href', '#');
			$('#mdCopyApproveLinkBtn').hide();
		}).fail(function(xhr){
			var t = '';
			try { t = String(xhr && xhr.responseText ? xhr.responseText : '').trim(); } catch(e){}
			if (t.length > 600) t = t.slice(0, 600) + '...';
			renderReportFallback(t || 'โหลดไม่สำเร็จ');
			$('#mdSequentialWrap').hide();
			try { $('#mdPlanUnitWrap').hide(); } catch(_epuR3) {}
			$('#mdCopyApproveLinkBtn').hide();
		});
	});

	$(document).on('click', '#mdCopyApproveLinkBtn', function(){
		var $b = $(this);
		var link = String($b.data('approve-link') || '').trim();
		if (!link) {
			pmApprUpdateCopyLinkBtn();
			link = String($('#mdCopyApproveLinkBtn').data('approve-link') || '').trim();
		}
		if (!link) return;
		pmApprCopyLinkToClipboard(link, $b);
	});
	$(document).on('click', '#mdSeqHead', function(){
		if (String($(this).attr('data-foldable') || '') !== '1') return;
		var $body = $('#mdSeqBody');
		var show = $body.is(':hidden');
		$body.toggle(show);
		$(this).toggleClass('is-collapsed', !show);
	});
	$(document).on('keydown', '#mdSeqHead', function(e){
		if (e.key !== 'Enter' && e.key !== ' ') return;
		if (String($(this).attr('data-foldable') || '') !== '1') return;
		e.preventDefault();
		$(this).trigger('click');
	});
	$(document).on('click', '#mdSeqCertFoldBtn', function(){
		var $w = $('#mdSeqCertWrap');
		var show = $w.is(':hidden');
		$w.toggle(show);
		$(this).toggleClass('is-collapsed', !show);
	});
	$(document).on('click', '.md-seq-per-link', function(e){
		e.preventDefault();
		e.stopPropagation();
		var $b = $(this);
		var link = String($b.attr('data-approve-link') || '').trim();
		if (!link) return false;
		pmApprCopyLinkToClipboard(link, $b);
		return false;
	});

	function pmApprTryDeepLink(){
		if (!pmApprDeepLink || pmApprDeepLink.handled) return;
		var rid = parseInt(pmApprDeepLink.requestId, 10) || 0;
		var pid = parseInt(pmApprDeepLink.projectId, 10) || 0;
		var flow = String(pmApprDeepLink.flow || 'include');
		if (flow === 'change' && (pid > 0 || rid > 0)) {
			pmApprDeepLink.handled = true;
			var $btnC = $('#projectApproveTable button[data-action="open-change-modal"][data-request-id="' + rid + '"]').first();
			if (!$btnC.length && pid > 0) {
				$btnC = $('#projectApproveTable button[data-action="open-change-modal"][data-project-id="' + pid + '"]').first();
			}
			if ($btnC.length) {
				$btnC.trigger('click');
			} else {
				openChangeRequestModal(pid, rid);
			}
			pmApprCleanDeepLinkFromUrl();
			return;
		}
		if (flow === 'operate' && pid > 0) {
			pmApprDeepLink.handled = true;
			var $btn = $('#projectApproveTable button[data-action="open-operate-modal"][data-project-id="' + pid + '"]').first();
			if ($btn.length) {
				$btn.trigger('click');
			} else {
				$('#mdCertFlowType').val('operate');
				$('#mdCertProjectId').val(String(pid));
				$('#mdOperateRequestId').val(rid > 0 ? String(rid) : '');
				$('#mdRequestId').val(String(pid));
				$('#mdBody').text('กำลังโหลด...');
				$('#mdSummaryBody').html('<tr><td colspan="2" class="text-center text-muted">กำลังโหลด...</td></tr>');
				try { $('#modalRequestDetail').modal('show'); } catch (eMo) {}
				$.ajax({
					type: 'POST',
					url: '?page=project_approve&op=get_operate_project_detail',
					data: { page: 'project_approve', op: 'get_operate_project_detail', project_id: pid, request_id: rid },
					dataType: 'text'
				}).done(function(raw){
					var resp = null;
					try { resp = JSON.parse(String(raw || '')); } catch (eJ) { resp = null; }
					if (resp && resp.ok) {
						lastDetailResp = resp;
						renderSummaryRows(resp.summary || [], resp);
						renderSequentialPanel(resp);
						pmApprUpdateCopyLinkBtn(String((resp.request || {}).budget_year || ''));
					}
				});
			}
			pmApprCleanDeepLinkFromUrl();
			return;
		}
		if (rid > 0) {
			pmApprDeepLink.handled = true;
			openIncludeRequestModal(rid);
			pmApprCleanDeepLinkFromUrl();
		}
	}
	setTimeout(pmApprTryDeepLink, 450);

	$(document).on('click', '#mdSubmitterReplySave', function(){
		var rid = parseInt($('#mdRequestId').val() || '0', 10);
		if (!rid) return;
		var note = String($('#mdSubmitterReply').val() || '').trim();
		if (!note) {
			if (typeof Swal !== 'undefined') {
				Swal.fire({ icon: 'warning', title: 'กรุณากรอกคำชี้แจง', text: 'ต้องระบุคำชี้แจงก่อนยืนยันส่งกลับผู้ตรวจสอบ' });
			} else {
				alert('กรุณากรอกคำชี้แจงก่อนยืนยันส่งกลับผู้ตรวจสอบ');
			}
			return;
		}
		var doSave = function(){
		var showReplyMsg = function(kind, text){
			var $m = $('#mdSubmitterReplyMsg');
			if (!$m.length) { try { window.alert(String(text||'')); } catch(e){} return; }
			var cls = (kind === 'ok') ? 'alert alert-success' : 'alert alert-danger';
			$m.attr('class', cls).text(String(text || '')).css({ display:'block' }).show();
		};
		$('#mdSubmitterReplySave').prop('disabled', true);
		showReplyMsg('ok', 'กำลังส่ง...');
		$.ajax({
			type: 'POST',
			url: '?page=project_approve',
			data: { ajax: '1', op: 'save_submitter_reply', request_id: rid, submitter_reply_note: note },
			dataType: 'text'
		}).done(function(raw){
			var r = null;
			try { r = JSON.parse(String(raw || '')); } catch(e) { r = null; }
			if (!r || !r.ok) {
				var msg = (r && r.msg) ? String(r.msg) : 'บันทึกไม่สำเร็จ';
				showReplyMsg('err', msg);
				$('#mdSubmitterReplySave').prop('disabled', false);
				return;
			}
			showReplyMsg('ok', (r.msg ? String(r.msg) : 'ส่งสำเร็จ'));
			// รีเฟรชจากฐานข้อมูลให้แน่ใจว่าบันทึกจริง แล้วซ่อนช่องกรอก
			submitterReplyEditing = false;
			$.ajax({
				type: 'POST',
				url: '?page=project_approve&op=get_request_detail',
				data: { request_id: rid },
				dataType: 'text'
			}).done(function(raw2){
				var rr = null;
				try { rr = JSON.parse(String(raw2 || '')); } catch(e2) { rr = null; }
				if (rr && rr.ok) {
					renderSequentialPanel(rr);
					try {
						var showEdit = !!(rr && rr.show_edit_all_projects);
						var eurl = (rr && rr.edit_all_projects_url) ? String(rr.edit_all_projects_url) : '';
						if (showEdit && eurl) {
							$('#mdEditAllWrap').show();
							$('#mdEditAllBtn').attr('href', eurl);
						} else {
							$('#mdEditAllWrap').hide();
							$('#mdEditAllBtn').attr('href', '#');
						}
					} catch (_eHideEdit) {}
				} else {
					// fallback: อย่างน้อยอัปเดตในหน้านี้
					if (lastDetailResp && lastDetailResp.submitter_reply) {
						lastDetailResp.submitter_reply.note = note;
						renderSequentialPanel(lastDetailResp);
					}
					$('#mdEditAllWrap').hide();
				}
				$('#mdSubmitterReplySave').prop('disabled', false);
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'success',
						title: 'ส่งกลับผู้ตรวจสอบแล้ว',
						text: 'สถานะโครงการเป็นรอบรรจุโครงการ และล็อกไม่ให้แก้ไข',
						confirmButtonText: 'ตกลง'
					}).then(function(){
						try { window.location.reload(); } catch (_eRel) {}
					});
				} else {
					try { window.location.reload(); } catch (_eRel2) {}
				}
			}).fail(function(){
				if (lastDetailResp && lastDetailResp.submitter_reply) {
					lastDetailResp.submitter_reply.note = note;
					if (r && r.updated_at) lastDetailResp.submitter_reply.updated_at = String(r.updated_at);
					renderSequentialPanel(lastDetailResp);
				}
				$('#mdSubmitterReplySave').prop('disabled', false);
				try { window.location.reload(); } catch (_eRel3) {}
			});
		}).fail(function(xhr){
			var t = '';
			try { t = String(xhr && xhr.responseText ? xhr.responseText : '').trim(); } catch(e){}
			if (t.length > 240) t = t.slice(0, 240) + '...';
			showReplyMsg('err', t || 'ส่งไม่สำเร็จ');
			$('#mdSubmitterReplySave').prop('disabled', false);
		});
		};
		if (typeof Swal !== 'undefined') {
			Swal.fire({
				icon: 'question',
				title: 'ยืนยันส่งกลับผู้ตรวจสอบ?',
				html: 'เมื่อยืนยันแล้ว <strong>สถานะจะเป็นรอบรรจุโครงการ</strong> และ<strong>ล็อกไม่ให้แก้ไข</strong>',
				showCancelButton: true,
				confirmButtonText: 'ยืนยันส่ง',
				cancelButtonText: 'ยกเลิก'
			}).then(function(res){
				if (res && res.isConfirmed) doSave();
			});
		} else if (confirm('ยืนยันส่งกลับผู้ตรวจสอบ? สถานะจะเป็นรอบรรจุโครงการและล็อกไม่ให้แก้ไข')) {
			doSave();
		}
	});
})();
</script>

