<?php
$err = isset($_GET['err']) ? trim((string) $_GET['err']) : '';
$error = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$messages = [
    'config' => 'ยังตั้งค่าการเข้าสู่ระบบไม่ครบ',
    'oauth' => 'เข้าสู่ระบบด้วย CMU ไม่สำเร็จ',
    'db' => 'เชื่อมต่อฐานข้อมูลไม่ได้',
    'noperm' => 'ไม่พบบัญชีนี้ในระบบ หรือไม่มีสิทธิ์เข้าใช้งาน',
];
if ($error === '' && $err !== '') {
    $error = $messages[$err] ?? $err;
}
$target = 'index.php';
if ($error !== '') {
    $target .= '?error=' . rawurlencode($error);
}
header('Location: ' . $target);
exit;
