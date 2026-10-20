<?php
header('Content-Type: application/json; charset=utf-8');
include_once(__DIR__ . '/../../config/config_db.php');

$code_consistency = isset($_POST['code_consistency']) ? trim((string)$_POST['code_consistency']) : '';
$selected = $_POST['selected'] ?? [];

// ถ้า selected ไม่ใช่ array (เช่น "1,3")
if (!is_array($selected)) {
    $selected = explode(",", (string)$selected);
}

// บังคับให้เป็น int ทั้งหมด
$selected = array_map('intval', $selected);

$data = [];

$stmt = mysqli_prepare(
    $db_Project,
    "SELECT * FROM set_project_consistency_detail WHERE code_consistency = ? AND status = '1' ORDER BY id ASC"
);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $code_consistency);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
} else {
    $sql = false;
}

if ($sql) {
    while ($row = mysqli_fetch_assoc($sql)) {

    $row['id'] = intval($row['id']); // บังคับเป็น int

    // check value
    $row['checked'] = in_array($row['id'], $selected) ? 1 : 0;

    $data[] = $row;
    }
}

echo json_encode($data);
