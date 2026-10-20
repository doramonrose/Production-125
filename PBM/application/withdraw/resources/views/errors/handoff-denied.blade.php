<x-layouts.app title="ยืนยันตัวตนไม่สำเร็จ">
    <div class="mx-2 py-6">
        <div class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-red-900">{{ session('error_title') ?: 'ไม่สามารถยืนยันสิทธิ์เข้าใช้งานได้' }}</h1>
                    <p class="mt-1 text-sm text-red-800">
                        {{ session('error') ?: 'ข้อมูลผู้ใช้งานจากระบบต้นทางไม่ถูกต้องหรือหมดอายุ' }}
                    </p>
                    <p class="mt-3 text-sm text-red-700">
                        กรุณากลับไปยังระบบหลักและกดเข้าใช้งานใหม่อีกครั้ง
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
