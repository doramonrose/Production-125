# Production-125

รหัสโปรเจกต์อยู่ในโฟลเดอร์ `PBM/`

## โครงสร้าง
- `PBM/application` — โค้ดแอป (รวมโมดูล project / KPI)
- `PBM/config` — ค่าคอนฟิก (ใช้ตัวแปรแวดล้อมสำหรับรหัสผ่าน DB)
- `PBM/docker-compose.yml`, `nginx`, `php-fpm` — สำหรับ deploy

อย่า commit ไฟล์ `.env` หรือรหัสผ่านฐานข้อมูลจริง
