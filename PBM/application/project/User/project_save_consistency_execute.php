<?php
/**
 * บันทึกความสอดคล้อง ส่วนที่ 2 (op=save_data_consistency_kpi_project)
 * แยกไฟล์เพื่อเรียกก่อนส่ง HTML ของ project_all.php — กัน jQuery parse JSON ไม่ได้ทั้งที่บันทึกสำเร็จ
 */
if (!function_exists('project_all_execute_save_consistency_kpi')) {
	function project_all_execute_save_consistency_kpi(): void
	{
		global $db_Project, $db_person, $db_KPI;

		if (!function_exists('build_swal_success_redirect')) {
			@include_once(__DIR__ . '/../fn/main_default.php');
		}
		if (!function_exists('getSetType') || !function_exists('pm_sdgs_consistency_enabled')) {
			@include_once(__DIR__ . '/../fn/main_project.php');
		}
		if (!function_exists('kpi_type_child_kind')) {
			@include_once(__DIR__ . '/../../KPI/fn/kpi_type_year.php');
		}

		$consistency_is_ajax = !empty($_POST['ajax']) && (string)$_POST['ajax'] === '1';
		if ($consistency_is_ajax) {
			while (ob_get_level()) {
				ob_end_clean();
			}
			header('Content-Type: application/json; charset=utf-8');
		}

		$sessEmail = (string)($_SESSION['r_email'] ?? '');
		$sessCode = (string)($_SESSION['code'] ?? '');
		$emailEsc = mysqli_real_escape_string($db_person, $sessEmail);
		$codeEsc = mysqli_real_escape_string($db_person, $sessCode);
		$s_staff = mysqli_query($db_person, "SELECT * FROM staff WHERE email='{$emailEsc}' AND code='{$codeEsc}' LIMIT 1");
		$r_staff = $s_staff ? mysqli_fetch_array($s_staff) : null;

		$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
		$op_from = isset($_POST['op_from']) ? trim((string)$_POST['op_from']) : '';
		$years_select = isset($_POST['years_select']) ? trim((string)$_POST['years_select']) : '';
		$overview = isset($_POST['overview']) ? (string)$_POST['overview'] : '';
		$project_id = (int)($_POST['project_id'] ?? 0);
		$code_kpi_type = trim((string)($_POST['code_kpi_type'] ?? ''));
		$staffId = (int)($r_staff['id'] ?? 0);
		$pidEsc = mysqli_real_escape_string($db_Project, (string)$project_id);
		$typeEsc = mysqli_real_escape_string($db_Project, $code_kpi_type);
		$staffEsc = mysqli_real_escape_string($db_Project, (string)$staffId);
		$detail_list = $_POST['consistency_detail'] ?? [];
		if (!is_array($detail_list)) {
			$detail_list = [];
		}
		$sdgs_enabled = function_exists('pm_sdgs_consistency_enabled') ? pm_sdgs_consistency_enabled($db_Project) : true;
		if (!$sdgs_enabled) {
			$detail_list = [];
		}

		$childKindSave = 'side';
		if ($code_kpi_type !== '' && isset($db_KPI) && function_exists('getSetType')) {
			$tRowSave = getSetType($db_KPI, $code_kpi_type);
			if (is_array($tRowSave) && function_exists('kpi_type_child_kind')) {
				$childKindSave = kpi_type_child_kind($tRowSave);
			} elseif (is_array($tRowSave)) {
				$childKindSave = ((string)($tRowSave['child_kind'] ?? '') === 'so') ? 'so' : 'side';
			}
		}

		$setsSave = [];
		$rawSets = $_POST['sets'] ?? null;
		if (is_array($rawSets)) {
			foreach ($rawSets as $rowSet) {
				if (!is_array($rowSet)) {
					continue;
				}
				$ap = trim((string)($rowSet['code_ap'] ?? ''));
				$side = trim((string)($rowSet['code_side'] ?? ''));
				$subprocess = trim((string)($rowSet['code_subprocess'] ?? ''));
				$kpiOther = trim((string)($rowSet['kpi_other'] ?? ''));
				if (function_exists('mb_substr')) {
					$kpiOther = mb_substr($kpiOther, 0, 500);
				} else {
					$kpiOther = substr($kpiOther, 0, 500);
				}
				$kpis = $rowSet['kpi_list'] ?? [];
				if (!is_array($kpis)) {
					$kpis = [];
				}
				$kpis = array_values(array_filter(array_map('strval', $kpis), static function ($v) {
					return trim($v) !== '';
				}));
				if ($childKindSave === 'so') {
					if ($ap === '') {
						continue;
					}
					$setsSave[] = ['code_ap' => $ap, 'code_side' => '', 'code_subprocess' => '', 'kpi_other' => $kpiOther, 'kpi_list' => $kpis];
				} else {
					if ($side === '') {
						continue;
					}
					$setsSave[] = ['code_ap' => '', 'code_side' => $side, 'code_subprocess' => $subprocess, 'kpi_other' => $kpiOther, 'kpi_list' => $kpis];
				}
			}
		}
		if (!$setsSave) {
			$ap = trim((string)($_POST['code_ap'] ?? ''));
			$side = trim((string)($_POST['code_side'] ?? ''));
			$kpis = $_POST['kpi_list'] ?? [];
			if (!is_array($kpis)) {
				$kpis = [];
			}
			$kpis = array_values(array_filter(array_map('strval', $kpis), static function ($v) {
				return trim($v) !== '';
			}));
			if ($childKindSave === 'so' && $ap !== '') {
				$setsSave[] = ['code_ap' => $ap, 'code_side' => '', 'code_subprocess' => '', 'kpi_list' => $kpis];
			} elseif ($childKindSave !== 'so' && $side !== '') {
				$setsSave[] = ['code_ap' => '', 'code_side' => $side, 'code_subprocess' => trim((string)($_POST['code_subprocess'] ?? '')), 'kpi_list' => $kpis];
			}
		}

		$type = isset($_POST['type']) ? (string)$_POST['type'] : '';
		$sql = false;
		$failMsg = 'บันทึกความสอดคล้องไม่สำเร็จ';

		if ($type === 'insert' || $type === 'update') {
			if ($code_kpi_type === '' || !$setsSave) {
				if ($consistency_is_ajax) {
					echo json_encode(['ok' => 0, 'msg' => 'กรุณาเลือกกรอบแผนและรายการอย่างน้อย 1 ชุด'], JSON_UNESCAPED_UNICODE);
					exit;
				}
				$link = '?page=' . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
				echo build_swal_error_redirect($link);
				return;
			}

			$oldIds = [];
			$qOld = mysqli_query($db_Project, "SELECT id FROM project_consistency WHERE project_id='$pidEsc' AND status!='99'");
			if ($qOld) {
				while ($or = mysqli_fetch_assoc($qOld)) {
					$oid = (int)($or['id'] ?? 0);
					if ($oid > 0) {
						$oldIds[] = $oid;
					}
				}
			}
			mysqli_query(
				$db_Project,
				"UPDATE project_consistency SET status='99', staff_insert='$staffEsc', dates=NOW() WHERE project_id='$pidEsc' AND status!='99'"
			);
			if ($oldIds) {
				$inOld = implode(',', $oldIds);
				mysqli_query($db_Project, "UPDATE project_consistency_kpi SET status='99', staff_insert='$staffEsc', dates=NOW() WHERE code_project_consistency IN ($inOld)");
				mysqli_query($db_Project, "UPDATE project_consistency_map SET status='99', staff_insert='$staffEsc', dates=NOW() WHERE code_project_consistency IN ($inOld)");
			}

			$sql = true;
			$firstNewId = 0;
			if (function_exists('pm_ensure_project_consistency_subprocess')) {
				pm_ensure_project_consistency_subprocess($db_Project);
			}
			if (function_exists('pm_ensure_project_consistency_kpi_other')) {
				pm_ensure_project_consistency_kpi_other($db_Project);
			}
			$hasSubCol = false;
			$qSubCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_consistency LIKE 'code_subprocess'");
			$hasSubCol = ($qSubCol && mysqli_num_rows($qSubCol) > 0);
			$hasOtherCol = false;
			$qOtherCol = @mysqli_query($db_Project, "SHOW COLUMNS FROM project_consistency LIKE 'kpi_other'");
			$hasOtherCol = ($qOtherCol && mysqli_num_rows($qOtherCol) > 0);
			foreach ($setsSave as $oneSet) {
				$apEsc = mysqli_real_escape_string($db_Project, (string)$oneSet['code_ap']);
				$sideEsc = mysqli_real_escape_string($db_Project, (string)$oneSet['code_side']);
				$subEsc = mysqli_real_escape_string($db_Project, (string)($oneSet['code_subprocess'] ?? ''));
				$otherEsc = mysqli_real_escape_string($db_Project, (string)($oneSet['kpi_other'] ?? ''));
				$subSqlCol = $hasSubCol ? ', code_subprocess' : '';
				$subSqlVal = $hasSubCol ? (", '" . $subEsc . "'") : '';
				$otherSqlCol = $hasOtherCol ? ', kpi_other' : '';
				$otherSqlVal = $hasOtherCol ? (", '" . $otherEsc . "'") : '';
				$ins = mysqli_query(
					$db_Project,
					"INSERT INTO project_consistency (
						project_id, code_kpi_type, code_so, code_ap, code_side{$subSqlCol}{$otherSqlCol}, status, staff_insert, dates
					) VALUES (
						'$pidEsc', '$typeEsc', '', '$apEsc', '$sideEsc'{$subSqlVal}{$otherSqlVal}, '1', '$staffEsc', NOW()
					)"
				);
				if (!$ins) {
					$sql = false;
					$failMsg = mysqli_error($db_Project) ?: $failMsg;
					break;
				}
				$newId = (int)mysqli_insert_id($db_Project);
				if ($newId <= 0) {
					$sql = false;
					break;
				}
				if ($firstNewId <= 0) {
					$firstNewId = $newId;
				}
				foreach ($oneSet['kpi_list'] as $kpi_id) {
					$kpiEsc = mysqli_real_escape_string($db_Project, trim((string)$kpi_id));
					if ($kpiEsc === '') {
						continue;
					}
					mysqli_query(
						$db_Project,
						"INSERT INTO project_consistency_kpi(
							code_project_consistency, code_kpi_detail, status, staff_insert, dates
						) VALUES (
							'$newId', '$kpiEsc', '1', '$staffEsc', NOW()
						)"
					);
				}
			}
			if ($sql && $sdgs_enabled && $firstNewId > 0) {
				foreach ($detail_list as $detail_id) {
					$detail_id = trim((string)$detail_id);
					if ($detail_id === '') {
						continue;
					}
					$detailEsc = mysqli_real_escape_string($db_Project, $detail_id);
					mysqli_query(
						$db_Project,
						"INSERT INTO project_consistency_map(
							code_project_consistency, code_consistency_detail, status, staff_insert, dates
						) VALUES (
							'$firstNewId', '$detailEsc', '1', '$staffEsc', NOW()
						)"
					);
				}
			}
		} elseif ($type === 'delete') {
			$project_consistency_id = (int)($_POST['project_consistency_id'] ?? 0);
			$sql = mysqli_query(
				$db_Project,
				"UPDATE project_consistency
					SET status = '99', staff_insert = '$staffEsc', dates = NOW()
					WHERE project_id = '$pidEsc'"
			);
			if ($sql && $project_consistency_id > 0) {
				mysqli_query(
					$db_Project,
					"UPDATE project_consistency_kpi
					 SET status = '99', staff_insert = '$staffEsc', dates = NOW()
					 WHERE code_project_consistency = '$project_consistency_id'"
				);
				mysqli_query(
					$db_Project,
					"UPDATE project_consistency_map
					 SET status = '99', staff_insert = '$staffEsc', dates = NOW()
					 WHERE code_project_consistency = '$project_consistency_id'"
				);
			}
		}

		if ($consistency_is_ajax) {
			if ($sql) {
				$link = '?page=' . rawurlencode($page) . '&op=' . rawurlencode($op_from) . '&project_id=' . (int)$project_id . '&years_select=' . rawurlencode($years_select) . '&overview=' . rawurlencode($overview);
				echo json_encode(['ok' => 1, 'redirect' => $link], JSON_UNESCAPED_UNICODE);
			} else {
				$em = mysqli_error($db_Project);
				echo json_encode([
					'ok' => 0,
					'msg' => $em !== '' ? $em : $failMsg,
				], JSON_UNESCAPED_UNICODE);
			}
			exit;
		}

		$link = '?page=' . $page . '&op=' . $op_from . '&project_id=' . $project_id . '&years_select=' . $years_select . '&overview=' . $overview;
		if ($sql) {
			echo build_swal_success_redirect($link);
		} else {
			echo build_swal_error_redirect($link);
		}
	}
}
