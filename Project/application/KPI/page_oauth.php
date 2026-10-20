<?php
ob_start();
session_start();
if (!isset($_SESSION['cmuitaccount'])) {
	//	if (!isset($_SESSION['firstname']) || (trim($_SESSION['firstname']) == '')) {
	if (!empty($_SESSION['pbm_project_side'])) {
		unset($_SESSION['pbm_project_side']);
		header('Location: /application/project/index.php?error=' . urlencode('เข้าสู่ระบบด้วย CMU ไม่สำเร็จ'));
		exit();
	}
	header("location: index.php");
	exit();
}

$session_prefix = $_SESSION['prefix'];
$session_fname = $_SESSION['firstname'];
$session_lname = $_SESSION['lastname'];
$session_cmuitaccount = $_SESSION['cmuitaccount'];
$fullname = $session_prefix . ' ' . $session_fname . ' ' . $session_lname;
//echo $fullname;

include("../../config/connect_db.php");

$sql = "select * from staff where  email='$session_cmuitaccount' ";
$result = mysqli_query($db_person, $sql);

if ($n_num = mysqli_num_rows($result)) {

	$row = mysqli_fetch_array($result);
	$email = $session_cmuitaccount;
	$code_user = $row['code'];

	#session-oauth-cmu
	$_SESSION['r_email'] = $session_cmuitaccount;
	$_SESSION['code'] = $row['code'];
	if (isset($row['hash_pw'])) {
		$_SESSION['hash_pw'] = $row['hash_pw'];
	}

	$projectSide = isset($_SESSION['pbm_project_side']) ? (string) $_SESSION['pbm_project_side'] : '';
	if ($projectSide === 'user' || $projectSide === 'plan') {
		unset($_SESSION['pbm_project_side']);
		$workStatus = (string) ($row['work_status'] ?? '');
		if ($workStatus !== '0' && $workStatus !== '1') {
			header('Location: /application/project/index.php?error=' . urlencode('บัญชีนี้ไม่ได้อยู่ในสถานะปฏิบัติงาน จึงไม่สามารถเข้าใช้งานได้'));
			exit();
		}
		$date_now = date("Y-m-d H:i:s");
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$devices = $projectSide === 'plan' ? 'ENTRA_ID_PROJECT_PLAN' : 'ENTRA_ID_PROJECT_USER';
		$logStmt = mysqli_prepare($db_person, 'INSERT INTO log_login (staff, ip, dates_login, devices) VALUES (?, ?, ?, ?)');
		if ($logStmt) {
			mysqli_stmt_bind_param($logStmt, 'ssss', $row['code'], $ip, $date_now, $devices);
			mysqli_stmt_execute($logStmt);
			mysqli_stmt_close($logStmt);
		}
		$dest = $projectSide === 'plan'
			? '/application/project/Plan/page.php'
			: '/application/project/User/page.php';
		header('Location: ' . $dest);
		exit();
	}

	$date_now = date("Y-m-d H:i:s");
	$ip = $_SERVER['REMOTE_ADDR'];
	$devices = "ENTRA_ID_KPI";

	$s_log = mysqli_query($db_KPI, "INSERT INTO log_login(staff,ip,dates_login,devices)values('$row[code]','$ip','$date_now','$devices') ");
	if ($row['job_code'] == "120103") {  // งานแผน+Admin
		echo $row['job_code'];
		echo "<meta http-equiv='refresh' content='0; url=Plan/page.php'>";
	} else {
		echo "<meta http-equiv='refresh' content='0; url=page.php'>";
	}
} else {
	if (!empty($_SESSION['pbm_project_side'])) {
		unset($_SESSION['pbm_project_side']);
		header('Location: /application/project/index.php?error=' . urlencode('ไม่พบบัญชีนี้ในระบบ กรุณาติดต่อผู้ดูแลระบบ'));
		exit();
	}
	echo "ท่านไม่มีสิทธิ์ค่ะ";
	echo "<meta http-equiv='refresh' content='2; url=index.php'>";
}
