<?php
include_once(__DIR__ . '/../../config/config_db.php');

$list_raw = isset($_POST['project_list']) ? trim((string)$_POST['project_list']) : '';
$list     = array_filter(array_map('intval', explode(',', $list_raw)));
$approve_position = isset($_POST['approve_position']) ? trim((string)$_POST['approve_position']) : '';
$approve_by       = isset($_POST['approve_by']) ? trim((string)$_POST['approve_by']) : '';
$submit_code      = isset($_SESSION['code']) ? trim((string)$_SESSION['code']) : '';

if (empty($list)) {
    echo "<script>Swal.fire('ข้อผิดพลาด','ไม่พบรายการโครงการ','error');</script>";
    exit;
}

$stmt = mysqli_prepare($db_Project, "
    UPDATE pre_project
    SET status = '2',
        submit_by = ?,
        submit_date = NOW(),
        approve_by = ?,
        approve_position = ?
    WHERE id = ?
");
if ($stmt) {
    foreach ($list as $pid) {
        mysqli_stmt_bind_param($stmt, 'sssi', $submit_code, $approve_by, $approve_position, $pid);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

// อัปโหลด PDF
// if($pdf['size'] > 0){
//     $newName = "plan_".time().".pdf";
//     move_uploaded_file($pdf['tmp_name'], "../../uploads/".$newName);
// }

echo "<script>
    Swal.fire('สำเร็จ','ขอบรรจุโครงการเรียบร้อยแล้ว','success')
        .then(()=>{ window.location='../page.php?page=project_all&overview=1'; });
</script>";
