<?php
if (!isset($db_Project) || !$db_Project) {
	include_once __DIR__ . '/../../config/config_db.php';
}
if (!function_exists('pm_load_system_feedback_report')) {
	include_once __DIR__ . '/main_project.php';
}
$pm_fb_rep = ['total' => 0, 'avg' => 0, 'stars' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], 'cnt_user' => 0, 'cnt_plan' => 0, 'cnt_comment' => 0, 'rows' => []];
try {
	if (function_exists('pm_load_system_feedback_report')) {
		$pm_fb_rep = pm_load_system_feedback_report($db_Project ?? null);
	}
} catch (Throwable $e) {
	$pm_fb_rep = ['total' => 0, 'avg' => 0, 'stars' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], 'cnt_user' => 0, 'cnt_plan' => 0, 'cnt_comment' => 0, 'rows' => []];
}
$pm_fb_total = (int)($pm_fb_rep['total'] ?? 0);
$pm_fb_avg = (float)($pm_fb_rep['avg'] ?? 0);
$pm_fb_stars = is_array($pm_fb_rep['stars'] ?? null) ? $pm_fb_rep['stars'] : [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$pm_fb_avg_round = (int)round($pm_fb_avg);
$pm_fb_report_show_table = !isset($pm_fb_report_show_table) || (bool)$pm_fb_report_show_table;
?>
<div class="pm-fb-report">
	<div class="pm-fb-report-head">
		<div>
			<p class="pm-fb-report-title">รายงานความพึงพอใจต่อการใช้งานระบบ</p>
			<p class="pm-fb-report-note"><?= $pm_fb_report_show_table ? 'จากปุ่มประเมินระบบ — คะแนน 1–5 และข้อเสนอแนะล่าสุด 50 รายการ' : 'จากปุ่มประเมินระบบ — สรุปคะแนนความพึงพอใจ 1–5' ?></p>
		</div>
		<div class="pm-fb-report-head-right">
			<?php if ($pm_fb_report_show_table): ?>
			<a class="pm-fb-export-btn" href="export_system_feedback_excel.php">
				<i class="fas fa-file-excel" aria-hidden="true"></i> Export Excel
			</a>
			<?php endif; ?>
			<div class="pm-fb-report-stars-avg">
				<?php for ($i = 1; $i <= 5; $i++): ?>
					<i class="fa fa-star<?= $i <= $pm_fb_avg_round ? '' : '-o' ?>"></i>
				<?php endfor; ?>
			</div>
		</div>
	</div>
	<div class="pm-fb-report-kpis">
		<div class="pm-fb-report-kpi">
			<div class="n"><?= $pm_fb_total > 0 ? number_format($pm_fb_avg, 2) : '-' ?></div>
			<div class="l">คะแนนเฉลี่ย / 5</div>
		</div>
		<div class="pm-fb-report-kpi">
			<div class="n"><?= number_format($pm_fb_total) ?></div>
			<div class="l">จำนวนผู้ประเมิน</div>
		</div>
		<div class="pm-fb-report-kpi">
			<div class="n"><?= number_format((int)($pm_fb_rep['cnt_user'] ?? 0)) ?> / <?= number_format((int)($pm_fb_rep['cnt_plan'] ?? 0)) ?></div>
			<div class="l">User / Plan</div>
		</div>
		<div class="pm-fb-report-kpi">
			<div class="n"><?= number_format((int)($pm_fb_rep['cnt_comment'] ?? 0)) ?></div>
			<div class="l">มีข้อเสนอแนะ</div>
		</div>
	</div>
	<div class="mb-3">
		<?php for ($s = 5; $s >= 1; $s--):
			$c = (int)($pm_fb_stars[$s] ?? 0);
			$pct = $pm_fb_total > 0 ? min(100, round($c * 100 / $pm_fb_total)) : 0;
		?>
		<div class="pm-fb-bar-row">
			<div class="pm-fb-bar-lab"><?= (int)$s ?> ดาว</div>
			<div class="pm-fb-bar-track"><div class="pm-fb-bar-fill" style="width:<?= (int)$pct ?>%"></div></div>
			<div class="pm-fb-bar-cnt"><?= number_format($c) ?></div>
		</div>
		<?php endfor; ?>
	</div>
	<?php if ($pm_fb_report_show_table): ?>
	<div class="table-responsive">
		<table class="pm-fb-report-table">
			<thead>
				<tr>
					<th style="width:9.5rem;">วันที่</th>
					<th style="width:12rem;">ผู้ประเมิน</th>
					<th style="width:5.5rem;">บทบาท</th>
					<th style="width:6rem;">คะแนน</th>
					<th>ข้อเสนอแนะ</th>
				</tr>
			</thead>
			<tbody>
				<?php if ($pm_fb_total <= 0): ?>
					<tr><td colspan="5" class="pm-fb-report-empty">ยังไม่มีข้อมูลการประเมินระบบ</td></tr>
				<?php else: ?>
					<?php foreach (($pm_fb_rep['rows'] ?? []) as $fbRow):
						$dt = trim((string)($fbRow['created_at'] ?? ''));
						$dtShow = $dt;
						if ($dt !== '') {
							$ts = strtotime($dt);
							if ($ts) {
								$dtShow = date('d/m/', $ts) . ((int)date('Y', $ts) + 543) . ' ' . date('H:i', $ts);
							}
						}
						$role = ((string)($fbRow['role_type'] ?? '') === 'plan') ? 'Plan' : 'User';
						$name = trim((string)($fbRow['staff_name'] ?? ''));
						if ($name === '') {
							$name = '-';
						}
						$cmt = trim((string)($fbRow['comment'] ?? ''));
						$rt = (int)($fbRow['rating'] ?? 0);
					?>
					<tr>
						<td><?= htmlspecialchars($dtShow, ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></td>
						<td><?= $rt > 0 ? ((int)$rt . ' ดาว') : '-' ?></td>
						<td><?= $cmt !== '' ? nl2br(htmlspecialchars($cmt, ENT_QUOTES, 'UTF-8')) : '<span class="text-muted">-</span>' ?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</div>
