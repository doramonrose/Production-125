<?php
/**
 * เตรียมข้อมูลขึ้น Production — เฉพาะข้อมูลพื้นฐาน
 * ไม่รวมโครงการทดสอบ / ไฟล์ผลโครงการ / log workflow
 *
 * เปิดตอน login ฝั่งแผนแล้ว: /project/Plan/export_prod_master.php
 * ดาวน์โหลด SQL: เพิ่ม ?download=1
 */
session_start();
include_once __DIR__ . '/../../config/config_db.php';
include_once __DIR__ . '/../fn/main_project.php';
date_default_timezone_set('Asia/Bangkok');

$sess_code = (string)($_SESSION['code'] ?? '');
$sess_email = (string)($_SESSION['r_email'] ?? '');
if ($sess_code === '' || $sess_email === '' || !($db_person instanceof mysqli)) {
	header('Location: ../index.php');
	exit;
}
$stmt_me = mysqli_prepare($db_person, "SELECT id, code, email FROM staff WHERE code = ? AND email = ? AND work_status IN (0,1) LIMIT 1");
mysqli_stmt_bind_param($stmt_me, 'ss', $sess_code, $sess_email);
mysqli_stmt_execute($stmt_me);
$me = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_me) ?: null);
if (!$me) {
	header('Location: ../index.php');
	exit;
}

$projectMaster = [
	'set_project_type' => 'ประเภทโครงการ',
	'set_project_nature' => 'ลักษณะโครงการ',
	'set_project_consistency' => 'ความสอดคล้อง (SDGs กลุ่ม)',
	'set_project_consistency_detail' => 'รายการความสอดคล้อง/SDGs',
	'set_project_budget' => 'แหล่งงบ (ตารางเดิม ถ้ามี)',
	'budget_set_source' => 'แหล่งงบ PBM ในฐาน project (ถ้ามี)',
	'budget_set_year' => 'ปีงบประมาณในฐาน project (ถ้ามี)',
	'set_project_expenses' => 'ประเภทค่าใช้จ่าย',
	'set_project_expenses_detail' => 'รายการค่าใช้จ่าย + เงื่อนไข',
	'set_project_status' => 'สถานะโครงการ',
	'set_project_target' => 'กลุ่มเป้าหมาย',
	'set_department' => 'หน่วยงานระดับสำนัก/ภาควิชา',
	'set_job' => 'ตำแหน่ง/งาน',
	'set_unit' => 'หน่วยงาน',
	'set_project_personnel' => 'บุคลากรหน่วยงาน + Admin หน่วย',
	'set_department_admin' => 'ผู้ดูแลหน่วยงาน (ถ้ามี)',
];

$kpiMaster = [
	'set_type' => 'กรอบแผน (พันธกิจ/ยุทธศาสตร์/พัฒนา)',
	'set_side' => 'พันธกิจ / ด้านที่พัฒนา',
	'set_so' => 'ยุทธศาสตร์ (SO)',
	'set_ap' => 'แผนปฏิบัติการ (AP)',
	'kpi' => 'ตัวชี้วัดหลัก',
	'kpi_year' => 'ตัวชี้วัดรายปี',
	'kpi_year_indicator' => 'รายละเอียดตัวชี้วัดปี (รวมธงส่งออกไปโครงการ)',
	'kpi_year_indicator_so' => 'ผูก SO กับตัวชี้วัดปี',
];

$pbmMaster = [
	'budget_set_year' => 'ปีงบประมาณ',
	'budget_set_source' => 'แหล่งงบ PBM',
];

$skipHint = [
	'pre_project' => 'โครงการทดสอบ',
	'project_consistency*' => 'ความสอดคล้องของโครงการ',
	'project_activity*' => 'กิจกรรม/งบ/ผล',
	'project_*request*' => 'ขอบรรจุ/ดำเนิน/เปลี่ยน/ยกเลิก',
	'*_action_log' => 'ประวัติพิจารณา',
	'document/project_files' => 'ไฟล์แนบโครงการทดสอบ',
];

function pm_prod_table_exists(mysqli $db, string $table): bool
{
	$esc = mysqli_real_escape_string($db, $table);
	$q = @mysqli_query($db, "SHOW TABLES LIKE '{$esc}'");
	return $q && mysqli_num_rows($q) > 0;
}

function pm_prod_table_count(mysqli $db, string $table): int
{
	$esc = mysqli_real_escape_string($db, $table);
	$q = @mysqli_query($db, "SELECT COUNT(*) AS c FROM `{$esc}`");
	$row = $q ? mysqli_fetch_assoc($q) : null;
	return (int)($row['c'] ?? 0);
}

function pm_prod_sql_value(mysqli $db, $v): string
{
	if ($v === null) {
		return 'NULL';
	}
	return "'" . mysqli_real_escape_string($db, (string)$v) . "'";
}

function pm_prod_dump_table(mysqli $db, string $dbName, string $table): string
{
	if (!pm_prod_table_exists($db, $table)) {
		return "-- SKIP `{$dbName}`.`{$table}` (ไม่มีตาราง)\n\n";
	}
	$out = "-- ----- `{$dbName}`.`{$table}` -----\n";
	$cq = mysqli_query($db, 'SHOW CREATE TABLE `' . mysqli_real_escape_string($db, $table) . '`');
	$cr = $cq ? mysqli_fetch_assoc($cq) : null;
	$create = (string)($cr['Create Table'] ?? '');
	if ($create !== '') {
		$create = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $create, 1);
		$out .= $create . ";\n\n";
	}
	$q = mysqli_query($db, 'SELECT * FROM `' . mysqli_real_escape_string($db, $table) . '`');
	if (!$q) {
		$out .= '-- อ่านข้อมูลไม่สำเร็จ: ' . mysqli_error($db) . "\n\n";
		return $out;
	}
	$n = 0;
	$batch = [];
	$cols = [];
	while ($row = mysqli_fetch_assoc($q)) {
		if (!$cols) {
			$cols = array_keys($row);
		}
		$vals = [];
		foreach ($cols as $c) {
			$vals[] = pm_prod_sql_value($db, $row[$c] ?? null);
		}
		$batch[] = '(' . implode(',', $vals) . ')';
		$n++;
		if (count($batch) >= 40) {
			$colSql = '`' . implode('`,`', array_map(static function ($c) {
				return str_replace('`', '``', $c);
			}, $cols)) . '`';
			$out .= "INSERT INTO `{$table}` ({$colSql}) VALUES\n" . implode(",\n", $batch) . ";\n";
			$batch = [];
		}
	}
	if ($batch) {
		$colSql = '`' . implode('`,`', array_map(static function ($c) {
			return str_replace('`', '``', $c);
		}, $cols)) . '`';
		$out .= "INSERT INTO `{$table}` ({$colSql}) VALUES\n" . implode(",\n", $batch) . ";\n";
	}
	$out .= "-- rows: {$n}\n\n";
	return $out;
}

function pm_prod_inventory(mysqli $db, array $map): array
{
	$rows = [];
	foreach ($map as $table => $label) {
		$exists = pm_prod_table_exists($db, $table);
		$rows[] = [
			'table' => $table,
			'label' => $label,
			'exists' => $exists,
			'count' => $exists ? pm_prod_table_count($db, $table) : 0,
		];
	}
	return $rows;
}

$invProject = ($db_Project instanceof mysqli) ? pm_prod_inventory($db_Project, $projectMaster) : [];
$invKpi = ($db_KPI instanceof mysqli) ? pm_prod_inventory($db_KPI, $kpiMaster) : [];
$invPbm = ($db_pbmBudget instanceof mysqli) ? pm_prod_inventory($db_pbmBudget, $pbmMaster) : [];

$expenseDir = dirname(__DIR__) . '/document/expense_files';
$expenseFiles = [];
if (is_dir($expenseDir)) {
	foreach (scandir($expenseDir) ?: [] as $fn) {
		if ($fn === '.' || $fn === '..' || $fn === '.htaccess') {
			continue;
		}
		if (is_file($expenseDir . '/' . $fn)) {
			$expenseFiles[] = $fn;
		}
	}
}

if (isset($_GET['download']) && (string)$_GET['download'] === '1') {
	$stamp = date('Ymd_His');
	$filename = 'project_prod_master_' . $stamp . '.sql';
	header('Content-Type: application/sql; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	echo "-- PlanWise Project master data for production\n";
	echo '-- Generated: ' . date('Y-m-d H:i:s') . " (Asia/Bangkok)\n";
	echo "-- Scope: master/settings only — no test projects\n";
	echo "SET NAMES utf8;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

	echo "-- ========== DB project ==========\n";
	if ($db_Project instanceof mysqli) {
		foreach (array_keys($projectMaster) as $t) {
			echo pm_prod_dump_table($db_Project, 'project', $t);
		}
	}
	echo "-- ========== DB kpi ==========\n";
	if ($db_KPI instanceof mysqli) {
		foreach (array_keys($kpiMaster) as $t) {
			echo pm_prod_dump_table($db_KPI, 'kpi', $t);
		}
	}
	echo "-- ========== DB pbm_budget ==========\n";
	if ($db_pbmBudget instanceof mysqli) {
		foreach (array_keys($pbmMaster) as $t) {
			echo pm_prod_dump_table($db_pbmBudget, 'pbm_budget', $t);
		}
	}
	echo "SET FOREIGN_KEY_CHECKS=1;\n";
	exit;
}

function pm_prod_h($s): string
{
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="th">
<head>
	<meta charset="utf-8">
	<title>เตรียมข้อมูล Project ขึ้น Production</title>
	<style>
		body{font-family:"Sarabun",Tahoma,sans-serif;margin:24px;color:#111;background:#faf8fd;}
		h1{font-size:1.4rem;margin:0 0 .4rem;}
		.hint{color:#6b7280;margin:0 0 1.2rem;}
		.card{background:#fff;border:1px solid #ece7f4;border-radius:10px;padding:16px 18px;margin:0 0 16px;}
		table{width:100%;border-collapse:collapse;}
		th,td{border-bottom:1px solid #f1eaf8;padding:.45rem .4rem;text-align:left;font-size:.95rem;}
		th{color:#6b7280;font-weight:600;}
		.ok{color:#15803d;}
		.miss{color:#b45309;}
		.btn{display:inline-block;background:#6d28d9;color:#fff;text-decoration:none;padding:.55rem 1rem;border-radius:8px;font-weight:700;}
		ul{margin:.3rem 0 .2rem 1.2rem;}
		code{background:#f3eef8;padding:0 .25rem;border-radius:4px;}
	</style>
</head>
<body>
	<h1>เตรียมข้อมูลระบบ Project ขึ้น Production</h1>
	<p class="hint">ขอบเขตที่เลือก: <strong>ข้อมูลพื้นฐานเท่านั้น</strong> — ไม่ย้ายโครงการทดสอบ</p>
	<p><a class="btn" href="?download=1">ดาวน์โหลดไฟล์ SQL ข้อมูลพื้นฐาน</a></p>

	<div class="card">
		<h2>ฐาน <code>project</code></h2>
		<table>
			<thead><tr><th>ตาราง</th><th>รายการ</th><th>จำนวนแถว</th></tr></thead>
			<tbody>
			<?php foreach ($invProject as $r): ?>
				<tr>
					<td><code><?= pm_prod_h($r['table']) ?></code></td>
					<td><?= pm_prod_h($r['label']) ?></td>
					<td class="<?= $r['exists'] ? 'ok' : 'miss' ?>"><?= $r['exists'] ? (int)$r['count'] : 'ไม่มีตาราง' ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="card">
		<h2>ฐาน <code>kpi</code> ที่หน้าโครงการต้องใช้</h2>
		<table>
			<thead><tr><th>ตาราง</th><th>รายการ</th><th>จำนวนแถว</th></tr></thead>
			<tbody>
			<?php foreach ($invKpi as $r): ?>
				<tr>
					<td><code><?= pm_prod_h($r['table']) ?></code></td>
					<td><?= pm_prod_h($r['label']) ?></td>
					<td class="<?= $r['exists'] ? 'ok' : 'miss' ?>"><?= $r['exists'] ? (int)$r['count'] : 'ไม่มีตาราง' ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="card">
		<h2>ฐาน <code>pbm_budget</code> (ปีงบ / แหล่งงบ)</h2>
		<table>
			<thead><tr><th>ตาราง</th><th>รายการ</th><th>จำนวนแถว</th></tr></thead>
			<tbody>
			<?php foreach ($invPbm as $r): ?>
				<tr>
					<td><code><?= pm_prod_h($r['table']) ?></code></td>
					<td><?= pm_prod_h($r['label']) ?></td>
					<td class="<?= $r['exists'] ? 'ok' : 'miss' ?>"><?= $r['exists'] ? (int)$r['count'] : 'ไม่มีตาราง' ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="card">
		<h2>ไฟล์ที่ต้อง copy ไป Production</h2>
		<p>เอกสารแนบประเภทค่าใช้จ่าย: <code>app/project/document/expense_files/</code> — <?= count($expenseFiles) ?> ไฟล์</p>
		<?php if ($expenseFiles): ?>
			<ul>
				<?php foreach ($expenseFiles as $fn): ?>
					<li><?= pm_prod_h($fn) ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<p>ไม่ต้อง copy โฟลเดอร์โครงการทดสอบ: <code>project_files</code>, <code>activity_result_files</code>, <code>kpi_result_files</code></p>
	</div>

	<div class="card">
		<h2>ไม่ย้าย</h2>
		<ul>
			<?php foreach ($skipHint as $k => $v): ?>
				<li><code><?= pm_prod_h($k) ?></code> — <?= pm_prod_h($v) ?></li>
			<?php endforeach; ?>
			<li>ฐาน <code>person</code> — Production ชี้เครื่องเดิมอยู่แล้ว (staff / ลายเซ็น)</li>
		</ul>
	</div>

	<div class="card">
		<h2>หลังนำเข้า Production</h2>
		<ol>
			<li>สร้างฐานว่าง <code>project</code>, <code>kpi</code>, <code>pbm_budget</code> (ถ้ายังไม่มี)</li>
			<li>นำเข้าไฟล์ SQL ที่ดาวน์โหลด โดยแยกตามฐาน</li>
			<li>copy โฟลเดอร์ <code>expense_files</code></li>
			<li>เปิดระบบครั้งแรกให้ <code>pm_ensure_*</code> สร้างตารางโครงการว่างเอง</li>
			<li>ตรวจเมนูข้อมูลพื้นฐาน: หน่วยงาน, ประเภทค่าใช้จ่าย, ความสอดคล้อง, กรอบแผน KPI</li>
		</ol>
	</div>
</body>
</html>
