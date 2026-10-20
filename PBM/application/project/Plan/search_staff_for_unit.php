<?php
// ค้นหา staff จาก db_person โดย "กรองรายชื่อที่มีอยู่แล้ว" ใน set_project_personnel ของหน่วยงานที่ระบุ
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
$limit = 20;

if (!$db_person || !$db_Project) {
	echo json_encode([], JSON_UNESCAPED_UNICODE);
	exit;
}

try {
	ensureSetProjectPersonnelTable($db_Project);
} catch (Throwable $e) {
	@error_log('search_staff_for_unit ensure: ' . $e->getMessage());
}

// รายชื่อที่มีอยู่แล้วในหน่วยงานนี้ (ใช้ staff_id เป็นหลัก และ fallback ด้วย name)
$existingStaffIds = [];
$existingNames = [];
if ($unit_id > 0) {
	try {
		$uidEsc = (int)$unit_id;
		$res = mysqli_query($db_Project, "SELECT staff_id, name FROM set_project_personnel WHERE status != '99' AND code_unit = '$uidEsc'");
		if ($res) {
			while ($r = mysqli_fetch_assoc($res)) {
				$sid = (int)($r['staff_id'] ?? 0);
				if ($sid > 0) $existingStaffIds[$sid] = true;
				$n = trim((string)($r['name'] ?? ''));
				if ($n !== '') $existingNames[$n] = true;
			}
		}
	} catch (Throwable $e) {
		@error_log('search_staff_for_unit existing: ' . $e->getMessage());
	}
}

// เตรียม query staff
if ($q === '') {
	$stmt = mysqli_prepare(
		$db_person,
		"SELECT id, code, CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
		 FROM staff
		 WHERE work_status IN ('0','1')
		 ORDER BY fname ASC, lname ASC, code ASC
		 LIMIT $limit"
	);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
} else {
	$like = '%' . $q . '%';
	$stmt = mysqli_prepare(
		$db_person,
		"SELECT id, code, CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
		 FROM staff
		 WHERE work_status IN ('0','1')
		   AND (
		        code LIKE ?
		        OR fname LIKE ?
		        OR lname LIKE ?
		        OR CONCAT(fname,' ',lname) LIKE ?
		   )
		 ORDER BY fname ASC, lname ASC, code ASC
		 LIMIT $limit"
	);
	if (!$stmt) {
		echo json_encode([], JSON_UNESCAPED_UNICODE);
		exit;
	}
	mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
}

$rows = [];
if ($res) {
	while ($r = mysqli_fetch_assoc($res)) {
		$staffId = (int)($r['id'] ?? 0);
		$code = (string)($r['code'] ?? '');
		$full = trim((string)($r['full_name'] ?? ''));
		if ($code === '' || $full === '') continue;

		// ไม่แสดงรายชื่อที่มีอยู่แล้วในหน่วยงาน (staff_id เป็นหลัก)
		if (($staffId > 0 && isset($existingStaffIds[$staffId])) || isset($existingNames[$full])) {
			continue;
		}
		$rows[] = [
			'code' => $code,
			'text' => $full,
		];
	}
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE);

