<?php
include("../../../config/connect_db.php");

function getYearSelect($db_connection)
{

    $query = "SELECT * FROM budget_set_year WHERE  budget_year_status = '1' ORDER BY budget_year_name DESC";
    $result = mysqli_query($db_connection, $query);

    $year_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $year_list[] = $row;
        }
    }
    return $year_list; // คืนค่าเป็น array ของหลายแถว

}




// function getStaffAPI($db_connection, $type)
// {
//     $type = mysqli_real_escape_string($db_connection, $type); // ป้องกัน SQL Injection
//     $query = "SELECT * FROM staff INNER JOIN workcurrent ON staff.emailCMU=workcurrent.emailCMU WHERE staff.employeeTypeNameTha='$type'  ORDER BY staff.firstNameTha ASC";
//     $result = mysqli_query($db_connection, $query);

//     $staff_list = [];
//     if ($result && mysqli_num_rows($result) > 0) {
//         while ($row = mysqli_fetch_assoc($result)) {
//             $staff_list[] = $row;
//         }
//     }
//     return $staff_list; // คืนค่าเป็น array ของหลายแถว
// }

// function notifyStaffAndStd($email_send, $staff_send, $subject, $body, $code_eform, $code_std, $level, $db_ecmu19, $dates_times)
// {
//     require '../../vendor-cmu/autoload.php';
//     $phpMailer = new PHPMailer(true);

//     // ส่งอีเมล
//     sendEmailNoReply($phpMailer, $email_send, $subject, $body, "", "");

//     // แจ้งเตือน App Smart
//     $sendTo = $staff_send;
//     $created = "E160041";
//     $notiMessage = strip_tags($body); // ดึงข้อความหลัก
//     $nameApp = "ระบบแบบคำร้องขอทั้วไป E-CMU19";

//     $notify_data = json_encode(array(
//         'action' => "SendNotify",
//         'system_name' => $nameApp,
//         'body_detail' => $notiMessage,
//         'action_url' => '',
//         'send_to' => $sendTo,
//         'created_by' => $created
//     ));
//     sendnotify($notify_data);

//     // mysqli_query($db_ecmu19, "INSERT INTO eform_log_email(code_eform,code_std,email,smart_notify,status,dates,status_level)
//     //         VALUE('$code_eform','$code_std','$email_send','$sendTo','success','$dates_times','$level') ");
// }
