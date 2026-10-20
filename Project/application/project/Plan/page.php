<?php
session_start();
include("../../config/config_db.php");
date_default_timezone_set('Asia/Bangkok');
include_once(__DIR__ . '/../fn/main_staff.php');
include_once(__DIR__ . '/../fn/main_default.php');
include_once(__DIR__ . '/../../KPI/fn/main_kpi.php');
include_once(__DIR__ . '/../../KPI/fn/kpi_type_year.php');

$title = $title ?? 'Project Plan';

$PharCode = isset($_GET['PharCode']) ? trim((string)$_GET['PharCode']) : '';
if ($PharCode !== '') {
	$stmt = mysqli_prepare($db_person, "SELECT code, email, hash_pw FROM staff WHERE code = ? AND work_status IN (0,1) LIMIT 1");
	mysqli_stmt_bind_param($stmt, 's', $PharCode);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
	if ($res && $row3 = mysqli_fetch_array($res)) {
		$_SESSION['code'] = $row3['code'];
		$_SESSION['r_email'] = $row3['email'];
		$_SESSION['hash_pw'] = $row3['hash_pw'];
	}
}

$sess_code = $_SESSION['code'] ?? '';
$sess_email = $_SESSION['r_email'] ?? '';
$stmt_me = mysqli_prepare($db_person, "SELECT * FROM staff WHERE code = ? AND email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_me, 'ss', $sess_code, $sess_email);
mysqli_stmt_execute($stmt_me);
$result = mysqli_stmt_get_result($stmt_me);
if ($result === false) {
	die("คุณไม่มีสิทธิ์เข้าใช้งานค่ะ");
}
if ($row = mysqli_fetch_array($result)) {

	$is_post = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');

	$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
	if ($page === '' && $is_post && isset($_POST['page'])) {
		$page = trim((string)$_POST['page']);
	}
	$op = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
	if ($op === '' && isset($_GET['cp'])) {
		$op = trim((string) $_GET['cp']);
	}
	$allowed_pages = ['dashboard', 'exec_dashboard', 'setting', 'pre_project', 'project', 'tools', 'project_all', 'project_manage', 'project_approve'];
	if ($page === '' || !in_array($page, $allowed_pages, true)) {
		$page = 'dashboard';
	}
	$pm_page_wide = in_array($page, ['project_manage', 'project_approve', 'project_all', 'project_follow'], true);
	// Plan = Admin owner system: ใช้กับหน้าโครงการ (รวม AJAX op=render_* ที่ยิงกลับมาที่ page.php)
	if (in_array($page, ['project_all', 'project_manage', 'project_approve', 'project_follow'], true)) {
		if (!defined('PLAN_ADMIN')) define('PLAN_ADMIN', true);
	}

	// ถ้าเป็นคำสั่ง AJAX ของหน้าโครงการ ให้ตอบกลับ JSON ตรง ๆ (ห้ามมี layout HTML ปน)
	// เช่น render_activity_table/render_budget_table/render_kpi_goal_table หรือ op ที่ลงท้าย _ajax
	// บางคำขอส่ง op ใน POST body อย่างเดียว (ไม่มีใน query) — ต้องอ่านจาก $_POST ด้วย
	$op_ajax = trim((string)$op);
	if ($op_ajax === '' && $is_post && isset($_POST['op'])) {
		$op_ajax = trim((string)$_POST['op']);
	}
	// ระบุ op ที่หน้า project_approve ใช้ (รวม flow ขอดำเนินการ operate) เพื่อไม่ให้ถูกห่อ layout HTML ของ Plan_Admin จนพัง JSON
	$is_ajax_op = $op_ajax !== '' && (
		(substr($op_ajax, 0, 7) === 'render_')
		|| (substr($op_ajax, -5) === '_ajax')
		|| $op_ajax === 'get_request_detail'
		|| $op_ajax === 'get_operate_project_detail'
		|| $op_ajax === 'get_report_project_detail'
		|| $op_ajax === 'get_change_request_detail'
		|| $op_ajax === 'get_cancel_request_detail'
		|| $op_ajax === 'download_change_file'
		|| $op_ajax === 'download_cancel_file'
		|| $op_ajax === 'plan_unit_decision'
		|| $op_ajax === 'save_plan_unit_submitter_reply'
		|| $op_ajax === 'save_submitter_reply'
		|| $op_ajax === 'certify_approve'
		|| $op_ajax === 'certify_reject'
		|| $op_ajax === 'certify_operate_approve'
		|| $op_ajax === 'certify_operate_reject'
		|| $op_ajax === 'certify_report_approve'
		|| $op_ajax === 'certify_report_reject'
		|| $op_ajax === 'plan_admin_report_approve'
		|| $op_ajax === 'plan_admin_report_reject'
		|| $op_ajax === 'certify_change_approve'
		|| $op_ajax === 'certify_change_reject'
		|| $op_ajax === 'mark_approve_inbox_read'
	);
	if ($is_post && $is_ajax_op && in_array($page, ['project_manage', 'project_approve', 'project_follow', 'project_all'], true)) {
		// กัน output ปะปน (HTML/notice) จน client parse JSON ไม่ได้
		while (ob_get_level()) {
			@ob_end_clean();
		}
		@ob_start();
		@ob_clean();
		$target_ajax = __DIR__ . '/' . $page . '.php';
		if (is_file($target_ajax)) {
			include($target_ajax);
			exit;
		}
	}
	// POST save_project (บันทึกชื่อโครงการ wizard): ต้องจบเป็น JSON เท่านั้น — ถ้าส่ง layout Plan ก่อนจะได้ response ~30KB+ และ jQuery dataType:json พัง (ขึ้น "เกิดข้อผิดพลาด" แม้ UPDATE สำเร็จ)
	if ($is_post && $page === 'project_manage' && $op_ajax === 'save_project') {
		include_once(__DIR__ . '/../fn/main_project.php');
		require_once __DIR__ . '/../User/project_save_project_execute.php';
		project_all_execute_save_project();
		exit;
	}
	if ($is_post && $page === 'project_manage' && ($op === 'save_data_consistency_kpi_project' || $op_ajax === 'save_data_consistency_kpi_project')) {
		include __DIR__ . '/../User/project_all.php';
		exit;
	}
	if ($page == "dashboard") {
		$page_name = 'Dashboard';
	}
	if ($page == "setting") {
		$page_name = 'ข้อมูลพื้นฐาน';
	}
	if ($page == "pre_project") {
		$page_name = 'จัดการร่างแผน';
	}
	if ($page == "project") {
		$page_name = 'จัดการแผนปฏิบัติการประจำปี';
	}
	if ($page == "tools") {
		$page_name = 'เครื่องมือ';
	}
	if ($page === 'project_manage' && ($op === 'print_project' || $op === 'print_project_pdf')) {
		include __DIR__ . '/../User/project_print.php';
		exit;
	}
	if ($page === 'project_approve' && ($op === 'download_change_file' || $op === 'download_cancel_file')) {
		include __DIR__ . '/project_approve.php';
		exit;
	}

	if (!function_exists('pm_page_crumb')) {
		function pm_page_crumb(string $leaf, string $mid = 'จัดการโครงการ'): string
		{
			return '<span class="pm-page-crumb">'
				. '<span class="pm-crumb-root">ระบบบริหารโครงการ</span>'
				. '<span class="pm-crumb-sep">&gt;</span>'
				. '<span class="pm-crumb-mid">' . htmlspecialchars($mid, ENT_QUOTES, 'UTF-8') . '</span>'
				. '<span class="pm-crumb-sep">&gt;</span>'
				. '<span class="pm-crumb-cur">' . htmlspecialchars($leaf, ENT_QUOTES, 'UTF-8') . '</span>'
				. '</span>';
		}
	}

	if ($page == "project_all") {
		$page_name = pm_page_crumb('โครงการทั้งหมด');
	}
	if ($page == "project_manage") {
		$page_name = pm_page_crumb('โครงการของฉัน');
	}
	if ($page == "project_approve") {
		$pm_appr_flow_title = [
			'include' => 'พิจารณาแผนงาน',
			'operate' => 'อนุมัติโครงการ',
			'report' => 'รายงานผล',
			'change' => 'เปลี่ยนแปลง/ยกเลิก',
		];
		$pm_appr_flow_now = isset($_GET['flow']) ? trim((string)$_GET['flow']) : '';
		if ($pm_appr_flow_now === '' && isset($_GET['open_flow'])) {
			$pm_appr_flow_now = trim((string)$_GET['open_flow']);
		}
		if (!isset($pm_appr_flow_title[$pm_appr_flow_now])) {
			$pm_appr_flow_now = 'include';
		}
		$page_name = pm_page_crumb($pm_appr_flow_title[$pm_appr_flow_now], 'แจ้งเตือน');
	}
	if ($page == "project_follow") {
		$page_name = pm_page_crumb('ติดตาม');
	}
	//echo "$page";

	$email = $row['email'];
	$email_exe = $row['email_exe'];
	$position_code = $row['position_code'];

	$r_position = getPosition($db_person, $position_code);
	$r_prefix = getPrefix($db_person, $row['prefix_code'] ?? '') ?: [];
	$pm_nav_user_name = trim(($r_prefix['name'] ?? '') . ' ' . ($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
	$pm_nav_user_pos = is_array($r_position) ? (string)($r_position['name'] ?? '') : '';
	$pm_nav_user_photo = rtrim((string)($path_picture ?? ''), '/') . '/' . ltrim((string)($row['photo'] ?? ''), '/');

	$pm_appr_menu_badges = ['include' => 0, 'operate' => 0, 'report' => 0, 'change' => 0];
	$pm_nav_appr_flow = isset($_GET['flow']) ? trim((string)$_GET['flow']) : '';
	if ($page === 'project_approve' && $pm_nav_appr_flow === '' && isset($_GET['open_flow'])) {
		$pm_nav_appr_flow = trim((string)$_GET['open_flow']);
	}
	if ($page === 'project_approve' && !in_array($pm_nav_appr_flow, ['include', 'operate', 'report', 'change'], true)) {
		$pm_nav_appr_flow = 'include';
	}
	$pm_nav_staff_id = (int)($row['id'] ?? 0);
	if ($pm_nav_staff_id > 0 && isset($db_Project) && $db_Project) {
		if (!function_exists('pm_approve_menu_badge_counts')) {
			@include_once(__DIR__ . '/../fn/main_project.php');
		}
		if (function_exists('pm_approve_menu_badge_counts')) {
			$pm_appr_menu_badges = pm_approve_menu_badge_counts($db_Project, $pm_nav_staff_id, true);
		}
	}

	if ($is_post && $op_ajax === 'save_system_feedback') {
		while (ob_get_level()) {
			@ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');
		@include_once(__DIR__ . '/../fn/main_project.php');
		$out = function_exists('pm_save_system_feedback')
			? pm_save_system_feedback(
				$db_Project ?? null,
				(int)$pm_nav_staff_id,
				(string)($pm_nav_user_name ?? ''),
				'plan',
				(string)$page,
				(int)($_POST['rating'] ?? 0),
				(string)($_POST['comment'] ?? '')
			)
			: ['ok' => 0, 'msg' => 'ไม่พร้อมบันทึก'];
		echo json_encode($out, JSON_UNESCAPED_UNICODE);
		exit;
	}

?>
	<!doctype html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
		<!-- <base href=".../Template/ace/" /> -->

		<title><?= $title; ?></title>

		<!-- include common vendor stylesheets & fontawesome -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap/dist/css/bootstrap.css">

		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/fontawesome.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/regular.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/brands.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/solid.css">




		<!-- include vendor stylesheets used in "Dashboard 4" page. see "/views//pages/partials/dashboard-4/@vendor-stylesheets.hbs" -->
		<!-- "Dashboard" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/dashboard-2/@page-style.css">
		<!-- "Charts" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/charts/@page-style.css">

		<!-- include vendor stylesheets used in "Horizontal Menu" page. see "/views//pages/partials/horizontal/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/fullcalendar/main.css">

		<!-- include fonts -->
		<!-- <link rel="stylesheet" type="text/css" href="../../Template/../../Template/ace/dist/css/../Template/../Template/ace-font.css"> -->
		<!-- <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" /> -->
		<link href="https://fonts.googleapis.com/css2?family=K2D:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Sarabun:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">


		<!-- include vendor stylesheets used in "Basic Elements" page. see "/views//pages/partials/form-basic/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/nouislider/distribute/nouislider.min.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/ion-rangeslider/css/ion.rangeSlider.min.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/tiny-date-picker/tiny-date-picker.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/tiny-date-picker/date-range-picker.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.css">


		<!-- jQuery ถูกโหลดจาก Template/ace ด้านล่างแล้ว (อย่าโหลดซ้ำ) -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<script src="https://unpkg.com/gijgo@1.9.13/js/gijgo.min.js" type="text/javascript"></script>
		<link href="https://unpkg.com/gijgo@1.9.13/css/gijgo.min.css" rel="stylesheet" type="text/css" />


		<!-- include vendor stylesheets used in "Calendar" page. see "/views//pages/partials/calendar/@vendor-stylesheets.hbs" -->
		<!-- <link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/fullcalendar/main.css"> -->

		<!-- ../Template/ace.css -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/dist/css/ace.css">

		<!-- favicon -->
		<link rel="icon" type="image/png" href="../../Template/ace/assets/mis.jpg" />

		<!-- include vendor stylesheets used in "Charts" page. see "/views//pages/partials/charts/@vendor-stylesheets.hbs" -->
		<!-- <link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/chartist/dist/chartist.min.css"> -->

		<!-- include vendor stylesheets used in "Icons" page. see "/views//pages/partials/icons/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/jam-icons/css/jam.min.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/eva-icons/style/eva-icons.css">

		<!-- "Profile" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/page-profile/@page-style.css">

		<!-- include vendor stylesheets used in "Buttons" page. see "/views//pages/partials/buttons/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/node-waves/dist/waves.css">


		<!-- include vendor stylesheets used in "Gallery" page. see "/views//pages/partials/gallery/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/photoswipe/dist/photoswipe.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/photoswipe/dist/default-skin/default-skin.css">

		<!-- include vendor stylesheets used in "DataTables" page. see "/views//pages/partials/table-datatables/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/datatables.net-bs4/css/dataTables.bootstrap4.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/datatables.net-buttons-bs4/css/buttons.bootstrap4.css">

		<!-- "DataTables" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/table-datatables/@page-style.css">

		<!-- include vendor stylesheets used in "Bootstrap Table" page. see "/views//pages/partials/table-bootstrap/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-table/dist/bootstrap-table.css">


		<!-- include vendor stylesheets used in "More Elements" page. see "/views//pages/partials/form-more/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-star-rating/css/star-rating.css">

		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-select/dist/css/bootstrap-select.css">

		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-duallistbox/dist/bootstrap-duallistbox.css">


		<!-- <link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/select2/dist/css/select2.css"> -->
		<!-- <link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/chosen-js/chosen.css"> -->



		<!-- "Horizontal Menu" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/dashboard-2/@page-style.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/dist/css/ace-themes.css">


		<!-- "Dashboard 4" page styles, specific to this page for demo only -->
		<!-- <link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/dashboard-4/@page-style.css"> -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/dist/css/ace-themes.css">
		<link rel="stylesheet" type="text/css" href="../asset/pm-theme.css?v=<?= (int) @filemtime(__DIR__ . '/../asset/pm-theme.css') ?>">

		<!-- include vendor stylesheets used in "Wysiwyg & Markdown" page. see "/views//pages/partials/form-wysiwyg/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/summernote/dist/summernote-lite.css">

		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-markdown/css/bootstrap-markdown.min.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/form-wysiwyg/@page-style.css">

		<!-- include vendor stylesheets used in "File Upload" page. see "/views//pages/partials/form-upload/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/dropzone/dist/dropzone.css">

		<!-- ckeditor -->
		<script type="text/javascript" src="ckeditor2/ckeditor.js"></script>
		<script type="text/javascript" src="../../vendor-cmu/sweetalert.min.js"></script>

		<!-- Select2 (โหลดไฟล์ CSS ใน head ได้ แต่ JS/init ต้องอยู่หลัง jQuery ตัวสุดท้าย) -->
		<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

		<style>
			/* สี background + ตัวหนังสือของรายการที่เลือก */
			.select2-container--default .select2-selection--multiple .select2-selection__choice {
				background-color: #926cb8 !important;
				border: 1px solid #734d96 !important;
				color: #fff !important;
				display: block !important;
				width: 100%;
				margin: 2px 0 !important;

				border-radius: 4px !important;
				padding: 5px 10px !important;
			}

			/* ปุ่มกากบาทของ tag ที่เลือก */
			.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
				color: #d41c1c !important;
				/* กากบาทสีแดง */


				border-radius: 50%;
				/* ทำให้เป็นวงกลม */
				width: 20px;
				/* ขนาดวงกลม */
				height: 20px;
				display: inline-flex;
				/* จัดให้อยู่กลาง */
				align-items: center;
				justify-content: center;
				font-size: 22px;
				/* ขนาดกากบาท */
				font-weight: bold;
				margin-right: 6px;
				/* ระยะห่างจากข้อความ */
				cursor: pointer;
			}

			/* เอฟเฟกต์เวลา hover */
			.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
				background-color: #d41c1c !important;
				/* วงกลมแดง */
				color: #fff !important;
				/* กากบาทขาว */
				transform: scale(1.2);
				/* ขยายเล็กน้อย */
			}

			/* ทำให้ช่องพิมพ์ค้นหา (multiple) แสดงและพิมพ์ได้เสมอ */
			.select2-container--default .select2-selection--multiple .select2-search--inline {
				float: none !important;
				display: block !important;
				width: 100% !important;
			}

			.select2-container--default .select2-selection--multiple .select2-search__field {
				width: 100% !important;
				min-width: 180px;
				margin-top: 6px !important;
			}
			.sidebar .nav .pm-appr-parent-link .nav-text{
				display:inline-flex;
				align-items:center;
				gap:.4rem;
			}
			.sidebar .nav .pm-appr-menu-link{
				display:flex !important;
				align-items:center;
				justify-content:space-between;
				gap:.55rem;
			}
			.sidebar .nav .pm-appr-menu-badge{
				flex:0 0 auto;
				min-width:1.35rem;
				height:1.15rem;
				padding:0 .38rem;
				border-radius:999px;
				background:rgba(255,255,255,.12);
				color:rgba(255,255,255,.72);
				font-size:.68rem;
				font-weight:700;
				line-height:1.15rem;
				text-align:center;
			}
			.sidebar .nav .pm-appr-menu-badge.is-hot{
				background:#2ea36b;
				color:#fff;
				font-weight:800;
			}
			.sidebar #submenu-approve{
				margin:.1rem .45rem .35rem .35rem;
				padding:.15rem 0 .2rem;
				border-left:2px solid rgba(255,255,255,.14);
			}
			.sidebar #submenu-approve .submenu-inner{
				padding-left:0;
			}
			.sidebar #submenu-approve .nav-item{
				margin:0;
			}
			.sidebar #submenu-approve .nav-link{
				margin:2px 0 2px .55rem;
				padding:.4rem .55rem .4rem .75rem !important;
				border-radius:999px;
				color:#fff !important;
				font-size:.9rem;
				line-height:1.25;
			}
			.sidebar #submenu-approve .nav-link .nav-text,
			.sidebar #submenu-approve .nav-link .nav-text > span{
				color:inherit !important;
				font-weight:600;
			}
			.sidebar #submenu-approve .nav-link .nav-text{
				display:flex;
				align-items:center;
				min-width:0;
				flex:1 1 auto;
			}
			.sidebar #submenu-approve .nav-link .pm-appr-sub-ico{
				width:1.05rem;
				margin-right:.4rem;
				opacity:.75;
				text-align:center;
			}
			.sidebar #submenu-approve .nav-item.active > .nav-link{
				background:linear-gradient(180deg, rgba(255,255,255,.7) 0%, rgba(255,255,255,.15) 45%, transparent 70%), linear-gradient(90deg, #f8f1fc 0%, #e4c9f5 100%);
				color:#4a1d73 !important;
			}
			.sidebar #submenu-approve .nav-item.active > .nav-link .pm-appr-sub-ico{
				opacity:1;
			}
			.sidebar #submenu-approve .nav-link:hover{
				background:rgba(255,255,255,.08);
			}
		</style>

		<!-- jQuery ต้องโหลดก่อนหน้าเนื้อหา (ไฟล์ที่ include มี inline script ใช้ jQuery) -->
		<script src="../../Template/ace/node_modules/jquery/dist/jquery.js"></script>

	</head>

	<body class="pm-theme">
		<div class="body-container">

			<div class="main-container">

				<div id="sidebar" class="sidebar sidebar-dark sidebar-color sidebar-fixed sidebar-backdrop expandable pm-sidebar"
					data-swipe="true" data-dismiss="true">
					<div class="sidebar-inner">

						<div class="ace-scroll flex-grow-1 mt-1px" data-ace-scroll="{}">
							<!-- all sidebar header is inside scrollable area -->
							<!-- .navbar-brand inside sidebar, only shown in desktop view -->
							<div class="d-flex sidebar-section-item fadeable-left fadeable-top">
								<div class="fadeinable">
									<!-- shown when sidebar is collapsed -->
									<div class="py-2 mx-1" id="sidebar-header-brand1">
										<a class="navbar-brand pm-side-brand-sm" href="page.php?page=dashboard">
											<span class="pm-side-logo-wrap pm-side-logo-wrap-sm">
												<img src="../../image/plan-logo1.png" alt="ระบบบริหารโครงการ" class="pm-side-logo">
											</span>
										</a>
									</div>
								</div>

								<div class="fadeable w-100 pm-side-brand-expanded">
									<!-- shown when sidebar is full-width (mobile only; desktop ซ่อน — แสดงตอนย่อเมนู) -->
									<div class="py-2 text-center mx-2" id="sidebar-header-brand2">
										<a class="navbar-brand pm-side-brand" href="page.php?page=dashboard">
											<span class="pm-side-logo-wrap">
												<img src="../../image/plan-logo1.png" alt="ระบบบริหารโครงการ" class="pm-side-logo">
											</span>
											<div class="pm-side-brand-title">ระบบบริหารโครงการ</div>
											<span class="pm-side-brand-badge">Pharmacy CMU</span>
										</a>
									</div>
								</div>
							</div><!-- /.sidebar-section-item  -->

							<!-- optional `nav` tag -->
							<nav aria-label="Main">
								<ul class="nav flex-column mt-2 has-active-border active-on-top">


									<li class="nav-item-caption">
										<span class="fadeable pl-3">ADMIN</span>
										<span class="fadeinable mt-n2 text-125">&hellip;</span>

									</li>


									<li class="nav-item <?php if ($page == 'dashboard') {
															echo "active";
														} ?>">

										<a href="page.php?page=dashboard" class="nav-link">
											<i class="nav-icon fa fa-tachometer-alt"></i>
											<span class="nav-text fadeable">
												<span>Dashboard</span>
											</span>


										</a>

										<b class="sub-arrow"></b>

									</li>

									<li class="nav-item <?php if ($page == 'exec_dashboard') {
															echo "active";
														} ?>">
										<a href="page.php?page=exec_dashboard" class="nav-link">
											<i class="nav-icon fa fa-chart-pie"></i>
											<span class="nav-text fadeable">
												<span>Executive Dashboard</span>
											</span>
										</a>
										<b class="sub-arrow"></b>
									</li>


									<li class="nav-item  <?php if ($page == 'setting') {
																echo "active open";
															} ?> ">

										<a href="#" class="nav-link dropdown-toggle" data-toggle="collapse" data-target="#submenu-setting" aria-expanded="<?= ($page == 'setting') ? 'true' : 'false' ?>">
											<i class="nav-icon fa fa-cube"></i>
											<span class="nav-text fadeable">
												<span>ข้อมูลพื้นฐาน</span>
											</span>

											<b class="caret fa fa-angle-left rt-n90"></b>


										</a>

										<div class="hideable submenu collapse <?php if ($page == 'setting') {
																					echo "show";
																				} ?>" id="submenu-setting">
											<ul class="submenu-inner">

												<li class="nav-item <?php if (($page == 'setting') && ($op == 'set_Project_Type')) {
																		echo "active";
																	} ?>">

													<a href="?page=setting&op=set_Project_Type" class="nav-link">

														<span class="nav-text">
															<span>ประเภทโครงการ</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_project_nature')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_project_nature" class="nav-link">

														<span class="nav-text">
															<span>ลักษณะโครงการ</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_project_consistency')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_project_consistency" class="nav-link">

														<span class="nav-text">
															<span>ความสอดคล้อง</span>
														</span>


													</a>


												</li>

												<li class="nav-item <?php if (($page == 'setting') && ($op == 'set_project_budget')) {
																		echo "active";
																	} ?>">

													<a href="?page=setting&op=set_project_budget" class="nav-link">

														<span class="nav-text">
															<span>แหล่งงบ</span>
														</span>


													</a>


												</li>


												<li class="nav-item <?php if (($page == 'setting') && ($op == 'set_project_expenses')) {
																		echo "active";
																	} ?>">

													<a href="?page=setting&op=set_project_expenses" class="nav-link">

														<span class="nav-text">
															<span>ประเภทค่าใช้จ่าย</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_project_status')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_project_status" class="nav-link">

														<span class="nav-text">
															<span>สถานะโครงการ</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_project_target')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_project_target" class="nav-link">

														<span class="nav-text">
															<span>กลุ่มเป้าหมาย</span>
														</span>


													</a>


												</li>

												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_project_personnel')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_project_personnel" class="nav-link">

														<span class="nav-text">
															<span>หน่วยงาน</span>
														</span>

													</a>

												</li>
												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_project_personnel_info')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_project_personnel_info" class="nav-link">

														<span class="nav-text">
															<span>ข้อมูลบุคลากร</span>
														</span>

													</a>

												</li>


											</ul>
										</div>

										<b class="sub-arrow"></b>

									</li>

									<!-- เมนูโครงการ -->
									<li class="nav-item pm-nav-group-title">
										<span class="nav-link pm-nav-group-label">
											<i class="nav-icon fa fa-folder-open"></i>
											<span class="nav-text fadeable">
												<span>จัดการโครงการ</span>
											</span>
										</span>
										<b class="sub-arrow"></b>
									</li>
									<li class="nav-item pm-nav-child <?php if ($page == 'project_manage') {
															echo "active";
														} ?>">
										<a href="?page=project_manage" class="nav-link">
											<i class="nav-icon fa fa-tasks"></i>
											<span class="nav-text fadeable">
												<span>โครงการของฉัน</span>
											</span>
										</a>
										<b class="sub-arrow"></b>
									</li>
									<li class="nav-item pm-nav-child <?php if ($page == 'project_all') {
															echo "active";
														} ?>">
										<a href="?page=project_all" class="nav-link">
											<i class="nav-icon fa fa-th-large"></i>
											<span class="nav-text fadeable">
												<span>โครงการทั้งหมด</span>
											</span>
										</a>
										<b class="sub-arrow"></b>
									</li>
									<?php
									$pm_appr_menu_items = [
										'include' => ['พิจารณาแผนงาน', 'fa-clipboard-list'],
										'operate' => ['อนุมัติโครงการ', 'fa-file-signature'],
										'report' => ['รายงานผล', 'fa-chart-bar'],
										'change' => ['เปลี่ยนแปลง/ยกเลิก', 'fa-exchange-alt'],
									];
									$pm_appr_open = ($page === 'project_approve');
									$pm_appr_menu_total = 0;
									foreach ($pm_appr_menu_items as $__flow => $__item) {
										$pm_appr_menu_total += (int)($pm_appr_menu_badges[$__flow] ?? 0);
									}
									?>
									<li class="nav-item <?php echo $pm_appr_open ? 'active open' : ''; ?>">
										<a href="#" class="nav-link dropdown-toggle pm-appr-parent-link<?php echo $pm_appr_open ? '' : ' collapsed'; ?>" data-toggle="collapse" data-target="#submenu-approve" aria-expanded="<?= $pm_appr_open ? 'true' : 'false' ?>">
											<i class="nav-icon fa fa-bell"></i>
											<span class="nav-text fadeable">
												<span>แจ้งเตือน</span>
												<span class="pm-appr-menu-badge pm-appr-menu-total<?php echo $pm_appr_menu_total > 0 ? ' is-hot' : ''; ?>"><?php echo (int)$pm_appr_menu_total; ?></span>
											</span>
											<b class="caret fa fa-angle-left rt-n90"></b>
										</a>
										<div class="hideable submenu collapse<?php echo $pm_appr_open ? ' show' : ''; ?>" id="submenu-approve">
											<ul class="submenu-inner">
												<?php foreach ($pm_appr_menu_items as $__flow => $__item):
													$__label = $__item[0];
													$__ico = $__item[1];
													$__n = (int)($pm_appr_menu_badges[$__flow] ?? 0);
													$__active = ($page === 'project_approve' && ($pm_nav_appr_flow ?? '') === $__flow);
												?>
												<li class="nav-item <?php if ($__active) { echo 'active'; } ?>">
													<a href="?page=project_approve&flow=<?php echo rawurlencode($__flow); ?>" class="nav-link pm-appr-menu-link">
														<span class="nav-text">
															<i class="fa <?php echo htmlspecialchars($__ico, ENT_QUOTES, 'UTF-8'); ?> pm-appr-sub-ico"></i>
															<span><?php echo htmlspecialchars($__label, ENT_QUOTES, 'UTF-8'); ?></span>
														</span>
														<span class="pm-appr-menu-badge<?php echo $__n > 0 ? ' is-hot' : ''; ?>"><?php echo $__n; ?></span>
													</a>
												</li>
												<?php endforeach; ?>
											</ul>
										</div>
										<b class="sub-arrow"></b>
									</li>
									<li class="nav-item">
										<a href="../User/page.php" class="nav-link" title="ไปหน้าผู้ใช้งาน">
											<i class="nav-icon fa fa-user"></i>
											<span class="nav-text fadeable">
												<span>ผู้ใช้งาน</span>
											</span>
										</a>
										<b class="sub-arrow"></b>
									</li>
								</ul>
							</nav>

						</div><!-- /.ace-scroll -->


						<div class="sidebar-section">
							<div class="sidebar-section-item fadeable-left fadeable-top">
								<div class="fadeinable">
									<button type="button" class="btn btn-primary btn-bold" data-toggle="modal" data-target="#modalSystemFeedback" title="ประเมินระบบ">
										<i class="fa fa-star"></i>
									</button>
								</div>

								<div id="sidebar-footer" class="fadeable text-center border-t-1 brc-white-tp10 w-95">
									<div class="py-2">
										<button type="button" class="pm-side-eval-btn" data-toggle="modal" data-target="#modalSystemFeedback">ประเมินระบบ</button>
										<div class="pm-side-ver">@2026 V.1.0</div>
									</div>
								</div>
							</div>
						</div>

					</div>
				</div>

				<div role="main" class="main-content">
					<nav class="navbar navbar-sm navbar-expand-lg navbar-fixed navbar-white">
						<div class="navbar-inner shadow-md">

							<button type="button"
								class="btn btn-burger align-self-center ml-25 mr-2 d-none d-xl-flex btn-h-lighter-blue"
								data-toggle="sidebar" data-target="#sidebar" aria-controls="sidebar" aria-expanded="true"
								aria-label="Toggle sidebar">
								<span class="bars text-default"></span>
							</button><!-- sidebar toggler button -->


							<div class="d-flex h-100 justify-content-xl-between align-items-center">
								<button type="button"
									class="btn btn-burger burger-arrowed static collapsed ml-2 d-flex d-xl-none btn-h-lighter-blue"
									data-toggle-mobile="sidebar" data-target="#sidebar" aria-controls="sidebar" aria-expanded="false"
									aria-label="Toggle sidebar">
									<span class="bars text-default"></span>
								</button><!-- mobile sidebar toggler button -->



								<!-- the small Dashboard selection menu -->
								<div class="d-inline-flex">
									<h2 class="d-none d-xl-inline-block text-140 text-dark-m2 mb-0 pb-1"><?= $page_name; ?></h2>


								</div>
							</div>




							<!-- search box for MOBILE view only -->
							<div class="d-lg-none navbar-content flex-grow-0 ml-auto">
								<button class="navbar-toggler h-75 px-2 px-sm-2" type="button" data-toggle="collapse"
									data-target="#navbarSearch" aria-controls="navbarSearch" aria-expanded="false"
									aria-label="Toggle navbar search">
									<i class="fa fa-search text-blue-m1 text-90"></i>
								</button>

								<div class="collapse navbar-collapse navbar-backdrop" id="navbarSearch">
									<div>
										<!-- by wrapping input element inside a "FORM[data-submit=dismiss]", when you submit the form, dropdown will be hidden -->
										<form class="d-flex align-items-center pl-4" data-submit="dismiss">
											<i class="fa fa-search text-blue-m1 mr-n425"></i>
											<input type="text" class="px-2 mx-0 w-100 autofocus border-1 py-35 pr-45 radius-round"
												placeholder="Start searching ..." aria-label="Search" />
											<a href="#"
												class="btn border-0 ml-n475 radius-100 py-15 btn-text-lightgrey btn-h-light-blue px-25 pos-rel">
												<i class="fa fa-microphone"></i>
											</a>
										</form>
									</div>
								</div>
							</div>




							<div class="ml-auto mr-xl-3 pm-nav-user">
								<img class="pm-nav-user-photo" src="<?= htmlspecialchars($pm_nav_user_photo, ENT_QUOTES, 'UTF-8') ?>" width="40" height="40" alt="">
								<div class="pm-nav-user-text" title="<?= htmlspecialchars($pm_nav_user_name, ENT_QUOTES, 'UTF-8') ?>">
									<div class="pm-nav-user-name"><?= htmlspecialchars($pm_nav_user_name, ENT_QUOTES, 'UTF-8') ?></div>
									<div class="pm-nav-user-pos"><?= htmlspecialchars($pm_nav_user_pos, ENT_QUOTES, 'UTF-8') ?></div>
								</div>
								<a href="../logout.php" class="pm-nav-logout">ออกจากระบบ</a>
							</div>

						</div>
					</nav>
					<div class="page-content <?= !empty($pm_page_wide) ? 'container-fluid pm-page-wide' : 'container container-plus' ?>">
						<!-- page header -->
						<div class="d-lg-none page-header py-0">
							<h1 class="text-dark-m3 pb-0 mb-0 text-125">
								Dashboard
							</h1>

							<!-- page tools -->
							<div class="page-tools d-none">
								<div class="action-buttons text-nowrap">
									<a class="btn bgc-white btn-light-secondary mx-0" href="#" data-toggle="tooltip" title="Details">
										<i class="fa fa-search-plus text-blue"></i>
									</a>
									<a class="btn bgc-white btn-light-secondary mx-0" href="#" data-toggle="tooltip" title="Print">
										<i class="fa fa-print text-purple"></i>
									</a>
									<a class="btn bgc-white btn-light-secondary mx-0" href="#" data-toggle="tooltip" title="Remove">
										<i class="fa fa-trash-alt text-danger"></i>
									</a>
								</div>
							</div>
						</div>

						<?php
						$target = __DIR__ . '/' . $page . '.php';
						try {
							if (in_array($page, $allowed_pages, true) && is_file($target)) {
								include($target);
							} else {
								include(__DIR__ . '/dashboard.php');
							}
						} catch (Throwable $e) {
							$GLOBALS['pm_manage_error_shown'] = true;
							echo '<div class="alert alert-danger">โหลดหน้าไม่สำเร็จ: '
								. htmlspecialchars($e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8')
								. '</div>';
						}
						?>


					</div>

					<!-- footer ตัดออกตามที่ขอ -->


					<!-- scroll to top button -->
					<div class="footer-tools mb-5 mb-sm-0">
						<a href="#"
							class="btn-scroll-up btn btn-white brc-black-tp6 px-25 py-2 text-95 border-2 radius-round mb-25 mr-2">
							<i class="fa fa-angle-double-up w-2 h-2"></i>
						</a>
					</div>


				</div>


			</div>


			<form autocomplete="off" class="mt-475" hidden>

				<div class="row mt-475">
					<!-- Custom file input -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<h5 class="card-title text-orange-d4">
									Custom File Input
								</h5>
							</div>

							<div class="card-body">
								<div>
									<input type="file" class="ace-file-input" id="ace-file-input1" />
								</div>
								<div>
									<input type="file" class="ace-file-input" id="ace-file-input" />
								</div>

								<div class="mt-4">
									<input type="file" class="ace-file-input" id="ace-file-input2" multiple />
								</div>
							</div>
						</div>
					</div><!-- /.col -->


					<!-- Range sliders -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<h5 class="card-title text-blue-d2">
									Range Sliders
								</h5>
							</div>

							<div class="card-body">
								<div class="row">
									<div class="col-2">
										<!-- vertical slider -->
										<div id="id-slider-1" class="noUi-toggle-tooltip slider-sm slider-tooltip-right slider-tooltip-caret">
										</div>
									</div>

									<div class="col-10">
										<div id="id-slider-2" class="noUi-toggle-tooltip mb-5 slider-thin"></div>

										<div id="id-slider-3" class="noUi-toggle-tooltip my-5"></div>

										<div id="id-slider-4" class="noUi-toggle-tooltip slider-sm my-5"></div>

										<div id="id-slider-5" class="noUi-toggle-tooltip my-5 slider-thin"></div>

										<div class="mt-5 mb-2">
											<!-- ionSlider -->
											<input type="text" id="id-ionslider-1" name="slider-1" value="" />
										</div>
									</div>
								</div>

							</div>
						</div>
					</div><!-- /.col -->


					<!-- Input spinner -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<h5 class="card-title text-purple-d3">
									Input Spinner
								</h5>
							</div>

							<div class="card-body">
								<input class="form-control form-control-lg" id="id-spinner-1" type="text" value="55" name="id-spinner-1">

								<hr />

								<input class="form-control btn-group-inside-spinner form-control-lg" id="id-spinner-2" type="text" value="" name="id-spinner-2">

								<hr />

								<div class="w-50">
									<input class="form-control text-center" id="id-spinner-3" type="text" value="" name="id-spinner-3">
								</div>
							</div>
						</div>
					</div><!-- /.col -->
				</div><!-- /.row -->
				<div class="row mt-475">
					<!-- Date picker -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<span class="text-primary-d2 text-120">Date/Range Picker</span>
							</div>

							<div class="card-body">
								<!-- Tinydate picker plugin -->
								<div class="form-field">
									<div class="pos-rel">
										<input type="text" class="form-control" id="id-date-1" />

										<input placeholder="modal mode..." type="text" class="form-control mt-25" id="id-date-2" />

										<input type="text" class="form-control mt-25" id="id-date-3" placeholder="Native datepicker on small devices ..." />
									</div>
								</div>


								<hr class="border-dotted my-35" />


								<div id="id-daterange-wrapper" class="pos-rel">
									<!-- daterange -->
									<div class="form-row">
										<div class="col">
											<input id="id-daterange-from" class="form-control ex-inputs-start" placeholder="From date" />
										</div>

										<div class="text-grey-l2">_</div>

										<div class="col">
											<input id="id-daterange-to" class="form-control ex-inputs-end" placeholder="To date" />
										</div>
									</div>

									<div id="id-daterange-container" class="dp-daterange-picker dp-daterange-above">
									</div>
								</div>


								<hr class="border-dotted my-35" />

								<!-- Datetime picker plugin -->
								<div>
									<div class="form-group">
										<label for="id_time_el" class="text-secondary-d3">Date/Time picker:</label>
										<div class="input-group date" id="id-timepicker">
											<input type="text" name="end_time" value="05:12:00" class="form-control" id="id_time_el" />
											<div class="input-group-addon input-group-append">
												<div class="input-group-text">
													<i class="far fa-clock"></i>
												</div>
											</div>
										</div>
									</div>
								</div>


								<!-- Custom time picker -->
								<div class="mt-3">
									<div class="form-group">
										<a href="#" class="btn btn-outline-info border-2" id="id-popover-timepicker">Time picker inside popover</a>

										<div id="id-popover-timepicker-html" class="d-none">
											<!-- it's a hidden element, and the html content of it will be shown inside popover -->
											<div class="d-flex">
												<select class="mx-1 pl-3 no-border caret-double ace-select border-2 radius-1 brc-default-l1 brc-h-default-m2 bgc-default-l4 text-secondary-d2 text-600 text-110" style="width: 5rem;">
													<option selected>H</option>
													<option>1</option>
													<option>2</option>
													<option>3</option>
													<option>4</option>
													<option>5</option>
													<option>6</option>
													<option>7</option>
													<option>8</option>
													<option>9</option>
													<option>10</option>
													<option>11</option>
													<option>12</option>
												</select>

												<span class="text-600 text-150">:</span>

												<select class="mx-1 pl-3 no-border caret-double ace-select border-2 radius-1 brc-default-l1 brc-h-default-m2 bgc-default-l4 text-secondary-d2 text-600 text-110" style="width: 5rem;">
													<option selected>M</option>
													<option>00</option>
													<option>05</option>
													<option>10</option>
													<option>15</option>
													<option>20</option>
													<option>25</option>
													<option>30</option>
													<option>35</option>
													<option>40</option>
													<option>45</option>
													<option>50</option>
													<option>55</option>
												</select>

												<div class="mx-1 btn-group btn-group-toggle" data-toggle="buttons">
													<div role="button" class="btn btn-bold radius-1 px-25 py-2 text-95 btn-light-blue btn-a-light-primary d-style">
														<input type="checkbox" value="1" />
														<span class="d-n-active">AM</span>
														<span class="d-active">PM</span>
													</div>
												</div>
											</div>
										</div>
									</div><!-- /.form-group -->
								</div>

							</div><!-- /.card-body -->
						</div><!-- /.card -->
					</div><!-- /.col -->


					<!-- Color picker -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<span class="text-primary-d2 text-120">
									Color Picker
								</span>
							</div>

							<div class="card-body">
								<div class="input-group">
									<div class="input-group-prepend">
										<span class="input-group-text border-0 radius-0" id="id-color-picker-1-update"></span>
									</div>
									<input type="text" class="form-control" id="id-color-picker-1" />
								</div>

								<hr class="my-4" />

								<div>
									<div id="color-picker-container"></div>
								</div>
							</div>
						</div>
					</div><!-- /.col -->


					<!-- knob -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<span class="text-primary-d2 text-120">Knob Input</span>
							</div>

							<div class="card-body">

								<div class="row">
									<div class="col-6 text-center pos-rel">
										<input type="text" class="knob border-0 mw-100 position-center m-auto" value="15" data-min="0" data-max="100" data-step="10" data-width="80" data-height="80" data-thickness=".2" data-fgcolor="rgb(55, 149, 218)" />
									</div>

									<div class="col-6 text-center pos-rel">
										<input type="text" class="knob border-0 mw-100 position-center m-auto" value="41" data-min="0" data-max="100" data-width="80" data-height="80" data-thickness=".2" data-fgcolor="#629A58" data-displayprevious="true" data-anglearc="250" data-angleoffset="-125" />
									</div>
								</div>

								<div class="row mt-3">
									<div class="col-12 text-center pos-rel">
										<input type="text" class="knob border-0 mw-100 position-center m-auto" value="1" data-min="0" data-max="10" data-width="150" data-height="150" data-thickness=".2" data-fgcolor="#94645C" data-angleoffset="90" data-cursor="true" />
									</div>
								</div>

							</div>
						</div>
					</div><!-- /.col -->
				</div><!-- /.row -->






			</form>



		</div> <!-- /.body-container-->














		<!-- include common vendor scripts used in demo pages -->
		<script src="../../Template/ace/node_modules/popper.js/dist/umd/popper.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap/dist/js/bootstrap.js"></script>

		<!-- Select2 (ต้องอยู่หลัง jQuery ตัวสุดท้าย) -->
		<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
		<script>
			$(function() {
				if (typeof $.fn.select2 !== 'function') {
					console.error('[select2] not loaded or jQuery overwritten');
					return;
				}
				$('.select2').select2({
					placeholder: "เลือก...",
					allowClear: true,
					width: '100%'
				});
			});
		</script>



		<!-- include vendor scripts used in "Horizontal Menu" page. see "/views//pages/partials/horizontal/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/chart.js/dist/Chart.js"></script>
		<script src="../../Template/ace/node_modules/fullcalendar/main.js"></script>


		<!-- include vendor scripts used in "Dashboard 4" page. see "/views//pages/partials/dashboard-4/@vendor-scripts.hbs" -->
		<!-- <script src="../Template/ace/node_modules/chart.js/dist/Chart.js"></script>

		<script src="../Template/ace/node_modules/fullcalendar/main.js"></script> -->

		<script src="../../Template/ace/node_modules/flot/dist/es5/jquery.flot.js"></script>


		<!-- <script src="../Template/ace/node_modules/chartist/dist/chartist.js"></script> -->


		<!-- <script src="https://www.gstatic.com/charts/loader.js"></script> -->

		<script src="../../Template/ace/node_modules/sortablejs/Sortable.js"></script>

		<!-- include vendor scripts used in "Modals" page. see "/views//pages/partials/modals/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/bootbox/bootbox.all.js"></script>
		<!-- include vendor scripts used in "Calendar" page. see "/views//pages/partials/calendar/@vendor-scripts.hbs" -->
		<!-- <script src="../Template/ace/node_modules/fullcalendar/main.js"></script> -->




		<!-- include vendor scripts used in "Basic Elements" page. see "/views//pages/partials/form-basic/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/autosize/dist/autosize.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-maxlength/dist/bootstrap-maxlength.js"></script>
		<script src="../../Template/ace/node_modules/inputmask/dist/jquery.inputmask.js"></script>
		<script src="../../Template/ace/node_modules/nouislider/distribute/nouislider.js"></script>
		<script src="../../Template/ace/node_modules/ion-rangeslider/js/ion.rangeSlider.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.js"></script>
		<script src="../../Template/ace/node_modules/tiny-date-picker/dist/date-range-picker.js"></script>
		<script src="../../Template/ace/node_modules/moment/moment.js"></script>
		<script src="../../Template/ace/node_modules/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.js"></script>
		<script src="../../Template/ace/node_modules/es6-object-assign/dist/object-assign-auto.js"></script>
		<script src="../../Template/ace/node_modules/@jaames/iro/dist/iro.js"></script>
		<script src="../../Template/ace/node_modules/jquery-knob/dist/jquery.knob.min.js"></script>

		<!-- include vendor scripts used in "Gallery" page. see "/views//pages/partials/gallery/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/photoswipe/dist/photoswipe.js"></script>
		<script src="../../Template/ace/node_modules/photoswipe/dist/photoswipe-ui-default.js"></script>

		<!-- include vendor scripts used in "DataTables" page. see "/views//pages/partials/table-datatables/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/datatables/media/js/jquery.dataTables.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-bs4/js/dataTables.bootstrap4.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-colreorder/js/dataTables.colReorder.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-select/js/dataTables.select.js"></script>


		<script src="../../Template/ace/node_modules/datatables.net-buttons/js/dataTables.buttons.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-buttons-bs4/js/buttons.bootstrap4.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-buttons/js/buttons.html5.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-buttons/js/buttons.print.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-buttons/js/buttons.colVis.js"></script>


		<script src="../../Template/ace/node_modules/datatables.net-responsive/js/dataTables.responsive.js"></script>


		<!-- include vendor scripts used in "More Elements" page. see "/views//pages/partials/form-more/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/bootstrap-star-rating/js/star-rating.js"></script>

		<script src="../../Template/ace/node_modules/typeahead.js/dist/typeahead.bundle.js"></script>

		<script src="../../Template/ace/node_modules/bootstrap-select/dist/js/bootstrap-select.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.js"></script>


		<!-- <script src="../../Template/ace/node_modules/select2/dist/js/select2.js"></script> -->
		<!-- <script src="../../Template/ace/node_modules/chosen-js/chosen.jquery.js"></script> -->


		<script src="../../Template/ace/node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.js"></script>

		<!-- include vendor scripts used in "Profile" page. see "/views//pages/partials/page-profile/@vendor-scripts.hbs" -->


		<script src="../../Template/ace/node_modules/sortablejs/Sortable.js"></script>

		<!-- include vendor scripts used in "Alerts" page. see "/views//pages/partials/alerts/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/sweetalert2/dist/sweetalert2.all.js"></script>
		<script src="../../Template/ace/node_modules/interactjs/dist/interact.js"></script>


		<!-- include vendor scripts used in "Bootstrap Table" page. see "/views//pages/partials/table-bootstrap/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/tableexport.jquery.plugin/tableExport.min.js"></script>


		<!-- "More Elements" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/form-more/@page-script.js"></script>


		<script src="../../Template/ace/node_modules/bootstrap-table/dist/bootstrap-table.min.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-table/dist/extensions/export/bootstrap-table-export.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-table/dist/extensions/print/bootstrap-table-print.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-table/dist/extensions/mobile/bootstrap-table-mobile.js"></script>





		<!-- include ../../Template/ace.js -->
		<script src="../../Template/ace/dist/js/ace.js"></script>


		<!-- demo.js is only for ../Template/ace's demo and you shouldn't use it -->
		<!-- <script src="../Template/ace/app/browser/demo.js"></script> -->



		<!-- "Dashboard 4" page script to enable its demo functionality -->
		<!-- <script src="../Template/ace/views/pages/dashboard-4/@page-script.js"></script> -->



		<!-- "Basic Elements" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/form-basic/@page-script.js"></script>

		<!-- "Icons" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/icons/@page-script.js"></script>

		<!-- "Modals" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/modals/@page-script.js"></script>

		<!-- "Gallery" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/gallery/@page-script.js"></script>
		<!-- "DataTables" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/table-datatables/@page-script.js"></script>

		<!-- "Profile" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/page-profile/@page-script.js"></script>

		<!-- "Alerts" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/alerts/@page-script.js"></script>

		<!-- "Bootstrap Table" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/table-bootstrap/@page-script.js"></script>


		<!-- include vendor scripts used in "File Upload" page. see "/views//pages/partials/form-upload/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/dropzone/dist/dropzone.js"></script>





		<!-- "Calendar" page script to enable its demo functionality -->
		<!-- <script src="../Template/ace/views/pages/calendar/@page-script.js"></script> -->

		<!-- <script src="calendar/@page-script.js"></script> -->

		<!-- "Dashboard" page script to enable its demo functionality -->
		<!-- <script src="../Template/ace/views/pages/dashboard/@page-script.js"></script>
		<script src="../Template/ace/views/pages/dashboard-2/@page-script.js"></script>
		<script src="../Template/ace/views/pages/dashboard-3/@page-script.js"></script>
		<script src="../Template/ace/views/pages/dashboard-4/@page-script.js"></script> -->

		<!-- <script src="../Template/ace/views/pages/charts/@page-script.js"></script> -->
		<!-- <script src="charts/@page-script-data.js"></script> -->
		<!-- <script type="text/javascript" src="assets/js/sweetalert.min.js"></script> -->
		<!-- "Horizontal Menu" page script to enable its demo functionality -->

		<!-- include vendor scripts used in "Wysiwyg & Markdown" page. see "/views//pages/partials/form-wysiwyg/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/summernote/dist/summernote-lite.js"></script>

		<script src="../../Template/ace/node_modules/jquery.hotkeys/jquery.hotkeys.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js"></script>


		<script src="../../Template/ace/node_modules/markdown/lib/markdown.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-markdown/js/bootstrap-markdown.js"></script>
		<script src="../../Template/ace/views/pages/form-wysiwyg/@page-script.js"></script>

		<script src="../../Template/ace/views/pages/dashboard-2/@page-script.js"></script>

		<script src="../../Template/ace/views/pages/form-upload/@page-script.js"></script>




		<script type="text/javascript">
			DecoupledEditor
				.create(document.querySelector('#editor'))
				.then(editor => {
					const toolbarContainer = document.querySelector('#toolbar-container');

					toolbarContainer.appendChild(editor.ui.view.toolbar.element);
				})
				.catch(error => {
					console.error(error);
				});
		</script>

		<script>
			$('#datepicker').datepicker({
				uiLibrary: 'bootstrap'
			});
		</script>

		<script type="text/javascript">
			// datepicker

			// datepicker
			var TinyDatePicker = DateRangePicker.TinyDatePicker;
			TinyDatePicker("#dates_1", {
				mode: "dp-below",
			}).on("statechange", function(ev) {});

			var TinyDatePicker = DateRangePicker.TinyDatePicker;
			TinyDatePicker("#dates_2", {
				mode: "dp-below",
			}).on("statechange", function(ev) {});
			var TinyDatePicker = DateRangePicker.TinyDatePicker;
			TinyDatePicker("#dates_3", {
				mode: "dp-below",
			}).on("statechange", function(ev) {});
			var TinyDatePicker = DateRangePicker.TinyDatePicker;
			TinyDatePicker("#dates_4", {
				mode: "dp-below",
			}).on("statechange", function(ev) {});
		</script>

		<script type="text/javascript">
			function openForm() {
				document.getElementById("myForm").style.display = "block";
			}

			function closeForm() {
				document.getElementById("myForm").style.display = "none";
			}
		</script>
		<?php include __DIR__ . '/../fn/system_feedback_ui.php'; ?>

	</body>

	</html>
<?php  } else {
	// ถ้าเป็น request แบบ AJAX (_ajax/render_) ให้ตอบเป็น JSON เพื่อไม่ให้ client ขึ้น error parse JSON
	$is_post = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
	$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
	$op = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
	$op_ajax = trim((string)$op);
	if ($op_ajax === '' && $is_post && isset($_POST['op'])) {
		$op_ajax = trim((string)$_POST['op']);
	}
	$is_ajax_op = $op_ajax !== '' && (
		(substr($op_ajax, 0, 7) === 'render_')
		|| (substr($op_ajax, -5) === '_ajax')
	);
	if ($is_post && $is_ajax_op && in_array($page, ['project_manage', 'project_approve', 'project_follow', 'project_all'], true)) {
		while (ob_get_level()) {
			@ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => 0, 'msg' => 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	echo "<center><font size=+2></font>กรุณาลองใหม่อีกครั้งค่ะ1</font></center>";
	echo "<meta http-equiv='refresh' content='1; url=../index.php'>";
}
?>