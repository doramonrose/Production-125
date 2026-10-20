<?php
require __DIR__ . '/../../vendor-excel/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function importKPIExcel($db, $filePath)
{
    try {
        if (!file_exists($filePath)) {
            return "ERROR: ไม่พบไฟล์ Excel";
        }

        // โหลด Excel
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return "ERROR: ไม่มีข้อมูลในไฟล์ Excel";
        }

        // หาหัว Column
        $header = trim($rows[1]['A']);
        if ($header !== "ชื่อตัวชี้วัด") {
            return "ERROR: ชื่อคอลัมน์ต้องเป็น 'ชื่อตัวชี้วัด' เท่านั้น";
        }

        $inserted = 0;
        $duplicate = 0;

        // Loop ข้อมูลตั้งแต่ row 2
        for ($i = 2; $i <= count($rows); $i++) {

            $name = trim($rows[$i]['A']);

            if ($name === "" || $name === null) {
                continue;
            }

            // ตรวจสอบชื่อซ้ำ
            $q = $db->prepare("SELECT id FROM kpi WHERE name = ?");
            $q->bind_param("s", $name);
            $q->execute();
            $q->store_result();

            if ($q->num_rows > 0) {
                $duplicate++;
                continue;
            }

            // Insert
            $stmt = $db->prepare("INSERT INTO kpi (name, status) VALUES (?, 1)");
            $stmt->bind_param("s", $name);

            if ($stmt->execute()) {
                $inserted++;
            }

            $stmt->close();
            $q->close();
        }

        return "นำเข้าเรียบร้อย: $inserted รายการ | ซ้ำ: $duplicate รายการ";
    } catch (Exception $e) {

        // LOG ERROR
        $log = __DIR__ . "/import_error.log";
        file_put_contents(
            $log,
            date("Y-m-d H:i:s") . " - " .
                $e->getMessage() . " @Line " . $e->getLine() . PHP_EOL,
            FILE_APPEND
        );

        return "ERROR: " . $e->getMessage();
    }
}
