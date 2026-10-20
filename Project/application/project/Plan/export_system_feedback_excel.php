<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

include_once __DIR__ . '/../../config/config_db.php';
include_once __DIR__ . '/../fn/main_project.php';

$sess_email = (string)($_SESSION['r_email'] ?? '');
$sess_code = (string)($_SESSION['code'] ?? '');
if ($sess_email === '' || $sess_code === '' || !isset($db_person) || !($db_person instanceof mysqli)) {
	http_response_code(403);
	exit('ไม่มีสิทธิ์เข้าถึง');
}

$stMe = mysqli_prepare($db_person, 'SELECT id FROM staff WHERE email = ? AND code = ? LIMIT 1');
if (!$stMe) {
	http_response_code(500);
	exit('ตรวจสอบสิทธิ์ไม่สำเร็จ');
}
mysqli_stmt_bind_param($stMe, 'ss', $sess_email, $sess_code);
mysqli_stmt_execute($stMe);
$resMe = mysqli_stmt_get_result($stMe);
$me = $resMe ? mysqli_fetch_assoc($resMe) : null;
if (!$me) {
	http_response_code(403);
	exit('ไม่พบผู้ใช้');
}

$autoload = __DIR__ . '/../../vendor-excel/autoload.php';
if (!is_file($autoload)) {
	http_response_code(500);
	exit('ไม่พบไลบรารี Excel');
}
require_once $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
	$rep = function_exists('pm_load_system_feedback_report')
		? pm_load_system_feedback_report($db_Project ?? null, 0)
		: ['total' => 0, 'avg' => 0, 'stars' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], 'cnt_user' => 0, 'cnt_plan' => 0, 'cnt_comment' => 0, 'rows' => []];

	$spreadsheet = new Spreadsheet();
	$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(12);

	$headerStyle = [
		'font' => ['bold' => true, 'color' => ['rgb' => '4A3263'], 'size' => 12],
		'fill' => [
			'fillType' => Fill::FILL_SOLID,
			'startColor' => ['rgb' => 'F3EEF8'],
		],
		'borders' => [
			'allBorders' => [
				'borderStyle' => Border::BORDER_THIN,
				'color' => ['rgb' => 'E8DEF2'],
			],
		],
	];
	$cellBorder = [
		'borders' => [
			'allBorders' => [
				'borderStyle' => Border::BORDER_THIN,
				'color' => ['rgb' => 'E8DEF2'],
			],
		],
	];

	$sheet1 = $spreadsheet->getActiveSheet();
	$sheet1->setTitle('Summary');
	$sheet1->setCellValue('A1', 'รายงานความพึงพอใจต่อการใช้งานระบบ');
	$sheet1->mergeCells('A1:B1');
	$sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('4A3263');

	$sheet1->setCellValue('A3', 'รายการ');
	$sheet1->setCellValue('B3', 'ค่า');
	$sheet1->getStyle('A3:B3')->applyFromArray($headerStyle);

	$summary = [
		['คะแนนเฉลี่ย / 5', ((int)($rep['total'] ?? 0) > 0 ? number_format((float)($rep['avg'] ?? 0), 2) : '-')],
		['จำนวนผู้ประเมิน', (int)($rep['total'] ?? 0)],
		['User', (int)($rep['cnt_user'] ?? 0)],
		['Plan', (int)($rep['cnt_plan'] ?? 0)],
		['มีข้อเสนอแนะ', (int)($rep['cnt_comment'] ?? 0)],
		['5 ดาว', (int)(($rep['stars'][5] ?? 0))],
		['4 ดาว', (int)(($rep['stars'][4] ?? 0))],
		['3 ดาว', (int)(($rep['stars'][3] ?? 0))],
		['2 ดาว', (int)(($rep['stars'][2] ?? 0))],
		['1 ดาว', (int)(($rep['stars'][1] ?? 0))],
	];
	$row = 4;
	foreach ($summary as $item) {
		$sheet1->setCellValue('A' . $row, $item[0]);
		$sheet1->setCellValue('B' . $row, $item[1]);
		$row++;
	}
	$lastSum = $row - 1;
	$sheet1->getStyle('A3:B' . $lastSum)->applyFromArray($cellBorder);
	$sheet1->getColumnDimension('A')->setWidth(28);
	$sheet1->getColumnDimension('B')->setWidth(18);

	$sheet2 = $spreadsheet->createSheet();
	$sheet2->setTitle('Feedback');
	$sheet2->setCellValue('A1', 'วันที่');
	$sheet2->setCellValue('B1', 'ผู้ประเมิน');
	$sheet2->setCellValue('C1', 'บทบาท');
	$sheet2->setCellValue('D1', 'คะแนน');
	$sheet2->setCellValue('E1', 'ข้อเสนอแนะ');
	$sheet2->setCellValue('F1', 'หน้า');
	$sheet2->getStyle('A1:F1')->applyFromArray($headerStyle);

	$r = 2;
	foreach (($rep['rows'] ?? []) as $fbRow) {
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
		$page = trim((string)($fbRow['page_name'] ?? ''));

		$sheet2->setCellValue('A' . $r, $dtShow !== '' ? $dtShow : '-');
		$sheet2->setCellValue('B' . $r, $name);
		$sheet2->setCellValue('C' . $r, $role);
		$sheet2->setCellValue('D' . $r, $rt > 0 ? ($rt . ' ดาว') : '-');
		$sheet2->setCellValue('E' . $r, $cmt !== '' ? $cmt : '-');
		$sheet2->setCellValue('F' . $r, $page !== '' ? $page : '-');
		$r++;
	}
	$lastData = max(1, $r - 1);
	$sheet2->getStyle('A1:F' . $lastData)->applyFromArray($cellBorder);
	$sheet2->getStyle('A1:F' . $lastData)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
	if ($lastData >= 2) {
		$sheet2->getStyle('E2:E' . $lastData)->getAlignment()->setWrapText(true);
	}
	$sheet2->getColumnDimension('A')->setWidth(20);
	$sheet2->getColumnDimension('B')->setWidth(32);
	$sheet2->getColumnDimension('C')->setWidth(12);
	$sheet2->getColumnDimension('D')->setWidth(12);
	$sheet2->getColumnDimension('E')->setWidth(50);
	$sheet2->getColumnDimension('F')->setWidth(22);
	$sheet2->freezePane('A2');

	$spreadsheet->setActiveSheetIndex(0);

	$filename = 'system_feedback_' . date('Ymd_His') . '.xlsx';
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Cache-Control: max-age=0');

	$writer = new Xlsx($spreadsheet);
	$writer->save('php://output');
	exit;
} catch (Throwable $e) {
	$rep = $rep ?? ['total' => 0, 'avg' => 0, 'stars' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], 'cnt_user' => 0, 'cnt_plan' => 0, 'cnt_comment' => 0, 'rows' => []];
	$h = static function ($s) {
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	};
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	$filename = 'system_feedback_' . date('Ymd_His') . '.xls';
	header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	echo "\xEF\xBB\xBF";
	echo '<html><head><meta charset="UTF-8"></head><body>';
	echo '<h3>รายงานความพึงพอใจต่อการใช้งานระบบ</h3>';
	echo '<table border="1"><tr><th>รายการ</th><th>ค่า</th></tr>';
	echo '<tr><td>คะแนนเฉลี่ย / 5</td><td>' . $h(((int)($rep['total'] ?? 0) > 0 ? number_format((float)($rep['avg'] ?? 0), 2) : '-')) . '</td></tr>';
	echo '<tr><td>จำนวนผู้ประเมิน</td><td>' . (int)($rep['total'] ?? 0) . '</td></tr>';
	echo '<tr><td>User</td><td>' . (int)($rep['cnt_user'] ?? 0) . '</td></tr>';
	echo '<tr><td>Plan</td><td>' . (int)($rep['cnt_plan'] ?? 0) . '</td></tr>';
	echo '<tr><td>มีข้อเสนอแนะ</td><td>' . (int)($rep['cnt_comment'] ?? 0) . '</td></tr>';
	echo '</table><br>';
	echo '<table border="1"><tr><th>วันที่</th><th>ผู้ประเมิน</th><th>บทบาท</th><th>คะแนน</th><th>ข้อเสนอแนะ</th><th>หน้า</th></tr>';
	foreach (($rep['rows'] ?? []) as $fbRow) {
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
		$cmt = trim((string)($fbRow['comment'] ?? ''));
		$rt = (int)($fbRow['rating'] ?? 0);
		$page = trim((string)($fbRow['page_name'] ?? ''));
		echo '<tr>';
		echo '<td>' . $h($dtShow !== '' ? $dtShow : '-') . '</td>';
		echo '<td>' . $h($name !== '' ? $name : '-') . '</td>';
		echo '<td>' . $h($role) . '</td>';
		echo '<td>' . $h($rt > 0 ? ($rt . ' ดาว') : '-') . '</td>';
		echo '<td>' . $h($cmt !== '' ? $cmt : '-') . '</td>';
		echo '<td>' . $h($page !== '' ? $page : '-') . '</td>';
		echo '</tr>';
	}
	echo '</table></body></html>';
	exit;
}
