# Production-125

รหัสโปรเจกต์อยู่ในโฟลเดอร์ `Project/`

## โครงสร้าง
- `Project/application` — โค้ดแอป (รวมโมดูล project / KPI)
- `Project/config` — ค่าคอนฟิก (ใช้ตัวแปรแวดล้อมสำหรับรหัสผ่าน DB)
- `Project/docker-compose.yml`, `nginx`, `php-fpm` — สำหรับ deploy

อย่า commit ไฟล์ `.env` หรือรหัสผ่านฐานข้อมูลจริง
