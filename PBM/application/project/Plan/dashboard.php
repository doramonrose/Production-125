<?php
/**
 * Dashboard (Plan Admin)
 * - สรุปสถานะโครงการในปีงบประมาณ
 * - ลิงก์ไปหน้ารายการที่เกี่ยวข้อง (project_manage)
 *
 * หมายเหตุ: `page.php` ของ Plan จะ include ไฟล์นี้ภายใต้ layout อยู่แล้ว
 */

if (!isset($db_Project) || !$db_Project) {
	include_once __DIR__ . '/../../config/config_db.php';
}
include_once __DIR__ . '/../fn/main_project.php';

// กันกรณีระบบเปิด MYSQLI_REPORT_STRICT (ทำให้ query ผิดพลาดแล้ว throw จนหน้า "ขาว")
if (function_exists('mysqli_report')) {
	@mysqli_report(MYSQLI_REPORT_OFF);
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
	// fallback: ปีงบประมาณปัจจุบัน (พ.ศ. เริ่ม 1 ต.ค.)
	$years_select = function_exists('getCurrentBudgetYear')
		? getCurrentBudgetYear()
		: (string)((int)date('Y') + 543 + ((int)date('n') >= 10 ? 1 : 0));
}
$yearsEsc = $db_Project ? mysqli_real_escape_string($db_Project, $years_select) : '';

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

$sql = "
	SELECT
		COUNT(*) AS total,
		SUM(CASE WHEN p.status_project='0' THEN 1 ELSE 0 END) AS cnt_draft,
		SUM(CASE WHEN p.status_project='2' THEN 1 ELSE 0 END) AS cnt_sent,
		SUM(CASE WHEN p.status_project='3' THEN 1 ELSE 0 END) AS cnt_wait_include,
		SUM(CASE WHEN p.status_project='4' AND TRIM(IFNULL(p.notify_result,''))='' THEN 1 ELSE 0 END) AS cnt_wait_notify,
		SUM(CASE WHEN p.status_project='4' AND TRIM(IFNULL(p.notify_result,''))<>'' THEN 1 ELSE 0 END) AS cnt_notified,
		SUM(CASE WHEN p.status_project='8' THEN 1 ELSE 0 END) AS cnt_returned,
		SUM(CASE WHEN p.status_project='9' THEN 1 ELSE 0 END) AS cnt_rejected,
		SUM(CASE
			WHEN TRIM(IFNULL(p.notify_result,''))<>'' AND (TRIM(p.notify_result) LIKE 'บรรจุแผน%') AND TRIM(p.notify_result) <> 'บรรจุแผนและแก้ไข'
			THEN 1 ELSE 0 END
		) AS cnt_included_plan
	FROM pre_project p
	WHERE p.status!='99'
	  AND p.year_project='" . $yearsEsc . "'
";
$row = [];
try {
	$q = @mysqli_query($db_Project, $sql);
	$row = $q ? mysqli_fetch_assoc($q) : [];
} catch (Throwable $e) {
	$row = [];
}

$toInt = static function ($v): int {
	return (int)($v ?? 0);
};
$cnt_total = $toInt($row['total'] ?? 0);
$cnt_draft = $toInt($row['cnt_draft'] ?? 0);
$cnt_sent = $toInt($row['cnt_sent'] ?? 0);
$cnt_wait_include = $toInt($row['cnt_wait_include'] ?? 0);
$cnt_wait_notify = $toInt($row['cnt_wait_notify'] ?? 0);
$cnt_notified = $toInt($row['cnt_notified'] ?? 0);
$cnt_returned = $toInt($row['cnt_returned'] ?? 0);
$cnt_rejected = $toInt($row['cnt_rejected'] ?? 0);
$cnt_included_plan = $toInt($row['cnt_included_plan'] ?? 0);

$budgetProjects = [];
try {
	$budgetSql = "
		SELECT
			p.id,
			p.status_project,
			TRIM(IFNULL(p.notify_result,'')) AS notify_result,
			IFNULL(p.operate_certifiers_json,'') AS operate_certifiers_json,
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
	$qb = @mysqli_query($db_Project, $budgetSql);
	while ($qb && ($r = mysqli_fetch_assoc($qb))) {
		$budgetProjects[] = $r;
	}
} catch (Throwable $e) {
	$budgetProjects = [];
}
$toFloat = static function ($v): float {
	if ($v === null || $v === '') return 0.0;
	if (is_numeric($v)) return (float)$v;
	return 0.0;
};
$sum_total_amount = 0.0;
$sum_included_plan = 0.0;
$sum_doing = 0.0;
$sum_approved = 0.0;
foreach ($budgetProjects as $pr) {
	$pid = (int)($pr['id'] ?? 0);
	if ($pid <= 0) continue;
	$st = (string)($pr['status_project'] ?? '');
	$nr = trim((string)($pr['notify_result'] ?? ''));
	$b = $toFloat($pr['budget_total'] ?? 0);
	$sum_total_amount += $b;

	$isIncludedPlan = ($nr !== '')
		&& ((function_exists('mb_strpos') ? (mb_strpos($nr, 'บรรจุแผน') === 0) : (strpos($nr, 'บรรจุแผน') === 0)))
		&& ($nr !== 'บรรจุแผนและแก้ไข');
	// "บรรจุแผน" ใน dashboard นี้: นับเฉพาะขั้นบรรจุแผน/นำเข้าที่ประชุม (status=4) เท่านั้น
	// เพื่อไม่ให้ซ้อนกับขั้นขอดำเนินการ (status=1) ที่ยังคงมี notify_result = บรรจุแผนอยู่
	if ($isIncludedPlan && $st === '4') {
		$sum_included_plan += $b;
	}

	// นิยาม "อนุมัติ": ผ่านลำดับขอดำเนินการครบ (เทียบกับหน้า approve)
	$isApprovedOperate = false;
	if (function_exists('pm_is_operate_final_approved') && $st === '1' && $nr !== 'บรรจุแผนและแก้ไข') {
		$isApprovedOperate = pm_is_operate_final_approved($db_Project, $pid, $pr);
	}
	if ($isApprovedOperate) {
		$sum_approved += $b;
	} elseif ($st === '1') {
		// นิยาม "ดำเนินการ": อยู่ในสถานะขอดำเนินการ แต่ยังไม่ผ่านครบทุกลำดับ
		$sum_doing += $b;
	}
}

$linkDraft = '?page=project_manage&years_select=' . rawurlencode($years_select) . '&project_group=draft';
$linkPlan = '?page=project_manage&years_select=' . rawurlencode($years_select) . '&project_group=plan';
?>

<style>
	.pmdb-card{
		border-radius: 1rem;
		border: 1px solid rgba(148,163,184,.35);
		box-shadow: 0 10px 28px -18px rgba(15,23,42,.28);
		background: #fff;
	}
	.pmdb-kpi{
		display:flex; align-items:center; gap: 1rem;
		padding: 1rem 1.05rem;
	}
	.pmdb-ic{
		width: 3rem; height: 3rem;
		border-radius: 1rem;
		display:flex; align-items:center; justify-content:center;
		color:#fff;
		box-shadow: 0 12px 22px -16px rgba(15,23,42,.45);
		flex-shrink:0;
	}
	.pmdb-sub{ color:#64748b; font-size:.9rem; margin: .1rem 0 0; }
	.pmdb-num{ font-weight: 950; font-size: 1.75rem; line-height: 1; color:#0f172a; }
	.pmdb-link{ font-weight: 800; color:#734d96; }
	.pmdb-grid{ display:grid; grid-template-columns: repeat(12, 1fr); gap: 1rem; }
	.pmdb-col-3{ grid-column: span 3; }
	.pmdb-col-6{ grid-column: span 6; }
	.pmdb-col-12{ grid-column: span 12; }
	.pmdb-chart-wrap{ padding: 1rem 1.05rem 1.15rem; }
	.pmdb-chart-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:.65rem; }
	.pmdb-chart-title{ font-weight: 900; color:#0f172a; margin:0; font-size:1.05rem; }
	.pmdb-chart-note{ color:#64748b; font-size:.88rem; margin:0; }
	.pmdb-chart-canvas{ position:relative; height: 320px; }
	.pmdb-chart-grid{ display:grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: stretch; }
	.pmdb-chart-box{ border: 1px solid rgba(148,163,184,.25); border-radius: .85rem; padding: .75rem .85rem; background: rgba(248,250,252,.6); }
	.pmdb-chart-box-title{ font-weight: 900; color:#0f172a; margin:0 0 .35rem; font-size:.95rem; }
	.pmdb-donut-wrap{ position:relative; height: 320px; }
	@media (max-width: 1200px){ .pmdb-col-3{ grid-column: span 6; } }
	@media (max-width: 768px){
		.pmdb-col-3, .pmdb-col-6, .pmdb-col-12{ grid-column: span 12; }
		.pmdb-chart-canvas{ height: 280px; }
		.pmdb-donut-wrap{ height: 280px; }
		.pmdb-chart-grid{ grid-template-columns: 1fr; }
	}
</style>

<div class="page-content container container-plus">
	<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
		<div>
			<h4 class="mb-1 font-weight-bold text-dark">Dashboard (Plan Admin)</h4>
			<div class="text-secondary">สรุปสถานะโครงการและงานคงค้างของหน่วยแผน</div>
		</div>
		<form method="get" action="" class="d-flex align-items-center" style="gap:.5rem;">
			<input type="hidden" name="page" value="dashboard">
			<label class="mb-0 text-secondary font-weight-bold">ปีงบประมาณ</label>
			<select name="years_select" class="form-control form-control-sm" onchange="this.form.submit()">
				<?php foreach ($yearOptions as $y): ?>
					<option value="<?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8') ?>" <?= $y === $years_select ? 'selected' : '' ?>>
						<?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8') ?>
					</option>
				<?php endforeach; ?>
			</select>
			<a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($linkDraft, ENT_QUOTES, 'UTF-8') ?>">
				<i class="fa fa-table mr-1"></i> ไปหน้าจัดการโครงการ
			</a>
		</form>
	</div>

	<div class="pmdb-grid">
		<div class="pmdb-card pmdb-col-3">
			<div class="pmdb-kpi">
				<div class="pmdb-ic" style="background:#926cb8">
					<i class="fa fa-layer-group"></i>
				</div>
				<div class="flex-grow-1">
					<div class="pmdb-num"><?= number_format($cnt_total) ?></div>
					<p class="pmdb-sub mb-0">โครงการทั้งหมด (ปี <?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>)</p>
				</div>
			</div>
		</div>

		<div class="pmdb-card pmdb-col-3">
			<div class="pmdb-kpi">
				<div class="pmdb-ic" style="background:#ea580c">
					<i class="fa fa-edit"></i>
				</div>
				<div class="flex-grow-1">
					<div class="pmdb-num"><?= number_format($cnt_draft) ?></div>
					<p class="pmdb-sub mb-0">ร่างโครงการ</p>
					<a class="pmdb-link" href="<?= htmlspecialchars($linkDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="pmdb-card pmdb-col-3">
			<div class="pmdb-kpi">
				<div class="pmdb-ic" style="background:#926cb8">
					<i class="fa fa-paper-plane"></i>
				</div>
				<div class="flex-grow-1">
					<div class="pmdb-num"><?= number_format($cnt_sent) ?></div>
					<p class="pmdb-sub mb-0">ส่งร่างโครงการ</p>
					<a class="pmdb-link" href="<?= htmlspecialchars($linkDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="pmdb-card pmdb-col-3">
			<div class="pmdb-kpi">
				<div class="pmdb-ic" style="background:#db2777">
					<i class="fa fa-hourglass-half"></i>
				</div>
				<div class="flex-grow-1">
					<div class="pmdb-num"><?= number_format($cnt_wait_include) ?></div>
					<p class="pmdb-sub mb-0">รอบรรจุโครงการ</p>
					<a class="pmdb-link" href="<?= htmlspecialchars($linkDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="pmdb-card pmdb-col-6">
			<div class="pmdb-kpi">
				<div class="pmdb-ic" style="background:#0f766e">
					<i class="fa fa-bell"></i>
				</div>
				<div class="flex-grow-1">
					<div class="font-weight-bold text-dark" style="font-size:1.05rem;">งานคงค้าง (Plan Admin)</div>
					<div class="text-secondary">โครงการ “นำเข้าที่ประชุม” ที่ยังไม่ได้แจ้งผล</div>
					<div class="d-flex flex-wrap align-items-end mt-2" style="gap:1rem;">
						<div>
							<div class="pmdb-num" style="color:#0f766e"><?= number_format($cnt_wait_notify) ?></div>
							<div class="pmdb-sub">รอแจ้งผล</div>
						</div>
						<div>
							<div class="pmdb-num" style="font-size:1.25rem"><?= number_format($cnt_notified) ?></div>
							<div class="pmdb-sub">แจ้งผลแล้ว</div>
						</div>
						<div class="ml-auto">
							<a class="btn btn-sm btn-success" href="<?= htmlspecialchars($linkDraft, ENT_QUOTES, 'UTF-8') ?>">
								<i class="fa fa-file-signature mr-1"></i> ไปแจ้งผล
							</a>
							<a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($linkPlan, ENT_QUOTES, 'UTF-8') ?>">
								<i class="fa fa-list mr-1"></i> รายการแผน
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="pmdb-card pmdb-col-3">
			<div class="pmdb-kpi">
				<div class="pmdb-ic" style="background:#734d96">
					<i class="fa fa-check-circle"></i>
				</div>
				<div class="flex-grow-1">
					<div class="pmdb-num"><?= number_format($cnt_included_plan) ?></div>
					<p class="pmdb-sub mb-0">บรรจุแผน (ยืนยันแล้ว)</p>
					<a class="pmdb-link" href="<?= htmlspecialchars($linkPlan, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="pmdb-card pmdb-col-3">
			<div class="pmdb-kpi">
				<div class="pmdb-ic" style="background:#b45309">
					<i class="fa fa-undo"></i>
				</div>
				<div class="flex-grow-1">
					<div class="pmdb-num"><?= number_format($cnt_returned) ?></div>
					<p class="pmdb-sub mb-0">ตีกลับให้แก้ไข</p>
					<a class="pmdb-link" href="<?= htmlspecialchars($linkDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="pmdb-card pmdb-col-3">
			<div class="pmdb-kpi">
				<div class="pmdb-ic" style="background:#991b1b">
					<i class="fa fa-times-circle"></i>
				</div>
				<div class="flex-grow-1">
					<div class="pmdb-num"><?= number_format($cnt_rejected) ?></div>
					<p class="pmdb-sub mb-0">ไม่อนุมัติ</p>
					<a class="pmdb-link" href="<?= htmlspecialchars($linkDraft, ENT_QUOTES, 'UTF-8') ?>">เปิดรายการ</a>
				</div>
			</div>
		</div>

		<div class="pmdb-card pmdb-col-12">
			<div class="pmdb-chart-wrap">
				<div class="pmdb-chart-head">
					<div>
						<p class="pmdb-chart-title">สรุปจำนวนเงิน (บาท) ตามสถานะ</p>
						<p class="pmdb-chart-note">รวมจากงบกิจกรรม (`project_activity_budget`) ของแต่ละโครงการในปีงบประมาณ</p>
					</div>
					<div class="text-right">
						<div class="text-secondary" style="font-size:.85rem; font-weight:800;">ยอดรวมทั้งหมด</div>
						<div style="font-weight:950; font-size:1.1rem; color:#0f172a;">
							<?= number_format($sum_total_amount, 2) ?>
						</div>
					</div>
				</div>
				<div class="pmdb-chart-grid">
					<div class="pmdb-chart-box">
						<p class="pmdb-chart-box-title">กราฟแท่ง</p>
						<div class="pmdb-chart-canvas">
							<canvas id="pmdbBudgetChart"></canvas>
						</div>
					</div>
					<div class="pmdb-chart-box">
						<p class="pmdb-chart-box-title">Donut</p>
						<div class="pmdb-donut-wrap">
							<canvas id="pmdbBudgetDonut"></canvas>
						</div>
						<div class="mt-2 text-secondary" style="font-size:.85rem;">
							สัดส่วน: บรรจุแผน / ดำเนินการ / อนุมัติ
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="pmdb-card pmdb-col-12">
			<?php include __DIR__ . '/../fn/system_feedback_report.php'; ?>
		</div>
	</div>
</div>

<script>
(function () {
	function initBudgetChart(retryLeft) {
		var el = document.getElementById('pmdbBudgetChart');
		var elDonut = document.getElementById('pmdbBudgetDonut');
		if (!el) return;
		if (typeof Chart === 'undefined') {
			if (retryLeft > 0) {
				setTimeout(function(){ initBudgetChart(retryLeft - 1); }, 250);
			}
			return;
		}

		var labels = ['จำนวนเงินทั้งหมด', 'บรรจุแผน', 'ดำเนินการ', 'อนุมัติ'];
		var data = [
			<?= json_encode($sum_total_amount, JSON_UNESCAPED_UNICODE) ?>,
			<?= json_encode($sum_included_plan, JSON_UNESCAPED_UNICODE) ?>,
			<?= json_encode($sum_doing, JSON_UNESCAPED_UNICODE) ?>,
			<?= json_encode($sum_approved, JSON_UNESCAPED_UNICODE) ?>
		];
		var bg = [
			'rgba(30, 58, 138, .75)',
			'rgba(37, 99, 235, .75)',
			'rgba(16, 163, 74, .75)',
			'rgba(20, 83, 45, .75)'
	];
		var border = [
			'rgba(30, 58, 138, .95)',
			'rgba(37, 99, 235, .95)',
			'rgba(16, 163, 74, .95)',
			'rgba(20, 83, 45, .95)'
	];

		var ctx = el.getContext('2d');
		new Chart(ctx, {
		type: 'bar',
		data: {
			labels: labels,
			datasets: [{
				label: 'จำนวนเงิน (บาท)',
				data: data,
				backgroundColor: bg,
				borderColor: border,
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
				xAxes: [{
					gridLines: { display: false },
					ticks: { fontStyle: 'bold' }
				}],
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

		// Donut: ใช้เฉพาะ 3 สถานะ (ไม่รวม "จำนวนเงินทั้งหมด")
		if (elDonut) {
			var ctxD = elDonut.getContext('2d');
			new Chart(ctxD, {
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
								try { v = Number(v); } catch (e) {}
								return ' ' + lbl + ': ' + v.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
							}
						}
					}
				}
			});
		}
	}

	if (document.readyState === 'complete') {
		initBudgetChart(20);
	} else {
		window.addEventListener('load', function(){ initBudgetChart(20); });
	}
})();
</script>

