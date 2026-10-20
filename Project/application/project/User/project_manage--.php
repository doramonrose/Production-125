<?php
/**
 * หน้าจัดการโครงการ (project_manage.php)
 * - แสดงเฉพาะโครงการของตนเอง หรือโครงการของหน่วยงาน (ถ้าเป็น Admin)
 * - มีปุ่มเพิ่ม/สร้างโครงการ
 */
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');
include_once(__DIR__ . '/../fn/main_staff.php');

$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
$op   = isset($_GET['op']) ? trim((string)$_GET['op']) : '';

// บันทึก Section 2 (reson, result, objective) ผ่าน AJAX
if ($op === 'save_section2_field') {
	header('Content-Type: application/json; charset=utf-8');
	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	$field = isset($_POST['field']) ? trim((string)$_POST['field']) : '';
	$allowed = ['reson', 'result', 'objective'];
	if ($project_id <= 0 || !in_array($field, $allowed, true)) {
		echo json_encode(['ok' => 0, 'msg' => 'ข้อมูลไม่ถูกต้อง']);
		exit;
	}
	$sess_email = $_SESSION['r_email'] ?? '';
	$sess_code = $_SESSION['code'] ?? '';
	$sid = 0;
	$staff_code = '';
	$unit_code = '';
	if ($sess_email !== '' && $sess_code !== '') {
		$st = mysqli_prepare($db_person, 'SELECT id, code, unit_code FROM staff WHERE email = ? AND code = ? LIMIT 1');
		mysqli_stmt_bind_param($st, 'ss', $sess_email, $sess_code);
		mysqli_stmt_execute($st);
		$rs = mysqli_stmt_get_result($st);
		$rw = $rs ? mysqli_fetch_assoc($rs) : null;
		if ($rw) {
			$sid = (int)($rw['id'] ?? 0);
			$staff_code = (string)($rw['code'] ?? '');
			$unit_code = (string)($rw['unit_code'] ?? '');
		}
	}
	if ($sid <= 0 || !staffCanEditPreProjectRichFields($db_Project, $project_id, $sid, $staff_code, $unit_code)) {
		echo json_encode(['ok' => 0, 'msg' => 'ไม่มีสิทธิ์แก้ไขโครงการนี้']);
		exit;
	}
	$value = isset($_POST['value']) ? (string)$_POST['value'] : '';
	$sql = 'UPDATE pre_project SET `' . $field . '` = ? WHERE id = ? LIMIT 1';
	$stmt = mysqli_prepare($db_Project, $sql);
	if (!$stmt) {
		echo json_encode(['ok' => 0, 'msg' => 'เตรียมคำสั่ง SQL ไม่สำเร็จ']);
		exit;
	}
	mysqli_stmt_bind_param($stmt, 'si', $value, $project_id);
	$ok = mysqli_stmt_execute($stmt);
	if (!$ok) {
		$em = mysqli_stmt_error($stmt);
		echo json_encode(['ok' => 0, 'msg' => $em !== '' ? $em : 'บันทึกไม่สำเร็จ']);
		exit;
	}
	echo json_encode(['ok' => 1]);
	exit;
}

// ถ้ามี op (save_project, project_set, update_project ฯลฯ) แต่ไม่ใช่ add_project ให้ project_all จัดการ
if ($op !== '' && $op !== 'add_project') {
	include __DIR__ . '/project_all.php';
	return;
}

// ดึงข้อมูล staff ปัจจุบัน
$sess_email = $_SESSION['r_email'] ?? '';
$sess_code  = $_SESSION['code'] ?? '';
$r_staff = null;
if ($sess_email !== '' && $sess_code !== '') {
	$stmt = mysqli_prepare($db_person, "SELECT * FROM staff WHERE email = ? AND code = ? LIMIT 1");
	mysqli_stmt_bind_param($stmt, 'ss', $sess_email, $sess_code);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
	$r_staff = mysqli_fetch_array($res);
}
if (!$r_staff || !is_array($r_staff)) {
	echo '<div class="alert alert-danger">ไม่พบข้อมูลผู้ใช้</div>';
	return;
}

$staff_id   = $r_staff['id'];
$staff_code = $r_staff['code'];
$code_unit  = $r_staff['unit_code'] ?? '';
$years_select = isset($_GET['years_select']) ? trim((string)$_GET['years_select']) : '';
if (empty($years_select)) {
	$years_select = (string)(date("Y") + 543);
}

$is_unit_admin = isUnitAdmin($db_Project, $staff_code, $code_unit);
$project_list  = getManageableProjectList($db_Project, $years_select, $staff_id, $staff_code, $is_unit_admin, $code_unit);
$year_list     = getYearSelect($db_pbmBudget);
$unit_names    = planBuildSetUnitNamesMap($db_Project);
if ($db_person && function_exists('getUnitSelect')) {
	foreach (getUnitSelect($db_person) as $u) {
		$c = trim((string) ($u['code'] ?? ''));
		if ($c !== '' && !isset($unit_names[$c])) {
			$unit_names[$c] = trim((string) ($u['name'] ?? '')) !== '' ? trim((string) $u['name']) : '-';
		}
	}
}

// หน้าเพิ่มโครงการ (op=add_project)
if ($op === 'add_project') {
	$type_list   = getProjectTypeSelect($db_Project);
	$nature_list = getNatureSelect($db_Project);
	$staff_list  = getStaffSelect($db_person);
	$budget_set_list = getAddBudgetSelect($db_Project);
	$plan_unit_select_groups = getSetUnitSelectGroupsForPlan($db_Project);
	$plan_unit_groups_js = [];
	foreach ($plan_unit_select_groups as $_g) {
		$_opts = [];
		foreach (($_g['units'] ?? []) as $_u) {
			if (!is_array($_u)) {
				continue;
			}
			$_v = planSetUnitOptionValue($_u);
			if ($_v === '') {
				continue;
			}
			$_opts[] = ['val' => $_v, 'name' => planSetUnitOptionLabel($_u)];
		}
		$plan_unit_groups_js[] = ['label' => (string) ($_g['label'] ?? ''), 'options' => $_opts];
	}
	$year_short = substr($years_select, -2);
	$ref_placeholder = 'S' . $year_short . '-XXX';
	$ref_display = $ref_placeholder;

	$edit_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
	$saved_pre = null;
	$saved_heads = [];
	$saved_staffs = [];
	$saved_persons = [];
	$saved_deps = [];
	$saved_budgets = [];
	if ($edit_project_id > 0) {
		$saved_pre = getPreProjectID($db_Project, $edit_project_id);
		$saved_heads = getProjectHeadList($db_Project, $edit_project_id);
		$saved_staffs = getProjectStaffList($db_Project, $edit_project_id);
		$saved_persons = getProjectPersonList($db_Project, $edit_project_id);
		$saved_deps = getProjectDepartmentList($db_Project, $edit_project_id, 1);
		$saved_budgets = getProjectBudgetList($db_Project, $edit_project_id);
		if (is_array($saved_pre)) {
			$years_select = $saved_pre['year_project'] ?? $years_select;
			$code_unit = planResolvePreProjectCodeUnit($db_Project, (string) ($saved_pre['code_unit'] ?? ''));
			if ($code_unit === '') {
				$code_unit = planResolvePreProjectCodeUnit($db_Project, (string) ($r_staff['unit_code'] ?? ''));
			}
			$ref_display = $saved_pre['code_pre_project'] ?? $ref_placeholder;
		} else {
			$saved_pre = [];
		}
	}
	if (!is_array($saved_pre)) {
		$saved_pre = [];
	}
	if ($edit_project_id <= 0) {
		$code_unit = planResolvePreProjectCodeUnit($db_Project, (string) $code_unit);
	}
	// ความสอดคล้อง - ต้องมี project_id (บันทึกแล้ว)
	$consistency_type_list = [];
	$consistency_so_list = [];
	$consistency_ap_list = [];
	$consistency_side_list = [];
	$consistency_current = null;
	if ($edit_project_id > 0 && function_exists('getKpiTypes')) {
		$consistency_type_list = getKpiTypes($db_KPI);
		$consistency_so_list = getSOList($db_KPI);
		$consistency_ap_list = getAPList($db_KPI);
		$consistency_side_list = getSideList($db_KPI);
		$consistency_current = getProjectConsistencyRow($db_Project, $edit_project_id);
	}
	?>
	<style>
		.pm-wizard { --pm-accent: #2563eb; --pm-accent-soft: rgba(37,99,235,.12); --pm-surface: #f8fafc; --pm-border: #e2e8f0; --pm-text: #0f172a; }
		.pm-hero {
			background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 45%, #e0e7ff 100%);
			border: 1px solid var(--pm-border);
			border-radius: 1rem;
			box-shadow: 0 10px 40px -12px rgba(15, 23, 42, .18);
			padding: 1.35rem 1.65rem;
			margin-bottom: 1.5rem;
			position: relative;
			overflow: hidden;
		}
		.pm-hero::after {
			content: '';
			position: absolute;
			right: -40px;
			top: -40px;
			width: 180px;
			height: 180px;
			border-radius: 50%;
			background: radial-gradient(circle, rgba(37,99,235,.15) 0%, transparent 70%);
			pointer-events: none;
		}
		.pm-section-card {
			border-radius: 1rem !important;
			border: 1px solid var(--pm-border) !important;
			box-shadow: 0 4px 24px -8px rgba(15, 23, 42, .12) !important;
			overflow: hidden;
		}
		.pm-section-card .card-body { background: #fff; }
		.pm-section-title {
			display: inline-flex;
			align-items: center;
			gap: .5rem;
			font-weight: 700;
			color: var(--pm-accent);
			letter-spacing: .02em;
		}
		.pm-section-title i { opacity: .9; }
		.pm-pill-soft {
			background: var(--pm-accent-soft);
			color: var(--pm-accent);
			border-radius: 999px;
			padding: .25rem .6rem;
			font-size: .72rem;
			font-weight: 700;
		}
		.pm-list-card {
			border-radius: 1rem !important;
			border: none !important;
			box-shadow: 0 8px 30px -10px rgba(15, 23, 42, .2) !important;
			overflow: hidden;
		}
		.pm-list-card .card-header {
			background: linear-gradient(90deg, #1e293b 0%, #334155 100%) !important;
			color: #f1f5f9 !important;
			border: none !important;
		}
		.pm-list-card .card-header .card-title { color: #f8fafc !important; }
		.pm-table-wrap { border-radius: .75rem; overflow: hidden; border: 1px solid var(--pm-border); }
		.pm-table-wrap table { margin-bottom: 0 !important; }
		.pm-table-wrap thead th {
			background: #f1f5f9 !important;
			color: #334155 !important;
			font-weight: 700;
			text-transform: none;
			border-color: var(--pm-border) !important;
		}
		.pm-table-wrap tbody tr:hover { background: #f8fafc !important; }
		.btn-pm-primary {
			background: linear-gradient(180deg, var(--pm-accent) 0%, #1d4ed8 100%);
			border: none;
			box-shadow: 0 4px 14px -4px rgba(37, 99, 235, .65);
		}
		.btn-pm-primary:hover { filter: brightness(1.05); }
		#modalConsistencyAdd .modal-content { border-radius: 1rem; overflow: hidden; border: none; box-shadow: 0 20px 50px -12px rgba(0,0,0,.35); }
		#modalGeneralInfo .modal-content { border-radius: 1rem; border: none; box-shadow: 0 20px 50px -12px rgba(0,0,0,.3); }
		.add-project-header { background: #fff; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
		.add-project-stepper { display: flex; align-items: flex-start; justify-content: center; max-width: 520px; margin: 1.5rem auto 0; gap: 0; }
		.add-project-step { display: flex; flex-direction: column; align-items: center; flex: 0 0 auto; }
		.add-project-step-line { flex: 1; min-width: 40px; max-width: 80px; height: 2px; background: #dee2e6; margin-top: 17px; }
		.add-project-step-circle { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600; flex-shrink: 0; }
		.add-project-step-circle.done { background: #28a745; color: #fff; }
		.add-project-step-circle.pending { background: #e9ecef; color: #6c757d; }
		.add-project-step-label { font-size: 0.75rem; margin-top: 0.5rem; color: #495057; text-align: center; white-space: nowrap; }
		#modalGeneralInfo .modal-header { border-bottom: 2px solid #007bff; padding: 0.75rem 1rem; }
		#modalGeneralInfo .modal-title { font-size: 1.4rem; font-weight: 700; color: #007bff; }
		#modalGeneralInfo .modal-body { font-size: 1.1rem; }
		#modalGeneralInfo .form-group.row { align-items: center; }
		#modalGeneralInfo .col-form-label,
		#modalGeneralInfo label.text-90,
		#modalGeneralInfo label.mb-0 { color: #6b7280; font-size: 0.9rem; font-weight: 500; }
		#modalGeneralInfo .form-control,
		#modalGeneralInfo select.form-control,
		#modalGeneralInfo input.form-control,
		#modalGeneralInfo textarea.form-control { color: #111827; font-size: 1rem; font-weight: 600; }
		#modalGeneralInfo .modal-body { overflow: visible; }
		#modalGeneralInfo .datepicker { z-index: 1060 !important; }
		.project-header-display { font-size: 1.35rem; font-weight: 900; color: #1d4ed8; letter-spacing: .2px; }
		.project-header-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
		.project-header-sub { font-size: 1.05rem; font-weight: 800; color: #111827; }
		.project-meta-pill { display: inline-flex; align-items: center; gap: .5rem; padding: .35rem .6rem; border-radius: 999px; font-weight: 800; line-height: 1; }
		.project-meta-pill .label { font-weight: 800; opacity: .85; }
		.project-meta-pill.year { background: rgba(59,130,246,.12); color: #1d4ed8; border: 1px solid rgba(59,130,246,.25); }
		.project-meta-pill.ref { background: rgba(168,85,247,.12); color: #7e22ce; border: 1px solid rgba(168,85,247,.25); }
		#general-info-summary .summary-label { min-width: 160px; color: #6b7280; font-size: 0.9rem; font-weight: 500; }
		#general-info-summary .summary-value { color: #111827; font-size: 1rem; font-weight: 600; }
		#general-info-summary .pm-ginfo-banner {
			background: #dbeafe;
			border-bottom: 1px solid rgba(37,99,235,.18);
			color: #1d4ed8;
			padding: .75rem 1rem;
			font-weight: 900;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
		}
		#general-info-summary .pm-ginfo-banner i { opacity: .95; }
		#general-info-summary .pm-ginfo-desc { line-height: 1.6; }
		/* Section 2 (ข้อมูลเป้าหมาย) */
		.pm-section2-banner {
			background: #dbeafe;
			border-bottom: 1px solid rgba(37,99,235,.18);
			color: #1d4ed8;
			padding: .75rem 1rem;
			font-weight: 900;
			display: flex;
			align-items: center;
			gap: .6rem;
			border-radius: 1rem 1rem 0 0;
		}
		.pm-section2-item {
			border-radius: 1rem !important;
			overflow: hidden;
		}
		.pm-section2-item-head {
			background: #ffffff;
			border-bottom: 1px solid var(--pm-border);
			padding: .85rem 1rem;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
		}
		.pm-section2-item-head .pm-section2-item-title {
			color: #1f2937;
			font-weight: 900;
			display: flex;
			align-items: center;
			gap: .5rem;
		}
		.pm-section2-item-body {
			background: #fff;
			padding: 1rem;
		}
		.pm-section2-view {
			min-height:60px;
			border: 0 !important;
			background: transparent !important;
			border-radius: .5rem !important;
			padding: 0 !important;
			box-shadow: none !important;
		}
		.section2-view {
			border: 1px solid #e5e7eb !important;
			background: #f8fafc !important;
			border-radius: .75rem !important;
			padding: 1rem !important;
		}
		#modalSection2Editor .section2-content { line-height: 1.6; }
	</style>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.th.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
	<div class="pm-wizard">
	<form method="POST" action="?page=project_manage&op=save_project" enctype="multipart/form-data" id="form-add-project">
		<input type="hidden" name="op_from" value="add_project">
		<input type="hidden" name="years_select" value="<?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>">
		<input type="hidden" id="edit_project_id" value="<?= (int)$edit_project_id ?>">
	<div class="add-project-header pm-hero">
		<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
			<div class="d-flex align-items-center gap-3 flex-grow-1">
				<span class="badge px-2 py-1" style="background:#fd7e14; color:#fff; border-radius:50px; font-size:0.75rem;">ร่าง</span>
				<h4 class="mb-0 project-header-title">โครงการ</h4>
				<span id="name_project_text" class="project-header-display d-none ml-2"></span>
				<input type="text" name="name_project" id="name_project_input" class="form-control form-control-sm ml-2" required placeholder="กรอกชื่อโครงการ" style="min-width:300px; flex:1;" value="<?= htmlspecialchars($saved_pre['name_project'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
				<span id="name_project_req" class="text-danger">*</span>
			</div>
			<div class="d-flex align-items-center flex-wrap" style="gap: 1rem;">
				<button type="button" class="btn btn-primary btn-sm" id="btnToggleHeaderEdit">
					<i class="fa fa-pencil-alt mr-1"></i> เพิ่ม/แก้ไข
				</button>
				<button type="submit" class="btn btn-success btn-sm">
					<i class="fa fa-save mr-1"></i> บันทึก
				</button>
				<a href="?page=project_manage&years_select=<?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
					<i class="fa fa-arrow-left mr-1"></i> กลับ
				</a>
			</div>
		</div>
		<div class="d-flex flex-wrap align-items-center gap-3 mt-3 pt-3 border-top" style="border-color:#eee!important;">
			<div class="d-flex align-items-center">
				<span class="project-meta-pill year">
					<span class="label">ปีงบประมาณ</span>
					<span id="year_project_text" class="project-header-sub d-none"></span>
				</span>
				<select name="year_project" id="year_project_select" class="form-control form-control-sm ml-2" required style="width:140px;">
					<?php foreach ($year_list as $y): ?>
						<option value="<?= htmlspecialchars($y['budget_year_name']) ?>" <?= ($years_select == $y['budget_year_name']) ? 'selected' : '' ?>><?= htmlspecialchars($y['budget_year_name']) ?></option>
					<?php endforeach ?>
				</select>
				<span id="year_project_req" class="text-danger ml-1">*</span>
			</div>
			<span class="text-muted">|</span>
			<div class="project-meta-pill ref">
				<span class="label">รหัสอ้างอิง</span>
				<strong class="ml-1"><?= htmlspecialchars($ref_display) ?></strong>
				<?php if ($edit_project_id <= 0): ?>
				<small class="text-muted ml-1">(จะถูกสร้างเมื่อบันทึก)</small>
				<?php endif ?>
			</div>
		</div>
		<!-- Stepper -->
		<div class="add-project-stepper">
			<div class="add-project-step">
				<div class="add-project-step-circle done"><i class="fa fa-check"></i></div>
				<span class="add-project-step-label">ข้อมูลทั่วไป</span>
			</div>
			<div class="add-project-step-line"></div>
			<div class="add-project-step">
				<?php
				$section2_done = is_array($saved_pre)
					&& !empty(trim(strip_tags($saved_pre['reson'] ?? '')))
					&& !empty(trim(strip_tags($saved_pre['result'] ?? '')))
					&& !empty(trim(strip_tags($saved_pre['objective'] ?? '')));
				$sc = $section2_done ? 'done' : 'pending';
				$si = $section2_done ? 'check' : 'times';
				$sf = $section2_done ? '0.9rem' : '0.65rem';
				?>
				<div class="add-project-step-circle <?php echo $sc; ?>" id="step-target-circle"><i class="fa fa-<?php echo $si; ?>" style="font-size:<?php echo $sf; ?>"></i></div>
				<span class="add-project-step-label">เป้าหมาย</span>
			</div>
			<div class="add-project-step-line"></div>
			<div class="add-project-step">
				<div class="add-project-step-circle pending"><i class="fa fa-times" style="font-size:0.65rem;"></i></div>
				<span class="add-project-step-label">ตัวชี้วัด</span>
			</div>
			<div class="add-project-step-line"></div>
			<div class="add-project-step">
				<div class="add-project-step-circle pending"><i class="fa fa-times" style="font-size:0.65rem;"></i></div>
				<span class="add-project-step-label">งบประมาณ</span>
			</div>
			<div class="add-project-step-line"></div>
			<div class="add-project-step">
				<div class="add-project-step-circle pending"><i class="fa fa-times" style="font-size:0.65rem;"></i></div>
				<span class="add-project-step-label">กิจกรรม</span>
			</div>
		</div>
	</div>
	<div class="card acard pm-section-card mb-4 shadow-sm border-0">
		<div class="card-body">
				<!-- ส่วนที่ 1 ข้อมูลทั่วไป -->
				<div class="mb-4">
					<div id="general-info-summary" class="text-muted p-0" style="min-height:40px;">
						<small>คลิกปุ่ม เพิ่ม/แก้ไข เพื่อกรอกข้อมูลทั่วไป</small>
					</div>
					<script>window.__autoBuildSummary = <?php echo $edit_project_id > 0 ? 'true' : 'false'; ?>;</script>
				</div>

				<!-- Modal ข้อมูลทั่วไป -->
				<div class="modal fade" id="modalGeneralInfo" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document" style="max-width: 70%;">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title">เพิ่ม/แก้ไขข้อมูลทั่วไป</h5>
								<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
							</div>
							<div class="modal-body py-3">
								<div class="form-group row mb-2">
									<label class="col-md-3 col-form-label text-90">หัวหน้าโครงการ <span class="text-danger">*</span></label>
									<div class="col-md-6">
										<select name="head_staff" class="form-control form-control-sm" data-placeholder="- ค้นหา -">
											<option value="">- ค้นหา -</option>
											<?php foreach ($staff_list as $s): ?>
												<?php
												$head_selected = $saved_heads[0]['staff_project'] ?? ($staff_id ?? 0);
												?>
												<option value="<?= (int)$s['id'] ?>" <?= ((int)$head_selected === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars(($s['fname'] ?? '') . ' ' . ($s['lname'] ?? '')) ?></option>
											<?php endforeach ?>
										</select>
									</div>
									<div class="col-md-3 d-flex align-items-center">
										<label class="mb-0 mr-2 text-90" style="white-space:nowrap;">โทรศัพท์ <span class="text-danger">*</span></label>
										<input type="text" name="head_phone" class="form-control form-control-sm flex-grow-1" placeholder="กรอก" value="<?= htmlspecialchars($saved_heads[0]['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
									</div>
								</div>
								<div class="form-group row mb-2">
									<label class="col-md-3 col-form-label text-90">ผู้ประสานงาน <span class="text-danger">*</span></label>
									<div class="col-md-6">
										<select name="coordinator_staff" class="form-control form-control-sm">
											<option value="">- ค้นหา -</option>
											<?php foreach ($staff_list as $s): ?>
												<?php $coord_selected = $saved_staffs[0]['staff_project'] ?? 0 ?>
												<option value="<?= (int)$s['id'] ?>" <?= ((int)$coord_selected === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars(($s['fname'] ?? '') . ' ' . ($s['lname'] ?? '')) ?></option>
											<?php endforeach ?>
										</select>
									</div>
									<div class="col-md-3 d-flex align-items-center">
										<label class="mb-0 mr-2 text-90" style="white-space:nowrap;">โทรศัพท์ <span class="text-danger">*</span></label>
										<input type="text" name="coordinator_phone" class="form-control form-control-sm flex-grow-1" placeholder="กรอก" value="<?= htmlspecialchars($saved_staffs[0]['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
									</div>
								</div>
								<div id="participant-rows-container">
									<?php
									$person_rows = !empty($saved_persons) ? $saved_persons : [null];
									foreach ($person_rows as $i => $p):
										$is_first = ($i === 0);
										$is_last = ($i === count($person_rows) - 1);
										$p_staff = is_array($p) ? (int)($p['staff_project'] ?? 0) : 0;
										$p_phone = is_array($p) ? ($p['phone'] ?? '') : '';
									?>
									<div class="form-group row mb-2 participant-row">
										<?php if ($is_first): ?>
											<label class="col-md-3 col-form-label text-90">ผู้ร่วมโครงการ (ถ้ามี)</label>
										<?php else: ?>
											<div class="col-md-3"></div>
										<?php endif ?>
										<div class="col-md-6">
											<select name="participant_staff[]" class="form-control form-control-sm">
												<option value="">- ค้นหา -</option>
												<?php foreach ($staff_list as $s): ?>
													<option value="<?= (int)$s['id'] ?>" <?= ($p_staff === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars(($s['fname'] ?? '') . ' ' . ($s['lname'] ?? '')) ?></option>
												<?php endforeach ?>
											</select>
										</div>
										<div class="col-md-2 d-flex align-items-center">
											<label class="mb-0 mr-2 text-90" style="white-space:nowrap;">โทรศัพท์</label>
											<input type="text" name="participant_phone[]" class="form-control form-control-sm flex-grow-1" placeholder="กรอก" value="<?= htmlspecialchars($p_phone, ENT_QUOTES, 'UTF-8') ?>">
										</div>
										<div class="col-md-1 d-flex align-items-center">
											<?php if ($is_last): ?>
												<button type="button" class="btn btn-primary btn-sm btn-add-participant"><i class="fa fa-plus"></i> เพิ่ม</button>
											<?php endif ?>
										</div>
									</div>
									<?php endforeach ?>
								</div>
								<div class="form-group row mb-2">
									<label class="col-md-3 col-form-label text-90">หน่วยงานหลัก <span class="text-danger">*</span></label>
									<div class="col-md-9">
										<select name="code_unit" class="form-control form-control-sm">
											<option value="">- เลือก -</option>
											<?php planRenderSetUnitSelectOptions($plan_unit_select_groups, (string) $code_unit, $db_Project); ?>
										</select>
									</div>
								</div>
								<div id="unit-co-rows-container">
									<?php
									$dep_rows = !empty($saved_deps) ? $saved_deps : [null];
									foreach ($dep_rows as $i => $drow):
										$is_first = ($i === 0);
										$is_last = ($i === count($dep_rows) - 1);
										$dep_val_selected = is_array($drow)
											? planResolvePreProjectCodeUnit($db_Project, (string) ($drow['department_project'] ?? ''))
											: '';
									?>
									<div class="form-group row mb-2 unit-co-row">
										<?php if ($is_first): ?>
											<label class="col-md-3 col-form-label text-90">หน่วยงานร่วม (ถ้ามี)</label>
										<?php else: ?>
											<div class="col-md-3"></div>
										<?php endif ?>
										<div class="col-md-8">
											<select name="code_unit_co[]" class="form-control form-control-sm unit-co-select">
												<option value="">- เลือก -</option>
												<?php planRenderSetUnitSelectOptions($plan_unit_select_groups, $dep_val_selected, $db_Project); ?>
												<option value="99"<?= ($dep_val_selected === '99') ? ' selected' : ''; ?>>- อื่นๆ (ระบุ)</option>
											</select>
											<div class="unit-co-other-wrap mt-2 d-none">
												<input type="text" name="code_unit_co_other[]" class="form-control form-control-sm" placeholder="ระบุหน่วยงาน">
											</div>
										</div>
										<div class="col-md-1 d-flex align-items-center">
											<?php if ($is_last): ?>
												<button type="button" class="btn btn-primary btn-sm btn-add-unit-co"><i class="fa fa-plus"></i> เพิ่ม</button>
											<?php endif ?>
										</div>
									</div>
									<?php endforeach ?>
								</div>
								<div class="form-group row mb-2">
									<label class="col-md-3 col-form-label text-90">ลักษณะโครงการ <span class="text-danger">*</span></label>
									<div class="col-md-9">
										<select name="code_nature" class="form-control form-control-sm">
											<option value="">- เลือก -</option>
											<?php foreach ($nature_list as $n): ?>
												<?php $nature_selected = (string)($saved_pre['code_nature'] ?? '') ?>
												<option value="<?= htmlspecialchars($n['id'] ?? '') ?>" <?= ($nature_selected !== '' && $nature_selected === (string)($n['id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($n['name'] ?? '') ?></option>
											<?php endforeach ?>
										</select>
									</div>
								</div>
								<div class="form-group row mb-2">
									<label class="col-md-3 col-form-label text-90">ประเภทโครงการ <span class="text-danger">*</span></label>
									<div class="col-md-9">
										<select name="code_type" class="form-control form-control-sm">
											<option value="">- เลือก -</option>
											<?php foreach ($type_list as $t): ?>
												<?php $type_selected = (string)($saved_pre['code_type'] ?? '') ?>
												<option value="<?= htmlspecialchars($t['id'] ?? '') ?>" <?= ($type_selected !== '' && $type_selected === (string)($t['id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($t['name'] ?? '') ?></option>
											<?php endforeach ?>
										</select>
									</div>
								</div>
								<div class="form-group row mb-2">
									<label class="col-md-3 col-form-label text-90">แหล่งงบที่ยื่นขอ <span class="text-danger">*</span></label>
									<div class="col-md-9">
										<select name="code_budget" class="form-control form-control-sm" id="modalBudgetSelect">
											<option value="">- เลือก -</option>
											<?php $budget_selected = (string)($saved_budgets[0]['code_budget'] ?? '') ?>
											<?php foreach ($budget_set_list as $b): ?>
												<option value="<?= htmlspecialchars($b['id'] ?? '') ?>" <?= ($budget_selected !== '' && $budget_selected === (string)($b['id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($b['name'] ?? '') ?></option>
											<?php endforeach ?>
											<option value="99" <?= ($budget_selected === '99') ? 'selected' : '' ?>>- อื่นๆ (ระบุ)</option>
										</select>
										<div id="budget_other_wrap" class="mt-2 d-none">
											<input type="text" name="budget_other" class="form-control form-control-sm" placeholder="ระบุแหล่งงบ" value="<?= htmlspecialchars($saved_budgets[0]['budget_other'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
										</div>
									</div>
								</div>
								<div class="form-group row mb-2">
									<label class="col-md-3 col-form-label text-90">ระยะเวลาดำเนินโครงการ <span class="text-danger">*</span></label>
									<?php
									$start_display = (!empty($saved_pre['start_month']) && !empty($saved_pre['start_year'])) ? ($saved_pre['start_month'] . '/' . $saved_pre['start_year']) : '';
									$end_display = (!empty($saved_pre['end_month']) && !empty($saved_pre['end_year'])) ? ($saved_pre['end_month'] . '/' . $saved_pre['end_year']) : '';
									?>
									<div class="col-md-4">
										<label class="col-form-label small">เดือน/ปี เริ่มต้น</label>
										<div class="input-group" id="project_start_date_wrap">
											<input type="text" id="project_start_date" class="form-control form-control-sm" placeholder="คลิกเลือกเดือน/ปี" readonly autocomplete="off" value="<?= htmlspecialchars($start_display, ENT_QUOTES, 'UTF-8') ?>">
											<div class="input-group-append" style="cursor:pointer;">
												<span class="input-group-text"><i class="fa fa-calendar-alt"></i></span>
											</div>
										</div>
										<input type="hidden" name="start_month" id="start_month" value="<?= htmlspecialchars($saved_pre['start_month'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
										<input type="hidden" name="start_year" id="start_year" value="<?= htmlspecialchars($saved_pre['start_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
										<input type="hidden" name="start_date_text" id="start_date_text" value="<?= htmlspecialchars($start_display, ENT_QUOTES, 'UTF-8') ?>">
									</div>
									<div class="col-md-5">
										<label class="col-form-label small">เดือน/ปี สิ้นสุด</label>
										<div class="input-group" id="project_end_date_wrap">
											<input type="text" id="project_end_date" class="form-control form-control-sm" placeholder="คลิกเลือกเดือน/ปี" readonly autocomplete="off" value="<?= htmlspecialchars($end_display, ENT_QUOTES, 'UTF-8') ?>">
											<div class="input-group-append" style="cursor:pointer;">
												<span class="input-group-text"><i class="fa fa-calendar-alt"></i></span>
											</div>
										</div>
										<input type="hidden" name="end_month" id="end_month" value="<?= htmlspecialchars($saved_pre['end_month'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
										<input type="hidden" name="end_year" id="end_year" value="<?= htmlspecialchars($saved_pre['end_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
										<input type="hidden" name="end_date_text" id="end_date_text" value="<?= htmlspecialchars($end_display, ENT_QUOTES, 'UTF-8') ?>">
									</div>
								</div>
								<div class="form-group row mb-2">
									<label class="col-md-3 col-form-label text-90">พื้นที่ดำเนินโครงการ <span class="text-danger">*</span></label>
									<div class="col-md-9">
										<textarea name="location" class="form-control form-control-sm" rows="2" placeholder="กรอก"><?= htmlspecialchars($saved_pre['location'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
									</div>
								</div>
								<input type="hidden" name="project_old" value="">
								<input type="hidden" name="budget_used" value="0">
								<input type="hidden" name="num_people" value="0">
							</div>
							<div class="modal-footer py-2">
								<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">ปิด</button>
								<button type="button" class="btn btn-success btn-sm" id="btnModalSaveGeneral"><i class="fa fa-save mr-1"></i> บันทึกในหน้าจอ</button>
							</div>
						</div>
					</div>
				</div>
				<script>
				document.getElementById('modalBudgetSelect').addEventListener('change', function(){
					document.getElementById('budget_other_wrap').classList.toggle('d-none', this.value !== '99');
				});
				// ปฏิทินเลือกเดือน/ปี (แสดง พ.ศ. — เลือกย้อนหลังได้หลายปี)
				var yearBuddhist = <?= (int)$years_select ?>;
				var yearChristian = yearBuddhist - 543;
				var pickerMinYearCe = yearChristian - 15;
				var pickerMaxYearCe = yearChristian + 5;
				var pickerMinDate = new Date(pickerMinYearCe, 0, 1);
				var pickerMaxDate = new Date(pickerMaxYearCe, 11, 31);

				function monthYearToIndex(month, yearBe) {
					var m = parseInt(month, 10) || 0;
					var y = parseInt(yearBe, 10) || 0;
					if (y > 0 && y < 2400) y += 543;
					if (m < 1 || m > 12 || y <= 0) return -1;
					return y * 12 + m;
				}
				function validateProjectPeriodRange() {
					var si = monthYearToIndex($('#start_month').val(), $('#start_year').val());
					var ei = monthYearToIndex($('#end_month').val(), $('#end_year').val());
					if (si < 0 || ei < 0) return null;
					if (ei < si) {
						return 'เดือน/ปี สิ้นสุดต้องไม่น้อยกว่าเดือน/ปี เริ่มต้น กรุณาเลือกใหม่';
					}
					return null;
				}
				function setProjectMonthYearFields($input, d, prefix) {
					if (!d) return;
					var m = d.getMonth() + 1;
					var yBe = d.getFullYear() + 543;
					$('#' + prefix + '_month').val(m);
					$('#' + prefix + '_year').val(yBe);
					var txt = m + '/' + yBe;
					$input.val(txt);
					$('#' + prefix + '_date_text').val(txt);
				}
				function patchPickerYearsToBE($input) {
					var dp = $input.data('datepicker');
					if (!dp || !dp.picker) return;
					var $p = dp.picker;
					$p.find('.datepicker-switch, .year').each(function () {
						var n = parseInt(String($(this).text()).replace(/[^\d]/g, ''), 10);
						if (!isNaN(n) && n >= 1900 && n < 2400) {
							$(this).text(String(n + 543));
						}
					});
				}
				function bindPickerBeDisplay($input) {
					$input.on('show changeMonth changeYear update', function () {
						setTimeout(function () { patchPickerYearsToBE($input); }, 0);
					});
				}
				var dpCommon = {
					language: 'th',
					format: 'mm/yyyy',
					startView: 'months',
					minViewMode: 'months',
					autoclose: true,
					orientation: 'bottom auto',
					container: '#modalGeneralInfo .modal-body',
					startDate: pickerMinDate,
					endDate: pickerMaxDate
				};
				$('#project_start_date').datepicker(dpCommon).on('changeDate', function (e) {
					var d = e.date;
					if (!d) return;
					setProjectMonthYearFields($('#project_start_date'), d, 'start');
					$('#project_end_date').datepicker('setStartDate', d);
					var endVal = $('#project_end_date').datepicker('getDate');
					if (endVal && endVal < d) {
						$('#project_end_date').datepicker('clearDates');
						$('#end_month').val('');
						$('#end_year').val('');
						$('#end_date_text').val('');
						$('#project_end_date').val('');
					}
				});
				$('#project_end_date').datepicker(dpCommon).on('changeDate', function (e) {
					var d = e.date;
					if (!d) return;
					var startVal = $('#project_start_date').datepicker('getDate');
					if (startVal && d < startVal) {
						alert('เดือน/ปี สิ้นสุดต้องไม่น้อยกว่าเดือน/ปี เริ่มต้น');
						$('#project_end_date').datepicker('clearDates');
						$('#end_month').val('');
						$('#end_year').val('');
						$('#end_date_text').val('');
						$('#project_end_date').val('');
						return;
					}
					setProjectMonthYearFields($('#project_end_date'), d, 'end');
					$('#project_start_date').datepicker('setEndDate', d);
				});
				bindPickerBeDisplay($('#project_start_date'));
				bindPickerBeDisplay($('#project_end_date'));
				$('#project_start_date_wrap').on('click', function(){ $('#project_start_date').focus(); });
				$('#project_end_date_wrap').on('click', function(){ $('#project_end_date').focus(); });
				function prefillDatepickersFromSaved() {
					try {
						var sm = parseInt($('#start_month').val() || '0', 10);
						var sy = parseInt($('#start_year').val() || '0', 10);
						// รองรับทั้งปี พ.ศ. (2567) และ ค.ศ. (2024)
						var startYearCe = (sy > 2400) ? (sy - 543) : sy;
						if (sm >= 1 && sm <= 12 && sy > 1900) {
							var startD = new Date(startYearCe, sm - 1, 1);
							$('#project_start_date').datepicker('setDate', startD);
							setProjectMonthYearFields($('#project_start_date'), startD, 'start');
							$('#project_end_date').datepicker('setStartDate', startD);
						}
						var em = parseInt($('#end_month').val() || '0', 10);
						var ey = parseInt($('#end_year').val() || '0', 10);
						var endYearCe = (ey > 2400) ? (ey - 543) : ey;
						if (em >= 1 && em <= 12 && ey > 1900) {
							var endD = new Date(endYearCe, em - 1, 1);
							$('#project_end_date').datepicker('setDate', endD);
							setProjectMonthYearFields($('#project_end_date'), endD, 'end');
						}
					} catch(e) {}
				}
				$('#modalGeneralInfo').on('shown.bs.modal', function(){
					// ใช้ setTimeout เพื่อให้ modal แสดงเต็มที่ก่อน datepicker อัปเดต
					setTimeout(function(){
						prefillDatepickersFromSaved();
						// ถ้า setDate ไม่ทำงาน ให้ set value โดยตรง (เดือน/ปี)
						var v1 = $('#project_start_date').val();
						if (!v1 && $('#start_month').val() && $('#start_year').val()) {
							$('#project_start_date').val($('#start_month').val() + '/' + $('#start_year').val());
						}
						var v2 = $('#project_end_date').val();
						if (!v2 && $('#end_month').val() && $('#end_year').val()) {
							$('#project_end_date').val($('#end_month').val() + '/' + $('#end_year').val());
						}
						$('#project_start_date').datepicker('update');
						$('#project_end_date').datepicker('update');
						// ตรวจสอบสถานะว่าแก้ไขแล้วหรือยัง (เพื่อเปลี่ยนสีปุ่มในแถบสรุป)
						try { updateGeneralInfoDirty(); } catch(e) {}
					}, 150);
					// show budget other if needed
					if ($('#modalBudgetSelect').val() === '99') {
						$('#budget_other_wrap').removeClass('d-none');
					}
				});
				// prefill เดือน/ปี ครั้งแรกเมื่อโหลดหน้า (สำหรับโหมดแก้ไข)
				$(function(){ prefillDatepickersFromSaved(); });
				document.getElementById('modalGeneralInfo').addEventListener('change', function(e){
					if (e.target.classList.contains('unit-co-select')) {
						var wrap = e.target.closest('.unit-co-row').querySelector('.unit-co-other-wrap');
						if (wrap) wrap.classList.toggle('d-none', e.target.value !== '99');
					}
					// ถ้าผู้ใช้แก้ไขข้อมูล ให้เปลี่ยนปุ่มแก้ไขเป็นสีเหลือง
					try { updateGeneralInfoDirty(); } catch(err) {}
				});
				document.getElementById('modalGeneralInfo').addEventListener('input', function(){
					// เช่นพิมพ์โทรศัพท์/คำอธิบาย/สถานที่
					try { updateGeneralInfoDirty(); } catch(err) {}
				});
				// เพิ่มผู้ร่วมโครงการ
				var participantOptions = <?= json_encode(array_map(function($s){ return ['id'=>(int)$s['id'], 'name'=>($s['fname']??'').' '.($s['lname']??'')]; }, $staff_list)) ?>;
				document.getElementById('participant-rows-container').addEventListener('click', function(e){
					if (e.target.closest('.btn-add-participant')) {
						var container = this;
						var lastRow = container.querySelector('.participant-row:last-child');
						var opts = participantOptions.map(function(s){ return '<option value="'+s.id+'">'+s.name+'</option>'; }).join('');
						var html = '<div class="form-group row mb-2 participant-row"><div class="col-md-3"></div><div class="col-md-6"><select name="participant_staff[]" class="form-control form-control-sm"><option value="">- ค้นหา -</option>'+opts+'</select></div><div class="col-md-2 d-flex align-items-center"><label class="mb-0 mr-2 text-90" style="white-space:nowrap;">โทรศัพท์</label><input type="text" name="participant_phone[]" class="form-control form-control-sm flex-grow-1" placeholder="กรอก"></div><div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-primary btn-sm btn-add-participant"><i class="fa fa-plus"></i> เพิ่ม</button></div></div>';
						lastRow.querySelector('.col-md-1').innerHTML = '';
						container.insertAdjacentHTML('beforeend', html);
					}
				});
				function escapeHtml(str) {
					return String(str ?? '')
						.replace(/&/g, '&amp;')
						.replace(/</g, '&lt;')
						.replace(/>/g, '&gt;')
						.replace(/"/g, '&quot;')
						.replace(/'/g, '&#039;');
				}
				// เพิ่มหน่วยงานร่วม
				var planUnitGroupsJs = <?= json_encode($plan_unit_groups_js, JSON_UNESCAPED_UNICODE) ?>;
				function buildPlanUnitCoSelectOptionsHtml() {
					var html = '<option value="">- เลือก -</option>';
					(planUnitGroupsJs || []).forEach(function(g) {
						var opts = g.options || [];
						if (!opts.length) return;
						html += '<optgroup label="' + escapeHtml(g.label || '') + '">';
						opts.forEach(function(x) {
							html += '<option value="' + escapeHtml(x.val) + '">' + escapeHtml(x.name) + '</option>';
						});
						html += '</optgroup>';
					});
					html += '<option value="99">- อื่นๆ (ระบุ)</option>';
					return html;
				}
				document.getElementById('unit-co-rows-container').addEventListener('click', function(e){
					if (e.target.closest('.btn-add-unit-co')) {
						var container = this;
						var lastRow = container.querySelector('.unit-co-row:last-child');
						var opts = buildPlanUnitCoSelectOptionsHtml();
						var html = '<div class="form-group row mb-2 unit-co-row"><div class="col-md-3"></div><div class="col-md-8"><select name="code_unit_co[]" class="form-control form-control-sm unit-co-select">' + opts + '</select><div class="unit-co-other-wrap mt-2 d-none"><input type="text" name="code_unit_co_other[]" class="form-control form-control-sm" placeholder="ระบุหน่วยงาน"></div></div><div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-primary btn-sm btn-add-unit-co"><i class="fa fa-plus"></i> เพิ่ม</button></div></div>';
						lastRow.querySelector('.col-md-1').innerHTML = '';
						container.insertAdjacentHTML('beforeend', html);
					}
				});

				function optText(sel) {
					if (!sel) return '';
					var opt = sel.options[sel.selectedIndex];
					return (opt && opt.value) ? opt.text : '';
				}

				function getInputVal(selector) {
					var el = document.querySelector(selector);
					return el ? (el.value || '') : '';
				}

				function getGeneralInfoStateStr() {
					var modal = document.getElementById('modalGeneralInfo');
					if (!modal) return '';

					function getValBySel(sel) {
						var el = modal.querySelector(sel);
						return el ? String(el.value || '').trim() : '';
					}

					var headStaff = getValBySel('select[name="head_staff"]');
					var headPhone = getValBySel('input[name="head_phone"]');
					var coordinatorStaff = getValBySel('select[name="coordinator_staff"]');
					var coordinatorPhone = getValBySel('input[name="coordinator_phone"]');

					var participantRows = Array.from(modal.querySelectorAll('#participant-rows-container .participant-row'));
					var participants = participantRows.map(function(row){
						var sel = row.querySelector('select[name="participant_staff[]"]');
						var phone = row.querySelector('input[name="participant_phone[]"]');
						return {
							staff: sel ? String(sel.value || '').trim() : '',
							phone: phone ? String(phone.value || '').trim() : ''
						};
					}).filter(function(x){
						return (x.staff || '').trim() !== '' || (x.phone || '').trim() !== '';
					});

					var unitMain = getValBySel('select[name="code_unit"]');
					var unitCoRows = Array.from(modal.querySelectorAll('#unit-co-rows-container .unit-co-row'));
					var unitCos = unitCoRows.map(function(row){
						var sel = row.querySelector('select[name="code_unit_co[]"]');
						var other = row.querySelector('.unit-co-other-wrap input[name="code_unit_co_other[]"]');
						return {
							unit: sel ? String(sel.value || '').trim() : '',
							other: other ? String(other.value || '').trim() : ''
						};
					}).filter(function(x){
						return (x.unit || '').trim() !== '' || (x.other || '').trim() !== '';
					});

					var nature = getValBySel('select[name="code_nature"]');
					var type = getValBySel('select[name="code_type"]');
					var budget = getValBySel('select[name="code_budget"]');
					var budgetOther = getValBySel('input[name="budget_other"]');

					// Date: ใช้ค่า raw เหมือนที่หน้ามีอยู่ (เพื่อเทียบง่าย)
					var startDateRaw = '';
					var projectStart = document.getElementById('project_start_date');
					var startDateText = document.getElementById('start_date_text');
					if (startDateText && startDateText.value) startDateRaw = String(startDateText.value || '').trim();
					else if (projectStart && projectStart.value) startDateRaw = String(projectStart.value || '').trim();
					else {
						var sm = getValBySel('#start_month');
						var sy = getValBySel('#start_year');
						startDateRaw = (sm && sy) ? (sm + '/' + sy) : '';
					}

					var endDateRaw = '';
					var projectEnd = document.getElementById('project_end_date');
					var endDateText = document.getElementById('end_date_text');
					if (endDateText && endDateText.value) endDateRaw = String(endDateText.value || '').trim();
					else if (projectEnd && projectEnd.value) endDateRaw = String(projectEnd.value || '').trim();
					else {
						var em = getValBySel('#end_month');
						var ey = getValBySel('#end_year');
						endDateRaw = (em && ey) ? (em + '/' + ey) : '';
					}

					var location = getValBySel('textarea[name="location"]');

					return JSON.stringify({
						headStaff: headStaff,
						headPhone: headPhone,
						coordinatorStaff: coordinatorStaff,
						coordinatorPhone: coordinatorPhone,
						participants: participants,
						unitMain: unitMain,
						unitCos: unitCos,
						nature: nature,
						type: type,
						budget: budget,
						budgetOther: budgetOther,
						startDateRaw: startDateRaw,
						endDateRaw: endDateRaw,
						location: location
					});
				}

				function syncGeneralInfoBannerButton() {
					var btn = document.querySelector('#general-info-summary .pm-ginfo-banner button[data-target="#modalGeneralInfo"]');
					if (!btn) return;
					btn.classList.remove('btn-pm-primary', 'btn-warning', 'text-white', 'text-dark');
					if (window.__ginfo_dirty) {
						btn.classList.add('btn-warning', 'text-dark');
					} else {
						btn.classList.add('btn-pm-primary', 'text-white');
					}
				}

				function updateGeneralInfoDirty() {
					var last = window.__ginfo_lastSavedStateStr || '';
					var cur = getGeneralInfoStateStr();
					var dirty = (cur !== last);
					window.__ginfo_dirty = dirty;
					syncGeneralInfoBannerButton();
				}

				function buildGeneralInfoSummary() {
					var markSaved = (arguments.length > 0 && !!arguments[0]);
					if (markSaved) {
						// เมื่อต้องการ “บันทึกในหน้าจอ” ให้ถือว่า baseline ใหม่ล่าสุดคือค่าปัจจุบัน
						window.__ginfo_lastSavedStateStr = getGeneralInfoStateStr();
						window.__ginfo_dirty = false;
					}

					var modal = document.getElementById('modalGeneralInfo');
					var headName = optText(modal ? modal.querySelector('select[name="head_staff"]') : null);
					var headPhone = modal ? getInputVal('#modalGeneralInfo input[name="head_phone"]') : '';
					var coordName = optText(modal ? modal.querySelector('select[name="coordinator_staff"]') : null);
					var coordPhone = modal ? getInputVal('#modalGeneralInfo input[name="coordinator_phone"]') : '';

					var participantRows = Array.from((modal ? modal : document).querySelectorAll('#participant-rows-container .participant-row'));
					var participants = participantRows.map(function(row) {
						var sel = row.querySelector('select[name="participant_staff[]"]');
						var phone = row.querySelector('input[name="participant_phone[]"]');
						var name = optText(sel);
						var ph = phone ? phone.value.trim() : '';
						if (!name && !ph) return null;
						return name + (ph ? ' (' + ph + ')' : '');
					}).filter(Boolean);

					var unitMain = optText(modal ? modal.querySelector('select[name="code_unit"]') : null);
					var unitCoRows = Array.from((modal ? modal : document).querySelectorAll('#unit-co-rows-container .unit-co-row'));
					var unitCos = unitCoRows.map(function(row) {
						var sel = row.querySelector('select[name="code_unit_co[]"]');
						var name = optText(sel);
						if (!name) return null;
						var otherWrap = row.querySelector('.unit-co-other-wrap input[name="code_unit_co_other[]"]');
						var otherVal = otherWrap ? otherWrap.value.trim() : '';
						if ((sel && sel.value === '99') && otherVal) return otherVal;
						return name;
					}).filter(Boolean);

					var nature = optText(modal ? modal.querySelector('select[name="code_nature"]') : null);
					var type = optText(modal ? modal.querySelector('select[name="code_type"]') : null);
					var budgetSel = modal ? modal.querySelector('select[name="code_budget"]') : null;
					var budgetName = optText(budgetSel);
					var budgetOther = modal ? getInputVal('#modalGeneralInfo input[name="budget_other"]') : '';
					if (budgetSel && budgetSel.value === '99' && budgetOther) budgetName = budgetOther;
					if (!budgetName && budgetOther) budgetName = budgetOther;

					var startDateTxt = (document.getElementById('start_date_text')?.value || '').trim() || (document.getElementById('project_start_date')?.value || '').trim();
					var endDateTxt = (document.getElementById('end_date_text')?.value || '').trim() || (document.getElementById('project_end_date')?.value || '').trim();
					if (!startDateTxt) {
						var sm = getInputVal('#start_month'); var sy = getInputVal('#start_year');
						if (sm && sy) startDateTxt = sm + '/' + sy;
					}
					if (!endDateTxt) {
						var em = getInputVal('#end_month'); var ey = getInputVal('#end_year');
						if (em && ey) endDateTxt = em + '/' + ey;
					}
					function monthYearToThai(s) {
						if (!s || typeof s !== 'string') return s || '-';
						var parts = s.trim().split('/');
						var m = parseInt(parts[0], 10);
						var y = parts[1] || '';
						var thaiMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
						var monthName = (m >= 1 && m <= 12) ? thaiMonths[m - 1] : s;
						return monthName + (y ? ' ' + y : '');
					}
					startDateTxt = monthYearToThai(startDateTxt);
					endDateTxt = monthYearToThai(endDateTxt);

					var locEl = modal ? modal.querySelector('textarea[name="location"]') : null;
					var location = (locEl ? (locEl.value || '') : '').trim();

					// ปรับสีปุ่มในแถบสรุปตามสถานะ "ยังไม่ได้บันทึก"
					var btnClass = window.__ginfo_dirty
						? 'btn btn-sm btn-warning text-dark'
						: 'btn btn-sm btn-pm-primary text-white';

					var left = [
						{ label: 'หัวหน้าโครงการ', value: headName, phone: headPhone },
						{ label: 'ผู้ประสานงาน', value: coordName, phone: coordPhone },
						{ label: 'ผู้ร่วมโครงการ (ถ้ามี)', value: participants.length ? participants.join(', ') : '-' },
						{ label: 'หน่วยงานหลัก', value: unitMain || '-' },
						{ label: 'หน่วยงานร่วม (ถ้ามี)', value: unitCos.length ? unitCos.join(', ') : '-' }
					];
					var right = [
						{ label: 'ลักษณะโครงการ', value: nature || '-' },
						{ label: 'ประเภทโครงการ', value: type || '-' },
						{ label: 'แหล่งงบที่ยื่นขอ', value: budgetName || '-' },
						{ label: 'ระยะเวลาดำเนินโครงการ', value: (startDateTxt || endDateTxt) ? ('เริ่ม: ' + (startDateTxt || '-') + ' | สิ้นสุด: ' + (endDateTxt || '-')) : '-' },
						{ label: 'พื้นที่ดำเนินการ', value: location || '-' }
					];

					function rowHtml(item) {
						var v = item.value ?? '-';
						if (item.phone !== undefined) {
							var phone = (item.phone || '').trim();
							v = (item.value || '-') + (phone ? ' (' + phone + ')' : '');
						}
						return '<div class="d-flex py-1 align-items-start" style="gap:10px;">'
							+ '<div class="summary-label">' + escapeHtml(item.label) + ' :</div>'
							+ '<div class="summary-value flex-grow-1">' + escapeHtml(v) + '</div>'
							+ '</div>';
					}

					var html = ''
						+ '<div style="overflow:hidden;" class="pm-ginfo-single">'
						+ '  <div class="pm-ginfo-banner">'
						+ '    <span><i class="fa fa-info-circle"></i> ส่วนที่ 1 ข้อมูลทั่วไป</span>'
						+ '    <button type="button" class="' + btnClass + '" data-toggle="modal" data-target="#modalGeneralInfo"><i class="fa fa-pencil-alt mr-1"></i> แก้ไข</button>'
						+ '  </div>'
						+ '  <div class="p-3">'
						+ '    <div class="row">'
						+ '      <div class="col-md-6">' + left.map(rowHtml).join('') + '</div>'
						+ '      <div class="col-md-6">' + right.map(rowHtml).join('') + '</div>'
						+ '    </div>'
						+ '  </div>'
						+ '</div>';

					document.getElementById('general-info-summary').innerHTML = html;
				}

				document.getElementById('btnModalSaveGeneral').addEventListener('click', function(){
					buildGeneralInfoSummary(true);
				});
				if (window.__autoBuildSummary) {
					setTimeout(function(){ try { buildGeneralInfoSummary(true); } catch(e){} }, 0);
				}

				function setHeaderMode(mode) {
					var isView = mode === 'view';
					var nameInput = document.getElementById('name_project_input');
					var nameText = document.getElementById('name_project_text');
					var nameReq = document.getElementById('name_project_req');
					var yearSel = document.getElementById('year_project_select');
					var yearText = document.getElementById('year_project_text');
					var yearReq = document.getElementById('year_project_req');

					if (isView) {
						nameText.textContent = (nameInput.value || '').trim();
						yearText.textContent = yearSel.value || '';
						nameText.classList.remove('d-none');
						yearText.classList.remove('d-none');
						nameInput.classList.add('d-none');
						yearSel.classList.add('d-none');
						nameReq.classList.add('d-none');
						yearReq.classList.add('d-none');
					} else {
						nameText.classList.add('d-none');
						yearText.classList.add('d-none');
						nameInput.classList.remove('d-none');
						yearSel.classList.remove('d-none');
						nameReq.classList.remove('d-none');
						yearReq.classList.remove('d-none');
						setTimeout(function(){ nameInput.focus(); }, 0);
					}
				}

				document.getElementById('btnToggleHeaderEdit').addEventListener('click', function(){
					var nameInput = document.getElementById('name_project_input');
					var isCurrentlyView = nameInput.classList.contains('d-none');
					setHeaderMode(isCurrentlyView ? 'edit' : 'view');
				});

				document.getElementById('name_project_input').addEventListener('input', function(){
					var t = document.getElementById('name_project_text');
					if (t) t.textContent = (this.value || '').trim();
				});
				document.getElementById('year_project_select').addEventListener('change', function(){
					var t = document.getElementById('year_project_text');
					if (t) t.textContent = this.value || '';
				});

				// ถ้าเป็นโครงการที่บันทึกแล้ว ให้เริ่มต้นเป็นโหมดแสดงข้อความ
				setHeaderMode(<?= ($edit_project_id > 0 ? "'view'" : "'edit'") ?>);
				// เช็คเดือน/ปี: เดือน/ปี สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มต้น
				document.getElementById('form-add-project').addEventListener('submit', function(e){
					var periodErr = validateProjectPeriodRange();
					if (periodErr) {
						e.preventDefault();
						alert(periodErr);
						$('#project_end_date').focus();
						return false;
					}
				});
				</script>

				<!-- ส่วนที่ 2 ข้อมูลเป้าหมาย -->
				<div class="mb-4 pm-section2-wrap">
					<div class="pm-section2-banner"><i class="fa fa-bullseye"></i> ส่วนที่ 2 ข้อมูลเป้าหมาย</div>
					<?php
					$section2_fields = [
						['name' => 'reson', 'label' => 'ความสำคัญของโครงการ/หลักการและเหตุผล', 'saved' => $saved_pre['reson'] ?? ''],
						['name' => 'result', 'label' => 'ทบทวนผลการดำเนินโครงการที่ผ่านมา', 'saved' => $saved_pre['result'] ?? ''],
						['name' => 'objective', 'label' => 'วัตถุประสงค์ของโครงการ', 'saved' => $saved_pre['objective'] ?? ''],
					];
					foreach ($section2_fields as $f):
						$has_val = !empty(trim(strip_tags($f['saved'] ?? '')));
						$icon = ($f['name'] === 'reson') ? 'fa-info-circle' : (($f['name'] === 'result') ? 'fa-rotate-left' : 'fa-bullseye');
					?>
						<div class="pm-section-card pm-section2-item mb-3">
							<div class="pm-section2-item-head">
								<div class="pm-section2-item-title">
									<i class="fa <?= $icon ?>"></i>
									<?= htmlspecialchars($f['label']) ?> <span class="text-danger">*</span>
								</div>
								<button type="button" class="btn btn-sm btn-section2-edit <?= $has_val ? 'btn-warning' : 'btn-primary' ?>" data-field="<?= $f['name'] ?>" data-label="<?= htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?>">
									<?= $has_val ? '<i class="fa fa-edit mr-1"></i> แก้ไข' : '<i class="fa fa-plus mr-1"></i> เพิ่ม' ?>
								</button>
							</div>
							<div class="pm-section2-item-body">
								<div id="section2-view-<?= $f['name'] ?>" class="pm-section2-view">
									<?php if ($has_val): ?>
										<div class="section2-content text-dark"><?= $f['saved'] ?></div>
									<?php else: ?>
										<div class="text-muted">กรอก</div>
									<?php endif ?>
								</div>
								<textarea name="<?= $f['name'] ?>" id="section2-<?= $f['name'] ?>" class="d-none"><?= htmlspecialchars($f['saved'], ENT_QUOTES, 'UTF-8') ?></textarea>
							</div>
						</div>
					<?php endforeach ?>
				</div>
				<!-- Modal แก้ไข Section 2 (ขนาดเท่า Modal ข้อมูลทั่วไป) -->
				<div class="modal fade" id="modalSection2Editor" tabindex="-1" role="dialog" data-backdrop="static">
					<div class="modal-dialog" role="document" style="max-width: 70%;">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="modalSection2EditorTitle">แก้ไข</h5>
								<button type="button" class="close btn-section2-close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							</div>
							<div class="modal-body py-3">
								<div id="section2-summernote-wrap"><textarea id="section2-summernote-editor"></textarea></div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary btn-section2-close">ปิด</button>
								<button type="button" class="btn btn-primary" id="btnSection2ModalSave"><i class="fa fa-save mr-1"></i> บันทึก</button>
							</div>
						</div>
					</div>
				</div>
				<script>
				(function(){
					var currentField = null;
					var $editor = null;
					$(document).on('click', '.btn-section2-edit', function(){
						var field = $(this).data('field');
						var label = $(this).data('label');
						var ta = document.getElementById('section2-' + field);
						var val = ta ? ta.value : '';
						currentField = field;
						$('#modalSection2EditorTitle').text(label);
						$('#section2-summernote-editor').val(val);
						if ($editor && $editor.length) { try { $editor.summernote('destroy'); } catch(e){} }
						$editor = $('#section2-summernote-editor');
						$editor.summernote({
							height: 320,
							toolbar: [['style',['style']],['font',['bold','italic','underline','clear']],['para',['ul','ol','paragraph']],['insert',['link']],['view',['fullscreen','codeview']]],
							placeholder: 'กรอก'
						});
						$('#modalSection2Editor').modal('show');
					});
					$('#btnSection2ModalSave').on('click', function(){
						if (!currentField) return;
						var html = $editor.summernote('code') || '';
						$('#section2-' + currentField).val(html);
						var viewEl = document.getElementById('section2-view-' + currentField);
						if (viewEl) {
							viewEl.innerHTML = html ? '<div class="section2-content text-dark">' + html + '</div>' : '<div class="text-muted">กรอก</div>';
						}
						var $btn = $('.btn-section2-edit[data-field="' + currentField + '"]');
						$btn.removeClass('btn-primary btn-warning').addClass(html ? 'btn-warning' : 'btn-primary');
						$btn.html(html ? '<i class="fa fa-edit mr-1"></i> แก้ไข' : '<i class="fa fa-plus mr-1"></i> เพิ่ม');
						var projectId = parseInt($('#edit_project_id').val() || '0', 10);
						if (projectId > 0) {
							$.ajax({
								url: '?page=project_manage&op=save_section2_field',
								type: 'POST',
								data: { project_id: projectId, field: currentField, value: html },
								dataType: 'json',
								success: function(r) {
									if (r.ok) {
										Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1500 });
									} else {
										Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: (r && r.msg) ? r.msg : 'กรุณาลองใหม่' });
									}
								},
								error: function() {
									Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'เกิดข้อผิดพลาดในการบันทึก' });
								}
							});
						}
						try { $editor.summernote('destroy'); } catch(e){}
						$('#modalSection2Editor').modal('hide');
						updateStepTarget();
						currentField = null;
					});
					function updateStepTarget() {
						var r = ($('#section2-reson').val() || '').replace(/<[^>]*>/g, '').trim();
						var s = ($('#section2-result').val() || '').replace(/<[^>]*>/g, '').trim();
						var o = ($('#section2-objective').val() || '').replace(/<[^>]*>/g, '').trim();
						var done = r && s && o;
						var $c = $('#step-target-circle');
						$c.removeClass('done pending').addClass(done ? 'done' : 'pending');
						$c.find('i').removeClass('fa-check fa-times').addClass(done ? 'fa-check' : 'fa-times').css('font-size', done ? '0.9rem' : '0.65rem');
					}
					$(document).on('click', '#modalSection2Editor .btn-section2-close', function(){
						try { if ($editor && $editor.length) $editor.summernote('destroy'); } catch(e){}
						$('#modalSection2Editor').modal('hide');
					});
					$('#modalSection2Editor').on('hidden.bs.modal', function(){
						if ($editor && $editor.length) { try { $editor.summernote('destroy'); } catch(e){} }
					});
				})();
				</script>

				<!-- ส่วนที่ 3 ความสอดคล้อง -->
				<div class="mb-4">
					<h6 class="pm-section-title border-bottom pb-2 mb-3" style="width:100%;"><i class="fa fa-link"></i> ส่วนที่ 3 ความสอดคล้อง <span class="text-danger">*</span></h6>
					<?php if ($edit_project_id > 0): ?>
						<?php
						$consistency_kpi_list = [];
						if ($consistency_current && !empty($consistency_current['id'])) {
							$consistency_kpi_list = getProjectConsistencyKPIDetail($db_Project, $consistency_current['id']);
						}
						$consistency_has_data = $consistency_current && (
							!empty($consistency_current['code_kpi_type']) ||
							!empty($consistency_current['code_so']) ||
							!empty($consistency_current['code_side']) ||
							!empty($consistency_kpi_list)
						);
						$type_name = '';
						$so_name = ''; $ap_name = ''; $side_name = '';
						if ($consistency_current) {
							if (!empty($consistency_current['code_kpi_type']) && function_exists('getSetType')) {
								$t = getSetType($db_KPI, $consistency_current['code_kpi_type']);
								$type_name = $t['name'] ?? '';
							}
							if (!empty($consistency_current['code_so']) && function_exists('getSetSO')) {
								$so = getSetSO($db_KPI, $consistency_current['code_so']);
								$so_name = $so['name'] ?? '';
							}
							if (!empty($consistency_current['code_ap']) && function_exists('getSetAP')) {
								$ap = getSetAP($db_KPI, $consistency_current['code_ap']);
								$ap_name = $ap['name'] ?? '';
							}
							if (!empty($consistency_current['code_side']) && function_exists('getSetSide')) {
								$sd = getSetSide($db_KPI, $consistency_current['code_side']);
								$side_name = $sd['name'] ?? '';
							}
						}
						?>
						<div class="d-flex justify-content-between align-items-start mb-2">
							<div id="consistency-summary" class="flex-grow-1 text-dark" style="min-height:40px;">
								<?php if ($consistency_has_data): ?>
									<div class="mb-1"><strong><?= htmlspecialchars($type_name) ?></strong></div>
									<?php if ($so_name || $ap_name): ?>
										<div class="text-95 mb-1"><?= htmlspecialchars($so_name) ?><?= ($so_name && $ap_name) ? ' / ' : '' ?><?= htmlspecialchars($ap_name) ?></div>
									<?php endif ?>
									<?php if ($side_name): ?>
										<div class="text-95 mb-1"><?= htmlspecialchars($side_name) ?></div>
									<?php endif ?>
									<?php if (!empty($consistency_kpi_list) && function_exists('getKPI')): ?>
										<div class="mt-2">
											<?php foreach ($consistency_kpi_list as $kpi_item):
												$kpi = getKPI($db_KPI, $kpi_item['code_kpi_detail']);
												$kpi_name = $kpi['name'] ?? ('ID:' . ($kpi_item['code_kpi_detail'] ?? ''));
											?>
												<div class="text-90"><i class="fa fa-caret-right fa-xs text-primary mr-1"></i><?= htmlspecialchars($kpi_name) ?></div>
											<?php endforeach ?>
										</div>
									<?php endif ?>
								<?php else: ?>
									<small class="text-muted">คลิกปุ่ม เพิ่ม/แก้ไข เพื่อระบุความสอดคล้องกับแผนกลยุทธ์หรือแผนพันธกิจ</small>
								<?php endif ?>
							</div>
							<button type="button" class="btn btn-sm ml-2 <?= $consistency_has_data ? 'btn-warning' : 'btn-primary' ?>" data-toggle="modal" data-target="#modalConsistencyAdd">
								<i class="fa fa-<?= $consistency_has_data ? 'edit' : 'plus' ?> mr-1"></i> เพิ่ม/แก้ไข
							</button>
						</div>
					<?php else: ?>
						<p class="text-muted"><small>กรุณาบันทึกโครงการก่อน เพื่อเพิ่มความสอดคล้อง</small></p>
					<?php endif ?>
				</div>

				<!-- ส่วนที่ 4 ข้อมูลตัวชี้วัดความสำเร็จ (ตาราง - สามารถเพิ่มในหน้าแก้ไขได้) -->
				<div class="mb-4">
					<h6 class="pm-section-title border-bottom pb-2 mb-3" style="width:100%;"><i class="fa fa-chart-line"></i> ส่วนที่ 4 ข้อมูลตัวชี้วัดความสำเร็จ</h6>
					<p class="text-muted text-95">สามารถเพิ่มตัวชี้วัดรายเดือนได้หลังจากบันทึกโครงการแล้ว ในหน้าแก้ไขโครงการ</p>
				</div>

				<!-- ส่วนที่ 5 ข้อมูลประมาณการงบประมาณ (ตาราง - สามารถเพิ่มในหน้าแก้ไขได้) -->
				<div class="mb-4">
					<h6 class="pm-section-title border-bottom pb-2 mb-3" style="width:100%;"><i class="fa fa-money-bill"></i> ส่วนที่ 5 ข้อมูลประมาณการงบประมาณ</h6>
					<p class="text-muted text-95">สามารถเพิ่มรายการงบประมาณได้หลังจากบันทึกโครงการแล้ว ในหน้าแก้ไขโครงการ</p>
				</div>

				<!-- ผู้รับผิดชอบอยู่ที่ Modal ข้อมูลทั่วไป -->

				<div class="d-flex flex-wrap gap-2 pt-2">
					<a href="?page=project_manage&years_select=<?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary"><i class="fa fa-arrow-left mr-1"></i> กลับ</a>
					<button type="submit" class="btn btn-pm-primary text-white"><i class="fa fa-save mr-1"></i> บันทึกโครงการ (ส่งข้อมูล)</button>
				</div>
				<script>
				(function () {
					var $form = $('#form-add-project');
					if (!$form.length) return;
					$form.on('submit', function (e) {
						e.preventDefault();
						var fd = new FormData(this);
						var $btns = $form.find('button[type="submit"]');
						$btns.prop('disabled', true);
						$.ajax({
							url: $form.attr('action'),
							type: 'POST',
							data: fd,
							processData: false,
							contentType: false,
							dataType: 'json'
						}).done(function (res) {
							if (res && res.ok === 1 && res.redirect) {
								if (typeof Swal !== 'undefined') {
									Swal.fire({ icon: 'success', title: 'บันทึกโครงการสำเร็จ', showConfirmButton: false, timer: 1000 }).then(function () {
										window.location.href = res.redirect;
									});
								} else {
									window.location.href = res.redirect;
								}
								return;
							}
							var msg = (res && res.msg) ? res.msg : 'ไม่สามารถบันทึกข้อมูลได้';
							if (typeof Swal !== 'undefined') {
								Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: msg });
							} else {
								alert(msg);
							}
						}).fail(function (xhr) {
							var msg = 'ไม่สามารถบันทึกข้อมูลได้';
							try {
								var j = JSON.parse(xhr.responseText || '{}');
								if (j && j.msg) msg = j.msg;
							} catch (err) { /* ignore */ }
							if (typeof Swal !== 'undefined') {
								Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: msg });
							} else {
								alert(msg);
							}
						}).always(function () {
							$btns.prop('disabled', false);
						});
					});
				})();
				</script>
			</form>
				<?php if ($edit_project_id > 0 && !empty($consistency_type_list)): ?>
				<!-- Modal ความสอดคล้อง: อยู่นอก form หลัก (HTML ห้ามซ้อน form) -->
				<div class="modal fade modal-lg" id="modalConsistencyAdd" tabindex="-1">
					<div class="modal-dialog modal-lg">
						<form id="form-consistency-kpi" method="POST" action="?page=project_manage&op=save_data_consistency_kpi_project">
							<input type="hidden" name="ajax" value="1">
							<div class="modal-content">
								<div class="modal-header bg-primary text-white">
									<h5 class="modal-title"><i class="fa fa-check-circle mr-1"></i> ระบุความสอดคล้อง</h5>
									<button type="button" class="close text-white" data-dismiss="modal">&times;</button>
								</div>
								<div class="modal-body">
									<p class="text-muted small mb-3">ความสอดคล้องกับแผนกลยุทธ์หรือแผนพันธกิจ (แผนประจำ) - เลือก เชิงกลยุทธ์ หรือ เชิงพันธกิจ (ประจำ)</p>
									<label class="font-weight-bold">ประเภท (Type)</label>
									<select id="selectConsistencyType" name="code_kpi_type" class="form-control mb-3">
										<option value="">- เลือกประเภท -</option>
										<?php foreach ($consistency_type_list as $t): ?>
											<option value="<?= htmlspecialchars($t['id']) ?>" <?= (($consistency_current['code_kpi_type'] ?? '') == $t['id']) ? 'selected' : '' ?>>
												<?= htmlspecialchars($t['name']) ?>
											</option>
										<?php endforeach ?>
									</select>
									<div id="boxConsistencySO" class="mb-3" style="display:none;">
										<label>ยุทธศาสตร์ (SO)</label>
										<select id="selectConsistencySO" name="code_so" class="form-control">
											<option value="">- เลือก -</option>
											<?php foreach ($consistency_so_list as $so): ?>
												<option value="<?= htmlspecialchars($so['id']) ?>" <?= (($consistency_current['code_so'] ?? '') == $so['id']) ? 'selected' : '' ?>><?= htmlspecialchars($so['name']) ?></option>
											<?php endforeach ?>
										</select>
									</div>
									<div id="boxConsistencyAP" class="mb-3" style="display:none;">
										<label>แผนปฏิบัติการ (AP)</label>
										<select id="selectConsistencyAP" name="code_ap" class="form-control">
											<option value="">- เลือก -</option>
											<?php foreach ($consistency_ap_list as $ap): ?>
												<option value="<?= htmlspecialchars($ap['id']) ?>" <?= (($consistency_current['code_ap'] ?? '') == $ap['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ap['name']) ?></option>
											<?php endforeach ?>
										</select>
									</div>
									<div id="boxConsistencySide" class="mb-3" style="display:none;">
										<label>ด้าน</label>
										<select id="selectConsistencySide" name="code_side" class="form-control">
											<option value="">- เลือก -</option>
											<?php foreach ($consistency_side_list as $sd): ?>
												<option value="<?= htmlspecialchars($sd['id']) ?>" <?= (($consistency_current['code_side'] ?? '') == $sd['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sd['name']) ?></option>
											<?php endforeach ?>
										</select>
									</div>
									<hr>
									<label class="font-weight-bold">ตัวชี้วัด</label>
									<div id="boxConsistencyKPI" class="border rounded p-3 bg-light">
										<div class="alert alert-info text-center mb-0">กรุณาเลือกประเภทก่อน</div>
									</div>
								</div>
								<input type="hidden" name="project_id" value="<?= (int)$edit_project_id ?>">
								<input type="hidden" name="years_select" value="<?= htmlspecialchars($years_select) ?>">
								<input type="hidden" name="op_from" value="add_project">
								<input type="hidden" name="overview" value="">
								<input type="hidden" name="type" value="<?= ($consistency_current && !empty($consistency_current['id'])) ? 'update' : 'insert' ?>">
								<?php if ($consistency_current && !empty($consistency_current['id'])): ?>
									<input type="hidden" name="project_consistency_id" value="<?= (int)$consistency_current['id'] ?>">
								<?php endif ?>
								<div class="modal-footer d-flex flex-wrap justify-content-between align-items-center">
									<div>
										<?php if ($consistency_current && !empty($consistency_current['id'])): ?>
											<button type="button" class="btn btn-outline-danger" id="btnConsistencyDelete">
												<i class="fa fa-trash mr-1"></i> ลบความสอดคล้อง
											</button>
										<?php endif ?>
									</div>
									<div class="ml-auto">
										<button type="submit" class="btn btn-success"><i class="fa fa-check mr-1"></i> บันทึก</button>
										<button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<script>
				(function(){
					var projectId = <?= (int)$edit_project_id ?>;
					function showConsistencyBoxes(){
						var t = document.getElementById('selectConsistencyType').value;
						var boxSO = document.getElementById('boxConsistencySO');
						var boxAP = document.getElementById('boxConsistencyAP');
						var boxSide = document.getElementById('boxConsistencySide');
						boxSO.style.display = (t == '2') ? 'block' : 'none';
						boxAP.style.display = (t == '2') ? 'block' : 'none';
						boxSide.style.display = (t == '1') ? 'block' : 'none';
					}
					function loadConsistencyKPI(){
						var typeVal = document.getElementById('selectConsistencyType').value;
						if (!typeVal) {
							document.getElementById('boxConsistencyKPI').innerHTML = '<div class="alert alert-info text-center mb-0">กรุณาเลือกประเภทก่อน</div>';
							return;
						}
						var soVal = (document.getElementById('selectConsistencySO')||{}).value || '';
						var apVal = (document.getElementById('selectConsistencyAP')||{}).value || '';
						var sideVal = (document.getElementById('selectConsistencySide')||{}).value || '';
						var xhr = new XMLHttpRequest();
						xhr.open('POST', 'get_kpi.php', true);
						xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
						xhr.onreadystatechange = function(){
							if (xhr.readyState === 4) {
								try {
									var res = JSON.parse(xhr.responseText || '[]');
									var html = '';
									if (!res || res.length === 0) {
										html = '<div class="alert alert-warning mb-0">ไม่พบ KPI</div>';
									} else {
										res.forEach(function(item){
											html += '<div class="form-check mb-1"><input type="checkbox" name="kpi_list[]" value="'+item.kpi_id+'" class="form-check-input" '+(item.checked?'checked':'')+'><label class="form-check-label">'+(item.kpi_name||'')+'</label></div>';
										});
									}
									document.getElementById('boxConsistencyKPI').innerHTML = html;
								} catch(e) { document.getElementById('boxConsistencyKPI').innerHTML = '<div class="alert alert-danger mb-0">เกิดข้อผิดพลาด</div>'; }
							}
						};
						xhr.send('project_id='+projectId+'&type='+encodeURIComponent(typeVal)+'&so='+encodeURIComponent(soVal)+'&ap='+encodeURIComponent(apVal)+'&side='+encodeURIComponent(sideVal));
					}
					document.getElementById('selectConsistencyType').addEventListener('change', function(){ showConsistencyBoxes(); loadConsistencyKPI(); });
					document.getElementById('modalConsistencyAdd').addEventListener('shown.bs.modal', function(){
						showConsistencyBoxes();
						loadConsistencyKPI();
					});
					var soEl = document.getElementById('selectConsistencySO');
					var apEl = document.getElementById('selectConsistencyAP');
					var sideEl = document.getElementById('selectConsistencySide');
					if (soEl) soEl.addEventListener('change', loadConsistencyKPI);
					if (apEl) apEl.addEventListener('change', loadConsistencyKPI);
					if (sideEl) sideEl.addEventListener('change', loadConsistencyKPI);
				})();
				</script>
				<script>
				(function () {
					var $fc = $('#form-consistency-kpi');
					if (!$fc.length) return;
					var consistencyUrl = $fc.attr('action');
					$fc.on('submit', function (e) {
						e.preventDefault();
						var fd = new FormData(this);
						fd.set('ajax', '1');
						var $sub = $fc.find('button[type="submit"]');
						$sub.prop('disabled', true);
						$.ajax({
							url: consistencyUrl,
							type: 'POST',
							data: fd,
							processData: false,
							contentType: false,
							dataType: 'json'
						}).done(function (res) {
							if (res && res.ok === 1 && res.redirect) {
								if (typeof Swal !== 'undefined') {
									Swal.fire({ icon: 'success', title: 'บันทึกความสอดคล้องแล้ว', showConfirmButton: false, timer: 1100 })
										.then(function () { window.location.href = res.redirect; });
								} else {
									window.location.href = res.redirect;
								}
							} else {
								var msg = (res && res.msg) ? res.msg : 'บันทึกไม่สำเร็จ';
								if (typeof Swal !== 'undefined') {
									Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: msg });
								} else {
									alert(msg);
								}
							}
						}).fail(function (xhr) {
							var msg = 'เกิดข้อผิดพลาด';
							try {
								var j = JSON.parse(xhr.responseText || '{}');
								if (j && j.msg) { msg = j.msg; }
							} catch (err) { /* ignore */ }
							if (typeof Swal !== 'undefined') {
								Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: msg });
							} else {
								alert(msg);
							}
						}).always(function () {
							$sub.prop('disabled', false);
						});
					});
					function runConsistencyDelete () {
						var fd = new FormData();
						fd.append('ajax', '1');
						fd.append('op_from', $fc.find('[name="op_from"]').val() || 'add_project');
						fd.append('years_select', $fc.find('[name="years_select"]').val() || '');
						fd.append('project_id', $fc.find('[name="project_id"]').val() || '');
						fd.append('overview', $fc.find('[name="overview"]').val() || '');
						fd.append('type', 'delete');
						var pcid = $fc.find('[name="project_consistency_id"]').val();
						if (!pcid) {
							if (typeof Swal !== 'undefined') {
								Swal.fire({ icon: 'error', text: 'ไม่พบรหัสความสอดคล้อง' });
							}
							return;
						}
						fd.append('project_consistency_id', pcid);
						var $bd = $('#btnConsistencyDelete');
						$bd.prop('disabled', true);
						$.ajax({
							url: consistencyUrl,
							type: 'POST',
							data: fd,
							processData: false,
							contentType: false,
							dataType: 'json'
						}).done(function (res) {
							if (res && res.ok === 1 && res.redirect) {
								if (typeof Swal !== 'undefined') {
									Swal.fire({ icon: 'success', title: 'ลบความสอดคล้องแล้ว', showConfirmButton: false, timer: 1100 })
										.then(function () { window.location.href = res.redirect; });
								} else {
									window.location.href = res.redirect;
								}
							} else {
								var msg = (res && res.msg) ? res.msg : 'ลบไม่สำเร็จ';
								if (typeof Swal !== 'undefined') {
									Swal.fire({ icon: 'error', text: msg });
								} else {
									alert(msg);
								}
							}
						}).fail(function () {
							if (typeof Swal !== 'undefined') {
								Swal.fire({ icon: 'error', text: 'เกิดข้อผิดพลาด' });
							} else {
								alert('เกิดข้อผิดพลาด');
							}
						}).always(function () {
							$bd.prop('disabled', false);
						});
					}
					$(document).on('click', '#btnConsistencyDelete', function () {
						if (typeof Swal === 'undefined') {
							if (confirm('ต้องการลบความสอดคล้องหรือไม่?')) { runConsistencyDelete(); }
							return;
						}
						Swal.fire({
							icon: 'warning',
							title: 'ลบความสอดคล้อง?',
							text: 'การลบจะยกเลิกความสอดคล้องของโครงการนี้',
							showCancelButton: true,
							confirmButtonText: 'ลบ',
							cancelButtonText: 'ยกเลิก',
							confirmButtonColor: '#dc3545'
						}).then(function (r) {
							if (r.isConfirmed) { runConsistencyDelete(); }
						});
					});
				})();
				</script>
				<?php endif ?>
		</div>
	</div>
	</div><!-- /.pm-wizard -->
	<?php
	return;
}
?>
<div class="card acard pm-list-card mb-4 shadow-sm border-0">
	<div class="card-header bgc-white border-b-1 brc-default-l2 py-3 d-flex justify-content-between align-items-center flex-wrap">
		<h5 class="card-title text-primary-d2 mb-0 text-120">
			<i class="fa fa-cog mr-2"></i> จัดการโครงการ
			<?php if ($is_unit_admin): ?>
				<span class="badge bgc-info text-90 ml-2">Admin หน่วยงาน</span>
			<?php endif ?>
		</h5>
		<div class="d-flex align-items-center gap-2">
			<select class="form-control form-control-sm ace-select" style="width: 140px;"
				onchange="location = '?page=project_manage&years_select=' + this.value;">
				<?php foreach ($year_list as $y): ?>
					<option value="<?= htmlspecialchars($y['budget_year_name']) ?>" <?= ($years_select == $y['budget_year_name']) ? 'selected' : '' ?>>
						<?= htmlspecialchars($y['budget_year_name']) ?>
					</option>
				<?php endforeach ?>
			</select>
			<a href="?page=project_manage&op=add_project&years_select=<?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
				<i class="fa fa-plus mr-1"></i> เพิ่มโครงการ
			</a>
		</div>
	</div>
	<div class="card-body">
		<div class="table-responsive pm-table-wrap">
			<table class="table table-bordered table-hover table-striped text-95 mb-0">
				<thead class="bgc-primary-l4 text-primary-d2 text-uppercase text-90">
					<tr>
						<th class="text-center border-0" style="width: 50px;">#</th>
						<th class="border-0">รหัสโครงการ</th>
						<th class="border-0">ชื่อโครงการ</th>
						<th class="border-0">หน่วยงาน</th>
						<th class="text-right border-0">งบประมาณ (บาท)</th>
						<th class="text-center border-0">สถานะ</th>
						<th class="text-center border-0" style="width: 120px;">การจัดการ</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($project_list)): ?>
						<?php foreach ($project_list as $idx => $row):
							$unit_name = isset($row['code_unit']) && isset($unit_names[$row['code_unit']]) ? $unit_names[$row['code_unit']] : '-';
							$budget = isset($row['budget_total']) ? (float)$row['budget_total'] : 0;
							$status_txt = '-';
							$status_class = 'secondary';
							if (isset($row['status_project'])) {
								if ($row['status_project'] == '0') { $status_txt = 'ร่าง'; $status_class = 'warning'; }
								elseif ($row['status_project'] == '1') { $status_txt = 'แผนปฏิบัติการ'; $status_class = 'success'; }
								elseif ($row['status_project'] == '2') { $status_txt = 'รอขอบรรจุโครงการ'; $status_class = 'purple'; }
							}
						?>
							<tr>
								<td class="text-center"><?= $idx + 1 ?></td>
								<td><?= htmlspecialchars($row['code_pre_project'] ?? '') ?></td>
								<td><?= htmlspecialchars($row['name_project'] ?? '') ?></td>
								<td><?= htmlspecialchars($unit_name) ?></td>
								<td class="text-right"><?= number_format($budget, 2) ?></td>
								<td class="text-center"><span class="badge bgc-<?= $status_class ?>"><?= $status_txt ?></span></td>
								<td class="text-center">
									<a href="?page=project_manage&op=add_project&project_id=<?= (int)($row['id'] ?? 0) ?>&years_select=<?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-xs">
										<i class="fa fa-edit"></i> แก้ไข
									</a>
								</td>
							</tr>
						<?php endforeach ?>
					<?php else: ?>
						<tr><td colspan="7" class="text-center text-secondary py-4">ไม่พบโครงการ</td></tr>
					<?php endif ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
