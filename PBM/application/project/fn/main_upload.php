<?php
// include __DIR__ . "/../../../config/connect_db.php";
// include("../../../config/connect_db.php");
require __DIR__ . '/../../vendor-excel/autoload.php'; // เรียกใช้ PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

function importKPIExcel($db_connection, $filePath)
{
    try {
        if (!file_exists($filePath)) {
            return "ไม่สามารถนำเข้าได้";
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return "ไม่สามารถนำเข้าได้";
        }

        // ✅ Header row
        $headers = array_map('trim', $rows[0]);
        $colIndex = array_flip($headers);

        // ✅ Mapping: Excel → DB
        $fieldMap = [
            'ชื่อตัวชี้วัด' => 'name'
        ];

        foreach ($fieldMap as $excelCol => $dbField) {
            if (!isset($colIndex[$excelCol])) {
                return "ไม่สามารถนำเข้าได้";
            }
        }

        $inserted = 0;
        $skipped  = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $record = [];
            foreach ($fieldMap as $excelCol => $dbField) {
                $record[$dbField] = trim($rows[$i][$colIndex[$excelCol]]);
            }

            if (empty($record['name'])) {
                continue;
            }

            // ✅ ตรวจสอบซ้ำ
            $check = $db_connection->prepare("SELECT id FROM kpi WHERE name = ?");
            $check->bind_param("s", $record['name']);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $skipped++;
            } else {
                $stmt = $db_connection->prepare("INSERT INTO kpi (name, status) VALUES (?, 1)");
                $stmt->bind_param("s", $record['name']);

                if ($stmt->execute()) {
                    $inserted++;
                }
                $stmt->close();
            }
            $check->close();
        }

        return "นำเข้าเรียบร้อยแล้ว: $inserted รายการ, ข้าม (ซ้ำ): $skipped รายการ";
    } catch (Exception $e) {
        // ✅ เก็บ error ลง log ไฟล์
        $logFile = __DIR__ . "/import_error.log";
        $message = date("Y-m-d H:i:s") . " - "
            . $e->getMessage()
            . " in " . $e->getFile()
            . " line " . $e->getLine()
            . PHP_EOL;
        file_put_contents($logFile, $message, FILE_APPEND);

        // ✅ ส่งข้อความสั้น ๆ กลับไปหา user
        return "ไม่สามารถนำเข้าได้";
    }
}
