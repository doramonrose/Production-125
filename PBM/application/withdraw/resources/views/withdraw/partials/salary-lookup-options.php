<?php
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
