<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../../../config/connect_db.php");
include("../fn/main_upload.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $type = $_POST['type'];
    $filePath = $_FILES['excel_file']['tmp_name'];


    // Upload and Import
    if ($type == 'upload_kpi') {
        echo importKPIExcel($db_connection, $filePath);
    }
}
