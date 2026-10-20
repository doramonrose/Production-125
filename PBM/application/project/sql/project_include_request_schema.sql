-- =============================================================================
-- โครงสร้างตารางสำหรับคำขอ "ขอบรรจุโครงการ" (ตามฟอร์มยืนยันขอบรรจุในระบบ)
-- ฐานข้อมูล: project (เช่น MySQL/MariaDB, InnoDB, utf8mb4)
-- หมายเหตุ: staff_id อ้างอิงตาราง staff ใน DB person — ไม่สร้าง FK ข้ามฐานข้อมูล
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1) หัวคำขอ (ส่วนงาน, เรื่อง, เนื้อหาหนังสือ, ปีงบประมาณ, ผู้ส่ง)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_include_request (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    budget_year     VARCHAR(32)  NOT NULL DEFAULT '' COMMENT 'ปีงบประมาณ (พ.ศ.)',
    section_name    TEXT         NULL COMMENT 'ส่วนงาน',
    subject         TEXT         NULL COMMENT 'เรื่อง',
    letter_body     MEDIUMTEXT   NULL COMMENT 'เนื้อหาหนังสือ/คำขอ (บล็อกข้อความ)',
    submitter_staff_id INT UNSIGNED NULL COMMENT 'ผู้ส่งคำขอ → staff.id (DB person)',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    status          VARCHAR(32)  NOT NULL DEFAULT 'submitted' COMMENT 'สถานะคำขอ เช่น submitted',
    PRIMARY KEY (id),
    KEY idx_pir_year (budget_year),
    KEY idx_pir_created (created_at),
    KEY idx_pir_submitter (submitter_staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='คำขอขอบรรจุโครงการ (หนึ่งครั้งต่อการกดส่งหนึ่งรอบ)';

-- -----------------------------------------------------------------------------
-- 2) โครงการที่รวมในคำขอนี้ (เชื่อมกับ pre_project)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_include_request_item (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id      INT UNSIGNED NOT NULL COMMENT 'FK → project_include_request.id',
    project_id      INT UNSIGNED NOT NULL COMMENT 'FK → pre_project.id',
    sort_order      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ลำดับแสดง (ถ้าต้องการ)',
    PRIMARY KEY (id),
    UNIQUE KEY uk_piri_req_proj (request_id, project_id),
    KEY idx_piri_project (project_id),
    CONSTRAINT fk_piri_request
        FOREIGN KEY (request_id) REFERENCES project_include_request (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='รายการโครงการที่เลือกในคำขอขอบรรจุ';

-- หาก pre_project อยู่คนละฐานข้อมูล ให้ลบ CONSTRAINT ด้านบน แล้วเก็บเฉพาะ project_id เป็นเลขอ้างอิง

-- -----------------------------------------------------------------------------
-- 3) สรุปตามความสอดคล้อง (snapshot ณ เวลาส่ง — ตารางสรุปในหน้าจอ)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_include_request_summary (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id          INT UNSIGNED NOT NULL COMMENT 'FK → project_include_request.id',
    sort_order          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ลำดับแถวบนจอ',
    row_kind            VARCHAR(16)  NOT NULL DEFAULT 'data' COMMENT 'header=หัวกลุ่ม, data=แถวข้อมูล, total=แถวรวม',
    section_group       VARCHAR(32)  NULL COMMENT 'strategic|mission|other หรือ NULL สำหรับแถวรวม',
    consistency_label   VARCHAR(512) NOT NULL DEFAULT '' COMMENT 'ข้อความในคอลัมน์ความสอดคล้อง',
    project_count       INT UNSIGNED NULL COMMENT 'จำนวนโครงการ (NULL ถ้าเป็นแถวหัวกลุ่ม)',
    budget_baht         DECIMAL(15,2) NULL COMMENT 'งบประมาณ (บาท)',
    PRIMARY KEY (id),
    KEY idx_pirs_request (request_id),
    KEY idx_pirs_sort (request_id, sort_order),
    CONSTRAINT fk_pirs_request
        FOREIGN KEY (request_id) REFERENCES project_include_request (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='สรุปรายการตามความสอดคล้อง (จัดกลุ่มเชิงยุทธศาสตร์/เชิงพันธกิจ/อื่นๆ และแถวรวม)';

-- -----------------------------------------------------------------------------
-- 4) ผู้รับรองเรียงลำดับ (คนสุดท้าย = ลงนามก่อนส่งแผน)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_include_request_certifier (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id      INT UNSIGNED NOT NULL COMMENT 'FK → project_include_request.id',
    sort_order      TINYINT UNSIGNED NOT NULL COMMENT 'ลำดับการพิจารณา 1,2,3,...',
    staff_id        INT UNSIGNED NOT NULL COMMENT 'ผู้รับรอง → staff.id (DB person)',
    position_title  VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'ตำแหน่ง (กรอกในฟอร์ม)',
    PRIMARY KEY (id),
    UNIQUE KEY uk_pirc_req_order (request_id, sort_order),
    KEY idx_pirc_staff (staff_id),
    CONSTRAINT fk_pirc_request
        FOREIGN KEY (request_id) REFERENCES project_include_request (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='ผู้รับรองโครงการตามลำดับ';

-- =============================================================================
-- ตัวอย่างการบันทึกแถวสรุป (อ้างอิง logic หน้าจอ)
-- row_kind = header  → หัวข้อกลุ่ม เช่น "เชิงยุทธศาสตร์" (project_count, budget_baht เป็น NULL)
-- row_kind = data    → แถว AP/ด้าน ย่อย พร้อมจำนวนโครงการและงบ
-- row_kind = total   → แถว "รวม" ท้ายตาราง
-- =============================================================================
