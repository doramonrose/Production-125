<?php
/**
 * ตัวอย่างไฟล์ตั้งค่าความลับสำหรับ Pharmacy CMU Portal Integration
 * คัดลอกเป็น portal.local.php (ห้าม commit) หรือกำหนดผ่าน environment variables
 */
return [
    'PORTAL_SYSTEM_CODE' => 'pbm_project',
    'PORTAL_SYSTEM_NAME' => 'ระบบบริหารโครงการ',
    'PORTAL_APP_URL' => 'https://pbm.pharmacy.cmu.ac.th/application/project',
    'PORTAL_API_TOKEN' => 'REPLACE_WITH_64_CHAR_ALPHANUMERIC_CSPRNG_TOKEN',
    'PORTAL_WEBHOOK_URL' => 'https://mis.pharmacy.cmu.ac.th/rx_portal/api/v1/integrations/pbm_project/webhook',
    'PORTAL_WEBHOOK_SECRET' => 'REPLACE_WITH_64_CHAR_ALPHANUMERIC_CSPRNG_SECRET',
    'PORTAL_ACTIONS_ENABLED' => 'true',
];
