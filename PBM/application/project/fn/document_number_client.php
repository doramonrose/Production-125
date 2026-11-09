<?php
/**
 * Client สำหรับ API ออกเลขที่หนังสือส่วนกลาง (OAuth client_credentials)
 * ใช้เฉพาะ server-side — ไม่ expose secret ไปยังเบราว์เซอร์
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/document_number.php';

if (!function_exists('pm_document_number_http_json')) {
	/**
	 * @param array<string,string> $headers
	 * @param array<string,scalar>|null $formOrJson null=GET, array with _as_json=>true for JSON POST, else form POST
	 * @return array{ok:bool,status:int,data:mixed,raw:string,error:string}
	 */
	function pm_document_number_http_json(string $method, string $url, array $headers = [], ?array $body = null, bool $asJson = false): array
	{
		$ch = curl_init($url);
		if ($ch === false) {
			return ['ok' => false, 'status' => 0, 'data' => null, 'raw' => '', 'error' => 'curl_init failed'];
		}
		$hdrs = [];
		foreach ($headers as $k => $v) {
			$hdrs[] = $k . ': ' . $v;
		}
		$opts = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 20,
			CURLOPT_HTTPHEADER => $hdrs,
			CURLOPT_CUSTOMREQUEST => strtoupper($method),
		];
		if ($body !== null) {
			if ($asJson) {
				$opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
				$hdrs[] = 'Content-Type: application/json';
				$opts[CURLOPT_HTTPHEADER] = $hdrs;
			} else {
				$opts[CURLOPT_POSTFIELDS] = http_build_query($body);
				$hdrs[] = 'Content-Type: application/x-www-form-urlencoded';
				$opts[CURLOPT_HTTPHEADER] = $hdrs;
			}
		}
		curl_setopt_array($ch, $opts);
		$raw = (string)curl_exec($ch);
		$err = curl_error($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);
		$data = json_decode($raw, true);
		return [
			'ok' => $err === '' && $status >= 200 && $status < 300,
			'status' => $status,
			'data' => $data,
			'raw' => $raw,
			'error' => $err,
		];
	}
}

if (!function_exists('pm_document_number_access_token')) {
	function pm_document_number_access_token(bool $forceRefresh = false): string
	{
		static $cached = ['token' => '', 'expires_at' => 0];
		$cfg = pm_document_number_config();
		$clientId = trim((string)($cfg['client_id'] ?? ''));
		$secret = (string)($cfg['client_secret'] ?? '');
		$tokenUrl = trim((string)($cfg['token_url'] ?? ''));
		$scope = trim((string)($cfg['scope'] ?? 'documents:read documents:issue'));
		if ($clientId === '' || $secret === '' || $tokenUrl === '') {
			throw new RuntimeException('ยังไม่ได้ตั้งค่า DOCUMENT_NUMBER client credentials');
		}
		if (!$forceRefresh && $cached['token'] !== '' && $cached['expires_at'] > time() + 30) {
			return $cached['token'];
		}
		$res = pm_document_number_http_json('POST', $tokenUrl, [], [
			'grant_type' => 'client_credentials',
			'client_id' => $clientId,
			'client_secret' => $secret,
			'scope' => $scope,
		], false);
		$payload = is_array($res['data']) ? $res['data'] : [];
		$token = trim((string)($payload['access_token'] ?? ''));
		$expiresIn = (int)($payload['expires_in'] ?? 0);
		if ($token === '' || ($payload['token_type'] ?? '') !== 'Bearer' || $expiresIn < 1) {
			throw new RuntimeException('ขอ access token จากระบบออกเลขไม่สำเร็จ');
		}
		$cached = ['token' => $token, 'expires_at' => time() + $expiresIn];
		return $token;
	}
}

if (!function_exists('pm_document_number_request')) {
	/**
	 * @param array<string,scalar> $query
	 * @param array<string,scalar>|null $jsonBody
	 * @return array<string,mixed>
	 */
	function pm_document_number_request(string $method, array $query = [], ?array $jsonBody = null, ?string $idempotencyKey = null): array
	{
		$cfg = pm_document_number_config();
		$url = trim((string)($cfg['api_url'] ?? ''));
		if ($url === '' || parse_url($url, PHP_URL_SCHEME) !== 'https') {
			throw new RuntimeException('DOCUMENT_NUMBER_API_URL ไม่ถูกต้อง');
		}
		if (!empty($query)) {
			$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
		}
		$token = pm_document_number_access_token(false);
		$headers = [
			'Accept' => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		];
		if ($idempotencyKey !== null && $idempotencyKey !== '') {
			$headers['Idempotency-Key'] = $idempotencyKey;
		}
		if ($jsonBody !== null && (($jsonBody['action'] ?? '') === 'issue')) {
			$jsonBody['source_app'] = (string)($cfg['source_app'] ?? 'ระบบบริหารโครงการ');
		}
		$res = pm_document_number_http_json($method, $url, $headers, $jsonBody, $jsonBody !== null);
		if ($res['status'] === 401) {
			$token = pm_document_number_access_token(true);
			$headers['Authorization'] = 'Bearer ' . $token;
			$res = pm_document_number_http_json($method, $url, $headers, $jsonBody, $jsonBody !== null);
		}
		$payload = is_array($res['data']) ? $res['data'] : [];
		if (!$res['ok'] || ($payload['success'] ?? false) !== true) {
			$msg = trim((string)($payload['message'] ?? ''));
			throw new RuntimeException($msg !== '' ? $msg : 'ระบบออกเลขที่หนังสือตอบกลับไม่สำเร็จ');
		}
		$data = $payload['data'] ?? null;
		return is_array($data) ? $data : [];
	}
}

if (!function_exists('pm_document_number_parse_issue')) {
	/**
	 * @param array<string,mixed> $data
	 * @return array{full_number:string,document_number:string,running_no:int,running_year:int,issue_id:int}
	 */
	function pm_document_number_parse_issue(array $data): array
	{
		$fullNumber = trim((string)($data['full_number'] ?? ''));
		$runningNo = (int)($data['sequence_no'] ?? $data['sequence'] ?? $data['running_no'] ?? 0);
		$centralYear = (int)($data['year_be'] ?? 0);
		$base = trim((string)($data['number_base'] ?? $data['document_number'] ?? ''));
		if ($base === '' && $fullNumber !== '' && $runningNo > 0) {
			$base = (string)preg_replace('/\d+$/u', '', $fullNumber);
		}
		$base = trim($base);
		if ($base !== '' && substr($base, -1) !== '/' && strpos($fullNumber, '/') !== false) {
			// keep as returned
		}
		$issueId = (int)($data['issue_id'] ?? $data['id'] ?? 0);
		if ($fullNumber === '' || $runningNo <= 0 || $centralYear <= 0) {
			throw new RuntimeException('ระบบกลางไม่ออกเลขที่หนังสือ');
		}
		return [
			'full_number' => $fullNumber,
			'document_number' => $base !== '' ? $base : preg_replace('/\d+$/u', '', $fullNumber),
			'running_no' => $runningNo,
			'running_year' => $centralYear >= 2400 ? $centralYear - 543 : $centralYear,
			'issue_id' => $issueId,
		];
	}
}

if (!function_exists('pm_document_number_preview')) {
	/**
	 * @return array{full_number:string,document_number:string,running_no:int,running_year:int,issue_id:int}
	 */
	function pm_document_number_preview(string $unitCode): array
	{
		$unitCode = trim($unitCode);
		if ($unitCode === '') {
			throw new RuntimeException('ไม่พบ unit_code สำหรับดูเลขที่หนังสือ');
		}
		$data = pm_document_number_request('GET', [
			'type' => 'org',
			'code' => $unitCode,
			'preview' => '1',
		], null, null);
		return pm_document_number_parse_issue($data);
	}
}

if (!function_exists('pm_document_number_issue')) {
	/**
	 * @return array{full_number:string,document_number:string,running_no:int,running_year:int,issue_id:int}
	 */
	function pm_document_number_issue(
		string $unitCode,
		string $subject,
		string $requestedBy,
		string $idempotencyKey,
		string $reference = '',
		string $recipient = ''
	): array {
		$unitCode = trim($unitCode);
		$subject = trim($subject);
		$requestedBy = trim($requestedBy);
		$idempotencyKey = trim($idempotencyKey);
		if ($unitCode === '' || $subject === '' || $requestedBy === '' || $idempotencyKey === '') {
			throw new RuntimeException('ข้อมูลสำหรับออกเลขที่หนังสือไม่ครบ');
		}
		$body = [
			'action' => 'issue',
			'type' => 'org',
			'code' => $unitCode,
			'subject' => mb_substr($subject, 0, 255),
			'requested_by' => mb_substr($requestedBy, 0, 255),
		];
		if (trim($recipient) !== '') {
			$body['recipient'] = mb_substr(trim($recipient), 0, 255);
		}
		if (trim($reference) !== '') {
			$body['reference'] = mb_substr(trim($reference), 0, 128);
		}
		$data = pm_document_number_request('POST', [], $body, $idempotencyKey);
		return pm_document_number_parse_issue($data);
	}
}

if (!function_exists('pm_document_number_list_org_options')) {
	/**
	 * รายการหน่วยงานจาก API กลาง สำหรับเลือก unit_code
	 * @return list<array{code:string,label:string,document_number:string}>
	 */
	function pm_document_number_list_org_options(): array
	{
		$cfg = pm_document_number_config();
		$url = trim((string)($cfg['department_api_url'] ?? ''));
		$token = trim((string)($cfg['department_api_token'] ?? ''));
		if ($url === '' || $token === '') {
			return [];
		}
		$res = pm_document_number_http_json('GET', $url, [
			'Accept' => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		], null, false);
		$payload = is_array($res['data']) ? $res['data'] : [];
		$rows = $payload['data'] ?? null;
		if (!is_array($rows)) {
			return [];
		}
		$out = [];
		$seen = [];
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$depCode = trim((string)($row['dep_code'] ?? ''));
			$depName = trim((string)($row['dep_name'] ?? ''));
			$jobCode = trim((string)($row['job_code'] ?? ''));
			$jobName = trim((string)($row['job_name'] ?? ''));
			$unitCode = trim((string)($row['unit_code'] ?? ''));
			$agencyName = trim((string)($row['agency_name'] ?? ''));
			$depDoc = trim((string)($row['dep_document_number'] ?? ''));
			$jobDoc = trim((string)($row['job_document_number'] ?? ''));
			$unitDoc = trim((string)($row['document_number'] ?? ''));

			$candidates = [];
			if ($depCode !== '' && $depName !== '') {
				$candidates[] = [
					'code' => $depCode,
					'label' => $depName,
					'document_number' => $depDoc,
				];
			}
			if ($jobCode !== '' && $jobName !== '') {
				$label = $depName !== '' ? ($depName . ' > ' . $jobName) : $jobName;
				$candidates[] = [
					'code' => $jobCode,
					'label' => $label,
					'document_number' => $jobDoc !== '' ? $jobDoc : $depDoc,
				];
			}
			if ($agencyName !== '') {
				$code = $unitCode !== '' ? $unitCode : ($jobCode !== '' ? $jobCode : $depCode);
				if ($code !== '') {
					$label = $depName;
					if ($jobName !== '') {
						$label .= ($label !== '' ? ' > ' : '') . $jobName;
					}
					$label .= ($label !== '' ? ' > ' : '') . $agencyName;
					$candidates[] = [
						'code' => $code,
						'label' => $label,
						'document_number' => $unitDoc !== '' ? $unitDoc : ($jobDoc !== '' ? $jobDoc : $depDoc),
					];
				}
			} elseif ($unitCode !== '') {
				$candidates[] = [
					'code' => $unitCode,
					'label' => $agencyName !== '' ? $agencyName : ($jobName !== '' ? $jobName : $depName),
					'document_number' => $unitDoc !== '' ? $unitDoc : ($jobDoc !== '' ? $jobDoc : $depDoc),
				];
			}

			foreach ($candidates as $c) {
				$code = trim((string)$c['code']);
				$label = trim((string)$c['label']);
				if ($code === '' || $label === '' || isset($seen[$code])) {
					continue;
				}
				$seen[$code] = true;
				$out[] = [
					'code' => $code,
					'label' => $label,
					'document_number' => trim((string)$c['document_number']),
				];
			}
		}
		usort($out, static function ($a, $b) {
			return strcmp($a['label'], $b['label']);
		});
		return $out;
	}
}
