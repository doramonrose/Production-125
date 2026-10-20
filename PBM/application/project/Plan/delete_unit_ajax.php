<?php
// ลบหน่วยงาน (status=99) และซ่อนรายชื่อในหน่วยงานนั้น (status=99) — JSON only
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');

header('Content-Type: application/json; charset=utf-8');

function json_fail(string $message): void
{
	echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
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
	ensureSetProjectPersonnelTable($db_Project);

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		json_fail('Method Not Allowed');
	}

	$unit_id = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
	if ($unit_id <= 0) {
		json_fail('ไม่พบรหัสหน่วยงาน');
	}

	// set_unit.status = 99
	$stmt1 = $db_Project->prepare("UPDATE set_unit SET status='99' WHERE id=?");
	if (!$stmt1) json_fail('เตรียมคำสั่ง SQL ไม่สำเร็จ');
	$stmt1->bind_param('i', $unit_id);
	$stmt1->execute();
	$affected_unit = $stmt1->affected_rows;
	$stmt1->close();

	// set_project_personnel.status = 99 (ของหน่วยนี้)
	$stmt2 = $db_Project->prepare("UPDATE set_project_personnel SET status='99' WHERE code_unit=?");
	if (!$stmt2) json_fail('เตรียมคำสั่ง SQL ไม่สำเร็จ');
	$stmt2->bind_param('i', $unit_id);
	$stmt2->execute();
	$affected_personnel = $stmt2->affected_rows;
	$stmt2->close();

	json_ok(['unit_affected' => $affected_unit, 'personnel_affected' => $affected_personnel]);
} catch (Throwable $e) {
	@error_log('delete_unit_ajax.php: ' . $e->getMessage());
	json_fail('เกิดข้อผิดพลาดระหว่างลบ');
}

