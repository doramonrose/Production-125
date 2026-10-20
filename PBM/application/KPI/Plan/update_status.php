<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../../../config/connect_db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    $type = $_POST['type'] ?? null;

    if (!$id || $status === null || !$type) {
        http_response_code(400);
        echo "missing_param";
        exit;
    }

    $tableMap = [
        'set_mark' => 'set_symbol',
        'set_unit' => 'set_unit',
        'set_type' => 'set_type',
        'set_so' => 'set_so',
        'set_tag' => 'set_tag',
        'set_count' => 'set_count',
        'set_side' => 'set_side',
        'set_owner' => 'set_owner',
        'set_report_round' => 'set_report_round',
        'set_ap' => 'set_ap',
        'kpi' => 'kpi'
    ];

    if (!isset($tableMap[$type])) {
        echo "invalid_type";
        exit;
    }

    $table = $tableMap[$type];

    // ✅ ตรวจสอบว่าค่าปัจจุบันตรงกับที่ส่งมาหรือไม่
    $check = $db_KPI->prepare("SELECT status FROM {$table} WHERE id=?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();

    if ($row && (int)$row['status'] === (int)$status) {
        echo "no_change";
        exit;
    }

    // ✅ อัปเดตเฉพาะ record ที่ระบุ id เท่านั้น
    $stmt = $db_KPI->prepare("UPDATE {$table} SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "sql_execute_failed: " . $stmt->error;
    }
}
