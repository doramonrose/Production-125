<?php
/**
 * CMU Account (Microsoft Entra ID) สำหรับหน้า login ทดสอบระบบโครงการ
 * ไม่ยุ่งกับ index.php / page_oauth.php ของพอร์ทัลเดิม
 */

function pm_oauth_auto_redirect_uri(): string
{
	$host = pm_oauth_request_hostname();
	$sslPort = pm_oauth_env('NGINX_SSL_PORT', '10443');
	$dir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/project/oauth-cmu.php')));
	$dir = rtrim($dir, '/');
	$origin = ($sslPort === '' || $sslPort === '443')
		? ('https://' . $host)
		: ('https://' . $host . ':' . $sslPort);
	return $origin . $dir . '/oauth-cmu.php';
}

function pm_oauth_http_origin(?string $host = null): string
{
	$host = $host !== null && $host !== '' ? $host : pm_oauth_request_hostname();
	$httpPort = pm_oauth_env('NGINX_PORT', '10011');
	if ($httpPort === '' || $httpPort === '80') {
		return 'http://' . $host;
	}
	return 'http://' . $host . ':' . $httpPort;
}

function pm_oauth_request_hostname(): string
{
	$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
	if (strpos($host, ':') !== false) {
		$host = explode(':', $host, 2)[0];
	}
	return $host !== '' ? $host : 'localhost';
}

function pm_oauth_env(string $key, string $default = ''): string
{
	$val = getenv($key);
	if ($val !== false && trim((string)$val) !== '') {
		return trim((string)$val);
	}
	$paths = [
		'/var/www/.env',
		dirname(__DIR__, 3) . '/.env',
	];
	foreach ($paths as $envFile) {
		if (!is_file($envFile)) {
			continue;
		}
		$lines = @file($envFile, FILE_IGNORE_NEW_LINES);
		if (!is_array($lines)) {
			continue;
		}
		foreach ($lines as $line) {
			$line = trim((string)$line);
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			if (strpos($line, '=') === false) {
				continue;
			}
			[$k, $v] = explode('=', $line, 2);
			if (trim($k) !== $key) {
				continue;
			}
			$v = trim($v);
			if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
				$q = $v[0];
				if (strlen($v) >= 2 && substr($v, -1) === $q) {
					$v = substr($v, 1, -1);
				}
			}
			return $v;
		}
	}
	return $default;
}

function pm_oauth_b64url_encode(string $raw): string
{
	return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

function pm_oauth_b64url_decode(string $data): string
{
	$pad = strlen($data) % 4;
	if ($pad > 0) {
		$data .= str_repeat('=', 4 - $pad);
	}
	$out = base64_decode(strtr($data, '-_', '+/'), true);
	return is_string($out) ? $out : '';
}

/** @param array<string, mixed> $payload */
function pm_oauth_sign_state(array $payload, array $cfg): string
{
	$json = json_encode($payload, JSON_UNESCAPED_SLASHES);
	if (!is_string($json) || $json === '') {
		$json = '{}';
	}
	$body = pm_oauth_b64url_encode($json);
	$sig = hash_hmac('sha256', $body, (string)$cfg['client_secret']);
	return 'pm1.' . $body . '.' . $sig;
}

/** @return array<string, mixed>|null */
function pm_oauth_verify_state(string $state, array $cfg)
{
	$parts = explode('.', $state, 3);
	if (count($parts) !== 3 || $parts[0] !== 'pm1') {
		return null;
	}
	$expect = hash_hmac('sha256', $parts[1], (string)$cfg['client_secret']);
	if (!hash_equals($expect, $parts[2])) {
		return null;
	}
	$json = pm_oauth_b64url_decode($parts[1]);
	$data = json_decode($json, true);
	if (!is_array($data)) {
		return null;
	}
	$ts = (int)($data['t'] ?? 0);
	if ($ts <= 0 || abs(time() - $ts) > 600) {
		return null;
	}
	return $data;
}

function pm_oauth_ticket_put(array $payload): string
{
	$id = bin2hex(random_bytes(16));
	$path = rtrim(sys_get_temp_dir(), '/') . '/pm-oauth-' . $id . '.json';
	$payload['exp'] = time() + 180;
	$ok = @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	if ($ok === false) {
		return '';
	}
	@chmod($path, 0600);
	return $id;
}

/** @return array<string, mixed>|null */
function pm_oauth_ticket_take(string $id)
{
	if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
		return null;
	}
	$path = rtrim(sys_get_temp_dir(), '/') . '/pm-oauth-' . $id . '.json';
	$raw = @file_get_contents($path);
	@unlink($path);
	if (!is_string($raw) || $raw === '') {
		return null;
	}
	$data = json_decode($raw, true);
	if (!is_array($data)) {
		return null;
	}
	if ((int)($data['exp'] ?? 0) < time()) {
		return null;
	}
	return $data;
}

/** @return array<string, string> */
function pm_oauth_config(): array
{
	$cfg = [
		'client_id' => 'YOUR_CLIENT_ID',
		'client_secret' => 'YOUR_CLIENT_SECRET',
		'redirect_uri' => '',
		'scope' => 'api://cmu/Mis.Account.Read.Me.Basicinfo offline_access',
		'auth_url' => 'https://login.microsoftonline.com/cf81f1df-de59-4c29-91da-a2dfd04aa751/oauth2/v2.0/authorize',
		'token_url' => 'https://login.microsoftonline.com/cf81f1df-de59-4c29-91da-a2dfd04aa751/oauth2/v2.0/token',
		'basicinfo_url' => 'https://api.cmu.ac.th/mis/cmuaccount/prod/v3/me/basicinfo',
		'plan_job_code' => '120103',
	];

	$kpiLocal = dirname(__DIR__, 2) . '/KPI/config/kpi_oauth.local.php';
	if (is_file($kpiLocal)) {
		$kpi = include $kpiLocal;
		if (is_array($kpi)) {
			if (!empty($kpi['client_id'])) {
				$cfg['client_id'] = (string)$kpi['client_id'];
			}
			if (!empty($kpi['client_secret'])) {
				$cfg['client_secret'] = (string)$kpi['client_secret'];
			}
		}
	}

	$local = dirname(__DIR__) . '/config/oauth.local.php';
	if (is_file($local)) {
		$own = include $local;
		if (is_array($own)) {
			$cfg = array_merge($cfg, $own);
		}
	}

	foreach ([
		'client_id' => 'YOUR_CLIENT_ID',
		'client_secret' => 'YOUR_CLIENT_SECRET',
		'redirect_uri' => 'PM_OAUTH_REDIRECT_URI',
	] as $key => $envKey) {
		$val = getenv($envKey);
		if ($val !== false && $val !== '') {
			$cfg[$key] = $val;
		}
	}

	if (trim((string)$cfg['redirect_uri']) === '') {
		$cfg['redirect_uri'] = pm_oauth_auto_redirect_uri();
	}

	return $cfg;
}

/** @return array<string, string> */
function pm_oauth_require_config(): array
{
	$cfg = pm_oauth_config();
	if (trim((string)$cfg['client_id']) === '' || trim((string)$cfg['client_secret']) === '') {
		header('Location: login.php?err=config');
		exit;
	}
	return $cfg;
}

function pm_oauth_is_redirect_mismatch(string $message): bool
{
	$m = strtolower($message);
	return (strpos($m, 'aadsts50011') !== false)
		|| (strpos($m, 'redirect_uri') !== false)
		|| (strpos($m, 'reply url') !== false);
}

/** @param array<int, string> $candidates */
function pm_oauth_lookup_staff($db, array $candidates)
{
	if (!($db instanceof mysqli)) {
		return null;
	}
	$cands = [];
	foreach ($candidates as $em) {
		$em = strtolower(trim((string)$em));
		if ($em !== '' && !in_array($em, $cands, true)) {
			$cands[] = $em;
		}
	}
	if ($cands === []) {
		return null;
	}
	$sqls = [
		'SELECT code, email, username, password, hash_pw, job_code FROM staff WHERE LOWER(TRIM(email)) = ? AND work_status IN (?, ?) LIMIT 1',
		'SELECT code, email, username, password, hash_pw, job_code FROM staff WHERE LOWER(TRIM(username)) = ? AND work_status IN (?, ?) LIMIT 1',
	];
	$work0 = '0';
	$work1 = '1';
	foreach ($cands as $em) {
		foreach ($sqls as $sql) {
			$stmt = mysqli_prepare($db, $sql);
			if (!$stmt) {
				continue;
			}
			mysqli_stmt_bind_param($stmt, 'sss', $em, $work0, $work1);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_store_result($stmt);
			if (mysqli_stmt_num_rows($stmt) > 0) {
				mysqli_stmt_bind_result($stmt, $code, $email, $username, $password, $hashPw, $jobCode);
				mysqli_stmt_fetch($stmt);
				mysqli_stmt_close($stmt);
				return [
					'code' => (string)$code,
					'email' => (string)$email,
					'username' => (string)$username,
					'password' => (string)$password,
					'hash_pw' => (string)$hashPw,
					'job_code' => (string)$jobCode,
				];
			}
			mysqli_stmt_close($stmt);
		}
	}
	return null;
}

function pm_oauth_bind_staff_session(array $row, array $cfg): string
{
	session_regenerate_id(true);
	$email = (string)$row['email'];
	$code = (string)$row['code'];
	$_SESSION['r_email'] = $email;
	$_SESSION['code'] = $code;
	$_SESSION['r_username'] = (string)($row['username'] ?? '');
	$_SESSION['r_password'] = (string)($row['password'] ?? '');
	$_SESSION['r_code'] = $code;
	$_SESSION['r_hash'] = (string)($row['hash_pw'] ?? '');
	$_SESSION['type'] = 'STAFF';
	$_SESSION['hash_staff'] = hash('sha512', $code . strtolower(trim($email)));
	$_SESSION['pm_login_via'] = 'cmu';
	unset($_SESSION['hash_std']);

	$planJob = trim((string)($cfg['plan_job_code'] ?? '120103'));
	$isPlan = (trim((string)($row['job_code'] ?? '')) === $planJob);
	$_SESSION['pm_is_plan'] = $isPlan ? 1 : 0;
	return $isPlan ? 'Plan/page.php' : 'User/page.php';
}

function pm_oauth_get_token(string $code, array $cfg): string
{
	$post = [
		'code' => $code,
		'client_id' => $cfg['client_id'],
		'client_secret' => $cfg['client_secret'],
		'redirect_uri' => $cfg['redirect_uri'],
		'grant_type' => 'authorization_code',
	];
	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $cfg['token_url'],
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $post,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_SSL_VERIFYPEER => false,
	]);
	$raw = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err || !is_string($raw) || $raw === '') {
		return '';
	}
	$obj = json_decode($raw);
	return (isset($obj->access_token) && is_string($obj->access_token)) ? $obj->access_token : '';
}

/** @return array<string, mixed>|null */
function pm_oauth_get_basicinfo(string $accessToken, array $cfg)
{
	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $cfg['basicinfo_url'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTPHEADER => [
			'Authorization: Bearer ' . $accessToken,
			'Cache-Control: no-cache',
		],
	]);
	$raw = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err || !is_string($raw) || $raw === '') {
		return null;
	}
	$json = json_decode($raw, true);
	return is_array($json) ? $json : null;
}
