<x-layouts.app>
    @php
        $fmtMoney = static function ($n): string {
            $n = (float) $n;
            $decimals = abs($n - round($n)) < 0.0000001 ? 0 : 2;

            return number_format($n, $decimals, '.', ',');
        };
    @endphp
    <div class="py-2">
        {{-- Header (สไตล์เดียวกับหน้า index) --}}
        <div class="mx-2 mb-4 overflow-hidden rounded-[28px] bg-gradient-to-br from-emerald-500/[0.12] via-blue-500/[0.10] via-[40%] to-white/60 shadow-[0_8px_32px_-8px_rgba(15,23,42,0.22),inset_0_1px_0_rgba(255,255,255,0.5)] backdrop-blur-[22px] backdrop-saturate-[1.7]">
            <div class="relative px-5 py-3 sm:px-6 sm:py-4 lg:px-8 lg:py-5">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -top-16 -left-16 h-56 w-56 rounded-full opacity-55 bg-[radial-gradient(circle,rgba(16,185,129,0.40),transparent_65%)] blur-[44px]"></div>
                    <div class="absolute -bottom-20 -right-16 h-52 w-52 rounded-full opacity-50 bg-[radial-gradient(circle,rgba(59,130,246,0.38),transparent_65%)] blur-[44px]"></div>
                    <div class="absolute left-1/3 top-[-10px] h-36 w-72 rounded-full opacity-35 bg-[radial-gradient(circle,rgba(6,182,212,0.30),transparent_65%)] blur-[48px]"></div>
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/[0.85] to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent"></div>
                </div>

                <div class="relative z-[1] flex flex-col gap-3">
                    <a href="{{ route('budget.manage.index') }}"
                        class="inline-flex h-10 w-fit max-w-full shrink-0 items-center gap-2 self-start rounded-2xl border border-slate-900/10 bg-white/70 px-4 text-sm font-extrabold text-slate-600 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-white hover:text-slate-800 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
                        <i class="fas fa-arrow-left shrink-0 text-[12px]"></i>
                        <span class="max-sm:truncate">กลับไปบริหารงบประมาณ</span>
                    </a>

                    <div class="min-w-0">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl !bg-[linear-gradient(135deg,#059669,#2563eb)] text-white shadow-lg shadow-emerald-500/25 ring-1 ring-white/20">
                                <i class="fas fa-wallet text-[13px] drop-shadow-[0_1px_2px_rgba(0,0,0,0.25)]"></i>
                            </span>
                            <h2 class="min-w-0 flex-1 text-[22px] font-black leading-snug tracking-tight text-slate-950 break-words lg:text-[28px]">
                                จัดการงบประมาณ
                            </h2>
                        </div>
                        <p class="mt-2 max-w-[58ch] text-sm leading-relaxed text-slate-600 lg:text-base">
                            <span class="font-bold text-slate-700">รหัสงบ : </span>
                            <span class="ml-1 tabular-nums text-slate-800">{{ $budget->budget_manage_code }}</span>
                        </p>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600 break-words [overflow-wrap:anywhere] lg:text-base">
                            <span class="font-bold text-slate-700">ชื่อแหล่ง : </span>
                            <span class="mt-0.5 block font-normal text-slate-600 sm:mt-0 sm:inline sm:pl-1">{{ $budget->budget_source_name }} ปี {{ $budget->budget_manage_year }}/{{ $budget->budget_plan_name }}/{{ $budget->budget_event_name }}{{ $budget->budget_activity_name ? '-'.$budget->budget_activity_name : '' }}/{{ $budget->budget_fund_name }}{{ $budget->budget_cost_name ? '/'.$budget->budget_cost_name : '' }}/{{ $budget->budget_category_budget_name }}/{{ $budget->budget_category_pay_name }}{{ $budget->budget_category_pay_detail ? '-'.$budget->budget_category_pay_detail : '' }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- เนื้อหาหลัก --}}
        <div class="mx-2 rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-soft-xl backdrop-blur-sm">
            {{-- padding รอบแถวปุ่ม + pt/pb ในกล่องเลื่อน — กัน overflow-x-auto ตัดเงา hover (ไม่ใช้ translate ล้นขอบ) --}}
            <div class="mb-4 flex w-full flex-row flex-nowrap items-center justify-end gap-2 overflow-x-auto px-1 py-3 [-webkit-overflow-scrolling:touch]">
                <x-ui.tooltip-trigger id="btAddBudgetExpensesPlan" as="button" type="button" text="เพิ่มแผนรายการจ่ายในงบนี้"
                    class="group relative inline-flex h-10 w-fit shrink-0 items-center justify-center gap-2 overflow-hidden whitespace-nowrap rounded-2xl !bg-[linear-gradient(to_top_right,#2563eb,#06b6d4,#22d3ee)] px-4 text-sm font-extrabold !text-white shadow-[0_18px_35px_-22px_rgba(37,99,235,0.85)] ring-1 ring-blue-900/10 transition-shadow duration-200 hover:shadow-[0_20px_38px_-20px_rgba(37,99,235,0.95)] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300">
                    <span class="pointer-events-none absolute inset-0 rounded-2xl opacity-0 bg-[radial-gradient(120px_80px_at_30%_20%,rgba(255,255,255,0.30),transparent_60%)] transition-opacity duration-200 group-hover:opacity-100"></span>
                    <span class="relative inline-flex h-7 w-7 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25 transition-transform duration-200 group-hover:rotate-90 group-hover:bg-white/20">
                        <i class="fas fa-plus text-[12px]"></i>
                    </span>
                    <span class="relative">เพิ่มแผนรายการจ่าย</span>
                </x-ui.tooltip-trigger>
                <x-ui.tooltip-trigger id="btAddBudgetManageList" as="button" type="button" text="บันทึกงบเพิ่มเติมหรือปรับงบ (รายการในงบนี้)"
                    class="group relative inline-flex h-10 w-fit shrink-0 items-center justify-center gap-2 overflow-hidden whitespace-nowrap rounded-2xl !bg-[linear-gradient(to_top_right,#059669,#22c55e,#84cc16)] px-4 text-sm font-extrabold !text-white shadow-[0_18px_35px_-22px_rgba(16,185,129,0.95)] ring-1 ring-emerald-900/10 transition-shadow duration-200 hover:shadow-[0_22px_42px_-22px_rgba(16,185,129,1)] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
                    <span class="pointer-events-none absolute inset-0 rounded-2xl opacity-0 bg-[radial-gradient(120px_80px_at_30%_20%,rgba(255,255,255,0.30),transparent_60%)] transition-opacity duration-200 group-hover:opacity-100"></span>
                    <span class="relative inline-flex h-7 w-7 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25 transition-transform duration-200 group-hover:rotate-90 group-hover:bg-white/20">
                        <i class="fas fa-plus text-[12px]"></i>
                    </span>
                    <span class="relative">งบเพิ่มเติม</span>
                </x-ui.tooltip-trigger>
            </div>
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="w-full table-auto text-sm [&_td]:align-top">
                    <thead class="sticky top-0 z-10">
                        <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-700 text-[11px] font-semibold uppercase tracking-wider backdrop-blur">
                            <th class="px-3 py-3 text-center align-middle" rowspan="2">หมวดรายจ่าย</th>
                            <th class="px-3 py-3 text-center bg-blue-50" colspan="3">งบประมาณ (บาท)</th>
                            <th class="px-3 py-3 text-center bg-green-50" colspan="2">แผนการใช้งบ</th>
                            <th class="px-3 py-3 text-center bg-amber-50" colspan="3">การเบิกจ่าย</th>
                        </tr>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-600 text-[11px] font-semibold uppercase tracking-wider">
                            <th class="px-3 py-2 text-center bg-blue-50 whitespace-nowrap">งบประมาณ</th>
                            <th class="px-3 py-2 text-center bg-blue-50 whitespace-nowrap">(+/-)</th>
                            <th class="px-3 py-2 text-center bg-blue-50 whitespace-nowrap">ยอดปัจจุบัน</th>
                            <th class="px-3 py-2 text-center bg-green-50 whitespace-nowrap">จัดแผนแล้ว</th>
                            <th class="px-3 py-2 text-center bg-green-50 whitespace-nowrap">คงเหลือ</th>
                            <th class="px-3 py-2 text-center bg-amber-50 whitespace-nowrap">เบิกจ่าย</th>
                            <th class="px-3 py-2 text-center bg-amber-50 whitespace-nowrap">คงเหลือ</th>
                            <th class="px-3 py-2 text-center bg-amber-50 whitespace-nowrap">รอเบิกจ่าย</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700 divide-y divide-slate-100">
                        {{-- แถวสรุปงบหลัก --}}
                        <tr class="bg-slate-50/80 font-semibold">
                            <td class="px-3 py-3">{{ $budget->budget_category_pay_name }}</td>
                            <td id="main-budget-amount" class="px-3 py-3 text-right bg-blue-50/40 tabular-nums">{{ $fmtMoney((float) $budget->budget_manage_amount) }}</td>
                            <td id="main-budget-delta" class="px-3 py-3 text-right bg-blue-50/40 tabular-nums">{{ $fmtMoney((float) $budget->budget_manage_balance - (float) $budget->budget_manage_amount) }}</td>
                            <td id="main-budget-current" class="px-3 py-3 text-right bg-blue-50/40 tabular-nums">{{ $fmtMoney((float) $budget->budget_manage_balance) }}</td>
                            <td id="main-plan-used" class="px-3 py-3 text-right bg-green-50/40 tabular-nums">0</td>
                            <td id="main-plan-balance" class="px-3 py-3 text-right bg-green-50/40 tabular-nums">0</td>
                            <td id="main-withdraw-amount" class="px-3 py-3 text-right bg-amber-50/40 tabular-nums">
                                @if ((float) $withdrawApproved > 0)
                                    <button type="button" class="js-disbursement-popup tabular-nums underline decoration-dotted underline-offset-4 hover:text-blue-700" data-id="{{ $budget->budget_manage_id }}" data-kind="approved" data-ui-tooltip="ดูรายการ">{{ $fmtMoney((float) $withdrawApproved) }}</button>
                                @else
                                    {{ $fmtMoney((float) $withdrawApproved) }}
                                @endif
                            </td>
                            <td id="main-withdraw-remaining" class="px-3 py-3 text-right bg-amber-50/40 tabular-nums">{{ $fmtMoney((float) $budget->budget_manage_balance - (float) $withdrawApproved) }}</td>
                            <td id="main-withdraw-wait" class="px-3 py-3 text-right bg-amber-50/40 text-orange-700 tabular-nums">
                                @if ((float) $withdrawWait > 0)
                                    <button type="button" class="js-disbursement-popup tabular-nums underline decoration-dotted underline-offset-4 hover:text-blue-700" data-id="{{ $budget->budget_manage_id }}" data-kind="pending" data-ui-tooltip="ดูรายการ">{{ $fmtMoney((float) $withdrawWait) }}</button>
                                @else
                                    {{ $fmtMoney((float) $withdrawWait) }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                    <tbody id="expenses-table" class="text-slate-700 divide-y divide-slate-100"></tbody>
                </table>
            </div>

            {{-- แอคคอร์เดียน --}}
            <div class="mt-6 space-y-4">
                <div x-data="{ open: false }" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 shadow-soft-xl">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-extrabold text-slate-800 transition hover:bg-slate-50/80">
                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><i class="fas fa-history text-[12px]"></i></span>
                            ประวัติรายการงบประมาณ
                        </span>
                        <i class="fas fa-chevron-down text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div x-show="open" x-collapse class="border-t border-slate-200/80 px-4 py-4 sm:px-5">
                        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full text-sm [&_td]:align-top">
                                <thead class="sticky top-0 z-10">
                                    <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-700 text-[11px] font-semibold uppercase tracking-wider backdrop-blur">
                                        <th class="px-3 py-3 text-center whitespace-nowrap">วันที่</th>
                                        <th class="px-3 py-3 text-center whitespace-nowrap">ประเภท</th>
                                        <th class="px-3 py-3 text-center whitespace-nowrap">จำนวน</th>
                                        <th class="px-3 py-3 text-center">รายละเอียด</th>
                                        <th class="px-3 py-3 text-center whitespace-nowrap">ผู้บันทึก</th>
                                        <th class="px-3 py-3 text-center whitespace-nowrap">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="history-table" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div x-data="{ open: false }" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 shadow-soft-xl">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-extrabold text-slate-800 transition hover:bg-slate-50/80">
                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><i class="fas fa-receipt text-[12px]"></i></span>
                            ประวัติรายการเบิกจ่าย
                            @if (! empty($disbursementHistory['groups']))
                                <span class="ml-1 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-bold tabular-nums text-amber-700 ring-1 ring-amber-200">
                                    {{ count($disbursementHistory['groups']) }}
                                </span>
                            @endif
                        </span>
                        <i class="fas fa-chevron-down text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div x-show="open" x-collapse class="border-t border-slate-200/80 px-4 py-4 sm:px-5">
                        @include('budget.manage.partials.disbursement-history', [
                            'disbursementHistory' => $disbursementHistory,
                            'fmtMoney' => $fmtMoney,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-modal name="manage-list-modal" maxWidth="lg" variant="modern" title="เพิ่ม/แก้ไขรายการปรับงบ"
        description="บันทึกการปรับเพิ่มหรือลดงบ พร้อมระบุแหล่งและหมวดรายจ่าย"
        iconClass="fas fa-exchange-alt">
        <form id="manage-list-form" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <input type="hidden" id="ml-id">
                <div>
                    <label for="ml-date" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">วันที่</label>
                    <x-date-picker id="ml-date" placeholder="เลือกวันที่" />
                </div>
                <div>
                    <label for="ml-type" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">ประเภทงบ</label>
                    <x-searchable-dropdown id="ml-type" placeholder="ค้นหาประเภทงบ..."
                        :options="$types" valueKey="budget_type_code" textKey="budget_type_name"
                        :showClear="true" />
                </div>
                <div>
                    <label for="ml-source" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">แหล่งงบ</label>
                    <x-searchable-dropdown id="ml-source" placeholder="ค้นหาแหล่งงบประมาณ..."
                        :options="$sources" valueKey="budget_source_code" textKey="budget_source_name"
                        :showClear="true" />
                </div>
                <div>
                    <label for="ml-category-pay" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">หมวดรายจ่าย</label>
                    <x-searchable-dropdown id="ml-category-pay" placeholder="ค้นหาหมวดรายจ่าย..."
                        :options="$categoryPays" valueKey="budget_category_pay_code" textKey="budget_category_pay_name"
                        :showClear="true" />
                </div>
                <div>
                    <label for="ml-amount" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">จำนวนเงิน</label>
                    <div class="relative">
                        <input id="ml-amount" type="number" step="0.01" inputmode="decimal" placeholder="0.00"
                            class="w-full rounded-2xl border border-slate-300/90 bg-white px-3.5 py-2.5 pr-14 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                        <span
                            class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-xs font-bold text-slate-500">บาท</span>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="ml-detail" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">รายละเอียด</label>
                    <textarea id="ml-detail" rows="3" placeholder="ใส่รายละเอียดเพิ่มเติม เช่น วัตถุประสงค์/หมายเหตุ"
                        class="w-full resize-none rounded-2xl border border-slate-300/90 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
                </div>
                <div class="md:col-span-2 mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="$dispatch('close-modal', 'manage-list-modal')"
                        class="inline-flex h-11 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-600 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-slate-50 sm:w-auto">ยกเลิก</button>
                    <button type="submit"
                        class="group inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl !bg-emerald-600 px-5 text-sm font-extrabold !text-white shadow-[0_18px_35px_-22px_rgba(16,185,129,0.95)] ring-1 !ring-emerald-900/10 transition-all hover:-translate-y-0.5 hover:!bg-emerald-700 sm:w-auto">
                        <i class="fas fa-check text-[12px] opacity-90"></i><span>บันทึก</span>
                    </button>
                </div>
            </form>
    </x-modal>

    <x-modal name="expense-modal" maxWidth="md" variant="modern" title="เพิ่ม/แก้ไขแผนรายการจ่าย"
        description="เลือกรายการจ่ายและจำนวนเงินตามแผน"
        iconClass="fas fa-list-alt">
        <form id="expense-form" class="grid grid-cols-1 gap-4">
                <input type="hidden" id="ex-id">
                <div>
                    <label class="mb-1.5 block text-[11px] font-extrabold text-slate-700">รายการจ่าย</label>
                    <x-searchable-dropdown id="ex-pay" placeholder="พิมพ์หรือเลือกรายการจ่าย..."
                        :options="$payLists" valueKey="budget_pay_code" textKey="budget_pay_name"
                        optionsKey="payListsExpenseModal" :showClear="true" />
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-extrabold text-slate-700">จำนวนเงิน</label>
                    <div class="relative">
                        <input id="ex-amount" type="number" step="0.01" inputmode="decimal" placeholder="0.00"
                            class="w-full rounded-2xl border border-slate-300/90 bg-white px-3.5 py-2.5 pr-14 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                        <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-xs font-bold text-slate-500">บาท</span>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-extrabold text-slate-700">รายละเอียด</label>
                    <textarea id="ex-detail" rows="3" placeholder="ใส่รายละเอียดเพิ่มเติม เช่น วัตถุประสงค์/หมายเหตุ"
                        class="w-full resize-none rounded-2xl border border-slate-300/90 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
                </div>
                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="$dispatch('close-modal', 'expense-modal')"
                        class="inline-flex h-11 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-600 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-slate-50 sm:w-auto">ยกเลิก</button>
                    <button type="submit"
                        class="group inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl !bg-emerald-600 px-5 text-sm font-extrabold !text-white shadow-[0_18px_35px_-22px_rgba(16,185,129,0.95)] ring-1 !ring-emerald-900/10 transition-all hover:-translate-y-0.5 hover:!bg-emerald-700 sm:w-auto">
                        <i class="fas fa-check text-[12px] opacity-90"></i><span>บันทึก</span>
                    </button>
                </div>
            </form>
    </x-modal>

    <x-confirm-modal
        name="manage-list-delete-confirm-modal"
        maxWidth="sm"
        title="ยืนยันการลบรายการปรับงบ?"
        message="คุณต้องการลบรายการนี้หรือไม่ การลบไม่สามารถย้อนกลับได้"
        confirmText="ลบ"
        cancelText="ยกเลิก"
        confirmVariant="danger"
        confirmClick="window.__manageListConfirmDelete()"
    />

    <x-confirm-modal
        name="expense-delete-confirm-modal"
        maxWidth="sm"
        title="ยืนยันการลบแผนรายการจ่าย?"
        message="คุณต้องการลบแผนรายการจ่ายนี้หรือไม่ การลบไม่สามารถย้อนกลับได้"
        confirmText="ลบ"
        cancelText="ยกเลิก"
        confirmVariant="danger"
        confirmClick="window.__expenseConfirmDelete()"
    />

    <x-confirm-modal
        name="expense-sub-delete-confirm-modal"
        maxWidth="sm"
        title="ยืนยันการลบรายการย่อย?"
        message="คุณต้องการลบรายการย่อยนี้หรือไม่ การลบไม่สามารถย้อนกลับได้"
        confirmText="ลบ"
        cancelText="ยกเลิก"
        confirmVariant="danger"
        confirmClick="window.__expenseSubConfirmDelete()"
    />

    @include('budget.manage.partials.disbursement-modal')

    @push('scripts')
        <script>
            (function () {
                const start = () => {
            (() => {
                const manageId = @json($budget->budget_manage_id);
                let budgetBalance = @json((float) $budget->budget_manage_balance);
                const budgetManageAmount = @json((float) $budget->budget_manage_amount);
                const withdrawApproved = @json((float) $withdrawApproved);
                const withdrawWait = @json((float) $withdrawWait);
                const historyPageBudgetRow = {!! json_encode([
                    'budget_manage_list_date' => ($budget->budget_manage_date ?? ''),
                    'budget_type_name' => ($budget->budget_manage_type_name ?? ''),
                    'budget_manage_list_amount' => (float) ($budget->budget_manage_amount ?? 0),
                    'budget_manage_list_detail' => ($budget->budget_manage_detail ?? ''),
                    'budget_manage_list_staff_create_name' => ($budget->budget_manage_staff_create_name ?? '-'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
                const csrf = document.querySelector('meta[name="csrf-token"]').content;

                const expensesTable = document.getElementById('expenses-table');
                const historyTable = document.getElementById('history-table');

                const btnAddList = document.getElementById('btAddBudgetManageList');
                const btnAddExpense = document.getElementById('btAddBudgetExpensesPlan');

                let mlId = null;
                let mlDate = null;
                let mlType = null;
                let mlSource = null;
                let mlCategoryPay = null;
                let mlAmount = null;
                let mlDetail = null;
                let manageListForm = null;

                let exId = null;
                let exPay = null;
                let exAmount = null;
                let exDetail = null;
                let expenseForm = null;

                const refreshModalRefs = () => {
                    mlId = document.getElementById('ml-id');
                    mlDate = document.getElementById('ml-date');
                    mlType = document.getElementById('ml-type');
                    mlSource = document.getElementById('ml-source');
                    mlCategoryPay = document.getElementById('ml-category-pay');
                    mlAmount = document.getElementById('ml-amount');
                    mlDetail = document.getElementById('ml-detail');
                    manageListForm = document.getElementById('manage-list-form');

                    exId = document.getElementById('ex-id');
                    exPay = document.getElementById('ex-pay');
                    exAmount = document.getElementById('ex-amount');
                    exDetail = document.getElementById('ex-detail');
                    expenseForm = document.getElementById('expense-form');
                };

                const setExpensesOptions = @json($setExpenses->map(fn ($x) => ['code' => (string) $x->budget_set_expenses_code, 'name' => $x->budget_set_expenses_name, 'payCode' => (string) ($x->budget_pay_code ?? '')])->values());
                const monthLabelsSub = ['ต.ค.', 'พ.ย.', 'ธ.ค.', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.'];

                let historyRows = [];
                let expenseRows = [];
                let pendingManageListDeleteId = 0;
                let pendingExpenseDeleteId = 0;
                let pendingExpenseSubDeleteId = 0;

                const money = (v) => {
                    const n = Number(v || 0);
                    const isWhole = Number.isFinite(n) && Math.abs(n - Math.round(n)) < 1e-9;
                    return n.toLocaleString(undefined, {
                        minimumFractionDigits: isWhole ? 0 : 2,
                        maximumFractionDigits: 2
                    });
                };
                const thaiDate = (value) => {
                    const raw = String(value || '').trim();
                    if (!raw) return '-';
                    const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
                    if (!m) return raw;
                    const y = Number(m[1]);
                    const month = Number(m[2]);
                    const d = Number(m[3]);
                    const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                    if (!y || month < 1 || month > 12 || !d) return raw;
                    const displayYear = y >= 2400 ? y : y + 543;
                    return `${d} ${monthNames[month - 1]} ${displayYear}`;
                };

                const post = (url, payload) => fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(payload),
                }).then(r => r.json());

                const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

                const toastOk = (msg) => {
                    if (window.toast?.success) window.toast.success(msg, { timer: 3000 });
                    else alert(msg);
                };
                const toastFail = (msg) => {
                    if (window.toast?.error) window.toast.error(msg, { timer: 4000 });
                    else alert(msg);
                };
                refreshModalRefs();

                const setValueAndDispatch = (el, value) => {
                    if (!el) return;
                    el.value = value ?? '';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const setDateValue = (el, value) => {
                    if (!el) return;
                    // Flatpickr instance is attached to the original input element.
                    try {
                        if (el._flatpickr && typeof el._flatpickr.setDate === 'function') {
                            el._flatpickr.setDate(value || '', true);
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                            return;
                        }
                    } catch (e) {
                        // Fallback to manual assignment below.
                    }
                    setValueAndDispatch(el, value || '');
                };

                const wireMonthTotal = (formRow) => {
                    const totalEl = formRow.querySelector('.total-amount');
                    const inputs = formRow.querySelectorAll('.month-input');
                    const recalc = () => {
                        let total = 0;
                        inputs.forEach((i) => { total += parseFloat(i.value || 0); });
                        if (totalEl) totalEl.textContent = money(total);
                    };
                    inputs.forEach((i) => i.addEventListener('input', recalc));
                    recalc();
                };

                const safeJsonForScript = (value) => JSON.stringify(value).replace(/<\//g, '<\\/');
                const buildInlineSearchableDropdown = ({ id, options, selectedValue = '', placeholder = '' }) => {
                    const optionsJson = safeJsonForScript(options ?? []);
                    // Match markup expected by `resources/views/components/searchable-dropdown.blade.php`
                    return `
                        <div class="relative isolate pbm-searchable-select-wrapper flex-1 min-w-0"
                            data-pbm-searchable-select-wrapper="1"
                            data-pbm-options-key=""
                            data-pbm-value-key="code"
                            data-pbm-text-key="name">
                            <input id="${id}-text" type="search" placeholder="${escapeHtml(placeholder)}"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                autocomplete="off" data-pbm-searchable-select-text />
                            <div id="${id}-suggest"
                                class="absolute left-0 right-0 top-full !z-[9999999] hidden max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white py-1 shadow-2xl"
                                data-pbm-searchable-select-suggest></div>
                            <input type="hidden" id="${id}" value="${escapeHtml(selectedValue)}" data-pbm-searchable-select-hidden />
                            <script type="application/json" class="pbm-searchable-select__options" data-options-key="">
                                ${optionsJson}
                            <\/script>
                        </div>
                    `;
                };

                const buildExpenseFormRowAdd = (expenseId, payCode) => {
                    let monthInputs = '';
                    for (let i = 1; i <= 12; i++) {
                        monthInputs += `<td class="px-1 py-1"><input type="number" step="0.01" min="0" name="budgetManageExpensesSubMonth${i}" class="month-input w-full rounded-2xl border border-slate-300/90 bg-white px-2.5 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" value="0"></td>`;
                    }
                    const filteredOptions = setExpensesOptions.filter((o) => String(o.payCode ?? '') === String(payCode ?? ''));
                    const optionsForDropdown = filteredOptions.length ? filteredOptions : setExpensesOptions;
                    const dropdownId = `sub-expense-name-add-${expenseId}`;
                    const dropdownHtml = buildInlineSearchableDropdown({
                        id: dropdownId,
                        options: optionsForDropdown.map((o) => ({ code: o.code, name: o.name })),
                        selectedValue: '',
                        placeholder: 'พิมพ์หรือเลือกรายการ...',
                    });
                    const tr = document.createElement('tr');
                    tr.className = 'sub-expenses-form bg-slate-50/90';
                    tr.innerHTML = `<td colspan="99" class="p-4 border-t border-slate-200">
                        <form id="frmAddManageExpensesSub-${expenseId}">
                            <input type="hidden" name="budgetManageExpensesSubId" value="0">
                            <input type="hidden" name="budgetManageExpensesId" value="${expenseId}">
                            <div class="mb-3 flex flex-wrap items-end gap-3">
                                <div class="flex w-[680px] max-w-full items-center gap-2">
                                    <label class="shrink-0 text-xs font-bold text-slate-700 whitespace-nowrap">ชื่อรายจ่าย</label>
                                    ${dropdownHtml}
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[640px] text-center text-xs">
                                    <thead>
                                        <tr><th colspan="12" class="px-2 py-2 bg-slate-100">งบประมาณรายเดือน (ต.ค. – ก.ย.)</th></tr>
                                        <tr>${monthLabelsSub.map((m) => `<th class="px-1 py-1 bg-slate-50">${m}</th>`).join('')}</tr>
                                    </thead>
                                    <tbody><tr>${monthInputs}</tr></tbody>
                                </table>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm text-slate-600">รวมทั้งปี: <strong><span class="total-amount">0</span> บาท</strong></div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="btSaveBudgetExpensesSub inline-flex h-10 items-center gap-2 rounded-2xl !bg-emerald-600 px-4 text-sm font-extrabold !text-white shadow-[0_14px_28px_-18px_rgba(16,185,129,0.95)] ring-1 !ring-emerald-900/10 transition-all hover:-translate-y-0.5 hover:!bg-emerald-700" data-id="${expenseId}">
                                        <i class="fas fa-save text-[12px] opacity-90"></i><span>บันทึกรายจ่าย</span>
                                    </button>
                                    <button type="button" class="btCancelSaveBudgetExpensesSub inline-flex h-10 items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-600 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-slate-50">
                                        <i class="fas fa-times text-[12px]"></i><span>ยกเลิก</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </td>`;
                    return tr;
                };

                const buildExpenseFormRowEdit = (s, expenseId, payCode) => {
                    const subId = s.budget_expenses_sub_id;
                    let monthInputs = '';
                    for (let i = 1; i <= 12; i++) {
                        const v = Number(s[`budget_expenses_sub_month_${i}`] ?? 0);
                        monthInputs += `<td class="px-1 py-1"><input type="number" step="0.01" min="0" name="budgetManageExpensesSubMonth${i}" class="month-input w-full rounded-2xl border border-slate-300/90 bg-white px-2.5 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" value="${v}"></td>`;
                    }
                    const codeStr = String(s.budget_expenses_sub_code ?? '');
                    const filteredOptions = setExpensesOptions.filter((o) => String(o.payCode ?? '') === String(payCode ?? ''));
                    let optionsForDropdown = filteredOptions.length ? filteredOptions : setExpensesOptions;
                    // กรณีข้อมูลเก่าไม่ตรง payCode ปัจจุบัน ให้แสดงรายการเดิมเพื่อไม่ให้ค่า selected หาย
                    if (!optionsForDropdown.some((o) => String(o.code) === codeStr)) {
                        optionsForDropdown = setExpensesOptions;
                    }
                    const dropdownId = `sub-expense-name-edit-${subId}`;
                    const dropdownHtml = buildInlineSearchableDropdown({
                        id: dropdownId,
                        options: optionsForDropdown.map((o) => ({ code: o.code, name: o.name })),
                        selectedValue: codeStr,
                        placeholder: 'พิมพ์หรือเลือกรายการ...',
                    });
                    const tr = document.createElement('tr');
                    tr.className = 'sub-expenses-form bg-amber-50/40';
                    tr.innerHTML = `<td colspan="99" class="p-4 border-t border-slate-200">
                        <form id="frmEditManageExpensesSub-${subId}">
                            <input type="hidden" name="budgetManageExpensesSubId" value="${subId}">
                            <input type="hidden" name="budgetManageExpensesId" value="${expenseId}">
                            <div class="mb-3 flex flex-wrap items-end gap-3">
                                <div class="flex w-[680px] max-w-full items-center gap-2">
                                    <label class="shrink-0 text-xs font-bold text-slate-700 whitespace-nowrap">ชื่อรายจ่าย</label>
                                    ${dropdownHtml}
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[640px] text-center text-xs">
                                    <thead>
                                        <tr><th colspan="12" class="px-2 py-2 bg-slate-100">งบประมาณรายเดือน (ต.ค. – ก.ย.)</th></tr>
                                        <tr>${monthLabelsSub.map((m) => `<th class="px-1 py-1 bg-slate-50">${m}</th>`).join('')}</tr>
                                    </thead>
                                    <tbody><tr>${monthInputs}</tr></tbody>
                                </table>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm text-slate-600">รวมทั้งปี: <strong><span class="total-amount">${money(s.budget_expenses_sub_total || 0)}</span> บาท</strong></div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="btUpdateBudgetExpensesSub inline-flex h-10 items-center gap-2 rounded-2xl !bg-emerald-600 px-4 text-sm font-extrabold !text-white shadow-[0_14px_28px_-18px_rgba(16,185,129,0.95)] ring-1 !ring-emerald-900/10 transition-all hover:-translate-y-0.5 hover:!bg-emerald-700" data-id="${subId}">
                                        <i class="fas fa-save text-[12px] opacity-90"></i><span>บันทึกการแก้ไข</span>
                                    </button>
                                    <button type="button" class="btCancelUpdateBudgetExpensesSub inline-flex h-10 items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-600 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-slate-50">
                                        <i class="fas fa-times text-[12px]"></i><span>ยกเลิก</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </td>`;
                    return tr;
                };

                const submitSubForm = async (form) => {
                    const fd = new FormData(form);
                    const subIdHint = Number(fd.get('budgetManageExpensesSubId') || 0);
                    const selectedSubValue = String(form.querySelector('input[data-pbm-searchable-select-hidden]')?.value || '').trim();
                    const typedSubText = String(form.querySelector('input[data-pbm-searchable-select-text]')?.value || '').trim();
                    const selectedSubName = selectedSubValue || typedSubText;
                    const payload = {
                        budgetManageExpensesSubId: subIdHint,
                        budgetManageId: Number(manageId),
                        budgetManageExpensesId: Number(fd.get('budgetManageExpensesId') || 0),
                        budgetManageExpensesSubName: selectedSubName,
                    };
                    for (let i = 1; i <= 12; i++) {
                        const raw = fd.get(`budgetManageExpensesSubMonth${i}`);
                        const n = Number(raw);
                        payload[`budgetManageExpensesSubMonth${i}`] = Number.isFinite(n) ? n : 0;
                    }
                    const rs = await post(@json(route('budget.api.expense-sub.save')), payload);
                    if (rs.status !== 'success') {
                        toastFail(rs.message || 'บันทึกไม่สำเร็จ');
                        return;
                    }
                    toastOk('บันทึกรายการย่อยสำเร็จ');
                    await loadExpenses();
                };

                const historyRowHtml = (x, { isPageBudget = false } = {}) => `
                            <tr class="${isPageBudget ? 'bg-emerald-50/50 font-semibold' : 'odd:bg-white even:bg-slate-50/40'} hover:bg-slate-100/60 transition-colors">
                                <td class="px-3 py-3 text-slate-700">${thaiDate(x.budget_manage_list_date)}</td>
                                <td class="px-3 py-3 text-slate-700">${isPageBudget ? `<span class="inline-flex flex-col items-start gap-0.5"><span class="font-semibold text-slate-800">${escapeHtml(x.budget_type_name) || '—'}</span></span>` : (x.budget_type_name || '-')}</td>
                                <td class="px-3 py-3 text-right tabular-nums">${money(x.budget_manage_list_amount)}</td>
                                <td class="px-3 py-3 text-xs text-slate-700">${escapeHtml(x.budget_manage_list_detail) || '-'}</td>
                                <td class="px-3 py-3 text-slate-700 whitespace-nowrap">${escapeHtml(x.budget_manage_list_staff_create_name) || '-'}</td>
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    ${isPageBudget ? '<span class="text-xs text-slate-400">—</span>' : `
                                    <div class="inline-flex items-center gap-1">
                                        <button data-edit-list='${JSON.stringify(x)}' type="button" data-ui-tooltip="แก้ไขรายการปรับงบ" aria-label="แก้ไขรายการปรับงบ" class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs font-semibold text-amber-700 shadow-sm hover:bg-amber-100"><i class="fas fa-edit"></i></button>
                                        <button data-delete-list="${x.budget_manage_list_id}" type="button" data-ui-tooltip="ลบรายการปรับงบ" aria-label="ลบรายการปรับงบ" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-2 text-xs font-semibold text-red-700 shadow-sm hover:bg-red-100"><i class="fas fa-trash"></i></button>
                                    </div>`}
                                </td>
                            </tr>`;

                const loadHistory = async () => {
                    try {
                        const url = @json(route('budget.api.manage.history', ['budgetManageId' => $budget->budget_manage_id]));
                        const json = await fetch(`${url}?_=${Date.now()}`, { cache: 'no-store' }).then(r => r.json());
                        historyRows = json?.data?.items || [];
                        const firstRow = historyRowHtml(historyPageBudgetRow, { isPageBudget: true });
                        if (historyRows.length === 0) {
                            historyTable.innerHTML = firstRow;
                            return;
                        }
                        historyTable.innerHTML = firstRow + historyRows.map((x) => historyRowHtml(x)).join('');

                        // Bind edit/delete for history
                        historyTable.querySelectorAll('[data-edit-list]').forEach((b) => {
                            b.addEventListener('click', () => {
                                refreshModalRefs();
                                const x = JSON.parse(b.dataset.editList);
                                if (mlId) mlId.value = x.budget_manage_list_id;
                                if (mlDate) setDateValue(mlDate, x.budget_manage_list_date || '');
                                if (mlType) setValueAndDispatch(mlType, x.budget_type_code || '');
                                if (mlSource) setValueAndDispatch(mlSource, x.budget_source_code || '');
                                if (mlCategoryPay) setValueAndDispatch(mlCategoryPay, x.budget_category_pay_code || '');
                                if (mlAmount) mlAmount.value = x.budget_manage_list_amount || '';
                                if (mlDetail) mlDetail.value = x.budget_manage_list_detail || '';
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manage-list-modal' }));
                            });
                        });

                        historyTable.querySelectorAll('[data-delete-list]').forEach((b) => {
                            b.addEventListener('click', async () => {
                                pendingManageListDeleteId = Number(b.dataset.deleteList || 0);
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manage-list-delete-confirm-modal' }));
                            });
                        });
                    } catch (e) {
                        console.error(e);
                        toastFail('โหลดรายการปรับงบไม่สำเร็จ');
                    }
                };

                const pbmProjectsUrl = @json(route('budget.api.manage.pbm-projects', ['budgetManageId' => $budget->budget_manage_id]));

                const renderMonthlyPlanTable = (expenseId, planRows, { hidden = true } = {}) => {
                    if (!planRows.length) return '';

                    const monthTotals = Array.from({ length: 12 }, () => 0);
                    let allTotal = 0;
                    const monthHeader = monthLabelsSub.map((m) => `<th class="px-2 py-2 text-right bg-slate-50 whitespace-nowrap">${m}</th>`).join('');

                    const rowsHtml = planRows.map((row) => {
                        const months = Array.isArray(row.months) ? row.months : [];
                        let monthCells = '';
                        for (let i = 0; i < 12; i++) {
                            const v = Number(months[i] || 0);
                            monthTotals[i] += v;
                            monthCells += `<td class="px-2 py-2 text-right tabular-nums whitespace-nowrap">${money(v)}</td>`;
                        }
                        const rowTotal = Number(row.total || 0);
                        allTotal += rowTotal;
                        const displayName = row.isProject
                            ? `โครงการ ${row.name || '-'}`
                            : (row.name || '-');
                        const nameHtml = row.code
                            ? `<div class="font-semibold text-slate-800">${escapeHtml(displayName)}</div><span class="mt-0.5 block text-[11px] font-medium text-slate-500">${escapeHtml(row.code)}</span>`
                            : escapeHtml(displayName);
                        const actions = row.localSub
                            ? `<span class="ml-2 inline-flex items-center gap-2">
                                    <button type="button" class="editBudgetExpensesPlanSub inline-flex h-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 text-xs font-semibold text-amber-700 shadow-sm hover:bg-amber-100" data-id="${row.localSub.budget_expenses_sub_id}" data-expense-id="${expenseId}" data-ui-tooltip="แก้ไขรายจ่ายย่อย" aria-label="แก้ไขรายจ่ายย่อย"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="deleteBudgetExpensesPlanSub inline-flex h-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-2.5 text-xs font-semibold text-red-700 shadow-sm hover:bg-red-100" data-id="${row.localSub.budget_expenses_sub_id}" data-expense-id="${expenseId}" data-ui-tooltip="ลบรายจ่ายย่อย" aria-label="ลบรายจ่ายย่อย"><i class="fas fa-trash"></i></button>
                                </span>`
                            : '';
                        return `
                            <tr class="border-b border-slate-100 last:border-b-0">
                                <td class="px-3 py-2 text-slate-700">
                                    ${nameHtml}
                                    ${actions}
                                </td>
                                ${monthCells}
                                <td class="px-2 py-2 text-right tabular-nums font-semibold whitespace-nowrap">${money(rowTotal)}</td>
                            </tr>
                        `;
                    }).join('');

                    const sumCells = monthTotals.map((v) => `<th class="px-2 py-2 text-right tabular-nums whitespace-nowrap">${money(v)}</th>`).join('');
                    return `
                        <tr data-sub-parent="${expenseId}" ${hidden ? 'style="display:none"' : ''} class="bg-slate-50/50">
                            <td colspan="9" class="px-4 py-3">
                                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                    <table class="w-full min-w-[1220px] text-xs">
                                        <thead>
                                            <tr class="border-b border-slate-200 text-slate-700">
                                                <th class="px-3 py-2 text-left whitespace-nowrap">รายจ่าย</th>
                                                ${monthHeader}
                                                <th class="px-2 py-2 text-right whitespace-nowrap">รวม</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-slate-700">${rowsHtml}</tbody>
                                        <tfoot>
                                            <tr class="border-t border-slate-200 bg-slate-100/70 text-slate-800">
                                                <th class="px-3 py-2 text-left whitespace-nowrap">รวม</th>
                                                ${sumCells}
                                                <th class="px-2 py-2 text-right tabular-nums whitespace-nowrap">${money(allTotal)}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    `;
                };

                const localSubToPlanRow = (s) => {
                    const months = [];
                    for (let i = 1; i <= 12; i++) {
                        months.push(Number(s[`budget_expenses_sub_month_${i}`] || 0));
                    }
                    return {
                        name: s.budget_expenses_sub_name || '-',
                        code: '',
                        months,
                        total: Number(s.budget_expenses_sub_total || 0),
                        localSub: s,
                    };
                };

                const renderSubRows = async (expenseId, pbmProjects = []) => {
                    const json = await fetch(`${@json(url('/budget/api/expense'))}/${expenseId}/subs?_=${Date.now()}`, { cache: 'no-store' }).then(r => r.json());
                    const subs = json?.data || [];
                    const pbmRows = (pbmProjects || []).map((p) => ({
                        name: p.name || p.label || '-',
                        code: p.code || '',
                        isProject: true,
                        months: Array.isArray(p.months) ? p.months : [],
                        total: Number(p.total || 0),
                    }));
                    const localRows = subs.map(localSubToPlanRow);
                    const planRows = [...pbmRows, ...localRows];
                    if (!planRows.length) return { subs, html: '', planTotal: 0 };

                    const planTotal = planRows.reduce((sum, row) => sum + Number(row.total || 0), 0);
                    return {
                        subs,
                        html: renderMonthlyPlanTable(expenseId, planRows, { hidden: pbmRows.length === 0 }),
                        planTotal,
                    };
                };

                const loadExpenses = async () => {
                    try {
                        const url = @json(route('budget.api.manage.expenses', ['budgetManageId' => $budget->budget_manage_id]));
                        const [json, pbmJson] = await Promise.all([
                            fetch(`${url}?_=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()),
                            fetch(`${pbmProjectsUrl}?_=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()).catch(() => null),
                        ]);
                    expenseRows = json?.data || [];
                    const pbmProjects = pbmJson?.data?.projects || [];

                    let html = '';
                    let totalPlanUsed = 0;
                    let pbmAttached = false;

                    for (const x of expenseRows) {
                        const attachPbm = !pbmAttached && pbmProjects.length > 0;
                        const { subs, html: subHtml, planTotal } = await renderSubRows(
                            x.budget_expenses_id,
                            attachPbm ? pbmProjects : []
                        );
                        if (attachPbm) pbmAttached = true;
                        const subTotal = Number(planTotal || 0);
                        const hasSubs = (subs.length > 0) || (attachPbm && pbmProjects.length > 0);
                        totalPlanUsed += subTotal;

                        const expAmount = Number(x.budget_expenses_amount || 0);
                        const planBalance = expAmount - subTotal;
                        const isOverBudget = subTotal > expAmount;
                        const isOverBalance = subTotal > budgetBalance;

                        html += `
                            <tr class="odd:bg-white even:bg-slate-50/40 hover:bg-slate-100/60 transition-colors">
                                <td class="px-3 py-3 text-right text-slate-700" colspan="4">
                                    <span class="font-semibold">${x.budget_pay_name || '-'}</span>
                                    (<span class="tabular-nums ${isOverBalance ? 'text-red-600 font-bold' : ''}">${money(expAmount)}</span>)
                                    <span class="mr-2 inline-flex flex-wrap items-center gap-1">
                                        ${hasSubs ? `
                                            <button type="button" data-id="${x.budget_expenses_id}" class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-100 detailBudgetExpensesPlanSub" data-ui-tooltip="ดูรายละเอียดรายจ่ายย่อย" aria-label="ดูรายละเอียดรายจ่ายย่อย">
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                        ` : ''}
                                        <button type="button" data-id="${x.budget_expenses_id}" data-paycode="${x.budget_pay_code}" data-amount="${expAmount}" class="addBudgetExpensesPlanSub inline-flex h-8 items-center justify-center rounded-lg border border-green-200 bg-green-50 px-2.5 text-xs font-semibold text-green-700 shadow-sm hover:bg-green-100" data-ui-tooltip="เพิ่มรายจ่ายย่อย" aria-label="เพิ่มรายจ่ายย่อย"><i class="fas fa-plus"></i></button>
                                        <button type="button" data-edit-exp='${JSON.stringify(x)}' class="inline-flex h-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 text-xs font-semibold text-amber-700 shadow-sm hover:bg-amber-100" data-ui-tooltip="แก้ไขแผนรายการจ่าย" aria-label="แก้ไขแผนรายการจ่าย"><i class="fas fa-edit"></i></button>
                                        <button type="button" data-delete-exp="${x.budget_expenses_id}" class="inline-flex h-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-2.5 text-xs font-semibold text-red-700 shadow-sm hover:bg-red-100" data-ui-tooltip="ลบแผนรายการจ่าย" aria-label="ลบแผนรายการจ่าย"><i class="fas fa-trash"></i></button>
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right bg-green-50/40 tabular-nums ${isOverBudget ? 'text-red-600 font-bold' : ''}">${money(subTotal)}</td>
                                <td class="px-3 py-3 text-right bg-green-50/40 tabular-nums ${planBalance < 0 ? 'text-red-600 font-bold' : ''}">${money(planBalance)}</td>
                                <td class="px-3 py-3 text-right bg-amber-50/40 tabular-nums">0</td>
                                <td class="px-3 py-3 text-right bg-amber-50/40 tabular-nums">${money(expAmount)}</td>
                                <td class="px-3 py-3 text-right bg-amber-50/40 text-orange-700 tabular-nums">0</td>
                            </tr>
                        `;
                        html += subHtml;
                    }

                    expensesTable.innerHTML = html || '';

                    // Update main summary
                    const mainPlanUsed = document.getElementById('main-plan-used');
                    const mainPlanBalance = document.getElementById('main-plan-balance');
                    if (mainPlanUsed) {
                        mainPlanUsed.textContent = money(totalPlanUsed);
                        mainPlanUsed.classList.toggle('text-red-600', totalPlanUsed > budgetBalance);
                        mainPlanUsed.classList.toggle('font-bold', totalPlanUsed > budgetBalance);
                    }
                    if (mainPlanBalance) {
                        const balance = budgetBalance - totalPlanUsed;
                        mainPlanBalance.textContent = money(balance);
                        mainPlanBalance.classList.toggle('text-red-600', balance < 0);
                        mainPlanBalance.classList.toggle('font-bold', balance < 0);
                    }
                    const mainBudgetAmount = document.getElementById('main-budget-amount');
                    const mainBudgetDelta = document.getElementById('main-budget-delta');
                    const mainBudgetCurrent = document.getElementById('main-budget-current');
                    const mainWithdrawAmount = document.getElementById('main-withdraw-amount');
                    const mainWithdrawRemaining = document.getElementById('main-withdraw-remaining');
                    const mainWithdrawWait = document.getElementById('main-withdraw-wait');
                    const setDisbursementCell = (cell, amount, kind) => {
                        if (!cell) return;
                        const n = Number(amount || 0);
                        const formatted = money(n);
                        if (!(n > 0)) {
                            cell.textContent = formatted;
                            return;
                        }
                        cell.innerHTML = `<button type="button" class="js-disbursement-popup tabular-nums underline decoration-dotted underline-offset-4 hover:text-blue-700" data-id="${manageId}" data-kind="${kind}" data-ui-tooltip="ดูรายการ">${formatted}</button>`;
                    };
                    if (mainBudgetAmount) mainBudgetAmount.textContent = money(budgetManageAmount);
                    if (mainBudgetDelta) mainBudgetDelta.textContent = money(budgetBalance - budgetManageAmount);
                    if (mainBudgetCurrent) mainBudgetCurrent.textContent = money(budgetBalance);
                    setDisbursementCell(mainWithdrawAmount, withdrawApproved, 'approved');
                    if (mainWithdrawRemaining) mainWithdrawRemaining.textContent = money(budgetBalance - withdrawApproved);
                    setDisbursementCell(mainWithdrawWait, withdrawWait, 'pending');
                    } catch (e) {
                        console.error(e);
                        toastFail('โหลดตารางแผนรายการจ่ายไม่สำเร็จ');
                    }
                };

                if (expensesTable) {
                    expensesTable.addEventListener('click', async (e) => {
                        const cancelAdd = e.target.closest('.btCancelSaveBudgetExpensesSub');
                        if (cancelAdd) {
                            cancelAdd.closest('tr.sub-expenses-form')?.remove();
                            return;
                        }
                        const cancelEdit = e.target.closest('.btCancelUpdateBudgetExpensesSub');
                        if (cancelEdit) {
                            cancelEdit.closest('tr.sub-expenses-form')?.remove();
                            return;
                        }

                        const saveAdd = e.target.closest('.btSaveBudgetExpensesSub');
                        if (saveAdd) {
                            const expenseId = Number(saveAdd.dataset.id || 0);
                            const form = document.getElementById(`frmAddManageExpensesSub-${expenseId}`);
                            if (form) await submitSubForm(form);
                            return;
                        }

                        const saveEdit = e.target.closest('.btUpdateBudgetExpensesSub');
                        if (saveEdit) {
                            const subId = Number(saveEdit.dataset.id || 0);
                            const form = document.getElementById(`frmEditManageExpensesSub-${subId}`);
                            if (form) await submitSubForm(form);
                            return;
                        }

                        const addBtn = e.target.closest('.addBudgetExpensesPlanSub');
                        if (addBtn) {
                            const expenseId = Number(addBtn.dataset.id || 0);
                            const payCode = String(addBtn.dataset.paycode ?? '');
                            const parentRow = addBtn.closest('tr');
                            if (!parentRow) return;
                            const next = parentRow.nextElementSibling;
                            if (next && next.classList.contains('sub-expenses-form')) {
                                next.remove();
                                return;
                            }
                            const tr = buildExpenseFormRowAdd(expenseId, payCode);
                            parentRow.parentNode.insertBefore(tr, parentRow.nextSibling);
                            wireMonthTotal(tr);
                            // Bind searchable-dropdown for dynamically added row.
                            window.PBMSearchableDropdown?.initAll?.();
                            return;
                        }

                        const editSubBtn = e.target.closest('.editBudgetExpensesPlanSub');
                        if (editSubBtn) {
                            const subId = Number(editSubBtn.dataset.id || 0);
                            const expenseId = Number(editSubBtn.dataset.expenseId || 0);
                            const parentRow = editSubBtn.closest('tr');
                            if (!parentRow || !subId || !expenseId) return;
                            const next = parentRow.nextElementSibling;
                            if (next && next.classList.contains('sub-expenses-form')) {
                                next.remove();
                                return;
                            }
                            const json = await fetch(`${@json(url('/budget/api/expense'))}/${expenseId}/subs?_=${Date.now()}`, { cache: 'no-store' }).then((r) => r.json());
                            const rows = json?.data || [];
                            const s = rows.find((x) => Number(x.budget_expenses_sub_id) === subId);
                            if (!s) {
                            toastFail('ไม่พบข้อมูลรายการย่อย');
                                return;
                            }
                            const payCode = String(expenseRows.find((r) => Number(r.budget_expenses_id) === expenseId)?.budget_pay_code ?? '');
                            const tr = buildExpenseFormRowEdit(s, expenseId, payCode);
                            parentRow.parentNode.insertBefore(tr, parentRow.nextSibling);
                            wireMonthTotal(tr);
                            // Bind searchable-dropdown for dynamically added row.
                            window.PBMSearchableDropdown?.initAll?.();
                            return;
                        }

                        const delSub = e.target.closest('.deleteBudgetExpensesPlanSub');
                        if (delSub) {
                            pendingExpenseSubDeleteId = Number(delSub.dataset.id || 0);
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'expense-sub-delete-confirm-modal' }));
                            return;
                        }

                        const detailBtn = e.target.closest('.detailBudgetExpensesPlanSub');
                        if (detailBtn) {
                            const expenseId = String(detailBtn.dataset.id || '');
                            if (!expenseId) return;
                            const parentAttr = (window.CSS && typeof CSS.escape === 'function') ? CSS.escape(expenseId) : expenseId.replace(/"/g, '');
                            expensesTable.querySelectorAll(`tr[data-sub-parent="${parentAttr}"]`).forEach((tr) => {
                                const isHidden = tr.style.display === 'none' || getComputedStyle(tr).display === 'none';
                                tr.style.display = isHidden ? 'table-row' : 'none';
                            });
                            return;
                        }

                        const editExp = e.target.closest('[data-edit-exp]');
                        if (editExp) {
                            refreshModalRefs();
                            const x = JSON.parse(editExp.dataset.editExp);
                            if (exId) exId.value = x.budget_expenses_id;
                            if (exPay) {
                                exPay.value = String(x.budget_pay_code ?? '');
                                exPay.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            if (exAmount) exAmount.value = x.budget_expenses_amount;
                            if (exDetail) exDetail.value = x.budget_expenses_detail || '';
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'expense-modal' }));
                            return;
                        }

                        const delExp = e.target.closest('[data-delete-exp]');
                        if (delExp) {
                            pendingExpenseDeleteId = Number(delExp.dataset.deleteExp || 0);
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'expense-delete-confirm-modal' }));
                        }
                    });
                }

                window.__manageListConfirmDelete = async () => {
                    const id = pendingManageListDeleteId;
                    pendingManageListDeleteId = 0;
                    try {
                        if (!id) return;
                        const rs = await post(@json(route('budget.api.manage-list.delete')), { budgetManageListId: id });
                        if (rs?.status === 'success') {
                            if (rs?.data?.budgetManageBalance !== undefined) {
                                budgetBalance = Number(rs.data.budgetManageBalance || 0);
                            }
                            toastOk('ลบรายการสำเร็จ');
                            await loadHistory();
                            await loadExpenses();
                        } else {
                            toastFail(rs?.message || 'ลบรายการไม่สำเร็จ');
                        }
                    } catch (e) {
                        toastFail('เกิดข้อผิดพลาด');
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'manage-list-delete-confirm-modal' }));
                    }
                };

                window.__expenseConfirmDelete = async () => {
                    const id = pendingExpenseDeleteId;
                    pendingExpenseDeleteId = 0;
                    try {
                        if (!id) return;
                        const rs = await post(@json(route('budget.api.expense.delete')), { budgetManageExpensesId: id });
                        if (rs?.status === 'success') {
                            toastOk('ลบแผนรายการจ่ายสำเร็จ');
                            await loadExpenses();
                        } else {
                            toastFail(rs?.message || 'ลบแผนรายการจ่ายไม่สำเร็จ');
                        }
                    } catch (e) {
                        toastFail('เกิดข้อผิดพลาด');
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'expense-delete-confirm-modal' }));
                    }
                };

                window.__expenseSubConfirmDelete = async () => {
                    const id = pendingExpenseSubDeleteId;
                    pendingExpenseSubDeleteId = 0;
                    try {
                        if (!id) return;
                        const rs = await post(@json(route('budget.api.expense-sub.delete')), { budgetManageExpensesSubId: id });
                        if (rs?.status === 'success') {
                            toastOk('ลบรายการย่อยสำเร็จ');
                            await loadExpenses();
                        } else {
                            toastFail(rs?.message || 'ลบรายการย่อยไม่สำเร็จ');
                        }
                    } catch (e) {
                        toastFail('เกิดข้อผิดพลาด');
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'expense-sub-delete-confirm-modal' }));
                    }
                };

                if (btnAddList) {
                    btnAddList.addEventListener('click', () => {
                        refreshModalRefs();
                        if (!manageListForm) return;
                        manageListForm.reset();
                        if (mlId) mlId.value = '';
                        const today = new Date().toISOString().slice(0, 10);
                        if (mlDate) setDateValue(mlDate, today);
                        // searchable-dropdown sync: reset doesn't necessarily update visible text.
                        if (mlType) setValueAndDispatch(mlType, '');
                        if (mlSource) setValueAndDispatch(mlSource, '');
                        if (mlCategoryPay) setValueAndDispatch(mlCategoryPay, '');
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manage-list-modal' }));
                    });
                }

                if (btnAddExpense) {
                    btnAddExpense.addEventListener('click', () => {
                        refreshModalRefs();
                        if (!expenseForm) return;
                        expenseForm.reset();
                        if (exId) exId.value = '';
                        if (exPay) {
                            exPay.value = '';
                            exPay.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'expense-modal' }));
                    });
                }

                document.addEventListener('submit', async (e) => {
                    const form = (e.target && typeof e.target.closest === 'function') ? e.target.closest('form') : null;
                    if (!form || !form.id) return;

                    if (form.id === 'manage-list-form') {
                        e.preventDefault();
                        refreshModalRefs();
                        const payload = {
                            budgetManageListId: Number(mlId?.value || 0),
                            budgetManageId: Number(manageId),
                            budgetManageDate: mlDate?.value || '',
                            budgetManageType: Number(mlType?.value || 0),
                            budgetManageSource: Number(mlSource?.value || 0),
                            budgetManageCategoryPay: Number(mlCategoryPay?.value || 0),
                            budgetManageAmount: Number(mlAmount?.value || 0),
                            budgetManageDetail: mlDetail?.value || '',
                        };
                        const rs = await post(@json(route('budget.api.manage-list.save')), payload);
                        if (rs.status !== 'success') {
                            toastFail(rs.message || 'บันทึกไม่สำเร็จ');
                            return;
                        }
                        toastOk('บันทึกรายการปรับงบสำเร็จ');
                        if (rs?.data?.budgetManageBalance !== undefined) {
                            budgetBalance = Number(rs.data.budgetManageBalance || 0);
                        }
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'manage-list-modal' }));
                        await loadHistory();
                        await loadExpenses();
                        return;
                    }

                    if (form.id === 'expense-form') {
                        e.preventDefault();
                        refreshModalRefs();
                        const rs = await post(@json(route('budget.api.expense.save')), {
                            budgetExpensesPlanId: Number(exId?.value || 0),
                            budgetManageId: Number(manageId),
                            budgetPayListCode: Number(exPay?.value || 0),
                            budgetExpensesPlanAmount: Number(exAmount?.value || 0),
                            budgetExpensesPlanDetail: exDetail?.value || '',
                        });
                        if (rs.status !== 'success') {
                            toastFail(rs.message || 'บันทึกไม่สำเร็จ');
                            return;
                        }
                        toastOk('บันทึกแผนรายการจ่ายสำเร็จ');
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'expense-modal' }));
                        await loadExpenses();
                    }
                });

                loadHistory();
                loadExpenses();
            })();
                };
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', start);
                } else {
                    start();
                }
            })();
        </script>
    @endpush
</x-layouts.app>
