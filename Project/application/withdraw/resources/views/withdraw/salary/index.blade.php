<x-layouts.app>
    <div class="py-2">
        <div class="mx-2 flex flex-col gap-4">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50/80 to-emerald-50/50 p-4 shadow-md shadow-slate-200/50 sm:px-4 sm:py-4">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-400/15 blur-2xl sm:h-36 sm:w-36"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-emerald-200/60 to-transparent"></div>
                <div class="relative flex min-w-0 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-lg text-white shadow-lg shadow-emerald-500/30 ring-4 ring-white/80">
                            <i class="fas fa-list-check" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.65rem]">{{ !empty($isReviewerApprovalMode) ? 'อนุมัติเบิกจ่ายเงินเดือน/ค่าจ้าง' : 'รายการเบิกจ่ายเงินเดือน/ค่าจ้าง' }}</h4>
                            <p class="mb-0 max-w-2xl text-sm leading-snug text-slate-500">
                                {{ !empty($isReviewerApprovalMode) ? 'หน้านี้แสดงรายการเบิกจ่ายเงินเดือน/ค่าจ้างที่รอการอนุมัติจากผู้ตรวจสอบ' : 'หน้านี้สำหรับสรุปและจัดการรายการเบิกจ่ายเงินเดือน/ค่าจ้างของระบบ withdraw' }}
                            </p>
                        </div>
                    </div>
                    @if (empty($isReviewerApprovalMode))
                        <button type="button" id="withdraw-open-create-modal"
                            class="inline-flex w-fit items-center justify-center gap-2 self-start rounded-xl border border-emerald-500/40 bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-sm font-bold text-white shadow-xl shadow-emerald-500/40 ring-2 ring-emerald-200/70 transition-all hover:-translate-y-0.5 hover:scale-[1.02] hover:from-emerald-500 hover:to-teal-500 hover:shadow-2xl hover:shadow-emerald-500/45 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-emerald-300/70 lg:self-center">
                            <i class="fas fa-file-signature text-sm" aria-hidden="true"></i>
                            เพิ่มการเบิกจ่าย
                        </button>
                    @endif
                </div>
            </div>

            @php
                $sampleRows = collect($withdrawRows ?? []);
                $isReviewerApprovalMode = !empty($isReviewerApprovalMode);
                $isAdminUser = (string) session('pbm.system_role', '') === 'admin';

                $statusClasses = [
                    'ร่าง' => 'bg-slate-100 text-slate-700 ring-slate-200',
                    'รออนุมัติ' => 'bg-amber-100 text-amber-700 ring-amber-200',
                    'ยกเลิกรายการ' => 'bg-slate-200 text-slate-700 ring-slate-300',
                    'อนุมัติ' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                    '-' => 'bg-slate-100 text-slate-500 ring-slate-200',
                ];

                $departmentOptions = [];
                $departmentSearchOptions = [];
                $departmentDocumentMap = [];
                $departmentCodeSet = [];
                $defaultDepartmentCode = '';
                $staffListOptions = [];
                $defaultApplicantStaffId = '';
                $currentStaffJobCode = '';
                $currentStaffDepCode = '';
                $departmentApiUrl = (string) config('services.department_api.url', '');
                $departmentApiToken = (string) config('services.department_api.token', '');
                $staffRxTokenRefreshUrl = (string) config('services.staff_rx_api.token_refresh_url', '');
                $staffRxCheckStaffUrl = (string) config('services.staff_rx_api.check_staff_url', '');
                $staffRxListStaffUrl = (string) config('services.staff_rx_api.list_staff_url', '');
                $staffRxSignatureApiBase = (string) config('services.staff_rx_api.signature_api_base', 'https://api.pharmacy.cmu.ac.th/api/staffSignature?staffID=');
                $currentStaffId = trim((string) (session('staff_id') ?? auth()->user()?->user_id ?? ''));
                $bookNumberByType = [];
                $generalBookNumberSuffix = '';

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
                    }
                } catch (\Throwable $e) {
                    $bookNumberByType = [];
                    $generalBookNumberSuffix = '';
                }

                $applyStaffApiAuth = static function (\Illuminate\Http\Client\PendingRequest $http, string $bearerHeaderToken, string $rawBearerToken) {
                    if ($rawBearerToken !== '') {
                        return $http->withToken($rawBearerToken);
                    }
                    if (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                        return $http->withHeaders(['Authorization' => $bearerHeaderToken]);
                    }

                    return $http->withToken($bearerHeaderToken);
                };

                $refreshStaffApiToken = static function (string $staffRxTokenRefreshUrl) {
                    if ($staffRxTokenRefreshUrl === '') {
                        return ['', '', false];
                    }

                    $tokenRefreshResponse = \Illuminate\Support\Facades\Http::acceptJson()
                        ->timeout(10)
                        ->get($staffRxTokenRefreshUrl);

                    if (! $tokenRefreshResponse->successful()) {
                        return ['', '', false];
                    }

                    $bearerHeaderToken = trim((string) $tokenRefreshResponse->json('token', ''));
                    $rawBearerToken = trim((string) $tokenRefreshResponse->json('raw_token', ''));

                    if ($rawBearerToken === '' && $bearerHeaderToken === '') {
                        return ['', '', false];
                    }

                    return [$bearerHeaderToken, $rawBearerToken, true];
                };

                $buildThaiName = static function (array $row) {
                    $prefix = trim((string) ($row['name_prefix_full'] ?? ''));
                    if ($prefix === '') {
                        $prefix = trim((string) ($row['name_prefix'] ?? ''));
                    }
                    $thai = trim(
                        $prefix.
                        (string) ($row['fname'] ?? '').
                        ' '.
                        (string) ($row['lname'] ?? '')
                    );

                    return $thai;
                };

                $buildStaffListLabel = static function (array $row) use ($buildThaiName) {
                    $name = $buildThaiName($row);
                    if ($name === '') {
                        $name = trim(
                            (string) ($row['e_fname'] ?? '').
                            ' '.
                            (string) ($row['e_lname'] ?? '')
                        );
                    }
                    if ($name === '') {
                        return '';
                    }

                    $position = trim((string) ($row['name_position'] ?? ''));
                    $job = trim((string) ($row['name_job'] ?? ''));
                    if ($job === '') {
                        $job = trim((string) ($row['name_department'] ?? ''));
                    }

                    if ($position !== '' && $job !== '') {
                        return $name.' - '.$position.' / '.$job;
                    }
                    if ($position !== '') {
                        return $name.' - '.$position;
                    }
                    if ($job !== '') {
                        return $name.' - '.$job;
                    }

                    return $name;
                };

                $matchCurrentStaff = static function (string $currentStaffId, array $row) {
                    if ($currentStaffId === '') {
                        return false;
                    }
                    $code = trim((string) ($row['code'] ?? ''));
                    if ($code !== '' && $code === $currentStaffId) {
                        return true;
                    }
                    $id = (int) ($row['id'] ?? 0);
                    if ($id > 0 && (string) $id === (string) (int) $currentStaffId) {
                        return true;
                    }

                    return false;
                };

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
                                $depName = trim((string) ($row['dep_name'] ?? ''));
                                $jobName = trim((string) ($row['job_name'] ?? ''));
                                $agencyName = trim((string) ($row['agency_name'] ?? ''));
                                $unitCode = trim((string) ($row['unit_code'] ?? ''));
                                $jobCode = trim((string) ($row['job_code'] ?? ''));
                                $depDocumentNumber = trim((string) ($row['dep_document_number'] ?? ''));
                                $jobDocumentNumber = trim((string) ($row['job_document_number'] ?? ''));
                                $unitDocumentNumber = trim((string) ($row['document_number'] ?? ''));

                                if ($depCode === '' || $depName === '') {
                                    continue;
                                }

                                $departmentOnlyKey = 'dep|'.$depCode;
                                $departmentOptions[$departmentOnlyKey] = [
                                    'value' => $depCode,
                                    'label' => $depName,
                                    'document_number' => $depDocumentNumber,
                                ];

                                if ($jobCode !== '' && $jobName !== '') {
                                    $jobLevelKey = 'job|'.$depCode.'|'.$jobCode;
                                    $departmentOptions[$jobLevelKey] = [
                                        'value' => $jobCode,
                                        'label' => $depName.' > '.$jobName,
                                        'document_number' => $jobDocumentNumber !== '' ? $jobDocumentNumber : $depDocumentNumber,
                                    ];
                                }

                                if ($agencyName !== '') {
                                    $agencyLevelKey = 'agency|'.$depCode.'|'.$jobCode.'|'.$unitCode.'|'.$agencyName;
                                    $agencyValue = $unitCode !== '' ? $unitCode : ($jobCode !== '' ? $jobCode : $depCode);
                                    $agencyLabel = $depName;

                                    if ($jobName !== '') {
                                        $agencyLabel .= ' > '.$jobName;
                                    }

                                    $agencyLabel .= ' > '.$agencyName;

                                    $departmentOptions[$agencyLevelKey] = [
                                        'value' => $agencyValue,
                                        'label' => $agencyLabel,
                                        'document_number' => $unitDocumentNumber !== '' ? $unitDocumentNumber : ($jobDocumentNumber !== '' ? $jobDocumentNumber : $depDocumentNumber),
                                    ];
                                }
                            }
                        }
                    }

                    foreach ($departmentOptions as $departmentOption) {
                        $optionCode = (string) ($departmentOption['value'] ?? '');
                        $optionLabel = (string) ($departmentOption['label'] ?? '');
                        $optionDocumentNumber = (string) ($departmentOption['document_number'] ?? '');

                        if ($optionCode === '' || $optionLabel === '') {
                            continue;
                        }

                        $departmentSearchOptions[] = [
                            'code' => $optionCode,
                            'label' => $optionLabel,
                        ];
                        $departmentCodeSet[$optionCode] = true;

                        if (! array_key_exists($optionCode, $departmentDocumentMap) || $departmentDocumentMap[$optionCode] === '') {
                            $departmentDocumentMap[$optionCode] = $optionDocumentNumber;
                        }
                    }

                    if (
                        $currentStaffId !== '' &&
                        $staffRxTokenRefreshUrl !== '' &&
                        $staffRxCheckStaffUrl !== '' &&
                        count($departmentCodeSet) > 0
                    ) {
                        [$bearerHeaderToken, $rawBearerToken, $tokenOk] = $refreshStaffApiToken($staffRxTokenRefreshUrl);
                        if ($tokenOk && ($rawBearerToken !== '' || $bearerHeaderToken !== '')) {
                            $checkStaffHttp = $applyStaffApiAuth(
                                \Illuminate\Support\Facades\Http::acceptJson()->timeout(10),
                                (string) $bearerHeaderToken,
                                (string) $rawBearerToken
                            );

                            $checkStaffResponse = $checkStaffHttp->get($staffRxCheckStaffUrl, [
                                'staffID' => $currentStaffId,
                            ]);

                            $checkStaffPayload = $checkStaffResponse->json();
                            $staffRow = is_array($checkStaffPayload['data'] ?? null)
                                ? $checkStaffPayload['data']
                                : (is_array($checkStaffPayload) ? $checkStaffPayload : []);

                            $currentStaffJobCode = trim((string) ($staffRow['job_code'] ?? ''));
                            $currentStaffDepCode = trim((string) ($staffRow['dep_code'] ?? ''));

                            $preferredCodes = [
                                trim((string) ($staffRow['unit_code'] ?? '')),
                                trim((string) ($staffRow['job_code'] ?? '')),
                                trim((string) ($staffRow['dep_code'] ?? '')),
                            ];

                            foreach ($preferredCodes as $preferredCode) {
                                if ($preferredCode !== '' && isset($departmentCodeSet[$preferredCode])) {
                                    $defaultDepartmentCode = $preferredCode;
                                    break;
                                }
                            }
                        }
                    }

                    if ($staffRxTokenRefreshUrl !== '' && $staffRxListStaffUrl !== '') {
                        [$bearerHeaderToken, $rawBearerToken, $tokenOk] = $refreshStaffApiToken($staffRxTokenRefreshUrl);
                        if ($tokenOk && ($rawBearerToken !== '' || $bearerHeaderToken !== '')) {
                            $listHttp = $applyStaffApiAuth(
                                \Illuminate\Support\Facades\Http::acceptJson()->timeout(20),
                                (string) $bearerHeaderToken,
                                (string) $rawBearerToken
                            );
                            $listStaffResponse = $listHttp->get($staffRxListStaffUrl);
                            if ($listStaffResponse->successful()) {
                                $listBody = $listStaffResponse->json();
                                $listRows = is_array($listBody['data'] ?? null) ? $listBody['data'] : [];

                                if (is_array($listRows)) {
                                    $seen = [];
                                    foreach ($listRows as $row) {
                                        if (! is_array($row)) {
                                            continue;
                                        }
                                        $id = (int) ($row['id'] ?? 0);
                                        if ($id <= 0) {
                                            continue;
                                        }
                                        if (array_key_exists($id, $seen)) {
                                            continue;
                                        }
                                        $seen[$id] = true;

                                        $label = $buildStaffListLabel($row);
                                        if ($label === '') {
                                            continue;
                                        }

                                        if ($defaultApplicantStaffId === '' && $matchCurrentStaff($currentStaffId, $row)) {
                                            $defaultApplicantStaffId = (string) $id;
                                        }

                                        $staffListOptions[] = [
                                            'id' => (string) $id,
                                            'label' => $label,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $departmentOptions = [];
                    $departmentSearchOptions = [];
                    $departmentDocumentMap = [];
                    $departmentCodeSet = [];
                    $defaultDepartmentCode = '';
                    $staffListOptions = [];
                    $defaultApplicantStaffId = '';
                    $currentStaffJobCode = '';
                    $currentStaffDepCode = '';
                }

                $activeStaffIdMap = collect($staffListOptions ?? [])
                    ->map(fn ($staff) => trim((string) ($staff['id'] ?? '')))
                    ->filter(fn ($staffId) => $staffId !== '')
                    ->flip();
                $currentUserApplicantStaffId = trim((string) ($defaultApplicantStaffId ?? ''));
            @endphp

            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/70 shadow-md shadow-slate-200/60">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-white via-slate-50/80 to-emerald-50/40 px-4 py-4 sm:px-5">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                            <i class="fas fa-id-card text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h5 class="text-base font-semibold text-slate-900 sm:text-lg">รายการเบิกจ่าย</h5>
                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ $isReviewerApprovalMode ? 'แสดงรายการที่รอการอนุมัติจากผู้ตรวจสอบ' : 'แสดงรายการล่าสุดทั้งหมด' }}
                                <span id="withdraw-salary-list-count" class="font-semibold text-slate-700">{{ count($sampleRows) }}</span>
                                รายการ
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-3 sm:p-5">
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-4xl lg:flex-1 lg:grid-cols-4">
                        <select id="withdraw-filter-fiscal-year" class="rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition-all focus:-translate-y-0.5 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">ปีงบประมาณทั้งหมด</option>
                        </select>
                        <select id="withdraw-filter-month" class="rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition-all focus:-translate-y-0.5 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">ทุกเดือน</option>
                        </select>
                        <select id="withdraw-filter-status" class="rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition-all focus:-translate-y-0.5 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">ทุกสถานะ</option>
                        </select>
                        <select id="withdraw-filter-type" class="rounded-xl border border-slate-300/90 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition-all focus:-translate-y-0.5 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">ทุกประเภท</option>
                        </select>
                    </div>

                    <div class="relative lg:w-64">
                        <input id="withdraw-filter-search" type="text" placeholder="ค้นหา"
                            class="w-full rounded-xl border border-slate-300/90 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 transition-all focus:-translate-y-0.5 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-500">
                            <i class="fas fa-search" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>

                <div id="withdraw-general-table-body" class="grid grid-cols-1 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">กำลังโหลดรายการ...</div>
                </div>
                </div>
            </section>
        </div>
    </div>

    <x-modal name="withdraw-salary-type-modal" maxWidth="md" :static="true" variant="modern"
        title="เลือกประเภทเบิกจ่ายเงินเดือน/ค่าจ้าง" description="เลือกประเภทเพื่อเปิดแบบฟอร์มที่เหมาะสม"
        iconClass="fas fa-layer-group">
        <div class="space-y-3 p-1">
            <button type="button" data-salary-type-option="salary_civil_servant"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                เงินเดือนข้าราชการ
            </button>
            <button type="button" data-salary-type-option="salary_university_staff"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                เงินเดือนพนักงานมหาวิทยาลัย
            </button>
            <button type="button" data-salary-type-option="position_allowance"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                เงินประจำตำแหน่ง
            </button>
            <button type="button" data-salary-type-option="pts"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                เงิน พ.ต.ส.
            </button>
        </div>
        <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
            <button type="button" @click="$dispatch('close-modal', 'withdraw-salary-type-modal')"
                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">
                ปิด
            </button>
        </div>
    </x-modal>

    <x-modal name="withdraw-request-modal" maxWidth="3xl" :static="true" variant="modern"
        title="เบิกจ่ายเงินเดือน/ค่าจ้าง" description="กรอกรายละเอียดเบิกจ่ายตามแบบฟอร์ม"
        iconClass="fas fa-file-signature">
        <form id="withdraw-general-form" class="space-y-5" enctype="multipart/form-data">
            <input type="hidden" id="withdraw-salary-request-type" value="salary_civil_servant" />
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 text-sm text-emerald-800">
                <span class="font-semibold">ประเภทที่เลือก:</span>
                <span id="withdraw-salary-request-type-label">เงินเดือนข้าราชการ</span>
            </div>
            <input type="hidden" id="withdraw-record-id" value="" />
            <div id="withdraw-revision-note-box" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <div class="font-bold">รายละเอียดที่ต้องแก้ไข</div>
                <div id="withdraw-revision-note-text" class="mt-1 whitespace-pre-wrap">-</div>
            </div>
            {{-- <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                <div class="text-sm text-slate-600">
                    เอกสารเลขที่ <span class="font-semibold text-slate-900">WDR-2569-0001</span>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                    <i class="fas fa-hourglass-half text-[10px]" aria-hidden="true"></i>
                    สถานะ: ร่าง
                </span>
            </div> --}}

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ส่วนงาน</label>
                    <x-searchable-dropdown
                        id="withdraw-department-select"
                        placeholder="{{ count($departmentSearchOptions) > 0 ? 'พิมพ์เพื่อค้นหาส่วนงาน...' : 'ไม่พบข้อมูลส่วนงาน' }}"
                        :options="$departmentSearchOptions"
                        valueKey="code"
                        textKey="label"
                        :showClear="true"
                        clearTitle="ล้างส่วนงาน"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">ที่</label>
                    <input type="text" id="withdraw-document-number" placeholder="เลือกส่วนงานเพื่อเติมเลขที่หนังสือ"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">วันที่</label>
                    <x-date-picker
                        id="withdraw-date"
                        placeholder="เลือกวันที่"
                        :value="now()->format('Y-m-d')"
                        :withIcon="false"
                        inputClass="rounded-xl border-slate-300 text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                    />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">เรื่อง</label>
                    <input type="text" id="withdraw-subject" placeholder="ระบุหัวข้อเรื่องขออนุมัติเบิกจ่าย" value=""
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">เรียน</label>
                    <input type="text" id="withdraw-to" placeholder="ระบุผู้รับเรื่อง" value=""
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                </div>
                <div class="md:col-span-2" data-memo-block="paragraph1">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 1</label>
                    <textarea id="withdraw-paragraph-1" rows="4" placeholder="เนื้อหาย่อหน้าแรก" 
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
                </div>
                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-white" data-memo-block="items">
                <div class="border-b border-slate-200 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <h6 class="text-sm font-semibold text-slate-800">รายการเบิกจ่าย</h6>
                        <span id="withdraw-item-count-badge"
                            class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">0</span>
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500">กรอกงบประมาณ → หมวด → รายการ → จำนวนเงิน ในแต่ละรายการ</p>
                </div>
                <div class="space-y-3 p-4">
                    <div id="withdraw-item-empty"
                        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-10 text-center">
                        <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 ring-1 ring-slate-200">
                            <i class="fas fa-list-ul text-lg"></i>
                        </span>
                        <p class="text-sm font-semibold text-slate-700">ยังไม่มีรายการเบิกจ่าย</p>
                        <p class="mt-1 max-w-sm text-xs text-slate-500">กดปุ่ม “เพิ่มรายการ” เพื่อเริ่มกรอกข้อมูล</p>
                        <button type="button" data-action="withdraw-add-item-empty"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-100 transition hover:bg-sky-100">
                            <i class="fas fa-plus text-[10px]"></i>
                            เพิ่มรายการแรก
                        </button>
                    </div>
                    <div id="withdraw-item-rows" class="space-y-3"></div>
                    <div id="withdraw-item-add-footer" class="hidden">
                        <button type="button" data-action="withdraw-add-item-footer"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-sky-300 bg-sky-50/60 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:border-sky-400 hover:bg-sky-50">
                            <i class="fas fa-plus text-xs"></i>
                            เพิ่มรายการ
                        </button>
                    </div>
                    <div id="withdraw-item-summary"
                        class="flex items-center justify-between gap-3 rounded-xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50/90 to-teal-50/60 px-4 py-3">
                        <div class="flex items-center gap-2 text-sm font-semibold text-emerald-900">
                            <i class="fas fa-calculator text-emerald-600"></i>
                            <span>รวมจำนวนเงินทั้งหมด</span>
                        </div>
                        <div class="text-right">
                            <span id="withdraw-item-total" class="text-lg font-bold tabular-nums text-emerald-900">0.00</span>
                            <span class="ml-1 text-xs font-medium text-emerald-700">บาท</span>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="w-full md:col-span-2 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5" data-memo-block="budget">
                <div class="mb-3">
                    <label class="block text-sm font-semibold text-slate-800">สรุปงบประมาณ</label>
                    <p class="mt-0.5 text-xs text-slate-500">สรุปจากงบประมาณที่เลือกในแต่ละรายการเบิกจ่าย</p>
                </div>
                <div id="withdraw-budget-summary-card" class="hidden rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <div class="overflow-x-auto">
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
                            <tbody id="withdraw-budget-summary-body">
                                <tr>
                                    <td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="6">- ยังไม่ได้เลือกงบประมาณ -</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2" data-memo-block="paragraph2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 2</label>
                    <textarea id="withdraw-paragraph-2" rows="6" placeholder="เนื้อหาย่อหน้าถัดไป"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
                </div>
            </div>

            <div class="md:col-span-2" data-memo-block="paragraph3">
                <label class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 3</label>
                <textarea id="withdraw-paragraph-3" rows="2" placeholder="เนื้อหาย่อหน้าที่ 3"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
            </div>

            <div class="md:col-span-2" data-memo-block="paragraph4">
                <label class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 4</label>
                <textarea id="withdraw-paragraph-4" rows="2" placeholder="เนื้อหาย่อหน้าที่ 4"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">ผู้ขอเบิก</label>
                <x-searchable-dropdown
                    id="withdraw-applicant-staff"
                    placeholder="{{ count($staffListOptions) > 0 ? 'พิมพ์เพื่อค้นหาผู้ขอเบิก...' : 'ไม่พบรายชื่อพนักงาน' }}"
                    :options="$staffListOptions"
                    valueKey="id"
                    textKey="label"
                    :showClear="true"
                    clearTitle="ล้างผู้ขอเบิก"
                />
            </div>

            <div id="withdraw-extra-actors-section" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h6 class="text-sm font-semibold text-slate-800">ผู้ตรวจสอบ/อนุมัติ เพิ่มเติม</h6>
                    <button type="button" id="withdraw-add-actor-row"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 transition hover:bg-emerald-100">
                        <i class="fas fa-plus text-[10px]"></i>
                        เพิ่มผู้ตรวจสอบ/อนุมัติ เพิ่มเติม
                    </button>
                </div>
                <div id="withdraw-actor-rows" class="hidden space-y-3"></div>
                <div class="mt-2 text-xs text-slate-500">
                    สามารถเพิ่มผู้ตรวจสอบ/อนุมัติ เพิ่มเติมได้ตามต้องการ (ไม่เพิ่มก็ได้)
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-3">
                    <h6 class="text-sm font-semibold text-slate-800">เอกสารประกอบการเบิกจ่าย</h6>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">เลือกประเภทและแนบไฟล์ตามรายการ</span>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-slate-700">ประเภทไฟล์ (เลือกได้หลายประเภท)</label>
                        <div id="withdraw-document-types" class="grid grid-cols-1 gap-2"></div>
                    </div>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-3 py-2.5 text-xs leading-relaxed text-slate-600">
                        1) เลือกประเภทไฟล์<br />
                        2) ติ๊กเลือกรายการเอกสารที่มี<br />
                        3) แนบไฟล์ตามรายการที่เลือก (แต่ละรายการย่อยแนบได้หลายไฟล์)
                    </div>
                </div>

                <div id="withdraw-document-subitems-container" class="mt-4 hidden">
                    <label class="mb-2 block text-xs font-bold text-slate-700">รายการเอกสารย่อย</label>
                    <div id="withdraw-document-subitems" class="grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                </div>

                <div id="withdraw-document-uploads-container" class="mt-4 hidden">
                    <label class="mb-2 block text-xs font-bold text-slate-700">แนบไฟล์ตามรายการที่เลือก</label>
                    <div id="withdraw-document-uploads" class="space-y-3"></div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" @click="$dispatch('close-modal', 'withdraw-request-modal')"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">
                    <i class="fas fa-times mr-1"></i>
                    ยกเลิก
                </button>
                <button type="submit" id="withdraw-save-draft-button"
                    class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:-translate-y-0.5 hover:from-emerald-500 hover:to-teal-500">
                    <i class="far fa-save mr-1"></i>
                    บันทึกแบบร่าง
                </button>
            </div>
        </form>
    </x-modal>

    <x-confirm-modal
        name="withdraw-general-delete-confirm-modal"
        maxWidth="sm"
        :title="__('ยืนยันการลบรายการ?')"
        :message="__('คุณต้องการลบรายการเบิกจ่ายนี้หรือไม่')"
        confirmText="{{ __('ลบ') }}"
        cancelText="{{ __('ยกเลิก') }}"
        confirmVariant="danger"
        confirmClick="window.__withdrawGeneralConfirmDelete()"
    />
    <x-modal name="withdraw-general-proceed-confirm-modal" maxWidth="sm" :static="true">
        <div class="p-6">
            <h3 id="withdraw-proceed-confirm-title" class="text-lg font-bold text-slate-800">ยืนยันดำเนินการเบิกจ่าย?</h3>
            <p id="withdraw-proceed-confirm-description" class="mt-1 text-sm text-slate-500">โปรดตรวจสอบรายละเอียดก่อนยืนยันดำเนินการ</p>
            <div id="withdraw-proceed-detail-box" class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <div><span id="withdraw-proceed-detail-code-label" class="font-semibold text-slate-800">เลขที่:</span> <span id="withdraw-proceed-detail-code">-</span></div>
                <div id="withdraw-proceed-detail-code-2-row" class="hidden mt-1"><span class="font-semibold text-slate-800">เลขที่ (ฉบับที่ 2):</span> <span id="withdraw-proceed-detail-code-2">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">วันที่:</span> <span id="withdraw-proceed-detail-date">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="withdraw-proceed-detail-subject">-</span></div>
                <div><span class="font-semibold text-slate-800">ผู้ขอเบิก:</span> <span id="withdraw-proceed-detail-applicant">-</span></div>
                <div><span class="font-semibold text-slate-800">จำนวนเงิน:</span> <span id="withdraw-proceed-detail-amount">-</span> บาท</div>  
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-general-proceed-confirm-modal')"
                    class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    ยกเลิก
                </button>
                <button type="button"
                    @click="window.__withdrawGeneralConfirmProceed()"
                    id="withdraw-proceed-confirm-submit-button"
                    class="inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-blue-600 to-green-500">
                    ยืนยัน
                </button>
            </div>
        </div>
    </x-modal>
    <x-modal name="withdraw-salary-memo-modal" maxWidth="4xl" :static="true">
        <div class="p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 id="withdraw-salary-memo-title" class="text-lg font-bold text-slate-800">บันทึกข้อความ</h3>
                    <p id="withdraw-salary-memo-subject" class="mt-1 text-sm text-slate-500">บันทึกข้อความขออนุมัติเบิกจ่าย</p>
                </div>
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-salary-memo-modal')"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                    aria-label="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="withdraw-salary-memo-body" class="mt-4 max-h-[75vh] space-y-4 overflow-y-auto"></div>
            <div class="mt-6 flex justify-end">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-salary-memo-modal')"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    ปิด
                </button>
            </div>
        </div>
    </x-modal>
    <x-modal name="withdraw-salary-esign-docs-modal" maxWidth="4xl" :static="true">
        <div class="p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">เอกสารลงนาม</h3>
                    <p id="withdraw-salary-esign-subject" class="mt-1 text-sm text-slate-500">เอกสารลงนาม e-Sign ที่แนบมา</p>
                </div>
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-salary-esign-docs-modal')"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                    aria-label="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="withdraw-salary-esign-loading" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                <i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลดเอกสาร...
            </div>
            <div id="withdraw-salary-esign-empty" class="mt-4 hidden rounded-xl border border-dashed border-violet-200 bg-violet-50/40 px-4 py-8 text-center text-sm text-slate-500">
                ยังไม่มีเอกสารลงนาม e-Sign ของรายการนี้
            </div>
            <div id="withdraw-salary-esign-body" class="mt-4 max-h-[75vh] space-y-4 overflow-y-auto"></div>
            <div class="mt-6 flex justify-end">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-salary-esign-docs-modal')"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    ปิด
                </button>
            </div>
        </div>
    </x-modal>
    <x-modal name="withdraw-salary-one-planning-confirm-modal" maxWidth="4xl" :static="true" variant="modern"
        :title="__('ส่งรายการไป One Planning')">
        <div class="space-y-4 p-1 sm:p-2">
            <p class="text-sm text-slate-600">ตรวจสอบข้อมูลด้านล่างก่อนยืนยัน ส่งงบประมาณละ 1 รายการไป One Planning</p>
            <div id="withdraw-one-planning-summary" class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">เลขที่:</span> <span id="withdraw-one-planning-code">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="withdraw-one-planning-subject">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">ผู้ขอเบิก:</span> <span id="withdraw-one-planning-applicant">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">จำนวนเงินรวม:</span> <span id="withdraw-one-planning-amount">-</span> บาท</div>
                <div class="mt-1"><span class="font-semibold text-slate-800">รูปแบบ ref_id:</span> <span id="withdraw-one-planning-ref-id">-</span></div>
                <div class="mt-1 text-xs text-slate-500">แต่ละงบจะได้ ref_id คนละค่า รูปแบบ salary_[id]_[ลำดับงบ]</div>
                <div class="mt-1"><span class="font-semibold text-slate-800">ref_status_name:</span> <span id="withdraw-one-planning-ref-status">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">ส่ง API ล่าสุด:</span> <span id="withdraw-one-planning-last-sent">-</span></div>
            </div>
            <div id="withdraw-one-planning-change-banner" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">
                พบข้อมูลที่เปลี่ยนแปลงจากรอบที่เคยส่ง API ไปแล้ว กรุณาตรวจสอบคอลัมน์เปรียบเทียบ
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="w-full table-fixed divide-y divide-slate-200 text-sm">
                    <colgroup>
                        <col class="w-[16%]">
                        <col class="w-[18%]">
                        <col class="w-[11%]">
                        <col class="w-[16%]">
                        <col class="w-[12%]">
                        <col class="w-[16%]">
                        <col class="w-[11%]">
                    </colgroup>
                    <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-2 py-2">รหัสงบ</th>
                            <th class="px-2 py-2">ref_id</th>
                            <th class="px-2 py-2 text-right">จำนวนเงิน</th>
                            <th class="px-2 py-2">หมวด</th>
                            <th class="px-2 py-2">ส่งล่าสุด</th>
                            <th class="px-2 py-2">เปรียบเทียบ</th>
                            <th class="px-2 py-2 text-center">Response</th>
                        </tr>
                    </thead>
                    <tbody id="withdraw-one-planning-payload-body" class="divide-y divide-slate-100 bg-white text-slate-700">
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-slate-400">กำลังโหลดข้อมูล...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end gap-3 pt-1">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-salary-one-planning-confirm-modal')"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    ยกเลิก
                </button>
                <button type="button"
                    id="withdraw-one-planning-confirm-submit"
                    @click="window.__withdrawSalaryConfirmOnePlanningSend?.()"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-gradient-to-tl from-cyan-700 to-sky-500 px-5 text-xs font-bold uppercase text-white">
                    ยืนยันส่ง One Planning
                </button>
            </div>
        </div>
    </x-modal>
    <x-modal name="withdraw-salary-one-planning-response-modal" maxWidth="3xl" :static="true" variant="modern"
        :title="__('Response จากระบบปลายทาง (One Planning)')"
        style="z-index: 100050;">
        <div class="space-y-3 p-1 sm:p-2">
            <div id="withdraw-one-planning-response-summary" class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">รหัสงบประมาณ:</span> <span id="withdraw-one-planning-response-budget-code">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">จำนวนครั้งที่ส่ง:</span> <span id="withdraw-one-planning-response-count">0</span></div>
            </div>
            <div id="withdraw-one-planning-response-list" class="max-h-[28rem] space-y-3 overflow-y-auto pr-1">
                <div class="rounded-xl border border-dashed border-slate-200 px-3 py-6 text-center text-sm text-slate-400">
                    ยังไม่มีข้อมูล Response
                </div>
            </div>
            <div class="flex justify-end pt-1">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-salary-one-planning-response-modal')"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    ปิด
                </button>
            </div>
        </div>
    </x-modal>
    @include('withdraw.salary.partials.detail-modal')
    <x-confirm-modal
        name="withdraw-item-row-delete-confirm-modal"
        maxWidth="sm"
        :title="__('ยืนยันการลบรายการเบิกจ่าย?')"
        :message="__('คุณต้องการลบรายการเบิกจ่ายแถวนี้หรือไม่')"
        confirmText="{{ __('ลบ') }}"
        cancelText="{{ __('ยกเลิก') }}"
        confirmVariant="danger"
        confirmClick="window.__withdrawGeneralConfirmDeleteItemRow()"
    />

    @push('scripts')
        <script>
            document.addEventListener('alpine:initialized', () => {
                const departmentSelect = document.getElementById('withdraw-department-select');
                const documentNumberInput = document.getElementById('withdraw-document-number');
                const departmentDocumentMap = @json($departmentDocumentMap);
                const bookNumberByType = @json($bookNumberByType);
                const generalBookNumberSuffix = @json($generalBookNumberSuffix);
                const defaultDepartmentCode = @json($defaultDepartmentCode);
                const itemRowsContainer = document.getElementById('withdraw-item-rows');
                const itemTotalElement = document.getElementById('withdraw-item-total');
                const withdrawOptionsApi = '/withdraw/api/salary/pay-list-options';
                const expenseChartOptionsApi = @json(url('/withdraw/api/salary/expense-chart-options'));
                const budgetSourceOptionsApi = @json(url('/withdraw/api/salary/budget-source-options'));
                const documentOptionsApi = @json(url('/withdraw/api/salary/document-options'));
                const saveDraftApi = @json(url('/withdraw/api/salary/save-draft'));
                const generalDetailApiBase = @json(url('/withdraw/api/salary'));
                const generalListApi = @json($generalListApiUrl ?? url('/withdraw/api/salary/list'));
                const approvalPendingListApi = @json(url('/withdraw/api/salary/list?scope=reviewer-pending'));
                const documentReviewListApi = @json(url('/withdraw/api/salary/list?scope=document-review'));
                const generalNextRunningNoApi = @json(url('/withdraw/api/salary/next-running-no'));
                const generalDeleteApi = @json(url('/withdraw/api/salary/delete'));
                const generalProceedApi = @json(url('/withdraw/api/salary/proceed'));
                const onePlanningPreviewApiBase = @json(url('/withdraw/api/salary'));
                const isReviewerApprovalMode = @json($isReviewerApprovalMode ?? false);
                const isAdminUser = @json($isAdminUser ?? false);
                const applicantStaffSelect = document.getElementById('withdraw-applicant-staff');
                const addActorRowButton = document.getElementById('withdraw-add-actor-row');
                const actorRowsContainer = document.getElementById('withdraw-actor-rows');
                const extraActorsSection = document.getElementById('withdraw-extra-actors-section');

                const setExtraActorsSectionVisible = (visible) => {
                    if (!extraActorsSection) return;
                    extraActorsSection.classList.toggle('hidden', !visible);
                    if (!visible && actorRowsContainer) {
                        actorRowsContainer.innerHTML = '';
                        actorRowsContainer.classList.add('hidden');
                    }
                };
                const withdrawGeneralForm = document.getElementById('withdraw-general-form');
                const openCreateModalButton = document.getElementById('withdraw-open-create-modal');
                const salaryTypeInput = document.getElementById('withdraw-salary-request-type');
                const salaryTypeLabel = document.getElementById('withdraw-salary-request-type-label');
                const withdrawSaveDraftButton = document.getElementById('withdraw-save-draft-button');
                const withdrawRecordIdInput = document.getElementById('withdraw-record-id');
                const withdrawSubjectInput = document.getElementById('withdraw-subject');
                const withdrawToInput = document.getElementById('withdraw-to');
                const withdrawParagraph1Input = document.getElementById('withdraw-paragraph-1');
                const withdrawParagraph2Input = document.getElementById('withdraw-paragraph-2');
                const withdrawParagraph3Input = document.getElementById('withdraw-paragraph-3');
                const withdrawParagraph4Input = document.getElementById('withdraw-paragraph-4');
                const withdrawDateInput = document.getElementById('withdraw-date');
                const MONTH_PERIOD_PLACEHOLDER_RE = /ประจำเดือน[\\.…]+/gu;
                const MONTH_PERIOD_VALUE_RE = /ประจำเดือน\s+[ก-๙]+\s+\d{4}/gu;

                const formatMonthPeriodLabel = (isoDateStr) => {
                    const match = String(isoDateStr || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (!match) return '';
                    const year = Number(match[1]);
                    const monthIndex = Number(match[2]) - 1;
                    if (!Number.isFinite(year) || monthIndex < 0 || monthIndex > 11) return '';
                    return `ประจำเดือน ${THAI_MONTH_NAMES[monthIndex]} ${year + 543}`;
                };

                const applyMonthPeriodToText = (text, monthPeriodLabel) => {
                    const source = String(text || '');
                    const label = String(monthPeriodLabel || '').trim();
                    if (source === '') return '';
                    if (label === '') {
                        return source.replace(MONTH_PERIOD_VALUE_RE, 'ประจำเดือน...');
                    }
                    if (MONTH_PERIOD_PLACEHOLDER_RE.test(source)) {
                        MONTH_PERIOD_PLACEHOLDER_RE.lastIndex = 0;
                        return source.replace(MONTH_PERIOD_PLACEHOLDER_RE, label);
                    }
                    MONTH_PERIOD_VALUE_RE.lastIndex = 0;
                    return source.replace(MONTH_PERIOD_VALUE_RE, label);
                };

                const syncMemoMonthPeriodFields = () => {
                    const monthPeriodLabel = formatMonthPeriodLabel(String(withdrawDateInput?.value || '').trim());
                    if (withdrawSubjectInput) {
                        withdrawSubjectInput.value = applyMonthPeriodToText(withdrawSubjectInput.value, monthPeriodLabel);
                    }
                    if (withdrawParagraph1Input) {
                        withdrawParagraph1Input.value = applyMonthPeriodToText(withdrawParagraph1Input.value, monthPeriodLabel);
                    }
                };

                const getTodayIsoDate = () => {
                    const today = new Date();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${today.getFullYear()}-${month}-${day}`;
                };

                const setWithdrawDateValue = (isoDate, triggerSync = true) => {
                    if (!withdrawDateInput) return;
                    const iso = String(isoDate || '').trim();
                    const fp = withdrawDateInput._flatpickr;
                    if (fp) {
                        if (iso === '') {
                            fp.clear();
                        } else {
                            fp.setDate(iso, true);
                        }
                    } else {
                        withdrawDateInput.value = iso;
                        withdrawDateInput.dispatchEvent(new Event('input', { bubbles: true }));
                        withdrawDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (triggerSync) syncMemoMonthPeriodFields();
                };

                const setWithdrawDateToToday = () => setWithdrawDateValue(getTodayIsoDate());

                withdrawDateInput?.addEventListener('change', syncMemoMonthPeriodFields);
                withdrawDateInput?.addEventListener('input', syncMemoMonthPeriodFields);

                const withdrawBudgetSummaryCard = document.getElementById('withdraw-budget-summary-card');
                const withdrawBudgetSummaryBody = document.getElementById('withdraw-budget-summary-body');
                const budgetSourceOptionMap = new Map();
                const withdrawGeneralTableBody = document.getElementById('withdraw-general-table-body');
                const defaultApplicantStaffId = @json($defaultApplicantStaffId);
                const staffSearchOptions = @json($staffListOptions ?? []);
                const documentTypeWrapper = document.getElementById('withdraw-document-types');
                const documentSubitemsContainer = document.getElementById('withdraw-document-subitems-container');
                const documentSubitemsElement = document.getElementById('withdraw-document-subitems');
                const documentUploadsContainer = document.getElementById('withdraw-document-uploads-container');
                const documentUploadsElement = document.getElementById('withdraw-document-uploads');
                const proceedDetailCode = document.getElementById('withdraw-proceed-detail-code');
                const proceedDetailCodeLabel = document.getElementById('withdraw-proceed-detail-code-label');
                const proceedDetailCode2Row = document.getElementById('withdraw-proceed-detail-code-2-row');
                const proceedDetailCode2 = document.getElementById('withdraw-proceed-detail-code-2');
                const proceedDetailDate = document.getElementById('withdraw-proceed-detail-date');
                const proceedDetailApplicant = document.getElementById('withdraw-proceed-detail-applicant');
                const proceedDetailAmount = document.getElementById('withdraw-proceed-detail-amount');
                const proceedDetailSubject = document.getElementById('withdraw-proceed-detail-subject');
                const revisionNoteBox = document.getElementById('withdraw-revision-note-box');
                const revisionNoteText = document.getElementById('withdraw-revision-note-text');
                const proceedConfirmTitle = document.getElementById('withdraw-proceed-confirm-title');
                const proceedConfirmDescription = document.getElementById('withdraw-proceed-confirm-description');
                const proceedConfirmSubmitButton = document.getElementById('withdraw-proceed-confirm-submit-button');
                const filterFiscalYearSelect = document.getElementById('withdraw-filter-fiscal-year');
                const filterMonthSelect = document.getElementById('withdraw-filter-month');
                const filterStatusSelect = document.getElementById('withdraw-filter-status');
                const filterTypeSelect = document.getElementById('withdraw-filter-type');
                const filterSearchInput = document.getElementById('withdraw-filter-search');
                const THAI_MONTH_NAMES = [
                    'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
                ];
                const getCurrentFiscalYearBe = () => {
                    const now = new Date();
                    const month = now.getMonth() + 1;
                    const ceYear = now.getFullYear();
                    const fiscalYearCe = month >= 10 ? ceYear + 1 : ceYear;

                    return fiscalYearCe + 543;
                };
                let fiscalYearFilterDefaulted = false;

                if (filterMonthSelect) {
                    filterMonthSelect.innerHTML = [
                        '<option value="">ทุกเดือน</option>',
                        ...THAI_MONTH_NAMES.map(
                            (label, index) => `<option value="${index + 1}">${label}</option>`,
                        ),
                    ].join('');
                }

                let withdrawDocumentTypeMap = {};
                let withdrawDocumentTypeLabels = {};
                let pendingDeleteId = 0;
                let pendingProceedId = 0;
                let pendingProceedAction = 'proceed';
                let isRevisionEditMode = false;
                let currentUploadedFilesByKey = new Map();
                const pendingRemoveFileIds = new Set();
                let pendingDeleteItemRowElement = null;
                let generalTableRows = @json($sampleRows->values());
                const salaryTypeOptions = {
                    salary_civil_servant: 'เงินเดือนข้าราชการ',
                    salary_university_staff: 'เงินเดือนพนักงานมหาวิทยาลัย',
                    position_allowance: 'เงินประจำตำแหน่ง',
                    pts: 'เงิน พ.ต.ส.',
                };
                const normalizeSalaryRequestTypeKey = (requestType) => {
                    const normalized = String(requestType || '').trim();
                    if (normalized === 'salary') {
                        return 'salary_civil_servant';
                    }
                    return salaryTypeOptions[normalized] ? normalized : 'salary_civil_servant';
                };
                const salaryRequestTypeLabel = (requestType) => {
                    const key = normalizeSalaryRequestTypeKey(requestType);
                    return salaryTypeOptions[key] || salaryTypeOptions.salary_civil_servant;
                };

                const statusClassMap = {
                    'ร่าง': 'bg-slate-100 text-slate-700 ring-slate-200',
                    'รอตรวจสอบ/อนุมัติ': 'bg-amber-100 text-amber-700 ring-amber-200',
                    'รอการเงินตรวจสอบเอกสาร': 'bg-cyan-100 text-cyan-700 ring-cyan-200',
                    'รอแก้ไข': 'bg-rose-100 text-rose-700 ring-rose-200',
                    'รออนุมัติ': 'bg-violet-100 text-violet-700 ring-violet-200',
                    'ยกเลิกรายการ': 'bg-slate-200 text-slate-700 ring-slate-300',
                    'อนุมัติ': 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                };

                const escapeHtml = (unsafe) => String(unsafe ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[char]));

                const normalizeTypeText = (value) => String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
                const withdrawFormTypeLabel = 'เบิกจ่ายเงินเดือน/ค่าจ้าง';
                const mergeDocumentNumber = (baseDocumentNumber, appendSuffix) => {
                    const base = String(baseDocumentNumber || '').trim();
                    const suffix = String(appendSuffix || '').trim();
                    if (base === '') return suffix;
                    if (suffix === '') return base;
                    if (base.endsWith(suffix)) return base;
                    return `${base}${suffix}`;
                };
                const resolveBookNumberSuffixByFormType = () => {
                    const entries = Object.entries(bookNumberByType || {})
                        .map(([type, value]) => [String(type || '').trim(), String(value || '').trim()])
                        .filter(([type, value]) => type !== '' && value !== '');
                    if (entries.length === 0) {
                        return String(generalBookNumberSuffix || '').trim();
                    }

                    const normalizedFormType = normalizeTypeText(withdrawFormTypeLabel);
                    const exactMatched = entries.find(([type]) => normalizeTypeText(type) === normalizedFormType);
                    if (exactMatched) {
                        return exactMatched[1];
                    }

                    const partialMatched = entries.find(([type]) => {
                        const normalizedType = normalizeTypeText(type);
                        return normalizedType.includes(normalizedFormType) || normalizedFormType.includes(normalizedType);
                    });
                    if (partialMatched) {
                        return partialMatched[1];
                    }

                    return String(generalBookNumberSuffix || '').trim();
                };
                const resolveProceedDocumentNumberPrefix = (detail) => {
                    const storedBase = String(detail?.document_number || '').trim();
                    if (storedBase !== '') {
                        return storedBase;
                    }
                    if (documentNumberInput) {
                        return String(documentNumberInput.value || '').trim();
                    }
                    return '';
                };
                const resetProceedDocumentNumberPreview = () => {
                    if (proceedDetailCodeLabel) {
                        proceedDetailCodeLabel.textContent = 'เลขที่:';
                    }
                    if (proceedDetailCode2Row) {
                        proceedDetailCode2Row.classList.add('hidden');
                    }
                    if (proceedDetailCode2) {
                        proceedDetailCode2.textContent = '-';
                    }
                };
                const salaryPtsUsesDualDocumentNumbers = (detail) => String(detail?.salary_request_type || '').trim() === 'pts';
                const setProceedDocumentNumberPreview = (detail, codePrefix, nextRunningNo) => {
                    const isPtsRequest = salaryPtsUsesDualDocumentNumbers(detail);
                    if (proceedDetailCodeLabel) {
                        proceedDetailCodeLabel.textContent = isPtsRequest ? 'เลขที่ (ฉบับที่ 1):' : 'เลขที่:';
                    }
                    if (proceedDetailCode && nextRunningNo > 0 && codePrefix !== '') {
                        proceedDetailCode.textContent = `${codePrefix}${nextRunningNo}`;
                    }
                    if (isPtsRequest && proceedDetailCode2Row && proceedDetailCode2 && nextRunningNo > 0 && codePrefix !== '') {
                        proceedDetailCode2Row.classList.remove('hidden');
                        proceedDetailCode2.textContent = `${codePrefix}${nextRunningNo + 1}`;
                        return;
                    }
                    if (proceedDetailCode2Row) {
                        proceedDetailCode2Row.classList.add('hidden');
                    }
                    if (proceedDetailCode2) {
                        proceedDetailCode2.textContent = '-';
                    }
                };

                let syncDocumentNumber = () => {};
                if (departmentSelect && documentNumberInput) {
                    syncDocumentNumber = () => {
                        const selectedCode = String(departmentSelect.value || '').trim();
                        const departmentDocumentNumber = selectedCode !== '' ? (departmentDocumentMap[selectedCode] || '') : '';
                        const mergedDocumentNumber = mergeDocumentNumber(departmentDocumentNumber, resolveBookNumberSuffixByFormType());
                        documentNumberInput.value = mergedDocumentNumber;
                    };

                    departmentSelect.addEventListener('change', syncDocumentNumber);

                    if (defaultDepartmentCode && departmentSelect.value !== defaultDepartmentCode) {
                        departmentSelect.value = defaultDepartmentCode;
                        departmentSelect.dispatchEvent(new Event('input', { bubbles: true }));
                        departmentSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    syncDocumentNumber();
                }

                if (applicantStaffSelect && defaultApplicantStaffId && applicantStaffSelect.value !== defaultApplicantStaffId) {
                    applicantStaffSelect.value = defaultApplicantStaffId;
                    applicantStaffSelect.dispatchEvent(new Event('input', { bubbles: true }));
                    applicantStaffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (!itemRowsContainer || !itemTotalElement) return;

                const categoryOptions = [];
                const expenseChartRows = [];
                let budgetSourceOptions = [];
                const expenseSubitemsByCategory = new Map();
                const payListNameByCode = new Map();
                const payListMetaByPayCode = new Map();
                let itemRowCounter = 0;
                let actorRowCounter = 0;
                let payListLoadStatus = 'idle';

                const actorStatusOptions = [
                    { value: 'พิจารณา', label: 'พิจารณา' },
                    { value: 'อนุมัติ', label: 'อนุมัติ' },
                    { value: 'เห็นชอบ', label: 'เห็นชอบ' },
                ];
                const currentUserApplicantStaffId = String(defaultApplicantStaffId || '').trim();
                const activeApplicantStaffIdSet = new Set(
                    (staffSearchOptions || [])
                    .map((staff) => String(staff?.id || '').trim())
                    .filter((staffId) => staffId !== '')
                );
                const canCurrentUserViewGeneralRow = (row) => {
                    if (isAdminUser) return true;
                    const applicantStaffId = String(row?.applicant_staff_id || '').trim();
                    if (applicantStaffId === '') return false;
                    if (!activeApplicantStaffIdSet.has(applicantStaffId)) return false;
                    if (currentUserApplicantStaffId === '') return false;
                    return applicantStaffId === currentUserApplicantStaffId;
                };

                const normalizePayListRows = (rows) => {
                    return (rows || []).map((item) => {
                        const code = String(item.budget_pay_code ?? item.code ?? '').trim();
                        const name = String(item.budget_pay_name ?? item.name ?? '').trim();
                        const status = Number(item.budget_pay_status ?? item.status ?? 0);
                        const categoryPayCode = Number(item.budget_category_pay_code ?? item.category_pay_code ?? 0);
                        const categoryPayName = String(item.budget_category_pay_name ?? item.category_pay_name ?? '').trim();
                        return {
                            code,
                            name,
                            status,
                            categoryPayCode,
                            categoryPayName,
                        };
                    }).filter((item) => {
                        if (item.code === '' || item.name === '') return false;
                        if (item.status > 0 && ![1].includes(item.status)) return false;
                        return true;
                    });
                };

                const fetchJson = async (url) => {
                    try {
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const text = await response.text();
                        return JSON.parse(text);
                    } catch (error) {
                        return null;
                    }
                };

                const getPayListDropdownStatusMessage = () => {
                    if (payListLoadStatus === 'loading') return 'กำลังโหลดรายการ...';
                    if (payListLoadStatus === 'error') return 'โหลดไม่สำเร็จ กรุณาลองใหม่';
                    return '';
                };
                const resolveCategoryOptionCode = (rawValue) => {
                    const input = String(rawValue || '').trim();
                    if (input === '') return '';
                    const exact = categoryOptions.find((option) => String(option.code || '').trim() === input);
                    if (exact) return String(exact.code || '').trim();
                    const byName = categoryOptions.find((option) => {
                        const optionName = String(option.name || option.label || '').trim();
                        return optionName !== '' && optionName === input;
                    });
                    if (byName) return String(byName.code || '').trim();
                    const numericInput = Number(input);
                    if (Number.isNaN(numericInput)) return input;
                    const numericMatched = categoryOptions.find((option) => Number(String(option.code || '').trim()) === numericInput);
                    return numericMatched ? String(numericMatched.code || '').trim() : input;
                };

                const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

                const postJson = async (url, body) => {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf(),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(body || {}),
                    });
                    return response.json();
                };

                const withdrawSalaryListCount = document.getElementById('withdraw-salary-list-count');
                const findSalaryListRowById = (id) => {
                    const numericId = Number(id || 0);
                    if (numericId <= 0) return null;
                    return (Array.isArray(generalTableRows) ? generalTableRows : []).find((item) => Number(item.id || 0) === numericId) || null;
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
                                        <span class="mt-1 line-clamp-2 min-h-[2rem] text-[11px] font-bold leading-4 ${textClass}">${escapeHtml(step.name)}</span>
                                        <span class="mt-0.5 rounded-full px-2 py-0.5 text-[10px] font-semibold ${subClass}">${subText}</span>
                                        ${approvedAtText !== '' ? `<span class="mt-0.5 text-[10px] font-medium leading-4 text-emerald-700">${escapeHtml(approvedAtText)}</span>` : ''}
                                    </div>`;
                                }).join('')}
                            </div>
                        </div>
                    </div>`;
                };

                const renderGeneralTableRows = (rows) => {
                    if (!withdrawGeneralTableBody) return;
                    const safeRows = Array.isArray(rows) ? rows : [];
                    if (withdrawSalaryListCount) {
                        withdrawSalaryListCount.textContent = String(safeRows.length);
                    }
                    if (safeRows.length === 0) {
                        withdrawGeneralTableBody.innerHTML = '<div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">ไม่พบข้อมูล</div>';
                        if (typeof window.releaseWithdrawMainScrollbar === 'function') {
                            window.releaseWithdrawMainScrollbar();
                        }
                        return;
                    }

                    withdrawGeneralTableBody.innerHTML = safeRows.map((row) => {
                        const status = String(row.status || '-');
                        const statusClass = statusClassMap[status] || 'bg-slate-100 text-slate-600 ring-slate-200';
                        const amount = Number(row.amount || 0).toLocaleString('th-TH');
                        const id = Number(row.id || 0);
                        const subject = String(row.subject || '-');
                        const manager = String(row.manager || '-');
                        const code = String(row.code || '-');
                        const date = String(row.date || '-');
                        const type = String(row.type || '-');
                        const rawStatus = String(row.raw_status || '').trim();
                        const subjectHtml = subject !== '-'
                            ? `<a href="javascript:void(0)" data-action="view" data-id="${id}" class="line-clamp-2 font-extrabold text-slate-900 underline decoration-slate-300 underline-offset-4 hover:text-emerald-600 hover:decoration-emerald-300">${escapeHtml(subject)}</a>`
                            : `<span class="text-slate-400">-</span>`;
                        const canManageDraft = !isReviewerApprovalMode && rawStatus === 'saved';
                        const canCancelProceed = !isReviewerApprovalMode && ['pending_primary_review', 'processing', 'pending_additional_review', 'pending_finance_review'].includes(rawStatus);
                        const canEditRevision = !isReviewerApprovalMode && rawStatus === 'pending_revision';
                        const isApprovedRow = rawStatus === 'approved' || status === 'อนุมัติ';
                        const canOpenMemoPdf = isApprovedRow;
                        const editButton = canManageDraft ? `<button type="button" data-action="edit" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"><i class="fas fa-pen-to-square text-[10px]"></i>แก้ไข</button>` : '';
                        const deleteButton = canManageDraft ? `<button type="button" data-action="delete" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"><i class="fas fa-trash text-[10px]"></i>ลบ</button>` : '';
                        const proceedButton = canManageDraft ? `<button type="button" data-action="proceed" data-id="${id}" data-proceed-action="proceed" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"><i class="fas fa-circle-play text-[10px]"></i>ดำเนินการเบิกจ่าย</button>` : '';
                        const cancelButton = canCancelProceed ? `<button type="button" data-action="proceed" data-id="${id}" data-proceed-action="cancel" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"><i class="fas fa-ban text-[10px]"></i>ยกเลิกรายการ</button>` : '';
                        const revisionEditButton = canEditRevision ? `<button type="button" data-action="edit-revision" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"><i class="fas fa-pen-to-square text-[10px]"></i>แก้ไขรายการ</button>` : '';
                        const isPtsSalaryRow = normalizeSalaryRequestTypeKey(row.salary_request_type) === 'pts';
                        const memoPdfButton = canOpenMemoPdf
                            ? `<button type="button" data-action="open-memo-pdf" data-id="${id}"${isPtsSalaryRow ? ' data-memo-copy="1"' : ''} class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"><i class="fas fa-file-pdf text-[10px]"></i>บันทึกข้อความ</button>`
                            : '';
                        const memoPdfPtsExportButton = canOpenMemoPdf && isPtsSalaryRow
                            ? `<button type="button" data-action="open-memo-pdf" data-id="${id}" data-memo-copy="2" class="inline-flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-100"><i class="fas fa-file-export text-[10px]"></i>บันทึกข้อความ (ส่งออก)</button>`
                            : '';
                        const financeEsignCount = Number(row.finance_esign_count || 0);
                        const esignDocsButton = financeEsignCount > 0
                            ? `<button type="button" data-action="open-esign-docs" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-100"><i class="fas fa-file-signature text-[10px]"></i>เอกสารลงนาม</button>`
                            : '';
                        const canSendOnePlanning = !isReviewerApprovalMode && rawStatus !== 'cancelled' && rawStatus !== '';
                        const onePlanningButton = canSendOnePlanning
                            ? `<button type="button" data-action="one-planning" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-cyan-200 bg-cyan-50 px-2.5 py-1.5 text-xs font-semibold text-cyan-800 transition hover:bg-cyan-100"><i class="fas fa-paper-plane text-[10px]"></i>ส่งรายการไป One Planning</button>`
                            : '';
                        const viewButton = `<button type="button" data-action="view" data-id="${id}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50"><i class="fas fa-eye text-[11px]"></i>ดูรายละเอียด</button>`;
                        return `<article class="group relative w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-md shadow-slate-200/50 ring-1 ring-white/80 transition-shadow duration-300 hover:border-emerald-200/80 hover:shadow-xl hover:shadow-emerald-100/40">
                            <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-emerald-300/20 blur-2xl"></div>
                            <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-emerald-200/60 to-transparent"></div>
                            <div class="relative flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100/90 px-2.5 py-1 text-[10px] font-bold tracking-wide text-slate-600 ring-1 ring-slate-200/80">
                                        <i class="fas fa-hashtag text-[10px]"></i>
                                        เลขที่ ${escapeHtml(code)}
                                    </div>
                                    <h6 class="mt-2 text-[15px] font-extrabold leading-6 text-slate-900">${subjectHtml}</h6>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-100">${escapeHtml(type)}</span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ${statusClass}">${escapeHtml(status)}</span>
                                </div>
                            </div>
                            <div class="relative mt-3 flex w-full flex-col gap-3 lg:flex-row lg:items-stretch">
                                <div class="grid w-full grid-cols-1 gap-3 rounded-xl border border-slate-200/80 bg-gradient-to-br from-slate-50/70 to-white p-3 text-sm sm:grid-cols-3 lg:max-w-none lg:flex-[0.9]">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">วันที่เบิกจ่าย</div>
                                        <div class="mt-1 font-semibold text-slate-800">${escapeHtml(date)}</div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ผู้ขอเบิก</div>
                                        <div class="mt-1 truncate font-semibold text-slate-800">${escapeHtml(manager)}</div>
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
                                ${viewButton}
                                ${editButton}
                                ${deleteButton}
                                ${proceedButton}
                                ${cancelButton}
                                ${revisionEditButton}
                                ${memoPdfButton}
                                ${memoPdfPtsExportButton}
                                ${esignDocsButton}
                                ${onePlanningButton}
                            </div>
                        </article>`;
                    }).join('');
                    if (typeof window.releaseWithdrawMainScrollbar === 'function') {
                        window.releaseWithdrawMainScrollbar();
                    }
                    requestAnimationFrame(() => {
                        void (withdrawGeneralTableBody && withdrawGeneralTableBody.offsetHeight);
                        window.dispatchEvent(new Event('resize'));
                    });
                };

                const salaryPdfCanEmbed = () => {
                    if (typeof window.matchMedia !== 'function') return true;
                    return window.matchMedia('(min-width: 1024px)').matches;
                };
                const openMemoPdfById = async (id, memoCopy = 0) => {
                    const recordId = Number(id || 0);
                    if (recordId <= 0) return;
                    const copy = Number(memoCopy || 0);
                    let targetUrl = `/withdraw/salary/${recordId}/memo-pdf`;
                    if (copy === 1 || copy === 2) {
                        targetUrl += `?copy=${copy}`;
                    }
                    const row = findSalaryListRowById(recordId) || {};
                    const subject = String(row.subject || '').trim();
                    const title = copy === 2 ? 'บันทึกข้อความ (ส่งออก)' : 'บันทึกข้อความ';
                    const previewUrl = `${targetUrl}${targetUrl.includes('?') ? '&' : '?'}preview=${Date.now()}`;
                    const titleEl = document.getElementById('withdraw-salary-memo-title');
                    const subjectEl = document.getElementById('withdraw-salary-memo-subject');
                    const bodyEl = document.getElementById('withdraw-salary-memo-body');
                    if (titleEl) titleEl.textContent = title;
                    if (subjectEl) subjectEl.textContent = subject !== '' && subject !== '-' ? subject : 'บันทึกข้อความขออนุมัติเบิกจ่าย';
                    if (bodyEl) {
                        const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0 text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>
                            <a href="${escapeHtml(targetUrl)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">เปิดไฟล์</a>
                        </div>`;
                        bodyEl.innerHTML = salaryPdfCanEmbed()
                            ? `<div class="overflow-hidden rounded-xl border border-indigo-100 bg-white p-2">${caption}<iframe src="${escapeHtml(previewUrl)}" title="${escapeHtml(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`
                            : `<div class="rounded-xl border border-indigo-100 bg-white p-3">${caption}</div>`;
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-salary-memo-modal' }));
                };
                const openSalaryEsignDocs = async (id) => {
                    const recordId = Number(id || 0);
                    if (recordId <= 0) return;
                    const row = findSalaryListRowById(recordId) || {};
                    const subject = String(row.subject || '').trim();
                    const subjectEl = document.getElementById('withdraw-salary-esign-subject');
                    const loadingEl = document.getElementById('withdraw-salary-esign-loading');
                    const emptyEl = document.getElementById('withdraw-salary-esign-empty');
                    const bodyEl = document.getElementById('withdraw-salary-esign-body');
                    if (subjectEl) subjectEl.textContent = subject !== '' && subject !== '-' ? subject : 'เอกสารลงนาม e-Sign ที่แนบมา';
                    loadingEl?.classList.remove('hidden');
                    emptyEl?.classList.add('hidden');
                    if (bodyEl) bodyEl.innerHTML = '';
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-salary-esign-docs-modal' }));
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
                        if (bodyEl) {
                            bodyEl.innerHTML = visibleFiles.map((file, index) => {
                                const title = String(file?.item_name || '').trim() || `เอกสารลงนาม e-Sign ${index + 1}`;
                                const url = String(file?.url || '').trim();
                                const previewUrl = url === '' ? '' : `${url}${url.includes('?') ? '&' : '?'}preview=${Date.now()}`;
                                const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <div class="min-w-0 text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>
                                    ${url !== '' ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">เปิดไฟล์</a>` : ''}
                                </div>`;
                                if (url === '' || !salaryPdfCanEmbed()) {
                                    return `<div class="rounded-xl border border-violet-100 bg-white p-3">${caption}</div>`;
                                }
                                return `<div class="overflow-hidden rounded-xl border border-violet-100 bg-white p-2">${caption}<iframe src="${escapeHtml(previewUrl)}" title="${escapeHtml(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`;
                            }).join('');
                        }
                    } catch (error) {
                        loadingEl?.classList.add('hidden');
                        emptyEl?.classList.remove('hidden');
                    }
                };

                let pendingOnePlanningId = 0;
                const onePlanningSendLogsByBudget = new Map();
                const onePlanningCodeEl = document.getElementById('withdraw-one-planning-code');
                const onePlanningSubjectEl = document.getElementById('withdraw-one-planning-subject');
                const onePlanningApplicantEl = document.getElementById('withdraw-one-planning-applicant');
                const onePlanningAmountEl = document.getElementById('withdraw-one-planning-amount');
                const onePlanningRefIdEl = document.getElementById('withdraw-one-planning-ref-id');
                const onePlanningRefStatusEl = document.getElementById('withdraw-one-planning-ref-status');
                const onePlanningLastSentEl = document.getElementById('withdraw-one-planning-last-sent');
                const onePlanningChangeBanner = document.getElementById('withdraw-one-planning-change-banner');
                const onePlanningPayloadBody = document.getElementById('withdraw-one-planning-payload-body');
                const onePlanningConfirmSubmit = document.getElementById('withdraw-one-planning-confirm-submit');
                const onePlanningResponseBudgetCodeEl = document.getElementById('withdraw-one-planning-response-budget-code');
                const onePlanningResponseCountEl = document.getElementById('withdraw-one-planning-response-count');
                const onePlanningResponseListEl = document.getElementById('withdraw-one-planning-response-list');

                const formatOnePlanningDateTime = (value) => {
                    const raw = String(value || '').trim();
                    if (raw === '') return '-';
                    const date = new Date(raw.replace(' ', 'T'));
                    if (Number.isNaN(date.getTime())) return raw;
                    const dateText = date.toLocaleDateString('th-TH', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    });
                    const timeText = date.toLocaleTimeString('th-TH', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                    });
                    return `${dateText} ${timeText} น.`;
                };

                const formatOnePlanningResponseJson = (value) => {
                    if (value == null || value === '') return '-';
                    if (typeof value === 'string') {
                        try {
                            return JSON.stringify(JSON.parse(value), null, 2);
                        } catch (error) {
                            return value;
                        }
                    }
                    try {
                        return JSON.stringify(value, null, 2);
                    } catch (error) {
                        return String(value);
                    }
                };

                const openOnePlanningResponseModal = (budgetCode) => {
                    const code = String(budgetCode || '').trim();
                    const logs = code !== '' && onePlanningSendLogsByBudget.has(code)
                        ? (onePlanningSendLogsByBudget.get(code) || [])
                        : [];
                    if (onePlanningResponseBudgetCodeEl) {
                        onePlanningResponseBudgetCodeEl.textContent = code !== '' ? code : '-';
                    }
                    if (onePlanningResponseCountEl) {
                        onePlanningResponseCountEl.textContent = String(logs.length);
                    }
                    if (onePlanningResponseListEl) {
                        if (logs.length === 0) {
                            onePlanningResponseListEl.innerHTML = `
                                <div class="rounded-xl border border-dashed border-slate-200 px-3 py-6 text-center text-sm text-slate-400">
                                    ยังไม่มีข้อมูล Response จากระบบปลายทางของรหัสงบนี้
                                </div>`;
                        } else {
                            onePlanningResponseListEl.innerHTML = logs.map((log, index) => {
                                const isSuccess = Boolean(log?.is_success);
                                const statusBadge = isSuccess
                                    ? '<span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100">สำเร็จ</span>'
                                    : '<span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-rose-100">ไม่สำเร็จ</span>';
                                const budgetValue = Number(log?.budget_value || 0).toLocaleString('th-TH', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                });
                                const responseText = formatOnePlanningResponseJson(log?.response);
                                return `
                                    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="text-sm font-semibold text-slate-800">ครั้งที่ ${logs.length - index}</div>
                                            <div class="flex items-center gap-2">${statusBadge}<span class="text-xs text-slate-500">${escapeHtml(formatOnePlanningDateTime(log?.sent_at))}</span></div>
                                        </div>
                                        <div class="mt-2 grid gap-1 text-xs text-slate-600 sm:grid-cols-2">
                                            <div><span class="font-semibold text-slate-700">response_message:</span> ${escapeHtml(String(log?.response_message || '-'))}</div>
                                            <div><span class="font-semibold text-slate-700">trigger_source:</span> ${escapeHtml(String(log?.trigger_source || '-'))}</div>
                                            <div><span class="font-semibold text-slate-700">ref_status_name:</span> ${escapeHtml(String(log?.ref_status_name || '-'))}</div>
                                            <div><span class="font-semibold text-slate-700">budget_value:</span> ${escapeHtml(budgetValue)}</div>
                                            <div><span class="font-semibold text-slate-700">ref_id:</span> ${escapeHtml(String(log?.ref_id || '-'))}</div>
                                            <div><span class="font-semibold text-slate-700">staff_id:</span> ${escapeHtml(String(log?.staff_id || '-'))}</div>
                                        </div>
                                        <div class="mt-3">
                                            <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Response จากระบบปลายทาง</div>
                                            <pre class="max-h-56 overflow-auto rounded-lg bg-slate-900 px-3 py-2 text-[11px] leading-5 text-slate-100 whitespace-pre-wrap break-all">${escapeHtml(responseText)}</pre>
                                        </div>
                                    </div>`;
                            }).join('');
                        }
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-salary-one-planning-response-modal' }));
                };

                window.__withdrawSalaryOpenOnePlanningResponse = openOnePlanningResponseModal;

                onePlanningPayloadBody?.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-action="one-planning-response"]');
                    if (!button) return;
                    event.preventDefault();
                    event.stopPropagation();
                    openOnePlanningResponseModal(String(button.getAttribute('data-budget-code') || button.dataset.budgetCode || ''));
                });

                const resetOnePlanningConfirmModal = () => {
                    onePlanningSendLogsByBudget.clear();
                    if (onePlanningCodeEl) onePlanningCodeEl.textContent = '-';
                    if (onePlanningSubjectEl) onePlanningSubjectEl.textContent = '-';
                    if (onePlanningApplicantEl) onePlanningApplicantEl.textContent = '-';
                    if (onePlanningAmountEl) onePlanningAmountEl.textContent = '-';
                    if (onePlanningRefIdEl) onePlanningRefIdEl.textContent = '-';
                    if (onePlanningRefStatusEl) onePlanningRefStatusEl.textContent = '-';
                    if (onePlanningLastSentEl) onePlanningLastSentEl.textContent = '-';
                    if (onePlanningChangeBanner) onePlanningChangeBanner.classList.add('hidden');
                    if (onePlanningPayloadBody) {
                        onePlanningPayloadBody.innerHTML = '<tr><td colspan="7" class="px-3 py-4 text-center text-slate-400">กำลังโหลดข้อมูล...</td></tr>';
                    }
                    if (onePlanningConfirmSubmit) {
                        onePlanningConfirmSubmit.disabled = true;
                        onePlanningConfirmSubmit.textContent = 'ยืนยันส่ง One Planning';
                    }
                };

                const renderOnePlanningComparisonRows = (payloads, removedPayloads) => {
                    const currentRows = Array.isArray(payloads) ? payloads : [];
                    const removedRows = Array.isArray(removedPayloads) ? removedPayloads : [];
                    if (!onePlanningPayloadBody) return currentRows.length + removedRows.length;

                    onePlanningSendLogsByBudget.clear();
                    [...currentRows, ...removedRows].forEach((row) => {
                        const budgetCode = String(row?.budget_code || '').trim();
                        if (budgetCode === '') return;
                        onePlanningSendLogsByBudget.set(
                            budgetCode,
                            Array.isArray(row?.send_logs) ? row.send_logs : []
                        );
                    });

                    const buildCompareCell = (row) => {
                        const labels = Array.isArray(row.change_labels) ? row.change_labels.filter(Boolean) : [];
                        if (labels.length === 0) {
                            return '<span class="inline-flex rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-100">ไม่เปลี่ยน</span>';
                        }
                        return `<div class="space-y-1">${labels.map((label) =>
                            `<div class="break-words rounded-md bg-amber-50 px-1.5 py-1 text-[10px] font-medium leading-4 text-amber-800 ring-1 ring-amber-100">${escapeHtml(String(label))}</div>`
                        ).join('')}</div>`;
                    };

                    const buildResponseCell = (row) => {
                        const budgetCode = String(row?.budget_code || '').trim();
                        const logs = Array.isArray(row?.send_logs) ? row.send_logs : [];
                        const latest = logs[0] || row?.last_send_log || null;
                        const hasLogs = logs.length > 0;
                        const latestOk = latest ? Boolean(latest.is_success) : null;
                        const statusHint = !hasLogs
                            ? '<div class="mt-1 text-[9px] leading-3 text-slate-400">ยังไม่เคยส่ง</div>'
                            : latestOk
                                ? `<div class="mt-1 text-[9px] font-medium leading-3 text-emerald-600">${logs.length} · สำเร็จ</div>`
                                : `<div class="mt-1 text-[9px] font-medium leading-3 text-rose-600">${logs.length} · error</div>`;
                        const buttonClass = hasLogs
                            ? (latestOk
                                ? 'border-cyan-200 bg-cyan-50 text-cyan-800 hover:bg-cyan-100'
                                : 'border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100')
                            : 'border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100';
                        return `<div class="flex flex-col items-center">
                            <button type="button"
                                data-action="one-planning-response"
                                data-budget-code="${escapeHtml(budgetCode)}"
                                class="inline-flex items-center gap-1 rounded-md border px-2 py-1 text-[10px] font-semibold transition ${buttonClass}"
                                ${budgetCode === '' ? 'disabled' : ''}>
                                <i class="fas fa-code text-[9px]"></i>ดู
                            </button>
                            ${statusHint}
                        </div>`;
                    };

                    const currentHtml = currentRows.map((row) => {
                        const hasChanges = Boolean(row.has_changes);
                        const rowClass = hasChanges ? 'bg-amber-50/40' : '';
                        const budgetCode = escapeHtml(String(row.budget_code || '-'));
                        const refId = escapeHtml(String(row.ref_id || '-'));
                        const budgetValue = Number(row.budget_value || 0).toLocaleString('th-TH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });
                        const comment = escapeHtml(String(row.comment || '-'));
                        const lastSent = formatOnePlanningDateTime(row.last_sent_at);
                        return `<tr class="${rowClass}">
                            <td class="break-all px-2 py-2 font-mono text-[11px] font-semibold leading-4 text-slate-800">${budgetCode}</td>
                            <td class="break-all px-2 py-2 font-mono text-[10px] leading-4 text-cyan-800">${refId}</td>
                            <td class="px-2 py-2 text-right text-xs font-semibold tabular-nums text-slate-800">${budgetValue}</td>
                            <td class="break-words px-2 py-2 text-xs leading-4 text-slate-700">${comment}</td>
                            <td class="break-words px-2 py-2 text-[11px] leading-4 text-slate-600">${escapeHtml(lastSent)}</td>
                            <td class="px-2 py-2">${buildCompareCell(row)}</td>
                            <td class="px-2 py-2 text-center">${buildResponseCell(row)}</td>
                        </tr>`;
                    }).join('');

                    const removedHtml = removedRows.map((row) => {
                        const budgetCode = escapeHtml(String(row.budget_code || '-'));
                        const refId = escapeHtml(String(row.ref_id || row?.last_send_log?.ref_id || '-'));
                        const lastValue = Number(row.last_sent_budget_value || 0).toLocaleString('th-TH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });
                        const lastComment = escapeHtml(String(row.last_sent_comment || '-'));
                        const lastSent = formatOnePlanningDateTime(row.last_sent_at);
                        return `<tr class="bg-rose-50/50">
                            <td class="break-all px-2 py-2 font-mono text-[11px] font-semibold leading-4 text-rose-800">${budgetCode}</td>
                            <td class="break-all px-2 py-2 font-mono text-[10px] leading-4 text-rose-700">${refId}</td>
                            <td class="px-2 py-2 text-right text-xs font-semibold text-rose-700">-</td>
                            <td class="px-2 py-2 text-xs text-rose-700">-</td>
                            <td class="break-words px-2 py-2 text-[11px] leading-4 text-rose-700">${escapeHtml(lastSent)}</td>
                            <td class="px-2 py-2">
                                <div class="space-y-1">
                                    <div class="break-words rounded-md bg-rose-50 px-1.5 py-1 text-[10px] font-medium leading-4 text-rose-800 ring-1 ring-rose-100">งบนี้เคยส่งแล้ว แต่ไม่มีในระบบปัจจุบัน</div>
                                    <div class="break-words text-[10px] leading-4 text-rose-700">ค่าล่าสุด: ${lastValue} / ${lastComment}</div>
                                </div>
                            </td>
                            <td class="px-2 py-2 text-center">${buildResponseCell(row)}</td>
                        </tr>`;
                    }).join('');

                    if (currentHtml === '' && removedHtml === '') {
                        onePlanningPayloadBody.innerHTML = '<tr><td colspan="7" class="px-3 py-4 text-center text-rose-500">ไม่พบข้อมูลงบประมาณ</td></tr>';
                        return 0;
                    }

                    onePlanningPayloadBody.innerHTML = `${currentHtml}${removedHtml}`;
                    return currentRows.length;
                };

                const openOnePlanningConfirmById = async (id) => {
                    const recordId = Number(id || 0);
                    if (recordId <= 0) return;
                    pendingOnePlanningId = recordId;
                    resetOnePlanningConfirmModal();
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-salary-one-planning-confirm-modal' }));
                    try {
                        const response = await fetch(`${onePlanningPreviewApiBase}/${recordId}/one-planning-preview`, {
                            headers: { Accept: 'application/json' },
                        });
                        const json = await response.json();
                        if (!response.ok || json?.status !== 'success' || !json?.data) {
                            throw new Error(String(json?.message || 'โหลดข้อมูลไม่สำเร็จ'));
                        }
                        const withdraw = json.data.withdraw || {};
                        const payloads = Array.isArray(json.data.payloads) ? json.data.payloads : [];
                        const removedPayloads = Array.isArray(json.data.removed_payloads) ? json.data.removed_payloads : [];
                        if (onePlanningCodeEl) onePlanningCodeEl.textContent = String(withdraw.code || '-').trim() || '-';
                        if (onePlanningSubjectEl) onePlanningSubjectEl.textContent = String(withdraw.subject || '-').trim() || '-';
                        if (onePlanningApplicantEl) onePlanningApplicantEl.textContent = String(withdraw.applicant || '-').trim() || '-';
                        if (onePlanningAmountEl) {
                            onePlanningAmountEl.textContent = String(withdraw.amount_formatted || Number(withdraw.amount || 0).toLocaleString('th-TH'));
                        }
                        if (onePlanningRefIdEl) {
                            onePlanningRefIdEl.textContent = String(withdraw.ref_id || `salary_${recordId}_{n}`);
                        }
                        if (onePlanningRefStatusEl) {
                            onePlanningRefStatusEl.textContent = String(json.data.ref_status_name || '-');
                        }
                        if (onePlanningLastSentEl) {
                            onePlanningLastSentEl.textContent = formatOnePlanningDateTime(json.data.last_sent_at);
                        }
                        if (onePlanningChangeBanner) {
                            onePlanningChangeBanner.classList.toggle('hidden', !Boolean(json.data.has_changes));
                        }
                        const sendableCount = renderOnePlanningComparisonRows(payloads, removedPayloads);
                        if (onePlanningConfirmSubmit) {
                            onePlanningConfirmSubmit.disabled = sendableCount <= 0;
                        }
                    } catch (error) {
                        if (onePlanningPayloadBody) {
                            onePlanningPayloadBody.innerHTML = `<tr><td colspan="7" class="px-3 py-4 text-center text-rose-500">${escapeHtml(String(error?.message || 'โหลดข้อมูลไม่สำเร็จ'))}</td></tr>`;
                        }
                        window.toast?.error?.(String(error?.message || 'โหลดข้อมูล One Planning ไม่สำเร็จ'), { timer: 1800 });
                    }
                };

                window.__withdrawSalaryConfirmOnePlanningSend = async () => {
                    const recordId = Number(pendingOnePlanningId || 0);
                    if (recordId <= 0) return;
                    if (onePlanningConfirmSubmit) {
                        onePlanningConfirmSubmit.disabled = true;
                        onePlanningConfirmSubmit.textContent = 'กำลังส่ง...';
                    }
                    try {
                        const response = await fetch(`${onePlanningPreviewApiBase}/${recordId}/one-planning-send`, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                            },
                            body: JSON.stringify({}),
                        });
                        const json = await response.json();
                        if (!response.ok || json?.status !== 'success') {
                            const results = Array.isArray(json?.data?.results) ? json.data.results : [];
                            const detailMessages = results
                                .filter((row) => row && !row.ok)
                                .map((row) => {
                                    const code = String(row.budget_code || '').trim();
                                    const message = String(row.message || '').trim();
                                    if (!message) return '';
                                    return code !== '' ? `${code}: ${message}` : message;
                                })
                                .filter(Boolean);
                            const detailText = detailMessages.length > 0
                                ? ` — ${Array.from(new Set(detailMessages)).join(' | ')}`
                                : '';
                            throw new Error(String(json?.message || `ส่งข้อมูลไม่สำเร็จ${detailText}`));
                        }
                        const successCount = Number(json?.data?.success_count || 0);
                        const failedCount = Number(json?.data?.failed_count || 0);
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-salary-one-planning-confirm-modal' }));
                        if (failedCount > 0) {
                            window.toast?.warning?.(String(json?.message || `ส่งสำเร็จ ${successCount} รายการ ล้มเหลว ${failedCount} รายการ`), { timer: 3200 });
                        } else {
                            window.toast?.success?.(String(json?.message || 'ส่งข้อมูลไป One Planning เรียบร้อยแล้ว'), { timer: 1800 });
                        }
                    } catch (error) {
                        window.toast?.error?.(String(error?.message || 'ส่งข้อมูลไป One Planning ไม่สำเร็จ'), { timer: 4200 });
                        if (onePlanningConfirmSubmit) {
                            onePlanningConfirmSubmit.disabled = false;
                            onePlanningConfirmSubmit.textContent = 'ยืนยันส่ง One Planning';
                        }
                        // โหลด preview ใหม่เพื่อให้กดดู Response ของรอบล่าสุดได้
                        if (recordId > 0) {
                            await openOnePlanningConfirmById(recordId);
                        }
                    }
                };

                const populateGeneralTableFilters = (rows) => {
                    const safeRows = (Array.isArray(rows) ? rows : []).filter((row) => canCurrentUserViewGeneralRow(row));
                    if (filterFiscalYearSelect) {
                        const current = String(filterFiscalYearSelect.value || '');
                        const years = Array.from(new Set(
                            safeRows.map((row) => String(row.fiscal_year || '').trim()).filter((value) => value !== '' && value !== '0')
                        )).sort((a, b) => Number(b) - Number(a));
                        const currentFiscalYear = String(getCurrentFiscalYearBe());
                        filterFiscalYearSelect.innerHTML = [
                            '<option value="">ปีงบประมาณทั้งหมด</option>',
                            ...years.map((year) => `<option value="${year}">${year}</option>`),
                        ].join('');
                        if (current === '') {
                            filterFiscalYearSelect.value = '';
                        } else if (years.includes(current)) {
                            filterFiscalYearSelect.value = current;
                        } else if (!fiscalYearFilterDefaulted && years.includes(currentFiscalYear)) {
                            filterFiscalYearSelect.value = currentFiscalYear;
                            fiscalYearFilterDefaulted = true;
                        } else {
                            filterFiscalYearSelect.value = '';
                        }
                    }

                    if (filterStatusSelect) {
                        const current = String(filterStatusSelect.value || '');
                        const statuses = Array.from(new Set(
                            safeRows.map((row) => String(row.status || '').trim()).filter((value) => value !== '' && value !== '-')
                        ));
                        filterStatusSelect.innerHTML = [
                            '<option value="">ทุกสถานะ</option>',
                            ...statuses.map((status) => `<option value="${status}">${status}</option>`),
                        ].join('');
                        filterStatusSelect.value = current === '' || statuses.includes(current) ? current : '';
                    }

                    if (filterTypeSelect) {
                        const current = String(filterTypeSelect.value || '');
                        const types = Array.from(new Set(
                            safeRows.map((row) => String(row.type || '').trim()).filter((value) => value !== '' && value !== '-')
                        ));
                        filterTypeSelect.innerHTML = [
                            '<option value="">ทุกประเภท</option>',
                            ...types.map((type) => `<option value="${type}">${type}</option>`),
                        ].join('');
                        filterTypeSelect.value = current === '' || types.includes(current) ? current : '';
                    }
                };

                const applyGeneralTableFilters = () => {
                    const fiscalYear = String(filterFiscalYearSelect?.value || '').trim();
                    const month = String(filterMonthSelect?.value || '').trim();
                    const status = String(filterStatusSelect?.value || '').trim();
                    const type = String(filterTypeSelect?.value || '').trim();
                    const keyword = String(filterSearchInput?.value || '').trim().toLowerCase();

                    const filtered = (Array.isArray(generalTableRows) ? generalTableRows : []).filter((row) => {
                        if (!canCurrentUserViewGeneralRow(row)) return false;
                        if (fiscalYear !== '' && String(row.fiscal_year || '').trim() !== fiscalYear) return false;
                        if (month !== '' && String(row.document_month || '').trim() !== month) return false;
                        if (status !== '' && String(row.status || '').trim() !== status) return false;
                        if (type !== '' && String(row.type || '').trim() !== type) return false;
                        if (keyword !== '') {
                            const haystack = [
                                row.code,
                                row.date,
                                row.subject,
                                row.manager,
                                row.status,
                                row.type,
                            ].map((value) => String(value || '').toLowerCase()).join(' ');
                            if (!haystack.includes(keyword)) return false;
                        }
                        return true;
                    });

                    renderGeneralTableRows(filtered);
                };

                const refreshGeneralTable = async () => {
                    const response = await fetch(generalListApi, { headers: { Accept: 'application/json' } });
                    const result = await response.json();
                    if (result?.status === 'success') {
                        generalTableRows = Array.isArray(result?.data) ? result.data : [];
                        populateGeneralTableFilters(generalTableRows);
                        applyGeneralTableFilters();
                    }
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

                const loadPayListOptions = async () => {
                    payListLoadStatus = 'loading';
                    const withdrawOptionsJson = await fetchJson(withdrawOptionsApi);
                    const payRows = withdrawOptionsJson?.status === 'success'
                        ? normalizePayListRows(withdrawOptionsJson?.data?.pay_lists || withdrawOptionsJson?.data?.payLists || [])
                        : [];
                    payListNameByCode.clear();
                    payListMetaByPayCode.clear();
                    payRows.forEach((item) => {
                        const code = String(item.code || '').trim();
                        const name = String(item.name || '').trim();
                        const categoryPayCode = String(item.categoryPayCode || '').trim();
                        const categoryPayName = String(item.categoryPayName || '').trim();
                        if (code !== '' && name !== '') payListNameByCode.set(code, name);
                        if (code !== '') {
                            payListMetaByPayCode.set(code, { categoryPayCode, categoryPayName });
                        }
                    });

                    const expenseOptionsJson = await fetchJson(expenseChartOptionsApi);
                    const chartRows = expenseOptionsJson?.status === 'success'
                        ? (Array.isArray(expenseOptionsJson?.data?.expense_charts) ? expenseOptionsJson.data.expense_charts : [])
                        : [];

                    expenseChartRows.splice(0, expenseChartRows.length, ...chartRows.map((row) => ({
                        id: Number(row?.id || 0),
                        pay_code: String(row?.pay_code || '').trim(),
                        expense_code: String(row?.expense_code || '').trim(),
                        name: String(row?.name || '').trim(),
                        detail: String(row?.detail || '').trim(),
                    })).filter((row) => row.id > 0 && row.name !== ''));

                    const categoryMap = new Map();
                    expenseSubitemsByCategory.clear();
                    expenseChartRows.forEach((item) => {
                        const payCode = String(item.pay_code || '').trim();
                        const fallbackCategoryCode = String(item.expense_code || '').trim().split('.').filter(Boolean)[0] || '';
                        const categoryCode = payCode !== '' ? payCode : (fallbackCategoryCode !== '' ? fallbackCategoryCode : `chart-${String(item.id)}`);
                        const payName = payListNameByCode.get(categoryCode) || '';
                        const categoryLabel = payName !== '' ? payName : (item.detail !== '' ? item.detail : `หมวด ${categoryCode}`);

                        if (!categoryMap.has(categoryCode)) {
                            const payMeta = payListMetaByPayCode.get(categoryCode) || {};
                            categoryMap.set(categoryCode, {
                                code: categoryCode,
                                name: categoryLabel,
                                label: categoryLabel,
                                categoryPayCode: String(payMeta.categoryPayCode || '').trim(),
                            });
                        }

                        const subitems = expenseSubitemsByCategory.get(categoryCode) || [];
                        subitems.push({
                            id: String(item.id),
                            label: item.name,
                            name: item.name,
                            detail: item.detail,
                            expense_code: item.expense_code,
                        });
                        expenseSubitemsByCategory.set(categoryCode, subitems);
                    });
                    const categories = Array.from(categoryMap.values()).sort((a, b) => String(a.label).localeCompare(String(b.label), 'th'));
                    expenseSubitemsByCategory.forEach((subitems, categoryCode) => {
                        const sorted = [...subitems].sort((a, b) => String(a.label || '').localeCompare(String(b.label || ''), 'th'));
                        expenseSubitemsByCategory.set(categoryCode, sorted);
                    });

                    categoryOptions.splice(0, categoryOptions.length, ...categories);
                    payListLoadStatus = categoryOptions.length > 0 && expenseChartRows.length > 0 ? 'success' : 'error';
                };

                const loadBudgetSourceOptions = async (forceReload = false) => {
                    if (!forceReload && budgetSourceOptions.length > 0) return budgetSourceOptions;
                    try {
                        const response = await fetch(`${budgetSourceOptionsApi}?_=${Date.now()}`, {
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
                            const name = (path !== '' ? path : code).replace(/\s*\/\s*/g, ' ').replace(/\s+/g, ' ').trim();
                            const year = String(row?.year ?? '').trim();
                            const amount = Number(row?.amount || 0) || 0;
                            const balance = Number(row?.balance || 0) || 0;
                            const approvedAmount = Number(row?.approved_amount || 0) || 0;
                            let label = '';
                            if (code !== '' && path !== '') {
                                label = `${code} — ${path}`;
                            } else {
                                label = code !== '' ? code : path;
                            }
                            const option = {
                                id,
                                code,
                                label,
                                name,
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
                    } catch (error) {
                        budgetSourceOptions = [];
                    }

                    refreshBudgetItemRowOptions();

                    return budgetSourceOptions;
                };

                const normalizePayCodeKey = (value) => String(value ?? '').trim();

                const getCategoryOptionsForBudget = (budgetManageId) => {
                    const budgetId = String(budgetManageId || '').trim();
                    if (budgetId === '') return [];
                    const budgetOption = budgetSourceOptionMap.get(budgetId);
                    const budgetCategoryPayCode = normalizePayCodeKey(budgetOption?.categoryPayCode);
                    if (budgetCategoryPayCode === '') {
                        return categoryOptions.map((cat) => ({
                            code: String(cat.code || '').trim(),
                            label: String(cat.label || cat.name || '').trim(),
                        }));
                    }
                    return categoryOptions
                        .filter((cat) => normalizePayCodeKey(cat.categoryPayCode) === budgetCategoryPayCode)
                        .map((cat) => ({
                            code: String(cat.code || '').trim(),
                            label: String(cat.label || cat.name || '').trim(),
                        }));
                };

                const syncItemRowCategoryOptions = (row, options = {}) => {
                    const { resetSelection = false } = options;
                    if (!row) return;
                    const categoryOptionsKey = String(row.dataset.categoryOptionsKey || '').trim();
                    if (categoryOptionsKey === '') return;

                    const budgetId = String(row.querySelector('.withdraw-item-budget-value')?.value || '').trim();
                    const filtered = getCategoryOptionsForBudget(budgetId);
                    const categoryHiddenInput = row.querySelector('.withdraw-item-category-value');
                    const categoryTextInput = row.querySelector('.withdraw-item-category');
                    const currentCategoryCode = String(categoryHiddenInput?.value || '').trim();

                    window.PBMSearchableDropdown?.setOptions?.(categoryOptionsKey, filtered);
                    if (categoryTextInput) {
                        categoryTextInput.placeholder = budgetId === '' ? 'เลือกงบประมาณก่อน' : 'ค้นหาหมวด...';
                    }
                    if (budgetId === '') {
                        window.PBMSearchableDropdown?.setStatus?.(categoryOptionsKey, 'เลือกงบประมาณก่อน');
                    } else if (filtered.length === 0) {
                        window.PBMSearchableDropdown?.setStatus?.(categoryOptionsKey, 'ไม่พบหมวดที่สอดคล้องกับงบประมาณนี้');
                    } else {
                        window.PBMSearchableDropdown?.setStatus?.(categoryOptionsKey, getPayListDropdownStatusMessage());
                    }

                    const stillValid = filtered.some((item) => String(item.code || '').trim() === currentCategoryCode);
                    if (resetSelection || (currentCategoryCode !== '' && !stillValid)) {
                        if (categoryHiddenInput) {
                            categoryHiddenInput.value = '';
                            categoryHiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                            categoryHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (categoryTextInput) categoryTextInput.value = '';
                        if (typeof row.__populateExpenseSubitems === 'function') {
                            row.__populateExpenseSubitems('');
                        }
                    }

                    window.PBMSearchableDropdown?.initAll?.();
                    refreshItemRowVisualState(row);
                };

                const refreshBudgetItemRowOptions = () => {
                    itemRowsContainer?.querySelectorAll('[data-pbm-options-key^="withdraw-item-budget-options-"]').forEach((wrapper) => {
                        const key = String(wrapper.dataset.pbmOptionsKey || '').trim();
                        if (key === '') return;
                        window.PBMSearchableDropdown?.setOptions?.(key, budgetSourceOptions);
                        window.PBMSearchableDropdown?.setStatus?.(
                            key,
                            budgetSourceOptions.length === 0 ? 'ไม่พบข้อมูลงบประมาณ' : ''
                        );
                    });
                    window.PBMSearchableDropdown?.initAll?.();
                    syncParagraph2WithBudgetRows();
                };

                const getBudgetNameById = (id, fallback = '') => {
                    const idValue = String(id || '').trim();
                    const option = budgetSourceOptions.find((row) => String(row.id || '').trim() === idValue);
                    const fallbackName = String(fallback || '').replace(/\s*\/\s*/g, ' ').replace(/\s+/g, ' ').trim();
                    return String(option?.name || fallbackName || '').trim();
                };

                const getItemRowElements = () => Array.from(itemRowsContainer?.querySelectorAll('.withdraw-item-card') || []);

                const escapeAttr = (value) => escapeHtml(value).replace(/"/g, '&quot;');

                const itemFieldInvalidClasses = ['border-amber-400', 'bg-amber-50'];
                const itemFieldValidClasses = ['border-slate-300', 'bg-white'];

                const setItemFieldInvalidVisual = (fieldEl, invalid) => {
                    if (!fieldEl) return;
                    fieldEl.querySelectorAll('input:not([type="hidden"])').forEach((input) => {
                        itemFieldInvalidClasses.forEach((cls) => input.classList.toggle(cls, invalid));
                        itemFieldValidClasses.forEach((cls) => input.classList.toggle(cls, !invalid));
                    });
                };

                const refreshItemRowVisualState = (row) => {
                    if (!row) return;
                    const budgetVal = String(row.querySelector('.withdraw-item-budget-value')?.value || '').trim();
                    const categoryVal = String(row.querySelector('.withdraw-item-category-value')?.value || '').trim();
                    const expenseVal = String(row.querySelector('.withdraw-item-name-value')?.value || '').trim();
                    const expenseText = String(row.querySelector('.withdraw-item-name')?.value || '').trim();
                    const amountVal = Number(String(row.querySelector('.withdraw-item-amount')?.value || '').replace(/,/g, '')) || 0;

                    setItemFieldInvalidVisual(row.querySelector('[data-field="budget"]'), budgetVal === '');
                    setItemFieldInvalidVisual(row.querySelector('[data-field="category"]'), categoryVal === '');
                    setItemFieldInvalidVisual(
                        row.querySelector('[data-field="expense"]'),
                        categoryVal !== '' && expenseVal === '' && expenseText.trim() === ''
                    );
                    setItemFieldInvalidVisual(row.querySelector('[data-field="amount"]'), amountVal <= 0);

                    const expenseWrap = row.querySelector('[data-expense-wrap]');
                    const expenseInput = row.querySelector('.withdraw-item-name');
                    const expenseLocked = categoryVal === '';
                    if (expenseWrap) {
                        expenseWrap.classList.toggle('pointer-events-none', expenseLocked);
                        expenseWrap.classList.toggle('opacity-50', expenseLocked);
                    }
                    if (expenseInput) {
                        expenseInput.disabled = expenseLocked;
                        expenseInput.placeholder = expenseLocked ? 'เลือกหมวดก่อน' : 'พิมพ์หรือเลือกรายการ...';
                    }

                    const budgetHint = row.querySelector('[data-budget-hint]');
                    if (budgetHint) {
                        const option = budgetSourceOptionMap.get(budgetVal);
                        if (option && budgetVal !== '') {
                            budgetHint.textContent = `คงเหลือ ${formatMoney(Number(option.balance || 0))} บาท`;
                            budgetHint.classList.remove('hidden');
                        } else {
                            budgetHint.textContent = '';
                            budgetHint.classList.add('hidden');
                        }
                    }
                };

                const updateItemListUi = () => {
                    const rows = getItemRowElements();
                    const count = rows.length;
                    const countBadge = document.getElementById('withdraw-item-count-badge');
                    const emptyEl = document.getElementById('withdraw-item-empty');
                    const addFooterEl = document.getElementById('withdraw-item-add-footer');
                    const summaryEl = document.getElementById('withdraw-item-summary');
                    if (countBadge) countBadge.textContent = String(count);
                    if (emptyEl) emptyEl.classList.toggle('hidden', count > 0);
                    if (addFooterEl) addFooterEl.classList.toggle('hidden', count === 0);
                    if (summaryEl) summaryEl.classList.toggle('hidden', count === 0);
                    rows.forEach(refreshItemRowVisualState);
                };

                const getSelectedBudgetRows = () => {
                    const aggregated = new Map();
                    getItemRowElements().forEach((row) => {
                        const id = String(row.querySelector('.withdraw-item-budget-value')?.value || '').trim();
                        const amountRaw = String(row.querySelector('.withdraw-item-amount')?.value || '').replace(/,/g, '').trim();
                        const amount = Number(amountRaw);
                        if (id === '' || id === '0' || !Number.isFinite(amount) || amount <= 0) return;
                        aggregated.set(id, (aggregated.get(id) || 0) + amount);
                    });
                    return Array.from(aggregated.entries()).map(([id, amount]) => ({ id, amount }));
                };

                const getBudgetParagraphRows = () => {
                    const aggregated = new Map();
                    getItemRowElements().forEach((row) => {
                        const id = String(row.querySelector('.withdraw-item-budget-value')?.value || '').trim();
                        const fallbackLabel = String(row.querySelector('.withdraw-item-budget')?.value || '').trim();
                        const amountRaw = String(row.querySelector('.withdraw-item-amount')?.value || '').replace(/,/g, '').trim();
                        const amount = Number(amountRaw);
                        if (id === '' || id === '0' || !Number.isFinite(amount) || amount <= 0) return;
                        const name = getBudgetNameById(id, fallbackLabel);
                        if (name === '') return;
                        aggregated.set(id, {
                            id,
                            name,
                            amount: (aggregated.get(id)?.amount || 0) + amount,
                        });
                    });
                    return Array.from(aggregated.values());
                };

                const renderBudgetSummary = (rows) => {
                    if (!withdrawBudgetSummaryCard || !withdrawBudgetSummaryBody) return;
                    const list = Array.isArray(rows) ? rows : getBudgetParagraphRows();
                    if (list.length === 0) {
                        withdrawBudgetSummaryCard.classList.add('hidden');
                        withdrawBudgetSummaryBody.innerHTML = '<tr><td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="6">- ยังไม่ได้เลือกงบประมาณ -</td></tr>';
                        return;
                    }
                    const escape = (value) => String(value || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                    withdrawBudgetSummaryBody.innerHTML = list.map((row) => {
                        const option = budgetSourceOptionMap.get(String(row.id)) || {};
                        const budgetAmount = Number(option.amount || 0) || 0;
                        const approvedAlready = Number(option.approvedAmount || 0) || 0;
                        const approvedThisTime = Number(row.amount || 0) || 0;
                        const totalApproved = approvedAlready + approvedThisTime;
                        const remaining = budgetAmount - totalApproved;
                        const code = String(option.code || '').trim();
                        return `
                            <tr>
                                <td class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">${escape(code)}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(budgetAmount)}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(approvedAlready)}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(approvedThisTime)}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(totalApproved)}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right ${remaining < 0 ? 'text-rose-600 font-semibold' : ''}">${formatMoney(remaining)}</td>
                            </tr>
                        `;
                    }).join('');
                    withdrawBudgetSummaryCard.classList.remove('hidden');
                };

                const syncParagraph2WithBudgetRows = () => {
                    const rows = getBudgetParagraphRows();
                    renderBudgetSummary(rows);
                    if (!withdrawParagraph2Input) return;
                    if (rows.length === 0) {
                        withdrawParagraph2Input.value = '';
                        return;
                    }
                    if (rows.length === 1) {
                        withdrawParagraph2Input.value = `โดยเบิกจากเงิน ${rows[0].name}`;
                        return;
                    }
                    withdrawParagraph2Input.value = [
                        'โดยเบิกจ่ายจาก',
                        ...rows.map((row) => `- ${row.name} จำนวนเงิน ${formatMoney(row.amount)} บาท`),
                    ].join('\n');
                };

                const formatMoney = (value) => Number(value || 0).toLocaleString('th-TH', {
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

                const AMOUNT_PHRASE_PLACEHOLDER_RE = /จำนวนเงิน[\\.…]+/gu;
                const AMOUNT_PHRASE_VALUE_RE = /จำนวนเงิน\s+[\d,]+(?:\.\d{1,2})?\s+บาท\s+\([^)]+\)/gu;

                const getItemsTotalAmount = () => {
                    let total = 0;
                    itemRowsContainer.querySelectorAll('.withdraw-item-amount').forEach((amountInput) => {
                        const numeric = Number(String(amountInput.value || '').replace(/,/g, ''));
                        if (!Number.isNaN(numeric)) total += numeric;
                    });
                    return total;
                };

                const formatParagraph1AmountPhrase = (total) => {
                    const numeric = Number(total || 0);
                    const safeAmount = Number.isNaN(numeric) ? 0 : numeric;
                    return `จำนวนเงิน ${formatMoney(safeAmount)} บาท (${toThaiBahtText(safeAmount)})`;
                };

                const applyAmountPhraseToText = (text, total) => {
                    const source = String(text || '');
                    if (!/จำนวนเงิน/.test(source)) return source;
                    const label = formatParagraph1AmountPhrase(total);
                    if (AMOUNT_PHRASE_PLACEHOLDER_RE.test(source)) {
                        AMOUNT_PHRASE_PLACEHOLDER_RE.lastIndex = 0;
                        return source.replace(AMOUNT_PHRASE_PLACEHOLDER_RE, label);
                    }
                    AMOUNT_PHRASE_VALUE_RE.lastIndex = 0;
                    return source.replace(AMOUNT_PHRASE_VALUE_RE, label);
                };

                const syncMemoParagraph1AmountField = () => {
                    if (!withdrawParagraph1Input) return;
                    const current = withdrawParagraph1Input.value;
                    const updated = applyAmountPhraseToText(current, getItemsTotalAmount());
                    if (updated !== current) {
                        withdrawParagraph1Input.value = updated;
                    }
                };

                const refreshRowIndex = () => {
                    getItemRowElements().forEach((row, index) => {
                        const rowNo = String(index + 1);
                        row.querySelectorAll('[data-row-index]').forEach((el) => {
                            el.textContent = rowNo;
                        });
                    });
                    updateItemListUi();
                };

                const refreshTotal = () => {
                    const total = getItemsTotalAmount();
                    if (itemTotalElement) itemTotalElement.textContent = formatMoney(total);
                    updateItemListUi();
                    syncParagraph2WithBudgetRows();
                    syncMemoParagraph1AmountField();
                };

                const collectItemRowPreset = (card) => {
                    if (!card) return {};
                    return {
                        budget_manage_id: String(card.querySelector('.withdraw-item-budget-value')?.value || '').trim(),
                        category_code: String(card.querySelector('.withdraw-item-category-value')?.value || '').trim(),
                        expense_chart_id: String(card.querySelector('.withdraw-item-name-value')?.value || '').trim(),
                        name: String(card.querySelector('.withdraw-item-name')?.value || '').trim(),
                        amount: String(card.querySelector('.withdraw-item-amount')?.value || '').trim(),
                    };
                };

                const resolveExpenseItemSaveName = (expenseItem, fallbackText = '') => {
                    const chartName = String(expenseItem?.name || fallbackText || '').trim();
                    const detail = String(expenseItem?.detail || '').trim();
                    if (detail !== '' && detail !== '-') {
                        return detail;
                    }
                    return chartName;
                };

                const collectSubmitItemRows = () => Array.from(itemRowsContainer?.querySelectorAll('.withdraw-item-card') || [])
                    .map((row) => {
                        const budgetHiddenInput = row.querySelector('.withdraw-item-budget-value');
                        const categoryHiddenInput = row.querySelector('.withdraw-item-category-value');
                        const nameHiddenInput = row.querySelector('.withdraw-item-name-value');
                        const nameInput = row.querySelector('.withdraw-item-name');
                        const amountInput = row.querySelector('.withdraw-item-amount');
                        const amountValue = Number(String(amountInput?.value || '0').replace(/,/g, ''));
                        const selectedCategoryCode = String(categoryHiddenInput?.value || '').trim();
                        const selectedExpenseId = String(nameHiddenInput?.value || '').trim();
                        const selectedBudgetId = Number(String(budgetHiddenInput?.value || '').trim() || 0);
                        const selectedExpense = selectedCategoryCode !== ''
                            ? (expenseSubitemsByCategory.get(selectedCategoryCode) || []).find((item) => String(item.id || '').trim() === selectedExpenseId)
                            : null;
                        const expenseName = selectedExpense
                            ? resolveExpenseItemSaveName(selectedExpense, nameInput?.value || '')
                            : String(nameInput?.value || '').trim();
                        return {
                            category_code: selectedCategoryCode,
                            category: selectedCategoryCode,
                            name: expenseName,
                            expense_chart_id: Number(selectedExpenseId || 0),
                            budget_manage_id: selectedBudgetId,
                            amount: Number.isNaN(amountValue) ? 0 : amountValue,
                        };
                    })
                    .filter((row) =>
                        Number(row.budget_manage_id || 0) > 0
                        && row.category_code !== ''
                        && row.name !== ''
                        && Number(row.amount || 0) > 0
                    );

                const buildBudgetBreakdownPayload = (itemRows) => {
                    const aggregated = new Map();
                    itemRows.forEach((row) => {
                        const id = Number(row.budget_manage_id || 0);
                        const amount = Number(row.amount || 0);
                        if (id <= 0 || !Number.isFinite(amount) || amount <= 0) return;
                        aggregated.set(id, (aggregated.get(id) || 0) + amount);
                    });
                    return Array.from(aggregated.entries()).map(([budget_manage_id, amount]) => ({
                        budget_manage_id: Number(budget_manage_id),
                        amount,
                    }));
                };

                const createActorRow = (preset = null) => {
                    if (!actorRowsContainer) return;
                    actorRowsContainer.classList.remove('hidden');
                    actorRowCounter += 1;
                    const staffInputId = `withdraw-actor-staff-${actorRowCounter}`;
                    const staffOptionsKey = `withdraw-actor-staff-options-${actorRowCounter}`;
                    const defaultStaffId = String(preset?.staff_id || '').trim();
                    const defaultStatus = String(preset?.status || '').trim();

                    const row = document.createElement('div');
                    row.className = 'withdraw-actor-row rounded-xl border border-slate-200 bg-slate-50/60 p-3';
                    row.innerHTML = `
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_240px_auto] md:items-end">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">รายชื่อผู้ตรวจสอบ/อนุมัติ เพิ่มเติม</label>
                            <div
                                class="relative isolate pbm-searchable-select-wrapper"
                                data-pbm-searchable-select-wrapper="1"
                                data-pbm-options-key="${staffOptionsKey}"
                                data-pbm-value-key="id"
                                data-pbm-text-key="label">
                                <input id="${staffInputId}-text" type="search" placeholder="พิมพ์เพื่อค้นหาผู้ตรวจสอบ/อนุมัติ เพิ่มเติม..."
                                    class="withdraw-actor-staff w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                    autocomplete="off"
                                    data-pbm-searchable-select-text />
                                <div id="${staffInputId}-suggest"
                                    class="absolute left-0 right-0 top-full !z-[9999999] hidden max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white py-1 shadow-2xl"
                                    data-pbm-searchable-select-suggest>
                                </div>
                                <input type="hidden" id="${staffInputId}" value="${defaultStaffId}" data-pbm-searchable-select-hidden />
                            </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">สถานะดำเนินการ</label>
                            <select class="withdraw-actor-status w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <option value="">เลือกสถานะดำเนินการ</option>
                                ${actorStatusOptions.map((option) => `<option value="${option.value}" ${defaultStatus === option.value ? 'selected' : ''}>${option.label}</option>`).join('')}
                            </select>
                            </div>
                            <div class="md:pb-[1px]">
                            <button type="button"
                                class="withdraw-remove-actor inline-flex h-9 items-center justify-center rounded-lg bg-rose-50 px-3 text-rose-600 ring-1 ring-rose-100 transition hover:bg-rose-100"
                                data-ui-tooltip="ลบรายการ" data-ui-tooltip-placement="top" aria-label="ลบรายการ">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                            </div>
                        </div>
                    `;

                    actorRowsContainer.appendChild(row);
                    window.PBMSearchableDropdown?.setOptions?.(staffOptionsKey, staffSearchOptions);
                    window.PBMSearchableDropdown?.initAll?.();
                    const hiddenInput = row.querySelector('[data-pbm-searchable-select-hidden]');
                    if (hiddenInput && defaultStaffId !== '') {
                        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                };

                const createItemRow = (preset = {}) => {
                    itemRowCounter += 1;
                    const dropdownZ = 50 + itemRowCounter;
                    const presetBudgetId = String(preset.budget_manage_id || '').trim();
                    const categoryInputId = `withdraw-item-category-${itemRowCounter}`;
                    const categoryOptionsKey = `withdraw-item-category-options-${itemRowCounter}`;
                    const budgetInputId = `withdraw-item-budget-${itemRowCounter}`;
                    const budgetOptionsKey = `withdraw-item-budget-options-${itemRowCounter}`;
                    const expenseInputId = `withdraw-item-expense-${itemRowCounter}`;
                    const expenseOptionsKey = `withdraw-item-expense-options-${itemRowCounter}`;
                    const presetBudgetOption = budgetSourceOptionMap.get(presetBudgetId);
                    const presetBudgetLabel = String(presetBudgetOption?.label || '').trim();
                    const row = document.createElement('div');
                    row.dataset.categoryOptionsKey = categoryOptionsKey;
                    row.className = 'withdraw-item-card rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50/70 p-4 shadow-sm ring-1 ring-slate-100 focus-within:border-sky-300 focus-within:shadow-[0_0_0_3px_rgb(224_242_254/0.6)]';
                    row.innerHTML = `
                        <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 py-2.5">
                            <p class="m-0 flex min-h-8 items-center text-sm font-semibold leading-5 text-slate-800">รายการที่ <span data-row-index>1</span></p>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button type="button"
                                    class="withdraw-duplicate-item inline-flex h-8 items-center gap-1 rounded-lg bg-slate-100 px-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200"
                                    data-ui-tooltip="คัดลอกรายการนี้" data-ui-tooltip-placement="top" aria-label="คัดลอกรายการ">
                                    <i class="fas fa-copy text-[10px]"></i>
                                    <span class="hidden sm:inline">คัดลอก</span>
                                </button>
                                <button type="button"
                                    class="withdraw-remove-item inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 ring-1 ring-rose-100 transition hover:bg-rose-100"
                                    data-ui-tooltip="ลบรายการ" data-ui-tooltip-placement="top" aria-label="ลบรายการ">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="withdraw-item-field min-w-0" data-field="budget">
                                <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                    <i class="fas fa-wallet w-3.5 text-center text-[10px] text-emerald-600"></i>
                                    งบประมาณ
                                </label>
                                <div
                                    class="relative isolate pbm-searchable-select-wrapper withdraw-item-row-budget-wrapper"
                                    data-pbm-searchable-select-wrapper="1"
                                    data-pbm-options-key="${budgetOptionsKey}"
                                    data-pbm-value-key="id"
                                    data-pbm-text-key="label"
                                    data-pbm-search-keys="label,code,name">
                                    <input id="${budgetInputId}-text" type="search" placeholder="พิมพ์รหัสงบประมาณหรือชื่องบ..."
                                        class="withdraw-item-budget w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        autocomplete="off"
                                        value="${escapeAttr(presetBudgetLabel)}"
                                        data-pbm-searchable-select-text />
                                    <div id="${budgetInputId}-suggest"
                                        class="absolute left-0 right-0 top-full hidden max-h-60 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-2xl"
                                        style="z-index: ${dropdownZ}"
                                        data-pbm-searchable-select-suggest>
                                    </div>
                                    <input type="hidden" id="${budgetInputId}" value="${escapeAttr(presetBudgetId)}" class="withdraw-item-budget-value" data-pbm-searchable-select-hidden />
                                </div>
                                <p data-budget-hint class="mt-1 hidden text-[11px] font-medium text-emerald-700"></p>
                            </div>
                            <div class="withdraw-item-field min-w-0" data-field="category">
                                <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                    <i class="fas fa-folder-open w-3.5 text-center text-[10px] text-sky-600"></i>
                                    หมวด
                                </label>
                                <div
                                    class="relative isolate pbm-searchable-select-wrapper"
                                    data-pbm-searchable-select-wrapper="1"
                                    data-pbm-options-key="${categoryOptionsKey}"
                                    data-pbm-value-key="code"
                                    data-pbm-text-key="label">
                                    <input id="${categoryInputId}-text" type="search" placeholder="เลือกงบประมาณก่อน"
                                        class="withdraw-item-category w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        autocomplete="off"
                                        data-pbm-searchable-select-text />
                                    <div id="${categoryInputId}-suggest"
                                        class="absolute left-0 right-0 top-full hidden max-h-60 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-2xl"
                                        style="z-index: ${dropdownZ}"
                                        data-pbm-searchable-select-suggest>
                                    </div>
                                    <input type="hidden" id="${categoryInputId}" value="" class="withdraw-item-category-value" data-pbm-searchable-select-hidden />
                                </div>
                            </div>
                            <div class="withdraw-item-field min-w-0" data-field="expense">
                                <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                    <i class="fas fa-list w-3.5 text-center text-[10px] text-violet-600"></i>
                                    รายการ
                                </label>
                                <div
                                    data-expense-wrap
                                    class="relative isolate pbm-searchable-select-wrapper transition-opacity"
                                    data-pbm-searchable-select-wrapper="1"
                                    data-pbm-options-key="${expenseOptionsKey}"
                                    data-pbm-value-key="id"
                                    data-pbm-text-key="label">
                                    <input id="${expenseInputId}-text" type="search" placeholder="เลือกหมวดก่อน"
                                        class="withdraw-item-name w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        autocomplete="off"
                                        disabled
                                        data-pbm-searchable-select-text />
                                    <div id="${expenseInputId}-suggest"
                                        class="absolute left-0 right-0 top-full hidden max-h-60 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-2xl"
                                        style="z-index: ${dropdownZ}"
                                        data-pbm-searchable-select-suggest>
                                    </div>
                                    <input type="hidden" id="${expenseInputId}" value="" class="withdraw-item-name-value" data-pbm-searchable-select-hidden />
                                </div>
                            </div>
                            <div class="withdraw-item-field min-w-0" data-field="amount">
                                <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                    <i class="fas fa-coins w-3.5 text-center text-[10px] text-amber-600"></i>
                                    จำนวนเงิน (บาท)
                                </label>
                                <input type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00"
                                    class="withdraw-item-amount w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-right text-sm font-semibold tabular-nums text-slate-800 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                            </div>
                        </div>
                    `;

                    itemRowsContainer.appendChild(row);
                    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    window.PBMSearchableDropdown?.setOptions?.(budgetOptionsKey, budgetSourceOptions);
                    window.PBMSearchableDropdown?.setStatus?.(
                        budgetOptionsKey,
                        budgetSourceOptions.length === 0 ? 'ไม่พบข้อมูลงบประมาณ' : ''
                    );
                    window.PBMSearchableDropdown?.setOptions?.(expenseOptionsKey, []);
                    window.PBMSearchableDropdown?.setStatus?.(expenseOptionsKey, 'เลือกหมวดก่อน');
                    window.PBMSearchableDropdown?.initAll?.();

                    const budgetHiddenInput = row.querySelector('.withdraw-item-budget-value');
                    const categoryHiddenInput = row.querySelector('.withdraw-item-category-value');
                    const categoryTextInput = row.querySelector('.withdraw-item-category');
                    const expenseHiddenInput = row.querySelector('.withdraw-item-name-value');
                    const expenseTextInput = row.querySelector('.withdraw-item-name');
                    const amountInput = row.querySelector('.withdraw-item-amount');
                    const onBudgetSelectionChange = () => {
                        syncParagraph2WithBudgetRows();
                        syncItemRowCategoryOptions(row, { resetSelection: true });
                    };
                    budgetHiddenInput?.addEventListener('input', onBudgetSelectionChange);
                    budgetHiddenInput?.addEventListener('change', onBudgetSelectionChange);
                    syncItemRowCategoryOptions(row);
                    const populateExpenseSubitems = (selectedCategoryCode, presetExpenseId = '', presetExpenseLabel = '') => {
                        const categoryCode = String(selectedCategoryCode || '').trim();
                        const options = categoryCode !== '' ? (expenseSubitemsByCategory.get(categoryCode) || []) : [];
                        window.PBMSearchableDropdown?.setOptions?.(expenseOptionsKey, options.map((item) => ({
                            id: String(item.id || '').trim(),
                            label: String(item.label || '').trim(),
                        })));
                        window.PBMSearchableDropdown?.setStatus?.(expenseOptionsKey, options.length > 0 ? '' : (categoryCode !== '' ? 'ไม่พบรายการย่อยของหมวดนี้' : 'เลือกหมวดก่อน'));
                        if (expenseHiddenInput) {
                            expenseHiddenInput.value = '';
                            expenseHiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                            expenseHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (expenseTextInput) expenseTextInput.value = '';
                        if (presetExpenseId && options.some((item) => String(item.id || '') === String(presetExpenseId))) {
                            if (expenseHiddenInput) {
                                expenseHiddenInput.value = String(presetExpenseId);
                                expenseHiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                                expenseHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        } else if (presetExpenseLabel && expenseTextInput) {
                            expenseTextInput.value = String(presetExpenseLabel);
                        }
                        window.PBMSearchableDropdown?.initAll?.();
                        refreshItemRowVisualState(row);
                    };
                    row.__populateExpenseSubitems = populateExpenseSubitems;
                    categoryHiddenInput?.addEventListener('change', () => {
                        populateExpenseSubitems(String(categoryHiddenInput?.value || '').trim());
                        refreshItemRowVisualState(row);
                    });
                    expenseHiddenInput?.addEventListener('change', () => refreshItemRowVisualState(row));
                    expenseTextInput?.addEventListener('input', () => refreshItemRowVisualState(row));
                    amountInput?.addEventListener('input', () => refreshItemRowVisualState(row));

                    if (amountInput && preset.amount !== undefined && preset.amount !== null && preset.amount !== '') {
                        amountInput.value = String(preset.amount || '');
                    }
                    if (budgetHiddenInput && presetBudgetId !== '') {
                        budgetHiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                        budgetHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    syncItemRowCategoryOptions(row);

                    const presetCategoryCode = resolveCategoryOptionCode(preset.category_code || preset.category || '');
                    const allowedCategories = getCategoryOptionsForBudget(String(budgetHiddenInput?.value || presetBudgetId || '').trim());
                    const presetAllowed = allowedCategories.some((item) => String(item.code || '').trim() === presetCategoryCode);
                    if (categoryHiddenInput && presetCategoryCode !== '' && presetAllowed) {
                        categoryHiddenInput.value = presetCategoryCode;
                        if (categoryTextInput) {
                            const selectedOption = allowedCategories.find((option) => String(option.code || '').trim() === presetCategoryCode)
                                || categoryOptions.find((option) => String(option.code || '').trim() === presetCategoryCode);
                            categoryTextInput.value = String(selectedOption?.label || selectedOption?.name || '').trim();
                        }
                        categoryHiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                        categoryHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    const presetExpenseChartId = String(preset.expense_chart_id || '').trim();
                    if (presetCategoryCode !== '' && presetAllowed) {
                        populateExpenseSubitems(presetCategoryCode, presetExpenseChartId, String(preset.name || ''));
                    }

                    refreshItemRowVisualState(row);
                    refreshRowIndex();
                    refreshTotal();
                };

                document.querySelector('[data-action="withdraw-add-item-empty"]')?.addEventListener('click', () => createItemRow());
                document.querySelector('[data-action="withdraw-add-item-footer"]')?.addEventListener('click', () => createItemRow());
                addActorRowButton?.addEventListener('click', () => createActorRow());

                itemRowsContainer.addEventListener('input', (event) => {
                    const card = event.target.closest('.withdraw-item-card');
                    if (event.target.classList.contains('withdraw-item-category') || event.target.classList.contains('withdraw-item-category-value')) {
                        syncDocumentNumber();
                    }
                    if (event.target.classList.contains('withdraw-item-budget') || event.target.classList.contains('withdraw-item-budget-value')) {
                        if (card) syncItemRowCategoryOptions(card, { resetSelection: true });
                        syncParagraph2WithBudgetRows();
                    }
                    if (event.target.classList.contains('withdraw-item-amount')) {
                        refreshTotal();
                    } else if (card) {
                        refreshItemRowVisualState(card);
                    }
                });
                itemRowsContainer.addEventListener('change', (event) => {
                    const card = event.target.closest('.withdraw-item-card');
                    if (event.target.classList.contains('withdraw-item-category') || event.target.classList.contains('withdraw-item-category-value')) {
                        syncDocumentNumber();
                    }
                    if (event.target.classList.contains('withdraw-item-budget') || event.target.classList.contains('withdraw-item-budget-value')) {
                        if (card) syncItemRowCategoryOptions(card, { resetSelection: true });
                        syncParagraph2WithBudgetRows();
                    }
                    if (card) refreshItemRowVisualState(card);
                });

                itemRowsContainer.addEventListener('click', (event) => {
                    const duplicateButton = event.target.closest('.withdraw-duplicate-item');
                    if (duplicateButton) {
                        const card = duplicateButton.closest('.withdraw-item-card');
                        createItemRow(collectItemRowPreset(card));
                        return;
                    }
                    const removeButton = event.target.closest('.withdraw-remove-item');
                    if (!removeButton) return;
                    pendingDeleteItemRowElement = removeButton.closest('.withdraw-item-card');
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-item-row-delete-confirm-modal' }));
                });

                actorRowsContainer?.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('.withdraw-remove-actor');
                    if (!removeButton) return;
                    removeButton.closest('.withdraw-actor-row')?.remove();
                    if (actorRowsContainer.querySelectorAll('.withdraw-actor-row').length === 0) {
                        actorRowsContainer.classList.add('hidden');
                    }
                });

                const bootstrapItemRows = async () => {
                    await loadPayListOptions();
                    await loadBudgetSourceOptions();
                    createItemRow();
                };

                bootstrapItemRows();
                populateGeneralTableFilters(generalTableRows);
                applyGeneralTableFilters();

                filterFiscalYearSelect?.addEventListener('change', applyGeneralTableFilters);
                filterMonthSelect?.addEventListener('change', applyGeneralTableFilters);
                filterStatusSelect?.addEventListener('change', applyGeneralTableFilters);
                filterTypeSelect?.addEventListener('change', applyGeneralTableFilters);
                filterSearchInput?.addEventListener('input', applyGeneralTableFilters);

                const groupUploadedFilesByKey = (uploadedFiles = []) => {
                    const map = new Map();
                    (Array.isArray(uploadedFiles) ? uploadedFiles : []).forEach((file) => {
                        const key = `${String(file.type || '').trim()}::${String(file.item || '').trim()}`;
                        if (!map.has(key)) {
                            map.set(key, []);
                        }
                        map.get(key).push(file);
                    });
                    return map;
                };

                const getActiveUploadedFilesMap = () => {
                    const map = new Map();
                    currentUploadedFilesByKey.forEach((files, key) => {
                        const activeFiles = (Array.isArray(files) ? files : []).filter((file) => {
                            const fileId = Number(file.file_id || 0);
                            return fileId <= 0 || !pendingRemoveFileIds.has(fileId);
                        });
                        if (activeFiles.length > 0) {
                            map.set(key, activeFiles);
                        }
                    });
                    return map;
                };

                const buildExistingFileRow = (file) => {
                    const fileId = Number(file.file_id || 0);
                    const fileName = String(file.name || 'เปิดไฟล์');
                    const fileUrl = String(file.url || '').trim();
                    return `
                        <div class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5" data-existing-file-row data-file-id="${fileId}">
                            ${fileUrl !== ''
                                ? `<a href="${fileUrl}" target="_blank" class="text-xs font-medium text-sky-700 underline decoration-sky-300 underline-offset-4 hover:text-sky-800">${fileName}</a>`
                                : `<span class="text-xs text-slate-600">${fileName}</span>`}
                            <button type="button" data-remove-existing-file
                                class="ml-auto rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100">
                                ลบ
                            </button>
                        </div>
                    `;
                };

                const buildNewFileInputRow = (itemType, itemId, canRemove = false) => `
                    <div class="flex items-start gap-2" data-new-file-row>
                        <input type="file" name="withdraw_documents[${itemType}][${itemId}][]"
                            class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" />
                        ${canRemove ? `<button type="button" data-remove-new-file-input
                            class="shrink-0 rounded-md border border-rose-200 bg-rose-50 px-2 py-1.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100">
                            ลบ
                        </button>` : ''}
                    </div>
                `;

                const buildSubitemUploadCard = (checkbox, index, existingFiles = []) => {
                    const itemType = checkbox.dataset.type || '';
                    const itemId = checkbox.dataset.itemId || checkbox.value;
                    const itemLabel = checkbox.dataset.label || '';
                    const existingRows = (Array.isArray(existingFiles) ? existingFiles : [])
                        .map((file) => buildExistingFileRow(file))
                        .join('');
                    return `
                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3" data-subitem-upload-card data-subitem-key="${itemType}::${itemId}">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <label class="block text-xs font-semibold text-slate-700">${index + 1}. ${itemLabel}</label>
                                <button type="button" data-add-file-input
                                    class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100">
                                    + เพิ่มไฟล์
                                </button>
                            </div>
                            ${existingRows ? `<div class="mb-2 space-y-1" data-existing-files>${existingRows}</div>` : ''}
                            <div class="space-y-2" data-new-file-inputs>
                                ${buildNewFileInputRow(itemType, itemId, false)}
                            </div>
                        </div>
                    `;
                };

                const initSubitemUploadHandlers = () => {
                    if (!documentUploadsElement || documentUploadsElement.dataset.subitemUploadBound === '1') {
                        return;
                    }
                    documentUploadsElement.dataset.subitemUploadBound = '1';
                    documentUploadsElement.addEventListener('click', (event) => {
                        const addButton = event.target.closest('[data-add-file-input]');
                        if (addButton) {
                            const card = addButton.closest('[data-subitem-upload-card]');
                            const inputsWrap = card?.querySelector('[data-new-file-inputs]');
                            const cardKey = String(card?.dataset.subitemKey || '');
                            const separatorIndex = cardKey.indexOf('::');
                            if (!inputsWrap || separatorIndex < 0) return;
                            const itemType = cardKey.slice(0, separatorIndex);
                            const itemId = cardKey.slice(separatorIndex + 2);
                            inputsWrap.insertAdjacentHTML('beforeend', buildNewFileInputRow(itemType, itemId, true));
                            return;
                        }

                        const removeNewButton = event.target.closest('[data-remove-new-file-input]');
                        if (removeNewButton) {
                            const row = removeNewButton.closest('[data-new-file-row]');
                            const inputsWrap = row?.closest('[data-new-file-inputs]');
                            if (!row || !inputsWrap) return;
                            const rows = inputsWrap.querySelectorAll('[data-new-file-row]');
                            if (rows.length <= 1) return;
                            row.remove();
                            return;
                        }

                        const removeExistingButton = event.target.closest('[data-remove-existing-file]');
                        if (removeExistingButton) {
                            const row = removeExistingButton.closest('[data-existing-file-row]');
                            const fileId = Number(row?.dataset.fileId || 0);
                            if (fileId <= 0 || !row) return;
                            pendingRemoveFileIds.add(fileId);
                            row.classList.add('opacity-50');
                            removeExistingButton.disabled = true;
                            removeExistingButton.textContent = 'จะลบเมื่อบันทึก';
                        }
                    });
                };

                const renderUploadInputs = (uploadedFilesByKey = null) => {
                    if (!documentUploadsElement || !documentUploadsContainer || !documentSubitemsElement) return;
                    const filesMap = uploadedFilesByKey instanceof Map ? uploadedFilesByKey : getActiveUploadedFilesMap();
                    const checkedItems = Array.from(documentSubitemsElement.querySelectorAll('input[type="checkbox"]:checked'));
                    if (checkedItems.length === 0) {
                        documentUploadsElement.innerHTML = '';
                        documentUploadsContainer.classList.add('hidden');
                        return;
                    }

                    documentUploadsElement.innerHTML = checkedItems.map((checkbox, index) => {
                        const itemType = checkbox.dataset.type || '';
                        const itemId = checkbox.dataset.itemId || checkbox.value;
                        const key = `${itemType}::${itemId}`;
                        return buildSubitemUploadCard(checkbox, index, filesMap.get(key) || []);
                    }).join('');
                    initSubitemUploadHandlers();
                    documentUploadsContainer.classList.remove('hidden');
                };

                if (documentTypeWrapper && documentSubitemsContainer && documentSubitemsElement && documentUploadsContainer && documentUploadsElement) {
                    initSubitemUploadHandlers();
                    const renderTypeOptions = (types) => {
                        if (!Array.isArray(types) || types.length === 0) {
                            documentTypeWrapper.innerHTML = `<div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-500">ไม่พบประเภทไฟล์จากการตั้งค่า</div>`;
                            return;
                        }

                        documentTypeWrapper.innerHTML = types.map((type) => `
                            <label class="flex w-full items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/40">
                                <input type="checkbox" value="${type.code}" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                <span>${type.name}</span>
                            </label>
                        `).join('');
                    };

                    const loadDocumentOptions = async () => {
                        const json = await fetchJson(documentOptionsApi);
                        const types = Array.isArray(json?.data?.types) ? json.data.types : [];
                        const items = Array.isArray(json?.data?.items) ? json.data.items : [];

                        withdrawDocumentTypeMap = {};
                        withdrawDocumentTypeLabels = {};

                        types.forEach((type) => {
                            const code = String(type.code || '').trim();
                            const name = String(type.name || '').trim();
                            if (code === '' || name === '') return;
                            withdrawDocumentTypeMap[code] = [];
                            withdrawDocumentTypeLabels[code] = name;
                        });

                        items.forEach((item) => {
                            const id = String(item.id || '').trim();
                            const typeCode = String(item.type_code || '').trim();
                            const name = String(item.name || '').trim();
                            if (id === '' || typeCode === '' || name === '') return;
                            if (!Array.isArray(withdrawDocumentTypeMap[typeCode])) {
                                withdrawDocumentTypeMap[typeCode] = [];
                            }
                            withdrawDocumentTypeMap[typeCode].push({ id, label: name });
                        });

                        const selectableTypes = types.filter((type) => {
                            const code = String(type.code || '').trim();
                            return code !== '' && (withdrawDocumentTypeMap[code] || []).length > 0;
                        });

                        renderTypeOptions(selectableTypes);
                    };

                    const renderSubitems = () => {
                        const previousCheckedValues = new Set(
                            Array.from(documentSubitemsElement.querySelectorAll('input[type="checkbox"]:checked'))
                                .map((checkbox) => String(checkbox.value || ''))
                        );
                        const selectedTypes = Array.from(documentTypeWrapper.querySelectorAll('input[type="checkbox"]:checked'))
                            .map((checkbox) => String(checkbox.value || '').trim())
                            .filter((value) => value !== '');
                        const items = selectedTypes.flatMap((type) => {
                            const typeItems = withdrawDocumentTypeMap[type] || [];
                            return typeItems.map((item) => ({
                                ...item,
                                type,
                            }));
                        });

                        if (items.length === 0) {
                            documentSubitemsElement.innerHTML = '';
                            documentUploadsElement.innerHTML = '';
                            documentSubitemsContainer.classList.add('hidden');
                            documentUploadsContainer.classList.add('hidden');
                            return;
                        }

                        documentSubitemsElement.innerHTML = items.map((item) => `
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/40">
                                <input type="checkbox" value="${item.type}::${item.id}" ${previousCheckedValues.has(`${item.type}::${item.id}`) ? 'checked' : ''} data-type="${item.type}" data-item-id="${item.id}" data-label="${withdrawDocumentTypeLabels[item.type] ? `${withdrawDocumentTypeLabels[item.type]} - ${item.label}` : item.label}"
                                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                <span>${item.label}</span>
                            </label>
                        `).join('');

                        documentSubitemsContainer.classList.remove('hidden');
                        renderUploadInputs();
                    };

                    documentTypeWrapper.addEventListener('change', (event) => {
                        if (!event.target.matches('input[type="checkbox"]')) return;
                        renderSubitems();
                    });
                    documentSubitemsElement.addEventListener('change', (event) => {
                        if (event.target.matches('input[type="checkbox"]')) {
                            renderUploadInputs();
                        }
                    });

                    loadDocumentOptions();
                }

                const resetGeneralForm = () => {
                    if (withdrawRecordIdInput) withdrawRecordIdInput.value = '';
                    isRevisionEditMode = false;
                    if (revisionNoteText) revisionNoteText.textContent = '-';
                    revisionNoteBox?.classList.add('hidden');
                    if (withdrawSaveDraftButton) {
                        withdrawSaveDraftButton.innerHTML = '<i class="far fa-save mr-1"></i>บันทึกแบบร่าง';
                    }
                    withdrawGeneralForm?.reset();
                    if (itemRowsContainer) {
                        itemRowsContainer.innerHTML = '';
                        createItemRow();
                    }
                    if (actorRowsContainer) {
                        actorRowsContainer.innerHTML = '';
                        actorRowsContainer.classList.add('hidden');
                    }
                    documentTypeWrapper?.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                        checkbox.checked = false;
                    });
                    if (documentSubitemsElement) documentSubitemsElement.innerHTML = '';
                    if (documentUploadsElement) documentUploadsElement.innerHTML = '';
                    documentSubitemsContainer?.classList.add('hidden');
                    documentUploadsContainer?.classList.add('hidden');
                    currentUploadedFilesByKey = new Map();
                    pendingRemoveFileIds.clear();
                    if (departmentSelect && defaultDepartmentCode) {
                        departmentSelect.value = defaultDepartmentCode;
                        departmentSelect.dispatchEvent(new Event('input', { bubbles: true }));
                        departmentSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        syncDocumentNumber();
                    }
                    if (applicantStaffSelect && defaultApplicantStaffId) {
                        applicantStaffSelect.value = defaultApplicantStaffId;
                        applicantStaffSelect.dispatchEvent(new Event('input', { bubbles: true }));
                        applicantStaffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (salaryTypeInput) salaryTypeInput.value = 'salary_civil_servant';
                    if (salaryTypeLabel) salaryTypeLabel.textContent = salaryTypeOptions.salary_civil_servant;
                    setWithdrawDateToToday();
                    refreshTotal();
                };

                openCreateModalButton?.addEventListener('click', () => {
                    resetGeneralForm();
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-salary-type-modal' }));
                });
                const memoTemplateApi = @json(url('/withdraw/api/memo-template'));

                const memoFormDirectChild = (node) => {
                    const formEl = document.getElementById('withdraw-general-form');
                    let current = node || null;
                    while (current && current.parentElement && current.parentElement !== formEl) {
                        current = current.parentElement;
                    }
                    return current && current.parentElement === formEl ? current : null;
                };

                const getMemoFormBlockElement = (blockKey) => {
                    const key = String(blockKey || '');
                    const formEl = document.getElementById('withdraw-general-form');
                    const byDataAttr = formEl?.querySelector(`[data-memo-block="${key}"]`)
                        || document.querySelector(`[data-memo-block="${key}"]`);
                    if (byDataAttr) return byDataAttr;
                    if (key === 'paragraph1') {
                        return document.getElementById('withdraw-paragraph-1')?.closest('.md\\:col-span-2') || null;
                    }
                    if (key === 'paragraph2') {
                        return document.getElementById('withdraw-paragraph-2')?.closest('.md\\:col-span-2') || null;
                    }
                    if (key === 'paragraph3') {
                        return document.getElementById('withdraw-paragraph-3')?.closest('.md\\:col-span-2') || null;
                    }
                    if (key === 'paragraph4') {
                        return document.getElementById('withdraw-paragraph-4')?.closest('.md\\:col-span-2') || null;
                    }
                    if (key === 'items') {
                        return document.querySelector('[data-memo-block="items"]') || null;
                    }
                    return null;
                };

                const ensureBudgetAfterItems = () => {
                    const itemsEl = document.querySelector('[data-memo-block="items"]');
                    const budgetEl = document.querySelector('[data-memo-block="budget"]');
                    if (!itemsEl || !budgetEl) return;
                    if (itemsEl.nextElementSibling !== budgetEl) {
                        itemsEl.insertAdjacentElement('afterend', budgetEl);
                    }
                };

                const reorderMemoFormBlocks = (blockOrder) => {
                    const formEl = document.getElementById('withdraw-general-form');
                    if (!formEl || !Array.isArray(blockOrder) || blockOrder.length === 0) return;
                    const insertBeforeNode = memoFormDirectChild(document.getElementById('withdraw-applicant-staff'));
                    blockOrder.forEach((blockKey) => {
                        const blockEl = getMemoFormBlockElement(String(blockKey || ''));
                        if (!blockEl || blockEl.parentElement !== formEl || blockEl === insertBeforeNode) return;
                        if (insertBeforeNode && insertBeforeNode.parentElement === formEl) {
                            formEl.insertBefore(blockEl, insertBeforeNode);
                        } else {
                            formEl.appendChild(blockEl);
                        }
                    });
                    ensureBudgetAfterItems();
                };

                const isMemoParagraphEnabled = (template, key) => {
                    const flags = template?.paragraph_enabled;
                    if (!flags || typeof flags !== 'object' || flags[key] === undefined) return true;
                    const value = flags[key];
                    return value !== false && value !== 0 && value !== '0' && value !== 'false';
                };

                const applyMemoParagraphVisibility = (template) => {
                    ['paragraph1', 'paragraph2', 'paragraph3', 'paragraph4'].forEach((key) => {
                        const blockEl = getMemoFormBlockElement(key);
                        if (!blockEl) return;
                        const enabled = isMemoParagraphEnabled(template, key);
                        blockEl.classList.toggle('hidden', !enabled);
                        blockEl.toggleAttribute('hidden', !enabled);
                        if (enabled) {
                            blockEl.style.removeProperty('display');
                        } else {
                            blockEl.style.setProperty('display', 'none', 'important');
                            if (String(withdrawRecordIdInput?.value || '').trim() === '') {
                                const input = blockEl.querySelector('textarea');
                                if (input) input.value = '';
                            }
                        }
                    });
                };

                const applyMemoTemplate = async (kind, subType) => {
                    try {
                        const url = new URL(memoTemplateApi, window.location.origin);
                        url.searchParams.set('kind', kind);
                        url.searchParams.set('sub_type', subType);
                        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        const template = result?.data;
                        if (!template) return;

                        if (withdrawSubjectInput) withdrawSubjectInput.value = String(template.subject || '');
                        if (withdrawToInput) withdrawToInput.value = String(template.to || '');
                        if (withdrawParagraph1Input) withdrawParagraph1Input.value = String(template.paragraph1 || '');
                        if (withdrawParagraph2Input) withdrawParagraph2Input.value = String(template.paragraph2 || '');
                        if (withdrawParagraph3Input) withdrawParagraph3Input.value = String(template.paragraph3 || '');
                        if (withdrawParagraph4Input) withdrawParagraph4Input.value = String(template.paragraph4 || '');
                        try {
                            reorderMemoFormBlocks(template.block_order || []);
                        } catch (reorderError) {
                            // keep going so a disabled paragraph is still hidden
                        }
                        applyMemoParagraphVisibility(template);

                        setExtraActorsSectionVisible(template.process_actors_enabled === true);
                        if (String(withdrawDateInput?.value || '').trim() === '') {
                            setWithdrawDateToToday();
                        } else {
                            syncMemoMonthPeriodFields();
                        }
                        syncMemoParagraph1AmountField();
                    } catch (error) {
                        // keep hardcoded defaults when template API is unavailable
                    }
                };

                document.querySelectorAll('[data-salary-type-option]').forEach((button) => {
                    button.addEventListener('click', async () => {
                        const selectedType = String(button.getAttribute('data-salary-type-option') || '').trim();
                        const selectedTypeKey = normalizeSalaryRequestTypeKey(selectedType);
                        if (!selectedType || !salaryTypeOptions[selectedTypeKey]) return;
                        if (salaryTypeInput) salaryTypeInput.value = selectedTypeKey;
                        if (salaryTypeLabel) salaryTypeLabel.textContent = salaryTypeOptions[selectedTypeKey];
                        await applyMemoTemplate('salary', selectedTypeKey);
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-salary-type-modal' }));
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-request-modal' }));
                    });
                });
                window.addEventListener('close-modal', (event) => {
                    if (String(event?.detail || '') === 'withdraw-request-modal') {
                        resetGeneralForm();
                    }
                });

                const setSelectedDocuments = (documents, uploadedFiles = []) => {
                    const rows = Array.isArray(documents) ? documents : [];
                    currentUploadedFilesByKey = groupUploadedFilesByKey(uploadedFiles);
                    pendingRemoveFileIds.clear();
                    const uploadedMap = getActiveUploadedFilesMap();
                    const selectedTypes = Array.from(new Set(rows.map((item) => String(item.type || '').trim()).filter(Boolean)));

                    documentTypeWrapper?.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                        checkbox.checked = selectedTypes.includes(String(checkbox.value || '').trim());
                    });

                    const selectedDocumentKeys = new Set(
                        rows.map((item) => `${String(item.type || '').trim()}::${String(item.item_id || '').trim()}`)
                    );

                    const selectedTypeValues = Array.from(documentTypeWrapper?.querySelectorAll('input[type="checkbox"]:checked') || [])
                        .map((checkbox) => String(checkbox.value || '').trim())
                        .filter(Boolean);
                    const items = selectedTypeValues.flatMap((type) => {
                        const typeItems = withdrawDocumentTypeMap[type] || [];
                        return typeItems.map((item) => ({ ...item, type }));
                    });

                    documentSubitemsElement.innerHTML = items.map((item) => `
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/40">
                            <input type="checkbox" value="${item.type}::${item.id}" ${selectedDocumentKeys.has(`${item.type}::${item.id}`) ? 'checked' : ''} data-type="${item.type}" data-item-id="${item.id}" data-label="${withdrawDocumentTypeLabels[item.type] ? `${withdrawDocumentTypeLabels[item.type]} - ${item.label}` : item.label}"
                                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            <span>${item.label}</span>
                        </label>
                    `).join('');

                    if (items.length > 0) {
                        documentSubitemsContainer.classList.remove('hidden');
                    } else {
                        documentSubitemsContainer.classList.add('hidden');
                    }

                    renderUploadInputs(uploadedMap);
                };

                const openEditModalById = async (id) => {
                    try {
                        await loadPayListOptions();
                        await loadBudgetSourceOptions();
                        const response = await fetch(`${generalDetailApiBase}/${id}`, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        if (result?.status !== 'success') {
                            window.toast?.error?.(result?.message || 'ไม่พบข้อมูลที่ต้องการแก้ไข', { timer: 1400 });
                            return;
                        }

                        const row = result.data || {};
                        const revisionComment = String(row.revision_comment || '').trim();
                        const rawStatus = String(row.raw_status || '').trim();
                        isRevisionEditMode = rawStatus === 'pending_revision';
                        if (revisionComment !== '') {
                            if (revisionNoteText) revisionNoteText.textContent = revisionComment;
                            revisionNoteBox?.classList.remove('hidden');
                        } else {
                            if (revisionNoteText) revisionNoteText.textContent = '-';
                            revisionNoteBox?.classList.add('hidden');
                        }
                        if (withdrawSaveDraftButton) {
                            withdrawSaveDraftButton.innerHTML = isRevisionEditMode
                                ? '<i class="fas fa-rotate-right mr-1"></i>บันทึกการแก้ไข'
                                : '<i class="far fa-save mr-1"></i>บันทึกแบบร่าง';
                        }
                        if (withdrawRecordIdInput) withdrawRecordIdInput.value = String(row.id || '');
                        const requestTypeKey = normalizeSalaryRequestTypeKey(row.salary_request_type);
                        if (salaryTypeInput) salaryTypeInput.value = requestTypeKey;
                        if (salaryTypeLabel) {
                            salaryTypeLabel.textContent = salaryRequestTypeLabel(requestTypeKey);
                        }
                        if (departmentSelect) {
                            departmentSelect.value = String(row.department_code || '');
                            departmentSelect.dispatchEvent(new Event('input', { bubbles: true }));
                            departmentSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (documentNumberInput) documentNumberInput.value = String(row.document_number || '');
                        const documentDate = String(row.document_date || '').trim();
                        if (documentDate !== '') {
                            setWithdrawDateValue(documentDate, false);
                        } else {
                            setWithdrawDateValue('', false);
                        }
                        if (withdrawSubjectInput) withdrawSubjectInput.value = String(row.subject || '');
                        if (withdrawToInput) withdrawToInput.value = String(row.to || '');
                        if (withdrawParagraph1Input) withdrawParagraph1Input.value = String(row.paragraph1 || '');
                        if (withdrawParagraph2Input) withdrawParagraph2Input.value = String(row.paragraph2 || '');
                        if (withdrawParagraph3Input) withdrawParagraph3Input.value = String(row.paragraph3 || '');
                        if (withdrawParagraph4Input) withdrawParagraph4Input.value = String(row.paragraph4 || '');
                        applyMemoParagraphVisibility(row);
                        if (applicantStaffSelect) {
                            applicantStaffSelect.value = String(row.applicant_staff_id || '');
                            applicantStaffSelect.dispatchEvent(new Event('input', { bubbles: true }));
                            applicantStaffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        itemRowsContainer.innerHTML = '';
                        const rowItems = Array.isArray(row.items) ? row.items : [];
                        if (rowItems.length === 0) {
                            createItemRow();
                        } else {
                            rowItems.forEach((item) => {
                                const itemBudgetId = Number(item.budget_manage_id || 0) > 0
                                    ? Number(item.budget_manage_id)
                                    : Number(row.budget_manage_id || 0);
                                createItemRow({
                                    budget_manage_id: itemBudgetId,
                                    category_code: item.category_code || item.category || '',
                                    category: item.category || '',
                                    expense_chart_id: item.expense_chart_id || '',
                                    name: item.name || '',
                                    amount: item.amount || '',
                                });
                            });
                        }
                        refreshTotal();
                        syncDocumentNumber();

                        if (actorRowsContainer) {
                            actorRowsContainer.innerHTML = '';
                            const actorRows = Array.isArray(row.process_actors) ? row.process_actors : [];
                            if (actorRows.length > 0) {
                                setExtraActorsSectionVisible(true);
                                actorRows.forEach((actor) => {
                                    createActorRow({
                                        staff_id: String(actor.staff_id || '').trim(),
                                        status: String(actor.status || '').trim(),
                                    });
                                });
                            } else {
                                actorRowsContainer.classList.add('hidden');
                            }
                        }

                        setSelectedDocuments(row.documents || [], row.uploaded_files || []);
                        if (String(row.document_date || '').trim() !== '') {
                            syncMemoMonthPeriodFields();
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-request-modal' }));
                    } catch (error) {
                        window.toast?.error?.('ไม่สามารถโหลดข้อมูลเพื่อแก้ไขได้', { timer: 1400 });
                    }
                };

                document.addEventListener('click', async (event) => {
                    const button = event.target.closest('[data-action]');
                    if (!button) return;

                    const action = String(button.dataset.action || '');
                    if (action === 'one-planning-response') {
                        event.preventDefault();
                        openOnePlanningResponseModal(String(button.dataset.budgetCode || ''));
                        return;
                    }

                    const id = Number(button.dataset.id || 0);
                        if (!action || id <= 0) return;

                        if (action === 'delete') {
                            pendingDeleteId = id;
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-general-delete-confirm-modal' }));
                            return;
                        }

                        if (action === 'proceed') {
                            pendingProceedId = id;
                            pendingProceedAction = String(button.dataset.proceedAction || 'proceed').trim() === 'cancel' ? 'cancel' : 'proceed';
                            const row = findSalaryListRowById(id) || {};
                            if (proceedDetailDate) proceedDetailDate.textContent = String(row.date || '-').trim() || '-';
                            if (proceedDetailSubject) proceedDetailSubject.textContent = String(row.subject || '-').trim() || '-';
                            if (proceedDetailApplicant) proceedDetailApplicant.textContent = String(row.manager || '-').trim() || '-';
                            if (proceedDetailAmount) proceedDetailAmount.textContent = Number(row.amount || 0).toLocaleString('th-TH');
                            if (proceedConfirmTitle) {
                                proceedConfirmTitle.textContent = pendingProceedAction === 'cancel'
                                    ? 'ยืนยันยกเลิกรายการเบิกจ่าย?'
                                    : 'ยืนยันดำเนินการเบิกจ่าย?';
                            }
                            if (proceedConfirmDescription) {
                                proceedConfirmDescription.textContent = pendingProceedAction === 'cancel'
                                    ? 'ระบบจะยกเลิกรายการ และเปลี่ยนสถานะเป็นยกเลิกรายการ'
                                    : 'โปรดตรวจสอบรายละเอียดก่อนยืนยันดำเนินการ';
                            }
                            if (proceedConfirmSubmitButton) {
                                proceedConfirmSubmitButton.textContent = pendingProceedAction === 'cancel' ? 'ยืนยันยกเลิก' : 'ยืนยัน';
                                proceedConfirmSubmitButton.className = pendingProceedAction === 'cancel'
                                    ? 'inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-rose-600 to-red-500'
                                    : 'inline-flex items-center justify-center h-10 px-5 font-bold text-white text-xs uppercase rounded-lg bg-gradient-to-tl from-blue-600 to-green-500';
                            }

                            const fallbackCode = String(row.code || '-').trim() || '-';
                            resetProceedDocumentNumberPreview();
                            if (proceedDetailCode) proceedDetailCode.textContent = fallbackCode;
                            if (pendingProceedAction === 'proceed') {
                                try {
                                    const detailResponse = await fetch(`${generalDetailApiBase}/${id}`, { headers: { Accept: 'application/json' } });
                                    const detailJson = await detailResponse.json();
                                    if (detailJson?.status !== 'success' || !detailJson?.data) {
                                        throw new Error('detail_load_failed');
                                    }
                                    const detail = detailJson.data;
                                    const ptsSecondDisplay = String(detail.pts_document_number_display || '').trim();
                                    if (salaryPtsUsesDualDocumentNumbers(detail) && ptsSecondDisplay !== '') {
                                        if (proceedDetailCodeLabel) {
                                            proceedDetailCodeLabel.textContent = 'เลขที่ (ฉบับที่ 1):';
                                        }
                                        if (proceedDetailCode) {
                                            proceedDetailCode.textContent = String(detail.document_number_display || fallbackCode).trim() || fallbackCode;
                                        }
                                        if (proceedDetailCode2Row) {
                                            proceedDetailCode2Row.classList.remove('hidden');
                                        }
                                        if (proceedDetailCode2) {
                                            proceedDetailCode2.textContent = ptsSecondDisplay;
                                        }
                                    } else {
                                        const existingRunningNo = Number(detail.running_no || 0);
                                        const existingDisplay = String(detail.document_number_display || '').trim();
                                        if (existingRunningNo > 0 && existingDisplay !== '') {
                                            if (proceedDetailCode) proceedDetailCode.textContent = existingDisplay;
                                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-general-proceed-confirm-modal' }));
                                            return;
                                        }
                                        const documentDate = String(detail.document_date || '').trim();
                                        const codePrefix = resolveProceedDocumentNumberPrefix(detail);
                                        const runningNoApiUrl = new URL(generalNextRunningNoApi, window.location.origin);
                                        runningNoApiUrl.searchParams.set('withdraw_type', 'salary');
                                        const departmentCode = String(detail.department_code || '').trim();
                                        if (departmentCode !== '') {
                                            runningNoApiUrl.searchParams.set('department_code', departmentCode);
                                        }
                                        if (documentDate !== '') {
                                            runningNoApiUrl.searchParams.set('document_date', documentDate);
                                        }
                                        if (codePrefix !== '') {
                                            runningNoApiUrl.searchParams.set('document_number', codePrefix);
                                        }
                                        const runningResponse = await fetch(runningNoApiUrl.toString(), { headers: { Accept: 'application/json' } });
                                        const runningJson = await runningResponse.json();
                                        const nextRunningNo = Number(runningJson?.data?.running_no || 0);
                                        const centralBase = String(runningJson?.data?.document_number || '').trim();
                                        setProceedDocumentNumberPreview(detail, centralBase !== '' ? centralBase : codePrefix, nextRunningNo);
                                    }
                                } catch (error) {
                                    // Keep fallback code from table when preview API fails.
                                }
                            }
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-general-proceed-confirm-modal' }));
                            return;
                        }

                        if (action === 'view') {
                            await window.WithdrawSalaryDetailModal?.openById?.(id)
                                || window.WithdrawGeneralDetailModal?.openById?.(id);
                            return;
                        }

                        if (action === 'edit') {
                            await openEditModalById(id);
                            return;
                        }

                        if (action === 'edit-revision') {
                            await openEditModalById(id);
                            return;
                        }

                        if (action === 'open-memo-pdf') {
                            const memoCopy = Number(button.dataset.memoCopy || 0);
                            await openMemoPdfById(id, memoCopy);
                            return;
                        }

                        if (action === 'open-esign-docs') {
                            await openSalaryEsignDocs(id);
                            return;
                        }

                        if (action === 'one-planning') {
                            await openOnePlanningConfirmById(id);
                            return;
                        }
                });

                const prefillEditId = Number(new URLSearchParams(window.location.search).get('edit_id') || 0);
                if (prefillEditId > 0) {
                    openEditModalById(prefillEditId);
                }

                if (withdrawGeneralForm) {
                    withdrawGeneralForm.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const selectedDocumentItems = Array.from(documentSubitemsElement?.querySelectorAll('input[type="checkbox"]:checked') || [])
                            .map((checkbox) => ({
                                type: String(checkbox.dataset.type || '').trim(),
                                item_id: String(checkbox.dataset.itemId || '').trim(),
                                label: String(checkbox.dataset.label || '').trim(),
                            }))
                            .filter((item) => item.type !== '' && item.item_id !== '');

                        syncParagraph2WithBudgetRows();
                        const itemCardCount = getItemRowElements().length;
                        const itemRows = collectSubmitItemRows();
                        const budgetBreakdownPayload = buildBudgetBreakdownPayload(itemRows);
                        const selectedBudgetIds = budgetBreakdownPayload.map((entry) => entry.budget_manage_id);
                        const primaryBudgetManageId = Number(selectedBudgetIds[0] || 0);

                        const processActors = Array.from(actorRowsContainer?.querySelectorAll('.withdraw-actor-row') || [])
                            .map((row) => {
                                const staffInput = row.querySelector('[data-pbm-searchable-select-hidden]');
                                const statusInput = row.querySelector('.withdraw-actor-status');
                                return {
                                    staff_id: String(staffInput?.value || '').trim(),
                                    status: String(statusInput?.value || '').trim(),
                                };
                            })
                            .filter((row) => row.staff_id !== '' || row.status !== '');

                        const requiredChecks = [
                            { label: 'ส่วนงาน', value: String(departmentSelect?.value || '').trim(), target: departmentSelect },
                            { label: 'ที่', value: String(documentNumberInput?.value || '').trim(), target: documentNumberInput },
                            { label: 'วันที่', value: String(document.getElementById('withdraw-date')?.value || '').trim(), target: document.getElementById('withdraw-date') },
                            { label: 'เรื่อง', value: String(withdrawSubjectInput?.value || '').trim(), target: withdrawSubjectInput },
                            { label: 'เรียน', value: String(withdrawToInput?.value || '').trim(), target: withdrawToInput },
                            { label: 'ย่อหน้า 1', value: String(withdrawParagraph1Input?.value || '').trim(), target: withdrawParagraph1Input },
                            { label: 'ย่อหน้า 2', value: String(withdrawParagraph2Input?.value || '').trim(), target: withdrawParagraph2Input },
                            { label: 'ย่อหน้า 3', value: String(withdrawParagraph3Input?.value || '').trim(), target: withdrawParagraph3Input },
                            { label: 'ผู้ขอเบิก', value: String(applicantStaffSelect?.value || '').trim(), target: applicantStaffSelect },
                        ];

                        const missingFields = requiredChecks.filter((field) => field.value === '');

                        if (itemCardCount === 0 || itemRows.length === 0) {
                            missingFields.push({
                                label: 'รายการเบิกจ่าย (อย่างน้อย 1 รายการ)',
                                target: itemRowsContainer,
                            });
                        } else if (itemRows.length !== itemCardCount) {
                            missingFields.push({
                                label: 'รายการเบิกจ่ายต้องกรอก งบประมาณ / หมวด / รายการ / จำนวนเงิน ให้ครบทุกแถว',
                                target: itemRowsContainer,
                            });
                        }

                        if (missingFields.length > 0) {
                            const message = missingFields.map((field) => `- ${field.label}`).join('\n');
                            window.toast?.error?.(`กรุณากรอกข้อมูลให้ครบถ้วน\n${message}`, { timer: 2600 });
                            const firstTarget = missingFields[0]?.target;
                            if (firstTarget?.focus) {
                                firstTarget.focus();
                            }
                            return;
                        }

                        const hasInvalidProcessActor = processActors.some((actor) =>
                            (actor.staff_id !== '' && actor.status === '') || (actor.staff_id === '' && actor.status !== '')
                        );
                        if (hasInvalidProcessActor) {
                            window.toast?.error?.('ผู้ตรวจสอบ/อนุมัติ เพิ่มเติม ต้องเลือกทั้งรายชื่อและสถานะดำเนินการให้ครบในแต่ละแถว', { timer: 2200 });
                            return;
                        }

                        const formData = new FormData(withdrawGeneralForm);
                        formData.append('id', String(withdrawRecordIdInput?.value || ''));
                        formData.append('department_code', String(departmentSelect?.value || '').trim());
                        formData.append('document_number', String(documentNumberInput?.value || '').trim());
                        formData.append('document_date', String(document.getElementById('withdraw-date')?.value || '').trim());
                        formData.append('subject', String(withdrawSubjectInput?.value || '').trim());
                        formData.append('to', String(withdrawToInput?.value || '').trim());
                        formData.append('paragraph1', String(withdrawParagraph1Input?.value || '').trim());
                        formData.append('paragraph2', String(withdrawParagraph2Input?.value || '').trim());
                        formData.append('paragraph3', String(withdrawParagraph3Input?.value || '').trim());
                        formData.append('paragraph4', String(withdrawParagraph4Input?.value || '').trim());
                        formData.append('applicant_staff_id', String(applicantStaffSelect?.value || '').trim());
                        formData.append('budget_manage_id', String(primaryBudgetManageId || ''));
                        formData.append('budget_manage_ids', JSON.stringify(selectedBudgetIds));
                        formData.append('budget_breakdown', JSON.stringify(budgetBreakdownPayload));
                        formData.append('items', JSON.stringify(itemRows));
                        formData.append('documents', JSON.stringify(selectedDocumentItems));
                        formData.append('process_actors', JSON.stringify(processActors));
                        formData.append('salary_request_type', normalizeSalaryRequestTypeKey(salaryTypeInput?.value));
                        formData.append('is_revision_update', isRevisionEditMode ? '1' : '0');
                        pendingRemoveFileIds.forEach((fileId) => {
                            formData.append('remove_file_ids[]', String(fileId));
                        });

                        if (withdrawSaveDraftButton) withdrawSaveDraftButton.disabled = true;

                        try {
                            const response = await fetch(saveDraftApi, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrf(),
                                    'Accept': 'application/json',
                                },
                                body: formData,
                            });
                            const result = await response.json();

                            if (result?.status === 'success') {
                                window.toast?.success?.(isRevisionEditMode ? 'บันทึกการแก้ไขสำเร็จ' : 'บันทึกแบบร่างสำเร็จ', { timer: 1200 });
                                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-request-modal' }));
                                resetGeneralForm();
                                await refreshGeneralTable();
                                await refreshApprovalPendingBadge();
                                await refreshDocumentReviewPendingBadge();
                            } else {
                                window.toast?.error?.(result?.message || 'ไม่สามารถบันทึกแบบร่างได้', { timer: 1400 });
                            }
                        } catch (error) {
                            window.toast?.error?.('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', { timer: 1400 });
                        } finally {
                            if (withdrawSaveDraftButton) withdrawSaveDraftButton.disabled = false;
                        }
                    });
                }

                window.__withdrawGeneralConfirmDelete = async () => {
                    const id = pendingDeleteId;
                    pendingDeleteId = 0;
                    try {
                        const result = await postJson(generalDeleteApi, { id });
                        if (result?.status === 'success') {
                            window.toast?.success?.('ลบข้อมูลสำเร็จ', { timer: 1000 });
                            await refreshGeneralTable();
                            await refreshApprovalPendingBadge();
                            await refreshDocumentReviewPendingBadge();
                        } else {
                            window.toast?.error?.(result?.message || 'ไม่สามารถลบข้อมูลได้', { timer: 1400 });
                        }
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-general-delete-confirm-modal' }));
                    }
                };

                window.__withdrawGeneralConfirmProceed = async () => {
                    const id = pendingProceedId;
                    pendingProceedId = 0;
                    const proceedAction = pendingProceedAction;
                    pendingProceedAction = 'proceed';
                    try {
                        const payload = proceedAction === 'cancel'
                            ? { id, action: 'cancel_proceed' }
                            : { id };
                        const result = await postJson(generalProceedApi, payload);
                        if (result?.status === 'success') {
                            if (proceedAction !== 'cancel') {
                                const mailDebug = result?.data?.mail_debug || null;
                                if (mailDebug?.attempted && mailDebug?.sent) {
                                    console.log('[Withdraw Mail Debug] ส่งอีเมลสำเร็จ', {
                                        to: mailDebug.to || '',
                                        subject: mailDebug.subject || '',
                                        message: mailDebug.message || '',
                                    });
                                } else if (mailDebug) {
                                    console.warn('[Withdraw Mail Debug] ส่งอีเมลไม่สำเร็จ', mailDebug);
                                    console.warn('[Withdraw Mail Debug] รายละเอียดแบบเต็ม', JSON.stringify(mailDebug));
                                }
                            }
                            window.toast?.success?.(
                                proceedAction === 'cancel' ? 'ยกเลิกรายการเบิกจ่ายสำเร็จ' : 'ดำเนินการเบิกจ่ายสำเร็จ',
                                { timer: 1000 }
                            );
                            await refreshGeneralTable();
                            await refreshApprovalPendingBadge();
                            await refreshDocumentReviewPendingBadge();
                        } else {
                            window.toast?.error?.(
                                result?.message || (proceedAction === 'cancel' ? 'ไม่สามารถยกเลิกรายการได้' : 'ไม่สามารถดำเนินการได้'),
                                { timer: 1400 }
                            );
                        }
                    } finally {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-general-proceed-confirm-modal' }));
                    }
                };

                window.__withdrawGeneralConfirmDeleteItemRow = () => {
                    if (pendingDeleteItemRowElement) {
                        pendingDeleteItemRowElement.remove();
                        pendingDeleteItemRowElement = null;
                        refreshRowIndex();
                        refreshTotal();
                        syncDocumentNumber();
                    }
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-item-row-delete-confirm-modal' }));
                };
            });
        </script>
    @endpush

</x-layouts.app>
