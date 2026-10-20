<?php
/**
 * ดาวน์โหลดบันทึกอนุมัติโครงการ ด้วย mPDF (TH Sarabun)
 *
 * โหมดปกติ: ต้อง login session + ส่งค่าจากฟอร์ม
 * โหมด API: ตั้ง $PM_APPROVE_MEMO_API = true ก่อน include (หรือเรียกผ่าน api_approve_memo_pdf.php)
 */
ob_start();
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once __DIR__ . '/../../mpdf/vendor/autoload.php';
include_once __DIR__ . '/../../config/config_db.php';
include_once __DIR__ . '/../fn/main_staff.php';
include_once __DIR__ . '/../fn/main_project.php';

$isApiMode = !empty($PM_APPROVE_MEMO_API);
$apiDisposition = isset($PM_APPROVE_MEMO_DISPOSITION)
	? strtolower(trim((string)$PM_APPROVE_MEMO_DISPOSITION))
	: 'download';
if (!in_array($apiDisposition, ['inline', 'download', 'attachment'], true)) {
	$apiDisposition = 'download';
}
if ($apiDisposition === 'attachment') {
	$apiDisposition = 'download';
}

if (!$isApiMode) {
	$sess_email = (string)($_SESSION['r_email'] ?? '');
	$sess_code = (string)($_SESSION['code'] ?? '');
	if ($sess_email === '' || $sess_code === '' || !isset($db_person) || !($db_person instanceof mysqli)) {
		http_response_code(403);
		ob_end_clean();
		exit('ไม่มีสิทธิ์เข้าถึง');
	}

	$stMe = mysqli_prepare($db_person, 'SELECT id,code FROM staff WHERE email = ? AND code = ? LIMIT 1');
	mysqli_stmt_bind_param($stMe, 'ss', $sess_email, $sess_code);
	mysqli_stmt_execute($stMe);
	$resMe = mysqli_stmt_get_result($stMe);
	$me = $resMe ? mysqli_fetch_assoc($resMe) : null;
	if (!$me) {
		http_response_code(403);
		ob_end_clean();
		exit('ไม่พบผู้ใช้');
	}
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : (isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0);
if ($project_id <= 0 && !empty($PM_APPROVE_MEMO_PROJECT_ID)) {
	$project_id = (int)$PM_APPROVE_MEMO_PROJECT_ID;
}
if ($project_id <= 0 || !isset($db_Project) || !($db_Project instanceof mysqli)) {
	http_response_code(400);
	ob_end_clean();
	exit('ไม่พบโครงการ');
}

$qPre = @mysqli_query($db_Project, "SELECT * FROM pre_project WHERE id='" . $project_id . "' AND IFNULL(status,'') NOT IN ('99','99.0') LIMIT 1");
$pre = $qPre ? mysqli_fetch_assoc($qPre) : null;
if (!$pre) {
	http_response_code(404);
	ob_end_clean();
	exit('ไม่พบโครงการ');
}

$memo_type = isset($_POST['memo_type']) ? trim((string)$_POST['memo_type']) : (isset($_GET['memo_type']) ? trim((string)$_GET['memo_type']) : '');
if ($memo_type === '' && !empty($PM_APPROVE_MEMO_DATA['memo_type'])) {
	$memo_type = trim((string)$PM_APPROVE_MEMO_DATA['memo_type']);
}
if ($memo_type !== 'change') {
	$memo_type = 'operate';
}
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : (isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0);
$chgReq = null;

if ($memo_type === 'change') {
	if (function_exists('pm_ensure_change_request_schema')) {
		pm_ensure_change_request_schema($db_Project);
	}
	if ($request_id <= 0) {
		http_response_code(400);
		ob_end_clean();
		exit('ไม่พบคำขอเปลี่ยนแปลง');
	}
	$qReq = @mysqli_query(
		$db_Project,
		"SELECT * FROM project_change_request
		 WHERE id='" . $request_id . "' AND project_id='" . $project_id . "' AND status='1'
		 LIMIT 1"
	);
	$chgReq = $qReq ? mysqli_fetch_assoc($qReq) : null;
	if (!$chgReq || (string)($chgReq['req_status'] ?? '') !== 'approved') {
		http_response_code(403);
		ob_end_clean();
		exit('คำขอเปลี่ยนแปลงยังไม่ได้รับการอนุมัติ จึงยังออกบันทึกข้อความไม่ได้');
	}
} else {
	if (function_exists('pm_ensure_operate_approver_schema')) {
		pm_ensure_operate_approver_schema($db_Project);
	}
	$ready = function_exists('pm_is_operate_final_approved')
		? pm_is_operate_final_approved($db_Project, $project_id, $pre)
		: false;
	if (!$ready) {
		http_response_code(403);
		ob_end_clean();
		exit('โครงการยังไม่พร้อมออกบันทึกอนุมัติ');
	}
}

$h = static function ($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$td18 = static function ($html, $extra = '') {
	$extra = trim((string)$extra);
	return '<td' . ($extra !== '' ? ' ' . $extra : '') . ' style="border:0.5pt solid #000; padding:5px 8px; vertical-align:top; overflow: wrap;">'
		. '<div style="font-family:sarabun; font-size:9pt; font-weight:normal; line-height:1.45;">' . $html . '</div>'
		. '</td>';
};
$pdfBudgetSrcCell = static function ($s) use ($h, $td18) {
	$s = trim((string)$s);
	if ($s === '') {
		$s = '-';
	}
	$s = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{25A0}-\x{25A3}\x{25A8}\x{25AB}\x{25FB}-\x{25FE}\x{2610}-\x{2612}\x{2B1A}\x{2B1C}\x{FFED}]/u', '', $s);
	$s = str_replace(['☐', '□', '▢', '■', '▪', '▫', '☑', '☒'], '', $s);
	$s = preg_replace('/[ \t]+/u', ' ', (string)$s);
	$s = preg_replace('#\s*/\s*#u', ' / ', (string)$s);
	$s = trim((string)$s);
	return $td18($h($s !== '' ? $s : '-'));
};

$post = static function ($key, $default = '') use ($isApiMode) {
	if (isset($_POST[$key])) {
		return trim((string)$_POST[$key]);
	}
	if ($isApiMode && !empty($GLOBALS['PM_APPROVE_MEMO_DATA']) && is_array($GLOBALS['PM_APPROVE_MEMO_DATA']) && array_key_exists($key, $GLOBALS['PM_APPROVE_MEMO_DATA'])) {
		return trim((string)$GLOBALS['PM_APPROVE_MEMO_DATA'][$key]);
	}
	return $default;
};

// โหมด API: ถ้ายังไม่มีชุดข้อมูล ให้สร้างจาก DB
if ($isApiMode && $memo_type === 'operate' && (empty($PM_APPROVE_MEMO_DATA) || !is_array($PM_APPROVE_MEMO_DATA))) {
	$built = function_exists('pm_build_approve_memo_payload_from_db')
		? pm_build_approve_memo_payload_from_db($db_Project, $db_person ?? null, $project_id, $pre)
		: ['ok' => 0, 'msg' => 'ไม่รองรับ'];
	if (empty($built['ok']) || empty($built['data']) || !is_array($built['data'])) {
		http_response_code(403);
		ob_end_clean();
		exit((string)($built['msg'] ?? 'สร้างข้อมูลบันทึกอนุมัติไม่สำเร็จ'));
	}
	$PM_APPROVE_MEMO_DATA = $built['data'];
	$GLOBALS['PM_APPROVE_MEMO_DATA'] = $PM_APPROVE_MEMO_DATA;
}

$dept = $post('dept');
$doc_no = $post('doc_no');
$date = $post('date');
$unit = $post('unit');
$code = $post('code');
$name = $post('name');
$budget = $post('budget');
$budget_src = $post('budget_src');
$proposer_name = $post('proposer_name');
$proposer_pos = $post('proposer_pos');
$proposer_date = $post('proposer_date');
$proposer_sig = $post('proposer_sig');
$approver_name = $post('approver_name');
$approver_pos = $post('approver_pos');
$approver_date = $post('approver_date');
$approver_sig = $post('approver_sig');
$year = $post('year');
$reason = $post('reason');
$details_json = $post('details_json', '[]');

// fallback จาก DB ถ้าไม่ได้ส่งค่าจากฟอร์ม
if ($name === '' && is_array($chgReq)) {
	$name = trim((string)($chgReq['project_name'] ?? ''));
}
if ($name === '') {
	$name = trim((string)($pre['name_project'] ?? ''));
}
if ($code === '' && is_array($chgReq)) {
	$code = trim((string)($chgReq['project_code'] ?? ''));
}
if ($code === '') {
	$code = trim((string)($pre['code_project'] ?? $pre['ref_code'] ?? $pre['code_pre_project'] ?? ''));
	if ($code === '') {
		$code = 'P' . $project_id;
	}
}
if ($year === '') {
	$year = trim((string)($pre['year_project'] ?? ''));
	if ($year !== '' && ctype_digit($year) && (int)$year > 0 && (int)$year < 2400) {
		$year = (string)((int)$year + 543);
	}
}
if ($reason === '' && is_array($chgReq)) {
	$reason = trim((string)($chgReq['reason'] ?? ''));
}
if ($budget === '' && is_array($chgReq) && (float)($chgReq['budget_amount'] ?? 0) > 0) {
	$budget = number_format((float)$chgReq['budget_amount'], 0, '.', ',');
}
if ($budget_src === '' && is_array($chgReq)) {
	$budget_src = trim((string)($chgReq['budget_source'] ?? ''));
}
$details = json_decode($details_json, true);
if (!is_array($details) || empty($details)) {
	$rawDet = is_array($chgReq) ? json_decode((string)($chgReq['details_json'] ?? ''), true) : null;
	$details = is_array($rawDet) ? $rawDet : [];
}

$krutPath = __DIR__ . '/../../image/krut.jpeg';
if (!is_file($krutPath)) {
	$krutPath = __DIR__ . '/../../image/krut.png';
}
$krutSrc = is_file($krutPath) ? $krutPath : '';

$fetchImgLocal = static function ($url) {
	$url = trim((string)$url);
	if ($url === '') {
		return '';
	}
	if (preg_match('#^data:image/#i', $url)) {
		return $url;
	}
	$data = null;
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 12,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_USERAGENT => 'Mozilla/5.0',
		]);
		$data = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($code >= 400) {
			$data = false;
		}
	}
	if ($data === false || $data === null || $data === '') {
		$data = @file_get_contents($url);
	}
	if ($data === false || $data === '') {
		return '';
	}
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime = $finfo->buffer($data) ?: 'image/png';
	if (strpos($mime, 'image/') !== 0) {
		return '';
	}
	return 'data:' . $mime . ';base64,' . base64_encode($data);
};

$proposerSigImg = $fetchImgLocal($proposer_sig);
$approverSigImg = $fetchImgLocal($approver_sig);

$unitShow = $unit !== '' ? $unit : 'หน่วยงาน';
if ($dept === '') {
	$dept = $unitShow . ' คณะเภสัชศาสตร์ โทร.44344';
}
if ($doc_no === '') {
	$doc_no = function_exists('pm_unit_paper_doc_no') ? pm_unit_paper_doc_no('อว', 'ผ001') : 'อว/ผ001';
}
$doc_no = preg_replace('#\s*/+\s*#', '/', trim((string)$doc_no));
if ($date === '') {
	$date = '....................';
}
if ($proposer_pos === '') {
	$proposer_pos = '........................';
}
if ($approver_pos === '') {
	$approver_pos = '........................';
}

$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'project_approve_memo';
if (!is_dir($tempDir)) {
	@mkdir($tempDir, 0775, true);
}

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];
// ขอบกระดาษแบบหนังสือราชการ (มม.) — ซ้าย ~3 ซม. ขวา ~2 ซม.
$mpdf = new \Mpdf\Mpdf([
	'mode' => 'utf-8',
	'format' => 'A4',
	'margin_left' => 30,
	'margin_right' => 20,
	'margin_top' => 15,
	'margin_bottom' => 24,
	'tempDir' => $tempDir,
	'default_font' => 'sarabun',
	'default_font_size' => 10,
	'fontdata' => $fontData + [
		'sarabun' => [
			'R' => 'THSarabunNew.ttf',
			'I' => 'THSarabunNewItalic.ttf',
			'B' => 'THSarabunNewBold.ttf',
			'BI' => 'THSarabunNewBoldItalic.ttf',
		],
	],
]);

$esignRef = '';
if (function_exists('pm_get_or_create_approve_memo_esign_ref')) {
	$esignRef = pm_get_or_create_approve_memo_esign_ref($db_Project, $project_id, (string)$code, $memo_type, (int)$request_id);
}
$esignFooter = function_exists('pm_approve_memo_esign_footer_text')
	? pm_approve_memo_esign_footer_text($esignRef)
	: '';

$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit = 1;
$mpdf->keep_table_proportions = true;
if ($esignFooter !== '') {
	$mpdf->SetHTMLFooter(
		'<div style="font-family:sarabun;font-size:7.5pt;line-height:1.3;color:#333;text-align:left;">'
		. htmlspecialchars($esignFooter, ENT_QUOTES, 'UTF-8')
		. '</div>'
	);
}
$mpdf->simpleTables = false;
$mpdf->SetTitle(($memo_type === 'change' ? 'บันทึกขออนุมัติเปลี่ยนแปลงโครงการ ' : 'บันทึกอนุมัติโครงการ ') . $code);

$detailsRowsHtml = '';
$di = 1;
foreach ($details as $drow) {
	if (!is_array($drow)) {
		continue;
	}
	$orig = trim((string)($drow['original'] ?? ''));
	$chg = trim((string)($drow['changed'] ?? ''));
	$detailsRowsHtml .= '<tr>' . $td18((string)$di, 'width="40" align="center"') . $td18(nl2br($h($orig !== '' ? $orig : '-'))) . $td18(nl2br($h($chg !== '' ? $chg : '-'))) . '</tr>';
	$di++;
}
if ($detailsRowsHtml === '') {
	$detailsRowsHtml = '<tr>' . $td18('1', 'width="40" align="center"') . $td18('-') . $td18('-') . '</tr>';
}

$signHtmlChange = '
<table class="signs" width="100%">
<tr>
	<td class="sign-head">เสนอเรื่องเพื่อพิจารณา</td>
	<td width="290">
		<div class="banner">Digitally signed by</div>
		<div class="sign-box">
			<table class="sig-inner" width="100%"><tr>
				<td width="48%">' .
					($proposerSigImg !== ''
						? '<img class="sig-img" src="' . $h($proposerSigImg) . '" alt="" />'
						: '<span style="color:#15803d;font-style:italic;font-size:10pt;">Digitally signed by</span>') .
				'</td>
				<td width="52%">
					<div class="meta-line">' . $h($proposer_name !== '' ? $proposer_name : '........................') . '</div>
					<div class="meta-line">' . $h($proposer_pos) . '</div>
					<div class="meta-line">' . $h($proposer_date !== '' ? $proposer_date : '....................') . '</div>
				</td>
			</tr></table>
		</div>
	</td>
</tr>
<tr><td colspan="2"><div class="hr"></div></td></tr>
<tr>
	<td class="sign-head">เพื่อพิจารณาอนุมัติ</td>
	<td width="290">
		<div class="approve">อนุมัติ</div>
		<div class="banner">Digitally signed by</div>
		<div class="sign-box">
			<table class="sig-inner" width="100%"><tr>
				<td width="48%">' .
					($approverSigImg !== ''
						? '<img class="sig-img" src="' . $h($approverSigImg) . '" alt="" />'
						: '<span style="color:#15803d;font-style:italic;font-size:10pt;">Digitally signed by</span>') .
				'</td>
				<td width="52%">
					<div class="meta-line">' . $h($approver_name !== '' ? $approver_name : '........................') . '</div>
					<div class="meta-line">' . $h($approver_pos) . '</div>
					<div class="meta-line">' . $h($approver_date !== '' ? $approver_date : '....................') . '</div>
				</td>
			</tr></table>
		</div>
	</td>
</tr>
</table>
';

$pmPdfParenName = static function ($name) {
	$name = trim((string)$name);
	if ($name === '' || $name === '........................') {
		return '(........................)';
	}
	$first = function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
	return $first === '(' ? $name : '(' . $name . ')';
};

$pdfSignLines = static function ($imgHtml, $name, $pos, $date, $approveAbove = false) {
	$rows = '';
	if ($approveAbove) {
		$rows .= '<tr><td class="sign-line approve">อนุมัติ</td></tr>';
	}
	if ($imgHtml !== '') {
		$rows .= '<tr><td class="sign-line sign-img-cell">' . $imgHtml . '</td></tr>';
	}
	$rows .= '<tr><td class="sign-line">' . $name . '</td></tr>';
	$rows .= '<tr><td class="sign-line">' . $pos . '</td></tr>';
	$rows .= '<tr><td class="sign-line">' . $date . '</td></tr>';
	return '<table class="sign-stack" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table>';
};
$signHtmlOperate = '
<table class="signs-plain" width="100%">
<tr>
	<td width="50%">&nbsp;</td>
	<td width="50%" class="sign-right">' .
		$pdfSignLines(
			$proposerSigImg !== '' ? '<img class="sig-img" src="' . $h($proposerSigImg) . '" alt="" />' : '',
			$h($pmPdfParenName($proposer_name)),
			$h($proposer_pos !== '' ? $proposer_pos : '........................'),
			$h($proposer_date !== '' ? $proposer_date : '....................')
		) . '
	</td>
</tr>
<tr>
	<td width="50%">&nbsp;</td>
	<td width="50%" class="sign-right" style="padding-top:14px;">' .
		$pdfSignLines(
			$approverSigImg !== '' ? '<img class="sig-img" src="' . $h($approverSigImg) . '" alt="" />' : '',
			$h($pmPdfParenName($approver_name)),
			$h($approver_pos !== '' ? $approver_pos : '........................'),
			$h($approver_date !== '' ? $approver_date : '....................'),
			true
		) . '
	</td>
</tr>
</table>
';

$signHtml = ($memo_type === 'change') ? $signHtmlChange : $signHtmlOperate;

$subjectLine = ($memo_type === 'change')
	? ('ขออนุมัติเปลี่ยนแปลงโครงการ ' . $h($name !== '' ? $name : '…'))
	: ('ขออนุมัติดำเนินการโครงการ ' . $h($name !== '' ? $name : '…'));

if ($memo_type === 'change') {
	$bodyHtml = '
<div class="para">ด้วย ' . $h($unitShow) . ' ได้รับอนุมัติให้ดำเนินการโครงการ ' . $h($name !== '' ? $name : '…') . ' ประจำปีงบประมาณ พ.ศ. ' . $h($year !== '' ? $year : '…') . ' รหัสโครงการ ' . $h($code !== '' ? $code : '-') . ' แหล่งงบประมาณ ' . $h($budget_src !== '' ? $budget_src : '-') . ' วงเงินงบประมาณ ' . $h($budget !== '' ? $budget : '0') . ' บาท</div>
<div class="para">ในการนี้ ' . $h($unitShow) . ' มีความประสงค์ขอเปลี่ยนแปลงรายละเอียดของโครงการ เนื่องจาก ' . nl2br($h($reason !== '' ? $reason : '…')) . '</div>
';
	$tableHtml = '
<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; overflow: wrap;">
<tr>
	' . $td18('ลำดับ', 'width="40" align="center"') . '
	' . $td18('รายละเอียดเดิม') . '
	' . $td18('รายละเอียดการเปลี่ยนแปลง') . '
</tr>
' . $detailsRowsHtml . '
</table>
';
} else {
	// 2 บรรทัดตามแบบ ขนาดเดียวกับบรรทัดล่าง (10pt) — ห้าม nowrap เพื่อไม่ดันไปหน้า 2
	$paraFace = 'font-family:sarabun; font-size:10pt; line-height:1.4;';
	$bodyHtml = '
<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
<tr>
	<td width="75" style="border:0; padding:6px 0 0 0; ' . $paraFace . '">&nbsp;</td>
	<td style="border:0; padding:6px 0 0 0; ' . $paraFace . ' letter-spacing:-0.2pt;">ด้วย' . $h($unitShow) . ' มีความประสงค์ขออนุมัติดำเนินการโครงการผ่านระบบบริหารจัดการ</td>
</tr>
<tr>
	<td colspan="2" style="border:0; padding:0 0 8px 0; ' . $paraFace . '">โครงการ ของคณะเภสัชศาสตร์ ดังนี้</td>
</tr>
</table>
';
	$tableHtml = '
<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; overflow: wrap;">
<tr>' . $td18('รหัสโครงการ', 'width="32%"') . $td18($h($code !== '' ? $code : '-')) . '</tr>
<tr>' . $td18('ชื่อโครงการ', 'width="32%"') . $td18($h($name !== '' ? $name : '-')) . '</tr>
<tr>' . $td18('งบประมาณ (บาท)', 'width="32%"') . $td18($h($budget !== '' ? $budget : '0')) . '</tr>
<tr>' . $td18('แหล่งงบประมาณ', 'width="32%"') . $pdfBudgetSrcCell($budget_src) . '</tr>
</table>
';
}

// หมายเหตุ mPDF: ห้ามใช้ width:1% + nowrap กับป้ายกำกับไทย — จะถูกบีบจนตัวอักษรขึ้นบรรทัดใหม่ทีละตัว
$html = '
<style>
body { font-family: sarabun; font-size: 10pt; color: #000; line-height: 1.35; }
table { border-collapse: collapse; }
.crest td { vertical-align: middle; border: 0; padding: 0; }
.title { text-align: center; font-weight: bold; font-size: 14pt; line-height: 1.2; }
.meta td { border: 0; padding: 2px 4px 4px 0; vertical-align: bottom; font-size: 10pt; line-height: 1.4; }
.lbl { font-weight: bold; font-size: 10pt; }
.val { border-bottom: 0.5pt solid #000; font-size: 10pt; }
.meta tr.to-line td { padding-top: 12px; }
.to-val { font-size: 10pt; }
.para { margin: 8px 0 0; padding: 0; text-indent: 75px; text-align: left; font-size: 10pt; line-height: 1.45; }
.closing { margin-left: 75px; text-indent: 0; text-align: justify; margin-top: 8px; margin-bottom: 12px; margin-right: 0; font-size: 10pt; line-height: 1.45; }
.signs td { border: 0; vertical-align: top; padding: 10px 0 6px; font-size: 10pt; }
.sign-head { font-weight: bold; font-size: 10pt; }
.banner { background: #000; color: #fff; font-weight: bold; padding: 3px 6px; font-size: 10pt; text-align: center; }
.sign-box { border: 0.5pt solid #000; padding: 6px 4px; }
.approve { color: #2e7d32; font-weight: bold; font-size: 10pt; margin: 0 0 3px; text-align: center; }
.sig-img { height: 48px; }
.meta-line { margin: 2px 0; font-size: 10pt; line-height: 1.35; text-align: center; }
.hr { border-top: 0.5pt solid #000; margin: 2px 0 0; }
.sig-inner td { border: 0; padding: 2px; vertical-align: middle; font-size: 10pt; text-align: center; }
.signs-plain td { border: 0; padding: 0; }
.signs-plain td.sign-right { border: 0; text-align: center; vertical-align: top; padding: 4px 0; font-size: 10pt; }
.sign-stack { width: 100%; }
.sign-stack td.sign-line { border: 0; text-align: center; vertical-align: top; padding: 1.5px 0; font-size: 10pt; line-height: 1.35; }
.sign-stack td.sign-img-cell { padding: 1px 0 3px; }
.sign-stack td.approve { color: #2e7d32; font-weight: bold; font-size: 10pt; padding: 0 0 2px; }
.signs-plain .sig-img { height: 48px; }
</style>

<table class="crest" width="100%">
<tr>
	<td width="90">' . ($krutSrc !== '' ? '<img src="' . $h($krutSrc) . '" width="80" height="80" alt="" />' : '') . '</td>
	<td class="title">บันทึกข้อความ</td>
	<td width="90">&nbsp;</td>
</tr>
</table>

<table class="meta" width="100%">
<tr>
	<td class="lbl" width="75"><b>ส่วนงาน</b></td>
	<td class="val" colspan="3">' . $h($dept) . '</td>
</tr>
<tr>
	<td class="lbl" width="75"><b>ที่</b></td>
	<td class="val" width="220">' . $h($doc_no) . '</td>
	<td class="lbl" width="50"><b>วันที่</b></td>
	<td class="val">' . $h($date) . '</td>
</tr>
<tr>
	<td class="lbl" width="75"><b>เรื่อง</b></td>
	<td class="val" colspan="3">' . $subjectLine . '</td>
</tr>
<tr class="to-line">
	<td class="lbl" width="75"><b>เรียน</b></td>
	<td class="to-val" colspan="3">คณบดีคณะเภสัชศาสตร์</td>
</tr>
</table>
' . $bodyHtml;

$htmlAfter = '
<div class="closing">จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ</div>
' . $signHtml;

ob_end_clean();

$safeCode = preg_replace('/[\\\\\/:*?"<>|]+/u', '_', $code !== '' ? $code : (string)$project_id);
$filename = ($memo_type === 'change' ? 'บันทึกเปลี่ยนแปลง_' : 'บันทึกอนุมัติ_') . $safeCode . ($memo_type === 'change' && $request_id > 0 ? ('_' . $request_id) : '') . '.pdf';

$mpdf->WriteHTML($html);
$mpdf->WriteHTML('<div style="font-family:sarabun;font-size:9pt;margin:4px 0 8px 0;">' . $tableHtml . '</div>');
$mpdf->WriteHTML($htmlAfter);
$dest = ($isApiMode && $apiDisposition === 'inline')
	? \Mpdf\Output\Destination::INLINE
	: \Mpdf\Output\Destination::DOWNLOAD;
$mpdf->Output($filename, $dest);
exit;
