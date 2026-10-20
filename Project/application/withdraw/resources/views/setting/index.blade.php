<x-layouts.app>
    <div class="py-2">
        <div class="mx-2 flex flex-col gap-4">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50/80 to-emerald-50/50 p-4 shadow-md shadow-slate-200/50 sm:px-4 sm:py-4">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-400/15 blur-2xl sm:h-36 sm:w-36"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-emerald-200/60 to-transparent"></div>
                <div class="relative flex min-w-0 gap-4">
                    <div class="flex h-12 w-12 mt-2 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-lg text-white shadow-lg shadow-emerald-500/30 ring-4 ring-white/80">
                        <i class="fas fa-sitemap" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.65rem]">{{ $title ?? 'ผังบัญชี (รายจ่าย)' }}</h4>
                        <p class="mb-0 max-w-2xl text-sm leading-snug text-slate-500">
                            {{ ($module ?? 'expenses') === 'documents'
                                ? 'ตั้งค่าประเภทไฟล์และรายการเอกสารประกอบการเบิกจ่าย'
                                : (($module ?? 'expenses') === 'book-numbers'
                                    ? 'ตั้งค่าประเภทการเบิกจ่ายและเลขที่หนังสือ'
                                    : (($module ?? 'expenses') === 'approvers'
                                        ? 'กำหนดผู้อนุมัติตามลำดับ โดยเลือก'
                                        : (($module ?? 'expenses') === 'finance-reviewers'
                                            ? 'กำหนดผู้ตรวจสอบงานการเงิน โดยเลือกได้หลายคน'
                                            : 'จัดการผังบัญชีรายจ่าย: เพิ่ม แก้ไข เปิด/ปิดสถานะ และลบรายการ'))) }}
                        </p>
                    </div>
                </div>
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/70 shadow-md shadow-slate-200/60">
                <div class="p-4 sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
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

                    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                        <table class="w-full table-auto text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr class="border-b border-slate-200 bg-slate-50/95 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-700 backdrop-blur">
                                    <th class="select-none whitespace-nowrap px-4 py-3" style="width: 40px;">#</th>
                                    <th class="select-none whitespace-nowrap px-4 py-3">
                                        {{ ($module ?? 'expenses') === 'book-numbers'
                                            ? 'ประเภทการเบิกจ่าย'
                                            : (($module ?? 'expenses') === 'approvers'
                                                ? 'ขั้นตอนอนุมัติ'
                                                : (($module ?? 'expenses') === 'finance-reviewers' ? 'ผู้ตรวจสอบงานการเงิน' : 'รายการ')) }}
                                    </th>
                                    <th class="select-none whitespace-nowrap px-4 py-3">
                                        {{ ($module ?? 'expenses') === 'book-numbers'
                                            ? 'เลขที่หนังสือ'
                                            : (($module ?? 'expenses') === 'approvers'
                                                ? 'ผู้อนุมัติ / ลำดับ'
                                                : (($module ?? 'expenses') === 'finance-reviewers' ? 'รายละเอียด' : 'รายละเอียด')) }}
                                    </th>
                                    <th class="select-none whitespace-nowrap px-4 py-3">สถานะ</th>
                                    @if (($module ?? 'expenses') === 'approvers')
                                        <th class="select-none whitespace-nowrap px-4 py-3">แจ้งเตือน</th>
                                    @endif
                                    <th class="select-none whitespace-nowrap px-4 py-3">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="settings-table" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <x-modal name="settings-form-modal" maxWidth="lg" :static="true" variant="modern" title="เพิ่มข้อมูล"
        description="เพิ่ม/แก้ไขข้อมูลผังบัญชีรายจ่าย" titleId="modal-title" descriptionId="modal-description" iconId="modal-icon" iconClass="fas fa-sitemap">
        <form id="settings-form" class="grid grid-cols-1 gap-4">
            <input type="hidden" id="f-id" />
            @if (($module ?? 'expenses') !== 'book-numbers')
                <div>
                    <label id="f-source-label" class="mb-1 block text-xs font-bold text-slate-700">
                        {{ ($module ?? 'expenses') === 'documents'
                            ? 'ประเภทไฟล์'
                            : (($module ?? 'expenses') === 'approvers'
                                ? 'ผู้อนุมัติ'
                                : (($module ?? 'expenses') === 'finance-reviewers'
                                    ? 'ผู้ตรวจสอบ (การเงิน) '
                                    : 'รายการจ่ายจากระบบงบประมาณ')) }}
                    </label>
                    <x-searchable-dropdown
                        id="f-pay-code"
                        placeholder="{{ ($module ?? 'expenses') === 'documents'
                            ? 'พิมพ์เพื่อค้นหาประเภทไฟล์...'
                            : (($module ?? 'expenses') === 'approvers'
                                ? 'พิมพ์เพื่อค้นหาผู้อนุมัติ...'
                                : (($module ?? 'expenses') === 'finance-reviewers'
                                    ? 'พิมพ์เพื่อค้นหาผู้ตรวจสอบ (การเงิน) ...'
                                    : 'พิมพ์เพื่อค้นหารายการจ่าย...')) }}"
                        :options="[]"
                        valueKey="code"
                        textKey="label"
                        optionsKey="{{ ($module ?? 'expenses') === 'documents'
                            ? 'withdraw-setting-document-types'
                            : (($module ?? 'expenses') === 'approvers'
                                ? 'withdraw-setting-approvers'
                                : (($module ?? 'expenses') === 'finance-reviewers'
                                    ? 'withdraw-setting-finance-reviewers'
                                    : 'withdraw-setting-pay-lists')) }}"
                        :showClear="true"
                        clearTitle="{{ ($module ?? 'expenses') === 'documents'
                            ? 'ล้างประเภทไฟล์'
                            : (($module ?? 'expenses') === 'approvers'
                                ? 'ล้างผู้อนุมัติ'
                                : (($module ?? 'expenses') === 'finance-reviewers' ? 'ล้างผู้ตรวจสอบ' : 'ล้างรายการจ่าย')) }}"
                    />
                </div>
            @endif
            @if (($module ?? 'expenses') === 'approvers')
                <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-3">
                    <input type="checkbox" id="f-approver-notify-enabled"
                        class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        checked />
                    <label for="f-approver-notify-enabled" class="cursor-pointer text-sm leading-snug text-slate-700">
                        <span class="font-bold text-slate-800">เปิดการแจ้งเตือน</span>
                        <span class="mt-0.5 block text-xs text-slate-500">เมื่อปิด ผู้อนุมัติรายนี้จะไม่ได้รับการแจ้งเตือนผ่านระบบ notification และอีเมล เมื่อมีรายการส่งถึงลำดับของท่าน</span>
                    </label>
                </div>
            @endif
            @if (($module ?? 'expenses') === 'documents')
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">หรือเพิ่มประเภทไฟล์ใหม่</label>
                    <input id="f-new-type-name"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        placeholder="เช่น เอกสารการอนุมัติ" />
                </div>
            @endif
            @if (($module ?? 'expenses') !== 'finance-reviewers')
                <div>
                    <label id="f-name-label" class="mb-1 block text-xs font-bold text-slate-700">
                        {{ ($module ?? 'expenses') === 'documents'
                            ? 'รายการเอกสาร'
                            : (($module ?? 'expenses') === 'book-numbers'
                                ? 'ประเภทการเบิกจ่าย'
                                : 'ชื่อผังบัญชี') }}
                    </label>
                    <input id="f-name"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">
                        {{ ($module ?? 'expenses') === 'book-numbers'
                            ? 'เลขที่หนังสือ'
                            : (($module ?? 'expenses') === 'approvers'
                                ? 'ลำดับอนุมัติ'
                                : 'รายละเอียด (ถ้ามี)') }}
                    </label>
                    <input id="f-detail"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                </div>
            @else
                <input type="hidden" id="f-name" value="" />
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">รายละเอียด (ถ้ามี)</label>
                    <input id="f-detail"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                </div>
            @endif
            <div class="mt-3 flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" @click="$dispatch('close-modal', 'settings-form-modal')"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-100 hover:text-slate-800 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                    <i class="fas fa-times mr-1"></i>ยกเลิก
                </button>
                <button type="submit"
                    class="rounded-xl !bg-[#16a34a] !text-white px-4 py-2 text-sm font-bold shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:!bg-[#15803d] hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-green-300">
                    <i class="far fa-save mr-1"></i>บันทึก
                </button>
            </div>
        </form>
    </x-modal>

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
                const base = @json($settingsApiBase ?? url('/withdraw/setting/api'));
                const module = @json($module ?? 'expenses');
                const tableColCount = module === 'approvers' ? 6 : 5;

                const table = document.getElementById('settings-table');
                const btnAdd = document.getElementById('btn-add');
                const form = document.getElementById('settings-form');
                const searchInput = document.getElementById('search-input');
                const fId = document.getElementById('f-id');
                const fPayCode = document.getElementById('f-pay-code');
                const fName = document.getElementById('f-name');
                const fDetail = document.getElementById('f-detail');
                const fNewTypeName = document.getElementById('f-new-type-name');
                const fSourceLabel = document.getElementById('f-source-label');
                const fNameLabel = document.getElementById('f-name-label');
                const fApproverNotify = document.getElementById('f-approver-notify-enabled');

                let allRows = [];
                let filteredRows = [];
                let deleteId = 0;
                let payLists = [];
                let payListByCode = new Map();
                let documentTypes = [];
                let documentTypeByCode = new Map();
                let staffOptions = [];
                let staffOptionByCode = new Map();
                let loadOptionsPromise = null;

                const moduleConfig = module === 'documents'
                    ? {
                        idKey: 'withdraw_set_document_item_id',
                        sourceCodeKey: 'withdraw_set_document_type_code',
                        sourceNameKey: 'withdraw_set_document_type_name',
                        nameKey: 'withdraw_set_document_item_name',
                        detailKey: 'withdraw_set_document_item_detail',
                        statusKey: 'withdraw_set_document_item_status',
                        sourceLabel: 'ประเภทไฟล์',
                        itemLabel: 'รายการเอกสาร',
                        addTitle: 'เพิ่มรายการเอกสารประกอบการเบิกจ่าย',
                        editTitle: 'แก้ไขรายการเอกสารประกอบการเบิกจ่าย',
                        modalDescription: 'เพิ่ม/แก้ไขรายการเอกสารประกอบการเบิกจ่าย',
                        modalIconClass: 'fas fa-file-lines',
                        saveExtraKey: 'type_code',
                        dropdownOptionsKey: 'withdraw-setting-document-types',
                        requiresSourceSelection: true,
                        groupBySource: true,
                    }
                    : module === 'book-numbers'
                        ? {
                            idKey: 'withdraw_set_book_number_id',
                            sourceCodeKey: '',
                            sourceNameKey: 'withdraw_set_book_number_type',
                            nameKey: 'withdraw_set_book_number_type',
                            detailKey: 'withdraw_set_book_number_value',
                            statusKey: 'withdraw_set_book_number_status',
                            sourceLabel: 'ประเภทการเบิกจ่าย',
                            itemLabel: 'ประเภทการเบิกจ่าย',
                            addTitle: 'เพิ่มเลขที่หนังสือ',
                            editTitle: 'แก้ไขเลขที่หนังสือ',
                            modalDescription: 'เพิ่ม/แก้ไขเลขที่หนังสือสำหรับใช้งานในระบบ',
                            modalIconClass: 'fas fa-hashtag',
                            saveExtraKey: '',
                            dropdownOptionsKey: '',
                            requiresSourceSelection: false,
                            groupBySource: false,
                        }
                    : module === 'approvers'
                        ? {
                            idKey: 'withdraw_set_approver_id',
                            sourceCodeKey: 'withdraw_set_approver_staff_id',
                            sourceNameKey: 'withdraw_set_approver_staff_label',
                            nameKey: 'withdraw_set_approver_step_name',
                            detailKey: 'withdraw_set_approver_staff_detail',
                            statusKey: 'withdraw_set_approver_status',
                            sourceLabel: 'ผู้อนุมัติ',
                            itemLabel: 'ขั้นตอนอนุมัติ',
                            addTitle: 'เพิ่มผู้อนุมัติ',
                            editTitle: 'แก้ไขผู้อนุมัติ',
                            modalDescription: 'กำหนดผู้อนุมัติตามลำดับ',
                            modalIconClass: 'fas fa-user-check',
                            saveExtraKey: 'staff_id',
                            dropdownOptionsKey: 'withdraw-setting-approvers',
                            requiresSourceSelection: true,
                            groupBySource: false,
                            sourceValueType: 'string',
                            detailLabel: 'ลำดับอนุมัติ',
                        }
                    : module === 'finance-reviewers'
                        ? {
                            idKey: 'withdraw_set_finance_reviewer_id',
                            sourceCodeKey: 'withdraw_set_finance_reviewer_staff_id',
                            sourceNameKey: 'withdraw_set_finance_reviewer_name',
                            nameKey: 'withdraw_set_finance_reviewer_name',
                            detailKey: 'withdraw_set_finance_reviewer_detail',
                            statusKey: 'withdraw_set_finance_reviewer_status',
                            sourceLabel: 'ผู้ตรวจสอบ (การเงิน) ',
                            itemLabel: 'ผู้ตรวจสอบ (การเงิน)',
                            addTitle: 'เพิ่มผู้ตรวจสอบ (การเงิน)',
                            editTitle: 'แก้ไขผู้ตรวจสอบ (การเงิน)',
                            modalDescription: 'กำหนดผู้ตรวจสอบ (การเงิน) ',
                            modalIconClass: 'fas fa-money-check-dollar',
                            saveExtraKey: 'staff_id',
                            dropdownOptionsKey: 'withdraw-setting-finance-reviewers',
                            requiresSourceSelection: true,
                            groupBySource: false,
                            sourceValueType: 'string',
                            detailLabel: 'รายละเอียด (ถ้ามี)',
                        }
                    : {
                        idKey: 'withdraw_set_expenses_id',
                        sourceCodeKey: 'withdraw_pay_code',
                        sourceNameKey: 'budget_pay_name',
                        nameKey: 'withdraw_set_expenses_name',
                        detailKey: 'withdraw_set_expenses_detail',
                        statusKey: 'withdraw_set_expenses_status',
                        sourceLabel: 'รายการจ่ายจากระบบงบประมาณ',
                        itemLabel: 'ชื่อผังบัญชี',
                        addTitle: 'เพิ่มผังบัญชี (รายจ่าย)',
                        editTitle: 'แก้ไขผังบัญชี (รายจ่าย)',
                        modalDescription: 'เพิ่ม/แก้ไขข้อมูลผังบัญชีรายจ่าย',
                        modalIconClass: 'fas fa-sitemap',
                        saveExtraKey: 'pay_code',
                        dropdownOptionsKey: 'withdraw-setting-pay-lists',
                        requiresSourceSelection: true,
                        groupBySource: true,
                            sourceValueType: 'number',
                            detailLabel: 'รายละเอียด (ถ้ามี)',
                    };
                if (!moduleConfig.sourceValueType) moduleConfig.sourceValueType = 'number';
                if (!moduleConfig.detailLabel) {
                    moduleConfig.detailLabel = module === 'book-numbers' ? 'เลขที่หนังสือ' : 'รายละเอียด (ถ้ามี)';
                }

                if (fSourceLabel) fSourceLabel.textContent = moduleConfig.sourceLabel;
                if (fNameLabel) fNameLabel.textContent = moduleConfig.itemLabel;
                const detailLabelEl = document.querySelector('label[for="f-detail"]') || fDetail?.parentElement?.querySelector('label');
                if (detailLabelEl) detailLabelEl.textContent = moduleConfig.detailLabel;

                const syncPayCodeInput = () => {
                    if (!fPayCode) return;
                    fPayCode.dispatchEvent(new Event('input', { bubbles: true }));
                    fPayCode.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

                const swalFetch = async (url, data, loadingText = 'กำลังทำงาน...', successText = 'สำเร็จ') => {
                    if (window.toast?.info) window.toast.info(loadingText, { timer: 1000 });
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                            },
                            body: JSON.stringify(data),
                        });
                        const result = await res.json();
                        if (result.status === 'success') window.toast?.success?.(successText, { timer: 1000 });
                        else window.toast?.error?.(result.message || 'เกิดข้อผิดพลาด', { timer: 1200 });
                        return result;
                    } catch (err) {
                        window.toast?.error?.('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', { timer: 1200 });
                        throw err;
                    }
                };

                const resetForm = () => {
                    fId.value = '';
                    if (fPayCode) fPayCode.value = '';
                    syncPayCodeInput();
                    if (fName) fName.value = '';
                    if (fDetail) fDetail.value = '';
                    if (fNewTypeName) fNewTypeName.value = '';
                    if (fApproverNotify) fApproverNotify.checked = true;
                };

                const openModal = (title, description = '', iconClass = '') => {
                    const titleEl = document.getElementById('modal-title');
                    if (titleEl) titleEl.textContent = title;
                    const descriptionEl = document.getElementById('modal-description');
                    if (descriptionEl) descriptionEl.textContent = description || '';
                    const iconEl = document.getElementById('modal-icon');
                    if (iconEl && iconClass) {
                        iconEl.className = `${iconClass} text-[14px]`;
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'settings-form-modal' }));
                };

                const closeModal = () => {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'settings-form-modal' }));
                };

                const rowCanDelete = (row) => row.can_delete !== false && row.can_delete !== 0;

                const normalizePayListRows = (rows) => {
                    return (rows || []).map((item) => {
                        const code = Number(item.code ?? item.budget_pay_code ?? 0);
                        const name = `${item.name ?? item.budget_pay_name ?? ''}`.trim();
                        const category = `${item.category ?? item.budget_category_pay_name ?? ''}`.trim();
                        return {
                            code,
                            name,
                            category,
                            label: name,
                        };
                    }).filter((item) => item.code > 0 && item.name !== '');
                };

                const normalizeDocumentTypeRows = (rows) => {
                    return (rows || []).map((item) => {
                        const code = Number(item.code ?? item.withdraw_set_document_type_code ?? 0);
                        const name = `${item.name ?? item.withdraw_set_document_type_name ?? ''}`.trim();
                        return {
                            code,
                            name,
                            label: name,
                        };
                    }).filter((item) => item.code > 0 && item.name !== '');
                };

                const normalizeStaffRows = (rows) => {
                    return (rows || []).map((item) => {
                        const code = `${item.code ?? item.id ?? ''}`.trim();
                        const label = `${item.label ?? item.name ?? ''}`.trim();
                        return {
                            code,
                            label: label || code,
                        };
                    }).filter((item) => item.code !== '');
                };

                const fetchJson = async (url) => {
                    try {
                        const r = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const text = await r.text();
                        return JSON.parse(text);
                    } catch (e) {
                        return null;
                    }
                };

                const fetchJsonWithRetry = async (url, retries = 2) => {
                    let last = null;
                    for (let i = 0; i <= retries; i++) {
                        last = await fetchJson(url);
                        if (last?.status === 'success') {
                            return last;
                        }
                    }
                    return last;
                };

                const setDropdownStatus = (message) => {
                    if (!moduleConfig.dropdownOptionsKey) {
                        return;
                    }
                    if (window.PBMSearchableDropdown?.setStatus) {
                        window.PBMSearchableDropdown.setStatus(moduleConfig.dropdownOptionsKey, message || '');
                    }
                };

                const loadOptions = async (force = false) => {
                    if (!moduleConfig.requiresSourceSelection) {
                        return Promise.resolve();
                    }
                    if (!force && loadOptionsPromise) {
                        return loadOptionsPromise;
                    }

                    loadOptionsPromise = (async () => {
                        setDropdownStatus('กำลังโหลดรายการ...');
                        let rows = [];

                        const withdrawOptionsJson = await fetchJsonWithRetry(`${base}/options`);
                        if (withdrawOptionsJson?.status === 'success') {
                            if (module === 'approvers' || module === 'finance-reviewers') {
                                rows = normalizeStaffRows(withdrawOptionsJson?.data?.staff_lists || []);
                            } else {
                                rows = normalizePayListRows(withdrawOptionsJson?.data?.pay_lists || []);
                            }
                        }

                        if (module === 'approvers') {
                            staffOptions = rows;
                            staffOptionByCode = new Map(staffOptions.map((item) => [String(item.code), item]));
                        } else {
                            payLists = rows;
                            payListByCode = new Map(payLists.map((item) => [Number(item.code), item]));
                        }
                        if (module === 'documents') {
                            documentTypes = normalizeDocumentTypeRows(withdrawOptionsJson?.data?.document_types || []);
                            documentTypeByCode = new Map(documentTypes.map((item) => [Number(item.code), item]));
                        }

                        if (window.PBMSearchableDropdown?.setOptions) {
                            const options = module === 'documents'
                                ? documentTypes
                                : (module === 'approvers' ? staffOptions : payLists);
                            window.PBMSearchableDropdown.setOptions(moduleConfig.dropdownOptionsKey, options);
                        }
                        const optionLength = module === 'documents'
                            ? documentTypes.length
                            : ((module === 'approvers' || module === 'finance-reviewers') ? staffOptions.length : payLists.length);
                        if (optionLength === 0) {
                            setDropdownStatus('โหลดไม่สำเร็จ กรุณาลองใหม่');
                        } else {
                            setDropdownStatus('');
                        }
                        syncPayCodeInput();
                        applySearch();
                    })();

                    try {
                        return await loadOptionsPromise;
                    } finally {
                        loadOptionsPromise = null;
                    }
                };

                const renderTable = () => {
                    const buildRowHtml = (row, idx) => {
                        const st = Number(row[moduleConfig.statusKey] || 0);
                        const statusTooltip = st === 1 ? 'ใช้งาน' : 'ไม่ใช้งาน';
                        const canDel = rowCanDelete(row);
                        const delBtn = canDel
                            ? `<button type="button" data-delete-id="${row[moduleConfig.idKey]}" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300"><i class="fas fa-trash-alt text-[11px]"></i><span>ลบ</span></button>`
                            : '';
                        const nSt = Number(row.withdraw_set_approver_notification_enabled ?? 1);
                        const notifyTooltip = nSt === 1 ? 'เปิดแจ้งเตือน (notification และอีเมล)' : 'ปิดแจ้งเตือน';
                        const notifyCell = module === 'approvers'
                            ? `<td class="px-4 py-3 text-center">
                                <label class="relative inline-flex cursor-pointer items-center" data-ui-tooltip="${notifyTooltip}" data-ui-tooltip-placement="top" aria-label="${notifyTooltip}">
                                    <input data-notify-toggle-id="${row[moduleConfig.idKey]}" type="checkbox" class="peer sr-only" ${nSt === 1 ? 'checked' : ''}>
                                    <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-sky-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                </label>
                            </td>`
                            : '';
                        return `<tr class="odd:bg-white even:bg-slate-50/40 hover:bg-green-50/60 transition-colors">
                            <td class="px-4 py-3 text-left text-slate-500">${idx + 1}</td>
                            <td class="px-4 py-3 font-medium text-slate-700">${row[moduleConfig.nameKey] || '-'}</td>
                            <td class="px-4 py-3 text-slate-600">${row[moduleConfig.detailKey] || '-'}</td>
                            <td class="px-4 py-3 text-center">
                                <label class="relative inline-flex cursor-pointer items-center" data-ui-tooltip="${statusTooltip}" data-ui-tooltip-placement="top" aria-label="${statusTooltip}">
                                    <input data-toggle-id="${row[moduleConfig.idKey]}" type="checkbox" class="peer sr-only" ${st === 1 ? 'checked' : ''}>
                                    <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                </label>
                            </td>
                            ${notifyCell}
                            <td class="px-4 py-3">
                                <div class="flex flex-nowrap justify-start gap-1 whitespace-nowrap">
                                    <button type="button" data-edit-id="${row[moduleConfig.idKey]}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"><i class="fas fa-edit text-[11px]"></i><span>แก้ไข</span></button>
                                    ${delBtn}
                                </div>
                            </td>
                        </tr>`;
                    };

                    let html = '';
                    if (moduleConfig.groupBySource) {
                        const grouped = {};
                        filteredRows.forEach((row) => {
                            const sourceCode = Number(row[moduleConfig.sourceCodeKey] || 0);
                            const mappedSource = module === 'documents'
                                ? documentTypeByCode.get(sourceCode)
                                : payListByCode.get(sourceCode);
                            const sourceName = row[moduleConfig.sourceNameKey] || mappedSource?.label || mappedSource?.name || '-';
                            const key = `${sourceCode}__${sourceName}`;
                            if (!grouped[key]) {
                                grouped[key] = {
                                    sourceName,
                                    rows: [],
                                };
                            }
                            grouped[key].rows.push(row);
                        });

                        Object.values(grouped).forEach((group) => {
                            html += `<tr class="bg-green-50/80"><td colspan="${tableColCount}" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-green-700">${group.sourceName}</td></tr>`;
                            group.rows.forEach((row, idx) => {
                                html += buildRowHtml(row, idx);
                            });
                        });
                    } else {
                        filteredRows.forEach((row, idx) => {
                            html += buildRowHtml(row, idx);
                        });
                    }

                    table.innerHTML = html || `<tr><td colspan="${tableColCount}" class="px-4 py-10 text-center text-slate-400">ไม่พบข้อมูล</td></tr>`;

                    table.querySelectorAll('[data-toggle-id]').forEach((el) => {
                        el.addEventListener('change', async (e) => {
                            const result = await swalFetch(`${base}/${module}/toggle`, {
                                id: Number(el.dataset.toggleId),
                                status: e.target.checked ? 1 : 0,
                            }, 'กำลังอัปเดต...', 'อัปเดตสำเร็จ');
                            if (result.status === 'success') render();
                        });
                    });

                    table.querySelectorAll('[data-notify-toggle-id]').forEach((el) => {
                        el.addEventListener('change', async (e) => {
                            const prev = !e.target.checked;
                            const result = await swalFetch(`${base}/approvers/toggle-notification`, {
                                id: Number(el.dataset.notifyToggleId),
                                enabled: e.target.checked ? 1 : 0,
                            }, 'กำลังอัปเดต...', 'อัปเดตสำเร็จ');
                            if (result.status === 'success') {
                                render();
                            } else {
                                e.target.checked = prev;
                            }
                        });
                    });

                    table.querySelectorAll('[data-delete-id]').forEach((el) => {
                        el.addEventListener('click', () => {
                            deleteId = Number(el.dataset.deleteId);
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'settings-delete-confirm-modal' }));
                        });
                    });

                    table.querySelectorAll('[data-edit-id]').forEach((el) => {
                        el.addEventListener('click', () => {
                            const id = Number(el.dataset.editId);
                            const row = allRows.find((x) => Number(x[moduleConfig.idKey]) === id);
                            if (!row) return;
                            fId.value = row[moduleConfig.idKey];
                            if (fPayCode) fPayCode.value = row[moduleConfig.sourceCodeKey] || '';
                            syncPayCodeInput();
                            if (fName) fName.value = row[moduleConfig.nameKey] || '';
                            if (module === 'approvers') {
                                if (fDetail) fDetail.value = String(row.withdraw_set_approver_step_order || '');
                                if (fApproverNotify) {
                                    fApproverNotify.checked = Number(row.withdraw_set_approver_notification_enabled ?? 1) === 1;
                                }
                            } else {
                                if (fDetail) fDetail.value = row[moduleConfig.detailKey] || '';
                            }
                            if (fNewTypeName) fNewTypeName.value = '';
                            openModal(moduleConfig.editTitle, moduleConfig.modalDescription, moduleConfig.modalIconClass);
                        });
                    });
                };

                const applySearch = () => {
                    const term = (searchInput.value || '').trim().toLowerCase();
                    filteredRows = !term ? [...allRows] : allRows.filter((row) =>
                        `${row[moduleConfig.sourceNameKey] || ''} ${row[moduleConfig.nameKey] || ''} ${row[moduleConfig.detailKey] || ''}`.toLowerCase().includes(term)
                    );
                    renderTable();
                };

                const render = async () => {
                    const r = await fetch(`${base}/${module}`);
                    const json = await r.json();
                    allRows = json.data || [];
                    filteredRows = [...allRows];
                    renderTable();
                };

                window.__settingsConfirmDelete = async () => {
                    const id = deleteId;
                    deleteId = 0;
                    try {
                        const result = await swalFetch(`${base}/${module}/delete`, { id }, 'กำลังลบ...', 'ลบสำเร็จ');
                        if (result?.status === 'success') render();
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'settings-delete-confirm-modal' }));
                    }
                };

                btnAdd.addEventListener('click', async () => {
                    await loadOptions(true);
                    resetForm();
                    openModal(moduleConfig.addTitle, moduleConfig.modalDescription, moduleConfig.modalIconClass);
                });

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const payload = {
                        id: Number(fId.value || 0),
                        name: fName ? fName.value.trim() : '',
                        detail: fDetail ? fDetail.value.trim() : '',
                    };
                    if (moduleConfig.requiresSourceSelection && moduleConfig.saveExtraKey && fPayCode) {
                        payload[moduleConfig.saveExtraKey] = moduleConfig.sourceValueType === 'number'
                            ? Number(fPayCode.value || 0)
                            : String(fPayCode.value || '').trim();
                    }
                    if (module === 'book-numbers') {
                        payload.type = fName.value.trim();
                        payload.book_number = fDetail.value.trim();
                    }
                    if (module === 'approvers') {
                        payload.step_order = Number(fDetail.value || 0);
                        if (fApproverNotify) {
                            payload.notify_enabled = fApproverNotify.checked ? 1 : 0;
                        }
                    }
                    if (module === 'documents' && fNewTypeName) {
                        payload.new_type_name = fNewTypeName.value.trim();
                    }
                    const result = await swalFetch(`${base}/${module}/save`, payload, 'กำลังบันทึก...', 'บันทึกสำเร็จ');
                    if (result.status === 'success') {
                        closeModal();
                        render();
                    }
                });

                searchInput.addEventListener('input', applySearch);
                fPayCode?.addEventListener('focus', () => {
                    const optionLength = module === 'documents'
                        ? documentTypes.length
                        : (module === 'approvers' ? staffOptions.length : payLists.length);
                    if (optionLength === 0) {
                        loadOptions(true);
                    }
                });

                Promise.all([loadOptions(true), render()]);
            });
        </script>
    @endpush
</x-layouts.app>
