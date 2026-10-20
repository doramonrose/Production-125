<?php
// บันทึกเพิ่มหน่วยงาน + รายชื่อบุคลากร (AJAX JSON only)
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');

header('Content-Type: application/json; charset=utf-8');

$debug = (isset($_GET['debug']) && (string)$_GET['debug'] === '1') || (isset($_POST['debug']) && (string)$_POST['debug'] === '1');

function json_fail(string $message, array $extra = []): void
{
	echo json_encode(array_merge(['ok' => false, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
	exit;
}

function json_ok(array $extra = []): void
{
	echo json_encode(array_merge(['ok' => true], $extra), JSON_UNESCAPED_UNICODE);
	exit;
}

try {
    // ใช้ db_Project / db_person จาก config_db.php
    global $db_Project, $db_person;
	if (!$db_Project || !$db_person) {
		json_fail('เชื่อมต่อฐานข้อมูลไม่สำเร็จ', ['debug' => ['db_Project' => (bool)$db_Project, 'db_person' => (bool)$db_person]]);
	}
	ensureSetProjectPersonnelTable($db_Project);

    $status = '1';
    $unit_name = mysqli_real_escape_string($db_Project, trim((string)($_POST['unit_name'] ?? '')));
    $paper_name = trim((string)($_POST['paper_name'] ?? ''));
    $paper_start = trim((string)($_POST['paper_start'] ?? ''));
    $code_department = isset($_POST['code_department']) ? (int) $_POST['code_department'] : 0;
    $code_job = isset($_POST['code_job']) ? (int) $_POST['code_job'] : 0;

    $staff_codes = $_POST['staff_codes'] ?? [];
    if (!is_array($staff_codes)) {
        $staff_codes = [$staff_codes];
    }
    $staff_codes = array_values(array_filter(array_map(function ($v) {
        return trim((string)$v);
    }, $staff_codes), function ($v) {
        return $v !== '';
    }));

    if ($unit_name === '') {
		json_fail('กรุณากรอกชื่อหน่วยงาน', $debug ? ['debug' => ['unit_name' => $unit_name]] : []);
    }

    // สร้างหน่วยงานใหม่: รองรับ schema ที่บังคับ field ต่าง ๆ (code/job_code/dep_code)
    // - code: U### ต่อเนื่อง
    // - job_code / dep_code: ดึงจาก set_job/set_department (ถ้ามีคอลัมน์ code), fallback เป็น id หรือ '' ถ้าไม่เลือก
    $hasCodeCol = false;
    $hasJobCodeCol = false;
    $hasDepCodeCol = false;
    $hasCodeJobCol = false;
    $hasCodeDeptCol = false;
    $hasPaperNameCol = false;
    $hasPaperStartCol = false;
    try {
        $qcol = mysqli_query($db_Project, "SHOW COLUMNS FROM set_unit");
        if ($qcol) {
            while ($c = mysqli_fetch_assoc($qcol)) {
                $name = strtolower((string)($c['Field'] ?? ''));
                if ($name === 'code') $hasCodeCol = true;
                if ($name === 'job_code') $hasJobCodeCol = true;
                if ($name === 'dep_code') $hasDepCodeCol = true;
                if ($name === 'code_job') $hasCodeJobCol = true;
                if ($name === 'code_department') $hasCodeDeptCol = true;
                if ($name === 'paper_name') $hasPaperNameCol = true;
                if ($name === 'paper_start') $hasPaperStartCol = true;
            }
        }
    } catch (Throwable $e) {
        @error_log('save_unit_ajax: show columns set_unit.code failed: ' . $e->getMessage());
    }

    $unit_code = '';
    if ($hasCodeCol) {
        $maxN = 0;
        try {
            $qmax = mysqli_query(
                $db_Project,
                "SELECT MAX(CAST(SUBSTRING(code,2) AS UNSIGNED)) AS mx
                 FROM set_unit
                 WHERE code REGEXP '^U[0-9]{3}$'"
            );
            if ($qmax) {
                $rr = mysqli_fetch_assoc($qmax);
                $maxN = (int)($rr['mx'] ?? 0);
            }
        } catch (Throwable $e) {
            @error_log('save_unit_ajax: select max U### failed: ' . $e->getMessage());
        }

        $nextN = $maxN + 1;
        $unit_code = 'U' . str_pad((string)$nextN, 3, '0', STR_PAD_LEFT);
        $tries = 0;
        while ($tries < 50) {
            $codeEsc = mysqli_real_escape_string($db_Project, $unit_code);
            $chk = null;
            try {
                $chk = mysqli_query($db_Project, "SELECT 1 FROM set_unit WHERE code = '$codeEsc' LIMIT 1");
            } catch (Throwable $e) {
                // ถ้า query ตรวจซ้ำล้มเหลว ให้หยุด loop แล้วลอง insert เลย
                break;
            }
            if (!$chk || mysqli_num_rows($chk) === 0) {
                break;
            }
            $nextN++;
            $unit_code = 'U' . str_pad((string)$nextN, 3, '0', STR_PAD_LEFT);
            $tries++;
        }
    }

    try {
        // เตรียมค่า job/dep สำหรับคอลัมน์ที่ระบบบังคับ
        $job_code_value = '';
        $dep_code_value = '';

        if ($code_job > 0) {
            // ถ้า set_job มีคอลัมน์ code ให้ใช้ code, ไม่งั้นใช้ id
            $jobRow = null;
            try {
                $qj = mysqli_query($db_Project, "SELECT * FROM set_job WHERE id = " . (int)$code_job . " LIMIT 1");
                if ($qj) $jobRow = mysqli_fetch_assoc($qj) ?: null;
            } catch (Throwable $e) {}
            $job_code_value = trim((string)($jobRow['code'] ?? ''));
            if ($job_code_value === '') {
                $job_code_value = (string)$code_job;
            }
        }

        if ($code_department > 0) {
            $depRow = null;
            try {
                $qd = mysqli_query($db_Project, "SELECT * FROM set_department WHERE id = " . (int)$code_department . " LIMIT 1");
                if ($qd) $depRow = mysqli_fetch_assoc($qd) ?: null;
            } catch (Throwable $e) {}
            $dep_code_value = trim((string)($depRow['code'] ?? ''));
            if ($dep_code_value === '') {
                $dep_code_value = (string)$code_department;
            }
        }

        // สร้าง SQL insert แบบยืดหยุ่นตามคอลัมน์ที่มี
        $cols = [];
        $vals = [];
        if ($hasCodeCol) {
            $cols[] = 'code';
            $vals[] = "'" . mysqli_real_escape_string($db_Project, $unit_code) . "'";
        }
        $cols[] = 'name';
        $vals[] = "'" . $unit_name . "'";
        $cols[] = 'status';
        $vals[] = "'" . mysqli_real_escape_string($db_Project, $status) . "'";

        // ถ้ามีคอลัมน์ job_code/dep_code ที่อาจบังคับ ให้ใส่เสมอ (ว่างได้ = '')
        if ($hasJobCodeCol) {
            $cols[] = 'job_code';
            $vals[] = "'" . mysqli_real_escape_string($db_Project, $job_code_value) . "'";
        }
        if ($hasDepCodeCol) {
            $cols[] = 'dep_code';
            $vals[] = "'" . mysqli_real_escape_string($db_Project, $dep_code_value) . "'";
        }
        // บาง schema ใช้ code_job / code_department เป็นตัวอ้างอิงแบบเลข
        if ($hasCodeJobCol) {
            $cols[] = 'code_job';
            $vals[] = $code_job > 0 ? (string)(int)$code_job : "NULL";
        }
        if ($hasCodeDeptCol) {
            $cols[] = 'code_department';
            $vals[] = $code_department > 0 ? (string)(int)$code_department : "NULL";
        }
        if ($hasPaperNameCol) {
            $cols[] = 'paper_name';
            $vals[] = "'" . mysqli_real_escape_string($db_Project, $paper_name) . "'";
        }
        if ($hasPaperStartCol) {
            $cols[] = 'paper_start';
            $vals[] = "'" . mysqli_real_escape_string($db_Project, $paper_start) . "'";
        }

        $sqlInsertUnit = "INSERT INTO set_unit(" . implode(',', $cols) . ") VALUES(" . implode(',', $vals) . ")";

        $sqlUnit = mysqli_query($db_Project, $sqlInsertUnit);
        if (!$sqlUnit) {
            $err = mysqli_error($db_Project);
            @error_log('save_unit_ajax: insert set_unit failed: ' . $err);
            json_fail('บันทึกหน่วยงานไม่สำเร็จ', $debug ? ['debug' => ['sql' => $sqlInsertUnit, 'mysqli_error' => $err, 'unit_code' => $unit_code, 'job_code' => $job_code_value, 'dep_code' => $dep_code_value]] : []);
        }
        $new_unit_id = (int) mysqli_insert_id($db_Project);
    } catch (Throwable $e) {
        // กรณี mysqli strict โยน exception
        @error_log('save_unit_ajax: insert set_unit exception: ' . $e->getMessage());
        json_fail('บันทึกหน่วยงานไม่สำเร็จ', $debug ? ['debug' => ['exception' => $e->getMessage(), 'unit_code' => $unit_code]] : []);
    }

    $vcd = $code_department > 0 ? "'" . (int)$code_department . "'" : 'NULL';
    $vcj = $code_job > 0 ? "'" . (int)$code_job . "'" : 'NULL';

	$inserted = 0;
	$failed = 0;

	// รายชื่อบุคลากรเป็นทางเลือก — กำหนดทีหลังจากหน้าข้อมูลบุคลากรได้
	if (!empty($staff_codes)) {
		$codesEsc = array_map(function ($c) use ($db_person) {
			return mysqli_real_escape_string($db_person, (string)$c);
		}, $staff_codes);
		$in = "'" . implode("','", $codesEsc) . "'";

		$personnelInfoMap = [];
		$sqlStaff = mysqli_query(
			$db_person,
			"SELECT id, code, unit_code, job_code, dep_code, CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
			 FROM staff
			 WHERE work_status IN ('0','1') AND code IN ($in)"
		);
		if (!$sqlStaff) {
			$err = mysqli_error($db_person);
			@error_log('save_unit_ajax: select staff failed: ' . $err);
			if ($debug) {
				json_fail('ดึงรายชื่อบุคลากรไม่สำเร็จ', ['debug' => ['mysqli_error' => $err]]);
			}
		}
		if ($sqlStaff) {
			while ($r = mysqli_fetch_assoc($sqlStaff)) {
				$personnelInfoMap[(string)($r['code'] ?? '')] = [
					'staff_id' => (int)($r['id'] ?? 0),
					'name' => trim((string)($r['full_name'] ?? '')),
				];
			}
		}

		$vcuNew = "'" . (int)$new_unit_id . "'";
		foreach ($staff_codes as $staff_code_raw) {
			$person = $personnelInfoMap[(string)$staff_code_raw] ?? null;
			$pName = trim((string)($person['name'] ?? ''));
			$staffId = (int)($person['staff_id'] ?? 0);
			if ($pName === '') {
				continue;
			}
			$pNameEsc = mysqli_real_escape_string($db_Project, $pName);
			$vStaffId = $staffId > 0 ? "'" . $staffId . "'" : 'NULL';
			$dupWhere = $staffId > 0
				? "(staff_id = $vStaffId OR name = '$pNameEsc')"
				: "name = '$pNameEsc'";
			$chk = mysqli_query($db_Project, "SELECT 1 FROM set_project_personnel WHERE code_unit=$vcuNew AND $dupWhere AND status!='99' LIMIT 1");
			if ($chk && mysqli_num_rows($chk) > 0) {
				continue;
			}
			$ok = mysqli_query(
				$db_Project,
				"INSERT INTO set_project_personnel(name,staff_id,code_department,code_job,code_unit,status)
				 VALUES('$pNameEsc',$vStaffId,$vcd,$vcj,'$new_unit_id','$status')"
			);
			if ($ok) {
				$inserted++;
			} else {
				$failed++;
				@error_log('save_unit_ajax: insert personnel failed: ' . mysqli_error($db_Project));
			}
		}
	}

    json_ok([
        'unit_id' => $new_unit_id,
        'unit_code' => $unit_code,
        'inserted' => $inserted,
        'failed' => $failed,
    ]);
} catch (Throwable $e) {
    @error_log('save_unit_ajax.php: ' . $e->getMessage());
	json_fail('เกิดข้อผิดพลาดระหว่างบันทึก', $debug ? ['debug' => ['exception' => $e->getMessage()]] : []);
}

