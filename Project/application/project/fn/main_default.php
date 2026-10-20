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

/**
 * ปีงบประมาณปัจจุบัน (พ.ศ.)
 * ปีงบไทยเริ่ม 1 ต.ค. — ต.ค.–ก.ย. เรียกชื่อตาม พ.ศ. ที่ปีงบสิ้นสุด
 * เช่น ต.ค. 2026 – ก.ย. 2027 = ปีงบ 2570
 */
function getCurrentBudgetYear(): string
{
  $yearBe = (int) date('Y') + 543;
  if ((int) date('n') >= 10) {
    $yearBe++;
  }
  return (string) $yearBe;
}

/** รหัสงานฝ่ายแผน / ผู้ดูแลระบบ PlanWise (สอดคล้อง KPI OAuth) */
function pm_is_plan_system_admin(array $staffRow): bool
{
  return trim((string) ($staffRow['job_code'] ?? '')) === '120103';
}

function convertMonthToThai($monthNumber)
{
  // แปลงเป็นเลข 2 หลัก เช่น 1 → 01
  $monthNumber = str_pad($monthNumber, 2, '0', STR_PAD_LEFT);

  // รายชื่อเดือนภาษาไทย
  $thaiMonths = [
    '01' => 'มกราคม',
    '02' => 'กุมภาพันธ์',
    '03' => 'มีนาคม',
    '04' => 'เมษายน',
    '05' => 'พฤษภาคม',
    '06' => 'มิถุนายน',
    '07' => 'กรกฎาคม',
    '08' => 'สิงหาคม',
    '09' => 'กันยายน',
    '10' => 'ตุลาคม',
    '11' => 'พฤศจิกายน',
    '12' => 'ธันวาคม'
  ];

  return $thaiMonths[$monthNumber] ?? '-';
}

function renderCleanHTML($html)
{
  // 1️⃣ แปลง HTML entities กลับเป็นข้อความจริง (ป้องกัน encode ซ้อน)
  $decoded = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

  // 2️⃣ อนุญาตเฉพาะแท็กที่ต้องการให้แสดงผลลักษณะ เช่น b, strong, i, br, ul, li
  $allowed = '<b><strong><i><em><br><ul><ol><li>';

  // 3️⃣ ลบแท็กอื่นออก แต่เก็บแท็กจัดรูปแบบไว้
  $clean = strip_tags($decoded, $allowed);

  // 4️⃣ คืนค่าให้ browser render ตามปกติ
  return $clean;
}

// SweetAlert + redirect
/**
 * สร้าง SweetAlert + redirect JS
 *
 * @param string $link URL ที่จะไป (จะถูก json_encode เพื่อความปลอดภัย)
 * @param string $title ข้อความหัวข้อ (ภาษาไทยหรืออังกฤษ)
 * @param string $text ข้อความรอง
 * @param string $icon icon: "success", "error", "warning", "info", "question"
 * @param bool   $exit_after ถ้า true จะเรียก exit() ทันทีหลัง echo (default false)
 * @return string คืนค่า script ที่จะ echo ลงหน้า HTML
 */
function build_swal_success_redirect(
  string $link,
  string $title = 'บันทึกข้อมูลเรียบร้อย',
  string $text  = 'Success',
  string $icon  = 'success',
  bool $exit_after = false
): string {
  // ใช้ json_encode เพื่อ escape ค่าใดๆ ให้เป็น JS string ที่ปลอดภัย
  $js_link  = json_encode($link, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  $js_title = json_encode($title, JSON_UNESCAPED_UNICODE);
  $js_text  = json_encode($text, JSON_UNESCAPED_UNICODE);
  $js_icon  = json_encode($icon);

  $script = <<<JS
<script>
  if (typeof swal === "function") {
    swal({
      title: {$js_title},
      text: {$js_text},
      icon: {$js_icon}
    }).then(function() {
      window.location.assign({$js_link});
    });
  } else {
    // fallback ถ้าไม่มี swal ให้ไป redirect ทันที
    window.location.assign({$js_link});
  }
</script>
JS;

  if ($exit_after) {
    echo $script;
    exit;
  }
  return $script;
}

function build_swal_error_redirect(
  string $link,
  string $title = 'บันทึกข้อมูลไม่สำเร็จ',
  string $text  = 'กรุณาลองใหม่อีกครั้งค่ะ',
  string $icon  = 'error',
  bool $exit_after = false
): string {
  // ป้องกันปัญหาอักขระพิเศษ ด้วย json_encode
  $js_link  = json_encode($link, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  $js_title = json_encode($title, JSON_UNESCAPED_UNICODE);
  $js_text  = json_encode($text, JSON_UNESCAPED_UNICODE);
  $js_icon  = json_encode($icon);

  $script = <<<JS
<script>
  if (typeof swal === "function") {
    swal({
      title: {$js_title},
      text: {$js_text},
      icon: {$js_icon}
    }).then(function() {
      window.location.assign({$js_link});
    });
  } else {
    window.location.assign({$js_link});
  }
</script>
JS;

  if ($exit_after) {
    echo $script;
    exit;
  }

  return $script;
}
// --------------------- End -----------------------------------

function toDecimalOrZero($val)
{
  // ถ้าไม่มีค่า หรือเป็นช่องว่าง → return 0.00
  if (!isset($val) || trim($val) === '' || !is_numeric($val)) {
    return '0.00';
  }
  // ถ้ามีค่า → แปลงเป็นทศนิยม 2 หลัก
  return number_format((float)$val, 2, '.', '');
}
function formatNumber($num)
{
  // ถ้าเป็น null หรือค่าว่าง ให้แสดงเป็น "-"
  if ($num === null || $num === '') return '-';

  // ถ้าเป็นจำนวนเต็ม (เช่น 5.00 → 5)
  if (fmod($num, 1) == 0) {
    return (int)$num; // แสดงเฉพาะเลขจำนวนเต็ม
  }

  // ถ้าไม่ใช่จำนวนเต็ม → แสดงทศนิยม 2 ตำแหน่ง
  return number_format($num, 2);
}
function formatNumberClean($num)
{
  if ($num === null || $num === '') return '';
  // แปลงเป็น float ก่อน เพื่อความแน่นอน
  $num = (float)$num;
  // แปลงเป็น string และลบ .00 หรือ 0 ที่ไม่จำเป็น
  return rtrim(rtrim(sprintf('%.2f', $num), '0'), '.');
}


function formatAmount($value, $decimals = 2)
{
  if ($value == 0 || $value === null || $value === '') {
    return ''; // ไม่แสดงอะไรเลย
  }

  // ถ้าเป็นจำนวนเต็ม (ไม่มีทศนิยม)
  if (floor($value) == $value) {
    return number_format($value, 0);
  }

  // ถ้ามีทศนิยม
  return number_format($value, $decimals);
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
