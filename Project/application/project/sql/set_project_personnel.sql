-- ฐานข้อมูล: project
-- บุคลากรอ้างอิง master ในฐานเดียวกัน: set_department, set_job, set_unit

CREATE TABLE IF NOT EXISTS set_project_personnel (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(512) NOT NULL DEFAULT '',
    code_department INT UNSIGNED NULL DEFAULT NULL COMMENT 'project.set_department.id',
    code_job INT UNSIGNED NULL DEFAULT NULL COMMENT 'project.set_job.id',
    code_unit INT UNSIGNED NULL DEFAULT NULL COMMENT 'project.set_unit.id',
    status VARCHAR(8) NOT NULL DEFAULT '1',
    PRIMARY KEY (id),
    KEY idx_spp_code_department (code_department),
    KEY idx_spp_code_job (code_job),
    KEY idx_spp_code_unit (code_unit),
    KEY idx_spp_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
