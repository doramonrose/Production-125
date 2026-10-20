<?php
/**
 * บันทึกชื่อโครงการจาก wizard (op=save_project)
 * แยกไฟล์เพื่อให้เรียกได้ก่อนส่งออก HTML ของ project_all.php — ป้องกัน JSON ปะปนกับหน้าเว็บทั้งหมด (jQuery parse error)
 */
if (!function_exists('project_all_execute_save_project')) {
	function project_all_execute_save_project()
	{
		global $db_Project, $db_person, $db_pbmBudget;

		if (!function_exists('build_swal_success_redirect')) {
			@include_once(__DIR__ . '/../fn/main_default.php');
		}

		@mysqli_query($db_Project, "CREATE TABLE IF NOT EXISTS project_include_request_action_log (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_id INT UNSIGNED NOT NULL,
			action_type VARCHAR(32) NOT NULL DEFAULT '',
			actor_staff_id INT UNSIGNED NULL,
			sort_order TINYINT UNSIGNED NULL,
			note MEDIUMTEXT NULL,
			meta_json MEDIUMTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_piral_req (request_id, created_at),
			KEY idx_piral_actor (actor_staff_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		if (!function_exists('pm_ensure_midyear_schema')) {
			@include_once(__DIR__ . '/../fn/main_project.php');
		}
		if (function_exists('pm_ensure_midyear_schema')) {
			pm_ensure_midyear_schema($db_Project);
		}
		$midyear_request = isset($_POST['midyear_request']) && (string)$_POST['midyear_request'] === '1';
		$midyear_reason = isset($_POST['midyear_reason']) ? trim((string)$_POST['midyear_reason']) : '';
		$midyear_reason_esc = mysqli_real_escape_string($db_Project, $midyear_reason);

		$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
		$op_from = isset($_POST['op_from']) ? trim((string)$_POST['op_from']) : 'project';
		$include_request_id = isset($_POST['include_request_id']) ? (int)$_POST['include_request_id'] : (isset($_GET['include_request_id']) ? (int)$_GET['include_request_id'] : 0);

		$is_ajax = (!empty($_POST['ajax']) && (string)$_POST['ajax'] === '1')
			|| ($page === 'project_manage' && $op_from === 'add_project');

		if ($is_ajax) {
			while (ob_get_level()) {
				ob_end_clean();
			}
			header('Content-Type: application/json; charset=utf-8');
		}

		$sess_email = mysqli_real_escape_string($db_person, (string)($_SESSION['r_email'] ?? ''));
		$sess_code = mysqli_real_escape_string($db_person, (string)($_SESSION['code'] ?? ''));
		$s_staff = mysqli_query($db_person, "SELECT * FROM staff WHERE email='$sess_email' AND code='$sess_code' LIMIT 1");
		$r_staff = mysqli_fetch_array($s_staff);
		$staff_insert = ($r_staff && isset($r_staff['id'])) ? (int)$r_staff['id'] : 0;

		$status = '1';
		$years_select = isset($_POST['years_select']) ? trim((string)$_POST['years_select']) : '';
		$name_raw = isset($_POST['name_project']) ? (string)$_POST['name_project'] : '';
		$name_project = mysqli_real_escape_string($db_Project, $name_raw);
		$years_esc = mysqli_real_escape_string($db_Project, $years_select);
		$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
		$overview = isset($_POST['overview']) ? trim((string)$_POST['overview']) : '';
		$unit_code_raw = ($r_staff && isset($r_staff['unit_code'])) ? trim((string)$r_staff['unit_code']) : '';
		if ($unit_code_raw !== '' && function_exists('planResolvePreProjectCodeUnit')) {
			$resolved_unit = planResolvePreProjectCodeUnit($db_Project, $unit_code_raw);
			if ($resolved_unit !== '') {
				$unit_code_raw = $resolved_unit;
			}
		}
		$unit_code_esc = mysqli_real_escape_string($db_Project, $unit_code_raw);
		$sql = false;
		$new_id = 0;
		$code_pre_project = '';

		if ($project_id > 0) {
			if (function_exists('pm_project_is_result_report_plan_closed')
				&& pm_project_is_result_report_plan_closed($db_Project, $project_id)) {
				if ($is_ajax) {
					echo json_encode(['ok' => 0, 'msg' => 'โครงการนี้ปิดแล้ว ไม่สามารถแก้ไขได้'], JSON_UNESCAPED_UNICODE);
					exit;
				}
				$link = '?page=' . rawurlencode($page) . '&op=' . rawurlencode($op_from) . '&years_select=' . rawurlencode($years_select);
				echo build_swal_error_redirect($link, 'ไม่สามารถแก้ไขได้', 'โครงการนี้ปิดแล้ว ไม่สามารถแก้ไขได้', 'warning', true);
				exit;
			}
			$st_lock = @mysqli_query($db_Project, "SELECT status_project FROM pre_project WHERE id='" . (int)$project_id . "' LIMIT 1");
			$rw_lock = $st_lock ? mysqli_fetch_assoc($st_lock) : null;
			$stNow = $rw_lock ? (string)($rw_lock['status_project'] ?? '') : '';

			if ($rw_lock && ($stNow !== '0' && $stNow !== '8') && !(defined('PLAN_ADMIN') && PLAN_ADMIN)) {
				$allowByIncludeRequest = false;
				if ($include_request_id > 0 && function_exists('ensureIncludeRequestTables')) {
					ensureIncludeRequestTables($db_Project);
					$qIr = @mysqli_query($db_Project, "SELECT submitter_staff_id, status FROM project_include_request WHERE id='" . (int)$include_request_id . "' LIMIT 1");
					$ir = $qIr ? mysqli_fetch_assoc($qIr) : null;
					if ($ir && (string)($ir['status'] ?? '') === 'rejected') {
						$qIt = @mysqli_query(
							$db_Project,
							"SELECT IFNULL(return_selected,0) AS return_selected, IFNULL(fix_status,'') AS fix_status
							 FROM project_include_request_item
							 WHERE request_id='" . (int)$include_request_id . "'
							   AND project_id='" . (int)$project_id . "'
							 LIMIT 1"
						);
						$itRow = $qIt ? mysqli_fetch_assoc($qIt) : null;
						$inReq = (bool)$itRow;
						$stillPendingFix = $inReq
							&& (int)($itRow['return_selected'] ?? 0) === 1
							&& in_array(trim((string)($itRow['fix_status'] ?? '')), ['', 'pending'], true);
						if ($inReq && $stillPendingFix) {
							$isSubmitter = ((int)($ir['submitter_staff_id'] ?? 0) === (int)$staff_insert);
							$canOwner = function_exists('staffCanEditPreProjectRichFields')
								? staffCanEditPreProjectRichFields($db_Project, (int)$project_id, (int)$staff_insert, (string)($r_staff['code'] ?? ''), (string)($r_staff['unit_code'] ?? ''))
								: false;
							if ($isSubmitter || $canOwner) {
								$allowByIncludeRequest = true;
							}
						}
					}
				}
				if ($allowByIncludeRequest) {
					@mysqli_query($db_Project, "UPDATE pre_project SET status_project='8', dates=NOW() WHERE id='" . (int)$project_id . "' LIMIT 1");
				} else {
					if ($is_ajax) {
						echo json_encode(['ok' => 0, 'msg' => 'โครงการอยู่ในสถานะที่ไม่สามารถแก้ไขได้'], JSON_UNESCAPED_UNICODE);
						exit;
					}
					$link = '?page=' . rawurlencode($page) . '&op=' . rawurlencode($op_from) . '&years_select=' . rawurlencode($years_select);
					echo build_swal_error_redirect($link, 'ไม่สามารถแก้ไขได้', 'โครงการอยู่ในสถานะที่ไม่สามารถแก้ไขได้', 'warning', true);
					exit;
				}
			}
			if (function_exists('pm_project_allows_kpi_result_fill') && pm_project_allows_kpi_result_fill($db_Project, $project_id)) {
				$qCurName = @mysqli_query($db_Project, "SELECT name_project FROM pre_project WHERE id='" . (int)$project_id . "' LIMIT 1");
				$rowCurName = $qCurName ? mysqli_fetch_assoc($qCurName) : null;
				$curName = trim((string)($rowCurName['name_project'] ?? ''));
				$newName = trim($name_raw);
				if ($curName !== $newName) {
					if ($is_ajax) {
						echo json_encode(['ok' => 0, 'msg' => 'โครงการที่ได้รับอนุมัติแล้ว ไม่สามารถแก้ชื่อโครงการได้'], JSON_UNESCAPED_UNICODE);
						exit;
					}
					$link = '?page=' . rawurlencode($page) . '&op=' . rawurlencode($op_from) . '&years_select=' . rawurlencode($years_select);
					echo build_swal_error_redirect($link, 'ไม่สามารถแก้ไขได้', 'โครงการที่ได้รับอนุมัติแล้ว ไม่สามารถแก้ชื่อโครงการได้', 'warning', true);
					exit;
				}
				$name_project = mysqli_real_escape_string($db_Project, $curName);
			}
			$sql = mysqli_query(
				$db_Project,
				"UPDATE pre_project SET name_project='$name_project'"
					. (isset($_POST['midyear_reason']) ? ", midyear_reason='$midyear_reason_esc'" : '')
					. " WHERE id='$project_id' LIMIT 1"
			);
			if (!empty($include_request_id) && $include_request_id > 0) {
				$noteLog = mysqli_real_escape_string($db_Project, trim((string)$name_raw));
				$metaLog = mysqli_real_escape_string($db_Project, json_encode(['project_id' => (int)$project_id], JSON_UNESCAPED_UNICODE));
				@mysqli_query(
					$db_Project,
					"INSERT INTO project_include_request_action_log (request_id, action_type, actor_staff_id, note, meta_json, created_at)
					 VALUES ('" . (int)$include_request_id . "', 'project_edited', '" . (int)$staff_insert . "', '".$noteLog."', '".$metaLog."', NOW())"
				);
			}
			$new_id = $project_id;
		} else {
			if ($years_select === '' || trim($name_raw) === '') {
				$sql = false;
			} else {
				$year_short = substr($years_select, -2);
				$prefix = 'S' . $year_short;
				$prefix_esc = mysqli_real_escape_string($db_Project, $prefix);

				$sql_last = mysqli_query($db_Project, "
						SELECT code_pre_project 
						FROM pre_project 
						WHERE code_pre_project LIKE '$prefix_esc-%' 
						ORDER BY id DESC LIMIT 1
					");
				$last_code = mysqli_fetch_assoc($sql_last);

				if ($last_code && isset($last_code['code_pre_project'])) {
					$last_num = (int)substr($last_code['code_pre_project'], -3);
					$new_num = str_pad((string)($last_num + 1), 3, '0', STR_PAD_LEFT);
				} else {
					$new_num = '001';
				}
				$code_pre_project = $prefix . '-' . $new_num;
				$code_esc = mysqli_real_escape_string($db_Project, $code_pre_project);

				$sql = mysqli_query(
					$db_Project,
					"INSERT INTO pre_project (code_pre_project, name_project, status, status_project, year_project, staff_insert, code_unit, dates, midyear_request, midyear_reason) 
   				 VALUES ('$code_esc', '$name_project', '$status', '0', '$years_esc', '$staff_insert', '$unit_code_esc', NOW(), '" . ($midyear_request ? '1' : '0') . "', '$midyear_reason_esc')"
				);
				if ($sql) {
					$new_id = (int)mysqli_insert_id($db_Project);
				}
			}
		}

		if ($is_ajax) {
			if ($sql) {
				$redirect = '?page=' . rawurlencode($page)
					. '&op=' . rawurlencode($op_from)
					. '&project_id=' . $new_id
					. '&years_select=' . rawurlencode($years_select)
					. '&overview=' . rawurlencode($overview)
					. ($midyear_request ? '&midyear=1&project_group=plan' : '');
				echo json_encode(['ok' => 1, 'redirect' => $redirect], JSON_UNESCAPED_UNICODE);
			} else {
				$db_err = mysqli_error($db_Project);
				echo json_encode([
					'ok' => 0,
					'msg' => $db_err !== '' ? $db_err : 'ไม่สามารถบันทึกข้อมูลได้',
				], JSON_UNESCAPED_UNICODE);
			}
			exit;
		}

		if ($sql) {
			if ($project_id > 0) {
				$link = '?page=' . $page . '&op=' . $op_from . '&project_id=' . $new_id . '&years_select=' . rawurlencode($years_select) . '&overview=' . rawurlencode($overview);
			} else {
				$link = '?page=' . $page . '&op=project_detail&code_pre_project=' . $code_pre_project . '&years_select=' . $years_select;
			}
			echo build_swal_success_redirect($link);
			exit;
		}
		$link = '?page=' . $page . '&op=' . $op_from . '&years_select=' . $years_select;
		echo build_swal_error_redirect($link);
		exit;
	}
}
