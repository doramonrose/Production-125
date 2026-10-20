<?php
session_start();
include("../../config/config_db.php");
date_default_timezone_set('Asia/Bangkok');

// รับค่า hash_staff จาก query
$hash_staff = isset($_GET['hash_staff']) ? trim((string)$_GET['hash_staff']) : '';
if ($hash_staff !== '') {
    $_SESSION['hash_staff'] = $hash_staff;
}

// ตรวจสอบรูปแบบ sha512 hex (128 chars) กัน input แปลกๆ
$hash_staff = isset($_SESSION['hash_staff']) ? (string)$_SESSION['hash_staff'] : '';
if ($hash_staff === '' || !preg_match('/^[a-f0-9]{128}$/i', $hash_staff)) {
    header('Location: ../index.php');
    exit;
}

// หา staff ด้วย hash ใน DB (ไม่ต้อง loop ทั้งตาราง)
$stmt = mysqli_prepare(
    $db_person,
    "SELECT code, email
     FROM staff
     WHERE work_status IN ('0','1')
       AND LOWER(SHA2(CONCAT(code, TRIM(LOWER(email))), 512)) = LOWER(?)
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $hash_staff);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_bind_result($stmt, $staff_code, $staff_email);
    mysqli_stmt_fetch($stmt);

    session_regenerate_id(true);

    $_SESSION['r_email'] = $staff_email;
    $_SESSION['code'] = $staff_code;
    $_SESSION['hash_staff'] = $hash_staff;

    $date_now = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $devices = "Person Database";

    // log_login (prepared)
    $stmt_log = mysqli_prepare(
        $db_person,
        "INSERT INTO log_login (staff, ip, dates_login, devices) VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt_log, 'ssss', $staff_code, $ip, $date_now, $devices);
    mysqli_stmt_execute($stmt_log);

    header('Location: page.php');
    exit;
}

// ไม่พบ staff จาก hash
header('Location: ../index.php');
exit;
