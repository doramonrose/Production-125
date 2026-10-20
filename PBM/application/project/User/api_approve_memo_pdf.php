<?php
/**
 * API: แสดง/ดาวน์โหลด PDF บันทึกอนุมัติโครงการ สำหรับระบบภายนอก
 *
 * ตัวอย่างเรียกใช้:
 *   GET  /project/User/api_approve_memo_pdf.php?project_code=S69-007
 *   GET  /project/User/api_approve_memo_pdf.php?project_code=S69-007&disposition=inline
 *   GET  /project/User/api_approve_memo_pdf.php?project_code=S69-007&disposition=download
 *   POST project_code=S69-007
 *
 * พารามิเตอร์:
 *   - project_code (จำเป็น) รหัสโครงการ เช่น S69-007
 *   - disposition  optional: inline (เปิดดูในเบราว์เซอร์/ฝัง iframe) | download
 *   - token        optional: ถ้าตั้งค่า PM_APPROVE_MEMO_API_TOKEN ใน env/constant ต้องส่งให้ตรง
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

// CORS แบบจำกัดสำหรับระบบภายในคณะ (ปรับได้ด้วย PM_APPROVE_MEMO_API_CORS)
$cors = '';
if (defined('PM_APPROVE_MEMO_API_CORS')) {
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
if (defined('PM_APPROVE_MEMO_API_TOKEN')) {
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

$projectCode = trim((string)($_GET['project_code'] ?? $_POST['project_code'] ?? ''));
if ($projectCode === '') {
	$projectCode = trim((string)($_GET['code'] ?? $_POST['code'] ?? ''));
}
if ($projectCode === '') {
	$fail(400, 'กรุณาระบุ project_code');
}

if (!isset($db_Project) || !($db_Project instanceof mysqli)) {
	$fail(500, 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ');
}

$pre = function_exists('pm_find_pre_project_by_code')
	? pm_find_pre_project_by_code($db_Project, $projectCode)
	: null;
if (!$pre) {
	$fail(404, 'ไม่พบโครงการจากรหัสที่ระบุ');
}

$projectId = (int)($pre['id'] ?? 0);
$built = function_exists('pm_build_approve_memo_payload_from_db')
	? pm_build_approve_memo_payload_from_db($db_Project, $db_person ?? null, $projectId, $pre)
	: ['ok' => 0, 'msg' => 'ระบบยังไม่พร้อม'];
if (empty($built['ok']) || empty($built['data']) || !is_array($built['data'])) {
	$fail(403, (string)($built['msg'] ?? 'โครงการยังไม่พร้อมออกบันทึกอนุมัติ'));
}

$disposition = strtolower(trim((string)($_GET['disposition'] ?? $_POST['disposition'] ?? 'inline')));
if (!in_array($disposition, ['inline', 'download', 'attachment'], true)) {
	$disposition = 'inline';
}

$PM_APPROVE_MEMO_API = true;
$PM_APPROVE_MEMO_DISPOSITION = $disposition;
$PM_APPROVE_MEMO_PROJECT_ID = $projectId;
$PM_APPROVE_MEMO_DATA = $built['data'];
$GLOBALS['PM_APPROVE_MEMO_DATA'] = $PM_APPROVE_MEMO_DATA;

// ป้องกัน cache ของ PDF ระหว่างระบบภายนอก
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require __DIR__ . '/approve_memo_pdf.php';
exit;
