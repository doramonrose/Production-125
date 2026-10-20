<x-layouts.app>
    <div class="py-2">
        <div
            class="mx-2 rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur-sm lg:p-8">
            @php
                $moduleBadge = match ($module ?? '') {
                    'year' => 'ปีงบประมาณ',
                    'plan' => 'แผนงาน',
                    'event' => 'งาน-กิจกรรม',
                    'activity' => 'กิจกรรม',
                    'fund' => 'กองทุน',
                    'cost' => 'ต้นทุน',
                    'source' => 'แหล่งงบ',
                    'type' => 'ประเภทงบ',
                    'category-budget' => 'หมวดงบ',
                    'category-pay' => 'หมวดรายจ่าย',
                    'pay-list' => 'รายการจ่าย',
                    'expenses' => 'รายจ่ายย่อย',
                    default => 'ตั้งค่าข้อมูลพื้นฐาน',
                };
            @endphp
            <div
                class="relative mb-4 overflow-hidden rounded-2xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50/80 to-emerald-50/50 p-4 shadow-sm shadow-slate-200/40 sm:px-2 sm:pb-1 sm:pt-2">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-400/15 blur-2xl sm:h-36 sm:w-36"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-emerald-200/60 to-transparent"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 gap-4">
                        <div
                            class="flex h-12 w-12 mt-2 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-lg text-white shadow-lg shadow-emerald-500/30 ring-4 ring-white/80">
                            <i class="fas fa-sliders-h" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            {{-- <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-emerald-900 ring-1 ring-emerald-100/80">{{ $moduleBadge }}</span>
                            </div> --}}
                            <h4 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.65rem]">
                                {{ $title ?? 'ตั้งค่าระบบ' }}</h4>
                            <p class="mb-0 max-w-2xl text-sm leading-snug text-slate-500">
                                {{ ($module ?? '') === 'pay-list' ? 'รายการรหัสงบประมาณ' : 'ค้นหา แก้ไข เปิด/ปิดสถานะ และจัดการรายการได้จากตารางด้านล่าง' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Box --}}
            <div class="mb-4 flex items-center justify-between gap-3">
                {{-- <p class="text-xs text-slate-500">ค้นหา แก้ไข และจัดการสถานะรายการได้จากตารางด้านล่าง</p> --}}
                <div class="relative w-full max-w-sm flex-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input id="search-input" type="search"
                        class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 placeholder-slate-400 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        placeholder="ค้นหารายการ..." />
                </div>
                <button type="button" id="btn-add"
                    class="group inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-green-700/20 bg-gradient-to-r from-green-500 to-green-500 px-4 py-3 text-xs font-bold uppercase tracking-wide text-white shadow-lg shadow-green-200/70 ring-1 ring-white/30 transition-all duration-200 hover:-translate-y-0.5 hover:from-green-600 hover:to-green-600 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-green-300">
                    <i class="fas fa-plus text-[11px] transition-transform duration-200 group-hover:rotate-90"></i>
                    <span>เพิ่มข้อมูล</span>
                </button>
            </div>

            @if ($module === 'expenses')
                <div id="available-withdraw-expenses" class="mb-3 hidden rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <i class="fas fa-list-check text-xs"></i>
                        </span>
                        <span class="text-sm font-bold text-emerald-900">ผังบัญชี (รายจ่าย) ที่ยังไม่ได้ใช้งาน</span>
                        <span id="available-withdraw-expenses-count" class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">0</span>
                    </div>
                    <div id="available-withdraw-expenses-list" class="mt-2 max-h-48 space-y-2 overflow-y-auto pr-1"></div>
                </div>
            @endif

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="w-full table-auto text-sm [&_td]:align-top">
                    <thead class="sticky top-0 z-10">
                        <tr
                            class="border-b border-slate-200 bg-slate-50/95 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-700 backdrop-blur">
                            <th class="select-none whitespace-nowrap px-4 py-3" style="width:40px;">#</th>
                            <th class="select-none whitespace-nowrap px-4 py-3">รายการ</th>
                            <th class="select-none whitespace-nowrap px-4 py-3">รายละเอียด</th>
                            @if ($module === 'type')
                                <th class="select-none whitespace-nowrap px-4 py-3">การปรับงบ</th>
                            @endif
                            <th class="select-none whitespace-nowrap px-4 py-3">สถานะ</th>
                            <th class="select-none whitespace-nowrap px-4 py-3">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="settings-table" class="divide-y divide-slate-100">
                        {{-- Rows rendered via JS --}}
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <x-pagination-bar id="pagination-area" infoId="pagination-info" pageId="pagination-page" prevId="btn-prev"
                nextId="btn-next" viewAllId="btn-view-all" viewAllText="ดูทั้งหมด" />
        </div>
    </div>

    {{-- Modal: settings-form-modal --}}
    <x-modal name="settings-form-modal" maxWidth="lg" :static="true" variant="modern" title="เพิ่มข้อมูล"
        description="เพิ่ม/แก้ไขข้อมูลตั้งค่าระบบจากหน้าต่างเดียว" titleId="modal-title" iconClass="fas fa-sliders-h">
        <form id="settings-form" class="grid grid-cols-1 gap-4">
                <input type="hidden" id="f-id" />

                {{-- Plan select (for event module) --}}
                <div id="f-plan-wrap" class="hidden">
                    <label class="mb-1 block text-xs font-bold text-slate-700">แผนงานงบประมาณ</label>
                    <x-searchable-dropdown id="f-plan"
                        placeholder="เลือกหรือพิมพ์ค้นหาแผนงานงบประมาณ..."
                        :options="$optionsData['plans'] ?? []" valueKey="budget_plan_code"
                        textKey="budget_plan_name" optionsKey="plans" :showClear="true" />
                </div>

                {{-- Event select (for activity module) --}}
                <div id="f-event-wrap" class="hidden">
                    <label class="mb-1 block text-xs font-bold text-slate-700">งาน</label>
                    <x-searchable-dropdown id="f-event"
                        placeholder="เลือกหรือพิมพ์ค้นหางาน..."
                        :options="$optionsData['events'] ?? []" valueKey="budget_event_code"
                        textKey="budget_event_name" optionsKey="events" :showClear="true" />
                </div>

                {{-- Event Name input (for new event within event module) --}}
                <div id="f-event-name-wrap" class="hidden">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ชื่องาน</label>
                    <input id="f-event-name"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        placeholder="ชื่องานใหม่" />
                </div>

                {{-- Activity Name input (for event module adding activity) --}}
                <div id="f-activity-name-wrap" class="hidden">
                    <label class="mb-1 block text-xs font-bold text-slate-700">กิจกรรม (ถ้ามี)</label>
                    <input id="f-activity-name"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        placeholder="กิจกรรม" />
                </div>

                {{-- Category Pay select (for pay-list module) --}}
                <div id="f-category-pay-wrap" class="hidden">
                    <label class="mb-1 block text-xs font-bold text-slate-700">หมวดรายจ่าย</label>
                    <x-searchable-dropdown id="f-category-pay"
                        placeholder="เลือกหรือพิมพ์ค้นหาหมวดรายจ่าย..."
                        :options="$optionsData['categoryPays'] ?? []" valueKey="budget_category_pay_code"
                        textKey="budget_category_pay_name" optionsKey="categoryPays" :showClear="true" />
                </div>

                {{-- Pay List select (for expenses module) --}}
                <div id="f-pay-wrap" class="hidden">
                    <label class="mb-1 block text-xs font-bold text-slate-700">รายการจ่ายหลัก</label>
                    <x-searchable-dropdown id="f-pay"
                        placeholder="เลือกหรือพิมพ์ค้นหารายการจ่ายหลัก..."
                        :options="$optionsData['payLists'] ?? []" valueKey="budget_pay_code"
                        textKey="budget_pay_name" optionsKey="payLists" :showClear="true" />
                </div>

                <div id="f-withdraw-expenses-wrap" class="hidden">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ผังบัญชี (รายจ่าย)</label>
                    <select id="f-withdraw-expenses" multiple class="hidden"></select>
                    <div class="space-y-2 rounded-xl border border-slate-300 bg-white p-2 shadow-sm">
                        <input id="f-withdraw-expenses-search" type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="ค้นหาผังบัญชี..." />
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-slate-500" id="f-withdraw-expenses-selected">เลือกแล้ว 0 รายการ</div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="f-withdraw-expenses-select-all"
                                    class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">เลือกทั้งหมด</button>
                                <button type="button" id="f-withdraw-expenses-clear"
                                    class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">ล้าง</button>
                            </div>
                        </div>
                        <div id="f-withdraw-expenses-list" class="max-h-44 overflow-y-auto rounded-lg border border-slate-200 p-1">
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">เลือกได้หลายรายการแบบติ๊ก checkbox และค้นหาได้ (ไม่บังคับ)</p>
                </div>

                <div id="f-name-wrap">
                    <label class="mb-1 block text-xs font-bold text-slate-700" id="f-name-label">ชื่อ</label>
                    <input id="f-name"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                </div>

                <div id="f-direction-wrap" class="hidden">
                    <label class="mb-1 block text-xs font-bold text-slate-700">การปรับงบ</label>
                    <select id="f-direction"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="+">เพิ่มงบ</option>
                        <option value="-">ลดงบ</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">รายละเอียด (ถ้ามี)</label>
                    <input id="f-detail"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                </div>

                <div class="mt-3 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" @click="$dispatch('close-modal', 'settings-form-modal')"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-100 hover:text-slate-800 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"><i
                            class="fas fa-times mr-1"></i>ยกเลิก</button>
                    <button type="submit"
                        class="rounded-xl !bg-[#16a34a] !text-white px-4 py-2 text-sm font-bold shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:!bg-[#15803d] hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-green-300">
                        <i class="far fa-save mr-1"></i>บันทึก
                    </button>
                </div>
            </form>
    </x-modal>

    @if (($module ?? '') === 'expenses')
        <x-modal name="bind-withdraw-expense-modal" maxWidth="lg" :static="true" variant="modern"
            title="ผูกผังบัญชีกับรายจ่ายย่อย" description="เลือกรายจ่ายย่อยที่มีอยู่ หรือเพิ่มรายการใหม่แล้วผูกกับผังบัญชีนี้"
            titleId="bind-withdraw-expense-title" iconClass="fas fa-link">
            <div id="bind-account-summary" class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3 text-sm text-emerald-950"></div>

            <div class="mt-4">
                <label for="bind-existing-search" class="mb-1 block text-xs font-bold text-slate-700">เลือกรายจ่ายย่อยที่มีอยู่</label>
                <input id="bind-existing-search" type="search" placeholder="ค้นหารายจ่ายย่อย..."
                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                <div id="bind-existing-list" class="mt-2 max-h-48 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-1"></div>
            </div>

            <div class="my-4 flex items-center gap-3 text-xs font-semibold text-slate-400">
                <span class="h-px flex-1 bg-slate-200"></span>
                <span>หรือเพิ่มรายจ่ายย่อยใหม่</span>
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label for="bind-new-name" class="mb-1 block text-xs font-bold text-slate-700">ชื่อรายจ่ายย่อยใหม่</label>
                    <input id="bind-new-name" placeholder="ชื่อรายจ่ายย่อย"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                </div>
                <div>
                    <label for="bind-new-detail" class="mb-1 block text-xs font-bold text-slate-700">รายละเอียด (ถ้ามี)</label>
                    <input id="bind-new-detail"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" id="bind-withdraw-cancel"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-100">ยกเลิก</button>
                <button type="button" id="bind-withdraw-save"
                    class="rounded-xl !bg-[#16a34a] px-4 py-2 text-sm font-bold !text-white shadow-md hover:!bg-[#15803d]">
                    <i class="fas fa-link mr-1"></i>บันทึกการผูก
                </button>
            </div>
        </x-modal>
    @endif

    {{-- Confirm delete (generic) --}}
    <x-confirm-modal
        name="settings-delete-confirm-modal"
        maxWidth="sm"
        :title="__('ยืนยันการลบรายการ?')"
        :message="__('คุณต้องการลบรายการนี้หรือไม่')"
        confirmText="{{ __('ลบ') }}"
        cancelText="{{ __('ยกเลิก') }}"
        confirmVariant="danger"
        confirmClick="window.__settingsConfirmDelete()"
    />

    @push('scripts')
        <script>
            document.addEventListener('alpine:initialized', () => {
                const module = @json($module);
                const base = @json(url('/budget/settings/api'));
                const table = document.getElementById('settings-table');
                const btnAdd = document.getElementById('btn-add');
                const form = document.getElementById('settings-form');
                const searchInput = document.getElementById('search-input');

                const fId = document.getElementById('f-id');
                const fName = document.getElementById('f-name');
                const fDetail = document.getElementById('f-detail');
                const fDirection = document.getElementById('f-direction');
                const fPlan = document.getElementById('f-plan');
                const fEvent = document.getElementById('f-event');
                const fPlanText = document.getElementById('f-plan-text');
                const fEventText = document.getElementById('f-event-text');
                const fPlanClear = document.getElementById('f-plan-clear');
                const fEventClear = document.getElementById('f-event-clear');
                const fPlanSuggest = document.getElementById('f-plan-suggest');
                const fEventSuggest = document.getElementById('f-event-suggest');
                const fEventName = document.getElementById('f-event-name');
                const fActivityName = document.getElementById('f-activity-name');
                const fCategoryPay = document.getElementById('f-category-pay');
                const fCategoryPayText = document.getElementById('f-category-pay-text');
                const fCategoryPayClear = document.getElementById('f-category-pay-clear');
                const fCategoryPaySuggest = document.getElementById('f-category-pay-suggest');
                const fPay = document.getElementById('f-pay');
                const fPayText = document.getElementById('f-pay-text');
                const fPayClear = document.getElementById('f-pay-clear');
                const fPaySuggest = document.getElementById('f-pay-suggest');
                const fWithdrawExpenses = document.getElementById('f-withdraw-expenses');
                const fWithdrawExpensesSearch = document.getElementById('f-withdraw-expenses-search');
                const fWithdrawExpensesSelected = document.getElementById('f-withdraw-expenses-selected');
                const fWithdrawExpensesList = document.getElementById('f-withdraw-expenses-list');
                const fWithdrawExpensesSelectAll = document.getElementById('f-withdraw-expenses-select-all');
                const fWithdrawExpensesClear = document.getElementById('f-withdraw-expenses-clear');
                const availableWithdrawExpenses = document.getElementById('available-withdraw-expenses');
                const availableWithdrawExpensesCount = document.getElementById('available-withdraw-expenses-count');
                const availableWithdrawExpensesList = document.getElementById('available-withdraw-expenses-list');

                const wPlan = document.getElementById('f-plan-wrap');
                const wEvent = document.getElementById('f-event-wrap');
                const wEventName = document.getElementById('f-event-name-wrap');
                const wActivityName = document.getElementById('f-activity-name-wrap');
                const wCategoryPay = document.getElementById('f-category-pay-wrap');
                const wPay = document.getElementById('f-pay-wrap');
                const wWithdrawExpenses = document.getElementById('f-withdraw-expenses-wrap');
                const wNameWrap = document.getElementById('f-name-wrap');
                const wDirection = document.getElementById('f-direction-wrap');

                const fNameLabel = document.getElementById('f-name-label');
                const eventsByPlanUrl = @json(url('/budget/api/cascade/event-by-plan'));
                const withdrawSettingsApiBase = @json(url('/withdraw/setting/api'));

                // Pagination
                const btnPrev = document.getElementById('btn-prev');
                const btnNext = document.getElementById('btn-next');
                const btnViewAll = document.getElementById('btn-view-all');
                const paginationInfo = document.getElementById('pagination-info');
                const paginationPage = document.getElementById('pagination-page');

                let options = @json($optionsData);
                let allRows = [];
                let filteredRows = [];
                let currentPage = 1;
                let pageSize = 50;
                let viewAll = false;
                let editMode = ''; // 'event', 'activity' for event module
                let withdrawExpensesOptions = [];
                let withdrawExpenseNameByCode = {};

                const showIf = (el, on) => el.classList.toggle('hidden', !on);
                const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
                const toggleClearBtn = (btn, textEl) => btn.classList.toggle('hidden', !(textEl.value || '').trim());

                // Module labels
                const moduleLabels = {
                    'year': 'ปีงบประมาณ',
                    'plan': 'แผนงานงบประมาณ',
                    'event': 'งาน-กิจกรรม งบประมาณ',
                    'activity': 'กิจกรรม',
                    'fund': 'กองทุนงบประมาณ',
                    'cost': 'ต้นทุน',
                    'source': 'แหล่งงบประมาณ',
                    'type': 'ประเภทงบประมาณ',
                    'category-budget': 'หมวดงบประมาณ',
                    'category-pay': 'หมวดรายจ่าย',
                    'pay-list': 'รายการจ่าย',
                    'expenses': 'รายจ่าย',
                };
                const modLabel = moduleLabels[module] || module;

                // SweetAlert helper
                const swalFetch = async (url, data, loadingText = 'กำลังทำงาน...', successText = 'สำเร็จ') => {
                    // Use toast for alerts (success/error) instead of Swal dialogs.
                    // Keep SweetAlert for confirm dialogs elsewhere.
                    if (window.toast?.info) window.toast.info(loadingText, { timer: 1000 });
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf()
                            },
                            body: JSON.stringify(data)
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            window.toast?.success?.(successText, { timer: 1000 });
                        } else {
                            window.toast?.error?.(result.message || 'เกิดข้อผิดพลาด', { timer: 1200 });
                        }
                        return result;
                    } catch (err) {
                        window.toast?.error?.('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', { timer: 1200 });
                        console.error('❌ Fetch error:', err);
                        throw err;
                    }
                };

                // Delete confirm (uses confirm-modal component)
                let deleteUrl = '';
                let deleteId = 0;
                window.__settingsConfirmDelete = async () => {
                    const url = deleteUrl;
                    const id = deleteId;
                    deleteUrl = '';
                    deleteId = 0;

                    try {
                        const result = await swalFetch(url, { id }, 'กำลังลบ...', 'ลบสำเร็จ');
                        if (result?.status === 'success') render();
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'settings-delete-confirm-modal' }));
                    }
                };

                const openModal = (title) => {
                    document.getElementById('modal-title').textContent = title;
                    window.dispatchEvent(new CustomEvent('open-modal', {
                        detail: 'settings-form-modal'
                    }));
                };

                const closeModal = () => {
                    window.dispatchEvent(new CustomEvent('close-modal', {
                        detail: 'settings-form-modal'
                    }));
                };

                const resetForm = () => {
                    fId.value = '';
                    fName.value = '';
                    fDetail.value = '';
                    fDirection.value = '+';
                    fPlan.value = '';
                    fPlanText.value = '';
                    fEvent.value = '';
                    fEventText.value = '';
                    fEventName.value = '';
                    fActivityName.value = '';
                    fCategoryPay.value = '';
                    fCategoryPayText.value = '';
                    fPay.value = '';
                    fPayText.value = '';
                    if (fWithdrawExpensesSearch) fWithdrawExpensesSearch.value = '';
                    setWithdrawExpenseOptions([]);
                    editMode = '';
                    [fPlanSuggest, fEventSuggest, fCategoryPaySuggest, fPaySuggest].forEach(el => {
                        el?.classList.add('hidden');
                    });
                    toggleClearBtn(fPlanClear, fPlanText);
                    toggleClearBtn(fEventClear, fEventText);
                    toggleClearBtn(fCategoryPayClear, fCategoryPayText);
                    toggleClearBtn(fPayClear, fPayText);
                    if (window.PBMSearchableDropdown?.setOptions) {
                        window.PBMSearchableDropdown.setOptions('events', [{
                            budget_event_code: 0,
                            budget_event_name: '-เพิ่มงานใหม่-'
                        }]);
                    }
                };

                const loadEventsForPlan = (planCode) => {
                    const baseOpts = [{
                        budget_event_code: 0,
                        budget_event_name: '-เพิ่มงานใหม่-'
                    }];
                    if (!planCode) {
                        window.PBMSearchableDropdown?.setOptions?.('events', baseOpts);
                        return Promise.resolve();
                    }
                    return fetch(`${eventsByPlanUrl}/${encodeURIComponent(planCode)}`)
                        .then(r => r.json())
                        .then(j => {
                            const evs = (j.data || []).map(e => ({
                                budget_event_code: e.budget_event_code,
                                budget_event_name: e.budget_event_name,
                            }));
                            window.PBMSearchableDropdown?.setOptions?.('events', [...baseOpts, ...evs]);
                        })
                        .catch(() => {
                            window.PBMSearchableDropdown?.setOptions?.('events', baseOpts);
                        });
                };

                const getDisplayNameByCode = (items, valueKey, textKey, code) => {
                    const found = (items || []).find(x => String(x[valueKey]) === String(code ?? ''));
                    return found ? (found[textKey] || '') : '';
                };

                const syncCodeFromText = (textEl, hiddenEl, items, valueKey, textKey) => {
                    const keyword = (textEl.value || '').trim().toLowerCase();
                    if (!keyword) {
                        hiddenEl.value = '';
                        return;
                    }
                    const found = (items || []).find(item => {
                        const name = String(item[textKey] || '').trim().toLowerCase();
                        const code = String(item[valueKey] || '').trim().toLowerCase();
                        return name === keyword || code === keyword;
                    });
                    hiddenEl.value = found ? String(found[valueKey]) : '';
                };

                const escapeHtml = (unsafe) => {
                    return String(unsafe ?? '').replace(/[&<>"']/g, (c) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;',
                    } [c]));
                };

                const escapeAttr = (unsafe) => String(unsafe ?? '').replace(/"/g, '&quot;');

                /** รายการที่ถูกใช้งานแล้ว: API ส่ง can_delete = false */
                const rowCanDelete = (row) => row.can_delete !== false && row.can_delete !== 0;

                const updateWithdrawExpenseSelectedText = () => {
                    if (!fWithdrawExpensesSelected) return;
                    const selectedCount = getSelectedWithdrawExpenseCodes().length;
                    fWithdrawExpensesSelected.textContent = `เลือกแล้ว ${selectedCount} รายการ`;
                };

                const getPayFilteredWithdrawExpenseOptions = () => {
                    const selectedPayCode = Number(fPay?.value || 0);
                    if (selectedPayCode <= 0) return [];
                    return withdrawExpensesOptions
                        .filter(item => Number(item.payCode || 0) === selectedPayCode)
                        .sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'th'));
                };

                const getWithdrawExpenseCodesUsedByOtherExpenses = () => {
                    const currentExpenseId = Number(fId?.value || 0);
                    return new Set(allRows
                        .filter(row => Number(row.budget_set_expenses_id || 0) !== currentExpenseId)
                        .flatMap(row => Array.isArray(row.withdraw_expenses_codes) ? row.withdraw_expenses_codes : [])
                        .map(code => String(code)));
                };

                const renderAvailableWithdrawExpenses = () => {
                    if (module !== 'expenses' || !availableWithdrawExpenses || !availableWithdrawExpensesCount || !availableWithdrawExpensesList) return;

                    const usedCodes = new Set(allRows
                        .flatMap(row => Array.isArray(row.withdraw_expenses_codes) ? row.withdraw_expenses_codes : [])
                        .map(code => String(code)));
                    const available = withdrawExpensesOptions
                        .filter(item => !usedCodes.has(String(item.code || '')))
                        .sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'th'));
                    const categoryNamesByCode = new Map((options.categoryPays || []).map(item => [
                        String(item.budget_category_pay_code || ''),
                        String(item.budget_category_pay_name || '').trim(),
                    ]));
                    const payListsByCode = new Map((options.payLists || []).map(item => [
                        String(item.budget_pay_code || ''), {
                            categoryCode: String(item.budget_category_pay_code || ''),
                            name: String(item.budget_pay_name || '').trim(),
                        },
                    ]));
                    const grouped = available.reduce((groups, item) => {
                        const pay = payListsByCode.get(String(item.payCode || '')) || {};
                        const categoryCode = pay.categoryCode || '';
                        const categoryName = categoryNamesByCode.get(categoryCode) || 'ไม่ระบุหมวดรายจ่าย';
                        const payName = pay.name || 'ไม่ระบุรายการจ่าย';
                        const groupKey = `${categoryCode}__${categoryName}`;
                        const payKey = String(item.payCode || '');
                        if (!groups.has(groupKey)) groups.set(groupKey, { categoryName, pays: new Map() });
                        const category = groups.get(groupKey);
                        if (!category.pays.has(payKey)) category.pays.set(payKey, { payName, items: [] });
                        category.pays.get(payKey).items.push(item);
                        return groups;
                    }, new Map());

                    availableWithdrawExpenses.classList.remove('hidden');
                    availableWithdrawExpensesCount.textContent = `${available.length} รายการ`;
                    availableWithdrawExpensesList.innerHTML = available.length ? Array.from(grouped.values())
                        .sort((a, b) => a.categoryName.localeCompare(b.categoryName, 'th'))
                        .map(category => `<section class="rounded-xl border border-emerald-100 bg-white/70 p-2.5">
                            <div class="mb-1.5 text-xs font-bold text-emerald-800">${escapeHtml(category.categoryName)}</div>
                            ${Array.from(category.pays.values())
                                .sort((a, b) => a.payName.localeCompare(b.payName, 'th'))
                                .map(pay => `<div class="mb-2 last:mb-0">
                                    <div class="mb-1 text-[11px] font-semibold text-slate-600">${escapeHtml(pay.payName)}</div>
                                    <div class="flex flex-wrap gap-1.5">${pay.items.map(item =>
                                        `<span class="inline-flex max-w-full items-center gap-1 rounded-lg border border-emerald-100 bg-white py-1 pl-2 pr-1 text-xs font-medium text-slate-700 shadow-sm"><span class="font-semibold text-emerald-700">${escapeHtml(item.code)}</span><span class="truncate">${escapeHtml(item.name || '-')}</span><button type="button" class="js-bind-unused-account inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300" style="background-color:#059669 !important;color:#ffffff !important;" data-code="${escapeAttr(item.code)}" data-name="${escapeAttr(item.name || '')}" data-pay-code="${escapeAttr(item.payCode || '')}" data-ui-tooltip="ผูกกับรายจ่ายย่อย" aria-label="ผูกผังบัญชี ${escapeAttr(item.name || item.code)}"><i class="fas fa-plus text-[10px]" style="color:#ffffff !important;"></i></button></span>`
                                    ).join('')}</div>
                                </div>`).join('')}
                        </section>`).join('') : '<span class="text-xs text-slate-500">ไม่มีผังบัญชีที่พร้อมใช้งาน</span>';
                };

                let bindAccount = null;
                let bindSelectedExpenseId = 0;

                const expensesForBindPay = () => {
                    const payCode = Number(bindAccount?.payCode || 0);
                    return allRows
                        .filter(row => payCode > 0 && Number(row.budget_pay_code || 0) === payCode)
                        .filter(row => String(row.budget_set_expenses_name || '').trim() !== '')
                        .sort((a, b) => String(a.budget_set_expenses_name || '').localeCompare(String(b.budget_set_expenses_name || ''), 'th'));
                };

                const renderBindExistingList = () => {
                    const list = document.getElementById('bind-existing-list');
                    if (!list) return;
                    const keyword = (document.getElementById('bind-existing-search')?.value || '').trim().toLowerCase();
                    const rows = expensesForBindPay().filter(row => {
                        if (!keyword) return true;
                        return String(row.budget_set_expenses_name || '').toLowerCase().includes(keyword)
                            || String(row.budget_set_expenses_detail || '').toLowerCase().includes(keyword);
                    });
                    if (!rows.length) {
                        list.innerHTML = `<div class="px-3 py-4 text-center text-xs text-slate-400">${keyword ? 'ไม่พบรายจ่ายย่อยตามคำค้นหา' : 'ยังไม่มีรายจ่ายย่อยในรายการจ่ายนี้'}</div>`;
                        return;
                    }
                    list.innerHTML = rows.map(row => {
                        const id = Number(row.budget_set_expenses_id || 0);
                        const selected = id === bindSelectedExpenseId;
                        return `<button type="button" class="js-bind-existing flex w-full items-start gap-2 rounded-lg px-3 py-2 text-left ${selected ? 'bg-emerald-50 ring-1 ring-emerald-200' : 'hover:bg-slate-50'}" data-expense-id="${id}">
                            <span class="mt-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full border ${selected ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300'}">${selected ? '<i class="fas fa-check text-[8px]"></i>' : ''}</span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-800">${escapeHtml(row.budget_set_expenses_name || '-')}</span>
                                ${row.budget_set_expenses_detail ? `<span class="block truncate text-xs text-slate-500">${escapeHtml(row.budget_set_expenses_detail)}</span>` : ''}
                            </span>
                        </button>`;
                    }).join('');
                };

                const openBindAccountModal = (account) => {
                    bindAccount = account;
                    bindSelectedExpenseId = 0;
                    const summary = document.getElementById('bind-account-summary');
                    const search = document.getElementById('bind-existing-search');
                    const nameInput = document.getElementById('bind-new-name');
                    const detailInput = document.getElementById('bind-new-detail');
                    if (summary) {
                        summary.innerHTML = `<div class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">ผังบัญชีที่เลือก</div><div class="mt-1 font-semibold">${escapeHtml(account.code)} · ${escapeHtml(account.name || '-')}</div>`;
                    }
                    if (search) search.value = '';
                    if (nameInput) nameInput.value = '';
                    if (detailInput) detailInput.value = '';
                    renderBindExistingList();
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'bind-withdraw-expense-modal' }));
                };

                const closeBindAccountModal = () => {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'bind-withdraw-expense-modal' }));
                };

                const withdrawItemsPayload = (codes) => codes.map(code => ({
                    code,
                    name: String(withdrawExpenseNameByCode[code] || '').trim(),
                }));

                const saveBindAccount = async () => {
                    if (!bindAccount) return;
                    const accountCode = Number(bindAccount.code || 0);
                    const payCode = Number(bindAccount.payCode || 0);
                    const newName = (document.getElementById('bind-new-name')?.value || '').trim();
                    const newDetail = (document.getElementById('bind-new-detail')?.value || '').trim();
                    if (accountCode <= 0) return;

                    let payload = null;
                    if (newName !== '') {
                        if (payCode <= 0) {
                            window.toast?.error?.('ผังบัญชีนี้ยังไม่ผูกกับรายการจ่าย จึงเพิ่มรายจ่ายย่อยใหม่ไม่ได้', { timer: 2500 });
                            return;
                        }
                        payload = {
                            id: 0,
                            name: newName,
                            detail: newDetail,
                            pay_code: payCode,
                            withdraw_expenses_codes: [accountCode],
                            withdraw_expenses_items: [{ code: accountCode, name: bindAccount.name || '' }],
                        };
                    } else if (bindSelectedExpenseId > 0) {
                        const row = allRows.find(item => Number(item.budget_set_expenses_id || 0) === bindSelectedExpenseId);
                        if (!row) {
                            window.toast?.error?.('ไม่พบรายจ่ายย่อยที่เลือก', { timer: 2000 });
                            return;
                        }
                        const codes = [...new Set([
                            ...(Array.isArray(row.withdraw_expenses_codes) ? row.withdraw_expenses_codes : []).map(code => Number(code || 0)),
                            accountCode,
                        ].filter(code => code > 0))];
                        payload = {
                            id: bindSelectedExpenseId,
                            name: row.budget_set_expenses_name || '',
                            detail: row.budget_set_expenses_detail || '',
                            pay_code: Number(row.budget_pay_code || 0),
                            withdraw_expenses_codes: codes,
                            withdraw_expenses_items: withdrawItemsPayload(codes),
                        };
                    } else {
                        window.toast?.error?.('กรุณาเลือกรายจ่ายย่อย หรือกรอกชื่อรายจ่ายย่อยใหม่', { timer: 2200 });
                        return;
                    }

                    const result = await swalFetch(`${base}/expenses/save`, payload, 'กำลังผูกผังบัญชี...', 'ผูกผังบัญชีสำเร็จ');
                    if (result?.status === 'success') {
                        closeBindAccountModal();
                        render();
                    }
                };

                if (module === 'expenses' && availableWithdrawExpensesList) {
                    availableWithdrawExpensesList.addEventListener('click', (event) => {
                        const button = event.target.closest('.js-bind-unused-account');
                        if (!button) return;
                        event.preventDefault();
                        openBindAccountModal({
                            code: Number(button.dataset.code || 0),
                            name: button.dataset.name || '',
                            payCode: Number(button.dataset.payCode || 0),
                        });
                    });
                    document.getElementById('bind-existing-search')?.addEventListener('input', renderBindExistingList);
                    document.getElementById('bind-existing-list')?.addEventListener('click', (event) => {
                        const button = event.target.closest('.js-bind-existing');
                        if (!button) return;
                        bindSelectedExpenseId = Number(button.dataset.expenseId || 0);
                        const nameInput = document.getElementById('bind-new-name');
                        const detailInput = document.getElementById('bind-new-detail');
                        if (nameInput) nameInput.value = '';
                        if (detailInput) detailInput.value = '';
                        renderBindExistingList();
                    });
                    document.getElementById('bind-new-name')?.addEventListener('input', () => {
                        if ((document.getElementById('bind-new-name')?.value || '').trim() !== '') {
                            bindSelectedExpenseId = 0;
                            renderBindExistingList();
                        }
                    });
                    document.getElementById('bind-withdraw-cancel')?.addEventListener('click', closeBindAccountModal);
                    document.getElementById('bind-withdraw-save')?.addEventListener('click', saveBindAccount);
                }

                const renderWithdrawExpenseChecklist = () => {
                    if (!fWithdrawExpensesList || !fWithdrawExpenses) return;
                    const keyword = (fWithdrawExpensesSearch?.value || '').trim().toLowerCase();
                    const selectedSet = new Set(getSelectedWithdrawExpenseCodes().map(x => String(x)));
                    const usedByOtherExpenses = getWithdrawExpenseCodesUsedByOtherExpenses();
                    const allForPay = getPayFilteredWithdrawExpenseOptions();

                    if (!allForPay.length) {
                        fWithdrawExpensesList.innerHTML =
                            '<div class="px-2 py-3 text-xs text-slate-400">กรุณาเลือกรายการจ่ายหลักก่อน</div>';
                        updateWithdrawExpenseSelectedText();
                        return;
                    }

                    const visibleItems = keyword ?
                        allForPay.filter(item => String(item.name || '').toLowerCase().includes(keyword)) :
                        allForPay;

                    if (!visibleItems.length) {
                        fWithdrawExpensesList.innerHTML =
                            '<div class="px-2 py-3 text-xs text-slate-400">ไม่พบรายการตามคำค้นหา</div>';
                        updateWithdrawExpenseSelectedText();
                        return;
                    }

                    fWithdrawExpensesList.innerHTML = visibleItems.map(item => {
                        const code = String(item.code || '');
                        const checkedAttr = selectedSet.has(code) ? 'checked' : '';
                        const isUsed = usedByOtherExpenses.has(code);
                        return `<label class="flex items-center gap-2 rounded-md px-2 py-1.5 ${isUsed ? 'cursor-not-allowed bg-slate-50 opacity-60' : 'cursor-pointer hover:bg-slate-50'}">
                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" data-withdraw-expense-code="${escapeAttr(code)}" ${checkedAttr} ${isUsed ? 'disabled' : ''}>
                            <span class="text-sm text-slate-700">${escapeHtml(item.name || '-')}</span>
                            ${isUsed ? '<span class="ml-auto text-[11px] font-semibold text-slate-500">ใช้งานแล้ว</span>' : ''}
                        </label>`;
                    }).join('');
                    updateWithdrawExpenseSelectedText();
                };

                const setWithdrawExpenseOptions = (selectedCodes = []) => {
                    if (!fWithdrawExpenses) return;
                    const selected = new Set((selectedCodes || []).map(v => String(v)));
                    const usedByOtherExpenses = getWithdrawExpenseCodesUsedByOtherExpenses();
                    const sorted = getPayFilteredWithdrawExpenseOptions();

                    fWithdrawExpenses.innerHTML = sorted.map(item => {
                        const code = String(item.code || '');
                        const name = item.name || '-';
                        const selectedAttr = selected.has(code) ? 'selected' : '';
                        const disabledAttr = usedByOtherExpenses.has(code) ? 'disabled' : '';
                        return `<option value="${escapeAttr(code)}" ${selectedAttr} ${disabledAttr}>${escapeHtml(name)}</option>`;
                    }).join('');
                    renderWithdrawExpenseChecklist();
                };

                const getSelectedWithdrawExpenseCodes = () => {
                    if (!fWithdrawExpenses) return [];
                    return Array.from(fWithdrawExpenses.selectedOptions || [])
                        .map(opt => Number(opt.value || 0))
                        .filter(v => Number.isFinite(v) && v > 0);
                };

                const getSelectedWithdrawExpenseItems = () => {
                    if (!fWithdrawExpenses) return [];
                    return Array.from(fWithdrawExpenses.selectedOptions || [])
                        .map(opt => ({
                            code: Number(opt.value || 0),
                            name: String(opt.textContent || '').trim(),
                        }))
                        .filter(x => Number.isFinite(x.code) && x.code > 0 && x.name !== '');
                };

                const syncWithdrawExpenseOptionsByPay = () => {
                    const selectedCodes = getSelectedWithdrawExpenseCodes();
                    const payCode = Number(fPay?.value || 0);
                    const allowedCodes = payCode > 0 ?
                        selectedCodes.filter(code => withdrawExpensesOptions.some(item =>
                            Number(item.code || 0) === code && Number(item.payCode || 0) === payCode
                        )) :
                        [];
                    setWithdrawExpenseOptions(allowedCodes);
                };

                const getWithdrawExpenseNamesByCodes = (codes = []) => {
                    return (codes || [])
                        .map(code => Number(code || 0))
                        .filter(code => code > 0)
                        .map(code => String(withdrawExpenseNameByCode[code] || '').trim())
                        .filter(name => name !== '');
                };

                const bindSearchableField = (textEl, hiddenEl, clearBtn, suggestEl, getItems, valueKey, textKey) => {
                    const hideSuggest = () => {
                        suggestEl?.classList.add('hidden');
                        suggestEl && (suggestEl.innerHTML = '');
                    };

                    const showAll = () => {
                        const items = getItems() || [];
                        if (!items.length) {
                            suggestEl.classList.remove('hidden');
                            suggestEl.innerHTML =
                                `<div class="px-3 py-2 text-sm text-slate-400">ไม่พบรายการ</div>`;
                            return;
                        }

                        suggestEl.classList.remove('hidden');
                        suggestEl.innerHTML = items.map(item => {
                            const code = item[valueKey];
                            const label = item[textKey] ?? '';
                            return `<button type="button" data-code="${escapeAttr(code)}" class="w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">${escapeHtml(label)}</button>`;
                        }).join('');
                    };

                    const showSuggest = (keyword) => {
                        const items = getItems() || [];
                        const term = keyword.toLowerCase().trim();
                        if (!term) return hideSuggest();

                        const matches = items.filter(item => {
                            const name = String(item[textKey] ?? '').toLowerCase();
                            const code = String(item[valueKey] ?? '').toLowerCase();
                            return name.includes(term) || code.includes(term);
                        }).slice(0, 30);

                        if (!matches.length) {
                            suggestEl.classList.remove('hidden');
                            suggestEl.innerHTML =
                                `<div class="px-3 py-2 text-sm text-slate-400">ไม่พบรายการ</div>`;
                            return;
                        }

                        suggestEl.classList.remove('hidden');
                        suggestEl.innerHTML = matches.map(item => {
                            const code = item[valueKey];
                            const label = item[textKey] ?? '';
                            return `<button type="button" data-code="${escapeAttr(code)}" class="w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">${escapeHtml(label)}</button>`;
                        }).join('');
                    };

                    let suppressBlur = false;

                    const sync = () => {
                        syncCodeFromText(textEl, hiddenEl, getItems(), valueKey, textKey);
                        toggleClearBtn(clearBtn, textEl);
                    };

                    const onInput = () => {
                        sync();
                        const keyword = (textEl.value || '').trim();
                        if (!keyword) return hideSuggest();
                        showSuggest(keyword);
                    };

                    textEl.addEventListener('input', onInput);

                    textEl.addEventListener('focus', () => {
                        showAll();
                    });

                    textEl.addEventListener('click', () => {
                        showAll();
                    });

                    textEl.addEventListener('blur', () => {
                        setTimeout(() => {
                            if (!suppressBlur) hideSuggest();
                            suppressBlur = false;
                        }, 160);
                    });

                    suggestEl?.addEventListener('pointerdown', () => {
                        suppressBlur = true;
                    });

                    suggestEl?.addEventListener('click', (e) => {
                        const btn = e.target.closest('button[data-code]');
                        if (!btn) return;
                        e.preventDefault();
                        const selectedCode = btn.getAttribute('data-code') || '';
                        const selectedText = (btn.textContent || '').trim();

                        hiddenEl.value = selectedCode;
                        textEl.value = selectedText;
                        toggleClearBtn(clearBtn, textEl);
                        hideSuggest();
                    });

                    textEl.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            hideSuggest();
                            return;
                        }
                        if (e.key === 'Enter' && suggestEl && !suggestEl.classList.contains('hidden')) {
                            const first = suggestEl.querySelector('button[data-code]');
                            if (first) {
                                first.click();
                                e.preventDefault();
                            }
                        }
                    });

                    clearBtn.addEventListener('click', () => {
                        textEl.value = '';
                        hiddenEl.value = '';
                        toggleClearBtn(clearBtn, textEl);
                        hideSuggest();
                        textEl.focus();
                    });

                    sync();
                    hideSuggest();
                };

                const fillOptions = () => {
                    if (fPlan.value) fPlanText.value = getDisplayNameByCode(options.plans, 'budget_plan_code',
                        'budget_plan_name', fPlan.value);
                    if (fEvent.value) fEventText.value = getDisplayNameByCode(options.events, 'budget_event_code',
                        'budget_event_name', fEvent.value);
                    if (fCategoryPay.value) fCategoryPayText.value = getDisplayNameByCode(options.categoryPays,
                        'budget_category_pay_code', 'budget_category_pay_name', fCategoryPay.value);
                    if (fPay.value) fPayText.value = getDisplayNameByCode(options.payLists, 'budget_pay_code',
                        'budget_pay_name', fPay.value);

                    toggleClearBtn(fPlanClear, fPlanText);
                    toggleClearBtn(fEventClear, fEventText);
                    toggleClearBtn(fCategoryPayClear, fCategoryPayText);
                    toggleClearBtn(fPayClear, fPayText);
                };

                // งาน + กิจกรรมในตารางเดียว (เทียบ budget_event.php)
                const renderEventActivityMergedTable = (rows) => {
                    let html = '';
                    let no = 0;
                    rows.forEach((row) => {
                        if (row._row_type === 'plan_header') {
                            html +=
                                `<tr class="bg-green-50/80"><td colspan="5" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-green-700">${escapeHtml(row.budget_plan_name || '')}</td></tr>`;
                            return;
                        }
                        if (row._row_type === 'event') {
                            no++;
                            const st = Number(row.budget_event_status);
                            const canDel = rowCanDelete(row);
                            const delBtn = canDel ?
                                `<button type="button" data-delete-event-id="${row.budget_event_id}" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300"><i class="fas fa-trash-alt text-[11px]"></i><span>ลบ</span></button>` :
                                '';
                            const searchTxt = `${(row.budget_event_name || '').toLowerCase()} ${(row.budget_event_detail || '').toLowerCase()}`;
                            html += `<tr class="odd:bg-white even:bg-slate-50/40 hover:bg-green-50/60 transition-colors" data-searchable="${searchTxt}">
                                <td class="px-4 py-3 text-left text-slate-500">${no}</td>
                                <td class="px-4 py-3 font-medium text-slate-700 budgetEventName">${escapeHtml(row.budget_event_name || '-')}</td>
                                <td class="px-4 py-3 text-slate-600 budgetEventDetail">${escapeHtml(row.budget_event_detail || '-')}</td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input data-toggle-id="${row.budget_event_id}" data-toggle-module="event" type="checkbox" class="peer sr-only" ${st === 1 ? 'checked' : ''}>
                                        <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="hidden budgetPlanCode">${row.budget_plan_code || ''}</span>
                                    <div class="flex flex-nowrap justify-start gap-1 whitespace-nowrap">
                                        <button type="button" data-edit-event-id="${row.budget_event_id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"><i class="fas fa-edit text-[11px]"></i><span>แก้ไข</span></button>
                                        ${delBtn}
                                    </div>
                                </td>
                            </tr>`;
                            return;
                        }
                        if (row._row_type === 'activity') {
                            no++;
                            const st = Number(row.budget_activity_status);
                            const canDel = rowCanDelete(row);
                            const delBtn = canDel ?
                                `<button type="button" data-delete-activity-id="${row.budget_activity_id}" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300"><i class="fas fa-trash-alt text-[11px]"></i><span>ลบ</span></button>` :
                                '';
                            const label = `${row.budget_event_name || ''} - ${row.budget_activity_name || ''}`;
                            const searchTxt = `${(row.budget_event_name || '').toLowerCase()} ${(row.budget_activity_name || '').toLowerCase()} ${(row.budget_activity_detail || '').toLowerCase()}`;
                            html += `<tr class="odd:bg-white even:bg-slate-50/40 hover:bg-slate-50/60 transition-colors" data-searchable="${searchTxt}">
                                <td class="px-4 py-3 text-left text-slate-500">${no}<span class="hidden budgetPlanCode">${row.budget_plan_code || ''}</span><span class="hidden budgetEventCode">${row.budget_event_code || ''}</span><span class="hidden budgetActivityName">${escapeHtml(row.budget_activity_name || '')}</span></td>
                                <td class="px-4 py-3 font-medium text-slate-700 budgetActivityJoin">${escapeHtml(label)}</td>
                                <td class="px-4 py-3 text-slate-600 budgetActivityDetail">${escapeHtml(row.budget_activity_detail || '-')}</td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input data-toggle-id="${row.budget_activity_id}" data-toggle-module="activity" type="checkbox" class="peer sr-only" ${st === 1 ? 'checked' : ''}>
                                        <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-nowrap justify-start gap-1 whitespace-nowrap">
                                        <button type="button" data-edit-activity-id="${row.budget_activity_id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"><i class="fas fa-edit text-[11px]"></i><span>แก้ไข</span></button>
                                        ${delBtn}
                                    </div>
                                </td>
                            </tr>`;
                        }
                    });

                    return html ||
                        `<tr><td colspan="5" class="px-4 py-10 text-center text-slate-400"><div class="flex flex-col items-center gap-2"><i class="far fa-folder-open text-xl text-slate-300"></i><span>ไม่พบข้อมูล</span></div></td></tr>`;
                };

                // Build grouped table HTML for pay-list module
                const renderGroupedPayListTable = (rows) => {
                    // Group by category pay
                    const grouped = {};
                    rows.forEach(row => {
                        const catName = row.budget_category_pay_name || '-';
                        const catCode = row.budget_category_pay_code || 0;
                        const key = `${catCode}__${catName}`;
                        if (!grouped[key]) grouped[key] = {
                            catName,
                            rows: []
                        };
                        grouped[key].rows.push(row);
                    });

                    let html = '';
                    Object.values(grouped).forEach(group => {
                        html +=
                            `<tr class="bg-green-50/80"><td colspan="5" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-green-700">${group.catName}</td></tr>`;
                        group.rows.forEach((row, idx) => {
                            const st = Number(row.budget_pay_status);
                            const canDel = rowCanDelete(row);
                            const delBtn = canDel ?
                                `<button type="button" data-delete-id="${row.budget_pay_id}" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300"><i class="fas fa-trash-alt text-[11px]"></i><span>ลบ</span></button>` :
                                '';
                            html += `<tr class="odd:bg-white even:bg-slate-50/40 hover:bg-green-50/60 transition-colors" data-searchable="${(row.budget_pay_name || '').toLowerCase()} ${(row.budget_pay_detail || '').toLowerCase()}">
                                <td class="px-4 py-3 text-left text-slate-500">${idx + 1}</td>
                                <td class="px-4 py-3 font-medium text-slate-700">${row.budget_pay_name || '-'}</td>
                                <td class="px-4 py-3 text-slate-600">${row.budget_pay_detail || '-'}</td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input data-toggle-id="${row.budget_pay_id}" data-toggle-module="pay-list" type="checkbox" class="peer sr-only" ${st === 1 ? 'checked' : ''}>
                                        <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="hidden budgetCategoryPayCode">${row.budget_category_pay_code || ''}</span>
                                    <div class="flex flex-nowrap justify-start gap-1 whitespace-nowrap">
                                        <button type="button" data-edit-id="${row.budget_pay_id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"><i class="fas fa-edit text-[11px]"></i><span>แก้ไข</span></button>
                                        ${delBtn}
                                    </div>
                                </td>
                            </tr>`;
                        });
                    });

                    return html ||
                        `<tr><td colspan="5" class="px-4 py-10 text-center text-slate-400"><div class="flex flex-col items-center gap-2"><i class="far fa-folder-open text-xl text-slate-300"></i><span>ไม่พบข้อมูล</span></div></td></tr>`;
                };

                // Build grouped table HTML for expenses module
                const renderGroupedExpensesTable = (rows) => {
                    // Group by pay list
                    const grouped = {};
                    rows.forEach(row => {
                        const payName = row.budget_pay_name || '-';
                        const payCode = row.budget_pay_code || 0;
                        const key = `${payCode}__${payName}`;
                        if (!grouped[key]) grouped[key] = {
                            payName,
                            rows: []
                        };
                        grouped[key].rows.push(row);
                    });

                    let html = '';
                    Object.values(grouped).forEach(group => {
                        html +=
                            `<tr class="bg-green-50/80"><td colspan="5" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-green-700">${group.payName}</td></tr>`;
                        group.rows.forEach((row, idx) => {
                            if (!(row.budget_set_expenses_name || '').trim()) return;
                            const st = Number(row.budget_set_expenses_status);
                            const withdrawCodes = Array.isArray(row.withdraw_expenses_codes) ? row.withdraw_expenses_codes : [];
                            const withdrawNames = getWithdrawExpenseNamesByCodes(withdrawCodes);
                            const withdrawSubListHtml = withdrawNames.length ?
                                `<div class="mt-1.5 rounded-lg bg-slate-50 px-2 py-1.5">
                                    <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">ผังบัญชี (รายจ่าย)</div>
                                    <ul class="space-y-0.5 text-xs text-slate-600">
                                        ${withdrawNames.map(name => `<li>• ${escapeHtml(name)}</li>`).join('')}
                                    </ul>
                                </div>` :
                                '';
                            const detailText = row.budget_set_expenses_detail || '-';
                            const canDel = rowCanDelete(row);
                            const delBtn = canDel ?
                                `<button type="button" data-delete-id="${row.budget_set_expenses_id}" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300"><i class="fas fa-trash-alt text-[11px]"></i><span>ลบ</span></button>` :
                                '';
                            html += `<tr class="odd:bg-white even:bg-slate-50/40 hover:bg-green-50/60 transition-colors" data-searchable="${(row.budget_set_expenses_name || '').toLowerCase()} ${(detailText || '').toLowerCase()} ${(withdrawNames.join(' ') || '').toLowerCase()}">
                                <td class="px-4 py-3 text-left text-slate-500">${idx + 1}</td>
                                <td class="px-4 py-3 font-medium text-slate-700">${escapeHtml(row.budget_set_expenses_name || '-')}${withdrawSubListHtml}</td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(detailText || '-')}</td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input data-toggle-id="${row.budget_set_expenses_id}" data-toggle-module="expenses" type="checkbox" class="peer sr-only" ${st === 1 ? 'checked' : ''}>
                                        <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="hidden budgetPayCode">${row.budget_pay_code || ''}</span>
                                    <div class="flex flex-nowrap justify-start gap-1 whitespace-nowrap">
                                        <button type="button" data-edit-id="${row.budget_set_expenses_id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"><i class="fas fa-edit text-[11px]"></i><span>แก้ไข</span></button>
                                        ${delBtn}
                                    </div>
                                </td>
                            </tr>`;
                        });
                    });

                    return html ||
                        `<tr><td colspan="5" class="px-4 py-10 text-center text-slate-400"><div class="flex flex-col items-center gap-2"><i class="far fa-folder-open text-xl text-slate-300"></i><span>ไม่พบข้อมูล</span></div></td></tr>`;
                };

                // Build grouped table HTML for activity module
                const renderGroupedActivityTable = (rows) => {
                    // Group by event
                    const grouped = {};
                    rows.forEach(row => {
                        const eventName = row.budget_event_name || '-';
                        const eventCode = row.budget_event_code || 0;
                        const key = `${eventCode}__${eventName}`;
                        if (!grouped[key]) grouped[key] = {
                            eventName,
                            rows: []
                        };
                        grouped[key].rows.push(row);
                    });

                    let html = '';
                    Object.values(grouped).forEach(group => {
                        html +=
                            `<tr class="bg-green-50/80"><td colspan="5" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-green-700">${group.eventName}</td></tr>`;
                        group.rows.forEach((row, idx) => {
                            const st = Number(row.budget_activity_status);
                            const canDel = rowCanDelete(row);
                            const delBtn = canDel ?
                                `<button type="button" data-delete-id="${row.budget_activity_id}" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300"><i class="fas fa-trash-alt text-[11px]"></i><span>ลบ</span></button>` :
                                '';
                            html += `<tr class="odd:bg-white even:bg-slate-50/40 hover:bg-green-50/60 transition-colors" data-searchable="${(row.budget_activity_name || '').toLowerCase()} ${(row.budget_activity_detail || '').toLowerCase()}">
                                <td class="px-4 py-3 text-left text-slate-500">${idx + 1}</td>
                                <td class="px-4 py-3 font-medium text-slate-700">${row.budget_activity_name || '-'}</td>
                                <td class="px-4 py-3 text-slate-600">${row.budget_activity_detail || '-'}</td>
                                <td class="px-4 py-3 text-center">
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input data-toggle-id="${row.budget_activity_id}" data-toggle-module="activity" type="checkbox" class="peer sr-only" ${st === 1 ? 'checked' : ''}>
                                        <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-nowrap justify-start gap-1 whitespace-nowrap">
                                        <button type="button" data-edit-id="${row.budget_activity_id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"><i class="fas fa-edit text-[11px]"></i><span>แก้ไข</span></button>
                                        ${delBtn}
                                    </div>
                                </td>
                            </tr>`;
                        });
                    });

                    return html ||
                        `<tr><td colspan="5" class="px-4 py-10 text-center text-slate-400"><div class="flex flex-col items-center gap-2"><i class="far fa-folder-open text-xl text-slate-300"></i><span>ไม่พบข้อมูล</span></div></td></tr>`;
                };

                // Get flat data by module
                const textByModule = (row) => {
                    if (module === 'year') return [row.budget_year_name, row.budget_year_detail ?? '', row
                        .budget_year_status, row.budget_year_id
                    ];
                    if (module === 'plan') return [row.budget_plan_name, row.budget_plan_detail ?? '', row
                        .budget_plan_status, row.budget_plan_id
                    ];
                    if (module === 'fund') return [row.budget_fund_name, row.budget_fund_detail ?? '', row
                        .budget_fund_status, row.budget_fund_id
                    ];
                    if (module === 'cost') return [row.budget_cost_name, row.budget_cost_detail ?? '', row
                        .budget_cost_status, row.budget_cost_id
                    ];
                    if (module === 'source') return [row.budget_source_name, row.budget_source_detail ?? '', row
                        .budget_source_status, row.budget_source_id
                    ];
                    if (module === 'type') return [row.budget_type_name, row.budget_type_detail ?? '', row
                        .budget_type_direction ?? '+', row.budget_type_status, row.budget_type_id
                    ];
                    if (module === 'category-budget') return [row.budget_category_budget_name, row
                        .budget_category_budget_detail ?? '', row.budget_category_budget_status, row
                        .budget_category_budget_id
                    ];
                    if (module === 'category-pay') return [row.budget_category_pay_name, row
                        .budget_category_pay_detail ?? '', row.budget_category_pay_status, row
                        .budget_category_pay_id
                    ];
                    if (module === 'pay-list') return [row.budget_pay_name, row.budget_pay_detail ?? '', row
                        .budget_pay_status, row.budget_pay_id
                    ];
                    if (module === 'expenses') return [row.budget_set_expenses_name, row
                        .budget_set_expenses_detail ?? '', row.budget_set_expenses_status, row
                        .budget_set_expenses_id
                    ];
                    if (module === 'activity') return [row.budget_activity_name,
                        `${row.budget_event_name ?? '-'} | ${row.budget_activity_detail ?? ''}`, row
                        .budget_activity_status, row.budget_activity_id
                    ];
                    return ['-', '-', 0, 0];
                };

                // Render flat (simple) table for most modules
                const renderFlatTable = (rows) => {
                    return rows.map((row, idx) => {
                            const values = textByModule(row);
                            const [name, detail] = values;
                            const [direction, status, id] = module === 'type' ? values.slice(2) : [null, ...values.slice(2)];
                            const st = Number(status);
                            const canDel = rowCanDelete(row);
                            const delBtn = canDel ?
                                `<button type="button" data-delete-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300"><i class="fas fa-trash-alt text-[11px]"></i><span>ลบ</span></button>` :
                                '';
                            return `<tr class="odd:bg-white even:bg-slate-50/40 hover:bg-green-50/60 transition-colors" data-searchable="${(name || '').toLowerCase()} ${(detail || '').toLowerCase()}">
                            <td class="px-4 py-3 text-left text-slate-500">${idx + 1}</td>
                            <td class="px-4 py-3 font-medium text-slate-700">${name || '-'}</td>
                            <td class="px-4 py-3 text-slate-600">${detail || '-'}</td>
                            ${module === 'type' ? `<td class="px-4 py-3 text-center font-semibold ${direction === '-' ? 'text-red-600' : 'text-emerald-600'}">${direction === '-' ? 'ลดงบ (-)' : 'เพิ่มงบ (+)'}</td>` : ''}
                            <td class="px-4 py-3 text-center">
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input data-toggle-id="${id}" type="checkbox" class="peer sr-only" ${st === 1 ? 'checked' : ''}>
                                    <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                </label>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-nowrap justify-start gap-1 whitespace-nowrap">
                                    <button type="button" data-edit-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"><i class="fas fa-edit text-[11px]"></i><span>แก้ไข</span></button>
                                    ${delBtn}
                                </div>
                            </td>
                        </tr>`;
                        }).join('') ||
                        `<tr><td colspan="${module === 'type' ? 6 : 5}" class="px-4 py-10 text-center text-slate-400"><div class="flex flex-col items-center gap-2"><i class="far fa-folder-open text-xl text-slate-300"></i><span>ไม่พบข้อมูล</span></div></td></tr>`;
                };

                // Apply search filter
                const applySearch = () => {
                    const term = searchInput.value.toLowerCase().trim();
                    filteredRows = term ? allRows.filter(row => {
                        const txt = JSON.stringify(row).toLowerCase();
                        return txt.includes(term);
                    }) : [...allRows];
                    currentPage = 1;
                    renderTable();
                };

                // Paginate
                const getPaginatedRows = () => {
                    if (viewAll) return filteredRows;
                    const start = (currentPage - 1) * pageSize;
                    return filteredRows.slice(start, start + pageSize);
                };

                const updatePagination = () => {
                    const total = filteredRows.length;
                    const totalPages = Math.ceil(total / pageSize);
                    if (viewAll) {
                        paginationInfo.textContent = `แสดง ${total} รายการ`;
                        paginationPage.textContent = 'แสดงทั้งหมด';
                        btnPrev.disabled = true;
                        btnNext.disabled = true;
                        btnViewAll.textContent = 'ดูน้อยลง';
                    } else {
                        const start = (currentPage - 1) * pageSize + 1;
                        const end = Math.min(currentPage * pageSize, total);
                        const safeTotalPages = Math.max(totalPages, 1);
                        paginationInfo.textContent = total > 0 ? `แสดง ${start} ถึง ${end} จาก ${total} รายการ` :
                        '';
                        paginationPage.textContent =
                            `หน้า ${Math.min(currentPage, safeTotalPages)}/${safeTotalPages}`;
                        btnPrev.disabled = currentPage <= 1;
                        btnNext.disabled = currentPage >= totalPages;
                        btnViewAll.textContent = 'ดูทั้งหมด';
                    }
                };

                // Main render
                const renderTable = () => {
                    const rows = getPaginatedRows();
                    let html = '';

                    if (module === 'event') {
                        html = renderEventActivityMergedTable(rows);
                    } else if (module === 'activity') {
                        html = renderGroupedActivityTable(rows);
                    } else if (module === 'pay-list') {
                        html = renderGroupedPayListTable(rows);
                    } else if (module === 'expenses') {
                        html = renderGroupedExpensesTable(rows);
                    } else {
                        html = renderFlatTable(rows);
                    }

                    table.innerHTML = html;
                    renderAvailableWithdrawExpenses();
                    updatePagination();
                    bindTableEvents();
                };

                // Bind events on table cells
                const bindTableEvents = () => {
                    // Toggle status
                    table.querySelectorAll('[data-toggle-id]').forEach(el => {
                        el.addEventListener('change', async (e) => {
                            const toggleModule = el.dataset.toggleModule || module;
                            const result = await swalFetch(`${base}/${toggleModule}/toggle`, {
                                id: Number(el.dataset.toggleId),
                                status: e.target.checked ? 1 : 0,
                            }, 'กำลังอัปเดต...', 'อัปเดตสำเร็จ');
                            if (result.status === 'success') render();
                        });
                    });

                    // Delete (generic)
                    table.querySelectorAll('[data-delete-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            deleteUrl = `${base}/${module}/delete`;
                            deleteId = Number(el.dataset.deleteId);
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'settings-delete-confirm-modal' }));
                        });
                    });

                    // Delete event (for event module)
                    table.querySelectorAll('[data-delete-event-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            deleteUrl = `${base}/event/delete`;
                            deleteId = Number(el.dataset.deleteEventId);
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'settings-delete-confirm-modal' }));
                        });
                    });

                    // Edit (generic)
                    table.querySelectorAll('[data-edit-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            const id = Number(el.dataset.editId);
                            const row = allRows.find(r => {
                                const values = textByModule(r);
                                const rid = values[values.length - 1];
                                return Number(rid) === id;
                            });
                            if (!row) return;
                            resetForm();
                            fId.value = id;

                            if (module === 'year') {
                                fName.value = row.budget_year_name || '';
                                fDetail.value = row.budget_year_detail || '';
                            }
                            if (module === 'plan') {
                                fName.value = row.budget_plan_name || '';
                                fDetail.value = row.budget_plan_detail || '';
                            }
                            if (module === 'activity') {
                                fName.value = row.budget_activity_name || '';
                                fDetail.value = row.budget_activity_detail || '';
                                fEvent.value = row.budget_event_code || '';
                                fEventText.value = row.budget_event_name || '';
                                toggleClearBtn(fEventClear, fEventText);
                            }
                            if (module === 'fund') {
                                fName.value = row.budget_fund_name || '';
                                fDetail.value = row.budget_fund_detail || '';
                            }
                            if (module === 'cost') {
                                fName.value = row.budget_cost_name || '';
                                fDetail.value = row.budget_cost_detail || '';
                            }
                            if (module === 'source') {
                                fName.value = row.budget_source_name || '';
                                fDetail.value = row.budget_source_detail || '';
                            }
                            if (module === 'type') {
                                fName.value = row.budget_type_name || '';
                                fDetail.value = row.budget_type_detail || '';
                                fDirection.value = row.budget_type_direction === '-' ? '-' : '+';
                            }
                            if (module === 'category-budget') {
                                fName.value = row.budget_category_budget_name || '';
                                fDetail.value = row.budget_category_budget_detail || '';
                            }
                            if (module === 'category-pay') {
                                fName.value = row.budget_category_pay_name || '';
                                fDetail.value = row.budget_category_pay_detail || '';
                            }
                            if (module === 'pay-list') {
                                fName.value = row.budget_pay_name || '';
                                fDetail.value = row.budget_pay_detail || '';
                                fCategoryPay.value = row.budget_category_pay_code || '';
                                fCategoryPayText.value = row.budget_category_pay_name || '';
                                toggleClearBtn(fCategoryPayClear, fCategoryPayText);
                            }
                            if (module === 'expenses') {
                                fName.value = row.budget_set_expenses_name || '';
                                fDetail.value = row.budget_set_expenses_detail || '';
                                fPay.value = row.budget_pay_code || '';
                                fPayText.value = row.budget_pay_name || '';
                                setWithdrawExpenseOptions(row.withdraw_expenses_codes || []);
                                toggleClearBtn(fPayClear, fPayText);
                            }

                            openModal(`แก้ไข${modLabel}`);
                        });
                    });

                    // Edit event (for event module)
                    table.querySelectorAll('[data-edit-event-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            const id = Number(el.dataset.editEventId);
                            const row = allRows.find(r => r._row_type === 'event' && Number(r.budget_event_id) === id);
                            if (!row) return;
                            resetForm();
                            editMode = 'event';
                            fId.value = id;
                            fPlan.value = row.budget_plan_code || '';
                            fPlanText.value = row.budget_plan_name || '';
                            fName.value = row.budget_event_name || '';
                            fDetail.value = row.budget_event_detail || '';
                            toggleClearBtn(fPlanClear, fPlanText);

                            showIf(wPlan, true);
                            showIf(wEvent, false);
                            showIf(wEventName, false);
                            showIf(wActivityName, false);
                            showIf(wNameWrap, true);
                            fNameLabel.textContent = 'ชื่องาน';

                            openModal(`แก้ไข${modLabel}`);
                        });
                    });

                    // Edit activity (บนหน้า event เดียวกัน)
                    table.querySelectorAll('[data-edit-activity-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            const id = Number(el.dataset.editActivityId);
                            const row = allRows.find(r => r._row_type === 'activity' && Number(r.budget_activity_id) === id);
                            if (!row) return;
                            resetForm();
                            editMode = 'activity';
                            fId.value = id;
                            fPlan.value = String(row.budget_plan_code || '');
                            fPlanText.value = row.budget_plan_name || '';
                            fDetail.value = row.budget_activity_detail || '';
                            fName.value = row.budget_activity_name || '';
                            toggleClearBtn(fPlanClear, fPlanText);
                            fNameLabel.textContent = 'ชื่อกิจกรรม';

                            showIf(wPlan, true);
                            showIf(wEvent, true);
                            showIf(wEventName, false);
                            showIf(wActivityName, false);
                            showIf(wNameWrap, true);

                            loadEventsForPlan(String(row.budget_plan_code || '')).then(() => {
                                fEvent.value = String(row.budget_event_code ?? '');
                                const opts = window.PBMSearchableDropdown?.optionsByKey?.events || [];
                                const found = opts.find(o => String(o.budget_event_code) === String(row.budget_event_code));
                                fEventText.value = found ? (found.budget_event_name || '') : (row.budget_event_name || '');
                                toggleClearBtn(fEventClear, fEventText);
                            });

                            openModal(`แก้ไข${modLabel}`);
                        });
                    });

                    // Delete activity (หน้า event)
                    table.querySelectorAll('[data-delete-activity-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            deleteUrl = `${base}/activity/delete`;
                            deleteId = Number(el.dataset.deleteActivityId);
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'settings-delete-confirm-modal' }));
                        });
                    });
                };

                // Load data from API
                const render = async () => {
                    const r = await fetch(`${base}/${module}`);
                    const json = await r.json();
                    allRows = json.data || [];
                    filteredRows = [...allRows];
                    renderTable();
                };

                // Add button
                btnAdd.addEventListener('click', () => {
                    resetForm();

                    // Setup modal fields by module
                    if (module === 'event') {
                        showIf(wPlan, true);
                        showIf(wEvent, true);
                        showIf(wEventName, false);
                        showIf(wActivityName, true);
                        showIf(wNameWrap, false);
                        fNameLabel.textContent = 'ชื่องาน';
                        editMode = '';
                        fEvent.value = '';
                        fEventText.value = '';
                        toggleClearBtn(fEventClear, fEventText);
                        loadEventsForPlan('');
                    } else {
                        showIf(wPlan, false);
                        showIf(wEvent, module === 'activity');
                        showIf(wEventName, false);
                        showIf(wActivityName, false);
                        showIf(wCategoryPay, module === 'pay-list');
                        showIf(wPay, module === 'expenses');
                        showIf(wWithdrawExpenses, module === 'expenses');
                        showIf(wDirection, module === 'type');
                        setWithdrawExpenseOptions([]);
                        fNameLabel.textContent = modLabel;
                    }

                    openModal(`เพิ่ม${modLabel}`);
                });

                // Form submit
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    let saveModule = module;
                    const payload = {
                        id: Number(fId.value || 0),
                        name: fName.value.trim(),
                        detail: fDetail.value.trim(),
                        plan_code: Number(fPlan.value || 0),
                        event_code: Number(fEvent.value || 0),
                        category_pay_code: Number(fCategoryPay.value || 0),
                        pay_code: Number(fPay.value || 0),
                        withdraw_expenses_codes: getSelectedWithdrawExpenseCodes(),
                        withdraw_expenses_items: getSelectedWithdrawExpenseItems(),
                        direction: fDirection.value,
                    };

                    if (module === 'event' && editMode === 'activity') {
                        saveModule = 'activity';
                    } else if (module === 'event' && editMode === 'event') {
                        saveModule = 'event';
                    } else if (module === 'event' && editMode === '') {
                        saveModule = 'event';
                        payload.existing_event_code = Number(fEvent.value || 0);
                        payload.event_name_new = fEventName.value.trim();
                        payload.activity_name = fActivityName.value.trim();
                        payload.name = '';
                    }

                    const result = await swalFetch(`${base}/${saveModule}/save`, payload, 'กำลังบันทึก...',
                        'บันทึกสำเร็จ');
                    if (result.status === 'success') {
                        closeModal();
                        render();
                    }
                });

                // Search
                searchInput.addEventListener('input', () => applySearch());

                // Pagination
                btnPrev.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        renderTable();
                    }
                });
                btnNext.addEventListener('click', () => {
                    const totalPages = Math.ceil(filteredRows.length / pageSize);
                    if (currentPage < totalPages) {
                        currentPage++;
                        renderTable();
                    }
                });
                btnViewAll.addEventListener('click', () => {
                    viewAll = !viewAll;
                    currentPage = 1;
                    renderTable();
                });

                // Init
                (async () => {
                    const op = await fetch(`${base}/options`).then(r => r.json()).catch(() => ({
                        data: {}
                    }));
                    options = op.data || options;
                    const withdrawExpenses = await fetch(`${withdrawSettingsApiBase}/expenses`)
                        .then(r => r.json())
                        .then(j => j.data || [])
                        .catch(() => []);
                    withdrawExpensesOptions = (withdrawExpenses || [])
                        .filter(row => Number(row.withdraw_set_expenses_status ?? 0) === 1)
                        .map(row => ({
                            code: Number(row.withdraw_set_expenses_code || 0),
                            name: row.withdraw_set_expenses_name || '',
                            payCode: Number(row.withdraw_pay_code || 0),
                        }))
                        .filter(row => row.code > 0 && row.name.trim() !== '');
                    withdrawExpenseNameByCode = withdrawExpensesOptions.reduce((acc, item) => {
                        acc[item.code] = item.name;
                        return acc;
                    }, {});
                    setWithdrawExpenseOptions([]);
                    if (window.PBMSearchableDropdown?.setOptions) {
                        window.PBMSearchableDropdown.setOptions('plans', options.plans || []);
                        window.PBMSearchableDropdown.setOptions('events', options.events || []);
                        window.PBMSearchableDropdown.setOptions('categoryPays', options.categoryPays || []);
                        window.PBMSearchableDropdown.setOptions('payLists', options.payLists || []);
                    }
                    fillOptions();

                    // Configure visible fields based on module
                    showIf(wPlan, module === 'event');
                    showIf(wEvent, module === 'activity');
                    showIf(wEventName, false);
                    showIf(wActivityName, false);
                    showIf(wCategoryPay, module === 'pay-list');
                    showIf(wPay, module === 'expenses');
                    showIf(wWithdrawExpenses, module === 'expenses');
                    showIf(wDirection, module === 'type');
                    showIf(wNameWrap, module !== 'event');

                    // Set label
                    fNameLabel.textContent = modLabel;

                    if (module === 'event') {
                        fPlan.addEventListener('change', () => {
                            const pc = fPlan.value;
                            loadEventsForPlan(pc).then(() => {
                                if (editMode === '') {
                                    fEvent.value = '';
                                    fEventText.value = '';
                                    toggleClearBtn(fEventClear, fEventText);
                                }
                                showIf(wEventName, false);
                            });
                        });
                        fEvent.addEventListener('change', () => {
                            if (editMode !== '') return;
                            const v = Number(fEvent.value || 0);
                            showIf(wEventName, v === 0);
                        });
                    }

                    fPay.addEventListener('change', () => {
                        syncWithdrawExpenseOptionsByPay();
                    });

                    fWithdrawExpensesSearch?.addEventListener('input', () => {
                        renderWithdrawExpenseChecklist();
                    });

                    fWithdrawExpensesList?.addEventListener('change', (e) => {
                        const target = e.target;
                        if (!(target instanceof HTMLInputElement) || target.type !== 'checkbox') return;
                        const code = String(target.dataset.withdrawExpenseCode || '');
                        const option = Array.from(fWithdrawExpenses?.options || []).find(opt => String(opt.value) === code);
                        if (!option) return;
                        option.selected = target.checked;
                        updateWithdrawExpenseSelectedText();
                    });

                    fWithdrawExpensesSelectAll?.addEventListener('click', () => {
                        const keyword = (fWithdrawExpensesSearch?.value || '').trim().toLowerCase();
                        const visibleCodes = getPayFilteredWithdrawExpenseOptions()
                            .filter(item => !keyword || String(item.name || '').toLowerCase().includes(keyword))
                            .filter(item => !getWithdrawExpenseCodesUsedByOtherExpenses().has(String(item.code || '')))
                            .map(item => String(item.code || ''));
                        Array.from(fWithdrawExpenses?.options || []).forEach(opt => {
                            if (visibleCodes.includes(String(opt.value))) opt.selected = true;
                        });
                        renderWithdrawExpenseChecklist();
                    });

                    fWithdrawExpensesClear?.addEventListener('click', () => {
                        Array.from(fWithdrawExpenses?.options || []).forEach(opt => {
                            opt.selected = false;
                        });
                        renderWithdrawExpenseChecklist();
                    });

                    render();
                })();
            });
        </script>
    @endpush
</x-layouts.app>
