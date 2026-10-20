<?php
include_once(__DIR__ . '/../../config/config_db.php');

$year = isset($_GET['year']) ? trim((string)$_GET['year']) : '';
$data = [];

if ($year !== '') {
    $stmt = mysqli_prepare($db_Project, "SELECT code, name FROM pre_projects WHERE budget_year = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = [
                    "code" => $row['code'],
                    "name" => $row['name']
                ];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($data);
