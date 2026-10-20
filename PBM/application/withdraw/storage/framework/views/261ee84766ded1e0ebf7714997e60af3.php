<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php
        $withdrawKind = trim((string) ($withdrawKind ?? 'general')) === 'project' ? 'project' : 'general';
        $allowManualCreate = array_key_exists('allowManualCreate', get_defined_vars())
            ? (bool) $allowManualCreate
            : true;
        $isProjectWithdrawPage = $withdrawKind === 'project';
        $pageHeading = $isProjectWithdrawPage ? 'รายการเบิกจ่ายเงินโครงการ' : 'รายการเบิกจ่ายทั่วไป';
        $pageDescription = $isProjectWithdrawPage
            ? 'หน้านี้แสดงรายการเบิกจ่ายเงินโครงการที่ส่งมาจากระบบโครงการ เพื่อจัดทำเอกสารเบิกจ่ายต่อในระบบนี้'
            : 'หน้านี้สำหรับสรุปและจัดการรายการเบิกจ่ายทั่วไปของระบบ withdraw';
        $formTitle = $isProjectWithdrawPage ? 'เบิกจ่ายเงินโครงการ' : 'เบิกจ่ายทั่วไป';
    ?>
    <div class="py-2">
        <div class="mx-2 flex flex-col gap-4">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50/80 to-emerald-50/50 p-4 shadow-md shadow-slate-200/50 sm:px-4 sm:py-4">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-400/15 blur-2xl sm:h-36 sm:w-36"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-emerald-200/60 to-transparent"></div>
                <div class="relative flex min-w-0 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-lg text-white shadow-lg shadow-emerald-500/30 ring-4 ring-white/80">
                            <i class="fas <?php echo e($isProjectWithdrawPage ? 'fa-diagram-project' : 'fa-list-check'); ?>" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.65rem]"><?php echo e(!empty($isReviewerApprovalMode) ? 'อนุมัติเบิกจ่ายทั่วไป' : $pageHeading); ?></h4>
                            <p class="mb-0 max-w-2xl text-sm leading-snug text-slate-500">
                                <?php echo e(!empty($isReviewerApprovalMode) ? 'หน้านี้แสดงรายการเบิกจ่ายทั่วไปที่รอการอนุมัติจากผู้ตรวจสอบ' : $pageDescription); ?>

                            </p>
                        </div>
                    </div>
                    <?php if(empty($isReviewerApprovalMode) && $allowManualCreate): ?>
                        <button type="button" id="withdraw-open-create-modal"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-request-modal' }))"
                            class="inline-flex w-fit items-center justify-center gap-2 self-start rounded-xl border border-emerald-500/40 bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-sm font-bold text-white shadow-xl shadow-emerald-500/40 ring-2 ring-emerald-200/70 transition-all hover:-translate-y-0.5 hover:scale-[1.02] hover:from-emerald-500 hover:to-teal-500 hover:shadow-2xl hover:shadow-emerald-500/45 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-emerald-300/70 lg:self-center">
                            <i class="fas fa-file-signature text-sm" aria-hidden="true"></i>
                            เพิ่มการเบิกจ่าย
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if($isProjectWithdrawPage && empty($isReviewerApprovalMode)): ?>
                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                    เพิ่มรายการเบิกจ่ายเงินโครงการได้จากระบบโครงการเท่านั้น เมื่อกด «ยืนยันและจัดทำเอกสารเบิกจ่าย» ระบบจะเปิดฟอร์มนี้พร้อมดึงรายการมาให้อัตโนมัติ
                </div>
                <div id="withdraw-handoff-expired-notice" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                    <div class="font-semibold">ไม่พบข้อมูลจากระบบโครงการ หรือลิงก์หมดอายุ</div>
                    <p class="mt-0.5 text-xs text-rose-800/90">กรุณากลับไปกดปุ่มทำรายการต่อจากระบบโครงการ</p>
                </div>
            <?php endif; ?>

            <?php
                $sampleRows = collect($withdrawRows ?? []);
                $isReviewerApprovalMode = !empty($isReviewerApprovalMode);
                $isAdminUser = (string) session('pbm.system_role', '') === 'admin';
                $isFinanceReviewer = false;
                $currentStaffIdForFinance = trim((string) (session('staff_id') ?? auth()->user()?->user_id ?? ''));
                try {
                    if (
                        $currentStaffIdForFinance !== ''
                        && \Illuminate\Support\Facades\Schema::hasTable('withdraw_set_finance_reviewer')
                    ) {
                        $isFinanceReviewer = \Illuminate\Support\Facades\DB::table('withdraw_set_finance_reviewer')
                            ->where('withdraw_set_finance_reviewer_staff_id', $currentStaffIdForFinance)
                            ->where('withdraw_set_finance_reviewer_status', 1)
                            ->exists();
                    }
                } catch (\Throwable $e) {
                    $isFinanceReviewer = false;
                }

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
                $defaultReviewerStaffId = '';
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
                                    $reviewerCandidates = [];
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

                                        $rowJobCode = trim((string) ($row['job_code'] ?? ''));
                                        $rowDepCode = trim((string) ($row['dep_code'] ?? ''));
                                        $positionDeanRaw = trim((string) ($row['position_dean'] ?? ''));
                                        if ($positionDeanRaw !== '') {
                                            $matchReviewerScope = false;
                                            if ($currentStaffJobCode !== '') {
                                                $matchReviewerScope = $rowJobCode !== '' && $rowJobCode === $currentStaffJobCode;
                                            } elseif ($currentStaffDepCode !== '') {
                                                $matchReviewerScope = $rowDepCode !== '' && $rowDepCode === $currentStaffDepCode;
                                            }

                                            if ($matchReviewerScope) {
                                                $reviewerCandidates[] = [
                                                    'id' => (string) $id,
                                                    'position_dean' => is_numeric($positionDeanRaw) ? (float) $positionDeanRaw : INF,
                                                    'position_dean_raw' => $positionDeanRaw,
                                                ];
                                            }
                                        }

                                        $staffListOptions[] = [
                                            'id' => (string) $id,
                                            'label' => $label,
                                        ];
                                    }

                                    if (count($reviewerCandidates) > 0) {
                                        usort($reviewerCandidates, static function (array $a, array $b) {
                                            if ($a['position_dean'] === $b['position_dean']) {
                                                return strcmp((string) $a['position_dean_raw'], (string) $b['position_dean_raw']);
                                            }

                                            return $a['position_dean'] <=> $b['position_dean'];
                                        });
                                        $defaultReviewerStaffId = (string) ($reviewerCandidates[0]['id'] ?? '');
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
                    $defaultReviewerStaffId = '';
                    $currentStaffJobCode = '';
                    $currentStaffDepCode = '';
                }

                $activeStaffIdMap = collect($staffListOptions ?? [])
                    ->map(fn ($staff) => trim((string) ($staff['id'] ?? '')))
                    ->filter(fn ($staffId) => $staffId !== '')
                    ->flip();
                $currentUserApplicantStaffId = trim((string) ($defaultApplicantStaffId ?? ''));
            ?>

            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/70 shadow-md shadow-slate-200/60">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-white via-slate-50/80 to-emerald-50/40 px-4 py-4 sm:px-5">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                            <i class="fas fa-id-card text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h5 class="text-base font-semibold text-slate-900 sm:text-lg">รายการเบิกจ่าย</h5>
                            <p class="mt-0.5 text-sm text-slate-500">
                                <?php echo e($isReviewerApprovalMode ? 'แสดงรายการที่รอการอนุมัติจากผู้ตรวจสอบ' : 'แสดงรายการล่าสุดทั้งหมด'); ?>

                                <span id="withdraw-general-list-count" class="font-semibold text-slate-700"><?php echo e(count($sampleRows)); ?></span>
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
                            <option value=""><?php echo e($isProjectWithdrawPage ? 'ทุกโครงการ' : 'ทุกประเภท'); ?></option>
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

    <?php
        $generalMemo = is_array($memoTemplate ?? null) ? $memoTemplate : [];
        $generalMemoSubject = (string) ($generalMemo['subject'] ?? '');
        $generalMemoTo = (string) ($generalMemo['to'] ?? '');
        $generalMemoParagraph1 = (string) ($generalMemo['paragraph1'] ?? '');
        $generalMemoParagraph2 = (string) ($generalMemo['paragraph2'] ?? '');
        $generalMemoParagraph3 = (string) ($generalMemo['paragraph3'] ?? '');
        $generalMemoParagraph4 = (string) ($generalMemo['paragraph4'] ?? '');
    ?>

    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'withdraw-request-modal','maxWidth' => '3xl','static' => true,'variant' => 'modern','title' => ''.e($formTitle).'','description' => 'กรอกรายละเอียดเบิกจ่ายตามแบบฟอร์ม','iconClass' => 'fas fa-file-signature']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-request-modal','maxWidth' => '3xl','static' => true,'variant' => 'modern','title' => ''.e($formTitle).'','description' => 'กรอกรายละเอียดเบิกจ่ายตามแบบฟอร์ม','iconClass' => 'fas fa-file-signature']); ?>
        <form id="withdraw-general-form" class="space-y-5" enctype="multipart/form-data">
            <input type="hidden" id="withdraw-record-id" value="" />
            <input type="hidden" id="withdraw-type" value="<?php echo e($withdrawKind); ?>" />
            <input type="hidden" id="withdraw-project-id" value="" />
            <input type="hidden" id="withdraw-project-code" value="" />
            <input type="hidden" id="withdraw-project-name" value="" />
            <input type="hidden" id="withdraw-project-activity-id" value="" />
            <input type="hidden" id="withdraw-project-activity-name" value="" />
            <input type="hidden" id="withdraw-project-activity-detail" value="" />
            <input type="hidden" id="withdraw-project-handoff-token" value="" />
            <input type="hidden" id="withdraw-project-amount" value="" />
            <input type="hidden" id="withdraw-budget-manage-id" value="" />
            <input type="hidden" id="withdraw-budget-manage-code" value="" />
            <div id="withdraw-project-source-box" class="hidden rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                <div class="font-bold">รายการจากระบบโครงการ</div>
                <div id="withdraw-project-source-text" class="mt-1">-</div>
            </div>
            <div id="withdraw-revision-note-box" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <div class="font-bold">รายละเอียดที่ต้องแก้ไข</div>
                <div id="withdraw-revision-note-text" class="mt-1 whitespace-pre-wrap">-</div>
            </div>
            <div id="withdraw-revision-explanation-box" class="hidden">
                <label for="withdraw-revision-explanation" class="mb-1 block text-xs font-bold text-slate-700">คำชี้แจงการแก้ไข</label>
                <textarea id="withdraw-revision-explanation" rows="3" placeholder="ระบุคำชี้แจงการแก้ไข เพื่อให้ผู้ที่ส่งกลับมาเห็นเมื่อส่งรายการกลับไป"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
            </div>
            

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ส่วนงาน</label>
                    <?php if (isset($component)) { $__componentOriginal0728ba7b37b7eda62f62767c5dccebf3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-dropdown','data' => ['id' => 'withdraw-department-select','placeholder' => ''.e(count($departmentSearchOptions) > 0 ? 'พิมพ์เพื่อค้นหาส่วนงาน...' : 'ไม่พบข้อมูลส่วนงาน').'','options' => $departmentSearchOptions,'valueKey' => 'code','textKey' => 'label','showClear' => true,'clearTitle' => 'ล้างส่วนงาน']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'withdraw-department-select','placeholder' => ''.e(count($departmentSearchOptions) > 0 ? 'พิมพ์เพื่อค้นหาส่วนงาน...' : 'ไม่พบข้อมูลส่วนงาน').'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($departmentSearchOptions),'valueKey' => 'code','textKey' => 'label','showClear' => true,'clearTitle' => 'ล้างส่วนงาน']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3)): ?>
<?php $attributes = $__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3; ?>
<?php unset($__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0728ba7b37b7eda62f62767c5dccebf3)): ?>
<?php $component = $__componentOriginal0728ba7b37b7eda62f62767c5dccebf3; ?>
<?php unset($__componentOriginal0728ba7b37b7eda62f62767c5dccebf3); ?>
<?php endif; ?>
                </div>
                <div class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">ที่</label>
                        <input type="text" id="withdraw-document-number" placeholder="เลือกส่วนงานเพื่อเติมเลขที่หนังสือ"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">วันที่</label>
                        <?php if (isset($component)) { $__componentOriginal37e12294b28f0bd91a733acab9bb06c5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal37e12294b28f0bd91a733acab9bb06c5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.date-picker','data' => ['id' => 'withdraw-date','placeholder' => 'เลือกวันที่','value' => $isProjectWithdrawPage ? now()->toDateString() : null,'withIcon' => false,'inputClass' => 'rounded-xl border-slate-300 text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('date-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'withdraw-date','placeholder' => 'เลือกวันที่','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isProjectWithdrawPage ? now()->toDateString() : null),'withIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'inputClass' => 'rounded-xl border-slate-300 text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal37e12294b28f0bd91a733acab9bb06c5)): ?>
<?php $attributes = $__attributesOriginal37e12294b28f0bd91a733acab9bb06c5; ?>
<?php unset($__attributesOriginal37e12294b28f0bd91a733acab9bb06c5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal37e12294b28f0bd91a733acab9bb06c5)): ?>
<?php $component = $__componentOriginal37e12294b28f0bd91a733acab9bb06c5; ?>
<?php unset($__componentOriginal37e12294b28f0bd91a733acab9bb06c5); ?>
<?php endif; ?>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">เรื่อง</label>
                    <input type="text" id="withdraw-subject" placeholder="ระบุหัวข้อเรื่องขออนุมัติเบิกจ่าย" value="<?php echo e($generalMemoSubject); ?>"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">เรียน</label>
                    <input type="text" id="withdraw-to" placeholder="ระบุผู้รับเรื่อง" value="<?php echo e($generalMemoTo); ?>"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                </div>
                <div class="md:col-span-2" data-memo-block="paragraph1">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 1</label>
                    <textarea id="withdraw-paragraph-1" rows="<?php echo e($isProjectWithdrawPage ? 6 : 3); ?>" placeholder="เนื้อหาย่อหน้าแรก"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"><?php echo e($generalMemoParagraph1); ?></textarea>
                </div>
                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-white" data-memo-block="items">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h6 class="text-sm font-semibold text-slate-800">รายการเบิกจ่าย</h6>
                                <span id="withdraw-item-count-badge"
                                    class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">0</span>
                            </div>
                            <?php if(! $isProjectWithdrawPage): ?>
                                <button type="button" id="withdraw-add-item-row"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-100 transition hover:bg-sky-100">
                                    <i class="fas fa-plus text-[10px]"></i>
                                    เพิ่มรายการ
                                </button>
                            <?php endif; ?>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">
                            <?php echo e($isProjectWithdrawPage
                                ? 'กรอกงบประมาณ → หมวด → รายการ → จำนวนเงิน ในแต่ละรายการ'
                                : 'กรอกหมวด → รายการ → จำนวนเงิน ในแต่ละรายการ'); ?>

                        </p>
                    </div>
                    <div class="space-y-3 p-4">
                        <div id="withdraw-item-rows" class="space-y-3"></div>
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
                <div class="md:col-span-2" data-memo-block="paragraph2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 2</label>
                    <textarea id="withdraw-paragraph-2" rows="<?php echo e($isProjectWithdrawPage ? 5 : 2); ?>" placeholder="เนื้อหาย่อหน้าถัดไป"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100 whitespace-pre-wrap"><?php echo e($generalMemoParagraph2); ?></textarea>
                </div>
            </div>

            <div class="md:col-span-2" data-memo-block="paragraph3">
                <label class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 3</label>
                <textarea id="withdraw-paragraph-3" rows="2" placeholder="เนื้อหาย่อหน้าที่ 3"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"><?php echo e($generalMemoParagraph3); ?></textarea>
            </div>

            <?php if($isProjectWithdrawPage): ?>
                <div class="md:col-span-2" data-memo-block="project-budget-summary">
                    <div id="withdraw-project-budget-summary-card" class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                        <div class="overflow-x-auto">
                            <table class="w-full table-fixed border-collapse border border-slate-300 text-sm">
                                <thead>
                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">งบประมาณโครงการ</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติแล้ว</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติครั้งนี้</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">รวมอนุมัติ</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">คงเหลือ</th>
                                    </tr>
                                </thead>
                                <tbody id="withdraw-project-budget-summary-body">
                                    <tr>
                                        <td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="5">- ยังไม่มีข้อมูลโครงการ -</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="md:col-span-2" data-memo-block="paragraph4">
                <label class="mb-1 block text-xs font-bold text-slate-700">ย่อหน้า 4</label>
                <textarea id="withdraw-paragraph-4" rows="2" placeholder="เนื้อหาย่อหน้าที่ 4"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"><?php echo e($generalMemoParagraph4); ?></textarea>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">ผู้ขอเบิก</label>
                    <?php if (isset($component)) { $__componentOriginal0728ba7b37b7eda62f62767c5dccebf3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-dropdown','data' => ['id' => 'withdraw-applicant-staff','placeholder' => ''.e(count($staffListOptions) > 0 ? 'พิมพ์เพื่อค้นหาผู้ขอเบิก...' : 'ไม่พบรายชื่อพนักงาน').'','options' => $staffListOptions,'valueKey' => 'id','textKey' => 'label','showClear' => true,'clearTitle' => 'ล้างผู้ขอเบิก']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'withdraw-applicant-staff','placeholder' => ''.e(count($staffListOptions) > 0 ? 'พิมพ์เพื่อค้นหาผู้ขอเบิก...' : 'ไม่พบรายชื่อพนักงาน').'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($staffListOptions),'valueKey' => 'id','textKey' => 'label','showClear' => true,'clearTitle' => 'ล้างผู้ขอเบิก']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3)): ?>
<?php $attributes = $__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3; ?>
<?php unset($__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0728ba7b37b7eda62f62767c5dccebf3)): ?>
<?php $component = $__componentOriginal0728ba7b37b7eda62f62767c5dccebf3; ?>
<?php unset($__componentOriginal0728ba7b37b7eda62f62767c5dccebf3); ?>
<?php endif; ?>
                </div>
                <div id="withdraw-reviewer-field">
                    <label class="mb-1 block text-xs font-bold text-slate-700">ผู้ตรวจสอบ/หัวหน้างาน-ภาควิชา</label>
                    <?php if (isset($component)) { $__componentOriginal0728ba7b37b7eda62f62767c5dccebf3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-dropdown','data' => ['id' => 'withdraw-reviewer-staff','placeholder' => ''.e(count($staffListOptions) > 0 ? 'พิมพ์เพื่อค้นหาผู้ตรวจสอบ/หัวหน้างาน-ภาควิชา...' : 'ไม่พบรายชื่อพนักงาน').'','options' => $staffListOptions,'valueKey' => 'id','textKey' => 'label','showClear' => true,'clearTitle' => 'ล้างผู้ตรวจสอบ/หัวหน้างาน-ภาควิชา']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'withdraw-reviewer-staff','placeholder' => ''.e(count($staffListOptions) > 0 ? 'พิมพ์เพื่อค้นหาผู้ตรวจสอบ/หัวหน้างาน-ภาควิชา...' : 'ไม่พบรายชื่อพนักงาน').'','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($staffListOptions),'valueKey' => 'id','textKey' => 'label','showClear' => true,'clearTitle' => 'ล้างผู้ตรวจสอบ/หัวหน้างาน-ภาควิชา']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3)): ?>
<?php $attributes = $__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3; ?>
<?php unset($__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0728ba7b37b7eda62f62767c5dccebf3)): ?>
<?php $component = $__componentOriginal0728ba7b37b7eda62f62767c5dccebf3; ?>
<?php unset($__componentOriginal0728ba7b37b7eda62f62767c5dccebf3); ?>
<?php endif; ?>
                </div>
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

            <div class="hidden rounded-xl border border-indigo-100 bg-indigo-50/40 p-4" aria-hidden="true">
                <div class="mb-2 flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                        <i class="fas fa-route text-xs"></i>
                    </span>
                    <div>
                        <h6 class="text-sm font-semibold text-slate-800">เส้นทางการอนุมัติ</h6>
                        <p class="text-[11px] text-slate-500">ตามการตั้งค่าเทมเพลตบันทึกข้อความ (เบิกจ่ายทั่วไป)</p>
                    </div>
                </div>
                <ol id="withdraw-approval-route-list" class="space-y-1.5 text-sm text-slate-700"></ol>
                <p id="withdraw-approval-route-extra-hint" class="mt-2 hidden text-[11px] text-slate-500">
                    ตามด้วยผู้ตรวจสอบ/อนุมัติเพิ่มเติมที่เลือกในฟอร์ม (ถ้ามี)
                </p>
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
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.confirm-modal','data' => ['name' => 'withdraw-general-delete-confirm-modal','maxWidth' => 'sm','title' => __('ยืนยันการลบรายการ?'),'message' => __('คุณต้องการลบรายการเบิกจ่ายนี้หรือไม่'),'confirmText' => ''.e(__('ลบ')).'','cancelText' => ''.e(__('ยกเลิก')).'','confirmVariant' => 'danger','confirmClick' => 'window.__withdrawGeneralConfirmDelete()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('confirm-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-general-delete-confirm-modal','maxWidth' => 'sm','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('ยืนยันการลบรายการ?')),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('คุณต้องการลบรายการเบิกจ่ายนี้หรือไม่')),'confirmText' => ''.e(__('ลบ')).'','cancelText' => ''.e(__('ยกเลิก')).'','confirmVariant' => 'danger','confirmClick' => 'window.__withdrawGeneralConfirmDelete()']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10)): ?>
<?php $attributes = $__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10; ?>
<?php unset($__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10)): ?>
<?php $component = $__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10; ?>
<?php unset($__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'withdraw-general-proceed-confirm-modal','maxWidth' => 'md','static' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-general-proceed-confirm-modal','maxWidth' => 'md','static' => true]); ?>
        <div class="p-6">
            <h3 id="withdraw-proceed-confirm-title" class="text-lg font-bold text-slate-800">ยืนยันดำเนินการเบิกจ่าย?</h3>
            <p id="withdraw-proceed-confirm-description" class="mt-1 text-sm text-slate-500">โปรดตรวจสอบรายละเอียดก่อนยืนยันดำเนินการ</p>
            <div id="withdraw-proceed-detail-box" class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <div><span class="font-semibold text-slate-800">เลขที่:</span> <span id="withdraw-proceed-detail-code">-</span></div>
                <div><span class="font-semibold text-slate-800">วันที่:</span> <span id="withdraw-proceed-detail-date">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">เรื่อง:</span> <span id="withdraw-proceed-detail-subject">-</span></div>
                <div><span class="font-semibold text-slate-800">ผู้ขอเบิก:</span> <span id="withdraw-proceed-detail-applicant">-</span></div>
                <div><span class="font-semibold text-slate-800">จำนวนเงิน:</span> <span id="withdraw-proceed-detail-amount">-</span> บาท</div>
            </div>
            <div id="withdraw-proceed-procurement-box" class="mt-3 hidden rounded-lg border border-orange-200 bg-orange-50/70 p-3 text-sm text-slate-700">
                <div class="mb-2 flex items-center gap-2 font-semibold text-orange-900">
                    <i class="fas fa-file-invoice text-xs text-orange-600"></i>
                    <span>เอกสารจัดซื้อจัดจ้าง</span>
                    <span id="withdraw-proceed-procurement-count"
                        class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-orange-100 px-1.5 py-0.5 text-[11px] font-bold text-orange-800 ring-1 ring-orange-200">0</span>
                </div>
                <ul id="withdraw-proceed-procurement-list" class="space-y-2"></ul>
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
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'withdraw-project-finance-docs-modal','maxWidth' => '4xl','static' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-project-finance-docs-modal','maxWidth' => '4xl','static' => true]); ?>
        <div class="p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">เอกสารลงนาม</h3>
                    <p id="withdraw-finance-docs-subject" class="mt-1 text-sm text-slate-500">เอกสารลงนาม e-Sign ที่แนบมา</p>
                </div>
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-project-finance-docs-modal')"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                    aria-label="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="withdraw-finance-docs-loading" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                <i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลดเอกสาร...
            </div>
            <div id="withdraw-finance-docs-empty" class="mt-4 hidden rounded-xl border border-dashed border-violet-200 bg-violet-50/40 px-4 py-8 text-center text-sm text-slate-500">
                ยังไม่มีเอกสารลงนาม e-Sign ของรายการนี้
            </div>
            <div id="withdraw-finance-docs-body" class="mt-4 max-h-[75vh] space-y-4 overflow-y-auto"></div>
            <div class="mt-6 flex justify-end">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-project-finance-docs-modal')"
                    class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    ปิด
                </button>
            </div>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'withdraw-memo-preview-modal','maxWidth' => '4xl','static' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-memo-preview-modal','maxWidth' => '4xl','static' => true]); ?>
        <div class="p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 id="withdraw-memo-preview-title" class="text-lg font-bold text-slate-800">บันทึกข้อความ</h3>
                    <p id="withdraw-memo-preview-subject" class="mt-1 text-sm text-slate-500">บันทึกข้อความขออนุมัติเบิกจ่าย</p>
                </div>
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-memo-preview-modal')"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                    aria-label="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div id="withdraw-memo-preview-body" class="mt-4 max-h-[75vh] space-y-4 overflow-y-auto"></div>
            <div class="mt-6 flex justify-end">
                <button type="button"
                    @click="$dispatch('close-modal', 'withdraw-memo-preview-modal')"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    ปิด
                </button>
            </div>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
    <?php echo $__env->make('withdraw.project.partials.procurement-modals', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('withdraw.general.partials.detail-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if (isset($component)) { $__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.confirm-modal','data' => ['name' => 'withdraw-item-row-delete-confirm-modal','maxWidth' => 'sm','title' => __('ยืนยันการลบรายการเบิกจ่าย?'),'message' => __('คุณต้องการลบรายการเบิกจ่ายแถวนี้หรือไม่'),'confirmText' => ''.e(__('ลบ')).'','cancelText' => ''.e(__('ยกเลิก')).'','confirmVariant' => 'danger','confirmClick' => 'window.__withdrawGeneralConfirmDeleteItemRow()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('confirm-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-item-row-delete-confirm-modal','maxWidth' => 'sm','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('ยืนยันการลบรายการเบิกจ่าย?')),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('คุณต้องการลบรายการเบิกจ่ายแถวนี้หรือไม่')),'confirmText' => ''.e(__('ลบ')).'','cancelText' => ''.e(__('ยกเลิก')).'','confirmVariant' => 'danger','confirmClick' => 'window.__withdrawGeneralConfirmDeleteItemRow()']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10)): ?>
<?php $attributes = $__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10; ?>
<?php unset($__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10)): ?>
<?php $component = $__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10; ?>
<?php unset($__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10); ?>
<?php endif; ?>

    <?php $__env->startPush('scripts'); ?>
        <script>
            document.addEventListener('alpine:initialized', () => {
                const departmentSelect = document.getElementById('withdraw-department-select');
                const documentNumberInput = document.getElementById('withdraw-document-number');
                const departmentDocumentMap = <?php echo json_encode($departmentDocumentMap, 15, 512) ?>;
                const bookNumberByType = <?php echo json_encode($bookNumberByType, 15, 512) ?>;
                const generalBookNumberSuffix = <?php echo json_encode($generalBookNumberSuffix, 15, 512) ?>;
                const defaultDepartmentCode = <?php echo json_encode($defaultDepartmentCode, 15, 512) ?>;
                const addItemRowButton = document.getElementById('withdraw-add-item-row');
                const itemRowsContainer = document.getElementById('withdraw-item-rows');
                const itemTotalElement = document.getElementById('withdraw-item-total');
                const withdrawOptionsApi = '/withdraw/api/general/pay-list-options';
                const documentOptionsApi = <?php echo json_encode(url('/withdraw/api/general/document-options'), 15, 512) ?>;
                const saveDraftApi = <?php echo json_encode(url('/withdraw/api/general/save-draft'), 15, 512) ?>;
                const generalDetailApiBase = <?php echo json_encode(url('/withdraw/api/general'), 15, 512) ?>;
                const generalListApi = <?php echo json_encode($generalListApiUrl ?? url('/withdraw/api/general/list'), 15, 512) ?>;
                const approvalPendingListApi = <?php echo json_encode(url('/withdraw/api/general/list?scope=reviewer-pending'), 15, 512) ?>;
                const documentReviewListApi = <?php echo json_encode(url('/withdraw/api/general/list?scope=document-review'), 15, 512) ?>;
                const generalNextRunningNoApi = <?php echo json_encode(url('/withdraw/api/general/next-running-no'), 15, 512) ?>;
                const generalDeleteApi = <?php echo json_encode(url('/withdraw/api/general/delete'), 15, 512) ?>;
                const generalProceedApi = <?php echo json_encode(url('/withdraw/api/general/proceed'), 15, 512) ?>;
                const isReviewerApprovalMode = <?php echo json_encode($isReviewerApprovalMode ?? false, 15, 512) ?>;
                const isAdminUser = <?php echo json_encode($isAdminUser ?? false, 15, 512) ?>;
                const isFinanceReviewer = <?php echo json_encode($isFinanceReviewer ?? false, 15, 512) ?>;
                const withdrawKind = <?php echo json_encode($withdrawKind ?? 'general', 15, 512) ?>;
                const projectHandoffToken = <?php echo json_encode($projectHandoffToken ?? '', 15, 512) ?>;
                const projectHandoffApiBase = <?php echo json_encode($projectHandoffApiUrl ?? url('/withdraw/api/project/handoff'), 15, 512) ?>;
                const projectAmountApi = <?php echo json_encode(url('/withdraw/api/project/amount'), 15, 512) ?>;
                const withdrawTypeInput = document.getElementById('withdraw-type');
                const projectIdInput = document.getElementById('withdraw-project-id');
                const projectCodeInput = document.getElementById('withdraw-project-code');
                const projectActivityIdInput = document.getElementById('withdraw-project-activity-id');
                const projectActivityNameInput = document.getElementById('withdraw-project-activity-name');
                const projectActivityDetailInput = document.getElementById('withdraw-project-activity-detail');
                const projectNameInput = document.getElementById('withdraw-project-name');
                const projectHandoffTokenInput = document.getElementById('withdraw-project-handoff-token');
                const projectAmountInput = document.getElementById('withdraw-project-amount');
                const budgetManageIdInput = document.getElementById('withdraw-budget-manage-id');
                const budgetManageCodeInput = document.getElementById('withdraw-budget-manage-code');
                const projectSourceBox = document.getElementById('withdraw-project-source-box');
                const projectSourceText = document.getElementById('withdraw-project-source-text');
                const withdrawDateInput = document.getElementById('withdraw-date');
                const departmentSearchOptions = <?php echo json_encode($departmentSearchOptions ?? [], 15, 512) ?>;
                const budgetSourceOptionsApi = <?php echo json_encode(url('/withdraw/api/general/budget-source-options'), 15, 512) ?>;
                const isProjectWithdraw = withdrawKind === 'project';
                const financeDocsModalSubject = document.getElementById('withdraw-finance-docs-subject');
                const financeDocsModalLoading = document.getElementById('withdraw-finance-docs-loading');
                const financeDocsModalEmpty = document.getElementById('withdraw-finance-docs-empty');
                const financeDocsModalBody = document.getElementById('withdraw-finance-docs-body');
                const canEmbedPdfPreview = () => {
                    if (typeof window.matchMedia !== 'function') return true;
                    return window.matchMedia('(min-width: 1024px)').matches
                        && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                };
                const renderFinanceEsignDocsHtml = (files = []) => files.map((file, index) => {
                    const title = String(file.item_name || '').trim() || `เอกสารลงนาม e-Sign ${index + 1}`;
                    const url = String(file.url || '').trim();
                    const previewUrl = url === '' ? '' : `${url}${url.includes('?') ? '&' : '?'}preview=${Date.now()}`;
                    const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0 text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>
                        ${url !== '' ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 shadow-sm transition hover:bg-violet-100">เปิดไฟล์</a>` : ''}
                    </div>`;
                    if (url === '' || !canEmbedPdfPreview()) {
                        return `<div class="rounded-xl border border-violet-100 bg-white p-3">${caption}</div>`;
                    }
                    return `<div class="overflow-hidden rounded-xl border border-violet-100 bg-white p-2">${caption}<iframe src="${escapeHtml(previewUrl)}" title="${escapeHtml(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`;
                }).join('');
                const openFinanceDocsModalById = async (id) => {
                    const recordId = Number(id || 0);
                    if (recordId <= 0) return;
                    const row = findGeneralListRowById(recordId) || {};
                    const subject = String(row.subject || '-').trim() || '-';
                    if (financeDocsModalSubject) {
                        financeDocsModalSubject.textContent = subject !== '-' ? subject : 'เอกสารลงนาม e-Sign ที่แนบมา';
                    }
                    financeDocsModalLoading?.classList.remove('hidden');
                    financeDocsModalEmpty?.classList.add('hidden');
                    if (financeDocsModalEmpty) {
                        financeDocsModalEmpty.textContent = 'ยังไม่มีเอกสารลงนาม e-Sign ของรายการนี้';
                    }
                    if (financeDocsModalBody) financeDocsModalBody.innerHTML = '';
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-project-finance-docs-modal' }));
                    try {
                        const response = await fetch(`${generalDetailApiBase}/${recordId}`, { headers: { Accept: 'application/json' } });
                        const json = await response.json();
                        if (json?.status !== 'success' || !json?.data) {
                            throw new Error('detail_load_failed');
                        }
                        const files = Array.isArray(json.data.finance_esign_files) ? json.data.finance_esign_files : [];
                        const visibleFiles = files.filter((file) => String(file?.url || '').trim() !== '' || String(file?.name || '').trim() !== '');
                        financeDocsModalLoading?.classList.add('hidden');
                        if (visibleFiles.length === 0) {
                            financeDocsModalEmpty?.classList.remove('hidden');
                            if (financeDocsModalBody) financeDocsModalBody.innerHTML = '';
                            return;
                        }
                        financeDocsModalEmpty?.classList.add('hidden');
                        if (financeDocsModalBody) {
                            financeDocsModalBody.innerHTML = renderFinanceEsignDocsHtml(visibleFiles);
                            if (!canEmbedPdfPreview()) {
                                financeDocsModalBody.querySelectorAll('iframe').forEach((iframe) => iframe.remove());
                            }
                        }
                    } catch (error) {
                        financeDocsModalLoading?.classList.add('hidden');
                        financeDocsModalEmpty?.classList.remove('hidden');
                        if (financeDocsModalEmpty) {
                            financeDocsModalEmpty.textContent = 'ไม่สามารถโหลดเอกสารลงนาม e-Sign ได้';
                        }
                        if (financeDocsModalBody) financeDocsModalBody.innerHTML = '';
                        window.toast?.error?.('ไม่สามารถโหลดเอกสารลงนาม e-Sign ได้', { timer: 1400 });
                    }
                };
                const PROJECT_SUBJECT_DEFAULT = 'ขออนุมัติเบิกค่าใช้จ่ายโครงการ';
                let applyingProjectHandoff = false;
                let suppressProjectParagraphSync = false;
                let cachedProjectAmount = 0;
                let cachedProjectApprovedAlready = 0;
                let memoTemplateDefaults = <?php echo json_encode($memoTemplate ?? null, 15, 512) ?>;
                let lockedProjectActivityName = '';
                let lockedProjectActivityDetail = '';
                const PROJECT_P2_ACTIVITY_LABEL = 'กิจกรรม';
                const PROJECT_P2_DETAIL_LABEL = 'รายละเอียดการจัดกิจกรรม';
                const PROJECT_P2_DETAIL_LABEL_LEGACY = 'รายละเอียดการดำเนินการ';
                const PROJECT_P2_EXPENSE_LABEL = 'รายละเอียดค่าใช้จ่ายทั้งสิ้น';
                let budgetSourceOptions = [];
                const budgetSourceOptionMap = new Map();
                const applicantStaffSelect = document.getElementById('withdraw-applicant-staff');
                const reviewerStaffSelect = document.getElementById('withdraw-reviewer-staff');
                const addActorRowButton = document.getElementById('withdraw-add-actor-row');
                const actorRowsContainer = document.getElementById('withdraw-actor-rows');
                const extraActorsSection = document.getElementById('withdraw-extra-actors-section');
                const reviewerFieldWrap = document.getElementById('withdraw-reviewer-field');
                const approvalRouteListEl = document.getElementById('withdraw-approval-route-list');
                const approvalRouteExtraHint = document.getElementById('withdraw-approval-route-extra-hint');
                const approvalStepOptions = <?php echo json_encode($approvalStepOptions ?? [], 15, 512) ?>;

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
                const withdrawSaveDraftButton = document.getElementById('withdraw-save-draft-button');
                const withdrawRecordIdInput = document.getElementById('withdraw-record-id');
                const withdrawSubjectInput = document.getElementById('withdraw-subject');
                const withdrawToInput = document.getElementById('withdraw-to');
                const withdrawParagraph1Input = document.getElementById('withdraw-paragraph-1');
                const withdrawParagraph2Input = document.getElementById('withdraw-paragraph-2');
                const withdrawParagraph3Input = document.getElementById('withdraw-paragraph-3');
                const withdrawParagraph4Input = document.getElementById('withdraw-paragraph-4');
                const withdrawGeneralTableBody = document.getElementById('withdraw-general-table-body');
                const defaultApplicantStaffId = <?php echo json_encode($defaultApplicantStaffId, 15, 512) ?>;
                const defaultReviewerStaffId = <?php echo json_encode($defaultReviewerStaffId, 15, 512) ?>;
                const staffSearchOptions = <?php echo json_encode($staffListOptions ?? [], 15, 512) ?>;
                const documentTypeWrapper = document.getElementById('withdraw-document-types');
                const documentSubitemsContainer = document.getElementById('withdraw-document-subitems-container');
                const documentSubitemsElement = document.getElementById('withdraw-document-subitems');
                const documentUploadsContainer = document.getElementById('withdraw-document-uploads-container');
                const documentUploadsElement = document.getElementById('withdraw-document-uploads');
                const proceedDetailCode = document.getElementById('withdraw-proceed-detail-code');
                const proceedDetailDate = document.getElementById('withdraw-proceed-detail-date');
                const proceedDetailApplicant = document.getElementById('withdraw-proceed-detail-applicant');
                const proceedDetailAmount = document.getElementById('withdraw-proceed-detail-amount');
                const proceedDetailSubject = document.getElementById('withdraw-proceed-detail-subject');
                const proceedProcurementBox = document.getElementById('withdraw-proceed-procurement-box');
                const proceedProcurementList = document.getElementById('withdraw-proceed-procurement-list');
                const proceedProcurementCount = document.getElementById('withdraw-proceed-procurement-count');
                const revisionNoteBox = document.getElementById('withdraw-revision-note-box');
                const revisionNoteText = document.getElementById('withdraw-revision-note-text');
                const revisionExplanationBox = document.getElementById('withdraw-revision-explanation-box');
                const revisionExplanationInput = document.getElementById('withdraw-revision-explanation');
                const proceedConfirmTitle = document.getElementById('withdraw-proceed-confirm-title');
                const proceedConfirmDescription = document.getElementById('withdraw-proceed-confirm-description');
                const proceedConfirmSubmitButton = document.getElementById('withdraw-proceed-confirm-submit-button');
                const procurementListApi = <?php echo json_encode(url('/withdraw/api/project/procurement'), 15, 512) ?>;
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
                let generalTableRows = <?php echo json_encode($sampleRows->values(), 15, 512) ?>;

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

                let syncDocumentNumber = () => {};
                if (departmentSelect && documentNumberInput) {
                    const normalizeTypeText = (value) => String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
                    const withdrawFormTypeLabel = withdrawKind === 'project' ? 'เบิกจ่ายเงินโครงการ' : 'เบิกจ่ายทั่วไป';

                    const resolveBookNumberSuffixByFormType = () => {
                        const entries = Object.entries(bookNumberByType || {})
                            .map(([type, value]) => [String(type || '').trim(), String(value || '').trim()])
                            .filter(([type, value]) => type !== '' && value !== '');
                        if (entries.length === 0) {
                            return '';
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

                        return '';
                    };

                    const mergeDocumentNumber = (baseDocumentNumber, appendSuffix) => {
                        const base = String(baseDocumentNumber || '').trim();
                        const suffix = String(appendSuffix || '').trim();
                        if (base === '') return suffix;
                        if (suffix === '') return base;
                        if (base.endsWith(suffix)) return base;
                        return `${base}${suffix}`;
                    };

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

                if (reviewerStaffSelect && defaultReviewerStaffId && reviewerStaffSelect.value !== defaultReviewerStaffId) {
                    reviewerStaffSelect.value = defaultReviewerStaffId;
                    reviewerStaffSelect.dispatchEvent(new Event('input', { bubbles: true }));
                    reviewerStaffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (!itemRowsContainer || !itemTotalElement) return;

                const categoryOptions = [];
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
                        if (item.categoryPayCode > 0 && item.categoryPayCode !== 7) return false;
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

                const withdrawGeneralListCount = document.getElementById('withdraw-general-list-count');
                const findGeneralListRowById = (id) => {
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

                const renderGeneralTableRows = (rows) => {
                    if (!withdrawGeneralTableBody) return;
                    const safeRows = Array.isArray(rows) ? rows : [];
                    if (withdrawGeneralListCount) {
                        withdrawGeneralListCount.textContent = String(safeRows.length);
                    }
                    if (safeRows.length === 0) {
                        withdrawGeneralTableBody.innerHTML = '<div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">ไม่พบข้อมูล</div>';
                        return;
                    }

                    withdrawGeneralTableBody.innerHTML = safeRows.map((row) => {
                        const status = String(row.status || '-');
                        const statusClass = statusClassMap[status] || 'bg-slate-100 text-slate-600 ring-slate-200';
                        const amount = Number(row.amount || 0).toLocaleString('th-TH');
                        const id = Number(row.id || 0);
                        const subject = String(row.subject || '-');
                        const projectName = String(row.project_name || '').trim();
                        const manager = String(row.manager || '-');
                        const code = String(row.code || '-');
                        const date = String(row.date || '-');
                        const type = String(row.type || '-');
                        const rawStatus = String(row.raw_status || '').trim();
                        let subjectTitle = subject;
                        if (projectName !== '' && !subject.includes(projectName)) {
                            subjectTitle = `${subject} — ${projectName}`;
                        }
                        const subjectHtml = subject !== '-'
                            ? `<a href="javascript:void(0)" data-action="view" data-id="${id}" class="line-clamp-3 font-extrabold text-slate-900 underline decoration-slate-300 underline-offset-4 hover:text-emerald-600 hover:decoration-emerald-300">${escapeHtml(subjectTitle)}</a>`
                            : `<span class="text-slate-400">-</span>`;
                        const canManageDraft = !isReviewerApprovalMode && rawStatus === 'saved';
                        const canDeleteRow = !isReviewerApprovalMode && (isAdminUser || rawStatus === 'saved');
                        const canCancelProceed = !isReviewerApprovalMode && ['pending_primary_review', 'processing', 'pending_additional_review', 'pending_finance_review'].includes(rawStatus);
                        const canEditRevision = !isReviewerApprovalMode && rawStatus === 'pending_revision';
                        const canOpenMemoPdf = ['pending_finance_review', 'pending_approval', 'approved'].includes(rawStatus);
                        const canOpenMemoFinancePdf = rawStatus === 'approved';
                        const procurementCount = Number(row.procurement_count || 0);
                        const hideProcurementButton = isProjectWithdraw && rawStatus === 'approved' && procurementCount <= 0;
                        const procurementTypeLabels = {
                            w119: 'จัดซื้อจัดจ้าง ว.119',
                            eight: 'จัดซื้อจัดจ้าง 8 ข้อ',
                        };
                        const procurementLabel = isProjectWithdraw
                            ? (Array.isArray(row.procurement_types) ? row.procurement_types : [])
                                .map((typeCode) => procurementTypeLabels[String(typeCode || '').trim()] || '')
                                .filter(Boolean)
                                .map((label) => `<span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-[11px] font-semibold text-orange-700 ring-1 ring-orange-100">${escapeHtml(label)}</span>`)
                                .join('')
                            : '';
                        const editButton = canManageDraft ? `<button type="button" data-action="edit" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"><i class="fas fa-pen-to-square text-[10px]"></i>แก้ไข</button>` : '';
                        const deleteButton = canDeleteRow ? `<button type="button" data-action="delete" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"><i class="fas fa-trash text-[10px]"></i>ลบ</button>` : '';
                        const proceedButton = canManageDraft ? `<button type="button" data-action="proceed" data-id="${id}" data-proceed-action="proceed" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"><i class="fas fa-circle-play text-[10px]"></i>ดำเนินการเบิกจ่าย</button>` : '';
                        const procurementButton = !isReviewerApprovalMode && !hideProcurementButton
                            ? `<button type="button" data-action="open-procurement" data-id="${id}" data-withdraw-status="${escapeHtml(rawStatus)}" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100"><i class="fas fa-file-invoice text-[10px]"></i>เอกสารจัดซื้อจัดจ้าง</button>`
                            : '';
                        const cancelButton = canCancelProceed ? `<button type="button" data-action="proceed" data-id="${id}" data-proceed-action="cancel" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"><i class="fas fa-ban text-[10px]"></i>ยกเลิกรายการ</button>` : '';
                        const revisionEditButton = canEditRevision ? `<button type="button" data-action="edit-revision" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"><i class="fas fa-pen-to-square text-[10px]"></i>แก้ไขรายการ</button>` : '';
                        const memoPdfButton = canOpenMemoPdf ? `<button type="button" data-action="open-memo-pdf" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"><i class="fas fa-file-pdf text-[10px]"></i>บันทึกข้อความ</button>` : '';
                        const memoFinancePdfButton = canOpenMemoFinancePdf ? `<button type="button" data-action="open-memo-finance-pdf" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-cyan-200 bg-cyan-50 px-2.5 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100"><i class="fas fa-file-pdf text-[10px]"></i>บันทึกข้อความ (การเงิน)</button>` : '';
                        const financeEsignCount = Number(row.finance_esign_count || 0);
                        const financeDocsButton = financeEsignCount > 0
                            ? `<button type="button" data-action="open-finance-docs" data-id="${id}" class="inline-flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-100"><i class="fas fa-file-signature text-[10px]"></i>เอกสารลงนาม</button>`
                            : '';
                        const viewButton = `<button type="button" data-action="view" data-id="${id}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50"><i class="fas fa-eye text-[11px]"></i>ดูรายละเอียด</button>`;
                        return `<article class="group relative w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-md shadow-slate-200/50 ring-1 ring-white/80 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-200/80 hover:shadow-xl hover:shadow-emerald-100/40">
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
                                    ${procurementLabel}
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
                                ${procurementButton}
                                ${financeDocsButton}
                                ${proceedButton}
                                ${cancelButton}
                                ${revisionEditButton}
                                ${memoPdfButton}
                                ${memoFinancePdfButton}
                            </div>
                        </article>`;
                    }).join('');
                };

                const openMemoPreview = (url, title, subject) => {
                    const previewUrl = `${url}${url.includes('?') ? '&' : '?'}preview=${Date.now()}`;
                    const titleEl = document.getElementById('withdraw-memo-preview-title');
                    const subjectEl = document.getElementById('withdraw-memo-preview-subject');
                    const bodyEl = document.getElementById('withdraw-memo-preview-body');
                    if (titleEl) titleEl.textContent = title;
                    if (subjectEl) subjectEl.textContent = subject;
                    if (bodyEl) {
                        const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0 text-sm font-semibold text-slate-800">${escapeHtml(title)}</div>
                            <a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">เปิดไฟล์</a>
                        </div>`;
                        bodyEl.innerHTML = canEmbedPdfPreview()
                            ? `<div class="overflow-hidden rounded-xl border border-indigo-100 bg-white p-2">${caption}<iframe src="${escapeHtml(previewUrl)}" title="${escapeHtml(title)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`
                            : `<div class="rounded-xl border border-indigo-100 bg-white p-3">${caption}</div>`;
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-memo-preview-modal' }));
                };

                const openMemoPdfById = async (id) => {
                    const recordId = Number(id || 0);
                    if (recordId <= 0) return;
                    const row = findGeneralListRowById(recordId) || {};
                    const subject = String(row.subject || '').trim();
                    const targetUrl = isProjectWithdraw
                        ? `/withdraw/project/${recordId}/memo-pdf`
                        : `/withdraw/general/${recordId}/memo-pdf`;
                    openMemoPreview(targetUrl, 'บันทึกข้อความ', subject !== '' && subject !== '-' ? subject : 'บันทึกข้อความขออนุมัติเบิกจ่าย');
                };

                const openMemoFinancePdfById = async (id) => {
                    const recordId = Number(id || 0);
                    if (recordId <= 0) return;
                    const row = findGeneralListRowById(recordId) || {};
                    const subject = String(row.subject || '').trim();
                    const targetUrl = isProjectWithdraw
                        ? `/withdraw/project/${recordId}/memo-finance-pdf`
                        : `/withdraw/general/${recordId}/memo-finance-pdf`;
                    openMemoPreview(targetUrl, 'บันทึกข้อความ (การเงิน)', subject !== '' && subject !== '-' ? subject : 'บันทึกข้อความงานการเงิน');
                };

                const projectFilterKey = (row) => {
                    const id = String(row?.project_id || '').trim();
                    if (id !== '') return `id:${id}`;
                    const code = String(row?.project_code || '').trim();
                    if (code !== '') return `code:${code}`;
                    const name = String(row?.project_name || '').trim();
                    if (name !== '') return `name:${name}`;
                    return '';
                };

                const projectFilterLabel = (row) => {
                    const name = String(row?.project_name || '').trim();
                    const code = String(row?.project_code || '').trim();
                    if (name !== '' && code !== '') return `${code} — ${name}`;
                    return name || code;
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
                        if (isProjectWithdraw) {
                            const seen = {};
                            const projects = [];
                            safeRows.forEach((row) => {
                                const key = projectFilterKey(row);
                                const label = projectFilterLabel(row);
                                if (key === '' || label === '' || seen[key]) return;
                                seen[key] = true;
                                projects.push({ key, label });
                            });
                            projects.sort((a, b) => String(a.label).localeCompare(String(b.label), 'th'));
                            const hasUnspecified = safeRows.some((row) => projectFilterKey(row) === '');
                            filterTypeSelect.innerHTML = [
                                '<option value="">ทุกโครงการ</option>',
                                ...projects.map((project) => `<option value="${escapeHtml(project.key)}">${escapeHtml(project.label)}</option>`),
                                hasUnspecified ? '<option value="__none__">ไม่ระบุโครงการ</option>' : '',
                            ].join('');
                            const validValues = projects.map((project) => project.key);
                            if (hasUnspecified) validValues.push('__none__');
                            filterTypeSelect.value = current === '' || validValues.includes(current) ? current : '';
                        } else {
                            const types = Array.from(new Set(
                                safeRows.map((row) => String(row.type || '').trim()).filter((value) => value !== '' && value !== '-')
                            ));
                            filterTypeSelect.innerHTML = [
                                '<option value="">ทุกประเภท</option>',
                                ...types.map((type) => `<option value="${type}">${type}</option>`),
                            ].join('');
                            filterTypeSelect.value = current === '' || types.includes(current) ? current : '';
                        }
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
                        if (type !== '') {
                            if (isProjectWithdraw) {
                                const key = projectFilterKey(row);
                                if (type === '__none__') {
                                    if (key !== '') return false;
                                } else if (key !== type) {
                                    return false;
                                }
                            } else if (String(row.type || '').trim() !== type) {
                                return false;
                            }
                        }
                        if (keyword !== '') {
                            const haystack = [
                                row.code,
                                row.date,
                                row.subject,
                                row.project_name,
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
                    const rows = withdrawOptionsJson?.status === 'success'
                        ? normalizePayListRows(withdrawOptionsJson?.data?.pay_lists || withdrawOptionsJson?.data?.payLists || [])
                        : [];

                    const categoryMap = new Map();
                    rows.forEach((item) => {
                        const categoryCode = Number(item.categoryPayCode || 0);
                        if (categoryCode !== 7) return;
                        const code = String(item.code || '').trim();
                        if (code === '') return;
                        const name = String(item.name || '').trim();
                        if (name === '') return;
                        if (!categoryMap.has(code)) {
                            categoryMap.set(code, {
                                code,
                                name,
                                label: name,
                            });
                        }
                    });
                    const categories = Array.from(categoryMap.values()).sort((a, b) => String(a.name).localeCompare(String(b.name), 'th'));

                    categoryOptions.splice(0, categoryOptions.length, ...categories);
                    payListLoadStatus = categoryOptions.length > 0 ? 'success' : 'error';
                    refreshCategoryItemRowOptions();
                };

                const formatMoney = (value) => Number(value || 0).toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });

                const queryItemCards = () => Array.from(itemRowsContainer?.querySelectorAll('.withdraw-item-card') || []);

                const refreshRowIndex = () => {
                    const cards = queryItemCards();
                    cards.forEach((row, index) => {
                        const indexEl = row.querySelector('[data-row-index]');
                        if (indexEl) indexEl.textContent = String(index + 1);
                    });
                    const countBadge = document.getElementById('withdraw-item-count-badge');
                    if (countBadge) countBadge.textContent = String(cards.length);
                };

                const refreshTotal = () => {
                    let total = 0;
                    itemRowsContainer.querySelectorAll('.withdraw-item-amount').forEach((amountInput) => {
                        const numeric = Number(String(amountInput.value || '').replace(/,/g, ''));
                        if (!Number.isNaN(numeric)) total += numeric;
                    });
                    itemTotalElement.textContent = formatMoney(total);
                    if (isProjectWithdraw) {
                        syncProjectParagraph2();
                        renderProjectBudgetSummary();
                    }
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

                const getTodayIsoDate = () => {
                    const today = new Date();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${today.getFullYear()}-${month}-${day}`;
                };

                const setWithdrawDateValue = (isoDate) => {
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
                };

                const setCurrentApplicant = () => {
                    if (!applicantStaffSelect || !defaultApplicantStaffId) return;
                    applicantStaffSelect.value = defaultApplicantStaffId;
                    applicantStaffSelect.dispatchEvent(new Event('input', { bubbles: true }));
                    applicantStaffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const getSelectedDepartmentName = () => {
                    const selectedCode = String(departmentSelect?.value || '').trim();
                    const matched = (departmentSearchOptions || []).find((option) => String(option?.code || '').trim() === selectedCode);
                    const fullLabel = matched && String(matched.label || '').trim() !== ''
                        ? String(matched.label).trim()
                        : String(document.getElementById('withdraw-department-select-text')?.value || '').trim();
                    const parts = fullLabel.split(/\s*>\s*/).map((part) => part.trim()).filter((part) => part !== '');
                    let name = parts.length > 0 ? parts[parts.length - 1] : fullLabel;
                    name = name.replace(/^สังกัด\s*/u, '').trim();

                    return name;
                };

                const formatBudgetAmount = (value) => {
                    const num = Number(value || 0);
                    if (Number.isNaN(num)) {
                        return '0';
                    }
                    const rounded = Math.round(num * 100) / 100;
                    const hasFraction = Math.abs(rounded - Math.trunc(rounded)) > 0;
                    return rounded.toLocaleString('th-TH', {
                        minimumFractionDigits: hasFraction ? 2 : 0,
                        maximumFractionDigits: hasFraction ? 2 : 0,
                    });
                };

                const findBudgetOption = (budgetId = '', budgetCode = '') => {
                    const id = String(budgetId || '').trim();
                    const code = String(budgetCode || '').trim();
                    if (id !== '' && budgetSourceOptionMap.has(id)) {
                        return budgetSourceOptionMap.get(id);
                    }
                    if (code !== '') {
                        return (budgetSourceOptions || []).find((option) => String(option?.code || '').trim() === code) || null;
                    }
                    return null;
                };

                const getSelectedProjectBudget = () => {
                    const rows = queryItemCards();
                    for (const row of rows) {
                        const budgetId = String(row.querySelector('.withdraw-item-budget-value')?.value || '').trim();
                        const option = findBudgetOption(budgetId);
                        if (option) return option;
                    }
                    return findBudgetOption(budgetManageIdInput?.value || '', budgetManageCodeInput?.value || '');
                };

                const formatParagraphAmount = (value) => {
                    const amount = Number(value || 0);
                    if (Number.isNaN(amount)) return '0';
                    const isWhole = Math.abs(amount - Math.round(amount)) < 0.005;
                    return amount.toLocaleString('en-US', {
                        minimumFractionDigits: isWhole ? 0 : 2,
                        maximumFractionDigits: 2,
                    });
                };

                const amountToThaiBahtText = (value) => {
                    const normalizedAmount = Number(value || 0);
                    if (Number.isNaN(normalizedAmount) || normalizedAmount < 0) return 'ศูนย์บาทถ้วน';
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

                const formatLabeledMemoLine = (label, value) => {
                    const labelWidth = 26;
                    const extra = Math.max(2, labelWidth - Array.from(String(label || '')).length);
                    return `${label}${' '.repeat(extra)}${String(value || '').trim()}`;
                };

                const extractLabeledMemoValue = (text, label, nextLabel = '') => {
                    const source = String(text || '');
                    const start = source.indexOf(label);
                    if (start < 0) return '';
                    let end = source.length;
                    if (nextLabel !== '') {
                        const nextIndex = source.indexOf(nextLabel, start + label.length);
                        if (nextIndex >= 0) end = nextIndex;
                    }
                    return source.slice(start + label.length, end).replace(/^\s+/, '').replace(/\s+$/, '');
                };

                const lockProjectActivityFromSource = (name = '', detail = '') => {
                    const nextName = String(name || '').trim();
                    const nextDetail = String(detail || '').trim();
                    if (nextName !== '') {
                        lockedProjectActivityName = nextName;
                        if (projectActivityNameInput) projectActivityNameInput.value = nextName;
                    }
                    if (nextDetail !== '' && nextDetail !== '..........') {
                        lockedProjectActivityDetail = nextDetail;
                        if (projectActivityDetailInput) projectActivityDetailInput.value = nextDetail;
                    }
                };

                const getLockedProjectActivityName = () => {
                    if (lockedProjectActivityName !== '') return lockedProjectActivityName;
                    const fromHidden = String(projectActivityNameInput?.value || '').trim();
                    if (fromHidden !== '') {
                        lockedProjectActivityName = fromHidden;
                        return fromHidden;
                    }
                    const paragraph2 = String(withdrawParagraph2Input?.value || '');
                    let fromParagraph = extractLabeledMemoValue(
                        paragraph2,
                        PROJECT_P2_ACTIVITY_LABEL,
                        PROJECT_P2_DETAIL_LABEL
                    );
                    if (fromParagraph === '' || fromParagraph === '..........') {
                        fromParagraph = extractLabeledMemoValue(
                            paragraph2,
                            PROJECT_P2_ACTIVITY_LABEL,
                            PROJECT_P2_DETAIL_LABEL_LEGACY
                        );
                    }
                    if (fromParagraph !== '' && fromParagraph !== '..........') {
                        lockProjectActivityFromSource(fromParagraph, '');
                        return fromParagraph;
                    }
                    return '';
                };

                const getLockedProjectActivityDetail = () => {
                    if (lockedProjectActivityDetail !== '') return lockedProjectActivityDetail;
                    const fromHidden = String(projectActivityDetailInput?.value || '').trim();
                    if (fromHidden !== '' && fromHidden !== '..........') {
                        lockedProjectActivityDetail = fromHidden;
                        return fromHidden;
                    }
                    const paragraph2 = String(withdrawParagraph2Input?.value || '');
                    for (const detailLabel of [PROJECT_P2_DETAIL_LABEL, PROJECT_P2_DETAIL_LABEL_LEGACY]) {
                        const fromParagraph = extractLabeledMemoValue(
                            paragraph2,
                            detailLabel,
                            PROJECT_P2_EXPENSE_LABEL
                        );
                        if (fromParagraph !== '' && fromParagraph !== '..........') {
                            lockProjectActivityFromSource('', fromParagraph);
                            return fromParagraph;
                        }
                    }
                    return '';
                };

                const collectProjectWithdrawItems = () => {
                    return queryItemCards().map((row) => {
                        const categoryName = String(row.dataset.categoryName || row.querySelector('.withdraw-item-category')?.value || '').trim();
                        const name = String(row.querySelector('.withdraw-item-name')?.value || '').trim();
                        const amount = Number(String(row.querySelector('.withdraw-item-amount')?.value || '0').replace(/,/g, ''));
                        return {
                            categoryName,
                            name,
                            amount: Number.isNaN(amount) ? 0 : amount,
                        };
                    }).filter((item) => item.name !== '' || item.categoryName !== '' || item.amount > 0);
                };

                const PROJECT_PARAGRAPH1_TEMPLATE_DEFAULT = 'ตามที่.......... ได้รับอนุมัติให้จัดโครงการ .......... รหัสโครงการ .......... โดยเบิกจ่ายจาก .......... จำนวน .......... บาท นั้น';
                const PROJECT_PARAGRAPH2_TEMPLATE_DEFAULT = 'ในการนี้...........ได้ดำเนินการ.......... เรียบร้อยแล้ว จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย จำนวนเงินทั้งสิ้น ....... บาท  โดยมีรายละเอียดดังนี้.-';

                const getProjectParagraph1Template = () => {
                    const fromMemo = String(memoTemplateDefaults?.paragraph1 || '').trim();
                    return fromMemo !== '' ? fromMemo : PROJECT_PARAGRAPH1_TEMPLATE_DEFAULT;
                };

                const fillProjectParagraph1Template = (template, departmentName, projectName, projectCode, budgetName, budgetAmountPhrase) => {
                    const dept = String(departmentName || '').trim() || '..........';
                    const name = String(projectName || '').trim() || '..........';
                    const code = String(projectCode || '').trim() || '..........';
                    const budget = String(budgetName || '').trim() || '..........';
                    const amountPhrase = String(budgetAmountPhrase || '').trim() || '.......... บาท';
                    let text = String(template || '').trim();
                    if (text === '') {
                        text = PROJECT_PARAGRAPH1_TEMPLATE_DEFAULT;
                    }

                    if (/ตามที่/u.test(text)) {
                        text = text.replace(/(ตามที่)[\s.]+/u, `$1${dept} `);
                    }
                    if (/จัดโครงการ/u.test(text)) {
                        text = text.replace(/(จัดโครงการ)[\s.]+/u, `$1 ${name} `);
                    }
                    if (/รหัสโครงการ/u.test(text)) {
                        text = text.replace(/(รหัสโครงการ)[\s.]+/u, `$1 ${code} `);
                    }
                    if (/โดยเบิกจ่ายจาก/u.test(text)) {
                        text = text.replace(/(โดยเบิกจ่ายจาก)[\s.]+/u, `$1 ${budget} `);
                    } else if (/โดยจ่ายจาก/u.test(text)) {
                        text = text.replace(/(โดยจ่ายจาก)[\s.]+/u, `$1 ${budget} `);
                    }
                    if (/จำนวน/u.test(text)) {
                        // แทนที่ช่องจำนวน...บาท หลัง "โดยเบิกจ่ายจาก" ให้เป็นยอดโครงการ
                        text = text.replace(
                            /(จำนวน(?:เงิน)?)[\s.…]*(?:\d[\d,]*(?:\.\d+)?\s*)?(?:บาท(?:\s*\([^)]*\))?)?/u,
                            `$1 ${amountPhrase}`
                        );
                    }

                    return text.replace(/\s{2,}/g, ' ').trim();
                };

                const getProjectParagraph2Template = () => {
                    const fromMemo = String(memoTemplateDefaults?.paragraph2 || '').trim();
                    return fromMemo !== '' ? fromMemo : PROJECT_PARAGRAPH2_TEMPLATE_DEFAULT;
                };

                const fillProjectParagraph2Template = (template, departmentName, activityName, totalAmountText) => {
                    const dept = String(departmentName || '').trim() || '..........';
                    const activity = String(activityName || '').trim() || '..........';
                    const amount = String(totalAmountText || '').trim() || '.......';
                    let text = String(template || '').trim();
                    if (text === '') {
                        text = PROJECT_PARAGRAPH2_TEMPLATE_DEFAULT;
                    }

                    if (/ในการนี้/u.test(text)) {
                        text = text.replace(/(ในการนี้)[\s.]+/u, `$1${dept} `);
                    }
                    if (/ได้ดำเนินการ/u.test(text)) {
                        text = text.replace(/(ได้ดำเนินการ)[\s.]+/u, `$1${activity} `);
                    }
                    if (/จำนวนเงินทั้งสิ้น/u.test(text)) {
                        text = text.replace(/(จำนวนเงินทั้งสิ้น)\s*[\s.]+/u, `$1 ${amount} `);
                    } else if (/จำนวน\s/u.test(text)) {
                        text = text.replace(/(จำนวน)\s*[\s.]+(?=.*?บาท)/u, `$1 ${amount} `);
                    }

                    return text.replace(/\s{2,}/g, ' ').trim();
                };

                const buildProjectParagraph2 = () => {
                    const departmentName = getSelectedDepartmentName() || '..........';
                    const activityName = getLockedProjectActivityName() || '..........';
                    const items = collectProjectWithdrawItems();
                    const totalAmount = items.reduce((sum, item) => sum + Number(item.amount || 0), 0);
                    const totalDisplay = totalAmount > 0 ? formatParagraphAmount(totalAmount) : '.......';

                    return fillProjectParagraph2Template(
                        getProjectParagraph2Template(),
                        departmentName,
                        activityName,
                        totalDisplay
                    );
                };

                const setCachedProjectAmount = (amount, approvedAlready = null) => {
                    const numeric = Number(amount || 0);
                    cachedProjectAmount = Number.isFinite(numeric) && numeric > 0 ? numeric : 0;
                    if (approvedAlready !== null && approvedAlready !== undefined) {
                        const approvedNumeric = Number(approvedAlready || 0);
                        cachedProjectApprovedAlready = Number.isFinite(approvedNumeric) && approvedNumeric > 0
                            ? approvedNumeric
                            : 0;
                    }
                    if (projectAmountInput) {
                        projectAmountInput.value = cachedProjectAmount > 0 ? String(cachedProjectAmount) : '';
                    }
                    renderProjectBudgetSummary();
                };

                const getCurrentWithdrawItemsTotal = () => {
                    let total = 0;
                    itemRowsContainer?.querySelectorAll('.withdraw-item-amount').forEach((amountInput) => {
                        const numeric = Number(String(amountInput.value || '').replace(/,/g, ''));
                        if (!Number.isNaN(numeric)) total += numeric;
                    });
                    return total;
                };

                const renderProjectBudgetSummary = () => {
                    if (!isProjectWithdraw) return;
                    const body = document.getElementById('withdraw-project-budget-summary-body');
                    if (!body) return;
                    const projectId = String(projectIdInput?.value || '').trim();
                    const projectCode = String(projectCodeInput?.value || '').trim();
                    const hasProject = projectId !== '' || projectCode !== '';
                    if (!hasProject && cachedProjectAmount <= 0) {
                        body.innerHTML = '<tr><td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="5">- ยังไม่มีข้อมูลโครงการ -</td></tr>';
                        return;
                    }
                    const budgetAmount = Number(cachedProjectAmount || 0) || 0;
                    const approvedAlready = Number(cachedProjectApprovedAlready || 0) || 0;
                    const approvedThisTime = getCurrentWithdrawItemsTotal();
                    const totalApproved = approvedAlready + approvedThisTime;
                    const remaining = budgetAmount - totalApproved;
                    body.innerHTML = `
                        <tr>
                            <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(budgetAmount)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(approvedAlready)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(approvedThisTime)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right">${formatMoney(totalApproved)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right ${remaining < 0 ? 'text-rose-600 font-semibold' : ''}">${formatMoney(remaining)}</td>
                        </tr>
                    `;
                };

                const loadProjectAmount = async (projectId = '', projectCode = '') => {
                    if (!isProjectWithdraw) {
                        setCachedProjectAmount(0, 0);
                        return 0;
                    }
                    const id = String(projectId || projectIdInput?.value || '').trim();
                    const code = String(projectCode || projectCodeInput?.value || '').trim();
                    if (id === '' && code === '') {
                        setCachedProjectAmount(0, 0);
                        return 0;
                    }
                    try {
                        const params = new URLSearchParams();
                        if (id !== '') params.set('project_id', id);
                        if (code !== '') params.set('project_code', code);
                        const excludeId = Number(withdrawRecordIdInput?.value || 0) || 0;
                        if (excludeId > 0) params.set('exclude_id', String(excludeId));
                        params.set('_', String(Date.now()));
                        const response = await fetch(`${projectAmountApi}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                        });
                        const result = await response.json();
                        setCachedProjectAmount(result?.data?.amount || 0, result?.data?.approved_already || 0);
                    } catch (error) {
                        setCachedProjectAmount(0, 0);
                    }
                    return cachedProjectAmount;
                };

                const buildProjectParagraph1 = () => {
                    const departmentName = getSelectedDepartmentName() || '..........';
                    const projectName = String(projectNameInput?.value || '').trim() || '..........';
                    const projectCode = String(projectCodeInput?.value || projectIdInput?.value || '').trim() || '..........';
                    const budget = getSelectedProjectBudget();
                    const budgetName = String(budget?.name || budget?.label || '').trim() || '..........';
                    // ใช้เฉพาะจำนวนเงินที่ระบบโครงการส่งมาพร้อมชื่อ/รหัสโครงการ
                    const projectAmountNumber = Number(cachedProjectAmount || 0);
                    const hasProjectAmount = Number.isFinite(projectAmountNumber) && projectAmountNumber > 0;
                    const budgetAmountPhrase = hasProjectAmount
                        ? `${formatBudgetAmount(projectAmountNumber)} บาท (${amountToThaiBahtText(projectAmountNumber)})`
                        : '.......... บาท';

                    return fillProjectParagraph1Template(
                        getProjectParagraph1Template(),
                        departmentName,
                        projectName,
                        projectCode,
                        budgetName,
                        budgetAmountPhrase
                    );
                };

                const syncProjectParagraph1 = () => {
                    if (!isProjectWithdraw || suppressProjectParagraphSync) return;
                    if (!withdrawParagraph1Input) return;
                    withdrawParagraph1Input.value = buildProjectParagraph1();
                };

                const syncProjectParagraph2 = () => {
                    if (!isProjectWithdraw || suppressProjectParagraphSync) return;
                    if (!withdrawParagraph2Input) return;
                    withdrawParagraph2Input.value = buildProjectParagraph2();
                };

                const syncProjectParagraphs = () => {
                    syncProjectParagraph1();
                    syncProjectParagraph2();
                };

                departmentSelect?.addEventListener('change', () => {
                    if (isProjectWithdraw) syncProjectParagraphs();
                });

                const applyProjectCreateDefaults = () => {
                    if (!isProjectWithdraw) return;
                    setWithdrawDateValue(getTodayIsoDate());
                    const template = memoTemplateDefaults && typeof memoTemplateDefaults === 'object'
                        ? memoTemplateDefaults
                        : null;
                    const templateSubject = String(template?.subject || '').trim();
                    if (withdrawSubjectInput) {
                        withdrawSubjectInput.value = templateSubject !== '' ? templateSubject : PROJECT_SUBJECT_DEFAULT;
                    }
                    if (withdrawParagraph3Input) {
                        withdrawParagraph3Input.value = String(template?.paragraph3 || '');
                    }
                    if (withdrawParagraph4Input) {
                        withdrawParagraph4Input.value = String(template?.paragraph4 || '');
                    }
                    setCurrentApplicant();
                    syncProjectParagraphs();
                };

                const loadBudgetSourceOptions = async (forceReload = false) => {
                    if (!isProjectWithdraw) return budgetSourceOptions;
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
                            const name = (path !== '' ? path : (String(row?.budget_source_name || '').trim() || code))
                                .replace(/\s*\/\s*/g, ' ')
                                .replace(/\s+/g, ' ')
                                .trim();
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
                                amount: Number(row?.amount || 0) || 0,
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

                const setItemRowBudget = (row, budgetId = '', budgetCode = '') => {
                    if (!row) return;
                    const budgetHiddenInput = row.querySelector('.withdraw-item-budget-value');
                    const budgetTextInput = row.querySelector('.withdraw-item-budget');
                    if (!budgetHiddenInput) return;
                    const option = findBudgetOption(budgetId, budgetCode);
                    const resolvedId = String(option?.id || budgetId || '').trim();
                    budgetHiddenInput.value = resolvedId;
                    if (budgetTextInput) {
                        budgetTextInput.value = String(option?.label || '').trim();
                    }
                    budgetHiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                    budgetHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const refreshBudgetItemRowOptions = () => {
                    if (!isProjectWithdraw || !itemRowsContainer) return;
                    queryItemCards().forEach((row) => {
                        const optionsKey = String(row.dataset.budgetOptionsKey || '').trim();
                        if (optionsKey === '') return;
                        window.PBMSearchableDropdown?.setOptions?.(optionsKey, budgetSourceOptions);
                        window.PBMSearchableDropdown?.setStatus?.(
                            optionsKey,
                            budgetSourceOptions.length === 0 ? 'ไม่พบข้อมูลงบประมาณ' : ''
                        );
                    });
                    window.PBMSearchableDropdown?.initAll?.();
                };

                const getCategorySearchOptions = () => categoryOptions.map((category) => ({
                    code: String(category.code || '').trim(),
                    label: String(category.label || category.name || '').trim(),
                }));

                const refreshCategoryItemRowOptions = () => {
                    if (!itemRowsContainer) return;
                    const categorySearchOptions = getCategorySearchOptions();
                    queryItemCards().forEach((row) => {
                        const optionsKey = String(row.dataset.categoryOptionsKey || '').trim();
                        if (optionsKey === '') return;
                        window.PBMSearchableDropdown?.setOptions?.(optionsKey, categorySearchOptions);
                        window.PBMSearchableDropdown?.setStatus?.(optionsKey, getPayListDropdownStatusMessage());
                    });
                    window.PBMSearchableDropdown?.initAll?.();
                };

                const syncItemRowCategoryName = (row) => {
                    if (!row) return;
                    const hiddenInput = row.querySelector('.withdraw-item-category-value');
                    const textInput = row.querySelector('.withdraw-item-category');
                    const selectedCode = String(hiddenInput?.value || '').trim();
                    const selectedOption = categoryOptions.find((option) => String(option.code || '').trim() === selectedCode);
                    const label = String(selectedOption?.label || selectedOption?.name || textInput?.value || '').trim();
                    row.dataset.categoryName = label;
                };

                const fillItemRow = (currentRow, item = {}) => {
                    if (!currentRow) return;
                    const categoryHiddenInput = currentRow.querySelector('.withdraw-item-category-value');
                    const categoryTextInput = currentRow.querySelector('.withdraw-item-category');
                    const nameInput = currentRow.querySelector('.withdraw-item-name');
                    const amountInput = currentRow.querySelector('.withdraw-item-amount');
                    setItemRowBudget(
                        currentRow,
                        item.budget_manage_id || budgetManageIdInput?.value || '',
                        item.budget_manage_code || budgetManageCodeInput?.value || ''
                    );
                    if (categoryHiddenInput) {
                        const selectedCode = resolveCategoryOptionCode(item.category_code || item.category || '');
                        let resolvedCode = selectedCode;
                        let resolvedLabel = '';
                        if (resolvedCode !== '') {
                            const selectedOption = categoryOptions.find((option) => String(option.code || '').trim() === resolvedCode);
                            resolvedLabel = String(selectedOption?.label || selectedOption?.name || '').trim();
                        }
                        if (resolvedCode === '') {
                            const wantedName = String(item.category_name || item.expense_name || '').trim().toLowerCase();
                            if (wantedName !== '') {
                                const matched = categoryOptions.find((option) => {
                                    const label = String(option.label || option.name || '').trim().toLowerCase();
                                    return label === wantedName || label.includes(wantedName) || wantedName.includes(label);
                                });
                                if (matched) {
                                    resolvedCode = String(matched.code || '').trim();
                                    resolvedLabel = String(matched.label || matched.name || '').trim();
                                }
                            }
                        }
                        categoryHiddenInput.value = resolvedCode;
                        currentRow.dataset.categoryName = resolvedLabel !== ''
                            ? resolvedLabel
                            : String(item.category_name || item.expense_name || '').trim();
                        if (categoryTextInput) {
                            categoryTextInput.value = resolvedLabel !== ''
                                ? resolvedLabel
                                : String(item.category_name || item.expense_name || '').trim();
                        }
                        categoryHiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                        categoryHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (nameInput) {
                        let itemName = String(item.name || '').trim();
                        if (isProjectWithdraw) {
                            itemName = itemName.replace(/\s*\((?:ม\.ค\.|ก\.พ\.|มี\.ค\.|เม\.ย\.|พ\.ค\.|มิ\.ย\.|ก\.ค\.|ส\.ค\.|ก\.ย\.|ต\.ค\.|พ\.ย\.|ธ\.ค\.)\)\s*$/u, '').trim();
                        }
                        nameInput.value = itemName;
                    }
                    if (amountInput) amountInput.value = String(item.amount ?? '');
                };

                const createItemRow = () => {
                    itemRowCounter += 1;
                    const dropdownZ = 50 + itemRowCounter;
                    const categoryInputId = `withdraw-item-category-${itemRowCounter}`;
                    const categoryOptionsKey = `withdraw-item-category-options-${itemRowCounter}`;
                    const budgetInputId = `withdraw-item-budget-${itemRowCounter}`;
                    const budgetOptionsKey = `withdraw-item-budget-options-${itemRowCounter}`;
                    const categorySearchOptions = getCategorySearchOptions();
                    const defaultBudgetId = String(budgetManageIdInput?.value || '').trim();
                    const defaultBudgetOption = findBudgetOption(defaultBudgetId, budgetManageCodeInput?.value || '');
                    const defaultBudgetLabel = String(defaultBudgetOption?.label || '').trim();
                    const resolvedBudgetId = String(defaultBudgetOption?.id || defaultBudgetId || '').trim();

                    const row = document.createElement('div');
                    row.className = 'withdraw-item-card rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50/70 p-4 shadow-sm ring-1 ring-slate-100 focus-within:border-sky-300 focus-within:shadow-[0_0_0_3px_rgb(224_242_254/0.6)]';
                    row.dataset.budgetOptionsKey = isProjectWithdraw ? budgetOptionsKey : '';
                    row.dataset.categoryOptionsKey = categoryOptionsKey;

                    const budgetField = isProjectWithdraw ? `
                        <div class="withdraw-item-field min-w-0 sm:col-span-2" data-field="budget">
                            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                <i class="fas fa-wallet w-3.5 text-center text-[10px] text-emerald-600"></i>
                                งบประมาณ
                            </label>
                            <div
                                class="relative isolate pbm-searchable-select-wrapper"
                                data-pbm-searchable-select-wrapper="1"
                                data-pbm-options-key="${budgetOptionsKey}"
                                data-pbm-value-key="id"
                                data-pbm-text-key="label"
                                data-pbm-search-keys="label,code,name">
                                <input id="${budgetInputId}-text" type="search" placeholder="พิมพ์หรือเลือกงบประมาณ..."
                                    class="withdraw-item-budget w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                    autocomplete="off"
                                    value="${escapeHtml(defaultBudgetLabel)}"
                                    data-pbm-searchable-select-text />
                                <div id="${budgetInputId}-suggest"
                                    class="absolute left-0 right-0 top-full hidden max-h-60 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-2xl"
                                    style="z-index: ${dropdownZ}"
                                    data-pbm-searchable-select-suggest>
                                </div>
                                <input type="hidden" id="${budgetInputId}" value="${escapeHtml(resolvedBudgetId)}" class="withdraw-item-budget-value" data-pbm-searchable-select-hidden />
                            </div>
                        </div>
                    ` : '';

                    const manageActions = isProjectWithdraw ? '' : `
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button type="button"
                                class="withdraw-remove-item inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 ring-1 ring-rose-100 transition hover:bg-rose-100"
                                data-ui-tooltip="ลบรายการ" data-ui-tooltip-placement="top" aria-label="ลบรายการ">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    `;

                    row.innerHTML = `
                        <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 py-2.5">
                            <p class="m-0 flex min-h-8 items-center text-sm font-semibold leading-5 text-slate-800">รายการที่ <span data-row-index>1</span></p>
                            ${manageActions}
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            ${budgetField}
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
                                    <input id="${categoryInputId}-text" type="search" placeholder="พิมพ์หรือเลือกหมวด..."
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
                                <input type="text" placeholder="ระบุรายการ"
                                    class="withdraw-item-name w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
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
                    window.PBMSearchableDropdown?.setOptions?.(categoryOptionsKey, categorySearchOptions);
                    window.PBMSearchableDropdown?.setStatus?.(categoryOptionsKey, getPayListDropdownStatusMessage());
                    if (isProjectWithdraw) {
                        window.PBMSearchableDropdown?.setOptions?.(budgetOptionsKey, budgetSourceOptions);
                        window.PBMSearchableDropdown?.setStatus?.(
                            budgetOptionsKey,
                            budgetSourceOptions.length === 0 ? 'ไม่พบข้อมูลงบประมาณ' : ''
                        );
                    }
                    window.PBMSearchableDropdown?.initAll?.();
                    refreshRowIndex();
                    refreshTotal();
                    if (isProjectWithdraw) {
                        const budgetHiddenInput = row.querySelector('.withdraw-item-budget-value');
                        budgetHiddenInput?.addEventListener('input', () => {
                            const selectedBudgetId = String(budgetHiddenInput.value || '').trim();
                            if (budgetManageIdInput && selectedBudgetId !== '') {
                                budgetManageIdInput.value = selectedBudgetId;
                            }
                            const selectedOption = findBudgetOption(selectedBudgetId);
                            if (budgetManageCodeInput && selectedOption?.code) {
                                budgetManageCodeInput.value = selectedOption.code;
                            }
                            syncProjectParagraphs();
                        });
                        budgetHiddenInput?.addEventListener('change', () => syncProjectParagraphs());
                    }
                    const categoryHiddenInput = row.querySelector('.withdraw-item-category-value');
                    categoryHiddenInput?.addEventListener('input', () => {
                        syncItemRowCategoryName(row);
                        if (isProjectWithdraw) syncProjectParagraph2();
                    });
                    categoryHiddenInput?.addEventListener('change', () => {
                        syncItemRowCategoryName(row);
                        if (isProjectWithdraw) syncProjectParagraph2();
                    });
                };

                addItemRowButton?.addEventListener('click', createItemRow);
                addActorRowButton?.addEventListener('click', () => createActorRow());

                itemRowsContainer.addEventListener('input', (event) => {
                    if (event.target.classList.contains('withdraw-item-category') || event.target.matches('[data-pbm-searchable-select-hidden]')) {
                        syncDocumentNumber();
                    }
                    if (event.target.classList.contains('withdraw-item-amount')) {
                        refreshTotal();
                    }
                    if (isProjectWithdraw && event.target.classList.contains('withdraw-item-name')) {
                        syncProjectParagraph2();
                    }
                });
                itemRowsContainer.addEventListener('change', (event) => {
                    if (event.target.classList.contains('withdraw-item-category') || event.target.matches('[data-pbm-searchable-select-hidden]')) {
                        syncDocumentNumber();
                    }
                    if (event.target.classList.contains('withdraw-item-budget-value')) {
                        syncProjectParagraphs();
                    }
                });

                itemRowsContainer.addEventListener('click', (event) => {
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
                    if (isProjectWithdraw) {
                        await loadBudgetSourceOptions();
                    } else {
                        createItemRow();
                    }
                    if (isProjectWithdraw) {
                        applyProjectCreateDefaults();
                    }
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
                    if (revisionExplanationInput) revisionExplanationInput.value = '';
                    revisionExplanationBox?.classList.add('hidden');
                    if (withdrawSaveDraftButton) {
                        withdrawSaveDraftButton.innerHTML = '<i class="far fa-save mr-1"></i>บันทึกแบบร่าง';
                    }
                    withdrawGeneralForm?.reset();
                    if (itemRowsContainer) {
                        itemRowsContainer.innerHTML = '';
                        if (!isProjectWithdraw) {
                            createItemRow();
                        }
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
                    if (reviewerStaffSelect && defaultReviewerStaffId) {
                        reviewerStaffSelect.value = defaultReviewerStaffId;
                        reviewerStaffSelect.dispatchEvent(new Event('input', { bubbles: true }));
                        reviewerStaffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    applyMemoTemplateFields(memoTemplateDefaults);
                    if (projectIdInput) projectIdInput.value = '';
                    if (projectCodeInput) projectCodeInput.value = '';
                    if (projectNameInput) projectNameInput.value = '';
                    if (projectActivityIdInput) projectActivityIdInput.value = '';
                    if (projectActivityNameInput) projectActivityNameInput.value = '';
                    if (projectActivityDetailInput) projectActivityDetailInput.value = '';
                    lockedProjectActivityName = '';
                    lockedProjectActivityDetail = '';
                    if (projectHandoffTokenInput) projectHandoffTokenInput.value = '';
                    setCachedProjectAmount(0, 0);
                    if (budgetManageIdInput) budgetManageIdInput.value = '';
                    if (budgetManageCodeInput) budgetManageCodeInput.value = '';
                    projectSourceBox?.classList.add('hidden');
                    if (projectSourceText) projectSourceText.textContent = '-';
                    refreshTotal();
                    if (isProjectWithdraw) {
                        applyProjectCreateDefaults();
                    }
                };

                const memoTemplateApi = <?php echo json_encode(url('/withdraw/api/memo-template'), 15, 512) ?>;

                const getMemoFormBlockElement = (blockKey) => {
                    const key = String(blockKey || '');
                    const byDataAttr = document.querySelector(`[data-memo-block="${key}"]`);
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
                        return document.getElementById('withdraw-item-rows')?.closest('.rounded-2xl')
                            || document.getElementById('withdraw-add-item-row')?.closest('.rounded-2xl')
                            || null;
                    }
                    return null;
                };

                const renderApprovalRoutePreview = (template) => {
                    if (!approvalRouteListEl) return;
                    const labelByCode = {};
                    (approvalStepOptions || []).forEach((option) => {
                        const code = String(option.code || '');
                        if (code !== '') {
                            labelByCode[code] = String(option.label || code);
                        }
                    });
                    const codes = Array.isArray(template?.approval_step_codes) ? template.approval_step_codes : [];
                    const items = codes
                        .filter((code) => String(code || '').trim() !== '' && String(code) !== 'additional_reviewer')
                        .map((code, index) => {
                            const key = String(code);
                            return `<li class="flex items-start gap-2 rounded-lg border border-indigo-100/80 bg-white/80 px-2.5 py-1.5">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700">${index + 1}</span>
                                <span>${escapeHtml(labelByCode[key] || key)}</span>
                            </li>`;
                        });
                    approvalRouteListEl.innerHTML = items.length > 0
                        ? items.join('')
                        : '<li class="text-xs text-slate-500">ยังไม่ได้กำหนดเส้นทางการอนุมัติในเทมเพลต</li>';

                    const hasReviewerHead = codes.includes('reviewer_head');
                    if (reviewerFieldWrap) {
                        reviewerFieldWrap.classList.toggle('hidden', !hasReviewerHead);
                    }

                    approvalRouteExtraHint?.classList.toggle('hidden', template?.process_actors_enabled !== true);
                };

                const isMemoParagraphEnabled = (template, key) => {
                    const flags = template?.paragraph_enabled;
                    if (!flags || typeof flags !== 'object' || flags[key] === undefined) return true;
                    return flags[key] !== false && flags[key] !== 0 && flags[key] !== '0';
                };

                const applyMemoParagraphVisibility = (template) => {
                    ['paragraph1', 'paragraph2', 'paragraph3', 'paragraph4'].forEach((key) => {
                        const blockEl = getMemoFormBlockElement(key);
                        if (!blockEl) return;
                        const enabled = isMemoParagraphEnabled(template, key);
                        blockEl.classList.toggle('hidden', !enabled);
                        if (!enabled && String(withdrawRecordIdInput?.value || '').trim() === '') {
                            const input = blockEl.querySelector('textarea');
                            if (input) input.value = '';
                        }
                    });
                };

                const applyMemoTemplateFields = (template) => {
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
                    renderApprovalRoutePreview(template);
                    if (isProjectWithdraw && String(withdrawRecordIdInput?.value || '').trim() === '') {
                        applyProjectCreateDefaults();
                    }
                };

                const reorderMemoFormBlocks = (blockOrder) => {
                    const formEl = document.getElementById('withdraw-general-form');
                    if (!formEl || !Array.isArray(blockOrder) || blockOrder.length === 0) return;
                    const applicantGrid = document.getElementById('withdraw-applicant-staff')?.closest('.grid.grid-cols-1');
                    const insertBeforeNode = applicantGrid || null;
                    blockOrder.forEach((blockKey) => {
                        const blockEl = getMemoFormBlockElement(String(blockKey || ''));
                        if (!blockEl) return;
                        if (insertBeforeNode) {
                            formEl.insertBefore(blockEl, insertBeforeNode);
                        } else {
                            formEl.appendChild(blockEl);
                        }
                    });
                    const projectSummaryEl = document.querySelector('[data-memo-block="project-budget-summary"]');
                    const paragraph3El = getMemoFormBlockElement('paragraph3');
                    if (projectSummaryEl && paragraph3El?.parentNode) {
                        paragraph3El.parentNode.insertBefore(projectSummaryEl, paragraph3El.nextSibling);
                    }
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

                        memoTemplateDefaults = template;
                        applyMemoTemplateFields(template);
                    } catch (error) {
                        applyMemoTemplateFields(memoTemplateDefaults);
                    }
                };

                if (memoTemplateDefaults?.block_order) {
                    reorderMemoFormBlocks(memoTemplateDefaults.block_order);
                }
                applyMemoTemplateFields(memoTemplateDefaults);

                openCreateModalButton?.addEventListener('click', () => {
                    resetGeneralForm();
                });

                window.addEventListener('open-modal', async (event) => {
                    if (String(event?.detail || '') !== 'withdraw-request-modal') return;
                    if (String(withdrawRecordIdInput?.value || '').trim() !== '') return;
                    if (applyingProjectHandoff) return;
                    await applyMemoTemplate(withdrawKind === 'project' ? 'project' : 'general', 'default');
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
                        if (revisionExplanationInput) revisionExplanationInput.value = '';
                        if (isRevisionEditMode && isProjectWithdraw) {
                            revisionExplanationBox?.classList.remove('hidden');
                        } else {
                            revisionExplanationBox?.classList.add('hidden');
                        }
                        if (withdrawSaveDraftButton) {
                            withdrawSaveDraftButton.innerHTML = isRevisionEditMode
                                ? '<i class="fas fa-rotate-right mr-1"></i>บันทึกการแก้ไข'
                                : '<i class="far fa-save mr-1"></i>บันทึกแบบร่าง';
                        }
                        if (withdrawRecordIdInput) withdrawRecordIdInput.value = String(row.id || '');
                        if (withdrawTypeInput && String(row.withdraw_type || '').trim() !== '') {
                            withdrawTypeInput.value = String(row.withdraw_type || withdrawKind);
                        }
                        if (projectIdInput) projectIdInput.value = String(row.project_id || '');
                        if (projectCodeInput) projectCodeInput.value = String(row.project_code || '');
                        if (projectNameInput) projectNameInput.value = String(row.project_name || '');
                        if (projectActivityIdInput) projectActivityIdInput.value = String(row.project_activity_id || '');
                        if (projectActivityNameInput) projectActivityNameInput.value = String(row.project_activity_name || '');
                        lockProjectActivityFromSource(
                            String(row.project_activity_name || ''),
                            String(row.project_activity_detail || '').trim()
                        );
                        if (budgetManageIdInput) budgetManageIdInput.value = String(row.budget_manage_id || '');
                        if (isProjectWithdraw) {
                            await loadBudgetSourceOptions();
                            const amountFromDetail = Number(row.project_amount || 0);
                            await loadProjectAmount(row.project_id || '', row.project_code || '');
                            if (Number.isFinite(amountFromDetail) && amountFromDetail > 0) {
                                setCachedProjectAmount(amountFromDetail, cachedProjectApprovedAlready);
                            }
                            suppressProjectParagraphSync = true;
                        }
                        if (departmentSelect) {
                            departmentSelect.value = String(row.department_code || '');
                            departmentSelect.dispatchEvent(new Event('input', { bubbles: true }));
                            departmentSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (documentNumberInput) documentNumberInput.value = String(row.document_number || '');
                        setWithdrawDateValue(String(row.document_date || ''));
                        if (withdrawSubjectInput) withdrawSubjectInput.value = String(row.subject || '');
                        if (withdrawToInput) withdrawToInput.value = String(row.to || '');
                        if (withdrawParagraph1Input) withdrawParagraph1Input.value = String(row.paragraph1 || '');
                        if (withdrawParagraph2Input) withdrawParagraph2Input.value = String(row.paragraph2 || '');
                        if (withdrawParagraph3Input) withdrawParagraph3Input.value = String(row.paragraph3 || '');
                        if (withdrawParagraph4Input) withdrawParagraph4Input.value = String(row.paragraph4 || '');
                        applyMemoParagraphVisibility(row.paragraph_enabled ? row : memoTemplateDefaults);
                        if (isProjectWithdraw) {
                            const storedActivityDetail = String(row.project_activity_detail || '').trim();
                            const paragraph2Text = String(row.paragraph2 || '');
                            const detailFromParagraph = storedActivityDetail !== ''
                                ? storedActivityDetail
                                : (
                                    extractLabeledMemoValue(paragraph2Text, PROJECT_P2_DETAIL_LABEL, PROJECT_P2_EXPENSE_LABEL)
                                    || extractLabeledMemoValue(paragraph2Text, PROJECT_P2_DETAIL_LABEL_LEGACY, PROJECT_P2_EXPENSE_LABEL)
                                );
                            lockProjectActivityFromSource(
                                String(row.project_activity_name || ''),
                                detailFromParagraph
                            );
                        }
                        if (applicantStaffSelect) {
                            applicantStaffSelect.value = String(row.applicant_staff_id || '');
                            applicantStaffSelect.dispatchEvent(new Event('input', { bubbles: true }));
                            applicantStaffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (reviewerStaffSelect) {
                            reviewerStaffSelect.value = String(row.reviewer_staff_id || '');
                            reviewerStaffSelect.dispatchEvent(new Event('input', { bubbles: true }));
                            reviewerStaffSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        itemRowsContainer.innerHTML = '';
                        const rowItems = Array.isArray(row.items) ? row.items : [];
                        if (rowItems.length === 0 && !isProjectWithdraw) {
                            createItemRow();
                        } else {
                            rowItems.forEach((item) => {
                                createItemRow();
                                fillItemRow(itemRowsContainer.lastElementChild, item);
                            });
                        }
                        refreshTotal();
                        syncDocumentNumber();
                        suppressProjectParagraphSync = false;
                        if (isProjectWithdraw) {
                            // รีเฟรชยอดจำนวนในย่อหน้า 1 ให้เป็นยอดโครงการ/งบที่เลือก
                            syncProjectParagraph1();
                        }

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
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-request-modal' }));
                    } catch (error) {
                        suppressProjectParagraphSync = false;
                        window.toast?.error?.('ไม่สามารถโหลดข้อมูลเพื่อแก้ไขได้', { timer: 1400 });
                    }
                };

                document.addEventListener('click', async (event) => {
                    const button = event.target.closest('[data-action]');
                    if (!button) return;

                    const action = String(button.dataset.action || '');
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
                            const row = findGeneralListRowById(id) || {};
                            if (proceedDetailDate) proceedDetailDate.textContent = String(row.date || '-').trim() || '-';
                            if (proceedDetailSubject) proceedDetailSubject.textContent = String(row.subject || '-').trim() || '-';
                            if (proceedDetailApplicant) proceedDetailApplicant.textContent = String(row.manager || '-').trim() || '-';
                            if (proceedDetailAmount) proceedDetailAmount.textContent = Number(row.amount || 0).toLocaleString('th-TH');
                            if (proceedProcurementBox) proceedProcurementBox.classList.add('hidden');
                            if (proceedProcurementList) proceedProcurementList.innerHTML = '';
                            if (proceedProcurementCount) proceedProcurementCount.textContent = '0';
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
                            if (proceedDetailCode) proceedDetailCode.textContent = fallbackCode;
                            if (pendingProceedAction === 'proceed') {
                                let memoCodePrefix = '';
                                let memoNextRunningNo = 0;
                                try {
                                    const detailResponse = await fetch(`${generalDetailApiBase}/${id}`, { headers: { Accept: 'application/json' } });
                                    const detailJson = await detailResponse.json();
                                    if (detailJson?.status !== 'success' || !detailJson?.data) {
                                        throw new Error('detail_load_failed');
                                    }
                                    const detail = detailJson.data;
                                    const departmentCode = String(detail.department_code || '').trim();
                                    const documentDate = String(detail.document_date || '').trim();
                                    const documentNumberBase = String(detail.document_number || '').trim();
                                    const displayCode = String(detail.document_number_display || '').trim();
                                    const existingRunningNo = Number(detail.running_no || 0);
                                    memoCodePrefix = documentNumberBase !== '' ? documentNumberBase : displayCode;
                                    if (existingRunningNo > 0 && displayCode !== '') {
                                        if (proceedDetailCode) proceedDetailCode.textContent = displayCode;
                                    } else {
                                    const runningNoApiUrl = new URL(generalNextRunningNoApi, window.location.origin);
                                    if (departmentCode !== '') {
                                        runningNoApiUrl.searchParams.set('department_code', departmentCode);
                                    }
                                    if (documentDate !== '') {
                                        runningNoApiUrl.searchParams.set('document_date', documentDate);
                                    }
                                    if (documentNumberBase !== '') {
                                        runningNoApiUrl.searchParams.set('document_number', documentNumberBase);
                                    }
                                    runningNoApiUrl.searchParams.set('withdraw_type', withdrawKind === 'project' ? 'project' : 'general');
                                    const runningResponse = await fetch(runningNoApiUrl.toString(), { headers: { Accept: 'application/json' } });
                                    const runningJson = await runningResponse.json();
                                    memoNextRunningNo = Number(runningJson?.data?.running_no || 0);
                                    const centralFullNumber = String(runningJson?.data?.full_number || '').trim();
                                    if (proceedDetailCode && centralFullNumber !== '') {
                                        proceedDetailCode.textContent = centralFullNumber;
                                    } else if (proceedDetailCode && memoNextRunningNo > 0 && memoCodePrefix !== '') {
                                        proceedDetailCode.textContent = `${memoCodePrefix}${memoNextRunningNo}`;
                                    }
                                    }
                                } catch (error) {
                                    // Keep fallback code from table when preview API fails.
                                }

                                try {
                                    const procurementUrl = new URL(procurementListApi, window.location.origin);
                                    procurementUrl.searchParams.set('withdraw_id', String(id));
                                    const procurementResponse = await fetch(procurementUrl.toString(), {
                                        headers: { Accept: 'application/json' },
                                    });
                                    const procurementJson = await procurementResponse.json();
                                    const procurementRows = Array.isArray(procurementJson?.data) ? procurementJson.data : [];
                                    if (procurementRows.length > 0 && proceedProcurementList && proceedProcurementBox) {
                                        proceedProcurementList.innerHTML = procurementRows.map((item, index) => {
                                            const typeLabel = String(item.type_label || item.type || '-').trim() || '-';
                                            const title = String(item.item_name || item.procurement || item.subject || '-').trim() || '-';
                                            const dateText = String(item.date_modify_display || item.document_date || '-').trim() || '-';
                                            // เลขที่เอกสารจัดซื้อจัดจ้าง = ลำดับถัดจากเลขที่หนังสือด้านบน (memo + 1, +2, ...)
                                            const procurementRunningNo = memoNextRunningNo > 0
                                                ? memoNextRunningNo + index + 1
                                                : 0;
                                            const existingDocNo = String(item.document_number_display || item.document_number || '').trim();
                                            const predictedDocNo = (memoCodePrefix !== '' && procurementRunningNo > 0)
                                                ? `${memoCodePrefix}${procurementRunningNo}`
                                                : existingDocNo;
                                            const docNoHtml = predictedDocNo !== ''
                                                ? `<div class="mt-0.5 text-xs font-semibold text-slate-700">ที่ ${escapeHtml(predictedDocNo)}</div>`
                                                : '';
                                            return `
                                                <li class="rounded-lg border border-orange-100 bg-white px-3 py-2">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="min-w-0">
                                                            <div class="text-xs font-semibold text-orange-800">${escapeHtml(typeLabel)}</div>
                                                            <div class="mt-0.5 font-medium text-slate-800">${escapeHtml(title)}</div>
                                                            ${docNoHtml}
                                                            <div class="mt-0.5 text-xs text-slate-500">อัปเดตล่าสุด: ${escapeHtml(dateText)}</div>
                                                        </div>
                                                        <span class="shrink-0 rounded-full bg-orange-50 px-2 py-0.5 text-[11px] font-bold text-orange-700 ring-1 ring-orange-100">${procurementRunningNo > 0 ? procurementRunningNo : (index + 1)}</span>
                                                    </div>
                                                </li>
                                            `;
                                        }).join('');
                                        if (proceedProcurementCount) {
                                            proceedProcurementCount.textContent = String(procurementRows.length);
                                        }
                                        proceedProcurementBox.classList.remove('hidden');
                                    }
                                } catch (error) {
                                    // Ignore procurement preview errors; proceed confirm still works.
                                }
                            }
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-general-proceed-confirm-modal' }));
                            return;
                        }

                        if (action === 'open-procurement') {
                            const status = String(button.dataset.withdrawStatus || '').trim();
                            if (window.WithdrawProjectProcurement?.openForWithdraw) {
                                window.WithdrawProjectProcurement.openForWithdraw(id, { status });
                            } else {
                                window.WithdrawProjectProcurement?.openTypePicker?.(id);
                            }
                            return;
                        }

                        if (action === 'open-finance-docs') {
                            await openFinanceDocsModalById(id);
                            return;
                        }

                        if (action === 'view') {
                            await window.WithdrawGeneralDetailModal?.openById?.(id);
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
                            await openMemoPdfById(id);
                            return;
                        }

                        if (action === 'open-memo-finance-pdf') {
                            await openMemoFinancePdfById(id);
                        }
                });

                const overlayProjectHandoff = async (payload) => {
                    const data = payload && typeof payload === 'object' ? payload : {};
                    const activityName = String(data.activity_name || '').trim();
                    const projectName = String(data.project_name || '').trim();
                    const projectCode = String(data.project_code || data.project_id || '').trim();
                    if (projectIdInput) projectIdInput.value = String(data.project_id || '');
                    if (projectCodeInput) projectCodeInput.value = projectCode;
                    if (projectNameInput) projectNameInput.value = projectName;
                    if (projectActivityIdInput) projectActivityIdInput.value = String(data.activity_id || '');
                    if (projectActivityNameInput) projectActivityNameInput.value = activityName;
                    lockProjectActivityFromSource(activityName, String(data.activity_detail || '').trim());
                    if (budgetManageIdInput) budgetManageIdInput.value = String(data.budget_manage_id || '');
                    if (budgetManageCodeInput) budgetManageCodeInput.value = String(data.budget_manage_code || '');
                    // จำนวนเงินโครงการจาก API handoff (ฟิลด์เดียวกับที่ส่งชื่อ/รหัสโครงการ)
                    const amountFromHandoff = Number(
                        data.project_amount
                        ?? data.project_budget_amount
                        ?? data.budget_total
                        ?? data.total_budget
                        ?? data.approved_budget
                        ?? data.estimated_total
                        ?? data.total_amount
                        ?? data.total
                        ?? 0
                    );
                    await loadProjectAmount(String(data.project_id || ''), projectCode);
                    if (Number.isFinite(amountFromHandoff) && amountFromHandoff > 0) {
                        setCachedProjectAmount(amountFromHandoff, cachedProjectApprovedAlready);
                    }
                    if (withdrawTypeInput) withdrawTypeInput.value = 'project';
                    const sourceParts = [
                        projectCode !== '' ? projectCode : '',
                        projectName,
                        activityName,
                    ].filter((part) => String(part || '').trim() !== '');
                    if (projectSourceText) {
                        projectSourceText.textContent = sourceParts.length > 0
                            ? sourceParts.join(' — ')
                            : 'ดึงรายการเบิกจ่ายจากระบบโครงการแล้ว';
                    }
                    projectSourceBox?.classList.remove('hidden');

                    itemRowsContainer.innerHTML = '';
                    const rowItems = Array.isArray(data.items) ? data.items : [];
                    rowItems.forEach((item) => {
                        createItemRow();
                        fillItemRow(itemRowsContainer.lastElementChild, {
                            ...item,
                            budget_manage_id: item.budget_manage_id || data.budget_manage_id,
                            budget_manage_code: item.budget_manage_code || data.budget_manage_code,
                        });
                    });
                    applyProjectCreateDefaults();
                    refreshTotal();
                    syncDocumentNumber();
                };

                const PENDING_HANDOFF_STORAGE_KEY = 'withdraw.project.pending_handoffs.v1';
                const isArrayLikeItems = (value) => Array.isArray(value);
                const setHandoffExpiredNoticeVisible = (visible) => {
                    document.getElementById('withdraw-handoff-expired-notice')?.classList.toggle('hidden', !visible);
                };

                const readLocalPendingHandoffs = () => {
                    try {
                        const raw = localStorage.getItem(PENDING_HANDOFF_STORAGE_KEY);
                        const parsed = raw ? JSON.parse(raw) : [];
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (error) {
                        return [];
                    }
                };

                const writeLocalPendingHandoffs = (items) => {
                    try {
                        localStorage.setItem(PENDING_HANDOFF_STORAGE_KEY, JSON.stringify(Array.isArray(items) ? items : []));
                    } catch (error) {
                        // Ignore quota / private mode errors.
                    }
                };

                const upsertLocalPendingHandoff = (token, payload) => {
                    const handoffToken = String(token || '').trim();
                    const data = payload && typeof payload === 'object' ? payload : {};
                    if (handoffToken === '') return;
                    const items = isArrayLikeItems(data.items) ? data.items : [];
                    const next = {
                        token: handoffToken,
                        project_id: String(data.project_id || '').trim(),
                        project_code: String(data.project_code || '').trim(),
                        project_name: String(data.project_name || '').trim(),
                        activity_name: String(data.activity_name || '').trim(),
                        item_count: items.length,
                        project_amount: Number(data.project_amount || 0) || 0,
                        payload: data,
                        updated_at: new Date().toISOString(),
                        source: 'local',
                    };
                    const rows = readLocalPendingHandoffs().filter((row) => String(row?.token || '').trim() !== handoffToken);
                    rows.unshift(next);
                    writeLocalPendingHandoffs(rows.slice(0, 20));
                };

                const removeLocalPendingHandoff = (token) => {
                    const handoffToken = String(token || '').trim();
                    if (handoffToken === '') return;
                    writeLocalPendingHandoffs(
                        readLocalPendingHandoffs().filter((row) => String(row?.token || '').trim() !== handoffToken)
                    );
                };

                const getLocalPendingPayload = (token) => {
                    const handoffToken = String(token || '').trim();
                    const row = readLocalPendingHandoffs().find((item) => String(item?.token || '').trim() === handoffToken);
                    return row?.payload && typeof row.payload === 'object' ? row.payload : null;
                };

                const openProjectHandoffPayload = async (handoffToken, data) => {
                    applyingProjectHandoff = true;
                    resetGeneralForm();
                    await applyMemoTemplate('project', 'default');
                    if (projectHandoffTokenInput) projectHandoffTokenInput.value = handoffToken;
                    await overlayProjectHandoff(data);
                    if (cachedProjectAmount <= 0) {
                        await loadProjectAmount(
                            String(data.project_id || ''),
                            String(data.project_code || '')
                        );
                    }
                    syncProjectParagraph1();
                    upsertLocalPendingHandoff(handoffToken, data);
                    setHandoffExpiredNoticeVisible(false);
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-request-modal' }));
                    applyingProjectHandoff = false;
                };

                const applyProjectHandoff = async (token) => {
                    const handoffToken = String(token || '').trim();
                    if (handoffToken === '') return;
                    try {
                        await loadPayListOptions();
                        await loadBudgetSourceOptions();

                        if (projectHandoffApiBase) {
                            const response = await fetch(`${projectHandoffApiBase}/${encodeURIComponent(handoffToken)}`, {
                                headers: { Accept: 'application/json' },
                            });
                            const result = await response.json();
                            if (result?.status === 'success' && result?.data) {
                                await openProjectHandoffPayload(handoffToken, result.data);
                                return;
                            }
                        }

                        const localPayload = getLocalPendingPayload(handoffToken);
                        if (localPayload) {
                            await openProjectHandoffPayload(handoffToken, localPayload);
                            window.toast?.success?.('เปิดจากข้อมูลที่เก็บไว้ในเครื่องนี้', { timer: 2200 });
                            return;
                        }

                        setHandoffExpiredNoticeVisible(true);
                        window.toast?.error?.('ไม่พบข้อมูลจากระบบโครงการ หรือลิงก์หมดอายุ กรุณากลับไปกดปุ่มทำรายการต่อจากระบบโครงการ', { timer: 3200 });
                    } catch (error) {
                        applyingProjectHandoff = false;
                        const localPayload = getLocalPendingPayload(handoffToken);
                        if (localPayload) {
                            try {
                                await openProjectHandoffPayload(handoffToken, localPayload);
                                return;
                            } catch (innerError) {
                                // fall through
                            }
                        }
                        setHandoffExpiredNoticeVisible(true);
                        window.toast?.error?.('ไม่สามารถดึงรายการจากระบบโครงการได้', { timer: 1800 });
                    }
                };

                const openDeepLinkedWithdraw = async () => {
                    const params = new URLSearchParams(window.location.search);
                    const prefillEditId = Number(params.get('edit_id') || 0);
                    const viewId = Number(
                        params.get('id')
                        || params.get('withdraw_id')
                        || params.get('view_id')
                        || 0
                    );

                    if (prefillEditId > 0) {
                        await openEditModalById(prefillEditId);
                        return;
                    }

                    if (viewId > 0) {
                        try {
                            await window.WithdrawGeneralDetailModal?.openById?.(viewId);
                        } catch (error) {
                            window.toast?.error?.('ไม่สามารถเปิดรายละเอียดรายการเบิกจ่ายได้', { timer: 1800 });
                        }
                        try {
                            const cleanUrl = new URL(window.location.href);
                            ['id', 'withdraw_id', 'view_id'].forEach((key) => cleanUrl.searchParams.delete(key));
                            window.history.replaceState({}, '', cleanUrl.pathname + (cleanUrl.search ? cleanUrl.search : '') + cleanUrl.hash);
                        } catch (error) {
                            // Ignore URL cleanup errors.
                        }
                        return;
                    }

                    if (String(projectHandoffToken || '').trim() !== '') {
                        applyProjectHandoff(projectHandoffToken);
                    }
                };

                // Detail modal script may initialize in the same alpine:initialized turn.
                setTimeout(() => {
                    openDeepLinkedWithdraw();
                }, 0);

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

                        const itemRows = queryItemCards().map((row) => {
                            const categoryHiddenInput = row.querySelector('.withdraw-item-category-value');
                            const budgetHiddenInput = row.querySelector('.withdraw-item-budget-value');
                            const nameInput = row.querySelector('.withdraw-item-name');
                            const amountInput = row.querySelector('.withdraw-item-amount');
                            const amountValue = Number(String(amountInput?.value || '0').replace(/,/g, ''));
                            const itemBudgetId = Number(budgetHiddenInput?.value || budgetManageIdInput?.value || 0);
                            return {
                                category: String(categoryHiddenInput?.value || '').trim(),
                                name: String(nameInput?.value || '').trim(),
                                amount: Number.isNaN(amountValue) ? 0 : amountValue,
                                budget_manage_id: Number.isNaN(itemBudgetId) ? 0 : itemBudgetId,
                            };
                        }).filter((row) => row.category !== '' || row.name !== '' || row.amount > 0);

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
                            { label: 'ผู้ตรวจสอบ/หัวหน้างาน-ภาควิชา', value: String(reviewerStaffSelect?.value || '').trim(), target: reviewerStaffSelect },
                        ];

                        const missingFields = requiredChecks.filter((field) => field.value === '');

                        if (itemRows.length === 0) {
                            missingFields.push({
                                label: 'รายการเบิกจ่าย (อย่างน้อย 1 รายการ)',
                                target: itemRowsContainer,
                            });
                        } else {
                            const invalidItemRow = itemRows.find((row) => (
                                row.name === ''
                                || Number(row.amount || 0) <= 0
                                || row.category === ''
                                || (isProjectWithdraw && Number(row.budget_manage_id || 0) <= 0)
                            ));
                            if (invalidItemRow) {
                                missingFields.push({
                                    label: isProjectWithdraw
                                        ? 'รายการเบิกจ่ายต้องกรอก งบประมาณ / หมวด / รายการ / จำนวนเงิน ให้ครบ'
                                        : 'รายการเบิกจ่ายต้องกรอก หมวด / รายการ / จำนวนเงิน ให้ครบ',
                                    target: itemRowsContainer,
                                });
                            }
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

                        const revisionExplanation = String(revisionExplanationInput?.value || '').trim();
                        if (isRevisionEditMode && isProjectWithdraw && revisionExplanation === '') {
                            window.toast?.error?.('กรุณากรอกคำชี้แจงการแก้ไข', { timer: 1800 });
                            revisionExplanationInput?.focus?.();
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
                        formData.append('reviewer_staff_id', String(reviewerStaffSelect?.value || '').trim());
                        formData.append('withdraw_type', String(withdrawTypeInput?.value || withdrawKind || 'general').trim());
                        formData.append('project_id', String(projectIdInput?.value || '').trim());
                        formData.append('project_code', String(projectCodeInput?.value || '').trim());
                        formData.append('project_name', String(projectNameInput?.value || '').trim());
                        formData.append('project_activity_id', String(projectActivityIdInput?.value || '').trim());
                        formData.append('project_activity_name', String(projectActivityNameInput?.value || '').trim());
                        formData.append('project_activity_detail', String(projectActivityDetailInput?.value || '').trim());
                        formData.append('project_handoff_token', String(projectHandoffTokenInput?.value || '').trim());
                        formData.append('project_amount', String(projectAmountInput?.value || cachedProjectAmount || '').trim());
                        const headerBudgetId = String(itemRows.find((row) => Number(row.budget_manage_id || 0) > 0)?.budget_manage_id || budgetManageIdInput?.value || '').trim();
                        if (budgetManageIdInput && headerBudgetId !== '') {
                            budgetManageIdInput.value = headerBudgetId;
                        }
                        formData.append('budget_manage_id', headerBudgetId);
                        formData.append('items', JSON.stringify(itemRows));
                        formData.append('documents', JSON.stringify(selectedDocumentItems));
                        formData.append('process_actors', JSON.stringify(processActors));
                        formData.append('is_revision_update', isRevisionEditMode ? '1' : '0');
                        formData.append('revision_explanation', isRevisionEditMode && isProjectWithdraw ? revisionExplanation : '');
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
                                const savedHandoffToken = String(projectHandoffTokenInput?.value || '').trim();
                                if (savedHandoffToken !== '') {
                                    removeLocalPendingHandoff(savedHandoffToken);
                                }
                                resetGeneralForm();
                                const cleanUrl = new URL(window.location.href);
                                if (cleanUrl.searchParams.has('handoff')) {
                                    cleanUrl.searchParams.delete('handoff');
                                    window.history.replaceState({}, '', cleanUrl.pathname + (cleanUrl.search ? cleanUrl.search : '') + cleanUrl.hash);
                                }
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
    <?php $__env->stopPush(); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH /var/www/application/withdraw/resources/views/withdraw/general/index.blade.php ENDPATH**/ ?>