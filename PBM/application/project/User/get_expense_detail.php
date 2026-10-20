<?php
header('Content-Type: application/json; charset=utf-8');
include_once(__DIR__ . '/../../config/config_db.php');

$code_expenses = isset($_POST['code_expenses']) ? trim((string)$_POST['code_expenses']) : '';
$data = [];

if ($code_expenses !== '') {
    $stmt = mysqli_prepare(
        $db_Project,
        "SELECT * FROM set_project_expenses_detail WHERE code_expenses = ? AND status = '1' ORDER BY id ASC"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $code_expenses);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $data[] = $row;
            }
        }
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
