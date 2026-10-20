<?php
header("Content-Type: application/json; charset=utf-8");

include_once(__DIR__ . '/../../config/config_db.php');

// รับค่าที่ส่งมา (เป็น comma-separated) — บังคับ intval ทุกค่า
$list = isset($_POST['project_list']) ? trim((string)$_POST['project_list']) : '';
$ids = array_filter(array_map('intval', explode(",", $list)));

if (empty($ids)) {
    echo json_encode([]);
    exit;
}

// แปลง array → string (ค่าทั้งหมดผ่าน intval แล้วปลอดภัย)
$id_sql = implode(",", $ids);

/*
    ตารางที่ใช้ (อ้างจากระบบของคุณ)

    project                    → id, name_project, ...
    project_consistency        → project_id, code_so, code_ap
    set_so                    → id, name
    set_ap                    → id, name
    project_activity          → project_id, budget
*/

$sql = "
SELECT 
    so.name AS so_name,
    ap.name AS ap_name,
    COUNT(DISTINCT p.id) AS project_count,
    SUM(IFNULL(act.budget,0)) AS total_budget
FROM pre_project p

LEFT JOIN project_consistency pc 
       ON pc.project_id = p.id AND pc.status != '99'

LEFT JOIN set_so so 
       ON so.id = pc.code_so

LEFT JOIN set_ap ap 
       ON ap.id = pc.code_ap

LEFT JOIN project_activity act 
       ON act.code_pre_project = p.id AND act.status != '99'

WHERE p.id IN ($id_sql)
GROUP BY pc.code_so, pc.code_ap
ORDER BY pc.code_so, pc.code_ap
";

$q = mysqli_query($db_Project, $sql);
$data = [];

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = [
            "so_name"       => $r['so_name'] ?: "-",
            "ap_name"       => $r['ap_name'] ?: "-",
            "project_count" => $r['project_count'],
            "total_budget"  => $r['total_budget']
        ];
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
