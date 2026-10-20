<?php
ob_start();
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_start();
require_once __DIR__ . '/fn/pm_oauth.php';

$cfg = pm_oauth_require_config();
$httpOrigin = pm_oauth_http_origin();

$fail = static function (string $err, string $hint = '', ?string $retOrigin = null) use ($cfg, $httpOrigin): void {
	$origin = ($retOrigin !== null && $retOrigin !== '') ? rtrim($retOrigin, '/') : $httpOrigin;
	$_SESSION['pm_oauth_err'] = $hint;
	$_SESSION['pm_oauth_redirect_uri'] = (string)($cfg['redirect_uri'] ?? '');
	header('Location: ' . $origin . '/project/login.php?err=' . rawurlencode($err));
	exit;
};

$ticket = isset($_GET['ticket']) ? trim((string)$_GET['ticket']) : '';
if ($ticket !== '') {
	$data = pm_oauth_ticket_take($ticket);
	if (!is_array($data)) {
		$fail('oauth', 'ticket');
	}
	try {
		require dirname(__DIR__) . '/config/config_db.php';
	} catch (Throwable $e) {
		$fail('db');
	}
	if (!isset($db_person) || !($db_person instanceof mysqli)) {
		$fail('db');
	}
	$code = trim((string)($data['code'] ?? ''));
	$email = trim((string)($data['email'] ?? ''));
	$row = pm_oauth_lookup_staff($db_person, [$email, $code]);
	if (!$row && $code !== '') {
		$stmt = mysqli_prepare($db_person, 'SELECT code, email, username, password, hash_pw, job_code FROM staff WHERE code = ? AND work_status IN (?, ?) LIMIT 1');
		if ($stmt) {
			$w0 = '0';
			$w1 = '1';
			mysqli_stmt_bind_param($stmt, 'sss', $code, $w0, $w1);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_store_result($stmt);
			if (mysqli_stmt_num_rows($stmt) > 0) {
				mysqli_stmt_bind_result($stmt, $c, $em, $un, $pw, $hp, $jc);
				mysqli_stmt_fetch($stmt);
				$row = [
					'code' => (string)$c,
					'email' => (string)$em,
					'username' => (string)$un,
					'password' => (string)$pw,
					'hash_pw' => (string)$hp,
					'job_code' => (string)$jc,
				];
			}
			mysqli_stmt_close($stmt);
		}
	}
	if (!$row) {
		$fail('noperm');
	}
	$_SESSION['cmuitaccount'] = (string)($data['cmuitaccount'] ?? $row['email']);
	$_SESSION['emailCMU'] = (string)($data['emailCMU'] ?? $row['email']);
	$_SESSION['prefix'] = (string)($data['prefix'] ?? '');
	$_SESSION['firstname'] = (string)($data['firstname'] ?? '');
	$_SESSION['lastname'] = (string)($data['lastname'] ?? '');
	$dest = pm_oauth_bind_staff_session($row, $cfg);
	$dateNow = date('Y-m-d H:i:s');
	$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
	$devices = 'CMU-ACCOUNT-TEST';
	$stmtLog = mysqli_prepare($db_person, 'INSERT INTO log_login (staff, ip, dates_login, devices) VALUES (?, ?, ?, ?)');
	if ($stmtLog) {
		$staffCode = (string)$row['code'];
		mysqli_stmt_bind_param($stmtLog, 'ssss', $staffCode, $ip, $dateNow, $devices);
		mysqli_stmt_execute($stmtLog);
		mysqli_stmt_close($stmtLog);
	}
	header('Location: ' . $dest);
	exit;
}

$statePayload = null;
if (!empty($_GET['state'])) {
	$statePayload = pm_oauth_verify_state((string)$_GET['state'], $cfg);
	if (is_array($statePayload) && !empty($statePayload['ret'])) {
		$httpOrigin = rtrim((string)$statePayload['ret'], '/');
	}
}

if (!empty($_GET['error'])) {
	$desc = trim((string)($_GET['error_description'] ?? $_GET['error'] ?? ''));
	$kind = pm_oauth_is_redirect_mismatch($desc) ? 'redirect' : 'oauth';
	$fail($kind, $desc, $httpOrigin);
}

if (empty($_GET['code'])) {
	$state = pm_oauth_sign_state([
		'n' => bin2hex(random_bytes(8)),
		't' => time(),
		'ret' => pm_oauth_http_origin(),
	], $cfg);
	$auth = $cfg['auth_url']
		. '?response_type=code'
		. '&client_id='YOUR_SECRET_VALUE'])
		. '&redirect_uri=' . rawurlencode($cfg['redirect_uri'])
		. '&scope=' . rawurlencode($cfg['scope'])
		. '&state=' . rawurlencode($state)
		. '&prompt=select_account';
	header('Location: ' . $auth);
	exit;
}

if (!is_array($statePayload)) {
	$fail('oauth', 'state mismatch', $httpOrigin);
}

$accessToken = pm_oauth_get_token((string)$_GET['code'], $cfg);
if ($accessToken === '') {
	$fail('oauth', 'token', $httpOrigin);
}

$info = pm_oauth_get_basicinfo($accessToken, $cfg);
if (!is_array($info)) {
	$fail('oauth', 'basicinfo', $httpOrigin);
}

$cmuit = trim((string)($info['cmuitaccount'] ?? ''));
$emailCmu = trim((string)($info['emailCMU'] ?? ''));
if ($cmuit === '' && $emailCmu === '') {
	$fail('oauth', 'no account', $httpOrigin);
}

try {
	require dirname(__DIR__) . '/config/config_db.php';
} catch (Throwable $e) {
	$fail('db', '', $httpOrigin);
}

if (!isset($db_person) || !($db_person instanceof mysqli)) {
	$fail('db', '', $httpOrigin);
}

$row = pm_oauth_lookup_staff($db_person, [$emailCmu, $cmuit]);
if (!$row) {
	$fail('noperm', '', $httpOrigin);
}

$ticketId = pm_oauth_ticket_put([
	'code' => (string)$row['code'],
	'email' => (string)$row['email'],
	'cmuitaccount' => $cmuit !== '' ? $cmuit : $emailCmu,
	'emailCMU' => $emailCmu !== '' ? $emailCmu : $cmuit,
	'prefix' => (string)($info['prename_TH'] ?? ''),
	'firstname' => (string)($info['firstname_TH'] ?? ''),
	'lastname' => (string)($info['lastname_TH'] ?? ''),
]);
if ($ticketId === '') {
	$fail('oauth', 'ticket write', $httpOrigin);
}

header('Location: ' . $httpOrigin . '/project/oauth-cmu.php?ticket=' . rawurlencode($ticketId));
exit;
