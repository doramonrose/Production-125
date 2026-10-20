<?php
session_start();
while (ob_get_level() > 0) {
	@ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../../config/config_db.php';
include_once __DIR__ . '/../fn/main_staff.php';
include_once __DIR__ . '/../fn/main_project.php';

$sess_code = (string)($_SESSION['code'] ?? '');
$sess_email = (string)($_SESSION['r_email'] ?? '');
$staffId = 0;
$staffName = '';
if ($sess_code !== '' && $sess_email !== '' && isset($db_person) && $db_person) {
	$stmt = @mysqli_prepare($db_person, "SELECT id, prefix_code, fname, lname FROM staff WHERE code = ? AND email = ? LIMIT 1");
	if ($stmt) {
		mysqli_stmt_bind_param($stmt, 'ss', $sess_code, $sess_email);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		$row = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($stmt);
		if (is_array($row)) {
			$staffId = (int)($row['id'] ?? 0);
			$prefix = function_exists('getPrefix') ? (getPrefix($db_person, $row['prefix_code'] ?? '') ?: []) : [];
			$staffName = trim(($prefix['name'] ?? '') . ' ' . ($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
		}
	}
}

$role = (isset($_POST['role']) && (string)$_POST['role'] === 'plan') ? 'plan' : 'user';
$pageName = trim((string)($_POST['page'] ?? ''));
$rating = (int)($_POST['rating'] ?? 0);
$comment = (string)($_POST['comment'] ?? '');

$out = function_exists('pm_save_system_feedback')
	? pm_save_system_feedback($db_Project ?? null, $staffId, $staffName, $role, $pageName, $rating, $comment)
	: ['ok' => 0, 'msg' => 'ไม่พร้อมบันทึก'];

echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;
