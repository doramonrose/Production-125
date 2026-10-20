<?php
/**
 * Executive Dashboard (Plan Admin / ผู้บริหาร)
 * - โฟกัส: ภาพรวมงบ + สถานะหลัก + Top projects + สรุปตามหน่วยงาน
 */

if (!isset($db_Project) || !$db_Project) {
	include_once __DIR__ . '/../../config/config_db.php';
}
include_once __DIR__ . '/../fn/main_project.php';

if (function_exists('mysqli_report')) {
	@mysqli_report(MYSQLI_REPORT_OFF);
}

$years_select = isset($_GET['years_select']) ? trim((string)$_GET['years_select']) : '';
if ($years_select === '') {
	$years_select = function_exists('getCurrentBudgetYear')
		? getCurrentBudgetYear()
		: (string)((int)date('Y') + 543 + ((int)date('n') >= 10 ? 1 : 0));
}
$yearsEsc = $db_Project ? mysqli_real_escape_string($db_Project, $years_select) : '';

$yearOptions = [];
try {
	$qy = @mysqli_query($db_Project, "SELECT budget_year_name FROM budget_set_year WHERE budget_year_status='1' ORDER BY budget_year_name DESC");
	while ($qy && ($r = mysqli_fetch_assoc($qy))) {
		$y = trim((string)($r['budget_year_name'] ?? ''));
		if ($y !== '') $yearOptions[] = $y;
	}
} catch (Throwable $e) {
	$yearOptions = [];
}
if (empty($yearOptions)) $yearOptions = [$years_select];

$toFloat = static function ($v): float {
	if ($v === null || $v === '') return 0.0;
	return is_numeric($v) ? (float)$v : 0.0;
};

// งบรวมต่อโครงการ + metadata ที่ใช้แยกสถานะ
$projects = [];
try {
	$sql = "
		SELECT
			p.id,
			p.code_pre_project,
			p.name_project,
			p.status_project,
			TRIM(IFNULL(p.notify_result,'')) AS notify_result,
			IFNULL(p.operate_certifiers_json,'') AS operate_certifiers_json,
			IFNULL(p.code_unit,'') AS code_unit,
			IFNULL(pb.b, 0) AS budget_total
		FROM pre_project p
		LEFT JOIN (
			SELECT code_pre_project, IFNULL(SUM(total),0) AS b
			FROM project_activity_budget
			WHERE status!='99'
			GROUP BY code_pre_project
		) pb ON pb.code_pre_project = p.id
		WHERE p.status!='99'
		  AND p.year_project='" . $yearsEsc . "'
	";
	$q = @mysqli_query($db_Project, $sql);
	while ($q && ($r = mysqli_fetch_assoc($q))) {
		$projects[] = $r;
	}
} catch (Throwable $e) {
	$projects = [];
}

// unit names
$unitNames = [];
try {
	$qU = @mysqli_query($db_person, "SELECT code, name FROM set_unit WHERE status='1' ORDER BY name ASC");
	while ($qU && ($u = mysqli_fetch_assoc($qU))) {
		$c = trim((string)($u['code'] ?? ''));
		if ($c !== '') $unitNames[$c] = (string)($u['name'] ?? '');
	}
} catch (Throwable $e) {
	$unitNames = [];
}

// สรุป 4 สถานะหลัก (เงิน)
$sum_total = 0.0;
$sum_included_plan = 0.0; // status=4 + notify_result=บรรจุแผน
$sum_doing = 0.0;         // status=1 และยังไม่ผ่านครบ
$sum_approved = 0.0;      // ผ่าน operate chain ครบ

// counts
$cnt_total = 0;
$cnt_included_plan = 0;
$cnt_doing = 0;
$cnt_approved = 0;

// by unit (budget)
$unitBudget = []; // code => sum

// top projects
foreach ($projects as $pr) {
	$pid = (int)($pr['id'] ?? 0);
	if ($pid <= 0) continue;
	$cnt_total++;
	$b = $toFloat($pr['budget_total'] ?? 0);
	$sum_total += $b;

	$st = (string)($pr['status_project'] ?? '');
	$nr = trim((string)($pr['notify_result'] ?? ''));
	$codeUnit = trim((string)($pr['code_unit'] ?? ''));

	$isIncludedPlan = ($st === '4')
		&& ($nr !== '')
		&& ((function_exists('mb_strpos') ? (mb_strpos($nr, 'บรรจุแผน') === 0) : (strpos($nr, 'บรรจุแผน') === 0)))
		&& ($nr !== 'บรรจุแผนและแก้ไข');
	if ($isIncludedPlan) {
		$sum_included_plan += $b;
		$cnt_included_plan++;
	}

	$isApprovedOperate = false;
	if (function_exists('pm_is_operate_final_approved') && $st === '1' && $nr !== 'บรรจุแผนและแก้ไข') {
		$isApprovedOperate = pm_is_operate_final_approved($db_Project, $pid, $pr);
	}
	if ($isApprovedOperate) {
		$sum_approved += $b;
		$cnt_approved++;
	} elseif ($st === '1') {
		$sum_doing += $b;
		$cnt_doing++;
	}

	if ($codeUnit !== '') {
		$unitBudget[$codeUnit] = ($unitBudget[$codeUnit] ?? 0.0) + $b;
	}
}

// Top units
arsort($unitBudget, SORT_NUMERIC);
$topUnits = array_slice($unitBudget, 0, 6, true);
$otherUnitsSum = 0.0;
if (count($unitBudget) > 6) {
	$otherUnitsSum = array_sum(array_slice(array_values($unitBudget), 6));
}

// Top projects by budget
usort($projects, function ($a, $b) use ($toFloat) {
	return $toFloat($b['budget_total'] ?? 0) <=> $toFloat($a['budget_total'] ?? 0);
});
$topProjects = array_slice($projects, 0, 5);

$fmt = static function (float $v): string {
	return number_format($v, 2);
};

// ---------- แนวโน้มรายเดือน / Funnel / เวลาเฉลี่ย ----------
$now = new DateTime('now');
$monthKeys = [];
$monthLabels = [];
for ($i = 11; $i >= 0; $i--) {
	$dt = (clone $now)->modify("-{$i} months");
	$monthKeys[] = $dt->format('Y-m');
	$monthLabels[] = $dt->format('m/Y');
}
$monthFrom = (clone $now)->modify('-11 months')->format('Y-m-01 00:00:00');
$monthFromEsc = $db_Project ? mysqli_real_escape_string($db_Project, $monthFrom) : '';

// Monthly: include request, finalize include, notify, operate-approved complete
$m_include = array_fill_keys($monthKeys, 0);
$m_finalize = array_fill_keys($monthKeys, 0);
$m_notify = array_fill_keys($monthKeys, 0);
$m_operate_done = array_fill_keys($monthKeys, 0);

try {
	$sqlInc = "
		SELECT DATE_FORMAT(ir.created_at, '%Y-%m') AS ym, COUNT(DISTINCT i.project_id) AS c
		FROM project_include_request ir
		JOIN project_include_request_item i ON i.request_id = ir.id
		JOIN pre_project p ON p.id = i.project_id AND p.status!='99' AND p.year_project='" . $yearsEsc . "'
		WHERE ir.created_at >= '" . $monthFromEsc . "'
		GROUP BY ym
	";
	$qInc = @mysqli_query($db_Project, $sqlInc);
	while ($qInc && ($r = mysqli_fetch_assoc($qInc))) {
		$ym = (string)($r['ym'] ?? '');
		if ($ym !== '' && isset($m_include[$ym])) $m_include[$ym] = (int)($r['c'] ?? 0);
	}
} catch (Throwable $e) {}

try {
	$sqlL = "
		SELECT DATE_FORMAT(l.created_at, '%Y-%m') AS ym, l.action_type, COUNT(DISTINCT l.project_id) AS c
		FROM project_plan_review_log l
		JOIN pre_project p ON p.id = l.project_id AND p.status!='99' AND p.year_project='" . $yearsEsc . "'
		WHERE l.created_at >= '" . $monthFromEsc . "'
		  AND l.action_type IN ('finalize_include','notify_plan_result')
		GROUP BY ym, l.action_type
	";
	$qL = @mysqli_query($db_Project, $sqlL);
	while ($qL && ($r = mysqli_fetch_assoc($qL))) {
		$ym = (string)($r['ym'] ?? '');
		$at = (string)($r['action_type'] ?? '');
		$c = (int)($r['c'] ?? 0);
		if ($ym === '' || !isset($m_finalize[$ym])) continue;
		if ($at === 'finalize_include') $m_finalize[$ym] = $c;
		if ($at === 'notify_plan_result') $m_notify[$ym] = $c;
	}
} catch (Throwable $e) {}

// operate complete per project (PHP aggregate): map latest decision by project+sort
$operateDec = [];
try {
	$qOD = @mysqli_query(
		$db_Project,
		"SELECT d.project_id, d.sort_order, d.decision, d.decided_at
		 FROM project_operate_certifier_decision d
		 JOIN pre_project p ON p.id=d.project_id AND p.status!='99' AND p.year_project='" . $yearsEsc . "'
		 WHERE d.decided_at >= '" . $monthFromEsc . "'"
	);
	while ($qOD && ($r = mysqli_fetch_assoc($qOD))) {
		$pid = (int)($r['project_id'] ?? 0);
		$so = (int)($r['sort_order'] ?? 0);
		if ($pid <= 0 || $so <= 0) continue;
		$operateDec[$pid][$so] = [
			'decision' => (string)($r['decision'] ?? ''),
			'decided_at' => (string)($r['decided_at'] ?? ''),
		];
	}
} catch (Throwable $e) {
	$operateDec = [];
}

$operateDoneAt = []; // pid => completion datetime string
foreach ($projects as $pr) {
	$pid = (int)($pr['id'] ?? 0);
	if ($pid <= 0) continue;
	$operateRaw = trim((string)($pr['operate_certifiers_json'] ?? ''));
	if ($operateRaw === '') continue;
	$certArr = json_decode($operateRaw, true);
	if (!is_array($certArr)) continue;
	$req = [];
	$seq = 0;
	foreach ($certArr as $c) {
		if (!is_array($c)) continue;
		$seq++;
		$so = (int)($c['sort_order'] ?? 0);
		if ($so <= 0) $so = $seq;
		if ((int)($c['staff_id'] ?? 0) <= 0) continue;
		$req[$so] = true;
	}
	$req = array_keys($req);
	sort($req, SORT_NUMERIC);
	if (empty($req)) continue;
	$allOk = true;
	$maxAt = '';
	foreach ($req as $so) {
		$dv = (string)($operateDec[$pid][$so]['decision'] ?? '');
		if ($dv !== 'approved') { $allOk = false; break; }
		$at = (string)($operateDec[$pid][$so]['decided_at'] ?? '');
		if ($at !== '' && ($maxAt === '' || $at > $maxAt)) $maxAt = $at;
	}
	if ($allOk && $maxAt !== '') {
		$operateDoneAt[$pid] = $maxAt;
		$ym = substr($maxAt, 0, 7);
		if (isset($m_operate_done[$ym])) $m_operate_done[$ym] += 1;
	}
}

// Funnel (counts): บรรจุแผน (status=4 + notify), ดำเนินการ (status=1 not ok), อนุมัติ (operate complete), คอขวดหลัก: รอแจ้งผล (status=4 notify empty)
$funnel_wait_notify = 0;
$funnel_included = 0;
$funnel_doing = 0;
$funnel_approved = 0;
foreach ($projects as $pr) {
	$pid = (int)($pr['id'] ?? 0);
	if ($pid <= 0) continue;
	$st = (string)($pr['status_project'] ?? '');
	$nr = trim((string)($pr['notify_result'] ?? ''));
	$isIncluded = ($st === '4')
		&& ($nr !== '')
		&& ((function_exists('mb_strpos') ? (mb_strpos($nr,'บรรจุแผน')===0) : (strpos($nr,'บรรจุแผน')===0)))
		&& ($nr !== 'บรรจุแผนและแก้ไข');
	if ($st === '4' && $nr === '') $funnel_wait_notify++;
	if ($isIncluded) $funnel_included++;
	if (isset($operateDoneAt[$pid])) $funnel_approved++;
	else if ($st === '1') $funnel_doing++;
}

// เวลาเฉลี่ยแต่ละขั้นตอน (วัน): include_request -> finalize_include -> notify_plan_result -> operate_done
$tsInclude = [];
$tsFinalize = [];
$tsNotify = [];
try {
	$qT1 = @mysqli_query(
		$db_Project,
		"SELECT i.project_id, MAX(ir.created_at) AS t
		 FROM project_include_request_item i
		 JOIN project_include_request ir ON ir.id=i.request_id
		 JOIN pre_project p ON p.id=i.project_id AND p.status!='99' AND p.year_project='" . $yearsEsc . "'
		 GROUP BY i.project_id"
	);
	while ($qT1 && ($r = mysqli_fetch_assoc($qT1))) {
		$pid = (int)($r['project_id'] ?? 0);
		$t = (string)($r['t'] ?? '');
		if ($pid > 0 && $t !== '') $tsInclude[$pid] = $t;
	}
} catch (Throwable $e) {}
try {
	$qT2 = @mysqli_query(
		$db_Project,
		"SELECT project_id,
			MAX(CASE WHEN action_type='finalize_include' THEN created_at ELSE NULL END) AS tf,
			MAX(CASE WHEN action_type='notify_plan_result' THEN created_at ELSE NULL END) AS tn
		 FROM project_plan_review_log
		 WHERE project_id IN (SELECT id FROM pre_project WHERE status!='99' AND year_project='" . $yearsEsc . "')
		 GROUP BY project_id"
	);
	while ($qT2 && ($r = mysqli_fetch_assoc($qT2))) {
		$pid = (int)($r['project_id'] ?? 0);
		if ($pid <= 0) continue;
		$tf = (string)($r['tf'] ?? '');
		$tn = (string)($r['tn'] ?? '');
		if ($tf !== '') $tsFinalize[$pid] = $tf;
		if ($tn !== '') $tsNotify[$pid] = $tn;
	}
} catch (Throwable $e) {}

$dur_inc_to_fin = [];
$dur_fin_to_not = [];
$dur_not_to_op = [];
$toDays = static function (string $a, string $b): ?float {
	$ta = strtotime($a);
	$tb = strtotime($b);
	if ($ta === false || $tb === false) return null;
	$diff = $tb - $ta;
	if ($diff < 0) return null;
	return $diff / 86400.0;
};
foreach ($projects as $pr) {
	$pid = (int)($pr['id'] ?? 0);
	if ($pid <= 0) continue;
	if (isset($tsInclude[$pid], $tsFinalize[$pid])) {
		$d = $toDays($tsInclude[$pid], $tsFinalize[$pid]);
		if ($d !== null) $dur_inc_to_fin[] = $d;
	}
	if (isset($tsFinalize[$pid], $tsNotify[$pid])) {
		$d = $toDays($tsFinalize[$pid], $tsNotify[$pid]);
		if ($d !== null) $dur_fin_to_not[] = $d;
	}
	if (isset($tsNotify[$pid], $operateDoneAt[$pid])) {
		$d = $toDays($tsNotify[$pid], $operateDoneAt[$pid]);
		if ($d !== null) $dur_not_to_op[] = $d;
	}
}
$avg = static function (array $xs): ?float {
	if (empty($xs)) return null;
	return array_sum($xs) / count($xs);
};
$median = static function (array $xs): ?float {
	if (empty($xs)) return null;
	sort($xs, SORT_NUMERIC);
	$n = count($xs);
	$mid = intdiv($n - 1, 2);
	if ($n % 2 === 1) return (float)$xs[$mid];
	return ((float)$xs[$mid] + (float)$xs[$mid + 1]) / 2.0;
};
$step_inc_to_fin = ['avg' => $avg($dur_inc_to_fin), 'median' => $median($dur_inc_to_fin), 'n' => count($dur_inc_to_fin)];
$step_fin_to_not = ['avg' => $avg($dur_fin_to_not), 'median' => $median($dur_fin_to_not), 'n' => count($dur_fin_to_not)];
$step_not_to_op = ['avg' => $avg($dur_not_to_op), 'median' => $median($dur_not_to_op), 'n' => count($dur_not_to_op)];
?>

<style>
	.exd-grid{ display:grid; grid-template-columns: repeat(12, 1fr); gap: 1rem; }
	.exd-card{ background:#fff; border:1px solid rgba(148,163,184,.35); border-radius: 1rem; box-shadow: 0 10px 28px -18px rgba(15,23,42,.28); }
	.exd-head{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap: .75rem; margin-bottom: 1rem; }
	.exd-title{ font-weight: 950; color:#0f172a; margin: 0; }
	.exd-sub{ color:#64748b; margin: .15rem 0 0; }
	.exd-kpi{ grid-column: span 3; padding: 1rem 1.05rem; display:flex; gap: .9rem; align-items:center; }
	.exd-ic{ width: 3rem; height: 3rem; border-radius: 1rem; display:flex; align-items:center; justify-content:center; color:#fff; box-shadow: 0 12px 22px -16px rgba(15,23,42,.45); flex-shrink:0;}
	.exd-num{ font-weight: 950; font-size: 1.45rem; color:#0f172a; line-height: 1.1; }
	.exd-meta{ color:#64748b; font-size: .88rem; }
	.exd-wide{ grid-column: span 8; padding: 1rem 1.05rem 1.15rem; }
	.exd-side{ grid-column: span 4; padding: 1rem 1.05rem 1.15rem; }
	.exd-chart{ position:relative; height: 320px; }
	.exd-table{ width:100%; }
	.exd-table th{ font-size:.85rem; color:#4a3263; font-weight:800; background:#f3eef8; border-top:0; }
	.exd-table td{ vertical-align: middle; }
	.exd-badge{ display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .6rem; border-radius:999px; font-weight:900; font-size:.78rem; }
	.exd-b1{ background: #f3eef8; color:#5c3d7a; border:1px solid #e8def2; }
	.exd-b2{ background: rgba(16,163,74,.14); color:#14532d; border:1px solid rgba(16,163,74,.25); }
	.exd-b3{ background: rgba(20,83,45,.14); color:#14532d; border:1px solid rgba(20,83,45,.25); }
	.exd-col-12{ grid-column: span 12; padding: 1rem 1.05rem 1.15rem; }
	.exd-small{ color:#64748b; font-size:.86rem; }
	.exd-funnel{ display:grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
	.exd-funnel-box{ border:1px solid rgba(148,163,184,.25); border-radius:.85rem; padding:.8rem .9rem; background: rgba(248,250,252,.65); }
	.exd-funnel-num{ font-weight:950; font-size:1.35rem; color:#0f172a; line-height:1.05; }
	.exd-funnel-lbl{ font-weight:900; color:#334155; margin-top:.25rem; }
	.exd-step-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap:.75rem; }
	.exd-step{ border:1px solid rgba(148,163,184,.25); border-radius:.85rem; padding:.8rem .9rem; background: rgba(248,250,252,.65); }
	.exd-step h6{ margin:0 0 .35rem; font-weight:950; color:#0f172a; }
	@media (max-width:1200px){ .exd-funnel{ grid-template-columns: repeat(2, 1fr); } .exd-step-grid{ grid-template-columns: 1fr; } }
	@media (max-width:1200px){ .exd-kpi{ grid-column: span 6; } .exd-wide{ grid-column: span 12; } .exd-side{ grid-column: span 12; } }
	@media (max-width:768px){ .exd-kpi{ grid-column: span 12; } .exd-chart{ height: 280px; } }
</style>

<div class="page-content container container-plus">
	<div class="exd-head">
		<div>
			<h4 class="exd-title">Executive Dashboard</h4>
			<div class="exd-sub">ภาพรวมโครงการและงบประมาณ (สำหรับผู้บริหาร)</div>
		</div>
		<form method="get" action="" class="d-flex align-items-center" style="gap:.5rem;">
			<input type="hidden" name="page" value="exec_dashboard">
			<label class="mb-0 text-secondary font-weight-bold">ปีงบประมาณ</label>
			<select name="years_select" class="form-control form-control-sm" onchange="this.form.submit()">
				<?php foreach ($yearOptions as $y): ?>
					<option value="<?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8') ?>" <?= $y === $years_select ? 'selected' : '' ?>>
						<?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8') ?>
					</option>
				<?php endforeach; ?>
			</select>
		</form>
	</div>

	<div class="exd-grid">
		<div class="exd-card exd-kpi">
			<div class="exd-ic" style="background:#926cb8"><i class="fa fa-coins"></i></div>
			<div>
				<div class="exd-num"><?= $fmt($sum_total) ?></div>
				<div class="exd-meta">จำนวนเงินทั้งหมด (บาท)</div>
			</div>
		</div>
		<div class="exd-card exd-kpi">
			<div class="exd-ic" style="background:#734d96"><i class="fa fa-check-circle"></i></div>
			<div>
				<div class="exd-num"><?= $fmt($sum_included_plan) ?></div>
				<div class="exd-meta">บรรจุแผน (<?= number_format($cnt_included_plan) ?> โครงการ)</div>
			</div>
		</div>
		<div class="exd-card exd-kpi">
			<div class="exd-ic" style="background:#15803d"><i class="fa fa-play"></i></div>
			<div>
				<div class="exd-num"><?= $fmt($sum_doing) ?></div>
				<div class="exd-meta">ดำเนินการ (<?= number_format($cnt_doing) ?> โครงการ)</div>
			</div>
		</div>
		<div class="exd-card exd-kpi">
			<div class="exd-ic" style="background:#166534"><i class="fa fa-award"></i></div>
			<div>
				<div class="exd-num"><?= $fmt($sum_approved) ?></div>
				<div class="exd-meta">อนุมัติ (<?= number_format($cnt_approved) ?> โครงการ)</div>
			</div>
		</div>

		<div class="exd-card exd-wide">
			<div class="d-flex flex-wrap justify-content-between align-items-end" style="gap:.75rem;">
				<div>
					<div class="font-weight-bold text-dark" style="font-size:1.05rem;">สัดส่วนงบประมาณตามสถานะหลัก</div>
					<div class="text-secondary" style="font-size:.9rem;">บรรจุแผน / ดำเนินการ / อนุมัติ</div>
				</div>
			</div>
			<div class="exd-chart mt-2">
				<canvas id="execDonut"></canvas>
			</div>
		</div>

		<div class="exd-card exd-side">
			<div class="font-weight-bold text-dark" style="font-size:1.05rem;">Top 5 โครงการงบสูงสุด</div>
			<div class="text-secondary mb-2" style="font-size:.9rem;">คลิกเข้าไปดูรายละเอียดได้</div>
			<div class="table-responsive">
				<table class="table table-sm exd-table mb-0">
					<thead>
						<tr>
							<th style="width:70px;">รหัส</th>
							<th>ชื่อโครงการ</th>
							<th class="text-right" style="width:120px;">งบ (บาท)</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($topProjects as $p): ?>
							<tr>
								<td><?= htmlspecialchars((string)($p['code_pre_project'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
								<td>
									<a href="?page=project_manage&op=add_project&project_id=<?= (int)($p['id'] ?? 0) ?>&years_select=<?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>" style="font-weight:800;">
										<?= htmlspecialchars((string)($p['name_project'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
									</a>
									<div class="mt-1">
										<?php
											$st = (string)($p['status_project'] ?? '');
											$nr = trim((string)($p['notify_result'] ?? ''));
											$badgeTxt = 'อื่นๆ';
											$badgeCls = 'exd-b1';
											$isIncluded = ($st === '4' && $nr !== '' && (function_exists('mb_strpos') ? (mb_strpos($nr,'บรรจุแผน')===0) : (strpos($nr,'บรรจุแผน')===0)) && $nr !== 'บรรจุแผนและแก้ไข');
											if ($isIncluded) { $badgeTxt='บรรจุแผน'; $badgeCls='exd-b1'; }
											$isApproved = false;
											if (function_exists('pm_is_operate_final_approved') && $st === '1' && $nr !== 'บรรจุแผนและแก้ไข') {
												$isApproved = pm_is_operate_final_approved($db_Project, (int)($p['id'] ?? 0), $p);
											}
											if ($isApproved) { $badgeTxt='อนุมัติ'; $badgeCls='exd-b3'; }
											elseif ($st === '1') { $badgeTxt='ดำเนินการ'; $badgeCls='exd-b2'; }
										?>
										<span class="exd-badge <?= $badgeCls ?>"><i class="fa fa-circle" style="font-size:.55rem;"></i><?= htmlspecialchars($badgeTxt, ENT_QUOTES, 'UTF-8') ?></span>
									</div>
								</td>
								<td class="text-right"><?= $fmt($toFloat($p['budget_total'] ?? 0)) ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if (empty($topProjects)): ?>
							<tr><td colspan="3" class="text-center text-secondary py-3">ไม่พบข้อมูล</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="exd-card exd-wide">
			<div class="font-weight-bold text-dark" style="font-size:1.05rem;">งบประมาณตามหน่วยงาน (Top)</div>
			<div class="text-secondary" style="font-size:.9rem;">แสดงหน่วยงานที่มีงบรวมสูงสุด (รวม “อื่นๆ”)</div>
			<div class="exd-chart mt-2">
				<canvas id="execUnitBar"></canvas>
			</div>
		</div>

		<div class="exd-card exd-col-12">
			<div class="d-flex flex-wrap justify-content-between align-items-end" style="gap:.75rem;">
				<div>
					<div class="font-weight-bold text-dark" style="font-size:1.05rem;">แนวโน้มรายเดือน (12 เดือนล่าสุด)</div>
					<div class="exd-small">จำนวนโครงการ: ส่งคำขอบรรจุ / ยืนยันบรรจุ / แจ้งผล / อนุมัติดำเนินการครบ</div>
				</div>
			</div>
			<div class="exd-chart mt-2">
				<canvas id="execMonthlyLine"></canvas>
			</div>
		</div>

		<div class="exd-card exd-col-12">
			<div class="font-weight-bold text-dark" style="font-size:1.05rem;">Funnel งานค้าง / คอขวด</div>
			<div class="exd-small mb-2">โฟกัสขั้นตอนหลักที่ “ค้าง” มากที่สุดในปีงบประมาณนี้</div>
			<div class="exd-funnel">
				<div class="exd-funnel-box">
					<div class="exd-funnel-num"><?= number_format((int)$funnel_wait_notify) ?></div>
					<div class="exd-funnel-lbl">รอแจ้งผล</div>
					<div class="exd-small">สถานะ 4 แต่ยังไม่ระบุผล</div>
				</div>
				<div class="exd-funnel-box">
					<div class="exd-funnel-num"><?= number_format((int)$funnel_included) ?></div>
					<div class="exd-funnel-lbl">บรรจุแผน</div>
					<div class="exd-small">สถานะ 4 + บรรจุแผน</div>
				</div>
				<div class="exd-funnel-box">
					<div class="exd-funnel-num"><?= number_format((int)$funnel_doing) ?></div>
					<div class="exd-funnel-lbl">ดำเนินการ</div>
					<div class="exd-small">สถานะ 1 ยังไม่ผ่านครบ</div>
				</div>
				<div class="exd-funnel-box">
					<div class="exd-funnel-num"><?= number_format((int)$funnel_approved) ?></div>
					<div class="exd-funnel-lbl">อนุมัติ</div>
					<div class="exd-small">ผ่านขอดำเนินการครบ</div>
				</div>
			</div>
		</div>

		<div class="exd-card exd-col-12">
			<div class="font-weight-bold text-dark" style="font-size:1.05rem;">เวลาเฉลี่ยแต่ละขั้นตอน (วัน)</div>
			<div class="exd-small mb-2">คำนวณจาก timestamp ที่มีจริงในระบบ (เฉพาะโครงการที่มีข้อมูลครบคู่)</div>
			<div class="exd-step-grid">
				<div class="exd-step">
					<h6>ส่งคำขอบรรจุ → ยืนยันบรรจุแผน</h6>
					<div class="exd-small">n = <?= number_format((int)$step_inc_to_fin['n']) ?></div>
					<div><b>เฉลี่ย:</b> <?= $step_inc_to_fin['avg'] === null ? '-' : number_format((float)$step_inc_to_fin['avg'], 1) ?> วัน</div>
					<div><b>มัธยฐาน:</b> <?= $step_inc_to_fin['median'] === null ? '-' : number_format((float)$step_inc_to_fin['median'], 1) ?> วัน</div>
				</div>
				<div class="exd-step">
					<h6>ยืนยันบรรจุแผน → แจ้งผล</h6>
					<div class="exd-small">n = <?= number_format((int)$step_fin_to_not['n']) ?></div>
					<div><b>เฉลี่ย:</b> <?= $step_fin_to_not['avg'] === null ? '-' : number_format((float)$step_fin_to_not['avg'], 1) ?> วัน</div>
					<div><b>มัธยฐาน:</b> <?= $step_fin_to_not['median'] === null ? '-' : number_format((float)$step_fin_to_not['median'], 1) ?> วัน</div>
				</div>
				<div class="exd-step">
					<h6>แจ้งผล → อนุมัติดำเนินการครบ</h6>
					<div class="exd-small">n = <?= number_format((int)$step_not_to_op['n']) ?></div>
					<div><b>เฉลี่ย:</b> <?= $step_not_to_op['avg'] === null ? '-' : number_format((float)$step_not_to_op['avg'], 1) ?> วัน</div>
					<div><b>มัธยฐาน:</b> <?= $step_not_to_op['median'] === null ? '-' : number_format((float)$step_not_to_op['median'], 1) ?> วัน</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function(){
	function init(retryLeft){
		if (typeof Chart === 'undefined') {
			if (retryLeft > 0) return setTimeout(function(){ init(retryLeft - 1); }, 250);
			return;
		}
		var donut = document.getElementById('execDonut');
		var unitBar = document.getElementById('execUnitBar');

		if (donut) {
			new Chart(donut.getContext('2d'), {
				type: 'doughnut',
				data: {
					labels: ['บรรจุแผน', 'ดำเนินการ', 'อนุมัติ'],
					datasets: [{
						data: [
							<?= json_encode($sum_included_plan, JSON_UNESCAPED_UNICODE) ?>,
							<?= json_encode($sum_doing, JSON_UNESCAPED_UNICODE) ?>,
							<?= json_encode($sum_approved, JSON_UNESCAPED_UNICODE) ?>
						],
						backgroundColor: [
							'rgba(37, 99, 235, .82)',
							'rgba(16, 163, 74, .82)',
							'rgba(20, 83, 45, .82)'
						],
						borderColor: [
							'rgba(37, 99, 235, .95)',
							'rgba(16, 163, 74, .95)',
							'rgba(20, 83, 45, .95)'
						],
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					cutoutPercentage: 68,
					legend: { position: 'bottom' },
					tooltips: {
						callbacks: {
							label: function (tooltipItem, dataObj) {
								var lbl = (dataObj.labels && dataObj.labels[tooltipItem.index]) ? dataObj.labels[tooltipItem.index] : '';
								var v = (dataObj.datasets && dataObj.datasets[0] && dataObj.datasets[0].data) ? dataObj.datasets[0].data[tooltipItem.index] : 0;
								try { v = Number(v); } catch(e) {}
								return ' ' + lbl + ': ' + v.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
							}
						}
					}
				}
			});
		}

		if (unitBar) {
			var labels = <?= json_encode(array_map(function($code) use ($unitNames){
				$nm = $unitNames[$code] ?? ('หน่วย ' . $code);
				return $nm;
			}, array_keys($topUnits)), JSON_UNESCAPED_UNICODE) ?>;
			var data = <?= json_encode(array_values($topUnits), JSON_UNESCAPED_UNICODE) ?>;
			<?php if ($otherUnitsSum > 0): ?>
			labels.push('อื่นๆ');
			data.push(<?= json_encode($otherUnitsSum, JSON_UNESCAPED_UNICODE) ?>);
			<?php endif; ?>

			new Chart(unitBar.getContext('2d'), {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [{
						label: 'งบรวม (บาท)',
						data: data,
						backgroundColor: 'rgba(15, 118, 110, .75)',
						borderColor: 'rgba(15, 118, 110, .95)',
						borderWidth: 1,
						borderRadius: 10
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
								try { v = Number(v); } catch(e) {}
								return ' ' + v.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
							}
						}
					},
					scales: {
						xAxes: [{ gridLines: { display:false } }],
						yAxes: [{
							ticks: {
								beginAtZero: true,
								callback: function (value) {
									try { return Number(value).toLocaleString('th-TH'); } catch(e) { return value; }
								}
							}
						}]
					}
				}
			});
		}

		var monthly = document.getElementById('execMonthlyLine');
		if (monthly) {
			var labelsM = <?= json_encode($monthLabels, JSON_UNESCAPED_UNICODE) ?>;
			var dsInclude = <?= json_encode(array_values($m_include), JSON_UNESCAPED_UNICODE) ?>;
			var dsFinalize = <?= json_encode(array_values($m_finalize), JSON_UNESCAPED_UNICODE) ?>;
			var dsNotify = <?= json_encode(array_values($m_notify), JSON_UNESCAPED_UNICODE) ?>;
			var dsOperate = <?= json_encode(array_values($m_operate_done), JSON_UNESCAPED_UNICODE) ?>;
			new Chart(monthly.getContext('2d'), {
				type: 'line',
				data: {
					labels: labelsM,
					datasets: [
						{ label: 'ส่งคำขอบรรจุ', data: dsInclude, borderColor:'rgba(168,85,247,.95)', backgroundColor:'rgba(168,85,247,.12)', pointRadius:3, tension:.25, fill:true },
						{ label: 'ยืนยันบรรจุแผน', data: dsFinalize, borderColor:'rgba(37,99,235,.95)', backgroundColor:'rgba(37,99,235,.10)', pointRadius:3, tension:.25, fill:true },
						{ label: 'แจ้งผล', data: dsNotify, borderColor:'rgba(15,118,110,.95)', backgroundColor:'rgba(15,118,110,.10)', pointRadius:3, tension:.25, fill:true },
						{ label: 'อนุมัติครบ', data: dsOperate, borderColor:'rgba(20,83,45,.95)', backgroundColor:'rgba(20,83,45,.10)', pointRadius:3, tension:.25, fill:true },
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					legend: { position: 'bottom' },
					scales: {
						xAxes: [{ gridLines: { display:false } }],
						yAxes: [{ ticks: { beginAtZero:true, precision:0 } }]
					}
				}
			});
		}
	}

	if (document.readyState === 'complete') init(20);
	else window.addEventListener('load', function(){ init(20); });
})();
</script>

