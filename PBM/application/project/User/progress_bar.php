<?php
include_once(__DIR__ . '/../../config/config_db.php');

$page         = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
$op_from      = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
$years_select = isset($_GET['years_select']) ? trim((string)$_GET['years_select']) : '';
$project_id   = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$overview     = isset($_GET['overview']) ? trim((string)$_GET['overview']) : '';


$year_list = getYearSelect($db_pbmBudget);
$pre_project_data = getPreProjectID($db_Project, $project_id);

// หัวหน้าโครงการ
$head_list = getProjectHeadList($db_Project, $project_id);
$text_head = 'หัวหน้าโครงการ';
if (!empty($head_list)) {
    $status_head = "done";
    $icon_head = "check";
} else {
    $icon_head = "times";
}
//ผู้ประสานงาน
$staff_list = getProjectStaffList($db_Project, $project_id);
$text_staff = 'ผู้ประสานงาน';
if (!empty($staff_list)) {
    $status_staff = "done";
    $icon_staff = "check";
} else {
    $icon_staff = "times";
}
//หน่วยงานหลัก
$department_list = getProjectDepartmentList($db_Project, $project_id, 1);
$text_department = 'หน่วยงานหลัก';
if (!empty($department_list)) {
    $status_department = "done";
    $icon_department = "check";
} else {
    $icon_department = "times";
}
//ลักษณะโครงการ
$nature_list = getPreProjectID($db_Project, $project_id);
$text_nature = 'ลักษณะโครงการ';
if (!empty($nature_list['code_nature'])) {
    $status_nature = "done";
    $icon_nature = "check";
} else {
    $icon_nature = "times";
}
//ประเภทโครงการ
$type_list = getPreProjectID($db_Project, $project_id);
$text_type = 'ประเภทโครงการ';
if (!empty($type_list['code_type'])) {
    $status_type = "done";
    $icon_type = "check";
} else {
    $icon_type = "times";
}
//แหล่งงบ
$budget_list = getProjectBudgetList($db_Project, $project_id);
$text_budget = 'แหล่งงบ';
if (!empty($budget_list)) {
    $status_budget = "done";
    $icon_budget = "check";
} else {
    $icon_budget = "times";
}
$text_start_month = 'ระยะเวลา';
if (!empty($pre_project_data['start_month'])) {
    $status_start_month = "done";
    $icon_start_month = "check";
} else {
    $icon_start_month = "times";
}
$text_location = 'พื้นที่';
if (!empty($pre_project_data['location'])) {
    $status_location = "done";
    $icon_location = "check";
} else {
    $icon_location = "times";
}
$text_reson = 'ความสำคัญ';
if (!empty($pre_project_data['reson'])) {
    $status_reson = "done";
    $icon_reson = "check";
} else {
    $icon_reson = "times";
}
$text_result = 'ผลลัพธ์';
if (!empty($pre_project_data['result'])) {
    $status_result = "done";
    $icon_result = "check";
} else {
    $icon_result = "times";
}
$text_objective = 'วัตถุประสงค์';
if (!empty($pre_project_data['objective'])) {
    $status_objective = "done";
    $icon_objective = "check";
} else {
    $icon_objective = "times";
}
//ความสอดคล้องโครงการ
$consistency_data = getProjectConsistency($db_Project, $project_id);
$text_consistency = 'ความเชื่อมโยงกับแผนและตัวชี้วัด';
if (!empty($consistency_data['code_consistency'])) {
    $status_consistency = "done";
    $icon_consistency = "check";
} else {
    $icon_consistency = "times";
}
$text_target = 'เป้าหมาย';
if (!empty($pre_project_data['target_project'])) {
    $status_target = "done";
    $icon_target = "check";
} else {
    $icon_target = "times";
}
//ตัวชี้วัดโครงการ
$project_kpi_list = getProjectKPIList($db_Project, $project_id);
$text_kpi = 'ตัวชี้วัด';
if (!empty($project_kpi_list)) {
    $status_kpi = "done";
    $icon_kpi = "check";
} else {
    $icon_kpi = "times";
}
//กิจกรรมโครงการ
$project_activity_list = getProjectActivityList($db_Project, $project_id);
$text_activity = 'กิจกรรม';
if (!empty($project_activity_list)) {
    $status_activity = "done";
    $icon_activity = "check";
} else {
    $icon_activity = "times";
}

// รวมสถานะทั้งหมด
$all_status = [
    $status_head ?? '',
    $status_staff ?? '',
    $status_department ?? '',
    $status_nature ?? '',
    $status_type ?? '',
    $status_budget ?? '',
    $status_start_month ?? '',
    $status_location ?? '',
    $status_reson ?? '',
    $status_result ?? '',
    $status_objective ?? '',
    $status_consistency ?? '',
    $status_target ?? '',
    $status_kpi ?? '',
    $status_activity ?? '',
];

// ฟังก์ชันเช็คว่าทุกค่าใน array = "done"
$all_done = !in_array("", $all_status, true) && !in_array("times", $all_status, true);
?>

<style>
        .step-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px auto;
            width: 100%;
            max-width: 100%;
            /* แก้ให้เต็มจอ */
            position: relative;
            padding: 0 20px;
        }

        .step {
            text-align: center;
            position: relative;
            flex: 1;
        }

        /* วงกลมพื้นฐาน */
        .step .circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 4px solid #bfc7d1;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
            background: #fff;
            color: #bfc7d1;
            margin: 0 auto 10px auto;
            z-index: 2;
            position: relative;
        }

        /* Label */
        .step .label {
            font-size: 14px;
            color: #5f5f5fff;
        }

        /* เส้นพื้นหลังเต็ม timeline */
        .step-timeline::before {
            content: "";
            position: absolute;
            top: 24px;
            left: 0;
            right: 0;
            height: 4px;
            background: #d9dde2;
            z-index: 1;
        }

        /* เส้น progress สีเขียว: ทำเฉพาะ steps ที่เป็น done หรือ active */
        .step.done::after,
        .step.active::after {
            content: "";
            position: absolute;
            top: 24px;
            left: 50%;
            width: 100%;
            height: 4px;
            background: #53d7b4ff;
            z-index: 1;
        }

        /* วงกลม active */
        .step.active .circle {
            border-color: #007bff;
            color: #007bff;
            font-weight: bold;
        }

        /* วงกลมสำเร็จ done */
        .step.done .circle {
            background: #53d7b4ff;
            border-color: #53d7b4ff;
            color: white;
            font-size: 20px;
        }

        /* วงกลมล้มเหลว failed */
        .step.failed .circle {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
            font-size: 20px;
        }

        /* ไม่ให้เส้นเกิน step สุดท้าย */
        .step:last-child::after {
            display: none;
        }
</style>

<div class="progress-bar-wrapper">
    <div class="step-timeline">

        <div class="step <?= $status_head; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_head; ?>"></i></div><span class="label"><?= $text_head; ?></span>
        </div>

        <div class="step <?= $status_staff; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_staff; ?>"></i></div><span class="label"><?= $text_staff; ?></span>
        </div>

        <div class="step <?= $status_department; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_department; ?>"></i></div><span class="label"><?= $text_department; ?></span>
        </div>

        <div class="step <?= $status_nature; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_nature; ?>"></i></div><span class="label"><?= $text_nature; ?></span>
        </div>

        <div class="step <?= $status_type; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_type; ?>"></i></div><span class="label"><?= $text_type; ?></span>
        </div>
        <div class="step <?= $status_budget; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_budget; ?>"></i></div><span class="label"><?= $text_budget; ?></span>
        </div>
        <div class="step <?= $status_start_month; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_start_month; ?>"></i></div><span class="label"><?= $text_start_month; ?></span>
        </div>
        <div class="step <?= $status_location; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_location; ?>"></i></div><span class="label"><?= $text_location; ?></span>
        </div>
        <div class="step <?= $status_reson; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_reson; ?>"></i></div><span class="label"><?= $text_reson; ?></span>
        </div>
        <div class="step <?= $status_result; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_result; ?>"></i></div><span class="label"><?= $text_result; ?></span>
        </div>
        <div class="step <?= $status_objective; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_objective; ?>"></i></div><span class="label"><?= $text_objective; ?></span>
        </div>
        <div class="step <?= $status_consistency; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_consistency ?>"></i></div><span class="label"><?= $text_consistency; ?></span>
        </div>
        <div class="step <?= $status_target; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_target; ?>"></i></div><span class="label"><?= $text_target; ?></span>
        </div>
        <div class="step <?= $status_kpi; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_kpi; ?>"></i></div><span class="label"><?= $text_kpi; ?></span>
        </div>
        <div class="step <?= $status_activity; ?>">
            <div class="circle"><i class="fa fa-<?= $icon_activity; ?>"></i></div><span class="label"><?= $text_activity; ?></span>
        </div>

    </div>

    <div class="w-100 text-center mt-4 mb-5">

        <?php if ($all_done) { ?>
            <?php if ($pre_project_data['status_project'] == 0) { ?>
                <!-- ปุ่มเปิด Modal -->
                <button class="btn btn-warning px-4 py-2 text-120"
                    data-toggle="modal"
                    data-target="#modalConfirmSubmitPlan">
                    ✔ ขอบรรจุโครงการ
                </button>
            <?php } else { ?>
                <button class="btn btn-secondary px-4 py-2 mb-2" disabled>
                    ✔ ได้ขอบรรจุโครงการไปแล้ว
                </button>
            <?php } ?>
        <?php } else { ?>

            <div class="d-flex flex-column align-items-center">
                <button class="btn btn-secondary px-4 py-2 mb-2" disabled>
                    ⚠ กรอกข้อมูลให้ครบก่อน
                </button>

                <small class="text-muted">
                    กรุณากรอกข้อมูลให้ครบก่อน จึงจะสามารถขอบรรจุโครงการได้
                </small>
            </div>

        <?php } ?>

    </div>

    <!-- Modal ยืนยันการขอบรรจุโครงการ -->
    <div class="modal fade" id="modalConfirmSubmitPlan" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" id="modalLabel">
                        <i class="fa fa-exclamation-circle"></i> ยืนยันการขอบรรจุโครงการ
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body text-center">
                    <p class="text-dark">
                        คุณต้องการ<b>ยืนยันการขอบรรจุโครงการ</b>สำหรับโครงการนี้หรือไม่?
                    </p>

                    <!-- <p class="text-muted">
                        หลังจากขอบรรจุโครงการแล้วจะไม่สามารถแก้ไขข้อมูลบางส่วนได้
                    </p> -->
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">
                        ยกเลิก
                    </button>

                    <!-- ปุ่มส่งไปอัปเดตสถานะในระบบ เช่น op=submit_plan -->
                    <?php
                    $ep = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
                    $ey = htmlspecialchars($years_select, ENT_QUOTES, 'UTF-8');
                    $eo = htmlspecialchars($overview, ENT_QUOTES, 'UTF-8');
                    ?>
                    <a href="?page=<?= $ep ?>&op=submit_plan&project_id=<?= $project_id ?>&years_select=<?= $ey ?>&overview=<?= $eo ?>"
                        class="btn btn-success">
                        ✔ ยืนยัน
                    </a>
                </div>

            </div>
        </div>
    </div>

</div>