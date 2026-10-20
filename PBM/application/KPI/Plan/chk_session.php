<?php
session_start();
include("../../../config/connect_db.php");
ini_set('date.timezone', 'Asia/Bangkok');
$login_date = date("Y-m-d H:i:s");

$_SESSION['hash_staff'] = $_GET['hash_staff'];  // Staff

$s_staff = mysqli_query($db_person, "select * from staff where work_status='1' or work_status='0'");
while ($r_staff = mysqli_fetch_array($s_staff)) {
    $hash_STAFF = $r_staff['code'] . "" . $r_staff['email'];
    $hash_data_staff = hash('sha512', $hash_STAFF);
    if ($_SESSION['hash_staff'] == $hash_data_staff) {
        $_SESSION['code'] = $r_staff['code'];
        $_SESSION['r_email'] = $r_staff['email'];
    }
}

if (isset($_SESSION['hash_staff'])) {


    $s_staff = mysqli_query($db_person, "select * from staff where  code='$_SESSION[code]' and email='$_SESSION[r_email]'");
    if ($num = mysqli_num_rows($s_staff)) {
        if ($row_staff = mysqli_fetch_array($s_staff)) {

            $s_staff_mis = mysqli_query($db_person, "select * from staff_mis where  code='$row_staff[code]' and person='1'");
            $r_staff_mis = mysqli_fetch_array($s_staff_mis);

            $_SESSION['r_email'] = $row_staff['email'];
            $_SESSION['code'] = $row_staff['code'];
            $date_now = date("Y-m-d H:i:s");


            $ip = $_SERVER['REMOTE_ADDR'];

            $devices = "Person Database";

            $s_log = mysqli_query($db_person, "INSERT INTO log_login (staff,ip,dates_login,devices)values('$r_staff[code]','$ip','$date_now','$devices') ");


            header('location: page.php');
        }
    } else {
        echo "<center><b>คุณไม่สามารถเข้ามาในส่วนนี้ได้ค่ะ SOS</b>";
        // echo "<meta http-equiv='refresh' content='2; url=index.php'>";
    }
}
