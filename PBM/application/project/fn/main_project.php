<?php
include_once(__DIR__ . '/../../config/config_db.php');

/** Plan admin ขอเรียงผู้รับรองใหม่หลัง operate ครบ — ให้ได้ทั้งโครงการ status=1 และกรณีหลังตีกลับผู้ยื่นแล้วส่งคิวใหม่แล้ว (status=8) */
if (!function_exists('pm_notify_allows_plan_admin_operate_rechain')) {
	function pm_notify_allows_plan_admin_operate_rechain($notify_result, $status_project): bool
	{
		$nr = trim((string)$notify_result);
		$st = trim((string)$status_project);
		if ($st !== '1' && $st !== '8') {
			return false;
		}
		if ($nr === 'บรรจุแผนและแก้ไข') {
			return false;
		}
		if ($nr !== '' && preg_match('/ไม่ผ่าน/u', $nr)) {
			return false;
		}
		return true;
	}
}

/** โครงการที่หน่วยแผนติ๊กให้แสดงในระบบลาราชการ */
if (!function_exists('pm_ensure_show_on_leave_system_schema')) {
	function pm_ensure_show_on_leave_system_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'show_on_leave_system'");
		if (!$chk || mysqli_num_rows($chk) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN show_on_leave_system TINYINT(1) NOT NULL DEFAULT 0");
		}
	}
}

if (!function_exists('pm_project_shows_on_leave_system')) {
	function pm_project_shows_on_leave_system($preRow): bool
	{
		return (int)($preRow['show_on_leave_system'] ?? 0) === 1;
	}
}

/** ชื่อตำแหน่งจากรหัสตำแหน่งของบุคลากร (ใช้ตอนเลือกผู้ตรวจสอบ/ผู้รับรอง/ผู้พิจารณา) */
if (!function_exists('pm_staff_position_label')) {
	function pm_staff_position_label($db_person, $staffRow): string
	{
		if (!is_array($staffRow)) {
			return '';
		}
		$pc = trim((string)($staffRow['position_code'] ?? ''));
		if ($pc !== '' && $db_person) {
			static $map = null;
			if ($map === null) {
				$map = [];
				if (function_exists('getPositionSelect')) {
					$posList = getPositionSelect($db_person);
					if (is_array($posList)) {
						foreach ($posList as $p) {
							$c = trim((string)($p['code'] ?? ''));
							if ($c === '') {
								continue;
							}
							$map[$c] = trim((string)($p['name'] ?? ''));
						}
					}
				}
			}
			$fromMap = trim((string)($map[$pc] ?? ''));
			if ($fromMap !== '') {
				return $fromMap;
			}
			if (function_exists('getPosition')) {
				$rp = getPosition($db_person, $pc);
				if (is_array($rp)) {
					$fromPos = trim((string)($rp['name'] ?? ''));
					if ($fromPos !== '') {
						return $fromPos;
					}
				}
			}
		}
		$pd = trim((string)($staffRow['position_dean'] ?? ''));
		if ($pd !== '' && $db_person && function_exists('getPositionDean')) {
			$rd = getPositionDean($db_person, $pd);
			if (is_array($rd)) {
				$fromDean = trim((string)($rd['name'] ?? $rd['name_th'] ?? ''));
				if ($fromDean !== '') {
					return $fromDean;
				}
			}
		}
		foreach (['position_name', 'position', 'positionNameTha'] as $k) {
			$v = trim((string)($staffRow[$k] ?? ''));
			if ($v !== '') {
				return $v;
			}
		}
		return '';
	}
}

/** เลขที่หนังสือบันทึกข้อความของหน่วยงาน: paper_name/paper_start โดยไม่มี // ซ้ำ */
if (!function_exists('pm_unit_paper_doc_no')) {
	function pm_unit_paper_doc_no($paperName, $paperStart = '', $fallbackRun = 1): string
	{
		$name = rtrim(trim((string)$paperName), " \t/");
		if ($name === '') {
			$name = 'อว';
		}
		$start = trim((string)$paperStart, " \t/");
		if ($start === '') {
			$run = preg_replace('/\D+/', '', (string)$fallbackRun);
			if ($run === '') {
				$run = '1';
			}
			$start = 'ผ' . str_pad($run, 3, '0', STR_PAD_LEFT);
		} elseif (preg_match('/^\d+$/', $start)) {
			$start = 'ผ' . str_pad($start, 3, '0', STR_PAD_LEFT);
		}
		$doc = $name . '/' . $start;
		$doc = preg_replace('#\s*/+\s*#', '/', $doc);
		return (string)$doc;
	}
}

/**
 * คำขอขอบรรจุที่ถูกตีกลับ: ยังต้องให้ผู้ยื่นส่งคำชี้แจงรอบใหม่หรือไม่
 * คำชี้แจงเดิมเก็บไว้เสมอ — เทียบเวลาตีกลับล่าสุดกับเวลาส่งคำชี้แจง
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

if (!function_exists('pm_parse_operate_chain_json')) {
	/** แปลง JSON ลำดับผู้รับรอง/ผู้อนุมัติ เป็น map sort_order => staff_id, position_title */
	function pm_parse_operate_chain_json($json): array
	{
		$certRaw = trim((string)$json);
		$certArr = ($certRaw !== '') ? json_decode($certRaw, true) : [];
		if (!is_array($certArr)) {
			$certArr = [];
		}
		$out = [];
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
			$out[$so] = [
				'staff_id' => $sidC,
				'position_title' => trim((string)($c['position_title'] ?? $c['position'] ?? '')),
			];
		}
		return $out;
	}
}

if (!function_exists('pm_operate_chain_staff_in_json')) {
	function pm_operate_chain_staff_in_json($json, $staff_id): bool
	{
		$staff_id = (int)$staff_id;
		if ($staff_id <= 0) {
			return false;
		}
		foreach (pm_parse_operate_chain_json($json) as $row) {
			if ((int)($row['staff_id'] ?? 0) === $staff_id) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('pm_ensure_operate_approver_schema')) {
	/** คอลัมน์/ตารางลำดับผู้อนุมัติที่หน่วยแผนเลือก (แยกจากผู้รับรองฝั่งผู้ใช้) */
	function pm_ensure_operate_approver_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'operate_approvers_json'");
		if (!$chk || mysqli_num_rows($chk) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN operate_approvers_json MEDIUMTEXT NULL");
		}
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_operate_approver_decision (
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
			UNIQUE KEY uk_poad_project_sort (project_id, sort_order),
			KEY idx_poad_project (project_id),
			KEY idx_poad_staff (staff_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}
}

if (!function_exists('pm_ensure_change_request_schema')) {
	function pm_ensure_change_request_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_change_request (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			project_id INT UNSIGNED NOT NULL,
			reason MEDIUMTEXT NULL,
			details_json LONGTEXT NULL,
			certifiers_json LONGTEXT NULL,
			project_name VARCHAR(500) NULL,
			project_code VARCHAR(80) NULL,
			budget_source VARCHAR(255) NULL,
			budget_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
			staff_insert INT UNSIGNED NULL,
			req_status VARCHAR(32) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			status TINYINT NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY idx_pcr_project_st (project_id, req_status, status),
			KEY idx_pcr_staff (staff_insert)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_change_request_file (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_id INT UNSIGNED NOT NULL,
			original_name VARCHAR(255) NULL,
			stored_file VARCHAR(255) NULL,
			staff_insert INT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			status TINYINT NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY idx_pcrf_req (request_id, status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_change_certifier_decision (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_id INT UNSIGNED NOT NULL,
			project_id INT UNSIGNED NOT NULL,
			sort_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
			staff_id INT UNSIGNED NOT NULL,
			decision VARCHAR(16) NOT NULL DEFAULT '',
			certifier_note MEDIUMTEXT NULL,
			decided_at DATETIME NULL,
			decided_by_staff_id INT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uk_pccd_req_sort (request_id, sort_order),
			KEY idx_pccd_staff (staff_id),
			KEY idx_pccd_project (project_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_change_approver_decision (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_id INT UNSIGNED NOT NULL,
			project_id INT UNSIGNED NOT NULL,
			sort_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
			staff_id INT UNSIGNED NOT NULL,
			decision VARCHAR(16) NOT NULL DEFAULT '',
			certifier_note MEDIUMTEXT NULL,
			decided_at DATETIME NULL,
			decided_by_staff_id INT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uk_pcad_req_sort (request_id, sort_order),
			KEY idx_pcad_staff (staff_id),
			KEY idx_pcad_project (project_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_change_apply_log (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_id INT UNSIGNED NOT NULL,
			project_id INT UNSIGNED NOT NULL,
			apply_note MEDIUMTEXT NULL,
			details_json LONGTEXT NULL,
			staff_id INT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_pcal_req (request_id),
			KEY idx_pcal_project (project_id, created_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		$chgAlters = [
			'approvers_json' => "ALTER TABLE project_change_request ADD COLUMN approvers_json MEDIUMTEXT NULL",
			'plan_rechain_submitted_at' => "ALTER TABLE project_change_request ADD COLUMN plan_rechain_submitted_at DATETIME NULL DEFAULT NULL",
			'plan_rechain_staff_id' => "ALTER TABLE project_change_request ADD COLUMN plan_rechain_staff_id INT UNSIGNED NULL",
			'submitter_reply_note' => "ALTER TABLE project_change_request ADD COLUMN submitter_reply_note MEDIUMTEXT NULL",
			'applied_at' => "ALTER TABLE project_change_request ADD COLUMN applied_at DATETIME NULL DEFAULT NULL",
			'applied_by_staff_id' => "ALTER TABLE project_change_request ADD COLUMN applied_by_staff_id INT UNSIGNED NULL",
			'apply_note' => "ALTER TABLE project_change_request ADD COLUMN apply_note MEDIUMTEXT NULL",
			'approve_memo_esign_ref' => "ALTER TABLE project_change_request ADD COLUMN approve_memo_esign_ref VARCHAR(160) NULL",
			'approve_memo_esign_at' => "ALTER TABLE project_change_request ADD COLUMN approve_memo_esign_at DATETIME NULL DEFAULT NULL",
		];
		foreach ($chgAlters as $col => $sql) {
			$chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_change_request LIKE '" . mysqli_real_escape_string($db_Project, $col) . "'");
			if (!$chk || mysqli_num_rows($chk) === 0) {
				@mysqli_query($db_Project, $sql);
			}
		}
		$chgDir = __DIR__ . '/../document/project_change_files';
		if (!is_dir($chgDir)) {
			@mkdir($chgDir, 0775, true);
		}
	}
}

if (!function_exists('pm_ensure_cancel_request_schema')) {
	function pm_ensure_cancel_request_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_cancel_request (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			project_id INT UNSIGNED NOT NULL,
			reason MEDIUMTEXT NULL,
			project_name VARCHAR(500) NULL,
			project_code VARCHAR(80) NULL,
			budget_source VARCHAR(255) NULL,
			budget_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
			staff_insert INT UNSIGNED NULL,
			req_status VARCHAR(32) NOT NULL DEFAULT 'draft',
			submitted_at DATETIME NULL DEFAULT NULL,
			decided_at DATETIME NULL DEFAULT NULL,
			decided_by_staff_id INT UNSIGNED NULL,
			decide_note MEDIUMTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			status TINYINT NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY idx_pcrq_project_st (project_id, req_status, status),
			KEY idx_pcrq_staff (staff_insert)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_cancel_request_file (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_id INT UNSIGNED NOT NULL,
			original_name VARCHAR(255) NULL,
			stored_file VARCHAR(255) NULL,
			staff_insert INT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			status TINYINT NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY idx_pcrqf_req (request_id, status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		$preAlters = [
			'cancelled_at' => "ALTER TABLE pre_project ADD COLUMN cancelled_at DATETIME NULL DEFAULT NULL",
			'cancelled_by_staff_id' => "ALTER TABLE pre_project ADD COLUMN cancelled_by_staff_id INT UNSIGNED NULL",
		];
		foreach ($preAlters as $col => $sql) {
			$chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE '" . mysqli_real_escape_string($db_Project, $col) . "'");
			if (!$chk || mysqli_num_rows($chk) === 0) {
				@mysqli_query($db_Project, $sql);
			}
		}
		$canDir = __DIR__ . '/../document/project_cancel_files';
		if (!is_dir($canDir)) {
			@mkdir($canDir, 0775, true);
		}
	}
}

if (!function_exists('pm_load_project_cancel_request')) {
	function pm_load_project_cancel_request($db_Project, int $projectId)
	{
		$pid = (int)$projectId;
		if ($pid <= 0 || !$db_Project) {
			return null;
		}
		if (function_exists('pm_ensure_cancel_request_schema')) {
			pm_ensure_cancel_request_schema($db_Project);
		}
		$q = @mysqli_query(
			$db_Project,
			"SELECT * FROM project_cancel_request
			 WHERE project_id='" . $pid . "' AND status='1'
			 ORDER BY id DESC LIMIT 1"
		);
		$row = $q ? mysqli_fetch_assoc($q) : null;
		return is_array($row) ? $row : null;
	}
}

if (!function_exists('pm_project_is_cancelled')) {
	function pm_project_is_cancelled($db_Project, int $projectId, $preRow = null): bool
	{
		$pid = (int)$projectId;
		if ($pid <= 0) {
			return false;
		}
		if (is_array($preRow) && trim((string)($preRow['cancelled_at'] ?? '')) !== '') {
			return true;
		}
		if (!$db_Project) {
			return false;
		}
		if (function_exists('pm_ensure_cancel_request_schema')) {
			pm_ensure_cancel_request_schema($db_Project);
		}
		if (is_array($preRow) && array_key_exists('cancelled_at', $preRow)) {
			return trim((string)$preRow['cancelled_at']) !== '';
		}
		$q = @mysqli_query($db_Project, "SELECT cancelled_at FROM pre_project WHERE id='" . $pid . "' LIMIT 1");
		$r = $q ? mysqli_fetch_assoc($q) : null;
		if ($r && trim((string)($r['cancelled_at'] ?? '')) !== '') {
			return true;
		}
		$req = function_exists('pm_load_project_cancel_request') ? pm_load_project_cancel_request($db_Project, $pid) : null;
		return is_array($req) && (string)($req['req_status'] ?? '') === 'approved';
	}
}

if (!function_exists('pm_load_cancel_status_maps')) {
	function pm_load_cancel_status_maps($db_Project, array $projectIds): array
	{
		$out = ['pending' => [], 'cancelled' => []];
		if (!$db_Project || empty($projectIds)) {
			return $out;
		}
		$ids = [];
		foreach ($projectIds as $id) {
			$id = (int)$id;
			if ($id > 0) {
				$ids[$id] = $id;
			}
		}
		if (empty($ids)) {
			return $out;
		}
		if (function_exists('pm_ensure_cancel_request_schema')) {
			pm_ensure_cancel_request_schema($db_Project);
		}
		$in = implode(',', $ids);
		$qPre = @mysqli_query($db_Project, "SELECT id FROM pre_project WHERE id IN (" . $in . ") AND IFNULL(cancelled_at,'') <> ''");
		while ($qPre && ($r = mysqli_fetch_assoc($qPre))) {
			$pid = (int)($r['id'] ?? 0);
			if ($pid > 0) {
				$out['cancelled'][$pid] = true;
			}
		}
		$q = @mysqli_query(
			$db_Project,
			"SELECT project_id, req_status FROM project_cancel_request
			 WHERE status='1' AND project_id IN (" . $in . ")
			 ORDER BY id DESC"
		);
		$seen = [];
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$pid = (int)($r['project_id'] ?? 0);
			if ($pid <= 0 || isset($seen[$pid])) {
				continue;
			}
			$seen[$pid] = true;
			$st = (string)($r['req_status'] ?? '');
			if ($st === 'approved') {
				$out['cancelled'][$pid] = true;
			} elseif ($st === 'pending') {
				$out['pending'][$pid] = true;
			}
		}
		return $out;
	}
}

if (!function_exists('pm_save_cancel_request_files')) {
	function pm_save_cancel_request_files($db_Project, int $requestId, int $staffId, string $field = 'cancel_files'): array
	{
		$out = ['ok' => 1, 'msg' => '', 'saved' => 0];
		$requestId = (int)$requestId;
		if ($requestId <= 0 || !$db_Project) {
			return $out;
		}
		$fileNames = $_FILES[$field]['name'] ?? null;
		if (!is_array($fileNames) || empty($fileNames)) {
			return $out;
		}
		$uploadDir = realpath(__DIR__ . '/../document/project_cancel_files');
		if ($uploadDir === false) {
			$uploadDir = __DIR__ . '/../document/project_cancel_files';
			if (!is_dir($uploadDir)) {
				@mkdir($uploadDir, 0775, true);
			}
			$uploadDir = realpath($uploadDir);
		}
		$hasNamed = false;
		foreach ($fileNames as $fn) {
			if (trim((string)$fn) !== '') {
				$hasNamed = true;
				break;
			}
		}
		if (!$hasNamed) {
			return $out;
		}
		if (!$uploadDir || !is_dir($uploadDir) || !is_writable($uploadDir)) {
			$out['ok'] = 0;
			$out['msg'] = 'ไม่สามารถบันทึกไฟล์แนบได้';
			return $out;
		}
		$esc = static function ($v) use ($db_Project) {
			return mysqli_real_escape_string($db_Project, (string)$v);
		};
		$maxBytes = 15 * 1024 * 1024;
		for ($i = 0; $i < count($fileNames); $i++) {
			$name = isset($_FILES[$field]['name'][$i]) ? (string)$_FILES[$field]['name'][$i] : '';
			$tmp = $_FILES[$field]['tmp_name'][$i] ?? '';
			$err = (int)($_FILES[$field]['error'][$i] ?? UPLOAD_ERR_NO_FILE);
			$size = (int)($_FILES[$field]['size'][$i] ?? 0);
			if ($name === '' || $err === UPLOAD_ERR_NO_FILE) {
				continue;
			}
			if ($err !== UPLOAD_ERR_OK || $size <= 0 || $size > $maxBytes) {
				continue;
			}
			$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			if ($ext !== 'pdf') {
				$out['ok'] = 0;
				$out['msg'] = 'เอกสารแนบต้องเป็นไฟล์ PDF เท่านั้น';
				return $out;
			}
			$stored = $requestId . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.pdf';
			$dest = $uploadDir . DIRECTORY_SEPARATOR . $stored;
			if (!@move_uploaded_file((string)$tmp, $dest)) {
				continue;
			}
			@mysqli_query(
				$db_Project,
				"INSERT INTO project_cancel_request_file (request_id, original_name, stored_file, staff_insert, created_at, status)
				 VALUES ('" . $requestId . "', '" . $esc($name) . "', '" . $esc($stored) . "', '" . (int)$staffId . "', NOW(), '1')"
			);
			$out['saved']++;
		}
		return $out;
	}
}

if (!function_exists('pm_ensure_midyear_schema')) {
	function pm_ensure_midyear_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$cols = [
			'midyear_request' => "TINYINT UNSIGNED NOT NULL DEFAULT 0",
			'midyear_reason' => "MEDIUMTEXT NULL",
		];
		foreach ($cols as $col => $def) {
			$chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE '" . mysqli_real_escape_string($db_Project, $col) . "'");
			if (!$chk || mysqli_num_rows($chk) === 0) {
				@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN `" . $col . "` " . $def);
			}
		}
	}
}

if (!function_exists('pm_project_is_midyear')) {
	function pm_project_is_midyear($preRow): bool
	{
		return is_array($preRow) && (int)($preRow['midyear_request'] ?? 0) === 1;
	}
}

if (!function_exists('pm_ensure_admin_tags_schema')) {
	function pm_ensure_admin_tags_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'admin_tags'");
		if (!$chk || mysqli_num_rows($chk) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN `admin_tags` MEDIUMTEXT NULL");
		}
	}
}

if (!function_exists('pm_ensure_general_note_schema')) {
	function pm_ensure_general_note_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'general_note'");
		if (!$chk || mysqli_num_rows($chk) === 0) {
			@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN `general_note` MEDIUMTEXT NULL");
		}
	}
}

if (!function_exists('pm_parse_admin_tags')) {
	function pm_parse_admin_tags($raw): array
	{
		$out = [];
		if (is_array($raw)) {
			$items = $raw;
		} else {
			$txt = trim((string)$raw);
			if ($txt === '') {
				return [];
			}
			$decoded = json_decode($txt, true);
			if (is_array($decoded)) {
				$items = $decoded;
			} else {
				$items = preg_split('/[,;\n]+/u', $txt) ?: [];
			}
		}
		$seen = [];
		foreach ($items as $item) {
			$tag = trim(preg_replace('/\s+/u', ' ', (string)$item));
			if ($tag === '') {
				continue;
			}
			$key = function_exists('mb_strtolower') ? mb_strtolower($tag, 'UTF-8') : strtolower($tag);
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$out[] = $tag;
		}
		return $out;
	}
}

if (!function_exists('pm_admin_tags_to_storage')) {
	function pm_admin_tags_to_storage(array $tags): string
	{
		$norm = function_exists('pm_parse_admin_tags') ? pm_parse_admin_tags($tags) : $tags;
		return json_encode(array_values($norm), JSON_UNESCAPED_UNICODE);
	}
}

if (!function_exists('pm_admin_tags_search_text')) {
	function pm_admin_tags_search_text($raw): string
	{
		$tags = function_exists('pm_parse_admin_tags') ? pm_parse_admin_tags($raw) : [];
		return implode(' ', $tags);
	}
}

if (!function_exists('pm_ensure_approve_memo_schema')) {
	function pm_ensure_approve_memo_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$cols = [
			'approve_memo_dept' => "VARCHAR(500) NULL",
			'approve_memo_date' => "VARCHAR(255) NULL",
			'approve_memo_esign_ref' => "VARCHAR(160) NULL",
			'approve_memo_esign_at' => "DATETIME NULL DEFAULT NULL",
		];
		foreach ($cols as $col => $def) {
			$chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE '" . mysqli_real_escape_string($db_Project, $col) . "'");
			if (!$chk || mysqli_num_rows($chk) === 0) {
				@mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN `" . $col . "` " . $def);
			}
		}
	}
}

if (!function_exists('pm_approve_memo_esign_footer_text')) {
	function pm_approve_memo_esign_footer_text(string $ref): string
	{
		$ref = trim($ref);
		return 'เอกสารฉบับนี้ใช้ลายมือชื่ออิเล็กทรอนิกส์ ตามพระราชบัญญัติว่าด้วยธุรกรรมทางอิเล็กทรอนิกส์ พ.ศ.2544 และข้อบังคับมหาวิทยาลัยเชียงใหม่ ว่าด้วยการให้ใช้ลายมือชื่ออิเล็กทรอนิกส์ พ.ศ.2564 ในระบบ PlanWise ตามรหัสอ้างอิงเลขที่ : ' . ($ref !== '' ? $ref : '-');
	}
}

if (!function_exists('pm_get_or_create_approve_memo_esign_ref')) {
	function pm_get_or_create_approve_memo_esign_ref($db_Project, int $project_id, string $projectCode = '', string $memoType = 'operate', int $request_id = 0): string
	{
		if (!($db_Project instanceof mysqli) || $project_id <= 0) {
			return '';
		}
		$memoType = ($memoType === 'change') ? 'change' : 'operate';
		if ($memoType === 'change') {
			if (function_exists('pm_ensure_change_request_schema')) {
				pm_ensure_change_request_schema($db_Project);
			}
			if ($request_id <= 0) {
				return '';
			}
			$q = @mysqli_query(
				$db_Project,
				"SELECT IFNULL(approve_memo_esign_ref,'') AS r FROM project_change_request
				 WHERE id='" . $request_id . "' AND project_id='" . $project_id . "' AND status='1' LIMIT 1"
			);
			$row = $q ? mysqli_fetch_assoc($q) : null;
			$exist = trim((string)($row['r'] ?? ''));
			if ($exist !== '') {
				return $exist;
			}
		} else {
			if (function_exists('pm_ensure_approve_memo_schema')) {
				pm_ensure_approve_memo_schema($db_Project);
			}
			$q = @mysqli_query(
				$db_Project,
				"SELECT IFNULL(approve_memo_esign_ref,'') AS r FROM pre_project
				 WHERE id='" . $project_id . "' AND IFNULL(status,'') NOT IN ('99','99.0') LIMIT 1"
			);
			$row = $q ? mysqli_fetch_assoc($q) : null;
			$exist = trim((string)($row['r'] ?? ''));
			if ($exist !== '') {
				return $exist;
			}
		}
		$code = strtoupper(trim(preg_replace('/[^A-Za-z0-9\-]/', '', $projectCode)));
		if ($code === '') {
			$code = 'P' . $project_id;
		}
		try {
			$token = strtoupper(bin2hex(random_bytes(4)));
		} catch (Throwable $e) {
			$token = strtoupper(substr(md5(uniqid((string)$project_id, true)), 0, 8));
		}
		$now = time();
		$beDate = (string)(((int)date('Y', $now)) + 543) . date('md', $now);
		$tm = date('His', $now);
		$ref = $token . '-' . $code . '-' . $beDate . '-' . $tm;
		$refEsc = mysqli_real_escape_string($db_Project, $ref);
		if ($memoType === 'change') {
			@mysqli_query(
				$db_Project,
				"UPDATE project_change_request
				 SET approve_memo_esign_ref='" . $refEsc . "', approve_memo_esign_at=NOW()
				 WHERE id='" . $request_id . "' AND project_id='" . $project_id . "'
				   AND IFNULL(approve_memo_esign_ref,'')='' LIMIT 1"
			);
			$q2 = @mysqli_query(
				$db_Project,
				"SELECT IFNULL(approve_memo_esign_ref,'') AS r FROM project_change_request
				 WHERE id='" . $request_id . "' LIMIT 1"
			);
		} else {
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project
				 SET approve_memo_esign_ref='" . $refEsc . "', approve_memo_esign_at=NOW()
				 WHERE id='" . $project_id . "'
				   AND IFNULL(approve_memo_esign_ref,'')='' LIMIT 1"
			);
			$q2 = @mysqli_query(
				$db_Project,
				"SELECT IFNULL(approve_memo_esign_ref,'') AS r FROM pre_project
				 WHERE id='" . $project_id . "' LIMIT 1"
			);
		}
		$saved = $q2 ? mysqli_fetch_assoc($q2) : null;
		$out = trim((string)($saved['r'] ?? ''));
		return $out !== '' ? $out : $ref;
	}
}

/**
 * หาโครงการจากรหัส project_code (code_pre_project)
 * @return array|null แถว pre_project
 */
if (!function_exists('pm_find_pre_project_by_code')) {
	function pm_find_pre_project_by_code($db_Project, string $projectCode): ?array
	{
		$code = trim($projectCode);
		if ($code === '' || !($db_Project instanceof mysqli)) {
			return null;
		}
		$codeEsc = mysqli_real_escape_string($db_Project, $code);
		$q = @mysqli_query(
			$db_Project,
			"SELECT * FROM pre_project
			 WHERE TRIM(CAST(IFNULL(code_pre_project,'') AS CHAR)) = '{$codeEsc}'
			   AND IFNULL(status,'') NOT IN ('99','99.0')
			 ORDER BY id DESC
			 LIMIT 1"
		);
		$row = $q ? mysqli_fetch_assoc($q) : null;
		return is_array($row) ? $row : null;
	}
}

/**
 * สร้างชุดข้อมูลบันทึกอนุมัติโครงการจากฐานข้อมูล (สำหรับ PDF / API)
 *
 * @return array{ok:int,msg?:string,data?:array<string,mixed>}
 */
if (!function_exists('pm_build_approve_memo_payload_from_db')) {
	function pm_build_approve_memo_payload_from_db($db_Project, $db_person, $project_id, ?array $pre = null): array
	{
		$project_id = (int)$project_id;
		if ($project_id <= 0 || !($db_Project instanceof mysqli)) {
			return ['ok' => 0, 'msg' => 'ไม่พบโครงการ'];
		}
		if (!is_array($pre)) {
			$qPre = @mysqli_query(
				$db_Project,
				"SELECT * FROM pre_project WHERE id='" . $project_id . "' AND IFNULL(status,'') NOT IN ('99','99.0') LIMIT 1"
			);
			$pre = $qPre ? mysqli_fetch_assoc($qPre) : null;
		}
		if (!is_array($pre)) {
			return ['ok' => 0, 'msg' => 'ไม่พบโครงการ'];
		}
		if (function_exists('pm_ensure_operate_approver_schema')) {
			pm_ensure_operate_approver_schema($db_Project);
		}
		if (function_exists('pm_ensure_approve_memo_schema')) {
			pm_ensure_approve_memo_schema($db_Project);
		}
		$ready = function_exists('pm_is_operate_final_approved')
			? pm_is_operate_final_approved($db_Project, $project_id, $pre)
			: ((string)($pre['status_project'] ?? '') === '1');
		if (!$ready) {
			return ['ok' => 0, 'msg' => 'โครงการยังไม่พร้อมออกบันทึกอนุมัติ'];
		}

		$fmtThaiLong = static function ($ts) {
			if ($ts === false || $ts <= 0) {
				return '';
			}
			$m = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
			$d = (int)date('j', $ts);
			$mi = (int)date('n', $ts);
			$y = (int)date('Y', $ts) + 543;
			return $d . ' ' . $m[max(0, min(11, $mi - 1))] . ' ' . $y;
		};

		$code = trim((string)($pre['code_pre_project'] ?? ''));
		if ($code === '') {
			$code = 'P' . $project_id;
		}
		$name = trim((string)($pre['name_project'] ?? ''));
		$year = trim((string)($pre['year_project'] ?? ''));
		if ($year !== '' && ctype_digit($year) && (int)$year > 0 && (int)$year < 2400) {
			$year = (string)((int)$year + 543);
		}

		$unit = '-';
		$paperName = 'อว';
		$paperStart = 'ผ001';
		$codeUnit = trim((string)($pre['code_unit'] ?? ''));
		if ($codeUnit !== '') {
			$ucEsc = mysqli_real_escape_string($db_Project, $codeUnit);
			$hasPaperName = false;
			$hasPaperStart = false;
			$qColsU = @mysqli_query($db_Project, 'SHOW COLUMNS FROM set_unit');
			while ($qColsU && ($cu = mysqli_fetch_assoc($qColsU))) {
				$fn = strtolower(trim((string)($cu['Field'] ?? '')));
				if ($fn === 'paper_name') {
					$hasPaperName = true;
				}
				if ($fn === 'paper_start') {
					$hasPaperStart = true;
				}
			}
			$selPaper = 'name';
			if ($hasPaperName) {
				$selPaper .= ', paper_name';
			}
			if ($hasPaperStart) {
				$selPaper .= ', paper_start';
			}
			$qPaper = @mysqli_query(
				$db_Project,
				"SELECT {$selPaper} FROM set_unit
				 WHERE (CAST(id AS CHAR)='{$ucEsc}' OR TRIM(CAST(IFNULL(code,'') AS CHAR))='{$ucEsc}')
				   AND IFNULL(status,'') NOT IN ('99','99.0')
				 LIMIT 1"
			);
			$rPaper = $qPaper ? mysqli_fetch_assoc($qPaper) : null;
			if ($rPaper) {
				$unit = trim((string)($rPaper['name'] ?? '')) ?: $unit;
				if ($hasPaperName) {
					$pn = trim((string)($rPaper['paper_name'] ?? ''));
					if ($pn !== '') {
						$paperName = $pn;
					}
				}
				if ($hasPaperStart) {
					$ps = trim((string)($rPaper['paper_start'] ?? ''));
					if ($ps !== '') {
						$paperStart = $ps;
					}
				}
			}
		}
		$dept = ($unit !== '-' && $unit !== '' ? $unit : 'หน่วยงาน') . ' คณะเภสัชศาสตร์ โทร.44344';
		$savedDept = trim((string)($pre['approve_memo_dept'] ?? ''));
		if ($savedDept !== '') {
			$dept = $savedDept;
		}
		$docNo = function_exists('pm_unit_paper_doc_no')
			? pm_unit_paper_doc_no($paperName, $paperStart, ((($project_id % 999) ?: 0) + 1))
			: (rtrim($paperName, " \t/") . '/' . ltrim($paperStart, " \t/"));

		$amt = isset($pre['notify_approved_budget']) ? (float)$pre['notify_approved_budget'] : 0.0;
		if ($amt <= 0) {
			$qSum = @mysqli_query(
				$db_Project,
				"SELECT IFNULL(SUM(pa.total),0) AS t FROM project_activity_budget pa
				 WHERE pa.code_pre_project='" . $project_id . "' AND pa.status!='99'"
			);
			$rSum = $qSum ? mysqli_fetch_assoc($qSum) : null;
			$amt = (float)($rSum['t'] ?? 0);
		}
		$budget = number_format($amt, 0, '.', ',');
		$budgetSrc = function_exists('resolveProjectBudgetSourceForDisplay')
			? resolveProjectBudgetSourceForDisplay($db_Project, $project_id)
			: '-';

		$date = $fmtThaiLong(time());
		$savedDate = trim((string)($pre['approve_memo_date'] ?? ''));
		if ($savedDate !== '') {
			$date = $savedDate;
		} else {
			$qReqAt = @mysqli_query(
				$db_Project,
				"SELECT created_at FROM project_plan_review_log
				 WHERE project_id='" . $project_id . "'
				   AND action_type IN ('request_operate','request_operate_project')
				 ORDER BY id DESC LIMIT 1"
			);
			$rReqAt = $qReqAt ? mysqli_fetch_assoc($qReqAt) : null;
			if ($rReqAt && trim((string)($rReqAt['created_at'] ?? '')) !== '') {
				$tsReq = strtotime((string)$rReqAt['created_at']);
				if ($tsReq) {
					$date = $fmtThaiLong($tsReq);
				}
			} elseif (!empty($pre['dates'])) {
				$tsDates = strtotime((string)$pre['dates']);
				if ($tsDates) {
					$date = $fmtThaiLong($tsDates);
				}
			}
		}

		// ผู้เสนอ = หัวหน้าโครงการ
		$proposerName = '';
		$proposerPos = 'หัวหน้าโครงการ';
		$proposerSid = 0;
		$proposerKey = '';
		$heads = function_exists('getProjectHeadList') ? getProjectHeadList($db_Project, $project_id) : [];
		if (!empty($heads) && is_array($heads[0] ?? null)) {
			$h0 = $heads[0];
			$proposerKey = trim((string)($h0['staff_project'] ?? $h0['staff_id'] ?? ''));
			$proposerSid = (int)$proposerKey;
		}
		if ($proposerKey !== '' && ($db_person instanceof mysqli)) {
			$keyEsc = mysqli_real_escape_string($db_person, $proposerKey);
			$qHn = @mysqli_query(
				$db_person,
				"SELECT id, files_sig, position_code,
				        CONCAT(TRIM(IFNULL(fname,'')),' ',TRIM(IFNULL(lname,''))) AS n
				 FROM staff
				 WHERE id='" . (int)$proposerKey . "'
				    OR TRIM(CAST(IFNULL(code,'') AS CHAR))='" . $keyEsc . "'
				 LIMIT 1"
			);
			$rHn = $qHn ? mysqli_fetch_assoc($qHn) : null;
			if ($rHn) {
				$proposerSid = (int)($rHn['id'] ?? $proposerSid);
				$proposerName = trim((string)($rHn['n'] ?? ''));
				$pc = trim((string)($rHn['position_code'] ?? ''));
				if ($pc !== '' && function_exists('getPosition')) {
					$rp = getPosition($db_person, $pc);
					if (is_array($rp) && trim((string)($rp['name'] ?? '')) !== '') {
						$proposerPos = trim((string)$rp['name']);
					}
				}
			}
		}
		$proposerDate = $date;

		// ผู้อนุมัติล่าสุดจาก decision
		$approverName = '';
		$approverPos = 'คณบดี';
		$approverDate = $date;
		$approverSid = 0;
		$approverDateRaw = '';
		$useApproverTable = trim((string)($pre['operate_approvers_json'] ?? '')) !== '';
		$decTable = $useApproverTable ? 'project_operate_approver_decision' : 'project_operate_approver_decision';
		// ถ้ามี operate_approvers_json ใช้ตาราง approver; ไม่งั้นลองทั้งสองตาราง
		$tryTables = $useApproverTable
			? ['project_operate_approver_decision']
			: ['project_operate_approver_decision', 'project_operate_certifier_decision'];
		foreach ($tryTables as $tbl) {
			$qDec = @mysqli_query(
				$db_Project,
				"SELECT sort_order, staff_id, decided_by_staff_id, decision, decided_at
				 FROM `{$tbl}`
				 WHERE project_id='" . $project_id . "'
				   AND LOWER(TRIM(IFNULL(decision,''))) IN ('approve','approved','1')
				 ORDER BY sort_order DESC, id DESC
				 LIMIT 1"
			);
			$rDec = $qDec ? mysqli_fetch_assoc($qDec) : null;
			if ($rDec) {
				$approverSid = (int)($rDec['decided_by_staff_id'] ?? 0);
				if ($approverSid <= 0) {
					$approverSid = (int)($rDec['staff_id'] ?? 0);
				}
				$approverDateRaw = trim((string)($rDec['decided_at'] ?? ''));
				break;
			}
		}
		if ($approverDateRaw !== '') {
			$tsAp = strtotime($approverDateRaw);
			if ($tsAp) {
				$approverDate = $fmtThaiLong($tsAp);
			}
		}
		if ($approverSid > 0 && ($db_person instanceof mysqli)) {
			$qAp = @mysqli_query(
				$db_person,
				"SELECT id, files_sig, position_dean, position_code,
				        CONCAT(TRIM(IFNULL(fname,'')),' ',TRIM(IFNULL(lname,''))) AS n
				 FROM staff WHERE id='" . $approverSid . "' LIMIT 1"
			);
			$rAp = $qAp ? mysqli_fetch_assoc($qAp) : null;
			if ($rAp) {
				$approverName = trim((string)($rAp['n'] ?? ''));
				$pd = trim((string)($rAp['position_dean'] ?? ''));
				if ($pd !== '' && function_exists('getPositionDean')) {
					$rpd = getPositionDean($db_person, $pd);
					if (is_array($rpd) && trim((string)($rpd['name'] ?? '')) !== '') {
						$approverPos = trim((string)$rpd['name']);
					}
				}
			}
		}

		$pathSigBase = isset($GLOBALS['path_sig']) ? rtrim((string)$GLOBALS['path_sig'], '/') : '';
		if ($pathSigBase === '' && !empty($GLOBALS['path_picture'])) {
			$pathSigBase = preg_replace('#/files_person$#', '/files_sig', rtrim((string)$GLOBALS['path_picture'], '/'));
		}
		if ($pathSigBase === '') {
			$pathSigBase = 'https://backoffice.pharmacy.cmu.ac.th/person/files_sig';
		}
		$buildSigUrl = static function ($file, $base) {
			$file = trim((string)$file);
			$base = rtrim((string)$base, '/');
			if ($file === '' || $base === '') {
				return '';
			}
			if (preg_match('#^https?://#i', $file)) {
				return $file;
			}
			$file = preg_replace('#^files_sig/#i', '', $file);
			return $base . '/' . ltrim($file, '/');
		};
		$resolveSig = static function ($db_person, $staffId) use ($pathSigBase, $buildSigUrl) {
			$staffId = (int)$staffId;
			if ($staffId <= 0 || !($db_person instanceof mysqli)) {
				return '';
			}
			$q = @mysqli_query($db_person, "SELECT files_sig FROM staff WHERE id='" . $staffId . "' LIMIT 1");
			$r = $q ? mysqli_fetch_assoc($q) : null;
			$file = trim((string)($r['files_sig'] ?? ''));
			return $buildSigUrl($file, $pathSigBase);
		};
		$proposerSig = $resolveSig($db_person, $proposerSid);
		$approverSig = $resolveSig($db_person, $approverSid);

		return [
			'ok' => 1,
			'data' => [
				'project_id' => $project_id,
				'memo_type' => 'operate',
				'dept' => $dept,
				'doc_no' => $docNo,
				'date' => $date,
				'unit' => ($unit !== '-' && $unit !== '') ? $unit : 'หน่วยงาน',
				'code' => $code,
				'name' => $name,
				'year' => $year,
				'budget' => $budget,
				'budget_src' => $budgetSrc !== '' ? $budgetSrc : '-',
				'proposer_name' => $proposerName,
				'proposer_pos' => $proposerPos,
				'proposer_date' => $proposerDate,
				'proposer_sig' => $proposerSig,
				'approver_name' => $approverName,
				'approver_pos' => $approverPos,
				'approver_date' => $approverDate,
				'approver_sig' => $approverSig,
			],
		];
	}
}

if (!function_exists('pm_change_active_req_statuses')) {
	function pm_change_active_req_statuses(): array
	{
		return ['pending', 'waiting_plan', 'pending_approve'];
	}
}

if (!function_exists('pm_project_pending_change_request_id')) {
	function pm_project_pending_change_request_id($db_Project, int $projectId): int
	{
		$pid = (int)$projectId;
		if ($pid <= 0 || !$db_Project) {
			return 0;
		}
		if (function_exists('pm_ensure_change_request_schema')) {
			pm_ensure_change_request_schema($db_Project);
		}
		$stIn = "'pending','waiting_plan','pending_approve'";
		if (function_exists('pm_change_active_req_statuses')) {
			$stIn = "'" . implode("','", array_map('strval', pm_change_active_req_statuses())) . "'";
		}
		$q = @mysqli_query(
			$db_Project,
			"SELECT id FROM project_change_request
			 WHERE project_id='" . $pid . "' AND status='1' AND req_status IN (" . $stIn . ")
			 ORDER BY id DESC LIMIT 1"
		);
		$row = $q ? mysqli_fetch_assoc($q) : null;
		return (int)($row['id'] ?? 0);
	}
}

if (!function_exists('pm_load_pending_change_project_ids')) {
	function pm_load_pending_change_project_ids($db_Project, array $projectIds): array
	{
		$out = [];
		if (!$db_Project || empty($projectIds)) {
			return $out;
		}
		$ids = [];
		foreach ($projectIds as $id) {
			$id = (int)$id;
			if ($id > 0) {
				$ids[$id] = $id;
			}
		}
		if (empty($ids)) {
			return $out;
		}
		if (function_exists('pm_ensure_change_request_schema')) {
			pm_ensure_change_request_schema($db_Project);
		}
		$in = implode(',', $ids);
		$stIn = "'pending','waiting_plan','pending_approve'";
		if (function_exists('pm_change_active_req_statuses')) {
			$stIn = "'" . implode("','", array_map('strval', pm_change_active_req_statuses())) . "'";
		}
		$q = @mysqli_query(
			$db_Project,
			"SELECT project_id FROM project_change_request
			 WHERE status='1' AND req_status IN (" . $stIn . ") AND project_id IN (" . $in . ")"
		);
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$pid = (int)($r['project_id'] ?? 0);
			if ($pid > 0) {
				$out[$pid] = true;
			}
		}
		return $out;
	}
}

if (!function_exists('pm_change_thai_dt')) {
	function pm_change_thai_dt($dt): string
	{
		$dt = trim((string)$dt);
		if ($dt === '') {
			return '';
		}
		$ts = strtotime($dt);
		if ($ts === false) {
			return $dt;
		}
		$m = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
		$d = (int)date('j', $ts);
		$mi = (int)date('n', $ts);
		$y = ((int)date('Y', $ts) + 543) % 100;
		$t = date('H:i', $ts);
		return $d . ' ' . $m[max(0, min(11, $mi - 1))] . ' ' . str_pad((string)$y, 2, '0', STR_PAD_LEFT) . ' ' . $t . ' น.';
	}
}

if (!function_exists('pm_load_project_change_requests')) {
	function pm_load_project_change_requests($db_Project, int $projectId): array
	{
		$pid = (int)$projectId;
		if ($pid <= 0 || !$db_Project) {
			return [];
		}
		if (function_exists('pm_ensure_change_request_schema')) {
			pm_ensure_change_request_schema($db_Project);
		}
		$out = [];
		$q = @mysqli_query(
			$db_Project,
			"SELECT id, project_id, reason, details_json, req_status, staff_insert, created_at,
			        project_name, project_code, budget_source, budget_amount, approvers_json,
			        applied_at, applied_by_staff_id, apply_note, plan_rechain_staff_id
			 FROM project_change_request
			 WHERE project_id='" . $pid . "' AND status='1'
			 ORDER BY id ASC"
		);
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$details = json_decode((string)($r['details_json'] ?? ''), true);
			$r['details'] = is_array($details) ? $details : [];
			$out[] = $r;
		}
		return $out;
	}
}

if (!function_exists('pm_load_unapplied_change_project_ids')) {
	function pm_load_unapplied_change_project_ids($db_Project, array $projectIds): array
	{
		$out = [];
		if (!$db_Project || empty($projectIds)) {
			return $out;
		}
		$ids = [];
		foreach ($projectIds as $id) {
			$id = (int)$id;
			if ($id > 0) {
				$ids[$id] = $id;
			}
		}
		if (empty($ids)) {
			return $out;
		}
		if (function_exists('pm_ensure_change_request_schema')) {
			pm_ensure_change_request_schema($db_Project);
		}
		$in = implode(',', $ids);
		$q = @mysqli_query(
			$db_Project,
			"SELECT project_id FROM project_change_request
			 WHERE status='1' AND req_status='approved'
			   AND (applied_at IS NULL OR TRIM(IFNULL(applied_at,''))='')
			   AND project_id IN (" . $in . ")"
		);
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$pid = (int)($r['project_id'] ?? 0);
			if ($pid > 0) {
				$out[$pid] = true;
			}
		}
		return $out;
	}
}

if (!function_exists('pm_load_operate_decision_map')) {
	function pm_load_operate_decision_map($db_Project, int $projectId, string $table = 'project_operate_certifier_decision'): array
	{
		$pid = (int)$projectId;
		$allowed = [
			'project_operate_certifier_decision' => true,
			'project_operate_approver_decision' => true,
		];
		if ($pid <= 0 || !$db_Project || empty($allowed[$table])) {
			return [];
		}
		$decMap = [];
		$qOD = @mysqli_query(
			$db_Project,
			"SELECT sort_order, staff_id, decided_by_staff_id, decision, certifier_note, decided_at
			 FROM `" . $table . "`
			 WHERE project_id='" . $pid . "'
			 ORDER BY sort_order ASC"
		);
		while ($qOD && ($od = mysqli_fetch_assoc($qOD))) {
			$so = (int)($od['sort_order'] ?? 0);
			if ($so > 0) {
				$decMap[$so] = $od;
			}
		}
		return $decMap;
	}
}

if (!function_exists('pm_compute_operate_map_all_approved')) {
	function pm_compute_operate_map_all_approved(array $chainMap, array $decMap): bool
	{
		$required = array_keys($chainMap);
		sort($required, SORT_NUMERIC);
		if (empty($required)) {
			return false;
		}
		foreach ($required as $soReq) {
			if ((string)($decMap[$soReq]['decision'] ?? '') !== 'approved') {
				return false;
			}
		}
		return true;
	}
}

if (!function_exists('pm_is_operate_returned_partial')) {
	/** ตีกลับหลังขอดำเนินการของแผนประจำปี — แก้ได้บางข้อเหมือนก่อนขอดำเนินการ (ยังไม่ส่งกลับผู้รับรอง) */
	function pm_is_operate_returned_partial($status_project, $notify_result = '', $reply_note = ''): bool
	{
		if (trim((string)$reply_note) !== '') {
			return false;
		}
		return ((string)$status_project === '8' && trim((string)$notify_result) === 'บรรจุแผน');
	}
}

if (!function_exists('pm_ab_count_chain_progress')) {
	/** นับจำนวน approved / ทั้งหมด จากสาย JSON + decision map */
	function pm_ab_count_chain_progress($json, array $decMap): array
	{
		$chain = function_exists('pm_parse_operate_chain_json') ? pm_parse_operate_chain_json($json) : [];
		$total = count($chain);
		$done = 0;
		foreach ($chain as $so => $_) {
			if ((string)($decMap[(int)$so]['decision'] ?? '') === 'approved') {
				$done++;
			}
		}
		return ['done' => $done, 'total' => $total];
	}
}

if (!function_exists('pm_ab_include_cert_progress')) {
	function pm_ab_include_cert_progress($db_Project, int $projectId): array
	{
		$empty = ['done' => 0, 'total' => 0, 'all_approved' => false];
		$pid = (int)$projectId;
		if ($pid <= 0 || !$db_Project) {
			return $empty;
		}
		$qRid = @mysqli_query(
			$db_Project,
			"SELECT MAX(request_id) AS rid FROM project_include_request_item WHERE project_id='" . $pid . "'"
		);
		$rRid = $qRid ? mysqli_fetch_assoc($qRid) : null;
		$rid = (int)($rRid['rid'] ?? 0);
		if ($rid <= 0) {
			return $empty;
		}
		$total = 0;
		$qT = @mysqli_query($db_Project, "SELECT COUNT(*) AS c FROM project_include_request_certifier WHERE request_id='" . $rid . "'");
		$rT = $qT ? mysqli_fetch_assoc($qT) : null;
		$total = (int)($rT['c'] ?? 0);
		$done = 0;
		$qD = @mysqli_query(
			$db_Project,
			"SELECT COUNT(*) AS c FROM project_include_request_certifier_decision
			 WHERE request_id='" . $rid . "' AND decision='approved'"
		);
		$rD = $qD ? mysqli_fetch_assoc($qD) : null;
		$done = (int)($rD['c'] ?? 0);
		return [
			'done' => $done,
			'total' => $total,
			'all_approved' => ($total > 0 && $done >= $total),
		];
	}
}

if (!function_exists('pm_ab_operate_progress')) {
	function pm_ab_operate_progress($db_Project, $row): array
	{
		$out = [
			'cert_done' => 0,
			'cert_total' => 0,
			'appr_done' => 0,
			'appr_total' => 0,
			'certs_all' => false,
			'apprs_all' => false,
			'waiting_plan' => false,
			'final_approved' => false,
		];
		$pid = (int)($row['id'] ?? 0);
		if ($pid <= 0 || !$db_Project) {
			return $out;
		}
		$certJson = (string)($row['operate_certifiers_json'] ?? '');
		$apprJson = (string)($row['operate_approvers_json'] ?? '');
		$certMap = function_exists('pm_load_operate_decision_map')
			? pm_load_operate_decision_map($db_Project, $pid, 'project_operate_certifier_decision')
			: [];
		$apprMap = function_exists('pm_load_operate_decision_map')
			? pm_load_operate_decision_map($db_Project, $pid, 'project_operate_approver_decision')
			: [];
		$cp = pm_ab_count_chain_progress($certJson, $certMap);
		$out['cert_done'] = (int)$cp['done'];
		$out['cert_total'] = (int)$cp['total'];
		$out['certs_all'] = ($out['cert_total'] > 0 && $out['cert_done'] >= $out['cert_total']);
		if ($apprJson !== '') {
			$ap = pm_ab_count_chain_progress($apprJson, $apprMap);
			$out['appr_done'] = (int)$ap['done'];
			$out['appr_total'] = (int)$ap['total'];
			$out['apprs_all'] = ($out['appr_total'] > 0 && $out['appr_done'] >= $out['appr_total']);
		}
		if (function_exists('pm_is_operate_waiting_plan_approver')) {
			$out['waiting_plan'] = pm_is_operate_waiting_plan_approver($db_Project, $pid, $row);
		}
		if (function_exists('pm_is_operate_final_approved')) {
			$out['final_approved'] = pm_is_operate_final_approved($db_Project, $pid, $row);
		}
		return $out;
	}
}

if (!function_exists('pm_ab_report_progress')) {
	function pm_ab_report_progress($db_Project, $row): array
	{
		$json = (string)($row['result_report_certifiers_json'] ?? '');
		$pid = (int)($row['id'] ?? 0);
		$decMap = [];
		if ($pid > 0 && $db_Project && $json !== '') {
			$q = @mysqli_query(
				$db_Project,
				"SELECT sort_order, decision FROM project_result_report_certifier_decision WHERE project_id='" . $pid . "'"
			);
			while ($q && ($d = mysqli_fetch_assoc($q))) {
				$so = (int)($d['sort_order'] ?? 0);
				if ($so > 0) {
					$decMap[$so] = $d;
				}
			}
		}
		$p = pm_ab_count_chain_progress($json, $decMap);
		$rejected = false;
		foreach ($decMap as $d) {
			if ((string)($d['decision'] ?? '') === 'rejected') {
				$rejected = true;
				break;
			}
		}
		return [
			'done' => (int)$p['done'],
			'total' => (int)$p['total'],
			'rejected' => $rejected,
		];
	}
}

if (!function_exists('pm_ab_fmt_progress')) {
	function pm_ab_fmt_progress(int $done, int $total): string
	{
		if ($total <= 0) {
			return '';
		}
		return ' : ' . $done . '/' . $total;
	}
}

if (!function_exists('pm_ab_hero_class')) {
	function pm_ab_hero_class(string $mod): string
	{
		$mod = trim($mod);
		if (in_array($mod, ['a_reject', 'rejected', 'cancelled', 'cancel_pending'], true)) {
			return 'pm-hero-st-no';
		}
		if (in_array($mod, ['b3', 'approved', 'included_blue'], true)) {
			return 'pm-hero-st-ok';
		}
		if (in_array($mod, ['b4', 'b4_done', 'b5', 'report_done'], true)) {
			return 'pm-hero-st-b4';
		}
		if (in_array($mod, ['a2', 'b2'], true)) {
			return 'pm-hero-st-a2';
		}
		if (in_array($mod, ['a_fix', 'b_fix', 'returned'], true)) {
			return 'pm-hero-st-fix';
		}
		if ($mod === 'b1') {
			return 'pm-hero-st-wait';
		}
		if ($mod === 'midyear') {
			return 'pm-hero-st-mid';
		}
		return 'pm-hero-st-wait';
	}
}

if (!function_exists('pm_resolve_ab_status')) {
	/**
	 * ชั้นแสดงผล A/B — ไม่เปลี่ยน status_project / notify_result
	 * @return array{code:string,label:string,mod:string,icon:string,group:string,can_edit:bool,progress:string}
	 */
	function pm_resolve_ab_status($row, array $extra = []): array
	{
		$row = is_array($row) ? $row : [];
		$st = (string)($row['status_project'] ?? '');
		$nr = trim((string)($row['notify_result'] ?? ''));
		$pid = (int)($row['id'] ?? ($extra['project_id'] ?? 0));
		$userFix = trim((string)($extra['user_fix_note'] ?? $row['user_fix_note'] ?? ''));
		$opReply = trim((string)($extra['operate_reply_note'] ?? $row['operate_submitter_reply_note'] ?? ''));
		$rrSub = trim((string)($extra['report_submitted_at'] ?? $row['result_report_submitted_at'] ?? ''));
		$rrDone = trim((string)($extra['report_completed_at'] ?? $row['result_report_completed_at'] ?? ''));
		$rrPlanClosed = trim((string)($extra['report_plan_closed_at'] ?? $row['result_report_plan_closed_at'] ?? ''));
		$isMidyear = array_key_exists('midyear', $extra)
			? !empty($extra['midyear'])
			: ((int)($row['midyear_request'] ?? 0) === 1);
		$cancelled = !empty($extra['cancelled']);
		$cancelPending = !empty($extra['cancel_pending']);
		$changePending = !empty($extra['change_pending']);
		$changeWait = !empty($extra['change_wait_apply']);
		$db = $extra['db'] ?? null;

		$base = [
			'code' => '',
			'label' => '-',
			'mod' => 'unknown',
			'icon' => 'fa-minus-circle',
			'group' => '',
			'can_edit' => false,
			'progress' => '',
		];

		$certDone = (int)($extra['cert_done'] ?? -1);
		$certTotal = (int)($extra['cert_total'] ?? -1);
		$apprDone = (int)($extra['appr_done'] ?? -1);
		$apprTotal = (int)($extra['appr_total'] ?? -1);
		$incDone = (int)($extra['include_done'] ?? -1);
		$incTotal = (int)($extra['include_total'] ?? -1);
		$incAll = array_key_exists('include_all_approved', $extra) ? (bool)$extra['include_all_approved'] : null;
		$repDone = (int)($extra['report_done'] ?? -1);
		$repTotal = (int)($extra['report_total'] ?? -1);
		$repRejected = array_key_exists('report_rejected', $extra) ? (bool)$extra['report_rejected'] : null;
		$waitingPlan = array_key_exists('operate_waiting_plan', $extra) ? (bool)$extra['operate_waiting_plan'] : null;
		$finalOk = array_key_exists('operate_all_approved', $extra) ? (bool)$extra['operate_all_approved'] : null;

		if ($db && $pid > 0) {
			if (in_array($st, ['1', '8'], true) && ($certTotal < 0 || $waitingPlan === null || $finalOk === null)) {
				$op = pm_ab_operate_progress($db, array_merge($row, ['id' => $pid]));
				if ($certTotal < 0) {
					$certDone = (int)$op['cert_done'];
					$certTotal = (int)$op['cert_total'];
					$apprDone = (int)$op['appr_done'];
					$apprTotal = (int)$op['appr_total'];
				}
				if ($waitingPlan === null) {
					$waitingPlan = !empty($op['waiting_plan']);
				}
				if ($finalOk === null) {
					$finalOk = !empty($op['final_approved']);
				}
			}
			if (in_array($st, ['3', '4'], true) && $incTotal < 0) {
				$inc = pm_ab_include_cert_progress($db, $pid);
				$incDone = (int)$inc['done'];
				$incTotal = (int)$inc['total'];
				$incAll = !empty($inc['all_approved']);
			}
			if ($rrSub !== '' && ($repTotal < 0 || $repRejected === null)) {
				$rp = pm_ab_report_progress($db, array_merge($row, ['id' => $pid]));
				if ($repTotal < 0) {
					$repDone = (int)$rp['done'];
					$repTotal = (int)$rp['total'];
				}
				if ($repRejected === null) {
					$repRejected = !empty($rp['rejected']);
				}
			}
		}
		$certDone = max(0, $certDone);
		$certTotal = max(0, $certTotal);
		$apprDone = max(0, $apprDone);
		$apprTotal = max(0, $apprTotal);
		$incDone = max(0, $incDone);
		$incTotal = max(0, $incTotal);
		$repDone = max(0, $repDone);
		$repTotal = max(0, $repTotal);
		$waitingPlan = (bool)$waitingPlan;
		$finalOk = (bool)$finalOk;
		$incAll = (bool)$incAll;
		$repRejected = (bool)$repRejected;

		$out = $base;
		if ($st === '0') {
			$out = [
				'code' => 'A0',
				'label' => 'A0 ร่าง',
				'mod' => 'a0',
				'icon' => 'fa-edit',
				'group' => 'A',
				'can_edit' => true,
				'progress' => '',
			];
		} elseif ($st === '2') {
			$out = [
				'code' => 'A1',
				'label' => 'A1 รอส่งตรวจสอบ',
				'mod' => 'a1',
				'icon' => 'fa-paper-plane',
				'group' => 'A',
				'can_edit' => false,
				'progress' => '',
			];
		} elseif ($st === '3') {
			$prog = pm_ab_fmt_progress($incDone, $incTotal);
			$out = [
				'code' => 'A2',
				'label' => 'A2 อยู่ระหว่างตรวจสอบ ผู้ตรวจสอบหน่วยงาน' . $prog,
				'mod' => 'a2',
				'icon' => 'fa-hourglass-half',
				'group' => 'A',
				'can_edit' => false,
				'progress' => $prog,
			];
		} elseif ($st === '4') {
			if ($nr === 'บรรจุแผน') {
				$out = [
					'code' => 'B1',
					'label' => 'B1 รอยื่นอนุมัติดำเนินการ',
					'mod' => 'b1',
					'icon' => 'fa-play-circle',
					'group' => 'B',
					'can_edit' => true,
					'progress' => '',
				];
			} elseif ($nr === 'บรรจุแผนและแก้ไข') {
				$out = [
					'code' => 'A',
					'label' => 'A รอแก้ไข ผู้ตรวจสอบมีข้อแก้ไข',
					'mod' => 'a_fix',
					'icon' => 'fa-undo',
					'group' => 'A',
					'can_edit' => ($userFix === ''),
					'progress' => '',
				];
			} elseif ($incAll || ($incTotal <= 0)) {
				$out = [
					'code' => 'A2',
					'label' => 'A2 อยู่ระหว่างพิจารณา งานแผน',
					'mod' => 'a2',
					'icon' => 'fa-hourglass-half',
					'group' => 'A',
					'can_edit' => false,
					'progress' => '',
				];
			} else {
				$prog = pm_ab_fmt_progress($incDone, $incTotal);
				$out = [
					'code' => 'A2',
					'label' => 'A2 อยู่ระหว่างตรวจสอบ ผู้ตรวจสอบหน่วยงาน' . $prog,
					'mod' => 'a2',
					'icon' => 'fa-hourglass-half',
					'group' => 'A',
					'can_edit' => false,
					'progress' => $prog,
				];
			}
		} elseif ($st === '9') {
			$out = [
				'code' => 'A',
				'label' => 'A ไม่ผ่านการพิจารณา',
				'mod' => 'a_reject',
				'icon' => 'fa-times-circle',
				'group' => 'A',
				'can_edit' => false,
				'progress' => '',
			];
		} elseif ($st === '8') {
			if ($nr === 'บรรจุแผน') {
				if ($opReply !== '') {
					$prog = pm_ab_fmt_progress($certDone, $certTotal);
					$out = [
						'code' => 'B2',
						'label' => 'B2 อยู่ระหว่างตรวจสอบ ผู้ตรวจสอบหน่วยงาน' . $prog,
						'mod' => 'b2',
						'icon' => 'fa-hourglass-half',
						'group' => 'B',
						'can_edit' => false,
						'progress' => $prog,
					];
				} else {
					$out = [
						'code' => 'B',
						'label' => 'B รอแก้ไข ผู้ตรวจสอบมีข้อแก้ไข',
						'mod' => 'b_fix',
						'icon' => 'fa-undo',
						'group' => 'B',
						'can_edit' => true,
						'progress' => '',
					];
				}
			} elseif ($nr === 'บรรจุแผนและแก้ไข' || $nr === 'บรรจุและแก้ไข') {
				if ($userFix !== '') {
					$out = [
						'code' => 'A2',
						'label' => 'A2 อยู่ระหว่างพิจารณา งานแผน',
						'mod' => 'a2',
						'icon' => 'fa-hourglass-half',
						'group' => 'A',
						'can_edit' => false,
						'progress' => '',
					];
				} else {
					$out = [
						'code' => 'A',
						'label' => 'A รอแก้ไข ผู้ตรวจสอบมีข้อแก้ไข',
						'mod' => 'a_fix',
						'icon' => 'fa-undo',
						'group' => 'A',
						'can_edit' => true,
						'progress' => '',
					];
				}
			} else {
				$out = [
					'code' => 'A',
					'label' => 'A รอแก้ไข ผู้ตรวจสอบมีข้อแก้ไข',
					'mod' => 'a_fix',
					'icon' => 'fa-undo',
					'group' => 'A',
					'can_edit' => true,
					'progress' => '',
				];
			}
		} elseif ($st === '1') {
			$reportCertified = ($rrDone !== '')
				|| ($rrSub !== '' && !$repRejected && $repTotal > 0 && $repDone >= $repTotal);
			if ($rrPlanClosed !== '') {
				$out = [
					'code' => 'B5',
					'label' => 'B5 ปิดโครงการ',
					'mod' => 'b5',
					'icon' => 'fa-check-circle',
					'group' => 'B',
					'can_edit' => false,
					'progress' => '',
				];
			} elseif ($reportCertified) {
				$out = [
					'code' => 'B4',
					'label' => 'B4 รายงานผลเรียบร้อย',
					'mod' => 'b4_done',
					'icon' => 'fa-check-circle',
					'group' => 'B',
					'can_edit' => false,
					'progress' => '',
				];
			} elseif ($rrSub !== '' && $repRejected) {
				$out = [
					'code' => 'B',
					'label' => 'B รอแก้ไข ผู้ตรวจสอบมีข้อแก้ไข',
					'mod' => 'b_fix',
					'icon' => 'fa-undo',
					'group' => 'B',
					'can_edit' => true,
					'progress' => '',
				];
			} elseif ($rrSub !== '') {
				$prog = pm_ab_fmt_progress($repDone, $repTotal);
				$out = [
					'code' => 'B4',
					'label' => 'B4 อยู่ระหว่างตรวจรายงาน' . $prog,
					'mod' => 'b4',
					'icon' => 'fa-hourglass-half',
					'group' => 'B',
					'can_edit' => false,
					'progress' => $prog,
				];
			} elseif ($finalOk) {
				$out = [
					'code' => 'B3',
					'label' => 'B3 อนุมัติ/อยู่ระหว่างดำเนินการ',
					'mod' => 'b3',
					'icon' => 'fa-check-circle',
					'group' => 'B',
					'can_edit' => false,
					'progress' => '',
				];
			} elseif ($waitingPlan) {
				$out = [
					'code' => 'B2',
					'label' => 'B2 อยู่ระหว่างเสนออนุมัติ งานแผน',
					'mod' => 'b2',
					'icon' => 'fa-hourglass-half',
					'group' => 'B',
					'can_edit' => false,
					'progress' => '',
				];
			} elseif ($apprTotal > 0) {
				$prog = pm_ab_fmt_progress($apprDone, $apprTotal);
				$out = [
					'code' => 'B2',
					'label' => 'B2 อยู่ระหว่างเสนออนุมัติ ผู้อนุมัติ' . $prog,
					'mod' => 'b2',
					'icon' => 'fa-hourglass-half',
					'group' => 'B',
					'can_edit' => false,
					'progress' => $prog,
				];
			} else {
				$prog = pm_ab_fmt_progress($certDone, $certTotal);
				$out = [
					'code' => 'B2',
					'label' => 'B2 อยู่ระหว่างตรวจสอบ ผู้ตรวจสอบหน่วยงาน' . $prog,
					'mod' => 'b2',
					'icon' => 'fa-hourglass-half',
					'group' => 'B',
					'can_edit' => false,
					'progress' => $prog,
				];
			}
		}

		$modNow = (string)$out['mod'];
		if ($cancelled) {
			$out['label'] = 'ยกเลิก';
			$out['mod'] = 'cancelled';
			$out['icon'] = 'fa-ban';
			$out['can_edit'] = false;
		} elseif ($cancelPending && in_array($modNow, ['b3', 'approved', 'changing', 'b4', 'b4_done', 'b5'], true)) {
			$out['label'] = 'ขอยกเลิก';
			$out['mod'] = 'cancel_pending';
			$out['icon'] = 'fa-ban';
			$out['can_edit'] = false;
		} elseif ($changePending && in_array($modNow, ['b3', 'approved'], true)) {
			$out['label'] = 'เปลี่ยนแปลง';
			$out['mod'] = 'changing';
			$out['icon'] = 'fa-exchange-alt';
		} elseif ($changeWait && in_array($modNow, ['b3', 'approved'], true)) {
			$out['label'] = 'รอหน่วยแผนแก้ไข';
			$out['mod'] = 'changing';
			$out['icon'] = 'fa-edit';
		} elseif ($isMidyear && (string)$out['group'] === 'A' && !in_array((string)$out['mod'], ['a_fix', 'a_reject'], true)) {
			$out['label'] = 'ยื่นระหว่างปี';
			$out['mod'] = 'midyear';
			$out['icon'] = 'fa-calendar-plus';
			$out['group'] = 'B';
		}

		return pm_ab_normalize_chip($out);
	}
}

if (!function_exists('pm_ab_parse_label')) {
	function pm_ab_parse_label(string $label, string $code = ''): array
	{
		$label = trim($label);
		if ($code === '' && preg_match('/^(A0|A1|A2|B1|B2|B3|B4|B5|A|B)\b/u', $label, $m)) {
			$code = (string)$m[1];
			$label = trim((string)preg_replace('/^(A0|A1|A2|B1|B2|B3|B4|B5|A|B)\b/u', '', $label));
		} elseif ($code !== '') {
			$label = trim((string)preg_replace('/^' . preg_quote($code, '/') . '\b\s*/u', '', $label));
		}
		$title = $label;
		$sub = '';
		foreach (['ผู้ตรวจสอบหน่วยงาน', 'ผู้อนุมัติ', 'ผู้ตรวจสอบมีข้อแก้ไข', 'งานแผน'] as $hint) {
			$pos = function_exists('mb_strpos') ? mb_strpos($label, $hint) : strpos($label, $hint);
			if ($pos !== false) {
				$title = trim(function_exists('mb_substr') ? mb_substr($label, 0, (int)$pos) : substr($label, 0, (int)$pos));
				$sub = trim(function_exists('mb_substr') ? mb_substr($label, (int)$pos) : substr($label, (int)$pos));
				break;
			}
		}
		return ['code' => $code, 'title' => $title, 'subtitle' => $sub];
	}
}

if (!function_exists('pm_ab_normalize_chip')) {
	function pm_ab_normalize_chip(array $out): array
	{
		$mod = (string)($out['mod'] ?? '');
		$code = trim((string)($out['code'] ?? ''));
		$prog = ltrim(trim((string)($out['progress'] ?? '')), ' :');
		$label = (string)($out['label'] ?? '');
		$title = trim((string)($out['title'] ?? ''));
		$sub = trim((string)($out['subtitle'] ?? ''));
		$has = function_exists('mb_strpos')
			? static function ($hay, $needle) { return mb_strpos((string)$hay, (string)$needle) !== false; }
			: static function ($hay, $needle) { return strpos((string)$hay, (string)$needle) !== false; };

		if ($title === '') {
			switch ($mod) {
				case 'a0':
					$title = 'ร่าง';
					$code = $code !== '' ? $code : 'A0';
					break;
				case 'a1':
					$title = 'รอส่งตรวจสอบ';
					$code = $code !== '' ? $code : 'A1';
					break;
				case 'a2':
					$code = $code !== '' ? $code : 'A2';
					if ($has($label, 'พิจารณา')) {
						$title = 'อยู่ระหว่างพิจารณา';
						$sub = 'งานแผน';
					} else {
						$title = 'อยู่ระหว่างตรวจสอบ';
						$sub = 'ผู้ตรวจสอบหน่วยงาน' . ($prog !== '' ? ' : ' . $prog : '');
					}
					break;
				case 'a_fix':
					$title = 'รอแก้ไข';
					$sub = 'ผู้ตรวจสอบมีข้อแก้ไข';
					$code = $code !== '' ? $code : 'A';
					break;
				case 'a_reject':
					$title = 'ไม่ผ่านการพิจารณา';
					$code = $code !== '' ? $code : 'A';
					break;
				case 'b1':
					$title = 'รอยื่นอนุมัติดำเนินการ';
					$code = $code !== '' ? $code : 'B1';
					break;
				case 'b2':
					$code = $code !== '' ? $code : 'B2';
					if ($has($label, 'เสนออนุมัติ')) {
						$title = 'อยู่ระหว่างเสนออนุมัติ';
						$sub = $has($label, 'ผู้อนุมัติ')
							? ('ผู้อนุมัติ' . ($prog !== '' ? ' : ' . $prog : ''))
							: 'งานแผน';
					} else {
						$title = 'อยู่ระหว่างตรวจสอบ';
						$sub = 'ผู้ตรวจสอบหน่วยงาน' . ($prog !== '' ? ' : ' . $prog : '');
					}
					break;
				case 'b3':
					$title = 'อนุมัติ/อยู่ระหว่างดำเนินการ';
					$code = $code !== '' ? $code : 'B3';
					break;
				case 'b4':
					$title = 'อยู่ระหว่างตรวจรายงาน';
					$code = $code !== '' ? $code : 'B4';
					$sub = 'ผู้ตรวจสอบหน่วยงาน' . ($prog !== '' ? ' : ' . $prog : '');
					break;
				case 'b4_done':
					$title = 'รายงานผลเรียบร้อย';
					$code = 'B4';
					$sub = 'รอตรวจงานแผน';
					break;
				case 'b5':
					$title = 'ปิดโครงการ';
					$code = 'B5';
					break;
				case 'b_fix':
					$title = 'รอแก้ไข';
					$sub = 'ผู้ตรวจสอบมีข้อแก้ไข';
					$code = $code !== '' ? $code : 'B';
					break;
				case 'cancelled':
					$title = 'ยกเลิก';
					$code = '';
					break;
				case 'cancel_pending':
					$title = 'ขอยกเลิก';
					$code = '';
					break;
				case 'changing':
					$title = $label !== '' ? $label : 'เปลี่ยนแปลง';
					$code = '';
					break;
				case 'midyear':
					$title = 'ยื่นระหว่างปี';
					$code = '';
					break;
				default:
					$parsed = pm_ab_parse_label($label, $code);
					$code = (string)($parsed['code'] ?? $code);
					$title = (string)($parsed['title'] !== '' ? $parsed['title'] : ($label !== '' ? $label : '-'));
					$sub = (string)($parsed['subtitle'] ?? '');
					break;
			}
		}
		$out['code'] = $code;
		$out['title'] = $title;
		$out['subtitle'] = $sub;
		$out['label'] = trim(($code !== '' ? $code . ' ' : '') . $title . ($sub !== '' ? ' ' . $sub : ''));
		return $out;
	}
}

if (!function_exists('pm_render_ab_badge')) {
	function pm_render_ab_badge($ab, array $opts = []): string
	{
		if (!is_array($ab)) {
			$ab = [
				'label' => (string)$ab,
				'mod' => (string)($opts['mod'] ?? ''),
				'code' => (string)($opts['code'] ?? ''),
			];
		}
		$p = pm_ab_normalize_chip($ab);
		$mod = preg_replace('/[^a-z0-9_]/i', '', (string)($p['mod'] ?? 'unknown')) ?: 'unknown';
		$code = (string)($p['code'] ?? '');
		$title = (string)($p['title'] ?? '-');
		$sub = (string)($p['subtitle'] ?? '');
		$id = trim((string)($opts['id'] ?? ''));
		$extra = trim((string)($opts['extra_class'] ?? ''));
		$hidden = !empty($opts['hidden']);
		$cls = trim('pm-status-badge pm-ab-chip pm-status-' . $mod . ($extra !== '' ? ' ' . $extra : '') . ($hidden ? ' d-none' : ''));
		$html = '<span';
		if ($id !== '') {
			$html .= ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';
		}
		$html .= ' class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '">';
		if ($code !== '') {
			$html .= '<span class="pm-status-code">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</span>';
		}
		$html .= '<span class="pm-status-text">';
		$html .= '<span class="pm-status-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>';
		if ($sub !== '') {
			$html .= '<span class="pm-status-sub">' . htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') . '</span>';
		}
		$html .= '</span></span>';
		return $html;
	}
}

if (!function_exists('pm_reopen_operate_queue_after_submitter_reply')) {
	/**
	 * ผู้ยื่นส่งคำชี้แจงกลับแล้ว แต่แถวยังค้าง status=8 + ผลตีกลับ
	 * → เปิดคิวผู้รับรองใหม่จากลำดับที่ตีกลับ และล็อกให้ user แก้ต่อไม่ได้
	 */
	function pm_reopen_operate_queue_after_submitter_reply($db_Project, int $project_id = 0): void
	{
		if (!$db_Project) {
			return;
		}
		$qCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE 'operate_submitter_reply_note'");
		if (!$qCol || mysqli_num_rows($qCol) === 0) {
			return;
		}
		$wherePid = ($project_id > 0) ? (" AND id='" . (int)$project_id . "'") : '';
		$q = @mysqli_query(
			$db_Project,
			"SELECT id
			 FROM pre_project
			 WHERE status='1'
			   AND status_project='8'
			   AND TRIM(IFNULL(operate_submitter_reply_note,'')) <> ''
			   AND (
					IFNULL(notify_result,'')='บรรจุแผน'
					OR IFNULL(midyear_request,0)=1
			   )
			   " . $wherePid
		);
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$pid = (int)($r['id'] ?? 0);
			if ($pid <= 0) {
				continue;
			}
			@mysqli_query(
				$db_Project,
				"UPDATE pre_project
				 SET status_project='1', dates=NOW()
				 WHERE id='" . $pid . "' AND status_project='8'
				 LIMIT 1"
			);
			$decTable = 'project_operate_certifier_decision';
			$rejectedByApprover = false;
			$qLast = @mysqli_query(
				$db_Project,
				"SELECT sort_order
				 FROM project_operate_certifier_decision
				 WHERE project_id='" . $pid . "' AND decision='rejected'
				 ORDER BY decided_at DESC, id DESC
				 LIMIT 1"
			);
			$last = $qLast ? mysqli_fetch_assoc($qLast) : null;
			if (!$last) {
				$qLastAp = @mysqli_query(
					$db_Project,
					"SELECT sort_order
					 FROM project_operate_approver_decision
					 WHERE project_id='" . $pid . "' AND decision='rejected'
					 ORDER BY decided_at DESC, id DESC
					 LIMIT 1"
				);
				$last = $qLastAp ? mysqli_fetch_assoc($qLastAp) : null;
				if ($last) {
					$rejectedByApprover = true;
					$decTable = 'project_operate_approver_decision';
				}
			}
			if ($rejectedByApprover) {
				@mysqli_query(
					$db_Project,
					"DELETE FROM project_operate_certifier_decision WHERE project_id='" . $pid . "'"
				);
				@mysqli_query(
					$db_Project,
					"DELETE FROM project_operate_approver_decision WHERE project_id='" . $pid . "'"
				);
			} else {
				$so = (int)($last['sort_order'] ?? 0);
				if ($so > 0) {
					@mysqli_query(
						$db_Project,
						"DELETE FROM `" . $decTable . "`
						 WHERE project_id='" . $pid . "'
						   AND sort_order >= '" . $so . "'"
					);
				}
			}
		}
	}
}

if (!function_exists('pm_load_operate_stage_row')) {
	function pm_load_operate_stage_row($db_Project, int $projectId, $preRow = null): ?array
	{
		$pid = (int)$projectId;
		if ($preRow && is_array($preRow)
			&& array_key_exists('operate_certifiers_json', $preRow)
			&& array_key_exists('status_project', $preRow)
			&& array_key_exists('operate_approvers_json', $preRow)
			&& array_key_exists('plan_admin_operate_rechain_submitted_at', $preRow)
		) {
			return $preRow;
		}
		if ($pid <= 0 || !$db_Project) {
			return is_array($preRow) ? $preRow : null;
		}
		pm_ensure_operate_approver_schema($db_Project);
		$q = @mysqli_query(
			$db_Project,
			"SELECT id, status_project, notify_result, operate_certifiers_json, operate_approvers_json, plan_admin_operate_rechain_submitted_at
			 FROM pre_project WHERE id='" . $pid . "' LIMIT 1"
		);
		$row = $q ? mysqli_fetch_assoc($q) : null;
		if (!$row) {
			return is_array($preRow) ? $preRow : null;
		}
		if (is_array($preRow)) {
			return array_merge($preRow, $row);
		}
		return $row;
	}
}

if (!function_exists('pm_is_operate_legacy_approver_in_certifiers')) {
	/** รอบเก่า: หน่วยแผนแทนที่ operate_certifiers_json แล้ว — ยังไม่มี operate_approvers_json */
	function pm_is_operate_legacy_approver_in_certifiers($preRow): bool
	{
		if (!is_array($preRow)) {
			return false;
		}
		return trim((string)($preRow['operate_approvers_json'] ?? '')) === ''
			&& trim((string)($preRow['plan_admin_operate_rechain_submitted_at'] ?? '')) !== '';
	}
}

if (!function_exists('pm_operate_approver_json_from_row')) {
	function pm_operate_approver_json_from_row($preRow): string
	{
		if (!is_array($preRow)) {
			return '';
		}
		$aj = trim((string)($preRow['operate_approvers_json'] ?? ''));
		if ($aj !== '') {
			return $aj;
		}
		if (pm_is_operate_legacy_approver_in_certifiers($preRow)) {
			return trim((string)($preRow['operate_certifiers_json'] ?? ''));
		}
		return '';
	}
}

if (!function_exists('pm_compute_operate_approver_chain_all_approved')) {
	function pm_compute_operate_approver_chain_all_approved($db_Project, int $projectId, $preRow = null): bool
	{
		$row = pm_load_operate_stage_row($db_Project, $projectId, $preRow);
		if (!$row) {
			return false;
		}
		$json = pm_operate_approver_json_from_row($row);
		$chain = pm_parse_operate_chain_json($json);
		if (empty($chain)) {
			return false;
		}
		$table = pm_is_operate_legacy_approver_in_certifiers($row)
			? 'project_operate_certifier_decision'
			: 'project_operate_approver_decision';
		$decMap = pm_load_operate_decision_map($db_Project, $projectId, $table);
		return pm_compute_operate_map_all_approved($chain, $decMap);
	}
}

if (!function_exists('pm_is_operate_waiting_plan_approver')) {
	function pm_is_operate_waiting_plan_approver($db_Project, int $projectId, $preRow = null): bool
	{
		$row = pm_load_operate_stage_row($db_Project, $projectId, $preRow);
		if (!$row) {
			return false;
		}
		$st = (string)($row['status_project'] ?? '');
		if (!in_array($st, ['1', '8'], true)) {
			return false;
		}
		if (trim((string)($row['operate_approvers_json'] ?? '')) !== '') {
			return false;
		}
		if (trim((string)($row['plan_admin_operate_rechain_submitted_at'] ?? '')) !== '') {
			return false;
		}
		return pm_compute_operate_chain_all_approved(
			$db_Project,
			$projectId,
			(string)($row['operate_certifiers_json'] ?? '')
		);
	}
}

if (!function_exists('pm_is_operate_final_approved')) {
	/** อนุมัติจริง: ผู้อนุมัติที่หน่วยแผนเลือกผ่านครบ (หรือรอบเก่าที่แทนที่ลำดับแล้วผ่านครบ) */
	function pm_is_operate_final_approved($db_Project, int $projectId, $preRow = null): bool
	{
		$row = pm_load_operate_stage_row($db_Project, $projectId, $preRow);
		if (!$row) {
			return false;
		}
		$st = (string)($row['status_project'] ?? '');
		if ($st !== '1') {
			return false;
		}
		if (trim((string)($row['notify_result'] ?? '')) === 'บรรจุแผนและแก้ไข') {
			return false;
		}
		return pm_compute_operate_approver_chain_all_approved($db_Project, $projectId, $row);
	}
}

if (!function_exists('pm_compute_operate_chain_all_approved')) {
	/**
	 * ผ่านลำดับ "ขออนุมัติดำเนินการ" ครบ — เกณฑ์เดียวกับหน้า approve (operate_certifiers_json;
	 * หากว่างให้ใช้ลำดับใน project_include_request_certifier ของคำขอบรรจุล่าสุด)
	 */
	function pm_compute_operate_chain_all_approved($db_Project, int $projectId, string $operateCertifiersJson): bool
	{
		$pid = (int)$projectId;
		if ($pid <= 0 || !$db_Project) {
			return false;
		}
		$certMap = [];
		$certRaw = trim($operateCertifiersJson);
		$certArr = ($certRaw !== '') ? json_decode($certRaw, true) : [];
		if (!is_array($certArr)) {
			$certArr = [];
		}
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
			if ((int)($c['staff_id'] ?? 0) <= 0) {
				continue;
			}
			$certMap[$so] = true;
		}
		$required = array_keys($certMap);
		sort($required, SORT_NUMERIC);
		if (empty($required)) {
			$rid = 0;
			$qRid = @mysqli_query(
				$db_Project,
				"SELECT MAX(request_id) AS rid FROM project_include_request_item WHERE project_id='" . $pid . "'"
			);
			$rRid = $qRid ? mysqli_fetch_assoc($qRid) : null;
			$rid = (int)($rRid['rid'] ?? 0);
			if ($rid <= 0) {
				return false;
			}
			$qC = @mysqli_query(
				$db_Project,
				"SELECT sort_order FROM project_include_request_certifier WHERE request_id='" . $rid . "' ORDER BY sort_order ASC"
			);
			while ($qC && ($cr = mysqli_fetch_assoc($qC))) {
				$so = (int)($cr['sort_order'] ?? 0);
				if ($so > 0) {
					$required[] = $so;
				}
			}
			$required = array_values(array_unique(array_map('intval', $required)));
			sort($required, SORT_NUMERIC);
		}
		if (empty($required)) {
			return false;
		}
		$decMap = [];
		$qOD = @mysqli_query($db_Project, "SELECT sort_order, decision FROM project_operate_certifier_decision WHERE project_id='" . $pid . "' ORDER BY sort_order ASC");
		while ($qOD && ($od = mysqli_fetch_assoc($qOD))) {
			$so = (int)($od['sort_order'] ?? 0);
			if ($so > 0) {
				$decMap[$so] = (string)($od['decision'] ?? '');
			}
		}
		foreach ($required as $soReq) {
			if (($decMap[$soReq] ?? '') !== 'approved') {
				return false;
			}
		}
		return true;
	}
}

/** โครงการอนุมัติครบแล้ว — เปิดให้กรอกผลตัวชี้วัด (และห้ามแก้ชื่อโครงการ) */
if (!function_exists('pm_project_allows_kpi_result_fill')) {
	function pm_project_allows_kpi_result_fill($db_Project, $project_id): bool
	{
		$pid = (int)$project_id;
		if ($pid <= 0 || !$db_Project || !function_exists('pm_compute_operate_chain_all_approved')) {
			return false;
		}
		$rq = @mysqli_query($db_Project, "SELECT status_project, operate_certifiers_json, notify_result FROM pre_project WHERE id='" . $pid . "' LIMIT 1");
		$row = $rq ? mysqli_fetch_assoc($rq) : null;
		if (!$row) {
			return false;
		}
		$st = (string)($row['status_project'] ?? '');
		if (!in_array($st, ['1', '8'], true)) {
			return false;
		}
		if (function_exists('pm_is_operate_final_approved')) {
			if (!pm_is_operate_final_approved($db_Project, $pid, $row)) {
				return false;
			}
		} elseif (!pm_compute_operate_chain_all_approved($db_Project, $pid, (string)($row['operate_certifiers_json'] ?? ''))) {
			return false;
		}
		if ($st === '8' && trim((string)($row['notify_result'] ?? '')) !== 'บรรจุแผน') {
			return false;
		}
		return true;
	}
}

function getSetProjectType($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_type WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetProjectTypeList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_type WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $ProjectType_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ProjectType_list[] = $row;
        }
    }
    return $ProjectType_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetProjectTypeSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_type WHERE id = '$id' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $ProjectType_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ProjectType_list[] = $row;
        }
    }
    return $ProjectType_list; // คืนค่าเป็น array ของหลายแถว

}
function getProjectTypeSelect($db_connection)
{

    $query = "SELECT * FROM set_project_type WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $ProjectType_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ProjectType_list[] = $row;
        }
    }
    return $ProjectType_list; // คืนค่าเป็น array ของหลายแถว

}
/** สร้างชื่อแหล่งงบแบบลำดับชั้นจากแถว view_budget_manage */
function pm_build_budget_manage_label(array $row): string
{
	$parts = [];
	$src = trim((string)($row['budget_source_name'] ?? ''));
	$yr = trim((string)($row['budget_manage_year'] ?? ''));
	if ($src !== '' && $yr !== '' && strpos($src, $yr) === false) {
		$yrShow = (strlen($yr) <= 2) ? ('25' . str_pad($yr, 2, '0', STR_PAD_LEFT)) : $yr;
		$src .= 'ปี ' . $yrShow;
	}
	if ($src !== '') {
		$parts[] = $src;
	}
	foreach (['budget_plan_name', 'budget_event_name'] as $k) {
		$v = trim((string)($row[$k] ?? ''));
		if ($v !== '') {
			$parts[] = $v;
		}
	}
	$act = trim((string)($row['budget_activity_name'] ?? ''));
	if ($act !== '') {
		if (!empty($parts)) {
			$parts[count($parts) - 1] .= '-' . $act;
		} else {
			$parts[] = $act;
		}
	}
	foreach (['budget_fund_name', 'budget_category_budget_name'] as $k) {
		$v = trim((string)($row[$k] ?? ''));
		if ($v !== '') {
			$parts[] = $v;
		}
	}
	$pay = trim((string)($row['budget_category_pay_name'] ?? ''));
	$payDet = trim((string)($row['budget_category_pay_detail'] ?? ''));
	if ($pay !== '') {
		$parts[] = $payDet !== '' ? ($pay . '-' . $payDet) : $pay;
	}
	return implode('/', $parts);
}

/**
 * รายการแหล่งงบจาก PBM (view_budget_manage)
 * @return list<array{id:string,code:string,name:string,status:string,year:string,amount:float}>
 */
function getPbmBudgetManageList($db_connection = null, bool $activeOnly = true): array
{
	$db = pm_budget_source_connection($db_connection);
	$list = [];
	if (!($db instanceof mysqli)) {
		return $list;
	}
	$where = $activeOnly
		? "WHERE budget_manage_status IN ('1', 1)"
		: "WHERE IFNULL(budget_manage_status,'') NOT IN ('99')";
	$sql = "SELECT budget_manage_id, budget_manage_code, budget_manage_year, budget_manage_status,
			budget_manage_amount, budget_manage_balance,
			budget_source_name, budget_plan_name, budget_event_name, budget_activity_name,
			budget_fund_name, budget_category_budget_name, budget_category_pay_name, budget_category_pay_detail
		FROM view_budget_manage
		$where
		ORDER BY budget_manage_year DESC, budget_manage_code ASC";
	$q = @mysqli_query($db, $sql);
	if (!$q) {
		return $list;
	}
	while ($row = mysqli_fetch_assoc($q)) {
		$code = trim((string)($row['budget_manage_code'] ?? ''));
		if ($code === '') {
			continue;
		}
		$name = pm_build_budget_manage_label($row);
		$yrRaw = trim((string)($row['budget_manage_year'] ?? ''));
		$yrShow = $yrRaw;
		if ($yrRaw !== '' && strlen(preg_replace('/\D+/', '', $yrRaw)) <= 2) {
			$digits = preg_replace('/\D+/', '', $yrRaw);
			$yrShow = '25' . str_pad($digits, 2, '0', STR_PAD_LEFT);
		}
		$list[] = [
			'id' => $code,
			'code' => $code,
			'name' => $name !== '' ? $name : $code,
			'status' => (string)($row['budget_manage_status'] ?? ''),
			'year' => $yrShow,
			'year_raw' => $yrRaw,
			'amount' => (float)($row['budget_manage_amount'] ?? 0),
			'balance' => (float)($row['budget_manage_balance'] ?? 0),
			'budget_manage_id' => (string)($row['budget_manage_id'] ?? ''),
		];
	}
	return $list;
}

/** เชื่อมต่อฐานแหล่งงบ (PBM) — ใช้ $db_pbmBudget_PBM เป็นหลัก */
function pm_budget_source_connection($fallback = null)
{
	global $db_pbmBudget_PBM, $db_pbmBudget;
	if (isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli) {
		return $db_pbmBudget_PBM;
	}
	if (isset($db_pbmBudget) && $db_pbmBudget instanceof mysqli) {
		return $db_pbmBudget;
	}
	return ($fallback instanceof mysqli) ? $fallback : null;
}

/** แปลงแถว budget_set_source ให้รูปแบบเดียวกับ set_project_budget เดิม (id/name/status) */
function pm_normalize_budget_source_row(array $row, bool $preferCodeAsId = false): array
{
	$pk = (string)($row['budget_source_id'] ?? ($row['id'] ?? ''));
	$code = trim((string)($row['budget_source_code'] ?? ($row['code'] ?? '')));
	$name = trim((string)($row['budget_source_name'] ?? ($row['name'] ?? '')));
	$status = (string)($row['budget_source_status'] ?? ($row['status'] ?? ''));
	$id = $preferCodeAsId && $code !== '' ? $code : $pk;
	return [
		'id' => $id,
		'name' => $name,
		'status' => $status,
		'code' => $code,
		'budget_source_id' => $pk,
		'budget_source_code' => $code,
		'budget_source_name' => $name,
		'budget_source_status' => $status,
	];
}

function getSetBudget($db_connection, $id)
{
	$db = pm_budget_source_connection($db_connection);
	$idRaw = trim((string)$id);
	if (!$db instanceof mysqli || $idRaw === '') {
		return null;
	}
	$idEsc = mysqli_real_escape_string($db, $idRaw);
	// 1) รหัสงบจาก view_budget_manage
	$qManage = @mysqli_query(
		$db,
		"SELECT budget_manage_id, budget_manage_code, budget_manage_year, budget_manage_status,
			budget_source_name, budget_plan_name, budget_event_name, budget_activity_name,
			budget_fund_name, budget_category_budget_name, budget_category_pay_name, budget_category_pay_detail
		 FROM view_budget_manage
		 WHERE budget_manage_code = '$idEsc'
		 LIMIT 1"
	);
	if ($qManage && ($mRow = mysqli_fetch_assoc($qManage))) {
		$code = trim((string)($mRow['budget_manage_code'] ?? ''));
		$name = pm_build_budget_manage_label($mRow);
		return [
			'id' => $code,
			'code' => $code,
			'name' => $name !== '' ? $name : $code,
			'status' => (string)($mRow['budget_manage_status'] ?? ''),
		];
	}
	$qManageId = @mysqli_query(
		$db,
		"SELECT budget_manage_id, budget_manage_code, budget_manage_year, budget_manage_status,
			budget_source_name, budget_plan_name, budget_event_name, budget_activity_name,
			budget_fund_name, budget_category_budget_name, budget_category_pay_name, budget_category_pay_detail
		 FROM view_budget_manage
		 WHERE CAST(budget_manage_id AS CHAR) = '$idEsc'
		 LIMIT 1"
	);
	if ($qManageId && ($mRow = mysqli_fetch_assoc($qManageId))) {
		$code = trim((string)($mRow['budget_manage_code'] ?? ''));
		$name = pm_build_budget_manage_label($mRow);
		return [
			'id' => $code !== '' ? $code : $idRaw,
			'code' => $code !== '' ? $code : $idRaw,
			'name' => $name !== '' ? $name : ($code !== '' ? $code : $idRaw),
			'status' => (string)($mRow['budget_manage_status'] ?? ''),
		];
	}
	$q = @mysqli_query(
		$db,
		"SELECT * FROM budget_set_source
		 WHERE (CAST(budget_source_id AS CHAR) = '$idEsc' OR budget_source_code = '$idEsc')
		   AND IFNULL(budget_source_status,'') NOT IN ('99', '99.0')
		 LIMIT 1"
	);
	if ($q && ($row = mysqli_fetch_assoc($q))) {
		return pm_normalize_budget_source_row($row, false);
	}
	// fallback ตารางเดิมใน project
	if ($db_connection instanceof mysqli) {
		$idEsc2 = mysqli_real_escape_string($db_connection, $idRaw);
		$result = @mysqli_query($db_connection, "SELECT * FROM set_project_budget WHERE id = '$idEsc2' LIMIT 1");
		if ($result && mysqli_num_rows($result) > 0) {
			return mysqli_fetch_array($result, MYSQLI_ASSOC);
		}
	}
	return null;
}

function getSetBudgetList($db_connection, $id = null)
{
	// หน้าตั้งค่า/รายการแหล่งงบ: ใช้รายการงบจาก view_budget_manage (รหัสงบ + ชื่อแหล่ง)
	$manage = getPbmBudgetManageList($db_connection, false);
	if (!empty($manage)) {
		return $manage;
	}
	$db = pm_budget_source_connection($db_connection);
	$budget_list = [];
	if ($db instanceof mysqli) {
		$result = @mysqli_query(
			$db,
			"SELECT * FROM budget_set_source
			 WHERE IFNULL(budget_source_status,'') NOT IN ('99', '99.0')
			 ORDER BY budget_source_id ASC"
		);
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$budget_list[] = pm_normalize_budget_source_row($row, false);
			}
			return $budget_list;
		}
	}
	if (!($db_connection instanceof mysqli)) {
		return $budget_list;
	}
	$query = "SELECT * FROM set_project_budget WHERE status != '99' ORDER BY id ASC";
	$result = mysqli_query($db_connection, $query);
	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$budget_list[] = $row;
		}
	}
	return $budget_list;
}

function getSetBudgetSelect($db_connection, $id)
{
	$row = getSetBudget($db_connection, $id);
	return $row ? [$row] : [];
}

function getAddBudgetSelect($db_connection)
{
	$manage = getPbmBudgetManageList($db_connection, true);
	if (!empty($manage)) {
		return $manage;
	}
	$db = pm_budget_source_connection($db_connection);
	$budget_list = [];
	if ($db instanceof mysqli) {
		$result = @mysqli_query(
			$db,
			"SELECT * FROM budget_set_source
			 WHERE budget_source_status IN ('1', 1)
			 ORDER BY budget_source_id ASC"
		);
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				// ฟอร์มโครงการเก็บ code_budget เป็นรหัสแหล่งงบ — ใช้ code ถ้ามี
				$budget_list[] = pm_normalize_budget_source_row($row, true);
			}
			return $budget_list;
		}
	}
	if (!($db_connection instanceof mysqli)) {
		return $budget_list;
	}
	$query = "SELECT * FROM set_project_budget WHERE status = '1' ORDER BY id ASC";
	$result = mysqli_query($db_connection, $query);
	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$budget_list[] = $row;
		}
	}
	return $budget_list;
}
function getSetExpenses($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_expenses WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetExpensesList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_expenses WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $expenses_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenses_list[] = $row;
        }
    }
    return $expenses_list; // คืนค่าเป็น array ของหลายแถว
}

/** หมวดค่าใช้จ่ายแบบไม่มีค่าใช้จ่าย / ไม่ใช้งบประมาณ */
if (!function_exists('pm_expense_category_is_no_expense')) {
	function pm_expense_category_is_no_expense($name): bool
	{
		$n = trim((string)$name);
		if ($n === '') {
			return false;
		}
		$needles = ['ไม่มีค่าใช้จ่าย', 'ไม่ใช้งบประมาณ'];
		foreach ($needles as $needle) {
			if ($n === $needle) {
				return true;
			}
			if (function_exists('mb_strpos')) {
				if (mb_strpos($n, $needle) !== false) {
					return true;
				}
			} elseif (strpos($n, $needle) !== false) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('pm_expense_category_id_is_no_expense')) {
	function pm_expense_category_id_is_no_expense($db_connection, $code_expenses): bool
	{
		$code = trim((string)$code_expenses);
		if ($code === '' || !$db_connection || !function_exists('getSetExpenses')) {
			return false;
		}
		$row = getSetExpenses($db_connection, $code);
		if (!is_array($row)) {
			return false;
		}
		return pm_expense_category_is_no_expense($row['name'] ?? '');
	}
}

if (!function_exists('pm_first_expense_detail_id')) {
	function pm_first_expense_detail_id($db_connection, $code_expenses): string
	{
		$code = trim((string)$code_expenses);
		if ($code === '' || !$db_connection) {
			return '';
		}
		$codeEsc = mysqli_real_escape_string($db_connection, $code);
		$q = @mysqli_query(
			$db_connection,
			"SELECT id FROM set_project_expenses_detail
			 WHERE code_expenses = '$codeEsc' AND status = '1'
			 ORDER BY id ASC LIMIT 1"
		);
		$row = ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;
		return $row ? trim((string)($row['id'] ?? '')) : '';
	}
}

function getSetExpensesSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_expenses WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $expenses_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenses_list[] = $row;
        }
    }
    return $expenses_list; // คืนค่าเป็น array ของหลายแถว
}
function getExpensesSelect($db_connection)
{

    $query = "SELECT * FROM set_expenses WHERE status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $expenses_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenses_list[] = $row;
        }
    }
    return $expenses_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetExpensesDetailList($db_connection, $id, $activeOnly = true)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $statusSql = $activeOnly
        ? "AND status = '1'"
        : "AND (status IS NULL OR status = '' OR status != '99')";
    $query = "SELECT * FROM set_project_expenses_detail WHERE code_expenses = '$id' $statusSql ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $expenses_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenses_list[] = $row;
        }
    }
    return $expenses_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetExpensesDetailByID($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_expenses_detail WHERE id = '$id' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

if (!function_exists('ensureSetExpensesDetailAttachSchema')) {
    function ensureSetExpensesDetailAttachSchema($db_Project)
    {
        static $done = false;
        if ($done || !$db_Project) {
            return;
        }
        $done = true;
        foreach (['attach_file' => 'VARCHAR(255) NULL DEFAULT NULL', 'attach_name' => 'VARCHAR(255) NULL DEFAULT NULL'] as $col => $type) {
            $esc = mysqli_real_escape_string($db_Project, $col);
            $q = @mysqli_query($db_Project, "SHOW COLUMNS FROM set_project_expenses_detail LIKE '$esc'");
            if ($q && mysqli_num_rows($q) === 0) {
                @mysqli_query($db_Project, "ALTER TABLE set_project_expenses_detail ADD COLUMN `$col` $type");
            }
        }
    }
}

if (!function_exists('pmExpenseDetailAttachDir')) {
    function pmExpenseDetailAttachDir()
    {
        $dir = dirname(__DIR__) . '/document/expense_files';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $ht = $dir . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents(
                $ht,
                "# Kill PHP Execution\n<Files ~ \".ph(?:p[345]?|t|tml)$\">\ndeny from all\n</Files>\n"
            );
        }
        return $dir;
    }
}

if (!function_exists('pmExpenseDetailAttachUrl')) {
    function pmExpenseDetailAttachUrl($stored)
    {
        $stored = basename(trim((string) $stored));
        if ($stored === '' || $stored === '.' || $stored === '..') {
            return '';
        }
        return '../document/expense_files/' . rawurlencode($stored);
    }
}

if (!function_exists('pmDeleteExpenseAttachFile')) {
    function pmDeleteExpenseAttachFile($stored)
    {
        $stored = basename(trim((string) $stored));
        if ($stored === '' || $stored === '.' || $stored === '..') {
            return;
        }
        $path = pmExpenseDetailAttachDir() . DIRECTORY_SEPARATOR . $stored;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

if (!function_exists('pmSaveUploadedExpenseAttach')) {
    function pmSaveUploadedExpenseAttach($field = 'attach_file')
    {
        if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
            return ['ok' => 0, 'empty' => 1];
        }
        $name = (string) ($_FILES[$field]['name'] ?? '');
        $err = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmp = (string) ($_FILES[$field]['tmp_name'] ?? '');
        $size = (int) ($_FILES[$field]['size'] ?? 0);
        if ($name === '' || $err === UPLOAD_ERR_NO_FILE) {
            return ['ok' => 0, 'empty' => 1];
        }
        if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => 0, 'empty' => 0, 'msg' => 'อัปโหลดเอกสารแนบไม่สำเร็จ'];
        }
        $maxBytes = 15 * 1024 * 1024;
        if ($size <= 0 || $size > $maxBytes) {
            return ['ok' => 0, 'empty' => 0, 'msg' => 'ขนาดเอกสารแนบต้องไม่เกิน 15 MB'];
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return ['ok' => 0, 'empty' => 0, 'msg' => 'เอกสารแนบต้องเป็นไฟล์ PDF เท่านั้น'];
        }
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string) @finfo_file($finfo, $tmp) : '';
            if ($finfo) {
                @finfo_close($finfo);
            }
            if ($mime !== '' && $mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
                return ['ok' => 0, 'empty' => 0, 'msg' => 'เอกสารแนบต้องเป็นไฟล์ PDF เท่านั้น'];
            }
        }
        $dir = pmExpenseDetailAttachDir();
        if (!is_dir($dir) || !is_writable($dir)) {
            return ['ok' => 0, 'empty' => 0, 'msg' => 'ไม่สามารถสร้างโฟลเดอร์เก็บเอกสารแนบได้'];
        }
        $stored = 'expd_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;
        if (!@move_uploaded_file($tmp, $dest)) {
            return ['ok' => 0, 'empty' => 0, 'msg' => 'บันทึกเอกสารแนบไม่สำเร็จ'];
        }
        $safeName = $name;
        if (function_exists('mb_substr')) {
            $safeName = mb_substr($name, 0, 200, 'UTF-8');
        } else {
            $safeName = substr($name, 0, 200);
        }
        return ['ok' => 1, 'empty' => 0, 'stored' => $stored, 'name' => $safeName];
    }
}

function getSetConsistency($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_consistency WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetConsistencyList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_consistency WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $expenses_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenses_list[] = $row;
        }
    }
    return $expenses_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetConsistencySelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_consistency WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $expenses_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenses_list[] = $row;
        }
    }
    return $expenses_list; // คืนค่าเป็น array ของหลายแถว
}

/** set_project_consistency.id ของ SDGs ในข้อมูลพื้นฐาน */
if (!defined('PM_SDGS_CONSISTENCY_ID')) {
	define('PM_SDGS_CONSISTENCY_ID', 2);
}

/** สถานะการใช้งานของหมวดความสอดคล้อง (1 = เปิด) */
if (!function_exists('pm_set_consistency_is_enabled')) {
	function pm_set_consistency_is_enabled($db_connection, $id): bool
	{
		$id = (int)$id;
		if ($id <= 0 || !$db_connection) {
			return false;
		}
		$q = @mysqli_query($db_connection, "SELECT status FROM set_project_consistency WHERE id='" . $id . "' LIMIT 1");
		$row = $q ? mysqli_fetch_assoc($q) : null;
		return is_array($row) && (string)($row['status'] ?? '') === '1';
	}
}

if (!function_exists('pm_sdgs_consistency_id')) {
	function pm_sdgs_consistency_id($db_connection): int
	{
		$fallback = defined('PM_SDGS_CONSISTENCY_ID') ? (int)PM_SDGS_CONSISTENCY_ID : 2;
		if (!$db_connection) {
			return $fallback;
		}
		$q = @mysqli_query(
			$db_connection,
			"SELECT id FROM set_project_consistency
			 WHERE IFNULL(status,'') != '99'
			   AND (id='2' OR name LIKE '%SDG%')
			 ORDER BY CASE WHEN name LIKE '%SDG%' THEN 0 WHEN id=2 THEN 1 ELSE 2 END, id ASC
			 LIMIT 1"
		);
		$row = $q ? mysqli_fetch_assoc($q) : null;
		$id = (int)($row['id'] ?? 0);
		return $id > 0 ? $id : $fallback;
	}
}

if (!function_exists('pm_sdgs_consistency_enabled')) {
	function pm_sdgs_consistency_enabled($db_connection): bool
	{
		return pm_set_consistency_is_enabled($db_connection, pm_sdgs_consistency_id($db_connection));
	}
}

if (!function_exists('pm_consistency_ui_labels')) {
	function pm_consistency_ui_labels(string $typeName = '', string $childKind = ''): array
	{
		$group = 'other';
		if (preg_match('/กลยุทธ์|กลยุทธ/u', $typeName)) {
			$group = 'strategy';
		} elseif (preg_match('/พันธกิจ/u', $typeName)) {
			$group = 'mission';
		} elseif (preg_match('/พัฒนา/u', $typeName)) {
			$group = 'develop';
		} elseif ($childKind === 'so') {
			$group = 'strategy';
		}

		$sideLabel = 'ด้าน';
		$sideHint = '';
		$soLabel = 'ยุทธศาสตร์ (SO)';
		$soHint = '';
		$level2Label = 'ด้าน/แผนปฏิบัติการ (AP)';
		$sidePlaceholder = '- เลือกด้าน -';
		$soPlaceholder = '- เลือกยุทธศาสตร์ -';
		$needSide = 'กรุณาเลือกด้าน';
		$needSo = 'กรุณาเลือกยุทธศาสตร์ (SO)';

		if ($group === 'mission') {
			$sideLabel = 'พันธกิจที่สนับสนุน';
			$sideHint = 'เลือกพันธกิจหลักที่โครงการมีส่วนสนับสนุน';
			$level2Label = 'พันธกิจที่สนับสนุน';
			$sidePlaceholder = '- เลือกพันธกิจ -';
			$needSide = 'กรุณาเลือกพันธกิจที่สนับสนุน';
		} elseif ($group === 'develop') {
			$sideLabel = 'ด้านที่พัฒนา';
			$sideHint = 'เลือกด้านหลักที่โครงการมุ่งให้เกิดการเปลี่ยนแปลงหรือปรับปรุง';
			$level2Label = 'ด้านที่พัฒนา';
			$sidePlaceholder = '- เลือกด้าน -';
			$needSide = 'กรุณาเลือกด้านที่พัฒนา';
		} elseif ($group === 'strategy') {
			$soLabel = 'แผนปฏิบัติการ (AP)';
			$soHint = 'เลือกแผนปฏิบัติการหลักที่โครงการมีส่วนสนับสนุนให้บรรลุเป้าหมาย';
			$level2Label = 'แผนปฏิบัติการ (AP)';
			$soPlaceholder = '- เลือก AP -';
			$needSo = 'กรุณาเลือกแผนปฏิบัติการ (AP)';
		}

		return [
			'group' => $group,
			'sectionTitle' => 'ความเชื่อมโยงกับแผนและตัวชี้วัด',
			'sectionHint' => 'ระบุแผน ด้าน และตัวชี้วัดของคณะที่โครงการมีส่วนสนับสนุน',
			'typeLabel' => 'กรอบแผนของโครงการ',
			'typeHint' => 'เลือกกรอบแผนที่สอดคล้องกับวัตถุประสงค์หลักของโครงการ',
			'sideLabel' => $sideLabel,
			'sideHint' => $sideHint,
			'soLabel' => $soLabel,
			'soHint' => $soHint,
			'level2Label' => $level2Label,
			'sidePlaceholder' => $sidePlaceholder,
			'soPlaceholder' => $soPlaceholder,
			'needType' => 'กรุณาเลือกกรอบแผนของโครงการก่อน',
			'needSide' => $needSide,
			'needSo' => $needSo,
			'needAp' => 'กรุณาเลือกแผนปฏิบัติการ (AP)',
		];
	}
}

function getConsistencySelect($db_connection)
{

    $query = "SELECT * FROM set_project_consistency WHERE status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $expenses_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenses_list[] = $row;
        }
    }
    return $expenses_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetConsistencyDetailList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_consistency_detail WHERE code_consistency = '$id' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $expenses_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenses_list[] = $row;
        }
    }
    return $expenses_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetConsistencyDetailByID($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_consistency_detail WHERE id = '$id' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetNature($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_nature WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetNatureList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_nature WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $nature_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $nature_list[] = $row;
        }
    }
    return $nature_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetNatureSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_nature WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $nature_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $nature_list[] = $row;
        }
    }
    return $nature_list; // คืนค่าเป็น array ของหลายแถว
}
function getNatureSelect($db_connection)
{

    $query = "SELECT * FROM set_project_nature WHERE status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $nature_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $nature_list[] = $row;
        }
    }
    return $nature_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetStatus($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_status WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetStatusList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_status WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $status_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $status_list[] = $row;
        }
    }
    return $status_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetStatusSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_status WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $status_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $status_list[] = $row;
        }
    }
    return $status_list; // คืนค่าเป็น array ของหลายแถว
}
function getStatusSelect($db_connection)
{

    $query = "SELECT * FROM set_project_status WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $status_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $status_list[] = $row;
        }
    }
    return $status_list; // คืนค่าเป็น array ของหลายแถว
}

function getSetTarget($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_target WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetTargetList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_target WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $target_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $target_list[] = $row;
        }
    }
    return $target_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetTargetSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_project_target WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $target_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $target_list[] = $row;
        }
    }
    return $target_list; // คืนค่าเป็น array ของหลายแถว
}
function getTargetSelect($db_connection)
{

    $query = "SELECT * FROM set_project_target WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $target_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $target_list[] = $row;
        }
    }
    return $target_list; // คืนค่าเป็น array ของหลายแถว
}

/**
 * สร้างตาราง set_project_personnel ในฐาน project ถ้ายังไม่มี (ใช้กับเมนูตั้งค่าหน่วยงาน)
 * เชื่อม set_department, set_job, set_unit ในฐานเดียวกัน (db_Project)
 */
function ensureSetProjectPersonnelTable($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;

    try {
        $sql = "CREATE TABLE IF NOT EXISTS set_project_personnel (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(512) NOT NULL DEFAULT '',
            staff_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'person.staff.id',
            code_department INT UNSIGNED NULL DEFAULT NULL COMMENT 'project.set_department.id',
            code_job INT UNSIGNED NULL DEFAULT NULL COMMENT 'project.set_job.id',
            code_unit INT UNSIGNED NULL DEFAULT NULL COMMENT 'project.set_unit.id',
            status VARCHAR(8) NOT NULL DEFAULT '1',
            PRIMARY KEY (id),
            KEY idx_spp_staff_id (staff_id),
            KEY idx_spp_code_department (code_department),
            KEY idx_spp_code_job (code_job),
            KEY idx_spp_code_unit (code_unit),
            KEY idx_spp_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!mysqli_query($db_Project, $sql)) {
            @error_log('ensureSetProjectPersonnelTable: ' . mysqli_error($db_Project));

            return;
        }

        $addIfMissing = function ($col, $def) use ($db_Project) {
            try {
                $esc = mysqli_real_escape_string($db_Project, $col);
                $q = mysqli_query($db_Project, "SHOW COLUMNS FROM set_project_personnel LIKE '$esc'");
                if ($q && mysqli_num_rows($q) === 0) {
                    mysqli_query($db_Project, "ALTER TABLE set_project_personnel ADD COLUMN $def");
                }
            } catch (Throwable $e) {
                @error_log('ensureSetProjectPersonnelTable addIfMissing: ' . $e->getMessage());
            }
        };

        $addIfMissing('staff_id', 'staff_id INT UNSIGNED NULL DEFAULT NULL AFTER name');
        $addIfMissing('code_department', 'code_department INT UNSIGNED NULL DEFAULT NULL AFTER staff_id');
        $addIfMissing('code_job', 'code_job INT UNSIGNED NULL DEFAULT NULL AFTER code_department');
        $addIfMissing('code_unit', 'code_unit INT UNSIGNED NULL DEFAULT NULL AFTER code_job');
        $addIfMissing('is_unit_admin', 'is_unit_admin TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER code_unit');
    } catch (Throwable $e) {
        @error_log('ensureSetProjectPersonnelTable: ' . $e->getMessage());
    }
}

/**
 * ตาราง set_department_admin (ฐาน project) — โครงตามสคีมาที่ใช้จริง:
 * dep_code, job_code, unit_code (varchar — อ้างอิงหน่วย เช่น set_unit.id), dep_name,
 * admin_group (1=กลุ่มที่ 1, 2=กลุ่มที่ 2), staff_admin (varchar), dates
 *
 * - กลุ่ม 1: ขอบเขตหลักที่คู่ dep_code + job_code (+ admin_group)
 * - กลุ่ม 2: เดียวกัน + unit_code ชี้หน่วย
 */
function ensureSetDepartmentAdminTable($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    try {
        $chk = mysqli_query($db_Project, "SHOW TABLES LIKE 'set_department_admin'");
        $exists = $chk && mysqli_num_rows($chk) > 0;
        if (!$exists) {
            $sql = "CREATE TABLE IF NOT EXISTS set_department_admin (
                id INT(11) NOT NULL AUTO_INCREMENT,
                dep_code VARCHAR(20) NULL DEFAULT NULL COMMENT 'ภาค/ศูนย์',
                job_code VARCHAR(20) NULL DEFAULT NULL COMMENT 'งาน',
                unit_code VARCHAR(20) NULL DEFAULT NULL,
                dep_name VARCHAR(255) NULL DEFAULT NULL,
                admin_group TINYINT NULL DEFAULT NULL COMMENT '1=กลุ่มที่ 1  2=กลุ่มที่ 2',
                staff_admin VARCHAR(20) NULL DEFAULT NULL,
                dates DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_sda_dep_job_grp (dep_code, job_code, admin_group),
                KEY idx_sda_staff (staff_admin),
                KEY idx_sda_unit_code (unit_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            if (!mysqli_query($db_Project, $sql)) {
                @error_log('ensureSetDepartmentAdminTable: ' . mysqli_error($db_Project));
                $done = false;

                return;
            }
        }

        $addIfMissing = function ($col, $def) use ($db_Project) {
            try {
                $esc = mysqli_real_escape_string($db_Project, $col);
                $q = mysqli_query($db_Project, "SHOW COLUMNS FROM set_department_admin LIKE '$esc'");
                if ($q && mysqli_num_rows($q) === 0) {
                    mysqli_query($db_Project, "ALTER TABLE set_department_admin ADD COLUMN $def");
                }
            } catch (Throwable $e) {
                @error_log('ensureSetDepartmentAdminTable addIfMissing: ' . $e->getMessage());
            }
        };

        $addIfMissing('admin_group', "admin_group TINYINT NULL DEFAULT NULL COMMENT '1=กลุ่มที่ 1  2=กลุ่มที่ 2' AFTER dep_name");

        // เดิมใช้ชื่อคอลัมน์ code_unit — เปลี่ยนเป็น unit_code (คงข้อมูลถ้ามีอยู่แล้ว)
        try {
            $qc = mysqli_query($db_Project, "SHOW COLUMNS FROM set_department_admin LIKE 'code_unit'");
            $qu = mysqli_query($db_Project, "SHOW COLUMNS FROM set_department_admin LIKE 'unit_code'");
            $hasOldCodeUnit = $qc && mysqli_num_rows($qc) > 0;
            $hasUnitCode = $qu && mysqli_num_rows($qu) > 0;
            if ($hasOldCodeUnit && !$hasUnitCode) {
                @mysqli_query($db_Project, 'ALTER TABLE set_department_admin CHANGE COLUMN code_unit unit_code VARCHAR(20) NULL DEFAULT NULL');
            } elseif (!$hasUnitCode) {
                mysqli_query($db_Project, "ALTER TABLE set_department_admin ADD COLUMN unit_code VARCHAR(20) NULL DEFAULT NULL AFTER job_code");
            }
        } catch (Throwable $e) {
            @error_log('ensureSetDepartmentAdminTable migrate unit_code: ' . $e->getMessage());
        }

        mysqli_query($db_Project, "UPDATE set_department_admin SET admin_group = 1 WHERE admin_group IS NULL OR admin_group = 0");
    } catch (Throwable $e) {
        @error_log('ensureSetDepartmentAdminTable: ' . $e->getMessage());
        $done = false;
    }
}

/**
 * รายชื่อคอลัมน์ที่มีใน set_unit (คีย์เป็นตัวพิมพ์เล็ก) — ใช้หลีกเลี่ยง SELECT อ้างคอลัมน์ที่ไม่มี
 *
 * @return array<string, true>
 */
function planSetUnitColumnSet($db_Project): array
{
    static $byConn = [];

    if (!$db_Project) {
        return [];
    }
    $k = function_exists('spl_object_id')
        ? (string) spl_object_id($db_Project)
        : spl_object_hash($db_Project);
    if (isset($byConn[$k])) {
        return $byConn[$k];
    }
    $set = [];
    $q = @mysqli_query($db_Project, 'SHOW COLUMNS FROM set_unit');
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $f = strtolower((string) ($row['Field'] ?? ''));
            if ($f !== '') {
                $set[$f] = true;
            }
        }
    }
    $byConn[$k] = $set;

    return $set;
}

/**
 * ค้นหาแถว set_unit สำหรับสลับ Admin (ลอง id ก่อน แล้ว code)
 * รองรับกรณี UI ส่งเลขที่เป็นรหัสหน่วยใน master (เช่น 12010504) แทน id จริงในตาราง
 * และกรณี schema ไม่มีคอลัมน์ code (ใช้ SELECT * ที่ id / จับคู่ผ่านคอลัมน์ที่มีจริง)
 *
 * @return array<string,mixed>|null
 */
function planResolveSetUnitRowForToggle($db_Project, int $codeUnitReq, string $unitCodeRaw, int $fallbackDep = 0, int $fallbackJob = 0)
{
    if (!$db_Project) {
        return null;
    }
    $pick = static function ($db_Project, string $sql) {
        $q = @mysqli_query($db_Project, $sql);
        if ($q && mysqli_num_rows($q) > 0) {
            $r = mysqli_fetch_assoc($q);
            if ($r && (string) ($r['status'] ?? '') !== '99') {
                return $r;
            }
        }

        return null;
    };

    $colSet = planSetUnitColumnSet($db_Project);
    $codeFields = [];
    foreach (['code', 'unit_code'] as $cf) {
        if (isset($colSet[$cf])) {
            $codeFields[] = $cf;
        }
    }

    if ($codeUnitReq > 0) {
        $r = $pick($db_Project, 'SELECT * FROM set_unit WHERE id = ' . (int) $codeUnitReq . ' LIMIT 1');
        if ($r) {
            return $r;
        }
    }

    $tryCodes = [];
    $u = trim($unitCodeRaw);
    if ($u !== '') {
        $tryCodes[] = $u;
    }
    if ($codeUnitReq > 0) {
        $tryCodes[] = (string) (int) $codeUnitReq;
    }
    $seen = [];
    $expanded = [];
    foreach ($tryCodes as $c0) {
        $c = trim((string) $c0);
        if ($c === '' || isset($seen[$c])) {
            continue;
        }
        $seen[$c] = true;
        $expanded[] = $c;
        if (preg_match('/^[0-9]+$/', $c)) {
            $nz = ltrim($c, '0');
            if ($nz !== '' && $nz !== $c && !isset($seen[$nz])) {
                $seen[$nz] = true;
                $expanded[] = $nz;
            }
        }
    }

    foreach ($expanded as $c) {
        $c = trim((string) $c);
        if ($c === '') {
            continue;
        }
        $e = mysqli_real_escape_string($db_Project, $c);
        foreach ($codeFields as $field) {
            $r = $pick(
                $db_Project,
                "SELECT * FROM set_unit WHERE TRIM(CAST(IFNULL(`{$field}`,'') AS CHAR)) = '{$e}'
				 AND IFNULL(status,'') != '99' ORDER BY id ASC LIMIT 1"
            );
            if ($r) {
                return $r;
            }
        }
    }

    if (
        ($fallbackDep > 0 || $fallbackJob > 0)
        && isset($colSet['code_department'], $colSet['code_job'])
    ) {
        $d = (int) $fallbackDep;
        $j = (int) $fallbackJob;
        $r = $pick(
            $db_Project,
            'SELECT * FROM set_unit WHERE IFNULL(code_department,0) = ' . $d . '
			 AND IFNULL(code_job,0) = ' . $j . "
			 AND IFNULL(status,'') != '99' ORDER BY id ASC LIMIT 1"
        );
        if ($r) {
            return $r;
        }
    }

    return null;
}

/**
 * สร้างค่า dep_code / job_code / dep_name / admin_group สำหรับ set_department_admin จากแถว set_unit
 *
 * @param  array<string,mixed> $unitRow แถว set_unit (มี code, name, job_code, code_job, code_department)
 * @return array{dep_code:string,job_code:string,dep_name:string,admin_group:int}
 */
function planDepartmentAdminRowFromUnit($db_Project, array $unitRow)
{
    $jobCodeText = trim((string) ($unitRow['job_code'] ?? ''));
    $codeJobNum = (int) ($unitRow['code_job'] ?? 0);
    $codeDepNum = (int) ($unitRow['code_department'] ?? 0);
    $unitName = trim((string) ($unitRow['name'] ?? ''));
    $unitCode = trim((string) ($unitRow['code'] ?? ''));

    $trunc20 = static function (string $s): string {
        if (strlen($s) <= 20) {
            return $s;
        }

        return substr($s, 0, 20);
    };
    $trunc255 = static function (string $s): string {
        if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($s, 'UTF-8') > 255) {
            return mb_substr($s, 0, 255, 'UTF-8');
        }
        if (strlen($s) > 255) {
            return substr($s, 0, 255);
        }

        return $s;
    };

    // กลุ่มที่ 2: หน่วยไม่ผูก job บน set_unit — เก็บ dep_code = job_code = รหัสหน่วย (ตามแถวตัวอย่างในฐาน)
    if ($jobCodeText === '' && $codeJobNum <= 0) {
        $c = $unitCode !== '' ? $unitCode : ('U' . (int) ($unitRow['id'] ?? 0));
        $c20 = $trunc20($c);
        $nm = $unitName !== '' ? $unitName : $c20;

        return [
            'dep_code' => $c20,
            'job_code' => $c20,
            'dep_name' => $trunc255($nm),
            'admin_group' => 2,
        ];
    }

    $depStr = '';
    $depNamePart = '';
    if ($codeDepNum > 0) {
        $qd = mysqli_query($db_Project, 'SELECT code, name FROM set_department WHERE id = ' . $codeDepNum . " AND IFNULL(status,'') != '99' LIMIT 1");
        if ($qd && mysqli_num_rows($qd) > 0) {
            $dr = mysqli_fetch_assoc($qd) ?: [];
            $depStr = trim((string) ($dr['code'] ?? ''));
            $depNamePart = trim((string) ($dr['name'] ?? ''));
        }
    }

    $jobStr = '';
    $jobNamePart = '';
    if ($jobCodeText !== '') {
        $jobStr = $jobCodeText;
    }
    if ($codeJobNum > 0) {
        $qj = mysqli_query($db_Project, 'SELECT code, name FROM set_job WHERE id = ' . $codeJobNum . " AND IFNULL(status,'') != '99' LIMIT 1");
        if ($qj && mysqli_num_rows($qj) > 0) {
            $jr = mysqli_fetch_assoc($qj) ?: [];
            $jc = trim((string) ($jr['code'] ?? ''));
            if ($jc !== '') {
                $jobStr = $jc;
            }
            if ($jobNamePart === '') {
                $jobNamePart = trim((string) ($jr['name'] ?? ''));
            }
        }
    }

    $depCode = $trunc20($depStr !== '' ? $depStr : ('D' . $codeDepNum));
    $jobCode = $trunc20($jobStr !== '' ? $jobStr : ('J' . $codeJobNum));
    $depName = $jobNamePart !== '' ? $jobNamePart : ($unitName !== '' ? $unitName : ($depNamePart !== '' ? $depNamePart : trim($depCode . '/' . $jobCode)));

    return [
        'dep_code' => $depCode,
        'job_code' => $jobCode,
        'dep_name' => $trunc255($depName),
        'admin_group' => 1,
    ];
}

/**
 * ซิงค์สิทธิ์ Admin ไปยัง set_department_admin หลังสลับ is_unit_admin ที่ set_project_personnel
 * staff_admin เก็บเป็นรหัสตัวเลข staff.id (สอดคล้องข้อมูลเดิมในตาราง)
 *
 * @param  mysqli $db_Project
 * @param  mysqli|null $db_person
 */
function planSyncSetDepartmentAdminForPersonnelToggle($db_Project, $db_person, int $staffId, int $codeUnitId, int $next, int $personnelDep = 0, int $personnelJob = 0): void
{
    if (!$db_Project || $staffId <= 0 || $codeUnitId <= 0) {
        return;
    }
    ensureSetDepartmentAdminTable($db_Project);
    if (!$db_person) {
        return;
    }
    $qs = mysqli_query($db_person, 'SELECT id FROM staff WHERE id = ' . (int) $staffId . ' LIMIT 1');
    if (!$qs || mysqli_num_rows($qs) < 1) {
        return;
    }
    $qu = mysqli_query(
        $db_Project,
        'SELECT * FROM set_unit WHERE id = ' . (int) $codeUnitId . ' LIMIT 1'
    );
    $unit = ($qu && mysqli_num_rows($qu) > 0) ? (mysqli_fetch_assoc($qu) ?: []) : [];
    if (!$unit && function_exists('planResolveSetUnitRowForToggle')) {
        $resolved = planResolveSetUnitRowForToggle($db_Project, $codeUnitId, '', $personnelDep, $personnelJob);
        if (is_array($resolved) && $resolved !== []) {
            $unit = $resolved;
        }
    }
    if (!$unit || !is_array($unit)) {
        throw new RuntimeException('set_department_admin_unit_missing');
    }
    if ((string) ($unit['status'] ?? '') === '99') {
        throw new RuntimeException('set_department_admin_unit_inactive');
    }
    $row = planDepartmentAdminRowFromUnit($db_Project, $unit);
    if (($row['dep_code'] ?? '') === '' && ($row['job_code'] ?? '') === '') {
        throw new RuntimeException('set_department_admin_scope_empty');
    }
    $d = mysqli_real_escape_string($db_Project, (string) $row['dep_code']);
    $j = mysqli_real_escape_string($db_Project, (string) $row['job_code']);
    $n = mysqli_real_escape_string($db_Project, (string) $row['dep_name']);
    $grp = (int) ($row['admin_group'] ?? 1);
    if ($grp !== 1 && $grp !== 2) {
        $grp = 1;
    }
    $unitPk = (int) ($unit['id'] ?? 0);
    $staffAdminEsc = mysqli_real_escape_string($db_Project, (string) (int) $staffId);
    // unit_code ใน set_department_admin เป็น VARCHAR(20) — เก็บ set_unit.id เป็นสตริง
    $unitCodeEsc = ($unitPk > 0)
        ? ("'" . mysqli_real_escape_string($db_Project, (string) $unitPk) . "'")
        : 'NULL';

    if ($next === 1) {
        if (!mysqli_query(
            $db_Project,
            "DELETE FROM set_department_admin WHERE dep_code = '$d' AND job_code = '$j' AND admin_group = $grp"
        )) {
            throw new RuntimeException('set_department_admin_delete_scope');
        }
        if (!mysqli_query(
            $db_Project,
            "INSERT INTO set_department_admin (dep_code, job_code, unit_code, dep_name, admin_group, staff_admin, dates)
			 VALUES ('$d', '$j', $unitCodeEsc, '$n', $grp, '$staffAdminEsc', NOW())"
        )) {
            throw new RuntimeException('set_department_admin_insert');
        }
    } elseif (!mysqli_query(
        $db_Project,
        "DELETE FROM set_department_admin WHERE dep_code = '$d' AND job_code = '$j' AND admin_group = $grp AND staff_admin = '$staffAdminEsc'"
    )) {
        throw new RuntimeException('set_department_admin_delete_staff');
    }
}

/** @param array<string,mixed> $row */
function projectPersonnelNormalizeLookupRow(array $row): array
{
    $row['unit_name'] = $row['unit_name'] ?? '';
    $row['job_name'] = $row['job_name'] ?? '';
    $row['department_name'] = $row['department_name'] ?? '';
    $row['unit_code'] = $row['unit_code'] ?? '';
    $row['is_unit_admin'] = isset($row['is_unit_admin']) ? (int) $row['is_unit_admin'] : 0;

    return $row;
}

/**
 * ลองรัน SQL ตามลำดับจนกว่าจะสำเร็จ (join ครบ → เหลือหน่วย → ไม่ join)
 *
 * @param  string[] $sqlVariants
 * @return mysqli_result|false
 */
function projectPersonnelTryQuery($db_Project, array $sqlVariants)
{
    foreach ($sqlVariants as $sql) {
        try {
            $res = mysqli_query($db_Project, $sql);
        } catch (Throwable $e) {
            $res = false;
        }
        if ($res) {
            return $res;
        }
    }

    return false;
}

function getSetPersonnel($db_connection, $id)
{
    ensureSetProjectPersonnelTable($db_connection);
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $sqlFull = "SELECT p.*, u.name AS unit_name, j.name AS job_name, d.name AS department_name
        FROM set_project_personnel p
        LEFT JOIN set_unit u ON u.id = p.code_unit AND u.status != '99'
        LEFT JOIN set_job j ON j.id = p.code_job AND j.status != '99'
        LEFT JOIN set_department d ON d.id = p.code_department AND d.status != '99'
        WHERE p.id = '$id'";
    $sqlUnit = "SELECT p.*, u.name AS unit_name
        FROM set_project_personnel p
        LEFT JOIN set_unit u ON u.id = p.code_unit AND u.status != '99'
        WHERE p.id = '$id'";
    $sqlPlain = "SELECT * FROM set_project_personnel WHERE id = '$id'";

    $result = projectPersonnelTryQuery($db_connection, [$sqlFull, $sqlUnit, $sqlPlain]);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    return projectPersonnelNormalizeLookupRow(mysqli_fetch_array($result, MYSQLI_ASSOC));
}

function getSetPersonnelList($db_connection, $id)
{
    ensureSetProjectPersonnelTable($db_connection);
    // พารามิเตอร์ $id สงวนไว้สำหรับกรองในอนาคต
    $sqlFull = "SELECT p.*, u.name AS unit_name, j.name AS job_name, d.name AS department_name
        FROM set_project_personnel p
        LEFT JOIN set_unit u ON u.id = p.code_unit AND u.status != '99'
        LEFT JOIN set_job j ON j.id = p.code_job AND j.status != '99'
        LEFT JOIN set_department d ON d.id = p.code_department AND d.status != '99'
        WHERE p.status != '99'
        ORDER BY (d.name IS NULL), d.name ASC, (u.name IS NULL), u.name ASC, (j.name IS NULL), j.name ASC, p.id ASC";
    $sqlUnit = "SELECT p.*, u.name AS unit_name
        FROM set_project_personnel p
        LEFT JOIN set_unit u ON u.id = p.code_unit AND u.status != '99'
        WHERE p.status != '99'
        ORDER BY (u.name IS NULL), u.name ASC, p.id ASC";
    $sqlPlain = "SELECT * FROM set_project_personnel WHERE status != '99' ORDER BY id ASC";

    $result = projectPersonnelTryQuery($db_connection, [$sqlFull, $sqlUnit, $sqlPlain]);
    if (!$result) {
        return [];
    }

    $personnel_list = [];
    try {
        while ($row = mysqli_fetch_assoc($result)) {
            $personnel_list[] = projectPersonnelNormalizeLookupRow($row);
        }
    } catch (Throwable $e) {
        @error_log('getSetPersonnelList fetch: ' . $e->getMessage());
    }

    return $personnel_list;
}

/** ป้ายกำกับรายการ master (รหัส — ชื่อ หรือชื่ออย่างเดียว) */
function planMasterRowLabel($row): string
{
    if (!is_array($row)) {
        return '';
    }
    $c = trim((string) ($row['code'] ?? ''));
    $n = trim((string) ($row['name'] ?? ''));

    return $c !== '' ? $c . ' — ' . $n : $n;
}

/**
 * หน่วยงานใน set_unit เป็นหน่วยงานหลักของบุคลากร (เปรียบเทียบชื่อกับ master คน)
 * ใช้กันไม่ให้เพิ่ม “หน่วยงานร่วม” ซ้ำกับสังกัดหลัก
 */
function planSetUnitIsStaffMainAffiliation($db_Project, $db_person, int $setUnitId, array $staffRow): bool
{
    if (!$db_Project || !$db_person || $setUnitId <= 0) {
        return false;
    }
    if (!function_exists('staffMainUnitDisplayName')) {
        include_once __DIR__ . '/main_staff.php';
    }
    $main = staffMainUnitDisplayName($db_person, $staffRow);
    if ($main === '') {
        return false;
    }
    $setUnitId = (int) $setUnitId;
    $uname = '';
    try {
        $q = mysqli_query($db_Project, "SELECT name FROM set_unit WHERE id = $setUnitId LIMIT 1");
        if ($q) {
            $r = mysqli_fetch_assoc($q);
            if (is_array($r)) {
                $uname = trim((string) ($r['name'] ?? ''));
            }
        }
    } catch (Throwable $e) {
        @error_log('planSetUnitIsStaffMainAffiliation: ' . $e->getMessage());

        return false;
    }

    return $uname !== '' && $uname === $main;
}

/** จัดรูปแบบชื่อจาก set_project_personnel.name ให้เทียบกับ CONCAT(fname,' ',lname) */
function planNormalizePersonDisplayName(string $name): string
{
    return trim(preg_replace('/\s+/u', ' ', $name));
}

/**
 * ค้นหาแถว staff จากชื่อเต็ม — ใช้ได้เฉพาะเมื่อมีผลลัพธ์คนเดียว (กันชื่อซ้ำ)
 *
 * @return array<string,mixed>|null
 */
function planLookupStaffRowByFullName($db_person, string $fullName): ?array
{
    $fullName = planNormalizePersonDisplayName($fullName);
    if ($fullName === '' || !$db_person) {
        return null;
    }
    $esc = mysqli_real_escape_string($db_person, $fullName);
    try {
        $q = mysqli_query(
            $db_person,
            "SELECT id, unit_code, job_code, dep_code, position_code
             FROM staff
             WHERE work_status IN ('0','1')
             AND CONCAT(TRIM(fname),' ',TRIM(lname)) = '$esc'
             LIMIT 2"
        );
        if (!$q || mysqli_num_rows($q) !== 1) {
            return null;
        }

        return mysqli_fetch_assoc($q) ?: null;
    } catch (Throwable $e) {
        @error_log('planLookupStaffRowByFullName: ' . $e->getMessage());

        return null;
    }
}

/**
 * สร้างแผนที่ชื่อที่แสดง (normalize) → สังกัดหลัก/ตำแหน่ง สำหรับแถวที่ยังไม่มี staff_id
 *
 * @param array<int, array<string,mixed>> $personnelRows
 *
 * @return array<string, array{main_unit: string, position: string}>
 */
function planBuildStaffDisplayByNameMap($db_person, array $personnelRows): array
{
    if (!$db_person) {
        return [];
    }
    if (!function_exists('staffMainUnitDisplayName')) {
        include_once __DIR__ . '/main_staff.php';
    }
    $namesNeed = [];
    foreach ($personnelRows as $_p) {
        if ((int) ($_p['staff_id'] ?? 0) > 0) {
            continue;
        }
        $n = planNormalizePersonDisplayName((string) ($_p['name'] ?? ''));
        if ($n !== '') {
            $namesNeed[$n] = true;
        }
    }
    $out = [];
    foreach (array_keys($namesNeed) as $n) {
        $sr = planLookupStaffRowByFullName($db_person, $n);
        if (!$sr) {
            continue;
        }
        $mainU = staffMainUnitDisplayName($db_person, $sr);
        $posN = '';
        $pc = trim((string) ($sr['position_code'] ?? ''));
        if ($pc !== '' && function_exists('getPosition')) {
            $rp = getPosition($db_person, $pc);
            if (is_array($rp)) {
                $posN = trim((string) ($rp['name'] ?? ''));
            }
        }
        $out[$n] = [
            'main_unit' => $mainU,
            'position' => $posN,
        ];
    }

    return $out;
}

/**
 * รัน SELECT บน db_Project อย่างปลอดภัย (กันตาราง/คอลัมน์ไม่ตรง + mysqli strict exception)
 *
 * @return array<int, array<string,mixed>>
 */
function projectPlanSafeQueryList($db_Project, string $sql): array
{
    $list = [];
    if (!$db_Project) {
        return $list;
    }
    try {
        $result = mysqli_query($db_Project, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $list[] = $row;
            }
        }
    } catch (Throwable $e) {
        @error_log('projectPlanSafeQueryList: ' . $e->getMessage());
    }

    return $list;
}

/** รายการจาก set_unit ในฐาน project */
function getSetUnitListForPlan($db_Project)
{
    if (!$db_Project) {
        return [];
    }
    // ลองหลายรูปแบบ: บางฐานไม่มีคอลัมน์ status/name หรือชนิดข้อมูลต่าง จึงคืน [] จากคำสั่งแรก
    $candidates = [
        "SELECT * FROM set_unit WHERE status != '99' ORDER BY name ASC, id ASC",
        "SELECT * FROM set_unit WHERE status != '99' ORDER BY id ASC",
        "SELECT * FROM set_unit ORDER BY name ASC, id ASC",
        "SELECT * FROM set_unit ORDER BY id ASC",
    ];
    foreach ($candidates as $sql) {
        $list = projectPlanSafeQueryList($db_Project, $sql);
        if (!empty($list)) {
            return $list;
        }
    }

    return [];
}

/** set_unit ที่เปิดใช้งาน (status = 1) */
function getSetUnitSelectActiveForPlan($db_Project)
{
    return projectPlanSafeQueryList($db_Project, "SELECT * FROM set_unit WHERE status = '1' ORDER BY name ASC, id ASC");
}

/** กลุ่มที่ 1 = ผูก job  กลุ่มที่ 2 = ไม่ผูก job (ตามหน้าข้อมูลพื้นฐาน/หน่วยงาน) */
function planSetUnitGroupNumber(array $unitRow): int
{
    $jobCodeText = trim((string) ($unitRow['job_code'] ?? ''));
    $codeJobNum = (int) ($unitRow['code_job'] ?? 0);

    return ($jobCodeText === '' && $codeJobNum <= 0) ? 2 : 1;
}

/**
 * แยก set_unit เป็น 2 กลุ่ม (เรียงตามป้ายกำกับ master)
 *
 * @return array{1: list<array<string,mixed>>, 2: list<array<string,mixed>>}
 */
function getSetUnitGroupedForPlan($db_Project): array
{
    $groups = [1 => [], 2 => []];
    $sortFn = function ($a, $b) {
        $la = planSetUnitOptionLabel(is_array($a) ? $a : []);
        $lb = planSetUnitOptionLabel(is_array($b) ? $b : []);

        return strcmp($la, $lb);
    };
    foreach (getSetUnitListForPlan($db_Project) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $g = planSetUnitGroupNumber($row);
        $groups[$g][] = $row;
    }
    usort($groups[1], $sortFn);
    usort($groups[2], $sortFn);

    return $groups;
}

/** ค่า value ของ &lt;option&gt; หน่วยงานจาก set_unit (ใช้ id) */
function planSetUnitOptionValue(array $unitRow): string
{
    $id = (int) ($unitRow['id'] ?? 0);

    return $id > 0 ? (string) $id : '';
}

/** ป้ายกำกับตัวเลือกหน่วยงาน (ชื่ออย่างเดียว ไม่ใส่รหัส) */
function planSetUnitOptionLabel(array $unitRow): string
{
    $n = function_exists('planStripUnitCodeFromLabel')
        ? planStripUnitCodeFromLabel((string) ($unitRow['name'] ?? ''))
        : trim((string) ($unitRow['name'] ?? ''));
    if ($n !== '') {
        return $n;
    }
    if (function_exists('planMasterRowLabel') && function_exists('planStripUnitCodeFromLabel')) {
        return planStripUnitCodeFromLabel(planMasterRowLabel($unitRow));
    }

    return '';
}

/** ตัดรหัสหน่วยงานหน้าชื่อ เช่น "U041 - หน่วย..." / "12010102 — หน่วย..." -> "หน่วย..." */
function planStripUnitCodeFromLabel(string $label): string
{
    $label = trim($label);
    if ($label === '') {
        return '';
    }
    if (preg_match('/^[A-Za-z0-9._]+\s*[—\-–]\s*(.+)$/u', $label, $m)) {
        $rest = trim((string) ($m[1] ?? ''));
        return $rest !== '' ? $rest : $label;
    }
    return $label;
}

/** แผนที่ id/code → ชื่อหน่วยงานอย่างเดียว (ไม่ใส่รหัส) */
function planBuildSetUnitNamesMapNameOnly($db_Project): array
{
    $map = [];
    if (!function_exists('getSetUnitListForPlan') || !function_exists('planSetUnitOptionValue')) {
        return $map;
    }
    foreach (getSetUnitListForPlan($db_Project) as $u) {
        if (!is_array($u)) {
            continue;
        }
        $nm = trim((string) ($u['name'] ?? ''));
        if (function_exists('planStripUnitCodeFromLabel')) {
            $nm = planStripUnitCodeFromLabel($nm);
        }
        if ($nm === '') {
            continue;
        }
        $id = planSetUnitOptionValue($u);
        if ($id !== '') {
            $map[$id] = $nm;
        }
        $code = trim((string) ($u['code'] ?? ''));
        if ($code !== '') {
            $map[$code] = $nm;
        }
    }
    return $map;
}

/**
 * กลุ่มหน่วยงานสำหรับ &lt;select&gt; — เหลือเฉพาะกลุ่มที่ 2 (เพิ่มเอง ไม่ผูก job)
 *
 * @return list<array{label: string, units: list<array<string,mixed>>}>
 */
function getSetUnitSelectGroupsForPlan($db_Project): array
{
    $grouped = getSetUnitGroupedForPlan($db_Project);

    return [
        ['label' => 'หน่วยงาน', 'units' => $grouped[2]],
    ];
}

/** แผนที่ id/code → ชื่อแสดงของ set_unit */
function planBuildSetUnitNamesMap($db_Project): array
{
    $map = [];
    foreach (getSetUnitListForPlan($db_Project) as $u) {
        if (!is_array($u)) {
            continue;
        }
        $label = planSetUnitOptionLabel($u);
        $id = planSetUnitOptionValue($u);
        if ($id !== '') {
            $map[$id] = $label;
        }
        $code = trim((string) ($u['code'] ?? ''));
        if ($code !== '') {
            $map[$code] = $label;
        }
    }

    return $map;
}

/** แปลงค่า code_unit ที่เก็บไว้ (id หรือรหัสเก่า) ให้เป็น set_unit.id */
function planResolvePreProjectCodeUnit($db_Project, string $stored): string
{
    $stored = trim($stored);
    if ($stored === '' || !$db_Project) {
        return '';
    }
    foreach (getSetUnitListForPlan($db_Project) as $u) {
        if (!is_array($u)) {
            continue;
        }
        $id = planSetUnitOptionValue($u);
        if ($id !== '' && $id === $stored) {
            return $id;
        }
        $code = trim((string) ($u['code'] ?? ''));
        if ($code !== '' && $code === $stored) {
            return $id;
        }
    }

    return $stored;
}

/** แผนที่ id/code → ชื่อส่วนงาน (set_unit ก่อน แล้วค่อย person.unit) */
function pm_build_project_unit_names_map($db_Project, $db_person = null): array
{
    $map = [];
    if (function_exists('planBuildSetUnitNamesMapNameOnly') && $db_Project) {
        $map = planBuildSetUnitNamesMapNameOnly($db_Project);
        if (!is_array($map)) {
            $map = [];
        }
    }
    if ($db_person && function_exists('getUnitSelect')) {
        foreach (getUnitSelect($db_person) as $u) {
            if (!is_array($u)) {
                continue;
            }
            $c = trim((string) ($u['code'] ?? ''));
            $nm = trim((string) ($u['name'] ?? ''));
            if (function_exists('planStripUnitCodeFromLabel')) {
                $nm = planStripUnitCodeFromLabel($nm);
            }
            if ($c !== '' && $nm !== '' && !isset($map[$c])) {
                $map[$c] = $nm;
            }
        }
    }

    return $map;
}

/** หาชื่อส่วนงานจากแผนที่ (รองรับค่าที่เก็บเป็น id หรือรหัสเก่า) */
function pm_unit_name_from_map(array $map, string $code_unit, $db_Project = null): string
{
    $code_unit = trim($code_unit);
    if ($code_unit === '') {
        return '';
    }
    $nm = trim((string) ($map[$code_unit] ?? ''));
    if ($nm === '' && $db_Project && function_exists('planResolvePreProjectCodeUnit')) {
        $resolved = planResolvePreProjectCodeUnit($db_Project, $code_unit);
        if ($resolved !== '' && $resolved !== $code_unit) {
            $nm = trim((string) ($map[$resolved] ?? ''));
        }
    }
    if ($nm !== '' && function_exists('planStripUnitCodeFromLabel')) {
        $nm = planStripUnitCodeFromLabel($nm);
    }

    return $nm;
}

/** ชื่อส่วนงานของโครงการจาก code_unit (set_unit ก่อน แล้วค่อย person.unit) */
function pm_project_unit_display_name($db_Project, string $code_unit, $db_person = null): string
{
    $code_unit = trim($code_unit);
    if ($code_unit === '') {
        return '';
    }
    $map = function_exists('pm_build_project_unit_names_map')
        ? pm_build_project_unit_names_map($db_Project, $db_person)
        : [];

    return pm_unit_name_from_map(is_array($map) ? $map : [], $code_unit, $db_Project);
}

/**
 * รายการตัวเลือกแบบแบน สำหรับ JavaScript (เฉพาะกลุ่มที่ 2)
 *
 * @return list<array{val: string, name: string, group: int}>
 */
function planSetUnitFlatOptionsForJs($db_Project): array
{
    $out = [];
    $grouped = getSetUnitGroupedForPlan($db_Project);
    foreach (($grouped[2] ?? []) as $u) {
        if (!is_array($u)) {
            continue;
        }
        $val = planSetUnitOptionValue($u);
        if ($val === '') {
            continue;
        }
        $out[] = [
            'val' => $val,
            'name' => planSetUnitOptionLabel($u),
            'group' => 2,
        ];
    }

    return $out;
}

/**
 * แสดง &lt;option&gt; / &lt;optgroup&gt; หน่วยงานจาก set_unit (เฉพาะหน่วยงานที่เพิ่มเอง)
 *
 * @param list<array{label: string, units: list<array<string,mixed>>}> $groups
 * @param mixed $db_Project unused (คงพารามิเตอร์ไว้เพื่อไม่ให้ call site เดิมพัง)
 */
function planRenderSetUnitSelectOptions(array $groups, string $selected = '', $db_Project = null): void
{
    $selected = trim($selected);
    foreach ($groups as $ginfo) {
        $units = $ginfo['units'] ?? [];
        if (!is_array($units) || empty($units)) {
            continue;
        }
        $label = trim((string) ($ginfo['label'] ?? ''));
        if ($label !== '') {
            echo '<optgroup label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';
        }
        foreach ($units as $u) {
            if (!is_array($u)) {
                continue;
            }
            $val = planSetUnitOptionValue($u);
            if ($val === '') {
                continue;
            }
            $code = trim((string) ($u['code'] ?? ''));
            $isSel = ($selected !== '' && ($selected === $val || ($code !== '' && $selected === $code)));
            echo '<option value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"'
                . ($isSel ? ' selected' : '') . '>'
                . htmlspecialchars(planSetUnitOptionLabel($u), ENT_QUOTES, 'UTF-8')
                . '</option>';
        }
        if ($label !== '') {
            echo '</optgroup>';
        }
    }
}

/** รายการจาก set_job ในฐาน project */
function getSetJobListForPlan($db_Project)
{
    if (!$db_Project) {
        return [];
    }
    $candidates = [
        "SELECT * FROM set_job WHERE status != '99' ORDER BY name ASC, id ASC",
        "SELECT * FROM set_job WHERE status != '99' ORDER BY id ASC",
        "SELECT * FROM set_job ORDER BY name ASC, id ASC",
        "SELECT * FROM set_job ORDER BY id ASC",
    ];
    foreach ($candidates as $sql) {
        $list = projectPlanSafeQueryList($db_Project, $sql);
        if (!empty($list)) {
            return $list;
        }
    }

    return [];
}

function getSetJobSelectActiveForPlan($db_Project)
{
    if (!$db_Project) {
        return [];
    }
    $candidates = [
        "SELECT * FROM set_job WHERE status = '1' ORDER BY name ASC, id ASC",
        "SELECT * FROM set_job WHERE status = '1' ORDER BY id ASC",
        // fallback: ถ้าคอลัมน์ status ใช้งานไม่เหมือนเดิม
        "SELECT * FROM set_job WHERE status != '99' ORDER BY name ASC, id ASC",
    ];
    foreach ($candidates as $sql) {
        $list = projectPlanSafeQueryList($db_Project, $sql);
        if (!empty($list)) {
            return $list;
        }
    }

    return [];
}

/** รายการจาก set_department ในฐาน project */
function getSetDepartmentListForPlan($db_Project)
{
    if (!$db_Project) {
        return [];
    }
    $candidates = [
        "SELECT * FROM set_department WHERE status != '99' ORDER BY name ASC, id ASC",
        "SELECT * FROM set_department WHERE status != '99' ORDER BY id ASC",
        "SELECT * FROM set_department ORDER BY name ASC, id ASC",
        "SELECT * FROM set_department ORDER BY id ASC",
    ];
    foreach ($candidates as $sql) {
        $list = projectPlanSafeQueryList($db_Project, $sql);
        if (!empty($list)) {
            return $list;
        }
    }

    return [];
}

function getSetDepartmentSelectActiveForPlan($db_Project)
{
    if (!$db_Project) {
        return [];
    }
    $candidates = [
        "SELECT * FROM set_department WHERE status = '1' ORDER BY name ASC, id ASC",
        "SELECT * FROM set_department WHERE status = '1' ORDER BY id ASC",
        "SELECT * FROM set_department WHERE status != '99' ORDER BY name ASC, id ASC",
    ];
    foreach ($candidates as $sql) {
        $list = projectPlanSafeQueryList($db_Project, $sql);
        if (!empty($list)) {
            return $list;
        }
    }

    return [];
}

function getSetPersonnelSelect($db_connection, $id)
{
    ensureSetProjectPersonnelTable($db_connection);
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $sqlFull = "SELECT p.*, u.name AS unit_name, j.name AS job_name, d.name AS department_name
        FROM set_project_personnel p
        LEFT JOIN set_unit u ON u.id = p.code_unit AND u.status != '99'
        LEFT JOIN set_job j ON j.id = p.code_job AND j.status != '99'
        LEFT JOIN set_department d ON d.id = p.code_department AND d.status != '99'
        WHERE p.id = '$id' AND p.status != '99'
        ORDER BY (d.name IS NULL), d.name ASC, (u.name IS NULL), u.name ASC, p.id ASC";
    $sqlUnit = "SELECT p.*, u.name AS unit_name
        FROM set_project_personnel p
        LEFT JOIN set_unit u ON u.id = p.code_unit AND u.status != '99'
        WHERE p.id = '$id' AND p.status != '99'
        ORDER BY (u.name IS NULL), u.name ASC, p.id ASC";
    $sqlPlain = "SELECT * FROM set_project_personnel WHERE id = '$id' AND status != '99' ORDER BY id ASC";

    $result = projectPersonnelTryQuery($db_connection, [$sqlFull, $sqlUnit, $sqlPlain]);
    if (!$result) {
        return [];
    }

    $personnel_list = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $personnel_list[] = projectPersonnelNormalizeLookupRow($row);
    }

    return $personnel_list;
}

function getPersonnelSelect($db_connection)
{
    ensureSetProjectPersonnelTable($db_connection);
    $sqlFull = "SELECT p.*, u.name AS unit_name, j.name AS job_name, d.name AS department_name
        FROM set_project_personnel p
        LEFT JOIN set_unit u ON u.id = p.code_unit AND u.status != '99'
        LEFT JOIN set_job j ON j.id = p.code_job AND j.status != '99'
        LEFT JOIN set_department d ON d.id = p.code_department AND d.status != '99'
        WHERE p.status = '1'
        ORDER BY (d.name IS NULL), d.name ASC, (u.name IS NULL), u.name ASC, p.id ASC";
    $sqlUnit = "SELECT p.*, u.name AS unit_name
        FROM set_project_personnel p
        LEFT JOIN set_unit u ON u.id = p.code_unit AND u.status != '99'
        WHERE p.status = '1'
        ORDER BY (u.name IS NULL), u.name ASC, p.id ASC";
    $sqlPlain = "SELECT * FROM set_project_personnel WHERE status = '1' ORDER BY id ASC";

    $result = projectPersonnelTryQuery($db_connection, [$sqlFull, $sqlUnit, $sqlPlain]);
    if (!$result) {
        return [];
    }

    $personnel_list = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $personnel_list[] = projectPersonnelNormalizeLookupRow($row);
    }

    return $personnel_list;
}

function getPreProjectList($db_connection, $years_select, $overview, $staff_code, $code_unit)
{
    $years_select = mysqli_real_escape_string($db_connection, $years_select); // ป้องกัน SQL Injection
    $overview = mysqli_real_escape_string($db_connection, $overview); // ป้องกัน SQL Injection
    $staff_code = mysqli_real_escape_string($db_connection, $staff_code); // ป้องกัน SQL Injection
    $code_unit = mysqli_real_escape_string($db_connection, $code_unit); // ป้องกัน SQL Injection
    if ($overview == "1") {
        $query = "SELECT * FROM pre_project WHERE  status = '1' AND year_project = '$years_select' AND staff_insert = '$staff_code' AND status_project IN ('0','8') ORDER BY id ASC";
    } else if ($overview == "2") {
        $query = "SELECT * FROM pre_project WHERE  status = '1' AND year_project = '$years_select' AND code_unit = '$code_unit' AND status_project IN ('0','8') ORDER BY id ASC";
    } else {
        return []; // หรือ $query = "SELECT ... WHERE 1=0"; เพื่อไม่ดึงข้อมูล
    }
    $result = mysqli_query($db_connection, $query);

    $pre_project_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $pre_project_list[] = $row;
        }
    }
    return $pre_project_list; // คืนค่าเป็น array ของหลายแถว
}
function getPreProjectID($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM pre_project WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

/** สิทธิ์แก้ฟิลด์ร่างยาว (reson / result / objective) ของ pre_project */
function staffCanEditPreProjectRichFields($db_Project, $project_id, $staff_id, $staff_code = '', $staff_unit_code = '')
{
    // โหมด Admin ของฝั่ง Plan: แก้ไขได้ทุกโครงการ
    if (defined('PLAN_ADMIN') && PLAN_ADMIN) {
        return true;
    }
    $project_id = (int)$project_id;
    $staff_id = (int)$staff_id;
    if ($project_id <= 0 || $staff_id <= 0) {
        return false;
    }
    $pre = getPreProjectID($db_Project, $project_id);
    if (!$pre || !is_array($pre)) {
        return false;
    }
    // แก้ไขได้เฉพาะ: 0=ร่าง, 8=ตีกลับให้แก้ไข
    $stNow = (string)($pre['status_project'] ?? '');
    if ($stNow !== '0' && $stNow !== '8') {
        return false;
    }
    if ((int)($pre['staff_insert'] ?? 0) === $staff_id) {
        return true;
    }
    foreach (getProjectHeadList($db_Project, $project_id) as $row) {
        if ((int)($row['staff_project'] ?? 0) === $staff_id) {
            return true;
        }
    }
    foreach (getProjectStaffList($db_Project, $project_id) as $row) {
        if ((int)($row['staff_project'] ?? 0) === $staff_id) {
            return true;
        }
    }
    foreach (getProjectPersonList($db_Project, $project_id) as $row) {
        if ((int)($row['staff_project'] ?? 0) === $staff_id) {
            return true;
        }
    }
    if (isUnitAdmin($db_Project, $staff_code, $staff_unit_code, $staff_id)
        && function_exists('staffMatchesProjectUnitAdminScope')
        && staffMatchesProjectUnitAdminScope($db_Project, $pre, $staff_id, $staff_code, $staff_unit_code)) {
        return true;
    }
    return false;
}

/**
 * สิทธิ์ดูส่วนกิจกรรม/งบประมาณในหน้าจัดการโครงการ (รวมโครงการที่อนุมัติแล้ว)
 * ไม่จำกัดสถานะเหมือน staffCanEditPreProjectRichFields — ใช้กับ render tables แบบอ่านอย่างเดียว
 */
function staffCanViewPreProjectManageDetail($db_Project, $project_id, $staff_id, $staff_code = '', $staff_unit_code = '')
{
    // โหมด Admin ของฝั่ง Plan: ดูรายละเอียดได้ทุกโครงการ
    if (defined('PLAN_ADMIN') && PLAN_ADMIN) {
        return true;
    }
    $project_id = (int)$project_id;
    $staff_id = (int)$staff_id;
    if ($project_id <= 0 || $staff_id <= 0) {
        return false;
    }
    $pre = getPreProjectID($db_Project, $project_id);
    if (!$pre || !is_array($pre)) {
        return false;
    }
    if ((int)($pre['staff_insert'] ?? 0) === $staff_id) {
        return true;
    }
    foreach (getProjectHeadList($db_Project, $project_id) as $row) {
        if ((int)($row['staff_project'] ?? 0) === $staff_id) {
            return true;
        }
    }
    foreach (getProjectStaffList($db_Project, $project_id) as $row) {
        if ((int)($row['staff_project'] ?? 0) === $staff_id) {
            return true;
        }
    }
    foreach (getProjectPersonList($db_Project, $project_id) as $row) {
        if ((int)($row['staff_project'] ?? 0) === $staff_id) {
            return true;
        }
    }
    if (isUnitAdmin($db_Project, $staff_code, $staff_unit_code, $staff_id)
        && function_exists('staffMatchesProjectUnitAdminScope')
        && staffMatchesProjectUnitAdminScope($db_Project, $pre, $staff_id, $staff_code, $staff_unit_code)) {
        return true;
    }
    foreach (['operate_certifiers_json', 'operate_approvers_json', 'result_report_certifiers_json'] as $jsonCol) {
        $raw = trim((string)($pre[$jsonCol] ?? ''));
        if ($raw === '') {
            continue;
        }
        $arr = json_decode($raw, true);
        if (!is_array($arr)) {
            continue;
        }
        foreach ($arr as $it) {
            if (is_array($it) && (int)($it['staff_id'] ?? 0) === $staff_id) {
                return true;
            }
        }
    }
    return false;
}

/** ตรวจว่า Admin หน่วยงานมีสิทธิ์บนโครงการนี้ตามขอบเขตหน่วยงานหรือไม่
 *  อนุญาต: หน่วยงานหลักตรงกับหน่วยงานของตน หรือเป็นหน่วยงานร่วมของโครงการ
 */
if (!function_exists('staffMatchesProjectUnitAdminScope')) {
    function staffMatchesProjectUnitAdminScope($db_Project, array $pre, int $staff_id, string $staff_code = '', string $staff_unit_code = ''): bool
    {
        if (!$db_Project) {
            return false;
        }
        $staff_id = (int) $staff_id;
        $keys = function_exists('planCollectStaffUnitScopeKeys')
            ? planCollectStaffUnitScopeKeys($db_Project, $staff_id, $staff_unit_code)
            : [];
        $keys = array_values(array_filter(array_map('strval', $keys), static function ($k) {
            return trim($k) !== '';
        }));
        if (empty($keys)) {
            return false;
        }
        $keyMap = array_fill_keys($keys, true);

        $matchesUnitVal = static function (string $val) use ($db_Project, $keyMap, $keys): bool {
            $val = trim($val);
            if ($val === '' || $val === '99') {
                return false;
            }
            if (isset($keyMap[$val])) {
                return true;
            }
            if (function_exists('planResolvePreProjectCodeUnit')) {
                $resolved = planResolvePreProjectCodeUnit($db_Project, $val);
                if ($resolved !== '' && isset($keyMap[$resolved])) {
                    return true;
                }
            }
            $valEsc = mysqli_real_escape_string($db_Project, $val);
            $inList = [];
            foreach ($keys as $k) {
                $inList[] = "'" . mysqli_real_escape_string($db_Project, $k) . "'";
            }
            $inSql = implode(',', $inList);
            $q = @mysqli_query(
                $db_Project,
                "SELECT 1 FROM set_unit
                 WHERE status != '99'
                   AND (CAST(id AS CHAR) = '$valEsc' OR IFNULL(code,'') = '$valEsc')
                   AND (CAST(id AS CHAR) IN ($inSql) OR IFNULL(code,'') IN ($inSql))
                 LIMIT 1"
            );
            return $q && mysqli_num_rows($q) > 0;
        };

        $pu = trim((string) ($pre['code_unit'] ?? ''));
        if ($matchesUnitVal($pu)) {
            return true;
        }

        $pid = (int) ($pre['id'] ?? 0);
        if ($pid > 0 && function_exists('getProjectDepartmentList')) {
            foreach (getProjectDepartmentList($db_Project, $pid, 1) as $dep) {
                if ($matchesUnitVal((string) ($dep['department_project'] ?? ''))) {
                    return true;
                }
            }
        }
        return false;
    }
}

/**
 * ขอบรรจุโครงการ: Admin หน่วยงานทำได้เฉพาะเมื่อหน่วยของตนเป็นหน่วยงานหลักของโครงการเท่านั้น
 * (หน่วยงานร่วมอย่างเดียว ไม่สามารถขอบรรจุได้)
 */
if (!function_exists('staffMatchesProjectMainUnitForInclude')) {
    function staffMatchesProjectMainUnitForInclude($db_Project, array $pre, int $staff_id, string $staff_unit_code = ''): bool
    {
        if (!$db_Project) {
            return false;
        }
        $staff_id = (int) $staff_id;
        $pu = trim((string) ($pre['code_unit'] ?? ''));
        if ($pu === '' || $pu === '99') {
            return false;
        }
        $keys = function_exists('planCollectStaffUnitScopeKeys')
            ? planCollectStaffUnitScopeKeys($db_Project, $staff_id, $staff_unit_code)
            : [];
        $keys = array_values(array_filter(array_map('strval', $keys), static function ($k) {
            return trim($k) !== '';
        }));
        if (empty($keys)) {
            return false;
        }
        $keyMap = array_fill_keys($keys, true);
        if (isset($keyMap[$pu])) {
            return true;
        }
        if (function_exists('planResolvePreProjectCodeUnit')) {
            $resolved = planResolvePreProjectCodeUnit($db_Project, $pu);
            if ($resolved !== '' && isset($keyMap[$resolved])) {
                return true;
            }
        }
        $puEsc = mysqli_real_escape_string($db_Project, $pu);
        $inList = [];
        foreach ($keys as $k) {
            $inList[] = "'" . mysqli_real_escape_string($db_Project, $k) . "'";
        }
        $inSql = implode(',', $inList);
        $q = @mysqli_query(
            $db_Project,
            "SELECT 1 FROM set_unit
             WHERE status != '99'
               AND (CAST(id AS CHAR) = '$puEsc' OR IFNULL(code,'') = '$puEsc')
               AND (CAST(id AS CHAR) IN ($inSql) OR IFNULL(code,'') IN ($inSql))
             LIMIT 1"
        );
        return $q && mysqli_num_rows($q) > 0;
    }
}

function getProjectHeadList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM project_head WHERE project_id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getProjectStaffList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM project_staff WHERE project_id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getProjectPersonList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM project_person WHERE project_id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getProjectDepartmentList($db_connection, $id, $level)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $level = mysqli_real_escape_string($db_connection, $level); // ป้องกัน SQL Injection
    $query = "SELECT * FROM project_department WHERE project_id = '$id' AND level='$level' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getProjectBudgetList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_budget WHERE project_id = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}

/**
 * แปลงรหัสแหล่งงบ (+ ข้อความอื่น ๆ) เป็นชื่อที่แสดง — ชุดเดียวกับหน้าแจ้งผล
 */
function pm_format_budget_source_label($db_Project, $code_budget, $budget_other = ''): string
{
	$cb = trim((string)$code_budget);
	$bo = trim((string)$budget_other);
	if ($cb === '99' || $cb === '99.0') {
		return $bo;
	}
	if ($cb !== '' && $cb !== '0') {
		try {
			if (function_exists('getAddBudgetSelect')) {
				$sel = getAddBudgetSelect($db_Project);
				if (is_array($sel) || $sel instanceof Traversable) {
					foreach ($sel as $row) {
						if (!is_array($row)) {
							continue;
						}
						$name = trim((string)($row['name'] ?? ''));
						if ($name === '') {
							continue;
						}
						$id = trim((string)($row['id'] ?? ''));
						$code = trim((string)($row['code'] ?? ''));
						$mid = trim((string)($row['budget_manage_id'] ?? ''));
						if ($cb === $id || ($code !== '' && $cb === $code) || ($mid !== '' && $cb === $mid)) {
							return $name;
						}
					}
				}
			}
		} catch (Throwable $e) {
			// ไป getSetBudget ต่อ
		}
		if (function_exists('getSetBudget')) {
			try {
				$bs = getSetBudget($db_Project, $cb);
				$name = is_array($bs) ? trim((string)($bs['name'] ?? '')) : '';
				if ($name !== '') {
					return $name;
				}
			} catch (Throwable $e) {
				// ignore
			}
		}
		if ($bo !== '') {
			return $bo;
		}
		return $cb;
	}
	return $bo;
}

/**
 * ข้อความแสดง "แหล่งงบประมาณ" ให้สอดคล้องกับหน้า project_manage
 * (pre_project + project_budget + กิจกรรมงบ + รองรับแถว project_budget ที่ status ไม่ตรงกับที่คาด)
 * ห่อ cache + try/catch เพื่อกันหน้า project_approve ขาว (fatal/ข้อมูล DB ผิดรูปแบบ/โหลดรายการยาว)
 */
function resolveProjectBudgetSourceForDisplay($db_Project, $project_id)
{
	static $memo = [];
	$project_id = (int)$project_id;
	if ($project_id <= 0) {
		return '-';
	}
	if (array_key_exists($project_id, $memo)) {
		return $memo[$project_id];
	}
	try {
		$memo[$project_id] = resolveProjectBudgetSourceForDisplay_core($db_Project, $project_id);
	} catch (Throwable $e) {
		$memo[$project_id] = '-';
	}
	return $memo[$project_id];
}

/**
 * @internal
 */
function resolveProjectBudgetSourceForDisplay_core($db_Project, $project_id)
{
	$project_id = (int)$project_id;
	if ($project_id <= 0) {
		return '-';
	}
	// mysqli ในโหมด strict อาจโยน mysqli_sql_exception เมื่อคอลัมน์/SQL ไม่ตรง — ห้ามให้ลามไปถูก catch ข้างนอกแล้วได้แต่ '-'
	$mq = static function ($mysqli, $sql) {
		try {
			return @mysqli_query($mysqli, $sql);
		} catch (Throwable $e) {
			return false;
		}
	};
	static $hasActivityBudgetCodeCol = null;
	if ($hasActivityBudgetCodeCol === null) {
		$hasActivityBudgetCodeCol = false;
		$chk = $mq($db_Project, "SHOW COLUMNS FROM project_activity_budget LIKE 'code_budget'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			$hasActivityBudgetCodeCol = true;
		}
	}
	$scalarStr = static function ($v) {
		if ($v === null || is_bool($v)) {
			return '';
		}
		if (is_scalar($v)) {
			return trim((string)$v);
		}
		return '';
	};
	$cb = '';
	$bo = '';
	$qpre = $mq($db_Project, "SELECT * FROM pre_project WHERE id='" . $project_id . "' LIMIT 1");
	if ($qpre && ($pr = mysqli_fetch_assoc($qpre))) {
		if (array_key_exists('code_budget', $pr)) {
			$cb = $scalarStr($pr['code_budget']);
		}
		if (array_key_exists('budget_other', $pr)) {
			$bo = $scalarStr($pr['budget_other']);
		}
	}
	// ฟอร์มโครงการเก็บแหล่งงบใน project_budget.budget_other และมักตั้ง code_budget='99'
	// ต้องดึงมาเติมแม้ pre_project จะมีรหัส 99 อยู่แล้ว
	$needPb = ($cb === '' || $cb === '0' || (($cb === '99' || $cb === '99.0') && $bo === ''));
	if ($needPb) {
		$qpb1 = $mq(
			$db_Project,
			"SELECT code_budget, budget_other FROM project_budget WHERE project_id='" . $project_id . "' AND status='1' ORDER BY id DESC LIMIT 1"
		);
		if ($qpb1 && ($rpb = mysqli_fetch_assoc($qpb1))) {
			$tcb = $scalarStr($rpb['code_budget'] ?? '');
			$tbo = $scalarStr($rpb['budget_other'] ?? '');
			if (($cb === '' || $cb === '0') && $tcb !== '' && $tcb !== '0') {
				$cb = $tcb;
			}
			if ($bo === '' && $tbo !== '') {
				$bo = $tbo;
			}
		}
	}
	if (($cb === '' || $cb === '0' || (($cb === '99' || $cb === '99.0') && $bo === '')) && $hasActivityBudgetCodeCol) {
		$qa = $mq(
			$db_Project,
			"SELECT code_budget FROM project_activity_budget WHERE code_pre_project='" . $project_id . "' AND status!='99'
			 AND TRIM(IFNULL(code_budget,'')) NOT IN ('','0','99','99.0') LIMIT 1"
		);
		if ($qa && ($ra = mysqli_fetch_assoc($qa))) {
			$cb = $scalarStr($ra['code_budget'] ?? '');
		}
	}
	$pbList = [];
	try {
		$pbList = function_exists('getProjectBudgetList') ? getProjectBudgetList($db_Project, (string)$project_id) : [];
	} catch (Throwable $e) {
		$pbList = [];
	}
	if (!is_array($pbList)) {
		$pbList = [];
	}
	if (count($pbList) > 0) {
		for ($i = count($pbList) - 1; $i >= 0; $i--) {
			$r = $pbList[$i];
			$tcb = $scalarStr($r['code_budget'] ?? '');
			$tbo = $scalarStr($r['budget_other'] ?? '');
			if ($tcb !== '' && $tcb !== '0') {
				if ($cb === '' || $cb === '0') {
					$cb = $tcb;
				}
				if ($bo === '') {
					$bo = $tbo;
				}
				break;
			}
			if ($bo === '' && $tbo !== '') {
				$bo = $tbo;
			}
		}
		if (($cb === '' || $cb === '0') && count($pbList) > 0) {
			$last = $pbList[count($pbList) - 1];
			$tcb = $scalarStr($last['code_budget'] ?? '');
			$tbo = $scalarStr($last['budget_other'] ?? '');
			if ($cb === '' || $cb === '0') {
				$cb = $tcb;
			}
			if ($bo === '') {
				$bo = $tbo;
			}
		}
	}
	if ($bo === '' || $cb === '' || $cb === '0' || $cb === '99' || $cb === '99.0') {
		$qany = $mq(
			$db_Project,
			"SELECT code_budget, budget_other FROM project_budget WHERE project_id='" . $project_id . "' ORDER BY (status='1') DESC, id DESC LIMIT 8"
		);
		if ($qany) {
			while (($rw = mysqli_fetch_assoc($qany))) {
				$tcb = $scalarStr($rw['code_budget'] ?? '');
				$tbo = $scalarStr($rw['budget_other'] ?? '');
				if (($cb === '' || $cb === '0' || $cb === '99' || $cb === '99.0') && $tcb !== '' && $tcb !== '0' && $tcb !== '99' && $tcb !== '99.0') {
					$cb = $tcb;
				} elseif (($cb === '' || $cb === '0') && $tcb !== '' && $tcb !== '0') {
					$cb = $tcb;
				}
				if ($bo === '' && $tbo !== '') {
					$bo = $tbo;
				}
				if ($bo !== '' && $cb !== '' && $cb !== '0' && $cb !== '99' && $cb !== '99.0') {
					break;
				}
			}
		}
	}
	if (($cb === '' || $cb === '0' || $cb === '99' || $cb === '99.0') && $bo === '') {
		$qlog = $mq(
			$db_Project,
			"SELECT meta_json FROM project_plan_review_log
			 WHERE project_id='" . $project_id . "' AND action_type='notify_plan_result'
			 ORDER BY id DESC LIMIT 1"
		);
		if ($qlog && ($lr = mysqli_fetch_assoc($qlog))) {
			$meta = json_decode((string)($lr['meta_json'] ?? ''), true);
			if (is_array($meta)) {
				$tcb = $scalarStr($meta['budget_code'] ?? ($meta['code_budget'] ?? ''));
				$tbo = $scalarStr($meta['budget_other'] ?? '');
				if (($cb === '' || $cb === '0' || $cb === '99' || $cb === '99.0') && $tcb !== '' && $tcb !== '0') {
					$cb = $tcb;
				}
				if ($bo === '' && $tbo !== '') {
					$bo = $tbo;
				}
			}
		}
	}
	$label = function_exists('pm_format_budget_source_label')
		? pm_format_budget_source_label($db_Project, $cb, $bo)
		: (($bo !== '') ? $bo : $cb);
	$label = trim((string)$label);
	return $label !== '' ? $label : '-';
}

/**
 * ตาราง project_kpi ต้องมี kpi_name / kpi_unit สำหรับตัวชี้วัดที่กรอกข้อความเอง
 * (โค้ดบันทึก/แสดงผลอ้างคอลัมน์เหล่านี้ — ถ้าไม่มี ชื่อจะหายและแสดงเป็น -)
 */
function ensureProjectKpiSchema($db_Project): void
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    $cols = [
        'kpi_name' => "VARCHAR(500) NULL DEFAULT NULL COMMENT 'ชื่อตัวชี้วัด (กรอกเอง)' AFTER code_kpi",
        'kpi_unit' => "VARCHAR(200) NULL DEFAULT NULL COMMENT 'หน่วยนับ (กรอกเอง)' AFTER code_unit",
    ];
    foreach ($cols as $col => $def) {
        $esc = mysqli_real_escape_string($db_Project, $col);
        $q = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_kpi LIKE '" . $esc . "'");
        if ($q && mysqli_num_rows($q) === 0) {
            @mysqli_query($db_Project, "ALTER TABLE project_kpi ADD COLUMN `" . $col . "` " . $def);
        }
    }
}

function getProjectKPIList($db_connection, $id)
{
    if (function_exists('ensureProjectKpiSchema')) {
        ensureProjectKpiSchema($db_connection);
    }
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_kpi WHERE code_pre_project = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}

/**
 * ป้ายชื่อตัวชี้วัดเป้าหมายโครงการ (project_kpi) สำหรับสรุปขอบรรจุ
 * รูปแบบเดียวกับหน้าจัดการโครงการ ส่วนที่ 3 — ชื่อ (หน่วยนับ)
 *
 * @return string[]
 */
function pmProjectKpiDisplayLabels($db_Project, $db_KPI, $project_id)
{
    $project_id = (int) $project_id;
    if ($project_id <= 0 || !function_exists('getProjectKPIList')) {
        return [];
    }
    $labels = [];
    foreach (getProjectKPIList($db_Project, $project_id) as $pk) {
        $kpi_name_show = trim((string) ($pk['kpi_name'] ?? ''));
        $kpi_unit_show = trim((string) ($pk['kpi_unit'] ?? ''));
        if ($kpi_name_show === '' && !empty($pk['code_kpi']) && $db_KPI && function_exists('getKPI')) {
            $kpiRow = getKPI($db_KPI, $pk['code_kpi']);
            $kpi_name_show = trim((string) ($kpiRow['name'] ?? ''));
        }
        if ($kpi_unit_show === '' && !empty($pk['code_unit']) && $db_KPI && function_exists('getSetUnit')) {
            $unitRow = getSetUnit($db_KPI, $pk['code_unit']);
            $kpi_unit_show = trim((string) ($unitRow['name'] ?? ''));
        }
        $label = $kpi_name_show !== '' ? $kpi_name_show : '-';
        if ($kpi_unit_show !== '') {
            $label .= ' (' . $kpi_unit_show . ')';
        }
        if ($label !== '-' && $label !== '') {
            $labels[] = $label;
        }
    }

    return $labels;
}

function getProjectActivityList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_activity WHERE code_pre_project = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}

function getProjectActivityTargetList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_activity_target WHERE code_activity = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}

function getProjectActivityKpiList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_activity_kpi WHERE code_activity = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getProjectActivityBudgetList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_activity_budget WHERE code_activity = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getProjectActivityBudgetALL($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_activity_budget WHERE code_pre_project = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getProjectActivityBudgetDetail($db_connection, $id, $budget_item_id = 0, $allow_legacy_fallback = true)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $budget_item_id = (int)$budget_item_id;
    $result = false;

    // ตรวจว่ามีคอลัมน์รองรับการแยกรายการย่อยแล้วหรือไม่
    $has_budget_item_col = false;
    try {
        $chk = mysqli_query($db_connection, "SHOW COLUMNS FROM project_activity_budget_detail LIKE 'code_activity_budget'");
        $has_budget_item_col = ($chk && mysqli_num_rows($chk) > 0);
    } catch (Throwable $e) {
        $has_budget_item_col = false;
    }

    if ($has_budget_item_col) {
        if ($budget_item_id > 0) {
            $query = "SELECT * FROM project_activity_budget_detail WHERE code_activity = '$id' AND code_activity_budget = '$budget_item_id' AND status != '99' ORDER BY id DESC";
        } else {
            $query = "SELECT * FROM project_activity_budget_detail WHERE code_activity = '$id' AND (code_activity_budget IS NULL OR code_activity_budget = '' OR code_activity_budget = '0') AND status != '99' ORDER BY id DESC";
        }
        try {
            $result = mysqli_query($db_connection, $query);
        } catch (Throwable $e) {
            $result = false;
        }
    }

    // Fallback โครงสร้างเดิม (ยังไม่แยกรายการย่อย)
    if (!$result && $allow_legacy_fallback) {
        $fallback_query = "SELECT * FROM project_activity_budget_detail WHERE code_activity = '$id' AND status != '99' ORDER BY id DESC";
        try {
            $result = mysqli_query($db_connection, $fallback_query);
        } catch (Throwable $e) {
            $result = false;
        }
    }

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getProjectTargetGroupList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_target_groups WHERE code_pre_project = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getProjectFileList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_file WHERE code_pre_project = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}

/** เพิ่มคอลัมน์สำหรับส่วนที่ 6 (ติดตาม/ความเสี่ยง) และ stored_file สำหรับไฟล์แนบ ถ้ายังไม่มี */
function ensureProjectExtraSchema($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    $cols = [
        'eval_method' => 'TEXT',
        'eval_tools' => 'TEXT',
        'risk_expected' => 'TEXT',
        'risk_mitigation' => 'TEXT',
        'include_certifiers_json' => 'LONGTEXT',
        'operate_certifiers_json' => 'LONGTEXT',
        'operate_approvers_json' => 'LONGTEXT',
        'user_fix_note' => 'TEXT',
        'user_fix_note_at' => 'DATETIME',
        'user_fix_note_staff_id' => 'INT',
        'notify_result' => 'VARCHAR(100)',
        'notify_result_note' => 'TEXT',
        'operate_submitter_reply_note' => 'MEDIUMTEXT',
        'operate_submitter_reply_updated_at' => 'DATETIME',
        'operate_submitter_reply_staff_id' => 'INT',
        'plan_admin_operate_rechain_submitted_at' => 'DATETIME',
        'result_report_plan_closed_at' => 'DATETIME',
    ];
    foreach ($cols as $col => $type) {
        $esc = mysqli_real_escape_string($db_Project, $col);
        $q = mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE '$esc'");
        if ($q && mysqli_num_rows($q) === 0) {
            mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN `$col` $type NULL");
        }
    }
    $q2 = mysqli_query($db_Project, "SHOW COLUMNS FROM project_file LIKE 'stored_file'");
    if ($q2 && mysqli_num_rows($q2) === 0) {
        mysqli_query($db_Project, "ALTER TABLE project_file ADD COLUMN stored_file VARCHAR(255) NULL DEFAULT NULL AFTER files");
    }
    if (function_exists('pm_ensure_operate_approver_schema')) {
        pm_ensure_operate_approver_schema($db_Project);
    }
    if (function_exists('pm_ensure_show_on_leave_system_schema')) {
        pm_ensure_show_on_leave_system_schema($db_Project);
    }
    if (function_exists('pm_ensure_change_request_schema')) {
        pm_ensure_change_request_schema($db_Project);
    }
}

/** สร้างตารางบันทึกผลตัวชี้วัดโครงการ (กรอกผล) ถ้ายังไม่มี */
function ensureKpiResultSchema($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    $run = function ($sql) use ($db_Project) {
        if (!mysqli_query($db_Project, $sql)) {
            @error_log('ensureKpiResultSchema: ' . mysqli_error($db_Project));
        }
    };
    $run("CREATE TABLE IF NOT EXISTS project_kpi_result (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id INT UNSIGNED NOT NULL,
        project_kpi_id INT UNSIGNED NOT NULL,
        result_month VARCHAR(4) NOT NULL DEFAULT '',
        result_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        result_detail TEXT NULL,
        staff_insert INT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_pkr_proj (project_id),
        KEY idx_pkr_kpi (project_kpi_id),
        KEY idx_pkr_month (result_month)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $run("CREATE TABLE IF NOT EXISTS project_kpi_result_file (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        result_id INT UNSIGNED NOT NULL,
        original_name VARCHAR(255) NOT NULL DEFAULT '',
        stored_file VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_pkrf_result (result_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** ตารางผลการดำเนินกิจกรรม (ส่วนที่ 4) */
function ensureActivityResultSchema($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    $run = function ($sql) use ($db_Project) {
        if (!mysqli_query($db_Project, $sql)) {
            @error_log('ensureActivityResultSchema: ' . mysqli_error($db_Project));
        }
    };
    $run("CREATE TABLE IF NOT EXISTS project_activity_result (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id INT UNSIGNED NOT NULL,
        project_activity_id INT UNSIGNED NOT NULL,
        result_detail MEDIUMTEXT NULL,
        progress_percent DECIMAL(5,2) NULL DEFAULT NULL,
        staff_insert INT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_par_proj (project_id),
        KEY idx_par_act (project_activity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $colChk = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_activity_result LIKE 'progress_percent'");
    if (!$colChk || mysqli_num_rows($colChk) === 0) {
        @mysqli_query($db_Project, "ALTER TABLE project_activity_result ADD COLUMN progress_percent DECIMAL(5,2) NULL DEFAULT NULL AFTER result_detail");
    }
    foreach (['mon_10', 'mon_11', 'mon_12', 'mon_01', 'mon_02', 'mon_03', 'mon_04', 'mon_05', 'mon_06', 'mon_07', 'mon_08', 'mon_09'] as $monCol) {
        $colMon = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_activity_result LIKE '" . $monCol . "'");
        if (!$colMon || mysqli_num_rows($colMon) === 0) {
            @mysqli_query($db_Project, "ALTER TABLE project_activity_result ADD COLUMN " . $monCol . " TINYINT(1) NOT NULL DEFAULT 0");
        }
    }
    $run("CREATE TABLE IF NOT EXISTS project_activity_result_target (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        result_id INT UNSIGNED NOT NULL,
        code_target VARCHAR(32) NOT NULL DEFAULT '',
        result_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_part_result (result_id),
        KEY idx_part_target (code_target)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $run("CREATE TABLE IF NOT EXISTS project_activity_result_file (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        result_id INT UNSIGNED NOT NULL,
        original_name VARCHAR(255) NOT NULL DEFAULT '',
        stored_file VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_parf_result (result_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** ตารางรายงานเบิกจ่าย (ส่วนที่ 5 — โหมดรายงานผล) */
function ensureBudgetDisburseReportSchema($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    $run = function ($sql) use ($db_Project) {
        if (!mysqli_query($db_Project, $sql)) {
            @error_log('ensureBudgetDisburseReportSchema: ' . mysqli_error($db_Project));
        }
    };
    $run("CREATE TABLE IF NOT EXISTS project_budget_disburse_report (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id INT UNSIGNED NOT NULL,
        project_activity_id INT UNSIGNED NOT NULL,
        activity_detail MEDIUMTEXT NULL,
        staff_insert INT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_pbdr_proj (project_id),
        KEY idx_pbdr_act (project_activity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $run("CREATE TABLE IF NOT EXISTS project_budget_disburse_line (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        report_id INT UNSIGNED NOT NULL,
        project_activity_budget_id INT UNSIGNED NOT NULL DEFAULT 0,
        line_detail VARCHAR(500) NOT NULL DEFAULT '',
        amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        disburse_month VARCHAR(8) NOT NULL DEFAULT '',
        doc_label VARCHAR(255) NOT NULL DEFAULT '',
        doc_status VARCHAR(64) NOT NULL DEFAULT '',
        doc_url VARCHAR(512) NOT NULL DEFAULT '',
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_pbdl_report (report_id),
        KEY idx_pbdl_item (project_activity_budget_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $colActDetail = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_budget_disburse_line LIKE 'activity_detail'");
    if (!$colActDetail || mysqli_num_rows($colActDetail) === 0) {
        @mysqli_query($db_Project, "ALTER TABLE project_budget_disburse_line ADD COLUMN activity_detail MEDIUMTEXT NULL AFTER line_detail");
    }
    $colConfirmedAt = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_budget_disburse_line LIKE 'confirmed_at'");
    if (!$colConfirmedAt || mysqli_num_rows($colConfirmedAt) === 0) {
        @mysqli_query($db_Project, "ALTER TABLE project_budget_disburse_line ADD COLUMN confirmed_at DATETIME NULL DEFAULT NULL AFTER doc_url");
    }
    $colExtWid = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_budget_disburse_line LIKE 'external_withdraw_id'");
    if (!$colExtWid || mysqli_num_rows($colExtWid) === 0) {
        @mysqli_query($db_Project, "ALTER TABLE project_budget_disburse_line ADD COLUMN external_withdraw_id INT UNSIGNED NULL DEFAULT NULL AFTER confirmed_at");
    }
}

/** URL ระบบเบิกจ่ายภายนอก (เปิดเมื่อกดยืนยันและจัดทำเอกสาร) */
if (!function_exists('pm_budget_disburse_external_url')) {
	function pm_budget_disburse_external_url($project_id = 0, $line_id = 0, $report_id = 0): string
	{
		$base = '';
		if (defined('PM_DISBURSE_SYSTEM_URL') && trim((string)PM_DISBURSE_SYSTEM_URL) !== '') {
			$base = trim((string)PM_DISBURSE_SYSTEM_URL);
		} elseif (!empty($GLOBALS['pm_disburse_system_url'])) {
			$base = trim((string)$GLOBALS['pm_disburse_system_url']);
		} else {
			// ค่าเริ่มต้น: หน้า handoff เบิกจ่ายเงินโครงการ
			$base = 'https://pbm.pharmacy.cmu.ac.th/withdraw/project/from-project';
		}
		$base = rtrim($base, " \t\n\r\0\x0B");
		// ถ้าตั้งเป็นโดเมนราก ให้ต่อ path handoff อัตโนมัติ
		if (preg_match('#^https?://[^/]+/?$#i', $base)) {
			$base = rtrim($base, '/') . '/withdraw/project/from-project';
		}
		return $base;
	}
}

/** Base URL ระบบเบิกจ่าย PBM (ไม่รวม path) */
if (!function_exists('pm_pbm_withdraw_base_url')) {
	function pm_pbm_withdraw_base_url(): string
	{
		$base = '';
		if (defined('PM_DISBURSE_SYSTEM_URL') && trim((string)PM_DISBURSE_SYSTEM_URL) !== '') {
			$base = trim((string)PM_DISBURSE_SYSTEM_URL);
		} elseif (!empty($GLOBALS['pm_disburse_system_url'])) {
			$base = trim((string)$GLOBALS['pm_disburse_system_url']);
		} else {
			$base = 'https://pbm.pharmacy.cmu.ac.th';
		}
		$base = rtrim($base, " \t\n\r\0\x0B/");
		if (preg_match('#/withdraw/project/from-project$#i', $base)) {
			$base = preg_replace('#/withdraw/project/from-project$#i', '', $base);
		}
		return rtrim((string)$base, '/');
	}
}

/** ค้นหา path ไฟล์ .env (app_nanlaphat/.env) จากตำแหน่งไฟล์ปัจจุบัน */
if (!function_exists('pm_dotenv_file_paths')) {
	function pm_dotenv_file_paths(): array
	{
		static $paths = null;
		if (is_array($paths)) {
			return $paths;
		}
		$paths = [];
		$seen = [];
		$dirs = [__DIR__];
		if (!empty($_SERVER['DOCUMENT_ROOT'])) {
			$dirs[] = (string)$_SERVER['DOCUMENT_ROOT'];
		}
		foreach ($dirs as $start) {
			$dir = rtrim((string)$start, " \t\n\r\0\x0B/\\");
			for ($i = 0; $i < 8; $i++) {
				$candidate = $dir . DIRECTORY_SEPARATOR . '.env';
				if (!isset($seen[$candidate]) && is_readable($candidate)) {
					$seen[$candidate] = true;
					$paths[] = $candidate;
				}
				$parent = dirname($dir);
				if ($parent === $dir) {
					break;
				}
				$dir = $parent;
			}
		}
		return $paths;
	}
}

/** อ่านค่าจากไฟล์ .env (app_nanlaphat/.env) */
if (!function_exists('pm_dotenv_get')) {
	function pm_dotenv_get(string $key, string $default = ''): string
	{
		static $cache = [];
		$key = trim($key);
		if ($key === '') {
			return $default;
		}
		if (array_key_exists($key, $cache)) {
			return $cache[$key];
		}
		foreach (pm_dotenv_file_paths() as $envFile) {
			$lines = @file($envFile, FILE_IGNORE_NEW_LINES);
			if (!is_array($lines)) {
				continue;
			}
			foreach ($lines as $line) {
				$line = trim((string)$line);
				if ($line === '' || $line[0] === '#') {
					continue;
				}
				if (strpos($line, '=') === false) {
					continue;
				}
				[$k, $v] = explode('=', $line, 2);
				$k = trim($k);
				if ($k !== $key) {
					continue;
				}
				$v = trim($v);
				if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
					$q = $v[0];
					if (strlen($v) >= 2 && substr($v, -1) === $q) {
						$v = substr($v, 1, -1);
					}
				}
				$cache[$key] = $v;
				return $v;
			}
		}
		$cache[$key] = $default;
		return $default;
	}
}

/** Secret ร่วมกับระบบ withdraw (PBM_BRIDGE_SECRET) — อ่านจาก app_nanlaphat/.env */
if (!function_exists('pm_pbm_bridge_secret')) {
	function pm_pbm_bridge_secret(): string
	{
		if (defined('PBM_BRIDGE_SECRET') && trim((string)PBM_BRIDGE_SECRET) !== '') {
			return trim((string)PBM_BRIDGE_SECRET);
		}
		return pm_dotenv_get('PBM_BRIDGE_SECRET');
	}
}

/** ลายเซ็น HMAC สำหรับ API external/withdraws */
if (!function_exists('pm_pbm_withdraw_external_signature')) {
	function pm_pbm_withdraw_external_signature(string $projectCode, string $secret): string
	{
		return hash_hmac('sha256', trim($projectCode), hash('sha256', $secret));
	}
}

/**
 * ดึงรายการเบิกจ่ายจากระบบ withdraw ตาม project_code
 *
 * @return array{ok:int,msg?:string,data?:array,meta?:array,http_code?:int}
 */
if (!function_exists('pm_pbm_fetch_external_withdraws')) {
	function pm_pbm_fetch_external_withdraws(string $projectCode): array
	{
		$projectCode = trim($projectCode);
		if ($projectCode === '') {
			return ['ok' => 0, 'msg' => 'ไม่พบรหัสโครงการ'];
		}
		$secret = pm_pbm_bridge_secret();
		if ($secret === '') {
			return ['ok' => 0, 'msg' => 'ยังไม่ได้ตั้งค่า PBM_BRIDGE_SECRET ใน app_nanlaphat/.env'];
		}
		$sig = pm_pbm_withdraw_external_signature($projectCode, $secret);
		$base = pm_pbm_withdraw_base_url();
		$url = $base . '/withdraw/api/project/external/withdraws'
			. '?project_code=' . rawurlencode($projectCode)
			. '&sig=' . rawurlencode($sig);
		$body = '';
		$httpCode = 0;
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_CONNECTTIMEOUT => 8,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HTTPHEADER => ['Accept: application/json'],
				CURLOPT_USERAGENT => 'eproject/withdraw-bridge',
			]);
			$body = (string)curl_exec($ch);
			$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
		} else {
			$ctx = stream_context_create([
				'http' => [
					'method' => 'GET',
					'timeout' => 30,
					'header' => "Accept: application/json\r\n",
				],
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
				],
			]);
			$body = (string)@file_get_contents($url, false, $ctx);
			if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $m)) {
				$httpCode = (int)$m[1];
			}
		}
		$decoded = json_decode($body, true);
		if (!is_array($decoded)) {
			return [
				'ok' => 0,
				'msg' => 'ไม่สามารถอ่านข้อมูลจากระบบเบิกจ่ายได้',
				'http_code' => $httpCode,
			];
		}
		if ($httpCode === 403 || (($decoded['status'] ?? '') === 'error' && stripos((string)($decoded['message'] ?? ''), 'ลายเซ็น') !== false)) {
			return ['ok' => 0, 'msg' => (string)($decoded['message'] ?? 'ลายเซ็นคำขอไม่ถูกต้อง'), 'http_code' => $httpCode];
		}
		if ($httpCode >= 400) {
			return ['ok' => 0, 'msg' => (string)($decoded['message'] ?? 'เรียก API ระบบเบิกจ่ายไม่สำเร็จ'), 'http_code' => $httpCode];
		}
		if (($decoded['status'] ?? '') !== 'success') {
			return ['ok' => 0, 'msg' => (string)($decoded['message'] ?? 'เรียก API ระบบเบิกจ่ายไม่สำเร็จ'), 'http_code' => $httpCode];
		}
		$data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : [];
		return [
			'ok' => 1,
			'data' => $data,
			'meta' => is_array($decoded['meta'] ?? null) ? $decoded['meta'] : [],
			'http_code' => $httpCode,
		];
	}
}

/**
 * ใบเบิกจ่ายที่เป็นแค่ร่าง / saved (ยังไม่ได้ส่งจริงในระบบเบิกจ่าย)
 */
if (!function_exists('pm_pbm_withdraw_is_draft_only')) {
	function pm_pbm_withdraw_is_draft_only(array $withdraw): bool
	{
		$raw = strtolower(trim((string)($withdraw['raw_status'] ?? '')));
		$st = trim((string)($withdraw['status'] ?? ''));
		if ($raw === 'saved' || $raw === 'draft') {
			return true;
		}
		if ($st === '') {
			return false;
		}
		$pos = function_exists('mb_strpos') ? mb_strpos($st, 'ร่าง') : strpos($st, 'ร่าง');
		return $pos !== false;
	}
}

/** รายละเอียดรายการที่มีความหมาย (ไม่ใช่ค่าว่าง / ครั้งที่ N) */
if (!function_exists('pm_pbm_line_detail_is_meaningful')) {
	function pm_pbm_line_detail_is_meaningful(string $detail): bool
	{
		$detail = trim($detail);
		if ($detail === '') {
			return false;
		}
		return !preg_match('/^ครั้งที่\s*\d+$/u', $detail);
	}
}

/** ตรวจว่าชื่อรายการย่อยในใบเบิกจ่าย ตรงกับรายละเอียดในโครงการหรือไม่ */
if (!function_exists('pm_pbm_withdraw_items_contain_detail')) {
	function pm_pbm_withdraw_items_contain_detail(array $withdraw, string $detail): bool
	{
		$detail = trim($detail);
		if ($detail === '') {
			return false;
		}
		$items = is_array($withdraw['items'] ?? null) ? $withdraw['items'] : [];
		foreach ($items as $it) {
			if (!is_array($it)) {
				continue;
			}
			$name = trim((string)($it['name'] ?? ''));
			if ($name === '') {
				continue;
			}
			$inName = function_exists('mb_strpos') ? mb_strpos($name, $detail) : strpos($name, $detail);
			if ($inName !== false) {
				return true;
			}
			$nameLen = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
			if ($nameLen >= 2) {
				$inDetail = function_exists('mb_strpos') ? mb_strpos($detail, $name) : strpos($detail, $name);
				if ($inDetail !== false) {
					return true;
				}
			}
		}
		return false;
	}
}

/**
 * ตรวจว่าใบเบิกจ่าย “เข้ากัน” กับรายการ/ชุดรายการในโครงการ
 * ถ้ามีรายละเอียดที่มีความหมาย ต้องเจอใน items — ไม่ยอมจับคู่แค่ยอดเงิน
 *
 * @param list<array> $lines
 */
if (!function_exists('pm_pbm_withdraw_fits_lines')) {
	function pm_pbm_withdraw_fits_lines(array $withdraw, array $lines): bool
	{
		$lines = array_values(array_filter($lines, static function ($ln) {
			return is_array($ln);
		}));
		if ($lines === []) {
			return false;
		}
		$items = is_array($withdraw['items'] ?? null) ? $withdraw['items'] : [];
		$wAmt = round((float)($withdraw['amount'] ?? 0), 2);
		$needAmts = [];
		$batchAmt = 0.0;
		$meaningful = [];
		foreach ($lines as $ln) {
			$amt = round((float)($ln['amount'] ?? 0), 2);
			$batchAmt += $amt;
			if ($amt > 0) {
				$needAmts[] = $amt;
			}
			$d = trim((string)($ln['line_detail'] ?? ''));
			if (pm_pbm_line_detail_is_meaningful($d)) {
				$meaningful[] = $d;
			}
		}
		$batchAmt = round($batchAmt, 2);

		if ($meaningful !== []) {
			foreach ($meaningful as $d) {
				if (!pm_pbm_withdraw_items_contain_detail($withdraw, $d)) {
					return false;
				}
			}
			return true;
		}

		if (count($lines) > 1) {
			if ($batchAmt > 0 && abs($wAmt - $batchAmt) < 0.01) {
				return true;
			}
			if ($needAmts === [] || $items === []) {
				return false;
			}
			$itemAmts = [];
			foreach ($items as $it) {
				if (!is_array($it)) {
					continue;
				}
				$ia = round((float)($it['amount'] ?? 0), 2);
				if ($ia > 0) {
					$itemAmts[] = $ia;
				}
			}
			if (count($itemAmts) < count($needAmts)) {
				return false;
			}
			foreach ($needAmts as $needAmt) {
				$found = false;
				foreach ($itemAmts as $ii => $ia) {
					if (abs($ia - $needAmt) < 0.01) {
						unset($itemAmts[$ii]);
						$itemAmts = array_values($itemAmts);
						$found = true;
						break;
					}
				}
				if (!$found) {
					return false;
				}
			}
			return true;
		}

		$lineAmt = $needAmts[0] ?? 0.0;
		if ($lineAmt > 0 && abs($wAmt - $lineAmt) < 0.01) {
			return true;
		}
		foreach ($items as $it) {
			if (!is_array($it)) {
				continue;
			}
			if (abs(round((float)($it['amount'] ?? 0), 2) - $lineAmt) < 0.01) {
				return true;
			}
		}
		return false;
	}
}

/** ล้าง / อัปเดต external_withdraw_id ของรายการ */
if (!function_exists('pm_pbm_set_line_external_withdraw_id')) {
	function pm_pbm_set_line_external_withdraw_id($db_Project, int $lineId, ?int $withdrawId): void
	{
		if (!$db_Project || $lineId <= 0) {
			return;
		}
		if ($withdrawId === null || $withdrawId <= 0) {
			@mysqli_query(
				$db_Project,
				"UPDATE project_budget_disburse_line SET external_withdraw_id=NULL WHERE id='" . $lineId . "' LIMIT 1"
			);
			return;
		}
		@mysqli_query(
			$db_Project,
			"UPDATE project_budget_disburse_line SET external_withdraw_id='" . (int)$withdrawId . "' WHERE id='" . $lineId . "' LIMIT 1"
		);
	}
}

/** จับคู่รายการเบิกจ่ายในระบบโครงการกับรายการจาก withdraw */
if (!function_exists('pm_pbm_match_external_withdraw_for_line')) {
	function pm_pbm_match_external_withdraw_for_line(array $withdraws, array $lineRow, array $batchLines = null): ?array
	{
		if (empty($withdraws)) {
			return null;
		}
		$batch = [];
		if (is_array($batchLines) && !empty($batchLines)) {
			foreach ($batchLines as $bl) {
				if (is_array($bl)) {
					$batch[] = $bl;
				}
			}
		}
		if ($batch === []) {
			$batch = [$lineRow];
		}
		$extId = (int)($lineRow['external_withdraw_id'] ?? 0);
		if ($extId <= 0) {
			foreach ($batch as $bl) {
				$eid = (int)($bl['external_withdraw_id'] ?? 0);
				if ($eid > 0) {
					$extId = $eid;
					break;
				}
			}
		}
		if ($extId > 0) {
			foreach ($withdraws as $w) {
				if (!is_array($w)) {
					continue;
				}
				if ((int)($w['id'] ?? 0) === $extId) {
					if (pm_pbm_withdraw_fits_lines($w, $batch)) {
						return $w;
					}
					break;
				}
			}
		}
		$candidates = [];
		foreach ($withdraws as $w) {
			if (!is_array($w)) {
				continue;
			}
			if (pm_pbm_withdraw_fits_lines($w, $batch)) {
				$candidates[] = $w;
			}
		}
		if ($candidates === []) {
			return null;
		}
		if (count($candidates) === 1) {
			return $candidates[0];
		}
		$wantN = count($batch);
		usort($candidates, static function ($a, $b) use ($wantN) {
			$na = is_array($a['items'] ?? null) ? count($a['items']) : 0;
			$nb = is_array($b['items'] ?? null) ? count($b['items']) : 0;
			$da = abs($na - $wantN);
			$db = abs($nb - $wantN);
			if ($da !== $db) {
				return $da <=> $db;
			}
			return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
		});
		return $candidates[0];
	}
}

/**
 * โหลดรายการที่ยืนยันพร้อมกัน (batch) จาก confirmed_at + report_id
 *
 * @return list<array>
 */
if (!function_exists('pm_load_confirmed_disburse_batch_siblings')) {
	function pm_load_confirmed_disburse_batch_siblings($db_Project, array $lineRow): array
	{
		$lineId = (int)($lineRow['id'] ?? 0);
		$reportId = (int)($lineRow['report_id'] ?? 0);
		$confirmedAt = trim((string)($lineRow['confirmed_at'] ?? ''));
		if ($lineId <= 0 || $reportId <= 0 || $confirmedAt === '' || !$db_Project) {
			return [$lineRow];
		}
		$sql = "SELECT l.*, r.project_id, r.project_activity_id, r.activity_detail AS report_activity_detail
		        FROM project_budget_disburse_line l
		        INNER JOIN project_budget_disburse_report r ON r.id = l.report_id
		        WHERE l.report_id = '" . $reportId . "'
		          AND l.status = '1' AND r.status = '1'
		          AND l.doc_status = 'confirmed'
		          AND l.confirmed_at = '" . mysqli_real_escape_string($db_Project, $confirmedAt) . "'
		        ORDER BY l.id ASC";
		$q = @mysqli_query($db_Project, $sql);
		$rows = [];
		if ($q) {
			while ($row = mysqli_fetch_assoc($q)) {
				$rows[] = $row;
			}
		}
		return $rows !== [] ? $rows : [$lineRow];
	}
}

/**
 * จับคู่หลายรายการเบิกจ่ายกับ withdraw (ไม่ใช้ withdraw ซ้ำ)
 * รองรับชุดที่ยืนยันพร้อมกันหลายรายการ (batch ตาม confirmed_at)
 *
 * @param list<array> $withdraws
 * @param list<array> $lines
 * @return array<int,array> map line_id => withdraw
 */
if (!function_exists('pm_pbm_match_external_withdraws_for_lines')) {
	function pm_pbm_match_external_withdraws_for_lines(array $withdraws, array $lines): array
	{
		$pool = [];
		foreach ($withdraws as $w) {
			if (!is_array($w)) {
				continue;
			}
			$wid = (int)($w['id'] ?? 0);
			if ($wid > 0) {
				$pool[$wid] = $w;
			}
		}
		$map = [];
		$confirmed = [];
		foreach ($lines as $ln) {
			if (!is_array($ln)) {
				continue;
			}
			$lineId = (int)($ln['id'] ?? 0);
			if ($lineId <= 0) {
				continue;
			}
			if (trim((string)($ln['doc_status'] ?? '')) !== 'confirmed') {
				continue;
			}
			$confirmed[$lineId] = $ln;
		}

		// จัดชุดตาม confirmed_at + report_id (รายการที่ยืนยันพร้อมกัน)
		$batches = [];
		foreach ($confirmed as $lineId => $ln) {
			$confirmedAt = trim((string)($ln['confirmed_at'] ?? ''));
			$reportId = (int)($ln['report_id'] ?? 0);
			if ($confirmedAt !== '' && $reportId > 0) {
				$key = $confirmedAt . '|' . $reportId;
			} else {
				$key = 'single:' . $lineId;
			}
			if (!isset($batches[$key])) {
				$batches[$key] = [];
			}
			$batches[$key][] = $ln;
		}

		$assignBatch = static function (array $batchLines, array $w) use (&$map, &$pool) {
			$wid = (int)($w['id'] ?? 0);
			foreach ($batchLines as $bl) {
				$lineId = (int)($bl['id'] ?? 0);
				if ($lineId > 0) {
					$map[$lineId] = $w;
				}
			}
			if ($wid > 0) {
				unset($pool[$wid]);
			}
		};

		foreach ($batches as $key => $batchLines) {
			// 1) มี external_withdraw_id ในชุด → ใช้เฉพาะเมื่อใบนั้น fits กับชุด
			$extWid = 0;
			foreach ($batchLines as $bl) {
				$eid = (int)($bl['external_withdraw_id'] ?? 0);
				if ($eid <= 0) {
					continue;
				}
				if (isset($pool[$eid]) && pm_pbm_withdraw_fits_lines($pool[$eid], $batchLines)) {
					$extWid = $eid;
					break;
				}
				// ใบถูกใช้ไปแล้วในชุดอื่น — หาจากรายการ withdraw เดิม (ยังต้อง fits)
				if (!isset($pool[$eid])) {
					foreach ($withdraws as $w0) {
						if (is_array($w0) && (int)($w0['id'] ?? 0) === $eid && pm_pbm_withdraw_fits_lines($w0, $batchLines)) {
							$assignBatch($batchLines, $w0);
							$extWid = -1;
							break 2;
						}
					}
				}
			}
			if ($extWid > 0 && isset($pool[$extWid])) {
				$assignBatch($batchLines, $pool[$extWid]);
				continue;
			}
			if ($extWid === -1) {
				continue;
			}

			// 2) จับคู่จาก fits (รายละเอียด / ยอดชุด / ยอดรายการ)
			$candidates = [];
			foreach ($pool as $wid => $w) {
				if (pm_pbm_withdraw_fits_lines($w, $batchLines)) {
					$candidates[$wid] = $w;
				}
			}
			if ($candidates !== []) {
				$wantN = count($batchLines);
				uksort($candidates, static function ($a, $b) use ($candidates, $wantN) {
					$na = is_array($candidates[$a]['items'] ?? null) ? count($candidates[$a]['items']) : 0;
					$nb = is_array($candidates[$b]['items'] ?? null) ? count($candidates[$b]['items']) : 0;
					$da = abs($na - $wantN);
					$db = abs($nb - $wantN);
					if ($da !== $db) {
						return $da <=> $db;
					}
					return (int)$b <=> (int)$a;
				});
				$wid = (int)array_key_first($candidates);
				$assignBatch($batchLines, $candidates[$wid]);
				continue;
			}

			// 3) รายการในชุดที่ยังไม่จับคู่ — จับทีละรายการ (ไม่ใช้ใบสุดท้ายใน pool แบบสุ่ม)
			foreach ($batchLines as $ln) {
				$lineId = (int)($ln['id'] ?? 0);
				if ($lineId <= 0 || isset($map[$lineId])) {
					continue;
				}
				$lineCands = [];
				foreach ($pool as $wid => $w) {
					if (pm_pbm_withdraw_fits_lines($w, [$ln])) {
						$lineCands[$wid] = $w;
					}
				}
				if (count($lineCands) === 1) {
					$wid = (int)array_key_first($lineCands);
					$map[$lineId] = $lineCands[$wid];
					unset($pool[$wid]);
				} elseif (count($lineCands) > 1) {
					// มีรายละเอียดที่ตรงจริงเท่านั้น (fits แล้ว) — เลือกใบล่าสุด
					krsort($lineCands);
					$wid = (int)array_key_first($lineCands);
					$map[$lineId] = $lineCands[$wid];
					unset($pool[$wid]);
				}
			}
		}
		return $map;
	}
}

/**
 * นับความคืบหน้าลำดับอนุมัติจาก approval_steps
 *
 * @return array{done:int,total:int}
 */
if (!function_exists('pm_pbm_approval_progress')) {
	function pm_pbm_approval_progress(array $withdraw): array
	{
		$steps = isset($withdraw['approval_steps']) && is_array($withdraw['approval_steps'])
			? $withdraw['approval_steps']
			: [];
		$total = 0;
		$done = 0;
		foreach ($steps as $s) {
			if (!is_array($s)) {
				continue;
			}
			$st = strtolower(trim((string)($s['status'] ?? '')));
			if ($st === 'skipped') {
				continue;
			}
			$total++;
			if ($st === 'approved') {
				$done++;
			}
		}
		return ['done' => $done, 'total' => $total];
	}
}

/**
 * ข้อความสถานะสำหรับแสดงในปุ่มเอกสารเบิกจ่าย
 * สถานะรอตรวจสอบ/รออนุมัติ → แสดงข้อความ + ความคืบหน้า เช่น รอตรวจสอบ 1/4
 */
if (!function_exists('pm_pbm_format_withdraw_status_label')) {
	function pm_pbm_format_withdraw_status_label(array $withdraw): string
	{
		$st = trim((string)($withdraw['status'] ?? ''));
		$raw = strtolower(trim((string)($withdraw['raw_status'] ?? '')));
		if ($st === '' && $raw !== '') {
			$st = $raw;
		}
		$isPendingReview = (
			$raw !== ''
			&& strpos($raw, 'pending') === 0
			&& $raw !== 'pending_revision'
		) || (
			$st !== ''
			&& (
				(function_exists('mb_strpos') ? mb_strpos($st, 'รอตรวจสอบ') : strpos($st, 'รอตรวจสอบ')) !== false
				|| (function_exists('mb_strpos') ? mb_strpos($st, 'รออนุมัติ') : strpos($st, 'รออนุมัติ')) !== false
			)
		);
		if ($isPendingReview) {
			$prog = pm_pbm_approval_progress($withdraw);
			$prefix = $st;
			// ย่อข้อความยาว เช่น "รอตรวจสอบ/อนุมัติ" → "รอตรวจสอบ"
			if (
				(function_exists('mb_strpos') ? mb_strpos($st, 'รอตรวจสอบ') : strpos($st, 'รอตรวจสอบ')) !== false
			) {
				$prefix = 'รอตรวจสอบ';
			} elseif (
				(function_exists('mb_strpos') ? mb_strpos($st, 'รออนุมัติ') : strpos($st, 'รออนุมัติ')) !== false
			) {
				$prefix = 'รออนุมัติ';
			} elseif ($raw === 'pending_approval') {
				$prefix = 'รออนุมัติ';
			} elseif ($raw !== '' && strpos($raw, 'pending') === 0) {
				$prefix = 'รอตรวจสอบ';
			}
			if ($prog['total'] > 0) {
				return $prefix . ' ' . (string)$prog['done'] . '/' . (string)$prog['total'];
			}
			return $prefix;
		}
		return $st !== '' ? $st : 'ยืนยันแล้ว';
	}
}

/**
 * ดึงรายการ handoff ค้างจากระบบเบิกจ่าย (pending-confirms)
 *
 * @return array{ok:int,msg?:string,data?:array,meta?:array}
 */
if (!function_exists('pm_pbm_fetch_pending_confirms')) {
	function pm_pbm_fetch_pending_confirms(string $projectCode = '', string $staffId = ''): array
	{
		$projectCode = trim($projectCode);
		$staffId = trim($staffId);
		$secret = pm_pbm_bridge_secret();
		if ($secret === '') {
			return ['ok' => 0, 'msg' => 'ยังไม่ได้ตั้งค่า PBM_BRIDGE_SECRET ใน app_nanlaphat/.env', 'data' => []];
		}
		$signBase = $projectCode !== '' ? $projectCode : ($staffId !== '' ? $staffId : 'pending');
		$sig = pm_pbm_withdraw_external_signature($signBase, $secret);
		$query = http_build_query(array_filter([
			'project_code' => $projectCode !== '' ? $projectCode : null,
			'staff_id' => $staffId !== '' ? $staffId : null,
			'sig' => $sig,
		], static function ($v) {
			return $v !== null && $v !== '';
		}));
		$base = pm_pbm_withdraw_base_url();
		$url = rtrim($base, '/') . '/withdraw/api/project/external/pending-confirms?' . $query;
		$body = '';
		$httpCode = 0;
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_CONNECTTIMEOUT => 8,
				CURLOPT_TIMEOUT => 12,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HTTPHEADER => ['Accept: application/json'],
				CURLOPT_USERAGENT => 'eproject/pending-confirms',
			]);
			$body = (string)curl_exec($ch);
			$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
		} else {
			$ctx = stream_context_create([
				'http' => [
					'method' => 'GET',
					'timeout' => 12,
					'header' => "Accept: application/json\r\n",
				],
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
				],
			]);
			$body = (string)@file_get_contents($url, false, $ctx);
			if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $m)) {
				$httpCode = (int)$m[1];
			}
		}
		$decoded = json_decode($body, true);
		if (!is_array($decoded) || ($decoded['status'] ?? '') !== 'success') {
			return [
				'ok' => 0,
				'msg' => (string)($decoded['message'] ?? 'ดึงรายการค้างจากระบบเบิกจ่ายไม่สำเร็จ'),
				'data' => [],
				'http_code' => $httpCode,
			];
		}
		$data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : [];
		return [
			'ok' => 1,
			'data' => $data,
			'meta' => is_array($decoded['meta'] ?? null) ? $decoded['meta'] : [],
			'http_code' => $httpCode,
		];
	}
}

/**
 * โหลดรายการยืนยันเบิกจ่ายจากรายงานการใช้จ่ายของโครงการ
 *
 * @return list<array>
 */
if (!function_exists('pm_load_confirmed_disburse_lines_for_project')) {
	function pm_load_confirmed_disburse_lines_for_project($db_Project, int $project_id): array
	{
		$project_id = (int)$project_id;
		if ($project_id <= 0 || !$db_Project) {
			return [];
		}
		if (function_exists('ensureBudgetDisburseReportSchema')) {
			ensureBudgetDisburseReportSchema($db_Project);
		}
		$sql = "SELECT l.id, l.report_id, l.project_activity_budget_id, l.line_detail, l.activity_detail,
		               l.amount, l.disburse_month, l.doc_label, l.doc_status, l.doc_url,
		               l.confirmed_at, l.external_withdraw_id, l.sort_order,
		               r.project_activity_id, r.activity_detail AS report_activity_detail
		        FROM project_budget_disburse_line l
		        INNER JOIN project_budget_disburse_report r ON r.id = l.report_id
		        WHERE r.project_id = '" . $project_id . "'
		          AND r.status = '1' AND l.status = '1'
		          AND l.doc_status = 'confirmed'
		        ORDER BY l.confirmed_at DESC, l.id DESC";
		$q = @mysqli_query($db_Project, $sql);
		$rows = [];
		if ($q) {
			while ($row = mysqli_fetch_assoc($q)) {
				$rows[] = $row;
			}
		}
		return $rows;
	}
}

/**
 * รวมสถานะรายการยืนยันในโครงการ กับ withdraw / pending-confirm
 *
 * @return list<array>
 */
if (!function_exists('pm_reconcile_confirmed_disburse_lines')) {
	function pm_reconcile_confirmed_disburse_lines(
		$db_Project,
		int $project_id,
		string $projectCode = '',
		?array $withdraws = null,
		?array $pendingConfirms = null
	): array {
		$project_id = (int)$project_id;
		if ($project_id <= 0) {
			return [];
		}
		if ($projectCode === '') {
			$pre = function_exists('getPreProjectID') ? getPreProjectID($db_Project, $project_id) : null;
			$projectCode = is_array($pre) ? trim((string)($pre['code_pre_project'] ?? '')) : '';
			if ($projectCode === '') {
				$projectCode = 'P' . $project_id;
			}
		}
		$lines = pm_load_confirmed_disburse_lines_for_project($db_Project, $project_id);
		if (empty($lines)) {
			return [];
		}
		if ($withdraws === null) {
			$apiRes = function_exists('pm_pbm_fetch_external_withdraws')
				? pm_pbm_fetch_external_withdraws($projectCode)
				: ['ok' => 0, 'data' => []];
			$withdraws = (!empty($apiRes['ok']) && is_array($apiRes['data'] ?? null)) ? $apiRes['data'] : [];
		}
		if ($pendingConfirms === null) {
			$pendRes = function_exists('pm_pbm_fetch_pending_confirms')
				? pm_pbm_fetch_pending_confirms($projectCode, '')
				: ['ok' => 0, 'data' => []];
			$pendingConfirms = (!empty($pendRes['ok']) && is_array($pendRes['data'] ?? null)) ? $pendRes['data'] : [];
		}
		$matched = function_exists('pm_pbm_match_external_withdraws_for_lines')
			? pm_pbm_match_external_withdraws_for_lines($withdraws, $lines)
			: [];
		$actNames = [];
		$acts = function_exists('getProjectActivityList') ? getProjectActivityList($db_Project, $project_id) : [];
		foreach ($acts as $a) {
			$aid = (int)($a['id'] ?? 0);
			if ($aid > 0) {
				$actNames[$aid] = trim((string)($a['activity_name'] ?? ''));
			}
		}
		$defaultResume = '';
		$defaultToken = '';
		foreach ($pendingConfirms as $pc) {
			if (!is_array($pc)) {
				continue;
			}
			$url = trim((string)($pc['resume_url'] ?? ''));
			if ($url !== '') {
				$defaultResume = $url;
				$defaultToken = trim((string)($pc['token'] ?? ''));
				break;
			}
		}
		$out = [];
		foreach ($lines as $ln) {
			$lineId = (int)($ln['id'] ?? 0);
			$actId = (int)($ln['project_activity_id'] ?? 0);
			$row = [
				'line_id' => $lineId,
				'report_id' => (int)($ln['report_id'] ?? 0),
				'project_id' => $project_id,
				'project_code' => $projectCode,
				'activity_id' => $actId,
				'activity_name' => $actNames[$actId] ?? '',
				'project_activity_budget_id' => (int)($ln['project_activity_budget_id'] ?? 0),
				'line_detail' => (string)($ln['line_detail'] ?? ''),
				'activity_detail' => (string)(($ln['activity_detail'] ?? '') !== '' ? $ln['activity_detail'] : ($ln['report_activity_detail'] ?? '')),
				'amount' => round((float)($ln['amount'] ?? 0), 2),
				'disburse_month' => (string)($ln['disburse_month'] ?? ''),
				'confirmed_at' => (string)($ln['confirmed_at'] ?? ''),
				'external_withdraw_id' => (int)($ln['external_withdraw_id'] ?? 0),
				'withdraw_id' => 0,
				'withdraw_status' => '',
				'withdraw_raw_status' => '',
				'resume_url' => '',
				'pending_token' => '',
				'reconcile' => 'confirmed_no_withdraw',
			];
			if ($lineId > 0 && isset($matched[$lineId]) && is_array($matched[$lineId])) {
				$w = $matched[$lineId];
				$wid = (int)($w['id'] ?? 0);
				$row['withdraw_id'] = $wid;
				$row['external_withdraw_id'] = $wid;
				$storedExt = (int)($ln['external_withdraw_id'] ?? 0);
				if ($wid > 0 && $storedExt !== $wid && function_exists('pm_pbm_set_line_external_withdraw_id')) {
					pm_pbm_set_line_external_withdraw_id($db_Project, $lineId, $wid);
				}
				$row['withdraw_status'] = function_exists('pm_pbm_format_withdraw_status_label')
					? pm_pbm_format_withdraw_status_label($w)
					: (string)($w['status'] ?? '');
				$row['withdraw_raw_status'] = (string)($w['raw_status'] ?? '');
				$row['reconcile'] = 'has_withdraw';
				// ร่าง: ใส่ลิงก์เปิดใบร่างต่อได้
				if (function_exists('pm_pbm_withdraw_is_draft_only') && pm_pbm_withdraw_is_draft_only($w)) {
					if ($wid > 0 && function_exists('pm_pbm_withdraw_system_view_url')) {
						$row['resume_url'] = pm_pbm_withdraw_system_view_url($projectCode, $wid, $w);
					} elseif ($defaultResume !== '') {
						$row['resume_url'] = $defaultResume;
						$row['pending_token'] = $defaultToken;
					}
				}
			} elseif ($defaultResume !== '') {
				$storedExt = (int)($ln['external_withdraw_id'] ?? 0);
				if ($storedExt > 0 && function_exists('pm_pbm_set_line_external_withdraw_id')) {
					pm_pbm_set_line_external_withdraw_id($db_Project, $lineId, null);
					$row['external_withdraw_id'] = 0;
				}
				$row['reconcile'] = 'pending_handoff';
				$row['withdraw_status'] = 'รอบันทึกในระบบเบิกจ่าย';
				$row['withdraw_raw_status'] = 'pending_handoff';
				$row['resume_url'] = $defaultResume;
				$row['pending_token'] = $defaultToken;
			} else {
				$storedExt = (int)($ln['external_withdraw_id'] ?? 0);
				if ($storedExt > 0 && function_exists('pm_pbm_set_line_external_withdraw_id')) {
					pm_pbm_set_line_external_withdraw_id($db_Project, $lineId, null);
					$row['external_withdraw_id'] = 0;
				}
				$row['withdraw_status'] = 'ยืนยันแล้ว';
				$row['withdraw_raw_status'] = 'confirmed_local';
			}
			$out[] = $row;
		}
		return $out;
	}
}

/**
 * เติมสถานะเบิกจ่ายจากระบบ withdraw ลงใน budget report data
 */
if (!function_exists('pm_enrich_budget_report_withdraw_status')) {
	function pm_enrich_budget_report_withdraw_status($db_Project, int $project_id, array $data): array
	{
		$project_id = (int)$project_id;
		if ($project_id <= 0 || empty($data['activities']) || !is_array($data['activities'])) {
			return $data;
		}
		$pre = function_exists('getPreProjectID') ? getPreProjectID($db_Project, $project_id) : null;
		$projectCode = is_array($pre) ? trim((string)($pre['code_pre_project'] ?? '')) : '';
		if ($projectCode === '') {
			$projectCode = 'P' . $project_id;
		}
		$withdraws = [];
		if (function_exists('pm_pbm_fetch_external_withdraws')) {
			$apiRes = pm_pbm_fetch_external_withdraws($projectCode);
			if (!empty($apiRes['ok']) && is_array($apiRes['data'] ?? null)) {
				$withdraws = $apiRes['data'];
			}
		}
		$pendingConfirms = [];
		if (function_exists('pm_pbm_fetch_pending_confirms')) {
			$pendRes = pm_pbm_fetch_pending_confirms($projectCode, '');
			if (!empty($pendRes['ok']) && is_array($pendRes['data'] ?? null)) {
				$pendingConfirms = $pendRes['data'];
			}
		}
		$allLines = [];
		foreach ($data['activities'] as $act) {
			$items = is_array($act['items'] ?? null) ? $act['items'] : [];
			foreach ($items as $it) {
				$lines = is_array($it['lines'] ?? null) ? $it['lines'] : [];
				foreach ($lines as $ln) {
					if (is_array($ln) && (int)($ln['id'] ?? 0) > 0) {
						$allLines[] = $ln;
					}
				}
			}
		}
		$matched = (!empty($withdraws) && function_exists('pm_pbm_match_external_withdraws_for_lines'))
			? pm_pbm_match_external_withdraws_for_lines($withdraws, $allLines)
			: [];
		$defaultResume = '';
		$defaultToken = '';
		foreach ($pendingConfirms as $pc) {
			if (!is_array($pc)) {
				continue;
			}
			$url = trim((string)($pc['resume_url'] ?? ''));
			if ($url !== '') {
				$defaultResume = $url;
				$defaultToken = trim((string)($pc['token'] ?? ''));
				break;
			}
		}
		foreach ($data['activities'] as &$act) {
			if (empty($act['items']) || !is_array($act['items'])) {
				continue;
			}
			foreach ($act['items'] as &$it) {
				if (empty($it['lines']) || !is_array($it['lines'])) {
					continue;
				}
				foreach ($it['lines'] as &$ln) {
					$lineId = (int)($ln['id'] ?? 0);
					if ($lineId <= 0 || trim((string)($ln['doc_status'] ?? '')) !== 'confirmed') {
						continue;
					}
					$storedExt = (int)($ln['external_withdraw_id'] ?? 0);
					if (isset($matched[$lineId]) && is_array($matched[$lineId])) {
						$w = $matched[$lineId];
						$wid = (int)($w['id'] ?? 0);
						$raw = trim((string)($w['raw_status'] ?? ''));
						$prog = function_exists('pm_pbm_approval_progress')
							? pm_pbm_approval_progress($w)
							: ['done' => 0, 'total' => 0];
						$ln['withdraw_status'] = function_exists('pm_pbm_format_withdraw_status_label')
							? pm_pbm_format_withdraw_status_label($w)
							: (string)($w['status'] ?? '');
						$ln['withdraw_raw_status'] = $raw;
						$ln['withdraw_status_label'] = trim((string)($w['status'] ?? ''));
						$ln['withdraw_approval_done'] = $prog['done'];
						$ln['withdraw_approval_total'] = $prog['total'];
						$ln['external_withdraw_id'] = $wid;
						if ($wid > 0 && $storedExt !== $wid && function_exists('pm_pbm_set_line_external_withdraw_id')) {
							pm_pbm_set_line_external_withdraw_id($db_Project, $lineId, $wid);
						}
						$ln['withdraw_resume_url'] = '';
						$ln['withdraw_reconcile'] = 'has_withdraw';
						// มีร่างแล้ว — ใส่ลิงก์เปิดใบร่างต่อได้
						if (function_exists('pm_pbm_withdraw_is_draft_only') && pm_pbm_withdraw_is_draft_only($w)) {
							if ($wid > 0 && function_exists('pm_pbm_withdraw_system_view_url')) {
								$ln['withdraw_resume_url'] = pm_pbm_withdraw_system_view_url($projectCode, $wid, $w);
							} elseif ($defaultResume !== '') {
								$ln['withdraw_resume_url'] = $defaultResume;
								$ln['withdraw_pending_token'] = $defaultToken;
							}
						}
						continue;
					}
					// ไม่พบใบที่เข้ากัน — ล้าง external_withdraw_id ที่จับคู่ผิดไว้ก่อนหน้า
					if ($storedExt > 0 && function_exists('pm_pbm_set_line_external_withdraw_id')) {
						pm_pbm_set_line_external_withdraw_id($db_Project, $lineId, null);
						$ln['external_withdraw_id'] = null;
					}
					if ($defaultResume !== '') {
						$ln['withdraw_status'] = 'รอบันทึกในระบบเบิกจ่าย';
						$ln['withdraw_raw_status'] = 'pending_handoff';
						$ln['withdraw_status_label'] = 'ยืนยันแล้ว รอทำเอกสารในระบบเบิกจ่าย';
						$ln['withdraw_approval_done'] = 0;
						$ln['withdraw_approval_total'] = 0;
						$ln['withdraw_resume_url'] = $defaultResume;
						$ln['withdraw_pending_token'] = $defaultToken;
						$ln['withdraw_reconcile'] = 'pending_handoff';
					} else {
						$ln['withdraw_status'] = 'ยืนยันแล้ว';
						$ln['withdraw_raw_status'] = 'confirmed_local';
						$ln['withdraw_status_label'] = 'ยืนยันแล้ว แต่ยังไม่พบรายการในระบบเบิกจ่าย';
						$ln['withdraw_resume_url'] = '';
						$ln['withdraw_reconcile'] = 'confirmed_no_withdraw';
					}
				}
				unset($ln);
			}
			unset($it);
		}
		unset($act);
		return $data;
	}
}

/** URL เปิดรายการเบิกจ่ายในระบบ withdraw (หลัง login จะไปที่รายการนั้น) */
if (!function_exists('pm_pbm_withdraw_system_view_url')) {
	function pm_pbm_withdraw_system_view_url(string $projectCode, int $withdrawId, ?array $withdraw = null): string
	{
		if (is_array($withdraw)) {
			foreach (['view_url', 'open_url', 'system_url', 'url'] as $key) {
				$url = trim((string)($withdraw[$key] ?? ''));
				if ($url !== '' && preg_match('#^https?://#i', $url)) {
					return $url;
				}
			}
		}
		$withdrawId = (int)$withdrawId;
		if ($withdrawId <= 0) {
			return '';
		}
		$base = pm_pbm_withdraw_base_url();
		return $base . '/withdraw/project?id=' . rawurlencode((string)$withdrawId);
	}
}

/** แปลงเดือนเบิกจ่าย mon_08 → 08 */
if (!function_exists('pm_disburse_month_to_mm')) {
	function pm_disburse_month_to_mm($month): string
	{
		$m = trim((string)$month);
		if ($m === '') {
			return '';
		}
		if (preg_match('/mon[_-]?(\d{1,2})/i', $m, $mm)) {
			return str_pad((string)((int)$mm[1]), 2, '0', STR_PAD_LEFT);
		}
		if (preg_match('/^(\d{1,2})$/', $m, $mm)) {
			return str_pad((string)((int)$mm[1]), 2, '0', STR_PAD_LEFT);
		}
		return $m;
	}
}

/**
 * ดึงรหัสแหล่งงบดิบของโครงการ (code_budget จาก pre/project_budget/log)
 * @return array{code:string,other:string}
 */
if (!function_exists('pm_resolve_project_budget_code_raw')) {
	function pm_resolve_project_budget_code_raw($db_Project, $project_id): array
	{
		$project_id = (int)$project_id;
		$out = ['code' => '', 'other' => ''];
		if ($project_id <= 0 || !($db_Project instanceof mysqli)) {
			return $out;
		}
		$mq = static function ($mysqli, $sql) {
			try {
				return @mysqli_query($mysqli, $sql);
			} catch (Throwable $e) {
				return false;
			}
		};
		$scalarStr = static function ($v) {
			if ($v === null || is_bool($v)) {
				return '';
			}
			if (is_scalar($v)) {
				return trim((string)$v);
			}
			return '';
		};
		$cb = '';
		$bo = '';
		$qpre = $mq($db_Project, "SELECT * FROM pre_project WHERE id='" . $project_id . "' LIMIT 1");
		if ($qpre && ($pr = mysqli_fetch_assoc($qpre))) {
			if (array_key_exists('code_budget', $pr)) {
				$cb = $scalarStr($pr['code_budget']);
			}
			if (array_key_exists('budget_other', $pr)) {
				$bo = $scalarStr($pr['budget_other']);
			}
		}
		$needPb = ($cb === '' || $cb === '0' || (($cb === '99' || $cb === '99.0') && $bo === ''));
		if ($needPb) {
			$qpb = $mq(
				$db_Project,
				"SELECT code_budget, budget_other FROM project_budget WHERE project_id='" . $project_id . "' AND status='1' ORDER BY id DESC LIMIT 1"
			);
			if ($qpb && ($rpb = mysqli_fetch_assoc($qpb))) {
				$tcb = $scalarStr($rpb['code_budget'] ?? '');
				$tbo = $scalarStr($rpb['budget_other'] ?? '');
				if (($cb === '' || $cb === '0') && $tcb !== '' && $tcb !== '0') {
					$cb = $tcb;
				}
				if ($bo === '' && $tbo !== '') {
					$bo = $tbo;
				}
			}
		}
		if ($cb === '' || $cb === '0' || $cb === '99' || $cb === '99.0') {
			$qany = $mq(
				$db_Project,
				"SELECT code_budget, budget_other FROM project_budget WHERE project_id='" . $project_id . "' ORDER BY (status='1') DESC, id DESC LIMIT 8"
			);
			if ($qany) {
				while (($rw = mysqli_fetch_assoc($qany))) {
					$tcb = $scalarStr($rw['code_budget'] ?? '');
					$tbo = $scalarStr($rw['budget_other'] ?? '');
					if (($cb === '' || $cb === '0' || $cb === '99' || $cb === '99.0') && $tcb !== '' && $tcb !== '0' && $tcb !== '99' && $tcb !== '99.0') {
						$cb = $tcb;
					} elseif (($cb === '' || $cb === '0') && $tcb !== '' && $tcb !== '0') {
						$cb = $tcb;
					}
					if ($bo === '' && $tbo !== '') {
						$bo = $tbo;
					}
					if ($bo !== '' && $cb !== '' && $cb !== '0' && $cb !== '99' && $cb !== '99.0') {
						break;
					}
				}
			}
		}
		if ($cb === '' || $cb === '0' || $cb === '99' || $cb === '99.0') {
			$qlog = $mq(
				$db_Project,
				"SELECT meta_json FROM project_plan_review_log
				 WHERE project_id='" . $project_id . "' AND action_type='notify_plan_result'
				 ORDER BY id DESC LIMIT 1"
			);
			if ($qlog && ($lr = mysqli_fetch_assoc($qlog))) {
				$meta = json_decode((string)($lr['meta_json'] ?? ''), true);
				if (is_array($meta)) {
					$tcb = $scalarStr($meta['budget_code'] ?? ($meta['code_budget'] ?? ''));
					$tbo = $scalarStr($meta['budget_other'] ?? '');
					if (($cb === '' || $cb === '0' || $cb === '99' || $cb === '99.0') && $tcb !== '' && $tcb !== '0') {
						$cb = $tcb;
					}
					if ($bo === '' && $tbo !== '') {
						$bo = $tbo;
					}
				}
			}
		}
		$out['code'] = $cb;
		$out['other'] = $bo;
		return $out;
	}
}

/**
 * แปลงรหัสแหล่งงบโครงการ → budget_manage_id / budget_manage_code ของระบบงบ
 * @return array{budget_manage_id:int|string,budget_manage_code:string}
 */
if (!function_exists('pm_resolve_project_budget_manage_ref')) {
	function pm_resolve_project_budget_manage_ref($db_Project, $project_id): array
	{
		$out = ['budget_manage_id' => 0, 'budget_manage_code' => ''];
		$raw = pm_resolve_project_budget_code_raw($db_Project, $project_id);
		$code = trim((string)($raw['code'] ?? ''));
		if ($code === '' || $code === '0' || $code === '99' || $code === '99.0') {
			return $out;
		}
		$db = function_exists('pm_budget_source_connection')
			? pm_budget_source_connection($db_Project)
			: $db_Project;
		if (!($db instanceof mysqli)) {
			$out['budget_manage_code'] = $code;
			return $out;
		}
		$codeEsc = mysqli_real_escape_string($db, $code);
		$q = @mysqli_query(
			$db,
			"SELECT budget_manage_id, budget_manage_code
			 FROM view_budget_manage
			 WHERE budget_manage_code = '$codeEsc'
			    OR CAST(budget_manage_id AS CHAR) = '$codeEsc'
			 LIMIT 1"
		);
		if ($q && ($row = mysqli_fetch_assoc($q))) {
			$mid = (int)($row['budget_manage_id'] ?? 0);
			$mcode = trim((string)($row['budget_manage_code'] ?? ''));
			$out['budget_manage_id'] = $mid > 0 ? $mid : 0;
			$out['budget_manage_code'] = $mcode !== '' ? $mcode : $code;
			return $out;
		}
		$out['budget_manage_code'] = $code;
		if (ctype_digit($code)) {
			$out['budget_manage_id'] = (int)$code;
		}
		return $out;
	}
}

/** แม็พชื่อหมวด/ประเภทค่าใช้จ่าย → รหัสหมวดในระบบงบ (budget_set_pay_list.budget_pay_code) */
if (!function_exists('pm_map_expense_name_to_pbm_category_code')) {
	function pm_map_expense_name_to_pbm_category_code($names): string
	{
		$candidates = [];
		foreach ((array)$names as $n) {
			$n = trim((string)$n);
			if ($n !== '') {
				$candidates[] = $n;
			}
		}
		if (empty($candidates)) {
			return '';
		}
		$db = function_exists('pm_budget_source_connection')
			? pm_budget_source_connection(null)
			: null;
		if (!($db instanceof mysqli)) {
			return '';
		}
		static $payMap = null;
		if ($payMap === null) {
			$payMap = [];
			$q = @mysqli_query(
				$db,
				"SELECT budget_pay_code, budget_pay_name
				 FROM budget_set_pay_list
				 WHERE IFNULL(budget_pay_status,'') NOT IN ('99','0')
				 ORDER BY budget_pay_id ASC"
			);
			if ($q) {
				while ($row = mysqli_fetch_assoc($q)) {
					$name = trim((string)($row['budget_pay_name'] ?? ''));
					$code = trim((string)($row['budget_pay_code'] ?? ''));
					if ($name !== '' && $code !== '') {
						$payMap[$name] = $code;
					}
				}
			}
		}
		foreach ($candidates as $name) {
			if (isset($payMap[$name])) {
				return (string)$payMap[$name];
			}
		}
		foreach ($candidates as $name) {
			foreach ($payMap as $pName => $pCode) {
				if ($pName === '') {
					continue;
				}
				$hit = false;
				if (function_exists('mb_strpos')) {
					$hit = (mb_strpos($name, $pName) !== false || mb_strpos($pName, $name) !== false);
				} else {
					$hit = (strpos($name, $pName) !== false || strpos($pName, $name) !== false);
				}
				if ($hit) {
					return (string)$pCode;
				}
			}
		}
		return '';
	}
}

/**
 * สร้าง payload ส่งไประบบเบิกจ่าย (POST /withdraw/project/from-project)
 *
 * @param list<array{project_activity_budget_id?:int,line_detail?:string,amount?:float|int|string,disburse_month?:string}> $lines
 */
if (!function_exists('pm_build_budget_disburse_external_payload')) {
	function pm_build_budget_disburse_external_payload(
		$db_Project,
		$project_id,
		$activity_id,
		$activity_detail,
		array $lines,
		$staff_code = ''
	): array {
		$project_id = (int)$project_id;
		$activity_id = (int)$activity_id;
		$activity_detail = trim((string)$activity_detail);
		$staff_code = trim((string)$staff_code);

		$pre = function_exists('getPreProjectID') ? getPreProjectID($db_Project, $project_id) : null;
		$projectCode = is_array($pre) ? trim((string)($pre['code_pre_project'] ?? '')) : '';
		$projectName = is_array($pre) ? trim((string)($pre['name_project'] ?? '')) : '';
		if ($projectCode === '') {
			$projectCode = (string)$project_id;
		}

		$actRow = null;
		$acts = function_exists('getProjectActivityList') ? getProjectActivityList($db_Project, $project_id) : [];
		foreach ($acts as $a) {
			if ((int)($a['id'] ?? 0) === $activity_id) {
				$actRow = $a;
				break;
			}
		}
		$activityNo = is_array($actRow) ? trim((string)($actRow['activity_no'] ?? '')) : '';
		$activityName = is_array($actRow) ? trim((string)($actRow['activity_name'] ?? '')) : '';
		$activityIdOut = $activityNo !== '' ? $activityNo : (string)$activity_id;

		$budgetRef = pm_resolve_project_budget_manage_ref($db_Project, $project_id);
		$budgetManageId = $budgetRef['budget_manage_id'];
		$budgetManageCode = (string)$budgetRef['budget_manage_code'];

		$subject = 'ขออนุมัติเบิกจ่าย' . ($activityName !== '' ? $activityName : 'โครงการ');

		$budgetById = [];
		$budgetList = function_exists('getProjectActivityBudgetList')
			? getProjectActivityBudgetList($db_Project, $activity_id)
			: [];
		foreach ($budgetList as $it) {
			$bid = (int)($it['id'] ?? 0);
			if ($bid > 0) {
				$budgetById[$bid] = $it;
			}
		}

		$items = [];
		foreach ($lines as $ln) {
			if (!is_array($ln)) {
				continue;
			}
			$itemId = (int)($ln['project_activity_budget_id'] ?? 0);
			$amount = (float)($ln['amount'] ?? 0);
			$monthMm = pm_disburse_month_to_mm($ln['disburse_month'] ?? '');
			$lineName = trim((string)($ln['line_detail'] ?? ''));
			if ($itemId <= 0 || $amount <= 0) {
				continue;
			}
			$it = $budgetById[$itemId] ?? null;
			$codeExp = is_array($it) ? trim((string)($it['code_expenses'] ?? '')) : '';
			$codeExpDet = is_array($it) ? trim((string)($it['code_expenses_detail'] ?? '')) : '';
			$catName = '';
			$parentName = '';
			if ($codeExpDet !== '' && function_exists('getSetExpensesDetailByID')) {
				$exd = getSetExpensesDetailByID($db_Project, $codeExpDet);
				$catName = is_array($exd) ? trim((string)($exd['name'] ?? '')) : '';
			}
			if ($codeExp !== '' && function_exists('getSetExpenses')) {
				$ex = getSetExpenses($db_Project, $codeExp);
				$parentName = is_array($ex) ? trim((string)($ex['name'] ?? '')) : '';
			}
			if ($catName === '') {
				$catName = $parentName !== '' ? $parentName : 'รายการค่าใช้จ่าย';
			}
			$catCode = pm_map_expense_name_to_pbm_category_code([$catName, $parentName]);
			if ($catCode === '') {
				$catCode = $codeExpDet !== '' ? $codeExpDet : $codeExp;
			}
			if ($lineName === '') {
				$displayName = $catName;
			} elseif ($catName === '' || $catName === $lineName) {
				$displayName = $lineName;
			} else {
				$displayName = $catName . ' (' . $lineName . ')';
			}
			$items[] = [
				'category_code' => (string)$catCode,
				'category_name' => $catName,
				'month' => $monthMm,
				'name' => $displayName,
				'amount' => round($amount, 2),
			];
		}

		$payload = [
			'project_id' => $projectCode,
			'project_code' => $projectCode,
			'project_name' => $projectName,
			'activity_id' => (string)$activityIdOut,
			'activity_name' => $activityName,
			'activity_detail' => $activity_detail,
			'budget_manage_id' => is_numeric($budgetManageId) ? (0 + $budgetManageId) : $budgetManageId,
			'budget_manage_code' => $budgetManageCode,
			'subject' => $subject,
			'items' => $items,
			'from' => 'eproject',
		];
		if ($staff_code !== '') {
			$payload['staff_code'] = $staff_code;
			$payload['PharCode'] = $staff_code;
		}
		return $payload;
	}
}

/** ตารางการดำเนินการอื่นๆ (ส่วนที่ 5 — โหมดรายงานผล) */
function ensureBudgetOtherActionSchema($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    $run = function ($sql) use ($db_Project) {
        if (!mysqli_query($db_Project, $sql)) {
            @error_log('ensureBudgetOtherActionSchema: ' . mysqli_error($db_Project));
        }
    };
    $run("CREATE TABLE IF NOT EXISTS project_budget_other_action (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id INT UNSIGNED NOT NULL,
        action_type VARCHAR(16) NOT NULL DEFAULT 'loan',
        project_name VARCHAR(500) NOT NULL DEFAULT '',
        budget_source VARCHAR(500) NOT NULL DEFAULT '',
        staff_insert INT UNSIGNED NULL,
        request_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        detail TEXT NULL,
        amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        status_label VARCHAR(128) NOT NULL DEFAULT '',
        doc_label VARCHAR(255) NOT NULL DEFAULT '',
        doc_url VARCHAR(512) NOT NULL DEFAULT '',
        external_ref VARCHAR(128) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_pboa_proj (project_id),
        KEY idx_pboa_type (action_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** ตารางประเมินผลโครงการ (ส่วนที่ 6 — โหมดรายงานผล) */
function ensureProjectEvaluationResultSchema($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    $run = function ($sql) use ($db_Project) {
        if (!mysqli_query($db_Project, $sql)) {
            @error_log('ensureProjectEvaluationResultSchema: ' . mysqli_error($db_Project));
        }
    };
    $run("CREATE TABLE IF NOT EXISTS project_evaluation_result (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id INT UNSIGNED NOT NULL,
        overall_status VARCHAR(16) NOT NULL DEFAULT '',
        problem_obstacle TEXT NULL,
        solution_suggestion TEXT NULL,
        kpi_summary_percent INT NULL DEFAULT NULL,
        staff_insert INT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        status VARCHAR(8) NOT NULL DEFAULT '1',
        PRIMARY KEY (id),
        KEY idx_pevr_proj (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** ส่งรายงานผลโครงการ — ลำดับผู้ตรวจสอบและผลพิจารณา */
function ensureProjectResultReportSchema($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;
    $run = function ($sql) use ($db_Project) {
        if (!mysqli_query($db_Project, $sql)) {
            @error_log('ensureProjectResultReportSchema: ' . mysqli_error($db_Project));
        }
    };
    $cols = [
        'result_report_certifiers_json' => 'LONGTEXT NULL',
        'result_report_submitted_at' => 'DATETIME NULL DEFAULT NULL',
        'result_report_completed_at' => 'DATETIME NULL DEFAULT NULL',
        'result_report_submitter_reply_note' => 'MEDIUMTEXT NULL',
        'result_report_submitter_reply_updated_at' => 'DATETIME NULL DEFAULT NULL',
        'result_report_submitter_reply_staff_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'result_report_plan_closed_at' => 'DATETIME NULL DEFAULT NULL',
        'result_report_plan_closed_by' => 'INT UNSIGNED NULL DEFAULT NULL',
        'result_report_plan_note' => 'MEDIUMTEXT NULL',
    ];
    foreach ($cols as $col => $def) {
        $chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM pre_project LIKE '" . mysqli_real_escape_string($db_Project, $col) . "'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            @mysqli_query($db_Project, "ALTER TABLE pre_project ADD COLUMN " . $col . " " . $def);
        }
    }
    $run("CREATE TABLE IF NOT EXISTS project_result_report_certifier_decision (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id INT UNSIGNED NOT NULL,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        staff_id INT UNSIGNED NOT NULL DEFAULT 0,
        decided_by_staff_id INT UNSIGNED NULL DEFAULT NULL,
        decision VARCHAR(16) NOT NULL DEFAULT '',
        certifier_note MEDIUMTEXT NULL,
        decided_at DATETIME NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_prrcd_proj_sort (project_id, sort_order),
        KEY idx_prrcd_proj (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if (!function_exists('pm_project_is_result_report_plan_closed')) {
	function pm_project_is_result_report_plan_closed($db_Project, $project_id, $row = null): bool
	{
		if (is_array($row) && array_key_exists('result_report_plan_closed_at', $row)) {
			return trim((string)($row['result_report_plan_closed_at'] ?? '')) !== '';
		}
		$pid = (int)$project_id;
		if ($pid <= 0 || !$db_Project) {
			return false;
		}
		ensureProjectResultReportSchema($db_Project);
		$q = @mysqli_query($db_Project, "SELECT result_report_plan_closed_at FROM pre_project WHERE id='" . $pid . "' AND status='1' LIMIT 1");
		$r = $q ? mysqli_fetch_assoc($q) : null;
		return is_array($r) && trim((string)($r['result_report_plan_closed_at'] ?? '')) !== '';
	}
}

/**
 * สร้างตารางคำขอนำเข้าที่ประชุมโครงการ (ถ้ายังไม่มี) — ใช้กับ project_include_request_schema.sql
 */
function ensureIncludeRequestTables($db_Project)
{
    static $done = false;
    if ($done || !$db_Project) {
        return;
    }
    $done = true;

    $run = function ($sql) use ($db_Project) {
        if (!mysqli_query($db_Project, $sql)) {
            @error_log('ensureIncludeRequestTables: ' . mysqli_error($db_Project));
        }
    };

    $run("CREATE TABLE IF NOT EXISTS project_include_request (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        budget_year VARCHAR(32) NOT NULL DEFAULT '',
        section_name TEXT NULL,
        subject TEXT NULL,
        letter_body MEDIUMTEXT NULL,
        submitter_staff_id INT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        status VARCHAR(32) NOT NULL DEFAULT 'submitted',
        PRIMARY KEY (id),
        KEY idx_pir_year (budget_year),
        KEY idx_pir_created (created_at),
        KEY idx_pir_submitter (submitter_staff_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $run("CREATE TABLE IF NOT EXISTS project_include_request_item (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        request_id INT UNSIGNED NOT NULL,
        project_id INT UNSIGNED NOT NULL,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY uk_piri_req_proj (request_id, project_id),
        KEY idx_piri_project (project_id),
        CONSTRAINT fk_piri_request FOREIGN KEY (request_id) REFERENCES project_include_request (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $run("CREATE TABLE IF NOT EXISTS project_include_request_summary (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        request_id INT UNSIGNED NOT NULL,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        row_kind VARCHAR(16) NOT NULL DEFAULT 'data',
        section_group VARCHAR(32) NULL,
        consistency_label VARCHAR(512) NOT NULL DEFAULT '',
        project_count INT UNSIGNED NULL,
        budget_baht DECIMAL(15,2) NULL,
        PRIMARY KEY (id),
        KEY idx_pirs_request (request_id),
        KEY idx_pirs_sort (request_id, sort_order),
        CONSTRAINT fk_pirs_request FOREIGN KEY (request_id) REFERENCES project_include_request (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $run("CREATE TABLE IF NOT EXISTS project_include_request_certifier (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        request_id INT UNSIGNED NOT NULL,
        sort_order TINYINT UNSIGNED NOT NULL,
        staff_id INT UNSIGNED NOT NULL,
        position_title VARCHAR(255) NOT NULL DEFAULT '',
        PRIMARY KEY (id),
        UNIQUE KEY uk_pirc_req_order (request_id, sort_order),
        KEY idx_pirc_staff (staff_id),
        CONSTRAINT fk_pirc_request FOREIGN KEY (request_id) REFERENCES project_include_request (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // -----------------------------------------------------------------------------
    // 5) ผลการรับรอง/ลงนามของผู้รับรอง (used by project_approve page)
    // -----------------------------------------------------------------------------
    $run("CREATE TABLE IF NOT EXISTS project_include_request_certifier_decision (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        request_id INT UNSIGNED NOT NULL,
        sort_order TINYINT UNSIGNED NOT NULL,
        staff_id INT UNSIGNED NOT NULL,
        decision VARCHAR(16) NOT NULL DEFAULT '',
        decided_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        decided_by_staff_id INT UNSIGNED NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_pircd_req_order (request_id, sort_order),
        KEY idx_pircd_request (request_id),
        KEY idx_pircd_staff (staff_id),
        CONSTRAINT fk_pircd_request FOREIGN KEY (request_id) REFERENCES project_include_request (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $chkNote = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request_certifier_decision LIKE 'certifier_note'");
    if ($chkNote && mysqli_num_rows($chkNote) === 0) {
        @mysqli_query($db_Project, "ALTER TABLE project_include_request_certifier_decision ADD COLUMN certifier_note TEXT NULL AFTER decision");
    }

    // ตีกลับรายโครงการ + คำชี้แจงยืนยันการแก้ไข
    $itemReturnCols = [
        'return_selected' => "TINYINT UNSIGNED NOT NULL DEFAULT 0",
        'returned_at' => "DATETIME NULL DEFAULT NULL",
        'returned_by_staff_id' => "INT UNSIGNED NULL DEFAULT NULL",
        'returned_sort_order' => "TINYINT UNSIGNED NULL DEFAULT NULL",
        'return_cert_note' => "MEDIUMTEXT NULL",
        'fix_note' => "MEDIUMTEXT NULL",
        'fix_at' => "DATETIME NULL DEFAULT NULL",
        'fix_staff_id' => "INT UNSIGNED NULL DEFAULT NULL",
        'fix_status' => "VARCHAR(16) NOT NULL DEFAULT ''",
    ];
    foreach ($itemReturnCols as $col => $def) {
        $chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request_item LIKE '" . mysqli_real_escape_string($db_Project, $col) . "'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            @mysqli_query($db_Project, "ALTER TABLE project_include_request_item ADD COLUMN `" . $col . "` " . $def);
        }
    }
    $chkAdminFix = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_include_request LIKE 'admin_fix_sent_at'");
    if (!$chkAdminFix || mysqli_num_rows($chkAdminFix) === 0) {
        @mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN admin_fix_sent_at DATETIME NULL DEFAULT NULL");
        @mysqli_query($db_Project, "ALTER TABLE project_include_request ADD COLUMN admin_fix_sent_by INT UNSIGNED NULL DEFAULT NULL");
    }

    $run("CREATE TABLE IF NOT EXISTS project_staff_notification (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        staff_id INT UNSIGNED NOT NULL,
        kind VARCHAR(48) NOT NULL DEFAULT '',
        title VARCHAR(255) NOT NULL DEFAULT '',
        body MEDIUMTEXT NULL,
        link_url VARCHAR(512) NULL,
        ref_type VARCHAR(48) NULL,
        ref_id INT UNSIGNED NULL,
        is_read TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_psn_staff_read (staff_id, is_read, created_at),
        KEY idx_psn_ref (ref_type, ref_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** แจ้งเตือนในกระดิ่ง (Admin หน่วยงาน / ผู้เกี่ยวข้อง) */
if (!function_exists('pm_staff_notify')) {
	function pm_staff_notify($db_Project, $staff_id, $kind, $title, $body = '', $link_url = '', $ref_type = '', $ref_id = 0): bool
	{
		$staff_id = (int)$staff_id;
		if (!$db_Project || $staff_id <= 0) {
			return false;
		}
		if (function_exists('ensureIncludeRequestTables')) {
			ensureIncludeRequestTables($db_Project);
		}
		$kind = mysqli_real_escape_string($db_Project, substr(trim((string)$kind), 0, 48));
		$title = mysqli_real_escape_string($db_Project, substr(trim((string)$title), 0, 255));
		$body = mysqli_real_escape_string($db_Project, (string)$body);
		$link = mysqli_real_escape_string($db_Project, substr(trim((string)$link_url), 0, 512));
		$refType = mysqli_real_escape_string($db_Project, substr(trim((string)$ref_type), 0, 48));
		$refId = (int)$ref_id;
		return (bool)@mysqli_query(
			$db_Project,
			"INSERT INTO project_staff_notification (staff_id, kind, title, body, link_url, ref_type, ref_id, is_read, created_at)
			 VALUES ('{$staff_id}', '{$kind}', '{$title}', " . ($body !== '' ? "'{$body}'" : "NULL") . ",
			         " . ($link !== '' ? "'{$link}'" : "NULL") . ",
			         " . ($refType !== '' ? "'{$refType}'" : "NULL") . ",
			         " . ($refId > 0 ? "'{$refId}'" : "NULL") . ",
			         0, NOW())"
		);
	}
}

/** แจ้ง Admin หน่วยงานของโครงการ (set_department_admin + หน่วยหลัก) */
if (!function_exists('pm_notify_unit_admins_for_project')) {
	function pm_notify_unit_admins_for_project($db_Project, $db_person, $project_id, $kind, $title, $body = '', $link_url = ''): int
	{
		$project_id = (int)$project_id;
		if ($project_id <= 0 || !$db_Project) {
			return 0;
		}
		$q = @mysqli_query($db_Project, "SELECT code_unit, job_code, dep_code FROM pre_project WHERE id='{$project_id}' AND status='1' LIMIT 1");
		$row = $q ? mysqli_fetch_assoc($q) : null;
		if (!$row) {
			return 0;
		}
		$codeUnit = trim((string)($row['code_unit'] ?? ''));
		$jobCode = trim((string)($row['job_code'] ?? ''));
		$depCode = trim((string)($row['dep_code'] ?? ''));
		$staffIds = [];
		$qAdm = @mysqli_query(
			$db_Project,
			"SELECT DISTINCT staff_admin FROM set_department_admin
			 WHERE staff_admin IS NOT NULL AND TRIM(staff_admin) <> ''"
		);
		$adminKeys = [];
		while ($qAdm && ($ar = mysqli_fetch_assoc($qAdm))) {
			$adminKeys[] = trim((string)($ar['staff_admin'] ?? ''));
		}
		// กรองเฉพาะ admin ที่ผูกหน่วยงานของโครงการ
		$qAdm2 = @mysqli_query(
			$db_Project,
			"SELECT staff_admin, dep_code, job_code FROM set_department_admin"
		);
		while ($qAdm2 && ($a = mysqli_fetch_assoc($qAdm2))) {
			$dep = trim((string)($a['dep_code'] ?? ''));
			$job = trim((string)($a['job_code'] ?? ''));
			$match = false;
			if ($dep !== '' && ($dep === $depCode || $dep === $jobCode || ($jobCode !== '' && strpos($jobCode, $dep) === 0))) {
				$match = true;
			}
			if ($job !== '' && ($job === $jobCode || $job === $depCode || ($jobCode !== '' && strpos($jobCode, $job) === 0))) {
				$match = true;
			}
			if (!$match) {
				continue;
			}
			$key = trim((string)($a['staff_admin'] ?? ''));
			if ($key === '') {
				continue;
			}
			if (ctype_digit($key)) {
				$staffIds[(int)$key] = (int)$key;
			} elseif ($db_person) {
				$kEsc = mysqli_real_escape_string($db_person, $key);
				$qs = @mysqli_query($db_person, "SELECT id FROM staff WHERE code='{$kEsc}' OR id='{$kEsc}' LIMIT 1");
				$sr = $qs ? mysqli_fetch_assoc($qs) : null;
				$sid = (int)($sr['id'] ?? 0);
				if ($sid > 0) {
					$staffIds[$sid] = $sid;
				}
			}
		}
		$n = 0;
		foreach ($staffIds as $sid) {
			if (pm_staff_notify($db_Project, (int)$sid, $kind, $title, $body, $link_url, 'pre_project', $project_id)) {
				$n++;
			}
		}
		return $n;
	}
}

/** staff_insert ของโครงการ */
if (!function_exists('pm_project_staff_insert_id')) {
	function pm_project_staff_insert_id($db_Project, $project_id): int
	{
		$project_id = (int)$project_id;
		if ($project_id <= 0 || !$db_Project) {
			return 0;
		}
		$q = @mysqli_query($db_Project, "SELECT staff_insert FROM pre_project WHERE id='" . $project_id . "' AND status='1' LIMIT 1");
		$row = $q ? mysqli_fetch_assoc($q) : null;
		return (int)($row['staff_insert'] ?? 0);
	}
}

/** แจ้งผู้ยื่นเมื่อถูกตีกลับให้แก้ไข (กระดิ่ง / เอกสารส่ง) */
if (!function_exists('pm_notify_revision_needed')) {
	function pm_notify_revision_needed($db_Project, $staff_ids, $kind, $title, $body = '', $link_url = '', $ref_type = '', $ref_id = 0, $skip_staff_id = 0): int
	{
		if (!$db_Project || !function_exists('pm_staff_notify')) {
			return 0;
		}
		$n = 0;
		$seen = [];
		$skip = (int)$skip_staff_id;
		foreach ((array)$staff_ids as $sidN) {
			$sidN = (int)$sidN;
			if ($sidN <= 0 || $sidN === $skip || isset($seen[$sidN])) {
				continue;
			}
			$seen[$sidN] = true;
			if (pm_staff_notify($db_Project, $sidN, $kind, $title, $body, $link_url, $ref_type, $ref_id)) {
				$n++;
			}
		}
		return $n;
	}
}

/** รายการโครงการที่ถูกเลือกตีกลับในคำขอ (ยังอยู่ในรอบแก้ไข) */
if (!function_exists('pm_include_returned_items')) {
	function pm_include_returned_items($db_Project, $request_id): array
	{
		$request_id = (int)$request_id;
		$out = [];
		if ($request_id <= 0 || !$db_Project) {
			return $out;
		}
		if (function_exists('ensureIncludeRequestTables')) {
			ensureIncludeRequestTables($db_Project);
		}
		$q = @mysqli_query(
			$db_Project,
			"SELECT i.*, p.code_pre_project, p.name_project, p.status_project
			 FROM project_include_request_item i
			 LEFT JOIN pre_project p ON p.id = i.project_id
			 WHERE i.request_id='{$request_id}' AND IFNULL(i.return_selected,0)=1
			 ORDER BY i.sort_order ASC, i.id ASC"
		);
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$out[] = $r;
		}
		return $out;
	}
}

/** ชื่อเรื่องคำขอพิจารณาแผน — แสดง "ขอพิจารณาแผนงาน" แทน "ขอบรรจุโครงการ" */
if (!function_exists('pm_include_request_subject_label')) {
	function pm_include_request_subject_label($subject): string
	{
		$s = trim((string)$subject);
		if ($s === '') {
			return $s;
		}
		$s = str_replace('ขอบรรจุโครงการ', 'ขอพิจารณาแผนงาน', $s);
		if (mb_strpos($s, 'ขอพิจารณาแผนงาน') === false) {
			$s = preg_replace('/ยื่นขอพิจารณาแผน/u', 'ขอพิจารณาแผนงาน', $s, 1);
		}
		return $s;
	}
}

/** โครงการที่ถูกตีกลับรอบนี้ยืนยันคำชี้แจงครบหรือยัง */
if (!function_exists('pm_include_all_returned_fixes_confirmed')) {
	function pm_include_all_returned_fixes_confirmed($db_Project, $request_id): bool
	{
		$items = pm_include_returned_items($db_Project, $request_id);
		if (empty($items)) {
			return false;
		}
		foreach ($items as $it) {
			$st = trim((string)($it['fix_status'] ?? ''));
			if ($st !== 'confirmed' && $st !== 'admin_sent') {
				return false;
			}
		}
		return true;
	}
}

/**
 * บันทึกคำขอขอบรรจุ + รายการโครงการ + สรุป + ผู้รับรอง
 *
 * @return int คืน request_id หรือ 0 เมื่อไม่สำเร็จ
 */
function insertProjectIncludeRequestBundle(
    $db_Project,
    $budget_year,
    $section_name,
    $subject,
    $letter_body,
    $submitter_staff_id,
    array $project_ids,
    array $certifiers,
    array $summary_rows
) {
    $budget_year = (string)$budget_year;
    $section_name = (string)$section_name;
    $subject = (string)$subject;
    $letter_body = (string)$letter_body;
    $submitter_staff_id = (int)$submitter_staff_id;

    $esc = function ($v) use ($db_Project) {
        return mysqli_real_escape_string($db_Project, (string)$v);
    };

    $sqlIns = "
        INSERT INTO project_include_request
            (budget_year, section_name, subject, letter_body, submitter_staff_id, status)
        VALUES (
            '".$esc($budget_year)."',
            '".$esc($section_name)."',
            '".$esc($subject)."',
            '".$esc($letter_body)."',
            ".($submitter_staff_id > 0 ? "'".$submitter_staff_id."'" : "NULL").",
            'submitted'
        )
    ";
    if (!mysqli_query($db_Project, $sqlIns)) {
        return 0;
    }
    $request_id = (int)mysqli_insert_id($db_Project);
    if ($request_id <= 0) {
        return 0;
    }

    $so = 0;
    foreach ($project_ids as $pid) {
        $pid = (int)$pid;
        if ($pid <= 0) {
            continue;
        }
        if (!mysqli_query($db_Project, "
            INSERT INTO project_include_request_item (request_id, project_id, sort_order)
            VALUES ('".$request_id."', '".$pid."', '".$so."')
        ")) {
            return 0;
        }
        $so++;
    }

    $sortSum = 0;
    foreach ($summary_rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rk = $esc(substr((string)($row['row_kind'] ?? 'data'), 0, 16));
        $sgRaw = $row['section_group'] ?? null;
        $sg = ($sgRaw === null || $sgRaw === '') ? 'NULL' : "'".$esc(substr((string)$sgRaw, 0, 32))."'";
        $labRaw = (string)($row['consistency_label'] ?? '');
        $lab = $esc(function_exists('mb_substr') ? mb_substr($labRaw, 0, 512, 'UTF-8') : substr($labRaw, 0, 512));
        $pc = isset($row['project_count']) && $row['project_count'] !== '' && $row['project_count'] !== null
            ? "'".(int)$row['project_count']."'" : 'NULL';
        $bb = isset($row['budget_baht']) && $row['budget_baht'] !== '' && $row['budget_baht'] !== null
            ? "'".mysqli_real_escape_string($db_Project, (string)$row['budget_baht'])."'" : 'NULL';
        if (!mysqli_query($db_Project, "
            INSERT INTO project_include_request_summary
                (request_id, sort_order, row_kind, section_group, consistency_label, project_count, budget_baht)
            VALUES (
                '".$request_id."',
                '".$sortSum."',
                '".$rk."',
                ".$sg.",
                '".$lab."',
                ".$pc.",
                ".$bb."
            )
        ")) {
            return 0;
        }
        $sortSum++;
    }

    $ord = 1;
    foreach ($certifiers as $c) {
        if (!is_array($c)) {
            continue;
        }
        $st = (int)($c['staff_id'] ?? 0);
        $posRaw = (string)($c['position'] ?? '');
        $pos = $esc(function_exists('mb_substr') ? mb_substr($posRaw, 0, 255, 'UTF-8') : substr($posRaw, 0, 255));
        if ($st <= 0) {
            continue;
        }
        if (!mysqli_query($db_Project, "
            INSERT INTO project_include_request_certifier
                (request_id, sort_order, staff_id, position_title)
            VALUES ('".$request_id."', '".$ord."', '".$st."', '".$pos."')
        ")) {
            return 0;
        }
        $ord++;
    }

    return $request_id;
}

function getSelectedConsistency($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $selected = [];
    $query = "SELECT * FROM project_consistency_map WHERE code_project_consistency = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    while ($r = mysqli_fetch_assoc($result)) {
        $selected[] = $r['code_consistency_detail'];
    }
    return $selected;
}


// function getSelectedConsistency($db_connection, $project_id)
// {
//     $project_id = mysqli_real_escape_string($db_connection, $project_id);

//     $selected = [];

//     $query = "
//         SELECT code_consistency_detail
//         FROM project_consistency_map
//         WHERE code_project_consistency = '$project_id'
//           AND status != '99'
//     ";

//     $result = mysqli_query($db_connection, $query);

//     while ($row = mysqli_fetch_assoc($result)) {
//         $selected[] = intval($row['code_consistency_detail']);
//     }

//     return $selected;
// }
function getProjectConsistencyRows($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, (string)$id);
    $query = "SELECT * FROM project_consistency WHERE project_id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);
    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list;
}
function getProjectConsistency($db_connection, $id)
{
    $rows = getProjectConsistencyRows($db_connection, $id);
    return $rows ? $rows[0] : null;
}
function getProjectConsistencyMapList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_consistency_map WHERE code_project_consistency = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getSelectedConsistencyMap($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id);

    $selected = [];

    $query = "
        SELECT *
        FROM project_consistency_map
        WHERE code_project_consistency = '$id'
          AND status != '99'
    ";

    $result = mysqli_query($db_connection, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $selected[] = intval($row['code_consistency_detail']);
    }

    return $selected;
}

// -----
function getProjectConsistencyKPIDetail($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_consistency_kpi WHERE code_project_consistency = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getSelectedConsistencyKpi($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $selected = [];
    $query = "SELECT * FROM project_consistency_kpi WHERE code_project_consistency = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    while ($r = mysqli_fetch_assoc($result)) {
        $selected[] = $r['code_consistency_detail'];
    }
    return $selected;
}



function getProjectConsistencyKpi($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM project_consistency WHERE project_id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getProjectConsistencyKpiList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection

    $query = "SELECT * FROM project_consistency_kpi WHERE code_project_consistency = '$id'  AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list; // คืนค่าเป็น array ของหลายแถว
}
function getSelectedConsistencyKpis($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id);

    $selected = [];

    $query = "
        SELECT *
        FROM project_consistency_kpi
        WHERE code_project_consistency = '$id'
          AND status != '99'
    ";

    $result = mysqli_query($db_connection, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $selected[] = intval($row['code_consistency_detail']);
    }

    return $selected;
}


// ใช้เลือกความสอดคล้องของโครงการ
function fetchAllAssoc($db, $sql)
{
    $q = mysqli_query($db, $sql);
    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }
    return $data;
}

// TYPE
function getKpiTypes($db)
{
    return pm_dedupe_set_rows_by_name(fetchAllAssoc($db, "SELECT * FROM set_type WHERE status='1' ORDER BY id ASC"));
}

/**
 * ตัดรายการ master ที่ชื่อซ้ำ (เก็บ id น้อยสุด) สำหรับ select ความสอดคล้อง
 */
function pm_dedupe_set_rows_by_name($rows)
{
    if (!is_array($rows) || !$rows) {
        return [];
    }
    $out = [];
    $seenId = [];
    $seenName = [];
    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }
        $id = trim((string)($r['id'] ?? ''));
        $name = trim((string)($r['name'] ?? ''));
        if ($id === '' || isset($seenId[$id])) {
            continue;
        }
        $nameKey = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        if ($nameKey !== '' && isset($seenName[$nameKey])) {
            continue;
        }
        $seenId[$id] = true;
        if ($nameKey !== '') {
            $seenName[$nameKey] = true;
        }
        $out[] = $r;
    }
    return $out;
}

/** รวม id ที่ชื่อเดียวกันในตาราง master (รองรับข้อมูลซ้ำ) */
function pm_collect_same_name_ids($db, string $table, string $id): array
{
    $id = trim($id);
    if ($id === '' || !$db) {
        return [];
    }
    $allowed = ['set_type' => true, 'set_side' => true, 'set_ap' => true, 'set_so' => true];
    if (!isset($allowed[$table])) {
        return [];
    }
    $ids = [];
    $ids[(string)intval($id)] = true;
    $ids[$id] = true;
    $esc = mysqli_real_escape_string($db, $id);
    $escInt = mysqli_real_escape_string($db, (string)intval($id));
    $q = @mysqli_query(
        $db,
        "SELECT t2.id
         FROM `{$table}` t1
         INNER JOIN `{$table}` t2 ON t2.name = t1.name AND t2.status = '1'
         WHERE t1.id = '{$esc}' OR t1.id = '{$escInt}'"
    );
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $tid = trim((string)($r['id'] ?? ''));
            if ($tid !== '' && preg_match('/^\d+$/', $tid)) {
                $ids[(string)intval($tid)] = true;
            }
        }
    }
    $out = [];
    foreach (array_keys($ids) as $tid) {
        if ($tid !== '' && preg_match('/^\d+$/', $tid)) {
            $out[] = (string)intval($tid);
        }
    }
    return array_values(array_unique($out));
}

/**
 * เหมือน pm_collect_same_name_ids แต่จำกัดเฉพาะแถวปีงบที่ตรง (หรือไม่ระบุปี)
 * กัน id ปีอื่นที่ชื่อซ้ำหลุดเข้าไปในตัวกรองตัวชี้วัด
 *
 * @return list<string>
 */
function pm_collect_same_name_ids_for_year($db, string $table, string $id, string $year): array
{
    $all = pm_collect_same_name_ids($db, $table, $id);
    $year = trim($year);
    if ($year === '' || !$all || !($db instanceof mysqli)) {
        return $all;
    }
    $allowed = ['set_type' => true, 'set_side' => true, 'set_ap' => true, 'set_so' => true];
    if (!isset($allowed[$table])) {
        return $all;
    }
    $yearVals = [];
    if (function_exists('kpi_type_year_norm')) {
        $yearVals = kpi_type_year_norm($year);
    } else {
        $digits = preg_replace('/\D+/', '', $year);
        $yearVals = array_values(array_unique(array_filter([$year, $digits, $digits !== '' ? substr($digits, -2) : ''])));
    }
    if (!$yearVals) {
        return $all;
    }
    $chk = @mysqli_query($db, "SHOW COLUMNS FROM `{$table}` LIKE 'kpi_year'");
    if (!$chk || mysqli_num_rows($chk) === 0) {
        return $all;
    }
    $inIds = implode(',', array_map('intval', $all));
    $inYears = [];
    foreach ($yearVals as $y) {
        $inYears[] = "'" . mysqli_real_escape_string($db, $y) . "'";
    }
    $yearIn = implode(',', $inYears);
    $q = @mysqli_query(
        $db,
        "SELECT id FROM `{$table}`
         WHERE id IN ({$inIds}) AND status = '1'
           AND (kpi_year IN ({$yearIn}) OR kpi_year IS NULL OR TRIM(kpi_year) = '')"
    );
    $out = [];
    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $tid = (string) intval($r['id'] ?? 0);
        if ($tid !== '0') {
            $out[] = $tid;
        }
    }
    // ถ้ากรองปีแล้วไม่เหลือ ให้ใช้ id เดิมที่เรียกมา (กันพัง)
    return $out ?: [(string) intval($id)];
}

// SO
function getSOList($db)
{
    return pm_dedupe_set_rows_by_name(fetchAllAssoc($db, "SELECT * FROM set_so WHERE status='1' ORDER BY id ASC"));
}

// AP
function getAPList($db)
{
    return pm_dedupe_set_rows_by_name(fetchAllAssoc($db, "SELECT * FROM set_ap WHERE status='1' ORDER BY id ASC"));
}

// SIDE
function getSideList($db)
{
    return pm_dedupe_set_rows_by_name(fetchAllAssoc($db, "SELECT * FROM set_side WHERE status='1' ORDER BY id ASC"));
}

// Project header consistency
function getProjectConsistencyRow($db, $project_id)
{
    $rows = getProjectConsistencyRows($db, $project_id);
    return $rows ? $rows[0] : null;
}

if (!function_exists('pm_consistency_set_copy')) {
	function pm_consistency_set_copy(string $group, bool $isPrimary): array
	{
		$role = $isPrimary ? 'หลัก' : 'ร่วม';
		$level2 = 'ด้าน/แผนปฏิบัติการ (AP)';
		$hint = '';
		$addBtn = '+ เพิ่มรายการและตัวชี้วัด';
		$need = 'กรุณาเลือกรายการ';
		$ph = '- เลือก -';
		if ($group === 'strategy') {
			$level2 = $isPrimary ? 'แผนปฏิบัติการ (AP)' : 'แผนปฏิบัติการ (AP) ร่วม';
			$hint = 'เลือกแผนปฏิบัติการ' . $role . 'ที่โครงการมีส่วนสนับสนุนให้บรรลุเป้าหมาย';
			$addBtn = '+ เพิ่มแผนปฏิบัติการและตัวชี้วัด';
			$need = 'กรุณาเลือกแผนปฏิบัติการ (AP)';
			$ph = '- เลือก AP -';
		} elseif ($group === 'mission') {
			$level2 = $isPrimary ? 'พันธกิจที่สนับสนุน' : 'พันธกิจที่สนับสนุนร่วม';
			$hint = 'เลือกพันธกิจ' . $role . 'ที่โครงการมีส่วนสนับสนุน';
			$addBtn = '+ เพิ่มพันธกิจที่สนับสนุนและตัวชี้วัด';
			$need = 'กรุณาเลือกพันธกิจที่สนับสนุน';
			$ph = '- เลือกพันธกิจ -';
		} elseif ($group === 'develop') {
			$level2 = $isPrimary ? 'ด้านที่พัฒนา' : 'ด้านที่พัฒนาร่วม';
			$hint = 'เลือกด้าน' . $role . 'ที่โครงการมุ่งให้เกิดการเปลี่ยนแปลงหรือปรับปรุง';
			$addBtn = '+ เพิ่มด้านที่พัฒนาและตัวชี้วัด';
			$need = 'กรุณาเลือกด้านที่พัฒนา';
			$ph = '- เลือกด้าน -';
		}
		return [
			'level2Label' => $level2,
			'hint' => $hint,
			'addBtn' => $addBtn,
			'need' => $need,
			'placeholder' => $ph,
		];
	}
}

/**
 * จัดกลุ่มสรุปคำขอขอบรรจุตามความสอดคล้อง
 * เดิมผูกแค่ code_kpi_type = '1'|'2' ทำให้โครงการที่ set_type ใช้ id อื่นถูกมองว่าไม่มีความสอดคล้อง
 * รองรับ: ลำดับประเภทจาก getKpiTypes(), อนุมานจาก code_ap/code_side, ชื่อจาก getSetType, หรือมีแต่ KR ใน project_consistency_kpi
 *
 * @param mysqli|null $db_KPI
 * @param mysqli|null $db_Project
 * @return array{0:string,1:string,2:string} [consKind, consKey, consLabel] consKind = strategic|mission|''
 */
function pm_include_consistency_bucket_meta($db_KPI, $db_Project, $mPc, array $include_ap_name_by_id, array $include_side_name_by_id)
{
    $mConsKind = '';
    $mConsKey = 'unassigned';
    $mConsLabel = 'ยังไม่ระบุความสอดคล้อง';

    if (empty($mPc) || !is_array($mPc)) {
        return [$mConsKind, $mConsKey, $mConsLabel];
    }

    $typesOrdered = ($db_KPI && function_exists('getKpiTypes')) ? getKpiTypes($db_KPI) : [];
    $missionIds = [];
    $strategicIds = [];
    foreach ($typesOrdered as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $tid = isset($row['id']) ? (string)$row['id'] : '';
        if ($tid === '') {
            continue;
        }
        if ($idx === 0) {
            $missionIds[$tid] = true;
        } elseif ($idx === 1) {
            $strategicIds[$tid] = true;
        }
    }
    if (!$missionIds && !$strategicIds) {
        $missionIds['1'] = true;
        $strategicIds['2'] = true;
    }

    $ct = trim((string)($mPc['code_kpi_type'] ?? ''));
    $apid = trim((string)($mPc['code_ap'] ?? ''));
    $sideid = trim((string)($mPc['code_side'] ?? ''));

    $isMission = ($ct !== '' && isset($missionIds[$ct]));
    $isStrategic = ($ct !== '' && isset($strategicIds[$ct]));

    if (!$isMission && !$isStrategic && $ct !== '') {
        if ($apid !== '') {
            $isStrategic = true;
        } elseif ($sideid !== '') {
            $isMission = true;
        }
    }
    if (!$isMission && !$isStrategic && $ct === '') {
        if ($apid !== '') {
            $isStrategic = true;
        } elseif ($sideid !== '') {
            $isMission = true;
        }
    }

    if ($isStrategic) {
        $mConsKind = 'strategic';
        if ($apid !== '') {
            $mConsKey = 'ap:' . $apid;
            $mConsLabel = $include_ap_name_by_id[$apid] ?? ('แผนปฏิบัติการ (AP) (' . $apid . ')');
        } else {
            $mConsKey = 'strategic:pending';
            $mConsLabel = 'เชิงยุทธศาสตร์ (ยังไม่เลือก AP)';
        }

        return [$mConsKind, $mConsKey, $mConsLabel];
    }

    if ($isMission) {
        $mConsKind = 'mission';
        if ($sideid !== '') {
            $mConsKey = 'side:' . $sideid;
            $mConsLabel = $include_side_name_by_id[$sideid] ?? ('ด้าน (' . $sideid . ')');
        } else {
            $mConsKey = 'mission:pending';
            $mConsLabel = 'เชิงพันธกิจ (ยังไม่เลือกด้าน)';
        }

        return [$mConsKind, $mConsKey, $mConsLabel];
    }

    if ($ct !== '' && function_exists('getSetType') && $db_KPI) {
        $t = getSetType($db_KPI, $ct);
        $tn = trim((string)($t['name'] ?? ''));
        if ($tn !== '') {
            $mConsKind = 'mission';
            $mConsKey = 'type:' . $ct;
            $mConsLabel = $tn;

            return [$mConsKind, $mConsKey, $mConsLabel];
        }
    }

    $pcid = (int)($mPc['id'] ?? 0);
    if ($pcid > 0 && $db_Project) {
        $pcidEsc = (string)(int)$pcid;
        $qk = @mysqli_query(
            $db_Project,
            "SELECT 1 FROM project_consistency_kpi WHERE code_project_consistency='" . $pcidEsc . "' AND status!='99' LIMIT 1"
        );
        if ($qk && mysqli_fetch_row($qk)) {
            $mConsKind = 'mission';
            $mConsKey = 'kpi-filled';
            $mConsLabel = 'ระบุตัวชี้วัดแล้ว (ยังไม่ระบุประเภทแผน/ด้าน)';

            return [$mConsKind, $mConsKey, $mConsLabel];
        }
    }

    return [$mConsKind, $mConsKey, $mConsLabel];
}

// Selected KPI ของโครงการ
function getProjectSelectedKpi($db, $project_id)
{
    $sql = "SELECT code_kpi_detail 
            FROM project_consistency_kpi k
            JOIN project_consistency pc ON k.code_project_consistency = pc.id
            WHERE pc.project_id='$project_id' 
              AND pc.status!='99'
              AND k.status!='99'";

    $rows = fetchAllAssoc($db, $sql);
    return array_column($rows, 'code_kpi_detail');
}

// KPI YEAR INDICATOR - แสดงตัวชี้วัดตามการเลือก ประเภท(type) SO AP ด้าน(side) และปีงบ(year)
// code_kpi_year ใน kpi_year_indicator ชี้ไปที่ kpi_year.id ไม่ใช่ kpi.id จึงต้อง JOIN ผ่าน kpi_year
// หมายเหตุ: บางปี kpi_year.status ถูกตั้งเป็น '99' ทั้งที่ indicator ยัง status='1'
// จึงไม่กรอง ky.status ออกตอนเลือกความสอดคล้อง (ใช้ yi.status เป็นหลัก)
function getKpiYearIndicatorSelect($db, $filter = [])
{
    // ให้ Project เห็นเฉพาะตัวชี้วัดที่ติ๊ก "ส่งออกไปยังระบบโครงการ"
    $exportFilter = '';
    static $exportColOk = null;
    if ($exportColOk === null) {
        $exportColOk = false;
        $chk = @mysqli_query($db, "SHOW COLUMNS FROM kpi_year_indicator LIKE 'export_to_project'");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $exportColOk = true;
        } else {
            @mysqli_query(
                $db,
                "ALTER TABLE kpi_year_indicator ADD COLUMN export_to_project CHAR(1) NOT NULL DEFAULT '0' AFTER notify_months"
            );
            $chk2 = @mysqli_query($db, "SHOW COLUMNS FROM kpi_year_indicator LIKE 'export_to_project'");
            $exportColOk = ($chk2 && mysqli_num_rows($chk2) > 0);
        }
    }
    if ($exportColOk) {
        $exportFilter = "(yi.export_to_project = '1' OR yi.export_to_project = 1)";
    }

    $run = function ($where) use ($db) {
        if (empty($where)) return [];
        $sql = "
            SELECT yi.*, k.id AS kpi_id, k.name AS kpi_name
            FROM kpi_year_indicator yi
            INNER JOIN kpi_year ky ON yi.code_kpi_year = ky.id
            INNER JOIN kpi k ON ky.code_kpi = k.id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY yi.id ASC
        ";
        $result = mysqli_query($db, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    };

    $where = [];
    if ($exportFilter !== '') {
        $where[] = $exportFilter;
    }
    if (!empty($filter['year'])) {
        $yr = mysqli_real_escape_string($db, $filter['year']);
        $yr2 = substr(preg_replace('/\D+/', '', (string)$filter['year']), -2);
        if ($yr2 !== '') {
            $where[] = "(ky.kpi_year='" . $yr . "' OR ky.kpi_year='" . mysqli_real_escape_string($db, $yr2) . "')";
        } else {
            $where[] = "ky.kpi_year='" . $yr . "'";
        }
    }
    // รองรับ master ซ้ำชื่อคนละ id — แต่จำกัดปีงบเมื่อมี year ใน filter
    $filterYear = isset($filter['year']) ? trim((string)$filter['year']) : '';
    $collectIds = static function ($db, string $table, string $id) use ($filterYear): array {
        if ($filterYear !== '' && function_exists('pm_collect_same_name_ids_for_year')) {
            return pm_collect_same_name_ids_for_year($db, $table, $id, $filterYear);
        }
        return pm_collect_same_name_ids($db, $table, $id);
    };
    $typeList = [];
    $typeRaw = isset($filter['type']) ? trim((string)$filter['type']) : '';
    if ($typeRaw !== '') {
        $typeList = $collectIds($db, 'set_type', $typeRaw);
        if ($typeList) {
            $where[] = "yi.code_type IN (" . implode(',', $typeList) . ")";
        } else {
            $where[] = "yi.code_type=" . intval($typeRaw);
        }
    }
    if (!empty($filter['so'])) {
        $soIds = $collectIds($db, 'set_so', (string)$filter['so']);
        if (!$soIds) {
            $soIds = [(string)intval($filter['so'])];
        }
        $soOr = [];
        foreach ($soIds as $sid) {
            $so = (int)$sid;
            $so_esc = mysqli_real_escape_string($db, $sid);
            $soOr[] = "(yi.code_so={$so} OR yi.code_so='{$so_esc}' OR EXISTS (
                SELECT 1 FROM kpi_year_indicator_so yso
                WHERE yso.code_kpi_year = yi.code_kpi_year AND yso.code_so = {$so} AND yso.status = '1'
            ))";
        }
        if ($soOr) {
            $where[] = '(' . implode(' OR ', $soOr) . ')';
        }
    }
    $ap_val = isset($filter['ap']) ? trim((string)$filter['ap']) : '';
    if ($ap_val !== '') {
        $apIds = $collectIds($db, 'set_ap', $ap_val);
        if (!$apIds) {
            $apIds = [(string)intval($ap_val)];
        }
        $apParts = [];
        foreach ($apIds as $aid) {
            $ap_esc = mysqli_real_escape_string($db, $aid);
            $apParts[] = "(yi.code_ap=" . intval($aid) . " OR yi.code_ap='" . $ap_esc . "')";
        }
        $where[] = '(' . implode(' OR ', $apParts) . ')';
    }
    $side_val = isset($filter['side']) ? trim((string)$filter['side']) : '';
    if ($side_val !== '') {
        $sideIds = $collectIds($db, 'set_side', $side_val);
        if (!$sideIds) {
            $sideIds = [(string)intval($side_val)];
        }
        $sideParts = [];
        foreach ($sideIds as $sid) {
            $side_esc = mysqli_real_escape_string($db, $sid);
            $sideParts[] = "(yi.code_side=" . intval($sid) . " OR yi.code_side='" . $side_esc . "')";
        }
        $where[] = '(' . implode(' OR ', $sideParts) . ')';
    }
    $where[] = "yi.status='1'";

    $data = $run($where);
    // ถ้าเลือกด้าน/AP แล้วไม่พบ: ผ่อนเฉพาะ type+ปี (ไม่ดึงทั้งปีข้ามกรอบแผน)
    $hasLevel2 = ($ap_val !== '' || $side_val !== '');
    if (empty($data) && $hasLevel2 && $typeRaw !== '' && $filterYear !== '') {
        $whereFallback = ["yi.status='1'"];
        if ($exportFilter !== '') {
            $whereFallback[] = $exportFilter;
        }
        $yr = mysqli_real_escape_string($db, $filterYear);
        $yr2 = substr(preg_replace('/\D+/', '', $filterYear), -2);
        if ($yr2 !== '') {
            $whereFallback[] = "(ky.kpi_year='" . $yr . "' OR ky.kpi_year='" . mysqli_real_escape_string($db, $yr2) . "')";
        } else {
            $whereFallback[] = "ky.kpi_year='" . $yr . "'";
        }
        if (!empty($typeList)) {
            $whereFallback[] = "yi.code_type IN (" . implode(',', $typeList) . ")";
        } else {
            $whereFallback[] = "yi.code_type=" . intval($typeRaw);
        }
        $data = $run($whereFallback);
    }
    return $data;
}
// จบ ใช้เลือกความสอดคล้องของโครงการ

function getProjectDraftList($db, $year, $staffcode)
{
    $sql = "
        SELECT p.id, p.code_pre_project, p.name_project,
               IFNULL(c.code_consistency, '-') AS consistency_text,
               IFNULL((SELECT SUM(total) FROM project_activity_budget pa WHERE pa.code_pre_project = p.id AND pa.status != '99'), 0) AS budget
        FROM pre_project p
        LEFT JOIN project_consistency c ON p.id = c.project_id AND c.status != '99'
        WHERE p.year_project = '$year'
          AND p.staff_insert = '$staffcode'
          AND p.status_project IN ('0','8')
        ORDER BY p.code_pre_project ASC
    ";

    return fetchAllAssoc($db, $sql);
}

/**
 * ดึงรายการโครงการสำหรับหน้าภาพรวม (ปีงบ, รหัส, ชื่อ, หน่วยงาน, งบ, เบิกจ่าย, %ความสำเร็จ)
 * กรองด้วย ปีงบประมาณ, หน่วยงาน (code_unit), คำค้น (ชื่อ/รหัส)
 */
function getOverviewProjectList($db_Project, $years_select, $code_unit_filter = '', $keyword = '')
{
    if (function_exists('ensureProjectExtraSchema')) {
        ensureProjectExtraSchema($db_Project);
    }
    if (function_exists('pm_ensure_midyear_schema')) {
        pm_ensure_midyear_schema($db_Project);
    }
    if (function_exists('ensureProjectResultReportSchema')) {
        ensureProjectResultReportSchema($db_Project);
    }
    $years_select = mysqli_real_escape_string($db_Project, $years_select);
    $code_unit_filter = trim($code_unit_filter);
    $keyword = trim($keyword);

    $sql = "
        SELECT p.id, p.year_project, p.code_pre_project, p.name_project, p.code_unit,
               p.staff_insert, p.status_project, p.notify_result, p.notify_result_note,
               p.user_fix_note, p.operate_submitter_reply_note, p.midyear_request,
               p.result_report_submitted_at, p.result_report_completed_at,
               IFNULL((SELECT SUM(pa.total) FROM project_activity_budget pa WHERE pa.code_pre_project = p.id AND pa.status != '99'), 0) AS budget_total,
               IFNULL((
                   SELECT SUM(CAST(IFNULL(l.amount, 0) AS DECIMAL(18, 2)))
                   FROM project_budget_disburse_line l
                   INNER JOIN project_budget_disburse_report r ON r.id = l.report_id
                   WHERE r.project_id = p.id
                     AND l.status = '1'
                     AND (r.status IS NULL OR r.status = '' OR r.status != '99')
               ), 0) AS disburse_total
        FROM pre_project p
        WHERE p.status = '1' AND p.year_project = ?
    ";
    $params = [$years_select];
    $types = 's';

    if ($code_unit_filter !== '') {
        $sql .= " AND p.code_unit = ?";
        $params[] = $code_unit_filter;
        $types .= 's';
    }
    if ($keyword !== '') {
        $sql .= " AND (p.name_project LIKE ? OR p.code_pre_project LIKE ?)";
        $kw = '%' . $keyword . '%';
        $params[] = $kw;
        $params[] = $kw;
        $types .= 'ss';
    }
    $sql .= " ORDER BY p.code_pre_project ASC";

    $stmt = mysqli_prepare($db_Project, $sql);
    if (!$stmt) return [];
    $bind_args = [$types];
    foreach ($params as $k => $v) {
        $bind_args[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_args);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $list = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $list[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $list;
}

/**
 * ตรวจสอบว่า staff เป็น Admin ของหน่วยงานหรือไม่
 * - set_department_admin.staff_admin เก็บได้ทั้ง staff.code และ staff.id
 * - set_project_personnel.is_unit_admin (ตั้งจากหน้า Plan) เป็นแหล่งสิทธิ์ร่วม
 * - $unit_code รับไว้เพื่อความเข้ากันได้ของโค้ดเดิม (ขอบเขตหน่วยใช้ planCollectStaffUnitScopeKeys)
 */
function isUnitAdmin($db_Project, $staff_code, $unit_code = '', $staff_id = 0)
{
    if (!$db_Project) {
        return false;
    }
    $staff_code = trim((string) $staff_code);
    $sid = (int) $staff_id;
    global $db_person;
    if ($sid <= 0 && $staff_code !== '' && !empty($db_person)) {
        $codeEsc = mysqli_real_escape_string($db_person, $staff_code);
        $qid = @mysqli_query($db_person, "SELECT id FROM staff WHERE code = '$codeEsc' LIMIT 1");
        if ($qid && ($idr = mysqli_fetch_assoc($qid))) {
            $sid = (int) ($idr['id'] ?? 0);
        }
    }
    if ($sid <= 0 && $staff_code === '') {
        return false;
    }
    $hit = false;
    if (function_exists('ensureSetDepartmentAdminTable')) {
        ensureSetDepartmentAdminTable($db_Project);
    }
    $matchParts = [];
    if ($staff_code !== '') {
        $codeEsc = mysqli_real_escape_string($db_Project, $staff_code);
        $matchParts[] = "sda.staff_admin = '$codeEsc'";
    }
    if ($sid > 0) {
        $matchParts[] = "sda.staff_admin = '" . (int) $sid . "'";
        $matchParts[] = "sda.staff_admin = '" . mysqli_real_escape_string($db_Project, (string) $sid) . "'";
    }
    if (!empty($matchParts)) {
        $match = implode(' OR ', $matchParts);
        $q = @mysqli_query($db_Project, "SELECT 1 FROM set_department_admin sda WHERE ($match) LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $hit = true;
        }
    }
    if ($sid > 0) {
        if (function_exists('ensureSetProjectPersonnelTable')) {
            ensureSetProjectPersonnelTable($db_Project);
        }
        $qpp = @mysqli_query(
            $db_Project,
            "SELECT 1 FROM set_project_personnel
             WHERE staff_id = '" . (int) $sid . "' AND is_unit_admin = 1 AND status != '99'
             LIMIT 1"
        );
        if ($qpp && mysqli_num_rows($qpp) > 0) {
            $hit = true;
        }
    }
    if (!$hit) {
        return false;
    }
    if ($sid > 0 && function_exists('planCollectStaffUnitScopeKeys')) {
        try {
            $scope = planCollectStaffUnitScopeKeys($db_Project, $sid, (string) $unit_code);
            return !empty($scope);
        } catch (Throwable $e) {
            return $hit;
        }
    }
    return $hit;
}

/** รวบรวมรหัสหน่วยงาน (set_unit.id / code) ที่ผู้ใช้เป็น Admin จริง — ไม่ใช้ unit_code จาก HR */
if (!function_exists('planCollectStaffUnitScopeKeys')) {
	function planCollectStaffUnitScopeKeys($db_Project, int $staff_id, string $staff_unit_code = ''): array
	{
		$keys = [];
		$addCanonical = static function ($raw) use ($db_Project, &$keys) {
			$raw = trim((string) $raw);
			if ($raw === '' || $raw === '99' || !$db_Project) {
				return;
			}
			$esc = mysqli_real_escape_string($db_Project, $raw);
			$row = null;
			$qId = @mysqli_query(
				$db_Project,
				"SELECT * FROM set_unit
				 WHERE status != '99' AND CAST(id AS CHAR) = '$esc'
				 LIMIT 1"
			);
			$row = $qId ? mysqli_fetch_assoc($qId) : null;
			if (!$row) {
				$qCode = @mysqli_query(
					$db_Project,
					"SELECT * FROM set_unit
					 WHERE status != '99' AND IFNULL(code,'') = '$esc'
					 LIMIT 1"
				);
				$row = $qCode ? mysqli_fetch_assoc($qCode) : null;
			}
			if (!is_array($row) || empty($row)) {
				return;
			}
			if (function_exists('planSetUnitGroupNumber') && planSetUnitGroupNumber($row) !== 2) {
				return;
			}
			$id = (int) ($row['id'] ?? 0);
			if ($id > 0) {
				$keys[(string) $id] = true;
			}
			$code = trim((string) ($row['code'] ?? ''));
			if ($code !== '') {
				$keys[$code] = true;
			}
		};

		if ($db_Project && $staff_id > 0) {
			if (function_exists('ensureSetProjectPersonnelTable')) {
				ensureSetProjectPersonnelTable($db_Project);
			}
			$q = @mysqli_query(
				$db_Project,
				"SELECT code_unit FROM set_project_personnel
				 WHERE staff_id='" . (int) $staff_id . "' AND is_unit_admin=1 AND status!='99'"
			);
			while ($q && ($r = mysqli_fetch_assoc($q))) {
				$addCanonical((string) ($r['code_unit'] ?? ''));
			}
			if (function_exists('ensureSetDepartmentAdminTable')) {
				ensureSetDepartmentAdminTable($db_Project);
			}
			global $db_person;
			$staffOr = "sda.staff_admin = '" . (int) $staff_id . "'";
			if (!empty($db_person)) {
				$qs = @mysqli_query($db_person, "SELECT id, code FROM staff WHERE id='" . (int) $staff_id . "' LIMIT 1");
				$rs = $qs ? mysqli_fetch_assoc($qs) : null;
				$code = mysqli_real_escape_string($db_Project, (string) ($rs['code'] ?? ''));
				if ($code !== '') {
					$staffOr = "($staffOr OR sda.staff_admin = '$code')";
				}
			}
			$q2 = @mysqli_query(
				$db_Project,
				"SELECT unit_code FROM set_department_admin sda
				 WHERE $staffOr AND IFNULL(unit_code,'') <> ''"
			);
			while ($q2 && ($r2 = mysqli_fetch_assoc($q2))) {
				$addCanonical((string) ($r2['unit_code'] ?? ''));
			}
		}
		return array_keys($keys);
	}
}

/**
 * ดึงรายการโครงการสำหรับหน้าจัดการโครงการ
 * แสดง:
 * - ผู้ใช้ทั่วไป: โครงการที่มีชื่อเรา (สร้าง / หัวหน้า / ผู้ประสาน / ผู้ร่วม)
 * - Admin หน่วยงาน: โครงการที่มีชื่อเรา + โครงการของหน่วยที่ได้รับสิทธิ์ Admin (ไม่ดึงหน่วยจาก HR)
 * - PLAN_ADMIN: ทุกโครงการในปีงบประมาณ
 */
function getManageableProjectList($db_Project, $years_select, $staff_id, $staff_code, $is_unit_admin, $staff_unit_code = '')
{
    if (!$db_Project) {
        return [];
    }
    $years_select = mysqli_real_escape_string($db_Project, $years_select);
    $staff_id_esc = mysqli_real_escape_string($db_Project, (string) $staff_id);
    $staff_id_int = (int) $staff_id;
    $staff_code = mysqli_real_escape_string($db_Project, (string) $staff_code);
    $staff_unit_code = trim((string) $staff_unit_code);

    // โหมด Admin ของฝั่ง Plan: เห็นได้ทุกโครงการในปีงบประมาณนั้น
    if (defined('PLAN_ADMIN') && PLAN_ADMIN) {
        $where = "p.status = '1' AND p.year_project = '$years_select'";
    } else {
        // โครงการที่มีชื่อเราเกี่ยวข้อง (หลัก + ร่วม)
        $memberOr = "p.staff_insert = '$staff_id_esc'";
        if ($staff_id_int > 0) {
            $memberOr .= "
                OR EXISTS (
                    SELECT 1 FROM project_head ph
                    WHERE ph.project_id = p.id AND ph.status != '99' AND ph.staff_project = '$staff_id_int'
                )
                OR EXISTS (
                    SELECT 1 FROM project_staff ps
                    WHERE ps.project_id = p.id AND ps.status != '99' AND ps.staff_project = '$staff_id_int'
                )
                OR EXISTS (
                    SELECT 1 FROM project_person pp
                    WHERE pp.project_id = p.id AND pp.status != '99' AND pp.staff_project = '$staff_id_int'
                )";
        }

        $unitScopeSql = '';
        if (!empty($is_unit_admin) && ($staff_id_int > 0 || $staff_unit_code !== '')) {
            $unitKeys = function_exists('planCollectStaffUnitScopeKeys')
                ? planCollectStaffUnitScopeKeys($db_Project, $staff_id_int, $staff_unit_code)
                : [];
            $unitKeys = array_values(array_filter(array_map('strval', $unitKeys), static function ($k) {
                return trim($k) !== '';
            }));
            if (!empty($unitKeys)) {
                $inList = [];
                foreach ($unitKeys as $uk) {
                    $inList[] = "'" . mysqli_real_escape_string($db_Project, $uk) . "'";
                }
                $inSql = implode(',', $inList);
                // หน่วยงานหลักของโครงการ หรือหน่วยงานร่วม (project_department level=1)
                $unitScopeSql = "
                (
                    IFNULL(p.code_unit,'') <> ''
                    AND (
                        p.code_unit IN ($inSql)
                        OR EXISTS (
                            SELECT 1 FROM set_unit su
                            WHERE su.status != '99'
                              AND (CAST(su.id AS CHAR) = p.code_unit OR IFNULL(su.code,'') = p.code_unit)
                              AND (CAST(su.id AS CHAR) IN ($inSql) OR IFNULL(su.code,'') IN ($inSql))
                        )
                    )
                )
                OR EXISTS (
                    SELECT 1 FROM project_department pd
                    WHERE pd.project_id = p.id
                      AND pd.level = '1'
                      AND pd.status != '99'
                      AND IFNULL(pd.department_project,'') <> ''
                      AND pd.department_project <> '99'
                      AND (
                        pd.department_project IN ($inSql)
                        OR EXISTS (
                            SELECT 1 FROM set_unit su3
                            WHERE su3.status != '99'
                              AND (CAST(su3.id AS CHAR) = pd.department_project OR IFNULL(su3.code,'') = pd.department_project)
                              AND (CAST(su3.id AS CHAR) IN ($inSql) OR IFNULL(su3.code,'') IN ($inSql))
                        )
                      )
                )";
            }
        }

        if ($unitScopeSql !== '') {
            $where = "p.status = '1' AND p.year_project = '$years_select'
                AND (
                    ($memberOr)
                    OR ($unitScopeSql)
                )";
        } else {
            $where = "p.status = '1' AND p.year_project = '$years_select'
                AND (
                    $memberOr
                )";
        }
    }

    $sql = "
        SELECT p.*,
               IFNULL((SELECT SUM(pa.total) FROM project_activity_budget pa WHERE pa.code_pre_project = p.id AND pa.status != '99'), 0) AS budget_total
        FROM pre_project p
        WHERE $where
        ORDER BY p.code_pre_project ASC
    ";
    $result = @mysqli_query($db_Project, $sql);
    $list = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
    }
    return $list;
}

/**
 * เติมโครงการที่ผู้ยื่นโดนตีกลับคำขอบรรจุเข้าไปในรายการจัดการ
 * เพื่อไม่ให้หายจากแท็บร่างเมื่อผู้ยื่นไม่ใช่ staff_insert ของโครงการ
 */
function pm_append_include_returned_projects_for_submitter($db_Project, array $project_list, $years_select, $staff_id): array
{
    $staff_id = (int)$staff_id;
    $years_select = trim((string)$years_select);
    if (!$db_Project || $staff_id <= 0 || $years_select === '') {
        return $project_list;
    }
    $existingIds = [];
    foreach ($project_list as $__p) {
        $pid = (int)($__p['id'] ?? 0);
        if ($pid > 0) {
            $existingIds[$pid] = 1;
        }
    }
    $ysEsc = mysqli_real_escape_string($db_Project, $years_select);
    $qRet = @mysqli_query(
        $db_Project,
        "SELECT p.*,
                IFNULL((SELECT SUM(pa.total) FROM project_activity_budget pa WHERE pa.code_pre_project = p.id AND pa.status != '99'), 0) AS budget_total
         FROM pre_project p
         INNER JOIN project_include_request_item i ON i.project_id = p.id
         INNER JOIN project_include_request ir ON ir.id = i.request_id
         WHERE p.status='1'
           AND p.year_project='" . $ysEsc . "'
           AND ir.status='rejected'
           AND (IFNULL(i.return_selected,0)=1 OR p.status_project='8')
           AND ir.submitter_staff_id='" . $staff_id . "'
         GROUP BY p.id"
    );
    while ($qRet && ($ar = mysqli_fetch_assoc($qRet))) {
        $pid = (int)($ar['id'] ?? 0);
        if ($pid <= 0 || !empty($existingIds[$pid])) {
            continue;
        }
        $project_list[] = $ar;
        $existingIds[$pid] = 1;
    }
    return $project_list;
}

/**
 * จัดกลุ่มโครงการในคำขอขอบรรจุตามความสอดคล้อง (ตรรกะเดียวกับ project_manage.js buildIncludeSummaryRowsForSave)
 * คืนค่าแมปคีย์ "section_group\0consistency_label" => รายการโครงการพร้อมชื่อตัวชี้วัดเป้าหมาย
 *
 * @return array<string, array<int, array{project_id:int,name:string,year_project:string,kpi_names:string[]}>>
 */
function buildIncludeRequestSummaryProjectBuckets($db_Project, $db_KPI, $request_id)
{
    $request_id = (int)$request_id;
    if ($request_id <= 0) {
        return [];
    }

    $include_ap_name_by_id = [];
    if (function_exists('getAPList')) {
        foreach (getAPList($db_KPI) as $__aprow) {
            $__apid = (string)($__aprow['id'] ?? '');
            if ($__apid !== '') {
                $include_ap_name_by_id[$__apid] = (string)($__aprow['name'] ?? '');
            }
        }
    }
    $include_side_name_by_id = [];
    if (function_exists('getSideList')) {
        foreach (getSideList($db_KPI) as $__sdrow) {
            $__sid = (string)($__sdrow['id'] ?? '');
            if ($__sid !== '') {
                $include_side_name_by_id[$__sid] = (string)($__sdrow['name'] ?? '');
            }
        }
    }

    $buckets = [];
    $sql = "
        SELECT i.project_id, p.name_project, p.year_project
        FROM project_include_request_item i
        INNER JOIN pre_project p ON p.id = i.project_id
        WHERE i.request_id='" . $request_id . "'
        ORDER BY i.sort_order ASC, i.id ASC
    ";
    $q = @mysqli_query($db_Project, $sql);
    while ($q && ($row = mysqli_fetch_assoc($q))) {
        $pid = (int)($row['project_id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }

        $mPc = function_exists('getProjectConsistencyRow') ? getProjectConsistencyRow($db_Project, $pid) : null;
        list($mConsKind, $mConsKey, $mConsLabel) = pm_include_consistency_bucket_meta(
            $db_KPI,
            $db_Project,
            is_array($mPc) ? $mPc : [],
            $include_ap_name_by_id,
            $include_side_name_by_id
        );

        $sg = 'other';
        if ($mConsKind === 'strategic') {
            $sg = 'strategic';
        } elseif ($mConsKind === 'mission') {
            $sg = 'mission';
        }

        $bid = $mConsKind . "\0" . $mConsKey;
        if (!isset($buckets[$bid])) {
            $buckets[$bid] = [
                'section_group' => $sg,
                'label' => $mConsLabel,
                'items' => [],
            ];
        }

        $kpiNames = function_exists('pmProjectKpiDisplayLabels')
            ? pmProjectKpiDisplayLabels($db_Project, $db_KPI, $pid)
            : [];

        $budgetTotal = 0;
        $qBt = @mysqli_query(
            $db_Project,
            "SELECT IFNULL(SUM(pa.total),0) AS t FROM project_activity_budget pa WHERE pa.code_pre_project='" . $pid . "' AND pa.status!='99'"
        );
        if ($qBt && ($rBt = mysqli_fetch_assoc($qBt))) {
            $budgetTotal = (float)($rBt['t'] ?? 0);
        }

        $buckets[$bid]['items'][] = [
            'project_id' => $pid,
            'name' => (string)($row['name_project'] ?? ''),
            'year_project' => (string)($row['year_project'] ?? ''),
            'kpi_names' => $kpiNames,
            'budget_total' => $budgetTotal,
        ];
    }

    $map = [];
    foreach ($buckets as $b) {
        $sgKey = (string)($b['section_group'] ?? '');
        $labKey = (string)($b['label'] ?? '');
        $map[$sgKey . "\0" . $labKey] = $b['items'];
    }

    return $map;
}

/**
 * สร้างแถวสรุปสำหรับโมดัลรายละเอียดคำขอ — คำนวณจากโครงการจริงในคำขอเท่านั้น
 * (ไม่ผูกกับ project_include_request_summary ที่บันทึกตอนยื่น เพราะคีย์กลุ่มอาจไม่ตรงหลังปรับตรรกะความสอดคล้อง)
 *
 * @return array<int, array<string, mixed>>
 */
function buildIncludeRequestDetailSummaryRows($db_Project, $db_KPI, $request_id)
{
    $request_id = (int)$request_id;
    if ($request_id <= 0 || !function_exists('buildIncludeRequestSummaryProjectBuckets')) {
        return [];
    }

    $map = buildIncludeRequestSummaryProjectBuckets($db_Project, $db_KPI, $request_id);
    if (empty($map)) {
        return [];
    }

    $labelsBySg = [
        'strategic' => [],
        'mission' => [],
        'other' => [],
    ];
    foreach ($map as $lk => $items) {
        $parts = explode("\0", (string)$lk, 2);
        $sg = (string)($parts[0] ?? '');
        $lab = isset($parts[1]) ? (string)$parts[1] : '';
        if (!isset($labelsBySg[$sg])) {
            $sg = 'other';
        }
        $labelsBySg[$sg][$lab] = is_array($items) ? $items : [];
    }

    $sectionHead = [
        'strategic' => 'เชิงยุทธศาสตร์',
        'mission' => 'เชิงพันธกิจ',
        'other' => 'ยังไม่ระบุ / อื่น ๆ',
    ];

    $rows = [];
    $sortOrder = 0;
    $grandProjects = 0;
    $grandBudget = 0.0;

    foreach (['strategic', 'mission', 'other'] as $sg) {
        $bucketMap = $labelsBySg[$sg];
        if (empty($bucketMap)) {
            continue;
        }

        $labels = array_keys($bucketMap);
        sort($labels, SORT_STRING);

        $rows[] = [
            'row_kind' => 'header',
            'section_group' => $sg,
            'consistency_label' => $sectionHead[$sg],
            'project_count' => null,
            'budget_baht' => null,
            'sort_order' => $sortOrder++,
            'include_project_items' => [],
        ];

        foreach ($labels as $lab) {
            $items = $bucketMap[$lab];
            if (!is_array($items)) {
                continue;
            }
            $cnt = count($items);
            $sumB = 0.0;
            $displayItems = [];
            foreach ($items as $it) {
                if (!is_array($it)) {
                    continue;
                }
                $sumB += (float)($it['budget_total'] ?? 0);
                $displayItems[] = [
                    'project_id' => (int)($it['project_id'] ?? 0),
                    'name' => (string)($it['name'] ?? ''),
                    'kpi_names' => isset($it['kpi_names']) && is_array($it['kpi_names']) ? $it['kpi_names'] : [],
                ];
            }
            $grandProjects += $cnt;
            $grandBudget += $sumB;

            $rows[] = [
                'row_kind' => 'data',
                'section_group' => $sg,
                'consistency_label' => $lab,
                'project_count' => $cnt,
                'budget_baht' => $sumB,
                'sort_order' => $sortOrder++,
                'include_project_items' => $displayItems,
            ];
        }
    }

    $rows[] = [
        'row_kind' => 'total',
        'section_group' => null,
        'consistency_label' => 'รวม',
        'project_count' => $grandProjects,
        'budget_baht' => $grandBudget,
        'sort_order' => $sortOrder++,
        'include_project_items' => [],
    ];

    return $rows;
}

/**
 * ชื่อคอลัมน์ "คำอธิบายเพิ่มเติม" ใน project_activity_budget
 * ระบบเดิมใช้ detail บางที่ใช้ budget_item
 */
function pmProjectActivityBudgetDescColumn($db_Project): string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = '';
    if (!$db_Project) {
        return $cache;
    }
    if (function_exists('mysqli_report')) {
        @mysqli_report(MYSQLI_REPORT_OFF);
    }
    $q = @mysqli_query($db_Project, 'SHOW COLUMNS FROM project_activity_budget');
    if ($q) {
        $fields = [];
        while ($r = mysqli_fetch_assoc($q)) {
            if (!empty($r['Field'])) {
                $fields[(string) $r['Field']] = true;
            }
        }
        if (!empty($fields['detail'])) {
            $cache = 'detail';
        } elseif (!empty($fields['budget_item'])) {
            $cache = 'budget_item';
        }
    }

    return $cache;
}

/** อ่านค่าคำอธิบายเพิ่มเติมจากแถวงบ */
function pmProjectActivityBudgetDescValue(array $row, string $descCol = ''): string
{
    if ($descCol === '') {
        $descCol = 'detail';
        if (!array_key_exists('detail', $row) && array_key_exists('budget_item', $row)) {
            $descCol = 'budget_item';
        }
    }
    if ($descCol === '' || !array_key_exists($descCol, $row)) {
        return '';
    }

    return trim((string) ($row[$descCol] ?? ''));
}

/** มีคอลัมน์งบประมาณกิจกรรม (project_activity.budget) หรือไม่ */
function pmProjectActivityHasBudgetColumn($db_Project): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = false;
    if (!$db_Project) {
        return $cache;
    }
    if (function_exists('mysqli_report')) {
        @mysqli_report(MYSQLI_REPORT_OFF);
    }
    $q = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_activity LIKE 'budget'");
    $cache = ($q && mysqli_num_rows($q) > 0);

    return $cache;
}

/** งบประมาณกิจกรรม (project_activity.budget) */
function pmGetProjectActivityBudgetLimit($db_Project, int $activity_id): float
{
    if (!$db_Project || $activity_id <= 0 || !pmProjectActivityHasBudgetColumn($db_Project)) {
        return 0.0;
    }
    $q = @mysqli_query(
        $db_Project,
        "SELECT budget FROM project_activity WHERE id='" . (int) $activity_id . "' AND status!='99' LIMIT 1"
    );
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) ($row['budget'] ?? 0);
    }

    return 0.0;
}

/** ผลรวมรายการค่าใช้จ่ายย่อยของกิจกรรม */
function pmSumProjectActivityBudgetItems($db_Project, int $activity_id, int $exclude_item_id = 0): float
{
    if (!$db_Project || $activity_id <= 0) {
        return 0.0;
    }
    $sql = "SELECT IFNULL(SUM(CAST(total AS DECIMAL(14,4))), 0) AS s
            FROM project_activity_budget
            WHERE code_activity='" . (int) $activity_id . "' AND status!='99'";
    if ($exclude_item_id > 0) {
        $sql .= " AND id!='" . (int) $exclude_item_id . "'";
    }
    $q = @mysqli_query($db_Project, $sql);
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) ($row['s'] ?? 0);
    }

    return 0.0;
}

/** ตรวจผลรวมรายการค่าใช้จ่ายไม่เกินงบประมาณกิจกรรม */
function pmValidateActivityBudgetItemsAgainstLimit($db_Project, int $activity_id, float $add_total = 0.0, int $exclude_item_id = 0): ?string
{
    $limit = pmGetProjectActivityBudgetLimit($db_Project, $activity_id);
    if ($limit <= 0) {
        return null;
    }
    $sum = pmSumProjectActivityBudgetItems($db_Project, $activity_id, $exclude_item_id) + (float) $add_total;
    if ($sum <= ($limit + 0.0001)) {
        return null;
    }
    $actLabel = 'กิจกรรม #' . (int) $activity_id;
    $qAct = @mysqli_query(
        $db_Project,
        "SELECT activity_no, activity_name FROM project_activity WHERE id='" . (int) $activity_id . "' LIMIT 1"
    );
    if ($qAct && ($ra = mysqli_fetch_assoc($qAct))) {
        $no = trim((string) ($ra['activity_no'] ?? ''));
        $nm = trim((string) ($ra['activity_name'] ?? ''));
        $actLabel = ($no !== '' ? $no : (string) (int) $activity_id) . ($nm !== '' ? '. ' . $nm : '');
    }

    return 'รวมรายการค่าใช้จ่ายของกิจกรรม ' . $actLabel . ' ต้องไม่เกินงบประมาณกิจกรรม (งบประมาณ '
        . rtrim(rtrim(number_format($limit, 2, '.', ''), '0'), '.')
        . ' บาท, ปัจจุบันรวม '
        . rtrim(rtrim(number_format($sum, 2, '.', ''), '0'), '.')
        . ' บาท)';
}

/** ตรวจทุกกิจกรรมในโครงการ — คืนข้อความแจ้งเตือนหรือ null */
function pmValidateProjectActivityBudgetItemsAll($db_Project, int $project_id): ?string
{
    if (!$db_Project || $project_id <= 0 || !pmProjectActivityHasBudgetColumn($db_Project)) {
        return null;
    }
    $acts = function_exists('getProjectActivityList') ? getProjectActivityList($db_Project, $project_id) : [];
    $bad = [];
    foreach ($acts as $a) {
        $aid = (int) ($a['id'] ?? 0);
        if ($aid <= 0) {
            continue;
        }
        $msg = pmValidateActivityBudgetItemsAgainstLimit($db_Project, $aid, 0.0, 0);
        if ($msg !== null) {
            $bad[] = $msg;
        }
    }
    if (empty($bad)) {
        return null;
    }

    return implode(' | ', $bad);
}

/** ค่าน้ำหนักเท่ากับ 100 (ยอมรับทศนิยมเล็กน้อย) */
function pmWeightIsHundred($value, float $epsilon = 0.05): bool
{
    return abs(((float) $value) - 100.0) <= $epsilon;
}

/** ผลรวมค่าน้ำหนักตัวชี้วัดเป้าหมายโครงการ (project_kpi) */
function pmGetProjectKpiWeightSum($db_Project, int $project_id): float
{
    if (!$db_Project || $project_id <= 0) {
        return 0.0;
    }
    $q = @mysqli_query(
        $db_Project,
        "SELECT IFNULL(SUM(CAST(kpi_weight AS DECIMAL(12,4))), 0) AS s
         FROM project_kpi
         WHERE code_pre_project = '" . (int) $project_id . "' AND status != '99'"
    );
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) ($row['s'] ?? 0);
    }

    return 0.0;
}

/** ตรวจผลรวมตัวชี้วัด = 100 — คืนข้อความแจ้งเตือนหรือ null ถ้าผ่าน */
function pmValidateProjectKpiWeightTotal($db_Project, int $project_id, float $epsilon = 0.05): ?string
{
    $sum = pmGetProjectKpiWeightSum($db_Project, $project_id);
    if (pmWeightIsHundred($sum, $epsilon)) {
        return null;
    }

    return 'ค่าน้ำหนักตัวชี้วัดเป้าหมายโครงการรวมกันต้องเท่ากับ 100 (ปัจจุบันรวม ' . rtrim(rtrim(number_format($sum, 2, '.', ''), '0'), '.') . ')';
}

/** ผลรวมค่าน้ำหนักกิจกรรม (project_activity) */
function pmGetProjectActivityWeightSum($db_Project, int $project_id): float
{
    if (!$db_Project || $project_id <= 0) {
        return 0.0;
    }
    $q = @mysqli_query(
        $db_Project,
        "SELECT IFNULL(SUM(CAST(activity_weight AS DECIMAL(12,4))), 0) AS s
         FROM project_activity
         WHERE code_pre_project = '" . (int) $project_id . "' AND status != '99'"
    );
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        return (float) ($row['s'] ?? 0);
    }

    return 0.0;
}

/** ตรวจผลรวมกิจกรรม = 100 — คืนข้อความแจ้งเตือนหรือ null ถ้าผ่าน */
function pmValidateProjectActivitiesWeights($db_Project, int $project_id, float $epsilon = 0.05): ?string
{
    $sum = pmGetProjectActivityWeightSum($db_Project, $project_id);
    if (pmWeightIsHundred($sum, $epsilon)) {
        return null;
    }

    return 'ค่าน้ำหนักกิจกรรม/ขั้นตอนการดำเนินงานรวมกันต้องเท่ากับ 100 (ปัจจุบันรวม ' . rtrim(rtrim(number_format($sum, 2, '.', ''), '0'), '.') . ')';
}

/**
 * ผลรวมแผนการใช้จ่ายรายเดือนของรายการค่าใช้จ่ายย่อย
 * คืนค่า null ถ้ายังไม่มีแถวแผน
 */
function pmGetActivityBudgetItemPlanMonthSum($db_Project, int $activity_id, int $budget_item_id, int $project_id = 0): ?float
{
    if (!$db_Project || $activity_id <= 0 || $budget_item_id <= 0) {
        return null;
    }
    static $monthKeys = [
        'mon_10', 'mon_11', 'mon_12', 'mon_01', 'mon_02', 'mon_03',
        'mon_04', 'mon_05', 'mon_06', 'mon_07', 'mon_08', 'mon_09',
    ];
    static $schemaCache = null;
    if ($schemaCache === null) {
        $schemaCache = [
            'has_item_col' => false,
            'cols' => [],
        ];
        $chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_activity_budget_detail LIKE 'code_activity_budget'");
        $schemaCache['has_item_col'] = ($chk && mysqli_num_rows($chk) > 0);
        $colRes = @mysqli_query($db_Project, 'SHOW COLUMNS FROM project_activity_budget_detail');
        if ($colRes) {
            while ($c = mysqli_fetch_assoc($colRes)) {
                if (!empty($c['Field'])) {
                    $schemaCache['cols'][(string) $c['Field']] = true;
                }
            }
        }
    }
    $hasItemCol = !empty($schemaCache['has_item_col']);
    $detailCols = $schemaCache['cols'];

    $where = [];
    if (isset($detailCols['code_activity'])) {
        $where[] = "code_activity='" . (int) $activity_id . "'";
    }
    if ($project_id > 0 && isset($detailCols['code_pre_project'])) {
        $where[] = "code_pre_project='" . (int) $project_id . "'";
    }
    if ($hasItemCol) {
        $where[] = "code_activity_budget='" . (int) $budget_item_id . "'";
    }
    if (isset($detailCols['status'])) {
        $where[] = "status!='99'";
    }
    $sql = 'SELECT * FROM project_activity_budget_detail';
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY id DESC LIMIT 1';
    $rs = @mysqli_query($db_Project, $sql);
    $row = ($rs && mysqli_num_rows($rs) > 0) ? mysqli_fetch_assoc($rs) : null;
    if (!is_array($row)) {
        // โครงสร้างเก่า: แผนระดับกิจกรรม (ไม่มีแยกรายการย่อย)
        if (!$hasItemCol && function_exists('getProjectActivityBudgetDetail')) {
            $legacy = getProjectActivityBudgetDetail($db_Project, $activity_id, 0, true);
            if (!is_array($legacy)) {
                return null;
            }
            $row = $legacy;
        } else {
            return null;
        }
    }
    $sum = 0.0;
    foreach ($monthKeys as $mk) {
        $sum += (float) ($row[$mk] ?? 0);
    }

    return $sum;
}

/** ตรวจว่าทุกรายการค่าใช้จ่ายมีแผนการใช้จ่ายครบ (ยอดแผน = ยอดรายการ) — คืนข้อความหรือ null */
function pmValidateProjectBudgetSpendingPlans($db_Project, int $project_id): ?string
{
    if (!$db_Project || $project_id <= 0) {
        return null;
    }
    $items = function_exists('getProjectActivityBudgetALL')
        ? getProjectActivityBudgetALL($db_Project, $project_id)
        : [];
    if (empty($items)) {
        return null;
    }
    $epsilon = 0.05;
    static $hasItemCol = null;
    if ($hasItemCol === null) {
        $chk = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_activity_budget_detail LIKE 'code_activity_budget'");
        $hasItemCol = ($chk && mysqli_num_rows($chk) > 0);
    }

    $missing = 0;
    if ($hasItemCol) {
        foreach ($items as $it) {
            $itemId = (int) ($it['id'] ?? 0);
            $actId = (int) ($it['code_activity'] ?? 0);
            $itemTotal = (float) ($it['total'] ?? 0);
            if ($itemId <= 0 || $actId <= 0 || $itemTotal <= 0.0001) {
                continue;
            }
            $planSum = pmGetActivityBudgetItemPlanMonthSum($db_Project, $actId, $itemId, $project_id);
            if ($planSum === null || abs((float) $planSum - $itemTotal) > $epsilon) {
                $missing++;
            }
        }
    } else {
        // โครงสร้างเก่า: แผนระดับกิจกรรม ต้องเท่ากับยอดรวมทุกรายการในกิจกรรมนั้น
        $byAct = [];
        foreach ($items as $it) {
            $actId = (int) ($it['code_activity'] ?? 0);
            if ($actId <= 0) {
                continue;
            }
            if (!isset($byAct[$actId])) {
                $byAct[$actId] = 0.0;
            }
            $byAct[$actId] += (float) ($it['total'] ?? 0);
        }
        foreach ($byAct as $actId => $actItemsTotal) {
            if ($actItemsTotal <= 0.0001) {
                continue;
            }
            $planSum = pmGetActivityBudgetItemPlanMonthSum($db_Project, (int) $actId, 0, $project_id);
            // legacy: เรียก detail ระดับกิจกรรมโดยไม่ผูก item
            if ($planSum === null && function_exists('getProjectActivityBudgetDetail')) {
                $legacy = getProjectActivityBudgetDetail($db_Project, (int) $actId, 0, true);
                $planSum = 0.0;
                if (is_array($legacy)) {
                    foreach (['mon_10','mon_11','mon_12','mon_01','mon_02','mon_03','mon_04','mon_05','mon_06','mon_07','mon_08','mon_09'] as $mk) {
                        $planSum += (float) ($legacy[$mk] ?? 0);
                    }
                } else {
                    $planSum = null;
                }
            }
            if ($planSum === null || abs((float) $planSum - $actItemsTotal) > $epsilon) {
                // นับตามจำนวนรายการที่ยอด > 0 ในกิจกรรมนั้น
                foreach ($items as $it) {
                    if ((int) ($it['code_activity'] ?? 0) !== (int) $actId) {
                        continue;
                    }
                    if ((float) ($it['total'] ?? 0) > 0.0001) {
                        $missing++;
                    }
                }
            }
        }
    }
    if ($missing <= 0) {
        return null;
    }

    return 'กรุณากรอกแผนการใช้จ่ายให้ครบทุกรายการค่าใช้จ่าย โดยยอดแผนรายเดือนต้องเท่ากับยอดรายการ (ยังไม่ครบ ' . (int) $missing . ' รายการ) — ไม่สามารถส่งร่างโครงการได้จนกว่าจะกรอกครบ';
}

/** ตรวจส่วนที่ 6: ติดตามประเมินผล + ประเมินความเสี่ยง ต้องกรอกครบก่อนส่งร่าง */
function pmValidateProjectSection6ForDraft($db_Project, int $project_id): ?string
{
    if (!$db_Project || $project_id <= 0) {
        return null;
    }
    $want = ['evaluate_type' => false, 'evaluate_tool' => false, 'risk_action' => false, 'risk_stop' => false];
    $qcol = @mysqli_query($db_Project, 'SHOW COLUMNS FROM pre_project');
    if ($qcol) {
        while ($c = mysqli_fetch_assoc($qcol)) {
            $f = (string) ($c['Field'] ?? '');
            if (isset($want[$f])) {
                $want[$f] = true;
            }
        }
    }
    $cols = [];
    foreach ($want as $col => $has) {
        if ($has) {
            $cols[] = '`' . $col . '`';
        }
    }
    $row = [];
    if (!empty($cols)) {
        $q = @mysqli_query(
            $db_Project,
            'SELECT ' . implode(',', $cols) . " FROM pre_project WHERE id='" . (int) $project_id . "' LIMIT 1"
        );
        $row = ($q ? mysqli_fetch_assoc($q) : null) ?: [];
    }
    $txt = static function ($v): string {
        return trim(strip_tags((string) $v));
    };
    $evalOk = $txt($row['evaluate_type'] ?? '') !== '' && $txt($row['evaluate_tool'] ?? '') !== '';
    $riskOk = $txt($row['risk_action'] ?? '') !== '' && $txt($row['risk_stop'] ?? '') !== '';
    if ($evalOk && $riskOk) {
        return null;
    }
    $parts = [];
    if (!$evalOk) {
        $parts[] = 'การติดตามประเมินผล (วิธีการประเมินผล และเครื่องมือ)';
    }
    if (!$riskOk) {
        $parts[] = 'การประเมินความเสี่ยง (ความเสี่ยงที่คาดว่าจะเกิดขึ้น และวิธีการป้องกันความเสี่ยง)';
    }
    return 'กรุณากรอก' . implode(' และ ', $parts) . ' ให้ครบก่อนส่งร่างโครงการ';
}

/** ตรวจกฎค่าน้ำหนักทั้งตัวชี้วัดและกิจกรรม */
function pmValidateProjectWeightRules($db_Project, int $project_id, float $epsilon = 0.05): ?string
{
    $kpiMsg = pmValidateProjectKpiWeightTotal($db_Project, $project_id, $epsilon);
    if ($kpiMsg !== null) {
        return $kpiMsg;
    }

    $actWeightMsg = pmValidateProjectActivitiesWeights($db_Project, $project_id, $epsilon);
    if ($actWeightMsg !== null) {
        return $actWeightMsg;
    }

    $budgetItemsMsg = pmValidateProjectActivityBudgetItemsAll($db_Project, $project_id);
    if ($budgetItemsMsg !== null) {
        return $budgetItemsMsg;
    }

    $budgetPlanMsg = pmValidateProjectBudgetSpendingPlans($db_Project, $project_id);
    if ($budgetPlanMsg !== null) {
        return $budgetPlanMsg;
    }

    return pmValidateProjectSection6ForDraft($db_Project, $project_id);
}

/** ตารางจำว่าผู้ใช้คลิกอ่านเรื่องในกล่องอนุมัติแล้ว */
if (!function_exists('pm_ensure_approve_inbox_read_table')) {
	function pm_ensure_approve_inbox_read_table($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		@mysqli_query(
			$db_Project,
			"CREATE TABLE IF NOT EXISTS project_approve_inbox_read (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT,
				staff_id INT UNSIGNED NOT NULL,
				flow VARCHAR(16) NOT NULL DEFAULT '',
				ref_key VARCHAR(80) NOT NULL DEFAULT '',
				read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY uk_pair_staff_flow_ref (staff_id, flow, ref_key),
				KEY idx_pair_staff_flow (staff_id, flow)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}
}

if (!function_exists('pm_approve_inbox_mark_read')) {
	function pm_approve_inbox_mark_read($db_Project, int $staff_id, string $flow, string $ref_key): bool
	{
		$staff_id = (int)$staff_id;
		$flow = trim($flow);
		$ref_key = trim($ref_key);
		if (!$db_Project || $staff_id <= 0 || $flow === '' || $ref_key === '') {
			return false;
		}
		pm_ensure_approve_inbox_read_table($db_Project);
		$flowEsc = mysqli_real_escape_string($db_Project, substr($flow, 0, 16));
		$refEsc = mysqli_real_escape_string($db_Project, substr($ref_key, 0, 80));
		return (bool)@mysqli_query(
			$db_Project,
			"INSERT INTO project_approve_inbox_read (staff_id, flow, ref_key, read_at)
			 VALUES ('{$staff_id}', '{$flowEsc}', '{$refEsc}', NOW())
			 ON DUPLICATE KEY UPDATE read_at = NOW()"
		);
	}
}

if (!function_exists('pm_approve_inbox_clear_read')) {
	/** ล้างเครื่องหมายอ่าน เพื่อให้เรื่องที่ส่งกลับมาขึ้นเป็นยังไม่อ่านอีกครั้ง */
	function pm_approve_inbox_clear_read($db_Project, string $flow, string $ref_key): void
	{
		$flow = trim($flow);
		$ref_key = trim($ref_key);
		if (!$db_Project || $flow === '' || $ref_key === '') {
			return;
		}
		pm_ensure_approve_inbox_read_table($db_Project);
		$flowEsc = mysqli_real_escape_string($db_Project, substr($flow, 0, 16));
		$refEsc = mysqli_real_escape_string($db_Project, substr($ref_key, 0, 80));
		@mysqli_query(
			$db_Project,
			"DELETE FROM project_approve_inbox_read
			 WHERE flow='{$flowEsc}' AND ref_key='{$refEsc}'"
		);
	}
}

if (!function_exists('pm_approve_inbox_read_key_set')) {
	function pm_approve_inbox_read_key_set($db_Project, int $staff_id): array
	{
		$out = [];
		$staff_id = (int)$staff_id;
		if (!$db_Project || $staff_id <= 0) {
			return $out;
		}
		pm_ensure_approve_inbox_read_table($db_Project);
		$q = @mysqli_query(
			$db_Project,
			"SELECT flow, ref_key FROM project_approve_inbox_read WHERE staff_id='" . $staff_id . "'"
		);
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$k = trim((string)($r['flow'] ?? '')) . "\0" . trim((string)($r['ref_key'] ?? ''));
			if ($k !== "\0") {
				$out[$k] = true;
			}
		}
		return $out;
	}
}

if (!function_exists('pm_approve_inbox_is_read')) {
	function pm_approve_inbox_is_read(array $readSet, string $flow, string $ref_key): bool
	{
		return isset($readSet[$flow . "\0" . $ref_key]);
	}
}

/** ยังไม่อ่าน: คิวผู้ตรวจ หรือเอกสารส่งที่ถูกตีกลับให้แก้ไข */
if (!function_exists('pm_approve_inbox_row_unread')) {
	function pm_approve_inbox_row_unread(array $readSet, string $flow, string $ref_key, $is_reviewer, $is_submitter, string $status_group): bool
	{
		$isRev = ((string)$is_reviewer === '1' || $is_reviewer === true || $is_reviewer === 1);
		$isSub = ((string)$is_submitter === '1' || $is_submitter === true || $is_submitter === 1);
		$sg = trim($status_group);
		$needs = ($isRev && $sg === 'pending') || ($isSub && $sg === 'returned');
		if (!$needs) {
			return false;
		}
		return !pm_approve_inbox_is_read($readSet, $flow, $ref_key);
	}
}

/** ลำดับ JSON: ถึงคิวผู้ใช้หรือยัง (ยังไม่ได้กดผ่าน/ตีกลับ และลำดับก่อนหน้าผ่านแล้ว) */
if (!function_exists('pm_appr_json_chain_user_turn')) {
	function pm_appr_json_chain_user_turn($chainJson, array $decBySo, int $sid): bool
	{
		$sid = (int)$sid;
		if ($sid <= 0) {
			return false;
		}
		$arr = is_array($chainJson) ? $chainJson : json_decode((string)$chainJson, true);
		if (!is_array($arr) || empty($arr)) {
			return false;
		}
		$seq = [];
		$n = 0;
		foreach ($arr as $c) {
			if (!is_array($c)) {
				continue;
			}
			$n++;
			$so = (int)($c['sort_order'] ?? 0);
			if ($so <= 0) {
				$so = $n;
			}
			$uid = (int)($c['staff_id'] ?? 0);
			if ($uid > 0) {
				$seq[] = ['so' => $so, 'sid' => $uid];
			}
		}
		usort($seq, static function ($a, $b) {
			return ($a['so'] <=> $b['so']);
		});
		$prevOk = true;
		foreach ($seq as $it) {
			$dec = strtolower(trim((string)($decBySo[(int)$it['so']] ?? '')));
			if ((int)$it['sid'] === $sid) {
				if ($dec === 'approved' || $dec === 'rejected') {
					$prevOk = ($dec === 'approved');
					continue;
				}
				return $prevOk;
			}
			if ($dec !== 'approved') {
				$prevOk = false;
			}
		}
		return false;
	}
}

if (!function_exists('pm_staff_is_reviewer_or_approver_on_project')) {
	/** User ที่เป็นผู้ตรวจสอบ/ผู้อนุมัติของโครงการนี้ในคิวปัจจุบัน */
	function pm_staff_is_reviewer_or_approver_on_project($db_Project, int $projectId, int $staffId): bool
	{
		$projectId = (int)$projectId;
		$staffId = (int)$staffId;
		if (!$db_Project || $projectId <= 0 || $staffId <= 0) {
			return false;
		}
		$pr = null;
		$q = @mysqli_query(
			$db_Project,
			"SELECT operate_certifiers_json, operate_approvers_json, result_report_certifiers_json,
			        IFNULL(result_report_submitted_at,'') AS result_report_submitted_at,
			        IFNULL(result_report_completed_at,'') AS result_report_completed_at
			 FROM pre_project WHERE id='" . $projectId . "' LIMIT 1"
		);
		if ($q) {
			$pr = mysqli_fetch_assoc($q) ?: null;
		}
		if (is_array($pr) && function_exists('pm_appr_json_chain_user_turn') && function_exists('pm_appr_dec_map_by_so')) {
			$certDec = pm_appr_dec_map_by_so(
				$db_Project,
				"SELECT sort_order, decision FROM project_operate_certifier_decision WHERE project_id='" . $projectId . "'"
			);
			if (pm_appr_json_chain_user_turn($pr['operate_certifiers_json'] ?? '', $certDec, $staffId)) {
				return true;
			}
			$apprJson = trim((string)($pr['operate_approvers_json'] ?? ''));
			if ($apprJson !== '') {
				$apprDec = pm_appr_dec_map_by_so(
					$db_Project,
					"SELECT sort_order, decision FROM project_operate_approver_decision WHERE project_id='" . $projectId . "'"
				);
				if (pm_appr_json_chain_user_turn($apprJson, $apprDec, $staffId)) {
					return true;
				}
			}
			if (trim((string)($pr['result_report_submitted_at'] ?? '')) !== ''
				&& trim((string)($pr['result_report_completed_at'] ?? '')) === '') {
				$repDec = pm_appr_dec_map_by_so(
					$db_Project,
					"SELECT sort_order, decision FROM project_result_report_certifier_decision WHERE project_id='" . $projectId . "'"
				);
				if (pm_appr_json_chain_user_turn($pr['result_report_certifiers_json'] ?? '', $repDec, $staffId)) {
					return true;
				}
			}
		}
		$qInc = @mysqli_query(
			$db_Project,
			"SELECT c.staff_id
			 FROM project_include_request_item i
			 JOIN project_include_request r ON r.id = i.request_id
			 JOIN project_include_request_certifier c ON c.request_id = r.id
			 WHERE i.project_id='" . $projectId . "'
			   AND IFNULL(r.status,'') IN ('pending','submitted','review')
			   AND c.staff_id='" . $staffId . "'
			 LIMIT 1"
		);
		if ($qInc && mysqli_fetch_assoc($qInc)) {
			return true;
		}
		if (function_exists('pm_ensure_change_request_schema')) {
			pm_ensure_change_request_schema($db_Project);
		}
		$qChg = @mysqli_query(
			$db_Project,
			"SELECT certifiers_json, approvers_json, req_status
			 FROM project_change_request
			 WHERE project_id='" . $projectId . "'
			   AND status='1'
			   AND req_status IN ('pending','waiting_plan','pending_approve')
			 ORDER BY id DESC LIMIT 1"
		);
		$cr = $qChg ? mysqli_fetch_assoc($qChg) : null;
		if (is_array($cr) && function_exists('pm_appr_json_chain_user_turn') && function_exists('pm_appr_dec_map_by_so')) {
			$rid = 0;
			$qRid = @mysqli_query(
				$db_Project,
				"SELECT id FROM project_change_request
				 WHERE project_id='" . $projectId . "' AND status='1'
				   AND req_status IN ('pending','waiting_plan','pending_approve')
				 ORDER BY id DESC LIMIT 1"
			);
			$rrid = $qRid ? mysqli_fetch_assoc($qRid) : null;
			$rid = (int)($rrid['id'] ?? 0);
			if ($rid > 0) {
				$cDec = pm_appr_dec_map_by_so(
					$db_Project,
					"SELECT sort_order, decision FROM project_change_certifier_decision WHERE request_id='" . $rid . "'"
				);
				if (pm_appr_json_chain_user_turn($cr['certifiers_json'] ?? '', $cDec, $staffId)) {
					return true;
				}
				if (trim((string)($cr['req_status'] ?? '')) === 'pending_approve') {
					$aDec = pm_appr_dec_map_by_so(
						$db_Project,
						"SELECT sort_order, decision FROM project_change_approver_decision WHERE request_id='" . $rid . "'"
					);
					if (pm_appr_json_chain_user_turn($cr['approvers_json'] ?? '', $aDec, $staffId)) {
						return true;
					}
				}
			}
		}
		return false;
	}
}

if (!function_exists('pm_appr_json_chain_user_recheck_after_reply')) {
	/** หลังผู้ยื่นส่งกลับ: คิวของผู้รับรองที่เคยตีกลับ และลำดับก่อนหน้าผ่านแล้ว */
	function pm_appr_json_chain_user_recheck_after_reply($chainJson, array $decBySo, int $sid): bool
	{
		$sid = (int)$sid;
		if ($sid <= 0) {
			return false;
		}
		$arr = is_array($chainJson) ? $chainJson : json_decode((string)$chainJson, true);
		if (!is_array($arr) || empty($arr)) {
			return false;
		}
		$seq = [];
		$n = 0;
		foreach ($arr as $c) {
			if (!is_array($c)) {
				continue;
			}
			$n++;
			$so = (int)($c['sort_order'] ?? 0);
			if ($so <= 0) {
				$so = $n;
			}
			$uid = (int)($c['staff_id'] ?? 0);
			if ($uid > 0) {
				$seq[] = ['so' => $so, 'sid' => $uid];
			}
		}
		usort($seq, static function ($a, $b) {
			return ($a['so'] <=> $b['so']);
		});
		$prevOk = true;
		foreach ($seq as $it) {
			$dec = strtolower(trim((string)($decBySo[(int)$it['so']] ?? '')));
			if ((int)$it['sid'] === $sid) {
				return ($dec === 'rejected' && $prevOk);
			}
			if ($dec !== 'approved') {
				$prevOk = false;
			}
		}
		return false;
	}
}

if (!function_exists('pm_appr_dec_map_by_so')) {
	function pm_appr_dec_map_by_so($db_Project, string $sql): array
	{
		$map = [];
		if (!$db_Project || $sql === '') {
			return $map;
		}
		$q = @mysqli_query($db_Project, $sql);
		while ($q && ($r = mysqli_fetch_assoc($q))) {
			$so = (int)($r['sort_order'] ?? 0);
			if ($so > 0) {
				$map[$so] = (string)($r['decision'] ?? '');
			}
		}
		return $map;
	}
}

/**
 * จำนวนเรื่องที่ยังรอพิจารณา/อนุมัติในแต่ละเมนู (ไม่นับตามการคลิกอ่าน)
 * @return array{include:int,operate:int,report:int,change:int}
 */
if (!function_exists('pm_approve_menu_badge_counts')) {
	function pm_approve_menu_badge_counts($db_Project, int $staff_id, bool $isPlanAdmin = false): array
	{
		$counts = ['include' => 0, 'operate' => 0, 'report' => 0, 'change' => 0];
		$staff_id = (int)$staff_id;
		if (!$db_Project || $staff_id <= 0) {
			return $counts;
		}
		if (function_exists('ensureIncludeRequestTables')) {
			ensureIncludeRequestTables($db_Project);
		}
		if (function_exists('ensureProjectExtraSchema')) {
			ensureProjectExtraSchema($db_Project);
		}
		if (function_exists('pm_ensure_midyear_schema')) {
			pm_ensure_midyear_schema($db_Project);
		}
		if (function_exists('ensureProjectResultReportSchema')) {
			ensureProjectResultReportSchema($db_Project);
		}

		$incSeen = [];
		$prevByReq = [];
		$qInc = @mysqli_query(
			$db_Project,
			"SELECT c.request_id, c.sort_order
			 FROM project_include_request_certifier c
			 JOIN project_include_request ir ON ir.id = c.request_id
			 LEFT JOIN project_include_request_certifier_decision d
			   ON d.request_id = c.request_id AND d.sort_order = c.sort_order
			 WHERE c.staff_id='" . $staff_id . "'
			   AND (d.id IS NULL OR TRIM(IFNULL(d.decision,'')) = '')
			   AND IFNULL(ir.status,'') NOT IN ('cancelled')"
		);
		$incNeedPrev = [];
		while ($qInc && ($r = mysqli_fetch_assoc($qInc))) {
			$rid = (int)($r['request_id'] ?? 0);
			$so = (int)($r['sort_order'] ?? 0);
			if ($rid > 0 && $so > 0) {
				$incNeedPrev[$rid] = $so;
			}
		}
		if (!empty($incNeedPrev)) {
			$idIn = implode(',', array_map('intval', array_keys($incNeedPrev)));
			$qPrev = @mysqli_query(
				$db_Project,
				"SELECT request_id, sort_order, decision
				 FROM project_include_request_certifier_decision
				 WHERE request_id IN (" . $idIn . ")"
			);
			while ($qPrev && ($pr = mysqli_fetch_assoc($qPrev))) {
				$rid = (int)($pr['request_id'] ?? 0);
				$so = (int)($pr['sort_order'] ?? 0);
				if ($rid > 0 && $so > 0) {
					$prevByReq[$rid][$so] = (string)($pr['decision'] ?? '');
				}
			}
			foreach ($incNeedPrev as $rid => $mySo) {
				$ok = true;
				for ($p = 1; $p < $mySo; $p++) {
					if (strtolower(trim((string)($prevByReq[$rid][$p] ?? ''))) !== 'approved') {
						$ok = false;
						break;
					}
				}
				if ($ok && empty($incSeen[$rid])) {
					$incSeen[$rid] = true;
					$counts['include']++;
				}
			}
		}
		// หลัง Admin หน่วยงานส่งกลับ: ผู้รับรองที่เคยตีกลับต้องเห็นตัวเลขแจ้งเตือนอีกครั้ง
		$incRecheckNeed = [];
		$qIncRecheck = @mysqli_query(
			$db_Project,
			"SELECT c.request_id, c.sort_order
			 FROM project_include_request_certifier c
			 JOIN project_include_request ir ON ir.id = c.request_id
			 JOIN project_include_request_certifier_decision d
			   ON d.request_id = c.request_id AND d.sort_order = c.sort_order
			 WHERE c.staff_id='" . $staff_id . "'
			   AND LOWER(TRIM(IFNULL(d.decision,''))) = 'rejected'
			   AND IFNULL(ir.status,'') = 'rejected'
			   AND (
					TRIM(IFNULL(ir.admin_fix_sent_at,'')) <> ''
					OR TRIM(IFNULL(ir.submitter_reply_note,'')) <> ''
			   )"
		);
		while ($qIncRecheck && ($r = mysqli_fetch_assoc($qIncRecheck))) {
			$rid = (int)($r['request_id'] ?? 0);
			$so = (int)($r['sort_order'] ?? 0);
			if ($rid > 0 && $so > 0) {
				$incRecheckNeed[$rid] = $so;
			}
		}
		if (!empty($incRecheckNeed)) {
			$needPrevIds = [];
			foreach ($incRecheckNeed as $rid => $mySo) {
				if ($mySo > 1 && !isset($prevByReq[$rid])) {
					$needPrevIds[$rid] = $rid;
				}
			}
			if (!empty($needPrevIds)) {
				$idIn2 = implode(',', array_map('intval', array_values($needPrevIds)));
				$qPrev2 = @mysqli_query(
					$db_Project,
					"SELECT request_id, sort_order, decision
					 FROM project_include_request_certifier_decision
					 WHERE request_id IN (" . $idIn2 . ")"
				);
				while ($qPrev2 && ($pr = mysqli_fetch_assoc($qPrev2))) {
					$rid = (int)($pr['request_id'] ?? 0);
					$so = (int)($pr['sort_order'] ?? 0);
					if ($rid > 0 && $so > 0) {
						$prevByReq[$rid][$so] = (string)($pr['decision'] ?? '');
					}
				}
			}
			foreach ($incRecheckNeed as $rid => $mySo) {
				$ok = true;
				for ($p = 1; $p < $mySo; $p++) {
					if (strtolower(trim((string)($prevByReq[$rid][$p] ?? ''))) !== 'approved') {
						$ok = false;
						break;
					}
				}
				if ($ok && empty($incSeen[$rid])) {
					$incSeen[$rid] = true;
					$counts['include']++;
				}
			}
		}
		if ($isPlanAdmin) {
			$qPu = @mysqli_query(
				$db_Project,
				"SELECT ir.id
				 FROM project_include_request ir
				 WHERE IFNULL(ir.status,'') NOT IN ('cancelled')
				   AND TRIM(IFNULL(ir.plan_unit_decision,'')) = ''
				   AND EXISTS (
						SELECT 1
						FROM project_include_request_item i
						JOIN pre_project p ON p.id = i.project_id AND p.status='1'
						WHERE i.request_id = ir.id
				   )
				   AND EXISTS (
						SELECT 1 FROM project_include_request_certifier c2 WHERE c2.request_id = ir.id
				   )
				   AND NOT EXISTS (
						SELECT 1 FROM project_include_request_certifier c
						LEFT JOIN project_include_request_certifier_decision d
						  ON d.request_id = c.request_id AND d.sort_order = c.sort_order
						WHERE c.request_id = ir.id
						  AND (d.id IS NULL OR TRIM(IFNULL(d.decision,'')) <> 'approved')
				   )"
			);
			while ($qPu && ($pr = mysqli_fetch_assoc($qPu))) {
				$rid = (int)($pr['id'] ?? 0);
				if ($rid > 0 && empty($incSeen[$rid])) {
					$incSeen[$rid] = true;
					$counts['include']++;
				}
			}
			$qFix = @mysqli_query(
				$db_Project,
				"SELECT id
				 FROM pre_project
				 WHERE status='1'
				   AND status_project='8'
				   AND IFNULL(notify_result,'')='บรรจุแผนและแก้ไข'
				   AND TRIM(IFNULL(user_fix_note,'')) <> ''"
			);
			while ($qFix && ($fr = mysqli_fetch_assoc($qFix))) {
				$pid = (int)($fr['id'] ?? 0);
				if ($pid > 0) {
					$counts['include']++;
				}
			}
		}
		$qIncRet = @mysqli_query(
			$db_Project,
			"SELECT ir.id
			 FROM project_include_request ir
			 WHERE ir.submitter_staff_id='" . $staff_id . "'
			   AND IFNULL(ir.status,'') = 'rejected'
			   AND IFNULL(ir.plan_unit_decision,'') IN ('', 'returned')
			   AND (
					IFNULL(ir.plan_unit_decision,'') = 'returned'
					OR EXISTS (
						SELECT 1 FROM project_include_request_item i
						WHERE i.request_id = ir.id
						  AND IFNULL(i.return_selected,0)=1
						  AND IFNULL(i.fix_status,'') IN ('','pending')
					)
			   )"
		);
		while ($qIncRet && ($ir = mysqli_fetch_assoc($qIncRet))) {
			$rid = (int)($ir['id'] ?? 0);
			if ($rid > 0 && empty($incSeen[$rid])) {
				$incSeen[$rid] = true;
				$counts['include']++;
			}
		}
		$qIncRetOwn = @mysqli_query(
			$db_Project,
			"SELECT DISTINCT i.request_id
			 FROM project_include_request_item i
			 JOIN project_include_request ir ON ir.id = i.request_id
			 JOIN pre_project p ON p.id = i.project_id AND p.status='1'
			 WHERE p.staff_insert='" . $staff_id . "'
			   AND IFNULL(ir.status,'') = 'rejected'
			   AND IFNULL(i.return_selected,0)=1
			   AND IFNULL(i.fix_status,'') IN ('','pending')"
		);
		while ($qIncRetOwn && ($ir = mysqli_fetch_assoc($qIncRetOwn))) {
			$rid = (int)($ir['request_id'] ?? 0);
			if ($rid > 0 && empty($incSeen[$rid])) {
				$incSeen[$rid] = true;
				$counts['include']++;
			}
		}

		if (function_exists('pm_reopen_operate_queue_after_submitter_reply')) {
			pm_reopen_operate_queue_after_submitter_reply($db_Project);
		}

		$opSeen = [];
		$qOp = @mysqli_query(
			$db_Project,
			"SELECT id, operate_certifiers_json, operate_approvers_json, status_project, notify_result, midyear_request, operate_submitter_reply_note
			 FROM pre_project
			 WHERE status='1'
			   AND (
					status_project='1'
					OR (status_project='8' AND IFNULL(notify_result,'')='บรรจุแผน')
					OR (status_project='8' AND IFNULL(midyear_request,0)=1)
			   )
			   AND TRIM(IFNULL(operate_certifiers_json,'')) <> ''"
		);
		while ($qOp && ($pr = mysqli_fetch_assoc($qOp))) {
			$pid = (int)($pr['id'] ?? 0);
			if ($pid <= 0) {
				continue;
			}
			$certDec = pm_appr_dec_map_by_so(
				$db_Project,
				"SELECT sort_order, decision FROM project_operate_certifier_decision WHERE project_id='" . $pid . "'"
			);
			if (pm_appr_json_chain_user_turn($pr['operate_certifiers_json'] ?? '', $certDec, $staff_id)) {
				$opSeen[$pid] = true;
				$counts['operate']++;
				continue;
			}
			$replyOp = trim((string)($pr['operate_submitter_reply_note'] ?? ''));
			$stOp = (string)($pr['status_project'] ?? '');
			if ($replyOp !== '' && $stOp === '8' && pm_appr_json_chain_user_recheck_after_reply($pr['operate_certifiers_json'] ?? '', $certDec, $staff_id)) {
				$opSeen[$pid] = true;
				$counts['operate']++;
				continue;
			}
			$apprJson = trim((string)($pr['operate_approvers_json'] ?? ''));
			if ($apprJson !== '') {
				$apprDec = pm_appr_dec_map_by_so(
					$db_Project,
					"SELECT sort_order, decision FROM project_operate_approver_decision WHERE project_id='" . $pid . "'"
				);
				if (pm_appr_json_chain_user_turn($apprJson, $apprDec, $staff_id)) {
					$opSeen[$pid] = true;
					$counts['operate']++;
					continue;
				}
				if ($replyOp !== '' && $stOp === '8' && function_exists('pm_appr_json_chain_user_recheck_after_reply')
					&& pm_appr_json_chain_user_recheck_after_reply($apprJson, $apprDec, $staff_id)) {
					$opSeen[$pid] = true;
					$counts['operate']++;
					continue;
				}
			}
			$certsDone = true;
			$cArr = json_decode((string)($pr['operate_certifiers_json'] ?? ''), true);
			if (is_array($cArr) && !empty($cArr)) {
				$n = 0;
				foreach ($cArr as $c) {
					if (!is_array($c)) {
						continue;
					}
					$n++;
					$so = (int)($c['sort_order'] ?? 0);
					if ($so <= 0) {
						$so = $n;
					}
					if (strtolower(trim((string)($certDec[$so] ?? ''))) !== 'approved') {
						$certsDone = false;
						break;
					}
				}
			} else {
				$certsDone = false;
			}
			if ($isPlanAdmin && $certsDone && $apprJson === '') {
				$opSeen[$pid] = true;
				$counts['operate']++;
			}
		}
		$qOpRet = @mysqli_query(
			$db_Project,
			"SELECT p.id
			 FROM pre_project p
			 WHERE p.status='1'
			   AND p.staff_insert='" . $staff_id . "'
			   AND p.status_project='8'
			   AND TRIM(IFNULL(p.operate_certifiers_json,'')) <> ''
			   AND (
					IFNULL(p.notify_result,'')='บรรจุแผน'
					OR IFNULL(p.midyear_request,0)=1
					OR EXISTS (
						SELECT 1 FROM project_operate_certifier_decision d
						WHERE d.project_id = p.id AND d.decision='rejected'
					)
					OR EXISTS (
						SELECT 1 FROM project_operate_approver_decision d2
						WHERE d2.project_id = p.id AND d2.decision='rejected'
					)
			   )"
		);
		while ($qOpRet && ($or = mysqli_fetch_assoc($qOpRet))) {
			$pidO = (int)($or['id'] ?? 0);
			if ($pidO > 0 && empty($opSeen[$pidO])) {
				$opSeen[$pidO] = true;
				$counts['operate']++;
			}
		}

		$qRep = @mysqli_query(
			$db_Project,
			"SELECT id, result_report_certifiers_json, result_report_completed_at
			 FROM pre_project
			 WHERE status='1'
			   AND TRIM(IFNULL(result_report_submitted_at,'')) <> ''
			   AND TRIM(IFNULL(result_report_certifiers_json,'')) <> ''
			   AND TRIM(IFNULL(result_report_completed_at,'')) = ''"
		);
		while ($qRep && ($pr = mysqli_fetch_assoc($qRep))) {
			$pid = (int)($pr['id'] ?? 0);
			if ($pid <= 0) {
				continue;
			}
			$dec = pm_appr_dec_map_by_so(
				$db_Project,
				"SELECT sort_order, decision FROM project_result_report_certifier_decision WHERE project_id='" . $pid . "'"
			);
			if (pm_appr_json_chain_user_turn($pr['result_report_certifiers_json'] ?? '', $dec, $staff_id)) {
				$counts['report']++;
			}
		}
		$repRetSeen = [];
		if ($isPlanAdmin) {
			$qRepPlan = @mysqli_query(
				$db_Project,
				"SELECT p.id
				 FROM pre_project p
				 WHERE p.status='1'
				   AND TRIM(IFNULL(p.result_report_submitted_at,'')) <> ''
				   AND TRIM(IFNULL(p.result_report_completed_at,'')) <> ''
				   AND TRIM(IFNULL(p.result_report_plan_closed_at,'')) = ''"
			);
			while ($qRepPlan && ($prp = mysqli_fetch_assoc($qRepPlan))) {
				$pidP = (int)($prp['id'] ?? 0);
				if ($pidP > 0 && empty($repRetSeen[$pidP])) {
					$repRetSeen[$pidP] = true;
					$counts['report']++;
				}
			}
		}
		$qRepRet = @mysqli_query(
			$db_Project,
			"SELECT p.id
			 FROM pre_project p
			 WHERE p.status='1'
			   AND p.staff_insert='" . $staff_id . "'
			   AND TRIM(IFNULL(p.result_report_submitted_at,'')) <> ''
			   AND TRIM(IFNULL(p.result_report_completed_at,'')) = ''
			   AND EXISTS (
					SELECT 1 FROM project_result_report_certifier_decision d
					WHERE d.project_id = p.id AND d.decision='rejected'
			   )"
		);
		$repRetSeen = [];
		while ($qRepRet && ($rr = mysqli_fetch_assoc($qRepRet))) {
			$pidR = (int)($rr['id'] ?? 0);
			if ($pidR > 0 && empty($repRetSeen[$pidR])) {
				$repRetSeen[$pidR] = true;
				$counts['report']++;
			}
		}

		if (function_exists('pm_ensure_change_request_schema')) {
			pm_ensure_change_request_schema($db_Project);
		}
		$qChg = @mysqli_query(
			$db_Project,
			"SELECT id, certifiers_json, approvers_json, req_status, applied_at
			 FROM project_change_request
			 WHERE status='1'
			   AND req_status IN ('pending','waiting_plan','pending_approve','approved')"
		);
		while ($qChg && ($cr = mysqli_fetch_assoc($qChg))) {
			$rid = (int)($cr['id'] ?? 0);
			if ($rid <= 0) {
				continue;
			}
			$st = trim((string)($cr['req_status'] ?? ''));
			$appliedAt = trim((string)($cr['applied_at'] ?? ''));
			if ($st === 'approved' && $appliedAt !== '') {
				continue;
			}
			$inCert = false;
			$cArr = json_decode((string)($cr['certifiers_json'] ?? ''), true);
			if (is_array($cArr)) {
				foreach ($cArr as $c) {
					if (is_array($c) && (int)($c['staff_id'] ?? 0) === $staff_id) {
						$inCert = true;
						break;
					}
				}
			}
			$inAppr = false;
			$aArr = json_decode((string)($cr['approvers_json'] ?? ''), true);
			if (is_array($aArr)) {
				foreach ($aArr as $c) {
					if (is_array($c) && (int)($c['staff_id'] ?? 0) === $staff_id) {
						$inAppr = true;
						break;
					}
				}
			}
			if ($inCert && $st === 'pending') {
				$counts['change']++;
				continue;
			}
			if ($inAppr && $st === 'pending_approve') {
				$counts['change']++;
				continue;
			}
			if ($isPlanAdmin && ($st === 'waiting_plan' || ($st === 'approved' && $appliedAt === ''))) {
				$counts['change']++;
			}
		}
		$qChgRet = @mysqli_query(
			$db_Project,
			"SELECT id
			 FROM project_change_request
			 WHERE status='1'
			   AND staff_insert='" . $staff_id . "'
			   AND req_status='rejected'"
		);
		while ($qChgRet && ($cr = mysqli_fetch_assoc($qChgRet))) {
			$rid = (int)($cr['id'] ?? 0);
			if ($rid > 0) {
				$counts['change']++;
			}
		}

		if (function_exists('pm_ensure_cancel_request_schema')) {
			pm_ensure_cancel_request_schema($db_Project);
		}
		if ($isPlanAdmin) {
			$qCan = @mysqli_query(
				$db_Project,
				"SELECT id FROM project_cancel_request WHERE status='1' AND req_status='pending'"
			);
			while ($qCan && ($cr = mysqli_fetch_assoc($qCan))) {
				$rid = (int)($cr['id'] ?? 0);
				if ($rid > 0) {
					$counts['change']++;
				}
			}
		}

		return $counts;
	}
}

if (!function_exists('pm_ensure_system_feedback_schema')) {
	function pm_ensure_system_feedback_schema($db_Project): void
	{
		if (!$db_Project) {
			return;
		}
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_system_feedback (
			id INT AUTO_INCREMENT PRIMARY KEY,
			staff_id INT NOT NULL DEFAULT 0,
			staff_name VARCHAR(255) NULL,
			role_type VARCHAR(20) NULL,
			page_name VARCHAR(80) NULL,
			rating TINYINT NOT NULL DEFAULT 0,
			comment TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			KEY idx_staff (staff_id),
			KEY idx_created (created_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}
}

if (!function_exists('pm_save_system_feedback')) {
	function pm_save_system_feedback($db_Project, int $staffId, string $staffName, string $roleType, string $pageName, int $rating, string $comment): array
	{
		if (!$db_Project) {
			return ['ok' => 0, 'msg' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'];
		}
		if ($rating < 1 || $rating > 5) {
			return ['ok' => 0, 'msg' => 'กรุณาเลือกคะแนนความพึงพอใจ'];
		}
		pm_ensure_system_feedback_schema($db_Project);
		$staffName = trim($staffName);
		$roleType = $roleType === 'plan' ? 'plan' : 'user';
		$pageName = trim($pageName);
		$comment = trim($comment);
		$stmt = mysqli_prepare(
			$db_Project,
			"INSERT INTO project_system_feedback (staff_id, staff_name, role_type, page_name, rating, comment)
			 VALUES (?, ?, ?, ?, ?, ?)"
		);
		if (!$stmt) {
			return ['ok' => 0, 'msg' => 'ไม่สามารถบันทึกได้'];
		}
		mysqli_stmt_bind_param($stmt, 'isssis', $staffId, $staffName, $roleType, $pageName, $rating, $comment);
		$ok = mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
		if (!$ok) {
			return ['ok' => 0, 'msg' => 'บันทึกไม่สำเร็จ'];
		}
		return ['ok' => 1, 'msg' => 'บันทึกข้อเสนอแนะเรียบร้อยแล้ว ขอบคุณค่ะ'];
	}
}

if (!function_exists('pm_load_system_feedback_report')) {
	function pm_load_system_feedback_report($db_Project, $rowLimit = 50): array
	{
		$out = [
			'total' => 0,
			'avg' => 0.0,
			'stars' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
			'cnt_user' => 0,
			'cnt_plan' => 0,
			'cnt_comment' => 0,
			'rows' => [],
		];
		if (!$db_Project) {
			return $out;
		}
		if (function_exists('pm_ensure_system_feedback_schema')) {
			pm_ensure_system_feedback_schema($db_Project);
		}
		$q = @mysqli_query(
			$db_Project,
			"SELECT
				COUNT(*) AS total,
				IFNULL(AVG(rating),0) AS avg_rating,
				SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) AS s1,
				SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) AS s2,
				SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) AS s3,
				SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) AS s4,
				SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) AS s5,
				SUM(CASE WHEN role_type='plan' THEN 1 ELSE 0 END) AS cnt_plan,
				SUM(CASE WHEN role_type='user' THEN 1 ELSE 0 END) AS cnt_user,
				SUM(CASE WHEN TRIM(IFNULL(comment,''))<>'' THEN 1 ELSE 0 END) AS cnt_comment
			 FROM project_system_feedback"
		);
		$row = $q ? mysqli_fetch_assoc($q) : null;
		if (is_array($row)) {
			$out['total'] = (int)($row['total'] ?? 0);
			$out['avg'] = round((float)($row['avg_rating'] ?? 0), 2);
			$out['stars'][1] = (int)($row['s1'] ?? 0);
			$out['stars'][2] = (int)($row['s2'] ?? 0);
			$out['stars'][3] = (int)($row['s3'] ?? 0);
			$out['stars'][4] = (int)($row['s4'] ?? 0);
			$out['stars'][5] = (int)($row['s5'] ?? 0);
			$out['cnt_plan'] = (int)($row['cnt_plan'] ?? 0);
			$out['cnt_user'] = (int)($row['cnt_user'] ?? 0);
			$out['cnt_comment'] = (int)($row['cnt_comment'] ?? 0);
		}
		$limitSql = ((int)$rowLimit > 0) ? (' LIMIT ' . (int)$rowLimit) : '';
		$ql = @mysqli_query(
			$db_Project,
			"SELECT id, staff_id, staff_name, role_type, page_name, rating, comment, created_at
			 FROM project_system_feedback
			 ORDER BY created_at DESC, id DESC
			 {$limitSql}"
		);
		while ($ql && ($r = mysqli_fetch_assoc($ql))) {
			$out['rows'][] = $r;
		}
		return $out;
	}
}
