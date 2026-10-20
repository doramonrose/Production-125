<?php
include_once(__DIR__ . '/../../config/config_db.php');

$ids = $_POST['ids'] ?? [];

if (!is_array($ids)) {
    $ids = explode(",", (string)$ids);
}
$ids = array_filter(array_map("intval", $ids));

if (empty($ids)) {
    echo "<tr><td colspan='4' class='text-center text-danger'>ไม่พบข้อมูล</td></tr>";
    exit;
}

$id_list = implode(",", $ids);

$sql = "
SELECT 
    so.name AS so_name,
    ap.name AS ap_name,
    COUNT(DISTINCT p.id) AS project_count,
    SUM(IFNULL(act.total,0)) AS total_budget
FROM pre_project p
LEFT JOIN project_consistency pc ON pc.project_id = p.id AND (pc.status IS NULL OR pc.status != '99')
LEFT JOIN set_so so ON so.id = pc.code_so
LEFT JOIN set_ap ap ON ap.id = pc.code_ap
LEFT JOIN project_activity_budget act ON act.code_pre_project = p.id
WHERE p.id IN ($id_list)
GROUP BY pc.code_so, pc.code_ap, so.name, ap.name
ORDER BY pc.code_so, pc.code_ap

";

$q = mysqli_query($db_Project, $sql);
$summary = [];

if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $summary[] = [
            'so'     => $row['so_name'] ?: '-',
            'ap'     => $row['ap_name'] ?: '-',
            'count'  => (int)$row['project_count'],
            'budget' => floatval($row['total_budget'])
        ];
    }
}

/* ---------- แสดงผล ---------- */
$total_project = 0;
$total_budget  = 0;

foreach ($summary as $item) {
    $total_project += $item['count'];
    $total_budget  += $item['budget'];

    $so = htmlspecialchars($item['so'], ENT_QUOTES, 'UTF-8');
    $ap = htmlspecialchars($item['ap'], ENT_QUOTES, 'UTF-8');
    echo "
    <tr>
        <td>{$so}</td>
        <td>{$ap}</td>
        <td class='text-center'>{$item['count']}</td>
        <td class='text-right'>" . number_format($item['budget'], 2) . "</td>
    </tr>
    ";
}

echo "
<tr class='table-secondary font-weight-bold'>
    <td colspan='2' class='text-right'>รวมทั้งหมด</td>
    <td class='text-center'>$total_project</td>
    <td class='text-right'>" . number_format($total_budget, 2) . "</td>
</tr>
";
