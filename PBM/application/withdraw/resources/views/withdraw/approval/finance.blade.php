<x-layouts.app>
    <div class="py-2">
        <div class="mx-2 flex flex-col gap-4">
            @php
                $isDocumentReviewMode = true;
                $pageHeading = trim((string) ($title ?? '')) !== '' ? (string) $title : 'ตรวจสอบเอกสารเบิกจ่าย';
                $pageDescription = 'หน้านี้แสดงรายการเบิกจ่ายทุกประเภทสำหรับการตรวจสอบเอกสารตามสิทธิ์ของผู้ใช้งาน';
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
                    </div>
                </div>
            </div>

            @php
                $sampleRows = collect($withdrawRows ?? []);
                $financeDocumentCode = '12010401';
                $bookNumberByType = [];
                $generalBookNumberSuffix = '';
                $financeMemoBookSuffix = '';
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('withdraw_set_book_number')) {
                        $bookNumberRows = \Illuminate\Support\Facades\DB::table('withdraw_set_book_number')
                            ->where('withdraw_set_book_number_status', 1)
                            ->orderBy('withdraw_set_book_number_type')
                            ->orderBy('withdraw_set_book_number_value')
                            ->get([
                                'withdraw_set_book_number_type',
                                'withdraw_set_book_number_value',
                            ]);

                        foreach ($bookNumberRows as $bookNumberRow) {
                            $bookType = trim((string) ($bookNumberRow->withdraw_set_book_number_type ?? ''));
                            $bookValue = trim((string) ($bookNumberRow->withdraw_set_book_number_value ?? ''));
                            if ($bookType === '' || $bookValue === '') {
                                continue;
                            }
                            if (! array_key_exists($bookType, $bookNumberByType)) {
                                $bookNumberByType[$bookType] = $bookValue;
                            }
                        }

                        foreach ($bookNumberByType as $bookType => $bookValue) {
                            if (mb_strpos((string) $bookType, 'ทั่วไป') !== false) {
                                $generalBookNumberSuffix = (string) $bookValue;
                                break;
                            }
                        }

                        if ($generalBookNumberSuffix === '' && count($bookNumberByType) > 0) {
                            $generalBookNumberSuffix = (string) (reset($bookNumberByType) ?: '');
                        }

                        foreach ($bookNumberByType as $bookType => $bookValue) {
                            $bt = (string) $bookType;
                            if (mb_strpos($bt, 'งานการเงิน') !== false || mb_strpos($bt, 'การเงิน') !== false) {
                                $financeMemoBookSuffix = (string) $bookValue;
                                break;
                            }
                        }

                        // ถ้ายังไม่ได้ตั้งเลขประเภทการเงินแยก ให้ใช้ค่าของเบิกจ่ายทั่วไป (เฉพาะ suffix ในตารางตั้งค่า ไม่ใช่เลขจากรายการ withdraw)
                        if ($financeMemoBookSuffix === '' && $generalBookNumberSuffix !== '') {
                            $financeMemoBookSuffix = $generalBookNumberSuffix;
                        }
                        if ($financeMemoBookSuffix === '' && count($bookNumberByType) > 0) {
                            $financeMemoBookSuffix = (string) (reset($bookNumberByType) ?: '');
                        }
                    }
                } catch (\Throwable $e) {
                    $bookNumberByType = [];
                    $generalBookNumberSuffix = '';
                    $financeMemoBookSuffix = '';
                }

                $financeDepartmentBookNumberFromApi = '';
                $departmentApiUrl = (string) config('services.department_api.url', '');
                $departmentApiToken = (string) config('services.department_api.token', '');
                try {
                    if ($departmentApiUrl !== '' && $departmentApiToken !== '') {
                        $departmentResponse = \Illuminate\Support\Facades\Http::withToken($departmentApiToken)
                            ->acceptJson()
                            ->timeout(10)
                            ->get($departmentApiUrl);
                        $departmentRows = $departmentResponse->json('data', []);
                        if (is_array($departmentRows)) {
                            foreach ($departmentRows as $row) {
                                $depCode = trim((string) ($row['dep_code'] ?? ''));
                                $jobCode = trim((string) ($row['job_code'] ?? ''));
                                $unitCode = trim((string) ($row['unit_code'] ?? ''));
                                if (! in_array($financeDocumentCode, [$unitCode, $jobCode, $depCode], true)) {
                                    continue;
                                }
                                $depDocumentNumber = trim((string) ($row['dep_document_number'] ?? ''));
                                $jobDocumentNumber = trim((string) ($row['job_document_number'] ?? ''));
                                $unitDocumentNumber = trim((string) ($row['document_number'] ?? ''));
                                $financeDepartmentBookNumberFromApi = $unitCode === $financeDocumentCode
                                    ? ($unitDocumentNumber !== '' ? $unitDocumentNumber : ($jobDocumentNumber !== '' ? $jobDocumentNumber : $depDocumentNumber))
                                    : ($jobCode === $financeDocumentCode
                                        ? ($jobDocumentNumber !== '' ? $jobDocumentNumber : $depDocumentNumber)
                                        : $depDocumentNumber);
                                if ($financeDepartmentBookNumberFromApi !== '') {
                                    break;
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $financeDepartmentBookNumberFromApi = '';
                }

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
                            <h5 class="text-base font-semibold text-slate-900 sm:text-lg">รายการรอตรวจสอบ</h5>
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
                        <button type="button" id="approval-tab-pending"
                            class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 ring-1 ring-amber-100 transition hover:bg-amber-100">
                            <i class="fas fa-hourglass-half text-[10px]"></i>
                            รอตรวจสอบ
                        </button>
                        <button type="button" id="approval-tab-approved"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">
                            <i class="fas fa-circle-check text-[10px]"></i>
                            ตรวจสอบแล้ว
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
    <x-modal name="withdraw-finance-review-form-modal" maxWidth="6xl" :static="true" variant="modern"
        title="ตรวจสอบเอกสารเบิกจ่าย" description="ข้อมูลในแบบฟอร์มนี้เพื่อใช้ประกอบการตรวจสอบเอกสาร"
        iconClass="fas fa-file-circle-check">
        <div class="p-6">
            <div id="finance-review-detail-loading"
                class="hidden rounded-xl border border-slate-200 bg-white px-4 py-16 text-center text-sm text-slate-500">
                <i class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></i>กำลังโหลดรายละเอียด...
            </div>
            <div id="finance-review-loaded-content">
            <div id="finance-expense-dropdown-template" class="hidden">
                <x-searchable-dropdown id="finance-expense-map-template" placeholder="พิมพ์เพื่อค้นหาผังบัญชี (รายจ่าย)..."
                    :options="[]" valueKey="id" textKey="label" optionsKey="finance-expense-map-template" :showClear="true"
                    clearTitle="ล้างผังบัญชี" />
            </div>
            <div id="finance-revision-info-card" class="mb-3 hidden rounded-xl border border-rose-200 bg-rose-50/70 p-3">
                <div class="text-xs font-bold text-rose-700">ข้อมูลการส่งกลับให้แก้ไข</div>
                <div class="mt-1 text-xs text-slate-700">
                    <span class="font-semibold">ส่งกลับจาก:</span>
                    <span id="finance-revision-from">-</span>
                </div>
                <div class="mt-1 text-xs text-slate-700">
                    <span class="font-semibold">รายละเอียดที่ต้องแก้ไข:</span>
                    <span id="finance-revision-comment">-</span>
                </div>
            </div>
            <div id="finance-revision-explanation-card" class="mb-3 hidden rounded-xl border border-sky-200 bg-sky-50/80 p-3">
                <div class="text-xs font-bold text-sky-800">คำชี้แจงการแก้ไข</div>
                <div id="finance-revision-explanation" class="mt-1 whitespace-pre-wrap text-sm text-slate-800">-</div>
            </div>
            <div id="finance-review-detail-content" class="rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700">
                <div class="text-slate-400">- ไม่มีข้อมูล -</div>
            </div>

            <div id="finance-procurement-card" class="mt-3 hidden rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                <div class="mb-2 flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700 ring-1 ring-sky-200">
                        <i class="fas fa-folder-open text-xs" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-slate-800">เอกสารจัดซื้อจัดจ้าง</div>
                        <div class="text-xs text-slate-500">รายการเอกสารจัดซื้อจัดจ้างของการเบิกจ่ายนี้</div>
                    </div>
                </div>
                <div id="finance-procurement-empty" class="hidden rounded-xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                    ยังไม่มีเอกสารจัดซื้อจัดจ้างของรายการนี้
                </div>
                <div id="finance-procurement-wrap" class="space-y-3">
                    <div id="finance-procurement-body" class="space-y-3"></div>
                </div>
            </div>

            <div id="finance-memo-section">
                <div class="mt-4 flex items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 px-3 py-2.5">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                        <i class="fas fa-file-lines text-xs" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-slate-800">บันทึกเสนอ งานการเงิน</div>
                        <div class="text-xs text-slate-500">กรอกข้อมูลสำหรับเอกสารประกอบการตรวจสอบและเสนอพิจารณา</div>
                    </div>
                </div>

                <div class="mt-3 rounded-xl border border-slate-200 bg-white/80 p-3">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label for="finance-request-document-number" class="mb-1 block text-xs font-bold text-slate-700">ที่</label>
                            <input id="finance-request-document-number" type="text"
                                class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm transition-[box-shadow,border-color] duration-300 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label for="finance-request-document-date" class="mb-1 block text-xs font-bold text-slate-700">วันที่</label>
                            <x-date-picker
                                id="finance-request-document-date"
                                placeholder="เลือกวันที่"
                                :withIcon="false"
                                inputClass="rounded-xl border-slate-300 text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            />
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="finance-request-subject" class="mb-1 block text-xs font-bold text-slate-700">เรื่อง</label>
                        <input id="finance-request-subject" type="text"
                            class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                    </div>
                    <div class="mt-3">
                        <label for="finance-request-to" class="mb-1 block text-xs font-bold text-slate-700">เรียน</label>
                        <input id="finance-request-to" type="text"
                            class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                    </div>
                    <div class="mt-3">
                        <label for="finance-request-paragraph1" class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 1</label>
                        <textarea id="finance-request-paragraph1" rows="2"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"></textarea>
                    </div>
                    <div class="relative z-30 mt-3 overflow-visible">
                        <label class="mb-1 block text-xs font-bold text-slate-700">งบประมาณ</label>
                        <x-searchable-dropdown id="finance-budget-source" placeholder="พิมพ์เพื่อค้นหางบประมาณ..."
                            :options="[]" valueKey="id" textKey="label" optionsKey="finance-budget-source-options" :showClear="true"
                            clearTitle="ล้างงบประมาณ" />
                    </div>
                    <div id="finance-expense-mapping-card" class="relative z-20 mt-3 overflow-visible rounded-xl border border-slate-200 bg-slate-50/70 p-3 isolate focus-within:z-[2147483645]">
                        <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700 ring-1 ring-cyan-200">
                                    <i class="fas fa-diagram-project text-xs" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <div class="text-sm font-bold text-slate-800">รายการเบิกจ่ายสำหรับผูกกับผังบัญชี</div>
                                    <div id="finance-expense-map-progress" class="text-xs text-slate-500">จำนวนเงินเดิมแสดงไว้ให้ดู คอลัมน์ถัดไปกรอกจำนวนของงานการเงิน</div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" id="finance-expense-reset-amounts"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700"
                                    style="background-color:#ffffff;color:#334155;border-color:#cbd5e1;">
                                    ใช้จำนวนเดิมทั้งหมด
                                </button>
                                <button type="button" id="finance-expense-copy-chart"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700"
                                    style="background-color:#ffffff;color:#334155;border-color:#cbd5e1;">
                                    ใช้ผังบัญชีกับแถวที่ว่าง
                                </button>
                            </div>
                        </div>
                        <div class="relative z-30 overflow-x-auto overflow-y-visible rounded-xl border border-slate-200 bg-white">
                            <table class="relative w-full table-fixed border-collapse overflow-visible text-sm">
                                <colgroup>
                                    <col style="width:3.25rem">
                                    <col>
                                    <col style="width:8.5rem">
                                    <col style="width:9.25rem">
                                    <col style="width:34%">
                                </colgroup>
                                <thead>
                                    <tr class="bg-slate-50 text-slate-700">
                                        <th class="border border-slate-200 px-2 py-2 text-center align-middle font-bold">ลำดับ</th>
                                        <th class="border border-slate-200 px-3 py-2 text-left align-middle font-bold">รายการเบิกจ่าย</th>
                                        <th class="border border-slate-200 px-2 py-2 text-right align-middle font-bold leading-tight">จำนวนเงิน</th>
                                        <th class="border border-slate-200 px-2 py-2 text-right align-middle font-bold leading-tight">จำนวนเงิน<br>(งานการเงิน)</th>
                                        <th class="border border-slate-200 px-3 py-2 text-left align-middle font-bold">ผังบัญชี (รายจ่าย)</th>
                                    </tr>
                                </thead>
                                <tbody id="finance-expense-mapping-body">
                                    <tr>
                                        <td colspan="5" class="border border-slate-200 px-3 py-3 text-center text-slate-400">- ยังไม่มีรายการเบิกจ่าย -</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="finance-request-paragraph2" class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 2</label>
                        <textarea id="finance-request-paragraph2" rows="4"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"></textarea>
                    </div>
                    <div class="mt-3">
                        <label for="finance-request-paragraph3" class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 3</label>
                        <textarea id="finance-request-paragraph3" rows="2"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"></textarea>
                    </div>
                    <div id="finance-budget-summary-card" class="mt-3 hidden rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                        <div class="mb-2 text-xs font-bold text-slate-700">สรุปงบประมาณ <span id="finance-budget-summary-title" class="font-semibold text-slate-600"></span></div>
                        <div class="overflow-x-auto">
                            <table class="w-full table-fixed border-collapse border border-slate-300 text-sm">
                                <thead>
                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">งบประมาณ</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติแล้ว</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติครั้งนี้</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">รวมอนุมัติ</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">คงเหลือ</th>
                                    </tr>
                                </thead>
                                <tbody id="finance-budget-summary-body">
                                    <tr>
                                        <td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="5">- ยังไม่ได้เลือกงบประมาณ -</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="finance-esign-upload-card" class="mt-3 overflow-hidden rounded-2xl border border-violet-200/80 bg-gradient-to-br from-violet-50/80 via-white to-slate-50 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-violet-100/80 bg-white/70 px-3 py-2.5 backdrop-blur-sm">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-600 text-white shadow-sm shadow-violet-200">
                            <i class="fas fa-file-signature text-xs" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-sm font-bold text-slate-800">เอกสารสำหรับผู้บริหารลงนาม</div>
                                <span id="finance-esign-count-badge"
                                    class="hidden inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-semibold text-violet-700 ring-1 ring-violet-200">
                                    0 รายการ
                                </span>
                            </div>
                            <div class="text-xs text-slate-500">กรอกชื่อเอกสาร เช่น ใบสำคัญตั้งหนี้ แล้วเลือกหรือลากไฟล์ PDF มาวาง — บันทึกเมื่อยืนยันตรวจสอบ</div>
                        </div>
                    </div>
                    <button type="button" id="finance-esign-add-row"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-violet-700 bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-violet-200 transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-300"
                        style="background-color:#7c3aed;color:#ffffff;border-color:#6d28d9;">
                        <i class="fas fa-plus text-[10px]" style="color:#ffffff;"></i>
                        เพิ่มเอกสาร
                    </button>
                </div>
                <div class="p-2.5">
                    <div id="finance-esign-rows" class="space-y-2"></div>
                    <div id="finance-esign-empty"
                        class="rounded-xl border-2 border-dashed border-violet-200 bg-white/80 px-3 py-4 text-center">
                        <div class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-500 ring-1 ring-violet-100">
                            <i class="fas fa-cloud-upload-alt text-sm" aria-hidden="true"></i>
                        </div>
                        <div class="text-sm font-semibold text-slate-700">ยังไม่มีเอกสาร e-Sign</div>
                        <div class="mt-1 text-xs text-slate-500">กด “เพิ่มเอกสาร” เพื่อเริ่มกรอกชื่อและแนบไฟล์ PDF</div>
                        <button type="button" id="finance-esign-empty-add"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-100">
                            <i class="fas fa-plus text-[10px]"></i>
                            เพิ่มเอกสารแรก
                        </button>
                    </div>
                </div>
            </div>

            <div id="finance-attached-docs-card" class="mt-3 hidden rounded-xl border border-violet-200 bg-violet-50/40 p-3">
                <div class="mb-2 flex items-start gap-2">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700 ring-1 ring-violet-200">
                        <i class="fas fa-file-signature text-xs" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-slate-800">เอกสารการเงิน</div>
                        <div class="text-xs text-slate-500">เอกสาร e-Sign ที่งานการเงินแนบตอนตรวจสอบเบิกจ่าย</div>
                    </div>
                </div>
                <div id="finance-attached-docs-empty" class="hidden rounded-xl border border-dashed border-violet-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                    ยังไม่มีเอกสารการเงินของรายการนี้
                </div>
                <div id="finance-attached-docs-wrap" class="space-y-3">
                    <div id="finance-attached-docs-body" class="space-y-3"></div>
                </div>
            </div>

            <div id="finance-approval-route-slot" class="mt-3 hidden"></div>

            <div class="mt-3">
                <label for="finance-review-comment" class="mb-1 block text-xs font-bold text-slate-700">ความคิดเห็นจากงานการเงิน (ถ้ามี)</label>
                <textarea id="finance-review-comment" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" placeholder="ระบุความคิดเห็นเพิ่มเติม"></textarea>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'withdraw-finance-review-form-modal')"
                    class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    ปิด
                </button>
                <button type="button" id="finance-review-revision-button"
                    class="inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-rose-600 to-red-500">
                    ส่งกลับให้แก้ไข
                </button>
                <button type="button" id="finance-review-submit-button"
                    class="inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-emerald-600 to-green-500">
                    ยืนยันตรวจสอบ
                </button>
            </div>
            </div>
        </div>
    </x-modal>
    <x-modal name="withdraw-finance-revision-modal" maxWidth="lg" :static="true" variant="modern"
        title="ส่งกลับให้แก้ไข" description="โปรดระบุรายละเอียดที่ต้องแก้ไขก่อนส่งกลับรายการ"
        iconClass="fas fa-rotate-left">
        <div class="p-6">
            <div>
                <label for="finance-revision-reason" class="mb-1 block text-sm font-bold text-slate-700">รายละเอียดที่ต้องแก้ไข</label>
                <textarea id="finance-revision-reason" rows="5" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" placeholder="ระบุรายการที่ต้องแก้ไขให้ชัดเจน"></textarea>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'withdraw-finance-revision-modal')"
                    class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    ยกเลิก
                </button>
                <button type="button" id="finance-revision-submit-button"
                    class="inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-rose-600 to-red-500">
                    ส่งให้แก้ไข
                </button>
            </div>
        </div>
    </x-modal>
    <x-confirm-modal
        name="withdraw-finance-review-confirm-modal"
        maxWidth="sm"
        :title="__('ยืนยันตรวจสอบเอกสารเบิกจ่าย?')"
        :message="__('ท่านต้องการยืนยันการส่งรายการเบิกจ่ายนี้ไปยังผู้อนุมัติลำดับถัดไปหรือไม่')"
        confirmText="{{ __('ยืนยันตรวจสอบ') }}"
        cancelText="{{ __('ยกเลิก') }}"
        confirmVariant="primary"
        confirmClick="window.__withdrawFinanceConfirmSubmit()"
    />
    <x-modal name="withdraw-approval-confirm-modal" maxWidth="md" :static="true" variant="modern"
        title="ยืนยันตรวจสอบรายการ?" description="โปรดตรวจสอบข้อมูล และระบุความคิดเห็น (ถ้ามี) ก่อนยืนยันตรวจสอบรายการ"
        iconClass="fas fa-circle-check">
        <div class="p-6">
            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">เลขที่:</span> <span id="approval-confirm-detail-code">-</span></div>
                <div><span class="font-semibold text-slate-800">วันที่:</span> <span id="approval-confirm-detail-date">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="approval-confirm-detail-subject">-</span></div>
                <div><span class="font-semibold text-slate-800">ผู้ขอเบิก:</span> <span id="approval-confirm-detail-applicant">-</span></div>
                <div><span class="font-semibold text-slate-800">จำนวนเงิน:</span> <span id="approval-confirm-detail-amount">-</span> บาท</div>
            </div>
            <div class="mt-4">
                <label for="withdraw-approval-comment" class="mb-1 block text-sm font-semibold text-slate-700">ความคิดเห็น (ถ้ามี)</label>
                <textarea id="withdraw-approval-comment" rows="4" placeholder="ระบุความคิดเห็นประกอบการตรวจสอบ (หากไม่ความคิดเห็นไม่ต้องระบุ)"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-approval-confirm-modal')"
                    class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    ยกเลิก
                </button>
                <button type="button" id="withdraw-approval-confirm-button"
                    class="inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-emerald-600 to-green-500">
                    ยืนยันตรวจสอบ
                </button>
            </div>
        </div>
    </x-modal>

    @push('scripts')
        <script>
            document.addEventListener('alpine:initialized', () => {
                const tableBody = document.getElementById('approval-general-table-body');
                const countEl = document.getElementById('approval-count');
                const filterFiscalYearSelect = document.getElementById('approval-filter-fiscal-year');
                const filterStatusSelect = document.getElementById('approval-filter-status');
                const filterTypeSelect = document.getElementById('approval-filter-type');
                const filterSearchInput = document.getElementById('approval-filter-search');
                const pendingTabButton = document.getElementById('approval-tab-pending');
                const approvedTabButton = document.getElementById('approval-tab-approved');
                const generalListApi = @json($generalListApiUrl ?? url('/withdraw/api/general/list?scope=document-review'));
                const approvalPendingListApi = @json(url('/withdraw/api/general/list?scope=reviewer-pending'));
                const generalProceedApi = @json(url('/withdraw/api/general/proceed'));
                const salaryProceedApi = @json(url('/withdraw/api/salary/proceed'));
                const generalDetailApiBase = @json(url('/withdraw/api/general'));
                const salaryDetailApiBase = @json(url('/withdraw/api/salary'));
                const generalNextRunningNoApi = @json(url('/withdraw/api/general/next-running-no'));
                const salaryNextRunningNoApi = @json(url('/withdraw/api/salary/next-running-no'));
                const expenseChartOptionsApi = @json(url('/withdraw/api/general/expense-chart-options'));
                const salaryExpenseChartOptionsApi = @json(url('/withdraw/api/salary/expense-chart-options'));
                const budgetSourceOptionsApi = @json(url('/withdraw/api/general/budget-source-options'));
                const salaryBudgetSourceOptionsApi = @json(url('/withdraw/api/salary/budget-source-options'));
                const payListOptionsApi = @json(url('/withdraw/api/general/pay-list-options'));
                const salaryPayListOptionsApi = @json(url('/withdraw/api/salary/pay-list-options'));
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
                const findFinanceRowById = (id, kind = '') => {
                    const numericId = Number(id || 0);
                    if (numericId <= 0) return null;
                    if (kind !== '') {
                        const normalizedKind = normalizeWithdrawKind(kind);
                        return generalTableRows.find((item) => Number(item.id || 0) === numericId && normalizeWithdrawKind(item.withdraw_kind) === normalizedKind) || null;
                    }
                    return generalTableRows.find((item) => Number(item.id || 0) === numericId) || null;
                };
                const proceedApiForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryProceedApi : generalProceedApi;
                const detailApiBaseForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryDetailApiBase : generalDetailApiBase;
                const nextRunningNoApiForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryNextRunningNoApi : generalNextRunningNoApi;
                const expenseChartApiForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryExpenseChartOptionsApi : expenseChartOptionsApi;
                const budgetSourceApiForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryBudgetSourceOptionsApi : budgetSourceOptionsApi;
                const payListApiForKind = (kind) => normalizeWithdrawKind(kind) === 'salary' ? salaryPayListOptionsApi : payListOptionsApi;
                const openFinanceDetailModal = async (id, kind = 'general', options = {}) => {
                    const resolvedKind = normalizeWithdrawKind(kind);
                    if (resolvedKind === 'salary' && window.WithdrawSalaryDetailModal?.openById) {
                        return window.WithdrawSalaryDetailModal.openById(id, options);
                    }
                    return window.WithdrawGeneralDetailModal?.openById?.(id, options);
                };
                const getFinanceDetailRow = (kind = 'general') => {
                    const resolvedKind = normalizeWithdrawKind(kind);
                    if (resolvedKind === 'salary') {
                        return window.WithdrawSalaryDetailModal?.getLastLoadedRow?.() || null;
                    }
                    return window.WithdrawGeneralDetailModal?.getLastLoadedRow?.() || null;
                };
                const financeDocumentCode = @json($financeDocumentCode ?? '12010401');
                const financeDepartmentBookNumberFromApi = @json($financeDepartmentBookNumberFromApi ?? '');
                const bookNumberByType = @json($bookNumberByType ?? (object) []);
                const generalBookNumberSuffixFallback = @json($generalBookNumberSuffix ?? '');
                const financeMemoBookSuffixFallback = @json($financeMemoBookSuffix ?? '');
                const approveCommentInput = document.getElementById('withdraw-approval-comment');
                const approveConfirmButton = document.getElementById('withdraw-approval-confirm-button');
                const approveDetailCodeEl = document.getElementById('approval-confirm-detail-code');
                const approveDetailDateEl = document.getElementById('approval-confirm-detail-date');
                const approveDetailSubjectEl = document.getElementById('approval-confirm-detail-subject');
                const approveDetailApplicantEl = document.getElementById('approval-confirm-detail-applicant');
                const approveDetailAmountEl = document.getElementById('approval-confirm-detail-amount');
                const statusClassMap = {
                    'ร่าง': 'bg-slate-100 text-slate-700 ring-slate-200',
                    'รอตรวจสอบ/อนุมัติ': 'bg-amber-100 text-amber-700 ring-amber-200',
                    'รอการเงินตรวจสอบเอกสาร': 'bg-cyan-100 text-cyan-700 ring-cyan-200',
                    'รอแก้ไข': 'bg-rose-100 text-rose-700 ring-rose-200',
                    'รออนุมัติ': 'bg-violet-100 text-violet-700 ring-violet-200',
                    'อนุมัติ': 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                };
                let generalTableRows = @json($sampleRows->values());
                let pendingApproveId = 0;
                let activeTab = 'pending';
                let pendingFinanceReviewId = 0;
                let pendingFinanceReviewKind = 'general';
                let pendingFinanceDetailRow = null;
                let expenseChartOptions = [];
                const expenseChartSelectionMap = new Map();
                const expenseDropdownTemplate = document.getElementById('finance-expense-dropdown-template');
                const financeReviewDetailContent = document.getElementById('finance-review-detail-content');
                const setFinanceReviewLoading = (isLoading) => {
                    document.getElementById('finance-review-detail-loading')?.classList.toggle('hidden', !isLoading);
                    document.getElementById('finance-review-loaded-content')?.classList.toggle('hidden', isLoading);
                };
                const financeReviewComment = document.getElementById('finance-review-comment');
                const financeApprovalRouteSlot = document.getElementById('finance-approval-route-slot');
                const financeReviewSubmitButton = document.getElementById('finance-review-submit-button');
                const financeRevisionButton = document.getElementById('finance-review-revision-button');
                const financeBudgetSourceInput = document.getElementById('finance-budget-source');
                const financeRevisionReasonInput = document.getElementById('finance-revision-reason');
                const financeRevisionSubmitButton = document.getElementById('finance-revision-submit-button');
                const financeBudgetSummaryCard = document.getElementById('finance-budget-summary-card');
                const financeBudgetSummaryBody = document.getElementById('finance-budget-summary-body');
                const financeBudgetSummaryTitle = document.getElementById('finance-budget-summary-title');
                const financeRevisionInfoCard = document.getElementById('finance-revision-info-card');
                const financeRevisionFromEl = document.getElementById('finance-revision-from');
                const financeRevisionCommentEl = document.getElementById('finance-revision-comment');
                const financeRevisionExplanationCard = document.getElementById('finance-revision-explanation-card');
                const financeRevisionExplanationEl = document.getElementById('finance-revision-explanation');
                const financeRequestDocumentNumberInput = document.getElementById('finance-request-document-number');
                const financeDocumentNumberHint = document.getElementById('finance-document-number-hint');
                const financeRequestDocumentDateInput = document.getElementById('finance-request-document-date');
                const financeRequestSubjectInput = document.getElementById('finance-request-subject');
                const financeRequestToInput = document.getElementById('finance-request-to');
                const financeRequestParagraph1Input = document.getElementById('finance-request-paragraph1');
                const financeRequestParagraph2Input = document.getElementById('finance-request-paragraph2');
                const financeRequestParagraph3Input = document.getElementById('finance-request-paragraph3');
                const financeExpenseMappingBody = document.getElementById('finance-expense-mapping-body');
                const financeMemoSection = document.getElementById('finance-memo-section');
                const financeProcurementCard = document.getElementById('finance-procurement-card');
                const financeProcurementEmpty = document.getElementById('finance-procurement-empty');
                const financeEsignRows = document.getElementById('finance-esign-rows');
                const financeEsignEmpty = document.getElementById('finance-esign-empty');
                const financeEsignAddRowButton = document.getElementById('finance-esign-add-row');
                const financeEsignEmptyAddButton = document.getElementById('finance-esign-empty-add');
                const financeEsignCountBadge = document.getElementById('finance-esign-count-badge');
                let financeEsignRowCounter = 0;
                const financeAttachedDocsCard = document.getElementById('finance-attached-docs-card');
                const financeAttachedDocsEmpty = document.getElementById('finance-attached-docs-empty');
                const financeAttachedDocsWrap = document.getElementById('finance-attached-docs-wrap');
                const financeAttachedDocsBody = document.getElementById('finance-attached-docs-body');
                const financeProcurementWrap = document.getElementById('finance-procurement-wrap');
                const financeProcurementBody = document.getElementById('finance-procurement-body');
                let budgetSourceOptions = [];
                const budgetSourceOptionMap = new Map();
                let pendingFinanceReviewAmount = 0;
                let pendingFinanceSubmitPayload = null;

                const escapeHtml = (unsafe) => String(unsafe ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[char]));
                const canEmbedPdfPreviewInFinance = () => {
                    if (typeof window.matchMedia !== 'function') return true;
                    return window.matchMedia('(min-width: 1024px)').matches
                        && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                };
                const stripPdfIframesForCompactView = (root) => {
                    if (!root || canEmbedPdfPreviewInFinance()) return;
                    root.querySelectorAll('iframe').forEach((iframe) => iframe.remove());
                };
                const renderFinanceProcurementPreviewHtml = (rows = []) => rows.map((item) => {
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
                    if (canEmbedPdfPreviewInFinance()) {
                        return `<div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-2">${caption}<iframe src="${escapeHtml(pdfUrl)}" title="${escapeHtml(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`;
                    }
                    return `<div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3">
                        ${caption}
                        <div class="flex flex-col gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-700 ring-1 ring-rose-100"><i class="fas fa-file-pdf"></i></span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>
                                    <div class="text-xs text-slate-500">เปิดไฟล์เพื่อดูบนอุปกรณ์นี้</div>
                                </div>
                            </div>
                            <a href="${escapeHtml(pdfUrl)}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-100">
                                เปิด PDF
                            </a>
                        </div>
                    </div>`;
                }).join('');
                const toNumber = (value) => {
                    const parsed = Number(value || 0);
                    return Number.isFinite(parsed) ? parsed : 0;
                };
                const parseMoneyInput = (value) => {
                    const cleaned = String(value ?? '').replace(/,/g, '').trim();
                    if (cleaned === '') return 0;
                    return toNumber(cleaned);
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
                            if (digit === 0) { if (unitIndex === 0 && position >= 6) output += unitText[6]; continue; }
                            if (unitIndex === 0 && position >= 6) output += unitText[6];
                            if (unitIndex === 1) {
                                if (digit === 1) output += unitText[1];
                                else if (digit === 2) output += `ยี่${unitText[1]}`;
                                else output += `${digitText[digit]}${unitText[1]}`;
                                continue;
                            }
                            if (unitIndex === 0 && digit === 1 && length > 1) { output += 'เอ็ด'; continue; }
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
                const setInputValue = (element, value) => {
                    if (!element) return;
                    element.value = String(value || '').trim();
                };
                const todayIsoDate = () => {
                    const now = new Date();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    return `${now.getFullYear()}-${month}-${day}`;
                };
                const setFinanceRequestDocumentDate = (isoDate) => {
                    const input = financeRequestDocumentDateInput;
                    if (!input) return;
                    const value = String(isoDate || '').trim();
                    if (!input._flatpickr && window.PBMDatePicker?.initAll) {
                        input.value = value;
                        window.PBMDatePicker.initAll();
                    }
                    if (input._flatpickr?.setDate) {
                        input._flatpickr.setDate(value !== '' ? value : null, true);
                        return;
                    }
                    input.value = value;
                };
                const pulseFinanceDocumentNumberField = () => {
                    const el = financeRequestDocumentNumberInput;
                    if (!el) return;
                    el.classList.add('ring-2', 'ring-emerald-400', 'border-emerald-400', 'shadow-md', 'shadow-emerald-100');
                    window.setTimeout(() => {
                        el.classList.remove('ring-2', 'ring-emerald-400', 'border-emerald-400', 'shadow-md', 'shadow-emerald-100');
                    }, 900);
                };
                const setFinanceDocumentNumberPreviewHint = (payload) => {
                    if (!financeDocumentNumberHint) return;
                    const timeStr = String(payload?.timeStr || '').trim();
                    const mergedBase = String(payload?.mergedBase || '').trim();
                    const nextRunningNo = Number(payload?.nextRunningNo || 0);
                    const reason = String(payload?.reason || '').trim();
                    if (mergedBase === '') {
                        const emptyMsg = reason !== '' ? reason : 'ยังไม่มีคำนำหน้าเลขที่ (ตรวจ API หน่วยงาน / withdraw_set_book_number)';
                        financeDocumentNumberHint.textContent = timeStr !== '' ? `${timeStr} — ${emptyMsg}` : '';
                        return;
                    }
                    if (nextRunningNo > 0) {
                        financeDocumentNumberHint.textContent = `${timeStr} — ระบบตั้งค่าเลขรันลำดับถัดไปเป็น ${nextRunningNo} (คำนำหน้า: ${mergedBase})`;
                        return;
                    }
                    financeDocumentNumberHint.textContent = reason !== ''
                        ? `${timeStr} — ${reason}`
                        : `${timeStr} — แสดงเฉพาะคำนำหน้า ${mergedBase} (ยังไม่ได้เลขรันจาก API)`;
                };
                const resolveDepartmentLabelFromDetailDom = () => {
                    const detailRoot = document.getElementById('withdraw-detail-status')?.closest('.space-y-3');
                    const label = String(detailRoot?.querySelector?.('#withdraw-detail-department')?.textContent || '').trim();
                    return label !== '' ? label : 'ภาควิชา/หน่วยงาน ........................';
                };
                const normalizeFinanceRequestSubject = (value = '') => String(value || '').trim().replaceAll('ขออนุมัติเบิกจ่าย', 'ขออนุมัติค่าใช้จ่าย');
                const resolveSubjectFromDetailDom = (fallback = '') => {
                    const detailRoot = document.getElementById('withdraw-detail-status')?.closest('.space-y-3');
                    const subject = String(detailRoot?.querySelector?.('#withdraw-detail-subject')?.textContent || '').trim();
                    return subject !== '' ? normalizeFinanceRequestSubject(subject) : normalizeFinanceRequestSubject(fallback);
                };
                const resolveSelectedBudgetLabel = () => {
                    const id = String(financeBudgetSourceInput?.value || '').trim();
                    const option = budgetSourceOptionMap.get(id) || null;
                    const path = String(option?.path || '').trim();
                    const code = String(option?.code || '').trim();
                    let label = path;
                    if (label === '') {
                        label = String(option?.label || '').trim();
                        if (code !== '' && label.startsWith(code)) {
                            label = label.slice(code.length).replace(/^[\s—\-–]+/, '').trim();
                        }
                    }
                    label = label.replaceAll('/', ' ').replace(/\s+/g, ' ').trim();
                    return label;
                };
                const resolveFinanceProjectName = (listRow = null) => {
                    const detail = pendingFinanceDetailRow && typeof pendingFinanceDetailRow === 'object' ? pendingFinanceDetailRow : {};
                    return String(detail.project_name || listRow?.project_name || '').trim();
                };
                const resolveCurrentTotalAmount = () => {
                    const total = toNumber(pendingFinanceReviewAmount || 0);
                    return total > 0 ? total : 0;
                };
                let financeAutoAmountPhrase = '';
                const buildFinanceAmountPhrase = (amount) => {
                    const safeAmount = toNumber(amount);
                    if (!(safeAmount > 0)) return '';
                    const formatted = safeAmount.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `${formatted} บาท (${toThaiBahtText(safeAmount)})`;
                };
                const replaceFinanceParagraph2Amount = (nextAmount) => {
                    const nextPhrase = buildFinanceAmountPhrase(nextAmount);
                    if (nextPhrase === '' || financeAutoAmountPhrase === '' || nextPhrase === financeAutoAmountPhrase) return;
                    const current = String(financeRequestParagraph2Input?.value || '');
                    if (!current.includes(financeAutoAmountPhrase)) return;
                    setInputValue(financeRequestParagraph2Input, current.replace(financeAutoAmountPhrase, nextPhrase));
                    financeAutoAmountPhrase = nextPhrase;
                };
                const updateFinanceRequestAutoText = (listRow = null) => {
                    const departmentLabel = resolveDepartmentLabelFromDetailDom();
                    const detail = pendingFinanceDetailRow && typeof pendingFinanceDetailRow === 'object' ? pendingFinanceDetailRow : {};
                    const projectName = resolveFinanceProjectName(listRow);
                    const isProject = isProjectWithdrawKind(pendingFinanceReviewKind)
                        || String(detail.withdraw_type || listRow?.withdraw_type || '').trim() === 'project';
                    const blank = '........................';
                    const activityName = String(detail.project_activity_name || '').trim() || blank;
                    const projectCode = String(detail.project_code || listRow?.project_code || '').trim() || blank;
                    const projectNameText = projectName !== '' ? projectName : blank;
                    const subject = projectName !== ''
                        ? `ขออนุมัติค่าใช้จ่ายโครงการ ${projectName}`
                        : resolveSubjectFromDetailDom(String(listRow?.subject || pendingFinanceDetailRow?.subject || ''));
                    const budgetLabel = resolveSelectedBudgetLabel();
                    const amount = resolveCurrentTotalAmount();
                    const amountText = buildFinanceAmountPhrase(amount);
                    financeAutoAmountPhrase = amountText;
                    const paragraph1Text = isProject
                        ? `ด้วย ${departmentLabel} ได้ดำเนินการ ${activityName} โครงการ (${projectCode}) ${projectNameText} เรียบร้อยแล้ว มีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้`
                        : `ด้วย ${departmentLabel} มีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้`;
                    const paragraph2Text = `จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ โดยเบิกจ่ายจาก ${budgetLabel || '........................'}${amountText !== '' ? ` จำนวน ${amountText}` : ''}`;

                    setInputValue(financeRequestSubjectInput, subject);
                    setInputValue(financeRequestToInput, 'คณบดีคณะเภสัชศาสตร์');
                    setInputValue(financeRequestParagraph1Input, paragraph1Text);
                    setInputValue(financeRequestParagraph2Input, paragraph2Text);
                    setInputValue(financeRequestParagraph3Input, 'ทั้งนี้ สรุปงบประมาณ ดังนี้');
                };
                const normalizeTypeText = (value) => String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
                const mergeDocumentNumber = (baseDocumentNumber, appendSuffix) => {
                    const base = String(baseDocumentNumber || '').trim();
                    const suffix = String(appendSuffix || '').trim();
                    if (base === '') return suffix;
                    if (suffix === '') return base;
                    if (base.endsWith(suffix)) return base;
                    return `${base}${suffix}`;
                };
                /**
                 * Prefix สำหรับเลขที่บันทึกเสนองานการเงิน + การเรียก next-running-no (withdraw_document type general_finance).
                 * ต้องใช้ finance_document_number_base จากฐานข้อมูล ไม่ใช้ document_number ของเอกสารทั่วไป — ไม่งั้นเลขรันจะไปคนละชุดกับ general_finance
                 */
                const resolveFinanceApiDocumentNumber = (detailRow = null) => {
                    const financeBase = String(detailRow?.finance_document_number_base || '').trim();
                    if (financeBase !== '') return financeBase;

                    const display = String(detailRow?.finance_document_number_display || '').trim();
                    const financeRun = Number(detailRow?.finance_document_running_no ?? 0);
                    if (display !== '' && Number.isFinite(financeRun) && financeRun > 0) {
                        const suffix = String(financeRun);
                        if (display.endsWith(suffix)) {
                            const stripped = display.slice(0, -suffix.length).trimEnd();
                            if (stripped !== '') return stripped;
                        }
                    }
                    if (display !== '') return display;

                    const fromFinanceDeptApi = String(financeDepartmentBookNumberFromApi || '').trim();
                    if (fromFinanceDeptApi !== '') return fromFinanceDeptApi;

                    return '';
                };
                const resolveFinanceMemoBookSuffix = () => {
                    const fromServer = String(financeMemoBookSuffixFallback || '').trim();
                    if (fromServer !== '') return fromServer;

                    const entries = Object.entries(bookNumberByType || {})
                        .map(([type, val]) => [String(type || '').trim(), String(val || '').trim()])
                        .filter(([type, val]) => type !== '' && val !== '');

                    const financeKeywordGroups = ['งานการเงิน', 'บันทึกเสนอ', 'การเงิน'];
                    for (const kw of financeKeywordGroups) {
                        const nk = normalizeTypeText(kw);
                        const hit = entries.find(([type]) => normalizeTypeText(type).includes(nk));
                        if (hit) return hit[1];
                    }

                    const generalNorm = normalizeTypeText('เบิกจ่ายทั่วไป');
                    const nonGeneral = entries.find(([type]) => !normalizeTypeText(type).includes(generalNorm));
                    if (nonGeneral) return nonGeneral[1];

                    const generalFallback = String(generalBookNumberSuffixFallback || '').trim();
                    if (generalFallback !== '') return generalFallback;

                    return entries.length > 0 ? entries[0][1] : '';
                };
                const resolveFinanceMemoFullBase = (detailRow = null) => {
                    const row = detailRow || pendingFinanceDetailRow;
                    return mergeDocumentNumber(resolveFinanceApiDocumentNumber(row), resolveFinanceMemoBookSuffix());
                };
                const fillFinanceRequestForm = () => {
                    setInputValue(financeRequestDocumentNumberInput, '');
                    if (financeDocumentNumberHint) {
                        financeDocumentNumberHint.textContent = '';
                    }
                    if (financeRequestDocumentNumberInput) {
                        financeRequestDocumentNumberInput.dataset.baseNumber = '';
                    }
                    setInputValue(financeRequestDocumentDateInput, '');
                    setInputValue(financeRequestSubjectInput, '');
                    setInputValue(financeRequestToInput, '');
                    setInputValue(financeRequestParagraph1Input, '');
                    setInputValue(financeRequestParagraph2Input, '');
                    setInputValue(financeRequestParagraph3Input, '');
                    if (financeExpenseMappingBody) {
                        financeExpenseMappingBody.innerHTML = '<tr><td colspan="5" class="border border-slate-200 px-3 py-3 text-center text-slate-400">- ยังไม่มีรายการเบิกจ่าย -</td></tr>';
                        refreshFinanceMappingRowState();
                    }
                    resetFinanceEsignUpload();
                };
                const refreshFinanceDocumentNumberPreview = async (documentDate = '', detailRow = null) => {
                    if (!financeRequestDocumentNumberInput) return;

                    const timeStr = new Date().toLocaleTimeString('th-TH', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                    });

                    let effectiveRow = detailRow || pendingFinanceDetailRow;
                    const recordId = Number(pendingFinanceReviewId || 0);
                    if (recordId > 0) {
                        try {
                            const detailRes = await fetch(`${detailApiBaseForKind(pendingFinanceReviewKind)}/${recordId}`, {
                                headers: { Accept: 'application/json' },
                                cache: 'no-store',
                            });
                            const detailBody = await detailRes.json().catch(() => null);
                            if (detailBody?.status === 'success' && detailBody?.data && typeof detailBody.data === 'object') {
                                effectiveRow = detailBody.data;
                                pendingFinanceDetailRow = effectiveRow;
                            }
                        } catch (error) {
                            /* keep effectiveRow from argument / pendingFinanceDetailRow */
                        }
                    }

                    const mergedBase = resolveFinanceMemoFullBase(effectiveRow);
                    if (mergedBase === '') {
                        financeRequestDocumentNumberInput.dataset.baseNumber = '';
                        setInputValue(financeRequestDocumentNumberInput, '');
                        setFinanceDocumentNumberPreviewHint({
                            timeStr,
                            mergedBase: '',
                            nextRunningNo: 0,
                            reason: 'ยังไม่มีคำนำหน้าเลขที่สำหรับการเงิน',
                        });
                        return;
                    }

                    financeRequestDocumentNumberInput.dataset.baseNumber = mergedBase;

                    const dateForApi = String(
                        documentDate
                        || String(effectiveRow?.finance_document_date || '').trim()
                        || String(effectiveRow?.document_date || '').trim()
                        || String(financeRequestDocumentDateInput?.value || '').trim()
                    ).trim();

                    let nextRunningNo = 0;
                    let apiReason = '';
                    let runningJson = null;
                    try {
                        const runningNoApiUrl = new URL(nextRunningNoApiForKind(pendingFinanceReviewKind), window.location.origin);
                        runningNoApiUrl.searchParams.set('withdraw_type', 'general_finance');
                        runningNoApiUrl.searchParams.set('department_code', String(financeDocumentCode || '12010401').trim() || '12010401');
                        if (dateForApi !== '') {
                            runningNoApiUrl.searchParams.set('document_date', dateForApi);
                        }
                        if (mergedBase !== '') {
                            runningNoApiUrl.searchParams.set('document_number', mergedBase);
                        }
                        runningNoApiUrl.searchParams.set('_', String(Date.now()));
                        const runningResponse = await fetch(runningNoApiUrl.toString(), { headers: { Accept: 'application/json' }, cache: 'no-store' });
                        runningJson = await runningResponse.json().catch(() => null);
                        if (runningJson?.status !== 'success') {
                            apiReason = String(runningJson?.message || `HTTP ${runningResponse.status} — ไม่สามารถดึงเลขรันได้`);
                        } else {
                            const rawNext = Number(runningJson?.data?.running_no ?? 0);
                            nextRunningNo = Number.isFinite(rawNext) && rawNext > 0 ? rawNext : 0;
                            if (nextRunningNo <= 0) {
                                apiReason = String(runningJson?.message || 'API ตอบสำเร็จแต่ไม่มีเลขรัน');
                            }
                        }
                    } catch (error) {
                        nextRunningNo = 0;
                        apiReason = String(error?.message || 'เรียก API เลขรันล้มเหลว');
                    }

                    const centralFullNumber = String(runningJson?.data?.full_number || '').trim();
                    const nextFull = centralFullNumber !== ''
                        ? centralFullNumber
                        : (nextRunningNo > 0 ? `${mergedBase}${nextRunningNo}` : mergedBase);
                    const docEl = financeRequestDocumentNumberInput;
                    docEl.value = '';
                    void docEl.offsetWidth;
                    docEl.value = nextFull;
                    docEl.dispatchEvent(new Event('input', { bubbles: true }));
                    docEl.dispatchEvent(new Event('change', { bubbles: true }));
                    pulseFinanceDocumentNumberField();
                    setFinanceDocumentNumberPreviewHint({
                        timeStr,
                        mergedBase,
                        nextRunningNo,
                        reason: apiReason,
                    });
                };
                const applySavedFinanceMemoValues = (detailRow = null) => {
                    if (!detailRow || typeof detailRow !== 'object') return;
                    const savedParagraph1 = String(detailRow?.finance_paragraph1 || '').trim();
                    const savedParagraph3 = String(detailRow?.finance_paragraph3 || '').trim();
                    const isProject = isProjectWithdrawKind(pendingFinanceReviewKind)
                        || String(detailRow?.withdraw_type || '').trim() === 'project';
                    const isLegacyParagraph1 = /^ด้วย\s+.+\s+มีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้$/u.test(savedParagraph1);

                    // วันที่ เรื่อง เรียน และย่อหน้า 2 ใช้ค่าเริ่มต้นของบันทึกเสนอ ไม่ดึงค่าที่บันทึกไว้มาทับ
                    if (savedParagraph1 !== '' && !(isProject && isLegacyParagraph1)) {
                        setInputValue(financeRequestParagraph1Input, savedParagraph1);
                    }
                    if (savedParagraph3 !== '') setInputValue(financeRequestParagraph3Input, savedParagraph3);
                };
                const renderFinanceProcurementList = async (withdrawId) => {
                    if (!financeProcurementCard || !financeProcurementBody) return;
                    if (Number(withdrawId || 0) <= 0) {
                        financeProcurementCard.classList.add('hidden');
                        return;
                    }
                    financeProcurementBody.innerHTML = '';
                    financeProcurementEmpty?.classList.add('hidden');
                    financeProcurementWrap?.classList.remove('hidden');
                    financeProcurementCard.classList.remove('hidden');
                    try {
                        const procurementUrl = new URL(procurementListApi, window.location.origin);
                        procurementUrl.searchParams.set('withdraw_id', String(withdrawId));
                        const response = await fetch(procurementUrl.toString(), { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        const rows = Array.isArray(result?.data) ? result.data : [];
                        if (rows.length === 0) {
                            financeProcurementEmpty?.classList.remove('hidden');
                            financeProcurementWrap?.classList.add('hidden');
                            return;
                        }
                        financeProcurementBody.innerHTML = renderFinanceProcurementPreviewHtml(rows);
                        stripPdfIframesForCompactView(financeProcurementCard);
                    } catch (error) {
                        financeProcurementEmpty?.classList.remove('hidden');
                        financeProcurementEmpty.textContent = 'ไม่สามารถโหลดเอกสารจัดซื้อจัดจ้างได้';
                        financeProcurementWrap?.classList.add('hidden');
                    }
                };
                const resetFinanceAttachedDocsCard = () => {
                    if (financeAttachedDocsBody) financeAttachedDocsBody.innerHTML = '';
                    financeAttachedDocsEmpty?.classList.add('hidden');
                    financeAttachedDocsWrap?.classList.remove('hidden');
                    if (financeAttachedDocsEmpty) {
                        financeAttachedDocsEmpty.textContent = 'ยังไม่มีเอกสารการเงินของรายการนี้';
                    }
                    financeAttachedDocsCard?.classList.add('hidden');
                };
                const renderFinanceAttachedDocsHtml = (files = []) => files.map((file, index) => {
                    const title = String(file.name || `เอกสารการเงิน ${index + 1}`).trim() || `เอกสารการเงิน ${index + 1}`;
                    const url = String(file.url || '').trim();
                    const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>
                            <div class="mt-0.5 text-xs text-slate-500">เอกสาร e-Sign จากงานการเงิน</div>
                        </div>
                        ${url !== '' ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 shadow-sm transition hover:bg-violet-100">เปิดไฟล์</a>` : ''}
                    </div>`;
                    if (url === '') {
                        return `<div class="rounded-xl border border-dashed border-violet-200 bg-white px-3 py-3 text-sm text-slate-500">${caption}<div>ยังไม่มีไฟล์</div></div>`;
                    }
                    if (canEmbedPdfPreviewInFinance()) {
                        return `<div class="overflow-hidden rounded-xl border border-violet-100 bg-white p-2">${caption}<iframe src="${escapeHtml(url)}" title="${escapeHtml(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`;
                    }
                    return `<div class="overflow-hidden rounded-xl border border-violet-100 bg-white p-3">
                        ${caption}
                        <div class="flex flex-col gap-3 rounded-lg border border-dashed border-violet-200 bg-violet-50/40 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700 ring-1 ring-violet-200"><i class="fas fa-file-pdf"></i></span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>
                                    <div class="text-xs text-slate-500">เปิดไฟล์เพื่อดูบนอุปกรณ์นี้</div>
                                </div>
                            </div>
                            <a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700 hover:bg-violet-100">
                                เปิด PDF
                            </a>
                        </div>
                    </div>`;
                }).join('');
                const renderFinanceAttachedDocsList = (files = []) => {
                    if (!financeAttachedDocsCard || !financeAttachedDocsBody) return;
                    const rows = Array.isArray(files) ? files.filter((file) => String(file?.url || '').trim() !== '' || String(file?.name || '').trim() !== '') : [];
                    financeAttachedDocsBody.innerHTML = '';
                    if (rows.length === 0) {
                        resetFinanceAttachedDocsCard();
                        return;
                    }
                    financeAttachedDocsEmpty?.classList.add('hidden');
                    financeAttachedDocsWrap?.classList.remove('hidden');
                    financeAttachedDocsCard.classList.remove('hidden');
                    financeAttachedDocsBody.innerHTML = renderFinanceAttachedDocsHtml(rows);
                    stripPdfIframesForCompactView(financeAttachedDocsCard);
                };
                const placeFinanceApprovalRoute = (withdrawKind = 'general') => {
                    if (!financeApprovalRouteSlot) return;
                    financeApprovalRouteSlot.innerHTML = '';
                    const selector = normalizeWithdrawKind(withdrawKind) === 'salary'
                        ? '#withdraw-salary-detail-approval-route-section'
                        : '#withdraw-detail-approval-route-section';
                    const source = document.querySelector(selector);
                    if (!source || String(source.textContent || '').trim() === '') {
                        financeApprovalRouteSlot.classList.add('hidden');
                        return;
                    }
                    const copy = source.cloneNode(true);
                    copy.classList.remove('hidden', 'mt-3', 'pt-3');
                    copy.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
                    financeApprovalRouteSlot.appendChild(copy);
                    financeApprovalRouteSlot.classList.remove('hidden');
                };
                if (financeApprovalRouteSlot && financeApprovalRouteSlot.dataset.bound !== '1') {
                    financeApprovalRouteSlot.dataset.bound = '1';
                    financeApprovalRouteSlot.addEventListener('click', async (event) => {
                        const copyButton = event.target.closest('[data-general-copy-approval-link], [data-salary-copy-approval-link]');
                        if (!copyButton || !financeApprovalRouteSlot.contains(copyButton)) return;
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
                const toggleFinanceReviewLayout = (withdrawKind = 'general') => {
                    const isProject = isProjectWithdrawKind(withdrawKind);
                    financeMemoSection?.classList.remove('hidden');
                    financeProcurementCard?.classList.toggle('hidden', !isProject);
                    if (!isProject) {
                        if (financeProcurementBody) {
                            financeProcurementBody.innerHTML = '';
                        }
                        financeProcurementEmpty?.classList.add('hidden');
                        financeProcurementWrap?.classList.remove('hidden');
                        if (financeProcurementEmpty) {
                            financeProcurementEmpty.textContent = 'ยังไม่มีเอกสารจัดซื้อจัดจ้างของรายการนี้';
                        }
                    }
                };

                const latestRevisionExplanation = (detailRow) => {
                    const logs = Array.isArray(detailRow?.action_logs) ? detailRow.action_logs : [];
                    const matches = logs.filter((log) => String(log?.action_code || '').trim() === 'submit_revision_update');
                    const latest = matches.length > 0 ? matches[matches.length - 1] : null;
                    return String(latest?.comment || '').trim();
                };
                const renderFinanceRevisionExplanation = (detailRow) => {
                    const explanation = latestRevisionExplanation(detailRow);
                    if (!financeRevisionExplanationCard || !financeRevisionExplanationEl) return;
                    if (explanation === '') {
                        financeRevisionExplanationEl.textContent = '-';
                        financeRevisionExplanationCard.classList.add('hidden');
                        return;
                    }
                    financeRevisionExplanationEl.textContent = explanation;
                    financeRevisionExplanationCard.classList.remove('hidden');
                };
                const renderFinanceRevisionInfo = (listRow, detailRow) => {
                    renderFinanceRevisionExplanation(detailRow);
                    if (!financeRevisionInfoCard || !financeRevisionFromEl || !financeRevisionCommentEl) return;
                    const rawStatus = String(listRow?.raw_status || '').trim();
                    const statusLabel = String(listRow?.status || '').trim();
                    const isRevisionCase = rawStatus === 'pending_finance_review' && statusLabel.includes('(แก้ไข)');
                    if (!isRevisionCase) {
                        financeRevisionInfoCard.classList.add('hidden');
                        financeRevisionFromEl.textContent = '-';
                        financeRevisionCommentEl.textContent = '-';
                        return;
                    }
                    const approvalSteps = Array.isArray(detailRow?.approval_steps) ? detailRow.approval_steps : [];
                    const rejectedStep = approvalSteps
                        .filter((step) => String(step?.status || '').trim().toLowerCase() === 'rejected')
                        .slice(-1)[0] || null;
                    const senderName = String(rejectedStep?.staff_name || '').trim();
                    const senderId = String(rejectedStep?.staff_id || '').trim();
                    const senderStep = String(rejectedStep?.step_name || '').trim();
                    const senderText = senderName !== ''
                        ? senderName
                        : (senderId !== '' ? senderId : (senderStep !== '' ? senderStep : '-'));
                    const revisionComment = String(listRow?.revision_comment || rejectedStep?.comment || '').trim();
                    financeRevisionFromEl.textContent = senderText !== '' ? senderText : '-';
                    financeRevisionCommentEl.textContent = revisionComment !== '' ? revisionComment : '-';
                    financeRevisionInfoCard.classList.remove('hidden');
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

                const syncPendingBadge = (rows) => {
                    const badge = document.getElementById('document-review-pending-count-badge');
                    if (!badge) return;
                    const safeRows = Array.isArray(rows) ? rows : [];
                    const pendingCount = safeRows.filter((row) => String(row?.user_step_status || '').trim() === 'pending').length;
                    badge.textContent = String(pendingCount);
                    badge.dataset.count = String(pendingCount);
                    badge.classList.toggle('hidden', pendingCount <= 0);
                };
                const refreshApprovalPendingBadge = async () => {
                    if (typeof window.refreshWithdrawApprovalPendingBadge === 'function') {
                        await window.refreshWithdrawApprovalPendingBadge();
                        return;
                    }
                    const badge = document.getElementById('approval-pending-count-badge');
                    if (!badge) return;
                    try {
                        const response = await fetch(`${approvalPendingListApi}&_=${Date.now()}`, {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                        });
                        const result = await response.json();
                        const rows = Array.isArray(result?.data) ? result.data : [];
                        const pendingCount = rows.filter((row) => {
                            const userStep = String(row?.user_step_status || '').trim();
                            if (userStep !== 'pending') return false;
                            const rawStatus = String(row?.raw_status || '').trim();
                            const statusLabel = String(row?.status || '').trim();
                            if (rawStatus === 'pending_finance_review') return false;
                            if (statusLabel.includes('รอการเงินตรวจสอบเอกสาร')) return false;
                            return true;
                        }).length;
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
                        const rawStatus = String(row.status || '-');
                        const status = rawStatus === 'รออนุมัติ'
                            ? 'รอตรวจสอบ'
                            : (rawStatus === 'อนุมัติ' ? 'ตรวจสอบ' : rawStatus);
                        const statusClass = statusClassMap[rawStatus] || 'bg-slate-100 text-slate-600 ring-slate-200';
                        const id = Number(row.id || 0);
                        const withdrawKind = normalizeWithdrawKind(row.withdraw_kind);
                        const userStepStatus = String(row.user_step_status || '').trim().toLowerCase();
                        const canApprove = userStepStatus === 'pending';
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
                                ${canApprove ? `<button type="button" data-action="approve" data-id="${id}" data-withdraw-kind="${withdrawKind}" class="group inline-flex items-center gap-1.5 rounded-xl border border-emerald-600/20 bg-gradient-to-r from-emerald-500 to-green-600 px-4 py-2 text-[11px] font-bold text-white shadow-md shadow-emerald-200 transition-all duration-200 hover:-translate-y-0.5 hover:from-emerald-600 hover:to-green-700 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
                                    <i class="fas fa-file-pen text-[11px] transition-transform duration-200 group-hover:scale-110"></i>ตรวจสอบเอกสาร
                                </button>` : ''}
                            </div>
                        </article>`;
                    }).join('');
                };

                const syncTabUi = () => {
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

                const loadExpenseChartOptions = async (withdrawKind = pendingFinanceReviewKind) => {
                    const apiUrl = expenseChartApiForKind(withdrawKind);
                    if (expenseChartOptions.length > 0 && expenseChartOptions.__apiUrl === apiUrl) return expenseChartOptions;
                    try {
                        const response = await fetch(apiUrl, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        const rows = Array.isArray(result?.data?.expense_charts) ? result.data.expense_charts : [];
                        expenseChartOptions = rows.map((row) => ({
                            id: Number(row?.id || 0),
                            payCode: String(row?.pay_code || '').trim(),
                            expenseCode: String(row?.expense_code || '').trim(),
                            name: String(row?.name || '').trim(),
                            detail: String(row?.detail || '').trim(),
                        })).filter((row) => row.id > 0 && (row.name !== '' || row.detail !== ''));
                        expenseChartOptions.__apiUrl = apiUrl;
                    } catch (error) {
                        expenseChartOptions = [];
                    }
                    return expenseChartOptions;
                };
                const loadBudgetSourceOptions = async (forceReload = false, withdrawKind = pendingFinanceReviewKind) => {
                    const apiUrl = budgetSourceApiForKind(withdrawKind);
                    if (!forceReload && budgetSourceOptions.length > 0 && budgetSourceOptions.__apiUrl === apiUrl) return budgetSourceOptions;
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
                            const year = String(row?.year ?? '').trim();
                            const path = String(row?.source_path ?? '').trim();
                            const amount = toNumber(row?.amount || 0);
                            const balance = toNumber(row?.balance || 0);
                            const approvedAmount = toNumber(row?.approved_amount || 0);
                            let label = '';
                            if (code !== '' && path !== '') {
                                label = `${code} — ${path}`;
                            } else {
                                label = code !== '' ? code : path;
                            }
                            const option = {
                                id,
                                label,
                                path,
                                code,
                                year,
                                amount,
                                balance,
                                approvedAmount,
                                categoryPayCode: String(row?.category_pay_code ?? '').trim(),
                                categoryPayName: String(row?.category_pay_name ?? '').trim(),
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
                const renderBudgetSummary = () => {
                    if (!financeBudgetSummaryCard || !financeBudgetSummaryBody) return;
                    const selectedBudgetId = String(financeBudgetSourceInput?.value || '').trim();
                    const selected = selectedBudgetId !== '' ? budgetSourceOptionMap.get(selectedBudgetId) : null;
                    if (!selected) {
                        financeBudgetSummaryCard.classList.add('hidden');
                        if (financeBudgetSummaryTitle) {
                            financeBudgetSummaryTitle.textContent = '';
                        }
                        financeBudgetSummaryBody.innerHTML = '<tr><td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="5">- ยังไม่ได้เลือกงบประมาณ -</td></tr>';
                        return;
                    }
                    if (financeBudgetSummaryTitle) {
                        financeBudgetSummaryTitle.textContent = `(${String(selected.label || '-')})`;
                    }
                    const approvedAlready = toNumber(selected.approvedAmount);
                    const approvedThisTime = toNumber(pendingFinanceReviewAmount);
                    const totalApproved = approvedAlready + approvedThisTime;
                    const remaining = toNumber(selected.amount) - totalApproved;
                    financeBudgetSummaryBody.innerHTML = `<tr>
                        <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(toNumber(selected.amount))}</td>
                        <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(approvedAlready)}</td>
                        <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(approvedThisTime)}</td>
                        <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(totalApproved)}</td>
                        <td class="border border-slate-300 px-2 py-1 text-right ${remaining < 0 ? 'text-rose-600 font-semibold' : ''}">${formatMoney(remaining)}</td>
                    </tr>`;
                    financeBudgetSummaryCard.classList.remove('hidden');
                };

                const payCodesByCategoryPayCode = new Map();
                let payListLoadedApi = '';
                const normalizePayCodeKey = (value) => {
                    const text = String(value ?? '').trim();
                    if (text === '') return '';
                    const numeric = Number(text);
                    if (Number.isFinite(numeric) && String(numeric) === String(Number(text))) {
                        return String(numeric);
                    }
                    return text;
                };
                const loadPayListOptions = async (withdrawKind = pendingFinanceReviewKind) => {
                    const apiUrl = payListApiForKind(withdrawKind);
                    if (payListLoadedApi === apiUrl) return payCodesByCategoryPayCode;
                    payCodesByCategoryPayCode.clear();
                    try {
                        const response = await fetch(apiUrl, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        const rows = Array.isArray(result?.data?.pay_lists) ? result.data.pay_lists : [];
                        rows.forEach((row) => {
                            const payCode = normalizePayCodeKey(row?.budget_pay_code ?? row?.code ?? '');
                            const categoryPayCode = normalizePayCodeKey(row?.budget_category_pay_code ?? row?.category_pay_code ?? '');
                            if (payCode === '' || categoryPayCode === '') return;
                            if (!payCodesByCategoryPayCode.has(categoryPayCode)) {
                                payCodesByCategoryPayCode.set(categoryPayCode, new Set());
                            }
                            payCodesByCategoryPayCode.get(categoryPayCode).add(payCode);
                        });
                        payListLoadedApi = apiUrl;
                    } catch (error) {
                        payListLoadedApi = '';
                    }
                    return payCodesByCategoryPayCode;
                };
                const resolveExpenseOptionsForSelectedBudget = () => {
                    const selectedBudgetId = String(financeBudgetSourceInput?.value || '').trim();
                    const selected = selectedBudgetId !== '' ? budgetSourceOptionMap.get(selectedBudgetId) : null;
                    const categoryPayCode = normalizePayCodeKey(selected?.categoryPayCode || '');
                    const categoryPayName = String(selected?.categoryPayName || '').trim();
                    if (!selected) {
                        return { options: [], status: 'เลือกงบประมาณก่อน' };
                    }
                    if (categoryPayCode === '') {
                        return { options: [], status: 'ไม่พบหมวดของงบประมาณนี้' };
                    }
                    const payCodes = payCodesByCategoryPayCode.get(categoryPayCode) || new Set();
                    const options = expenseChartOptions.filter((option) => payCodes.has(normalizePayCodeKey(option?.payCode || '')));
                    const categoryLabel = categoryPayName !== '' ? `"${categoryPayName}"` : 'งบประมาณนี้';
                    return {
                        options,
                        status: options.length === 0 ? `ไม่พบผังบัญชีของหมวด ${categoryLabel}` : '',
                    };
                };

                const createExpenseChartDropdown = (recordId, rowIndex, itemId, parentCategory = '', parentCategoryCode = '') => {
                    if (!expenseDropdownTemplate) return null;
                    const sourceWrapper = expenseDropdownTemplate.querySelector('[data-pbm-searchable-select-wrapper]');
                    if (!sourceWrapper) return null;

                    const wrapper = sourceWrapper.cloneNode(true);
                    // Important: cloned node must be re-initialized by PBMSearchableDropdown.
                    wrapper.removeAttribute('data-pbm-inited');
                    delete wrapper.dataset.pbmInited;
                    wrapper.className = 'relative isolate z-[1] overflow-visible focus-within:z-[2147483646]';
                    const optionsKey = `finance-expense-map-${recordId}-${rowIndex}`;
                    const hiddenId = `finance-expense-map-${recordId}-${rowIndex}-hidden`;
                    const selectionKey = `${recordId}:${itemId > 0 ? itemId : rowIndex}`;
                    const resolved = resolveExpenseOptionsForSelectedBudget();
                    const optionsForDropdown = resolved.options.map((option) => ({
                        id: String(option.id),
                        label: option.name !== ''
                            ? (option.detail !== '' ? `${option.name} (${option.detail})` : option.name)
                            : option.detail,
                    }));
                    const allowedIds = new Set(optionsForDropdown.map((option) => String(option.id)));
                    let selectedValue = String(expenseChartSelectionMap.get(selectionKey) || '').trim();
                    if (selectedValue !== '' && !allowedIds.has(selectedValue)) {
                        expenseChartSelectionMap.delete(selectionKey);
                        selectedValue = '';
                    }

                    wrapper.dataset.pbmOptionsKey = optionsKey;

                    const scriptEl = wrapper.querySelector('script.pbm-searchable-select__options');
                    if (scriptEl) {
                        scriptEl.dataset.optionsKey = optionsKey;
                        scriptEl.textContent = JSON.stringify(optionsForDropdown);
                    }

                    const hiddenEl = wrapper.querySelector('[data-pbm-searchable-select-hidden]');
                    const textEl = wrapper.querySelector('[data-pbm-searchable-select-text]');
                    let suggestEl = wrapper.querySelector('[data-pbm-searchable-select-suggest]');
                    const clearBtn = wrapper.querySelector('button[id$="-clear"]');
                    if (!suggestEl) {
                        suggestEl = document.createElement('div');
                        suggestEl.setAttribute('data-pbm-searchable-select-suggest', '1');
                        wrapper.appendChild(suggestEl);
                    }

                    if (hiddenEl) {
                        hiddenEl.id = hiddenId;
                        hiddenEl.value = selectedValue;
                        hiddenEl.dataset.recordId = String(recordId);
                        hiddenEl.dataset.rowIndex = String(rowIndex);
                        hiddenEl.dataset.itemId = String(itemId || 0);
                        hiddenEl.dataset.expenseMapSelect = '1';
                    }
                    if (textEl) {
                        textEl.id = `${hiddenId}-text`;
                        textEl.className = 'h-9 w-full rounded-md border border-slate-300 bg-white px-3 pr-9 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100';
                    }
                    if (suggestEl) {
                        suggestEl.id = `${hiddenId}-suggest`;
                        suggestEl.className = 'hidden max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white py-1 shadow-2xl';
                    }
                    if (clearBtn) {
                        clearBtn.id = `${hiddenId}-clear`;
                    }

                    const holder = document.createElement('div');
                    holder.className = 'overflow-visible';
                    holder.setAttribute('data-expense-map-wrapper', '1');
                    holder.appendChild(wrapper);

                    if (window.PBMSearchableDropdown?.setOptions) {
                        window.PBMSearchableDropdown.setOptions(optionsKey, optionsForDropdown);
                    }
                    if (window.PBMSearchableDropdown?.setStatus) {
                        window.PBMSearchableDropdown.setStatus(optionsKey, resolved.status || '');
                    }

                    return holder;
                };

                const financeExpenseMapProgress = document.getElementById('finance-expense-map-progress');
                const financeExpenseResetAmountsButton = document.getElementById('finance-expense-reset-amounts');
                const expenseMapItemRows = () => Array.from(
                    financeExpenseMappingBody?.querySelectorAll('[data-withdraw-item-id]') || []
                );
                const refreshFinanceMappingRowState = () => {
                    const rows = expenseMapItemRows();
                    let done = 0;
                    rows.forEach((row) => {
                        const chartId = Number(row.querySelector('[data-expense-map-select="1"]')?.value || 0);
                        const amount = parseMoneyInput(row.querySelector('[data-expense-map-amount="1"]')?.value);
                        if (chartId > 0 && amount > 0) done += 1;
                    });
                    if (financeExpenseMapProgress) {
                        financeExpenseMapProgress.textContent = rows.length === 0
                            ? 'จำนวนเงินเดิมแสดงไว้ให้ดู คอลัมน์ถัดไปกรอกจำนวนของงานการเงิน'
                            : `จำนวนเงินเดิมแสดงไว้ให้ดู · กรอกครบ ${done}/${rows.length} รายการ`;
                    }
                };
                const syncFinanceExpenseAmountTotals = () => {
                    const totals = new Map();
                    expenseMapItemRows().forEach((row) => {
                        const groupKey = String(row.dataset.expenseGroupKey || '');
                        const amount = parseMoneyInput(row.querySelector('[data-expense-map-amount="1"]')?.value);
                        totals.set(groupKey, (totals.get(groupKey) || 0) + amount);
                    });
                    financeExpenseMappingBody?.querySelectorAll('[data-expense-finance-total="1"]').forEach((cell) => {
                        const groupKey = String(cell.closest('[data-expense-group-key]')?.dataset?.expenseGroupKey || '');
                        cell.textContent = formatMoney(totals.get(groupKey) || 0);
                    });
                    let total = 0;
                    totals.forEach((amount) => {
                        total += toNumber(amount);
                    });
                    pendingFinanceReviewAmount = total;
                    renderBudgetSummary();
                    replaceFinanceParagraph2Amount(total);
                    refreshFinanceMappingRowState();
                };
                const applyChartToMappingRow = (row, chartId, chartLabel) => {
                    const hidden = row.querySelector('[data-expense-map-select="1"]');
                    const text = row.querySelector('[data-pbm-searchable-select-text]');
                    const clearBtn = row.querySelector('button[id$="-clear"]');
                    if (!(hidden instanceof HTMLInputElement)) return;
                    hidden.value = String(chartId);
                    if (text instanceof HTMLInputElement) text.value = chartLabel;
                    clearBtn?.classList.remove('hidden');
                    hidden.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const renderFinanceExpenseMappingTable = (recordId, detailRow) => {
                    if (!financeExpenseMappingBody) return;
                    const items = Array.isArray(detailRow?.items) ? detailRow.items : [];
                    if (items.length === 0) {
                        financeExpenseMappingBody.innerHTML = '<tr><td colspan="5" class="border border-slate-200 px-3 py-3 text-center text-slate-400">- ยังไม่มีรายการเบิกจ่าย -</td></tr>';
                        refreshFinanceMappingRowState();
                        return;
                    }

                    const typedFinanceAmounts = new Map();
                    expenseMapItemRows().forEach((row) => {
                        const itemId = String(row.dataset.withdrawItemId || '').trim();
                        const raw = row.querySelector('[data-expense-map-amount="1"]')?.value;
                        if (itemId !== '' && String(raw ?? '').trim() !== '') {
                            typedFinanceAmounts.set(itemId, parseMoneyInput(raw));
                        }
                    });

                    const groupedItems = [];
                    const groupedMap = new Map();
                    items.forEach((item) => {
                        const category = String(item?.category || '-').trim() || '-';
                        const categoryCode = String(item?.category_code || '').trim();
                        const name = String(item?.name || '-').trim() || '-';
                        const amount = toNumber(item?.amount || 0);
                        const itemId = Number(item?.id || 0);
                        const typedAmount = typedFinanceAmounts.has(String(itemId))
                            ? typedFinanceAmounts.get(String(itemId))
                            : null;
                        const savedFinanceAmount = toNumber(item?.finance_amount);
                        const groupKey = `${categoryCode}::${category}`;
                        if (!groupedMap.has(groupKey)) {
                            const group = { category, categoryCode, items: [], total: 0 };
                            groupedMap.set(groupKey, group);
                            groupedItems.push(group);
                        }
                        const group = groupedMap.get(groupKey);
                        group.items.push({
                            id: itemId,
                            name,
                            amount,
                            financeAmount: typedAmount !== null ? typedAmount : (savedFinanceAmount > 0 ? savedFinanceAmount : amount),
                            expenseChartId: String(item?.expense_chart_id || '').trim(),
                        });
                        group.total += amount;
                    });

                    let flatRowIndex = 0;
                    financeExpenseMappingBody.innerHTML = groupedItems.map((group, groupIndex) => {
                        const groupKey = `group-${groupIndex}`;
                        const financeTotal = group.items.reduce((sum, item) => sum + toNumber(item.financeAmount), 0);
                        const categoryRow = `<tr class="bg-slate-50/80 font-semibold text-slate-700" data-expense-group-key="${groupKey}" data-main-category-code="${escapeHtml(group.categoryCode || '')}">
                            <td class="border border-slate-200 px-2 py-2 text-center align-middle">${groupIndex + 1}</td>
                            <td class="border border-slate-200 px-3 py-2 align-middle">${escapeHtml(group.category)}</td>
                            <td class="border border-slate-200 px-2 py-2 text-right align-middle tabular-nums whitespace-nowrap">${formatMoney(group.total)}</td>
                            <td class="border border-slate-200 px-2 py-2 text-right align-middle tabular-nums whitespace-nowrap" data-expense-finance-total="1">${formatMoney(financeTotal)}</td>
                            <td class="border border-slate-200 px-3 py-2 align-middle text-slate-400">-</td>
                        </tr>`;
                        const itemRows = group.items.map((item) => {
                            const rowIndex = flatRowIndex;
                            flatRowIndex += 1;
                            const selectionKey = `${recordId}:${item.id > 0 ? item.id : rowIndex}`;
                            if (item.expenseChartId !== '' && !expenseChartSelectionMap.has(selectionKey)) {
                                expenseChartSelectionMap.set(selectionKey, item.expenseChartId);
                            }
                            const amountValue = formatMoney(item.financeAmount);
                            return `<tr data-expense-group-key="${groupKey}" data-withdraw-item-id="${item.id}" data-item-name="${escapeHtml(item.name)}" data-original-amount="${toNumber(item.amount)}" data-parent-category="${escapeHtml(group.category)}" data-parent-category-code="${escapeHtml(group.categoryCode || '')}" data-expense-chart-id="${escapeHtml(item.expenseChartId)}">
                                <td class="border border-slate-200 px-2 py-2 text-center align-middle text-slate-400"></td>
                                <td class="border border-slate-200 px-3 py-2 align-middle text-slate-700">- ${escapeHtml(item.name)}</td>
                                <td class="border border-slate-200 px-2 py-2 text-right align-middle tabular-nums whitespace-nowrap text-slate-700">${formatMoney(item.amount)}</td>
                                <td class="border border-slate-200 px-1.5 py-1.5 text-right align-middle">
                                    <input type="text" inputmode="decimal" autocomplete="off" data-expense-map-amount="1" value="${amountValue}"
                                        class="h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-right text-sm font-semibold tabular-nums text-slate-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                                </td>
                                <td class="relative overflow-visible border border-slate-200 px-2 py-1.5 align-middle"><div class="overflow-visible" data-expense-dropdown-slot="1"></div></td>
                            </tr>`;
                        }).join('');
                        return `${categoryRow}${itemRows}`;
                    }).join('');

                    Array.from(financeExpenseMappingBody.querySelectorAll('[data-withdraw-item-id]')).forEach((row, index) => {
                        const itemId = Number(row.dataset.withdrawItemId || 0);
                        const parentCategory = String(row.dataset.parentCategory || '').trim();
                        const parentCategoryCode = String(row.dataset.parentCategoryCode || '').trim();
                        const slot = row.querySelector('[data-expense-dropdown-slot="1"]');
                        const dropdownNode = createExpenseChartDropdown(recordId, index, itemId, parentCategory, parentCategoryCode);
                        if (!slot || !dropdownNode) return;
                        slot.innerHTML = '';
                        slot.appendChild(dropdownNode);
                    });

                    financeExpenseMappingBody.querySelectorAll('[data-expense-map-select="1"]').forEach((hiddenEl) => {
                        hiddenEl.addEventListener('change', (event) => {
                            const target = event.currentTarget;
                            if (!(target instanceof HTMLInputElement)) return;
                            const record = String(target.dataset.recordId || '');
                            const rowIdx = String(target.dataset.rowIndex || '');
                            const itemId = String(target.dataset.itemId || '').trim();
                            const key = `${record}:${itemId !== '' && Number(itemId) > 0 ? itemId : rowIdx}`;
                            const value = String(target.value || '').trim();
                            if (value === '') {
                                expenseChartSelectionMap.delete(key);
                            } else {
                                expenseChartSelectionMap.set(key, value);
                            }
                            refreshFinanceMappingRowState();
                        });
                    });

                    financeExpenseMappingBody.querySelectorAll('[data-expense-map-amount="1"]').forEach((amountInput) => {
                        amountInput.addEventListener('focus', () => {
                            const amount = parseMoneyInput(amountInput.value);
                            amountInput.value = amount > 0 ? String(amount) : '';
                            amountInput.select?.();
                        });
                        amountInput.addEventListener('input', syncFinanceExpenseAmountTotals);
                        amountInput.addEventListener('blur', () => {
                            amountInput.value = formatMoney(parseMoneyInput(amountInput.value));
                            syncFinanceExpenseAmountTotals();
                        });
                        amountInput.addEventListener('keydown', (event) => {
                            if (event.key !== 'Enter') return;
                            event.preventDefault();
                            amountInput.closest('[data-withdraw-item-id]')
                                ?.querySelector('[data-pbm-searchable-select-text]')
                                ?.focus();
                        });
                    });
                    syncFinanceExpenseAmountTotals();

                    if (window.PBMSearchableDropdown?.initAll) {
                        window.PBMSearchableDropdown.initAll();
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
                const postFinanceReviewPayload = async (url, payload, esignEntries = []) => {
                    const entries = Array.from(esignEntries || []).filter((entry) => entry?.file instanceof File);
                    if (entries.length === 0) {
                        return postJson(url, payload);
                    }
                    const formData = new FormData();
                    Object.entries(payload || {}).forEach(([key, value]) => {
                        if (value === null || value === undefined) return;
                        if (typeof value === 'object') {
                            formData.append(key, JSON.stringify(value));
                            return;
                        }
                        formData.append(key, String(value));
                    });
                    entries.forEach((entry) => {
                        formData.append('esign_files[]', entry.file, entry.file.name);
                        formData.append('esign_names[]', String(entry.name || '').trim());
                    });
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: formData,
                    });
                    const data = await response.json().catch(() => null);
                    if (!response.ok) {
                        throw new Error(String(data?.message || 'ไม่สามารถทำรายการได้'));
                    }
                    return data;
                };
                const resetFinanceEsignUpload = () => {
                    if (financeEsignRows) {
                        financeEsignRows.innerHTML = '';
                    }
                    financeEsignRowCounter = 0;
                    syncFinanceEsignEmptyState();
                };
                const formatFinanceEsignFileSize = (bytes) => {
                    const size = Number(bytes || 0);
                    if (size <= 0) return '0 KB';
                    if (size < 1024 * 1024) return `${Math.max(1, Math.round(size / 1024))} KB`;
                    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
                };
                const suggestFinanceEsignNameFromFile = (file) => {
                    const raw = String(file?.name || '').trim();
                    if (raw === '') return '';
                    return raw.replace(/\.pdf$/i, '').replace(/[_]+/g, ' ').trim();
                };
                const isPdfFile = (file) => {
                    if (!(file instanceof File)) return false;
                    const mime = String(file.type || '').toLowerCase();
                    const name = String(file.name || '').toLowerCase();
                    return mime.includes('pdf') || name.endsWith('.pdf');
                };
                const renumberFinanceEsignRows = () => {
                    Array.from(financeEsignRows?.querySelectorAll('[data-finance-esign-row]') || []).forEach((row, index) => {
                        const indexEl = row.querySelector('[data-finance-esign-index]');
                        if (indexEl) indexEl.textContent = String(index + 1);
                    });
                };
                const syncFinanceEsignRowState = (row) => {
                    if (!row) return;
                    const fileInput = row.querySelector('input[type="file"]');
                    const nameInput = row.querySelector('[data-finance-esign-name]');
                    const dropzone = row.querySelector('[data-finance-esign-dropzone]');
                    const statusEl = row.querySelector('[data-finance-esign-status]');
                    const file = fileInput?.files?.[0] || null;
                    const name = String(nameInput?.value || '').trim();
                    const hasFile = file instanceof File;
                    const hasName = name !== '';

                    row.classList.remove('border-violet-200', 'border-emerald-300', 'border-amber-300', 'ring-1', 'ring-emerald-100', 'ring-amber-100', 'ring-violet-100');
                    if (hasFile && hasName) {
                        row.classList.add('border-emerald-300', 'ring-1', 'ring-emerald-100');
                    } else if (hasFile || hasName) {
                        row.classList.add('border-amber-300', 'ring-1', 'ring-amber-100');
                    } else {
                        row.classList.add('border-violet-200');
                    }

                    if (statusEl) {
                        if (hasFile && hasName) {
                            statusEl.className = 'inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200';
                            statusEl.innerHTML = '<i class="fas fa-check text-[9px]"></i> พร้อม';
                        } else if (hasFile && !hasName) {
                            statusEl.className = 'inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200';
                            statusEl.innerHTML = '<i class="fas fa-pen text-[9px]"></i> กรอกชื่อ';
                        } else if (!hasFile && hasName) {
                            statusEl.className = 'inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200';
                            statusEl.innerHTML = '<i class="fas fa-file-pdf text-[9px]"></i> เลือกไฟล์';
                        } else {
                            statusEl.className = 'inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200';
                            statusEl.innerHTML = '<i class="fas fa-circle text-[7px]"></i> รอกรอก';
                        }
                    }

                    if (dropzone) {
                        const emptyState = dropzone.querySelector('[data-finance-esign-drop-empty]');
                        const filledState = dropzone.querySelector('[data-finance-esign-drop-filled]');
                        const fileNameEl = dropzone.querySelector('[data-finance-esign-file-name]');
                        const fileMetaEl = dropzone.querySelector('[data-finance-esign-file-meta]');
                        if (hasFile) {
                            emptyState?.classList.add('hidden');
                            emptyState?.classList.remove('flex');
                            filledState?.classList.remove('hidden');
                            filledState?.classList.add('flex');
                            dropzone.classList.remove('border-violet-200', 'bg-violet-50/40', 'hover:border-violet-300', 'hover:bg-violet-50/70');
                            dropzone.classList.add('border-emerald-300', 'bg-emerald-50/50');
                            if (fileNameEl) fileNameEl.textContent = file.name;
                            if (fileMetaEl) fileMetaEl.textContent = formatFinanceEsignFileSize(file.size);
                        } else {
                            emptyState?.classList.remove('hidden');
                            emptyState?.classList.add('flex');
                            filledState?.classList.add('hidden');
                            filledState?.classList.remove('flex');
                            dropzone.classList.remove('border-emerald-300', 'bg-emerald-50/50');
                            dropzone.classList.add('border-violet-200', 'bg-violet-50/40', 'hover:border-violet-300', 'hover:bg-violet-50/70');
                        }
                    }
                };
                const syncFinanceEsignEmptyState = () => {
                    const rows = Array.from(financeEsignRows?.querySelectorAll('[data-finance-esign-row]') || []);
                    const rowCount = rows.length;
                    financeEsignEmpty?.classList.toggle('hidden', rowCount > 0);
                    if (financeEsignCountBadge) {
                        if (rowCount > 0) {
                            const readyCount = rows.filter((row) => {
                                const file = row.querySelector('input[type="file"]')?.files?.[0];
                                const name = String(row.querySelector('[data-finance-esign-name]')?.value || '').trim();
                                return file instanceof File && name !== '';
                            }).length;
                            financeEsignCountBadge.classList.remove('hidden');
                            financeEsignCountBadge.textContent = readyCount === rowCount
                                ? `${rowCount} รายการพร้อม`
                                : `${readyCount}/${rowCount} พร้อม`;
                            financeEsignCountBadge.className = readyCount === rowCount
                                ? 'inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200'
                                : 'inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-semibold text-violet-700 ring-1 ring-violet-200';
                        } else {
                            financeEsignCountBadge.classList.add('hidden');
                        }
                    }
                    renumberFinanceEsignRows();
                };
                const assignFinanceEsignFile = (row, file, { autofillName = true } = {}) => {
                    if (!row || !(file instanceof File)) return false;
                    if (!isPdfFile(file)) {
                        window.toast?.error?.('รองรับเฉพาะไฟล์ PDF', { timer: 1600 });
                        return false;
                    }
                    const fileInput = row.querySelector('input[type="file"]');
                    const nameInput = row.querySelector('[data-finance-esign-name]');
                    if (!fileInput) return false;
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    fileInput.files = transfer.files;
                    if (autofillName && nameInput && String(nameInput.value || '').trim() === '') {
                        nameInput.value = suggestFinanceEsignNameFromFile(file);
                    }
                    syncFinanceEsignRowState(row);
                    syncFinanceEsignEmptyState();
                    return true;
                };
                const clearFinanceEsignFile = (row) => {
                    const fileInput = row?.querySelector('input[type="file"]');
                    if (!fileInput) return;
                    fileInput.value = '';
                    syncFinanceEsignRowState(row);
                    syncFinanceEsignEmptyState();
                };
                const collectFinanceEsignEntries = () => {
                    if (!financeEsignRows) return [];
                    return Array.from(financeEsignRows.querySelectorAll('[data-finance-esign-row]')).map((row) => {
                        const fileInput = row.querySelector('input[type="file"]');
                        const nameInput = row.querySelector('[data-finance-esign-name]');
                        return {
                            name: String(nameInput?.value || '').trim(),
                            file: fileInput?.files?.[0] || null,
                            row,
                        };
                    }).filter((entry) => entry.file instanceof File || entry.name !== '');
                };
                const validateFinanceEsignEntries = () => {
                    const rows = Array.from(financeEsignRows?.querySelectorAll('[data-finance-esign-row]') || []);
                    for (const row of rows) {
                        const fileInput = row.querySelector('input[type="file"]');
                        const nameInput = row.querySelector('[data-finance-esign-name]');
                        const file = fileInput?.files?.[0] || null;
                        const name = String(nameInput?.value || '').trim();
                        syncFinanceEsignRowState(row);
                        if (!file && name === '') {
                            continue;
                        }
                        if (!file) {
                            window.toast?.error?.('กรุณาเลือกไฟล์ PDF ให้ครบทุกแถวที่มีชื่อเอกสาร', { timer: 1800 });
                            row.scrollIntoView?.({ behavior: 'smooth', block: 'nearest' });
                            fileInput?.focus?.();
                            return false;
                        }
                        if (name === '') {
                            window.toast?.error?.('กรุณากรอกชื่อเอกสารให้ครบทุกแถวที่มีไฟล์', { timer: 1800 });
                            row.scrollIntoView?.({ behavior: 'smooth', block: 'nearest' });
                            nameInput?.focus?.();
                            return false;
                        }
                    }
                    return true;
                };
                const createFinanceEsignRow = ({ focus = true } = {}) => {
                    if (!financeEsignRows) return null;
                    financeEsignRowCounter += 1;
                    const rowId = financeEsignRowCounter;
                    const row = document.createElement('div');
                    row.className = 'rounded-2xl border border-violet-200 bg-white p-3 shadow-sm transition';
                    row.dataset.financeEsignRow = String(rowId);
                    row.innerHTML = `
                        <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span data-finance-esign-index
                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-600 text-[11px] font-bold text-white shadow-sm shadow-violet-200">1</span>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-700">เอกสารสำหรับผู้บริหารลงนาม</div>
                                    <div class="text-[11px] text-slate-400">ชื่อที่แสดงในระบบ + ไฟล์ PDF</div>
                                </div>
                                <span data-finance-esign-status
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">
                                    <i class="fas fa-circle text-[7px]"></i> รอกรอก
                                </span>
                            </div>
                            <button type="button" data-finance-esign-remove
                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                                title="ลบแถวนี้">
                                <i class="fas fa-trash text-[10px]"></i>
                                ลบ
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] lg:items-stretch">
                            <div class="flex flex-col">
                                <label class="mb-1 block text-xs font-bold text-slate-700">
                                    ชื่อเอกสาร <span class="font-semibold text-rose-500">*</span>
                                </label>
                                <div class="relative flex-1">
                                    <input type="text" data-finance-esign-name
                                        placeholder="เช่น ใบสำคัญการตั้งหนี้"
                                        class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-100" />
                                </div>
                            </div>
                            <div class="flex flex-col">
                                <label class="mb-1 block text-xs font-bold text-slate-700">
                                    ไฟล์ PDF <span class="font-semibold text-rose-500">*</span>
                                </label>
                                <div data-finance-esign-dropzone
                                    class="relative flex h-10 cursor-pointer items-center rounded-xl border-2 border-dashed border-violet-200 bg-violet-50/40 px-3 transition hover:border-violet-300 hover:bg-violet-50/70">
                                    <input type="file" accept="application/pdf,.pdf" class="sr-only" data-finance-esign-file />
                                    <div data-finance-esign-drop-empty class="flex w-full items-center gap-3">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center text-violet-600">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </span>
                                        <div class="min-w-0 text-left">
                                            <div class="text-xs font-semibold text-slate-700">คลิกเลือก หรือลากไฟล์ PDF มาวาง</div>
                                            <div class="text-[11px] text-slate-500">รองรับเฉพาะ .pdf</div>
                                        </div>
                                    </div>
                                    <div data-finance-esign-drop-filled class="hidden w-full items-center gap-3">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center text-emerald-700">
                                            <i class="fas fa-file-pdf"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div data-finance-esign-file-name class="truncate text-xs font-semibold text-slate-800"></div>
                                            <div data-finance-esign-file-meta class="text-[11px] text-slate-500"></div>
                                        </div>
                                        <button type="button" data-finance-esign-clear-file
                                            class="shrink-0 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50"
                                            title="เปลี่ยนไฟล์">
                                            เปลี่ยน
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    const fileInput = row.querySelector('input[type="file"]');
                    const nameInput = row.querySelector('[data-finance-esign-name]');
                    const dropzone = row.querySelector('[data-finance-esign-dropzone]');
                    const clearFileButton = row.querySelector('[data-finance-esign-clear-file]');

                    nameInput?.addEventListener('input', () => {
                        syncFinanceEsignRowState(row);
                        syncFinanceEsignEmptyState();
                    });
                    fileInput?.addEventListener('change', () => {
                        const file = fileInput.files?.[0] || null;
                        if (!file) {
                            syncFinanceEsignRowState(row);
                            syncFinanceEsignEmptyState();
                            return;
                        }
                        if (!isPdfFile(file)) {
                            fileInput.value = '';
                            window.toast?.error?.('รองรับเฉพาะไฟล์ PDF', { timer: 1600 });
                            syncFinanceEsignRowState(row);
                            syncFinanceEsignEmptyState();
                            return;
                        }
                        if (nameInput && String(nameInput.value || '').trim() === '') {
                            nameInput.value = suggestFinanceEsignNameFromFile(file);
                        }
                        syncFinanceEsignRowState(row);
                        syncFinanceEsignEmptyState();
                    });
                    dropzone?.addEventListener('click', (event) => {
                        if (event.target.closest('[data-finance-esign-clear-file]')) return;
                        fileInput?.click();
                    });
                    clearFileButton?.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        clearFinanceEsignFile(row);
                        fileInput?.click();
                    });
                    ['dragenter', 'dragover'].forEach((type) => {
                        dropzone?.addEventListener(type, (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            dropzone.classList.add('border-violet-500', 'bg-violet-100/70', 'scale-[1.01]');
                        });
                    });
                    ['dragleave', 'drop'].forEach((type) => {
                        dropzone?.addEventListener(type, (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            dropzone.classList.remove('border-violet-500', 'bg-violet-100/70', 'scale-[1.01]');
                        });
                    });
                    dropzone?.addEventListener('drop', (event) => {
                        const file = event.dataTransfer?.files?.[0] || null;
                        if (!file) return;
                        assignFinanceEsignFile(row, file, { autofillName: true });
                    });

                    financeEsignRows.appendChild(row);
                    syncFinanceEsignRowState(row);
                    syncFinanceEsignEmptyState();
                    if (focus) {
                        nameInput?.focus?.();
                    }
                    return row;
                };
                const removeFinanceEsignRow = (rowEl) => {
                    rowEl?.remove?.();
                    syncFinanceEsignEmptyState();
                };
                const collectExpenseMappingRows = () => {
                    const itemRows = Array.from(
                        financeExpenseMappingBody?.querySelectorAll('[data-withdraw-item-id]') || []
                    ).filter((row) => Number(String(row?.dataset?.withdrawItemId || '').trim() || 0) > 0);
                    return itemRows.map((row) => {
                        const hiddenInput = row.querySelector('[data-expense-map-select="1"]');
                        const textInput = row
                            .querySelector('[data-pbm-searchable-select-wrapper]')
                            ?.querySelector('[data-pbm-searchable-select-text]');
                        const amountInput = row.querySelector('[data-expense-map-amount="1"]');
                        return {
                            row,
                            itemId: Number(String(row?.dataset?.withdrawItemId || '').trim() || 0),
                            hiddenInput,
                            textInput,
                            amountInput,
                            hiddenValue: String(hiddenInput?.value || '').trim(),
                            textValue: String(textInput?.value || '').trim(),
                            amountValue: String(amountInput?.value || '').trim(),
                        };
                    });
                };
                const validateFinanceExpenseSelections = () => {
                    const mappingRows = collectExpenseMappingRows();
                    if (mappingRows.length === 0) {
                        window.toast?.error?.('ไม่พบรายการเบิกจ่ายสำหรับตรวจสอบผังบัญชี', { timer: 1800 });
                        return false;
                    }

                    const missingInputRow = mappingRows.find((row) => !row.hiddenInput);
                    if (missingInputRow) {
                        window.toast?.error?.('ไม่พบช่องเลือกผังบัญชี กรุณาปิดแล้วเปิดหน้าตรวจสอบใหม่อีกครั้ง', { timer: 2200 });
                        return false;
                    }

                    const invalidRow = mappingRows.find((row) => {
                        const selectedId = Number(row.hiddenValue || 0);
                        return selectedId <= 0 || row.textValue === '';
                    });
                    if (!invalidRow) {
                        return true;
                    }

                    const rowElement = invalidRow.row;
                    const rowLabel = String(rowElement?.dataset?.itemName || rowElement?.querySelector('td:nth-child(2)')?.textContent || '').trim().replace(/^-\s*/, '');
                    window.toast?.error?.(
                        rowLabel !== ''
                            ? `กรุณาเลือกผังบัญชีให้ครบทุกช่อง (${rowLabel})`
                            : 'กรุณาเลือกผังบัญชีให้ครบทุกช่องก่อนยืนยันตรวจสอบ',
                        { timer: 1800 }
                    );

                    const searchableTextInput = invalidRow.textInput;
                    searchableTextInput?.focus?.();
                    return false;
                };
                const validateFinanceExpenseAmounts = () => {
                    const invalidAmountRow = collectExpenseMappingRows().find((row) => parseMoneyInput(row.amountValue) <= 0);
                    if (!invalidAmountRow) return true;
                    const rowLabel = String(invalidAmountRow.row?.dataset?.itemName || '').trim();
                    window.toast?.error?.(
                        rowLabel !== ''
                            ? `กรุณากรอกจำนวนเงินให้มากกว่า 0 (${rowLabel})`
                            : 'กรุณากรอกจำนวนเงินให้มากกว่า 0 ทุกแถว',
                        { timer: 1800 }
                    );
                    invalidAmountRow.amountInput?.focus?.();
                    return false;
                };
                const validateBudgetSourceSelection = () => {
                    const manageId = String(financeBudgetSourceInput?.value || '').trim();
                    const validManageId = Number(manageId);
                    if (manageId !== '' && Number.isFinite(validManageId) && validManageId > 0) {
                        return true;
                    }

                    window.toast?.error?.('กรุณาเลือกงบประมาณจากรายการก่อนยืนยันตรวจสอบ', { timer: 1800 });
                    const sourceTextInput = financeBudgetSourceInput
                        ?.closest('[data-pbm-searchable-select-wrapper]')
                        ?.querySelector('[data-pbm-searchable-select-text]');
                    sourceTextInput?.focus?.();
                    return false;
                };
                const resolveExistingBudgetManageId = (detailRow, detailRoot = null) => {
                    const itemRows = Array.isArray(detailRow?.items) ? detailRow.items : [];
                    const idFromItems = itemRows
                        .map((item) => String(item?.budget_manage_id || '').trim())
                        .find((value) => Number(value || 0) > 0);
                    if (idFromItems) {
                        return idFromItems;
                    }

                    const budgetIdFromDom = Array.from(
                        detailRoot?.querySelectorAll?.('table tbody tr[data-budget-manage-id]') || []
                    )
                        .map((row) => String(row?.dataset?.budgetManageId || '').trim())
                        .find((value) => Number(value || 0) > 0);
                    return budgetIdFromDom || '';
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
                        if (activeTab === 'approved' && userStepStatus !== 'approved') return false;
                        if (activeTab === 'pending' && userStepStatus !== 'pending') return false;
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
                    const withdrawKind = normalizeWithdrawKind(trigger.dataset.withdrawKind || '');
                    const row = findFinanceRowById(id, withdrawKind);

                    if (trigger.dataset.action === 'approve') {
                        pendingFinanceReviewId = id;
                        pendingFinanceReviewKind = withdrawKind;
                        pendingFinanceReviewAmount = toNumber(row?.amount || 0);
                        if (financeReviewComment) financeReviewComment.value = '';
                        setFinanceReviewLoading(true);
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-general-detail-modal' }));
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-salary-detail-modal' }));
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-finance-review-form-modal' }));
                        try {
                        await openFinanceDetailModal(id, withdrawKind, { openModal: false });
                        const detailRow = getFinanceDetailRow(withdrawKind);
                        pendingFinanceDetailRow = detailRow;
                        toggleFinanceReviewLayout(withdrawKind);
                        fillFinanceRequestForm();
                        renderFinanceRevisionInfo(row, detailRow);
                        await loadExpenseChartOptions(withdrawKind);
                        await loadBudgetSourceOptions(true, withdrawKind);
                        await loadPayListOptions(withdrawKind);
                        const requestFiscalYear = String(row?.fiscal_year || '').trim();
                        let budgetSourceOptionsByYear = requestFiscalYear !== ''
                            ? budgetSourceOptions.filter((option) => String(option?.year || '').trim() === requestFiscalYear)
                            : budgetSourceOptions;
                        const existingBudgetManageId = resolveExistingBudgetManageId(detailRow);
                        if (existingBudgetManageId !== '' && !budgetSourceOptionsByYear.some((option) => String(option?.id || '').trim() === existingBudgetManageId)) {
                            const existingOption = budgetSourceOptions.find((option) => String(option?.id || '').trim() === existingBudgetManageId);
                            if (existingOption) {
                                budgetSourceOptionsByYear = [...budgetSourceOptionsByYear, existingOption];
                            }
                        }
                        if (window.PBMSearchableDropdown?.setOptions) {
                            window.PBMSearchableDropdown.setOptions('finance-budget-source-options', budgetSourceOptionsByYear);
                        }
                        if (window.PBMSearchableDropdown?.setStatus) {
                            window.PBMSearchableDropdown.setStatus(
                                'finance-budget-source-options',
                                budgetSourceOptionsByYear.length === 0
                                    ? (requestFiscalYear !== '' ? `ไม่พบข้อมูลงบประมาณสำหรับปี ${requestFiscalYear}` : 'ไม่พบข้อมูลงบประมาณ')
                                    : ''
                            );
                        }
                        if (financeBudgetSourceInput) {
                            const hasExistingBudgetOption = existingBudgetManageId !== ''
                                && budgetSourceOptionsByYear.some((option) => String(option?.id || '').trim() === existingBudgetManageId);
                            financeBudgetSourceInput.value = hasExistingBudgetOption ? existingBudgetManageId : '';
                            financeBudgetSourceInput.dispatchEvent(new Event('input', { bubbles: true }));
                            financeBudgetSourceInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        renderBudgetSummary();
                        const financeRequestDate = todayIsoDate();
                        setFinanceRequestDocumentDate(financeRequestDate);
                        updateFinanceRequestAutoText(row);
                        applySavedFinanceMemoValues(detailRow);
                        await refreshFinanceDocumentNumberPreview(financeRequestDate, detailRow);
                        if (isProjectWithdrawKind(withdrawKind)) {
                            await renderFinanceProcurementList(id);
                        }
                        renderFinanceAttachedDocsList(Array.isArray(detailRow?.finance_esign_files) ? detailRow.finance_esign_files : []);
                        const isSalaryReview = normalizeWithdrawKind(withdrawKind) === 'salary';
                        const detailRoot = document.getElementById(isSalaryReview ? 'withdraw-salary-detail-status' : 'withdraw-detail-status')?.closest('.space-y-3');
                        if (financeReviewDetailContent) {
                            if (financeProcurementCard && financeMemoSection) {
                                financeMemoSection.insertAdjacentElement('beforebegin', financeProcurementCard);
                            }
                            if (detailRoot) {
                                const cloned = detailRoot.cloneNode(true);
                                cloned.querySelectorAll('.overflow-x-auto').forEach((container) => {
                                    container.classList.remove('overflow-x-auto');
                                    container.classList.add('overflow-visible');
                                });
                                cloned.querySelector('#withdraw-detail-approval-route-section, #withdraw-salary-detail-approval-route-section')?.remove();
                                cloned.querySelector('#withdraw-detail-approval-section, #withdraw-salary-detail-approval-section')?.remove();
                                cloned.querySelector('#withdraw-detail-applicant, #withdraw-salary-detail-applicant')?.closest('.text-center')?.remove();
                                cloned.querySelector('#withdraw-detail-reviewer, #withdraw-salary-detail-reviewer')?.closest('.text-center')?.remove();
                                const signerGrid = cloned.querySelector('.grid.grid-cols-1.gap-3.md\\:grid-cols-2');
                                if (signerGrid && signerGrid.children.length === 0) {
                                    signerGrid.remove();
                                }
                                cloned.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
                                const supportingPreview = cloned.querySelector('[data-withdraw-document-preview]');
                                if (supportingPreview && detailRow && normalizeWithdrawKind(withdrawKind) !== 'salary' && window.WithdrawDocumentPreview?.fill) {
                                    window.WithdrawDocumentPreview.fill(supportingPreview, detailRow, { projectDocsLast: true });
                                }
                                financeReviewDetailContent.innerHTML = '';
                                financeReviewDetailContent.appendChild(cloned);
                                const supportingHeading = Array.from(cloned.querySelectorAll('div')).find((node) => (
                                    node.children.length === 0 && node.textContent.trim() === 'เอกสารประกอบการเบิกจ่าย'
                                ));
                                const supportingSection = supportingHeading?.closest('.p-1') || supportingHeading?.parentElement || null;
                                if (financeProcurementCard && supportingSection) {
                                    supportingSection.before(financeProcurementCard);
                                }
                                renderFinanceExpenseMappingTable(id, detailRow);
                            } else {
                                financeReviewDetailContent.innerHTML = '<div class="text-slate-400">- ไม่สามารถโหลดข้อมูลรายละเอียดได้ -</div>';
                                renderFinanceExpenseMappingTable(id, null);
                            }
                        }
                        placeFinanceApprovalRoute(withdrawKind);
                        } catch (error) {
                            if (financeReviewDetailContent) {
                                financeReviewDetailContent.innerHTML = '<div class="text-slate-400">- ไม่สามารถโหลดข้อมูลรายละเอียดได้ -</div>';
                            }
                        } finally {
                            setFinanceReviewLoading(false);
                        }
                        return;
                    }

                    await openFinanceDetailModal(id, withdrawKind, {});
                });

                approveConfirmButton?.addEventListener('click', async () => {
                    const id = Number(pendingApproveId || 0);
                    if (id <= 0) return;
                    const comment = String(approveCommentInput?.value || '').trim();
                    try {
                        const approveKind = normalizeWithdrawKind(
                            findFinanceRowById(id, pendingFinanceReviewKind)?.withdraw_kind || pendingFinanceReviewKind
                        );
                        const result = await postJson(proceedApiForKind(approveKind), {
                            id,
                            comment,
                            approval_comment: comment,
                        });
                        if (result?.status !== 'success') {
                            window.toast?.error?.(result?.message || 'ไม่สามารถตรวจสอบรายการได้', { timer: 1500 });
                            return;
                        }
                        window.toast?.success?.(result?.message || 'ตรวจสอบรายการสำเร็จ', { timer: 1200 });
                        pendingApproveId = 0;
                        pendingFinanceReviewKind = 'general';
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-approval-confirm-modal' }));
                        await fetchRows();
                    } catch (error) {
                        window.toast?.error?.(error?.message || 'ไม่สามารถตรวจสอบรายการได้', { timer: 1500 });
                    }
                });

                financeReviewSubmitButton?.addEventListener('click', async () => {
                    const id = Number(pendingFinanceReviewId || 0);
                    if (id <= 0) return;
                    if (!validateFinanceExpenseSelections()) return;
                    if (!validateFinanceExpenseAmounts()) return;
                    if (!validateBudgetSourceSelection()) return;
                    if (!validateFinanceEsignEntries()) return;
                    const expenseMappings = collectExpenseMappingRows().map((row) => ({
                        item_id: row.itemId,
                        expense_chart_id: Number(row.hiddenValue || 0),
                        amount: Math.round(parseMoneyInput(row.amountValue) * 100) / 100,
                    })).filter((row) => row.item_id > 0);
                    const comment = String(financeReviewComment?.value || '').trim();
                    const budgetManageId = Number(String(financeBudgetSourceInput?.value || '').trim() || 0);
                    pendingFinanceSubmitPayload = {
                        id,
                        comment,
                        approval_comment: comment,
                        budget_manage_id: budgetManageId > 0 ? budgetManageId : null,
                        expense_mappings: expenseMappings,
                    };
                    const financeDocumentNumberInput = document.getElementById('finance-request-document-number');
                    const financeDocumentBaseNumber = String(financeDocumentNumberInput?.dataset?.baseNumber || '').trim();
                    pendingFinanceSubmitPayload.document_number = financeDocumentBaseNumber !== ''
                        ? financeDocumentBaseNumber
                        : String(financeDocumentNumberInput?.value || '').trim();
                    pendingFinanceSubmitPayload.document_date = String(document.getElementById('finance-request-document-date')?.value || '').trim();
                    pendingFinanceSubmitPayload.subject = String(document.getElementById('finance-request-subject')?.value || '').trim();
                    pendingFinanceSubmitPayload.to = String(document.getElementById('finance-request-to')?.value || '').trim();
                    pendingFinanceSubmitPayload.paragraph1 = String(document.getElementById('finance-request-paragraph1')?.value || '').trim();
                    pendingFinanceSubmitPayload.paragraph2 = String(document.getElementById('finance-request-paragraph2')?.value || '').trim();
                    pendingFinanceSubmitPayload.paragraph3 = String(document.getElementById('finance-request-paragraph3')?.value || '').trim();
                    pendingFinanceSubmitPayload.department_code = String(financeDocumentCode || '12010401').trim() || '12010401';
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-finance-review-confirm-modal' }));
                });

                window.__withdrawFinanceConfirmSubmit = async () => {
                    const payload = pendingFinanceSubmitPayload;
                    if (!payload || Number(payload.id || 0) <= 0) {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-finance-review-confirm-modal' }));
                        return;
                    }
                    try {
                        const esignEntries = collectFinanceEsignEntries()
                            .filter((entry) => entry.file instanceof File)
                            .map((entry) => ({ name: entry.name, file: entry.file }));
                        const result = await postFinanceReviewPayload(
                            proceedApiForKind(pendingFinanceReviewKind),
                            payload,
                            esignEntries
                        );
                        if (result?.status !== 'success') {
                            window.toast?.error?.(result?.message || 'ไม่สามารถตรวจสอบรายการได้', { timer: 1500 });
                            return;
                        }
                        window.toast?.success?.(result?.message || 'ตรวจสอบรายการสำเร็จ', { timer: 1200 });
                        pendingFinanceReviewId = 0;
                        pendingFinanceReviewKind = 'general';
                        pendingFinanceDetailRow = null;
                        pendingFinanceSubmitPayload = null;
                        resetFinanceEsignUpload();
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-finance-review-form-modal' }));
                        await fetchRows();
                        await refreshApprovalPendingBadge();
                    } catch (error) {
                        window.toast?.error?.(error?.message || 'ไม่สามารถตรวจสอบรายการได้', { timer: 1500 });
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-finance-review-confirm-modal' }));
                    }
                };

                financeRevisionButton?.addEventListener('click', () => {
                    const id = Number(pendingFinanceReviewId || 0);
                    if (id <= 0) return;
                    if (financeRevisionReasonInput) {
                        financeRevisionReasonInput.value = '';
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-finance-revision-modal' }));
                });

                financeRevisionSubmitButton?.addEventListener('click', async () => {
                    const id = Number(pendingFinanceReviewId || 0);
                    if (id <= 0) return;
                    const revisionReason = String(financeRevisionReasonInput?.value || '').trim();
                    if (revisionReason === '') {
                        window.toast?.error?.('กรุณาระบุรายละเอียดที่ต้องแก้ไขก่อนส่งกลับ', { timer: 1800 });
                        financeRevisionReasonInput?.focus?.();
                        return;
                    }

                    try {
                        const result = await postJson(proceedApiForKind(pendingFinanceReviewKind), {
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
                        pendingFinanceReviewId = 0;
                        pendingFinanceReviewKind = 'general';
                        pendingFinanceDetailRow = null;
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-finance-revision-modal' }));
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-finance-review-form-modal' }));
                        await fetchRows();
                    } catch (error) {
                        window.toast?.error?.(error?.message || 'ไม่สามารถส่งกลับให้แก้ไขได้', { timer: 1500 });
                    }
                });

                const fetchRows = async () => {
                    try {
                        const response = await fetch(generalListApi, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        generalTableRows = Array.isArray(result?.data) ? result.data : [];
                        syncPendingBadge(generalTableRows);
                        populateFilters(generalTableRows);
                        applyFilters();
                        await consumeFinanceDeepLink();
                    } catch (error) {
                        window.toast?.error?.('ไม่สามารถโหลดรายการตรวจสอบได้', { timer: 1400 });
                    }
                };

                const consumeFinanceDeepLink = async () => {
                    if (window.__withdrawFinanceDeepLinkConsumed) return;
                    const params = new URLSearchParams(window.location.search);
                    const deepId = Number(params.get('id') || 0);
                    if (deepId <= 0) return;
                    window.__withdrawFinanceDeepLinkConsumed = true;
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
                        window.toast?.error?.('ไม่พบรายการนี้ในรายการรอตรวจสอบของท่าน', { timer: 2400 });
                        return;
                    }
                    approveButton.click();
                };

                syncPendingBadge(generalTableRows);
                populateFilters(generalTableRows);
                syncTabUi();
                applyFilters();
                pendingTabButton?.addEventListener('click', () => {
                    activeTab = 'pending';
                    syncTabUi();
                    applyFilters();
                });
                approvedTabButton?.addEventListener('click', () => {
                    activeTab = 'approved';
                    syncTabUi();
                    applyFilters();
                });
                filterFiscalYearSelect?.addEventListener('change', applyFilters);
                filterStatusSelect?.addEventListener('change', applyFilters);
                filterTypeSelect?.addEventListener('change', applyFilters);
                filterSearchInput?.addEventListener('input', applyFilters);
                const refreshFinanceExpenseMappingForBudget = () => {
                    const recordId = Number(pendingFinanceReviewId || 0);
                    if (recordId <= 0 || !pendingFinanceDetailRow) return;
                    renderFinanceExpenseMappingTable(recordId, pendingFinanceDetailRow);
                };
                financeBudgetSourceInput?.addEventListener('change', renderBudgetSummary);
                financeBudgetSourceInput?.addEventListener('input', renderBudgetSummary);
                financeBudgetSourceInput?.addEventListener('change', () => updateFinanceRequestAutoText(null));
                financeBudgetSourceInput?.addEventListener('change', refreshFinanceExpenseMappingForBudget);
                financeExpenseResetAmountsButton?.addEventListener('click', () => {
                    expenseMapItemRows().forEach((row) => {
                        const input = row.querySelector('[data-expense-map-amount="1"]');
                        if (!(input instanceof HTMLInputElement)) return;
                        input.value = formatMoney(parseMoneyInput(row.dataset.originalAmount || '0'));
                    });
                    syncFinanceExpenseAmountTotals();
                });
                document.getElementById('finance-expense-copy-chart')?.addEventListener('click', () => {
                    const sourceRow = expenseMapItemRows().find((row) => Number(row.querySelector('[data-expense-map-select="1"]')?.value || 0) > 0);
                    const chartId = String(sourceRow?.querySelector('[data-expense-map-select="1"]')?.value || '').trim();
                    const chartLabel = String(sourceRow?.querySelector('[data-pbm-searchable-select-text]')?.value || '').trim();
                    if (!sourceRow || Number(chartId) <= 0 || chartLabel === '') {
                        window.toast?.error?.('เลือกผังบัญชีอย่างน้อย 1 แถวก่อน', { timer: 1800 });
                        return;
                    }
                    let copied = 0;
                    expenseMapItemRows().forEach((row) => {
                        if (row === sourceRow) return;
                        const current = Number(row.querySelector('[data-expense-map-select="1"]')?.value || 0);
                        if (current > 0) return;
                        applyChartToMappingRow(row, chartId, chartLabel);
                        copied += 1;
                    });
                    refreshFinanceMappingRowState();
                    window.toast?.[copied > 0 ? 'success' : 'error']?.(
                        copied > 0 ? `ใช้ผังบัญชีกับ ${copied} แถวที่ยังว่าง` : 'ไม่มีแถวที่ยังไม่ได้เลือกผังบัญชี',
                        { timer: 1600 }
                    );
                });
                financeEsignAddRowButton?.addEventListener('click', () => createFinanceEsignRow());
                financeEsignEmptyAddButton?.addEventListener('click', () => createFinanceEsignRow());
                financeEsignRows?.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-finance-esign-remove]');
                    if (!removeButton) return;
                    event.preventDefault();
                    removeFinanceEsignRow(removeButton.closest('[data-finance-esign-row]'));
                });
                financeRequestDocumentDateInput?.addEventListener('change', async () => {
                    if (Number(pendingFinanceReviewId || 0) <= 0) return;
                    await refreshFinanceDocumentNumberPreview(String(financeRequestDocumentDateInput?.value || '').trim(), pendingFinanceDetailRow);
                });
                fetchRows();
            });
        </script>
        <style>
            [data-pbm-searchable-select-suggest] button {
                background-color: #ffffff;
                color: #334155;
            }
            @media (max-width: 1023px) {
                #finance-procurement-card iframe,
                #finance-attached-docs-card iframe {
                    display: none !important;
                    width: 0 !important;
                    height: 0 !important;
                    border: 0 !important;
                }
            }
        </style>
    @endpush
</x-layouts.app>
