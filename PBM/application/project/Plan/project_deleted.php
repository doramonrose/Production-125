<?php
/**
 * รายการโครงการที่ Admin ลบ — ดูผู้ลบและเวลาที่ลบ
 */
if (!defined('PLAN_ADMIN') || !PLAN_ADMIN) {
	echo '<div class="alert alert-danger">หน้านี้ใช้ได้เฉพาะผู้ดูแลระบบ</div>';
	return;
}

@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_delete_log (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	project_id INT UNSIGNED NOT NULL,
	year_project VARCHAR(10) NULL,
	code_pre_project VARCHAR(64) NULL,
	name_project VARCHAR(500) NULL,
	code_unit VARCHAR(32) NULL,
	status_project_before VARCHAR(8) NULL,
	notify_result VARCHAR(120) NULL,
	owner_staff_id INT UNSIGNED NULL,
	actor_staff_id INT UNSIGNED NULL,
	actor_staff_code VARCHAR(32) NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_pdl_project (project_id, created_at),
	KEY idx_pdl_actor (actor_staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$yearFilter = isset($_GET['years_select']) ? trim((string)$_GET['years_select']) : '';
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$yearOptions = [];
$qy = @mysqli_query($db_Project, "SELECT DISTINCT year_project FROM project_delete_log WHERE IFNULL(year_project,'') <> '' ORDER BY year_project DESC");
while ($qy && ($yr = mysqli_fetch_assoc($qy))) {
	$yearOptions[] = (string)$yr['year_project'];
}

$sql = "SELECT * FROM project_delete_log WHERE 1=1";
if ($yearFilter !== '') {
	$yEsc = mysqli_real_escape_string($db_Project, $yearFilter);
	$sql .= " AND year_project='" . $yEsc . "'";
}
if ($q !== '') {
	$qEsc = mysqli_real_escape_string($db_Project, $q);
	$sql .= " AND (code_pre_project LIKE '%{$qEsc}%' OR name_project LIKE '%{$qEsc}%' OR actor_staff_code LIKE '%{$qEsc}%')";
}
$sql .= " ORDER BY created_at DESC, id DESC";
$rows = [];
$res = @mysqli_query($db_Project, $sql);
while ($res && ($r = mysqli_fetch_assoc($res))) {
	$rows[] = $r;
}

$staffIds = [];
foreach ($rows as $r) {
	$aid = (int)($r['actor_staff_id'] ?? 0);
	$oid = (int)($r['owner_staff_id'] ?? 0);
	if ($aid > 0) {
		$staffIds[$aid] = $aid;
	}
	if ($oid > 0) {
		$staffIds[$oid] = $oid;
	}
}
$staffNames = [];
if ($staffIds && isset($db_person) && $db_person) {
	$in = implode(',', array_map('intval', $staffIds));
	$qs = @mysqli_query($db_person, "SELECT id, fname, lname, code FROM staff WHERE id IN ($in)");
	while ($qs && ($s = mysqli_fetch_assoc($qs))) {
		$nm = trim((string)($s['fname'] ?? '') . ' ' . (string)($s['lname'] ?? ''));
		$code = trim((string)($s['code'] ?? ''));
		$staffNames[(int)$s['id']] = $nm !== '' ? ($nm . ($code !== '' ? ' (' . $code . ')' : '')) : ($code !== '' ? $code : ('#' . (int)$s['id']));
	}
}

$unitNames = function_exists('pm_build_project_unit_names_map')
	? pm_build_project_unit_names_map($db_Project, $db_person ?? null)
	: [];
if (!is_array($unitNames)) {
	$unitNames = [];
}

$statusMap = [
	'0' => 'ร่าง',
	'1' => 'อนุมัติดำเนินการ',
	'2' => 'รอพิจารณา',
	'3' => 'พิจารณาแผน',
	'4' => 'นำเข้าที่ประชุม',
	'8' => 'ตีกลับให้แก้ไข',
];
$thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
$fmtWhen = static function ($dt) use ($thaiMonths): string {
	$dt = trim((string)$dt);
	$ts = $dt !== '' ? strtotime($dt) : false;
	if ($ts === false) {
		return '-';
	}
	$d = (int)date('j', $ts);
	$m = (int)date('n', $ts);
	$y = (int)date('Y', $ts) + 543;
	$hm = date('H:i', $ts);
	return $d . ' ' . ($thaiMonths[$m] ?? '') . ' ' . $y . ' ' . $hm;
};
?>
<div class="card acard pm-list-card mb-4 shadow-sm border-0">
	<div class="card-header bgc-white border-b-1 brc-default-l2 py-3">
		<form method="get" class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center justify-content-between" style="gap:12px;">
			<input type="hidden" name="page" value="project_deleted">
			<h5 class="card-title text-primary-d2 mb-0 text-120">
				<i class="fa fa-trash-alt mr-2"></i> โครงการที่ถูกลบ
				<span class="badge badge-pill bgc-default-l3 text-dark-m1 text-90 font-normal ml-2 border brc-default-m2"><?= count($rows) ?> รายการ</span>
			</h5>
			<div class="d-flex align-items-center flex-wrap" style="gap:8px;">
				<select name="years_select" class="form-control form-control-sm" style="width:140px;" onchange="this.form.submit()">
					<option value="">ทุกปีงบประมาณ</option>
					<?php foreach ($yearOptions as $y): ?>
						<option value="<?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8') ?>" <?= $yearFilter === $y ? 'selected' : '' ?>><?= htmlspecialchars($y, ENT_QUOTES, 'UTF-8') ?></option>
					<?php endforeach; ?>
				</select>
				<input type="search" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm" style="width:min(100%, 280px);" placeholder="ค้นหารหัส / ชื่อ / ผู้ลบ">
				<button type="submit" class="btn btn-sm btn-primary">ค้นหา</button>
			</div>
		</form>
	</div>
	<div class="card-body pt-3">
		<div class="table-responsive pm-table-wrap">
			<table class="table table-bordered table-hover table-striped text-95 mb-0">
				<thead class="bgc-primary-l4 text-primary-d2 text-uppercase text-90">
					<tr>
						<th class="text-center border-0" style="width:52px;">#</th>
						<th class="text-center border-0" style="width:110px;">รหัสโครงการ</th>
						<th class="border-0">ชื่อโครงการ</th>
						<th class="text-center border-0" style="width:90px;">ปีงบ</th>
						<th class="border-0" style="width:180px;">หน่วยงาน</th>
						<th class="text-center border-0" style="width:160px;">สถานะก่อนลบ</th>
						<th class="border-0" style="width:220px;">ผู้ลบ</th>
						<th class="text-center border-0" style="width:170px;">ลบเมื่อ</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!$rows): ?>
						<tr><td colspan="8" class="text-center text-muted py-4">ยังไม่มีโครงการที่ถูกลบ</td></tr>
					<?php else: ?>
						<?php foreach ($rows as $i => $r):
							$st = (string)($r['status_project_before'] ?? '');
							$stLabel = $statusMap[$st] ?? ($st !== '' ? $st : '-');
							$nr = trim((string)($r['notify_result'] ?? ''));
							if ($nr !== '') {
								$stLabel .= ' / ' . $nr;
							}
							$unitCode = trim((string)($r['code_unit'] ?? ''));
							$unitName = $unitCode !== '' ? trim((string)($unitNames[$unitCode] ?? '')) : '';
							if ($unitName === '') {
								$unitName = $unitCode !== '' ? $unitCode : '-';
							}
							$actorId = (int)($r['actor_staff_id'] ?? 0);
							$actor = $staffNames[$actorId] ?? '';
							if ($actor === '') {
								$actor = trim((string)($r['actor_staff_code'] ?? ''));
							}
							if ($actor === '') {
								$actor = $actorId > 0 ? ('#' . $actorId) : '-';
							}
						?>
						<tr>
							<td class="text-center"><?= $i + 1 ?></td>
							<td class="text-center"><?= htmlspecialchars((string)($r['code_pre_project'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string)($r['name_project'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
							<td class="text-center"><?= htmlspecialchars((string)($r['year_project'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($unitName, ENT_QUOTES, 'UTF-8') ?></td>
							<td class="text-center"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($actor, ENT_QUOTES, 'UTF-8') ?></td>
							<td class="text-center"><?= htmlspecialchars($fmtWhen($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
