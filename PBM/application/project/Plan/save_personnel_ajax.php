<?php
// เพิ่มรายชื่อบุคลากรเข้า set_project_personnel ให้กับหน่วยงานที่เลือก (AJAX JSON only)
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');

header('Content-Type: application/json; charset=utf-8');

function json_fail(string $message, array $extra = []): void
{
	echo json_encode(array_merge(['ok' => false, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
	exit;
}
function json_ok(array $extra = []): void
{
	echo json_encode(array_merge(['ok' => true], $extra), JSON_UNESCAPED_UNICODE);
	exit;
}

$debug = (isset($_GET['debug']) && (string)$_GET['debug'] === '1') || (isset($_POST['debug']) && (string)$_POST['debug'] === '1');

try {
	global $db_Project, $db_person;
	if (!$db_Project || !$db_person) {
		json_fail('เชื่อมต่อฐานข้อมูลไม่สำเร็จ');
	}

	ensureSetProjectPersonnelTable($db_Project);

	$code_unit = isset($_POST['code_unit']) ? (int)$_POST['code_unit'] : 0;
	$code_department = isset($_POST['code_department']) ? (int)$_POST['code_department'] : 0;
	$code_job = isset($_POST['code_job']) ? (int)$_POST['code_job'] : 0;
	$status = '1';

	$staff_codes = $_POST['staff_codes'] ?? [];
	if (!is_array($staff_codes)) {
		$staff_codes = [$staff_codes];
	}
	$staff_codes = array_values(array_filter(array_map(function ($v) {
		return trim((string)$v);
	}, $staff_codes), function ($v) {
		return $v !== '';
	}));

	if ($code_unit <= 0) {
		json_fail('ไม่พบรหัสหน่วยงาน');
	}
	if (empty($staff_codes)) {
		json_fail('กรุณาเลือกรายชื่อบุคลากรอย่างน้อย 1 คน');
	}

	$vcd = $code_department > 0 ? "'" . (int)$code_department . "'" : 'NULL';
	$vcj = $code_job > 0 ? "'" . (int)$code_job . "'" : 'NULL';
	$vcu = "'" . (int)$code_unit . "'";

	$codesEsc = array_map(function ($c) use ($db_person) {
		return mysqli_real_escape_string($db_person, (string)$c);
	}, $staff_codes);
	$in = "'" . implode("','", $codesEsc) . "'";

	$personnelInfoMap = [];
	$sqlStaff = mysqli_query(
		$db_person,
		"SELECT id, code, unit_code, job_code, dep_code, CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
		 FROM staff
		 WHERE work_status IN ('0','1') AND code IN ($in)"
	);
	if (!$sqlStaff) {
		json_fail('ดึงรายชื่อบุคลากรไม่สำเร็จ', $debug ? ['debug' => ['mysqli_error' => mysqli_error($db_person)]] : []);
	}
	while ($r = mysqli_fetch_assoc($sqlStaff)) {
		$personnelInfoMap[(string)($r['code'] ?? '')] = [
			'staff_id' => (int)($r['id'] ?? 0),
			'name' => trim((string)($r['full_name'] ?? '')),
			'staff_row' => $r,
		];
	}

	// ตรวจสอบก่อนบันทึก: ห้ามซ้ำในหน่วยงานเดียวกัน (ไม่บล็อกชื่อเดียวกับสังกัดหลักในระบบบุคคล)
	foreach ($staff_codes as $staff_code_raw) {
		$person = $personnelInfoMap[(string)$staff_code_raw] ?? null;
		$pName = trim((string)($person['name'] ?? ''));
		$staffId = (int)($person['staff_id'] ?? 0);
		if ($pName === '') {
			continue;
		}
		$pNameEsc = mysqli_real_escape_string($db_Project, $pName);
		$vStaffId = $staffId > 0 ? "'" . $staffId . "'" : 'NULL';
		$dupWhere = $staffId > 0
			? "(staff_id = $vStaffId OR name = '$pNameEsc')"
			: "name = '$pNameEsc'";
		$chk = mysqli_query($db_Project, "SELECT 1 FROM set_project_personnel WHERE code_unit=$vcu AND $dupWhere AND status!='99' LIMIT 1");
		if ($chk && mysqli_num_rows($chk) > 0) {
			json_fail('มีหน่วยงานนี้อยู่แล้วสำหรับบุคลากรที่เลือก');
		}
	}

	$inserted = 0;
	$skipped = 0;
	$failed = 0;

	foreach ($staff_codes as $staff_code_raw) {
		$person = $personnelInfoMap[(string)$staff_code_raw] ?? null;
		$pName = trim((string)($person['name'] ?? ''));
		$staffId = (int)($person['staff_id'] ?? 0);
		if ($pName === '') {
			$skipped++;
			continue;
		}
		$pNameEsc = mysqli_real_escape_string($db_Project, $pName);
		$vStaffId = $staffId > 0 ? "'" . $staffId . "'" : 'NULL';

		$ok = mysqli_query(
			$db_Project,
			"INSERT INTO set_project_personnel(name,staff_id,code_department,code_job,code_unit,status)
			 VALUES('$pNameEsc',$vStaffId,$vcd,$vcj,$vcu,'$status')"
		);
		if ($ok) $inserted++;
		else $failed++;
	}

	json_ok(['inserted' => $inserted, 'skipped' => $skipped, 'failed' => $failed]);
} catch (Throwable $e) {
	@error_log('save_personnel_ajax.php: ' . $e->getMessage());
	json_fail('เกิดข้อผิดพลาดระหว่างบันทึก', $debug ? ['debug' => ['exception' => $e->getMessage()]] : []);
}

