<?php

$month_name = array("", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม");
$month_name_short = array("", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค.");
$day_name = array("อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์");

function FullThaiDateTime($dateTime)
{
    global $day_name, $month_name;
    $thai_date_return = "วัน" . $day_name[date("w", $dateTime)];
    $thai_date_return .=    "ที่ " . date("j", $dateTime);
    $thai_date_return .= " เดือน" . $month_name[date("n", $dateTime)];
    $thai_date_return .=    " พ.ศ." . (date("Yํ", $dateTime) + 543);
    $thai_date_return .=    "  " . date("H:i", $dateTime) . " น.";
    return $thai_date_return;
}

function FullThaiDate($dateTime)
{
    global $day_name, $month_name;
    $thai_date_return = "วัน" . $day_name[date("w", $dateTime)];
    $thai_date_return .=    "ที่ " . date("j", $dateTime);
    $thai_date_return .= " เดือน" . $month_name[date("n", $dateTime)];
    $thai_date_return .=    " พ.ศ." . (date("Yํ", $dateTime) + 543);
    return $thai_date_return;
}

function DBThaiDate($d)
{
    if (!$d) return "";
    if ($d == "0000-00-00") return "";
    $d1 = explode("-", $d);
    return $d1[2] . "/" . $d1[1] . "/" . (intval($d1[0]) + 543);
}

function DBThaiLongDate($d)
{
    global $month_name;
    if (!$d) return "";
    if ($d == "0000-00-00") return "";
    $d1 = explode("-", $d);
    return intval($d1[2]) . " " . $month_name[intval($d1[1])] . " " . (intval($d1[0]) + 543);
}

function DBThaiLongMonth($d)
{
    global $month_name;
    if (!$d) return "";
    if ($d == "0000-00-00") return "";
    $d1 = explode("-", $d);
    return $month_name[intval($d1[1])] . " " . (intval($d1[0]) + 543);
}

function DBThaiMonth($d)
{
    global $month_name;
    if (!$d) return "";
    if ($d == "0000-00-00") return "";
    $d1 = explode("-", $d);
    return intval($d1[2]) . " " . $month_name[intval($d1[1])] . " " . (intval($d1[0]) + 543);
}

function DBThaiLongDateFull($d)
{
    global $month_name;
    if (!$d) return "";
    if ($d == "0000-00-00") return "";
    $d1 = explode("-", $d);
    return "วันที่ " . intval($d1[2]) . " เดือน " . $month_name[intval($d1[1])] . " พ.ศ. " . (intval($d1[0]) + 543);
}

function DBThaiShortDate($d)
{
    global $month_name_short;
    if (!$d) return "";
    if ($d == "0000-00-00") return "";
    $d1 = explode("-", $d);
    return intval($d1[2]) . " " . $month_name_short[intval($d1[1])] . " " . (intval($d1[0]) + 543);
}

function ThaiDate2DBDate($d)
{
    if (!$d) return "";
    if ($d == "00-00-0000") return "";
    $d1 = explode("/", $d);
    return (intval($d1[2]) - 543) . "-" . $d1[1] . "-" . $d1[0];
}

function Diff2LongTime($time_a, $time_b)
{
    if ($time_a == '' || $time_a == '00:00:00') return "-";
    if ($time_b == '' || $time_b == '00:00:00') return "-";
    $now_time1 = strtotime(date("Y-m-d " . $time_a));
    $now_time2 = strtotime(date("Y-m-d " . $time_b));
    $time_diff = abs($now_time2 - $now_time1);
    $time_diff_h = floor($time_diff / 3600); // จำนวนชั่วโมงที่ต่างกัน
    $time_diff_m = floor(($time_diff % 3600) / 60); // จำวนวนนาทีที่ต่างกัน
    $time_diff_s = ($time_diff % 3600) % 60; // จำนวนวินาทีที่ต่างกัน
    //    return $time_diff_h." ชั่วโมง ".$time_diff_m." นาที ".$time_diff_s." วินาที";
    return $time_diff_h . " ชั่วโมง " . $time_diff_m . " นาที ";
}

function Diff2ShortTime($time_a, $time_b)
{
    if ($time_a == '' || $time_a == '00:00:00') return "-";
    if ($time_b == '' || $time_b == '00:00:00') return "-";
    $now_time1 = strtotime(date("Y-m-d " . $time_a));
    $now_time2 = strtotime(date("Y-m-d " . $time_b));
    $time_diff = abs($now_time2 - $now_time1);
    $time_diff_h = floor($time_diff / 3600); // จำนวนชั่วโมงที่ต่างกัน
    $time_diff_m = floor(($time_diff % 3600) / 60); // จำวนวนนาทีที่ต่างกัน
    $time_diff_s = ($time_diff % 3600) % 60; // จำนวนวินาทีที่ต่างกัน
    //    return $time_diff_h." ชั่วโมง ".$time_diff_m." นาที ".$time_diff_s." วินาที";
    return $time_diff_h . " ชม. " . $time_diff_m . " น.";
}

function TimeThai($strTime)
{
    if ($strTime == '') return "-";
    $strHour = date("H", strtotime($strTime));
    $strMinute = date("i", strtotime($strTime));
    $strSeconds = date("s", strtotime($strTime));
    return "$strHour:$strMinute" . " น.";
}

function DateTimeThai($strDate)
{
    if (!$strDate) return "";
    if ($strDate == "0000-00-00 00:00:00") return "00-00-0000 00:00:00";
    $strYear = date("Y", strtotime($strDate)) + 543;
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    $strHour = date("H", strtotime($strDate));
    $strMinute = date("i", strtotime($strDate));
    $strSeconds = date("s", strtotime($strDate));
    $strMonthCut = array("", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค.");
    $strMonthThai = $strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear" . " เวลา " . "$strHour:$strMinute:$strSeconds";
}

function DateDiff($strDate1, $strDate2)
{
    return (strtotime($strDate2) - strtotime($strDate1)) /  (60 * 60 * 24);  // 1 day = 60*60*24
}

function CalDateStartToEnd($dateStart, $dateEnd)
{
    if ($dateStart == "0000-00-00" || $dateEnd == "0000-00-00" || $dateStart == "" || $dateEnd == "") return "";
    list($syear, $smonth, $sady) = explode("-", $dateStart);
    list($eyear, $emonth, $eday) = explode("-", $dateEnd);

    if ($syear < 1970) {
        $yearad = 1970 - $syear;
        $syear = 1970;
    } else {
        $yearad = 0;
    }

    // $mStart = mktime(0,0,0,$smonth,$sday,$syear);
    $mStart = mktime(0, 0, 0, $smonth, $syear);
    $mEnd = mktime(0, 0, 0, $emonth, $eday, $eyear);

    $num = ($mEnd - $mStart);
    $numDate = (date("Y", $num) - 1970 + $yearad) . " ปี " .
        (date("m", $num) - 1) . " เดือน " .
        (date("d", $num) - 1) . " วัน";

    return ($numDate);
}

function CalDateTimeStartToEnd($dateStart, $dateEnd)
{
    date_default_timezone_set('Asia/Bangkok');
    $nt = strtotime($dateStart);   // มันจะดึงเวลาปัจุบันมาเอง
    $ct = strtotime($dateEnd);  // ใส่เวลาที่คุณต้องการ อาจจะมาจากดาต้าเบสก็ได้ดึงค่ามาใส่
    $distance = $ct - $nt; //หักลบวันเพื่อใช้คำนวณ
    $days = floor($distance / (60 * 60 * 24));
    $hours = floor(($distance % (60 * 60 * 24)) / (60 * 60));
    $minutes = floor($distance % (60 * 60) / 60);
    $seconds = floor($distance % 60);

    if ($distance >= 0) {
        $keep = $days . "  วัน   " . $hours . " ชั่วโมง  " . $minutes . "  นาที  ";
    } else {
        $keep = "";
    }

    return ($keep);
}



function GetAge($birthday)
{
    if ($birthday == "0000-00-00") return "";
    $then = strtotime($birthday);
    return (floor((time() - $then) / 31556926));
}

function UpImg($img, $imglocate)
{
    if ($img['name'] != '') {
        $fileupload1 = $img['tmp_name'];
        $g_img = explode(".", $img['name']);
        $file_up = time() . "." . $g_img[1];
        if ($fileupload1) {
            $array_last = explode(".", $file_up);
            $c = count($array_last) - 1;
            $lastname = strtolower($array_last[$c]);
            @copy($fileupload1, $imglocate . $file_up);
        }
    }
    return $file_up;
}

function num2word($number)
{
    $number = number_format($number, 2, '.', '');
    $numberx = $number;
    $txtnum1 = array('ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า', 'สิบ');
    $txtnum2 = array('', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน');
    $number = str_replace(",", "", $number);
    $number = str_replace(" ", "", $number);
    $number = str_replace("บาท", "", $number);
    $number = explode(".", $number);
    if (sizeof($number) > 2) {
        return 'ทศนิยมหลายตัว';
        exit;
    }
    $strlen = strlen($number[0]);
    $convert = '';
    for ($i = 0; $i < $strlen; $i++) {
        $n = substr($number[0], $i, 1);
        if ($n != 0) {
            if ($i == ($strlen - 1) and $n == 1) {
                $convert .= 'เอ็ด';
            } elseif ($i == ($strlen - 2) and $n == 2) {
                $convert .= 'ยี่';
            } elseif ($i == ($strlen - 2) and $n == 1) {
                $convert .= '';
            } else {
                $convert .= $txtnum1[$n];
            }
            $convert .= $txtnum2[$strlen - $i - 1];
        }
    }

    $convert .= 'บาท';
    if (
        $number[1] == '0' or $number[1] == '00' or
        $number[1] == ''
    ) {
        $convert .= 'ถ้วน';
    } else {
        $strlen = strlen($number[1]);
        for ($i = 0; $i < $strlen; $i++) {
            $n = substr($number[1], $i, 1);
            if ($n != 0) {
                if ($i == ($strlen - 1) and $n == 1) {
                    $convert
                        .= 'เอ็ด';
                } elseif (
                    $i == ($strlen - 2) and
                    $n == 2
                ) {
                    $convert .= 'ยี่';
                } elseif (
                    $i == ($strlen - 2) and
                    $n == 1
                ) {
                    $convert .= '';
                } else {
                    $convert .= $txtnum1[$n];
                }
                $convert .= $txtnum2[$strlen - $i - 1];
            }
        }
        $convert .= 'สตางค์';
    }
    //แก้ต่ำกว่า 1 บาท ให้แสดงคำว่าศูนย์ แก้ ศูนย์บาท
    if ($numberx < 1) {
        $convert = "ศูนย์" .  $convert;
    }

    //แก้เอ็ดสตางค์
    $len = strlen($numberx);
    $lendot1 = $len - 2;
    $lendot2 = $len - 1;
    if (($numberx[$lendot1] == 0) && ($numberx[$lendot2] == 1)) {
        $convert = substr($convert, 0, -10);
        $convert = $convert . "หนึ่งสตางค์";
    }

    //แก้เอ็ดบาท สำหรับค่า 1-1.99
    if ($numberx >= 1) {
        if ($numberx < 2) {
            $convert = substr($convert, 4);
            $convert = "หนึ่ง" .  $convert;
        }
    }
    return $convert;
}

function getNumDay($d1, $d2)
{
    $dArr1    = preg_split("/-/", $d1);
    list($year1, $month1, $day1) = $dArr1;
    $Day1 =  mktime(0, 0, 0, $month1, $day1, $year1);

    $dArr2    = preg_split("/-/", $d2);
    list($year2, $month2, $day2) = $dArr2;
    $Day2 =  mktime(0, 0, 0, $month2, $day2, $year2);

    return round(abs($Day2 - $Day1) / 86400) + 1;
}

//อัพโหลดรูปภาพ
function uppic_only($img, $imglocate, $multi = false, $limit_size = 2000000, $limit_width = false, $limit_height = false)
{
    $allowed_types = array("jpg", "jpeg", "gif", "png");   // กำหนดนามสกุลของไฟล์ที่อนุญาตให้อัพโหลด 
    $file_up = NULL; // กำหนดชื่อไฟล์เริ่มต้น เป็น NULL
    $i_num = (isset($multi)) ? count($img['name']) : false; // ตรวจสอบว่ามีการอัพทีละหลายๆ ไฟล์หรือไม่
    if ($img["name"] != "" || @implode("", $img["name"]) != "") { // มีการส่งไฟล์เข้ามาจริง
        if ($i_num) {
            $file_up = array();
            for ($i = 0; $i < $i_num; $i++) {     // วนลูปกรณีอัพที่ละหลายไฟล์           
                $fileupload1 = $img["tmp_name"][$i]; // ข้อมูลไฟล์อัพโหลด
                $data_Img = @getimagesize($fileupload1); // หาความกว้าง ความสูงของรูป
                $g_img = explode(".", $img["name"][$i]); // แยกข้อมูลจากชื่อไฟล์ เพื่อหา ชื่อไฟล์ นามสกุลไฟล์
                $ext = strtolower(array_pop($g_img));  // แยกนามกุลไฟล์ออกมา แล้วปรับให้เป็นตัวพิมพ์เล็ก
                $file_up_arr = time() . "-" . $i . "." . $ext;  // กำหนดชื่อไฟล์ใหม่ ในที่นี้จะเป็นในรูปแบบ เช่น 1234556789-1.jpg                     
                $canUpload = 0; // กำหนดสถานะอัพโหลดเริ่มต้น
                if (isset($data_Img) && $data_Img[0] > 0 && $data_Img[1] > 0) { // ตรวงสอบว่ามีข้อมูลเกี่ยวกับรูปหรือไม่ เช่น ความกว้าง สูง
                    if ($img["size"][$i] <= $limit_size) {   // ขนาดของรูปที่อัพโหลด จะต้องไม่เกิน ค่าที่กำหนด           
                        if ($limit_width && $limit_height) { // ถ้ามีการกำหนดให้ตรวจสอบ ขนาดความกว้างและสูง ของรูป
                            if ($data_Img[0] <= $limit_width && $data_Img[1] <= $limit_height) {
                                $canUpload = 1;
                            }
                        } elseif ($limit_width > 0 && $limit_height == false) { // ถ้ามีการกำหนดให้ตรวจสอบ เฉพาะขนาดความกว้าง ของรูป
                            if ($data_Img[0] <= $limit_width) {
                                $canUpload = 1;
                            }
                        } elseif ($limit_width == false && $limit_height > 0) { // ถ้ามีการกำหนดให้ตรวจสอบ เฉพาะขนาดความสูง ของรูป
                            if ($data_Img[1] <= $limit_height) {
                                $canUpload = 1;
                            }
                        } else { // ไม่มีการรวจสอบเพิ่มเติมใดๆ นอกจากขนาดไฟล์
                            $canUpload = 1;
                        }
                    }
                }
                // เมื่อมีข้อมูลไฟล์ที่อัพโหลด และเป็นไฟล์ที่อนุญาต และสามารถอัพโหลดได้โดยไม่ติดเงื่อนไขใดๆ     
                if ($fileupload1 != "" && @in_array($ext, $allowed_types) && $canUpload == 1) {
                    // ตรวจสอบว่าเป็นไฟล์อัพโหลด และทำการย้ายไฟล์ไปยัง path ที่กำหนด           
                    if (is_uploaded_file($fileupload1) && @move_uploaded_file($fileupload1, $imglocate . $file_up_arr)) {
                        array_push($file_up, $file_up_arr);
                        @chmod($imglocate . $file_up_arr, 0777); // เปลี่ยน permission ของไฟล์ ส่วนใหย๋ค่ำสั่ง chmod จะใช้ไม่ได้                               
                    }
                }
            }
            if (count($file_up) == 0) {
                $file_up = NULL; // อัพโหลดไม่ได้ ให้เป็น NULL    
            }
        } else {
            $fileupload1 = $img["tmp_name"]; // ข้อมูลไฟล์อัพโหลด
            $data_Img = @getimagesize($fileupload1); // หาความกว้าง ความสูงของรูป
            $g_img = explode(".", $img["name"]); // แยกข้อมูลจากชื่อไฟล์ เพื่อหา ชื่อไฟล์ นามสกุลไฟล์
            $ext = strtolower(array_pop($g_img));  // แยกนามกุลไฟล์ออกมา แล้วปรับให้เป็นตัวพิมพ์เล็ก
            $file_up = time() . "." . $ext;  // กำหนดชื่อไฟล์ใหม่ ในที่นี้จะเป็นในรูปแบบ เช่น 1234556789.jpg                  
            $canUpload = 0; // กำหนดสถานะอัพโหลดเริ่มต้น
            if (isset($data_Img) && $data_Img[0] > 0 && $data_Img[1] > 0) { // ตรวงสอบว่ามีข้อมูลเกี่ยวกับรูปหรือไม่ เช่น ความกว้าง สูง
                if ($img["size"] <= $limit_size) {   // ขนาดของรูปที่อัพโหลด จะต้องไม่เกิน ค่าที่กำหนด           
                    if ($limit_width && $limit_height) { // ถ้ามีการกำหนดให้ตรวจสอบ ขนาดความกว้างและสูง ของรูป
                        if ($data_Img[0] <= $limit_width && $data_Img[1] <= $limit_height) {
                            $canUpload = 1;
                        }
                    } elseif ($limit_width > 0 && $limit_height == false) { // ถ้ามีการกำหนดให้ตรวจสอบ เฉพาะขนาดความกว้าง ของรูป
                        if ($data_Img[0] <= $limit_width) {
                            $canUpload = 1;
                        }
                    } elseif ($limit_width == false && $limit_height > 0) { // ถ้ามีการกำหนดให้ตรวจสอบ เฉพาะขนาดความสูง ของรูป
                        if ($data_Img[1] <= $limit_height) {
                            $canUpload = 1;
                        }
                    } else { // ไม่มีการรวจสอบเพิ่มเติมใดๆ นอกจากขนาดไฟล์
                        $canUpload = 1;
                    }
                }
            }
            // เมื่อมีข้อมูลไฟล์ที่อัพโหลด และเป็นไฟล์ที่อนุญาต และสามารถอัพโหลดได้โดยไม่ติดเงื่อนไขใดๆ     
            if ($fileupload1 != "" && @in_array($ext, $allowed_types) && $canUpload == 1) {
                // ตรวจสอบว่าเป็นไฟล์อัพโหลด และทำการย้ายไฟล์ไปยัง path ที่กำหนด           
                if (is_uploaded_file($fileupload1) && @move_uploaded_file($fileupload1, $imglocate . $file_up)) {
                    @chmod($imglocate . $file_up, 0777); // เปลี่ยน permission ของไฟล์ ส่วนใหย๋ค่ำสั่ง chmod จะใช้ไม่ได้                               
                }
            } else {
                $file_up = NULL; // อัพโหลดไม่ได้ ให้เป็น NULL
            }
        }
    }
    return $file_up; // ส่งกลับชื่อไฟล์
}

function tinyDatePickerToDB($datetiny)
{
    $date_ex = explode("/", $datetiny);
    $date_to_db = $date_ex[2] . "-" . $date_ex[1] . "-" . $date_ex[0];
    return $date_to_db;
}


function dbToTinyDatePicker($datedb)
{
    $date_ex = explode("-", $datedb);
    $date_to_picker = $date_ex[2] . "/" . $date_ex[1] . "/" . $date_ex[0];
    return $date_to_picker;
}


function lineNoti($sToken, $sMessage)
{
    $chOne = curl_init();
    curl_setopt($chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
    curl_setopt($chOne, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($chOne, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($chOne, CURLOPT_POST, 1);
    curl_setopt($chOne, CURLOPT_POSTFIELDS, "message=" . $sMessage);
    $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $sToken . '',);
    curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($chOne, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($chOne);
    return $result;
}


function sendEmail($emailTo, $subject, $body)
{
    $email = new PHPMailer\PHPMailer\PHPMailer();
    $email->IsSMTP();
    $email->SMTPAuth = true;
    $email->SMTPSecure = 'ssl';
    $email->Host = "smtp.gmail.com";
    $email->Port = 465;
    $email->Username = "pharmacy.sendmail.cmu@gmail.com";
    $email->Password = "dhdpnxbpubcboaws";
    $email->IsHTML(true);
    $email->CharSet = "utf-8";
    $email->SetFrom("admin@pharmacy.cmu.ac.th");
    $email->AddAddress($emailTo);
    $email->Subject = $subject;
    $email->Body = $body;
    if (!$email->Send()) {
        $send_mail_error = "Mailer Error: " . $email->ErrorInfo;
        return $send_mail_error;
    }
}

function messageBodyEmail($staffTo, $messageTo)
{
    $messageBodyEmail = "
                    <table width='700' border='0' align='center' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td height='64' bgcolor='#698B23'>
                        <div align='center'><font color='#FFFFFF' size='+2'><b>PHARMACY MIS</b></font></div>
                        </td>
                    </tr>
                    <tr>
                        <td height='170' valign='top' bgcolor='#F2F2F2'><table width='90%' border='0' align='center' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td height='38'><div align='left'><font size='+2'>เรียน&nbsp;{$staffTo} </font></div></td>
                        </tr>
                        <tr>
                            <td height='69'><div align='left'>{$messageTo} กรุณาเข้าระบบ <a href='https://mts.pharmacy.cmu.ac.th' target='blank'>PHARMACY MIS</a> เพื่อดำเนินการ</div></td>
                        </tr>
                        <tr>
                            <td height='33'><div align='left'>** หากระบบขัดข้อกรุณาติดต่อหน่วยพัฒนาสารสนเทศ : rossukon.t@cmu.ac.th , nidchaphon.j@cmu.ac.th
                            </div></td>
                        </tr>
                        </table></td>
                    </tr>
                    <tr>
                        <td height='71' bgcolor='#698B23'><div align='center'><font color='#FFFFFF' >© 2023 PHARMACY MIS, Faculty of Pharmacy Chiang Mai University. All right reserved.</font></div></td>
                    </tr>
                    </table>

                    ";
    return $messageBodyEmail;
}


function sendEmailNoReply($phpMailer, $email, $subject, $body, $file, $filePath)
{

    $mail = $phpMailer;
    //Server settings
    $mail->isSMTP();
    $mail->Host         = "10.0.0.5";
    $mail->SMTPAuth     = true;
    $mail->Username     = "phar-services";
    $mail->Password     = "rai4eelae7ja4zie6eiP";
    $mail->Port         = 587;
    $mail->SMTPSecure   = true;
    $mail->SMTPAutoTLS = true;
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    //Recipients
    $mail->setFrom('no-reply-PBM@cmu.ac.th');
    // $mail->addReplyTo('pharm.cmu@gmail.com');

    //Content
    $mail->isHTML(true);
    $mail->CharSet = "utf-8";
    $mail->addAddress($email);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    if ($file != "") {
        $listFile = explode(",", $file);
        foreach ($listFile as $valFile) {
            $mail->addAttachment($filePath . $valFile);
        }
    }
    // $mail->send();
    if (!$mail->Send()) {
        $send_mail_error = "Mailer Error: " . $mail->ErrorInfo;
        return $send_mail_error;
    }
    // ล้างข้อมูลเกี่ยวกับผู้รับและเนื้อหาเพื่อให้สามารถใช้สำหรับอีเมลถัดไป
    $mail->clearAddresses();
    $mail->clearAttachments();
}


function fileUpload($fileInputName, $prefixFile, $uploadDirectory, $fileTypeName)
{
    if ($_FILES[$fileInputName]['name'] != '') {
        $fileName = strtolower($_FILES[$fileInputName]['name']);
        $fileSize = $_FILES[$fileInputName]['size'];
        $fileTmp = $_FILES[$fileInputName]['tmp_name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = $prefixFile . "-" . date('YmdHis') . "-" . substr($fileName, 0, 200);

        // Check file type
        if (in_array($fileType, ['pdf', 'jpg', 'jpeg', 'png'])) {
            // Check file size
            if ($fileSize <= 20000000) {
                // Move the uploaded file
                if (move_uploaded_file($fileTmp, $uploadDirectory . $newFileName)) {
                    return $newFileName;
                } else {
                    displayAlert($fileTypeName, 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์!', 'info');
                    goBackPage(-1);
                    exit;
                }
            } else {
                displayAlert($fileTypeName, 'ขนาดไฟล์ต้องไม่เกิน 20MB !', 'info');
                goBackPage(-1);
                exit;
            }
        } else {
            displayAlert($fileTypeName, 'อัพโหลดไฟล์ (jpg png หรือ pdf) เท่านั้น!', 'info');
            goBackPage(-1);
            exit;
        }
    } else {
        return "";
    }
}

function sendnotify($notify_data)
{

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.pharmacy.cmu.ac.th/smart_phamacy/Pharmacy_notify/controller/Notify.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $notify_data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',

        ),
    ));

    $response = curl_exec($curl);

    if ($response === false) {
        echo "cURL Error: " . curl_error($curl);
    } else {

        echo "Response: " . $response;
    }
}

function convertDateForDisplay(string $date_db, string $format = 'n/j/Y'): string
{
    if (empty($date_db)) {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d', $date_db);
    return $date ? $date->format($format) : '';
}
function convertDateForDB(string $date_display): string
{
    if (empty($date_display)) {
        return '';
    }
    // กำหนดว่า input เป็น m/d/Y เช่น 8/21/2025
    $date = DateTime::createFromFormat('m/d/Y', $date_display);
    return $date ? $date->format('Y-m-d') : '';
}
