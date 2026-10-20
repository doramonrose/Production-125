<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class EsignAttachedPdf
{
    public const ROLE_PREPARER = 'preparer';

    public const ROLE_REVIEWER = 'reviewer';

    public const ROLE_SECRETARY = 'secretary';

    public const ROLE_VICE_DEAN = 'vice_dean';

    public const ROLE_REQUESTER = 'requester';

    /** @var array<string, string> */
    private const ROLE_LABELS = [
        self::ROLE_PREPARER => 'ผู้จัดทำ',
        self::ROLE_REVIEWER => 'ผู้ตรวจสอบ',
        self::ROLE_REQUESTER => 'ผู้เบิก',
    ];

    /** @var array<int, string> */
    private const SIGNATURE_ROLES = [
        self::ROLE_PREPARER,
        self::ROLE_REVIEWER,
        self::ROLE_SECRETARY,
        self::ROLE_VICE_DEAN,
        self::ROLE_REQUESTER,
    ];

    /** @var array<int, string> */
    private const ESIGN_LEGAL_NOTICE_LINES = [
        'เอกสารฉบับนี้ใช้ลายมือชื่ออิเล็กทรอนิกส์ ตามพระราชบัญญัติ ว่าด้วยธุรกรรมทางอิเล็กทรอนิกส์ พ.ศ.2544',
        'และข้อบังคับมหาวิทยาลัยเชียงใหม่ ว่าด้วยการใช้ลายมือชื่ออิเล็กทรอนิกส์ พ.ศ.2564',
    ];

    /** @var array<int, string> */
    private const LAST_APPROVER_STEP_CODES = ['faculty_secretary', 'vice_dean', 'dean'];

    public static function isEsignTypeCode(string $typeCode): bool
    {
        $normalized = trim($typeCode);
        if ($normalized === '' || ! Schema::hasTable('withdraw_set_document_type')) {
            return false;
        }

        $name = mb_strtolower(trim((string) (DB::table('withdraw_set_document_type')
            ->where('withdraw_set_document_type_code', $normalized)
            ->value('withdraw_set_document_type_name') ?? '')));

        return $name !== '' && (str_contains($name, 'e-sign') || str_contains($name, 'esign'));
    }

    /**
     * @return array<int, string>
     */
    public static function financeEsignTypeCodes(): array
    {
        if (! Schema::hasTable('withdraw_set_document_type')) {
            return [];
        }

        return DB::table('withdraw_set_document_type')
            ->get([
                'withdraw_set_document_type_code',
                'withdraw_set_document_type_name',
            ])
            ->filter(function ($row): bool {
                $name = mb_strtolower(trim((string) ($row->withdraw_set_document_type_name ?? '')));

                return $name !== '' && (str_contains($name, 'e-sign') || str_contains($name, 'esign'));
            })
            ->map(fn ($row) => trim((string) ($row->withdraw_set_document_type_code ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array{documents: array<int, array<string, mixed>>, finance_esign_documents: array<int, array<string, mixed>>}
     */
    public static function partitionFinanceEsignDocuments(array $documents): array
    {
        $regular = [];
        $finance = [];
        foreach ($documents as $document) {
            $type = trim((string) ($document['type'] ?? ''));
            if ($type !== '' && $type !== 'project_approve_memo' && $type !== 'project_print' && self::isEsignTypeCode($type)) {
                $finance[] = $document;
                continue;
            }
            $regular[] = $document;
        }

        return [
            'documents' => array_values($regular),
            'finance_esign_documents' => array_values($finance),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $files
     * @return array{uploaded_files: array<int, array<string, mixed>>, finance_esign_files: array<int, array<string, mixed>>}
     */
    public static function partitionFinanceEsignUploadedFiles($files): array
    {
        $regular = [];
        $finance = [];
        $itemNames = self::documentItemNames();
        foreach (collect($files)->values()->all() as $file) {
            if (! is_array($file)) {
                continue;
            }
            $type = trim((string) ($file['type'] ?? ''));
            if ($type !== '' && $type !== 'project_approve_memo' && $type !== 'project_print' && self::isEsignTypeCode($type)) {
                $file['item_name'] = self::resolveDocumentItemName(
                    $itemNames,
                    $type,
                    trim((string) ($file['item'] ?? ''))
                );
                $finance[] = $file;
                continue;
            }
            $regular[] = $file;
        }

        return [
            'uploaded_files' => array_values($regular),
            'finance_esign_files' => array_values($finance),
        ];
    }

    /**
     * รายการย่อยของประเภทเอกสาร เก็บทั้งรหัสรายการ (ที่ฟอร์มแนบไฟล์ใช้) และรหัสโค้ด
     *
     * @return array{by_id: array<string, string>, by_code: array<string, string>}
     */
    private static function documentItemNames(): array
    {
        $empty = ['by_id' => [], 'by_code' => []];
        if (! Schema::hasTable('withdraw_set_document_item')) {
            return $empty;
        }

        $names = $empty;
        $rows = DB::table('withdraw_set_document_item')->get([
            'withdraw_set_document_item_id',
            'withdraw_set_document_item_code',
            'withdraw_set_document_type_code',
            'withdraw_set_document_item_name',
        ]);
        foreach ($rows as $row) {
            $name = trim((string) ($row->withdraw_set_document_item_name ?? ''));
            $type = trim((string) ($row->withdraw_set_document_type_code ?? ''));
            if ($name === '' || $type === '') {
                continue;
            }
            $id = trim((string) ($row->withdraw_set_document_item_id ?? ''));
            $code = trim((string) ($row->withdraw_set_document_item_code ?? ''));
            if ($id !== '') {
                $names['by_id'][$type.'#'.$id] = $name;
            }
            if ($code !== '') {
                $names['by_code'][$type.'#'.$code] = $name;
            }
        }

        return $names;
    }

    /**
     * @param  array{by_id: array<string, string>, by_code: array<string, string>}  $names
     */
    private static function resolveDocumentItemName(array $names, string $typeCode, string $itemCode): string
    {
        $typeCode = trim($typeCode);
        $itemCode = trim($itemCode);
        if ($typeCode === '' || $itemCode === '') {
            return '';
        }
        $key = $typeCode.'#'.$itemCode;

        return trim((string) ($names['by_id'][$key] ?? $names['by_code'][$key] ?? ''));
    }

    /**
     * @return array{preparer?: string, reviewer?: string, secretary?: string, vice_dean?: string, requester?: string}
     */
    public static function resolveGeneralSignerStaffIds(object $row, string $uploaderStaffId = ''): array
    {
        $steps = self::loadApprovalSteps(
            'withdraw_approval_step',
            'withdraw_id',
            (int) ($row->withdraw_id ?? 0)
        );

        return self::resolveSignerStaffIdsFromSteps($steps, $uploaderStaffId);
    }

    /**
     * @return array{preparer?: string, reviewer?: string, secretary?: string, vice_dean?: string, requester?: string}
     */
    public static function resolveSalarySignerStaffIds(object $row, string $uploaderStaffId = ''): array
    {
        $steps = self::loadApprovalSteps(
            'withdraw_approval_step',
            'withdraw_id',
            (int) ($row->withdraw_id ?? 0)
        );

        return self::resolveSignerStaffIdsFromSteps($steps, $uploaderStaffId);
    }

    /**
     * Overlay signature images onto an e-Sign PDF. Returns PDF binary, or null to keep the original file.
     *
     * @param  array<string, string>  $signatureDataUris  keyed by role (preparer/reviewer/requester)
     * @param  array{name?:string,position?:string}  $requesterCaption
     */
    public static function stamp(string $sourcePdfPath, array $signatureDataUris, array $requesterCaption = []): ?string
    {
        if ($sourcePdfPath === '' || ! is_file($sourcePdfPath) || ! is_readable($sourcePdfPath)) {
            return null;
        }

        $images = [];
        foreach (self::SIGNATURE_ROLES as $role) {
            $path = self::persistSignatureImage((string) ($signatureDataUris[$role] ?? ''));
            if ($path !== '') {
                $images[$role] = $path;
            }
        }
        $captionName = trim((string) ($requesterCaption['name'] ?? ''));
        $captionPosition = trim((string) ($requesterCaption['position'] ?? ''));
        $approvedDate = trim((string) ($requesterCaption['approved_date'] ?? ''));
        if ($images === [] && $captionName === '' && $captionPosition === '' && $approvedDate === '') {
            return null;
        }

        try {
            return self::overlaySignatures($sourcePdfPath, $images, [
                'name' => $captionName,
                'position' => $captionPosition,
                'position_lines' => array_values(array_filter(
                    array_map('trim', is_array($requesterCaption['position_lines'] ?? null) ? $requesterCaption['position_lines'] : []),
                    static fn ($line) => $line !== ''
                )),
                'approved_date' => $approvedDate,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @return array{name: string, position: string, position_lines: array<int, string>}
     */
    public static function fetchStaffCaption(string $staffId, string $stepCode = ''): array
    {
        $normalizedStaffId = trim($staffId);
        if ($normalizedStaffId === '') {
            return ['name' => '', 'position' => '', 'position_lines' => []];
        }

        $tokenRefreshUrl = trim((string) config('services.staff_rx_api.token_refresh_url', ''));
        $checkStaffUrl = trim((string) config('services.staff_rx_api.check_staff_url', ''));
        if ($tokenRefreshUrl === '' || $checkStaffUrl === '') {
            return ['name' => '', 'position' => '', 'position_lines' => []];
        }

        try {
            $refresh = Http::acceptJson()->timeout(10)->get($tokenRefreshUrl);
            if (! $refresh->successful()) {
                return ['name' => '', 'position' => '', 'position_lines' => []];
            }
            $bearerHeaderToken = trim((string) $refresh->json('token', ''));
            $rawBearerToken = trim((string) $refresh->json('raw_token', ''));
            if ($bearerHeaderToken === '' && $rawBearerToken === '') {
                return ['name' => '', 'position' => '', 'position_lines' => []];
            }

            $http = Http::acceptJson()->timeout(10);
            if ($rawBearerToken !== '') {
                $http = $http->withToken($rawBearerToken);
            } elseif (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                $http = $http->withHeaders(['Authorization' => $bearerHeaderToken]);
            } else {
                $http = $http->withToken($bearerHeaderToken);
            }

            $row = null;
            foreach ([['staffID' => $normalizedStaffId], ['staff_id' => $normalizedStaffId], ['id' => $normalizedStaffId]] as $query) {
                $response = $http->get($checkStaffUrl, $query);
                if (! $response->successful()) {
                    continue;
                }
                $candidate = $response->json('data');
                if (! is_array($candidate)) {
                    $candidate = $response->json();
                }
                if (is_array($candidate) && (
                    trim((string) ($candidate['fname'] ?? '')) !== ''
                    || trim((string) ($candidate['dean_name'] ?? '')) !== ''
                )) {
                    $row = $candidate;
                    break;
                }
            }
            if (! is_array($row)) {
                $positionLines = self::normalizeRequesterPositionLines('', $stepCode);

                return [
                    'name' => '',
                    'position' => (string) ($positionLines[0] ?? ''),
                    'position_lines' => $positionLines,
                ];
            }

            $name = StaffApiName::composeDisplayName($row);
            $position = trim((string) ($row['dean_name'] ?? ''));
            if ($position === '') {
                $position = trim((string) ($row['name_job'] ?? ''));
            }
            if ($position === '') {
                $position = trim((string) ($row['name_position'] ?? $row['position'] ?? ''));
            }

            $positionLines = self::normalizeRequesterPositionLines($position, $stepCode);

            return [
                'name' => $name,
                'position' => (string) ($positionLines[0] ?? $position),
                'position_lines' => $positionLines,
            ];
        } catch (\Throwable $e) {
            return ['name' => '', 'position' => '', 'position_lines' => []];
        }
    }

    /**
     * @return array<int, string>
     */
    public static function normalizeRequesterPositionLines(string $position, string $stepCode = ''): array
    {
        $stepCode = trim($stepCode);
        $position = trim($position);
        $isViceDean = $stepCode === 'vice_dean' || str_contains($position, 'รองคณบดี');
        $isDean = $stepCode === 'dean'
            || ((str_starts_with($position, 'คณบดี') || $position === 'คณบดี') && ! $isViceDean);

        if ($isViceDean) {
            return [
                'รองคณบดี ปฏิบัติการแทน',
                'คณบดีคณะเภสัชศาสตร์',
            ];
        }
        if ($isDean) {
            return ['คณบดีคณะเภสัชศาสตร์'];
        }

        return $position !== '' ? [$position] : [];
    }

    public static function resolveGeneralLastApproverStepCode(object $row): string
    {
        return self::resolveLastApproverStep(
            self::loadApprovalSteps(
                'withdraw_approval_step',
                'withdraw_id',
                (int) ($row->withdraw_id ?? 0)
            )
        )['step_code'];
    }

    public static function resolveSalaryLastApproverStepCode(object $row): string
    {
        return self::resolveLastApproverStep(
            self::loadApprovalSteps(
                'withdraw_approval_step',
                'withdraw_id',
                (int) ($row->withdraw_id ?? 0)
            )
        )['step_code'];
    }

    public static function resolveGeneralLastApproverApprovedDate(object $row): string
    {
        return self::formatLastApproverApprovedDate(
            self::resolveLastApproverStep(
                self::loadApprovalSteps(
                    'withdraw_approval_step',
                    'withdraw_id',
                    (int) ($row->withdraw_id ?? 0)
                )
            )
        );
    }

    public static function resolveSalaryLastApproverApprovedDate(object $row): string
    {
        return self::formatLastApproverApprovedDate(
            self::resolveLastApproverStep(
                self::loadApprovalSteps(
                    'withdraw_approval_step',
                    'withdraw_id',
                    (int) ($row->withdraw_id ?? 0)
                )
            )
        );
    }

    /**
     * @param  array{staff_id?:string,step_code?:string,status?:string,approved_at?:string}  $step
     */
    public static function formatLastApproverApprovedDate(array $step): string
    {
        if (strtolower(trim((string) ($step['status'] ?? ''))) !== 'approved') {
            return '';
        }

        return self::formatThaiApprovalDate((string) ($step['approved_at'] ?? ''));
    }

    public static function formatThaiApprovalDate(string $dateText): string
    {
        $input = trim($dateText);
        if ($input === '') {
            return '';
        }

        try {
            $date = Carbon::parse($input)->timezone(config('app.timezone', 'Asia/Bangkok'));
        } catch (\Throwable $e) {
            return '';
        }

        $longMonths = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม',
        ];
        $day = (int) $date->day;
        $month = $longMonths[(int) $date->month] ?? '';
        $beYear = (int) $date->year + 543;
        if ($month === '') {
            return '';
        }

        return 'วันที่ '.$day.' '.$month.' '.$beYear;
    }

    /**
     * ผู้จัดทำ = ผู้อัปโหลดไฟล์ / งานการเงินที่ตรวจรายการ
     * ผู้ตรวจสอบ = หัวหน้างานการเงิน (แสดงหลังอนุมัติแล้ว)
     * ลายเซ็นห้อยหลังผู้ตรวจสอบ = เลขาคณะ (แสดงหลังอนุมัติแล้ว)
     * ผู้เบิก = รองคณบดี/คณบดี/ผู้อนุมัติลำดับสุดท้าย (แสดงหลังอนุมัติแล้ว)
     *
     * @param  array<int, array{step_code:string,step_order:int,staff_id:string,status:string,approved_at?:string}>  $steps
     * @return array{preparer?: string, reviewer?: string, secretary?: string, vice_dean?: string, requester?: string}
     */
    private static function resolveSignerStaffIdsFromSteps(array $steps, string $uploaderStaffId = ''): array
    {
        $preparerId = trim($uploaderStaffId);
        if ($preparerId === '') {
            $preparerId = self::resolveApprovedStepStaffId($steps, 'finance_officer');
        }
        if ($preparerId === '') {
            $preparerId = self::resolveStepStaffId($steps, 'finance_officer');
        }

        $financeManagerId = self::resolveStepStaffId($steps, 'finance_manager');
        $lastApprover = self::resolveLastApproverStep($steps);
        $lastApproverId = $lastApprover['staff_id'];

        $secretaryId = self::resolveStepStaffId($steps, 'faculty_secretary');
        $viceDeanId = '';
        if (trim((string) ($lastApprover['step_code'] ?? '')) === 'dean') {
            $viceDeanId = self::resolveStepStaffId($steps, 'vice_dean');
        }

        $staffIds = array_filter([
            self::ROLE_PREPARER => $preparerId,
            self::ROLE_REVIEWER => $financeManagerId,
            self::ROLE_SECRETARY => $secretaryId,
            self::ROLE_VICE_DEAN => $viceDeanId,
            self::ROLE_REQUESTER => $lastApproverId,
        ], static fn ($value) => trim((string) $value) !== '');

        return self::gateSignaturesByApproval($staffIds, $steps, $lastApprover);
    }

    /**
     * @param  array<string, string>  $staffIds
     * @param  array<int, array{step_code:string,staff_id:string,status:string}>  $steps
     * @param  array{staff_id: string, step_code: string, status: string, approved_at: string}  $lastApprover
     * @return array<string, string>
     */
    private static function gateSignaturesByApproval(array $staffIds, array $steps, array $lastApprover): array
    {
        // ผู้จัดทำแสดงได้ทันทีหลังอัปโหลด/ตรวจเอกสาร
        $gated = [];
        if (isset($staffIds[self::ROLE_PREPARER])) {
            $gated[self::ROLE_PREPARER] = $staffIds[self::ROLE_PREPARER];
        }

        if (isset($staffIds[self::ROLE_REVIEWER]) && self::isStepApproved($steps, 'finance_manager')) {
            $gated[self::ROLE_REVIEWER] = $staffIds[self::ROLE_REVIEWER];
        }

        if (isset($staffIds[self::ROLE_SECRETARY]) && self::isStepApproved($steps, 'faculty_secretary')) {
            $gated[self::ROLE_SECRETARY] = $staffIds[self::ROLE_SECRETARY];
        }

        if (isset($staffIds[self::ROLE_VICE_DEAN]) && self::isStepApproved($steps, 'vice_dean')) {
            $gated[self::ROLE_VICE_DEAN] = $staffIds[self::ROLE_VICE_DEAN];
        }

        if (
            isset($staffIds[self::ROLE_REQUESTER])
            && strtolower(trim((string) ($lastApprover['status'] ?? ''))) === 'approved'
        ) {
            $gated[self::ROLE_REQUESTER] = $staffIds[self::ROLE_REQUESTER];
        }

        return $gated;
    }

    /**
     * @param  array<int, array{step_code:string,staff_id:string,status:string}>  $steps
     */
    private static function isStepApproved(array $steps, string $stepCode): bool
    {
        $wanted = trim($stepCode);
        if ($wanted === '') {
            return false;
        }

        foreach ($steps as $step) {
            if (trim((string) ($step['step_code'] ?? '')) !== $wanted) {
                continue;
            }

            return strtolower(trim((string) ($step['status'] ?? ''))) === 'approved';
        }

        return false;
    }

    /**
     * @param  array<int, array{step_code:string,staff_id:string,status:string}>  $steps
     */
    private static function resolveApprovedStepStaffId(array $steps, string $stepCode): string
    {
        $wanted = trim($stepCode);
        if ($wanted === '') {
            return '';
        }

        foreach ($steps as $step) {
            if (trim((string) ($step['step_code'] ?? '')) !== $wanted) {
                continue;
            }
            if (strtolower(trim((string) ($step['status'] ?? ''))) !== 'approved') {
                continue;
            }
            $staffId = trim((string) ($step['staff_id'] ?? ''));
            if ($staffId !== '') {
                return $staffId;
            }
        }

        return '';
    }

    /**
     * @param  array<int, array{step_code:string,step_order:int,staff_id:string,status:string,approved_at:string}>  $steps
     * @return array{staff_id: string, step_code: string, status: string, approved_at: string}
     */
    private static function resolveLastApproverStep(array $steps): array
    {
        $executiveSteps = collect($steps)
            ->filter(function ($step) {
                $code = trim((string) ($step['step_code'] ?? ''));

                return in_array($code, self::LAST_APPROVER_STEP_CODES, true)
                    && trim((string) ($step['staff_id'] ?? '')) !== '';
            })
            ->sortBy(fn ($step) => (int) ($step['step_order'] ?? 0))
            ->values();
        $lastExecutive = $executiveSteps->last();
        if (is_array($lastExecutive)) {
            return [
                'staff_id' => trim((string) ($lastExecutive['staff_id'] ?? '')),
                'step_code' => trim((string) ($lastExecutive['step_code'] ?? '')),
                'status' => trim((string) ($lastExecutive['status'] ?? '')),
                'approved_at' => trim((string) ($lastExecutive['approved_at'] ?? '')),
            ];
        }

        $fallback = collect($steps)
            ->filter(function ($step) {
                $code = trim((string) ($step['step_code'] ?? ''));
                if (in_array($code, ['reviewer_head', 'finance_officer', 'additional_reviewer'], true)) {
                    return false;
                }

                return trim((string) ($step['staff_id'] ?? '')) !== '';
            })
            ->sortBy(fn ($step) => (int) ($step['step_order'] ?? 0))
            ->last();
        if (is_array($fallback)) {
            return [
                'staff_id' => trim((string) ($fallback['staff_id'] ?? '')),
                'step_code' => trim((string) ($fallback['step_code'] ?? '')),
                'status' => trim((string) ($fallback['status'] ?? '')),
                'approved_at' => trim((string) ($fallback['approved_at'] ?? '')),
            ];
        }

        return ['staff_id' => '', 'step_code' => '', 'status' => '', 'approved_at' => ''];
    }

    /**
     * @param  array<int, array{step_code:string,staff_id:string}>  $steps
     */
    private static function resolveStepStaffId(array $steps, string $stepCode): string
    {
        $wanted = trim($stepCode);
        if ($wanted === '') {
            return '';
        }

        foreach ($steps as $step) {
            if (trim((string) ($step['step_code'] ?? '')) !== $wanted) {
                continue;
            }
            $staffId = trim((string) ($step['staff_id'] ?? ''));
            if ($staffId !== '') {
                return $staffId;
            }
        }

        if (! Schema::hasTable('withdraw_set_approver')) {
            return '';
        }

        return trim((string) (DB::table('withdraw_set_approver')
            ->where('withdraw_set_approver_step_code', $wanted)
            ->where('withdraw_set_approver_status', 1)
            ->value('withdraw_set_approver_staff_id') ?? ''));
    }

    /**
     * @return array<int, array{step_code:string,step_order:int,staff_id:string,status:string}>
     */
    private static function loadApprovalSteps(string $table, string $foreignKey, int $withdrawId): array
    {
        if ($withdrawId <= 0 || ! Schema::hasTable($table)) {
            return [];
        }

        $codeColumn = str_replace('_step', '_step_code', $table);
        $orderColumn = str_replace('_step', '_step_order', $table);
        $staffColumn = str_replace('_step', '_staff_id', $table);
        $statusColumn = str_replace('_step', '_status', $table);

        $approvedAtColumn = str_replace('_step', '_approved_at', $table);

        return DB::table($table)
            ->where($foreignKey, $withdrawId)
            ->orderBy($orderColumn)
            ->get([
                $codeColumn.' as step_code',
                $orderColumn.' as step_order',
                $staffColumn.' as staff_id',
                $statusColumn.' as status',
                $approvedAtColumn.' as approved_at',
            ])
            ->map(fn ($step) => [
                'step_code' => trim((string) ($step->step_code ?? '')),
                'step_order' => (int) ($step->step_order ?? 0),
                'staff_id' => trim((string) ($step->staff_id ?? '')),
                'status' => trim((string) ($step->status ?? '')),
                'approved_at' => trim((string) ($step->approved_at ?? '')),
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $imagePaths
     * @param  array{name:string,position:string,position_lines?:array<int, string>,approved_date?:string}  $requesterCaption
     */
    private static function overlaySignatures(string $sourcePdfPath, array $imagePaths, array $requesterCaption): string
    {
        $pages = self::extractPdfWords($sourcePdfPath);
        $slotsByPage = self::locateSignatureSlotsFromPages($pages);
        $captionSlotsByPage = self::locateRequesterCaptionSlotsFromPages($pages);
        $tempDir = storage_path('framework/cache/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontDir = array_merge($defaultConfig['fontDir'], [
            storage_path('fonts'),
        ]);
        $fontData = ($defaultFontConfig['fontdata'] ?? []) + [
            'thsarabunnew' => [
                'R' => 'THSarabunNew.ttf',
                'B' => 'THSarabunNew-Bold.ttf',
            ],
        ];

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [215.9, 279.4],
            'tempDir' => $tempDir,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'fontDir' => $fontDir,
            'fontdata' => $fontData,
            'default_font' => 'thsarabunnew',
        ]);
        $mpdf->SetTitle('');

        $rasterPages = [];
        try {
            $pageCount = $mpdf->setSourceFile($sourcePdfPath);
        } catch (\Throwable $e) {
            $rasterPages = self::rasterizePdfPages($sourcePdfPath);
            if ($rasterPages === []) {
                throw $e;
            }
            $pageCount = count($rasterPages);
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => [215.9, 279.4],
                'tempDir' => $tempDir,
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'fontDir' => $fontDir,
                'fontdata' => $fontData,
                'default_font' => 'thsarabunnew',
            ]);
            $mpdf->SetTitle('');
        }
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $rasterPath = $rasterPages[$pageNo - 1] ?? '';
            if ($rasterPath !== '' && is_file($rasterPath)) {
                $rasterSize = @getimagesize($rasterPath);
                $pxW = max(1, (int) ($rasterSize[0] ?? 1));
                $pxH = max(1, (int) ($rasterSize[1] ?? 1));
                $pageWidthMm = $pxW / 120 * 25.4;
                $pageHeightMm = $pxH / 120 * 25.4;
            } else {
                $tplId = $mpdf->importPage($pageNo);
                $size = $mpdf->getTemplateSize($tplId);
                $pageWidthMm = (float) ($size['width'] ?? 215.9);
                $pageHeightMm = (float) ($size['height'] ?? 279.4);
            }
            $orientation = $pageWidthMm > $pageHeightMm ? 'L' : 'P';
            $usingRaster = $rasterPath !== '' && is_file($rasterPath);
            if ($usingRaster || $pageNo > 1) {
                $mpdf->AddPageByArray([
                    'orientation' => $orientation,
                    'newformat' => [$pageWidthMm, $pageHeightMm],
                    'margin-left' => 0,
                    'margin-right' => 0,
                    'margin-top' => 0,
                    'margin-bottom' => 0,
                ]);
            } else {
                $mpdf->_setPageSize([$pageWidthMm, $pageHeightMm], $orientation);
                $mpdf->DefOrientation = $orientation;
                $mpdf->CurOrientation = $orientation;
                $mpdf->w = $pageWidthMm;
                $mpdf->h = $pageHeightMm;
                $mpdf->fw = $pageWidthMm;
                $mpdf->fh = $pageHeightMm;
            }
            if ($usingRaster) {
                $mpdf->Image($rasterPath, 0, 0, $pageWidthMm, $pageHeightMm, 'png', '', true, false);
            } else {
                $mpdf->useTemplate($tplId);
            }

            $pageSlots = $slotsByPage[$pageNo] ?? [];
            if ($pageSlots === [] && $rasterPath !== '' && is_file($rasterPath)) {
                $pageSlots = self::detectScannedSignatureSlots($rasterPath, $pageWidthMm, $pageHeightMm);
            }
            if ($pageSlots === [] && $pageNo === $pageCount && $slotsByPage === []) {
                $pageSlots = self::fallbackSlots($pageWidthMm, $pageHeightMm);
            }
            if (
                $pageSlots !== []
                && (
                    (isset($imagePaths[self::ROLE_SECRETARY]) && is_file((string) $imagePaths[self::ROLE_SECRETARY]))
                    || (isset($imagePaths[self::ROLE_VICE_DEAN]) && is_file((string) $imagePaths[self::ROLE_VICE_DEAN]))
                )
            ) {
                if (! isset($pageSlots[self::ROLE_REVIEWER])) {
                    $fallback = self::fallbackSlots($pageWidthMm, $pageHeightMm);
                    if (isset($fallback[self::ROLE_REVIEWER])) {
                        $pageSlots[self::ROLE_REVIEWER] = $fallback[self::ROLE_REVIEWER];
                    }
                }
                if (isset($pageSlots[self::ROLE_REVIEWER])) {
                    $pageSlots = self::appendSecretarySlotAfterReviewer($pageSlots, $pageWidthMm);
                    if (isset($imagePaths[self::ROLE_VICE_DEAN]) && is_file((string) $imagePaths[self::ROLE_VICE_DEAN])) {
                        $pageSlots = self::appendViceDeanSlotBelowSecretary($pageSlots);
                    }
                }
            }

            $stampedOnPage = false;
            foreach ($pageSlots as $role => $slot) {
                $imagePath = $imagePaths[$role] ?? '';
                if ($imagePath === '' || ! is_file($imagePath)) {
                    continue;
                }
                self::drawFittedSignature($mpdf, $imagePath, $slot);
                $stampedOnPage = true;
            }

            $captionSlots = $captionSlotsByPage[$pageNo] ?? [];
            if (isset($pageSlots[self::ROLE_REQUESTER])) {
                $fromLine = self::captionSlotsFromRequesterSlot($pageSlots[self::ROLE_REQUESTER]);
                if (! isset($captionSlots['name']) && isset($fromLine['name'])) {
                    $captionSlots['name'] = $fromLine['name'];
                }
                if (! isset($captionSlots['position']) && isset($fromLine['position'])) {
                    $captionSlots['position'] = $fromLine['position'];
                }
            }
            $wroteCaption = false;
            if ($captionSlots !== []) {
                try {
                    self::writeRequesterCaption($mpdf, $captionSlots, $requesterCaption);
                    $wroteCaption = trim((string) ($requesterCaption['name'] ?? '')) !== ''
                        || trim((string) ($requesterCaption['position'] ?? '')) !== ''
                        || trim((string) ($requesterCaption['approved_date'] ?? '')) !== '';
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            if ($wroteCaption) {
                $stampedOnPage = true;
            }
            if ($stampedOnPage) {
                self::writeEsignLegalNotice($mpdf, $pageWidthMm, $pageHeightMm);
            }
        }

        $output = $mpdf->Output('', 'S');
        foreach ($rasterPages as $rasterPath) {
            if (is_file($rasterPath)) {
                @unlink($rasterPath);
            }
        }

        return $output;
    }

    /**
     * @return array<int, string>
     */
    private static function rasterizePdfPages(string $sourcePdfPath): array
    {
        $binary = trim((string) shell_exec('command -v pdftoppm'));
        if ($binary === '' || ! is_file($sourcePdfPath)) {
            return [];
        }

        $dir = rtrim(sys_get_temp_dir(), '/').'/esign-raster';
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (! is_dir($dir) || ! is_writable($dir)) {
            return [];
        }

        $prefix = $dir.'/'.uniqid('page-', true);
        $command = $binary.' -png -r 120 '.escapeshellarg($sourcePdfPath).' '.escapeshellarg($prefix).' 2>/dev/null';
        shell_exec($command);
        $files = glob($prefix.'-*.png') ?: [];
        natsort($files);

        return array_values($files);
    }

    /**
     * Scanned vouchers have no text layer. Find the ผู้จัดทำ / ผู้ตรวจสอบ / ผู้เบิก underlines.
     *
     * @return array<string, array{x:float,y:float,w:float,h:float,line_w?:float}>
     */
    private static function detectScannedSignatureSlots(string $imagePath, float $pageWidthMm, float $pageHeightMm): array
    {
        if (! function_exists('imagecreatefrompng')) {
            return [];
        }
        $image = @imagecreatefrompng($imagePath);
        if ($image === false) {
            return [];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 10 || $height < 10) {
            imagedestroy($image);

            return [];
        }

        $yStart = (int) floor($height * 0.60);
        $yEnd = (int) floor($height * 0.82);
        $lines = [];
        for ($y = max(2, $yStart); $y < min($height - 2, $yEnd); $y++) {
            $runStart = null;
            $runs = [];
            for ($x = 20; $x < $width - 20; $x++) {
                $value = self::imageGray($image, $x, $y);
                $above = self::imageGray($image, $x, $y - 2);
                $below = self::imageGray($image, $x, $y + 2);
                $isRule = $value < 210 && ($value + 12) < $above && ($value + 12) < $below;
                if ($isRule) {
                    if ($runStart === null) {
                        $runStart = $x;
                    }
                    continue;
                }
                if ($runStart !== null) {
                    if ($x - $runStart >= 50) {
                        $runs[] = [$runStart, $x - 1];
                    }
                    $runStart = null;
                }
            }
            foreach ($runs as [$start, $end]) {
                $lines[] = ['y' => $y, 'a' => $start, 'b' => $end];
            }
        }
        imagedestroy($image);

        $merged = [];
        foreach ($lines as $line) {
            $matched = null;
            foreach ($merged as $index => $existing) {
                $closeY = abs($existing['y'] - $line['y']) <= 2;
                $overlap = ! ($line['b'] < $existing['a'] - 10 || $line['a'] > $existing['b'] + 10);
                if ($closeY && $overlap) {
                    $matched = $index;
                    break;
                }
            }
            if ($matched === null) {
                $merged[] = $line;
                continue;
            }
            $merged[$matched]['y'] = (int) round(($merged[$matched]['y'] + $line['y']) / 2);
            $merged[$matched]['a'] = min($merged[$matched]['a'], $line['a']);
            $merged[$matched]['b'] = max($merged[$matched]['b'], $line['b']);
        }

        $left = [];
        $right = [];
        foreach ($merged as $line) {
            $length = $line['b'] - $line['a'];
            if ($length < 70) {
                continue;
            }
            $mid = ($line['a'] + $line['b']) / 2;
            if ($mid < $width * 0.55) {
                $left[] = $line;
            } else {
                $right[] = $line;
            }
        }
        usort($left, static fn (array $a, array $b): int => $a['y'] <=> $b['y']);
        usort($right, static fn (array $a, array $b): int => ($b['b'] - $b['a']) <=> ($a['b'] - $a['a']));

        $slotsPx = [];
        $requester = $right[0] ?? null;
        if (is_array($requester)) {
            $slotsPx[self::ROLE_REQUESTER] = $requester;
        }

        $preparer = null;
        if (is_array($requester)) {
            $bestDistance = $height;
            foreach ($left as $line) {
                $distance = abs($line['y'] - $requester['y']);
                if ($distance < $bestDistance && $distance <= (int) ($height * 0.04)) {
                    $bestDistance = $distance;
                    $preparer = $line;
                }
            }
        }
        if (! is_array($preparer) && $left !== []) {
            $preparer = $left[0];
        }
        if (is_array($preparer)) {
            $slotsPx[self::ROLE_PREPARER] = $preparer;
            foreach ($left as $line) {
                $gap = $line['y'] - $preparer['y'];
                if ($gap >= 40 && $gap <= (int) ($height * 0.08)) {
                    $slotsPx[self::ROLE_REVIEWER] = $line;
                    break;
                }
            }
        }

        $dpi = 120.0;
        $slots = [];
        foreach ($slotsPx as $role => $line) {
            $labelInset = 7.0;
            $lineW = max(16.0, (($line['b'] - $line['a']) / $dpi * 25.4) - $labelInset);
            $w = min(42.0, max(18.0, $lineW));
            $h = 9.0;
            $x = ($line['a'] / $dpi * 25.4) + $labelInset;
            $yLine = $line['y'] / $dpi * 25.4;
            $slots[$role] = [
                'x' => $x,
                'y' => $yLine - $h + 0.2,
                'w' => $w,
                'h' => $h,
                'line_w' => $lineW + $labelInset,
            ];
        }

        return $slots;
    }

    private static function imageGray(\GdImage $image, int $x, int $y): int
    {
        $color = imagecolorat($image, $x, $y);
        if (! imageistruecolor($image)) {
            $rgb = imagecolorsforindex($image, $color);

            return (int) round((((int) $rgb['red']) + ((int) $rgb['green']) + ((int) $rgb['blue'])) / 3);
        }
        $red = ($color >> 16) & 255;
        $green = ($color >> 8) & 255;
        $blue = $color & 255;

        return (int) round(($red + $green + $blue) / 3);
    }

    /**
     * @param  array<int, array{width:float,height:float,words:array<int, array{text:string,xMin:float,yMin:float,xMax:float,yMax:float}>}>  $pages
     * @return array<int, array<string, array{x:float,y:float,w:float,h:float}>>
     */
    private static function locateSignatureSlotsFromPages(array $pages): array
    {
        if ($pages === []) {
            return [];
        }

        $result = [];
        foreach ($pages as $pageNo => $page) {
            $pageWidthPt = max(1.0, (float) ($page['width'] ?? 612));
            $pageHeightPt = max(1.0, (float) ($page['height'] ?? 792));
            $pageWidthMm = $pageWidthPt * 25.4 / 72;
            $pageHeightMm = $pageHeightPt * 25.4 / 72;
            $words = $page['words'] ?? [];
            foreach (self::ROLE_LABELS as $role => $label) {
                $slotPt = self::findUnderlineSlot($words, $label);
                if ($slotPt === null) {
                    continue;
                }
                $result[$pageNo][$role] = self::slotPtToMm($slotPt, $pageWidthPt, $pageHeightPt, $pageWidthMm, $pageHeightMm);
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array{width:float,height:float,words:array<int, array{text:string,xMin:float,yMin:float,xMax:float,yMax:float}>}>  $pages
     * @return array<int, array{name?:array{x:float,y:float,w:float,h:float},position?:array{x:float,y:float,w:float,h:float}}>
     */
    private static function locateRequesterCaptionSlotsFromPages(array $pages): array
    {
        $result = [];
        foreach ($pages as $pageNo => $page) {
            $pageWidthPt = max(1.0, (float) ($page['width'] ?? 612));
            $pageHeightPt = max(1.0, (float) ($page['height'] ?? 792));
            $pageWidthMm = $pageWidthPt * 25.4 / 72;
            $pageHeightMm = $pageHeightPt * 25.4 / 72;
            $slotsPt = self::findRequesterCaptionBoxes($page['words'] ?? []);
            foreach ($slotsPt as $key => $box) {
                $result[$pageNo][$key] = self::boxPtToMm($box, $pageWidthPt, $pageHeightPt, $pageWidthMm, $pageHeightMm);
            }
        }

        return $result;
    }

    /**
     * @param  array{x:float,y:float,w:float,h:float}  $slotPt
     * @return array{x:float,y:float,w:float,h:float}
     */
    private static function slotPtToMm(array $slotPt, float $pageWidthPt, float $pageHeightPt, float $pageWidthMm, float $pageHeightMm): array
    {
        $scaleX = $pageWidthMm / $pageWidthPt;
        $scaleY = $pageHeightMm / $pageHeightPt;
        $underlineW = max(8.0, (float) $slotPt['w'] * $scaleX);
        $lineTop = (float) $slotPt['y'] * $scaleY;
        $lineH = max(2.4, (float) $slotPt['h'] * $scaleY);
        $w = $underlineW >= 22.0
            ? min(42.0, max(22.0, $underlineW * 0.95))
            : max(18.0, $underlineW);
        $h = 11.0;
        $x = (float) $slotPt['x'] * $scaleX;
        $y = $lineTop - ($h - $lineH) + 0.35;

        return [
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
        ];
    }

    /**
     * @param  array{x:float,y:float,w:float,h:float}  $boxPt
     * @return array{x:float,y:float,w:float,h:float}
     */
    private static function boxPtToMm(array $boxPt, float $pageWidthPt, float $pageHeightPt, float $pageWidthMm, float $pageHeightMm): array
    {
        $scaleX = $pageWidthMm / $pageWidthPt;
        $scaleY = $pageHeightMm / $pageHeightPt;

        return [
            'x' => (float) $boxPt['x'] * $scaleX,
            'y' => (float) $boxPt['y'] * $scaleY,
            'w' => (float) $boxPt['w'] * $scaleX,
            'h' => (float) $boxPt['h'] * $scaleY,
        ];
    }

    /**
     * @param  array<int, array{text:string,xMin:float,yMin:float,xMax:float,yMax:float}>  $words
     * @return array{name?:array{x:float,y:float,w:float,h:float},position?:array{x:float,y:float,w:float,h:float}}
     */
    private static function findRequesterCaptionBoxes(array $words): array
    {
        $requesterWord = null;
        foreach ($words as $word) {
            if (! str_contains((string) ($word['text'] ?? ''), 'ผู้เบิก')) {
                continue;
            }
            if ($requesterWord === null || (float) $word['yMin'] > (float) $requesterWord['yMin']) {
                $requesterWord = $word;
            }
        }
        if (! is_array($requesterWord)) {
            return [];
        }

        $requesterLeft = (float) $requesterWord['xMin'];
        $requesterRight = (float) $requesterWord['xMax'];
        $requesterMidY = ((float) $requesterWord['yMin'] + (float) $requesterWord['yMax']) / 2;

        $open = null;
        $close = null;
        $positionWord = null;
        foreach ($words as $word) {
            $text = trim((string) ($word['text'] ?? ''));
            $midX = ((float) $word['xMin'] + (float) $word['xMax']) / 2;
            $midY = ((float) $word['yMin'] + (float) $word['yMax']) / 2;
            if ($midY < $requesterMidY - 2 || $midX < $requesterLeft - 30) {
                continue;
            }
            if ($text === '(' && ($open === null || (float) $word['yMin'] < (float) $open['yMin'])) {
                $open = $word;
            }
            if ($text === ')' && ($close === null || (float) $word['yMin'] < (float) $close['yMin'])) {
                $close = $word;
            }
            if ($text === 'ตำแหน่ง' && ($positionWord === null || (float) $word['yMin'] < (float) $positionWord['yMin'])) {
                $positionWord = $word;
            }
        }

        $slots = [];
        if (is_array($open) && is_array($close) && (float) $close['xMin'] > (float) $open['xMin']) {
            $nameLeft = (float) $open['xMax'];
            $nameRight = (float) $close['xMin'];
            $slots['name'] = [
                'x' => $nameLeft,
                'y' => (float) $open['yMin'] - 1.5,
                'w' => max(24.0, $nameRight - $nameLeft),
                'h' => max(16.0, (float) $open['yMax'] - (float) $open['yMin'] + 3.0),
            ];
        } else {
            $slots['name'] = [
                'x' => $requesterLeft,
                'y' => (float) $requesterWord['yMax'] + 0.2,
                'w' => max(24.0, $requesterRight - $requesterLeft),
                'h' => max(16.0, (float) $requesterWord['yMax'] - (float) $requesterWord['yMin'] + 2.0),
            ];
        }
        if (is_array($positionWord)) {
            $slots['position'] = [
                'x' => (float) $positionWord['xMax'] + 3.0,
                'y' => (float) $positionWord['yMin'] - 1.0,
                'w' => max(40.0, $requesterRight - (float) $positionWord['xMax'] - 3.0),
                'h' => max(10.0, (float) $positionWord['yMax'] - (float) $positionWord['yMin'] + 1.5),
            ];
        }

        return $slots;
    }

    /**
     * @param  array{x:float,y:float,w:float,h:float,line_w?:float}  $requesterSlot
     * @return array{name:array{x:float,y:float,w:float,h:float},position:array{x:float,y:float,w:float,h:float}}
     */
    private static function captionSlotsFromRequesterSlot(array $requesterSlot): array
    {
        $sigX = (float) $requesterSlot['x'];
        $lineW = max(28.0, (float) ($requesterSlot['line_w'] ?? $requesterSlot['w']));
        $lineBottom = (float) $requesterSlot['y'] + (float) $requesterSlot['h'];
        $nameX = max(0.0, $sigX - 12.0);
        $nameW = $lineW + ($sigX - $nameX);

        return [
            'name' => [
                'x' => $nameX,
                'y' => $lineBottom + 0.2,
                'w' => max(28.0, $nameW),
                'h' => 6.2,
            ],
            'position' => [
                'x' => $sigX,
                'y' => $lineBottom + 5.3,
                'w' => max(28.0, $lineW),
                'h' => 4.6,
            ],
        ];
    }

    /**
     * @param  array{name?:array{x:float,y:float,w:float,h:float},position?:array{x:float,y:float,w:float,h:float}}  $slots
     * @param  array{name:string,position:string,position_lines?:array<int, string>,approved_date?:string}  $caption
     */
    private static function writeRequesterCaption(\Mpdf\Mpdf $mpdf, array $slots, array $caption): void
    {
        $name = trim((string) ($caption['name'] ?? ''));
        $approvedDate = trim((string) ($caption['approved_date'] ?? ''));
        $positionLines = array_values(array_filter(
            array_map('trim', is_array($caption['position_lines'] ?? null) ? $caption['position_lines'] : []),
            static fn ($line) => $line !== ''
        ));
        if ($positionLines === []) {
            $single = trim((string) ($caption['position'] ?? ''));
            if ($single !== '') {
                $positionLines = [$single];
            }
        }
        if ($name === '' && $positionLines === [] && $approvedDate === '') {
            return;
        }

        $mpdf->SetTextColor(0, 0, 0);
        if ($name !== '' && isset($slots['name']) && is_array($slots['name'])) {
            self::writeCenteredFittedCell($mpdf, $slots['name'], $name, 15.0, 11.0);
        }
        $positionBox = (isset($slots['position']) && is_array($slots['position'])) ? $slots['position'] : null;
        if ($positionLines !== [] && $positionBox !== null) {
            $lineH = max(4.2, (float) $positionBox['h'] - 0.4);
            foreach ($positionLines as $index => $line) {
                $lineBox = $positionBox;
                $lineBox['y'] = (float) $positionBox['y'] + ($index * $lineH);
                $lineBox['w'] = max((float) $positionBox['w'], 52.0);
                self::writeFittedCell($mpdf, $lineBox, $line, 'L');
            }
        }
        if ($approvedDate !== '' && $positionBox !== null) {
            $lineH = max(4.2, (float) $positionBox['h'] - 0.4);
            $dateBox = $positionBox;
            $dateBox['y'] = (float) $positionBox['y'] + (count($positionLines) * $lineH) + 0.4;
            $dateBox['w'] = max((float) $positionBox['w'], 52.0);
            self::writeFittedCell($mpdf, $dateBox, $approvedDate, 'L');
        }
    }

    /**
     * @param  array{x:float,y:float,w:float,h:float}  $box
     */
    private static function writeFittedCell(
        \Mpdf\Mpdf $mpdf,
        array $box,
        string $text,
        string $align,
        float $startFont = 14.0,
        float $minFont = 9.0
    ): void {
        $x = (float) $box['x'];
        $y = (float) $box['y'];
        $w = max(8.0, (float) $box['w']);
        $h = max(4.0, (float) $box['h']);
        $fontSize = max($minFont, $startFont);
        $mpdf->SetFont('thsarabunnew', '', $fontSize);
        while ($fontSize > $minFont && $mpdf->GetStringWidth($text) > $w) {
            $fontSize -= 0.5;
            $mpdf->SetFont('thsarabunnew', '', $fontSize);
        }
        $savedL = $mpdf->cMarginL;
        $savedR = $mpdf->cMarginR;
        $mpdf->cMarginL = 0;
        $mpdf->cMarginR = 0;
        $mpdf->SetXY($x, $y);
        $mpdf->Cell($w, $h, $text, 0, 0, $align);
        $mpdf->cMarginL = $savedL;
        $mpdf->cMarginR = $savedR;
    }

    /**
     * @param  array{x:float,y:float,w:float,h:float}  $box
     */
    private static function writeCenteredFittedCell(
        \Mpdf\Mpdf $mpdf,
        array $box,
        string $text,
        float $startFont = 14.0,
        float $minFont = 9.0
    ): void {
        $boxX = (float) $box['x'];
        $boxY = (float) $box['y'];
        $w = max(8.0, (float) $box['w']);
        $h = max(4.0, (float) $box['h']);
        $fontSize = max($minFont, $startFont);
        $mpdf->SetFont('thsarabunnew', '', $fontSize);
        while ($fontSize > $minFont && $mpdf->GetStringWidth($text) > $w) {
            $fontSize -= 0.5;
            $mpdf->SetFont('thsarabunnew', '', $fontSize);
        }
        $savedL = $mpdf->cMarginL;
        $savedR = $mpdf->cMarginR;
        $mpdf->cMarginL = 0;
        $mpdf->cMarginR = 0;
        $mpdf->SetXY($boxX, $boxY);
        $mpdf->Cell($w, $h, $text, 0, 0, 'C');
        $mpdf->cMarginL = $savedL;
        $mpdf->cMarginR = $savedR;
    }

    private static function writeEsignLegalNotice(\Mpdf\Mpdf $mpdf, float $pageWidthMm, float $pageHeightMm): void
    {
        $lines = self::ESIGN_LEGAL_NOTICE_LINES;
        $marginX = 10.0;
        $w = max(40.0, $pageWidthMm - ($marginX * 2));
        $lineH = 5.0;
        $fontSize = 12.0;
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFont('thsarabunnew', '', $fontSize);
        foreach ($lines as $line) {
            while ($fontSize > 8.5 && $mpdf->GetStringWidth($line) > $w) {
                $fontSize -= 0.5;
                $mpdf->SetFont('thsarabunnew', '', $fontSize);
            }
        }
        $blockH = $lineH * count($lines);
        $y = max(8.0, $pageHeightMm - $blockH - 7.0);
        foreach ($lines as $index => $line) {
            $mpdf->SetXY($marginX, $y + ($index * $lineH));
            $mpdf->Cell($w, $lineH, $line, 0, 0, 'C');
        }
    }

    /**
     * @return array{x:float,y:float,w:float,h:float}
     */
    private static function fallbackSlots(float $pageWidthMm, float $pageHeightMm): array
    {
        $scaleX = $pageWidthMm / 215.9;
        $scaleY = $pageHeightMm / 279.4;
        $fromLetter = static function (float $xPt, float $yPt, float $wPt, float $hPt) use ($scaleX, $scaleY): array {
            $xMm = $xPt * 25.4 / 72 * $scaleX;
            $yMm = $yPt * 25.4 / 72 * $scaleY;
            $wMm = $wPt * 25.4 / 72 * $scaleX;
            $hMm = $hPt * 25.4 / 72 * $scaleY;

            return [
                'x' => $xMm,
                'y' => $yMm - 6.5,
                'w' => min(42.0, max(32.0, $wMm)),
                'h' => 12.0,
            ];
        };

        return [
            self::ROLE_PREPARER => $fromLetter(133.91, 526.29, 96.09, 14.3),
            self::ROLE_REVIEWER => $fromLetter(148.0, 568.67, 83.22, 14.3),
            self::ROLE_REQUESTER => $fromLetter(396.0, 511.60, 125.43, 14.3),
        ];
    }

    /**
     * Place the faculty-secretary signature on the right of the ผู้ตรวจสอบ underline.
     * Keep the reviewer size; overlap the line end so the mark is not floating in whitespace.
     *
     * @param  array<string, array{x:float,y:float,w:float,h:float}>  $pageSlots
     * @return array<string, array{x:float,y:float,w:float,h:float}>
     */
    private static function appendSecretarySlotAfterReviewer(array $pageSlots, float $pageWidthMm): array
    {
        if (! isset($pageSlots[self::ROLE_REVIEWER]) || ! is_array($pageSlots[self::ROLE_REVIEWER])) {
            return $pageSlots;
        }

        $reviewer = $pageSlots[self::ROLE_REVIEWER];
        $origX = (float) $reviewer['x'];
        $origY = (float) $reviewer['y'];
        $origW = (float) $reviewer['w'];
        $secretaryW = 14.0;
        $secretaryH = 7.5;
        $secretaryX = $origX + max(12.0, $origW) + 1.2;
        $secretaryY = $origY + max(0.0, (float) ($reviewer['h'] ?? 11.0) - $secretaryH);

        $maxRight = $pageWidthMm - 8.0;
        if (isset($pageSlots[self::ROLE_REQUESTER]) && is_array($pageSlots[self::ROLE_REQUESTER])) {
            $requester = $pageSlots[self::ROLE_REQUESTER];
            $sameRow = abs((float) $requester['y'] - $origY) < 12.0;
            if ($sameRow) {
                $maxRight = min($maxRight, (float) $requester['x'] - 3.0);
            }
        }
        if ($secretaryX + $secretaryW > $maxRight) {
            $secretaryX = max($origX + 6.0, $maxRight - $secretaryW);
        }
        if ($secretaryW > $maxRight - $secretaryX) {
            $secretaryW = max(12.0, $maxRight - $secretaryX);
        }

        $pageSlots[self::ROLE_SECRETARY] = [
            'x' => $secretaryX,
            'y' => $secretaryY,
            'w' => $secretaryW,
            'h' => $secretaryH,
        ];

        return $pageSlots;
    }

    /**
     * Place the vice-dean signature under เลขาคณะ, same size, no extra label.
     *
     * @param  array<string, array{x:float,y:float,w:float,h:float}>  $pageSlots
     * @return array<string, array{x:float,y:float,w:float,h:float}>
     */
    private static function appendViceDeanSlotBelowSecretary(array $pageSlots): array
    {
        $anchor = null;
        if (isset($pageSlots[self::ROLE_SECRETARY]) && is_array($pageSlots[self::ROLE_SECRETARY])) {
            $anchor = $pageSlots[self::ROLE_SECRETARY];
        } elseif (isset($pageSlots[self::ROLE_REVIEWER]) && is_array($pageSlots[self::ROLE_REVIEWER])) {
            $anchor = $pageSlots[self::ROLE_REVIEWER];
        }
        if (! is_array($anchor)) {
            return $pageSlots;
        }

        $w = max(12.0, min(14.0, (float) ($anchor['w'] ?? 14.0)));
        $h = max(7.0, min(7.5, (float) ($anchor['h'] ?? 7.5)));
        $pageSlots[self::ROLE_VICE_DEAN] = [
            'x' => (float) $anchor['x'],
            'y' => (float) $anchor['y'] + (float) ($anchor['h'] ?? $h) + 1.6,
            'w' => $w,
            'h' => $h,
        ];

        return $pageSlots;
    }

    /**
     * @param  array<int, array{text:string,xMin:float,yMin:float,xMax:float,yMax:float}>  $words
     * @return array{x:float,y:float,w:float,h:float}|null
     */
    private static function findUnderlineSlot(array $words, string $label): ?array
    {
        $count = count($words);
        $best = null;
        $bestScore = -1.0;
        for ($i = 0; $i < $count; $i++) {
            $text = (string) ($words[$i]['text'] ?? '');
            if ($text === '' || ! str_contains($text, $label)) {
                continue;
            }

            $slot = self::underlineSlotForLabelWord($words, $i);
            if ($slot === null) {
                continue;
            }
            $hasLine = $slot['w'] >= 18.0;
            $y = (float) $slot['y'];
            $score = ($hasLine ? 1000.0 : 0.0) + $y;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $slot;
            }
        }

        return $best;
    }

    /**
     * @param  array<int, array{text:string,xMin:float,yMin:float,xMax:float,yMax:float}>  $words
     * @return array{x:float,y:float,w:float,h:float}|null
     */
    private static function underlineSlotForLabelWord(array $words, int $index): ?array
    {
        $labelWord = $words[$index] ?? null;
        if (! is_array($labelWord)) {
            return null;
        }

        $text = (string) ($labelWord['text'] ?? '');
        $underscoreAt = mb_strpos($text, '_');
        if ($underscoreAt !== false && $underscoreAt > 0) {
            return self::splitCombinedUnderlineSlot($labelWord, $underscoreAt);
        }

        $union = self::unionSameLineUnderlines($words, $index);
        if ($union !== null) {
            return $union;
        }

        return null;
    }

    /**
     * @param  array<int, array{text:string,xMin:float,yMin:float,xMax:float,yMax:float}>  $words
     * @return array{x:float,y:float,w:float,h:float}|null
     */
    private static function unionSameLineUnderlines(array $words, int $labelIndex): ?array
    {
        $label = $words[$labelIndex];
        $labelRight = (float) $label['xMax'];
        $xMin = null;
        $yMin = null;
        $xMax = null;
        $yMax = null;
        $count = count($words);
        for ($i = 0; $i < $count; $i++) {
            if ($i === $labelIndex) {
                continue;
            }
            $word = $words[$i];
            if (! self::isSameLine($label, $word)) {
                continue;
            }
            if ((float) $word['xMin'] < $labelRight - 1.0) {
                continue;
            }
            if (! self::isUnderlineText((string) ($word['text'] ?? ''))) {
                continue;
            }
            $xMin = $xMin === null ? (float) $word['xMin'] : min($xMin, (float) $word['xMin']);
            $yMin = $yMin === null ? (float) $word['yMin'] : min($yMin, (float) $word['yMin']);
            $xMax = $xMax === null ? (float) $word['xMax'] : max($xMax, (float) $word['xMax']);
            $yMax = $yMax === null ? (float) $word['yMax'] : max($yMax, (float) $word['yMax']);
        }
        if ($xMin === null || $yMin === null || $xMax === null || $yMax === null) {
            return null;
        }

        return [
            'x' => $xMin,
            'y' => $yMin,
            'w' => max(12.0, $xMax - $xMin),
            'h' => max(8.0, $yMax - $yMin),
        ];
    }

    /**
     * @param  array{text:string,xMin:float,yMin:float,xMax:float,yMax:float}  $word
     * @return array{x:float,y:float,w:float,h:float}
     */
    private static function splitCombinedUnderlineSlot(array $word, int $underscoreAt): array
    {
        $text = (string) ($word['text'] ?? '');
        $totalChars = max(1, mb_strlen($text));
        $labelChars = max(1, $underscoreAt);
        $underscoreChars = max(1, $totalChars - $underscoreAt);
        $labelWeight = $labelChars * 1.55;
        $underscoreWeight = $underscoreChars * 1.0;
        $labelFraction = $labelWeight / ($labelWeight + $underscoreWeight);
        $width = max(8.0, (float) $word['xMax'] - (float) $word['xMin']);
        $x = (float) $word['xMin'] + ($width * $labelFraction) + 1.5;
        $w = max(24.0, (float) $word['xMax'] - $x);

        return [
            'x' => $x,
            'y' => (float) $word['yMin'],
            'w' => $w,
            'h' => max(10.0, (float) $word['yMax'] - (float) $word['yMin']),
        ];
    }

    /**
     * @param  array{xMin:float,yMin:float,xMax:float,yMax:float}  $word
     * @return array{x:float,y:float,w:float,h:float}
     */
    private static function bboxToSlot(array $word): array
    {
        return [
            'x' => (float) $word['xMin'],
            'y' => (float) $word['yMin'],
            'w' => max(24.0, (float) $word['xMax'] - (float) $word['xMin']),
            'h' => max(10.0, (float) $word['yMax'] - (float) $word['yMin']),
        ];
    }

    /**
     * @param  array{yMin:float,yMax:float}  $a
     * @param  array{yMin:float,yMax:float}  $b
     */
    private static function isSameLine(array $a, array $b): bool
    {
        return abs(((float) $a['yMin'] + (float) $a['yMax']) / 2 - ((float) $b['yMin'] + (float) $b['yMax']) / 2) <= 4.0;
    }

    private static function isUnderlineText(string $text): bool
    {
        $normalized = trim($text);

        return $normalized !== '' && preg_match('/^[_….\-\s]+$/u', $normalized) === 1;
    }

    /**
     * @return array<int, array{width:float,height:float,words:array<int, array{text:string,xMin:float,yMin:float,xMax:float,yMax:float}>}>
     */
    private static function extractPdfWords(string $sourcePdfPath): array
    {
        $binary = '';
        foreach (['/usr/bin/pdftotext', '/usr/local/bin/pdftotext'] as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                $binary = $candidate;
                break;
            }
        }
        if ($binary === '') {
            $binary = trim((string) shell_exec('command -v pdftotext'));
        }
        if ($binary === '') {
            return [];
        }

        $command = $binary.' -bbox '.escapeshellarg($sourcePdfPath).' - 2>/dev/null';
        $html = (string) shell_exec($command);
        if (trim($html) === '') {
            return [];
        }

        $pages = [];
        if (preg_match_all('/<page\b([^>]*)>(.*?)<\/page>/is', $html, $pageMatches, PREG_SET_ORDER) < 1) {
            return [];
        }

        $pageNo = 0;
        foreach ($pageMatches as $pageMatch) {
            $pageNo++;
            $attrs = (string) ($pageMatch[1] ?? '');
            $body = (string) ($pageMatch[2] ?? '');
            $width = self::attrFloat($attrs, 'width', 612);
            $height = self::attrFloat($attrs, 'height', 792);
            $words = [];
            if (preg_match_all('/<word\b([^>]*)>(.*?)<\/word>/is', $body, $wordMatches, PREG_SET_ORDER) > 0) {
                foreach ($wordMatches as $wordMatch) {
                    $wordAttrs = (string) ($wordMatch[1] ?? '');
                    $text = html_entity_decode(trim(strip_tags((string) ($wordMatch[2] ?? ''))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if ($text === '') {
                        continue;
                    }
                    $words[] = [
                        'text' => $text,
                        'xMin' => self::attrFloat($wordAttrs, 'xMin', 0),
                        'yMin' => self::attrFloat($wordAttrs, 'yMin', 0),
                        'xMax' => self::attrFloat($wordAttrs, 'xMax', 0),
                        'yMax' => self::attrFloat($wordAttrs, 'yMax', 0),
                    ];
                }
            }
            $pages[$pageNo] = [
                'width' => $width,
                'height' => $height,
                'words' => $words,
            ];
        }

        return $pages;
    }

    private static function attrFloat(string $attrs, string $name, float $default): float
    {
        if (preg_match('/\b'.preg_quote($name, '/').'\s*=\s*"([^"]+)"/i', $attrs, $match) !== 1) {
            return $default;
        }

        return (float) $match[1];
    }

    /**
     * @param  array{x:float,y:float,w:float,h:float}  $slot
     */
    private static function drawFittedSignature(\Mpdf\Mpdf $mpdf, string $imagePath, array $slot): void
    {
        $boxW = max(8.0, (float) $slot['w']);
        $boxH = max(6.0, (float) $slot['h']);
        $boxX = (float) $slot['x'];
        $boxY = (float) $slot['y'];
        $drawW = $boxW;
        $drawH = $boxH;
        $info = @getimagesize($imagePath);
        if (is_array($info) && (int) ($info[0] ?? 0) > 0 && (int) ($info[1] ?? 0) > 0) {
            $ratio = (float) $info[0] / (float) $info[1];
            if ($ratio > 0.15) {
                if ($boxW / $boxH > $ratio) {
                    $drawH = $boxH;
                    $drawW = $boxH * $ratio;
                } else {
                    $drawW = $boxW;
                    $drawH = $boxW / $ratio;
                }
            }
        }

        $mpdf->Image(
            $imagePath,
            $boxX,
            $boxY + max(0.0, $boxH - $drawH),
            $drawW,
            $drawH,
            '',
            '',
            true,
            false
        );
    }

    private static function persistSignatureImage(string $dataUri): string
    {
        $dataUri = trim($dataUri);
        if ($dataUri === '') {
            return '';
        }

        if (str_starts_with($dataUri, 'file://')) {
            $localPath = substr($dataUri, 7);
            if ($localPath !== '' && is_file($localPath)) {
                return realpath($localPath) ?: $localPath;
            }

            return '';
        }

        if (! str_starts_with($dataUri, 'data:') && is_file($dataUri)) {
            return realpath($dataUri) ?: $dataUri;
        }

        if (! preg_match('#^data:image/(jpeg|jpg|png|gif|webp)[^,]*,([A-Za-z0-9+/=\r\n]+)$#i', $dataUri, $matches)) {
            return '';
        }

        $extension = strtolower((string) ($matches[1] ?? 'png'));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $binary = base64_decode((string) ($matches[2] ?? ''), true);
        if ($binary === false || $binary === '') {
            return '';
        }

        $hash = substr(md5($binary), 0, 20);
        $fileName = 'esign-v2-'.$hash.'.png';
        $directories = [
            storage_path('framework/cache/mpdf/signatures'),
            storage_path('framework/cache/mpdf'),
            sys_get_temp_dir(),
        ];
        $filePath = '';
        foreach ($directories as $directory) {
            if ($directory === '') {
                continue;
            }
            if (! is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
            if (! is_dir($directory)) {
                continue;
            }
            $candidate = $directory.DIRECTORY_SEPARATOR.$fileName;
            if (is_file($candidate) && (int) filesize($candidate) >= 50) {
                $filePath = $candidate;
                break;
            }
            if (! is_writable($directory)) {
                continue;
            }
            if (@file_put_contents($candidate, $binary) === false) {
                continue;
            }
            self::knockoutSignatureBackground($candidate);
            $filePath = $candidate;
            break;
        }
        if ($filePath === '' || ! is_file($filePath)) {
            return '';
        }

        return realpath($filePath) ?: $filePath;
    }

    private static function knockoutSignatureBackground(string $filePath): void
    {
        if (! function_exists('imagecreatetruecolor') || ! is_file($filePath)) {
            return;
        }

        $binary = (string) @file_get_contents($filePath);
        if ($binary === '') {
            return;
        }
        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 4 || $height < 4) {
            imagedestroy($source);

            return;
        }

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefilledrectangle($dest, 0, 0, $width, $height, $transparent);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($source, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                if ($alpha >= 120 || ($r > 242 && $g > 242 && $b > 242)) {
                    continue;
                }
                $color = imagecolorallocatealpha($dest, $r, $g, $b, $alpha);
                imagesetpixel($dest, $x, $y, $color);
            }
        }

        imagepng($dest, $filePath);
        imagedestroy($source);
        imagedestroy($dest);
        self::cropSignatureOpaqueBounds($filePath);
    }

    private static function cropSignatureOpaqueBounds(string $filePath): void
    {
        if (! function_exists('imagecreatetruecolor') || ! is_file($filePath)) {
            return;
        }

        $source = @imagecreatefrompng($filePath);
        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($source, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha >= 120) {
                    continue;
                }
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                if ($r > 242 && $g > 242 && $b > 242) {
                    continue;
                }
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }
        if ($maxX < $minX || $maxY < $minY) {
            imagedestroy($source);

            return;
        }

        $padX = max(2, (int) round(($maxX - $minX + 1) * 0.06));
        $padY = max(2, (int) round(($maxY - $minY + 1) * 0.08));
        $cropX = max(0, $minX - $padX);
        $cropY = max(0, $minY - $padY);
        $cropW = min($width - $cropX, ($maxX - $minX + 1) + ($padX * 2));
        $cropH = min($height - $cropY, ($maxY - $minY + 1) + ($padY * 2));
        if ($cropW < 8 || $cropH < 8 || ($cropW >= $width - 2 && $cropH >= $height - 2)) {
            imagedestroy($source);

            return;
        }

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);
        imagecopy($cropped, $source, 0, 0, $cropX, $cropY, $cropW, $cropH);
        imagepng($cropped, $filePath);
        imagedestroy($source);
        imagedestroy($cropped);
    }
}
