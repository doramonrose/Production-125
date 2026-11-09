<?php
/**
 * Config สำหรับ API ออกเลขที่หนังสือส่วนกลาง (document_number)
 * ค่าลับอ่านจาก getenv หรือ document_number.local.php เท่านั้น — ห้าม commit secret
 */
declare(strict_types=1);

if (!function_exists('pm_document_number_config')) {
	function pm_document_number_config(): array
	{
		static $cfg = null;
		if (is_array($cfg)) {
			return $cfg;
		}

		$local = [];
		$localFile = __DIR__ . '/document_number.local.php';
		if (is_file($localFile)) {
			$loaded = include $localFile;
			if (is_array($loaded)) {
				$local = $loaded;
			}
		}

		$env = static function (string $key, $default = '') use ($local) {
			$v = getenv($key);
			if ($v !== false && trim((string)$v) !== '') {
				return trim((string)$v);
			}
			if (array_key_exists($key, $local) && trim((string)$local[$key]) !== '') {
				return trim((string)$local[$key]);
			}
			$snake = strtolower($key);
			foreach ($local as $lk => $lv) {
				if (strtolower((string)$lk) === $snake && trim((string)$lv) !== '') {
					return trim((string)$lv);
				}
			}
			return is_string($default) ? $default : $default;
		};

		// รายการหน่วยงาน: ใช้ external API (Bearer token แยก) — ถ้าไม่ตั้ง ให้ลองอ่านจาก withdraw .env
		$departmentUrl = $env('DEPARTMENT_API_URL', 'https://mis.pharmacy.cmu.ac.th/document_number/public/api/external.php');
		$departmentToken = $env('DEPARTMENT_API_TOKEN', '');
		if ($departmentToken === '') {
			$withdrawEnv = dirname(__DIR__) . '/withdraw/.env';
			if (is_file($withdrawEnv) && is_readable($withdrawEnv)) {
				$lines = @file($withdrawEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
				foreach ($lines as $line) {
					$line = trim((string)$line);
					if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
						continue;
					}
					[$k, $v] = array_map('trim', explode('=', $line, 2));
					$v = trim($v, " \t\"'");
					if ($k === 'DEPARTMENT_API_URL' && $v !== '') {
						$departmentUrl = $v;
					}
					if ($k === 'DEPARTMENT_API_TOKEN' && $v !== '') {
						$departmentToken = $v;
					}
				}
			}
		}

		$cfg = [
			'api_url' => $env('DOCUMENT_NUMBER_API_URL', 'https://mis.pharmacy.cmu.ac.th/document_number/public/api/issue.php'),
			'token_url' => $env('DOCUMENT_NUMBER_TOKEN_URL', 'https://mis.pharmacy.cmu.ac.th/document_number/public/api/token.php'),
			'client_id' => $env('DOCUMENT_NUMBER_CLIENT_ID', 'mis-project'),
			'client_secret' => $env('DOCUMENT_NUMBER_CLIENT_SECRET', ''),
			'source_app' => $env('DOCUMENT_NUMBER_SOURCE_APP', 'ระบบบริหารโครงการ'),
			'scope' => $env('DOCUMENT_NUMBER_OAUTH_SCOPE', 'documents:read documents:issue'),
			'department_api_url' => $departmentUrl,
			'department_api_token' => $departmentToken,
		];

		return $cfg;
	}
}
