<?php
$allowed = [
    'phar' => ['file' => 'logo-phar-cmu.png', 'type' => 'image/png'],
    'cmu' => ['file' => 'logo-cmu.jpg', 'type' => 'image/jpeg'],
];

$key = isset($_GET['f']) ? (string) $_GET['f'] : '';
if (!isset($allowed[$key])) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . '/img/' . $allowed[$key]['file'];
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $allowed[$key]['type']);
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
