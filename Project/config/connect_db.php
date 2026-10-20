<?php
/**
 * ตัวอย่างการเชื่อมต่อฐานข้อมูล — กรอกค่าจริงบนเซิร์ฟเวอร์ (อย่า commit รหัสผ่าน)
 */
\$host_Project = getenv('DB_PROJECT_HOST') ?: '127.0.0.1';
\$user_Project = getenv('DB_PROJECT_USER') ?: 'your_user';
\$pass_Project = getenv('DB_PROJECT_PASS') ?: 'your_password';
\$db_name_Project = getenv('DB_PROJECT_NAME') ?: 'project_db';

\$host_KPI = getenv('DB_KPI_HOST') ?: '127.0.0.1';
\$user_KPI = getenv('DB_KPI_USER') ?: 'your_user';
\$pass_KPI = getenv('DB_KPI_PASS') ?: 'your_password';
\$db_name_KPI = getenv('DB_KPI_NAME') ?: 'kpi_db';

// ... ต่อด้วยการ mysqli_connect ตามโครงสร้างเดิมของโปรเจกต์
