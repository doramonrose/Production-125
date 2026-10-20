<?php
/**
 * หน้าพิมพ์รายละเอียดโครงการ (ทุกหัวข้อที่มีข้อมูล — พิมพ์ได้แม้กรอกไม่ครบ)
 *
 * โหมดปกติ: ต้อง login + สิทธิ์ดูโครงการ
 * โหมด API: ตั้ง $PM_PROJECT_PRINT_API = true ก่อน include (หรือเรียกผ่าน api_project_print_pdf.php)
 */
if (!isset($db_Project) || !($db_Project instanceof mysqli)) {
	http_response_code(500);
	exit('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
}
if (!function_exists('getPreProjectID')) {
	include_once __DIR__ . '/../fn/main_project.php';
}
if (function_exists('pm_ensure_general_note_schema') && isset($db_Project) && $db_Project) {
	pm_ensure_general_note_schema($db_Project);
}

$isPrintApiMode = !empty($PM_PROJECT_PRINT_API);
$apiPrintDisposition = isset($PM_PROJECT_PRINT_DISPOSITION)
	? strtolower(trim((string)$PM_PROJECT_PRINT_DISPOSITION))
	: 'inline';
if (!in_array($apiPrintDisposition, ['inline', 'download', 'attachment'], true)) {
	$apiPrintDisposition = 'inline';
}
if ($apiPrintDisposition === 'attachment') {
	$apiPrintDisposition = 'download';
}

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
if ($project_id <= 0 && !empty($PM_PROJECT_PRINT_PROJECT_ID)) {
	$project_id = (int)$PM_PROJECT_PRINT_PROJECT_ID;
}
$me = (isset($row) && is_array($row)) ? $row : [];
$sid = (int)($me['id'] ?? 0);
$staff_code = (string)($me['code'] ?? '');
$unit_code = (string)($me['unit_code'] ?? '');

$h = static function ($s): string {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$rich = static function ($s): string {
	$raw = trim((string)$s);
	if ($raw === '') {
		return '-';
	}
	$raw = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $raw);
	$raw = preg_replace('/\son\w+="[^"]*"/i', '', $raw);
	$raw = preg_replace("/\son\w+='[^']*'/i", '', $raw);
	$raw = preg_replace('#</?font\b[^>]*>#i', '', $raw);
	$raw = preg_replace('/\s(style|face|color|size)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $raw);
	$raw = preg_replace('#<h[1-6]\b[^>]*>#i', '<p>', $raw);
	$raw = preg_replace('#</h[1-6]>#i', '</p>', $raw);
	$raw = strip_tags($raw, '<p><br><div><span><b><strong><i><em><u><ul><ol><li>');
	$raw = trim($raw);
	return $raw !== '' ? $raw : '-';
};
$orEmpty = static function ($s) use ($h): string {
	$t = trim((string)$s);
	return $t !== '' ? $h($t) : '-';
};
$plain = static function ($s) use ($h): string {
	$t = trim(html_entity_decode(strip_tags((string)$s), ENT_QUOTES, 'UTF-8'));
	return $t !== '' ? $h($t) : '';
};
$money = static function ($v): string {
	$s = trim((string)$v);
	if ($s === '') {
		return '';
	}
	$f = (float)$s;
	if (abs($f) < 0.0001) {
		return '0';
	}
	$rounded = round($f, 2);
	if (abs($rounded - round($rounded)) < 0.0001) {
		return number_format((int)round($rounded));
	}
	return rtrim(rtrim(number_format($rounded, 2, '.', ','), '0'), '.');
};
$moneyMo = static function ($v) use ($money): string {
	return str_replace(',', '', $money($v));
};
$calcText = static function (array $it) use ($money): string {
	$unit1 = trim((string)($it['unit1'] ?? ''));
	$unit2 = trim((string)($it['unit2'] ?? ''));
	$qty2 = $it['qty2'] ?? '';
	$price = $money($it['price'] ?? '');
	$q1 = $money($it['qty1'] ?? '');
	$parts = [];
	if ($price !== '') {
		$parts[] = $price . ' บาท';
	}
	$p2 = trim($q1 . ($unit1 !== '' ? ' ' . $unit1 : ''));
	if ($p2 !== '') {
		$parts[] = $p2;
	}
	if ($qty2 !== '' && is_numeric($qty2) && (float)$qty2 > 0) {
		$parts[] = trim($money($qty2) . ($unit2 !== '' ? ' ' . $unit2 : ''));
	}
	return implode(' x ', $parts);
};
$monthTh = static function ($m, $y): string {
	$months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
	$mi = (int)$m;
	$ys = trim((string)$y);
	if ($ys !== '' && ctype_digit($ys) && (int)$ys > 0 && (int)$ys < 2400) {
		$ys = (string)((int)$ys + 543);
	}
	$name = ($mi >= 1 && $mi <= 12) ? $months[$mi] : trim((string)$m);
	if ($name === '' && $ys === '') {
		return '';
	}
	return trim($name . ($ys !== '' ? ' ' . $ys : ''));
};

if ($project_id <= 0) {
	http_response_code(400);
	if ($isPrintApiMode) {
		header('Content-Type: text/plain; charset=utf-8');
		exit('กรุณาระบุโครงการ');
	}
	echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>พิมพ์โครงการ</title></head><body><p>กรุณาบันทึกโครงการก่อนพิมพ์</p></body></html>';
	exit;
}

if (!$isPrintApiMode) {
	if (!function_exists('staffCanViewPreProjectManageDetail')
		|| !staffCanViewPreProjectManageDetail($db_Project, $project_id, $sid, $staff_code, $unit_code)) {
		http_response_code(403);
		echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>พิมพ์โครงการ</title></head><body><p>ไม่มีสิทธิ์เข้าถึงโครงการนี้</p></body></html>';
		exit;
	}
}

$pre = function_exists('getPreProjectID') ? getPreProjectID($db_Project, $project_id) : null;
if (!$pre || !is_array($pre)) {
	http_response_code(404);
	if ($isPrintApiMode) {
		header('Content-Type: text/plain; charset=utf-8');
		exit('ไม่พบโครงการ');
	}
	echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>พิมพ์โครงการ</title></head><body><p>ไม่พบโครงการ</p></body></html>';
	exit;
}

$unit_names = function_exists('planBuildSetUnitNamesMapNameOnly')
	? planBuildSetUnitNamesMapNameOnly($db_Project)
	: (function_exists('planBuildSetUnitNamesMap') ? planBuildSetUnitNamesMap($db_Project) : []);
$staffName = static function ($id) use ($db_person): string {
	if (function_exists('pm_get_staff_display_name_by_id')) {
		return pm_get_staff_display_name_by_id($db_person, (int)$id);
	}
	if (function_exists('getStaffByID')) {
		$r = getStaffByID($db_person, (int)$id);
		return is_array($r) ? trim((string)($r['fname'] ?? '') . ' ' . (string)($r['lname'] ?? '')) : '';
	}
	return '';
};

$name_project = trim((string)($pre['name_project'] ?? ''));
$year_project = trim((string)($pre['year_project'] ?? ''));
$ref_code = trim((string)($pre['code_pre_project'] ?? ''));
$status_project = (string)($pre['status_project'] ?? '0');
$notify_result = trim((string)($pre['notify_result'] ?? ''));

$status_label = 'ร่าง';
if (function_exists('pm_is_operate_final_approved') && pm_is_operate_final_approved($db_Project, $project_id, $pre)) {
	$status_label = 'อนุมัติดำเนินโครงการ';
} elseif ($status_project === '4' || (function_exists('mb_strpos') ? mb_strpos($notify_result, 'บรรจุแผน') === 0 : strpos($notify_result, 'บรรจุแผน') === 0)) {
	$status_label = ($notify_result !== '' ? $notify_result : 'บรรจุแผน');
} elseif ($status_project === '2') {
	$status_label = 'ส่งร่าง';
} elseif ($status_project === '3') {
	$status_label = 'รอบรรจุ';
} elseif ($status_project === '8') {
	$status_label = 'ตีกลับให้แก้ไข';
} elseif ($status_project === '1') {
	$status_label = 'ขอดำเนินการ';
}

$heads = function_exists('getProjectHeadList') ? getProjectHeadList($db_Project, $project_id) : [];
$coords = function_exists('getProjectStaffList') ? getProjectStaffList($db_Project, $project_id) : [];
$persons = function_exists('getProjectPersonList') ? getProjectPersonList($db_Project, $project_id) : [];
$deps = function_exists('getProjectDepartmentList') ? getProjectDepartmentList($db_Project, $project_id, 1) : [];

$fmtPerson = static function (array $row) use ($staffName): string {
	$name = $staffName((int)($row['staff_project'] ?? 0));
	$phone = trim((string)($row['phone'] ?? ''));
	if ($name === '' && $phone === '') {
		return '';
	}
	return ($name !== '' ? $name : '-') . ($phone !== '' ? ' (' . $phone . ')' : '');
};
$headTxt = !empty($heads[0]) ? $fmtPerson($heads[0]) : '';
$coordTxt = !empty($coords[0]) ? $fmtPerson($coords[0]) : '';
$personTxts = [];
foreach ($persons as $p) {
	$t = $fmtPerson($p);
	if ($t !== '') {
		$personTxts[] = $t;
	}
}

$code_unit = function_exists('planResolvePreProjectCodeUnit')
	? planResolvePreProjectCodeUnit($db_Project, (string)($pre['code_unit'] ?? ''))
	: (string)($pre['code_unit'] ?? '');
$unitMain = '';
if ($code_unit !== '' && isset($unit_names[$code_unit])) {
	$unitMain = (string)$unit_names[$code_unit];
} elseif ($code_unit !== '') {
	$unitMain = $code_unit;
}
$unitCos = [];
foreach ($deps as $d) {
	$code = trim((string)($d['department_project'] ?? ''));
	if ($code === '') {
		continue;
	}
	$nm = $unit_names[$code] ?? $code;
	if ($nm !== '') {
		$unitCos[] = $nm;
	}
}

$natureRow = ($pre['code_nature'] ?? '') !== '' && function_exists('getSetNature')
	? getSetNature($db_Project, $pre['code_nature']) : null;
$natureName = is_array($natureRow) ? trim((string)($natureRow['name'] ?? '')) : '';
$typeRow = ($pre['code_type'] ?? '') !== '' && function_exists('getSetProjectType')
	? getSetProjectType($db_Project, $pre['code_type']) : null;
$typeName = is_array($typeRow) ? trim((string)($typeRow['name'] ?? '')) : '';
$budgetSrc = function_exists('resolveProjectBudgetSourceForDisplay')
	? resolveProjectBudgetSourceForDisplay($db_Project, $project_id)
	: trim((string)($pre['budget_other'] ?? ''));
$startTxt = $monthTh($pre['start_month'] ?? '', $pre['start_year'] ?? '');
$endTxt = $monthTh($pre['end_month'] ?? '', $pre['end_year'] ?? '');
$periodTxt = ($startTxt !== '' || $endTxt !== '')
	? ('เริ่ม: ' . ($startTxt !== '' ? $startTxt : '-') . ' | สิ้นสุด: ' . ($endTxt !== '' ? $endTxt : '-'))
	: '';
$location = trim((string)($pre['location'] ?? ''));

$consRows = function_exists('getProjectConsistencyRows')
	? getProjectConsistencyRows($db_Project, $project_id)
	: [];
$cons = $consRows ? $consRows[0] : (function_exists('getProjectConsistencyRow') ? getProjectConsistencyRow($db_Project, $project_id) : null);
if ($cons && is_array($cons) && !$consRows) {
	$consRows = [$cons];
}
$type_name = $level2_name = '';
$kpi_names = [];
$sdg_names = [];
$cons_display_sets = [];
$pm_sdgs_enabled = function_exists('pm_sdgs_consistency_enabled') ? pm_sdgs_consistency_enabled($db_Project) : true;
$consUi = function_exists('pm_consistency_ui_labels')
	? pm_consistency_ui_labels('', '')
	: [
		'sectionTitle' => 'ความเชื่อมโยงกับแผนและตัวชี้วัด',
		'typeLabel' => 'กรอบแผนของโครงการ',
		'level2Label' => 'ด้าน/แผนปฏิบัติการ (AP)',
	];
$childKindPrint = '';
if ($consRows) {
	$typeIdKey = (string)($consRows[0]['code_kpi_type'] ?? '');
	if ($typeIdKey !== '' && function_exists('getSetType') && isset($db_KPI)) {
		$t = getSetType($db_KPI, $typeIdKey);
		$type_name = trim((string)($t['name'] ?? ''));
		$childKindPrint = function_exists('kpi_type_child_kind')
			? kpi_type_child_kind($t)
			: (string)($t['child_kind'] ?? '');
	}
	if (function_exists('pm_consistency_ui_labels')) {
		$consUi = pm_consistency_ui_labels($type_name, $childKindPrint);
	}
	$consGroup = (string)($consUi['group'] ?? 'other');
	$sdg_selected = [];
	foreach ($consRows as $setIdx => $cRow) {
		$codeSide = (string)($cRow['code_side'] ?? '');
		$codeAp = (string)($cRow['code_ap'] ?? '');
		$setSideName = $setApName = '';
		if ($codeSide !== '' && function_exists('getSetSide') && isset($db_KPI)) {
			$sd = getSetSide($db_KPI, $codeSide);
			$setSideName = trim((string)($sd['name'] ?? ''));
		}
		if ($codeAp !== '' && function_exists('getSetAP') && isset($db_KPI)) {
			$ap = getSetAP($db_KPI, $codeAp);
			$setApName = trim((string)($ap['name'] ?? ''));
		}
		$setName = ($childKindPrint === 'so') ? $setApName : $setSideName;
		if ($setName === '') {
			$setName = $setApName !== '' ? $setApName : $setSideName;
		}
		$copy = function_exists('pm_consistency_set_copy')
			? pm_consistency_set_copy($consGroup, $setIdx === 0)
			: ['level2Label' => $consUi['level2Label']];
		$setKpis = [];
		$ckpis = !empty($cRow['id']) && function_exists('getProjectConsistencyKPIDetail')
			? getProjectConsistencyKPIDetail($db_Project, (int)$cRow['id']) : [];
		if (!empty($ckpis) && function_exists('getKPI') && isset($db_KPI)) {
			foreach ($ckpis as $kpi_item) {
				$kpi = getKPI($db_KPI, $kpi_item['code_kpi_detail'] ?? '');
				$kn = (string)($kpi['name'] ?? ('ID:' . ($kpi_item['code_kpi_detail'] ?? '')));
				$setKpis[] = $kn;
				$kpi_names[] = $kn;
			}
		}
		if (!empty($cRow['id']) && function_exists('getSelectedConsistencyMap')) {
			foreach (getSelectedConsistencyMap($db_Project, (int)$cRow['id']) as $sidg) {
				$sidg = (int)$sidg;
				if ($sidg && !in_array($sidg, $sdg_selected, true)) {
					$sdg_selected[] = $sidg;
				}
			}
		}
		$cons_display_sets[] = [
			'label' => (string)($copy['level2Label'] ?? $consUi['level2Label']),
			'name' => $setName,
			'kpis' => $setKpis,
		];
		if ($setIdx === 0) {
			$level2_name = $setName;
		}
	}
	$sdg_detail_list = function_exists('getSetConsistencyDetailList') ? getSetConsistencyDetailList($db_Project, function_exists('pm_sdgs_consistency_id') ? pm_sdgs_consistency_id($db_Project) : 2) : [];
	if (!empty($pm_sdgs_enabled) && !empty($sdg_selected) && !empty($sdg_detail_list)) {
		foreach ($sdg_detail_list as $d) {
			$did = (int)($d['id'] ?? 0);
			if ($did && in_array($did, $sdg_selected, true)) {
				$sdg_names[] = (string)($d['name'] ?? '');
			}
		}
	}
}

$s3_fields = [];
if ((int)($pre['midyear_request'] ?? 0) === 1) {
	$s3_fields[] = ['label' => 'เหตุผลการขอเพิ่มโครงการระหว่างปี', 'val' => $pre['midyear_reason'] ?? ''];
}
$s3_fields = array_merge($s3_fields, [
	['label' => 'ความสำคัญของโครงการ/หลักการและเหตุผล', 'val' => $pre['reson'] ?? ''],
	['label' => 'ทบทวนผลการดำเนินโครงการที่ผ่านมา', 'val' => $pre['result'] ?? ''],
	['label' => 'วัตถุประสงค์ของโครงการ', 'val' => $pre['objective'] ?? ''],
	['label' => 'เป้าหมายโครงการ', 'val' => $pre['target_project'] ?? ''],
]);

$kpis = function_exists('getProjectKPIList') ? getProjectKPIList($db_Project, $project_id) : [];
$acts = function_exists('getProjectActivityList') ? getProjectActivityList($db_Project, $project_id) : [];
$files = function_exists('getProjectFileList') ? getProjectFileList($db_Project, $project_id) : [];

$monthCols = ['mon_10','mon_11','mon_12','mon_01','mon_02','mon_03','mon_04','mon_05','mon_06','mon_07','mon_08','mon_09'];
$monthMap = [
	'mon_10' => 'ต.ค.', 'mon_11' => 'พ.ย.', 'mon_12' => 'ธ.ค.',
	'mon_01' => 'ม.ค.', 'mon_02' => 'ก.พ.', 'mon_03' => 'มี.ค.',
	'mon_04' => 'เม.ย.', 'mon_05' => 'พ.ค.', 'mon_06' => 'มิ.ย.',
	'mon_07' => 'ก.ค.', 'mon_08' => 'ส.ค.', 'mon_09' => 'ก.ย.',
];

$printed_at = date('d/m/') . ((int)date('Y') + 543) . ' ' . date('H:i');
$title = ($name_project !== '' ? $name_project : 'โครงการ') . ($ref_code !== '' ? ' (' . $ref_code . ')' : '');
$wantPdf = $isPrintApiMode
	|| (isset($op) && $op === 'print_project_pdf')
	|| (isset($_GET['format']) && (string)$_GET['format'] === 'pdf');

if ($wantPdf) {
	$autoload = __DIR__ . '/../../mpdf/vendor/autoload.php';
	if (!is_file($autoload)) {
		http_response_code(500);
		echo 'ไม่พบไลบรารีสร้าง PDF';
		exit;
	}
	require_once $autoload;

	$s2Kpi = empty($kpi_names) ? '-' : $h(implode(', ', $kpi_names));
	$s2Sdg = empty($sdg_names) ? '-' : $h(implode(', ', $sdg_names));
	$s2SetRows = '';
	if (!empty($cons_display_sets)) {
		foreach ($cons_display_sets as $dSet) {
			$s2SetRows .= '<tr><td class="k">' . $h((string)($dSet['label'] ?? $consUi['level2Label'])) . ' :</td><td class="v">' . $orEmpty($dSet['name'] ?? '') . '</td></tr>';
			$s2SetKpi = empty($dSet['kpis']) ? '-' : $h(implode(', ', $dSet['kpis']));
			$s2SetRows .= '<tr><td class="k">ตัวชี้วัดเชิงพันธกิจ/ตัวชี้วัดเชิงกลยุทธ์ (KR) :</td><td class="v">' . $s2SetKpi . '</td></tr>';
		}
	} else {
		$s2SetRows = '<tr><td class="k">' . $h($consUi['level2Label'] ?? 'ด้าน/แผนปฏิบัติการ (AP)') . ' :</td><td class="v">' . $orEmpty($level2_name) . '</td></tr>'
			. '<tr><td class="k">ตัวชี้วัดเชิงพันธกิจ/ตัวชี้วัดเชิงกลยุทธ์ (KR) :</td><td class="v">' . $s2Kpi . '</td></tr>';
	}
	$periodPdf = ($startTxt === '' && $endTxt === '')
		? '-'
		: ('เริ่ม ' . ($startTxt !== '' ? $h($startTxt) : '-') . '<br>สิ้นสุด ' . ($endTxt !== '' ? $h($endTxt) : '-'));

	$s3Rows = '';
	foreach ($s3_fields as $i => $f) {
		$s3Rows .= '<tr>'
			. '<td class="lbl" width="30%" valign="top">3.' . ((int)$i + 1) . ' ' . $h($f['label']) . '</td>'
			. '<td class="rich" width="70%" valign="top">' . $rich($f['val']) . '</td>'
			. '</tr>';
	}
	$kpiInner = '-';
	if (!empty($kpis)) {
		$kpiInner = '<table class="kpi" width="100%" cellpadding="4" cellspacing="0"><thead><tr>'
			. '<td width="80%" align="center"><b>ตัวชี้วัดของโครงการ (หน่วยนับ)</b></td>'
			. '<td width="20%" align="center"><b>ค่าเป้าหมาย</b></td>'
			. '</tr></thead><tbody>';
		foreach ($kpis as $i => $pk) {
			$kpi_name_show = trim((string)($pk['kpi_name'] ?? ''));
			$kpi_unit_show = trim((string)($pk['kpi_unit'] ?? ''));
			if ($kpi_name_show === '' && !empty($pk['code_kpi']) && function_exists('getKPI') && isset($db_KPI)) {
				$kpiRow = getKPI($db_KPI, $pk['code_kpi']);
				$kpi_name_show = trim((string)($kpiRow['name'] ?? ''));
			}
			if ($kpi_unit_show === '' && !empty($pk['code_unit']) && function_exists('getSetUnit') && isset($db_KPI)) {
				$unitRow = getSetUnit($db_KPI, $pk['code_unit']);
				$kpi_unit_show = trim((string)($unitRow['name'] ?? ''));
			}
			$mon_sum = 0.0;
			foreach ($monthCols as $mc) {
				$mon_sum += (float)($pk[$mc] ?? 0);
			}
			$w = (string)($pk['kpi_weight'] ?? '');
			$wShow = ($w !== '' && is_numeric($w)) ? $money($w) : $w;
			if ($wShow === '') {
				$wShow = '0';
			}
			$label = ((int)$i + 1) . '. ' . ($kpi_name_show !== '' ? $kpi_name_show : '-');
			if ($kpi_unit_show !== '') {
				$label .= ' (' . $kpi_unit_show . ')';
			}
			$label .= ' (ค่าน้ำหนัก : ' . $wShow . ')';
			$kpiInner .= '<tr><td>' . $h($label) . '</td><td align="center">' . ($mon_sum > 0 ? $h($money($mon_sum)) : '-') . '</td></tr>';
		}
		$kpiInner .= '</tbody></table>';
	}
	$s3Rows .= '<tr><td class="lbl" width="30%" valign="top">3.5 ตัวชี้วัดเป้าหมายโครงการ</td><td class="kpi-cell" width="70%" valign="top">' . $kpiInner . '</td></tr>';

	$actRows = '';
	$grand = 0.0;
	if (!empty($acts)) {
		foreach ($acts as $a) {
			$actId = (int)($a['id'] ?? 0);
			$actNo = trim((string)($a['activity_no'] ?? ''));
			$actNm = trim((string)($a['activity_name'] ?? ''));
			$wRaw = (string)($a['activity_weight'] ?? '');
			$wShow = ($wRaw !== '' && is_numeric($wRaw)) ? $money($wRaw) : $wRaw;
			$actLabel = trim($actNo . '. ' . $actNm);
			if ($wShow !== '') {
				$actLabel .= ' (ค่าน้ำหนัก : ' . $wShow . ')';
			}
			$targets = function_exists('getProjectActivityTargetList') ? getProjectActivityTargetList($db_Project, $actId) : [];
			$tHtml = [];
			foreach ($targets as $tix => $t) {
				$set = function_exists('getSetTarget') ? getSetTarget($db_Project, $t['code_target'] ?? '') : null;
				$tn = is_array($set) ? trim((string)($set['name'] ?? '')) : '';
				$unitT = is_array($set) ? trim((string)($set['unit'] ?? $set['name_unit'] ?? '')) : '';
				$amt = trim((string)($t['target_amount'] ?? ''));
				$amtShow = ($amt !== '' && is_numeric($amt)) ? $money($amt) : $amt;
				if ($tn === '' && $amtShow === '') {
					continue;
				}
				$line = ((int)$tix + 1) . '. ' . ($tn !== '' ? $tn : '-');
				if ($unitT !== '' && strpos($tn, '(') === false) {
					$line .= ' (' . $unitT . ')';
				}
				if ($amtShow !== '') {
					$line .= ' : ' . $amtShow;
				}
				$tHtml[] = $h($line);
			}
			$items = function_exists('getProjectActivityBudgetList') ? getProjectActivityBudgetList($db_Project, $actId) : [];
			$actTotal = 0.0;
			foreach ($items as $it) {
				$actTotal += (float)($it['total'] ?? 0);
			}
			$grand += $actTotal;
			$mo = '';
			foreach ($monthCols as $mc) {
				$mo .= '<td class="mo">' . ((!empty($a[$mc]) && (string)$a[$mc] === '1') ? 'x' : '') . '</td>';
			}
			$actRows .= '<tr><td width="28%">' . $h($actLabel !== '.' ? $actLabel : '-') . '</td><td width="16%">' . (!empty($tHtml) ? implode('<br>', $tHtml) : '-') . '</td><td class="r" width="10%">' . ($actTotal > 0 ? $h($money($actTotal)) : '0') . '</td>' . $mo . '</tr>';
			foreach ($items as $it) {
				$calc = $calcText(is_array($it) ? $it : []);
				$catName = '';
				if (!empty($it['code_expenses']) && function_exists('getSetExpenses')) {
					$exCat = getSetExpenses($db_Project, (string)$it['code_expenses']);
					$catName = is_array($exCat) ? trim((string)($exCat['name'] ?? '')) : '';
				}
				$detailName = '';
				if (!empty($it['code_expenses_detail']) && function_exists('getSetExpensesDetailByID')) {
					$exd = getSetExpensesDetailByID($db_Project, $it['code_expenses_detail']);
					$detailName = is_array($exd) ? trim((string)($exd['name'] ?? '')) : '';
				}
				$desc = function_exists('pmProjectActivityBudgetDescValue')
					? pmProjectActivityBudgetDescValue(is_array($it) ? $it : [])
					: trim((string)($it['detail'] ?? $it['budget_item'] ?? ''));
				$paren = $desc !== '' ? $desc : $detailName;
				if ($catName !== '' && $paren !== '' && $paren !== $catName) {
					$head = $catName . '(' . $paren . ')';
				} elseif ($paren !== '') {
					$head = $paren;
				} else {
					$head = $catName;
				}
				$showLine = trim($head . ($calc !== '' ? ' ' . $calc : ''));
				$plan = function_exists('getProjectActivityBudgetDetail')
					? getProjectActivityBudgetDetail($db_Project, $actId, (int)($it['id'] ?? 0), true)
					: null;
				if (!is_array($plan)) {
					$plan = [];
				}
				if ($showLine === '') {
					continue;
				}
				$moI = '';
				foreach ($monthCols as $mc) {
					$mv = (float)($plan[$mc] ?? 0);
					$moI .= '<td class="mo">' . ($mv > 0 ? $h($money($mv)) : '') . '</td>';
				}
				$actRows .= '<tr><td>• ' . $h($showLine) . '</td><td></td><td class="r">' . $h($money($it['total'] ?? 0)) . '</td>' . $moI . '</tr>';
			}
		}
	}
	$moHead = '';
	foreach ($monthCols as $mc) {
		$moHead .= '<td class="mo" width="3.8%" align="center"><b>' . $h($monthMap[$mc]) . '</b></td>';
	}
	$s4Html = empty($acts)
		? '<div class="pad">-</div>'
		: ('<table class="act" width="100%" cellpadding="4" cellspacing="0"><thead><tr><td width="28%" align="center"><b>กิจกรรม</b></td><td width="16%" align="center"><b>กลุ่มเป้าหมาย</b></td><td width="10%" align="center"><b>งบประมาณ (บาท)</b></td><td colspan="12" align="center"><b>แผนการดำเนินการ</b></td></tr><tr><td></td><td></td><td></td>' . $moHead . '</tr></thead><tbody>' . $actRows . '</tbody></table>'
			. '<div class="foot">แหล่งงบประมาณ : ' . $orEmpty($budgetSrc !== '-' ? $budgetSrc : '') . '</div>'
			. '<div class="total">รวมงบประมาณค่าใช้จ่าย : ' . $h($money($grand)) . ' บาท</div>');

	$pdfCss = '
	body{ font-family:sarabun; font-size:9pt; line-height:1.45; color:#000; }
	h1{ font-family:sarabun; font-size:12pt; text-align:center; margin:0 0 2pt; font-weight:700; line-height:1.35; }
	.meta{ font-family:sarabun; text-align:center; font-size:12pt; font-weight:700; margin:0 0 10pt; }
	.sec{ border:0.4pt solid #000; margin:0 0 8pt; }
	.banner{ font-family:sarabun; font-size:9pt; font-weight:700; color:#000; padding:5pt 8pt 4pt; border-bottom:0.4pt solid #000; }
	.pad{ padding:7pt 9pt 8pt; }
	table{ font-family:sarabun; font-size:9pt; border-collapse:collapse; }
	table.g2{ width:100%; border:0; }
	table.g2 td.col-l{ width:55%; vertical-align:top; padding-right:8pt; border:0; }
	table.g2 td.col-r{ width:45%; vertical-align:top; padding-left:8pt; border:0; border-left:0.4pt solid #000; }
	table.kv{ width:100%; border:0; }
	table.kv td{ font-size:9pt; vertical-align:top; padding:2pt 3pt; line-height:1.4; border:0; }
	td.k{ width:42%; font-weight:400; }
	td.v{ font-weight:700; }
	table.s2 td.k{ width:40%; }
	table.grid{ width:100%; border:0; }
	table.grid td{
		font-size:9pt; line-height:1.45; vertical-align:top; padding:6pt 7pt;
		border-top:0; border-left:0; border-right:0.4pt solid #000; border-bottom:0.4pt solid #000;
	}
	td.lbl{ width:30%; font-weight:700; font-size:9pt; }
	td.rich, td.kpi-cell{ border-right:0; }
	table.grid tr:last-child td{ border-bottom:0; }
	td.rich p, td.rich div, td.rich span, td.rich li{ font-size:9pt; line-height:1.45; }
	td.rich p{ margin:0 0 3pt; text-align:left; }
	td.kpi-cell{ padding:0; }
	table.kpi{ width:100%; border:0; }
	table.kpi td{
		font-size:9pt; padding:4pt 5pt; vertical-align:top;
		border-top:0.4pt solid #000; border-left:0.4pt solid #000; border-right:0; border-bottom:0;
	}
	table.kpi tr:first-child td{ border-top:0; }
	table.kpi td:first-child{ border-left:0; }
	table.act{ width:100%; border:0; }
	table.act td{
		font-size:9pt; line-height:1.4; padding:4pt 5pt; vertical-align:top;
		border-top:0; border-left:0; border-right:0.4pt solid #000; border-bottom:0.4pt solid #000;
	}
	table.act td:last-child{ border-right:0; }
	table.act tr:last-child td{ border-bottom:0; }
	table.act td.r{ text-align:right; }
	table.act td.mo{ text-align:center; font-size:9pt; padding:3pt 1pt; }
	.foot{ font-size:9pt; padding:6pt 9pt 2pt; }
	.total{ font-size:9pt; font-weight:700; padding:3pt 8pt; margin:3pt 9pt 8pt; border:0.4pt solid #000; }
	table.eval{ width:100%; border:0; }
	table.eval td{ font-size:9pt; line-height:1.5; padding:6pt 8pt; vertical-align:top; border:0; }
	table.eval td.el{ width:24%; text-align:center; font-weight:700; border-right:0.4pt solid #000; }
	';

	$html = '
	<h1>' . ($name_project !== '' ? $h($name_project) : '-') . '</h1>
	<div class="meta">ปีงบประมาณ : ' . ($year_project !== '' ? $h($year_project) : '-') . ' | รหัสโครงการ : ' . ($ref_code !== '' ? $h($ref_code) : '-') . '</div>
	<div class="sec"><div class="banner">ส่วนที่ 1 ข้อมูลทั่วไป</div>
	<div class="pad"><table class="g2" width="100%" cellpadding="0" cellspacing="0"><tr>
	<td class="col-l" width="55%"><table class="kv" width="100%" cellpadding="0" cellspacing="0">
		<tr><td class="k" width="42%">ผู้รับผิดชอบโครงการ :</td><td class="v" width="58%">' . $orEmpty($headTxt) . '</td></tr>
		<tr><td class="k">ผู้ประสานงาน :</td><td class="v">' . $orEmpty($coordTxt) . '</td></tr>
		<tr><td class="k">ผู้ร่วมโครงการ (ถ้ามี) :</td><td class="v">' . (!empty($personTxts) ? $h(implode(', ', $personTxts)) : '-') . '</td></tr>
		<tr><td class="k">หน่วยงานหลัก :</td><td class="v">' . $orEmpty($unitMain) . '</td></tr>
		<tr><td class="k">หน่วยงานร่วม (ถ้ามี) :</td><td class="v">' . (!empty($unitCos) ? $h(implode(', ', $unitCos)) : '-') . '</td></tr>
	</table></td>
	<td class="col-r" width="45%"><table class="kv" width="100%" cellpadding="0" cellspacing="0">
		<tr><td class="k" width="48%">ระยะเวลาดำเนินโครงการ :</td><td class="v" width="52%">' . $periodPdf . '</td></tr>
		<tr><td class="k">พื้นที่ดำเนินโครงการ :</td><td class="v">' . $orEmpty($location) . '</td></tr>
		<tr><td class="k">หมายเหตุ (ถ้ามี) :</td><td class="v">' . $orEmpty($pre['general_note'] ?? '') . '</td></tr>
	</table></td>
	</tr></table></div></div>
	<div class="sec"><div class="banner">ส่วนที่ 2 ' . $h($consUi['sectionTitle'] ?? 'ความเชื่อมโยงกับแผนและตัวชี้วัด') . ' *</div>
	<div class="pad"><table class="kv s2" width="100%" cellpadding="0" cellspacing="0">
		<tr><td class="k" width="40%">' . $h($consUi['typeLabel'] ?? 'กรอบแผนของโครงการ') . ' :</td><td class="v" width="60%">' . $orEmpty($type_name) . '</td></tr>
		' . $s2SetRows . '
		' . (!empty($pm_sdgs_enabled) ? '<tr><td class="k">SDGs :</td><td class="v">' . $s2Sdg . '</td></tr>' : '') . '
	</table></div></div>
	<div class="sec"><div class="banner">ส่วนที่ 3 ข้อมูลโครงการ</div>
	<table class="grid" width="100%" cellpadding="0" cellspacing="0">' . $s3Rows . '</table></div>
	<div class="sec"><div class="banner">ส่วนที่ 4 กิจกรรม/ขั้นตอนการดำเนินงาน / รายละเอียดค่าใช้จ่าย</div>' . $s4Html . '</div>
	<div class="sec"><div class="banner">ส่วนที่ 5 การวัดประเมินผล</div>
	<table class="eval" width="100%" cellpadding="0" cellspacing="0"><tr>
		<td class="el" width="24%">การติดตามประเมินผล</td>
		<td width="76%">1. วิธีการประเมินผล : ' . $plain($pre['evaluate_type'] ?? '') . '<br>
		2. เครื่องมือ : ' . $plain($pre['evaluate_tool'] ?? '') . '<br>
		3. ความเสี่ยงที่คาดว่าจะเกิดขึ้น : ' . $plain($pre['risk_action'] ?? '') . '<br>
		4. วิธีป้องกันความเสี่ยง : ' . $plain($pre['risk_stop'] ?? '') . '</td>
	</tr></table></div>';

	$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'project_print_pdf';
	if (!is_dir($tempDir)) {
		@mkdir($tempDir, 0775, true);
	}
	$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
	$fontData = $defaultFontConfig['fontdata'];
	$mpdf = new \Mpdf\Mpdf([
		'mode' => 'utf-8',
		'format' => 'A4',
		'margin_left' => 14,
		'margin_right' => 14,
		'margin_top' => 14,
		'margin_bottom' => 16,
		'tempDir' => $tempDir,
		'default_font' => 'sarabun',
		'default_font_size' => 9,
		'shrink_tables_to_fit' => 0,
		'keep_table_proportions' => true,
		'fontdata' => $fontData + [
			'sarabun' => [
				'R' => 'THSarabunNew.ttf',
				'I' => 'THSarabunNewItalic.ttf',
				'B' => 'THSarabunNewBold.ttf',
				'BI' => 'THSarabunNewBoldItalic.ttf',
			],
		],
	]);
	$mpdf->SetDisplayMode('fullpage');
	$mpdf->SetTitle($title);
	$mpdf->autoLangToFont = false;
	$mpdf->autoScriptToLang = false;
	$mpdf->useSubstitutions = false;
	if (method_exists($mpdf, 'SetDefaultBodyCSS')) {
		$mpdf->SetDefaultBodyCSS('font-family', 'sarabun');
		$mpdf->SetDefaultBodyCSS('font-size', '9pt');
	}
	try {
		$mpdf->WriteHTML('<html><head><meta charset="utf-8"><style>' . $pdfCss . '</style></head><body>' . $html . '</body></html>');
		$safe = preg_replace('/[\\\\\/:*?"<>|]+/u', '_', $ref_code !== '' ? $ref_code : ('project_' . $project_id));
		$dest = ($isPrintApiMode && $apiPrintDisposition === 'inline')
			? \Mpdf\Output\Destination::INLINE
			: \Mpdf\Output\Destination::DOWNLOAD;
		$mpdf->Output('โครงการ_' . $safe . '.pdf', $dest);
	} catch (Throwable $e) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=utf-8');
		echo 'ไม่สามารถสร้าง PDF ได้';
	}
	exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>พิมพ์โครงการ — <?= $h($title) ?></title>
	<link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,400;0,600;0,700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/fontawesome.css">
	<link rel="stylesheet" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/solid.css">
	<style>
		@font-face{
			font-family:'TH Sarabun New';
			src:url('../../mpdf/vendor/mpdf/mpdf/ttfonts/THSarabunNew.ttf') format('truetype');
			font-weight:400; font-style:normal; font-display:swap;
		}
		@font-face{
			font-family:'TH Sarabun New';
			src:url('../../mpdf/vendor/mpdf/mpdf/ttfonts/THSarabunNewBold.ttf') format('truetype');
			font-weight:700; font-style:normal; font-display:swap;
		}
		@font-face{
			font-family:'TH Sarabun New';
			src:url('../../mpdf/vendor/mpdf/mpdf/ttfonts/THSarabunNewItalic.ttf') format('truetype');
			font-weight:400; font-style:italic; font-display:swap;
		}
		@font-face{
			font-family:'TH Sarabun New';
			src:url('../../mpdf/vendor/mpdf/mpdf/ttfonts/THSarabunNewBoldItalic.ttf') format('truetype');
			font-weight:700; font-style:italic; font-display:swap;
		}
		*{ box-sizing:border-box; }
		html, body{
			font-family:'TH Sarabun New','Sarabun','THSarabunNew',Tahoma,sans-serif;
		}
		body{
			margin:0; background:#eef2f7; color:#111827;
			font-size:9.5pt; line-height:1.4;
		}
		.pm-print-bar{
			position:sticky; top:0; z-index:20;
			display:flex; gap:.5rem; justify-content:flex-end; align-items:center;
			padding:.65rem 1rem; background:#0f172a; color:#fff;
			font-family:'TH Sarabun New','Sarabun',Tahoma,sans-serif;
			font-size:12pt;
		}
		.pm-print-bar button, .pm-print-bar a.btn-pdf{
			font-family:inherit; font-weight:700; border:0; border-radius:.45rem;
			padding:.4rem .9rem; cursor:pointer;
		}
		.pm-print-bar .btn-print{ background:#111; color:#fff; }
		.pm-print-bar .btn-pdf{ background:#111; color:#fff; text-decoration:none; display:inline-block; }
		.pm-print-bar .btn-close{ background:#e5e7eb; color:#111827; }
		.paper{
			max-width:980px; margin:1rem auto 2.5rem; background:#fff;
			padding:22px 32px 36px; box-shadow:0 10px 30px rgba(15,23,42,.12);
		}
		.doc-head{ text-align:center; margin:0 0 .85rem; }
		.doc-head h1{ font-size:12.5pt; margin:0 0 .25rem; font-weight:700; line-height:1.3; color:#111; }
		.doc-head .meta{ color:#111; font-size:10pt; font-weight:700; }
		.card-sec{
			border:1px solid #94a3b8; border-radius:6px; overflow:hidden;
			margin:0 0 .7rem; background:#fff; page-break-inside:avoid;
		}
		.card-sec.allow-break{ page-break-inside:auto; overflow:visible; }
		.card-sec .banner{
			background:transparent; color:#111; font-weight:700; font-size:10pt;
			padding:.35rem .85rem; display:flex; align-items:center; gap:.4rem;
			border-bottom:1px solid #cbd5e1;
		}
		.card-sec .banner i{ color:#111; font-size:.85em; }
		.card-sec .req{ color:#111; }
		.card-pad{ padding:.7rem .9rem .75rem; }
		.g2{ display:flex; gap:1.4rem; }
		.g2 > div{ flex:1; min-width:0; }
		.ginfo{
			display:grid;
			grid-template-columns: 1.12fr .88fr;
			column-gap: 1.4rem;
			align-items: start;
		}
		.ginfo .ginfo-col{
			min-width:0;
			display:grid;
			grid-template-columns: max-content minmax(0,1fr);
			column-gap: .55rem;
			row-gap: .22rem;
			align-items: start;
		}
		.ginfo .ginfo-col + .ginfo-col{
			padding-left: 1.1rem;
			border-left: 1px solid #cbd5e1;
		}
		.ginfo .ginfo-col-right{
			display:block;
		}
		.ginfo .kv{ display:contents; }
		.ginfo .k{
			color:#000; font-weight:400; white-space:nowrap;
			padding: .04rem 0;
		}
		.ginfo .v{
			font-weight:700; color:#111; min-width:0;
			line-height:1.4; padding: .04rem 0;
		}
		.ginfo .ginfo-col-right .kv{
			display:flex;
			align-items:flex-start;
			gap: .45rem;
			padding: .18rem 0;
		}
		.ginfo .ginfo-col-right .k{ flex:0 0 auto; }
		.ginfo .ginfo-col-right .v{ flex:1 1 auto; min-width:0; }
		.ginfo .period{
			display:grid;
			grid-template-columns: 3.1rem minmax(0,1fr);
			column-gap: .35rem;
			row-gap: .08rem;
		}
		.ginfo .period .pk{ color:#000; font-weight:400; }
		.kv{ display:flex; gap:.55rem; padding:.28rem 0; align-items:flex-start; }
		.kv .k{ min-width:12.2rem; color:#000; font-weight:400; flex:0 0 12.2rem; }
		.kv .v{ flex:1; font-weight:700; color:#111; }
		.s2 .kv .k{ min-width:17.2rem; flex-basis:17.2rem; }
		.empty{ color:#9ca3af; font-weight:400; }
		table.grid{ width:100%; border-collapse:collapse; font-size:9.5pt; table-layout:fixed; border:0; }
		table.grid th, table.grid td{ border:1px solid #cbd5e1; padding:.4rem .55rem; vertical-align:top; }
		table.grid tr:first-child th, table.grid tr:first-child td{ border-top:0; }
		table.grid th:first-child, table.grid td:first-child{ border-left:0; }
		table.grid th:last-child, table.grid td:last-child{ border-right:0; }
		table.grid tr:last-child th, table.grid tr:last-child td{ border-bottom:0; }
		table.grid th.lbl{ width:20%; background:#fff; font-weight:700; text-align:left; color:#111; font-size:9pt; line-height:1.3; }
		table.grid td, table.grid td p, table.grid td div, table.grid td span, table.grid td li{
			font-family:inherit; font-size:9.5pt !important; line-height:1.4;
		}
		table.grid td p{ margin:0 0 .25rem; }
		table.grid td p:last-child{ margin-bottom:0; }
		table.grid td.kpi-cell{ padding:0; }
		table.kpi{ width:100%; border-collapse:collapse; font-size:9pt; margin:0; border:0; }
		table.kpi th, table.kpi td{ border:1px solid #cbd5e1; padding:.28rem .4rem; }
		table.kpi tr:first-child th{ border-top:0; }
		table.kpi th:first-child, table.kpi td:first-child{ border-left:0; }
		table.kpi th:last-child, table.kpi td:last-child{ border-right:0; }
		table.kpi tr:last-child td{ border-bottom:0; }
		table.kpi th{ background:transparent; font-weight:700; text-align:center; color:#111; }
		table.kpi td.center, table.grid td.center{ text-align:center; }
		table.act{ width:100%; border-collapse:collapse; table-layout:fixed; font-size:8.5pt; border:0; }
		table.act th, table.act td{ border:1px solid #cbd5e1; padding:.28rem .4rem; vertical-align:top; }
		table.act tr:first-child th{ border-top:0; }
		table.act th:first-child, table.act td:first-child{ border-left:0; }
		table.act th:last-child, table.act td:last-child{ border-right:0; }
		table.act tr:last-child td{ border-bottom:0; }
		table.act thead th{ background:transparent; font-weight:700; text-align:center; color:#111; }
		table.act td.left{ text-align:left; padding-left:.45rem; }
		table.act td.center{ text-align:center; }
		table.act td.num{ text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; padding-right:.55rem; }
		table.act col.act-c{ width:24%; }
		table.act col.tgt-c{ width:12%; }
		table.act col.bud-c{ width:8%; }
		table.act col.mo-c{ width:4.67%; }
		table.act .mo, table.act th.mo{
			text-align:center; font-size:7pt; line-height:1.15;
			padding:.15rem .04rem; overflow:visible;
			word-break:break-word; overflow-wrap:anywhere;
			font-variant-numeric:tabular-nums;
		}
		.act-name{ font-weight:700; }
		.act-sub{ padding-left:.15rem; }
		.foot-src{ padding:.55rem .9rem .3rem; font-size:9pt; }
		.foot-src .k{ color:#000; }
		.total-box{
			margin:.3rem .9rem .8rem; display:inline-block;
			background:transparent; color:#111; font-weight:700;
			padding:.25rem .7rem; border:1px solid #94a3b8; border-radius:4px; font-size:9.5pt;
		}
		.eval-wrap{ display:flex; min-height:4.5rem; }
		.eval-lbl{
			width:22%; display:flex; align-items:center; justify-content:center;
			font-weight:700; border-right:1px solid #cbd5e1; padding:.7rem .8rem; text-align:center;
			font-size:9.5pt;
		}
		.eval-val{ flex:1; padding:.7rem .9rem; }
		.eval-val div{ margin:.22rem 0; }
		@media print{
			html, body{
				background:#fff !important;
				font-size:9.5pt;
				width:auto;
				overflow:visible;
				-webkit-print-color-adjust:exact;
				print-color-adjust:exact;
			}
			.pm-print-bar{ display:none !important; }
			.paper{
				box-shadow:none; margin:0; max-width:none; width:auto;
				padding:2mm 0 6mm;
			}
			.card-sec, .card-sec.allow-break{
				overflow:visible !important;
				page-break-inside:avoid;
			}
			.card-pad{ padding:.7rem .7rem .8rem; }
			.ginfo{
				display:flex !important;
				align-items:flex-start;
				column-gap:.75rem;
				width:100%;
			}
			.ginfo .ginfo-col{
				display:block !important;
				flex:1 1 0;
				min-width:0;
				width:auto;
			}
			.ginfo .ginfo-col + .ginfo-col,
			.ginfo .ginfo-col-right{
				padding-left:.7rem;
				margin-left:0;
			}
			.ginfo .kv,
			.ginfo .ginfo-col-right .kv{
				display:flex !important;
				flex-wrap:nowrap;
				align-items:flex-start;
				gap:.35rem;
				padding:.12rem 0;
			}
			.ginfo .k,
			.ginfo .ginfo-col-right .k{
				white-space:normal;
				flex:0 0 7.6rem;
				max-width:7.6rem;
				width:7.6rem;
			}
			.ginfo .v,
			.ginfo .ginfo-col-right .v{
				flex:1 1 auto;
				min-width:0;
				overflow-wrap:anywhere;
				word-break:break-word;
			}
			.s2 .kv{ display:flex !important; }
			.s2 .kv .k{
				min-width:0;
				flex:0 0 12.5rem;
				max-width:12.5rem;
			}
			.s2 .kv .v{ min-width:0; overflow-wrap:anywhere; }
			.card-sec .banner{ background:transparent !important; color:#111 !important; }
			.total-box{ background:transparent !important; color:#111 !important; }
			table.act{ font-size:8pt; }
			table.act .mo, table.act th.mo{ font-size:6.8pt; padding:.1rem .02rem; }
			a[href]:after{ content:none !important; }
		}
		@page{ size:A4 portrait; margin:10mm 8mm; }
	</style>
</head>
<body>
	<div class="pm-print-bar">
		<a class="btn-pdf" href="?page=project_manage&amp;op=print_project_pdf&amp;project_id=<?= (int)$project_id ?>">PDF</a>
		<button type="button" class="btn-print" onclick="window.print()">พิมพ์</button>
		<button type="button" class="btn-close" onclick="window.close()">ปิด</button>
	</div>
	<div class="paper">
		<div class="doc-head">
			<h1><?= $name_project !== '' ? $h($name_project) : '<span class="empty">ยังไม่ได้ตั้งชื่อโครงการ</span>' ?></h1>
			<div class="meta">ปีงบประมาณ : <?= $year_project !== '' ? $h($year_project) : '-' ?> | รหัสโครงการ : <?= $ref_code !== '' ? $h($ref_code) : '-' ?></div>
		</div>

		<div class="card-sec">
			<div class="banner"><i class="fas fa-info-circle"></i> ส่วนที่ 1 ข้อมูลทั่วไป</div>
			<div class="card-pad">
				<div class="ginfo">
					<div class="ginfo-col">
						<div class="kv"><div class="k">ผู้รับผิดชอบโครงการ :</div><div class="v"><?= $orEmpty($headTxt) ?></div></div>
						<div class="kv"><div class="k">ผู้ประสานงาน :</div><div class="v"><?= $orEmpty($coordTxt) ?></div></div>
						<div class="kv"><div class="k">ผู้ร่วมโครงการ (ถ้ามี) :</div><div class="v"><?= !empty($personTxts) ? $h(implode(', ', $personTxts)) : '-' ?></div></div>
						<div class="kv"><div class="k">หน่วยงานหลัก :</div><div class="v"><?= $orEmpty($unitMain) ?></div></div>
						<div class="kv"><div class="k">หน่วยงานร่วม (ถ้ามี) :</div><div class="v"><?= !empty($unitCos) ? $h(implode(', ', $unitCos)) : '-' ?></div></div>
					</div>
					<div class="ginfo-col ginfo-col-right">
						<div class="kv">
							<div class="k">ระยะเวลาดำเนินโครงการ :</div>
							<div class="v">
								<?php if ($startTxt === '' && $endTxt === ''): ?>
									-
								<?php else: ?>
								<div class="period">
									<span class="pk">เริ่ม</span><span><?= $startTxt !== '' ? $h($startTxt) : '-' ?></span>
									<span class="pk">สิ้นสุด</span><span><?= $endTxt !== '' ? $h($endTxt) : '-' ?></span>
								</div>
								<?php endif ?>
							</div>
						</div>
						<div class="kv"><div class="k">พื้นที่ดำเนินโครงการ :</div><div class="v"><?= $orEmpty($location) ?></div></div>
						<div class="kv"><div class="k">หมายเหตุ (ถ้ามี) :</div><div class="v"><?= $orEmpty($pre['general_note'] ?? '') ?></div></div>
					</div>
				</div>
			</div>
		</div>

		<div class="card-sec s2">
			<div class="banner"><i class="fas fa-link"></i> ส่วนที่ 2 <?= $h($consUi['sectionTitle'] ?? 'ความเชื่อมโยงกับแผนและตัวชี้วัด') ?> <span class="req">*</span></div>
			<div class="card-pad">
				<div class="kv"><div class="k"><?= $h($consUi['typeLabel'] ?? 'กรอบแผนของโครงการ') ?> :</div><div class="v"><?= $orEmpty($type_name) ?></div></div>
				<?php if (!empty($cons_display_sets)): ?>
					<?php foreach ($cons_display_sets as $dSet): ?>
				<div class="kv"><div class="k"><?= $h((string)($dSet['label'] ?? $consUi['level2Label'])) ?> :</div><div class="v"><?= $orEmpty($dSet['name'] ?? '') ?></div></div>
				<div class="kv"><div class="k">ตัวชี้วัดเชิงพันธกิจ/ตัวชี้วัดเชิงกลยุทธ์ (KR) :</div><div class="v"><?php
					if (empty($dSet['kpis'])) {
						echo '-';
					} else {
						echo $h(implode(', ', $dSet['kpis']));
					}
				?></div></div>
					<?php endforeach ?>
				<?php else: ?>
				<div class="kv"><div class="k"><?= $h($consUi['level2Label'] ?? 'ด้าน/แผนปฏิบัติการ (AP)') ?> :</div><div class="v"><?= $orEmpty($level2_name) ?></div></div>
				<div class="kv"><div class="k">ตัวชี้วัดเชิงพันธกิจ/ตัวชี้วัดเชิงกลยุทธ์ (KR) :</div><div class="v"><?php
					if (empty($kpi_names)) {
						echo '-';
					} else {
						echo $h(implode(', ', $kpi_names));
					}
				?></div></div>
				<?php endif; ?>
				<?php if (!empty($pm_sdgs_enabled)): ?>
				<div class="kv"><div class="k">SDGs :</div><div class="v"><?php
					if (empty($sdg_names)) {
						echo '-';
					} else {
						echo $h(implode(', ', $sdg_names));
					}
				?></div></div>
				<?php endif; ?>
			</div>
		</div>

		<div class="card-sec">
			<div class="banner"><i class="fas fa-clipboard-list"></i> ส่วนที่ 3 ข้อมูลโครงการ</div>
			<table class="grid">
				<?php foreach ($s3_fields as $i => $f): ?>
				<tr>
					<th class="lbl">3.<?= (int)$i + 1 ?> <?= $h($f['label']) ?></th>
					<td><?= $rich($f['val']) ?></td>
				</tr>
				<?php endforeach ?>
				<tr>
					<th class="lbl">3.5 ตัวชี้วัดเป้าหมายโครงการ</th>
					<td class="kpi-cell">
						<?php if (empty($kpis)): ?>
							<span class="empty">-</span>
						<?php else: ?>
						<table class="kpi">
							<thead>
								<tr>
									<th>ตัวชี้วัดของโครงการ (หน่วยนับ)</th>
									<th style="width:90px;">ค่าเป้าหมาย</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($kpis as $i => $pk):
								$kpi_name_show = trim((string)($pk['kpi_name'] ?? ''));
								$kpi_unit_show = trim((string)($pk['kpi_unit'] ?? ''));
								if ($kpi_name_show === '' && !empty($pk['code_kpi']) && function_exists('getKPI') && isset($db_KPI)) {
									$kpiRow = getKPI($db_KPI, $pk['code_kpi']);
									$kpi_name_show = trim((string)($kpiRow['name'] ?? ''));
								}
								if ($kpi_unit_show === '' && !empty($pk['code_unit']) && function_exists('getSetUnit') && isset($db_KPI)) {
									$unitRow = getSetUnit($db_KPI, $pk['code_unit']);
									$kpi_unit_show = trim((string)($unitRow['name'] ?? ''));
								}
								$mon_sum = 0.0;
								foreach ($monthCols as $mc) {
									$mon_sum += (float)($pk[$mc] ?? 0);
								}
								$w = (string)($pk['kpi_weight'] ?? '');
								$wShow = ($w !== '' && is_numeric($w)) ? $money($w) : $w;
								if ($wShow === '') {
									$wShow = '0';
								}
								$label = ((int)$i + 1) . '. ' . ($kpi_name_show !== '' ? $kpi_name_show : '-');
								if ($kpi_unit_show !== '') {
									$label .= ' (' . $kpi_unit_show . ')';
								}
								$label .= ' (ค่าน้ำหนัก : ' . $wShow . ')';
							?>
								<tr>
									<td><?= $h($label) ?></td>
									<td class="center"><?= $mon_sum > 0 ? $h($money($mon_sum)) : '-' ?></td>
								</tr>
							<?php endforeach ?>
							</tbody>
						</table>
						<?php endif ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="card-sec allow-break">
			<div class="banner"><i class="fas fa-tasks"></i> ส่วนที่ 4 กิจกรรม/ขั้นตอนการดำเนินงาน / รายละเอียดค่าใช้จ่าย</div>
			<?php if (empty($acts)): ?>
				<div class="card-pad"><span class="empty">-</span></div>
			<?php else: ?>
				<table class="act">
					<colgroup>
						<col class="act-c">
						<col class="tgt-c">
						<col class="bud-c">
						<?php foreach ($monthCols as $_mc): ?><col class="mo-c"><?php endforeach ?>
					</colgroup>
					<thead>
						<tr>
							<th>กิจกรรม</th>
							<th>กลุ่มเป้าหมาย</th>
							<th>งบประมาณ (บาท)</th>
							<th colspan="12">แผนการดำเนินการ</th>
						</tr>
						<tr>
							<th></th><th></th><th></th>
							<?php foreach ($monthCols as $mc): ?><th class="mo"><?= $h($monthMap[$mc]) ?></th><?php endforeach ?>
						</tr>
					</thead>
					<tbody>
					<?php
					$grand = 0.0;
					foreach ($acts as $a):
						$actId = (int)($a['id'] ?? 0);
						$actNo = trim((string)($a['activity_no'] ?? ''));
						$actNm = trim((string)($a['activity_name'] ?? ''));
						$wRaw = (string)($a['activity_weight'] ?? '');
						$wShow = ($wRaw !== '' && is_numeric($wRaw)) ? $money($wRaw) : $wRaw;
						$actLabel = trim($actNo . '. ' . $actNm);
						if ($wShow !== '') {
							$actLabel .= ' (ค่าน้ำหนัก : ' . $wShow . ')';
						}
						$targets = function_exists('getProjectActivityTargetList') ? getProjectActivityTargetList($db_Project, $actId) : [];
						$tHtml = [];
						foreach ($targets as $tix => $t) {
							$set = function_exists('getSetTarget') ? getSetTarget($db_Project, $t['code_target'] ?? '') : null;
							$tn = is_array($set) ? trim((string)($set['name'] ?? '')) : '';
							$unitT = is_array($set) ? trim((string)($set['unit'] ?? $set['name_unit'] ?? '')) : '';
							$amt = trim((string)($t['target_amount'] ?? ''));
							$amtShow = ($amt !== '' && is_numeric($amt)) ? $money($amt) : $amt;
							if ($tn === '' && $amtShow === '') {
								continue;
							}
							$line = ((int)$tix + 1) . '. ' . ($tn !== '' ? $tn : '-');
							if ($unitT !== '' && strpos($tn, '(') === false) {
								$line .= ' (' . $unitT . ')';
							}
							if ($amtShow !== '') {
								$line .= ' : ' . $amtShow;
							}
							$tHtml[] = $h($line);
						}
						$items = function_exists('getProjectActivityBudgetList') ? getProjectActivityBudgetList($db_Project, $actId) : [];
						$actTotal = 0.0;
						foreach ($items as $it) {
							$actTotal += (float)($it['total'] ?? 0);
						}
						$grand += $actTotal;
					?>
						<tr>
							<td class="left"><span class="act-name"><?= $h($actLabel !== '.' ? $actLabel : '-') ?></span></td>
							<td class="left"><?= !empty($tHtml) ? implode('<br>', $tHtml) : '-' ?></td>
							<td class="num"><?= $actTotal > 0 ? $h($money($actTotal)) : '0' ?></td>
							<?php foreach ($monthCols as $mc): ?>
								<td class="mo"><?= (!empty($a[$mc]) && (string)$a[$mc] === '1') ? 'x' : '' ?></td>
							<?php endforeach ?>
						</tr>
						<?php foreach ($items as $it):
							$calc = $calcText(is_array($it) ? $it : []);
							$catName = '';
							if (!empty($it['code_expenses']) && function_exists('getSetExpenses')) {
								$exCat = getSetExpenses($db_Project, (string)$it['code_expenses']);
								$catName = is_array($exCat) ? trim((string)($exCat['name'] ?? '')) : '';
							}
							$detailName = '';
							if (!empty($it['code_expenses_detail']) && function_exists('getSetExpensesDetailByID')) {
								$exd = getSetExpensesDetailByID($db_Project, $it['code_expenses_detail']);
								$detailName = is_array($exd) ? trim((string)($exd['name'] ?? '')) : '';
							}
							$desc = function_exists('pmProjectActivityBudgetDescValue')
								? pmProjectActivityBudgetDescValue(is_array($it) ? $it : [])
								: trim((string)($it['detail'] ?? $it['budget_item'] ?? ''));
							$paren = $desc !== '' ? $desc : $detailName;
							if ($catName !== '' && $paren !== '' && $paren !== $catName) {
								$head = $catName . '(' . $paren . ')';
							} elseif ($paren !== '') {
								$head = $paren;
							} else {
								$head = $catName;
							}
							$showLine = trim($head . ($calc !== '' ? ' ' . $calc : ''));
							$plan = (function_exists('getProjectActivityBudgetDetail'))
								? getProjectActivityBudgetDetail($db_Project, $actId, (int)($it['id'] ?? 0), true)
								: null;
							if (!is_array($plan)) {
								$plan = [];
							}
							if ($showLine === '') {
								continue;
							}
						?>
						<tr>
							<td class="left act-sub">• <?= $h($showLine) ?></td>
							<td></td>
							<td class="num"><?= $h($money($it['total'] ?? 0)) ?></td>
							<?php foreach ($monthCols as $mc):
								$mv = (float)($plan[$mc] ?? 0);
							?><td class="mo"><?= $mv > 0 ? $h($moneyMo($mv)) : '' ?></td><?php endforeach ?>
						</tr>
						<?php endforeach; ?>
					<?php endforeach ?>
					</tbody>
				</table>
				<div class="foot-src"><span class="k">แหล่งงบประมาณ :</span> <?= $orEmpty($budgetSrc !== '-' ? $budgetSrc : '') ?></div>
				<div class="total-box">รวมงบประมาณค่าใช้จ่าย : <?= $h($money($grand)) ?> บาท</div>
			<?php endif ?>
		</div>

		<div class="card-sec">
			<div class="banner"><i class="fas fa-clipboard-check"></i> ส่วนที่ 5 การวัดประเมินผล</div>
			<div class="eval-wrap">
				<div class="eval-lbl">การติดตามประเมินผล</div>
				<div class="eval-val">
					<div>1. วิธีการประเมินผล : <?= $plain($pre['evaluate_type'] ?? '') ?></div>
					<div>2. เครื่องมือ : <?= $plain($pre['evaluate_tool'] ?? '') ?></div>
					<div>3. ความเสี่ยงที่คาดว่าจะเกิดขึ้น : <?= $plain($pre['risk_action'] ?? '') ?></div>
					<div>4. วิธีป้องกันความเสี่ยง : <?= $plain($pre['risk_stop'] ?? '') ?></div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
<?php
exit;