<?php
// แก้ไขข้อมูลหน่วยงาน (ไม่แตะรายชื่อบุคลากร) — AJAX JSON only
include_once(__DIR__ . '/../../config/config_db.php');

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
	global $db_Project;
	if (!$db_Project) {
		json_fail('เชื่อมต่อฐานข้อมูลไม่สำเร็จ');
	}
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
		http_response_code(405);
		json_fail('Method Not Allowed');
	}

	$unit_id = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
	$unit_name = trim((string)($_POST['unit_name'] ?? ''));
	$paper_name = trim((string)($_POST['paper_name'] ?? ''));
	$paper_start = trim((string)($_POST['paper_start'] ?? ''));
	$has_code_job = array_key_exists('code_job', $_POST);
	$has_code_department = array_key_exists('code_department', $_POST);
	$code_job = $has_code_job ? (int)$_POST['code_job'] : 0;
	$code_department = $has_code_department ? (int)$_POST['code_department'] : 0;

	if ($unit_id <= 0) {
		json_fail('ไม่พบรหัสหน่วยงาน');
	}
	if ($unit_name === '') {
		json_fail('กรุณากรอกชื่อหน่วยงาน');
	}

	$columns = [];
	try {
		$qcol = mysqli_query($db_Project, "SHOW COLUMNS FROM set_unit");
		if ($qcol) {
			while ($c = mysqli_fetch_assoc($qcol)) {
				$columns[strtolower((string)($c['Field'] ?? ''))] = true;
			}
		}
	} catch (Throwable $e) {
		@error_log('update_unit_ajax show columns: ' . $e->getMessage());
	}

	$sets = [];
	$sets[] = "name='" . mysqli_real_escape_string($db_Project, $unit_name) . "'";
	if (isset($columns['paper_name'])) {
		$sets[] = "paper_name='" . mysqli_real_escape_string($db_Project, $paper_name) . "'";
	}
	if (isset($columns['paper_start'])) {
		$sets[] = "paper_start='" . mysqli_real_escape_string($db_Project, $paper_start) . "'";
	}
	if ($has_code_job && isset($columns['code_job'])) {
		$sets[] = "code_job=" . ($code_job > 0 ? "'" . $code_job . "'" : "NULL");
	}
	if ($has_code_department && isset($columns['code_department'])) {
		$sets[] = "code_department=" . ($code_department > 0 ? "'" . $code_department . "'" : "NULL");
	}

	$sql = "UPDATE set_unit SET " . implode(', ', $sets) . " WHERE id='" . (int)$unit_id . "' LIMIT 1";
	$ok = mysqli_query($db_Project, $sql);
	if (!$ok) {
		json_fail('แก้ไขหน่วยงานไม่สำเร็จ');
	}

	json_ok(['unit_id' => $unit_id, 'affected' => (int)mysqli_affected_rows($db_Project)]);
} catch (Throwable $e) {
	@error_log('update_unit_ajax.php: ' . $e->getMessage());
	json_fail('เกิดข้อผิดพลาดระหว่างบันทึก');
}

