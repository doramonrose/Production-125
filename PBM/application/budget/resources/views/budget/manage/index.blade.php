<x-layouts.app>
    <div class="py-2">
        {{-- Header --}}
        <div class="mx-2 mb-4 overflow-hidden rounded-[28px] bg-gradient-to-br from-emerald-500/[0.12] via-blue-500/[0.10] via-[40%] to-white/60 shadow-[0_8px_32px_-8px_rgba(15,23,42,0.22),inset_0_1px_0_rgba(255,255,255,0.5)] backdrop-blur-[22px] backdrop-saturate-[1.7]">
            <div class="relative px-5 py-3 sm:px-6 sm:py-4 lg:px-8 lg:py-5">
                <div class="pointer-events-none absolute inset-0">
                    {{-- Green orb --}}
                    <div class="absolute -top-16 -left-16 h-56 w-56 rounded-full opacity-55 bg-[radial-gradient(circle,rgba(16,185,129,0.40),transparent_65%)] blur-[44px]"></div>
                    {{-- Blue orb --}}
                    <div class="absolute -bottom-20 -right-16 h-52 w-52 rounded-full opacity-50 bg-[radial-gradient(circle,rgba(59,130,246,0.38),transparent_65%)] blur-[44px]"></div>
                    {{-- Teal bridge --}}
                    <div class="absolute left-1/3 top-[-10px] h-36 w-72 rounded-full opacity-35 bg-[radial-gradient(circle,rgba(6,182,212,0.30),transparent_65%)] blur-[48px]"></div>
                    {{-- Glass edge highlights --}}
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/[0.85] to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent"></div>
                </div>

                <div class="relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-6">
                    <div class="min-w-0 lg:flex-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl !bg-[linear-gradient(135deg,#059669,#2563eb)] text-white shadow-lg shadow-emerald-500/25 ring-1 ring-white/20">
                                <i class="fas fa-wallet text-[13px] drop-shadow-[0_1px_2px_rgba(0,0,0,0.25)]"></i>
                            </span>
                            <h2 class="truncate text-[22px] font-black tracking-tight text-slate-950 lg:text-[28px]">
                                บริหารงบประมาณ
                            </h2>
                        </div>
                        <p class="mt-1 max-w-[58ch] text-xs leading-relaxed text-slate-600 lg:text-sm">
                            จัดการรายการงบประมาณ ค้นหา กรอง และดูภาพรวมการเบิกจ่ายได้ทันที
                        </p>
                        <div class="mt-1 hidden flex-wrap items-center gap-1.5 text-[11px] font-semibold text-slate-600 lg:flex">
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-900/10 bg-white/55 px-3 py-1.5 shadow-sm">
                                <i class="fas fa-filter text-[11px] text-slate-500"></i>
                                Filters
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-900/10 bg-white/55 px-3 py-1.5 shadow-sm">
                                <i class="fas fa-chart-line text-[11px] text-slate-500"></i>
                                Dashboard
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-900/10 bg-white/55 px-3 py-1.5 shadow-sm">
                                <i class="fas fa-bolt text-[11px] text-slate-500"></i>
                                Real-time
                            </span>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center justify-end gap-2">
                        <button id="btn-add-budget" type="button"
                            class="group relative inline-flex h-10 w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl !bg-[linear-gradient(to_top_right,#059669,#22c55e,#84cc16)] px-4 text-sm font-extrabold !text-white shadow-[0_18px_35px_-22px_rgba(16,185,129,0.95)] ring-1 ring-emerald-900/10 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_22px_45px_-24px_rgba(16,185,129,1)] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 lg:w-auto">
                            <span class="pointer-events-none absolute inset-0 rounded-2xl opacity-0 bg-[radial-gradient(120px_80px_at_30%_20%,rgba(255,255,255,0.30),transparent_60%)] transition-opacity duration-200 group-hover:opacity-100"></span>
                            <span class="relative inline-flex h-7 w-7 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25 transition-transform duration-200 group-hover:rotate-90 group-hover:bg-white/20">
                                <i class="fas fa-plus text-[12px]"></i>
                            </span>
                            <span class="relative">สร้างรายการใหม่</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dashboard Summary --}}
        <div class="mx-2 mb-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-soft-xl text-center">
                <h6 class="text-sm font-bold text-slate-600">งบประมาณรวม</h6>
                <h5 id="sum-total" class="text-xl font-bold text-blue-500 mt-1">0 ฿</h5>
                <p id="sum-remain-total" class="text-xs font-semibold text-slate-500 mt-0.5">0 คงเหลือ</p>
                <div class="h-20 relative mt-2"><canvas id="chartTotal"></canvas></div>
                <p id="sum-wait-total" class="text-xs text-slate-400 mt-1">0 รอเบิกจ่าย</p>
            </div>
            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-soft-xl text-center">
                <h6 class="text-sm font-bold text-slate-600">เงินรายได้</h6>
                <h5 id="sum-income" class="text-xl font-bold text-green-500 mt-1">0 ฿</h5>
                <p id="sum-remain-income" class="text-xs font-semibold text-slate-500 mt-0.5">0 คงเหลือ</p>
                <div class="h-20 relative mt-2"><canvas id="chartIncome"></canvas></div>
                <p id="sum-wait-income" class="text-xs text-slate-400 mt-1">0 รอเบิกจ่าย</p>
            </div>
            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-soft-xl text-center">
                <h6 class="text-sm font-bold text-slate-600">เงินแผ่นดิน</h6>
                <h5 id="sum-gov" class="text-xl font-bold text-yellow-500 mt-1">0 ฿</h5>
                <p id="sum-remain-gov" class="text-xs font-semibold text-slate-500 mt-0.5">0 คงเหลือ</p>
                <div class="h-20 relative mt-2"><canvas id="chartGov"></canvas></div>
                <p id="sum-wait-gov" class="text-xs text-slate-400 mt-1">0 รอเบิกจ่าย</p>
            </div>
            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-soft-xl text-center">
                <h6 class="text-sm font-bold text-slate-600">เงินนอกงบประมาณ</h6>
                <h5 id="sum-external" class="text-xl font-bold text-red-500 mt-1">0 ฿</h5>
                <p id="sum-remain-external" class="text-xs font-semibold text-slate-500 mt-0.5">0 คงเหลือ</p>
                <div class="h-20 relative mt-2"><canvas id="chartExternal"></canvas></div>
                <p id="sum-wait-external" class="text-xs text-slate-400 mt-1">0 รอเบิกจ่าย</p>
            </div>
        </div>

        {{-- Filters --}}
        <div
            class="mx-2 mb-4 rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm relative z-[80]">
            {{-- Source Filter Buttons --}}
            <div class="mb-3 flex items-center gap-2">
                <div class="flex min-w-0 flex-1 items-center gap-2 overflow-x-auto pb-1">
                    <button type="button" data-filter-source=""
                        class="filter-source-btn active inline-flex h-11 shrink-0 items-center rounded-xl border border-blue-500/20 bg-white px-3 text-sm font-semibold text-blue-600 shadow-sm transition-all hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300">
                        ทั้งหมด
                    </button>
                    @foreach ($sources as $src)
                        <button type="button" data-filter-source="{{ $src->budget_source_code }}"
                            class="filter-source-btn inline-flex h-11 shrink-0 items-center rounded-xl border border-blue-500/20 bg-white px-3 text-sm font-semibold text-blue-600 shadow-sm transition-all hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200">
                            {{ $src->budget_source_name }}
                        </button>
                    @endforeach
                </div>

                <div class="group relative w-48 shrink-0 sm:w-80">
                    <label for="filter-search" class="sr-only">ค้นหารหัสงบหรือชื่อแหล่งงบ</label>
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 transition-colors group-focus-within:text-emerald-600">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input id="filter-search" type="text" inputmode="search" placeholder="ค้นหารหัสงบ หรือชื่อแหล่งงบ..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-2.5 pl-9 pr-10 text-sm font-medium text-slate-700 shadow-sm transition-all placeholder:font-normal placeholder:text-slate-400 hover:border-slate-300 hover:bg-white focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100" />
                    <button id="btn-clear-search" type="button"
                        class="absolute inset-y-0 right-1.5 hidden my-auto h-7 w-7 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300"
                        data-ui-tooltip="ล้างคำค้นหา" aria-label="ล้างคำค้นหา">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Dropdown Filters --}}
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-7">
                <x-searchable-dropdown id="filter-year" placeholder="ค้นหาปี..." :options="$years"
                    valueKey="budget_year_name" textKey="budget_year_name" />
                <x-searchable-dropdown id="filter-type" placeholder="ค้นหาประเภท..." :options="$types"
                    valueKey="budget_type_code" textKey="budget_type_name" />
                <x-searchable-dropdown id="filter-plan" placeholder="ค้นหาแผน..." :options="$plans"
                    valueKey="budget_plan_code" textKey="budget_plan_name" />
                <x-searchable-dropdown id="filter-fund" placeholder="ค้นหากองทุน..." :options="$funds"
                    valueKey="budget_fund_code" textKey="budget_fund_name" />
                <x-searchable-dropdown id="filter-cost" placeholder="ค้นหาต้นทุน..." :options="$costs"
                    valueKey="budget_cost_code" textKey="budget_cost_name" />
                <x-searchable-dropdown id="filter-category" placeholder="ค้นหาหมวด..." :options="$categoryBudgets"
                    valueKey="budget_category_budget_code" textKey="budget_category_budget_name" />
                <button id="btn-reset-filter" type="button"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 transition-all">
                    <i class="fas fa-undo-alt mr-1"></i>รีเซ็ต
                </button>
            </div>
        </div>

        {{-- Data Table --}}
        <div
            class="mx-2 rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-soft-xl backdrop-blur-sm">
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="w-full table-auto text-sm [&_td]:align-top">
                    <thead class="sticky top-0 z-10">
                        <tr
                            class="border-b border-slate-200 bg-slate-50/95 text-slate-700 text-[11px] font-semibold uppercase tracking-wider backdrop-blur">
                            <th class="w-11 px-3 py-3 text-center align-middle" rowspan="2">#</th>
                            <th class="px-3 py-3 text-center align-middle select-none cursor-pointer hover:bg-slate-100/70 transition-colors"
                                rowspan="2" data-sort-key="code">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>รหัสงบ</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="code"></span>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-center align-middle select-none cursor-pointer hover:bg-slate-100/70 transition-colors"
                                rowspan="2" data-sort-key="source_path">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>ชื่อแหล่ง</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="source_path"></span>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-center bg-blue-50" colspan="3">งบประมาณ (บาท)</th>
                            <th class="px-3 py-3 text-center bg-green-50" colspan="2">แผนการใช้งบ</th>
                            <th class="px-3 py-3 text-center bg-amber-50" colspan="3">การเบิกจ่าย</th>
                            <th class="w-[140px] px-3 py-3 text-center align-middle" rowspan="2">เครื่องมือ</th>
                        </tr>
                        <tr
                            class="border-b border-slate-200 bg-slate-50 text-slate-600 text-[11px] font-semibold uppercase tracking-wider">
                            <th class="px-3 py-2 text-center bg-blue-50 select-none cursor-pointer hover:bg-blue-100/60 transition-colors"
                                data-sort-key="amount">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>งบประมาณ</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="amount"></span>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-center bg-blue-50 select-none cursor-pointer hover:bg-blue-100/60 transition-colors"
                                data-sort-key="dw">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>(+/-)</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="dw"></span>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-center bg-blue-50 select-none cursor-pointer hover:bg-blue-100/60 transition-colors"
                                data-sort-key="balance">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>ยอดปัจจุบัน</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="balance"></span>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-center bg-green-50 select-none cursor-pointer hover:bg-green-100/60 transition-colors"
                                data-sort-key="plan_used">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>จัดแผนแล้ว</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="plan_used"></span>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-center bg-green-50 select-none cursor-pointer hover:bg-green-100/60 transition-colors"
                                data-sort-key="plan_balance">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>คงเหลือ</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="plan_balance"></span>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-center bg-amber-50 select-none cursor-pointer hover:bg-amber-100/60 transition-colors"
                                data-sort-key="withdraw_amount">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>เบิกจ่าย</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="withdraw_amount"></span>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-center bg-amber-50 select-none cursor-pointer hover:bg-amber-100/60 transition-colors"
                                data-sort-key="withdraw_balance">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>คงเหลือ</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="withdraw_balance"></span>
                                </div>
                            </th>
                            <th class="px-3 py-2 text-center bg-amber-50 select-none cursor-pointer hover:bg-amber-100/60 transition-colors"
                                data-sort-key="withdraw_wait">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <span>รอเบิกจ่าย</span>
                                    <span class="text-[10px] text-slate-400" data-sort-indicator-for="withdraw_wait"></span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="budget-manage-table" class="text-slate-700 divide-y divide-slate-100 [&>tr>td]:align-top"></tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <x-pagination-bar infoId="table-info" pageId="pagination-page" prevId="btn-prev" nextId="btn-next"
                viewAllId="btn-view-all" viewAllText="แสดงทั้งหมด" />
        </div>
    </div>

    <x-modal name="budget-manage-modal" maxWidth="3xl" variant="modern" title="เพิ่มงบประมาณ"
        description="กรอกข้อมูลให้ครบเพื่อสร้างรายการงบประมาณใหม่ หรือแก้ไขรายการเดิมได้ทันที"
        titleId="budget-modal-title" iconClass="fas fa-layer-group">
        <form id="budget-manage-form">
            <input type="hidden" id="f-id" />

            {{-- Content --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                {{-- Section: ข้อมูลหลัก --}}
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-700">ข้อมูลหลัก</h4>
                        <span class="text-[11px] font-semibold text-slate-500">* จำเป็น</span>
                    </div>
                    <div class="mt-2 h-px bg-gradient-to-r from-slate-200 via-slate-200/70 to-transparent"></div>
                </div>

                    <div>
                        <label for="f-year" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">ปีงบประมาณ *</label>
                        <x-searchable-dropdown id="f-year" placeholder="ค้นหาปีงบประมาณ..." :options="$years"
                            valueKey="budget_year_name" textKey="budget_year_name" />
                    </div>

                    <div>
                        <label for="f-code" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">รหัสงบประมาณ *</label>
                        <input id="f-code" placeholder="เช่น 691E09961G00010"
                            class="w-full rounded-2xl border border-slate-300/90 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                    </div>

                    <div class="md:col-span-2">
                        <label for="f-source" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">แหล่งงบประมาณ *</label>
                        <x-searchable-dropdown id="f-source" placeholder="ค้นหาแหล่งงบประมาณ..." :options="$sources"
                            valueKey="budget_source_code" textKey="budget_source_name" />
                    </div>

                    {{-- Section: โครงสร้างแผน --}}
                    <div class="md:col-span-2 mt-2">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-700">โครงสร้างแผน</h4>
                        <div class="mt-2 h-px bg-gradient-to-r from-slate-200 via-slate-200/70 to-transparent"></div>
                    </div>

                    <div>
                        <label for="f-plan" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">แผน *</label>
                        <x-searchable-dropdown id="f-plan" placeholder="ค้นหาแผน..." :options="$plans"
                            valueKey="budget_plan_code" textKey="budget_plan_name" />
                    </div>

                    <div>
                        <label for="f-event" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">งาน *</label>
                        <x-searchable-dropdown id="f-event" placeholder="ค้นหางาน..." :options="[]"
                            valueKey="value" textKey="label" optionsKey="budget-manage-events" />
                    </div>

                    {{-- Section: การจัดหมวดหมู่ --}}
                    <div class="md:col-span-2 mt-2">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-700">การจัดหมวดหมู่</h4>
                        <div class="mt-2 h-px bg-gradient-to-r from-slate-200 via-slate-200/70 to-transparent"></div>
                    </div>

                    <div>
                        <label for="f-fund" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">กองทุน *</label>
                        <x-searchable-dropdown id="f-fund" placeholder="ค้นหากองทุน..." :options="$funds"
                            valueKey="budget_fund_code" textKey="budget_fund_name" />
                    </div>

                    <div>
                        <label for="f-type" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">หมวดประเภท *</label>
                        <x-searchable-dropdown id="f-type" placeholder="ค้นหาหมวดประเภท..." :options="$types"
                            valueKey="budget_type_code" textKey="budget_type_name" />
                    </div>

                    <div>
                        <label for="f-category-budget" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">หมวดงบประมาณ *</label>
                        <x-searchable-dropdown id="f-category-budget" placeholder="ค้นหาหมวดงบประมาณ..." :options="$categoryBudgets"
                            valueKey="budget_category_budget_code" textKey="budget_category_budget_name" />
                    </div>

                    <div>
                        <label for="f-category-pay" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">หมวดรายจ่าย *</label>
                        <x-searchable-dropdown id="f-category-pay" placeholder="ค้นหาหมวดรายจ่าย..." :options="$categoryPays"
                            valueKey="budget_category_pay_code" textKey="budget_category_pay_name" />
                    </div>

                    <div class="md:col-span-2">
                        <label for="f-category-pay-detail" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">รายละเอียดหมวดรายจ่าย</label>
                        <input id="f-category-pay-detail" placeholder="ระบุเพิ่มเติม (ถ้ามี)"
                            class="w-full rounded-2xl border border-slate-300/90 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                    </div>

                    {{-- Section: จำนวนเงิน & รายละเอียด --}}
                    <div class="md:col-span-2 mt-2">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-700">จำนวนเงิน & รายละเอียด</h4>
                        <div class="mt-2 h-px bg-gradient-to-r from-slate-200 via-slate-200/70 to-transparent"></div>
                    </div>

                    <div>
                        <label for="f-amount" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">จำนวนเงิน *</label>
                        <div class="relative">
                            <input id="f-amount" type="number" step="0.01" inputmode="decimal" placeholder="0.00"
                                class="w-full rounded-2xl border border-slate-300/90 bg-white px-3.5 py-2.5 pr-14 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                            <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-xs font-bold text-slate-500">บาท</span>
                        </div>
                    </div>

                    <div>
                        <label for="f-date" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">วันที่ *</label>
                        <x-date-picker id="f-date" placeholder="เลือกวันที่" />
                    </div>

                    <div class="md:col-span-2">
                        <label for="f-cost" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">ต้นทุน</label>
                        <x-searchable-dropdown id="f-cost" placeholder="ค้นหาต้นทุน..." :options="$costs"
                            valueKey="budget_cost_code" textKey="budget_cost_name" />
                    </div>

                <div class="md:col-span-2">
                    <label for="f-detail" class="mb-1.5 block text-[11px] font-extrabold text-slate-700">รายละเอียด</label>
                    <textarea id="f-detail" rows="3" placeholder="ใส่รายละเอียดเพิ่มเติม เช่น วัตถุประสงค์/หมายเหตุ"
                        class="w-full resize-none rounded-2xl border border-slate-300/90 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'budget-manage-modal')"
                    class="inline-flex h-11 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-600 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50 hover:text-slate-700 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 sm:w-auto">
                    ยกเลิก
                </button>
                <button type="submit"
                    class="group inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl !bg-emerald-600 px-5 text-sm font-extrabold !text-white shadow-[0_18px_35px_-22px_rgba(16,185,129,0.95)] ring-1 !ring-emerald-900/10 transition-all duration-200 hover:-translate-y-0.5 hover:!bg-emerald-700 hover:shadow-[0_22px_45px_-24px_rgba(16,185,129,1)] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 sm:w-auto">
                    <i class="fas fa-check text-[12px] opacity-90"></i>
                    <span>บันทึก</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-confirm-modal
        name="budget-manage-delete-confirm-modal"
        maxWidth="sm"
        title="ยืนยันการลบรายการงบประมาณ?"
        message="คุณต้องการลบรายการนี้หรือไม่ การลบไม่สามารถย้อนกลับได้"
        confirmText="ลบ"
        cancelText="ยกเลิก"
        confirmVariant="danger"
        confirmClick="window.__budgetManageConfirmDelete()"
    />

    @include('budget.manage.partials.disbursement-modal')

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            (function() {
                // Elements
                const tbody = document.getElementById('budget-manage-table');
                const btnAdd = document.getElementById('btn-add-budget');
                const btnPrev = document.getElementById('btn-prev');
                const btnNext = document.getElementById('btn-next');
                const btnViewAll = document.getElementById('btn-view-all');
                const btnReset = document.getElementById('btn-reset-filter');
                const tableInfo = document.getElementById('table-info');
                const paginationPage = document.getElementById('pagination-page');
                const filterSearch = document.getElementById('filter-search');
                const btnClearSearch = document.getElementById('btn-clear-search');
                const filterYear = document.getElementById('filter-year');
                const filterType = document.getElementById('filter-type');
                const filterPlan = document.getElementById('filter-plan');
                const filterFund = document.getElementById('filter-fund');
                const filterCost = document.getElementById('filter-cost');
                const filterCategory = document.getElementById('filter-category');
                const filterYearText = document.getElementById('filter-year-text');
                const filterYearSuggest = document.getElementById('filter-year-suggest');
                const filterTypeText = document.getElementById('filter-type-text');
                const filterTypeSuggest = document.getElementById('filter-type-suggest');
                const filterPlanText = document.getElementById('filter-plan-text');
                const filterPlanSuggest = document.getElementById('filter-plan-suggest');
                const filterFundText = document.getElementById('filter-fund-text');
                const filterFundSuggest = document.getElementById('filter-fund-suggest');
                const filterCostText = document.getElementById('filter-cost-text');
                const filterCostSuggest = document.getElementById('filter-cost-suggest');
                const filterCategoryText = document.getElementById('filter-category-text');
                const filterCategorySuggest = document.getElementById('filter-category-suggest');
                const sourceButtons = document.querySelectorAll('.filter-source-btn');
                const defaultYear = @json($currentYear ?? '');

                const applyDefaultYearFilter = () => {
                    if (!defaultYear) return;
                    if (filterYear) filterYear.value = defaultYear;
                    if (filterYearText) filterYearText.value = defaultYear;
                };

                // Modal ใช้ x-teleport — ตอนสคริปต์นี้รันครั้งแรกองค์ประกอบอาจยังอยู่ใน <template> จึงต้อง refresh จาก document ทุกครั้งที่ใช้งานจริง
                let fId = null;
                let fYear = null;
                let fCode = null;
                let fSource = null;
                let fPlan = null;
                let fEvent = null;
                let fFund = null;
                let fCost = null;
                let fCategoryBudget = null;
                let fCategoryPay = null;
                let fCategoryPayDetail = null;
                let fType = null;
                let fAmount = null;
                let fDate = null;
                let fDetail = null;

                const refreshBudgetManageModalRefs = () => {
                    fId = document.getElementById('f-id');
                    fYear = document.getElementById('f-year');
                    fCode = document.getElementById('f-code');
                    fSource = document.getElementById('f-source');
                    fPlan = document.getElementById('f-plan');
                    fEvent = document.getElementById('f-event');
                    fFund = document.getElementById('f-fund');
                    fCost = document.getElementById('f-cost');
                    fCategoryBudget = document.getElementById('f-category-budget');
                    fCategoryPay = document.getElementById('f-category-pay');
                    fCategoryPayDetail = document.getElementById('f-category-pay-detail');
                    fType = document.getElementById('f-type');
                    fAmount = document.getElementById('f-amount');
                    fDate = document.getElementById('f-date');
                    fDetail = document.getElementById('f-detail');
                };

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const toastOk = (msg) => window.toast?.success?.(msg, { timer: 3000 });
                const toastFail = (msg) => {
                    if (window.toast?.error) window.toast.error(msg, { timer: 4000 });
                    else alert(msg);
                };
                const escAttr = (v) => String(v ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                const urlEventsByPlanTpl = @json(route('budget.api.cascade.events-by-plan', ['planCode' => '__PLAN__']));
                const urlActivitiesByEventTpl = @json(route('budget.api.cascade.activities-by-event', ['eventCode' => '__EVENT__']));

                const ensureModalCascadeBindings = () => {
                    refreshBudgetManageModalRefs();

                    if (fPlan && !fPlan.dataset.pbmCascadeBound) {
                        fPlan.dataset.pbmCascadeBound = '1';
                        const onPlanChange = () => {
                            if (suppressPlanCascade) return;
                            refreshEventsByPlan(fPlan.value || '');
                        };
                        fPlan.addEventListener('input', onPlanChange);
                        fPlan.addEventListener('change', onPlanChange);
                    }
                };

                const setHiddenValue = (hiddenId, value) => {
                    const el = document.getElementById(hiddenId);
                    if (!el) return;
                    el.value = value ?? '';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                };

                async function refreshEventsByPlan(planCode, preselectValue = '') {
                    const plan = String(planCode ?? '').trim();

                    // Clear current selection + options when no plan selected.
                    if (!plan) {
                        setHiddenValue('f-event', '');
                        if (window.PBMSearchableDropdown?.setOptions) {
                            window.PBMSearchableDropdown.setOptions('budget-manage-events', []);
                        }
                        return;
                    }

                    try {
                        const url = String(urlEventsByPlanTpl).replace('__PLAN__', encodeURIComponent(plan));
                        const res = await fetch(url);
                        const json = await res.json();
                        const events = json?.data || [];

                        const buildOptions = async () => {
                            const groups = await Promise.all((events || []).map(async (ev) => {
                                const eventCode = String(ev?.budget_event_code ?? '');
                                const eventName = String(ev?.budget_event_name ?? '');
                                if (!eventCode) return [];

                                try {
                                    const aUrl = String(urlActivitiesByEventTpl).replace('__EVENT__', encodeURIComponent(eventCode));
                                    const aRes = await fetch(aUrl);
                                    const aJson = await aRes.json();
                                    const acts = aJson?.data || [];
                                    if (Array.isArray(acts) && acts.length > 0) {
                                        const base = { value: `${eventCode}||`, label: eventName };
                                        const expanded = acts.map(ac => {
                                            const activityCode = String(ac?.budget_activity_code ?? '');
                                            const activityName = String(ac?.budget_activity_name ?? '');
                                            return { value: `${eventCode}||${activityCode}`, label: `${eventName} - ${activityName}` };
                                        });
                                        return [base, ...expanded];
                                    }
                                } catch (e) {}

                                return [{ value: `${eventCode}||`, label: eventName }];
                            }));
                            return groups.flat();
                        };

                        const options = await buildOptions();

                        // Preselect (for edit mode) before setOptions so it can sync visible text.
                        setHiddenValue('f-event', preselectValue ? String(preselectValue) : '');

                        if (window.PBMSearchableDropdown?.setOptions) {
                            window.PBMSearchableDropdown.setOptions('budget-manage-events', options);
                        }

                        // setOptions sync เฉพาะเมื่อ hidden มีค่าแล้ว — ย้ำอีกครั้งหลัง options พร้อม (กรณี race / DOM หลัง teleport)
                        if (preselectValue) {
                            const v = String(preselectValue);
                            setHiddenValue('f-event', v);
                        }
                    } catch (e) {
                        // fail silently; keep whatever options were already rendered
                    }
                }

                /** ค่า value ของ dropdown งานต้องตรงกับตัวเลือก เช่น event|| หรือ event||activity (DB เก็บ activity 0 = ไม่มีกิจกรรมย่อย) */
                function buildEventActivityPreselect(eventCode, activityCode) {
                    const ev = eventCode != null && String(eventCode).trim() !== '' ? String(eventCode).trim() : '';
                    if (!ev) return '';
                    const ac = Number(activityCode);
                    return ac > 0 ? `${ev}||${ac}` : `${ev}||`;
                }

                // Data & State
                let allRows = [];
                let filteredRows = [];
                let currentPage = 1;
                const pageSize = 10;
                let currentSourceFilter = '';
                let viewAll = false;
                let sortKey = '';
                let sortDir = 'asc'; // 'asc' | 'desc'
                /** กัน refresh งานจากแผนอัตโนมัติตอนโหลดฟอร์มแก้ไข (จะชนกับ preselect) */
                let suppressPlanCascade = false;
                let pendingDeleteBudgetManageId = 0;

                // Source button styling uses inline styles because color utilities may be themed/overridden.
                // Keep class toggling for logic (active/currentSourceFilter) but let inline styles control appearance.
                const SOURCE_ACTIVE_BG = '#2563eb';
                const SOURCE_ACTIVE_TEXT = '#ffffff';
                const SOURCE_INACTIVE_BG = '#ffffff';
                const SOURCE_INACTIVE_TEXT = '#2563eb';
                const SOURCE_ACTIVE_BORDER = '#2563eb';
                const SOURCE_INACTIVE_BORDER = '#93c5fd';

                const applySourceButtonStyle = (btn, active) => {
                    if (!btn) return;
                    btn.style.backgroundColor = active ? SOURCE_ACTIVE_BG : SOURCE_INACTIVE_BG;
                    btn.style.color = active ? SOURCE_ACTIVE_TEXT : SOURCE_INACTIVE_TEXT;
                    btn.style.borderColor = active ? SOURCE_ACTIVE_BORDER : SOURCE_INACTIVE_BORDER;
                };

                const updateSearchClearButton = () => {
                    if (!btnClearSearch) return;
                    btnClearSearch.classList.toggle('hidden', !(filterSearch?.value || '').trim());
                    btnClearSearch.classList.toggle('inline-flex', !!(filterSearch?.value || '').trim());
                };

                const manageFilterStorageKey = 'pbm.budget.manage.filters';
                let hydratingFilters = false;

                const readSavedFilters = () => {
                    try {
                        const raw = sessionStorage.getItem(manageFilterStorageKey);
                        if (!raw) return null;
                        const data = JSON.parse(raw);
                        return data && typeof data === 'object' ? data : null;
                    } catch (error) {
                        return null;
                    }
                };

                const saveManageFilters = () => {
                    if (hydratingFilters) return;
                    const payload = {
                        search: filterSearch?.value || '',
                        year: filterYear?.value || '',
                        type: filterType?.value || '',
                        plan: filterPlan?.value || '',
                        fund: filterFund?.value || '',
                        cost: filterCost?.value || '',
                        category: filterCategory?.value || '',
                        source: currentSourceFilter || '',
                    };
                    try {
                        sessionStorage.setItem(manageFilterStorageKey, JSON.stringify(payload));
                    } catch (error) {
                        // Keep filtering even if the browser blocks storage.
                    }
                };

                const applySourceSelection = (source) => {
                    currentSourceFilter = source || '';
                    sourceButtons.forEach(btn => {
                        const isActive = (btn?.dataset?.filterSource || '') === currentSourceFilter;
                        btn.classList.toggle('active', isActive);
                        applySourceButtonStyle(btn, isActive);
                    });
                };

                const restoreSavedFilters = () => {
                    const saved = readSavedFilters();
                    if (!saved) return false;
                    hydratingFilters = true;
                    if (filterSearch) filterSearch.value = saved.search || '';
                    updateSearchClearButton();
                    setHiddenValue('filter-year', saved.year || '');
                    setHiddenValue('filter-type', saved.type || '');
                    setHiddenValue('filter-plan', saved.plan || '');
                    setHiddenValue('filter-fund', saved.fund || '');
                    setHiddenValue('filter-cost', saved.cost || '');
                    setHiddenValue('filter-category', saved.category || '');
                    applySourceSelection(saved.source || '');
                    hydratingFilters = false;
                    return true;
                };

                // Initialize default "active" style (e.g. "ทั้งหมด" on first load)
                sourceButtons.forEach(btn => {
                    const isActive = btn?.dataset?.filterSource === '' || btn.classList.contains('active');
                    applySourceButtonStyle(btn, isActive);
                });

                const money = (v) => {
                    const n = Number(v || 0);
                    const isWhole = Number.isFinite(n) && Math.abs(n - Math.round(n)) < 1e-9;
                    return n.toLocaleString(undefined, {
                        minimumFractionDigits: isWhole ? 0 : 2,
                        maximumFractionDigits: 2
                    });
                };

                // Charts
                let charts = {};

                if (!tbody) return;

                // ========== Chart Functions ==========
                const centerTextPlugin = {
                    id: 'centerText',
                    afterDraw(chart) {
                        const {
                            width,
                            height,
                            ctx
                        } = chart;
                        const dataset = chart.data.datasets[0];
                        const percent = dataset.data[0];
                        const amount = chart.config._config.options.custom?.amount || 0;
                        const color = dataset.backgroundColor[0];
                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillStyle = color;
                        ctx.font = 'bold 14px "K2D", sans-serif';
                        ctx.fillText(percent + '%', width / 2, height / 2 - 3);
                        ctx.fillStyle = '#000';
                        ctx.font = '10px "K2D", sans-serif';
                        ctx.fillText(money(amount) + '฿', width / 2, height / 2 + 18);
                        ctx.restore();
                    }
                };

                function renderChart(id, percent, color, amount) {
                    const canvas = document.getElementById(id);
                    if (!canvas) return;
                    if (charts[id]) charts[id].destroy();
                    charts[id] = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            datasets: [{
                                data: [percent, 100 - percent],
                                backgroundColor: [color, '#e9ecef'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            rotation: -90,
                            circumference: 180,
                            cutout: '70%',
                            custom: {
                                amount
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: false
                                }
                            },
                            animation: {
                                animateRotate: true,
                                duration: 800
                            }
                        },
                        plugins: [centerTextPlugin]
                    });
                }

                function updateSummary(rows = []) {
                    const sumBy = (list, pick) => list.reduce((s, r) => s + Number(pick(r) || 0), 0);
                    const onlySource = (code) => rows.filter(r => String(r?.source_code ?? '') === String(code));

                    const totalBalance = sumBy(rows, r => r.balance);
                    const incomeBalance = sumBy(onlySource(1), r => r.balance);
                    const govBalance = sumBy(onlySource(2), r => r.balance);
                    const externalBalance = sumBy(onlySource(3), r => r.balance);

                    const totalAmount = sumBy(rows, r => r.amount);
                    const incomeAmount = sumBy(onlySource(1), r => r.amount);
                    const govAmount = sumBy(onlySource(2), r => r.amount);
                    const externalAmount = sumBy(onlySource(3), r => r.amount);

                    const totalWait = sumBy(rows, r => r.withdraw_wait);
                    const incomeWait = sumBy(onlySource(1), r => r.withdraw_wait);
                    const govWait = sumBy(onlySource(2), r => r.withdraw_wait);
                    const externalWait = sumBy(onlySource(3), r => r.withdraw_wait);

                    const totalWithdraw = sumBy(rows, r => r.withdraw_amount);
                    const incomeWithdraw = sumBy(onlySource(1), r => r.withdraw_amount);
                    const govWithdraw = sumBy(onlySource(2), r => r.withdraw_amount);
                    const externalWithdraw = sumBy(onlySource(3), r => r.withdraw_amount);

                    const remainOf = (list) => sumBy(list, r => (
                        r.withdraw_balance != null
                            ? r.withdraw_balance
                            : (Number(r.balance || 0) - Number(r.withdraw_amount || 0))
                    ));
                    const totalRemain = remainOf(rows);
                    const incomeRemain = remainOf(onlySource(1));
                    const govRemain = remainOf(onlySource(2));
                    const externalRemain = remainOf(onlySource(3));

                    const setText = (id, text) => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = text;
                    };

                    setText('sum-total', money(totalBalance) + ' ฿');
                    setText('sum-income', money(incomeBalance) + ' ฿');
                    setText('sum-gov', money(govBalance) + ' ฿');
                    setText('sum-external', money(externalBalance) + ' ฿');

                    setText('sum-remain-total', money(totalRemain) + ' คงเหลือ');
                    setText('sum-remain-income', money(incomeRemain) + ' คงเหลือ');
                    setText('sum-remain-gov', money(govRemain) + ' คงเหลือ');
                    setText('sum-remain-external', money(externalRemain) + ' คงเหลือ');

                    setText('sum-wait-total', money(totalWait) + ' รอเบิกจ่าย');
                    setText('sum-wait-income', money(incomeWait) + ' รอเบิกจ่าย');
                    setText('sum-wait-gov', money(govWait) + ' รอเบิกจ่าย');
                    setText('sum-wait-external', money(externalWait) + ' รอเบิกจ่าย');

                    const pctDisbursed = (withdraw, budget) => {
                        if (!budget || budget <= 0) return 0;
                        return Math.max(0, Math.min(100, Math.round((Number(withdraw || 0) / budget) * 100)));
                    };

                    renderChart('chartTotal', pctDisbursed(totalWithdraw, totalAmount), '#3b82f6', totalWithdraw);
                    renderChart('chartIncome', pctDisbursed(incomeWithdraw, incomeAmount), '#22c55e', incomeWithdraw);
                    renderChart('chartGov', pctDisbursed(govWithdraw, govAmount), '#eab308', govWithdraw);
                    renderChart('chartExternal', pctDisbursed(externalWithdraw, externalAmount), '#ef4444',
                        externalWithdraw);
                }

                // ========== Filter Functions ==========
                const isNumericSortKey = (key) => {
                    return [
                        'amount',
                        'dw',
                        'balance',
                        'plan_used',
                        'plan_balance',
                        'withdraw_amount',
                        'withdraw_balance',
                        'withdraw_wait',
                    ].includes(key);
                };

                const applySort = (rows) => {
                    if (!sortKey) return rows;
                    const dir = sortDir === 'desc' ? -1 : 1;
                    const numeric = isNumericSortKey(sortKey);

                    return [...rows].sort((a, b) => {
                        const av = a?.[sortKey];
                        const bv = b?.[sortKey];

                        if (numeric) {
                            const an = Number(av || 0);
                            const bn = Number(bv || 0);
                            if (an === bn) return 0;
                            return an > bn ? dir : -dir;
                        }

                        const as = String(av ?? '').toLowerCase();
                        const bs = String(bv ?? '').toLowerCase();
                        if (as === bs) return 0;
                        return as > bs ? dir : -dir;
                    });
                };

                const updateSortIndicators = () => {
                    document.querySelectorAll('[data-sort-indicator-for]').forEach(el => {
                        const k = el.getAttribute('data-sort-indicator-for');
                        if (!k) return;
                        if (k !== sortKey) {
                            el.textContent = '';
                            return;
                        }
                        el.textContent = sortDir === 'asc' ? '▲' : '▼';
                    });
                };

                function applyFilters(options = {}) {
                    const preservePagination = options.preservePagination === true;
                    const search = (filterSearch?.value || '').toLowerCase().trim();
                    const year = filterYear?.value || '';
                    const type = filterType?.value || '';
                    const plan = filterPlan?.value || '';
                    const fund = filterFund?.value || '';
                    const cost = filterCost?.value || '';
                    const category = filterCategory?.value || '';

                    const base = allRows.filter(row => {
                        if (currentSourceFilter && row.source_code != currentSourceFilter) return false;
                        if (year && row.year != year) return false;
                        if (type && row.type_code != type) return false;
                        if (plan && row.plan_code != plan) return false;
                        if (fund && row.fund_code != fund) return false;
                        if (cost && row.cost_code != cost) return false;
                        if (category && row.category_budget_code != category) return false;
                        if (search) {
                            const haystack = `${row.code} ${row.source_path}`.toLowerCase();
                            if (!haystack.includes(search)) return false;
                        }
                        return true;
                    });
                    filteredRows = applySort(base);
                    updateSummary(filteredRows);

                    if (!preservePagination) {
                        viewAll = false;
                        currentPage = 1;
                    } else if (!viewAll && filteredRows.length > 0) {
                        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
                        if (currentPage > totalPages) currentPage = totalPages;
                    }
                    renderTable();
                    saveManageFilters();
                }

                /** อัปเดต allRows หลังบันทึก/แก้ไขจาก API โดยไม่โหลดตารางใหม่ทั้งหมด */
                function upsertManageRow(row) {
                    if (!row || row.id == null) return;
                    const id = Number(row.id);
                    const idx = allRows.findIndex(r => Number(r.id) === id);
                    const isUpdate = idx >= 0;
                    if (isUpdate) allRows[idx] = row;
                    else allRows.unshift(row);
                    applyFilters({ preservePagination: isUpdate });
                }

                function removeManageRow(id) {
                    const nid = Number(id);
                    allRows = allRows.filter(r => Number(r.id) !== nid);
                    applyFilters({ preservePagination: true });
                }

                function resetFilters() {
                    if (filterSearch) filterSearch.value = '';
                    updateSearchClearButton();
                    if (filterType) filterType.value = '';
                    if (filterPlan) filterPlan.value = '';
                    if (filterFund) filterFund.value = '';
                    if (filterCost) filterCost.value = '';
                    if (filterCategory) filterCategory.value = '';
                    applyDefaultYearFilter();
                    if (filterTypeText) filterTypeText.value = '';
                    if (filterPlanText) filterPlanText.value = '';
                    if (filterFundText) filterFundText.value = '';
                    if (filterCostText) filterCostText.value = '';
                    if (filterCategoryText) filterCategoryText.value = '';
                    if (filterYearSuggest) {
                        filterYearSuggest.classList.add('hidden');
                        filterYearSuggest.innerHTML = '';
                    }
                    if (filterTypeSuggest) {
                        filterTypeSuggest.classList.add('hidden');
                        filterTypeSuggest.innerHTML = '';
                    }
                    if (filterPlanSuggest) {
                        filterPlanSuggest.classList.add('hidden');
                        filterPlanSuggest.innerHTML = '';
                    }
                    if (filterFundSuggest) {
                        filterFundSuggest.classList.add('hidden');
                        filterFundSuggest.innerHTML = '';
                    }
                    if (filterCostSuggest) {
                        filterCostSuggest.classList.add('hidden');
                        filterCostSuggest.innerHTML = '';
                    }
                    if (filterCategorySuggest) {
                        filterCategorySuggest.classList.add('hidden');
                        filterCategorySuggest.innerHTML = '';
                    }
                    currentSourceFilter = '';
                    viewAll = false;
                    sourceButtons.forEach(btn => {
                        const isAll = btn?.dataset?.filterSource === '';
                        btn.classList.toggle('active', isAll);
                        applySourceButtonStyle(btn, isAll);
                    });
                    applyFilters();
                    try {
                        sessionStorage.removeItem(manageFilterStorageKey);
                    } catch (error) {
                        // The on-screen filters are already cleared.
                    }
                }

                // ========== Render Table ==========
                function renderTable() {
                    let start = 0;
                    let end = 0;
                    let totalPages = 1;

                    if (viewAll) {
                        start = 0;
                        end = filteredRows.length;
                        totalPages = 1;
                        currentPage = 1;
                    } else {
                        start = (currentPage - 1) * pageSize;
                        end = start + pageSize;
                        totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
                    }
                    const pageRows = filteredRows.slice(start, end);

                    const disbursementBtn = (amount, kind, id) => {
                        const n = Number(amount || 0);
                        const formatted = money(n);
                        if (!(n > 0)) return formatted;
                        return `<button type="button" class="js-disbursement-popup tabular-nums underline decoration-dotted underline-offset-4 hover:text-blue-700" data-id="${id}" data-kind="${kind}" data-ui-tooltip="ดูรายการ">${formatted}</button>`;
                    };

                    if (pageRows.length === 0) {
                        tbody.innerHTML =
                            `<tr><td colspan="12" class="px-4 py-10 text-center text-slate-400">ไม่พบข้อมูล</td></tr>`;
                    } else {
                        tbody.innerHTML = pageRows.map((row, idx) => {
                            const planUsed = Number(row.plan_used || 0);
                            const deleteBtn = planUsed > 0 ? '' :
                                `<button type="button" data-delete-id="${row.id}" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-2 text-xs font-semibold text-red-700 shadow-sm hover:bg-red-100" data-ui-tooltip="ลบ" aria-label="ลบ"><i class="fas fa-trash"></i></button>`;
                            const pathTip = row.source_path ?
                                ` data-ui-tooltip="${escAttr(row.source_path)}"` : '';
                            return `
                            <tr class="odd:bg-white even:bg-slate-50/40 hover:bg-slate-100/60 transition-colors">
                                <td class="px-3 py-3 align-top text-center text-slate-500 tabular-nums">${start + idx + 1}</td>
                                <td class="px-3 py-3 align-top font-semibold text-center tabular-nums">${row.code ?? '-'}</td>
                                <td class="px-3 py-3 align-top text-xs text-slate-700 whitespace-normal break-words"${pathTip}>${row.source_path ?? '-'}</td>
                                <td class="px-3 py-3 align-top text-right bg-blue-50/40 tabular-nums">${money(row.amount)}</td>
                                <td class="px-3 py-3 align-top text-right bg-blue-50/40 tabular-nums ${Number(row.dw || 0) >= 0 ? 'text-green-700' : 'text-red-700'}">${money(row.dw)}</td>
                                <td class="px-3 py-3 align-top text-right bg-blue-50/40 font-semibold tabular-nums">${money(row.balance)}</td>
                                <td class="px-3 py-3 align-top text-right bg-green-50/40 tabular-nums">${money(row.plan_used)}</td>
                                <td class="px-3 py-3 align-top text-right bg-green-50/40 tabular-nums ${Number(row.plan_balance || 0) < 0 ? 'text-red-700' : ''}">${money(row.plan_balance)}</td>
                                <td class="px-3 py-3 align-top text-right bg-amber-50/40 tabular-nums">${disbursementBtn(row.withdraw_amount, 'approved', row.id)}</td>
                                <td class="px-3 py-3 align-top text-right bg-amber-50/40 tabular-nums">${money(row.withdraw_balance)}</td>
                                <td class="px-3 py-3 align-top text-right bg-amber-50/40 text-orange-700 tabular-nums">${disbursementBtn(row.withdraw_wait, 'pending', row.id)}</td>
                                <td class="px-3 py-3 align-top text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="${@json(url('/budget/manage'))}/${row.id}" class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-2 text-xs font-semibold text-blue-700 shadow-sm hover:bg-blue-100" data-ui-tooltip="จัดการ" aria-label="จัดการ"><i class="fas fa-cog"></i></a>
                                        <button type="button" data-edit-id="${row.id}" class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs font-semibold text-amber-700 shadow-sm hover:bg-amber-100" data-ui-tooltip="แก้ไข" aria-label="แก้ไข"><i class="fas fa-edit"></i></button>
                                        ${deleteBtn}
                                    </div>
                                </td>
                            </tr>
                        `;
                        }).join('');

                    }

                    // Update info & pagination
                    if (tableInfo) {
                        tableInfo.textContent = filteredRows.length > 0 ?
                            `แสดง ${start + 1} - ${Math.min(end, filteredRows.length)} จาก ${filteredRows.length} รายการ` :
                            '';
                    }
                    if (paginationPage) {
                        if (viewAll) paginationPage.textContent = 'แสดงทั้งหมด';
                        else paginationPage.textContent = `หน้า ${Math.min(currentPage, totalPages)}/${totalPages}`;
                    }
                    if (btnPrev) btnPrev.disabled = viewAll ? true : currentPage <= 1;
                    if (btnNext) btnNext.disabled = viewAll ? true : currentPage >= totalPages;
                    if (btnViewAll) btnViewAll.textContent = viewAll ? 'แสดงน้อยลง' : 'แสดงทั้งหมด';
                    updateSortIndicators();
                }

                // ========== CRUD ==========
                function resetForm() {
                    refreshBudgetManageModalRefs();
                    if (fId) fId.value = '';
                    if (fCode) fCode.value = '';
                    if (fCategoryPayDetail) fCategoryPayDetail.value = '';
                    if (fAmount) fAmount.value = '';
                    if (fDetail) fDetail.value = '';
                    setHiddenValue('f-year', '');
                    setHiddenValue('f-source', '');
                    setHiddenValue('f-fund', '');
                    setHiddenValue('f-cost', '');
                    setHiddenValue('f-category-budget', '');
                    setHiddenValue('f-category-pay', '');
                    setHiddenValue('f-type', '');
                    const today = new Date().toISOString().slice(0, 10);
                    if (fDate) {
                        fDate.value = today;
                        if (fDate._flatpickr) {
                            fDate._flatpickr.setDate(today, true, 'Y-m-d');
                        }
                    }
                    setHiddenValue('f-plan', '');
                    setHiddenValue('f-event', '');
                    if (window.PBMSearchableDropdown?.setOptions) {
                        window.PBMSearchableDropdown.setOptions('budget-manage-events', []);
                    }
                }

                function openModal(title) {
                    const titleEl = document.getElementById('budget-modal-title');
                    if (titleEl) titleEl.textContent = title;
                    // Ensure teleported dropdowns are bound before any cascade refresh happens.
                    if (window.PBMSearchableDropdown?.initAll) window.PBMSearchableDropdown.initAll();
                    window.dispatchEvent(new CustomEvent('open-modal', {
                        detail: 'budget-manage-modal'
                    }));
                    // Bind cascade after modal inserts into DOM.
                    setTimeout(() => {
                        ensureModalCascadeBindings();
                        if (window.PBMDatePicker?.initAll) window.PBMDatePicker.initAll();
                    }, 0);
                }

                function openEdit(id) {
                    const row = allRows.find(x => Number(x.id) === id);
                    if (!row) return;
                    openModal('แก้ไขงบประมาณ');

                    // Wait for teleported modal DOM, then populate fields.
                    setTimeout(() => {
                        (async () => {
                            if (window.PBMSearchableDropdown?.initAll) window.PBMSearchableDropdown.initAll();
                            ensureModalCascadeBindings();
                            if (window.PBMDatePicker?.initAll) window.PBMDatePicker.initAll();

                            const elId = document.getElementById('f-id');
                            const elYear = document.getElementById('f-year');
                            const elCode = document.getElementById('f-code');
                            const elSource = document.getElementById('f-source');
                            const elPlan = document.getElementById('f-plan');
                            const elFund = document.getElementById('f-fund');
                            const elCost = document.getElementById('f-cost');
                            const elCategoryBudget = document.getElementById('f-category-budget');
                            const elCategoryPay = document.getElementById('f-category-pay');
                            const elCategoryPayDetail = document.getElementById('f-category-pay-detail');
                            const elType = document.getElementById('f-type');
                            const elAmount = document.getElementById('f-amount');
                            const elDate = document.getElementById('f-date');
                            const elDetail = document.getElementById('f-detail');

                            const planCode = row.plan_code ?? '';
                            const preselect = buildEventActivityPreselect(row.event_code, row.activity_code);

                            suppressPlanCascade = true;
                            try {
                                if (elId) elId.value = row.id ?? '';
                                if (elYear) setHiddenValue('f-year', row.year ?? '');
                                if (elCode) elCode.value = row.code ?? '';
                                if (elSource) setHiddenValue('f-source', row.source_code ?? '');
                                if (elPlan) setHiddenValue('f-plan', planCode);
                                await refreshEventsByPlan(planCode, preselect);
                            } finally {
                                suppressPlanCascade = false;
                            }

                            if (elFund) setHiddenValue('f-fund', row.fund_code ?? '');
                            if (elCost) setHiddenValue('f-cost', row.cost_code ?? '');
                            if (elCategoryBudget) setHiddenValue('f-category-budget', row.category_budget_code ?? '');
                            if (elCategoryPay) setHiddenValue('f-category-pay', row.category_pay_code ?? '');
                            if (elCategoryPayDetail) elCategoryPayDetail.value = row.category_pay_detail ?? '';
                            if (elType) setHiddenValue('f-type', row.type_code ?? '');
                            if (elAmount) elAmount.value = row.amount ?? '';
                            if (elDetail) elDetail.value = row.detail ?? '';

                            if (elDate) {
                                const rawDate = String(row.date || '').trim();
                                const dateMatch = rawDate.match(/^(\d{4})-(\d{2})-(\d{2})/);
                                let dateValue = rawDate;
                                if (dateMatch && Number(dateMatch[1]) >= 2400) {
                                    dateValue = `${String(Number(dateMatch[1]) - 543).padStart(4, '0')}-${dateMatch[2]}-${dateMatch[3]}`;
                                }
                                elDate.value = dateValue;
                                if (elDate._flatpickr) {
                                    elDate._flatpickr.setDate(dateValue || null, true, 'Y-m-d');
                                }
                            }
                        })();
                    }, 0);
                }

                function deleteRow(id) {
                    pendingDeleteBudgetManageId = Number(id) || 0;
                    if (!pendingDeleteBudgetManageId) return;
                    window.dispatchEvent(new CustomEvent('open-modal', {
                        detail: 'budget-manage-delete-confirm-modal'
                    }));
                }

                // Filtering can replace a row between pointerdown and click. Handle the action
                // on pointerdown, then reserve click for keyboard activation.
                let pointerActionButton = null;
                const handleTableAction = (e) => {
                    const editButton = e.target.closest('[data-edit-id]');
                    const deleteButton = e.target.closest('[data-delete-id]');
                    const actionButton = editButton || deleteButton;
                    if (!actionButton) return;

                    e.preventDefault();
                    e.stopPropagation();

                    if (e.type === 'click' && pointerActionButton === actionButton) {
                        pointerActionButton = null;
                        return;
                    }

                    if (e.type === 'pointerdown') {
                        pointerActionButton = actionButton;
                    }

                    if (editButton) {
                        openEdit(Number(editButton.dataset.editId));
                    } else {
                        e.preventDefault();
                        deleteRow(Number(deleteButton.dataset.deleteId));
                    }
                };
                tbody.addEventListener('pointerdown', handleTableAction, true);
                tbody.addEventListener('click', handleTableAction);

                async function loadRows() {
                    try {
                        tbody.innerHTML =
                            `<tr><td colspan="12" class="px-3 py-6 text-center text-slate-400"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลด...</td></tr>`;
                        const response = await fetch(@json(route('budget.api.manage.list')));
                        const payload = await response.json();
                        allRows = payload?.data || [];
                        filteredRows = [...allRows];
                        applyFilters();
                    } catch (error) {
                        tbody.innerHTML =
                            `<tr><td colspan="12" class="px-3 py-6 text-center text-red-600">โหลดข้อมูลไม่สำเร็จ</td></tr>`;
                    }
                }

                window.__budgetManageConfirmDelete = async () => {
                    const id = pendingDeleteBudgetManageId;
                    pendingDeleteBudgetManageId = 0;
                    try {
                        if (!id) return;
                        const res = await fetch(@json(route('budget.api.manage.delete')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({
                                budgetManageId: id
                            }),
                        });
                        const json = await res.json();
                        if (json.status !== 'success') {
                            toastFail(json.message || 'ลบไม่สำเร็จ');
                            return;
                        }
                        removeManageRow(id);
                        toastOk('ลบรายการสำเร็จ');
                    } catch (e) {
                        toastFail('เกิดข้อผิดพลาด');
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', {
                            detail: 'budget-manage-delete-confirm-modal'
                        }));
                    }
                };

                // ========== Event Bindings ==========
                if (btnAdd) {
                    btnAdd.addEventListener('click', () => {
                        resetForm();
                        openModal('เพิ่มงบประมาณ');
                    });
                }

                // ฟอร์มอยู่ใน x-teleport — ตอนโหลดสคริปต์ยังหา #budget-manage-form ไม่เจอ จึงใช้ delegation
                document.addEventListener('submit', async (e) => {
                    const target = e.target;
                    if (!target || target.id !== 'budget-manage-form') return;
                    e.preventDefault();
                    refreshBudgetManageModalRefs();
                    const payload = {
                        budgetManageId: Number(fId?.value || 0),
                        budgetManageYear: fYear?.value ?? '',
                        budgetManageCode: (fCode?.value ?? '').trim(),
                        budgetManageSource: Number(fSource?.value || 0),
                        budgetManagePlan: Number(fPlan?.value || 0),
                        budgetManageEvent: `${fEvent?.value || ''}`,
                        budgetManageFund: Number(fFund?.value || 0),
                        budgetManageCost: Number(fCost?.value || 0),
                        budgetManageCategoryBudget: Number(fCategoryBudget?.value || 0),
                        budgetManageCategoryPay: Number(fCategoryPay?.value || 0),
                        budgetManageCategoryPayDetail: (fCategoryPayDetail?.value ?? '').trim(),
                        budgetManageType: Number(fType?.value || 0),
                        budgetManageAmount: Number(fAmount?.value || 0),
                        budgetManageDate: fDate?.value ?? '',
                        budgetManageDetail: (fDetail?.value ?? '').trim(),
                    };

                    try {
                        const res = await fetch(@json(route('budget.api.manage.save')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(payload),
                        });
                        const json = await res.json();
                        if (json.status !== 'success' || !json.data) {
                            toastFail(json.message || 'บันทึกไม่สำเร็จ');
                            return;
                        }
                        upsertManageRow(json.data);
                        const isEdit = Number(payload.budgetManageId || 0) > 0;
                        toastOk(isEdit ? 'แก้ไขข้อมูลสำเร็จ' : 'บันทึกข้อมูลสำเร็จ');
                        window.dispatchEvent(new CustomEvent('close-modal', {
                            detail: 'budget-manage-modal'
                        }));
                    } catch (err) {
                        toastFail('เกิดข้อผิดพลาด');
                    }
                }, true);

                // Filter bindings
                sourceButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        sourceButtons.forEach(b => {
                            b.classList.remove('active');
                            applySourceButtonStyle(b, false);
                        });
                        btn.classList.add('active');
                        applySourceButtonStyle(btn, true);
                        currentSourceFilter = btn.dataset.filterSource;
                        applyFilters();
                    });
                });

                // A search input fires `change` when it loses focus. Listening to that event
                // re-rendered the table just before a row action's click event, swallowing the
                // first click after searching. Search only needs live `input` filtering.
                if (filterSearch) {
                    filterSearch.addEventListener('input', () => {
                        updateSearchClearButton();
                        applyFilters();
                    });
                }
                if (btnClearSearch) {
                    btnClearSearch.addEventListener('click', () => {
                        if (!filterSearch) return;
                        filterSearch.value = '';
                        updateSearchClearButton();
                        applyFilters();
                        filterSearch.focus();
                    });
                }

                [filterYear, filterType, filterPlan, filterFund, filterCost, filterCategory].forEach(el => {
                    if (el) el.addEventListener('input', applyFilters);
                    if (el) el.addEventListener('change', applyFilters);
                });

                // Cascade bindings are attached on modal open (teleported DOM).
                window.addEventListener('open-modal', (e) => {
                    if (e?.detail !== 'budget-manage-modal') return;
                    setTimeout(() => {
                        ensureModalCascadeBindings();
                    }, 0);
                });

                // Sort by clicking header cells
                document.querySelectorAll('th[data-sort-key]').forEach(th => {
                    th.addEventListener('click', () => {
                        const k = th.getAttribute('data-sort-key') || '';
                        if (!k) return;
                        if (sortKey === k) sortDir = (sortDir === 'asc') ? 'desc' : 'asc';
                        else {
                            sortKey = k;
                            sortDir = 'asc';
                        }
                        filteredRows = applySort(filteredRows);
                        currentPage = 1;
                        renderTable();
                    });
                });

                if (btnReset) btnReset.addEventListener('click', resetFilters);
                if (btnPrev) btnPrev.addEventListener('click', () => {
                    if (viewAll) return;
                    if (currentPage > 1) {
                        currentPage--;
                        renderTable();
                    }
                });
                if (btnNext) btnNext.addEventListener('click', () => {
                    if (viewAll) return;
                    currentPage++;
                    renderTable();
                });
                if (btnViewAll) btnViewAll.addEventListener('click', () => {
                    viewAll = !viewAll;
                    currentPage = 1;
                    renderTable();
                });

                // เริ่มด้วยค่าค้นหาที่ค้างไว้ในแท็บนี้ หรือปีงบประมาณปัจจุบันหากยังไม่เคยค้น
                if (!restoreSavedFilters()) {
                    applyDefaultYearFilter();
                }
                loadRows();
            })();
        </script>
    @endpush
</x-layouts.app>


