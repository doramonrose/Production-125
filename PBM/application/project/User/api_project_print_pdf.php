<?php
/**
 * API: แสดง/ดาวน์โหลด PDF รายละเอียดโครงการ สำหรับระบบภายนอก
 * (เนื้อหาเดียวกับหน้า print_project / print_project_pdf)
 *
 * ตัวอย่างเรียกใช้:
 *   GET  /project/User/api_project_print_pdf.php?project_code=S69-007
 *   GET  /project/User/api_project_print_pdf.php?project_code=S69-007&disposition=inline
 *   GET  /project/User/api_project_print_pdf.php?project_code=S69-007&disposition=download
 *   GET  /project/User/api_project_print_pdf.php?project_id=91
 *   POST project_code=S69-007
 *
 * พารามิเตอร์:
 *   - project_code หรือ project_id (อย่างน้อยหนึ่งอย่าง)
 *   - disposition  optional: inline (ค่าเริ่มต้น) | download
 *   - token        optional: ถ้าตั้งค่า PM_PROJECT_PRINT_API_TOKEN ใน env/constant ต้องส่งให้ตรง
 *
 * Response:
 *   - สำเร็จ: application/pdf
 *   - ไม่สำเร็จ: text/plain + HTTP 4xx
 */
ob_start();

include_once __DIR__ . '/../../config/config_db.php';
include_once __DIR__ . '/../fn/main_staff.php';
include_once __DIR__ . '/../fn/main_project.php';

$fail = static function (int $code, string $msg) {
	while (ob_get_level() > 0) {
		@ob_end_clean();
	}
	http_response_code($code);
	header('Content-Type: text/plain; charset=utf-8');
	header('X-Content-Type-Options: nosniff');
	echo $msg;
	exit;
};

// CORS แบบจำกัดสำหรับระบบภายในคณะ (ปรับได้ด้วย PM_PROJECT_PRINT_API_CORS)
$cors = '';
if (defined('PM_PROJECT_PRINT_API_CORS')) {
	$cors = trim((string)PM_PROJECT_PRINT_API_CORS);
} elseif (getenv('PM_PROJECT_PRINT_API_CORS') !== false) {
	$cors = trim((string)getenv('PM_PROJECT_PRINT_API_CORS'));
} elseif (defined('PM_APPROVE_MEMO_API_CORS')) {
	$cors = trim((string)PM_APPROVE_MEMO_API_CORS);
} elseif (getenv('PM_APPROVE_MEMO_API_CORS') !== false) {
	$cors = trim((string)getenv('PM_APPROVE_MEMO_API_CORS'));
}
if ($cors === '') {
	$cors = '*';
}
header('Access-Control-Allow-Origin: ' . $cors);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Token');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
	while (ob_get_level() > 0) {
		@ob_end_clean();
	}
	http_response_code(204);
	exit;
}

$expectedToken = '';
if (defined('PM_PROJECT_PRINT_API_TOKEN')) {
	$expectedToken = trim((string)PM_PROJECT_PRINT_API_TOKEN);
} elseif (getenv('PM_PROJECT_PRINT_API_TOKEN') !== false) {
	$expectedToken = trim((string)getenv('PM_PROJECT_PRINT_API_TOKEN'));
} elseif (defined('PM_APPROVE_MEMO_API_TOKEN')) {
	$expectedToken = trim((string)PM_APPROVE_MEMO_API_TOKEN);
} elseif (getenv('PM_APPROVE_MEMO_API_TOKEN') !== false) {
	$expectedToken = trim((string)getenv('PM_APPROVE_MEMO_API_TOKEN'));
}
if ($expectedToken !== '') {
	$got = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
	if ($got === '' && !empty($_SERVER['HTTP_X_API_TOKEN'])) {
		$got = trim((string)$_SERVER['HTTP_X_API_TOKEN']);
	}
	if ($got === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
		$auth = trim((string)$_SERVER['HTTP_AUTHORIZATION']);
		if (stripos($auth, 'Bearer ') === 0) {
			$got = trim(substr($auth, 7));
		}
	}
	if (!hash_equals($expectedToken, $got)) {
		$fail(401, 'Unauthorized');
	}
}

if (!isset($db_Project) || !($db_Project instanceof mysqli)) {
	$fail(500, 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ');
}

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
$projectCode = trim((string)($_GET['project_code'] ?? $_POST['project_code'] ?? ''));
if ($projectCode === '') {
	$projectCode = trim((string)($_GET['code'] ?? $_POST['code'] ?? ''));
}

$pre = null;
if ($projectId > 0) {
	$pre = function_exists('getPreProjectID') ? getPreProjectID($db_Project, $projectId) : null;
} elseif ($projectCode !== '') {
	$pre = function_exists('pm_find_pre_project_by_code')
		? pm_find_pre_project_by_code($db_Project, $projectCode)
		: null;
} else {
	$fail(400, 'กรุณาระบุ project_code หรือ project_id');
}

if (!$pre || !is_array($pre)) {
	$fail(404, 'ไม่พบโครงการจากรหัสที่ระบุ');
}

$projectId = (int)($pre['id'] ?? 0);
if ($projectId <= 0) {
	$fail(404, 'ไม่พบโครงการจากรหัสที่ระบุ');
}

$disposition = strtolower(trim((string)($_GET['disposition'] ?? $_POST['disposition'] ?? 'inline')));
if (!in_array($disposition, ['inline', 'download', 'attachment'], true)) {
	$disposition = 'inline';
}

$PM_PROJECT_PRINT_API = true;
$PM_PROJECT_PRINT_DISPOSITION = $disposition;
$PM_PROJECT_PRINT_PROJECT_ID = $projectId;
$op = 'print_project_pdf';
$row = ['id' => 0, 'code' => '', 'unit_code' => ''];

// ป้องกัน cache ของ PDF ระหว่างระบบภายนอก
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

while (ob_get_level() > 0) {
	@ob_end_clean();
}

require __DIR__ . '/project_print.php';
exit;
