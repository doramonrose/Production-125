<?php
header("Content-Type: application/json; charset=utf-8");
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');

$type       = isset($_POST['type']) ? trim((string)$_POST['type']) : '';
$so         = isset($_POST['so']) ? trim((string)$_POST['so']) : '';
$ap         = isset($_POST['ap']) ? trim((string)$_POST['ap']) : '';
$side       = isset($_POST['side']) ? trim((string)$_POST['side']) : '';
$subprocess = isset($_POST['subprocess']) ? trim((string)$_POST['subprocess']) : '';
$year       = isset($_POST['year']) ? trim((string)$_POST['year']) : '';
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

$selected = [];
if (isset($_POST['selected_ids']) && is_string($_POST['selected_ids'])) {
	$decoded = json_decode($_POST['selected_ids'], true);
	if (is_array($decoded)) {
		foreach ($decoded as $sid) {
			$sid = trim((string)$sid);
			if ($sid !== '') {
				$selected[] = $sid;
			}
		}
	}
} elseif (isset($_POST['selected']) && is_array($_POST['selected'])) {
	foreach ($_POST['selected'] as $sid) {
		$sid = trim((string)$sid);
		if ($sid !== '') {
			$selected[] = $sid;
		}
	}
} elseif ($project_id > 0 && function_exists('getProjectConsistencyRows')) {
	$rows = getProjectConsistencyRows($db_Project, $project_id);
	foreach ($rows as $row) {
		$match = false;
		if ($ap !== '' && (string)($row['code_ap'] ?? '') === $ap) {
			$match = true;
		} elseif ($side !== '' && $ap === '' && (string)($row['code_side'] ?? '') === $side) {
			$rowSp = trim((string)($row['code_subprocess'] ?? ''));
			$match = ($subprocess === '' || $subprocess === '0' || $rowSp === '' || $rowSp === '0' || $rowSp === $subprocess);
		}
		if (!$match) {
			continue;
		}
		$kpiRows = function_exists('getProjectConsistencyKPIDetail')
			? getProjectConsistencyKPIDetail($db_Project, (int)($row['id'] ?? 0))
			: [];
		foreach ($kpiRows as $kr) {
			$kid = trim((string)($kr['code_kpi_detail'] ?? ''));
			if ($kid !== '') {
				$selected[] = $kid;
			}
		}
		break;
	}
}

$filter = [
    "type" => $type,
    "so"   => $so,
    "ap"   => $ap,
    "side" => $side,
    "subprocess" => $subprocess,
    "year" => $year,
];

$kpi_list = getKpiYearIndicatorSelect($db_KPI, $filter);

foreach ($kpi_list as &$item) {
    $item['checked'] = in_array((string)($item['kpi_id'] ?? ''), $selected, true) ? 1 : 0;
}
unset($item);

echo json_encode($kpi_list);
exit;
