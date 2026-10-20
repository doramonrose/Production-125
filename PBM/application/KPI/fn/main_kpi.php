<?php
include("../../../config/connect_db.php");
function getSetSymbol($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_symbol WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetSymbolList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_symbol WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $symbol_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $symbol_list[] = $row;
        }
    }
    return $symbol_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetSymbolSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_symbol WHERE id = '$id' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $symbol_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $symbol_list[] = $row;
        }
    }
    return $symbol_list; // คืนค่าเป็น array ของหลายแถว

}
function getSymbolSelect($db_connection)
{

    $query = "SELECT * FROM set_symbol WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $symbol_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $symbol_list[] = $row;
        }
    }
    return $symbol_list; // คืนค่าเป็น array ของหลายแถว

}
function getSetUnit($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_unit WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetUnitList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_unit WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $unit_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $unit_list[] = $row;
        }
    }
    return $unit_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetUnitSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_unit WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $unit_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $unit_list[] = $row;
        }
    }
    return $unit_list; // คืนค่าเป็น array ของหลายแถว
}
function getAddUnitSelect($db_connection)
{

    $query = "SELECT * FROM set_unit WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $unit_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $unit_list[] = $row;
        }
    }
    return $unit_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetType($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_type WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetTypeList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_type WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $type_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $type_list[] = $row;
        }
    }
    return $type_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetTypeSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_type WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $type_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $type_list[] = $row;
        }
    }
    return $type_list; // คืนค่าเป็น array ของหลายแถว
}
function getTypeSelect($db_connection)
{

    $query = "SELECT * FROM set_type WHERE status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $type_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $type_list[] = $row;
        }
    }
    return $type_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetAP($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_ap WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetAPList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_ap WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $ap_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ap_list[] = $row;
        }
    }
    return $ap_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetAPSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_ap WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $ap_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ap_list[] = $row;
        }
    }
    return $ap_list; // คืนค่าเป็น array ของหลายแถว
}
function getAPSelect($db_connection)
{

    $query = "SELECT * FROM set_ap WHERE status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $ap_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ap_list[] = $row;
        }
    }
    return $ap_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetSO($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_so WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetSOList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_so WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $so_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $so_list[] = $row;
        }
    }
    return $so_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetSOSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_so WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $so_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $so_list[] = $row;
        }
    }
    return $so_list; // คืนค่าเป็น array ของหลายแถว
}
function getSOSelect($db_connection)
{

    $query = "SELECT * FROM set_so WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $so_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $so_list[] = $row;
        }
    }
    return $so_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetTag($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_tag WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetTagList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_tag WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $tag_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tag_list[] = $row;
        }
    }
    return $tag_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetTagSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_tag WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $tag_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tag_list[] = $row;
        }
    }
    return $tag_list; // คืนค่าเป็น array ของหลายแถว
}
function getTagSelect($db_connection)
{

    $query = "SELECT * FROM set_tag WHERE status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $tag_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tag_list[] = $row;
        }
    }
    return $tag_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetCount($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_count WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetCountList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_count WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $count_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $count_list[] = $row;
        }
    }
    return $count_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetCountSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_count WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $count_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $count_list[] = $row;
        }
    }
    return $count_list; // คืนค่าเป็น array ของหลายแถว
}
function getCountSelect($db_connection)
{

    $query = "SELECT * FROM set_count WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $count_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $count_list[] = $row;
        }
    }
    return $count_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetSide($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_side WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetSideList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_side WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $side_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $side_list[] = $row;
        }
    }
    return $side_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetSideSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_side WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $side_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $side_list[] = $row;
        }
    }
    return $side_list; // คืนค่าเป็น array ของหลายแถว
}
function getSideSelect($db_connection)
{

    $query = "SELECT * FROM set_side WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $side_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $side_list[] = $row;
        }
    }
    return $side_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetOwner($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_owner WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetOwnerList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_owner WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $owner_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $owner_list[] = $row;
        }
    }
    return $owner_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetOwnerSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_owner WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $owner_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $owner_list[] = $row;
        }
    }
    return $owner_list; // คืนค่าเป็น array ของหลายแถว
}
function getOwnerSelect($db_connection)
{

    $query = "SELECT * FROM set_owner WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $owner_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $owner_list[] = $row;
        }
    }
    return $owner_list; // คืนค่าเป็น array ของหลายแถว
}
function getOwnerList($db_connection, $type)
{
    $type = mysqli_real_escape_string($db_connection, $type); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_owner WHERE type='$type' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $owner_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $owner_list[] = $row;
        }
    }
    return $owner_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetreport_round($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_report_round WHERE id = '$id'";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}

function getSetReportRoundList($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_report_round WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $report_round_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $report_round_list[] = $row;
        }
    }
    return $report_round_list; // คืนค่าเป็น array ของหลายแถว
}
function getSetReportRoundSelect($db_connection, $id)
{
    $id = mysqli_real_escape_string($db_connection, $id); // ป้องกัน SQL Injection
    $query = "SELECT * FROM set_report_round WHERE id = '$id' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $report_round_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $report_round_list[] = $row;
        }
    }
    return $report_round_list; // คืนค่าเป็น array ของหลายแถว
}
function getReportRoundSelect($db_connection)
{

    $query = "SELECT * FROM set_report_round WHERE  status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $report_round_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $report_round_list[] = $row;
        }
    }
    return $report_round_list; // คืนค่าเป็น array ของหลายแถว
}


function getKPI($db_connection, $code_kpi)
{
    $code_kpi = mysqli_real_escape_string($db_connection, $code_kpi); // ป้องกัน SQL Injection

    $query = "SELECT * FROM kpi WHERE id='$code_kpi'  ";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getKPIYear($db_connection, $year_select)
{
    $year_select = mysqli_real_escape_string($db_connection, $year_select); // ป้องกัน SQL Injection

    $query = "SELECT * FROM kpi_year WHERE kpi_year='$year_select'    ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
// function getKPIYearSearch($db_connection, $year_select, $keywords)
// {
//     $year_select = mysqli_real_escape_string($db_connection, $year_select); // ป้องกัน SQL Injection
//     $keywords = mysqli_real_escape_string($db_connection, $keywords); // ป้องกัน SQL Injection
//     $query = "SELECT * FROM kpi INNER JOIN kpi_year ON kpi.id=kpi_year.code_kpi 
//     WHERE kpi_year.kpi_year='$year_select' AND kpi.name=LIKE'%$keywords%'   ORDER BY id ASC";
//     $result = mysqli_query($db_connection, $query);

//     if ($result && mysqli_num_rows($result) > 0) {
//         return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
//     } else {
//         return null; // ไม่พบข้อมูล
//     }
// }

function getKPIByYearAndKeyword($db_connection, $years_select, $keywords)
{
    $sql = "
        SELECT kpi.*, kpi_year.kpi_year
        FROM kpi
        INNER JOIN kpi_year ON kpi.id = kpi_year.code_kpi
        WHERE kpi_year.kpi_year = ?
          AND kpi.name LIKE ?
        ORDER BY kpi.id ASC
    ";

    $stmt = $db_connection->prepare($sql);

    $like = "%" . $keywords . "%";  // ใส่ wildcard รอบ keyword
    $stmt->bind_param("is", $years_select, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $kpi_list = [];
    while ($row = $result->fetch_assoc()) {
        $kpi_list[] = $row;
    }

    $stmt->close();
    return $kpi_list;
}

function getKPIList($db_connection)
{

    $query = "SELECT * FROM kpi WHERE  status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $kpi_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $kpi_list[] = $row;
        }
    }
    return $kpi_list; // คืนค่าเป็น array ของหลายแถว
}
function getKPIYearList($db_connection, $year_select)
{
    $year_select = mysqli_real_escape_string($db_connection, $year_select); // ป้องกัน SQL Injection
    $query = "SELECT * FROM kpi_year WHERE kpi_year='$year_select' AND status != '99' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $kpi_year_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $kpi_year_list[] = $row;
        }
    }
    return $kpi_year_list; // คืนค่าเป็น array ของหลายแถว
}
function getKPIYearID($db_connection, $years_select, $code_kpi)
{
    $years_select = mysqli_real_escape_string($db_connection, $years_select); // ป้องกัน SQL Injection
    $code_kpi = mysqli_real_escape_string($db_connection, $code_kpi); // ป้องกัน SQL Injection
    $query = "SELECT * FROM kpi_year  WHERE kpi_year='$years_select' AND code_kpi='$code_kpi'  AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
function getKPIYearTargetList($db_connection, $code_kpi_year)
{
    $code_kpi_year = mysqli_real_escape_string($db_connection, $code_kpi_year); // ป้องกัน SQL Injection
    $query = "SELECT * FROM kpi_year_target WHERE code_kpi_year='$code_kpi_year' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $target_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $target_list[] = $row;
        }
    }
    return $target_list; // คืนค่าเป็น array ของหลายแถว
}

function getKPIYearDataOwnerList($db_connection, $code_kpi_year)
{
    $code_kpi_year = mysqli_real_escape_string($db_connection, $code_kpi_year); // ป้องกัน SQL Injection
    $query = "SELECT * FROM kpi_year_data_owner WHERE code_kpi_year='$code_kpi_year' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $data_owner_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data_owner_list[] = $row;
        }
    }
    return $data_owner_list; // คืนค่าเป็น array ของหลายแถว
}

function getKPIYearKPIOwnerList($db_connection, $code_kpi_year)
{
    $code_kpi_year = mysqli_real_escape_string($db_connection, $code_kpi_year); // ป้องกัน SQL Injection
    $query = "SELECT * FROM kpi_year_kpi_owner WHERE code_kpi_year='$code_kpi_year' AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);

    $kpi_owner_list = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $kpi_owner_list[] = $row;
        }
    }
    return $kpi_owner_list; // คืนค่าเป็น array ของหลายแถว
}
function getKPIYearIndicator($db_connection, $code_kpi_year)
{

    $code_kpi_year = mysqli_real_escape_string($db_connection, $code_kpi_year); // ป้องกัน SQL Injection
    $query = "SELECT * FROM kpi_year_indicator  WHERE code_kpi_year='$code_kpi_year'  AND status = '1' ORDER BY id ASC";
    $result = mysqli_query($db_connection, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC); // คืนค่าแบบ associative array
    } else {
        return null; // ไม่พบข้อมูล
    }
}
