<x-layouts.app>
    <div class="py-2">
        <div class="mx-2 flex flex-col gap-4">
            @php
                $isDocumentReviewMode = (bool) ($isDocumentReviewMode ?? false);
                $pageHeading = trim((string) ($title ?? '')) !== '' ? (string) $title : 'อนุมัติเบิกจ่าย';
                $pageDescription = $isDocumentReviewMode
                    ? 'หน้านี้แสดงรายการเบิกจ่ายทุกประเภทสำหรับการตรวจสอบเอกสารตามสิทธิ์ของผู้ใช้งาน'
                    : 'หน้านี้แสดงรายการเบิกจ่ายทุกประเภทที่รอการอนุมัติตามลำดับของท่าน';
            @endphp
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50/80 to-amber-50/50 p-4 shadow-md shadow-slate-200/50 sm:px-4 sm:py-4">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-amber-400/15 blur-2xl sm:h-36 sm:w-36"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-amber-200/60 to-transparent"></div>
                <div class="relative flex min-w-0 items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-lg text-white shadow-lg shadow-amber-500/30 ring-4 ring-white/80">
                        <i class="fas fa-user-check" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.65rem]">{{ $pageHeading }}</h4>
                        <p class="mb-0 max-w-2xl text-sm leading-snug text-slate-500">
                            {{ $pageDescription }}
                        </p>
                        @unless ($isDocumentReviewMode)
                            <div id="approval-pending-banner"
                                class="mt-3 inline-flex max-w-full items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 shadow-sm">
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                                    <i class="fas fa-bell text-xs" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0 leading-snug">
                                    รายการรออนุมัติของคุณ
                                    <span id="approval-pending-count-badge" data-count="0"
                                        class="mx-1 inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">0</span>
                                    รายการ — กดปุ่มอนุมัติหรือเปิดจากลิงก์ในอีเมลได้ทันที
                                </span>
                            </div>
                        @endunless
                    </div>
                </div>
            </div>

            @php
                $sampleRows = collect($withdrawRows ?? []);
                $statusClasses = [
                    'ร่าง' => 'bg-slate-100 text-slate-700 ring-slate-200',
                    'รอตรวจสอบ/อนุมัติ' => 'bg-amber-100 text-amber-700 ring-amber-200',
                    'รอการเงินตรวจสอบเอกสาร' => 'bg-cyan-100 text-cyan-700 ring-cyan-200',
                    'รออนุมัติ' => 'bg-violet-100 text-violet-700 ring-violet-200',
                    'อนุมัติ' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                    '-' => 'bg-slate-100 text-slate-500 ring-slate-200',
                ];
            @endphp

            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/70 shadow-md shadow-slate-200/60">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-white via-slate-50/80 to-amber-50/40 px-4 py-4 sm:px-5">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                            <i class="fas fa-table-list text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h5 id="approval-section-title" class="text-base font-semibold text-slate-900 sm:text-lg">รายการรออนุมัติ</h5>
                            <p class="mt-0.5 text-sm text-slate-500">
                                ทั้งหมด
                                <span id="approval-count" class="font-semibold text-slate-700">{{ count($sampleRows) }}</span>
                                รายการ
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-3 sm:p-5">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <button type="button" id="approval-tab-pending" onclick="window.__withdrawApprovalQueuedTab='pending'; window.__withdrawApprovalSwitchTab && window.__withdrawApprovalSwitchTab('pending')"
                            class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 ring-1 ring-amber-100 transition hover:bg-amber-100">
                            <i class="fas fa-hourglass-half text-[10px]"></i>
                            รออนุมัติ
                        </button>
                        <button type="button" id="approval-tab-approved" onclick="window.__withdrawApprovalQueuedTab='approved'; window.__withdrawApprovalSwitchTab && window.__withdrawApprovalSwitchTab('approved')"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">
                            <i class="fas fa-circle-check text-[10px]"></i>
                            อนุมัติแล้ว
                        </button>
                    </div>

                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:max-w-3xl lg:flex-1">
                            <select id="approval-filter-fiscal-year" class="rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition-all focus:-translate-y-0.5 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                                <option value="">- เลือกปีงบประมาณ -</option>
                            </select>
                            <select id="approval-filter-status" class="rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition-all focus:-translate-y-0.5 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                                <option value="">- เลือกสถานะ -</option>
                            </select>
                            <select id="approval-filter-type" class="rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition-all focus:-translate-y-0.5 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                                <option value="">- เลือกประเภท -</option>
                            </select>
                        </div>

                        <div class="relative lg:w-64">
                            <input id="approval-filter-search" type="text" placeholder="ค้นหา"
                                class="w-full rounded-xl border border-slate-300/90 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 transition-all focus:-translate-y-0.5 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                            <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-500">
                                <i class="fas fa-search" aria-hidden="true"></i>
                            </span>
                        </div>
                    </div>

                    <div id="approval-general-table-body" class="grid grid-cols-1 gap-3">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">กำลังโหลดรายการ...</div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @include('withdraw.general.partials.detail-modal')
    @include('withdraw.salary.partials.detail-modal')
    <x-modal name="withdraw-approval-confirm-modal" maxWidth="md" :static="true" variant="modern"
        title="ยืนยันอนุมัติรายการ?" description="โปรดตรวจสอบข้อมูล และระบุความคิดเห็น (ถ้ามี) ก่อนยืนยันอนุมัติรายการ"
        iconClass="fas fa-circle-check">
        <div class="p-6">
            {{-- <div id="approval-review-detail-content" class="rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700"></div> --}}
            <div id="approval-confirm-summary-card" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">เลขที่:</span> <span id="approval-confirm-detail-code">-</span></div>
                <div><span class="font-semibold text-slate-800">วันที่:</span> <span id="approval-confirm-detail-date">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="approval-confirm-detail-subject">-</span></div>
                <div><span class="font-semibold text-slate-800">ผู้ขอเบิก:</span> <span id="approval-confirm-detail-applicant">-</span></div>
                <div><span class="font-semibold text-slate-800">จำนวนเงิน:</span> <span id="approval-confirm-detail-amount">-</span> บาท</div>
            </div>
            <div class="mt-4">
                <label for="withdraw-approval-comment" class="mb-1 block text-sm font-semibold text-slate-700">ความคิดเห็น (ถ้ามี)</label>
                <textarea id="withdraw-approval-comment" rows="4" placeholder="ระบุความคิดเห็นประกอบการอนุมัติ (หากไม่มีความคิดเห็นไม่ต้องระบุ)"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-approval-confirm-modal')"
                    class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    ยกเลิก
                </button>
                <button type="button" id="withdraw-approval-confirm-button" @click="window.__withdrawApprovalConfirmProceed?.()"
                    class="inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-emerald-600 to-green-500">
                    ยืนยันอนุมัติ
                </button>
            </div>
        </div>
    </x-modal>
    <x-modal name="withdraw-executive-approval-form-modal" maxWidth="6xl" :static="true" variant="modern"
        title="ยืนยันอนุมัติรายการ" description="ข้อมูลในแบบฟอร์มนี้เพื่อใช้ประกอบการอนุมัติของผู้บริหาร"
        iconClass="fas fa-file-circle-check">
        <div class="p-6">
            <div id="executive-approval-detail-loading"
                class="hidden rounded-xl border border-slate-200 bg-white px-4 py-16 text-center text-sm text-slate-500">
                <i class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></i>กำลังโหลดรายละเอียด...
            </div>
            <div id="executive-approval-loaded-content">
            <div id="executive-revision-explanation-card" class="mb-3 hidden rounded-xl border border-sky-200 bg-sky-50/80 p-3">
                <div class="text-xs font-bold text-sky-800">คำชี้แจงการแก้ไข</div>
                <div id="executive-revision-explanation" class="mt-1 whitespace-pre-wrap text-sm text-slate-800">-</div>
            </div>
            <div id="executive-approval-summary-card"
                class="mb-3 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700">
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-semibold text-slate-500">เรื่องที่ขออนุมัติ</div>
                        <div id="executive-summary-subject" class="mt-0.5 text-base font-bold leading-snug text-slate-900">-</div>
                        <div class="mt-1 flex min-w-0 items-baseline gap-1.5 text-xs leading-snug">
                            <span class="shrink-0 font-semibold text-slate-500">ส่วนงาน</span>
                            <span id="executive-summary-department" class="min-w-0 break-words font-semibold text-slate-800">-</span>
                        </div>
                    </div>
                    <div class="shrink-0 rounded-lg bg-emerald-50 px-3 py-1.5 text-right">
                        <div class="text-[11px] font-semibold text-emerald-700">จำนวนเงิน</div>
                        <div class="mt-0.5 flex items-baseline justify-end gap-1">
                            <span id="executive-summary-amount" class="text-lg font-bold tabular-nums leading-none text-emerald-800">-</span>
                            <span class="text-[11px] font-medium text-emerald-700">บาท</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs">
                    <div>
                        <span class="font-semibold text-slate-500">เลขที่</span>
                        <span id="executive-summary-code" class="ml-1.5 break-words font-semibold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-500">วันที่</span>
                        <span id="executive-summary-date" class="ml-1.5 font-semibold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-500">ผู้ขอเบิก</span>
                        <span id="executive-summary-applicant" class="ml-1.5 break-words font-semibold text-slate-800">-</span>
                    </div>
                </div>
            </div>
            <div id="executive-department-memo-toggle-wrap" class="mb-2 hidden w-full">
                <button type="button" id="executive-department-memo-toggle"
                    class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2.5 text-left"
                    style="background-color:#ffffff;color:#334155;">
                    <span class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600">
                            <i class="fas fa-file-lines text-xs" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-bold text-slate-800">บันทึกข้อความหน่วยงาน</span>
                            <span id="executive-department-memo-toggle-hint" class="block text-xs text-slate-500">กดเพื่อซ่อนทั้งการ์ด</span>
                        </span>
                    </span>
                    <span id="executive-department-memo-toggle-action" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-slate-600">
                        <i class="fas fa-chevron-up text-[10px]"></i>
                        ซ่อน
                    </span>
                </button>
            </div>
            <div id="executive-department-memo-panel" class="rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700">
                <div id="executive-approval-detail-content"></div>
                <div id="executive-procurement-card" class="mt-4 hidden">
                    <div class="mb-2 flex items-start gap-2">
                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700 ring-1 ring-sky-200">
                            <i class="fas fa-folder-open text-xs" aria-hidden="true"></i>
                        </span>
                        <div>
                            <div class="text-sm font-bold text-slate-800">เอกสารจัดซื้อจัดจ้าง</div>
                            <div class="text-xs text-slate-500">รายการเอกสารจัดซื้อจัดจ้างของการเบิกจ่ายนี้</div>
                        </div>
                    </div>
                    <div id="executive-procurement-empty" class="hidden rounded-xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                        ยังไม่มีเอกสารจัดซื้อจัดจ้างของรายการนี้
                    </div>
                    <div id="executive-procurement-wrap" class="space-y-3">
                        <div id="executive-procurement-body" class="space-y-3"></div>
                    </div>
                </div>
            </div>

            <div id="executive-finance-memo-card" class="mt-3 hidden rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700">
                <div class="mb-2 flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600">
                        <i class="fas fa-file-lines text-xs" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-slate-800">บันทึกข้อความงานการเงิน</div>
                        <div class="text-xs text-slate-500">บันทึกเสนอที่งานการเงินจัดทำเพื่อเสนอผู้บริหาร</div>
                    </div>
                </div>
                <div id="executive-finance-memo-body"></div>
            </div>

            <div id="executive-finance-docs-card" class="mt-3 hidden rounded-xl border border-violet-200 bg-violet-50/40 p-3">
                <div class="mb-2 flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700 ring-1 ring-violet-200">
                        <i class="fas fa-file-signature text-xs" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-slate-800">เอกสารลงนาม e-Sign</div>
                        <div id="executive-finance-docs-note" class="mt-0.5 hidden text-xs font-semibold text-slate-600"></div>
                    </div>
                </div>
                <div id="executive-finance-docs-empty" class="hidden rounded-xl border border-dashed border-violet-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                    ยังไม่มีเอกสารลงนาม e-Sign ของรายการนี้
                </div>
                <div id="executive-finance-docs-wrap" class="space-y-3">
                    <div id="executive-finance-docs-body" class="space-y-3"></div>
                </div>
            </div>
            </div>

            <div id="executive-approval-route-slot" class="mt-4 hidden"></div>

            <div class="sticky bottom-0 z-20 -mx-6 mt-4 border-t border-slate-200 bg-white/95 px-6 py-3 backdrop-blur">
                <label for="executive-approval-comment" class="mb-1 block text-xs font-bold text-slate-700">ความคิดเห็นจากผู้อนุมัติ (ถ้ามี)</label>
                <textarea id="executive-approval-comment" rows="2" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" placeholder="ระบุความคิดเห็นเพิ่มเติม หากไม่มีความคิดเห็นสามารถเว้นว่างได้"></textarea>
                <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
                    <button type="button" @click="$dispatch('close-modal', 'withdraw-executive-approval-form-modal')"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold"
                        style="background-color:#ffffff;color:#475569;">
                        ปิด
                    </button>
                    <button type="button" id="executive-approval-revision-button"
                        @click="window.__withdrawExecutiveOpenRevisionModal?.()"
                        class="inline-flex h-10 items-center justify-center rounded-lg px-4 text-sm font-bold"
                        style="background-color:#e11d48;color:#ffffff;">
                        ส่งกลับให้แก้ไข
                    </button>
                    <button type="button" id="executive-approval-submit-button"
                        onclick="window.__withdrawExecutiveHandleSubmitClick?.();"
                        class="inline-flex h-10 items-center justify-center rounded-lg px-5 text-sm font-bold"
                        style="background-color:#059669;color:#ffffff;">
                        ยืนยันอนุมัติ
                    </button>
                </div>
            </div>
        </div>
    </x-modal>
    <x-modal name="withdraw-executive-revision-modal" maxWidth="lg" :static="true" variant="modern"
        title="ส่งกลับให้แก้ไข" description="โปรดระบุรายละเอียดที่ต้องแก้ไขก่อนส่งกลับรายการ"
        iconClass="fas fa-rotate-left">
        <div class="p-6">
            <div>
                <label for="executive-revision-reason" class="mb-1 block text-sm font-bold text-slate-700">รายละเอียดที่ต้องแก้ไข</label>
                <textarea id="executive-revision-reason" rows="5" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" placeholder="ระบุรายการที่ต้องแก้ไขให้ชัดเจน"></textarea>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'withdraw-executive-revision-modal')"
                    class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    ยกเลิก
                </button>
                <button type="button" id="executive-revision-submit-button"
                    @click="window.__withdrawExecutiveRevisionProceed?.()"
                    class="inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-rose-600 to-red-500">
                    ส่งให้แก้ไข
                </button>
            </div>
        </div>
    </x-modal>
    <x-confirm-modal
        name="withdraw-executive-revision-confirm-modal"
        maxWidth="sm"
        :title="__('ยืนยันส่งกลับให้แก้ไข?')"
        :message="__('ระบบจะส่งรายการกลับไปยังผู้ทำรายการเพื่อให้แก้ไขตามรายละเอียดที่ระบุ')"
        confirmText="{{ __('ยืนยันส่งกลับ') }}"
        cancelText="{{ __('ยกเลิก') }}"
        confirmVariant="danger"
        confirmClick="window.__withdrawExecutiveSubmitRevision()"
    />
    <x-confirm-modal
        name="withdraw-executive-approval-confirm-modal"
        maxWidth="sm"
        :title="__('ยืนยันอนุมัติรายการ?')"
        :message="__('ท่านต้องการยืนยันการอนุมัติรายการเบิกจ่ายนี้หรือไม่')"
        confirmText="{{ __('ยืนยันอนุมัติ') }}"
        cancelText="{{ __('ยกเลิก') }}"
        confirmVariant="primary"
        confirmClick="window.__withdrawExecutiveApprovalConfirmSubmit()"
        confirmId="withdraw-executive-approval-confirm-submit-button"
    />
    <x-confirm-modal
        name="withdraw-approval-submit-confirm-modal"
        maxWidth="sm"
        :title="__('ยืนยันอนุมัติรายการ?')"
        :message="__('ระบบจะบันทึกความคิดเห็นและดำเนินการอนุมัติรายการนี้')"
        confirmText="{{ __('ยืนยันอนุมัติ') }}"
        cancelText="{{ __('ยกเลิก') }}"
        confirmVariant="primary"
        confirmClick="window.__withdrawApprovalConfirmSubmit()"
    />

    @push('scripts')
        <script>
            window.__withdrawApprovalQueuedTab = window.__withdrawApprovalQueuedTab || 'pending';
            window.__withdrawApprovalSwitchTab = window.__withdrawApprovalSwitchTab || function (tab) {
                window.__withdrawApprovalQueuedTab = String(tab || '').trim().toLowerCase() === 'approved' ? 'approved' : 'pending';
            };

            const initWithdrawApprovalPage = () => {
                if (window.__withdrawApprovalPageInited) return;
                const tableBody = document.getElementById('approval-general-table-body');
                const countEl = document.getElementById('approval-count');
                const filterFiscalYearSelect = document.getElementById('approval-filter-fiscal-year');
                const filterStatusSelect = document.getElementById('approval-filter-status');
                const filterTypeSelect = document.getElementById('approval-filter-type');
                const filterSearchInput = document.getElementById('approval-filter-search');
                const pendingTabButton = document.getElementById('approval-tab-pending');
                const approvedTabButton = document.getElementById('approval-tab-approved');
                const sectionTitle = document.getElementById('approval-section-title');
                if (!tableBody || !pendingTabButton || !approvedTabButton) {
                    return;
                }
                window.__withdrawApprovalPageInited = true;
                const generalListApi = @json($generalListApiUrl ?? url('/withdraw/api/general/list?scope=reviewer-pending'));
                const documentReviewListApi = @json(url('/withdraw/api/general/list?scope=document-review'));
                const generalProceedApi = @json(url('/withdraw/api/general/proceed'));
                const salaryProceedApi = @json(url('/withdraw/api/salary/proceed'));
                const generalDetailApiBase = @json(url('/withdraw/api/general'));
                const salaryDetailApiBase = @json(url('/withdraw/api/salary'));
                const expenseChartOptionsApi = @json(url('/withdraw/api/general/expense-chart-options'));
                const salaryExpenseChartOptionsApi = @json(url('/withdraw/api/salary/expense-chart-options'));
                const budgetSourceOptionsApi = @json(url('/withdraw/api/general/budget-source-options'));
                const salaryBudgetSourceOptionsApi = @json(url('/withdraw/api/salary/budget-source-options'));
                const procurementListApi = @json(url('/withdraw/api/project/procurement'));
                const procurementPdfByIdBase = @json(url('/withdraw/api/project/procurement'));
                const normalizeWithdrawKind = (kind) => {
                    const normalized = String(kind || 'general').trim();
                    if (normalized === 'salary') return 'salary';
                    if (normalized === 'project') return 'project';
                    return 'general';
                };
                const isProjectWithdrawKind = (kind) => normalizeWithdrawKind(kind) === 'project';
                const formatApprovalListSubject = (row) => {
                    const subject = String(row?.subject || '').trim() || '-';
                    const kind = normalizeWithdrawKind(row?.withdraw_kind || row?.withdraw_type || '');
                    const typeLabel = String(row?.type || '').trim();
                    const isProject = kind === 'project' || typeLabel.includes('โครงการ');
                    const projectName = String(row?.project_name || '').trim();
                    if (!isProject || projectName === '' || subject === '-') {
                        return subject;
                    }
                    if (subject === projectName || subject.endsWith(` - ${projectName}`) || subject.includes(` - ${projectName}`)) {
                        return subject;
                    }
                    return `${subject} - ${projectName}`;
                };
                const resolveWithdrawKindFromContext = (kind = 'general', listRow = null, detail = null) => {
                    const normalized = normalizeWithdrawKind(kind);
                    if (normalized === 'project' || normalized === 'salary') {
                        return normalized;
                    }
                    const rowKind = normalizeWithdrawKind(listRow?.withdraw_kind || listRow?.withdraw_type || '');
                    if (rowKind === 'project' || rowKind === 'salary') {
                        return rowKind;
                    }
                    const rowType = String(listRow?.type || '').trim();
                    if (rowType.includes('โครงการ')) {
                        return 'project';
                    }
                    const detailType = String(detail?.withdraw_type || '').trim();
                    if (detailType === 'project') {
                        return 'project';
                    }
                    if (detailType === 'salary') {
                        return 'salary';
                    }
                    return 'general';
                };
                const isProjectWithdrawContext = (kind = 'general', listRow = null, detail = null) =>
                    resolveWithdrawKindFromContext(kind, listRow, detail) === 'project';
                const findRowById = (id, kind = '') => {
                    const numericId = Number(id || 0);
                    if (numericId <= 0) {
                        return null;
                    }
                    if (kind !== '') {
                        const normalizedKind = normalizeWithdrawKind(kind);
                        const matched = generalTableRows.find((item) => Number(item.id || 0) === numericId && normalizeWithdrawKind(item.withdraw_kind) === normalizedKind) || null;
                        if (matched) {
                            return matched;
                        }
                    }
                    return generalTableRows.find((item) => Number(item.id || 0) === numericId) || null;
                };
                const detailApiBaseForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryDetailApiBase : generalDetailApiBase;
                const proceedApiForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryProceedApi : generalProceedApi;
                const expenseChartApiForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryExpenseChartOptionsApi : expenseChartOptionsApi;
                const budgetSourceApiForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryBudgetSourceOptionsApi : budgetSourceOptionsApi;
                const openWithdrawDetailModal = async (rowOrId, kind = 'general', options = {}) => {
                    const row = typeof rowOrId === 'object' && rowOrId !== null ? rowOrId : findRowById(rowOrId, kind);
                    const resolvedKind = normalizeWithdrawKind(row?.withdraw_kind || kind);
                    const id = Number(row?.id || rowOrId || 0);
                    if (id <= 0) {
                        return;
                    }
                    if (resolvedKind === 'salary' && window.WithdrawSalaryDetailModal?.openById) {
                        return window.WithdrawSalaryDetailModal.openById(id, options);
                    }
                    return window.WithdrawGeneralDetailModal?.openById?.(id, options);
                };
                const detailElementId = (part, kind = 'general') => {
                    const prefix = normalizeWithdrawKind(kind) === 'salary' ? 'withdraw-salary-detail' : 'withdraw-detail';
                    return `${prefix}-${part}`;
                };
                const getDetailRootForKind = (kind = 'general') =>
                    document.getElementById(detailElementId('status', kind))?.closest('.space-y-3') ?? null;
                const queryDetailIn = (root, part, kind = 'general') => {
                    if (!root) return null;
                    if (part === 'documents') {
                        return root.querySelector('[data-withdraw-document-preview]')
                            || root.querySelector(`#${detailElementId(part, kind)}`)
                            || null;
                    }
                    return root.querySelector(`#${detailElementId(part, kind)}`) ?? null;
                };
                const canEmbedPdfPreviewInApproval = () => {
                    if (typeof window.matchMedia !== 'function') return true;
                    return window.matchMedia('(min-width: 1024px)').matches
                        && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                };
                const canDesktopExpandPdfPreview = () => canEmbedPdfPreviewInApproval();
                const stripPdfIframesForCompactView = (root) => {
                    if (!root || canEmbedPdfPreviewInApproval()) return;
                    root.querySelectorAll('iframe').forEach((iframe) => iframe.remove());
                };
                const renderOnDemandPdfBlock = (title, url, metaHtml = '', tone = 'sky') => {
                    const safeTitle = escapeHtml(String(title || 'เอกสาร').trim() || 'เอกสาร');
                    const rawUrl = String(url || '').trim();
                    const freshUrl = rawUrl === ''
                        ? ''
                        : `${rawUrl}${rawUrl.includes('?') ? '&' : '?'}preview=${Date.now()}`;
                    const safeUrl = escapeHtml(freshUrl);
                    const toneMap = {
                        sky: {
                            border: 'border-slate-200',
                            chip: 'bg-rose-50 text-rose-700 ring-rose-100',
                            btn: 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100',
                            preview: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                        },
                        violet: {
                            border: 'border-violet-100',
                            chip: 'bg-violet-100 text-violet-700 ring-violet-200',
                            btn: 'border-violet-200 bg-violet-50 text-violet-700 hover:bg-violet-100',
                            preview: 'border-violet-200 bg-white text-violet-700 hover:bg-violet-50',
                        },
                    };
                    const colors = toneMap[tone] || toneMap.sky;
                    if (safeUrl === '') {
                        return `<div class="rounded-xl border border-dashed ${colors.border} bg-white px-3 py-3 text-sm text-slate-500">${metaHtml}<div>ยังไม่มีไฟล์ PDF</div></div>`;
                    }
                    if (canEmbedPdfPreviewInApproval()) {
                        return `<div class="overflow-hidden rounded-xl border ${colors.border} bg-white p-2">
                            ${metaHtml}
                            <iframe src="${safeUrl}" title="${safeTitle}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe>
                        </div>`;
                    }
                    return `<div class="overflow-hidden rounded-xl border ${colors.border} bg-white p-3" data-executive-pdf-card>
                        ${metaHtml}
                        <div class="flex flex-col gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50/80 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1 ${colors.chip}"><i class="fas fa-file-pdf"></i></span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800">${safeTitle}</div>
                                    <div class="text-xs text-slate-500">เปิดไฟล์เมื่อพร้อมตรวจ ไม่โหลด PDF อัตโนมัติ</div>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <a href="${safeUrl}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition ${colors.btn}">
                                    <i class="fas fa-external-link-alt text-[10px]"></i>เปิด PDF
                                </a>
                            </div>
                        </div>
                    </div>`;
                };
                const convertEmbeddedPdfsToOnDemand = (root) => {
                    if (!root || canEmbedPdfPreviewInApproval()) return;
                    root.querySelectorAll('iframe').forEach((iframe) => {
                        const src = String(iframe.getAttribute('src') || '').trim();
                        const title = String(iframe.getAttribute('title') || 'เอกสาร PDF').trim() || 'เอกสาร PDF';
                        const host = iframe.closest('.overflow-hidden.rounded-xl, .rounded-xl') || iframe.parentElement;
                        if (!host || src === '') {
                            iframe.remove();
                            return;
                        }
                        const openLink = host.querySelector('a[href]');
                        const caption = openLink?.closest('.mb-2')?.outerHTML || '';
                        host.outerHTML = renderOnDemandPdfBlock(title, src, caption, 'sky');
                    });
                };
                document.addEventListener('click', (event) => {
                    const previewButton = event.target.closest('[data-executive-pdf-preview]');
                    if (!previewButton) return;
                    event.preventDefault();
                    const card = previewButton.closest('[data-executive-pdf-card]');
                    const slot = card?.querySelector('[data-executive-pdf-preview-slot]');
                    const url = String(previewButton.getAttribute('data-executive-pdf-preview') || '').trim();
                    const title = String(previewButton.getAttribute('data-executive-pdf-title') || 'เอกสาร PDF').trim() || 'เอกสาร PDF';
                    if (!slot || url === '') return;
                    if (!slot.classList.contains('hidden') && slot.querySelector('iframe')) {
                        slot.classList.add('hidden');
                        slot.innerHTML = '';
                        previewButton.innerHTML = '<i class="fas fa-eye text-[10px]"></i>แสดงตัวอย่าง';
                        return;
                    }
                    slot.classList.remove('hidden');
                    slot.innerHTML = `<iframe src="${escapeHtml(url)}" title="${escapeHtml(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe>`;
                    previewButton.innerHTML = '<i class="fas fa-eye-slash text-[10px]"></i>ซ่อนตัวอย่าง';
                });
                const departmentApiUrl = @json((string) config('services.department_api.url', ''));
                const departmentApiToken = @json((string) config('services.department_api.token', ''));
                const approveCommentInput = document.getElementById('withdraw-approval-comment');
                const approveConfirmButton = document.getElementById('withdraw-approval-confirm-button');
                const approveConfirmModal = document.getElementById('withdraw-approval-confirm-modal');
                const approvalReviewDetailContent = document.getElementById('approval-review-detail-content');
                const approvalConfirmSummaryCard = document.getElementById('approval-confirm-summary-card');
                const getExecutiveApprovalDetailContent = () => document.getElementById('executive-approval-detail-content');
                const getExecutiveProcurementElements = () => ({
                    card: document.getElementById('executive-procurement-card'),
                    empty: document.getElementById('executive-procurement-empty'),
                    wrap: document.getElementById('executive-procurement-wrap'),
                    body: document.getElementById('executive-procurement-body'),
                });
                const getExecutiveFinanceDocsElements = () => ({
                    card: document.getElementById('executive-finance-docs-card'),
                    empty: document.getElementById('executive-finance-docs-empty'),
                    wrap: document.getElementById('executive-finance-docs-wrap'),
                    body: document.getElementById('executive-finance-docs-body'),
                });
                const executiveApprovalCommentInput = document.getElementById('executive-approval-comment');
                const executiveApprovalRouteSlot = document.getElementById('executive-approval-route-slot');
                const executiveApprovalSubmitButton = document.getElementById('executive-approval-submit-button');
                const executiveApprovalConfirmSubmitButton = document.getElementById('withdraw-executive-approval-confirm-submit-button');
                const executiveApprovalRevisionButton = document.getElementById('executive-approval-revision-button');
                const executiveRevisionReasonInput = document.getElementById('executive-revision-reason');
                const executiveRevisionSubmitButton = document.getElementById('executive-revision-submit-button');
                const getExecutiveRevisionReasonInput = () =>
                    document.querySelector('#withdraw-executive-revision-modal #executive-revision-reason')
                    || document.getElementById('executive-revision-reason');
                const statusClassMap = {
                    'ร่าง': 'bg-slate-100 text-slate-700 ring-slate-200',
                    'รอตรวจสอบ/อนุมัติ': 'bg-amber-100 text-amber-700 ring-amber-200',
                    'รอการเงินตรวจสอบเอกสาร': 'bg-cyan-100 text-cyan-700 ring-cyan-200',
                    'รออนุมัติ': 'bg-violet-100 text-violet-700 ring-violet-200',
                    'อนุมัติ': 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                };
                let generalTableRows = @json($sampleRows->values());
                let pendingApproveId = 0;
                let pendingApproveKind = 'general';
                let activeTab = 'pending';
                let pendingExecutiveApprovalId = 0;
                let pendingExecutiveApprovalKind = 'general';
                let pendingExecutiveSubmitPayload = null;
                let expenseChartOptions = [];
                let budgetSourceOptions = [];
                let departmentByCodeMap = null;
                const budgetSourceOptionMap = new Map();

                const escapeHtml = (unsafe) => String(unsafe ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[char]));
                const toNumber = (value) => {
                    const parsed = Number(value || 0);
                    return Number.isFinite(parsed) ? parsed : 0;
                };
                const formatMoney = (value) => toNumber(value).toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
                const toThaiBahtText = (amount) => {
                    const normalizedAmount = Number(amount || 0);
                    if (Number.isNaN(normalizedAmount) || normalizedAmount < 0) return '-';
                    const digitText = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
                    const unitText = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
                    const convertInteger = (numText) => {
                        if (numText === '' || /^0+$/.test(numText)) return 'ศูนย์';
                        let output = '';
                        const length = numText.length;
                        for (let index = 0; index < length; index += 1) {
                            const digit = Number(numText[index]);
                            const position = length - index - 1;
                            const unitIndex = position % 6;
                            if (digit === 0) {
                                if (unitIndex === 0 && position >= 6) output += unitText[6];
                                continue;
                            }
                            if (unitIndex === 0 && position >= 6) output += unitText[6];
                            if (unitIndex === 1) {
                                if (digit === 1) output += unitText[1];
                                else if (digit === 2) output += `ยี่${unitText[1]}`;
                                else output += `${digitText[digit]}${unitText[1]}`;
                                continue;
                            }
                            if (unitIndex === 0 && digit === 1 && length > 1) {
                                output += 'เอ็ด';
                                continue;
                            }
                            output += `${digitText[digit]}${unitText[unitIndex]}`;
                        }
                        return output;
                    };
                    const fixedAmount = normalizedAmount.toFixed(2);
                    const [bahtPartRaw, satangPartRaw] = fixedAmount.split('.');
                    const bahtText = convertInteger(bahtPartRaw);
                    if (Number(satangPartRaw || 0) === 0) return `${bahtText}บาทถ้วน`;
                    return `${bahtText}บาท${convertInteger(String(satangPartRaw || '0'))}สตางค์`;
                };
                const formatThaiDate = (value) => {
                    const raw = String(value || '').trim();
                    if (raw === '') return '-';
                    const parsed = new Date(raw);
                    if (Number.isNaN(parsed.getTime())) return raw;
                    return parsed.toLocaleDateString('th-TH', { day: '2-digit', month: 'long', year: 'numeric' });
                };

                const formatApprovalDateTime = (value) => {
                    const raw = String(value || '').trim();
                    if (raw === '' || raw.startsWith('0000-00-00')) return '';
                    const parsed = new Date(raw.replace(' ', 'T'));
                    if (Number.isNaN(parsed.getTime())) return '';
                    const dateText = parsed.toLocaleDateString('th-TH', {
                        day: 'numeric',
                        month: 'short',
                        year: '2-digit',
                    });
                    const timeText = parsed.toLocaleTimeString('th-TH', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                    });
                    return `${dateText} ${timeText} น.`;
                };
                const buildApprovalTimeline = (row) => {
                    const rawStatus = String(row?.raw_status || '').trim();
                    const displayNameByCode = {
                        reviewer_head: 'ผู้ตรวจสอบ/หัวหน้างาน',
                        finance_officer: 'งานการเงินตรวจสอบรายการ',
                    };
                    const steps = (Array.isArray(row?.approval_steps) ? row.approval_steps : [])
                        .map((step) => {
                            const code = String(step?.step_code || '').trim().toLowerCase();
                            const storedName = String(step?.step_name || '').trim();
                            return {
                                code,
                                name: displayNameByCode[code] || storedName || code || 'ขั้นตอน',
                                status: String(step?.status || 'pending').trim().toLowerCase(),
                                order: Number(step?.step_order || 0),
                                approvedAt: String(step?.approved_at || '').trim(),
                            };
                        })
                        .filter((step) => step.name !== '')
                        .sort((a, b) => a.order - b.order);
                    if (rawStatus === 'saved' || rawStatus === 'cancelled' || steps.length === 0) {
                        const emptyLabel = rawStatus === 'cancelled'
                            ? 'รายการนี้ถูกยกเลิกแล้ว'
                            : (rawStatus === 'saved' ? 'ยังไม่เข้าสู่วงอนุมัติ — บันทึกเป็นร่าง' : 'ยังไม่มีขั้นตอนอนุมัติ');
                        return `<div class="h-full rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3 py-2 text-center text-[11px] font-semibold text-slate-500">${escapeHtml(emptyLabel)}</div>`;
                    }
                    const currentIndex = steps.findIndex((step) => step.status !== 'approved' && step.status !== 'skipped');
                    const allDone = currentIndex < 0;
                    const remaining = allDone ? 0 : steps.slice(currentIndex).filter((step) => step.status !== 'approved' && step.status !== 'skipped').length;
                    const currentName = allDone ? 'อนุมัติครบทุกขั้นตอน' : steps[currentIndex].name;
                    const summary = allDone
                        ? 'อนุมัติครบทุกขั้นตอนแล้ว'
                        : `ถึงขั้นตอน: ${currentName} · เหลือ ${remaining} ขั้นตอน`;
                    return `<div class="h-full w-full rounded-xl border border-slate-200/80 bg-gradient-to-br from-slate-50/80 via-white to-emerald-50/30 p-3">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ความคืบหน้าการอนุมัติ</span>
                            <span class="text-[11px] font-semibold ${allDone ? 'text-emerald-700' : 'text-amber-700'}">${escapeHtml(summary)}</span>
                        </div>
                        <div class="relative w-full pb-1">
                            <div class="flex w-full items-start">
                                ${steps.map((step, index) => {
                                    const isDone = allDone || index < currentIndex || step.status === 'approved';
                                    const isCurrent = !allDone && index === currentIndex;
                                    const isRejected = step.status === 'rejected';
                                    const approvedAtText = isDone && !isRejected ? formatApprovalDateTime(step.approvedAt) : '';
                                    const dotClass = isRejected
                                        ? 'border-rose-500 bg-rose-500 text-white shadow shadow-rose-200'
                                        : (isDone
                                            ? 'border-emerald-500 bg-emerald-500 text-white shadow shadow-emerald-200'
                                            : (isCurrent ? 'border-amber-300 bg-amber-50 text-amber-700 shadow shadow-amber-100' : 'border-slate-300 bg-white text-slate-400'));
                                    const textClass = isRejected ? 'text-rose-700' : (isDone ? 'text-emerald-700' : (isCurrent ? 'text-amber-700' : 'text-slate-500'));
                                    const subText = isRejected
                                        ? 'ส่งกลับแก้ไข'
                                        : (isDone
                                            ? (step.code === 'finance_officer' ? 'ตรวจสอบแล้ว' : 'ผ่านแล้ว')
                                            : (isCurrent
                                                ? (step.code === 'finance_officer' ? 'กำลังตรวจสอบ' : 'กำลังดำเนินการ')
                                                : 'รอคิว'));
                                    const subClass = isRejected
                                        ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-100'
                                        : (isDone ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : (isCurrent ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'));
                                    const connector = index < steps.length - 1
                                        ? `<div class="pointer-events-none absolute left-1/2 top-4 h-0.5 w-full ${isDone && !isRejected ? 'bg-emerald-400' : 'bg-slate-200'}"></div>`
                                        : '';
                                    return `<div class="relative flex min-w-0 flex-1 flex-col items-center px-1 text-center">
                                        ${connector}
                                        <span class="relative z-10 inline-flex h-8 w-8 items-center justify-center rounded-full border text-[11px] font-bold ${dotClass}">
                                            ${isRejected ? '<i class="fas fa-rotate-left text-[10px]"></i>' : (isDone ? '<i class="fas fa-check text-[10px]"></i>' : `${index + 1}`)}
                                        </span>
                                        <span class="mt-1 line-clamp-2 min-h-[2rem] text-[11px] font-bold leading-4 ${textClass}">${escapeHtml(step.name)}</span>
                                        <span class="mt-0.5 rounded-full px-2 py-0.5 text-[10px] font-semibold ${subClass}">${subText}</span>
                                        ${approvedAtText !== '' ? `<span class="mt-0.5 text-[10px] font-medium leading-4 text-emerald-700">${escapeHtml(approvedAtText)}</span>` : ''}
                                    </div>`;
                                }).join('')}
                            </div>
                        </div>
                    </div>`;
                };

                const isApprovalMenuPendingRow = (row) => {
                    const userStep = String(row?.user_step_status || '').trim().toLowerCase();
                    if (userStep !== 'pending') return false;
                    const rawStatus = String(row?.raw_status || '').trim();
                    const statusLabel = String(row?.status || '').trim();
                    if (rawStatus === 'pending_finance_review') return false;
                    if (statusLabel.includes('รอการเงินตรวจสอบเอกสาร')) return false;
                    return true;
                };
                const syncPendingBadge = (rows) => {
                    const pendingCount = (Array.isArray(rows) ? rows : []).filter(isApprovalMenuPendingRow).length;
                    document.querySelectorAll('#approval-pending-count-badge').forEach((badge) => {
                        badge.textContent = String(pendingCount);
                        badge.dataset.count = String(pendingCount);
                        badge.classList.toggle('hidden', pendingCount <= 0);
                    });
                    const banner = document.getElementById('approval-pending-banner');
                    if (banner) {
                        banner.classList.toggle('hidden', pendingCount <= 0);
                        banner.classList.toggle('opacity-60', pendingCount <= 0);
                    }
                    window.refreshWithdrawApprovalPendingBadge?.();
                };
                const refreshDocumentReviewPendingBadge = async () => {
                    const badge = document.getElementById('document-review-pending-count-badge');
                    if (!badge) return;
                    try {
                        const response = await fetch(`${documentReviewListApi}&_=${Date.now()}`, {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                        });
                        const result = await response.json();
                        const rows = Array.isArray(result?.data) ? result.data : [];
                        const pendingCount = rows.filter((row) => String(row?.user_step_status || '').trim() === 'pending').length;
                        badge.textContent = String(pendingCount);
                        badge.dataset.count = String(pendingCount);
                        badge.classList.toggle('hidden', pendingCount <= 0);
                    } catch (error) {}
                };


                const renderRows = (rows) => {
                    const safeRows = Array.isArray(rows) ? rows : [];
                    if (countEl) countEl.textContent = String(safeRows.length);
                    if (!tableBody) return;
                    if (safeRows.length === 0) {
                        tableBody.innerHTML = '<div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">ไม่พบข้อมูล</div>';
                        return;
                    }
                    tableBody.innerHTML = safeRows.map((row) => {
                        const status = String(row.status || '-');
                        const statusClass = statusClassMap[status] || 'bg-slate-100 text-slate-600 ring-slate-200';
                        const id = Number(row.id || 0);
                        const withdrawKind = normalizeWithdrawKind(row.withdraw_kind || row.withdraw_type);
                        const userStepStatus = String(row.user_step_status || '').trim().toLowerCase();
                        const statusText = String(row.status || '').trim();
                        // Prefer the user's own step label to avoid mislabeling when
                        // the same staff appears in multiple approval roles.
                        const approverStatusText = [
                            row.user_step_name,
                            row.user_step_label,
                            row.approval_level_name,
                            row.current_step_name,
                            row.current_step_label,
                            row.role_name,
                        ].map((value) => String(value || '').trim()).find((value) => value !== '') || '';
                        const isExecutiveApprover = approverStatusText.includes('ผู้บริหาร');
                        const canApprove = userStepStatus === 'pending'
                            && !(statusText === 'รอตรวจสอบ/อนุมัติ' && isExecutiveApprover);
                        const approveButtonLabel = approverStatusText !== ''
                            ? `อนุมัติรายการ (${escapeHtml(approverStatusText)})`
                            : 'อนุมัติรายการ';
                        const subject = formatApprovalListSubject(row);
                        const amount = Number(row.amount || 0).toLocaleString('th-TH');
                        const subjectHtml = subject !== '-'
                            ? `<a href="javascript:void(0)" data-action="view" data-id="${id}" data-withdraw-kind="${withdrawKind}" class="line-clamp-2 font-extrabold text-slate-900 underline decoration-slate-300 underline-offset-4 hover:text-amber-600 hover:decoration-amber-300">${escapeHtml(subject)}</a>`
                            : `<span class="text-slate-400">-</span>`;
                        const procurementTypeLabels = {
                            w119: 'จัดซื้อจัดจ้าง ว.119',
                            eight: 'จัดซื้อจัดจ้าง 8 ข้อ',
                        };
                        const procurementLabel = (Array.isArray(row.procurement_types) ? row.procurement_types : [])
                            .map((typeCode) => procurementTypeLabels[String(typeCode || '').trim()] || '')
                            .filter(Boolean)
                            .map((label) => `<span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-[11px] font-semibold text-orange-700 ring-1 ring-orange-100">${escapeHtml(label)}</span>`)
                            .join('');
                        return `<article class="group relative w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-md shadow-slate-200/50 ring-1 ring-white/80 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-amber-200/80 hover:shadow-xl hover:shadow-amber-100/40">
                            <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-amber-300/20 blur-2xl"></div>
                            <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-amber-200/60 to-transparent"></div>
                            <div class="relative flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100/90 px-2.5 py-1 text-[10px] font-bold tracking-wide text-slate-600 ring-1 ring-slate-200/80">
                                        <i class="fas fa-hashtag text-[10px]"></i>
                                        เลขที่ ${escapeHtml(row.code || '-')}
                                    </div>
                                    <h6 class="mt-2 text-[15px] font-extrabold leading-6 text-slate-900">${subjectHtml}</h6>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-100">${escapeHtml(row.type || '-')}</span>
                                    ${procurementLabel}
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ${statusClass}">${escapeHtml(status)}</span>
                                </div>
                            </div>
                            <div class="relative mt-3 flex w-full flex-col gap-3 lg:flex-row lg:items-stretch">
                                <div class="grid w-full grid-cols-1 gap-3 rounded-xl border border-slate-200/80 bg-gradient-to-br from-slate-50/70 to-white p-3 text-sm sm:grid-cols-3 lg:max-w-none lg:flex-[0.9]">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">วันที่เบิกจ่าย</div>
                                        <div class="mt-1 font-semibold text-slate-800">${escapeHtml(row.date || '-')}</div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ผู้ขอเบิก</div>
                                        <div class="mt-1 truncate font-semibold text-slate-800">${escapeHtml(row.manager || '-')}</div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">จำนวนเงิน</div>
                                        <div class="mt-1 text-base font-extrabold text-slate-900">${amount} <span class="text-[11px] font-semibold text-slate-500">บาท</span></div>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-[1.4]">
                                    ${buildApprovalTimeline(row)}
                                </div>
                            </div>
                            <div class="relative mt-3 flex flex-wrap items-center justify-end gap-2">
                                ${canApprove ? `<button type="button" data-action="approve" data-id="${id}" data-withdraw-kind="${withdrawKind}" data-code="${escapeHtml(String(row.code || '-'))}" data-date="${escapeHtml(String(row.date || '-'))}" data-subject="${escapeHtml(subject)}" data-manager="${escapeHtml(String(row.manager || '-'))}" data-amount="${Number(row.amount || 0)}" class="group inline-flex items-center gap-1.5 rounded-xl border border-emerald-600/20 bg-gradient-to-r from-emerald-500 to-green-600 px-4 py-2 text-[11px] font-bold text-white shadow-md shadow-emerald-200 transition-all duration-200 hover:-translate-y-0.5 hover:from-emerald-600 hover:to-green-700 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
                                    <i class="fas fa-check text-[11px] transition-transform duration-200 group-hover:scale-110"></i>${approveButtonLabel}
                                </button>` : ''}
                            </div>
                        </article>`;
                    }).join('');
                };

                const syncTabUi = () => {
                    if (sectionTitle) {
                        sectionTitle.textContent = activeTab === 'approved' ? 'รายการอนุมัติแล้ว' : 'รายการรออนุมัติ';
                    }
                    if (pendingTabButton) {
                        pendingTabButton.className = activeTab === 'pending'
                            ? 'inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 ring-1 ring-amber-100 transition hover:bg-amber-100'
                            : 'inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50';
                    }
                    if (approvedTabButton) {
                        approvedTabButton.className = activeTab === 'approved'
                            ? 'inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100 transition hover:bg-emerald-100'
                            : 'inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50';
                    }
                };

                const postJson = async (url, payload) => {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify(payload || {}),
                    });
                    const data = await response.json().catch(() => null);
                    if (!response.ok) {
                        throw new Error(String(data?.message || 'ไม่สามารถทำรายการได้'));
                    }
                    return data;
                };
                const fillApproveConfirmDetail = (row) => {
                    const approveDetailCodeEl = document.getElementById('approval-confirm-detail-code');
                    const approveDetailDateEl = document.getElementById('approval-confirm-detail-date');
                    const approveDetailSubjectEl = document.getElementById('approval-confirm-detail-subject');
                    const approveDetailApplicantEl = document.getElementById('approval-confirm-detail-applicant');
                    const approveDetailAmountEl = document.getElementById('approval-confirm-detail-amount');
                    if (approveDetailCodeEl) approveDetailCodeEl.textContent = String(row?.code || row?.document_number || '-').trim() || '-';
                    if (approveDetailDateEl) approveDetailDateEl.textContent = String(row?.date || row?.document_date || '-').trim() || '-';
                    if (approveDetailSubjectEl) approveDetailSubjectEl.textContent = formatApprovalListSubject(row);
                    if (approveDetailApplicantEl) approveDetailApplicantEl.textContent = String(row?.manager || row?.applicant_name || row?.applicant_staff_id || '-').trim() || '-';
                    if (approveDetailAmountEl) approveDetailAmountEl.textContent = Number(row?.amount || row?.total_amount || 0).toLocaleString('th-TH');
                };
                const resolveWithdrawDocumentDisplayCode = (detail = {}) => {
                    const display = String(detail?.document_number_display || '').trim();
                    if (display !== '') return display;
                    const base = String(detail?.document_number || '').trim();
                    if (base === '') return '';
                    const runningNo = Number(detail?.running_no || 0);
                    if (Number.isFinite(runningNo) && runningNo > 0) return `${base}${runningNo}`;
                    return base;
                };
                const getPendingApproveId = () => {
                    const buttonId = Number(String(approveConfirmButton?.dataset?.approvalId || '').trim() || 0);
                    if (buttonId > 0) return buttonId;
                    const modalId = Number(String(approveConfirmModal?.dataset?.approvalId || '').trim() || 0);
                    if (modalId > 0) return modalId;
                    return Number(pendingApproveId || 0);
                };
                const getPendingApproveKind = () => {
                    const fromButton = normalizeWithdrawKind(approveConfirmButton?.dataset?.withdrawKind || '');
                    if (fromButton !== 'general' || approveConfirmButton?.dataset?.withdrawKind) {
                        return fromButton;
                    }
                    const fromModal = normalizeWithdrawKind(approveConfirmModal?.dataset?.withdrawKind || '');
                    if (fromModal !== 'general' || approveConfirmModal?.dataset?.withdrawKind) {
                        return fromModal;
                    }
                    return normalizeWithdrawKind(pendingApproveKind);
                };
                const loadApproveConfirmDetailById = async (id, fallbackRow = null, withdrawKind = 'general') => {
                    if (fallbackRow && Object.keys(fallbackRow).length > 0) {
                        fillApproveConfirmDetail(fallbackRow);
                    } else {
                        fillApproveConfirmDetail({});
                    }
                    try {
                        const response = await fetch(`${detailApiBaseForKind(withdrawKind)}/${id}`, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        if (result?.status !== 'success') return;
                        const detail = result?.data || {};
                        fillApproveConfirmDetail({
                            // Non-executive confirm modal: document number must come from withdraw_document.
                            code: resolveWithdrawDocumentDisplayCode(detail),
                            date: String(fallbackRow?.date || detail.document_date || '').trim(),
                            subject: String(detail.subject || fallbackRow?.subject || '').trim(),
                            project_name: String(detail.project_name || fallbackRow?.project_name || '').trim(),
                            withdraw_kind: normalizeWithdrawKind(withdrawKind || fallbackRow?.withdraw_kind || detail.withdraw_type || ''),
                            withdraw_type: String(detail.withdraw_type || fallbackRow?.withdraw_type || '').trim(),
                            manager: String(detail.applicant_name || fallbackRow?.manager || '').trim(),
                            applicant_name: String(detail.applicant_name || fallbackRow?.manager || '').trim(),
                            applicant_staff_id: String(detail.applicant_staff_id || fallbackRow?.applicant_staff_id || '').trim(),
                            amount: Number(detail.total_amount || fallbackRow?.amount || 0),
                        });
                    } catch (error) {}
                };
                const renderApproveReviewDetailContent = async (id, withdrawKind = 'general') => {
                    if (!approvalReviewDetailContent) return;
                    approvalReviewDetailContent.innerHTML = '';
                    try {
                        await openWithdrawDetailModal(id, withdrawKind, { openModal: false });
                        const detailRoot = getDetailRootForKind(withdrawKind);
                        if (!detailRoot) {
                            approvalReviewDetailContent.innerHTML = '';
                            return;
                        }
                        const cloned = detailRoot.cloneNode(true);
                        cloned.querySelectorAll('.overflow-x-auto').forEach((container) => {
                            container.style.overflowX = 'visible';
                            container.style.overflowY = 'visible';
                        });
                        queryDetailIn(cloned, 'approval-route-section', withdrawKind)?.remove();
                        queryDetailIn(cloned, 'approval-section', withdrawKind)?.remove();
                        cloned.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
                        approvalReviewDetailContent.innerHTML = '';
                        approvalReviewDetailContent.appendChild(cloned);
                    } catch (error) {
                        approvalReviewDetailContent.innerHTML = '';
                    }
                };
                const isFinanceManagerApprovalLevel = (row = null) => {
                    const stepCode = String(row?.user_step_code || '').trim();
                    if (stepCode === 'finance_manager') {
                        return true;
                    }
                    const rawText = [
                        row?.user_step_name,
                        row?.user_step_label,
                        row?.role_name,
                    ].map((value) => String(value || '').trim()).filter(Boolean).join(' ');
                    return rawText.includes('หัวหน้างานการเงิน');
                };
                const isReviewerHeadApprovalLevel = (row = null) => {
                    if (isFinanceManagerApprovalLevel(row)) {
                        return false;
                    }
                    const stepCode = String(row?.user_step_code || '').trim();
                    if (stepCode === 'reviewer_head') {
                        return true;
                    }
                    const rawText = [
                        row?.approval_level_name,
                        row?.approval_level,
                        row?.current_step_name,
                        row?.current_step_label,
                        row?.user_step_name,
                        row?.user_step_label,
                        row?.role_name,
                        row?.step_role_name,
                    ].map((value) => String(value || '').trim()).filter(Boolean).join(' ');
                    if (rawText.includes('หัวหน้างานการเงิน')) {
                        return false;
                    }
                    return rawText.includes('ผู้ตรวจสอบ/หัวหน้างาน') || rawText.includes('ผู้ตรวจสอบ');
                };
                const shouldUseExecutiveApprovalDetail = (row = null, withdrawKind = 'general') => {
                    if (isExecutiveApprovalConfirmLevel(row)) {
                        return true;
                    }
                    return isProjectWithdrawContext(withdrawKind, row) && isReviewerHeadApprovalLevel(row);
                };
                const isExecutiveApprovalConfirmLevel = (row = null) => {
                    if (isFinanceManagerApprovalLevel(row)) {
                        return true;
                    }
                    const stepCode = String(row?.user_step_code || '').trim();
                    if (['faculty_secretary', 'vice_dean', 'dean'].includes(stepCode)) {
                        return true;
                    }
                    const rawText = [
                        row?.approval_level_name,
                        row?.approval_level,
                        row?.current_step_name,
                        row?.current_step_label,
                        row?.user_step_name,
                        row?.user_step_label,
                        row?.role_name,
                        row?.step_role_name,
                    ].map((value) => String(value || '').trim()).filter(Boolean).join(' ');
                    if (rawText === '') return false;
                    if (rawText.includes('ผู้ตรวจสอบ')) return false;
                    return ['เลขาคณะ', 'รองคณบดี', 'คณบดี', 'ผู้บริหาร'].some((label) => rawText.includes(label));
                };
                const isSimpleApprovalConfirmLevel = (row = null, withdrawKind = 'general') => !shouldUseExecutiveApprovalDetail(row, withdrawKind);
                const shouldShowExecutiveBudgetSummary = (row = null, withdrawKind = 'general') => {
                    // ผู้ตรวจสอบ/หัวหน้างาน และขั้นก่อนงานการเงินตรวจสอบ — ไม่แสดงสรุปตามแหล่งงบ
                    const stepCode = String(row?.user_step_code || '').trim();
                    if (['reviewer_head', 'additional_reviewer', 'finance_officer'].includes(stepCode)) {
                        return false;
                    }
                    if (isReviewerHeadApprovalLevel(row)) {
                        return false;
                    }
                    return isFinanceManagerApprovalLevel(row) || isExecutiveApprovalConfirmLevel(row);
                };
                const removeExecutiveBudgetSummaryBlocks = (root) => {
                    if (!root) return;
                    // ลบเฉพาะสรุปตามแหล่งงบ (ของเบิกจ่ายทั่วไป/ผู้บริหาร) — คงตารางงบโครงการไว้
                    root.querySelectorAll('[data-budget-summary-block]').forEach((node) => node.remove());
                };
                const ensureProjectBudgetSummaryVisible = (root, detail = null) => {
                    if (!root) return;
                    const summaryBlock = root.querySelector('[data-detail-memo-block="project-budget-summary"]')
                        || root.querySelector('#withdraw-detail-project-budget-summary');
                    if (!summaryBlock) return;
                    const paragraph3El = root.querySelector('[data-detail-memo-block="paragraph3"]')
                        || root.querySelector('[id$="-detail-paragraph3"]');
                    if (paragraph3El?.parentNode) {
                        paragraph3El.parentNode.insertBefore(summaryBlock, paragraph3El.nextSibling);
                    }
                    summaryBlock.classList.remove('hidden');

                    const body = summaryBlock.querySelector('#withdraw-detail-project-budget-summary-body')
                        || summaryBlock.querySelector('tbody');
                    if (!body || !detail) return;
                    const budgetAmount = Number(detail.project_amount || 0) || 0;
                    const approvedAlready = Number(detail.approved_already || 0) || 0;
                    const approvedThisTime = Number(detail.total_amount || 0) || 0;
                    const totalApproved = approvedAlready + approvedThisTime;
                    const remaining = budgetAmount - totalApproved;
                    const formatDetailMoney = (value) => {
                        const amount = Number(value || 0);
                        const safe = Number.isFinite(amount) ? amount : 0;
                        return safe.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    };
                    body.innerHTML = `
                        <tr>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${formatDetailMoney(budgetAmount)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${formatDetailMoney(approvedAlready)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${formatDetailMoney(approvedThisTime)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${formatDetailMoney(totalApproved)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums ${remaining < 0 ? 'text-rose-600 font-semibold' : ''}">${formatDetailMoney(remaining)}</td>
                        </tr>
                    `;
                };
                const setApprovalConfirmLegacyLayout = (isLegacy) => {
                    if (!approvalReviewDetailContent) return;
                    if (isLegacy) {
                        approvalReviewDetailContent.classList.add('hidden');
                        approvalReviewDetailContent.innerHTML = '';
                        approvalConfirmSummaryCard?.classList.remove('mt-3');
                        return;
                    }
                    approvalReviewDetailContent.classList.remove('hidden');
                    approvalConfirmSummaryCard?.classList.add('mt-3');
                };
                const loadExpenseChartOptions = async (withdrawKind = 'general') => {
                    const apiUrl = expenseChartApiForKind(withdrawKind);
                    if (expenseChartOptions.length > 0 && expenseChartOptions.__apiUrl === apiUrl) {
                        return expenseChartOptions;
                    }
                    try {
                        const response = await fetch(apiUrl, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        const rows = Array.isArray(result?.data?.expense_charts) ? result.data.expense_charts : [];
                        expenseChartOptions = rows.map((row) => ({
                            id: Number(row?.id || 0),
                            payCode: String(row?.pay_code || '').trim(),
                            name: String(row?.name || '').trim(),
                            detail: String(row?.detail || '').trim(),
                        })).filter((row) => row.id > 0 && (row.name !== '' || row.detail !== ''));
                        expenseChartOptions.__apiUrl = apiUrl;
                    } catch (error) {
                        expenseChartOptions = [];
                    }
                    return expenseChartOptions;
                };
                const loadBudgetSourceOptions = async (withdrawKind = 'general') => {
                    const apiUrl = budgetSourceApiForKind(withdrawKind);
                    if (budgetSourceOptions.length > 0 && budgetSourceOptions.__apiUrl === apiUrl) {
                        return budgetSourceOptions;
                    }
                    try {
                        const response = await fetch(`${apiUrl}?_=${Date.now()}`, {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                        });
                        const result = await response.json();
                        const rows = Array.isArray(result?.data?.sources) ? result.data.sources : [];
                        budgetSourceOptionMap.clear();
                        budgetSourceOptions = rows.map((row) => {
                            const id = String(row?.id ?? '').trim();
                            const code = String(row?.code ?? '').trim();
                            const path = String(row?.source_path ?? '').trim();
                            const amount = toNumber(row?.amount || 0);
                            const approvedAmount = toNumber(row?.approved_amount || 0);
                            const label = code !== '' && path !== ''
                                ? `${code} — ${path}`
                                : (code !== '' ? code : path);
                            const option = {
                                id,
                                code,
                                label,
                                amount,
                                approvedAmount,
                                sourceName: String(row?.budget_source_name || '').trim(),
                                sourceCode: String(row?.budget_source_code || '').trim(),
                                sourcePath: path,
                            };
                            if (id !== '') {
                                budgetSourceOptionMap.set(id, option);
                            }
                            return option;
                        }).filter((row) => row.id !== '' && row.label !== '');
                        budgetSourceOptions.__apiUrl = apiUrl;
                    } catch (error) {
                        budgetSourceOptions = [];
                        budgetSourceOptionMap.clear();
                    }
                    return budgetSourceOptions;
                };
                const loadDepartmentByCodeMap = async () => {
                    if (departmentByCodeMap instanceof Map) return departmentByCodeMap;
                    const nextMap = new Map();
                    try {
                        if (departmentApiUrl) {
                            const headers = { Accept: 'application/json' };
                            if (departmentApiToken) {
                                headers.Authorization = String(departmentApiToken).startsWith('Bearer ')
                                    ? String(departmentApiToken)
                                    : `Bearer ${String(departmentApiToken)}`;
                            }
                            const response = await fetch(departmentApiUrl, { headers });
                            const payload = await response.json();
                            const rows = Array.isArray(payload?.data) ? payload.data : [];
                            rows.forEach((item) => {
                                const depCode = String(item?.dep_code || item?.department_code || item?.code || '').trim();
                                const depName = String(item?.dep_name || item?.department_name || item?.name || '').trim();
                                const jobCode = String(item?.job_code || '').trim();
                                const jobName = String(item?.job_name || '').trim();
                                const unitCode = String(item?.unit_code || '').trim();
                                const unitName = String(item?.agency_name || item?.unit_name || '').trim();
                                if (depCode !== '' && depName !== '') {
                                    nextMap.set(depCode, { depName, jobName: '', unitName: '' });
                                }
                                if (jobCode !== '' && depName !== '' && jobName !== '') {
                                    nextMap.set(jobCode, { depName, jobName, unitName: '' });
                                }
                                if (unitCode !== '' && depName !== '' && unitName !== '') {
                                    nextMap.set(unitCode, { depName, jobName, unitName });
                                }
                            });
                        }
                    } catch (error) {}
                    departmentByCodeMap = nextMap;
                    return departmentByCodeMap;
                };
                const resolveExpenseChartLabelById = (expenseChartId) => {
                    const targetId = String(expenseChartId || '').trim();
                    if (targetId === '' || Number(targetId) <= 0) return '';
                    const matched = expenseChartOptions.find((option) => String(option?.id || '') === targetId);
                    if (!matched) return '';
                    return matched.name !== ''
                        ? (matched.detail !== '' ? `${matched.name} (${matched.detail})` : matched.name)
                        : matched.detail;
                };
                const resolveFinanceSelectedItemLabel = (item = {}, row = null) => {
                    const fromItem = String(item?.expense_chart_name || item?.display_name || '').trim();
                    if (fromItem !== '') return fromItem;
                    const fromRowDataset = String(row?.dataset?.expenseChartName || '').trim();
                    if (fromRowDataset !== '') return fromRowDataset;
                    const expenseChartId = String(item?.expense_chart_id || row?.dataset?.expenseChartId || '').trim();
                    const fromOptions = resolveExpenseChartLabelById(expenseChartId);
                    if (fromOptions !== '') return fromOptions;
                    return String(item?.name || row?.dataset?.itemName || '').trim();
                };
                const resolveDisplayedFinanceAmount = (item) => {
                    const financeAmount = toNumber(item?.finance_amount);
                    return financeAmount > 0 ? financeAmount : toNumber(item?.amount || 0);
                };
                const applyFinanceSelectedItemLabels = (root, items = []) => {
                    if (!root) return;
                    const itemById = new Map(
                        (Array.isArray(items) ? items : [])
                            .filter((item) => Number(item?.id || 0) > 0)
                            .map((item) => [Number(item.id), item])
                    );
                    root.querySelectorAll('table tbody tr[data-withdraw-item-id]').forEach((row) => {
                        const itemId = Number(row.dataset.withdrawItemId || 0);
                        const item = itemById.get(itemId) || {};
                        const originalName = String(item?.name || row.dataset.itemName || '').trim()
                            || String(row.querySelector('td:nth-child(2)')?.textContent || '').replace(/^\s*-\s*/, '').trim()
                            || '-';
                        const expenseChartId = Number(item?.expense_chart_id || row.dataset.expenseChartId || 0);
                        const financeLabel = resolveFinanceSelectedItemLabel(item, row);
                        const displayName = financeLabel !== '' ? financeLabel : originalName;
                        row.dataset.itemName = originalName;
                        row.dataset.expenseChartId = expenseChartId > 0 ? String(expenseChartId) : '';
                        if (financeLabel !== '' && financeLabel !== originalName) {
                            row.dataset.expenseChartName = financeLabel;
                        }
                        const nameCell = row.querySelector('td:nth-child(2)');
                        if (nameCell) {
                            nameCell.querySelector('[data-expense-map-wrapper]')?.remove();
                            nameCell.textContent = `- ${displayName}`;
                        }
                        const financeAmount = toNumber(item?.finance_amount);
                        const amountCell = row.querySelector('td:nth-child(3)');
                        if (amountCell && financeAmount > 0) {
                            amountCell.textContent = formatMoney(financeAmount);
                        }
                    });
                    const itemRows = Array.from(root.querySelectorAll('table tbody tr[data-withdraw-item-id]'));
                    const totalAmount = itemRows.reduce((sum, row) => {
                        const amountText = String(row.querySelector('td:nth-child(3)')?.textContent || '').replace(/,/g, '');
                        return sum + toNumber(amountText);
                    }, 0);
                    root.querySelectorAll('table tbody tr').forEach((row) => {
                        if (row.hasAttribute('data-withdraw-item-id')) return;
                        const amountCell = row.querySelector('td:nth-child(3)');
                        if (!amountCell) return;
                        const groupRows = [];
                        let sibling = row.nextElementSibling;
                        while (sibling && sibling.hasAttribute('data-withdraw-item-id')) {
                            groupRows.push(sibling);
                            sibling = sibling.nextElementSibling;
                        }
                        if (groupRows.length === 0) return;
                        const groupTotal = groupRows.reduce((sum, itemRow) => {
                            const amountText = String(itemRow.querySelector('td:nth-child(3)')?.textContent || '').replace(/,/g, '');
                            return sum + toNumber(amountText);
                        }, 0);
                        amountCell.textContent = formatMoney(groupTotal);
                    });
                    const totalEl = root.querySelector('#withdraw-detail-items-total, #withdraw-salary-detail-items-total');
                    const totalTextEl = root.querySelector('#withdraw-detail-items-total-text, #withdraw-salary-detail-items-total-text');
                    if (totalEl && itemRows.length > 0) {
                        totalEl.textContent = formatMoney(totalAmount);
                    }
                    if (totalTextEl && itemRows.length > 0) {
                        totalTextEl.textContent = toThaiBahtText(totalAmount);
                    }
                };
                const removeExecutiveWithdrawItemsSection = (root) => {
                    if (!root) return;
                    root.querySelectorAll('.p-1').forEach((section) => {
                        const titleEl = section.querySelector('.mb-3.text-base.font-bold, .mb-3.sm\\:text-lg, .mb-3');
                        const title = String(titleEl?.textContent || '').trim();
                        if (title === 'รายการเบิกจ่าย') {
                            section.remove();
                        }
                    });
                };
                const renderExecutiveProcurementTableRowsHtml = (rows = []) => rows.map((item) => {
                    const typeLabel = String(item.type_label || item.type || '-').trim() || '-';
                    const title = String(item.item_name || item.procurement || item.subject || '-').trim() || '-';
                    const dateText = String(item.date_modify_display || item.document_date || '-').trim() || '-';
                    const docNo = String(item.document_number_display || item.document_number || '').trim();
                    const procurementId = Number(item.id || 0);
                    const pdfUrl = procurementId > 0 ? `${procurementPdfByIdBase}/${procurementId}/pdf` : '';
                    const metaParts = [
                        typeLabel !== '-' ? typeLabel : '',
                        docNo !== '' ? `เลขที่ ${docNo}` : '',
                        dateText !== '-' ? dateText : '',
                    ].filter(Boolean);
                    const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>
                            ${metaParts.length > 0 ? `<div class="mt-0.5 text-xs text-slate-500">${escapeHtml(metaParts.join(' · '))}</div>` : ''}
                        </div>
                        ${pdfUrl !== '' ? `<a href="${escapeHtml(pdfUrl)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100">เปิดไฟล์</a>` : ''}
                    </div>`;
                    if (pdfUrl === '') {
                        return `<div class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-3 text-sm text-slate-500">${caption}<div>ยังไม่มีไฟล์ PDF</div></div>`;
                    }
                    return renderOnDemandPdfBlock(title, pdfUrl, caption, 'sky');
                }).join('');
                const fetchExecutiveProcurementRows = async (withdrawId) => {
                    if (Number(withdrawId || 0) <= 0) {
                        return [];
                    }
                    const procurementUrl = new URL(procurementListApi, window.location.origin);
                    procurementUrl.searchParams.set('withdraw_id', String(withdrawId));
                    const response = await fetch(procurementUrl.toString(), { headers: { Accept: 'application/json' } });
                    const result = await response.json();
                    return Array.isArray(result?.data) ? result.data : [];
                };
                const parkExecutiveProcurementCard = () => {
                    const content = document.getElementById('executive-approval-detail-content');
                    const card = document.getElementById('executive-procurement-card');
                    if (!content || !card) return;
                    content.insertAdjacentElement('afterend', card);
                };
                const placeExecutiveProcurementBeforeSupportingDocs = (root) => {
                    const card = document.getElementById('executive-procurement-card');
                    if (!card || !root) return;
                    const heading = Array.from(root.querySelectorAll('div')).find((node) => (
                        node.children.length === 0 && node.textContent.trim() === 'เอกสารประกอบการเบิกจ่าย'
                    ));
                    const section = heading?.closest('.p-1') || heading?.parentElement || null;
                    if (!section) return;
                    section.before(card);
                };
                const resetExecutiveProcurementCard = () => {
                    const { card, empty, wrap, body } = getExecutiveProcurementElements();
                    if (body) body.innerHTML = '';
                    empty?.classList.add('hidden');
                    wrap?.classList.remove('hidden');
                    if (empty) {
                        empty.textContent = 'ยังไม่มีเอกสารจัดซื้อจัดจ้างของรายการนี้';
                    }
                    card?.classList.add('hidden');
                };
                const resetExecutiveFinanceDocsCard = () => {
                    const { card, empty, wrap, body } = getExecutiveFinanceDocsElements();
                    if (body) body.innerHTML = '';
                    empty?.classList.add('hidden');
                    wrap?.classList.remove('hidden');
                    if (empty) {
                        empty.textContent = 'ยังไม่มีเอกสารลงนาม e-Sign ของรายการนี้';
                    }
                    const note = document.getElementById('executive-finance-docs-note');
                    if (note) {
                        note.textContent = '';
                        note.classList.add('hidden');
                    }
                    card?.classList.add('hidden');
                };
                const executiveFinanceDocHeading = (file) => String(file?.item_name || '').trim();
                const renderExecutiveFinanceDocsHtml = (files = []) => files.map((file) => {
                    const title = executiveFinanceDocHeading(file);
                    const url = String(file.url || '').trim();
                    const distinctHeadings = files
                        .map((row) => executiveFinanceDocHeading(row))
                        .filter((name, index, all) => name !== '' && all.indexOf(name) === index);
                    const showFileHeading = distinctHeadings.length > 1 && title !== '';
                    const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            ${showFileHeading ? `<div class="text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>` : ''}
                        </div>
                        ${url !== '' ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 shadow-sm transition hover:bg-violet-100">เปิดไฟล์</a>` : ''}
                    </div>`;
                    if (url === '') {
                        return `<div class="rounded-xl border border-dashed border-violet-200 bg-white px-3 py-3 text-sm text-slate-500">${caption}<div>ยังไม่มีไฟล์</div></div>`;
                    }
                    return renderOnDemandPdfBlock(title, url, caption, 'violet');
                }).join('');
                const renderExecutiveFinanceDocsList = (files = []) => {
                    const { card, empty, wrap, body } = getExecutiveFinanceDocsElements();
                    if (!card || !body) return;
                    const rows = Array.isArray(files) ? files.filter((file) => String(file?.url || '').trim() !== '' || String(file?.name || '').trim() !== '') : [];
                    body.innerHTML = '';
                    if (rows.length === 0) {
                        resetExecutiveFinanceDocsCard();
                        return;
                    }
                    empty?.classList.add('hidden');
                    wrap?.classList.remove('hidden');
                    card.classList.remove('hidden');
                    const note = document.getElementById('executive-finance-docs-note');
                    const headings = rows
                        .map((file) => executiveFinanceDocHeading(file))
                        .filter((name, index, all) => name !== '' && all.indexOf(name) === index);
                    if (note) {
                        note.textContent = headings.join(' · ');
                        note.classList.toggle('hidden', headings.length === 0);
                    }
                    body.innerHTML = renderExecutiveFinanceDocsHtml(rows);
                    stripPdfIframesForCompactView(card);
                };
                const renderExecutiveProcurementList = async (withdrawId, preloadedRows = null) => {
                    const { card, empty, wrap, body } = getExecutiveProcurementElements();
                    if (!card || !body) return [];
                    if (Number(withdrawId || 0) <= 0) {
                        resetExecutiveProcurementCard();
                        return [];
                    }
                    body.innerHTML = '';
                    empty?.classList.add('hidden');
                    wrap?.classList.remove('hidden');
                    card.classList.remove('hidden');
                    try {
                        const rows = preloadedRows !== null
                            ? (Array.isArray(preloadedRows) ? preloadedRows : [])
                            : await fetchExecutiveProcurementRows(withdrawId);
                        if (rows.length === 0) {
                            resetExecutiveProcurementCard();
                            return [];
                        }
                        body.innerHTML = renderExecutiveProcurementTableRowsHtml(rows);
                        stripPdfIframesForCompactView(card);
                        return rows;
                    } catch (error) {
                        resetExecutiveProcurementCard();
                        return [];
                    }
                };
                const resolveExecutiveBudgetSummaryRows = (cloned, budgetBreakdown = null, detailFallback = null) => {
                    const fromApi = Array.isArray(budgetBreakdown) ? budgetBreakdown : [];
                    if (fromApi.length > 0) {
                        return fromApi
                            .map((entry) => ({
                                budgetId: String(entry?.budget_manage_id ?? entry?.id ?? '').trim(),
                                code: String(entry?.budget_manage_code ?? entry?.code ?? '').trim(),
                                amount: toNumber(entry?.amount ?? 0),
                            }))
                            .filter((item) => Number(item.budgetId || 0) > 0 && item.amount > 0);
                    }

                    const detailItems = Array.isArray(detailFallback?.items) ? detailFallback.items : [];
                    if (detailItems.length > 0) {
                        const itemBudgetMap = new Map();
                        detailItems.forEach((item) => {
                            const budgetId = String(item?.budget_manage_id ?? '').trim();
                            const amount = toNumber(item?.finance_amount) > 0
                                ? toNumber(item?.finance_amount)
                                : toNumber(item?.amount || 0);
                            if (Number(budgetId || 0) <= 0 || amount <= 0) return;
                            const prev = itemBudgetMap.get(budgetId) || { budgetId, code: '', amount: 0 };
                            prev.amount += amount;
                            const code = String(item?.budget_manage_code || '').trim();
                            if (code !== '' && prev.code === '') prev.code = code;
                            itemBudgetMap.set(budgetId, prev);
                        });
                        if (itemBudgetMap.size > 0) {
                            return Array.from(itemBudgetMap.values());
                        }
                    }

                    const fromItems = Array.from(cloned.querySelectorAll('table tbody tr[data-budget-manage-id]'))
                        .map((row) => {
                            const budgetId = String(row?.dataset?.budgetManageId || '').trim();
                            const amountText = String(row.querySelector('td:last-child')?.textContent || '').replace(/,/g, '').trim();
                            const code = String(row?.dataset?.budgetManageCode || '').trim();
                            return { budgetId, code, amount: toNumber(amountText) };
                        })
                        .filter((item) => Number(item.budgetId || 0) > 0 && item.amount > 0);
                    if (fromItems.length > 0) {
                        return fromItems;
                    }

                    const budgetManageId = Number(detailFallback?.budget_manage_id || 0);
                    const totalAmount = toNumber(detailFallback?.total_amount || 0);
                    if (budgetManageId > 0 && totalAmount > 0) {
                        const headerCode = String(detailFallback?.budget_manage_code || detailFallback?.project_code || '').trim();
                        return [{ budgetId: String(budgetManageId), code: headerCode, amount: totalAmount }];
                    }

                    return [];
                };
                const appendExecutiveBudgetSummaryBlock = (cloned, withdrawKind = 'general', budgetBreakdown = null, detailFallback = null, mountTarget = null) => {
                    const paragraph3El = queryDetailIn(cloned, 'paragraph3', withdrawKind);
                    const summaryRows = resolveExecutiveBudgetSummaryRows(cloned, budgetBreakdown, detailFallback);
                    const budgetSummaryMap = new Map();
                    summaryRows.forEach((item) => {
                        const key = item.budgetId;
                        const prev = budgetSummaryMap.get(key) || { budgetId: key, code: '', amount: 0 };
                        const nextCode = String(item?.code || prev.code || '').trim();
                        budgetSummaryMap.set(key, {
                            budgetId: key,
                            code: nextCode,
                            amount: toNumber(prev.amount || 0) + toNumber(item.amount || 0),
                        });
                    });
                    const paragraph3Row = paragraph3El?.closest('.grid.grid-cols-\\[76px_1fr\\].items-start.gap-2');
                    const paragraph3Section = paragraph3Row?.closest('.p-1');
                    const host = mountTarget || paragraph3Section;
                    host?.querySelector('[data-budget-summary-block]')?.remove();
                    if (!host) return;
                    const summaryBlock = document.createElement('div');
                    summaryBlock.className = 'mt-2 space-y-3';
                    summaryBlock.setAttribute('data-budget-summary-block', '1');
                    const summaryList = Array.from(budgetSummaryMap.values());
                    const classifyBudgetSourceLabel = (rawName = '') => {
                        const name = String(rawName || '').trim();
                        if (name === '') return 'ไม่ระบุแหล่งงบ';
                        if (name.includes('แผ่นดิน')) return 'งบแผ่นดิน';
                        if (name.includes('รายได้')) return 'งบรายได้';
                        return name;
                    };
                    if (summaryList.length === 0) {
                        summaryBlock.innerHTML = `<div class="mt-2 overflow-x-auto max-lg:hidden">
                                <table class="w-full table-fixed border-collapse border border-slate-300 text-sm">
                                    <thead>
                                        <tr class="bg-white">
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">รหัสงบประมาณ</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">งบประมาณ</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติแล้ว</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติครั้งนี้</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">รวมอนุมัติ</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">คงเหลือ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="6">- ยังไม่ได้เลือกงบประมาณ -</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>`;
                    } else {
                        const rowsHtml = summaryList.map((item) => {
                            const budgetOption = budgetSourceOptionMap.get(item.budgetId) || {};
                            const budgetAmount = toNumber(budgetOption?.amount || 0);
                            const approvedAlready = toNumber(budgetOption?.approvedAmount || 0);
                            const approvedThisTime = toNumber(item.amount || 0);
                            const totalApproved = approvedAlready + approvedThisTime;
                            const remaining = budgetAmount - totalApproved;
                            const budgetCode = escapeHtml(String(budgetOption?.code || item.code || '').trim() || '-');
                            const sourceLabel = escapeHtml(classifyBudgetSourceLabel(budgetOption.sourceName || budgetOption.sourcePath || ''));
                            return `<tr>
                                    <td class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">${budgetCode}<div class="mt-0.5 text-[11px] font-medium text-slate-500">${sourceLabel}</div></td>
                                    <td class="border border-slate-300 px-2 py-1 text-right tabular-nums">${formatMoney(budgetAmount)}</td>
                                    <td class="border border-slate-300 px-2 py-1 text-right tabular-nums">${formatMoney(approvedAlready)}</td>
                                    <td class="border border-slate-300 px-2 py-1 text-right tabular-nums">${formatMoney(approvedThisTime)}</td>
                                    <td class="border border-slate-300 px-2 py-1 text-right tabular-nums">${formatMoney(totalApproved)}</td>
                                    <td class="border border-slate-300 px-2 py-1 text-right tabular-nums ${remaining < 0 ? 'text-rose-600 font-semibold' : ''}">${formatMoney(remaining)}</td>
                                </tr>`;
                        }).join('');
                        const mobileCardsHtml = summaryList.map((item) => {
                            const budgetOption = budgetSourceOptionMap.get(item.budgetId) || {};
                            const budgetAmount = toNumber(budgetOption?.amount || 0);
                            const approvedAlready = toNumber(budgetOption?.approvedAmount || 0);
                            const approvedThisTime = toNumber(item.amount || 0);
                            const totalApproved = approvedAlready + approvedThisTime;
                            const remaining = budgetAmount - totalApproved;
                            const budgetCode = escapeHtml(String(budgetOption?.code || item.code || '').trim() || '-');
                            const sourceLabel = escapeHtml(classifyBudgetSourceLabel(budgetOption.sourceName || budgetOption.sourcePath || ''));
                            return `<div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="text-xs font-semibold text-teal-700">${sourceLabel}</div>
                                <div class="mt-0.5 text-sm font-bold text-slate-800 break-words">${budgetCode}</div>
                                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-600">
                                    <div>งบประมาณ<br><span class="font-semibold tabular-nums text-slate-800">${formatMoney(budgetAmount)}</span></div>
                                    <div>อนุมัติแล้ว<br><span class="font-semibold tabular-nums text-slate-800">${formatMoney(approvedAlready)}</span></div>
                                    <div>ครั้งนี้<br><span class="font-semibold tabular-nums text-emerald-700">${formatMoney(approvedThisTime)}</span></div>
                                    <div>คงเหลือ<br><span class="font-semibold tabular-nums ${remaining < 0 ? 'text-rose-600' : 'text-slate-800'}">${formatMoney(remaining)}</span></div>
                                </div>
                            </div>`;
                        }).join('');
                        summaryBlock.innerHTML = `<div class="space-y-2 lg:hidden">${mobileCardsHtml}</div>
                            <div class="mt-2 overflow-x-auto max-lg:hidden">
                                <table class="w-full table-fixed border-collapse border border-slate-300 text-sm">
                                    <thead>
                                        <tr class="bg-white">
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">รหัสงบประมาณ</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">งบประมาณ</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติแล้ว</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติครั้งนี้</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">รวมอนุมัติ</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">คงเหลือ</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rowsHtml}</tbody>
                                </table>
                            </div>`;
                    }
                    host.appendChild(summaryBlock);
                };
                const guessFilePreviewKind = (file = {}) => {
                    const mime = String(file?.mime || '').trim().toLowerCase();
                    const name = String(file?.name || '').trim().toLowerCase();
                    const url = String(file?.url || '').trim().toLowerCase();
                    const haystack = `${mime} ${name} ${url}`;
                    if (mime.includes('pdf') || /\.pdf(\?|$)/.test(haystack)) return 'pdf';
                    if (mime.startsWith('image/') || /\.(png|jpe?g|gif|webp|bmp|svg)(\?|$)/.test(haystack)) return 'image';
                    if (
                        mime.includes('spreadsheet')
                        || mime.includes('excel')
                        || mime === 'text/csv'
                        || /\.(xlsx|xls|csv)(\?|$)/.test(haystack)
                    ) return 'spreadsheet';
                    if (
                        mime.includes('word')
                        || mime.includes('msword')
                        || /\.(docx|doc)(\?|$)/.test(haystack)
                    ) return 'word';
                    return 'other';
                };
                const withFileDownloadParam = (url) => {
                    const source = String(url || '').trim();
                    if (source === '') return '';
                    try {
                        const parsed = new URL(source, window.location.origin);
                        parsed.searchParams.set('download', '1');
                        return parsed.toString();
                    } catch (_error) {
                        return source + (source.includes('?') ? '&' : '?') + 'download=1';
                    }
                };
                const renderInlineFilePreview = (file = {}, label = '') => {
                    const url = String(file?.url || '').trim();
                    const name = String(file?.name || '').trim() || 'เอกสาร';
                    const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-slate-800">${escapeHtml(label)}${name !== '' ? ` <span class="font-normal text-slate-500">(${escapeHtml(name)})</span>` : ''}</div>
                        ${url !== '' ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100">เปิดไฟล์</a>` : ''}
                    </div>`;
                    if (url === '') {
                        return `<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">${escapeHtml(label)} — ยังไม่มีไฟล์</div>`;
                    }
                    const kind = guessFilePreviewKind(file);
                    let sameOrigin = false;
                    try {
                        sameOrigin = new URL(url, window.location.origin).origin === window.location.origin;
                    } catch (_error) {
                        sameOrigin = false;
                    }
                    const canEmbed = file?.embed !== false && sameOrigin;
                    if (kind === 'image') {
                        return `<div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-2">${caption}<img src="${escapeHtml(url)}" alt="${escapeHtml(name)}" class="max-h-[70vh] w-full rounded-lg bg-slate-50 object-contain" /></div>`;
                    }
                    if (kind === 'pdf' && canEmbed) {
                        const downloadUrl = withFileDownloadParam(url);
                        const captionWithDownload = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div class="text-sm font-semibold text-slate-800">${escapeHtml(label)}${name !== '' ? ` <span class="font-normal text-slate-500">(${escapeHtml(name)})</span>` : ''}</div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100">เปิดไฟล์</a>
                                <a href="${escapeHtml(downloadUrl)}" download="${escapeHtml(name)}" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">ดาวน์โหลด</a>
                            </div>
                        </div>`;
                        return renderOnDemandPdfBlock(name || label, url, captionWithDownload, 'sky');
                    }
                    const downloadUrl = withFileDownloadParam(url);
                    const isSpreadsheet = kind === 'spreadsheet';
                    const hint = isSpreadsheet
                        ? 'ไฟล์ Excel ไม่สามารถแสดงในหน้าเว็บได้ กรุณาเปิดหรือดาวน์โหลดเพื่อดู'
                        : (kind === 'pdf'
                            ? 'เปิดไฟล์ในแท็บใหม่เพื่อดูเอกสาร'
                            : 'ไฟล์ประเภทนี้ไม่สามารถแสดงในหน้าเว็บได้ กรุณาเปิดหรือดาวน์โหลดเพื่อดู');
                    const icon = isSpreadsheet ? 'fa-file-excel' : (kind === 'pdf' ? 'fa-file-pdf' : (kind === 'word' ? 'fa-file-word' : 'fa-file'));
                    const iconWrap = isSpreadsheet
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-100'
                        : (kind === 'word' ? 'bg-sky-50 text-sky-700 ring-sky-100' : 'bg-slate-100 text-slate-600 ring-slate-200');
                    return `<div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3">
                        <div class="mb-2 text-sm font-semibold text-slate-800">${escapeHtml(label)}${name !== '' ? ` <span class="font-normal text-slate-500">(${escapeHtml(name)})</span>` : ''}</div>
                        <div class="flex flex-col gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1 ${iconWrap}"><i class="fas ${icon}"></i></span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800">${escapeHtml(name)}</div>
                                    <div class="text-xs text-slate-500">${escapeHtml(hint)}</div>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-xs font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100">เปิดไฟล์</a>
                                <a href="${escapeHtml(downloadUrl)}" download="${escapeHtml(name)}" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">ดาวน์โหลด</a>
                            </div>
                        </div>
                    </div>`;
                };
                const renderApprovalDocumentsPreviewHtml = (detail = {}) => {
                    const docs = Array.isArray(detail.documents) ? detail.documents : [];
                    const uploadedFiles = Array.isArray(detail.uploaded_files) ? detail.uploaded_files : [];
                    const uploadedMap = new Map();
                    uploadedFiles.forEach((file) => {
                        const key = `${String(file.type || '').trim()}::${String(file.item || '').trim()}`;
                        if (!uploadedMap.has(key)) uploadedMap.set(key, []);
                        uploadedMap.get(key).push(file);
                    });
                    const blocks = [];
                    const travelFiles = (Array.isArray(detail.travel_supporting_files) ? detail.travel_supporting_files : [])
                        .filter((file) => String(file?.url || '').trim() !== '');
                    let labelNumber = 1;
                    if (docs.length > 0) {
                        docs.forEach((doc) => {
                            const label = `${labelNumber}) ${String(doc.label || '-').trim() || '-'}`;
                            labelNumber += 1;
                            const files = uploadedMap.get(`${String(doc.type || '').trim()}::${String(doc.item_id || '').trim()}`) || [];
                            if (files.length === 0) {
                                blocks.push(`<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">${escapeHtml(label)} — ยังไม่มีไฟล์</div>`);
                                return;
                            }
                            files.forEach((file) => blocks.push(renderInlineFilePreview(file, label)));
                        });
                    } else {
                        uploadedFiles.forEach((file) => {
                            blocks.push(renderInlineFilePreview(file, `${labelNumber})`));
                            labelNumber += 1;
                        });
                    }
                    travelFiles.forEach((file) => {
                        const label = `${labelNumber}) ${String(file?.label || file?.name || 'เอกสารจากระบบขออนุมัติไปปฏิบัติงาน').trim() || 'เอกสารจากระบบขออนุมัติไปปฏิบัติงาน'}`;
                        labelNumber += 1;
                        blocks.push(renderInlineFilePreview(file, label));
                    });
                    if (blocks.length === 0) {
                        return '<div class="text-slate-400">- ไม่มีเอกสารประกอบ -</div>';
                    }
                    return `<div class="space-y-3">${blocks.join('')}</div>`;
                };
                const emphasizeApprovalSectionHeadings = (cloned) => {
                    const titles = new Set(['รายการเบิกจ่าย', 'เอกสารประกอบการเบิกจ่าย']);
                    cloned.querySelectorAll('div').forEach((el) => {
                        const text = String(el.textContent || '').trim();
                        if (!titles.has(text) || el.children.length > 0) return;
                        el.classList.remove('mb-1', 'mb-2', 'text-xs', 'text-slate-700');
                        el.classList.add('mb-3', 'text-base', 'font-bold', 'tracking-tight', 'text-slate-900', 'sm:text-lg');
                    });
                };
                const enhanceApprovalDocumentPreviews = (cloned, withdrawKind, detail = {}, options = {}) => {
                    const documentsEl = queryDetailIn(cloned, 'documents', withdrawKind);
                    if (!documentsEl) return;
                    const projectDocsLast = options.projectDocsLast === true;
                    const hasProjectPdfs = String(detail?.project_approve_memo_pdf?.url || '').trim() !== ''
                        || String(detail?.project_print_pdf?.url || '').trim() !== '';
                    const hasTravelFiles = Array.isArray(detail?.travel_supporting_files)
                        && detail.travel_supporting_files.some((file) => String(file?.url || '').trim() !== '');
                    const hasPayload = (Array.isArray(detail?.documents) && detail.documents.length > 0)
                        || (Array.isArray(detail?.uploaded_files) && detail.uploaded_files.length > 0)
                        || hasProjectPdfs
                        || hasTravelFiles;
                    if (!hasPayload) return;
                    if (window.WithdrawDocumentPreview?.fill) {
                        window.WithdrawDocumentPreview.fill(documentsEl, detail, projectDocsLast ? { projectDocsLast: true } : {});
                        convertEmbeddedPdfsToOnDemand(documentsEl);
                        return;
                    }
                    documentsEl.classList.remove('space-y-1');
                    documentsEl.classList.add('space-y-3');
                    documentsEl.innerHTML = renderApprovalDocumentsPreviewHtml(detail);
                };
                const enhanceExecutiveMobileItemTables = (root) => {
                    if (!root) return;
                    root.querySelectorAll('table.wd-table').forEach((table) => {
                        const wrap = table.closest('.wd-table-wrap, .overflow-x-auto') || table.parentElement;
                        if (!wrap) return;
                        wrap.querySelectorAll('[data-executive-mobile-items]').forEach((node) => node.remove());
                        table.dataset.executiveMobileEnhanced = '0';
                        const rows = Array.from(table.querySelectorAll('tbody tr'));
                        const cards = [];
                        rows.forEach((row) => {
                            const cells = Array.from(row.querySelectorAll('td'));
                            if (cells.length < 2) return;
                            const label = String(cells[1]?.textContent || '').trim();
                            const amount = String(cells[cells.length - 1]?.textContent || '').trim();
                            if (label === '' && amount === '') return;
                            const isCategory = row.classList.contains('bg-slate-50/60') || row.classList.contains('font-semibold');
                            cards.push(`<div class="rounded-xl border ${isCategory ? 'border-slate-300 bg-slate-50' : 'border-slate-200 bg-white'} px-3 py-2.5 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 text-sm ${isCategory ? 'font-bold text-slate-800' : 'font-medium text-slate-700'} break-words">${escapeHtml(label || '-')}</div>
                                    <div class="shrink-0 text-sm font-bold tabular-nums text-slate-900">${escapeHtml(amount || '-')}</div>
                                </div>
                            </div>`);
                        });
                        const footAmount = String(table.querySelector('tfoot #withdraw-detail-items-total, tfoot [id$="-detail-items-total"], tfoot td.text-right')?.textContent || '').trim();
                        const footText = String(table.querySelector('tfoot #withdraw-detail-items-total-text, tfoot [id$="-detail-items-total-text"]')?.textContent || '').trim();
                        if (cards.length === 0) return;
                        table.dataset.executiveMobileEnhanced = '1';
                        table.classList.add('max-lg:hidden');
                        const mobile = document.createElement('div');
                        mobile.className = 'space-y-2 lg:hidden';
                        mobile.setAttribute('data-executive-mobile-items', '1');
                        mobile.innerHTML = `${cards.join('')}
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-bold text-emerald-900">รวม</span>
                                    <span class="text-base font-bold tabular-nums text-emerald-800">${escapeHtml(footAmount || '-')}</span>
                                </div>
                                ${footText !== '' ? `<div class="mt-1 text-xs font-semibold text-emerald-800">(${escapeHtml(footText)})</div>` : ''}
                            </div>`;
                        wrap.appendChild(mobile);
                    });
                };
                const fillExecutiveApprovalSummary = async (row = {}, detail = {}) => {
                    const code = resolveWithdrawDocumentDisplayCode(detail)
                        || String(detail?.document_number_display || detail?.document_number || row?.code || '-').trim()
                        || '-';
                    const dateRaw = String(detail?.document_date || row?.date || '').trim();
                    const dateText = dateRaw !== '' ? (formatThaiDate(dateRaw) || dateRaw) : '-';
                    const subject = String(detail?.subject || row?.subject || '-').trim() || '-';
                    const applicant = String(detail?.applicant_name || row?.manager || row?.applicant_name || '-').trim() || '-';
                    let department = String(detail?.department_display || detail?.department_name || row?.department_display || row?.department_name || '').trim();
                    if (department === '' || department === '-') {
                        const departmentCode = String(detail?.department_code || row?.department_code || '').trim();
                        if (departmentCode !== '') {
                            const departmentMap = await loadDepartmentByCodeMap();
                            const mapped = departmentMap.get(departmentCode) || null;
                            const unitName = String(mapped?.unitName || '').trim();
                            const jobName = String(mapped?.jobName || '').trim();
                            const depName = String(mapped?.depName || '').trim();
                            const jobOrDepartment = jobName || depName;
                            department = unitName !== '' && jobOrDepartment !== ''
                                ? `${unitName}  ${jobOrDepartment}`
                                : (unitName || jobOrDepartment);
                        }
                    }
                    if (department === '') department = '-';
                    const amount = formatMoney(detail?.total_amount ?? row?.amount ?? 0);
                    const setText = (id, value) => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = value;
                    };
                    setText('executive-summary-code', code);
                    setText('executive-summary-date', dateText);
                    setText('executive-summary-subject', subject);
                    setText('executive-summary-applicant', applicant);
                    setText('executive-summary-department', department);
                    setText('executive-summary-amount', amount);
                };
                const setExecutiveDetailLoading = (isLoading) => {
                    document.getElementById('executive-approval-detail-loading')?.classList.toggle('hidden', !isLoading);
                    document.getElementById('executive-approval-loaded-content')?.classList.toggle('hidden', isLoading);
                };
                const resolveFinanceDepartmentLabel = async () => {
                    const fixedDepartmentCode = '12010401';
                    const departmentMap = await loadDepartmentByCodeMap();
                    const mappedDepartment = departmentMap.get(fixedDepartmentCode) || null;
                    const unitName = String(mappedDepartment?.unitName || '').trim();
                    const jobName = String(mappedDepartment?.jobName || '').trim();
                    const depName = String(mappedDepartment?.depName || '').trim();
                    const displayName = unitName !== '' && jobName !== ''
                        ? `${unitName}  ${jobName}`
                        : (unitName || jobName || depName);
                    return displayName !== '' ? displayName : 'งานการเงิน';
                };
                const resetExecutiveFinanceMemoCard = () => {
                    const card = document.getElementById('executive-finance-memo-card');
                    const body = document.getElementById('executive-finance-memo-body');
                    if (body) body.innerHTML = '';
                    card?.classList.add('hidden');
                };
                const renderIndentedMemoParagraphHtml = (text) => {
                    const raw = String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim();
                    if (raw === '' || raw === '-') return '';
                    const collapsed = raw
                        .replace(/[ \t\u00A0\u200B-\u200D\uFEFF]+/g, ' ')
                        .replace(/(?<!\n)\n(?!\n)/g, ' ');
                    const parts = collapsed.split(/\n{2,}/).map((part) => part.trim()).filter(Boolean);
                    const blocks = parts.length > 0 ? parts : [raw];
                    return blocks.map((part) => (
                        `<p class="mb-2 text-justify leading-relaxed last:mb-0" style="text-indent:20mm">${escapeHtml(part)}</p>`
                    )).join('');
                };
                const fillIndentedMemoParagraph = (el, text) => {
                    if (!el) return;
                    const field = el.closest('.wd-field') || el.closest('.grid');
                    const spacer = field?.querySelector('.wd-label-spacer') || field?.firstElementChild;
                    if (field?.classList.contains('grid')) {
                        field.classList.remove('grid', 'grid-cols-[76px_1fr]', 'gap-2', 'items-start');
                    }
                    if (spacer && spacer !== el) spacer.classList.add('hidden');
                    el.classList.remove('whitespace-pre-wrap');
                    const html = renderIndentedMemoParagraphHtml(text);
                    if (html === '') {
                        el.textContent = '-';
                        return;
                    }
                    el.innerHTML = html;
                };
                const appendProjectFinanceMemo = async (detail = null) => {
                    const card = document.getElementById('executive-finance-memo-card');
                    const body = document.getElementById('executive-finance-memo-body');
                    if (!card || !body || !detail) {
                        resetExecutiveFinanceMemoCard();
                        return;
                    }

                    await loadExpenseChartOptions('project');
                    const departmentLabel = await resolveFinanceDepartmentLabel();
                    const financeDocNo = String(detail?.finance_document_number_display || detail?.finance_document_number || '').trim();
                    const financeDocDate = String(detail?.finance_document_date || '').trim();
                    const financeSubject = String(detail?.finance_subject || '').trim();
                    const financeTo = String(detail?.finance_to || '').trim();
                    const financeP1 = String(detail?.finance_paragraph1 || '').trim();
                    const financeP2 = String(detail?.finance_paragraph2 || '').trim();
                    const financeP3 = String(detail?.finance_paragraph3 || '').trim();
                    const items = Array.isArray(detail?.items) ? detail.items : [];
                    const itemRows = items.map((item, index) => {
                        const chartLabel = resolveFinanceSelectedItemLabel(item);
                        const name = chartLabel !== '' ? chartLabel : (String(item?.name || '').trim() || '-');
                        const amount = resolveDisplayedFinanceAmount(item);
                        return `<tr>
                            <td class="border border-slate-300 px-2 py-1 text-center">${index + 1}</td>
                            <td class="border border-slate-300 px-2 py-1">${escapeHtml(name)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(amount)}</td>
                        </tr>`;
                    }).join('');
                    const totalAmount = items.reduce((sum, item) => sum + resolveDisplayedFinanceAmount(item), 0);
                    const itemsHtml = items.length === 0
                        ? ''
                        : `<div class="py-2">
                            <div class="overflow-x-auto">
                                <table class="wd-table min-w-[640px] w-full border-collapse border border-slate-300 text-sm">
                                    <thead>
                                        <tr class="bg-slate-50">
                                            <th class="border border-slate-300 px-2 py-1 text-center">ที่</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center">รายการ</th>
                                            <th class="border border-slate-300 px-2 py-1 text-center">จำนวนเงิน (บาท)</th>
                                        </tr>
                                    </thead>
                                    <tbody>${itemRows}</tbody>
                                    <tfoot>
                                        <tr class="font-semibold">
                                            <td class="border border-slate-300 px-2 py-1 text-center" colspan="2">รวม</td>
                                            <td class="border border-slate-300 px-2 py-1 text-right">
                                                <div>${formatMoney(totalAmount)}</div>
                                                <div class="mt-0.5 text-xs font-bold leading-relaxed text-slate-700">(${escapeHtml(toThaiBahtText(totalAmount))})</div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>`;
                    const paragraphHtml = (text) => `<div class="py-1">${renderIndentedMemoParagraphHtml(text) || '<div class="px-1 py-1">-</div>'}</div>`;
                    body.innerHTML = `
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <div class="w-[76px] shrink-0 font-semibold">ส่วนงาน</div>
                                <div class="min-w-0 flex-1 px-1 py-1">${escapeHtml(departmentLabel)}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <div class="w-[76px] shrink-0 font-semibold">ที่</div>
                                    <div class="min-w-0 flex-1 px-1 py-1">${escapeHtml(financeDocNo !== '' ? financeDocNo : '-')}</div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <div class="w-[44px] shrink-0 text-right font-semibold">วันที่</div>
                                    <div class="w-[180px] shrink-0 px-1 py-1">${escapeHtml(financeDocDate !== '' ? formatThaiDate(financeDocDate) : '-')}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-[76px_1fr] items-center gap-2">
                                <div class="font-semibold">เรื่อง</div>
                                <div class="px-1 py-1">${escapeHtml(financeSubject !== '' ? financeSubject : '-')}</div>
                            </div>
                            <div class="grid grid-cols-[76px_1fr] items-center gap-2">
                                <div class="font-semibold">เรียน</div>
                                <div class="px-1 py-1">${escapeHtml(financeTo !== '' ? financeTo : '-')}</div>
                            </div>
                            ${paragraphHtml(financeP1)}
                            ${itemsHtml}
                            ${paragraphHtml(financeP2)}
                            ${paragraphHtml(financeP3)}
                        </div>
                    `;
                    card.classList.remove('hidden');
                };
                const appendFinanceMemoProposer = (detail = null) => {
                    const body = document.getElementById('executive-finance-memo-body');
                    if (!body) return;
                    body.querySelector('[data-finance-memo-proposer]')?.remove();
                    const steps = Array.isArray(detail?.approval_steps) ? detail.approval_steps : [];
                    const financeOfficer = steps.find((step) => String(step?.step_code || '').trim() === 'finance_officer') || null;
                    const name = String(detail?.finance_proposer_name || financeOfficer?.staff_name || '').trim();
                    const position = String(detail?.finance_proposer_position || '').trim();
                    const block = document.createElement('div');
                    block.className = 'mt-4 text-center text-sm leading-relaxed text-slate-800';
                    block.setAttribute('data-finance-memo-proposer', '1');
                    block.innerHTML = `
                        <div class="font-semibold">ผู้เสนอเรื่อง</div>
                        <div>${escapeHtml(name !== '' ? name : '-')}</div>
                        ${position !== '' ? `<div class="text-slate-600">${escapeHtml(position)}</div>` : ''}
                    `;
                    body.appendChild(block);
                };
                const resetDepartmentMemoToggle = () => {
                    const wrap = document.getElementById('executive-department-memo-toggle-wrap');
                    const card = document.getElementById('executive-department-memo-panel');
                    wrap?.classList.add('hidden');
                    card?.classList.remove('hidden');
                };
                const enableDepartmentMemoToggle = () => {
                    const wrap = document.getElementById('executive-department-memo-toggle-wrap');
                    const button = document.getElementById('executive-department-memo-toggle');
                    const card = document.getElementById('executive-department-memo-panel');
                    if (!wrap || !button || !card) return;
                    const hint = document.getElementById('executive-department-memo-toggle-hint');
                    const action = document.getElementById('executive-department-memo-toggle-action');
                    const syncLabel = (collapsed) => {
                        card.classList.toggle('hidden', collapsed);
                        if (hint) {
                            hint.textContent = collapsed
                                ? 'ส่วนนี้ถูกซ่อนอยู่ กดแถบนี้เพื่อแสดง'
                                : 'กดเพื่อซ่อนทั้งการ์ด';
                        }
                        if (action) {
                            action.innerHTML = collapsed
                                ? '<i class="fas fa-chevron-down text-[10px]"></i> แสดง'
                                : '<i class="fas fa-chevron-up text-[10px]"></i> ซ่อน';
                        }
                        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    };
                    if (button.dataset.bound !== '1') {
                        button.dataset.bound = '1';
                        button.addEventListener('click', () => {
                            syncLabel(!card.classList.contains('hidden'));
                        });
                    }
                    wrap.classList.remove('hidden');
                    syncLabel(true);
                };
                const clearExecutiveApprovalRoute = () => {
                    if (!executiveApprovalRouteSlot) return;
                    executiveApprovalRouteSlot.innerHTML = '';
                    executiveApprovalRouteSlot.classList.add('hidden');
                };
                const placeExecutiveApprovalRoute = (withdrawKind = 'general') => {
                    if (!executiveApprovalRouteSlot) return;
                    executiveApprovalRouteSlot.innerHTML = '';
                    const source = document.getElementById(detailElementId('approval-route-section', withdrawKind));
                    if (!source || String(source.textContent || '').trim() === '') {
                        executiveApprovalRouteSlot.classList.add('hidden');
                        return;
                    }
                    const copy = source.cloneNode(true);
                    copy.classList.remove('hidden', 'mt-3', 'pt-3');
                    copy.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
                    executiveApprovalRouteSlot.appendChild(copy);
                    executiveApprovalRouteSlot.classList.remove('hidden');
                };
                if (executiveApprovalRouteSlot && executiveApprovalRouteSlot.dataset.bound !== '1') {
                    executiveApprovalRouteSlot.dataset.bound = '1';
                    executiveApprovalRouteSlot.addEventListener('click', async (event) => {
                        const copyButton = event.target.closest('[data-general-copy-approval-link], [data-salary-copy-approval-link]');
                        if (!copyButton || !executiveApprovalRouteSlot.contains(copyButton)) return;
                        event.preventDefault();
                        const link = String(copyButton.dataset.link || '').trim();
                        if (link === '') return;
                        try {
                            if (navigator.clipboard?.writeText) {
                                await navigator.clipboard.writeText(link);
                            } else {
                                const helper = document.createElement('textarea');
                                helper.value = link;
                                document.body.appendChild(helper);
                                helper.select();
                                document.execCommand('copy');
                                helper.remove();
                            }
                            window.toast?.success?.('คัดลอกลิงก์แล้ว ส่งให้ผู้อนุมัติคนถัดไปได้เลย', { timer: 1800 });
                        } catch (error) {
                            window.toast?.error?.('ไม่สามารถคัดลอกลิงก์ได้', { timer: 1800 });
                        }
                    });
                }
                const renderExecutiveRevisionExplanation = (detailRow) => {
                    const card = document.getElementById('executive-revision-explanation-card');
                    const text = document.getElementById('executive-revision-explanation');
                    if (!card || !text) return;
                    const logs = Array.isArray(detailRow?.action_logs) ? detailRow.action_logs : [];
                    const matches = logs.filter((log) => String(log?.action_code || '').trim() === 'submit_revision_update');
                    const latest = matches.length > 0 ? matches[matches.length - 1] : null;
                    const explanation = String(latest?.comment || '').trim();
                    if (explanation === '') {
                        text.textContent = '-';
                        card.classList.add('hidden');
                        return;
                    }
                    text.textContent = explanation;
                    card.classList.remove('hidden');
                };
                const loadExecutiveApprovalDetailById = async (id, withdrawKind = 'general', listRow = null) => {
                    const executiveApprovalDetailContent = getExecutiveApprovalDetailContent();
                    if (!executiveApprovalDetailContent) return;
                    parkExecutiveProcurementCard();
                    executiveApprovalDetailContent.innerHTML = '';
                    clearExecutiveApprovalRoute();
                    const normalizedKind = normalizeWithdrawKind(withdrawKind);
                    const isSalaryWithdraw = normalizedKind === 'salary';
                    await openWithdrawDetailModal(id, withdrawKind, { openModal: false });
                    const detailRoot = getDetailRootForKind(withdrawKind);
                    if (!detailRoot) {
                        executiveApprovalDetailContent.innerHTML = '<div class="text-slate-400">- ไม่สามารถโหลดข้อมูลรายละเอียดได้ -</div>';
                        renderExecutiveRevisionExplanation(null);
                        resetExecutiveProcurementCard();
                        resetExecutiveFinanceDocsCard();
                        resetExecutiveFinanceMemoCard();
                        resetDepartmentMemoToggle();
                        return;
                    }
                    const cloned = detailRoot.cloneNode(true);
                    cloned.querySelectorAll('.overflow-x-auto').forEach((container) => {
                        container.style.overflowX = 'visible';
                        container.style.overflowY = 'visible';
                    });

                    if (isSalaryWithdraw) {
                        let salaryDetail = {};
                        let salaryBudgetBreakdown = [];
                        try {
                            const response = await fetch(`${detailApiBaseForKind(withdrawKind)}/${Number(id || 0)}`, { headers: { Accept: 'application/json' } });
                            const result = await response.json();
                            const detail = result?.data || {};
                            salaryDetail = detail;
                            salaryBudgetBreakdown = Array.isArray(detail?.budget_breakdown) ? detail.budget_breakdown : [];
                            const statusEl = cloned.querySelector('[id$="-detail-status"]') || queryDetailIn(cloned, 'status', withdrawKind);
                            if (statusEl) statusEl.textContent = String(detail?.status || statusEl.textContent || '-').trim();
                            const codeEl = queryDetailIn(cloned, 'code', withdrawKind);
                            const docCode = resolveWithdrawDocumentDisplayCode(detail);
                            if (codeEl && docCode !== '') codeEl.textContent = docCode;
                            const dateEl = queryDetailIn(cloned, 'date', withdrawKind);
                            const docDate = String(detail?.document_date || '').trim();
                            if (dateEl && docDate !== '') dateEl.textContent = formatThaiDate(docDate);
                            const subjectEl = queryDetailIn(cloned, 'subject', withdrawKind);
                            if (subjectEl) subjectEl.textContent = String(detail?.subject || '-').trim() || '-';
                            const toEl = queryDetailIn(cloned, 'to', withdrawKind);
                            if (toEl) toEl.textContent = String(detail?.to || '-').trim() || '-';
                            const p1El = queryDetailIn(cloned, 'paragraph1', withdrawKind);
                            const p2El = queryDetailIn(cloned, 'paragraph2', withdrawKind);
                            const p3El = queryDetailIn(cloned, 'paragraph3', withdrawKind);
                            fillIndentedMemoParagraph(p1El, detail?.paragraph1);
                            fillIndentedMemoParagraph(p2El, detail?.paragraph2);
                            fillIndentedMemoParagraph(p3El, detail?.paragraph3);
                        } catch (error) {}
                        queryDetailIn(cloned, 'approval-route-section', withdrawKind)?.remove();
                        queryDetailIn(cloned, 'approval-section', withdrawKind)?.remove();
                        applyFinanceSelectedItemLabels(cloned, Array.isArray(salaryDetail?.items) ? salaryDetail.items : []);
                        if (shouldShowExecutiveBudgetSummary(listRow, withdrawKind)) {
                            appendExecutiveBudgetSummaryBlock(cloned, withdrawKind, salaryBudgetBreakdown, salaryDetail);
                        } else {
                            removeExecutiveBudgetSummaryBlocks(cloned);
                        }
                        enhanceApprovalDocumentPreviews(cloned, withdrawKind, salaryDetail);
                        emphasizeApprovalSectionHeadings(cloned);
                        enhanceExecutiveMobileItemTables(cloned);
                        cloned.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
                        executiveApprovalDetailContent.appendChild(cloned);
                        stripPdfIframesForCompactView(executiveApprovalDetailContent);
                        convertEmbeddedPdfsToOnDemand(executiveApprovalDetailContent);
                        await fillExecutiveApprovalSummary(listRow || {}, salaryDetail);
                        renderExecutiveRevisionExplanation(null);
                        placeExecutiveApprovalRoute(withdrawKind);
                        resetExecutiveProcurementCard();
                        resetExecutiveFinanceMemoCard();
                        resetDepartmentMemoToggle();
                        renderExecutiveFinanceDocsList(Array.isArray(salaryDetail?.finance_esign_files) ? salaryDetail.finance_esign_files : []);
                        return;
                    }

                    let executiveDetail = {};
                    try {
                        const response = await fetch(`${detailApiBaseForKind(withdrawKind)}/${Number(id || 0)}`, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        executiveDetail = result?.data || {};
                    } catch (error) {
                        executiveDetail = {};
                    }

                    let isProjectWithdraw = isProjectWithdrawContext(withdrawKind, listRow, executiveDetail);

                    const departmentEl = queryDetailIn(cloned, 'department', withdrawKind);
                    if (!isProjectWithdraw && departmentEl) {
                        const fixedDepartmentCode = '12010401';
                        const departmentMap = await loadDepartmentByCodeMap();
                        const mappedDepartment = departmentMap.get(fixedDepartmentCode) || null;
                        const unitName = String(mappedDepartment?.unitName || '').trim();
                        const jobName = String(mappedDepartment?.jobName || '').trim();
                        const depName = String(mappedDepartment?.depName || '').trim();
                        const displayName = unitName !== '' && jobName !== ''
                            ? `${unitName}  ${jobName}`
                            : (unitName || jobName || depName);
                        departmentEl.textContent = displayName !== '' ? displayName : `หน่วยงานการเงิน code ${fixedDepartmentCode}`;
                    }

                    try {
                        const detail = executiveDetail;

                        const codeEl = queryDetailIn(cloned, 'code', withdrawKind);
                        const dateEl = queryDetailIn(cloned, 'date', withdrawKind);
                        const subjectEl = queryDetailIn(cloned, 'subject', withdrawKind);
                        const toEl = queryDetailIn(cloned, 'to', withdrawKind);
                        const p1El = queryDetailIn(cloned, 'paragraph1', withdrawKind);
                        const p2El = queryDetailIn(cloned, 'paragraph2', withdrawKind);
                        const p3El = queryDetailIn(cloned, 'paragraph3', withdrawKind);
                        const p4El = queryDetailIn(cloned, 'paragraph4', withdrawKind);
                        const setExecutiveMemoParagraph = (el, text) => {
                            if (!el) return;
                            const value = String(text || '').trim();
                            fillIndentedMemoParagraph(el, value);
                            const block = el.closest?.('[data-detail-memo-block="paragraph4"]');
                            if (block) {
                                block.classList.toggle('hidden', value === '');
                            }
                        };

                        if (isProjectWithdraw) {
                            const docCode = resolveWithdrawDocumentDisplayCode(detail);
                            if (codeEl && docCode !== '') codeEl.textContent = docCode;
                            const docDate = String(detail?.document_date || '').trim();
                            if (dateEl && docDate !== '') dateEl.textContent = formatThaiDate(docDate);
                            const subject = String(detail?.subject || '').trim();
                            if (subjectEl && subject !== '') subjectEl.textContent = subject;
                            const to = String(detail?.to || '').trim();
                            if (toEl && to !== '') toEl.textContent = to;
                            setExecutiveMemoParagraph(p1El, detail?.paragraph1);
                            setExecutiveMemoParagraph(p2El, detail?.paragraph2);
                            setExecutiveMemoParagraph(p3El, detail?.paragraph3);
                            setExecutiveMemoParagraph(p4El, detail?.paragraph4);
                        } else {
                            // ผู้บริหาร: ให้แสดง "บันทึกเสนอ งานการเงิน" ตามข้อมูลที่เก็บจริง
                            const financeDocNo = String(detail?.finance_document_number_display || detail?.finance_document_number || '').trim();
                            const financeDocDate = String(detail?.finance_document_date || '').trim();
                            const financeSubject = String(detail?.finance_subject || '').trim();
                            const financeTo = String(detail?.finance_to || '').trim();
                            const financeP1 = String(detail?.finance_paragraph1 || '').trim();
                            const financeP2 = String(detail?.finance_paragraph2 || '').trim();
                            const financeP3 = String(detail?.finance_paragraph3 || '').trim();

                            if (codeEl && financeDocNo !== '') codeEl.textContent = financeDocNo;
                            if (dateEl && financeDocDate !== '') dateEl.textContent = formatThaiDate(financeDocDate);
                            if (subjectEl && financeSubject !== '') subjectEl.textContent = financeSubject;
                            if (toEl && financeTo !== '') toEl.textContent = financeTo;
                            setExecutiveMemoParagraph(p1El, financeP1);
                            setExecutiveMemoParagraph(p2El, financeP2);
                            setExecutiveMemoParagraph(p3El, financeP3);
                            setExecutiveMemoParagraph(p4El, detail?.paragraph4 || '');

                            // ผู้บริหาร (ไม่ใช่โครงการ): ให้แสดง "รายการเบิกจ่าย" ก่อน paragraph2
                            const paragraph2Block = p2El?.closest?.('.grid.grid-cols-\\[76px_1fr\\].items-start.gap-2')
                                || p2El?.closest?.('.grid');
                            const itemsSection = queryDetailIn(cloned, 'items-body', withdrawKind)?.closest?.('.p-1') || null;
                            if (itemsSection && paragraph2Block && itemsSection.parentElement) {
                                itemsSection.insertAdjacentElement('afterend', paragraph2Block);
                            }

                            const approvalSteps = Array.isArray(detail?.approval_steps) ? detail.approval_steps : [];
                            const financeApprovedStep = approvalSteps.find((step) => String(step?.step_code || '').trim() === 'finance_officer' && String(step?.status || '').trim().toLowerCase() === 'approved')
                                || approvalSteps.find((step) => String(step?.step_code || '').trim() === 'finance_manager' && String(step?.status || '').trim().toLowerCase() === 'approved');
                            const financeApprovedAt = String(financeApprovedStep?.approved_at || '').trim();
                            if (dateEl && financeApprovedAt !== '') {
                                dateEl.textContent = formatThaiDate(financeApprovedAt);
                            }
                        }
                    } catch (error) {}
                    queryDetailIn(cloned, 'approval-route-section', withdrawKind)?.remove();
                    queryDetailIn(cloned, 'approval-section', withdrawKind)?.remove();
                    queryDetailIn(cloned, 'applicant', withdrawKind)?.closest('.text-center')?.remove();
                    queryDetailIn(cloned, 'reviewer', withdrawKind)?.closest('.text-center')?.remove();
                    const signerGrid = cloned.querySelector('.grid.grid-cols-1.gap-3.md\\:grid-cols-2');
                    if (signerGrid && signerGrid.children.length === 0) {
                        signerGrid.remove();
                    }
                    if (isProjectWithdraw) {
                        removeExecutiveWithdrawItemsSection(cloned);
                    } else {
                        // ผู้บริหาร: แสดงรายการเบิกจ่ายตามผังบัญชีที่งานการเงินเลือก
                        applyFinanceSelectedItemLabels(cloned, Array.isArray(executiveDetail?.items) ? executiveDetail.items : []);
                    }
                    const showProjectFinanceMemo = isProjectWithdraw && shouldShowExecutiveBudgetSummary(listRow, withdrawKind);
                    if (showProjectFinanceMemo) {
                        removeExecutiveBudgetSummaryBlocks(cloned);
                        ensureProjectBudgetSummaryVisible(cloned, executiveDetail);
                        await appendProjectFinanceMemo(executiveDetail);
                        appendExecutiveBudgetSummaryBlock(
                            cloned,
                            withdrawKind,
                            null,
                            executiveDetail,
                            document.getElementById('executive-finance-memo-body')
                        );
                        appendFinanceMemoProposer(executiveDetail);
                    } else if (shouldShowExecutiveBudgetSummary(listRow, withdrawKind)) {
                        appendExecutiveBudgetSummaryBlock(cloned, withdrawKind, null, executiveDetail);
                        resetExecutiveFinanceMemoCard();
                    } else {
                        removeExecutiveBudgetSummaryBlocks(cloned);
                        resetExecutiveFinanceMemoCard();
                        if (isProjectWithdraw) {
                            ensureProjectBudgetSummaryVisible(cloned, executiveDetail);
                        }
                    }
                    enhanceApprovalDocumentPreviews(cloned, withdrawKind, executiveDetail, {
                        projectDocsLast: isProjectWithdraw,
                    });
                    emphasizeApprovalSectionHeadings(cloned);
                    enhanceExecutiveMobileItemTables(cloned);
                    cloned.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
                    executiveApprovalDetailContent.innerHTML = '';
                    executiveApprovalDetailContent.appendChild(cloned);
                    if (isProjectWithdraw) {
                        placeExecutiveProcurementBeforeSupportingDocs(cloned);
                    }
                    if (showProjectFinanceMemo) {
                        enableDepartmentMemoToggle();
                    } else {
                        resetDepartmentMemoToggle();
                    }
                    stripPdfIframesForCompactView(executiveApprovalDetailContent);
                    convertEmbeddedPdfsToOnDemand(executiveApprovalDetailContent);
                    await fillExecutiveApprovalSummary(listRow || {}, executiveDetail);
                    renderExecutiveRevisionExplanation(isProjectWithdraw ? executiveDetail : null);
                    placeExecutiveApprovalRoute(withdrawKind);

                    isProjectWithdraw = isProjectWithdrawContext(withdrawKind, listRow, executiveDetail);
                    // แสดงเอกสารจัดซื้อจัดจ้างทั้งเบิกจ่ายทั่วไปและเบิกจ่ายโครงการ (ถ้ามี)
                    await renderExecutiveProcurementList(id);
                    renderExecutiveFinanceDocsList(Array.isArray(executiveDetail?.finance_esign_files) ? executiveDetail.finance_esign_files : []);
                };


                const populateFilters = (rows) => {
                    const safeRows = Array.isArray(rows) ? rows : [];
                    const years = Array.from(new Set(safeRows.map((row) => String(row.fiscal_year || '').trim()).filter(Boolean))).sort((a, b) => Number(b) - Number(a));
                    const statuses = Array.from(new Set(safeRows.map((row) => String(row.status || '').trim()).filter(Boolean))).sort();
                    const types = Array.from(new Set(safeRows.map((row) => String(row.type || '').trim()).filter(Boolean))).sort();
                    if (filterFiscalYearSelect) filterFiscalYearSelect.innerHTML = ['<option value="">- เลือกปีงบประมาณ -</option>', ...years.map((value) => `<option value="${value}">${value}</option>`)].join('');
                    if (filterStatusSelect) filterStatusSelect.innerHTML = ['<option value="">- เลือกสถานะ -</option>', ...statuses.map((value) => `<option value="${value}">${value}</option>`)].join('');
                    if (filterTypeSelect) filterTypeSelect.innerHTML = ['<option value="">- เลือกประเภท -</option>', ...types.map((value) => `<option value="${value}">${value}</option>`)].join('');
                };

                const applyFilters = () => {
                    const fiscalYear = String(filterFiscalYearSelect?.value || '').trim();
                    const status = String(filterStatusSelect?.value || '').trim();
                    const type = String(filterTypeSelect?.value || '').trim();
                    const keyword = String(filterSearchInput?.value || '').trim().toLowerCase();
                    const filtered = generalTableRows.filter((row) => {
                        const rowStatus = String(row.status || '').trim();
                        const userStepStatus = String(row.user_step_status || '').trim().toLowerCase();
                        const isUserWaitingToApprove = userStepStatus === 'pending';
                        const isApprovedBucket = !isUserWaitingToApprove
                            && (userStepStatus === 'approved' || rowStatus === 'อนุมัติ');
                        if (activeTab === 'approved' && !isApprovedBucket) return false;
                        if (activeTab === 'pending' && isApprovedBucket) return false;
                        if (activeTab === 'pending' && rowStatus === 'รอการเงินตรวจสอบเอกสาร') return false;
                        if (fiscalYear !== '' && String(row.fiscal_year || '') !== fiscalYear) return false;
                        if (status !== '' && rowStatus !== status) return false;
                        if (type !== '' && String(row.type || '').trim() !== type) return false;
                        if (keyword === '') return true;
                        const haystack = [row.code, row.subject, row.project_name, row.manager, row.date, rowStatus, row.type, formatApprovalListSubject(row)].map((v) => String(v || '')).join(' ').toLowerCase();
                        return haystack.includes(keyword);
                    });
                    renderRows(filtered);
                };

                tableBody?.addEventListener('click', async (event) => {
                    const trigger = event.target.closest('[data-action]');
                    if (!trigger) return;
                    const id = Number(trigger.dataset.id || 0);
                    if (id <= 0) return;
                    const row = findRowById(id, trigger.dataset.withdrawKind || '') || findRowById(id);
                    const withdrawKind = resolveWithdrawKindFromContext(trigger.dataset.withdrawKind || '', row);

                    if (trigger.dataset.action === 'approve') {
                        pendingApproveId = id;
                        pendingApproveKind = withdrawKind;
                        const statusText = String(row?.status || '').trim();
                        const useFinanceManagerExecutiveFlow = isFinanceManagerApprovalLevel(row);
                        const useExecutiveApprovalDetail = shouldUseExecutiveApprovalDetail(row, withdrawKind);
                        // ผู้ตรวจสอบ (ไม่ใช่โครงการ): modal ยืนยันแบบสั้น — หัวหน้างานโครงการ/หัวหน้างานการเงิน/ผู้บริหารใช้ฟอร์มยืนยันแบบเต็ม
                        let useLegacyLayout = !useExecutiveApprovalDetail;
                        if (['รอตรวจสอบ/อนุมัติ', 'รอการเงินตรวจสอบเอกสาร'].includes(statusText) && !useFinanceManagerExecutiveFlow && !useExecutiveApprovalDetail) {
                            useLegacyLayout = true;
                        }
                        if (!useLegacyLayout) {
                            pendingExecutiveApprovalId = id;
                            pendingExecutiveApprovalKind = withdrawKind;
                            pendingExecutiveSubmitPayload = null;
                            if (executiveApprovalCommentInput) executiveApprovalCommentInput.value = '';
                            expenseChartOptions = [];
                            budgetSourceOptions = [];
                            await fillExecutiveApprovalSummary(row || {}, {});
                            const executiveApprovalDetailContent = getExecutiveApprovalDetailContent();
                            if (executiveApprovalDetailContent) {
                                parkExecutiveProcurementCard();
                                executiveApprovalDetailContent.innerHTML = '';
                            }
                            resetExecutiveProcurementCard();
                            resetExecutiveFinanceDocsCard();
                            resetExecutiveFinanceMemoCard();
                            resetDepartmentMemoToggle();
                            setExecutiveDetailLoading(true);
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-executive-approval-form-modal' }));
                            try {
                                await loadBudgetSourceOptions(withdrawKind);
                                await loadExecutiveApprovalDetailById(id, withdrawKind, row);
                                enhanceExecutiveMobileItemTables(getExecutiveApprovalDetailContent());
                            } finally {
                                setExecutiveDetailLoading(false);
                            }
                            return;
                        }
                        if (approveConfirmModal) {
                            approveConfirmModal.dataset.approvalId = String(id);
                            approveConfirmModal.dataset.withdrawKind = withdrawKind;
                        }
                        if (approveConfirmButton) {
                            approveConfirmButton.dataset.approvalId = String(id);
                            approveConfirmButton.dataset.withdrawKind = withdrawKind;
                        }
                        if (approveCommentInput) approveCommentInput.value = '';
                        fillApproveConfirmDetail({
                            // Show temporary placeholder, then replace with withdraw_document value from API.
                            code: '-',
                            date: String(trigger.dataset.date || row?.date || '-').trim(),
                            subject: String(trigger.dataset.subject || row?.subject || '-').trim(),
                            project_name: String(row?.project_name || '').trim(),
                            withdraw_kind: withdrawKind,
                            withdraw_type: String(row?.withdraw_type || '').trim(),
                            manager: String(trigger.dataset.manager || row?.manager || '-').trim(),
                            amount: Number(trigger.dataset.amount || row?.amount || 0),
                        });
                        setApprovalConfirmLegacyLayout(useLegacyLayout);
                        if (!useLegacyLayout) {
                            await renderApproveReviewDetailContent(id, withdrawKind);
                        }
                        loadApproveConfirmDetailById(id, row || {}, withdrawKind);
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-approval-confirm-modal' }));
                        return;
                    }

                    await openWithdrawDetailModal(row || id, withdrawKind, {});
                });

                const requestApproveConfirm = () => {
                    const id = getPendingApproveId();
                    if (id <= 0) {
                        window.toast?.error?.('ไม่พบรหัสรายการสำหรับอนุมัติ กรุณาปิดหน้าต่างแล้วลองใหม่', { timer: 2200 });
                        return;
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-approval-submit-confirm-modal' }));
                };
                const submitApproveConfirm = async () => {
                    const id = getPendingApproveId();
                    if (id <= 0) {
                        window.toast?.error?.('ไม่พบรหัสรายการสำหรับอนุมัติ กรุณาปิดหน้าต่างแล้วลองใหม่', { timer: 2200 });
                        return;
                    }
                    const comment = String(approveCommentInput?.value || '').trim();
                    const originalButtonText = String(approveConfirmButton?.innerHTML || '');
                    if (approveConfirmButton) {
                        approveConfirmButton.disabled = true;
                        approveConfirmButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>กำลังบันทึก...';
                    }
                    window.toast?.success?.('กำลังส่งข้อมูลอนุมัติ...', { timer: 700 });
                    try {
                        const approveKind = getPendingApproveKind();
                        const result = await postJson(proceedApiForKind(approveKind), {
                            id,
                            comment,
                            approval_comment: comment,
                        });
                        if (result?.status !== 'success') {
                            window.toast?.error?.(result?.message || 'ไม่สามารถอนุมัติรายการได้', { timer: 1500 });
                            return;
                        }
                        window.toast?.success?.(result?.message || 'อนุมัติรายการสำเร็จ', { timer: 1200 });
                        pendingApproveId = 0;
                        pendingApproveKind = 'general';
                        if (approveConfirmModal) {
                            delete approveConfirmModal.dataset.approvalId;
                            delete approveConfirmModal.dataset.withdrawKind;
                        }
                        if (approveConfirmButton) {
                            delete approveConfirmButton.dataset.approvalId;
                            delete approveConfirmButton.dataset.withdrawKind;
                        }
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-approval-confirm-modal' }));
                        await fetchRows();
                        await refreshDocumentReviewPendingBadge();
                    } catch (error) {
                        console.error('[Withdraw Approval Confirm Error]', error);
                        window.toast?.error?.(error?.message || 'ไม่สามารถอนุมัติรายการได้', { timer: 1500 });
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-approval-submit-confirm-modal' }));
                        if (approveConfirmButton) {
                            approveConfirmButton.disabled = false;
                            approveConfirmButton.innerHTML = originalButtonText !== '' ? originalButtonText : 'ยืนยันอนุมัติ';
                        }
                    }
                };
                window.__withdrawApprovalConfirmProceed = requestApproveConfirm;
                window.__withdrawApprovalConfirmSubmit = submitApproveConfirm;
                if (approveConfirmButton) {
                    approveConfirmButton.addEventListener('click', requestApproveConfirm);
                }
                let isExecutiveApprovalSubmitting = false;
                window.__withdrawExecutiveHandleSubmitClick = async () => {
                    console.log('[Withdraw Executive] click: executive-approval-submit-button');
                    const id = Number(pendingExecutiveApprovalId || 0);
                    console.log('[Withdraw Executive] pendingExecutiveApprovalId =', id);
                    if (id <= 0) return;
                    const comment = String(executiveApprovalCommentInput?.value || '').trim();
                    console.log('[Withdraw Executive] prepared payload summary =', {
                        id,
                        commentLength: comment.length,
                    });
                    pendingExecutiveSubmitPayload = {
                        id,
                        comment,
                        approval_comment: comment,
                    };
                    console.log('[Withdraw Executive] open confirm popup: withdraw-executive-approval-confirm-modal');
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-executive-approval-confirm-modal' }));
                };
                executiveApprovalSubmitButton?.addEventListener('click', window.__withdrawExecutiveHandleSubmitClick);
                window.__withdrawExecutiveApprovalConfirmSubmit = async () => {
                    console.log('[Withdraw Executive] enter: __withdrawExecutiveApprovalConfirmSubmit');
                    if (isExecutiveApprovalSubmitting) return;
                    const payload = pendingExecutiveSubmitPayload;
                    console.log('[Withdraw Executive] current payload =', payload);
                    if (!payload || Number(payload.id || 0) <= 0) {
                        console.warn('[Withdraw Executive] invalid payload or id <= 0', payload);
                        window.toast?.error?.('ไม่พบข้อมูลรายการสำหรับยืนยันอนุมัติ กรุณาปิดหน้าต่างแล้วเปิดใหม่', { timer: 2200 });
                        return;
                    }
                    isExecutiveApprovalSubmitting = true;
                    const executiveKind = normalizeWithdrawKind(pendingExecutiveApprovalKind);
                    const executiveProceedApi = proceedApiForKind(executiveKind);
                    console.log('[Withdraw Executive] posting to proceed API =', executiveProceedApi);
                    window.toast?.success?.('กำลังบันทึกการอนุมัติ...', { timer: 700 });
                    try {
                        const result = await postJson(executiveProceedApi, payload);
                        console.log('[Withdraw Executive] API result =', result);
                        if (result?.status !== 'success') {
                            window.toast?.error?.(result?.message || 'ไม่สามารถอนุมัติรายการได้', { timer: 1500 });
                            return;
                        }
                        window.toast?.success?.(result?.message || 'อนุมัติรายการสำเร็จ', { timer: 1200 });
                        pendingExecutiveApprovalId = 0;
                        pendingExecutiveApprovalKind = 'general';
                        pendingExecutiveSubmitPayload = null;
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-executive-approval-form-modal' }));
                        await fetchRows();
                        await refreshDocumentReviewPendingBadge();
                    } catch (error) {
                        console.error('[Withdraw Executive] submit error =', error);
                        window.toast?.error?.(error?.message || 'ไม่สามารถอนุมัติรายการได้', { timer: 1500 });
                    } finally {
                        console.log('[Withdraw Executive] finally: close confirm modal and unlock submit');
                        isExecutiveApprovalSubmitting = false;
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-executive-approval-confirm-modal' }));
                    }
                };
                executiveApprovalConfirmSubmitButton?.addEventListener('click', window.__withdrawExecutiveApprovalConfirmSubmit);
                if (!window.__withdrawExecutiveApprovalConfirmDelegatedBound) {
                    document.addEventListener('click', (event) => {
                        const target = event.target;
                        if (!(target instanceof Element)) return;
                        const trigger = target.closest('#withdraw-executive-approval-confirm-submit-button');
                        if (!trigger) return;
                        console.log('[Withdraw Executive] delegated click: withdraw-executive-approval-confirm-submit-button');
                        window.__withdrawExecutiveApprovalConfirmSubmit?.();
                    });
                    window.__withdrawExecutiveApprovalConfirmDelegatedBound = true;
                }
                window.__withdrawExecutiveOpenRevisionModal = () => {
                    const id = Number(pendingExecutiveApprovalId || 0);
                    if (id <= 0) return;
                    const revisionInput = getExecutiveRevisionReasonInput();
                    if (revisionInput) revisionInput.value = '';
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-executive-revision-modal' }));
                };
                executiveApprovalRevisionButton?.addEventListener('click', window.__withdrawExecutiveOpenRevisionModal);

                window.__withdrawExecutiveRevisionProceed = () => {
                    const id = Number(pendingExecutiveApprovalId || 0);
                    if (id <= 0) return;
                    const revisionInput = getExecutiveRevisionReasonInput();
                    const revisionReason = String(revisionInput?.value || '').trim();
                    if (revisionReason === '') {
                        window.toast?.error?.('กรุณาระบุรายละเอียดที่ต้องแก้ไขก่อนส่งกลับ', { timer: 1800 });
                        revisionInput?.focus?.();
                        return;
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-executive-revision-confirm-modal' }));
                };
                executiveRevisionSubmitButton?.addEventListener('click', window.__withdrawExecutiveRevisionProceed);

                window.__withdrawExecutiveSubmitRevision = async () => {
                    const id = Number(pendingExecutiveApprovalId || 0);
                    if (id <= 0) return;
                    const revisionInput = getExecutiveRevisionReasonInput();
                    const revisionReason = String(revisionInput?.value || '').trim();
                    if (revisionReason === '') {
                        window.toast?.error?.('กรุณาระบุรายละเอียดที่ต้องแก้ไขก่อนส่งกลับ', { timer: 1800 });
                        revisionInput?.focus?.();
                        return;
                    }
                    const revisionKind = normalizeWithdrawKind(
                        findRowById(id, pendingExecutiveApprovalKind)?.withdraw_kind || pendingExecutiveApprovalKind
                    );
                    try {
                        const result = await postJson(proceedApiForKind(revisionKind), {
                            id,
                            action: 'request_revision',
                            revision_comment: revisionReason,
                            approval_comment: revisionReason,
                            comment: revisionReason,
                        });
                        if (result?.status !== 'success') {
                            window.toast?.error?.(result?.message || 'ไม่สามารถส่งกลับให้แก้ไขได้', { timer: 1500 });
                            return;
                        }
                        window.toast?.success?.(result?.message || 'ส่งกลับให้แก้ไขเรียบร้อย', { timer: 1200 });
                        pendingExecutiveApprovalId = 0;
                        pendingExecutiveApprovalKind = 'general';
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-executive-revision-modal' }));
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-executive-approval-form-modal' }));
                        await fetchRows();
                    } catch (error) {
                        window.toast?.error?.(error?.message || 'ไม่สามารถส่งกลับให้แก้ไขได้', { timer: 1500 });
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-executive-revision-confirm-modal' }));
                    }
                };

                const fetchRows = async () => {
                    try {
                        const response = await fetch(`${generalListApi}${generalListApi.includes('?') ? '&' : '?'}_=${Date.now()}`, {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                        });
                        const result = await response.json();
                        generalTableRows = Array.isArray(result?.data) ? result.data : [];
                        syncPendingBadge(generalTableRows);
                        populateFilters(generalTableRows);
                        applyFilters();
                        await consumeApprovalDeepLink();
                    } catch (error) {
                        window.toast?.error?.('ไม่สามารถโหลดรายการอนุมัติได้', { timer: 1400 });
                    }
                };

                const consumeApprovalDeepLink = async () => {
                    if (window.__withdrawApprovalDeepLinkConsumed) return;
                    const params = new URLSearchParams(window.location.search);
                    const deepId = Number(params.get('id') || 0);
                    if (deepId <= 0) return;
                    window.__withdrawApprovalDeepLinkConsumed = true;
                    const kindParam = String(params.get('kind') || '').trim();
                    const deepKind = kindParam !== '' ? normalizeWithdrawKind(kindParam) : '';
                    activeTab = 'pending';
                    syncTabUi();
                    applyFilters();
                    const selector = deepKind !== ''
                        ? `[data-action="approve"][data-id="${deepId}"][data-withdraw-kind="${deepKind}"]`
                        : `[data-action="approve"][data-id="${deepId}"]`;
                    const approveButton = tableBody?.querySelector(selector);
                    if (!approveButton) {
                        window.toast?.error?.('ไม่พบรายการนี้ในรายการรออนุมัติของท่าน', { timer: 2400 });
                        return;
                    }
                    approveButton.click();
                    try {
                        const cleanUrl = new URL(window.location.href);
                        ['id', 'kind', 'withdraw_id', 'view_id'].forEach((key) => cleanUrl.searchParams.delete(key));
                        window.history.replaceState({}, '', cleanUrl.pathname + (cleanUrl.search ? cleanUrl.search : '') + cleanUrl.hash);
                    } catch (error) {}
                };

                syncPendingBadge(generalTableRows);
                populateFilters(generalTableRows);
                syncTabUi();
                applyFilters();
                window.__withdrawApprovalSwitchTab = (tab) => {
                    const nextTab = String(tab || '').trim().toLowerCase() === 'approved' ? 'approved' : 'pending';
                    activeTab = nextTab;
                    syncTabUi();
                    applyFilters();
                };
                if (window.__withdrawApprovalQueuedTab === 'approved') {
                    window.__withdrawApprovalSwitchTab('approved');
                } else {
                    window.__withdrawApprovalSwitchTab('pending');
                }
                pendingTabButton?.addEventListener('click', () => {
                    window.__withdrawApprovalSwitchTab('pending');
                });
                approvedTabButton?.addEventListener('click', () => {
                    window.__withdrawApprovalSwitchTab('approved');
                });
                filterFiscalYearSelect?.addEventListener('change', applyFilters);
                filterStatusSelect?.addEventListener('change', applyFilters);
                filterTypeSelect?.addEventListener('change', applyFilters);
                filterSearchInput?.addEventListener('input', applyFilters);
                fetchRows();
            };

            document.addEventListener('alpine:initialized', initWithdrawApprovalPage);
            document.addEventListener('DOMContentLoaded', initWithdrawApprovalPage);
        </script>
        <style>
            #executive-approval-detail-content .pbm-searchable-select-wrapper {
                position: relative;
                z-index: 1;
                isolation: isolate;
                overflow: visible !important;
            }

            #executive-approval-detail-content .pbm-searchable-select-wrapper:focus-within {
                z-index: 2147483646 !important;
            }

            #executive-approval-detail-content .pbm-searchable-select-wrapper [data-pbm-searchable-select-suggest] {
                z-index: 2147483647 !important;
            }

            #executive-approval-detail-content .overflow-x-auto {
                overflow: visible !important;
            }

            #executive-approval-detail-content table,
            #executive-approval-detail-content tbody,
            #executive-approval-detail-content tr,
            #executive-approval-detail-content td {
                overflow: visible;
                position: relative;
            }

            @media (max-width: 1023px) {
                #executive-approval-detail-content iframe,
                #executive-procurement-card iframe,
                #executive-finance-docs-card iframe {
                    display: none !important;
                    width: 0 !important;
                    height: 0 !important;
                    border: 0 !important;
                }
            }
        </style>
    @endpush
</x-layouts.app>
