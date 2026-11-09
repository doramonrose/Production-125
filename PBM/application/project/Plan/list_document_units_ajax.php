<?php
/**
 * รายการหน่วยงานจาก API ส่วนกลาง สำหรับเลือก unit_code ตอนตั้งค่าหน่วยงาน
 */
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
include_once __DIR__ . '/../../config/config_db.php';
require_once __DIR__ . '/../fn/document_number_client.php';

header('Content-Type: application/json; charset=utf-8');

try {
	if (empty($_SESSION['code']) && empty($_SESSION['r_email'])) {
		http_response_code(401);
		echo json_encode(['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$options = pm_document_number_list_org_options();
	echo json_encode([
		'ok' => true,
		'options' => $options,
		'count' => count($options),
	], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
	@error_log('list_document_units_ajax: ' . $e->getMessage());
	echo json_encode([
		'ok' => false,
		'message' => 'โหลดรายการหน่วยงานส่วนกลางไม่สำเร็จ',
		'options' => [],
	], JSON_UNESCAPED_UNICODE);
}
