<?php
// สลับสิทธิ์ Admin หน่วยงานรายคน (set_project_personnel.is_unit_admin)
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

try {
	global $db_Project, $db_person;
	if (!$db_Project) {
		json_fail('เชื่อมต่อฐานข้อมูลไม่สำเร็จ');
	}
	ensureSetProjectPersonnelTable($db_Project);
	ensureSetDepartmentAdminTable($db_Project);

	// PHP 8+ อาจเปิด MYSQLI_REPORT_STRICT — mysqli จะ throw แทนการคืน false ทำให้ข้อความกลายเป็น "เกิดข้อผิดพลาด"
	if (function_exists('mysqli_report')) {
		mysqli_report(MYSQLI_REPORT_OFF);
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		json_fail('Method not allowed');
	}

	$id = isset($_POST['personnel_id']) ? (int) $_POST['personnel_id'] : 0;
	$staffIdReq = isset($_POST['staff_id']) ? (int) $_POST['staff_id'] : 0;
	$codeUnitReq = isset($_POST['code_unit']) ? (int) $_POST['code_unit'] : 0;
	$unitCodeRaw = isset($_POST['unit_code']) ? trim((string) $_POST['unit_code']) : '';
	$codeDepReq = isset($_POST['code_department']) ? (int) $_POST['code_department'] : 0;
	$codeJobReq = isset($_POST['code_job']) ? (int) $_POST['code_job'] : 0;

	if ($id <= 0) {
		$hasUnitHint = $codeUnitReq > 0 || $unitCodeRaw !== ''
			|| $codeDepReq > 0 || $codeJobReq > 0;
		if ($staffIdReq <= 0 || !$hasUnitHint) {
			json_fail('ไม่พบรหัสรายการ');
		}
		if (!$db_person) {
			json_fail('เชื่อมต่อฐานข้อมูลบุคลากรไม่สำเร็จ');
		}
		$sid = (int) $staffIdReq;
		$unitRow = function_exists('planResolveSetUnitRowForToggle')
			? planResolveSetUnitRowForToggle($db_Project, $codeUnitReq, $unitCodeRaw, $codeDepReq, $codeJobReq)
			: null;
		if (!$unitRow || (string) ($unitRow['status'] ?? '') === '99') {
			json_fail('ไม่พบหน่วยงาน');
		}
		$cu = (int) ($unitRow['id'] ?? 0);
		if ($cu <= 0) {
			json_fail('ไม่พบหน่วยงาน');
		}
		$qExist = mysqli_query(
			$db_Project,
			"SELECT id FROM set_project_personnel
			 WHERE staff_id = $sid AND code_unit = $cu AND status != '99' LIMIT 1"
		);
		$existRow = ($qExist && mysqli_num_rows($qExist) > 0) ? mysqli_fetch_assoc($qExist) : null;
		if ($existRow) {
			$id = (int) ($existRow['id'] ?? 0);
		} else {
			$codeDepartment = (int) ($unitRow['code_department'] ?? 0);
			$codeJob = (int) ($unitRow['code_job'] ?? 0);
			$qs = mysqli_query(
				$db_person,
				"SELECT id, code, fname, lname, unit_code, job_code, dep_code, position_code
				 FROM staff WHERE id = $sid LIMIT 1"
			);
			$st = ($qs && mysqli_num_rows($qs) > 0) ? mysqli_fetch_assoc($qs) : null;
			if (!$st) {
				json_fail('ไม่พบข้อมูลบุคลากร');
			}
			$st = is_array($st) ? $st : [];
			// เส้นทางตั้ง/ถอด Admin ต้องอนุญาตหน่วยหลักด้วย
			// (กฎห้ามซ้ำหน่วยหลักใช้เฉพาะฟอร์มเพิ่มหน่วยงานร่วม)
			$pName = trim((string) ($st['fname'] ?? '') . ' ' . (string) ($st['lname'] ?? ''));
			if ($pName === '') {
				json_fail('ไม่พบชื่อบุคลากร');
			}
			$pNameEsc = mysqli_real_escape_string($db_Project, $pName);
			$vStaffId = "'" . $sid . "'";
			$vcd = $codeDepartment > 0 ? "'" . $codeDepartment . "'" : 'NULL';
			$vcj = $codeJob > 0 ? "'" . $codeJob . "'" : 'NULL';
			$vcu = "'" . $cu . "'";
			$dupWhere = "(staff_id = $vStaffId OR name = '$pNameEsc')";
			$chk = mysqli_query(
				$db_Project,
				"SELECT 1 FROM set_project_personnel WHERE code_unit = $vcu AND $dupWhere AND status != '99' LIMIT 1"
			);
			if ($chk && mysqli_num_rows($chk) > 0) {
				json_fail('มีรายชื่อในหน่วยงานนี้อยู่แล้ว');
			}
			$ins = mysqli_query(
				$db_Project,
				"INSERT INTO set_project_personnel(name, staff_id, code_department, code_job, code_unit, status)
				 VALUES ('$pNameEsc', $vStaffId, $vcd, $vcj, $vcu, '1')"
			);
			if (!$ins) {
				json_fail('สร้างรายการไม่สำเร็จ');
			}
			$id = (int) mysqli_insert_id($db_Project);
		}
	}

	if ($id <= 0) {
		json_fail('ไม่พบรหัสรายการ');
	}

	$idEsc = (int) $id;
	$cur = null;
	$q = mysqli_query(
		$db_Project,
		"SELECT id, is_unit_admin, status, code_unit, code_department, code_job, staff_id
		 FROM set_project_personnel WHERE id = $idEsc LIMIT 1"
	);
	if ($q) {
		$cur = mysqli_fetch_assoc($q) ?: null;
	}
	if (!$cur || (string) ($cur['status'] ?? '') === '99') {
		json_fail('ไม่พบข้อมูลรายการ');
	}

	$next = ((int) ($cur['is_unit_admin'] ?? 0) === 1) ? 0 : 1;
	$codeUnit = (int) ($cur['code_unit'] ?? 0);
	$pDep = (int) ($cur['code_department'] ?? 0);
	$pJob = (int) ($cur['code_job'] ?? 0);

	$run = function (string $sql) use ($db_Project): bool {
		$res = mysqli_query($db_Project, $sql);

		return (bool) $res;
	};

	$useTx = false;
	if (function_exists('mysqli_begin_transaction')) {
		try {
			$useTx = (bool) @mysqli_begin_transaction($db_Project);
		} catch (Throwable $e) {
			$useTx = false;
		}
	}

	try {
		if ($next === 1) {
			$isGroup2 = false;
			$scopeDep = $pDep;
			$scopeJob = $pJob;

			if ($codeUnit > 0) {
				$qu = mysqli_query(
					$db_Project,
					"SELECT id, status, job_code, code_job, code_department
					 FROM set_unit WHERE id = " . (int) $codeUnit . " LIMIT 1"
				);
				$unit = ($qu && mysqli_num_rows($qu) > 0) ? mysqli_fetch_assoc($qu) : null;
				if ($unit && (string) ($unit['status'] ?? '') !== '99') {
					$jobCodeText = trim((string) ($unit['job_code'] ?? ''));
					$codeJobNum = (int) ($unit['code_job'] ?? 0);
					if ($jobCodeText === '' && $codeJobNum <= 0) {
						$isGroup2 = true;
					} else {
						$scopeDep = (int) ($unit['code_department'] ?? 0);
						$scopeJob = (int) ($unit['code_job'] ?? 0);
					}
				}
			}

			if ($isGroup2 && $codeUnit > 0) {
				// กลุ่มที่ 2: ได้เพียง 1 คนต่อหน่วยงาน (code_unit เดียวกัน)
				if (!$run(
					"UPDATE set_project_personnel SET is_unit_admin = 0
					 WHERE status != '99' AND id != $idEsc AND code_unit = " . (int) $codeUnit
				)) {
					throw new RuntimeException('clear_unit_admin');
				}
			} elseif (!$isGroup2 && ($scopeDep > 0 || $scopeJob > 0)) {
				// กลุ่มที่ 1: ได้เพียง 1 คนต่อคู่สังกัด (code_department) + งาน (code_job)
				if (!$run(
					"UPDATE set_project_personnel SET is_unit_admin = 0
					 WHERE status != '99' AND id != $idEsc
					 AND IFNULL(code_department,0) = " . (int) $scopeDep . "
					 AND IFNULL(code_job,0) = " . (int) $scopeJob
				)) {
					throw new RuntimeException('clear_dep_job_admin');
				}
			}
		}

		$ok = $run(
			"UPDATE set_project_personnel SET is_unit_admin = $next WHERE id = $idEsc AND status != '99' LIMIT 1"
		);
		if (!$ok) {
			throw new RuntimeException('update_self');
		}

		$staffIdCur = (int) ($cur['staff_id'] ?? 0);
		if (function_exists('planSyncSetDepartmentAdminForPersonnelToggle')) {
			planSyncSetDepartmentAdminForPersonnelToggle($db_Project, $db_person, $staffIdCur, $codeUnit, $next, $pDep, $pJob);
		}

		if ($useTx && function_exists('mysqli_commit')) {
			try {
				@mysqli_commit($db_Project);
			} catch (Throwable $e) {
				throw new RuntimeException('commit');
			}
		}
	} catch (Throwable $e) {
		if ($useTx && function_exists('mysqli_rollback')) {
			try {
				@mysqli_rollback($db_Project);
			} catch (Throwable $e2) {
				// ไม่มี active transaction — ข้าม
			}
		}
		json_fail('อัปเดตไม่สำเร็จ');
	}

	json_ok(['is_unit_admin' => $next]);
} catch (Throwable $e) {
	@error_log('toggle_personnel_unit_admin_ajax.php: ' . $e->getMessage());
	json_fail('เกิดข้อผิดพลาด');
}
