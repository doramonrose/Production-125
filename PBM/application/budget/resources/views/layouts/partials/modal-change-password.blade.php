@if (\Illuminate\Support\Facades\Route::has('password.update'))
<x-modal name="change-password" maxWidth="md" variant="modern" title="แก้ไขรหัสผ่าน"
    description="เปลี่ยนรหัสผ่านสำหรับการเข้าสู่ระบบด้วยอีเมลและรหัสผ่าน" iconClass="fas fa-key">
    <div class="text-sm text-slate-600">
        {{-- เนื้อหาฟอร์มจะถูกเติมในภายหลัง --}}
    </div>
</x-modal>
@endif
