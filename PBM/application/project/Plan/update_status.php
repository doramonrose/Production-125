<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');
ensureSetProjectPersonnelTable($db_Project);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

if (isset($_POST['type']) && trim((string)$_POST['type']) === 'reorder_expenses_detail') {
    header('Content-Type: application/json; charset=utf-8');
    if (function_exists('ensureSetExpensesDetailAttachSchema')) {
        ensureSetExpensesDetailAttachSchema($db_Project);
    }
    $catId = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
    $idsRaw = $_POST['ids'] ?? [];
    if (!is_array($idsRaw)) {
        $idsRaw = json_decode((string)$idsRaw, true);
    }
    if ($catId <= 0 || !is_array($idsRaw) || empty($idsRaw)) {
        http_response_code(400);
        echo json_encode(['ok' => 0, 'msg' => 'missing_param']);
        exit;
    }
    $stmt = $db_Project->prepare("UPDATE set_project_expenses_detail SET sort_order = ? WHERE id = ? AND code_expenses = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => 0, 'msg' => 'prepare_failed']);
        exit;
    }
    $order = 1;
    foreach ($idsRaw as $itemId) {
        $iid = (int)$itemId;
        if ($iid <= 0) {
            continue;
        }
        $stmt->bind_param('iii', $order, $iid, $catId);
        $stmt->execute();
        $order++;
    }
    $stmt->close();
    echo json_encode(['ok' => 1]);
    exit;
}

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : null;
$type   = isset($_POST['type']) ? trim((string)$_POST['type']) : '';

if ($id <= 0 || $status === null || $type === '') {
        http_response_code(400);
        echo "missing_param";
        exit;
    }

    // ✅ ตรวจสอบว่า type ที่ส่งมาตรงกับ table ที่รองรับ
    switch ($type) {

        // ---------- PROJECT TABLES ----------
        case 'set_Project_Type':
            $stmt = $db_Project->prepare("UPDATE set_project_type SET status = ? WHERE id = ?");
            break;

        case 'set_nature':
            $stmt = $db_Project->prepare("UPDATE set_project_nature SET status = ? WHERE id = ?");
            break;

        case 'set_budget':
            // แหล่งงบจากระบบ PBM ($db_pbmBudget_PBM.budget_set_source)
            global $db_pbmBudget_PBM;
            if (isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli) {
                $stmt = $db_pbmBudget_PBM->prepare("UPDATE budget_set_source SET budget_source_status = ?, budget_source_date_modify = NOW() WHERE budget_source_id = ?");
            } else {
                $stmt = $db_Project->prepare("UPDATE set_project_budget SET status = ? WHERE id = ?");
            }
            break;

        case 'set_expenses':
            $stmt = $db_Project->prepare("UPDATE set_project_expenses SET status = ? WHERE id = ?");
            break;

        case 'set_expenses_detail':
            $stmt = $db_Project->prepare("UPDATE set_project_expenses_detail SET status = ? WHERE id = ?");
            break;

        case 'set_status':
            $stmt = $db_Project->prepare("UPDATE set_project_status SET status = ? WHERE id = ?");
            break;

        case 'set_target':
            $stmt = $db_Project->prepare("UPDATE set_project_target SET status = ? WHERE id = ?");
            break;

        case 'set_personnel':
            $stmt = $db_Project->prepare("UPDATE set_project_personnel SET status = ? WHERE id = ?");
            break;

        case 'set_unit_project':
            // เปิด/ปิดสถานะหน่วยงานในฐาน project
            $stmt = $db_Project->prepare("UPDATE set_unit SET status = ? WHERE id = ?");
            break;
        case 'set_department_project':
            // เปิด/ปิดสถานะหน่วยงานหลัก (department) ในฐาน project
            $stmt = $db_Project->prepare("UPDATE set_department SET status = ? WHERE id = ?");
            break;

        case 'set_consistency':
            $stmt = $db_Project->prepare("UPDATE set_project_consistency SET status = ? WHERE id = ?");
            break;

        // ---------- KPI TABLE ----------
        case 'kpi':
            $stmt = $db_KPI->prepare("UPDATE kpi SET status = ? WHERE id = ?");
            break;

        // ---------- TYPE ไม่ตรง ----------
        default:
            http_response_code(400);
            echo "invalid_type";
            exit;
    }

    // ✅ ตรวจสอบ prepare สำเร็จหรือไม่
    if (!$stmt) {
        http_response_code(500);
        $errDb = ($type === 'set_budget' && isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli)
            ? $db_pbmBudget_PBM
            : (($type === 'kpi') ? $db_KPI : $db_Project);
        echo "sql_prepare_failed: " . ($errDb->error ?? '');
        exit;
    }

    // ✅ Bind และ Execute
    $stmt->bind_param("ii", $status, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "success";
        } else {
            // ไม่มีข้อมูลเปลี่ยนแปลง
            echo "no_change";
        }
    } else {
        http_response_code(500);
        echo "sql_execute_failed: " . $stmt->error;
    }

    $stmt->close();
