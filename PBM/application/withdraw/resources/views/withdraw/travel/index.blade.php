<x-layouts.app title="เบิกจ่ายในการเดินทางไปปฏิบัติงาน">
    <div class="py-2">
        <div class="mx-2 flex flex-col gap-4">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50/80 to-emerald-50/50 p-4 shadow-md shadow-slate-200/50 sm:px-4 sm:py-4">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-400/15 blur-2xl sm:h-36 sm:w-36"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-emerald-200/60 to-transparent"></div>
                <div class="relative flex min-w-0 items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-lg text-white shadow-lg shadow-emerald-500/30 ring-4 ring-white/80">
                        <i class="fas fa-plane-departure" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.65rem]">เบิกจ่ายในการเดินทางไปปฏิบัติงาน</h4>
                        <p class="mb-0 max-w-2xl text-sm leading-snug text-slate-500">รายการที่ยืนยันเซ็นเอกสารแล้วจากระบบขออนุมัติเดินทาง เลือกงบประมาณ ผูกผังบัญชี บันทึกแบบร่าง แล้วกดดำเนินการเบิกจ่ายเพื่อส่งให้ผู้อนุมัติ</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                เพิ่มรายการได้จากระบบขออนุมัติเดินทางเท่านั้น เมื่อยืนยันเอกสารแล้ว ระบบจะเปิดฟอร์มนี้พร้อมดึงรายการมาให้อัตโนมัติ
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/70 shadow-md shadow-slate-200/60">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-white via-slate-50/80 to-emerald-50/40 px-4 py-4 sm:px-5">
                    <h5 class="text-base font-semibold text-slate-900 sm:text-lg">รอทำรายการ</h5>
                    <p class="mt-0.5 text-sm text-slate-500">รายการที่ยังไม่ได้ส่งเข้าสายอนุมัติ</p>
                </div>
                <div id="travel-pending-list" class="grid grid-cols-1 gap-3 p-3 sm:p-5">
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">กำลังโหลด...</div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/70 shadow-md shadow-slate-200/60">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-white via-slate-50/80 to-emerald-50/40 px-4 py-4 sm:px-5">
                    <h5 class="text-base font-semibold text-slate-900 sm:text-lg">รายการเบิกจ่าย</h5>
                    <p class="mt-0.5 text-sm text-slate-500">รายการแบบร่างกดดำเนินการเบิกจ่ายเพื่อส่งให้ผู้อนุมัติ</p>
                </div>
                <div id="travel-record-list" class="grid grid-cols-1 gap-3 p-3 sm:p-5">
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">กำลังโหลด...</div>
                </div>
            </section>
        </div>
    </div>

    @php
        include resource_path('views/withdraw/partials/salary-lookup-options.php');
    @endphp

    <x-modal name="travel-withdraw-modal" maxWidth="4xl" :static="true" variant="modern"
        title="เบิกจ่ายในการเดินทางไปปฏิบัติงาน" description="ตรวจข้อมูลการเดินทาง กรอกบันทึกข้อความ แล้วผูกผังบัญชี"
        iconClass="fas fa-file-signature">
        <style>
            #travel-withdraw-form { color: #0f172a; }
            #travel-withdraw-form .travel-card {
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                background: #fff;
                padding: 16px;
            }
            #travel-withdraw-form .travel-card-head {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }
            #travel-withdraw-form .travel-card-icon {
                display: inline-flex;
                height: 32px;
                width: 32px;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                background: #0f766e;
                color: #fff;
                font-size: 12px;
            }
            #travel-withdraw-form .travel-card-title {
                margin: 0;
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
            }
            #travel-withdraw-form .travel-card-note {
                margin: 2px 0 0;
                font-size: 12px;
                color: #64748b;
            }
            #travel-withdraw-form .travel-label {
                display: block;
                margin-bottom: 6px;
                font-size: 12px;
                font-weight: 700;
                color: #334155;
            }
            #travel-withdraw-form input[type="text"],
            #travel-withdraw-form input[type="search"],
            #travel-withdraw-form input[type="number"],
            #travel-withdraw-form textarea {
                border-color: #cbd5e1;
                background: #fff;
                color: #0f172a;
            }
            #travel-withdraw-form textarea { line-height: 1.55; }
            #travel-withdraw-form .travel-callout {
                border: 1px solid #99f6e4;
                border-radius: 16px;
                background: linear-gradient(180deg, #f0fdfa 0%, #fff 72%);
                padding: 16px;
            }
            #travel-withdraw-form [data-memo-block="items"].travel-card { padding: 0; overflow: hidden; }
            #travel-withdraw-form .travel-items thead th {
                background: #f8fafc;
                color: #475569;
                font-size: 12px;
                font-weight: 700;
            }
            #travel-withdraw-form .travel-items tbody td { vertical-align: middle; }
            #travel-withdraw-form .travel-items tbody tr:last-child td { border-bottom: 0; }
            #travel-withdraw-form #travel-budget-category {
                display: inline-flex;
                margin-top: 10px;
                border-radius: 999px;
                background: #ccfbf1;
                padding: 4px 10px;
                font-size: 12px;
                font-weight: 700;
                color: #115e59;
            }
        </style>
        <form id="travel-withdraw-form" class="space-y-4" enctype="multipart/form-data">
            <input type="hidden" id="travel-handoff-token" value="" />
            <div id="travel-source-box" class="space-y-3"></div>
            <div class="travel-card">
                <div class="travel-card-head">
                    <span class="travel-card-icon"><i class="fas fa-file-lines" aria-hidden="true"></i></span>
                    <div>
                        <p class="travel-card-title">ข้อมูลหนังสือ</p>
                        <p class="travel-card-note">ส่วนงาน เลขที่ วันที่ เรื่อง และผู้รับเรื่อง</p>
                    </div>
                </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="travel-label">ส่วนงาน</label>
                    <x-searchable-dropdown
                        id="travel-department"
                        placeholder="{{ count($departmentSearchOptions) > 0 ? 'พิมพ์เพื่อค้นหาส่วนงาน...' : 'ไม่พบข้อมูลส่วนงาน' }}"
                        :options="$departmentSearchOptions"
                        valueKey="code"
                        textKey="label"
                        :showClear="true"
                        clearTitle="ล้างส่วนงาน"
                    />
                </div>
                <div>
                    <label class="travel-label">ที่</label>
                    <input type="text" id="travel-document-number" placeholder="เลือกส่วนงานเพื่อเติมเลขที่หนังสือ"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                </div>
                <div>
                    <label class="travel-label">วันที่</label>
                    <x-date-picker
                        id="travel-date"
                        placeholder="เลือกวันที่"
                        :value="now()->format('Y-m-d')"
                        :withIcon="false"
                        inputClass="rounded-xl border-slate-300 text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                    />
                </div>
                <div class="md:col-span-2">
                    <label for="travel-subject" class="travel-label">เรื่อง</label>
                    <input type="text" id="travel-subject" placeholder="ระบุหัวข้อเรื่องขออนุมัติเบิกจ่าย"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                </div>
                <div class="md:col-span-2">
                    <label for="travel-to" class="travel-label">เรียน</label>
                    <input type="text" id="travel-to" placeholder="ระบุผู้รับเรื่อง"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                </div>
            </div>
            </div>
            <div data-memo-block="paragraph1">
                <label class="travel-label">ย่อหน้า 1</label>
                <textarea id="travel-paragraph-1" rows="4" placeholder="เนื้อหาย่อหน้าแรก"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
            </div>
            <div class="travel-card" data-memo-block="items">
                    <div class="travel-card-head border-b border-slate-200 px-4 py-3" style="margin-bottom:0;">
                        <span class="travel-card-icon"><i class="fas fa-list-check" aria-hidden="true"></i></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="travel-card-title">รายการเบิกจ่าย</p>
                                <span id="travel-item-count" class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">0</span>
                            </div>
                            <p class="travel-card-note">ชื่อรายการและจำนวนเงินมาจากรายงานการเดินทาง เลือกผังบัญชีให้ตรงชื่อรายการ</p>
                        </div>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="travel-items w-full table-fixed border-collapse text-sm">
                                <colgroup>
                                    <col class="w-14" />
                                    <col />
                                    <col class="w-32" />
                                    <col class="w-[38%]" />
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th class="border-b border-slate-200 px-3 py-2 text-center">ลำดับ</th>
                                        <th class="border-b border-slate-200 px-3 py-2 text-left">รายการ</th>
                                        <th class="border-b border-slate-200 px-3 py-2 text-right">จำนวนเงิน</th>
                                        <th class="border-b border-slate-200 px-3 py-2 text-left">ผังบัญชี</th>
                                    </tr>
                                </thead>
                                <tbody id="travel-item-rows"></tbody>
                            </table>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50/90 to-teal-50/60 px-4 py-3">
                            <div class="flex items-center gap-2 text-sm font-semibold text-emerald-900">
                                <i class="fas fa-calculator text-emerald-600"></i>
                                <span>รวมจำนวนเงินทั้งหมด</span>
                            </div>
                            <div class="text-right">
                                <span id="travel-item-total" class="text-lg font-bold tabular-nums text-emerald-900">0.00</span>
                                <span class="ml-1 text-xs font-medium text-emerald-700">บาท</span>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="travel-callout" data-memo-block="budget">
                <div class="travel-card-head">
                    <span class="travel-card-icon"><i class="fas fa-wallet" aria-hidden="true"></i></span>
                    <div>
                        <p class="travel-card-title">งบประมาณ</p>
                        <p class="travel-card-note">เลือกครั้งเดียว ระบบใช้หมวดของงบนี้กำหนดรายการผังบัญชี</p>
                    </div>
                </div>
                <label for="travel-budget-text" class="travel-label">รหัสและชื่องบประมาณ</label>
                <div class="relative isolate pbm-searchable-select-wrapper" data-pbm-searchable-select-wrapper="1" data-pbm-options-key="travel-budget-options" data-pbm-value-key="id" data-pbm-text-key="label" data-pbm-search-keys="label,code">
                    <input id="travel-budget-text" type="search" placeholder="พิมพ์หรือเลือกงบประมาณ..." class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" autocomplete="off" data-pbm-searchable-select-text />
                    <div class="absolute left-0 right-0 top-full z-50 hidden max-h-60 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-2xl" data-pbm-searchable-select-suggest></div>
                    <input type="hidden" id="travel-budget" data-pbm-searchable-select-hidden />
                </div>
                <p id="travel-budget-category" class="mt-2 text-xs text-slate-500">เลือกงบประมาณเพื่อใช้หมวดกำหนดผังบัญชี</p>
            </div>
            <div data-memo-block="paragraph2">
                <label class="travel-label">ย่อหน้า 2</label>
                <textarea id="travel-paragraph-2" rows="4" placeholder="เนื้อหาย่อหน้าถัดไป" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
            </div>
            <div data-memo-block="paragraph3">
                <label class="travel-label">ย่อหน้า 3</label>
                <textarea id="travel-paragraph-3" rows="6" placeholder="เนื้อหาย่อหน้าที่ 3" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
            </div>
            <div data-memo-block="paragraph4">
                <label class="travel-label">ย่อหน้า 4</label>
                <textarea id="travel-paragraph-4" rows="2" placeholder="เนื้อหาย่อหน้าที่ 4" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
            </div>
            <div class="travel-card" data-memo-block="budget-summary">
                <div class="travel-card-head">
                    <span class="travel-card-icon"><i class="fas fa-table" aria-hidden="true"></i></span>
                    <div>
                        <p class="travel-card-title">สรุปงบประมาณ</p>
                        <p class="travel-card-note">ยอดอนุมัติครั้งนี้รวมจากรายการในรายงานการเดินทาง</p>
                    </div>
                </div>
                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <table class="w-full table-fixed border-collapse border border-slate-300 text-sm">
                        <thead>
                            <tr class="bg-white">
                                <th class="border border-slate-300 px-2 py-1 text-center">รหัสงบประมาณ</th>
                                <th class="border border-slate-300 px-2 py-1 text-center">งบประมาณ</th>
                                <th class="border border-slate-300 px-2 py-1 text-center">อนุมัติแล้ว</th>
                                <th class="border border-slate-300 px-2 py-1 text-center">อนุมัติครั้งนี้</th>
                                <th class="border border-slate-300 px-2 py-1 text-center">รวมอนุมัติ</th>
                                <th class="border border-slate-300 px-2 py-1 text-center">คงเหลือ</th>
                            </tr>
                        </thead>
                        <tbody id="travel-budget-summary">
                            <tr><td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="6">- ยังไม่ได้เลือกงบประมาณ -</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="travel-applicant-block" class="travel-card">
                <div class="travel-card-head">
                    <span class="travel-card-icon"><i class="fas fa-user" aria-hidden="true"></i></span>
                    <p class="travel-card-title">ผู้ขอเบิก</p>
                </div>
                <x-searchable-dropdown
                    id="travel-applicant"
                    placeholder="{{ count($staffListOptions) > 0 ? 'พิมพ์เพื่อค้นหาผู้ขอเบิก...' : 'ไม่พบรายชื่อพนักงาน' }}"
                    :options="$staffListOptions"
                    valueKey="id"
                    textKey="label"
                    :showClear="true"
                    clearTitle="ล้างผู้ขอเบิก"
                />
            </div>
            <div id="travel-extra-actors" class="travel-card">
                <div class="travel-card-head">
                    <span class="travel-card-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="travel-card-title">ผู้ตรวจสอบ/อนุมัติ เพิ่มเติม</p>
                        <p class="travel-card-note">เพิ่มได้เมื่อต้องการ ไม่เพิ่มก็ส่งต่อได้</p>
                    </div>
                    <button type="button" id="travel-add-actor" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100" style="background-color:#ecfdf5;color:#047857;">
                        <i class="fas fa-plus text-[10px]"></i>เพิ่มผู้ตรวจสอบ/อนุมัติ เพิ่มเติม
                    </button>
                </div>
                <div id="travel-actor-rows" class="hidden space-y-3"></div>
            </div>
            <div class="travel-card">
                <div class="travel-card-head">
                    <span class="travel-card-icon"><i class="fas fa-paperclip" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="travel-card-title">เอกสารประกอบการเบิกจ่าย</p>
                        <p class="travel-card-note">เลือกประเภท เลือกรายการ แล้วแนบไฟล์</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-slate-700">ประเภทไฟล์ (เลือกได้หลายประเภท)</label>
                        <div id="travel-document-types" class="grid grid-cols-1 gap-2"></div>
                    </div>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-3 py-2.5 text-xs leading-relaxed text-slate-600">
                        1) เลือกประเภทไฟล์<br />
                        2) ติ๊กเลือกรายการเอกสารที่มี<br />
                        3) แนบไฟล์ตามรายการที่เลือก (แต่ละรายการย่อยแนบได้หลายไฟล์)
                    </div>
                </div>
                <div id="travel-document-subitems-wrap" class="mt-4 hidden">
                    <label class="mb-2 block text-xs font-bold text-slate-700">รายการเอกสารย่อย</label>
                    <div id="travel-document-subitems" class="grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                </div>
                <div id="travel-document-uploads-wrap" class="mt-4 hidden">
                    <label class="mb-2 block text-xs font-bold text-slate-700">แนบไฟล์ตามรายการที่เลือก</label>
                    <div id="travel-document-uploads" class="space-y-3"></div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" id="travel-form-close"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm"
                    style="background-color:#ffffff;color:#475569;">
                    <i class="fas fa-times mr-1"></i>ยกเลิก
                </button>
                <button type="submit" id="travel-submit"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-md"
                    style="background-color:#059669;color:#ffffff;">
                    <i class="far fa-save mr-1"></i>บันทึกแบบร่าง
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal name="travel-cancel-confirm-modal" maxWidth="sm" :static="true">
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-800">ยกเลิกทำรายการ?</h3>
            <p class="mt-1 text-sm text-slate-500">รายการนี้จะถูกนำออกจากคิวรอทำรายการ</p>
            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">รหัส:</span> <span id="travel-cancel-code">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="travel-cancel-subject">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">ผู้เดินทาง:</span> <span id="travel-cancel-traveler">-</span></div>
                <div><span class="font-semibold text-slate-800">จำนวนเงิน:</span> <span id="travel-cancel-amount">-</span> บาท</div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="travel-cancel-dismiss"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-600"
                    style="background-color:#ffffff;color:#475569;">
                    ไม่ยกเลิก
                </button>
                <button type="button" id="travel-cancel-confirm"
                    class="inline-flex h-10 items-center justify-center rounded-lg px-5 text-xs font-bold uppercase text-white"
                    style="background-color:#e11d48;color:#ffffff;">
                    ยืนยันยกเลิก
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="travel-proceed-confirm-modal" maxWidth="sm" :static="true">
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-800">ยืนยันดำเนินการเบิกจ่าย?</h3>
            <p class="mt-1 text-sm text-slate-500">โปรดตรวจสอบรายละเอียดก่อนยืนยันดำเนินการ</p>
            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">เลขที่:</span> <span id="travel-proceed-code">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">วันที่:</span> <span id="travel-proceed-date">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="travel-proceed-subject">-</span></div>
                <div><span class="font-semibold text-slate-800">ผู้ขอเบิก:</span> <span id="travel-proceed-applicant">-</span></div>
                <div><span class="font-semibold text-slate-800">จำนวนเงิน:</span> <span id="travel-proceed-amount">-</span> บาท</div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="travel-proceed-cancel"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-600"
                    style="background-color:#ffffff;color:#475569;">
                    ยกเลิก
                </button>
                <button type="button" id="travel-proceed-confirm"
                    class="inline-flex h-10 items-center justify-center rounded-lg px-5 text-xs font-bold uppercase text-white"
                    style="background-image:linear-gradient(to top left,#2563eb,#22c55e);color:#ffffff;">
                    ยืนยัน
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="travel-record-cancel-confirm-modal" maxWidth="sm" :static="true">
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-800">ยืนยันยกเลิกรายการเบิกจ่าย?</h3>
            <p class="mt-1 text-sm text-slate-500">ระบบจะยกเลิกรายการ และเปลี่ยนสถานะเป็นยกเลิกรายการ</p>
            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">เลขที่:</span> <span id="travel-record-cancel-code">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">วันที่:</span> <span id="travel-record-cancel-date">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="travel-record-cancel-subject">-</span></div>
                <div><span class="font-semibold text-slate-800">ผู้ขอเบิก:</span> <span id="travel-record-cancel-applicant">-</span></div>
                <div><span class="font-semibold text-slate-800">จำนวนเงิน:</span> <span id="travel-record-cancel-amount">-</span> บาท</div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="travel-record-cancel-dismiss"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-600"
                    style="background-color:#ffffff;color:#475569;">
                    ไม่ยกเลิก
                </button>
                <button type="button" id="travel-record-cancel-confirm"
                    class="inline-flex h-10 items-center justify-center rounded-lg px-5 text-xs font-bold uppercase text-white"
                    style="background-color:#e11d48;color:#ffffff;">
                    ยืนยันยกเลิก
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="travel-delete-confirm-modal" maxWidth="sm" :static="true">
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-800">ยืนยันการลบรายการ?</h3>
            <p class="mt-1 text-sm text-slate-500">คุณต้องการลบรายการเบิกจ่ายนี้หรือไม่ รายการและข้อมูลที่เกี่ยวข้องจะถูกลบออกจากระบบ</p>
            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">เลขที่:</span> <span id="travel-delete-code">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="travel-delete-subject">-</span></div>
                <div><span class="font-semibold text-slate-800">จำนวนเงิน:</span> <span id="travel-delete-amount">-</span> บาท</div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="travel-delete-dismiss"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-600"
                    style="background-color:#ffffff;color:#475569;">
                    ไม่ลบ
                </button>
                <button type="button" id="travel-delete-confirm"
                    class="inline-flex h-10 items-center justify-center rounded-lg px-5 text-xs font-bold uppercase text-white"
                    style="background-color:#e11d48;color:#ffffff;">
                    ลบ
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="travel-memo-modal" maxWidth="4xl" :static="true">
        <div class="p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">บันทึกข้อความ</h3>
                    <p id="travel-memo-subject" class="mt-1 text-sm text-slate-500">บันทึกข้อความขออนุมัติเบิกค่าใช้จ่ายในการเดินทางไปปฏิบัติงาน</p>
                </div>
                <button type="button"
                    @click="$dispatch('close-modal', 'travel-memo-modal')"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                    aria-label="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="travel-memo-body" class="mt-4 max-h-[75vh] space-y-4 overflow-y-auto"></div>
            <div class="mt-6 flex justify-end">
                <button type="button"
                    @click="$dispatch('close-modal', 'travel-memo-modal')"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    ปิด
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="travel-esign-docs-modal" maxWidth="4xl" :static="true">
        <div class="p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">เอกสารลงนาม</h3>
                    <p id="travel-esign-docs-subject" class="mt-1 text-sm text-slate-500">เอกสารลงนาม e-Sign ที่แนบมา</p>
                </div>
                <button type="button"
                    @click="$dispatch('close-modal', 'travel-esign-docs-modal')"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                    aria-label="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="travel-esign-docs-loading" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                <i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลดเอกสาร...
            </div>
            <div id="travel-esign-docs-empty" class="mt-4 hidden rounded-xl border border-dashed border-violet-200 bg-violet-50/40 px-4 py-8 text-center text-sm text-slate-500">
                ยังไม่มีเอกสารลงนาม e-Sign ของรายการนี้
            </div>
            <div id="travel-esign-docs-body" class="mt-4 max-h-[75vh] space-y-4 overflow-y-auto"></div>
            <div class="mt-6 flex justify-end">
                <button type="button"
                    @click="$dispatch('close-modal', 'travel-esign-docs-modal')"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    ปิด
                </button>
            </div>
        </div>
    </x-modal>

    @include('withdraw.salary.partials.detail-modal')

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const openLinkedTravelRecord = () => {
                    const viewId = Number(new URLSearchParams(window.location.search).get('view_id') || 0);
                    if (viewId <= 0 || window.__travelViewOpened) return viewId <= 0;
                    if (!window.WithdrawSalaryDetailModal?.openById) return false;
                    window.__travelViewOpened = true;
                    window.WithdrawSalaryDetailModal.openById(viewId);
                    return true;
                };
                if (!openLinkedTravelRecord()) {
                    document.addEventListener('alpine:initialized', openLinkedTravelRecord);
                    let travelViewTries = 0;
                    const travelViewTimer = setInterval(() => {
                        travelViewTries += 1;
                        if (openLinkedTravelRecord() || travelViewTries >= 20) clearInterval(travelViewTimer);
                    }, 150);
                }
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const isTravelAdmin = @json((string) session('pbm.system_role', '') === 'admin');
                const pendingEl = document.getElementById('travel-pending-list');
                const recordEl = document.getElementById('travel-record-list');
                const form = document.getElementById('travel-withdraw-form');
                const rowsEl = document.getElementById('travel-item-rows');
                const totalEl = document.getElementById('travel-item-total');
                const countEl = document.getElementById('travel-item-count');
                let budgets = [];
                let payLists = [];
                let expenses = [];
                let budgetDropdownOptions = [];
                let itemSerial = 0;
                let actorSerial = 0;
                const departmentDocumentMap = @json($departmentDocumentMap ?? []);
                const bookNumberByType = @json($bookNumberByType ?? []);
                const generalBookNumberSuffix = @json($generalBookNumberSuffix ?? '');
                const defaultDepartmentCode = @json($defaultDepartmentCode ?? '');
                const defaultApplicantStaffId = @json($defaultApplicantStaffId ?? '');
                const staffSearchOptions = @json($staffListOptions ?? []);
                const actorStatusOptions = [
                    { value: 'พิจารณา', label: 'พิจารณา' },
                    { value: 'อนุมัติ', label: 'อนุมัติ' },
                    { value: 'เห็นชอบ', label: 'เห็นชอบ' },
                ];
                const documentTypeMap = {};
                const documentTypeLabels = {};

                const money = (value) => Number(value || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                    const [bahtPartRaw, satangPartRaw] = normalizedAmount.toFixed(2).split('.');
                    const bahtText = convertInteger(bahtPartRaw);
                    if (Number(satangPartRaw || 0) === 0) return `${bahtText}บาทถ้วน`;
                    return `${bahtText}บาท${convertInteger(String(satangPartRaw || '0'))}สตางค์`;
                };
                let travelParagraph3Template = '';
                const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[ch]));
                const emptyState = (text) => `<div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">${esc(text)}</div>`;

                const loadOptions = async () => {
                    const [budgetRes, payRes, expenseRes] = await Promise.all([
                        fetch('/withdraw/api/salary/budget-source-options', { headers: { Accept: 'application/json' } }).then((r) => r.json()),
                        fetch('/withdraw/api/salary/pay-list-options', { headers: { Accept: 'application/json' } }).then((r) => r.json()),
                        fetch('/withdraw/api/salary/expense-chart-options', { headers: { Accept: 'application/json' } }).then((r) => r.json()),
                    ]);
                    budgets = Array.isArray(budgetRes?.data?.sources) ? budgetRes.data.sources : [];
                    payLists = Array.isArray(payRes?.data?.pay_lists) ? payRes.data.pay_lists : [];
                    expenses = Array.isArray(expenseRes?.data?.expense_charts) ? expenseRes.data.expense_charts : [];
                    budgetDropdownOptions = budgets.map((row) => {
                        const code = String(row.code || '').trim();
                        const path = String(row.source_path || [row.budget_source_name, row.year ? `ปี ${row.year}` : ''].filter(Boolean).join(' · ')).replace(/\//g, ' ').replace(/\s+/g, ' ').trim();
                        return {
                            id: String(row.id || ''),
                            code,
                            category: String(row.category_pay_code || ''),
                            label: [code, path].filter(Boolean).join(' · '),
                        };
                    }).filter((row) => row.id !== '' && row.label !== '');
                    setDropdown('travel-budget-options', budgetDropdownOptions, budgetDropdownOptions.length ? '' : 'ไม่พบข้อมูลงบประมาณ');
                    window.PBMSearchableDropdown?.initAll?.();
                };

                const selectedBudget = () => {
                    const id = String(document.getElementById('travel-budget')?.value || '');
                    return budgets.find((row) => String(row.id) === id) || null;
                };

                const chartOptionsForBudget = (budget) => {
                    if (!budget) return [];
                    const categoryCode = String(budget.category_pay_code || '');
                    const payCodes = new Set();
                    payLists.forEach((row) => {
                        const payCode = String(row.budget_pay_code || '');
                        const parent = String(row.budget_category_pay_code || '');
                        if (!payCode) return;
                        if (categoryCode === '' || parent === categoryCode || payCode === categoryCode) payCodes.add(payCode);
                    });
                    if (categoryCode !== '') payCodes.add(categoryCode);
                    return expenses
                        .filter((row) => payCodes.has(String(row.pay_code || '')))
                        .map((row) => {
                            const detail = String(row.detail || '').trim();
                            const name = detail !== '' && detail !== '-' ? detail : String(row.name || '');
                            return {
                                id: String(row.id || ''),
                                code: String(row.expense_code || ''),
                                pay_code: String(row.pay_code || ''),
                                label: String(row.name || name || ''),
                            };
                        })
                        .filter((row) => row.id !== '' && row.label !== '');
                };

                const syncBudgetCategoryLabel = () => {
                    const label = document.getElementById('travel-budget-category');
                    const budget = selectedBudget();
                    if (!label) return;
                    if (!budget) {
                        label.textContent = 'เลือกงบประมาณเพื่อใช้หมวดกำหนดผังบัญชี';
                        return;
                    }
                    label.textContent = `หมวด: ${budget.category_pay_name || budget.category_pay_code || '-'}`;
                };

                const sameChartName = (left, right) => String(left || '').replace(/\s+/g, ' ').trim() === String(right || '').replace(/\s+/g, ' ').trim();

                const refreshChartOptions = () => {
                    const budget = selectedBudget();
                    const options = chartOptionsForBudget(budget);
                    const allowed = new Set(options.map((row) => row.id));
                    rowsEl.querySelectorAll('.travel-item').forEach((row) => {
                        const hidden = row.querySelector('.travel-expense');
                        const text = row.querySelector('.travel-expense-text');
                        const itemName = row.querySelector('.travel-name')?.value || '';
                        if (hidden && hidden.value && !allowed.has(String(hidden.value))) {
                            hidden.value = '';
                            if (text) text.value = '';
                        }
                        if (hidden && !String(hidden.value || '').trim()) {
                            const matched = options.find((option) => sameChartName(option.label, itemName));
                            if (matched) {
                                hidden.value = matched.id;
                                if (text) text.value = matched.label;
                            }
                        }
                    });
                    setDropdown('travel-chart-options', options, budget ? (options.length ? '' : 'ไม่พบผังบัญชีในหมวดนี้') : 'เลือกงบประมาณก่อน');
                    window.PBMSearchableDropdown?.initAll?.();
                };

                const refreshBudgetSummary = () => {
                    const body = document.getElementById('travel-budget-summary');
                    if (!body) return;
                    const budget = selectedBudget();
                    const thisTime = [...rowsEl.querySelectorAll('.travel-amount')].reduce((sum, input) => {
                        const amount = Number(input.value || 0);
                        return sum + (Number.isFinite(amount) ? amount : 0);
                    }, 0);
                    if (!budget) {
                        body.innerHTML = '<tr><td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="6">- ยังไม่ได้เลือกงบประมาณ -</td></tr>';
                        return;
                    }
                    const approved = Number(budget.approved_amount || 0);
                    const budgetAmount = Number(budget.amount || 0);
                    const totalApproved = approved + thisTime;
                    const remaining = budgetAmount - totalApproved;
                    const cell = (value) => `<td class="border border-slate-300 px-2 py-2 text-right tabular-nums">${money(value)}</td>`;
                    body.innerHTML = `<tr>
                        <td class="border border-slate-300 px-2 py-2 text-center">${esc(budget.code || '-')}</td>
                        ${cell(budgetAmount)}
                        ${cell(approved)}
                        ${cell(thisTime)}
                        ${cell(totalApproved)}
                        ${cell(remaining)}
                    </tr>`;
                };

                const refreshTotal = () => {
                    const amounts = [...rowsEl.querySelectorAll('.travel-amount')].map((input) => Number(input.value || 0));
                    const total = amounts.reduce((sum, amount) => sum + (Number.isFinite(amount) ? amount : 0), 0);
                    if (totalEl) totalEl.textContent = money(total);
                    refreshBudgetSummary();
                    syncTravelParagraph3();
                };

                const reindexRows = () => {
                    rowsEl.querySelectorAll('.travel-item').forEach((card, index) => {
                        const label = card.querySelector('[data-row-label]');
                        const name = card.querySelector('.travel-name')?.value || '';
                        if (label) label.textContent = `รายการที่ ${index + 1}${name ? ` · ${name}` : ''}`;
                    });
                    refreshTotal();
                };

                const setDropdown = (key, options, status) => {
                    window.PBMSearchableDropdown?.setOptions?.(key, options);
                    window.PBMSearchableDropdown?.setStatus?.(key, status || (options.length ? '' : 'ไม่พบข้อมูล'));
                };

                const itemRowHtml = (item, index) => `
                    <tr class="travel-item" data-index="${index}">
                        <td class="border-b border-slate-100 px-3 py-3 text-center text-xs font-bold text-slate-400">${index + 1}</td>
                        <td class="border-b border-slate-100 px-3 py-3 font-semibold text-slate-800">${esc(item.name || '-')}</td>
                        <td class="border-b border-slate-100 px-3 py-3 text-right font-semibold tabular-nums text-slate-800">${money(item.amount)}</td>
                        <td class="border-b border-slate-100 px-3 py-2">
                            <div class="relative isolate pbm-searchable-select-wrapper" data-pbm-searchable-select-wrapper="1" data-pbm-options-key="travel-chart-options" data-pbm-value-key="id" data-pbm-text-key="label" data-pbm-search-keys="label">
                                <input type="search" placeholder="เลือกผังบัญชี..." class="travel-expense-text h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" autocomplete="off" data-pbm-searchable-select-text />
                                <div class="absolute left-0 right-0 top-full z-50 hidden max-h-60 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-2xl" data-pbm-searchable-select-suggest></div>
                                <input type="hidden" class="travel-expense" data-pbm-searchable-select-hidden />
                            </div>
                            <input type="hidden" class="travel-name" value="${esc(item.name || '')}" />
                            <input type="hidden" class="travel-amount" value="${Number(item.amount || 0)}" />
                        </td>
                    </tr>`;

                const travelExpenseLabels = ['ค่าใช้จ่ายเดินทาง', 'ค่าใช้จ่ายอื่น', 'ค่าลงทะเบียน', 'ค่าเบี้ยเลี้ยง', 'ค่าที่พัก', 'ค่าพาหนะ'];
                const expenseItemName = (name) => {
                    const text = String(name || '').trim();
                    return travelExpenseLabels.find((label) => text === label || text.endsWith(` ${label}`)) || text;
                };
                const reportExpenseRows = (items) => {
                    const grouped = new Map();
                    (Array.isArray(items) ? items : []).forEach((item) => {
                        const name = expenseItemName(item?.name);
                        const amount = Number(item?.amount || 0);
                        if (name === '' || !Number.isFinite(amount) || amount <= 0) return;
                        grouped.set(name, (grouped.get(name) || 0) + amount);
                    });
                    return [...grouped.entries()].map(([name, amount]) => ({ name, amount }));
                };

                const entryRowsFromPayload = (payload) => {
                    const fromItems = reportExpenseRows(payload?.items);
                    if (fromItems.length) return fromItems;
                    const people = Array.isArray(payload?.report_view?.people) ? payload.report_view.people : [];
                    const grouped = new Map();
                    people.forEach((person) => {
                        (Array.isArray(person?.expenses) ? person.expenses : []).forEach((expense) => {
                            const name = expenseItemName(expense?.label);
                            const amount = Number(expense?.amount || 0);
                            if (name === '' || !Number.isFinite(amount) || amount <= 0) return;
                            grouped.set(name, (grouped.get(name) || 0) + amount);
                        });
                    });
                    return [...grouped.entries()].map(([name, amount]) => ({ name, amount }));
                };
                const renderRows = (payload) => {
                    const list = entryRowsFromPayload(payload);
                    rowsEl.innerHTML = list.length
                        ? list.map((item, index) => itemRowHtml(item, index)).join('')
                        : '<tr><td class="px-3 py-6 text-center text-slate-400" colspan="4">ไม่พบรายการจากรายงานการเดินทาง</td></tr>';
                    if (countEl) countEl.textContent = String(list.length);
                    refreshChartOptions();
                    refreshTotal();
                };
                rowsEl?.addEventListener('input', (event) => {
                    if (event.target.closest('.travel-amount')) refreshTotal();
                });

                const travelItemsTotal = () => [...rowsEl.querySelectorAll('.travel-amount')].reduce((sum, input) => {
                    const amount = Number(input.value || 0);
                    return sum + (Number.isFinite(amount) ? amount : 0);
                }, 0);
                const fillTravelParagraph3 = (templateText, budgetName, total) => {
                    const amountText = money(total);
                    const words = toThaiBahtText(total);
                    let text = String(templateText || '');
                    const placeholders = text.match(/\.{2,}|…+/gu) || [];
                    if (placeholders.length >= 3) {
                        [budgetName, amountText, words].forEach((value) => {
                            text = text.replace(/\.{2,}|…+/u, value || '...');
                        });
                        return text;
                    }
                    if (placeholders.length === 1 && /โดยเบิกจ่ายจาก/u.test(text)) {
                        return text.replace(/\.{2,}|…+/u, `${budgetName || '...'} จำนวนเงิน ${amountText} บาท ( ${words} ) ของคณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่`);
                    }
                    return text;
                };
                const syncTravelParagraph3 = () => {
                    const input = document.getElementById('travel-paragraph-3');
                    const block = input?.closest('[data-memo-block]');
                    if (!input || !block || block.classList.contains('hidden') || travelParagraph3Template.trim() === '') return;
                    const budget = selectedBudget();
                    const budgetName = String(budget?.source_path || budget?.budget_source_name || '').replace(/\//g, ' ').replace(/\s+/g, ' ').trim();
                    input.value = fillTravelParagraph3(travelParagraph3Template, budgetName, travelItemsTotal());
                };

                document.getElementById('travel-budget')?.addEventListener('change', () => {
                    syncBudgetCategoryLabel();
                    refreshChartOptions();
                    refreshBudgetSummary();
                    syncTravelParagraph3();
                });

                const sectionHead = (icon, title) => `
                    <div class="mb-3 flex items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-white" style="background-color:#0d9488;"><i class="fas ${icon} text-[11px]" aria-hidden="true"></i></span>
                        <p class="mb-0 text-sm font-bold text-slate-800">${esc(title)}</p>
                    </div>`;
                const requestSummary = (headers) => {
                    const list = (headers || []).filter((item) => item && (item.name || item.title));
                    const header = list.find((item) => item.is_requester !== false) || list[0] || {};
                    if (!header.name && !header.title) return '';
                    const chips = [
                        header.code ? `<span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">#${esc(header.code)}</span>` : '',
                        header.status_name ? `<span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-800 ring-1 ring-amber-100">${esc(header.status_name)}</span>` : '',
                        header.type_name ? `<span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-600 ring-1 ring-slate-100">${esc(header.type_name)}</span>` : '',
                        header.is_abroad ? '<span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-bold text-sky-700 ring-1 ring-sky-100">ต่างประเทศ</span>' : '<span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-600 ring-1 ring-slate-100">ในประเทศ</span>',
                        header.is_group ? '<span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100">กลุ่ม</span>' : '',
                    ].filter(Boolean).join('');
                    const role = [header.position, header.department].filter(Boolean).join(' · ');
                    const fact = (icon, wrap, label, value) => value ? `
                        <div class="flex items-center gap-2.5 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-100">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md ${wrap}"><i class="fas ${icon} text-[10px]" aria-hidden="true"></i></span>
                            <span class="min-w-0"><span class="block text-[11px] font-semibold leading-none text-slate-500">${label}</span><span class="mt-1 block text-sm font-semibold text-slate-800">${esc(value)}</span></span>
                        </div>` : '';
                    return `<section class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                        <div class="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50 px-4 py-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-white" style="background-color:#0d9488;"><i class="fas fa-file-signature text-[11px]" aria-hidden="true"></i></span>
                            <p class="mb-0 text-sm font-bold text-slate-800">ข้อมูลการขออนุมัติ</p>
                        </div>
                        <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3.5">
                            ${header.photo_url ? `<img src="${esc(header.photo_url)}" alt="" class="h-14 w-14 shrink-0 rounded-full object-cover ring-2 ring-teal-100">` : `<span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700 ring-2 ring-teal-100"><i class="fas fa-user" aria-hidden="true"></i></span>`}
                            <div class="min-w-0 flex-1">
                                <p class="mb-0 text-[11px] font-semibold text-slate-500">${header.is_requester === false ? 'ผู้ร่วมเดินทาง' : 'ผู้ขออนุมัติ'}</p>
                                <p class="mb-0 mt-1 truncate text-base font-bold text-slate-900">${esc(header.name || '-')}</p>
                                ${role ? `<p class="mb-0 mt-0.5 truncate text-sm text-slate-500">${esc(role)}</p>` : ''}
                            </div>
                        </div>
                        <div class="space-y-3 px-4 py-3.5">
                            <div class="flex flex-wrap items-center gap-1.5">${chips}</div>
                            <div>
                                <h3 class="mb-0 text-sm font-bold leading-snug text-slate-800">${esc(header.title || '-')}</h3>
                                ${header.objective ? `<p class="mb-0 mt-1 text-xs leading-relaxed text-slate-500">วัตถุประสงค์: ${esc(header.objective)}</p>` : ''}
                            </div>
                            <div class="grid gap-2">
                                ${fact('fa-calendar-day', 'bg-sky-100 text-sky-600', 'วันที่ไปปฏิบัติงาน', header.dates)}
                                ${fact('fa-location-dot', 'bg-amber-100 text-amber-600', 'สถานที่', header.place)}
                                ${header.borrowed ? `<div class="rounded-xl bg-rose-50 px-3 py-2 ring-1 ring-rose-100"><p class="mb-0 text-[11px] font-semibold text-rose-700">ยืมเงิน</p><p class="mb-0 mt-1 text-sm font-bold text-rose-800">${esc(header.loan_amount || '0.00')} บาท</p>${header.loan_no ? `<p class="mb-0 mt-0.5 text-[11px] text-rose-700">เลขที่ใบยืม ${esc(header.loan_no)}</p>` : ''}</div>` : ''}
                            </div>
                        </div>
                    </section>`;
                };
                const renderSource = (payload) => {
                    const box = document.getElementById('travel-source-box');
                    if (!box) return;
                    const view = payload.report_view && typeof payload.report_view === 'object' ? payload.report_view : null;
                    const summary = requestSummary(view?.headers);
                    box.innerHTML = summary || `<section class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">${sectionHead('fa-route', 'รายการจากระบบขออนุมัติเดินทาง')}<p class="mb-0 text-sm text-slate-500">${esc([payload.traveler_name, payload.place, payload.leaves_code ? `เลขที่ ${payload.leaves_code}` : ''].filter(Boolean).join(' · ') || '-')}</p></section>`;
                };

                const syncDocumentNumber = () => {
                    const code = String(document.getElementById('travel-department')?.value || '').trim();
                    const input = document.getElementById('travel-document-number');
                    if (!input) return;
                    const base = String(departmentDocumentMap[code] || '').trim();
                    const entries = Object.entries(bookNumberByType || {});
                    const matched = entries.find(([type]) => String(type).includes('เดินทาง')) || entries.find(([type]) => String(type).includes('ทั่วไป'));
                    const suffix = String((matched && matched[1]) || generalBookNumberSuffix || '').trim();
                    input.value = base === '' ? suffix : (suffix === '' || base.endsWith(suffix) ? base : `${base}${suffix}`);
                };
                document.getElementById('travel-department')?.addEventListener('change', syncDocumentNumber);
                if (defaultDepartmentCode) {
                    const departmentInput = document.getElementById('travel-department');
                    if (departmentInput && departmentInput.value !== defaultDepartmentCode) {
                        departmentInput.value = defaultDepartmentCode;
                        departmentInput.dispatchEvent(new Event('input', { bubbles: true }));
                        departmentInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                syncDocumentNumber();
                if (defaultApplicantStaffId) {
                    const applicantInput = document.getElementById('travel-applicant');
                    if (applicantInput) {
                        applicantInput.value = defaultApplicantStaffId;
                        applicantInput.dispatchEvent(new Event('input', { bubbles: true }));
                        applicantInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                const actorRows = document.getElementById('travel-actor-rows');
                document.getElementById('travel-add-actor')?.addEventListener('click', () => {
                    actorSerial += 1;
                    const key = `travel-actor-staff-${actorSerial}`;
                    actorRows?.classList.remove('hidden');
                    const row = document.createElement('div');
                    row.className = 'travel-actor-row rounded-xl border border-slate-200 bg-slate-50/60 p-3';
                    row.innerHTML = `<div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_240px_auto] md:items-end">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">รายชื่อผู้ตรวจสอบ/อนุมัติ เพิ่มเติม</label>
                            <div class="relative isolate pbm-searchable-select-wrapper" data-pbm-searchable-select-wrapper="1" data-pbm-options-key="${key}" data-pbm-value-key="id" data-pbm-text-key="label">
                                <input type="search" placeholder="พิมพ์เพื่อค้นหา..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" autocomplete="off" data-pbm-searchable-select-text />
                                <div class="absolute left-0 right-0 top-full z-50 hidden max-h-60 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-2xl" data-pbm-searchable-select-suggest></div>
                                <input type="hidden" class="travel-actor-staff" data-pbm-searchable-select-hidden />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">สถานะดำเนินการ</label>
                            <select class="travel-actor-status w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><option value="">เลือกสถานะดำเนินการ</option>${actorStatusOptions.map((option) => `<option value="${esc(option.value)}">${esc(option.label)}</option>`).join('')}</select>
                        </div>
                        <button type="button" class="travel-remove-actor inline-flex h-9 items-center justify-center rounded-lg px-3 text-rose-600 ring-1 ring-rose-100" style="background-color:#fff1f2;color:#e11d48;">ลบ</button>
                    </div>`;
                    actorRows?.appendChild(row);
                    setDropdown(key, staffSearchOptions, staffSearchOptions.length ? '' : 'ไม่พบรายชื่อ');
                    window.PBMSearchableDropdown?.initAll?.();
                });
                actorRows?.addEventListener('click', (event) => {
                    const button = event.target.closest('.travel-remove-actor');
                    if (!button) return;
                    button.closest('.travel-actor-row')?.remove();
                    if (!actorRows.querySelector('.travel-actor-row')) actorRows.classList.add('hidden');
                });

                const typeWrap = document.getElementById('travel-document-types');
                const subWrap = document.getElementById('travel-document-subitems-wrap');
                const subList = document.getElementById('travel-document-subitems');
                const uploadWrap = document.getElementById('travel-document-uploads-wrap');
                const uploadList = document.getElementById('travel-document-uploads');
                const renderUploads = () => {
                    const checked = [...subList.querySelectorAll('input[type="checkbox"]:checked')];
                    if (!checked.length) {
                        uploadList.innerHTML = '';
                        uploadWrap.classList.add('hidden');
                        return;
                    }
                    uploadWrap.classList.remove('hidden');
                    uploadList.innerHTML = checked.map((checkbox, index) => `
                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">${index + 1}. ${esc(checkbox.dataset.label || '')}</label>
                            <input type="file" name="withdraw_documents[${esc(checkbox.dataset.type)}][${esc(checkbox.dataset.itemId)}][]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" />
                        </div>`).join('');
                };
                const renderSubitems = () => {
                    const selected = [...typeWrap.querySelectorAll('input[type="checkbox"]:checked')].map((input) => input.value);
                    const items = selected.flatMap((type) => (documentTypeMap[type] || []).map((item) => ({ ...item, type })));
                    if (!items.length) {
                        subList.innerHTML = '';
                        subWrap.classList.add('hidden');
                        renderUploads();
                        return;
                    }
                    subWrap.classList.remove('hidden');
                    subList.innerHTML = items.map((item) => `<label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"><input type="checkbox" value="${esc(item.type)}::${esc(item.id)}" data-type="${esc(item.type)}" data-item-id="${esc(item.id)}" data-label="${esc((documentTypeLabels[item.type] || '') + ' - ' + item.label)}" class="h-4 w-4 rounded border-slate-300 text-emerald-600" /><span>${esc(item.label)}</span></label>`).join('');
                    renderUploads();
                };
                typeWrap?.addEventListener('change', renderSubitems);
                subList?.addEventListener('change', renderUploads);
                fetch('/withdraw/api/salary/document-options', { headers: { Accept: 'application/json' } }).then((response) => response.json()).then((json) => {
                    const types = Array.isArray(json?.data?.types) ? json.data.types : [];
                    const items = Array.isArray(json?.data?.items) ? json.data.items : [];
                    types.forEach((type) => {
                        const code = String(type.code || '').trim();
                        if (!code) return;
                        documentTypeMap[code] = [];
                        documentTypeLabels[code] = String(type.name || '');
                    });
                    items.forEach((item) => {
                        const typeCode = String(item.type_code || '').trim();
                        if (!documentTypeMap[typeCode]) return;
                        documentTypeMap[typeCode].push({ id: String(item.id || ''), label: String(item.name || '') });
                    });
                    if (typeWrap) {
                        typeWrap.innerHTML = types.filter((type) => (documentTypeMap[String(type.code || '')] || []).length).map((type) => `<label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"><input type="checkbox" value="${esc(type.code)}" class="h-4 w-4 rounded border-slate-300 text-emerald-600" /><span>${esc(type.name)}</span></label>`).join('');
                    }
                }).catch(() => {});

                const setTravelField = (id, value) => {
                    const input = document.getElementById(id);
                    if (input) input.value = String(value ?? '');
                };

                const isTravelParagraphEnabled = (template, key) => {
                    const flags = template?.paragraph_enabled;
                    if (!flags || typeof flags !== 'object' || flags[key] === undefined) return true;
                    const value = flags[key];
                    return value !== false && value !== 0 && value !== '0' && value !== 'false';
                };

                const reorderTravelMemoBlocks = (blockOrder) => {
                    const formEl = document.getElementById('travel-withdraw-form');
                    const anchor = document.getElementById('travel-applicant-block');
                    if (!formEl || !anchor || !Array.isArray(blockOrder)) return;
                    blockOrder.forEach((blockKey) => {
                        const blockEl = formEl.querySelector(`[data-memo-block="${String(blockKey || '')}"]`);
                        if (!blockEl || blockEl.parentElement !== formEl) return;
                        formEl.insertBefore(blockEl, anchor);
                    });
                    const itemsEl = formEl.querySelector('[data-memo-block="items"]');
                    const budgetEl = formEl.querySelector('[data-memo-block="budget"]');
                    const paragraph2 = formEl.querySelector('[data-memo-block="paragraph2"]');
                    const paragraph2Visible = paragraph2 && !paragraph2.classList.contains('hidden') && paragraph2.style.display !== 'none';
                    if (budgetEl && paragraph2Visible) {
                        paragraph2.insertAdjacentElement('afterend', budgetEl);
                    } else if (budgetEl && itemsEl && itemsEl.parentElement === formEl) {
                        itemsEl.insertAdjacentElement('beforebegin', budgetEl);
                    }
                    const summaryEl = formEl.querySelector('[data-memo-block="budget-summary"]');
                    const paragraph4 = formEl.querySelector('[data-memo-block="paragraph4"]');
                    if (summaryEl && paragraph4 && paragraph4.parentElement === formEl) {
                        paragraph4.insertAdjacentElement('afterend', summaryEl);
                    }
                };

                const fillTravelParagraph1 = (templateText, payload) => {
                    const headers = Array.isArray(payload?.report_view?.headers) ? payload.report_view.headers : [];
                    const header = headers.find((item) => item && item.is_requester !== false) || headers[0] || {};
                    const typeName = String(header.type_name || '').trim();
                    const detail = String(header.title || '').trim();
                    const purpose = [typeName, detail && detail !== typeName ? detail : ''].filter(Boolean).join(' ');
                    const year = String(header.year_text || '').trim();
                    const place = String(header.place || '').trim();
                    const abroad = header.is_abroad === true || header.is_abroad === 1 || header.is_abroad === '1' || header.is_abroad === 'true';
                    const regionName = String(header.province_name || '').trim();
                    const dateMatch = String(header.dates || '').match(/\d+\s+(\S+)\s+(\d{4})/u);
                    const month = String(header.month_text || (dateMatch ? `${dateMatch[1]} ${dateMatch[2]}` : '')).trim();
                    let text = String(templateText || '');
                    text = text.replace(/จังหวัด\s*(?:\.{2,}|…+)/u, regionName === '' ? (abroad ? 'ประเทศ...' : 'จังหวัด...') : `${abroad ? 'ประเทศ' : 'จังหวัด'}${regionName}`);
                    [purpose, year, place, month].forEach((value) => {
                        text = text.replace(/\.{2,}|…+/u, value || '...');
                    });
                    return text;
                };

                const applyTravelMemoTemplate = async () => {
                    try {
                        const url = new URL('/withdraw/api/memo-template', window.location.origin);
                        url.searchParams.set('kind', 'travel');
                        url.searchParams.set('sub_type', 'default');
                        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        const template = result?.data;
                        if (!template) return;
                        setTravelField('travel-subject', template.subject || '');
                        setTravelField('travel-to', template.to || '');
                        setTravelField('travel-paragraph-1', template.paragraph1 || '');
                        setTravelField('travel-paragraph-2', template.paragraph2 || '');
                        travelParagraph3Template = String(template.paragraph3 || '');
                        setTravelField('travel-paragraph-3', travelParagraph3Template);
                        setTravelField('travel-paragraph-4', template.paragraph4 || '');
                        ['paragraph1', 'paragraph2', 'paragraph3', 'paragraph4'].forEach((key) => {
                            const block = form?.querySelector(`[data-memo-block="${key}"]`);
                            if (!block) return;
                            const enabled = isTravelParagraphEnabled(template, key);
                            block.classList.toggle('hidden', !enabled);
                            if (enabled) {
                                block.style.removeProperty('display');
                            } else {
                                block.style.setProperty('display', 'none', 'important');
                                const input = block.querySelector('textarea');
                                if (input) input.value = '';
                            }
                        });
                        reorderTravelMemoBlocks(template.block_order || []);
                        const actors = document.getElementById('travel-extra-actors');
                        if (actors) actors.classList.toggle('hidden', template.process_actors_enabled !== true);
                    } catch (error) {
                        const paragraph1 = document.getElementById('travel-paragraph-1');
                        if (paragraph1 && paragraph1.value.trim() === '') {
                            paragraph1.value = 'งานการเงินขอเบิกจ่ายค่าใช้จ่ายตามรายงานการเดินทางที่เซ็นเอกสารเรียบร้อยแล้ว';
                        }
                    }
                };

                const openForm = async (payload) => {
                    document.getElementById('travel-handoff-token').value = payload.token || '';
                    await applyTravelMemoTemplate();
                    const paragraph1 = document.getElementById('travel-paragraph-1');
                    const paragraph1Block = paragraph1?.closest('[data-memo-block]');
                    if (paragraph1 && paragraph1Block && !paragraph1Block.classList.contains('hidden')) {
                        paragraph1.value = fillTravelParagraph1(paragraph1.value, payload);
                    }
                    const subject = document.getElementById('travel-subject');
                    if (subject && subject.value.trim() === '') subject.value = payload.subject || 'ขอเบิกจ่ายค่าใช้จ่ายเดินทาง';
                    renderSource(payload);
                    renderRows(payload);
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'travel-withdraw-modal' }));
                };
                const closeForm = () => {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-withdraw-modal' }));
                };
                document.getElementById('travel-form-close')?.addEventListener('click', closeForm);

                let pendingTravelRows = [];
                const loadPending = async () => {
                    const result = await fetch('/withdraw/api/travel/pending', { headers: { Accept: 'application/json' } }).then((r) => r.json());
                    const items = Array.isArray(result?.data?.items) ? result.data.items : [];
                    pendingTravelRows = items;
                    if (!items.length) {
                        pendingEl.innerHTML = emptyState('ยังไม่มีรายการรอทำ');
                        return;
                    }
                    pendingEl.innerHTML = items.map((row) => `
                        <article class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-md shadow-slate-200/50">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100/90 px-2.5 py-1 text-[10px] font-bold tracking-wide text-slate-600 ring-1 ring-slate-200/80">${esc(row.leaves_code || 'รอทำรายการ')}</div>
                                    <h6 class="mt-2 text-[15px] font-extrabold leading-6 text-slate-900">${esc(row.subject || 'รายการเดินทาง')}</h6>
                                    <p class="mt-1 text-sm text-slate-500">${esc(row.traveler || '-')}${row.place ? ' · ' + esc(row.place) : ''}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-base font-extrabold text-slate-900">${money(row.amount)} <span class="text-[11px] font-semibold text-slate-500">บาท</span></div>
                                    <div class="mt-2 flex flex-wrap justify-end gap-2">
                                        <button type="button" class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold" style="background-color:#fff1f2;color:#be123c;border:1px solid #fecdd3;" data-cancel-token="${esc(row.token)}"><i class="fas fa-ban text-[10px]"></i>ยกเลิกทำรายการ</button>
                                        <button type="button" class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold text-white" style="background-color:#059669;color:#ffffff;" data-open-token="${esc(row.token)}">ทำรายการต่อ</button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    `).join('');
                };

                const travelStatusClassMap = {
                    'ร่าง': 'bg-slate-100 text-slate-700 ring-slate-200',
                    'รอตรวจสอบ/อนุมัติ': 'bg-amber-100 text-amber-700 ring-amber-200',
                    'รอการเงินตรวจสอบเอกสาร': 'bg-cyan-100 text-cyan-700 ring-cyan-200',
                    'รอการเงินตรวจสอบเอกสาร (แก้ไข)': 'bg-cyan-100 text-cyan-700 ring-cyan-200',
                    'รอแก้ไข': 'bg-rose-100 text-rose-700 ring-rose-200',
                    'รออนุมัติ': 'bg-violet-100 text-violet-700 ring-violet-200',
                    'ยกเลิกรายการ': 'bg-slate-200 text-slate-700 ring-slate-300',
                    'อนุมัติ': 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                };
                const formatApprovalDateTime = (value) => {
                    const raw = String(value || '').trim();
                    if (raw === '' || raw.startsWith('0000-00-00')) return '';
                    const parsed = new Date(raw.replace(' ', 'T'));
                    if (Number.isNaN(parsed.getTime())) return '';
                    const dateText = parsed.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
                    const timeText = parsed.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', hour12: false });
                    return `${dateText} ${timeText} น.`;
                };
                const buildApprovalTimeline = (row) => {
                    const rawStatus = String(row?.raw_status || '').trim();
                    const excludedStepCodes = new Set(['finance_officer', 'reviewer_head']);
                    const steps = (Array.isArray(row?.approval_steps) ? row.approval_steps : [])
                        .map((step) => ({
                            code: String(step?.step_code || '').trim().toLowerCase(),
                            name: String(step?.step_name || step?.step_code || '').trim() || 'ขั้นตอน',
                            status: String(step?.status || 'pending').trim().toLowerCase(),
                            order: Number(step?.step_order || 0),
                            approvedAt: String(step?.approved_at || '').trim(),
                        }))
                        .filter((step) => step.name !== '' && !excludedStepCodes.has(step.code))
                        .sort((a, b) => a.order - b.order);
                    if (rawStatus === 'saved' || rawStatus === 'cancelled' || steps.length === 0) {
                        const emptyLabel = rawStatus === 'cancelled'
                            ? 'รายการนี้ถูกยกเลิกแล้ว'
                            : (rawStatus === 'saved' ? 'ยังไม่เข้าสู่วงอนุมัติ — บันทึกเป็นร่าง' : 'ยังไม่มีขั้นตอนอนุมัติ');
                        return `<div class="h-full rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3 py-2 text-center text-[11px] font-semibold text-slate-500">${esc(emptyLabel)}</div>`;
                    }
                    const currentIndex = steps.findIndex((step) => step.status !== 'approved' && step.status !== 'skipped');
                    const allDone = currentIndex < 0;
                    const remaining = allDone ? 0 : steps.slice(currentIndex).filter((step) => step.status !== 'approved' && step.status !== 'skipped').length;
                    const currentName = allDone ? 'อนุมัติครบทุกขั้นตอน' : steps[currentIndex].name;
                    const summary = allDone ? 'อนุมัติครบทุกขั้นตอนแล้ว' : `ถึงขั้นตอน: ${currentName} · เหลือ ${remaining} ขั้นตอน`;
                    return `<div class="h-full w-full rounded-xl border border-slate-200/80 bg-gradient-to-br from-slate-50/80 via-white to-emerald-50/30 p-3">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ความคืบหน้าการอนุมัติ</span>
                            <span class="text-[11px] font-semibold ${allDone ? 'text-emerald-700' : 'text-amber-700'}">${esc(summary)}</span>
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
                                    const subText = isRejected ? 'ส่งกลับแก้ไข' : (isDone ? 'ผ่านแล้ว' : (isCurrent ? 'กำลังดำเนินการ' : 'รอคิว'));
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
                                        <span class="mt-1 line-clamp-2 min-h-[2rem] text-[11px] font-bold leading-4 ${textClass}">${esc(step.name)}</span>
                                        <span class="mt-0.5 rounded-full px-2 py-0.5 text-[10px] font-semibold ${subClass}">${subText}</span>
                                        ${approvedAtText !== '' ? `<span class="mt-0.5 text-[10px] font-medium leading-4 text-emerald-700">${esc(approvedAtText)}</span>` : ''}
                                    </div>`;
                                }).join('')}
                            </div>
                        </div>
                    </div>`;
                };
                let travelRecordRows = [];
                const loadRecords = async () => {
                    const result = await fetch('/withdraw/api/travel/records', { headers: { Accept: 'application/json' } }).then((r) => r.json());
                    const items = Array.isArray(result?.data?.items) ? result.data.items : [];
                    travelRecordRows = items;
                    if (!items.length) {
                        recordEl.innerHTML = emptyState('ยังไม่มีรายการเบิกจ่าย');
                        return;
                    }
                    recordEl.innerHTML = items.map((row) => {
                        const rawStatus = String(row.raw_status || '').trim();
                        const status = String(row.status || '-');
                        const statusClass = travelStatusClassMap[status] || 'bg-slate-100 text-slate-600 ring-slate-200';
                        const amount = Number(row.amount || 0).toLocaleString('th-TH');
                        const canProceed = rawStatus === 'saved' || rawStatus === 'draft';
                        const canCancelRecord = ['saved', 'draft', 'pending_primary_review', 'processing', 'pending_additional_review', 'pending_finance_review', 'pending_approval', 'pending_revision'].includes(rawStatus);
                        const hasProceeded = !['', 'saved', 'draft', 'cancelled'].includes(rawStatus);
                        const viewButton = `<button type="button" class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-[11px] font-semibold shadow-sm" style="background-color:#ffffff;color:#334155;border:1px solid #cbd5e1;" data-travel-view="${Number(row.id || 0)}"><i class="fas fa-eye text-[11px]"></i>ดูรายละเอียด</button>`;
                        const proceedButton = canProceed
                            ? `<button type="button" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold" style="background-color:#ecfdf5;color:#047857;border:1px solid #a7f3d0;" data-travel-proceed="${Number(row.id || 0)}" data-subject="${esc(row.subject || '-')}" data-amount="${esc(amount)}"><i class="fas fa-circle-play text-[10px]"></i>ดำเนินการเบิกจ่าย</button>`
                            : '';
                        const cancelButton = canCancelRecord
                            ? `<button type="button" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold" style="background-color:#fff1f2;color:#be123c;border:1px solid #fecdd3;" data-travel-cancel-record="${Number(row.id || 0)}"><i class="fas fa-ban text-[10px]"></i>ยกเลิกรายการ</button>`
                            : '';
                        const deleteButton = isTravelAdmin
                            ? `<button type="button" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold" style="background-color:#fff1f2;color:#be123c;border:1px solid #fecdd3;" data-travel-delete="${Number(row.id || 0)}"><i class="fas fa-trash text-[10px]"></i>ลบ</button>`
                            : '';
                        const memoPdfButton = hasProceeded
                            ? `<button type="button" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold" style="background-color:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;" data-travel-memo="${Number(row.id || 0)}"><i class="fas fa-file-pdf text-[10px]"></i>บันทึกข้อความ</button>`
                            : '';
                        const financeEsignCount = Number(row.finance_esign_count || 0);
                        const esignDocsButton = hasProceeded && financeEsignCount > 0
                            ? `<button type="button" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold" style="background-color:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe;" data-travel-esign="${Number(row.id || 0)}"><i class="fas fa-file-signature text-[10px]"></i>เอกสารลงนาม</button>`
                            : '';
                        return `<article class="group relative w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-md shadow-slate-200/50 ring-1 ring-white/80">
                            <div class="relative flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100/90 px-2.5 py-1 text-[10px] font-bold tracking-wide text-slate-600 ring-1 ring-slate-200/80">
                                        <i class="fas fa-hashtag text-[10px]"></i>
                                        เลขที่ ${esc(row.code || '-')}
                                    </div>
                                    <h6 class="mt-2 text-[15px] font-extrabold leading-6 text-slate-900">${esc(row.subject || '-')}</h6>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-100">${esc(row.type || 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน')}</span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ${statusClass}">${esc(status)}</span>
                                </div>
                            </div>
                            <div class="relative mt-3 flex w-full flex-col gap-3 lg:flex-row lg:items-stretch">
                                <div class="grid w-full grid-cols-1 gap-3 rounded-xl border border-slate-200/80 bg-gradient-to-br from-slate-50/70 to-white p-3 text-sm sm:grid-cols-3 lg:max-w-none lg:flex-[0.9]">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">วันที่เบิกจ่าย</div>
                                        <div class="mt-1 font-semibold text-slate-800">${esc(row.date || '-')}</div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ผู้ขอเบิก</div>
                                        <div class="mt-1 truncate font-semibold text-slate-800">${esc(row.manager || '-')}</div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">จำนวนเงิน</div>
                                        <div class="mt-1 text-base font-extrabold text-slate-900">${esc(amount)} <span class="text-[11px] font-semibold text-slate-500">บาท</span></div>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-[1.4]">
                                    ${buildApprovalTimeline(row)}
                                </div>
                            </div>
                            <div class="relative mt-3 flex flex-wrap items-center justify-end gap-2">${viewButton}${proceedButton}${cancelButton}${memoPdfButton}${esignDocsButton}${deleteButton}</div>
                        </article>`;
                    }).join('');
                };

                let pendingTravelProceedId = 0;
                let pendingTravelRecordCancelId = 0;
                let pendingTravelDeleteId = 0;
                const setTravelRecordActionText = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) element.textContent = String(value ?? '').trim() || '-';
                };
                const setTravelProceedText = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) element.textContent = String(value ?? '').trim() || '-';
                };
                const travelEsignDocsCanEmbed = () => {
                    if (typeof window.matchMedia !== 'function') return true;
                    return window.matchMedia('(min-width: 1024px)').matches;
                };
                const renderTravelEsignDocsHtml = (files) => files.map((file, index) => {
                    const title = String(file?.item_name || '').trim() || `เอกสารลงนาม e-Sign ${index + 1}`;
                    const url = String(file?.url || '').trim();
                    const previewUrl = url === '' ? '' : `${url}${url.includes('?') ? '&' : '?'}preview=${Date.now()}`;
                    const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0 text-sm font-semibold text-slate-800">${esc(title)}</div>
                        ${url !== '' ? `<a href="${esc(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">เปิดไฟล์</a>` : ''}
                    </div>`;
                    if (url === '' || !travelEsignDocsCanEmbed()) {
                        return `<div class="rounded-xl border border-violet-100 bg-white p-3">${caption}</div>`;
                    }
                    return `<div class="overflow-hidden rounded-xl border border-violet-100 bg-white p-2">${caption}<iframe src="${esc(previewUrl)}" title="${esc(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`;
                }).join('');
                const openTravelMemo = (recordId) => {
                    const row = travelRecordRows.find((item) => Number(item.id || 0) === recordId) || {};
                    const subject = String(row.subject || '').trim();
                    const subjectEl = document.getElementById('travel-memo-subject');
                    const bodyEl = document.getElementById('travel-memo-body');
                    const url = `/withdraw/travel/${recordId}/memo-pdf`;
                    const previewUrl = `${url}?preview=${Date.now()}`;
                    if (subjectEl) subjectEl.textContent = subject !== '' && subject !== '-' ? subject : 'บันทึกข้อความขออนุมัติเบิกค่าใช้จ่ายในการเดินทางไปปฏิบัติงาน';
                    if (bodyEl) {
                        const title = 'บันทึกข้อความ';
                        const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0 text-sm font-semibold text-slate-800">${esc(title)}</div>
                            <a href="${esc(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">เปิดไฟล์</a>
                        </div>`;
                        bodyEl.innerHTML = travelEsignDocsCanEmbed()
                            ? `<div class="overflow-hidden rounded-xl border border-indigo-100 bg-white p-2">${caption}<iframe src="${esc(previewUrl)}" title="${esc(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`
                            : `<div class="rounded-xl border border-indigo-100 bg-white p-3">${caption}</div>`;
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'travel-memo-modal' }));
                };
                const openTravelEsignDocs = async (recordId) => {
                    const row = travelRecordRows.find((item) => Number(item.id || 0) === recordId) || {};
                    const subject = String(row.subject || '').trim();
                    const subjectEl = document.getElementById('travel-esign-docs-subject');
                    const loadingEl = document.getElementById('travel-esign-docs-loading');
                    const emptyEl = document.getElementById('travel-esign-docs-empty');
                    const bodyEl = document.getElementById('travel-esign-docs-body');
                    if (subjectEl) subjectEl.textContent = subject !== '' && subject !== '-' ? subject : 'เอกสารลงนาม e-Sign ที่แนบมา';
                    loadingEl?.classList.remove('hidden');
                    emptyEl?.classList.add('hidden');
                    if (bodyEl) bodyEl.innerHTML = '';
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'travel-esign-docs-modal' }));
                    try {
                        const response = await fetch(`/withdraw/api/salary/${recordId}`, { headers: { Accept: 'application/json' } });
                        const json = await response.json();
                        const files = Array.isArray(json?.data?.finance_esign_files) ? json.data.finance_esign_files : [];
                        const visibleFiles = files.filter((file) => String(file?.url || '').trim() !== '' || String(file?.item_name || '').trim() !== '');
                        loadingEl?.classList.add('hidden');
                        if (json?.status !== 'success' || visibleFiles.length === 0) {
                            emptyEl?.classList.remove('hidden');
                            return;
                        }
                        if (bodyEl) bodyEl.innerHTML = renderTravelEsignDocsHtml(visibleFiles);
                    } catch (error) {
                        loadingEl?.classList.add('hidden');
                        emptyEl?.classList.remove('hidden');
                    }
                };
                recordEl?.addEventListener('click', async (event) => {
                    const viewButton = event.target.closest('[data-travel-view]');
                    if (viewButton) {
                        const viewId = Number(viewButton.getAttribute('data-travel-view') || 0);
                        if (viewId > 0) await window.WithdrawSalaryDetailModal?.openById?.(viewId);
                        return;
                    }
                    const memoButton = event.target.closest('[data-travel-memo]');
                    if (memoButton) {
                        const memoId = Number(memoButton.getAttribute('data-travel-memo') || 0);
                        if (memoId > 0) openTravelMemo(memoId);
                        return;
                    }
                    const esignButton = event.target.closest('[data-travel-esign]');
                    if (esignButton) {
                        const esignId = Number(esignButton.getAttribute('data-travel-esign') || 0);
                        if (esignId > 0) await openTravelEsignDocs(esignId);
                        return;
                    }
                    const cancelRecordButton = event.target.closest('[data-travel-cancel-record]');
                    if (cancelRecordButton) {
                        const cancelId = Number(cancelRecordButton.getAttribute('data-travel-cancel-record') || 0);
                        if (cancelId <= 0) return;
                        const row = travelRecordRows.find((item) => Number(item.id || 0) === cancelId) || {};
                        pendingTravelRecordCancelId = cancelId;
                        setTravelRecordActionText('travel-record-cancel-code', row.code || '-');
                        setTravelRecordActionText('travel-record-cancel-date', row.date || '-');
                        setTravelRecordActionText('travel-record-cancel-subject', row.subject || '-');
                        setTravelRecordActionText('travel-record-cancel-applicant', row.manager || '-');
                        setTravelRecordActionText('travel-record-cancel-amount', Number(row.amount || 0).toLocaleString('th-TH'));
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'travel-record-cancel-confirm-modal' }));
                        return;
                    }
                    const deleteButton = event.target.closest('[data-travel-delete]');
                    if (deleteButton) {
                        if (!isTravelAdmin) return;
                        const deleteId = Number(deleteButton.getAttribute('data-travel-delete') || 0);
                        if (deleteId <= 0) return;
                        const row = travelRecordRows.find((item) => Number(item.id || 0) === deleteId) || {};
                        pendingTravelDeleteId = deleteId;
                        setTravelRecordActionText('travel-delete-code', row.code || '-');
                        setTravelRecordActionText('travel-delete-subject', row.subject || '-');
                        setTravelRecordActionText('travel-delete-amount', Number(row.amount || 0).toLocaleString('th-TH'));
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'travel-delete-confirm-modal' }));
                        return;
                    }
                    const button = event.target.closest('[data-travel-proceed]');
                    if (!button) return;
                    const id = Number(button.getAttribute('data-travel-proceed') || 0);
                    if (id <= 0) return;
                    pendingTravelProceedId = id;
                    const row = travelRecordRows.find((item) => Number(item.id || 0) === id) || {};
                    const fallbackCode = String(row.code || '-').trim() || '-';
                    setTravelProceedText('travel-proceed-code', fallbackCode);
                    setTravelProceedText('travel-proceed-date', row.date || '-');
                    setTravelProceedText('travel-proceed-subject', row.subject || '-');
                    setTravelProceedText('travel-proceed-applicant', row.manager || '-');
                    setTravelProceedText('travel-proceed-amount', Number(row.amount || 0).toLocaleString('th-TH'));
                    try {
                        const detailResponse = await fetch(`/withdraw/api/salary/${id}`, { headers: { Accept: 'application/json' } });
                        const detailJson = await detailResponse.json();
                        const detail = detailJson?.data;
                        if (detailJson?.status === 'success' && detail) {
                            const existingRunningNo = Number(detail.running_no || 0);
                            const existingDisplay = String(detail.document_number_display || '').trim();
                            if (existingRunningNo > 0 && existingDisplay !== '') {
                                setTravelProceedText('travel-proceed-code', existingDisplay);
                            } else {
                                const documentDate = String(detail.document_date || '').trim();
                                const codePrefix = String(detail.document_number || '').trim();
                                const runningNoApiUrl = new URL('/withdraw/api/salary/next-running-no', window.location.origin);
                                runningNoApiUrl.searchParams.set('withdraw_type', 'salary');
                                const departmentCode = String(detail.department_code || '').trim();
                                if (departmentCode !== '') runningNoApiUrl.searchParams.set('department_code', departmentCode);
                                if (documentDate !== '') runningNoApiUrl.searchParams.set('document_date', documentDate);
                                if (codePrefix !== '') runningNoApiUrl.searchParams.set('document_number', codePrefix);
                                const runningResponse = await fetch(runningNoApiUrl.toString(), { headers: { Accept: 'application/json' } });
                                const runningJson = await runningResponse.json();
                                const nextRunningNo = Number(runningJson?.data?.running_no || 0);
                                const centralBase = String(runningJson?.data?.document_number || '').trim();
                                const prefix = centralBase !== '' ? centralBase : codePrefix;
                                if (nextRunningNo > 0 && prefix !== '') {
                                    setTravelProceedText('travel-proceed-code', `${prefix}${nextRunningNo}`);
                                }
                            }
                        }
                    } catch (error) {
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'travel-proceed-confirm-modal' }));
                });
                let pendingTravelCancelToken = '';
                document.getElementById('travel-cancel-dismiss')?.addEventListener('click', () => {
                    pendingTravelCancelToken = '';
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-cancel-confirm-modal' }));
                });
                document.getElementById('travel-cancel-confirm')?.addEventListener('click', async () => {
                    const token = pendingTravelCancelToken;
                    const button = document.getElementById('travel-cancel-confirm');
                    if (token === '') return;
                    if (button) button.disabled = true;
                    try {
                        const cancelled = await fetch(`/withdraw/api/travel/pending/${encodeURIComponent(token)}/cancel`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                        }).then((response) => response.json());
                        if (cancelled?.status !== 'success') throw new Error(cancelled?.message || 'ยกเลิกทำรายการไม่สำเร็จ');
                        window.toast?.success?.(cancelled.message || 'ยกเลิกทำรายการแล้ว');
                        pendingTravelCancelToken = '';
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-cancel-confirm-modal' }));
                        await loadPending();
                    } catch (error) {
                        window.toast?.error?.(error.message || 'ยกเลิกทำรายการไม่สำเร็จ');
                    } finally {
                        if (button) button.disabled = false;
                    }
                });
                document.getElementById('travel-record-cancel-dismiss')?.addEventListener('click', () => {
                    pendingTravelRecordCancelId = 0;
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-record-cancel-confirm-modal' }));
                });
                document.getElementById('travel-record-cancel-confirm')?.addEventListener('click', async () => {
                    const id = pendingTravelRecordCancelId;
                    const button = document.getElementById('travel-record-cancel-confirm');
                    if (id <= 0) return;
                    if (button) button.disabled = true;
                    try {
                        const cancelled = await fetch('/withdraw/api/travel/cancel', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id }),
                        }).then((response) => response.json());
                        if (cancelled?.status !== 'success') throw new Error(cancelled?.message || 'ไม่สามารถยกเลิกรายการได้');
                        window.toast?.success?.(cancelled.message || 'ยกเลิกรายการเบิกจ่ายสำเร็จ');
                        pendingTravelRecordCancelId = 0;
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-record-cancel-confirm-modal' }));
                        await loadRecords();
                    } catch (error) {
                        window.toast?.error?.(error.message || 'ไม่สามารถยกเลิกรายการได้');
                    } finally {
                        if (button) button.disabled = false;
                    }
                });
                document.getElementById('travel-delete-dismiss')?.addEventListener('click', () => {
                    pendingTravelDeleteId = 0;
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-delete-confirm-modal' }));
                });
                document.getElementById('travel-delete-confirm')?.addEventListener('click', async () => {
                    const id = pendingTravelDeleteId;
                    const button = document.getElementById('travel-delete-confirm');
                    if (!isTravelAdmin || id <= 0) return;
                    if (button) button.disabled = true;
                    try {
                        const deleted = await fetch('/withdraw/api/travel/delete', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id }),
                        }).then((response) => response.json());
                        if (deleted?.status !== 'success') throw new Error(deleted?.message || 'ไม่สามารถลบข้อมูลได้');
                        window.toast?.success?.(deleted.message || 'ลบข้อมูลสำเร็จ');
                        pendingTravelDeleteId = 0;
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-delete-confirm-modal' }));
                        await loadRecords();
                    } catch (error) {
                        window.toast?.error?.(error.message || 'ไม่สามารถลบข้อมูลได้');
                    } finally {
                        if (button) button.disabled = false;
                    }
                });
                document.getElementById('travel-proceed-cancel')?.addEventListener('click', () => {
                    pendingTravelProceedId = 0;
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-proceed-confirm-modal' }));
                });
                document.getElementById('travel-proceed-confirm')?.addEventListener('click', async () => {
                    const id = pendingTravelProceedId;
                    const button = document.getElementById('travel-proceed-confirm');
                    if (id <= 0) return;
                    if (button) button.disabled = true;
                    try {
                        const proceeded = await fetch('/withdraw/api/salary/proceed', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id }),
                        }).then((r) => r.json());
                        if (proceeded?.status !== 'success') throw new Error(proceeded?.message || 'ไม่สามารถดำเนินการได้');
                        window.toast?.success?.(proceeded.message || 'ดำเนินการเบิกจ่ายสำเร็จ');
                        pendingTravelProceedId = 0;
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'travel-proceed-confirm-modal' }));
                        await loadRecords();
                    } catch (error) {
                        window.toast?.error?.(error.message || 'ไม่สามารถดำเนินการได้');
                    } finally {
                        if (button) button.disabled = false;
                    }
                });

                const setTravelCancelText = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) element.textContent = String(value ?? '').trim() || '-';
                };
                pendingEl?.addEventListener('click', async (event) => {
                    const cancelButton = event.target.closest('[data-cancel-token]');
                    if (cancelButton) {
                        const cancelToken = cancelButton.getAttribute('data-cancel-token') || '';
                        if (cancelToken === '') return;
                        const row = pendingTravelRows.find((item) => String(item.token || '') === cancelToken) || {};
                        pendingTravelCancelToken = cancelToken;
                        setTravelCancelText('travel-cancel-code', row.leaves_code || '-');
                        setTravelCancelText('travel-cancel-subject', row.subject || 'รายการเดินทาง');
                        setTravelCancelText('travel-cancel-traveler', row.traveler || '-');
                        setTravelCancelText('travel-cancel-amount', money(row.amount));
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'travel-cancel-confirm-modal' }));
                        return;
                    }
                    const button = event.target.closest('[data-open-token]');
                    if (!button) return;
                    const token = button.getAttribute('data-open-token') || '';
                    const result = await fetch(`/withdraw/api/travel/handoff/${encodeURIComponent(token)}`, { headers: { Accept: 'application/json' } }).then((r) => r.json());
                    if (result?.status !== 'success') {
                        window.toast?.error?.(result?.message || 'เปิดรายการไม่สำเร็จ');
                        return;
                    }
                    openForm(result.data || {});
                });

                form?.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const button = document.getElementById('travel-submit');
                    const required = [
                        ['ส่วนงาน', 'travel-department'],
                        ['ที่', 'travel-document-number'],
                        ['วันที่', 'travel-date'],
                        ['เรื่อง', 'travel-subject'],
                        ['เรียน', 'travel-to'],
                        ['ย่อหน้า 1', 'travel-paragraph-1'],
                        ['ย่อหน้า 2', 'travel-paragraph-2'],
                        ['ย่อหน้า 3', 'travel-paragraph-3'],
                        ['ผู้ขอเบิก', 'travel-applicant'],
                    ];
                    const missing = required.find(([, id]) => {
                        const input = document.getElementById(id);
                        if (!input || input.closest('[data-memo-block].hidden, #travel-extra-actors.hidden')) return false;
                        return String(input.value || '').trim() === '';
                    });
                    if (missing) {
                        window.toast?.error?.(`กรุณากรอก${missing[0]}`);
                        return;
                    }
                    const budget = selectedBudget();
                    if (!budget) {
                        window.toast?.error?.('กรุณาเลือกงบประมาณ');
                        return;
                    }
                    const chartOptions = chartOptionsForBudget(budget);
                    const items = [...rowsEl.querySelectorAll('.travel-item')].map((row) => {
                        const chartId = String(row.querySelector('.travel-expense')?.value || '');
                        const chart = chartOptions.find((option) => option.id === chartId);
                        return {
                            name: row.querySelector('.travel-name')?.value || '',
                            amount: row.querySelector('.travel-amount')?.value || '',
                            budget_manage_id: String(budget.id || ''),
                            budget_manage_code: budget.code || '',
                            category_code: chart?.pay_code || budget.category_pay_code || '',
                            expense_chart_id: chartId,
                        };
                    });
                    if (!items.length || items.some((item) => !item.expense_chart_id || !item.category_code || Number(item.amount) <= 0)) {
                        window.toast?.error?.('เลือกผังบัญชีให้ครบทุกรายการจากรายงานการเดินทาง');
                        return;
                    }
                    const actors = [...document.querySelectorAll('.travel-actor-row')].map((row) => ({
                        staff_id: String(row.querySelector('.travel-actor-staff')?.value || '').trim(),
                        status: String(row.querySelector('.travel-actor-status')?.value || '').trim(),
                    })).filter((row) => row.staff_id !== '' || row.status !== '');
                    if (actors.some((actor) => actor.staff_id === '' || actor.status === '')) {
                        window.toast?.error?.('ผู้ตรวจสอบ/อนุมัติ เพิ่มเติม ต้องเลือกทั้งรายชื่อและสถานะดำเนินการ');
                        return;
                    }
                    const documents = [...document.querySelectorAll('#travel-document-subitems input[type="checkbox"]:checked')].map((checkbox) => ({
                        type: String(checkbox.dataset.type || ''),
                        item_id: String(checkbox.dataset.itemId || ''),
                        label: String(checkbox.dataset.label || ''),
                    }));
                    const body = new FormData(form);
                    body.append('salary_request_type', 'travel_leave');
                    body.append('department_code', document.getElementById('travel-department')?.value || '');
                    body.append('document_number', document.getElementById('travel-document-number')?.value || '');
                    body.append('document_date', document.getElementById('travel-date')?.value || '');
                    body.append('subject', document.getElementById('travel-subject')?.value || '');
                    body.append('to', document.getElementById('travel-to')?.value || '');
                    body.append('paragraph1', document.getElementById('travel-paragraph-1')?.value || '');
                    body.append('paragraph2', document.getElementById('travel-paragraph-2')?.value || '');
                    body.append('paragraph3', document.getElementById('travel-paragraph-3')?.value || '');
                    body.append('paragraph4', document.getElementById('travel-paragraph-4')?.value || '');
                    const budgetBreakdown = [];
                    const budgetTotals = new Map();
                    items.forEach((item) => {
                        const id = Number(item.budget_manage_id || 0);
                        const amount = Number(item.amount || 0);
                        if (id <= 0 || !Number.isFinite(amount) || amount <= 0) return;
                        budgetTotals.set(id, (budgetTotals.get(id) || 0) + amount);
                    });
                    budgetTotals.forEach((amount, budgetManageId) => {
                        budgetBreakdown.push({ budget_manage_id: Number(budgetManageId), amount });
                    });
                    body.append('applicant_staff_id', document.getElementById('travel-applicant')?.value || '');
                    body.append('budget_manage_id', String(budgetBreakdown[0]?.budget_manage_id || ''));
                    body.append('budget_manage_ids', JSON.stringify(budgetBreakdown.map((row) => row.budget_manage_id)));
                    body.append('budget_breakdown', JSON.stringify(budgetBreakdown));
                    body.append('items', JSON.stringify(items));
                    body.append('documents', JSON.stringify(documents));
                    body.append('process_actors', JSON.stringify(actors));
                    body.append('travel_handoff_token', document.getElementById('travel-handoff-token')?.value || '');
                    if (button) button.disabled = true;
                    try {
                        const saved = await fetch('/withdraw/api/salary/save-draft', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                            body,
                        }).then((r) => r.json());
                        if (saved?.status !== 'success') throw new Error(saved?.message || 'บันทึกไม่สำเร็จ');
                        window.toast?.success?.(saved.message || 'บันทึกแบบร่างสำเร็จ');
                        closeForm();
                        await loadPending();
                        await loadRecords();
                    } catch (error) {
                        window.toast?.error?.(error.message || 'ทำรายการไม่สำเร็จ');
                    } finally {
                        if (button) button.disabled = false;
                    }
                });

                (async () => {
                    try {
                        await loadOptions();
                        await loadPending();
                        await loadRecords();
                        const token = @json($handoffToken);
                        if (token) {
                            const result = await fetch(`/withdraw/api/travel/handoff/${encodeURIComponent(token)}`, { headers: { Accept: 'application/json' } }).then((r) => r.json());
                            if (result?.status === 'success') openForm(result.data || {});
                            else window.toast?.error?.(result?.message || 'ไม่พบรายการ หรือทำรายการนี้ไปแล้ว');
                        }
                    } catch (error) {
                        pendingEl.innerHTML = emptyState('โหลดรายการไม่สำเร็จ');
                    }
                })();
            });
        </script>
    @endpush
</x-layouts.app>
