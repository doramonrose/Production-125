<?php
/**
 * Dashboard (User)
 * - สรุปโครงการในขอบเขตของผู้ใช้ (เจ้าของโครงการ / Admin หน่วยงาน)
 * - ลิงก์ไปหน้าจัดการ / อนุมัติที่เกี่ยวข้อง
 *
 * หมายเหตุ: `page.php` ของ User จะ include ไฟล์นี้ภายใต้ layout อยู่แล้ว
 */

if (!isset($db_Project) || !$db_Project) {
	include_once __DIR__ . '/../../config/config_db.php';
}
include_once __DIR__ . '/../fn/main_project.php';

if (function_exists('mysqli_report')) {
	@mysqli_report(MYSQLI_REPORT_OFF);
}

$staff_id = (int)($row['id'] ?? 0);
$staff_code = (string)($row['code'] ?? '');
$code_unit = (string)($row['unit_code'] ?? '');
$staff_fname = trim((string)($row['fname'] ?? ''));
$staff_lname = trim((string)($row['lname'] ?? ''));
$staff_display = trim($staff_fname . ' ' . $staff_lname);
if ($staff_display === '') {
	$staff_display = 'ผู้ใช้งาน';
}

$years_select = isset($_GET['years_select']) ? trim((string)$_GET['years_select']) : '';
try {
	if ($years_select === '') {
		$qy = @mysqli_query($db_Project, "SELECT budget_year_name FROM budget_set_year WHERE budget_year_status='1' ORDER BY budget_year_name DESC LIMIT 1");
		$ry = $qy ? mysqli_fetch_assoc($qy) : null;
		$years_select = (string)($ry['budget_year_name'] ?? '');
	}
} catch (Throwable $e) {
	$years_select = '';
}
$years_select = trim((string)$years_select);
if ($years_select === '') {
	$years_select = function_exists('getCurrentBudgetYear')
		? getCurrentBudgetYear()
		: (string)((int)date('Y') + 543 + ((int)date('n') >= 10 ? 1 : 0));
}

$yearOptions = [];
try {
	$qy2 = @mysqli_query($db_Project, "SELECT budget_year_name FROM budget_set_year WHERE budget_year_status='1' ORDER BY budget_year_name DESC");
	while ($qy2 && ($r = mysqli_fetch_assoc($qy2))) {
		$y = (string)($r['budget_year_name'] ?? '');
		if ($y !== '') {
			$yearOptions[] = $y;
		}
	}
} catch (Throwable $e) {
	$yearOptions = [];
}
if (empty($yearOptions)) {
	$yearOptions = [$years_select];
}

$is_unit_admin = (function_exists('isUnitAdmin'))
	? isUnitAdmin($db_Project, $staff_code, $code_unit, (int)$staff_id)
	: false;

$project_list = ($staff_id > 0 && function_exists('getManageableProjectList'))
	? getManageableProjectList($db_Project, $years_select, $staff_id, $staff_code, $is_unit_admin, $code_unit)
	: [];

$toFloat = static function ($v): float {
	if ($v === null || $v === '') {
		return 0.0;
	}
	return is_numeric($v) ? (float)$v : 0.0;
};

$cnt_total = 0;
$cnt_draft = 0;
$cnt_sent = 0;
$cnt_wait_include = 0;
$cnt_meeting = 0;
$cnt_included_plan = 0;
$cnt_operate = 0;
$cnt_operate_approved = 0;
$cnt_returned = 0;
$cnt_rejected = 0;

$sum_total_amount = 0.0;
$sum_draft_pipeline = 0.0;
$sum_included_plan_budget = 0.0;
$sum_doing = 0.0;
$sum_approved = 0.0;

$recent_projects = [];
$cnt_pending_operate_certify = 0;

foreach ($project_list as $pr) {
	$cnt_total++;
	$pid = (int)($pr['id'] ?? 0);
	$st = (string)($pr['status_project'] ?? '0');
	$nr = trim((string)($pr['notify_result'] ?? ''));
	$b = $toFloat($pr['budget_total'] ?? 0);
	$sum_total_amount += $b;

	switch ($st) {
		case '0':
			$cnt_draft++;
			$sum_draft_pipeline += $b;
			break;
		case '2':
			$cnt_sent++;
			$sum_draft_pipeline += $b;
			break;
		case '3':
			$cnt_wait_include++;
			$sum_draft_pipeline += $b;
			break;
		case '4':
			if ($nr === 'บรรจุแผน') {
				$cnt_included_plan++;
				$sum_included_plan_budget += $b;
			} else {
				$cnt_meeting++;
				$sum_draft_pipeline += $b;
			}
			break;
		case '1':
			$cnt_operate++;
			$isApprovedOperate = false;
			if ($pid > 0 && function_exists('pm_is_operate_final_approved') && $nr !== 'บรรจุแผนและแก้ไข') {
				$isApprovedOperate = pm_is_operate_final_approved($db_Project, $pid, $pr);
			}
			if ($isApprovedOperate) {
				$cnt_operate_approved++;
				$sum_approved += $b;
			} else {
				$sum_doing += $b;
			}
			break;
		case '8':
			$cnt_returned++;
			$sum_draft_pipeline += $b;
			break;
		case '9':
			$cnt_rejected++;
			$sum_draft_pipeline += $b;
			break;
	}

	$recent_projects[] = [
		'id' => $pid,
		'code' => (string)($pr['code_pre_project'] ?? ''),
		'name' => (string)($pr['name_project'] ?? ''),
		'status_project' => $st,
		'notify_result' => $nr,
		'user_fix_note' => (string)($pr['user_fix_note'] ?? ''),
		'operate_submitter_reply_note' => (string)($pr['operate_submitter_reply_note'] ?? ''),
		'result_report_submitted_at' => (string)($pr['result_report_submitted_at'] ?? ''),
		'result_report_completed_at' => (string)($pr['result_report_completed_at'] ?? ''),
		'midyear_request' => (int)($pr['midyear_request'] ?? 0),
		'budget' => $b,
		'dates' => (string)($pr['dates'] ?? ''),
	];
}

usort($recent_projects, static function ($a, $b) {
	$ta = strtotime((string)($a['dates'] ?? '')) ?: 0;
	$tb = strtotime((string)($b['dates'] ?? '')) ?: 0;
	if ($ta === $tb) {
		return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
	}
	return $tb <=> $ta;
});
$recent_projects = array_slice($recent_projects, 0, 8);

$cnt_pending_include_certify = 0;
if ($staff_id > 0 && $db_Project) {
	$yearsEsc = mysqli_real_escape_string($db_Project, $years_select);
	$sidEsc = (int)$staff_id;
	$pendingIncludeSql = "
		SELECT COUNT(DISTINCT ir.id) AS cnt
		FROM project_include_request ir
		INNER JOIN project_include_request_certifier c
			ON c.request_id = ir.id AND c.staff_id = '" . $sidEsc . "'
		INNER JOIN project_include_request_item i ON i.request_id = ir.id
		INNER JOIN pre_project p ON p.id = i.project_id AND p.status = '1' AND p.year_project = '" . $yearsEsc . "'
		WHERE (ir.status IS NULL OR ir.status <> 'rejected')
		  AND NOT EXISTS (
			SELECT 1 FROM project_include_request_certifier_decision d
			WHERE d.request_id = ir.id AND d.decision = 'rejected'
		  )
		  AND NOT EXISTS (
			SELECT 1
			FROM project_include_request_item i2
			INNER JOIN pre_project p2 ON p2.id = i2.project_id
			WHERE i2.request_id = ir.id
			  AND (p2.status <> '1' OR p2.status_project <> '4')
		  )
		  AND NOT EXISTS (
			SELECT 1 FROM project_include_request_certifier_decision d
			WHERE d.request_id = ir.id AND d.sort_order = c.sort_order
			  AND IFNULL(d.decision,'') <> ''
		  )
		  AND NOT EXISTS (
			SELECT 1
			FROM project_include_request_certifier c2
			LEFT JOIN project_include_request_certifier_decision d2
				ON d2.request_id = c2.request_id AND d2.sort_order = c2.sort_order
			WHERE c2.request_id = ir.id
			  AND c2.sort_order < c.sort_order
			  AND IFNULL(d2.decision,'') <> 'approved'
		  )
	";
	try {
		$qPend = @mysqli_query($db_Project, $pendingIncludeSql);
		$rPend = $qPend ? mysqli_fetch_assoc($qPend) : null;
		$cnt_pending_include_certify = (int)($rPend['cnt'] ?? 0);
	} catch (Throwable $e) {
		$cnt_pending_include_certify = 0;
	}
}

$cnt_pending_operate_certify = 0;
if ($staff_id > 0 && $db_Project) {
	$yearsEscOp = mysqli_real_escape_string($db_Project, $years_select);
	$qOpPend = @mysqli_query(
		$db_Project,
		"SELECT p.id, p.operate_certifiers_json, p.operate_approvers_json, p.status_project, IFNULL(p.notify_result,'') AS notify_result
		 FROM pre_project p
		 WHERE p.status='1'
		   AND p.year_project='" . $yearsEscOp . "'
		   AND (
		        p.status_project='1'
		        OR (p.status_project='8' AND IFNULL(p.notify_result,'')='บรรจุแผน')
		   )
		   AND TRIM(IFNULL(p.operate_certifiers_json,'')) <> ''"
	);
	while ($qOpPend && ($opRow = mysqli_fetch_assoc($qOpPend))) {
		$opPid = (int)($opRow['id'] ?? 0);
		$certArr = json_decode((string)($opRow['operate_certifiers_json'] ?? ''), true);
		if ($opPid <= 0 || !is_array($certArr) || empty($certArr)) {
			continue;
		}
		$certList = [];
		$seq = 0;
		foreach ($certArr as $c) {
			if (!is_array($c)) {
				continue;
			}
			$seq++;
			$so = (int)($c['sort_order'] ?? 0);
			if ($so <= 0) {
				$so = $seq;
			}
			$sidC = (int)($c['staff_id'] ?? 0);
			if ($sidC > 0) {
				$certList[$so] = $sidC;
			}
		}
		if (empty($certList)) {
			continue;
		}
		$decMap = [];
		$qDec = @mysqli_query(
			$db_Project,
			"SELECT sort_order, decision FROM project_operate_certifier_decision WHERE project_id='" . $opPid . "'"
		);
		while ($qDec && ($dr = mysqli_fetch_assoc($qDec))) {
			$dso = (int)($dr['sort_order'] ?? 0);
			if ($dso > 0) {
				$decMap[$dso] = (string)($dr['decision'] ?? '');
			}
		}
		$allSorts = array_keys($certList);
		sort($allSorts, SORT_NUMERIC);
		$certsAllPend = !empty($allSorts);
		$countedPend = false;
		foreach ($allSorts as $so) {
			$dec = (string)($decMap[$so] ?? '');
			if ($dec !== 'approved') {
				$certsAllPend = false;
			}
			if ($countedPend) {
				continue;
			}
			if ($dec === 'approved') {
				continue;
			}
			if ($dec === 'rejected') {
				$countedPend = true;
				break;
			}
			if ((int)($certList[$so] ?? 0) === $staff_id) {
				$cnt_pending_operate_certify++;
			}
			$countedPend = true;
		}
		if ($certsAllPend && function_exists('pm_parse_operate_chain_json')) {
			$apprRawPend = trim((string)($opRow['operate_approvers_json'] ?? ''));
			$apprListPend = pm_parse_operate_chain_json($apprRawPend);
			if (!empty($apprListPend)) {
				$apprDecPend = function_exists('pm_load_operate_decision_map')
					? pm_load_operate_decision_map($db_Project, $opPid, 'project_operate_approver_decision')
					: [];
				$apprSortsPend = array_keys($apprListPend);
				sort($apprSortsPend, SORT_NUMERIC);
				foreach ($apprSortsPend as $soA) {
					$decA = (string)($apprDecPend[$soA]['decision'] ?? '');
					if ($decA === 'approved') {
						continue;
					}
					if ($decA === 'rejected') {
						break;
					}
					if ((int)($apprListPend[$soA]['staff_id'] ?? 0) === $staff_id) {
						$cnt_pending_operate_certify++;
					}
					break;
				}
			}
		}
	}
}

$cnt_pending_certify_total = $cnt_pending_include_certify + $cnt_pending_operate_certify;
$cnt_need_action = $cnt_draft + $cnt_returned;

// --- KPI ตัวชี้วัด (project_kpi + ความสอดคล้อง KR) ---
if (!isset($db_KPI) || !$db_KPI) {
	include_once __DIR__ . '/../../config/config_db.php';
}
if (!function_exists('getKPI')) {
	include_once __DIR__ . '/../../KPI/fn/main_kpi.php';
}

$project_ids = [];
$project_meta = [];
foreach ($project_list as $pr) {
	$pid = (int)($pr['id'] ?? 0);
	if ($pid <= 0) {
		continue;
	}
	$project_ids[] = $pid;
	$project_meta[$pid] = [
		'code' => (string)($pr['code_pre_project'] ?? ''),
		'name' => (string)($pr['name_project'] ?? ''),
	];
}

$cnt_kpi_total = 0;
$cnt_kr_strategic = 0;
$cnt_projects_with_kpi = 0;
$cnt_projects_with_consistency = 0;
$cnt_projects_no_kpi = 0;
$sum_kpi_weight = 0.0;
$kpi_rows = [];
$kpi_month_totals = array_fill(0, 12, 0.0);
$kpi_per_project = [];
$projects_with_kpi_set = [];

$month_keys_fiscal = ['mon_10', 'mon_11', 'mon_12', 'mon_01', 'mon_02', 'mon_03', 'mon_04', 'mon_05', 'mon_06', 'mon_07', 'mon_08', 'mon_09'];
$month_labels_fiscal = ['ต.ค.', 'พ.ย.', 'ธ.ค.', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.'];

$udb_kpi_label = static function (array $pk) use ($db_KPI): array {
	$kpi_name = trim((string)($pk['kpi_name'] ?? ''));
	$kpi_unit = trim((string)($pk['kpi_unit'] ?? ''));
	if ($kpi_name === '' && !empty($pk['code_kpi']) && $db_KPI && function_exists('getKPI')) {
		$kpiRow = getKPI($db_KPI, $pk['code_kpi']);
		$kpi_name = trim((string)($kpiRow['name'] ?? ''));
	}
	if ($kpi_unit === '' && !empty($pk['code_unit']) && $db_KPI && function_exists('getSetUnit')) {
		$unitRow = getSetUnit($db_KPI, $pk['code_unit']);
		$kpi_unit = trim((string)($unitRow['name'] ?? ''));
	}
	return [$kpi_name !== '' ? $kpi_name : '-', $kpi_unit !== '' ? $kpi_unit : '-'];
};

$udb_kpi_annual_target = static function (array $pk) use ($toFloat, $month_keys_fiscal): float {
	$sum = 0.0;
	foreach ($month_keys_fiscal as $mk) {
		$sum += $toFloat($pk[$mk] ?? 0);
	}
	return $sum;
};

if (!empty($project_ids) && $db_Project) {
	$in = implode(',', array_map('intval', $project_ids));
	$qKpi = @mysqli_query(
		$db_Project,
		"SELECT pk.*
		 FROM project_kpi pk
		 WHERE pk.status <> '99' AND pk.code_pre_project IN ($in)
		 ORDER BY pk.code_pre_project ASC, pk.id ASC"
	);
	while ($qKpi && ($pk = mysqli_fetch_assoc($qKpi))) {
		$cnt_kpi_total++;
		$pid = (int)($pk['code_pre_project'] ?? 0);
		$projects_with_kpi_set[$pid] = true;
		$weight = $toFloat($pk['kpi_weight'] ?? 0);
		$sum_kpi_weight += $weight;
		[$kName, $kUnit] = $udb_kpi_label($pk);
		$annual = $udb_kpi_annual_target($pk);
		foreach ($month_keys_fiscal as $mi => $mk) {
			$kpi_month_totals[$mi] += $toFloat($pk[$mk] ?? 0);
		}
		$kpi_per_project[$pid] = ($kpi_per_project[$pid] ?? 0) + 1;
		$meta = $project_meta[$pid] ?? ['code' => '', 'name' => ''];
		$kpi_rows[] = [
			'project_id' => $pid,
			'project_code' => $meta['code'],
			'project_name' => $meta['name'],
			'kpi_name' => $kName,
			'kpi_unit' => $kUnit,
			'weight' => $weight,
			'annual_target' => $annual,
		];
	}
	$cnt_projects_with_kpi = count($projects_with_kpi_set);
	$cnt_projects_no_kpi = max(0, count($project_ids) - $cnt_projects_with_kpi);

	$qCons = @mysqli_query(
		$db_Project,
		"SELECT COUNT(DISTINCT pc.project_id) AS cnt
		 FROM project_consistency pc
		 WHERE pc.status <> '99' AND pc.project_id IN ($in)"
	);
	$rCons = $qCons ? mysqli_fetch_assoc($qCons) : null;
	$cnt_projects_with_consistency = (int)($rCons['cnt'] ?? 0);

	$qKr = @mysqli_query(
		$db_Project,
		"SELECT COUNT(pck.id) AS cnt
		 FROM project_consistency_kpi pck
		 INNER JOIN project_consistency pc ON pc.id = pck.code_project_consistency AND pc.status <> '99'
		 WHERE pck.status <> '99' AND pc.project_id IN ($in)"
	);
	$rKr = $qKr ? mysqli_fetch_assoc($qKr) : null;
	$cnt_kr_strategic = (int)($rKr['cnt'] ?? 0);
}

$avg_kpi_weight = ($cnt_kpi_total > 0) ? ($sum_kpi_weight / $cnt_kpi_total) : 0.0;
$kpi_table_rows = array_slice($kpi_rows, 0, 12);

$kpi_chart_projects = [];
$kpi_chart_counts = [];
if (!empty($kpi_per_project)) {
	arsort($kpi_per_project);
	$chartIdx = 0;
	foreach ($kpi_per_project as $pid => $kcnt) {
		if ($chartIdx >= 8) {
			break;
		}
		$meta = $project_meta[(int)$pid] ?? ['code' => '', 'name' => ''];
		$lbl = trim((string)($meta['code'] ?? ''));
		if ($lbl === '') {
			$lbl = (string)($meta['name'] ?? '');
			if (function_exists('mb_substr')) {
				$lbl = mb_substr($lbl, 0, 22);
			} else {
				$lbl = substr($lbl, 0, 22);
			}
		}
		$kpi_chart_projects[] = $lbl !== '' ? $lbl : ('#' . (int)$pid);
		$kpi_chart_counts[] = (int)$kcnt;
		$chartIdx++;
	}
}

$udb_status_label = static function ($rowOrSt, $nr = '') use ($db_Project): array {
	if (function_exists('pm_resolve_ab_status')) {
		$row = is_array($rowOrSt) ? $rowOrSt : [
			'status_project' => (string)$rowOrSt,
			'notify_result' => (string)$nr,
		];
		$ab = pm_resolve_ab_status($row, ['db' => $db_Project]);
		return [(string)($ab['label'] ?? '-'), (string)($ab['mod'] ?? 'unknown'), (string)($ab['icon'] ?? 'fa-minus-circle')];
	}
	$st = is_array($rowOrSt) ? (string)($rowOrSt['status_project'] ?? '') : (string)$rowOrSt;
	$nr = is_array($rowOrSt) ? trim((string)($rowOrSt['notify_result'] ?? '')) : (string)$nr;
	$txt = '-';
	$mod = 'unknown';
	$icon = 'fa-minus-circle';
	if ($st === '0') {
		$txt = 'ร่าง';
		$mod = 'draft';
		$icon = 'fa-edit';
	} elseif ($st === '2') {
		$txt = 'ส่งร่างโครงการ';
		$mod = 'sent';
		$icon = 'fa-paper-plane';
	} elseif ($st === '3') {
		$txt = 'รอบรรจุโครงการ';
		$mod = 'include';
		$icon = 'fa-hourglass-half';
	} elseif ($st === '4') {
		$txt = ($nr === 'บรรจุแผน') ? 'บรรจุแผน' : 'นำเข้าที่ประชุม';
		$mod = ($nr === 'บรรจุแผน') ? 'included' : 'meeting';
		$icon = 'fa-file-signature';
	} elseif ($st === '1') {
		$txt = 'ขอดำเนินการ';
		$mod = 'operate';
		$icon = 'fa-check-circle';
	} elseif ($st === '8') {
		$txt = 'ตีกลับให้แก้ไข';
		$mod = 'returned';
		$icon = 'fa-undo';
	} elseif ($st === '9') {
		$txt = 'ไม่อนุมัติ';
		$mod = 'rejected';
		$icon = 'fa-times-circle';
	}
	return [$txt, $mod, $icon];
};

$linkManageDraft = '?page=project_manage&years_select=' . rawurlencode($years_select) . '&project_group=draft';
$linkManagePlan = '?page=project_manage&years_select=' . rawurlencode($years_select) . '&project_group=plan';
$linkApprove = '?page=project_approve&years_select=' . rawurlencode($years_select) . '&project_group=pending';
$linkAll = '?page=project_all&years_select=' . rawurlencode($years_select);
?>

<style>
	.udb-card{
		border-radius: 1rem;
		border: 1px solid rgba(148,163,184,.35);
		box-shadow: 0 10px 28px -18px rgba(15,23,42,.28);
		background: #fff;
	}
	.udb-kpi{
		display:flex; align-items:center; gap: 1rem;
		padding: 1rem 1.05rem;
		height: 100%;
	}
	.udb-ic{
		width: 3rem; height: 3rem;
		border-radius: 1rem;
		display:flex; align-items:center; justify-content:center;
		color:#fff;
		box-shadow: 0 12px 22px -16px rgba(15,23,42,.45);
		flex-shrink:0;
	}
	.udb-sub{ color:#64748b; font-size:.88rem; margin: .1rem 0 0; }
	.udb-num{ font-weight: 950; font-size: 1.65rem; line-height: 1; color:#0f172a; }
	.udb-link{ font-weight: 800; font-size: .88rem; color:#734d96; }
	.udb-grid{ display:grid; grid-template-columns: repeat(12, 1fr); gap: 1rem; }
	.udb-col-3{ grid-column: span 3; }
	.udb-col-4{ grid-column: span 4; }
	.udb-col-5{ grid-column: span 5; }
	.udb-col-6{ grid-column: span 6; }
	.udb-col-7{ grid-column: span 7; }
	.udb-col-8{ grid-column: span 8; }
	.udb-col-12{ grid-column: span 12; }
	.udb-welcome{
		background: linear-gradient(135deg, #734d96 0%, #926cb8 55%, #b89ad4 100%);
		color:#fff;
		border-radius: 1rem;
		padding: 1.15rem 1.25rem;
		box-shadow: 0 16px 32px -20px rgba(146,108,184,.55);
	}
	.udb-welcome h5{ font-weight: 900; margin: 0 0 .25rem; }
	.udb-welcome p{ margin: 0; opacity: .92; font-size: .95rem; }
	.udb-action-card{
		border-left: 4px solid #f59e0b;
		background: linear-gradient(90deg, rgba(255,251,235,.95) 0%, #fff 72%);
	}
	.udb-chart-wrap{ padding: 1rem 1.05rem 1.15rem; }
	.udb-chart-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:.65rem; flex-wrap:wrap; }
	.udb-chart-title{ font-weight: 900; color:#0f172a; margin:0; font-size:1.05rem; }
	.udb-chart-note{ color:#64748b; font-size:.88rem; margin:0; }
	.udb-chart-grid{ display:grid; grid-template-columns: 1.4fr 1fr; gap: 1rem; align-items: stretch; }
	.udb-chart-box{ border: 1px solid rgba(148,163,184,.25); border-radius: .85rem; padding: .75rem .85rem; background: rgba(248,250,252,.6); }
	.udb-chart-box-title{ font-weight: 900; color:#0f172a; margin:0 0 .35rem; font-size:.95rem; }
	.udb-chart-canvas{ position:relative; height: 300px; }
	.udb-donut-wrap{ position:relative; height: 300px; }
	.udb-table thead th{ background:#f3eef8; color:#4a3263; font-weight:800; border-top:none; }
	.udb-table tbody td{ vertical-align:middle; }
	.udb-status{
		display:inline-flex; align-items:center; gap:.35rem;
		padding:.22rem .55rem; border-radius:999px;
		font-size:.78rem; font-weight:800; white-space:normal;
		max-width: 16rem; text-align:center; line-height:1.2;
		border:1px solid transparent;
	}
	.udb-table .pm-status-badge.pm-ab-chip{
		max-width: 18.5rem;
		text-align:left;
	}
	.udb-status-draft{ background:#fef3c7; color:#92400e; border-color:rgba(245,158,11,.35); }
	.udb-status-sent{ background:#dbeafe; color:#1e40af; border-color:rgba(59,130,246,.35); }
	.udb-status-include{ background:#ede9fe; color:#5b21b6; border-color:rgba(124,58,237,.35); }
	.udb-status-meeting{ background:#cffafe; color:#0e7490; border-color:rgba(6,182,212,.35); }
	.udb-status-included{ background:#bfdbfe; color:#1d4ed8; border-color:rgba(37,99,235,.35); }
	.udb-status-operate{ background:#dcfce7; color:#166534; border-color:rgba(22,163,74,.35); }
	.udb-status-returned{ background:#ffedd5; color:#9a3412; border-color:rgba(249,115,22,.35); }
	.udb-status-rejected{ background:#fee2e2; color:#991b1b; border-color:rgba(239,68,68,.35); }
	.udb-status-unknown{ background:#f1f5f9; color:#475569; border-color:rgba(148,163,184,.35); }
	.udb-status-a0{ background:#fef3c7; color:#92400e; border-color:rgba(245,158,11,.35); }
	.udb-status-a1{ background:#dbeafe; color:#1e40af; border-color:rgba(59,130,246,.35); }
	.udb-status-a2{ background:#ede9fe; color:#5b21b6; border-color:rgba(124,58,237,.35); }
	.udb-status-a_fix{ background:#ffedd5; color:#9a3412; border-color:rgba(249,115,22,.35); }
	.udb-status-a_reject{ background:#fee2e2; color:#991b1b; border-color:rgba(239,68,68,.35); }
	.udb-status-b1{ background:#bfdbfe; color:#1d4ed8; border-color:rgba(37,99,235,.35); }
	.udb-status-b2{ background:#fef3c7; color:#92400e; border-color:rgba(245,158,11,.35); }
	.udb-status-b3{ background:#dcfce7; color:#166534; border-color:rgba(22,163,74,.35); }
	.udb-status-b4{ background:#fef9c3; color:#854d0e; border-color:rgba(234,179,8,.35); }
	.udb-status-b4_done,
	.udb-status-b5{ background:#e8f6ea; color:#2e7d32; border-color:rgba(67,160,71,.35); }
	.udb-status-b_fix{ background:#ffedd5; color:#9a3412; border-color:rgba(249,115,22,.35); }
	.udb-status-midyear{ background:#f1f5f9; color:#475569; border-color:rgba(148,163,184,.35); }
	.udb-status-cancelled,
	.udb-status-cancel_pending{ background:#fee2e2; color:#991b1b; border-color:rgba(239,68,68,.35); }
	.udb-status-changing{ background:#ffedd5; color:#9a3412; border-color:rgba(249,115,22,.35); }
	.udb-quick-btn{
		border-radius:.65rem; font-weight:800;
		padding:.45rem .85rem; font-size:.88rem;
	}
	.udb-admin-chip{
		display:inline-flex; align-items:center; gap:.35rem;
		background:#f3eef8; color:#5c3d7a; border:1px solid #e8def2;
		border-radius:999px; padding:.2rem .65rem; font-size:.78rem; font-weight:800;
	}
	.udb-section-head{
		padding: 1rem 1.05rem .35rem;
		border-bottom: 1px solid rgba(148,163,184,.22);
		margin-bottom: .25rem;
	}
	.udb-section-head h5{ font-weight: 900; color: #0f172a; margin: 0; }
	.udb-section-head p{ margin: .2rem 0 0; color: #64748b; font-size: .88rem; }
	.udb-kpi-chart-sm{ position:relative; height: 240px; }
	.udb-kpi-name{ font-weight: 700; color: #0f172a; }
	.udb-kpi-project{ font-size: .82rem; color: #64748b; }
	@media (max-width: 1200px){ .udb-col-3, .udb-col-4{ grid-column: span 6; } }
	@media (max-width: 768px){
		.udb-col-3, .udb-col-4, .udb-col-5, .udb-col-6, .udb-col-7, .udb-col-8, .udb-col-12{ grid-column: span 12; }
		.udb-chart-canvas, .udb-donut-wrap{ height: 260px; }
		.udb-chart-grid{ grid-template-columns: 1fr; }
	}
</style>

<div class="page-content container container-plus px-2 px-md-3">
	<div class="udb-grid mb-3">
		<div class="udb-col-8">
			<div class="udb-welcome h-100 d-flex flex-column justify-content-center">
				<h5>สวัสดี, <?= htmlspecialchars($staff_display, ENT_QUOTES, 'UTF-8') ?></h5>
				<p>ภาพรวมโครงการของคุณ ปีงบประมาณ <?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>
					<?php if ($is_unit_admin): ?>
						<span class="udb-admin-chip ml-2"><i class="fa fa-shield-alt"></i> Admin หน่วยงาน</span>
					<?php endif; ?>
				</p>
			</div>
		</div>
		<div class="udb-col-4">
			<div class="udb-card h-100 p-3 d-flex flex-column justify-content-center">
				<form method="get" action="" class="mb-0">
					<input type="hidden" name="page" value="dashboard">
					<label class="mb-1 text-secondary font-weight-bold">ปีงบประมาณ</label>
					<select name="years_select" class="form-control form-control-sm mb-2" onchange="this.form.submit()">
						<?php foreach ($yearOptions as $y): ?>
							<option value="<?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8') ?>" <?= $y === $years_select ? 'selected' : '' ?>>
								<?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8') ?>
							</option>
						<?php endforeach; ?>
					</select>
				</form>
				<div class="d-flex flex-wrap" style="gap:.4rem;">
					<a class="btn btn-sm btn-primary udb-quick-btn" href="<?= htmlspecialchars($linkManageDraft, ENT_QUOTES, 'UTF-8') ?>">
						<i class="fa fa-cog mr-1"></i> โครงการของฉัน
					</a>
					<a class="btn btn-sm btn-outline-primary udb-quick-btn" href="<?= htmlspecialchars($linkApprove, ENT_QUOTES, 'UTF-8') ?>">
						<i class="fa fa-bell mr-1"></i> แจ้งเตือน
					</a>
				</div>
			</div>
		</div>
	</div>

	<?php if ($cnt_pending_certify_total > 0 || $cnt_need_action > 0): ?>
	<div class="udb-card udb-action-card udb-col-12 mb-3">
		<div class="p-3 d-flex flex-wrap align-items-center" style="gap:1rem;">
			<div class="flex-grow-1">
				<div class="font-weight-bold text-dark" style="font-size:1.05rem;">
					<i class="fa fa-bell text-warning mr-1"></i> งานที่ควรดำเนินการ
				</div>
				<div class="text-secondary mt-1" style="font-size:.9rem;">
					<?php if ($cnt_draft > 0): ?>
						<span class="mr-3">ร่างยังไม่ส่ง <strong><?= number_format($cnt_draft) ?></strong> โครงการ</span>
					<?php endif; ?>
					<?php if ($cnt_returned > 0): ?>
						<span class="mr-3">ตีกลับให้แก้ไข <strong><?= number_format($cnt_returned) ?></strong> โครงการ</span>
					<?php endif; ?>
					<?php if ($cnt_pending_certify_total > 0): ?>
						<span>รอพิจารณาจากคุณ <strong><?= number_format($cnt_pending_certify_total) ?></strong> รายการ</span>
					<?php endif; ?>
				</div>
			</div>
			<div class="d-flex flex-wrap" style="gap:.45rem;">
				<?php if ($cnt_need_action > 0): ?>
					<a class="btn btn-sm btn-warning udb-quick-btn" href="<?= htmlspecialchars($linkManageDraft, ENT_QUOTES, 'UTF-8') ?>">
						<i class="fa fa-edit mr-1"></i> ไปแก้ไขโครงการ
					</a>
				<?php endif; ?>
				<?php if ($cnt_pending_certify_total > 0): ?>
					<a class="btn btn-sm btn-success udb-quick-btn" href="<?= htmlspecialchars($linkApprove, ENT_QUOTES, 'UTF-8') ?>">
						<i class="fa fa-stamp mr-1"></i> ไปพิจารณา
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="udb-grid">
		<div class="udb-card udb-col-3">
			<div class="udb-kpi">
				<div class="udb-ic" style="background:#926cb8">
					<i class="fa fa-layer-group"></i>
				</div>
				<div class="flex-grow-1">
					<div class="udb-num"><?= number_format($cnt_total) ?></div>
					<p class="udb-sub mb-0">โครงการทั้งหมด</p>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-3">
			<div class="udb-kpi">
				<div class="udb-ic" style="background:#ea580c">
					<i class="fa fa-edit"></i>
				</div>
				<div class="flex-grow-1">
					<div class="udb-num"><?= number_format($cnt_draft) ?></div>
					<p class="udb-sub mb-0">ร่างโครงการ</p>
					<a class="udb-link" href="<?= htmlspecialchars($linkManageDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-3">
			<div class="udb-kpi">
				<div class="udb-ic" style="background:#926cb8">
					<i class="fa fa-paper-plane"></i>
				</div>
				<div class="flex-grow-1">
					<div class="udb-num"><?= number_format($cnt_sent) ?></div>
					<p class="udb-sub mb-0">ส่งร่างโครงการ</p>
					<a class="udb-link" href="<?= htmlspecialchars($linkManageDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-3">
			<div class="udb-kpi">
				<div class="udb-ic" style="background:#db2777">
					<i class="fa fa-hourglass-half"></i>
				</div>
				<div class="flex-grow-1">
					<div class="udb-num"><?= number_format($cnt_wait_include) ?></div>
					<p class="udb-sub mb-0">รอบรรจุโครงการ</p>
					<a class="udb-link" href="<?= htmlspecialchars($linkManageDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-3">
			<div class="udb-kpi">
				<div class="udb-ic" style="background:#0f766e">
					<i class="fa fa-file-signature"></i>
				</div>
				<div class="flex-grow-1">
					<div class="udb-num"><?= number_format($cnt_included_plan + $cnt_meeting) ?></div>
					<p class="udb-sub mb-0">นำเข้าที่ประชุม / บรรจุแผน</p>
					<a class="udb-link" href="<?= htmlspecialchars($linkManagePlan, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-3">
			<div class="udb-kpi">
				<div class="udb-ic" style="background:#15803d">
					<i class="fa fa-play-circle"></i>
				</div>
				<div class="flex-grow-1">
					<div class="udb-num"><?= number_format($cnt_operate) ?></div>
					<p class="udb-sub mb-0">ขอดำเนินการ (<?= number_format($cnt_operate_approved) ?> อนุมัติแล้ว)</p>
					<a class="udb-link" href="<?= htmlspecialchars($linkManagePlan, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-3">
			<div class="udb-kpi">
				<div class="udb-ic" style="background:#b45309">
					<i class="fa fa-undo"></i>
				</div>
				<div class="flex-grow-1">
					<div class="udb-num"><?= number_format($cnt_returned) ?></div>
					<p class="udb-sub mb-0">ตีกลับให้แก้ไข</p>
					<a class="udb-link" href="<?= htmlspecialchars($linkManageDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-3">
			<div class="udb-kpi">
				<div class="udb-ic" style="background:#991b1b">
					<i class="fa fa-times-circle"></i>
				</div>
				<div class="flex-grow-1">
					<div class="udb-num"><?= number_format($cnt_rejected) ?></div>
					<p class="udb-sub mb-0">ไม่อนุมัติ</p>
					<a class="udb-link" href="<?= htmlspecialchars($linkManageDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-12">
			<div class="udb-chart-wrap">
				<div class="udb-chart-head">
					<div>
						<p class="udb-chart-title">สรุปจำนวนเงิน (บาท) ตามสถานะ</p>
						<p class="udb-chart-note">รวมจากงบกิจกรรมของโครงการในขอบเขตของคุณ ปี <?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?></p>
					</div>
					<div class="text-right">
						<div class="text-secondary" style="font-size:.85rem; font-weight:800;">ยอดรวมทั้งหมด</div>
						<div style="font-weight:950; font-size:1.1rem; color:#0f172a;">
							<?= number_format($sum_total_amount, 2) ?>
						</div>
					</div>
				</div>
				<div class="udb-chart-grid">
					<div class="udb-chart-box">
						<p class="udb-chart-box-title">กราฟแท่ง</p>
						<div class="udb-chart-canvas">
							<canvas id="udbBudgetChart"></canvas>
						</div>
					</div>
					<div class="udb-chart-box">
						<p class="udb-chart-box-title">สัดส่วนสถานะ (จำนวนโครงการ)</p>
						<div class="udb-donut-wrap">
							<canvas id="udbStatusDonut"></canvas>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="udb-card udb-col-12">
			<div class="udb-section-head d-flex flex-wrap justify-content-between align-items-start">
				<div>
					<h5><i class="fa fa-chart-line text-purple mr-2"></i>KPI ตัวชี้วัด</h5>
					<p>สรุปตัวชี้วัดเป้าหมายโครงการและความสอดคล้อง (KR) ในขอบเขตของคุณ</p>
				</div>
			</div>

			<div class="udb-grid px-2 pb-2" style="margin-top:.75rem;">
				<div class="udb-card udb-col-3" style="box-shadow:none;">
					<div class="udb-kpi">
						<div class="udb-ic" style="background:#734d96">
							<i class="fa fa-bullseye"></i>
						</div>
						<div class="flex-grow-1">
							<div class="udb-num"><?= number_format($cnt_kpi_total) ?></div>
							<p class="udb-sub mb-0">ตัวชี้วัดเป้าหมาย (รายการ)</p>
						</div>
					</div>
				</div>
				<div class="udb-card udb-col-3" style="box-shadow:none;">
					<div class="udb-kpi">
						<div class="udb-ic" style="background:#0369a1">
							<i class="fa fa-project-diagram"></i>
						</div>
						<div class="flex-grow-1">
							<div class="udb-num"><?= number_format($cnt_projects_with_kpi) ?><span style="font-size:.95rem;color:#64748b;"> / <?= number_format($cnt_total) ?></span></div>
							<p class="udb-sub mb-0">โครงการที่กำหนด KPI แล้ว</p>
							<?php if ($cnt_projects_no_kpi > 0): ?>
								<span class="text-warning" style="font-size:.82rem;font-weight:700;">ยังไม่มี KPI <?= number_format($cnt_projects_no_kpi) ?> โครงการ</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="udb-card udb-col-3" style="box-shadow:none;">
					<div class="udb-kpi">
						<div class="udb-ic" style="background:#047857">
							<i class="fa fa-link"></i>
						</div>
						<div class="flex-grow-1">
							<div class="udb-num"><?= number_format($cnt_kr_strategic) ?></div>
							<p class="udb-sub mb-0">KR ความสอดคล้อง (<?= number_format($cnt_projects_with_consistency) ?> โครงการ)</p>
						</div>
					</div>
				</div>
				<div class="udb-card udb-col-3" style="box-shadow:none;">
					<div class="udb-kpi">
						<div class="udb-ic" style="background:#c2410c">
							<i class="fa fa-balance-scale"></i>
						</div>
						<div class="flex-grow-1">
							<div class="udb-num"><?= number_format($avg_kpi_weight, 2) ?></div>
							<p class="udb-sub mb-0">น้ำหนักเฉลี่ยต่อตัวชี้วัด</p>
						</div>
					</div>
				</div>

				<?php if ($cnt_kpi_total > 0): ?>
				<div class="udb-col-7">
					<div class="udb-chart-box h-100">
						<p class="udb-chart-box-title">เป้าหมายรายเดือน (รวมทุกตัวชี้วัด)</p>
						<div class="udb-kpi-chart-sm">
							<canvas id="udbKpiMonthChart"></canvas>
						</div>
					</div>
				</div>
				<div class="udb-col-5">
					<div class="udb-chart-box h-100">
						<p class="udb-chart-box-title">จำนวนตัวชี้วัดต่อโครงการ (Top <?= count($kpi_chart_projects) ?>)</p>
						<div class="udb-kpi-chart-sm">
							<canvas id="udbKpiProjectChart"></canvas>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<div class="table-responsive px-3 pb-3">
				<table class="table table-hover udb-table mb-0">
					<thead>
						<tr>
							<th>โครงการ</th>
							<th>ตัวชี้วัด</th>
							<th class="text-center">หน่วย</th>
							<th class="text-right">น้ำหนัก</th>
							<th class="text-right">เป้าหมายรวมปี</th>
							<th class="text-center" style="width:72px;">เปิด</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($kpi_table_rows)): ?>
							<tr>
								<td colspan="6" class="text-center text-secondary py-4">
									<i class="fa fa-chart-line fa-2x mb-2 d-block opacity-50"></i>
									ยังไม่มีตัวชี้วัดเป้าหมายในโครงการของคุณ
									<div class="mt-2 text-muted" style="font-size:.88rem;">กำหนด KPI ได้ที่หน้าแก้ไขโครงการ → ส่วนที่ 3 ตัวชี้วัดเป้าหมาย</div>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($kpi_table_rows as $kr): ?>
								<?php
								$kpiEditUrl = '?page=project_manage&op=add_project&project_id=' . (int)($kr['project_id'] ?? 0)
									. '&years_select=' . rawurlencode($years_select);
								?>
								<tr>
									<td>
										<div class="udb-kpi-name"><?= htmlspecialchars((string)($kr['project_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
										<div class="udb-kpi-project"><?= htmlspecialchars((string)($kr['project_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
									</td>
									<td><?= htmlspecialchars((string)($kr['kpi_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
									<td class="text-center"><?= htmlspecialchars((string)($kr['kpi_unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
									<td class="text-right"><?= number_format((float)($kr['weight'] ?? 0), 2) ?></td>
									<td class="text-right"><?= number_format((float)($kr['annual_target'] ?? 0), 2) ?></td>
									<td class="text-center">
										<a class="btn btn-xs btn-outline-primary" href="<?= htmlspecialchars($kpiEditUrl, ENT_QUOTES, 'UTF-8') ?>" title="แก้ไข KPI">
											<i class="fa fa-edit"></i>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if (count($kpi_rows) > count($kpi_table_rows)): ?>
								<tr>
									<td colspan="6" class="text-center text-secondary py-2" style="font-size:.88rem;">
										แสดง <?= count($kpi_table_rows) ?> จาก <?= count($kpi_rows) ?> รายการ — ดูทั้งหมดได้จากโครงการของฉัน
									</td>
								</tr>
							<?php endif; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="udb-card udb-col-12">
			<div class="p-3 pb-2 d-flex flex-wrap justify-content-between align-items-center">
				<div>
					<h5 class="mb-0 font-weight-bold text-dark">โครงการล่าสุด</h5>
					<div class="text-secondary" style="font-size:.88rem;">เรียงตามวันที่อัปเดตล่าสุด</div>
				</div>
				<a class="btn btn-sm btn-outline-secondary udb-quick-btn" href="<?= htmlspecialchars($linkAll, ENT_QUOTES, 'UTF-8') ?>">
					<i class="fa fa-th-large mr-1"></i> โครงการทั้งหมด
				</a>
			</div>
			<div class="table-responsive px-3 pb-3">
				<table class="table table-hover udb-table mb-0">
					<thead>
						<tr>
							<th style="width:48px;">#</th>
							<th>รหัส</th>
							<th>ชื่อโครงการ</th>
							<th class="text-right">งบประมาณ</th>
							<th class="text-center">สถานะ</th>
							<th class="text-center" style="width:100px;">จัดการ</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($recent_projects)): ?>
							<tr>
								<td colspan="6" class="text-center text-secondary py-4">
									<i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
									ยังไม่มีโครงการในปีงบประมาณนี้
									<div class="mt-2">
										<a class="btn btn-sm btn-primary udb-quick-btn" href="<?= htmlspecialchars($linkManageDraft, ENT_QUOTES, 'UTF-8') ?>">
											<i class="fa fa-plus mr-1"></i> ไปโครงการของฉัน
										</a>
									</div>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($recent_projects as $idx => $rp): ?>
								<?php
								[$stTxt, $stMod, $stIcon] = $udb_status_label($rp);
								$editUrl = '?page=project_manage&op=add_project&project_id=' . (int)($rp['id'] ?? 0)
									. '&years_select=' . rawurlencode($years_select);
								?>
								<tr>
									<td class="text-secondary"><?= $idx + 1 ?></td>
									<td class="font-weight-bold text-primary"><?= htmlspecialchars((string)($rp['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
									<td><?= htmlspecialchars((string)($rp['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
									<td class="text-right"><?= number_format((float)($rp['budget'] ?? 0), 2) ?></td>
									<td class="text-center">
										<?= function_exists('pm_render_ab_badge')
											? pm_render_ab_badge([
												'label' => $stTxt,
												'mod' => $stMod,
											], ['extra_class' => 'udb-status'])
											: '<span class="udb-status udb-status-'.htmlspecialchars($stMod, ENT_QUOTES, 'UTF-8').'"><i class="fa '.htmlspecialchars($stIcon, ENT_QUOTES, 'UTF-8').'"></i> '.htmlspecialchars($stTxt, ENT_QUOTES, 'UTF-8').'</span>'
										?>
									</td>
									<td class="text-center">
										<a class="btn btn-xs btn-outline-primary" href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>" title="เปิดโครงการ">
											<i class="fa fa-external-link-alt"></i>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="udb-card udb-col-12">
			<?php
			$pm_fb_report_show_table = false;
			include __DIR__ . '/../fn/system_feedback_report.php';
			?>
		</div>
	</div>
</div>

<script>
(function () {
	function initUserDashboardCharts(retryLeft) {
		if (typeof Chart === 'undefined') {
			if (retryLeft > 0) {
				setTimeout(function () { initUserDashboardCharts(retryLeft - 1); }, 250);
			}
			return;
		}

		var elBar = document.getElementById('udbBudgetChart');
		if (elBar) {
			var budgetLabels = ['ระหว่างดำเนินการ', 'บรรจุแผน', 'ขอดำเนินการ', 'อนุมัติแล้ว'];
			var budgetData = [
				<?= json_encode($sum_draft_pipeline, JSON_UNESCAPED_UNICODE) ?>,
				<?= json_encode($sum_included_plan_budget, JSON_UNESCAPED_UNICODE) ?>,
				<?= json_encode($sum_doing, JSON_UNESCAPED_UNICODE) ?>,
				<?= json_encode($sum_approved, JSON_UNESCAPED_UNICODE) ?>
			];
			new Chart(elBar.getContext('2d'), {
				type: 'bar',
				data: {
					labels: budgetLabels,
					datasets: [{
						label: 'จำนวนเงิน (บาท)',
						data: budgetData,
						backgroundColor: [
							'rgba(234, 88, 12, .75)',
							'rgba(37, 99, 235, .75)',
							'rgba(16, 163, 74, .75)',
							'rgba(20, 83, 45, .75)'
						],
						borderColor: [
							'rgba(234, 88, 12, .95)',
							'rgba(37, 99, 235, .95)',
							'rgba(16, 163, 74, .95)',
							'rgba(20, 83, 45, .95)'
						],
						borderWidth: 1,
						borderRadius: 10,
						maxBarThickness: 56
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					legend: { display: false },
					tooltips: {
						callbacks: {
							label: function (tooltipItem) {
								var v = tooltipItem.yLabel || 0;
								try { v = Number(v); } catch (e) {}
								return ' ' + v.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
							}
						}
					},
					scales: {
						xAxes: [{ gridLines: { display: false }, ticks: { fontStyle: 'bold' } }],
						yAxes: [{
							ticks: {
								beginAtZero: true,
								callback: function (value) {
									try { return Number(value).toLocaleString('th-TH'); } catch (e) { return value; }
								}
							}
						}]
					}
				}
			});
		}

		var elDonut = document.getElementById('udbStatusDonut');
		if (elDonut) {
			var statusLabels = ['ร่าง', 'ส่งร่าง', 'รอบรรจุ', 'นำเข้าที่ประชุม', 'บรรจุแผน', 'ขอดำเนินการ', 'ตีกลับ', 'ไม่อนุมัติ'];
			var statusData = [
				<?= (int)$cnt_draft ?>,
				<?= (int)$cnt_sent ?>,
				<?= (int)$cnt_wait_include ?>,
				<?= (int)$cnt_meeting ?>,
				<?= (int)$cnt_included_plan ?>,
				<?= (int)$cnt_operate ?>,
				<?= (int)$cnt_returned ?>,
				<?= (int)$cnt_rejected ?>
			];
			var filteredLabels = [];
			var filteredData = [];
			for (var i = 0; i < statusData.length; i++) {
				if (statusData[i] > 0) {
					filteredLabels.push(statusLabels[i]);
					filteredData.push(statusData[i]);
				}
			}
			if (filteredData.length === 0) {
				filteredLabels = ['ไม่มีข้อมูล'];
				filteredData = [1];
			}
			new Chart(elDonut.getContext('2d'), {
				type: 'doughnut',
				data: {
					labels: filteredLabels,
					datasets: [{
						data: filteredData,
						backgroundColor: [
							'rgba(245, 158, 11, .82)',
							'rgba(59, 130, 246, .82)',
							'rgba(124, 58, 237, .82)',
							'rgba(6, 182, 212, .82)',
							'rgba(37, 99, 235, .82)',
							'rgba(34, 197, 94, .82)',
							'rgba(249, 115, 22, .82)',
							'rgba(239, 68, 68, .82)'
						],
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					cutoutPercentage: 62,
					legend: { position: 'bottom' }
				}
			});
		}

		var elKpiMonth = document.getElementById('udbKpiMonthChart');
		if (elKpiMonth) {
			new Chart(elKpiMonth.getContext('2d'), {
				type: 'line',
				data: {
					labels: <?= json_encode($month_labels_fiscal, JSON_UNESCAPED_UNICODE) ?>,
					datasets: [{
						label: 'เป้าหมายรวม',
						data: <?= json_encode(array_values($kpi_month_totals), JSON_UNESCAPED_UNICODE) ?>,
						borderColor: 'rgba(109, 40, 217, .95)',
						backgroundColor: 'rgba(167, 139, 250, .25)',
						borderWidth: 2,
						pointRadius: 3,
						pointBackgroundColor: '#6d28d9',
						fill: true,
						lineTension: 0.25
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					legend: { display: false },
					tooltips: {
						callbacks: {
							label: function (tooltipItem) {
								var v = tooltipItem.yLabel || 0;
								try { v = Number(v); } catch (e) {}
								return ' ' + v.toLocaleString('th-TH', { maximumFractionDigits: 2 });
							}
						}
					},
					scales: {
						xAxes: [{ gridLines: { display: false } }],
						yAxes: [{
							ticks: {
								beginAtZero: true,
								callback: function (value) {
									try { return Number(value).toLocaleString('th-TH'); } catch (e) { return value; }
								}
							}
						}]
					}
				}
			});
		}

		var elKpiProj = document.getElementById('udbKpiProjectChart');
		if (elKpiProj) {
			new Chart(elKpiProj.getContext('2d'), {
				type: 'horizontalBar',
				data: {
					labels: <?= json_encode($kpi_chart_projects, JSON_UNESCAPED_UNICODE) ?>,
					datasets: [{
						label: 'จำนวนตัวชี้วัด',
						data: <?= json_encode($kpi_chart_counts, JSON_UNESCAPED_UNICODE) ?>,
						backgroundColor: 'rgba(14, 165, 233, .78)',
						borderColor: 'rgba(2, 132, 199, .95)',
						borderWidth: 1,
						borderRadius: 6
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					legend: { display: false },
					scales: {
						xAxes: [{
							ticks: { beginAtZero: true, precision: 0 }
						}],
						yAxes: [{ gridLines: { display: false } }]
					}
				}
			});
		}
	}

	if (document.readyState === 'complete') {
		initUserDashboardCharts(20);
	} else {
		window.addEventListener('load', function () { initUserDashboardCharts(20); });
	}
})();
</script>
