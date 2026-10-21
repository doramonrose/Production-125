<?php
// ถูก include จาก page.php ที่ start session แล้ว — ไม่เรียก session_start()
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');
require_once __DIR__ . '/project_save_project_execute.php';
require_once __DIR__ . '/project_save_consistency_execute.php';

$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
$op   = isset($_GET['op']) ? trim((string)$_GET['op']) : '';

// POST save_project: ต้องจบด้วย JSON/HTML จาก handler เท่านั้น — ห้ามส่งออก CSS/HTML ของไฟล์นี้ก่อน (ทำให้ AJAX ขึ้น "เกิดข้อผิดพลาด" แม้บันทึกสำเร็จ)
if ($op === 'save_project' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	project_all_execute_save_project();
	exit;
}
// POST ความสอดคล้อง ส่วนที่ 2: เหมือนกัน — ถ้าปล่อยให้พิมพ์ <style> ก่อน JSON หน้าจะขึ้น "เกิดข้อผิดพลาด" ทั้งที่บันทึกแล้ว
if ($op === 'save_data_consistency_kpi_project' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	project_all_execute_save_consistency_kpi();
	exit;
}
?>
<!-- <style>
	.page-content {
		background-color: #fff !important;
		padding-left: 0 !important;
		padding-right: 0 !important;
	}
</style> -->
<!-- 🔹 CSS สำหรับเอฟเฟกต์ hover -->
<style>
	.icon-btn i {
		transition: all 0.2s ease;
		cursor: pointer;xs
	}

	.icon-btn:hover i {
		transform: scale(1.2);
	}

	.icon-btn.edit i {
		color: #ff6f00ff;
	}

	.icon-btn.edit:hover i {
		color: #cd8a0cff;
	}

	.icon-btn.delete i {
		color: #dc3545;
	}

	.icon-btn.delete:hover i {
		color: #b30000;
	}




	/* ซ่อน textarea ดิบของ Summernote */
	#summernote-reson {
		display: none !important;
	}

	/* ปรับช่องว่างใน modal ให้สวย */
	.modal-body {
		padding: 1.5rem 2rem;
	}

	.note-editor.note-frame {
		border: 1px solid #dcdcdc;
		border-radius: 0.5rem;
	}

	.note-toolbar {
		background-color: #f9f9f9;
		border-bottom: 1px solid #ddd;
		padding: 6px 10px;
		border-top-left-radius: 0.5rem;
		border-top-right-radius: 0.5rem;
	}

	.note-editable {
		font-family: 'K2D', 'Kanit', sans-serif;
		font-size: 1rem;
		color: #333;
		min-height: 180px;
		padding: 1rem;
	}


	.month-wrapper {
		display: flex;
		flex-wrap: wrap;
		justify-content: start;
		gap: 8px;
		/* ✅ ระยะห่างระหว่างช่อง (ลองปรับเป็น 10px ได้) */
	}

	.month-box {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.month-label {
		font-size: 0.85rem;
		text-align: center;
		margin-bottom: 4px;
	}

	.month-input {
		width: 77px;
		/* ✅ กำหนดความกว้างช่อง */
		text-align: center;
	}

	.mon-wrapper {
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
		justify-content: space-between;
	}

	.mon-box {
		flex: 1 1 calc(16.66% - 10px);
		/* 6 ช่องต่อแถว */
		min-width: 120px;
		/* ป้องกันช่องเล็กเกิน */
		text-align: center;
	}

	.mon-label {
		display: block;
		font-weight: 600;
		margin-bottom: 4px;
	}

	.mon-input {
		width: 100%;
		text-align: right;
	}

	@media (max-width: 992px) {
		.mon-box {
			flex: 1 1 calc(33.33% - 10px);
			/* แสดง 3 ช่องต่อแถว บนอุปกรณ์ขนาดกลาง */
		}
	}

	@media (max-width: 576px) {
		.mon-box {
			flex: 1 1 calc(50% - 10px);
			/* 2 ช่องต่อแถว บนมือถือ */
		}
	}

	/* ภาพรวม: ให้โทน/โครงเหมือนหน้าจัดการโครงการ (project_manage) */
	.po-overview-scope .pm-manage-toolbar .form-control {
		border-radius: .55rem;
	}
	.po-overview-scope .pm-manage-toolbar .btn {
		border-radius: .55rem;
		font-weight: 800;
	}
	.po-overview-scope .pm-manage-toolbar {
		gap: .55rem !important;
	}
	.po-overview-scope .pm-manage-toolbar > * {
		margin-right: .25rem;
	}
	.po-overview-scope .pm-manage-toolbar > *:last-child {
		margin-right: 0;
	}
	.po-overview-scope .po-unit-select-wrap {
		display: inline-block;
		vertical-align: middle;
		border-radius: .55rem;
		border: 1px solid rgba(146, 108, 184, .35);
		background: linear-gradient(180deg, #faf7fc 0%, #f3eef8 100%);
		box-shadow: 0 10px 18px -16px rgba(146, 108, 184, .45);
		overflow: hidden;
	}
	.po-overview-scope .po-unit-select-wrap:focus-within {
		border-color: #926cb8;
		box-shadow: 0 0 0 .15rem rgba(146, 108, 184, .22);
	}
	.po-overview-scope .po-unit-select {
		width: 220px !important;
		min-width: 0;
		height: 38px !important;
		border: none !important;
		background-color: transparent !important;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='10' viewBox='0 0 16 10'%3E%3Cpath fill='none' stroke='%234a3263' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' d='M2 2l6 6 6-6'/%3E%3C/svg%3E");
		background-repeat: no-repeat;
		background-position: right .55rem center;
		background-size: 14px 9px;
		box-shadow: none !important;
		color: #4a3263 !important;
		font-weight: 800 !important;
		padding-left: .75rem !important;
		padding-right: 2rem !important;
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
		cursor: pointer;
	}
	.po-overview-scope .po-unit-select:focus {
		outline: none !important;
		box-shadow: none !important;
	}
	.po-overview-scope .pm-year-select-wrap {
		display: inline-block;
		vertical-align: middle;
		border-radius: .55rem;
		border: 1px solid rgba(146, 108, 184, .35);
		background: linear-gradient(180deg, #faf7fc 0%, #f3eef8 100%);
		box-shadow: 0 10px 18px -16px rgba(146, 108, 184, .45);
		overflow: hidden;
	}
	.po-overview-scope .pm-year-select-wrap:focus-within {
		border-color: #926cb8;
		box-shadow: 0 0 0 .15rem rgba(146, 108, 184, .22);
	}
	.po-overview-scope .pm-year-select {
		width: 168px !important;
		min-width: 0;
		height: 38px !important;
		border: none !important;
		background-color: transparent !important;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='10' viewBox='0 0 16 10'%3E%3Cpath fill='none' stroke='%234a3263' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' d='M2 2l6 6 6-6'/%3E%3C/svg%3E");
		background-repeat: no-repeat;
		background-position: right .55rem center;
		background-size: 14px 9px;
		box-shadow: none !important;
		color: #4a3263 !important;
		font-weight: 900 !important;
		padding-left: .75rem !important;
		padding-right: 2rem !important;
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
		cursor: pointer;
	}
	.po-overview-scope .pm-year-select:focus {
		outline: none !important;
		box-shadow: none !important;
	}
	.po-overview-scope #projectOverviewTable {
		border-radius: .65rem;
		overflow: hidden;
	}
	.po-overview-scope #projectOverviewTable thead th {
		background: #f3eef8 !important;
		color: #4a3263 !important;
		font-weight: 800 !important;
		font-size: .92rem !important;
		border-color: #e8def2 !important;
		vertical-align: middle;
		text-align: center !important;
	}
	.po-overview-scope #projectOverviewTable .pm-sortable {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: .35rem;
	}
	.po-overview-scope #projectOverviewTable .pm-sort-icons {
		display: inline-flex;
		flex-direction: column;
		line-height: 1;
		gap: 1px;
	}
	.po-overview-scope #projectOverviewTable .pm-sort-btn {
		border: none;
		background: transparent;
		color: #64748b;
		font-size: .62rem;
		line-height: 1;
		padding: 0;
		width: 14px;
		height: 10px;
		cursor: pointer;
	}
	.po-overview-scope #projectOverviewTable .pm-sort-btn:hover {
		color: #734d96;
	}
	.po-overview-scope #projectOverviewTable .pm-sort-btn.active {
		color: #734d96;
		font-weight: 900;
	}
	.po-overview-scope .po-overview-pager .btn {
		min-width: 36px;
		border-radius: .45rem;
	}
	.po-overview-scope .po-overview-pager .btn.active {
		background: #926cb8;
		color: #fff;
		border-color: #734d96;
	}
	.po-overview-scope .pm-group-switch {
		display: inline-flex;
		gap: .45rem;
		padding: .28rem;
		border-radius: 999px;
		background: #f3eef8;
	}
	.po-overview-scope .pm-group-btn {
		border-radius: 999px;
		font-weight: 600;
		padding: .42rem .95rem;
		text-decoration: none !important;
		font-size: .9rem;
	}
	.po-overview-scope .pm-group-btn.pm-active-draft {
		background: #fef3c7;
		color: #92400e !important;
		border: 1px solid #fde68a;
	}
	.po-overview-scope .pm-group-btn.pm-active-plan {
		background: #dcfce7;
		color: #166534 !important;
		border: 1px solid #bbf7d0;
	}
	.po-overview-scope .pm-group-btn.pm-inactive {
		background: #fff;
		color: #4b5563 !important;
		border: 1px solid #d1d5db;
	}
	.po-overview-scope #projectOverviewTable tbody td {
		vertical-align: top;
		padding-top: .62rem;
		padding-bottom: .62rem;
	}
	.po-overview-scope #projectOverviewTable tbody tr:hover {
		background: #fff !important;
	}
	.po-overview-scope #projectOverviewTable .pm-project-name {
		font-weight: 400;
		color: #334155;
	}
	.po-overview-scope #projectOverviewTable .pm-project-head {
		display: block;
		margin-top: .12rem;
		font-size: .72rem;
		color: #64748b;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge {
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
		gap: .22rem !important;
		width: 9.2rem !important;
		min-width: 9.2rem !important;
		max-width: 9.2rem !important;
		height: 2.15rem !important;
		min-height: 2.15rem !important;
		padding: .16rem .36rem !important;
		border-radius: 999px;
		font-weight: 600;
		font-size: .7rem !important;
		line-height: 1.15 !important;
		letter-spacing: 0;
		border: 1px solid transparent;
		box-shadow: none !important;
		white-space: normal !important;
		text-align: center !important;
		box-sizing: border-box !important;
		overflow: hidden !important;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-ab-chip {
		justify-content: flex-start !important;
		width: auto !important;
		min-width: 0 !important;
		max-width: 18.5rem !important;
		height: auto !important;
		min-height: 2.45rem !important;
		padding: .22rem .78rem .22rem .22rem !important;
		gap: .42rem !important;
		text-align: left !important;
		overflow: visible !important;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge i {
		font-size: .66rem !important;
		opacity: .85;
		flex-shrink: 0;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-draft {
		background: #fef9c3;
		color: #854d0e;
		border-color: #fde68a;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-plan {
		background: #dcfce7;
		color: #166534;
		border-color: #bbf7d0;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-queue {
		background: #f3e8ff;
		color: #6b21a8;
		border-color: #e9d5ff;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-unknown {
		background: #f1f5f9;
		color: #475569;
		border-color: #e2e8f0;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-sent {
		background: #e0f2fe;
		color: #1e40af;
		border-color: #bae6fd;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-include_project {
		background: #fce7f3;
		color: #9d174d;
		border-color: #fbcfe8;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-included_plan {
		background: #ccfbf1;
		color: #0f766e;
		border-color: #99f6e4;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-approved {
		background: #dcfce7;
		color: #166534;
		border-color: #bbf7d0;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-report_pending {
		background: #fef9c3;
		color: #854d0e;
		border-color: #fde68a;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-report_done {
		background: #e0f2fe;
		color: #1e40af;
		border-color: #bae6fd;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-returned {
		background: #ffedd5;
		color: #9a3412;
		border-color: #fed7aa;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-a0,
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-a1,
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-b1 {
		background: #fff6de;
		color: #c48a12;
		border-color: #f0d48a;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-a2,
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-b2 {
		background: #f4eefb;
		color: #5b3a8c;
		border-color: #e0d0f2;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-a_fix,
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-b_fix {
		background: #fff1e6;
		color: #d35400;
		border-color: #f5c9a6;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-a_reject {
		background: #fdecec;
		color: #d32f2f;
		border-color: #f5c2c2;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-b3 {
		background: #e8f4fc;
		color: #2b8bb8;
		border-color: #c5e4f5;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-b4,
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-b4_done,
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-b5 {
		background: #e8f6ea;
		color: #2e7d32;
		border-color: #c5e6c8;
	}
	.po-overview-scope #projectOverviewTable .pm-status-badge.pm-status-rejected {
		background: #fee2e2;
		color: #991b1b;
		border-color: #fecaca;
	}
	.po-overview-scope #projectOverviewTable .pm-pct-pill {
		display: inline-block;
		min-width: 58px;
		padding: .2rem .55rem;
		border-radius: 999px;
		font-size: .72rem;
		font-weight: 900;
		letter-spacing: .01em;
		border: 1px solid transparent;
	}
	.po-overview-scope #projectOverviewTable .pm-pct-low {
		background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
		color: #991b1b;
		border-color: rgba(153, 27, 27, .18);
	}
	.po-overview-scope #projectOverviewTable .pm-pct-mid {
		background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
		color: #92400e;
		border-color: rgba(146, 64, 14, .2);
	}
	.po-overview-scope #projectOverviewTable .pm-pct-high {
		background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
		color: #166534;
		border-color: rgba(22, 101, 52, .2);
	}
	@media print {
		.po-overview-scope .pm-manage-toolbar {
			display: none !important;
		}
	}
</style>
	<!-- ✅ โหลด CSS / JS ที่จำเป็น
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.th.min.js"></script> -->
	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

	<?php function project()
	{
		global $db_Project, $db_person, $db_pbmBudget;

		$page = $_GET['page'];
		$op_from = $_GET['op'];
		$years_select = $_GET['years_select'] ?? '';
		$unit_filter = isset($_GET['unit_filter']) ? trim((string)$_GET['unit_filter']) : '';
		$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
		$project_group = isset($_GET['project_group']) ? trim((string)$_GET['project_group']) : 'draft';
		if ($project_group !== 'draft' && $project_group !== 'plan') {
			$project_group = 'draft';
		}
		$ov_show_pct_col = ($project_group === 'plan');
		$ov_colspan = $ov_show_pct_col ? 8 : 7;

		// ถ้าเป็นค่าว่าง → ใช้ปีงบประมาณปัจจุบัน (พ.ศ. เริ่ม 1 ต.ค.)
		if (empty($years_select)) {
			$years_select = function_exists('getCurrentBudgetYear')
				? getCurrentBudgetYear()
				: (string)((int)date('Y') + 543 + ((int)date('n') >= 10 ? 1 : 0));
		}

		$year_list = getYearSelect($db_pbmBudget);
		// ชื่อหน่วยงาน: ใช้ set_unit (ฐาน project) — แสดงเฉพาะชื่อ ไม่ใส่รหัสหน่วย
		$unit_names = [];
		if (function_exists('getSetUnitListForPlan') && function_exists('planSetUnitOptionValue')) {
			foreach (getSetUnitListForPlan($db_Project) as $u) {
				if (!is_array($u)) {
					continue;
				}
				$nm = trim((string) ($u['name'] ?? ''));
				if (function_exists('planStripUnitCodeFromLabel')) {
					$nm = planStripUnitCodeFromLabel($nm);
				}
				if ($nm === '') {
					continue;
				}
				$id = planSetUnitOptionValue($u);
				if ($id !== '') {
					$unit_names[$id] = $nm;
				}
				$code = trim((string) ($u['code'] ?? ''));
				if ($code !== '') {
					$unit_names[$code] = $nm;
				}
			}
		}
		if ($db_person && function_exists('getUnitSelect')) {
			foreach (getUnitSelect($db_person) as $u) {
				$c = trim((string) ($u['code'] ?? ''));
				$nm = trim((string) ($u['name'] ?? ''));
				if ($c !== '' && $nm !== '' && !isset($unit_names[$c])) {
					$unit_names[$c] = $nm;
				}
			}
		}
		// กรองหน่วยงาน: ใช้ชุดเดียวกับแบบฟอร์มโครงการ (set_unit กลุ่มที่ 2 ในฐาน project)
		$unit_list = [];
		$plan_unit_filter_groups = function_exists('getSetUnitSelectGroupsForPlan')
			? getSetUnitSelectGroupsForPlan($db_Project)
			: [];
		foreach ($plan_unit_filter_groups as $ginfo) {
			foreach (($ginfo['units'] ?? []) as $u) {
				if (!is_array($u)) {
					continue;
				}
				$st = trim((string) ($u['status'] ?? '1'));
				if ($st !== '' && $st !== '1') {
					continue;
				}
				$val = function_exists('planSetUnitOptionValue') ? planSetUnitOptionValue($u) : (string) ($u['id'] ?? '');
				$nm = function_exists('planSetUnitOptionLabel')
					? planSetUnitOptionLabel($u)
					: trim((string) ($u['name'] ?? ''));
				if ($val === '' || $nm === '') {
					continue;
				}
				$unit_list[] = [
					'code' => $val,
					'name' => $nm,
				];
			}
		}
		if (empty($unit_list) && function_exists('getSetUnitListForPlan') && function_exists('planSetUnitOptionValue')) {
			foreach (getSetUnitListForPlan($db_Project) as $u) {
				if (!is_array($u)) {
					continue;
				}
				$val = planSetUnitOptionValue($u);
				$nm = trim((string) ($u['name'] ?? ''));
				if (function_exists('planStripUnitCodeFromLabel')) {
					$nm = planStripUnitCodeFromLabel($nm);
				}
				if ($val === '' || $nm === '') {
					continue;
				}
				$unit_list[] = [
					'code' => $val,
					'name' => $nm,
				];
			}
		}
		$overview_rows = function_exists('getOverviewProjectList')
			? getOverviewProjectList($db_Project, $years_select, $unit_filter, $q)
			: [];
		$overview_rows_filtered = [];
		if (!empty($overview_rows)) {
			foreach ($overview_rows as $orow) {
				$st = (string)($orow['status_project'] ?? '0');
				$nrRow = trim((string)($orow['notify_result'] ?? ''));
				$__isMidyear = (int)($orow['midyear_request'] ?? 0) === 1;
				$pm_is_notified_included = in_array($nrRow, ['บรรจุแผน', 'บรรจุแผนและแก้ไข'], true);
				if ($project_group === 'draft') {
					// แท็บร่าง: สถานะสาย A เท่านั้น — ไม่ดึงสาย B (เช่น B1 รอยื่นอนุมัติดำเนินการ)
					if ($__isMidyear) {
						continue;
					}
					$allowDraft = in_array($st, ['0', '2', '3', '9', ''], true)
						|| ($st === '4' && !$pm_is_notified_included)
						|| ($st === '8' && $nrRow !== 'บรรจุแผน');
					if ($allowDraft) {
						$overview_rows_filtered[] = $orow;
					}
				} else {
					// แท็บแผนงานประจำปี: บรรจุแผนแล้ว / อนุมัติ / ระหว่างปี
					$allowPlan = ($st === '1')
						|| ($st === '4' && $pm_is_notified_included)
						|| ($st === '8' && $nrRow === 'บรรจุแผน')
						|| $__isMidyear;
					if ($allowPlan) {
						$overview_rows_filtered[] = $orow;
					}
				}
			}
		}

		$head_name_cache_ov = [];
		$getHeadNameOv = function ($pid) use ($db_Project, $db_person, &$head_name_cache_ov) {
			$pid = (int)$pid;
			if ($pid <= 0) {
				return '-';
			}
			if (isset($head_name_cache_ov[$pid])) {
				return $head_name_cache_ov[$pid];
			}
			$heads = function_exists('getProjectHeadList') ? getProjectHeadList($db_Project, $pid) : [];
			if (empty($heads) || empty($heads[0]['staff_project'])) {
				$head_name_cache_ov[$pid] = '-';
				return '-';
			}
			$sid = (int)$heads[0]['staff_project'];
			$sq = @mysqli_query($db_person, "SELECT fname, lname FROM staff WHERE id='" . $sid . "' LIMIT 1");
			$rr = $sq ? mysqli_fetch_assoc($sq) : null;
			$name = trim((string)(($rr['fname'] ?? '') . ' ' . ($rr['lname'] ?? '')));
			if ($name === '') {
				$name = '-';
			}
			$head_name_cache_ov[$pid] = $name;
			return $name;
		};
	?>
		<div class="po-overview-scope">
		<form id="poOverviewFilterForm" method="get" class="d-none" aria-hidden="true">
			<input type="hidden" name="page" value="project_all">
			<input type="hidden" name="project_group" value="<?= htmlspecialchars($project_group, ENT_QUOTES, 'UTF-8') ?>">
		</form>

		<div class="card acard pm-list-card mb-4 shadow-sm border-0">
			<div class="card-header bgc-white border-b-1 brc-default-l2 py-3 po-overview-toolbar">
				<div class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center justify-content-between gap-3 w-100">
					<h5 class="card-title text-primary-d2 mb-0 text-120">
						<i class="fa fa-th-large mr-2"></i> โครงการทั้งหมด
						<span class="text-secondary-d2 text-95 ml-1">ประจำปีงบประมาณ
							<span class="badge badge-pill px-3 py-1 ml-1" style="background:#f3eef8; color:#4a3263; font-weight:600; font-size:.9rem; border:1px solid #e8def2;">
								<?= htmlspecialchars((string)$years_select, ENT_QUOTES, 'UTF-8') ?>
							</span>
						</span>
						<span class="badge badge-pill bgc-default-l3 text-dark-m1 text-90 font-normal ml-2 border brc-default-m2">ดูได้อย่างเดียว</span>
					</h5>
					<div class="d-flex align-items-center flex-wrap pm-manage-toolbar justify-content-xl-end">
						<div class="po-unit-select-wrap">
							<select class="form-control form-control-sm po-unit-select"
								form="poOverviewFilterForm"
								name="unit_filter"
								onchange="document.getElementById('poOverviewFilterForm').submit();"
								title="กรองหน่วยงาน">
								<option value="">ทั้งหมด — หน่วยงาน</option>
								<?php foreach ($unit_list as $u): ?>
									<option value="<?= htmlspecialchars((string)($u['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
										<?= ($unit_filter !== '' && $unit_filter === (string)($u['code'] ?? '')) ? 'selected' : ''; ?>>
										<?= htmlspecialchars((string)($u['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="pm-year-select-wrap">
							<select class="form-control form-control-sm ace-select pm-year-select"
								form="poOverviewFilterForm"
								name="years_select"
								onchange="document.getElementById('poOverviewFilterForm').submit();">
								<?php foreach ($year_list as $year): ?>
									<option value="<?= htmlspecialchars($year['budget_year_name']); ?>"
										<?= ($years_select == $year['budget_year_name']) ? 'selected' : ''; ?>>
										<?= htmlspecialchars($year['budget_year_name']); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<input type="search" id="overviewListSearch" class="form-control form-control-sm" style="width:min(100%, 320px);" placeholder="ค้นหา: รหัส / ชื่อโครงการ / หน่วยงาน" autocomplete="off" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
						<select id="overviewPerPage" class="form-control form-control-sm" style="width: 150px;" title="จำนวนรายการต่อหน้า">
							<option value="20" selected>20 รายการ/หน้า</option>
							<option value="all">แสดงทั้งหมด</option>
						</select>
						<button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();return false;">
							<i class="fa fa-print mr-1"></i> พิมพ์
						</button>
					</div>
				</div>
			</div>
			<div class="card-body pt-3">
				<div class="mb-3">
					<div class="pm-group-switch">
						<a class="pm-group-btn <?= $project_group === 'draft' ? 'pm-active-draft' : 'pm-inactive' ?>"
							href="?page=project_all&years_select=<?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>&project_group=draft&unit_filter=<?= urlencode((string)$unit_filter) ?>&q=<?= urlencode((string)$q) ?>">
							ร่างโครงการ
						</a>
						<a class="pm-group-btn <?= $project_group === 'plan' ? 'pm-active-plan' : 'pm-inactive' ?>"
							href="?page=project_all&years_select=<?= htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8') ?>&project_group=plan&unit_filter=<?= urlencode((string)$unit_filter) ?>&q=<?= urlencode((string)$q) ?>">
							แผนงานประจำปี
						</a>
					</div>
				</div>
				<div class="table-responsive pm-table-wrap">
					<table class="table table-bordered table-hover table-striped text-95 mb-0" id="projectOverviewTable">
						<thead class="bgc-primary-l4 text-primary-d2 text-uppercase text-90">
							<tr>
								<th class="text-center border-0" style="width:52px;">#</th>
								<th class="text-center border-0" style="width:110px;">
									<div class="pm-sortable justify-content-center">เลขที่คำขอ <span class="pm-sort-icons"><button type="button" class="pm-sort-btn" data-sort-key="code" data-sort-dir="asc" title="น้อยไปมาก">▲</button><button type="button" class="pm-sort-btn" data-sort-key="code" data-sort-dir="desc" title="มากไปน้อย">▼</button></span></div>
								</th>
								<th class="text-center border-0">
									<div class="pm-sortable justify-content-center">ชื่อโครงการ <span class="pm-sort-icons"><button type="button" class="pm-sort-btn" data-sort-key="name" data-sort-dir="asc" title="น้อยไปมาก">▲</button><button type="button" class="pm-sort-btn" data-sort-key="name" data-sort-dir="desc" title="มากไปน้อย">▼</button></span></div>
								</th>
								<th class="text-center border-0" style="width:200px;">
									<div class="pm-sortable justify-content-center">หน่วย <span class="pm-sort-icons"><button type="button" class="pm-sort-btn" data-sort-key="unit" data-sort-dir="asc" title="น้อยไปมาก">▲</button><button type="button" class="pm-sort-btn" data-sort-key="unit" data-sort-dir="desc" title="มากไปน้อย">▼</button></span></div>
								</th>
								<th class="text-center border-0" style="width:106px; min-width:106px;">
									<div class="pm-sortable justify-content-center">สถานะ <span class="pm-sort-icons"><button type="button" class="pm-sort-btn" data-sort-key="status" data-sort-dir="asc" title="น้อยไปมาก">▲</button><button type="button" class="pm-sort-btn" data-sort-key="status" data-sort-dir="desc" title="มากไปน้อย">▼</button></span></div>
								</th>
								<th class="text-center border-0" style="width:170px; min-width:170px;">
									<div class="pm-sortable justify-content-center">งบประมาณ (บาท) <span class="pm-sort-icons"><button type="button" class="pm-sort-btn" data-sort-key="budget" data-sort-dir="asc" title="น้อยไปมาก">▲</button><button type="button" class="pm-sort-btn" data-sort-key="budget" data-sort-dir="desc" title="มากไปน้อย">▼</button></span></div>
								</th>
								<th class="text-center border-0" style="width:170px; min-width:170px;">
									<div class="pm-sortable justify-content-center">เบิกจ่าย (บาท) <span class="pm-sort-icons"><button type="button" class="pm-sort-btn" data-sort-key="disb" data-sort-dir="asc" title="น้อยไปมาก">▲</button><button type="button" class="pm-sort-btn" data-sort-key="disb" data-sort-dir="desc" title="มากไปน้อย">▼</button></span></div>
								</th>
								<?php if ($ov_show_pct_col): ?>
								<th class="text-center border-0" style="width:120px; min-width:120px;">
									<div class="pm-sortable justify-content-center">%การดำเนินงาน <span class="pm-sort-icons"><button type="button" class="pm-sort-btn" data-sort-key="pct" data-sort-dir="asc" title="น้อยไปมาก">▲</button><button type="button" class="pm-sort-btn" data-sort-key="pct" data-sort-dir="desc" title="มากไปน้อย">▼</button></span></div>
								</th>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody>
						<?php
						if (!empty($overview_rows_filtered)) :
							foreach ($overview_rows_filtered as $i => $row) :
								$pid_ov = (int)($row['id'] ?? 0);
								$head_name = $getHeadNameOv($pid_ov);
								$code_unit_ov = trim((string)($row['code_unit'] ?? ''));
								$unit_name = '-';
								if ($code_unit_ov !== '') {
									if (isset($unit_names[$code_unit_ov]) && trim((string)$unit_names[$code_unit_ov]) !== '') {
										$unit_name = (string)$unit_names[$code_unit_ov];
									} elseif (function_exists('planResolvePreProjectCodeUnit')) {
										$resolved_cu = planResolvePreProjectCodeUnit($db_Project, $code_unit_ov);
										if ($resolved_cu !== '' && isset($unit_names[$resolved_cu]) && trim((string)$unit_names[$resolved_cu]) !== '') {
											$unit_name = (string)$unit_names[$resolved_cu];
										}
									}
									if ($unit_name === '-' && function_exists('getUnit')) {
										$urow = getUnit($db_person, $code_unit_ov);
										if ($urow && !empty($urow['name'])) {
											$unit_name = (string)$urow['name'];
										}
									}
									// กันกรณีชื่อยังมีรูปแบบ "รหัส — ชื่อ" ค้างอยู่
									if ($unit_name !== '-' && preg_match('/^\d+\s*[—\-–]\s*(.+)$/u', $unit_name, $m)) {
										$unit_name = trim((string)$m[1]);
									}
								}
								$budget = (float)($row['budget_total'] ?? 0);
								$disb = (float)($row['disburse_total'] ?? 0);
								$st = (string)($row['status_project'] ?? '0');
								$pm_ab_ov = function_exists('pm_resolve_ab_status')
									? pm_resolve_ab_status($row, ['db' => $db_Project])
									: [
										'label' => '-',
										'mod' => 'unknown',
										'icon' => 'fa-minus-circle',
									];
								$status_txt = (string)($pm_ab_ov['label'] ?? '-');
								$status_mod = (string)($pm_ab_ov['mod'] ?? 'unknown');
								$status_icon = (string)($pm_ab_ov['icon'] ?? 'fa-minus-circle');
								$pct_success = 0;
								$notify_result_note = trim((string)($row['notify_result_note'] ?? ''));
								$pct_class = 'pm-pct-low';
								if ($pct_success >= 80) {
									$pct_class = 'pm-pct-high';
								} elseif ($pct_success >= 50) {
									$pct_class = 'pm-pct-mid';
								}
								$search_text = strtolower(trim(
									(string)($row['code_pre_project'] ?? '') . ' ' .
									(string)($row['name_project'] ?? '') . ' ' .
									(string)$unit_name
								));
						?>
							<tr data-overview-row="1"
								data-search="<?= htmlspecialchars($search_text, ENT_QUOTES, 'UTF-8') ?>"
								data-year="<?= htmlspecialchars((string)($row['year_project'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
								data-code="<?= htmlspecialchars((string)($row['code_pre_project'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
								data-name="<?= htmlspecialchars((string)($row['name_project'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
								data-unit="<?= htmlspecialchars((string)$unit_name, ENT_QUOTES, 'UTF-8') ?>"
								data-status="<?= htmlspecialchars((string)($row['status_project'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
								data-budget="<?= htmlspecialchars((string)$budget, ENT_QUOTES, 'UTF-8') ?>"
								data-disb="<?= htmlspecialchars((string)$disb, ENT_QUOTES, 'UTF-8') ?>"
								data-pct="<?= (int)$pct_success ?>">
								<td class="text-center"><?= (int)($i + 1); ?></td>
								<td class="text-nowrap"><?= htmlspecialchars((string)($row['code_pre_project'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<div class="pm-project-name"><?= htmlspecialchars((string)($row['name_project'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
									<small class="pm-project-head"><i class="fa fa-user-tie mr-1"></i><?= htmlspecialchars($head_name, ENT_QUOTES, 'UTF-8'); ?></small>
								</td>
								<td><?= htmlspecialchars($unit_name, ENT_QUOTES, 'UTF-8'); ?></td>
								<td class="text-center">
									<?= function_exists('pm_render_ab_badge')
										? pm_render_ab_badge(isset($pm_ab_ov) && is_array($pm_ab_ov) ? $pm_ab_ov : [
											'label' => $status_txt,
											'mod' => $status_mod,
										])
										: '<span class="pm-status-badge pm-status-'.htmlspecialchars($status_mod, ENT_QUOTES, 'UTF-8').'"><i class="fa '.htmlspecialchars($status_icon, ENT_QUOTES, 'UTF-8').'"></i> '.htmlspecialchars($status_txt, ENT_QUOTES, 'UTF-8').'</span>'
									?>
								</td>
								<td class="text-right text-nowrap"><?= number_format($budget, 2); ?></td>
								<td class="text-right text-nowrap"><?= number_format($disb, 2); ?></td>
								<?php if ($ov_show_pct_col): ?>
								<td class="text-center"><span class="pm-pct-pill <?= $pct_class ?>"><?= (int)$pct_success; ?>%</span></td>
								<?php endif; ?>
							</tr>
						<?php
							endforeach;
						else :
						?>
							<tr id="overview-empty-row">
								<td colspan="<?= (int)$ov_colspan ?>" class="text-center text-secondary py-4"><?= $project_group === 'plan' ? 'ไม่พบโครงการในแผนงานประจำปี' : 'ไม่พบโครงการในร่างโครงการ' ?></td>
							</tr>
						<?php endif; ?>
						<?php if (!empty($overview_rows_filtered)) : ?>
							<tr id="overview-no-match" class="d-none">
								<td colspan="<?= (int)$ov_colspan ?>" class="text-center text-muted py-3"><i class="fa fa-search mr-1"></i> ไม่พบรายการที่ค้นหา</td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>
				</div>
				<div class="d-flex justify-content-between align-items-center mt-3">
					<div class="text-muted small" id="overviewListSummary">-</div>
					<div class="d-flex align-items-center gap-1 po-overview-pager" id="overviewListPager"></div>
				</div>
			</div>
		</div>
		</div>
		<script>
		jQuery(function ($) {
			var $tableBody = $('#projectOverviewTable tbody');
			var $rows = $tableBody.find('tr[data-overview-row="1"]');
			var $noMatch = $('#overview-no-match');
			var $inp = $('#overviewListSearch');
			var $perPage = $('#overviewPerPage');
			var $pager = $('#overviewListPager');
			var $summary = $('#overviewListSummary');
			var sortKey = '';
			var sortDir = 'asc';
			var currentPage = 1;

			function getPerPage() {
				var v = String($perPage.val() || '20');
				return v === 'all' ? 0 : (parseInt(v, 10) || 20);
			}

			function getSortValue($tr, key) {
				if (key === 'budget' || key === 'disb' || key === 'pct' || key === 'status' || key === 'year') {
					return parseFloat($tr.attr('data-' + key) || '0') || 0;
				}
				return String($tr.attr('data-' + key) || '').toLowerCase();
			}

			function applySort() {
				if (!sortKey) return;
				var rowsArr = $rows.get();
				rowsArr.sort(function (a, b) {
					var $a = $(a);
					var $b = $(b);
					var va = getSortValue($a, sortKey);
					var vb = getSortValue($b, sortKey);
					if (typeof va === 'number' && typeof vb === 'number') {
						return sortDir === 'asc' ? (va - vb) : (vb - va);
					}
					if (va < vb) return sortDir === 'asc' ? -1 : 1;
					if (va > vb) return sortDir === 'asc' ? 1 : -1;
					return 0;
				});
				for (var ri = 0; ri < rowsArr.length; ri++) {
					$tableBody.append(rowsArr[ri]);
				}
				if ($noMatch.length) {
					$tableBody.append($noMatch);
				}
				$('#projectOverviewTable .pm-sort-btn').removeClass('active');
				$('#projectOverviewTable .pm-sort-btn[data-sort-key="' + sortKey + '"][data-sort-dir="' + sortDir + '"]').addClass('active');
			}

			function getFilteredRows() {
				var q = String($inp.val() || '').toLowerCase().trim();
				if (!q) return $rows;
				return $rows.filter(function () {
					var s = String($(this).attr('data-search') || '');
					return s.indexOf(q) !== -1;
				});
			}

			function renderPager(totalRows, perPage, totalPages) {
				$pager.empty();
				if (totalRows === 0 || perPage === 0 || totalPages <= 1) return;

				var start = Math.max(1, currentPage - 2);
				var end = Math.min(totalPages, start + 4);
				if (end - start < 4) start = Math.max(1, end - 4);

				var prevDisabled = currentPage <= 1 ? ' disabled' : '';
				$pager.append('<button type="button" class="btn btn-sm btn-outline-secondary' + prevDisabled + '" data-page="' + (currentPage - 1) + '">‹</button>');

				for (var p = start; p <= end; p++) {
					var active = p === currentPage ? ' active' : '';
					$pager.append('<button type="button" class="btn btn-sm btn-outline-secondary' + active + '" data-page="' + p + '">' + p + '</button>');
				}

				var nextDisabled = currentPage >= totalPages ? ' disabled' : '';
				$pager.append('<button type="button" class="btn btn-sm btn-outline-secondary' + nextDisabled + '" data-page="' + (currentPage + 1) + '">›</button>');
			}

			function applyOverviewFilter(resetPage) {
				if (resetPage) currentPage = 1;

				var filtered = getFilteredRows();
				var total = filtered.length;
				var perPage = getPerPage();
				var totalPages = perPage === 0 ? 1 : Math.max(1, Math.ceil(total / perPage));
				if (currentPage > totalPages) currentPage = totalPages;

				$rows.hide();
				if (total > 0) {
					if (perPage === 0) {
						filtered.show();
					} else {
						var start = (currentPage - 1) * perPage;
						filtered.slice(start, start + perPage).show();
					}
				}

				if ($noMatch.length) {
					var q = String($inp.val() || '').toLowerCase().trim();
					$noMatch.toggleClass('d-none', !(q !== '' && total === 0));
				}

				if (total === 0) {
					$summary.text('ไม่พบข้อมูล');
				} else if (perPage === 0) {
					$summary.text('แสดงทั้งหมด ' + total + ' รายการ');
				} else {
					var from = (currentPage - 1) * perPage + 1;
					var to = Math.min(currentPage * perPage, total);
					$summary.text('แสดง ' + from + '-' + to + ' จาก ' + total + ' รายการ');
				}

				renderPager(total, perPage, totalPages);
			}

			$inp.on('input', function () { applyOverviewFilter(true); });
			$perPage.on('change', function () { applyOverviewFilter(true); });
			$pager.on('click', 'button[data-page]', function () {
				if ($(this).prop('disabled')) return;
				var p = parseInt($(this).attr('data-page'), 10) || 1;
				if (p < 1) p = 1;
				currentPage = p;
				applyOverviewFilter(false);
			});
			$('#projectOverviewTable').on('click', '.pm-sort-btn', function () {
				sortKey = String($(this).attr('data-sort-key') || '');
				sortDir = String($(this).attr('data-sort-dir') || 'asc');
				applySort();
				applyOverviewFilter(true);
			});
			applyOverviewFilter(true);
		});
		</script>

	<?php }  //project
	?>
	<?php function project_set()
	{
		global $db_Project, $db_person, $db_pbmBudget;
		$page = $_GET['page'];
		$op_from = $_GET['op'];
		$years_select = $_GET['years_select'] ?? '';
		$project_id = $_GET['project_id'] ?? '';
		$overview = $_GET['overview'] ?? '';


		$year_list = getYearSelect($db_pbmBudget);
		$pre_project_data = getPreProjectID($db_Project, $project_id);

		$stp = (string)($pre_project_data['status_project'] ?? '');
		if (function_exists('pm_resolve_ab_status') && $stp !== '') {
			$pm_ab_ov_one = pm_resolve_ab_status($pre_project_data, ['db' => $db_Project]);
			$status_project = (string)($pm_ab_ov_one['label'] ?? 'แผนปฏิบัติการ');
			$pm_ab_ov_mod = (string)($pm_ab_ov_one['mod'] ?? '');
			if (in_array($pm_ab_ov_mod, ['a_reject', 'rejected', 'cancelled'], true)) {
				$color_status = 'danger';
			} elseif (in_array($pm_ab_ov_mod, ['a_fix', 'b_fix', 'a0', 'returned'], true)) {
				$color_status = 'warning';
			} elseif (in_array($pm_ab_ov_mod, ['b3', 'b4_done', 'b5', 'b1', 'approved'], true)) {
				$color_status = 'success';
			} else {
				$color_status = 'purple';
			}
		} elseif ($stp === '0') {
			$status_project = 'ร่าง';
			$color_status = 'warning';
		} elseif ($stp === '2') {
			$status_project = 'ส่งร่างโครงการ';
			$color_status = 'purple';
		} elseif ($stp === '3') {
			$status_project = 'รอบรรจุโครงการ';
			$color_status = 'purple';
		} elseif ($stp === '4') {
			$status_project = 'นำเข้าที่ประชุม';
			$color_status = 'success';
		} elseif ($stp === '1') {
			$status_project = 'อนุมัติ';
			$color_status = 'success';
		} elseif ($stp === '8') {
			$status_project = 'ตีกลับให้แก้ไข';
			$color_status = 'warning';
		} elseif ($stp === '9') {
			$status_project = 'ไม่อนุมัติ';
			$color_status = 'danger';
		} else {
			// legacy: ถ้าไม่มี status_project (ค่าเป็นค่าว่าง) ให้ถือว่าเป็นขั้นแผน
			$status_project = 'แผนปฏิบัติการ';
			$color_status = 'success';
		}

	?>
		<h2><small>ปีงบประมาณ :</small> <?= $years_select; ?> </h2>


		<div class="d-flex justify-content-end mb-3">
			<a class="d-style btn btn-warning btn-bgc-tp shadow-sm text-600 letter-spacing px-4 mb-1" href="?page=<?= $page; ?>&op=project&years_select=<?= $years_select; ?>&overview=<?= $overview; ?>" role="button">

				<i class="fa fa-arrow-left ml-2 f-n-hover"></i>
				ย้อนกลับ
			</a>
		</div>
		<h4 class="text-center"><small>โครงการ :</small> <?= htmlspecialchars($pre_project_data['name_project']); ?></h4>
		<p class="text-primary text-center">ปีงบประมาณ <?= htmlspecialchars($pre_project_data['year_project']); ?> || รหัสอ้างอิง : <?= htmlspecialchars($pre_project_data['code_pre_project']); ?>
			<span class="m-1 badge bgc-<?= $color_status; ?> radius-2 text-dark px-3 text-110">
				<?= $status_project; ?>
			</span>
		</p>
		<div class="row col-12">
			<!-- SmartWizard html -->
			<?php include("progress_bar.php"); ?>
		</div>
		<div class="page-content container-fluid p-0 m-0">
			<div class="row no-gutters">

				<div class="col-12">

					<div class="card border-0 ">
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$head_list = getProjectHeadList($db_Project, $project_id);
								if (!empty($head_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit text-dark-l3 mr-1" style="color: #f26cb1ff;"></i>
								<?php } ?>
								หัวหน้าโครงการ
								<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-head-project<?= $project_id; ?>">
									<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
								</button>
							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">
							<?php

							if (!empty($head_list)) :
								foreach ($head_list as $i => $head) :
									$staff = getStaffID($db_person, $head['staff_project']);
									$prefix = getPrefix($db_person, $staff['prefix_code']);

							?>

									<p class="mb-1">
										<span class="fw-bold "><?= $i + 1; ?>.</span>
										<?= htmlspecialchars($prefix['name']); ?>
										<?= htmlspecialchars($staff['fname']); ?>&nbsp;&nbsp;
										<?= htmlspecialchars($staff['lname']); ?>
										<span class="text-muted">(<?= htmlspecialchars($head['phone']); ?>)</span>

										<!-- 🔹 ปุ่มแก้ไข -->
										<button type="button"
											class="btn btn-link p-0 icon-btn edit"
											title="แก้ไขข้อมูล"
											data-target="#modal-edit-head<?= $head['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-edit fa-lg"></i>
										</button>

										<!-- 🔹 ปุ่มลบ -->
										<button type="button"
											class="btn btn-link p-0 ml-2 icon-btn delete"
											title="ลบข้อมูล"
											data-target="#modal-del-head<?= $head['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-trash fa-lg"></i>
										</button>



									</p>

									<!-- with white backdrop -->
									<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-head<?= $head['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
										<div class="modal-dialog " role="document">
											<div class="modal-content bgc-transparent brc-danger-m2 shadow">
												<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
													<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
														WARNING!
													</h5>

													<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true" class="text-150">&times;</span>
													</button>
												</div>


												<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
													<div class="d-flex align-items-top mr-2 mr-md-5">
														<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
														<div class="text-secondary-d2 text-105">
															<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

														</div>
													</div>
												</div>

												<div class="modal-footer bgc-white-tp2 border-0">
													<form action="?page=<?= $page; ?>&op=save_data_person" method="POST">
														<input type="hidden" name="op_from" value="<?= $op_from; ?>">
														<input type="hidden" name="years_select" value="<?= $years_select; ?>">
														<input type="hidden" name="project_id" value="<?= $project_id; ?>">
														<input type="hidden" name="staff_id" value="<?= $head['id']; ?>">
														<input type="hidden" name="overview" value="<?= $overview; ?>">
														<input type="hidden" name="tb_data" value="project_head">
														<input type="hidden" name="type" value="delete">
														<button class="btn btn-danger" type="submit">ยืนยัน </button>

													</form>
												</div>
											</div>
										</div>
									</div>
									<!-- แก้ไข -->
									<div class="modal fade modal-lg" id="modal-edit-head<?= $head['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

										<div class="modal-dialog " role="document">
											<div class="modal-content">

												<div class="modal-header">
													<h5 class="modal-title text-blue" id="exampleModalLabel2">
														<i class="fas fa-plus text-secondary"></i> แก้ไขหัวหน้าโครงการ
													</h5>

													<button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>

												</div>

												<div class="modal-body bg-white">
													<div class="bgc-white radius-1">
														<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_person" method="POST">

															<div class="row col-12 mt-2 mt-md-4">
																<!-- the left side profile picture and other info -->
																<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
																<div class="row col-md-12 mt-3">
																	<div class=col-md-3>
																		<i class="fas fa-layer-group text-blue"></i>
																		ชื่อ-นามสกุล
																	</div>
																	<div class=col-md-6>
																		<?php
																		$staff_list = getStaffSelect($db_person);

																		$id_selected = $head['staff_project']  ?? null; // รับค่า id ที่ส่งเข้ามา

																		// สร้าง select
																		echo '<select name="staff_project" id="staff_project" class="form-control">';

																		foreach ($staff_list as $staff) {
																			$selected = ($id_selected == $staff['code']) ? 'selected' : '';
																			echo '<option value="' . htmlspecialchars($staff['code']) . '" ' . $selected . '>'
																				. htmlspecialchars($staff['fname']) . ' ' . htmlspecialchars($staff['lname'])
																				. '</option>';
																		}

																		echo '</select>';
																		?>

																	</div>
																	<div class=col-md-3>
																		<input type="text" class="form-control" name="phone" placeholder="เบอร์โทรศัพท์" value="<?= htmlspecialchars($head['phone']) ?>" required />
																	</div>
																</div>

															</div>
															<input type="hidden" name="op_from" value="<?= $op_from; ?>">
															<input type="hidden" name="years_select" value="<?= $years_select; ?>">
															<input type="hidden" name="project_id" value="<?= $project_id; ?>">
															<input type="hidden" name="staff_id" value="<?= $head['id']; ?>">
															<input type="hidden" name="overview" value="<?= $overview; ?>">
															<input type="hidden" name="tb_data" value="project_head">
															<input type="hidden" name="type" value="update">

															<div class="offset-md-4 col-md-8 text-nowrap mt-5">
																<button class="btn btn-info btn-bold px-4" type="submit">
																	<i class="fa fa-check mr-1"></i>
																	บันทึกข้อมูล
																</button>
																<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
																	<i class="fa fa-undo mr-1"></i>
																	ค่าเริ่มต้น
																</button>
															</div>
														</form>
													</div>
												</div>
												<div class="modal-footer mt-3">
													<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
														<i class="ace-icon fa fa-times"></i>
														Close
													</button>
												</div>
											</div><!-- /.modal-content -->
										</div><!-- /.modal-dialog -->
									</div>
									<!-- จบ เพิ่ม -->
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
								</tr>
							<?php endif; ?>
						</div>
						<!-- เพิ่ม -->
						<div class="modal fade modal-lg" id="modal-add-head-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-plus text-secondary"></i> เพิ่มหัวหน้าโครงการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_person" method="POST">

												<div class="row col-12 mt-2 mt-md-4">
													<!-- the left side profile picture and other info -->
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
													<div class="row col-md-12 mt-3">
														<div class=col-md-3>
															<i class="fas fa-layer-group text-blue"></i>
															ชื่อ-นามสกุล
														</div>
														<div class=col-md-6>
															<?php
															$staff_list = getStaffSelect($db_person);
															$id_selected = $kpi['staff_project']  ?? null; // รับค่า id ที่ส่งเข้ามา
															// สร้าง select
															echo '<select name="staff_project" id="staff_project" class="form-control">';

															foreach ($staff_list as $staff) {
																$selected = ($id_selected == $staff['code']) ? 'selected' : '';
																echo '<option value="' . htmlspecialchars($staff['code']) . '" ' . $selected . '>'
																	. htmlspecialchars($staff['fname']) . ' ' . htmlspecialchars($staff['lname'])
																	. '</option>';
															}
															echo '</select>';
															?>
														</div>
														<div class=col-md-3>
															<input type="text" class="form-control" name="phone" placeholder="เบอร์โทรศัพท์" required />
														</div>
													</div>
												</div>
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="project_head">
												<input type="hidden" name="type" value="insert">

												<div class="offset-md-4 col-md-8 text-nowrap mt-5">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i>
														บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i>
														ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>



									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i>
											Close
										</button>


									</div>

								</div><!-- /.modal-content -->
							</div><!-- /.modal-dialog -->
						</div>
						<!-- จบ เพิ่ม -->
						<!-- ผู้ประสานงาน -->
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$staff_list = getProjectStaffList($db_Project, $project_id);
								if (!empty($staff_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit text-dark-l3 mr-1" style="color: #f26cb1ff;"></i>
								<?php } ?>
								ผู้ประสานโครงการ
								<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-staff-project<?= $project_id; ?>">
									<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
								</button>
							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">
							<?php

							if (!empty($staff_list)) :
								foreach ($staff_list as $i => $staff_pro) :
									$staff = getStaffID($db_person, $staff_pro['staff_project']);
									$prefix = getPrefix($db_person, $staff['prefix_code']);

							?>

									<p class="mb-1">
										<span class="fw-bold "><?= $i + 1; ?>.</span>
										<?= htmlspecialchars($prefix['name']); ?>
										<?= htmlspecialchars($staff['fname']); ?>&nbsp;&nbsp;
										<?= htmlspecialchars($staff['lname']); ?>
										<span class="text-muted">(<?= htmlspecialchars($staff_pro['phone']); ?>)</span>

										<!-- 🔹 ปุ่มแก้ไข -->
										<button type="button"
											class="btn btn-link p-0 icon-btn edit"
											title="แก้ไขข้อมูล"
											data-target="#modal-edit-staff<?= $staff_pro['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-edit fa-lg"></i>
										</button>

										<!-- 🔹 ปุ่มลบ -->
										<button type="button"
											class="btn btn-link p-0 ml-2 icon-btn delete"
											title="ลบข้อมูล"
											data-target="#modal-del-staff<?= $staff_pro['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-trash fa-lg"></i>
										</button>



									</p>

									<!-- with white backdrop -->
									<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-staff<?= $staff_pro['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
										<div class="modal-dialog " role="document">
											<div class="modal-content bgc-transparent brc-danger-m2 shadow">
												<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
													<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
														WARNING!
													</h5>

													<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true" class="text-150">&times;</span>
													</button>
												</div>


												<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
													<div class="d-flex align-items-top mr-2 mr-md-5">
														<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
														<div class="text-secondary-d2 text-105">
															<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

														</div>
													</div>
												</div>

												<div class="modal-footer bgc-white-tp2 border-0">
													<form action="?page=<?= $page; ?>&op=save_data_person" method="POST">
														<input type="hidden" name="op_from" value="<?= $op_from; ?>">
														<input type="hidden" name="years_select" value="<?= $years_select; ?>">
														<input type="hidden" name="project_id" value="<?= $project_id; ?>">
														<input type="hidden" name="staff_id" value="<?= $staff_pro['id']; ?>">
														<input type="hidden" name="overview" value="<?= $overview; ?>">
														<input type="hidden" name="tb_data" value="project_staff">
														<input type="hidden" name="type" value="delete">
														<button class="btn btn-danger" type="submit">ยืนยัน </button>

													</form>
												</div>
											</div>
										</div>
									</div>
									<!-- แก้ไข -->
									<div class="modal fade modal-lg" id="modal-edit-staff<?= $staff_pro['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

										<div class="modal-dialog " role="document">
											<div class="modal-content">

												<div class="modal-header">
													<h5 class="modal-title text-blue" id="exampleModalLabel2">
														<i class="fas fa-plus text-secondary"></i> แก้ไขผู้ประสานงานโครงการ
													</h5>

													<button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>

												</div>

												<div class="modal-body bg-white">
													<div class="bgc-white radius-1">
														<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_person" method="POST">

															<div class="row col-12 mt-2 mt-md-4">
																<!-- the left side profile picture and other info -->
																<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
																<div class="row col-md-12 mt-3">
																	<div class=col-md-3>
																		<i class="fas fa-layer-group text-blue"></i>
																		ชื่อ-นามสกุล
																	</div>
																	<div class=col-md-6>
																		<?php
																		$staff_pro_list = getStaffSelect($db_person);

																		$id_selected = $staff_pro['staff_project']  ?? null; // รับค่า id ที่ส่งเข้ามา

																		// สร้าง select
																		echo '<select name="staff_project" id="staff_project" class="form-control">';

																		foreach ($staff_pro_list as $staff_staff) {
																			$selected = ($id_selected == $staff_staff['code']) ? 'selected' : '';
																			echo '<option value="' . htmlspecialchars($staff_staff['code']) . '" ' . $selected . '>'
																				. htmlspecialchars($staff_staff['fname']) . ' ' . htmlspecialchars($staff_staff['lname'])
																				. '</option>';
																		}

																		echo '</select>';
																		?>

																	</div>
																	<div class=col-md-3>
																		<input type="text" class="form-control" name="phone" placeholder="เบอร์โทรศัพท์" value="<?= htmlspecialchars($staff_pro['phone']) ?>" required />
																	</div>
																</div>

															</div>
															<input type="hidden" name="op_from" value="<?= $op_from; ?>">
															<input type="hidden" name="years_select" value="<?= $years_select; ?>">
															<input type="hidden" name="project_id" value="<?= $project_id; ?>">
															<input type="hidden" name="staff_id" value="<?= $staff_pro['id']; ?>">
															<input type="hidden" name="overview" value="<?= $overview; ?>">
															<input type="hidden" name="tb_data" value="project_staff">
															<input type="hidden" name="type" value="update">

															<div class="offset-md-4 col-md-8 text-nowrap mt-5">
																<button class="btn btn-info btn-bold px-4" type="submit">
																	<i class="fa fa-check mr-1"></i>
																	บันทึกข้อมูล
																</button>
																<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
																	<i class="fa fa-undo mr-1"></i>
																	ค่าเริ่มต้น
																</button>
															</div>
														</form>
													</div>
												</div>
												<div class="modal-footer mt-3">
													<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
														<i class="ace-icon fa fa-times"></i>
														Close
													</button>
												</div>
											</div><!-- /.modal-content -->
										</div><!-- /.modal-dialog -->
									</div>
									<!-- จบ เพิ่ม -->
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
								</tr>
							<?php endif; ?>
						</div>
						<!-- เพิ่ม -->
						<div class="modal fade modal-lg" id="modal-add-staff-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-plus text-secondary"></i> เพิ่มผู้ประสานงานโครงการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_person" method="POST">

												<div class="row col-12 mt-2 mt-md-4">
													<!-- the left side profile picture and other info -->
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
													<div class="row col-md-12 mt-3">
														<div class=col-md-3>
															<i class="fas fa-layer-group text-blue"></i>
															ชื่อ-นามสกุล
														</div>
														<div class=col-md-6>
															<?php
															$staff_list = getStaffSelect($db_person);

															// สร้าง select
															echo '<select name="staff_project" id="staff_project" class="form-control">';

															foreach ($staff_list as $staff) {

																echo '<option value="' . htmlspecialchars($staff['code']) . '" ' . '>'
																	. htmlspecialchars($staff['fname']) . ' ' . htmlspecialchars($staff['lname'])
																	. '</option>';
															}
															echo '</select>';
															?>
														</div>
														<div class=col-md-3>
															<input type="text" class="form-control" name="phone" placeholder="เบอร์โทรศัพท์" required />
														</div>
													</div>
												</div>
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="project_staff">
												<input type="hidden" name="type" value="insert">

												<div class="offset-md-4 col-md-8 text-nowrap mt-5">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i>
														บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i>
														ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>



									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i>
											Close
										</button>


									</div>

								</div><!-- /.modal-content -->
							</div><!-- /.modal-dialog -->
						</div>
						<!-- จบ เพิ่ม -->

						<!-- card -->
						<!-- ผู้ร่วมโครงการ -->
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$staff_list = getProjectPersonList($db_Project, $project_id);
								if (!empty($staff_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit text-dark-l3 mr-1" style="color: #f26cb1ff;"></i>
								<?php } ?>
								ผู้ร่วมโครงการ (ถ้ามี)
								<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-person-project<?= $project_id; ?>">
									<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
								</button>
							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">
							<?php

							if (!empty($staff_list)) :
								foreach ($staff_list as $i => $staff_pro) :
									$staff = getStaffID($db_person, $staff_pro['staff_project']);
									$prefix = getPrefix($db_person, $staff['prefix_code']);

							?>

									<p class="mb-1">
										<span class="fw-bold "><?= $i + 1; ?>.</span>
										<?= htmlspecialchars($prefix['name']); ?>
										<?= htmlspecialchars($staff['fname']); ?>&nbsp;&nbsp;
										<?= htmlspecialchars($staff['lname']); ?>
										<span class="text-muted">(<?= htmlspecialchars($staff_pro['phone']); ?>)</span>

										<!-- 🔹 ปุ่มแก้ไข -->
										<button type="button"
											class="btn btn-link p-0 icon-btn edit"
											title="แก้ไขข้อมูล"
											data-target="#modal-edit-person<?= $staff_pro['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-edit fa-lg"></i>
										</button>

										<!-- 🔹 ปุ่มลบ -->
										<button type="button"
											class="btn btn-link p-0 ml-2 icon-btn delete"
											title="ลบข้อมูล"
											data-target="#modal-del-person<?= $staff_pro['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-trash fa-lg"></i>
										</button>



									</p>

									<!-- with white backdrop -->
									<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-person<?= $staff_pro['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
										<div class="modal-dialog " role="document">
											<div class="modal-content bgc-transparent brc-danger-m2 shadow">
												<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
													<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
														WARNING!
													</h5>

													<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true" class="text-150">&times;</span>
													</button>
												</div>


												<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
													<div class="d-flex align-items-top mr-2 mr-md-5">
														<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
														<div class="text-secondary-d2 text-105">
															<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

														</div>
													</div>
												</div>

												<div class="modal-footer bgc-white-tp2 border-0">
													<form action="?page=<?= $page; ?>&op=save_data_person" method="POST">
														<input type="hidden" name="op_from" value="<?= $op_from; ?>">
														<input type="hidden" name="years_select" value="<?= $years_select; ?>">
														<input type="hidden" name="project_id" value="<?= $project_id; ?>">
														<input type="hidden" name="staff_id" value="<?= $staff_pro['id']; ?>">
														<input type="hidden" name="overview" value="<?= $overview; ?>">
														<input type="hidden" name="tb_data" value="project_person">
														<input type="hidden" name="type" value="delete">
														<button class="btn btn-danger" type="submit">ยืนยัน </button>

													</form>
												</div>
											</div>
										</div>
									</div>
									<!-- แก้ไข -->
									<div class="modal fade modal-lg" id="modal-edit-person<?= $staff_pro['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

										<div class="modal-dialog " role="document">
											<div class="modal-content">

												<div class="modal-header">
													<h5 class="modal-title text-blue" id="exampleModalLabel2">
														<i class="fas fa-plus text-secondary"></i> แก้ไขผู้ร่วมโครงการ
													</h5>

													<button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>

												</div>

												<div class="modal-body bg-white">
													<div class="bgc-white radius-1">
														<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_person" method="POST">

															<div class="row col-12 mt-2 mt-md-4">
																<!-- the left side profile picture and other info -->
																<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
																<div class="row col-md-12 mt-3">
																	<div class=col-md-3>
																		<i class="fas fa-layer-group text-blue"></i>
																		ชื่อ-นามสกุล
																	</div>
																	<div class=col-md-6>
																		<?php
																		$staff_pro_list = getStaffSelect($db_person);

																		$id_selected = $staff_pro['staff_project']  ?? null; // รับค่า id ที่ส่งเข้ามา

																		// สร้าง select
																		echo '<select name="staff_project" id="staff_project" class="form-control">';

																		foreach ($staff_pro_list as $staff_staff) {
																			$selected = ($id_selected == $staff_staff['code']) ? 'selected' : '';
																			echo '<option value="' . htmlspecialchars($staff_staff['code']) . '" ' . $selected . '>'
																				. htmlspecialchars($staff_staff['fname']) . ' ' . htmlspecialchars($staff_staff['lname'])
																				. '</option>';
																		}

																		echo '</select>';
																		?>

																	</div>
																	<div class=col-md-3>
																		<input type="text" class="form-control" name="phone" placeholder="เบอร์โทรศัพท์" value="<?= htmlspecialchars($staff_pro['phone']) ?>" required />
																	</div>
																</div>

															</div>
															<input type="hidden" name="op_from" value="<?= $op_from; ?>">
															<input type="hidden" name="years_select" value="<?= $years_select; ?>">
															<input type="hidden" name="project_id" value="<?= $project_id; ?>">
															<input type="hidden" name="staff_id" value="<?= $staff_pro['id']; ?>">
															<input type="hidden" name="overview" value="<?= $overview; ?>">
															<input type="hidden" name="tb_data" value="project_person">
															<input type="hidden" name="type" value="update">

															<div class="offset-md-4 col-md-8 text-nowrap mt-5">
																<button class="btn btn-info btn-bold px-4" type="submit">
																	<i class="fa fa-check mr-1"></i>
																	บันทึกข้อมูล
																</button>
																<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
																	<i class="fa fa-undo mr-1"></i>
																	ค่าเริ่มต้น
																</button>
															</div>
														</form>
													</div>
												</div>
												<div class="modal-footer mt-3">
													<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
														<i class="ace-icon fa fa-times"></i>
														Close
													</button>
												</div>
											</div><!-- /.modal-content -->
										</div><!-- /.modal-dialog -->
									</div>
									<!-- จบ เพิ่ม -->
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
								</tr>
							<?php endif; ?>
						</div>
						<!-- เพิ่ม -->
						<div class="modal fade modal-lg" id="modal-add-person-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-plus text-secondary"></i> เพิ่มผู้ร่วมโครงการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_person" method="POST">

												<div class="row col-12 mt-2 mt-md-4">
													<!-- the left side profile picture and other info -->
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
													<div class="row col-md-12 mt-3">
														<div class=col-md-3>
															<i class="fas fa-layer-group text-blue"></i>
															ชื่อ-นามสกุล
														</div>
														<div class=col-md-6>
															<?php
															$staff_list = getStaffSelect($db_person);

															// สร้าง select
															echo '<select name="staff_project" id="staff_project" class="form-control">';

															foreach ($staff_list as $staff) {

																echo '<option value="' . htmlspecialchars($staff['code']) . '" ' . '>'
																	. htmlspecialchars($staff['fname']) . ' ' . htmlspecialchars($staff['lname'])
																	. '</option>';
															}
															echo '</select>';
															?>
														</div>
														<div class=col-md-3>
															<input type="text" class="form-control" name="phone" placeholder="เบอร์โทรศัพท์" required />
														</div>
													</div>
												</div>
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="project_person">
												<input type="hidden" name="type" value="insert">

												<div class="offset-md-4 col-md-8 text-nowrap mt-5">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i>
														บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i>
														ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>



									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i>
											Close
										</button>


									</div>

								</div><!-- /.modal-content -->
							</div><!-- /.modal-dialog -->
						</div>
						<!-- จบ เพิ่ม -->

						<!-- card -->
						<!-- หน่วยงานหลัก -->
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$department_list = getProjectDepartmentList($db_Project, $project_id, 1);
								if (!empty($department_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit text-dark-l3 mr-1" style="color: #f26cb1ff;"></i>
								<?php } ?>
								หน่วยงานหลัก
								<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-department-project<?= $project_id; ?>">
									<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
								</button>
							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">
							<?php

							if (!empty($department_list)) :
								foreach ($department_list as $i => $department) :
									$unit = getSetOwner($db_KPI, $department['department_project']);


							?>

									<p class="mb-1">

										<?= htmlspecialchars($unit['name']); ?>


										<!-- 🔹 ปุ่มแก้ไข -->
										<button type="button"
											class="btn btn-link p-0 icon-btn edit"
											title="แก้ไขข้อมูล"
											data-target="#modal-edit-department<?= $department['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-edit fa-lg"></i>
										</button>

										<!-- 🔹 ปุ่มลบ -->
										<button type="button"
											class="btn btn-link p-0 ml-2 icon-btn delete"
											title="ลบข้อมูล"
											data-target="#modal-del-department<?= $department['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-trash fa-lg"></i>
										</button>



									</p>

									<!-- with white backdrop -->
									<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-department<?= $department['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
										<div class="modal-dialog " role="document">
											<div class="modal-content bgc-transparent brc-danger-m2 shadow">
												<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
													<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
														WARNING!
													</h5>

													<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true" class="text-150">&times;</span>
													</button>
												</div>


												<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
													<div class="d-flex align-items-top mr-2 mr-md-5">
														<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
														<div class="text-secondary-d2 text-105">
															<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

														</div>
													</div>
												</div>

												<div class="modal-footer bgc-white-tp2 border-0">
													<form action="?page=<?= $page; ?>&op=save_data_department" method="POST">
														<input type="hidden" name="op_from" value="<?= $op_from; ?>">
														<input type="hidden" name="years_select" value="<?= $years_select; ?>">
														<input type="hidden" name="project_id" value="<?= $project_id; ?>">
														<input type="hidden" name="department_id" value="<?= $department['id']; ?>">
														<input type="hidden" name="overview" value="<?= $overview; ?>">
														<input type="hidden" name="tb_data" value="project_department">
														<input type="hidden" name="type" value="delete">
														<button class="btn btn-danger" type="submit">ยืนยัน </button>

													</form>
												</div>
											</div>
										</div>
									</div>
									<!-- แก้ไข -->
									<div class="modal fade modal-lg" id="modal-edit-department<?= $department['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

										<div class="modal-dialog " role="document">
											<div class="modal-content">

												<div class="modal-header">
													<h5 class="modal-title text-blue" id="exampleModalLabel2">
														<i class="fas fa-plus text-secondary"></i> แก้ไขหน่วยงานหลัก
													</h5>

													<button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>

												</div>

												<div class="modal-body bg-white">
													<div class="bgc-white radius-1">
														<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_department" method="POST">

															<div class="row col-12 mt-2 mt-md-4">
																<!-- the left side profile picture and other info -->
																<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
																<div class="row col-md-12 mt-3">
																	<div class=col-md-3>
																		<i class="fas fa-layer-group text-blue"></i>
																		หน่วยงาน
																	</div>
																	<div class=col-md-9>
																		<?php
																		$department_list = getOwnerList($db_KPI, 1);

																		$id_selected = $department['department_project']  ?? null; // รับค่า id ที่ส่งเข้ามา
																		// echo $department_select['id'];
																		// echo $department['department_project'];
																		// สร้าง select
																		echo '<select name="department_project" id="department_project" class="form-control">';

																		foreach ($department_list as $department_select) {
																			//echo $department_select['id'];
																			$selected = ($id_selected == $department_select['id']) ? 'selected' : '';
																			echo '<option value="' . htmlspecialchars($department_select['id']) . '" ' . $selected . '>'
																				. htmlspecialchars($department_select['name'])
																				. '</option>';
																		}

																		echo '</select>';
																		?>

																	</div>

																</div>

															</div>
															<input type="hidden" name="op_from" value="<?= $op_from; ?>">
															<input type="hidden" name="years_select" value="<?= $years_select; ?>">
															<input type="hidden" name="project_id" value="<?= $project_id; ?>">
															<input type="hidden" name="department_id" value="<?= $department['id']; ?>">
															<input type="hidden" name="overview" value="<?= $overview; ?>">
															<input type="hidden" name="tb_data" value="project_department">
															<input type="hidden" name="level" value="1">
															<input type="hidden" name="type" value="update">

															<div class="offset-md-4 col-md-8 text-nowrap mt-5">
																<button class="btn btn-info btn-bold px-4" type="submit">
																	<i class="fa fa-check mr-1"></i>
																	บันทึกข้อมูล
																</button>
																<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
																	<i class="fa fa-undo mr-1"></i>
																	ค่าเริ่มต้น
																</button>
															</div>
														</form>
													</div>
												</div>
												<div class="modal-footer mt-3">
													<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
														<i class="ace-icon fa fa-times"></i>
														Close
													</button>
												</div>
											</div><!-- /.modal-content -->
										</div><!-- /.modal-dialog -->
									</div>
									<!-- จบ เพิ่ม -->
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
								</tr>
							<?php endif; ?>
						</div>
						<!-- เพิ่ม -->
						<div class="modal fade modal-lg" id="modal-add-department-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-plus text-secondary"></i> เพิ่มหน่วยงานหลัก
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_department" method="POST">

												<div class="row col-12 mt-2 mt-md-4">
													<!-- the left side profile picture and other info -->
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
													<div class="row col-md-12 mt-3">
														<div class=col-md-3>
															<i class="fas fa-layer-group text-blue"></i>
															หน่วยงาน
														</div>
														<div class=col-md-9>
															<?php
															// 1= หน่วยงาน
															$department_list = getOwnerList($db_KPI, 1);



															// สร้าง select
															echo '<select name="department_project" id="department_project" class="form-control">';

															foreach ($department_list as $department) {
																$selected = ($id_selected == $department['id']) ? 'selected' : '';
																echo '<option value="' . htmlspecialchars($department['id']) . '" ' . $selected . '>'
																	. htmlspecialchars($department['name'])
																	. '</option>';
															}

															echo '</select>';
															?>
														</div>

													</div>
												</div>
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="project_department">
												<input type="hidden" name="level" value="1">
												<input type="hidden" name="type" value="insert">

												<div class="offset-md-4 col-md-8 text-nowrap mt-5">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i>
														บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i>
														ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>



									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i>
											Close
										</button>


									</div>

								</div><!-- /.modal-content -->
							</div><!-- /.modal-dialog -->
						</div>
						<!-- จบ เพิ่ม -->

						<!-- card -->
						<!-- หน่วยงานร่วม -->
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$department_list = getProjectDepartmentList($db_Project, $project_id, 2);
								if (!empty($department_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit text-dark-l3 mr-1" style="color: #f26cb1ff;"></i>
								<?php } ?>
								หน่วยงานร่วม (ถ้ามี)
								<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-department2-project<?= $project_id; ?>">
									<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
								</button>
							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">
							<?php

							if (!empty($department_list)) :
								foreach ($department_list as $i => $department) :
									$unit = getSetOwner($db_KPI, $department['department_project']);


							?>

									<p class="mb-1">

										<?= htmlspecialchars($unit['name']); ?>


										<!-- 🔹 ปุ่มแก้ไข -->
										<button type="button"
											class="btn btn-link p-0 icon-btn edit"
											title="แก้ไขข้อมูล"
											data-target="#modal-edit-department2<?= $department['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-edit fa-lg"></i>
										</button>

										<!-- 🔹 ปุ่มลบ -->
										<button type="button"
											class="btn btn-link p-0 ml-2 icon-btn delete"
											title="ลบข้อมูล"
											data-target="#modal-del-department2<?= $department['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-trash fa-lg"></i>
										</button>



									</p>

									<!-- with white backdrop -->
									<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-department2<?= $department['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
										<div class="modal-dialog " role="document">
											<div class="modal-content bgc-transparent brc-danger-m2 shadow">
												<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
													<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
														WARNING!
													</h5>

													<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true" class="text-150">&times;</span>
													</button>
												</div>


												<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
													<div class="d-flex align-items-top mr-2 mr-md-5">
														<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
														<div class="text-secondary-d2 text-105">
															<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

														</div>
													</div>
												</div>

												<div class="modal-footer bgc-white-tp2 border-0">
													<form action="?page=<?= $page; ?>&op=save_data_department" method="POST">
														<input type="hidden" name="op_from" value="<?= $op_from; ?>">
														<input type="hidden" name="years_select" value="<?= $years_select; ?>">
														<input type="hidden" name="project_id" value="<?= $project_id; ?>">
														<input type="hidden" name="department_id" value="<?= $department['id']; ?>">
														<input type="hidden" name="overview" value="<?= $overview; ?>">
														<input type="hidden" name="tb_data" value="project_department">
														<input type="hidden" name="type" value="delete">
														<button class="btn btn-danger" type="submit">ยืนยัน </button>

													</form>
												</div>
											</div>
										</div>
									</div>
									<!-- แก้ไข -->
									<div class="modal fade modal-lg" id="modal-edit-department2<?= $department['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

										<div class="modal-dialog " role="document">
											<div class="modal-content">

												<div class="modal-header">
													<h5 class="modal-title text-blue" id="exampleModalLabel2">
														<i class="fas fa-plus text-secondary"></i> แก้ไขหน่วยงานร่วม
													</h5>

													<button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>

												</div>

												<div class="modal-body bg-white">
													<div class="bgc-white radius-1">
														<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_department" method="POST">

															<div class="row col-12 mt-2 mt-md-4">
																<!-- the left side profile picture and other info -->
																<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
																<div class="row col-md-12 mt-3">
																	<div class=col-md-3>
																		<i class="fas fa-layer-group text-blue"></i>
																		หน่วยงาน
																	</div>
																	<div class=col-md-9>
																		<?php
																		$department_list = getOwnerList($db_KPI, 1);

																		$id_selected = $department['department_project']  ?? null; // รับค่า id ที่ส่งเข้ามา
																		// echo $department_select['id'];
																		// echo $department['department_project'];
																		// สร้าง select
																		echo '<select name="department_project" id="department_project" class="form-control">';

																		foreach ($department_list as $department_select) {
																			//echo $department_select['id'];
																			$selected = ($id_selected == $department_select['id']) ? 'selected' : '';
																			echo '<option value="' . htmlspecialchars($department_select['id']) . '" ' . $selected . '>'
																				. htmlspecialchars($department_select['name'])
																				. '</option>';
																		}

																		echo '</select>';
																		?>

																	</div>

																</div>

															</div>
															<input type="hidden" name="op_from" value="<?= $op_from; ?>">
															<input type="hidden" name="years_select" value="<?= $years_select; ?>">
															<input type="hidden" name="project_id" value="<?= $project_id; ?>">
															<input type="hidden" name="department_id" value="<?= $department['id']; ?>">
															<input type="hidden" name="overview" value="<?= $overview; ?>">
															<input type="hidden" name="tb_data" value="project_department">
															<input type="hidden" name="level" value="2">
															<input type="hidden" name="type" value="update">

															<div class="offset-md-4 col-md-8 text-nowrap mt-5">
																<button class="btn btn-info btn-bold px-4" type="submit">
																	<i class="fa fa-check mr-1"></i>
																	บันทึกข้อมูล
																</button>
																<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
																	<i class="fa fa-undo mr-1"></i>
																	ค่าเริ่มต้น
																</button>
															</div>
														</form>
													</div>
												</div>
												<div class="modal-footer mt-3">
													<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
														<i class="ace-icon fa fa-times"></i>
														Close
													</button>
												</div>
											</div><!-- /.modal-content -->
										</div><!-- /.modal-dialog -->
									</div>
									<!-- จบ เพิ่ม -->
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
								</tr>
							<?php endif; ?>
						</div>
						<!-- เพิ่ม -->
						<div class="modal fade modal-lg" id="modal-add-department2-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-plus text-secondary"></i> เพิ่มหน่วยงานร่วม
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_department" method="POST">

												<div class="row col-12 mt-2 mt-md-4">
													<!-- the left side profile picture and other info -->
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
													<div class="row col-md-12 mt-3">
														<div class=col-md-3>
															<i class="fas fa-layer-group text-blue"></i>
															หน่วยงาน
														</div>
														<div class=col-md-9>
															<?php
															// 1= หน่วยงาน
															$department_list = getOwnerList($db_KPI, 1);



															// สร้าง select
															echo '<select name="department_project" id="department_project" class="form-control">';

															foreach ($department_list as $department) {
																$selected = ($id_selected == $department['id']) ? 'selected' : '';
																echo '<option value="' . htmlspecialchars($department['id']) . '" ' . $selected . '>'
																	. htmlspecialchars($department['name'])
																	. '</option>';
															}

															echo '</select>';
															?>
														</div>

													</div>
												</div>
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="project_department">
												<input type="hidden" name="level" value="2">
												<input type="hidden" name="type" value="insert">

												<div class="offset-md-4 col-md-8 text-nowrap mt-5">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i>
														บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i>
														ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>



									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i>
											Close
										</button>


									</div>

								</div><!-- /.modal-content -->
							</div><!-- /.modal-dialog -->
						</div>
						<!-- จบ เพิ่ม -->

						<!-- card -->
						<!-- ลักษณะโครงการ -->
						<?php
						$nature_list = getPreProjectID($db_Project, $project_id);
						$nature_name = getSetNature($db_Project, $nature_list['code_nature']);

						?>
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($nature_list['code_nature'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								ลักษณะโครงการ
								<? if (empty($nature_list['code_nature'])) { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-nature-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>
							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">

								<?= htmlspecialchars($nature_name['name']); ?>
								<? if (!empty($nature_list['code_nature'])) {  ?>
									<!-- 🔹 ปุ่มแก้ไข -->
									<button type="button"
										class="btn btn-link p-0 icon-btn edit"
										title="แก้ไขข้อมูล"
										data-target="#modal-edit-nature<?= $pre_project_data['id']; ?>"
										data-toggle="modal">
										<i class="fa fa-edit fa-lg"></i>
									</button>

									<!-- 🔹 ปุ่มลบ -->
									<button type="button"
										class="btn btn-link p-0 ml-2 icon-btn delete"
										title="ลบข้อมูล"
										data-target="#modal-del-nature<?= $pre_project_data['id']; ?>"
										data-toggle="modal">
										<i class="fa fa-trash fa-lg"></i>
									</button>
								<? } ?>


							</p>

							<!-- with white backdrop -->
							<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-nature<?= $pre_project_data['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
								<div class="modal-dialog " role="document">
									<div class="modal-content bgc-transparent brc-danger-m2 shadow">
										<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
											<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
												WARNING!
											</h5>

											<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true" class="text-150">&times;</span>
											</button>
										</div>


										<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
											<div class="d-flex align-items-top mr-2 mr-md-5">
												<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
												<div class="text-secondary-d2 text-105">
													<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

												</div>
											</div>
										</div>

										<div class="modal-footer bgc-white-tp2 border-0">
											<form action="?page=<?= $page; ?>&op=update_project" method="POST">
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">

												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="code_nature">
												<input type="hidden" name="type" value="delete">
												<button class="btn btn-danger" type="submit">ยืนยัน </button>

											</form>
										</div>
									</div>
								</div>
							</div>
							<!-- แก้ไข -->
							<div class="modal fade modal-lg" id="modal-edit-nature<?= $pre_project_data['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-plus text-secondary"></i> แก้ไขลักษณะโครงการ
											</h5>

											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>

										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_project" method="POST">

													<div class="row col-12 mt-2 mt-md-4">
														<!-- the left side profile picture and other info -->
														<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
														<div class="row col-md-12 mt-3">
															<div class=col-md-3>
																<i class="fas fa-layer-group text-blue"></i>
																ลักษณะโครงการ
															</div>
															<div class=col-md-6>
																<?php
																$nature_list = getNatureSelect($db_Project);

																$id_selected = $pre_project_data['code_nature']  ?? null; // รับค่า id ที่ส่งเข้ามา

																// สร้าง select
																echo '<select name="data" id="data" class="form-control">';

																foreach ($nature_list as $nature) {
																	$selected = ($id_selected == $nature['id']) ? 'selected' : '';
																	echo '<option value="' . htmlspecialchars($nature['id']) . '" ' . $selected . '>'
																		. htmlspecialchars($nature['name'])
																		. '</option>';
																}

																echo '</select>';
																?>

															</div>

														</div>

													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">
													<input type="hidden" name="years_select" value="<?= $years_select; ?>">
													<input type="hidden" name="project_id" value="<?= $project_id; ?>">

													<input type="hidden" name="overview" value="<?= $overview; ?>">
													<input type="hidden" name="tb_data" value="pre_project">
													<input type="hidden" name="col_data" value="code_nature">

													<div class="offset-md-4 col-md-8 text-nowrap mt-5">
														<button class="btn btn-info btn-bold px-4" type="submit">
															<i class="fa fa-check mr-1"></i>
															บันทึกข้อมูล
														</button>
														<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
															<i class="fa fa-undo mr-1"></i>
															ค่าเริ่มต้น
														</button>
													</div>
												</form>
											</div>
										</div>
										<div class="modal-footer mt-3">
											<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
												<i class="ace-icon fa fa-times"></i>
												Close
											</button>
										</div>
									</div><!-- /.modal-content -->
								</div><!-- /.modal-dialog -->
							</div>
							<!-- จบ เพิ่ม -->

						</div>
						<!-- เพิ่ม -->
						<div class="modal fade modal-lg" id="modal-add-nature-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-plus text-secondary"></i> เพิ่มลักษณะโครงการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_project" method="POST">

												<div class="row col-12 mt-2 mt-md-4">
													<!-- the left side profile picture and other info -->
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
													<div class="row col-md-12 mt-3">
														<div class=col-md-3>
															<i class="fas fa-layer-group text-blue"></i>
															ลักษณะโครงการ
														</div>
														<div class=col-md-9>
															<?php
															$nature_list = getNatureSelect($db_Project);

															// สร้าง select
															echo '<select name="data" id="data" class="form-control">';

															foreach ($nature_list as $nature) {

																echo '<option value="' . htmlspecialchars($nature['id']) . '" ' . '>'
																	. htmlspecialchars($nature['name'])
																	. '</option>';
															}
															echo '</select>';
															?>
														</div>

													</div>
												</div>
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="code_nature">

												<div class="offset-md-4 col-md-8 text-nowrap mt-5">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i>
														บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i>
														ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>



									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i>
											Close
										</button>


									</div>

								</div><!-- /.modal-content -->
							</div><!-- /.modal-dialog -->
						</div>
						<!-- จบ เพิ่ม -->

						<!-- card -->
						<!-- ประเภทโครงการ -->
						<?php
						$type_list = getPreProjectID($db_Project, $project_id);
						$type_name = getSetProjectType($db_Project, $type_list['code_type']);

						?>
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($type_list['code_type'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								ประเภทโครงการ
								<? if (empty($type_list['code_type'])) { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-type-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<? } ?>
							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">

								<?= htmlspecialchars($type_name['name']); ?>
								<? if (!empty($type_list['code_type'])) { ?>
									<!-- 🔹 ปุ่มแก้ไข -->
									<button type="button"
										class="btn btn-link p-0 icon-btn edit"
										title="แก้ไขข้อมูล"
										data-target="#modal-edit-type<?= $pre_project_data['id']; ?>"
										data-toggle="modal">
										<i class="fa fa-edit fa-lg"></i>
									</button>

									<!-- 🔹 ปุ่มลบ -->
									<button type="button"
										class="btn btn-link p-0 ml-2 icon-btn delete"
										title="ลบข้อมูล"
										data-target="#modal-del-type<?= $pre_project_data['id']; ?>"
										data-toggle="modal">
										<i class="fa fa-trash fa-lg"></i>
									</button>
								<? } ?>


							</p>

							<!-- with white backdrop -->
							<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-type<?= $pre_project_data['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
								<div class="modal-dialog " role="document">
									<div class="modal-content bgc-transparent brc-danger-m2 shadow">
										<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
											<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
												WARNING!
											</h5>

											<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true" class="text-150">&times;</span>
											</button>
										</div>


										<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
											<div class="d-flex align-items-top mr-2 mr-md-5">
												<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
												<div class="text-secondary-d2 text-105">
													<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

												</div>
											</div>
										</div>

										<div class="modal-footer bgc-white-tp2 border-0">
											<form action="?page=<?= $page; ?>&op=update_project" method="POST">
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">

												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="code_type">
												<input type="hidden" name="type" value="delete">
												<button class="btn btn-danger" type="submit">ยืนยัน </button>

											</form>
										</div>
									</div>
								</div>
							</div>
							<!-- แก้ไข -->
							<div class="modal fade modal-lg" id="modal-edit-type<?= $pre_project_data['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-plus text-secondary"></i> แก้ไขประเภทโครงการ
											</h5>

											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>

										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_project" method="POST">

													<div class="row col-12 mt-2 mt-md-4">
														<!-- the left side profile picture and other info -->
														<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
														<div class="row col-md-12 mt-3">
															<div class=col-md-3>
																<i class="fas fa-layer-group text-blue"></i>
																ประเภทโครงการ
															</div>
															<div class=col-md-6>
																<?php
																$type_list = getProjectTypeSelect($db_Project);

																$id_selected = $pre_project_data['code_type']  ?? null; // รับค่า id ที่ส่งเข้ามา

																// สร้าง select
																echo '<select name="data" id="data" class="form-control">';

																foreach ($type_list as $type) {
																	$selected = ($id_selected == $type['id']) ? 'selected' : '';
																	echo '<option value="' . htmlspecialchars($type['id']) . '" ' . $selected . '>'
																		. htmlspecialchars($type['name'])
																		. '</option>';
																}

																echo '</select>';
																?>

															</div>

														</div>

													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">
													<input type="hidden" name="years_select" value="<?= $years_select; ?>">
													<input type="hidden" name="project_id" value="<?= $project_id; ?>">

													<input type="hidden" name="overview" value="<?= $overview; ?>">
													<input type="hidden" name="tb_data" value="pre_project">
													<input type="hidden" name="col_data" value="code_type">

													<div class="offset-md-4 col-md-8 text-nowrap mt-5">
														<button class="btn btn-info btn-bold px-4" type="submit">
															<i class="fa fa-check mr-1"></i>
															บันทึกข้อมูล
														</button>
														<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
															<i class="fa fa-undo mr-1"></i>
															ค่าเริ่มต้น
														</button>
													</div>
												</form>
											</div>
										</div>
										<div class="modal-footer mt-3">
											<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
												<i class="ace-icon fa fa-times"></i>
												Close
											</button>
										</div>
									</div><!-- /.modal-content -->
								</div><!-- /.modal-dialog -->
							</div>
							<!-- จบ เพิ่ม -->

						</div>
						<!-- เพิ่ม -->
						<div class="modal fade modal-lg" id="modal-add-type-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-plus text-secondary"></i> เพิ่มประเภทโครงการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_project" method="POST">

												<div class="row col-12 mt-2 mt-md-4">
													<!-- the left side profile picture and other info -->
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
													<div class="row col-md-12 mt-3">
														<div class=col-md-3>
															<i class="fas fa-layer-group text-blue"></i>
															ประเภทโครงการ
														</div>
														<div class=col-md-9>
															<?php
															$type_list = getProjectTypeSelect($db_Project);

															// สร้าง select
															echo '<select name="data" id="data" class="form-control">';

															foreach ($type_list as $type) {

																echo '<option value="' . htmlspecialchars($type['id']) . '" ' . '>'
																	. htmlspecialchars($type['name'])
																	. '</option>';
															}
															echo '</select>';
															?>
														</div>

													</div>
												</div>
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="code_type">

												<div class="offset-md-4 col-md-8 text-nowrap mt-5">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i>
														บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i>
														ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>



									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i>
											Close
										</button>


									</div>

								</div><!-- /.modal-content -->
							</div><!-- /.modal-dialog -->
						</div>
						<!-- จบ เพิ่ม -->

						<!-- card -->
						<!-- แหล่งงบ -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$budget_list = getProjectBudgetList($db_Project, $project_id);
								if (!empty($budget_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								แหล่งงบประมาณ

								<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-budget-project<?= $project_id; ?>">
									<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
								</button>

							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">
								<?php


								if (!empty($budget_list)) :
									//	echo $project_id;
									foreach ($budget_list as $i => $budget) :
										$budget_name = getSetBudget($db_Project, $budget['code_budget']);


								?>

										<?php
										if ($budget['code_budget'] == '99') {
											echo '(' . htmlspecialchars($budget_name['name']) . ') ' . htmlspecialchars($budget['budget_other']);
										} else {
											echo  htmlspecialchars($budget_name['name']);
										}
										?>
										<!-- 🔹 ปุ่มแก้ไข -->
										<button type="button"
											class="btn btn-link p-0 icon-btn edit"
											title="แก้ไขข้อมูล"
											data-target="#modal-edit-budget<?= $budget['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-edit fa-lg"></i>
										</button>

										<!-- 🔹 ปุ่มลบ -->
										<button type="button"
											class="btn btn-link p-0 ml-2 icon-btn delete"
											title="ลบข้อมูล"
											data-target="#modal-del-budget<?= $budget['id']; ?>"
											data-toggle="modal">
											<i class="fa fa-trash fa-lg"></i>
										</button>
										<!-- with white backdrop -->
							<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-budget<?= $budget['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
								<div class="modal-dialog " role="document">
									<div class="modal-content bgc-transparent brc-danger-m2 shadow">
										<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
											<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
												WARNING!
											</h5>

											<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true" class="text-150">&times;</span>
											</button>
										</div>


										<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
											<div class="d-flex align-items-top mr-2 mr-md-5">
												<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
												<div class="text-secondary-d2 text-105">
													<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

												</div>
											</div>
										</div>

										<div class="modal-footer bgc-white-tp2 border-0">
											<form action="?page=<?= $page; ?>&op=save_data_budget" method="POST">
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="budget_id" value="<?= $budget['id']; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="project_budget">

												<input type="hidden" name="type" value="delete">
												<button class="btn btn-danger" type="submit">ยืนยัน </button>

											</form>
										</div>
									</div>
								</div>
							</div>

							<!-- แก้ไข -->
							<div class="modal fade modal-lg" id="modal-edit-budget<?= $budget['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-edit text-secondary"></i> แก้ไขแหล่งงบประมาณ
											</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation"
													id="form-edit-budget-<?= $budget['id']; ?>"
													novalidate
													enctype="multipart/form-data"
													action="?page=<?= $page; ?>&op=save_data_budget"
													method="POST">

													<div class="row col-12 mt-2 mt-md-4">
														<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

														<!-- แหล่งงบประมาณ -->
														<div class="row col-md-12 mt-3">
															<div class="col-md-3">
																<i class="fas fa-layer-group text-blue"></i> แหล่งงบประมาณ
															</div>
															<div class="col-md-6">
																<select name="budget_code" id="budget_code_edit_<?= $budget['id']; ?>" class="form-control" required>
																	<option value="">-- เลือกแหล่งงบประมาณ --</option>
																	<?php
																	$budget_set_list = getAddBudgetSelect($db_Project);
																	foreach ($budget_set_list as $budget_set) {
																		$selected = ($budget['code_budget'] == $budget_set['id']) ? 'selected' : '';
																		echo '<option value="' . htmlspecialchars($budget_set['id']) . '" ' . $selected . '>'
																			. htmlspecialchars($budget_set['name'])
																			. '</option>';
																	}
																	?>
																	<!-- <option value="99" <?= ($budget['code_budget'] == '99') ? 'selected' : ''; ?>>อื่นๆ</option> -->
																</select>
															</div>
														</div>

														<!-- ช่องกรอกอื่นๆ -->
														<div class="row col-md-12 mt-3 <?= ($budget['code_budget'] == '99') ? '' : 'd-none'; ?>" id="budget_other_wrapper_edit_<?= $budget['id']; ?>">
															<div class="col-md-3">
																<i class="fas fa-pen text-blue"></i> โปรดระบุ
															</div>
															<div class="col-md-9">
																<input type="text"
																	name="budget_other"
																	id="budget_other_edit_<?= $budget['id']; ?>"
																	class="form-control"
																	placeholder="กรอกแหล่งงบประมาณอื่นๆ..."
																	value="<?= htmlspecialchars($budget['budget_other'] ?? ''); ?>"
																	<?= ($budget['code_budget'] == '99') ? 'required' : ''; ?>>
															</div>
														</div>
													</div>

													<input type="hidden" name="op_from" value="<?= $op_from; ?>">
													<input type="hidden" name="years_select" value="<?= $years_select; ?>">
													<input type="hidden" name="project_id" value="<?= $project_id; ?>">
													<input type="hidden" name="budget_id" value="<?= $budget['id']; ?>">
													<input type="hidden" name="overview" value="<?= $overview; ?>">
													<input type="hidden" name="tb_data" value="project_budget">
													<input type="hidden" name="type" value="update">

													<div class="offset-md-4 col-md-8 text-nowrap mt-5">
														<button class="btn btn-info btn-bold px-4" type="submit">
															<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
														</button>
														<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
															<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
														</button>
													</div>
												</form>
											</div>
										</div>

										<div class="modal-footer mt-3">
											<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
												<i class="ace-icon fa fa-times"></i> Close
											</button>
										</div>
									</div>
								</div>
							</div>

							<!-- ✅ SCRIPT -->
							<script>
								$(document).ready(function() {
									const select = $('#budget_code_edit_<?= $budget['id']; ?>');
									const wrapper = $('#budget_other_wrapper_edit_<?= $budget['id']; ?>');
									const input = $('#budget_other_edit_<?= $budget['id']; ?>');
									const form = $('#form-edit-budget-<?= $budget['id']; ?>');

									// ✅ ตรวจค่าเริ่มต้น
									if (select.val() === '99') {
										wrapper.removeClass('d-none');
										input.attr('required', true);
									}

									// ✅ เมื่อเปลี่ยนค่า
									select.on('change', function() {
										const selected = $(this).val();
										if (selected === '99') {
											wrapper.removeClass('d-none');
											input.attr('required', true);
										} else {
											wrapper.addClass('d-none');
											input.removeAttr('required').val('');
										}
									});

									// ✅ ตรวจสอบก่อน submit (เฉพาะกรณีเลือก 99)
									form.on('submit', function(e) {
										const selected = select.val();
										const other = input.val().trim();

										// ตรวจเฉพาะตอนเลือก "อื่นๆ"
										if (selected === '99' && other === '') {
											e.preventDefault();
											Swal.fire({
												icon: 'warning',
												title: 'กรุณากรอกแหล่งงบประมาณอื่นๆ',
												text: 'เมื่อเลือก “อื่นๆ” กรุณากรอกข้อมูลเพิ่มเติมก่อนบันทึกค่ะ',
												confirmButtonText: 'ตกลง',
												confirmButtonColor: '#3085d6'
											});
										}
									});
								});
							</script>
							<!-- จบ เพิ่ม -->
							<br>
					<?php
									endforeach;
								else :
									echo " - ";
								endif;
					?>


					</p>




						</div>
						<!-- เพิ่ม -->
						<div class="modal fade modal-lg" id="modal-add-budget-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-plus text-secondary"></i> เพิ่มแหล่งงบประมาณ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation" id="form-add-budget_<?= $project_id; ?>" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_budget" method="POST">

												<div class="row col-12 mt-2 mt-md-4">
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>
													<div class="row col-md-12 mt-3">
														<div class="col-md-3">
															<i class="fas fa-layer-group text-blue"></i>
															แหล่งงบประมาณ
														</div>
														<div class="col-md-9">
															<select name="budget_code" id="budget_code_add_<?= $project_id; ?>" class="form-control" required>
																<option value="">-- เลือกแหล่งงบประมาณ --</option>
																<?php
																$budget_set_list = getAddBudgetSelect($db_Project);
																foreach ($budget_set_list as $budget_set) {
																	echo '<option value="' . htmlspecialchars($budget_set['id']) . '">' . htmlspecialchars($budget_set['name']) . '</option>';
																}
																?>
																<!-- <option value="99">อื่นๆ</option> -->
															</select>
														</div>
													</div>

													<!-- ช่องอื่นๆ -->
													<div class="row col-md-12 mt-3 d-none" id="budget_other_wrapper_<?= $project_id; ?>">
														<div class="col-md-3">
															<i class="fas fa-pen text-blue"></i> โปรดระบุ
														</div>
														<div class="col-md-9">
															<input type="text" name="budget_other" id="budget_other_add_<?= $project_id; ?>" class="form-control" placeholder="กรอกแหล่งงบประมาณอื่นๆ...">
														</div>
													</div>

												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="project_budget">
												<input type="hidden" name="type" value="insert">

												<div class="offset-md-4 col-md-8 text-nowrap mt-5">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i>
														บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i>
														ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>

									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i>
											Close
										</button>
									</div>

								</div>
							</div>
						</div>
						<script>
							$(document).ready(function() {
								// ✅ ใช้ project_id เพื่อไม่ให้ชนกับ modal อื่น
								const formId = '#form-add-budget-<?= $project_id; ?>';
								const selectId = '#budget_code_add_<?= $project_id; ?>';
								const otherWrapper = '#budget_other_wrapper_<?= $project_id; ?>';
								const otherInput = '#budget_other_add_<?= $project_id; ?>';

								// เมื่อเปลี่ยนค่า select
								$(selectId).on('change', function() {
									const selected = $(this).val();
									if (selected === '99') {
										$(otherWrapper).removeClass('d-none');
										$(otherInput).attr('required', true);
									} else {
										$(otherWrapper).addClass('d-none');
										$(otherInput).removeAttr('required').val('');
									}
								});

								// ตรวจสอบก่อน submit
								$(formId).on('submit', function(e) {
									const selected = $(selectId).val();
									const other = $(otherInput).val().trim();

									// ✅ เตือนเฉพาะตอนเลือก "อื่นๆ" แล้วไม่กรอก
									if (selected === '99' && other === '') {
										e.preventDefault();
										Swal.fire({
											icon: 'warning',
											title: 'กรุณากรอกแหล่งงบประมาณอื่นๆ',
											text: 'เมื่อเลือก “อื่นๆ” กรุณากรอกรายละเอียดเพิ่มเติมก่อนบันทึกข้อมูล',
											confirmButtonText: 'ตกลง',
											confirmButtonColor: '#3085d6'
										});
										return false;
									}
								});
							});
						</script>



						<!-- card -->
						<!-- ระยะเวลาดำเนินการ -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($pre_project_data['start_month'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								ระยะเวลาดำเนินการ
								<?php if (!empty($pre_project_data['start_month'])) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-time-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-time-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>

							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">
								<?php if (!empty($pre_project_data['start_month'])) : ?>
									<span>
										วันที่เริ่มต้นโครงการ :
										<span class="text-primary font-weight-bold">
											<?= convertMonthToThai($pre_project_data['start_month']); ?>

											ปี <?= htmlspecialchars($pre_project_data['start_year'] ?? '-'); ?></span>
									</span>
									<br>
								<?php endif; ?>

								<?php if (!empty($pre_project_data['end_month'])) : ?>
									<span>
										วันที่สิ้นสุดโครงการ :
										<span class="text-danger font-weight-bold">
											<?= convertMonthToThai($pre_project_data['end_month']); ?>

											ปี <?= htmlspecialchars($pre_project_data['end_year'] ?? '-'); ?></span>
									</span>
								<?php endif; ?>

							</p>




						</div>

						<!-- ✅ Modal: เพิ่มระยะเวลาดำเนินการ -->
						<div class="modal fade modal-lg" id="modal-add-time-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered" role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-calendar-alt text-secondary"></i> เพิ่มระยะเวลาดำเนินการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation"
												id="form-monthyear-<?= $project_id; ?>"
												novalidate
												enctype="multipart/form-data"
												action="?page=<?= $page; ?>&op=save_data_time_project"
												method="POST">

												<div class="row col-12 mt-3">
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>



													<!-- ✅ เริ่มต้นฟอร์มระยะเวลาโครงการ -->
													<div id="project-time-container" class="container-fluid">
														<!-- จะถูกเติมเนื้อหาด้วย JavaScript -->
													</div>
													<!-- ✅ จบฟอร์ม -->

													<script>
														document.addEventListener("DOMContentLoaded", function() {
															generateMonthYearSelects("project-time-container");
														});

														function generateMonthYearSelects(containerId) {
															const container = document.getElementById(containerId);
															if (!container) return;

															const months = [{
																	value: "01",
																	text: "มกราคม"
																},
																{
																	value: "02",
																	text: "กุมภาพันธ์"
																},
																{
																	value: "03",
																	text: "มีนาคม"
																},
																{
																	value: "04",
																	text: "เมษายน"
																},
																{
																	value: "05",
																	text: "พฤษภาคม"
																},
																{
																	value: "06",
																	text: "มิถุนายน"
																},
																{
																	value: "07",
																	text: "กรกฎาคม"
																},
																{
																	value: "08",
																	text: "สิงหาคม"
																},
																{
																	value: "09",
																	text: "กันยายน"
																},
																{
																	value: "10",
																	text: "ตุลาคม"
																},
																{
																	value: "11",
																	text: "พฤศจิกายน"
																},
																{
																	value: "12",
																	text: "ธันวาคม"
																}
															];

															const today = new Date();
															const currentMonth = (today.getMonth() + 1).toString().padStart(2, "0");
															const currentYear = today.getFullYear() + 543;
															const nextYear = currentYear + 1;

															container.innerHTML = `
																<!-- 🔹 เริ่มต้นโครงการ -->
																<div class="row col-md-12 mt-3 align-items-center">
																	<div class="col-md-4">
																		<i class="fas fa-calendar-alt text-blue"></i> เริ่มต้นโครงการ เดือน
																	</div>
																	<div class="col-md-3">
																		<select class="form-control" name="start_month" id="start_month" required>
																			<option value="">-- เลือกเดือน --</option>
																			${months.map(m => 
																				`<option value="${m.value}" ${m.value === currentMonth ? "selected" : ""}>${m.text}</option>`
																			).join("")}
																		</select>
																	</div>
																	<div class="col-md-2 text-right">
																		 ปี
																	</div>
																	<div class="col-md-3">
																		<select class="form-control" name="start_year" id="start_year" required>
																			<option value="">-- เลือกปี --</option>
																			<option value="${currentYear}" selected>${currentYear}</option>
																			<option value="${nextYear}">${nextYear}</option>
																		</select>
																	</div>
																</div>

																<!-- 🔹 สิ้นสุดโครงการ -->
																<div class="row col-md-12 mt-3 align-items-center">
																	<div class="col-md-4">
																		<i class="fas fa-calendar-check text-blue"></i> สิ้นสุดโครงการ เดือน
																	</div>
																	<div class="col-md-3">
																		<select class="form-control" name="end_month" id="end_month" required>
																			<option value="">-- เลือกเดือน --</option>
																			${months.map(m => 
																				`<option value="${m.value}" ${m.value === currentMonth ? "selected" : ""}>${m.text}</option>`
																			).join("")}
																		</select>
																	</div>
																	<div class="col-md-2 text-right">
																		 ปี
																	</div>
																	<div class="col-md-3">
																		<select class="form-control" name="end_year" id="end_year" required>
																			<option value="">-- เลือกปี --</option>
																			<option value="${currentYear}">${currentYear}</option>
																			<option value="${nextYear}" selected>${nextYear}</option>
																		</select>
																	</div>
																</div>
															`;
														}
													</script>


												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="type" value="update">

												<div class="offset-md-4 col-md-8 text-nowrap mt-4">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>

									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> Close
										</button>
									</div>

								</div>
							</div>
						</div>

						<script>
							$(function() {
								const monthNamesThai = [
									"มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
									"กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
								];

								// ตั้งค่า Datepicker ให้เลือกเฉพาะเดือนและปี
								$('#id-daterange-from1, #id-daterange-to1').datepicker({
									format: "MM yyyy",
									startView: "months",
									minViewMode: "months",
									language: "th",
									autoclose: true,
									todayHighlight: true
								}).on('changeDate', function(e) {
									const date = e.date;
									const month = date.getMonth() + 1;
									const year = date.getFullYear() + 543;
									$(this).val(monthNamesThai[month - 1] + " " + year);
								});
							});
						</script>
						<!-- ระยะเวลา -->

						<!-- พื้นที่ดำเนินการ -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($pre_project_data['location'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								พื้นที่ดำเนินการ
								<?php if (!empty($pre_project_data['location'])) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-location-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-location-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>

							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">

								<?= htmlspecialchars($pre_project_data['location']); ?>



							</p>




						</div>

						<!-- ✅ Modal: พื้นที่ดำเนินการ -->
						<div class="modal fade modal-lg" id="modal-add-location-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog" role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-calendar-alt text-secondary"></i> เพิ่มพื้นที่ดำเนินการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form class="row col-12 needs-validation"
												id="form-location-<?= $project_id; ?>"
												novalidate
												enctype="multipart/form-data"
												action="?page=<?= $page; ?>&op=save_data_location_project"
												method="POST">

												<div class="row col-12 mt-3">
													<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

													<div class="row col-md-12 mt-3">
														<div class="col-md-4">
															<i class="fas fa-layer-group text-blue"></i>
															เพิ่มพื้นที่ดำเนินการ
														</div>
														<div class="col-md-8">
															<input type="text" name="location" id="location" class="form-control" value="<?= htmlspecialchars($pre_project_data['location']); ?>" required>

														</div>
													</div>




												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="location">
												<input type="hidden" name="type" value="update">

												<div class="offset-md-4 col-md-8 text-nowrap mt-4">
													<button class="btn btn-info btn-bold px-4" type="submit">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>
											</form>
										</div>
									</div>

									<div class="modal-footer mt-3">
										<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> Close
										</button>
									</div>

								</div>
							</div>
						</div>


						<!-- พื้นที่ดำเนินการ -->
						<!-- หลักการและเหตุผล -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($pre_project_data['reson'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								ความสำคัญของโครงการ/หลักการและเหตุผล
								<?php if (!empty($pre_project_data['reson'])) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-reson-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-reson-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>

							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">

								<?= renderCleanHTML($pre_project_data['reson']); ?>



							</p>




						</div>

						<!-- ✅ Modal: เพิ่มความสำคัญของโครงการ/หลักการและเหตุผล -->
						<div class="modal fade modal-lg" id="modal-add-reson-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered" role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่มความสำคัญของโครงการ / หลักการและเหตุผล
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-reson-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_location_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">
													<label for="summernote-reson" class="font-weight-bold">ความสำคัญของโครงการ / หลักการและเหตุผล</label><br>
													<!-- <textarea id="summernote-reson" name="reson"><?= htmlspecialchars($pre_project_data['reson']); ?></textarea> -->
													<!-- <div id="bootstrap-editor" name="resons" class="bootstrap-wysiwyg-editor px-3 py-2"></div> -->
													<textarea name="reson" id="reson" class="form-control" style="width:100%;" rows='10'><?= htmlspecialchars($pre_project_data['reson']); ?></textarea>

												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="reson">
												<input type="hidden" name="type" value="update">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>


						<!-- หลักการและเหตุผล -->
						<!-- ทบทวนผลการดำเนินโครงการที่ผ่านมา -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($pre_project_data['result'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								ทบทวนผลการดำเนินโครงการที่ผ่านมา
								<?php if (!empty($pre_project_data['result'])) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-result-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-result-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>

							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">

								<?= renderCleanHTML($pre_project_data['result']); ?>



							</p>




						</div>

						<!-- ✅ Modal: เพิ่มทบทวนผลการดำเนินโครงการที่ผ่านมา -->
						<div class="modal fade modal-lg" id="modal-add-result-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered" role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่มทบทวนผลการดำเนินโครงการที่ผ่านมา
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-result-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_location_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">
													<label for="summernote-reson" class="font-weight-bold">ทบทวนผลการดำเนินโครงการที่ผ่านมา</label><br>
													<!-- <textarea id="summernote-reson" name="reson"><?= htmlspecialchars($pre_project_data['reson']); ?></textarea> -->
													<!-- <div id="bootstrap-editor" name="resons" class="bootstrap-wysiwyg-editor px-3 py-2"></div> -->
													<textarea name="result" id="result" class="form-control" style="width:100%;" rows='10'><?= htmlspecialchars($pre_project_data['result']); ?></textarea>

												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="result">
												<input type="hidden" name="type" value="update">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>


						<!-- ทบทวนผลการดำเนินโครงการที่ผ่านมา -->
						<!-- วัตถุประสงค์ของโครงการ  -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($pre_project_data['objective'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								วัตถุประสงค์ของโครงการ
								<?php if (!empty($pre_project_data['objective'])) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-objective-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-objective-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>

							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">

								<?= renderCleanHTML($pre_project_data['objective']); ?>



							</p>




						</div>

						<!-- ✅ Modal: วัตถุประสงค์ของโครงการ  -->
						<div class="modal fade modal-lg" id="modal-add-objective-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered" role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่มวัตถุประสงค์ของโครงการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-objective-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_location_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">
													<label for="summernote-reson" class="font-weight-bold">วัตถุประสงค์ของโครงการ </label><br>
													<!-- <textarea id="summernote-reson" name="reson"><?= htmlspecialchars($pre_project_data['reson']); ?></textarea> -->
													<!-- <div id="bootstrap-editor" name="resons" class="bootstrap-wysiwyg-editor px-3 py-2"></div> -->
													<textarea name="objective" id="objective" class="form-control" style="width:100%;" rows='10'><?= htmlspecialchars($pre_project_data['objective']); ?></textarea>

												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="objective">
												<input type="hidden" name="type" value="update">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>


						<!-- วัตถุประสงค์ของโครงการ  -->
						<!-- ความสอดคล้อง  -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$consistency_data = getProjectConsistency($db_Project, $project_id);
								if (!empty($consistency_data['code_consistency'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								ความสอดคล้อง


							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">
								<i class="fas fa-circle fa-xs" style="color: #78a7c4;"></i>
								ความสอดคล้อง :
								<?php
								$consistency = getSetConsistency($db_Project, $consistency_data['code_consistency']);
								echo '<span class="badge bgc-green-d1 pos-rel text-white text-100 radius-4 px-3">
									<span class="bgc-primary-tp4 opacity-5 position-tl h-100 w-100 radius-4"></span>
								<span class="pos-rel">' . htmlspecialchars($consistency['name']) . '</span></span>';

								?>
								<?php if (!empty($consistency_data['code_consistency'])) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-consistency-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-consistency-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>
								<br>
							<div class="ml-4 mt-1">
								<?php
								$consistency_map_list = getProjectConsistencyMapList($db_Project, $consistency_data['id']);

								foreach ($consistency_map_list as $map_item) {

									// ดึงชื่อระดับความสอดคล้อง
									$consistency_map = getSetConsistencyDetailByID($db_Project, $map_item['code_consistency_detail']);

									echo '<i class="fas fa-caret-right fa-xs" style="color: #78bac4ff;"></i> '
										. htmlspecialchars($consistency_map['name']) . "<br>";
								}
								?>
							</div>


							</p>
							<p class="mb-1">
								<i class="fas fa-circle fa-xs" style="color: #78a7c4;"></i>
								ตัวชี้วัด :
								<?php
								$type_kpi_data = getSetType($db_KPI, $consistency_data['code_kpi_type']);

								//$consistency = getSetConsistency($db_KPI, $consistency_data['code_consistency']);
								echo ' <span class="badge bgc-purple-d1 pos-rel text-white text-100 radius-4 px-3">
									<span class="bgc-primary-tp4 opacity-5 position-tl h-100 w-100 radius-4"></span>
								<span class="pos-rel">
										
									' . htmlspecialchars($type_kpi_data['name']) . '</span>
								</span>';

								?>
								<?php if (!empty($consistency_data['code_kpi_type'])) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-consistency-kpi-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-consistency-kpi-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>
							<div class="ml-4 mt-1">
								<?php

								$consistency_kpi_list = getProjectConsistencyKPIDetail($db_Project, $consistency_data['id']);
								foreach ($consistency_kpi_list as $kpi_item) {

									// ดึงชื่อระดับความสอดคล้อง
									$consistency_kpi = getKPI($db_KPI, $kpi_item['code_kpi_detail']);

									echo '<i class="fas fa-caret-right fa-xs" style="color: #78bac4ff;"></i> '
										. htmlspecialchars($consistency_kpi['name']) . "<br>";
								}
								?>
							</div>
							</p>


							<?php
							$consistency_list = getConsistencySelect($db_Project);
							$selected_items = getSelectedConsistency($db_Project, $project_id);
							?>

							<div class="modal fade modal-lg" id="modal-add-consistency-project<?= $project_id; ?>">
								<div class="modal-dialog">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue">
												<i class="fas fa-check-circle"></i> เพิ่มความสอดคล้อง
											</h5>
											<button type="button" class="close" data-dismiss="modal">
												<span>&times;</span>
											</button>
										</div>

										<form method="POST" action="?page=<?= $page ?>&op=save_data_consistency_project">
											<div class="modal-body">

												<p class="text-center text-info"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<!-- เลือกหมวด -->
												<label class="font-weight-bold">เลือกหมวดความสอดคล้อง</label>
												<select id="consistencyGroupSelect<?= $project_id ?>"
													class="form-control mb-3" name="code_consistency">
													<option value="">-- เลือกความสอดคล้อง --</option>

													<?php foreach ($consistency_list as $item): ?>
														<option value="<?= $item['id']; ?>">
															<?= htmlspecialchars($item['name']); ?>
														</option>
													<?php endforeach; ?>
												</select>

												<!-- แสดง checkbox -->
												<div id="consistencyCheckboxArea<?= $project_id ?>">
													<div class="alert alert-info text-center">
														กรุณาเลือกความสอดคล้องก่อน
													</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">

												<input type="hidden" name="type" value="insert">

											</div>

											<div class="modal-footer">
												<button type="submit" class="btn btn-success px-4">
													<i class="fa fa-check"></i> บันทึก
												</button>
												<button class="btn btn-danger px-4" data-dismiss="modal">
													<i class="fa fa-times"></i> ปิด
												</button>
											</div>

										</form>

									</div>
								</div>
							</div>
							<script>
								$(document).ready(function() {

									$("#consistencyGroupSelect<?= $project_id ?>").change(function() {

										let code_consistency = $(this).val();
										let selected = <?= json_encode($selected_items) ?>;

										if (!code_consistency) {
											$("#consistencyCheckboxArea<?= $project_id ?>").html(
												'<div class="alert alert-warning text-center">กรุณาเลือกหมวดความสอดคล้องก่อน</div>'
											);
											return;
										}

										$.post("get_consistency_detail.php", {
											code_consistency: code_consistency,
											selected: selected
										}, function(data) {

											let html = '<div class="border rounded p-3 bg-light">';

											data.forEach(item => {
												html += `
											<div class="form-check mb-2">
												<input type="checkbox" class="form-check-input"
													name="consistency_detail[]"
													value="${item.id}"
													${item.checked == 1 ? "checked" : ""}>
												<label class="form-check-label">${item.name}</label>
											</div>
										`;
											});

											html += '</div>';

											$("#consistencyCheckboxArea<?= $project_id ?>").html(html);

										}, "json");

									});

								});
							</script>

							<?php
							$project_consistency = getProjectConsistency($db_Project, $project_id);
							$selected_items = getSelectedConsistencyMap($db_Project, $project_consistency['id']);

							if (!is_array($selected_items)) $selected_items = [];

							//print_r($selected_items);
							?>

							<div class="modal fade modal-lg" id="modal-edit-consistency-project<?= $project_id; ?>">
								<div class="modal-dialog">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue">
												<i class="fas fa-edit"></i> แก้ไขความสอดคล้อง<?= $project_id ?>
											</h5>
											<button type="button" class="close" data-dismiss="modal">
												<span>&times;</span>
											</button>
										</div>

										<form method="POST" action="?page=<?= $page ?>&op=save_data_consistency_project">

											<div class="modal-body">

												<p class="text-center text-info">
													<?= htmlspecialchars($pre_project_data['name_project']); ?>
												</p>

												<label>เลือกหมวดความสอดคล้อง</label>
												<select id="editConsistencyGroupSelect<?= $project_id ?>"
													class="form-control mb-3"
													name="code_consistency">

													<option value="">-- เลือกความสอดคล้อง --</option>

													<?php foreach ($consistency_list as $item): ?>
														<option value="<?= $item['id']; ?>"
															<?= ($item['id'] == ($project_consistency['code_consistency'] ?? '')) ? 'selected' : '' ?>>
															<?= htmlspecialchars($item['name']); ?>
														</option>
													<?php endforeach; ?>

												</select>

												<div id="editConsistencyCheckboxArea<?= $project_id ?>">
													<div class="alert alert-info text-center">กำลังโหลดข้อมูล...</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="project_consistency_id" value="<?= $project_consistency['id']; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">

												<input type="hidden" name="type" value="update">


											</div>

											<div class="modal-footer">
												<button type="submit" class="btn btn-success px-4">
													<i class="fa fa-check"></i> บันทึกการแก้ไข
												</button>
												<button class="btn btn-danger px-4" data-dismiss="modal">
													<i class="fa fa-times"></i> ปิด
												</button>
											</div>

										</form>

									</div>
								</div>
							</div>
							<script>
								$(document).ready(function() {

									function loadEditConsistency<?= $project_id ?>() {

										let code_consistency = $("#editConsistencyGroupSelect<?= $project_id ?>").val();
										let selected_items = <?= json_encode($selected_items) ?>;

										console.log("Loading:", code_consistency, "Selected:", selected_items);

										if (!code_consistency) {
											$("#editConsistencyCheckboxArea<?= $project_id ?>").html(
												`<div class="alert alert-warning text-center">กรุณาเลือกหมวดความสอดคล้องก่อน</div>`
											);
											return;
										}

										$.post("get_consistency_detail.php", {
												code_consistency: code_consistency,
												selected: selected_items
											},
											function(data) {

												console.log("Response:", data);

												let html = '<div class="border rounded p-3 bg-light">';

												data.forEach(item => {

													html += `
                    <div class="form-check mb-2">
                        <input type="checkbox"
                               class="form-check-input"
                               name="consistency_detail[]"
                               value="${item.id}"
                               ${item.checked == 1 ? "checked" : ""}>
                        <label class="form-check-label">${item.name}</label>
                    </div>
                `;
												});

												html += "</div>";

												$("#editConsistencyCheckboxArea<?= $project_id ?>").html(html);

											}, "json");

									}

									// เมื่อเปิด modal ให้โหลดข้อมูลเดิม
									$('#modal-edit-consistency-project<?= $project_id ?>')
										.on('shown.bs.modal', function() {
											loadEditConsistency<?= $project_id ?>();
										});

									// เมื่อเปลี่ยนหมวด
									$('#editConsistencyGroupSelect<?= $project_id ?>')
										.change(function() {
											loadEditConsistency<?= $project_id ?>();
										});

								});
							</script>


							<!-- Kpi -->


							<?php


							$type_list = getKpiTypes($db_KPI);
							$so_list   = getSOList($db_KPI);
							$ap_list   = getAPList($db_KPI);
							$side_list = getSideList($db_KPI);

							$current = getProjectConsistencyRow($db_Project, $project_id);
							?>

							<div class="modal fade modal-lg" id="modal-add-consistency-kpi-project<?= $project_id; ?>">
								<div class="modal-dialog modal-lg">
									<form method="POST" action="?page=<?= $page ?>&op=save_data_consistency_kpi_project">



										<div class="modal-content">

											<div class="modal-header bg-primary text-white">
												<h5><i class="fa fa-check-circle"></i> เพิ่มตัวชี้วัดความสอดคล้อง (KPI)</h5>
												<button class="close" data-dismiss="modal">×</button>
											</div>

											<div class="modal-body">

												<!-- TYPE -->
												<label class="font-weight-bold">เลือกประเภท (Type)</label>
												<select id="selectType" name="code_kpi_type" class="form-control mb-3">
													<option value="">-- เลือกประเภท --</option>
													<?php foreach ($type_list as $t): ?>
														<option value="<?= $t['id'] ?>" <?= ($t['id'] == ($current['code_kpi_type'] ?? '')) ? 'selected' : '' ?>>
															<?= $t['name'] ?>
														</option>
													<?php endforeach; ?>
												</select>

												<!-- SO -->
												<div id="boxSO" style="display:none;">
													<label>เลือกยุทธศาสตร์ (SO)</label>
													<select id="selectSO" name="code_so" class="form-control mb-3">
														<option value="">-- เลือก SO --</option>
														<?php foreach ($so_list as $so): ?>
															<option value="<?= $so['id'] ?>" <?= ($so['id'] == ($current['code_so'] ?? '')) ? 'selected' : '' ?>>
																<?= $so['name'] ?>
															</option>
														<?php endforeach; ?>
													</select>
												</div>

												<!-- AP -->
												<div id="boxAP" style="display:none;">
													<label>เลือก AP</label>
													<select id="selectAP" name="code_ap" class="form-control mb-3">
														<option value="">-- เลือก AP --</option>
														<?php foreach ($ap_list as $ap): ?>
															<option value="<?= $ap['id'] ?>" <?= ($ap['id'] == ($current['code_ap'] ?? '')) ? 'selected' : '' ?>>
																<?= $ap['name'] ?>
															</option>
														<?php endforeach; ?>
													</select>
												</div>

												<!-- Side -->
												<div id="boxSide" style="display:none;">
													<label>เลือก Side</label>
													<select id="selectSide" name="code_side" class="form-control mb-3">
														<option value="">-- เลือก Side --</option>
														<?php foreach ($side_list as $sd): ?>
															<option value="<?= $sd['id'] ?>" <?= ($sd['id'] == ($current['code_side'] ?? '')) ? 'selected' : '' ?>>
																<?= $sd['name'] ?>
															</option>
														<?php endforeach; ?>
													</select>
												</div>

												<hr>

												<!-- KPI -->
												<div id="boxKPI">
													<div class="alert alert-info text-center">กรุณาเลือกประเภทก่อน</div>
												</div>

											</div>
											<input type="hidden" name="op_from" value="<?= $op_from; ?>">
											<input type="hidden" name="years_select" value="<?= $years_select; ?>">
											<input type="hidden" name="project_id" value="<?= $project_id; ?>">
											<!-- <input type="hidden" name="project_consistency_id" value="<?= $project_consistency['id']; ?>"> -->
											<input type="hidden" name="overview" value="<?= $overview; ?>">

											<input type="hidden" name="type" value="insert">
											<div class="modal-footer">
												<button class="btn btn-success">
													<i class="fa fa-check"></i> บันทึก
												</button>
												<button class="btn btn-secondary" data-dismiss="modal">ปิด</button>
											</div>

										</div>

									</form>
								</div>
							</div>

							<script>
								$(document).ready(function() {

									function showBoxes() {
										let t = $("#selectType").val();
										if (t == "2") {
											$("#boxSO, #boxAP").show();
											$("#boxSide").hide();
										} else if (t == "1") {
											$("#boxSide").show();
											$("#boxSO, #boxAP").hide();
										} else {
											$("#boxSO, #boxAP, #boxSide").hide();
										}
									}

									function loadKPI() {
										$.post("get_kpi.php", {
											project_id: <?= $project_id ?>,
											type: $("#selectType").val(),
											so: $("#selectSO").val(),
											ap: $("#selectAP").val(),
											side: $("#selectSide").val()
										}, function(res) {

											if (!res || res.length === 0) {
												$("#boxKPI").html("<div class='alert alert-danger'>ไม่พบ KPI</div>");
												return;
											}

											let html = "<div class='border p-3 rounded bg-light'>";
											res.forEach(function(item) {
												html += `
										<div class="form-check mb-1">
											<input type="checkbox" name="kpi_list[]" value="${item.kpi_id}"
												class="form-check-input" ${item.checked ? 'checked' : ''}>
											<label class="form-check-label">${item.kpi_name}</label>
										</div>
										`;
											});
											html += "</div>";

											$("#boxKPI").html(html);

										}, "json");
									}

									$("#selectType").change(function() {
										showBoxes();
										loadKPI();
									});

									$("#selectSO,#selectAP,#selectSide").change(loadKPI);

									// โหลดเมื่อเปิด modal
									$('#modal-kpi-consistency').on('shown.bs.modal', function() {
										showBoxes();
										loadKPI();
									});

								});
							</script>


							<!-- KpI -->
						</div>
						<!-- ความสอดคล้อง -->
						<!-- เป้าหมายของโครงการ   -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($pre_project_data['target_project'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								เป้าหมายของโครงการ
								<?php if (!empty($pre_project_data['target_project'])) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-target-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-target-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?>

							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<p class="mb-1">

								<?= renderCleanHTML($pre_project_data['target_project']); ?>



							</p>




						</div>
						<!-- ✅ Modal: เป้าหมายของโครงการ  -->
						<div class="modal fade modal-lg" id="modal-add-target-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered" role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่มเป้าหมายของโครงการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_location_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">
													<label for="summernote-reson" class="font-weight-bold">เป้าหมายของโครงการ </label><br>
													<!-- <textarea id="summernote-reson" name="reson"><?= htmlspecialchars($pre_project_data['reson']); ?></textarea> -->
													<!-- <div id="bootstrap-editor" name="resons" class="bootstrap-wysiwyg-editor px-3 py-2"></div> -->
													<textarea name="target_project" id="target_project" class="form-control" style="width:100%;" rows='10'><?= htmlspecialchars($pre_project_data['target_project']); ?></textarea>

												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="target_project">
												<input type="hidden" name="type" value="update">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>
						<!-- เป้าหมายของโครงการ  -->

						<!-- ตัวชี้วัดผลผลิตโครงการ   -->
						<?php
						$project_kpi_list = getProjectKPIList($db_Project, $project_id);
						?>
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($project_kpi_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								ตัวชี้วัดผลผลิตโครงการ
								<!-- <?php if (!empty($project_kpi_list)) { ?>
									<button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-kpi-project<?= $project_id; ?>">
										<i class="fa fa-edit text-105 mr-3px"></i> แก้ไขข้อมูล
									</button>
								<?php } else { ?>
									<button type="button" class="btn btn-primary btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-add-kpi-project<?= $project_id; ?>">
										<i class="fa fa-plus text-105 mr-3px"></i> เพิ่มข้อมูล
									</button>
								<?php } ?> -->

							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<div class="table-responsive">
								<table class="table table-bordered table-striped text-center align-middle">
									<thead class="bg-light text-dark">
										<tr>
											<th rowspan="2" style="vertical-align: middle;">ตัวชี้วัดของโครงการ (หน่วยนับ)</th>

											<th style="vertical-align: middle;">ค่าน้ำหนัก</th>
											<th style="vertical-align: middle;"></th>
											<th colspan="13">ค่าเป้าหมายตัวชี้วัด</th>
										</tr>
										<tr class="bg-light">
											<th>รวมร้อยละ..100..</th>
											<th></th>
											<th>รวม</th>
											<th>ต.ค.</th>
											<th>พ.ย.</th>
											<th>ธ.ค.</th>
											<th>ม.ค.</th>
											<th>ก.พ.</th>
											<th>มี.ค.</th>
											<th>เม.ย.</th>
											<th>พ.ค.</th>
											<th>มิ.ย.</th>
											<th>ก.ค.</th>
											<th>ส.ค.</th>
											<th>ก.ย.</th>
										</tr>
									</thead>
									<tbody>
										<tr class="bg-light">
											<td colspan="17" class="text-left font-weight-bold">
												<a data-toggle="modal" data-target="#modal-add-kpi-project<?= $project_id; ?>" class="btn btn-sm btn-primary text-white">
													<i class="fa fa-plus"></i> เพิ่มตัวชี้วัด
												</a>
											</td>
										</tr>
										<?php
										if (!empty($project_kpi_list)) :
											foreach ($project_kpi_list as $i => $project_kpi) :
												$kpi = getKPI($db_KPI, $project_kpi['code_kpi'] ?? '');
												$unit = getKPI($db_KPI, $project_kpi['code_unit'] ?? '');
												$kpiNameShow = trim((string)($project_kpi['kpi_name'] ?? ''));
												if ($kpiNameShow === '' && is_array($kpi) && !empty($kpi['name'])) {
													$kpiNameShow = (string)$kpi['name'];
												}
												if ($kpiNameShow === '') {
													$kpiNameShow = '-';
												}
												$kpiUnitShow = trim((string)($project_kpi['kpi_unit'] ?? ''));
												if ($kpiUnitShow === '' && is_array($unit) && !empty($unit['name'])) {
													$kpiUnitShow = (string)$unit['name'];
												}
												if ($kpiUnitShow === '') {
													$kpiUnitShow = '-';
												}
												$All_month_Target =
													$project_kpi['mon_10'] +
													$project_kpi['mon_11'] +
													$project_kpi['mon_12'] +
													$project_kpi['mon_01'] +
													$project_kpi['mon_02'] +
													$project_kpi['mon_03'] +
													$project_kpi['mon_04'] +
													$project_kpi['mon_05'] +
													$project_kpi['mon_06'] +
													$project_kpi['mon_07'] +
													$project_kpi['mon_08'] +
													$project_kpi['mon_09'];
										?>
												<tr>
													<td class="text-left"><?= $i + 1; ?>. <?= htmlspecialchars($kpiNameShow); ?> (<?= htmlspecialchars($kpiUnitShow); ?>)
														<!-- <button type="button" class="btn btn-warning btn-sm btn-h-outline-primary btn-a-outline-primary " data-toggle="modal" data-target="#modal-edit-kpi-project<?= $project_kpi['id']; ?>">
															<i class="fa fa-edit text-90 mr-3px"></i> แก้ไข
														</button> -->
														<!-- 🔹 ปุ่มแก้ไข -->
														<button type="button"
															class="btn btn-link p-0 icon-btn edit"
															title="แก้ไขข้อมูล"
															data-target="#modal-edit-kpi-project<?= $project_kpi['id']; ?>"
															data-toggle="modal">
															<i class="fa fa-edit fa-lg"></i>
														</button>

														<!-- 🔹 ปุ่มลบ -->
														<button type="button"
															class="btn btn-link p-0 ml-2 icon-btn delete"
															title="ลบข้อมูล"
															data-target="#modal-del-kpi-project<?= $project_kpi['id']; ?>"
															data-toggle="modal">
															<i class="fa fa-trash fa-lg"></i>
														</button>
														<!-- with white backdrop -->
														<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-kpi-project<?= $project_kpi['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
															<div class="modal-dialog " role="document">
																<div class="modal-content bgc-transparent brc-danger-m2 shadow">
																	<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
																		<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
																			WARNING!
																		</h5>

																		<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
																			<span aria-hidden="true" class="text-150">&times;</span>
																		</button>
																	</div>


																	<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
																		<div class="d-flex align-items-top mr-2 mr-md-5">
																			<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
																			<div class="text-secondary-d2 text-105">
																				<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

																			</div>
																		</div>
																	</div>

																	<div class="modal-footer bgc-white-tp2 border-0">
																		<form action="?page=<?= $page; ?>&op=save_data_kpi_project" method="POST">
																			<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																			<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																			<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																			<input type="hidden" name="project_kpi_id" value="<?= $project_kpi['id']; ?>">
																			<input type="hidden" name="overview" value="<?= $overview; ?>">


																			<input type="hidden" name="type" value="delete">
																			<button class="btn btn-danger" type="submit">ยืนยัน </button>

																		</form>
																	</div>
																</div>
															</div>
														</div>
													</td>
													<td><?= $project_kpi['kpi_weight']; ?></td>
													<td>**?? แผน</td>
													<td class="text-primary"><?= $All_month_Target; ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_10']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_11']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_12']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_01']); ?></td>
													<td class="text-green" v><?= formatAmount($project_kpi['mon_02']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_03']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_04']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_05']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_06']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_07']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_08']); ?></td>
													<td class="text-green"><?= formatAmount($project_kpi['mon_09']); ?></td>
												</tr>

												<!-- ✅ Modal: ตัวชี้วัดผลผลิตโครงการ  -->
												<div class="modal fade modal-xl" id="modal-edit-kpi-project<?= $project_kpi['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
													<div class="modal-dialog " role="document">
														<div class="modal-content">

															<div class="modal-header">
																<h5 class="modal-title text-blue" id="exampleModalLabel2">
																	<i class="fas fa-file-alt text-secondary"></i> แก้ไขตัวชี้วัดผลผลิตโครงการ<?= $project_kpi['id']; ?>
																</h5>
																<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																</button>
															</div>

															<div class="modal-body bg-white">
																<div class="bgc-white radius-1">
																	<form id="form-target_project-<?= $project_kpi['id']; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_kpi_project" method="POST">

																		<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

																		<div class="form-group">


																			<div class="row col-md-12 mt-3">
																				<div class="col-md-8">
																					<label class="font-weight-bold">ชื่อตัวชี้วัด <span class="text-danger">*</span></label><br>
																					<input type="text" name="kpi_name" class="form-control" required placeholder="กรอกชื่อตัวชี้วัด" value="<?= htmlspecialchars($project_kpi['kpi_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
																				</div>
																				<div class="col-md-4">
																					<label class="font-weight-bold">หน่วยนับ <span class="text-danger">*</span></label><br>
																					<input type="text" name="kpi_unit" class="form-control" required placeholder="กรอกหน่วยนับ" value="<?= htmlspecialchars($project_kpi['kpi_unit'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
																				</div>
																			</div>
																			<div class="row col-md-12 mt-3">
																				<div class="col-md-12">

																					<div class="mb-3">
																						<label class="form-label fw-bold">ค่าเป้าหมาย <span class="text-danger">*</span></label>
																						<div class="month-wrapper">
																							<div class="month-box">
																								<span class="month-label">ต.ค.</span>
																								<input type="number" class="form-control month-input" name="mon_10" value="<?= $project_kpi['mon_10']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">พ.ย.</span>
																								<input type="number" class="form-control month-input" name="mon_11" value="<?= $project_kpi['mon_11']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">ธ.ค.</span>
																								<input type="number" class="form-control month-input" name="mon_12" value="<?= $project_kpi['mon_12']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">ม.ค.</span>
																								<input type="number" class="form-control month-input" name="mon_01" value="<?= $project_kpi['mon_01']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">ก.พ.</span>
																								<input type="number" class="form-control month-input" name="mon_02" value="<?= $project_kpi['mon_02']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">มี.ค.</span>
																								<input type="number" class="form-control month-input" name="mon_03" value="<?= $project_kpi['mon_03']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">เม.ย.</span>
																								<input type="number" class="form-control month-input" name="mon_04" value="<?= $project_kpi['mon_04']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">พ.ค.</span>
																								<input type="number" class="form-control month-input" name="mon_05" value="<?= $project_kpi['mon_05']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">มิ.ย.</span>
																								<input type="number" class="form-control month-input" name="mon_06" value="<?= $project_kpi['mon_06']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">ก.ค.</span>
																								<input type="number" class="form-control month-input" name="mon_07" value="<?= $project_kpi['mon_07']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">ส.ค.</span>
																								<input type="number" class="form-control month-input" name="mon_08" value="<?= $project_kpi['mon_08']; ?>" step="0.01" min="0">
																							</div>
																							<div class="month-box">
																								<span class="month-label">ก.ย.</span>
																								<input type="number" class="form-control month-input" name="mon_09" value="<?= $project_kpi['mon_09']; ?>" step="0.01" min="0">
																							</div>
																						</div>
																					</div>
																				</div>
																			</div>
																			<div class="row col-md-12 mt-3">
																				<div class="col-md-4">
																					<label for="summernote-reson" class="font-weight-bold">ค่าน้ำหนักของตัวชี้วัด <span class="text-danger">*</span></label><br>
																					<input type="number" name="kpi_weight" id="kpi_weight" class="form-control" placeholder="* กรอก 0-100 " value="<?= $project_kpi['kpi_weight']; ?>" required>
																				</div>
																			</div>
																		</div>

																		<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																		<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																		<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																		<input type="hidden" name="project_kpi_id" value="<?= $project_kpi['id']; ?>">
																		<input type="hidden" name="overview" value="<?= $overview; ?>">


																		<input type="hidden" name="type" value="update">

																		<div class="text-center mt-4">
																			<button type="submit" class="btn btn-info btn-bold px-4">
																				<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
																			</button>
																			<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
																				<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
																			</button>
																		</div>

																	</form>
																</div>
															</div>

															<div class="modal-footer">
																<button class="btn btn-sm btn-danger" data-dismiss="modal">
																	<i class="ace-icon fa fa-times"></i> ปิด
																</button>
															</div>

														</div>
													</div>
												</div>
											<?php endforeach; ?>
										<?php else : ?>
											<tr>
												<td colspan="16" style="text-align:center;">ไม่พบข้อมูล</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>




						</div>
						<!-- ✅ Modal: ตัวชี้วัดผลผลิตโครงการ  -->
						<div class="modal fade modal-xl" id="modal-add-kpi-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่มตัวชี้วัดผลผลิตโครงการ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_kpi_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">


													<div class="row col-md-12 mt-3">
														<div class="col-md-8">
															<label class="font-weight-bold">ชื่อตัวชี้วัด <span class="text-danger">*</span></label><br>
															<input type="text" name="kpi_name" class="form-control" required placeholder="กรอกชื่อตัวชี้วัด" value="">
														</div>
														<div class="col-md-4">
															<label class="font-weight-bold">หน่วยนับ <span class="text-danger">*</span></label><br>
															<input type="text" name="kpi_unit" class="form-control" required placeholder="กรอกหน่วยนับ" value="">
														</div>
													</div>
													<div class="row col-md-12 mt-3">
														<div class="col-md-12">

															<div class="mb-3">
																<label class="form-label fw-bold">ค่าเป้าหมาย <span class="text-danger">*</span></label>
																<div class="month-wrapper">
																	<div class="month-box">
																		<span class="month-label">ต.ค.</span>
																		<input type="number" class="form-control month-input" name="mon_10" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">พ.ย.</span>
																		<input type="number" class="form-control month-input" name="mon_11" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">ธ.ค.</span>
																		<input type="number" class="form-control month-input" name="mon_12" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">ม.ค.</span>
																		<input type="number" class="form-control month-input" name="mon_01" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">ก.พ.</span>
																		<input type="number" class="form-control month-input" name="mon_02" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">มี.ค.</span>
																		<input type="number" class="form-control month-input" name="mon_03" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">เม.ย.</span>
																		<input type="number" class="form-control month-input" name="mon_04" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">พ.ค.</span>
																		<input type="number" class="form-control month-input" name="mon_05" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">มิ.ย.</span>
																		<input type="number" class="form-control month-input" name="mon_06" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">ก.ค.</span>
																		<input type="number" class="form-control month-input" name="mon_07" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">ส.ค.</span>
																		<input type="number" class="form-control month-input" name="mon_08" step="0.01" min="0">
																	</div>
																	<div class="month-box">
																		<span class="month-label">ก.ย.</span>
																		<input type="number" class="form-control month-input" name="mon_09" step="0.01" min="0">
																	</div>
																</div>
															</div>
														</div>
													</div>
													<div class="row col-md-12 mt-3">
														<div class="col-md-4">
															<label for="summernote-reson" class="font-weight-bold">ค่าน้ำหนักของตัวชี้วัด <span class="text-danger">*</span></label><br>
															<input type="number" name="kpi_weight" id="kpi_weight" class="form-control" placeholder="* กรอก 0-100 " value="<?= htmlspecialchars($pre_project_data['kpi_weight']); ?>" required>
														</div>
													</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="insert">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>

						<!-- ตัวชี้วัดผลผลิตโครงการ   -->
						<!-- กิจกรรม/ขั้นตอนการดำเนินงาน   -->
						<?php
						$project_activity_list = getProjectActivityList($db_Project, $project_id);
						?>
						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($project_activity_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								กิจกรรม/ขั้นตอนการดำเนินงาน


							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<div class="table-responsive">
								<table class="table table-bordered table-striped text-center align-middle">
									<thead class="bg-light text-dark">
										<tr>
											<th rowspan="2" style="vertical-align: middle;">กิจกรรม</th>

											<th style="vertical-align: middle;">ค่าน้ำหนัก</th>
											<th style="vertical-align: middle;">ตัวชี้วัดกิจกรรม:เป้า

												(ถ้ามี)</th>
											<th colspan="13">เดือนที่วางแผนดำเนินการ</th>
										</tr>
										<tr class="bg-light">
											<th></th>
											<th></th>
											<!-- <th>รวม</th> -->
											<th>ต.ค.</th>
											<th>พ.ย.</th>
											<th>ธ.ค.</th>
											<th>ม.ค.</th>
											<th>ก.พ.</th>
											<th>มี.ค.</th>
											<th>เม.ย.</th>
											<th>พ.ค.</th>
											<th>มิ.ย.</th>
											<th>ก.ค.</th>
											<th>ส.ค.</th>
											<th>ก.ย.</th>
										</tr>
									</thead>
									<tbody>
										<tr class="bg-light">
											<td colspan="17" class="text-left font-weight-bold">
												<a data-toggle="modal" data-target="#modal-add-activity-project<?= $project_id; ?>" class="btn btn-sm btn-primary text-white">
													<i class="fa fa-plus"></i> เพิ่มกิจกรรม
												</a>
											</td>
										</tr>
										<?php
										if (!empty($project_activity_list)) :
											foreach ($project_activity_list as $i => $project_activity) :
												// $activity = getKPI($db_KPI, $project_activity['code_kpi']);
												// $unit = getKPI($db_KPI, $project_kpi['code_unit']);
												$All_month_Target =
													$project_activity['mon_10'] +
													$project_activity['mon_11'] +
													$project_activity['mon_12'] +
													$project_activity['mon_01'] +
													$project_activity['mon_02'] +
													$project_activity['mon_03'] +
													$project_activity['mon_04'] +
													$project_activity['mon_05'] +
													$project_activity['mon_06'] +
													$project_activity['mon_07'] +
													$project_activity['mon_08'] +
													$project_activity['mon_09'];

												if ($project_activity['mon_10'] === '1') {
													$icon_mon_10 = 'check-circle text-success';
												}
												if ($project_activity['mon_11'] === '1') {
													$icon_mon_11 = 'check-circle text-success';
												}
												if ($project_activity['mon_12'] === '1') {
													$icon_mon_12 = 'check-circle text-success';
												}
												if ($project_activity['mon_01'] === '1') {
													$icon_mon_01 = 'check-circle text-success';
												}
												if ($project_activity['mon_02'] === '1') {
													$icon_mon_02 = 'check-circle text-success';
												}
												if ($project_activity['mon_03'] === '1') {
													$icon_mon_03 = 'check-circle text-success';
												}
												if ($project_activity['mon_04'] === '1') {
													$icon_mon_04 = 'check-circle text-success';
												}
												if ($project_activity['mon_05'] === '1') {
													$icon_mon_05 = 'check-circle text-success';
												}

												if ($project_activity['mon_06'] === '1') {
													$icon_mon_06 = 'check-circle text-success';
												}
												if ($project_activity['mon_07'] === '1') {
													$icon_mon_07 = 'check-circle text-success';
												}
												if ($project_activity['mon_08'] === '1') {
													$icon_mon_08 = 'check-circle text-success';
												}
												if ($project_activity['mon_09'] === '1') {
													$icon_mon_09 = 'check-circle text-success';
												}



										?>
												<tr>
													<td class="text-left"><?= htmlspecialchars($project_activity['activity_no']); ?> . <?= htmlspecialchars($project_activity['activity_name']); ?>
														<!-- 🔹 ปุ่มแก้ไข -->
														<button type="button"
															class="btn btn-link p-0 icon-btn edit"
															title="แก้ไขข้อมูล"
															data-target="#modal-edit-activity-project<?= $project_activity['id']; ?>"
															data-toggle="modal">
															<i class="fa fa-edit fa-lg"></i>
														</button>

														<!-- 🔹 ปุ่มลบ -->
														<button type="button"
															class="btn btn-link p-0 ml-2 icon-btn delete"
															title="ลบข้อมูล"
															data-target="#modal-del-activity-project<?= $project_activity['id']; ?>"
															data-toggle="modal">
															<i class="fa fa-trash fa-lg"></i>
														</button>
														<br><?= htmlspecialchars($project_activity['activity_detail']); ?>
														<br>กลุ่มเป้าหมาย : <a data-toggle="modal" data-target="#modal-add-activity-target-project<?= $project_activity['id']; ?>" class="btn btn-sm btn-green  text-white">
															<i class="fa fa-plus"></i> เพิ่มกลุ่มเป้าหมาย
														</a>
														<?php
														$project_activity_target_list = getProjectActivityTargetList($db_Project, $project_activity['id']);


														if (!empty($project_activity_target_list)) :

															foreach ($project_activity_target_list as $i => $project_activity_target) :
																$target = getSetTarget($db_Project, $project_activity_target['code_target']);


														?>
																<br> <i class="fas fa-circle fa-xs" style="color: #8d70e6;"></i> <?= htmlspecialchars($target['name']); ?> (<?= formatNumber($project_activity_target['target_amount']); ?> )
																<!-- 🔹 ปุ่มแก้ไข -->
																<button type="button"
																	class="btn btn-link p-0 icon-btn edit"
																	title="แก้ไขข้อมูล"
																	data-target="#modal-edit-activity-target-project<?= $project_activity_target['id']; ?>"
																	data-toggle="modal">
																	<i class="fa fa-edit fa-lg"></i>
																</button>

																<!-- 🔹 ปุ่มลบ -->
																<button type="button"
																	class="btn btn-link p-0 ml-2 icon-btn delete"
																	title="ลบข้อมูล"
																	data-target="#modal-del-activity-target-project<?= $project_activity_target['id']; ?>"
																	data-toggle="modal">
																	<i class="fa fa-trash fa-lg"></i>
																</button>
																<!-- with white backdrop -->
																<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-activity-target-project<?= $project_activity_target['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
																	<div class="modal-dialog " role="document">
																		<div class="modal-content bgc-transparent brc-danger-m2 shadow">
																			<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
																				<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
																					WARNING!
																				</h5>

																				<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
																					<span aria-hidden="true" class="text-150">&times;</span>
																				</button>
																			</div>


																			<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
																				<div class="d-flex align-items-top mr-2 mr-md-5">
																					<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
																					<div class="text-secondary-d2 text-105">
																						<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

																					</div>
																				</div>
																			</div>

																			<div class="modal-footer bgc-white-tp2 border-0">
																				<form action="?page=<?= $page; ?>&op=save_data_activity_target_project" method="POST">
																					<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																					<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																					<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																					<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																					<input type="hidden" name="project_activity_target_id" value="<?= $project_activity_target['id']; ?>">
																					<input type="hidden" name="overview" value="<?= $overview; ?>">


																					<input type="hidden" name="type" value="delete">
																					<button class="btn btn-danger" type="submit">ยืนยัน </button>

																				</form>
																			</div>
																		</div>
																	</div>
																</div>
																<!-- ✅ Modal: แก้ไขกลุ่มเป้าหมาย  -->
																<div class="modal fade modal-xl" id="modal-edit-activity-target-project<?= $project_activity_target['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
																	<div class="modal-dialog " role="document">
																		<div class="modal-content">

																			<div class="modal-header">
																				<h5 class="modal-title text-blue" id="exampleModalLabel2">
																					<i class="fas fa-file-alt text-secondary"></i> แก้ไขกลุ่มเป้าหมาย (รายกิจกรรม)
																				</h5>
																				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																					<span aria-hidden="true">&times;</span>
																				</button>
																			</div>

																			<div class="modal-body bg-white">
																				<div class="bgc-white radius-1">
																					<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_activity_target_project" method="POST">

																						<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

																						<div class="form-group">

																							<div class="row col-md-12 mt-3">
																								<div class="col-md-12">
																									<label for="summernote-reson" class="font-weight-bold">ระบุกลุ่มเป้าหมาย (รายกิจกรรม)<span class="text-danger"> ไม่ระบุรายกิจกรรมแต่ไประบุภาพรวมทั้งโครงการก็ได้ </span></label><br>
																									<div class="card border-0  w-100">
																										<div class="card-body p-3">


																											<div id="targetGroupContainer000">
																												<div class="row g-2 align-items-center mb-2 ">
																													<div class="col-md-7 col-12">


																														<?php
																														$target_list = getTargetSelect($db_Project);


																														$id_selected = $project_activity_target['code_target']  ?? null; // รับค่า id ที่ส่งเข้ามา

																														// สร้าง select
																														echo '<select name="target_group" id="target_group" class="form-control">';

																														foreach ($target_list as $target) {

																															$selected = ($id_selected == $target['id']) ? 'selected' : '';
																															echo '<option value="' . htmlspecialchars($target['id']) . '" ' . $selected . '>'
																																. htmlspecialchars($target['name'])
																																. '</option>';
																														}

																														echo '</select>';
																														?>

																													</div>

																													<div class="col-md-3 col-12">
																														<input type="number" class="form-control" name="target_amount" placeholder="กรอกจำนวน" min="0" value="<?= $project_activity_target['target_amount']; ?>">
																													</div>


																												</div>
																											</div>
																										</div>
																									</div>
																								</div>


																							</div>
																						</div>

																						<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																						<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																						<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																						<input type="hidden" name="project_activity_target_id" value="<?= $project_activity_target['id']; ?>">
																						<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																						<input type="hidden" name="overview" value="<?= $overview; ?>">


																						<input type="hidden" name="type" value="update">

																						<div class="text-center mt-4">
																							<button type="submit" class="btn btn-info btn-bold px-4">
																								<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
																							</button>
																							<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
																								<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
																							</button>
																						</div>

																					</form>
																				</div>
																			</div>

																			<div class="modal-footer">
																				<button class="btn btn-sm btn-danger" data-dismiss="modal">
																					<i class="ace-icon fa fa-times"></i> ปิด
																				</button>
																			</div>

																		</div>
																	</div>
																</div>

																<!-- แก้ไขกลุ่มเป้าหมาย   -->
															<?php endforeach; ?>
														<?php endif; ?>
														<!-- with white backdrop -->
														<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-activity-project<?= $project_activity['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
															<div class="modal-dialog " role="document">
																<div class="modal-content bgc-transparent brc-danger-m2 shadow">
																	<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
																		<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
																			WARNING!
																		</h5>

																		<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
																			<span aria-hidden="true" class="text-150">&times;</span>
																		</button>
																	</div>


																	<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
																		<div class="d-flex align-items-top mr-2 mr-md-5">
																			<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
																			<div class="text-secondary-d2 text-105">
																				<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

																			</div>
																		</div>
																	</div>

																	<div class="modal-footer bgc-white-tp2 border-0">
																		<form action="?page=<?= $page; ?>&op=save_data_activity_project" method="POST">
																			<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																			<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																			<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																			<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																			<input type="hidden" name="overview" value="<?= $overview; ?>">


																			<input type="hidden" name="type" value="delete">
																			<button class="btn btn-danger" type="submit">ยืนยัน </button>

																		</form>
																	</div>
																</div>
															</div>
														</div>
													</td>
													<td><?= $project_activity['activity_weight']; ?></td>
													<td class="text-left"><a data-toggle="modal" data-target="#modal-add-activity-kpi-project<?= $project_activity['id']; ?>" class="btn btn-sm btn-green  text-white">
															<i class="fa fa-plus"></i> เพิ่มตัวชี้วัด
														</a>
														<?php
														$project_activity_kpi_list = getProjectActivityKpiList($db_Project, $project_activity['id']);


														if (!empty($project_activity_kpi_list)) :

															foreach ($project_activity_kpi_list as $i => $project_activity_kpi) :



														?>
																<span class="text-left">
																	<br><i class="fas fa-circle fa-xs" style="color: #428ad7;"></i> <?= htmlspecialchars($project_activity_kpi['kpi_name']); ?> (<?= formatNumber($project_activity_kpi['kpi_amount']); ?> )
																	<!-- 🔹 ปุ่มแก้ไข -->
																	<button type="button"
																		class="btn btn-link p-0 icon-btn edit"
																		title="แก้ไขข้อมูล"
																		data-target="#modal-edit-activity-kpi-project<?= $project_activity_kpi['id']; ?>"
																		data-toggle="modal">
																		<i class="fa fa-edit fa-lg"></i>
																	</button>

																	<!-- 🔹 ปุ่มลบ -->
																	<button type="button"
																		class="btn btn-link p-0 ml-2 icon-btn delete"
																		title="ลบข้อมูล"
																		data-target="#modal-del-activity-kpi-project<?= $project_activity_kpi['id']; ?>"
																		data-toggle="modal">
																		<i class="fa fa-trash fa-lg"></i>
																	</button>

																</span>
																<!-- with white backdrop -->
																<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-activity-kpi-project<?= $project_activity_kpi['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
																	<div class="modal-dialog " role="document">
																		<div class="modal-content bgc-transparent brc-danger-m2 shadow">
																			<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
																				<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
																					WARNING!
																				</h5>

																				<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
																					<span aria-hidden="true" class="text-150">&times;</span>
																				</button>
																			</div>


																			<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
																				<div class="d-flex align-items-top mr-2 mr-md-5">
																					<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
																					<div class="text-secondary-d2 text-105">
																						<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

																					</div>
																				</div>
																			</div>

																			<div class="modal-footer bgc-white-tp2 border-0">
																				<form action="?page=<?= $page; ?>&op=save_data_activity_kpi_project" method="POST">
																					<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																					<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																					<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																					<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																					<input type="hidden" name="project_activity_kpi_id" value="<?= $project_activity_kpi['id']; ?>">
																					<input type="hidden" name="overview" value="<?= $overview; ?>">


																					<input type="hidden" name="type" value="delete">
																					<button class="btn btn-danger" type="submit">ยืนยัน </button>

																				</form>
																			</div>
																		</div>
																	</div>
																</div>
																<!-- ✅ Modal: แก้ไขตัวชี้วัดกิจกรรม  -->
																<div class="modal fade modal-xl" id="modal-edit-activity-kpi-project<?= $project_activity_kpi['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
																	<div class="modal-dialog " role="document">
																		<div class="modal-content">

																			<div class="modal-header">
																				<h5 class="modal-title text-blue" id="exampleModalLabel2">
																					<i class="fas fa-file-alt text-secondary"></i> แก้ไขตัวชี้วัดกิจกรรม
																				</h5>
																				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																					<span aria-hidden="true">&times;</span>
																				</button>
																			</div>

																			<div class="modal-body bg-white">
																				<div class="bgc-white radius-1">
																					<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_activity_kpi_project" method="POST">

																						<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

																						<div class="form-group">

																							<div class="row col-md-12 mt-3">
																								<div class="col-md-12">
																									<label for="summernote-reson" class="font-weight-bold">แก้ไขตัวชี้วัดกิจกรรม <span class="text-danger"> ไม่ระบุก็ได้ </span></label><br>
																									<div class="card border-0  w-100">
																										<div class="card-body p-3">


																											<div id="kpiGroupContainer000">
																												<div class="row g-2 align-items-center mb-2 ">

																													<div class="col-md-6 col-12">
																														<input type="text" class="form-control" name="kpi_name" placeholder="กรอกตัวชี้วัด" value="<?= $project_activity_kpi['kpi_name']; ?>">
																													</div>
																													<div class="col-md-3 col-12">
																														<input type="text" class="form-control" name="kpi_unit" placeholder="กรอกหน่วยนับ" value="<?= $project_activity_kpi['kpi_unit']; ?>">
																													</div>
																													<div class="col-md-3 col-12">
																														<input type="number" class="form-control" name="kpi_amount" placeholder="กรอกจำนวน" min="0" value="<?= $project_activity_kpi['kpi_amount']; ?>">
																													</div>

																												</div>
																											</div>
																										</div>
																									</div>
																								</div>


																							</div>
																						</div>

																						<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																						<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																						<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																						<input type="hidden" name="project_activity_kpi_id" value="<?= $project_activity_kpi['id']; ?>">
																						<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																						<input type="hidden" name="overview" value="<?= $overview; ?>">


																						<input type="hidden" name="type" value="update">

																						<div class="text-center mt-4">
																							<button type="submit" class="btn btn-info btn-bold px-4">
																								<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
																							</button>
																							<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
																								<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
																							</button>
																						</div>

																					</form>
																				</div>
																			</div>

																			<div class="modal-footer">
																				<button class="btn btn-sm btn-danger" data-dismiss="modal">
																					<i class="ace-icon fa fa-times"></i> ปิด
																				</button>
																			</div>

																		</div>
																	</div>
																</div>

																<!-- แก้ไขตัวชี้วัดกิจกรรม  -->

															<?php endforeach; ?>
														<?php endif; ?>


													</td>
													<!-- <td><?= $All_month_Target; ?></td> -->
													<td><i class="fas fa-<?= $icon_mon_10; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_11; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_12; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_01; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_02; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_03; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_04; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_05; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_06; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_07; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_08; ?>"></i></td>
													<td><i class="fas fa-<?= $icon_mon_09; ?>"></i></td>

												</tr>
												<!-- ✅ Modal: เพิ่มตัวชี้วัดกิจกรรม  -->
												<div class="modal fade modal-xl" id="modal-add-activity-kpi-project<?= $project_activity['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
													<div class="modal-dialog " role="document">
														<div class="modal-content">

															<div class="modal-header">
																<h5 class="modal-title text-blue" id="exampleModalLabel2">
																	<i class="fas fa-file-alt text-secondary"></i> เพิ่มตัวชี้วัดกิจกรรม : เป้า (ถ้ามี)
																</h5>
																<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																</button>
															</div>

															<div class="modal-body bg-white">
																<div class="bgc-white radius-1">
																	<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_activity_kpi_project" method="POST">

																		<p class="text-center text-info mb-4"><span class="text-dark">กิจกรรม : </span><?= htmlspecialchars($project_activity['activity_name']); ?></p>

																		<div class="form-group">

																			<div class="row col-md-12 mt-3">
																				<div class="col-md-12">
																					<label for="summernote-reson" class="font-weight-bold">ระบุตัวชี้วัดของกิจกรรมนี้ <span class="text-danger">ไม่ระบุก็ได้</span></label><br>
																					<div class="card border-0  w-100">
																						<div class="card-body p-3">


																							<div id="kpiGroupContainer<?= $project_activity['id']; ?>">
																								<div class="row g-2 align-items-center mb-2 kpi-row<?= $project_activity['id']; ?>">

																									<div class="col-md-5 col-12">
																										<!-- <label for="kpi_name" class="font-weight-bold">กรอกตัวชี้วัด </label> -->
																										<input type="text" class="form-control" name="kpi_name[]" placeholder="กรอกตัวชี้วัด">
																									</div>
																									<div class="col-md-2 col-12">
																										<!-- <label for="kpi_unit" class="font-weight-bold">กรอกหน่วยนับ </label> -->

																										<input type="text" class="form-control" name="kpi_unit[]" placeholder="กรอกหน่วยนับ">
																									</div>
																									<div class="col-md-3 col-12">
																										<!-- <label for="kpi_amount" class="font-weight-bold">กรอกจำนวน </label> -->
																										<input type="number" class="form-control" name="kpi_amount[]" placeholder="กรอกจำนวน" min="0">
																									</div>

																									<div class="col-md-2 col-12 text-center">
																										<button type="button" class="btn btn-outline-primary w-100 addKpiRowBtn<?= $project_activity['id']; ?>">
																											<i class="fa fa-plus"></i> เพิ่ม
																										</button>
																									</div>
																								</div>
																							</div>
																						</div>
																					</div>
																				</div>
																				<script>
																					// ✅ เพิ่ม/ลบแถว
																					document.addEventListener("click", function(e) {
																						if (e.target.closest(".addKpiRowBtn<?= $project_activity['id']; ?>")) {
																							const row = e.target.closest(".kpi-row<?= $project_activity['id']; ?>");
																							const newRow = row.cloneNode(true);
																							// newRow.querySelector("select").value = "";
																							newRow.querySelector("input").value = "";
																							newRow.querySelector("input").value = "";
																							newRow.querySelector("input").value = "";
																							newRow.querySelector(".addKpiRowBtn<?= $project_activity['id']; ?>").outerHTML =
																								`<button type="button" class="btn btn-outline-danger w-100 removeKpiRowBtn<?= $project_activity['id']; ?>">
																							<i class="fa fa-trash"></i> ลบ
																						</button>`;
																							document.getElementById("kpiGroupContainer<?= $project_activity['id']; ?>").appendChild(newRow);
																						}

																						if (e.target.closest(".removeKpiRowBtn<?= $project_activity['id']; ?>")) {
																							e.target.closest(".kpi-row<?= $project_activity['id']; ?>").remove();
																						}
																					});
																				</script>

																			</div>
																		</div>

																		<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																		<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																		<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																		<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">

																		<input type="hidden" name="overview" value="<?= $overview; ?>">


																		<input type="hidden" name="type" value="insert">

																		<div class="text-center mt-4">
																			<button type="submit" class="btn btn-info btn-bold px-4">
																				<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
																			</button>
																			<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
																				<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
																			</button>
																		</div>

																	</form>
																</div>
															</div>

															<div class="modal-footer">
																<button class="btn btn-sm btn-danger" data-dismiss="modal">
																	<i class="ace-icon fa fa-times"></i> ปิด
																</button>
															</div>

														</div>
													</div>
												</div>

												<!-- เพิ่มตัวชี้วัดกิจกรรม   -->
												<!-- ✅ Modal: เพิ่มกลุ่มเป้าหมาย  -->
												<div class="modal fade modal-xl" id="modal-add-activity-target-project<?= $project_activity['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
													<div class="modal-dialog " role="document">
														<div class="modal-content">

															<div class="modal-header">
																<h5 class="modal-title text-blue" id="exampleModalLabel2">
																	<i class="fas fa-file-alt text-secondary"></i> เพิ่มกลุ่มเป้าหมาย (รายกิจกรรม)
																</h5>
																<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																</button>
															</div>

															<div class="modal-body bg-white">
																<div class="bgc-white radius-1">
																	<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_activity_target_project" method="POST">

																		<p class="text-center text-info mb-4"> <span class="text-dark">กิจกรรม : </span><?= htmlspecialchars($project_activity['activity_name']); ?></p>

																		<div class="form-group">

																			<div class="row col-md-12 mt-3">
																				<div class="col-md-12">
																					<label for="summernote-reson" class="font-weight-bold">ระบุกลุ่มเป้าหมาย (รายกิจกรรม)<span class="text-danger"> ไม่ระบุรายกิจกรรมแต่ไประบุภาพรวมทั้งโครงการก็ได้ </span></label><br>
																					<div class="card border-0  w-100">
																						<div class="card-body p-3">


																							<div id="targetGroupContainer<?= $project_activity['id']; ?>">
																								<div class="row g-2 align-items-center mb-2 target-row<?= $project_activity['id']; ?>">
																									<div class="col-md-7 col-12">


																										<?php
																										$target_list = getTargetSelect($db_Project);


																										$id_selected = $project_activity['code_target']  ?? null; // รับค่า id ที่ส่งเข้ามา

																										// สร้าง select
																										echo '<select name="target_group[]" id="target_group[]" class="form-control">';

																										foreach ($target_list as $target) {

																											$selected = ($id_selected == $target['id']) ? 'selected' : '';
																											echo '<option value="' . htmlspecialchars($target['id']) . '" ' . $selected . '>'
																												. htmlspecialchars($target['name'])
																												. '</option>';
																										}

																										echo '</select>';
																										?>

																									</div>

																									<div class="col-md-3 col-12">
																										<input type="number" class="form-control" name="target_amount[]" placeholder="กรอกจำนวน" min="0">
																									</div>

																									<div class="col-md-2 col-12 text-center">
																										<button type="button" class="btn btn-outline-primary w-100 addRowBtn<?= $project_activity['id']; ?>">
																											<i class="fa fa-plus"></i> เพิ่ม
																										</button>
																									</div>
																								</div>
																							</div>
																						</div>
																					</div>
																				</div>
																				<script>
																					// ✅ เพิ่ม/ลบแถว
																					document.addEventListener("click", function(e) {
																						if (e.target.closest(".addRowBtn<?= $project_activity['id']; ?>")) {
																							const row = e.target.closest(".target-row<?= $project_activity['id']; ?>");
																							const newRow = row.cloneNode(true);
																							newRow.querySelector("select").value = "";
																							newRow.querySelector("input").value = "";
																							newRow.querySelector(".addRowBtn<?= $project_activity['id']; ?>").outerHTML =
																								`<button type="button" class="btn btn-outline-danger w-100 removeRowBtn<?= $project_activity['id']; ?>">
																							<i class="fa fa-trash"></i> ลบ
																						</button>`;
																							document.getElementById("targetGroupContainer<?= $project_activity['id']; ?>").appendChild(newRow);
																						}

																						if (e.target.closest(".removeRowBtn<?= $project_activity['id']; ?>")) {
																							e.target.closest(".target-row<?= $project_activity['id']; ?>").remove();
																						}
																					});
																				</script>

																			</div>
																		</div>

																		<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																		<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																		<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																		<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">

																		<input type="hidden" name="overview" value="<?= $overview; ?>">


																		<input type="hidden" name="type" value="insert">

																		<div class="text-center mt-4">
																			<button type="submit" class="btn btn-info btn-bold px-4">
																				<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
																			</button>
																			<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
																				<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
																			</button>
																		</div>

																	</form>
																</div>
															</div>

															<div class="modal-footer">
																<button class="btn btn-sm btn-danger" data-dismiss="modal">
																	<i class="ace-icon fa fa-times"></i> ปิด
																</button>
															</div>

														</div>
													</div>
												</div>

												<!-- เพิ่มกลุ่มเป้าหมาย   -->

												<!-- ✅ Modal: กิจกรรม/ขั้นตอนการดำเนินงาน  -->
												<div class="modal fade modal-xl" id="modal-edit-activity-project<?= $project_activity['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
													<div class="modal-dialog " role="document">
														<div class="modal-content">

															<div class="modal-header">
																<h5 class="modal-title text-blue" id="exampleModalLabel2">
																	<i class="fas fa-file-alt text-secondary"></i> แก้ไขกิจกรรม/ขั้นตอนการดำเนินงาน
																</h5>
																<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																</button>
															</div>

															<div class="modal-body bg-white">
																<div class="bgc-white radius-1">
																	<form id="form-target_project-<?= $project_activity['id']; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_activity_project" method="POST">

																		<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

																		<div class="form-group">


																			<div class="row col-md-12 mt-3">
																				<div class="col-md-2">
																					<label for="summernote-reson" class="font-weight-bold">ลำดับที่ <span class="text-danger">*</span></label><br>
																					<input type="text" name="activity_no" id="activity_no" class="form-control" placeholder="* กรอกลำดับที่ " value="<?= $project_activity['activity_no']; ?>" required>

																				</div>
																				<div class="col-md-10">
																					<label for="summernote-reson" class="font-weight-bold">ชื่อกิจกรรม <span class="text-danger">*</span></label><br>

																					<input type="text" name="activity_name" id="activity_name" class="form-control" placeholder="* กรอกชื่อกิจกรรม " value="<?= $project_activity['activity_name']; ?>" required>
																				</div>
																			</div>
																			<div class="row col-md-12 mt-3">

																				<div class="col-md-12">
																					<label for="summernote-reson" class="font-weight-bold">กรอกรายละเอียดกิจกรรม </label><br>

																					<input type="text" name="activity_detail" id="activity_detail" class="form-control" placeholder="กรอกรายละเอียดกิจกรรม " value="<?= $project_activity['activity_detail']; ?>" required>
																				</div>
																			</div>
																			<div class="row col-md-12 mt-3">

																				<div class="col-md-12">
																					<div class="form-group">
																						<label class="font-weight-bold text-primary">
																							เลือกเดือนที่วางแผนดำเนินกิจกรรม <span class="text-danger">*</span>
																						</label>

																						<div class="row">
																							<?php
																							$monthNames = [
																								'ม.ค.',
																								'ก.พ.',
																								'มี.ค.',
																								'เม.ย.',
																								'พ.ค.',
																								'มิ.ย.',
																								'ก.ค.',
																								'ส.ค.',
																								'ก.ย.',
																								'ต.ค.',
																								'พ.ย.',
																								'ธ.ค.'
																							];
																							for ($i = 1; $i <= 12; $i++) {
																								$col = sprintf('mon_%02d', $i);
																								$checked = (!empty($project_activity[$col]) && $project_activity[$col] == 1) ? 'checked' : '';
																							?>
																								<div class="col-md-2 col-6 mb-2">
																									<input type="hidden" name="<?= $col ?>" value="0">
																									<div class="form-check">
																										<input class="form-check-input" type="checkbox" id="<?= $col ?>" name="<?= $col ?>" value="1" <?= $checked ?>>
																										<label class="form-check-label" for="<?= $col ?>"><?= $monthNames[$i - 1] ?></label>
																									</div>
																								</div>
																							<?php } ?>
																						</div>
																					</div>
																				</div>
																			</div>
																			<div class="row col-md-12 mt-3">
																				<div class="col-md-4">
																					<label for="summernote-reson" class="font-weight-bold">ค่าน้ำหนักของตัวชี้วัด <span class="text-danger">*</span></label><br>
																					<input type="number" name="activity_weight" id="activity_weight" class="form-control" placeholder="* กรอก 0-100 " value="<?= $project_activity['activity_weight']; ?>" required>
																				</div>
																			</div>
																		</div>

																		<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																		<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																		<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																		<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																		<input type="hidden" name="overview" value="<?= $overview; ?>">


																		<input type="hidden" name="type" value="update">

																		<div class="text-center mt-4">
																			<button type="submit" class="btn btn-info btn-bold px-4">
																				<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
																			</button>
																			<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
																				<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
																			</button>
																		</div>

																	</form>
																</div>
															</div>

															<div class="modal-footer">
																<button class="btn btn-sm btn-danger" data-dismiss="modal">
																	<i class="ace-icon fa fa-times"></i> ปิด
																</button>
															</div>

														</div>
													</div>
												</div>



											<?php endforeach; ?>
										<?php else : ?>
											<tr>
												<td colspan="15" style="text-align:center;">ไม่พบข้อมูล</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>




						</div>
						<!-- ✅ Modal: กิจกรรม/ขั้นตอนการดำเนินงาน  -->
						<div class="modal fade modal-xl" id="modal-add-activity-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่มกิจกรรม/ขั้นตอนการดำเนินงาน
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_activity_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">


													<div class="row col-md-12 mt-3">
														<div class="col-md-2">
															<label for="summernote-reson" class="font-weight-bold">ลำดับที่ <span class="text-danger">*</span></label><br>
															<input type="text" name="activity_no" id="activity_no" class="form-control" required>

														</div>
														<div class="col-md-10">
															<label for="summernote-reson" class="font-weight-bold">ชื่อกิจกรรม <span class="text-danger">*</span></label><br>

															<input type="text" name="activity_name" id="activity_name" class="form-control" required>
														</div>
													</div>
													<div class="row col-md-12 mt-3">

														<div class="col-md-12">
															<label for="summernote-reson" class="font-weight-bold">กรอกรายละเอียดกิจกรรม </label><br>

															<input type="text" name="activity_detail" id="activity_detail" class="form-control" required>
														</div>
													</div>
													<div class="row col-md-12 mt-3">
														<div class="col-md-12">
															<div class="form-group">
																<label class="font-weight-bold text-primary">
																	เลือกเดือนที่วางแผนดำเนินกิจกรรม <span class="text-danger">*</span>
																</label>

																<div class="row">
																	<?php
																	$monthNames = [
																		'ม.ค.',
																		'ก.พ.',
																		'มี.ค.',
																		'เม.ย.',
																		'พ.ค.',
																		'มิ.ย.',
																		'ก.ค.',
																		'ส.ค.',
																		'ก.ย.',
																		'ต.ค.',
																		'พ.ย.',
																		'ธ.ค.'
																	];
																	for ($i = 1; $i <= 12; $i++) {
																		$col = sprintf('mon_%02d', $i);
																		$checked = (!empty($project_activity[$col]) && $project_activity[$col] == 1) ? 'checked' : '';
																	?>
																		<div class="col-md-2 col-6 mb-2">
																			<input type="hidden" name="<?= $col ?>" value="0">
																			<div class="form-check">
																				<input class="form-check-input" type="checkbox" id="<?= $col ?>" name="<?= $col ?>" value="1" <?= $checked ?>>
																				<label class="form-check-label" for="<?= $col ?>"><?= $monthNames[$i - 1] ?></label>
																			</div>
																		</div>
																	<?php } ?>
																</div>
															</div>
														</div>
													</div>

													<div class="row col-md-12 mt-3">
														<div class="col-md-4">
															<label for="summernote-reson" class="font-weight-bold">ค่าน้ำหนักของกิจกรรม <span class="text-danger">*</span></label><br>
															<input type="number" name="activity_weight" id="activity_weight" class="form-control" placeholder="* กรอก 0-100 " required>
														</div>
													</div>

												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">

												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="insert">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>

						<!-- กิจกรรม/ขั้นตอนการดำเนินงาน   -->
						<!-- ประมาณการค่าใช้จ่าย   -->
						<?php
						$project_activity_list = getProjectActivityList($db_Project, $project_id);

						$project_activity_budget_ALL = getProjectActivityBudgetALL($db_Project, $project_id);

						$ALL_total_sum = 0;
						foreach ($project_activity_budget_ALL as $ALL_item) {
							$ALL_total_sum += (float)$ALL_item['total']; // สมมติว่าช่องรวมเก็บชื่อ total
						}
						?>


						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($project_activity_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								ประมาณการค่าใช้จ่าย : <span class="text-danger text-110 text-bold"><?= number_format($ALL_total_sum, 2); ?></span> บาท


							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<div class="table-responsive">
								<table class="table table-bordered table-striped text-center align-middle">
									<thead class="bg-light text-dark">
										<tr>
											<th rowspan="2" style="vertical-align: middle;">รายการค่าใช้จ่าย</th>
											<th colspan="2">จำนวนเงิน (บาท)</th>
										</tr>
										<tr class="bg-light">
											<th>รายละเอียด</th>
											<th>รวม</th>
										</tr>
									</thead>
									<tbody>

										<?php if (!empty($project_activity_list)) : ?>
											<?php foreach ($project_activity_list as $project_activity) :

											?>
												<tr class="bg-light">
													<td class="text-left text-primary text-100 text-bold"><?= htmlspecialchars($project_activity['activity_no']); ?> . <?= htmlspecialchars($project_activity['activity_name']); ?>

														<a data-toggle="modal" data-target="#modal-add-budget-expenses-project<?= $project_activity['id']; ?>" class="btn btn-sm btn-primary text-white">
															<i class="fa fa-plus"></i> เพิ่มค่าใช้จ่าย
														</a>


													</td>
													<td></td>
													<td>

														<?php
														$project_activity_budget_list = getProjectActivityBudgetList($db_Project, $project_activity['id']);

														$total_sum = 0;
														foreach ($project_activity_budget_list as $item) {
															$total_sum += (float)$item['total']; // สมมติว่าช่องรวมเก็บชื่อ total
														}
														?>
														<span class="text-primary text-100 text-bold"><?= number_format($total_sum, 2); ?></span>
													</td>

												</tr>
												<?php
												if (!empty($project_activity_budget_list)) :
													foreach ($project_activity_budget_list as $budget_item) :
														$expenses = getSetExpenses($db_Project, $budget_item['code_expenses']);
														$expenses_detail = getSetExpensesDetailByID($db_Project, $budget_item['code_expenses_detail']);
														// echo $budget_item['code_expenses_detail'];
														// echo $expenses_detail['id'];

												?>
														<tr class="bg-white">
															<td class="text-left">

																<i class="fas fa-circle fa-xs" style="color: #6e787eff;"></i> <?= htmlspecialchars($expenses_detail['name']); ?>
																<?php if (!empty($budget_item['detail'])): ?>(<?= htmlspecialchars($budget_item['detail']); ?> )<?php endif; ?>

																<!-- 🔹 ปุ่มแก้ไข -->
																<button type="button"
																	class="btn btn-link p-0 icon-btn edit"
																	title="แก้ไขข้อมูล"
																	data-target="#modal-edit-budget-expenses-project<?= $budget_item['id']; ?>"
																	data-toggle="modal">
																	<i class="fa fa-edit fa-lg"></i>
																</button>

																<!-- 🔹 ปุ่มลบ -->
																<button type="button"
																	class="btn btn-link p-0 ml-2 icon-btn delete"
																	title="ลบข้อมูล"
																	data-target="#modal-del-budget-expenses-project<?= $budget_item['id']; ?>"
																	data-toggle="modal">
																	<i class="fa fa-trash fa-lg"></i>
																</button>
															</td>
															<td>
																<?php if (!empty($budget_item['price'])) { ?>
																	<?= formatNumberClean($budget_item['price']); ?> บาท x <?= formatNumberClean($budget_item['qty1']); ?> <?= $budget_item['unit1']; ?> x <?= formatNumberClean($budget_item['qty2']); ?> <?= $budget_item['unit2']; ?>
																<?php } ?></td>
															<td><?php if (!empty($budget_item['price'])) { ?>
																	<?= number_format($budget_item['total'], 2); ?>
																<?php } ?></td>
														</tr>
														<!-- with white backdrop -->
														<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-budget-expenses-project<?= $budget_item['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
															<div class="modal-dialog " role="document">
																<div class="modal-content bgc-transparent brc-danger-m2 shadow">
																	<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
																		<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
																			WARNING!
																		</h5>

																		<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
																			<span aria-hidden="true" class="text-150">&times;</span>
																		</button>
																	</div>


																	<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
																		<div class="d-flex align-items-top mr-2 mr-md-5">
																			<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
																			<div class="text-secondary-d2 text-105">
																				<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

																			</div>
																		</div>
																	</div>

																	<div class="modal-footer bgc-white-tp2 border-0">
																		<form action="?page=<?= $page; ?>&op=save_data_activity_budget_project" method="POST">
																			<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																			<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																			<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																			<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																			<input type="hidden" name="budget_item" value="<?= $budget_item['id']; ?>">
																			<input type="hidden" name="overview" value="<?= $overview; ?>">


																			<input type="hidden" name="type" value="delete">
																			<button class="btn btn-danger" type="submit">ยืนยัน </button>

																		</form>
																	</div>
																</div>
															</div>
														</div>
														<!-- ✅ Modal: แก้ไขประมาณค่าใช้จ่าย -->
														<div class="modal fade modal-xl" id="modal-edit-budget-expenses-project<?= $budget_item['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
															<div class="modal-dialog modal-dialog-centered" role="document">
																<div class="modal-content">

																	<div class="modal-header">
																		<h5 class="modal-title text-blue">
																			<i class="fas fa-edit text-success"></i> แก้ไขประมาณค่าใช้จ่าย
																		</h5>
																		<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
																	</div>

																	<div class="modal-body bg-white">
																		<form method="POST" action="?page=<?= $page; ?>&op=save_data_activity_budget_project">

																			<p class="text-center text-info mb-4">
																				<span class="text-dark">กิจกรรม:</span> <?= htmlspecialchars($project_activity['activity_name']); ?>
																			</p>

																			<!-- 🟦 แถว 1: ลำดับ, หมวด, ประเภท -->
																			<div class="row mb-3">
																				<div class="col-md-2">
																					<label class="font-weight-bold">ลำดับที่ <span class="text-danger">*</span></label>
																					<input type="number" name="budget_no" value="<?= htmlspecialchars($budget_item['budget_no']); ?>" class="form-control" required>
																				</div>
																				<div class="col-md-5">
																					<label class="font-weight-bold">หมวดค่าใช้จ่าย <span class="text-danger">*</span></label>
																					<select id="edit_code_expenses<?= $budget_item['id']; ?>" name="code_expenses" class="form-control" required>
																						<option value="">-- เลือกหมวดค่าใช้จ่าย --</option>
																						<?php
																						$sql = "SELECT * FROM set_project_expenses ORDER BY id ASC";
																						$res = mysqli_query($db_Project, $sql);
																						while ($row = mysqli_fetch_assoc($res)) {
																							$selected = ($budget_item['code_expenses'] == $row['id']) ? 'selected' : '';
																							echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
																						}
																						?>
																					</select>
																				</div>
																				<div class="col-md-5">
																					<label class="font-weight-bold">ประเภทค่าใช้จ่าย <span class="text-danger">*</span></label>
																					<select id="edit_code_expenses_detail<?= $budget_item['id']; ?>" name="code_expenses_detail" class="form-control" required>
																						<?php
																						$sub_sql = "SELECT * FROM set_project_expenses_detail WHERE code_expenses = '{$budget_item['code_expenses']}' ORDER BY id ASC";
																						$sub_res = mysqli_query($db_Project, $sub_sql);
																						while ($sub = mysqli_fetch_assoc($sub_res)) {
																							$selected = ($budget_item['code_expenses_detail'] == $sub['id']) ? 'selected' : '';
																							echo "<option value='{$sub['id']}' $selected>{$sub['name']}</option>";
																						}
																						?>
																					</select>
																				</div>
																			</div>

																			<!-- 🟦 แถว 2: คำอธิบาย -->
																			<div class="form-group mb-3">
																				<label class="font-weight-bold">คำอธิบายเพิ่มเติม</label>
																				<textarea name="detail" class="form-control" rows="3"><?= htmlspecialchars($budget_item['detail']); ?></textarea>
																			</div>

																			<!-- 🟦 แถว 3: กล่องคำนวณจำนวนเงิน -->
																			<label class="font-weight-bold mb-2">จำนวนเงิน (บาท) <span class="text-danger">*</span></label>
																			<div id="edit-expense-condition<?= $budget_item['id']; ?>">
																				<?php if (!empty($expenses_detail['detail']) || !empty($expenses_detail['attach_file'])): ?>
																					<div class="alert alert-warning mt-3">
																						<i class="fas fa-info-circle"></i> <b>เงื่อนไข / หมายเหตุ:</b><br>
																						<?= htmlspecialchars((string)($expenses_detail['detail'] ?? '')) ?>
																						<?php
																							$edAttach = trim((string)($expenses_detail['attach_file'] ?? ''));
																							$edAttachUrl = ($edAttach !== '' && function_exists('pmExpenseDetailAttachUrl')) ? pmExpenseDetailAttachUrl($edAttach) : '';
																							$edAttachName = trim((string)($expenses_detail['attach_name'] ?? ''));
																							if ($edAttachName === '') $edAttachName = $edAttach;
																						?>
																						<?php if ($edAttachUrl !== ''): ?>
																							<div class="mt-2">
																								<a href="<?= htmlspecialchars($edAttachUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
																									<i class="fa fa-paperclip"></i> เอกสารแนบ: <?= htmlspecialchars($edAttachName, ENT_QUOTES, 'UTF-8') ?>
																								</a>
																							</div>
																						<?php endif; ?>
																					</div>
																				<?php endif; ?>
																			</div>

																			<div class="border rounded p-3 bg-light mb-3">
																				<div class="row g-2 align-items-center">
																					<div class="col-md-2">
																						<label class="small text-muted mb-1">จำนวนเงิน (บาท/หน่วย)</label>
																						<input type="number" step="0.01" id="edit_price<?= $budget_item['id']; ?>" name="price" value="<?= htmlspecialchars($budget_item['price']); ?>" class="form-control text-right">
																					</div>
																					<div class="col-md-2">
																						<label class="small text-muted mb-1">จำนวน 1</label>
																						<input type="number" step="0.01" id="edit_qty1<?= $budget_item['id']; ?>" name="qty1" value="<?= htmlspecialchars($budget_item['qty1']); ?>" class="form-control text-right">
																					</div>
																					<div class="col-md-2">
																						<label class="small text-muted mb-1">หน่วย 1</label>
																						<input type="text" id="edit_unit1<?= $budget_item['id']; ?>" name="unit1" value="<?= htmlspecialchars($budget_item['unit1']); ?>" class="form-control text-center">
																					</div>
																					<div class="col-md-2">
																						<label class="small text-muted mb-1">จำนวน 2</label>
																						<input type="number" step="0.01" id="edit_qty2<?= $budget_item['id']; ?>" name="qty2" value="<?= htmlspecialchars($budget_item['qty2']); ?>" class="form-control text-right">
																					</div>
																					<div class="col-md-2">
																						<label class="small text-muted mb-1">หน่วย 2</label>
																						<input type="text" id="edit_unit2<?= $budget_item['id']; ?>" name="unit2" value="<?= htmlspecialchars($budget_item['unit2']); ?>" class="form-control text-center">
																					</div>
																					<div class="col-md-2">
																						<label class="small text-muted mb-1">รวมเป็นเงิน (บาท)</label>
																						<!-- ✅ ช่องที่แสดงผลมี comma -->
																						<input type="text" id="edit_totalDisplay<?= $budget_item['id']; ?>" class="form-control text-right bg-white" readonly value="<?= number_format($budget_item['total'], 2); ?>">

																						<!-- ✅ ช่องเก็บค่าจริง -->
																						<input type="hidden" id="edit_total<?= $budget_item['id']; ?>" name="total" value="<?= htmlspecialchars($budget_item['total']); ?>">
																						<!-- <input type="text" id="edit_total<?= $budget_item['id']; ?>" name="total" class="form-control text-right bg-white" readonly value="<?= number_format($budget_item['total'], 2); ?>"> -->
																					</div>
																				</div>
																			</div>

																			<!-- <p class="text-center text-muted">ถ้าไม่ทราบตัวคูณ ให้ใส่ช่องสุดท้ายช่องเดียว</p> -->

																			<script>
																				$(document).ready(function() {
																					function calcEditTotal<?= $budget_item['id']; ?>() {
																						let p = parseFloat($('#edit_price<?= $budget_item['id']; ?>').val()) || 0;
																						let q1 = parseFloat($('#edit_qty1<?= $budget_item['id']; ?>').val()) || 1;
																						let q2 = parseFloat($('#edit_qty2<?= $budget_item['id']; ?>').val()) || 1;
																						let total = p * q1 * q2;

																						// $('#edit_total<?= $budget_item['id']; ?>').val(total.toLocaleString('th-TH', {
																						// 	minimumFractionDigits: 2,
																						// 	maximumFractionDigits: 2
																						// }));
																						// ✅ แสดงผลแบบมี comma
																						$('#edit_totalDisplay<?= $budget_item['id']; ?>').val(
																							total.toLocaleString('th-TH', {
																								minimumFractionDigits: 2,
																								maximumFractionDigits: 2
																							})
																						);

																						// ✅ เก็บค่าจริง (ไม่มี comma)
																						$('#edit_total<?= $budget_item['id']; ?>').val(total.toFixed(2));
																					}
																					$('#edit_price<?= $budget_item['id']; ?>, #edit_qty1<?= $budget_item['id']; ?>, #edit_qty2<?= $budget_item['id']; ?>')
																						.on('input', calcEditTotal<?= $budget_item['id']; ?>);

																					// ดึงเงื่อนไขเมื่อเลือกประเภทใหม่
																					$('#edit_code_expenses_detail<?= $budget_item['id']; ?>').change(function() {
																						let detailID = $(this).val();
																						let $infoBox = $('#edit-expense-condition<?= $budget_item['id']; ?>');
																						if (detailID) {
																							$.ajax({
																								url: 'get_expense_condition.php',
																								type: 'POST',
																								data: {
																									id: detailID
																								},
																								dataType: 'json',
																								success: function(res) {
																									if (res && res.detail) {
																										var attach = '';
																										if (res.attach_url) {
																											var an = $('<div>').text(res.attach_name || 'ดาวน์โหลด').html();
																											var au = String(res.attach_url || '').replace(/"/g, '&quot;');
																											attach = '<div class="mt-2"><a href="'+au+'" target="_blank" rel="noopener"><i class="fa fa-paperclip"></i> เอกสารแนบ: '+an+'</a></div>';
																										}
																										$infoBox.html(`
																										<div class="alert alert-warning mt-3">
																										<i class="fas fa-info-circle"></i>
																										<b>เงื่อนไข / หมายเหตุ:</b><br>${$('<div>').text(res.detail).html()}
																										${attach}
																										</div>`);
																									} else {
																										$infoBox.html('<div class="alert alert-warning mt-3">ไม่พบข้อมูลเงื่อนไข</div>');
																									}
																								},
																								error: function() {
																									$infoBox.html('<div class="alert alert-danger mt-3">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>');
																								}
																							});
																						} else {
																							$infoBox.html('');
																						}
																					});
																				});
																			</script>

																			<input type="hidden" name="budget_item" value="<?= $budget_item['id']; ?>">
																			<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																			<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																			<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																			<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																			<input type="hidden" name="overview" value="<?= $overview; ?>">

																			<input type="hidden" name="type" value="update">

																			<div class="text-center mt-4">
																				<button type="submit" class="btn btn-success px-4">
																					<i class="fa fa-save mr-1"></i> บันทึกการแก้ไข
																				</button>
																				<button type="button" class="btn btn-outline-secondary ml-2 px-4" data-dismiss="modal">
																					<i class="fa fa-times mr-1"></i> ปิด
																				</button>
																			</div>

																		</form>
																	</div>
																</div>
															</div>
														</div>
													<?php
													endforeach;
													?>
												<?php endif; ?>

												<!-- ✅ Modal: ประมาณค่าใช้จ่าย -->
												<div class="modal fade modal-xl" id="modal-add-budget-expenses-project<?= $project_activity['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
													<div class="modal-dialog " role="document">
														<div class="modal-content">

															<div class="modal-header">
																<h5 class="modal-title text-blue">
																	<i class="fas fa-file-alt text-secondary"></i>
																	เพิ่มประมาณค่าใช้จ่าย
																</h5>
																<button type="button" class="close" data-dismiss="modal">
																	<span>&times;</span>
																</button>
															</div>

															<div class="modal-body bg-white">
																<form method="POST" action="?page=<?= $page; ?>&op=save_data_activity_budget_project">

																	<p class="text-center text-info mb-4">
																		<span class="text-dark">กิจกรรม:</span> <?= htmlspecialchars($project_activity['activity_name']); ?>
																	</p>

																	<!-- 🟦 แถว 1: ลำดับ, หมวด, ประเภท -->
																	<div class="row mb-3">
																		<div class="col-md-2">
																			<label class="font-weight-bold">ลำดับที่ <span class="text-danger">*</span></label>
																			<input type="number" name="budget_no" class="form-control" required>
																		</div>
																		<div class="col-md-5">
																			<label class="font-weight-bold">หมวดค่าใช้จ่าย <span class="text-danger">*</span></label>
																			<select id="code_expenses<?= $project_activity['id']; ?>" name="code_expenses" class="form-control" required>
																				<option value="">-- เลือกหมวดค่าใช้จ่าย --</option>
																				<?php
																				$sql = "SELECT * FROM set_project_expenses ORDER BY id ASC";
																				$res = mysqli_query($db_Project, $sql);
																				while ($row = mysqli_fetch_assoc($res)) {
																					echo "<option value='{$row['id']}'>{$row['name']}</option>";
																				}
																				?>
																			</select>
																		</div>
																		<div class="col-md-5">
																			<label class="font-weight-bold">ประเภทค่าใช้จ่าย <span class="text-danger">*</span></label>
																			<select id="code_expenses_detail<?= $project_activity['id']; ?>" name="code_expenses_detail" class="form-control" required>
																				<option value="">-- เลือกประเภทค่าใช้จ่าย --</option>
																			</select>
																		</div>
																	</div>

																	<!-- 🟦 แถว 2: คำอธิบาย -->
																	<div class="form-group mb-3">
																		<label class="font-weight-bold">คำอธิบายเพิ่มเติม</label>
																		<textarea name="detail" class="form-control" rows="3" placeholder="กรอกคำอธิบายเพิ่มเติม (ถ้ามี)"></textarea>
																	</div>

																	<!-- 🟦 แถว 3: กล่องคำนวณจำนวนเงิน -->
																	<label class="font-weight-bold mb-2">จำนวนเงิน (บาท) <span class="text-danger">*</span>

																	</label>
																	<div id="expense-condition<?= $project_activity['id']; ?>"></div>

																	<div class="border rounded p-3 bg-light mb-3">
																		<div class="row g-2 align-items-center">
																			<div class="col-md-2">
																				<label class="small text-muted mb-1">จำนวนเงิน (บาท/หน่วย)</label>
																				<input type="number" step="0.01" id="price<?= $project_activity['id']; ?>" name="price" class="form-control text-right" placeholder="0">
																			</div>
																			<div class="col-md-2">
																				<label class="small text-muted mb-1">จำนวน 1</label>
																				<input type="number" step="0.01" id="qty1<?= $project_activity['id']; ?>" name="qty1" class="form-control text-right" placeholder="0">
																			</div>
																			<div class="col-md-2">
																				<label class="small text-muted mb-1">หน่วย 1</label>
																				<input type="text" id="unit1<?= $project_activity['id']; ?>" name="unit1" class="form-control text-center" placeholder="หน่วย1">
																			</div>
																			<div class="col-md-2">
																				<label class="small text-muted mb-1">จำนวน 2</label>
																				<input type="number" step="0.01" id="qty2<?= $project_activity['id']; ?>" name="qty2" class="form-control text-right" placeholder="0">
																			</div>
																			<div class="col-md-2">
																				<label class="small text-muted mb-1">หน่วย 2</label>
																				<input type="text" id="unit2<?= $project_activity['id']; ?>" name="unit2" class="form-control text-center" placeholder="หน่วย2">
																			</div>
																			<div class="col-md-2">
																				<label class="small text-muted mb-1">รวมเป็นเงิน (บาท)</label>
																				<!-- ✅ ช่องที่แสดงผลมี comma -->
																				<input type="text" id="totalDisplay<?= $project_activity['id']; ?>" class="form-control text-right bg-white" readonly>
																				<!-- ✅ ช่องเก็บค่าจริง (ไม่มี comma) -->
																				<input type="hidden" name="total" id="total<?= $project_activity['id']; ?>">
																				<!-- <input type="text" id="total<?= $project_activity['id']; ?>" name="total" class="form-control text-right bg-white" readonly> -->
																			</div>
																		</div>
																	</div>

																	<!-- <p class="text-center text-muted">ถ้าไม่ทราบตัวคูณ ให้ใส่ช่องสุดท้ายช่องเดียว</p> -->


																	<script>
																		$(document).ready(function() {
																			function calcTotal<?= $project_activity['id']; ?>() {
																				let p = parseFloat($('#price<?= $project_activity['id']; ?>').val()) || 0;
																				let q1 = parseFloat($('#qty1<?= $project_activity['id']; ?>').val()) || 1;
																				let q2 = parseFloat($('#qty2<?= $project_activity['id']; ?>').val()) || 1;

																				// ถ้าทั้ง qty1 และ qty2 เป็น 0 ให้ถือว่าคูณแค่ราคา
																				if (q1 === 0 && q2 === 0) {
																					q1 = 1;
																				}

																				let total = p * q1 * q2;

																				// ✅ แสดงผลแบบมี comma
																				$('#totalDisplay<?= $project_activity['id']; ?>').val(
																					total.toLocaleString('th-TH', {
																						minimumFractionDigits: 2,
																						maximumFractionDigits: 2
																					})
																				);

																				// ✅ เก็บค่าจริง (ไม่มี comma)
																				$('#total<?= $project_activity['id']; ?>').val(total.toFixed(2));
																			}

																			// ผูก event กับ input ทุกช่องที่เกี่ยวข้อง
																			$('#price<?= $project_activity['id']; ?>, #qty1<?= $project_activity['id']; ?>, #qty2<?= $project_activity['id']; ?>')
																				.on('input', calcTotal<?= $project_activity['id']; ?>);
																		});
																	</script>

																	<!-- Hidden -->

																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																	<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																	<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																	<input type="hidden" name="overview" value="<?= $overview; ?>">

																	<input type="hidden" name="type" value="insert">

																	<!-- 🟢 ปุ่ม -->
																	<div class="text-center mt-4">
																		<button type="submit" class="btn btn-info px-4">
																			<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
																		</button>
																		<button type="button" class="btn btn-outline-secondary ml-2 px-4" data-dismiss="modal">
																			<i class="fa fa-times mr-1"></i> ปิด
																		</button>
																	</div>
																</form>
															</div>
														</div>
													</div>
												</div>
												<script>
													$(document).ready(function() {
														// เมื่อเปลี่ยนหมวด
														$('#code_expenses<?= $project_activity['id']; ?>').change(function() {
															let categoryCode = $(this).val();
															let $detailSelect = $('#code_expenses_detail<?= $project_activity['id']; ?>');

															$detailSelect.html('<option value="">กำลังโหลด...</option>');

															if (categoryCode) {
																$.ajax({
																	url: 'get_expense_detail.php',
																	type: 'POST',
																	data: {
																		code_expenses: categoryCode
																	},
																	dataType: 'json',
																	success: function(data) {
																		$detailSelect.empty().append('<option value="">-- เลือกประเภทค่าใช้จ่าย --</option>');
																		if (data.length > 0) {
																			$.each(data, function(i, item) {
																				$detailSelect.append('<option value="' + item.id + '">' + item.name + '</option>');
																			});
																		} else {
																			$detailSelect.append('<option value="">ไม่พบข้อมูล</option>');
																		}
																	},
																	error: function() {
																		$detailSelect.html('<option value="">เกิดข้อผิดพลาดในการโหลดข้อมูล</option>');
																	}
																});
															} else {
																$detailSelect.html('<option value="">-- เลือกประเภทค่าใช้จ่าย --</option>');
															}
														});

														// เมื่อเลือกประเภทค่าใช้จ่าย
														$('#code_expenses_detail<?= $project_activity['id']; ?>').change(function() {
															let detailID = $(this).val();
															let $infoBox = $('#expense-condition<?= $project_activity['id']; ?>');

															if (detailID) {
																$.ajax({
																	url: 'get_expense_condition.php',
																	type: 'POST',
																	data: {
																		id: detailID
																	},
																	dataType: 'json',
																	success: function(res) {
																		if (res && res.detail) {
																			var attach = '';
																			if (res.attach_url) {
																				var an = $('<div>').text(res.attach_name || 'ดาวน์โหลด').html();
																				var au = String(res.attach_url || '').replace(/"/g, '&quot;');
																				attach = '<div class="mt-2"><a href="'+au+'" target="_blank" rel="noopener"><i class="fa fa-paperclip"></i> เอกสารแนบ: '+an+'</a></div>';
																			}
																			$infoBox.html(`
																			<div class="alert alert-warning mt-3">
																				<i class="fas fa-info-circle"></i> <b>เงื่อนไข / หมายเหตุ:</b><br>
																				${$('<div>').text(res.detail).html()}
																				${attach}
																			</div>
																			`);
																		} else {
																			$infoBox.html('<div class="alert alert-warning mt-3">ไม่พบข้อมูลเงื่อนไข</div>');
																		}
																	},
																	error: function() {
																		$infoBox.html('<div class="alert alert-danger mt-3">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>');
																	}
																});
															} else {
																$infoBox.html('');
															}
														});
													});
												</script>


											<?php endforeach; ?>


										<?php endif; ?>

									</tbody>
								</table>
							</div>
						</div>


						<!-- ประมาณค่าใช้จ่าย -->

						<!-- แผนการใช้จ่ายรายเดือน -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($project_activity_list)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								แผนการใช้จ่ายรายเดือน


							</h4>
						</div>
						<div class="card-body px-4 py-3 bg-white">


							<div class="table-responsive">
								<table class="table table-bordered table-striped text-center align-middle">
									<thead class="bg-light text-dark">
										<tr>
											<th style="vertical-align: middle;">กิจกรรม</th>

											<th style="vertical-align: middle;">ประมาณค่าใช้จ่าย</th>

											<th colspan="13">เดือนที่วางแผนดำเนินการ</th>
										</tr>
										<tr class="bg-light">
											<th></th>
											<th></th>
											<th>รวม</th>
											<th>ต.ค.</th>
											<th>พ.ย.</th>
											<th>ธ.ค.</th>
											<th>ม.ค.</th>
											<th>ก.พ.</th>
											<th>มี.ค.</th>
											<th>เม.ย.</th>
											<th>พ.ค.</th>
											<th>มิ.ย.</th>
											<th>ก.ค.</th>
											<th>ส.ค.</th>
											<th>ก.ย.</th>
										</tr>
									</thead>
									<tbody>

										<?php
										if (!empty($project_activity_list)) :
											foreach ($project_activity_list as $i => $project_activity) :
												// $activity = getKPI($db_KPI, $project_activity['code_kpi']);
												// $unit = getKPI($db_KPI, $project_kpi['code_unit']);

												$project_activity_budget_list = getProjectActivityBudgetList($db_Project, $project_activity['id']);
												$total_month =
													$project_activity_budget_list['mon_10'] +
													$project_activity_budget_list['mon_11'] +
													$project_activity_budget_list['mon_12'] +
													$project_activity_budget_list['mon_01'] +
													$project_activity_budget_list['mon_02'] +
													$project_activity_budget_list['mon_03'] +
													$project_activity_budget_list['mon_04'] +
													$project_activity_budget_list['mon_05'] +
													$project_activity_budget_list['mon_06'] +
													$project_activity_budget_list['mon_07'] +
													$project_activity_budget_list['mon_08'] +
													$project_activity_budget_list['mon_09'];




												$total_sum = 0;
												foreach ($project_activity_budget_list as $item) {
													$total_sum += (float)$item['total']; // สมมติว่าช่องรวมเก็บชื่อ total
												}
												//echo $project_activity['id'];
												$project_activity_budget_detail = getProjectActivityBudgetDetail($db_Project, $project_activity['id']);



										?><?= $project_activity_budget_detail['total']; ?>
										<tr>
											<td class="text-left text-primary text-bold"><?= htmlspecialchars($project_activity['activity_no']); ?> . <?= htmlspecialchars($project_activity['activity_name']); ?>



											</td>
											<td> <span class="text-success text-bold"><?= number_format($total_sum, 2); ?></span>
												<!-- 🔹 ปุ่มแก้ไข -->
												<button type="button"
													class="btn btn-link p-0 icon-btn edit"
													title="แก้ไขข้อมูล"
													data-target="#modal-edit-activity-budget-mon-project<?= $project_activity['id']; ?>"
													data-toggle="modal">
													<i class="fa fa-edit fa-lg"></i>
												</button>
											</td>
											<td class="text-primary"><?= formatAmount($project_activity_budget_detail['total'], 2); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_10']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_11']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_12']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_01']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_02']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_03']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_04']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_05']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_06']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_07']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_08']); ?></td>
											<td class="text-green"><?= formatAmount($project_activity_budget_detail['mon_09']); ?></td>
										</tr>

										<!-- ✅ Modal: แผนการใช้จ่ายรายเดือน  -->
										<div class="modal fade modal-xl" id="modal-edit-activity-budget-mon-project<?= $project_activity['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
											<div class="modal-dialog " role="document">
												<div class="modal-content">

													<div class="modal-header">
														<h5 class="modal-title text-blue" id="exampleModalLabel2">
															<i class="fas fa-file-alt text-secondary"></i> แก้ไขแผนการใช้จ่ายรายเดือน
														</h5>
														<button type="button" class="close" data-dismiss="modal" aria-label="Close">
															<span aria-hidden="true">&times;</span>
														</button>
													</div>

													<div class="modal-body bg-white">
														<div class="bgc-white radius-1">
															<form id="form-target_project-<?= $project_activity['id']; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_budget_detail_project" method="POST">

																<p class="text-center text-info mb-4">
																	<span class="text-dark">กิจกรรม:</span> <?= htmlspecialchars($project_activity['activity_name']); ?>
																</p>

																<div class="form-group">
																	<div class="row col-md-12 mt-3">
																		<div class="col-md-12">


																			<label class="form-label fw-bold">เลือกเดือนที่มีแผนการใช้จ่ายของกิจกรรม <span class="text-danger">*</span></label>

																			<div class="row col-md-12 mt-3">
																				<div class="col-md-12">
																					<div class="mon-wrapper">
																						<div class="mon-box">
																							<span class="mon-label">ต.ค.</span>
																							<input type="number" class="form-control mon-input" name="mon_10" value="<?= $project_activity_budget_detail['mon_10']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">พ.ย.</span>
																							<input type="number" class="form-control mon-input" name="mon_11" value="<?= $project_activity_budget_detail['mon_11']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">ธ.ค.</span>
																							<input type="number" class="form-control mon-input" name="mon_12" value="<?= $project_activity_budget_detail['mon_12']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">ม.ค.</span>
																							<input type="number" class="form-control mon-input" name="mon_01" value="<?= $project_activity_budget_detail['mon_01']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">ก.พ.</span>
																							<input type="number" class="form-control mon-input" name="mon_02" value="<?= $project_activity_budget_detail['mon_02']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">มี.ค.</span>
																							<input type="number" class="form-control mon-input" name="mon_03" value="<?= $project_activity_budget_detail['mon_03']; ?>" step="0.01" min="0">
																						</div>
																					</div>
																				</div>
																			</div>
																			<div class="row col-md-12 mt-3">
																				<div class="col-md-12">
																					<div class="mon-wrapper">
																						<div class="mon-box">
																							<span class="mon-label">เม.ย.</span>
																							<input type="number" class="form-control mon-input" name="mon_04" value="<?= $project_activity_budget_detail['mon_04']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">พ.ค.</span>
																							<input type="number" class="form-control mon-input" name="mon_05" value="<?= $project_activity_budget_detail['mon_05']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">มิ.ย.</span>
																							<input type="number" class="form-control mon-input" name="mon_06" value="<?= $project_activity_budget_detail['mon_06']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">ก.ค.</span>
																							<input type="number" class="form-control mon-input" name="mon_07" value="<?= $project_activity_budget_detail['mon_07']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">ส.ค.</span>
																							<input type="number" class="form-control mon-input" name="mon_08" value="<?= $project_activity_budget_detail['mon_08']; ?>" step="0.01" min="0">
																						</div>
																						<div class="mon-box">
																							<span class="mon-label">ก.ย.</span>
																							<input type="number" class="form-control mon-input" name="mon_09" value="<?= $project_activity_budget_detail['mon_09']; ?>" step="0.01" min="0">
																						</div>
																					</div>
																				</div>
																			</div>


																		</div>
																	</div>

																</div>

																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																<input type="hidden" name="project_id" value="<?= $project_id; ?>">
																<input type="hidden" name="project_activity_id" value="<?= $project_activity['id']; ?>">
																<input type="hidden" name="project_budget_detail_id" value="<?= $project_activity_budget_detail['id']; ?>">
																<input type="hidden" name="overview" value="<?= $overview; ?>">


																<input type="hidden" name="type" value="update">

																<div class="text-center mt-4">
																	<button type="submit" class="btn btn-info btn-bold px-4">
																		<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
																	</button>
																	<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
																		<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
																	</button>
																</div>

															</form>
														</div>
													</div>

													<div class="modal-footer">
														<button class="btn btn-sm btn-danger" data-dismiss="modal">
															<i class="ace-icon fa fa-times"></i> ปิด
														</button>
													</div>

												</div>
											</div>
										</div>
									<?php endforeach; ?>


								<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>



						<!-- แผนการใช้จ่ายรายเดือน -->
						<!-- กลุ่มเป้าหมายของโครงการ -->

						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$project_target_groups = getProjectTargetGroupList($db_Project, $project_id);
								if (!empty($project_target_groups)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								กลุ่มเป้าหมายของโครงการ : <span class="text-danger">ก็ไม่ต้องระบุตรงนี้ก็ได้</span>

								<a data-toggle="modal" data-target="#modal-add-target-groups-project<?= $project_id; ?>" class="btn btn-sm btn-primary  text-white">
									<i class="fa fa-plus"></i> เพิ่มกลุ่มเป้าหมาย
								</a>

							</h4>
						</div>


						<div class="card-body px-4 py-3 bg-white">
							<p class="mb-1">
								<?php



								foreach ($project_target_groups as $project_target_groups_item) {
									$target_groups = getSetTarget($db_Project, $project_target_groups_item['code_target']);

									echo '<i class="fas fa-circle fa-xs" style="color: #5b7db9;"></i> ';
									echo htmlspecialchars($target_groups['name']);
									echo ' : ';
									echo number_format($project_target_groups_item['target_amount']);

								?>
									<!-- 🔹 ปุ่มแก้ไข -->
									<button type="button"
										class="btn btn-link p-0 icon-btn edit"
										title="แก้ไขข้อมูล"
										data-target="#modal-edit-target-groups-project<?= $project_target_groups_item['id']; ?>"
										data-toggle="modal">
										<i class="fa fa-edit fa-lg"></i>
									</button>

									<!-- 🔹 ปุ่มลบ -->
									<button type="button"
										class="btn btn-link p-0 ml-2 icon-btn delete"
										title="ลบข้อมูล"
										data-target="#modal-del-target-groups-project<?= $project_target_groups_item['id']; ?>"
										data-toggle="modal">
										<i class="fa fa-trash fa-lg"></i>
									</button>
									<!-- with white backdrop -->
							<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-activity-target-project<?= $project_target_groups_item['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
								<div class="modal-dialog " role="document">
									<div class="modal-content bgc-transparent brc-danger-m2 shadow">
										<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
											<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
												WARNING!
											</h5>

											<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true" class="text-150">&times;</span>
											</button>
										</div>


										<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
											<div class="d-flex align-items-top mr-2 mr-md-5">
												<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
												<div class="text-secondary-d2 text-105">
													<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

												</div>
											</div>
										</div>

										<div class="modal-footer bgc-white-tp2 border-0">
											<form action="?page=<?= $page; ?>&op=save_data_target_groups_project" method="POST">
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="project_activity_id" value="<?= $project_id; ?>">
												<input type="hidden" name="project_target_groups_id" value="<?= $project_target_groups_item['id']; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="delete">
												<button class="btn btn-danger" type="submit">ยืนยัน </button>

											</form>
										</div>
									</div>
								</div>
							</div>
							<!-- ลบ -->

							<!-- ✅ Modal: แก้ไขกลุ่มเป้าหมาย  -->
							<div class="modal fade modal-xl" id="modal-edit-target-groups-project<?= $project_target_groups_item['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-file-alt text-secondary"></i> แก้ไขกลุ่มเป้าหมายโครงการ
											</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_target_groups_project" method="POST">

													<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

													<div class="form-group">

														<div class="row col-md-12 mt-3">
															<div class="col-md-12">
																<label for="summernote-reson" class="font-weight-bold">ระบุกลุ่มเป้าหมาย</label><br>
																<div class="card border-0  w-100">
																	<div class="card-body p-3">


																		<div id="targetGroupContainer000">
																			<div class="row g-2 align-items-center mb-2 ">
																				<div class="col-md-7 col-12">


																					<?php
																					$target_list = getTargetSelect($db_Project);


																					$id_selected = $project_target_groups_item['code_target']  ?? null; // รับค่า id ที่ส่งเข้ามา

																					// สร้าง select
																					echo '<select name="target_groups" id="target_groups" class="form-control">';

																					foreach ($target_list as $target) {

																						$selected = ($id_selected == $target['id']) ? 'selected' : '';
																						echo '<option value="' . htmlspecialchars($target['id']) . '" ' . $selected . '>'
																							. htmlspecialchars($target['name'])
																							. '</option>';
																					}

																					echo '</select>';
																					?>

																				</div>

																				<div class="col-md-3 col-12">
																					<input type="number" class="form-control" name="target_amount" placeholder="กรอกจำนวน" min="0" value="<?= $project_target_groups_item['target_amount']; ?>">
																				</div>


																			</div>
																		</div>
																	</div>
																</div>
															</div>


														</div>
													</div>

													<input type="hidden" name="op_from" value="<?= $op_from; ?>">
													<input type="hidden" name="years_select" value="<?= $years_select; ?>">
													<input type="hidden" name="project_id" value="<?= $project_id; ?>">
													<input type="hidden" name="project_target_groups_id" value="<?= $project_target_groups_item['id']; ?>">
													<input type="hidden" name="project_activity_id" value="<?= $project_id; ?>">
													<input type="hidden" name="overview" value="<?= $overview; ?>">


													<input type="hidden" name="type" value="update">

													<div class="text-center mt-4">
														<button type="submit" class="btn btn-info btn-bold px-4">
															<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
														</button>
														<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
															<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
														</button>
													</div>

												</form>
											</div>
										</div>

										<div class="modal-footer">
											<button class="btn btn-sm btn-danger" data-dismiss="modal">
												<i class="ace-icon fa fa-times"></i> ปิด
											</button>
										</div>

									</div>
								</div>
							</div>

							<!-- แก้ไขกลุ่มเป้าหมาย   -->

						<?php } ?>
						</p>

						</div>

						<!-- ✅ Modal: เพิ่มกลุ่มเป้าหมาย  -->
						<div class="modal fade modal-xl" id="modal-add-target-groups-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่มกลุ่มเป้าหมาย
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_target_groups_project" method="POST">

												<p class="text-center text-info mb-4"> <span class="text-dark">โครงการ : </span><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">

													<div class="row col-md-12 mt-3">
														<div class="col-md-12">
															<label for="summernote-reson" class="font-weight-bold">ระบุกลุ่มเป้าหมาย </label><br>
															<div class="card border-0  w-100">
																<div class="card-body p-3">


																	<div id="GroupstargetGroupContainer<?= $project_id; ?>">
																		<div class="row g-2 align-items-center mb-2 Groups-target-row<?= $project_id; ?>">
																			<div class="col-md-7 col-12">


																				<?php
																				$target_list = getTargetSelect($db_Project);


																				$id_selected = $project_activity['code_target']  ?? null; // รับค่า id ที่ส่งเข้ามา

																				// สร้าง select
																				echo '<select name="target_groups[]" id="target_groups[]" class="form-control">';

																				foreach ($target_list as $target) {

																					$selected = ($id_selected == $target['id']) ? 'selected' : '';
																					echo '<option value="' . htmlspecialchars($target['id']) . '" ' . $selected . '>'
																						. htmlspecialchars($target['name'])
																						. '</option>';
																				}

																				echo '</select>';
																				?>

																			</div>

																			<div class="col-md-3 col-12">
																				<input type="number" class="form-control" name="target_amount[]" placeholder="กรอกจำนวน" min="0">
																			</div>

																			<div class="col-md-2 col-12 text-center">
																				<button type="button" class="btn btn-outline-primary w-100 addRowBtnGroups<?= $project_id; ?>">
																					<i class="fa fa-plus"></i> เพิ่ม
																				</button>
																			</div>
																		</div>
																	</div>
																</div>
															</div>
														</div>
														<script>
															// ✅ เพิ่ม/ลบแถว
															document.addEventListener("click", function(e) {
																if (e.target.closest(".addRowBtnGroups<?= $project_id; ?>")) {
																	const row = e.target.closest(".Groups-target-row<?= $project_id; ?>");
																	const newRow = row.cloneNode(true);
																	newRow.querySelector("select").value = "";
																	newRow.querySelector("input").value = "";
																	newRow.querySelector(".addRowBtnGroups<?= $project_id; ?>").outerHTML =
																		`<button type="button" class="btn btn-outline-danger w-100 removeRowBtnGroups<?= $project_id; ?>">
																							<i class="fa fa-trash"></i> ลบ
																						</button>`;
																	document.getElementById("GroupstargetGroupContainer<?= $project_id; ?>").appendChild(newRow);
																}

																if (e.target.closest(".removeRowBtnGroups<?= $project_id; ?>")) {
																	e.target.closest(".Groups-target-row<?= $project_id; ?>").remove();
																}
															});
														</script>

													</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">


												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="insert">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>

						<!-- เพิ่มกลุ่มเป้าหมาย   -->
						<!-- กลุ่มเป้าหมายของโครงการ -->
						<!-- วิธีการติดตามผล -->


						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($pre_project_data['evaluate_type'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								วิธีการติดตามประเมินผล

							</h4>
						</div>


						<div class="card-body px-4 py-3 bg-white">
							<p class="mb-1">
								<?php
								echo '<i class="fas fa-circle fa-xs" style="color: #5b7db9;"></i> วิธีการ  :  ';
								echo htmlspecialchars($pre_project_data['evaluate_type']);
								?>
								<a data-toggle="modal" data-target="#modal-edit-evaluate-type-project<?= $project_id; ?>" class="btn btn-sm btn-primary  text-white">
									<i class="fa fa-plus"></i> เพิ่ม/แก้ไข
								</a>

							</p>
						</div>
						<!-- ✅ Modal: แก้ไขวิธีการติดตามผล  -->
						<div class="modal fade modal-xl" id="modal-edit-evaluate-type-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่ม/แก้ไขวิธีการติดตามผล
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">

													<div class="row col-md-12 mt-3">
														<div class="col-md-12">
															<label for="summernote-reson" class="font-weight-bold">ระบุวิธีการติดตามผล</label><br>
															<div class="card border-0  w-100">
																<div class="card-body p-3">


																	<div id="evaluateTypeContainer<?= $project_id; ?>">
																		<div class="row g-2 align-items-center mb-2 ">

																			<div class="col-md-12 col-12">
																				<input type="text" class="form-control" name="data" placeholder="วิธีการ" value="<?= $pre_project_data['evaluate_type']; ?>">
																			</div>
																		</div>
																	</div>
																</div>
															</div>
														</div>


													</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="project_target_groups_id" value="<?= $project_target_groups_item['id']; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="evaluate_type">
												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="update">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>

						<!-- เครื่องมือ   -->
						<div class="card-body bg-white">
							<p class="mb-1">
								<?php
								echo '<i class="fas fa-circle fa-xs" style="color: #5b7db9;"></i> เครื่องมือ  :  ';
								echo htmlspecialchars($pre_project_data['evaluate_tool']);
								?>
								<a data-toggle="modal" data-target="#modal-edit-evaluate-tool-project<?= $project_id; ?>" class="btn btn-sm btn-primary  text-white">
									<i class="fa fa-plus"></i> เพิ่ม/แก้ไข
								</a>

							</p>
						</div>
						<!-- ✅ Modal: แก้ไขเครื่องมือ -->
						<div class="modal fade modal-xl" id="modal-edit-evaluate-tool-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่ม/แก้ไขเครื่องมือ
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">

													<div class="row col-md-12 mt-3">
														<div class="col-md-12">
															<label for="summernote-reson" class="font-weight-bold">ระบุเครื่องมือ</label><br>
															<div class="card border-0  w-100">
																<div class="card-body p-3">


																	<div id="evaluateTypeContainer<?= $project_id; ?>">
																		<div class="row g-2 align-items-center mb-2 ">

																			<div class="col-md-12 col-12">
																				<input type="text" class="form-control" name="data" placeholder="เครื่องมือ" value="<?= $pre_project_data['evaluate_tool']; ?>">
																			</div>
																		</div>
																	</div>
																</div>
															</div>
														</div>


													</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="project_target_groups_id" value="<?= $project_target_groups_item['id']; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="evaluate_tool">
												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="update">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>

						<!-- แก้ไขเครื่องมือ   -->
						<!-- วิธีการติดตามผล -->

						<!-- การประเมินความเสี่ยง -->


						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php

								if (!empty($pre_project_data['risk_action'])) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								การประเมินความเสี่ยง

							</h4>
						</div>


						<div class="card-body px-4 py-3 bg-white">
							<p class="mb-1">
								<?php
								echo '<i class="fas fa-circle fa-xs" style="color: #5b7db9;"></i> ความเสี่ยงที่คาดว่าจะเกิดขึ้น  :  ';
								echo htmlspecialchars($pre_project_data['risk_action']);
								?>
								<a data-toggle="modal" data-target="#modal-edit-risk-action-project<?= $project_id; ?>" class="btn btn-sm btn-primary  text-white">
									<i class="fa fa-plus"></i> เพิ่ม/แก้ไข
								</a>

							</p>
						</div>
						<!-- ✅ Modal: แก้ไขวิธีการติดตามผล  -->
						<div class="modal fade modal-xl" id="modal-edit-risk-action-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่ม/แก้ไขความเสี่ยงที่คาดว่าจะเกิดขึ้น
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">

													<div class="row col-md-12 mt-3">
														<div class="col-md-12">
															<label for="summernote-reson" class="font-weight-bold">ระบุความเสี่ยงที่คาดว่าจะเกิดขึ้น</label><br>
															<div class="card border-0  w-100">
																<div class="card-body p-3">


																	<div id="riskTypeContainer<?= $project_id; ?>">
																		<div class="row g-2 align-items-center mb-2 ">

																			<div class="col-md-12 col-12">
																				<input type="text" class="form-control" name="data" placeholder="ความเสี่ยงที่จะเกิดขึ้น" value="<?= $pre_project_data['risk_action']; ?>">
																			</div>
																		</div>
																	</div>
																</div>
															</div>
														</div>


													</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="project_target_groups_id" value="<?= $project_target_groups_item['id']; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="risk_action">
												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="update">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>

						<!-- วิธีการปิดความเสี่ยง   -->
						<div class="card-body bg-white">
							<p class="mb-1">
								<?php
								echo '<i class="fas fa-circle fa-xs" style="color: #5b7db9;"></i> วิธีการปิดความเสี่ยง  :  ';
								echo htmlspecialchars($pre_project_data['risk_stop']);
								?>
								<a data-toggle="modal" data-target="#modal-edit-risk-stop-project<?= $project_id; ?>" class="btn btn-sm btn-primary  text-white">
									<i class="fa fa-plus"></i> เพิ่ม/แก้ไข
								</a>

							</p>
						</div>

						<!-- ✅ Modal: แก้ไขวิธีการปิดความเสี่ยง -->
						<div class="modal fade modal-xl" id="modal-edit-risk-stop-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> เพิ่ม/แก้ไข
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">

													<div class="row col-md-12 mt-3">
														<div class="col-md-12">
															<label for="summernote-reson" class="font-weight-bold">ระบุวิธีการปิดความเสี่ยง</label><br>
															<div class="card border-0  w-100">
																<div class="card-body p-3">


																	<div id="riskTypeContainer<?= $project_id; ?>">
																		<div class="row g-2 align-items-center mb-2 ">

																			<div class="col-md-12 col-12">
																				<input type="text" class="form-control" name="data" placeholder="วิธีการปิดความเสี่ยง" value="<?= $pre_project_data['risk_stop']; ?>">
																			</div>
																		</div>
																	</div>
																</div>
															</div>
														</div>


													</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">
												<input type="hidden" name="project_target_groups_id" value="<?= $project_target_groups_item['id']; ?>">
												<input type="hidden" name="tb_data" value="pre_project">
												<input type="hidden" name="col_data" value="risk_stop">
												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="update">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>

						<!-- แก้ไขวิธีการปิดความเสี่ยง   -->
						<!-- การประเมินความเสี่ยง -->

						<!-- แนบไฟล์เอกสารเพิ่มเติม -->


						<div class="card-header d-flex justify-content-between align-items-center bg-white">
							<h4 class="card-title text-110 text-primary-d2 mb-0">
								<?php
								$project_file_item = getProjectFileList($db_Project, $project_id);
								if (!empty($project_file_item)) {
								?><i class="fas fa-check-double" style="color: #63E6BE;"></i>
								<?php } else { ?>
									<i class="far fa-edit " style="color: #f26cb1ff;"></i>
								<?php } ?>
								แนบไฟล์เอกสารเพิ่มเติม
								<a data-toggle="modal" data-target="#modal-upload-file-project<?= $project_id; ?>" class="btn btn-sm btn-light  text-primary">
									<i class="fa fa-upload"></i> อัพโหลดไฟล์
								</a>
							</h4>
						</div>

						<div class="card-body px-4 py-3 bg-white">
							<p class="mb-1">
								<?php

								foreach ($project_file_item as $project_file) {


									echo '<i class="fas fa-paperclip fa-xs" style="color: #c7e6cd;"></i> ';
									echo htmlspecialchars($project_file['files']);



								?>

									<!-- 🔹 ปุ่มลบ -->
									<button type="button"
										class="btn btn-link p-0 ml-2 icon-btn delete"
										title="ลบข้อมูล"
										data-target="#modal-del-file-project<?= $project_file['id']; ?>"
										data-toggle="modal">
										<i class="fa fa-trash fa-lg"></i>
									</button>
									<br>
									<!-- with white backdrop -->
							<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-file-project<?= $project_file['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
								<div class="modal-dialog " role="document">
									<div class="modal-content bgc-transparent brc-danger-m2 shadow">
										<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
											<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
												WARNING!
											</h5>

											<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true" class="text-150">&times;</span>
											</button>
										</div>


										<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
											<div class="d-flex align-items-top mr-2 mr-md-5">
												<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
												<div class="text-secondary-d2 text-105">
													<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

												</div>
											</div>
										</div>

										<div class="modal-footer bgc-white-tp2 border-0">
											<form action="?page=<?= $page; ?>&op=save_data_target_groups_project" method="POST">
												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">

												<input type="hidden" name="project_file_id" value="<?= $project_file['id']; ?>">
												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="delete">
												<button class="btn btn-danger" type="submit">ยืนยัน </button>

											</form>
										</div>
									</div>
								</div>
							</div>
							<!-- ลบ -->
						<?php } ?>
						</p>

						</div>

						<!-- ✅ Modal: แนบไฟล์เอกสารเพิ่มเติม  -->
						<div class="modal fade modal-xl" id="modal-upload-file-project<?= $project_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
							<div class="modal-dialog " role="document">
								<div class="modal-content">

									<div class="modal-header">
										<h5 class="modal-title text-blue" id="exampleModalLabel2">
											<i class="fas fa-file-alt text-secondary"></i> อัพโหลดไฟล์เอกสารเพิ่มเติม
										</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>

									<div class="modal-body bg-white">
										<div class="bgc-white radius-1">
											<form id="form-target_project-<?= $project_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data" action="?page=<?= $page; ?>&op=upload_file_project" method="POST">

												<p class="text-center text-info mb-4"><?= htmlspecialchars($pre_project_data['name_project']); ?></p>

												<div class="form-group">

													<div class="row col-md-12 mt-3">
														<div class="col-md-12">
															<label class="font-weight-bold">อัปโหลดไฟล์เอกสาร (เลือกหลายไฟล์ได้)</label><br>
															<div class="card border-0  w-100">
																<div class="card-body p-3">


																	<div id="riskTypeContainer<?= $project_id; ?>">
																		<div class="row g-2 align-items-center mb-2 ">

																			<div class="col-md-12 col-12">

																				<input type="file"
																					name="upload_files[]"
																					class="form-control"
																					multiple
																					accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
																			</div>
																		</div>
																	</div>
																</div>
															</div>
														</div>


													</div>
												</div>

												<input type="hidden" name="op_from" value="<?= $op_from; ?>">
												<input type="hidden" name="years_select" value="<?= $years_select; ?>">
												<input type="hidden" name="project_id" value="<?= $project_id; ?>">

												<input type="hidden" name="overview" value="<?= $overview; ?>">


												<input type="hidden" name="type" value="insert">

												<div class="text-center mt-4">
													<button type="submit" class="btn btn-info btn-bold px-4">
														<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
													</button>
													<button type="reset" class="btn btn-outline-secondary ml-2 px-4">
														<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
													</button>
												</div>

											</form>
										</div>
									</div>

									<div class="modal-footer">
										<button class="btn btn-sm btn-danger" data-dismiss="modal">
											<i class="ace-icon fa fa-times"></i> ปิด
										</button>
									</div>

								</div>
							</div>
						</div>


						<!-- แนบไฟล์เอกสารเพิ่มเติม -->

					</div>
				</div>
			</div>

		</div>

	<? }  //project_detail
	?>
	<?php function project_detail()
	{
		global $db_Project, $db_person, $db_pbmBudget;
		$page = $_GET['page'];
		$years_select = $_GET['years_select'] ?? '';
		$code_pre_project = $_GET['code_pre_project'] ?? '';


		$year_list = getYearSelect($db_pbmBudget);
	?>
		<h3 class="text-center"> </h3>
		<?= $years_select; ?>
	<?php }  //project_detail
	?>

	<?php
	switch ($op) {
		case "project":
		default;
			project();
			break;
		case "pre_project":
			pre_project();
			break;
		case "project_set":
			project_set();
			break;


		case "save_project":
			// Logic อยู่ใน project_save_project_execute.php — เรียกตั้งแต่ต้นไฟล์เมื่อ POST เพื่อไม่ให้ HTML ปะปนกับ JSON
			project_all_execute_save_project();
			break;
		case "update_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];
			$tb_data = $_POST['tb_data'] ?? '';
			$data = $_POST['data'] ?? '';
			$col_data = $_POST['col_data'] ?? '';
			$project_id = $_POST['project_id'] ?? '';
			$type = $_POST['type'] ?? '';
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];
			$overview = $_POST['overview'];

			if (!empty($tb_data) && !empty($col_data) && !empty($project_id)) {

				if (!empty($type) && $type == "delete") {
					echo $tb_data;
					$sql = mysqli_query($db_Project, "UPDATE `$tb_data` SET `$col_data`='' WHERE id='$project_id' ");
				} else {
					$sql = mysqli_query($db_Project, "UPDATE `$tb_data` SET `$col_data`='$data' WHERE id='$project_id' ");
				}
			}


			if ($sql) {
				// echo "U";

				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;

				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;

				echo build_swal_error_redirect($link);
			}


			break;
		case "del_set_all":
			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];
			$tb_data = $_POST['op_from'];
			$id = $_GET['id'];
			if ($id != "") {

				$sql = mysqli_query($db_Project,  "UPDATE $tb_data SET status='99' WHERE  id='$id' ");
			}

			if ($sql) {

				$link = "?page=" . $page . '&op=' . $op_from;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from;
				echo build_swal_error_redirect($link);
			}



			break;
		case "save_data_person":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];
			$staff_id = $_POST['staff_id'];
			$staff_project = $_POST['staff_project'];
			$phone = $_POST['phone'];
			$overview = $_POST['overview'];
			$tb_data = $_POST['tb_data'];
			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				$sql = mysqli_query(
					$db_Project,
					"INSERT INTO $tb_data (project_id, staff_project, status, phone,dates,staff_insert) 
   				 VALUES ('$project_id', '$staff_project', '$status', '$phone', NOW(), '$r_staff[id]')"
				);
			} else if ($type == "update") {
				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							staff_project = '$staff_project',
							phone = '$phone',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$staff_id'"
				);
			} elseif ($type == "delete") {
				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$staff_id'"
				);
			}


			if ($sql) {
				$isAjax = isset($_POST['ajax']) && (string)$_POST['ajax'] === '1';
				if ($isAjax) {
					header('Content-Type: application/json; charset=utf-8');
					$id = 0;
					if (($type ?? '') === 'insert') {
						$id = (int)mysqli_insert_id($db_Project);
					} else {
						$id = isset($project_kpi_id) ? (int)$project_kpi_id : 0;
					}
					echo json_encode(['ok' => 1, 'id' => $id], JSON_UNESCAPED_UNICODE);
					exit;
				}
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$isAjax = isset($_POST['ajax']) && (string)$_POST['ajax'] === '1';
				if ($isAjax) {
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode(['ok' => 0, 'msg' => 'บันทึกไม่สำเร็จ'], JSON_UNESCAPED_UNICODE);
					exit;
				}
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;

		case "save_data_department":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];
			$department_id = $_POST['department_id'];
			$department_project = $_POST['department_project'];
			$level = $_POST['level'];
			$overview = $_POST['overview'];
			$tb_data = $_POST['tb_data'];
			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				$sql = mysqli_query(
					$db_Project,
					"INSERT INTO $tb_data (project_id, department_project, status, level,dates,staff_insert) 
   				 VALUES ('$project_id', '$department_project', '$status', '$level', NOW(), '$r_staff[id]')"
				);
			} else if ($type == "update") {
				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							department_project = '$department_project',
							level = '$level',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$department_id'"
				);
			} elseif ($type == "delete") {
				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$department_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_budget":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];
			$budget_id = $_POST['budget_id'];
			$code_budget = $_POST['budget_code'];
			$budget_other = $_POST['budget_other'];

			$overview = $_POST['overview'];
			$tb_data = $_POST['tb_data'];
			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				$sql = mysqli_query(
					$db_Project,
					"INSERT INTO $tb_data (project_id, code_budget, status, budget_other,dates,staff_insert) 
   				 VALUES ('$project_id', '$code_budget', '$status', '$budget_other', NOW(), '$r_staff[id]')"
				);
			} else if ($type == "update") {
				if ($code_budget != '99') {
					$budget_other = '';
				}
				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							code_budget = '$code_budget',
							budget_other = '$budget_other',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$budget_id'"
				);
			} elseif ($type == "delete") {
				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$budget_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_time_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];
			$start_month = $_POST['start_month'];
			$start_year = $_POST['start_year'];
			$end_month = $_POST['end_month'];
			$end_year = $_POST['end_year'];

			$overview = $_POST['overview'];
			$tb_data = $_POST['tb_data'];
			$type = $_POST['type'] ?? '';
			if ($type == "update") {

				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							start_month = '$start_month',
							start_year = '$start_year',
							end_month = '$end_month',
							end_year = '$end_year'
						
						WHERE id = '$project_id'"
				);
			} elseif ($type == "delete") {
				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$project_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_location_project":
			//echo "U";
			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$tb_data = $_POST['tb_data'];
			$col_data = $_POST['col_data'];
			$type = $_POST['type'] ?? '';
			if ($type == "update") {
				if ($col_data == 'location') { //พื้นที่ดำเนินการ
					$data_insert = $_POST['location'];
				}
				if ($col_data == 'reson') { //เหตุผล

					$data_insert = $_POST['reson'];
				}
				if ($col_data == 'result') { //ผลที่ผ่านมา
					$data_insert = $_POST['result'];
				}
				if ($col_data == 'objective') { //วัตถุประสงค์
					$data_insert = $_POST['objective'];
				}
				if ($col_data == 'target_project') { //เป้าหมาย
					$data_insert = $_POST['target_project'];
				}
				//	echo $data_insert;
				$sql = mysqli_query(
					$db_Project,
					"UPDATE $tb_data 
						SET 
							$col_data = '$data_insert',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE id = '$project_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_kpi_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$kpi_name_esc = mysqli_real_escape_string($db_Project, trim((string)($_POST['kpi_name'] ?? '')));
			$kpi_unit_esc = mysqli_real_escape_string($db_Project, trim((string)($_POST['kpi_unit'] ?? '')));
			$code_kpi_raw = trim((string)($_POST['code_kpi'] ?? ''));
			$code_unit_raw = trim((string)($_POST['code_unit'] ?? ''));
			if ($code_kpi_raw === '') {
				$code_kpi_raw = '0';
			}
			if ($code_unit_raw === '') {
				$code_unit_raw = '0';
			}
			$code_kpi = mysqli_real_escape_string($db_Project, $code_kpi_raw);
			$code_unit = mysqli_real_escape_string($db_Project, $code_unit_raw);
			// $mon_01 = $_POST['mon_01'];
			// $mon_02 = $_POST['mon_02'];
			// $mon_03 = $_POST['mon_03'];
			// $mon_04 = $_POST['mon_04'];
			// $mon_05 = $_POST['mon_05'];
			// $mon_06 = $_POST['mon_06'];
			// $mon_07 = $_POST['mon_07'];
			// $mon_08 = $_POST['mon_08'];
			// $mon_09 = $_POST['mon_09'];
			// $mon_10 = $_POST['mon_10'];
			// $mon_11 = $_POST['mon_11'];
			// $mon_12 = $_POST['mon_12'];
			$kpi_weight = $_POST['kpi_weight'];

			$mon_01 = toDecimalOrZero($_POST['mon_01'] ?? '');
			$mon_02 = toDecimalOrZero($_POST['mon_02'] ?? '');
			$mon_03 = toDecimalOrZero($_POST['mon_03'] ?? '');
			$mon_04 = toDecimalOrZero($_POST['mon_04'] ?? '');
			$mon_05 = toDecimalOrZero($_POST['mon_05'] ?? '');
			$mon_06 = toDecimalOrZero($_POST['mon_06'] ?? '');
			$mon_07 = toDecimalOrZero($_POST['mon_07'] ?? '');
			$mon_08 = toDecimalOrZero($_POST['mon_08'] ?? '');
			$mon_09 = toDecimalOrZero($_POST['mon_09'] ?? '');
			$mon_10 = toDecimalOrZero($_POST['mon_10'] ?? '');
			$mon_11 = toDecimalOrZero($_POST['mon_11'] ?? '');
			$mon_12 = toDecimalOrZero($_POST['mon_12'] ?? '');


			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				//	echo $data_insert;
				$sql = mysqli_query(
					$db_Project,
					"INSERT INTO project_kpi(code_pre_project, code_kpi, code_unit, kpi_name, kpi_unit, mon_01, mon_02, mon_03, mon_04, mon_05, mon_06, mon_07, mon_08, mon_09, mon_10, mon_11, mon_12, kpi_weight, status, dates, staff_insert) 
   				 VALUES ('$project_id', '$code_kpi', '$code_unit', '$kpi_name_esc', '$kpi_unit_esc', '$mon_01', '$mon_02', '$mon_03', '$mon_04', '$mon_05', '$mon_06', '$mon_07', '$mon_08', '$mon_09', '$mon_10', '$mon_11', '$mon_12', '$kpi_weight', '1', NOW(), '$r_staff[id]')"

				);
			} else if ($type == "update") {
				$project_kpi_id = $_POST['project_kpi_id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_kpi 
						SET 
							code_kpi = '$code_kpi',
							code_unit = '$code_unit',
							kpi_name = '$kpi_name_esc',
							kpi_unit = '$kpi_unit_esc',
							mon_01 = '$mon_01',
							mon_02 = '$mon_02',
							mon_03 = '$mon_03',
							mon_04 = '$mon_04',
							mon_05 = '$mon_05',
							mon_06 = '$mon_06',
							mon_07 = '$mon_07',
							mon_08 = '$mon_08',
							mon_09 = '$mon_09',
							mon_10 = '$mon_10',
							mon_11 = '$mon_11',
							mon_12 = '$mon_12',
							kpi_weight = '$kpi_weight',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE id = '$project_kpi_id'"
				);
			} elseif ($type == "delete") {
				$project_kpi_id = $_POST['project_kpi_id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_kpi 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$project_kpi_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_activity_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$activity_no = $_POST['activity_no'];
			$activity_name = $_POST['activity_name'];
			$activity_detail = $_POST['activity_detail'];
			$activity_weight = $_POST['activity_weight'];

			for ($i = 1; $i <= 12; $i++) {
				$key = sprintf('mon_%02d', $i);
				$$key = isset($_POST[$key]) ? (int)$_POST[$key] : 0;
			}

			$type = $_POST['type'] ?? '';
			if ($type == "insert") {
				// insert activity
				$sql = mysqli_query(
					$db_Project,
					"INSERT INTO project_activity (
						code_pre_project, activity_no,activity_name, activity_detail,
						mon_01, mon_02, mon_03, mon_04, mon_05, mon_06,
						mon_07, mon_08, mon_09, mon_10, mon_11, mon_12,
						activity_weight, status, dates, staff_insert
					) VALUES (
						'$project_id', '$activity_no', '$activity_name','$activity_detail',
						'$mon_01', '$mon_02', '$mon_03', '$mon_04', '$mon_05', '$mon_06',
						'$mon_07', '$mon_08', '$mon_09', '$mon_10', '$mon_11', '$mon_12',
						'$activity_weight', '1', NOW(), '$r_staff[id]'
					)"
				);
			} else if ($type == "update") {
				$project_activity_id = $_POST['project_activity_id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_activity 
						SET 
							activity_no = '$activity_no',
							activity_name = '$activity_name',
							activity_detail = '$activity_detail',
							mon_01 = '$mon_01',
							mon_02 = '$mon_02',
							mon_03 = '$mon_03',
							mon_04 = '$mon_04',
							mon_05 = '$mon_05',
							mon_06 = '$mon_06',
							mon_07 = '$mon_07',
							mon_08 = '$mon_08',
							mon_09 = '$mon_09',
							mon_10 = '$mon_10',
							mon_11 = '$mon_11',
							mon_12 = '$mon_12',
							activity_weight = '$activity_weight',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE id = '$project_activity_id'"
				);
			} elseif ($type == "delete") {
				$project_activity_id = $_POST['project_activity_id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_activity 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$project_activity_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_activity_target_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$project_activity_id = $_POST['project_activity_id'];
			$project_activity_target_id = $_POST['project_activity_target_id'];

			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				// insert target groups

				$target_groups = $_POST['target_group'] ?? [];
				$target_amounts = $_POST['target_amount'] ?? [];

				for ($i = 0; $i < count($target_groups); $i++) {
					$group = mysqli_real_escape_string($db_Project, trim($target_groups[$i]));
					$amount = (float) $target_amounts[$i];

					if (!empty($group) && $amount > 0) {
						$sql = "
								INSERT INTO project_activity_target 
									(code_pre_project,code_activity, code_target, target_amount,  status,staff_insert, dates)
								VALUES 
									('$project_id','$project_activity_id', '$group', '$amount', '1','$r_staff[id]', NOW())
							";
						mysqli_query($db_Project, $sql);
					}
				}
			} else if ($type == "update") {

				// update target groups
				$target_groups = $_POST['target_group'];
				$target_amounts = $_POST['target_amount'];
				$project_activity_target_id;
				//	echo $r_staff['id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_activity_target 
						SET 
							code_target = '$target_groups',
							target_amount = '$target_amounts',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE id = '$project_activity_target_id'"
				);
			} elseif ($type == "delete") {

				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_activity_target 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$project_activity_target_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_activity_kpi_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$project_activity_id = $_POST['project_activity_id'];
			$project_activity_kpi_id = $_POST['project_activity_kpi_id'];

			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				// insert target groups

				$kpi_names = $_POST['kpi_name'] ?? [];
				$kpi_units = $_POST['kpi_unit'] ?? [];
				$kpi_amounts = $_POST['kpi_amount'] ?? [];

				for ($i = 0; $i < count($kpi_names); $i++) {
					$kpi_name_item = mysqli_real_escape_string($db_Project, trim($kpi_names[$i]));
					$kpi_unit_item = mysqli_real_escape_string($db_Project, trim($kpi_units[$i]));
					$kpi_amount_item = (float) $kpi_amounts[$i];

					if (!empty($kpi_name_item) && $kpi_amount_item > 0) {

						$sql = "
            INSERT INTO project_activity_kpi 
                (code_pre_project, code_activity, kpi_name, kpi_unit, kpi_amount, status, staff_insert, dates)
            VALUES 
                ('$project_id', '$project_activity_id', '$kpi_name_item', '$kpi_unit_item', '$kpi_amount_item', '1', '$r_staff[id]', NOW())
       			 ";
						mysqli_query($db_Project, $sql);



						// if (mysqli_query($db_Project, $sql)) {

						// 	echo "✅ Inserted KPI: " . htmlspecialchars($kpi_name_item) . "<br>";
						// } else {

						// 	echo "❌ SQL Error: " . mysqli_error($db_Project) . "<br>";
						// 	echo "SQL: " . htmlspecialchars($sql) . "<br>";
						// }
					} else {
						echo "⚠️ ข้ามรายการที่ $i (ไม่มีชื่อหรือจำนวนไม่ถูกต้อง)<br>";
					}
				}
			} else if ($type == "update") {

				// update target groups
				$kpi_name = $_POST['kpi_name'];
				$kpi_unit = $_POST['kpi_unit'];
				$kpi_amounts = $_POST['kpi_amount'];
				$project_activity_kpi_id;
				//	echo $r_staff['id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_activity_kpi
						SET 
							kpi_name = '$kpi_name',
							kpi_unit = '$kpi_unit',
							kpi_amount = '$kpi_amounts',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE id = '$project_activity_kpi_id'"
				);
			} elseif ($type == "delete") {

				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_activity_kpi
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$project_activity_kpi_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_activity_budget_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'] ?? '';
			$years_select = $_POST['years_select'] ?? '';
			$project_id = $_POST['project_id'] ?? '';

			$overview = $_POST['overview'] ?? '';
			$project_activity_id = $_POST['project_activity_id'] ?? '';
			$budget_no = $_POST['budget_no'] ?? '';
			$code_expenses = $_POST['code_expenses'] ?? '';
			$code_expenses_detail = $_POST['code_expenses_detail'] ?? '';

			$ิbudget_item = $_POST['budget_item'] ?? '';
			$price = $_POST['price'] ?? '';
			$qty1 = $_POST['qty1'] ?? '';
			$unit1 = $_POST['unit1'] ?? '';
			$qty2 = $_POST['qty2'] ?? '';
			$unit2 = $_POST['unit2'] ?? '';
			$total = $_POST['total'] ?? '';

			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				$sql = mysqli_query(
					$db_Project,
					"INSERT INTO project_activity_budget (
						code_pre_project, code_activity,budget_no,code_expenses, code_expenses_detail,
						status, dates, staff_insert,price, qty1, unit1, qty2, unit2, total
					) VALUES (
						'$project_id','$project_activity_id', '$budget_no', '$code_expenses','$code_expenses_detail',
						'1', NOW(), '$r_staff[id]', '$price', '$qty1', '$unit1', '$qty2', '$unit2', '$total'
					)"
				);
			} else if ($type == "update") {

				// update target groups

				$project_activity_budget_id = $_POST['project_activity_budget_id'];

				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_activity_budget
						SET 
							budget_no = '$budget_no',
							code_expenses = '$code_expenses',
							code_expenses_detail = '$code_expenses_detail',
							staff_insert = '$r_staff[id]',
							price = '$price',
							qty1 = '$qty1',
							unit1 = '$unit1',
							qty2 = '$qty2',
							unit2 = '$unit2',
							total = '$total',
							dates = NOW()						
						WHERE id = '$ิbudget_item'"
				);
			} elseif ($type == "delete") {

				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_activity_budget
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$ิbudget_item'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_budget_detail_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$project_activity_id = $_POST['project_activity_id'];
			$project_budget_detail_id = $_POST['project_budget_detail_id'];


			$mon_01 = toDecimalOrZero($_POST['mon_01'] ?? '');
			$mon_02 = toDecimalOrZero($_POST['mon_02'] ?? '');
			$mon_03 = toDecimalOrZero($_POST['mon_03'] ?? '');
			$mon_04 = toDecimalOrZero($_POST['mon_04'] ?? '');
			$mon_05 = toDecimalOrZero($_POST['mon_05'] ?? '');
			$mon_06 = toDecimalOrZero($_POST['mon_06'] ?? '');
			$mon_07 = toDecimalOrZero($_POST['mon_07'] ?? '');
			$mon_08 = toDecimalOrZero($_POST['mon_08'] ?? '');
			$mon_09 = toDecimalOrZero($_POST['mon_09'] ?? '');
			$mon_10 = toDecimalOrZero($_POST['mon_10'] ?? '');
			$mon_11 = toDecimalOrZero($_POST['mon_11'] ?? '');
			$mon_12 = toDecimalOrZero($_POST['mon_12'] ?? '');
			$total = $mon_01 + $mon_02 + $mon_03 + $mon_04 + $mon_05 + $mon_06 + $mon_07 + $mon_08 + $mon_09 + $mon_10 + $mon_11 + $mon_12;

			$type = $_POST['type'] ?? '';
			if ($type == "update") {


				if (!empty($project_budget_detail_id)) {

					$sql = mysqli_query(
						$db_Project,
						"UPDATE project_activity_budget_detail
						SET 
							mon_01 = '$mon_01',
							mon_02 = '$mon_02',
							mon_03 = '$mon_03',
							mon_04 = '$mon_04',
							mon_05 = '$mon_05',
							mon_06 = '$mon_06',
							mon_07 = '$mon_07',
							mon_08 = '$mon_08',
							mon_09 = '$mon_09',
							mon_10 = '$mon_10',
							mon_11 = '$mon_11',
							mon_12 = '$mon_12',
							total = '$total',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE id = '$project_budget_detail_id'"
					);
				} else {

					$sql = mysqli_query(
						$db_Project,
						"INSERT INTO project_activity_budget_detail (
							code_pre_project, code_activity,
							mon_01, mon_02, mon_03, mon_04, mon_05, mon_06,
							mon_07, mon_08, mon_09, mon_10, mon_11, mon_12,
							total,status,staff_insert, dates
						) VALUES (
							'$project_id', '$project_activity_id',
							'$mon_01', '$mon_02', '$mon_03', '$mon_04', '$mon_05', '$mon_06',
							'$mon_07', '$mon_08', '$mon_09', '$mon_10', '$mon_11', '$mon_12',
							'$total','1','$r_staff[id]', NOW()
						)"
					);
				}
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_target_groups_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$project_target_groups_id = $_POST['project_target_groups_id'];

			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				// insert target groups

				$target_groups = $_POST['target_groups'] ?? [];
				$target_amounts = $_POST['target_amount'] ?? [];

				for ($i = 0; $i < count($target_groups); $i++) {
					$group = mysqli_real_escape_string($db_Project, trim($target_groups[$i]));
					$amount = (float) $target_amounts[$i];

					if (!empty($group) && $amount > 0) {
						$sql = "
								INSERT INTO project_target_groups
									(code_pre_project,code_target, target_amount,  status,staff_insert, dates)
								VALUES 
									('$project_id','$group', '$amount', '1','$r_staff[id]', NOW())
							";
						mysqli_query($db_Project, $sql);
					}
				}
			} else if ($type == "update") {

				// update target groups
				$target_groups = $_POST['target_groups'];
				$target_amounts = $_POST['target_amount'];

				//	echo $r_staff['id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_target_groups
						SET 
							code_target = '$target_groups',
							target_amount = '$target_amounts',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE id = '$project_target_groups_id'"
				);
			} elseif ($type == "delete") {

				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_target_groups 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$project_target_groups_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "upload_file_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$project_file_id = $_POST['project_file_id'];


			$type = $_POST['type'] ?? '';
			if ($type == "insert") {

				if (!empty($_FILES['upload_files']['name'][0])) {

					$uploadDir = "../document/project_files/";
					if (!file_exists($uploadDir)) {
						mkdir($uploadDir, 0777, true);
					}

					foreach ($_FILES['upload_files']['name'] as $key => $name) {

						$tmpName = $_FILES['upload_files']['tmp_name'][$key];
						$size = $_FILES['upload_files']['size'][$key];

						if ($size > 0) {

							$newName = time() . "_" . basename($name);
							$targetFile = $uploadDir . $newName;

							if (move_uploaded_file($tmpName, $targetFile)) {

								// 🔥 บันทึกลงฐานข้อมูลถ้าต้องการ
								mysqli_query(
									$db_Project,
									"INSERT INTO project_file (code_pre_project, files, dates, status, staff_insert)
                    				 VALUES ('$project_id', '$name', NOW(), '1', '$r_staff[id]')"
								);
							}
						}
					}
				}
			} elseif ($type == "delete") {

				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_file
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()
						WHERE id = '$project_file_id'"
				);
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_consistency_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$project_id = $_POST['project_id'];

			$overview = $_POST['overview'];
			$code_consistency = $_POST['code_consistency'];

			$detail_list = $_POST['consistency_detail'] ?? [];

			$type = $_POST['type'] ?? '';
			if ($type == "insert") {
				$sql_now = mysqli_query(
					$db_Project,
					"SELECT * FROM project_consistency 
    					 WHERE project_id = '$project_id' ORDER BY id DESC LIMIT 1"
				);
				$n_now = mysqli_num_rows($sql_now);
				if ($n_now > 0) { //มีข้อมูลอยู่แล้ว ให้ update แทน
					$r_now = mysqli_fetch_array($sql_now);
					// $code_consistency = $r_now['id'];
					$sql = mysqli_query(
						$db_Project,
						"UPDATE project_consistency 
						SET 
							code_consistency = '$code_consistency',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE project_id = '$project_id'"
					);
				} else { //ไม่มีข้อมูลค่อย insert ใหม่
					// insert prject_consistencyก่อน
					$sql = mysqli_query(
						$db_Project,
						"INSERT INTO project_consistency (
							project_id, code_consistency,status,staff_insert, dates
						) VALUES (
							'$project_id', '$code_consistency','1','$r_staff[id]', NOW()
						)"
					);
				}
				//สำเร็จ แล้วค่อย insert project_consistency_map
				if ($sql) {

					$sql_now = mysqli_query(
						$db_Project,
						"SELECT * FROM project_consistency 
    					 WHERE project_id = '$project_id' ORDER BY id DESC LIMIT 1"
					);
					$r_now = mysqli_fetch_array($sql_now);
					$code_consistency = $r_now['id'];
					//  Insert รายการ checkbox

					foreach ($detail_list as $detail_id) {
						mysqli_query(
							$db_Project,
							"INSERT INTO project_consistency_map(
							code_project_consistency,
							code_consistency_detail,
							status,
							staff_insert,
							dates
						) VALUES (
							'$code_consistency',
							'$detail_id',
							'1',
							'$r_staff[id]',
							NOW()
						)"
						);
					}
				}
			} else if ($type == "update") {
				$project_consistency_id = $_POST['project_consistency_id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_consistency 
						SET 
							code_consistency = '$code_consistency',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE project_id = '$project_id'"
				);
				//สำเร็จ แล้วค่อย insert project_consistency_map
				if ($sql) {
					$sql_now = mysqli_query(
						$db_Project,
						"UPDATE project_consistency_map 
						 SET status = '99',staff_insert = '$r_staff[id]', dates = NOW()
    					 WHERE code_project_consistency = '$project_consistency_id' "
					);

					//  Insert รายการ checkbox
					foreach ($detail_list as $detail_id) {
						mysqli_query(
							$db_Project,
							"INSERT INTO project_consistency_map(
							code_project_consistency,
							code_consistency_detail,
							status,
							staff_insert,
							dates
						) VALUES (
							'$project_consistency_id',
							'$detail_id',
							'1',
							'$r_staff[id]',
							NOW()
						)"
						);
					}
				}
			} else if ($type == "delete") {

				$project_consistency_id = $_POST['project_consistency_id'];
				$sql = mysqli_query(
					$db_Project,
					"UPDATE project_consistency 
						SET 
							status = '99',
							staff_insert = '$r_staff[id]',
							dates = NOW()						
						WHERE project_id = '$project_id'"
				);
				//สำเร็จ แล้วค่อย insert project_consistency_map
				if ($sql) {
					$sql_now = mysqli_query(
						$db_Project,
						"UPDATE project_consistency_map 
						 SET status = '99',staff_insert = '$r_staff[id]', dates = NOW()
    					 WHERE code_project_consistency = '$project_consistency_id' "
					);
				}
			}


			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
			}


			break;
		case "save_data_consistency_kpi_project":
			project_all_execute_save_consistency_kpi();
			break;

		case "submit_project":

			global $db_Project, $db_person, $db_pbmBudget;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_GET['op_from'];
			$years_select = $_GET['years_select'];
			$project_id = $_GET['project_id'];
			$overview = $_GET['overview'];
			$status_project = '4'; // นำเข้าที่ประชุม
			if (!empty($project_id)) {


				$sql = mysqli_query($db_Project, "UPDATE `pre_project` SET `status_project`='$status_project' WHERE id='$project_id' ");
			}


			if ($sql) {
				// echo "U";

				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;

				echo build_swal_success_redirect($link);
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;

				echo build_swal_error_redirect($link);
			}


			break;
	}
	?>