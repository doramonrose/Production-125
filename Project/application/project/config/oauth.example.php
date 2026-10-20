<?php
/**
 * คัดลอกเป็น oauth.local.php แล้วใส่ค่าจริง (ไฟล์ .local ไม่ควร commit)
 * ถ้ามี KPI/config/kpi_oauth.local.php อยู่แล้ว ระบบจะใช้ client_id / client_secret จากไฟล์นั้นให้อัตโนมัติ
 *
 * ระบบหลักยังเป็น HTTP ได้ — Azure ต้องใช้ Redirect URI แบบ HTTPS เช่น
 * https://172.17.1.145:10443/project/oauth-cmu.php
 * (พอร์ต SSL ดูที่ NGINX_SSL_PORT ใน .env)
 * ถ้าเว้นว่าง ระบบจะประกอบ URL HTTPS ให้อัตโนมัติ
 *
 * @return array<string, string>
 */
return [
	'client_id' => 'YOUR_CLIENT_ID',
	'client_secret' => 'YOUR_CLIENT_SECRET',
	'redirect_uri' => '',
];
