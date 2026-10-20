<?php
include_once(__DIR__ . '/../../config/config_db.php');
function getDuty($db_connection, $duty_code)
{
    $duty_code = mysqli_real_escape_string($db_connection, $duty_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM duty WHERE code = '$duty_code'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getDutySelect($db_connection)
{

    $query = "SELECT * FROM duty ORDER BY name ASC";
    $result = mysqli_query($db_connection, $query);

    $duty_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $duty_list[] = $row;
        }
    }
    return $duty_list; // คืนค่าเป็น array ของหลายแถว
}
function getPrefix($db_connection, $prefix_code)
{
    $prefix_code = mysqli_real_escape_string($db_connection, $prefix_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM prefix WHERE code = '$prefix_code'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getPrefixSelect($db_connection)
{

    $query = "SELECT * FROM prefix ORDER BY name ASC";
    $result = mysqli_query($db_connection, $query);

    $prefix_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $prefix_list[] = $row;
        }
    }
    return $prefix_list; // คืนค่าเป็น array ของหลายแถว
}
function getPosition($db_connection, $position_code)
{
    $position_code = mysqli_real_escape_string($db_connection, $position_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM position WHERE code = '$position_code'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getPositionSelect($db_connection)
{

    $query = "SELECT * FROM position  ORDER BY name ASC";
    $result = mysqli_query($db_connection, $query);

    $position_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $position_list[] = $row;
        }
    }
    return $position_list; // คืนค่าเป็น array ของหลายแถว
}
function getPositionDean($db_connection, $position_dean)
{
    $position_dean = mysqli_real_escape_string($db_connection, $position_dean); // ป้องกัน SQL Injection
    $query = "SELECT * FROM position_dean WHERE code = '$position_dean'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getPositionDeanSelect($db_connection)
{

    $query = "SELECT * FROM position_dean WHERE acttive='1' ORDER BY level ASC";
    $result = mysqli_query($db_connection, $query);

    $position_dean_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $position_dean_list[] = $row;
        }
    }
    return $position_dean_list; // คืนค่าเป็น array ของหลายแถว
}
function getDepartment($db_connection, $dep_code)
{
    $dep_code = mysqli_real_escape_string($db_connection, $dep_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM department WHERE code = '$dep_code'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getDepartmentSelect($db_connection)
{

    $query = "SELECT * FROM department  ORDER BY name ASC";
    $result = mysqli_query($db_connection, $query);

    $department_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $department_list[] = $row;
        }
    }
    return $department_list; // คืนค่าเป็น array ของหลายแถว
}
function getJob($db_connection, $job_code)
{
    $job_code = mysqli_real_escape_string($db_connection, $job_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM job WHERE code = '$job_code'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getJobSelect($db_connection)
{

    $query = "SELECT * FROM job  ORDER BY name ASC";
    $result = mysqli_query($db_connection, $query);

    $job_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $job_list[] = $row;
        }
    }
    return $job_list; // คืนค่าเป็น array ของหลายแถว
}
function getUnit($db_connection, $unit_code)
{
    $unit_code = mysqli_real_escape_string($db_connection, $unit_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM unit WHERE code = '$unit_code'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

/**
 * ชื่อหน่วยงานหลักที่ใช้แสดงผล (ลำดับ unit_code → job_code → dep_code)
 * ให้ตรงกับการคำนวณในเมนูแผน / ข้อมูลบุคลากร
 */
function staffMainUnitDisplayName($db_connection, array $srow): string
{
    $mainUnitName = '';
    $unitCodeStaff = trim((string) ($srow['unit_code'] ?? ''));
    $jobCodeStaff = trim((string) ($srow['job_code'] ?? ''));
    $depCodeStaff = trim((string) ($srow['dep_code'] ?? ''));

    if ($unitCodeStaff !== '') {
        if (function_exists('getUnit')) {
            $uMain = getUnit($db_connection, $unitCodeStaff);
            if (is_array($uMain)) {
                $mainUnitName = trim((string) ($uMain['name'] ?? ''));
            }
        }
        if ($mainUnitName === '') {
            $mainUnitName = $unitCodeStaff;
        }
    } elseif ($jobCodeStaff !== '') {
        if (function_exists('getJob')) {
            $jMain = getJob($db_connection, $jobCodeStaff);
            if (is_array($jMain)) {
                $mainUnitName = trim((string) ($jMain['name'] ?? ''));
            }
        }
        if ($mainUnitName === '') {
            $mainUnitName = $jobCodeStaff;
        }
    } elseif ($depCodeStaff !== '') {
        if (function_exists('getDepartment')) {
            $dMain = getDepartment($db_connection, $depCodeStaff);
            if (is_array($dMain)) {
                $mainUnitName = trim((string) ($dMain['name'] ?? ''));
            }
        }
        if ($mainUnitName === '') {
            $mainUnitName = $depCodeStaff;
        }
    }

    return $mainUnitName;
}
function getUnitSelect($db_connection)
{

    $query = "SELECT * FROM unit  ORDER BY name ASC";
    $result = mysqli_query($db_connection, $query);

    $unit_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $unit_list[] = $row;
        }
    }
    return $unit_list; // คืนค่าเป็น array ของหลายแถว
}
function getWorkstatus($db_connection, $work_status)
{
    $work_status = mysqli_real_escape_string($db_connection, $work_status); // ป้องกัน SQL Injection
    $query = "SELECT * FROM work_status WHERE code = '$work_status'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getWorkstatusSelect($db_connection)
{

    $query = "SELECT * FROM work_status  ORDER BY name ASC";
    $result = mysqli_query($db_connection, $query);

    $work_status_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $work_status_list[] = $row;
        }
    }
    return $work_status_list; // คืนค่าเป็น array ของหลายแถว
}
function getStaff($db_connection, $duty_code)
{
    $duty_code = mysqli_real_escape_string($db_connection, $duty_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM staff WHERE duty_code = '$duty_code' AND work_status = '0' ORDER BY fname ASC";
    $result = mysqli_query($db_connection, $query);

    $staff_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $staff_list[] = $row;
        }
    }
    return $staff_list; // คืนค่าเป็น array ของหลายแถว
}
function getStaffID($db_connection, $staff_code)
{
    $staff_code = mysqli_real_escape_string($db_connection, $staff_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM staff WHERE code = '$staff_code' ";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getStaffByID($db_connection, $staff_code)
{
    $staff_code = mysqli_real_escape_string($db_connection, $staff_code); // ป้องกัน SQL Injection
    $query = "SELECT * FROM staff WHERE id = '$staff_code' ";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getStaffSelect($db_connection)
{
    $query = "SELECT * FROM staff WHERE work_status = '0' ORDER BY fname ASC";
    $result = mysqli_query($db_connection, $query);

    $staff_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $staff_list[] = $row;
        }
    }
    return $staff_list; // คืนค่าเป็น array ของหลายแถว
}
function getStaffList($db_connection)
{
    $query = "SELECT * FROM staff WHERE work_status = '0' ORDER BY fname ASC";
    $result = mysqli_query($db_connection, $query);

    $staff_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $staff_list[] = $row;
        }
    }
    return $staff_list; // คืนค่าเป็น array ของหลายแถว
}

function getStaffAPI($db_connection, $type)
{
    $type = mysqli_real_escape_string($db_connection, $type); // ป้องกัน SQL Injection
    $query = "SELECT * FROM staff INNER JOIN workcurrent ON staff.emailCMU=workcurrent.emailCMU WHERE staff.employeeTypeNameTha='$type'  ORDER BY staff.firstNameTha ASC";
    $result = mysqli_query($db_connection, $query);

    $staff_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $staff_list[] = $row;
        }
    }
    return $staff_list; // คืนค่าเป็น array ของหลายแถว
}

function notifyStaffAndStd($email_send, $staff_send, $subject, $body, $code_eform, $code_std, $level, $db_ecmu19, $dates_times)
{
    require '../vendor-cmu/autoload.php';
    $phpMailer = new PHPMailer(true);

    // ส่งอีเมล
    sendEmailNoReply($phpMailer, $email_send, $subject, $body, "", "");

    // แจ้งเตือน App Smart
    $sendTo = $staff_send;
    $created = "E160041";
    $notiMessage = strip_tags($body); // ดึงข้อความหลัก
    $nameApp = "ระบบแบบคำร้องขอทั้วไป E-CMU19";

    $notify_data = json_encode(array(
        'action' => "SendNotify",
        'system_name' => $nameApp,
        'body_detail' => $notiMessage,
        'action_url' => '',
        'send_to' => $sendTo,
        'created_by' => $created
    ));
    sendnotify($notify_data);

    // mysqli_query($db_ecmu19, "INSERT INTO eform_log_email(code_eform,code_std,email,smart_notify,status,dates,status_level)
    //         VALUE('$code_eform','$code_std','$email_send','$sendTo','success','$dates_times','$level') ");
}
