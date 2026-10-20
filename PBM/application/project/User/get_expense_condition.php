<?php
header('Content-Type: application/json; charset=utf-8');
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$response = [];

if ($id > 0) {
    if (function_exists('ensureSetExpensesDetailAttachSchema')) {
        ensureSetExpensesDetailAttachSchema($db_Project);
    }
    // ป้องกัน SQL Injection ด้วย prepared statement
    $stmt = $db_Project->prepare("SELECT id, name, detail, attach_file, attach_name FROM set_project_expenses_detail WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $attachStored = trim((string)($row['attach_file'] ?? ''));
        $attachName = trim((string)($row['attach_name'] ?? ''));
        $attachUrl = '';
        if ($attachStored !== '' && function_exists('pmExpenseDetailAttachUrl')) {
            $attachUrl = pmExpenseDetailAttachUrl($attachStored);
        }
        if ($attachName === '') {
            $attachName = $attachStored;
        }

        // กำหนด default ถ้าไม่มี detail
        $response = [
            'status' => 'success',
            'id' => $row['id'],
            'name' => $row['name'],
            'detail' => !empty($row['detail']) ? $row['detail'] : 'ไม่มีเงื่อนไขเพิ่มเติม',
            'attach_url' => $attachUrl,
            'attach_name' => $attachName,
        ];
    } else {
        $response = [
            'status' => 'not_found',
            'detail' => 'ไม่พบข้อมูลเงื่อนไขในระบบ'
        ];
    }

    $stmt->close();
} else {
    $response = [
        'status' => 'error',
        'detail' => 'ไม่ได้รับค่า ID ที่ต้องการค้นหา'
    ];
}

// ส่งข้อมูลออกในรูปแบบ JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
