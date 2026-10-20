<?php
// endpoint สำหรับ Select2: ค้นหา staff จากฐาน person
// return: JSON array [{code: '...', text: '...'}]

include_once(__DIR__ . '/../../config/config_db.php');

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$debug = isset($_GET['debug']) && (string)$_GET['debug'] === '1';

if ($debug) {
	@error_log('search_staff.php debug=1 q=' . $q);
}

// กัน SQL Injection: ใช้ prepared statement
if ($q === '') {
	$stmt = mysqli_prepare(
		$db_person,
		"SELECT code,
		        CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
		 FROM staff
		 WHERE work_status IN ('0','1')
		 ORDER BY fname ASC, lname ASC, code ASC
		 LIMIT 20"
	);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
} else {
	$like = '%' . $q . '%';

	$stmt = mysqli_prepare(
		$db_person,
		"SELECT code,
		        CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
		 FROM staff
		 WHERE work_status IN ('0','1')
		   AND (
		        code LIKE ?
		        OR fname LIKE ?
		        OR lname LIKE ?
		        OR CONCAT(fname,' ',lname) LIKE ?
		   )
		 ORDER BY fname ASC, lname ASC, code ASC
		 LIMIT 20"
	);

	if (!$stmt) {
		echo json_encode([], JSON_UNESCAPED_UNICODE);
		exit;
	}

	mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
}

$rows = [];
if ($res) {
	while ($r = mysqli_fetch_assoc($res)) {
		$code = (string)($r['code'] ?? '');
		$full = trim((string)($r['full_name'] ?? ''));
		if ($code === '' || $full === '') continue;
		$rows[] = [
			'code' => $code,
		'text' => $full,
		];
	}
}

if ($debug) {
	@error_log('search_staff.php debug=1 rows=' . count($rows));
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE);

